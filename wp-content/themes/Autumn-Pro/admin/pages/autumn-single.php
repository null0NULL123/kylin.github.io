<?php

if(!WPJAM_Verify::verify()){
	wp_redirect(admin_url('admin.php?page=wpjam-basic'));
	exit;		
}

add_filter('wpjam_theme_setting', function(){

	$single_sub_fields		= [
		'single_read'		=> '阅读数量',
		'single_comment'	=> '评论数量',
		'single_share'		=> '文章分享',
		'single_fav'		=> '文章收藏',
		'single_like'		=> '点赞数量',
		'post_up_down'		=> '不显示 上一篇/下一篇',
		'xintheme_author'	=> '作者模块',
		'text_indent_2'		=> '文章段落缩进2个字符',
		'post_sidebar_author'=> '文章侧栏 显示作者模块',
		//'single_tag'		=> '文章标签',
	];

	$single_sub_fields		= array_map(function($desc){return ['title'=>'','type'=>'checkbox','description'=>$desc]; }, $single_sub_fields);
	
	$sections	= [

		'extend'	=>[
			'title'		=>'扩展选项', 
			'summary'	=>'<p>下面的选项，可以让你选择性显示或关闭一些功能。</p>',
			'fields'	=>[
				'single'		=>['title'=>'文章详情页',	'type'=>'fieldset',	'fields'=>$single_sub_fields],
			],	
		],
		'poster'	=>[
			'title'		=>'分享海报', 
			'summary'	=>'<p>在文章内容底部显示，PHP生成分享海报，首次生成海报后储存图片到服务器，后面直接调用已生成图片，节省服务器资源</p>',
			'fields'	=>[
				'poster'		=> ['title'=>'开启文章海报', 'type'=>'checkbox', 'description'=>'勾选后开启，不勾选则不显示文章海报'],
				'poster_logo'	=> ['title'=>'海报Logo', 'type'=>'img',	'item_type'=>'url',	'size'=>'240*50', 'description'=>'尺寸：240*50'],
				'poster_txt'	=> ['title'=>'Logo标语', 'type'=>'text', 'rows'=>4, 'description'=>'简短的一句话，显示在海报Logo下面'],
				
			],	
		],
		'reward'	=>[
			'title'		=>'打赏作者', 
			'summary'	=>'<p>在文章内容底部显示，可设置支付宝或者微信收款码</p>',
			'fields'	=>[

				'donate_title'	=> ['title'=>'打赏-文字描述信息', 'type'=>'text', 'rows'=>4, 'description'=>'如：万水千山总是情，打赏一块行不行'],
				'donate_weixin_img' => ['title'=>'微信收款码', 'type'=>'img',	'item_type'=>'url', 'description'=>'建议尺寸：150x150'],
				'donate_ali_img' => ['title'=>'支付宝收款码', 'type'=>'img',	'item_type'=>'url',	'description'=>'建议尺寸：150x150'],
	
			],	
		],

		
	];

	return compact('sections', 'field_validate');
});

flush_rewrite_rules();