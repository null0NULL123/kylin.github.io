<?php
class WPJAM_Topic_API{
	public static function register_api($json){
		if($json == 'topic.create'){
			wpjam_register_api($json, ['callback'=>[self::class, 'api_create']]);
		}elseif($json == 'topic.update'){
			wpjam_register_api($json, ['callback'=>[self::class, 'api_update']]);
		}elseif($json == 'topic.config'){
			$account_id	= self::api_get_account_id();

			wpjam_register_api($json, ['callback'=>[self::class, 'api_config']]);
		}elseif($json == 'topic.list'){
			$account_id	= self::api_get_account_id();

			$modules		= [];
			$list_module	= [
				'type'	=> 'post_type',
				'args'	=> [
					'post_type'		=> 'topic',
					'post_status'	=> 'publish',
					'action'		=> 'list',
					'orderby'		=> 'last_comment_time'
				]
			];

			if(wpjam_topic_get_setting('topic_type')){
				$topic_type		= wpjam_get_parameter('type',	['default'=>0]);
				$type_setting	= wpjam_topic_get_type_setting($topic_type);				

				if(!$type_setting){
					wpjam_send_json(new WP_Error('invalid_topic_type', '无效的类型'));
				}

				$modules[]	= [
					'type'	=> 'other',
					'args'	=> ['current_topic_type'=>$type_setting]
				];

				$list_module['args']['topic_type']	= $topic_type;
			}

			$modules[]	= $list_module;

			wpjam_register_api('topic.list', [
				'title'		=> '帖子列表',
				'modules'	=> $modules
			]);
		}elseif($json == 'topic.get'){
			$account_id	= self::api_get_account_id();

			wpjam_register_api('topic.get', [
				'title'		=> '帖子详情',
				'modules'	=> [[
					'type'	=> 'post_type',
					'args'	=> [
						'post_type'		=> 'topic',
						'post_status'	=> 'publish',
						'action'		=> 'get'
					]
				]]
			]);
		}elseif($json == 'topic.applied.list'){
			wpjam_register_api('topic.applied.list', [
				'title'		=> '申请合作',
				'page_title'=> '申请合作列表',
				'callback'	=> [self::class, 'api_apply_list']
			]);
		}elseif($json == 'topic.apply.approve'){
			wpjam_register_api('topic.apply.approve', [
				'title'		=> '申请合作通过',
				'page_title'=> '申请合作通过',
				'callback'	=> [self::class, 'api_apply_approve']
			]);
		}elseif($json == 'topic.apply.reject'){
			wpjam_register_api('topic.apply.reject', [
				'title'		=> '申请合作通过',
				'page_title'=> '申请合作通过',
				'callback'	=> [self::class, 'api_apply_reject']
			]);
		}
	}

	public static function api_get_account_id(){
		if(!wpjam_topic_get_setting('account')){
			return new WP_Error('wpjam_account_required', '请先激活前台用户插件');
		}

		$account_id	= false;

		if(is_weapp()){
			$openid		= weapp_get_current_openid();

			if(is_wp_error($openid)){
				return $openid;
			}

			$weapp_user		= WEAPP_Account::get(weapp_get_appid(), $openid);
			$account_id		= $weapp_user ? ($weapp_user['account_id'] ?? 0) : 0;
			$account_obj	= $account_id ? WPJAM_Account::get_instance($account_id) : null;

			if($account_obj){
				wpjam_account_bind('weapp', $openid, $account_id);
			}else{
				$account_obj	= wpjam_account_signup('weapp', $openid);
			}

			if(is_wp_error($account_obj)){
				return $account_obj;
			}

			$account_id	= $account_obj->id;
		}

		if(empty($account_id)){
			return new WP_Error('wpjam_account_required', '前台账号ID不能为空');
		}

		return $account_id;
	}

	public static function api_config(){
		$pages	= wpjam_topic_get_setting('pages');

		return [
			'pages'		=> $pages ? array_column($pages, 'id', 'key') : [],
			'types'		=> wpjam_topic_get_type_setting(),
			'groups'	=> wpjam_get_terms([
				'taxonomy'		=> 'group', 
				'hide_empty'	=> false, 
				'meta_key'		=> 'order', 
				'orderby'		=> 'meta_value_num',
				'order'			=> 'DESC'
			])
		];
	}

	public static function api_create(){
		$data		= self::get_data();

		if(is_wp_error($data)){
			return $data;
		}

		$object	= WPJAM_Topic::create($data);

		if(is_wp_error($object)){
			return $object;
		}

		$errmsg	= $object->status == 'pending' ? '发布成功，请等待管理员审核。' : '发布成功，点击查看';

		return [
			'errcode'	=> 0,
			'errmsg'	=> $errmsg,
			'topic_id'	=> $object->id,
			'status'	=> $object->status
		];
	}

	public static function api_update(){
		$topic_id	= wpjam_get_parameter('topic_id', ['method'=>'POST', 'type'=>'int', 'required'=>true]);
		$data		= self::get_data($topic_id);

		if(is_wp_error($data)){
			return $data;
		}

		$result		= WPJAM_Topic::update($topic_id, $data);

		if(is_wp_error($result)){
			return $result;
		}

		return [
			'errcode'	=> 0,
			'topic_id'	=> $topic_id,
			'status'	=> get_post_status($topic_id)
		];
	}

	public static function api_apply_list($args){
		$account_id		= self::api_get_account_id();

		if(is_wp_error($account_id)){
			return $account_id;
		}

		return WPJAM_Topic_Account_Apply::get_list($account_id);
	}

	public static function api_apply_approve(){
		$comment_id	= wpjam_get_parameter('comment_id',	['method'=>'post', 'required'=>true]);
		$result		= self::update_apply($comment_id, 1);

		if(is_wp_error($result)){
			return $result;
		}

		return true;
	}

	public static function api_apply_reject(){
		$comment_id	= wpjam_get_parameter('comment_id',	['method'=>'post', 'required'=>true]);
		$result		= self::update_apply($comment_id, -1);

		if(is_wp_error($result)){
			return $result;
		}

		return true;
	}

	public static function update_apply($comment_id, $status){
		$account_id	= self::api_get_account_id();

		if(is_wp_error($account_id)){
			return $account_id;
		}

		return WPJAM_Topic_Account_Apply::update($account_id, $comment_id, $status);
	}

	public static function get_data($topic_id=''){
		if($topic_id){
			$topic	= wpjam_validate_post($topic_id);

			if(is_wp_error($topic)){
				return $topic;
			}
		}
		
		$account_id	= self::api_get_account_id();

		if(is_wp_error($account_id)){
			return $account_id;
		}

		if($topic_id && $account_id != $topic->account_id){
			return new WP_Error('not_allowed', '无权限');
		}

		$data	= [
			'title'		=> wpjam_get_parameter('title',		['method'=>'post', 'required'=>true]),
			'content'	=> wpjam_get_parameter('content',	['method'=>'post', 'required'=>true]),
			'images'	=> wpjam_get_parameter('images',	['method'=>'post']),
			'group_id'	=> wpjam_get_parameter('group_id',	['method'=>'post', 'required'=>true]),
		];

		if(!$topic_id){
			if(wpjam_topic_get_setting('audit')){
				$data['post_status']	= 'pending';
			}else{
				$data['post_status']	= 'publish';
			}

			$data['account_id']		= $account_id;
			$data['post_author']	= 0;
		}

		$data['meta_input']		= [];

		if(wpjam_topic_get_setting('apply')){
			if($topic_id){
				$data['apply_status']	= (int)wpjam_get_parameter('apply_status',	['method'=>'post', 'required'=>true]);
			}else{
				$apply_status	= wpjam_get_parameter('apply_status',	['method'=>'post']);

				if(!is_null($apply_status)){
					$data['apply_status']	= $apply_status;
				}
			}
		}

		if(wpjam_topic_get_setting('topic_type')){
			$topic_type	= $topic_id ? $topic->topic_type : '';

			if(!$topic_type){
				$data['topic_type']	= $topic_type	= wpjam_get_parameter('topic_type',	['method'=>'post', 'required'=>true]);
			}
			
			$fields	= self::get_fields($topic_type);

			if(is_wp_error($fields)){
				return $fields;
			}elseif($fields){
				$data['meta_input']['fields']	= $fields;
			}
		}

		return $data;
	}

	public static function get_fields($topic_type){
		$type	= wpjam_topic_get_type_setting($topic_type);

		if(!$type){
			return new WP_Error('invalid_topic_type', '无效的类型');
		}

		$fields		= $type['fields'] ?? [];
		$columns	= $type['columns'];

		if(is_array($fields)){
			foreach($fields as &$field){
				$fields['columns']	= $fields['columns'] ?? $columns;

				if($fields['columns'] && in_array($topic_type, $fields['columns'])){
					if($field['type'] == 'number'){
						$field['value']	= (int)wpjam_get_parameter($field['key'],	['method'=>'post', 'required'=>$field['required']]);
					}else{
						$field['value']	= wpjam_get_parameter($field['key'],	['method'=>'post', 'required'=>$field['required']]);
					}
				}
			}
		}

		return $fields;
	}

	public static function filter_post_json($post_json, $post_id){
		if($post_json['post_type'] == 'topic'){
			$post	= get_post($post_id);

 			if(is_single()){
 				$post_json['raw_content']	= $post->post_content;
 			}

 			if(is_admin()){
 				$post_json['images']	= get_post_meta($post_id, 'images', true) ?: [];
 			}else{
 				if($images = get_post_meta($post_id, 'images', true)){
					$thumb_size	= count($images) > 1 ? '300x300' : '300x0';

					array_walk($images, function(&$image) use($thumb_size){
						$image = [
							'thumb'		=> wpjam_get_thumbnail($image, $thumb_size),
							'original'	=> wpjam_get_thumbnail($image, '1080x0')
						];
					});

					$post_json['images']	= $images;
				}else{
					$post_json['images']	= [];
				}
 			}

			$post_json['group_id']	= $post_json['group'] ? $post_json['group'][0]['id'] : '';

			if(wpjam_topic_get_setting('topic_type')){
				$topic_type	= $post->topic_type;

				if($type_setting = wpjam_topic_get_type_setting($topic_type)){
					$post_json['topic_type']	= [
						'name'		=> $type_setting['name'],
						'slug'		=> $type_setting['slug'],
						'sub_name'	=> $type_setting['sub_name'],
						'sub_slug'	=> $topic_type,
						'group'		=> (bool)$type_setting['group'],
					];
				}

				$fields	= $type_setting['fields'] ?? [];

				if(is_array($fields)){
					$post_json['fields']	= get_post_meta($post_id, 'fields', true) ?: new stdClass;
				}
 			}

			if(wpjam_topic_get_setting('account')){
				$account_obj			= wpjam_get_account($post->account_id);
				$post_json['account']	= $account_obj ? $account_obj->parse_for_json() : [];
			}

			if(wpjam_topic_get_setting('apply')){
				if($post_json['apply_status'] = (int)$post->apply_status){
					$is_did	= wpjam_did_post_action($post_id, 'apply');

					if($post_json['is_applied']	= (bool)$is_did){
						$post_json['applied_status']	= (int)get_comment($is_did)->comment_approved;
					}
				}
			}
		}

		return $post_json;
	}
}

add_action('wpjam_api',			['WPJAM_Topic_API', 'register_api'], 0);
add_filter('wpjam_post_json',	['WPJAM_Topic_API', 'filter_post_json'], 10, 2);