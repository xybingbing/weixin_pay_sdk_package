<?php
namespace xybingbing;
use xybingbing\weixin_pay_sdk;

//微信红包
class weixin_bonus extends weixin_pay_sdk{
    /*
    * 发送普通现金红包
    * @param String(32)  $openid  接受红包的用户用户在wxappid下的openid
    * @param String(32)  $orderid 商户订单号 （微信发送红包的订单ID）
    * @param float(1.00) $money   金额,单位元，保留2位小数
    * @param  $bonus_data=array(	  创建红包的数据
                'send_name'=>,	@param  String(32), 是，红包发送者名称，一般公司名称就可以了
                'wishing'=>,	@param  String(32), 是，红包祝福语
                'act_name'=>,	@param  String(32), 是，活动名称
                'remark'=>,		@param  String(32), 是，备注信息
    *		 );
    * @return array
    */
    public function sendredpack($openid,$orderid,$money,$bonus_data=array()){
        $options=array(
            'wxappid'=> $this->appid,  //是，公众账号ID
            'mch_id' => $this->mch_id, //是，商户号
            'nonce_str'	=> self::getNonceStr(), //是，随机字符串
            'mch_billno'=> $orderid,    //是，商户订单号（每个订单号必须唯一）组成：mch_id+yyyymmdd+10位一天内不能重复的数字。
            'send_name'=> (!empty($bonus_data['send_name'])) ? $bonus_data['send_name'] : '红包发送者名称',
            're_openid'=> $openid,
            'total_amount'=> ($money*100),
            'total_num'=> 1,
            'wishing'=> (!empty($bonus_data['wishing'])) ? $bonus_data['wishing'] : '红包祝福语',
            'client_ip'=> self::getIP(),
            'act_name'=> (!empty($bonus_data['act_name'])) ? $bonus_data['act_name'] : '活动名称',
            'remark'=> (!empty($bonus_data['remark'])) ? $bonus_data['remark'] : '备注信息',
        );
        $sign=self::get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::SEND_RED_PACK,$xmldata,true,5);
    }
    /*
    * 发送裂变现金红包
    * @param String(32)  $openid 接收红包的种子用户（首个用户）用户在wxappid下的openid
    * @param String(32)  $orderid 商户订单号 （微信发送红包的订单ID）
    * @param float(1.00) $money   金额,单位元，保留2位小数
    * @param  $bonus_data=array(	  创建红包的数据
                'send_name'=>,	@param  String(32), 是，红包发送者名称，一般公司名称就可以了
                'total_num'=>,	@param  String(32), 是，红包发放总人数，即总共有多少人可以领到该组红包（包括分享者）
                'amt_type'=>,	@param  String(32), 否，默认：ALL_RAND， 红包金额设置方式 ALL_RAND—全部随机,商户指定总金额和红包发放总人数，由微信支付随机计算出各红包金额
                'wishing'=>,	@param  String(32), 是，红包祝福语
                'act_name'=>,	@param  String(32), 是，活动名称
                'remark'=>,		@param  String(32), 是，备注信息
    *		 );
    * @return array
    */
    public function sendgroupredpack($openid,$orderid,$money,$bonus_data=array()){
        $options=array(
            'wxappid'=> $this->appid,  //是，公众账号ID
            'mch_id' => $this->mch_id, //是，商户号
            'nonce_str'	=> self::getNonceStr(), //是，随机字符串
            'mch_billno'=> $orderid,	//是，商户订单号（每个订单号必须唯一）组成：mch_id+yyyymmdd+10位一天内不能重复的数字。
            'send_name'=> (!empty($bonus_data['send_name'])) ? $bonus_data['send_name'] : '红包发送者名称',
            're_openid'=> $openid,
            'total_amount'=> ($money*100),
            'total_num'=> (!empty($bonus_data['total_num'])) ? $bonus_data['total_num'] : 1,
            'amt_type'=> (!empty($bonus_data['amt_type'])) ? $bonus_data['amt_type'] : 'ALL_RAND',
            'wishing'=> (!empty($bonus_data['wishing'])) ? $bonus_data['wishing'] : '红包祝福语',
            'act_name'=> (!empty($bonus_data['act_name'])) ? $bonus_data['act_name'] : '活动名称',
            'remark'=> (!empty($bonus_data['remark'])) ? $bonus_data['remark'] : '备注信息',
        );
        $sign=self::get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::SEND_GROUP_RED_PACK,$xmldata,true,5);
    }
    /*
    * 查询红包的具体信息
    * @param String(32)  $orderid 商户发放红包的商户订单号
    * @param	 $atta=array(	附加数据：根据需要传入字段
                'bill_type'=>,		@param  String(32), 是，默认：MCHT:通过商户订单号获取红包信息。
    *		 );
    * @return array
    */
    public function gethbinfo($orderid,$atta=array()){
        $options=array(
            'appid'=> $this->appid,  //是，公众账号ID
            'mch_id' => $this->mch_id, //是，商户号
            'nonce_str'	=> self::getNonceStr(), //是，随机字符串
            'mch_billno'=> $orderid,
            'bill_type'	=> (!empty($atta['bill_type'])) ? $atta['bill_type'] : 'MCHT',
        );
        $sign=self::get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::QUERY_RED_PACK_INFO,$xmldata,true,5);
    }
}
