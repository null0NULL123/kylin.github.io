<?php get_header(); ?>

<div class="page-banner" style="background: url(<?php echo wpjam_topic_get_setting('theme_banner');?>);background-position: center center;-webkit-background-size: cover;background-size: cover;">
	<div class="dark-overlay"></div>
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="page-content" style="text-align: left;">
					<h2><?php echo wpjam_topic_get_setting('banner_title');?></h2>
					<p class="text-muted lead">
						<?php echo wpjam_topic_get_setting('banner_desc');?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="site-content container">
	<div class="row">
		<?php
			if( wpjam_theme_get_setting('sidebar_left') ){
				get_sidebar();
			}
		?>
		<div class="col-lg-9">
			<div class="content-area">
				<main class="site-main">
				<?php $list_region_list = wpjam_theme_get_setting('list_region') == 'list';?>
					<?php if(have_posts()){ ?>
					<div class="rownone posts-wrapper">
						<div class="term-bar autumn-topic">
							<div class="term-info">
							<?php
							if(wpjam_topic_get_setting('add_4_theme')) {
							if( !wpjam_theme_get_setting('subscriber_ft') || !current_user_can('subscriber') ){?>
							<a class='add-topic' href="<?php echo home_url(user_trailingslashit('/user/topic')); ?>"><i class="iconfont icon-fatieliang" style=""></i> 发布帖子</a>
							<?php } }?>
							<a class='group-all' href="<?php echo home_url(); ?>/topic">全部</a>
							<?php
							$post_categories = get_the_terms( $post->ID, 'group' );

							$args=array(
								'taxonomy'		=> 'group', 
								'hide_empty'	=> false, 
								'meta_key'		=> 'order', 
								'orderby'		=> 'meta_value_num',
								'order'			=> 'DESC'	
							);
							$categories=get_categories($args);
							foreach($categories as $category){
							?>
							<a class='group-<?php echo $category->term_id;?>' href="<?php echo get_category_link( $category->term_id )?>"> <?php echo $category->name ;?></a>
							<?php }?>
							<?php if(is_tax()){?>
								<style>.group-<?php echo $post_categories[0]->term_id;?>{border-bottom: 2px solid var(--accent-color);color: #1a1a1a;pointer-events: none;padding-bottom: 3px}</style>
							<?php }else{?>
								<style>.group-all{border-bottom: 3px solid var(--accent-color);color: #1a1a1a;pointer-events: none;padding-bottom: 2px}</style>
							<?php }?>
							</div>
						</div>
					<?php while(have_posts()){ the_post(); $post_categories = get_the_terms( $post->ID, 'group' );?>
						<article class="post-list">
						<div class="post-wrapper">
							<div class="entry-media fit">
								<div class="placeholder" style="padding-bottom: 100%;">
									<a href="<?php the_permalink(); ?>">
										<?php echo get_avatar( get_the_author_meta('ID'), '300' );?>
									</a>
								</div>
							</div>
							<div class="entry-wrapper">
								<header class="entry-header">
								<div class="entry-meta">
									<?php if(!is_tax()){?>
									<span class="meta-category">
										<a href="<?php echo get_category_link($post_categories[0]->term_id);?>" rel="category tag"><?php echo $post_categories[0]->name;?></a>
									</span>
									<?php }?>
									<span class="meta-time">
										<?php the_time('Y-m-d') ?>
									</span>
									<span class="meta-author">
										<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>">
											<span><?php echo get_the_author() ?></span>
										</a>
									</span>
									<span class="meta-views">
										<a href="<?php the_permalink(); ?>">
							            	<?php echo wpjam_get_post_views(get_the_ID()); ?> 次浏览
										</a>
									</span>
									<span class="meta-comment">
										<a href="<?php the_permalink(); ?>#comments">
							            	<?php echo $post->comment_count; ?> 条评论
										</a>
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
					<?php } ?>
					<?php wpjam_pagenavi(); ?>
					</div>
				<?php }else{?>	
					<div class="_404">
						<h1 class="entry-title">暂无文章</h1>
					</div>
				<?php } ?>
				
				</main>
			</div>
		</div>
		
		<?php
			if( !wpjam_theme_get_setting('sidebar_left') ){
				get_sidebar();
			}
		?>
		
	</div>
</div>
<?php get_footer();?>