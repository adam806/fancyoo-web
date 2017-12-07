<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/20
 * Time: 下午5:03
 */

namespace Fancyoo\Controller;

use Interop\Container\ContainerInterface;

class BaseController {
    protected $ci;
    protected $redis;
    protected $coreDb;
    protected $logger;
    protected $renderer;
    protected $rabbitMQ;

    //Constructor
    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
        $this->redis = $this->ci->get('redis');
        $this->coreDb = $this->ci->get('coreDb');
        $this->logger = $this->ci->get('logger');
        $this->renderer = $this->ci->get('renderer');
        $this->rabbitMQ = $this->ci->get('rabbitMQ');
    }
}