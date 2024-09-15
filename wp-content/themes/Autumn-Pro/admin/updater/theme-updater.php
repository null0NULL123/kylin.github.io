<?php
/**
 * Easy Digital Downloads Theme Updater
 *
 * @package EDD Sample Theme
 */

// Includes the files needed for the theme updater
if ( !class_exists( 'EDD_Theme_Updater_Admin' ) ) {
	include( dirname( __FILE__ ) . '/theme-updater-admin.php' );
}

// Loads the updater classes
$updater = new EDD_Theme_Updater_Admin(

	// Config settings
	$config = array(
		'remote_api_url' => 'https://www.xintheme.com', // Site where EDD is hosted
		'item_name'      => 'Autumn-Pro', // Name of theme
		'theme_slug'     => 'autumn-pro', // Theme slug
		'version'        => wp_get_theme()->get( 'Version' ),
		'author'         => wp_get_theme()->get( 'Author' ),
		'download_id'    => '53480', // Optional, used for generating a license renewal link
		'renew_url'      => '', // Optional, allows for a custom license renewal link
		'beta'           => false, // Optional, set to true to opt into beta versions
	),

	// Strings
	$strings = array(
		'theme-license'             => __( '主题授权', 'part of the WordPress dashboard Themedd menu title', 'themedd' ),
		'enter-key'                 => __( '输入您的授权码.', 'themedd' ),
		'license-key'               => __( '授权码', 'themedd' ),
		'license-action'            => __( '主题激活', 'themedd' ),
		'deactivate-license'        => __( '解除授权' , 'themedd' ),
		'activate-license'          => __( '激活授权', 'themedd' ),
		'status-unknown'            => __( '授权发生了未知错误', 'themedd' ),
		'renew'                     => __( '刷新?', 'themedd' ),
		'unlimited'                 => __( '无限', 'themedd' ),
		'license-key-is-active'     => __( '授权已激活.', 'themedd' ),
		'expires%s'                 => __( '%s 已使用.', 'themedd' ),
		'lifetime'                  => __( '终身授权.', 'themedd' ),
		'%1$s/%2$-sites'            => __( '您已经激活 %1$s 个站点（共可授权 %2$s 个站点）.', 'themedd' ),
		'license-key-expired-%s'    => __( '授权码已经过期 %s.', 'themedd' ),
		'license-key-expired'       => __( '授权码.', 'themedd' ),
		'license-keys-do-not-match' => __( '授权码不匹配.', 'themedd' ),
		'license-is-inactive'       => __( '此授权被远程计算机关闭.', 'themedd' ),
		'license-key-is-disabled'   => __( '验证码.', 'themedd' ),
		'site-is-inactive'          => __( '此站点授权被取消.', 'themedd' ),
		'license-status-unknown'    => __( '授权状态未知.', 'themedd' ),
		'update-notice'             => __( "进行更新的过程中，会覆盖您自己对主题修改的部分，请在升级前做好备份工作，出现异常概不负责！（点击【确定】将进行更新！）", 'themedd' ),
		'update-available'          => __( '<strong>%1$s %2$s</strong> 更新可用. <a href="%3$s" class="thickbox" title="%4s">查看详情</a> 或者 <a href="%5$s"%6$s>直接升级</a>.', 'themedd' )
	)

);

function xintheme_license_notify() {
    $status = get_option( 'autumn-pro_license_key_status' );
    if( $status != false && $status == 'valid' )
    return;
    if( !is_admin() && !is_module('user') ){
        wp_die(
            '<div class="no-verify-box" style="text-align: center;">
			<div class="no-verify-inner" style="background: #fff6dc;padding: 20px;margin-top: 20px;">
				<p style="font-family:microsoft yahei;margin:10px 0;line-height:30px">您好，您还没有激活本主题， 请到 <a href="/wp-admin/themes.php?page=autumn-pro-license">[后台 → 外观 → 主题授权 ]</a>进行激活</br>
			</p>
			</div>
			</div>'
        );
    }
}
add_action( 'wp_head', 'xintheme_license_notify' );

