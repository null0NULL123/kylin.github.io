<?php
class WPJAM_Terms_Filter{
	public static function get_fields(){
		$fields		= [];
		$post_type	= wpjam_get_plugin_page_setting('post_type');

		foreach(get_object_taxonomies($post_type, 'objects') as $taxonomy => $tax_obj){
			if($tax_obj->show_ui && $tax_obj->filterable){
				$label	= $tax_obj->label;
				$tax	= $taxonomy == 'post_tag' ? 'tag' : $taxonomy;

				$fields[$taxonomy]	= ['title'=>$label,	'type'=>'fieldset',	'fields'=>[
					$taxonomy.'s'		=> ['title'=>'','type'=>'mu-text',	'class'=>'all-options',	'placeholder'=>'请输入'.$label,	'data_type'=>'taxonomy',	'taxonomy'=>$taxonomy],
					$taxonomy.'_filter'	=> ['title'=>'','type'=>'select',	'options'=>[$tax.'__and'=>'所有'.$label.'都使用', $tax.'__in'=>'至少使用其中一个', $tax.'__not_in'=>'所有'.$label.'都不使用']]
				]];
			}
		}

		if(count($fields) > 1){
			$fields['relation']	= ['title'=>'关系',	'type'=>'select',	'options'=>['and'=>'AND','or'=>'OR']];
		}

		$fields['export']		= ['title'=>'导出',	'type'=>'checkbox',	'description'=>'筛选后支持导出文章标题和链接'];

		return $fields;
	}

	public static function generate_link($data){
		$post_type		= wpjam_get_parameter('post_type',	['method'=>'POST', 'default'=>'post']);
		$filter_args	= [];

		foreach(get_object_taxonomies($post_type, 'objects') as $taxonomy => $tax_obj){
			if($tax_obj->show_ui && $tax_obj->filterable){
				if($terms = $data[$taxonomy.'s']){
					$filter_args[]	= $data[$taxonomy.'_filter'].'='.urlencode(implode(',', $terms));
				}
			}
		}

		if(empty($filter_args)){
			return new WP_error('empty_terms', '你至少要选择一个分类或者标签或者其他分类模式');
		}

		$link	= admin_url('edit.php?post_type='.$post_type.'&'.implode('&',$filter_args));

		if(count($filter_args) > 1){
			$link	.= '&tax_query_relation='.$data['relation'];
		}

		if(!empty($data['export'])){
			$link	.= '&export_required=1';
		}

		return $link;
	}
}

wpjam_register_page_action('terms_filter',[
	'summary'		=> '多重筛选支持筛选出同时用多个标签的文章列表，详细介绍：<a href="https://blog.wpjam.com/project/wpjam-taxonomy/" target="_blank">分类设置插件</a>。',
	'submit_text'	=> '筛选',
	'capability'	=> 'edit_posts',
	'response'		=> 'redirect',
	'validate'		=> true,
	'fields'		=> ['WPJAM_Terms_Filter', 'get_fields'],
	'callback'		=> ['WPJAM_Terms_Filter', 'generate_link']
]);

	