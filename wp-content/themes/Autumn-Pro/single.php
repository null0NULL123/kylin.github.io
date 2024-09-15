<?php get_header();
$poster_img = get_post_meta( get_the_ID(), 'poster_img', true );?>

<?php get_template_part('template-parts/single-banner');?>

<div class="site-content container">
	<div class="row">
		<?php if(wpjam_theme_get_setting('sidebar_left') && get_post_meta(get_the_ID(), 'post_layout', true) != '3'){ get_sidebar(); } ?>
		
		<div class="<?php if( get_post_meta(get_the_ID(), 'post_layout', true) == '3' ){ echo 'col-lg-12'; }else{ echo 'col-lg-9'; }?>">
			<div class="content-area">
				<main class="site-main">
				<article class="type-post post">

				<div class="term-bar breadcrumbs">
					<div class="term-info">
						<?php get_breadcrumbs();?>
					</div>
				</div>

				<header class="entry-header">
				<div class="entry-category"><?php the_category(' '); ?></div>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>
				<div class="entry-action">
					<div>
						<a class="view" href="<?php the_permalink(); ?>"><i class="iconfont icon-rili"></i><span class="count"><?php the_time('Y-m-d') ?></span></a>
						<?php if(wpjam_theme_get_setting('single_read')){ ?>
						<a class="view" href="<?php the_permalink(); ?>"><i class="iconfont icon-icon-test"></i><span class="count"><?php echo wpjam_get_post_views(get_the_ID()); ?></span></a>
						<?php }?>
						<?php if(wpjam_theme_get_setting('single_comment')){ ?>
						<a class="comment" href="<?php the_permalink(); ?>#comments"><i class="iconfont icon-pinglun"></i><span class="count"><?php echo get_post(get_the_ID())->comment_count; ?></span></a>
						<?php }?>
						<?php if( current_user_can( 'manage_options' ) ) {?>
							<?php edit_post_link('[编辑文章]'); ?>
						<?php }?>
					</div>
					<?php if(wpjam_theme_get_setting('single_share')){ ?>
					<div>
						<a class="share" href="<?php the_permalink(); ?>" weixin_share="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" data-url="<?php the_permalink(); ?>" data-title="<?php the_title(); ?>" data-thumbnail="<?php echo wpjam_get_post_thumbnail_url($post,array(350,150), $crop=1);?>" data-image="<?php echo wpjam_get_post_thumbnail_url($post,array(1130,848), $crop=1);?>">
						<i class="iconfont icon-fenxiang"></i><span>分享</span>
						</a>
					</div>
					<?php }?>
				</div>
				<div class="entry-wrapper">
					<div class="entry-content u-clearfix<?php if(wpjam_theme_get_setting('text_indent_2')){?> text_indent_2<?php }?>">

					<?php if( wpjam_theme_get_setting('single_top_ad') ){ ?>
					<p style="text-indent:0">
						<?php echo wpjam_theme_get_setting('single_top_ad'); ?>
					</p>
					<?php }?>

					<?php while( have_posts() ): the_post(); ?>
						<?php the_content();?>
					<?php endwhile; ?>

					<?php if( wpjam_theme_get_setting('single_bottom_ad') ){ ?>
					<p style="text-indent:0">
						<?php echo wpjam_theme_get_setting('single_bottom_ad'); ?>
					</p>
					<?php }?>

					</div>
					<div class="tag-share">
						<div class="entry-tags">
							<?php the_tags('标签：', ' · ', ''); ?>
						</div>

						<div class="post-share">
							<div class="post-share-icons">

								<?php if(wpjam_theme_get_setting('poster')){?>
								<div class="post-poster action action-poster">
									<a class="btn-bigger-cover" data-nonce="<?php echo wp_create_nonce('xintheme-create-bigger-image-'.$post->ID );?>" data-id="<?php echo $post->ID; ?>" data-action="create-bigger-image" id="bigger-cover" href="javascript:;"><i class="iconfont icon-xiangce"></i> <span>生成海报</span></a>
									<div class="poster-share">
										<div class="poster-image">

											<?php if ( wp_is_mobile() ) {?>
													<a class="poster-download-img" href="javascript:void(0);"><i class="iconfont icon-xiazai1"></i> 长按图片即可保存</a>
											<?php }else{?>
												<a class="poster-download-img" href="<?php echo $poster_img; ?>" download="<?php the_title();?>-Poster"><i class="iconfont icon-xiazai1"></i> 点击下载海报</a>
											<?php }?>

											<div class="poster-close"><i class="iconfont icon-guanbi1"></i></div>
											<?php if( $poster_img ){?>
												<img class="load-poster-img lazyload" data-srcset="<?php echo $poster_img ?>" src="<?php echo get_template_directory_uri().'/static/images/loading.gif';?>" alt="<?php the_title(); ?>">
											<?php }else{?>
												<img class="load-poster-img" style="border:none;" src="<?php echo get_template_directory_uri().'/static/images/loading.gif'; ?>" alt="加载中">
											<?php } ?>
										</div>
									</div>
								</div>
								<?php }?>

								<?php if( wpjam_theme_get_setting('donate_title') || wpjam_theme_get_setting('donate_weixin_img') || wpjam_theme_get_setting('donate_ali_img') ){?>
								<a href="javascript:;" class="donate_icon"><i class="iconfont icon-qiandai"></i> 打赏作者</a>
								<div class="donate_hide_box hidden"></div>	
								<div class="donate_box hidden">
									<p class="donate_close">
										<svg t="1553064665406" class="close_icon" style="" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="3368" xmlns:xlink="http://www.w3.org/1999/xlink" width="200" height="200"> <defs> <style type="text/css"></style> </defs> <path d="M512 12C235.9 12 12 235.9 12 512s223.9 500 500 500 500-223.9 500-500S788.1 12 512 12z m166.3 609.7c15.6 15.6 15.6 40.9 0 56.6-7.8 7.8-18 11.7-28.3 11.7s-20.5-3.9-28.3-11.7L512 568.6 402.3 678.3c-7.8 7.8-18 11.7-28.3 11.7s-20.5-3.9-28.3-11.7c-15.6-15.6-15.6-40.9 0-56.6L455.4 512 345.7 402.3c-15.6-15.6-15.6-40.9 0-56.6 15.6-15.6 40.9-15.6 56.6 0L512 455.4l109.7-109.7c15.6-15.6 40.9-15.6 56.6 0 15.6 15.6 15.6 40.9 0 56.6L568.6 512l109.7 109.7z" p-id="3369"></path> </svg>
									</p>
									<h2>打赏作者</h2>
									<p><?php echo wpjam_theme_get_setting('donate_title');?></p>
									<?php if(wpjam_theme_get_setting('donate_weixin_img')){?>
									<span class="wedo doimg">
										<img src="<?php echo wpjam_theme_get_setting('donate_weixin_img');?>">
									</span>
									<?php }?>
									<?php if(wpjam_theme_get_setting('donate_ali_img')){?>
									<span class="alido doimg">
										<img src="<?php echo wpjam_theme_get_setting('donate_ali_img');?>">
									</span>
									<?php }?>
								</div>
								<?php }?>

								<?php if(wpjam_theme_get_setting('single_fav')){?>
									<?php echo wpjam_post_fav_button(get_the_ID());?>
								<?php }?>
								<?php if(wpjam_theme_get_setting('single_like')){ ?>
									<?php wpjam_post_like_button_2(get_the_ID());?>
								<?php }?>

								<?php if(wpjam_theme_get_setting('single_share')){ ?>
								<a class="share" href="<?php the_permalink(); ?>" weixin_share="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" data-url="<?php the_permalink(); ?>" data-title="<?php the_title(); ?>" data-thumbnail="<?php echo wpjam_get_post_thumbnail_url($post,array(350,150), $crop=1);?>" data-image="<?php echo wpjam_get_post_thumbnail_url($post,array(1130,848), $crop=1);?>">
									<i class="iconfont icon-fenxiang"></i><span>分享</span>
								</a>
								<?php }?>
                              
							</div>
						</div>
					</div>
				</div>
				</article>

				<?php if( !wpjam_theme_get_setting('post_up_down') ){?>
				<div class="entry-navigation">
				<?php
					$prev_post = get_previous_post();
					if(!empty($prev_post)):?>					
					<div class="nav previous">
						<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($prev_post, '690x400'); ?>">
						<span>上一篇</span>
						<h4 class="entry-title"><?php echo $prev_post->post_title;?></h4>
						<a class="u-permalink" href="<?php echo get_permalink($prev_post->ID);?>"></a>
					</div>
					<?php else: ?>
					<div class="nav none">
						<span>没有了，已经是最后一篇了</span>
					</div>
				<?php endif;?>
				<?php
					$next_post = get_next_post();
					if(!empty($next_post)):?>
					<div class="nav next">
						<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($next_post, '690x400'); ?>">
						<span>下一篇</span>
						<h4 class="entry-title"><?php echo $next_post->post_title;?></h4>
						<a class="u-permalink" href="<?php echo get_permalink($next_post->ID);?>"></a>
					</div>
					<?php else: ?>
					<div class="nav none">
						<span>没有了，已经是最新一篇了</span>
					</div>
				<?php endif;?>
				</div>
				<?php }?>
				<?php if( wpjam_theme_get_setting('xintheme_author') ) : ?>
				<div class="about-author">
					<div class="author-image">
						<?php echo get_avatar( get_the_author_meta('ID'), '200' );?>	
					</div>
					<div class="author-info">
						<h4 class="author-name">
						<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>"><?php echo get_the_author() ?></a>
						</h4>
						<div class="author-bio">
							<?php if(get_the_author_meta('description')){ echo the_author_meta( 'description' );}else{ echo '我还没有学会写个人说明！'; }?>
						</div>
						<a class="author_link" href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>" rel="author"><span class="text">查看作者页面</span></a>
					</div>
				</div>
				<?php endif; ?>

				<?php if (comments_open()) comments_template(); ?>

				<div class="related-post rownone">
					<h3 class="section-title"><span>相关推荐</span></h3>
					<?php $related_query	= wpjam_get_related_posts_query(4);
					if($related_query->have_posts()){ ?>
					<?php while( $related_query->have_posts() ) { $related_query->the_post(); ?>
					<article class="post-list">
					<div class="post-wrapper">
						<div class="entry-media fit">
							<div class="placeholder">
								<a href="<?php the_permalink(); ?>">
								<img class="lazyload" data-srcset="<?php echo wpjam_get_post_thumbnail_url($post,array(300,300), $crop=1); ?>" alt="<?php the_title(); ?>">
								</a>
							</div>
						</div>
						<div class="entry-wrapper">
							<header class="entry-header">
							<div class="entry-meta">
								<span class="meta-category">
									<?php the_category(' ');?>
								</span>
								<span class="meta-time">
									<?php the_time('Y-m-d') ?>
								</span>
							</div>
							<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
							</header>
							<div class="entry-excerpt u-text-format">
								<?php the_excerpt();?>
							</div>
						</div>
					</div>
					</article>
					<?php } } else {?> 
						<p>暂无相关文章!</p>
					<?php } wp_reset_query(); ?>
				</div>

				</main>
			</div>
		</div>

		<?php if(!wpjam_theme_get_setting('sidebar_left') && get_post_meta(get_the_ID(), 'post_layout', true) != '3'){ get_sidebar(); } ?>
	</div>
</div>

<?php get_footer();?>