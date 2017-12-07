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

class UserController extends BaseController {
    private $userService;
    private $activiyLoginService;

    public function __construct(ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->userService = $this->ci->get("userService");
        $this->activiyLoginService = $this->ci->get("activityLoginService");
    }

    public function getUserInfo($request, $response, $args){
        $unique_id = $args["id"];
        $result = $this->userService->getUserInfo($unique_id);
        if(!empty($result) && isset($result["id"])){
            unset($result["id"]);
        }
        return $response->withJson($result, 200);
    }

    public function authorizeWithWechat($request, $response, $args){
        $params = $request->getParsedBody();
        $openid = $params['openid'];
        $unionid = $params['unionid'];
        $nickname = $params['nickname'];
        $sex = $params['sex'];
        $province = $params['province'];
        $city = $params['city'];
        $country = $params['country'];
        $headimgurl = $params['headimgurl'];
        $privilege = $params['privilege'];
        $access_token = $params['access_token'];
        $expires_in = $params['expires_in'];
        $refresh_token = $params['refresh_token'];
        $scope = $params['scope'];

        $result = $this->userService->authorizeWithWechat($openid, $unionid, $nickname, $sex, $province, $city, $country, $headimgurl, $privilege, $access_token, $expires_in, $refresh_token, $scope);
        return $response->withJson($result, 200);
    }

    public function updateUserOnlineStatus($request, $response, $args){
        $query_params = $request->getQueryParams();
        $timestamp = $query_params['timestamp'];
        $nonce = $query_params['nonce'];
        $signature = $query_params['signature'];

        //check signature
        $isValid = $this->userService->checkRongCloudSignature($nonce, $timestamp, $signature);
        if(!$isValid){
            throw new CustomException(ErrorCode::INVALID_SIGNATURE);
        }

        $post_params = $request->getParsedBody();
        if(!empty($post_params) && is_array($post_params)){
            foreach ($post_params as $item){
                $unique_id = $item['userid'];
                $status = $item['status'];
                $os = $item['os'];
                $time = $item['time'];
                $user = $this->userService->getUserInfo($unique_id);
                $result = false;
                if(!empty($user)){
                    $user_id = $user["id"];
                    $result = $this->userService->updateUserOnlineStatus($user_id, $status, $os, $time);
                    //login event check
                    $this->activiyLoginService->allLoginCheck($user_id, $status);
                }
            }
        }
        $this->logger->info(json_encode(array("event"=>"updateUserOnlineStatus", "method"=>"POST", "post_params" => json_encode($params), "query_params" => json_encode($query_params))));

        return $response->withJson($result, 200);
    }

}