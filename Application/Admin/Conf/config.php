<?php
return array(
    //view路径配置
    'TMPL_PARSE_STRING' => array(
        '__viewPath__'     =>  __ROOT__.'/Application/Admin/View/',
        '__adminPublic__'  =>  __ROOT__.'/Application/Admin/View/public',
    ),
	//分页每页显示多少个
	'PAGE_SIZE' => 10,
	'UPLOAD_FILE_BUCKET' => 'zshhb',
	'UPLOAD_TARGET_DIRECTORY' => 'hbpt/', //阿里云服务器上文件上传的路径
	'UPLOAD_IMAGE_TIMEOUT' => 3600, //访问上传的图片url有效期 3600秒
);