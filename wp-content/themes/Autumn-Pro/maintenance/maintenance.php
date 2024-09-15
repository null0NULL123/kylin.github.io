<?php
// 网站维扩护503代码
if(!is_admin() && wpjam_theme_get_setting('maintenance_show') ){
	
	add_action('wp_loaded', function (){
		global $pagenow;

		if (current_user_can('manage_options')  || $pagenow == 'wp-login.php') {
			return;
		}
		
		header( $_SERVER["SERVER_PROTOCOL"] . ' 503 Service Temporarily Unavailable', true, 503 );
		header('Content-Type:text/html;charset=utf-8');

		require TEMPLATEPATH.'/maintenance/index.php';

		exit;
	});
}