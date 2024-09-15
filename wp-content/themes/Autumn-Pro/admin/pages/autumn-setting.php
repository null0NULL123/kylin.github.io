<?php
add_action('admin_head',function(){ ?>
<style type="text/css">
	#tr_slide_type_img .wpjam-img.default{width: 75px;height: 50px}
	#tr_slide_type_img label.sub-field-label{font-weight: 400}
	#tr_slide_type_img input.all-options{width: 500px}

	#slide_region_options label{
	display:inline-block;
	width:156px;
	height:111px;
	background-repeat:no-repeat;
	background-size: contain;
	margin-right:10px;
	}

	#slide_region_options input{
		display: none;
	}
	
	<?php for ($i=1; $i<=5; $i++) { ?>

	#label_slide_region_<?php echo $i; ?>{	
	background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/slide-'.$i.'.png';?>);
	}

	#label_slide_region_<?php echo $i; ?> #slide_region_<?php echo $i; ?>:checked {
		border:2px solid #1e8cbe;
		width: 100%;
		height: 0;
		border-radius: 0;
		background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/slide-'.$i.'.png';?>);
		display: block;
	}

	<?php } ?>
	
</style>

<?php });

if(!WPJAM_Verify::verify()){
	wp_redirect(admin_url('admin.php?page=wpjam-basic'));
	exit;		
}

add_filter('wpjam_theme_setting', function(){
	
	$global_sub_fields		= [
		'navbar_sticky'		=> '固定导航栏，导航栏一直固定显示在网站顶部',
		'feature_list'		=> '文章列表，全部使用「特色形式」显示,【只对网格文章列表生效】在网格文章列表中以“特色形式”展示，醒目的展示效果，适合重点文章使用',
		'sidebar_left'		=> '侧栏小工具显示在「左侧栏」，默认显示在右边',
		'head_dark_switch'	=> '导航栏显示「暗黑模式」，切换按钮',
		'width_1500'		=> '启用宽版，内容区域1500px',
		'mac_dark'	=> '暗黑模式跟随设备系统显示',
	];

	$list_sub_fields		= [
		'list_time'			=> '发布时间',
		'list_read'			=> '阅读数量',
		'list_comment'		=> '评论数量',
		'list_share'		=> '文章分享',
		//'list_like'		=> '点赞数量',
		'list_author'		=> '文章作者（网格列表样式下不要同时勾选「发布时间」，适合多用户网站使用，个人博客不建议勾选）',
		'list_cat_zsj'		=> '「分类目录」显示在文章缩略图左上角',
		'list_no_excerpt'	=> '不显示文章摘要，注意，是：不显示，不显示，不显示',
	];

/*
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
*/

	$global_sub_fields		= array_map(function($desc){return ['title'=>'','type'=>'checkbox','description'=>$desc]; }, $global_sub_fields);
	$list_sub_fields		= array_map(function($desc){return ['title'=>'','type'=>'checkbox','description'=>$desc]; }, $list_sub_fields);
	//$single_sub_fields		= array_map(function($desc){return ['title'=>'','type'=>'checkbox','description'=>$desc]; }, $single_sub_fields);
	
	$sections	= [
		'icon'	=>[
			'title'		=>'网站设置', 
			'fields'	=>[
				'scheme_set'	=> ['title'=>'网站配色',		'type'=>'fieldset',	'fields'=>[
					'theme_color'	=> ['title'=>'', 'type'=>'color', 'description'=>'主题主色调'],
					'dark_mode'		=> ['title'=>'', 'type'=>'checkbox', 'description'=>'开启暗黑模式'],
				]],

				'logo'		=> ['title'=>'网站Logo', 'type'=>'img',	'item_type'=>'url',	'size'=>'120*40', 'description'=>'网站 LOGO，尺寸：120x40'],
				'favicon'	=> ['title'=>'Favicon图标', 'type'=>'img',	'item_type'=>'url',	'description'=>'网站 Favicon'],
				'img_lazysizes'	=> ['title'=>'延迟加载图片', 'type'=>'img',	'item_type'=>'url',	'description'=>'延迟加载-默认加载图像'],
					
				// 'thumbnails'	=> ['title'=>'默认缩略图', 'type'=>'mu-img', 'item_type'=>'url', 'description'=>'文章没有设置缩略图也没有图片时候，使用该图中的随机一张，建议尺寸：420*260 px，会覆盖CDN加速设置中的默认缩略图设置。'],
			],	
		],

		'extend'	=>[
			'title'		=>'扩展选项', 
			'summary'	=>'<p>下面的选项，可以让你选择性显示或关闭一些功能。</p>',
			'fields'	=>[
				'global'		=>['title'=>'全局设置',	'type'=>'fieldset',	'fields'=>$global_sub_fields],
				'dark_logo'		=> ['title'=>'暗黑模式 - 网站Logo', 'type'=>'img', 'item_type'=>'url', 'show_if'=>['key'=>'head_dark_switch', 'value'=>1],	'description'=>'自定义暗黑模式下的网站Logo'],
				'list'			=>['title'=>'文章列表', 'type'=>'fieldset', 'fields'=>$list_sub_fields],
				//'single'		=>['title'=>'文章详情页',	'type'=>'fieldset',	'fields'=>$single_sub_fields],
				
				//'excerpt_count'	=> ['title'=>'文章摘要字数',	'type'=>'text', 'description'=>'默认截取115个字符，留空则显示默认值，如果勾选了上面的「不显示文章摘要」，此处设置将会无效。'],
				'paging_xintheme'	=> ['title'=>'分页样式',	 'type'=>'select', 'options'=>['1'=>'数字分页','2'=>'上一页|下一页','3'=>'点击按钮加载','4'=>'滚动页面自动加载']],
				'xintheme_copy'		=> ['title'=>'整站禁止复制', 'type'=>'checkbox', 'description'=>'用js onselectstart事件禁止选中文字，有效防止内容被访客复制'],
			],	
		],
		/*
		'poster'	=>[
			'title'		=>'文章海报', 
			'summary'	=>'<p>在文章内容底部显示，PHP生成分享海报，首次生成海报后储存图片到服务器，后面直接调用已生成图片，节省服务器资源</p>',
			'fields'	=>[
				'poster'		=> ['title'=>'开启文章海报', 'type'=>'checkbox', 'description'=>'勾选后开启，不勾选则不显示文章海报'],
				'poster_logo'	=> ['title'=>'海报Logo', 'type'=>'img',	'item_type'=>'url',	'size'=>'240*50', 'description'=>'尺寸：240*50'],
				'poster_txt'	=> ['title'=>'Logo标语', 'type'=>'text', 'rows'=>4, 'description'=>'简短的一句话，显示在海报Logo下面'],
				
			],	
		],
		*/
		'comments'	=>[
			'title'		=>'评论框扩展', 
			'summary'	=>'<p>主题调用WordPress默认评论框，如果你勾选了插件里面的【前台不加载语言包】评论和后台登陆界面就会显示英文。</p>',
			'fields'	=>[
				'comment_flower'	=> ['title'=>'礼花特效', 'type'=>'checkbox', 'description'=>'网站评论框，输入内容时增加礼花特效',],
				'comment_shock'		=> ['title'=>'震动特效', 'type'=>'checkbox', 'description'=>'需同时开启礼花特效，不然不会生效',],
				'comment-form-url'	=> ['title'=>'隐藏评论框网址栏', 'type'=>'checkbox',	'description'=>'勾选后评论框将不显示网址输入框'],
			],	
		],
		'click_effect'	=>[
			'title'		=>'网页特效', 
			'summary'	=>'<p>在网站任意位置点击鼠标，会不断的跳出你输入的词汇或者符号，留空则不显示。</p>',
			'fields'	=>[
				'click_effect'			=> ['title'=>'鼠标点击特效', 'type'=>'mu-text', 'description'=>'可添加多个词汇'],
				'click_effect_color'	=> ['title'=>'字体颜色',	'type'=>'color'],
				'nest_switcher'			=> ['title'=>'网页粒子背景 - Canvas-nest', 'type'=>'checkbox', 'description'=>'开启后下方设置才会生效',],
				'nest_opacity'			=> ['title'=>'线条透明度', 'type'=>'text',	'description'=>'一般设置成0.3-1之间'],
				'nest_count'			=> ['title'=>'线条疏密',	'type'=>'number', 'description'=>'取值范围是0-150','mim'=>1,'max'=>150],
				
			],	
		],

		
	];

	return compact('sections');
});

flush_rewrite_rules();