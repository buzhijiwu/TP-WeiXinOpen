<?php
//引入异常处理类
include("Hongbao/SDKRuntimeException.class.php");
/**
 * 微信红包,默认使用微往联盟公众号配置
 */
class WeixinHongbao {
    var $parameters; //定义参数组
    public static $APPID = "wx59437eafc22a2410";
    public static $APPSECRET = 'b7dc111b8dc48b50913c325de0f6fbb4';
    public static $MCHID = '1311059201';
    public static $KEY = "U8rnd763laWe2Mnv64c064sdbrx3k1mf";//支付的key
    public static $SSLCERT_PATH = './Application/Common/Hongbao/pem/weiwanglianmeng/apiclient_cert.pem';
    public static $SSLKEY_PATH = './Application/Common/Hongbao/pem/weiwanglianmeng/apiclient_key.pem';

    /**
     * 实例化配置信息
     */
    public function __construct($weixin = 'weiwanglianmeng'){
        if($weixin != 'weiwanglianmeng'){
            WeixinHongbao::$APPID = C('appid_'.$weixin);
            WeixinHongbao::$APPSECRET = C('appsecret_'.$weixin);
            WeixinHongbao::$MCHID = C('mchid_'.$weixin);
            WeixinHongbao::$KEY = C('partnerkey_'.$weixin);
            WeixinHongbao::$SSLCERT_PATH = './Application/Common/Hongbao/pem/'.$weixin.'/apiclient_cert.pem';
            WeixinHongbao::$SSLKEY_PATH = './Application/Common/Hongbao/pem/'.$weixin.'/apiclient_key.pem';
        }
    }

    public function getParameter($key){
        return $this->parameters[$key];
    }

    public function setParameter($key,$val){
        $this->parameters[$this->trimString($key)]=$this->trimString($val);
    }


    /**
     * 生成要发送给微信的xml 红包消息
     */
    public function create_xml(){
        try{
            $sign=$this->getSign($this->parameters);
            $this->parameters["sign"]=$sign;//设置签名sign进去
            $xml=$this->arrayToXml($this->parameters);
            return $xml;
        }catch (SDKRuntimeException $e){
            die($e->errorMessage());
        }
    }

    /**
     * 	作用：array转xml
     */
    function arrayToXml($arr){

        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


    /**
     * 生成签名
     * @param $parameter 待签名需要的参数数组
     */
    public function getSign($parameter){
        try {
            if (null == $parameter || "" == $parameter ) {
                throw new SDKRuntimeException("密钥不能为空！" . "<br>");
            }
            if($this->check_sign_parameters() == false) {   //检查生成签名参数
                throw new SDKRuntimeException("生成签名参数缺失！" . "<br>");
            }

            $unSignParaString = $this->formationSign($parameter, false);

            $sign=$this->sign($unSignParaString,$this->trimString(WeixinHongbao::$KEY));
            return $sign;
        }catch (SDKRuntimeException $e){
            die($e->errorMessage());
        }
    }

    /**
     * 检查签名的参数不能为空
     */
    function check_sign_parameters(){
        if($this->parameters["nonce_str"] == null ||
            $this->parameters["mch_billno"] == null ||
            $this->parameters["mch_id"] == null ||
            $this->parameters["wxappid"] == null ||
            $this->parameters["nick_name"] == null ||
            $this->parameters["send_name"] == null ||
            $this->parameters["re_openid"] == null ||
            $this->parameters["total_amount"] == null ||
            $this->parameters["max_value"] == null ||
            $this->parameters["total_num"] == null ||
            $this->parameters["wishing"] == null ||
            $this->parameters["client_ip"] == null ||
            $this->parameters["act_name"] == null ||
            $this->parameters["remark"] == null ||
            $this->parameters["min_value"] == null
        ){

            return false;
        }
        return true;
    }

    /**
     * 格式化签名参数
     * @param $parameter 待签名需要的参数数组
     */
    public function formationSign($parameters){
        $buff = "";
        ksort($parameters);// 对数组按照键名排序 去掉数组中的sign 不参与签名算法

        foreach ($parameters as $k => $v){
            if (null != $v && "null" != $v && "sign" != $k) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff)-1);//去掉最后一个&符号
        }
        return $reqPar;
    }

    /**
     * 生成最终的签名sign
     * @param signstr 上面生成的签名字符
     * @param paykey 加密的支付key 32位的那个
     */
    public function sign($signstr,$paykey){
        try {
            if (null == $paykey) {
                die("支付key不能为空");
            }
            if (null == $signstr) {
                die("签名内容不能为空");
            }
            $sign = $signstr . "&key=" . $paykey;
            return strtoupper(md5($sign));//md5后转换大写字符
        }catch (SDKRuntimeException $e)
        {
            die($e->errorMessage());
        }
    }

    /**
     * 	作用：使用证书，以post方式提交xml到对应的接口url
     */
    function postXmlSSLCurl($xml,$url,$second=30)
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch,CURLOPT_HEADER,FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        //设置证书
        curl_setopt($ch,CURLOPT_SSLCERT,WeixinHongbao::$SSLCERT_PATH);
        curl_setopt($ch,CURLOPT_SSLKEY, WeixinHongbao::$SSLKEY_PATH);
        //post提交方式
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        }else {
//            $error = curl_errno($ch);
//            echo "curl出错，错误码:$error"."<br>";
//            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 去出空格
     * @param value
     * @return 去除掉空格
     */
    static function trimString($value){
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 	作用：产生随机字符串，不长于32位
     */
    public function createNoncestr( $length = 32 )
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    /**
     * 	作用：产生随机数字
     */
    public function createNoncestr2( $length = 10 )
    {
        $chars = "0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

}
?>