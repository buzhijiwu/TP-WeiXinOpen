<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHP 控制器基类 抽象类
 */
abstract class AdminController extends Controller{


    public function __construct() {
    	$user_id = session('user_id');
    	if(!isset($_GET['page'])){
    		$arr=array();
    		session("search",$arr);
    	}
    	if(!$user_id) {
    		$this->redirect('admin/index/login');
    	}else{
        Hook::listen('action_begin',$this->config);
        //实例化视图类
        $this->view     = Think::instance('Think\View');
        //控制器初始化
        if(method_exists($this,'_initialize'))
            $this->_initialize();
    	}
    }
}
// 设置控制器别名 便于升级
class_alias('Think\Controller','Think\Action');