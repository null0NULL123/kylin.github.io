<?php
/*
Plugin Name: WPJAM 短信系统
Plugin URI: http://blog.wpjam.com/project/wpjam-basic/
Description: WPJAM 短信系统
Version: 2.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || defined('WPJAM_SMS_PLUGIN_DIR')){
		return;
	}

	define('WPJAM_SMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
	define('WPJAM_SMS_PLUGIN_FILE', __FILE__);
	
	include WPJAM_SMS_PLUGIN_DIR . 'includes/class-sms.php';
	
	include WPJAM_SMS_PLUGIN_DIR . 'public/utils.php';
	include WPJAM_SMS_PLUGIN_DIR . 'public/hooks.php';
});