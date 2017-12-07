<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/21
 * Time: 上午11:01
 */

namespace Fancyoo\Exception;

class CustomException extends \Exception
{
    public function __construct(array $errorCode){
        parent::__construct($errorCode["message"], $errorCode["code"]);
    }
}
