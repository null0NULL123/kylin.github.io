<?php
/*
Plugin Name: WPJAM 社交登录
Plugin URI: https://blog.wpjam.com/project/wpjam-basic/
Description: 使用社交账号登录注册 WordPress，支持一次邀请链接，支持微信服务号 OAuth 2.0 登录和服务号扫码登录。
Author: Denis
Author URI: http://blog.wpjam.com/
Version: 2.0
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || defined('WPJAM_SIGNUP_PLUGIN_DIR')){
		return;
	}

	define('WPJAM_SIGNUP_PLUGIN_DIR',	plugin_dir_path(__FILE__));

	include WPJAM_SIGNUP_PLUGIN_DIR . 'includes/trait-qrcode.php';
	include WPJAM_SIGNUP_PLUGIN_DIR . 'includes/class-signup.php';
	include WPJAM_SIGNUP_PLUGIN_DIR . 'includes/class-invite.php';

	include WPJAM_SIGNUP_PLUGIN_DIR . 'public/hooks.php';

	add_action('init', function(){
		include WPJAM_SIGNUP_PLUGIN_DIR . 'public/weixin.php';
		include WPJAM_SIGNUP_PLUGIN_DIR . 'public/weapp.php';

		include WPJAM_SIGNUP_PLUGIN_DIR . 'public/compat.php';
	});
});

	

