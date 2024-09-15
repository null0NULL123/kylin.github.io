<?php
class WPJAM_Comment_Reply{
	public static function get_fields($type){
		if($type == 'setting'){
			return ['reply_type'=>['title'=>'回复方式',	'type'=>'select',	'value'=>'all',	'options'=>['all'=>'任何人都可回复',	'admin_reply'=>'仅管理员可回复']]];
		}

		return [];
	}

	public static function render_item($item, $reply_type){
		$comment_id	= $item['id'];
		
		if($reply_type == 'admin_reply'){
			$comment	= get_comment($comment_id);

			foreach($comment->get_children(['format'=>'flat']) as $child){
				if($child->user_id && user_can($child->user_id, 'manage_options')){
					$reply_action	= wpjam_get_list_table_row_action('reply',[
						'id'	=> $comment_id,
						'data'	=> ['reply_id'=>$child->comment_ID],
						'title'	=> '修改',
					]);

					$item['comment']	.= '<div class="admin_reply">'.wpautop('管理员回复（'.$reply_action."）：".$child->comment_content).'</div>'; 

					unset($item['row_actions']['reply']);

					break;
				}
			}
		}elseif($reply_type == 'all'){
			static $top_comment_id;

			if($parent = $item['comment_parent']){
				if(isset($top_comment_id) && $top_comment_id != $parent){
					if($parent_comment	= get_comment($parent)){
						$item['comment']	=  '<a class="reply_to" data-parent="'.$parent.'" href="#comment-'.$parent.'">@'.$parent_comment->comment_author.'</a> '.$item['comment'];
					}
				}
			}else{
				$top_comment_id	= $comment_id;
			}
		}

		return $item;
	}

	public static function filter_comment_json($comment_json, $comment_id, $args, $reply_type){
		if($reply_type == 'disabled'){
			return $comment_json;
		}

		if($comment_json['parent']){
			if($reply_type == 'all'){
				if(empty($args['top_comment_id']) || $args['top_comment_id'] != $comment_json['parent']){
					$parent_comment	= get_comment($comment_json['parent']);
					$comment_json['reply_to']	= $parent_comment ? $parent_comment->comment_author : '';
				}
			}else{
				$comment_json['author']	= [];
			}
		}else{
			$comment_children = get_comment($comment_id)->get_children([
				'format'	=> 'flat',
				'type'		=> $comment_json['type'],
				'status'	=> $args['status'] ?? 'all'
			]);

			$comment_json['children']	= [];

			if($comment_children){
				$args['top_comment_id']	= $comment_json['id'];

				foreach($comment_children as $comment_child){
					if($reply_type == 'admin_reply'){
						if($comment_child->user_id && user_can($comment_child->user_id, 'manage_options')){
							$comment_json['children'][]	= wpjam_get_comment($comment_child, $args);
						}
					}else{
						$comment_json['children'][]	= wpjam_get_comment($comment_child, $args);
					}
				}
			}
		}

		return $comment_json;
	}

	public static function filter_the_comments($comments, $query, $reply_type){
		if($reply_type == 'all'){
			if(is_admin()){
				$items	= [];

				foreach(array_values($comments) as $comment){
					$items[]	= $comment;
					
					foreach($comment->get_children(['format'=>'flat']) as $child){
						$items[] = $child;
					}
				}

				$query->query_vars['hierarchical']	= false;

				return $items;
			}
		}else{
			add_filter('comment_reply_link',	'__return_empty_string');
		}

		return $comments;
	}

	public static function filter_comment_text($text, $comment){
		// static $top_comment_id;

		if($comment->comment_parent){
			// if(isset($top_comment_id) && $comment->comment_parent != $top_comment_id){
				$parent	= get_comment($comment->comment_parent);
				$text	= '<a href="'.esc_url(get_comment_link($parent)).'">@'.$parent->comment_author.'</a> '.$text;
			// }
		}else{
			// $top_comment_id	= $comment->comment_ID;
		}

		return $text;
	}

	public static function on_pre_get_comments($query, $reply_type){
		$query->query_vars['hierarchical']	= $reply_type == 'disabled' ? false : 'threaded';
	}

	public static function on_comments_page_load(){
		wp_add_inline_style('list-tables', "\n".'td div.admin_reply{background: #ffe; padding:1px 4px;}');
	}
}

if(get_option('thread_comments')){
	wpjam_register_comment_feature('reply_type', ['model'=>'WPJAM_Comment_Reply']);

	// add_filter('pre_option_thread_comments_depth',	function(){return 2;});
}

