<div class="col-lg-7 col-md-12 col-pad-0 align-self-center">
	<div class="login-inner-form">
		<div class="details">
			<h3 class="none-992">手机短信登录</h3>
			<div class="login-trps d-tips page-register"></div>
			<form action="" class="form" method="POST" id="mobile_login_form">

				<div class="form-group email">
					<input type="text" id="phone" name="phone" value="" class="input-text" placeholder="输入手机号码">
					<div class="lp-trps"><i></i> <span></span></div>
				</div>

				<div class="form-group fieldset" style="position:relative;">
					<input class="input-control inline full-width has-border input-text" id="code" type="text" name="code" placeholder="输入短信验证码" required>
					<input type="button" class="captcha-clk inline" id="send_sms" value="获取验证码">
					<div class="lp-trps"><i></i> <span></span></div>
            	</div>

				<div class="form-group">
					<input type="hidden" name="action" value="">
					<button type="submit" class="btn-md btn-theme">立即登录</button>
				</div>
			</form>
			<p>
				手机不在身旁？<a href="<?php echo home_url(user_trailingslashit('/user/login')); ?>"> 返回邮箱登录 </a>
			</p>
		</div>
	</div>
</div>

<script type="text/javascript">
var ajaxurl	= '<?php echo admin_url('admin-ajax.php', 'login_post'); ?>'

jQuery(function($){
	$('body').on('submit', '#mobile_login_form', function(e){
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
			action:	'sms-signup',
			phone:	phone,
			code:	code
		},function(data, status){
			if(data.errcode){
				alert(data.errmsg);
			}else{
				window.location.href	= '<?php echo home_url(user_trailingslashit('/user')); ?>';
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

