<?php

/* 移除后台 外观 下面的编辑菜单
add_action('admin_init', function () {
	remove_submenu_page('themes.php', 'theme-editor.php');
}, 999);
*/

if(PHP_VERSION >= 7.2){
	add_filter('wpjam_pages', function ($wpjam_pages){
		$wpjam_pages['autumn'] = [
			'menu_title'	=> 'Autumn',
			'icon'			=> 'dashicons-hammer',
			'position'		=> '59',	
			'subs'			=> [
				'autumn'		=> [
					'menu_title'	=> '基础设置',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-setting.php',
				],
				'autumn-home'	=> [
					'menu_title'	=> '首页设置',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-home.php',
				],
				'autumn-single'	=> [
					'menu_title'	=> '文章页面',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-single.php',
				],
				'autumn-footer'	=> [
					'menu_title'	=> '页脚设置',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-footer.php',
				],
				'autumn-mobile'	=> [
					'menu_title'	=> '手机端设置',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-mobile.php',
				],
				'autumn-login'	=> [
					'menu_title'	=> '登录/注册',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-login.php',
				],
				'autumn-social'	=> [
					'menu_title'	=> '社交工具',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-social.php',
				],
				'autumn-ad'	=> [
					'menu_title'	=> '广告设置',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-ad.php',
				],
				'autumn-maintenance'	=> [
					'menu_title'	=> '维护模式',
					'function'		=> 'option',
					'option_name'	=> 'wpjam_theme',
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-maintenance.php',
				],

				'autumn-iconfont'	=> [
					'menu_title'	=> '主题图标',	
					'page_file'		=> TEMPLATEPATH .'/admin/pages/autumn-iconfont.php',		
				]

			]		
		];

		//unset($wpjam_pages['wpjam-basic']['subs']['wpjam-thumbnail']);
		return $wpjam_pages;
	});
}