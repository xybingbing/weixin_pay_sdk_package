<?php
/* 微信公众号支付相关 */

require "../vendor/autoload.php";

$parameter=array(
	'appid'=>'', //微信ID
    'appsecret'=>'', //微信密钥
    'mch_id'=>'', //微信商户ID
    'paykey'=>'', //微信商户密钥
	'debug'=>true, //是否开启调试模式。关闭调试模式后 报错 会输出到weixin_log.txt
);

$wxpay=new xybingbing\weixin_pay($parameter);
//统一下单  
//$re=$wxpay->unifiedorder('oY8X0s9aYANqYI3ETXPnlKYcDS4o','20150702100','测试公众号下单','0.01','http://xxxx.com/wap/notify/wx_notify');
//print_r($re);
//查询订单
//$re=$wxpay->orderquery(array('out_trade_no'=>'20150702101'));
//print_r($re);
//关闭订单
//$re=$wxpay->closeorder(array('out_trade_no'=>'20150702101'));
//print_r($re);
//申请退款
//$re=$wxpay->refund('20150701201','0.01','0.01',array('out_trade_no'=>'20150702100'));
//print_r($re);
//查询退款
//$re=$wxpay->refundquery(array('out_trade_no'=>'20150702100'));
//print_r($re);
//微信对账单
//$re=$wxpay->downloadbill('20160705');
//print_r($re);
?>

<!--调用微信支付的时候一定要把但前页面的URL路径 设置到 微信公众号－>微信支付－>开发配置的 支付授权目录-->
<!--
<script type="text/javascript">
document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
	WeixinJSBridge.invoke('getBrandWCPayRequest', {
		'appId' : '<?php echo $re['appId'];?>',
		'timeStamp': '<?php echo $re['timeStamp'];?>',
		'nonceStr' : '<?php echo $re['nonceStr'];?>',
		'package' : '<?php echo $re['package'];?>',
		'signType' : '<?php echo $re['signType'];?>',
		'paySign' : '<?php echo $re['paySign'];?>'
	}, function(res) {
		if(res.err_msg == 'get_brand_wcpay_request:ok') {
			alert('微信支付成功!');
		}else{
			alert('微信支付失败!');
		}
	});
}, false);
</script>
