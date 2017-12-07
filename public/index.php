<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/19
 * Time: 下午9:35
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/route/api_routes.php';
require __DIR__ . '/../src/route/service_routes.php';
require __DIR__ . '/../src/route/web_routes.php';


// Run app
$app->run();
