<?php
/**
 * 红包活动Model
 */

namespace Admin\Model;
use Think\Model;

class RedPacketModel extends Model
{
    /*
     * 初始化需要的表
     */
    public function __construct()
    {
        $this->redpacket = M('redpacket','wx_');//红包活动表
    }

    /*
     * 获取商家红包活动列表
     *  * @parma $user_id //商家id
     *  @where 搜索条件
     *  @page 当前页数
     *  @pageSize 分页显示几条数据
     */
    public function getRedPacketLists($user_id,$where,$page,$pageSize){
    	$where['user_id'] = $user_id;
        $res = $this->redpacket->where($where)->page($page,$pageSize)->select();
        return $res;
    }
    
    /*
     * 获取当前用户所有的活动总数
     * @parma $user_id //商家id
     */
    public function getRedPacketNums($user_id){
    	$where['user_id'] = $user_id;
    	$count = $this->redpacket->where($where)->count();
    	return $count;
    }
    
    /*
     * 添加活动 
     * return 活动id
     */
   public function addRedPacket($data){
   		$rid = $this->redpacket->add($data);
   		return $rid;
   }
   
   /*
    * 编辑活动
    */
   public function editRedPacket($rid,$data){
   		$where['id'] = $rid;
   		$res = $this->redpacket->where($where)->save($data);
   		return $res;
   }
   
   /*
    * 删除某个活动
    * 将is_delete标识为1 
    */
   public function delRedPacket($rid){
   		$where['id'] = $rid;
   		$data['is_delete'] = 1;
   		$res = $this->redpacket->where($where)->save($data);
   		return $res;
   }
   
   /*
    * 发布活动
    * 将is_release标识为1
    */
   public function releaseRedPacket($rid){
	   	$where['id'] = $rid;
	   	$where['is_delete'] = 0;
	   	$data['is_release'] = 1;
	   	$res = $this->redpacket->where($where)->save($data);
	   	return $res;
   }
   
   /*
    * get数据库链接
    */
   public function getRedpacketConnection(){
   		return $this->redpacket;
   } 
   
   /*
    * 某个活动的信息
    */
   public function getRedPacket($id){
   		$where['id'] = $id;
   		$redPacketInfo = $this->redpacket->where($where)->find();
   		return $redPacketInfo ? $redPacketInfo : array();
   }
  
}