<?php
class WPJAM_Comment_API{
	public static function register($json){
		if(wpjam_get_api($json)){
			return;
		}

		$json_parts	= explode('.', $json);
		$post_type	= $json_parts[0];

		if(!post_type_exists($post_type)){
			return;
		}

		foreach(wpjam_get_comment_types([], 'object') as $comment_type => $ct_obj){
			if($post_type != 'post' && !in_array($post_type, $ct_obj->object_type)){
				continue;
			}

			$label	= $ct_obj->label;

			if($ct_obj->action){
				if($json == $post_type.'.'.$comment_type){
					return wpjam_register_api($json, [
						'auth'			=> true,
						'status'		=> 1,
						'comment_type'	=> $comment_type,
						'callback'		=> [self::class, 'api_comment']
					]);
				}

				if($json == $post_type.'.un'.$comment_type){
					return wpjam_register_api($json, [
						'auth'			=> true,
						'status'		=> 0,
						'comment_type'	=> $comment_type,
						'callback'		=> [self::class, 'api_comment']
					]);
				}
			}else{
				if($json == $post_type.'.'.$comment_type){
					return wpjam_register_api($json, [
						'auth'			=> true,
						'comment_type'	=> $comment_type,
						'callback'		=> [self::class, 'api_comment']
					]);
				}

				if($json == $post_type.'.'.$comment_type.'.get'){
					return wpjam_register_api($json, [
						'page_title'	=> $label.'详情',
						'callback'		=> [self::class, 'api_comment_get']
					]);
				}
			}

			if($json == $post_type.'.'.$comment_type.'.list'){
				$args	= [
					'comment_type'	=> $comment_type,
					'post_type'		=> $post_type,
					'callback'		=> [self::class, 'api_comment_list'],
				];

				if(wpjam_get_parameter('post_id') == null){
					$args	= array_merge($args, [
						'page_title'	=> '我的'.$label,
						'auth'			=> true,
						'output'		=> $ct_obj->action ? $post_type.'s' : $ct_obj->plural
					]);
				}else{
					$args	= array_merge($args, [
						'page_title'	=> $label.'列表',
						'output'		=> $ct_obj->plural
					]);
				}

				return wpjam_register_api($json, $args);
			}

			$ct_obj->register_api($json, $post_type);
		}
	}
	
	public static function api_comment($args){
		$post_id	= (int)wpjam_get_parameter('post_id', ['method'=>'POST', 'required'=>true]);
		$post_type	= get_post_type($post_id);

		if(!$post_type){
			return new WP_Error('invalid_post_id', '无效的 post_id');;
		}

		$comment_type	= $args['comment_type'];

		$ct_obj	= wpjam_get_comment_type_object($comment_type);

		if(!wpjam_is_object_in_comment_type($post_type, $comment_type)){
			return new WP_Error($comment_type.'_not_supported', '操作不支持');
		}

		if('publish' != get_post_status($post_id)){
			return new WP_Error('invalid_post_status', '文章未发布，不能'.$ct_obj->label.'。');
		}

		$args	= wpjam_array_except($args, ['auth', 'callback']);

		return $ct_obj->api_comment($post_id, $args);
	}

	public static function api_comment_get(){
		$comment_id		= (int)wpjam_get_parameter('id', ['required'=>true]);
		$comment_json	= wpjam_get_comment($comment_id);
		
		if(empty($comment_json)){
			return new WP_Error('invalid_comment_id', '无效的id');
		}

		
		$comment_type	= $comment_json['type'];
		$post_id		= $comment_json['post_id'];
		$post_json		= wpjam_get_post($post_id);
		$post_type		= $post_json['post_type'];

		return [$comment_type=>$comment_json, $post_type=>$post_json];
	}

	public static function api_comment_delete(){
		$comment_id	= (int)wpjam_get_parameter('id', ['method'=>'POST', 'required'=>true]);
		$result		= wpjam_delete_comment($comment_id);

		if(is_wp_error($result)){
			return $result;
		}

		return ['errmsg' => '删除成功'];
	}

	public static function api_comment_list($args){
		$comment_type	= $args['comment_type'] ?? 'comment';

		$ct_obj	= wpjam_get_comment_type_object($comment_type);

		if(!$ct_obj){
			return new WP_Error($comment_type.'_not_supported', '操作不支持');
		}

		$comment_args	= array_merge(wpjam_array_except($args, ['title', 'callback']), ['type'=>$comment_type]);

		$response	= [];
		$output		= wpjam_array_pull($args, 'output');

		if($post_id	= (int)wpjam_get_parameter('post_id')){
			if($comment_type == 'comment' && get_option('page_comments')){
				$comment_args['number']		= (int)wpjam_get_parameter('number',	['default'=>get_option('comments_per_page')]);
				$comment_args['paged']		= (int)wpjam_get_parameter('paged',		['default'=>1]);
				$comment_args['order']		= get_option('comment_order', 'asc');
				$comment_args['post_id']	= $post_id;

				$comments_json	= [];
				$comment_query	= wpjam_get_comment_query($comment_args);

				foreach($comment_query->comments as $comment){
					$comments_json[]	= wpjam_get_comment($comment, $args);
				}

				$response['total']			= (int)$comment_query->found_comments;
				$response['total_pages']	= (int)$comment_query->max_num_pages;
				$response['current_page']	= (int)$comment_args['paged'];

				$response[$output]	= $comments_json;
			}else{
				$response[$output]	= $ct_obj->get_comments($post_id, true);
			}
		}else{
			if($ct_obj->post_meta){
				return new WP_Error($comment_type.'_not_supported', '操作不支持');
			}

			$comment_args['number']	= $args['number'] ?? 20;
			$comment_args['order']	= $args['order'] ?? 'DESC';
			$comment_args['status']	= $args['status'] ?? 'any';

			$post_type	= $args['post_type'] ?? wpjam_get_parameter('post_type');

			if(!is_null($post_type)){
				$comment_args['post_type']	= $post_type;
			}

			$paged	= (int)wpjam_get_parameter('paged', ['default'=>0]);

			if($paged){
				$comment_args['paged']	= $paged;
				$use_cursor	= false;
			}else{
				$use_cursor	= true;
			}

			if($use_cursor){
				$cursor	= (int)wpjam_get_parameter('cursor', ['default'=>0]);

				if($cursor){
					$comment_args['date_query']	= [['before'=>get_date_from_gmt(date('Y-m-d H:i:s',$cursor))]];
				}
			}

			$comment_query	= wpjam_get_comment_query($comment_args);

			if(is_wp_error($comment_query)){
				wpjam_send_json($comment_query);
			}

			$comments			= $ct_obj->parse_comments($comment_query->comments, $args);
			$response[$output]	= $comments;

			$response['total']			= (int)$comment_query->found_comments;
			$response['total_pages']	= (int)$comment_query->max_num_pages;
			$response['current_page']	= (int)($comment_query->query_vars['paged'] ?: 1);

			if($use_cursor){
				if($comment_query->max_num_pages > 1){
					$response['next_cursor']	= strtotime(end($comment_query->comments)->date_gmt);
				}else{
					$response['next_cursor']	= 0;
				}
			}
		}

		return $response;
	}

	public static function filter_post_json($post_json, $post_id){
		$post_type	= $post_json['post_type'];

		foreach(wpjam_get_comment_types([], 'object') as $comment_type => $ct_obj){
			if(!in_array($post_type, $ct_obj->object_type)){
				continue;
			}

			$post_json	= $ct_obj->filter_post_json($post_json, $post_id);
		}

		return $post_json;
	}
}

add_filter('wpjam_post_json',	['WPJAM_Comment_API', 'filter_post_json'], 1, 2);
add_action('wpjam_api',			['WPJAM_Comment_API', 'register']);