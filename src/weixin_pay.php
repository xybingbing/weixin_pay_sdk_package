<?php
namespace xybingbing;
use xybingbing\pay;

//微信公众号支付
class weixin_pay extends pay{
    /*
    * 微信公众号统一下单 (解释：覆盖父类的方法：$notify_url是字符串, $notify_url=array() 这样写是为了不报错,因为父类的统一下单第5个参数是数组。）
    * @param String(32)  $openid  付款人openid
    * @param String(32)  $orderid 订单ID
    * @param String(128) $body 	  商品描述
    * @param int(1.00) 	 $total_fee 	  价格（元）
    * @param String(256) $notify_url  回调URL地址(支付成功后微信会把支付结果推送到这个地址)
    * @param  $atta=array(	根据需要传入需要的字段
                'device_info'=>'WEB', //String(32), 终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传"WEB"
                'detail'=>'',		//String(8192)，商品详情明细
                'attach'=>'',		//String(127), 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
                'fee_type'=>'CNY',	//String(16), 符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_2
                'time_start'=>'',	//String(14), 订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
                'time_expire'=>'',	//String(14), 订单失效时间(到这个时间用户未支付，该订单自动失效)，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010 注意：最短失效时间间隔必须大于5分钟
                'goods_tag'=>'',	//String(32), 商品标记，代金券或立减优惠功能的参数
                'product_id'=>'',	//String(32), 商品ID，商户自行定义。
                'limit_pay'=>'',	//String(32), no_credit--指定不能使用信用卡支付
    * 		）
    * @return 支付所需要的字段 ( 注：$atta['trade_type']＝NATIVE 只返回 扫描的支付链接。可以用这个生成二维码手机扫描就可以支付了。 )
    */
    public function unifiedorder($openid,$orderid,$body,$total_fee,$notify_url=array(),$atta=array()){
        $atta=array(
            'device_info'=>'WEB',
            'trade_type'=>'JSAPI',
            'openid'=>$openid,
        );
        $prepay=parent::unifiedorder($orderid,$body,$total_fee,$notify_url,$atta);
        return $this->createPayParams($prepay['prepay_id']);
    }
    /*
    * 生成支付参数
    * @param String(64)  $prepay_id 微信生成的预支付回话标识
    * @return array
    */
    private function createPayParams($prepay_id){
        if (empty($prepay_id)){
            if(self::$IS_DEBUG){
                exit('统一下单prepay_id不存在');
            }else{
                self::log('统一下单prepay_id不存在');
            }
        }
        $params['appId']     = $this->appid;
        $params['timeStamp'] = (string)time();
        $params['nonceStr']  = self::getNonceStr();
        $params['package']   = 'prepay_id='.$prepay_id;
        $params['signType']  = 'MD5';
        $params['paySign']   = $this->get_sign($params);
        return $params;
    }
}
