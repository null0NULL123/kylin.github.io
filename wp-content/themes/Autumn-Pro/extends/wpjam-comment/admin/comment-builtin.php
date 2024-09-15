<?php
class WPJAM_Comment_Bultin_Page{
	public static function filter_count_comments($stats, $post_id){
		if($post_id){
			return $stats;
		}

		$cache_key		= 'comment_stats:'.wp_cache_get_last_changed('comment');
		$stats_object	= wp_cache_get($cache_key, 'comment');

		if($stats_object === false){
			$stats	= [
				'approved'		=> 0,
				'moderated'		=> 0,
				'spam'			=> 0,
				'trash'			=> 0,
				'post-trashed'	=> 0,
			];

			$actions	= wpjam_get_comment_types(['action'=>true]);

			$where		= "WHERE comment_type NOT IN ('".implode("','", $actions)."')";
			$totals		= (array)$GLOBALS['wpdb']->get_results("SELECT comment_approved, COUNT( * ) AS total FROM {$GLOBALS['wpdb']->comments} {$where} GROUP BY comment_approved", ARRAY_A);

			if($totals){
				$totals	= array_column($totals, 'total', 'comment_approved');
				
				if(isset($totals['0'])){
					$totals['moderated']	= wpjam_array_pull($totals, '0');
				}

				if(isset($totals['1'])){
					$totals['approved']	= wpjam_array_pull($totals, '1');
				}

				$stats	= wp_parse_args($totals, $stats);
			}

			$stats['all']				= $stats['approved']+$stats['moderated'];
			$stats['total_comments']	= $stats['all']+$stats['spam'];

			$stats_object	= (object)$stats;

			wp_cache_set($cache_key, $stats_object, 'comment', DAY_IN_SECONDS);
		}

		return $stats_object;
	}

	public static function filter_comments_query_args($args){
		if($actions = wpjam_get_comment_types(['action'=>true])){
			$args['type__not_in']	= $args['type__not_in'] ?? [];
			$args['type__not_in']	+= $actions;
		}

		return $args;
	}

	public static function filter_comment_email($email){
		return (strpos($email, '.weapp') || strpos($email, '.weixin')) ? '' :$email;
	}

	public static function filter_comment_row_actions($actions, $comment){
		return $actions+['comment_id'=>'ID：'.$comment->comment_ID];
	}

	public static function filter_post_single_row($single_row, $post_id){
		return preg_replace_callback('/(<div class="post-com-count-wrapper">)(.*?)<\/div>/is', function($matches) use($post_id){
			$action	= wpjam_get_list_table_row_action('add_comment', ['id'=>$post_id,	'dashicon'=>'plus-alt2', 'class'=>'row-action']);

			if(strpos($matches[0], '<span aria-hidden="true">&#8212;</span>') !== false){
				return $matches[1].$matches[2].' '.$action.'</div>';
			}else{
				return $matches[0].$action;
			}
		}, $single_row);
	}

	public static function get_fields($post_id, $action_key){
		$ct_obj	= wpjam_get_comment_type_object('comment');

		return $ct_obj->get_fields('add', ['post_type'=>get_post_type($post_id)]);
	}

	public static function add_comment($post_id, $data){
		$data['user_id']		= 0;
		$data['author']			= $data['commenter'];
		$data['author_email']	= '';
		$data['author_url']		= '';

		return wpjam_add_post_comment($post_id, $data);
	}

	public static function on_page_load($screen_base, $current_screen){
		if($screen_base == 'edit-comments'){
			if($post_id	= (int)wpjam_get_parameter('p')){
				$post_type	= get_post_type($post_id);

				if($post_type == 'post'){
					wp_redirect(admin_url('edit.php?page='.$post_type.'-comments&post_id='.$post_id));
				}else{
					wp_redirect(admin_url('edit.php?post_type='.$post_type.'&page='.$post_type.'-comments&post_id='.$post_id));
				}

				exit;
			}

			add_filter('comments_list_table_query_args',	[self::class, 'filter_comments_query_args'], 1);
			add_filter('comment_email', 					[self::class, 'filter_comment_email'], 9);
			add_filter('comment_row_actions',				[self::class, 'filter_comment_row_actions'], 10, 2);
		}elseif($screen_base == 'edit'){
			if(post_type_supports($current_screen->post_type, 'comments')){
				wpjam_register_list_table_action('add_comment', [
					'title'			=> '添加评论',
					'page_title'	=> '添加评论',
					'submit_text'	=> '添加',
					'row_action'	=> false,
					'fields'		=> [self::class, 'get_fields'],
					'callback'		=> [self::class, 'add_comment']
				]);

				add_filter('wpjam_single_row', [self::class, 'filter_post_single_row'], 10, 2);
			}
		}
	}
}

add_filter('wp_count_comments',			['WPJAM_Comment_Bultin_Page', 'filter_count_comments'], 10 ,2);
add_action('wpjam_builtin_page_load',	['WPJAM_Comment_Bultin_Page', 'on_page_load'], 10, 2);