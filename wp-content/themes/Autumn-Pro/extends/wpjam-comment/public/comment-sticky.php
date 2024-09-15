<?php
class WPJAM_Comment_Sticky{
	private $post_id	= 0;
	public  $stickies	= [];

	public function __construct($post_id){
		$this->post_id	= $post_id;
		$this->stickies	= get_post_meta($post_id, 'sticky_comments', true) ?: [];
	}

	public function stick($id){
		if(!$this->is_sticky($id)){
			if(count($this->stickies) >= 5){
				return new WP_Error('sticky_comments_over_quato', '最多5个置顶评论。');
			}

			if(get_comment($id)->comment_type != 'comment'){
				return new WP_Error('unsupport_type', '只支持置顶评论。');
			}

			array_unshift($this->stickies, $id);

			$this->save();

			do_action('wpjam_stick_comment', $id);
		}

		return true;
	}

	public function unstick($id){
		if($this->is_sticky($id)){
			$this->stickies	= array_diff($this->stickies, [$id]);

			$this->save();

			do_action('wpjam_unstick_comment', $id);
		}

		return true;
	}

	public function is_sticky($id){
		return $this->stickies && in_array($id, $this->stickies);
	}

	private function save(){
		if($this->stickies){
			$this->stickies	= array_values(array_unique($this->stickies));
			return update_post_meta($this->post_id, 'sticky_comments', $this->stickies);
		}else{
			return delete_post_meta($this->post_id, 'sticky_comments');
		}
	}

	private static $instances	= [];

	public static function get_instance($id){
		if(!isset($instances[$id])){
			$instances[$id]	= new self($id);
		}

		return $instances[$id];
	}

	public static function get_fields($type, $post_type){
		if($type == 'setting'){
			return ['sticky'=>['title'=>'评论置顶',	'type'=>'checkbox',	'value'=>1,	'description'=>'开启评论置顶功能']];
		}

		return [];
	}

	public static function filter_the_comments($comments, $query){
		if($query->query_vars['offset'] == 0 && $query->query_vars['paged'] == 1){
			add_filter('get_comment_author',	[self::class, 'filter_comment_author'], 10, 3);

			$post_id	= $query->query_vars['post_id'];
			$instance	= self::get_instance($post_id);
			$stickies	= $instance->stickies;

			if($stickies && is_array($stickies)){
				$stickies	= array_reverse($stickies);
				$comments	= array_combine(array_column($comments, 'comment_ID'), $comments);

				foreach($stickies as $i=>$sticky_comment_id){
					if(isset($comments[$sticky_comment_id])){
						$sticky_comment	= $comments[$sticky_comment_id];
						$comments		= [$sticky_comment_id=>$sticky_comment]+$comments;

						unset($stickies[$i]);
					}else{
						$comments	= [$sticky_comment_id=>null]+$comments;
					}
				}

				if(!empty($stickies)){
					$stickies	= get_comments([
						'comment__in'	=> $stickies,
						'orderby'		=> 'comment__in',
						'type'			=> 'comment'
					]);

					foreach($stickies as $sticky){
						$comments[$sticky->comment_ID]	= $sticky;
					}
				}

				return array_values(array_filter($comments));
			}
		}

		return $comments;
	}

	public static function filter_comment_author($author, $comment_id, $comment){
		$instance	= self::get_instance($comment->comment_post_ID);

		if($instance->is_sticky($comment_id)){
			return '<span class="dashicons dashicons-sticky"></span> '.$author;
		}

		return $author;
	}

	public static function filter_comment_json($comment_json, $comment_id){
		if($comment_json['type'] == 'comment' && $comment_json['parent'] == 0){
			$post_id	= $comment_json['post_id'];
			$post_type	= get_post_type($post_id);

			if(is_singular($post_type) && $post_id == get_queried_object_id()){
				$comment_json['is_sticky']	= self::get_instance($post_id)->is_sticky($comment_id);
			}
		}

		return $comment_json;
	}

	public static function show_if($id, $action_key){
		if($comment = wpjam_get_comment($id)){
			if($comment['approved'] && $comment['parent'] == 0){
				$stick_obj	= self::get_instance($comment['post_id']);
				$is_sticky	= $stick_obj->is_sticky($id);

				if(($is_sticky && $action_key == 'unstick') 
					|| (!$is_sticky && $action_key == 'stick') 
				){
					return true;
				}
			}
		}

		return false;
	}

	public static function list_action($id, $data, $list_action){
		$post_id	= get_comment($id)->comment_post_ID;
		$stick_obj	= self::get_instance($post_id);

		return $stick_obj->$list_action($id);
	}

	public static function on_comments_page_load($comment_type, $post_type){
		if($comment_type == 'comment'){
			wpjam_register_list_table_action('stick', 	[
				'title'		=> '置顶',
				'callback'	=> [self::class, 'list_action'],
				'show_if'	=> [self::class, 'show_if'],
				'direct'	=> true,
				'confirm'	=> true
			]);

			wpjam_register_list_table_action('unstick',	[
				'title'		=>'取消置顶',
				'callback'	=> [self::class, 'list_action'],
				'show_if'	=> [self::class, 'show_if'],
				'direct'	=> true,
				'confirm'	=> true
			]);
		}		
	}
}

wpjam_register_comment_feature('sticky', ['model'=>'WPJAM_Comment_Sticky']);