<?php if(get_option('users_can_register')){?>
<div class="col-lg-7 col-md-12 col-pad-0 align-self-center">
	<div class="login-inner-form">
		<div class="details">
			<h3 class="none-992">新用户注册</h3>
			<div class="login-trps d-tips page-register"></div>
			<form action="" class="form" method="POST" id="register_form">
				<div class="form-group username">
					<input type="text" id="username" name="username" class="input-text" placeholder="输入用户名">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<div class="form-group email">
					<input type="email" id="email" name="email" value="" class="input-text" placeholder="输入常用邮箱">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>

				<script>window._WPJAM_XinTheme = {uri: '<?php echo get_bloginfo("template_url") ?>'}</script>
				<div class="form-group fieldset" id="captcha_inline">
					<input class="input-control inline full-width has-border input-text" id="captcha" type="text" name="captcha" placeholder="输入邮箱验证码" required="">
					<span class="captcha-clk inline">获取验证码</span>
					<div class="lp-trps"><i></i> <span></span></div>
            	</div>

				<div class="form-group password_1">
					<input type="password" id="password_1" name="password_1" value="" class="input-text" placeholder="输入密码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<div class="form-group password_2">
					<input type="password" id="password_2" name="password_2" value="" class="input-text" placeholder="重复输入密码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<?php if( !wpjam_theme_get_setting('close_vercode') ){?>
				<div class="form-group vercode">
					<input id="vercode" type="text" autocomplete="off" name="vercode" class="input-text" value="" placeholder="输入验证码">
					<img onclick="this.src=this.src+'?k='+Math.random();" src="<?php echo home_url(user_trailingslashit('/user/code')); ?>" alt="点击刷新验证码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<?php }?>
				<?php $register_agree = wpjam_theme_get_setting('register_agree'); if( $register_agree ){?>
				<div class="checkbox clearfix">
					<div class="form-check checkbox-theme">
						<input class="form-check-input" type="checkbox" value="" required="required" id="termsOfService">
						<label class="form-check-label clause" for="termsOfService"><?php echo $register_agree; ?></label>
					</div>
				</div>
				<?php }?>
				<div class="form-group">
					<input type="hidden" name="action" value="xintheme_register">
					<button type="submit" class="btn-md btn-theme">立即注册</button>
				</div>
			</form>
			<p>
				已有账号？<a href="<?php echo home_url(user_trailingslashit('/user/login')); ?>"> 点击登录 </a>
			</p>
		</div>
	</div>
</div>
<?php }else{
    wp_safe_redirect( home_url(user_trailingslashit('/user/login')) );
    die();
}?>