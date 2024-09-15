<?php
add_action('parse_query', function (&$wp_query){
	if(!is_admin() && $wp_query->get('post_type') == 'nav_menu_item'){	// 让菜单也支持缓存
		$wp_query->set('suppress_filters', false);
	}
});

add_filter('posts_pre_query', function ($pre, $wp_query){
	if($wp_query->get('orderby') == 'rand'){	// 随机排序就不能缓存了
		return $pre;
	}

	if(!$wp_query->is_main_query() && $wp_query->get('post_type') != 'nav_menu_item' && !$wp_query->get('cache_it') ){	// 只缓存主循环 || 菜单 || 要求缓存的
		return $pre;
	}

	$key			= md5(serialize(array_filter($wp_query->query_vars)).$wp_query->request);
	$last_changed	= wp_cache_get_last_changed('posts');
	$cache_key		= "wpjam_get_posts:$key:$last_changed";

	// $cache_key	= 'wpjam_cache:'.md5(maybe_serialize($wp_query->query_vars)).':'.$last_changed;
	
	$post_ids		= wp_cache_get($cache_key, 'wpjam_post_ids');

	$wp_query->set('cache_key', $cache_key);
	
	if($post_ids === false){
		return $pre;
	}

	if($post_ids && !$wp_query->is_singular() && empty($wp_query->get('nopaging')) && empty($wp_query->get('no_found_rows'))){	// 如果需要缓存总数
		$found_posts	= wp_cache_get($cache_key, 'wpjam_found_posts');

		if($found_posts === false){
			return $pre;
		}

		$wp_query->set('no_found_rows', true);

		$wp_query->found_posts		= $found_posts;
		$wp_query->max_num_pages	= ceil($found_posts/$wp_query->get('posts_per_page'));
	}

	$args	= wp_array_slice_assoc($wp_query->query_vars, ['update_post_term_cache', 'update_post_meta_cache']);

	return wpjam_get_posts($post_ids, $args);	
}, 10, 2); 

add_filter('posts_results',	 function ($posts, $wp_query) {
	$cache_key	= $wp_query->get('cache_key');

	if($cache_key){
		if(count($posts)>1){
			$post_authors	= wp_list_pluck($posts, 'post_author');
			$post_authors	= array_unique($post_authors);
			$post_authors	= array_filter($post_authors);

			if(count($post_authors)>1){
				cache_users($post_authors);
			}
		}

		$post_ids	= wp_cache_get($cache_key, 'wpjam_post_ids');
		if($post_ids === false){
			wp_cache_set($cache_key, array_column($posts, 'ID'), 'wpjam_post_ids', HOUR_IN_SECONDS);
		}
	}

	return $posts;
}, 10, 2);

add_filter('found_posts', function ($found_posts, $wp_query) {
	$cache_key	= $wp_query->get('cache_key');

	if($cache_key){
		wp_cache_set($cache_key, $found_posts, 'wpjam_found_posts', HOUR_IN_SECONDS);
	}
		
	return $found_posts;
}, 10, 2);


add_filter('the_posts', function($posts, $wp_query){
	if($posts && $wp_query->is_main_query() && !is_admin() && !$wp_query->is_singular() && empty($wp_query->query['related_query'])) {
		$pt_ids			= [];

		$cache_post_ids	= [];
		$cache_terms	= [];

		$fetch_image_ids	= false;

		if($thumbnail_orders	= wpjam_cdn_get_setting('post_thumbnail_orders')){
			$order_types	= wp_list_pluck($thumbnail_orders, 'type');
			if(in_array('first', $order_types)){
				$fetch_image_ids	= true;
			}
		}
		
		foreach ($posts as $post) {
			$post_id	= $post->ID;
			$post_type	= $post->post_type;

			$pt_ids[$post_type][]	= $post_id;

			if($fetch_image_ids){
				if($post->post_content && strpos($post->post_content, '<img')!==false){
					if(preg_match_all( '/class=[\'"].*?wp-image-([\d]*)[\'"]/i', $post->post_content, $matches)){
						$cache_post_ids	= array_merge($cache_post_ids, $matches[1]);
					}
				}
			}
		}

		foreach ($pt_ids as $post_type => $post_ids){
			if(post_type_supports($post_type, 'thumbnail')){
				foreach ($post_ids as $post_id) {
					$cache_post_ids[]	= get_post_thumbnail_id($post_id);
				}
			}

			if($post_fields = wpjam_get_post_fields($post_type)){
				foreach ($post_ids as $post_id) {

					foreach ($post_fields  as $field_key => $post_field) {
						$post_meta_value	= get_post_meta($post_id, $field_key, true);
						if($field_post_ids	= wpjam_get_field_post_ids($post_meta_value, $post_field)){
							$cache_post_ids	= array_merge($cache_post_ids, $field_post_ids);
						}
					}
				}
			}

			// if($taxonomies = get_object_taxonomies($post_type)){
			// 	foreach ($taxonomies as $taxonomy) {
			// 		if($term_fields = wpjam_get_term_options($taxonomy)){
			// 			$term_ids	= [];
			// 			foreach ($post_ids as $post_id) {
			// 				if($terms	= get_the_terms($post_id, $taxonomy)){
			// 					$term_ids	= array_merge($term_ids, wp_list_pluck($terms, 'term_id'));
			// 				}
			// 			}

			// 			$term_ids	= array_values(array_filter($term_ids));
			// 			if($term_ids){
			// 				$cache_terms[$taxonomy]	= ['term_ids'=>$term_ids, 'term_fields'=>$term_fields];
			// 			}		
			// 		}
			// 	}
			// }
		}

		// if($cache_terms){
		// 	$cache_term_ids	= array_values(wp_list_pluck($cache_terms, 'term_ids'));	
		// 	$cache_term_ids	= array_merge(...$cache_term_ids);
			
		// 	update_termmeta_cache($cache_term_ids);

		// 	foreach ($cache_terms as $taxonomy => $cache_term) {
		// 		$term_fields	= $cache_term['term_fields'];
		// 		$term_ids		= $cache_term['term_ids'];

		// 		foreach ($term_fields  as $field_key => $term_field) {
		// 			foreach ($term_ids as $term_id) {
		// 				$term_value	= get_term_meta($term_id, $field_key, true);
		// 				if($field_post_ids	= wpjam_get_field_post_ids($term_value, $term_field)){
		// 					$cache_post_ids	= array_merge($cache_post_ids, $field_post_ids);
		// 				}
		// 			}	
		// 		}
		// 	}
		// }
			
		$cache_post_ids	= array_filter($cache_post_ids);
		$cache_post_ids	= array_unique($cache_post_ids);

		if($cache_post_ids){
			_prime_post_caches($cache_post_ids);
		}
	}

	return $posts;
}, 10, 2);


// add_action('post_updated', function($post_ID, $post_after, $post_before){

// 	$cache_key	= 'wpjam_cache:'.$post_after->post_type.':'.$post_after->post_name;

// 	wpjam_print_R($cache_key);

// 	wp_cache_delete($cache_key, 'wpjam_post_ids');

// 	$cache_key	= 'wpjam_cache:'.$post_before->post_type.':'.$post_before->post_name;

// 	wp_cache_delete($cache_key, 'wpjam_post_ids');

// 	exit;
	
// }, 10, 3);
