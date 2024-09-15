<?php
add_action('admin_head',function(){ ?>

	<style type="text/css">
		#tr_cat_banner_img,#tr_cat_banner_text_align,#div_cat_banner_img,#div_cat_banner_text_align{display: none}
		#div_cat_banner_type #label_cat_banner_type_1,#div_cat_banner_type #label_cat_banner_type_2,#div_cat_banner_text_align #label_cat_banner_text_align_left,#div_cat_banner_text_align #label_cat_banner_text_align_center,#label_cat_list_type_col_3,#label_cat_list_type_col_3_sidebar,#label_cat_list_type_col_4,#label_cat_list_type_list{margin-right: 10px;display: inline-block}
		.form-field > label {font-size: 14px;font-weight: 600 !important;margin-bottom: 10px}

		#cat_list_type_options label{display:inline-block;width:156px;height:111px;background-repeat:no-repeat;background-size:contain;margin-right:10px}
		#cat_list_type_options label{border:1px solid #ccc;margin-bottom:20px}
		#cat_list_type_options input,#cat_list_type_options input[type=radio]:checked::before{display:none}

		#label_cat_list_type_col_3 #cat_list_type_col_3:checked,#label_cat_list_type_col_3_sidebar #cat_list_type_col_3_sidebar:checked,#label_cat_list_type_col_4 #cat_list_type_col_4:checked,#label_cat_list_type_list #cat_list_type_list:checked,#label_cat_list_type_list_2 #cat_list_type_list_2:checked,#label_cat_list_type_noimg_list #cat_list_type_noimg_list:checked{border:4px solid #f44336;width:calc(100% + 2px);height:0;border-radius:0;display:block;margin-left:-1px;margin-top:-8px}
		#label_cat_list_type_col_3,#label_cat_list_type_col_3:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-1.png';?>)}
		#label_cat_list_type_col_3_sidebar,#label_cat_list_type_col_3_sidebar:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-2.png';?>)}
		#label_cat_list_type_col_4,#label_cat_list_type_col_4:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-3.png';?>)}
		#label_cat_list_type_list,#label_cat_list_type_list:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-4.png';?>)}
		#label_cat_list_type_list_2,#label_cat_list_type_list_2:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-5.png';?>)}
		#label_cat_list_type_noimg_list,#label_cat_list_type_noimg_list:checked{background-image: url(<?php echo get_stylesheet_directory_uri().'/static/images/set/post-list-6.png';?>)}

	</style>

	<script type="text/javascript">
	jQuery(function($){
		
		$('tr#tr_cat_banner_img').hide();
		$('tr#tr_cat_banner_text_align').hide();
		$('#div_cat_banner_img').hide();
		$('#div_cat_banner_text_align').hide();
		
		$('body').on('change', '#cat_banner_type_options input', function(){
			$('tr#tr_cat_banner_img').show();
			$('tr#tr_cat_banner_text_align').show();
			$('#div_cat_banner_img').show();
			$('#div_cat_banner_text_align').show();
			if ($(this).is(':checked')) {
				if($(this).val() != '2'){
					$('tr#tr_cat_banner_img').hide();
					$('tr#tr_cat_banner_text_align').hide();
					$('#div_cat_banner_img').hide();
					$('#div_cat_banner_text_align').hide();
				}
			}			
		});

		//【优化选中显示】当选中为Banner样式2的时候，即使刷新页面，也会默认显示上传背景图像和选中显示位置
		if(document.getElementById("cat_banner_type_2").checked){
			$('tr#tr_cat_banner_img').show();
			$('tr#tr_cat_banner_text_align').show();
		}else{
			$('tr#tr_cat_banner_img').hide();
			$('tr#tr_cat_banner_text_align').hide();
		} 

		//$('select#cat_banner_type').change();
	});
	</script>

<?php });


add_filter('wpjam_category_term_options',function ($post_options){
	$term_options['cat_list_type']	= ['title'=>'列表样式', 'type'=>'radio', 'options'=>['col_3'=>'','col_3_sidebar'=>'','col_4'=>'','list'=>'','list_2'=>'','noimg_list'=>'']];
	$term_options['cat_banner_type'] = ['title'=>'Banner 样式', 'type'=>'radio', 'options'=>['1'=>'常规样式','2'=>'背景图像+分类标题+分类描述']];
	$term_options['cat_banner_img']	= ['title'=>'Banner 背景图像', 'type'=>'img', 'item_type'=>'url', 'size'=>'152*50', 'description'=>'建议尺寸：1920*462'];
	$term_options['cat_banner_text_align']	= ['title'=>'分类标题+描述 显示位置',	'type'=>'radio', 'options'=>['left'=>'居左','center'=>'居中']];


	
	return $term_options;
});