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

class DLIP_DownLoadFront{

    public $post_id = 0;

	public function __construct(){

//		if(!is_single()) return;

	    //是否开启下载
	    $switch = DLIP_DownLoadAdmin::cnf('switch',0);

		if($switch){
			add_filter('the_content',array($this,'the_content'),40);
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_head' ), 50 );
			add_action( 'wp_footer', array( $this, 'stickyHtml' ), 50 );
			add_action('wp_ajax_wb_dlipp_front', array($this,'wb_ajax'));
			add_action('wp_ajax_nopriv_wb_dlipp_front', array($this,'wb_ajax'));

			add_action('widgets_init',array($this,'widgets_init'));
		}

		add_filter('wb_dlip_html', array(__CLASS__,'downHtml'));
	}
	public static function coffin_set_cookies( $comment, $user, $cookies_consent){
				$cookies_consent = true;
				wp_set_comment_cookies($comment, $user, $cookies_consent);
			}
	public static  function wb_body_classes($classes) {
        $classes[] = 'wb-with-sticky-btm';
        return $classes;
    }
	public function wp_head(){
		$post_id = get_the_ID();
		$meta_value = DLIP_DownLoadAdmin::meta_values($post_id);
		$with_dl_info = isset($meta_value['wb_dl_type']) && $meta_value['wb_dl_type'] ? 1 : 0;

        if ( is_single() && $with_dl_info ) {
	        if(!wp_script_is( 'jquery', 'enqueued' )){
		        wp_enqueue_script('jquery');
	        }

	        if (!function_exists('wbolt_header')) {
		        wp_enqueue_style('wbui-css', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/wbui/assets/wbui.css', null, DLIPP_VERSION);
		        wp_enqueue_script('wbui-js', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/wbui/wbui.js', null, DLIPP_VERSION,true);
	        }
	        echo "<link rel='stylesheet' id='wbs-style-dlipp-css'  href='".plugin_dir_url(DLIPP_BASE_FILE) . "assets/wbp_dlipp.css?v=".DLIPP_VERSION."' type='text/css' media='all' />";

//	        wp_enqueue_script('clipboard-js', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/clipboard/clipboard.min.js', array('jquery'), DLIPP_VERSION,true);
	        wp_enqueue_script('wbs-front-dlipp', plugin_dir_url(DLIPP_BASE_FILE) . 'assets/wbp_front.js', array('jquery'), DLIPP_VERSION,true);

            $wbdl_inline_script = 'var wb_dlipp_config = {ajax_url:"'. admin_url('/admin-ajax.php') .'", pid: ' . get_the_ID() .', dir: "'.plugin_dir_url(DLIPP_BASE_FILE).'", ver: "'.DLIPP_VERSION.'"};';

            wp_add_inline_script('wbs-front-dlipp',$wbdl_inline_script,'before');

	        //若开启评论下载
	        $cur_post_need_comment = isset($meta_value['wb_dl_mode']) && $meta_value['wb_dl_mode'] == 1 ? 1 : 0;
	        $need_comment = DLIP_DownLoadAdmin::cnf('need_comment',0);
	        if($need_comment && $cur_post_need_comment){
		        add_filter('comment_form_field_cookies','__return_false');
		        add_action('set_comment_cookies', array(__CLASS__,'coffin_set_cookies'),10,3);
	        }

	        $sticky_mode = DLIP_DownLoadAdmin::cnf('sticky_mode',0);
	        if($sticky_mode == 2){
		        add_filter('body_class',array(__CLASS__,'wb_body_classes'));
	        }
        }
    }

    public function downHtml($with_title = true){

	    $post_id = get_the_ID();
	    $html = '';

	    do{
	        if(!$post_id){
	            break;
            }

            $this->post_id = $post_id;

            $meta_value = DLIP_DownLoadAdmin::meta_values($post_id);

	        //关闭资源
            if(!$meta_value['wb_dl_type']){
	            break;
            }

            if(!$meta_value['wb_down_url'] && !$meta_value['wb_down_local_url'] && !$meta_value['wb_down_url_ct']){
	            break;
            }

            // 'wb_dl_type','wb_dl_mode', 'wb_down_local_url', 'wb_down_url_ct', 'wb_down_url','wb_down_pwd'
            $dl_info = array();
		    if(isset($meta_value['wb_down_url']) && $meta_value['wb_down_url']){
		    	$bdpsw = isset($meta_value['wb_down_pwd']) && $meta_value['wb_down_pwd'] ? $meta_value['wb_down_pwd'] : '';
			    $dl_info['baidu'] = array(
			    	'name'=>'百度网盘下载',
				    'url' => $meta_value['wb_down_url'],
				    'psw' => $bdpsw
			    );
		    }

		    if(isset($meta_value['wb_down_local_url']) && $meta_value['wb_down_local_url']){
			    $dl_info['local'] = array(
			    	'name'=>'本地直接下载',
				    'url' => $meta_value['wb_down_local_url']
			    );
		    }

		    if(isset($meta_value['wb_down_url_ct']) && $meta_value['wb_down_url_ct']){
			    $dl_info['ct'] = array(
			    	'name'=>'城通网盘下载',
				    'url' => $meta_value['wb_down_url_ct']
			    );
		    }

		    if(isset($meta_value['wb_down_url_magnet']) && $meta_value['wb_down_url_magnet']){
			    $dl_info['magnet'] = array(
			    	'name'=>'磁力链接',
				    'url' => $meta_value['wb_down_url_magnet']
			    );
		    }

		    if(isset($meta_value['wb_down_url_xunlei']) && $meta_value['wb_down_url_xunlei']){
			    $dl_info['xunlei'] = array(
			    	'name'=>'迅雷下载',
				    'url' => $meta_value['wb_down_url_xunlei']
			    );
		    }

		    $display_count = DLIP_DownLoadAdmin::cnf('display_count',0);
		    $btn_align = DLIP_DownLoadAdmin::cnf('btn_align',0);
		    $remark_info = DLIP_DownLoadAdmin::cnf('remark', '');

		    $need_login = DLIP_DownLoadAdmin::cnf('need_member',0);
		    $is_login = is_user_logged_in();
		    $need_comment = isset($meta_value['wb_dl_mode']) && $meta_value['wb_dl_mode'] == 1 ? 1 : 0;
		    $is_comment = $this->wb_is_comment($post_id);

		    $need_pay = isset($meta_value['wb_dl_mode']) && $meta_value['wb_dl_mode'] == 2 ? 1 : 0;
		    $need_pay = current_user_can( 'edit_post', $post_id ) ? 0 : $need_pay;
		    $pay_tips_content = '该资源需支付后下载，当前出了点小问题，请稍后再试或联系站长。';
		    $is_buy = false;
		    if( class_exists('WP_VK') && class_exists('WP_VK_Order') && WP_VK_Order::post_price($post_id) ){
		    	$attr = array('tpl'=>'此资源需支付%price%后下载');
			    $pay_tips_content = WP_VK::sc_vk_content($attr);
			    $is_buy = WP_VK_Order::is_buy($post_id);
		    }

		    ob_start();
		    if($with_title){
			    include DLIPP_PATH.'/tpl/download.php';
		    }else{
		    	include DLIPP_PATH.'/tpl/widget_download.php';
		    }
		    $html = ob_get_clean();

        }while(false);

	    return $html;
    }

    public function stickyHtml(){

	    if(!is_single()){
	        return;
        }
	    $post_id = $this->post_id;

        //$html = '';

        do{
            if(!$post_id){
                break;
            }
            $meta_value = DLIP_DownLoadAdmin::meta_values($post_id);

            //关闭资源
            if(!$meta_value['wb_dl_type']){
                break;
            }

//	        $need_login = DLIP_DownLoadAdmin::cnf('need_member',0);
//	        $is_login = is_user_logged_in();
//	        $need_comment = isset($meta_value['wb_dl_mode']) && $meta_value['wb_dl_mode'] == 1 ? 1 : 0;
//	        $is_comment = $this->wb_is_comment($post_id);

            $sticky_mode = DLIP_DownLoadAdmin::cnf('sticky_mode',0);
            include DLIPP_PATH.'/tpl/sticky.php';

        }while(false);

	    //echo $html;
    }

	public static function wb_is_comment($post_id){
		$email = null;
		$user_ID = wp_get_current_user()->ID;
		$user_name = wp_get_current_user()->display_name;

		if ($user_ID > 0) {
			$email = get_userdata($user_ID)->user_email;
		} else if (isset($_COOKIE['comment_author_email_' . COOKIEHASH])) {
			$email = str_replace('%40', '@', $_COOKIE['comment_author_email_' . COOKIEHASH]);
		} else {
			return false;
		}
		if (empty($email) && empty($user_name)) {
			return false;
		}

		global $wpdb;
		$pid = $post_id;
		$query = "SELECT `comment_ID` FROM {$wpdb->comments} WHERE `comment_post_ID`={$pid} and `comment_approved`='1' and (`comment_author_email`='{$email}' or `comment_author`='{$user_name}') LIMIT 1";
		if ($wpdb->get_results($query)) {
			return true;
		}
	}

	public function the_content($content){
	  if(is_single()) {
		$content .= $this->downHtml();

	  }

	  return $content;
	}

	public static function wb_ajax(){
		$post_id = intval($_POST['pid']);
		$dl_type = trim($_POST['rid']);

		$meta_value = DLIP_DownLoadAdmin::meta_values($post_id);
		$need_login = DLIP_DownLoadAdmin::cnf('need_member',0);
		$is_login = is_user_logged_in();
		$need_comment = isset($meta_value['wb_dl_mode']) && $meta_value['wb_dl_mode'] == 1 ? 1 : 0;


		$ret = array('code'=>0, 'is_login'=>is_user_logged_in(), 'data'=>array());

		do{
			if(!$post_id){
				$ret['code'] = 1;
				break;
			}
			if($need_login && !$is_login){
				$ret['code'] = 2;
				break;
			}
            $is_comment = 0;
			if($need_comment){

                $is_comment = self::wb_is_comment($post_id);
            }
			if($need_comment && !$is_comment){
				$ret['code'] = 3;
				break;
			}

			//'wb_dl_type','wb_dl_mode', 'wb_down_local_url', 'wb_down_url_ct', 'wb_down_url','wb_down_pwd'
			switch ($dl_type){
				case 'local':
					$ret['data']['url'] = isset($meta_value['wb_down_local_url']) && $meta_value['wb_down_local_url'] ? $meta_value['wb_down_local_url'] : '';
					$ret['data']['pwd'] = '';
					break;
				case 'baidu':
					$ret['data']['url'] = isset($meta_value['wb_down_url']) && $meta_value['wb_down_url'] ? $meta_value['wb_down_url'] : '';
					$ret['data']['pwd'] = isset($meta_value['wb_down_pwd']) && $meta_value['wb_down_pwd'] ? $meta_value['wb_down_pwd'] : '';
					break;
				case 'ct':
					$ret['data']['url'] = isset($meta_value['wb_down_url_ct']) && $meta_value['wb_down_url_ct'] ? $meta_value['wb_down_url_ct'] : '';
					$ret['data']['pwd'] = '';
					break;
				case 'xunlei':
					$ret['data']['url'] = isset($meta_value['wb_down_url_xunlei']) && $meta_value['wb_down_url_xunlei'] ? $meta_value['wb_down_url_xunlei'] : '';
					$ret['data']['pwd'] = '';
					break;
				case 'magnet':
					$ret['data']['url'] = isset($meta_value['wb_down_url_magnet']) && $meta_value['wb_down_url_magnet'] ? $meta_value['wb_down_url_magnet'] : '';
					$ret['data']['pwd'] = '';
					break;
			}

			$val = (int)get_post_meta($post_id,'post_downs',true);
			$val = $val ? $val + 1 : 1;
			update_post_meta($post_id,'post_downs',$val);
			$ret['data']['post_downs'] = $val;

		}while(false);


		header('content-type:text/json;charset=utf-8');
		echo json_encode($ret);
		exit();
	}

	public function widgets_init(){
		wp_register_sidebar_widget('wbolt-download-info', '#下载信息#', array($this,'wb_download_info'),array('description'=>'侧栏展示下载信息，可选'));
	}

	public function wb_download_info(){
		echo $this->downHtml(false);
	}

	public static function getPostMataVal($key,$default=0){
		$postId = get_the_ID();
		if(!$postId)return $default;
		$val = get_post_meta($postId,$key,true);
		return $val?$val:$default;
	}
}