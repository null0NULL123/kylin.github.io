<?php
/*
Plugin Name: WPJAM 编辑器优化
Plugin URI: http://blog.wpjam.com/project/wpjam-sticky-posts/
Description: 优化 WordPress 传统的 TinyMCE 编辑器，添加下划线等按钮，支持截屏贴图等
Version: 2.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_TinyMCE')){
		return;
	}

	include __DIR__.'/includes/class-wpjam-tinymce.php';

	add_filter('mce_buttons',			['WPJAM_TinyMCE', 'filter_mce_buttons']);
	add_filter('mce_buttons_2',			['WPJAM_TinyMCE', 'filter_mce_buttons_2']);
	add_filter('tiny_mce_before_init',	['WPJAM_TinyMCE', 'filter_tiny_mce_before_init']);
	add_filter('mce_external_plugins',	['WPJAM_TinyMCE', 'filter_mce_external_plugins']);
	add_filter('content_save_pre',		['WPJAM_TinyMCE', 'filter_content_save_pre'], 1);
});