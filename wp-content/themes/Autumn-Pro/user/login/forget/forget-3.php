<div class="col-lg-7 col-md-12 col-pad-0 align-self-center">
	<div class="login-inner-form">
		<div class="details">
			<h3 class="none-992">重置密码</h3>
			<div class="login-trps d-tips page-lostpassword"></div>

			<div class="lostpassword clearfix">
				<ul>
				    <li>
				        <div class="w-cir"></div>
				        <p>邮箱/用户名</p>
				    </li>
				    <li>
				        <div class="w-cir"></div>
				        <p>获取验证码</p>
				    </li>
				    <li class="current">
				        <div class="w-cir"></div>
				        <p>重置密码</p>
				    </li>
				    <li>
				        <div class="w-cir"></div>
				        <p>重置成功</p>
				    </li>
				</ul>
			</div>

			<div class="form login">

				<div class="form-group password_1">
					<input type="password" id="password_1" name="password_1" value="" class="input-text" placeholder="输入密码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>
				<div class="form-group password_2">
					<input type="password" id="password_2" name="password_2" value="" class="input-text" placeholder="重复输入密码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>

				<div class="form-group">
					<input type="submit" class="get_pwd-3 btn-md btn-theme" value="确认修改">
				</div>

			</div>

			<p>
				记起密码了？<a href="<?php echo home_url(user_trailingslashit('/user/login')); ?>"> 返回邮箱登录 </a>
			</p>
		</div>
	</div>
</div>