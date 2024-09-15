<?php
trait WPJAM_QrcodeSignupTrait{
	public static function qrcode_signup($scene, $code, $args=[]){
		if($user	= apply_filters('wpjam_qrcode_signup', null, $scene, $code)){
			return $user;
		}
		
		$openid	= self::verify_qrcode($scene, $code);

		if(is_wp_error($openid)){
			return $openid;
		}else{
			return self::signup($openid, $args);
		}
	}

	public static function verify_qrcode($scene, $code){
		if(empty($code)){
			return new WP_Error('invalid_code', '验证码不能为空');
		}

		$qrcode	= self::get_qrcode($scene);

		if(is_wp_error($qrcode)){
			return $qrcode;
		}

		if(empty($qrcode['openid'])){
			return new WP_Error('invalid_code', '请先扫描二维码！');
		}

		if($code != $qrcode['code']){
			do_action('wpjam_qrcode_signup_failed', $scene);
			return new WP_Error('invalid_code', '验证码错误！');
		}

		self::cache_delete($scene.'_scene');
		
		return $qrcode['openid'];
	}

	protected static function get_qrcode($scene){
		if(empty($scene)){
			return new WP_Error('invalid_scene', '场景值不能为空');
		}

		$qrcode	= self::cache_get($scene.'_scene');

		if($qrcode === false){
			return new WP_Error('invalid_scene', '二维码无效或已过期，请刷新页面再来验证！');
		}

		return apply_filters('wpjam_signup_qrcode', $qrcode, $scene);
	}

	public static function scan_qrcode($openid, $scene){
		$qrcode = self::get_qrcode($scene);
		
		if(is_wp_error($qrcode)){
			return $qrcode;
		}

		if(!empty($qrcode['openid']) && $qrcode['openid'] != $openid){
			return new WP_Error('qrcode_scaned', '已有用户扫描该二维码！');
		}else{
			$key	= $qrcode['key'];
			self::cache_delete($key.'_qrcode');

			if(!empty($qrcode['user_id'])){
				self::cache_delete($scene.'_scene');
				return self::bind($qrcode['user_id'], $openid);
			}else{
				$qrcode['openid'] = $openid;
				self::cache_set($scene.'_scene', $qrcode, 1200);

				return $qrcode['code'];
			}
		}
	}

	public static function login_form($args){
		$errors = new WP_Error();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$scene	= $_POST['scene'] ?? '';
			$code	= $_POST['code'] ?? '';
			$user 	= self::qrcode_signup($scene, $code, $args);

			if(is_wp_error($user)){
				$errors	= $user;
			}else{
				self::redirect();
			}
		}

		$action	= $args['action'];
		$key	= wp_generate_password(32, false, false);

		$redirect_to	= $_REQUEST['redirect_to'] ?? '';
		$errors			= apply_filters('wp_login_errors', $errors, $redirect_to);

		login_header($args['login_title'],'',$errors);

		$qrcode	= self::create_qrcode($key);

		if(is_wp_error($qrcode)){
			wp_die('二维码创建失败，请刷新重试或<a href="'.home_url('/wp-login.php?action=login').'">使用账号密码登录</a>。
				<br />错误信息：'.$qrcode->get_error_message().$qrcode->get_error_code());
		}

		$login_url	= site_url('wp-login.php?action='.$action, 'login_post');

		if(isset($_REQUEST['interim-login'])){
			$login_url	= add_query_arg(['interim-login'=>1], $login_url);
		}
		?>
		<form name="loginform" id="loginform" action="<?php echo esc_url($login_url); ?>" method="post">
			<p>
				<label for="code">微信扫码，一键登录<br />
				<img src="<?php echo $qrcode['qrcode_url']; ?>" width="272" /></label>
			</p>
			<p>
				<label for="code">验证码<br />
				<input type="number" name="code" id="code" class="input" value="" size="20" /></label>
			</p>

			<?php do_action( 'login_form' );?>

			<input type="hidden" name="scene" id="scene" value="<?php echo $qrcode['scene']; ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>" />
			<input type="hidden" name="invite_key" id="invite_key" value="<?php echo $_REQUEST['invite_key'] ?? ''; ?>" />
			
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>" />
			</p>
		</form>
		<?php

		login_footer('code');
		
		exit;
	}

	public static function login_footer($action){
		?>
		<script type="text/javascript">
		jQuery(function($){
			var ajaxurl 	= '<?php echo admin_url('admin-ajax.php', 'login_post'); ?>';
			var redirect_to = '<?php echo $_REQUEST['redirect_to'] ?? admin_url(); ?>';

			$('body').on('submit', '#loginform', function(e){
				e.preventDefault();

				var scene		= $.trim($('#scene').val());
				var code		= $.trim($('#code').val());
				var invite_key	= $.trim($('#invite_key').val());

				if(!code){
					alert('请输入有效的验证码'); 
					return false;
				}

				$('div#login_error').remove();

				$.post(ajaxurl, {
					action:		'<?php echo $action;?>-qrcode-signup',
					scene:		scene,
					code:		code,
					invite_key:	invite_key
				},function(data, status){
					if(data.errcode){
						// alert(data.errmsg);
						
						$('h1').after('<div id="login_error">'+data.errmsg+'</div>');

						wpjam_shake_form();
					}else{
						window.location.href	= redirect_to;
					}
				});
			});
		});
		</script>
		<?php
	}

	public static function get_bind_fields(){
		$user_id	= get_current_user_id();
		$third_user	= self::get_bind_third_user($user_id);

		if($third_user){
			$view	= '绑定的微信账号是：';
			$view	.= "\n\n".'昵称：'.$third_user['nickname']."\n".'地区：'.$third_user['province'].' '.$third_user['city'];

			$avatar_field	= self::get_avatar_field();
			$avarar_url		= $third_user[$avatar_field];
			
			$view	.= " \n\n".'<img src="'.str_replace('/132', '/0', $avarar_url).'" width="160" />';

			return [
				'view'		=> ['title'=>'',		'type'=>'view',		'value'=>wpautop($view)],
				'bind_type'	=> ['title'=>'',		'type'=>'hidden',	'value'=>'unbind']
			];
		}else{
			$key	= md5('bind_'.$user_id);
			$qrcode	= self::create_qrcode($key, $user_id);

			if(is_wp_error($qrcode)){
				return $qrcode;
				// wpjam_admin_add_error('二维码创建失败，请刷新重试！', 'error');
			}else{
				return [
					'view'		=> ['title'=>'',		'type'=>'view',		'value'=>'<p>使用微信扫一扫，绑定账号之后就可以直接微信扫码登录了。</p>'],
					'qrcode'	=> ['title'=>'二维码',	'type'=>'view',		'value'=>'<img src="'.$qrcode['qrcode_url'].'" style="max-width:215px;" />'],
					// 'code'		=> array('title'=>'验证码',	'type'=>'number',	'class'=>'',	'description'=>'验证码10分钟内有效！'),
					'bind'		=> ['title'=>'操作说明',	'type'=>'view',		'value'=>'扫描上面的二维码，刷新即可！'],
					'scene'		=> ['title'=>'scene',	'type'=>'hidden',	'value'=>$qrcode['scene']],
					'bind_type'	=> ['title'=>'',		'type'=>'hidden',	'value'=>'bind']
				];
			}
		}
	}

	public static function bind_ajax_response(){
		$user_id	= get_current_user_id();
		$bind_type 	= wpjam_get_data_parameter('bind_type');
	
		if($bind_type == 'bind'){
			$openid = self::get_user_openid($user_id);		

			if(!$openid){
				return new WP_Error('scan_fail', '请先扫描，再点击刷新。');
			}

			return true;
		}elseif($bind_type == 'unbind'){
			$openid		= self::get_user_openid($user_id);

			if(!$openid){
				$openid	= self::get_openid_by_user_id($user_id);
			}

			return self::unbind($user_id, $openid);
		}
	}
}