<?php
class WPJAM_Field{
	private $field = [];

	public  function __construct($field, $key=''){
		foreach($field as $attr => $value){
			if(is_numeric($attr)){
				$attr	= $value = strtolower(trim($value));

				if(!self::is_bool_attr($attr)){
					continue;
				}
			}else{
				$attr	= strtolower(trim($attr));
			
				if(self::is_bool_attr($attr)){
					if(!$value){
						continue;
					}

					$value	= $attr;
				}
			}

			$this->field[$attr]	= $value;
		}

		$this->options	= wp_parse_args($this->options);

		$this->type	= $this->type 	?: ($this->options ? 'select' : 'text');
		$this->key	= $this->key	?: $key;
		$this->name	= $this->name	?: $this->key;
		$this->id	= $this->id		?: $this->key;

		if($this->type == 'mu-text'){
			$this->item_type	= $this->item_type ?: 'text';
			$this->item_object	= new WPJAM_Field(array_merge($this->field, ['type'=>$this->item_type]));
		}elseif(in_array($this->type, ['fieldset', 'mu-fields'])){
			$objects	= [];
			$fields		= $this->fields ?: [];

			foreach($fields as $sub_key => &$sub_field){
				$sub_name = $sub_field['name'] ?? $sub_key;

				$sub_field['key']		= $sub_key;
				$sub_field['sub_field']	= true;

				if($this->type == 'fieldset'){
					if($this->fieldset_type == 'array'){
						$sub_field['name']	= $this->name.$this->generate_sub_name($sub_name);
						$sub_field['key']	= $this->key.'_'.$sub_key;
					}else{
						if(!isset($sub_field['show_in_rest'])){
							$sub_field['show_in_rest']	= $this->show_in_rest;
						}
					}
				}

				$objects[$sub_name]	= new WPJAM_Field($sub_field);
			}

			$this->fields	= $fields;
			$this->objects	= $objects;

			$this->fields_object	= new WPJAM_Fields($objects);
		}
	}

	public  function __get($key){
		if($key == 'field'){
			return $this->field;
		}elseif($key == 'mu_type'){
			return in_array($this->type, ['mu-text', 'mu-fields', 'mu-img', 'mu-image', 'mu-file'], true);
		}elseif($key == 'option_values'){
			if($this->type == 'select'){
				$value	= [];

				foreach($this->options as $opt_value => $opt_title){
					if(is_array($opt_title) && !empty($opt_title['options'])){
						$value		= array_merge($value, array_map('strval', array_keys($opt_title['options'])));
					}else{
						$value[]	= (string)$opt_value;
					}
				}

				return $value;
			}else{
				return array_map('strval', array_keys($this->options));
			}
		}else{
			$value	= $this->field[$key] ?? null;

			if($key == 'max_items'){
				$value	= is_null($value) ? $this->total : $value;
				
				return (int)$value;
			}elseif($key == 'show_in_rest'){
				return is_null($value) ? true : $value;
			}elseif(in_array($key, ['min', 'max', 'minlength', 'maxlength'])){
				return is_numeric($value) ? $value : null;
			}

			return $value;
		}
	}

	public  function __set($key, $value){
		if($key == 'field'){
			$this->field	= $value; 
		}else{
			$this->field[$key]	= $value;
		}
	}

	public  function __isset($key){
		return isset($this->field[$key]);
	}

	public  function __unset($key){
		unset($this->field[$key]);
	}

	public  function is_editable(){
		if(in_array($this->type, ['view', 'br','hr']) 
			|| $this->show_admin_column === 'only'
			|| $this->disabled
			|| $this->readonly
		){
			return false;
		}

		return true;
	}

	public  function validate($value, $validate=true){
		$title		= $this->title ?: $this->key;
		$title		= '「'.$title.'」';
		$required	= $validate ? $this->required : false;

		if(is_null($value) && $required){
			return new WP_Error('value_required', $title.'的值不能为空');
		}

		if($this->validate_callback && is_callable($this->validate_callback)){
			$result	= call_user_func($this->validate_callback, $value);

			if($result === false){
				return $validate ? new WP_Error('invalid_value', $title.'的值无效') : null;
			}elseif(is_wp_error($result)){
				return $validate ? $result : null;
			}
		}

		if($this->type == 'checkbox'){
			if($this->options){
				$value	= is_array($value) ? $value : [];
				$value	= array_values(array_intersect($this->option_values, $value));

				if($value){
					if($this->max_items && count($value) > $this->max_items){
						$value	= array_slice($value, 0, $this->max_items);
					}
				}else{
					if($required){
						$value	= null;
					}
				}
			}else{
				if($validate){
					$value	= (int)$value;
				}
			}
		}elseif($this->mu_type){
			if($value){
				if(!is_array($value)){
					$value	= wpjam_json_decode($value);
				}else{
					$value	= wpjam_array_filter($value, function($item){ return !empty($item) || is_numeric($item); });
				}
			}

			if(empty($value) || is_wp_error($value)){
				$value	= null;
			}else{
				$value	= array_values($value);

				if($this->max_items && count($value) > $this->max_items){
					$value	= array_slice($value, 0, $this->max_items);
				}
			}
		}else{
			if(empty($value) && !is_numeric($value) && $required){
				$value	= null;
			}else{
				if(in_array($this->type, ['radio', 'select'])){
					if(!in_array($value, $this->option_values)){
						$value	= null;
					}
				}elseif(in_array($this->type, ['number', 'range'])){
					if(!is_null($value)){
						if($this->step && ($this->step == 'any' || strpos($this->step, '.'))){
							$value	= (float)$value;
						}else{
							$value	= (int)$value;
						}

						if(isset($this->min) && $value < $this->min){
							$value	= $this->min;
						}

						if(isset($this->max) && $value > $this->max){
							$value	= $this->max;
						}
					}
				}else{
					if(!is_null($value)){
						if($validate){
							if(isset($this->minlength) && mb_strlen($value) < $this->minlength){
								return new WP_Error('invalid_value', $title.'的长度小于最小长度'.$this->minlength);
							}

							if(isset($this->maxlength) && mb_strlen($value) > $this->maxlength){
								return new WP_Error('invalid_value', $title.'的长度大于最大长度'.$this->maxlength);
							}
						}

						if($this->type == 'textarea'){
							$value	= str_replace("\r\n", "\n", $value);
						}
					}
				}
			}
		}

		if($this->data_type && $value){
			$value	= apply_filters('wpjam_data_type_field_value', $value, $this->field);
		}

		if(is_null($value) && $required){
			return new WP_Error('value_required', $title.'的值为空或无效');
		}

		if($this->sanitize_callback && is_callable($this->sanitize_callback)){
			$value	= call_user_func($this->sanitize_callback, $value);
		}

		return $value;
	}

	public  function parse_json_schema(){
		if($this->mu_type){
			$schema	= ['type'=>'array',	'items'=>['type'=>'string',	'format'=>'uri']];
		}else{
			$schema	= ['type'=>'string'];
		}

		if($this->type == 'email'){
			$schema['format']	= 'email';
		}elseif($this->type == 'url'){
			$schema['format']	= 'uri';
		}elseif(in_array($this->type, ['radio', 'select'])){
			$schema['enum']	= $this->option_values;
		}elseif(in_array($this->type, ['number', 'range'])){
			if($this->step && ($this->step == 'any' || strpos($this->step, '.'))){
				$schema['type']	= 'number';
			}else{
				$schema['type']	= 'integer';
			}
		}elseif($this->type == 'checkbox'){
			if($this->options){
				$schema['type']		= 'array';
				$schema['items']	= ['type'=>'string',	'enum'=>$this->option_values];
			}else{
				$schema['type']		= 'boolean';
			}
		}elseif($this->type == 'img'){
			if($this->item_type == 'url'){
				$schema['format']	= 'uri';
			}else{
				$schema['type']		= 'integer';
			}
		}elseif(in_array($this->type, ['fieldset', 'mu-fields'])){
			$properties	= array_map(function($object){ return $object->parse_json_schema(); }, $this->objects);

			if($this->type == 'fieldset'){
				$schema['type']			= 'object';
				$schema['properties']	= $properties;
			}else{
				$schema['items']	= ['type'=>'object',	'properties'=>$properties];
			}
		}elseif($this->type == 'mu-text'){
			$schema['items']	= $this->item_object->parse_json_schema();
		}elseif($this->type == 'mu-img'){
			if($this->item_type != 'url'){
				$schema['items']	= ['type'=>'integer'];
			}
		}

		return $schema;
	}

	public  function prepare($args=[]){
		if(!empty($args['value_callback'])){
			$cb_arg	= $args['id'] ?? $args;
			$value	= call_user_func($args['value_callback'], $this->name, $cb_arg);
		}else{
			$value	= $args['value'] ?? null;
		}

		$default	= $this->value;
		$schema		= $this->parse_json_schema();

		$show_in_rest	= $this->show_in_rest;

		if(is_array($show_in_rest)){
			if(isset($show_in_rest['schema']) && is_array($show_in_rest['schema'])){
				$schema	= array_merge($schema, $show_in_rest['schema']);
			}

			if(isset($show_in_rest['type'])){
				$schema['type']	= $show_in_rest['type'];
			}

			if(isset($show_in_rest['default'])){
				$default	= $show_in_rest['default'];
			}
		}

		if(is_null($value)){
			$value	= $default;
		}

		if((in_array($schema['type'], ['boolean', 'integer', 'number'], true) && $value === '') 
			|| is_wp_error(rest_validate_value_from_schema($value, $schema))
		){
			if($schema['type'] == 'string'){
				$value	= '';
			}elseif($schema['type'] == 'boolean'){
				$value	= false;
			}elseif($schema['type'] == 'integer'){
				$value	= 0;
			}elseif($schema['type'] == 'number'){
				$value	= 0.0;
			}elseif($schema['type'] == 'array'){
				$value	= [];
			}elseif($schema['type'] == 'object'){
				$value	= new stdClass;
			}
		}else{
			$value	= rest_sanitize_value_from_schema($value, $schema);
		}

		if(in_array($this->type, ['image', 'file', 'img'])){
			return $value ? $this->prepare_image($value) : '';
		}elseif(in_array($this->type, ['mu-image', 'mu-file', 'mu-img'])){
			if($value && is_array($value)){
				foreach($value as &$item){
					$item	= $this->prepare_image($item, $field);
				}
			}
		}elseif($this->type == 'mu-text') {
			if($value && is_array($value)){
				$value	= array_filter($value);

				foreach($value as &$item){
					$item	= $this->item_object->prepare(['value'=>$item]);
				}
			}
		}elseif($this->type == 'mu-fields'){
			if($value && is_array($value)){
				foreach($value as &$item){
					$item	= $this->prepare_properties($item);
				}
			}
		}elseif($this->type == 'fieldset'){
			if($value && is_array($value)){
				$value	= $this->prepare_properties($value);
			}
		}else{
			if($value && $this->data_type && $this->parse_required){
				if(in_array($this->data_type, ['video', 'qq-video', 'qq_video'])){
					$value	= wpjam_get_video_mp4($value);
				}elseif($this->data_type == 'post_type'){
					$args	= $this->size ? ['thumbnal_size'=>$this->size] : [];
					$value	= wpjam_get_post($value, $args);
				}elseif($this->data_type == 'taxonomy'){
					$value	= wpjam_get_term($value);
				}
			}
		}

		return $value;
	}

	public  function prepare_image($value){
		if(in_array($this->type,['mu-image', 'mu-file', 'image', 'file'])){
			$item_type	= 'url';
		}else{
			$item_type	= $this->item_type;
		}

		$size	= $this->size ?: [];
		
		if($value && $item_type != 'url'){
			$value	= wp_get_attachment_url($value);
		}
	
		return $value ? wpjam_get_thumbnail($value, $size) : '';
	}

	public  function prepare_properties($value){
		foreach($this->objects as $sub_name => $object){
			$value[$sub_name]	= $value[$sub_name] ?? null;
			$value[$sub_name]	= $object->prepare(['value'=>$value[$sub_name]]);
		}

		return $value;
	}

	public  function callback($args=[]){
		if($this->type == 'fieldset'){
			return $this->fieldset_callback($args);
		}

		if(empty($args['is_add'])){
			$this->value	= $this->parse_value($args);
		}

		if(!empty($args['show_if_keys']) && in_array($this->key, $args['show_if_keys'])){
			$this->show_if_key	= true;
		}

		if(!empty($args['name'])){
			$this->name	= $args['name'].$this->generate_sub_name($this->name);
		}

		return $this->render();
	}

	public  function fieldset_callback($args=[]){
		$html	= $this->title ? '<legend class="screen-reader-text"><span>'.$this->title.'</span></legend>' : '';

		if($objects = $this->objects){
			$group_obj	= new WPJAM_Field_Group();

			foreach($objects as $object){
				if($object->type == 'fieldset'){
					wp_die('fieldset 不允许内嵌 fieldset');
				}else{
					$html	.= $group_obj->render($object->group);
					$html	.= $object->wrap($object->callback($args));
				}
			}
			
			$html	.= $group_obj->reset();

			unset($object);
		}

		if($this->description){
			$html	.= '<p class="description">'.$this->description.'</p>';
		}

		if($this->group){
			$html	= '<div class="field-group">'.$html.'</div>';
		}

		return $html;
	}

	public  function parse_value($args=[]){
		$default	= is_admin() ? $this->value : $this->defaule;
		$cb_arg		= $args['id'] ?? $args;

		$name_obj	= WPJAM_Field_Name::get_instance($this->name);
		$name		= $name_obj->top_name;

		if($value_callback = $this->value_callback){
			if(!is_callable($value_callback)){
				wp_die($this->key.'的 value_callback「'.$value_callback.'」无效');
			}

			$value	= call_user_func($value_callback, $name, $cb_arg);
		}else{
			if(in_array($this->type, ['view', 'br','hr']) && !is_null($default)){
				return $default;
			}

			if(!empty($args['data']) && isset($args['data'][$name])){
				$value	= $args['data'][$name];
			}elseif(!empty($args['value_callback'])){
				$value	= call_user_func($args['value_callback'], $name, $cb_arg);
			}else{
				$value	= null;
			}
		}

		$value	= $name_obj->parse_value($value);

		return is_null($value) ? $default : $value;
	}

	public  function render(){
		if(is_numeric($this->key)){
			trigger_error('Field 的 key「'.$this->key.'」'.'为纯数字');
			return;
		}

		if(is_null($this->value)){
			if($this->type == 'radio' && $this->options){
				$this->value	= current(array_keys($this->options));
			}else{
				$this->value	= '';
			}
		}

		if(is_null($this->class)){
			$field_type	= $this->type == 'mu-text' ? $this->item_type : $this->type;

			if(in_array($field_type, ['textarea', 'editor'])){
				$this->class	= ['large-text'];
			}elseif(in_array($field_type, ['text', 'password', 'url', 'image', 'file', 'mu-file', 'mu-image'], true)){
				$this->class	= ['regular-text'];
			}else{
				$this->class	= [];
			}
		}elseif($this->class){
			if(!is_array($this->class)){
				$this->class	= explode(' ', $this->class);
			}
		}else{
			$this->class	= [];
		}

		if($this->description){
			if($this->type == 'checkbox' && !$this->options){
				$this->description	= '&thinsp;'.$this->description;
			}elseif($this->mu_type
				|| in_array($this->type, ['img', 'color', 'checkbox', 'radio', 'textarea']) 
				|| in_array('large-text', $this->class)
				|| in_array('regular-text', $this->class)
			){
				$this->description	= '<p class="description">'.$this->description.'</p>';
			}else{
				$this->description	= '&ensp;<span class="description">'.$this->description.'</span>';
			}
		}else{
			$this->description	= '';
		}

		if($this->mu_type){
			$html	= $this->render_mu_type();
		}elseif(in_array($this->type, ['view','br'], true)){
			if($options = $this->options){
				$values	= $this->value ? [$this->value] : ['', 0];

				foreach($values as $v){
					if(isset($options[$v])){
						return $options[$v];
					}
				}
			}

			return $this->value;
		}elseif($this->type == 'hr'){
			return '<hr />';
		}elseif($this->type == 'img'){
			if(current_user_can('upload_files')){
				$attr	= [];
				
				$attr['item_type']	= $this->item_type ?: '';
				$attr['uploader_id']= 'wpjam_uploader_'.$this->id;
				$attr['img_style']	= '';

				if($size = wpjam_array_pull($this->field, 'size')){
					$size	= wpjam_parse_size($size);

					list($width, $height)	= wp_constrain_dimensions($size['width'], $size['height'], 600, 600);

					$attr['img_style']	.= $width > 2 ? 'width:'.($width/2).'px;' : '';
					$attr['img_style']	.= $height > 2 ? ' height:'.($height/2).'px;' : '';

					$attr['thumb_args']	= wpjam_get_thumbnail('',$size);
				}else{
					$attr['thumb_args']	= wpjam_get_thumbnail('', 400);
				}

				$class		= '';
				$img_tag	= '';

				if(!empty($this->value)){
					$img_url	= $attr['item_type'] == 'url' ? $this->value : wp_get_attachment_url($this->value);

					if($img_url){
						$class		.= ' has-img';
						$img_tag	= '<img style="'.$attr['img_style'].'" src="'.wpjam_get_thumbnail($img_url, $size).'" alt="" />';
					}
				}

				if(!$this->readonly && !$this->disabled){
					$img_tag	.= self::get_icon('del_img').'<div class="wp-media-buttons"><button type="button" class="button add_media"><span class="wp-media-buttons-icon"></span> 添加图片</button></div>';
				}else{
					$class	.= ' readonly';
				}

				$html	= '<div class="wpjam-img'.$class.'" '.wpjam_data_attribute_string($attr).'>'.$img_tag.'</div>';
				$html	.= ((!$this->readonly && !$this->disabled) ? $this->render_input(['type'=>'hidden']) : '').$this->description;
			}
		}elseif(in_array($this->type, ['file', 'image'], true)){
			if(current_user_can('upload_files')){
				if($this->type == 'image'){
					$btn_name	= '图片';
					$item_type	= 'image';
				}else{
					$btn_name	= '文件';
					$item_type	= $this->item_type ?: '';
				}

				$button	= sprintf('<a class="button" data-uploader_id="%s" data-item_type="%s">选择%s</a>', 'wpjam_uploader_'.$this->id, $item_type, $btn_name);
				$html	= $this->render_input(['type'=>'url', 'description'=>'']).' '.$button;
				$html	= '<div class="wpjam-file">'.$html.'</div>'.$this->description;
			}
		}elseif($this->type == 'editor'){
			$settings	= wpjam_array_pull($this->field, 'settings') ?: [];
			$settings	= wp_parse_args($settings, [
				'tinymce'		=>[
					'wpautop'	=> true,
					'plugins'	=> 'charmap colorpicker compat3x directionality hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
					'toolbar1'	=> 'bold italic underline strikethrough | bullist numlist | blockquote hr | alignleft aligncenter alignright alignjustify | link unlink | wp_adv',
					'toolbar2'	=> 'formatselect forecolor backcolor | pastetext removeformat charmap | outdent indent | undo redo | wp_help'
				],
				'quicktags'		=> true,
				'mediaButtons'	=> true
			]);

			if(wp_doing_ajax()){
				$html	= $this->render_input(['id'=>'editor_'.$this->id, 'data-settings'=>wpjam_json_encode($settings)]);
			}else{
				ob_start();

				wp_editor($this->value, 'editor_'.$this->id, $settings);

				$editor	= ob_get_clean();

				$style	= $this->style ? ' style="'.$this->style.'"' : '';
				$html 	= '<div'.$style.'>'.$editor.'</div>'.$this->description;
			}	
		}else{
			$html	= $this->render_input();

			if($this->list && $this->options){
				$html	.= '<datalist id="'.$this->list.'">';

				foreach($this->options as $opt_value => $opt_title){
					$html	.= '<option label="'.esc_attr($opt_title).'" value="'.esc_attr($opt_value).'" />';
				}

				$html	.= '</datalist>';
			}
		}

		return apply_filters('wpjam_field_html', $html, $this->field);
	}

	private function render_input($args=[], $lable_attr=''){
		$field	= array_merge($this->field, $args);

		$type	= wpjam_array_pull($field, 'type');
		$value	= wpjam_array_pull($field, 'value');
		$sep	= wpjam_array_pull($field, 'sep', '&emsp;');

		$options		= wpjam_array_pull($field, 'options');
		$description	= wpjam_array_pull($field, 'description');

		if($options && in_array($type, ['radio', 'checkbox'])){
			$args['required']	= false;
			$args['options']	= [];

			$wrap_id	= $field['id'].'_options';
			$attr		= 'id="'.esc_attr($wrap_id).'"';

			if($type == 'checkbox'){
				$args['name']	= $field['name'].'[]';

				if($this->max_items){
					$attr	.= 'data-max_items="'.$this->max_items.'"';
				}
			}

			$items	= [];

			if($type == 'checkbox' && !is_array($value) && ($value || is_numeric($value))){
				$value	= [$value];
			}

			foreach($options as $opt_value => $opt_title){
				if($type == 'checkbox'){
					$checked	= is_array($value) && in_array($opt_value, $value);
				}else{
					$checked	= $opt_value == $value;
				}

				$class		= $checked ? ['checked'] : [];
				$opt_title	= $this->parse_option_title($opt_title, $class, $lable_attr);

				$args['id']				= $field['id'].'_'.$opt_value;
				$args['data-wrap_id']	= $wrap_id;
				$args['value']			= $opt_value;
				$args['checked']		= $checked ? 'checked' : false;
				$args['description']	= '&thinsp;'.$opt_title;
				$args['options']		= [];

				$items[]	= $this->render_input($args, $lable_attr);
			}

			return '<div '.$attr.'>'.implode($sep, $items).'</div>'.$description;
		}else{
			if($type == 'checkbox'){
				if(!isset($args['checked'])){
					$field['checked']	= $value == 1 ? 'checked' : false;

					$value	= 1;
				}
			}elseif($type == 'textarea'){
				$field	= wp_parse_args($field, ['rows'=>6, 'cols'=>50]);
			}elseif($type == 'color'){
				$field['class'][]	= 'color';
			}elseif($type == 'editor'){
				$type	= 'textarea';
				$field	= wp_parse_args($field, ['type'=>'textarea', 'rows'=>12, 'cols'=>50]);

				$field['class'][]	= 'wpjam-editor';
			}

			foreach(['readonly', 'disabled'] as $attr_key){
				if(isset($field[$attr_key])){
					$field['class'][]	= $attr_key;
				}
			}

			if(wpjam_array_pull($field, 'show_if_key') 
				|| in_array($type, ['checkbox', 'radio', 'select'], true)){
				$field['class'][]	= 'show-if-key';
			}

			$query_title		= WPJAM_Field_Data_Type::parse_query_title($field, $value);

			$field['data-key']	= wpjam_array_pull($field, 'key');
			$field['class']		= $field['class'] ? implode(' ', array_unique($field['class'])) : '';

			$keys	= ['title','default','description','fields','sortable_column','sub_field','sub_i','parse_required','item_type','group','show_if','show_in_rest','creatable','post_type','taxonomy','value_callback','sanitize_callback','validate_callback','column_callback','show_admin_column','max_items','total'];

			$attr	= [];

			foreach($field as $attr_key => $attr_value){
				if(!in_array($attr_key, $keys)){
					if(is_object($attr_value) || is_array($attr_value)){
						trigger_error($attr_key.' '.var_export($attr_value, true).var_export($field, true));
					}elseif(is_int($attr_value) || $attr_value){
						$attr[]	= $attr_key.'="'.esc_attr($attr_value).'"';
					}
				}
			}

			$attr	= implode(' ', $attr);

			if($type == 'select'){
				$html	= '<select '.$attr.'>'.$this->render_select_options($options, $value).'</select>' .$description;
			}elseif($type == 'textarea'){
				$html	= '<textarea '.$attr.'>'.esc_textarea($value).'</textarea>'.$description;
			}else{
				$attr	.= $type == 'color' ? 'type="text"' : 'type="'.esc_attr($type).'"';
				$html	= '<input value="'.esc_attr($value).'" '.$attr.' />'.$query_title;

				if(($lable_attr || $description) && $type != 'hidden'){
					$lable_attr	.= ' id="label_'.esc_attr($field['id']).'" for="'.esc_attr($field['id']).'"';

					if(in_array($type, ['color'])){
						$html	= '<label '.$lable_attr.'>'.$html.'</label>'.$description;
					}else{
						$html	= '<label '.$lable_attr.'>'.$html.$description.'</label>';
					}
				}
			}

			return $html;
		}
	}

	private function render_mu_type(){
		$max_items		= $this->max_items;
		$max_reached	= false;

		$value			= $this->value;

		if($value || is_numeric($value)){
			if(is_array($value)){
				$value	= wpjam_array_filter($value, function($item){ 
					return !empty($item) || is_numeric($item); 
				});

				if($max_items && count($value) >= $max_items){
					$max_reached	= true;

					$value	= array_slice($value, 0, $max_items);
				}

				$value	= array_values($value);
			}else{
				$value	= (array)$value;
			}
		}else{
			$value	= [];
		}

		if(!$max_reached){
			if($this->type == 'mu-fields'){
				$value[]	= [];
			}elseif($this->type != 'mu-img'){
				$value[]	= '';
			}
		}

		$item_class	= 'mu-item';
		$mu_items	= [];
		$last_item	= array_key_last($value);

		if($this->type == 'mu-img'){
			if(!current_user_can('upload_files')){
				return '';
			}

			$mu_class	= 'mu-imgs';
			$item_class	.= ' mu-img';
			$item_type	= $this->item_type ?: '';
			$item_args	= ['id'=>'', 'type'=>'hidden', 'name'=>$this->name.'[]'];

			foreach($value as $img){
				$img_url	= $item_type == 'url' ? $img : wp_get_attachment_url($img);
				$img_tag	= '<img src="'.wpjam_get_thumbnail($img_url, 200, 200).'" alt="">';
				$img_tag	= '<a href="'.$img_url.'" class="wpjam-modal">'.$img_tag.'</a>';

				if(!$this->readonly && !$this->disabled){
					$img_tag	.= $this->render_input(array_merge($item_args, ['value'=>$img])).self::get_icon('del_icon');
				}

				$mu_items[]	= $img_tag;
			}

			if(!$this->readonly && !$this->disabled){
				$attr		= ['name'=>$this->name.'[]', 'item_class'=>$item_class, 'item_type'=>$item_type, 'uploader_id'=>'wpjam_uploader_'.$this->id, 'thumb_args'=>wpjam_get_thumbnail('', [200,200])];
				$button		= '<div class="wpjam-mu-img dashicons dashicons-plus-alt2" '.wpjam_data_attribute_string($attr).'>'.self::get_icon('del_icon').'</div>';
			}else{
				$mu_class	.= ' readonly';
				$button		=  '';
			}
		}elseif($this->type == 'mu-fields'){
			if(!$this->objects){
				return '';
			}

			if(wpjam_array_pull($this->field, 'group')){
				$item_class	.= ' field-group';
			}

			$mu_class	= 'mu-fields';
			$tmpl_id	= md5($this->name);
			$button		= ' <a class="wpjam-mu-fields button" data-i="%s" data-item_class="'.$item_class.'" data-tmpl_id="'.$tmpl_id.'">添加选项</a>';

			foreach($value as $i => $item){
				$item_html	= $this->render_mu_fields($i, $item);

				if(!$this->readonly && !$this->disabled){
					$item_html	.= ($last_item === $i) ? sprintf($button, $i) : self::get_icon('del_btn,move_btn');
				}

				$mu_items[]	= $item_html;
			}

			if(!$this->readonly && !$this->disabled){
				$this->description	.= self::generate_tmpl($tmpl_id, $this->render_mu_fields('{{ data.i }}').sprintf($button, '{{ data.i }}'));
			}
		}elseif($this->type == 'mu-text'){
			$this->field	= wpjam_array_except($this->field, 'required');	// 提交时再验证

			$mu_class	= 'mu-texts';
			$button		= ' <a class="wpjam-mu-text button">添加选项</a>';

			foreach($value as $i => $item){
				$item_html	= $this->item_object->render_input(['value'=>$item, 'id'=>'', 'class'=>$this->class, 'name'=>$this->name.'[]', 'description'=>'']);
				$item_html	.= ($last_item === $i) ? $button : self::get_icon('del_btn,move_btn');

				$mu_items[]	= $item_html;
			}
		}elseif(in_array($this->type, ['mu-file', 'mu-image'], true)){
			if(!current_user_can('upload_files')){
				return '';
			}

			$mu_class	= 'mu-files';
			$item_type	= $this->type == 'mu-image' ? 'image' : '';
			$item_args	= ['type'=>'url', 'id'=>'', 'name'=>$this->name.'[]', 'description'=>''];

			$title		= $item_type == 'image' ? '图片' : '文件';
			$attr		= ['name'=>$this->name.'[]', 'item_class'=>'mu-item', 'item_type'=>$item_type, 'uploader_id'=>'wpjam_uploader_'.$this->id,	'title'=>'选择'.$title];
			$button		= '<a class="wpjam-mu-file button" '.wpjam_data_attribute_string($attr).'>选择'.$title.'[多选]</a>';

			foreach($value as $i => $item){
				$item_html	= $this->render_input(array_merge($item_args, ['value'=>$item]));
				$item_html	.= ($last_item === $i) ? $button : self::get_icon('del_btn,move_btn');
				
				$mu_items[]	= $item_html;
			}
		}

		$html	= $mu_items ? '<div class="'.$item_class.'">'.implode('</div> <div class="'.$item_class.'">', $mu_items).'</div>' : '';

		if($this->type == 'mu-img'){
			$html	.= $button;
		}

		return '<div class="'.$mu_class.'" id="'.$this->id.'" data-max_items="'.$max_items.'">'.$html.'</div>'.$this->description;
	}

	private function render_mu_fields($i, $value=[]){
		$show_if_keys	= $this->fields_object->show_if_keys;
		$group_obj		= new WPJAM_Field_Group();

		$html	= '';

		foreach($this->objects as $name => $object){
			if(preg_match('/\[([^\]]*)\]/', $name)){
				wp_die('mu-fields 类型里面子字段不允许[]模式');
			}

			if(in_array($object->type, ['fieldset', 'mu-fields'])){
				wp_die('mu-fields 不允许内嵌 '.$object->type);
			}

			$raw_field = $object->field;

			if($value && isset($value[$name])){
				$object->value	= $value[$name];
			}

			if($show_if_keys && in_array($object->key, $show_if_keys)){
				$object->show_if_key	= true;
			}

			$object->sub_i	= $i;
			$object->name	= $this->name.'['.$i.']'.'['.$name.']';
			$object->key	= $object->key.'__'.$i;
			$object->id		= $object->id.'__'.$i;

			$html	.= $group_obj->render($object->group);
			$html	.= $object->wrap($object->render());

			$object->field	= $raw_field;
		}
		
		$html	.= $group_obj->reset();

		return $html;
	}

	private function render_select_options($options, $value){
		$items		= [];

		foreach($options as $opt_value => $opt_title){
			if(is_array($opt_title) && !empty($opt_title['options'])){
				$sub_opts	= wpjam_array_pull($opt_title, 'options');
			}else{
				$sub_opts	= [];
			}

			$opt_title	= $this->parse_option_title($opt_title, [], $attr);

			if($sub_opts){
				$items[]	= '<optgroup '.$attr.' label="'.esc_attr($opt_title).'" >'.$this->render_select_options($sub_opts, $value).'</optgroup>';
			}else{
				$items[]	= '<option '.$attr.' value="'.esc_attr($opt_value).'" '.selected($opt_value, $value, false).'>'.$opt_title.'</option>';;
			}
		}

		return implode('', $items);
	}

	public  function wrap($html, $tag='div', $class=[]){
		if($this->type == 'hidden'){
			return $html;
		}

		if($title = $this->title){
			if($this->type != 'fieldset'){
				$title	= '<label'.($this->sub_field ? ' class="sub-field-label"' : '').' for="'.esc_attr($this->id).'">'.$title.'</label>';
			}
		}

		if($tag){
			$class		= (array)$class;
			$class[]	= wpjam_array_pull($this->field, 'wrap_class');

			if($this->sub_field){
				$class[]	= 'sub-field';
				$html		= '<div class="sub-field-detail">'.$html.'</div>';
			}

			$data	= [];

			if($show_if = $this->show_if){
				if(isset($this->sub_i) && !isset($show_if['postfix'])){
					$show_if['postfix']	= '__'.$this->sub_i;
				}

				if($show_if	= wpjam_parse_show_if($show_if, $class)){
					$data['show_if']	= $show_if; 
				}
			}

			$attr	= ['class'=>$class, 'data'=>$data, 'id'=>$tag.'_'.esc_attr($this->id)];

			if($tag == 'tr'){
				$attr['valign']	= 'top';

				$html	= $title ? '<th scope="row">'.$title.'</th><td>'.$html.'</td>' : '<td colspan="2">'.$html.'</td>';
			}else{
				$html	= $title.$html;
			}

			return '<'.$tag.' '.wpjam_attribute_string($attr).'">'.$html.'</'.$tag.'>';
		}else{
			return $title.$html;
		}
	}

	private function parse_option_title($opt_title, $class=[], &$attr){
		$data	= [];

		if(is_array($opt_title)){
			$opt_arr	= $opt_title;
			$opt_title	= wpjam_array_pull($opt_arr, 'title');

			foreach($opt_arr as $k => $v){
				if($k == 'show_if'){
					if(isset($this->sub_i) && !isset($v['postfix'])){
						$v['postfix']	= '__'.$this->sub_i;
					}

					if($show_if = wpjam_parse_show_if($v, $class)){
						$data['show_if']	= $show_if;
					}
				}elseif($k == 'class'){
					$class	= array_merge($class, explode(' ', $v));
				}elseif(!is_array($v)){
					$data[$k]	= $v;
				}
			}
		}

		$attr	= wpjam_attribute_string(['class'=>$class, 'data'=>$data]);

		return $opt_title;
	}

	private function generate_sub_name($name){
		return WPJAM_Field_Name::get_instance($name)->sub_name;
	}

	public static function is_bool_attr($attr){
		return in_array($attr, ['allowfullscreen', 'allowpaymentrequest', 'allowusermedia', 'async', 'autofocus', 'autoplay', 'checked', 'controls', 'default', 'defer', 'disabled', 'download', 'formnovalidate', 'hidden', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'playsinline', 'readonly', 'required', 'reversed', 'selected', 'typemustmatch'], true);
	}

	public static function get_icon($name){
		$return	= '';
		
		foreach(wp_parse_list($name) as $name){
			if($name == 'move_btn'){
				$return	.= ' <span class="dashicons dashicons-menu"></span>';
			}elseif($name == 'del_btn'){
				$return	.= ' <a href="javascript:;" class="button wpjam-del-item">删除</a>';
			}elseif($name == 'del_icon' || $name == 'del_img'){
				$class	= $name == 'del_img' ? 'wpjam-del-img' : 'wpjam-del-item';
				$return	.= ' <a href="javascript:;" class="del-item-icon dashicons dashicons-no-alt '.$class.'"></a>';
			}
		}

		return $return;
	}

	public  static function print_media_templates(){
		$tmpls	= [
			'mu-action'	=> self::get_icon('del_btn,move_btn'),
			'img'		=> '<img style="{{ data.img_style }}" src="{{ data.img_url }}{{ data.thumb_args }}" alt="" />',
			'mu-img'	=> '<img src="{{ data.img_url }}{{ data.thumb_args }}" /><input type="hidden" name="{{ data.name }}" value="{{ data.img_value }}" />',
			'mu-file'	=> '<input type="url" name="{{ data.name }}" class="regular-text" value="{{ data.img_url }}" />'
		];

		foreach($tmpls as $tmpl_id => $tmpl){
			echo self::generate_tmpl($tmpl_id, $tmpl);
		}

		echo '<div id="tb_modal"></div>';
	}

	public  static function generate_tmpl($tmpl_id, $tmpl){
		return "\n".'<script type="text/html" id="tmpl-wpjam-'.$tmpl_id.'">'.$tmpl.'</script>'."\n";
	}

	// 兼容
	public  static function get_value($field, $args=[]){
		return wpjam_parse_field_value($field, $args);
	}
}

class WPJAM_Fields{
	private $objects	= [];

	public  function __construct($fields){
		foreach($fields as $key => $field){
			$this->objects[$key]	= is_object($field) ? $field : new WPJAM_Field($field, $key);
		}
	}

	public  function __get($key){
		if($key == 'objects'){
			return $this->objects;
		}elseif($key == 'show_if_keys'){
			return $this->get_show_if_keys();
		}
	}

	public  function __isset($key){
		return in_array($key, ['objects', 'show_if_keys']);
	}

	public  function get_show_if_keys(){
		$keys	= [];

		foreach($this->objects as $object){
			$show_if	= $object->show_if;

			if($show_if && !empty($show_if['key'])){
				$keys[]	= $show_if['key'];
			}

			if($object->type == 'fieldset'){
				$keys	= array_merge($keys, $object->fields_object->show_if_keys);
			}
		}

		return array_unique($keys);
	}

	public  function get_defaults(){
		$defaults	= [];

		foreach($this->objects as $object){
			if(!$object->is_editable()){
				continue;
			}

			if($object->type == 'fieldset'){
				if($object->fields){
					$value	= $object->fields_object->get_defaults();

					if($object->fieldset_type == 'array'){
						$value	= array_filter($value, function($item){ return !is_null($item); });
					}

					$defaults	= wpjam_array_merge($defaults, $value);
				}
			}else{
				$name_obj	= WPJAM_Field_Name::get_instance($object->name);
				$name		= $name_obj->top_name;
				$value		= $name_obj->wrap_value($object->value);
				$defaults	= wpjam_array_merge($defaults, [$name=>$value]);
			}
		}

		return $defaults;
	}

	public  function get_data($values=null, $args=[]){
		$get_show_if	= $args['get_show_if'] ?? false;
		$show_if_values	= $args['show_if_values'] ?? [];
		$field_validate	= $get_show_if ? false : ($args['validate'] ?? true);

		$data	= [];

		foreach($this->objects as $object){
			if(!$object->is_editable()){
				continue;
			}

			$validate	= $field_validate;

			if($validate 
				&& $object->show_if
				&& wpjam_show_if($show_if_values, $object->show_if) === false
			){
				$validate	= false;
			}

			if($object->type == 'fieldset'){
				if($object->fields){
					$value	= $object->fields_object->get_data($values, array_merge($args, ['validate'=>$validate]));

					if(is_wp_error($value)){
						return $value;
					}

					if($object->fieldset_type == 'array'){
						$value	= array_filter($value, function($item){ return !is_null($item); });
					}

					$data	= wpjam_array_merge($data, $value);
				}
			}else{
				$name_obj	= WPJAM_Field_Name::get_instance($object->name);
				$name		= $name_obj->top_name;
				
				if(isset($values)){
					$value	= $values[$name] ?? null;
				}else{
					$value	= wpjam_get_parameter($name, ['method'=>'POST']);
				}

				$value	= $name_obj->parse_value($value);

				if($get_show_if){
					$key		= $object->key;	// show_if 判断是基于 key //并且 fieldset array 的情况下的 key 是 ${key}_{$sub_key}
					$data[$key]	= $object->validate($value, false);
				}else{
					$value = $object->validate($value, $validate);

					if(is_wp_error($value)){
						return $value;
					}

					$value	= $name_obj->wrap_value($value);
					$data	= wpjam_array_merge($data, [$name=>$value]);
				}
			}
		}

		return $data;
	}

	public  function validate($values=null){
		$show_if_values	= $this->show_if_keys ? $this->get_data($values, ['get_show_if'=>true]) : [];

		return $this->get_data($values, ['show_if_values'=>$show_if_values]);
	}

	public  function prepare($args=[]){
		$value	= [];

		foreach($this->objects as $object){
			if($object->type == 'fieldset' && $object->fieldset_type != 'array'){
				$value	= array_merge($value, $object->fields_object->prepare($args));
			}else{
				if($object->show_in_rest){
					$value[$object->name]	= $object->prepare($args);
				}
			}
		}

		return $value;
	}

	public  function callback($args=[]){
		$type	= wpjam_array_pull($args, 'fields_type', 'table');
		$class	= wpjam_array_pull($args, 'wrap_class', []);
		$echo	= wpjam_array_pull($args, 'echo', true);
		$map	= ['list'=>'li', 'table'=>'tr'];
		$tag	= $map[$type] ?? $type;
		$html	= '';

		$args['show_if_keys']	= $this->show_if_keys;

		foreach($this->objects as $object){
			if($object->show_admin_column === 'only'){
				continue;
			}

			$html	.= $object->wrap($object->callback($args), $tag, $class);
		}

		if($type == 'list'){
			$html	= '<ul>'.$html.'</ul>';
		}elseif($type == 'table'){
			$html	= '<table class="form-table" cellspacing="0"><tbody>'.$html.'</tbody></table>';
		}

		if($echo){
			echo $html;
		}else{
			return $html;
		}
	}
}

class WPJAM_Field_Name{
	private $top_name	= '';
	private $name_arr	= [];
	private $sub_arr	= [];

	public  function __construct($name){
		if(preg_match('/\[([^\]]*)\]/', $name)){
			$name_arr	= wp_parse_args($name);

			$this->top_name	= current(array_keys($name_arr));
			$this->name_arr	= current(array_values($name_arr));
		}else{
			$this->top_name	= $name;
		}
	}

	public  function __get($key){
		if($key == 'sub_name'){
			$name	= '['.$this->top_name.']';

			if($name_arr = $this->name_arr){
				do{
					$name		.='['.current(array_keys($name_arr)).']';
					$name_arr	= current(array_values($name_arr));
				}while($name_arr);
			}

			return $name;
		}elseif(in_array($key, ['top_name', 'name_arr'])){
			return $this->$key;
		}

		return null;
	}

	public  function __isset($key){
		return $this->$key !== null;
	}

	public  function parse_value($value){
		if($name_arr = $this->name_arr){
			$this->sub_arr	= [];

			do{
				$sub_name	= current(array_keys($name_arr));
				$name_arr	= current(array_values($name_arr));

				if(isset($value) && is_array($value) && isset($value[$sub_name])){
					$value	= $value[$sub_name];
				}else{
					$value	= null;
				}

				array_unshift($this->sub_arr, $sub_name);
			}while($name_arr && $value);
		}

		return $value;
	}

	public  function wrap_value($value){
		if($sub_arr = $this->sub_arr){
			foreach($sub_arr as $sub_name){
				$value	= [$sub_name => $value];
			}
		}

		return $value;
	}

	private static $instances	= [];

	public static function get_instance($name){
		if(!isset(self::$instances[$name])){
			self::$instances[$name]	= new self($name);
		}

		return self::$instances[$name];
	}
}

class WPJAM_Field_Group{
	private $group = '';

	public  function render($group){
		$return	= '';

		if($group != $this->group){
			if($this->group){
				$return	.= '</div>';
			}

			if($group){
				$return	.= '<div class="field-group" id="field_group_'.esc_attr($group).'">';
			}
		
			$this->group	= $group;
		}

		return $return;
	}

	public  function reset(){
		if($this->group){
			$this->group	= '';

			return '</div>';
		}

		return '';
	}
}

class WPJAM_Field_Data_Type{
	public static function parse_query_title(&$field, $value){
		$query_title	= '';
		$data_type		= wpjam_array_pull($field, 'data_type');
		$query_args		= wpjam_array_pull($field, 'query_args') ?: [];

		if($query_args && !is_array($query_args)){
			$query_args	= wp_parse_args($query_args);
		}

		if(!$data_type){
			return '';
		}elseif($data_type == 'post_type'){
			if($post_type = wpjam_array_pull($field, 'post_type')){
				$query_args['post_type']	= $post_type;
			}

			if($value && is_numeric($value)){
				if($data = get_post($value)){
					$query_title	= $data->post_title ?: $data->ID;
				}
			}
		}elseif($data_type == 'taxonomy'){
			if($taxonomy = wpjam_array_pull($field, 'taxonomy')){
				$query_args['taxonomy']	= $taxonomy;
			}

			if($value && is_numeric($value)){
				if($data = get_term($value)){
					$query_title	= $data->name ?: $data->term_id;
				}
			}
		}elseif($data_type == 'model'){
			if($model = wpjam_array_pull($field, 'model')){
				$query_args['model']	= $model;
			}

			$model	= $query_args['model'] ?? null;

			if(empty($model) || !class_exists($model)){
				wp_die($field['key'].' model 未定义');
			}

			$query_args	= wp_parse_args($query_args, ['label_key'=>'title', 'id_key'=>'id']);

			if($value){
				if($data = $model::get($value)){
					$label_key		= $query_args['label_key']; 
					$id_key			= $query_args['id_key'];
					$query_title	= $data[$label_key] ?: $data[$id_key];
				}
			}
		}

		$query_class	= $field['class'] ? ' '.implode(' ', array_unique($field['class'])) : '';

		if($query_title){
			$field['class'][]	= 'hidden';
		}else{
			$query_class	.= ' hidden';
		}

		$field['class'][]	= 'wpjam-autocomplete';

		$field['data-data_type']	= $data_type;
		$field['data-query_args']	= wpjam_json_encode($query_args);

		return '<span class="wpjam-query-title'.$query_class.'">
		<span class="dashicons dashicons-dismiss"></span>
		<span class="wpjam-query-text">'.$query_title.'</span>
		</span>';
	}

	public static function ajax_query(){
		$data_type	= wpjam_get_parameter('data_type',	['method'=>'POST']);
		$query_args	= wpjam_get_parameter('query_args',	['method'=>'POST']);

		if($data_type == 'post_type'){
			$query_args['posts_per_page']	= $query_args['posts_per_page'] ?? 10;
			$query_args['post_status']		= $query_args['post_status'] ?? 'publish';

			$query	= wpjam_query($query_args);
			$posts	= array_map(function($post){ return wpjam_get_post($post->ID); }, $query->posts);

			wpjam_send_json(['datas'=>$posts]);
		}elseif($data_type == 'taxonomy'){
			$query_args['number']		= $query_args['number'] ?? 10;
			$query_args['hide_empty']	= $query_args['hide_empty'] ?? 0;
			
			$terms	= wpjam_get_terms($query_args, -1);

			wpjam_send_json(['datas'=>$terms]);
		}elseif($data_type == 'model'){
			$model	= $query_args['model'];

			$query_args	= wpjam_array_except($query_args, ['model', 'label_key', 'id_key']);
			$query_args	= $query_args + ['number'=>10];
			
			$query	= $model::Query($query_args);

			wpjam_send_json(['datas'=>$query->datas]);
		}
	}
}

class WPJAM_Show_IF{
	private $show_if;

	public  function __construct($show_if){
		$this->show_if	= wp_parse_args($show_if);

		$this->init();
	}

	public  function __get($key){
		if($key == 'show_if'){
			return $this->show_if;
		}else{
			return $this->show_if[$key] ?? null;
		}
	}

	public  function __set($key, $value){
		$this->show_if[$key]	= $value;
	}

	public  function __isset($key){
		return isset($this->show_if);
	}

	public  function init(){
		if($this->key){
			$this->compare	= $this->compare ? strtoupper($this->compare) : '=';

			if($this->postfix){
				$this->key	= $this->key.$this->postfix;
			}

			if($this->compare == 'ITEM'){
				$this->show_if	= [];
			}elseif(in_array($this->compare, ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'])){
				if(!is_array($this->value)){
					$this->value	= preg_split('/[,\s]+/', $this->value);
				}

				if(count($this->value) == 1){
					$this->value	= current($this->value);
					$this->compare	= in_array($this->compare, ['IN', 'BETWEEN']) ? '=' : '!=';
				}else{
					$this->value	= array_map('strval', $this->value);	// JS Array.indexof is strict
				}
			}else{
				$this->value	= trim($this->value);
			}
		}else{
			$this->show_if	= [];
		}
	}

	public  function validate($item){
		if($this->key && isset($item[$this->key])){
			return self::compare($item[$this->key], $this->compare, $this->value);
		}

		return null;	// 没有比较
	}

	public static function compare($value, $operator, $compare_value){
		if(is_array($value)){
			if($operator == '='){
				return in_array($compare_value, $value);
			}else if($operator == '!='){
				return !in_array($compare_value, $value);
			}else if($operator == 'IN'){
				return array_intersect($value, $compare_value) == $compare_value;
			}else if($operator == 'NOT IN'){
				return array_intersect($value, $compare_value) == [];
			}
		}else{
			if($operator == '='){
				return $value == $compare_value;
			}else if($operator == '!='){
				return $value != $compare_value;
			}else if($operator == '>'){
				return $value > $compare_value;
			}else if($operator == '>='){
				return $value >= $compare_value;
			}else if($operator == '<'){
				return $value < $compare_value;
			}else if($operator == '<='){
				return $value <= $compare_value;
			}else if($operator == 'IN'){
				return in_array($value, $compare_value);
			}else if($operator == 'NOT IN'){
				return !in_array($value, $compare_value);
			}else if($operator == 'BETWEEN'){
				return $value > $compare_value[0] && $value < $compare_value[1];
			}else if($operator == 'NOT BETWEEN'){
				return $value < $compare_value[0] && $value > $compare_value[1];
			}
		}

		return false;
	}
}