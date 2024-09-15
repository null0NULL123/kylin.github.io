<?php
wp_cache_add_global_groups(['wpjam_sms']);

class WPJAM_SMS{
	private static $domestic_providers		= [];
	private static $international_providers	= [];
	private static $limit_times				= 5;

	public static function send($phone, $args=[]){
		if(empty($phone)){
			return new WP_Error('empty_phone', '号码不能为空');
		}

		$over	= self::is_over($phone);
		if($over && is_wp_error($over)){
			return $over;
		}

		$sender	= $args['sender'] ?? '';

		if(empty($sender) || !is_callable($sender)){
			return new WP_Error('empty_sms_provider', '系统未设置短信服务商');
		}

		$args['type']	= $args['type'] ?? 'code';

		if($args['type'] == 'code'){
			if(self::cache_get($phone.':time') !== false){
				return new WP_Error('sms_code_sent', '验证码1分钟前已发送了。');
			}

			$code			= rand(1000,9999);
			$args['code']	= $code;
		}

		$result	= call_user_func($sender, $phone, $args);

		if(is_wp_error($result)){
			return $result;
		}

		if($args['type'] == 'code'){
			self::cache_set($phone.':code', $code, MINUTE_IN_SECONDS*30);
			self::cache_set($phone.':time', time(), MINUTE_IN_SECONDS);
		}

		return true;
	}

	public static function verify($phone, $phone_code){
		$phone	= trim($phone);

		$over	= self::is_over($phone);
		if($over && is_wp_error($over)){
			return $over;
		}

		$current_code	= self::cache_get($phone.':code');

		if($current_code  === false){
			return new WP_Error('sms_code_not_exits', '手机验证码已过期');
		}elseif($phone_code != $current_code ){
			$failed_times	= self::cache_get($phone.':failed_times') ?: 0;
			$failed_times	= $failed_times + 1;

			self::cache_set($phone.':failed_times', $failed_times, MINUTE_IN_SECONDS*15);

			return new WP_Error('error_sms_code', '错误的手机验证码');
		}else{
			return true;
		}
	}

	public static function is_over($phone){
		$failed_times	= self::cache_get($phone.':failed_times') ?: 0;

		if($failed_times > self::$limit_times){
			return new WP_Error('too_many_retries', '你已尝试多次错误的验证码，请15分钟后重试！');
		}

		return false;
	}

	public static function cache_get($key){
		return wp_cache_get($key, 'wpjam_sms');
	}

	public static function cache_set($key, $data, $cache_time=DAY_IN_SECONDS){
		return wp_cache_set($key, $data, 'wpjam_sms', $cache_time);
	}

	public static function cache_delete($key){
		return wp_cache_delete($key, 'wpjam_sms');
	}

	public static function include_provider($key, $type='domestic', $admin=false){
		if(empty($key)){
			return false;
		}

		$providers 	= self::get_providers($type);
		$provider	= $providers[$key] ?? [];

		if(empty($provider)){
			return new WP_Error('unregister_sms_provider', '该短信服务商未注册');
		}

		if($admin){
			if(!empty($provider['admin'])){
				include_once $provider['admin'];
			}

			return true;
		}else{
			if(!empty($provider['file'])){
				include_once $provider['file'];
			}

			return $provider['sender'] ?? '';
		}
	}

	public static function get_providers($type='domestic'){
		if($type == 'domestic'){
			return self::$domestic_providers;
		}else{
			return self::$international_providers;
		}
	}

	public static function register_provider($key, $args, $type='domestic'){
		if($type == 'domestic'){
			self::$domestic_providers[$key]	= $args;
		}else{
			self::$international_providers[$key]	= $args;
		}
	}

	public static function load_plugin_page(){
		wpjam_register_plugin_page_tab('sms', ['title'=>'短信设置',	'function'=>'option',	'option_name'=>'wpjam-signup',	'load_callback'=>['WPJAM_SMS', 'load_option_page']]);

		wpjam_include_sms_provider(wpjam_sms_get_setting('sms_provider'), 'domestic', true);
		wpjam_include_sms_provider(wpjam_sms_get_setting('international_sms_provider'), 'international', true);
	}

	public static function load_option_page(){
		$fields = [];
					
		foreach (['domestic'=>'国内', 'international'=>'国际/港台'] as $region_key=>$region_name) {
			$sms_providers			= WPJAM_SMS::get_providers($region_key);
			$sms_provider_options	= array_map(function($provider){return $provider['title'];}, $sms_providers);
			$sms_provider_options	= array_merge([''=>'不启用'], $sms_provider_options);

			$field_key	= $region_key == 'domestic' ? 'sms_provider' : $region_key.'_sms_provider';

			$fields[$field_key]	= ['title'=>$region_name.'短信服务提供商',	'type'=>'select',	'options'=>$sms_provider_options];
		}

		wpjam_register_option('wpjam-signup', ['fields'=>$fields, 'ajax'=>false]);

		if(did_action('wpjam_signup_loaded') && wpjam_sms_get_setting('sms_provider')){
			WPJAM_User_Phone::create_table();
		}
	}
}