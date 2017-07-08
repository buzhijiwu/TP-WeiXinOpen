<?php
/**
 * 微信公众平台公共接口类
 */
namespace Common;

class Weixin {
    private $redis;
    private $appId;
    private $appSecret;
    private $access_token;

    //初始化微信配置信息
    public function __construct($AppId,$AppSecret) {
        $this->appId = $AppId;
        $this->appSecret = $AppSecret;
        $this->redis = new \Common\ThinkRedis();
    }

    //获取全局凭证access_token
    public function getAccessToken(){
        $this->access_token = $this->redis->get('AccessToken_'.$this->appId);
        if(!$this->access_token){
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appId.'&secret='.$this->appSecret;
            $result = https_request($url);
            $jsoninfo = json_decode($result, true);
            if(isset($jsoninfo['access_token']) && $jsoninfo['access_token']){
                $this->access_token = $jsoninfo['access_token'];
                $this->redis->set('AccessToken_'.$this->appId, $jsoninfo['access_token']);
                $this->redis->expire('AccessToken_'.$this->appId, 7000);
            }
        }
        return $this->access_token;
    }

    //页面授权获取用户openid（snsapi_base）
    public function Oauth($session_name){
        if(!session($session_name)){
            if(isset($_GET['code']) && $_GET['code']){
                $code = $_GET['code'];
                $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appId."&secret=".$this->appSecret."&code=".$code."&grant_type=authorization_code";
                $result = https_request($url);
                $jsoninfo = json_decode($result, true);
                if(isset($jsoninfo['openid']) && $jsoninfo['openid']){
                    session($session_name,$jsoninfo['openid']);
                }
            }else{
                $redirect_uri = urlencode(get_url());
                $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appId."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=".time()."#wechat_redirect";
                redirect($url);
            }
        }
    }

    //页面授权获取用户基本信息（snsapi_userinfo）
    public function Oauth2($session_userinfo){
        if(!$session_userinfo){
            if(isset($_GET['code']) && $_GET['code']){
                $code = $_GET['code'];
                $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appId."&secret=".$this->appSecret."&code=".$code."&grant_type=authorization_code";
                $result = https_request($url);
                $jsoninfo = json_decode($result,true);
                if(isset($jsoninfo['openid']) && $jsoninfo['openid'] && isset($jsoninfo['access_token']) && $jsoninfo['access_token']){
                    $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$jsoninfo['access_token'].'&openid='.$jsoninfo['openid'].'&lang=zh_CN';
                    $userinfo = https_request($url);
                    $userinfo = json_decode($userinfo,true);
                    session($session_userinfo,$userinfo);
                }
            }else{
                $redirect_uri = urlencode(get_url());
                $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appId."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=".time()."#wechat_redirect";
                redirect($url);
            }
        }
    }

    //获取用户基本信息(UnionID机制)
    public function getUserinfo($openid){
        $this->access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
        $result = https_request($url);
        $userinfo = json_decode($result, true);
        return $userinfo;
    }

    //获取微信JSSDK配置信息
    public function getSignPackage($url='') {
        //临时票据jsapi_ticket
        $jsapiTicket = $this->redis->get('JSSDK_Ticket_'.$this->appId);
        if (!$jsapiTicket) {
            $this->access_token = $this->getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $get_access_token_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$this->access_token";
            $result = https_request($get_access_token_url);
            $result = json_decode($result,true);
            $jsapiTicket = $result['ticket'];
            $this->redis->set('JSSDK_Ticket_'.$this->appId,$jsapiTicket);
            $this->redis->expire('JSSDK_Ticket_'.$this->appId,7000);
        }

        //随机字符串
        $nonceStr = "";
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        for ($i = 0; $i < 16; $i++) {
            $nonceStr .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        //时间戳
        $timestamp = time();

        //当前URL（注意URL一定要动态获取，不能 hardcode）
        if(!$url){
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }

        //对所有待签名参数按照字段名的ASCII码从小到大排序（字典序）后，使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串string：
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        //对string进行sha1签名，得到signature
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    //创建自定义菜单
    public function create_menu($json_data){
        $this->access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->access_token;
        $result = https_request($url,$json_data);
        $result = json_decode($result, true);
        return $result;
    }

}