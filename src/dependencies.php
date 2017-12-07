<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/19
 * Time: 下午9:40
 */

// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// mysql pdo
$container['coreDb'] = function ($c) {
    $settings = $c->get('settings')['coreDb'];
    $dbms = $settings['dbms'];     //数据库类型
    $host = $settings['host']; //数据库主机名
    $dbName = $settings['dbName'];    //使用的数据库
    $user = $settings['user'];      //数据库连接用户名
    $pass = $settings['pass'];          //对应的密码
    $dsn="$dbms:host=$host;dbname=$dbName";
    $db = new PDO($dsn, $user, $pass);
    return $db;
};

$container['ossDb'] = function ($c) {
    $settings = $c->get('settings')['ossDb'];
    $dbms = $settings['dbms'];     //数据库类型
    $host = $settings['host']; //数据库主机名
    $dbName = $settings['dbName'];    //使用的数据库
    $user = $settings['user'];      //数据库连接用户名
    $pass = $settings['pass'];          //对应的密码
    $dsn="$dbms:host=$host;dbname=$dbName";
    $db = new PDO($dsn, $user, $pass);
    return $db;
};

//redis
$container['redis'] = function ($c) {
    $settings = $c->get('settings')['redis'];
    $host = $settings['host'];
    $port = $settings['port'];
    $pwd = $settings['pwd'];

    $redis = new Redis();
    $redis->pconnect($host, $port, 60);
    if (!empty($pwd)) {
        $redis->auth($pwd);
    }
    return $redis;
};

//rabbit mq
$container['rabbitMQ'] = function ($c) {
    $settings = $c->get('settings')['rabbitMQ'];
    $rabbitMQ = new \Fancyoo\Service\queue\MessageQueueService($settings);
    return $rabbitMQ;
};

$container['errorHandler'] = function ($c) {
    return new \Fancyoo\Exception\CustomHandler();
};

//service
$container['userService'] = function ($c) {
    return new \Fancyoo\Service\UserService($c);
};
$container['remoteApiService'] = function ($c) {
    return new \Fancyoo\Service\RemoteApiService($c);
};
$container['activity'] = function ($c) {
    return new \Fancyoo\Service\Activity\Activity($c);
};
$container['fragmentActivity'] = function ($c) {
    return new \Fancyoo\Service\Activity\FragmentActivity($c);
};
$container['gamingActivity'] = function ($c) {
    return new \Fancyoo\Service\Activity\GamingActivity($c);
};
$container['activityEventService'] = function ($c) {
    return new \Fancyoo\Service\Activity\ActivityEventService($c);
};
$container['activityLoginService'] = function ($c) {
    return new \Fancyoo\Service\Activity\ActivityLoginService($c);
};