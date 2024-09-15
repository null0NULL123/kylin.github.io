<?php
/*
Template Name: 点赞排行榜
*/
get_header();?>
<div class="site-content container">
	<div class="row">
		<div class="col-lg-12">
			<div class="term-bar">
				<div class="term-info">
				<span>当前页面</span><h1 class="term-title">点赞排行榜</h1></div>
			</div>
			<div class="content-area">
				<main class="site-main">
				<div class="row">
					<?php
						$args = array(
							'ignore_sticky_posts' => 1,
							'meta_key' => 'likes',
							'orderby' => 'meta_value_num',
							'showposts' => 20
						);	
						query_posts($args);
						if ( have_posts() ) : ?>
					<?php 
						while ( have_posts() ) : the_post();
							get_template_part( 'template-parts/content-list' );
						endwhile; endif;?>
					<?php //get_template_part( 'template-parts/paging' );?>
					</div>
				</main>
			</div>
		</div>
	</div>
</div>
<?php get_footer();?>