<?php
namespace Admin\Model;
use Think\Model;

class IndexModel extends Model {
    public function __construct(){
        $this->wx_admin = M('admin');
        $this->wx_user = M('user');
        $this->wx_login_history = M('login_history');
    }

    //登录
    public function Login($username,$password){
        $result = false;
        $where['username'] = $username;
        $where['password'] = md5($password);
        $where['is_delete'] = 0;
        $admin_info = $this->wx_admin->where($where)->find();
        if($admin_info){
            session('AdminId',$admin_info['id']);
            session('AdminRole',$admin_info['role']);
            session('AdminName',$admin_info['username']);

            $this->addLoginHistory(1,$admin_info['id']);
            $result = true;
        }else{
            $user_info = $this->wx_user->where($where)->find();
            if($user_info){
                session('UserId',$user_info['id']);
                session('UserName',$user_info['username']);

                $this->addLoginHistory(2,$user_info['id']);
                $result = true;
            }
        }
        return $result;
    }

    //添加登录历史
    public function addLoginHistory($manager_type,$manager_id){
        $data['manager_type'] = $manager_type;
        $data['manager_id'] = $manager_id;
        $data['login_ip'] = get_client_ip();
        $data['login_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $data['add_time'] = date('Y-m-d H:i:s');
        $this->wx_login_history->add($data);
    }

    //上次登录时间
    public function getLastLoginTime($manager_type,$manager_id){
        $last_login_time = '0000-00-00 00:00:00';
        $where['manager_type'] = $manager_type;
        $where['manager_id'] = $manager_id;
        $last_login_info = $this->wx_login_history->where($where)->order('add_time desc')->limit(1,1)->select();
        if($last_login_info[0]){
            $last_login_time = $last_login_info[0]['add_time'];
        }
        return $last_login_time;
    }

    //获取商家账号总数
    public function getUserTotalCounts(){
        $count = 0;
        if(session('AdminId') && session('AdminRole')){
            $admin_id = session('AdminId');
            $role = session('AdminRole');
            $where = array();
            $where['is_delete'] = 0;
            if($role != 1){
                $where['aid'] = $admin_id;
            }
            $count = $this->wx_user->where($where)->count();
        }
        return $count;
    }

    //获取商家账号列表
    public function getUserList($page,$pageSize){
        $list = array();
        if(session('AdminId') && session('AdminRole')){
            $admin_id = session('AdminId');
            $role = session('AdminRole');
            $where = array();
            $where['is_delete'] = 0;
            if($role != 1){
                $where['aid'] = $admin_id;
            }
            $list = $this->wx_user->where($where)->page($page,$pageSize)->order('id asc')->select();
        }
        return $list;
    }

    //根据ID获取商家账号信息
    public function getUserInfoById($user_id){
        $where_user['id'] = $user_id;
        $where_user['is_delete'] = 0;
        $user_info = $this->wx_user->where($where_user)->find();
        return $user_info;
    }

    //后台管理员管理商户账号
    public function getAdminUserById($user_id){
        $result = false;
        $where_user['id'] = $user_id;
        $where_user['is_delete'] = 0;
        $user_info = $this->wx_user->where($where_user)->find();
        if($user_info && session('AdminId')){
            if(session('AdminRole') == 1){
                $result = true;
            }
            if(session('AdminId') == $user_info['aid']){
                $result = true;
            }
        }
        if($result){
            session('UserId',$user_info['id']);
            session('UserName',$user_info['username']);
        }else{
            $user_info = array();
        }
        return $user_info;
    }
}