<?php
namespace xybingbing;
use xybingbing\weixin_notify;

//微信支付结果通知类
class weixin_notify extends weixin_pay_sdk{
    /**
     * 接口通知接收
     * @return array
     */
    public static function get_notify($key){
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = self::XmlArray($xml);
        if(!empty($data)){
            if($data['return_code']=='SUCCESS'){
                if($data['result_code']=='SUCCESS'){
                		$sign_old=$data['sign'];
					unset($data['sign']);
                    $sign=$this->get_sign($data,$key);		//获取签名
                    if($sign==$sign_old){
                    		return $data;
                    }else{
                    		self::log('签名有误：'.$data);
                    }
                    
                }else{
                    self::log('错误码：'.$data['err_code'].'  错误描述：'.$data['err_code_des']);
                }
				
            }else{
                self::log($data['return_msg']);
            }
        }
    }
    //回复通知
    public static function return_notify($return_msg=true){
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
