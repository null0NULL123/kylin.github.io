<?php
/*
Template Name: 全屏页面
*/
get_header();?>
<div class="site-content container">
	<div class="row">
		<div class="col-lg-12">
			<div class="content-area">
				<main class="site-main">
				<article class="type-post post">
				<header class="entry-header page">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>
				<div class="entry-wrapper">
					<div class="entry-content u-clearfix">
					<?php while( have_posts() ): the_post(); $p_id = get_the_ID(); ?>
						<?php the_content();?>
					<?php endwhile; ?>
					</div>
				</div>
				</article>
				<?php if (comments_open()) comments_template(); ?>
				</main>
			</div>
		</div>
	</div>
</div>
<?php get_footer();?>