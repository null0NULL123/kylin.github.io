<?php

add_filter('wpjam_theme_setting', function(){

	$login_sub_fields		= [
		'navbar_user'		=> '菜单栏增加用户登录注册按钮',
		//'login_register'	=> '开启用户注册',
		'no_wp_admin'		=> '禁止非管理员账号访问后台  /wp-admin',
		'show_only_login'	=> '整站，登录后可见（禁止未登录用户查看网站）',
		'subscriber_ft'		=> '订阅者权限不可发布帖子(讨论组)',
		'subscriber_fw'		=> '订阅者权限不可发布文章',
		'close_vercode'		=> '关闭「登录/注册」页面图形验证码功能',
	];

	$login_sub_fields		= array_map(function($desc){return ['title'=>'','type'=>'checkbox','description'=>$desc]; }, $login_sub_fields);

	$fields	= [

		'login'				=> ['title'=>'登录/注册',	'type'=>'fieldset',	'fields'=>$login_sub_fields],

		'login_logo'		=> ['title'=>'登录页面-Logo', 'type'=>'img',	'item_type'=>'url'],
		'login_title'		=> ['title'=>'登录页面-标题',	 'type'=>'text', 'rows'=>4],
		'login_container'	=> ['title'=>'登录页面-描述内容，可使用html', 'type'=>'textarea'],
		'login_bg_img'		=> ['title'=>'登录页面-背景图像', 'type'=>'img',	'item_type'=>'url'],
		'register_agree'	=> ['title'=>'用户注册协议', 'type'=>'textarea', 'description'=>'可使用html标签，例如：注册即代表阅读并同意 &lt;a href="#"&gt;《新主题用户协议》&lt;/a&gt;'],

	];

	return compact('fields');

});