<?php
/*
 * 接口类
 * 1.用户参与情况的接口
 * 2.获取活动情况的接口
 * 3.用户完成活动的接口
 * 4.用户分享活动的接口
 */
namespace Home\Controller;
use Think\Controller;

class RedPacketController extends Controller {
	private $redis;
	public function _initialize(){
		$this->redis = new \Common\ThinkRedis();
	}
	/*
	 * 用户参与情况的接口
	 * @param id //活动的id
	 * 需要处理的步骤如下
	 * 1.关键字回复或者菜单点击进入此方法，获取用户的openId
	 * 2.根据用户的openId,活动id,随机码加密成身份认证token
	 * 3.跳转活动页面url,url上带上openId,活动id及token
	 */
    public function index(){
		$id = I('get.id',0);
		$key = C('DES_KEY');//加密key
		$merchant_openId = $this->getMerchantOpenId($id);//用户的openid(商家)
		$platform_openId = $this->getPlatformOpenId();//用户的openid(微往联盟)
		if($merchant_openId && $platform_openId){
			//查找redis中是否有本活动入口，没有则需要查询一次数据库
			$entrance = $this->redis->hGet('activity_url','activity_entrance_'.$id);
			if(!$entrance){
				$redpacket = D('Home/RedPacket');
				$res = $redpacket->getActivityInfoById($id);
				if($res){
					$entrance = $res['entrance'];
					$this->redis->hSet('activity_url','activity_entrance_'.$id,$entrance);
				}else{
					$entrance = '';
				}
			}
			if($entrance){
				//判断token是否存在
				$token = $this->redis->hGet('activity_token','token_'.$id.'_'.$platform_openId.'_'.$merchant_openId);
				if(!$token){
					$token = do_mencrypt($platform_openId.$merchant_openId.$id,$key);
					$this->redis->hSet('activity_token','token_'.$id.'_'.$platform_openId.'_'.$merchant_openId,$token);
				}
				//跳转到活动页面
				$params['merchant_openId'] = $merchant_openId;
				$params['platform_openId'] = $platform_openId;
				$params['activityId'] = $id;
				$params['token'] = $token;
				//url上传递参数
				$redirectUrl = add_querystring_var($entrance,$params);
				header("Location:".$redirectUrl);
			}else{
				echo "系统繁忙...";exit;
			}
		}else{
			echo "授权失败";exit;
		}
    }
    
    /*
     * 获取活动信息接口
     * @param merchant_openId //用户openId(商家)
     * @param platform_openId //用户openId(微往联盟)
     * @param activityId //活动id
     * @param token //身份认证
     * 要处理的步骤
     * 1.信息认证
     * 2.获取活动信息
     */
    public function getActivityInfo(){
    	$merchant_openId = I('post.merchant_openId','');
    	$platform_openId = I('post.platform_openId','');
    	$activityId = I('post.activityId','');
    	//身份认证
    	$token = I('post.token','');
    	$token_redis = $this->redis->hGet('activity_token','token_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId);
    	if($token_redis && $token == $token_redis){
    		$activity_info = $this->redis->hGet('activity_info','activity_'.$activityId);
    		if(!$activity_info){
	    		$activity_info = $this->ProcessRedPacketNormalData($activityId);
	    		$this->redis->hSet('activity_info','activity_'.$activityId,$activity_info);
    		}
    		$activity_info = json_decode($activity_info,true);
    		$times_info = $this->judgeRedPacketStatus($activityId);//红包活动设置的时间段,活动状态及金额数量
    		$data = array_merge($activity_info,$times_info);
    		return array(
    			'code' => 1,
    			'message' => 'OK',
    			'data' => $data
    		);
    	}else{
    		return array(
    			'code' => -1,
    			'message' => '权限不足'	
    		);
    	}
    }
    
    /*
     * 序列化活动的基本数据
     * @param id 活动id
     */
    public function ProcessRedPacketNormalData($id){
    	$redpacket = D('Home/RedPacket');
    	$activifyInfo = $redpacket->getActivityInfoById($id);
    	$data = array();
    	if($activifyInfo){
    		$data['id'] = $activifyInfo['id'];//活动id
    		$data['name'] = $activifyInfo['name'];//活动名称
    		$data['isRelease'] = $activifyInfo['is_release'];//活动是否发布
    		$data['isDelete'] = $activifyInfo['is_delete'];//活动是否删除
    		$data['entrance'] = $activifyInfo['entrance'];//活动入口
    		$data['addTime'] = $activifyInfo['add_time'];//活动添加时间
    		$share = $activifyInfo['share'];
    		if($share){
    			$data['shareTitle'] = $share['share_title'];//活动分享标题
    			$data['shareDescription'] = $share['share_description'];//活动分享描述
    			$data['shareImg'] = $share['share_img'];//活动分享图标
    			$data['shareUrl'] = $share['share_url'];//活动分享链接
    		}
    		$send = $activifyInfo['send'];
    		if($send){
    			$data['merchant'] = $send['merchant'];//红包活动发放商家
    			$data['activityName'] = $send['activity_name'];//红包活动发放名称
    			$data['blessWord'] = $send['bless_word'];//红包祝福语
    			$data['remark'] = $send['remark'];//红包备注
    		}
    	}
    	return json_encode($data);
    } 
    
    /*
     * 获取活动的时间，红包金额，数量
     * @param id 活动id
     */
    public function getRedPacketOtherDatas($id){
    	$redpacket = D('Home/RedPacket');
    	$activifyInfo = $redpacket->getActivityInfoById($id);
    	$data = array();
    	$options = $activifyInfo['options'];
    	if($options){
    		$data['options'] = $options;
    	}
    	return $data;
    }
    
    /*
     * 判断红包活动的状态 ,并获取当前或者下一阶段活动的开始时间和结束时间,红包的金额和数量
     * 活动状态有3种  1.未开始 2.进行中 3.已结束
     * @param id 活动id
     */
    public function judgeRedPacketStatus($id){
    	$activity_times_info = $this->redis->hGet('activity_times_info','time_'.$id);
    	if(!$activity_times_info){
    		$redpacket = D('Home/RedPacket');
    		$activifyInfo = $redpacket->getActivityInfoById($id);
    		$activity_times_info = json_encode($activifyInfo['options']);
	    	$this->redis->hSet('activity_times_info','time_'.$id,$activity_times_info);
    	}
    	$now = time();//当前时间
    	$isStart = false;
    	$data = array();
    	$endTimes = array();
    	$startTimes = array();
    	$options = json_decode($activity_times_info,true);
    	if($options){
    		foreach ($options as $option){
    			$start_time = strtotime($option['start_time']);
    			$end_time = strtotime($option['end_time']);
    			$startTimes[] = $start_time;
    			$endTimes[] = $end_time;
    			//在活动设置的某个时间段内
    			if($start_time <= $now && $end_time > $now){
    				$isStart = true;
    				$data['startTime'] = $option['start_time'];
    				$data['endTime'] = $option['end_time'];
    				$data['activityStatus'] = 1;//活动进行中
    				$data['price'] = $option['price'];
    				$data['num'] = $option['num'];
    			}
    		}
    		if(!$isStart){
    			if($startTimes && $endTimes){
    				//获取设置的活动最早开始时间
    				asort($startTimes);
    				$first_time = current($startTimes);
    				//获取设置的最终结束时间
    				asort($endTimes);
    				$last_time = end($endTimes);
    				if($first_time && $now < $first_time){
    					$data['activityStatus'] = 0;//活动未开始
    					$data['startTime'] = date('Y-m-d H:i:s',$first_time);//下一阶段活动开始时间
    					$data['endTime'] = date('Y-m-d H:i:s',$last_time);//下一阶段活动结束时间
    					$data['price'] = $options[0]['price'];
    					$data['num'] = $options[0]['num'];
    				}else if($last_time && $now > $last_time){
    					$data['activityStatus'] = 2;//活动已结束
    				}else{
    					//活动开始了,还未结束,但当前时间不在活动时间范围内
    					foreach ($startTimes as $key=>$val){
    						if($val > $now){
    							$data['activityStatus'] = 0;//活动未开始
    							$data['startTime'] = date('Y-m-d H:i:s',$val);//下一阶段活动开始时间
    							$data['endTime'] = date('Y-m-d H:i:s',$endTimes[$key]);//下一阶段活动结束时间
    							$data['price'] = $options[$key]['price'];
    							$data['num'] = $options[$key]['num'];
    							break;//终止循环
    						}
    					}
    				}
    			}
    		}
    	}
    	return $data;
    }
    
    /*
     * 用户分享活动接口
     * 1.认证身份
     * 2.检测是否分享
     */
    public function shareRedPacket(){
    	$merchant_openId = I('post.merchant_openId','');
    	$platform_openId = I('post.platform_openId','');
    	$activityId = I('post.activityId','');
    	$isShare = I('post.isShare',0);
    	//身份认证
    	$token = I('post.token','');
    	$token_redis = $this->redis->hGet('activity_token','token_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId);
    	if($token_redis && $token == $token_redis){
    		//判断活动状态
    		$activityData = $this->judgeRedPacketStatus($activityId);
    		if(isset($activityData['activityStatus']) && $activityData['activityStatus'] == 1){
    			//活动进行中
    			$share = $this->redis->hGet('activity_share','activity_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId);
    			if(!$share){
    				if($isShare && $isShare == 1){
    					$this->redis->hSet('activity_share','activity_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId,1);
    					return 1;//分享成功
    				}else{
    					return 0;//还未分享
    				}
    			}else{
    				return 1;//OK 用户已分享
    			}
    		}else{
				return 2;//活动未开始或者已经结束    			
    		}
    	}else{
    		return 3;//身份认证不通过
    	}
    }
    
    /*
     * 用户完成活动接口
     * 1.身份认证
     * 2.检测是否分享
     * 3.如果分享了,向红包队列添加信息，表示该用户具备领取红包资格
     */
    public function activitySuccess(){
    	$merchant_openId = I('post.merchant_openId','');
    	$platform_openId = I('post.platform_openId','');
    	$activityId = I('post.activityId','');
    	$isSuccess = I('post.isSuccess',0);//是否完成
    	//身份认证
    	$token = I('post.token','');
    	$token_redis = $this->redis->hGet('activity_token','token_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId);
    	if($token_redis && $token == $token_redis){
    		//判断活动状态
    		$activityData = $this->judgeRedPacketStatus($activityId);
    		if(isset($activityData['activityStatus']) && $activityData['activityStatus'] == 1){
    			//活动进行中
	    		$isAllowGetRedPacket = $this->redis->hGet('activity_allow_redpacket','activity_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId);
	    		if(!$isAllowGetRedPacket){
	    			if($isSuccess && $isSuccess == 1){
	    				//活动完成,检测活动是否已经分享
	    				$share = $this->redis->hGet('activity_share','activity_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId);
	    				if($share){
		    				$this->redis->hSet('activity_allow_redpacket','activity_'.$activityId.'_'.$platform_openId.'_'.$merchant_openId,1);
		    				return 1;//已分享 向红包队列添加信息，具备领取红包资格
	    				}else{
	    					return -1;//活动未分享
	    				}
	    			}else{
	    				return 0;//不能领取红包，活动未完成
	    			}
	    		}else{
	    			return 1;//有领取红包的资格
	    		}
    		}else{
    			return 2;//活动未开始或者已经结束
    		}
    	}else{
    		return 3;//身份认证未通过，不能领取红包
    	}
    }
    
    /*
     * 获取用户的商家openId
     * @param id //活动id
     */
    public function getMerchantOpenId($id){
    	$userAppId = $this->redis->hGet('user_appid',$id);
    	if(!$userAppId){
	    	$redpacket = D('Home/RedPacket');
	    	$activifyInfo = $redpacket->getActivityInfoById($id);
	    	if($activifyInfo){
	    		$userId = $activifyInfo['user_id'];
	    	}else{
	    		$userId = 0;
	    	}
	    	$user = $redpacket->getUserInfo($userId);
    		$userAppId = $user ? $user['user_appid'] : null;
    		$this->redis->hSet('user_appid',$id,$userAppId);
    	}
    	return $this->getOpenId($userAppId);
    }
    
    /*
     * 获取微往联盟平台的openId
     */
    public function getPlatformOpenId(){
    	return $this->getOpenId(C('appid_weiwanglianmeng_open'));
    }
    
    /*
     * 获取openId
     * @userAppId  需要获取的商家的appid
     */
    public function getOpenId($userAppId){
    	$appId = C('appid_weiwanglianmeng_open');
    	$appSecret = C('appsecret_weiwanglianmeng_open');
    	$WeixinOpen = new \Common\WeixinOpen($appId,$appSecret,$userAppId);
    	$session_name = 'OpenId';
    	$WeixinOpen->Oauth($session_name);
    	$openid = session($session_name);
    	return $openid;
    }
    
    public function test(){
    	echo "You are right!";
    }
}