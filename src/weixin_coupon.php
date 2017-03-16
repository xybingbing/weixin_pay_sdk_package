<?php
namespace xybingbing;
use xybingbing\weixin_pay_sdk;

//微信优惠劵卡卷(创建优惠劵还没有提供接口)
class weixin_coupon extends weixin_pay_sdk{
    /*
    * 发放代金券
    * @param String	  	$openid  微信分配的公众账号ID（企业号corpid即为此appId）
    * @param String(32)	$stock_id  代金劵对应的批次号
    * @param String	$partnerid 商户单据号
    * @param  $atta=array(	  附加数据
                'op_user_id'=>,		@param  String(32), 否，操作员帐号, 默认为商户号 可在商户平台配置操作员对应的api权限
                'device_info'=>,	@param  String(32), 否，微信支付分配的终端设备号
                'version'=>,		@param  String(32), 否，协议版本
                'type'=>,			@param  String(32), 否，协议类型
    *		 );
    * @return array
    */
    public function send_coupon($openid,$stock_id,$partnerid){
        $options=array(
            'appid' => $this->appid,  //是，公众账号ID
            'mch_id'=> $this->mch_id, //是，商户号
            'nonce_str'	=> self::getNonceStr(), //是，随机字符串
            'coupon_stock_id'=> $stock_id,		//是，代金券批次id
            'openid_count'=> (!empty($atta['openid_count'])) ? $atta['openid_count'] : '1',		//是，openid记录数（目前支持num=1）
            'partner_trade_no'=> $partnerid, //商户此次发放凭据号（格式：商户id+日期+流水号），商户侧需保持唯一性
            'openid'=>$openid,	//是，发送给的的openid用户
            'op_user_id'=> (!empty($atta['op_user_id'])) ? $atta['op_user_id'] : $this->mch_id, 	//否， 操作员帐号, 默认为商户号 可在商户平台配置操作员对应的api权限
            'device_info'=> (!empty($atta['device_info'])) ? $atta['device_info'] : '',		//微信支付分配的终端设备号
            'version'=> (!empty($atta['version'])) ? $atta['version'] : '1.0',  //否，协议版本
            'type'=> (!empty($atta['type'])) ? $atta['type'] : 'XML',	//否，协议类型 【目前仅支持默认XML】
        );
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::SEND_COUPON_URL,$xmldata,true,5);
    }
    /*
    * 查询代金券批次信息
    * @param String(32)  $stock_id  代金劵对应的批次号
    * @param  $atta=array(   附加数据
                'op_user_id'=>,		@param  String(32), 否，操作员帐号, 默认为商户号 可在商户平台配置操作员对应的api权限
                'device_info'=>,	@param  String(32), 否，微信支付分配的终端设备号
                'version'=>,		@param  String(32), 否，协议版本
                'type'=>,			@param  String(32), 否，协议类型
    *		 );
    * @return array
    */
    public function query_coupon_stock($stock_id,$atta=array()){
        $options=array(
            'appid'=> $this->appid,  //是，公众账号ID
            'mch_id' => $this->mch_id, //是，商户号
            'nonce_str'	=> self::getNonceStr(), //是，随机字符串
            'coupon_stock_id'=> $stock_id,		//是，代金券批次id
            'op_user_id'=> (!empty($atta['op_user_id'])) ? $atta['op_user_id'] : $this->mch_id, 		//否， 操作员帐号, 默认为商户号 可在商户平台配置操作员对应的api权限
            'device_info'=> (!empty($atta['device_info'])) ? $atta['device_info'] : '',		//微信支付分配的终端设备号
            'version'=> (!empty($atta['version'])) ? $atta['version'] : '1.0',  //否，协议版本
            'type'=> (!empty($atta['type'])) ? $atta['type'] : 'XML',	//否，协议类型 【目前仅支持默认XML】
        );
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::QUERY_COUPON_STOCK_URL,$xmldata,false,5);
    }
    /*
    * 查询代金券信息
    * @param String(32)		$stock_id  代金劵对应的批次号
    * @param String	  $openid    收到这个代金券的用户openid
    * @param String	  $coupon_id 代金券id
    * @param  $atta=array(	  附加数据
                'op_user_id'=>,		@param  String(32), 否，操作员帐号, 默认为商户号 可在商户平台配置操作员对应的api权限
                'device_info'=>,	@param  String(32), 否，微信支付分配的终端设备号
                'version'=>,		@param  String(32), 否，协议版本
                'type'=>,			@param  String(32), 否，协议类型
    *		 );
    * @return array
    */
    public function querycouponsinfo($stock_id,$openid,$coupon_id,$atta=array()){
        $options=array(
            'appid'=> $this->appid,  //是，公众账号ID
            'mch_id' => $this->mch_id, //是，商户号
            'nonce_str'	=> self::getNonceStr(), //是，随机字符串
            'coupon_id'	=> $coupon_id,	//是，代金券id
            'openid'    => $openid,
            'stock_id'	=> $stock_id, 	//是，代金券批次id
            'op_user_id'=> (!empty($atta['op_user_id'])) ? $atta['op_user_id'] : $this->mch_id, 		//否， 操作员帐号, 默认为商户号 可在商户平台配置操作员对应的api权限
            'device_info'=> (!empty($atta['device_info'])) ? $atta['device_info'] : '',		//微信支付分配的终端设备号
            'version'=> (!empty($atta['version'])) ? $atta['version'] : '1.0',  //否，协议版本
            'type'=> (!empty($atta['type'])) ? $atta['type'] : 'XML',	//否，协议类型 【目前仅支持默认XML】
        );
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::QUERY_COUPON_INFO_URL,$xmldata,false,5);
    }
}
