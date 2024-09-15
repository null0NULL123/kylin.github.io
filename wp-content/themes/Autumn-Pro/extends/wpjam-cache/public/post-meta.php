<?php
// 使用内存来存储和获取自定义字段信息
add_filter('update_post_metadata', function($check, $post_id, $meta_key, $meta_value){
	if($meta_key == 'views'){
		if($meta_value % 10 != 0){
			$check	= true;

			wp_cache_set($post_id, $meta_value, 'views');
		}else{
			wp_cache_delete($post_id, 'views');
		}
	}elseif($meta_key == '_edit_lock'){
		return wp_cache_set($post_id, $meta_value, '_edit_lock', 300);
	}elseif($meta_key == '_edit_last'){
		if(get_post($post_id)->post_author == $meta_value){
			if(get_post_meta($post_id, $meta_key, true) != $meta_value){
				delete_post_meta($post_id, $meta_key);
			}
			
			return true;
		}
	}

	return $check;
}, 1, 4);

// 防止以经常更新浏览数，破坏列表缓存
add_action('updated_post_meta', function($meta_id, $object_id, $meta_key, $_meta_value){
	if($meta_key == 'views' && $_meta_value % 50 != 0){
		remove_action('updated_post_meta', 'wp_cache_set_posts_last_changed');
	}
}, 1, 4);


add_filter('add_post_metadata', function($check, $post_id, $meta_key, $meta_value){
	if($meta_key == '_edit_lock'){
		return wp_cache_set($post_id, $meta_value, '_edit_lock', 300);
	}elseif($meta_key == '_edit_last'){
		if(get_post($post_id)->post_author == $meta_value){
			return true;
		}
	}elseif($meta_key == '_wp_old_slug'){
		if(strpos($meta_value, '%') !== false){	// 含有 % 说明不是英文，含有中文和特殊字符
			return true;
		}
	}

	return $check;
}, 1, 4);

add_filter('get_post_metadata', function($pre, $post_id, $meta_key){
	$cache_keys	= ['views', '_edit_lock'];
	
	if(in_array($meta_key, $cache_keys)){
		$meta_value	= wp_cache_get($post_id, $meta_key);

		if($meta_value !== false){
			return [$meta_value];
		}
	}elseif($meta_key == '_edit_last'){
		$meta_values	= get_post_meta($post_id);

		if(!isset($meta_values['_edit_last'])){
			$meta_values['_edit_last']	= [get_post($post_id)->post_author];
		}
		
		return $meta_values['_edit_last'];
	}elseif($meta_key == ''){
		$meta_cache	= wp_cache_get($post_id, 'post_meta');

		if($meta_cache === false) {
			$meta_cache	= update_meta_cache('post', [$post_id]);
			$meta_cache	= $meta_cache[$post_id];
		}

		foreach($cache_keys as $mkey){
			$mval	= wp_cache_get($post_id, $mkey);
			if($mval !== false){
				$meta_cache[$mkey]	= [$mval];
			}
		}

		return $meta_cache;	
	}

	return $pre;
}, 1, 3);

