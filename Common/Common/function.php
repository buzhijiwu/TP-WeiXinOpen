<?php
/**
 * 系统函数库
 */

/**
 * curl请求
 */
function https_request($url, $data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

/**
 * 微信红包
 */
function WeixinHongbao($weixin){
    require './Application/Common/WeixinHongbao.class.php';
    $WeixinHongbao = new WeixinHongbao($weixin);
    return $WeixinHongbao;
}

//获取当前完整URL
function get_url(){
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
    return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}

/**
 * 导出Excle文件
 * $title为一维数组，列数和$data相同
 * $filename为文件名
 */
function ExcleExport($title,$data,$filename){
    if(!$filename){
        $filename = time().rand(0,10000);
    }
    header("Content-type:application/octet-stream");
    header("Accept-Ranges:bytes");
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:attachment;filename=".$filename.".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    if (!empty($title)){
        foreach ($title as $k => $v) {
            $title[$k]=iconv("UTF-8", "GB2312//IGNORE",$v);
        }
        $title= implode("\t", $title);
        echo "$title\n";
    }
    if (!empty($data)){
        foreach($data as $key=>$val){
            foreach ($val as $ck => $cv) {
                $data[$key][$ck]=iconv("UTF-8", "GB2312//IGNORE", $cv);
            }
            $data[$key]=implode("\t", $data[$key]);
        }
        echo implode("\n",$data);
    }
}

/*
 * DES加密算法
 */
function do_mencrypt($input, $key)
{
	$key = substr(md5($key), 0, 24);
	$td = mcrypt_module_open('tripledes', '', 'ecb', '');
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
	$encrypted_data = mcrypt_generic($td, $input);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	return trim(chop(base64_encode($encrypted_data)));
}

/*
 * Des解密算法
 *
 */
function do_mdecrypt($input, $key)
{
	$input = trim(chop(base64_decode($input)));
	$td = mcrypt_module_open('tripledes', '', 'ecb', '');
	$key = substr(md5($key), 0, 24);
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
	$decrypted_data = mdecrypt_generic($td, $input);
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	return trim(chop($decrypted_data));
}
/*
 * 动态为url添加key-value查询参数，如果参数已经存在则会用新的进行覆盖
 */
function add_querystring_var($url, $params) {
	foreach($params as $key=>$param){
		$url = preg_replace('/(.*)(?|&)'.$key.'=[^&]+?(&)(.*)/i','$1$2$4',$url.'&');
		$url=substr($url,0,-1);
		if(strpos($url,'?') === false){
			$url = $url.'?'.$key.'='.$param;
		} else {
			$url = $url.'&'.$key.'='.$param;
		}
	}
	return $url;
}