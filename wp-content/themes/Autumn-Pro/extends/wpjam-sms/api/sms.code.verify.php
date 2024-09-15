<?php
$phone	= wpjam_get_parameter('phone',	array('method'=>'POST', 'required'=> true));
$code	= wpjam_get_parameter('code',	array('method'=>'POST', 'required'=> true));

$result	= wpjam_verify_sms($phone, $code);

if(is_wp_error($result)){
	wpjam_send_json($result);
}elseif($result){
	do_action('wpjam_sms_verified', $phone);
}