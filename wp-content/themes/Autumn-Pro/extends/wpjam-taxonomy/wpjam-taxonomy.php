<?php
/*
Plugin Name: WPJAM 分类管理
Plugin URI: https://blog.wpjam.com/project/wpjam-taxonomy/
Description: 层式管理分类和分类拖动排序，支持设置分类的层级，并且在 WordPress 后台分类管理界面可以按层级显示和拖动排序。
Version: 4.1
Author: Denis
Author URI: https://blog.wpjam.com/
Update URI: https://blog.wpjam.com/project/wpjam-taxonomy/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Taxonomy_Setting')){
		return;
	}

	include __DIR__.'/public/taxonomy-utils.php';
	include __DIR__.'/public/taxonomy-hooks.php';

	if(is_admin()){
		include __DIR__.'/admin/taxonomy-menus.php';
		include __DIR__.'/admin/taxonomy-builtins.php';
	}
});
