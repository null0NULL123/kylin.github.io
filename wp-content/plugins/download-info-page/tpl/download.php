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

<div id="J_DLIPPCont" class="dlipp-cont-wp">
    <div class="dlipp-cont-inner">
        <div class="dlipp-cont-hd">
            <svg class="wb-icon-dlipp wbsico-dlipp-local"><use xlink:href="#wbsico-dlipp-local"></use></svg> <span>相关文件下载地址</span>
        </div>

        <div class="dlipp-cont-bd">
            <?php if($need_pay && !$is_buy): ?>
                <?php
	            /**
	             * 付费
	             */
                echo $pay_tips_content;
                ?>
            <?php else: ?>

                <?php if($need_login && !$is_login){//if login ?>
                    <div class="wb-tips">该资源需登录后下载，去<a class="link" href="<?php echo wp_login_url(get_permalink());?>">登录</a>?</div>

                <?php }elseif($need_comment && !$is_comment){//else if need comment ?>
                    <div class="wb-tips">*该资源需回复评论后下载，马上去<a class="link" href="#comments">发表评论</a>?</div>

                <?php }else{//else if login ?>
                    <input class="with-psw" style="z-index: 0; opacity: 0; position: absolute; width:20px;" id="WBDL_PSW">

                    <?php foreach ($dl_info as $k => $v): ?>
                        <a class="dlipp-dl-btn j-wbdlbtn-dlipp" data-rid="<?php echo $k; ?>">
                            <svg class="wb-icon-dlipp wbsico-dlipp-<?php echo $k; ?>"><use xlink:href="#wbsico-dlipp-<?php echo $k; ?>"></use></svg><span><?php echo $v['name']; ?></span>
                        </a>
                    <?php endforeach; ?>

                <?php } //end if login ?>

            <?php endif; //end if pay ?>
        </div>

        <div class="dlipp-cont-ft"><?php echo $remark_info ? $remark_info : '&copy;下载资源版权归作者所有；本站所有资源均来源于网络，仅供学习使用，请支持正版！'; ?></div>
    </div>
</div>