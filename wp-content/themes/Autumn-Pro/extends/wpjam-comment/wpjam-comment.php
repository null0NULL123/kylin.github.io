<?php
/*
Plugin Name: WPJAM 评论增强
Plugin URI: http://blog.wpjam.com/project/wpjam-comment/
Description: 1. 评论点赞，2. 评论置顶，3. 评论点赞排序。
Version: 4.1
Author: Denis
Author URI: http://blog.wpjam.com/
Update URI: http://blog.wpjam.com/project/wpjam-comment/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Comment')){
		return;
	}

	include __DIR__.'/includes/class-comment-type.php';
	include __DIR__.'/includes/class-comment.php';
	include __DIR__.'/includes/class-post-comment.php';

	include __DIR__.'/public/comment-utils.php';
	include __DIR__.'/public/comment-hooks.php';
	include __DIR__.'/public/comment-api.php';
	include __DIR__.'/public/action-button.php';

	include __DIR__.'/public/comment-features.php';
	include __DIR__.'/public/comment-sticky.php';
	include __DIR__.'/public/comment-digg.php';
	include __DIR__.'/public/comment-rating.php';
	include __DIR__.'/public/reply-type.php';

	if(is_admin()){
		include __DIR__.'/admin/comment-builtin.php';
		include __DIR__.'/admin/comment-menus.php';
	}

	do_action('wpjam_comment_loaded');
});