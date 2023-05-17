<?php

declare(strict_types=1);

use Laminas\ServiceManager\ServiceManager;
use Sync\DatabaseManager;

// Load configuration
$config = require __DIR__ . '/config.php';

$dependencies                       = $config['dependencies'];
$dependencies['services']['config'] = $config;

$dbManager = new DatabaseManager();
$dbManager->init($config['database']);

// Build container
return new ServiceManager($dependencies);
