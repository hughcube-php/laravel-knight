<?php

use HughCube\Laravel\Knight\OPcache\OPcache;
use Illuminate\Support\Collection;
use PhpParser\ParserFactory;

$excludes = [];

$publicPath = getcwd();

$classes = get_declared_classes();

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
        if (is_file($file = base_path($script))) {
            $stmts = $parser->parse(file_get_contents($file));
            $classes = $classes->merge($opcache->getPHPParserStmtClasses($stmts));
        }
    }

    $classes->each(function ($class) {
        class_exists($class);
    });
});

$loads = Collection::make(get_declared_classes())
    ->diff($classes)
    ->diff($excludes)
    ->filter(function ($class) {
        return 0 !== strripos($class, "PhpParser\\");
    })
    ->values()
    ->map(function ($class) {
        return sprintf("class_exists('%s');", $class);
    });

$contents = "<?php \n\nrequire __DIR__.'/vendor/autoload.php';\n\n";
$contents .= $loads->implode(PHP_EOL);

if (file_put_contents($publicPath.'/../preload.php', $contents) <= 0) {
    throw new Exception('Unable to write preload file');
}

echo sprintf('Preload file created, class: %s', $loads->count()), PHP_EOL;
