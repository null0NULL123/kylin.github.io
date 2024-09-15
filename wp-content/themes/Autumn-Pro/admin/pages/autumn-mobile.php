<?php
if(!WPJAM_Verify::verify()){
	wp_redirect(admin_url('admin.php?page=wpjam-basic'));
	exit;		
}

add_action('admin_head',function(){ ?>

<style>
	#tr_mobile_foot_menu .wpjam-img.default{width:105px;height:70px}
	#tr_mobile_foot_menu .sub-field-label{font-weight:400}
	.form-table #tr_mobile_foot_menu input[type="radio"]{margin-top: -2px}
	#tr_mobile_foot_menu .sub-field-detail span{margin-right:10px}

	#sub_field_mobile_foot_menu_img_0,#sub_field_mobile_foot_menu_img_1,#sub_field_mobile_foot_menu_img_2,#sub_field_mobile_foot_menu_img_3,#sub_field_mobile_foot_menu_img_4,#sub_field_mobile_foot_menu_img_5,#sub_field_mobile_foot_menu_img_6,#sub_field_mobile_foot_menu_img_7,#sub_field_mobile_foot_menu_img_8,#sub_field_mobile_foot_menu_img_9,#sub_field_mobile_foot_menu_img_10,#sub_field_mobile_foot_menu_img_text_0,#sub_field_mobile_foot_menu_img_text_1,#sub_field_mobile_foot_menu_img_text_2,#sub_field_mobile_foot_menu_img_text_3,#sub_field_mobile_foot_menu_img_text_4,#sub_field_mobile_foot_menu_img_text_5,#sub_field_mobile_foot_menu_img_text_6,#sub_field_mobile_foot_menu_img_text_7,#sub_field_mobile_foot_menu_img_text_8,#sub_field_mobile_foot_menu_img_text_9,#sub_field_mobile_foot_menu_img_text_10{display:none}
</style>

<?php });

add_filter('wpjam_theme_setting', function(){
	$fields	= [
		'mobile_setting'	=>['title'=>'扩展选项',	'type'=>'fieldset',	'fields'=>[
			'mobile_no_sidebar'		=> ['title'=>'','type'=>'checkbox','description'=>'手机端隐藏侧边栏内容，除「菜单栏」外，将不显示侧栏内容'],
			'mobile_foot_menu_no'	=> ['title'=>'','type'=>'checkbox','description'=>'开启手机端底部菜单【最多只能添加5个菜单】'],
			'mobile_foot_menu_700'	=> ['title'=>'','type'=>'checkbox','description'=>'手机端底部菜单，字体加粗！','show_if'=>['key'=>'mobile_foot_menu_no', 'value'=>1]],
		]],

		'mobile_foot_menu'	=> ['title'=>'手机端底部菜单', 'type'=>'mu-fields',	'show_if'=>['key'=>'mobile_foot_menu_no', 'value'=>1],	'total'=>5, 'fields'=>[
			'mobile_foot_menu_type'		=> ['title'=>'菜单类型', 'type'=>'radio', 'options'=>['link'=>'跳转链接','img'=>'弹出二维码','user'=>'登录/用户中心','home'=>'首页(必须放在第一位)']],
			'mobile_foot_menu_img'		=> ['title'=>'上传二维码', 'type'=>'img', 'show_if'=>['key'=>'mobile_foot_menu_type', 'value'=>'img'],	'item_type'=>'url', 'description'=>'建议尺寸：200*200 px'],
			'mobile_foot_menu_img_text'	=> ['title'=>'二维码标题', 'type'=>'text', 'show_if'=>['key'=>'mobile_foot_menu_type', 'value'=>'img'],	'class'=>'all-options'],

			'mobile_foot_menu_text'		=> ['title'=>'菜单名字', 'type'=>'text', 'show_if'=>['key'=>'mobile_foot_menu_type', 'compare'=>'IN', 'value'=>['link','img']],	'class'=>'all-options'],

			'mobile_foot_menu_icon'		=> ['title'=>'菜单图标', 'show_if'=>['key'=>'mobile_foot_menu_type', 'compare'=>'IN', 'value'=>['link','img']], 'type'=>'text',	'class'=>'all-options'],
			'mobile_foot_menu_url'		=> ['title'=>'跳转链接', 'type'=>'text', 'show_if'=>['key'=>'mobile_foot_menu_type', 'value'=>'link'],	'class'=>'all-options'],

		]],

	];

	$ajax = false;

	return compact('fields','ajax');
});