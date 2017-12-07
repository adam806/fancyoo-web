<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午12:12
 *
 * Logic flow:
 * 1. get related event data by event id
 * 2. get related activity config datas by event config id
 * 3. get activity data and check available
 * 4. check or give fragments to user, need check amount limit
 */


namespace Fancyoo\Service\Activity;

use Fancyoo\Exception\CustomException;
use Fancyoo\Exception\ErrorCode;
use Fancyoo\Service\BaseService;
use Fancyoo\Service\UserService;
use Fancyoo\Common\CacheKey;

class ActivityEventService extends BaseService {
    //define event types
    const
        EVENT_TYPE_USER = 1,
        EVENT_TYPE_SYSTEM = 2,
        EVENT_TYPE_ROBOT = 3;

    //activity status
    const
        ACTIVITY_STATUS_OPEN = 1,
        ACTIVITY_STATUS_DELETED = 2,
        ACTIVITY_STATUS_CLOSED = 0;

    //fragment draw status
    const
        FRAGMENT_DRAW_NONE = 0,
        FRAGMENT_DRAW_DONE = 1,
        FRAGMENT_DRAW_UNKNOWN = -1;

    //fragment award target user type
    const
        FRAGMENT_AWARD_SOURCE_USER = 1, //event trigger user side
        FRAGMENT_AEWRD_TARGET_USER = 2, //event target user side
        FRAGMENT_AEWARD_BOTH_USER = 3; //event both users side

    private $arSupportAwardTargets = array(
        self::FRAGMENT_AWARD_SOURCE_USER,
        self::FRAGMENT_AEWRD_TARGET_USER,
        self::FRAGMENT_AEWARD_BOTH_USER
    );

    const
        EVENT_CONFIG_TYPE_ENCOUNTER = 7, //for encounter
        EVENT_CONFIG_TYPE_CHEST_SPEEDUP = 8, //for user chest speedup
        EVENT_CONFIG_TYPE_SHARE_ENCOUNTER = 9, //for encounter share
        EVENT_CONFIG_TYPE_NONE = 0;

    const
        ACTIVITY_TYPE_ENCOUNTER = "encounter";

    private $arUniqueUserIdEvents = array(
        self::EVENT_CONFIG_TYPE_ENCOUNTER,
        self::EVENT_CONFIG_TYPE_CHEST_SPEEDUP,
    );

    private $fromUserId;
    private $targetUserId;

    /**
     * Check related fragment is drawed or not
     * if drawed, return true or false
     * @param $userId
     * @param $activityId
     * @param $eventId
     * @return bool
     */
    public function drawCheck($userId, $activityId, $eventId) {
        $drawStatus = $this->getFragmentDrawStatus($userId, $activityId, $eventId);
        if($drawStatus == false) {
            return true;
        }
        return ($drawStatus == self::FRAGMENT_DRAW_DONE) ? true : false;
    }

    /**
     * User draw fragments of single activity and event id
     * @param $userId
     * @param $activityId
     * @param $eventId
     * @return bool
     */
    public function drawFragments($userId, $activityId, $eventId) {
        $drawStatus = $this->getFragmentDrawStatus($userId, $activityId, $eventId);
        if($drawStatus == false || $drawStatus == self::FRAGMENT_DRAW_NONE) {
            return false;
        }
        //begin draw fragment
        $sql = "UPDATE user_fragment_details SET status = ? WHERE user_id = ? AND activity_id = ? AND event_id = ? AND status = ?";
        $stmt = $this->ossDb->prepare($sql);
        $effectRows = $stmt->execute(array(self::FRAGMENT_DRAW_DONE, $userId, $activityId, $eventId, self::FRAGMENT_DRAW_NONE));

        if($effectRows <= 0) {
            return false;
        }

        //sync into cache
        $this->setFragmentDrawStatus($userId, $activityId, $eventId, self::FRAGMENT_DRAW_NONE);

        return true;
    }

    /**
     * set activity fragment draw status
     * @param $userId
     * @param $activityId
     * @param $eventId
     * @param $status
     * @return bool
     */
    public function setFragmentDrawStatus($userId, $activityId, $eventId, $status) {
        if(empty($userId) || empty($activityId) || empty($eventId)) {
            return false;
        }

        $activityEndTime = 0;
        $activityIsAvailable = $this->checkActivityAvailable($activityId, $activityEndTime);
        if(!$activityIsAvailable) {
            return false;
        }

        $redisKey = self::Format(CacheKey::$USER_ACTIVITY_FRAGMENT_DRAW_STATUS, $userId, $activityId);
        $curStatus = $this->getFragmentDrawStatus($userId, $activityId, $eventId);
        if($curStatus != false && !empty($curStatus)) {
            if($curStatus == self::FRAGMENT_DRAW_DONE) {
                //already done
                return false;
            }
            if($curStatus == $status) {
                //same status
                return false;
            }

            //set status;
            $this->hSet($redisKey. $eventId. $status);
            return true;
        }

        //calculate TTL for current activity
        $now = time();
        $ttl = $activityEndTime - $now;

        //set into redis cache
        $this->redis->hSet($redisKey, $eventId, $status);
        $this->redis->expire($redisKey, $ttl);

        return true;
    }

    /**
     * Get fragment draw status of single activity and event id
     * @param $userId
     * @param $activityId
     * @param $eventId
     * @return bool
     */
    public function getFragmentDrawStatus($userId, $activityId, $eventId) {
        if(empty($userId) || empty($activityId) || empty($eventId)) {
            return false;
        }
        $redisKey = self::Format(CacheKey::$USER_ACTIVITY_FRAGMENT_DRAW_STATUS, $userId, $activityId);
        return $this->redis->hGet($redisKey, $eventId);
    }

    /**
     * check assigned activity is available or not
     * @param $activityId
     * @param $activityEndTime
     * @return bool
     */
    public function checkActivityAvailable($activityId, &$activityEndTime) {
        if(empty($activityId)) {
            return false;
        }
        //get activity info
        $activityObj = (new Activity())->getModel($activityId);

        $startTime = $activityObj->start_time;
        $endTime = $activityObj->end_time;

        $now = time();
        $startTimeInt = strtotime($startTime);
        $endTimeInt = strtotime($endTime);
        $activityEndTime = $endTimeInt;

        return ($now >= $startTimeInt && $now <= $endTimeInt) ? true : false;
    }

    /**
     * Check event status and generate fragment for user
     * @param $userId integer
     * @param $eventId integer
     * @return bool
     */
    public function check($userId, $eventId, $extParams = array()) {
        if(empty($userId) || empty($eventId)) {
            return false;
        }
        //get and check event
        $arEvent = $this->getEvent($eventId);
        if(empty($arEvent)) {
            //no event record
            return false;
        }

        $eventConfigId = $arEvent['config_id'];
        $fromUserId = $arEvent['user_id'];
        $targetUserId = $arEvent['target_user_id'];

        //the user id from config type 7, 8 is unique user id
        //this need convert to user id
        if(in_array($eventConfigId, $this->arUniqueUserIdEvents)) {
            //need convert unique user id
            $fromUserInfo = (new UserService())->getUserInfo($fromUserId);
            $targetUserInfo = (new UserService())->getUserInfo($targetUserId);
            $fromUserId = isset($fromUserInfo['id']) ? $fromUserInfo['id'] : null;
            $targetUserId = isset($targetUserInfo['id']) ? $targetUserInfo['id'] : null;
        }

        if(empty($fromUserId)) {
            return false;
        }

        //set property
        $this->fromUserId = $fromUserId;
        $this->targetUserId = $targetUserId;

        //get related activity event configs by event config id
        $arActivityIds = array();
        $arActivityEventConfigs = $this->getActivityEventConfig($eventConfigId, $arActivityIds);
        if(empty($arActivityEventConfigs) || empty($arActivityIds)) {
            //no activity event config
            return false;
        }

        //get related activities by activity ids
        $arActivities = $this->getActivities($arActivityIds);
        if(empty($arActivities)) {
            //no available activities
            return false;
        }

        //if has extra config data
        //need check the extra config match the ext params or not
        if(!empty($extParams)) {
            switch ($eventConfigId) {
                case self::EVENT_CONFIG_TYPE_ENCOUNTER:
                    $arAvailableActivityIds = $this->checkForFinishEncounter($extParams, $arActivities);
                    break;

                case self::EVENT_CONFIG_TYPE_SHARE_ENCOUNTER:
                    $arAvailableActivityIds = $this->checkForShareEncounter($extParams, $arActivities);
                    break;
                default:
                    $arAvailableActivityIds = array_keys($arActivities);
            }
        }else{
            $arAvailableActivityIds = array_keys($arActivities);
        }

        if(empty($arAvailableActivityIds)) {
            return false;
        }

        //get fragments configs of related activities
        $arFragmentConfigs = $this->getFragmentConfigs($arAvailableActivityIds);
        if(empty($arFragmentConfigs)) {
            //no available fragment configs
            return false;
        }

        //resort activity event configs by award target
        $arActivityEventConfigByWardTarget = $this->resortActivityEventConfigByAwardTarget($arActivityEventConfigs, $arAvailableActivityIds);
        if(empty($arActivityEventConfigByWardTarget)) {
            //no record by award target
            return false;
        }

        //process fragment and props by award
        $arUserIds = array();
        foreach ($arActivityEventConfigByWardTarget as $awardTarget => $arBatchActivityEventConfigs) {
            switch ($awardTarget) {
                case self::FRAGMENT_AWARD_SOURCE_USER:
                    $arUserIds = array($this->fromUserId);
                    break;
                case self::FRAGMENT_AEWRD_TARGET_USER:
                    if($this->targetUserId > 0) {
                        $arUserIds = array($this->targetUserId);
                    }
                    break;
                case self::FRAGMENT_AEWARD_BOTH_USER:
                    $arUserIds = array($this->fromUserId);
                    if($this->targetUserId > 0) {
                        array_push($arUserIds, $this->targetUserId);
                    }
                    break;
                default:
                    break;
            }
            if(empty($arUserIds)) {
                continue;
            }
            //process single award target datas
            $arUserNeedIncrFragments = $this->processUserFragmentAwards($arUserIds, $arAvailableActivityIds, $arBatchActivityEventConfigs, $arFragmentConfigs);
            if(empty($arUserNeedIncrFragments)) {
                //no any user fragments for add
                return false;
            }

            //begin save related details and give user props
            //$arUserNeedIncrFragments => $map[$userId][$activityId][$fragmentId] = array(propId, amount, actual_amount)
            foreach ($arUserNeedIncrFragments as $userId => $arActivityFragments) {
                foreach($arActivityFragments as $activityId => $arFragments) {
                    foreach ($arFragments as $fragmentId => $arPropAmount) {
                        list($propId, $amount, $actualAmount) = $arPropAmount;
                        //begin add related records
                        //add fragment detail
                        $this->addUserFragmentDetail($userId, $eventId, $activityId, $fragmentId, $amount, $actualAmount);

                        //add fragment
                        $this->addUserFragments($userId, $activityId, $fragmentId, $amount);

                        //add props
                        $this->addUserProps($userId, $propId, $amount);
                    }
                    //send draw tips into chat room for current user
                    $remoteApiService = $this->ci->get("remoteApiService");
                    $remoteApiService->sendActivityFragmentDrawMessage($userId, $activityId, $eventId);
                }
            }
        }

        return true;
    }


    /**
     * Check ext params and ext config in activity is match or not
     * @param $extParam
     * @param $arActivities
     * @return array
     */
    private function checkForShareEncounter($extParam, $arActivities) {
        $arAvailableActivityIds = array();
        $dataId = $extParam[0];
        //not check now??
        $arAvailableActivityIds = array_keys($arActivities);
        return $arAvailableActivityIds;
    }

    /**
     * Check ext params and ext config in activity is match or not
     * @param $extParam array
     * @param $arActivities array
     * @return array
     */
    private function checkForFinishEncounter($extParam, $arActivities) {
        $arAvailableActivityIds = array();
        $dataId = $extParam[0];
        //check one by one
        foreach ($arActivities as $activityId => $arExtConfigs) {
            foreach ($arExtConfigs as $arSingleExtConfig) {
                $type = isset($arSingleExtConfig['type']) ? $arSingleExtConfig['type'] : '';
                $id = isset($arSingleExtConfig['id']) ? $arSingleExtConfig['id'] : 0;
                if(empty($type) || empty($id)) {
                    continue;
                }
                if($type != self::ACTIVITY_TYPE_ENCOUNTER || $id != $dataId) {
                    //type or data id not matched
                    continue;
                }
                if(!in_array($activityId, $arAvailableActivityIds)) {
                    array_push($arAvailableActivityIds, $activityId);
                }
            }
        }
        return $arAvailableActivityIds;
    }

    /**
     * Step 9.
     * Give related props to user
     * @param $userId integer
     * @param $propId integer
     * @param $amount integer
     * @return bool
     */
    private function addUserProps($userId, $propId, $amount) {
        if(empty($userId) || empty($propId) || $amount <= 0) {
            return false;
        }
        $sql = "INSERT INTO user_game_props (user_id, game_props_id, amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = amount + ?";
        $stmt = $this->coreDb->prepare($sql);
        $ret = $stmt->execute(array($userId, $propId, $amount, $amount));
        return $ret;
    }

    /**
     * Step 8.
     * give related fragment to user
     * @param $userId integer
     * @param $activityId integer
     * @param $fragmentId integer
     * @param $fragmentAmount integer
     * @return bool
     */
    private function addUserFragments($userId, $activityId, $fragmentId, $fragmentAmount) {
        if(empty($userId) || empty($activityId)) {
            return false;
        }
        if(empty($fragmentId) || $fragmentAmount <= 0) {
            return false;
        }
        $sql = "INSERT INTO user_fragments (user_id, activity_id, fragment_id, fragment_amount) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE fragment_amount = fragment_amount + ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($userId, $activityId, $fragmentId, $fragmentAmount, $fragmentAmount));
        return true;
    }


    /**
     * @param $userId integer
     * @param $eventId integer
     * @param $activityId integer
     * @param $fragmentId integer
     * @param $amount integer
     * @param $actualAmount integer
     * @return bool
     */
    private function addUserFragmentDetail($userId, $eventId, $activityId, $fragmentId, $amount, $actualAmount) {
        if(empty($userId) || empty($eventId)) {
            return false;
        }
        if(empty($activityId) || ($amount <= 0 && $actualAmount <= 0)) {
            return false;
        }
        $sql = "INSERT INTO user_fragment_details (user_id, event_id, activity_id, fragment_id, amount, actual_amount) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->ossDb->prepare($sql);
        $ret = $stmt->execute(array($userId, $eventId, $activityId, $fragmentId, $amount, $actualAmount));
        return true;
    }

    /**
     * Step 7.
     * Process batch user fragment awards for single award target
     * all fragment amount limit base on one day
     * @param $arUserIds array
     * @param $arActivityIds array
     * @param $arBatchActivityEventConfigs array
     * @param $arFragmentConfigs array
     * @return array, $map[$userId][$activityId][$fragmentId] = array(propId, amount, actual_amount)
     */
    private function processUserFragmentAwards($arUserIds, $arActivityIds, $arBatchActivityEventConfigs, $arFragmentConfigs) {

        //get users had got fragments of assigned activity ids in current day
        //format:$arr[$userId][$activityId][$fragmentId] = totalAmount
        list($userFragmentById, $userFragmentByActivity) = $this->getUserFragments($arUserIds, $arActivityIds);
        if($userFragmentById == NULL) {
            $userFragmentById = array();
        }
        if($userFragmentByActivity == NULL) {
            $userFragmentByActivity = array();
        }
        //$arFragmentConfigs[$activityId][$fragmentId] = array($propId, $max_amount);

        $sumByActivity = array();
        $sumByFragment = array();
        $needIncrFragment = array();//map[$userId][$activityId][$fragmentId] = array(propId, amount, actualAmount),

        //check or update need granted fragment data
        //step 1. by user id
        foreach ($arUserIds as $userId) {
            //step 2. by activity id
            foreach ($arActivityIds as $curActivityId) {
                //$sumByActivity[$userId][$curActivityId] = 0;
                //step 3. by activity event configs
                foreach($arBatchActivityEventConfigs as $activityEventConfig) {
                    $activityId = $activityEventConfig['activity_id'];
                    $fragmentId = $activityEventConfig['fragment_id'];
                    $fragmentAmount = $activityEventConfig['fragment_amount'];//total amount fragments per time
                    $maxAmount = $activityEventConfig['max_amount'];//max amount for current fragments per day

                    if($activityId != $curActivityId) {
                        continue;
                    }

                    if(!isset($arFragmentConfigs[$curActivityId][$fragmentId])) {
                        continue;
                    }

                    //get user had got fragment amount of current activity and fragment id
                    $userHadGotAmountByActivity = 0;
                    $userHadGotAmountByFragment = 0;
                    $needIncrFragment[$userId][$activityId][$fragmentId] = array(0,0,0);
                    if(isset($userFragmentById[$userId][$fragmentId])) {
                        $userHadGotAmountByFragment = $userFragmentById[$userId][$fragmentId];
                    }

                    if(isset($userFragmentByActivity[$userId][$curActivityId][$fragmentId])) {
                        $userHadGotAmountByActivity = $userFragmentByActivity[$userId][$curActivityId][$fragmentId];
                    }

                    //get max amount limit of single activity and activity id
                    list($propsId, $maxAmountForActivity) = $arFragmentConfigs[$curActivityId][$fragmentId];

                    //check by fragment
                    if(!isset($sumByFragment[$userId][$fragmentId])) {
                        $sumByFragment[$userId][$fragmentId] = $userHadGotAmountByFragment;
                    }

                    //check by activity
                    if(!isset($sumByActivity[$userId][$curActivityId][$fragmentId])) {
                        $sumByActivity[$userId][$curActivityId][$fragmentId] = $userHadGotAmountByActivity;
                    }

                    //summation amount for event, activity per day
                    //max amount per activity storage in $arFragmentConfigs

                    //check by fragment id
                    $tmpFragmentAmountById = $sumByFragment[$userId][$fragmentId] + $fragmentAmount;
                    $tmpFragmentAmountByActivity = $sumByActivity[$userId][$activityId][$fragmentId] + $fragmentAmount;

                    if($tmpFragmentAmountById > $maxAmount) {
                        //up to fragment amount limit
                        $incrAmountById = $maxAmount - $sumByFragment[$userId][$fragmentId];
                        $needIncrFragment[$userId][$activityId][$fragmentId][0] = $propsId;
                        $needIncrFragment[$userId][$activityId][$fragmentId][1] += $incrAmountById;
                        $needIncrFragment[$userId][$activityId][$fragmentId][2] += $fragmentAmount;
                        continue;
                    }else{
                        //check by activity id
                        if($maxAmountForActivity != -1 && $tmpFragmentAmountByActivity > $maxAmountForActivity) {
                            $incrAmountByActivity = $maxAmountForActivity - $sumByActivity[$userId][$activityId][$fragmentId];
                            $needIncrFragment[$userId][$activityId][$fragmentId][0] = $propsId;
                            $needIncrFragment[$userId][$activityId][$fragmentId][1] += $incrAmountByActivity;
                            $needIncrFragment[$userId][$activityId][$fragmentId][2] += $fragmentAmount;
                            continue;
                        }else{
                            $sumByFragment[$userId][$fragmentId] = $tmpFragmentAmountById;
                            $sumByActivity[$userId][$activityId][$fragmentId] = $tmpFragmentAmountByActivity;
                            $needIncrFragment[$userId][$activityId][$fragmentId][0] = $propsId;
                            $needIncrFragment[$userId][$activityId][$fragmentId][1] += $fragmentAmount;
                            $needIncrFragment[$userId][$activityId][$fragmentId][2] += $fragmentAmount;
                        }
                    }
                }
            }
        }
        return $needIncrFragment;
    }


    /**
     * Step 6.
     * Get user all fragments by activity ids, only get datas of current day
     * return two array, one sorted by fragment id, another sorted by activity id
     * @param $userIds array
     * @param $activityIds array
     * @return array(a,b), a: $map[$userId][$activityId] = $amount b: $map[$userId][$fragmentId] = $amount
     */
    private function getUserFragments($userIds, $activityIds) {
        if(empty($userIds) || empty($activityIds)) {
            return array(NULL, NULL);
        }
        $now = time();
        $beginTime = date("Y-m-d", $now) . " 00:00:00";
        $endTime = date("Y-m-d", $now) . " 23:59:59";
        $activityIdStr = implode(",", $activityIds);
        $userIdStr = implode(",", $userIds);
        $sql = "SELECT * FROM user_fragment_details WHERE user_id IN (?) AND activity_id IN (?) AND created_at >= ? AND created_at <= ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($userIdStr, $activityIdStr, $beginTime, $endTime));
        $arFragments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(empty($arFragments)) {
            return array(NULL, NULL);
        }
        //resort data by user id, activity id and fragment id.
        $arFragmentsById = array();
        $arFragmentsByActivity = array();
        foreach ($arFragments as $arSingleFragment) {
            $userId = $arSingleFragment['user_id'];
            $fragmentId = $arSingleFragment['fragment_id'];
            $amount = $arSingleFragment['fragment_amount'];
            $activityId = $arSingleFragment['activity_id'];
            //sum by fragment and activity
            if(!isset($arFragmentsById[$userId][$fragmentId])) {
                $arFragmentsById[$userId][$fragmentId] = $amount;
            }else{
                $arFragmentsById[$userId][$fragmentId] += $amount;
            }
            if(!isset($arFragmentsByActivity[$userId][$activityId][$fragmentId])) {
                $arFragmentsByActivity[$userId][$activityId][$fragmentId] = $amount;
            }else{
                $arFragmentsByActivity[$userId][$activityId][$fragmentId] += $amount;
            }
        }
        return $arFragmentsById;
    }

    /**
     * Step 5.
     * Resort activity event config by award target
     * @param $arActivityEventConfigs, $map[$eventConfigId][$activityId] = $arSingleConfig
     * @param $arAvailableActivityIds
     * @return array
     */
    private function resortActivityEventConfigByAwardTarget($arActivityEventConfigs, $arAvailableActivityIds) {
        $arAwardTargetConfigs = array();
        if(empty($arActivityEventConfigs) || empty($arAvailableActivityIds)) {
            return $arAwardTargetConfigs;
        }
        foreach ($arActivityEventConfigs as $eventConfigId => $arActivityConfig) {
            foreach ($arActivityConfig as $activityId => $arSingleConfig) {
                $activityId = $arSingleConfig['activity_id'];
                $awardTarget = $arSingleConfig['award_target'];
                if(!in_array($activityId, $arAvailableActivityIds)) {
                    continue;
                }
                if(!in_array($awardTarget, $this->arSupportAwardTargets)) {
                    continue;
                }
                $arAwardTargetConfigs[$awardTarget][] = $arSingleConfig;
            }
        }
        return $arAwardTargetConfigs;
    }

    /**
     * Step 4.
     * Get all fragments config by activity ids
     * max_amount is max fragments for current activity
     * @param $activityIds array
     * @return array, $map[activityId][fragmentId] = array(propId, maxAmount)
     */
    private function getFragmentConfigs($activityIds) {
        $arFragments = array();
        if(empty($activityIds)) {
            return $arFragments;
        }
        $activityIdStr = implode(",", $activityIds);
        $sql = "SELECT id as fragment_id, activity_id, game_props_id, max_amount FROM activity_fragment_configs WHERE activity_id IN (?)";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activityIdStr));
        $arFragments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(empty($arFragments)) {
            return $arFragments;
        }
        //resort by activity and fragment id
        $arFragmentSorted = array();
        foreach ($arFragments as $arSingleFragment) {
            $activityId = $arSingleFragment['activity_id'];
            $fragmentId = $arSingleFragment['fragment_id'];
            $propId = $arSingleFragment['game_props_id'];
            $max_amount = $arSingleFragment['max_amount'];
            $arFragmentSorted[$activityId][$fragmentId] = array($propId, $max_amount);
        }
        return $arFragmentSorted;
    }

    /**
     * Step 3.
     * Get related activities by ids
     * @param $activityIds array
     * @return array, $map[$activityId] = $arExtraConfig
     */
    private function getActivities($activityIds) {
        $arActivity = array();
        if(empty($activityIds)) {
            return $arActivity;
        }
        $activityIdStr = implode(",", $activityIds);
        $curTime = date("Y-m-d H:i:s", time());
        $status = self::ACTIVITY_STATUS_OPEN;
        $sql = "SELECT id, extra_configs FROM activities WHERE id IN (?) AND status = ? AND start_time <= ? AND end_time >= ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activityIdStr, $status, $curTime, $curTime));
        $arActivity = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(empty($arActivity)) {
            return $arActivity;
        }
        $arFinalActivity = array();
        foreach ($arActivity as $arSingleActivity) {
            $activityId = $arSingleActivity['id'];
            $extraConfig = $arSingleActivity['extra_configs'];
            $arFinalActivity[$activityId] = json_decode($extraConfig, true);
        }
        return $arFinalActivity;
    }

    /**
     * Step 2.
     * Get related activity event config from zizai db, event_config_id maybe map to multi activity_ids
     * @param $eventConfigId
     * @param $arActivityIds array
     * @return array
     */
    private function getActivityEventConfig($eventConfigId, &$arActivityIds) {
        $arConfigs = array();
        if(empty($eventConfigId)) {
            return $arConfigs;
        }
        $sql = "SELECT * FROM activity_event_configs WHERE event_config_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($eventConfigId));
        $arConfigs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(empty($arConfigs)) {
            return $arConfigs;
        }
        //resort by event config id and activity id
        //create temp array for storage award target and fragment id related datas
        $arSortedConfigs = array();
        foreach ($arConfigs as $arSingleConfig) {
            $activityId = $arSingleConfig['activity_id'];
            $arSortedConfigs[$eventConfigId][$activityId] = $arSingleConfig;
            if(!in_array($activityId, $arActivityIds)) {
                array_push($arActivityIds, $activityId);
            }
         }
        return $arSortedConfigs;
    }

    /**
     * Step 1.
     * Get single event record from zizai db
     * only get event for user, one record per event id
     * @param $eventId
     * @return array
     */
    private function getEvent($eventId) {
        $arEvent = array();
        if(empty($eventId)) {
            return $arEvent;
        }
        $sql = "SELECT config_id, user_id, target_user_id FROM event WHERE id = ? AND type = ? LIMIT 1";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($eventId, self::EVENT_TYPE_USER));
        $arEvent = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $arEvent;
    }
}
