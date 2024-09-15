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
<?php if($need_login && !$is_login){//if login ?>
    <a href="<?php echo wp_login_url(get_permalink());?>"><svg class="wb-icon-dlipp wbsico-download"><use xlink:href="#wbsico-dlipp-donwload"></use></svg> <span>去下载</span></a>

<?php }elseif($need_comment && !$is_comment){//else if need comment ?>
    <p class="wb-tips">*该资源需回复评论后下载 <a href="#comments">去评论</a></p>
<?php }else{//else if login ?>

    <a class="wb-btn wb-btn-outlined wb-btn-download" href="#J_DLIPPCont"><svg class="wb-icon-dlipp wbsico-dlipp-donwload"><use xlink:href="#wbsico-dlipp-download"> <span>去下载</span></a>


<?php } //end if login ?>