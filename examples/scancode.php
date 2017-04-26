<?php
/* 扫码支付相关 */

require "../vendor/autoload.php";

$parameter=array(
	'appid'=>'', //微信ID
    'appsecret'=>'', //微信密钥
    'mch_id'=>'', //微信商户ID
    'paykey'=>'', //微信商户密钥
	'debug'=>true, //是否开启调试模式。关闭调试模式后 报错 会输出到weixin_log.txt
);

$wxpay=new xybingbing\weixin_scancode_pay($parameter);
//统一下单   
//$re=$wxpay->unifiedorder('20150703100','1001','测试扫码下单','0.01','http://xxxx.com/notify/wx_notify');
//$value = $re['code_url']; 	//二维码内容
//$Level = 'QR_ECLEVEL_L';		//容错级别
//$Size = 10;					//生成图片大小   
//PHPQRCode\QRcode::png($value, false, $Level, $Size);		//生成二维码

//查询订单
//$re=$wxpay->orderquery(array('out_trade_no'=>'20150703100'));
//print_r($re);
//关闭订单
//$re=$wxpay->closeorder(array('out_trade_no'=>'20150703100'));
//print_r($re);
//申请退款
//$re=$wxpay->refund('20150701301','0.01','0.01',array('out_trade_no'=>'20150703100'));
//print_r($re);
//查询退款
//$re=$wxpay->refundquery(array('out_trade_no'=>'20150703100'));
//print_r($re);
//微信对账单
//$re=$wxpay->downloadbill('20160705');
//print_r($re);