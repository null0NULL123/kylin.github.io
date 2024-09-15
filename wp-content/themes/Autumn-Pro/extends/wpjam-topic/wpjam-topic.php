<?php
/*
Plugin Name: WPJAM 讨论组
Plugin URI: http://blog.wpjam.com/project/wpjam-basic/
Description: WPJAM 讨论组
Version: 2.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Topic')){
		return;
	}

	include __DIR__.'/includes/class-topic.php';

	include __DIR__.'/public/topic-utils.php';
	include __DIR__.'/public/topic-hooks.php';
	include __DIR__.'/public/topic-api.php';

	if(is_admin()){
		include __DIR__.'/admin/topic-menus.php';
	}
	
});