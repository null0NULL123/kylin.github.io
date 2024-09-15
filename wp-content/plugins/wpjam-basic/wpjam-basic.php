<?php
/*
Plugin Name: WPJAM BASIC
Plugin URI: https://blog.wpjam.com/project/wpjam-basic/
Description: WPJAM 常用的函数和接口，屏蔽所有 WordPress 不常用的功能。
Version: 5.9.6
Requires at least: 5.7
Tested up to: 5.9
Requires PHP: 7.2
Author: Denis
Author URI: http://blog.wpjam.com/
*/
if (version_compare(PHP_VERSION, '7.2.0') < 0) {
	include plugin_dir_path(__FILE__).'old/wpjam-basic.php';
}else{
	define('WPJAM_BASIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
	define('WPJAM_BASIC_PLUGIN_URL', plugin_dir_url(__FILE__));
	define('WPJAM_BASIC_PLUGIN_FILE', __FILE__);

	include __DIR__.'/includes/class-wpjam-model.php';		// Model 类
	include __DIR__.'/includes/class-wpjam-db.php';			// DB 操作类
	include __DIR__.'/includes/class-wpjam-field.php';		// 字段解析类
	include __DIR__.'/includes/class-wpjam-register.php';	// 数据注册类
	include __DIR__.'/includes/class-wpjam-util.php';		// 常用工具类
	include __DIR__.'/includes/class-wpjam-setting.php';	// 选项设置类
	include __DIR__.'/includes/class-wpjam-path.php';		// 路径平台类
	include __DIR__.'/includes/class-wpjam-post.php';		// 文章处理类
	include __DIR__.'/includes/class-wpjam-term.php';		// 分类处理类
	include __DIR__.'/includes/class-wpjam-user.php';		// 用户处理类

	include __DIR__.'/public/wpjam-core.php';		// 核心函数
	include __DIR__.'/public/wpjam-functions.php';	// 常用函数
	include __DIR__.'/public/wpjam-route.php';		// 路由接口
	include __DIR__.'/public/wpjam-basic.php';		// 基础设置
	include __DIR__.'/public/wpjam-notice.php';		// 消息通知
	include __DIR__.'/public/wpjam-cdn.php';		// CDN 处理
	include __DIR__.'/public/wpjam-thumbnail.php';	// 缩略图处理
	include __DIR__.'/public/wpjam-hooks.php';		// 基本优化
	include __DIR__.'/public/wpjam-bind.php';		// 登录绑定
	include __DIR__.'/public/wpjam-compat.php';		// 兼容代码

	if(is_admin()){
		include __DIR__.'/public/wpjam-admin.php';		// 后台入口
		include __DIR__.'/public/wpjam-menus.php';		// 后台菜单	
		include __DIR__.'/public/wpjam-builtins.php';	// 内置页面
		include __DIR__.'/public/wpjam-verify.php';
	}

	do_action('wpjam_loaded');
}