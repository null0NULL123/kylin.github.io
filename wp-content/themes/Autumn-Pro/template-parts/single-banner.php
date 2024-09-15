<?php if( get_post_meta($post->ID, 'post_layout', true) == '2' ){ ?>

<div class="hero lazyload visible" data-bg="<?php echo get_post_meta($post->ID, 'header_img', true);?>">
	<?php if( get_post_meta($post->ID, 'header_video_id', true) ) { ?>

	<div class="hero-media">
		<div class="container">
		<div class="fluid-width-video-wrapper" style="padding-top: 56.25%;">
			<iframe src="https://v.qq.com/iframe/player.html?vid=<?php echo get_post_meta($post->ID, 'header_video_id', true);?>&tiny=0&auto=0" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen>
			</iframe>
		</div>
		</div>
	</div>

	<script type="text/javascript">
		var classVal = document.getElementById("body").getAttribute("class");
		//classVal = "hero-video " + classVal + " ";
		classVal = classVal .concat(" hero-video");document.getElementById("body").setAttribute("class",classVal );

	<?php if(wpjam_theme_get_setting('dark_mode')){
		echo 'classVal = classVal .concat(" dark-mode");document.getElementById("body").setAttribute("class",classVal );'; 
	}?>
	</script>

	<?php }else{ ?>

	<div class="hero-content"></div>

	<?php }?>
</div>
<?php }?>

<?php if(wp_is_mobile()){ ?>

<style type="text/css">.single .type-post .entry-header{padding:15px 15px 15px}.single .type-post .entry-wrapper{padding: 15px 15px 30px 15px;}</style>

<?php } ?>