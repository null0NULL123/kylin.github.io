<?php
function wpjam_get_current_page_url(){
	return set_url_scheme('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
}

function wpjam_human_time_diff($from, $to=0){
	$to	= $to ?: time();

	if($to - $from > 0){
		return sprintf(__('%s ago'), human_time_diff($from, $to));
	}else{
		return sprintf(__('%s from now'), human_time_diff($to, $from));
	}
}

function wpjam_attribute_string($attributes, $type=''){
	$string	= '';

	foreach($attributes as $key => $value){
		if($value || $value === 0){
			if($type == 'data'){
				if(is_array($value)){
					if($key == 'data'){
						$value	= http_build_query($value);
					}else{
						$value	= wpjam_json_encode($value);
					}
				}else{
					$value	= esc_attr($value);
				}

				$string	.= ' data-'.$key.'=\''.$value.'\'';
			}else{
				if(is_array($value)){
					if($key == 'data'){
						$string	.= ' '.wpjam_attribute_string($value, 'data');
					}elseif($key == 'class'){
						$string	.= 'class="'.implode(' ', array_filter($value)).'"';
					}
				}else{
					$string	.= ' '.$key.'="'.esc_attr($value).'"';
				}
			}
		}
	}

	return $string;
}

function wpjam_data_attribute_string($attributes){
	return wpjam_attribute_string($attributes, 'data');
}

function wpjam_zh_urlencode($url){
	return preg_replace_callback('/[\x{4e00}-\x{9fa5}]+/u', function($matches){ return urlencode($matches[0]); }, $url);
}

function wpjam_get_video_mp4($id_or_url){
	if(filter_var($id_or_url, FILTER_VALIDATE_URL)){ 
		if(preg_match('#http://www.miaopai.com/show/(.*?).htm#i',$id_or_url, $matches)){
			return 'http://gslb.miaopai.com/stream/'.esc_attr($matches[1]).'.mp4';
		}elseif(preg_match('#https://v.qq.com/x/page/(.*?).html#i',$id_or_url, $matches)){
			return wpjam_get_qqv_mp4($matches[1]);
		}elseif(preg_match('#https://v.qq.com/x/cover/.*/(.*?).html#i',$id_or_url, $matches)){
			return wpjam_get_qqv_mp4($matches[1]);
		}else{
			return wpjam_zh_urlencode($id_or_url);
		}
	}else{
		return wpjam_get_qqv_mp4($id_or_url);
	}
}

function wpjam_get_qqv_mp4($vid){
	if(strlen($vid) > 20){
		return new WP_Error('invalid_qqv_vid', '非法的腾讯视频 ID');
	}

	$mp4 = wp_cache_get($vid, 'qqv_mp4');

	if($mp4 === false){
		$response	= wpjam_remote_request(
			'http://vv.video.qq.com/getinfo?otype=json&platform=11001&vid='.$vid,
			['timeout'=>4,	'json_decode_required'=>false]
		);

		if(is_wp_error($response)){
			return $response;
		}

		$response	= trim(substr($response, strpos($response, '{')),';');
		$response	= wpjam_json_decode($response);

		if(is_wp_error($response)){
			return $response;
		}

		if(empty($response['vl'])){
			return new WP_Error('illegal_qqv', '该腾讯视频不存在或者为收费视频！');
		}

		$u		= $response['vl']['vi'][0];
		$p0		= $u['ul']['ui'][0]['url'];
		$p1		= $u['fn'];
		$p2		= $u['fvkey'];
		$mp4	= $p0.$p1.'?vkey='.$p2;

		wp_cache_set($vid, $mp4, 'qqv_mp4', HOUR_IN_SECONDS*6);
	}

	return $mp4;
}

function wpjam_get_qqv_id($id_or_url){
	if(filter_var($id_or_url, FILTER_VALIDATE_URL)){
		foreach([
			'#https://v.qq.com/x/page/(.*?).html#i',
			'#https://v.qq.com/x/cover/.*/(.*?).html#i'
		] as $pattern){
			if(preg_match($pattern,$id_or_url, $matches)){
				return $matches[1];
			}
		}

		return '';
	}else{
		return $id_or_url;
	}
}

function wpjam_restore_attachment_file($id){
	$file = get_attached_file($id, true);

	if($file && !file_exists($file)){
		$dir	= dirname($file);

		if(!is_dir($dir)){
			mkdir($dir, 0777, true);
		}

		$image	= wpjam_cdn_host_replace(wp_get_attachment_url($id));
		$result	= wpjam_remote_request($image, ['stream'=>true, 'filename'=>$file]);

		if(is_wp_error($result)){
			return $result;
		}
	}

	return true;
}

function wpjam_upload_bits($bits, $name, $post_id=0){
	$upload	= wp_upload_bits($name, null, $bits);

	if(!empty($upload['error'])){
		return new WP_Error('upload_bits_error', $upload['error']);
	}

	$id	= wp_insert_attachment([
		'post_title'		=> explode('.', $name)[0],
		'post_content'		=> '',
		'post_type'			=> 'attachment',
		'post_parent'		=> $post_id,
		'post_mime_type'	=> $upload['type'],
		'guid'				=> $upload['url'],
	], $upload['file'], $post_id);

	if(!is_wp_error($id)){
		wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
	}

	return $id;
}

function wpjam_download_image($img_url, ...$args){
	if(isset($args[0]) && is_array($args[0])){
		$name	= $args[0]['name'] ?? '';
		$media	= $args[0]['media'] ?? false;

		if($media){
			$post_id	= $args[0]['post_id'] ?? 0;
			$return		= $args[0]['return'] ?? 'id';
		}else{
			$return		= $args[0]['return'] ?? 'file';
		}
	}else{
		$name	= $args[0] ?? '';
		$media	= $args[1] ?? false;

		if($media){
			$post_id	= $args[2] ?? 0;
			$return		= $args[3] ?? 'id';
		}else{
			$return		= $args[2] ?? 'file';
		}
	}

	if($metas = wpjam_get_by_meta('post', 'source_url', $img_url)){
		$id		= current($metas)['post_id'];
		$post	= get_post($id);

		if($post && $post->post_type == 'attachment'){
			if($return == 'id'){
				return $id;
			}elseif($return == 'file'){
				return get_attached_file($id);
			}elseif($return == 'url'){
				return wp_get_attachment_url($id);
			}
		}
	}

	$tmp_file	= download_url($img_url);

	if(is_wp_error($tmp_file)){
		return $tmp_file;
	}

	if(empty($name)){
		$type	= wp_get_image_mime($tmp_file);
		$name	= md5($img_url).'.'.(explode('/', $type)[1]);
	}

	$file_array	= ['name'=>$name,	'tmp_name'=>$tmp_file];

	if($media){
		$id		= media_handle_sideload($file_array, $post_id);

		if(is_wp_error($id)){
			@unlink($tmp_file);
		}else{
			update_post_meta($id, 'source_url', $img_url);
		}

		if($return == 'id'){
			return $id;
		}elseif($return == 'file'){
			return get_attached_file($id);
		}elseif($return == 'url'){
			return wp_get_attachment_url($id);
		}
	}else{
		$file	= wp_handle_sideload($file_array, ['test_form'=>false]);

		if(isset($file['error'])){
			@unlink($tmp_file);
			return new WP_Error('upload_error', $file['error']);
		}

		return $file[$return] ?? $file;
	}
}

function wpjam_fetch_external_images(&$img_urls, ...$args){
	if(isset($args[0]) && is_array($args[0])){
		$args	= wp_parse_args($args[0], ['post_id'=>0, 'media'=>true, 'return'=>'url']);
	}else{
		$args	= ['post_id'=>($args[0] ?? 0), 'media'=>($args[1] ?? true), 'return'=>'url'];
	}

	$search	= $replace	= [];
	
	foreach($img_urls as $i => $img_url){
		if($img_url && wpjam_is_external_image($img_url, 'fetch')){
			$download	= wpjam_download_image($img_url, $args);

			if(!is_wp_error($download)){
				$search[]	= $img_url;
				$replace[]	= $download;
			}	
		}
	}

	$img_urls	= $search;

	return $replace;
}

function wpjam_is_image($img_url){
	$ext_types	= wp_get_ext_types();
	$img_exts	= $ext_types['image'];
	$img_parts	= explode('?', $img_url);

	return preg_match('/\.('.implode('|', $img_exts).')$/i', $img_parts[0]);
}

function wpjam_is_external_image($img_url, $scene=''){
	$site_url	= str_replace(['http://', 'https://'], '//', site_url());
	$status		= strpos($img_url, $site_url) === false;	

	return apply_filters('wpjam_is_external_image', $status, $img_url, $scene);
}

function wpjam_unserialize(&$serialized){
	if($serialized){
		$fixed_serialized	= preg_replace_callback('!s:(\d+):"(.*?)";!', function($m) {
			return 's:'.strlen($m[2]).':"'.$m[2].'";';
		}, $serialized);

		$unserialized		= unserialize($fixed_serialized);

		if($unserialized && is_array($unserialized)){
			$serialized	= $fixed_serialized;

			return $unserialized;
		}
	}

	return false;
}

// 去掉非 utf8mb4 字符
function wpjam_strip_invalid_text($text, $charset='utf8mb4'){
	if($text){
		$regex	= '/
			(
				(?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
				|   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx';

		if($charset === 'utf8mb3' || $charset === 'utf8mb4'){
			$regex	.= '
			|   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
				|   [\xE1-\xEC][\x80-\xBF]{2}
				|   \xED[\x80-\x9F][\x80-\xBF]
				|   [\xEE-\xEF][\x80-\xBF]{2}';
		}

		if($charset === 'utf8mb4'){
			$regex	.= '
				|    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
				|    [\xF1-\xF3][\x80-\xBF]{3}
				|    \xF4[\x80-\x8F][\x80-\xBF]{2}';
		}

		$regex		.= '
			){1,40}                  # ...one or more times
			)
			| .                      # anything else
			/x';

		return preg_replace($regex, '$1', $text);
	}

	return $text;
}

// 去掉 4字节 字符
function wpjam_strip_4_byte_chars($chars){
	if($chars){
		return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $chars);
		// return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $chars);
	}

	return $chars;
}

// 去掉控制字符
function wpjam_strip_control_characters($chars){
	if($chars){
		// 移除除了 line feeds 和 carriage returns 所有控制字符
		return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F]/u', '', $chars);
		// return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $chars);
	}

	return $chars;
}

//获取纯文本
function wpjam_get_plain_text($text){
	if($text){
		$text	= wp_strip_all_tags($text);
		$text	= str_replace(['"', '\''], '', $text);
		$text	= str_replace(["\r\n", "\n", "  "], ' ', $text);

		return trim($text);
	}

	return $text;
}

//获取第一段
function wpjam_get_first_p($text){
	if($text){
		$text = explode("\n", trim(strip_tags($text))); 
		$text = trim($text[0]); 
	}

	return $text;
}

//中文截取方式
function wpjam_mb_strimwidth($text, $start=0, $width=40, $trimmarker='...', $encoding='utf-8'){
	if($text){
		return mb_strimwidth(wpjam_get_plain_text($text), $start, $width, $trimmarker, $encoding);
	}

	return $text;
}

// 检查非法字符
function wpjam_blacklist_check($text, $name='内容'){
	if($text){
		$pre	= apply_filters('wpjam_pre_blacklist_check', null, $text, $name);

		if(!is_null($pre)){
			return $pre;
		}

		$moderation_keys	= trim(get_option('moderation_keys'));
		$disallowed_keys	= trim(get_option('disallowed_keys'));

		$words = explode("\n", $moderation_keys ."\n".$disallowed_keys);

		foreach ((array)$words as $word){
			$word = trim($word);

			// Skip empty lines
			if(empty($word)){
				continue;
			}

			// Do some escaping magic so that '#' chars in the
			// spam words don't break things:
			$word	= preg_quote($word, '#');
			if ( preg_match("#$word#i", $text) ) {
				return true;
			}
		}
	}

	return false;
}

function wpjam_unicode_decode($text){
	if($text){
		// [U+D800 - U+DBFF][U+DC00 - U+DFFF]|[U+0000 - U+FFFF]
		return preg_replace_callback('/(\\\\u[0-9a-fA-F]{4})+/i', function($matches){
			return json_decode('"'.$matches[0].'"') ?: $matches[0];
			// return mb_convert_encoding(pack("H*", $matches[1]), 'UTF-8', 'UCS-2BE');
		}, $text);
	}

	return $text;
}

function wpjam_hex2rgba($color, $opacity=null){
	if($color[0] == '#'){
		$color	= substr($color, 1);
	}

	if(strlen($color) == 6){
		$hex	= [$color[0].$color[1], $color[2].$color[3], $color[4].$color[5]];
	}elseif(strlen($color) == 3) {
		$hex	= [$color[0].$color[0], $color[1].$color[1], $color[2].$color[2]];
	}else{
		return $color;
	}

	$rgb 	=  array_map('hexdec', $hex);

	if(isset($opacity)){
		$opacity	= $opacity > 1 ? 1.0 : $opacity;
		
		return 'rgba('.implode(",",$rgb).','.$opacity.')';
	}else{
		return 'rgb('.implode(",",$rgb).')';
	}
}

function wpjam_get_ipdata($ip=''){
	return WPJAM_Var::parse_ip($ip);
}

function wpjam_parse_ip($ip=''){
	return WPJAM_Var::parse_ip($ip);
}

function wpjam_get_ip(){
	return WPJAM_Var::get_ip();
}

function wpjam_get_user_agent(){
	return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

function wpjam_get_ua(){
	return wpjam_get_user_agent();
}

function wpjam_parse_user_agent($user_agent='',$referer=''){
	return WPJAM_Var::parse_user_agent($user_agent, $referer);
}

function wpjam_is_webp_supported(){
	return $GLOBALS['is_chrome'] || is_android() || (is_ios() && wpjam_get_os_version() >= 14);
}

function wpjam_get_device(){
	return WPJAM_Var::get_instance()->device;
}

function is_ipad(){
	return wpjam_get_device() == 'iPad';
}

function is_iphone(){
	return wpjam_get_device() == 'iPone';
}

function wpjam_get_os(){
	return WPJAM_Var::get_instance()->os;
}

function wpjam_get_os_version(){
	return WPJAM_Var::get_instance()->os_version;
}

function is_ios(){
	return wpjam_get_os() == 'iOS';
}

function is_mac(){
	return is_macintosh();
}

function is_macintosh(){
	return wpjam_get_os() == 'Macintosh';
}

function is_android(){
	return wpjam_get_os() == 'Android';
}

function wpjam_get_browser(){
	return WPJAM_Var::get_instance()->browser;
}

function wpjam_get_browser_version(){
	return WPJAM_Var::get_instance()->browser_version;
}

function wpjam_get_app(){
	return WPJAM_Var::get_instance()->app;
}

function wpjam_get_app_version(){
	return WPJAM_Var::get_instance()->app_version;
}

// 判断当前用户操作是否在微信内置浏览器中
function is_weixin(){ 
	if(isset($_GET['weixin_appid'])){
		return true;
	}

	return wpjam_get_app() == 'weixin';
}

// 判断当前用户操作是否在微信小程序中
function is_weapp(){ 
	if(isset($_GET['appid'])){
		return true;
	}

	return wpjam_get_app() == 'weapp';
}

// 判断当前用户操作是否在头条小程序中
function is_bytedance(){ 
	if(isset($_GET['bytedance_appid'])){
		return true;
	}

	return wpjam_get_app() == 'bytedance';
}




function wpjam_doing_debug(){
	if(isset($_GET['debug'])){
		return $_GET['debug'] ? sanitize_key($_GET['debug']) : true;
	}else{
		return false;
	}
}

// 打印
function wpjam_print_r($value){
	$capability	= is_multisite() ? 'manage_site' : 'manage_options';

	if(current_user_can($capability)){
		echo '<pre>';
		print_r($value);
		echo '</pre>'."\n";
	}
}

function wpjam_var_dump($value){
	$capability	= is_multisite() ? 'manage_site' : 'manage_options';
	if(current_user_can($capability)){
		echo '<pre>';
		var_dump($value);
		echo '</pre>'."\n";
	}
}

function wpjam_pagenavi($total=0, $echo=true){
	$args = [
		'prev_text'	=> '&laquo;',
		'next_text'	=> '&raquo;'
	];

	if(!empty($total)){
		$args['total']	= $total;
	}

	if($echo){
		echo '<div class="pagenavi">'.paginate_links($args).'</div>'; 
	}else{
		return '<div class="pagenavi">'.paginate_links($args).'</div>'; 
	}
}

// 判断一个数组是关联数组，还是顺序数组
function wpjam_is_assoc_array(array $arr){
	if ([] === $arr) return false;
	return array_keys($arr) !== range(0, count($arr) - 1);
}

function wpjam_array_push(&$array, $data=null, $key=false){
	$data	= (array)$data;

	$offset	= $key !== false ? array_search($key, array_keys($array), true) : false;

	if($offset !== false){
		$array = array_merge(array_slice($array, 0, $offset), $data, array_slice($array, $offset));
	}else{
		$array = array_merge($array, $data);
	}
}

function wpjam_array_first($array, $callback=null){
	if(empty($array)){
		return null;
	}

	if($callback && is_callable($callback)){
		foreach($array as $key => $value){
			if(call_user_func($callback, $value, $key)){
				return $value;
			}
		}
	}else{
		return current($array);
	}
}

function wpjam_array_pull(&$array, $key, $default=null){
	$keys	= is_array($key) ? $key : [$key];

	foreach($keys as $key){
		if(isset($array[$key])){
			$value	= $array[$key];

			unset($array[$key]);
			
			return $value;
		}
	}

	return $default;
}

function wpjam_array_get($array, $key, $default=null){
	$keys	= is_array($key) ? $key : [$key];

	foreach($keys as $key){
		if(isset($array[$key])){
			return $array[$key];
		}
	}

	return $default;
}

function wpjam_array_except($array, $keys){
	foreach((array)$keys as $key){
		unset($array[$key]);
	}

	return $array;
}

function wpjam_array_filter($array, $callback, $mode=0){
	$return	= [];

	foreach($array as $key=>$value){
		if(is_array($value)){
			$value	= wpjam_array_filter($value, $callback, $mode);
		}

		if($mode == ARRAY_FILTER_USE_KEY){
			$result	= call_user_func($callback, $key);
		}elseif($mode == ARRAY_FILTER_USE_BOTH){
			$result	= call_user_func($callback, $value, $key);
		}else{
			$result	= call_user_func($callback, $value);
		}

		if($result){
			$return[$key]	= $value;	
		}
	}

	return $return;
}

function wpjam_array_merge($arr1, $arr2){
	if(wp_is_numeric_array($arr1) && wp_is_numeric_array($arr2)){
		return array_merge($arr1, $arr2);
	}

	foreach($arr2 as $key => $value){
		if(is_array($value) && isset($arr1[$key]) && is_array($arr1[$key])){
			if(wp_is_numeric_array($value) && wp_is_numeric_array($arr1[$key])){
				$arr1[$key]	= array_merge($arr1[$key], $value);
			}elseif(wp_is_numeric_array($value) || wp_is_numeric_array($arr1[$key])){
				$arr1[$key]	= $value;
			}else{
				$arr1[$key]	= wpjam_array_merge($arr1[$key], $value);
			}
		}else{
			$arr1[$key]	= $value;
		}
	}

	return $arr1;
}


function wpjam_list_sort($list, $orderby='order', $order='DESC', $preserve_keys=true){
	$index	= 0;
	$scores	= [];

	foreach($list as $key => $item){
		$value	= is_object($item) ? ($item->$orderby ?? 10) : ($item[$orderby] ?? 10);
		$index 	= $index+1;

		$scores[$key]	= [$orderby=>$value, 'index'=>$index];
	}

	$scores	= wp_list_sort($scores, [$orderby=>$order, 'index'=>'ASC'], '', $preserve_keys);

	return wp_array_slice_assoc($list, array_keys($scores));
}

function wpjam_sort_items($items, $orderby='order', $order='DESC'){
	return wpjam_list_sort($items, $orderby, $order);
}

function wpjam_list_filter($list, $args=[], $operator='AND'){	// 增强 wp_list_filter ，支持 in_array 判断
	if(empty($args)){
		return $list;
	}

	$operator	= strtoupper($operator);

	if(!in_array($operator, ['AND', 'OR', 'NOT'], true)){
		return [];
	}

	$count		= count($args);
	$filtered	= [];

	foreach($list as $key => $item){
		$matched	= 0;

		foreach($args as $m_key => $m_value){
			if(is_array($item)){
				if(array_key_exists($m_key, $item)){
					if((is_array($m_value) && in_array($item[$m_key], $m_value, true))
						|| (!is_array($m_value) && $m_value == $item[$m_key])
					){
						$matched++;
					}
				}
			}elseif(is_object($item)){
				if(isset($item->{$m_key})){
					if((is_array($m_value) && in_array($item->{$m_key}, $m_value, true))
						|| (!is_array($m_value) && $m_value == $item->{$m_key})
					){
						$matched++;
					}
				}
			}
		}

		if(('AND' === $operator && $matched === $count)
			|| ('OR' === $operator && $matched > 0)
			|| ('NOT' === $operator && 0 === $matched)
		){
			$filtered[$key]	= $item;
		}
	}

	return $filtered;
}

function wpjam_list_flatten($list, $depth=0, $fields=[]){
	$flat	= [];

	$name		= $fields['name'] ?? 'name'; 
	$children	= $fields['children'] ?? 'children'; 

	foreach($list as $item){
		$item[$name]	= str_repeat('&emsp;', $depth).$item[$name];
		$flat[]			= $item;

		if(!empty($item[$children])){
			$flat	= array_merge($flat, wpjam_list_flatten($item[$children], $depth+1));
		}
	}

	return $flat;
}

function wpjam_localize_script($handle, $object_name, $l10n ){
	wp_localize_script($handle, $object_name, ['l10n_print_after' => $object_name.' = ' . wpjam_json_encode($l10n)]);
}

function wpjam_is_mobile_number($number){
	return preg_match('/^0{0,1}(1[3,5,8][0-9]|14[5,7]|166|17[0,1,3,6,7,8]|19[8,9])[0-9]{8}$/', $number);
}

function wpjam_set_cookie($key, $value, $expire=DAY_IN_SECONDS){
	$expire	= $expire < time() ? $expire+time() : $expire;

	setcookie($key, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

	if(COOKIEPATH != SITECOOKIEPATH){
		setcookie($key, $value, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}
}

function wpjam_clear_cookie($key){
	setcookie($key, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
	setcookie($key, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
}
