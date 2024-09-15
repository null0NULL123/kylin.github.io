<?php 
class WPJAM_Comment_Setting{
	use WPJAM_Setting_Trait;

	private function __construct(){
		$this->init('wpjam_comments');
	}

	public static function get_fields(){
		$post_type	= wpjam_get_plugin_page_setting('post_type');
		
		$fields		= [];

		foreach(WPJAM_Comment_Feature::get_registereds() as $feature => $feature_obj){
			if(method_exists($feature_obj->model, 'get_fields')){
				$feature_fields	= call_user_func([$feature_obj->model, 'get_fields'], 'setting', $post_type);

				foreach($feature_fields as $field_key => $field){
					if(strpos($field_key, 'reply') === false){
						$field_key	= 'comment_'.$field_key;
					}

					$fields[$post_type.'_'.$field_key]	= $field;
				}
			}
		}

		return $fields;
	}
}

function wpjam_comment_get_setting($name, $default=null){
	return WPJAM_Comment_Setting::get_instance()->get_setting($name, $default);
}

function wpjam_register_comment_type($name, $object_type, $args=[]){
	if(!get_post_type_object($object_type)){
		return null;
	}

	if($object = WPJAM_Comment_Type::get($name)){
		return $object->register_for_object_type($object_type, $args);
	}else{
		$model	= wpjam_array_pull($args, 'model');

		if(!$model || !class_exists($model)){
			return null;
		}

		$args['plural']			= $args['plural'] ?? $name.'s';
		$args['model']			= new $model($name, $args);
		$args['object_type']	= $object_type;

		return WPJAM_Comment_Type::register($name, $args);
	}
}

function wpjam_register_comment_type_for_object_type($name, $object_type, $args=[]){
	if(!get_post_type_object($object_type)){
		return false;
	}

	if($object = WPJAM_Comment_Type::get($name)){
		return $object->register_for_object_type($object_type, $args);
	}

	return false;
}

function wpjam_get_comment_types($args=[], $output='names', $operator='and'){
	return WPJAM_Comment_Type::get_by($args, $output, $operator);
}

function wpjam_get_comment_type_object($name){
	return WPJAM_Comment_Type::get($name);
}

function wpjam_is_object_in_comment_type($object_type, $comment_type){
	$ct_obj	= wpjam_get_comment_type_object($comment_type);

	if(!$ct_obj || !in_array($object_type, $ct_obj->object_type)){
		return false;
	}

	return true;
}

function wpjam_register_comment_feature($name, $args=[]){
	if($object = wpjam_get_comment_feature($name)){
		return $object;
	}

	return WPJAM_Comment_Feature::register($name, $args);
}

function wpjam_get_comment_feature($name){
	return WPJAM_Comment_Feature::get($name);
}

function wpjam_comment_supports($id, $feature){
	if($comment_obj = WPJAM_Comment::get_instance($id)){
		if(wpjam_comment_type_supports($comment_obj->type, $comment_obj->post_type, $feature)){
			return true;
		}

		return new WP_Error($comment_obj->type.'_'.$feature.'_not_supported', '不支持 '.$comment_obj->type.' '.$feature);
	}

	return new WP_Error('invalid_comment', '无效的评论');
}

function wpjam_comment_type_supports($comment_type, $post_type, $feature){
	if($ct_obj = wpjam_get_comment_type_object($comment_type)){
		return $ct_obj->supports($post_type, $feature);
	}

	return false;
}

function wpjam_comment_query_supports($query, $feature){
	if($query->query_vars['post_id'] && empty($query->query_vars['parent__in'])){
		$type		= $query->query_vars['type'] ?: 'comment';
		$post_id	= $query->query_vars['post_id'];

		return wpjam_comment_type_supports($type, get_post_type($post_id), $feature);
	}

	return false;
}

function wpjam_get_comment_query($args){
	return WPJAM_Comment::get_query($args);
}

function wpjam_get_comments($args){
	return WPJAM_Comment::get_comments($args);
}

function wpjam_get_comment($id, $args=[]){
	if($object = WPJAM_Comment::get_instance($id)){
		return $object->parse_for_json($args);
	}else{
		return [];
	}
}

function wpjam_get_current_commenter_emails(){
	$emails		= [];
	
	if(did_action('wpjam_account_loaded')){
		if($account_obj = wpjam_get_current_account(false)){
			$emails	= $account_obj->get_user_emails();
		}
	}

	$commenter	= wp_get_current_commenter();

	if(!empty($commenter['comment_author_email'])){
		$emails[]	= $commenter['comment_author_email'];
	}

	return array_unique($emails);
}

function wpjam_delete_comment($id, $force_delete=false){
	return WPJAM_Comment::delete($id, $force_delete);
}

function wpjam_add_post_comment($post_id, $data){
	if(!$post_id || !get_post($post_id)){
		return new WP_Error('invalid_post_id', '无效的 post_id');
	}

	$type	= $data['type'] = empty($data['type']) ? 'comment': $data['type'];
	$ct_obj	= wpjam_get_comment_type_object($type);

	if(!$ct_obj || !in_array(get_post_type($post_id), $ct_obj->object_type)){
		return new WP_Error($type.'_not_supported', '操作不支持');
	}

	return $ct_obj->add_comment($post_id, $data);
}

function wpjam_post_action($post_id, $action='fav', $args=[]){
	if(!$post_id || !get_post($post_id)){
		return new WP_Error('invalid_post_id', '无效的 post_id');
	}

	if(strpos($action, 'un') === 0){
		$type	= str_replace('un', '', $action);
		$status	= 0;
	}else{
		$type	= $action;
		$status	= 1;
	}

	$ct_obj		= wpjam_get_comment_type_object($type);

	if(!$ct_obj || !$ct_obj->action || !in_array(get_post_type($post_id), $ct_obj->object_type)){
		return new WP_Error($type.'_not_supported', '操作不支持');
	}

	return $ct_obj->action($post_id, $status);
}

function wpjam_did_post_action($post_id, $action='fav'){
	if(!$post_id || !get_post($post_id)){
		return new WP_Error('invalid_post_id', '无效的 post_id');
	}

	$ct_obj		= wpjam_get_comment_type_object($action);

	if(!$ct_obj || !$ct_obj->action || !in_array(get_post_type($post_id), $ct_obj->object_type)){
		return new WP_Error($action.'_not_supported', '操作不支持');
	}

	return $ct_obj->is_did($post_id);
}

function wpjam_get_post_action_count($post_id, $action){
	$ct_obj	= wpjam_get_comment_type_object($action);

	return $ct_obj->get_count($post_id);
}

function wpjam_get_post_comments($post_id, $type, $parse_for_json=true){
	$ct_obj	= wpjam_get_comment_type_object($type);

	return $ct_obj->get_comments($post_id, $parse_for_json);
}

function wpjam_get_post_actions($post_id, $type, $parse_for_json=true){
	return wpjam_get_post_comments($post_id, $type, $parse_for_json);
}




