<?php
/* 微信代金券相关 */

require "../vendor/autoload.php";

$parameter=array(
	'appid'=>'', //微信ID
    'appsecret'=>'', //微信密钥
    'mch_id'=>'', //微信商户ID
    'paykey'=>'', //微信商户密钥
	'debug'=>true, //是否开启调试模式。关闭调试模式后 报错 会输出到weixin_log.txt
);
$wxpay=new xybingbing\weixin_coupon($parameter);
//发放代金券
//$re=$wxpay->send_coupon('oY8X0s9aYANqYI3ETXPnlKYcDS4o','483017','2015071200');
//print_r($re);
//查询代金券批次信息
//$re=$wxpay->query_coupon_stock('483017');
//print_r($re);
//查询代金券批次信息
//$re=$wxpay->querycouponsinfo('483017','oY8X0s9aYANqYI3ETXPnlKYcDS4o', '609647121');
//print_r($re);