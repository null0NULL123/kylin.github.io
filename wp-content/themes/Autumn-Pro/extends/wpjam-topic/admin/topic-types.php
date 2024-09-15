<?php
class WPJAM_Topic_Types_Admin extends WPJAM_Model{
	public static function __callStatic($method, $args){
		if(in_array($method, ['update_fields', 'add_field', 'edit_field', 'del_field', 'move_field'])){
			$id		= wpjam_array_pull($args, 0);
			$data	= $args[1] ?? [];
			$item	= self::get($id);
			$fields = $item['fields'] ?? [];
			
			if($method == 'update_fields'){
				return self::update($id, ['fields'=>array_values($data)]);
			}elseif($method == 'add_field'){
				$fields[]	= $data['field'];
				return self::update_fields($id, $fields);
			}elseif($method == 'edit_field'){
				$i	= wpjam_get_data_parameter('i');

				$fields[$i]	= $data['field'];
				
				return self::update_fields($id, $fields);
			}elseif($method == 'del_field'){
				$i	= wpjam_get_data_parameter('i');

				$fields	= wpjam_array_except($fields, $i);

				return self::update_fields($id, $fields);
			}elseif($method == 'move_field'){
				$new_fields	= [];

				$orders	= wpjam_get_data_parameter('field');

				foreach ($orders as $i) {
					if(isset($fields[$i])){
						$new_fields[]	= wpjam_array_pull($fields, $i);
					}
				}

				if($fields){
					$new_fields	= array_merge($new_fields, $fields);
				}

				return self::update_fields($id, $fields);
			}
		}elseif($method == 'get'){
			if($item = parent::__callStatic($method, $args)){
				$i	= wpjam_get_data_parameter('i');

				if(isset($i)){
					$item['field']	= $item['fields'][$i] ?? [];
				}
			}

			return $item;
		}else{
			return parent::__callStatic($method, $args);
		}
	}

	protected static $handler;

	public static function get_handler(){
		if(is_null(static::$handler)) {
			static::$handler = new WPJAM_Option_Items('wpjam-topic-types', ['primary_key'=>'id']);
		}

		return static::$handler;
	}

	public static function render_item($item){
		$columns	= [];

		if(is_array($item['columns'])){
			foreach($item['columns'] as $column){
				$columns[]	= $column['name'].'('.$column['slug'].')'; 
			}

			$item['columns']	= implode('<br />', $columns);
		}else{
			$item['columns']	= '';
		}

		$item['fields']	= wpjam_render_list_table_column_items($item['id'], $item['fields'], [
			'item_type'	=> 'text',
			'sortable'	=> true,
			'text_key'	=> 'name',
			'add_item'	=> 'add_field',
			'edit_item'	=> 'edit_field',
			'del_item'	=> 'del_field',
			'move_item'	=> 'move_field',
		]);

		return $item;
	}

	public static function get_actions(){
		return [
			'add'			=> ['title'=>'新增'],
			'edit'			=> ['title'=>'编辑'],
			'delete'		=> ['title'=>'删除',		'bulk'=>true,	'direct'=>true,	'confirm'=>true],
			'add_field'		=> ['title'=>'添加字段',	'page_title'=>'添加字段',	'response'=>'add_item',		'row_action'=>false],
			'edit_field'	=> ['title'=>'编辑字段',	'page_title'=>'编辑字段',	'response'=>'edit_item',	'row_action'=>false],
			'del_field'		=> ['title'=>'删除字段',	'page_title'=>'编辑字段',	'response'=>'del_item',		'row_action'=>false,	'direct'=>true, 'confirm'=>true],
			'move_field'	=> ['title'=>'移动字段',	'page_title'=>'移动字段',	'response'=>'move_item',	'row_action'=>false,	'direct'=>true]
		];
	}

	public static function get_fields($action_key='', $id=0){
		if($action_key == 'add_field' || $action_key == 'edit_field'){
			$item		= self::get($id);
			$columns	= array_column($item['columns'], 'name', 'slug');
			$types		= [
				'text'		=> '输入框',
				'textarea'	=> '文本框',
				'number'	=> '数字输入框',
				'url'		=> '链接输入框',
				'email'		=> '邮件输入框',
				'date'		=> '日期选择框',
				'time'		=> '时间选择框',
				'select'	=> '下拉选择框',
				'radio'		=> '单选框',
				'checkbox'	=> '复选框',
				// 'id'		=> '中国大陆身份证号',
				// 'tel'	=> '中国大陆手机号码',
				'img'		=> '上传图片',
			];

			$fields 	= [
				'name'		=> ['title'=>'名称',	'type'=>'text',		'name'=>'field[name]',		'required',	'class'=>'',	'placeholder'=>'请输入字段名称'],	
				'key'		=> ['title'=>'键名',	'type'=>'text',		'name'=>'field[key]',		'required',	'class'=>'',	'placeholder'=>'请输入字段键名'],	
				// 'label'		=> ['title'=>'提示',	'type'=>'text',		'name'=>'field[label]',		'placeholder'=>'输入提示'],
				'type'		=> ['title'=>'类型',	'type'=>'select',	'name'=>'field[type]',		'options'=>$types],
				'required'	=> ['title'=>'必填',	'type'=>'checkbox',	'name'=>'field[required]',	'description'=>'该字段必填'],
				'options'	=> ['title'=>'选项',	'type'=>'mu-text',	'name'=>'field[options]',	'class'=>'',	'placeholder'=>'请输入选项...',	'show_if'=>['key'=>'type', 'compare'=>'in', 'value'=>['select', 'radio', 'checkbox']]],
				'columns'	=> ['title'=>'栏目',	'type'=>'checkbox',	'name'=>'field[columns]',	'options'=>$columns,	'value'=>array_keys($columns)]
			];

			return $fields;
		}else{
			return [
				'name'		=> ['title'=>'名称',	'type'=>'text',		'show_admin_column'=>true,	'class'=>''],
				'slug'		=> ['title'=>'别名',	'type'=>'text',		'show_admin_column'=>true,	'class'=>''],
				'group'		=> ['title'=>'分组',	'type'=>'radio',	'show_admin_column'=>true,	'options'=>[0=>'不支持分组', 1=>'支持分组']],
				'columns'	=> ['title'=>'栏目',	'type'=>'mu-fields','show_admin_column'=>true,	'group'=>true,	'fields'=>[
					'name'	=> ['title'=>'',	'type'=>'text',	'placeholder'=>'请输入栏目名称',	'class'=>''],
					'slug'	=> ['title'=>'',	'type'=>'text',	'placeholder'=>'请输入栏目别名',	'class'=>''],
				]],
				'fields'	=> ['title'=>'字段',	'type'=>'view','show_admin_column'=>'only']	
			];
		}
	}
}