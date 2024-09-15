<?php
function wpjam_get_field_post_ids($value, $field){
	if(empty($value)){
		return [];
	}

	if($field['type'] == 'img'){
		$item_type		= $field['item_type']??'';
		if($item_type != 'url'){
			return [$value];
		}
	}elseif($field['type'] == 'mu-img') {
		$item_type		= $field['item_type']??'';
		if(is_array($value) && $item_type != 'url'){
			return $value;
		}
	}elseif($field['type'] == 'mu-fields'){
		if($value && is_array($value)){
			foreach ($field['fields'] as $sub_key => $sub_field) {
				if($sub_field['type']	== 'img'){
					return array_column($value, $sub_key);
				}elseif(!empty($sub_field['data_type'])){
					if($sub_field['data_type'] == 'post_type'){
						return array_column($value, $sub_key);
					}
				}		
			}
		}
	}elseif($field['type'] == 'mu-text') {
		$value	= array_filter($value);

		if(!empty($field['data_type'])){
			if($field['data_type'] == 'post_type'){
				return $value;
			}
		}
	}else{
		if(!empty($field['data_type'])){
			if($field['data_type'] == 'post_type'){
				return [$value];
			}
		}
	}

	return [];
}

