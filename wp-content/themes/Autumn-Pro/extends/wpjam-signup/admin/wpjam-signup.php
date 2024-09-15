<?php
add_filter('wpjam_signup_setting', function(){
	$fields = [];

	if(is_multisite() && is_network_admin()){
		$fields['blog_id']	= ['title'=>'服务号博客ID',	'type'=>'number',	'class'=>'',	'description'=>'微信服务号安装的博客站点ID。'];
	}else{
		$signups	= wpjam_get_signups();

		foreach ($signups as $key => $signup) {
			$signup_title	= $signup['title'];

			$sub_fields		= [
				$key.'_login'	=> ['title'=>'',	'type'=>'checkbox',	'value'=>$signup['default'],	'description'=>'支持'.$signup['login_title']],
				$key.'_bind'	=> ['title'=>'',	'type'=>'checkbox',	'value'=>$signup['default'],	'description'=>'支持用户绑定'.$signup_title.'']
			];

			if($key == 'weapp' && isset($signups['weixin'])){
				$sub_fields['weapp_unionid']	=  ['title'=>'',	'type'=>'checkbox',	'value'=>0,	'description'=>$signup_title.'已经加入开放平台'];
			}

			$fields[$key]	= ['title'=>$signup_title,	'type'=>'fieldset',	'fields'=>$sub_fields];
		}
	}

	$ajax	= false;

	return compact('fields', 'ajax');
});