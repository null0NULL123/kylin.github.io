<?php
add_action('parse_comment_query', function($comment_query){
	if(is_singular()){
		if(isset($comment_query->query_vars['parent']) && $comment_query->query_vars['parent'] === 0){
			$comment_query->query_vars['type']	= 'comment';
		}	
	}
});

//评论列表
function wpjam_theme_list_comments($comment, $args, $depth) { ?>
<li id="comment-<?php comment_ID() ?>" <?php comment_class(); ?>>
	<div id="div-comment-<?php comment_ID() ?>" class="comment-wrapper u-clearfix">
		<div class="comment-author-avatar vcard">
			<?php echo get_avatar($comment,60); ?>
		</div>
		<div class="comment-content">
			<div class="comment-author-name vcard">
				<cite class="fn"><?php /*wpjam_comment_level($comment);*/ comment_author_link();?></cite>
			</div>
			<div class="comment-metadata">
				<time><?php comment_date() ?> <?php comment_time() ?></time>
				<span class="reply-link">
					<?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => "回复"))) ?>
				</span>
			</div>
			<div class="comment-body" itemprop="comment">
				<?php comment_text() ?>
				<?php if ( $comment->comment_approved == '0' ) { ?>
					<font style="color:#C00; font-style:inherit">您的评论正在等待审核中...</font>
				<?php } ?>
			</div>
		</div>
	</div>
</li>
<?php }

//评论等级
function wpjam_author_class($comment_author_email){
	global $wpdb; $author_count = count($wpdb->get_results( "SELECT comment_ID as author_count FROM $wpdb->comments WHERE comment_author_email = '$comment_author_email' ")); 
	$adminEmail = get_option('admin_email');if($comment_author_email ==$adminEmail) return;
	if($author_count>=1 && $author_count<10 && $comment_author_email!=$adminEmail)
		echo '<span class="level level-0">初来乍到</span>';
	else if($author_count>=10 && $author_count< 20)
		echo '<span class="level level-1">江湖少侠</span>';
	else if($author_count>=20 && $author_count< 40)
		echo '<span class="level level-2">崭露头角</span>';
	else if($author_count>=40 && $author_count< 60)
		echo '<span class="level level-3">自成一派</span>';
	else if($author_count>=60 && $author_count< 80)
		echo '<span class="level level-4">横扫千军</span>';
	else if($author_count>=80&& $author_count<100)
		echo '<span class="level level-5">登峰造极</span>';
	else if($author_count>=100&& $author_count< 120)
		echo '<span class="level level-6">一统江湖</span>';
}

function wpjam_comment_level($comment){
	$html = "";
	if(($vip = wpjam_author_class($comment->comment_author_email))){
		$html .= '' . $vip . '>';
		for($i = 0; $i < $vip; $i++){
			$html .= '';
		}
		$html .= '';
	};
	echo $html;
}
