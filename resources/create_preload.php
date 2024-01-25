<?php

use Illuminate\Support\Collection;


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
