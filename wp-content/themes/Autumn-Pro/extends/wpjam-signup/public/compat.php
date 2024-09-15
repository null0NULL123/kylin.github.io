<?php
function wpjam_signup_get_setting($setting_name){
	return wpjam_get_setting('wpjam-signup', $setting_name);
}

function wpjam_signup_update_setting($setting, $value){
	return wpjam_update_setting('wpjam-signup', $setting, $value);
}

function wpjam_weixin_qrcode_signup($scene, $code, $args=[]){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEIXIN_Signup::qrcode_signup');
	return WEIXIN_Signup::qrcode_signup($scene, $code, $args);
}

function wpjam_create_weixin_qrcode($key, $user_id=0){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEIXIN_Signup::create_qrcode');
	return WEIXIN_Signup::create_qrcode($key, $user_id);
}

function wpjam_weixin_bind($user_id, $openid){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEIXIN_Signup::bind');
	return WEIXIN_Signup::bind($user_id, $openid);
}

function wpjam_weixin_unbind($user_id, $openid=''){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEIXIN_Signup::unbind');
	return WEIXIN_Signup::unbind($user_id, $openid);
}

function wpjam_get_weixin_user($openid){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEIXIN_Signup::get_third_user');
	return WEIXIN_Signup::get_third_user($openid);
}

function wpjam_get_user_weixin_openid($user_id=0){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEIXIN_Signup::get_user_openid');

	$user_id	= $user_id ?: get_current_user_id();
	return WEIXIN_Signup::get_user_openid($user_id);
}

function wpjam_verify_weixin_qrcode($scene, $code){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEIXIN_Signup::verify_qrcode');
	return WEIXIN_Signup::verify_qrcode($scene, $code);
}

function wpjam_weapp_register($openid, $args=[]){
	_deprecated_function(__FUNCTION__, 'WPJAM Signup 2.0', 'WEAPP_Signup::register');
	WEAPP_Signup::register($openid, $args);
}



function weixin_subscribe_code_reply($keyword){
	WEIXIN_Signup::subscribe_reply($keyword);
}

function weixin_scan_code_reply($keyword){
	WEIXIN_Signup::scan_reply($keyword);
}






