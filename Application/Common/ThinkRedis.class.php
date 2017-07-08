<?php
/**
 * Redis公共库
 */
namespace Common;

class ThinkRedis {
    private $redis;
    /**
     * 初始化配置信息：TP_REDIS_HOST(IP)，TP_REDIS_PORT(端口)，TP_REDIS_AUTH(密码)
     */
    public function __construct() {
        $this->redis  = new \Redis;
        $this->redis->connect(C('TP_REDIS_HOST'),C('TP_REDIS_PORT'));
        if (C('TP_REDIS_AUTH')) {
            $this->redis->auth(C('TP_REDIS_AUTH'));
        }
    }

    //设置一个KEY-VALUE，如果存在就覆盖
    public function set($key,$value) {
        return $this->redis->set($key,$value);
    }

    //设置一个KEY-VALUE，如果存在就返回False
    public function setnx($key,$value){
        return $this->redis->setnx($key,$value);
    }

    //根据KEY获取对应的值
    public function get($key) {
        return $this->redis->get($key);
    }

    //移除已经存在KEYS
    public function del($key){
        return $this->redis->del($key);
    }

    //验证一个指定的KEY是否存在
    public function exists($key){
        return $this->redis->exists($key);
    }

    //添加一个VALUE到HASH中，如果存在则覆盖
    public function hSet($key,$hashKey,$value){
        return $this->redis->hSet($key,$hashKey,$value);
    }

    //添加一个VALUE到HASH中，如果存在就返回False
    public function hSetNx($key,$hashKey,$value){
        return $this->redis->hSetNx($key,$hashKey,$value);
    }

    //取得HASH中的VALUE，如果HASH不存在，或者KEY不存在返回FLASE
    public function hGet($key,$hashKey){
        return $this->redis->hGet($key,$hashKey);
    }

    //删除HASH表中指定的元素
    public function hDel($key,$hashKey){
        return $this->redis->hDel($key,$hashKey);
    }

    //取得HASH表中的KEYS，以数组形式返回
    public function hKeys($key){
        return $this->redis->hKeys($key);
    }

    //取得HASH表中所有的VALUE，以数组形式返回
    public function hVals($key){
        return $this->redis->hVals($key);
    }

    //取得整个HASH表的信息，返回一个以KEY为索引VALUE为内容的数组
    public function hGetAll($key){
        return $this->redis->hGetAll($key);
    }

    //设置KEY的过期时间，单位秒
    public function expire($key,$time){
        return $this->redis->expire($key,$time);
    }

    //切换数据库,$db表示数据库的ID值
    public function select($db){
        return $this->redis->select($db);
    }


}