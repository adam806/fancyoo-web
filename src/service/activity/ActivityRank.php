<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午12:12
 */

namespace Fancyoo\Service\Activity;

use Fancyoo\Exception\CustomException;
use Fancyoo\Exception\ErrorCode;
use Fancyoo\Service\BaseService;
use Interop\Container\ContainerInterface;

class ActivityRank extends BaseService{
    public $rank = 0;
    public $num = 0;
    public $award = null;
    public $user = null;

    public function __construct(ContainerInterface $ci, $row = null)
    {
        parent::__construct($ci);
        $this->rank = $row["rank"];
        $this->num = $row["num"];
        if($row["user_id"] > 0){
            $this->user = $this->ci->get("userService")->getUserInfoById($row["user_id"]);
            unset($this->user["id"]);
        }
    }

//    public function constructActivityRankModel($row){
//        $rank = new ActivityRank($this->ci);
//        $rank->rank = $row["rank"];
//        $rank->num = $row["num"];
//        if($row["user_id"] > 0){
//            $rank->user = $this->ci->get("userService")->getUserInfoById($row["user_id"]);
//            unset($rank->user["id"]);
//        }
//        return $rank;
//    }
}