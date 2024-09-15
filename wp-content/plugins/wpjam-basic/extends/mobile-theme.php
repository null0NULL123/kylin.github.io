<?php
/*
Name: 移动主题
URI: https://blog.wpjam.com/m/mobile-theme/
Description: 给当前站点设置移动设备设置上使用单独的主题。
Version: 1.0
*/
class WPJAM_Mobile_Theme{
	private static $template	= null;

	public static function filter_stylesheet($stylesheet){
		return wpjam_basic_get_setting('mobile_stylesheet');
	}

	public static function filter_template($template){
		if(is_null(self::$template)){
			$stylesheet		= wpjam_basic_get_setting('mobile_stylesheet');
			self::$template	= wp_get_theme($stylesheet)->get_template();
		}

		return self::$template;
	}

	public static function load_option_page(){
		$current_theme	= wp_get_theme();
		$theme_options	= [];
		
		$theme_options[$current_theme->get_stylesheet()]	= $current_theme->get('Name');

		foreach(wp_get_themes() as $theme){
			$theme_options[$theme->get_stylesheet()]	= $theme->get('Name');
		}

		wpjam_register_option('wpjam-basic', [
			'fields'	=> ['mobile_stylesheet'=>['title'=>'选择移动主题',	'type'=>'select',	'options'=>$theme_options]],
			'summary'	=> '使用手机和平板访问网站的用户将看到以下选择的主题界面，而桌面用户依然看到 <strong>'.$current_theme->get('Name').'</strong> 主题界面。'
		]);
	}
}

add_action('plugins_loaded', function(){
	if(wp_is_mobile() && wpjam_basic_get_setting('mobile_stylesheet')){
		add_filter('stylesheet',	['WPJAM_Mobile_Theme', 'filter_stylesheet']);
		add_filter('template',		['WPJAM_Mobile_Theme', 'filter_template']);
	}

	if(is_admin()){
		wpjam_add_menu_page('mobile-theme', [
			'menu_title'	=> '移动主题',
			'parent'		=> 'themes',
			'function'		=> 'option',
			'option_name'	=> 'wpjam-basic',
			'load_callback'	=> ['WPJAM_Mobile_Theme', 'load_option_page']
		]);
	}
}, 0);
