<?php
class WPJAM_Taxonomy_Builtin{
	public static function filter_pre_insert_term($term, $taxonomy){
		if($taxonomy == get_current_screen()->taxonomy 
			&& is_taxonomy_hierarchical($taxonomy)
		){
			$levels	= get_taxonomy($taxonomy)->levels;

			if($levels && !empty($_POST['parent']) && $_POST['parent']!=-1){
				if(wpjam_get_term_level($_POST['parent']) >= $levels-1){
					return new WP_Error('invalid_parent', '不能超过'.$levels.'级');
				}
			}
		}

		return $term;
	}

	public static function filter_per_page($per_page){
		$parent	= $GLOBALS['wpjam_list_table']->get_parent();

		return !is_null($parent) ? 9999 : $per_page;
	}

	public static function sort_column_callback($term_id){
		$parent	= $GLOBALS['wpjam_list_table']->get_parent();
		
		if(is_null($parent) || wpjam_get_data_parameter('orderby') || wpjam_get_data_parameter('s')){
			return wpjam_admin_tooltip('<span class="dashicons dashicons-editor-help"></span>', '如要进行排序，请先点击「只显示第一级」按钮。');
		}elseif(get_term($term_id)->parent == $parent){
			return 
			'<div class="row-actions">'.
			'<span class="move">'.wpjam_get_list_table_row_action('move',		['id'=>$term_id]).'</span>'.
			'<span class="up"> | '.wpjam_get_list_table_row_action('up',		['id'=>$term_id]).'</span>'.
			'<span class="down"> | '.wpjam_get_list_table_row_action('down',	['id'=>$term_id]).'</span>'.
			'</div>';
		}
	}

	public static function get_fields($term_id, $action_key=''){
		if($action_key == 'merge_to'){
			$fields	= ['taxonomy'=>['title'=>'',	'type'=>'select',	'options'=>[]]];

			foreach(get_object_taxonomies(get_current_screen()->post_type, 'objects') as $taxonomy => $object){
				if($object->show_ui){
					$fields['taxonomy']['options'][$taxonomy]	= $object->label;

					$fields[$taxonomy.'_id']	= array_merge(wpjam_get_term_id_field($taxonomy), ['title'=>'', 'required', 'show_if'=>['key'=>'taxonomy', 'value'=>$taxonomy]]);
				}		
			}

			return [
				'merge_to'	=> ['title'=>'合并到',	'type'=>'fieldset',	'fields'=>$fields],
				'delete'	=> ['title'=>'删除',		'type'=>'checkbox',	'description'=>'<span class="attention">合并之后删除旧的'.get_taxonomy(get_term($term_id)->taxonomy)->label.'「<strong>'.get_term($term_id)->name.'</strong>」。</span>']
			];
		}
	}

	public static function merge_to($term_id, $data){
		$current	= get_current_screen()->taxonomy;
		$taxonomy	= $data['taxonomy'];
		$merge_to	= (int)$data[$taxonomy.'_id'];

		if($merge_to){
			$merge_term	= get_term($merge_to, $taxonomy);
		}

		if(!$merge_to || !$merge_term){
			return new WP_Error('invalid_merge_to', '合并到的'.get_taxonomy($current)->label.'无效');
		}

		if(is_wp_error($merge_term)){
			return $merge_term;
		}

		if($merge_to == $term_id){
			return new WP_Error('same_merge_to', '合并到的'.get_taxonomy($current)->label.'不能同一个');
		}

		$post_types	= get_taxonomy($current)->object_type;

		if($taxonomy != $current){
			$post_types	= array_intersect($post_types, get_taxonomy($taxonomy)->object_type);
		}

		$wp_query	= new WP_Query([
			'tax_query' => [[
				'taxonomy'	=> $current,
				'field'		=> 'term_id',
				'terms'		=> $term_id,
			]],
			'post_type'	=> $post_types,
			'nopaging'	=> true,
		]);

		if($data['delete']){
			$result	= wp_delete_term($term_id, $current);

			if(is_wp_error($result)){
				return $result;
			}elseif($result == 0){
				return new WP_Error('delete_default_'.$current.'_not_allow', '默认'.get_taxonomy($current)->label.'不能被合并');
			}
		}	

		foreach($wp_query->posts as $post){
			wp_set_post_terms($post->ID, [$merge_to], $taxonomy, true);
		}

		wp_cache_set_posts_last_changed();

		if($data['delete']){
			return ['type'=>'items', 'items'=>[$term_id=>['type'=>'delete', 'dismiss'=>true], $merge_to=>['type'=>'update']]];
		}else{
			return ['bulk'=>true, 'ids'=>[$term_id, $merge_to]];
		}
	}

	public static function export_posts_button($post_type){
		echo '<a href="'.str_replace('export_required', 'export_action', wpjam_get_current_page_url()).'" class="button button-primary">导出</a>';
	}

	public static function filter_request($query_vars){
		if(wpjam_get_data_parameter('export_action')){
			$query_vars['posts_per_page']	= -1;
			$query_vars['post_status']		= 'publish';
		}

		return $query_vars;
	}

	public static function export_posts(){
		global $wp_query;

		wp_edit_posts_query();

		if($wp_query->found_posts <= 0){
			wp_die('导出的文章为空');
		}

		$csv_key	= md5($wp_query->query_vars).'_'.time();

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=posts_'.$csv_key.'.csv');
		header('Pragma: no-cache');
		header('Expires: 0');

		$file_handle	= fopen('php://output', 'w');

		fwrite($file_handle, chr(0xEF).chr(0xBB).chr(0xBF));
		fputcsv($file_handle, ['标题', '链接']);

		while($wp_query->have_posts()){
			$wp_query->the_post();

			fputcsv($file_handle, [get_the_title() ,get_the_permalink()]);
		}

		fclose($file_handle);
		exit;
	}

	public static function page_load($screen_base, $current_screen){
		if($screen_base == 'edit-tags'){
			$taxonomy	= $current_screen->taxonomy;

			if(is_taxonomy_hierarchical($taxonomy)){
				if(get_taxonomy($taxonomy)->sortable){
					add_filter('edit_'.$taxonomy.'_per_page',	[self::class, 'filter_per_page']);

					wpjam_register_list_table_column('sort',	['title'=>'排序',	'column_callback'=>[self::class, 'sort_column_callback']]);

					wp_add_inline_style('list-tables', "th.column-sort{width: 80px;}\ntd.column-sort div.row-actions{left: 0;}");
				}

				add_filter('pre_insert_term',	[self::class, 'filter_pre_insert_term'], 10, 2);
			}

			wpjam_register_list_table_action('merge_to', [
				'title'			=>'合并到',
				'page_title'	=>'合并到',
				'submit_text'	=>'合并',
				'fields'		=>[self::class, 'get_fields'],
				'callback'		=>[self::class, 'merge_to']
			]);
		}elseif($screen_base == 'edit'){
			if(wpjam_get_data_parameter('export_required')){
				add_action('restrict_manage_posts',	[self::class, 'export_posts_button']);
			}

			if(wpjam_get_data_parameter('export_action')){
				add_filter('request',		[self::class, 'filter_request']);
				add_action('load-edit.php', [self::class, 'export_posts']);
			}
		}
	}
}

add_action('wpjam_builtin_page_load',	['WPJAM_Taxonomy_Builtin', 'page_load'], 10, 2);