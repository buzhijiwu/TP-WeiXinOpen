<?php
namespace Admin\Controller;
use Think\Controller;

class IndexController extends CheckLoginController {
    private $Model_Index;

    public function _initialize(){
        parent::_initialize();
        $this->Model_Index = D('Admin/Index');
    }

    public function index(){
        //验证是否超级管理员登录
        $admin_id = session('AdminId');
        if($admin_id){
            $this->redirect('Admin/Index/index_admin');exit;
        }
        //验证是否商家登录
        $user_id = session('UserId');
        if($user_id){
            $this->redirect('Admin/Index/index_user');exit;
        }
        $this->redirect('Admin/Login/login');
	}

    //超级管理员后台首页
    public function index_admin(){
        $admin_id = session('AdminId');
        if(!$admin_id){
            $this->redirect('Admin/Login/login');exit;
        }
        $role = session('AdminRole');   //管理员角色
        $last_login_time = $this->Model_Index->getLastLoginTime(1,$admin_id);   //最后登录时间

        $nowPage = I('get.page',1);
        $user_list = $this->Model_Index->getUserList($nowPage,C('PAGE_SIZE'));     //获取用户列表
        //分页
        $Page = D('Page','Model');
        $count = $this->Model_Index->getUserTotalCounts();
        $link_page = $Page->getPage($nowPage, $count,U('Admin/Index/index_admin'),C('PAGE_SIZE'));

        $this->assign('role',$role);
        $this->assign('user_list',$user_list);
        $this->assign('link_page',$link_page);
        $this->assign('last_login_time',$last_login_time);
        $this->display("index/index_manage_user");
    }

    //普通商户后台首页
    public function index_user(){
        $user_id = session('UserId');
        if(!$user_id){
            $this->redirect('Admin/Login/login');exit;
        }
        $last_login_time = $this->Model_Index->getLastLoginTime(2,$user_id);   //最后登录时间
        $user_info = $this->Model_Index->getUserInfoById($user_id);

        $this->assign('last_login_time',$last_login_time);
        $this->assign('user_info',$user_info);
        $this->display("index/index_user");
    }

    //后台管理员管理商户账号
    public function index_manage_user(){
        $user_id = I('get.user_id');
        $user_info = $this->Model_Index->getAdminUserById($user_id);
        if($user_info){
            $this->redirect('Admin/Index/index_user');exit;
        }else{
            echo '您没有权限管理该账号！';exit;
        }
    }
}