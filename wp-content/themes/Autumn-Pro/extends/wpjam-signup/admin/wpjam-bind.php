<?php
foreach(wpjam_get_bind_actions() as $bind_name => $bind_action){
	wpjam_register_plugin_page_tab($bind_name, ['title'=>$bind_action['title'],	'function'=>'form',	'form_name'=>$bind_name.'_bind']);
}

add_action('wpjam_plugin_page_load', function($plugin_page, $current_tab){
	if($current_tab){
		$signup_model	= wpjam_get_signup_model($current_tab);

		$user_id		= get_current_user_id();
		$third_user		= $signup_model::get_bind_third_user($user_id);

		$submit_text	= $third_user ? '解除绑定' : '立刻绑定';

		wpjam_register_page_action($current_tab.'_bind', [
			'fields'		=> [$signup_model, 'get_bind_fields'],
			'callback'		=> [$signup_model, 'bind_ajax_response'],
			'submit_text'	=> $submit_text,
			'response'		=> 'redirect'
		]);

		if($current_tab == 'sms'){
			if(!wp_doing_ajax()){
				add_action('admin_footer', [$signup_model, 'send_sms_js']);
			}
		}
	}
		
}, 10, 2);