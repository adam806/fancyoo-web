<?php

/**
 * RabbitMQ消息队列服务接口
 *
 * $author: xiaohua
 *
 */
namespace Fancyoo\Service\queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessageQueueService {
    const INTIMACY_QUEUE = "intimacy_queue";
    const NOTICE_QUEUE = "push_notice";
    const USER_LEVEL_QUEUE = "user_level_notice";
    const QUEUE_EVENT = "event_queue";

    private $arSettings = array();//rabbitMQ setting
	private $connection = null;

	public function __construct($setting = array()){
	    $this->arSettings = $setting;
    }

    /**
     * Connect rabbitMQ server
     * @return bool
     */
	public function connectServer() {
        if($this->connection == null) {
            if(empty($this->arSettings)) {
                return false;
            }
            $host = $this->arSettings['host'];
            $port = $this->arSettings['port'];
            $user = $this->arSettings['user'];
            $pwd = $this->arSettings['pwd'];
            $this->connection = new AMQPStreamConnection($host, $port, $user, $pwd);
        }
        return true;
    }

    /**
     * 发布消息到队列
     * @param $queueName 队列名称
     * @param $data 数据，json格式
     * @param bool $durable 是否持久化，默认不持久化，重要队列需要定义为持久化
     * @return bool
     */
	public function sendMessageToQueue($queueName, $data, $durable = false){
		$this->connectServer();
        if(!$this->connection) {
            return false;
        }
		$channel = $this->connection->channel();
		$channel->queue_declare($queueName, false, $durable, false, false);

		$msg = new AMQPMessage(
			$data,
			array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );
		$channel->basic_publish($msg, '', $queueName);
		$channel->close();
		$this->closeConnection();
        return true;
	}

	private function closeConnection(){
		if($this->connection != null) {
			$this->connection->close();
			$this->connection = null;
		}
	}

}