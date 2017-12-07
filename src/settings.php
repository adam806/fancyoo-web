<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/19
 * Time: 下午9:38
 */

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Mysql settings
        'coreDb' => [
            'dbms' => getenv('DB_MS') !== FALSE ? getenv('DB_MS'): 'mysql',
            'host' => getenv('DB_HOST') !== FALSE ? getenv('DB_HOST'): '127.0.0.1',
            'dbName' => getenv('DB_NAME') !== FALSE ? getenv('DB_NAME'): 'zizai',
            'user' => getenv('DB_USER') !== FALSE ? getenv('DB_USER'): 'root',
            'pass' => getenv('DB_PWD') !== FALSE ? getenv('DB_PWD'): 'dev',
        ],

        'ossDb' => [
            'dbms' => getenv('OSS_DB_MS') !== FALSE ? getenv('OSS_DB_MS'): 'mysql',
            'host' => getenv('OSS_DB_HOST') !== FALSE ? getenv('OSS_DB_HOST'): '127.0.0.1',
            'dbName' => getenv('OSS_DB_NAME') !== FALSE ? getenv('OSS_DB_NAME'): 'howdyoss',
            'user' => getenv('OSS_DB_USER') !== FALSE ? getenv('OSS_DB_USER'): 'root',
            'pass' => getenv('OSS_DB_PWD') !== FALSE ? getenv('OSS_DB_PWD'): 'dev',
        ],

        // Redis settings
        'redis' => [
            'host' => getenv('REDIS_HOST') !== FALSE ? getenv('REDIS_HOST'): '127.0.0.1',
            'port' => getenv('REDIS_PORT') !== FALSE ? getenv('REDIS_PORT'): 6379,
            'pwd' => getenv('REDIS_PWD') !== FALSE ? getenv('REDIS_PWD'): '',
        ],

        //RabbitMQ settings
        'rabbitMQ' => [
            'host' => getenv('RABBIT_MQ_HOST') !== FALSE ? getenv('RABBIT_MQ_HOST'): '127.0.0.1',
            'port' => getenv('RABBIT_MQ_PORT') !== FALSE ? getenv('RABBIT_MQ_PORT'): 5672,
            'user' => getenv('RABBIT_MQ_USER') !== FALSE ? getenv('RABBIT_MQ_USER'): 'myuser',
            'pwd' => getenv('RABBIT_MQ_PWD') !== FALSE ? getenv('RABBIT_MQ_PWD'): 'mypass',
        ],

        // Remote apis
        'remoteServers' => [
            'core_server' => getenv('CORE_SERVER') !== FALSE ? getenv('CORE_SERVER'): '127.0.0.1',
            'encounter_server' => getenv('ENCOUNTER_SERVER') !== FALSE ? getenv('ENCOUNTER_SERVER'): '192.168.3.81:8113',
            'messaging_server' => getenv('SVC_MESSAGING_SERVER') !== FALSE ? getenv('SVC_MESSAGING_SERVER') : '127.0.0.1:8095',
        ],
        'remoteApis' => [
            'register_with_wechat' => '/service/v1/noauth/users/registerWithWechatMp',
            'encounter_questions' => '/api/v2/encounter/pre-questions/questions',
            'encounter_answers' => '/api/v2/encounter/pre-questions/answers',
            'encounter_scene' => '/api/v2/encounter/encounters/sceneInfo',
            'add_items' => '/service/v1/noauth/addItems',
            'svc_messaging' => '/api/messages',
        ],

        // Wechat
        'wechat' => [
            'appid' => 'wx1b995d5a13c1d4c4',
            'secret' => 'a3f6dd159120feaa415a6b694a3884e6',
        ],

        // RongCloud API Secret
        'rongCloudSecret' => getenv('RONG_CLOUD_SECRET') !== FALSE ? getenv('RONG_CLOUD_SECRET'): 'secret',
    ],
];
