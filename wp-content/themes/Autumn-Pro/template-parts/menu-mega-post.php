<article class="grid-item">

	<div class="post">

		<div class="entry-media with-placeholder">

			<a href="<?php the_permalink(); ?>" rel="nofollow">
				<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($post,array(500,345), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
			</a>

			<?php if(has_post_format('gallery')) { //相册 ?>
			<div class="entry-format">
				<i class="iconfont icon-xiangce"></i>
			</div>
			<?php }elseif(has_post_format('video')) { //视频 ?>
			<div class="entry-format">
				<i class="iconfont icon-shipin"></i>
			</div>
			<?php }elseif(has_post_format('audio')) { //音频 ?>
			<div class="entry-format">
				<i class="iconfont icon-yinpin"></i>
			</div>
			<?php } ?>
		</div>
		<div class="entry-wrapper">
			<header class="entry-header">
				<h2 class="entry-title">
					<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
				</h2>
			</header>
		</div>

	</div>

</article>