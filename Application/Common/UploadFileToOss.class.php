<?php
namespace Common;
include("FileUpload/aliyun-oss-php-sdk-2.0.4.phar");
use OSS\OssClient;
use OSS\Core\OssException;
class UploadFileToOss{
	//创建OssClient
	function createOssClient(){
		$accessKeyId = "OwBUzPTPBiGipSfT"; ;
		$accessKeySecret = "SjzEzEy2JelPPJm02Xtt65EMI2bnUt";
		$endpoint = "oss-cn-hangzhou.aliyuncs.com";
		try {
			$ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
		} catch (OssException $e) {
			print $e->getMessage();
		}
		return $ossClient;
	}
	
	//文件上传到oss函数
	function uploadFile($ossClient,$bucket,$object,$filePath)
	{
		try{
			$ossClient->uploadFile($bucket, $object, $filePath);
			return "OK";
		} catch(OssException $e) {
			printf(__FUNCTION__ . ": FAILED\n");
			printf($e->getMessage() . "\n");
			return;
		}
		//print(__FUNCTION__ . ": OK" . "\n");
	}
	
	//得到OSS返回的图片路径
	function getSignedUrlForGettingObject($ossClient, $bucket,$object,$timeout)
	{
		//$object = "leke2015-test/1451547739.png";
		try{
			$signedUrl = $ossClient->signUrl($bucket, $object, $timeout);
			return $signedUrl;
		} catch(OssException $e) {
			printf(__FUNCTION__ . ": FAILED\n");
			printf($e->getMessage() . "\n");
			return;
		}
		//print(__FUNCTION__ . ": signedUrl: " . $signedUrl. "\n");
		/**
		 * 可以类似的代码来访问签名的URL，也可以输入到浏览器中去访问
		 */
		/*
		 $request = new RequestCore($signedUrl);
		 $request->set_method('GET');
		 $request->send_request();
		 $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
		 if ($res->isOK()) {
		 print(__FUNCTION__ . ": OK" . "\n");
		 } else {
		 print(__FUNCTION__ . ": FAILED" . "\n");
		 };
		 */
	}
	
	/*
	 * 单个文件上传
	 * @bucket 申请的xxx
	 * @target_directory 要上传到的目标目录
	 * @file 文件名
	 * @filePath 文件路径
	 */
	function scandirFile($bucket,$target_directory,$file,$filePath){
		$ossClient = $this->createOssClient();
		$object=$target_directory.$file;
		$uplpadResult=$this->uploadFile($ossClient,$bucket,$object,$filePath);
		return $uplpadResult;
	}
}
?>