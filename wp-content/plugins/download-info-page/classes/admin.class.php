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




class DLIP_DownLoadAdmin{


    public static $name = 'dlip_pack';
    public static $mataPrefix = '_mddp_down_';
    public static $optionName = 'dlip_option';


    public static $meta_fields = array(
    	'wb_dl_type', //下载开关
	    'wb_dl_mode', //下载方式
	    'wb_down_local_url',
	    'wb_down_url_ct',
	    'wb_down_url',
	    'wb_down_pwd',
	    'wb_down_url_magnet',
	    'wb_down_url_xunlei',
	    'wb_down_price'
    );

	public function __construct(){

        if(is_admin()){


            add_action( 'admin_menu', array($this,'admin_menu') );
            add_action( 'admin_init', array($this,'admin_init') );

            add_filter( 'plugin_action_links', array($this,'actionLinks'), 10, 2 );

            //设置页引入样式
            add_action('admin_enqueue_scripts',array($this,'admin_enqueue_scripts'),1);

	        add_action('wp_ajax_wb_dlipp',array(__CLASS__,'wp_ajax_wb_dlipp'));

            register_activation_hook(DLIPP_BASE_FILE, array($this, 'activation'));
            register_deactivation_hook(DLIPP_BASE_FILE, array($this, 'deactivation'));

            add_filter('plugin_row_meta', array(__CLASS__, 'plugin_row_meta'), 10, 2);


            if(self::cnf('switch')){

                //自定表单
                add_action( 'add_meta_boxes', array($this,'addMataBox'));

                //保存自定义表单数据
                add_action( 'save_post', array($this,'saveMataData'));
            }

        }
	}

	public function admin_enqueue_scripts($hook){
        global $wb_settings_page_hook_dlipp;
        if($wb_settings_page_hook_dlipp != $hook) return;
        wp_enqueue_style('wbs-style-dlipp', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/wbp_setting.css', array(), DLIPP_VERSION);

    }

    public static function cnf($key=null,$default=null){
        static $_option = array();
        if(!$_option){
            $_option = get_option(self::$optionName,array('switch'=>1,'need_member'=>0,'display_count'=>0,'sticky_mode'=>0,'btn_align'=>0,'remark'=>''));
        }

        if(null === $key){
            return $_option;
        }

        if(isset($_option[$key])){
            return $_option[$key];
        }

        return $default;

    }


    //兼容转换旧数据
    private static  function compat(){


	    global $wpdb;

	    $num = $wpdb->get_var("select count(1) num from $wpdb->postmeta where meta_key='_mddp_down_url'");
	    if(!$num){
	        return true;
        }

        //删除空的meta
        foreach(array('title','url','pwd','version','format','size') as $v){
	        $meta_key = '_mddp_down_'.$v;
	        $sql = "DELETE FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=''";
	        $wpdb->query($wpdb->prepare($sql,$meta_key));
        }

        //转换旧meta为新meta
        foreach(array('title','url','pwd') as $v){
	        $old_key = '_mddp_down_'.$v;
	        $new_key = 'wb_down_'.$v;
	        $sql = "UPDATE $wpdb->postmeta SET meta_key=%s WHERE meta_key=%s";
	        $wpdb->query($wpdb->prepare($sql,$new_key,$old_key));
        }

        //转换类型为百度下载
        $wpdb->query("INSERT INTO $wpdb->postmeta(post_id,meta_key,meta_value) SELECT post_id,'wb_dl_type','2' FROM $wpdb->postmeta WHERE meta_key='wb_down_url'");


    }

    public static function meta_values($post_id){


	    //self::compat();


        $meta_values = array();
        foreach(self::$meta_fields as $field){
            $meta_values[$field] = get_post_meta($post_id,$field,true);
        }
        if('' === $meta_values['wb_dl_type']){
            $meta_values['wb_dl_type'] = '0';
        }
        if('' === $meta_values['wb_dl_mode']){
            $meta_values['wb_dl_mode'] = '0';
        }

        //print_r($meta_values);


        return $meta_values;
    }



	public function activation(){
        /*
        $_option = self::cnf();
        if(!$_option){
            $_option = array('switch'=>1);
            update_option(self::$optionName,$_option);
        }
        */
    }


    public function deactivation(){

	    delete_option(self::$optionName);

    }

    public static function plugin_row_meta($links,$file){

        $base = plugin_basename(DLIPP_BASE_FILE);
        if($file == $base) {
            $links[] = '<a href="https://www.wbolt.com/plugins/dip?utm_source=dip_setting&utm_medium=link&utm_campaign=plugins_list">插件主页</a>';
            $links[] = '<a href="https://www.wbolt.com/dip-plugin-documentation.html?utm_source=dip_setting&utm_medium=link&utm_campaign=plugins_list">FAQ</a>';
            $links[] = '<a href="https://wordpress.org/support/plugin/download-info-page/">反馈</a>';
        }
        return $links;
    }
	function actionLinks( $links, $file ) {
		
		if ( $file != plugin_basename(DLIPP_BASE_FILE) )
			return $links;
	
		$settings_link = '<a href="'.menu_page_url( self::$name, false ).'">设置</a>';
	
		array_unshift( $links, $settings_link );
	
		return $links;
	}
	
	function admin_menu(){
		global $wb_settings_page_hook_dlipp;
		$wb_settings_page_hook_dlipp = add_options_page(
			'WP资源下载管理设置',
			'WP资源下载管理',
			'manage_options',
			self::$name,
			array($this,'admin_settings')
		);
	}

	function admin_settings(){
		$setting_field = self::$optionName;
		$option_name = self::$optionName;
		$opt = self::cnf();
		include_once( DLIPP_PATH.'/settings.php' );
	}

	function admin_init(){
		register_setting(  self::$optionName,self::$optionName );
	}


	function addMataBox() {
        $screens = array( 'post');
        foreach ($screens as $screen) {
            add_meta_box(
                'wbolt_meta_box_download_info_dlipp',
                '下载设置',
                array($this,'renderMataBox'),
                $screen
            );

        }
	}

	function renderMataBox( $post ) {

        wp_enqueue_style('wbp-admin-style-dlipp', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/wbp_admin_dlipp.css', array(), DLIPP_VERSION);
        wp_enqueue_script('wbp-admin-js-dlipp', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/wbp_admin_dlipp.js', array(), DLIPP_VERSION,true);

        $meta_value = self::meta_values($post->ID);
        $meta_value_vk_price = get_post_meta($post->ID, 'vk_price', true);

        //原有的下载方式字段wb_dl_type 改为下载开关
		$wb_dipp_switch = $meta_value['wb_dl_type'];

		$dl_mode = $meta_value['wb_dl_mode'];

		$wpvk_install = file_exists(WP_CONTENT_DIR.'/plugins/wp-vk/index.php');
		if($wpvk_install){
			$wpvk_active = class_exists('WP_VK');
		}

        include DLIPP_PATH.'/tpl/meta_box.php';
	}

	function saveMataData( $post_id ) {

        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if(isset($_POST['wb_dl_mode']) && !isset($_POST['wb_dl_type'])){
            $_POST['wb_dl_type'] = 0;
            //$_POST['wb_dl_type'] = $_POST['wb_dl_type'] == null ? 0 : 1;//wb_dl_mode
        }
        if( isset($_POST['wb_down_vk_price']) ){
           update_post_meta($post_id,'vk_price', $_POST['wb_down_vk_price']);
        }

        foreach(self::$meta_fields as $field){
            if(!isset($_POST[$field]))continue;
            $value = trim($_POST[$field]);
            $value = sanitize_text_field( $value );
            update_post_meta($post_id, $field, $value);
        }


	}

	/**
	 * ajax
	 */
	public static function wp_ajax_wb_dlipp(){
		if (!current_user_can('manage_options')) {
			exit();
		}

        switch ($_REQUEST['do']) {
	        case 'chk_ver':
		        $http = wp_remote_get( 'https://www.wbolt.com/wb-api/v1/themes/checkver?code=dip&ver=' . DLIPP_VERSION . '&chk=1',array('sslverify' => false,'headers'=>array('referer'=>home_url()),));

		        if ( wp_remote_retrieve_response_code( $http ) == 200 ) {
			        echo wp_remote_retrieve_body( $http );
		        }

		        exit();
		        break;
        }

        exit();
	}
}
