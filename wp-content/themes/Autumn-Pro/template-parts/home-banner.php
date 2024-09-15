<?php 
if(get_query_var('paged') > 1) return; 

$slide_type = wpjam_theme_get_setting('slide_type');
?>

<?php if($slide_type == 'img_one'){?>

<?php 

if(wp_is_mobile()){ 
	$img_one_url	= wpjam_theme_get_setting('img_one_url_mb') ?: wpjam_theme_get_setting('img_one_url'); 
}else{
	$img_one_url	= wpjam_theme_get_setting('img_one_url'); 
}
?>

<div class="hero banner-one lazyload visible" data-bg="<?php echo $img_one_url; ?>">

	<div class="container">
		<header class="entry-header dan">
		<h1 class="hero-heading"><?php echo wpjam_theme_get_setting('img_one_title');?></h1>
		<div class="hero-subheading">
			<?php echo wpjam_theme_get_setting('img_one_ms');?>
		</div>
		</header>
	</div>
</div>

<?php }elseif( $slide_type == 'img' ){?>

<div class="hero">
	<div class="hero-slider owl-carousel">
	<?php
	if($slide_type_img = wpjam_theme_get_setting('slide_type_img')){
		$slide_type_img	= array_filter($slide_type_img, function($slide_type_img){
		?>

		<?php if(wp_is_mobile()){?>
			<div class="slider-item lazyload visible" data-bg="<?php if($slide_type_img['img_url_mb']){ echo $slide_type_img['img_url_mb']; }else{ echo $slide_type_img['img_url']; }?>">
		<?php }else{?>
			<div class="slider-item lazyload visible" data-bg="<?php echo $slide_type_img['img_url'];?>">
		<?php }?>
				<div class="hero-content">
					<header class="entry-header">
					<h2 class="hero-heading">
						<?php if($slide_type_img['img_title']){ echo $slide_type_img['img_title']; }?>
					</h2>
					<div class="hero-subheading">
						<?php if($slide_type_img['img_ms']){ echo $slide_type_img['img_ms']; }?>
					</div>
					<?php
					if( $slide_type_img['img_btn1_txt'] ){
						echo '<a class="button transparent" href="'.$slide_type_img['img_btn1_url'].'">'.$slide_type_img['img_btn1_txt'].'</a>';
					}
					if( $slide_type_img['img_btn2_txt'] ){
						echo '<a class="button" href="'.$slide_type_img['img_btn2_url'].'">'.$slide_type_img['img_btn2_txt'].'</a>';
					}?>
					</header>
				</div>
			</div>
		<?php });
	}?>
	</div>
</div>

<?php }else{ ?>

<?php
	$slide_region	= wpjam_theme_get_setting('slide_region');
	$slide_query	= wpjam_query([
		'post__in'				=> wpjam_theme_get_setting('slide_post_id'),
		'ignore_sticky_posts'	=> 1
	]);
	
	if($slide_query->have_posts()){
?>

<?php if ( $slide_region == '5' ) { ?>
<div class="autumn_module_slider_center">
	<div class="container">
		<div class="module slider center owl">
			<?php while($slide_query->have_posts()){ $slide_query->the_post();?>
			<article class="post lazyload" data-bg="<?php echo wpjam_get_post_thumbnail_url($post, 'full', $crop);?>">
			<div class="entry-wrapper">
				<header class="entry-header white">
					<div class="entry-category"><?php  the_category(' ');?></div>
					<h2 class="entry-title"><?php the_title(); ?></h2>
				</header>
				<div class="entry-excerpt u-text-format">
                	<?php the_excerpt(); ?>
				</div>
				<div class="entry-footer">
					<a href="<?php the_permalink(); ?>">
					<time datetime="<?php the_time('Y-m-d h:m:s'); ?>">
      					<i class="iconfont icon-rili"></i> <?php the_time('Y-m-d'); ?>
					</time>
					</a>
				</div>
			</div>
			<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
			</article>
			<?php }?>
		</div>
	</div>
</div>
<?php }else{ ?>


<?php if ( $slide_region == '4' ) { ?>
<div class="featured-wrapper lazyload" data-bg="<?php echo wpjam_theme_get_setting('slide_bg_img');?>">
<?php } ?>

<div class="container mobile">
<?php if( $slide_region == '1' ){?>
	<div class="featured-posts v1 owl-carousel with-padding">
		<?php while($slide_query->have_posts()){ $slide_query->the_post();?>
		<article class="featured-post lazyload visible" data-bg="<?php echo wpjam_get_post_thumbnail_url($post,array(1130,400), $crop=1);?>">
		<div class="entry-wrapper">
			<header class="entry-header">
				<div class="entry-category"><?php  the_category(' ');?></div>
				<h2 class="entry-title"><?php the_title(); ?></h2>
			</header>
			<div class="entry-excerpt"><p><?php the_excerpt();?></p></div>
			<?php if(wpjam_theme_get_setting('list_author')){ ?>
			<div class="entry-author">
				<?php echo get_avatar( get_the_author_meta('ID'), '200' );?>
				<div class="author-info">
					<a class="author-name" href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>"><?php the_author() ?></a>
					<span class="entry-date"><time datetime="<?php the_time('Y-m-d h:m:s') ?>"><?php the_time('Y-m-d') ?></time></span>
				</div>
			</div>
			<?php }?>
		</div>
		<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
		</article>
		<?php } ?>
	</div>
<?php }elseif( $slide_region == '2' ){?>
	<div class="featured-posts v2 owl-carousel with-padding">
		<?php while($slide_query->have_posts()){ $slide_query->the_post();?>
		<article class="featured-post lazyload visible">
		<div class="entry-wrapper">
			<div class="entry-thumbnail">
				<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url($post,array(500,345), $crop=1);?>">
			</div>
			<header class="entry-header">
			<div class="entry-category">
				<div class="entry-category"><?php  the_category(' ');?></div>
				<h2 class="entry-title"><?php the_title(); ?></h2>
			</header>
			<div class="entry-excerpt">
				<p><?php the_excerpt();?></p>
			</div>
		</div>
		<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
		</article>
		<?php }?>
	</div>
<?php }elseif( $slide_region == '3' ){?>
	<div class="featured-posts v3 owl-carousel with-padding">
		<?php while($slide_query->have_posts()){ $slide_query->the_post();?>
		<article class="featured-post lazyload visible" data-bg="<?php echo wpjam_get_post_thumbnail_url($post,array(840,560), $crop=1);?>">
		<div class="entry-wrapper">
			<header class="entry-header">
				<div class="entry-category"><?php  the_category(' ');?></div>
				<h2 class="entry-title"><?php the_title(); ?></h2>
			</header>
		</div>
		<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
		</article>
		<?php } ?>
	</div>
<?php }elseif( $slide_region == '4' ){?>
	<div class="featured-posts v2 owl-carousel with-padding">
		<?php while($slide_query->have_posts()){ $slide_query->the_post();?>
		<article class="featured-post lazyload visible">
		<div class="entry-wrapper">
			<div class="entry-thumbnail">
				<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url($post,array(500,345), $crop=1);?>">
			</div>
			<header class="entry-header">
				<div class="entry-category"><?php  the_category(' ');?></div>
				<h2 class="entry-title"><?php the_title(); ?></h2>
			</header>
			<div class="entry-excerpt">
				<p><?php the_excerpt();?></p>
			</div>
		</div>
		<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
		</article>
		<?php }?>
	</div>
<?php }else{?>
	<div class="featured-posts v1 owl-carousel with-padding">
		<?php while($slide_query->have_posts()){ $slide_query->the_post();?>
		<article class="featured-post lazyload visible" data-bg="<?php echo wpjam_get_post_thumbnail_url($post, $size, $crop);?>">
		<div class="entry-wrapper">
			<header class="entry-header">
				<div class="entry-category"><?php  the_category(' ');?></div>
				<h2 class="entry-title"><?php the_title(); ?></h2>
			</header>
			<div class="entry-excerpt">
				<p><?php the_excerpt(); ?></p>
			</div>
			<?php if(wpjam_theme_get_setting('list_author')){ ?>
			<div class="entry-author">
				<?php echo get_avatar( get_the_author_meta('email'), '200' );?>
				<div class="author-info">
					<a class="author-name" href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>"><?php echo get_the_author() ?></a>
					<span class="entry-date"><time datetime="<?php the_time('Y-m-d h:m:s') ?>"><?php the_time('Y-m-d') ?></time></span>
				</div>
			</div>
			<?php }?>
		</div>
		<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
		</article>
		<?php } ?>
	</div>
<?php } ?>

<?php wp_reset_query(); ?>

</div>

<?php if ( $slide_region == '4' ) { ?></div><?php } ?>

<?php } ?>

<?php } } ?>
<!-- 首页Banner结束 -->


<?php
$index_cat = wpjam_theme_get_setting('index_cat');
$index_cat_lb = wpjam_theme_get_setting('index_cat_lb');
$index_cat1 = $index_cat == '1';
if( ( !$index_cat1 ) ) { ?>
<div class="container mobile">
	<div class="category-boxes<?php if(wpjam_get_setting('wpjam_theme', 'width_1500')){?>-3<?php }else{ ?><?php if($index_cat == '3'){?>-2<?php }?><?php }?> owl-carousel with-padding<?php if(!$index_cat_lb){?> cat-lb-none<?php }?>">
	<?php 
	if($cat_ids= wpjam_theme_get_setting('index_cat_id')){

	foreach ($cat_ids as $cat_id ){
	
	$cat = get_category($cat_id);

	if($cat){ ?>

	<div class="category-box">
		<div class="entry-thumbnails">
		<?php 
		$cat_posts_query = wpjam_query([
			'posts_per_page'		=> 3,
			'ignore_sticky_posts'	=> true,
			'cat'					=> $cat_id
		]);
		if($cat_posts_query->have_posts()){ $i = 0;
			?>
			<?php while($cat_posts_query->have_posts()){ $cat_posts_query->the_post(); $i++; global $post;?>
			<?php if($i == 1){ ?>
				<div class="big thumbnail">
					<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url($post,array(420,280), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php echo $cat->name; ?>">
				</div>
			<?php }elseif($i == 2){ ?>
				<div class="small">
				
				<div class="thumbnail">
					<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url($post,array(150,150), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php echo $cat->name; ?>">
				</div>
			<?php }elseif($i == 3){ ?>
				<div class="thumbnail">
					<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url($post,array(150,150), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php echo $cat->name; ?>">
					<span><?php echo $cat->count; ?> 篇文章</span>
				</div>
			<?php } ?>
			<?php } wp_reset_query(); ?>
			</div>
		<?php } ?>
		</div>
		<div class="entry-content">
			<div class="left">
				<h3 class="entry-title"><?php echo $cat->name; ?></h3>
			</div>
			<div class="right">
				<a class="arrow" href="<?php echo get_category_link($cat_id);?>"><i class="iconfont icon-zhaoyou"></i></a>
			</div>
		</div>
		<a class="u-permalink" href="<?php echo get_category_link($cat_id);?>"></a>
	</div>

	<?php } } } } ?>
	</div>
</div>