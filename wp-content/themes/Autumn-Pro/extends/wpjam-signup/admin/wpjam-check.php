<?php
exit;
add_filter('wpjam_signup_tabs', function($tabs){

	$signups	= array_merge(wpjam_get_login_actions(), wpjam_get_bind_actions());

	unset($signups['sms']);

	foreach($signups as $key => $signup){
		$tabs[$key]	= ['title'=>$signup['title'].'绑定检查',	'function'=>'wpjam_signup_check_page'];
	}

	return $tabs;
});

function wpjam_signup_check_page(){
	global $current_tab, $wpdb;

	$signups	= wpjam_get_signups();
	$signup		= $signups[$current_tab];
	$signup_model	= $signup['model'];

	$meta_key		= $signup_model::get_meta_key();
	$weixin_appid	= $signup_model::get_appid();
	$weixin_table	= $wpdb->base_prefix.$current_tab.'_'.$weixin_appid.'_users';

	$results	= $wpdb->get_results("SELECT um.umeta_id, um.user_id as um_user_id, um.meta_key, um.meta_value, wxu.openid, wxu.user_id as wxu_userid FROM {$wpdb->usermeta} um LEFT JOIN {$weixin_table} wxu ON um.meta_value=wxu.openid AND um.meta_key='{$meta_key}' WHERE um.user_id != wxu.user_id ORDER BY um.user_id DESC LIMIT 0, 1000");

	if($results){
		foreach ($results as $result) {
			if($result->wxu_userid == 0){
				WEIXIN_Signup::bind($result->um_user_id, $result->openid);
			}
		}

		$results	= $wpdb->get_results("SELECT um.umeta_id, um.user_id as um_user_id, um.meta_key, um.meta_value, wxu.openid, wxu.user_id as wxu_userid FROM {$wpdb->usermeta} um LEFT JOIN {$weixin_table} wxu ON um.meta_value=wxu.openid WHERE um.meta_key='{$meta_key}' AND um.user_id != wxu.user_id ORDER BY um.user_id DESC LIMIT 0, 1000");

		if($results){
			wpjam_print_R($results);

			return;
		}
	}

	$results	= $wpdb->get_results("SELECT um.umeta_id, um.user_id as um_user_id, um.meta_key, um.meta_value, wxu.openid, wxu.user_id as wxu_userid FROM {$weixin_table} wxu LEFT JOIN {$wpdb->usermeta} um ON um.meta_value=wxu.openid AND um.meta_key='{$meta_key}' AND wxu.user_id > 0 WHERE um.user_id != wxu.user_id ORDER BY wxu.user_id DESC LIMIT 0, 1000");

	if($results){
		wpjam_print_R($results);

		return;
	}

	//重复用户
	$results	= $wpdb->get_results("SELECT user_id, meta_key, meta_value, count(*) as count FROM {$wpdb->usermeta} WHERE meta_key='{$meta_key}' GROUP BY meta_value HAVING count(*) > 1 ORDER BY count DESC LIMIT 0, 100");

	if($results){
		wpjam_print_R($results);
	}

	echo '<p>暂无问题</p>';
}