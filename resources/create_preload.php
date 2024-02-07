<?php

use HughCube\Laravel\Knight\OPcache\OPcache;
use Illuminate\Support\Collection;
use PhpParser\ParserFactory;

$excludes = [];

$publicPath = getcwd();

$classes = get_declared_classes();

/** index.php */
call_user_func(function () use ($publicPath) {
    ob_start();
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? null ?: '127.0.0.1';
    require $publicPath.'/index.php';
    ob_clean();
});

/** OPcache Scripts */
call_user_func(function () {

    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    $scripts = OPcache::i()->getRemoteScripts();
    foreach ($scripts as $script) {
        if (!is_file($file = base_path($script['file']))) {
            continue;
        }

        $stmts = $parser->parse(file_get_contents($file));
        foreach ($stmts as $stmt){

        }
    }
});

$loads = Collection::make(get_declared_classes())
    ->diff($classes)
    ->diff($excludes)
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
