<?php
class WEIXIN_Signup extends WPJAM_Signup{
	use WPJAM_QrcodeSignupTrait;

	private static $appid;

	public static function set_appid($appid){
		self::$appid	= $appid;
	}

	public static function get_appid(){
		return self::$appid;
	}

	public static function get_name(){
		return 'weixin';
	}

	public static function get_openid_by_unionid($unionid){
		WEIXIN_User::set_appid(self::$appid);
		
		return WEIXIN_User::Query()->where('unionid', $unionid)->get_var('openid');
	}

	public static function get_openid_by_user_id($user_id){
		WEIXIN_User::set_appid(self::$appid);
		
		return WEIXIN_User::Query()->where('user_id', $user_id)->get_var('openid');
	}

	public static function get_third_user($openid){
		$blacklist	= weixin(self::$appid)->get_blacklist();
		
		if($blacklist && in_array($openid, $blacklist)){
			return new WP_Error('invalid_openid', '无此微信用户');
		}

		WEIXIN_User::set_appid(self::$appid);

		$weixin_user	= WEIXIN_User::get($openid);
		
		if(empty($weixin_user) || $weixin_user['last_update'] < time() - DAY_IN_SECONDS){
			$user_info	= weixin(self::$appid)->get_user_info($openid);

			if($user_info && !is_wp_error($user_info)){
				$weixin_user	= WEIXIN_User::sync($user_info);
			}
		}
		
		return $weixin_user;
	}

	protected static function update_third_user($openid, $data){
		$blacklist	= weixin(self::$appid)->get_blacklist();

		if($blacklist && in_array($openid, $blacklist)){
			return new WP_Error('invalid_openid', '无此微信用户');
		}

		WEIXIN_User::set_appid(self::$appid);

		return WEIXIN_User::update($openid, $data);
	}

	public static function get_user_by_unionid($third_user){
		$pre	= apply_filters('weixin_query_unionid', false);

		if(!$pre){
			return null;
		}

		$unionid	= $third_user['unionid'];

		if(empty($unionid)){
			return new WP_Error('empty_unionid','请先授权！');
		}

		if(!class_exists('WEAPP_Signup')){
			return new WP_Error('weapp_signup_disabled','请先开启微信小程序登录');
		}

		$weapp_openid = WEAPP_Signup::get_openid_by_unionid($unionid);

		if(empty($weapp_openid)) {
			return new WP_Error('user_not_exists','用户不存在');
		}

		return WEAPP_Signup::get_user_by_openid($weapp_openid);
	}

	public static function create_qrcode($key, $user_id=0){
		$qrcode	= self::cache_get($key.'_qrcode');

		if($qrcode === false){
			$scene	= wp_generate_password(24,false,false).microtime(true)*10000;
			$qrcode = weixin(self::$appid)->create_qrcode('QR_STR_SCENE', $scene, 1200);

			if(is_wp_error($qrcode)){
				return $qrcode;
			}

			$qrcode['qrcode_url']	= 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$qrcode['ticket'];
			$qrcode['key']			= $key;
			$qrcode['scene']		= $scene;
			$qrcode['user_id']		= $user_id;
			$qrcode['code']			= rand(1000,9999);
			
			self::cache_set($key.'_qrcode', $qrcode, 1200);
			self::cache_set($scene.'_scene', $qrcode, 1200);
		}

		return $qrcode;	
	}

	public static function login_action($args=[]){		
		if(is_weixin()){
			$user	= self::oauth_signup($args);

			if(is_wp_error($user)){
				wp_die($user, '错误', ['response'=>200]);
			}else{
				self::redirect();
			}
		}else{
			self::login_form($args);
		}
	}

	public static function oauth_signup($args=[]){
		if(!self::is_bind_blog()){
			$redirect_to	= get_home_url(self::get_bind_blog_id(), '/wp-login.php?action=weixin');

			if(!empty($_REQUEST['redirect_to'])){
				$redirect_to	.= '&redirect_to='.urlencode($_REQUEST['redirect_to']);
			}

			if(!empty($args['invite'])){
				$redirect_to	.= '&invite_key='.$args['invite'];
			}
			
			wp_redirect($redirect_to);

			exit;
		}else{
			$openid	= weixin_get_current_openid();

			if(is_wp_error($openid)){
				weixin_oauth_request('snsapi_userinfo');
			}else{
				return self::signup($openid, $args);
			}
		}
	}

	public static function scan_reply($keyword, $weixin_reply){
		$message	= $weixin_reply->get_message();
		$scene		= $message['EventKey'] ?? '';

		if($scene && self::get_reply($scene, $weixin_reply)){
			$reply_required	= false;
		}else{
			$reply_required	= true;
		}

		return $weixin_reply->scan_reply($keyword, $reply_required);
	}

	public static function subscribe_reply($keyword, $weixin_reply){
		$message	= $weixin_reply->get_message();
		$scene		= $message['EventKey'] ?? '';
		$scene		= str_replace('qrscene_','',$scene);

		if($scene && self::get_reply($scene, $weixin_reply)){
			$reply_required	= false;
		}else{
			$reply_required	= true;
		}

		return $weixin_reply->subscribe_reply($keyword, $reply_required);
	}

	public static function get_reply($scene, $weixin_reply){
		if(is_numeric($scene)){
			return false;
		}

		$openid	= $weixin_reply->get_openid();
		$code	= WEIXIN_Signup::scan_qrcode($openid, $scene, 'weixin');

		if(is_wp_error($code)){
			if($code->get_error_code() == 'invalid_scene'){
				return false;
			}else{
				$reply	= $code->get_error_message();
			}
		}elseif(is_numeric($code)){
			$reply	= '你的验证码是 '.$code;
		}else{
			$reply	= '已绑定，请刷新页面！';
		}

		$weixin_reply->textReply($reply);

		return true;
	}

	protected static function get_avatar_field(){
		return 'headimgurl';
	}

	protected static function get_email($openid){
		return $openid.'@'.self::$appid.'.weixin';
	}

	public static function get_bind_blog_id(){
		return apply_filters('weixin_bind_blog_id', null);
	}

	public static function is_bind_blog(){
		return is_multisite() ? get_current_blog_id() == self::get_bind_blog_id() : true;	
	}

	public static function ajax_qrcode_signup(){
		$args	= [];

		if(wpjam_get_invite_actions()){
			$invite_key	= $_REQUEST['invite_key'] ?? '';

			if($invite_key){
				$invite	= wpjam_validate_invite($invite_key);

				if(is_wp_error($invite)){
					wpjam_send_json($invite);
				}elseif($invite){
					$args['invite']				= $invite_key;
					$args['role']				= $invite['role'];
					$args['blog_id']			= $invite['blog_id'] ?? 0;
					$args['users_can_register']	= true;
				}
			}
		}

		$result	= self::qrcode_signup($_POST['scene'], $_POST['code'], $args);

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
		if(in_array($json, [ 'weixin.qrcode.create', 'weixin.qrcode.verify'])){
			wpjam_register_api($json, ['template' => WPJAM_SIGNUP_PLUGIN_DIR.'api/'.$json.'.php']);
		}
	}





	

	public static function get_user_by_openid_meta_key($openid, $args=[]){
		$users	= get_users(['meta_key'=>self::get_meta_key(), 'meta_value'=>$openid, 'blog_id'=>0]);

		if($users){
			$user_id	= $users[0]->ID;

			$result		= self::bind($user_id, $openid);
			
			if(is_wp_error($result)){
				return $result;
			}
		}else{
			$user_id = username_exists($openid);	// 最后尝试

			if($user_id){
				$has_openid = self::get_user_openid($user_id);

				if($has_openid && $has_openid != $openid){
					// 妈的不知道怎么回事了
				}else{
					$result	= self::bind($user_id, $openid);

					if(is_wp_error($result)){
						return $result;
					}
				}
			}
		}

		if($user_id){
			return get_userdata($user_id);
		}else{
			return null;
		}		
	}
}