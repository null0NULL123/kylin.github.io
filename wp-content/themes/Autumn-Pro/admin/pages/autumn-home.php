<?php
add_action('admin_head',function(){ ?>
<style type="text/css">
	#tr_slide_type_img .wpjam-img.default{width: 75px;height: 50px}
	#tr_slide_type_img label.sub-field-label{font-weight: 400}
	#tr_slide_type_img input.all-options{width: 500px}

	#list_region_options label,#slide_region_options label{display:inline-block;width:156px;height:111px;background-repeat:no-repeat;background-size:contain;margin-right:10px;border:1px solid #ccc;margin-bottom:20px}

	#slide_region_options input,#slide_region_options input[type=radio]:checked::before,#list_region_options input,#list_region_options input[type=radio]:checked::before{display:none}
	
	<?php for ($i=1; $i<=5; $i++) { ?>

	#label_slide_region_<?php echo $i; ?>{	
	background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/slide-'.$i.'.png';?>);
	}

	#label_slide_region_<?php echo $i; ?> #slide_region_<?php echo $i; ?>:checked {
		border:4px solid #f44336;width:calc(100% + 2px);height:0;border-radius:0;display:block;margin-left:-1px;
		background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/slide-'.$i.'.png';?>);
	}

	<?php } ?>

	#label_list_region_col_3 #list_region_col_3:checked,#label_list_region_col_3_sidebar #list_region_col_3_sidebar:checked,#label_list_region_col_4 #list_region_col_4:checked,#label_list_region_list #list_region_list:checked,#label_list_region_list_2 #list_region_list_2:checked,#label_list_region_noimg_list #list_region_noimg_list:checked{border:4px solid #f44336;width:calc(100% + 2px);height:0;border-radius:0;display:block;margin-left:-1px;margin-top:-8px}
	#label_list_region_col_3,#label_list_region_col_3:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-1.png';?>)}
	#label_list_region_col_3_sidebar,#label_list_region_col_3_sidebar:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-2.png';?>)}
	#label_list_region_col_4,#label_list_region_col_4:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-3.png';?>)}
	#label_list_region_list,#label_list_region_list:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-4.png';?>)}
	#label_list_region_list_2,#label_list_region_list_2:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-5.png';?>)}
	#label_list_region_noimg_list,#label_list_region_noimg_list:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-6.png';?>)}
	
</style>


<?php });

add_filter('wpjam_theme_setting', function(){

	$show_if_1	= ['key'=>'slide_type', 'value'=>'post'];
	$show_if_2	= ['key'=>'slide_type', 'value'=>'img'];
	$show_if_3	= ['key'=>'slide_type', 'value'=>'img_one'];

	$show_if_1_2	= ['key'=>'slide_region', 'value'=>'4'];

	$sections	= [
		

		'banner'	=>[
			'title'		=>'首页 Banner', 
			'fields'	=>[
				'slide_type'		=> ['title'=>'Banner 类型', 'type'=>'radio', 'options'=>['post'=>'指定文章','img'=>'图片轮播','img_one'=>'单张图片']],

				'slide_type_img'	=> ['title'=>'图片轮播选项', 'type'=>'mu-fields', 'show_if'=>$show_if_2, 'fields'=>[

					'img_url'		=> ['title'=>'上传图像（PC端）', 'type'=>'img', 'item_type'=>'url', 'description'=>'没有建议尺寸，喜欢就好'],
					'img_url_mb'	=> ['title'=>'上传图像（手机端）', 'type'=>'img', 'item_type'=>'url', 'description'=>'建议尺寸：750*580'],
					'img_title'		=> ['title'=>'图片标题',	'type'=>'text', 'class'=>'all-options'],
					'img_ms'		=> ['title'=>'图片描述',	'type'=>'text',	'class'=>'all-options'],
					
					'img_btn1_txt'	=> ['title'=>'按钮-1 标题',	'type'=>'text',	'class'=>'all-options'],
					'img_btn1_url'	=> ['title'=>'按钮-1 链接',	'type'=>'text',	'class'=>'all-options'],
					
					'img_btn2_txt'	=> ['title'=>'按钮-2 标题',	'type'=>'text',	'class'=>'all-options'],
					'img_btn2_url'	=> ['title'=>'按钮-2 链接',	'type'=>'text',	'class'=>'all-options'],

				]],

				'img_one_url'		=> ['title'=>'上传Banner图像（PC端）', 'type'=>'img', 'show_if'=>$show_if_3, 'item_type'=>'url', 'description'=>'没有建议尺寸，越大越好，喜欢就好'],
				'img_one_url_mb'	=> ['title'=>'上传Banner图像（手机端）', 'type'=>'img', 'show_if'=>$show_if_3, 'item_type'=>'url', 'description'=>'建议尺寸：750*580'],
				'img_one_title'		=> ['title'=>'Banner标题', 'type'=>'text', 'show_if'=>$show_if_3, 'rows'=>4],
				'img_one_ms'		=> ['title'=>'Banner描述', 'type'=>'text', 'show_if'=>$show_if_3, 'rows'=>4],

				'slide_region'		=> ['title'=>'文章轮播样式', 'type'=>'radio', 'show_if'=>$show_if_1, 'options'=>['1'=>'','2'=>'','4'=>'','3'=>'','5'=>''], 'show_admin_column'=>true],
				'slide_bg_img'		=>['title'=>'背景图像',	'type'=>'img', 'show_if'=>$show_if_1_2, 'item_type'=>'url', 'description'=>'上传一张背景图像'],
				'slide_post_id'		=>['title'=>'调用文章', 'type'=>'mu-text', 'show_if'=>$show_if_1, 'data_type'=>'post_type', 'post_type'=>'post', 'class'=>'all-options', 'placeholder'=>'', 'description'=>'请输入文章ID或者关键字进行筛选'],
			]
		],

		'index_cat'	=> [
			'title'		=> '分类模块',
			'fields'	=> [
				'index_cat'			=> ['title'=>'分类模块', 'type'=>'radio', 'options'=>['1'=>'关闭','2'=>'一组3个，超出可轮播','3'=>'一组4个，超出可轮播']],
				'index_cat_lb'		=> ['title'=>'轮播按钮',	'type'=>'checkbox',	'description'=>'显示轮播切换按钮，分类过多时候可以轮播切换'],
				'index_cat_id'		=> ['title'=>'填写分类id', 'type'=>'mu-text', 'description'=>'可添加多个id，拖动排序【此处设置仅在「分类模块」开启后生效，<span style="color:#F44336">分类内文章数量小于3篇请不要设置</span>】'],
			]
		],

		'index_list'	=> [
			'title'		=> '文章列表',
			'fields'	=> [
				'list_region'		=> ['title'=>'文章列表', 'type'=>'radio', 'options'=>['col_3'=>'','col_3_sidebar'=>'','col_4'=>'','list'=>'','list_2'=>'','noimg_list'=>'']],
				'new_title'			=> ['title'=>'模块标题',	'type'=>'checkbox',	'description'=>'显示【最新文章】标题'],
			]
		]

		
	];

	return compact('sections');
});