<?php

add_filter('wpjam_theme_setting', function(){	
	return [
		'title'		=>'维护模式',
		'summary'	=>'<p>维护模式仅用于网站调试阶段使用，开启后只有登陆状态下的管理员账号权限才可以看到网站的正常内容，其他访客将显示维护倒计时。</p>',
		'fields'	=> [
			'maintenance_show'		=> ['title'=>'开启维护模式',	'type'=>'checkbox',	'description'=>'网站正式上线后一定要记得关闭维护模式'],
			'maintenance_logo'		=> ['title'=>'Logo', 		'type'=>'img',	'item_type'=>'url',	'size'=>'200x70', 'description'=>'尺寸：200x70'],
			'maintenance_title'		=> ['title'=>'标题',			'type'=>'text', 'rows'=>4],
			'maintenance_container'	=> ['title'=>'描述内容',		'type'=>'textarea', 'description'=>'可使用html，例如：<pre>&lt;p&gt;<br>抱歉，我们的网站正在维护中...<br>&lt;span class="dull-text"&gt;请稍后再回来，我们会给你一个惊喜！&lt;/span&gt;<br>&lt;/p&gt;</pre>'],
			'maintenance_time'		=> ['title'=>'开放时间',		'type'=>'text',	'rows'=>4, 'description'=>'预计网站开放时间，例如：2019/07/02 02:00'],
		],
	];
});