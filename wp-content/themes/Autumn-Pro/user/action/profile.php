<?php 
if(is_user_logged_in()){
	global $current_user; 
	wp_get_current_user();
	$uid = $current_user->ID;

	$action2 = $_POST['action2']??'';
	
	if($action2 == '1'){
		$error = 0;$msg = '';
		$userdata = array();
		$userdata['ID'] = $uid;
		$userdata['nickname'] = str_replace(array('<','>','&','"','\'','#','^','*','_','+','$','?','!'), '', $_POST['nickname']);
		$userdata['display_name'] = $userdata['nickname'];
		$userdata['description'] = esc_sql($_POST['description']);
		wp_update_user($userdata);
		$error = 0;	
		$msg = '用户资料修改成功';
		echo '<div class="cg-notify-message ng-scope '.($error?'alert-danger':'alert-success').'" ng-class="$classes" style="z-index: 800; margin-left: -68px; top: 150px; margin-top: -60px; visibility: visible; opacity: 1;" data-closing="true"><div ng-show="!$messageTemplate" class="ng-binding">'.$msg.'</div></div><script>setTimeout("$(\'.cg-notify-message\').hide();",2000)</script>';
		
	}elseif($action2 == '3'){
		$error = 0;$msg = ''; 
    	$password = esc_sql($_POST['password']); 
		$password2 = esc_sql($_POST['password2']); 
		if(strlen($password) < 6){
			$error = 1;
			$msg = '密码长度至少6位';
		}elseif($password != $password2){
			$error = 1;
			$msg = '两次输入密码不一致';
		}else{
			$userdata = array();
			$userdata['ID'] = wp_get_current_user()->ID;
			$userdata['user_pass'] = $password;
			wp_update_user($userdata);
			$error = 0;
			$msg = '用户密码修改成功';
		}
		echo '<div class="cg-notify-message ng-scope '.($error?'alert-danger':'alert-success').'" ng-class="$classes" style="z-index: 800; margin-left: -68px; top: 150px; margin-top: -60px; visibility: visible; opacity: 1;" data-closing="true"><div ng-show="!$messageTemplate" class="ng-binding">'.$msg.'</div></div><script>setTimeout("$(\'.cg-notify-message\').hide();",2000)</script>';
	}

}?>

<div class="user-profile col-lg-9">
	<div class="row posts-wrapper">
		<h3 class="section-title"><span>账号信息</span></h3>
		<?php if($action == 'profile'){?>
		<form action="<?php echo get_bloginfo('template_url');?>/user/action/avatar.php" method="post" class="form-horizontal account-form ng-pristine ng-valid ng-scope ng-valid-required" role="form" name="AvatarForm" id="AvatarForm"  enctype="multipart/form-data">
            <div class="form-group">
            <div class="col-sm-4 col-sm-offset-2">
				<div class="avatar-editor"> <span class="avatar" style="background-image: url(<?php echo get_avatar_url(get_current_user_id());?>)"></span> <span class="name ng-binding"></span>
					<a class="edit link-upload ng-scope" href="javascript:void(0)" ng-if="!progress"> 修改头像
					<input type="file" name="addPic" id="addPic" ng-multiple="false" accept=".jpg, .gif, .png" resetonclick="true">
					</a>
				</div>
            </div>
            </div>
        </form>
        <form action="" method="post" class="form-horizontal account-form ng-pristine ng-valid ng-scope ng-valid-required" role="form" ng-submit="submitForm($event)" name="BasicInfoForm" ng-if="formData.id">
			<div class="form-group">
				<label class="col-sm-2 control-label">用户ID</label>
				<div class="col-sm-4">
					<input style="cursor: no-drop;" type="text" class="form-control ng-pristine ng-untouched ng-valid ng-valid-required" data-ng-model="formData.username" name="username" required="" value="<?php echo $current_user->user_login;?>" disabled="disabled">
				</div>
			</div>
			<div class="form-group">
                <label class="col-sm-2 control-label">用户昵称</label>
				<div class="col-sm-4">
					<input type="text" class="form-control ng-pristine ng-untouched ng-valid ng-valid-required" data-ng-model="formData.nickname" name="nickname" required="" placeholder="请输入用户昵称" value="<?php echo $current_user->nickname;?>">
                </div>
            </div>
                    
            <div class="form-group">
                <label class="col-sm-2 control-label">邮箱</label>
                <div class="col-sm-4">
					<input style="cursor: no-drop;" type="email" class="form-control ng-pristine ng-untouched ng-valid ng-valid-required" data-ng-model="formData.email" required="" placeholder="请输入email" value="<?php echo $current_user->user_email;?>" name="email" disabled="disabled" >
                </div>
                <div class="col-sm-6 ng-scope" ng-if="(originData.unconfirmed_email || originData.email)"></div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">一句话简介</label>
                <div class="col-sm-9">
					<textarea style="height: 100px;margin-bottom: 30px;" class="form-control" placeholder="" name="description"><?php echo $current_user->description;?></textarea>
                </div>
                <div class="col-sm-4 error-tip"></div>
            </div>
            <div class="form-group">
				<div class="col-sm-10 col-sm-offset-2">
					<input type="hidden" name="action2" value="1">
					<button type="submit" class="btn btn-primary btn-lg ladda-button"><span class="ladda-label">提交</span><span class="ladda-spinner"></span></button>
                </div>
            </div>
        </form>
		<script src="<?php echo get_bloginfo('template_url');?>/static/js/jquery.form.js"></script>
        <script>
			jQuery(function($){
			$("#addPic").change(function(){
				$("#AvatarForm").ajaxSubmit({
					dataType:  'json',
					beforeSend: function() {
						//return tips('上传中...');	
					},
					uploadProgress: function(event, position, total, percentComplete) {
						
					},
					success: function(data) {
						if (data == "1") {
							//tips('头像修改成功');
							location.reload();     
						}else if(data == "2"){
							 alert('图片大小请不要超过1M');	
						}else if(data == "3"){
							 alert('图片格式只支持.jpg .png .gif');	
						}else{
							 alert('上传失败');	
						}
					},
					error:function(xhr){
						alert('上传失败.');	
					}
				});
	
			});
			});
		</script>
		<?php } elseif($action == 'password'){?>
        <form action="" method="post" class="form-horizontal account-form ng-pristine ng-scope ng-invalid ng-invalid-required ng-valid-minlength" role="form" name="PasswordForm">
            <div class="form-group">
                <label class="col-sm-2 control-label">输入新密码</label>
                <div class="col-sm-6">
                    <input type="password" class="form-control ng-pristine ng-untouched ng-invalid ng-invalid-required ng-valid-minlength" placeholder="请输入6位以上密码" data-ng-model="user.password" required="" minlength="6" name="password">
                </div>
            </div>
                    
            <div class="form-group">
                <label class="col-sm-2 control-label">重复新密码</label>
                <div class="col-sm-6">
                    <input type="password" class="form-control ng-pristine ng-untouched ng-invalid ng-invalid-required ng-valid-minlength" data-ng-model="user.password2" required="" minlength="6" name="password2">
                </div>
            </div>
                
            <div class="form-group">
                <div class="col-sm-10 col-sm-offset-2">
                    <input type="hidden" name="action2" value="3">
                    <button type="submit" class="btn btn-primary btn-lg ladda-button" ng-click="submitForm($event)" ladda="submitLoading" data-style="expand-right"><span class="ladda-label">修改</span><span class="ladda-spinner"></span></button>
                </div>
            </div>
        </form>
		<?php } ?>
		
	</div>
</div>