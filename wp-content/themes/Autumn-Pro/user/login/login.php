<div class="col-lg-7 col-md-12 col-pad-0 align-self-center">
	<div class="login-inner-form">
		<div class="details">
			<h3 class="none-992">登录账号</h3>
			<form action="" class="form login" method="POST" id="login_form">
				<div class="login-trps d-tips"></div>
				<div class="form-group login_name">
					<input id="login_name" type="text" name="login_name" class="input-text" value="" placeholder="输入用户名/邮箱">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<div class="form-group password">
					<input id="password" type="password" name="password" class="input-text" value="" placeholder="输入登录密码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<?php if( !wpjam_theme_get_setting('close_vercode') ){?>
				<div class="form-group vercode">
					<input id="vercode" type="text" autocomplete="off" name="vercode" class="input-text" value="" placeholder="输入验证码">
					<img onclick="this.src=this.src+'?k='+Math.random();" src="<?php echo home_url(user_trailingslashit('/user/code')); ?>" alt="点击刷新验证码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<?php }?>
				<div class="checkbox clearfix">
					<a class="wangji" href="<?php echo home_url(user_trailingslashit('/user/lostpassword')); ?>">忘记密码？</a>
				</div>
				<div class="form-group">
					<input type="hidden" name="action" value="xintheme_login">
					<button type="submit" class="btn-md btn-theme">登录</button>
				</div>
			</form>
			<?php if( get_option('users_can_register') ){?>
			<p>
				还没有注册账号？<a href="<?php echo home_url(user_trailingslashit('/user/register')); ?>"> 点击注册 </a>
			</p>
			<?php }?>

			<?php if( get_option('users_can_register') ){?>
				<?php $login_actions = wpjam_get_login_actions('login'); if($login_actions){?>
					<ul class="mobile-social-list clearfix">

						<?php if(isset($login_actions['sms'])){ ?><li><a href="<?php echo home_url(user_trailingslashit('/user/mobile-login')); ?>" class="mobile-bg"><i class="iconfont icon-shouji"></i> 短信登录</a></li><?php } ?>
					</ul>
				<?php }?>
			<?php }?>

		</div>
	</div>
</div>