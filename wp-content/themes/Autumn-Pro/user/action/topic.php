<div class="wp-editor col-lg-9">
	<div class="row posts-wrapper2">
	
	<div class="tougao">
		<h3 class="section-title"><span>发布帖子</span></h3>
		<form action="<?php echo home_url('user/topic'); ?>" method="POST" id="post_form" enctype="multipart/form-data">
		<div class="tougao-editor">
			<b>输入标题</b>
			<h2 style="margin-top: 15px"><input name="topic_title" class="topic_title" type="text" placeholder="请输入标题..." value="<?php echo $toptic_title;  ?>" required="required"></h2>
		</div>

		<b>输入内容</b>
		<textarea rows="1" name="topic_content" class="topic_content" placeholder="请输入内容..." required="required" style= "overflow:hidden; resize:none; "></textarea>

		<div class="content-editor">
			<div class="category">
				<b>选择分类</b>
				<div class="package">

					<select name="group_id" title="请选择所属分类">
						<?php
						$args=array(
							'taxonomy'		=> 'group', 
							'hide_empty'	=> false, 
							'meta_key'		=> 'order', 
							'orderby'		=> 'meta_value_num',
							'order'			=> 'DESC'	
						);
						$categories=get_categories($args);
						foreach($categories as $category){
							echo '<option value="'.$category->term_id.'">'.$category->name.'</option>';
						}?>
					</select>
					
				</div>
			</div>


			<!--点击上传图片 触发下面的div 点击事件-->
			<div class="upload_img_wrap"><!-- 暂时隐藏 -->
				<div id="imgBox"></div>
				<img class="upload_img" data-id="1" src="<?php echo get_stylesheet_directory_uri();?>/static/images/upload_img.png" />
				<img style="display:none" class="upload_img" data-id="2" src="<?php echo get_stylesheet_directory_uri();?>/static/images/upload_img.png" />
				<img style="display:none" class="upload_img" data-id="3" src="<?php echo get_stylesheet_directory_uri();?>/static/images/upload_img.png" />
			</div>
			<div style="display: none; width: 100%;height: 100vh;position: relative;">
				<!-- <form id="upBox" class="upload_form" action="" method="post" enctype="multipart/form-data"> -->
					<div style="display: none;" id="inputBox">
						<input type="file" name="image1" data-id="1" title="请选择图片" id="file1" accept="image/png,image/jpg,image/gif,image/JPEG" />
						<input type="file" name="image2" data-id="2" title="请选择图片" id="file2" accept="image/png,image/jpg,image/gif,image/JPEG" />
						<input type="file" name="image3" data-id="3" title="请选择图片" id="file3" accept="image/png,image/jpg,image/gif,image/JPEG" /> 点击选择图片
					</div>
					<!-- <input style="display:none" type="submit" id="sub" /> -->
				<!-- </form> -->
			</div>

			<script>	
				var imgNum = 0;
				$(".upload_img_wrap .upload_img").bind("click", function(ev) {
					//console.log(ev.currentTarget.dataset.id)
					var index = ev.currentTarget.dataset.id;
					var that = this;
					if(index == 1) {
						$("#file1").click();
						$("#file1").unbind().change(function(e) {
							var index = e.currentTarget.dataset.id;
							if($('#file').val() == '') {
								return false;
							}
							$(that).hide();
							var filePath = $(this).val();
							changeImg(e, filePath, index);
							
							imgNum++;
							if(imgNum<3){
								$(".upload_img").eq(1).show();
							}
							$(".upload_img_length").html(imgNum);
						})
					} else if(index == 2) {
						$("#file2").click();
						$("#file2").unbind().change(function(e) {
							var index = e.currentTarget.dataset.id;
							if($('#file').val() == '') {
								return false;
							}
							$(that).hide();
							var filePath = $(this).val();
							changeImg(e, filePath, index);
							
							imgNum++;
							if(imgNum<3){
								$(".upload_img").eq(2).show();
							}
							$(".upload_img_length").html(imgNum);
						})
					} else if(index == 3) {
						$("#file3").click();
						$("#file3").unbind().change(function(e) {
							var index = e.currentTarget.dataset.id;
							if($('#file').val() == '') {
								return false;
							}
							var filePath = $(this).val();
							changeImg(e, filePath, index);
							$(that).hide();
							imgNum++;
							$(".upload_img_length").html(imgNum);
						})
					}
				})

				function changeImg(e, filePath, index) {
					fileFormat = filePath.substring(filePath.lastIndexOf(".")).toLowerCase();
					//检查后缀名
					if(!fileFormat.match(/.png|.jpg|.jpeg/)) {
						showError('文件格式必须为：png/jpg/jpeg');
						return;
					}
					//获取并记录图片的base64编码
					var reader = new FileReader();
					reader.readAsDataURL(e.target.files[0]);
					reader.onloadend = function() {
						// 图片的 base64 格式, 可以直接当成 img 的 src 属性值        
						var dataURL = reader.result;
						// console.log(dataURL)
						// 显示图片
						$("#imgBox").html($("#imgBox").html() + '<div class="imgContainer" data-index=' + index + '><img   src=' + dataURL + ' onclick="imgDisplay(this)"><img onclick="removeImg(this,' + index + ')"  class="imgDelete" src="<?php echo get_stylesheet_directory_uri();?>/static/images/del_img.png" /></div>');
					};

				}

				function removeImg(obj, index) {
					for(var i = 0; i < $(".imgContainer").length; i++) {
						if($(".imgContainer").eq(i).attr("data-index") == index) {
							$(".imgContainer").eq(i).remove();
						}
					}
					for(var i = 0; i < $(".upload_img").length; i++) {
						$(".upload_img").eq(i).hide();
						if($(".upload_img").eq(i).attr("data-id") == index) {
							console.log($(".upload_img").eq(i).attr("data-id"))
							$(".upload_img").eq(i).show();
						}
					}
					imgNum--;
					$(".upload_img_length").html(imgNum);
				}
				
				/* 点击缩略图  放大
				function imgDisplay(obj) {
					var src = $(obj).attr("src");
					var imgHtml = '<div style="width: 100%;height: 100vh;overflow: auto;background: rgba(0,0,0,0.5);text-align: center;position: fixed;top: 0;left: 0;z-index: 1000;display: flex;justify-content: center;    align-items: center;"><img src=' + src + ' style="margin-top: 100px;width: 96%;margin-bottom: 100px;"/><p style="font-size: 50px;position: fixed;top: 30px;right: 30px;color: white;cursor: pointer;" onclick="closePicture(this)">×</p></div>'
					$('body').append(imgHtml);
				}
				
				function closePicture(obj) {
					$(obj).parent("div").remove();
				}
				*/
			</script>
		</div>
		

		<div class="tijiao">
			<!-- <a href="javascript:;" class="caogao publish_post">发布帖子</a> -->
			<input type="submit" name="topic" value="发布帖子">
		</div>

		</form>

	</div>
</div>
</div>