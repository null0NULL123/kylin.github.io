<?php
class WPJAM_Topic_Accounts_Admin extends WPJAM_Model{
	protected static $handler;

	public static function get_handler(){
		if(is_null(static::$handler)) {
			static::$handler = new WPJAM_Option_Items('wpjam-topic-accounts', ['primary_key'=>'key']);
		}

		return static::$handler;
	}

	public static function render_item($item){
		return $item;
	}

	public static function get_fields($action_key='', $id=0){
		$options	= [
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
			'key'		=> ['title'=>'键名',	'type'=>'text',		'required',	'class'=>'',	'placeholder'=>'请输入字段键名',	'show_admin_column'=>true],	
			'name'		=> ['title'=>'名称',	'type'=>'text',		'required',	'class'=>'',	'placeholder'=>'请输入字段名称',	'show_admin_column'=>true],	
			// 'label'		=> ['title'=>'提示',	'type'=>'text',		'placeholder'=>'输入提示'],
			'type'		=> ['title'=>'类型',	'type'=>'select',	'options'=>$options,	'show_admin_column'=>true],
			'required'	=> ['title'=>'必填',	'type'=>'checkbox',	'description'=>'该字段必填',	'show_admin_column'=>true],
			'options'	=> ['title'=>'选项',	'type'=>'mu-text',	'class'=>'',	'placeholder'=>'请输入选项...',	'show_if'=>['key'=>'type', 'compare'=>'in', 'value'=>['select', 'radio', 'checkbox']]]
		];

		return $fields;
	}
}