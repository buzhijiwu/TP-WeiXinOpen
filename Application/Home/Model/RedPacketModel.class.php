<?php
namespace Home\Model;
use Think\Model;

class RedPacketModel extends Model{
	public function __construct(){
		$this->redpacket = M('redpacket');//红包表
		$this->redpacket_option = M('redpacket_option');//红包活动时间及金额数量表
		$this->redpacket_share = M('redpacket_share');//红包活动分享信息表
		$this->redpacket_send = M('redpacket_send');//红包活动发放设置表
		$this->user = M('user');//商户表
	}
	
	/*
	 * 根基活动id获取活动信息
	 */
	public function getActivityInfoById($id){
		$where['id'] = $id;
		$res = $this->redpacket->where($where)->find();
		if($res){
			$where_option['rid'] = $id;
			$options = $this->redpacket_option->where($where_option)->select();
			$res['options'] = $options;
			$where_share['rid'] = $id;
			$share = $this->redpacket_share->where($where_share)->find();
			$res['share'] = $share;
			$where_send['rid'] = $id;
			$send = $this->redpacket_send->where($where_send)->find();
			$res['send'] = $send;
			return $res;
		}else{
			return array();
		}
	}
	
	/*
	 * 获取商户的信息
	 */
	public function getUserInfo($user_id){
		$where['id'] = $user_id;
		$res = $this->user->where($where)->find();
		if($res){
			return $res;
		}else{
			return array();
		}
	}
}