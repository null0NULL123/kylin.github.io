<?php
class WPJAM_Post_Comment{
	protected $name	= '';
	protected $args	= [];

	public function __construct($name, $args=[]){
		$this->name	= $name;
		$this->args	= $args;
	}

	public function __get($key){
		return $this->args[$key] ?? null;
	}

	public function __set($key, $value){
		$this->args[$key]	= $value;
	}

	public function __isset($key){
		return isset($this->args[$key]);
	}

	public function __unset($key){
		unset($this->args[$key]);
	}

	public function get_count($post_id){
		if($this->name == 'comment'){
			return get_comments_number($post_id);
		}else{
			return (int)get_post_meta($post_id, $this->plural, true);
		}
	}

	public function get_comments($post_id, $parse_for_json=false){
		if(!$parse_for_json){
			return get_comments(['post_id'=>$post_id, 'type'=>$this->name, 'order'=>'ASC', 'status'=>'any']);
		}else{
			return wpjam_get_comments(['post_id'=>$post_id, 'type'=>$this->name, 'order'=>'ASC']);
		}
	}

	public function add_comment($post_id, $data){
		if(!current_user_can('moderate_comments')){
			if('publish' != get_post_status($post_id)){
				return new WP_Error('invalid_post_status', '文章未发布，不能'.$this->label.'。');
			}

			if($this->name == 'comment' && !comments_open($post_id)){
				return new WP_Error('comment_closed', '已关闭留言');
			}
		}

		$data['post_id']	= $post_id;
		$data['type']		= $this->name;

		return WPJAM_Comment::create($data);
	}

	public function api_comment($post_id, $data=[]){
		$data['type']	= $this->name;

		if($text = wpjam_get_parameter('text',	['method'=>'POST'])){
			$data['comment']	= $text;
		}else{
			$data['comment']	= wpjam_get_parameter('comment',	['method'=>'POST', 'required'=>true]);
		}

		if($reply_to = wpjam_get_parameter('reply_to',	['method'=>'POST'])){
			$data['parent']		= (int)$reply_to;
		}else{
			$data['parent']		= (int)wpjam_get_parameter('parent',	['method'=>'POST', 'default'=>0]);
		}

		$comment_obj	= $this->add_comment($post_id, $data);

		if(is_wp_error($comment_obj)){
			wpjam_send_json($comment_obj);
		}

		return [$this->name => $comment_obj->parse_for_json()];
	}

	public function parse_comments($comments, $args=[]){
		$comments_json	= [];

		foreach($comments as $comment){
			$comment_json	= wpjam_get_comment($comment, $args);

			if($post_json = wpjam_get_post($comment_json['post_id'], $args)){
				$post_type	= $post_json['post_type'];

				$comment_json[$post_type]	= $post_json;
			}

			$comments_json[]	= $comment_json;
		}

		return $comments_json;
	}

	public function filter_post_json($post_json, $post_id){
		if($plural = $this->plural){
			$post_json[$this->name.'_count']	= (int)$this->get_count($post_id);

			if($this->name == 'comment'){
				$post_json[$this->name.'_status']	= comments_open($post_id) ? 'open' : 'closed';
			}else{
				$post_json[$this->name.'_status']	= 'open';
			}

			if(is_single($post_id)){
				if($this->name == 'comment' && get_option('page_comments')){
					$post_json['page_comments']	= true;
				}

				$post_json[$plural]	= $this->get_comments($post_id, true);
			}
		}

		return $post_json;
	}

	public function query_data($post_id, $args){
		$args	= wp_parse_args($args, [
			'post_id'		=> $post_id,
			'type'			=> $this->name,
			'no_found_rows'	=> false,

			'update_comment_meta_cache'	=> true,
			'update_comment_post_cache'	=> true,
		]);

		if($this->name == 'comment'){
			$args['hierarchical']	= get_option('thread_comments') ? 'threaded' : false;
		}

		if($orderby	= wpjam_get_data_parameter('orderby',	['sanitize_callback'=>'sanitize_key'])){
			$args['orderby']	= $orderby;
		}

		if($order = wpjam_get_data_parameter('order',		['sanitize_callback'=>'sanitize_key'])){
			$args['order']		= $order;
		}

		$query	= new WP_Comment_Query($args);

		return ['items'=>$query->comments, 'total'=>$query->found_comments];
	}

	public function render_item($item){
		$id		= $item['id'] ;

		$author	= get_comment_author($id);
		$avatar	= get_avatar_url(get_comment($id), 64);

		if($item['comment_parent']){
			$item['author']	= '<span class="author-pad">'.str_repeat('&emsp; ', 1).'</span>'.'<img src="'.$avatar.'" width="24">'.$author;
		}else{
			$item['author']	= '<img src="'.$avatar.'" width="32">'.$author;
		}

		$item['class']	= $item['comment_approved'] ? 'approved' : 'unapproved';

		if($item['comment_content'] && !is_serialized($item['comment_content'])){
			$item['comment']	= $item['comment'] ?? '';
			$item['comment']	= $item['comment_content'].$item['comment'];	
		}

		if($item['comment']){
			$item['comment']	= wpautop($item['comment']);
		}

		return $item;
	}

	public function get_actions(){
		$actions	= [
			'add'		=> ['title'=>'新增'],
			'approve'	=> ['title'=>'批准',			'order'=>20,	'direct'=>true],
			'unapprove'	=> ['title'=>'驳回',			'order'=>20,	'direct'=>true],
			'reply'		=> ['title'=>'回复',			'order'=>20],
			'spam'		=> ['title'=>'标记为垃圾',	'order'=>20,	'direct'=>true,	'response'=>'delete'],
			'delete'	=> ['title'=>'删除',			'order'=>1,		'direct'=>true, 'confirm'=>true, 'bulk'=>true],
		];

		if($this->name != 'comment'){
			$actions	= wpjam_array_except($actions, 'add');
		}

		return $actions;
	}

	public function get_fields($action_key='', $args=[]){
		if($action_key == 'reply'){
			$reply_id	= wpjam_get_data_parameter('reply_id') ?? '';
			$reply_text	= $reply_id ? get_comment($reply_id)->comment_content : '';

			return	[
				'reply_id'		=> ['title'=>'',	'type'=>'hidden',	'value'=>$reply_id],
				'reply_text'	=> ['title'=>'',	'type'=>'textarea',	'value'=>$reply_text]
			];
		}elseif($action_key == 'add'){
			return [
				'commenter'	=> ['title'=>'用户昵称',	'type'=>'text',	'class'=>''],
				'avatarurl'	=> ['title'=>'用户头像',	'type'=>'img',	'item_type'=>'url',	'size'=>'100x100',	'name'=>'meta[avatarurl]'],
				'date'		=> ['title'=>'评论时间',	'type'=>'datetime-local'],
				'comment'	=> ['title'=>'评论内容',	'type'=>'textarea'],
			];
		}elseif($action_key == ''){
			return ['comment'=>['title'=>'评论',	'type'=>'textarea',	'show_admin_column'=>'only']];
		}
	}
}

class WPJAM_Post_Action extends WPJAM_Post_Comment{
	public function is_did($post_id, $wp_error=true){
		if(is_user_logged_in()){
			$field	= 'user_id';
			$value	= [get_current_user_id()];
		}else{
			$field	= 'comment_author_email';
			$value	= wpjam_get_current_commenter_emails();

			if(empty($value)){
				return new WP_Error('bad_authentication', '无权限');
			}
		}

		$comments	= $this->get_comments($post_id);

		foreach($comments as $comment){
			if(in_array($comment->$field, $value)){
				return $comment->comment_ID;
			}
		}

		return 0;
	}

	public function action($post_id, $status=1, $args=[]){
		$did	= $this->is_did($post_id);

		if(is_wp_error($did)){
			return $did;
		}

		if(($status == 1 && $did) || ($status != 1 && !$did)){
			return true;
		}

		if($did){
			return wp_delete_comment($did, $force_delete=true);
		}else{
			return WPJAM_Comment::insert(array_merge($args, ['post_id'=>$post_id, 'type'=>$this->name]));
		}
	}

	public function api_comment($post_id, $args=[]){
		$status	= wpjam_array_pull($args, 'status');
		$result	= $this->action($post_id, $status, $args);

		return is_wp_error($result) ? $result : true;
	}

	public function parse_comments($comments, $args=[]){
		$posts_json	= [];

		foreach($comments as $comment){
			$comment_json	= wpjam_get_comment($comment, $args);

			if($post_json = wpjam_get_post($comment_json['post_id'], $args)){
				$comment_type	= $comment_json['type'];
				$posts_json[]	= array_merge($post_json, [$comment_type => $comment_json]);
			}
		}

		return $posts_json;
	}

	public function filter_comment_json($comment_json, $comment_id, $args){
		$author	= wpjam_array_pull($comment_json, 'author');

		return array_merge($comment_json, $author);
	}

	public function filter_post_json($post_json, $post_id){
		$post_json	= parent::filter_post_json($post_json, $post_id);
		
		if(is_single($post_id)){
			if($is_did = $this->is_did){
				$post_json[$is_did]	= (bool)$this->is_did($post_id, $wp_error=false);
			}
		}

		return $post_json;
	}

	public function get_actions(){
		return [];
	}

	public function get_fields($action_key='', $args=[]){
		return [];
	}
}

class WPJAM_Post_Like extends WPJAM_Post_Action{
	public function get_comments($post_id, $parse_for_json=false){
		$action_data	= get_post_meta($post_id, $this->name.'_data', true) ?: [];

		if(!$parse_for_json){
			return $action_data;
		}

		$items	= [];

		$user_ids		= $action_data['user_ids'] ?? [];
		$user_emails	= $action_data['user_emails'] ?? [];

		if($user_ids){
			cache_users($user_ids);

			foreach ($user_ids as $user_id) {
				$userdata	= get_userdata($user_id);
				$email		= $userdata->user_email;
				$nickname	= $userdata->display_name;
				$avatar		= get_avatar_url($user_id, 200);
				$user_id	= (int)$user_id;

				$items[]	= compact('email', 'nickname', 'user_id',  'avatar');
			}
		}

		if($user_emails){
			foreach ($user_emails as $email => $nickname) {
				$avatar		= get_avatar_url($email, 200);
				$user_id	= 0;

				$items[]	= compact('email', 'nickname', 'user_id',  'avatar');
			}
		}

		return $items;
	}

	public function is_did($post_id, $wp_error=true){
		$comments	= $this->get_comments($post_id);

		if(empty($comments)){
			return 0;
		}

		if(is_user_logged_in()){
			$user_ids	= $comments['user_ids'] ?? [];

			return $user_ids && in_array(get_current_user_id(), $user_ids);
		}else{
			$commenter_emails	= wpjam_get_current_commenter_emails();

			if(empty($commenter_emails)){
				return isset($_COOKIE[$this->is_did.'_'.$post_id]) && $_COOKIE[$this->is_did.'_'.$post_id] == $post_id;
			}else{
				$user_emails	= $comments['user_emails'] ?? [];

				return $user_emails && array_intersect(array_keys($user_emails), $commenter_emails);
			}
		}
	}

	public function action($post_id, $status=1, $args=[]){
		$retry_times	= 10;

		do{
			$did	= $this->is_did($post_id, false);

			if(($status == 1 && $did) || ($status == 0 && !$did)){
				return true;
			}

			$use_cookie	= false;
			$new_data	= $prev_data = $this->get_comments($post_id);;

			if(is_user_logged_in()){
				$user_ids	= $prev_data['user_ids'] ?? [];

				if($status == 1){
					$user_ids[]	= get_current_user_id(); 
				}else{
					$user_ids	= array_values(array_diff($user_ids, [get_current_user_id()])); 
				}

				$new_data['user_ids']	= $user_ids;
			}else{
				$commenter	= wp_get_current_commenter();

				if(empty($commenter['comment_author_email'])){
					$use_cookie	= true;

					if($status == 1){
						wpjam_set_cookie($this->is_did.'_'.$post_id, $post_id);
					}else{
						wpjam_clear_cookie($this->is_did.'_'.$post_id);
					}
				}else{
					$user_emails	= $prev_data['user_emails'] ?? [];

					if($status == 1){
						$email		= $commenter['comment_author_email'];
						$nickname	= $commenter['comment_author'];

						$user_emails[$author_email]	= $nickname;
					}else{
						foreach(wpjam_get_current_commenter_emails() as $email){
							unset($user_emails[$email]);
						} 
					}

					$new_data['user_emails']	= $user_emails;
				}
			}

			$updated	= $use_cookie ?: update_post_meta($post_id, $this->name.'_data', $new_data, $prev_data);

			if($updated){
				$count	= (int)get_post_meta($post_id, $this->plural, true);

				if($status == 1){
					$count	= $count+1;
				}else{
					$count	= $count-1;
					$count	= $count > 0 ? $count : 0;
				}

				update_post_meta($post_id, $this->plural, $count);

				if($status == 0){
					wpjam_clear_cookie($this->is_did.'_'.$post_id);
				}
			}

			$retry_times --;
		}while(!$updated && $retry_times > 0);

		return $updated;
	}

	public function query_data($post_id, $args){
		$items	= $this->get_comments($post_id, true);

		return ['items'=>$items, 'total'=>count($items)];
	}

	public function render_item($item){
		return $item;
	}
}