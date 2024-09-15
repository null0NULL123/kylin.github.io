<?php
if(empty($GLOBALS['current_tab'])){
	wpjam_register_plugin_page_tab('international-submail', ['title'=>'国际赛邮·云通信',	'function'=>'option',	'option_name'=>'wpjam_international_submail',	'tab_file'=>__DIR__.'/admin.php']);
}elseif($GLOBALS['current_tab'] == 'international-submail'){
	add_filter('wpjam_international_submail_setting', function(){
		$summary	= wpautop('1、点击这里注册<a href="https://wpjam.com/go/mysubmail/">赛邮·云通信服务</a>。
		2、点击这里获取 <a href="https://www.mysubmail.com/chs/internationalsms/apps">APPID 和 APPKEY</a>。
		3、按照要求在赛邮·云通信后台创模板项目，然后填回到下面对应的选项中。
		4、如需要接收回调，请把回调url填到<a href="https://www.mysubmail.com/chs/internationalsms/subhook">SUBHOOK 状态推送 </a>');

		$fields	= [
			'appid'                    => ['title'=>'APPID',	'type'=>'text'],
			'appkey'                   => ['title'=>'APPKEY',	'type'=>'text'],
			'project'                  => ['title'=>'项目',		'type'=>'text'],
			'subhook_key'              => ['title'=>'SubhookKey', 'type'=>'text'],
			'subhook_url'              => ['title'=>'回调url',		'type'=>'view', 'value' => home_url('api/sms/mysubmail/event/push.json')],
			'payed_notify_project'     => ['title'=>'订单支付通知项目',	'type'=>'text', 'placeholder' => '请填写您在短信服务提供商配置好的模板项目名'],
			'consigned_notify_project' => ['title'=>'订单发货通知项目',	'type'=>'text', 'placeholder' => '请填写您在短信服务提供商配置好的模板项目名'],
			'refunded_notify_project'  => ['title'=>'订单退款通知项目',	'type'=>'text', 'placeholder' => '请填写您在短信服务提供商配置好的模板项目名'],
		];
		
		return compact('fields', 'summary');
	});
}