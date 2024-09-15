<?php function xintheme_strip_tags($content){
	if($content){
		$content = preg_replace("/\[.*?\].*?\[\/.*?\]/is", "", $content);
	}
	return strip_tags($content);
} ?>
<div class="user comment col-lg-9">
	<div class="row posts-wrapper">
		<h3 class="section-title"><span>我的评论</span></h3>
          <div class="favorite-wrapper ng-scope">
            <?php
				$counts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE user_id=" . get_current_user_id());
				$perpage = 10;
				$pages = ceil($counts / $perpage);
				$paged = (get_query_var('paged')) ? $wpdb->escape(get_query_var('paged')) : 1;
				
				$args = array('type'=>'comments','user_id' => get_current_user_id(), 'number' => 10, 'offset' => ($paged - 1) * 10);
				$lists = get_comments($args);
				
			?>
            <div class="ng-isolate-scope loading-content-wrap loading-show loading-show-active">
              <?php
              	if($lists) {
			  ?>
              <div class="list-group ng-scope">
                <div class="list-group-item content-list-item ng-scope">
                  <?php foreach($lists as $value){ ?>
					<li>
						<a class="comment" href="<?php echo get_permalink($value->comment_post_ID);?>#comments"><?php echo mb_strimwidth( xintheme_strip_tags( $value->comment_content ), 0, 50,"...");?></a>
						<div class="plp2"><span><?php echo $value->comment_date; ?>　</span><span>评论文章：<a style="color: #bababa;" target="_blank" href="<?php echo get_permalink($value->comment_post_ID);?>#comments"><?php echo get_post($value->comment_post_ID)->post_title;?></a></span></div>
					</li>
                  <?php }?>
                </div>
              </div>
              <!--div class="text-center">
                <nav class="text-center comp-pagination">
                  分页
                </nav>
              </div-->
			  <?php }?>
            </div>
          </div>
	</div>
</div>