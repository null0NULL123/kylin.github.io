<?php
/*
Plugin Name: WPJAM 文章隐藏
Plugin URI: http://blog.wpjam.com/project/wpjam-platform-hide/
Description: 设置文章在列表页不显示，并且可以根据不同平台进行设置
Version: 2.1
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Platform_Hidden')){
		return;
	}

	include __DIR__.'/includes/class-wpjam-platform-hidden.php';

	add_filter('wp_insert_post_data',		['WPJAM_Platform_Hidden_Setting', 'filter_insert_post_data'], 10, 2);
	add_filter('register_post_type_args',	['WPJAM_Platform_Hidden_Setting', 'filter_register_post_type_args'], 10, 2);
	add_filter('posts_where',				['WPJAM_Platform_Hidden_Setting', 'filter_posts_where'], 10, 2);
	add_filter('wpjam_terms',				['WPJAM_Platform_Hidden_Setting', 'filter_terms'], 10, 3);
	
	if(is_admin() && (!is_multisite() || !is_network_admin())){
		add_action('wpjam_plugin_page_load',	['WPJAM_Platform_Hidden_Setting', 'on_plugin_page_load'] , 1, 2);
		add_action('wpjam_builtin_page_load',	['WPJAM_Platform_Hidden_Setting', 'on_builtin_page_load'] , 1, 2);
	}
});