<?php
class WPJAM_Edit_Builtin{
	public static function load($screen_base, $current_screen){
		if($screen_base == 'upload'){
			add_action('restrict_manage_posts',	[self::class, 'on_restrict_manage_posts'], 1);
			add_filter('request',				[self::class, 'filter_request']);
		}elseif($screen_base == 'edit'){
			add_action('restrict_manage_posts',	[self::class, 'on_restrict_manage_posts'], 1);
			add_filter('request',				[self::class, 'filter_request']);

			$ptype	= $current_screen->post_type;
			$pt_obj	= $ptype ? get_post_type_object($ptype) : null;

			if(!$pt_obj){
				return;
			}

			add_filter('post_column_taxonomy_links',	[self::class, 'filter_taxonomy_links'], 10, 3);

			if(is_object_in_taxonomy($ptype, 'category')){
				add_filter('disable_categories_dropdown', '__return_true');
			}

			$style	= ['.fixed .column-views{width:7%;}', '.fixed .column-date{width:8%;}'];

			if($ptype == 'page'){
				wpjam_register_posts_column('template', '模板', 'get_page_template_slug');

				$style[]	= '.fixed .column-template{width:15%;}';
			}elseif($ptype == 'product'){
				if(wpjam_basic_get_setting('post_list_set_thumbnail', 1) && defined('WC_PLUGIN_FILE')){
					wpjam_unregister_posts_column('thumb');
				}
			}

			$set_fields	= [];

			$set_fields['post_title']	= ['title'=>'标题',	'type'=>'text',	'required'];

			if(post_type_supports($ptype, 'excerpt')){
				$set_fields['post_excerpt']	= ['title'=>'摘要',	'type'=>'textarea',	'class'=>'',	'rows'=>3];
			}

			if(post_type_supports($ptype, 'thumbnail')){
				$set_fields['_thumbnail_id']	= ['title'=>'头图', 'type'=>'img', 'size'=>'600x0'];
			}

			add_filter('wpjam_single_row', [self::class, 'filter_post_single_row'], 10, 2);

			if(!WPJAM_List_Table_Action::get('set')){
				wpjam_register_list_table_action('set', [
					'title'			=> '设置',
					'page_title'	=> '设置'.$pt_obj->label,
					'fields'		=> $set_fields,
					'row_action'	=> false
				]);
			}

			wpjam_register_list_table_action('set_thumbnail', [
				'title'			=> '设置',
				'page_title'	=> '设置'.$pt_obj->label,
				'fields'		=> $set_fields,
				'row_action'	=> false
			]);	// 兼容代码

			if(!empty($pt_obj->viewable)
				|| (wpjam_basic_get_setting('post_list_update_views', 1) 
					&& is_post_type_viewable($ptype)
				)
			){
				wpjam_register_list_table_action('update_views', [
					'title'			=> '修改',
					'page_title'	=> '修改浏览数',
					'capability'	=> $pt_obj->cap->edit_others_posts,
					'fields'		=> ['views'=>['title'=>'浏览数', 'type'=>'number']],
					'row_action'	=> false,
					'width'			=> 500
				]);

				wpjam_register_posts_column('views', [
					'title'				=> '浏览',
					'sortable_column'	=> 'views',
					'column_callback'	=> [self::class, 'views_column']
				]);
			}

			if(wpjam_basic_get_setting('upload_external_images')){
				wpjam_register_list_table_action('upload_external_images', [
					'title'			=> '上传外部图片',
					'page_title'	=> '上传外部图片',
					'direct'		=> true,
					'confirm'		=> true,
					'bulk'			=> 2,
					'order'			=> 9,
					'callback'		=> [self::class, 'upload_external_images']
				]);
			}

			$width_columns	= [];

			if(post_type_supports($ptype, 'author')){
				$width_columns[]	= '.fixed .column-author';
			}

			foreach(get_object_taxonomies($ptype, 'objects') as $taxonomy => $tax_obj){
				if($tax_obj->show_admin_column){
					$width_columns[]	= '.fixed .column-'.self::get_taxonomy_column_key($taxonomy);
				}
			}

			if($count = count($width_columns)){
				$widths		= ['14%',	'12%',	'10%',	'8%',	'7%'];
				$style[]	= implode(',', $width_columns).'{width:'.($widths[$count-1] ?? '6%').'}';
			}

			wp_add_inline_style('list-tables', "\n".implode("\n", $style)."\n");
		}elseif($screen_base == 'edit-tags'){
			add_filter('term_updated_messages',			['WPJAM_Term_Builtin', 'filter_term_updated_messages']);
			add_filter('taxonomy_parent_dropdown_args',	['WPJAM_Term_Builtin', 'filter_parent_dropdown_args'], 10, 3);

			add_filter('wpjam_single_row',	[self::class, 'filter_term_single_row'], 10, 2);

			$taxonomy	= $current_screen->taxonomy;
			$tax_obj	= $taxonomy ? get_taxonomy($taxonomy) : null;

			if(!$tax_obj){
				return;
			}

			$supports	= $tax_obj->supports;

			if($tax_obj->levels == 1){
				$supports	= array_diff($supports, ['parent']);
			}

			$style		= [
				'.fixed th.column-slug{ width:16%; }',
				'.fixed th.column-description{width:22%;}',
				'.form-field.term-parent-wrap p{display: none;}',
				'.form-field span.description{color:#666;}'
			];

			foreach(['slug', 'description', 'parent'] as $key){ 
				if(!in_array($key, $supports)){
					$style[]	= '.form-field.term-'.$key.'-wrap{display: none;}'."\n";
				}
			}

			wp_add_inline_style('list-tables', "\n".implode("\n", $style));
		}
		
		if($screen_base == 'edit' && self::is_wc_shop_post_type($ptype)){
			$ajax	= false;
		}else{
			$scripts	= '';

			if(wpjam_basic_get_setting('post_list_ajax', 1)){
				$ajax		= true;
				$scripts	= <<<'EOT'
jQuery(function($){
	$(window).load(function(){
		$.wpjam_delegate_events('#the-list', '.editinline');
		$.wpjam_delegate_events('#doaction');
	});
});
EOT;
			}else{
				$ajax	= false;
			}

			$scripts	.= <<<'EOT'
jQuery(function($){
	let observer = new MutationObserver(function(mutations){
		if($('#the-list .inline-editor').length > 0){
			let tr_id	= $('#the-list .inline-editor').attr('id');

			if(tr_id == 'bulk-edit'){
				$('#the-list').trigger('bulk_edit');
			}else{
				let id	= tr_id.split('-')[1];

				if(id > 0){
					$('#the-list').trigger('quick_edit', id);
				}
			}
		}
	});

	observer.observe(document.querySelector('body'), {childList: true, subtree: true});
});
EOT;
			wp_add_inline_script('jquery', $scripts);
		}

		$current_screen->add_option('wpjam_list_table', ['ajax'=>$ajax, 'form_id'=>'posts-filter']);
	}

	public static function is_wc_shop_post_type($ptype){
		return defined('WC_PLUGIN_FILE') && in_array($ptype, ['shop_order', 'shop_coupon', 'shop_webhook']);
	}

	public static function views_column($post_id){
		$views	= wpjam_get_post_views($post_id, false) ?: 0;

		return wpjam_get_list_table_row_action('update_views', ['id'=>$post_id, 'title'=>$views, 'fallback'=>true]);
	}

	public static function get_taxonomy_column_key($taxonomy){
		if('category' === $taxonomy) {
			return 'categories';
		}elseif('post_tag' === $taxonomy){
			return 'tags';
		}else{
			return 'taxonomy-'.$taxonomy;
		}
	}

	public static function filter_post_single_row($single_row, $post_id){
		$ptype	= get_post_type($post_id);

		if(wpjam_basic_get_setting('post_list_set_thumbnail', 1) && post_type_supports($ptype, 'thumbnail')){		
			$thumbnail	= get_the_post_thumbnail($post_id, [50,50]) ?: '<span class="no-thumbnail">暂无图片</span>';
			$thumbnail	= wpjam_get_list_table_row_action('set', ['id'=>$post_id, 'class'=>'wpjam-thumbnail-wrap', 'title'=>$thumbnail, 'fallback'=>true]);
			$single_row	= str_replace('<a class="row-title" ', $thumbnail.'<a class="row-title" ', $single_row);
		}

		if(wpjam_basic_get_setting('post_list_ajax', 1)){
			$quick_edit	= '<a title="快速编辑" href="javascript:;" class="editinline row-action"><span class="dashicons dashicons-edit"></span></a>';

			if(post_type_supports($ptype, 'author')){
				$single_row = preg_replace('/(<td class=\'author column-author\' .*?>)(.*?)(<\/td>)/is', '$1$2 '.$quick_edit.'$3', $single_row);
			}

			foreach(get_object_taxonomies($ptype, 'objects') as $taxonomy => $tax_obj){
				if('post_format' != $taxonomy && $tax_obj->show_admin_column){
					$column_key	= self::get_taxonomy_column_key($taxonomy);
					$single_row	= preg_replace('/(<td class=\''.$column_key.' column-'.$column_key.'\' .*?>)(.*?)(<\/td>)/is', '$1$2 '.$quick_edit.'$3', $single_row);
				}
			}
		}

		return $single_row;
	}

	public static function filter_term_single_row($single_row, $term_id){
		$taxonomy	= get_term($term_id)->taxonomy;

		if(WPJAM_List_Table_Action::get('set_thumbnail')){
			$thumb_url	= wpjam_get_term_thumbnail_url($term_id, [100, 100]);
			$thumbnail	= $thumb_url ? '<img class="wp-term-image" src="'.$thumb_url.'"'.image_hwstring(50,50).' />' : '<span class="no-thumbnail">暂无图片</span>';
			$thumbnail	= wpjam_get_list_table_row_action('set_thumbnail', ['id'=>$term_id, 'class'=>'wpjam-thumbnail-wrap', 'title'=>$thumbnail, 'fallback'=>true]);
			$single_row	= str_replace('<a class="row-title" ', $thumbnail.'<a class="row-title" ', $single_row);
		}

		$permastruct	= wpjam_get_permastruct($taxonomy);

		if(empty($permastruct) || strpos($permastruct, '/%'.$taxonomy.'_id%')){
			$single_row	= self::term_edit_link_replace($single_row, $term_id);
		}

		return $single_row;
	}

	public static function term_edit_link_replace($link, $term_id){
		$term		= get_term($term_id);
		$taxonomy	= $term->taxonomy;

		$query_var	= get_taxonomy($taxonomy)->query_var;
		$query_key	= wpjam_get_taxonomy_query_key($taxonomy);
		$query_str	= $query_var ? $query_var.'='.$term->slug : 'taxonomy='.$taxonomy.'&#038;term='.$term->slug;

		return str_replace($query_str, $query_key.'='.$term->term_id, $link);
	}

	public static function filter_taxonomy_links($term_links, $taxonomy, $terms){
		$permastruct	= wpjam_get_permastruct($taxonomy);

		if($taxonomy == 'post_format'){
			foreach($term_links as &$term_link){
				$term_link	= str_replace('post-format-', '', $term_link);
			}
		}elseif(empty($permastruct) || strpos($permastruct, '/%'.$taxonomy.'_id%')){
			foreach($terms as $i => $term){
				$term_links[$i]	= self::term_edit_link_replace($term_links[$i], $term);
			}
		}

		return $term_links;
	}

	public static function filter_request($query_vars){
		$tax_query	= [];

		foreach(get_object_taxonomies(get_current_screen()->post_type, 'objects') as $taxonomy => $tax_obj){
			if(!$tax_obj->show_ui){
				continue;
			}

			$tax	= $taxonomy == 'post_tag' ? 'tag' : $taxonomy;

			if($tax != 'category'){
				if($tax_id = (int)wpjam_get_data_parameter($tax.'_id')){
					$query_vars[$tax.'_id']	= $tax_id;
				}
			}

			$tax_arg		= ['taxonomy'=>$taxonomy,	'field'=>'term_id'];

			$tax__and		= wpjam_get_data_parameter($tax.'__and',	['sanitize_callback'=>'wp_parse_id_list']);
			$tax__in		= wpjam_get_data_parameter($tax.'__in',		['sanitize_callback'=>'wp_parse_id_list']);
			$tax__not_in	= wpjam_get_data_parameter($tax.'__not_in',	['sanitize_callback'=>'wp_parse_id_list']);

			if($tax__and){
				if(count($tax__and) == 1){
					$tax__in	= is_null($tax__in) ? [] : $tax__in;
					$tax__in[]	= reset($tax__and);
				}else{
					$tax_query[]	= array_merge($tax_arg, ['terms'=>$tax__and,	'operator'=>'AND']);	// 'include_children'	=> false,
				}
			}

			if($tax__in){
				$tax_query[]	= array_merge($tax_arg, ['terms'=>$tax__in]);
			}

			if($tax__not_in){
				$tax_query[]	= array_merge($tax_arg, ['terms'=>$tax__not_in,	'operator'=>'NOT IN']);
			}
		}

		if($tax_query){
			$tax_query['relation']		= wpjam_get_data_parameter('tax_query_relation',	['default'=>'and']);
			$query_vars['tax_query']	= $tax_query;
		}

		return $query_vars;
	}

	public static function upload_external_images($post_id){
		$content	= get_post($post_id)->post_content;
		$bulk		= (int)wpjam_get_parameter('bulk', ['method'=>'POST']);

		if(preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $content, $matches)){
			$img_urls	= array_unique($matches[1]);

			if($replace	= wpjam_fetch_external_images($img_urls, $post_id)){
				$content	= str_replace($img_urls, $replace, $content);
				return wp_update_post(['post_content'=>$content, 'ID'=>$post_id], true);
			}else{
				return $bulk == 2 ? true : new WP_Error('no_external_images', '文章中无外部图片');
			}
		}

		return $bulk == 2 ? true : new WP_Error('no_images', '文章中无图片');
	}

	public static function on_restrict_manage_posts($ptype){
		foreach(get_object_taxonomies($ptype, 'objects') as $taxonomy => $tax_obj){
			$filterable	= $tax_obj->filterable ?? ($taxonomy == 'category' ? true : false);

			if(empty($filterable) || empty($tax_obj->show_admin_column)){
				continue;
			}

			$query_key	= wpjam_get_taxonomy_query_key($taxonomy);
			$selected	= wpjam_get_data_parameter($query_key);

			if(is_null($selected)){
				if($query_var = $tax_obj->query_var){
					$term_slug	= wpjam_get_data_parameter($query_var);
				}elseif(wpjam_get_data_parameter('taxonomy') == $taxonomy){
					$term_slug	= wpjam_get_data_parameter('term');
				}else{
					$term_slug	= '';
				}

				$term 		= $term_slug ? get_term_by('slug', $term_slug, $taxonomy) : null;
				$selected	= $term ? $term->term_id : '';
			}

			if($tax_obj->hierarchical){
				wp_dropdown_categories([
					'taxonomy'			=> $taxonomy,
					'show_option_all'	=> $tax_obj->labels->all_items,
					'show_option_none'	=> '没有设置',
					'name'				=> $query_key,
					'selected'			=> (int)$selected,
					'hierarchical'		=> true
				]);
			}else{
				echo wpjam_get_field_html([
					'key'			=> $query_key,
					'value'			=> $selected,
					'type'			=> 'text',
					'data_type'		=> 'taxonomy',
					'taxonomy'		=> $taxonomy,
					'placeholder'	=> '请输入'.$tax_obj->label,
					'title'			=> '',
					'class'			=> ''
				]);
			}
		}

		if(wpjam_basic_get_setting('post_list_author_filter', 1) && post_type_supports($ptype, 'author')){
			wp_dropdown_users(wpjam_get_authors([
				'name'						=> 'author',
				'orderby'					=> 'post_count',
				'order'						=> 'DESC',
				'hide_if_only_one_author'	=> true,
				'show_option_all'			=> $ptype == 'attachment' ? '所有上传者' : '所有作者',
				'selected'					=> (int)wpjam_get_data_parameter('author')
			], 'args'));
		}

		if(wpjam_basic_get_setting('post_list_sort_selector', 1) && !self::is_wc_shop_post_type($ptype)){
			$options		= [''=>'排序','ID'=>'ID'];
			$wp_list_table	= _get_list_table('WP_Posts_List_Table', ['screen'=>get_current_screen()->id]);

			list($columns, $hidden, $sortable_columns)	= $wp_list_table->get_column_info();

			foreach($sortable_columns as $sortable_column => $data){
				if(isset($columns[$sortable_column])){
					$options[$data[0]]	= $columns[$sortable_column];
				}
			}

			if($ptype != 'attachment'){
				$options['modified']	= '修改时间';
			}

			$orderby	= wpjam_get_data_parameter('orderby', ['sanitize_callback'=>'sanitize_key']);
			$order		= wpjam_get_data_parameter('order', ['sanitize_callback'=>'sanitize_key', 'default'=>'DESC']);

			echo wpjam_get_field_html(['key'=>'orderby',	'type'=>'select',	'value'=>$orderby,	'options'=>$options]);
			echo wpjam_get_field_html(['key'=>'order',		'type'=>'select',	'value'	=>$order,	'options'=>['desc'=>'降序','asc'=>'升序']]);
		}
	}
}

class WPJAM_Post_Builtin{
	public static function load(){
		add_filter('post_updated_messages',		[self::class, 'filter_post_updated_messages']);
		add_filter('admin_post_thumbnail_html',	[self::class, 'filter_admin_thumbnail_html'], 10, 2);
		add_filter('redirect_post_location',	[self::class, 'filter_redirect_location']);

		add_filter('post_edit_category_parent_dropdown_args',	[self::class, 'filter_edit_category_parent_dropdown_args']);

		$style	= [];
		$ptype	= get_current_screen()->post_type;

		foreach(get_object_taxonomies($ptype, 'objects') as $taxonomy => $tax_obj){
			if(isset($tax_obj->levels) && $tax_obj->levels == 1){
				$style[]	= '#new'.$taxonomy.'_parent{display:none;}';
			}
		}

		if(wpjam_basic_get_setting('disable_trackbacks')){
			$style[]	= 'label[for="ping_status"]{display:none !important;}';
		}

		if($style){
			wp_add_inline_style('list-tables', "\n".implode("\n", $style));
		}

		if(!get_current_screen()->is_block_editor){
			if(wpjam_basic_get_setting('disable_autoembed')){
				$scripts	= <<<'EOT'
jQuery(function($){
	wp.domReady(function () {
		wp.blocks.unregisterBlockType('core/embed');
	});
});
EOT;
				wp_add_inline_script('jquery', $scripts);

				remove_action('edit_form_advanced',	[$GLOBALS['wp_embed'], 'maybe_run_ajax_cache']);
				remove_action('edit_page_form',		[$GLOBALS['wp_embed'], 'maybe_run_ajax_cache']);
			}
		}
	}

	public static function filter_post_updated_messages($messages){
		$ptype	= get_current_screen()->post_type;
		$pt_obj	= get_post_type_object($ptype);
		$key	= $pt_obj->hierarchical ? 'page' : 'post';

		if(isset($messages[$key])){
			$search		= $key == 'post' ? '文章':'页面';
			$replace	= $pt_obj->labels->name;

			foreach($messages[$key] as &$message){
				$message	= str_replace($search, $replace, $message);
			}
		}

		return $messages;
	}

	public static function filter_admin_thumbnail_html($content, $post_id){
		if($post_id){
			$ptype		= get_post_type($post_id);
			$size		= get_post_type_object($ptype)->thumbnail_size ?? '';
			$content	.= $size ? wpautop('尺寸：'.$size) : '';
		}

		return $content;
	}

	public static function filter_redirect_location($location){
		if(parse_url($location, PHP_URL_FRAGMENT)){
			return $location;
		}

		if($fragment = parse_url(wp_get_referer(), PHP_URL_FRAGMENT)){
			return $location.'#'.$fragment;
		}

		return $location;
	}

	public static function filter_edit_category_parent_dropdown_args($args){
		$levels	= get_taxonomy($args['taxonomy'])->levels ?? 0;

		if($levels == 1){
			$args['parent']	= -1;
		}elseif($levels > 1){
			$args['depth']	= $levels - 1;
		}

		return $args;
	}

	public static function filter_content_save_pre($content){
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return $content;
		}

		if(!preg_match_all('/<img.*?src=\\\\[\'"](.*?)\\\\[\'"].*?>/i', $content, $matches)){
			return $content;
		}

		$img_urls	= array_unique($matches[1]);
		
		if($replace	= wpjam_fetch_external_images($img_urls)){
			if(is_multisite()){
				setcookie('wp-saving-post', $_POST['post_ID'].'-saved', time()+DAY_IN_SECONDS, ADMIN_COOKIE_PATH, false, is_ssl());
			}

			$content	= str_replace($img_urls, $replace, $content);
		}

		return $content;
	}
}

class WPJAM_Term_Builtin{
	public static function filter_term_updated_messages($messages){
		$taxonomy	= get_current_screen()->taxonomy;

		if(!in_array($taxonomy, ['post_tag', 'category'])){
			$label	= get_taxonomy($taxonomy)->labels->name;
			
			foreach($messages['_item'] as $key => $message){
				$messages[$taxonomy][$key]	= str_replace(['项目', 'Item'], [$label, ucfirst($label)], $message);
			}
		}

		return $messages;
	}

	public static function filter_parent_dropdown_args($args, $taxonomy, $action_type){
		if(($levels = get_taxonomy($taxonomy)->levels) && $levels > 1){
			$args['depth']	= $levels - 1;

			if($action_type == 'edit'){
				$term_id	= $args['exclude_tree'];
				$term_level	= wpjam_get_term_level($term_id);

				if($children = get_term_children($term_id, $taxonomy)){
					$child_level	= 0;

					foreach($children as $child){
						$new_child_level	= wpjam_get_term_level($child);

						if($child_level	< $new_child_level){
							$child_level	= $new_child_level;
						}
					}
				}else{
					$child_level	= $term_level;
				}

				$redueced	= $child_level - $term_level;

				if($redueced < $args['depth']){
					$args['depth']	-= $redueced;
				}else{
					$args['parent']	= -1;
				}
			}
		}

		return $args;
	}
}

class WPJAM_Dashboad_Builtin{
	public static function on_dashboard_setup(){
		remove_meta_box('dashboard_primary', get_current_screen(), 'side');

		add_filter('dashboard_recent_posts_query_args', function($query_args){
			$query_args['post_type']	= 'any';
			$query_args['cache_it']		= true;
			// $query_args['posts_per_page']	= 10;

			return $query_args;
		});

		add_filter('dashboard_recent_drafts_query_args', function($query_args){
			$query_args['post_type']	= 'any';

			return $query_args;
		});

		add_action('pre_get_comments', function($query){
			$query->query_vars['type']	= 'comment';
		});
			
		if(is_multisite()){
			if(!is_user_member_of_blog()){
				remove_meta_box('dashboard_quick_press', get_current_screen(), 'side');
			}
		}
		
		$dashboard_widgets	= [];

		$dashboard_widgets['wpjam_update']	= [
			'title'		=> 'WordPress资讯及技巧',
			'context'	=> 'side',	// 位置，normal 左侧, side 右侧
			'callback'	=> [self::class, 'update_dashboard_widget']
		];

		if($dashboard_widgets	= apply_filters('wpjam_dashboard_widgets', $dashboard_widgets)){
			foreach ($dashboard_widgets as $widget_id => $meta_box){
				$title		= $meta_box['title'];
				$callback	= $meta_box['callback'] ?? wpjam_get_filter_name($widget_id, 'dashboard_widget_callback');
				$context	= $meta_box['context'] ?? 'normal';	// 位置，normal 左侧, side 右侧
				$args		= $meta_box['args'] ?? [];

				add_meta_box($widget_id, $title, $callback, get_current_screen(), $context, 'core', $args);
			}
		}
	}

	public static function update_dashboard_widget(){
		?>
		<style type="text/css">
			#dashboard_wpjam .inside{margin:0; padding:0;}
			a.jam-post {border-bottom:1px solid #eee; margin: 0 !important; padding:6px 0; display: block; text-decoration: none; }
			a.jam-post:last-child{border-bottom: 0;}
			a.jam-post p{display: table-row; }
			a.jam-post img{display: table-cell; width:40px; height: 40px; margin:4px 12px; }
			a.jam-post span{display: table-cell; height: 40px; vertical-align: middle;}
		</style>
		<div class="rss-widget">
		<?php

		$jam_posts = get_transient('dashboard_jam_posts');

		if($jam_posts === false){
			$response	= wpjam_remote_request('https://jam.wpweixin.com/api/post/list.json', ['timeout'=>1]);

			if(is_wp_error($response)){
				$jam_posts	= [];
			}else{
				$jam_posts	= $response['posts'];
			}

			set_transient('dashboard_jam_posts', $jam_posts, 12 * HOUR_IN_SECONDS );
		}

		if($jam_posts){
			$i = 0;
			foreach ($jam_posts as $jam_post){
				if($i == 5) break;
				echo '<a class="jam-post" target="_blank" href="http://blog.wpjam.com'.$jam_post['post_url'].'"><p>'.'<img src="'.str_replace('imageView2/1/w/200/h/200/', 'imageView2/1/w/100/h/100/', $jam_post['thumbnail']).'" /><span>'.$jam_post['title'].'</span></p></a>';
				$i++;
			}
		}	
		?>
		</div>

		<?php
	}
}

class WPJAM_Plugin_Builtin{
	public static function get_jam_plugin($plugin_file){
		if($jam_plugins	= self::get_jam_plugins()){
			$plugin_fields	= array_column($jam_plugins['fields'], 'index', 'title');
			$plugin_index	= $plugin_fields['插件'];

			foreach($jam_plugins['content'] as $plugin_data){
				if($plugin_data['i'.$plugin_index] == $plugin_file){
					$new_data	= [];

					foreach($plugin_fields as $name => $index){
						$new_data[$name]	= $plugin_data['i'.$index] ?? '';
					}

					return $new_data;
				}
			}
		}

		return null;
	}

	public static function get_jam_plugins(){
		$jam_plugins = get_transient('jam_plugins');

		if($jam_plugins === false){
			$response	= wpjam_remote_request('https://jam.wpweixin.com/api/template/get.json?id=7506');

			if(!is_wp_error($response)){
				$jam_plugins	= $response['template']['table'];

				set_transient('jam_plugins', $jam_plugins, HOUR_IN_SECONDS);
			}
		}

		return $jam_plugins;
	}

	public static function filter_update_plugins($update, $plugin_data, $plugin_file, $locales){
		if($jam_plugin = self::get_jam_plugin($plugin_file)){
			return [
				'id'			=> $plugin_data['UpdateURI'],
				'plugin'		=> $plugin_file,
				'url'			=> $jam_plugin['更新地址'],
				'package'		=> '',
				'icons'			=> [],
				'banners'		=> [],
				'banners_rtl'	=> [],
				'requires'		=> '',
				'tested'		=> '',
				'requires_php'	=> 7.2,
				'new_version'	=> $jam_plugin['版本'],
				'version'		=> $jam_plugin['版本'],
			];
		}

		return $update;
	}
}

add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
	if(in_array($screen_base, ['edit', 'upload', 'term', 'edit-tags'])){
		WPJAM_Edit_Builtin::load($screen_base, $current_screen);
	}elseif($screen_base == 'post'){
		WPJAM_Post_Builtin::load();
	}elseif($screen_base == 'term'){
		add_filter('term_updated_messages',			['WPJAM_Term_Builtin', 'filter_term_updated_messages']);
		add_filter('taxonomy_parent_dropdown_args',	['WPJAM_Term_Builtin', 'filter_parent_dropdown_args'], 10, 3);
	}elseif($screen_base == 'users'){
		if(wpjam_get_user_signups()){
			wpjam_register_list_table_column('openid', ['title'=>'绑定账号',	'column_callback'=>['WPJAM_User_Signup_Type', 'openid_column'],	'order'=>20]);
		}
	}elseif(in_array($screen_base, ['plugins', 'plugins-network'])){
		add_filter('update_plugins_blog.wpjam.com', ['WPJAM_Plugin_Builtin', 'filter_update_plugins'], 10, 4);

		$scripts	= <<<'EOT'
jQuery(function($){
	$('tr.plugin-update-tr').each(function(){
		if($(this).data('slug').indexOf('//blog.wpjam.com/') != -1){
			$(this).find('a.open-plugin-details-modal').removeClass('thickbox open-plugin-details-modal').attr('target','_blank');
		}
	});
});
EOT;
		wp_add_inline_script('jquery', $scripts);

		// delete_site_transient( 'update_plugins' );
		// wpjam_print_r(get_site_transient( 'update_plugins' ));
	}elseif($screen_base == 'dashboard'){
		add_action('wp_dashboard_setup',			['WPJAM_Dashboad_Builtin', 'on_dashboard_setup'], 1);
	}elseif($screen_base == 'dashboard-network'){
		add_action('wp_network_dashboard_setup',	['WPJAM_Dashboad_Builtin', 'on_dashboard_setup'], 1);
	}elseif($screen_base == 'dashboard-user'){
		add_action('wp_user_dashboard_setup',		['WPJAM_Dashboad_Builtin', 'on_dashboard_setup'], 1);
	}
}, 99, 2);