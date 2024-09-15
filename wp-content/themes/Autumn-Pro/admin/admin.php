<?php
include TEMPLATEPATH .'/admin/hooks/admin-menus.php';

add_action('wpjam_post_page_file', function($post_type){
	if($post_type == 'post'){
		require TEMPLATEPATH .'/admin/post/post-options.php';
	}
});

add_action('wpjam_post_list_page_file', function($post_type){
	if($post_type == 'post'){
		require TEMPLATEPATH .'/admin/post/post-options.php';
	}
});

add_action('wpjam_term_list_page_file', function($taxonomy){
	if($taxonomy == 'category'){
		require TEMPLATEPATH .'/admin/post/term-options.php';
	}
});

add_action('wpjam_term_page_file', function($taxonomy){
	if($taxonomy == 'category'){
		require TEMPLATEPATH .'/admin/post/term-options.php';
	}
});