<?php
/*
Plugin Name: WPJAM 内容模板
Plugin URI: http://blog.wpjam.com/project/wpjam-content-template/
Description: WordPress 内容模板，通过 shortcode 在内容中插入一段共用的内容模板，支持内容和表格模板。
Version: 4.0
Author: Denis
Author URI: http://blog.wpjam.com/
Update URI: http://blog.wpjam.com/project/wpjam-content-template/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Content_Template')){
		return;
	}

	include __DIR__.'/includes/class-wpjam-content-template-setting.php';
	include __DIR__.'/includes/class-wpjam-content-template.php';
	include __DIR__.'/includes/class-wpjam-card-template.php';
	include __DIR__.'/includes/class-wpjam-table-template.php';

	function wpjam_content_template_get_setting($name){
		return WPJAM_Content_Template_Setting::get_instance()->get_setting($name);
	}

	function wpjam_get_content_template_type($post_id){
		return get_post_meta($post_id, '_template_type', true);
	}

	function wpjam_register_content_template_type($name, $args){
		WPJAM_Content_Template_Type::register($name, $args);
	}

	function wpjam_get_content_template_type_object($type){
		return WPJAM_Content_Template_Type::get($type);
	}

	$instance	= WPJAM_Content_Template_Setting::get_instance();

	$instance->register();

	wpjam_register_content_template_type('content', ['title'=>'内容',	'dashicon'=>'edit']);
	wpjam_register_content_template_type('card',	['title'=>'卡片',	'dashicon'=>'index-card',	'model'=>'WPJAM_Card_Template']);
	wpjam_register_content_template_type('table',	['title'=>'表格',	'dashicon'=>'editor-table',	'model'=>'WPJAM_Table_Template']);

	add_action('init', ['WPJAM_Card_Template', 'register_paths']);

	add_shortcode('template',	['WPJAM_Content_Template', 'template_shortcode_callback']);
	add_shortcode('password',	['WPJAM_Content_Template', 'password_shortcode_callback']);
	// add_shortcode('field',		['WPJAM_Content_Template', 'field_shortcode_callback']);

	add_action('wpjam_api',	['WPJAM_Content_Template', 'register_api']);

	add_filter('wpjam_post_json',	['WPJAM_Content_Template', 'filter_post_json'], 11, 2);

	add_filter('post_password_required',	['WPJAM_Content_Template', 'filter_post_password_required'], 10, 2);
	add_filter('protected_title_format',	['WPJAM_Content_Template', 'filter_protected_title_format'], 10, 2);

	add_action('init',			[$instance, 'add_style']);
	add_filter('the_content',	[$instance, 'filter_the_content'], 1);

	if(is_admin()){
		add_action('wpjam_admin_init',			['WPJAM_Content_Template_Setting', 'add_menu_pages']);
		add_action('wpjam_builtin_page_load',	[$instance, 'on_builtin_page_load'], 1, 2);
	}

	if(did_action('weixin_loaded')){
		include __DIR__.'/includes/class-weixin-post-password.php';

		add_filter('the_password_form',		['WEIXIN_Post_Password', 'filter_password_form'], 10, 2);
		add_action('weixin_reply_loaded',	['WEIXIN_Post_Password', 'on_weixin_reply_loaded']);
	}
});
