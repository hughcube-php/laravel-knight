<?php

use Illuminate\Support\Collection;

$classes = get_declared_classes();

$publicPath = getcwd();

call_user_func(function () use ($publicPath) {
    ob_start();
    require $publicPath.'/index.php';
    ob_clean();
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