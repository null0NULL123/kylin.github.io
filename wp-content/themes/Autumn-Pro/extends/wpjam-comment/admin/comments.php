<?php
class WPJAM_Comments_Admin{
	public static function get($id){
		return get_comment($id, ARRAY_A);
	}

	public static function insert($data){
		$data['user_id']		= 0;
		$data['author']			= $data['commenter'];
		$data['author_email']	= '';
		$data['author_url']		= '';

		$post_id		= (int)wpjam_get_data_parameter('post_id');
		$comment_obj	= wpjam_add_post_comment($post_id, $data);

		return is_wp_error($comment_obj) ? $comment_obj : $comment_obj->id;
	}

	public static function approve($id){
		return wp_set_comment_status($id, 'approve', $wp_error=true);
	}

	public static function unapprove($id){
		return wp_set_comment_status($id, 'hold', $wp_error=true);
	}

	public static function spam($id){
		return wp_spam_comment($id);
	}

	public static function delete($id){
		return wp_delete_comment($id);
	}

	public static function reply($id, $data){
		$reply_id	= $data['reply_id'] ?? 0;
		$reply_text	= $data['reply_text'] ?? '';

		if(empty($reply_text)){
			return new WP_Error('empty_reply', '回复的文本不能为空');
		}

		if($reply_id){
			return wp_update_comment([
				'comment_content'	=> $reply_text,
				'comment_ID'		=> $reply_id
			], $wp_error=true);
		}else{
			$post_id	= get_comment($id)->comment_post_ID;

			return wpjam_add_post_comment($post_id, [
				'user_id'	=> get_current_user_id(),
				'comment'	=> $reply_text,
				'parent'	=> $id,
			]);
		}
	}

	public static function get_primary_key(){
		return 'comment_ID';
	}

	public static function filter_clauses($clauses){
		global $wpdb;

		$ct_obj		= self::get_type_object();
		$type_str	= $ct_obj->name	== 'comment' ? "'comment', ''" : "'".esc_sql($ct_obj->name)."'";
		$ct_where	= "ct.comment_type IN ({$type_str}) AND ct.comment_parent=0 AND ct.comment_approved NOT IN ('spam', 'trash', 'post-trashed')";

		$clauses['fields']	.= ', ct.comment_post_ID, MAX(ct.comment_ID) as cid';
		$clauses['join']	= "INNER JOIN {$wpdb->comments} AS ct ON {$wpdb->posts}.ID = ct.comment_post_ID AND {$ct_where}";
		$clauses['groupby']	= "ct.comment_post_ID";
		$clauses['orderby']	= "cid DESC";

		return $clauses;
	}

	public static function get_posts(&$total=0){
		static $posts, $found;

		if(!isset($posts)){
			$left_type	= wpjam_get_data_parameter('left_type') ?: 'new';
			$left_paged	= wpjam_get_data_parameter('left_paged') ?: 1;
			$post_type	= wpjam_get_plugin_page_setting('post_type');
			$ct_obj		= self::get_type_object();

			$query_args	= [
				'post_type'			=> $post_type,
				'paged'				=> $left_paged,
				'post_status'		=> '',
				'posts_per_page'	=> 10
			];

			if(in_array($left_type, ['publish', 'most'])){
				if($left_type == 'most'){
					if($ct_obj->name == 'comment'){
						$query_args['orderby']		= 'comment_count';
						$query_args['comment_count']= [
							'value'		=> 1,
							'compare'	=> '>='
						];
					}else{
						$query_args['orderby']	= 'meta_value_num';
						$query_args['meta_key']	= $ct_obj->plural;
					}
				}
			}elseif($left_type == 'new'){
				add_filter('posts_clauses', [self::class, 'filter_clauses']);
			}

			$wp_query	= new WP_Query($query_args);

			$posts	= $wp_query->posts;
			$found	= $wp_query->found_posts;
		}

		$total	= $found;

		return $posts;
	}

	public static function get_type_object(){
		$comment_type	= wpjam_get_plugin_page_setting('comment_type');
		return wpjam_get_comment_type_object($comment_type);
	}

	public static function query_data($args){
		$post_id	= (int)wpjam_get_data_parameter('post_id');

		if(empty($post_id)){
			$posts		= self::get_posts();
			$post_id	= $posts ? current($posts)->ID : 0;
		}

		if($post_id){
			$ct_obj	= self::get_type_object();

			return $ct_obj->query_data($post_id, $args);
		}else{
			return ['items'=>[], 'total'=>0];
		}
	}

	public static function render_item($item){
		$ct_obj	= self::get_type_object();

		return $ct_obj->render_item($item);
	}

	public static function get_actions(){
		$ct_obj	= self::get_type_object();

		return $ct_obj->get_actions();
	}

	public static function get_fields($action_key='', $id=0){
		$post_type	= wpjam_get_plugin_page_setting('post_type');
		$ct_obj		= self::get_type_object();

		$fields	= $ct_obj->get_fields($action_key, ['id'=>$id,	'post_type'=>$post_type]);

		if($action_key == ''){
			$fields	= array_merge(['author'=> ['title'=>'用户',	'type'=>'text',	'show_admin_column'=>'only']], $fields);
			$fields	= array_merge($fields, $ct_obj->get_meta_fields($post_type));
		}

		return $fields;
	}

	public static function col_left(){
		$left_paged	= wpjam_get_data_parameter('left_paged') ?: 1;
		$post_id	= wpjam_get_data_parameter('post_id');

		$posts		= self::get_posts($total);
		$ct_obj		= self::get_type_object();

		$post_type	= wpjam_get_plugin_page_setting('post_type');
		$pt_label	= get_post_type_object($post_type)->label;
		$options	= ['new'=>'最新'.$ct_obj->label, 'most'=>'最多'.$ct_obj->label, 'publish'=>'最新'.$pt_label];

		if($ct_obj->post_meta){
			$options	= wpjam_array_except($options, 'new');
		}
		?>
		<div class="tablenav">
			<div class="alignleft actions"><?php 
			echo wpjam_render_field([
				'key'		=> 'left_type',
				'type'		=> 'select',
				'class'		=> 'left-filter',
				'value'		=> wpjam_get_data_parameter('left_type'),
				'options'	=> $options
			]);
			?></div>
		</div>
		<table class="widefat striped">
			<thead>
				<tr><th><?php echo $pt_label; ?></th></tr>
			</thead>
			<tbody>
			<?php 

			if($posts){

			if($post_id){
				$post_ids	= wp_list_pluck($posts, 'ID');

				if(!in_array($post_id, $post_ids)){ 
					array_unshift($posts, get_post($post_id));
				}
			}else{
				$post_id	= $posts[0]->ID;
			}

			$post_ids	= array_column($posts, 'ID');
			$counts		= [];

			foreach(WPJAM_Comment::get_counts($post_ids, ['type'=>$ct_obj->name]) as $count){
				$comment_post_id	= $count['comment_post_ID'];
				$comment_approved	= $count['comment_approved'];

				$counts[$comment_post_id][$comment_approved]	= $count['count'];
			}
			?>
			<?php foreach($posts as $post){ 
				$class	= 'left-item';

				if($post_id == $post->ID){
					$class	.= ' left-current';
				}
			?>
				<tr data-id="<?php echo $post->ID; ?>" id="left-<?php echo $post->ID; ?>" class="<?php echo $class; ?>">
					<td><?php 

					$count		= $ct_obj->get_count($post->ID);

					$approved	= $counts[$post->ID][1] ?? 0;
					$unapproved	= $counts[$post->ID][0] ?? 0;

					if($approved != $count){
						$diff	= '不相同('.$approved.')';
					}else{
						$diff	= '';
					}

					if($ct_obj->name == 'comment'){
						$count	= $count ? $count.'条评论': (comments_open($post) ? '暂无评论' : '评论未开启');
					}else{
						// if($diff){
						// 	$count	= $approved;
						// 	update_post_meta($post->ID, $ct_obj->plural, $count);
						// }

						$count	= ($count ? $count.' ' : '暂无').$ct_obj->label;
					}

					// $count	.= $diff;

					$post_card	= '<p class="row-title column-response">';
					$post_card	.= '<span class="post-title">'.get_the_title($post).'</span>'._post_states($post, false);

					// if($unapproved){
					// 	$post_card	.= '<span class=""><span class="post-com-count post-com-count-pending"><span class="comment-count-pending" aria-hidden="true">'.$unapproved.'</span><span class="screen-reader-text">'.$unapproved.'条待审</span></span></span>';
					// }

					$post_card	.= '</p>';
					$post_card	.= '<p>';
					$post_card	.= '<span class="post-time">'.get_the_time('Y-m-d H:i:s', $post).'</span>';
					$post_card	.= '<span class="comment-count wp-ui-highlight">'.$count.'</span>';
					$post_card	.= '</p>';

					echo $post_card;

					?></td>
				</tr>
			<?php } }else{ ?>
				<tr class="no-items"><td class="colspanchange">找不到<?php echo $pt_label;?>。</td></tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr><th><?php echo $pt_label; ?></th></tr>
			</tfoot>
		</table>

		<?php

		return ['total_items'=>$total, 'per_page'=>10, 'left_value'=>$post_id];
	}
}

wp_add_inline_style('list-tables', "\n".join("\n", [
	'th.manage-column{min-width:28px;}',
	'th.column-author{min-width: 220px;}',
	'th.column-comment_date{width: 100px;}',
	'.widefat td.column-comment p{margin:0 0 .6em 0;}',
	'span.author-pad{float:left; margin-right:10px;}',

	'@media screen and (min-width: 782px){ #col-left{width: 30%;} #col-right{width: 70%} }',

	'div#col-left td{padding-left: 14px; padding-right: 20px;}',
	'div#col-left td a:focus{box-shadow: inherit;}',
	'div#col-left td span.post-time{font-size:smaller;}',
	'div#col-left td span.comment-count{font-size:smaller; float:right; padding:2px 4px;}'
])."\n");

add_action('admin_head', function(){
	?>
	<script type="text/javascript">
	jQuery(function($){
		$('body').on('click', 'a.reply_to', function(event){
			var parent	= $(this).data('parent');
			var top		= $('#comment-'+parent).offset().top-40;

			$('html').animate({scrollTop: top}, 500, function(){
				$('#comment-'+parent).animate({opacity: 0.1}, 500).animate({opacity: 1}, 500);
			});

		    event.preventDefault();
		});
	});
	</script>
	<?php
});