<?php
add_action('wpjam_admin_init',	function(){
	if(is_multisite() && is_network_admin()){
		if($GLOBALS['plugin_page'] == 'wpjam-topics'){
			wpjam_add_menu_page('wpjam-topics', [
				'parent'			=> 'wpjam-basic',
				'network'			=> true,
				'menu_title'		=> '讨论组',
				'function'			=> 'option',
				'option_name'		=> 'wpjam-topics',
				'sanitize_callback'	=> ['WPJAM_Topic_Setting', 'sanitize_option'],
				'fields'			=> ['WPJAM_Topic_Setting', 'get_fields']
			]);
		}
	}else{
		if(wpjam_get_topic_blog_id()){
			$subs	= [];

			$subs['wpjam-topics']	= [
				'menu_title'	=> wpjam_topic_get_setting('topic_name', '帖子'),
				'function'		=> 'list', 
				'capability'	=> 'manage_topics',
				'map_meta_cap'	=> ['WPJAM_Topic', 'map_meta_cap'],
				'page_file'		=> __DIR__.'/topics.php',
			];

			$subs['wpjam-groups']	= [
				'menu_title'	=> wpjam_topic_get_setting('group_name', '分组'),
				'function'		=> 'list', 
				'plural'		=> 'wpjam-groups',
				'singular'		=> 'wpjam-group',
				'model'			=> 'WPJAM_Groups_Admin',
				'sortable'		=> true,
				'data_type'		=> 'taxonomy',
				'taxonomy'		=> 'group',
				'capability'	=> is_multisite() ? 'manage_sites' : 'manage_options',
				'page_file'		=> __DIR__.'/groups.php'
			];

			if(wpjam_topic_get_setting('topic_type')){
				$subs['wpjam-topic-types']	= [
					'menu_title'	=> '类型',
					'function'		=> 'list',
					'plural'		=> 'wpjam-topic-types',
					'singular'		=> 'wpjam-topic-type',
					'model'			=> 'WPJAM_Topic_Types_Admin',
					'capability'	=> 'manage_options',
					'sortable'		=> true,
					'search'		=> true,
					'page_file'		=> __DIR__.'/topic-types.php'
				];
			}

			if(wpjam_topic_get_setting('account')){
				$subs['wpjam-topic-accounts']	= [
					'menu_title'	=> '账户',
					'page_title'	=> '账户字段',
					'function'		=> 'list',
					'plural'		=> 'wpjam-topic-accounts',
					'singular'		=> 'wpjam-topic-account',
					'model'			=> 'WPJAM_Topic_Accounts_Admin',
					'capability'	=> 'manage_options',
					'sortable'		=> true,
					'search'		=> true,
					'page_file'		=> __DIR__.'/topic-account.php'
				];

				if(wpjam_topic_get_setting('apply')){
					$subs['wpjam-topic-applies']	= [
						'menu_title'		=> '申请',
						'function'			=> 'list',
						'plural'			=> 'wpjam-topic-applies',
						'singular'			=> 'wpjam-topic-apply',
						'capability'		=> 'manage_options',
						'comment_type'		=> 'apply',
						'post_type'			=> 'topic',
						'list_table_name'	=> 'comments',
						'load_callback'		=> ['WPJAM_Comment_Menu', 'load_comments_page']
					];
				}
			}

			if(wpjam_is_topic_blog()){
				$subs['wpjam-topic-setting']	= [
					'menu_title'	=> '设置',
					'function'		=> 'option',
					'option_name'	=> 'wpjam-topics',
					'model'			=> 'WPJAM_Topic_Setting'
				];
			}

			wpjam_add_menu_page('wpjam-topics', [
				'menu_title'	=> wpjam_topic_get_setting('topics_name', '讨论组'),
				'function'		=> 'list', 
				'icon'			=> 'dashicons-format-chat',
				'position'		=> '3.9999',
				'capability'	=> 'manage_topics',
				'map_meta_cap'	=> ['WPJAM_Topic', 'map_meta_cap'],
				'subs'			=> $subs
			]);
		}
	}
});