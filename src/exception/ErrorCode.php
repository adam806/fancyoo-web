<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/21
 * Time: 上午11:30
 */

namespace Fancyoo\Exception;


class ErrorCode
{
    const INVALID_ARGUMENT = array("code"=>1000, "message"=>"参数不合法");
    const REMOTE_API_CALL_FAILED = array("code"=>1001, "message"=>"API调用失败");
    const NO_PERMISSION = array("code"=>1002, "message"=>"无操作权限");
    const SQL_EXECUTION_FAILED = array("code"=>1003, "message"=>"数据库错误");
    const USER_IS_NOT_EXIST = array("code"=>1004, "message"=>"该用户不存在");
    const OPERATION_FAILED = array("code"=>1005, "message"=>"操作失败");
    const WECHAT_AUTH_FAILED = array("code"=>1006, "message"=>"微信授权失败");
    const ACTIVITY_IS_NOT_EXIST = array("code"=>1007, "message"=>"活动不存在");
    const ACTIVITY_NOT_SUPPORT_RANK = array("code"=>1008, "message"=>"活动没有配置排行榜数量");
    const ACTIVITY_NOT_LOADED = array("code"=>1009, "message"=>"活动尚未加载");
    const ACTIVITY_EXCHANGE_NOT_START = array("code"=>1010, "message"=>"活动兑换尚未开始");
    const ACTIVITY_EXCHANGE_IS_END = array("code"=>1011, "message"=>"活动兑换已经结束");
    const ACTIVITY_EXCHANGE_EXCEED_TIMES_LIMIT = array("code"=>1012, "message"=>"超过了兑换的次数限制");
    const ACTIVITY_EXCHANGE_EXCEED_AMOUNT_LIMIT = array("code"=>1013, "message"=>"超过了当日兑换的数量限制");
    const ACTIVITY_EXCHANGE_FRAGMENT_NOT_ENOUGH = array("code"=>1014, "message"=>"碎片数量不足");
    const ACTIVITY_EXCHANGE_INVALID = array("code"=>1015, "message"=>"非法的奖励兑换");
    const ACTIVITY_FRAGMENT_NOT_EXIST = array("code"=>1016, "message"=>"活动碎片尚未配置");
    const ACTIVITY_EXCHANGE_NOT_SUPPORT = array("code"=>1017, "message"=>"当前活动不支持碎片兑换");

    const ACTIVITY_GAMING_FULL = array("code"=>1018, "message"=>"碎片已集满");

    const INVALID_SIGNATURE = array("code"=>2000, "message"=>"签名错误");
    const INVALID_TOKEN = array("code"=>2001, "message"=>"授权已过期");
}