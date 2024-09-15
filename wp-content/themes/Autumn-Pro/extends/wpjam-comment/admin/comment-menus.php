<?php
class WPJAM_Comment_Menu{
	public static function load_comments_page(){
		$post_type		= wpjam_get_plugin_page_setting('post_type');
		$comment_type	= wpjam_get_plugin_page_setting('comment_type');
		$ct_obj			= wpjam_get_comment_type_object($comment_type);

		include __DIR__.'/comments.php';

		$ct_obj->on_comments_page_load($post_type);

		if(!$ct_obj->post_meta){
			wpjam_register_list_table_column('comment_date', ['title'=>'提交于', 'order'=>1]);
		}

		wpjam_register_list_table('comments', [
			'plural'	=> $ct_obj->plural,
			'singular'	=> $comment_type,
			'model'		=> 'WPJAM_Comments_Admin',
			'per_page'	=> $ct_obj->post_meta ? 9999 : 20,
			'layout'	=> 'left',
			'left_key'	=> 'post_id'
		]);
	}

	public static function on_admin_init(){
		$post_types	= get_post_types(['show_ui'=>true], 'objects');

		foreach($post_types as $post_type=>$pt_obj){
			$comment_types	= wpjam_get_comment_types([],'object');

			foreach($comment_types as $comment_type => $ct_obj){
				if(in_array($post_type, $ct_obj->object_type)){
					wpjam_add_menu_page($post_type.'-'.$ct_obj->plural,  [
						'parent'			=> $post_type.'s',
						'menu_title'		=> $pt_obj->label.$ct_obj->label,
						'comment_type'		=> $comment_type,
						'post_type'			=> $post_type,
						'capability'		=> 'edit_posts',
						'function'			=> 'list',
						'list_table_name'	=> 'comments',
						'load_callback'		=> [self::class, 'load_comments_page']
					]);
				}
			}

			if(post_type_supports($post_type, 'comments')){
				wpjam_add_menu_page($post_type.'-comments-setting', [
					'post_type'		=> $post_type,
					'parent'		=> $post_type.'s',
					'menu_title'	=> '评论设置',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_comments',
					'capability'	=> is_multisite() ? 'manage_sites' : 'manage_options',
					'fields'		=> ['WPJAM_Comment_Setting', 'get_fields']
				]);
			}
		}
	}
}

add_action('wpjam_admin_init',	['WPJAM_Comment_Menu', 'on_admin_init'], 999);