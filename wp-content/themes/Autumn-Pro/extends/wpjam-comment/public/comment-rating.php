<?php
class WPJAM_Comment_Rating{
	public static function get_rating($post_id){
		if(!metadata_exists('post', $post_id, 'rating')){
			self::update_rating($post_id);	
		}

		return (float)(get_post_meta($post_id, 'rating', true) ?: 5);
	}

	public static function update_rating($post_id){
		if(post_type_supports(get_post_type($post_id), 'rating')){
			$ratings	= [];

			foreach(get_comments(['post_id'=>$post_id, 'status'=>'approve', 'order'=>'ASC', 'meta_key'=>'rating']) as $comment){
				if($rating = get_comment_meta($comment->comment_ID, 'rating', true)){
					$ratings[]	= $rating;
				}
			}

			$rating	= $ratings ? round(array_sum($ratings)/count($ratings), 2) : 5;

			update_post_meta($post_id, 'rating', $rating);
		}
	}

	public static function get_fields($type, $post_type){
		if(post_type_supports($post_type, 'rating')){
			if($type == 'meta'){
				return ['rating'	=> ['title'=>'评分',		'type'=>'number',	'step'=>0.5,	'min'=>0.5,	'max'=>5,	'value'=>5]];
			}elseif($type == 'setting'){
				return ['rating'	=> ['title'=>'评论评分',	'type'=>'checkbox',	'description'=>'用户发布评论的时候支持插入1-5评分，需主题支持']];
			}	
		}

		return [];
	}

	public static function column_callback($comment_id){
		$rating	= get_comment_meta($comment_id, 'rating', true);

		if($rating && $rating > 0 && $rating <= 5){
			$result	= str_repeat('<span class="dashicons comment-rating dashicons-star-filled"></span>', (int)$rating);

			if($rating - (int)$rating == 0.5){
				$result	.= '<span class="dashicons comment-rating dashicons-star-half"></span>';
			}
		}else{
			$result	= '';
		}

		return $result;
	}

	public static function filter_comment_json($comment_json, $comment_id){
		$comment_json['rating']	= (float)get_comment_meta($comment_id, 'rating', true);

		return $comment_json;
	}

	public static function filter_post_json($post_json, $post_id){
		if(post_type_supports($post_json['post_type'], 'rating')){
			$post_json['rating']	= self::get_rating($post_id);
		}

		return $post_json;
	}

	public static function on_comments_page_load($comment_type, $post_type){
		wpjam_register_list_table_column('rating', ['title'=>'评分', 'column_callback'=>[self::class, 'column_callback']]);

		wp_add_inline_style('list-tables', "\n".implode("\n", ['th.column-rating{min-width: 56px;}','table.wp-list-table span.comment-rating{font-size: 14px;}']));
	}

	public static function on_update_comment_count($post_id, $new, $old){
		self::update_rating($post_id);
	}
}

wpjam_register_comment_feature('rating', ['model'=>'WPJAM_Comment_Rating']);

add_action('wp_update_comment_count',	['WPJAM_Comment_Rating', 'on_update_comment_count'], 10, 3);
