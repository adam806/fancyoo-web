<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午6:02
 */

namespace Fancyoo\Controller;

use Interop\Container\ContainerInterface;
use Fancyoo\Exception\CustomException;
use Fancyoo\Exception\ErrorCode;

class EncounterController extends BaseController {
    private $remoteApiService;
    private $userService;

    public function __construct(ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->remoteApiService = $this->ci->get("remoteApiService");
        $this->userService = $this->ci->get("userService");

    }

    public function getEncounterQuestions($request, $response, $args){
        $params = $request->getQueryParams();
        $to = isset($params['to']) ? $params['to'] : '';
        $sceneId = isset($params['sceneId']) ? $params['sceneId'] : '';
        $openid = isset($params['openid']) ? $params['openid'] : '';

        //先检查用户是否已是APP注册用户，如果是，则带上用户unique_id
        $user = $this->userService->getWechatUser($openid);
        if(empty($user)){
            throw new CustomException(ErrorCode::USER_IS_NOT_EXIST);
        }
        $from = '';
        if(!empty($user["user_unique_id"])){
            $from = $user["user_unique_id"];
        }
        $result = $this->remoteApiService->getEncounterQuestions($from, $to, $sceneId);
        return $response->withJson($result, 200);
    }

    public function getEncounterScene($request, $response, $args){
        $params = $request->getQueryParams();
        $sceneId = isset($params['sceneId']) ? $params['sceneId'] : '';

        $result = $this->remoteApiService->getEncounterScene($sceneId);
        return $response->withJson($result, 200);
    }

    public function submitEncounterAnswers($request, $response, $args){
        $params = $request->getParsedBody();
        $to = isset($params['to']) ? $params['to'] : '';
        $sceneId = isset($params['sceneId']) ? $params['sceneId'] : '';
        $storyId = isset($params['storyId']) ? $params['storyId'] : '';
        $questionIds = isset($params['questionIds']) ? $params['questionIds'] : '';
        $answers = isset($params['answers']) ? $params['answers'] : '';
        $openid = isset($params['openid']) ? $params['openid'] : '';
        $unionid = isset($params['unionid']) ? $params['unionid'] : '';

        //先检查用户是否已是APP注册用户，如果不是，则在提交问题前先自动注册一个APP账号
        $user = $this->userService->getWechatUser($openid);
        if(empty($user)){
            throw new CustomException(ErrorCode::USER_IS_NOT_EXIST);
        }
        $from = '';
        if(!empty($user["user_unique_id"])){
            $from = $user["user_unique_id"];
        }else {
            $res = $this->remoteApiService->registerWithWechat($openid, $unionid);
            if(!empty($res) && !empty($res["user"])) {
                $from = $res["user"]["id"];
            }
        }
        $result = $this->remoteApiService->submitEncounterAnswers($sceneId, $storyId, $questionIds, $answers, $from, $to);
        return $response->withJson($result, 200);
    }

}