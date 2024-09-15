<?php
class WPJAM_Comment{
	protected $comment	= null;

	public function __construct($comment){
		$this->comment	= get_comment($comment);
	}

	public function __get($key){
		if($key == 'type'){
			return $this->comment->comment_type ?: 'comment';
		}elseif(in_array($key, ['id', 'ID', 'comment_id'])){
			$key	= 'comment_ID';
		}elseif(in_array($key, ['post_id', 'post_ID', 'comment_post_id'])){
			$key	= 'comment_post_ID';
		}elseif(in_array($key, ['author', 'author_email', 'author_url', 'author_IP', 'date', 'date_gmt', 'content', 'karma', 'approved', 'agent', 'type', 'parent'])){
			$key	= 'comment_'.$key;
		}

		return $this->comment->$key ?? null;
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public function update($data){
		$update_data	= [];

		if(isset($data['comment'])){
			$update_data['comment_content']	=  wpjam_array_pull($data, 'comment');
		}

		if($data){
			$update_data['comment_ID']	= $this->id;

			$result	= wp_update_comment($update_data, $wp_error=true);

			if(!is_wp_error($result)){
				$this->comment	= get_comment($this->id);
			}

			return $result;
		}

		return true;
	}

	public function get_author(){
		return [
			'email'		=> $this->author_email,
			'nickname'	=> $this->author,
			'avatar'	=> get_avatar_url($this->comment, 200),
			'user_id'	=> (int)$this->user_id
		];
	}

	public function parse_for_json($args=[]){
		$timestamp	= strtotime($this->date_gmt);
		$author		= $this->get_author();

		$comment_json	= [
			'id'		=> (int)$this->id,
			'post_id'	=> (int)$this->post_id,
			'type'		=> $this->type,
			'parent'	=> (int)$this->parent,
			'approved'	=> (int)$this->approved,
			'timestamp'	=> $timestamp,
			'time'		=> wpjam_human_time_diff($timestamp),
			'date'		=> wp_date('Y-m-d', $timestamp),
			'content'	=> wp_strip_all_tags($this->content),
			'author'	=> $author,
			'user_id'	=> $author['user_id']
		];

		$ct_obj			= wpjam_get_comment_type_object($this->type);
		$comment_json	= $ct_obj->filter_comment_json($comment_json, $this->id, $args);

		return apply_filters('wpjam_comment_json', $comment_json, $this->id, $args);
	}

	private static $instances	= [];

	public static function get_instance($id){
		$comment	= $id ? get_comment($id) : null;

		if(empty($comment)){
			return null;
		}

		$id	= $comment->comment_ID;

		if(!isset($instances[$id])){
			if(!get_post($comment->comment_post_ID)){
				return null;
			}

			$type	= $comment->comment_type ?: 'comment';
			$ct_obj	= wpjam_get_comment_type_object($type);

			if(!$ct_obj){
				return null;
			}

			$instances[$id]	= new self($comment);
		}

		return $instances[$id];
	}

	public static function logged_in_required(){
		if(get_option('comment_registration')){
			return new WP_Error('logged_in_required', '只支持登录用户操作');
		}

		return false;
	}

	public static function insert($data){
		if(is_user_logged_in()){
			$user	= wp_get_current_user();

			$data['comment_author']			= $data['author'] ?? $user->display_name ?: $user->user_login;
			$data['comment_author_email']	= $data['author_email'] ?? $user->user_email;
			$data['comment_author_url']		= esc_url($data['author_url'] ?? $user->user_url);

			if(!current_user_can('moderate_comments') || !isset($data['user_id'])){
				$data['user_id']	= get_current_user_id();
			}
		}else{
			if($required = self::logged_in_required()){
				return $required;
			}

			$commenter	= wp_get_current_commenter();

			if(empty($commenter['comment_author_email'])){
				return new WP_Error('bad_authentication', '无权限');
			}

			$data['comment_author']			= $commenter['comment_author'];
			$data['comment_author_email']	= $commenter['comment_author_email'];
			$data['comment_author_url']		= esc_url($commenter['comment_author_url']);
		}

		if(!empty($data['post_id'])){
			$data['comment_post_ID']= wpjam_array_pull($data, 'post_id');
		}

		if(!empty($data['type'])){
			$data['comment_type']	= wpjam_array_pull($data, 'type');
		}elseif(empty($data['comment_type'])){
			$data['comment_type']	= 'comment';
		}

		if(!empty($data['date'])){
			$data['comment_date']	= wpjam_array_pull($data, 'date');
		}elseif(empty($data['comment_date'])){
			$data['comment_date']	= current_time('mysql');
		}

		$data['comment_date_gmt']	= get_gmt_from_date($data['comment_date']);

		if(!empty($data['meta'])){
			$data['comment_meta']	= array_filter(wpjam_array_pull($data, 'meta'));
		}

		if(!empty($data['comment'])){
			$data['comment_content']	= wpjam_array_pull($data, 'comment');
		}else{
			$data['comment_content']	= $data['comment_content'] ?? '';
		}

		if(isset($data['parent'])){
			$data['comment_parent'] 	= (int)wpjam_array_pull($data, 'parent');
		}else{
			$data['comment_parent']		= $data['comment_parent'] ?? 0;
		}

		$data['comment_author_IP']	= preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
		$data['comment_agent']		= substr($_SERVER['HTTP_USER_AGENT'], 0, 254);

		$data	= apply_filters('preprocess_comment', $data);

		$data	= wp_slash($data);
		$data	= wp_filter_comment($data);

		$ct_obj	= wpjam_get_comment_type_object($data['comment_type']);

		if($ct_obj->action){
			$approve 	= $ct_obj->approve ?? 1;

			$comment_approved	= apply_filters('pre_comment_approved', $approve, $data);
		}else{
			$comment_approved	= wp_allow_comment($data, $avoid_die=true);
		}

		if(is_wp_error($comment_approved)) {
			if($comment_approved->get_error_code() == 'comment_duplicate'){
				return new WP_Error('comment_duplicate', '检测到重复评论，您似乎已经提交过这条评论了！');
			}elseif($comment_approved->get_error_code() == 'comment_flood'){
				return new WP_Error('comment_flood', '您提交评论的速度太快了，请稍后再发表评论。');
			}else{
				return $comment_approved;
			}
		}

		if(!$ct_obj->action && current_user_can('moderate_comments')){
			$data['comment_approved']	= 1;
		}else{
			$data['comment_approved']	= $comment_approved;
		}

		$id	= wp_insert_comment($data);

		if(!$id){
			$fields = ['comment_author', 'comment_author_email', 'comment_author_url', 'comment_content'];

			foreach($fields as $field){
				$data[$field]	= $GLOBALS['wpdb']->strip_invalid_text_for_column($GLOBALS['wpdb']->comments, $field, $data[$field]);
			}

			$data	= wp_filter_comment($data);
			$id		= wp_insert_comment($data);
		}

		if(!$id){
			return new WP_Error('comment_save_error', '评论保存失败，请稍后重试！', 500);
		}

		do_action('comment_post', $id, $data['comment_approved'], $data);

		return $id;
	}

	public static function create($data){
		if(isset($data['comment'])){
			$data['comment_content']	= wpjam_array_pull($data, 'comment');
		}

		if(empty($data['comment_content'])){
			return new WP_Error('comment_required', '评论内容不能为空');
		}

		$data['comment_content']	= trim(wp_strip_all_tags($data['comment_content']));

		$id	= self::insert($data);

		return is_wp_error($id) ? $id : self::get_instance($id);
	}

	public static function delete($id, $force_delete=false){
		$comment	= get_comment($id);

		if(empty($comment)){
			return new WP_Error('invalid_comment_id', '无效的 comment_id');
		}

		if(is_user_logged_in()){
			if($comment->user_id != get_current_user_id() && !current_user_can('moderate_comments')){
				return new WP_Error('bad_authentication', '你不能删除别人的评论');
			}
		}else{
			if($required = self::logged_in_required()){
				return $required;
			}

			if($comment->user_id){
				return new WP_Error('bad_authentication', '你不能删除别人的评论');
			}

			$commenter_emails	= wpjam_get_current_commenter_emails();

			if(empty($commenter_emails)){
				return new WP_Error('bad_authentication', '无权限');
			}

			if(!in_array($comment->comment_author_email, $commenter_emails)){
				return new WP_Error('bad_authentication', '你不能删除别人的评论');
			}
		}

		return wp_delete_comment($id, $force_delete);
	}

	public static function set_status($id, $status){
		if($status == 'reject' || $status == -1){
			$status			= -1;
			$comment_old	= clone get_comment($id);

			if(!$GLOBALS['wpdb']->update($GLOBALS['wpdb']->comments, ['comment_approved' => $status], ['comment_ID' => $comment_old->comment_ID])){
				return new WP_Error('db_update_error', __('Could not update comment status.'), $GLOBALS['wpdb']->last_error);
			}

			clean_comment_cache($comment_old->comment_ID);

			$comment	= get_comment($comment_old->comment_ID);

			do_action('wp_set_comment_status', $comment->comment_ID, $status);

			wp_transition_comment_status($status, $comment_old->comment_approved, $comment);

			wp_update_comment_count($comment->comment_post_ID );

			return true;
		}

		return wp_set_comment_status($id, $status, $wp_error=true);
	}

	public static function update_caches($ids, $update_meta_cache=true){
		_prime_comment_caches($ids, false);

		if($update_meta_cache){
			update_meta_cache('comment', $ids);
		}
	}

	public static function get_comments($args, $parse_for_json=true){
		$comments_json	= [];
		$comment_query	= self::get_query($args);

		foreach($comment_query->comments as $comment){
			$comments_json[]	= self::get_instance($comment)->parse_for_json($args);
		}

		return $comments_json;
	}

	public static function get_query($args=[]){
		$args	= wp_parse_args($args, [
			'post_id'	=> 0,
			'order'		=> 'ASC',
			'type'		=> 'comment',

			'update_comment_meta_cache'	=> true,
			'update_comment_post_cache'	=> true,
		]);

		$comment_type	= $args['type'] ?: 'comment';
		$ct_obj			= wpjam_get_comment_type_object($comment_type);
		$post_id		= (int)$args['post_id'];

		if(empty($post_id) || !empty($args['number'])){
			$args['no_found_rows']	= false;
		}else{
			$args['status']			= 'approve';
		}

		if(!$ct_obj->action){
			if($post_id){
				$post_type	= get_post_type($post_id);

				if(!$post_type){
					return new WP_Error('invalid_post_id', '无效的 post_id');
				}
			}elseif(isset($args['post_type'])){
				$post_type	= $args['post_type'];
			}else{
				$post_type	= null;
			}
		}

		if(is_user_logged_in()){
			if(empty($post_id)){
				$args['user_id']	= get_current_user_id();
			}else{
				if(!$ct_obj->action){
					$args['include_unapproved']	= get_current_user_id();
				}
			}
		}else{
			$commenter_emails	= wpjam_get_current_commenter_emails();

			if(empty($post_id)){
				if(empty($commenter_emails)){
					return new WP_Error('bad_authentication', '无权限');
				}

				$args['author_email__in']	= $commenter_emails;
			}else{
				if(!$ct_obj->action && !empty($commenter_emails)){
					$args['include_unapproved']	= $commenter_emails;
				}
			}
		}

		return new WP_Comment_Query($args);
	}

	public static function get_counts($post_id, $args=[]){
		$post_ids	= wp_parse_id_list($post_id);
		$post_ids	= array_map('intval', $post_ids);
		$where		= 'comment_post_ID in ('.implode(',', $post_ids).')';
		$fields		= ['comment_post_ID', 'comment_approved', 'comment_type'];

		$args		= wp_parse_args($args, ['approved'=>'', 'type'=>'']);

		if(is_numeric($args['approved'])){
			$where	.= ' AND comment_approved = '.(int)$args['approved'];
			$fields	= array_diff($fields, ['comment_approved']);
		}

		if($args['type']){
			$where	.= ' AND comment_type = '."'".esc_sql($args['type'])."'";
			$fields	= array_diff($fields, ['comment_type']);
		}

		$fields	= implode(', ', $fields);
		$sql	= "SELECT count(*) as count, {$fields} FROM {$GLOBALS['wpdb']->comments} WHERE {$where} GROUP BY {$fields}"; 

		return $GLOBALS['wpdb']->get_results($sql, ARRAY_A);
	}
}