<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/23
 * Time: 下午12:12
 */

namespace Fancyoo\Service\Activity;

class ActivityRankAward extends ActivityAward{
    public $rank = 0;

    public function __construct($row, $rank){
        parent::__construct($row);
        $this->rank = $rank;
    }
}
