<div class="col-lg-3 <?php $list_region = wpjam_get_setting('wpjam_theme', 'list_region'); if( $list_region == 'list' || $list_region == 'noimg_list' ){ echo 'index-sidebar'; }?><?php if( wpjam_get_setting('wpjam_theme', 'mobile_no_sidebar') ){?> mobile-none-sidebar<?php }?>">
<aside class="widget-area">

	<!--作者模块开始-->
	<?php
	wp_reset_query();
		if ( is_single() && wpjam_theme_get_setting('post_sidebar_author') ) {
		$author_id = get_the_author_meta('ID');
	?>
	<div id="zuozhebg" style="background: url(<?php echo get_avatar_url( get_the_author_meta('ID'), '200' );?>) center center no-repeat;background-size:cover"></div>
	<div class="relive_widget_v3">
		<div class="box-author-info">
			<div class="author-face">
				<a href="<?php echo get_author_posts_url($author_id);?>" target="_blank" rel="nofollow" title="访问<?php echo the_author_meta( 'nickname' ); ?>的主页">
					<?php echo get_avatar( get_the_author_meta('ID'), '200' );?>
				</a>
			</div>
			<div class="author-name">
				<?php echo the_author_meta( 'nickname' ); ?>
			</div>
			<div class="author-one">
				<?php if(get_the_author_meta('description')){ echo the_author_meta( 'description' );}else{echo'我还没有学会写个人说明！'; }?>
			</div>
		</div>
		<dl class="article-newest">
			<dt><span class="tit">最近文章</span></dt>
			<?php
				global $post;
				$args = array(
					'author' => $post->post_author, 
					'post__not_in' => array($post->ID),
					'showposts' => 6, // 显示相关文章数量
					'ignore_sticky_posts' => 1
				);
				$wpjam_query = wpjam_query($args);
				$i = 0;
				if ($wpjam_query->have_posts()) {
				while ($wpjam_query->have_posts()) { $i++;
				$wpjam_query->the_post(); update_post_caches($posts); ?>
				<li>
					<span class="order od-<?php echo $i;?>"><?php echo $i;?></span>
					<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><i class="iconfont icon-sanjiao"></i><?php the_title(); ?></a>
				</li>
				<?php } }
				else {
				echo '<li>* 没有更多文章了</li>';
				}
				wp_reset_query();?>
		</dl>
	</div>
	<?php }?>
	<!--作者模块结束-->

	<?php 
	if (function_exists('dynamic_sidebar') && dynamic_sidebar('widget_right')) : endif; 

	if (is_single()){
		if (function_exists('dynamic_sidebar') && dynamic_sidebar('widget_post')) : endif; 
	}

	else if (is_page()){
		if (function_exists('dynamic_sidebar') && dynamic_sidebar('widget_page')) : endif; 
	}

	else if (is_home()){
		if (function_exists('dynamic_sidebar') && dynamic_sidebar('widget_home')) : endif; 
	}
	else {
		if (function_exists('dynamic_sidebar') && dynamic_sidebar('widget_other')) : endif; 
	}
	?>
</aside>
</div>