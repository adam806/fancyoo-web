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
use Lcobucci\JWT\Parser;

class UserService extends BaseService{
    public static $USER_KEY = "fancy:user:unique_id:%s";
    public static $USER_ID_KEY = "fancy:user:id:%s";

    public function getUserInfo($unique_id){
        $key = self::Format(self::$USER_KEY, $unique_id);
        if($this->redis->exists($key)){
            $row = $this->redis->hmGet($key, array('id', 'unique_id', 'nickname', 'avatar', 'gender'));
            $this->redis->expire($key, 600);
            if(!empty($row)){
                return $row;
            }
        }
        $sql = "SELECT id, unique_id, nickname, avatar, gender FROM user WHERE unique_id = ?";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($unique_id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(empty($row)){
            throw new CustomException(ErrorCode::USER_IS_NOT_EXIST);
        }
        $this->redis->hMset($key, $row);
        return $row;
    }

    public function getUserInfoById($user_id){
        $key = self::Format(self::$USER_ID_KEY, $user_id);
        if($this->redis->exists($key)){
            $row = $this->redis->hmGet($key, array('id', 'unique_id', 'nickname', 'avatar', 'gender'));
            $this->redis->expire($key, 600);
            if(!empty($row)){
                return $row;
            }
        }
        $sql = "SELECT id, unique_id, nickname, avatar, gender FROM user WHERE id = ?";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($user_id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(empty($row)){
            throw new CustomException(ErrorCode::USER_IS_NOT_EXIST);
        }
        $this->redis->hMset($key, $row);
        return $row;
    }

    public function authorizeWithWechat($openid, $unionid, $nickname, $sex, $province, $city, $country, $headimgurl, $privilege, $access_token, $expires_in, $refresh_token, $scope, $invite_unique_id){
        $user_id = 0;
        $user_unique_id = '';
        //先检查该unionid是否已在app注册
        $user = $this->getUserInfoByWechatUnionId($unionid);
        if(!empty($user)){
            $user_id = $user["id"];
            $user_unique_id = $user["unique_id"];
        }
        //检查邀请人信息
        $invite_user = $this->getUserInfo($invite_unique_id);
        if(empty($invite_user)){
            throw new CustomException(ErrorCode::USER_IS_NOT_EXIST);
        }
        $invite_user_id = $invite_user["id"];
        $flag = $this->addOrUpdateWehcatUser($openid, $unionid, $nickname, $sex, $province, $city, $country, $headimgurl, $privilege, $access_token, $expires_in, $refresh_token, $scope, $user_id, $user_unique_id, $invite_user_id, $invite_unique_id);
        return $flag;
    }

    /**
     * 通过微信unionid看是否是app注册用户
     * @param $unionid
     * @return mixed
     */
    public function getUserInfoByWechatUnionId($unionid){
        $account_type = 1;
        $sql = "SELECT * FROM user WHERE user_name = ? AND account_type = ?";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($unionid, $account_type));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row;
    }

    public function addOrUpdateWehcatUser($openid, $unionid, $nickname, $sex, $province, $city, $country, $headimgurl, $privilege, $access_token, $expires_in, $refresh_token, $scope, $user_id, $user_unique_id, $invite_user_id, $invite_unique_id){
        $sql = "INSERT INTO wechat_user(openid, unionid, user_id, user_unique_id, invite_user_id, invite_unique_id, nickname, sex, province, city, country, headimgurl, privilege, access_token, expires_in, refresh_token, scope)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id = ?, user_unique_id = ?, invite_user_id = ?, invite_unique_id = ?, nickname = ?, sex = ?, province = ?, city = ?, country = ?, headimgurl = ?, privilege = ?, access_token = ?, expires_in = ?, refresh_token = ?, scope = ?";
        $stmt = $this->coreDb->prepare($sql);
        $flag = $stmt->execute(array($openid, $unionid, $user_id, $user_unique_id, $invite_user_id, $invite_unique_id, $nickname, $sex, $province, $city, $country, $headimgurl, $privilege, $access_token, $expires_in, $refresh_token, $scope,
          $user_id, $user_unique_id, $invite_user_id, $invite_unique_id, $nickname, $sex, $province, $city, $country, $headimgurl, $privilege, $access_token, $expires_in, $refresh_token, $scope));
        if(!$flag){
            throw new CustomException(ErrorCode::SQL_EXECUTION_FAILED);
        }
        return $flag;
    }

    public function getWechatUser($openid){
        $sql = "SELECT * FROM wechat_user WHERE openid = ?";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($openid));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(!empty($row)){
            return $row;
        }
        return array();
    }

    public function updateUserOnlineStatus($user_id, $status, $os, $time){
        $sql = "INSERT INTO user_online_status(user_id, status, os, time) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = ?, os = ?, time = ?";
        $stmt = $this->coreDb->prepare($sql);
        $flag = $stmt->execute(array($user_id, $status, $os, $time, $status, $os, $time));
        return $flag;
    }

    public function checkRongCloudSignature($nonce, $timestamp, $signature){
        $appSecret = $this->ci->get("settings")["rongCloudSecret"];
        $local_signature = sha1($appSecret . $nonce . $timestamp);
        if(strcmp($signature, $local_signature)===0){
            return true;
        } else {
            $this->logger->err(json_encode(array("event"=>"updateUserOnlineStatus_invalid_signature", "appSecret"=>$appSecret, "nonce"=>$nonce, "timestamp" => $timestamp, "signature" => $signature, "local_signature" => $local_signature)));
            return false;
        }
    }

    public function checkUserAccessToken($user_id, $token){
        if(empty($user_id) || empty($token)){
            return false;
        }
        try {
            $parsedToken = (new Parser())->parse((string) $token); // Parses from a string
        }catch (\Exception $e){
            return false;
        }
        $iss = $parsedToken->getClaim("iss");
//        $name = $parsedToken->getClaim("name");
//        $acctype = $parsedToken->getClaim("acctype");
        $sql = "SELECT * FROM auth_jwt WHERE user_id = ? AND iss = ?";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($user_id, $iss));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(empty($row)){
            return false;
        }
        return true;
    }

    public function parseUserAccessToken($token){
        if(empty($token)){
            return null;
        }
        try {
            $parsedToken = (new Parser())->parse((string) $token); // Parses from a string
        }catch (\Exception $e){
            return null;
        }
        $iss = $parsedToken->getClaim("iss");
        $sql = "SELECT user_id FROM auth_jwt WHERE iss = ?";
        $stmt = $this->coreDb->prepare($sql);
        $stmt->execute(array($iss));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(empty($row)){
            return null;
        }
        return $row;
    }

}