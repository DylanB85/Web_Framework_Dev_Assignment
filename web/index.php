<?php

require_once __DIR__.'/../vendor/autoload.php';

$app['debug'] = true;

$app = new App\Application('prod');
$app['http_cache']->run();
