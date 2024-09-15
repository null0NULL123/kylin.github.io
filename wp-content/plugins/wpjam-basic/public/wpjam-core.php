<?php
function wpjam_json_encode($data, $options=JSON_UNESCAPED_UNICODE, $depth=512){
	return wp_json_encode($data, $options, $depth);
}

function wpjam_json_decode($json, $assoc=true, $depth=512, $options=0){
	if($json = wpjam_strip_control_characters($json)){
		$result	= json_decode($json, $assoc, $depth, $options);

		if(is_null($result)){
			$result	= json_decode(stripslashes($json), $assoc, $depth, $options);

			if(is_null($result)){
				if(wpjam_doing_debug()){
					print_r(json_last_error());
					print_r(json_last_error_msg());
				}
				trigger_error('json_decode_error '. json_last_error_msg()."\n".var_export($json,true));
				return new WP_Error('json_decode_error', json_last_error_msg());
			}
		}

		return $result;
	}else{
		return new WP_Error('empty_json', 'JSON 内容不能为空！');
	}
}

function wpjam_send_json($response=[], $status_code=null){
	if(is_wp_error($response)){
		$errdata	= $response->get_error_data();
		$response	= ['errcode'=>$response->get_error_code(), 'errmsg'=>$response->get_error_message()];

		if($errdata){
			$errdata	= is_array($errdata) ? $errdata : ['errdata'=>$errdata];
			$response 	= $response + $errdata;
		}
	}else{
		$response	= array_merge(['errcode'=>0], $response);
	}

	$result	= wpjam_json_encode($response);

	if(!headers_sent() && !wpjam_doing_debug()){
		if(!is_null($status_code)){
			status_header($status_code);
		}

		if(wp_is_jsonp_request()){
			@header('Content-Type: application/javascript; charset=' . get_option('blog_charset'));

			$jsonp_callback	= $_GET['_jsonp'];

			$result	= '/**/' . $jsonp_callback . '(' . $result . ')';

		}else{
			@header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		}
	}

	echo $result;

	exit;
}

function wpjam_method_allow($method, $send=true){
	$method	= strtoupper($method);

	if($_SERVER['REQUEST_METHOD'] != $method){
		$wp_error = new WP_Error('method_not_allow', '接口不支持 '.$_SERVER['REQUEST_METHOD'].' 方法，请使用 '.$method.' 方法！');
		
		return $send ? wpjam_send_json($wp_error): $wp_error;
	}

	return true;
}

// 获取参数，
function wpjam_get_parameter($parameter, $args=[]){
	$value	= null;
	$method	= !empty($args['method']) ? strtoupper($args['method']) : 'GET';

	if($method == 'GET'){
		if(isset($_GET[$parameter])){
			$value = wp_unslash($_GET[$parameter]);
		}
	}else{
		if(empty($_POST) && ($method == 'POST' || !isset($_GET[$parameter]))){
			static $post_input;

			if(!isset($post_input)){
				$post_input	= file_get_contents('php://input');

				if(is_string($post_input)){
					$post_input	= @wpjam_json_decode($post_input);
				}
			}

			if(is_array($post_input) && isset($post_input[$parameter])){
				$value = $post_input[$parameter];
			}
		}else{
			if($method == 'POST'){
				if(isset($_POST[$parameter])){
					$value = wp_unslash($_POST[$parameter]);
				}
			}else{
				if(isset($_REQUEST[$parameter])){
					$value = wp_unslash($_REQUEST[$parameter]);
				}
			}
		}
	}

	if(is_null($value) && isset($args['default'])){
		return $args['default'];
	}

	$validate_callback	= $args['validate_callback'] ?? '';

	$send	= $args['send'] ?? true;

	if($validate_callback && is_callable($validate_callback)){
		$result	= call_user_func($validate_callback, $value);

		if($result === false){
			$wp_error = new WP_Error('invalid_parameter', '非法参数：'.$parameter);

			return $send ? wpjam_send_json($wp_error): $wp_error;
		}elseif(is_wp_error($result)){
			return $send ? wpjam_send_json($result): $result;
		}
	}else{
		if(!empty($args['required']) && is_null($value)){
			$wp_error = new WP_Error('missing_parameter', '缺少参数：'.$parameter);

			return $send ? wpjam_send_json($wp_error): $wp_error;
		}

		$length	= $args['length'] ?? 0;
		$length	= (int)$length;

		if($length && (mb_strlen($value) < $length)){
			$wp_error = new WP_Error('short_parameter', $parameter.' 参数长度不能少于 '.$length);

			return $send ? wpjam_send_json($wp_error): $wp_error;
		}
	}

	$sanitize_callback	= $args['sanitize_callback'] ?? '';

	if($sanitize_callback && is_callable($sanitize_callback)){
		$value	= call_user_func($sanitize_callback, $value);
	}else{
		if(!empty($args['type']) && $args['type'] == 'int' && $value){
			$value	= (int)$value;
		}
	}

	return $value;
}

function wpjam_get_data_parameter($parameter, $args=[]){
	$value		= null;

	if(isset($_GET[$parameter])){
		$value	= wp_unslash($_GET[$parameter]);
	}elseif(isset($_REQUEST['data'])){
		$data		= wp_parse_args(wp_unslash($_REQUEST['data']));
		$defaults	= !empty($_REQUEST['defaults']) ? wp_parse_args(wp_unslash($_REQUEST['defaults'])) : [];
		$data		= wpjam_array_merge($defaults, $data);

		if(isset($data[$parameter])){
			$value	= $data[$parameter];
		}
	}

	if(is_null($value) && isset($args['default'])){
		return $args['default'];
	}

	$sanitize_callback	= $args['sanitize_callback'] ?? '';

	if(is_callable($sanitize_callback)){
		$value	= call_user_func($sanitize_callback, $value);
	}

	return $value;
}

function wpjam_http_request($url, $args=[], $err_args=[]){
	$args = wp_parse_args($args, [
		'timeout'		=> 5,
		'body'			=> [],
		'headers'		=> [],
		'sslverify'		=> false,
		'blocking'		=> true,	// 如果不需要立刻知道结果，可以设置为 false
		'stream'		=> false,	// 如果是保存远程的文件，这里需要设置为 true
		'filename'		=> null,	// 设置保存下来文件的路径和名字
		// 'headers'	=> ['Accept-Encoding'=>'gzip;'],	//使用压缩传输数据
		// 'headers'	=> ['Accept-Encoding'=>''],
		// 'compress'	=> false,
		'decompress'	=> true,
	]);

	if(wpjam_doing_debug()){
		print_r($url);
		print_r($args);
	}

	if(isset($args['json_encode_required'])){
		$json_encode_required	= wpjam_array_pull($args, 'json_encode_required');
	}elseif(isset($args['need_json_encode'])){
		$json_encode_required	= wpjam_array_pull($args, 'need_json_encode');
	}else{
		$json_encode_required	= false;
	}

	if(isset($args['json_decode_required'])){
		$json_decode_required	= wpjam_array_pull($args, 'json_decode_required');
	}elseif(isset($args['need_json_decode'])){
		$json_decode_required	= wpjam_array_pull($args, 'need_json_decode');
	}else{
		$json_decode_required	= $args['stream'] ? false : true;
	}

	$method	= wpjam_array_pull($args, 'method');
	$method	= $method ? strtoupper($method) : ($args['body'] ? 'POST' : 'GET');

	if($method == 'GET'){
		$response = wp_remote_get($url, $args);
	}elseif($method == 'POST'){
		if($json_encode_required){
			if(is_array($args['body'])){
				$args['body']	= wpjam_json_encode($args['body']);
			}

			if(empty($args['headers']['Content-Type'])){
				$args['headers']['Content-Type']	= 'application/json';
			}
		}

		$response	= wp_remote_post($url, $args);
	}elseif($method == 'FILE'){	// 上传文件
		$args['method']				= $args['body'] ? 'POST' : 'GET';
		$args['sslcertificates']	= $args['sslcertificates'] ?? ABSPATH.WPINC.'/certificates/ca-bundle.crt';
		$args['user-agent']			= $args['user-agent'] ?? 'WordPress';

		$wp_http_curl	= new WP_Http_Curl();
		$response		= $wp_http_curl->request($url, $args);
	}elseif($method == 'HEAD'){
		if($json_encode_required && is_array($args['body'])){
			$args['body']	= wpjam_json_encode($args['body']);
		}

		$response = wp_remote_head($url, $args);
	}else{
		if($json_encode_required && is_array($args['body'])){
			$args['body']	= wpjam_json_encode($args['body']);
		}

		$response = wp_remote_request($url, $args);
	}

	if(is_wp_error($response)){
		trigger_error($url."\n".$response->get_error_code().' : '.$response->get_error_message()."\n".var_export($args['body'],true));
		return $response;
	}

	if(!empty($response['response']['code']) && $response['response']['code'] != 200){
		return new WP_Error($response['response']['code'], '远程服务器错误：'.$response['response']['code'].' - '.$response['response']['message']);
	}

	if(!$args['blocking']){
		return true;
	}

	$headers	= $response['headers'];
	$response	= $response['body'];

	if(empty($headers['content-disposition']) || strpos($headers['content-disposition'], 'attachment;') === false){
		if(!$json_decode_required){
			$content_type	= $headers['content-type'] ?? '';
			$content_type	= is_array($content_type) ? implode(' ', $content_type) : $content_type;

			$json_decode_required	= $content_type && strpos($content_type, '/json');
		}	

		if($json_decode_required){
			if($args['stream']){
				$response	= file_get_contents($args['filename']);
			}

			if(empty($response)){
				trigger_error(var_export($response, true).var_export($headers, true));
			}else{
				$response	= wpjam_json_decode($response);

				if(is_wp_error($response)){
					return $response;
				}
			}
		}
	}

	$err_args	= wp_parse_args($err_args,  [
		'errcode'	=>'errcode',
		'errmsg'	=>'errmsg',
		'detail'	=>'detail',
		'success'	=>'0',
	]);

	if(isset($response[$err_args['errcode']]) && $response[$err_args['errcode']] != $err_args['success']){
		$errcode	= wpjam_array_pull($response, $err_args['errcode']);
		$errmsg		= wpjam_array_pull($response, $err_args['errmsg']);
		$detail		= wpjam_array_pull($response, $err_args['detail']);
		$detail		= is_null($detail) ? array_filter($response) : $detail;

		if(apply_filters('wpjam_http_response_error_debug', true, $errcode, $errmsg, $detail)){
			trigger_error($url."\n".$errcode.' : '.$errmsg."\n".($detail ? var_export($detail,true)."\n" : '').var_export($args['body'],true));
		}

		return new WP_Error($errcode, $errmsg, $detail);
	}

	if(wpjam_doing_debug()){
		echo $url;
		print_r($response);
	}

	return $response;
}

function wpjam_remote_request($url, $args=[], $err_args=[]){
	return wpjam_http_request($url, $args, $err_args);
}



function wpjam_get_by_meta($meta_type, ...$args){
	if(empty($args)){
		return [];
	}

	$meta_key	= $meta_value = null;

	if(is_array($args[0])){
		$args	= $args[0];

		if(isset($args['meta_key'])){
			$meta_key	= $args['meta_key'];
		}elseif(isset($args['key'])){
			$meta_key	= $args['key'];
		}

		if(isset($args['meta_value'])){
			$meta_value	= $args['meta_value'];
		}elseif(isset($args['value'])){
			$meta_value	= $args['value'];
		}
	}else{
		$meta_key	= $args[0];

		if(isset($args[1])){
			$meta_value	= $args[1];
		}
	}

	global $wpdb;

	$where	= [];

	if($meta_key){
		$where[]	= $wpdb->prepare('meta_key=%s', $meta_key);
	}

	if(!is_null($meta_value)){
		$where[]	= $wpdb->prepare('meta_value=%s', maybe_serialize($meta_value));
	}

	if($where){
		$table	= _get_meta_table($meta_type);
		$where	= implode(' AND ', $where);

		if($data = $wpdb->get_results("SELECT * FROM {$table} WHERE {$where}", ARRAY_A)){
			foreach($data as &$item){
				$item['meta_value']	= maybe_unserialize($item['meta_value']);
			}

			return $data;
		}
	}

	return [];
}

// wpjam_get_metadata($meta_type, $object_id, $meta_keys)
// wpjam_get_metadata($meta_type, $object_id, $meta_key, $default)
function wpjam_get_metadata($meta_type, $object_id, ...$args){
	if(!$object_id){
		return null;
	}

	if(is_array($args[0])){
		$data	= [];

		foreach($args[0] as $meta_key => $default){
			if(is_numeric($meta_key)){
				$meta_key	= $default;
				$default	= null;
			}

			$data[$meta_key]	= wpjam_get_metadata($meta_type, $object_id, $meta_key, $default);
		}

		return $data;
	}else{
		$meta_key	= $args[0];

		if(metadata_exists($meta_type, $object_id, $meta_key)){
			return get_metadata($meta_type, $object_id, $meta_key, true);
		}else{
			return $args[1] ?? null;
		}
	}
}

// wpjam_update_metadata($meta_type, $object_id, $data, $defults=null)
// wpjam_update_metadata($meta_type, $object_id, $meta_key, $meta_value, $default=null)
function wpjam_update_metadata($meta_type, $object_id, ...$args){
	if(is_array($args[0])){
		$data		= $args[0];
		$defaults	= (isset($args[1]) && is_array($args[1])) ? $args[1] : array_keys($data);

		foreach($defaults as $meta_key => $default){
			if(is_numeric($meta_key)){
				if(is_numeric($default)){
					continue;
				}

				$meta_key	= $default;
				$default	= null;
			}

			$meta_value	= $data[$meta_key] ?? false;

			wpjam_update_metadata($meta_type, $object_id, $meta_key, $meta_value, $default);
		}

		return true;
	}else{
		$meta_key	= $args[0];
		$meta_value	= $args[1];
		$default	= $args[2] ?? null;

		if(is_array($default) && is_array($meta_value)){
			if(array_diff_assoc($default, $meta_value)){
				return update_metadata($meta_type, $object_id, $meta_key, wp_slash($meta_value));
			}else{
				return delete_metadata($meta_type, $object_id, $meta_key);
			}
		}elseif(!is_null($meta_value) 
			&& $meta_value !== ''
			&& ((is_null($default) && $meta_value)
				|| (!is_null($default) && $meta_value != $default)
			)
		){
			return update_metadata($meta_type, $object_id, $meta_key, wp_slash($meta_value));
		}else{
			return delete_metadata($meta_type, $object_id, $meta_key);
		}
	}	
}



function wpjam_get_setting($option_name, $setting_name, $blog_id=0){
	$option_value	= is_string($option_name) ? wpjam_get_option($option_name, $blog_id) : $option_name;

	if($option_value && !is_wp_error($option_value) && is_array($option_value) && isset($option_value[$setting_name])){
		$value	= $option_value[$setting_name];

		if(is_wp_error($value)){
			return null;
		}elseif($value && is_string($value)){
			return  str_replace("\r\n", "\n", trim($value));
		}else{
			return $value;
		}
	}else{
		return null;
	}
}

function wpjam_update_setting($option_name, $setting_name, $setting_value, $blog_id=0){
	$option_value	= wpjam_get_option($option_name, $blog_id);

	$option_value[$setting_name]	= $setting_value;

	return wpjam_update_option($option_name, $option_value, $blog_id);
}

function wpjam_delete_setting($option_name, $setting_name, $blog_id=0){
	if($option_value = wpjam_get_option($option_name, $blog_id)){
		$option_value	= wpjam_array_except($option_value, $setting_name);
	}

	return wpjam_update_option($option_name, $option_value, $blog_id);
}

function wpjam_get_option($name, $blog_id=0){
	$value	= (is_multisite() && $blog_id) ? get_blog_option($blog_id, $name) : get_option($name);

	return wpjam_sanitize_option_value($value);
}

function wpjam_update_option($name, $value, $blog_id=0){
	$value	= wpjam_sanitize_option_value($value);

	return (is_multisite() && $blog_id) ? update_blog_option($blog_id, $name, $value) : update_option($name, $value);
}

function wpjam_get_site_option($name){
	return is_multisite() ? wpjam_sanitize_option_value(get_site_option($name, [])) : [];
}

function wpjam_update_site_option($name, $value){
	return is_multisite() ? update_site_option($name, wpjam_sanitize_option_value($value)) : true;
}

function wpjam_sanitize_option_value($value){
	return (is_wp_error($value) || empty($value)) ? [] : $value;
}



// WP_Query 缓存
function wpjam_query($args=[]){
	return new WP_Query(wp_parse_args($args, [
		'no_found_rows'			=> true,
		'ignore_sticky_posts'	=> true,
		'cache_it'				=> true
	]));
}

function wpjam_parse_query_vars($query_vars){
	$tax_query	= $query_vars['tax_query'] ?? [];

	$taxonomies	= array_values(get_taxonomies(['_builtin'=>false]));

	foreach(array_merge($taxonomies, ['category', 'post_tag']) as $taxonomy){
		$query_key	= wpjam_get_taxonomy_query_key($taxonomy);

		if($term_id	= wpjam_array_pull($query_vars, $query_key)){
			if($term_id == -1){
				$tax_query[]	= ['taxonomy'=>$taxonomy,	'field'=>'term_id',	'operator'=>'NOT EXISTS'];
			}else{
				if(in_array($taxonomy, ['category', 'post_tag'])){
					$query_vars[$query_key]	= $term_id;
				}else{
					$tax_query[]	= ['taxonomy'=>$taxonomy,	'field'=>'term_id',	'terms'=>[$term_id]];
				}
			}
		}
	}

	if(!empty($query_vars['taxonomy']) && empty($query_vars['term'])){
		if($term_id	= wpjam_array_pull($query_vars, 'term_id')){
			if(is_numeric($term_id)){
				$tax_query[]	= ['taxonomy'=>wpjam_array_pull($query_vars, 'taxonomy'),	'field'=>'term_id',	'terms'=>[$term_id]];
			}else{
				$query_vars['term']	= $term_id;
			}
		}
	}

	if($tax_query){
		$query_vars['tax_query']	= $tax_query;
	}

	$date_query	= $query_vars['date_query'] ?? [];

	if($cursor = wpjam_array_pull($query_vars, 'cursor')){
		$date_query[]	= ['before' => get_date_from_gmt(date('Y-m-d H:i:s', $cursor))];
	}

	if($since = wpjam_array_pull($query_vars, 'since')){
		$date_query[]	= ['after' => get_date_from_gmt(date('Y-m-d H:i:s', $since))];
	}

	if($date_query){
		$query_vars['date_query']	= $date_query;
	}

	return $query_vars;
}

function wpjam_parse_query($query, $args=[], $parse_for_json=true){
	if($parse_for_json){
		return WPJAM_Post::parse_query($query, $args);
	}else{
		return wpjam_render_query($query, $args);
	}
}

function wpjam_render_query($query, $args=[]){
	return WPJAM_Post::render_query($query, $args);
}

function wpjam_validate_post($post_id, $post_type=''){
	$object	= WPJAM_Post::get_instance($post_id, $post_type);

	return is_wp_error($object) ? [] : $object->post;
}

function wpjam_get_posts($post_ids, $args=[]){
	$posts = WPJAM_Post::get_by_ids($post_ids, $args);

	return $posts ? array_values($posts) : [];
}

function wpjam_get_post_id_field($post_type='post', $args=[]){
	return WPJAM_Post::get_id_field($post_type, $args);
}

function wpjam_get_post($post, $args=[]){
	$object	= WPJAM_Post::get_instance($post);

	return is_wp_error($object) ? [] : $object->parse_for_json($args);
}

function wpjam_get_post_views($post=null, $addon=true){
	$object	= WPJAM_Post::get_instance($post);

	return is_wp_error($object) ? 0 : $object->get_views($addon);
}

function wpjam_update_post_views($post=null){
	$object	= WPJAM_Post::get_instance($post);

	return is_wp_error($object) ? null : $object->view();
}

function wpjam_get_post_excerpt($post=null, $length=0, $more=null){
	$object	= WPJAM_Post::get_instance($post);

	if(is_wp_error($object)){
		return '';
	}

	if($object->excerpt){
		return wp_strip_all_tags($object->excerpt, true);
	}
	
	$excerpt	= $object->get_content(true);
	$excerpt	= strip_shortcodes($excerpt);
	$excerpt	= excerpt_remove_blocks($excerpt);
	$excerpt	= wp_strip_all_tags($excerpt, true);

	// remove_filter('the_content', 'wp_filter_content_tags');
	// $excerpt	= $object->filter_content($excerpt);
	// add_filter('the_content', 'wp_filter_content_tags');	

	$length	= $length ?: apply_filters('excerpt_length', 200);
	$more	= $more ?? apply_filters('excerpt_more', ' &hellip;');

	return mb_strimwidth($excerpt, 0, $length, '', 'utf-8').$more;
}

function wpjam_get_post_content($post=null, $raw=false){
	$object	= WPJAM_Post::get_instance($post);

	return is_wp_error($object) ? '' : $object->get_content($raw);
}

function wpjam_get_post_thumbnail_url($post=null, $size='full', $crop=1){
	$object	= WPJAM_Post::get_instance($post);

	return is_wp_error($object) ? '' : $object->get_thumbnail_url($size, $crop);
}

function wpjam_get_post_first_image_url($post=null, $size='full'){
	$object	= WPJAM_Post::get_instance($post);

	return is_wp_error($object) ? '' : $object->get_first_image_url($size);
}

function wpjam_related_posts($args=[]){
	echo wpjam_get_related_posts(null, $args, false);
}

function wpjam_get_related_posts($post=null, $args=[], $parse_for_json=false){
	$object	= WPJAM_Post::get_instance($post);

	if(is_wp_error($object)){
		return '';
	}

	$number	= wpjam_array_pull($args, 'number') ?: 5;
	$query	= $object->get_related_query($number);

	return wpjam_parse_query($query, $args, $parse_for_json);
}

function wpjam_get_new_posts($args=[], $parse_for_json=false){
	$query	= wpjam_query([
		'post_status'		=> 'publish',
		'posts_per_page'	=> wpjam_array_pull($args, 'number') ?: 5, 
		'post_type'			=> wpjam_array_pull($args, 'post_type') ?: 'post', 
		'orderby'			=> wpjam_array_pull($args, 'orderby') ?: 'date', 
	]);

	return wpjam_parse_query($query, $args, $parse_for_json);
}

function wpjam_get_top_viewd_posts($args=[], $parse_for_json=false){
	$days	= wpjam_array_pull($args, 'days');
	$query	= wpjam_query([
		'posts_per_page'	=> wpjam_array_pull($args, 'number') ?: 5,
		'post_type'			=> wpjam_array_pull($args, 'post_type') ?: 'post', 
		'post_status'		=> 'publish',
		'orderby'			=> 'meta_value_num', 
		'meta_key'			=> 'views', 
		'date_query'		=> $days ? [[
			'column'	=> wpjam_array_pull($args, 'column') ?: 'post_date_gmt',
			'after'		=> date('Y-m-d', current_time('timestamp')-DAY_IN_SECONDS*$days)
		]] : [] 
	]);

	return wpjam_parse_query($query, $args, $parse_for_json);
}

function wpjam_get_permastruct($name){
	return $GLOBALS['wp_rewrite']->extra_permastructs[$name]['struct'] ?? '';
}


function wpjam_get_taxonomy_query_key($taxonomy){
	$query_keys	= ['category'=>'cat', 'post_tag'=>'tag_id'];

	return $query_keys[$taxonomy] ?? $taxonomy.'_id';
}

function wpjam_get_terms($args, $max_depth=null){
	return WPJAM_Term::get_terms($args, $max_depth);
}

function wpjam_get_term_id_field($taxonomy='category', $args=[]){
	return WPJAM_Term::get_id_field($taxonomy, $args);
}

function wpjam_get_related_object_ids($tt_ids, $number, $page=1){
	return WPJAM_Term::get_related_object_ids($tt_ids, $number, $page);
}

function wpjam_get_term($term, $taxonomy=''){
	$object	= WPJAM_Term::get_instance($term);

	if(is_wp_error($object)){
		return $object;
	}

	if($taxonomy && $taxonomy != 'any' && $taxonomy != $object->taxonomy){
		return new WP_Error('invalid_taxonomy', '无效的分类模式');
	}

	return $object->parse_for_json();
}

function wpjam_get_term_thumbnail_url($term=null, $size='full', $crop=1){
	$object	= WPJAM_Term::get_instance($term);

	return is_wp_error($object) ? '' : $object->get_thumbnail_url($size, $crop);
}

function wpjam_get_term_level($term){
	$object	= WPJAM_Term::get_instance($term);

	return is_wp_error($object) ? '' : $object->get_level();
}


function wpjam_parse_show_if($show_if, &$class=[]){
	$object	= new WPJAM_Show_IF($show_if);

	if($show_if = $object->show_if){
		$class[]	= 'show-if-'.$object->key;
	}

	return $show_if;
}

function wpjam_show_if($item, $show_if){
	return (new WPJAM_Show_IF($show_if))->validate($item);
}

function wpjam_compare($value, $operator, $compare_value){
	return WPJAM_Show_IF::compare($value, $operator, $compare_value);
}

function wpjam_generate_random_string($length){
	return WPJAM_Crypt::generate_random_string($length);
}

function wpjam_fields($fields, $args=[]){
	return (new WPJAM_Fields($fields))->callback($args);
}

function wpjam_validate_fields_value($fields, $values=null){
	if(is_wp_error($fields)){
		return $fields;
	}

	return (new WPJAM_Fields($fields))->validate($values);
}

function wpjam_validate_field_value($field, $value){
	return (new WPJAM_Field($field))->validate($value);
}

function wpjam_parse_field_value($field, $args=[]){
	return (new WPJAM_Field($field))->parse_value($args);
}

function wpjam_get_field_value($field, $args=[]){
	return wpjam_parse_field_value($field, $args);
}

function wpjam_get_field_html($field){
	return wpjam_render_field($field);
}

function wpjam_render_field($field){
	return (new WPJAM_Field($field))->render();
}


function wpjam_get_filter_name($name='', $type=''){
	$filter	= str_replace('-', '_', $name);
	$filter	= str_replace('wpjam_', '', $filter);

	return 'wpjam_'.$filter.'_'.$type;
}

function wpjam_get_authors($args=[], $return='users'){
	if(version_compare($GLOBALS['wp_version'], '5.9', '<')){
		$args['who']		= 'authors';
	}else{
		$args['capability']	= ['edit_posts'];
	}

	return $return == 'args' ? $args : get_users($args);
}

function wpjam_is_login(){
	if(preg_match('#(wp-login\.php)([?/].*?)?$#i', $_SERVER['PHP_SELF'])){
		return true;
	}

	return false;
}

function wpjam_get_filesystem(){
	if(empty($GLOBALS['wp_filesystem'])){
		if(!function_exists('WP_Filesystem')){
			require_once(ABSPATH.'wp-admin/includes/file.php');
		}

		WP_Filesystem();
	}

	return $GLOBALS['wp_filesystem'];		
}

function wpjam_parse_shortcode_attr($str, $tagnames=null){
	$pattern = get_shortcode_regex([$tagnames]);

	if(preg_match("/$pattern/", $str, $m)){
		return shortcode_parse_atts($m[3]);
	}else{
		return [];
	}
}

