<?php
$data  = $_POST;
wpjam_include_sms_provider('mysubmail');
wpjam_include_sms_provider('mysubmail', 'international');
$check = WPJAM_MySubMail::event_verify($data);

if (!$check) exit(); 
/**
 * @see https://www.mysubmail.com/documents/GHVPT
 */
switch ($data['events'] ?? '') {
    case 'request': //请求 
        do_action('wpjam_mysubmail_request_event', $data);
        break;
    case 'delievered': //短信发送成功
        do_action('wpjam_mysubmail_delievered_event', $data);
        break;
    case 'dropped': //短信发送失败
        do_action('wpjam_mysubmail_dropped_event', $data);
        break;
    case 'sending': //短信正在发送中
        do_action('wpjam_mysubmail_sending_event', $data);
        break;
    case 'mo': //短信上行接口推送
        do_action('wpjam_mysubmail_mo_event', $data);
        break;
    case 'template_accept': //短信模板审核通过
        do_action('wpjam_mysubmail_template_accept_event', $data);
        break;
    case 'template_reject': //短信上行接口推送
        do_action('wpjam_mysubmail_template_reject_event', $data);
        break;
    default:
        exit();
        break;
}

wpjam_send_json(['data' => 'OK']);