<?php
/**
 * Created by PhpStorm.
 * User: d
 * Date: 2016/5/26
 * Time: 12:14
 */

namespace Fancyoo\Middleware;

class LoginMiddleware
{
    private $container;
    private $userService;
    const AUTH_HEADER = 'Authorization';
    const AUTH_PREFIX = 'Bearer';

    public function __construct($container)
    {
        $this->container = $container;
        $this->userService = $container->get("userService");
    }

    public function __invoke($request, $response, $next)
    {
        $authorization = $request->getHeader(self::AUTH_HEADER);
        $token = '';
        if (!empty($authorization)) {
            $token = trim(str_ireplace(self::AUTH_PREFIX, '', array_shift($authorization)));
        }
        $user_id = '';
        if (!empty($token)) {
            //检查用户token
            $user = $this->userService->parseUserAccessToken($token);
            if ($user) {
                $user_id = $user["user_id"];
            }
        }
        $this->container['user_id'] = $user_id;
        $response = $next($request, $response);
        return $response;
    }
}


