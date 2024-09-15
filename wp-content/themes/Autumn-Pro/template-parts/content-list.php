<?php 
if(is_module('user')){
	$list_region	= 'list';
}elseif(is_category()){
	$list_region = get_term_meta($cat, 'cat_list_type', true);
}else{
	$list_region	= wpjam_theme_get_setting('list_region');
}

?>
<?php if($list_region == 'list' ) { ?>
<article class="post-list">
<div class="post-wrapper">
	<div class="entry-media fit">
		<div class="placeholder">
			<a href="<?php the_permalink(); ?>">
			<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($post,array(300,300), $crop=1); ?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
			</a>
		</div>
	</div>
	<div class="entry-wrapper">
		<header class="entry-header">
		<div class="entry-meta">
		<?php if(is_module('user')){?>
			<?php
			//if($post->post_status == 'draft' || $post->post_status == 'pending' ){
			if(is_module('user')){ ?>
				<span class="status"><a style="color:var(--accent-color)" href="<?php echo home_url(user_trailingslashit('/user/contribute'));?>?post_id=<?php echo $post->ID; ?>"><i class="iconfont icon-ykq_tab_tougao" style="font-size:12px;padding-right:2px;"></i>编辑文章</a></span>
			<?php } ?>

			<?php if($post->post_status == 'publish' ){ ?>
				<span class="status">已发布</span>
			<?php }else if($post->post_status == 'pending' ){ ?>
				<span class="status"> 等待审核</span>
			<?php }else if($post->post_status == 'draft' ){ ?>
				<span class="status"> 草稿</span>
			<?php } ?>
		<?php } ?>
			<span class="meta-category<?php if( wpjam_theme_get_setting('list_cat_zsj') ){ echo '-2'; } ?>">
				<?php the_category(' ');?>
			</span>
			<?php if(wpjam_theme_get_setting('list_time')){ ?>
			<span class="meta-time">
				<?php the_time('Y-m-d') ?>
			</span>
			<?php }?>
			<?php if(wpjam_theme_get_setting('list_author')){ ?>
			<span class="meta-author">
				<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>">
					<span><?php echo get_the_author() ?></span>
				</a>
			</span>
			<?php }?>
			<?php if(wpjam_theme_get_setting('list_read')){ ?>
				<span class="meta-view">
				<a class="view" href="<?php the_permalink(); ?>" rel="nofollow"><span class="count"><?php echo wpjam_get_post_views(get_the_ID()); ?> 次浏览</span></a>
				</span>
			<?php } ?>
			<?php if(wpjam_theme_get_setting('list_comment')){ ?>
			<span class="meta-comment">
				<a href="<?php the_permalink(); ?>#comments">
	            	<?php echo $post->comment_count; ?> 条评论
				</a>
			</span>
			<?php }?>

			<?php if(wpjam_theme_get_setting('list_share') && !is_module('user')){ ?>
			<span class="meta-share">
				<a class="share" href="<?php the_permalink(); ?>" rel="nofollow" weixin_share="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" data-url="<?php the_permalink(); ?>" data-title="<?php the_title(); ?>" data-thumbnail="<?php echo wpjam_get_post_thumbnail_url($post,array(350,150), $crop=1);?>" data-image="<?php echo wpjam_get_post_thumbnail_url($post,array(1130,848), $crop=1);?>">
					<span>分享</span>
				</a>
			</span>
			<?php } ?>

		</div>
		<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
		</header>
		<?php if( !wpjam_theme_get_setting('list_no_excerpt') ){?>
		<div class="entry-excerpt u-text-format">
			<?php the_excerpt();?>
		</div>
		<?php }?>
	</div>
</div>
</article>
<?php }else if($list_region == 'list_2'){ ?>
<article class="post-list list_2">
<div class="post-wrapper">
	<div class="entry-media fit">
		<div class="placeholder">
			<a href="<?php the_permalink(); ?>">
			<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($post,array(300,181), $crop=1); ?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
			</a>
		</div>
	</div>
	<div class="entry-wrapper">
		<header class="entry-header">
		<div class="entry-meta">
		<?php if(is_module('user')){?>
			<?php if($post->post_status == 'draft' || $post->post_status == 'pending' ){  ?>
				<span class="status"><a style="color:var(--accent-color)" href="<?php echo home_url(user_trailingslashit('/user/contribute'));?>?post_id=<?php echo $post->ID; ?>"><i class="iconfont icon-ykq_tab_tougao" style="font-size:12px;padding-right:2px;"></i>编辑文章</a></span>
			<?php } ?>

			<?php if($post->post_status == 'publish' ){ ?>
				<span class="status">已发布</span>
			<?php }else if($post->post_status == 'pending' ){ ?>
				<span class="status"> 等待审核</span>
			<?php }else if($post->post_status == 'draft' ){ ?>
				<span class="status"> 草稿</span>
			<?php } ?>
		<?php } ?>
			<span class="meta-category<?php if( wpjam_theme_get_setting('list_cat_zsj') ){ echo '-2'; } ?>">
				<?php the_category(' ');?>
			</span>
			<?php if(wpjam_theme_get_setting('list_time')){ ?>
			<span class="meta-time">
				<?php the_time('Y-m-d') ?>
			</span>
			<?php }?>
			<?php if(wpjam_theme_get_setting('list_author')){ ?>
			<span class="meta-author">
				<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>">
					<span><?php echo get_the_author() ?></span>
				</a>
			</span>
			<?php }?>
			<?php if(wpjam_theme_get_setting('list_read')){ ?>
				<span class="meta-view">
				<a class="view" href="<?php the_permalink(); ?>" rel="nofollow"><span class="count"><?php echo wpjam_get_post_views(get_the_ID()); ?> 次浏览</span></a>
				</span>
			<?php } ?>
			<?php if(wpjam_theme_get_setting('list_comment')){ ?>
			<span class="meta-comment">
				<a href="<?php the_permalink(); ?>#comments">
	            	<?php echo $post->comment_count; ?> 条评论
				</a>
			</span>
			<?php }?>

			<?php if(wpjam_theme_get_setting('list_share') && !is_module('user')){ ?>
			<span class="meta-share">
				<a class="share" href="<?php the_permalink(); ?>" rel="nofollow" weixin_share="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" data-url="<?php the_permalink(); ?>" data-title="<?php the_title(); ?>" data-thumbnail="<?php echo wpjam_get_post_thumbnail_url($post,array(350,150), $crop=1);?>" data-image="<?php echo wpjam_get_post_thumbnail_url($post,array(1130,848), $crop=1);?>">
					<span>分享</span>
				</a>
			</span>
			<?php } ?>

		</div>
		<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
		</header>
		<?php if( !wpjam_theme_get_setting('list_no_excerpt') ){?>
		<div class="entry-excerpt u-text-format">
			<?php the_excerpt();?>
		</div>
		<a class="post_more_link" href="<?php the_permalink(); ?>"><span class="text">查看全文</span></a>
		<?php }?>
	</div>
</div>
</article>
<?php }else if($list_region == 'noimg_list'){ ?>
<article class="post-list">
<div class="post-wrapper">
	<div class="entry-wrapper">
		<header class="entry-header">
		<div class="entry-meta">
			<span class="meta-category">
				<?php the_category(' ');?>
			</span>
			<?php if(wpjam_theme_get_setting('list_time')){ ?>
			<span class="meta-time">
				<?php the_time('Y-m-d') ?>
			</span>
			<?php }?>
			<?php if(wpjam_theme_get_setting('list_author')){ ?>
			<span class="meta-author">
				<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>">
					<span><?php echo get_the_author() ?></span>
				</a>
			</span>
			<?php }?>
			<?php if(wpjam_theme_get_setting('list_read')){ ?>
				<span class="meta-view">
				<a class="view" href="<?php the_permalink(); ?>" rel="nofollow"><span class="count"><?php echo wpjam_get_post_views(get_the_ID()); ?> 次浏览</span></a>
				</span>
			<?php } ?>
			<?php if(wpjam_theme_get_setting('list_comment')){ ?>
			<span class="meta-comment">
				<a href="<?php the_permalink(); ?>#comments">
	            	<?php echo $post->comment_count; ?> 条评论
				</a>
			</span>
			<?php }?>

			<?php if(wpjam_theme_get_setting('list_share')){ ?>
			<span class="meta-share">
				<a class="share" href="<?php the_permalink(); ?>" rel="nofollow" weixin_share="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" data-url="<?php the_permalink(); ?>" data-title="<?php the_title(); ?>" data-thumbnail="<?php echo wpjam_get_post_thumbnail_url($post,array(350,150), $crop=1);?>" data-image="<?php echo wpjam_get_post_thumbnail_url($post,array(1130,848), $crop=1);?>">
					<span>分享</span>
				</a>
			</span>
			<?php } ?>

		</div>
		<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
		</header>
		<?php if( !wpjam_theme_get_setting('list_no_excerpt') ){?>
		<div class="entry-excerpt u-text-format">
			<?php the_excerpt();?>
		</div>
		<?php }?>
	</div>
</div>
</article>
<?php }else{?>
<article class="col-md-6 col-lg-4 <?php if( $list_region == 'col_3' || $list_region == 'col_3_sidebar' ) { echo 'col-xl-4'; }elseif($list_region == 'col_4' ){ echo 'col-xl-3'; }else{ echo 'col-xl-3'; } ?> grid-item">
	
	<?php 
		if( wpjam_theme_get_setting('feature_list') ){
			$feature_list = wpjam_theme_get_setting('feature_list');
		}else{
			$feature_list = get_post_meta(get_the_ID(), 'feature_list', true);
		}
	?>

	<div class="post <?php if($feature_list){ echo 'global_feature_list'; }?> <?php if($feature_list){ echo 'cover lazyloaded'; } ?>" <?php if($feature_list){ echo 'style="background-image: url(\''. wpjam_get_post_thumbnail_url($post).'\');"'; } ?>>

		<div class="entry-media<?php if(!$feature_list){?> with-placeholder<?php }?>">
			<?php if(!$feature_list) {?>
			<a href="<?php the_permalink(); ?>" rel="nofollow">
				<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($post,array(500,345), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
			</a>
			<?php } ?>
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
			<div class="entry-category<?php if( wpjam_theme_get_setting('list_cat_zsj') ){ echo '-2'; } ?>"><?php the_category(' ');?></div>
			<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
			</header>
			<?php if( !wpjam_theme_get_setting('list_no_excerpt') ){?>
			<div class="entry-excerpt">
				<?php the_excerpt();?>
			</div>
			<?php }?>
			<?php if(wpjam_theme_get_setting('list_author')){ ?>
			<div class="entry-author">
				<?php echo get_avatar( get_the_author_meta('ID'), '200' );?>
				<div class="author-info">
					<a class="author-name" href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>"><?php echo get_the_author() ?></a>
					<span class="entry-date"><time datetime="<?php the_time('Y-m-d h:m:s') ?>"><?php the_time('Y-m-d') ?></time></span>
				</div>
			</div>
			<?php } ?>
		</div>
		<div class="entry-action">
			<div>
				<?php if(is_module('user')){?>
				<?php if($post->post_status == 'publish' ){ ?>
				<span class="status published">已发布</span>
				<?php }else if($post->post_status == 'pending' ){ ?>
				<span class="status">等待审核</span>
				<?php }else if($post->post_status == 'draft' ){ ?>
				<span class="status">草稿</span>
				<?php } ?>
				<?php if($post->post_status == 'draft' ){  ?>
				<span class="status"><a style="color:var(--accent-color)" href="<?php echo home_url(user_trailingslashit('/user/contribute'));?>?post_id=<?php echo $post->ID; ?>" rel="nofollow"><i class="iconfont icon-zuozhe" style="font-size: 14px;"></i> 编辑文章</a></span>
				<?php } ?>
				<?php } ?>

				<?php if(wpjam_theme_get_setting('list_time')){ ?>
					<a class="time" href="<?php the_permalink(); ?>" rel="nofollow"><i class="iconfont icon-rili"></i><span class="count"><?php the_time('Y-m-d') ?></span></a>
				<?php }?>

				<?php if(wpjam_theme_get_setting('list_read')){ ?>
					<a class="view" href="<?php the_permalink(); ?>" rel="nofollow"><i class="iconfont icon-icon-test"></i><span class="count"><?php echo wpjam_get_post_views(get_the_ID()); ?></span></a>
				<?php } ?>

				<?php if(wpjam_theme_get_setting('list_comment')){ ?>
					<a class="comment" href="<?php the_permalink(); ?>#comments" rel="nofollow"><i class="iconfont icon-pinglun"></i><span class="count"><?php echo $post->comment_count; ?></span></a>
				<?php } ?>
			</div>
			<?php if(wpjam_theme_get_setting('list_share')){ ?>
			<div>
				<a class="share" href="<?php the_permalink(); ?>" rel="nofollow" weixin_share="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" data-url="<?php the_permalink(); ?>" data-title="<?php the_title(); ?>" data-thumbnail="<?php echo wpjam_get_post_thumbnail_url($post,array(350,150), $crop=1);?>" data-image="<?php echo wpjam_get_post_thumbnail_url($post,array(1130,848), $crop=1);?>">
					<i class="iconfont icon-fenxiang"></i><span>分享</span>
				</a>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php if($feature_list) {?><a class="u-permalink" href="<?php the_permalink(); ?>" rel="nofollow"></a><?php } ?>
</article>
<?php }
