<?php
include_once WPJAM_SMS_PLUGIN_DIR . 'includes/class-mysubmail.php';

function wpjam_international_submail_send_sms($phone, $args=[]){
	$args	= wp_parse_args($args, [
		'appid'		=> wpjam_international_submail_get_setting('appid'),
		'appkey'	=> wpjam_international_submail_get_setting('appkey'),
		'project'	=> '',
		'type'		=> '',
		'code'		=> '',
		'vars'		=> []
	]);

	if(empty($args['appid'])){
		return new WP_Error('empty_appid', '国际赛邮·云通信 的APPID不能为空');
	}

	if(empty($args['appkey'])){
		return new WP_Error('empty_appkey', '国际赛邮·云通信 的APPKEY不能为空');
	}

	$type		= $args['type'];
	$project	= $args['project'];

	if($type == 'code'){
		$project	= $project ?: wpjam_international_submail_get_setting('project');

		$code		= $args['code'];
		$vars		= compact('code');
	}else{
		$project	= $project ?: wpjam_international_submail_get_setting($type . '_project');
		$vars		= $args['vars'];
	}

	if(empty($project)){
		return new WP_Error('empty_project', '国际赛邮·云通信 的项目不能为空');
	}

	$mysubmail = new WPJAM_MySubMail($args['appid'], $args['appkey']);

	return $mysubmail->xsend_international([
		'to'		=> $phone,
		'project'	=> $project,
		'vars'		=> wpjam_json_encode($vars)
	]);
}

function wpjam_international_submail_get_setting($setting){
	return wpjam_get_setting('wpjam_international_submail', $setting);
}

function wpjam_international_submail_update_setting($setting, $value){
	return wpjam_get_setting('wpjam_international_submail', $setting, $value);
}