<?php

use Illuminate\Support\Collection;

$classes = get_declared_classes();

$publicPath = getcwd();

/** web server */
call_user_func(function () use ($publicPath) {
    ob_start();
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? null ?: '127.0.0.1';
    require $publicPath.'/index.php';
    ob_clean();
});

/** composer classmap */
call_user_func(function () use ($publicPath) {
    $classmap = require $publicPath.'/../vendor/composer/autoload_classmap.php';
    foreach ($classmap as $class => $file) {
        class_exists($class);
    }
});

$loads = Collection::make(get_declared_classes())
    ->diff($classes)
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
