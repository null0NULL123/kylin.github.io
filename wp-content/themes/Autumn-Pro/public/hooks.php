<?php

//整个网站  登录后可见
if(wpjam_theme_get_setting('show_only_login')){

	add_action('template_redirect',	function($xintheme_show_only_login) {
		//判断登录
		if( !is_module('user') && !is_user_logged_in() ){
			auth_redirect(); //跳转到登录页面
			exit();
		}
		return $xintheme_show_only_login;
	});

}

add_action('registered_post_type', function($post_type){
	if($post_type == 'post'){
		add_post_type_support($post_type, 'favs');
		add_post_type_support($post_type, 'likes');
	}
});

add_action('after_setup_theme', function(){
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('post-formats', ['gallery','video','audio']);

	register_nav_menus(['main' =>'主菜单', 'd1' =>'底部菜单①', 'd2' =>'底部菜单②', 'd3' =>'底部菜单③']);

	register_sidebar([
		'name'			=> '全站侧栏',
		'id'			=> 'widget_right',
		'before_widget'	=> '<section class="widget %2$s">', 
		'after_widget'	=> '</section>', 
		'before_title'	=> '<h5 class="widget-title">', 
		'after_title'	=> '</h5>' 
	]);
	register_sidebar([
		'name'			=> '首页侧栏',
		'id'			=> 'widget_home',
		'before_widget'	=> '<section class="widget %2$s">', 
		'after_widget'	=> '</section>', 
		'before_title'	=> '<h5 class="widget-title">', 
		'after_title'	=> '</h5>' 
	]);
	register_sidebar([
		'name'			=> '文章页侧栏',
		'id'			=> 'widget_post',
		'before_widget'	=> '<section class="widget %2$s">', 
		'after_widget'	=> '</section>', 
		'before_title'	=> '<h5 class="widget-title">', 
		'after_title'	=> '</h5>' 
	]);
	register_sidebar([
		'name'			=> '分类/标签/搜索页侧栏',
		'id'			=> 'widget_other',
		'before_widget'	=> '<section class="widget %2$s">', 
		'after_widget'	=> '</section>', 
		'before_title'	=> '<h5 class="widget-title">', 
		'after_title'	=> '</h5>' 
	]);
	register_sidebar([
		'name'			=> '页面侧栏',
		'id'			=> 'widget_page',
		'before_widget'	=> '<section class="widget %2$s">', 
		'after_widget'	=> '</section>', 
		'before_title'	=> '<h5 class="widget-title">', 
		'after_title'	=> '</h5>' 
	]);
});

add_filter('wpjam_template', function($wpjam_template, $module){
	if($module == 'user'){
		return get_template_directory().'/user/index.php';
	}

	return $wpjam_template;
}, 10, 2);

add_filter('wpjam_rewrite_rules', function ($rules){
	$rules['user/posts/page/?([0-9]{1,})$']	= 'index.php?module=user&action=posts&paged=$matches[1]';
	$rules['user/([^/]+)$']					= 'index.php?module=user&action=$matches[1]';
	$rules['user/page/?([0-9]{1,})$']		= 'index.php?module=user&action=posts&paged=$matches[1]';
	$rules['user$']							= 'index.php?module=user&action=posts';

	return $rules;
});

if( wpjam_get_setting('wpjam_theme', 'foot_link') ) {
	add_filter('pre_option_link_manager_enabled', '__return_true');	/*激活友情链接后台*/
}

//载入JS\CSS
add_action('wp_enqueue_scripts', function () {
	if (!is_admin()) {

		$ver	= wp_get_theme()->get('Version');

		wp_deregister_script('jquery');
		wp_enqueue_script('jquery', "https://cdn.staticfile.org/jquery/3.3.1/jquery.min.js", false, null);
		
		global $wp_query; 

		if(!did_action('wpjam_static')){
			$theme_color	= wpjam_theme_get_setting('theme_color') ?: '#f16b6f';

			wp_enqueue_style('style', get_stylesheet_directory_uri().'/static/css/style.css', [], $ver);
			wp_enqueue_style('fonts', get_stylesheet_directory_uri().'/static/fonts/iconfont.css', [], $ver);

			//页面模块样式
			wp_enqueue_style('module-style', get_stylesheet_directory_uri().'/module-page/css/style.css', [], $ver);
			

			wp_add_inline_style('style', 'html{--accent-color:'.$theme_color.'}');
			
			wp_deregister_script('jquery-migrate');
			wp_enqueue_script('jquery-migrate', "https://cdn.staticfile.org/jquery-migrate/3.0.1/jquery-migrate.min.js", false, null);

			wp_enqueue_script('autumn',	get_stylesheet_directory_uri() . '/static/js/autumn.min.js', ['jquery'], $ver, true);
			wp_localize_script('autumn', 'site_url', [
				'home_url'=>home_url(),
				'admin_url'=>admin_url('admin-ajax.php')
			]);
		}else{
			wp_localize_script('wpjam-static-script', 'xintheme', [
				'ajaxurl'		=> admin_url('admin-ajax.php'),
				'query'			=> wpjam_json_encode($wp_query->query),
				'current_page'	=> get_query_var('paged') ?: 1,
				'max_page'		=> $wp_query->max_num_pages ?? 0,
				'paging_type'	=> wpjam_theme_get_setting('paging_xintheme'),
				'close_vercode'	=> wpjam_theme_get_setting('close_vercode') ?: 0
			]);

			wp_localize_script('wpjam-static-script', 'site_url', [
				'home_url'	=>home_url(),
				'admin_url'	=>admin_url('admin-ajax.php')
			]);
		}

		if (is_singular()){
			if(comments_open() && get_option('thread_comments')){
				wp_enqueue_script('comment-reply');
			}

			wp_enqueue_script('require',	get_stylesheet_directory_uri() . '/static/js/require.min.js', ['jquery'], '', true);
			
			wp_enqueue_style('fancybox', 'https://cdn.staticfile.org/fancybox/3.5.7/jquery.fancybox.min.css');
			wp_enqueue_script('fancybox', 'https://cdn.staticfile.org/fancybox/3.5.7/jquery.fancybox.min.js', ['jquery'], '', true);
		}

		if(!did_action('wpjam_static')){
			wp_enqueue_script( 'xintheme_ajax', get_stylesheet_directory_uri() . '/static/js/ajax.js', ['jquery'], $ver, true );
			wpjam_localize_script('xintheme_ajax', 'xintheme', [
				'ajaxurl'		=> admin_url('admin-ajax.php'),
				'query'			=> wpjam_json_encode($wp_query->query),
				'current_page'	=> get_query_var('paged') ?: 1,
				'max_page'		=> $wp_query->max_num_pages ?? 0,
				'paging_type'	=> wpjam_theme_get_setting('paging_xintheme'),
				'close_vercode'	=> wpjam_theme_get_setting('close_vercode') ?: 0
			]);
		}
	}	
}, 11);

add_action('init', function(){
	if(did_action('wpjam_static')){
		wpjam_register_static('autumn-style', [
			'type'		=> 'style',
			'source'	=> 'file',
			'file' 		=> get_stylesheet_directory().'/static/css/style.css',
			'baseurl'	=> get_stylesheet_directory_uri().'/static/css/'
		]);

		wpjam_register_static('autumn-iconfont', [
			'type'		=> 'style',
			'source'	=> 'file',
			'file' 		=> get_stylesheet_directory().'/static/fonts/iconfont.css',
			'baseurl'	=> get_stylesheet_directory_uri().'/static/fonts/'
		]);

		$theme_color	= wpjam_theme_get_setting('theme_color') ?: '#f16b6f';
		wpjam_register_static('autumn-inline-style', [
			'type'		=> 'style',
			'source'	=> 'value',
			'value'		=> 'html{--accent-color:'.$theme_color.'}'
		]);

		wpjam_register_static('autumn-ajax', [
			'type'		=> 'script',
			'source'	=> 'file',
			'file' 		=> get_stylesheet_directory().'/static/js/ajax.js'
		]);

		wpjam_register_static('autumn-script', [
			'type'		=> 'script',
			'source'	=> 'file',
			'file' 		=> get_stylesheet_directory().'/static/js/autumn.min.js'
		]);
	}
});

// //删除菜单多余css class
// function wpjam_css_attributes_filter($classes) {
// 	return is_array($classes) ? array_intersect($classes, array('current-menu-item','current-post-ancestor','current-menu-ancestor','current-menu-parent','menu-item-has-children','menu-item')) : '';
// }
// add_filter('nav_menu_css_class',	'wpjam_css_attributes_filter', 100, 1);
// add_filter('nav_menu_item_id',		'wpjam_css_attributes_filter', 100, 1);
// add_filter('page_css_class', 		'wpjam_css_attributes_filter', 100, 1);

add_filter('body_class',function ($classes) {
	//固定导航
	if ( wpjam_get_setting('wpjam_theme', 'navbar_sticky') ){
		$classes[]	= 'navbar-sticky';
	}

	//暗黑风格
	if(wpjam_get_setting('wpjam_theme', 'dark_mode')){
		$classes[]	= 'dark-mode';
	}

	//启用宽版
	if(wpjam_get_setting('wpjam_theme', 'width_1500')){
		$classes[]	= 'width_1500';
	}

	//前端切换暗黑模式 body 添加class
	if(isset($_COOKIE['dahuzi_site_style']) && $_COOKIE['dahuzi_site_style'] == 'dark'){
		$classes[] = 'dark-mode';
	}

	//首页图片轮播
	if( wpjam_theme_get_setting('slide_type') == 'img' ){
		$classes[]	= 'with-hero hero-gallery';
	}

	return $classes;
});

//删除wordpress默认相册样式
add_filter( 'use_default_gallery_style', '__return_false' );



/* 评论作者链接新窗口打开 */
add_filter('get_comment_author_link', function () {
	$url	= get_comment_author_url();
	$author = get_comment_author();
	if ( empty( $url ) || 'http://' == $url ){
		return $author;
	}else{
		return "<a target='_blank' href='$url' rel='external nofollow' class='url'>$author</a>";
	}
});

//fancybox3图片添加 data-fancybox
add_filter( 'the_content', function ( $content ) {
	//fancybox3图片添加 data-fancybox
	global $post;
	$pattern = "/<a(.*?)href=('|\")([^>]*)(.bmp|.gif|.jpeg|.jpg|.png|.swf)('|\")(.*?)>(.*?)<\/a>/i";
	$replacement = '<a$1href=$2$3$4$5 data-fancybox="images" $6>$7</a>';
	$content = preg_replace($pattern, $replacement, $content);
	$content = str_replace(']]>', ']]>', $content);
	return $content;
});

//在文章内容的第二段后面插入广告
if(wpjam_theme_get_setting('single_middle_ad')){
	add_filter('the_content', function ($content) {
		$ad_code	= wpjam_theme_get_setting('single_middle_ad');
		$ad_code	= $ad_code ? '<p style="text-indent:0;">'.$ad_code.'</p>' : '';
		$ad_number	= wpjam_theme_get_setting('single_middle_ad_number') ?: '4';
		if ( is_singular('post') && ! is_admin() ) {
			$paragraphs	= explode( '</p>', $content );
			foreach ($paragraphs as $index => $paragraph) {
				if ( trim( $paragraph ) ) {
					$paragraphs[$index] .= '</p>';
				}
				if ( $ad_number == $index + 1 ) {
					$paragraphs[$index] .= $ad_code;
				}
			}
			$content = implode( '', $paragraphs );
		}
		return $content;
	});
}

/*
//修复 WordPress 找回密码提示“抱歉，该key似乎无效”
add_filter('retrieve_password_message', function ( $message, $key ) {
	if ( strpos($_POST['user_login'], '@') ) {
		$user_data = get_user_by('email', trim($_POST['user_login']));
	} else {
		$login = trim($_POST['user_login']);
		$user_data = get_user_by('login', $login);
	}
	
	$user_login = $user_data->user_login;
	$msg	= __('有人要求重设如下帐号的密码：'). "\r\n\r\n";
	$msg	.= network_site_url() . "\r\n\r\n";
	$msg	.= sprintf(__('用户名：%s'), $user_login) . "\r\n\r\n";
	$msg	.= __('若这不是您本人要求的，请忽略本邮件，一切如常。') . "\r\n\r\n";
	$msg	.= __('要重置您的密码，请打开下面的链接：'). "\r\n\r\n";
	$msg	.= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') ;

	return $msg;
}, null, 2);
*/



// 在文章编辑页面的[添加媒体]只显示用户自己上传的文件
add_action('pre_get_posts', function ( $wp_query_obj ) {
	global $current_user, $pagenow;
	if( !is_a( $current_user, 'WP_User') )
		return;
	if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' )
		return;
	if( !current_user_can( 'manage_options' ) && !current_user_can('manage_media_library') )
		$wp_query_obj->set('author', $current_user->ID );
	return;
});
// 结束

add_action('pre_get_posts',	function($wp_query) {
	global $current_user, $pagenow;

	if($wp_query->is_main_query()){
		if(is_module('user', 'posts')){
			$wp_query->set('ignore_sticky_posts', true);
			$wp_query->set('post_type', 'post');
			$wp_query->set('post_status', 'any');
			$wp_query->set('author',	get_current_user_id());
		}elseif(is_home()){
			//$wp_query->set('ignore_sticky_posts',true);
		}elseif(is_search()){
			if(!is_admin()){
				$wp_query->set('post_type', 'post');	//搜索结果排除所有页面
			}
		}elseif(is_tax('group')){
			if(!is_admin()){
				$wp_query->set('post_type', 'topic');	//搜索结果排除所有页面
			}
		}
	}

	return $wp_query;
});

/* 搜索关键词为空 */
add_filter( 'request', function ( $query_variables ) {
	if (isset($_GET['s']) && !is_admin()) {
		if (empty($_GET['s']) || ctype_space($_GET['s'])) {
			wp_redirect( home_url() );
			exit;
		}
	}
	return $query_variables;
});

//删除分类描述P标签 http://www.xintheme.com/wpjiaocheng/49754.html
add_filter('category_description', function($description) {
  $description	= trim($description);
  $description	= wp_strip_all_tags($description);
  return $description;
}); 

//去除自带小工具
add_action("widgets_init", function() {
   //unregister_widget("WP_Widget_Pages");//页面
   //unregister_widget("WP_Widget_Calendar");//文章日程表
   //unregister_widget("WP_Widget_Archives");//文章归档
   //unregister_widget("WP_Widget_Meta");//登入/登出，管理，Feed 和 WordPress 链接
   //unregister_widget("WP_Widget_Search");//搜索
   //unregister_widget("WP_Widget_Categories");//分类目录
   //unregister_widget("WP_Widget_Recent_Posts");//近期文章
   //unregister_widget("WP_Widget_Recent_Comments");//近期评论
   unregister_widget("WP_Widget_RSS");//RSS订阅
   //unregister_widget("WP_Widget_Links");//链接
   //unregister_widget("WP_Widget_Text");//文本
   //unregister_widget("WP_Widget_Tag_Cloud");//标签云
   //unregister_widget("WP_Nav_Menu_Widget");//自定义菜单
   //unregister_widget("WP_Widget_Media_Audio");//音频
   //unregister_widget("WP_Widget_Media_Image");//图片
   //unregister_widget("WP_Widget_Media_Video");//视频
   //unregister_widget("WP_Widget_Media_Gallery");//画廊
});

//重定向wordpress登录页面
if( !wpjam_theme_get_setting('maintenance_show') && get_option('users_can_register') ){
	add_action('init',function() {
		global $pagenow;
		if( $pagenow == "wp-login.php" && $_GET['action']!="logout") {
			if(!is_user_logged_in()){
				wp_redirect(home_url(user_trailingslashit('/user/login')));
				exit;
			}else{
			    wp_redirect(home_url(user_trailingslashit('/user')));
                exit;
			}
		}
	});
}

//非管理员用户禁止访问后台 /wp-admin
if( !wpjam_theme_get_setting('maintenance_show') && wpjam_theme_get_setting('no_wp_admin') ){

	if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
 		$manage_options = current_user_can( 'manage_options' );
 		if(!$manage_options) {
			wp_safe_redirect( home_url(user_trailingslashit('/user')) );
			exit();
		}
	}

}

//直接去掉函数 comment_class() 和 body_class() 中输出的 "comment-author-" 和 "author-"
//避免 WordPress 登录用户名被暴露 
function xintheme_comment_body_class($content){
    $pattern = "/(.*?)([^>]*)author-([^>]*)(.*?)/i";
    $replacement = '$1$4';
    $content = preg_replace($pattern, $replacement, $content);
    return $content;
}
add_filter('comment_class', 'xintheme_comment_body_class');
add_filter('body_class', 'xintheme_comment_body_class');

//延迟加载默认图像
function xintheme_lazysizes(){
	if( wpjam_theme_get_setting('img_lazysizes') ){
		return 'src="'.wpjam_theme_get_setting('img_lazysizes').'"';
	}else{
		return 'src="'.get_template_directory_uri().'/static/images/loading.gif"';
	}
}

//自动添加暗箱标签属性
add_filter('the_content', 'pirobox_gall_replace');
function pirobox_gall_replace ($content){
    global $post;
    $pattern = "/<a(.*?)href=('|\")([^>]*).(bmp|gif|jpeg|jpg|png)('|\")(.*?)>(.*?)<\/a>/i";
    $replacement = '<a$1href=$2$3.$4$5 data-fancybox="images"$6>$7</a>';
    $content = preg_replace($pattern, $replacement, $content);
    
    return $content;
}

//修复WordPress定时发布失败
function pubMissedPosts() {
	if (is_front_page() || is_single()) {
		global $wpdb;
		$now=gmdate('Y-m-d H:i:00');
	
    	$args=array(
        	'public'                => true,
	        'exclude_from_search'   => false,
    	    '_builtin'              => false
	    ); 
    	$post_types = get_post_types($args,'names','and');
		$str=implode ('\',\'',$post_types);

		if ($str) {
			$sql="Select ID from $wpdb->posts WHERE post_type in ('post','page','$str') AND post_status='future' AND post_date_gmt<'$now'";
		}
		else {$sql="Select ID from $wpdb->posts WHERE post_type in ('post','page') AND post_status='future' AND post_date_gmt<'$now'";}

		$resulto = $wpdb->get_results($sql);
 		if($resulto) {
			foreach( $resulto as $thisarr ) {
				wp_publish_post($thisarr->ID);
			}
		}
	}
}
add_action('wp_head', 'pubMissedPosts'); 

include_once TEMPLATEPATH.'/admin/updater/theme-updater.php'; // 授权+在线更新


add_filter('excerpt_more', function ($more) {
    return ' ...';
});
