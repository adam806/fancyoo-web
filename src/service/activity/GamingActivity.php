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

class GamingActivity extends FragmentActivity {
    //集碎片奖励列表
    public $gamingList = '';

    public function participateGaming($user_id, $gaming_id, $fragment_amount){
        $activity_id = $this->getActivityId();
        $fragment_id = $this->getFragmentId();

        //检查碎片是否已集满
        $isFull = $this->isGamingFragmentFull($gaming_id);
        if($isFull){
            throw new CustomException(ErrorCode::ACTIVITY_GAMING_FULL);
        }
        //检查碎片是否够
        $userFragmentsAmount = $this->getUserFragmentsAmount($user_id);
        if($userFragmentsAmount < $fragment_amount){
            throw new CustomException(ErrorCode::ACTIVITY_EXCHANGE_FRAGMENT_NOT_ENOUGH);
        }
        try {
            //记录明细
            $this->ossDb->beginTransaction();
            $sql = "INSERT INTO user_gaming_details (user_id, activity_id, gaming_id, fragment_id, fragment_amount) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->ossDb->prepare($sql);
            $stmt->execute(array($user_id, $activity_id, $gaming_id, $fragment_id, $fragment_amount));

            //更新参与碎片数量
            $sql = "UPDATE activity_gaming_configs SET participant_fragment_amount = participant_fragment_amount + ? WHERE id = ?";
            $stmt = $this->ossDb->prepare($sql);
            $stmt->execute(array($fragment_amount, $gaming_id));

            //更新用户碎片数量
            $sql = "UPDATE user_fragments SET fragment_amount = fragment_amount - ? WHERE activity_id = ? AND user_id = ? AND fragment_id = ?";
            $stmt = $this->ossDb->prepare($sql);
            $stmt->execute(array($fragment_amount, $activity_id, $user_id, $fragment_id));
            $this->ossDb->commit();
            return true;
        }catch (\Exception $e) {
            $this->ossDb->rollBack();
            return false;
        }
    }

    public function isGamingFragmentFull($gaming_id){
        $sql = "SELECT * FROM activity_gaming_configs WHERE id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($gaming_id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(empty($row)){
            throw new CustomException(ErrorCode::INVALID_ARGUMENT);
        }
        if($row["participant_fragment_amount"] >= $row["total_fragment_amount"]){
            return true;
        }
        return false;
    }

    public function getGamingList($user_id = 0){
        $activity_id = $this->getActivityId();
        if(!empty($this->gamingList)){
            return $this->gamingList;
        }
        $sql = "SELECT g.id AS gaming_id, g.total_fragment_amount, g.participant_fragment_amount, a.name AS award_name, a.icon AS award_icon, a.description AS award_description 
                FROM activity_gaming_configs AS g INNER JOIN activity_award_configs AS a ON a.id = g.award_id 
                WHERE g.activity_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $gamingList = array();
        if(!empty($rows)){
            $userGamingAmounts = array();
            if($user_id > 0){
                $userGamingAmounts = $this->getUserGamingAmounts($user_id);
            }
            foreach ($rows as $row){
                $item = array();
                $item["total_fragment_amount"] = intval($row["total_fragment_amount"]);
                $item["participant_fragment_amount"] = intval($row["participant_fragment_amount"]);
                $item["remain_fragment_amount"] = $item["total_fragment_amount"] - $item["participant_fragment_amount"];
                $item["award_name"] = $row["award_name"];
                $item["award_icon"] = $row["award_icon"];
                $item["award_description"] = !empty($row["award_description"]) ? $row["award_description"] : "";
                $gaming_id = intval($row["gaming_id"]);
                $item["gaming_id"] = $gaming_id;
                $amount = 0;
                if(!empty($userGamingAmounts) && isset($userGamingAmounts[$gaming_id])){
                    $amount = $userGamingAmounts[$gaming_id];
                }
                $item["user_participant_fragment_amount"] = intval($amount);
                $gamingList[] = $item;
            }
        }
        $this->gamingList = $gamingList;
        return $gamingList;
    }

    public function getUserGamingAmounts($user_id){
        $activity_id = $this->getActivityId();
        $sql = "SELECT gaming_id, SUM(fragment_amount) AS amount FROM user_gaming_details WHERE activity_id = ? AND user_id = ? GROUP BY gaming_id";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id, $user_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $gamingAmounts = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                $gamingAmounts[$row["gaming_id"]] = intval($row["amount"]);
            }
        }
        return $gamingAmounts;
    }

    public function getHistoryGamingWinners(){
        $activity_id = $this->getActivityId();
        $sql = "SELECT a.name AS award_name, a.icon AS award_icon, a.description AS award_description, w.user_id FROM activity_gaming_winners AS w INNER JOIN activity_gaming_configs AS g ON w.gaming_id = g.id INNER JOIN activity_award_configs AS a ON a.id = g.award_id WHERE w.activity_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $winners = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                $winner = array();
                $winner["award_name"] = $row["award_name"];
                $winner["award_icon"] = $row["award_icon"];
                $winner["award_description"] = !empty($row["award_description"]) ? $row["award_description"] : "";
                $user = $this->ci->get("userService")->getUserInfoById($row["user_id"]);
                $winner["winner"] = $user;
                $winners[] = $winner;
            }
        }
        return $winners;
    }

    public function generateGamingWinners(){
        $activity_id = $this->getActivityId();
        $fragment_id = $this->getFragmentId();
        //先检查是否到开奖时间
        $now = time();
        if($now < strtotime($this->exchange_deadline)){
            return;
        }
        //接着检查是否已经开过奖
        $sql = "SELECT * FROM activity_gaming_winners WHERE activity_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($rows) && count($rows) > 0){
           return;
        }
        //获取一元夺宝的配置
        $sql = "SELECT * FROM activity_gaming_configs WHERE activity_id = ? AND fragment_id = ?";
        $stmt = $this->ossDb->prepare($sql);
        $stmt->execute(array($activity_id, $fragment_id));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $winners = array();
        if(!empty($rows)){
            foreach ($rows as $row){
                $gaming_id = $row["id"];
                $total_fragment_amount = $row["total_fragment_amount"];
                $participant_fragment_amount = $row["participant_fragment_amount"];
                //参与购买的碎片数没有达到该奖励需要的碎片数，则该奖励不会有任何人能获得
                if($participant_fragment_amount < $total_fragment_amount){
                    continue;
                }
                //获取参与的用户
                $sql = "SELECT user_id, SUM(fragment_amount) AS amount FROM user_gaming_details WHERE activity_id = ? AND fragment_id = ? AND gaming_id = ? GROUP BY user_id ORDER BY amount DESC";
                $stmt = $this->ossDb->prepare($sql);
                $stmt->execute(array($activity_id, $fragment_id, $gaming_id));
                $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $winner = $this->chooseWinner($users, $participant_fragment_amount);
                if($winner > 0){
                    //存储获奖用户信息
                    $sql = "INSERT INTO activity_gaming_winners (user_id, gaming_id, activity_id) VALUES (?, ?, ?)";
                    $stmt = $this->ossDb->prepare($sql);
                    $stmt->execute(array($winner, $gaming_id, $activity_id));
                    $winners[] = $winner;
                }
            }
        }
        return $winners;
    }

    private function chooseWinner($users, $participant_fragment_amount){
        if(empty($users)){
            return null;
        }
        $winner = null;
        $richUser = null;
        $maxAmount = 0;
        foreach ($users as $user){
            $user_id = $user["user_id"];
            $amount = $user["amount"];
            if($amount > $maxAmount){
                $maxAmount = $amount;
                $richUser = $user_id;
            }
            //玩家购买的碎片占总数的比例
            $ratio = $amount / $participant_fragment_amount;
            //获得该奖品的概率
            $probability = atan($ratio)/pi() * 4;
            //中奖
            if($probability >= rand(0, 1)){
                $winner = $user_id;
                break;
            }
        }
        //如果实际运算中出现了奖励无人获得的情况，则将该奖励交给花费碎片最多的一个玩家
        if(empty($winner)){
            $winner = $richUser;
        }
        return $winner;
    }

}