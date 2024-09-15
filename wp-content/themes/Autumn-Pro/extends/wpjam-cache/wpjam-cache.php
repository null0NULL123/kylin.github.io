<?php
/*
Plugin Name: WPJAM 深度优化
Plugin URI: https://blog.wpjam.com/project/wpjam-cache/
Description: 深度优化 WordPress
Version: 2.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || defined('WPJAM_CACHE_PLUGIN_DIR')){
		return;
	}

	define('WPJAM_CACHE_PLUGIN_DIR', plugin_dir_path(__FILE__));

	include WPJAM_CACHE_PLUGIN_DIR.'public/utils.php';
	
	include WPJAM_CACHE_PLUGIN_DIR.'public/post-query.php';
	include WPJAM_CACHE_PLUGIN_DIR.'public/post-meta.php';

});