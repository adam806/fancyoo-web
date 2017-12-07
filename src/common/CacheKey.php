<?php
namespace Fancyoo\Common;

class CacheKey {
    public static $USER_WECHAT_LOGIN_COUNT = "v2:zizai:user:wechat:user_id:%s:login:count";
    public static $USER_GEN_LOGIN_COUNT = "v2:zizai:user:user_id:%s:login:count";
    public static $USER_ACTIVITY_FRAGMENT_DRAW_STATUS = "v2:fancy:user:user_id:%s:acitity:%s:draw:status";
}
