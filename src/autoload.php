<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/20
 * Time: 下午4:19
 */

spl_autoload_register(function ($class) {
    $file = __DIR__ . "/";
    $arr = explode("\\", $class);
    for ($i = 0; $i < count($arr); $i++){
        if($arr[$i] == "Fancyoo"){
            continue;
        }
        if($i == count($arr) - 1){
            $file .= $arr[$i] . ".php";
        }else {
            $file .= strtolower($arr[$i]) . "/";
        }
    }
    if (file_exists($file)) {
        require_once $file;
    }
});
