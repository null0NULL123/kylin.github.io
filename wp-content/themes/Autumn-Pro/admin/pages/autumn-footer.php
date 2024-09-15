<?php
if(!WPJAM_Verify::verify()){
	wp_redirect(admin_url('admin.php?page=wpjam-basic'));
	exit;		
}

add_filter('wpjam_theme_setting', function(){
	$show_if_1	= ['key'=>'footer_type', 'value'=>'1'];
	$show_if_2	= ['key'=>'footer_type', 'value'=>'2'];
	
	$fields	= [
		'footer_type'		=> ['title'=>'页脚样式',		'type'=>'radio', 'options'=>['1'=>'样式-1','2'=>'样式-2']],
		
		'foot_logo'			=> ['title'=>'页脚 LOGO',	'type'=>'img',		'show_if'=>$show_if_2,	'item_type'=>'url',	'size'=>'152*50',	'description'=>'建议尺寸：180*54'],
		'foot_describe'		=> ['title'=>'页脚描述',		'type'=>'textarea',	'show_if'=>$show_if_2,	'description'=>'可使用html标签，显示在页脚Logo下方，页脚菜单请在【后台 - 外观 - 菜单】中进行设置'],
		'foot_social'		=> ['title'=>'社交工具',		'type'=>'checkbox', 'show_if'=>$show_if_2,	'description'=>'页脚显示社交工具，相关信息在【社交工具】中设置',],

		'foot_copyright'	=> ['title'=>'自定义页脚版权信息',	'type'=>'textarea', 'description'=>'可使用html标签，留空则显示默认页脚版权信息'],

		'footer_icp'		=> ['title'=>'网站备案号', 'type'=>'text', 'rows'=>4],
		'foot_link'			=> ['title'=>'友情链接', 'type'=>'checkbox', 'description'=>'激活“友情链接”，显示在首页底部，在【后台 - 连接】中添加友情链接'],
		'foot_timer'		=> ['title'=>'页面加载时间', 'type'=>'checkbox', 'description'=>'页脚显示当前页面加载时间'],
		'xintheme_link'		=> ['title'=>'不显示主题版权信息',	'type'=>'checkbox', 'description'=>'因为是收费主题，你可以勾选此选项来删除页脚的主题版权链接，当然，如果你保留链接，我们也对你表示感谢！'],
	];

	return compact('fields');
});