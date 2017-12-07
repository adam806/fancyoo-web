<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/20
 * Time: 下午8:58
 */

$app->get('/', function ($request, $response, $args) {
    $request = new Slim\Http\MobileRequest($request);
    if ($request->isMobile()) {
        return $this->renderer->render($response, 'index/mobile.phtml', array("name" => "mobile"));
    } else {
        return $this->renderer->render($response, 'index/pc.phtml', array("name" => "desktop"));
    }
});

$app->get('/privacy', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/privacy.phtml');
});
$app->get('/agreement', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/agreement.phtml');
});

$app->get('/recharge', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/recharge.phtml');
});

$app->get('/guardian', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/guardian.phtml');
});
$app->get('/help', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/help.phtml');
});

$app->get('/join', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/join.phtml');
});

$app->get('/culture', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/culture.phtml');
});
$app->get('/connect', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/connect.phtml');
});
$app->get('/register', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/register.phtml');
});
$app->get('/getPassword', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/getPassword.phtml');
});

$app->get('/m/agreement/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/agreement_mobile.phtml');
});

$app->get('/m/privacy/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/privacy_mobile.phtml');
});
$app->get('/scan', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index/scan.phtml');
});

//下方三条广告页用
$app->get('/city', function ($request, $response, $args) {
    $request = new Slim\Http\MobileRequest($request);
    if ($request->isMobile()) {
        return $this->renderer->render($response, 'publicity/mcity.phtml', array("name" => "mobile"));
    } else {
        return $this->renderer->render($response, 'publicity/pccity.phtml', array("name" => "desktop"));
    }

});
$app->get('/home', function ($request, $response, $args) {
    $request = new Slim\Http\MobileRequest($request);
    if ($request->isMobile()) {
        return $this->renderer->render($response, 'publicity/mhome.phtml', array("name" => "mobile"));
    } else {
        return $this->renderer->render($response, 'publicity/pchome.phtml', array("name" => "desktop"));
    }
});

$app->get('/meet', function ($request, $response, $args) {
    $request = new Slim\Http\MobileRequest($request);
    if ($request->isMobile()) {
        return $this->renderer->render($response, 'publicity/mmeet.phtml', array("name" => "mobile"));
    } else {
        return $this->renderer->render($response, 'publicity/pcmeet.phtml', array("name" => "desktop"));
    }
});


$app->get('/encounter', function ($request, $response, $args) {
    return $this->renderer->render($response, 'share/encounter.phtml');
});

$app->get('/wechat/redirect', function ($request, $response, $args) {
    $params = $request->getQueryParams();
    $code = isset($params['code']) ? $params['code'] : '';
    $toUserid = isset($params['toUserId']) ? $params['toUserId'] : '';
    $sceneId = isset($params['sceneId']) ? $params['sceneId'] : '';
    $photo = isset($params['photo']) ? $params['photo'] : '';
    $toUseridArray = array('targetId' => $toUserid, 'sceneId' => $sceneId, 'photoUrl' => $photo);
    $remoteApiService = $this->get("remoteApiService");
    $userInfo = $remoteApiService->getWechatAccessToken($code);

    if (!empty($userInfo) && isset($userInfo["openid"])) {
        $userService = $this->get("userService");
        $privilege = !empty($userInfo["privilege"]) ? json_encode($userInfo["privilege"]) : "";
        $userService->authorizeWithWechat($userInfo["openid"], $userInfo["unionid"], $userInfo["nickname"], $userInfo["sex"], $userInfo["province"], $userInfo["city"], $userInfo["country"], $userInfo["headimgurl"], $privilege, $userInfo["access_token"], $userInfo["expires_in"], $userInfo["refresh_token"], $userInfo["scope"], $toUserid);
    }
    return $this->renderer->render($response, 'share/encounter_wx.phtml', array("user" => $userInfo, "toTargetid" => $toUseridArray));
});

$app->group('/activities', function () {
    $this->get('/{id}', function ($request, $response, $args) {
        $activity_id = isset($args['id']) ? $args['id'] : '';
        $params = $request->getQueryParams();
        $user_unique_id = isset($params['user_id']) ? $params['user_id'] : "";

        $token = isset($params['token']) ? $params['token'] : "";
        $channel = isset($params['channel']) ? $params['channel'] : "";
        $version = isset($params['version']) ? $params['version'] : "";
        $mei = isset($params['mei']) ? $params['mei'] : "";

        $user_id = "";
        if ($user_unique_id > 0 && !empty($token)) {
            //检查用户是否存在，并将unique_id转成user_id
            $userService = $this->get("userService");
            $user = $userService->getUserInfo($user_unique_id);
            $user_id = $user["id"];
            //检查用户token
            $isValid = $userService->checkUserAccessToken($user_id, $token);
            if (!$isValid) {
                $errorMessage = '您的授权已过期';
                return $this->renderer->render($response, 'share/autherror.phtml', array("autherror" => $errorMessage));
            }
        }
        $fragmentActivity = $this->get("fragmentActivity");
        $activity = $fragmentActivity->getModel($activity_id);
        $activity->user_fragment_amount = $activity->getUserFragmentsAmount($user_id);
        if ($activity->type == \Fancyoo\Service\Activity\ActivityType::$FRAGMENT_RANK) { //旧排行榜,id=1
            $activity->getActivityRanks();
            $template = 'share/encounter_ranking.phtml';
        } elseif ($activity->type == \Fancyoo\Service\Activity\ActivityType::$FRAGMENT_EXCHANGE) { //id=2
            $activity->getExchangeAwards($user_id);
            $template = 'share/encounter_debris.phtml';
        } elseif ($activity->type == \Fancyoo\Service\Activity\ActivityType::$FRAGMENT_GAMING) { //id=1
            $activity->getExchangeAwards($user_id);
            $template = 'share/activity_treasure.phtml';
        }

        return $this->renderer->render($response, $template, array("activity" => $activity, "user_id" => $user_unique_id, "token" => $token));
    });
    $this->get('/share/wechat', function ($request, $response, $args) {
        return $this->renderer->render($response, 'share/activity_share.phtml');
    });
    $this->get('/share/treasure', function ($request, $response, $args) {
        return $this->renderer->render($response, 'share/activity_treasure.phtml');
    });
});
$app->get('/video', function ($request, $response, $args) {
    return $this->renderer->render($response, 'share/encounter_vr.phtml');
});