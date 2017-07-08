<?php
namespace Admin\Controller;
use Think\Controller;

class CheckLoginController extends Controller {
    public function _initialize(){
        // 后台用户权限检查
        if(!session('AdminId') && !session('UserId')){
            $this->redirect('Admin/Login/login');
        }
    }
}