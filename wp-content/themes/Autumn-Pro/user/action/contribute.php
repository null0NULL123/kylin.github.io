<?php 
$post_id = $_GET['post_id']??'';
$post_content = '';
$cat_id = [];
$post_thumbnail_src = '';
$thumbnail_id = '';

if( $post_id ){
	$get_post = get_post($post_id);

	$post_title = $get_post->post_title;
	$post_content = $get_post->post_content;
	$thumbnail_id = get_post_thumbnail_id($post_id);
	$attachment_image_src = wp_get_attachment_image_src($thumbnail_id,'full');
    $post_thumbnail_src = $attachment_image_src[0];

    $category = get_the_category($post_id);
    foreach ($category as $key => $value) {
    	$cat_id[] = $value->cat_ID;
    }

    if( $get_post->post_author != $user_id ){
        wp_die('系统异常');
    }

}
?>
<div class="wp-editor col-lg-9">
	<div class="row posts-wrapper2">
	
	<div class="tougao">
		<h3 class="section-title"><span>文章投稿</span></h3>
		<form action="" method="POST" id="post_form">
		<div class="tougao-editor">
			<h2><input name="post_title" id="post_title" type="text" placeholder="请输入文章标题..." value="<?php echo $post_title;  ?>"></h2>
			<div class="tgbjqzw editor">
				<?php
                    $content = $post_content ?? '';
                    $editor_id = 'editor'; 
                    $settings = array( 
                        'quicktags'     => true, 
						'media_buttons' => true,
                    );
                    wp_editor( $content, $editor_id, $settings );
                ?>
			</div>
		</div>

		<div class="content-editor">
			<div class="category">
				<b>选择文章分类</b>
				<div class="package">

					<select name="cats[]" title="请选择所属分类">
					<?php 
						$cats = get_categories( array( 'hide_empty' => false ) );
						foreach ($cats as $key => $value) {
							if( $cat_id ){
								if( in_array($value->term_id, $cat_id) ){
									echo '<option selected value="'.$value->term_id.'">'.$value->name.'</option>';
								}else{
									echo '<option value="'.$value->term_id.'">'.$value->name.'</option>';
								}
							}else{
								echo '<option value="'.$value->term_id.'">'.$value->name.'</option>';
							}
						}
					?>
					</select>
					
				</div>
			</div>
			<div class="thumbnail">
				<b>文章缩略图</b>
				<div class="package">
					<img src="<?php echo $post_thumbnail_src; ?>" class="thumbnail" style="width: 100px; margin-right: 20px; <?php  if( !$post_thumbnail_src ){ echo 'display:none;'; }?>">
					<input type="hidden" name="thumbnail" class="thumbnail" value="<?php echo $thumbnail_id; ?>">
					<a class="select-img" href="javascript:;">选择图片</a>
					<span>不设置则调用文章内图片</span>
				</div>
			</div>

		</div>
		<input type="hidden" name="action" id="publish_post" value="publish_post">
		<input type="hidden" name="post_status" id="post_status" value="">
		<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
		</form>

		<div class="tijiao">
			<a href="javascript:;" data-status="draft" class="caogao publish_post">保存草稿</a>
			<a href="javascript:;" data-status="pending" class="shenhe publish_post">提交审核</a>
		</div>


	</div>
</div>
</div>
<?php wp_enqueue_media(); ?> 