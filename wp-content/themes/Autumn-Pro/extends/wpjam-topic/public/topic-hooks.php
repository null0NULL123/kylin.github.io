<?php
class WPJAM_Topic_Hook{
	public static function registered_callback($post_type, $pt_obj){
		include dirname(__DIR__).'/includes/class-topic-apply.php';

		$ct_obj	= wpjam_register_comment_type('apply', $post_type, [
			'model'		=> 'WPJAM_Topic_Apply',
			'label'		=> '申请',
			'action'	=> true,
			'approve'	=> 0
		]);

		$ct_obj->add_support($post_type, 'images', true);
	}

	public static function on_pre_get_posts($query){
		if($query->is_main_query() 
			&& $query->get('post_type') == 'topic'
			&& !$query->get('orderby')
		){
			$query->set('orderby', 'last_comment_time');
		}
	}

	public static function filter_insert_post_data($data, $postarr){
		if(isset($postarr['post_type']) && $postarr['post_type'] == 'topic'){
			$result	= wpjam_blacklist_check(maybe_serialize($postarr), '发布的内容');

			$errmsg = '';

			if(is_wp_error($result)){
				$errmsg = $result->get_error_message();
			}elseif($result){
				$errmsg = '名称中含有非法字符';
			}

			if($errmsg){
				if(wp_doing_ajax() || wpjam_is_json_request()){
					wpjam_send_json(new WP_Error('blacklist_check_error', $errmsg));
				}else{
					wp_die($errmsg);
				}
			}

			if(isset($postarr['last_comment_time'])){
				$data['last_comment_time']	= $postarr['last_comment_time'];
			}

			if(wpjam_topic_get_setting('topic_type')){
				if(isset($postarr['topic_type'])){
					if(!wpjam_topic_get_type_setting($postarr['topic_type'])){
						return new WP_Error('invalid_topic_type', '无效的类型');
					}

					$data['topic_type']	= $postarr['topic_type'];
				}
			}

			if(wpjam_topic_get_setting('account')){
				if(isset($postarr['account_id'])){
					$data['account_id']	= (int)$postarr['account_id'];
				}			
			}

			if(wpjam_topic_get_setting('apply')){
				if(isset($postarr['apply_status'])){
					$data['apply_status']	= (int)$postarr['apply_status'];
				}
			}
		}

		return $data;
	}

	public static function filter_document_title_parts($title){
		if(is_post_type_archive() || is_home()){
			$topic_type	= $GLOBALS['wp_query']->get('topic_type', null);

			if(!is_null($topic_type)){
				if($type_setting = wpjam_topic_get_type_setting($topic_type)){
					if(is_home()){
						$title['site']	= $title['title'];

						if(isset($title['tagline'])){
							unset($title['tagline']);
						}
					}
					
					$title['title']	= $type_setting['sub_name'] ?? $type_setting['name'];
				}
			}
		}

		return $title;
	}

	public static function filter_posts_clauses($clauses, $query){
		if(!$query->is_single && $query->get('post_type') == 'topic'){
			$posts_table	= $GLOBALS['wpdb']->posts;

			if(wpjam_topic_get_setting('topic_type')){
				if(!is_null($query->get('topic_type', null))){
					$topic_type		= $query->get('topic_type');
					$type_setting	= wpjam_topic_get_type_setting($topic_type);

					if($type_setting && !isset($type_setting['sub_slug'])){
						$topic_type	= array_column($type_setting['columns'], 'slug');
					}

					if(is_array($topic_type)){
						$clauses['where']	.= " AND {$posts_table}.topic_type IN ('".join("','",$topic_type) ."')";
					}else{
						$clauses['where']	.= " AND {$posts_table}.topic_type = '{$topic_type}'";
					}
				}
			}

			if(wpjam_topic_get_setting('account')){
				if($account_id = (int)$query->get('account_id')){
					$clauses['where']	.= " AND {$posts_table}.account_id = '{$account_id}'";	
				}
			}

			if(wpjam_topic_get_setting('apply')){
				if($apply_status = (int)$query->get('apply_status')){
					$clauses['where']	.= " AND {$posts_table}.apply_status = '{$apply_status}'";	
				}
			}
		}

		return $clauses;
	}

	public static function filter_posts_orderby($orderby, $query){
		if($query->get('orderby') == 'last_comment_time'){
			$order	= $query->get('order') ?: 'DESC';
			return 'last_comment_time '.$order;
		}

		return $orderby;
	}

	public static function filter_user_has_cap($allcaps){
		return array_merge($allcaps, ['read'=>1]);
	}

	public static function filter_account_meta_fields($meta_fields){
		return array_merge($meta_fields, wpjam_get_option('wpjam-topic-accounts'));
	}
}

if(wpjam_is_topic_blog()){
	$public			= true;
	$has_archive	= true;
	$permastruct	= 'topic/%post_id%/';
}else{
	$public			= false;
	$has_archive	= false;
	$permastruct	= '';
}

$topic_name	= wpjam_topic_get_setting('topic_name', '帖子');
$group_name	= wpjam_topic_get_setting('group_name', '分组');

$supports	= ['title','editor'];

if(wpjam_topic_get_setting('account')){
	if(wpjam_topic_get_setting('comments', true)){
		$supports[]	= 'comments';
	}
}else{
	$supports[]	= 'author';
	$supports[]	= 'comments';
}

$topic_args	= [
	'label'					=> $topic_name,
	'public'				=> $public,
	'exclude_from_search'	=> true,
	'show_ui'				=> false,
	'show_in_nav_menus'		=> false,
	'rewrite'				=> false,
	'query_var'				=> false,
	'has_archive'			=> $has_archive,
	'supports'				=> $supports,
	'permastruct'			=> $permastruct
];

$group_args	= [
	'label'				=> $group_name,
	'public'			=> $public,
	'hierarchical'		=> true,
	'show_ui'			=> false,
	'show_in_nav_menus'	=> true,
	'rewrite'			=> false,
	'query_var'			=> true,
	'sortable'			=> true,
	'object_type'		=> ['topic'],
	'capabilities'		=> [
		'manage_terms'	=> 'manage_categories',
		'edit_terms'	=> 'edit_categories',
		'delete_terms'	=> 'delete_categories',
		'assign_terms'	=> 'read',
	],
	'supports'		=> ['name', 'slug', 'order'],
	'levels'		=> 1
];

if(wpjam_topic_get_setting('apply')){
	$topic_args['registered_callback']	= ['WPJAM_Topic_Hook', 'registered_callback'];
}

wpjam_register_post_type('topic',	$topic_args);
wpjam_register_taxonomy('group',	$group_args);

if(wpjam_topic_get_setting('account')){
	wpjam_register_term_option('prompt', [
		'title'			=> '设置提示',
		'page_title'	=> '设置提示',
		'taxonomy'		=> 'group',
		'order'			=> 10,
		'fields'		=> ['prompt'=>['title'=>'输入提示',	'type'=>'textarea',	'show_in_rest'=>true]]
	]);

	add_filter('wpjam_account_meta_fields',	['WPJAM_Topic_Hook', 'filter_account_meta_fields'], 999);
}

if(wpjam_topic_get_setting('topic_type')){
	add_filter('document_title_parts',	['WPJAM_Topic_Hook', 'filter_document_title_parts'], 999);

	if(did_action('weapp_loaded')){
		weapp_register_path('resource_list', [
			'title'		=> '供应需求列表页',
			'path'		=> '/pages/resourcePage/resourcePage',
			'tabbar'	=> true
		]);

		weapp_register_path('recruit_list', [
			'title'		=> '招聘求职列表页',
			'path'		=> '/pages/recruitPage/recruitPage',
			'tabbar'	=> true
		]);
		
		weapp_register_path('me', [
			'title'		=> '个人中心',
			'path'		=> '/pages/me/me',	
			'tabbar'	=> true
		]);

		weapp_register_path('topic', [
			'title'		=> '帖子详情页',
			'page_type'	=> 'post_type',
			'post_type'	=> 'topic',
			'path'		=> '/pages/resourceDetail/resourceDetail?id=%post_id%'
		]);
	}
}

add_action('pre_get_posts',			['WPJAM_Topic_Hook', 'on_pre_get_posts']);
add_filter('user_has_cap',			['WPJAM_Topic_Hook', 'filter_user_has_cap']);
add_filter('wp_insert_post_data',	['WPJAM_Topic_Hook', 'filter_insert_post_data'], 10, 2);
add_filter('posts_orderby',			['WPJAM_Topic_Hook', 'filter_posts_orderby'], 10, 2);
add_filter('posts_clauses',			['WPJAM_Topic_Hook', 'filter_posts_clauses'], 10, 2);