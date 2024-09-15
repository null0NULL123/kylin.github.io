<?php
function wpjam_register_route_module($name, $args){
	WPJAM_Route_Module::register($name, $args);
}

function wpjam_get_route_module($name){
	return WPJAM_Route_Module::get($name);
}

function wpjam_is_module($module='', $action=''){
	$_module	= $GLOBALS['wp']->query_vars['module'] ?? '';
	$_action	= $GLOBALS['wp']->query_vars['action'] ?? '';

	if($module && $action){
		return $module == $_module && $action == $_action;
	}elseif($module){
		return $module == $_module;
	}elseif($_module){
		return true;
	}else{
		return false;
	}
}


function wpjam_register_json($name, $args=[]){
	if(WPJAM_JSON::get($name)){
		trigger_error('API 「'.$name.'」已经注册。');
	}

	return WPJAM_JSON::register($name, $args);
}

function wpjam_register_api($name, $args=[]){
	return wpjam_register_json($name, $args);
}

function wpjam_get_json_object($name){
	return WPJAM_JSON::get($name);
}

function wpjam_get_api($name){
	return wpjam_get_json_object($name);
}

function wpjam_is_json_request(){
	if(get_option('permalink_structure')){
		if(preg_match("/\/api\/(.*)\.json/", $_SERVER['REQUEST_URI'])){ 
			return true;
		}
	}else{
		if(isset($_GET['module']) && $_GET['module'] == 'json'){
			return true;
		}
	}

	return false;
}

function wpjam_get_json(){
	return wpjam_get_current_json();
}

function wpjam_get_current_json(){
	return WPJAM_JSON::get_current();
}


function wpjam_get_current_user($required=false){
	$current_user	= apply_filters('wpjam_current_user', null);

	if($required){
		if(is_null($current_user)){
			return new WP_Error('bad_authentication', '无权限');
		}
	}else{
		if(is_wp_error($current_user)){
			return null;
		}
	}

	return $current_user;
}

function wpjam_get_current_commenter(){
	if(get_option('comment_registration')){
		return new WP_Error('logged_in_required', '只支持登录用户操作');
	}

	$commenter	= wp_get_current_commenter();

	if(empty($commenter['comment_author_email'])){
		return new WP_Error('bad_authentication', '无权限');
	}

	return $commenter;
}

// 注册后台选项
function wpjam_register_option($name, $args=[]){
	$args	= is_callable($args) ? call_user_func($args, $name) : $args;
	$args	= apply_filters('wpjam_register_option_args', $args, $name);

	if(!isset($args['sections']) && !isset($args['fields'])){
		$args	= ['sections'=>$args];
	}

	return WPJAM_Option_Setting::register($name, $args);
}

function wpjam_unregister_option($name){
	WPJAM_Option_Setting::unregister($name);
}


// 添加后台菜单
function wpjam_add_menu_page($menu_slug, $args=[]){
	if(is_admin()){
		WPJAM_Menu_Page::add($menu_slug, $args);
	}else{
		if(isset($args['function']) && $args['function'] == 'option'){
			if(!empty($args['sections']) || !empty($args['fields'])){
				$option_name	= $args['option_name'] ?? $menu_slug;

				wpjam_register_option($option_name, $args);
			}
		}
	}
}

// 注册 Meta 类型
function wpjam_register_meta_type($name, $args=[]){
	$object	= WPJAM_Meta_Type::get($name);
	$object	= $object ?: WPJAM_Meta_Type::register($name, $args);

	$table_name	= sanitize_key($name).'meta';

	$GLOBALS['wpdb']->$table_name = $object->get_table();

	return $object;
}

function wpjam_get_meta_type_object($name){
	return WPJAM_Meta_Type::get($name);
}


// 注册文章类型
function wpjam_register_post_type($name, $args=[]){
	return WPJAM_Post_Type::register($name, $args);
}


// 注册文章选项
function wpjam_register_post_option($meta_box, $args=[]){
	if(WPJAM_Post_Option::get($meta_box)){
		trigger_error('Post Option 「'.$meta_box.'」已经注册。');
	}

	return WPJAM_Post_Option::register($meta_box, $args);
}

function wpjam_unregister_post_option($meta_box){
	WPJAM_Post_Option::unregister($meta_box);
}

function wpjam_register_posts_column($name, ...$args){
	if(is_admin()){
		$field	= is_array($args[0]) ? $args[0] : ['title'=>$args[0], 'column_callback'=>($args[1] ?? null)];

		return wpjam_register_list_table_column($name, array_merge($field, ['screen_base'=>'edit']));
	}
}

function wpjam_unregister_posts_column($name){
	if(is_admin()){
		if(WPJAM_List_Table_Column::get($name)){
			WPJAM_List_Table_Column::unregister($name);
		}else{
			add_filter('manage_'.get_current_screen()->post_type.'_posts_columns', function($columns) use($name){
				return wpjam_array_except($columns, $name);
			}, 9999);
		}
	}
}


// 注册分类模式
function wpjam_register_taxonomy($name, $args=[]){
	return WPJAM_Taxonomy::register($name, $args);
}

// 注册分类选项
function wpjam_register_term_option($key, $args=[]){
	if(WPJAM_Term_Option::get($key)){
		trigger_error('Term Option 「'.$key.'」已经注册。');
	}

	if(!is_callable($args) && !isset($args['taxonomy'])){
		if($taxonomies = wpjam_array_pull($args, 'taxonomies')){
			$args['taxonomy']	= (array)$taxonomies;
		}
	}

	return WPJAM_Term_Option::register($key, $args);
}

function wpjam_unregister_term_option($key){
	WPJAM_Term_Option::unregister($key);
}

function wpjam_register_terms_column($name, ...$args){
	if(is_admin()){
		$field	= is_array($args[0]) ? $args[0] : ['title'=>$args[0], 'column_callback'=>($args[1] ?? null)];

		return wpjam_register_list_table_column($name, array_merge($field, ['screen_base'=>'edit-tags']));
	}
}

function wpjam_unregister_terms_column($name){
	if(is_admin()){
		if(WPJAM_List_Table_Column::get($name)){
			WPJAM_List_Table_Column::unregister($name);
		}else{
			add_filter('manage_'.get_current_screen()->id.'_columns', function($columns) use($name){
				return wpjam_array_except($columns, $name);
			}, 9999);
		}
	}
}

// 注册 LazyLoader
function wpjam_register_lazyloader($name, $args){
	$object	= WPJAM_Lazyloader::get($name);

	return $object ?: WPJAM_Lazyloader::register($name, $args);
}

function wpjam_get_lazyloader($name){
	return WPJAM_Lazyloader::get($name);
}

function wpjam_lazyload($name, $ids, ...$args){
	if(in_array($name, ['comment_meta', 'term_meta'])){
		$lazyloader	= wp_metadata_lazyloader();
		$lazyloader->queue_objects(str_replace('_meta', '', $name), $ids);
	}else{
		if($lazyloader = wpjam_get_lazyloader($name)){
			$lazyloader->queue_objects($ids, ...$args);
		}
	}
}

// 注册 AJAX
function wpjam_register_ajax($name, $args){
	$object	= wpjam_get_ajax_object($name);
	$object	= $object ?: WPJAM_AJAX::register($name, $args);

	add_action('wp_ajax_'.$name, [$object, 'callback']);

	if($object->nopriv){
		add_action('wp_ajax_nopriv_'.$name, [$object, 'callback']);
	}

	return $object;
}

function wpjam_get_ajax_object($name){
	return WPJAM_AJAX::get($name);
}

function wpjam_get_ajax_data_attr($name, $data=[], $return=''){
	if($object = wpjam_get_ajax_object($name)){
		return $object->get_data_attr($data, $return);
	}

	return $return == '' ? '' : [];
}

function wpjam_ajax_enqueue_scripts(){
	WPJAM_AJAX::enqueue_scripts();
}


// 注册平台
function wpjam_register_platform($key, $args){
	return WPJAM_Platform::register($key, $args);
}

function wpjam_is_platform($platform){
	return WPJAM_Platform::get($platform)->verify();
}

function wpjam_get_current_platform($platforms=[], $type='key'){
	return WPJAM_Platform::get_current($platforms, $type);
}

function wpjam_get_current_platforms(){
	return WPJAM_Path::get_platforms();
}

// 注册路径
function wpjam_register_path($page_key, ...$args){
	if(count($args) == 2){
		$item	= $args[1]+['path_type'=>$args[0]];
		$args	= [$item];
	}else{
		$args	= $args[0];
		$args	= wp_is_numeric_array($args) ? $args : [$args];
	}

	$object	= WPJAM_Path::get($page_key);
	$object	= $object ?: WPJAM_Path::register($page_key, []);

	foreach($args as $item){
		$type	= wpjam_array_pull($item, 'path_type');

		// if($object->get_type($path_type)){
		// 	trigger_error('Path 「'.$page_key.'」的「'.$path_type.'」已经注册。');
		// }

		$object->add_type($type, $item);
	}

	return $object;
}

function wpjam_unregister_path($page_key, $path_type=''){
	if($path_type){
		if($path_obj = WPJAM_Path::get($page_key)){
			$path_obj->remove_type($path_type);
		}
	}else{
		WPJAM_Path::unregister($page_key);
	}
}

function wpjam_get_path_object($page_key){
	return WPJAM_Path::get($page_key);
}

function wpjam_get_paths($path_type){
	return WPJAM_Path::get_by(['path_type'=>$path_type]);
}

function wpjam_get_tabbar_options($path_type){
	return WPJAM_Path::get_tabbar_options($path_type);
}

function wpjam_get_path_fields($path_type, $for=''){
	return WPJAM_Path::get_path_fields($path_type, $for);
}

function wpjam_get_page_keys($path_type){
	return WPJAM_Path::get_page_keys($path_type);
}

function wpjam_get_path($path_type, $page_key, $args=[]){
	$path_obj	= wpjam_get_path_obj($page_key);

	return $path_obj ? $path_obj->get_path($path_type, $args) : '';
}

function wpjam_parse_path_item($item, $path_type, $parse_backup=true){
	$parsed	= WPJAM_Path::parse_item($item, $path_type);

	if(empty($parsed) && $parse_backup && !empty($item['page_key_backup'])){
		$parsed	= WPJAM_Path::parse_item($item, $path_type, true);
	}

	return $parsed ?: ['type'=>'none'];
}

function wpjam_validate_path_item($item, $path_types){
	return WPJAM_Path::validate_item($item, $path_types);
}

function wpjam_get_path_item_link_tag($parsed, $text){
	return WPJAM_Path::get_item_link_tag($parsed, $text);
}

function wpjam_register_theme_upgrader($upgrader_url){
	$object	= WPJAM_Theme_Upgrader::register(get_template(), ['upgrader_url'=>$upgrader_url]);

	add_filter('site_transient_update_themes',	[$object, 'filter_site_transient']);
}

function wpjam_register_verify_txt($key, $args){
	return WPJAM_Verify_TXT::register($key, $args);
}

function wpjam_register_capability($capability, $map_meta_cap){
	return WPJAM_Capability::register($capability, ['map_meta_cap'=>$map_meta_cap]);
}

function wpjam_register_cron($hook, $args=[]){
	if(is_callable($hook)){
		wpjam_register_job($hook, $args);
	}else{
		if($cron = WPJAM_Cron::get($hook)){
			return $cron;
		}else{
			$cron = WPJAM_Cron::register($hook, wp_parse_args($args, ['recurrence'=>'', 'time'=>time(),	'args'=>[]]));

			return $cron->schedule();
		}	
	}
}

function wpjam_register_job($name, $args=[]){
	if(is_numeric($args)){
		$args	= ['weight'=>$args];
	}elseif(!is_array($args)){
		$args	= [];
	}

	if(empty($args['callback']) || !is_callable($args['callback'])){
		if(is_callable($name)){
			$args['callback']	= $name;

			if(is_object($name)){
				$name	= get_class($name);
			}elseif(is_array($name)){
				$name	= implode(':', $name);
			}
		}else{
			return null;
		}
	}

	return WPJAM_Job::register($name, wp_parse_args($args, ['weight'=>1, 'day'=>-1]));
}

function wpjam_is_scheduled_event($hook) {	// 不用判断参数
	$wp_crons	= _get_cron_array() ?: [];

	foreach($wp_crons as $timestamp => $cron){
		if(isset($cron[$hook])){
			return true;
		}
	}

	return false;
}

add_filter('map_meta_cap',	['WPJAM_Capability', 'filter'], 10, 4);

add_filter('register_taxonomy_args',		['WPJAM_Taxonomy', 'filter_register_args'], 10, 2);
add_filter('wpjam_data_type_field_value',	['WPJAM_Taxonomy', 'filter_data_type_field_value'], 1, 2);

add_filter('post_password_required', 		['WPJAM_Post_Type', 'filter_post_password_required'], 10, 2);
add_filter('wpjam_data_type_field_value',	['WPJAM_Post_Type', 'filter_data_type_field_value'], 1, 2);

add_action('registered_post_type',	['WPJAM_Post_Type', 'on_registered'], 1, 2);
add_action('registered_taxonomy',	['WPJAM_Taxonomy', 'on_registered'], 1, 3);

add_action('init',	function(){
	$GLOBALS['wp']->add_query_var('module');
	$GLOBALS['wp']->add_query_var('action');
	$GLOBALS['wp']->add_query_var('term_id');

	$GLOBALS['wpjam_grant']	= new WPJAM_Grant();

	add_rewrite_rule($GLOBALS['wp_rewrite']->root.'api/([^/]+)/(.*?)\.json?$',	'index.php?module=json&action=mag.$matches[1].$matches[2]', 'top');
	add_rewrite_rule($GLOBALS['wp_rewrite']->root.'api/([^/]+)\.json?$',		'index.php?module=json&action=$matches[1]', 'top');

	add_filter('root_rewrite_rules',	['WPJAM_Verify_TXT', 'filter_root_rewrite_rules']);

	wpjam_register_route_module('json', ['callback'=>['WPJAM_JSON', 'module']]);
	wpjam_register_route_module('txt',	['callback'=>['WPJAM_Verify_TXT', 'module']]);

	wpjam_register_platform('weapp',	['bit'=>1,	'order'=>4,		'title'=>'小程序',	'verify'=>'is_weapp']);
	wpjam_register_platform('weixin',	['bit'=>2,	'order'=>4,		'title'=>'微信网页',	'verify'=>'is_weixin']);
	wpjam_register_platform('mobile',	['bit'=>4,	'order'=>8,		'title'=>'移动网页',	'verify'=>'wp_is_mobile']);
	wpjam_register_platform('web',		['bit'=>8,	'order'=>10,	'title'=>'网页',		'verify'=>'__return_true']);
	wpjam_register_platform('template',	['bit'=>8,	'order'=>10,	'title'=>'网页',		'verify'=>'__return_true']);

	wpjam_register_lazyloader('user',		['filter'=>'wpjam_get_userdata',	'callback'=>'cache_users']);
	wpjam_register_lazyloader('post_meta',	['filter'=>'get_post_metadata',		'callback'=>'update_postmeta_cache']);
	wpjam_register_lazyloader('post_term',	['filter'=>'loop_start',			'callback'=>'update_object_term_cache',	'accepted_args'=>2]);

	foreach(WPJAM_Post_Type::get_registereds() as $name=>$post_type_object){
		if(is_admin() && $post_type_object->show_ui){
			add_filter('post_type_labels_'.$name, ['WPJAM_Post_Type', 'filter_labels']);
		}

		register_post_type($name, $post_type_object->to_array());
	}

	foreach(WPJAM_Taxonomy::get_registereds() as $name => $taxonomy_object){
		if(is_admin() && $taxonomy_object->show_ui){
			add_filter('taxonomy_labels_'.$name,	['WPJAM_Taxonomy', 'filter_labels']);
		}

		register_taxonomy($name, $taxonomy_object->object_type, $taxonomy_object->to_array());
	}

	add_filter('posts_clauses',		['WPJAM_Post_Type', 'filter_clauses'], 1, 2);
	add_filter('post_type_link',	['WPJAM_Post_Type', 'filter_link'], 1, 2);
	add_filter('pre_term_link',		['WPJAM_Taxonomy', 'filter_link'], 1, 2);

	if(WPJAM_Job::get_registereds()){
		wpjam_register_cron('wpjam_scheduled', [
			'recurrence'	=> 'five_minutes',
			'jobs'			=> ['WPJAM_Job', 'get_jobs'],
			'weight'		=> true
		]);
	}
});

add_action('send_headers', function($wp){
	if(!empty($wp->query_vars['module'])){
		if($object = wpjam_get_route_module($wp->query_vars['module'])){
			$action	= $wp->query_vars['action'] ?? '';

			$object->callback($action);
		}

		remove_action('template_redirect', 'redirect_canonical');

		add_filter('template_include', function(){
			$module		= get_query_var('module');
			$action		= get_query_var('action');

			$template	= $action ? $action.'.php' : 'index.php';
			$template	= STYLESHEETPATH.'/template/'.$module.'/'.$template;
			$template	= apply_filters('wpjam_template', $template, $module, $action);

			return is_file($template) ? $template : wp_die('路由错误！');
		});
	}

	$wp->query_vars	= wpjam_parse_query_vars($wp->query_vars);
});

add_action('wpjam_api', function($json){
	if(!wpjam_get_json_object($json)){
		if($json == 'post.list'){
			$post_type	= wpjam_get_parameter('post_type');

			$modules	= [];
			$modules[]	= [
				'type'	=> 'post_type',
				'args'	=> ['post_type'=>$post_type, 'action'=>'list', 'posts_per_page'=>10, 'output'=>'posts']
			];

			if($post_type && is_string($post_type) && get_post_type_object($post_type)){
				foreach(get_object_taxonomies($post_type, 'objects') as $taxonomy=>$tax_obj){
					if($tax_obj->hierarchical){
						$modules[]	= ['type'=>'taxonomy',	'args'=>['taxonomy'=>$taxonomy, 'hide_empty'=>0]];
					}
				}
			}

			wpjam_register_api('post.list',	['modules'=>$modules]);
		}elseif($json == 'post.get'){
			wpjam_register_api('post.get',	['modules'=>['type'=>'post_type',	'args'=>['action'=>'get', 'output'=>'post']]]);
		}elseif($json == 'media.upload'){
			wpjam_register_api('media.upload',	['modules'=>['type'=>'media',	'args'=>['media'=>'media']]]);
		}elseif($json == 'token.grant'){
			wpjam_register_api('token.grant',	['quota'=>1000,	'callback'=>[$GLOBALS['wpjam_grant'], 'generate_token']]);
		}elseif($json == 'token.validate'){
			wpjam_register_api('token.validate',	['quota'=>10,	'grant'=>true]);
		}
	}
}, 11);

add_filter('determine_current_user', function($user_id){
	if(empty($user_id)){
		$wpjam_user	= wpjam_get_current_user();

		if($wpjam_user && !empty($wpjam_user['user_id'])){
			return $wpjam_user['user_id'];
		}
	}

	return $user_id;
});

add_filter('wp_get_current_commenter', function($commenter){
	if(empty($commenter['comment_author_email'])){
		$wpjam_user	= wpjam_get_current_user();

		if($wpjam_user && !empty($wpjam_user['user_email'])){
			$commenter['comment_author_email']	= $wpjam_user['user_email'];
			$commenter['comment_author']		= $wpjam_user['nickname'];
		}
	}

	return $commenter;
});

if(wpjam_is_json_request()){
	remove_filter('the_title', 'convert_chars');

	remove_action('init', 'wp_widgets_init', 1);
	remove_action('init', 'maybe_add_existing_user_to_blog');
	remove_action('init', 'check_theme_switched', 99);

	remove_action('plugins_loaded', '_wp_customize_include');

	remove_action('wp_loaded', '_custom_header_background_just_in_time');
}

if(wp_using_ext_object_cache()){
	add_filter('pre_option_cron', function($pre){
		return get_transient('wpjam_crons') ?: $pre;
	});

	add_filter('pre_update_option_cron', function($value, $old_value){
		if(wp_doing_cron()){
			set_transient('wpjam_crons', $value, HOUR_IN_SECONDS*6);

			return $old_value;
		}else{
			delete_transient('wpjam_crons');

			return $value;
		}
	}, 10, 2);
}