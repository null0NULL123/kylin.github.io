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

class DLIP_DownLoadCommon{
	public static $name = 'dlip_pack';
	public static $mataPrefix = '_mddp_down_';
	public static $optionName = 'dlip_option';
	public static $langPack = 'dlip_textdomain';
	public static $settingField = 'dlip_pack_options';
	
	public static function fields(){
		//TODO 多语言
		$fields = array(
			'title' => array('text'=>'文件名称','type'=>'text'),
			'url' => array('text'=>'下载地址','type'=>'text'),
			'pwd' => array('text'=>'下载密码','type'=>'text'),
			'version' => array('text'=>'文件版本','type'=>'text'),
			'format' => array('text'=>'文件格式','type'=>'text'),
			'size' => array('text'=>'文件大小','type'=>'text'),
		);
		$fields = apply_filters('dlip_mata_fields',$fields);
		return $fields;
	}
}
 
