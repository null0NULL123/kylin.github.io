<?php
/*
Plugin Name: WPJAM 置顶文章
Plugin URI: http://blog.wpjam.com/project/wpjam-sticky-posts/
Description: 全局置顶文章排序，分类置顶文章。
Version: 2.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Sticky_Posts')){
		return;
	}

	include __DIR__.'/includes/class-wpjam-sticky-post.php';

	if(is_admin()){
		add_action('wpjam_plugin_page_load',	['WPJAM_Sticky_Posts', 'on_plugin_page_load'], 10, 2);
		add_action('wpjam_builtin_page_load',	['WPJAM_Sticky_Posts', 'on_builtin_page_load'], 10, 2);
	}else{
		add_filter('the_posts',	['WPJAM_Sticky_Posts', 'filter_the_posts'], 10, 2);
	}
});
