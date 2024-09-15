<?php if(get_term_meta($cat, 'cat_banner_type', true) == '2'){ ?>

<div class="page-banner" style="background: url(<?php echo get_term_meta($cat, 'cat_banner_img', true); ?>); background-position: center center; -webkit-background-size: cover;background-size: cover;">
	<div class="dark-overlay"></div>
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="page-content" <?php if(get_term_meta($cat, 'cat_banner_text_align', true) == 'left' ){?>style="text-align: left;"<?php }?>>
					<h2><?php single_cat_title()?></h2>
					<p class="text-muted lead"><?php echo category_description() ?: '请在【后台 – 文章 – 分类 – 编辑 – 图像描述】中输入文本描述…'; ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<?php }