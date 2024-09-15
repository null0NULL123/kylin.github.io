<?php
/**
 * This was contained in an addon until version 1.0.0 when it was rolled into
 * core.
 *
 * @package    WBOLT
 * @author     WBOLT
 * @since      1.3.3
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019, WBOLT
 */


wp_enqueue_script('wbp-js', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/wbp_setting.js', array(), DLIPP_VERSION, true);
//wp_enqueue_media();

$pd_title = 'WP资源下载管理';
$pd_version = DLIPP_VERSION;
$pd_code = 'dip-setting';
$pd_index_url = 'https://www.wbolt.com/plugins/dip';
$pd_doc_url = 'https://www.wbolt.com/dip-plugin-documentation.html';

?>

<div style=" display:none;">
    <svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <defs>
            <symbol id="sico-upload" viewBox="0 0 16 13">
                <path d="M9 8v3H7V8H4l4-4 4 4H9zm4-2.9V5a5 5 0 0 0-5-5 4.9 4.9 0 0 0-4.9 4.3A4.4 4.4 0 0 0 0 8.5C0 11 2 13 4.5 13H12a4 4 0 0 0 1-7.9z" fill="#666" fill-rule="evenodd"/>
            </symbol>
            <symbol id="sico-download" viewBox="0 0 16 16">
                <path d="M9 9V0H7v9H4l4 4 4-4z"/><path d="M15 16H1a1 1 0 0 1-1-1.1l1-8c0-.5.5-.9 1-.9h3v2H2.9L2 14H14L13 8H11V6h3c.5 0 1 .4 1 .9l1 8a1 1 0 0 1-1 1.1"/>
            </symbol>
            <symbol id="sico-wb-logo" viewBox="0 0 18 18">
                <title>sico-wb-logo</title>
                <path d="M7.264 10.8l-2.764-0.964c-0.101-0.036-0.172-0.131-0.172-0.243 0-0.053 0.016-0.103 0.044-0.144l-0.001 0.001 6.686-8.55c0.129-0.129 0-0.321-0.129-0.386-0.631-0.163-1.355-0.256-2.102-0.256-2.451 0-4.666 1.009-6.254 2.633l-0.002 0.002c-0.791 0.774-1.439 1.691-1.905 2.708l-0.023 0.057c-0.407 0.95-0.644 2.056-0.644 3.217 0 0.044 0 0.089 0.001 0.133l-0-0.007c0 1.221 0.257 2.314 0.643 3.407 0.872 1.906 2.324 3.42 4.128 4.348l0.051 0.024c0.129 0.064 0.257 0 0.321-0.129l2.25-5.593c0.064-0.129 0-0.257-0.129-0.321z"></path>
                <path d="M16.714 5.914c-0.841-1.851-2.249-3.322-4.001-4.22l-0.049-0.023c-0.040-0.027-0.090-0.043-0.143-0.043-0.112 0-0.206 0.071-0.242 0.17l-0.001 0.002-2.507 5.914c0 0.129 0 0.257 0.129 0.321l2.571 1.286c0.129 0.064 0.129 0.257 0 0.386l-5.979 7.264c-0.129 0.129 0 0.321 0.129 0.386 0.618 0.15 1.327 0.236 2.056 0.236 2.418 0 4.615-0.947 6.24-2.49l-0.004 0.004c0.771-0.771 1.414-1.671 1.929-2.7 0.45-1.029 0.643-2.121 0.643-3.279s-0.193-2.314-0.643-3.279z"></path>
            </symbol>
            <symbol id="sico-more" viewBox="0 0 16 16">
                <path d="M6 0H1C.4 0 0 .4 0 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1M15 0h-5c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1M6 9H1c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1v-5c0-.6-.4-1-1-1M15 9h-5c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1v-5c0-.6-.4-1-1-1"/>
            </symbol>
            <symbol id="sico-plugins" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M16 3h-2V0h-2v3H8V0H6v3H4v2h1v2a5 5 0 0 0 4 4.9V14H2v-4H0v5c0 .6.4 1 1 1h9c.6 0 1-.4 1-1v-3.1A5 5 0 0 0 15 7V5h1V3z"/>
            </symbol>
            <symbol id="sico-doc" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 0H1C.4 0 0 .4 0 1v14c0 .6.4 1 1 1h14c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1zm-1 2v9h-3c-.6 0-1 .4-1 1v1H6v-1c0-.6-.4-1-1-1H2V2h12z"/><path d="M4 4h8v2H4zM4 7h8v2H4z"/>
            </symbol>
        </defs>
    </svg>
</div>

<div id="version_tips" style="display: none;">
    <div class="update-message notice inline notice-warning notice-alt">
        <p>当前 <?php echo $pd_title;?> 有新版本可用. <a href="<?php echo $pd_index_url; ?>" data-wba-campaign="notice-bar#J_updateRecordsSection" target="_blank">查看版本<span class="ver"></span> 详情</a>
            或 <a href="<?php echo admin_url('/plugins.php?plugin_status=upgrade');?>" class="update-link" aria-label="现在更新<?php echo $pd_title;?>">现在更新</a>.
        </p>

    </div>
</div>

<div id="optionsframework-wrap" class="wbs-wrap wbps-wrap" data-wba-source="<?php echo $pd_code; ?>">
    <div class="wbs-header">
        <svg class="wb-icon sico-wb-logo"><use xlink:href="#sico-wb-logo"></use></svg>
        <span>WBOLT</span>
        <strong><?php echo $pd_title; ?></strong>

        <div class="links">
            <a class="wb-btn" href="<?php echo $pd_index_url; ?>" data-wba-campaign="title-bar" target="_blank">
                <svg class="wb-icon sico-plugins"><use xlink:href="#sico-plugins"></use></svg>
                <span>插件主页</span>
            </a>
            <a class="wb-btn" href="<?php echo $pd_doc_url; ?>" data-wba-campaign="title-bar" target="_blank">
                <svg class="wb-icon sico-doc"><use xlink:href="#sico-doc"></use></svg>
                <span>说明文档</span>
            </a>
        </div>
    </div>

    <div class="wbs-main">

        <form class="wbs-content option-form" id="optionsframework" action="options.php" method="post">
			<?php
			settings_fields($setting_field);

			$switch = isset($opt['switch']) && $opt['switch']?1:0;

			?>
            <h3 class="sc-header">
                <strong>资源下载管理设置</strong>
            </h3>
            <div class="sc-body">
                <table class="wbs-form-table">
                    <tbody>
                    <tr>
                        <th class="row w8em">下载功能开关</th>
                        <td>
                            <input class="wb-switch" data-target="#J_needLoginForDl"  type="checkbox" name="<?php echo $setting_field;?>[switch]"<?php echo $switch?' checked':'';?> value="1" id="dl_switch">
                            <label class="description" for="dl_switch">启用下载功能后，发布文章支持填入下载链接</label>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="default-hidden-box<?php echo $switch ? ' active':''; ?>" id="J_needLoginForDl">
                    <table class="wbs-form-table">
                        <tbody>
                        <tr>
                            <th class="row w8em">是否需要登录</th>
                            <td>
                                <input class="wb-switch" type="checkbox" name="<?php echo $setting_field;?>[need_member]"<?php echo isset($opt['need_member']) && $opt['need_member']?' checked':'';?> value="1" id="dl_need_login">
                                <label class="description" for="sign_active">启用登录下载，需要访客注册登录下载，否则可以直接下载 </label>
                            </td>
                        </tr>
                        <tr style="display:none;">
                            <th class="row w8em">是否显示下载数</th>
                            <td>
                                <input class="wb-switch" type="checkbox" name="<?php echo $setting_field;?>[display_count]"<?php echo isset($opt['display_count']) && $opt['display_count']?' checked':'';?> value="1">
                                <label class="description"></label>
                            </td>
                        </tr>
                        <tr>
                            <th class="row w8em">下载浮层</th>
                            <td>
                                <div class="selector-bar with-icons">
                                    <label><input class="wbs-radio" type="radio" name="<?php echo $setting_field;?>[sticky_mode]" value="0" <?php echo !$opt['sticky_mode'] || $opt['sticky_mode']==0?  'checked' : ''; ?>> 关闭（默认）</label>
                                    <label>
                                        <input class="wbs-radio" type="radio" name="<?php echo $setting_field;?>[sticky_mode]" value="1" <?php echo $opt['sticky_mode']==1?  'checked' : ''; ?>>
                                        <svg class="ib" xmlns="http://www.w3.org/2000/svg" width="54" height="54"><g fill="none" fill-rule="evenodd"><path fill="#EEE" stroke="#E3E3E3" d="M.5.5h53v53H.5z"/><path fill="#FFF" d="M1 1h52v9H1z"/><rect width="13" height="5" x="37" y="3" fill="#7DADDC" rx="1"/></g></svg>
                                    </label>
                                    <label>
                                        <input class="wbs-radio" type="radio" name="<?php echo $setting_field;?>[sticky_mode]" value="2" <?php echo $opt['sticky_mode']==2?  'checked' : ''; ?>>
                                        <svg class="ib" xmlns="http://www.w3.org/2000/svg" width="54" height="54"><g fill="none" fill-rule="evenodd"><path fill="#EEE" stroke="#E3E3E3" d="M.5.5h53v53H.5z"/><path fill="#FFF" d="M1 44h52v9H1z"/><rect width="13" height="5" x="37" y="46" fill="#7DADDC" rx="1"/></g></svg>
                                    </label>
                                </div>
                                <div class="description mt">*启用下载按钮置顶或者底部固定后，将会页面顶部或者底部展示下载按钮相关内容。如您所使用的主题已经有顶部或者底部固定栏，切勿启用该功能。</div>
                            </td>
                        </tr>
                        <tr style="display:none;">
                            <th class="row w8em">下载按钮位置</th>
                            <td>
                                <div class="selector-bar">
                                    <label>
                                        <input class="wbs-radio" type="radio" name="<?php echo $setting_field;?>[btn_align]" value="0" <?php echo !$opt['btn_align'] || $opt['btn_align']==0?  'checked' : ''; ?>> 左对齐（默认）</label>
                                    <label>
                                        <input class="wbs-radio" type="radio" name="<?php echo $setting_field;?>[btn_align]" value="1" <?php echo $opt['btn_align']==1?  'checked' : ''; ?>> 居中
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="row">版权说明</th>
                            <td>
                                <input class="wbs-input" type="text" name="<?php echo $setting_field;?>[remark]" placeholder="请输入" value="<?php echo isset($opt['remark'])?$opt['remark']:'';?>">
                                <p class="description">默认及没有输入自定义内容，将显示"©下载资源版权归作者所有；本站所有资源均来源于网络，仅供学习使用，请支持正版！"</p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <script type="text/javascript" src="https://www.wbolt.com/wb-api/v1/news/lastest"></script>

            <div class="wb-copyright-bar">
                <div class="wbcb-inner">
                    <a class="wb-logo" href="https://www.wbolt.com" data-wba-campaign="footer" title="WBOLT" target="_blank"><svg class="wb-icon sico-wb-logo"><use xlink:href="#sico-wb-logo"></use></svg></a>
                    <div class="wb-desc">
                        Made By <a href="https://www.wbolt.com" data-wba-campaign="footer" target="_blank">闪电博</a>
                        <span class="wb-version">版本：<?php echo $pd_version;?></span>
                    </div>
                    <div class="ft-links">
                        <a href="https://www.wbolt.com/plugins" data-wba-campaign="footer" target="_blank">免费插件</a>
                        <a href="https://www.wbolt.com/resources" data-wba-campaign="footer" target="_blank">插件支持</a>
                        <a href="<?php echo $pd_doc_url; ?>" data-wba-campaign="footer" target="_blank">说明文档</a>
                        <a href="https://www.wbolt.com/terms-conditions" data-wba-campaign="footer" target="_blank">服务协议</a>
                        <a href="https://www.wbolt.com/privacy-policy" data-wba-campaign="footer" target="_blank">隐私条例</a>
                    </div>
                </div>
            </div>

            <div class="wbs-footer sticky-bottom" id="optionsframework-submit">
                <div class="wbsf-inner">
                    <button class="wbs-btn-primary" type="submit" name="update">保存设置</button>
                </div>
            </div>
        </form>
    </div>
</div>
