<?php
class WPJAM_Taxonomy_Hook{	
	public static function filter_register_args($args, $taxonomy){
		if($order = wpjam_array_pull($args, 'order')){
			$args['sortable']	= $order;
		}

		$no_base_taxonomy	= wpjam_basic_get_setting('no_category_base');

		foreach(['levels'=>0, 'sortable'=>0, 'filterable'=>null, 'permalink'=>0] as $key=>$default){
			$setting_name	= $taxonomy.'__'.$key;

			$value	= wpjam_taxonomy_get_setting($setting_name);

			if($key == 'permalink'){
				if($value && !isset($args['permastruct']) && $no_base_taxonomy != $taxonomy){
					$slug	= is_array($args['rewrite']) ? ($args['rewrite']['slug'] ?? $taxonomy) : $taxonomy;

					$args['permastruct']	= $slug.'/%term_id%';
				}else{
					if(!is_null($value)){
						wpjam_taxonomy_delete_setting($setting_name);
					}
				}
			}else{
				if(!isset($args[$key])){
					$args[$key]	= $value ?? $default;
				}else{

					if(!is_null($value)){
						wpjam_taxonomy_delete_setting($setting_name);
					}
				}
			}
		}

		return $args;
	}

	public static function sort_callback($term1, $term2){
		if($term1->parent == $term2->parent){
			$order1	= get_term_meta($term1->term_id, 'order', true) ?: 9999;
			$order2	= get_term_meta($term2->term_id, 'order', true) ?: 9999;

			if($order2 === $order1){
				return $term2->term_id <=> $term1->term_id;
			}else{
				return (int)$order2 <=> (int)$order1;
			}
		}else{
			if($term1->parent == 0){
				return 1;
			}elseif($term2->parent == 0){
				return -1;
			}else{
				$level1	= wpjam_get_term_level($term1);
				$level2	= wpjam_get_term_level($term2);

				return $level1 <=> $level2;
			}
		}
	}

	public static function filter_terms($terms, $taxonomies, $query_vars){
		if($terms 
			&& $query_vars['fields'] == 'all'
			&& absint($query_vars['offset']) == 0
			&& absint($query_vars['number']) == 0
			&& wpjam_is_taxonomy_sortable($taxonomies)
			&& ($query_vars['orderby'] == 'order' 
				|| (in_array($query_vars['orderby'], ['id', 'name'])
					&& array_intersect(array_column(debug_backtrace(), 'function'), ['wp_dropdown_categories', 'wp_list_categories', 'rest_api_loaded'])
				)
			)
		){
			uasort($terms, [self::class, 'sort_callback']);
		}

		return $terms;
	}

	public static function filter_get_object_terms_args($args, $object_ids, $taxonomies){
		if($object_ids 
			&& empty($args['orderby'])
			&& count($taxonomies) == 1 
			&& get_taxonomy(current($taxonomies))->sort
		){
			$args['orderby']	= 'term_order';
		}

		return $args;
	}

	public static function filter_document_title_parts($title){
		if($tag_group_slug = get_query_var('tag_group')){
			if($tag_group	= wpjam_get_tag_group($tag_group_slug)){
				$title['title']	= $tag_group['name'];
			}
		}

		return $title;
	}

	public static function filter_terms_defaults($defaults, $taxonomies){
		if(wpjam_is_taxonomy_sortable($taxonomies)){
			$defaults['orderby']	= 'order';
		}

		return $defaults;
	}

	public static function on_pre_get_posts($wp_query){
		if($wp_query->is_main_query()){
			if($tag_group_slug = get_query_var('tag_group')){
				$tag_group	= wpjam_get_tag_group($tag_group_slug);

				if(!$tag_group){
					$wp_query->set_404();
				}else{
					$wp_query->is_home		= false;
					$wp_query->is_tag_group	= true;

					$relation	= $tag_group['relation'];
					$tags		= $tag_group['tags'];

					$wp_query->set('post_type', get_taxonomy('post_tag')->object_type);

					if($relation == 'related'){
						$wp_query->set('related_query', true);
						$wp_query->set('term_taxonomy_ids', array_column(WPJAM_Term::get_by_ids($tags), 'term_taxonomy_id'));
					}elseif($relation == 'or'){
						$wp_query->set('tag__in', $tags);
					}elseif($relation == 'and'){
						$wp_query->set('tag__and', $tags);
					}
				}
			}elseif($wp_query->is_home){
				if($platforms = wpjam_taxonomy_get_setting('home_cats_platforms')){
					$platform	= wpjam_get_current_platform($platforms);
					$settings	= $platform ? wpjam_taxonomy_get_setting('home_cats_'.$platform) : [];
					$type		= $settings ? ($settings['type'] ?? '') : '';

					if(in_array($type, ['category__in', 'category__not_in'])){
						$cats	= $settings['cats'] ?? [];
						$cats	= $cats ? array_filter(array_map('intval', $cats)): [];

						if($cats){
							$wp_query->set($type, $cats);
						}
					}
				}
			}		
		}
	}

	public static function add_rewrite_rules(){
		$GLOBALS['wp']->add_query_var('tag_group');

		add_rewrite_rule($GLOBALS['wp_rewrite']->root.'tag-group/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?tag_group=$matches[1]&paged=$matches[2]', 'top');
		add_rewrite_rule($GLOBALS['wp_rewrite']->root.'tag-group/([^/]+)/?$', 'index.php?tag_group=$matches[1]', 'top');
	}
}

add_filter('register_taxonomy_args',	['WPJAM_Taxonomy_Hook',	'filter_register_args'], 9, 2);
add_filter('document_title_parts',		['WPJAM_Taxonomy_Hook',	'filter_document_title_parts'], 9, 2);
add_filter('get_terms',					['WPJAM_Taxonomy_Hook',	'filter_terms'], 10, 3);
add_filter('get_terms_defaults',		['WPJAM_Taxonomy_Hook',	'filter_terms_defaults'], 10, 2);
add_filter('wp_get_object_terms_args',	['WPJAM_Taxonomy_Hook',	'filter_get_object_terms_args'], 10, 3);

add_action('init',			['WPJAM_Taxonomy_Hook',	'add_rewrite_rules']);
add_action('pre_get_posts',	['WPJAM_Taxonomy_Hook', 'on_pre_get_posts']);

