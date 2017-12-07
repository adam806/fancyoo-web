<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午6:02
 */

namespace Fancyoo\Controller;

use Fancyoo\Service\Activity\ActivityStatus;
use Fancyoo\Service\Activity\ActivityType;
use Interop\Container\ContainerInterface;
use Fancyoo\Exception\CustomException;
use Fancyoo\Exception\ErrorCode;

class ActivityController extends BaseController {
    private $activityEventService;
    private $activityLoginService;
    private $activity;
    private $fragmentActivity;
    private $gamingActivity;
    private $userService;

    public function __construct(ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->activityEventService = $this->ci->get("activityEventService");
        $this->activity = $this->ci->get("activity");
        $this->fragmentActivity = $this->ci->get("fragmentActivity");
        $this->gamingActivity = $this->ci->get("gamingActivity");
        $this->userService = $this->ci->get("userService");
        $this->activityLoginService = $this->ci->get("activityLoginService");
    }

    public function drawCheck($request, $response, $args) {
        //get GET parameters
        $params = $request->getQueryParams();
        $activityId = isset($args['id']) ? $args['id'] : '';
        $eventId = isset($params['eventId']) ? $params['eventId'] : '';

        //get logined user id from author token
        $userId = $this->ci->get('user_id');
        if (empty($userId)) {
            //no auth token
            throw new CustomException(ErrorCode::INVALID_TOKEN);
        }

        $result = $this->activityEventService->drawCheck($userId, $activityId, $eventId);
        return $response->withJson($result, 200);
    }

    public function drawFragments($request, $response, $args) {
        //get POST parameters
        $params = $request->getParsedBody();
        $activityId = isset($args['id']) ? $args['id'] : '';
        $eventId = isset($params['eventId']) ? $params['eventId'] : '';
        //get logined user id from author token
        $userId = $this->ci->get('user_id');
        if (empty($userId)) {
            //no auth token
            throw new CustomException(ErrorCode::INVALID_TOKEN);
        }
        $result = $this->activityEventService->drawFragments($userId, $activityId, $eventId);
        return $response->withJson($result, 200);
    }

    public function checkActivityLogin($request, $response, $args){
        $params = $request->getParsedBody();
        $userId = isset($params['userId']) ? $params['userId'] : '';
        $status = isset($params['status']) ? $params['status'] : '';
        //login check
        $result = $this->activityLoginService->allLoginCheck($userId, $status);
        return $response->withJson($result, 200);
    }

    public function checkActivityEvent($request, $response, $args){
        $params = $request->getParsedBody();
        //$userId = isset($params['userId']) ? $params['userId'] : '';
        $userId = $this->ci->get('user_id');
        if (empty($userId)) {
            //no auth token
            throw new CustomException(ErrorCode::INVALID_TOKEN);
        }
        $eventId = isset($params['eventId']) ? $params['eventId'] : '';
        $extParams = isset($params['params']) ? $params['params'] : array();
        //begin check event
        $result = $this->activityEventService->check($userId, $eventId, $extParams);
        return $response->withJson($result, 200);
    }

    public function getActivities($request, $response, $args){
        $status = ActivityStatus::$ONLINE;
        $activities = $this->activity->getActivities($status);
        return $response->withJson($activities, 200);
    }

    public function getActivityInfo($request, $response, $args){
        $activity_id = isset($args['id']) ? $args['id'] : '';

        $user_id = $this->ci->get("user_id");
        if (empty($user_id)) {
            // 非授权用户也可以看到活动信息，只是不能看到自己的碎片
            // throw new CustomException(ErrorCode::INVALID_TOKEN);
        }
        $activity = $this->gamingActivity->getModel($activity_id);
        $activity->user_fragment_amount = $activity->getUserFragmentsAmount($user_id);
        if($activity->type == ActivityType::$FRAGMENT_RANK){
            $activity->getActivityRanks();
        }elseif($activity->type == ActivityType::$FRAGMENT_GAMING){
            $activity->getExchangeAwards($user_id);
            $activity->getGamingList($user_id);
        }elseif($activity->type == ActivityType::$FRAGMENT_EXCHANGE){
            $activity->getExchangeAwards($user_id);
        }
        return $response->withJson($activity, 200);
    }

    public function getActivityRanks($request, $response, $args){
        $activity_id = isset($args['id']) ? $args['id'] : '';
        $activity = $this->activity->getModel($activity_id);
        $result = $activity->getActivityRanks();
        return $response->withJson($result, 200);
    }

    public function generateActivityRanks($request, $response, $args){
        $status = ActivityStatus::$ONLINE;
        $activities = $this->activity->getActivities($status);
        foreach ($activities as $item){
            if($item->type == ActivityType::$FRAGMENT_RANK){
                //检查是否在活动时间内
                $now = time();
                if($now < strtotime($item->start_time)){
                    continue;
                }
                if($now > strtotime($item->end_time)){
                    continue;
                }
                $activity = $this->fragmentActivity->getModel($item->id);
                $activity->generateRanksByFragments();
            }
        }
        return $response->withJson(true, 200);
    }

    public function getActivityExchanges($request, $response, $args){
        $activity_id = isset($args['id']) ? $args['id'] : '';
        $activity = $this->fragmentActivity->getModel($activity_id);
        $user_id = $this->ci->get("user_id");
        if (empty($user_id)) {
            // 非授权用户也可以看到活动信息，只是不能看到自己的碎片
            // throw new CustomException(ErrorCode::INVALID_TOKEN);
        }
        $result = $activity->getExchangeAwards($user_id);
        return $response->withJson($result, 200);
    }

    public function exchange($request, $response, $args){
        $activity_id = isset($args['id']) ? $args['id'] : '';
        $params = $request->getParsedBody();
        $exchange_id = isset($params['exchange_id']) ? $params['exchange_id'] : '';
        $amount = isset($params['amount']) ? $params['amount'] : 1;

        $user_id = $this->ci->get("user_id");
        if (empty($user_id)) {
            throw new CustomException(ErrorCode::INVALID_TOKEN);
        }
        $activity = $this->fragmentActivity->getModel($activity_id);
        if(!in_array($activity->type, array(ActivityType::$FRAGMENT_EXCHANGE, ActivityType::$FRAGMENT_GAMING))){
            throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_NOT_SUPPORT);
        }
        $result = $activity->exchangeAwards($user_id, $exchange_id, $amount);
        return $response->withJson($result, 200);
    }

    public function participateGaming($request, $response, $args){
        $activity_id = isset($args['id']) ? $args['id'] : '';
        $params = $request->getParsedBody();
        $gaming_id = isset($params['gaming_id']) ? $params['gaming_id'] : '';
        $amount = isset($params['amount']) ? $params['amount'] : 1;

        $user_id = $this->ci->get("user_id");
        if (empty($user_id)) {
            throw new CustomException(ErrorCode::INVALID_TOKEN);
        }
        $activity = $this->gamingActivity->getModel($activity_id);
        if($activity->type != ActivityType::$FRAGMENT_GAMING){
            throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_NOT_SUPPORT);
        }
        $result = $activity->participateGaming($user_id, $gaming_id, $amount);
        return $response->withJson($result, 200);
    }

    public function getGamingList($request, $response, $args){
        $activity_id = isset($args['id']) ? $args['id'] : '';
        $activity = $this->gamingActivity->getModel($activity_id);
        $user_id = $this->ci->get("user_id");
        $result = $activity->getGamingList($user_id);
        return $response->withJson($result, 200);
    }

    public function getHistoryGamingWinners($request, $response, $args){
        $activity_id = isset($args['id']) ? $args['id'] : '';
        $activity = $this->gamingActivity->getModel($activity_id);
        $result = $activity->getHistoryGamingWinners();
        return $response->withJson($result, 200);
    }

    public function generateGamingWinners($request, $response, $args){
       $status = ActivityStatus::$ONLINE;
        $activities = $this->activity->getActivities($status);
        foreach ($activities as $item){
            if($item->type == ActivityType::$FRAGMENT_GAMING){
                //检查是否在活动时间内
                $now = time();
                if($now < strtotime($item->exchange_deadline)){
                    continue;
                }
                $activity = $this->gamingActivity->getModel($item->id);
                $activity->generateGamingWinners();
            }
        }
        return $response->withJson(true, 200);
    }

    public function getEncounterScenesForActivity($request, $response, $args){
        $scenes = $this->activity->getEncounterScenesForActivity();
        return $response->withJson($scenes, 200);
    }
}