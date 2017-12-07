<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/20
 * Time: 下午6:27
 */

namespace Fancyoo\Service;

use Interop\Container\ContainerInterface;

class BaseService {
    protected $ci;
    protected $redis;
    protected $coreDb;
    protected $ossDb;
    protected $logger;
    protected $rabbitMQ;

    //Constructor
    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
        $this->redis = $this->ci->get('redis');
        $this->coreDb = $this->ci->get('coreDb');
        $this->ossDb = $this->ci->get('ossDb');
        $this->logger = $this->ci->get('logger');
        $this->rabbitMQ = $this->ci->get('rabbitMQ');
    }

    public static function Format($key, $value)
    {
        return vsprintf($key, $value);
    }
}