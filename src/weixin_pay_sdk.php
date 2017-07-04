<?php
namespace xybingbing;

//支付公共方法集合
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
            if(is_file(dirname(__FILE__).'/config/wx_config.php')){
                $config=include(dirname(__FILE__).'/config/wx_config.php');
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
    protected function get_sign($options,$key=''){
    		$key=!empty($key) ? $key : $this->paykey;
        ksort($options);
        $string=self::ToUrlParams($options);
        $string=$string.'&key='.$key;
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
