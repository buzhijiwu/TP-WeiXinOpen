<?php
/**
 * 红包分享设置Model
 */

namespace Admin\Model;
use Think\Model;

class RedPacketShareModel extends Model
{
	/*
	 * 初始化需要的表
	 */
	public function __construct()
	{
		$this->redpacket_share = M('redpacket_share','wx_');//红包分享信息设置表
	}

	/*
	 * 添加活动分享信息
	 */
	public function addRedPacketShare($rid,$data){
		$data['rid'] = $rid;
		$share_id = $this->redpacket_share->add($data);
		return $share_id;
	}
	
	/*
	 * 编辑活动分享信息
	 * 2种情况 
	 * 1.数据存在直接修改  2.数据不存在则插入
	 */
	public function editRedPacketShare($rid,$data){
		$res = $this->getRedPacketShare($rid);
		if($res){
			$where['rid'] = $rid;
			$res= $this->redpacket_share->where($where)->save($data);
			return $res;
		}else{
			$this->addRedPacketShare($rid,$data);
		}
	}
	
	/*
	 * 获取活动的分享信息
	 */
	public function getRedPacketShare($rid){
		$where['rid'] = $rid;
		$shareInfo = $this->redpacket_share->where($where)->find();
		return $shareInfo ? $shareInfo : array();
	}
}