<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/9/21
 * Time: 上午11:18
 */

namespace Fancyoo\Exception;

class CustomHandler {
    public function __invoke($request, $response, $exception) {
        if ($exception instanceof CustomException) {
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(array("code" => $exception->getCode(), "message" => $exception->getMessage())));
        }else{
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/html')
                ->write(json_encode(array("code" => $exception->getCode(), "message" => 'Server Internal Error! ' . $exception->getMessage())));
        }
    }
}