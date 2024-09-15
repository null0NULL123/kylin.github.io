<?php 
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

$qrcode_url	= $wpjam_qrcode['qrcode_url'];
?>
<div class="col-lg-7 col-md-12 col-pad-0 align-self-center">
	<div class="login-inner-form">
		<div class="details">
			<h3>微信扫码登录</h3>
			<div class="login-trps d-tips <?php if(isset($errors)){ echo 'error'; }?>">
				<?php if(isset($errors)){ echo $errors->get_error_message(); }?>
			</div>
			<form action="<?php echo home_url('user/weixin-login'); ?>" class="form login" method="POST" id="weixin_login_form">
				<div class="form-group weixin-img">
					<label for="code">
						<img src="<?php echo $qrcode_url; ?>" width="306">
						<input type="hidden" id="scene" name="scene" value="<?php echo $wpjam_qrcode['scene']; ?>">
						<input type="hidden" id="invite_key" name="invite_key" value="<?php echo $_GET['invite_key'] ?? ''; ?>">
					</label>
				</div>
				<div class="form-group code">
					<input id="code" type="text" id="code" name="code" class="input-text" value="" placeholder="输入验证码">
				</div>
				<div class="form-group">
					<input type="submit" name="submit" class="btn-md btn-theme" value="登录">
				</div>
			</form>
			<p>
				手机不在身旁？<a href="<?php echo home_url(user_trailingslashit('/user/login')); ?>"> 返回邮箱登录 </a>
			</p>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(function($){
	var ajaxurl	= '<?php echo admin_url('admin-ajax.php', 'login_post'); ?>';

	$('body').on('submit', '#weixin_login_form', function(e){
		e.preventDefault();

		var scene		= $.trim($('#scene').val());
		var code		= $.trim($('#code').val());
		var invite_key	= $.trim($('#invite_key').val());

		if(!code){
			alert('请输入有效的验证码'); 
			return false;
		}

		$.post(ajaxurl, {
			action:		'weixin-qrcode-signup',
			scene:		scene,
			code:		code,
			invite_key:	invite_key
		},function(data, status){
			if(data.errcode){
				alert(data.errmsg);
			}else{
				window.location.href	= window.location.href	= '<?php echo home_url(user_trailingslashit('/user')); ?>';
			}
		});
	});
});
</script>