<?php

$app->get('/test', function () {
    echo 'Hello!!';
});

/***
 * 生成一元夺宝获奖名单
 */
$app->get('/generateGamingWinners',
    '\Fancyoo\Controller\ActivityController:generateGamingWinners');