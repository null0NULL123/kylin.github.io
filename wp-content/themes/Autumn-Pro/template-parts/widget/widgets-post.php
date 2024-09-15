<?php

//date_default_timezone_set("Asia/Shanghai");

class xintheme_post_tools extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'xintheme_post_tools',
			'description' => '「Autumn-Pro」主题自带小工具，可以选择调用不同样式和不同内容。',
		);
		parent::__construct( 'xintheme_post_tools', '「Autumn-Pro」文章聚合', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		extract($args);
		$number   	= $instance['num'];
		$who   		= $instance['who'];
		$days  		= $instance['days'];
		$style 		= $instance['style'];
		$cat 		= $instance['cat'];
		$post_id 	= $instance['post_id'];
		$strtotime 	= strtotime('-'.$days.' days');
		$title 		= apply_filters('widget_name', $instance['title']);

		switch ($who) {
			case 'new_posts':
				$args = array(
					'type'                => 'post',
					'showposts'           => $number,
					'cat'              	  => $cat,			          
					'ignore_sticky_posts' => 1
				);
				break;

			case 'random_posts':
				$args = array(
					'type'                => 'post',
					'showposts'           => $number,
					'orderby'             => 'rand',
					'cat'              	  => $cat,					          
					'ignore_sticky_posts' => 1
				);
				break;

			case 'comment_posts':
				$args = array(
					'type'                => 'post',
					'showposts'           => $number,
					'orderby'             => 'comment_count',
					'cat'              	  => $cat,					          
					'ignore_sticky_posts' => 1,
					'date_query'          => array(
						array(
							'before' => date( 'Y-m-d H:00:00', time() ),
						),
						array(
							'after' => date( 'Y-m-d H:00:00', $strtotime ),
						),
					),
				);
				break;
			
			case 'like_posts':
				$args = array(
					'type'                => 'post',
					'showposts'           => $number,
					'orderby'             => 'meta_value_num',
					'cat'              	  => $cat,	
					'meta_key'            => 'likes',				          
					'ignore_sticky_posts' => 1,
					'date_query'          => array(

						array(
							'before' => date( 'Y-m-d H:00:00', time() ),
						),
						array(
							'after' => date( 'Y-m-d H:00:00', $strtotime ),
						),
			
					),
				);
				break;

			case 'views_posts':
				$args = array(
					'type'                => 'post',
					'showposts'           => $number,
					'orderby'             => 'meta_value_num',
					'cat'              	  => $cat,	
					'meta_key'            => 'views',				          
					'ignore_sticky_posts' => 1,
					'date_query'          => array(
						array(
							'before' => date( 'Y-m-d H:00:00', time() ),
						),
						array(
							'after' => date( 'Y-m-d H:00:00', $strtotime ),
						),
					),
				);
				break;

			default:
				$args = null;
				break;
		}

		if( $args ){
			$widget_query = wpjam_query($args);
		}
?>


<?php if( $style == '1' || $style == '2' ){ ?>
	<?php if( $who == 'post_id' ){?>
		<section class="widget widget_xintheme_hotpost">
		<h5 class="widget-title"><?php echo $title; ?></h5>
		<div class="posts<?php if( $style == '2' ){ ?> reverse<?php }?>">
			<?php
			

			// global $post;
			// $posts = get_posts("numberposts=".$number."&post_type=any&include=".$post_id.""); if($posts) : foreach( $posts as $post ) : setup_postdata( $post ); 

			$widget_query	= wpjam_query(['ignore_sticky_posts'=>true, 'posts_per_page'=>$number, 'post_type'=>'any', 'post__in'=>wp_parse_id_list($post_id)]);
			if($widget_query->have_posts()) : while($widget_query->have_posts()) : $widget_query->the_post();
			?>
			<div>
				<div class="entry-thumbnail">
					<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
					<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url(null,array(150,150), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
				</div>
				<header class="entry-header">
				<h6 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h6>
				<div class="entry-meta">
					<?php 
			                switch ($who) {

							case 'comment_posts':
								echo '<span>'.comments_popup_link ('<i class="iconfont icon-pinglun"></i> 0 条评论','<i class="iconfont icon-pinglun"></i> 1 条评论','<i class="iconfont icon-pinglun"></i> % 条评论').'</span>';
								break;

							case 'like_posts':
								$pnum = get_post_meta(get_the_ID(),'likes',true);
								$pnum = $pnum ? $pnum : 0;
								echo sprintf('<i class="%s"></i> %s 个点赞', 'iconfont icon-yixiangkan', $pnum );
								break;

							case 'views_posts':
								echo '<span><i class="iconfont icon-icon-test"></i> '.get_post_meta(get_the_ID(),'views',true).' 次浏览</span>';
								break;
						}
						unset($pnum);
			        ?>
				</div>
				</header>
			</div>
			<?php endwhile; endif; wp_reset_postdata(); ?>
		</div>
		</section>
	<?php }else{?>
		<section class="widget widget_xintheme_hotpost">
		<h5 class="widget-title"><?php echo $title; ?></h5>
		<div class="posts<?php if( $style == '2' ){ ?> reverse<?php }?>">
			<?php
			if( $args ){ 
				if ($widget_query->have_posts()) :
				while ($widget_query->have_posts()) : $widget_query->the_post();
			?>
			<div>
				<div class="entry-thumbnail">
					<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
					<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url(null,array(150,150), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
				</div>
				<header class="entry-header">
				<h6 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h6>
				<div class="entry-meta">
					<?php 
			                switch ($who) {

							case 'comment_posts':
								echo '<span>'.comments_popup_link ('<i class="iconfont icon-pinglun"></i> 0 条评论','<i class="iconfont icon-pinglun"></i> 1 条评论','<i class="iconfont icon-pinglun"></i> % 条评论').'</span>';
								break;

							case 'like_posts':
								$pnum = get_post_meta(get_the_ID(),'likes',true);
								$pnum = $pnum ? $pnum : 0;
								echo sprintf('<i class="%s"></i> %s 个点赞', 'iconfont icon-yixiangkan', $pnum );
								break;

							case 'views_posts':
								echo '<span><i class="iconfont icon-icon-test"></i> '.get_post_meta(get_the_ID(),'views',true).' 次浏览</span>';
								break;
						}
						unset($pnum);
			        ?>
				</div>
				</header>
			</div>
			<?php
				endwhile;
				wp_reset_query();
				else :
					echo '<p>抱歉，没有找到文章！</p>';
				endif;
				}else{
					echo '<p>抱歉，没有找到文章！</p>';
				}
			?> 
		</div>
		</section>
	<?php } ?>
<?php } ?>

<?php if( $style == '3' ){ ?>
	<?php if( $who == 'post_id' ){?>
		<section class="no-padding widget widget_xintheme_picks_widget">
		<div class="icon">
		</div>
		<div class="picked-posts owl-carousel">
			<?php
			// wpjam_print_R($post_id);
			// global $post;
			// $posts = get_posts("numberposts=".$number."&post_type=any&include=".$post_id.""); if($posts) : foreach( $posts as $post ) : setup_postdata( $post );
			$widget_query	= wpjam_query(['ignore_sticky_posts'=>true, 'posts_per_page'=>$number, 'post_type'=>'any', 'post__in'=>wp_parse_id_list($post_id)]);

			if($widget_query->have_posts()) : while($widget_query->have_posts()) : $widget_query->the_post();
			?>
			<article class="picked-post">
			<div class="entry-thumbnail">
				<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url(null,array(520,300), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
			</div>
			<header class="entry-header">
			<h6 class="entry-title"><?php the_title(); ?></h6>
			</header>
			<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
			</article>
			<?php endwhile; endif; ?>
		</div>
		</section>
	<?php }else{?>
		<section class="no-padding widget widget_xintheme_picks_widget">
		<div class="icon">
		</div>
		<div class="picked-posts owl-carousel">
			<?php
			if( $args ){ 
				if ($widget_query->have_posts()) :
				while ($widget_query->have_posts()) : $widget_query->the_post();
			?>
			<article class="picked-post">
			<div class="entry-thumbnail">
				<img class="lazyload" data-src="<?php echo wpjam_get_post_thumbnail_url(null,array(520,300), $crop=1);?>" <?php echo xintheme_lazysizes(); ?> alt="<?php the_title(); ?>">
			</div>
			<header class="entry-header">
			<h6 class="entry-title"><?php the_title(); ?></h6>
			</header>
			<a class="u-permalink" href="<?php the_permalink(); ?>"></a>
			</article>
			<?php
				endwhile;
				wp_reset_query();
				else :
					echo '<p>抱歉，没有找到文章！</p>';
				endif;
				}else{
					echo '<p>抱歉，没有找到文章！</p>';
				}
			?> 
		</div>
		</section>
	<?php }?>
<?php } ?>


<?php
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 
				'title' => '文章聚合',
				'num'   => 5,
				'days'  => 30,
				'who'   => 'new_posts',
				'style' => '1',
			) 
		);
?>
		<p>
			<label> 标题：
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
			</label>
		</p>
		<p>
			<label> 显示数量：
				<input class="widefat" id="<?php echo $this->get_field_id('num'); ?>" name="<?php echo $this->get_field_name('num'); ?>" type="number" step="1" min="4" value="<?php echo $instance['num']; ?>" />
			</label>
		</p>
		<p>
			<label> 显示样式：
				<select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
					<option <?php mi_selected( $instance['style'], '1' ); ?> value="1">样式-1</option>
					<option <?php mi_selected( $instance['style'], '2' ); ?> value="2">样式-2</option>
					<option <?php mi_selected( $instance['style'], '3' ); ?> value="3">样式-3</option>
				</select>
			</label>
		</p>
		<p>
			<label> 显示什么：
				<select class="widefat xintheme_select_handle" id="<?php echo $this->get_field_id('who'); ?>" name="<?php echo $this->get_field_name('who'); ?>">
					<option <?php mi_selected( $instance['who'], 'new_posts' ); ?> value="new_posts">最新文章</option>
					<option <?php mi_selected( $instance['who'], 'random_posts' ); ?> value="random_posts">随机文章</option>
					<option <?php mi_selected( $instance['who'], 'comment_posts' ); ?> value="comment_posts">评论最多</option>
					<option <?php mi_selected( $instance['who'], 'like_posts' ); ?> value="like_posts">点赞最多</option>
					<option <?php mi_selected( $instance['who'], 'views_posts' ); ?> value="views_posts">浏览最多</option>
					<option <?php mi_selected( $instance['who'], 'post_id' ); ?> value="post_id">指定文章</option>
				</select>
			</label>
		</p>
		<?php if( $instance['who'] == 'new_posts' || $instance['who'] == 'random_posts' || $instance['who'] == 'post_id' ){ ?>
		<p id="<?php echo $this->get_field_id('who'); ?>-box" style="display: none">
		<?php }else{ ?>
		<p id="<?php echo $this->get_field_id('who'); ?>-box">
		<?php } ?>
			<label>
				显示
				<input class="tiny-text" id="<?php echo $this->get_field_id('days'); ?>" name="<?php echo $this->get_field_name('days'); ?>" type="number" step="1" min="1" value="<?php echo $instance['days']; ?>" style="width: 70px;" />
				<span>
				<?php 
					switch ($instance['who']) {
						case 'comment_posts':
							echo '天内评论最多的文章';
							break;
						
						case 'like_posts':
							echo '天内点赞最多的文章';
							break;
						case 'views_posts':
							echo '天内浏览最多的文章';
							break;
						default:
							break;
					}
				?>
				</span>		
			</label>
		</p>

		<?php if( $instance['who'] == 'post_id' ){ ?>
		<p id="<?php echo $this->get_field_id('who'); ?>-box-2">
		<?php }else{ ?>
		<p id="<?php echo $this->get_field_id('who'); ?>-box-2" style="display: none">
		<?php }?>
			<label>
				指定文章ID：
				<input style="width:100%;" id="<?php echo $this->get_field_id('post_id'); ?>" name="<?php echo $this->get_field_name('post_id'); ?>" type="text" value="<?php echo $instance['post_id']; ?>" size="24" />
				格式：1,2 &nbsp;表示只显示文章ID为1,2分类的文章，注意：多个ID之间用英文逗号隔开！
			</label>
		</p>
		
		<?php if( $instance['who'] == 'post_id' ){ ?>
		<p id="<?php echo $this->get_field_id('who'); ?>-box-3" style="display: none">
		<?php }else{ ?>
		<p id="<?php echo $this->get_field_id('who'); ?>-box-3">
		<?php }?>
			<label>
				分类限制：
				<input style="width:100%;" id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>" type="text" value="<?php echo $instance['cat']; ?>" size="24" />
				格式：1,2 &nbsp;表示只显示分类ID为1,2分类的文章，格式：-1,-2 &nbsp;表示排除分类ID为1,2的文章，也可以直接写1或者-1；注意：多个ID之间用英文逗号隔开！
			</label>
		</p>

<?php
	}

}
add_action('widgets_init', function(){register_widget('xintheme_post_tools' );});

function mi_selected( $t, $i ){
	if( $t == $i ){
		echo 'selected';
	}
}

/**
 * 加载一段JS
 */
function xintheme_select_handle() {  
?>
	<script>

		jQuery( document ).ready( function() {
			if( jQuery('.xintheme_select_handle').length > 0 ){
				jQuery('.xintheme_select_handle').each(function(index, el) {
					xintheme_select_handle( jQuery(this).attr('id') );
				});
			}

		});

		jQuery(document).on('change', '.xintheme_select_handle', function(event) {
			event.preventDefault();
			xintheme_select_handle( jQuery(this).attr('id') );
		});

		function xintheme_select_handle( id ){
			var selected = jQuery('#'+id+' option:selected');
			if( selected.val() == 'comment_posts' || selected.val() == 'like_posts' || selected.val() == 'views_posts' ){
				jQuery('#'+id+'-box label span').text( ' 天内' + selected.text() + '的文章' );
		 		jQuery('#'+id+'-box').show();
		 		jQuery('#'+id+'-box-2').hide();
		 		jQuery('#'+id+'-box-3').show();

		 	}else if(selected.val() == 'post_id'){
		 		jQuery('#'+id+'-box').hide();
		 		jQuery('#'+id+'-box-2').show();
		 		jQuery('#'+id+'-box-3').hide();
		 	}else{
		 		jQuery('#'+id+'-box').hide();
		 		jQuery('#'+id+'-box-2').hide();
		 		jQuery('#'+id+'-box-3').show();
		 	}
		}
	</script>
<?php
}  
add_action( 'admin_footer', 'xintheme_select_handle' );

