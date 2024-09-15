<?php
class WPJAM_Topic{
	private $id;

	public function __construct($id){
		$this->id	= (int)$id;
	}

	public function __get($key){
		if(in_array($key, ['id', 'topic_id'])){
			return $this->id;
		}elseif(in_array($key, ['data', 'topic'])){
			return get_post($this->id, ARRAY_A);
		}elseif($key == 'last_commenter'){
			return get_post_meta($this->id, 'last_comment_user', true);
		}elseif(in_array($key, ['company', 'position', 'fields'])){
			if(wpjam_topic_get_setting('topic_type')){
				return get_post_meta($this->id, $key, true);
			}

			return null;
		}else{
			

			$data	= $this->data;

			if(isset($data[$key])){
				return $data[$key];
			}else{
				return $data['post_'.$key] ?? null;
			}
		}
	}

	public function __set($key, $value){
		if($key == 'last_commenter'){
			update_post_meta($this->id, 'last_comment_user', $value);
		}elseif(in_array($key, ['company', 'position', 'fields'])){
			if(wpjam_topic_get_setting('topic_type')){
				update_post_meta($this->id, $key, $value);
			}
		}
	}

	public function __isset($key){
		$value	= $this->$key;

		return is_null($value) ? false : true;
	}

	public function __call($method, $args){
		$data	= $args[0] ?? [];

		if($method == 'stick'){
			if(function_exists('wpjam_stick_post')){
				return wpjam_stick_post($this->id);
			}else{
				return true;
			}
		}elseif($method == 'unstick'){
			if(function_exists('wpjam_unstick_post')){
				return wpjam_unstick_post($this->id);
			}else{
				return true;
			}
		}elseif($method == 'get_content'){
			return $this->content;
		}elseif($method == 'get_fields'){
			return $this->fields;
		}elseif($method == 'approve'){
			$data	= ['post_status'=>'publish'];
		}elseif($method == 'unapprove'){
			$data	= ['post_status'=>'pending'];
		}elseif($method == 'open'){
			$data	= ['comment_status'=>'open'];
		}elseif($method == 'close'){
			$data	= ['comment_status'=>'closed'];
		}elseif($method == 'sink'){
			$data	= ['last_comment_time'=>$this->last_comment_time - MONTH_IN_SECONDS];
		}elseif($method == 'update'){
			$data	= self::validate($data, $this->id);

			if(is_wp_error($data)){
				return $data;
			}
		}elseif($method == 'set_type'){
			$topic_type	= $data['sub_type'];

			if(!empty($data['group_id'])){
				$tax_input	= ['group'=>[(int)$data['group_id']]];
			}else{
				$tax_input	= ['group'=>[]];
			}

			$data	= compact('topic_type', 'tax_input');
		}else{
			return null;
		}

		return WPJAM_Post::update($this->id, $data);
	}

	public function comment($data){
		$comment_data	= [
			'user_id'	=> get_current_user_id(),
			'type'		=> 'comment',
			'comment'	=> $data['comment'] ?? '',
			'parent'	=> $data['parent'] ?? 0
		];

		$comment_obj	= wpjam_add_post_comment($this->id, $comment_data);

		if(is_wp_error($comment_obj)){
			return $comment_obj;
		}

		WPJAM_Post::update($this->id, ['last_comment_time'=>time()]);

		$this->last_commenter	= get_current_user_id(); 

		// $message	= [
		// 	'sender'		=> $comment_data['user_id'],
		// 	'receiver'		=> 0,
		// 	'type'			=> '',
		// 	'post_id'		=> $post_id,
		// 	'comment_id'	=> $comment_obj->id,
		// 	'blog_id'		=> get_current_blog_id(),
		// 	'content'		=> $comment_data['comment']
		// ];

		// if($parent = $comment_data['parent']){
		// 	if($comment_parent = get_comment($parent)){
		// 		$message['receiver']	= $comment_parent->user_id;
		// 		$message['type']		= 'topic_reply';
		// 	}
		// }else{
		// 	$post	= get_post($post_id);
			
		// 	$message['receiver']	= $post->post_author;
		// 	$message['type']		= 'topic_comment';
		// }

		// if($message['type'] && $message['receiver']){
		// 	wpjam_send_user_message($message);

		// 	wpjam_add_user_notice($message['receiver'], [
		// 		'type'		=> 'info',
		// 		'key'		=> 'message',
		// 		'notice'	=> '你有<strong>'.WPJAM_User_Message::get_instance($message['receiver'])->get_unread_count().'</strong>条未读站内消息',
		// 		'admin_url'	=> 'wp-admin/admin.php?page=wpjam-topic-messages'
		// 	]);
		// }

		return $comment_obj->id;
	}

	private static $instances	= [];

	public static function get_instance($id){
		if(!isset(self::$instances[$id])){
			if($data = self::get($id)){
				self::$instances[$id]	= new self($id);
			}else{
				return null;
			}	
		}

		return self::$instances[$id];
	}

	public static function get($id){
		if(get_post_type($id) != 'topic'){
			return null;
		}

		return wpjam_get_post($id);
	}

	public static function create($data){
		$data['post_type']			= 'topic';
		$data['comment_status']		= 'open';
		$data['last_comment_time']	= time();

		$data	= self::validate($data);

		if(is_wp_error($data)){
			return $data;
		}

		$id	= WPJAM_Post::insert($data);

		return is_wp_error($id) ? $id : self::get_instance($id);
	}

	public static function delete($post_id){
		return WPJAM_Post::delete($post_id);
	}

	public static function insert($data){
		$object	= self::create($data);

		return is_wp_error($object) ? $object : $object->id;
	}

	public static function get_groups(){
		return wpjam_get_terms(['taxonomy'=>'group']);
	}

	public static function get_types(){
		return wpjam_topic_get_type_setting(null);
	}

	public static function validate($data, $post_id=0){
		$data['post_title']		= wp_strip_all_tags(wpjam_array_pull($data, 'title'));
		$data['post_content']	= wp_strip_all_tags(wpjam_array_pull($data, ['raw_content', 'content']));

		if(wpjam_blacklist_check($data['post_title'])){
			return new WP_Error('illegal_topic_title', '标题中有非法字符');
		}

		if(wpjam_blacklist_check($data['post_content'])){
			return new WP_Error('illegal_topic_content', '内容中有非法字符');
		}

		if($group_id = wpjam_array_pull($data, 'group_id')){
			$data['tax_input']	= ['group'=>[(int)$group_id]];
		}

		$data['meta_input']	= wpjam_array_pull($data, 'meta_input') ?: [];	

		$images	= wpjam_array_pull($data, 'images');
		$images	= $images ? array_slice($images, 0, 3) : [];

		if($images){
			$data['meta_input']['images']	= $images;
		}elseif($post_id){
			delete_post_meta($post_id, 'images');
		}

		return $data;
	}

	public static function map_meta_cap($user_id, $args){
		$action_key	= $args[1] ?? '';

		if($action_key == ''){
			return ['read'];
		}
		
		if(!wpjam_topic_get_setting('account')){
			if(in_array($action_key, ['add', 'comment'])){
				return ['read'];
			}elseif($action_key == 'edit'){
				$topic	= $args[0] ? self::get($args[0]) : null;

				if($topic && $user_id == $topic['author']['id'] && time() - $topic['timestamp'] < MINUTE_IN_SECONDS / 10){
					return ['read'];
				}
			}
		}

		return wpjam_is_topic_blog() ? ['manage_options'] : ['manage_sites'];
	}
}
	