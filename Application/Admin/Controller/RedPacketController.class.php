<?php
/**
 * 红包类
 */

namespace Admin\Controller;
use Think\Controller;

class RedPacketController extends CheckLoginController
{
	private $error;
	private $redpacket_model;
	private $redpacket_option_model;
	private $redpacket_share_model;
	private $redpacket_send_model;
	public function _initialize(){
		parent::_initialize();
		
		$this->redpacket_model = D('Admin/RedPacket');
		$this->redpacket_option_model = D('Admin/RedPacketOption');
		$this->redpacket_share_model = D('Admin/RedPacketShare');
		$this->redpacket_send_model = D('Admin/RedPacketSend');
	}
	
	/*
	 * 获取商家id 
	 */
	public function getUserId(){
		return session('UserId');
	}
	
    /*
     *红包活动列表页
     */
	public function index(){
		$user_id = $this->getUserId();//商家id
		$nowPage = I('get.page',1);
		$where = array();
		if(IS_POST){
			//查询
			$name = I('post.name','');
			$id = I('post.id','');
			if($name){
				$where['name'] = array('like','%'.$name.'%');
			}
			if($id){
				$where['id'] = $id;
			}
			$this->assign('id',$id);
			$this->assign('name',$name);
		}
		$results = $this->redpacket_model->getRedPacketLists($user_id,$where,$nowPage,C('PAGE_SIZE'));
		if($results){
			foreach ($results as $key=>$result){
				$result['totalPrices'] = $this->redpacket_option_model->getTotalRedPacketPrices($result['id']);
				$result['totalNums'] = $this->redpacket_option_model->getTotalRedPacketNums($result['id']);
				$results[$key] = $result;
			}
		}
		$data['lists'] = $results;
		//分页
		$Page = D('Page', 'Model');
		$count = $this->redpacket_model->getRedPacketNums($user_id,$nowPage,C('PAGE_SIZE'));
		$link_page = $Page->getPage($nowPage, $count,U('redPacket/index'),C('PAGE_SIZE'));
		$data['link_page'] = $link_page;
		$data['nav_name'] = 'redpacket';
		
		$this->assign($data);
		$this->display('redpacket/index');
	}
	
	/*
	 * 添加,编辑活动
	 */
	public function add(){
		if(IS_POST){
			$rid = I('post.rid',0);
			$validate = $this->validate();
			if(!$validate){
				$this->error($this->error);
			}
			//活动基本信息
			$data['name'] = I('post.name','');
			$data['entrance'] = I('post.entrance','');
			$data['user_id'] = $this->getUserId();
			$data['add_time'] = date("Y-m-d H:i:s");
			$data['modify_time'] = date("Y-m-d H:i:s");
			
			$start_times = I('post.start_time',array());
			$end_times = I('post.end_time',array());
			$prices = I('post.price',array());
			$nums = I('post.num',array());
			
			//活动分享信息
			$share['share_title'] = I('post.share_title','');
			$share['share_description'] = I('post.share_description','');
			$share['share_url'] = I('post.share_url','');
			$has_share_image = I('post.has_share_image','');
			$file = $_FILES['share_img']['name'];
			if($file){
				$arr = explode('.',$file);
				$share_img = microtime(true).'.'.$arr[1];
			}else{
				$share_img = '';
			}
			if($share_img){
				//文件上传阿里云服务器
				$uploadFileOss = new \Common\UploadFileToOss();
				$filePath = $_FILES['share_img']['tmp_name'];
				$uplpadResult = $uploadFileOss->scandirFile(C('UPLOAD_FILE_BUCKET'),C('UPLOAD_TARGET_DIRECTORY'),$share_img,$filePath);
				if($uplpadResult == "OK"){
					$ossClient = $uploadFileOss->createOssClient();
					$bucket = C('UPLOAD_FILE_BUCKET');
					$object = C('UPLOAD_TARGET_DIRECTORY').$share_img;
					$timeout = C('UPLOAD_IMAGE_TIMEOUT'); // 访问图片URL的有效期
					$fileUrl = $uploadFileOss->getSignedUrlForGettingObject($ossClient, $bucket,$object,$timeout);  //得到OSS返回的图片路径
					$share['share_img'] = $fileUrl;
				} else{
					$share['share_img'] = '';
				}
			}else{
				if($has_share_image){
					$share['share_img'] = $has_share_image;
				}else{
					$share['share_img'] = '';
				}
			}
			
			//活动发放信息
			$send['merchant'] = I('post.merchant','');
			$send['activity_name'] = I('post.activity_name','');
			$send['bless_word'] = I('post.bless_word','');
			$send['remark'] = I('post.remark','');
			
			//开始事务
			$model = $this->redpacket_model->getRedpacketConnection();
			$model->startTrans();
			if($rid){
				$redPacket_res = $this->redpacket_model->editRedPacket($rid,$data);
				$share_res = $this->redpacket_share_model->editRedPacketShare($rid,$share);
				$send_res = $this->redpacket_send_model->editRedPacketSend($rid,$send);
				if($redPacket_res === false || $share_res === false || $send_res === false){
					//操作失败回滚
					$model->rollback();
					$this->error('活动编辑失败！');
				}else{
					$model->commit();
					//活动编辑成功后为活动添加规则
					$this->redpacket_option_model->delRedPacketOptions($rid);
					$option_ids = I('post.option_id',array());
					foreach ($start_times as $key=> $start_time){
						$option = array();
						$option['start_time'] = $start_time;
						$option['end_time'] = $end_times[$key];
						$option['price'] = $prices[$key];
						$option['num'] = $nums[$key];
						if(isset($option_ids[$key])){
							$option['id'] = $option_ids[$key];
						}
						$this->redpacket_option_model->addRedPacketOption($rid,$option);
					}
					$this->success('活动编辑成功！', U('/Admin/redPacket/index'));
				}
			}else{
				$rid = $this->redpacket_model->addRedPacket($data);
				$share_id = $this->redpacket_share_model->addRedPacketShare($rid,$share);
				$send_id = $this->redpacket_send_model->addRedPacketSend($rid,$send);
				if($rid && $share_id && $send_id){
					$model->commit();
					//活动添加成功后为活动添加规则
					foreach ($start_times as $key=> $start_time){
						$option['start_time'] = $start_time;
						$option['end_time'] = $end_times[$key];
						$option['price'] = $prices[$key];
						$option['num'] = $nums[$key];
						$this->redpacket_option_model->addRedPacketOption($rid,$option);
					}
					$this->success('活动添加成功！', U('/Admin/redPacket/index'));
				}else{
					//操作失败回滚
					$model->rollback();
					$this->error('活动添加失败！');
				}
			}
		}else{
			$id = I('get.id',0);
			if($id){
				$redPacket = $this->redpacket_model->getRedPacket($id);
				if($redPacket){
					$this->assign('redPacket',$redPacket);
					$redPacketOptionLists = $this->redpacket_option_model->getRedPacketOptions($redPacket['id']);
					$this->assign('redPacketOptionLists',$redPacketOptionLists);
					$redPacketShare = $this->redpacket_share_model->getRedPacketShare($redPacket['id']);
					$this->assign('redPacketShare',$redPacketShare);
					$redPacketSend = $this->redpacket_send_model->getRedPacketSend($redPacket['id']);
					$this->assign('redPacketSend',$redPacketSend);
				}
			}
			$this->display('redpacket/add');
		}
	}
	
	/*
	 * 查看活动的详情
	 */
	public function view(){
		$id = I('get.id',0);
		$redPacket = $this->redpacket_model->getRedPacket($id);
		if($redPacket){
			$this->assign('redPacket',$redPacket);
			$redPacketOptionLists = $this->redpacket_option_model->getRedPacketOptions($redPacket['id']);
			$this->assign('redPacketOptionLists',$redPacketOptionLists);
			$redPacketShare = $this->redpacket_share_model->getRedPacketShare($redPacket['id']);
			$this->assign('redPacketShare',$redPacketShare);
			$redPacketSend = $this->redpacket_send_model->getRedPacketSend($redPacket['id']);
			$this->assign('redPacketSend',$redPacketSend);
			$this->display('redpacket/info');
		}else{
			//找不到活动
		}
	}
	
	/*
	 * 删除活动
	 */
	public function delete(){
		if(IS_AJAX){
			$id = I('post.id',0);
			$redpacket_del = $this->redpacket_model->delRedPacket($id);
			if($redpacket_del === false){
				$this->ajaxReturn(false);
			}else{
				$this->ajaxReturn(true);
			}
		}else{
			$this->ajaxReturn(false);
		}
	}
	
	/*
	 * 发布活动
	 */
	public function release(){
		if(IS_AJAX){
			$id = I('post.id',0);
			$redpacket_release = $this->redpacket_model->releaseRedPacket($id);
			if($redpacket_release === false){
				$this->ajaxReturn(false);
			}else{
				$this->ajaxReturn(true);
			}
		}else{
			$this->ajaxReturn(false);
		}
	}
	
	/*
	 * 提交数据验证
	 */
	public function validate(){
		$name = I('post.name','');
		if(!$name){
			$this->error = '请填写活动名称！';
			return false;
		}
		$entrance = I('post.entrance','');
		if(!$entrance){
			$this->error = '请填写活动入口！';
			return false;
		}
		$start_time = I('post.start_time',array());
		$end_time = I('post.end_time',array());
		$price = I('post.price',array());
		$num = I('post.num',array());
		if(empty($start_time) || empty($end_time) || empty($price) || empty($num)){
			$this->error = '请填写活动规则！';
			return false;
		}else{
			foreach ($start_time as $key=>$stime){
				if(strtotime($stime) >= strtotime($end_time[$key])){
					$this->error = '活动结束时间不能小于活动开始时间！';
					return false;
				}else if(($key+1 < count($start_time)) && (strtotime($start_time[$key+1]) < strtotime($end_time[$key]))){
					$this->error = '下一段的活动时间不能小于上一段的时间！';
					return false;
				}
			}
		}
		$has_share_image = I('post.has_share_image','');
		if(!$has_share_image){
			$share_img = $_FILES['share_img']['name'];
			if(!$share_img){
				$this->error = '请填写分享图标！';
				return false;
			}
		}
		
		$share_url = I('post.share_url','');
		if(!$share_url){
			$this->error = '请填写分享链接！';
			return false;
		}
		$send_merchant = I('post.merchant','');
		if(!$send_merchant){
			$this->error = '请填写商户名称！';
			return false;
		}
		$send_activity_name = I('post.activity_name','');
		if(!$send_activity_name){
			$this->error = '请填写红包发放的活动名称！';
			return false;
		}
		return true;
	}
}