<div class="wp-editor col-lg-9">
	<div class="row posts-wrapper2">

		<?php $bind_actions = wpjam_get_login_actions('bind'); ?>

		<?php if(isset($bind_actions['weixin'])){ ?>

		<div class="tougao weixin-bind" style="margin-bottom:30px">
			<h3 class="section-title"><span>绑定微信账号</span></h3>
				<div class="login-inner-form">
					<div class="details">
					<?php
					$user_id	= get_current_user_id();

					$openid			= wpjam_get_user_openid($user_id, 'weixin');
					$weixin_user	= []; 
						
					if($openid){
						$weixin_user	= WEIXIN_Signup::get_third_user($openid);

						if(!$weixin_user){
							$errors	= new WP_Error('invalid_bind', '绑定错误，请重新改绑定！');
						}
					} ?>

					<?php if($weixin_user){ ?>

						<p>您已经成功绑定微信账号，信息如下：</p>

						<table class="user-weixin-bind">
						<thead>
						<tr>
							<th>
								微信昵称
							</th>
							<th>
								头像
							</th>
							<th>
								地区
							</th>
							<th>
								操作
							</th>
						</tr>
						</thead>
						<tbody>
						<tr class="relative">
							<td>
								<?php echo $weixin_user['nickname']; ?>
							</td>
							<td>
								<img src="<?php echo str_replace('/132', '/0', $weixin_user['headimgurl']); ?>" alt="<?php echo $weixin_user['nickname']; ?>">
							</td>
							<td>
								<?php echo $weixin_user['province'].' '.$weixin_user['city']; ?>
							</td>
							<td>
								<a class="weixin-unbind" id="weixin_unbind" href="<?php echo home_url('user/weixin-bind?unbind'); ?>">解除绑定</a>
							</td>
						</tr>
						</tbody>
						</table>

					<?php }else{ 

					if(isset($_COOKIE['weixin_key'])){
						$key	= $_COOKIE['weixin_key'];
					}else{
						$key	= wp_generate_password(32, false, false);
						wpjam_set_cookie('weixin_key', $key, time()+30);
					}

					$wpjam_qrcode	= WEIXIN_Signup::create_qrcode($key); 
					if(is_wp_error($wpjam_qrcode)){
						wp_die($wpjam_qrcode);
					}
					
					?>

					<p>使用微信扫描二维码，将获取到的验证码填写到下面进行绑定</p>
					<div class="login-trps d-tips <?php if(isset($errors)){ echo 'error'; }?>">
						<?php if(isset($errors)){ echo $errors->get_error_message(); }?>
					</div>
					<form action="<?php echo home_url('user/weixin-bind'); ?>" class="form login" method="POST" id="weixin_qrcode_bind">
						<div class="form-group weixin-img">
							<label for="code">
								<img src="<?php echo $wpjam_qrcode['qrcode_url']; ?>" width="250">
								<input type="hidden" name="scene" id="scene" value="<?php echo $wpjam_qrcode['scene']; ?>">
							</label>
						</div>
						<div class="form-group code">
							<input id="code" type="text" name="code" class="input-text" value="" placeholder="输入验证码" required>
						</div>
						<div class="form-group">
							<input type="submit" name="submit" class="btn-md btn-theme" value="立即绑定">
						</div>
					</form>

					<?php } ?>
						
				</div>
			</div>
		</div>

		<?php } ?>
		

		<?php if(isset($bind_actions['sms'])){ ?>

		<div class="tougao mobile-bind">
			<h3 class="section-title"><span>绑定手机账号</span></h3>
			<div class="login-inner-form">
				<div class="details">

				<?php
					$user_id	= get_current_user_id();

					$phone		= SMS_Signup::get_user_openid($user_id);
					
					$sms_user	= []; 
						
					if($phone){
						$sms_user	= SMS_Signup::get_third_user($phone);

						if(!$sms_user){
							$errors	= new WP_Error('invalid_bind', '绑定错误，请重新改绑定！');
						}
					}

				?>

				<?php if($sms_user){ ?>

				<form action="" class="form" method="POST" id="sms_unbind">
	
					<div class="form-group email">
						你已经绑定手机号码：<?php echo $phone; ?>
					</div>
					<br><br>
					<div class="form-group">
						<input type="hidden" name="action" value="">
						<button type="submit" class="btn-md btn-theme">解除绑定</button>
					</div>
				</form>

				<?php }else{ ?>

				<div class="login-trps d-tips <?php if(isset($errors)){ echo 'error'; }?>">
					<?php if(isset($errors)){ echo $errors->get_error_message(); }?>
				</div>

				<form action="" class="form" method="POST" id="sms_bind">
	
					<div class="form-group email">
						<input type="text" id="phone" name="phone" value="" class="input-text" placeholder="输入手机号码">
					</div>
	
					<div class="form-group fieldset" style="position:relative;">
						<input class="input-control inline full-width has-border input-text" id="code" type="text" name="code" placeholder="输入短信验证码" required>
						<input type="button" class="captcha-clk inline" id="send_sms" value="获取验证码">
	            	</div>
					<br>
					<div class="form-group">
						<input type="hidden" name="action" value="">
						<button type="submit" class="btn-md btn-theme">立即绑定</button>
					</div>
				</form>

				<?php } ?>
						
				</div>
			</div>
		</div>

		<?php } ?>


	</div>
</div>

<script type="text/javascript">
var ajaxurl	= '<?php echo admin_url('admin-ajax.php', 'login_post'); ?>'

jQuery(function($){
	$('body').on('click', '#weixin_unbind', function(e){
		e.preventDefault();

		$.post(ajaxurl, {
			action:	'weixin-unbind',
		},function(data, status){
			if(data.errcode){
				alert(data.errmsg);
			}else{
				window.location.href	= '<?php echo home_url(user_trailingslashit('/user/bind')); ?>';
			}
		});
	});

	$('body').on('submit', '#weixin_qrcode_bind', function(e){
		e.preventDefault();

		var scene	= $.trim($('#scene').val());
		var code	= $.trim($('#code').val());

		if(!code){
			alert('请输入有效的验证码'); 
			return false;
		}

		$.post(ajaxurl, {
			action:	'weixin-qrcode-bind',
			scene:	scene,
			code:	code
		},function(data, status){
			if(data.errcode){
				alert(data.errmsg);
			}else{
				window.location.href	= '<?php echo home_url(user_trailingslashit('/user/bind')); ?>';
			}
		});
	});

	$('body').on('submit', '#sms_bind', function(e){
		e.preventDefault();

		var phone	= $.trim($('#phone').val());
		var code	= $.trim($('#code').val());

		if(!phone){
			alert('请输入有效的手机号码'); 
			return false;
		}

		if(!code){
			alert('请输入有效的验证码'); 
			return false;
		}

		$.post(ajaxurl, {
			action:	'sms-bind',
			phone:	phone,
			code:	code
		},function(data, status){
			if(data.errcode){
				alert(data.errmsg);
			}else{
				window.location.href	= '<?php echo home_url(user_trailingslashit('/user/bind')); ?>';
			}
		});
	});

	$('body').on('submit', '#sms_unbind', function(e){
		e.preventDefault();

		$.post(ajaxurl, {
			action:	'sms-unbind',
		},function(data, status){
			if(data.errcode){
				alert(data.errmsg);
			}else{
				window.location.href	= '<?php echo home_url(user_trailingslashit('/user/bind')); ?>';
			}
		});
	});

	$('body').on('click', '#send_sms', function(){
		var phone		= $.trim($('#phone').val());
		var phoneReg	= /(^1[3|4|5|6|7|8|9]\d{9}$)|(^09\d{8}$)/;
		var	time_left	= 60;

		if(!phone){
			alert('请输入手机号码');
			return false;
		}

		if (!phoneReg.test(phone)) {
			alert('请输入有效的手机号码'); 
			return false;
		}

		$('#send_sms').attr('disabled', "true");

		var sms_timer = window.setInterval(function(){
			if(time_left == 0){                
				window.clearInterval(sms_timer);
				$("#send_sms").removeAttr('disabled');
				$("#send_sms").val("重新发送");
			}else {
				$("#send_sms").val(time_left + "秒再获取");

				time_left--;
			}
		}, 1000);

		$.post(ajaxurl, {
			action:	'send-sms',
			phone:	phone
		},function(data, status){
			if(data.errcode){
				alert(data.errmsg);
				window.clearInterval(sms_timer);
				$("#send_sms").removeAttr('disabled');
				$("#send_sms").val("获取验证码");
			}else{
				$('#code').removeAttr('disabled');
			}
		});
	});
});
</script>