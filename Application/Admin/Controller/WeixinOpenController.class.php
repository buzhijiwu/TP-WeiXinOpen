<?php
namespace Admin\Controller;
use Think\Controller;

class WeixinOpenController extends Controller {
    private $appId;
    private $appSecret;
    private $WeixinOpen;

    public function _initialize(){
        $this->appId = C('appid_weiwanglianmeng_open');
        $this->appSecret = C('appsecret_weiwanglianmeng_open');
        $this->WeixinOpen = new \Common\WeixinOpen($this->appId,$this->appSecret);
    }

    // 授权事件接收URL，公众号消息推送URL
    public function component(){
        $this->WeixinOpen->getComponentVerifyTicket();
        echo 'success';exit;
    }

    //“微信公众号授权”的入口，引导公众号运营者进入授权页
    public function authorization(){
        $pre_auth_code = $this->WeixinOpen->getPreAuthCode();
        $redirect_uri = 'http://'.$_SERVER["HTTP_HOST"].'/index.php?s=/Admin/WeixinOpen/get_authorization_code/';
        $api_url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->appId.'&pre_auth_code='.$pre_auth_code.'&redirect_uri='.$redirect_uri;
        redirect($api_url);
    }

    //授权后回调URI，得到授权码，换取令牌和公众号信息
    public function get_authorization_code(){
        //得到授权码（authorization_code）和过期时间
        $authorization_code = I('get.auth_code');
        if(!$authorization_code){
            echo 'Error：get authorization code fail';exit;
        }
        //换取接口调用凭据和授权信息
        $authorizer_access_token = $this->WeixinOpen->getAuthorizerAccessToken($authorization_code);
        if(!$authorizer_access_token){
            echo 'Error：get authorizer access token fail';exit;
        }
        //获取授权公众号基本信息
        $authorizer_info = $this->WeixinOpen->getAuthorizerInfo();
        if(!$authorizer_info){
            echo 'Error：get authorizer info fail';exit;
        }
        //保存授权公众号信息
        if(session('UserId')){
            $user_id = session('UserId');
            $Model_Index = D('Admin/Index');
            $user_info = $Model_Index->getUserInfoById($user_id);
            if($user_info){
                $where['id'] = $user_id;
                $data['user_appid'] = $authorizer_info['authorization_info']['authorizer_appid'];
                $data['nick_name'] = $authorizer_info['authorizer_info']['nick_name'];
                $data['head_img'] = $authorizer_info['authorizer_info']['head_img'];
                $data['service_type'] = $authorizer_info['authorizer_info']['service_type_info']['id'];
                $data['verify_type'] = $authorizer_info['authorizer_info']['verify_type_info']['id'];
                $data['verify_type'] = $authorizer_info['authorizer_info']['verify_type_info']['id'];
                $data['user_name'] = $authorizer_info['authorizer_info']['user_name'];
                $data['alias'] = $authorizer_info['authorizer_info']['alias'];
                $data['qrcode_url'] = $authorizer_info['authorizer_info']['qrcode_url'];
                M('user')->where($where)->save($data);
            }
        }
        $this->redirect('Admin/Index/index_user');exit;
    }
}
