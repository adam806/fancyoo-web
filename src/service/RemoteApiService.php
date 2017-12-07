<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午12:12
 */

namespace Fancyoo\Service;

use Fancyoo\Exception\CustomException;
use Fancyoo\Exception\ErrorCode;
use \Curl\Curl;
use Interop\Container\ContainerInterface;

class RemoteApiService extends BaseService{
    private $curl;
    private $servers;
    private $apis;
    private $coreServer;
    private $wechatConfig;

    public function __construct(ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->curl = new Curl();
        $this->curl ->setTimeout(5);
        $this->servers = $ci->get("settings")["remoteServers"];
        $this->apis = $ci->get("settings")["remoteApis"];
        $this->coreServer = $ci->get("settings")["coreServer"];
        $this->wechatConfig = $ci->get("settings")["wechat"];
    }

    /**
     * Send activity fragment reward message to chat room
     * @param $targetUserId
     * @param $activityId
     * @param $eventId
     * @return array|mixed
     */
    public function sendActivityFragmentDrawMessage($targetUserId, $activityId, $eventId) {
        $url = $this->servers['messaging_server'] . $this->apis['svc_messaging'];
        $chatContent = '您完成邂逅活动，获取了碎片奖励！';
        $content = array(
            'content' => $chatContent,
            'extra' => array(
                'type' => 13,
                'activityId' => $activityId,
                'eventId' => $eventId
            ),
        );
        $data = array(
            'targetType'=> 3, //system message
            'targetId' => $targetUserId,
            'senderId' => 66666666, //from system user
            'contentType' => 10,//for encounter
            'isPersisted' => 0,
            'isCounted' => 0,
            'isIncludeSender' => 0,
            'content' => json_encode($content)
        );
        $this->curl->post($url, $data);
        if ($this->curl->error) {
            $this->logger->err(json_encode(array("event"=>"REMOTE_API_CALL_FAILED", "method"=>"POST", "url"=>$url, "params" => $data, "errorMessage" => $this->curl->errorMessage, "errorCode" => $this->curl->errorCode)));
        }
        $res = $this->curl->rawResponse;
        if(empty($res)){
            return array();
        }
        return json_decode($res);
    }

    /**
     * 注册用户
     */
    public function registerWithWechat($openid, $unionid){

        $url = $this->servers["core_server"] . $this->apis["register_with_wechat"];
        $channelId = "wechat";
        $data = array(
            "openid" => $openid,
            "unionid" => $unionid
        );
        $this->curl->post($url, $data);
        if ($this->curl->error) {
            $this->logger->err(json_encode(array("event"=>"REMOTE_API_CALL_FAILED", "method"=>"POST", "url"=>$url, "params" => $data, "errorMessage" => $this->curl->errorMessage, "errorCode" => $this->curl->errorCode)));
        }
        $res = $this->curl->rawResponse;
        if(empty($res)){
            return array();
        }
        return json_decode($res);
    }

    public function getWechatAccessToken($code){
        $appid = $this->wechatConfig["appid"];
        $secret = $this->wechatConfig["secret"];
        $getAccessTokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $data = array("appid" => $appid, "secret" => $secret, "code" => $code, "grant_type" => "authorization_code");
        $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->get($getAccessTokenUrl, $data);
        if ($this->curl->error) {
            throw new CustomException(ErrorCode::REMOTE_API_CALL_FAILED);
        } else {
            $res = $this->curl->rawResponse;
            if(!empty($res)) {
                $result = json_decode($res, true);
                if(isset($result["errcode"]) && $result["errcode"] > 0){
                    throw new CustomException(ErrorCode::WECHAT_AUTH_FAILED);
                }
                $access_token = $result["access_token"];
                $refresh_token = $result["refresh_token"];
                $expires_in = $result["expires_in"];
                $scope = $result["scope"];
                $openid = $result["openid"];

                $getUserInfoUrl = "https://api.weixin.qq.com/sns/userinfo";
                $data = array("access_token" => $access_token, "openid" => $openid, "lang" => "zh_CN");
                $this->curl->get($getUserInfoUrl, $data);
                if ($this->curl->error) {
                    throw new CustomException(ErrorCode::REMOTE_API_CALL_FAILED);
                } else {
                    $res = $this->curl->rawResponse;
                    if(!empty($res)) {
                        $userInfo = json_decode($res, true);
                        if(isset($userInfo["errcode"]) && $userInfo["errcode"] > 0){
                            throw new CustomException(ErrorCode::WECHAT_AUTH_FAILED);
                        }
                        $userInfo["access_token"] = $access_token;
                        $userInfo["refresh_token"] = $refresh_token;
                        $userInfo["expires_in"] = $expires_in;
                        $userInfo["scope"] = $scope;
                        return $userInfo;
                    }
                }
            }
        }
        return null;
    }

    /**
     * 获取邂逅问题
     * @param $from 获取者的用户id（微信用户的8位id）,还未注册请传""
     * @param $to 希望邂逅目标的用户id
     * @param $sceneId 场景id
     * @return 待回答的问题列表
     * @throws CustomException
     */
    public function getEncounterQuestions($from, $to, $sceneId){
        $url = $this->servers["encounter_server"] . $this->apis["encounter_questions"];
        $data = array(
            "from" => $from,
            "to" => $to,
            "sceneId" => $sceneId
        );
        $this->curl->get($url, $data);
        if ($this->curl->error) {
            $this->logger->err(json_encode(array("event"=>"REMOTE_API_CALL_FAILED", "method"=>"GET", "url"=>$url, "params" => $data, "errorMessage" => $this->curl->errorMessage, "errorCode" => $this->curl->errorCode)));
        }
        $res = $this->curl->rawResponse;
        if(empty($res)){
            return array();
        }
        return json_decode($res);
    }

    public function submitEncounterAnswers($sceneId, $storyId, $questionIds, $answers, $from, $to){
        $url = $this->servers["encounter_server"] . $this->apis["encounter_answers"];
        $data = array(
            "sceneId" => $sceneId,
            "storyId" => $storyId,
            "questionIds" => $questionIds,
            "answers" => $answers,
            "from" => $from,
            "to" => $to
        );
        $this->curl->post($url, $data);
        if ($this->curl->error) {
            $this->logger->err(json_encode(array("event"=>"REMOTE_API_CALL_FAILED", "method"=>"POST", "url"=>$url, "params" => $data, "errorMessage" => $this->curl->errorMessage, "errorCode" => $this->curl->errorCode)));
        }
        $res = $this->curl->rawResponse;
        if(empty($res)){
            return array();
        }
        return json_decode($res);
    }

    /**
     * 获取邂逅场景信息
     * @param $sceneId 场景id
     */
    public function getEncounterScene($sceneId){
        $url = $this->servers["encounter_server"] . $this->apis["encounter_scene"];
        $data = array(
            "sceneId" => $sceneId
        );
        $this->curl->get($url, $data);
        if ($this->curl->error) {
            $this->logger->err(json_encode(array("event"=>"REMOTE_API_CALL_FAILED", "method"=>"GET", "url"=>$url, "params" => $data, "errorMessage" => $this->curl->errorMessage, "errorCode" => $this->curl->errorCode)));
        }
        $res = $this->curl->rawResponse;
        if(empty($res)){
            return array();
        }
        return json_decode($res);
    }

    public function addAwards($user_id, $awards){
        $user = $this->ci->get("userService")->getUserInfoById($user_id);
        if(empty($user)){
            return false;
        }
        //解析奖励格式
        $items = array();
        $award_array = json_decode($awards, true);
        foreach ($award_array as $award){
            //如果奖励不符合性别要求，则剔除
            if(isset($award["gender"])){
                if($award["gender"] != $user["gender"]){
                    continue;
                }else{
                    unset($award["gender"]);
                }
            }
            $items[] = $award;
        }
        $unique_id = $user["unique_id"];
        $url = $this->servers["core_server"] . $this->apis["add_items"];
        $data = array(
            "userId" => $unique_id,
            "items" => json_encode($items),
            "from" => "activity",
        );
        $this->curl->post($url, $data);
        if ($this->curl->error) {
            $this->logger->err(json_encode(array("event"=>"REMOTE_API_CALL_FAILED", "method"=>"POST", "url"=>$url, "params" => $data, "errorMessage" => $this->curl->errorMessage, "errorCode" => $this->curl->errorCode)));
        }
        $res = $this->curl->rawResponse;
        $this->logger->info(json_encode(array("event"=>"REMOTE_API_CALL_INFO", "method"=>"POST", "url"=>$url, "params" => $data, "res" => $res)));

        if(empty($res)){
            return false;
        }
        $result = json_decode($res, true);
        if(!empty($result) && isset($result["code"]) && $result["code"] > 0){
            return false;
        }
        return true;
    }

}