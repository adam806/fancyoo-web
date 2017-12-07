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
use Fancyoo\Service\Activity\Activity;

class FragmentActivity extends Activity{
    //活动兑换
    public $exchanges = '';
    //活动收集的碎片id
    protected $fragment_id = null;

    public function getFragmentId(){
        if($this->fragment_id > 0){
            return $this->fragment_id;
        }
        $activity_id = $this->getActivityId();
        $sql = "SELECT * FROM activity_fragment_configs WHERE activity_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(empty($row)){
            throw new CustomException(ErrorCode::ACTIVITY_FRAGMENT_NOT_EXIST);
        }
        $this->fragment_id = $row["id"];
        return $this->fragment_id;
    }

    /**
     * 生成活动的排名
     */
    public function generateRanksByFragments(){
        $activity_id = $this->getActivityId();
        $fragment_id = $this->getFragmentId();
        //生成排名信息
        $count = $this->ranks_count;
        $sql = "SELECT user_id, SUM(fragment_amount) AS num FROM user_fragments WHERE activity_id = ? AND fragment_id = ? GROUP BY user_id ORDER BY num DESC LIMIT $count";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id, $fragment_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $ranks = array();
        $index = 1;
        if(!empty($rows)){
            //清除旧排名
            $sql = "DELETE FROM activity_ranks WHERE activity_id = ?";
            $stmt = $this->ossDb->prepare($sql);
            $stmt->execute(array($activity_id));
            //插入新排名
            foreach ($rows as $row){
                $row["rank"] = $index++;
                $sql = "INSERT INTO activity_ranks (user_id, activity_id, num, rank) VALUES (?, ?, ?, ?)";
                $stmt = $this->ossDb->prepare($sql);
                $stmt->execute(array($row["user_id"], $activity_id, $row["num"], $row["rank"]));
                $rank = new ActivityRank($this->ci, $row);
                $ranks[] = $rank;
            }
        }
        $this->ranks = $ranks;
        return $this->ranks;
    }

    /**
     * 获取用户碎片数量
     * @param $user_id
     * @return int
     */
    public function getUserFragmentsAmount($user_id){
        if(empty($user_id)){
            return 0;
        }
        $activity_id = $this->getActivityId();
        $sql = "SELECT fragment_amount FROM user_fragments WHERE user_id = ? AND activity_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($user_id, $activity_id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(!empty($row)){
            return intval($row['fragment_amount']);
        }
        return 0;
    }

    /**
     * 兑换奖励
     * @param $user_id
     * @param $exchange_id
     * @param $amount 兑换数量
     */
    public function exchangeAwards($user_id, $exchange_id, $amount = 1){
        $activity_id = $this->getActivityId();
        $fragment_id = $this->getFragmentId();
        //先检查是否满足兑换的时间
        $now = time();
        if($now < strtotime($this->exchange_start_time)){
            throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_NOT_START);
        }
        if($now > strtotime($this->exchange_deadline)){
            throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_IS_END);
        }
        //查找兑换奖励
        $sql = "SELECT e.*, a.award, a.type AS award_type FROM activity_exchange_configs AS e INNER JOIN activity_award_configs AS a ON a.id = e.award_id WHERE e.id = ? AND e.fragment_id = ? AND e.activity_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($exchange_id, $fragment_id, $activity_id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(empty($row)){
            throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_INVALID);
        }
        $award = $row["award"];
        $award_type = $row["award_type"];
        /* 检查是否符合兑换条件 */

        //兑换检查次数限制
        if($row["max_exchange_times"] > 0){
            $exchange_times = $this->getUserExchangeTimesById($user_id, $exchange_id);
            if($exchange_times >= $row["max_exchange_times"]){
                throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_EXCEED_TIMES_LIMIT);
            }
        }
        //检查兑换数量限制
        if($row["max_exchange_amount"] > 0){
            $exchanged_amount = $this->getExchangedAmountById($exchange_id);
            if($exchanged_amount >= $row["max_exchange_amount"]){
                throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_EXCEED_AMOUNT_LIMIT);
            }
        }
        //检查碎片是否够
        $userFragmentsAmount = $this->getUserFragmentsAmount($user_id);
        if($userFragmentsAmount < $row["fragment_amount"] * $amount){
            throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_FRAGMENT_NOT_ENOUGH);
        }
        //全部符合，则执行奖励逻辑
        $fragment_amount = $row["fragment_amount"];
        $exchange_amount = $row["exchange_amount"];
        return $this->executeExchange($user_id, $exchange_id, $fragment_id, $fragment_amount, $exchange_amount, $activity_id, $award, $award_type);
    }

    private function executeExchange($user_id, $exchange_id, $fragment_id, $fragment_amount, $exchange_amount, $activity_id, $award, $award_type){
        //插入兑换明细
        $sql = "INSERT INTO user_exchange_details (user_id, exchange_id, amount, activity_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($user_id, $exchange_id, $exchange_amount, $activity_id));
        $id = $this->ossDb->lastinsertid();
        if($id > 0){
            //调用发放奖励的接口
            $isSuccess = true;
            if(!empty($award) && $award_type == ActivityAwardType::$VIRTUAL_GOODS){
                $isSuccess = $this->ci->get("remoteApiService")->addAwards($user_id, $award);
            }
            if($isSuccess){
                try {
                    $this->ossDb->beginTransaction();
                    //标记兑换记录为已完成
                    $sql = "UPDATE user_exchange_details SET status = 1 WHERE id = ? AND status = 0";
                    $stmt = $this->ossDb->prepare($sql);
                    $stmt->execute(array($id));

                    //更新用户碎片数量
                    $sql = "UPDATE user_fragments SET fragment_amount = fragment_amount - ? WHERE activity_id = ? AND user_id = ? AND fragment_id = ?";
                    $stmt = $this->ossDb->prepare($sql);
                    $stmt->execute(array($fragment_amount, $activity_id, $user_id, $fragment_id));
                    $this->ossDb->commit();

                    return true;
                } catch (\Exception $e) {
                    $this->ossDb->rollBack();
                }
            }
        }
        return false;
    }

    /**
     * 获取活动兑换奖励
     * @param int $user_id 如果$user_id>0，获取用户兑换的信息；否则只包含兑换基础配置信息
     * @return array|string
     */
    public function getExchangeAwards($user_id = 0){
        $activity_id = $this->getActivityId();
        if(!empty($this->exchanges)){
            return $this->exchanges;
        }
        $sql = "SELECT a.*, e.id AS exchange_id, e.fragment_id, e.fragment_amount, e.exchange_amount, e.max_exchange_amount, e.max_exchange_times FROM activity_award_configs AS a INNER JOIN activity_exchange_configs AS e ON a.id = e.award_id WHERE a.activity_id = ? ORDER BY e.order";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //获取活动已兑换的奖励数量
        $exchangedAmounts = $this->getExchangedAmount();
        //获取用户兑奖信息
        $userExchanges = array();
        $gender = -1;
        if($user_id > 0){
            $user = $this->ci->get("userService")->getUserInfoById($user_id);
            $gender = intval($user["gender"]);
            $userExchanges = $this->getUserExchangeAwards($user_id);
        }
        $exchanges = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                $exchange = new ActivityExchangeAward($row, $gender);
                if($exchange->max_exchange_amount > 0 && !empty($exchangedAmounts) && isset($exchangedAmounts[$exchange->exchange_id])){
                    $available_exchange_amount = $exchange->max_exchange_amount - $exchangedAmounts[$exchange->exchange_id];
                    $exchange->available_exchange_amount = $available_exchange_amount >= 0 ? $available_exchange_amount : 0;
                }
                if($exchange->max_exchange_times > 0 && !empty($userExchanges) && isset($userExchanges[$exchange->exchange_id])){
                    $available_exchange_times = $exchange->max_exchange_times - $userExchanges[$exchange->exchange_id];
                    $exchange->available_exchange_times = $available_exchange_times >= 0 ? $available_exchange_times : 0;
                }
                $exchanges[] = $exchange;
            }
        }
        $this->exchanges = $exchanges;
        return $this->exchanges;
    }

    /**
     * 获取活动当天已兑换的奖励数量
     * @return array
     */
    public function getExchangedAmount(){
        $activity_id = $this->getActivityId();
        $from = date("Y-m-d 00:00:00");
        $to = date("Y-m-d 23:59:59");
        $sql = "SELECT exchange_id, SUM(amount) AS exchanged_amount FROM user_exchange_details WHERE activity_id = ? AND created_at >= ? AND created_at <= ? AND status = 1 GROUP BY exchange_id";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id, $from, $to));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $exchangedAmounts = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                $exchange_id = intval($row["exchange_id"]);
                $exchanged_amount = intval($row["exchanged_amount"]);
                $exchangedAmounts[$exchange_id] = $exchanged_amount;
            }
        }
        return $exchangedAmounts;
    }

    /**
     * 获取活动当天，某个奖励已兑换的数量
     * @param $exchange_id
     * @return array
     */
    public function getExchangedAmountById($exchange_id){
        $activity_id = $this->getActivityId();
        $from = date("Y-m-d 00:00:00");
        $to = date("Y-m-d 23:59:59");
        $sql = "SELECT SUM(amount) AS exchanged_amount FROM user_exchange_details WHERE activity_id = ? AND exchange_id = ? AND created_at <= ? AND created_at > ? AND status = 1";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id, $exchange_id, $from, $to));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(!empty($row)){
            return intval($row['exchanged_amount']);
        }
        return 0;
    }

    /**
     * 获取用户兑奖信息
     * @param $user_id
     */
    public function getUserExchangeAwards($user_id){
        $activity_id = $this->getActivityId();
        $sql = "SELECT exchange_id, COUNT(*) AS exchange_times FROM user_exchange_details WHERE user_id = ? AND activity_id = ? AND status = 1 GROUP BY exchange_id";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($user_id, $activity_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $userExchanges = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                $exchange_id = intval($row["exchange_id"]);
                $exchange_times = intval($row["exchange_times"]);
                $userExchanges[$exchange_id] = $exchange_times;
            }
        }
        return $userExchanges;
    }

    /**
     * 获取用户某个奖品已兑奖的次数
     * @param $user_id
     * @param $exchange_id
     */
    public function getUserExchangeTimesById($user_id, $exchange_id){
        $activity_id = $this->getActivityId();
        $sql = "SELECT COUNT(*) AS exchange_times FROM user_exchange_details WHERE user_id = ? AND activity_id = ?  AND status = 1 AND exchange_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($user_id, $activity_id, $exchange_id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(!empty($row)){
            return intval($row['exchange_times']);
        }
        return 0;
    }

}