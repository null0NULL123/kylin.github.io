<?php
/*
Name: 文章数量
URI: https://blog.wpjam.com/m/wpjam-posts-per-page/
Description: 设置不同页面不同的文章列表数量，不同的分类不同文章列表数量。
Version: 1.0
*/
class WPJAM_Posts_Per_Page_Setting{
	use WPJAM_Setting_Trait;

	private function __construct(){
		$this->init('wpjam-posts-per-page');
	}

	public function sanitize_callback($value){
		foreach (['posts_per_page', 'posts_per_rss'] as $option_name) {
			if(isset($value[$option_name])){
				if($value[$option_name]){
					update_option($option_name, $value[$option_name]);
				}

				unset($value[$option_name]);
			}
		}

		return $value;
	}

	public function get_fields(){
		if($GLOBALS['current_tab'] == 'posts-per-page'){
			$fields	= [];

			$fields['posts_per_page']	= ['title'=>'全局数量',	'type'=>'number',	'value'=>get_option('posts_per_page'),	'description'=>'博客全局设置的文章列表数量'];
			$fields['posts_per_rss']	= ['title'=>'Feed数量',	'type'=>'number',	'value'=>get_option('posts_per_rss'),	'description'=>'Feed中最近文章列表数量'];

			foreach(['home'=>'首页','author'=>'作者页','search'=>'搜索页','archive'=>'存档页'] as $page_key=>$page_name){
				$fields[$page_key]	= ['title'=>$page_name,	'type'=>'number'];
			}

			$taxonomies = get_taxonomies(['public'=>true,'show_ui'=>true],'objects');

			if(isset($taxonomies['series'])){
				unset($taxonomies['series']);
			}

			if($taxonomies){
				$taxonomies	= wp_list_sort($taxonomies, 'hierarchical', 'DESC', true);
				foreach ($taxonomies as $taxonomy=>$taxonomy_obj) {
					$sub_fields	= [];

					$sub_fields[$taxonomy]	= ['title'=>'',	'type'=>'number'];

					if($taxonomy_obj->hierarchical){
						$sub_fields[$taxonomy.'_individual']	= ['title'=>'',	'type'=>'checkbox',	'description'=>'每个'.$taxonomy_obj->label.'可独立设置数量'];
					}

					$fields[$taxonomy.'_set']	= ['title'=>$taxonomy_obj->label,	'type'=>'fieldset',	'fields'=>$sub_fields];
				}
			}

			$post_types = get_post_types(['public'=>true, 'has_archive'=>true],'objects');

			if($post_types){
				$sub_fields = [];
				foreach ($post_types as $post_type=>$pt_obj) {
					$sub_fields[$post_type]	= ['title'=>$pt_obj->label,	'type'=>'number'];
				}

				if(count($post_types) == 1){
					$field	= $sub_fields[$post_type];
					$field['title']		.= '存档页';
					$fields[$post_type]	= $field;
				}else{
					$fields['post_type']	= ['title'=>'文章类型存档页',	'type'=>'fieldset',	'fields'=>$sub_fields];
				}
			}
		}else{
			$post_types = get_post_types(['exclude_from_search'=>false],'objects');

			unset($post_types['page']);
			unset($post_types['attachment']);

			$post_type_options	= wp_list_pluck($post_types, 'label');

			$fields	= [];

			foreach(['home'=>'首页','author'=>'作者页','feed'=>'Feed页'] as $page_key=>$page_name){
				$fields[$page_key.'_post_types']	= ['title'=>$page_name,	'type'=>'checkbox',	'value'=>['post'],	'options'=>$post_type_options];
			}
		}

		return $fields;
	}
}

class WPJAM_Posts_Per_Page{
	public static function get_term_fields($term_id){
		$default	= wpjam_posts_per_page_get_setting(get_term($term_id)->taxonomy) ?: get_option('posts_per_page');

		return [
			'default'			=> ['title'=>'默认数量',	'type'=>'view',		'value'=>$default],
			'posts_per_page'	=> ['title'=>'文章数量',	'type'=>'number',	'class'=>'']
		];
	}

	public static function filter_term_row_actions($actions, $term){
		if($posts_per_page = get_term_meta($term->term_id, 'posts_per_page', true)){
			$posts_per_page	= $posts_per_page ? '（'.$posts_per_page.'）' : '';

			$actions['posts_per_page']	= str_replace('>文章数量<', '>文章数量'.$posts_per_page.'<', $actions['posts_per_page']);
		}

		return $actions;
	}

	public static function get_post_types($page){
		if(count(get_post_types(['exclude_from_search'=>false], 'objects')) > 3){
			return wpjam_posts_per_page_get_setting($page.'_post_types');
		}else{
			return [];
		}
	}

	public static function on_pre_get_posts($wp_query) {
		if(!$wp_query->is_main_query()){
			return;
		}

		if(is_home() || is_front_page()){
			$number		= wpjam_posts_per_page_get_setting('home');
			$post_types	= self::get_post_types('home');
		}elseif(is_feed()){
			$post_types	= self::get_post_types('feed');
		}elseif(is_author()){
			$number		= wpjam_posts_per_page_get_setting('author');
			$post_types	= self::get_post_types('author');
		}elseif(is_tax() || is_category() || is_tag()){
			if($term = $wp_query->get_queried_object()){
				$taxonomy	= $term->taxonomy;

				$number		= wpjam_posts_per_page_get_setting($taxonomy);
				$individual	= wpjam_posts_per_page_get_setting($taxonomy.'_individual');

				if($individual && metadata_exists('term', $term->term_id, 'posts_per_page')){
					$number	= get_term_meta($term->term_id, 'posts_per_page', true);
				}

				if(is_category() || is_tag()){
					$post_types	= get_taxonomy($taxonomy)->object_type;
					$post_types	= array_intersect($post_types, get_post_types(['public'=>true]));
				}
			}
		}elseif(is_post_type_archive()){
			$pt_object	= $wp_query->get_queried_object();
			$number		= wpjam_posts_per_page_get_setting($pt_object->name);
		}elseif(is_search()){
			$number		= wpjam_posts_per_page_get_setting('search');
		}elseif(is_archive()){
			$number		= wpjam_posts_per_page_get_setting('archive');
			$post_types	= 'any';
		}

		if(!empty($number)){
			$wp_query->set('posts_per_page', $number);
		}

		if(!isset($wp_query->query['post_type']) && !empty($post_types)){
			if(is_array($post_types) && count($post_types) == 1) {
				$post_types	= $post_types[0];
			}

			$wp_query->set('post_type', $post_types);
		}
	}

	public static function on_builtin_page_load($screen_base, $current_screen){
		if(in_array($screen_base, ['edit-tags', 'term'])){
			$taxonomy	= $current_screen->taxonomy;

			if(is_taxonomy_hierarchical($taxonomy) && wpjam_posts_per_page_get_setting($taxonomy.'_individual')){
				wpjam_register_list_table_action('posts_per_page',[
					'title'			=> '文章数量',
					'page_title'	=> '设置文章数量',
					'submit_text'	=> '设置',
					'width'			=> 400,
					'fields'		=> [self::class, 'get_term_fields']
				]);

				add_filter($taxonomy.'_row_actions', [self::class, 'filter_term_row_actions'], 10, 2);	
			}
		}
	}
}

function wpjam_posts_per_page_get_setting($name, $default=null){
	return WPJAM_Posts_Per_Page_Setting::get_instance()->get_setting($name, $default);
}

add_action('wp_loaded', function(){
	if(is_admin() && (!is_multisite() || !is_network_admin())){
		wpjam_register_plugin_page_tab('posts-per-page', [
			'title'			=> '文章数量',	
			'function'		=> 'option',	
			'option_name'	=> 'wpjam-posts-per-page',
			'plugin_page'	=> 'wpjam-posts',
			'order'			=> 18,
			'model'			=> 'WPJAM_Posts_Per_Page_Setting',
			'summary'		=> '文章数量扩展可以设置不同页面不同的文章列表数量，也可开启不同的分类不同文章列表数量。<br />空或者0则使用全局设置，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-posts-per-page/" target="_blank">文章数量扩展</a>。'
		]);

		if(count(get_post_types(['exclude_from_search'=>false], 'objects')) > 3){
			wpjam_register_plugin_page_tab('post_types-per-page', [
				'title'			=> '文章类型',	
				'function'		=> 'option',	
				'option_name'	=> 'wpjam-posts-per-page',
				'plugin_page'	=> 'wpjam-posts',
				'order'			=> 18,
				'model'			=> 'WPJAM_Posts_Per_Page_Setting',
				'summary'		=> '文章类型扩展可以设置不同页面显示不同文章类型，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-posts-per-page/" target="_blank">文章类型扩展</a>。'
			]);
		}

		add_action('wpjam_builtin_page_load',	['WPJAM_Posts_Per_Page', 'on_builtin_page_load'], 10, 2);
	}else{
		add_action('pre_get_posts',  ['WPJAM_Posts_Per_Page', 'on_pre_get_posts']);
	}
});
