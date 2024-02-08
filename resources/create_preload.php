<?php

use HughCube\Laravel\Knight\OPcache\OPcache;
use HughCube\Laravel\Knight\Support\Str;
use Illuminate\Support\Collection;
use PhpParser\ParserFactory;

$excludes = [];
$publicPath = getcwd();
$preloadFile = $publicPath.'/../preload.php';

$classes = array_merge(
    get_declared_classes(),
    get_declared_interfaces(),
    get_declared_traits()
);

call_user_func(function () {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? null ?: 'localhost';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? null ?: '127.0.0.1';
});

/** index.php */
call_user_func(function () use ($publicPath) {
    ob_start();
    require $publicPath.'/index.php';
    ob_clean();
});

/** OPcache Scripts */
call_user_func(function () {
    if ('1' !== getenv('WITH_REMOTE_SCRIPTS')) {
        return;
    }

    $opcache = OPcache::i();
    $scripts = $opcache->getRemoteScripts();
    $parser = (new ParserFactory())->createForNewestSupportedVersion();

    $classes = Collection::make();
    foreach ($scripts as $script) {
        $scriptClasses = Collection::empty();
        if (is_file($file = base_path($script))) {
            $stmts = $parser->parse(file_get_contents($file));
            $scriptClasses = $opcache->getPHPParserStmtClasses($stmts);
        }

        if ($scriptClasses->isEmpty()) {
            fwrite(STDERR, sprintf("'%s' does not have any class.", $script).PHP_EOL);
        }

        $classes = $classes->merge($scriptClasses);
    }

    $classes->unique()->each(function ($class) {
        class_exists($class);
        trait_exists($class);
        interface_exists($class);
    });
});

$loads = Collection::empty()
    ->merge(get_declared_classes())
    ->merge(get_declared_interfaces())
    ->merge(get_declared_traits())

    /** 剔除系统类 */
    ->diff($classes)
    ->diff($excludes)

    /** 剔除PhpParser */
    ->filter(function ($class) {
        return 0 !== strripos($class, 'PhpParser\\');
    })

    ->values()->map(function ($class) {
        $reflection = new \ReflectionClass($class);

        $type = null;
        if ($reflection->isInterface()) {
            $type = 'Interface';
            throw_unless(interface_exists($class), sprintf("Interface '%s' does not exist.", $class));
        } elseif ($reflection->isTrait()) {
            $type = 'Trait';
            throw_unless(trait_exists($class), sprintf("Trait '%s' does not exist.", $class));
        } else {
            $type = 'Class';
            throw_unless(class_exists($class), sprintf("Class '%s' does not exist.", $class));
        }

        if (null !== $type) {
            return sprintf("if( !%s_exists('%s') ){ throw new \Exception(\"%s '%s' does not exist.\"); }", strtolower($type), $class, $type, $class);
        }
    })
    /** 排序 */
    ->sort(function ($a, $b) {
        $getSort = function ($class) {
            if (Str::contains($class, 'interface_exists')) {
                return 3;
            } elseif (Str::contains($class, 'trait_exists')) {
                return 2;
            } else {
                return 1;
            }
        };

        return $getSort($a) <=> $getSort($b);
    })->values();

$contents = "<?php \n\nrequire __DIR__.'/vendor/autoload.php';\n\n";
$contents .= $loads->implode(PHP_EOL);

if (file_put_contents($preloadFile, $contents) <= 0) {
    throw new Exception('Unable to write preload file');
}

echo sprintf('Preload file created, class: %s', $loads->count()), PHP_EOL;
