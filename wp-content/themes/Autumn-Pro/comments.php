<?php if(post_password_required()) return;?>

<?php if( get_option('users_can_register') ){?>

	<?php if( is_user_logged_in() ){?>

		<div id="comments" class="comments-area">
			<h3 class="section-title"><span><?php comments_number('暂无评论', '1 条评论', '% 条评论' );?></span></h3>
			<?php if(have_comments()){ ?>
				<ol class="comment-list">
					<?php wp_list_comments('type=comment&callback=wpjam_theme_list_comments'); ?>
				</ol>

				<?php the_comments_pagination(['prev_text'=>'<i class="iconfont icon-xiangyou1"></i>', 'next_text'=>'<i class="iconfont icon-xiangzuo1"></i>']); ?>
			<?php } ?>
			<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) { ?>
				<p class="no-comments"><?php _e( 'Comments are closed.' ); ?></p>
			<?php } ?>
			<?php
				$comments_args = array(
					'title_reply' => '发表评论',
					'logged_in_as' => '<p class="logged-in-as">' . sprintf( __( '当前登录账号为：<a href="%1$s">%2$s</a>，<a href="%3$s" title="退出登录?">退出登录?</a>' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( ) ) ) ) . '</p>',
					'label_submit' => '发表评论',
				);
				comment_form($comments_args);
			?>
		</div>
		<style>
		<?php if( wpjam_theme_get_setting('comment-form-url') ) : ?>
		.comment-form-url{display: none;}
		<?php endif; ?>
		</style>

	<?php }else{?>

		<div id="comments" class="comments-area">
			<h3 class="section-title"><span><?php comments_number('暂无评论', '1 条评论', '% 条评论' );?></span></h3>
			<?php if(have_comments()){ ?>
				<ol class="comment-list">
					<?php wp_list_comments('type=comment&callback=wpjam_theme_list_comments'); ?>
				</ol>
				<?php the_comments_pagination(['prev_text'=>'<i class="iconfont icon-xiangyou1"></i>', 'next_text'=>'<i class="iconfont icon-xiangzuo1"></i>']); ?>
			<?php } ?>
			<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) { ?>
				<p class="no-comments"><?php _e( 'Comments are closed.' ); ?></p>
			<?php } ?>
			<style> .reply-link{display:none} </style>
			<div class="must-log-in">
				<p>
					要发表评论，您必须先 <a href="<?php echo home_url(user_trailingslashit('/user/login')); ?>" rel="nofollow"><i class="iconfont icon-weidenglu"></i>登录</a>
				</p>
			</div>
		</div>

	<?php }?>

<?php }else{?>

	<div id="comments" class="comments-area">
		<h3 class="section-title"><span><?php comments_number('暂无评论', '1 条评论', '% 条评论' );?></span></h3>
		<?php if(have_comments()){ ?>
			<ol class="comment-list">
				<?php wp_list_comments('type=comment&callback=wpjam_theme_list_comments'); ?>
			</ol>

			<?php the_comments_pagination(['prev_text'=>'<i class="iconfont icon-xiangyou1"></i>', 'next_text'=>'<i class="iconfont icon-xiangzuo1"></i>']); ?>
		<?php } ?>
		<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) { ?>
			<p class="no-comments"><?php _e( 'Comments are closed.' ); ?></p>
		<?php } ?>

		<?php
			$comments_args = array(
				'title_reply' => '发表评论',
				'comment_notes_before'  => '<p class="comment-notes"><span id="email-notes">您的电子邮件地址不会被公开，</span>必填项已用<span class="required">*</span>标注。</p>',
				'cancel_reply_link' => '取消回复',

				'fields' => apply_filters( 'comment_form_default_fields', array(
                	'author' => '<p class="comment-form-author"><label for="author">昵称 <span class="required">*</span></label> <input id="author" name="author" type="text" value="" size="30" maxlength="245" required="required"></p>',
                	'email' => '<p class="comment-form-email"><label for="email">邮箱 <span class="required">*</span></label> <input id="email" name="email" type="text" value="" size="30" maxlength="100" aria-describedby="email-notes" required="required"></p>',
                	'url' => '<p class="comment-form-url"><label for="url">网址</label> <input id="url" name="url" type="text" value="" size="30" maxlength="200"></p>',
                	'cookies' => '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"><label for="wp-comment-cookies-consent">在此浏览器中保存我的昵称、邮箱地址。</label></p>'
                ) ),
				'logged_in_as' => '<p class="logged-in-as">' . sprintf( __( '当前登录账号为： <a href="%1$s">%2$s</a>，<a href="%3$s" title="退出登录?">退出登录?</a>' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( ) ) ) ) . '</p>',
				'label_submit' => '发表评论',
			);
			comment_form($comments_args);
		?>

	</div>
	<style>
		<?php if( wpjam_theme_get_setting('comment-form-url') ) : ?>
			.comment-form-url{display: none;}
		<?php endif; ?>
	</style>

<?php }?>