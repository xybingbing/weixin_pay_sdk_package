<?php
namespace xybingbing;
use xybingbing\weixin_pay_sdk;

//微信付款
class weixin_payment extends weixin_pay_sdk{
    /*
    * 企业付款
    * @param String(32)  $openid  商户appid下，某用户的openid
    * @param String(32)  $orderid 商户订单号，需保持唯一性
    * @param float(1.00) $amount  金额,单位元，保留2位小数
    * @param String      $desc 	  企业付款描述信息 （默认是：提现）
    * @param  $atta=array(	附加数据：根据需要传入字段
                'device_info'=>,	@param  String(32), 否，终端设备号(商户自定义)
                'check_name'=>,		@param  	String(127),	 是，校验用户姓名选项，默认 NO_CHECK：不校验真实姓名，NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账） OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
                're_user_name'=>,	@param  String(16), 可选，如果check_name设置为FORCE_CHECK或OPTION_CHECK，则必填用户真实姓名
    *		 );
    * @return array
    */
    public function transfers($openid,$orderid,$amount,$desc='提现',$atta=array()){
        $options=array(
            'mch_appid'  => $this->appid, //是，公众账号ID
            'mchid' => $this->mch_id, //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'device_info'=> (!empty($atta['device_info'])) ? $atta['device_info'] : '',//否，微信支付分配的终端设备号
            'partner_trade_no'=> $orderid, //是，商户订单号，需保持唯一性
            'openid'=>$openid,	//是，用户openid
            'check_name'=> (!empty($atta['check_name'])) ? $atta['check_name'] : 'NO_CHECK',	//是，校验用户姓名选项，NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账） OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
            're_user_name'=> (!empty($atta['re_user_name'])) ? $atta['re_user_name'] : '',	//否，收款用户真实姓名。 如果check_name设置为FORCE_CHECK或OPTION_CHECK，则必填用户真实姓名
            'amount'=>($amount*100), //是，企业付款金额，单位为元
            'desc'=>$desc,	//是，企业付款操作说明信息。必填。
            'spbill_create_ip'=> self::getIP(),
        );
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::TRANSFERS_URL,$xmldata,true,5);
    }
    /*
    * 查询企业付款
    * @param String(32)  $orderid 商户调用企业付款API时使用的商户订单号
    * @return array
    */
    public function gettransferinfo($orderid){
        $options=array(
            'appid'  => $this->appid, //是，公众账号ID
            'mch_id' => $this->mch_id, //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'partner_trade_no'=> $orderid, //是，商户订单号，需保持唯一性
        );
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::QUERY_TRANSFERS_URL,$xmldata,true,5);
    }
}
