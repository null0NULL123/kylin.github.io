<?php
class WPJAM_Comment_Digg{
	private $id		= 0;
	public  $data	= [];
	public  $count	= 0;

	public function __construct($id){
		$this->id		= $id;
		$this->data		= get_comment_meta($id, 'digg_data', true) ?: [];
		$this->count	= (int)get_comment_meta($id, 'digg_count', true);
	}

	private function update($new_data){
		$updated	= update_comment_meta($this->id, 'digg_data', $new_data, $this->data);

		if($updated){
			$user_ids		= $new_data['user_ids'] ?? [];
			$user_emails	= $new_data['user_emails'] ?? [];

			$this->data		= $new_data;
			$this->count	= count($user_ids) + count($user_emails);

			update_comment_meta($this->id, 'digg_count', $this->count);
		}

		return $updated;
	}

	public function digg($type='digg'){
		$retry_times	= 10;	// CAS 处理

		do{
			$new_data	= $this->data = get_comment_meta($this->id, 'digg_data', true) ?: [];
			$is_digged	= $this->is_digged();

			if(($type == 'digg' && $is_digged) || ($type == 'undigg' && !$is_digged)){
				return true;
			}

			if(is_user_logged_in()){
				$user_ids	= $new_data['user_ids'] ?? [];

				if($type == 'digg'){
					$user_ids[]	= get_current_user_id(); 
				}else{
					$user_ids	= array_values(array_diff($user_ids, [get_current_user_id()])); 
				}

				$new_data['user_ids']	= $user_ids;
			}else{
				$commenter	= wp_get_current_commenter();

				if(empty($commenter['comment_author_email'])){
					return new WP_Error('bad_authentication', '无权限');
				}

				$user_emails	= $new_data['user_emails'] ?? [];

				if($type == 'digg'){
					$user_emails[]	= $commenter['comment_author_email'];
				}else{
					$user_emails	= array_values(array_diff($user_emails, wpjam_get_current_commenter_emails())); 
				}

				$new_data['user_emails']	= $user_emails;
			}

			$updated	= $this->update($new_data);
		}while(!$updated && $retry_times > 0);

		return $updated;
	}

	public function is_digged(){
		if(empty($this->data)){
			return false;
		}

		if(is_user_logged_in()){
			$user_ids	= $this->data['user_ids'] ?? [];

			return $user_ids && in_array(get_current_user_id(), $user_ids);
		}else{
			$commenter_emails	= wpjam_get_current_commenter_emails();

			if(empty($commenter_emails)){
				return false;
			}

			$user_emails	= $this->data['user_emails'] ?? [];

			return $user_emails && array_intersect($commenter_emails, $user_emails);
		}	
	}

	public function get_button($title=''){
		$digged	= $this->is_digged();
		$class	= 'comment-digg '.($digged ? 'is-digged' : 'is-undigged');
		$data	= ['comment_id'=>$this->id, 'type'=>($digged ? 'undigg' : 'digg')];

		if($title){
			$title	= str_replace('[count]', $this->count, $title);
		}else{
			$title	= '<span class="comment-digg-count">'.$this->count.'</span> <span class="dashicons dashicons-thumbs-up"></span>';
		}

		return '<a href="javascript:;" class="'.$class.'" '.wpjam_get_ajax_data_attr('comment-digg', $data).'>'.$title.'</a>';
	}

	private static $instances	= [];

	public static function get_instance($id){
		if(!isset($instances[$id])){
			$instances[$id]	= new self($id);
		}

		return $instances[$id];
	}

	public static function get_fields($type){
		if($type == 'setting'){
			return ['digg'	=> ['title'=>'评论点赞',	'type'=>'checkbox',	'description'=>'开启评论点赞功能']];
		}

		return [];
	}

	public static function api_callback($args){
		$comment_id	= (int)wpjam_get_parameter('comment_id', ['method'=>'POST', 'required'=>true]);
		$supports	= wpjam_comment_supports($comment_id, 'digg');

		if(is_wp_error($supports)){
			return $supports;
		}
		
		$digg_obj	= self::get_instance($comment_id);

		if($digg_obj->digg($args['digg_type'])){
			return ['comment'=>wpjam_get_comment($comment_id)];
		}else{
			return new WP_Error('digg_failed', '点赞失败');
		}
	}

	public static function ajax_callback(){
		$comment_id	= (int)wpjam_get_data_parameter('comment_id');
		$supports	= wpjam_comment_supports($comment_id, 'digg');

		if(is_wp_error($supports)){
			return $supports;
		}

		$digg_obj	= self::get_instance($comment_id);
		$type		= wpjam_get_data_parameter('type', ['sanitize_callback'=>'sanitize_key']);
		
		if($digg_obj->digg($type)){
			return [
				'count'	=> (int)$digg_obj->count, 
				'data'	=> http_build_query(['comment_id'=>$comment_id, 'type'=>($type == 'digg' ? 'undigg' : 'digg')])
			];
		}else{
			return new WP_Error('digg_failed', '点赞失败');
		}
	}

	public static function register_api($json, $comment_type, $post_type, $ct_obj){
		if($json == $post_type.'.'.$comment_type.'.digg'){
			return wpjam_register_api($json, [
				'title'		=> $ct_obj->labe.'点赞',
				'auth'		=> true,
				'digg_type'	=> 'digg',
				'callback'	=> [self::class, 'api_callback']
			]);
		}

		if($json == $post_type.'.'.$comment_type.'.undigg'){
			return wpjam_register_api($json, [
				'title'		=> '取消'.$ct_obj->labe.'点赞',
				'auth'		=> true,
				'digg_type'	=> 'undigg',
				'callback'	=> [self::class, 'api_callback']
			]);
		}
	}

	public static function column_callback($comment_id){
		return (int)get_comment_meta($comment_id, 'digg_count', true);
	}

	public static function filter_comment_json($comment_json, $comment_id){
		$digg_obj	= self::get_instance($comment_id);

		$comment_json['digg_count']	= $digg_obj->count;
		$comment_json['is_digged']	= $digg_obj->is_digged();

		return $comment_json;
	}

	public static function filter_comments_clauses($clauses, $query){
		if(in_array($query->query_vars['orderby'], ['digg_count', 'comment_date_gmt', ''])){
			global $wpdb;

			if (!empty($_GET['unapproved']) && !empty($_GET['moderation-hash'])){
				// 解决 Column 'comment_ID' in where clause is ambiguous错误
				$comment_id			= (int)$_GET['unapproved'];
				$clauses['where']	= str_replace(' AND comment_ID = '.$comment_id, ' AND '.$wpdb->comments.'.comment_ID = '.$comment_id, $clauses['where']);	
			}

			$clauses['fields']	.= ", (COALESCE(jam_cm.meta_value, 0)+0) as digg_count";
			$clauses['join']	.= " LEFT JOIN {$wpdb->commentmeta} jam_cm ON {$wpdb->comments}.comment_ID = jam_cm.comment_id AND jam_cm.meta_key = 'digg_count' ";
			$clauses['orderby']	= "digg_count DESC " . ($clauses['orderby'] ? ', '.$clauses['orderby'] : '');
			$clauses['groupby']	= 'comment_ID';
		}

		return $clauses;
	}

	public static function filter_comment_text($text, $comment){
		$digg_obj	= self::get_instance($comment->comment_ID);

		return $digg_obj->get_button().$text;
	}

	public static function on_comments_page_load($comment_type, $post_type){
		wpjam_register_list_table_column('digg_count', ['title'=>'点赞', 'column_callback'=>[self::class, 'column_callback']]);

		wp_add_inline_style('list-tables', "\n".'th.column-digg_count{width: 42px;}');
	}	

	public static function on_enqueue_scripts(){
		wpjam_ajax_enqueue_scripts();

		$style	= join("\n", [
			'.comment .dashicons{line-height: inherit;}',
			'a.comment-digg{float: right; margin-left: 10px;}',
			'a.comment-digg.is-undigged{color:#666;}'
		]);

		$script	= <<<'EOT'
jQuery(function($){
	$("body").on("click", ".comment-digg", function(e){
		e.preventDefault();

		$(this).wpjam_action(function(data){
			if(data.errcode != 0){
				alert(data.errmsg);
			}else{
				$(this).toggleClass("is-undigged").toggleClass("is-digged").data("data", data.data).find("span.comment-digg-count").html(data.count);
			}
		});
	});
});
EOT;
		if(did_action('wpjam_static')){
			wpjam_register_static('digg-script',	['title'=>'评论点赞脚本',	'type'=>'script',	'source'=>'value',	'value'=>$script]);
			wpjam_register_static('digg-style',		['title'=>'评论点赞样式',	'type'=>'style',	'source'=>'value',	'value'=>$style]);
			wpjam_register_static('dashicons',		['title'=>'Dashicons',	'type'=>'style',	'source'=>'file',	'file'=>ABSPATH.WPINC.'/css/dashicons.min.css',	'baseurl'=>home_url(WPINC.'/css/')]);
		}else{
			wp_enqueue_style('dashicons');
			wp_add_inline_style('dashicons', $style);

			wp_enqueue_script('jquery');
			wp_add_inline_script('jquery', $script);
		}
	}
}

wpjam_register_comment_feature('digg',	['title'=>'点赞', 'model'=>'WPJAM_Comment_Digg']);

wpjam_register_ajax('comment-digg',	['nopriv'=>true, 'callback'=>['WPJAM_Comment_Digg', 'ajax_callback'],	'nonce_keys'=>['comment_id']]);

add_action('wp_enqueue_scripts',	['WPJAM_Comment_Digg', 'on_enqueue_scripts'], 20);



