<?php
namespace xybingbing;
use xybingbing\weixin_pay_sdk;

//公共支付类
class pay extends weixin_pay_sdk{

    //统一下单
    public function unifiedorder($orderid,$body,$total_fee,$notify_url,$atta=array()){
        $options=array(
            'appid'  => $this->appid,   //是，公众账号ID
            'mch_id' => $this->mch_id,  //是，商户号
            'nonce_str'=> self::getNonceStr(),  //是，随机字符串
            'device_info' => (!empty($atta['device_info'])) ? $atta['device_info'] : '',    //否, 终端设备号(门店号或收银设备ID).
            'body' => $body,    //是，商品描述
            'detail' => (!empty($atta['detail'])) ? $atta['detail'] : '',   //否，商品详细列表，使用Json格式，传输签名前请务必使用CDATA标签将JSON文本串保护起来。
            'attach' =>	(!empty($atta['attach'])) ? $atta['attach'] : '',   //否，附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据。
            'out_trade_no' => $orderid, //是，商户系统内部的订单号,32个字符内、可包含字母。
            'fee_type' => (!empty($atta['fee_type'])) ? $atta['fee_type'] : 'CNY',  //否，符合ISO 4217标准的三位字母代码，默认人民币：CNY。
            'total_fee' => ($total_fee*100),    //是，订单总金额，单位为元，最多2位小数。
            'spbill_create_ip'=> self::getIP(), //是，APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
            'time_start' => (!empty($atta['time_start'])) ? $atta['time_start'] : '',
            'time_expire'=> (!empty($atta['time_expire'])) ? $atta['time_expire'] : '', //否，交易结束时间
            'goods_tag'=> (!empty($atta['goods_tag'])) ? $atta['goods_tag'] : '',   //否，商品标记
            'notify_url'=> $notify_url, //接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            'trade_type'=> (!empty($atta['trade_type'])) ? $atta['trade_type'] : 'JSAPI',   //是，交易类型，取值如下：JSAPI（js），NATIVE(扫码)，APP，
            'product_id'=> (!empty($atta['product_id'])) ? $atta['product_id'] : '',
            'limit_pay'=> (!empty($atta['limit_pay'])) ? $atta['limit_pay'] : '',   //否，指定支付方式,no_credit--指定不能使用信用卡支付
            'openid'=> (!empty($atta['openid'])) ? $atta['openid'] : '',    //是，用户标识
        );
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }    //删除为空的参数
        $sign=$this->get_sign($options);    //获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::UNIFIEDORDER_URL,$xmldata,false,5);
    }
    /*
    * 查询订单
    * @param $orderid=array(    订单ID（注： transaction_id、out_trade_no二选一 优先微信的订单号transaction_id）
             'transaction_id'=>,    @param  String(32) 微信的订单号，
             'out_trade_no'=>,      @param  String(32) 商户系统内部的订单号，
    * 		 )
    * @return array
    */
    public function orderquery($orderid){
        if(!is_array($orderid) || count($orderid) <= 0){
            if(self::$IS_DEBUG){
                exit("查询订单: 请传入数组。如：array('transaction_id'=>'20150806125346')");
            }else{
                self::log("查询订单: 请传入数组。如：array('transaction_id'=>'20150806125346')");
            }
        }
        $options=array(
            'appid'     => $this->appid, //是，公众账号ID
            'mch_id'    => $this->mch_id, //是，商户号
            'nonce_str' => self::getNonceStr(), //是，随机字符串
        );
        if(!empty($orderid['transaction_id'])){
            $options['transaction_id']=$orderid['transaction_id'];
        }else if(!empty($orderid['out_trade_no'])){
            $options['out_trade_no']=$orderid['out_trade_no'];
        }else{
            if(self::$IS_DEBUG){
                exit("微信的订单号或者商户系统内部的订单号必须传如一个, 如：array('transaction_id'=>'20150806125346') 或者  array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("微信的订单号或者商户系统内部的订单号必须传如一个, 如：array('transaction_id'=>'20150806125346') 或者  array('out_trade_no'=>'20150806125346')");
            }
        }
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::ORDER_QUERY_URL,$xmldata,false,5);
    }
    /*
    * 关闭订单
    * @param $orderid=array(        商户系统内部的订单号
                 'out_trade_no'=>,      @param  String(32) 商户系统内部的订单号，
    * 		 )
    * @return array
    */
    public function closeorder($orderid){
        if(!is_array($orderid) || count($orderid) <= 0){
            if(self::$IS_DEBUG){
                exit("关闭订单: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("关闭订单: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }
        }
        $options=array(
            'appid'     => $this->appid,    //是，公众账号ID
            'mch_id'    => $this->mch_id,   //是，商户号
            'nonce_str' => self::getNonceStr(), //是，随机字符串
        );
        if(!empty($orderid['out_trade_no'])){
            $options['out_trade_no']=$orderid['out_trade_no'];
        }else{
            if(self::$IS_DEBUG){
                exit("关闭订单： 要传入商户系统内部的订单号, 如： array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("关闭订单： 要传入商户系统内部的订单号, 如：  array('out_trade_no'=>'20150806125346')");
            }
        }
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);    //获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::CLOSE_ORDER_URL,$xmldata,false,5);
    }
    /*
    * 申请退款
    * @param String  $refund_id  商户系统内部的退款单号
    * @param float(1.00) $total_fee   订单总金额，单位为元，最多2位小数。
    * @param float(1.00) $refund_fee  退款总金额，单位为元，最多2位小数。
    * @param $orderid=array(    订单ID（注： transaction_id、out_trade_no二选一 优先微信的订单号transaction_id）
             'transaction_id'=>,	@param  String(32) 微信的订单号，
             'out_trade_no'=>,		@param  String(32) 商户系统内部的订单号，
    * 		 )
    * @return array
    */
    public function refund($refund_id,$total_fee,$refund_fee,$orderid,$atta=array()){
        if(!is_array($orderid) || count($orderid) <= 0){
            if(self::$IS_DEBUG){
                exit("申请退款: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("申请退款: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }
        }
        $options=array(
            'appid'	   => $this->appid,    //是，公众账号ID
            'mch_id'   => $this->mch_id,   //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'device_info' => (!empty($atta['device_info'])) ? $atta['device_info'] : '',    //否，终端设备号(商户自定义)
            'out_refund_no'=> $refund_id,   //是，商户系统内部的退款单号，商户系统内部唯一，同一退款单号多次请求只退一笔
            'total_fee' => ($total_fee*100),    //是， 订单总金额，单位为元，最多2位小数。
            'refund_fee' => ($refund_fee*100),  //是，退款总金额，订单总金额，单位为元，最多2位小数。
            'refund_fee_type' => (!empty($atta['refund_fee_type'])) ? $atta['refund_fee_type'] : 'CNY', //否，货币类型，符合ISO 4217标准的三位字母代码，默认人民币：CNY
            'op_user_id' => (!empty($atta['op_user_id'])) ? $atta['op_user_id'] : $this->mch_id,    //是，操作员帐号, 默认为商户号
        );
        if(!empty($orderid['transaction_id'])){
            $options['transaction_id']=$orderid['transaction_id'];
        }else if(!empty($orderid['out_trade_no'])){
            $options['out_trade_no']=$orderid['out_trade_no'];
        }else{
            if(self::$IS_DEBUG){
                exit("申请退款：微信的订单号或者商户系统内部的订单号必须传如一个, 如：array('transaction_id'=>'20150806125346') 或者  array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("申请退款：微信的订单号或者商户系统内部的订单号必须传如一个, 如：array('transaction_id'=>'20150806125346') 或者  array('out_trade_no'=>'20150806125346')");
            }
        }
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::REFUND_URL,$xmldata,true,5);
    }
    /*
    * 查询退款
    * @param $orderid=array(    订单ID（注： transaction_id、out_trade_no、out_refund_no、refund_id四选一 优先微信的订单号transaction_id）
                 'transaction_id'=>,    @param  String(32) 微信的订单号，
                 'out_trade_no'=>,	    @param  String(32) 商户系统内部的订单号，
                 'out_refund_no'=>,	    @param  String(32) 商户退款单号
                 'refund_id'=>,		    @param  String(32) 微信退款单号
    * 		 )
    * @return array
    */
    public function refundquery($orderid,$atta=array()){
        if(!is_array($orderid) || count($orderid) <= 0){
            if(self::$IS_DEBUG){
                exit("查询退款: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }else{
                self::log("查询退款: 请传入数组。如：array('out_trade_no'=>'20150806125346')");
            }
        }
        $options=array(
            'appid'	   => $this->appid,     //是，公众账号ID
            'mch_id'   => $this->mch_id,    //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'device_info'=> (!empty($atta['device_info'])) ? $atta['device_info'] : '', //否，商户自定义的终端设备号，如门店编号、设备的ID等
        );

        if(!empty($orderid['transaction_id'])){
            $options['transaction_id']=$orderid['transaction_id'];  //微信生成的订单号
        }else if(!empty($orderid['out_trade_no'])){
            $options['out_trade_no']=$orderid['out_trade_no'];      //商户内部的订单号
        }else if(!empty($orderid['out_refund_no'])){
            $options['out_refund_no']=$orderid['out_refund_no'];    //商户退款单号
        }else if(!empty($orderid['refund_id'])){
            $options['refund_id']=$orderid['refund_id'];            //微信退款单号
        }else{
            if(self::$IS_DEBUG){
                exit("查询退款: 微信的订单号或商户系统内部的订单号或商户退款单号或微信退款单号必须传如一个 <br>如：array('transaction_id'=>'20150806125346') 或  array('out_trade_no'=>'20150806125346') 或  array('out_refund_no'=>'20150806125346')  或  array('refund_id'=>'20150806125346')");
            }else{
                self::log("查询退款: 微信的订单号或商户系统内部的订单号或商户退款单号或微信退款单号必须传如一个 <br>如：array('transaction_id'=>'20150806125346') 或  array('out_trade_no'=>'20150806125346') 或  array('out_refund_no'=>'20150806125346')  或  array('refund_id'=>'20150806125346')");
            }
        }
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::REFUND_QUERY_URL,$xmldata,false,5);
    }
    /*
    * 下载对账单
    * @param String(32)  $date 下载对账单的日期，格式：20140603
    * @param $atta=array(   订单ID（注： transaction_id、out_trade_no、out_refund_no、refund_id四选一 优先微信的订单号transaction_id）
                 'device_info'=>,	@param  String(32) 商户自定义的终端设备号，如门店编号、设备的ID等
                 'bill_type'=>,		@param  String(32) 返回当日所有订单信息，默认值 ALL. SUCCESS，返回当日成功支付的订单 REFUND，返回当日退款订单。
    * 		 )
    * @param Boolean  $is_download_excel 真：导出excel文档对账单， 假：输出对账单数组。
    * @return array / file
    */
    public function downloadbill($date,$atta=array(),$is_download_excel=false){
        $options=array(
            'appid'	   => $this->appid, //是，公众账号ID
            'mch_id'   => $this->mch_id, //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'device_info'=> (!empty($atta['device_info'])) ? $atta['device_info'] : '', //否，商户自定义的终端设备号，如门店编号、设备的ID等
            'bill_date'=> $date, //是，下载对账单的日期，格式：20140603
            'bill_type'=> (!empty($atta['bill_type'])) ? $atta['bill_type'] : 'ALL', //是，返回当日所有订单信息，默认值 ALL.  SUCCESS，返回当日成功支付的订单 REFUND，返回当日退款订单
        );
        foreach($options as $opk=>$opv){ if(empty($opv)){ unset($options[$opk]); } }	//删除为空的参数
        $sign=$this->get_sign($options);
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        $response = self::postXmlCurl(self::DOWNLOAD_BILL_URL,$xmldata,false,5);
        $data = explode("\n",$response);
        foreach ($data as $key => $value){
            $data_array[] = explode(",",$value);
        }
        if($is_download_excel){
            self::exportexcel($data_array,array(),'微信对账单_'.$date);
        }else{
            return $data_array;
        }
    }
    //转换短链接
    public function shorturl($url){
        $options=array(
            'appid'	   => $this->appid, //是，公众账号ID
            'mch_id'	   => $this->mch_id, //是，商户号
            'nonce_str'=> self::getNonceStr(), //是，随机字符串
            'long_url' => $url, //是，需要转换的URL，签名用原串，传输需URLencode
        );
        $sign=$this->get_sign($options);
        $options['sign']=$sign;
        $options['long_url']=urlencode($options['long_url']);
        $xmldata=self::ArrayXml($options);
        $response = self::postXmlCurl(self::SHORT_URL,$xmldata,false,5);
    }
}
