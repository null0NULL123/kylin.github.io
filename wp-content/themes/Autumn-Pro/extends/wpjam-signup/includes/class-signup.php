<?php
wp_cache_add_global_groups('wpjam_signup');

abstract class WPJAM_Signup{
	public static $signups	= [];

	public static function register_signup($name, $args){
		self::$signups[$name]	= $args;
	}

	public static function get_signups(){
		return self::$signups;
	}

	public static function get_model($name){
		if(isset(self::$signups[$name])){
			return self::$signups[$name]['model'];
		}else{
			return null;
		}
	}

	public static function signup($openid, $args){
		$user	= static::get_user_by_openid($openid);

		if(is_wp_error($user)){
			return $user;
		}

		if(!$user){
			$third_user	= static::get_third_user($openid);

			if(is_wp_error($third_user)){
				return $third_user;
			}

			$is_create	= true;

			$args['user_login']	= $openid;
			$args['user_email']	= static::get_email($openid);

			if(!empty($third_user['nickname'])){
				$args['nickname']	= $third_user['nickname'];
			}

			$user_id	= WPJAM_User::create($args);

			if(is_wp_error($user_id)){
				return $user_id;
			}

			$user	= static::bind($user_id, $openid);

			if(is_wp_error($user)){
				return $user;
			}
		}else{
			$is_create	= false;
		}

		$wpjam_user	= WPJAM_User::get_instance($user->ID);

		if(is_wp_error($wpjam_user)){
			return $wpjam_user;
		}

		if(!$is_create && !empty($args['role'])){
			$blog_id	= $args['blog_id'] ?? 0;
			$user		= $wpjam_user->add_role($args['role'], $blog_id);

			if(is_wp_error($user)){
				return $user;
			}
		}

		$wpjam_user->login();

		do_action('wpjam_user_signuped', $user, $args);

		return $user;	
	}

	public static function bind($user_id, $openid){
		$third_user	= static::get_third_user($openid);

		if(is_wp_error($third_user)){
			return $third_user;
		}

		if($third_user){
			if($third_user['user_id'] != $user_id){	// 未绑定，或者绑定其他 user_id
				if($third_user['user_id'] && get_userdata($third_user['user_id'])){
					return new WP_Error('already_binded', '已绑定其他账号。');
				}else{
					// 未绑定，或者旧的绑定用户已经被删除，则可以重新绑定
					static::update_third_user($openid, compact('user_id'));
				}
			}

			$wpjam_user	= WPJAM_User::get_instance($user_id);

			$wpjam_user->bind(static::get_name(), static::get_appid(), $openid);

			$avatar_field	= static::get_avatar_field();

			if($avatar_field && !empty($third_user[$avatar_field])){
				$wpjam_user->update_avatarurl($third_user[$avatar_field]);
			}

			if(!empty($third_user['nickname'])){
				$wpjam_user->update_nickname($third_user['nickname']);
			}
		}	

		return get_userdata($user_id);	
	}

	public static function unbind($user_id){
		$wpjam_user	= WPJAM_User::get_instance($user_id);

		if($openid	= $wpjam_user->unbind(static::get_name(), static::get_appid())){
			static::update_third_user($openid, ['user_id'=>0]);
		}
		
		return true;
	}

	public static function get_user_openid($user_id){
		$wpjam_user	= WPJAM_User::get_instance($user_id);
		
		return $wpjam_user->get_openid(static::get_name(), static::get_appid());
	}

	public static function get_user_by_openid($openid){
		$third_user	= static::get_third_user($openid);

		if(is_wp_error($third_user)){
			return $third_user;
		}elseif(empty($third_user)){
			return null;
		}
		
		$user_id	= $third_user['user_id'] ?? 0;

		if(!$user_id || !get_userdata($user_id)){
			if(method_exists(get_called_class(), 'get_user_by_unionid')){
				$user	= static::get_user_by_unionid($third_user);

				if($user && !is_wp_error($user)){
					$user_id	= $user->ID;
				}
			}
			
			if(!$user_id || !get_userdata($user_id)){
				$user_id	= WPJAM_User::get_by_openid(static::get_name(), static::get_appid(), $openid);
			}
		}

		if($user_id && get_userdata($user_id)){
			$result	= static::bind($user_id, $openid);

			if(is_wp_error($result)){
				return $result;
			}

			return get_userdata($user_id);
		}else{
			return null;
		}		
	}

	public static function get_bind_third_user($user_id){
		$openid		= static::get_user_openid($user_id);

		if($openid){
			return static::get_third_user($openid);
		}else{
			if($openid = static::get_openid_by_user_id($user_id)){
				return static::get_third_user($openid);
			}else{
				return [];
			}
		}
	}

	protected static function redirect(){
		if(isset($_REQUEST['interim-login'])){
			global $interim_login;
			$interim_login = 'success';
			$message       = '<p class="message">' . __( 'You have logged in successfully.' ) . '</p>';

			login_header('', $message);
			?>
			</div>
			<?php do_action('login_footer'); ?>
			</body></html>
			<?php
		}else{
			$redirect_to	= $_REQUEST['redirect_to'] ?? '';
			$redirect_to	= $redirect_to ?: admin_url();
			wp_redirect($redirect_to);
		}

		exit;
	}

	public static function get_meta_key(){
		return WPJAM_User::get_bind_key(static::get_name(), static::get_appid());
	}

	public static function get_cache_key($key){
		$cache_key	= static::get_name();

		if($appid = static::get_appid()){
			$cache_key	.= '_'.$appid;
		}

		return $cache_key.':'.$key;
	}
	
	protected static function cache_get($key){
		$cache_key	= self::get_cache_key($key);

		return wp_cache_get($cache_key, 'wpjam_signup');
	}

	protected static function cache_set($key, $data, $cache_time=DAY_IN_SECONDS){
		$cache_key	= self::get_cache_key($key);

		return wp_cache_set($cache_key, $data, 'wpjam_signup', $cache_time);
	}

	protected static function cache_delete($key){
		$cache_key	= self::get_cache_key($key);

		return wp_cache_delete($cache_key, 'wpjam_signup');
	}

	abstract public static function get_openid_by_user_id($user_id);

	abstract protected static function get_third_user($openid);

	abstract protected static function update_third_user($openid, $data);

	abstract protected static function get_email($openid);
}

