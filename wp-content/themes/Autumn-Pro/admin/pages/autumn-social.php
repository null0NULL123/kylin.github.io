<?php
add_filter('wpjam_theme_setting', function(){
	return [
		'title'		=>'社交工具', 
		'fields'	=>[
			//'social'			=> ['title'=>'显示社交工具',		'type'=>'checkbox', 'description'=>'社交工具显示在网站头部',],
			'autumn_weixin'		=> ['title'=>'上传微信二维码',	'type'=>'img',		'item_type'=>'url'],
			'autumn_qq'			=> ['title'=>'输入QQ号码',		'type'=>'text',		'rows'=>4],
			'autumn_weibo'		=> ['title'=>'输入微博链接',		'type'=>'text',		'rows'=>4],
			'autumn_mail'		=> ['title'=>'输入QQ邮箱账号',		'type'=>'text',		'rows'=>4],
			'cool_qq'			=> ['title'=>'炫酷的客服按钮',	'type'=>'checkbox', 'description'=>'在全站悬浮一个客服按钮',],
		],	
	];
});