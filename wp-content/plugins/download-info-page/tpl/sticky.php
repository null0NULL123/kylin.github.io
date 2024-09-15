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
<?php if($sticky_mode > 0): ?>
<div class="wb-sticky-bar<?php if($sticky_mode == 2) echo ' at-bottom'; ?>" id="J_downloadBar">
    <div class="wbsb-inner pw">
        <div class="sb-title">
			<?php the_title(); ?>
        </div>

        <div class="ctrl-box">
            <a class="wb-btn wb-btn-outlined wb-btn-download" href="#J_DLIPPCont"><svg class="wb-icon-dlipp wbsico-dlipp-donwload"><use xlink:href="#wbsico-dlipp-download"> <span>去下载</span></a>
        </div>
    </div>
</div>
<?php endif; ?>
