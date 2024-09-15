<?php
function wpjam_send_sms($phone, $args=[]){
	if(preg_match('/^((\+86)|(86))?(1)\d{10}$/', $phone)){
		$provider	= $args['sms_provider'] ?? wpjam_sms_get_setting('sms_provider');
		$sender		= wpjam_include_sms_provider($provider);
	}else{
		$provider	= $args['international_sms_provider'] ?? wpjam_sms_get_setting('international_sms_provider');
		$sender		= wpjam_include_sms_provider($provider, 'international');

		$args['international']	= true;
	}

	if(is_wp_error($sender)){
		return $sender;
	}

	$args['sender']	= $sender;

	return WPJAM_SMS::send($phone, $args);
}

function wpjam_verify_sms($phone, $phone_code){
	return WPJAM_SMS::verify($phone, $phone_code);
}

function wpjam_include_sms_provider($key, $type='domestic', $admin=false){
	return WPJAM_SMS::include_provider($key, $type, $admin);
}

function wpjam_register_sms_provider($key, $args, $type='domestic'){
	WPJAM_SMS::register_provider($key, $args, $type);
}

function wpjam_sms_get_setting($setting_name){
	return wpjam_get_setting('wpjam-signup', $setting_name);
}

function wpjam_sms_update_setting($setting, $value){
	return wpjam_update_setting('wpjam-signup', $setting, $value);
}

function wpjam_is_china_number($number) {
	return preg_match('/^((\+86)|(86))?(1)\d{10}$/', $number);
}