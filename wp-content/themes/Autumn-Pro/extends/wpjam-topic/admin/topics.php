<?php
class WPJAM_Topics_Admin{
	public static function __callStatic($method, $args){
		$switched	= wpjam_topic_switch_to_blog();

		if(method_exists(self::class, '_'.$method)){
			$result	= call_user_func([self::class, '_'.$method], ...$args);
		}elseif(in_array($method, ['get', 'get_groups', 'get_types', 'insert', 'delete'])){
			$result	= call_user_func(['WPJAM_Topic', $method], ...$args);
		}elseif($id = wpjam_array_pull($args, 0)){
			$object	= WPJAM_Topic::get_instance($id);

			$result	= call_user_func_array([$object, $method], $args);
		}else{
			$result	= null;
		}

		if($switched){
			restore_current_blog();
		}

		return $result;
	}

	public static function get_views(){
		$views	= [];
		$is_all	= true;

		if(current_user_can('manage_topics', 0, 'stick')){
			if(function_exists('wpjam_get_stickies')){
				if($stickies = wpjam_get_stickies('topic')){
					if($_sticky	= wpjam_get_data_parameter('show_sticky')){
						$class	= 'current';
						$is_all	= false;	
					}else{
						$class	= '';
					}
					
					$count	= '<span class="count">（'.count($stickies).'）</span>';

					$views['sticky']	= wpjam_get_list_table_filter_link(['show_sticky'=>1], '置顶'.$count, $class);
				}
			}
		}

		if(current_user_can('manage_topics', 0, 'edit')){
			if($count = wp_count_posts('topic')->pending){
				if(wpjam_get_data_parameter('status') == 'pending'){
					$class	= 'current';
					$is_all	= false;
				}else{
					$class	= '';
				}

				$count	= '<span class="count">（'.$count.'）</span>';

				$views['status-pending']	= wpjam_get_list_table_filter_link(['status'=>'pending'], '待审'.$count, $class);
			}
		}

		if(wpjam_topic_get_setting('topic_type')){
			if($_type = wpjam_get_data_parameter('topic_type')){
				$is_all	= false;
			}

			foreach(self::get_types() as $type){
				$slug	= $type['slug'];
				$class	= $_type == $slug ? 'current' : '';

				$views['type_'.$slug]	= wpjam_get_list_table_filter_link(['topic_type'=>$slug], $type['name'], $class);
			}
		}else{
			if($_group	= wpjam_get_data_parameter('group')){
				$is_all	= false;
			}

			foreach(self::get_groups() as $group){	
				$slug	= $group['slug'];
				$class	= $_group == $slug ? 'current' : '';

				$views['group_'.$slug]	= wpjam_get_list_table_filter_link(['group'=>$slug], $group['name'], $class);
			}
		}

		$class	= $is_all ? 'current' : '';

		return ['all'=>wpjam_get_list_table_filter_link([], '全部', $class)]+$views;
	}

	public static function query_items($limit, $offset){
		$args	= [];

		if(current_user_can('edit_posts')){
			if(function_exists('wpjam_get_stickies') && wpjam_get_data_parameter('show_sticky')){
				$args['post__in']		= wpjam_get_stickies('topic');
				$args['orderby']		= 'post__in';
			}elseif($status = wpjam_get_data_parameter('status')){
				$args['post_status']	= $status;
			}
		}

		if(!isset($args['post_status'])){
			$args['post_status']	= current_user_can('manage_options') ? 'all' : 'publish';
		}

		if(!isset($args['orderby'])){
			$args['orderby']	= 'last_comment_time';
		}

		$args['post_type']		= 'topic';
		$args['posts_per_page']	= $limit;
		$args['offset']			= $offset;

		if(wpjam_topic_get_setting('topic_type')){
			if($topic_type = wpjam_get_data_parameter('topic_type')){
				$args['topic_type']	= $topic_type;
			}
		}

		if($group = wpjam_get_data_parameter('group')){
			$args['group']	= $group;
		}

		if(wpjam_topic_get_setting('account')){
			if($account_id = wpjam_get_data_parameter('account_id')){
				$args['account_id']	= $account_id;
			}
		}else{
			if($user_id = wpjam_get_data_parameter('user_id')){
				$args['author']		= $user_id;
			}
		}	

		if($s = wpjam_get_data_parameter('s')){
			$args['s']		= $s;
		}

		return self::query_data($args);
	}

	public static function render_item($item){
		$post	= get_post($item['id']);		

		$item['topic']	= self::render_topic($item);

		if(wpjam_topic_get_setting('audit')){
			if($item['status'] == 'publish'){
				$item['class']	= 'approved';
			}else{
				$item['class']	= 'unapproved';
			}
		}

		return wpjam_array_except($item, 'row_actions');
	}

	public static function get_actions(){
		$actions	= [
			'add'		=>['title'=>'发布'],
			'edit'		=>['title'=>'编辑'],
			'set_type'	=>['title'=>'修改类型'],
			'comment'	=>['title'=>'回复',		'page_title'=>'详情'],
			'view'		=>['title'=>'查看',		'page_title'=>'详情',	'submit_text'=>''],
			'approve'	=>['title'=>'批准',		'direct'=>true],
			'unapprove'	=>['title'=>'驳回',		'direct'=>true],
			'delete'	=>['title'=>'删除',		'page_title'=>'删除',	'direct'=>true,	'confirm'=>true],
			'open'		=>['title'=>'开启',		'page_title'=>'开启回复',	'direct'=>true,	'confirm'=>true],
			'close'		=>['title'=>'关闭',		'page_title'=>'关闭回复',	'direct'=>true,	'confirm'=>true],
			'sink'		=>['title'=>'沉贴',		'page_title'=>'沉贴',	'direct'=>true,	'confirm'=>true],
			'stick'		=>['title'=>'置顶',		'page_title'=>'置顶',	'direct'=>true,	'confirm'=>true,	'capability'=>'manage_options'],
			'unstick'	=>['title'=>'取消置顶',	'page_title'=>'取消置顶',	'direct'=>true,	'confirm'=>true]
		];

		if(wpjam_topic_get_setting('account')){
			unset($actions['add']);
		}

		if(wpjam_topic_get_setting('comments', 1)){
			unset($actions['view']);
		}else{
			unset($actions['comment']);
			unset($actions['open']);
			unset($actions['close']);
		}

		if(!wpjam_topic_get_setting('audit')){
			unset($actions['approve']);
			unset($actions['unapprove']);
		}

		if(!wpjam_topic_get_setting('topic_type')){
			unset($actions['set_type']);
		}

		if(!function_exists('wpjam_stick_post')){
			unset($actions['stick']);
			unset($actions['unstick']);
		}

		return $actions;
	}

	public static function get_fields($action_key='', $post_id=0){
		$group_name	= wpjam_topic_get_setting('group_name', '分组');
		$groups		= array_column(self::get_groups(), 'name', 'id');

		if($action_key == ''){
			return ['topic'	=> ['title'=>'帖子',		'type'=>'view',	'show_admin_column'=>true]];
		}elseif(in_array($action_key, ['add', 'edit'])){
			$content	= $action_key == 'edit' ? wp_strip_all_tags(self::get_content($post_id)) : '';	
			
			$fields 	= [
				'group_id'	=> ['title'=>$group_name,	'type'=>'select',	'options'=>$groups],
				'title'		=> ['title'=>'标题',			'type'=>'text'],
				'content'	=> ['title'=>'内容',			'type'=>'textarea',	'rows'=>6,	'value'=>$content],
				'images'	=> ['title'=>'相关图片',		'type'=>'mu-img',	'item_type'=>'url'],
			];

			if(wpjam_topic_get_setting('topic_type') && $action_key == 'edit'){
				unset($fields['group_id']);
			}

			return $fields;
		}elseif($action_key == 'set_type'){
			$topic_type	= self::get($post_id)['topic_type'];
			$types		= $sub_types = $group_show_if = [];

			foreach(wpjam_topic_get_type_setting() as $type_setting){
				$columns	= $type_setting['columns'] ?? [];

				$types[$type_setting['slug']]	= $type_setting['name'];

				foreach($columns as $column){
					$sub_types[$column['slug']]	= ['title'=>$column['name'], 'show_if'=>['key'=>'type',	'value'=>$type_setting['slug']]];
				}

				if(!empty($type_setting['group'])){
					$group_show_if[]	= $type_setting['slug'];
				}
			}

			return [
				'type_set'	=> ['title'=>'类型',	'type'=>'fieldset',	'group'=>true,	'fields'=>[
					'type'		=> ['type'=>'select',	'options'=>$types,		'value'=>$topic_type['slug']],
					'sub_type'	=> ['type'=>'select',	'options'=>$sub_types,	'value'=>$topic_type['sub_slug'],	'required'],
				]],
				'group_id'	=> ['title'=>$group_name,	'type'=>'select',	'options'=>$groups,	'show_if'=>['key'=>'type', 'compare'=>'IN', 'value'=>$group_show_if]],
			];
		}elseif($action_key == 'view'){
			return self::get_view_fields($post_id);
		}elseif($action_key == 'comment'){
			return self::get_comment_fields($post_id);
		}

		return [];
	}

	public static function _query_data($args){
		$_query	= new WP_Query($args);

		return ['items'=>$_query->posts, 'total'=>$_query->found_posts];
	}

	private static function _get_author_avatar($item, $filter=true, $size=60){
		if(wpjam_topic_get_setting('account')){
			$avatar	= get_avatar(WPJAM_Account::get_instance($item['account']['id']), $size);

			if($filter){
				$filters	= ['account_id'=>$item['account']['id']];
			}
		}else{
			$avatar	= get_avatar($item['author']['id'], $size);
			
			if($filter){
				$filters	= ['user_id'=>$item['author']['id']];
			}
		}

		if($filter){
			$avatar	= wpjam_get_list_table_filter_link($filters, $avatar);
		}

		return '<div class="topic-avatar">'.$avatar.'</div>';
	}

	private static function _get_author_nickname($item, $filter=true){
		if(wpjam_topic_get_setting('account')){
			$nickname	= $item['account']['nickname'];

			if($filter){
				$filters	= ['account_id'=>$item['account']['id']];
			}
		}else{
			$nickname	= $item['author']['name'];

			if($filter){
				$filters	= ['user_id'=>$item['author']['id']];
			}
		}

		if($filter){
			$nickname	= wpjam_get_list_table_filter_link($filters, $nickname);
		}

		return '<span class="topic-user">'.$nickname.'</span>';
	}

	private static function _render_topic($item){
		$object	= WPJAM_Topic::get_instance($item['id']);
		$group	= $item['group'] ? $item['group'][0] : [];

		$topic	= self::get_author_avatar($item);
		$topic	.= '<p class="topic-title">';

		if(is_sticky($item['id'])){
			$topic	.= '<span class="dashicons dashicons-sticky is-sticky"></span>';
		}

		if(wpjam_topic_get_setting('comments', 1)){
			$topic	.= wpjam_get_list_table_row_action('comment', ['id'=>$item['id'], 'title'=>$item['title']]);
		}else{
			$topic	.= wpjam_get_list_table_row_action('view', ['id'=>$item['id'], 'title'=>$item['title']]);
		}

		if(wpjam_topic_get_setting('comments', 1) && $item['comment_count']){
			$topic	.= '<span class="topic-comments">（'.$item['comment_count'].'）</span>';
		}

		$topic	.= '</p>';

		$topic	.= '<p class="topic-meta">';

		$topic	.= self::_get_author_nickname($item);

		if(wpjam_topic_get_setting('topic_type') && $item['topic_type']){
			$topic_type	= wpjam_get_list_table_filter_link(['topic_type'=>$item['topic_type']['slug']], $item['topic_type']['name']);

			if(isset($item['topic_type']['sub_slug'])){
				$topic_type	.= ' - '.wpjam_get_list_table_filter_link(['topic_type'=>$item['topic_type']['sub_slug']], $item['topic_type']['sub_name']);
			}

			$topic	.= '<span class="topic-type">'.$topic_type.'</span>';
		}

		$topic	.= $group ? '<span class="topic-group">'.wpjam_get_list_table_filter_link(['group'=>$group['slug']], $group['name']).'</span>' : '';
		$topic	.= '<span class="topic-time">'.$item['time'].'</span>';

		if(wpjam_topic_get_setting('comments', 1) && $item['comment_count']){
			$last_comment_user	= $object->last_commenter;
			$last_comment_user	= $last_comment_user ? get_userdata($last_comment_user) : null;

			if($last_comment_user){
				$topic	.= '<span class="topic-last_reply">最后回复来自 '.wpjam_get_list_table_filter_link(['user_id'=>$last_comment_user->ID], $last_comment_user->display_name).'</span>';	
			}
		}

		$action_wrap	= '<span class="topic-%1$s">%2$s</span>';
		
		$topic	.= wpjam_get_list_table_row_action('edit', ['id'=>$item['id'], 'wrap'=>$action_wrap]);

		if(wpjam_topic_get_setting('topic_type')){
			$topic	.= wpjam_get_list_table_row_action('set_type', ['id'=>$item['id'], 'wrap'=>$action_wrap]);

			if(did_action('weapp_loaded')){
				$topic	.= wpjam_get_list_table_row_action('generate_weapp_qrcode', ['id'=>$item['id'], 'wrap'=>$action_wrap]);
			}
		}

		if(wpjam_topic_get_setting('audit')){
			if($item['status'] == 'publish'){
				$topic	.= wpjam_get_list_table_row_action('unapprove', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';
			}else{
				$topic	.= wpjam_get_list_table_row_action('approve', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';
			}
		}

		if(wpjam_topic_get_setting('comments', 1)){
			if($item['comment_status'] == 'closed'){
				$topic	.= wpjam_get_list_table_row_action('open', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';
			}else{
				$topic	.= wpjam_get_list_table_row_action('close', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';
			}
		}

		if(is_sticky($item['id'])){
			$topic	.= wpjam_get_list_table_row_action('unstick', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';
		}else{
			$topic	.= wpjam_get_list_table_row_action('stick', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';
		}

		$topic	.= wpjam_get_list_table_row_action('sink', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';
		$topic	.= wpjam_get_list_table_row_action('delete', ['id'=>$item['id'], 'wrap'=>$action_wrap]).'</span>';

		$topic	.= '</p>';

		return $topic;
	}

	private static function _get_view_fields($post_id){
		wpjam_update_post_views($post_id);

		$fields		= [];
		$topic		= self::get($post_id);

		$avatar		= self::get_author_avatar($topic, false, 80).'</div>';

		$title		= $topic['group'] ? '<span class="topic-group">'.$topic['group'][0]['name'].'</span> | ' : '';
		$title		.= $topic['title'];
		$title		= '<h2>'.$title.'</h2>';

		$meta		=  self::get_author_nickname($topic, false);
		$meta		.= '<span class="topic-time">'.$topic['time'].'</span>';
		$meta		.= '<span class="topic-views">'.$topic['views'].' 次查看</span>';
		$meta		= '<div class="topic-meta">'.$meta.'</div>';

		$content	= make_clickable(self::get_content($post_id));

		// if($topic['modified'] && $topic['timestamp_modified'] != $topic['timestamp']){
		// 	$content	.= '<p>最后编辑于'.$topic['modified'].'</p>';
		// }

		if($topic['images'] && ($images = maybe_unserialize($topic['images']))){
			$content	.= '<p>'.implode("\n", array_map(function($image){ 
				$image = is_array($image) ? $image['url'] : $image; 
				return '<img src="'.$image.'" />'; 
			}, $images)).'</p>';
		}

		$content	= '<div class="topic-content">'.$content.'</div>';

		if(wpjam_topic_get_setting('topic_type')){
			$topic_fields	= '';

			foreach(self::get_fields($post_id) as $field){
				$value	= is_array($field['value']) ? implode("&emsp;", $field['value']) : $field['value'];
				$value	= wp_strip_all_tags($value);
				
				$topic_fields	.= '<p><span class="name">'.wp_strip_all_tags($field['name']).'</span><br /><span class="value">'.$value.'</span></p>';
			}

			$content	.= $topic_fields ? '<div class="topic-fields">'.$topic_fields.'</div>' : '';
		}

		$fields['topic']	= ['title'=>'',	'type'=>'view',	'value'=>$avatar.$title.$meta.$content];

		return $fields;
	}

	private static function _get_comment_fields($post_id){
		$fields	= self::_get_view_fields($post_id);
		$topic	= WPJAM_Topic::get($post_id);

		if($comments = WPJAM_Comment::get_comments(['post_id'=>$post_id])){
			$topic_comments	= '';

			foreach($comments as $comment){
				if(!$comment['approved'] && $comment['user_id'] != get_current_user_id()){
					continue;
				}

				$alternate	= empty($alternate) ? 'alternate' : '';

				$comment_avatar		= '<div class="comment-avatar">'.get_avatar($comment['user_id'], 50).'</div>';

				$comment_content	= make_clickable(wpautop(convert_smilies($comment['content'])));

				if($comment['parent']){
					$comment_parent		= $comment['reply_to'] ? '<a class="comment_parent" data-parent="'.$comment['parent'].'" href="javascript:;">@'.$comment['reply_to'].'</a> ' : '';
					$comment_content	= $comment_parent.$comment_content;
				}

				$comment_content	= '<div class="comment-content">'.$comment_content.'</div>';

				$comment_meta	= '<div class="comment-meta">';
				$comment_meta	.= '<span class="comment-author">'. $comment['author']['nickname'].'</span>';
				$comment_meta	.= '<span class="comment-time">'.$comment['time'].'</span>';
				$comment_meta	.= wpjam_get_page_button('delete_comment', ['data'=>['post_id'=>$post_id, 'comment_id'=>$comment['id']], 'wrap'=>'<span class="comment-delete">%2$s</span>']);
				$comment_meta	.= '</div>';

				$comment_reply	= '<a class="reply dashicons dashicons-undo" title="回复给'.$comment['author']['nickname'].'" data-user="'.$comment['author']['nickname'].'" data-comment_id="'.$comment['id'].'" href="javascript:;"></a>';

				$topic_comments	.= '<li id="comment_'. $comment['id'].'" class="'.$alternate.'">'.$comment_avatar.$comment_reply.$comment_meta.$comment_content.'</li>';
			}

			$topic_comments	= '<ul>'.$topic_comments.'</ul>';
			$topic_comments	= '<h3><span id="comment_count" data-count="'.$topic['comment_count'].'">'.$topic['comment_count'].'</span>条回复'.'</h3>'.$topic_comments;
			$topic_comments	= '<div id="comments">'.$topic_comments.'</div>';

			$fields['comments']	= ['title'=>'',	'type'=>'view',	'value'=>$topic_comments];
		}

		if($topic['comment_status'] == 'closed'){
			$fields['comment_set']	= ['title'=>'',	'type'=>'view',		'value'=>'<h3>帖子已关闭</h3>'];
		}else{
			$fields['comment_set']	= ['title'=>'',	'type'=>'fieldset',	'fields'=>[
				'title'		=> ['title'=>'',	'type'=>'view',		'value'=>'<h3 id="comment_title">我要回复</h3><p id="comment_subtitle" class="hidden;"></p>'],
				'comment'	=> ['title'=>'',	'type'=>'textarea',	'rows'=>6,	'description'=>' '],
				// 'images'		=> ['title'=>'',	'type'=>'mu-img',	'description'=>''],
				'parent'	=> ['title'=>'',	'type'=>'hidden',	'value'=>''],
			]];
		}

		return $fields;
	}

	public static function ajax_delete_comment(){
		$post_id	= wpjam_get_data_parameter('post_id');
		$comment_id	= wpjam_get_data_parameter('comment_id');
		$result		= WPJAM_Comment::delete($comment_id, $force_delete=false);

		if(is_wp_error($result)){
			return $result;
		}

		return ['comment_id'=>$comment_id];
	}
}

add_action('admin_head', function(){
	?>
	<style type="text/css">
	.tablenav.top{display: none;}
	
	table.wpjam-topics thead, table.wpjam-topics tfoot{display: none;}

	table.wpjam-topics tr{border-bottom: 1px solid #e5e5e5;}
	table.wpjam-topics tr:last-child{border-bottom:none;}

	.dashicons.is-sticky{color:#0073aa; width:16px; height:16px; font-size:16px; line-height:18px;}

	.topic-avatar{float: left; margin: 2px 10px 2px 0;}
	.topic-avatar a, .topic-avatar a img{display: block;}

	.widefat td p.topic-title{ margin: 4px 0 16px;}
	.widefat td p.topic-meta{ margin: 16px 0 4px;}
	
	.topic-meta span{ margin-right: 8px; padding-right: 8px; border-right: solid 1px #999; }
	.topic-meta span:last-child{ border-right: none; }
	.topic-delete a{color: #a00;}

	#TB_ajaxContent .topic-avatar{float: right; margin: 0 0 10px 10px;}
	#TB_ajaxContent h2, #TB_ajaxContent h3, #TB_ajaxContent div.topic-meta{margin: 4px 0 20px 0;}

	#TB_ajaxContent div#comments li { padding:10px; margin:10px 0; background: #fff;}
	#TB_ajaxContent div#comments li.alternate{background: #f9f9f9;}

	#TB_ajaxContent div#comments li .reply{float:right; display: none;}
	#TB_ajaxContent div#comments li:hover .reply{display: block;}

	#TB_ajaxContent a.unreply{cursor: pointer;}

	#TB_ajaxContent div.comment-meta{margin: 2px 0 6px 0;}
	#TB_ajaxContent div.comment-meta span{ margin-right: 8px; padding-right: 8px; border-right: solid 1px #999; }
	#TB_ajaxContent div.comment-meta span:last-child{ border-right: none; }
	#TB_ajaxContent div.comment-meta span a, #TB_ajaxContent .comment_parent{ text-decoration: none; }
	#TB_ajaxContent span.comment-delete a{color: #a00;}
	#TB_ajaxContent div.comment-content{margin-left: 66px;}
	#TB_ajaxContent div.comment-meta .dashicons{width:18px; height:18px; font-size:14px; line-height: 18px;}
	#TB_ajaxContent div.comment-avatar { float:left; margin:2px 12px 2px 2px; }

	#TB_ajaxContent #comment_subtitle{height:20px; line-height:20px; padding-bottom: 0;}

	</style>
	<script type="text/javascript">
	jQuery(function($){
		$('body').on('list_table_action_success', function(event, response){
			if(response.list_action == 'comment'){
				if($('#TB_ajaxContent #comment').length == 0){
					$('#TB_ajaxContent p.submit').remove();
				}

				if(response.list_action_type == 'submit'){
					$('#comment').val('').focus();
					$('div#comments li:last').animate({opacity: 0.1}, 500).animate({opacity: 1}, 500);
				}
			}
		});

		$('body').on('click', '.reply', function(){
			$('input[name=parent]').val($(this).data('comment_id'));
			$('#comment_subtitle').html('@'+$(this).data('user')+' <a class="unreply dashicons dashicons-no-alt"></a>').fadeIn(500);
			$('textarea#comment').focus();
		});

		$('body').on('click', '.unreply', function(){
			$('input[name=parent]').val(0);
			$('#comment_subtitle').fadeOut(300);
			$('textarea#comment').focus();
		});

		$('body').on('click', 'a.comment_parent', function(){
			var comment_parent = $('#comment_'+$(this).data('parent'));
			var top = comment_parent.offset().top - $('#list_table_action_form').offset().top;
			
			$('#TB_ajaxContent').animate({scrollTop:top}, 500, function(){
				comment_parent.animate({opacity: 0.1}, 500).animate({opacity: 1}, 500);
			});
		});

		$('body').on('page_action_success', function(e, response){
			if(response.page_action == 'delete_comment'){
				let count = $('#comment_count').data('count');

				if(count == 1){
					$('#comments').animate({opacity: 0.1}, 500, function(){ $(this).remove() });
				}else{
					$('#comment_count').data('count', count-1);
					$('#comment_count').html(count-1);

					$('#comment_'+response.comment_id).animate({opacity: 0.1}, 500, function(){ $(this).remove();});; 
				}
			}
		});
	});

	</script>
	<?php
});

if(wpjam_topic_get_setting('topic_type') && did_action('weapp_loaded')){
	wpjam_register_list_table_action('generate_weapp_qrcode', [
		'title'			=> '生成二维码',
		'post_status'	=> ['publish'],
		'response'		=> 'append',
		'fields'		=> ['WEAPP_Path', 'get_post_fields'],
		'callback'		=> ['WEAPP_Path', 'generate_post_qrcode']
	]);
}

wpjam_register_list_table('wpjam-topics', [
	'plural'		=> 'wpjam-topics',
	'singular'		=> 'wpjam-topic',
	'model'			=> 'WPJAM_Topics_Admin',
	'capability'	=> 'manage_topics',
	'search'		=> true,
	'per_page'		=> 10
]);

wpjam_register_page_action('delete_comment', [
	'button_text'	=> '删除',
	'capability'	=> 'manage_options',
	'class'			=> '',
	'direct'		=> true,
	'confirm'		=> true,
	'callback'		=> ['WPJAM_Topics_Admin', 'ajax_delete_comment']
]);