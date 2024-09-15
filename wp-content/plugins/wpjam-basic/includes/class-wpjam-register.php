<?php
trait WPJAM_Register_Trait{
	protected $name;
	protected $args;
	protected $filtered	= false;

	public function __construct($name, $args=[]){
		$this->name	= $name;
		$this->args	= $args;
	}

	public function parse_args(){
		return $this->args;
	}

	protected function get_args(){
		if(!$this->filtered){
			$filter	= strtolower(get_called_class()).'_args';
			$args	= $this->parse_args();

			$this->args		= apply_filters($filter, $args, $this->name);
			$this->filtered	= true;
		}

		return $this->args;
	}

	public function __get($key){
		if($key == 'name'){
			return $this->name;
		}else{
			$args	= $this->get_args();
			return $args[$key] ?? null;
		}
	}

	public function __set($key, $value){
		if($key != 'name'){
			$this->args	= $this->get_args();
			$this->args[$key]	= $value;
		}
	}

	public function __isset($key){
		$args	= $this->get_args();
		return isset($args[$key]);
	}

	public function __unset($key){
		$this->args	= $this->get_args();
		unset($this->args[$key]);
	}

	public function to_array(){
		return $this->get_args();
	}

	protected static $_registereds		= [];
	protected static $_pre_registereds	= [];

	public static function parse_name($name){
		if(empty($name)){
			trigger_error(self::class.'的注册 name 为空');
			return null;
		}elseif(is_numeric($name)){
			trigger_error(self::class.'的注册 name「'.$name.'」'.'为纯数字');
			return null;
		}elseif(!is_string($name)){
			trigger_error(self::class.'的注册 name「'.var_export($name, true).'」不为字符串');
			return null;
		}

		return $name;
	}

	public static function register(...$args){
		if(count($args) == 1){
			$object	= $args[0];
			$name	= $object->name;
		}else{
			if($name = self::parse_name($args[0])){
				$object	= new static($name, $args[1]);
			}else{
				$object	= null;
			}
		}

		if($object){
			self::$_registereds[$name]	= $object;
		}

		return $object;
	}

	protected static function register_instance($name, $instance){
		self::$_registereds[$name]	= $instance;

		return $instance;
	}

	public static function unregister($name){
		unset(self::$_registereds[$name]);
	}

	public static function get_by($args=[], $output='objects', $operator='and'){
		return self::get_registereds($args, $output, $operator);
	}

	public static function get_registereds($args=[], $output='objects', $operator='and'){
		$registereds	= $args ? wp_filter_object_list(self::$_registereds, $args, $operator, false) : self::$_registereds;

		if($output == 'names'){
			return array_keys($registereds);
		}elseif(in_array($output, ['args', 'settings'])){
			return array_map(function($registered){ return $registered->to_array(); }, $registereds);
		}else{
			return $registereds;
		}
	}

	public static function get($name){
		return self::$_registereds[$name] ?? null;
	}

	public static function exists($name){
		return self::get($name) ? true : false;
	}

	public static function pre_register($name, $args){
		if($name = self::parse_name($name)){
			$instance	= new static($name, $args);

			self::$_pre_registereds[]	= $instance;

			return $instance;
		}

		return null;
	}

	public static function unregister_pre($name, $args=[]){
		foreach(self::$_pre_registereds as $i => $pre){
			if($pre['name'] == $name){
				if($args && array_diff($args, $pre['args'])){
					continue;
				}

				unset(self::$_pre_registereds[$i]);
			}
		}
	}

	public static function get_pre_registereds(){
		return self::$_pre_registereds;
	}
}

class WPJAM_JSON{
	use WPJAM_Register_Trait;

	public function response(){
		$current_user	= wpjam_get_current_user($this->auth);

		if(is_wp_error($current_user)){
			wpjam_send_json($current_user);
		}

		$response	= [
			'errcode'		=> 0,
			'current_user'	=> $current_user
		];

		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			$response['page_title']		= (string)$this->page_title;
			$response['share_title']	= (string)$this->share_title ;
			$response['share_image']	= (string)$this->share_image;
		}

		if($modules = $this->modules){
			$modules	= wp_is_numeric_array($modules) ? $modules : [$modules];
			
			foreach($modules as $module){
				$module	= wp_parse_args($module, ['type'=>'', 'args'=>[]]);

				if($module_args = $module['args']){
					if(!is_array($module_args)){
						$module_args	= wpjam_parse_shortcode_attr(stripslashes_deep($module_args), 'module');
					}

					if($module['type'] && method_exists($this, 'parse_'.$module['type'].'_module')){
						$result	= call_user_func([$this, 'parse_'.$module['type'].'_module'], $module_args);
					}else{
						$result	= $module_args;
					}

					$response	= $this->merge_result($result, $response);
				}
			}
		}elseif($this->callback || $this->template){
			if($this->callback && is_callable($this->callback)){
				$result	= call_user_func($this->callback, $this->args, $this->name);
			}elseif($this->template && is_file($this->template)){
				$result	= include $this->template;
			}else{
				$result	= null;
			}

			$response	= $this->merge_result($result, $response);
		}else{
			$response	= $this->merge_result($this->args, $response);
		}

		$response	= apply_filters('wpjam_json', $response, $this->args, $this->name);

		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			if(empty($response['page_title'])){
				$response['page_title']		= html_entity_decode(wp_get_document_title());
			}

			if(empty($response['share_title'])){
				$response['share_title']	= $response['page_title'];
			}

			if(!empty($response['share_image'])){
				$response['share_image']	= wpjam_get_thumbnail($response['share_image'], '500x400');
			}
		}

		wpjam_send_json($response);
	}

	public function validate(){
		if(!isset($_GET['access_token']) && is_super_admin()){
			return;
		}

		if($this->name == 'token.grant'){
			$appid	= wpjam_get_parameter('appid',	['required'=>true]);
		}else{
			$appid	= '';

			if($this->grant){
				$token	= wpjam_get_parameter('access_token', ['required'=>true]);
				$item 	= $GLOBALS['wpjam_grant']->get_by_token($token);

				if(is_wp_error($item)){
					wpjam_send_json($item);
				}

				$appid	= $item['appid'];
			}
		}

		$caches	= $GLOBALS['wpjam_grant']->cache_get($appid) ?: [];
		$times	= $caches[$this->name] ?? 0;

		$caches[$this->name]	= $times+1;

		$GLOBALS['wpjam_grant']->cache_set($appid, $caches);

		if($this->quota && $times > $this->quota){
			wpjam_send_json(['errcode'=>'api_exceed_quota', 'errmsg'=>'API 调用次数超限']);
		}
	}

	protected static function merge_result($result, $response){
		if(is_wp_error($result)){
			wpjam_send_json($result);
		}elseif(is_array($result)){
			$except	= [];

			foreach(['page_title', 'share_title', 'share_image'] as $key){
				if(!empty($response[$key]) && isset($result[$key])){
					$except[]	= $key;
				}
			}

			if($except){
				$result	= wpjam_array_except($result, $except);
			}

			$response	= array_merge($response, $result);
		}

		return $response;
	}

	public static function module($action){
		if(!wpjam_doing_debug()){ 
			self::send_origin_headers();

			if(wp_is_jsonp_request()){
				@header('Content-Type: application/javascript; charset='.get_option('blog_charset'));
			}else{
				@header('Content-Type: application/json; charset='.get_option('blog_charset'));
			}
		}

		if(strpos($action, 'mag.') !== 0){
			return;
		}

		$json	= str_replace(['mag.','/'], ['','.'], $action);

		$GLOBALS['wp']->set_query_var('current_json', $json);

		do_action('wpjam_api', $json);

		if($object = self::get($json)){
			$object->validate();
			$object->response();
		}else{
			wpjam_send_json(['errcode'=>'api_not_defined',	'errmsg'=>'接口未定义！']);
		}
	}

	protected static function send_origin_headers(){
		header('X-Content-Type-Options: nosniff');

		if($origin	= get_http_origin()){
			// Requests from file:// and data: URLs send "Origin: null"
			if('null' !== $origin){
				$origin	= esc_url_raw($origin);
			}

			@header('Access-Control-Allow-Origin: ' . $origin);
			@header('Access-Control-Allow-Methods: GET, POST');
			@header('Access-Control-Allow-Credentials: true');
			@header('Access-Control-Allow-Headers: Authorization, Content-Type');
			@header('Vary: Origin');

			if('OPTIONS' === $_SERVER['REQUEST_METHOD']){
				exit;
			}
		}

		if('OPTIONS' === $_SERVER['REQUEST_METHOD']){
			status_header(403);
			exit;
		}
	}

	public static function get_current(){
		return $GLOBALS['wp']->query_vars['current_json'] ?? '';
	}

	public static function parse_post_type_module($module_args){
		$module_action	= wpjam_array_pull($module_args, 'action');

		if(!$module_action){
			wpjam_send_json(['errcode'=>'empty_action',	'errmsg'=>'没有设置 action']);
		}

		$wp	= $GLOBALS['wp'];

		if(isset($wp->raw_query_vars)){
			$wp->query_vars		= $wp->raw_query_vars;
		}else{
			$wp->raw_query_vars	= $wp->query_vars;
		}

		if($module_action == 'list'){
			return self::parse_post_list_module($module_args);
		}elseif($module_action == 'get'){
			return self::parse_post_get_module($module_args);
		}elseif($module_action == 'upload'){
			return self::parse_media_upload_module($module_args);
		}
	}

	/* 规则：
	** 1. 分成主的查询和子查询（$query_args['sub']=1）
	** 2. 主查询支持 $_GET 参数 和 $_GET 参数 mapping
	** 3. 子查询（sub）只支持 $query_args 参数
	** 4. 主查询返回 next_cursor 和 total_pages，current_page，子查询（sub）没有
	** 5. $_GET 参数只适用于 post.list 
	** 6. term.list 只能用 $_GET 参数 mapping 来传递参数
	*/
	public static function parse_post_list_module($query_args){
		$query_args	= wp_parse_args($query_args, ['cache_results'=>true]);
		$output		= wpjam_array_pull($query_args, 'output');

		$is_main_query	= !wpjam_array_pull($query_args, 'sub');	// 子查询不支持 $_GET 参数，置空之前要把原始的查询参数存起来

		if($is_main_query){
			$wp	= $GLOBALS['wp'];

			foreach($query_args as $query_key => $query_var){
				$wp->set_query_var($query_key, $query_var);
			}

			$post_type	= $wp->query_vars['post_type'] ?? '';

			if($posts_per_page = (int)wpjam_get_parameter('posts_per_page')){
				$wp->set_query_var('posts_per_page', ($posts_per_page > 20 ? 20 : $posts_per_page));
			}

			if($offset = (int)wpjam_get_parameter('offset')){
				$wp->set_query_var('offset', $offset);
			}

			$orderby	= $wp->query_vars['orderby'] ?? 'date';
			$paged		= $wp->query_vars['paged'] ?? null;
			$use_cursor	= (empty($paged) && is_null(wpjam_get_parameter('s')) && !is_array($orderby) && in_array($orderby, ['date', 'post_date']));

			if($use_cursor){
				if($cursor = (int)wpjam_get_parameter('cursor')){
					$wp->set_query_var('cursor', $cursor);
					$wp->set_query_var('ignore_sticky_posts', true);
				}

				if($since = (int)wpjam_get_parameter('since')){
					$wp->set_query_var('since', $since);
					$wp->set_query_var('ignore_sticky_posts', true);
				}
			}

			// taxonomy 参数处理，同时支持 $_GET 和 $query_args 参数
			$taxonomies	= $post_type ? get_object_taxonomies($post_type) : get_taxonomies(['public'=>true]);
			$taxonomies	= array_diff($taxonomies, ['post_format']);

			if(wpjam_array_pull($taxonomies, 'category') && empty($wp->query_vars['cat'])){
				foreach(['category_id', 'cat_id'] as $cat_key){
					if($term_id	= (int)wpjam_get_parameter($cat_key)){
						$wp->set_query_var('cat', $term_id);
						break;
					}
				}
			}

			foreach($taxonomies as $taxonomy){
				$query_key	= wpjam_get_taxonomy_query_key($taxonomy);

				if($term_id	= (int)wpjam_get_parameter($query_key)){
					$wp->set_query_var($query_key, $term_id);
				}
			}

			if($term_id	= (int)wpjam_get_parameter('term_id')){
				if($taxonomy = wpjam_get_parameter('taxonomy')){
					$wp->set_query_var('term_id', $term_id);
					$wp->set_query_var('taxonomy', $taxonomy);
				}
			}

			$wp->query_vars	= wpjam_parse_query_vars($wp->query_vars);

			$wp->query_posts();

			$wp_query	= $GLOBALS['wp_query'];
		}else{
			$post_type	= $query_args['post_type'] ?? '';
			$query_args	= wpjam_parse_query_vars($query_args);
			$wp_query	= new WP_Query($query_args);
		}

		if(empty($output)){
			$output	= ($post_type && !is_array($post_type)) ? $post_type.'s' : 'posts';
		}

		$_posts = [];

		while($wp_query->have_posts()){
			$wp_query->the_post();

			$_posts[]	= wpjam_get_post(get_the_ID(), $query_args);
		}

		$posts_json = [];

		if($is_main_query){
			if(is_category() || is_tag() || is_tax()){
				if($current_term = get_queried_object()){
					$taxonomy		= $current_term->taxonomy;
					$current_term	= wpjam_get_term($current_term, $taxonomy);

					$posts_json['current_taxonomy']		= $taxonomy;
					$posts_json['current_'.$taxonomy]	= $current_term;
				}else{
					$posts_json['current_taxonomy']		= null;
				}
			}elseif(is_author()){
				if($author = $wp_query->get('author')){
					$posts_json['current_author']	= WPJAM_User::get_instance($author)->parse_for_json();
				}else{
					$posts_json['current_author']	= null;
				}
			}

			$posts_json['total']		= (int)$wp_query->found_posts;
			$posts_json['total_pages']	= (int)$wp_query->max_num_pages;
			$posts_json['current_page']	= (int)($wp_query->get('paged') ?: 1);

			if($use_cursor){
				$posts_json['next_cursor']	= ($_posts && $wp_query->max_num_pages>1) ? end($_posts)['timestamp'] : 0;
			}

			$posts_json['page_title']	= $posts_json['share_title'] = html_entity_decode(wp_get_document_title());
		}

		$posts_json[$output]	= $_posts;

		return apply_filters('wpjam_posts_json', $posts_json, $wp_query, $output);
	}

	public static function parse_post_get_module($query_args){
		global $wp, $wp_query;

		$post_id	= $query_args['id'] ?? (int)wpjam_get_parameter('id');
		$post_type	= $query_args['post_type'] ?? wpjam_get_parameter('post_type',	['default'=>'any']);

		if($post_type != 'any'){
			$pt_obj	= get_post_type_object($post_type);

			if(!$pt_obj){
				wpjam_send_json(['errcode'=>'post_type_not_exists',	'errmsg'=>'post_type 未定义']);
			}
		}

		if(empty($post_id)){
			if($post_type == 'any'){
				wpjam_send_json(['errcode'=>'empty_post_id',	'errmsg'=>'文章ID不能为空']);
			}

			$orderby	= wpjam_get_parameter('orderby');

			if($orderby == 'rand'){
				$wp->set_query_var('orderby', 'rand');
			}else{
				$name_key	= $pt_obj->hierarchical ? 'pagename' : 'name';

				$wp->set_query_var($name_key,	wpjam_get_parameter($name_key,	['required'=>true]));
			}
		}else{
			$wp->set_query_var('p', $post_id);
		}

		$wp->set_query_var('post_type', $post_type);
		$wp->set_query_var('posts_per_page', 1);
		$wp->set_query_var('cache_results', true);

		$wp->query_posts();

		if($wp_query->have_posts()){
			$post_id	= $wp_query->post->ID;
		}else{
			if($post_name = get_query_var('name')){
				if($post_id = apply_filters('old_slug_redirect_post_id', null)){
					$post_type	= 'any';

					$wp->set_query_var('post_type', $post_type);
					$wp->set_query_var('posts_per_page', 1);
					$wp->set_query_var('p', $post_id);
					$wp->set_query_var('name', '');
					$wp->set_query_var('pagename', '');

					$wp->query_posts();
				}else{
					wpjam_send_json(['errcode'=>'empty_query',	'errmsg'=>'查询结果为空']);
				}
			}else{
				wpjam_send_json(['errcode'=>'empty_query',	'errmsg'=>'查询结果为空']);
			}
		}

		$_post	= wpjam_get_post($post_id, $query_args);

		$post_json	= [];

		$post_json['page_title']	= html_entity_decode(wp_get_document_title());

		if($share_title = wpjam_array_pull($_post, 'share_title')){
			$post_json['share_title']	= $share_title;
		}else{
			$post_json['share_title']	= $post_json['page_title'];
		}

		if($share_image = wpjam_array_pull($_post, 'share_image')){
			$post_json['share_image']	= $share_image;
		}

		$output	= $query_args['output'] ?? '';
		$output	= $output ?: $_post['post_type'];

		$post_json[$output]	= $_post;

		return $post_json;
	}

	public static function parse_taxonomy_module($module_args){
		$taxonomy	= $module_args['taxonomy'] ?? '';
		$tax_obj	= $taxonomy ? get_taxonomy($taxonomy) : null;

		if(empty($tax_obj)){
			wpjam_send_json(['errcode'=>'invalid_taxonomy',	'errmsg'=>'无效的自定义分类']);
		}

		$args	= $module_args;

		if($mapping = wpjam_array_pull($args, 'mapping')){
			$mapping	= wp_parse_args($mapping);

			if($mapping && is_array($mapping)){
				foreach($mapping as $key => $get){
					if($value = wpjam_get_parameter($get)){
						$args[$key]	= $value;
					}
				}
			}
		}

		$number		= (int)wpjam_array_pull($args, 'number');
		$output		= wpjam_array_pull($args, 'output') ?: $taxonomy.'s';
		$max_depth	= wpjam_array_pull($args, 'max_depth') ?: ($tax_obj->levels ?? -1);

		$terms_json	= [];

		if($terms = wpjam_get_terms($args, $max_depth)){
			if($number){
				$paged	= $args['paged'] ?? 1;
				$offset	= $number * ($paged-1);

				$terms_json['current_page']	= (int)$paged;
				$terms_json['total_pages']	= ceil(count($terms)/$number);
				$terms = array_slice($terms, $offset, $number);
			}

			$terms_json[$output]	= array_values($terms);
		}else{
			$terms_json[$output]	= [];
		}

		$terms_json['page_title']	= $tax_obj->label;

		return $terms_json;
	}

	public static function parse_media_upload_module($module_args){
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$media_id	= $module_args['media'] ?? 'media';
		$output		= $module_args['output'] ?? 'url';

		if (!isset($_FILES[$media_id])) {
			wpjam_send_json(['errcode'=>'empty_media',	'errmsg'=>'媒体流不能为空！']);
		}

		$post_id		= (int)wpjam_get_parameter('post_id',	['method'=>'POST', 'default'=>0]);
		$attachment_id	= media_handle_upload($media_id, $post_id);

		if(is_wp_error($attachment_id)){
			wpjam_send_json($attachment_id);
		}

		return [$output=>wp_get_attachment_url($attachment_id)];
	}

	public static function parse_media_module($module_args){
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$media_id	= $module_args['media'] ?? 'media';
		$output		= $module_args['output'] ?? 'url';

		if(!isset($_FILES[$media_id])){
			wpjam_send_json(['errcode'=>'empty_media',	'errmsg'=>'媒体流不能为空！']);
		}

		$upload_file	= wp_handle_upload($_FILES[$media_id], ['test_form'=>false]);

		if(isset($upload_file['error'])){
			wpjam_send_json(['errcode'=>'upload_error',	'errmsg'=>$upload_file['error']]);
		}

		return [$output=>$upload_file['url']];
	}

	public static function parse_setting_module($module_args){
		if(empty($module_args['option_name'])){
			return null;
		}

		$option_name	= $module_args['option_name'];
		$setting_name	= $module_args['setting_name'] ?? ($module_args['setting'] ?? '');

		if(!empty($module_args['output'])){
			$output	= $module_args['output'];
		}else{
			$output	= $setting_name ? $setting_name : $option_name;
		}

		if($object = WPJAM_Option_Setting::get($option_name)){
			$value	= $object->prepare_value();

			if($object->option_type == 'single'){
				$value	= $value[$option_name] ?? null;

				return [$output=>$value];
			}
		}else{
			$value	= wpjam_get_option($option_name);
		}

		if($setting_name){
			$value	= $value[$setting_name] ?? null;
		}

		return [$output=>$value];
	}
}

class WPJAM_Grant{
	protected $items = [];

	public function __construct(){
		$items = get_option('wpjam_grant') ?: [];

		if($items && !wp_is_numeric_array($items)){
			$items	= [$items];
		}

		$this->items	= $items;
	}

	public function __call($method, $args){
		if(in_array($method, ['cache_get', 'cache_set', 'cache_add', 'cache_delete'])){
			$cg_obj		= WPJAM_Cache_Group::get_instance('wpjam_api_times');

			$today		= date('Y-m-d', current_time('timestamp'));
			$args[0]	= $args[0] ? $args[0].':'.$today : $today;

			return call_user_func_array([$cg_obj, $method], $args);
		}elseif($method == 'get_items'){
			return $this->items;
		}elseif($method == 'save'){
			return update_option('wpjam_grant', array_values($this->items));
		}
	}

	public function get_by_appid($appid, &$index=0){
		if($appid && $this->items){
			foreach($this->items as $i=> $item){
				if($item['appid'] == $appid){
					$index	= $i;
					return $item;
				}
			}
		}

		return new WP_Error('invalid_appid', '无效的AppId');
	}

	public function get_by_token($token){
		foreach($this->items as $item){
			if(isset($item['token']) && $item['token'] == $token && (time()-$item['time'] < 7200)){
				return $item;
			}
		}

		return new WP_Error('invalid_access_token', '非法 Access Token');
	}

	public function add(){
		if(count($this->items) >= 3){
			return new WP_Error('appid_over_quota', '最多可以设置三个APPID');
		}

		$appid	= 'jam'.strtolower(wp_generate_password(15, false, false));
		$item	= $this->get_by_appid($appid);

		if($item && !is_wp_error($item)){
			return new WP_Error('appid_exists', 'AppId已存在');
		}

		$this->items[]	= compact('appid');
		$this->save();

		return $appid;
	}

	public function delete($appid){
		$item	= $this->get_by_appid($appid, $index);

		if(is_wp_error($item)){
			return $item;
		}

		unset($this->items[$index]);

		$this->save();
	}

	public function reset_secret($appid){
		$item	= $this->get_by_appid($appid, $index);

		if(is_wp_error($item)){
			return $item;
		}

		$secret	= strtolower(wp_generate_password(32, false, false));

		$item['secret']	= md5($secret);

		$this->items[$index]	= wpjam_array_except($iten, ['token', 'time']);
		$this->save();

		return $secret;
	}

	public function reset_token($appid, $secret){
		$item	= $this->get_by_appid($appid, $index);

		if(is_wp_error($item)){
			return $item;
		}

		if(empty($item['secret']) || $item['secret'] != md5($secret)){
			return new WP_Error('invalid_secret', '非法密钥');
		}

		$item['token']	= $token = wp_generate_password(64, false, false);
		$item['time']	= time();

		$this->items[$index]	= $item;
		$this->save();

		return $token;
	}

	public function generate_token(){
		$appid	= wpjam_get_parameter('appid',	['required'=>true]);
		$secret	= wpjam_get_parameter('secret', ['required'=>true]);
		$token	= $this->reset_token($appid, $secret);

		return is_wp_error($token) ? $token : ['access_token'=>$token, 'expires_in'=>7200];
	}
}

class WPJAM_API{
	public static function __callStatic($method, $args){
		$function	= 'wpjam_'.$method;

		if(function_exists($function)){
			return call_user_func($function, ...$args);
		}
	}

	public static function get_apis(){
		return WPJAM_JSON::get_by();
	}
}

class WPJAM_Meta_Type{
	use WPJAM_Register_Trait;

	private $lazyloader	= null;

	public function __call($method, $args){
		if(in_array($method, ['get_meta', 'add_meta', 'update_meta', 'delete_meta', 'lazyload_meta'])){
			$method	= str_replace('_meta', '_data', $method);
		}elseif(in_array($method, ['delete_meta_by_key', 'update_meta_cache', 'create_meta_table', 'get_meta_table'])){
			$method	= str_replace('_meta', '', $method);
		}

		return call_user_func([$this, $method], ...$args);
	}

	public function lazyload_data($ids){
		if(is_null($this->lazyloader)){
			$this->lazyloader	= wpjam_register_lazyloader($this->name.'_meta', [
				'filter'	=> 'get_'.$this->name.'_metadata', 
				'callback'	=> [$this, 'update_cache']
			]);
		}

		$this->lazyloader->queue_objects($ids);
	}

	public function get_data($id, $meta_key='', $single=false){
		return get_metadata($this->name, $id, $meta_key, $single);
	}

	public function add_data($id, $meta_key, $meta_value, $unique=false){
		return add_metadata($this->name, $id, $meta_key, wp_slash($meta_value), $unique);
	}

	public function update_data($id, $meta_key, $meta_value, $prev_value=''){
		if($meta_value){
			return update_metadata($this->name, $id, $meta_key, wp_slash($meta_value), $prev_value);
		}else{
			return delete_metadata($this->name, $id, $meta_key, $prev_value);
		}
	}

	public function delete_data($id, $meta_key, $meta_value=''){
		return delete_metadata($this->name, $id, $meta_key, $meta_value);
	}

	public function delete_by_key($meta_key){
		return delete_metadata($this->name, null, $meta_key, '', true);
	}

	public function update_cache($object_ids){
		if($object_ids){
			update_meta_cache($this->name, $object_ids);
		}
	}

	public function get_table(){
		return $this->table ?: $GLOBALS['wpdb']->prefix.sanitize_key($this->name).'meta';
	}

	public function create_table(){
		$table	= $this->get_table();

		if($GLOBALS['wpdb']->get_var("show tables like '{$table}'") != $table){
			$column	= sanitize_key($this->name).'_id';

			$GLOBALS['wpdb']->query("CREATE TABLE {$table} (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				{$column} bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value longtext,
				PRIMARY KEY  (meta_id),
				KEY {$column} ({$column}),
				KEY meta_key (meta_key(191))
			)");
		}
	}
}

class WPJAM_Lazyloader{
	use WPJAM_Register_Trait;

	private $pending_objects	= [];

	public function callback($check){
		if($this->pending_objects){
			if($this->accepted_args && $this->accepted_args > 1){
				foreach($this->pending_objects as $object){
					call_user_func($this->callback, $object['ids'], ...$object['args']);
				}
			}else{
				call_user_func($this->callback, $this->pending_objects);
			}

			$this->pending_objects	= [];
		}
	
		remove_filter($this->filter, [$this, 'callback']);

		return $check;
	}

	public function queue_objects($object_ids, ...$args){
		if($this->accepted_args && $this->accepted_args > 1){
			if((count($args)+1) >= $this->accepted_args){
				$key	= wpjam_json_encode($args);

				if(isset($this->pending_objects[$key])){
					$this->pending_objects[$key]['ids']	= array_merge($this->pending_objects[$key]['ids'], $object_ids);
					$this->pending_objects[$key]['ids']	= array_unique($this->pending_objects[$key]['ids']);
				}else{
					$this->pending_objects[$key]	= ['ids'=>$object_ids, 'args'=>$args];
				}
			}
		}else{
			$this->pending_objects	= array_merge($this->pending_objects, $object_ids);
			$this->pending_objects	= array_unique($this->pending_objects);
		}

		add_filter($this->filter, [$this, 'callback']);
	}
}

class WPJAM_AJAX{
	use WPJAM_Register_Trait;

	public function create_nonce($args=[]){
		$nonce_action	= $this->name;

		if($this->nonce_keys){
			foreach($this->nonce_keys as $key){
				if(!empty($args[$key])){
					$nonce_action	.= ':'.$args[$key];
				}
			}
		}

		return wp_create_nonce($nonce_action);
	}

	public function verify_nonce($nonce){
		$nonce_action	= $this->name;

		if($this->nonce_keys){
			foreach($this->nonce_keys as $key){
				if($value = wpjam_get_data_parameter($key)){
					$nonce_action	.= ':'.$value;
				}
			}
		}

		return wp_verify_nonce($nonce, $nonce_action);
	}

	public function callback(){
		if(!$this->callback || !is_callable($this->callback)){
			wp_die('0', 400);
		}
		
		$nonce	= wpjam_get_parameter('_ajax_nonce', ['method'=>'POST']);

		if(!$this->verify_nonce($nonce)){
			wpjam_send_json(['errcode'=>'invalid_nonce', 'errmsg'=>'非法操作']);
		}

		$result	= call_user_func($this->callback);

		wpjam_send_json($result);
	}

	public function get_data_attr($data=[], $return=''){
		$attr	= [
			'action'	=> $this->name,
			'nonce'		=> $this->create_nonce($data),
		];

		if($data){
			$attr['data']	= http_build_query($data);
		}

		return $return ? $attr : wpjam_data_attribute_string($attr);
	}
	
	public static $enqueued	= null;

	public static function enqueue_scripts(){
		if(isset(self::$enqueued)){
			return;
		}

		self::$enqueued	= true;

		$scripts	= '
if(typeof ajaxurl == "undefined"){
	var ajaxurl	= "'.admin_url('admin-ajax.php').'";
}

jQuery(function($){
	if(window.location.protocol == "https:"){
		ajaxurl	= ajaxurl.replace("http://", "https://");
	}

	$.fn.extend({
		wpjam_submit: function(callback){
			let _this	= $(this);
			
			$.post(ajaxurl, {
				action:			$(this).data(\'action\'),
				_ajax_nonce:	$(this).data(\'nonce\'),
				data:			$(this).serialize()
			},function(data, status){
				callback.call(_this, data);
			});
		},
		wpjam_action: function(callback){
			let _this	= $(this);
			
			$.post(ajaxurl, {
				action:			$(this).data(\'action\'),
				_ajax_nonce:	$(this).data(\'nonce\'),
				data:			$(this).data(\'data\')
			},function(data, status){
				callback.call(_this, data);
			});
		}
	});
});
		';

		if(did_action('wpjam_static') && !wpjam_is_login()){
			wpjam_register_static('wpjam-script',	['title'=>'AJAX 基础脚本', 'type'=>'script',	'source'=>'value',	'value'=>$scripts]);
		}else{
			wp_enqueue_script('jquery');
			wp_add_inline_script('jquery', $scripts);
		}
	}
}

class WPJAM_Theme_Upgrader{
	use WPJAM_Register_Trait;

	public function filter_site_transient($transient){
		if($this->upgrader_url){
			$theme	= $this->name;
	
			if(empty($transient->checked[$theme])){
				return $transient;
			}
			
			$remote	= get_transient('wpjam_theme_upgrade_'.$theme);

			if(false == $remote){
				$remote = wpjam_remote_request($this->upgrader_url);
		 
				if(!is_wp_error($remote)){
					set_transient('wpjam_theme_upgrade_'.$theme, $remote, HOUR_IN_SECONDS*12);
				}
			}

			if($remote && !is_wp_error($remote)){
				if(version_compare($transient->checked[$theme], $remote['new_version'], '<')){
					$transient->response[$theme]	= $remote;
				}
			}
		}
		
		return $transient;
	}
}

class WPJAM_Route_Module{
	use WPJAM_Register_Trait;

	public function callback($action){
		call_user_func($this->callback, $action, $this->name);
	}
}

class WPJAM_Capability{
	use WPJAM_Register_Trait;

	public static function filter($caps, $cap, $user_id, $args){
		if(in_array('do_not_allow', $caps) || empty($user_id)){
			return $caps;
		}

		if($object = self::get($cap)){
			return call_user_func($object->map_meta_cap, $user_id, $args, $cap);
		}

		return $caps;
	}
}

class WPJAM_Verify_TXT{
	use WPJAM_Register_Trait;

	public function get_data($key=''){
		$data	= wpjam_get_setting('wpjam_verify_txts', $this->name) ?: [];

		return $key ? ($data[$key] ?? '') : $data;
	}

	public function set_data($data){
		return wpjam_update_setting('wpjam_verify_txts', $this->name, $data) || true;
	}

	public function get_fields(){
		$data	= $this->get_data();

		return [
			'name'	=>['title'=>'文件名称',	'type'=>'text',	'required', 'value'=>$data['name'] ?? '',	'class'=>'all-options'],
			'value'	=>['title'=>'文件内容',	'type'=>'text',	'required', 'value'=>$data['value'] ?? '']
		];
	}

	public static function __callStatic($method, $args){
		$name	= $args[0];

		if($object = self::get($name)){
			if(in_array($method, ['get_name', 'get_value'])){
				return $object->get_data(str_replace('get_', '', $method));
			}elseif($method == 'set' || $method == 'set_value'){
				return $object->set_data(['name'=>$args[1], 'value'=>$args[2]]);
			}
		}	
	}

	public static function filter_root_rewrite_rules($root_rewrite){
		if(empty($GLOBALS['wp_rewrite']->root)){
			$home_path	= parse_url(home_url());

			if(empty($home_path['path']) || '/' == $home_path['path']){
				$root_rewrite	= array_merge(['([^/]+)\.txt?$'=>'index.php?module=txt&action=$matches[1]'], $root_rewrite);
			}
		}
		
		return $root_rewrite;
	}

	public static function module($action){
		if($values = wpjam_get_option('wpjam_verify_txts')){
			$name	= str_replace('.txt', '', $action).'.txt';
			
			foreach($values as $key => $value) {
				if($value['name'] == $name){
					header('Content-Type: text/plain');
					echo $value['value'];

					exit;
				}
			}
		}

		wp_die('错误');
	}
}

class WPJAM_Cron{
	use WPJAM_Register_Trait;

	public function schedule(){
		if(is_null($this->callback)){
			$this->callback	= [$this, 'callback'];
		}

		if(is_callable($this->callback)){
			add_action($this->name, $this->callback);

			if(!wpjam_is_scheduled_event($this->name)){
				$args	= $this->args['args'] ?? [];

				if($this->recurrence){
					wp_schedule_event($this->time, $this->recurrence, $this->name, $args);
				}else{
					wp_schedule_single_event($this->time, $this->name, $args);
				}
			}
		}

		return $this;
	}

	public function callback(){
		if(get_site_transient($this->name.'_lock')){
			return;
		}

		set_site_transient($this->name.'_lock', 1, 5);
		
		if($jobs = $this->get_jobs()){
			$callbacks	= array_column($jobs, 'callback');
			$total		= count($callbacks);
			$index		= get_transient($this->name.'_index') ?: 0;
			$index		= $index >= $total ? 0 : $index;
			$callback	= $callbacks[$index];
			
			set_transient($this->name.'_index', $index+1, DAY_IN_SECONDS);

			$this->increment();

			if(is_callable($callback)){
				call_user_func($callback);
			}else{
				trigger_error('invalid_job_callback'.var_export($callback, true));
			}
		}
	}

	public function get_jobs($jobs=null){
		if(is_null($jobs)){
			$jobs	= $this->jobs;

			if($jobs && is_callable($jobs)){
				$jobs	= call_user_func($jobs);
			}
		}

		$jobs	= $jobs ?: [];

		if(!$jobs || !$this->weight){
			return array_values($jobs);
		}

		$queue	= [];
		$next	= [];

		foreach($jobs as $job){
			$job['weight']	= $job['weight'] ?? 1;

			if($job['weight']){
				$queue[]	= $job;

				if($job['weight'] > 1){
					$job['weight'] --;
					$next[]	= $job;
				}
			}
		}

		if($next){
			$queue	= array_merge($queue, $this->get_jobs($next)); 
		}

		return $queue;
	}

	public function get_counter($increment=false){
		$today		= date('Y-m-d', current_time('timestamp'));
		$counter	= get_transient($this->name.'_counter:'.$today) ?: 0;

		if($increment){
			$counter ++;
			set_transient($this->name.'_counter:'.$today, $counter, DAY_IN_SECONDS);
		}

		return $counter;
	}

	public function increment(){
		return $this->get_counter(true);
	}
}

class WPJAM_Job{
	use WPJAM_Register_Trait;

	public static function get_jobs($raw=false){
		$jobs	= self::get_registereds([], 'args');

		return $raw ? $jobs : array_filter($jobs, function($job){
			if($job['day'] == -1){
				return true;
			}else{
				$day	= (current_time('H') > 2 && current_time('H') < 6) ? 0 : 1;
				return $job['day']	== $day;
			}
		});
	}
}

trait WPJAM_Type_Trait{
	use WPJAM_Register_Trait;
}

class_alias('WPJAM_Verify_TXT', 'WPJAM_VerifyTXT');