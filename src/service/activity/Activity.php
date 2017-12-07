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
use Fancyoo\Service\Activity\ActivityRankAward;
use Fancyoo\Service\Activity\ActivityExchangeAward;

class Activity extends BaseService{
    public $id = null;
    //活动名称
    public $name = '';
    //活动展示图
    public $banner = '';
    //描述
    public $description = '';
    //规则
    public $rules = '';
    //开始时间
    public $start_time = '';
    //结束时间
    public $end_time = '';
    //奖励兑换开始时间
    public $exchange_start_time = '';
    //奖励兑换截止时间
    public $exchange_deadline = '';
    //活动状态
    public $status = 0;
    //活动类型
    public $type = 0;
    //地区, 0代表全国
    public $regions = 0;
    //标签
    public $tags = '';
    //点击活动后跳转链接
    public $goto = '';

    //活动排名
    public $ranks = '';
    //活动排名数量限制
    public $ranks_count = 0;
    //额外配置
    public $extra_configs = '';

    /**
     * 获取活动ID
     * @return 活动ID
     * @throws CustomException
     */
    public function getActivityId(){
        if(empty($this->id)){
            throw new CustomException(ErrorCode::ACTIVITY_NOT_LOADED);
        }
        return $this->id;
    }

    /***
     * 加载活动模型
     * @param $activity_id
     * @return $this
     * @throws CustomException
     */
    public function getModel($activity_id){
       $sql = "SELECT * FROM activities WHERE id = ?";
       $stmt = $this->ossDb->prepare($sql);
       $stmt->execute(array($activity_id));
       $row = $stmt->fetch(\PDO::FETCH_ASSOC);
       if(empty($row)){
           throw new CustomException(ErrorCode::ACTIVITY_IS_NOT_EXIST);
       }
       $activity = $this->constructActivityModel($row);
       return $activity;
   }

    /**
     * 将数据库记录组装成Activity对象
     * @param $row 数据库中一行记录
     * @return Activity
     */
   private function constructActivityModel($row){
       $c = get_class($this);
       $activity = new $c($this->ci);
       $activity->id = intval($row["id"]);
       $activity->name = $row["name"];
       $activity->banner = $row["banner"];
       $activity->share_image = !empty($row["share_image"]) ? $row["share_image"] : $row["banner"];
       $activity->description = $row["description"];
       $activity->start_time = $row["start_time"];
       $activity->end_time = $row["end_time"];
       $activity->goto = !empty($row["goto"]) ? $row["goto"] : "";
       $activity->rules = $row["rules"];
       $activity->exchange_start_time = $row["exchange_start_time"];
       $activity->exchange_deadline = $row["exchange_deadline"];
       $activity->ranks_count = intval($row["ranks_count"]);
       $activity->status = intval($row["status"]);
       $activity->type = intval($row["type"]);
       $activity->extra_configs = !empty($row["extra_configs"]) ? $row["extra_configs"] : "";
       $activity->regions = !empty($row["regions"]) ? $row["regions"] : "";
       $activity->tags = !empty($row["tags"]) ? $row["tags"] : "";
       return $activity;
   }

    /**
     * 将数据库记录装欢为前端显示的对象
     * @param $row 数据库中一行记录
     * @return Activity
     */
    private function toVO($row){
        $activity = new \stdClass();
        $activity->id = intval($row["id"]);
        $activity->name = $row["name"];
        $activity->banner = $row["banner"];
        $activity->start_time = date("Y-m-d", strtotime($row["start_time"]));
        $activity->end_time = date("Y-m-d", strtotime($row["end_time"]));
        $activity->goto = !empty($row["goto"]) ? $row["goto"] : "";
        $activity->exchange_start_time = date("Y-m-d", strtotime($row["exchange_start_time"]));
        $activity->exchange_deadline = date("Y-m-d", strtotime($row["exchange_deadline"]));
        $activity->status = intval($row["status"]);
        $activity->type = intval($row["type"]);
        $activity->regions = !empty($row["regions"]) ? $row["regions"] : "";
        $activity->tags = !empty($row["tags"]) ? $row["tags"] : "";
        return $activity;
    }

    /**
     * 获取活动列表
     * @param $status 活动状态
     * @return mixed
     */
    public function getActivities($status){
        $sql = "SELECT * FROM activities WHERE status = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($status));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $activities = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                $activities[] = $this->toVO($row);
            }
        }
        return $activities;
    }

    /***
     * 保存活动
     */
    public function save(){
        $params = array($this->id, $this->name, $this->banner, $this->description, $this->rules, $this->start_time, $this->end_time, $this->exchange_deadline,
            $this->status, $this->type, $this->extra_configs, $this->regions, $this->tags, $this->goto);
        if($this->id > 0){
            $sql = "UPDATE activities SET name = ?, banner = ?, description = ?, rules = ?, start_time = ?, end_time = ?, exchange_deadline = ?,
                status = ?, type = ?, extra_configs = ?, regions = ?, tags = ?, goto = ? WHERE id = ?";
            array_shift($params);
        }else{
            $sql = "INSERT INTO activities (name, banner, description, rules, start_time, end_time, exchange_deadline,
                status, type, extra_configs, regions, tags, goto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute($params);
    }

    /***
     * 上架活动
     */
    public function takeOnline(){
        $this->status = ActivityStatus::$ONLINE;
        $this->save();
    }

    /***
     * 下架活动
     */
    public function takeOffline(){
        $this->status = ActivityStatus::$OFFLINE;
        $this->save();
    }

    /***
     * 删除活动
     */
    public function delete(){
        $this->status = ActivityStatus::$DELETED;
        $this->save();
    }

    /**
     * 获取活动排名
     */
    public function getActivityRanks(){
        $activity_id = $this->getActivityId();
        if($this->ranks_count <= 0){
            throw new CustomException(ErrorCode::ACTIVITY_NOT_SUPPORT_RANK);
        }
        if(!empty($this->ranks)){
            return $this->ranks;
        }
        //获取排名信息
        $count = $this->ranks_count;
        $sql = "SELECT * FROM activity_ranks WHERE activity_id = ? ORDER BY rank LIMIT $count";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //获取排名奖励
        $rankAwards = $this->getRankAwards();
        $ranks = array();
        $i = 1;
        if(!empty($rows)){
            foreach ($rows as $row){
                $rank = new ActivityRank($this->ci, $row);
                $rank->award = $rankAwards[$i++];
                $ranks[] = $rank;
            }
        }
        $this->ranks = $ranks;
        return $this->ranks;
    }

    /**
     * 获取活动排名的奖励
     */
    public function getRankAwards(){
        $activity_id = $this->getActivityId();
        if($this->ranks_count <= 0){
            throw new CustomException(ErrorCode::ACTIVITY_NOT_SUPPORT_RANK);
        }
        $rankAwards = array();
        $sql = "SELECT name, icon, description, type, ranking_range FROM activity_award_configs WHERE activity_id = ? AND ranking_range IS NOT NULL";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($rows)){
            $delimiter = "-";
            foreach ($rows as $row){
                if(strpos($row["ranking_range"], $delimiter)){
                    $arr = explode($delimiter, $row["ranking_range"]);
                    if(count($arr) == 2){
                        for($i = $arr[0]; $i <= $arr[1]; $i++){
                            $rankAwards[$i] = new ActivityRankAward($row, $i);
                        }
                    }
                }else {
                    $rank = $row["ranking_range"];
                    $rankAwards[$rank] = new ActivityRankAward($row, $rank);
                }
            }
        }
        $this->ranks = $rankAwards;
        return $this->ranks;
    }

    /**
     * 获取上架中的活动邂逅场景
     */
    public function getEncounterScenesForActivity(){
        $sql = "SELECT extra_configs FROM activities WHERE status = ? AND extra_configs IS NOT NULL";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array(ActivityStatus::$ONLINE));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $scenes = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                if(!empty($row["extra_configs"])){
                    $configs = json_decode($row["extra_configs"], true);
                    if(!empty($configs)){
                        if(is_array($configs) && count($configs) > 0){
                            foreach ($configs as $config){
                                if(isset($config["type"]) && $config["type"] == "encounter" && isset($config["id"]) && $config["id"] > 0){
                                    $scenes[] = intval($config["id"]);
                                }
                            }
                        }else{
                            if(isset($configs["type"]) && $configs["type"] == "encounter" && isset($configs["id"]) && $configs["id"] > 0){
                                $scenes[] = intval($configs["id"]);
                            }
                        }
                    }
                }
            }
        }
        return $scenes;
    }

}