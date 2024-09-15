<?php
class WPJAM_Menu_Page{
	use WPJAM_Register_Trait;

	public static function add($menu_slug, $args=[]){
		if($parent = wpjam_array_pull($args, 'parent')){
			$args	= [$menu_slug=>$args];

			if($object = self::get($parent)){
				$current_subs	= $object->subs ?: [];
				$object->subs	= $args+$current_subs;
			}else{
				self::register($parent, ['subs'=>$args]);
			}
		}else{
			if($object = self::get($menu_slug)){
				$current_subs	= $object->subs ?: [];
				$args['subs']	= $args['subs'] ?? [];
                $args['subs']	= array_merge($current_subs, $args['subs']);
			}

			self::register($menu_slug, $args);
		}
	}

	public static function get_builtin_parents(){
		if(is_multisite() && is_network_admin()){
			return [
				'settings'	=> 'settings.php',
				'theme'		=> 'themes.php',
				'themes'	=> 'themes.php',
				'plugins'	=> 'plugins.php',
				'users'		=> 'users.php',
				'sites'		=> 'sites.php',
			];
		}else{
			$builtin_parents	= [
				'dashboard'	=> 'index.php',
				'management'=> 'tools.php',
				'options'	=> 'options-general.php',
				'theme'		=> 'themes.php',
				'themes'	=> 'themes.php',
				'plugins'	=> 'plugins.php',
				'posts'		=> 'edit.php',
				'media'		=> 'upload.php',
				'links'		=> 'link-manager.php',
				'pages'		=> 'edit.php?post_type=page',
				'comments'	=> 'edit-comments.php',
				'users'		=> current_user_can('edit_users') ? 'users.php' : 'profile.php',
			];

			foreach(get_post_types(['_builtin'=>false, 'show_ui'=>true]) as $ptype) {
				$builtin_parents[$ptype.'s'] = 'edit.php?post_type='.$ptype;
			}

			return $builtin_parents;
		}
	}

	public static function init($plugin_page){
		$GLOBALS['plugin_page']	= $plugin_page;

		self::render(false);
	}

	public static function render($is_rendering){
		do_action('wpjam_admin_init');

		$is_rendering		= is_bool($is_rendering) ? $is_rendering : true;
		$builtin_parents	= self::get_builtin_parents();
		$menu_filter		= (is_multisite() && is_network_admin()) ? 'wpjam_network_pages' : 'wpjam_pages';
		$menu_pages			= apply_filters($menu_filter, self::get_registereds([], 'args'));

		foreach($menu_pages as $menu_slug => $menu_page){
			if(isset($builtin_parents[$menu_slug])){
				$parent_slug	= $builtin_parents[$menu_slug];
			}else{
				$parent_slug	= $menu_slug;
				$menu_page		= self::parse($menu_slug, $menu_page, '', $is_rendering);
			}

			if($menu_page && !empty($menu_page['subs'])){
				$menu_page['subs']	= wpjam_list_sort($menu_page['subs']);

				if($parent_slug	== $menu_slug){
					if(isset($menu_page['subs'][$menu_slug])){
						$menu_page['subs']	= array_merge([$menu_slug=>$menu_page['subs'][$menu_slug]], $menu_page['subs']);
					}else{
						$menu_page['subs']	= array_merge([$menu_slug=>$menu_page], $menu_page['subs']);
					}
				}

				foreach($menu_page['subs'] as $sub_slug => $sub_page){
					$sub_page	= self::parse($sub_slug, $sub_page, $parent_slug, $is_rendering);

					if(!$is_rendering && $GLOBALS['plugin_page'] == $sub_slug){
						break 2;
					}
				}
			}

			if(!$is_rendering && $GLOBALS['plugin_page'] == $menu_slug){
				break;
			}
		}
	}

	private static function parse($menu_slug, $menu_page, $parent_slug='', $is_rendering=true){
		if(!$is_rendering && $GLOBALS['plugin_page'] != $menu_slug){
			return $menu_page;
		}

		if(is_numeric($menu_slug) || empty($menu_page['menu_title'])){
			return false;
		}

		if($parent_slug && str_ends_with($parent_slug, '.php')){
			$admin_page	= $parent_slug;
			$network	= wpjam_array_pull($menu_page, 'network', false);
		}else{
			$admin_page	= 'admin.php';
			$network	= wpjam_array_pull($menu_page, 'network', true);
		}

		if(is_multisite() && is_network_admin()){
			if(!$network){
				return false;
			}
		}else{
			if($network === 'only'){
				return false;
			}
		}

		$menu_title	= $menu_page['menu_title'];
		$page_title	= $menu_page['page_title'] = $menu_page['page_title'] ?? $menu_title;
		$capability	= $menu_page['capability'] ?? 'manage_options';

		if(!empty($menu_page['map_meta_cap']) && is_callable($menu_page['map_meta_cap'])){
			wpjam_register_capability($capability, $menu_page['map_meta_cap']);
		}

		$menu_page['admin_url']	= $admin_url = add_query_arg(['page'=>$menu_slug], $admin_page);

		if(!empty($menu_page['query_args'])){
			$query_data	= wpjam_generate_query_data($menu_page['query_args']);

			if($null_queries = array_filter($query_data, 'is_null')){
				if($GLOBALS['plugin_page'] == $menu_slug){
					wp_die('「'.implode('」,「', array_keys($null_queries)).'」参数无法获取');
				}else{
					return $menu_page;
				}
			}

			$menu_page['query_data']	= $query_data;
			$menu_page['admin_url']		= $queried_url	= add_query_arg($query_data, $admin_url);

			if($is_rendering){
				WPJAM_Queried_Menu::register($menu_slug, ['admin_url'=>esc_url($admin_url),	'queried_url'=>$queried_url]);
			}
		}

		if($is_rendering){
			if($parent_slug){
				$page_hook	= add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, ['WPJAM_Plugin_Page', 'admin_page']);
			}else{
				$icon		= $menu_page['icon'] ?? '';
				$position	= $menu_page['position'] ?? '';

				$page_hook	= add_menu_page($page_title, $menu_title, $capability, $menu_slug, ['WPJAM_Plugin_Page', 'admin_page'], $icon, $position);
			}

			$menu_page['page_hook']	= $page_hook;
		}

		if($GLOBALS['plugin_page'] == $menu_slug 
			&& ($parent_slug  || ($parent_slug == '' && empty($menu_page['subs'])))
		){
			$GLOBALS['current_admin_url']	= is_network_admin() ? network_admin_url($menu_page['admin_url']) : admin_url($menu_page['admin_url']);

			wpjam_register_plugin_page($menu_slug, $menu_page);
		}

		return $menu_page;
	}

	public static function is_tab(){	// 兼容
		return wpjam_get_plugin_page_type() == 'tab';
	}
}

class WPJAM_Plugin_Page{
	use WPJAM_Register_Trait;
	
	public function load($doing_tab=false){
		if($doing_tab){
			$inc_file	= $this->tab_file;
			$load_args	= [$GLOBALS['plugin_page'], $GLOBALS['current_tab']];
			$cb_arg		= $GLOBALS['current_tab'];
		}else{
			$inc_file	= $this->page_file;
			$load_args	= [$GLOBALS['plugin_page'], ''];
			$cb_arg		= $GLOBALS['plugin_page'];
		}

		do_action('wpjam_plugin_page_load', ...$load_args);

		// 一般 load_callback 优先于 load_file 执行
		// 如果 load_callback 不存在，尝试优先加载 load_file
		if($load_callback = $this->load_callback){
			if(!is_callable($load_callback)){
				$this->include_file($inc_file, $doing_tab);
			}
			
			if(is_callable($load_callback)){
				call_user_func($load_callback, $cb_arg);
			}
		}
		
		$this->include_file($inc_file, $doing_tab);

		if($this->chart){
			WPJAM_Chart::init($this->chart);
		}

		if($this->function == 'tab'){
			$result	= $this->tab_load();
		}else{
			$result	= $this->page_load(); 
		}

		if($result && is_wp_error($result)){
			if(wp_doing_ajax()){
				wpjam_send_json($result);
			}else{
				wpjam_admin_add_error($result);
			}
		}
	}

	public function include_file($file, $doing_tab=false){
		static $included;

		$included	= $included ?? new WPJAM_Bit();
		$value		= $doing_tab ? 2 : 1;

		if($included->has($value)){
			return;
		}

		$included->add($value);

		if($file){
			$files	= is_array($file) ? $file : [$file];

			foreach($files as $file){
				include $file;
			}
		}
	}

	public function page_load(){
		$page_types	= [
			'option'		=> 'WPJAM_Option_Setting',
			'form'			=> 'WPJAM_Page_Action',
			'list_table'	=> 'WPJAM_List_Table_Setting',
			'dashboard'		=> 'WPJAM_Dashboard_Setting'
		];

		$function	= $this->function == 'list' ? 'list_table' : $this->function;

		if($function && is_string($function) && isset($page_types[$function])){
			$key	= $function.'_name';
			$name	= $this->$key ?: $GLOBALS['plugin_page'];

			$page_object	= call_user_func([$page_types[$function], 'get_object'], $name);

			if(is_wp_error($page_object)){
				return $page_object;
			}

			if(wp_doing_ajax()){
				return $page_object->page_load();
			}else{
				add_action('load-'.$this->page_hook, [$page_object, 'page_load']);

				$this->page_callback	= [$page_object, 'page'];

				if($title = $page_object->title){
					$this->page_title	= $title;
				}

				if($summary = $page_object->summary){
					$this->summary	= $summary;
				}

				if(method_exists($page_object, 'get_subtitle')){
					$subtitle	= $page_object->get_subtitle();
				}else{
					$subtitle	= $page_object->subtitle;
				}

				if($subtitle){
					$this->subtitle	= $subtitle;
				}
			}
		}else{
			$this->function	= $function ?: wpjam_get_filter_name($GLOBALS['plugin_page'], 'page');

			if(!is_callable($this->function)){
				return new WP_Error('invalid_function', $this->function.'无效或者不存在');
			}
		}
	}

	public function tab_load(){
		$tabs	= $this->tabs ?: [];
		$tabs	= apply_filters(wpjam_get_filter_name($GLOBALS['plugin_page'], 'tabs'), $tabs);

		foreach($tabs as $tab_name => $tab_args){
			self::register_tab($tab_name, $tab_args);
		}

		if(wp_doing_ajax()){
			$current_tab	= wpjam_get_parameter('current_tab', ['method'=>'POST', 'sanitize_callback'=>'sanitize_key']);
		}else{
			$current_tab	= wpjam_get_parameter('tab', ['sanitize_callback'=>'sanitize_key']);
		}

		$tabs	= [];

		foreach(wpjam_list_sort(self::get_registereds()) as $name => $object){
			if(!str_ends_with($name, '__tab') || ($object->plugin_page && $object->plugin_page != $GLOBALS['plugin_page'])){
				continue;
			}

			$name	= substr($name, 0, -5);

			if(empty($current_tab)){
				$current_tab	= $name;
			}

			if($object->capability){
				if($object->map_meta_cap && is_callable($object->map_meta_cap)){
					wpjam_register_map_meta_cap($object->capability, $object->map_meta_cap);
				}

				if(!current_user_can($object->capability)){
					continue;
				}
			}

			if($object->query_args){
				$query_data	= wpjam_generate_query_data($object->query_args);

				if($null_queries = array_filter($query_data, 'is_null')){
					if($current_tab == $name){
						wp_die('「'.implode('」,「', array_keys($null_queries)).'」参数无法获取');
					}else{
						continue;
					}
				}else{
					if($current_tab == $name){
						$GLOBALS['current_admin_url']	= add_query_arg($query_data, $GLOBALS['current_admin_url']);
					}
				}

				$object->query_data	= $query_data;
			}

			$tabs[$name]	= $object;
		}

		if(empty($tabs)){
			return new WP_Error('empty_tabs', 'Tabs 未设置');
		}

		$GLOBALS['current_tab']			= $current_tab;
		$GLOBALS['current_admin_url']	= $GLOBALS['current_admin_url'].'&tab='.$current_tab;

		$this->tabs	= $tabs;

		$object	= $current_tab ? ($tabs[$current_tab] ?? null) : null;

		if(!$object){
			return new WP_Error('invalid_tab', '无效的 Tab');
		}elseif(!$object->function){
			return new WP_Error('empty_tab_function', 'Tab 未设置 function');
		}elseif(!$object->function == 'tab'){
			return new WP_Error('invalid_tab_function', 'Tab 不能嵌套 Tab');
		}else{
			$object->page_hook	= $this->page_hook;
			$this->current_tab	= $object;

			return $object->load(true);
		}
	}

	public function page(){
		if($page_callback = $this->page_callback){
			call_user_func($page_callback);
		}else{
			if($this->chart){
				WPJAM_Chart::form();
			}
			
			if(is_callable($this->function)){
				call_user_func($this->function);
			}
		}
	}

	public function tab_page(){
		$function	= wpjam_get_filter_name($GLOBALS['plugin_page'], 'page');	// 所有 Tab 页面都执行的函数

		if(is_callable($function)){
			call_user_func($function);
		}

		if(count($this->tabs) > 1){
			echo '<nav class="nav-tab-wrapper wp-clearfix">';

			foreach($this->tabs as $tab_name => $tab_object){
				$tab_url	= $this->admin_url.'&tab='.$tab_name;

				if(!empty($tab_object->query_data)){
					$tab_url	= add_query_arg($tab_object->query_data, $tab_url);
				}
				
				$class	= 'nav-tab';
				
				if($GLOBALS['current_tab'] == $tab_name){
					$class	.= ' nav-tab-active';
				}

				$tab_title	= $tab_object->tab_title ?: $tab_object->title;

				echo '<a class="'.$class.'" href="'.$tab_url.'">'.$tab_title.'</a>';
			}

			echo '</nav>';
		}

		if($tab_object = $this->current_tab){
			$tab_object->title(true);
			$tab_object->page();
		}
	}

	public function title($doing_tab=false){
		$page_title	= $this->page_title ?? $this->title;

		if($page_title){
			$subtitle	= $this->subtitle;

			if($doing_tab){
				echo '<h2>'.$page_title.$subtitle.'</h2>';
			}else{
				echo '<h1 class="wp-heading-inline">'.$page_title.'</h1>';
				echo $subtitle;
				echo '<hr class="wp-header-end">';
			}
		}

		$summary	= '';

		if($this->summary){
			if(is_callable($this->summary)){
				$args		= [$GLOBALS['plugin_page']];
				$args[]		= $doing_tab ? $GLOBALS['current_tab'] : '';
				$summary	= call_user_func_array($this->summary, $args);
			}elseif(!is_array($this->summary)){
				$summary	= $this->summary;
			}
		}

		$page_type	= $doing_tab ? 'tab' : 'page';

		if($summary .= wpjam_get_page_summary($page_type)){
			echo wpautop($summary);
		}
	}

	public static function register_tab($name, $args){
		return self::register(sanitize_key($name).'__tab', $args);
	}

	public static function get_current(){
		if(!empty($GLOBALS['plugin_page'])){
			return self::get($GLOBALS['plugin_page']);
		}

		return null;
	}

	public static function admin_page(){
		echo '<div class="wrap">';

		$object	= self::get_current();

		$object->title();

		if($object->function == 'tab'){
			$object->tab_page();
		}else{
			$object->page();
		}

		echo '</div>';
	}
}

class WPJAM_Queried_Menu{
	use WPJAM_Register_Trait;

	public static function filter_html($html){
		$search	= $replace = [];

		foreach(self::get_registereds() as $object){
			$search[]	= "<a href='".$object->admin_url."'";
			$replace[]	= "<a href='".$object->queried_url."'";
		}

		return $search ? str_replace($search, $replace, $html) : $html;
	}
}

class WPJAM_Page_Action{
	use WPJAM_Register_Trait;

	public static function get_object($name){
		$object	= self::get($name);

		if(!$object){
			if(!wpjam_get_plugin_page_setting('callback', true)){
				return new WP_Error('page_action_unregistered', 'Page Action 「'.$name.'」 未注册');
			}

			$args	= wpjam_get_plugin_page_setting('', true);
			$object	= self::register($name, $args);
		}

		return $object;
	}

	public static function ajax_response(){
		$action_type	= wpjam_get_parameter('action_type',	['method'=>'POST', 'sanitize_callback'=>'sanitize_key']);
		$action			= wpjam_get_parameter('page_action',	['method'=>'POST']);

		if($action_type != 'form'){
			$nonce	= wpjam_get_parameter('_ajax_nonce',	['method'=>'POST']);

			if(!self::verify_nonce($nonce, $action)){
				wpjam_send_json(['errcode'=>'invalid_nonce',	'errmsg'=>'非法操作']);
			}
		}

		if($instance = self::get($action)){
			if($action_type == 'form'){
				$form	= $instance->get_form();

				if(is_wp_error($form)){
					wpjam_send_json($form);
				}

				$width		= $instance->width ?? 720;
				$page_title	= wpjam_get_parameter('page_title', ['method'=>'POST']);

				if(is_null($page_title)){
					foreach(['page_title', 'button_text', 'submit_text'] as $key){
						if(!empty($instance->$key)){
							$page_title	= $instance->$key;
							break;
						}
					}
				}

				$result	= ['form'=>$form, 'page_title'=>$page_title, 'width'=>$width];
			}else{
				$result	= $instance->callback($action_type);
			}
		}else{
			do_action_deprecated('wpjam_page_action', [$action, $action_type], 'WPJAM Basic 4.6');

			$ajax_response	= wpjam_get_filter_name($GLOBALS['plugin_page'], 'ajax_response');
			$ajax_response	= apply_filters_deprecated('wpjam_page_ajax_response', [$ajax_response, $GLOBALS['plugin_page'], $action, $action_type], 'WPJAM Basic 4.6');

			if(is_callable($ajax_response)){
				$result	= call_user_func($ajax_response, $action);
				$result	= (is_wp_error($result) || is_array($result)) ? $result : [];
			}else{
				$result	= new WP_Error('invalid_ajax_response', '无效的回调函数');
			}
		}

		wpjam_send_json($result);
	}

	public static function ajax_button($args){
		$args	= wp_parse_args($args, [
			'action'		=> '',
			'data'			=> [],
			'direct'		=> '',
			'confirm'		=> '',
			'button_text'	=> '保存',
			'page_title'	=> '',
			'tag'			=> 'a',
			'nonce'			=> '',
			'class'			=> 'button-primary large',
			'style'			=> ''
		]);

		if(empty($args['action'])){
			return '';
		}

		$title	= $args['page_title'] ?: $args['button_text'];
		$class	= $args['class'] ? $args['class'].' ' : '';

		$attr	= wpjam_attribute_string([
			'title'	=> $title,
			'class'	=> $class.'wpjam-button',
			'style'	=> $args['style'],
			'data'	=> array_merge([
				'action'	=> $args['action'],
				'nonce'		=> $args['nonce'] ?: self::create_nonce($args['action']),
				'data'		=> $args['data'],
				'title'		=> $title
			], wp_array_slice_assoc($args, ['direct', 'confirm']))
		]);

		if($args['tag'] == 'a'){
			$attr	= 'href="javascript:;" '.$attr;
		}
		
		return '<'.$args['tag'].' '.$attr.'>'.$args['button_text'].'</'.$args['tag'].'>';
	}

	public static function ajax_form($args){
		$args	= wp_parse_args($args, [
			'data_type'		=> 'form',
			'fields_type'	=> 'table',
			'fields'		=> [],
			'data'			=> [],
			'action'		=> '',
			'page_title'	=> '',
			'submit_text'	=> '',
			'nonce'			=> '',
			'form_id'		=> 'wpjam_form'
		]);

		$attr	= wpjam_attribute_string([
			'method'	=> 'post',
			'action'	=> '#',
			'id'		=> $args['form_id'],
			'data'		=> [
				'title'		=> $args['page_title'] ?: $args['submit_text'],
				'action'	=> $args['action'],
				'nonce'		=> $args['nonce'] ?: ($args['action'] ? self::create_nonce($args['action']) : '')
			]
		]);

		$form_fields	= $args['fields'] ? wpjam_fields($args['fields'], array_merge($args, ['echo'=>false])) : '';
		$submit_button	= $args['submit_text'] ? get_submit_button($args['submit_text'], 'primary', 'page_submit') : '';

		return 	'<form '.$attr.'>'.$form_fields.$submit_button.'</form>';
	}

	public static function get_nonce_action($key){
		if(isset($GLOBALS['plugin_page'])){
			return $GLOBALS['plugin_page'].'-'.$key;
		}else{
			return get_current_screen()->id.'-'.$key;
		}
	}

	protected static function create_nonce($key){
		return wp_create_nonce(self::get_nonce_action($key));
	}

	protected static function verify_nonce($nonce, $key){
		return wp_verify_nonce($nonce, self::get_nonce_action($key));
	}

	public function parse_args(){
		return wp_parse_args($this->args, ['response'=>$this->name, 'direct'=>false, 'fields'=>[]]);
	}

	public function current_user_can($type=''){
		$capability	= $this->capability ?? ($type ? 'manage_options' : 'read');

		return current_user_can($capability, $this->name);
	}

	public function callback($action_type){
		if(!$this->current_user_can($action_type)){
			return new WP_Error('bad_authentication', '无权限');
		}

		if(!$this->callback || !is_callable($this->callback)){
			return new WP_Error('invalid_ajax_callback', '无效的回调函数');
		}

		if($this->validate){
			$defaults	= wpjam_get_parameter('defaults',	['method'=>'POST', 'sanitize_callback'=>'wp_parse_args', 'default'=>[]]);
			$data		= wpjam_get_parameter('data',		['method'=>'POST', 'sanitize_callback'=>'wp_parse_args', 'default'=>[]]);
			$data		= wpjam_array_merge($defaults, $data);

			if($fields	= $this->get_fields()){
				$data	= wpjam_validate_fields_value($fields, $data);

				if(is_wp_error($data)){
					return $data;
				}
			}

			$result	= call_user_func($this->callback, $data, $this->name);
		}else{
			$result	= call_user_func($this->callback, $this->name);
		}

		if(is_wp_error($result)){
			return $result;
		}elseif(!$result){
			return new WP_Error('error_ajax_callback', '回调函数返回错误');
		}

		$response	= ['type'=>$this->response];

		if(is_array($result)){
			$response	= array_merge($response, $result);
		}elseif($result !== true){
			if($this->response == 'redirect'){
				$response['url']	= $result;
			}else{
				$response['data']	= $result;
			}
		}

		return apply_filters('wpjam_ajax_response', $response);
	}

	public function get_button($args=[]){
		if(!$this->current_user_can()){
			return '';
		}

		$button	= $this->ajax_button(array_merge($this->args, $args, ['action'=>$this->name]));

		return empty($args['wrap']) ? $button : sprintf($args['wrap'], esc_attr($this->name), $button);
	}

	public function get_fields($args=[]){
		$args	= array_merge($this->args, $args);
		$fields	= $args['fields'] ?? [];

		if($fields && is_callable($fields)){
			$fields	= call_user_func($fields, $this->name);
		}

		return $fields;
	}

	public function get_data($args=[]){
		$args	= array_merge($this->args, $args);
		$data	= $args['data'] ?? [];

		if(!empty($args['data_callback']) && is_callable($args['data_callback'])){
			$_data	= call_user_func($args['data_callback'], $this->name, $args['fields']);

			if(is_wp_error($_data)){
				return $_data;
			}

			$data	= array_merge($data, $_data);
		}

		return $data;
	}

	public function get_form($args=[]){
		if(!$this->current_user_can()){
			return '';
		}

		$args	= array_merge($this->args, $args, ['action'=>$this->name]);

		$args['fields']	= $this->get_fields($args);

		if(is_wp_error($args['fields'])){
			return $args['fields'];
		}

		$args['data']	= $this->get_data($args);

		if(is_wp_error($args['data'])){
			return $args['data'];
		}

		return $this->ajax_form($args);
	}

	public function page_load(){}

	public function page(){
		$form	= $this->get_form();

		if(is_wp_error($form)){
			wp_die($form);
		}else{
			echo $form;
		}
	}
}

class WPJAM_Dashboard_Setting{
	use WPJAM_Register_Trait;

	public function page_load(){
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		// wp_dashboard_setup();

		wp_enqueue_script('dashboard');

		if(wp_is_mobile()) {
			wp_enqueue_script('jquery-touch-punch');
		}

		if($this->widgets){
			foreach($this->widgets as $widget_id => $meta_box){
				wpjam_register_dashboard_widget($widget_id, $meta_box);
			}
		}

		foreach(wpjam_list_sort(WPJAM_Dashboard_Widget::get_registereds()) as $widget_id => $widget_object){
			if(!isset($widget_object->dashboard) || $widget_object->dashboard == $this->name){
				$title		= $widget_object->title;
				$callback	= $widget_object->callback ?? wpjam_get_filter_name($widget_id, 'dashboard_widget_callback');
				$context	= $widget_object->context ?? 'normal';	// 位置，normal 左侧, side 右侧
				$priority	= $widget_object->priority ?? 'core';
				$args		= $widget_object->args ?? [];

				// 传递 screen_id 才能在中文的父菜单下，保证一致性。
				add_meta_box($widget_id, $title, $callback, get_current_screen()->id, $context, $priority, $args);
			}
		}
	}

	public function page(){
		if($this->welcome_panel && is_callable($this->welcome_panel)){
			echo '<div id="welcome-panel" class="welcome-panel wpjam-welcome-panel">';
			call_user_func($this->welcome_panel, $this->name);
			// wp_welcome_panel();
			echo '</div>';
		}

		echo '<div id="dashboard-widgets-wrap">';
		wp_dashboard();
		echo '</div>';
	}

	public static function get_object($name){
		$object = self::get($name);

		if(!$object){
			if(!wpjam_get_plugin_page_setting('widgets', true)){
				return new WP_Error('dashboard_unregistered', 'Dashboard 「'.$name.'」 未注册');
			}

			$args	= wpjam_get_plugin_page_setting('', true);
			$object	= self::register($name, $args);
		}

		return $object;
	}
}

class WPJAM_Dashboard_Widget{
	use WPJAM_Register_Trait;
}