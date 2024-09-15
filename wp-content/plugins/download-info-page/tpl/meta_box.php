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

<div style=" display:none;">
    <svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <defs>
            <symbol id="sico-upload" viewBox="0 0 16 13">
                <path d="M9 8v3H7V8H4l4-4 4 4H9zm4-2.9V5a5 5 0 0 0-5-5 4.9 4.9 0 0 0-4.9 4.3A4.4 4.4 0 0 0 0 8.5C0 11 2 13 4.5 13H12a4 4 0 0 0 1-7.9z" fill="#666" fill-rule="evenodd"/>
            </symbol>
        </defs>
    </svg>
</div>

<div class="wb-post-sitting-panel">
    <div class="selector-bar switch-bar">
        <label><strong>下载功能</strong> <input class="wb-switch" id="J_DLIPP_SWITCH" type="checkbox" data-target="#J_DIPPMain" name="wb_dl_type"<?php echo $wb_dipp_switch ? ' checked':'';?> value="1"></label>
    </div>

    <div class="wbsp-main default-hidden-box<?php echo $wb_dipp_switch ? ' active':''; ?>" id="J_DIPPMain">
        <h3><strong>文件上传方式</strong></h3>

        <label class="wb-post-sitting-item section-upload">
            <span class="wb-form-label">上传文件</span>
            <div class="wbs-upload-box">
                <input class="wbs-input upload-input" type="text" placeholder="点击右侧上传按钮或者直接贴入下载链接" name="wb_down_local_url" id="wb_down_local_url" value="<?php echo $meta_value['wb_down_local_url'];?>">
                <button type="button" class="wbs-btn wbs-upload-btn">
                    <svg class="wb-icon sico-upload"><use xlink:href="#sico-upload"></use></svg><span>上传</span>
                </button>
            </div>
        </label>

        <label class="wb-post-sitting-item">
            <span class="wb-form-label">城通网盘</span>
            <input class="wbs-input" type="text" name="wb_down_url_ct" placeholder="留意填写完整url" value="<?php echo $meta_value['wb_down_url_ct'];?>">
        </label>

        <div class="wb-post-sitting-item dlipp-item-bdp">
            <label class="bdp-url-item">
                <span class="wb-form-label">百度网盘</span>
                <input class="wbs-input" type="text" name="wb_down_url" placeholder="留意填写完整url" id="wb_down_url" value="<?php echo $meta_value['wb_down_url'];?>">
            </label>
            <label class="bdp-psw-item">
                <span class="wb-form-label">网盘密码</span>
                <input class="wbs-input" type="text" name="wb_down_pwd" placeholder="" id="wb_down_pwd" value="<?php echo $meta_value['wb_down_pwd'];?>">
            </label>
        </div>
        <div class="wb-tip-txt">填入百度网盘客户端或者网页端分享链接及提取码，可自动识别链接和提取码填入哦。</div>

        <label class="wb-post-sitting-item">
            <span class="wb-form-label">磁力链接</span>
            <input class="wbs-input" type="text" name="wb_down_url_magnet" placeholder="请输入以magnet:开头的磁力链接" value="<?php echo $meta_value['wb_down_url_magnet'];?>">
        </label>

        <label class="wb-post-sitting-item">
            <span class="wb-form-label">迅雷下载</span>
            <input class="wbs-input" type="text" name="wb_down_url_xunlei" placeholder="请输入以thunder://开头的迅雷专用下载链接" value="<?php echo $meta_value['wb_down_url_xunlei'];?>">
        </label>


        <h3><strong>下载方式</strong></h3>

        <div class="wb-post-sitting-item">
            <span class="wb-form-label">选择方式</span>
            <div class="selector-bar">
                <label><input class="wbs-radio" type="radio" name="wb_dl_mode"<?php echo !$dl_mode?' checked="checked"':'';?> value="0"> 免费下载</label>
                <label><input class="wbs-radio" type="radio" name="wb_dl_mode"<?php echo $dl_mode=='1'?' checked="checked"':'';?> value="1"> 回复后下载</label>
                <label><input class="wbs-radio" type="radio" name="wb_dl_mode"<?php echo $dl_mode=='2'?' checked="checked"':'';?> value="2"> 付费下载</label>
            </div>
        </div>

        <div class="default-hidden-box set-price-box<?php echo $dl_mode=='2'?' active':'';?>" id="J_WBDLSetPrice">
	    <?php if(!$wpvk_install || !$wpvk_active):?>
            <p class="notice inline notice-warning notice-alt">付费下载需安装"Wordpress付费内容插件"，
            <?php
            if(!$wpvk_install){?>
                <span>未检测到该插件，</span>
                <a class="link" href="<?php echo admin_url('plugin-install.php?s=Wordpress付费内容插件+WP+VK&tab=search&type=term');?>" target="set_plugin">立即下载</a>
            <?php }else if(!$wpvk_active){?>
                <span>未检测到该插件启用，</span>
                <a class="link" href="<?php echo admin_url('plugin-install.php?s=Wordpress付费内容插件+WP+VK&tab=search&type=term');?>" target="set_plugin">立即启用</a>
            <?php } ?>
            </p>
        <?php else: ?>
            <input class="wbs-input w8em" type="hidden" name="wb_down_price" id="wb_down_price" placeholder="" value="<?php echo $meta_value['wb_down_price'];?>">
            <div class="wb-tip-txt">
                <label>
                    <span>设置价格: </span>
                    <input class="wbs-input wbs-input-short" type="number" name="wb_down_vk_price"
                           value="<?php echo $meta_value_vk_price;?>"
                           onchange="document.querySelector('#vk_price').value=this.value;document.querySelector('#wb_down_price').value=this.value"
                    >
                </label>
                <p>* 当前文章启用了付费下载，付费阅读功能失效。</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>


