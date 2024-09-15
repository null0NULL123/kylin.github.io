<?php
class WPJAM_Signup_Setting{
	use WPJAM_Setting_Trait;

	private $login_actions	= [];
	private $invite_actions	= [];
	private $bind_actions	= [];

	private function __construct(){
		$this->init('wpjam-signup');

		if($signups = wpjam_get_signups()){

			foreach ($signups as $name => $args){
				$login_enable	= $this->get_setting($name.'_login') ?? $args['default'];
				if($login_enable){
					$this->login_actions[$name]	= $args;

					if($name == 'weixin'){
						$this->invite_actions['weixin']	= $args;
					}
				}

				$bind_enable	= $this->get_setting($name.'_bind') ?? $args['default'];

				if($bind_enable){
					$this->bind_actions[$name]	= $args;
				}
				
			}
		}
	}

	public function get_login_actions(){
		return $this->login_actions;
	}

	public function get_invite_actions(){
		return $this->invite_actions;
	}

	public function get_bind_actions(){
		return $this->bind_actions;
	}
}

class WPJAM_Signup_Hook{
	public static function on_login_form_login(){
		$action	= $_REQUEST['action'] ?? '';
		$args	= [];

		if(wpjam_get_invite_actions()){
			$invite_key	= $_REQUEST['invite_key'] ?? '';

			if($invite_key){
				$invite	= wpjam_validate_invite($invite_key);

				if(is_wp_error($invite)){
					wp_die($invite);
				}elseif($invite){
					$args['invite']				= $invite_key;
					$args['role']				= $invite['role'];
					$args['blog_id']			= $invite['blog_id'] ?? 0;
					$args['users_can_register']	= true;
				
					$action	= 'weixin';
				}
			}
		}

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if(empty($action)){
				$action	= 'login';
			}
		}

		if($action == 'login'){
			return;
		}

		$login_actions	= wpjam_get_login_actions();

		if($action && isset($login_actions[$action])){
			$login_action			= $login_actions[$action];
			$args['action']			= $action;
			$args['login_title']	= $login_action['login_title'];
			call_user_func([$login_action['model'], 'login_action'], $args);
		}else{
			foreach($login_actions as $login_key => $login_action){
				$args['login_title']	= $login_action['login_title'];
				$args['action']			= $login_key;
				call_user_func([$login_action['model'], 'login_action'], $args);
			}
		}
	}

	public static function on_login_init(){
		if(empty($_COOKIE[TEST_COOKIE])){
			$_COOKIE[TEST_COOKIE]	= 'WP Cookie check';
		}

		wp_enqueue_script('jquery');
	}

	public static function on_login_footer(){
		if(!empty($_REQUEST['invite_key'])){
			return;
		}

		$login_actions	= wpjam_get_login_actions();
		$login_actions['login']	= ['title'=>'账号密码',	'login_title'=>'使用账号和密码登录'];

		$action	= $_REQUEST['action'] ?? '';

		if(empty($action)){
			if($_SERVER['REQUEST_METHOD'] == 'POST'){
				$action	='login';
			}else{
				$action	= current(array_keys($login_actions));
			}
		}
		
		if($action != 'login' && isset($login_actions[$action])){
			call_user_func([$login_actions[$action]['model'], 'login_footer'], $action);
		}

		unset($login_actions[$action]);

		$login_texts	= [];

		$redirect_to	= $_REQUEST['redirect_to'] ?? '';
		$interim_login	= isset($_REQUEST['interim-login']);
		$login_url		= site_url('wp-login.php', 'login_post');

		foreach ($login_actions as $login_key => $login_action) {
			$args	= ['action'=>$login_key];

			if($interim_login){
				$args['interim-login']	= 1;
			}

			if($redirect_to){
				$args['redirect_to']	= urlencode($redirect_to);
			}

			$login_url		= add_query_arg($args, $login_url);
			$login_texts[]	= '<a style="text-decoration: none;" href="'.esc_url($login_url).'">'.$login_action['login_title'].'</a>';
		}

		$login_text	= '<p style="line-height:30px; float:left;">'.implode('<br />', $login_texts).'</p>';

		if($action == 'login'){
			$login_text	= '<p style="clear:both;"></p>'.$login_text;
		}

		?>
		<script type="text/javascript">

		<?php if(!has_action('login_head', 'wp_shake_js')){ ?>
		
		function s(id,pos){g(id).left=pos+'px';}
		function g(id){return document.getElementById(id).style;}
		function shake(id,a,d){c=a.shift();s(id,c);if(a.length>0){setTimeout(function(){shake(id,a,d);},d);}else{try{g(id).position='static';wp_attempt_focus();}catch(e){}}}
		
		<?php } ?>

		function wpjam_shake_form(){
			var p=new Array(15,30,15,0,-15,-30,-15,0);p=p.concat(p.concat(p));var i=document.forms[0].id;g(i).position='relative';shake(i,p,20);
		}

		jQuery(function($){
			$('p.submit').after('<?php echo $login_text; ?>');
			$('p#nav').remove();
		});
		</script>

		<?php
	}

	public static function filter_shake_error_codes($shake_error_codes){
		return array_merge($shake_error_codes, [
			'invalid_code',
			'invalid_openid',
			'invalid_scene',
			'already_binded',
			'invalid_invite'
		]);
	}

	public static function register_api($json){
		if(in_array($json, ['user.signup', 'user.logout'])){
			wpjam_register_api($json, ['template' => WPJAM_SIGNUP_PLUGIN_DIR.'api/'.$json.'.php']);
		}
	}

	public static function filter_weapp_query_unionid(){
		return WPJAM_Signup_Setting::get_instance()->get_setting('weapp_unionid');
	}

	public static function filter_weixin_bind_blog_id($blog_id){
		if(is_null($blog_id)){
			$signup_setting	= get_site_option('wpjam-signup');

			if($signup_setting && !empty($signup_setting['blog_id'])){
				return $signup_setting['blog_id'];
			}
		}

		return $blog_id;
	}

	public static function openid_column_callback($user_id){
		$values = [];

		$signups	= wpjam_get_signups();

		foreach ($signups as $signup) {
			$openid		= $signup['model']::get_user_openid($user_id);
			$values[]	= $openid ? $signup['title'].'：<br />'.$openid : '';
		}

		return $values ? '<p>'.implode('</p><p>', $values).'</p>' : '';
	}

	public static function load_plugin_page($plugin_page){
		if($plugin_page == 'wpjam-bind'){
			foreach(wpjam_get_bind_actions() as $bind_name => $bind_action){
				wpjam_register_plugin_page_tab($bind_name, [
					'title'			=> $bind_action['title'],	
					'function'		=> 'form',	
					'form_name'		=> $bind_name.'_bind',
					'load_callback'	=> ['WPJAM_Signup_Hook', 'load_bind_page']
				]);
			}
		}elseif($plugin_page == 'wpjam-invite'){
			$role_options	= [];

			foreach(get_editable_roles() as $role_key => $role_details){
				$role_options[$role_key]	= translate_user_role($role_details['name']);
			}

			wpjam_register_page_action('invite_user', [
				'submit_text'	=> '生成邀请链接',
				'response'		=> 'append',
				'callback'		=> ['WPJAM_Invite', 'ajax_response'],
				'fields'		=> ['role'=>['title'=>'邀请用户角色',	'type'=>'select',	'options'=>$role_options]],
				'summary'		=> '<strong>操作流程</strong>：

				1. 选择角色并点击生成邀请链接。
				2. 复制链接发给相关用户。
				3. 每个链接只能使用一次。
				4. 支持微信端及电脑端邀请，6小时内有效。'
			]);

			wp_add_inline_style('list-tables', '
				div.response pre {background: #eaeaea; white-space: pre-wrap; word-wrap: break-word; padding:10px;}
				div.response pre code {background: none; margin:0; padding: 0;}');
		}elseif($plugin_page == 'wpjam-signup'){
			$fields = [];

			if(is_multisite() && is_network_admin()){
				$fields['blog_id']	= ['title'=>'服务号博客ID',	'type'=>'number',	'class'=>'',	'description'=>'微信服务号安装的博客站点ID。'];
			}else{
				$signups	= wpjam_get_signups();

				foreach ($signups as $key => $signup) {
					$signup_title	= $signup['title'];

					$sub_fields		= [
						$key.'_login'	=> ['title'=>'',	'type'=>'checkbox',	'value'=>$signup['default'],	'description'=>'支持'.$signup['login_title']],
						$key.'_bind'	=> ['title'=>'',	'type'=>'checkbox',	'value'=>$signup['default'],	'description'=>'支持用户绑定'.$signup_title.'']
					];

					if($key == 'weapp' && isset($signups['weixin'])){
						$sub_fields['weapp_unionid']	=  ['title'=>'',	'type'=>'checkbox',	'value'=>0,	'description'=>$signup_title.'已经加入开放平台'];
					}

					$fields[$key]	= ['title'=>$signup_title,	'type'=>'fieldset',	'fields'=>$sub_fields];
				}
			}

			wpjam_register_option('wpjam-signup', ['fields'=>$fields, 'ajax'=>false]);
		}
	}

	public static function load_bind_page($current_tab){
		$signup_model	= wpjam_get_signup_model($current_tab);

		$user_id		= get_current_user_id();
		$third_user		= $signup_model::get_bind_third_user($user_id);

		$submit_text	= $third_user ? '解除绑定' : '立刻绑定';

		wpjam_register_page_action($current_tab.'_bind', [
			'fields'		=> [$signup_model, 'get_bind_fields'],
			'callback'		=> [$signup_model, 'bind_ajax_response'],
			'submit_text'	=> $submit_text,
			'response'		=> 'redirect'
		]);

		if($current_tab == 'sms'){
			if(!wp_doing_ajax()){
				add_action('admin_footer', [$signup_model, 'send_sms_js']);
			}
		}
	}
}

function wpjam_register_signup($key, $args){
	WPJAM_Signup::register_signup($key, $args);
}

function wpjam_get_signups(){
	return WPJAM_Signup::get_signups();
}

function wpjam_get_signup_model($name){
	return WPJAM_Signup::get_model($name);
}

function wpjam_get_login_actions(){
	return WPJAM_Signup_Setting::get_instance()->get_login_actions();
}

function wpjam_get_bind_actions(){
	return WPJAM_Signup_Setting::get_instance()->get_bind_actions();
}

function wpjam_get_invite_actions(){
	return WPJAM_Signup_Setting::get_instance()->get_invite_actions();
}

function wpjam_invite_user($role, $args=[]){
	return WPJAM_Invite::get_instance()->add($role, $args);
}

function wpjam_validate_invite($invite_key=''){
	$invite	= WPJAM_Invite::get_instance()->exists($invite_key);

	return $invite ?: new WP_Error('invalid_invite', '无效邀请链接或者邀请链接已过期！');
}

function wpjam_get_user_openid($user_id, $type='weixin'){
	$model	= wpjam_get_signup_model($type);

	return $model ? $model::get_user_openid($user_id) : '';
}

function wpjam_get_user_by_openid($openid, $type='weixin'){
	$model	= wpjam_get_signup_model($type);

	return $model ? $model::get_user_by_openid($openid) : null;
}

if(is_multisite()){
	add_filter('pre_option_users_can_register',	'users_can_register_signup_filter');
	add_filter('weixin_bind_blog_id',			['WPJAM_Signup_Hook', 'filter_weixin_bind_blog_id'], 999);
}

add_action('wp_loaded',	function(){
	do_action('wpjam_signup_loaded');
});

add_action('wpjam_api',				['WPJAM_Signup_Hook', 'register_api']);
add_filter('weapp_query_unionid',	['WPJAM_Signup_Hook', 'filter_weapp_query_unionid']);

if(is_login()){
	add_action('wp_loaded', function(){
		if(!wpjam_get_login_actions()){
			return;
		}

		add_action('login_form_login',	['WPJAM_Signup_Hook', 'on_login_form_login']);
		add_action('login_init',		['WPJAM_Signup_Hook', 'on_login_init']);
		add_action('login_footer',		['WPJAM_Signup_Hook', 'on_login_footer'], 999);
		add_filter('shake_error_codes', ['WPJAM_Signup_Hook', 'filter_shake_error_codes']);
	});
}elseif(is_admin()){
	add_action('wpjam_admin_init', function(){
		if(!wpjam_get_signups()){
			return;
		}

		if(wpjam_get_bind_actions()){
			wpjam_add_menu_page('wpjam-bind', [
				'parent'		=> 'users',
				'menu_title'	=> '账号绑定',			
				'capability'	=> 'read',
				'function'		=> 'tab',
				'load_callback'	=> ['WPJAM_Signup_Hook', 'load_plugin_page']
			]);
		}

		if(wpjam_get_invite_actions()){
			wpjam_add_menu_page('wpjam-invite', [
				'parent'		=> 'users',
				'menu_title'	=> '邀请用户',
				'function'		=> 'form',
				'form_name'		=> 'invite_user',
				'load_callback'	=> ['WPJAM_Signup_Hook', 'load_plugin_page'],
			]);

			if(is_multisite() && !current_user_can('manage_sites')){
				add_action('admin_menu',function(){
					remove_submenu_page('users.php', 'user-new.php');
				});

				add_action('load-user-new.php', function(){
					wp_redirect(admin_url('users.php?page=wpjam-invite'));
					exit;
				});
			}
		}

		if(isset($_GET['check'])){
			// wpjam_add_menu_page('wpjam-signup', [
			// 	'parent'		=> 'users',
			// 	'menu_title'	=> '绑定检查',
			// 	'function'		=> 'tab',
			// 	'capability'	=> is_multisite() ? 'manage_sites' : 'manage_options',
			// 	'load_callback'	=> ['WPJAM_Signup_Hook', 'load_plugin_page'],
			// ]);
		}else{
			wpjam_add_menu_page('wpjam-signup', [
				'parent'		=> 'users',
				'menu_title'	=> '登录设置',
				'function'		=> 'option', 
				'option_name'	=> 'wpjam-signup', 
				'network'		=> is_multisite() && is_network_admin() && ($GLOBALS['plugin_page'] == 'wpjam-signup'),
				'load_callback'	=> ['WPJAM_Signup_Hook', 'load_plugin_page']
			]);
		}

		add_action('wpjam_plugin_page_load', function($plugin_page, $current_tab){
			$wpjam_signups	= wpjam_get_signups();

			if(isset($wpjam_signups['weapp']) && $plugin_page == 'weapp-users'){
				wpjam_register_list_table_action('bind_user', [
					'title'			=> '绑定用户',
					'capability'	=> is_multisite() ? 'manage_sites' : 'manage_options',
					'fields'		=> [
						'nickname'	=> ['title'=>'用户',		'type'=>'view'],
						'user_id'	=> ['title'=>'用户ID',	'type'=>'text',	'class'=>'all-options',	'description'=>'请输入 WordPress 的用户']
					],
					'callback'		=> function($id, $data){
						$user_id	= $data['user_id'] ?? 0;
						return	WEAPP_AdminUser::update($id, compact('user_id'));
					}
				]);
			}elseif(isset($wpjam_signups['weixin']) && $plugin_page == 'weixin-users' && $current_tab == 'list'){
				wpjam_register_list_table_action('bind_user', [
					'title'			=> '绑定用户',
					'capability'	=> is_multisite() ? 'manage_sites' : 'manage_options',
					'fields'		=> [
						'nickname'	=> ['title'=>'用户',		'type'=>'view'],
						'user_id'	=> ['title'=>'用户ID',	'type'=>'text',	'class'=>'all-options',	'description'=>'请输入 WordPress 的用户']
					],
					'callback'		=> function($id, $data){
						$user_id	= $data['user_id'] ?? 0;
						return	WEIXIN_AdminUser::update($id, compact('user_id'));
					}
				]);
			}
		}, 10, 2);

		add_action('wpjam_builtin_page_load', function ($screen_base){
			if($screen_base == 'users'){
				wpjam_register_list_table_column('openid', [
					'title'				=> '绑定账号',
					'column_callback'	=> ['WPJAM_Signup_Hook', 'openid_column_callback']
				]);
			}
		});
	});	
}