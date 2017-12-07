<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/12/7
 * Time: 下午4:37
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/autoload.php';

array_shift($argv); // Discard the filename
$pathInfo = array_shift($argv);

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';

$app = new \Slim\App($settings);
$container = $app->getContainer();
$container['environment'] = \Slim\Http\Environment::mock([
    'REQUEST_URI' => '/' . $pathInfo
]);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/route/cli_routes.php';

// Run app
$app->run();