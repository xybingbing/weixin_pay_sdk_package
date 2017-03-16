<?php
/**
 * Created by PhpStorm.
 * User: xybingbing
 * Date: 2017/3/15
 * Time: 下午8:11
 */
class weixin_pay_sdk{
    protected $appid;		//微信ID
    protected $appsecret;	//微信密钥
    protected $mch_id;		//微信商户ID
    protected $payKey;		//微信商户密钥
    public static $IS_DEBUG=false;	//是否开启调试模式(默认关闭)
    /*接口URL*/
    const MICROPAY_URL	= 'https://api.mch.weixin.qq.com/pay/micropay';         //微信刷卡支付
    const REVERSE_URL	= 'https://api.mch.weixin.qq.com/secapi/pay/reverse';   //撤销订单API
    const AUTHCODE_OPENID_URL='https://api.mch.weixin.qq.com/tools/authcodetoopenid';   //授权码查询OPENID接口
    const UNIFIEDORDER_URL	= 'https://api.mch.weixin.qq.com/pay/unifiedorder';         //统一下单
    const ORDER_QUERY_URL	= 'https://api.mch.weixin.qq.com/pay/orderquery';           //查询订单
    const CLOSE_ORDER_URL	= 'https://api.mch.weixin.qq.com/pay/closeorder';           //关闭订单
    const REFUND_URL		= 'https://api.mch.weixin.qq.com/secapi/pay/refund';        //申请退款
    const REFUND_QUERY_URL	= 'https://api.mch.weixin.qq.com/pay/refundquery';          //查询退款
    const DOWNLOAD_BILL_URL = 'https://api.mch.weixin.qq.com/pay/downloadbill';         //下载对账单
    const SEND_COUPON_URL 	= 'https://api.mch.weixin.qq.com/mmpaymkttransfers/send_coupon';    //发放代金劵
    const QUERY_COUPON_STOCK_URL= 'https://api.mch.weixin.qq.com/mmpaymkttransfers/query_coupon_stock'; //查询代金券批次信息
    const QUERY_COUPON_INFO_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/querycouponsinfo';   //查询代金券信息
    const SEND_RED_PACK         = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';        //发放普通红包
    const SEND_GROUP_RED_PACK   = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack';   //发裂变红包
    const QUERY_RED_PACK_INFO   = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';          //红包查询
    const TRANSFERS_URL		    = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';//企业付款接口
    const QUERY_TRANSFERS_URL	= 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';    //查询企业付款接口
    /*工具URL*/
    const SHORT_URL = 'https://api.mch.weixin.qq.com/tools/shorturl';       //转换短链接

    public function __construct($options = array()){
        //$options参数不存在，就当前目录找wx_config.php配置文件。
        if(empty($options)){
            if(is_file(dirname(__FILE__).'/wx_config.php')){
                $config=include(dirname(__FILE__).'/wx_config.php');
            }else{
                exit('wx_config.php配置文件不存在！，请直接传入微信配置参数。');
            }
        }
        $this->appid	= isset($options['appid'])	? $options['appid'] : $config['appid'];
        $this->appsecret= isset($options['appsecret'])	? $options['appsecret'] : $config['appsecret'];
        $this->mch_id	= isset($options['mch_id'])	? $options['mch_id'] : $config['mch_id'];
        $this->paykey	= isset($options['paykey'])	? $options['paykey'] : $config['paykey'];
        self::$IS_DEBUG = isset($options['debug'])	? $options['debug'] : $config['debug'];
    }
    /**
     * 签名
     */
    protected function get_sign($options){
        ksort($options);
        $string=self::ToUrlParams($options);
        $string=$string.'&key='.$this->paykey;
        $string = md5($string);
        $result = strtoupper($string);
        return $result;
    }
    /**
     * 格式化参数格式化成url参数
     */
    protected static function ToUrlParams($options){
        $buff = "";
        foreach ($options as $k => $v){
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
    /**
     * 数组解析成XML文档
     * @param  $array
     * @return xml
     */
    protected static function ArrayXml($options){
        if(!is_array($options) || count($options) <= 0){
            if(self::$IS_DEBUG){
                exit("传入的数组有问题，请检查。");
            }else{
                self::log('传入的数组有问题，请检查。');
            }
        }
        $xml = "<xml>";
        foreach ($options as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    /**
     * XML文档解析成数组，并将键值转成小写
     * @param  xml $xml
     * @return array
     */
    protected static function XmlArray($xml){
        $data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return array_change_key_case($data, CASE_LOWER);
    }
    /**
     * 产生随机字符串
     * @param int $length
     * @return 产生的随机字符串
     */
    protected static function getNonceStr($length = 32){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return 客户端IP地址
     */
    protected static function getIP($type = 0){
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if($ip!== NULL) return $ip[$type];
        $ip = $_SERVER['REMOTE_ADDR'];
        $long = sprintf("%u",ip2long($ip));	//IP地址合法验证
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $url  url
     * @param string $xml  需要post的xml数据
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @return array
     */
    protected static function postXmlCurl($url, $xml, $useCert = false, $second = 30){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);	//设置超时时间
        curl_setopt($ch, CURLOPT_URL, $url);
        if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);	//严格校验
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_HEADER, FALSE); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); //要求结果为字符串且输出到屏幕上
        if($useCert == true){
            $path=dirname(__FILE__).'/weixin_cert/';	 //设置当前路径证书
            if(!is_file($path.'apiclient_cert.pem')){
                if(self::$IS_DEBUG){
                    exit('cert证书文件不存在！');
                }else{
                    self::log('cert证书文件不存在！');
                }
            }
            if(!is_file($path.'apiclient_key.pem')){
                if(self::$IS_DEBUG){
                    exit('key证书文件不存在！');
                }else{
                    self::log('key证书文件不存在！');
                }
            }
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $path.'apiclient_cert.pem');
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $path.'apiclient_key.pem');
        }
        curl_setopt($ch, CURLOPT_POST, TRUE); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);	//提交的XML
        $data = curl_exec($ch); //运行curl
        if(!empty($data)){
            //判断返回的是不是XML文档
            if(substr($data,0,5)!="<xml>"){
                return $data;
            }else{
                $data = self::XmlArray($data);
                if(!empty($data)){
                    curl_close($ch);  //关闭连接
                    if($data['return_code']=='SUCCESS'){
                        if($data['result_code']=='SUCCESS'){
                            return $data;
                        }else{
                            if(self::$IS_DEBUG){
                                exit('错误码：'.$data['err_code'].'  错误描述：'.$data['err_code_des']);
                            }else{
                                return $data;
                                //self::log('错误码：'.$data['err_code'].'  错误描述：'.$data['err_code_des']);
                            }
                        }
                    }else{
                        if(self::$IS_DEBUG){
                            exit($data['return_msg']);
                        }else{
                            self::log($data['return_msg']);
                        }
                    }
                }else{
                    $error = curl_errno($ch);
                    $info  = curl_getinfo($ch);
                    curl_close($ch);
                    if(self::$IS_DEBUG){
                        exit($error.'->'.$info);
                    }else{
                        self::log($error.'->'.$info);
                    }
                }
            }
        }else{
            if(self::$IS_DEBUG){
                exit('未知错误，curl没有返回任何任何数据！');
            }else{
                self::log('未知错误，curl没有返回任何任何数据！');
            }
        }
    }
    //输出日志
    protected static function log($log){
        file_put_contents('weixin_log.txt','时间:'.date('Y-m-d H:i:s').PHP_EOL.'消息:'.$log.PHP_EOL.PHP_EOL.PHP_EOL,FILE_APPEND);
        exit;
    }
    /**
     * 导出数据为excel表格
     *@param $data    一个二维数组,结构如同从数据库查出来的数组
     *@param $title   excel的第一行标题,一个数组,如果为空则没有标题
     *@param $filename 下载的文件名
     *@examlpe
     *exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
     */
    protected static function exportexcel($data = array(), $title = array(), $filename = 'report'){
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        //导出xls 开始
        if (!empty($title)) {
            foreach ($title as $k => $v) {
                $encode = mb_detect_encoding($v, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
                if ($encode == "UTF-8"){
                    $title[$k] = iconv("UTF-8", "GB2312//TRANSLIT//IGNORE", $v);
                }
            }
            $title = implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                foreach ($val as $ck => $cv) {
                    $encode = mb_detect_encoding($cv, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
                    if ($encode == "UTF-8"){
                        $data[$key][$ck] = iconv("UTF-8", "GB2312//TRANSLIT//IGNORE", $cv);
                    }
                }
                $data[$key] = implode("\t", $data[$key]);
            }
            echo implode("\n", $data);
        }
    }
}
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

//扫码支付
class weixin_scancode_pay extends pay{
    /*
    * 扫码支付统一下单. (解释：覆盖父类的方法：$notify_url是字符串, $notify_url=array() 这样写是为了不报错,因为父类的统一下单第5个参数是数组。）
    * @param String(32)  $orderid 订单ID
    * @param String(32)  $itemid  商品ID
    * @param String(128) $body 	  商品描述
    * @param int(1.00) 	 $total_fee 	  价格（元）
    * @param String(256) $notify_url  回调URL地址(支付成功后微信会把支付结果推送到这个地址)
    * @param  $atta=array(	根据需要传入需要的字段
                'device_info'=>'WEB',//String(32), 终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传"WEB"
                'detail'=>'',		//String(8192)，商品详情明细
                'attach'=>'',		//String(127), 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
                'fee_type'=>'CNY',	//String(16), 符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_2
                'time_start'=>'',	//String(14), 订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
                'time_expire'=>'',	//String(14), 订单失效时间(到这个时间用户未支付，该订单自动失效)，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010 注意：最短失效时间间隔必须大于5分钟
                'goods_tag'=>'',	//String(32), 商品标记，代金券或立减优惠功能的参数
                'limit_pay'=>'',	//String(32), no_credit--指定不能使用信用卡支付
    * 		  ）
    * @return array 支付所需要的字段
    */
    public function unifiedorder($orderid,$itemid,$body,$total_fee,$notify_url=array(),$atta=array()){
        $atta=array(
            'device_info'=>'WEB',
            'trade_type'=>'NATIVE',
            'product_id'=>$itemid,
        );
        return parent::unifiedorder($orderid,$body,$total_fee,$notify_url,$atta);
    }
}

//APP支付
class weixin_app_pay extends pay{
    /*
    * APP统一下单
    * @param String(32)  $orderid 订单ID
    * @param String(128) $body 	  商品描述
    * @param int(1.00) 	 $total_fee 	  价格（元）
    * @param String(256) $notify_url 	  回调URL地址(支付成功后微信会把支付结果推送到这个地址)
    * @param  $atta=array(	根据需要传入需要的字段
                'device_info'=>'WEB', //String(32), 终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传"WEB"
                'detail'=>'',		//String(8192)，商品详情明细
                'attach'=>'',		//String(127), 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
                'fee_type'=>'CNY',	//String(16), 符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_2
                'time_start'=>'',	//String(14), 订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010
                'time_expire'=>'',	//String(14), 订单失效时间(到这个时间用户未支付，该订单自动失效)，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010 注意：最短失效时间间隔必须大于5分钟
                'goods_tag'=>'',	//String(32), 商品标记，代金券或立减优惠功能的参数
                'limit_pay'=>'',	//String(32), no_credit--指定不能使用信用卡支付
    * 		）
    * @return array 支付所需要的字段
    */
    public function unifiedorder($orderid,$body,$total_fee,$notify_url,$atta=array()){
        $atta=array(
            'device_info'=>'WEB',
            'trade_type'=>'APP',
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
                exit('APP支付: 统一下单prepay_id不存在');
            }else{
                self::log('APP支付: 统一下单prepay_id不存在');
            }
        }
        $params['appid']    = $this->appid;
        $params['partnerid']= $this->mch_id;
        $params['prepayid'] = $prepay_id;
        $params['package']  = 'Sign=WXPay';
        $params['noncestr'] = self::getNonceStr();
        $params['timestamp']= (string)time();
        $params['sign']   	= $this->get_sign($params);
        return $params;
    }
}

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
        $sign=$this->get_sign($options);		//获取签名
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
        $sign=$this->get_sign($options);		//获取签名
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
        $sign=$this->get_sign($options);		//获取签名
        $options['sign']=$sign;
        $xmldata=self::ArrayXml($options);
        return $response = self::postXmlCurl(self::QUERY_RED_PACK_INFO,$xmldata,true,5);
    }
}

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

//微信支付结果通知类
class weixin_notify extends weixin_pay_sdk{
    /**
     * 接口通知接收
     * @return array
     */
    public function get_notify(){
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = self::XmlArray($xml);
        if(!empty($data)){
            if($data['return_code']=='SUCCESS'){
                if($data['result_code']=='SUCCESS'){
                    return $data;
                }else{
                    self::log('错误码：'.$data['err_code'].'  错误描述：'.$data['err_code_des']);
                }
            }else{
                self::log($data['return_msg']);
            }
        }
    }
    //回复通知
    public function return_notify($return_msg=true){
        if($return_msg===true){
            $data=array(
                'return_code' => 'SUCCESS',
                'return_msg'  => 'ok'
            );
        }else{
            $data=array(
                'return_code' => 'FAIL',
                'return_msg'  => $return_msg
            );
        }
        echo $xmldata=self::ArrayXml($data);
    }
}
