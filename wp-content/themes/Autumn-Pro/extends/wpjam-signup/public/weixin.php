<?php
if(wp_is_mobile() && !is_weixin() && !is_weapp()){
	return;
}

if(is_multisite()){
	if(!defined('WEIXIN_ROBOT_PLUGIN_DIR') && is_dir(WP_PLUGIN_DIR.'/weixin-robot-advanced/')){
		define('WEIXIN_ROBOT_PLUGIN_DIR',	WP_PLUGIN_DIR . '/weixin-robot-advanced/');

		include WEIXIN_ROBOT_PLUGIN_DIR . 'public/weixin-utils.php';
		include WEIXIN_ROBOT_PLUGIN_DIR . 'includes/class-weixin-setting.php';
		include WEIXIN_ROBOT_PLUGIN_DIR . 'includes/class-weixin.php';
		include WEIXIN_ROBOT_PLUGIN_DIR . 'includes/trait-weixin.php';
		include WEIXIN_ROBOT_PLUGIN_DIR . 'includes/class-weixin-user.php';
	}
}

if(!defined('WEIXIN_ROBOT_PLUGIN_DIR')){
	return;
}

include WPJAM_SIGNUP_PLUGIN_DIR . 'includes/class-weixin-signup.php';

if(is_multisite()){
	$bind_blog_id	= WEIXIN_Signup::get_bind_blog_id();

	if(!$bind_blog_id){
		return;
	}

	if($weixin_settings	= WEIXIN_Setting::get_by('blog_id', $bind_blog_id)){
		$weixin_appid	= $weixin_settings[0]['appid'];
	}else{
		$weixin_appid	= '';
	}
}else{
	$weixin_appid	= weixin_get_appid();
}

if(empty($weixin_appid)){
	return;
}

if(weixin_get_type($weixin_appid) < 4){
	return;
}

WEIXIN_Signup::set_appid($weixin_appid);

wpjam_register_signup('weixin', [
	'title'			=>'微信公众号',
	'model'			=>'WEIXIN_Signup',
	'login_title'	=>'微信公众号扫码登录',
	'default'		=>true
]);

function weixin_signup($openid, $args=[]){
	return WEIXIN_Signup::signup($openid, $args);
}

add_action('wp_ajax_nopriv_weixin-qrcode-signup',	['WEIXIN_Signup', 'ajax_qrcode_signup']);
add_action('wp_ajax_weixin-qrcode-signup',			['WEIXIN_Signup', 'ajax_qrcode_signup']);
add_action('wp_ajax_weixin-qrcode-bind',			['WEIXIN_Signup', 'ajax_qrcode_bind']);
add_action('wp_ajax_weixin-unbind',					['WEIXIN_Signup', 'ajax_unbind']);

if(WEIXIN_Signup::is_bind_blog()){
	add_action('wpjam_api',	['WEIXIN_Signup', 'register_api']);

	add_action('weixin_reply_loaded', function(){
		weixin_register_reply('subscribe',	['type'=>'full',	'reply'=>'未关注扫码回复',	'callback'=>['WEIXIN_Signup','subscribe_reply']]);
		weixin_register_reply('scan',		['type'=>'full',	'reply'=>'已关注扫码回复',	'callback'=>['WEIXIN_Signup','scan_reply']]);
	});
}

// add_action('wpjam_message', function($data){

// 	if($weixin_openid	= get_user_meta($data['receiver'], WEIXIN_BIND_META_KEY, true)){
// 		$send_user = get_userdata($data['sender']);

// 		include_once(WPJAM_BASIC_PLUGIN_DIR.'include/class-weixin.php');
// 		weixin()->send_custom_message($weixin_openid, $send_user->display_name."给你发送了一条消息：\n\n".$data['content']);
// 	}
// });