<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午12:12
 */
namespace Fancyoo\Service\Activity;

use Fancyoo\Common\CacheKey;
use Fancyoo\Service\BaseService;
use Fancyoo\Service\UserService;
use Fancyoo\Service\queue\MessageQueueService;

class ActivityLoginService extends BaseService {
    const
        ACTIVITY_EVENT_WECHAT_LOGIN_FIRST_DAY = 4,
        ACTIVITY_EVENT_WECHAT_LOGIN_SECOND_DAY = 5,
        ACTIVITY_EVENT_WECHAT_LOGIN_THIRD_DAY = 6,
        ACTIVITY_EVENT_WECHAT_NONE = 0;

    const
        GEN_LOGIN_EVENT_CONFIG_ID = 10;

    const
        USER_STATUS_ONLINE = 0,
        USER_STATUS_OFFLINE = 1,
        USER_STATUS_LOGOUT = 2;

    const
        WECHAT_LOGIN_MAX_DAYS = 3,
        TTL_LOGIN_COUNT = 86400;//1 days;

    private $loginConfigIds = array(
        self::ACTIVITY_EVENT_WECHAT_LOGIN_FIRST_DAY,
        self::ACTIVITY_EVENT_WECHAT_LOGIN_SECOND_DAY,
        self::ACTIVITY_EVENT_WECHAT_LOGIN_THIRD_DAY
    );

    private $eventConfigs = array(
        "weLogin" => array(
            self::ACTIVITY_EVENT_WECHAT_LOGIN_FIRST_DAY,
            self::ACTIVITY_EVENT_WECHAT_LOGIN_SECOND_DAY,
            self::ACTIVITY_EVENT_WECHAT_LOGIN_THIRD_DAY
        ),//config id list
    );

    /**
     * All kind of login check, include wechat, general, ect.
     * @param $userId integer
     * @param $status integer, 0:online 1:offline 2:login out
     * @return bool
     */
    public function allLoginCheck($userId, $status) {
        if(empty($userId)) {
            return false;
        }
        //check wechat
        $this->weChatLoginCheck($userId);

        //check general
        $this->generalLoginCheck($userId, $status);

        return true;
    }


    /**
     * General login check, one time per day
     * @param $userId integer
     * @param $status integer
     * @return bool
     */
    public function generalLoginCheck($userId, $status) {
        if(empty($userId)) {
            return false;
        }

        if($status != self::USER_STATUS_ONLINE) {
            return false;
        }

        $redisKey = self::Format(CacheKey::$USER_GEN_LOGIN_COUNT, $userId);
        $lastLogin = $this->redis->get($redisKey);
        $curDay = date("Ymd", time());
        if(!empty($lastLogin)) {
            if($curDay == $lastLogin) {
                //had logined
                return false;
            }
        }

        //put event into mq queue
        $configId = self::GEN_LOGIN_EVENT_CONFIG_ID;
        $arParam = array($userId);
        $bRet = $this->pushEventInQueue($configId, $userId, 0, $arParam);
        if(!$bRet) {
            return false;
        }

        //put into redis cache
        $this->redis->set($redisKey, $curDay);
        $this->redis->expire($redisKey, self::TTL_LOGIN_COUNT);

        return true;
    }

    /**
     * wechat login event check
     * @param $userId integer
     * @return bool
     */
    public function weChatLoginCheck($userId)
    {
        if (empty($userId)) {
            return false;
        }

        $eventTag = "weLogin";
        if (!isset($this->eventConfigs[$eventTag])) {
            return false;
        }
        $weChatLoginConfig = $this->eventConfigs[$eventTag];

        //check wechat user from redis cache
        $redisKey = self::Format(CacheKey::$USER_WECHAT_LOGIN_COUNT, $userId);
        $allCacheInfo = $this->redis->hGetAll($redisKey);
        if (empty($allCacheInfo)) {
            //try get from db?
            $arWebChatInfo = $this->getWeChatUserInfo($userId);
            if(empty($arWebChatInfo)) {
                //no any record in db, do nothing
                return false;
            }
            $allCacheInfo = $arWebChatInfo;
            //need sync into redis
            $this->redis->hMSet($redisKey, $arWebChatInfo);
            $this->redis->expire($redisKey, self::TTL_LOGIN_COUNT);
        }

        //days check
        $joinTimeStamp = strtotime($allCacheInfo['join_at']);
        $joinAtDay = date("Ymd", $joinTimeStamp);
        $curDay = date("Ymd", time());
        $whichDay = $curDay - $joinAtDay + 1;
        if ($whichDay > self::WECHAT_LOGIN_MAX_DAYS) {
            //exceed total days limit
            return false;
        }

        //check event config is defined or not
        if(!isset($weChatLoginConfig[$whichDay-1])) {
            return false;
        }
        $configId = $weChatLoginConfig[$whichDay-1];

        //check login mark logs from redis
        $isExists = isset($allCacheInfo[$whichDay]) ? $allCacheInfo[$whichDay] : false;
        if($isExists) {
            //has done, skip it.
            return false;
        }

        //put event into mq queue
        $arParam = array();
        $inviteUserId = $allCacheInfo['invite_user_id'];
        $bRet = $this->pushEventInQueue($configId, $userId, $inviteUserId, $arParam);

        if(!$bRet) {
            return false;
        }

        //sync into redis
        $bRet = $this->redis->hSet($redisKey, $whichDay, true);
        return $bRet;
    }

    /**
     * Check config id is belong login type
     * @param $configId
     * @return bool
     */
    public function checkIsLoginConfig($configId) {
        if(empty($configId)) {
            false;
        }
        return in_array($configId, $this->loginConfigIds);
    }

    /**
     * @param $configId
     * @param $userId
     * @param $targetUserId
     * @param array $arParams
     * @return bool
     */
    public function pushEventInQueue($configId, $userId, $targetUserId, $arParams = array()) {
        if(empty($configId) || empty($userId)) {
            return false;
        }
        //format data and put into mq
        $mqData = array(
            "config_id" => $configId,
            "user_id" => $userId,
            "target_user_id" => $targetUserId,
            "params" => $arParams
        );
        $this->rabbitMQ->sendMessageToQueue(MessageQueueService::QUEUE_EVENT, json_encode($mqData),true);
        return true;
    }

    /**
     * Get web chat user info from zizai db
     * @param $userId integer
     * @return array
     */
    private function getWeChatUserInfo($userId) {
        $arUserInfo = array();
        if(empty($userId)) {
            return $arUserInfo;
        }
        $sql = "SELECT invite_user_id, join_at FROM wechat_user WHERE user_id = ? LIMIT 1";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($userId));
        $arUserInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $arUserInfo;
    }

    /**
     * Get left seconds of current day end
     * @return integer
     */
    private function getLeftSecondsOfCurDay() {
        $curTime = time();
        $curDay = date("Y-m-d", $curTime);
        $dayEndTime = sprintf("%s 23:59:59", $curDay);
        $timeOfEndDay = strtotime($dayEndTime);
        $leftSeconds = $timeOfEndDay - $curTime;
        return $leftSeconds;
    }
}
