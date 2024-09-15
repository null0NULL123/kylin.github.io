<div class="user-post col-lg-9">
	<div class="rownone posts-wrapper">
    <h3 class="section-title"><span>我的收藏</span></h3>
        <?php
			$counts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE user_id=" . get_current_user_id());
			$perpage = 20;
			$pages = ceil($counts / $perpage);
			$paged = (get_query_var('paged')) ? $wpdb->escape(get_query_var('paged')) : 1;
				
			$args = array('type'=>'fav', 'user_id' => get_current_user_id(), 'number' => 20, 'offset' => ($paged - 1) * 20);
			$lists = get_comments($args);
				
		?>
        <?php
            if($lists) {
		?>
		<?php foreach($lists as $value){ ?>
		<article class="post-list">
		<div class="post-wrapper">
			<div class="entry-media fit">
				<div class="placeholder">
					<a href="<?php echo get_permalink($value->comment_post_ID);?>">
					<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($value->comment_post_ID,array(300,300), $crop=1); ?>" alt="<?php echo get_post($value->comment_post_ID)->post_title;?>">
					</a>
				</div>
			</div>
			<div class="entry-wrapper">
				<header class="entry-header">
				<div class="entry-meta">
					<!--span class="meta-category<?php // if( wpjam_theme_get_setting('list_cat_zsj') ){ echo '-2'; } ?>">
						<?php // the_category(' ');?>
					</span-->
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
						<a class="view" href="<?php the_permalink(); ?>" rel="nofollow"><span class="count"><?php echo wpjam_get_post_views($value->comment_post_ID); ?> 次浏览</span></a>
						</span>
					<?php } ?>
					<?php if(wpjam_theme_get_setting('list_comment')){ ?>
					<span class="meta-comment">
						<a href="<?php the_permalink(); ?>#comments">
			            	<?php echo get_post($value->comment_post_ID)->comment_count;?> 条评论
						</a>
					</span>
					<?php }?>

					<?php if(wpjam_theme_get_setting('list_share')){ ?>
					<span class="meta-share">
						<a class="share" href="<?php the_permalink(); ?>" rel="nofollow" weixin_share="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" data-url="<?php echo get_permalink($value->comment_post_ID);?>" data-title="<?php echo get_post($value->comment_post_ID)->post_title;?>" data-thumbnail="<?php echo wpjam_get_post_thumbnail_url($value->comment_post_ID,array(350,150), $crop=1);?>" data-image="<?php echo wpjam_get_post_thumbnail_url($value->comment_post_ID,array(1130,848), $crop=1);?>">
							<span>分享</span>
						</a>
					</span>
					<?php } ?>

				</div>
				<h2 class="entry-title"><a href="<?php echo get_permalink($value->comment_post_ID);?>" rel="bookmark"><?php echo get_post($value->comment_post_ID)->post_title;?></a></h2>
				</header>
				<?php if( !wpjam_theme_get_setting('list_no_excerpt') ){?>
				<div class="entry-excerpt u-text-format">
					<p><?php echo mb_strimwidth(strip_tags(apply_filters('the_content', $value->post_content)), 0, 170, "…");?></p>
				</div>
				<?php }?>
			</div>
		</div>
		</article>
 		<?php }?>
 		<?php }?>
    </div>
   
</div>
