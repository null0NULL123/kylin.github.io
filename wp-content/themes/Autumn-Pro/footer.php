<?php $footer_type = wpjam_theme_get_setting('footer_type');?>

<footer class="site-footer" <?php if( $footer_type == '2' ){?>style="background-color: #27282d;"<?php }?>>
<?php
if( $footer_type == '2' ){?>
<div class="widget-footer container">
	<div class="row">
		<div class="col-md-5">
			<div class="logo text">
				<img src="<?php echo wpjam_theme_get_setting('foot_logo'); ?>" alt="<?php bloginfo('name'); ?>">
			</div>
			<div class="site-info">
				<?php echo wpjam_theme_get_setting('foot_describe'); ?>
			</div>
			<?php if( wpjam_theme_get_setting('foot_social') ) { ?>
			<div class="social-links">
				<?php if( $autumn_weibo = wpjam_theme_get_setting('autumn_weibo') ) : ?>
				<a href="<?php echo $autumn_weibo; ?>" title="微博" target="_blank" rel="nofollow">
					<i class="iconfont icon-weibo"></i>
				</a>
				<?php endif; ?>
				<?php if( $autumn_qq = wpjam_theme_get_setting('autumn_qq') ) : ?>
					<?php if( wp_is_mobile() ){?>
						<a href="mqqwpa://im/chat?chat_type=wpa&uin=<?php echo $autumn_qq; ?>&version=1&src_type=web&web_src=oicqzone.com" title="QQ" target="_blank" rel="nofollow">
					<?php }else{?>
						<a href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $autumn_qq; ?>&site=qq&menu=yes" title="QQ" target="_blank" rel="nofollow">
					<?php }?>
					<i class="iconfont icon-QQ"></i>
				</a>
				<?php endif; ?>
				<?php if( wpjam_theme_get_setting('autumn_weixin') ) : ?>
				<a id="tooltip-f-weixin" href="javascript:void(0);" title="微信">
					<i class="iconfont icon-weixin"></i>
				</a>
				<?php endif; ?>
				<?php if( $autumn_mail = wpjam_theme_get_setting('autumn_mail') ) : ?>
				<a href="http://mail.qq.com/cgi-bin/qm_share?t=qm_mailme&email=<?php echo $autumn_mail; ?>" title="QQ邮箱" target="_blank" rel="nofollow">
					<i class="iconfont icon-youxiang"></i>
				</a>
				<?php endif; ?>
			</div>
			<?php }?>

		</div>
		<div class="col-md-7">
			<div class="row">
				<div class="col-md-4">
					<section class="widget">
					<h5 class="widget-title">
						<?php 
							$menu=get_nav_menu_locations();
							if(isset($menu["d1"])):
								$menu_object=wp_get_nav_menu_object($menu["d1"]); 
								echo $menu_object->name ;
							else:
								echo '请到后台设置菜单';
							endif;
						?>
					</h5>
					<ul>
						<?php if(function_exists('wp_nav_menu')) wp_nav_menu(array('container' => false, 'items_wrap' => '%3$s', 'theme_location' => 'd1','depth'=> 1)); ?>
					</ul>
					</section>
				</div>
				<div class="col-md-4">
					<section class="widget">
					<h5 class="widget-title">
						<?php 
							$menu=get_nav_menu_locations();
							if(isset($menu["d2"])):
								$menu_object=wp_get_nav_menu_object($menu["d2"]); 
								echo $menu_object->name ;
							else:
								echo '请到后台设置菜单';
							endif;
						?>
					</h5>
					<ul>
						<?php if(function_exists('wp_nav_menu')) wp_nav_menu(array('container' => false, 'items_wrap' => '%3$s', 'theme_location' => 'd2','depth'=> 1)); ?>
					</ul>
					</section>
				</div>
				<div class="col-md-4">
					<section class="widget">
					<h5 class="widget-title">
						<?php 
							$menu=get_nav_menu_locations();
							if(isset($menu["d3"])):
								$menu_object=wp_get_nav_menu_object($menu["d3"]); 
								echo $menu_object->name ;
							else:
								echo '请到后台设置菜单';
							endif;
						?>
					</h5>
					<div class="menu-extra-container">
						<ul id="menu-extra" class="menu">
							<?php if(function_exists('wp_nav_menu')) wp_nav_menu(array('container' => false, 'items_wrap' => '%3$s', 'theme_location' => 'd3','depth'=> 1)); ?>
						</ul>
					</div>
					</section>
				</div>
			</div>
		</div>
	</div>
</div>
<?php }?>

<?php if( wpjam_theme_get_setting('foot_link') ) : ?>
<?php if ( is_home() ) { ?>
<div class="container social-bar"<?php if( $footer_type == '2' ){?> style="border-bottom:none;text-align:left;border-top:1px solid #333;"<?php }?>>
	<li style="color: #777;font-size: 12px;">友情链接：</li><?php wp_list_bookmarks('title_li=&categorize=0&show_images=0'); ?>
</div>
<?php } ?>
<?php endif; ?>

<?php if( $footer_type == '2' ){?>
<div class="site-info" style="max-width: 1160px;margin: 0 auto;padding: 30px 0 30px 0;border-top: 1px solid #333;">
	<p style="margin:0">
		<?php $foot_copyright = wpjam_theme_get_setting('foot_copyright'); ?>
		<?php if( $foot_copyright ){?><?php echo $foot_copyright;?><?php }else{?>Copyright <?php echo date('Y'); ?>. All Rights Reserved<?php }?><?php $footer_icp = wpjam_theme_get_setting('footer_icp'); if( $footer_icp ){?>.&nbsp;<a rel="nofollow" target="_blank" href="http://beian.miit.gov.cn/"><?php echo $footer_icp ?></a><?php }?><?php if( wpjam_theme_get_setting('foot_timer') ) {?>.&nbsp;页面加载时间：<?php timer_stop(1);?> 秒<?php } ?><?php if( !wpjam_theme_get_setting('xintheme_link') ) { ?>.&nbsp;Powered By&nbsp;<a href="http://www.xintheme.com" target="_blank">XinTheme</a>&nbsp;+&nbsp;<a href="https://blog.wpjam.com/" target="_blank">WordPress 果酱</a><?php }?>
	</p>
</div>
<?php } ?>

<?php if( $footer_type == '1' ){?>
<div class="site-info">
	<p>
		<?php $foot_copyright = wpjam_theme_get_setting('foot_copyright'); ?>
		<?php if( $foot_copyright ){?><?php echo $foot_copyright;?><?php }else{?>Copyright <?php echo date('Y'); ?>. All Rights Reserved<?php }?><?php $footer_icp = wpjam_theme_get_setting('footer_icp'); if( $footer_icp ){?>.&nbsp;<a rel="nofollow" target="_blank" href="http://beian.miit.gov.cn/"><?php echo $footer_icp ?></a><?php }?><?php if( wpjam_theme_get_setting('foot_timer') ) {?>.&nbsp;页面加载时间：<?php timer_stop(1);?> 秒<?php } ?><?php if( !wpjam_theme_get_setting('xintheme_link') ) { ?>.&nbsp;Powered By&nbsp;<a href="http://www.xintheme.com" target="_blank">XinTheme</a>&nbsp;+&nbsp;<a href="https://blog.wpjam.com/" target="_blank">WordPress 果酱</a><?php }?>
	</p>
</div>
<?php }?>

</footer>
</div>
<?php if( $autumn_weixin = wpjam_theme_get_setting('autumn_weixin') ) { ?>
<div class="f-weixin-dropdown">
	<div class="tooltip-weixin-inner">
		<h3>微信扫一扫</h3>
		<div class="qcode"> 
			<img src="<?php echo $autumn_weixin; ?>" alt="微信扫一扫">
		</div>
	</div>
	<div class="close-weixin">
		<span class="close-top"></span>
			<span class="close-bottom"></span>
	</div>
</div>
<?php } ?>
<!--以下是分享-->
<div class="dimmer"></div>
<div class="modal">
  <div class="modal-thumbnail">
	<img src="">
  </div>
  <h6 class="modal-title"></h6>
  <div class="modal-share">
  	<span>分享到：</span>
	<a class="weibo_share" href="#" target="_blank"><i class="iconfont icon-weibo"></i></a>
	<a class="qq_share" href="#" target="_blank"><i class="iconfont icon-QQ"></i></a>
	<a href="javascript:;" data-module="miPopup" data-selector="#post_qrcode" class="weixin"><i class="iconfont icon-weixin"></i></a>
  </div>
  <form class="modal-form inline">
	<input class="modal-permalink inline-field" value="" type="text">
	<button data-clipboard-text="" type="submit"><i class="iconfont icon-fuzhi"></i><span>复制链接</span></button>
  </form>
</div>
<div class="dialog-xintheme" id="post_qrcode">
	<div class="dialog-content dialog-wechat-content">
		<p>
			微信扫一扫,分享到朋友圈
		</p>
		<img class="weixin_share" src="<?php bloginfo('template_directory'); ?>/public/qrcode/?data=<?php the_permalink(); ?>" alt="<?php the_title_attribute(); ?>">
		<div class="btn-close">
			<i class="iconfont icon-guanbi1"></i>
		</div>
	</div>
</div>
<!--禁止选中-->
<script type="text/javascript">
<?php if( wpjam_theme_get_setting('xintheme_copy') ) :?>
document.getElementById("body").onselectstart = function(){return false;};
<?php endif;?>
</script>
<?php if( wpjam_theme_get_setting('cool_qq') ) :?>
<div class="container mobile-hide" id="J_container">
	<a class="livechat-girl js-livechat-girl animated" id="lc-girl-block-en_2" target="_blank" rel="nofollow" href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo wpjam_theme_get_setting('autumn_qq'); ?>&site=qq&menu=yes">
		<img class="girl" src="<?php bloginfo('template_directory'); ?>/static/images/qq.png" title="点击这里给我发消息" border="0">
	<div class="js-livechat-hint livechat-hint rd-notice rd-notice-tooltip single-line hide_hint">
		<div class="popover-content rd-notice-content">
			嘿！有什么能帮到您的吗？
		</div>
	</div>
	<div class="animated-circles js-animated-circles animated">
		<div class="circle c-1">
		</div>
		<div class="circle c-2">
		</div>
		<div class="circle c-3">
		</div>
	</div>
	</a>
</div>
<?php endif;?>
<div class="gotop">		 
	<a id="goTopBtn" href="javascript:;"><i class="iconfont icon-shang"></i></a>
</div>
<?php wp_footer(); ?>
<?php if ( is_single() ) { ?>
	<?php if( wpjam_theme_get_setting('comment_flower') ) {?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		POWERMODE.colorful = true;
		POWERMODE.shake = <?php if( wpjam_theme_get_setting('comment_shock') ) {?>true<?php }else{ ?>false<?php } ?>;
		document.body.addEventListener('input', POWERMODE);
	});
	</script>
	<?php } ?>
<?php } ?>
<?php if( wpjam_theme_get_setting('click_effect') ) { ?>
<script type="text/javascript">
	var a_idx = 0;
	jQuery(document).ready(function($) {
		$("body").click(function(e) {
			var a = new Array(<?php
			$click_effect= wpjam_theme_get_setting('click_effect');
			if(is_array( wpjam_theme_get_setting('click_effect') )){
				$i=0;
				foreach ( $click_effect as $value ){
					$i++;
					if($i!=1){echo ',';}
					echo '"'.$value.'"';
				}
			}?>);
			var $i = $("<span/>").text(a[a_idx]);

			a_idx = (a_idx + 1) % a.length;

			var x = e.pageX, y = e.pageY;

			$i.css({
				"z-index": 999999999999999999999999999,
				"top": y - 20,
				"left": x,
				"position": "absolute",
				"font-weight": "bold",
				"color": "<?php echo wpjam_theme_get_setting('click_effect_color');?>"
			});

			$("body").append($i);
			
			$i.animate({
				"top": y - 180,
				"opacity": 0
			},
			1500,
			function() {
				$i.remove();
			});
		});
	});	
</script>
<?php } ?>
<?php if( wpjam_theme_get_setting('nest_switcher') ) {?>
<script type="text/javascript" opacity="<?php echo wpjam_theme_get_setting('nest_opacity') ?>" zIndex="-3" count="<?php echo wpjam_theme_get_setting('nest_count') ?>" src="<?php bloginfo('template_directory'); ?>/static/js/nest.min.js"></script>
<?php } ?>
<?php if(wpjam_theme_get_setting('mobile_foot_menu_no')){ get_template_part( 'template-parts/mobile-foot-menu' ); }?>
</body>
</html>