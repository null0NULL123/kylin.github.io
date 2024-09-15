<?php
class WPJAM_Groups_Admin{
	public static function __callStatic($method, $args){
		$switched	= wpjam_topic_switch_to_blog();

		if($method == 'insert'){
			$result	= WPJAM_Term::insert(array_merge($args[0], ['taxonomy'=>'group']));
		}elseif(in_array($method, ['get', 'update', 'move', 'delete'])){
			$result	= call_user_func(['WPJAM_Term', $method], ...$args);
		}elseif($method == 'query_items'){
			$items	= wpjam_get_terms(['taxonomy'=>'group']);

			$result	= ['items'=>$items, 'total'=>count($items)];
		}elseif(method_exists(self::class, '_'.$method)){
			$result	= call_user_func([self::class, '_'.$method], ...$args);
		}else{
			$result	= null;
		}

		if($switched){
			restore_current_blog();
		}

		return $result;
	}

	public static function get_fields($action_key='', $id=0){
		return [
			'name'	=> ['title'=>'名称',	'type'=>'text',		'show_admin_column'=>true],
			'slug'	=> ['title'=>'别名',	'type'=>'text',		'show_admin_column'=>true],
			'count'	=> ['title'=>'数量',	'type'=>'number',	'show_admin_column'=>'only'],
		];
	}

	public static function _get_prompt($term_id){
		$prompt	= get_term_meta($term_id, 'prompt', true);

		return $prompt ? wpautop($prompt) : '';
	}
}

wpjam_register_terms_column('prompt', [
	'title'				=> '输入提示',
	'column_callback'	=> ['WPJAM_Groups_Admin', 'get_prompt']
]);