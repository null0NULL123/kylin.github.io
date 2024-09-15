<?php
include WPJAM_SMS_PLUGIN_DIR . 'includes/class-aliyun-sms.php';

function wpjam_aliyun_send_sms($phone, $args=[]){
	$args	= wp_parse_args($args, [
		'access_key_id'		=> wpjam_aliyun_sms_get_setting('access_key_id'),
		'access_key_secret'	=> wpjam_aliyun_sms_get_setting('access_key_secret'),
		'sign_name'			=> wpjam_aliyun_sms_get_setting('sign_name'),
		'template'			=> '',
		'international'		=> false,
		'type'				=> '',
		'code'				=> '',
		'vars'				=> []
	]);

	if(empty($args['access_key_id'])){
		return new WP_Error('empty_access_key_id', '阿里云短信服务的 Access Key ID 不能为空');
	}

	if(empty($args['access_key_secret'])){
		return new WP_Error('empty_access_key_secret', '阿里云短信服务的 Access Key Secret 不能为空');
	}

	$sign_name	= $args['sign_name'];

	if(empty($sign_name)){
		return new WP_Error('empty_sign_name', '阿里云短信服务的签名不能为空');
	}

	$type		= $args['type'];
	$template	= $args['template'];

	$international	= $args['international'];

	if($type == 'code'){
		if(empty($template)){
			$template_key	= $international ? $type.'_international_template' : 'template';
			$template		= wpjam_aliyun_sms_get_setting($template_key);
		}

		$code		= $args['code'];
		$vars		= compact('code');
	}else{
		if(empty($template)){
			$template_key	= $international ? $type.'_international_template' : $type.'_template';
			$template		= wpjam_aliyun_sms_get_setting($template_key);
		}

		$vars		= $args['vars'];
	}

	if(empty($template)){
		return new WP_Error('empty_template', '阿里云短信服务的模板CODE不能为空');
	}

	$aliyun_sms = new WPJAM_AliyunSMS($args['access_key_id'], $args['access_key_secret']);

	return $aliyun_sms->send([
		'PhoneNumbers'	=> $phone,
		'SignName'		=> $sign_name,
		'TemplateCode'	=> $template,
		'TemplateParam'	=> wpjam_json_encode($vars)
	]);
}

function wpjam_aliyun_sms_get_setting($setting){
	return wpjam_get_setting('wpjam_aliyun_sms', $setting);
}

function wpjam_aliyun_sms_update_setting($setting, $value){
	return wpjam_update_setting('wpjam_aliyun_sms', $setting, $value);
}

function wpjam_get_aliyun_sms_templates(){
	return WPJAM_AliyunSMS::get_templates();
}

function wpjam_register_aliyun_sms_template($key, $args){
	return WPJAM_AliyunSMS::register_template($key, $args);
}