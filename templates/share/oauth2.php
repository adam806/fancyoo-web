<?php
if (isset($_GET['code'])) {
    $code = $_GET['code'];
} else {
    echo "NO CODE";
    $code = false;
}
if ($code) {
    $appid = wx1b995d5a13c1d4c4;
    $secret = a3f6dd159120feaa415a6b694a3884e6;
    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
    $html = file_get_contents($url);
    echo json_encode($html);

}

?>
