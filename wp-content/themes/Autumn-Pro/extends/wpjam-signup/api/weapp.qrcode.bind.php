<?php
$scene	= wpjam_get_parameter('scene',	['method'=>'REQUEST',	'required'=>true]);

$openid	= weapp_get_current_openid();
		
if(is_wp_error($openid)){
	wpjam_send_json($openid);
}

$user	= WEAPP_Signup::scan_qrcode($openid, $scene);

if(is_wp_error($user)){
	wpjam_send_json($user);
}

if(is_numeric($user)){
	wpjam_send_json([
		'errcode'	=> 'invalid_scene',
		'errmsg'	=> '非法场景值',
	]);
}

wpjam_send_json([
	'user_id'	=> (int)$user->ID
]);
