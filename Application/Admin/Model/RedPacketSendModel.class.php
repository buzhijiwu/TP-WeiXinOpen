<?php
/**
 * 红包发放设置Model
 */

namespace Admin\Model;
use Think\Model;

class RedPacketSendModel extends Model
{
	/*
	 * 初始化需要的表
	 */
	public function __construct()
	{
		$this->redpacket_send = M('redpacket_send','wx_');//红包发放设置表
	}

	/*
	 * 添加活动分享信息
	 */
	public function addRedPacketSend($rid,$data){
		$data['rid'] = $rid;
		$share_id = $this->redpacket_send->add($data);
		return $share_id;
	}
	
	/*
	 * 编辑活动分享信息
	 * 2种情况 
	 * 1.数据存在直接修改  2.数据不存在则插入
	 */
	public function editRedPacketSend($rid,$data){
		$res = $this->getRedPacketSend($rid);
		if($res){
			$where['rid'] = $rid;
			$res= $this->redpacket_send->where($where)->save($data);
			return $res;
		}else{
			$this->addRedPacketSend($rid,$data);			
		}
	}
	
	/*
	 * 获取活动的分享信息
	 */
	public function getRedPacketSend($rid){
		$where['rid'] = $rid;
		$sendInfo = $this->redpacket_send->where($where)->find();
		return $sendInfo ? $sendInfo : array();
	}
}