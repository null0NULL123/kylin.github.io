<?php
/*
Plugin Name: WP资源下载管理
Plugin URI: https://wordpress.org/plugins/download-info-page/
Description: WP资源下载管理插件适用于资源下载类博客，支持站长发布文章时为访客提供本地下载、百度网盘及城通网盘等多种下载方式下载文章资源，并且支持设置登录会员或者评论回复后下载权限。
Author: wbolt team
Version: 1.3.8
Author URI:http://www.wbolt.com/
*/
define('DLIPP_PATH',dirname(__FILE__));
define('DLIPP_BASE_FILE',__FILE__);
define('DLIPP_VERSION','1.3.8');

require_once DLIPP_PATH.'/classes/admin.class.php';
require_once DLIPP_PATH.'/classes/front.class.php';

new DLIP_DownLoadAdmin();

new DLIP_DownLoadFront();


if(!function_exists('wp_post_meta_download_info')){

    function wp_post_meta_download_info($post_id){

        return DLIP_DownLoadAdmin::meta_values($post_id);
    }

}