<?php
/*
Plugin Name: WPJAM 格式文章
Plugin URI: http://blog.wpjam.com/project/wpjam-format/
Description: WPJAM 格式文章
Version: 3.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || defined('WPJAM_FORMAT_PLUGIN_DIR')){
		return;
	}

	define('WPJAM_FORMAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
	
	include __DIR__.'/includes/class-wpjam-format.php';
	
	add_action('after_setup_theme',	['WPJAM_Format', 'on_after_setup_theme']);
	add_action('wpjam_api',			['WPJAM_Format', 'register_api']);

	add_filter('post_link',			['WPJAM_Format', 'filter_post_link'], 10, 2);
	add_filter('get_the_excerpt',	['WPJAM_Format', 'filter_excerpt'], 1, 2);
	add_filter('the_content',		['WPJAM_Format', 'filter_content']);

	add_filter('wpjam_post_thumbnail_url',	['WPJAM_Format', 'filter_post_thumbnail_url'], 10, 2);
	add_filter('wpjam_post_json',			['WPJAM_Format', 'filter_post_json'], 10, 2);
	add_filter('wpjam_related_post_json',	['WPJAM_Format', 'filter_related_post_json'], 10, 2);

	if(is_admin()){
		add_action('wpjam_builtin_page_load',	['WPJAM_Format', 'on_builtin_page_load'], 10, 2);
	}
});