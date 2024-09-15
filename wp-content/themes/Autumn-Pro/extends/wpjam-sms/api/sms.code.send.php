<?php
$phone	= wpjam_get_parameter('phone',	array('method'=>'POST', 'required'=> true));

do_action('wpjam_pre_send_sms', $phone);

$response = wpjam_send_sms($phone);

if(is_wp_error($response)){
	wpjam_send_json($response);
}

wpjam_send_json(array(
	'errmsg'	=> '手机验证码已经成功发送给手机：'.$phone,
));
