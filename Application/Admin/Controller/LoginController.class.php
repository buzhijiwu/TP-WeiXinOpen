<?php
namespace Admin\Controller;
use Think\Controller;

class LoginController extends Controller {
    private $Model_Index;

    public function _initialize(){
        $this->Model_Index = D('Admin/Index');
    }

    //登录后台
    public function login(){
        $username = '';
        $password = '';
        if(IS_POST){
            $username = I('post.username');
            $password = I('post.password');
            $result = $this->Model_Index->Login($username,$password);
            if($result){
                $this->redirect('Admin/Index/index');exit;
            }else{
                $this->assign('error',1);
            }
        }
        $this->assign('username',$username);
        $this->assign('password',$password);
        $this->display("index/login");
    }

    //退出登录
    public function logout(){
        session_destroy();
        unset($_SESSION);
        $this->redirect('Admin/Login/login');
    }
}