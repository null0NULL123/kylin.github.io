<?php
/*
Template Name: 标签云
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
					<div class="tagslist">
					<ul>
					<?php 
						$tags_list = get_tags('orderby=count&order=DESC&number=30');
						if ($tags_list) { 
							foreach($tags_list as $tag) {
								echo '<li><a class="name" href="'.get_tag_link($tag).'">'. $tag->name .'</a><small>x '. $tag->count .'</small><br>'; 
								$posts = get_posts( "tag_id=". $tag->term_id ."&numberposts=1" );
								if( $posts ){
									foreach( $posts as $post ) {
										setup_postdata( $post );
										echo '<p><a class="tit" href="'.get_permalink().'">'.get_the_title().'</a></p>';
									}
								}
								echo '</li>';
							} 
						} 
					?>
					<ul>
					</div>
				</div>
				</div>
				</article>
				<?php //comments_template( '', true ); ?>
				</main>
			</div>
		</div>
	</div>
</div>
<?php get_footer();?>