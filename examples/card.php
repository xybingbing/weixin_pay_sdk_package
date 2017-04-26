<?php
/* 微信刷卡支付相关 */

require "../vendor/autoload.php";

$parameter=array(
	'appid'=>'', //微信ID
    'appsecret'=>'', //微信密钥
    'mch_id'=>'', //微信商户ID
    'paykey'=>'', //微信商户密钥
	'debug'=>true, //是否开启调试模式。关闭调试模式后 报错 会输出到weixin_log.txt
);
$wxpay=new xybingbing\weixin_pay_card($parameter);
//刷卡支付 
//$re=$wxpay->micropay('20150701100','测试刷卡','0.01','130097590535673883');
//print_r($re);
//查询订单
//$re=$wxpay->orderquery(array('out_trade_no'=>'20150701100'));
//print_r($re);
//撤销订单
//$re=$wxpay->reverse(array('out_trade_no'=>'20150701100'));
//print_r($re);
//申请退款
//$re=$wxpay->refund('20150701200','0.01','0.01',array('out_trade_no'=>'20150701101'));
//print_r($re);
//查询退款
//$re=$wxpay->refundquery(array('out_trade_no'=>'20150701101'));
//print_r($re);
//微信对账单
//$re=$wxpay->downloadbill('20160705');
//print_r($re);