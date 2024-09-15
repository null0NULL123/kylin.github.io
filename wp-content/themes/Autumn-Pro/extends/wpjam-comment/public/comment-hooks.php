<?php
class WPJAM_Comment_Hook{
	public static function on_registered_post_type($post_type, $pt_obj){
		if($pt_obj->_builtin && !in_array($post_type, ['post','page'])){
			return;
		}

		if(post_type_supports($post_type, 'comments')){
			if($ct_obj = wpjam_get_comment_type_object('comment')){
				wpjam_register_comment_type_for_object_type('comment', $post_type);
			}else{
				$ct_obj = wpjam_register_comment_type('comment', $post_type, [
					'model'		=> 'WPJAM_Post_Comment',
					'label'		=> '评论', 
					'plural'	=> 'comments'
				]);
			}

			foreach(WPJAM_Comment_Feature::get_registereds() as $feature => $feature_obj){
				$default	= null;

				if(method_exists($feature_obj->model, 'get_fields')){
					if($fields = call_user_func([$feature_obj->model, 'get_fields'], 'setting', $post_type)){
						$field		= current($fields);
						$default	= $field['value'] ?? null; 
					}
				}

				if(strpos($feature, 'reply') === false){
					$field_key	= 'comment_'.$feature;
				}else{
					$field_key	= $feature;
				}

				if($value = wpjam_comment_get_setting($post_type.'_'.$field_key, $default)){
					$ct_obj->add_support($post_type, $feature, $value);
				}
			}
		}

		if(post_type_supports($post_type, 'favs')){
			if(wpjam_get_comment_type_object('fav')){
				wpjam_register_comment_type_for_object_type('fav', $post_type);
			}else{
				wpjam_register_comment_type('fav', $post_type, [
					'model'		=> 'WPJAM_Post_Action',
					'label'		=> '收藏',
					'plural'	=> 'favs',
					'action'	=> true,
					'is_did'	=> 'is_faved',
					'icon'		=> ['star-filled', 'star-empty'],
					'class'		=> ['is-faved', 'is-unfaved']
				]);
			}
		}

		if(post_type_supports($post_type, 'likes')){
			if(wpjam_get_comment_type_object('like')){
				wpjam_register_comment_type_for_object_type('like', $post_type);
			}else{
				wpjam_register_comment_type('like', $post_type, [
					'model'		=> 'WPJAM_Post_Like',
					'label'		=> '点赞',
					'plural'	=> 'likes',
					'action'	=> true,
					'post_meta'	=> true,
					'is_did'	=> 'is_liked',
					'icon'		=> ['heart', 'heart'],
					'class'		=> ['is-liked', 'is-unliked']
				]);
			}
		}
	}

	public static function on_pre_get_comments($query){
		if($query->query_vars['post_id'] && empty($query->query_vars['parent__in'])){
			$type	= $query->query_vars['type'] ?: 'comment';

			if($ct_obj = wpjam_get_comment_type_object($type)){
				$ct_obj->on_pre_get_comments($query);
			}
		}

		if(!empty($query->query_vars['author_email__in'])){	// wp_comment_query Only use the args defined in the query_var_defaults to compute cache key,
			$query->query_var_defaults['author_email__in']	= [];
		}
	}

	public static function filter_comments_clauses($clauses, $query){
		if($query->query_vars['post_id'] && empty($query->query_vars['parent__in'])){
			$type	= $query->query_vars['type'] ?: 'comment';
		
			if($ct_obj = wpjam_get_comment_type_object($type)){
				$clauses	= $ct_obj->filter_comments_clauses($clauses, $query);
			}
		}

		if(!empty($query->query_vars['author_email__in'])){
			$author_email__in	= [];

			foreach($query->query_vars['author_email__in'] as $author_email){
				$author_email__in[]	= $GLOBALS['wpdb']->prepare('%s', $author_email);
			}

			$clauses['where']	.= ' AND comment_author_email in ('.implode(',', $author_email__in).')';
		}

		return $clauses;
	}

	public static function get_post_meta_keys(){
		$meta_keys	= [];
		
		foreach(wpjam_get_comment_types([], 'object') as $comment_type => $ct_obj){
			if($comment_type != 'comment' && $ct_obj->plural){
				$meta_keys[]	= $ct_obj->plural;
			}
		}

		return $meta_keys;
	}

	public static function filter_posts_clauses($clauses, $wp_query){
		global $wpdb;

		$orderby	= $wp_query->get('orderby');
		$meta_keys	= self::get_post_meta_keys();

		if($meta_keys && in_array($orderby, $meta_keys)){
			$order	= $wp_query->get('order') ?: 'DESC';

			$clauses['fields']	.= ", (COALESCE(jam_pm.meta_value, 0)+0) as {$orderby}";
			$clauses['join']	.= "LEFT JOIN {$wpdb->postmeta} jam_pm ON {$wpdb->posts}.ID = jam_pm.post_id AND jam_pm.meta_key = '{$orderby}' ";
			$clauses['orderby']	= "{$orderby} {$order}, " . $clauses['orderby'];
		}

		return $clauses;
	}

	public static function filter_is_protected_meta($protected, $meta_key, $meta_type){
		if($meta_type == 'post' && in_array($meta_key, self::get_post_meta_keys())){
			return true;
		}

		return $protected;
	}

	public static function filter_pre_comment_approved($approved, $comment_data){
		if(is_wp_error($approved) || $approved == 0){
			return $approved;
		}

		$post_id	= $comment_data['comment_post_ID'];
		$user_id	= $comment_data['user_id'] ?? '';

		if($user_id && user_can($user_id, 'edit_post', $post_id)){
			return $approved;
		}elseif($ct_obj = wpjam_get_comment_type_object($comment_data['comment_type'])){
			return $ct_obj->filter_pre_comment_approved($approved, $comment_data);
		}else{
			return $approved;
		}
	}

	public static function filter_the_comments($comments, $query){
		if($query->query_vars['post_id'] && empty($query->query_vars['parent__in'])){
			$type	= $query->query_vars['type'] ?: 'comment';

			if($ct_obj = wpjam_get_comment_type_object($type)){
				$comments	= $ct_obj->filter_the_comments($comments, $query);
			}
		}

		return $comments;
	}

	public static function filter_comment_text($text, $comment){
		if(is_singular() && $comment && $comment->comment_post_ID == get_queried_object_id()){
			$type	= $comment->comment_type ?: 'comment';

			if($ct_obj = wpjam_get_comment_type_object($type)){
				$text	= $ct_obj->filter_comment_text($text, $comment);
			}
		}

		return $text;
	}

	public static function filter_is_comment_flood($is_flood, $ip, $email, $date, $avoid_die=false){
		global $wpdb;

		if(current_user_can('manage_options') || current_user_can('moderate_comments')){
			return false;
		}

		$lasttime	= gmdate('Y-m-d H:i:s', time() - 15);

		if(is_user_logged_in()){
			$check_value	= get_current_user_id();
			$check_column	= '`user_id`';
		}else{
			$check_value	= $ip;
			$check_column	= '`comment_author_IP`';
		}

		$sql	= $wpdb->prepare("SELECT `comment_date_gmt` FROM `$wpdb->comments` WHERE `comment_type` = 'comment' AND `comment_date_gmt` >= %s AND ( $check_column = %s OR `comment_author_email` = %s ) ORDER BY `comment_date_gmt` DESC LIMIT 1", $lasttime, $check_value, $email);

		if($wpdb->get_var($sql)){
			if($avoid_die){
				return true;
			}else{
				$comment_flood_message = apply_filters('comment_flood_message', __('You are posting comments too quickly. Slow down.'));

				if(wp_doing_ajax()){
					die( $comment_flood_message );
				}

				wp_die($comment_flood_message, 429);
			}
		}

		return false;
	}

	public static function filter_pre_update_comment_count($count, $old, $post_id){
		$counts		= WPJAM_Comment::get_counts($post_id, ['approved'=>1]);
		$counts		= $counts ? array_column($counts, 'count', 'comment_type') : [];

		$post_type	= get_post_type($post_id);

		foreach(wpjam_get_comment_types([], 'object') as $comment_type => $ct_obj){
			if($comment_type != 'comment'
				&& !$ct_obj->post_meta
				&& in_array($post_type, $ct_obj->object_type)
			){
				$count	= $counts[$comment_type] ?? 0;

				if(get_post_meta($post_id, $ct_obj->plural, true) != $count){
					update_post_meta($post_id, $ct_obj->plural, $count);
				}
			}
		}

		return $counts['comment'] ?? 0;
	}
}


remove_action('check_comment_flood', 'check_comment_flood_db', 10, 4);

add_action('registered_post_type',	['WPJAM_Comment_Hook', 'on_registered_post_type'], 99, 2);
add_action('pre_get_comments',		['WPJAM_Comment_Hook', 'on_pre_get_comments']);

add_filter('wp_is_comment_flood',	['WPJAM_Comment_Hook', 'filter_is_comment_flood'], 10, 5);
add_filter('pre_comment_approved',	['WPJAM_Comment_Hook', 'filter_pre_comment_approved'], 99, 2);
add_filter('posts_clauses',			['WPJAM_Comment_Hook', 'filter_posts_clauses'], 1, 2);
add_filter('is_protected_meta',		['WPJAM_Comment_Hook', 'filter_is_protected_meta'], 10, 3);
add_filter('comments_clauses',		['WPJAM_Comment_Hook', 'filter_comments_clauses'], 10, 2);
add_filter('the_comments',			['WPJAM_Comment_Hook', 'filter_the_comments'], 10, 2);
add_filter('comment_text', 			['WPJAM_Comment_Hook', 'filter_comment_text'], 10, 2);

add_filter('pre_option_comment_whitelist',	'__return_zero');

add_filter('pre_wp_update_comment_count_now',	['WPJAM_Comment_Hook', 'filter_pre_update_comment_count'], 10, 3);
