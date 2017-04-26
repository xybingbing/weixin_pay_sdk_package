#微信商户平台所以接口相关



##1.安装
```
composer require xybingbing/weixin_pay_sdk
```


##2.使用
```
require "vendor/autoload.php";
```


> 基本参数
```
$parameter=array(
	'appid'=>'', 	//微信ID
    'appsecret'=>'', //微信密钥
    'mch_id'=>'', //微信商户ID
    'paykey'=>'', //微信商户密钥
	'debug'=>true, //是否开启调试模式。关闭调试模式后 报错 会输出到weixin_log.txt
);
```

> 1. 微信公众号支付相关  
```
$wxpay=new xybingbing\weixin_pay($parameter);		详细请看examples/wechat.php
```

> 2. APP微信支付相关
```
$wxpay=new xybingbing\weixin_app_pay($parameter);		详细请看examples/app.php
```

> 3. 扫码支付相关
```
$wxpay=new xybingbing\weixin_scancode_pay($parameter);		详细请看examples/scancode.php
```

> 4. 微信刷卡支付相关
```
$wxpay=new xybingbing\weixin_pay_card($parameter);		详细请看examples/card.php
```

> 5. 微信红包相关
```
$wxpay=new xybingbing\weixin_bonus($parameter);		详细请看examples/bonus.php
```

> 6. 微信代金券相关
```
$wxpay=new xybingbing\weixin_coupon($parameter);		详细请看examples/coupon.php
```

> 7. 企业付款相关
```
$wxpay=new xybingbing\weixin_payment($parameter);		详细请看examples/payment.php
```
