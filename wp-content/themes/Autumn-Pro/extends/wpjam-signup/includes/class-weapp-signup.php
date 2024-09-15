<?php
class WEAPP_Signup extends WPJAM_Signup{
	use WPJAM_QrcodeSignupTrait;

	private static $appid;
	private static $third_user_model;

	public static function set_appid($appid){
		self::$appid	= $appid;
	}

	public static function get_appid(){
		return self::$appid;
	}

	public static function get_name(){
		return 'weapp';
	}

	public static function get_third_user($openid){
		WEAPP_User::set_appid(self::$appid);

		return WEAPP_User::get($openid);
	}

	protected static function update_third_user($openid, $data){
		WEAPP_User::set_appid(self::$appid);

		return WEAPP_User::update($openid, $data);
	}

	public static function get_openid_by_user_id($user_id){
		WEAPP_User::set_appid(self::$appid);

		return WEAPP_User::Query()->where('user_id', $user_id)->get_var('openid');
	}

	public static function get_openid_by_unionid($unionid){
		WEAPP_User::set_appid(self::$appid);

		return WEAPP_User::Query()->where('unionid', $unionid)->get_var('openid');
	}

	public static function get_user_by_unionid($third_user){
		$pre	= apply_filters('weapp_query_unionid', false);

		if(!$pre){
			return null;
		}

		if(empty($third_user['unionid'])){
			return new WP_Error('empty_unionid','请先授权！');
		}

		if(!class_exists('WEIXIN_Signup')){
			return new WP_Error('weixin_signup_disabled','请先开启微信公众号登录');
		}

		$weixin_openid = WEIXIN_Signup::get_openid_by_unionid($third_user['unionid']);

		if(empty($weixin_openid)) {
			return new WP_Error('user_not_exists','用户不存在');
		}

		return WEIXIN_Signup::get_user_by_openid($weixin_openid);
	}

	public static function create_qrcode($key, $user_id=0){
		$qrcode	= self::cache_get($key.'_qrcode');

		if($qrcode === false){
			$scene	= wp_generate_password(11,false,false).microtime(true)*10000;
			$code 	= rand(1000,9999);
			
			$scene_str	= $user_id ? 'bind='.$scene : 'signup='.$scene;
			$weapp_page	= self::get_bind_page();
			$qrcode_url	= weapp_create_qrcode(['page'=>$weapp_page, 'scene'=>$scene_str, 'type'=>'unlimit', 'width'=>430], 'url', self::$appid);
			
			if(is_wp_error($qrcode_url)){
				return $qrcode_url;
			}

			$qrcode	= compact('key', 'code', 'scene', 'qrcode_url', 'user_id');

			self::cache_set($key.'_qrcode', $qrcode, 1200);
			self::cache_set($scene.'_scene', $qrcode, 1200);
		}

		return $qrcode;	
	}

	public static function login_action($args=[]){
		self::login_form($args);
	}

	public static function get_bind_page(){
		return apply_filters('weapp_bind_page', '');
	}

	protected static function get_avatar_field(){
		return 'avatarurl';
	}

	protected static function get_email($openid){
		return $openid.'@'.self::$appid.'.weapp';
	}


	public static function ajax_qrcode_signup(){
		$result	= self::qrcode_signup($_POST['scene'], $_POST['code']);

		if(is_wp_error($result)){
			wpjam_send_json($result);
		}else{
			wpjam_send_json();
		}
	}

	public static function ajax_qrcode_bind(){
		$openid		= self::verify_qrcode($_POST['scene'], $_POST['code']);	

		if(is_wp_error($openid)){
			wpjam_send_json($openid);
		}

		 $user_id = get_current_user_id();
		 $user    = self::bind($user_id, $openid);

		 if(is_wp_error($user)){
		 	wpjam_send_json($user);
		 }else{
		 	wpjam_send_json();
		 }
	}

	public static function ajax_unbind(){
		$user_id	= get_current_user_id();
		$openid		= self::get_user_openid($user_id);

		if(!$openid){
			$openid	= self::get_openid_by_user_id($user_id);
		}

		self::unbind($user_id, $openid);
		
		wpjam_send_json();
	}

	public static function register_api($json){
		if(in_array($json, ['weapp.qrcode.bind', 'weapp.qrcode.code'])){
			wpjam_register_api($json, ['template' => WPJAM_SIGNUP_PLUGIN_DIR.'api/'.$json.'.php']);
		}
	}
}