<?php
include_once WPJAM_SMS_PLUGIN_DIR . 'includes/class-mysubmail.php';

function wpjam_mysubmail_send_sms($phone, $args=[]){
	$args	= wp_parse_args($args, [
		'appid'		=> wpjam_mysubmail_get_setting('appid'),
		'appkey'	=> wpjam_mysubmail_get_setting('appkey'),
		'project'	=> '',
		'type'		=> '',
		'code'		=> '',
		'vars'		=> []
	]);

	if(empty($args['appid'])){
		return new WP_Error('empty_appid', '赛邮·云通信 的APPID不能为空');
	}

	if(empty($args['appkey'])){
		return new WP_Error('empty_appkey', '赛邮·云通信 的APPKEY不能为空');
	}

	$type		= $args['type'];
	$project	= $args['project'];

	if($type == 'code'){
		$project	= $project ?: wpjam_mysubmail_get_setting('project');

		$code		= $args['code'];
		$vars		= compact('code');
	}else{
		$project	= $project ?: wpjam_mysubmail_get_setting($type . '_project');
		$vars		= $args['vars'];
	}

	if(empty($project)){
		return new WP_Error('empty_project', '赛邮·云通信 的项目不能为空');
	}

	$mysubmail = new WPJAM_MySubMail($args['appid'], $args['appkey']);

	return $mysubmail->xsend([
		'to'		=> $phone,
		'project'	=> $project,
		'vars'		=> wpjam_json_encode($vars)
	]);
}

function wpjam_mysubmail_get_setting($setting){
	return wpjam_get_setting('wpjam_mysubmail', $setting);
}

function wpjam_mysubmail_sms_update_setting($setting, $value){
	return wpjam_update_setting('wpjam_mysubmail', $setting, $value);
}