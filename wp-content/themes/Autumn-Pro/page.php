<?php get_header();?>
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
				<article class="type-post post">
				<div class="term-bar breadcrumbs">
					<div class="term-info">
						<?php get_breadcrumbs();?>
					</div>
				</div>
				<header class="entry-header page">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>
				<div class="entry-wrapper">
					<div class="entry-content u-clearfix<?php if(wpjam_theme_get_setting('text_indent_2')){?> text_indent_2<?php }?>">
					<?php while( have_posts() ): the_post(); ?>
						<?php the_content();?>
					<?php endwhile; ?>
					</div>
				</div>
				</article>
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