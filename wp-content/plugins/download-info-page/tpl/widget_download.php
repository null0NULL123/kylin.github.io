<?php
/**
 * This was contained in an addon until version 1.0.0 when it was rolled into
 * core.
 *
 * @package    WBOLT
 * @author     WBOLT
 * @since      1.1.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019, WBOLT
 */

?>

<section class="widget widget-wbdl" id="J_widgetWBDownload">
    <h3 class="widgettitle">下载</h3>
    <div class="widget-main">
	    <?php include DLIPP_PATH.'/tpl/download_btn.php'; ?>

	    <?php if( $display_count ): ?>
            <p class="dl-count">已下载<span class="j-wbdl-count"><?php echo DLIP_DownLoadFront::getPostMataVal('post_downs'); ?></span>次</p>
	    <?php endif; ?>
    </div>
</section>