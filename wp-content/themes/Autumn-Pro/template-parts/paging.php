<?php

if(is_module('user')){
	$paging	= '1';
}else{
	$paging = wpjam_theme_get_setting('paging_xintheme');
}

$current_page	= get_query_var('paged') ?: 1;

if($paging == '1'){
	wpjam_pagenavi();
}elseif($paging == '3'){
	global $wp_query;
	// 如果没有更多文章 不显示按钮
	if ($wp_query->max_num_pages > $current_page)
		echo '<div class="infinite-scroll-action"><div class="xintheme-loadmore infinite-scroll-button button"><i class="iconfont icon-shuaxin" style="vertical-align: sub;"></i> 加载更多</div></div>';
 }elseif( $paging == '4' ){
	global $wp_query;
	// 如果没有更多文章 不显示按钮
	if ( $wp_query->max_num_pages > $current_page)
		echo '<div class="infinite-scroll-action"><div class="xintheme-loadmore infinite-scroll-button button"><i class="iconfont icon-shuaxin" style="vertical-align: sub;"></i> 加载更多</div></div>';
}elseif( $paging == '2' ){ ?>
<nav class="navigation posts-navigation paged-previous paged-next">
	<div class="nav-links">
		<div class="nav-previous">
			<?php next_posts_link('下一页 »') ?>
		</div>
		<div class="nav-next">
			<?php previous_posts_link('« 上一页') ?>
		</div>
	</div>
</nav>
<?php }