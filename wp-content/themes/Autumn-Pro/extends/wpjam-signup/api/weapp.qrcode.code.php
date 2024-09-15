<?php
$scene	= wpjam_get_parameter('scene',	['method'=>'REQUEST',	'required'=>true]);

$openid	= weapp_get_current_openid();
		
if(is_wp_error($openid)){
	wpjam_send_json($openid);
}

$code	= WEAPP_Signup::scan_qrcode($openid, $scene);

if(is_wp_error($code)){
	wpjam_send_json($code);
}

if(is_array($code)){
	wpjam_send_json([
		'errcode'	=> 'invalid_scene',
		'errmsg'	=> '非法场景值',
	]);
}

wpjam_send_json([
	'code'		=> (int)$code
]);