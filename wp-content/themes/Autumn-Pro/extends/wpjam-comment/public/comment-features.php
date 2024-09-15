<?php
class WPJAM_Comment_Anonymous{
	public static function get_fields($type){
		if($type == 'meta'){
			return ['anonymous'	=> ['title'=>'匿名',	'type'=>'checkbox',	'show_admin_column'=>true]];
		}elseif($type == 'setting'){
			return ['anonymous'	=> ['title'=>'匿名评论',	'type'=>'checkbox',	'description'=>'支持匿名评论，即评论者信息不显示，需主题支持']];
		}
	}

	public static function render_item($item){
		$item['anonymous']	= get_comment_meta($item['id'], 'anonymous', true) ? '是' : '否';

		return $item;
	}

	public static function filter_comment_json($comment_json, $comment_id){
		if(get_comment_meta($comment_id, 'anonymous', true)){
			$comment_json['anonymous']	= true;
			$comment_json['user_id']	= 0;
			$comment_json['author']		= ['email'=>'', 'author'=>'匿名', 'nickname'=>'匿名', 'user_id'=>0,  'avatar'=>get_avatar_url('')];
		}

		return $comment_json;
	}
}

class WPJAM_Comment_Images{
	public static function get_fields($type){
		if($type == 'meta'){
			return ['images'	=> ['title'=>'图片',		'type'=>'mu-img',	'item_type'=>'url',	'max_items'=>3]];
		}elseif($type == 'setting'){
			return ['images'	=> ['title'=>'图片评论',	'type'=>'checkbox',	'description'=>'用户发布评论的时候支持插入最多三张图片，需主题支持']];
		}

		return [];
	}

	public static function render_item($item){
		$meta_value	= get_comment_meta($item['id'], 'images', true);

		if(is_array($meta_value)){
			$images	= [];

			foreach($meta_value as $image) {
				$images[]	= '<a class="thickbox" rel="images-'.$item['id'].'" href="'.wpjam_get_thumbnail($image, 600).'"><img src="'.wpjam_get_thumbnail($image, 120, 120).'" width="60" /></a> ';
			}

			$item['comment']	.= "\n\n".implode(' ', $images);
		}

		return $item;
	}

	public static function filter_comment_json($comment_json, $comment_id){
		$images = get_comment_meta($comment_id, 'images', true);
			
		if($images){
			$thumb_size	= count($images) > 1 ? '200x200' : '300x0';

			array_walk($images, function(&$image) use($thumb_size){
				$image = [
					'thumb'		=> wpjam_get_thumbnail($image, $thumb_size),
					'original'	=> wpjam_get_thumbnail($image, '1080x0')
				];
			});
		}

		$comment_json['images']	= $images ?: [];

		return $comment_json;
	}
}

class WPJAM_Comment_Moderation{
	public static function filter_pre_comment_approved(){
		return 0;
	}

	public static function get_fields($type){
		if($type == 'setting'){
			if(get_option('comment_moderation') == 1){
				return ['moderation'	=> ['title'=>'人工审核',	'type'=>'view',		'value'=>'全局设置评论必须经人工批准']];
			}else{
				return ['moderation'	=> ['title'=>'人工审核',	'type'=>'checkbox',	'description'=>'评论必须经人工批准']];
			}
		}

		return [];
	}
}

wpjam_register_comment_feature('anonymous', 	['model'=>'WPJAM_Comment_Anonymous']);
wpjam_register_comment_feature('images', 		['model'=>'WPJAM_Comment_Images']);
wpjam_register_comment_feature('moderation',	['model'=>'WPJAM_Comment_Moderation']);