<?php

use Phpmig\Adapter;
use Illuminate\Database\Capsule\Manager as Capsule;

$container = new ArrayObject();

$dbConfig = require_once 'config/config.php';
$dbConfig = $dbConfig['database'];

$container['config'] = $dbConfig;

$capsule = new Capsule();
$capsule->addConnection($container['config']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container['db'] = $capsule;

// replace this with a better Phpmig\Adapter\AdapterInterface
$container['phpmig.adapter'] = new Adapter\File\Flat(__DIR__ . DIRECTORY_SEPARATOR . 'migrations/.migrations.log');

$container['phpmig.migrations_path'] = __DIR__ . DIRECTORY_SEPARATOR . 'migrations';

// You can also provide an array of migration files
// $container['phpmig.migrations'] = array_merge(
//     glob('migrations_1/*.php'),
//     glob('migrations_2/*.php')
// );

return $container;
