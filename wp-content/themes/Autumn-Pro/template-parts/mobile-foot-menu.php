
<style>@media screen and (max-width:767px){.site-footer,.login-dahuzi{margin-bottom: 55px}}</style>
<div class="mobile_btn">
	<?php
	if($mobile_foot_menu = wpjam_theme_get_setting('mobile_foot_menu')){?>
	
	<?php if( count($mobile_foot_menu) > 4){?><style>.mobile_btn ul li {min-width: 20%}</style><?php }?>
	
	<ul<?php if(wpjam_theme_get_setting('mobile_foot_menu_700')) {?> class="mobile_foot_menu_700"<?php }?>>
	<?php $mobile_foot_menu	= array_filter($mobile_foot_menu, function($mobile_foot_menu){ ?>

		<?php if( $mobile_foot_menu['mobile_foot_menu_type'] == 'link' ){?>
		<li>
			<a href="<?php echo $mobile_foot_menu['mobile_foot_menu_url'];?>" rel="nofollow"><i class="iconfont <?php echo $mobile_foot_menu['mobile_foot_menu_icon'];?>"></i><?php echo $mobile_foot_menu['mobile_foot_menu_text'];?></a>
		</li>
		<?php }?>

		<?php if( $mobile_foot_menu['mobile_foot_menu_type'] == 'home' ){?>
		<li>
			<a href="<?php echo home_url(); ?>" rel="nofollow" <?php if( is_home() && !is_module('user') ){?>id="mobile_foot_menu_home"<?php }?>><i class="iconfont icon-shouye"></i>首页</a>
		</li>
		<?php }?>

		<?php if( $mobile_foot_menu['mobile_foot_menu_type'] == 'img' ){?>
		<li>
			<a id="mobile_foot_menu_img" class="mobile_foot_menu_img" href="javascript:void(0);"><i class="iconfont <?php echo $mobile_foot_menu['mobile_foot_menu_icon'];?>"></i><?php echo $mobile_foot_menu['mobile_foot_menu_text'];?></a>
		</li>
		<div class="mobile-foot-weixin-dropdown">
			<div class="tooltip-weixin-inner">
				<h3><?php echo $mobile_foot_menu['mobile_foot_menu_img_text'];?></h3>
				<div class="qcode"> 
					<img src="<?php echo $mobile_foot_menu['mobile_foot_menu_img'];?>" alt="<?php echo $mobile_foot_menu['mobile_foot_menu_img_text'];?>">
				</div>
			</div>
			<div class="close-weixin">
				<span class="close-top"></span>
					<span class="close-bottom"></span>
		    </div>
		</div>
		<?php }?>

		<?php if( $mobile_foot_menu['mobile_foot_menu_type'] == 'user' ){?>
			<?php global $current_user; if ( is_user_logged_in() ) { ?>
				<li>
					<a href="<?php echo home_url(user_trailingslashit('/user')); ?>" rel="nofollow"><i class="iconfont icon-weidenglu"></i>个人中心</a>
				</li>
			<?php }else{?>
				<li>
					<a href="<?php echo home_url(user_trailingslashit('/user/login')); ?>" rel="nofollow"><i class="iconfont icon-weidenglu"></i>登录</a>
				</li>
			<?php }?>
		<?php }?>

	<?php }); ?>
	<?php }?>
	</ul>
</div>