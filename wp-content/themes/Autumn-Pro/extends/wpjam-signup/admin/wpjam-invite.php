<?php
$role_options	= [];

foreach(get_editable_roles() as $role_key => $role_details){
	$role_options[$role_key]	= translate_user_role($role_details['name']);
}

wpjam_register_page_action('invite_user', [
	'submit_text'	=> '生成邀请链接',
	'response'		=> 'append',
	'callback'		=> ['WPJAM_Invite', 'ajax_response'],
	'fields'		=> ['role'=>['title'=>'邀请用户角色',	'type'=>'select',	'options'=>$role_options]]
]);

wp_add_inline_style('list-tables', '
	div.response pre {background: #eaeaea; white-space: pre-wrap; word-wrap: break-word; padding:10px;}
	div.response pre code {background: none; margin:0; padding: 0;}');