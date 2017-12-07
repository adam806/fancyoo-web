<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/11/13
 * Time: 下午3:17
 */

$app->group('/api', function () {
    /**
     * 融云回调，更新用户在线状态
     */
    $this->post('/users/online_status',
        '\Fancyoo\Controller\UserController:updateUserOnlineStatus'
    );

    /**
     * 获取用户个人信息(只包含基本信息字段)
     */
    $this->get('/users/{id}',
        '\Fancyoo\Controller\UserController:getUserInfo'
    );

    /**
     * 记录微信授权信息
     */
    $this->post('/users/wxauth',
        '\Fancyoo\Controller\UserController:authorizeWithWechat'
    );

    /**
     * 获取邂逅问题
     */
    $this->get('/encounters',
        '\Fancyoo\Controller\EncounterController:getEncounterQuestions'
    );

    /**
     * 获取邂逅场景
     */
    $this->get('/encounters/sceneInfo',
        '\Fancyoo\Controller\EncounterController:getEncounterScene'
    );

    /**
     * 提交邂逅问题答案(先检查微信授权信息，然后注册一个app账号，用这个账号给邂逅提交邂逅答案)
     */
    $this->post('/encounters',
        '\Fancyoo\Controller\EncounterController:submitEncounterAnswers');

    $this->group('/activities', function () {
        global $container;

        /***
         * 获取活动列表
         */
        $this->get('',
            '\Fancyoo\Controller\ActivityController:getActivities');

        /***
         * 获取活动详情
         */
        $this->get('/{id}',
            '\Fancyoo\Controller\ActivityController:getActivityInfo');

        /***
         * 获取活动排名
         */
        $this->get('/{id}/ranks',
            '\Fancyoo\Controller\ActivityController:getActivityRanks');

        /***
         * 生成活动排名
         */
        $this->get('/ranks/generate',
            '\Fancyoo\Controller\ActivityController:generateActivityRanks');

        /***
         * 获取活动兑换奖励
         */
        $this->get('/{id}/exchanges',
            '\Fancyoo\Controller\ActivityController:getActivityExchanges');

        /***
         * 兑换奖励
         */
        $this->post('/{id}/exchanges',
            '\Fancyoo\Controller\ActivityController:exchange');

        /***
         * 参与集碎片夺宝
         */
        $this->post('/{id}/participateGaming',
            '\Fancyoo\Controller\ActivityController:participateGaming');

        /***
         * 获取集碎片奖品列表
         */
        $this->get('/{id}/getGamingList',
            '\Fancyoo\Controller\ActivityController:getGamingList');

        /***
         * 获取历史获奖名单
         */
        $this->get('/{id}/getHistoryGamingWinners',
            '\Fancyoo\Controller\ActivityController:getHistoryGamingWinners');

        /***
         * 生成一元夺宝获奖名单
         */
        $this->get('/{id}/generateGamingWinners',
            '\Fancyoo\Controller\ActivityController:generateGamingWinners');

        /***
         * 碎片领取
         */
        $this->post('/{id}/drawFragments','\Fancyoo\Controller\ActivityController:drawFragments');

        /***
         * 碎片状态查询
         */
        $this->get('/{id}/drawCheck', '\Fancyoo\Controller\ActivityController:drawCheck');

    });
});
