<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午12:12
 */

namespace Fancyoo\Service\Activity;

class ActivityExchangeAward extends ActivityAward{
    public $exchange_id = 0; //兑换ID
    public $fragment_id = 0; //碎片id
    public $fragment_amount = 0; //需要的碎片数
    public $exchange_amount = 0; //一次兑换奖励的数量
    public $max_exchange_amount = 0; //每天最多可兑换数量
    public $available_exchange_amount = 0; //剩余可兑换数量
    public $max_exchange_times = 0; //最多兑换次数
    public $available_exchange_times = 0; //剩余兑换次数

    public function __construct($row, $gender = -1){
        parent::__construct($row, $gender);
        $this->exchange_id = intval($row["exchange_id"]);
        $this->fragment_id = intval($row["fragment_id"]);
        $this->fragment_amount = intval($row["fragment_amount"]);
        $this->exchange_amount = intval($row["exchange_amount"]);

        $this->max_exchange_amount = intval($row["max_exchange_amount"]);
        $this->available_exchange_amount = $this->max_exchange_amount;

        $this->max_exchange_times = intval($row["max_exchange_times"]);
        $this->available_exchange_times = $this->max_exchange_times;
    }
}