<?php
wp_cache_add_global_groups('wpjam_user_phones');

class WPJAM_User_Phone extends WPJAM_Model{
	public static function insert($data){
		if(empty($data['phone'])){
			return new WP_Error('empty_phone', '手机号码不能为空');
		}

		$data['country_code']	= $data['country_code'] ?? 86;
		
		if($data['country_code'] != 86){
			$data['phone']		= $data['country_code'].$data['phone'];
		}
		
		$data['time']			= time();
		
		return parent::insert($data);
	}

	public static function update($phone, $data){
		if(empty($phone)){
			return new WP_Error('empty_phone', '手机号码不能为空');
		}

		if(isset($data['phone']) || isset($data['country_code'])){
			return new WP_Error('phone_modification_not_allow', '手机号码不能修改');
		}

		return parent::update($phone, $data);
	}

	public static function get($phone, $country_code=86){
		if($country_code != 86){
			$phone	= $country_code.$phone;	
		}
		
		return parent::get($phone);
	}

	private static 	$handler;

	public static function get_handler(){
		if(is_null(self::$handler)){
			self::$handler = new WPJAM_DB(self::get_table(), array(
				'primary_key'		=> 'phone',
				'cache_group'		=> 'wpjam_user_phones',
				'searchable_fields'	=> ['phone','user_id']
			));
		}

		return self::$handler;
	}

	public static function get_table(){
		return $GLOBALS['wpdb']->base_prefix.'user_phones';
	}

	public static function create_table(){
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$table	= self::get_table();

		if($GLOBALS['wpdb']->get_var("show tables like '{$table}'") != $table) {
			$sql = "
			CREATE TABLE IF NOT EXISTS `{$table}` (
				`phone` bigint(15) NOT NULL,
				`country_code` int(4) NOT NULL DEFAULT 86,
				`user_id` bigint(20) NOT NULL DEFAULT 0,
				`status` int(1) NOT NULL DEFAULT 1,
				`time` int(10) NOT NULL,
				PRIMARY KEY	(`phone`),
				KEY `user_id` (`user_id`),
				KEY `country_code` (`country_code`)
			) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			";
	 
			dbDelta($sql);
		}
	}
}