<?php
/* 微信红包相关 */

require "../vendor/autoload.php";

$parameter=array(
	'appid'=>'', //微信ID
    'appsecret'=>'', //微信密钥
    'mch_id'=>'', //微信商户ID
    'paykey'=>'', //微信商户密钥
	'debug'=>true, //是否开启调试模式。关闭调试模式后 报错 会输出到weixin_log.txt
);
$wxpay=new xybingbing\weixin_bonus($parameter);
//发放红包
//$hb=array(
//	'send_name'=>'我发的',
//	'wishing'=>'恭喜得到红包', 
//	'act_name'=>'测试红包',
//	'remark'=>'无'
//);
//$re=$wxpay->sendredpack('oY8X0s9aYANqYI3ETXPnlKYcDS4o','20150708603',1,$hb);
//print_r($re);
//发放裂变红包
//$hb=array(
//	'send_name'=>'我发的',
//	'total_num'=>3,
//	'wishing'=>'恭喜得到红包', 
//	'act_name'=>'测试红包',
//	'remark'=>'无'
//);
//$re=$wxpay->sendgroupredpack('oY8X0s9aYANqYI3ETXPnlKYcDS4o','20150708601',3,$hb);
//print_r($re);
//查询红包
//$re=$wxpay->gethbinfo('20150708600');
//print_r($re);