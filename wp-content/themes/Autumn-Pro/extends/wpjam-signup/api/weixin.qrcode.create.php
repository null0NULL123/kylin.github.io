<?php
$key	= wpjam_get_parameter('key');

if(empty($key)){
	wpjam_send_json(['errcode'=>'empty_key', 'errmsg'=>'KEY值不能为空']);
}

$wpjam_qrcode	= WEIXIN_Signup::create_qrcode($key);

if(is_wp_error($wpjam_qrcode)){
	wpjam_send_json(['errcode'=>'qrcode_create_failed', 'errmsg'=>'二维码创建失败，请稍后重试！']);
}

wpjam_send_json([
	'scene'		=> $wpjam_qrcode['scene'],
	'ticket'	=> $wpjam_qrcode['ticket']
]);
