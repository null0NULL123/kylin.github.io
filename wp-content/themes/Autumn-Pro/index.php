<?php get_header(); ?>

<?php if(is_home()){ 
	get_template_part('template-parts/home-banner'); 
}elseif(is_category()){
	get_template_part('template-parts/category-banner'); 
} ?>

<div class="site-content container">

	<div class="row">
		<?php if(is_category() || is_tag() || is_tax()){ ?>
			
		<?php if(get_term_meta($cat, 'cat_banner_type', true) != '2'){?>
		<div class="col-lg-12">
			<div class="term-bar">
				<div class="term-info">
					<span>当前<?php if(is_category()){ echo '分类'; }elseif(is_tag()){ echo '标签'; }else{ echo get_taxonomy(get_queried_object()->taxonomy)->labels->name; } ?></span>
					<h1 class="term-title"><?php single_term_title(); ?></h1>
				</div>
			</div>
		</div>
		<?php }?>
			
		<?php }elseif(is_author()){ ?>
		
		<div class="col-lg-12">
			<div class="term-bar">
				<div class="author-image"><?php echo get_avatar(get_queried_object(), '200');?></div>
				<div class="term-info">
					<h1 class="term-title" style="margin: 0 0 8px;"><?php the_author() ?></h1>
					<span><?php echo get_the_author_meta('description') ?: '我还没有学会写个人说明！';?></span>
				</div>
			</div>
		</div>
			
		<?php }elseif(is_search()){ ?>
		
		<div class="col-lg-12">
			<div class="term-bar">
				<div class="term-info">
					<span>搜索结果</span>
					<h1 class="term-title">“<?php echo get_search_query(); ?>” <?php global $wp_query; echo '搜到 ' . $wp_query->found_posts . ' 篇文章';?></h1>
				</div>
			</div>
		</div>
			
		<?php } ?>
	</div>

	<div class="row">
		<?php $list_region	= is_category() ? get_term_meta($cat, 'cat_list_type', true) : wpjam_theme_get_setting('list_region'); ?>

		<?php if(wpjam_theme_get_setting('sidebar_left') && in_array($list_region, ['list', 'list_2', 'noimg_list','col_3_sidebar'])){ get_sidebar(); } ?>

		<div class="<?php echo in_array($list_region, ['list' , 'list_2', 'noimg_list','col_3_sidebar']) ? 'col-lg-9' : 'col-lg-12';?>">

			<div class="content-area">

				<main class="site-main">

				<?php if ( wpjam_theme_get_setting('post_list_ad') && !is_home() && !is_search() ){?>
					<div class="post-list-ad" style="margin-bottom:25px;width:100%;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box;display: inline-block;overflow:hidden;">
						<?php echo wpjam_theme_get_setting('post_list_ad'); ?>
					</div>
				<?php }?>

				<?php if(is_home() && wpjam_theme_get_setting('new_title')){ if(!in_array($list_region, ['list', 'list_2', 'noimg_list'])){?>
						<h3 class="section-title"><span>最新文章</span></h3>
				<?php } }?>

				<?php if(have_posts()){ ?>

				<div class="row<?php if(in_array($list_region, ['list', 'list_2', 'noimg_list'])){?>none<?php }?> posts-wrapper">

				<?php if(is_home() && wpjam_theme_get_setting('new_title')){ if(in_array($list_region, ['list', 'list_2', 'noimg_list'])){ ?>
					<h3 class="section-title"><span>最新文章</span></h3>
				<?php } }?>

				<?php while(have_posts()){ the_post(); get_template_part('template-parts/content-list'); } ?>

				<?php get_template_part('template-parts/paging'); ?>

				</div>

				<?php }else{ ?>	
									
				<div class="_404">

					<?php if(is_search()){ ?>
					
					<h2 class="entry-title">姿势不对？换个词搜一下~</h2>
					<div class="entry-content">
						抱歉，没有找到“<?php echo get_search_query(); ?>”的相关内容
					</div>

					<?php } elseif(is_404()) { ?>
					
					<h1 class="entry-title">抱歉，这个页面不存在！</h1>
					<div class="entry-content">
						它可能已经被删除，或者您访问的URL是不正确的。也许您可以试试搜索？
					</div>
					
					<?php }else{?>
					
					<h1 class="entry-title">暂无文章</h1>
					
					<?php } ?>

					<?php if(is_search() || is_404()){ ?>
					
					<form method="get" class="search-form inline" action="<?php bloginfo('url'); ?>">
						<input class="search-field inline-field" placeholder="输入关键词进行搜索…" autocomplete="off" value="" name="s" required="true" type="search">
						<button type="submit" class="search-submit"><i class="iconfont icon-sousuo"></i></button>
					</form>
					
					<?php } ?>

				</div>

				<?php } ?>
				
				</main>
			</div>
		</div>

		<?php if(!wpjam_theme_get_setting('sidebar_left') && in_array($list_region, ['list', 'list_2', 'noimg_list','col_3_sidebar'])){ get_sidebar(); } ?>

	</div>
</div>

<?php get_footer();?>