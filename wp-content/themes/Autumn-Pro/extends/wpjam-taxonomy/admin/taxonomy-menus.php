<?php
class WPJAM_Taxonomy_Menu{
	public static function load_option_page(){
		$levels_fields		= [];
		$order_fields		= [];
		$permalink_fields	= [];
		$filter_fields		= [];

		$no_base_taxonomy	= wpjam_basic_get_setting('no_category_base');

		foreach(get_taxonomies([],'objects') as $taxonomy=>$tax_obj){
			$label	= $tax_obj->label;

			if($tax_obj->hierarchical && $tax_obj->show_ui){
				$levels	= wpjam_taxonomy_get_setting($taxonomy.'__levels');

				if(isset($tax_obj->levels) && is_null($levels)){
					$levels_fields[$taxonomy.'__levels']	= ['title'=>$label,	'type'=>'view',		'value'=>'代码设置为：'.$tax_obj->levels.'层'];
				}else{
					$levels_fields[$taxonomy.'__levels']	= ['title'=>$label,	'type'=>'number',	'value'=>0,	'class'=>'small-text',	'description'=>'层'];
				}

				$sortable	= wpjam_taxonomy_get_setting($taxonomy.'__sortable');

				if((isset($tax_obj->sortable)) && is_null($sortable)){
					$order_fields[$taxonomy.'__sortable']	= ['title'=>$label,	'type'=>'view',	'value'=>'代码设置为：支持'];
				}else{
					$order_fields[$taxonomy.'__sortable']	= ['title'=>$label,	'type'=>'checkbox',	'value'=>0,	'description'=>'支持拖动排序'];
				}
			}

			if($tax_obj->rewrite && $taxonomy != 'post_format'){
				$permalink	= wpjam_taxonomy_get_setting($taxonomy.'__permalink');

				if($no_base_taxonomy && $no_base_taxonomy == $taxonomy){
					$permalink_fields[$taxonomy.'__permalink']	= ['title'=>$label,	'type'=>'view',	'value'=>'已经设置为去掉目录中的<code>'.$taxonomy.'</code>'];
				}elseif(isset($tax_obj->permastruct) && is_null($permalink)){
					$permalink_fields[$taxonomy.'__permalink']	= ['title'=>$label,	'type'=>'view',	'value'=>'代码设置为：<code>'.$tax_obj->permastruct.'</code>'];
				}else{
					$sample_link	= user_trailingslashit(home_url($tax_obj->rewrite['slug'].'/123'));

					$permalink_fields[$taxonomy.'__permalink']	= ['title'=>$label,	'type'=>'checkbox',	'description'=>'使用数字固定链接：<code>'.$sample_link.'</code>'];
				}
			}

			if($tax_obj->show_admin_column && $tax_obj->show_ui){
				$filterable	= wpjam_taxonomy_get_setting($taxonomy.'__filterable');

				if(isset($tax_obj->filterable) && is_null($filterable)){
					$filter_fields[$taxonomy.'__filterable']	= ['title'=>$label,	'type'=>'view',	'value'=>'代码设置为：支持'];
				}else{
					$value	= $taxonomy == 'category' ? 1 : 0;

					$filter_fields[$taxonomy.'__filterable']	= ['title'=>$label,	'type'=>'checkbox',	'description'=>'后台文章列表页支持'.$label.'过滤',	'value'=>$value];
				}
			}
		}

		$levels		= get_taxonomy('category')->levels ?? 0;
		$cats		= wpjam_get_terms(['taxonomy'=>'category',	'hide_empty'=>0], $levels);
		$cats		= wpjam_flatten_terms($cats);
		$options	= $cats ? wp_list_pluck($cats, 'name', 'id') : [];

		$home_cats_fields	= [];

		$platform_options	= WPJAM_Platform::get_options('key');
		$home_cats_fields['home_cats_platforms']	= ['title'=>'设置的平台',	'type'=>'checkbox',	'options'=>$platform_options];

		$sub_fields	= [
			'type'	=> ['title'=>'',	'type'=>'select',	'options'=>[''=>'显示所有分类下的文章','category__in'=>'仅显示设置分类下的文章','category__not_in'=>'不显示设置分类下的文章']],
			'cats'	=> ['title'=>'',	'type'=>'mu-text',	'item_type'=>'select',	'options'=>[''=>'']+$options]
		];

		foreach ($platform_options as $platform => $platform_title) {
			$sub_fields['cats']['show_if']	= ['key'=>'home_cats_'.$platform.'_type', 'compare'=>'!=', 'value'=>''];

			$home_cats_fields['home_cats_'.$platform]	= ['title'=>$platform_title,	'type'=>'fieldset',	'fieldset_type'=>'array',	'show_if'=>['key'=>'home_cats_platforms', 'value'=>$platform],	'fields'=>$sub_fields];
		}

		wpjam_register_option('wpjam_taxonomy_setting', [
			'sections'			=> [
				'levels'	=> ['title'=>'分类层级',	'fields'=>$levels_fields,	'summary'=>'请设置分类的层级，层级为0则不限制层级。'],
				'order'		=> ['title'=>'分类排序',	'fields'=>$order_fields],
				'permalink'	=> ['title'=>'固定链接',	'fields'=>$permalink_fields],
				'filter'	=> ['title'=>'文章过滤',	'fields'=>$filter_fields],
				'home_cats'	=> ['title'=>'首页分类',	'fields'=>$home_cats_fields],
			],
			'sanitize_callback'	=> [self::class, 'sanitize_callback'],
			'summary'			=> '分类设置插件支持层式管理分类，设置分类层级，分类拖动排序和文章分类筛选过滤功能，详细介绍请点击：<a href="https://blog.wpjam.com/project/wpjam-taxonomy/" target="_blank">分类设置插件</a>。'
		]);
	}

	public static function sanitize_callback($value){
		flush_rewrite_rules();

		return $value;
	}

	public static function on_admin_init(){
		wpjam_add_menu_page('wpjam-taxonomy', [
			'parent'		=> 'wpjam-basic',
			'menu_title'	=> '分类设置',
			'order'			=> 17,
			'network'		=> false,
			'function'		=> 'option',
			'option_name'	=> 'wpjam_taxonomy_setting',
			'load_callback'	=> [self::class, 'load_option_page']
		]);

		foreach(get_post_types(['show_ui'=>true]) as $post_type){
			if(is_object_in_taxonomy($post_type, 'post_tag')){
				wpjam_add_menu_page($post_type.'-tag-groups', [
					'parent'		=> $post_type.'s',
					'order'			=> 30,
					'menu_title'	=> '标签集',
					'summary'		=> '将多个标签合并成一个标签集，通过标签集显示文章列表',
					'capability'	=> 'edit_others_posts',
					'function'		=> 'list',
					'plural'		=> 'post_tag-groups',
					'singular'		=> 'post_tag-group',
					'model'			=> 'WPJAM_Post_Tag_Groups_Admin',
					'page_file'		=> __DIR__.'/tag-groups.php'
				]);
			}

			$taxonomies	= get_object_taxonomies($post_type, 'objects');
			$taxonomies	= wp_filter_object_list($taxonomies, ['show_ui'=>true, 'filterable'=>true]);

			if($taxonomies){
				wpjam_add_menu_page($post_type.'-terms-filter', [
					'parent'		=> $post_type.'s',
					'post_type'		=> $post_type,
					'taxonomies'	=> $taxonomies,
					'menu_title'	=> get_post_type_object($post_type)->label.'筛选',
					'function'		=> 'form',
					'form_name'		=> 'terms_filter',
					'capability'	=> 'edit_posts',
					'page_file'		=> __DIR__.'/terms-filter.php'
				]);
			}
		}
	}
}

add_action('wpjam_admin_init',	['WPJAM_Taxonomy_Menu', 'on_admin_init']);