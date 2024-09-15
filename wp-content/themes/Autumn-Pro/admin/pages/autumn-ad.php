<?php
add_filter('wpjam_theme_setting', function(){
	return [
		'sections'=>[
			'single_ad'	=>[
				'title'		=>'文章详情', 
				'fields'	=>[
					'single_top_ad'	=> ['title'=>'内容顶部广告',	'type'=>'textarea',	'description'=>'显示在文章内容顶部，建议图像尺寸：800*100，可使用html标签，也可以插入联盟广告代码。'],
					'middle_ad'		=> ['title'=>'内容中间广告', 'type'=>'fieldset',	'fields'=>[
						'single_middle_ad_number' => ['title'=>'', 'type'=>'text',	'description'=>'显示在第几个段落后面？默认显示在第四个段落下面'],
						'single_middle_ad'	=> ['title'=>'', 'type'=>'textarea', 'description'=>'显示在文章内容中间，第四个段落下面，建议图像尺寸：800*100，可使用html标签，也可以插入联盟广告代码。']
					]],
					'single_bottom_ad'	=> ['title'=>'内容底部广告',	'type'=>'textarea',	'description'=>'显示在文章内容底部，建议图像尺寸：800*100，可使用html标签，也可以插入联盟广告代码。']
				],	
			],
			'list_ad'	=>[
				'title'		=>'文章列表', 
				'fields'	=>[
					'post_list_ad'	=> ['title'=>'列表中顶部广告',	'type'=>'textarea',	'description'=>'建议图像尺寸：1130*100，显示在文章列表顶部，可使用html标签，也可以插入联盟广告代码。']
				],	
			],
		],

		'ajax'=>false,
	];
});