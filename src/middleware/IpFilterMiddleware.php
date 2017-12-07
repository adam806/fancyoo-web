<?php

namespace Fancyoo\Middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class IpFilterMiddleware
{
    protected $ipWhiteList = [];
    protected $ipBlackList = [];
    protected $defaultMode = null;

    /**
     * IpFilterMiddleware constructor.
     * @param $ipWhiteList 白名单：多个ip之间英文逗号分隔
     * @param $ipBlackList 黑名单：多个ip之间英文逗号分隔
     * @param int $defaultMode 默认模式：DENY 表示默认拒绝，除非该ip在白名单中；ALLOW 表示默认允许，除非该ip在黑名单中；
     */
    public function __construct($ipWhiteList, $ipBlackList, $defaultMode = Mode::ALLOW)
    {
        $this->defaultMode = $defaultMode;
        if(!empty($ipWhiteList)){
            $this->ipWhiteList = $this->parseIpList($ipWhiteList);
        }
        if(!empty($ipBlackList)){
            $this->ipBlackList = $this->parseIpList($ipBlackList);
        }
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        $ip = $this->getIp($request);
        if (!$this->isAllowed($ip)) {
            $response = $response->withStatus(401);
            $response->getBody()->write("Access denied");
        }else{
            $response = $next($request, $response);
        }
        return $response;
    }

    private function getIp(Request $request){
        //如果是通过代理（比如SLB)过来的，需要从HTTP_X_FORWARDED_FOR取真实IP
        if(!empty($request->getServerParams()['HTTP_X_FORWARDED_FOR'])){
            return $request->getServerParams()['HTTP_X_FORWARDED_FOR'];
        }
        return $request->getServerParams()['REMOTE_ADDR'];
    }

    private function parseIpList($ipList){
        $ips = array();
        $ipList = explode(",", $ipList);
        foreach ($ipList as $ip){
            $ips[] = ip2long(trim($ip));
        }
        return $ips;
    }

    private function isAllowed($ip){
        $ip = ip2long($ip);
        if($this->defaultMode == Mode::ALLOW && !in_array($ip, $this->ipBlackList)){
            return true;
        }
        if($this->defaultMode == Mode::DENY && in_array($ip, $this->ipWhiteList)){
            return true;
        }
        return false;
    }
}
