<?php
if(empty($GLOBALS['current_tab'])){
	wpjam_register_plugin_page_tab('sms', ['title'=>'短信设置',	'function'=>'option',	'option_name'=>'wpjam-signup',	'tab_file'=>__DIR__.'/wpjam-sms.php']);

	wpjam_include_sms_provider(wpjam_sms_get_setting('sms_provider'), 'domestic', true);
	wpjam_include_sms_provider(wpjam_sms_get_setting('international_sms_provider'), 'international', true);
}elseif($GLOBALS['current_tab'] == 'sms'){
	if(isset($_GET['reset'])){
		delete_option('wpjam-signup');
	}

	$fields = [];
	
	foreach (['domestic'=>'国内', 'international'=>'国际/港台'] as $region_key=>$region_name) {
		$sms_providers			= WPJAM_SMS::get_providers($region_key);
		$sms_provider_options	= array_map(function($provider){return $provider['title'];}, $sms_providers);
		$sms_provider_options	= array_merge([''=>'不启用'], $sms_provider_options);

		$field_key	= $region_key == 'domestic' ? 'sms_provider' : $region_key.'_sms_provider';

		$fields[$field_key]	= ['title'=>$region_name.'短信服务提供商',	'type'=>'select',	'options'=>$sms_provider_options];
	}

	$ajax = false;

	wpjam_register_option('wpjam-signup', compact('fields', 'ajax'));
}

if(did_action('wpjam_signup_loaded')){
	WPJAM_User_Phone::create_table();
}