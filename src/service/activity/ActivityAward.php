<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午12:12
 */

namespace Fancyoo\Service\Activity;

class ActivityAward {
    public $name = "";
    public $icon = "";
    public $description = '';
    public $is_group = false;

    public function __construct($row, $gender = -1){
        $this->name = $row["name"];
        $this->icon = $row["icon"];
        if(!empty($row["description"])){
            $this->description = $row["description"];
        }
        //是否是组合的奖励
        if($row["is_group"] == 1){
            $this->is_group = true;
        }
        //是否是针对女性的奖励
        if($gender == 0 && !empty($row["icon2"])){
            $this->icon = $row["icon2"];
        }
    }
}