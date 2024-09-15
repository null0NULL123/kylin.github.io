<?php
class WPJAM_Topic_Apply extends WPJAM_Post_Action{
	public function parse($comment_json){
		$comment_json['content']	= $this->content;
		$comment_json['status']		= (int)$this->approved;
		$comment_json['author']		= $this->get_author();

		$comment_json 	= array_merge($comment_json, $this->parse_meta_fields());

		return $comment_json;
	}

	public function render($item){
		$item	= parent::render($item);
		$ct_obj	= wpjam_get_comment_type_object($this->type);
		$item	= array_merge($item, $ct_obj->render_meta_fields($this->post_type, $this->id));

		if($this->content && !is_serialized($this->content)){
			$item['comment']	= wpautop($this->content);	
		}

		return $item;
	}

	public function api_comment($post_id, $args=[]){
		$_post	= get_post($post_id);

		if(!$_post || $_post->apply_status == 0){
			// return new WP_Error('topic_apply_disabled', '帖子无需申请合作');
		}

		$data['comment']	= wpjam_get_parameter('comment',	['method'=>'POST', 'required'=>true]);

		if(!empty($args['meta'])){
			$data['meta']	= $args['meta'];
		}

		$comment_obj	= $this->add_comment($post_id, $data);

		if(is_wp_error($comment_obj)){
			return $comment_obj;
		}

		if($account_id	= $_post->account_id){
			WPJAM_Topic_Account_Apply::create($account_id, [
				'post_id'		=> $post_id,
				'comment_id'	=> $comment_obj->id,
				'status'		=> 0,
				'time'			=> time()
			]);
		}

		return true;
	}

	public function action($post_id, $status=1, $args=[]){
		if(!$status){
			return new WP_Error('unapply_disabled', '不存在取消申请操作');
		}

		$did	= $this->is_did($post_id);

		if(is_wp_error($did)){
			return $did;
		}

		if($did && ($comment_obj = WPJAM_Comment::get_instance($did))){
			if($comment_obj->approved == 0){
				return new WP_Error('prev_wait_approve', '之前的申请等待审核');
			}elseif($comment_obj->approved == 1){
				return new WP_Error('prev_approved', '之前的申请已审核，无需再申请');
			}
		}

		return WPJAM_Comment::insert(array_merge($args, ['post_id'=>$post_id, 'type'=>$this->name]));
	}

	public function get_fields($action_key='', $args=[]){
		$fields	= parent::get_fields($action_key, $args);

		return $action_key ? $fields : array_merge(['comment'=>['title'=>'申请',	'type'=>'text',	'show_admin_column'=>'only']], $fields);
	}

	public function get_actions(){
		return [];
	}
}

class WPJAM_Topic_Account_Apply{
	public static function create($account_id, $apply){
		// 清理过期的申请

		$applies	= get_account_meta($account_id, 'topic_applies', true) ?: [];
		$appiles	= array_filter($applies, function($apply){
			return $apply['status'] == 0 || $apply['time'] < time() - DAY_IN_SECONDS * 90;
		});

		array_unshift($applies, $apply);

		update_account_meta($account_id, 'topic_applies', $applies);
	}

	public static function update($account_id, $comment_id, $status){
		if($comment_obj = WPJAM_Comment::get_instance($comment_id)){
			$post_id 	= $comment_obj->post_id;
			$_post		= $post_id ? get_post($post_id) : null;

			if(!$_post || $_post->account_id != $account_id){
				return new WP_Error('bad_auth', '无操作权限');
			}

			if($comment_obj->approved != 0){
				return new WP_Error('invalid_apply_status', '无效的状态');
			}

			$result	= WPJAM_Comment::set_status($comment_id, $status);

			if(is_wp_error($result)){
				return $result;
			}

			$applies	= get_account_meta($account_id, 'topic_applies', true) ?: [];

			foreach($applies as &$apply){
				if($apply['comment_id'] == $comment_id){
					$apply['status']	= $status;
					$apply['time']		= time();

					update_account_meta($account_id, 'topic_applies', $applies);

					return true;
				}
			}

			return $result;
		}else{
			// 
		}

		return true;
	}

	public static function get_list($account_id){
		$posts_json	= [];

		if($applies	= get_account_meta($account_id, 'topic_applies', true) ?: []){
			WPJAM_Comment::update_caches(array_column($applies, 'comment_id'));
			WPJAM_Post::update_caches(array_column($applies, 'post_id'));

			foreach($applies as $apply){
				if($comment_obj	= WPJAM_Comment::get_instance($apply['comment_id'])){
					if($post_json = wpjam_get_post($apply['post_id'])){
						$posts_json[]	= array_merge($post_json, ['apply'=>$comment_obj->parse_for_json()]);
					}
				}else{
					// 要做一下清理
				}
			}
		}

		return ['topics'=>$posts_json];
	}
}