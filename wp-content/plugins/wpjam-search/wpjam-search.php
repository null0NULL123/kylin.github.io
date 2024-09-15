<?php
/*
Plugin Name: WPJAM 搜索优化
Plugin URI: http://blog.wpjam.com/project/wpjam-search/
Description: 支持限制和关闭 WordPress 搜索。
Version: 2.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Search')){
		return;
	}

	include __DIR__.'/includes/class-search.php';

	$instance	= WPJAM_Search::get_instance();

	add_filter('posts_clauses',	[$instance, 'filter_clauses'], 10, 2);

	if(is_admin()){
		add_action('wpjam_admin_init',		[$instance, 'add_menu_page'], 1);
	}else{
		add_filter('request',				[$instance, 'filter_request'], 999);
		add_filter('document_title_parts',	[$instance, 'filter_document_title_parts'], 999);

		add_action('pre_get_posts',			[$instance, 'on_pre_get_posts'], 1);
		add_action('template_redirect',		[$instance, 'on_template_redirect']);

		if(did_action('weixin_loaded') && weixin_doing_reply()){
			add_filter('weixin_query',	[$instance, 'filter_weixin_query']);
		}
	}
});