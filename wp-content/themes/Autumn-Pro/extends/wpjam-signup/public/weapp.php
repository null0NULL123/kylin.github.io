<?php
if(wp_is_mobile() && !is_weixin() && !is_weapp()){
	return;
}

if(!defined('WEAPP_PLUGIN_DIR')){
	return;
}

include WPJAM_SIGNUP_PLUGIN_DIR . 'includes/class-weapp-signup.php';

if(!WEAPP_Signup::get_bind_page()){
	return;
}

if(is_multisite()){
	if(is_weapp()){
		$weapp_appid	= weapp_get_appid();
	}elseif($weapp_settings	= WEAPP_Setting::get_by('blog_id', get_current_blog_id())){
		$weapp_appid	= $weapp_settings[0]['appid'];
	}else{
		$weapp_appid	= '';
	}
}else{
	$weapp_appid	= weapp_get_appid();
}

if(empty($weapp_appid)){
	return;
}

WEAPP_Signup::set_appid($weapp_appid);

wpjam_register_signup('weapp', [
	'title'			=>'微信小程序',	
	'model'			=>'WEAPP_Signup',
	'login_title'	=>'微信小程序扫码登录',
	'default'		=>false
]);

function weapp_signup($openid, $args=[]){
	return WEAPP_Signup::signup($openid, $args);
}

add_action('wp_ajax_nopriv_weapp-qrcode-signup',	['WEAPP_Signup', 'ajax_qrcode_signup']);
add_action('wp_ajax_weapp-qrcode-signup',			['WEAPP_Signup', 'ajax_qrcode_signup']);
add_action('wp_ajax_weapp-qrcode-bind',				['WEAPP_Signup', 'ajax_qrcode_bind']);
add_action('wp_ajax_weapp-unbind',					['WEAPP_Signup', 'ajax_unbind']);

add_action('wpjam_api', ['WEAPP_Signup', 'register_api']);