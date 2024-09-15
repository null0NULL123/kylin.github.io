<?php
//关掉一些WPJAM插件的扩展功能
if($wpjam_extends = get_option('wpjam-extends')){
	$wpjam_extends_updated	= false;

	//相关文章
	if(!empty($wpjam_extends['related-posts.php'])){
		unset($wpjam_extends['related-posts.php']);
		$wpjam_extends_updated	= true;
	}
	
	if(empty($wpjam_extends['wpjam-postviews.php'])){
		$wpjam_extends['wpjam-postviews.php']	= true;
		$wpjam_extends_updated	= true;
	}

	if($wpjam_extends_updated){
		update_option('wpjam-extends', $wpjam_extends);
	}
}

add_filter('option_wpjam-basic', function ($value){
	$value	= $value ?: [];

	$value['excerpt_optimization']	= $value['excerpt_optimization'] ?? 1;
	$value['excerpt_length']		= $value['excerpt_length'] ?? 115;

	return $value;
});

add_filter('option_wpjam-custom', function ($value){
	$value	= $value ?: [];
	$value['admin_footer']	= 'Powered by <a href="http://www.xintheme.com" target="_blank">新主题 XinTheme</a> + <a href="https://blog.wpjam.com/" target="_blank">WordPress 果酱</a>';

	return $value;
});

add_filter('wpjam_post_thumbnail_url', function($post_thumbnail_url, $post){
	if(get_post_meta($post->ID, 'header_img', true)){
		return get_post_meta($post->ID, 'header_img', true);
	}elseif($post_thumbnail_url){
		return $post_thumbnail_url;
	}else{
		return wpjam_get_post_first_image($post->post_content);
	}
},10,2);

add_action('init', function(){
	foreach([
		'add_4_theme'	=> 'add_topic',
		'banner_title'	=> 'topic_title',
		'banner_desc'	=> 'topic_ms',
		'theme_banner'	=> 'topic_bg_img',
	] as $topic_key => $theme_key){
		if(wpjam_topic_get_setting($topic_key) === null){
			$value	= wpjam_theme_get_setting($theme_key);

			if($value !== null){
				wpjam_topic_update_setting($topic_key, $value);
			}
		}
	}

	if($thumbnails	= wpjam_theme_get_setting('thumbnails')){
		wpjam_delete_setting('wpjam_theme', 'thumbnails');
		wpjam_update_setting('wpjam-thumbnail', 'default', $thumbnails);
	}
});


if(is_admin()){
	add_filter('wpjam_option_setting_args', function($args, $name){
		if($name == 'wpjam-extends'){
			unset($args['sections']['wpjam-extends']['fields']['related-posts.php']);
			unset($args['sections']['wpjam-extends']['fields']['wpjam-postviews.php']);
			unset($args['sections']['wpjam-extends']['fields']['mobile-theme.php']);
		}elseif($name == 'wpjam-thumbnail'){
			unset($args['sections']['wpjam-thumbnail']['fields']['term_set']);
		}elseif($name == 'wpjam-topics'){
			$args['sections']['wpjam-topics']['fields']['banner']		= ['title'=>'前端',	'type'=>'fieldset',	'fields'=>[
				'add_4_theme'	=>['title'=>'按钮',		'type'=>'checkbox',	'description'=>'前端用户中心和讨论组列表页显示发帖按钮'],
				'banner_title'	=>['title'=>'标题',		'type'=>'text',		'placeholder'=>'小论坛'],
				'banner_desc'	=>['title'=>'描述',		'type'=>'text',		'placeholder'=>'all-需要技术支持还是只想打个招呼？'],
				'theme_banner'	=>['title'=>'背景图',		'type'=>'text',		'type'=>'img',	'item_type'=>'url',	'size'=>'1920x300',	'description'=>'建议尺寸：1920*300'],
				'theme_url'		=>['title'=>'前台地址',	'type'=>'view',		'value'=>''.make_clickable(user_trailingslashit(home_url('/topic')))]
			]];
		}

		return $args;
	}, 99, 2);
}