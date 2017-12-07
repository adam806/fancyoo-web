<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/19
 * Time: 下午9:45
 */

// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

$app->add(new \Fancyoo\Middleware\LoginMiddleware($app->getContainer()));