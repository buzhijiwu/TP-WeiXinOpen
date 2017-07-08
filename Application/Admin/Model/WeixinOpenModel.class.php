<?php
namespace Admin\Model;
use Think\Model;

class WeixinOpenModel extends Model {
    public function __construct(){
//        $this->wx_user = M('user');
    }

    /**
     *  消息自动回复
     */
    public function reply($msg){
        $CreateTime = time();
        if(isset($msg['Content'])){
            $Content = $msg['Content'];
        }else{
            $Content = '被动响应消息测试';
        }
        $replyMsg = "<xml>
                    <ToUserName><![CDATA[{$msg['FromUserName']}]]></ToUserName>
                    <FromUserName><![CDATA[{$msg['ToUserName']}]]></FromUserName>
                    <CreateTime>{$CreateTime}</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[{$Content}]]></Content>
                </xml>";
        return $replyMsg;
    }
}