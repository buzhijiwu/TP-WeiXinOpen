<?php
/**
 * 微信开放平台公共接口类
 */
namespace Common;
use Com\WXBizMsgCrypt;

class WeixinOpen {
    private $redis;
    private $appId;
    private $appSecret;
    private $userAppId;
    private $token = 'cea437b008821a5dfe9df5c8aefbba81';
    private $encodingAesKey = 'DoWqytxfnoTfsoFq71bE50C7llqaly1d2UpnvQsPkGK';

    //初始化微信开放平台配置信息
    public function __construct($AppId,$AppSecret,$UserAppId='') {
        $this->appId = $AppId;
        $this->appSecret = $AppSecret;
        $this->userAppId = $UserAppId;
        $this->redis = new \Common\ThinkRedis();
    }

    /**
     *  推送component_verify_ticket协议
     */
    public function getComponentVerifyTicket(){
        $pc = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->appId);
        $msg_sign = I('get.msg_signature');
        $timeStamp = I('get.timestamp');
        $nonce = I('get.nonce');

        // 第三方收到公众号平台发送的消息
        $msg = '';

        //获取授权相关通知
        $from_xml  = file_get_contents("php://input");
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if ($errCode == 0) {
//            \Think\Log::write(" 消息解密 ===> ".$msg."\r\n");
            $msg_array = (array)simplexml_load_string($msg,'SimpleXMLElement',LIBXML_NOCDATA);

            if(isset($msg_array['InfoType']) && $msg_array['InfoType'] == 'component_verify_ticket'){
                if(isset($msg_array['ComponentVerifyTicket']) && $msg_array['ComponentVerifyTicket']){
                    $ComponentVerifyTicket = $msg_array['ComponentVerifyTicket'];
                    $this->redis->hSet('ComponentVerifyTicket',$this->appId,$ComponentVerifyTicket);
                }
            }else{
                $encryptMsg = '';
                $Model_WeixinOpen = D('Admin/WeixinOpen');
                $replyMsg = $Model_WeixinOpen->reply($msg_array);
                $pc->encryptMsg($replyMsg, $timeStamp, $nonce, $encryptMsg);
                echo $encryptMsg;exit;
            }
        }
    }

    /**
     * compoment_access_token是第三方平台的接口的调用凭据
     */
    public function getComponentAccessToken(){
        $component_access_token = $this->redis->get('ComponentAccessToken_'.$this->appId);
        if(!$component_access_token){
            $component_verify_ticket = $this->redis->hGet('ComponentVerifyTicket',$this->appId);
            if($component_verify_ticket){
                $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
                $json = '{
                        "component_appid":"'.$this->appId.'" ,
                        "component_appsecret": "'.$this->appSecret.'",
                        "component_verify_ticket": "'.$component_verify_ticket.'"
                    }';
                $result = https_request($url,$json);
                $jsoninfo = json_decode($result, true);
                $component_access_token = $jsoninfo['component_access_token'];
                if($component_access_token){
                    $this->redis->set('ComponentAccessToken_'.$this->appId,$component_access_token);
                    $this->redis->expire('ComponentAccessToken_'.$this->appId,7000);
                }
            }
        }
        return $component_access_token;
    }

    /**
     *  第三方平台方获取预授权码（pre_auth_code）
     */
    public function getPreAuthCode(){
        $pre_auth_code = '';
        $ComponentAccessToken = $this->getComponentAccessToken();
        if($ComponentAccessToken){
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.$ComponentAccessToken;
            $json = '{
                        "component_appid":"'.$this->appId.'"
                    }';
            $result = https_request($url,$json);
            $jsoninfo = json_decode($result, true);
            $pre_auth_code = $jsoninfo['pre_auth_code'];
        }
        return $pre_auth_code;
    }


    /**
     *  使用授权码换取公众号的接口调用凭据和授权信息
     */
    public function getAuthorizerAccessToken($authorization_code){
        $jsoninfo = array();
        if($authorization_code){
            $ComponentAccessToken = $this->getComponentAccessToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$ComponentAccessToken;
            $json = '{
                        "component_appid":"'.$this->appId.'" ,
                        "authorization_code": "'.$authorization_code.'"
                    }';
            $result = https_request($url,$json);
            $jsoninfo = json_decode($result, true);
            if(is_array($jsoninfo) && $jsoninfo){
                $authorizer_access_token = $jsoninfo['authorization_info']['authorizer_access_token'];
                $authorizer_refresh_token = $jsoninfo['authorization_info']['authorizer_refresh_token'];
                $this->userAppId = $jsoninfo['authorization_info']['authorizer_appid'];

                $this->redis->set('AuthorizerAccessToken_'.$this->userAppId,$authorizer_access_token);
                $this->redis->expire('AuthorizerAccessToken_'.$this->userAppId,7000);
                $this->redis->hSet('AuthorizerRefreshToken',$this->userAppId,$authorizer_refresh_token);
            }
        }
        return $jsoninfo;
    }

    /**
     *  获取(刷新)授权公众号的接口调用凭据（令牌）
     */
    public function getAccessTokenOpen(){
        $authorizer_access_token = $this->redis->get('AuthorizerAccessToken_'.$this->userAppId);
        if(!$authorizer_access_token){
            $authorizer_refresh_token = $this->redis->hGet('AuthorizerRefreshToken',$this->userAppId);
            if($authorizer_refresh_token){
                $ComponentAccessToken = $this->getComponentAccessToken();
                $url = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='.$ComponentAccessToken;

                $json = '{
                        "component_appid":"'.$this->appId.'" ,
                        "authorizer_appid": "'.$this->userAppId.'",
                        "authorizer_refresh_token": "'.$authorizer_refresh_token.'"
                    }';
                $result = https_request($url,$json);
                $jsoninfo = json_decode($result, true);
                $authorizer_access_token = $jsoninfo['authorizer_access_token'];
                $authorizer_refresh_token = $jsoninfo['authorizer_refresh_token'];

                $this->redis->set('AuthorizerAccessToken_'.$this->userAppId,$authorizer_access_token);
                $this->redis->expire('AuthorizerAccessToken_'.$this->userAppId,7000);
                $this->redis->hSet('AuthorizerRefreshToken',$this->userAppId,$authorizer_refresh_token);
            }
        }
        return $authorizer_access_token;
    }

    //获取授权方的公众号帐号基本信息
    public function getAuthorizerInfo(){
        $ComponentAccessToken = $this->getComponentAccessToken();
        $authorizer_info = array();
        if($ComponentAccessToken){
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$ComponentAccessToken;
            $json = '{
                        "component_appid":"'.$this->appId.'" ,
                        "authorizer_appid": "'.$this->userAppId.'"
                    }';
            $result = https_request($url,$json);
            $this->redis->hSet('AuthorizerInfo',$this->userAppId,$result);
            $authorizer_info = json_decode($result, true);
        }
        return $authorizer_info;
    }

    //页面授权获取用户openid（snsapi_base）
    public function Oauth($session_name){
        if(!session($session_name)){
            if(isset($_GET['code']) && $_GET['code']){
                $code = $_GET['code'];
                $ComponentAccessToken = $this->getComponentAccessToken();
                $url = "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=".$this->userAppId."&code=".$code."&grant_type=authorization_code&component_appid=".$this->appId."&component_access_token=".$ComponentAccessToken;
                $result = https_request($url);
                $jsoninfo = json_decode($result, true);
                if(isset($jsoninfo['openid']) && $jsoninfo['openid']){
                    session($session_name,$jsoninfo['openid']);
                }
            }else{
                $redirect_uri = urlencode(get_url());
                $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->userAppId."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=".time()."&component_appid=".$this->appId."#wechat_redirect";
                redirect($url);
            }
        }
    }
}