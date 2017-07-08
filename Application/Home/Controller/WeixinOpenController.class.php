<?php
namespace Home\Controller;
use Think\Controller;

class WeixinOpenController extends Controller {
    private $appId;
    private $appSecret;
    private $userAppId;
    private $WeixinOpen;

    public function _initialize(){
        $this->appId = C('appid_weiwanglianmeng_open');
        $this->appSecret = C('appsecret_weiwanglianmeng_open');
        $this->userAppId = 'wxbfaf05bc979d328d';  //多乐万
        $this->WeixinOpen = new \Common\WeixinOpen($this->appId,$this->appSecret,$this->userAppId);
    }

    //网页授权获取用户openid
    public function get_openid(){
        $session_name = 'OpenId';
        $this->WeixinOpen->Oauth($session_name);
        $openid = session($session_name);
        return $openid;
    }
}
