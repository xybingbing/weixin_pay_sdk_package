<?php
namespace xybingbing;
use xybingbing\pay;

//微信刷卡支付
class weixin_pay_card extends pay{
    /*
    * 刷卡支付
    * @param String(32)  $orderid 商户订单ID
    * @param String(128) $body 	  商品描述
    * @param float(1.00) $total_fee 	 价格（元）
    * @param String(128) $auth_code 	 扫码支付授权码
    * @param  $atta=array(	附加数据：根据需要传入字段
                'device_info'=>,    @param  String(32),   终端设备号(商户自定义，如门店编号)
                'detail'     =>,    @param  String(8192), 商品详细列表，使用Json格式，传输签名前请务必使用CDATA标签将JSON文本串保护起来。goods_detail []：
                'attach'     =>,    @param  String(127),  附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据				└ goods_id String 必填 32 商品的编号
                'fee_type'	 =>,    @param  String(16), 符合ISO4217标准的三位字母代码，默认人民币：CNY												└ wxpay_goods_id String 可选 32 微信支付定义的统一商品编号
                'goods_tag'	 =>,    @param  String(32),	商品标记，代金券或立减优惠功能的参数，														└ goods_name String 必填 256 商品名称
                'limit_pay'	 =>,    @param  String(32), no_credit--指定不能使用信用卡支付															└ goods_num Int 必填 商品数量																																└ price Int 必填 商品单价，单位为分
    *		 );
    * @return array
    */
    public function micropay($orderid,$body,$total_fee,$auth_code,$atta=array()){
        $options=array(
            'appid'	   => $this->appid, //是，公众账号ID
            'mch_id'	   => $this->mch_id, //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'device_info'=> (!empty($atta['device_info'])) ? $atta['device_info'] : '', //否，终端设备号(商户自定义，如门店编号)
            'body'	=> $body, //是, 商品或支付单简要描述
            'detail'	=> (!empty($atta['detail'])) ? $atta['detail'] : '',	//否, 商品详细列表Json格式
            'attach'=> (!empty($atta['attach'])) ? $atta['attach'] : '',	//否, 附加数据
            'out_trade_no'=> $orderid, //是，商户系统内部的订单号,32个字符内。
            'total_fee'	  => ($total_fee*100), //是,订单总金额,把元转换为分。
            'fee_type'	  => (!empty($atta['fee_type'])) ? $atta['fee_type'] : 'CNY',	//否，符合ISO4217标准的三位字母代码，默认人民币：CNY。
            'spbill_create_ip' =>self::getIP(),  //是，客户端IP。
            'goods_tag'	  => (!empty($atta['goods_tag'])) ? $atta['goods_tag'] : 'CNY',	//否，商品标记，代金券或立减优惠功能的参数。
            'limit_pay'	  => (!empty($atta['limit_pay'])) ? $atta['limit_pay'] : '',	//否，no_credit--指定不能使用信用卡支付。
            'auth_code'	  => $auth_code,  //是，扫码支付授权码，设备读取用户微信中的条码或者二维码信息
        );
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::MICROPAY_URL,$xmldata,false,5);
    }
    /*
    * 撤销订单
    * @param $orderid=array(	订单ID（注： transaction_id、out_trade_no二选一 优先微信的订单号transaction_id）
             'transaction_id'=>,	@param  String(32) 微信的订单号，
             'out_trade_no'=>,		@param  String(32) 商户系统内部的订单号，
    * 		 )
    * @return array
    */
    public function reverse($orderid){
        if(!is_array($orderid) || count($orderid) <= 0){
            if(self::$IS_DEBUG){
                exit("撤销订单: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("撤销订单: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }
        }
        $options=array(
            'appid'	   => $this->appid, //是，公众账号ID
            'mch_id'   => $this->mch_id, //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
        );
        if(!empty($orderid['transaction_id'])){
            $options['transaction_id']=$orderid['transaction_id'];
        }else if(!empty($orderid['out_trade_no'])){
            $options['out_trade_no']=$orderid['out_trade_no'];
        }else{
            if(self::$IS_DEBUG){
                exit("撤销订单：微信的订单号或者商户系统内部的订单号必须传如一个, 如：array('transaction_id'=>'20150806125346') 或者  array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("撤销订单：微信的订单号或者商户系统内部的订单号必须传如一个, 如：array('transaction_id'=>'20150806125346') 或者  array('out_trade_no'=>'20150806125346')");
            }
        }
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::REVERSE_URL,$xmldata,true,5);
    }
    /*
    * 授权码查询OPENID接口
    * @param String(128)  $auth_code 扫码支付授权码，设备读取用户微信中的条码或者二维码信息
    * @return array
    */
    public function authcodetoopenid($auth_code){
        $options=array(
            'appid'	   => $this->appid, //是，公众账号ID
            'mch_id'   => $this->mch_id, //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'auth_code'=> $auth_code,
        );
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::AUTHCODE_OPENID_URL,$xmldata,false,5);
    }
}
