<?php
/**
 * 红包设置Model
 */

namespace Admin\Model;
use Think\Model;

class RedPacketOptionModel extends Model
{
	/*
	 * 初始化需要的表
	 */
	public function __construct()
	{
		$this->redpacket_option = M('redpacket_option','wx_');//红包活动表
	}

	/*
	 * 获取红包活动总红包数量
	 * @param $rid 活动id
	 * return 红包总数
	 */
	public function getTotalRedPacketNums($rid){
		$data['rid'] = $rid;
		$num = $this->redpacket_option->where($data)->sum('num');
		return $num;

	}
	
	 /* 获取红包活动总金额
	 * @param $rid 活动id
	 * return 红包总金额
	 */
	public function getTotalRedPacketPrices($rid){
		$options = $this->getRedPacketOptions($rid);
		$prices = 0;
		if($options){
			foreach ($options as $option){
				$prices += $option['price'] * $option['num'];
			}
		}
		return $prices;
	}
	
	/*
	 * 获取某段时间内红包活动的总数量
	 * @param $id
	 * return 红包总数
	 */
	public function getSingleRedPacketNums($id){
		$data['id'] = $id;
		$num = $this->redpacket_option->where($data)->getField('num');
		return $num;
	}
	
	/*
	 * 获取某段时间内红包活动的总金额
	 * @param $rid 活动id
	 * return 红包总数
	 */
	public function getSingleRedPacketPrices($id){
		$data['id'] = $id;
		$price = $this->redpacket_option->where($data)->getField('price');
		return $price;
	}
	
	/*
	 * 添加红包活动规则
	 */
	public function addRedPacketOption($rid,$data){
		$data['rid'] = $rid;
		$id = $this->redpacket_option->add($data);
		return $id;
	}
	
	/*
	 * 删除活动规则
	 */
	public function delRedPacketOptions($rid){
		$where['rid'] = $rid;
		$res = $this->redpacket_option->where($where)->delete();
	}
	
	/*
	 * 获取活动的规则设置信息
	 * @param $rid 活动id
	 */
	public function getRedPacketOptions($rid){
		$where['rid'] = $rid;
		$redPacketOptionInfo = $this->redpacket_option->where($where)->select();
		return $redPacketOptionInfo ? $redPacketOptionInfo : array();
	}
	
	/*
	 * 获取活动的某个规则设置信息
	 * @param $id 规则id
	 */
	public function getRedPacketOption($id){
		$where['id'] = $id;
		$redPacketOptionInfo = $this->redpacket_option->where($where)->find();
		return $redPacketOptionInfo ? $redPacketOptionInfo : array();
	}
}