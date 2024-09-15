<?php 
$action = get_query_var('action') ?: 'posts';

if($action == 'code'){
	include get_template_directory().'/user/code.php';
	exit;
}

if(!is_user_logged_in()){
    if(in_array($action, ['login', 'register', 'weixin-login', 'mobile-login', 'lostpassword'])){
        include get_template_directory().'/user/login.php';
    }else{
        wp_redirect(home_url(user_trailingslashit('/user/login')));
    }
}else{
    $current_user   = wp_get_current_user();
    $user_id        = get_current_user_id();

    include get_template_directory().'/user/action.php';
}