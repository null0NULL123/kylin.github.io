<?php
/*
Template Name: 带评论的页面
*/
get_header();?>
<div class="site-content container">
	<div class="row">
		<div class="col-lg-9">
			<div class="content-area">
				<main class="site-main">
				<article class="type-post post">
				<header class="entry-header page">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>
				<div class="entry-wrapper">
					<div class="entry-content u-clearfix">
					<?php while( have_posts() ): the_post();?>
						<?php the_content();?>
					<?php endwhile; ?>
					</div>
				</div>
				</article>
				<?php comments_template( '', true ); ?>
				</main>
			</div>
		</div>
		<?php get_sidebar();?>
	</div>
</div>
<?php get_footer();?>