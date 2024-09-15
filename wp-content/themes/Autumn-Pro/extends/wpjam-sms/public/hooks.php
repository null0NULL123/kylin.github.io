<?php
wpjam_register_sms_provider('aliyun', [
	'title'		=> '阿里云短信服务', 
	'file'		=> WPJAM_SMS_PLUGIN_DIR.'public/aliyun/aliyun.php',
	'admin'		=> WPJAM_SMS_PLUGIN_DIR.'public/aliyun/admin.php',
	'sender'	=> 'wpjam_aliyun_send_sms',
]);

wpjam_register_sms_provider('aliyun', [
	'title'		=> '阿里云国际短信服务', 
	'file'		=> WPJAM_SMS_PLUGIN_DIR.'public/aliyun/aliyun.php',
	'admin'		=> WPJAM_SMS_PLUGIN_DIR.'public/aliyun/admin.php',
	'sender'	=> 'wpjam_aliyun_send_sms',
],'international');

wpjam_register_sms_provider('mysubmail', [
	'title'		=> '赛邮·云通信', 
	'file'		=> WPJAM_SMS_PLUGIN_DIR.'public/domestic/mysubmail/mysubmail.php',
	'admin'		=> WPJAM_SMS_PLUGIN_DIR.'public/domestic/mysubmail/admin.php',
	'sender'	=>'wpjam_mysubmail_send_sms',
]);

wpjam_register_sms_provider('mysubmail', [
	'title'		=> '国际赛邮·云通信', 
	'file'		=> WPJAM_SMS_PLUGIN_DIR.'public/international/mysubmail/mysubmail.php',
	'admin'		=> WPJAM_SMS_PLUGIN_DIR.'public/international/mysubmail/admin.php',
	'sender'	=> 'wpjam_international_submail_send_sms'
],'international');

add_action('wpjam_api', function($json){
	if(strpos($json, 'sms.') === 0) {
		wpjam_register_api($json, ['template' => WPJAM_SMS_PLUGIN_DIR.'api/'.$json.'.php']);
	}
});

if(did_action('wpjam_debug_loaded')){
	wpjam_register_debug_type('sms', [
		'name'		=> '短信插件警告',
		'callback'	=> function($args){
			return strpos($args['caller'], 'WPJAM_SMS') !== false || strpos($args['file'], 'wpjam-sms') !== false;
		}
	]);
}

add_action('wpjam_signup_loaded', function(){
	if(!wpjam_sms_get_setting('sms_provider')){
		return;
	}
	
	include WPJAM_SMS_PLUGIN_DIR . 'includes/class-sms-signup.php';
	include WPJAM_SMS_PLUGIN_DIR . 'includes/class-user-phone.php';

	wpjam_register_signup('sms', [
		'title'			=>'手机号码',	
		'login_title'	=>'手机短信验证码登录',	
		'model'			=>'SMS_Signup',	
		'default'		=>false
	]);

	function sms_signup($phone, $code, $args=[]){
		return SMS_Signup::code_signup($phone, $code, $args);
	}

	add_action('wp_ajax_nopriv_send-sms',	['SMS_Signup', 'ajax_send_sms']);
	add_action('wp_ajax_send-sms',			['SMS_Signup', 'ajax_send_sms']);

	add_action('wp_ajax_nopriv_sms-signup',	['SMS_Signup', 'ajax_sms_signup']);
	add_action('wp_ajax_sms-signup',		['SMS_Signup', 'ajax_sms_signup']);

	add_action('wp_ajax_sms-bind',			['SMS_Signup', 'ajax_sms_bind']);
	add_action('wp_ajax_sms-unbind',		['SMS_Signup', 'ajax_sms_unbind']);
});

add_action('wp_loaded',	function(){
	do_action('wpjam_sms_loaded');
});

if(is_admin()){
	add_action('wpjam_admin_init', function(){
		wpjam_add_menu_page('wpjam-sms', [
			'parent'		=> 'users',
			'menu_title'	=> '短信设置',			
			'function'		=> 'tab',
			'load_callback'	=> ['WPJAM_SMS', 'load_plugin_page']
		]);
	});
}


