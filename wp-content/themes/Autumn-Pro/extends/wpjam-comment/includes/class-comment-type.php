<?php
class WPJAM_Comment_Type{
	use WPJAM_Type_Trait;

	public $object_type = [];
	private $features 	= [];

	public function __construct($name, $args=[]){
		$this->name	= $name;
		$this->args	= wp_parse_args($args, [
			'label'		=> '',
			'plural'	=> '',
			'model'		=> 'WPJAM_Post_Comment',
			'action'	=> false
		]);

		if($object_type	= wpjam_array_pull($args, 'object_type')){
			if($supports = wpjam_array_pull($args, 'supports')){
				$this->add_support($object_type, $supports);
			}

			$this->object_type[]	= $object_type;
		}
	}

	public function __call($method, $args){
		if(method_exists($this->model, $method)){
			return call_user_func_array([$this->model, $method], $args);
		}

		return null;
	}

	public function register_for_object_type($object_type, $args=[]){
		if(!in_array($object_type, $this->object_type, true)){
			$this->object_type[] = $object_type;
		}

		if($supports = wpjam_array_pull($args, 'supports')){
			$this->add_support($object_type, $supports);
		}

		return $this;
	}

	public function add_support($object_type, $feature, $value=true){
		if(is_array($feature)){
			$supports	= $feature;
		}else{
			$supports	= [$feature=>$value];
		}

		foreach($supports as $feature => $value){
			if(is_numeric($feature)){
				$feature	= $value;
				$value		= true;
			}

			if($feature_obj = wpjam_get_comment_feature($feature)){
				$this->features[$object_type][$feature]	= $value;
			}
		}
	}

	public function get_supports($object_type){
		$supports	= $this->features[$object_type] ?? [];

		return array_filter($supports);
	}

	public function supports($object_type, $feature){
		return $this->features[$object_type][$feature] ?? false;
	}

	public function get_fields($action_key='', $args=[]){
		$fields	= call_user_func([$this->model, 'get_fields'], $action_key, $args);

		if($action_key == 'add'){
			foreach($this->get_meta_fields($args['post_type']) as $meta_key => $meta_field){
				$meta_field['name']	= 'meta['.$meta_key.']';

				$fields[$meta_key]	= $meta_field;
			}
		}

		return $fields;
	}

	public function get_meta_fields($object_type){
		$meta_fields	= [];

		foreach($this->get_supports($object_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'get_fields')){
				$meta_fields	= array_merge($meta_fields, call_user_func($callback, 'meta', $object_type));
			}
		}

		return $meta_fields;
	}

	public function get_action_attr($status){
		if(!$this->class || !$this->icon){
			return false;
		}

		if($status){
			$action	= 'un'.$this->name;
			$class	= $this->class[0];
			$icon	= $this->icon[0];
		}else{
			$action	= $this->name;
			$class	= $this->class[1];
			$icon	= $this->icon[1];
		}

		$class	= 'post-action post-'.$this->name.' '.$class;

		return ['action'=>$action, 'class'=>$class, 'icon'=>$icon];
	}

	public function get_feature_callback($feature, $method){
		$feature_obj	= wpjam_get_comment_feature($feature);
		$feature_model	= $feature_obj ? $feature_obj->model : null;

		if($feature_model && method_exists($feature_model, $method)){
			return [$feature_model, $method];
		}

		return false;
	}

	public function render_item($item){
		$item['comment']	= $item['comment'] ?? '';
		$item['id']			= $item['comment_ID'];

		$item		= call_user_func([$this->model, 'render_item'], $item);
		$post_type	= get_comment($item['id'])->post_type;

		foreach($this->get_supports($post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'render_item')){
				$item	= call_user_func($callback, $item, $support);
			}
		}

		return $item;
	}

	public function register_api($json, $post_type){
		foreach($this->get_supports($post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'register_api')){
				if($json_obj = call_user_func($callback, $json, $this->name, $post_type, $this)){
					return $json_obj;
				}
			}
		}
	}

	public function api_comment($post_id, $args=[]){
		$args['meta']	= [];

		foreach($this->get_meta_fields(get_post_type($post_id)) as $meta_key => $meta_field){
			$required	= !empty($meta_field['required']) || in_array('required', $meta_field, true);
			$meta_value = wpjam_get_parameter($meta_key, ['method'=>'POST', 'required'=>$required]);
			$meta_value	= wpjam_validate_field_value(array_merge($meta_field, ['key'=>$meta_key]), $meta_value);

			if(is_wp_error($meta_value)){
				return $meta_value;
			}

			if($meta_value){
				$args['meta'][$meta_key]	= $meta_value;
			}
		}

		return call_user_func([$this->model, 'api_comment'], $post_id, $args);
	}

	public function on_pre_get_comments($query){
		$post_type	= get_post_type($query->query_vars['post_id']);

		foreach($this->get_supports($post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'on_pre_get_comments')){
				call_user_func($callback, $query, $support);
			}
		}
	}

	public function filter_comments_clauses($clauses, $query){
		$post_type	= get_post_type($query->query_vars['post_id']);

		foreach($this->get_supports($post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'filter_comments_clauses')){
				$clauses	= call_user_func($callback, $clauses, $query, $support);
			}
		}

		return $clauses;
	}

	public function filter_post_json($post_json, $post_id){
		$post_type	= $post_json['post_type'];

		foreach($this->get_supports($post_type) as $feature => $support){
			if(strpos($feature, 'reply') === false){
				$feature_key	= $this->name.'_'.$feature;
			}else{
				$feature_key	= $feature;
			}

			$post_json[$feature_key]	= is_numeric($support) ? (bool)$support : $support;;

			if($callback = $this->get_feature_callback($feature, 'filter_post_json')){
				$post_json	= call_user_func($callback, $post_json, $post_id);
			}
		}

		if(method_exists($this->model, 'filter_post_json')){
			$post_json = call_user_func([$this->model, 'filter_post_json'], $post_json, $post_id);
		}

		return $post_json;
	}

	public function filter_comment_json($comment_json, $comment_id, $args){
		$post_type	= get_comment($comment_id)->post_type;

		foreach($this->get_supports($post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'filter_comment_json')){
				$comment_json	= call_user_func($callback, $comment_json, $comment_id, $args, $support);
			}
		}

		if(method_exists($this->model, 'filter_comment_json')){
			$comment_json = call_user_func([$this->model, 'filter_comment_json'], $comment_json, $comment_id, $args);
		}

		return $comment_json;
	}

	public function filter_pre_comment_approved($approved, $comment_data){
		if($post_type = get_post_type($comment_data['comment_post_ID'])){
			foreach($this->get_supports($post_type) as $feature => $support){
				if($callback = $this->get_feature_callback($feature, 'filter_pre_comment_approved')){
					$approved	= call_user_func($callback, $approved, $comment_data, $support);

					if(is_wp_error($approved) || $approved == 0){
						return $approved;
					}
				}
			}
		}	

		return $approved;
	}

	public function filter_the_comments($comments, $query){
		$post_type	= get_post_type($query->query_vars['post_id']);

		foreach($this->get_supports($post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'filter_the_comments')){
				$comments	= call_user_func($callback, $comments, $query, $support);
			}
		}

		return $comments;
	}

	public function filter_comment_text($text, $comment){
		foreach($this->get_supports($comment->post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'filter_comment_text')){
				$text	= call_user_func($callback, $text, $comment, $support);
			}
		}

		return $text;
	}

	public function on_comments_page_load($post_type){
		foreach($this->get_supports($post_type) as $feature => $support){
			if($callback = $this->get_feature_callback($feature, 'on_comments_page_load')){
				call_user_func($callback, $this->name, $post_type);
			}
		}

		// foreach($this->get_meta_fields($post_type) as $meta_key => $meta_field){
		// 	if(!empty($meta_field['show_admin_column'])){
		// 		if($style = wpjam_array_pull($meta_field, 'style')){
		// 			wp_add_inline_style('list-tables', "\n".$style);
		// 		}

		// 		if($meta_key == 'source_type'){
		// 			trigger_error('wpjam_register_list_table_column:source_type');
		// 		}

		// 		wpjam_register_list_table_column($meta_key, $meta_field);
		// 	}
		// }
	}
}

class WPJAM_Comment_Feature{
	use WPJAM_Type_Trait;
}