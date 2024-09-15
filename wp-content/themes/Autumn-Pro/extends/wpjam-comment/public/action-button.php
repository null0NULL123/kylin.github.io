<?php
class WPJAM_Post_Action_Button{
	public static function get_button($post_id, $type='like', $title=true){
		$ct_obj	= wpjam_get_comment_type_object($type);

		if(!$post_id || !get_post($post_id) || !$ct_obj || !in_array(get_post_type($post_id), $ct_obj->object_type)){
			return '';
		}

		$status	= $ct_obj->is_did($post_id, $wp_error=false);
		$attr	= $ct_obj->get_action_attr($status);

		if(!$attr){
			return '';
		}

		$dashicon	= $attr['icon'] ? 'dashicons-'.$attr['icon'] : '';
		$dashicon	= '<span class="dashicons '.$dashicon.'"></span>';

		if($title === true){
			$title	= $dashicon.$ct_obj->label;
		}elseif(empty($title)){
			$title	= $dashicon;
		}

		$title		.= ' <span class="post-action-count">'.$ct_obj->get_count($post_id).'</span>';
		$data		= ['post_id'=>$post_id, 'action'=>$attr['action']];
		$data_attr	= wpjam_get_ajax_data_attr('post-action', $data);

		return '<a href="javascript:;" class="'.$attr['class'].'" '.$data_attr.'>'.$title.'</a>';
	}

	public static function ajax_callback(){
		$post_id	= (int)wpjam_get_data_parameter('post_id');
		$action		= wpjam_get_data_parameter('action', ['sanitize_callback'=>'sanitize_key']);
		$result		= wpjam_post_action($post_id, $action);

		if(is_wp_error($result)){
			return $result;
		}

		if(strpos($action, 'un') === 0){
			$type	= str_replace('un', '', $action);
			$status	= 0;
		}else{
			$type	= $action;
			$status	= 1;
		}

		$ct_obj	= wpjam_get_comment_type_object($type);
		$attr	= $ct_obj->get_action_attr($status);

		return [
			'count'		=> wpjam_get_post_action_count($post_id, $type),
			'data'		=> http_build_query(['post_id'=>$post_id, 'action'=>$attr['action']]),
			'class'		=> $attr['class'],
			'icon'		=> $attr['icon'],
			'commenter'	=> wp_get_current_commenter()
		];
	}

	public static function on_enqueue_scripts(){
		wpjam_ajax_enqueue_scripts();

		$script	= <<<'EOT'
jQuery(function($){
	$("body").on("click", ".post-action", function(e){
		e.preventDefault();

		$(this).wpjam_action(function(data){
			if(data.errcode != 0){
				alert(data.errmsg);
			}else{
				$(this).removeClass().addClass(data.class);
				$(this).find('span.dashicons').removeClass().addClass('dashicons dashicons-'+data.icon);
				$(this).data('data', data.data).find('span.post-action-count').html(data.count);

				$('body').trigger('wpjam_post_action_success', data);
			}
		});
	});
});
EOT;
		if(did_action('wpjam_static')){
			wpjam_register_static('wpjam-action-script',	['title'=>'点赞收藏脚本',	'type'=>'script',	'source'=>'value',	'value'=>$script]);
			wpjam_register_static('dashicons',				['title'=>'Dashicons',	'type'=>'style',	'source'=>'file',	'file'=>ABSPATH.WPINC.'/css/dashicons.min.css',	'baseurl'=>home_url(WPINC.'/css/')]);
		}else{
			wp_enqueue_style('dashicons');

			wp_enqueue_script('jquery');
			wp_add_inline_script('jquery', $script);
		}
	}
}

function wpjam_get_post_action_button($post_id, $action='like', $title=true){
	return WPJAM_Post_Action_Button::get_button($post_id, $action, $title);
}

wpjam_register_ajax('post-action',	['nopriv'=>true, 'callback'=>['WPJAM_Post_Action_Button', 'ajax_callback'],	'nonce_keys'=>['post_id']]);

add_action('wp_enqueue_scripts',	['WPJAM_Post_Action_Button', 'on_enqueue_scripts'], 20);

