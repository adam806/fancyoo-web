<?php
/**
 * Created by PhpStorm.
 * User: xiaohua
 * Date: 2017/11/21
 * Time: 下午1:22
 */

$app->group('/service', function () {
    $this->get('/qrcode', function ($request, $response, $args) {
        $params = $request->getQueryParams();
        $text = $params['text'];
        $qrCode = new Endroid\QrCode\QrCode($text);
        $response->write($qrCode->writeString());
        return $response->withHeader('Content-type', $qrCode->getContentType());
    });

    $this->post('/qrcode', function ($request, $response, $args) {
        $params = $request->getParsedBody();
        $text = $params['text'];
        $qrCode = new Endroid\QrCode\QrCode($text);
        $response->write($qrCode->writeString());
        return $response->withHeader('Content-type', $qrCode->getContentType());
    });

    $this->post('/checkActivityEvent', '\Fancyoo\Controller\ActivityController:checkActivityEvent');

    $this->post('/checkActivityLogin', '\Fancyoo\Controller\ActivityController:checkActivityLogin');

    $this->get('/activities/scenes', '\Fancyoo\Controller\ActivityController:getEncounterScenesForActivity');
});
