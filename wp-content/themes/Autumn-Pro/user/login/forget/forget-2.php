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
				    <li class="current">
				        <div class="w-cir"></div>
				        <p>获取验证码</p>
				    </li>
				    <li>
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

                <div class="ver-mail-2">为了账号安全，需要验证邮箱有效性，一封包含有验证码的邮件已经发送至邮箱：<span><?php echo hideStar($user->data->user_email); ?></span></div>
                <div class="ver-mail-2">请及时登录邮箱查看邮件，把「验证码」填写到下面即可重置密码！</div>
                <div class="ver-mail-3">没有收到验证邮件？请查看是否分类到垃圾邮件/广告邮件目录。</div>

				<div class="form-group vcode">
					<input id="vcode" type="text" name="vcode" class="input-text" value="" placeholder="输入验证码">
					<button id="btnGetCode">重新发送(52)</button>
				</div>

				<script>
					$(function(){
						showTime();
					})
				</script>

				<div class="form-group">
					<input type="submit" class="get_pwd-2 btn-md btn-theme" value="下一步">
				</div>

			</div>

			<p>
				记起密码了？<a href="<?php echo home_url(user_trailingslashit('/user/login')); ?>"> 返回邮箱登录 </a>
			</p>
		</div>
	</div>
</div>