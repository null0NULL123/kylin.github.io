<?php
class WPJAM_Table_Template{
	public static function get_template($post, $text){
		$table_content	= $post->post_content ? maybe_unserialize($post->post_content) : [];
		$table_fields	= self::get_table_fields($post->ID);
		
		if(!$table_content || !$table_fields){
			return '';
		}

		$table_fields	= self::parse_table_fields($table_fields);

		$thead = $tbody = '';

		foreach ($table_fields as $table_field) {
			$thead .= "\t\t\t".'<th>'.$table_field['title'].'</th>'."\n";
		}

		$thead = "\t\t".'<tr>'."\n".$thead."\t\t".'</tr>'."\n";

		foreach ($table_content as $table_row) {
			$tbody .= "\t\t".'<tr>'."\n";
			foreach($table_fields as $table_field){
				$field_type		= $table_field['type'];
				$field_index	= 'i'.$table_field['index'];

				$value	= $table_row[$field_index] ?? '';

				if($field_type == 'img'){
					if(!empty($table_row[$field_index])){
						$thumb	= wpjam_get_thumbnail($value, '200x200');
						$value	= '<a href="'.$value.'"><img src="'.$thumb.'" width="100" height="100" /></a>';
					}
				}elseif($field_type == 'textarea'){
					$value	= wpautop(do_shortcode($value));
				}

				if(isset($table_field['url'])){
					$url_index	= 'i'.$table_field['url']['index'];

					$url	= $table_row[$url_index] ?? '';
					$value	= '<a href="'.$url.'">'.$value.'</a>';
				}

				$tbody .= "\t\t\t".'<td>'.$value.'</td>'."\n";
			}
			$tbody .= "\t\t".'</tr>'."\n";
		}

		$table		= "\t".'<thead>'."\n".$thead."\t".'</thead>'."\n"."\t".'<tbody>'."\n".$tbody."\t".'</tbody>'."\n";
		// $table .= "\t".'<tfoot>'."\n".$thead."\t".'</tfoot>'."\n";

		$summary	= $post->post_title ? 'summary="'.esc_attr($post->post_title).'"' : '';

		$text	= $post->post_excerpt . $text;
		$text	= $text ? "\n".wpautop(do_shortcode($text)) : '';

		return $text."\n".'<table '.$summary.'>'."\n".$table.'</table>'."\n";
	}

	public static function get_table_fields($post_id=null){
		if(is_admin() && is_null($post_id)){
			$post_id	= wpjam_get_data_parameter('post_id');
		}

		return $post_id ? get_post_meta($post_id, '_table_fields', true) : [];
	}

	public static function parse_table_fields($table_fields){
		$url_for_fields	= [];

		foreach($table_fields as $key => $table_field) {
			if($table_field['type'] == 'url'){
				if($table_field['url_for']){
					$url_for_fields[$table_field['url_for']]	= $key;
				}
			}
		}

		$fields	= [];

		foreach($table_fields as $key => $table_field) {
			if($table_field['type'] != 'url'){
				if(isset($url_for_fields[$table_field['title']])){
					$url_key	= $url_for_fields[$table_field['title']];

					$table_field['url']	= $table_fields[$url_key];

					$fields[]	= $table_field;
				}else{
					if(empty($table_field['url_for'])){
						$fields[]	= $table_field;
					}
				}
			}
		}

		return $fields;
	}

	public static function get_fields($action_key=''){
		$post_id		= wpjam_get_data_parameter('post_id');

		if($post_id){
			$post	= get_post($post_id);

			if(empty($post)){
				return new WP_Error('invaild_post_id', '无效的 post_id');
			}

			$table_fields	= self::get_table_fields();
		}

		if($action_key == 'save_setting'){
			if($post_id){
				$post_title		= $post->post_title;
				$post_name		= $post->post_name;
				$post_excerpt	= $post->post_excerpt;
				$post_password	= $post->post_password;
			}else{
				$post_title		= $post_name = $post_excerpt = $post_password = '';
				$table_fields	= [];
			}

			$type_options	= [
				'text'		=> '输入框',
				'textarea'	=> '文本框',
				'number'	=> '数字输入框',
				'url'		=> '链接输入框',
				'email'		=> '邮件输入框',
				'date'		=> '日期选择框',
				'time'		=> '时间选择框',
				'select'	=> '下拉选择框',
				'img'		=> '上传图片',
			];

			return [
				'post_title'	=> ['title'=>'标题',		'type'=>'text',		'value'=>$post_title],
				'post_name'		=> ['title'=>'标识',		'type'=>'text',		'value'=>$post_name],
				'post_excerpt'	=> ['title'=>'简介',		'type'=>'textarea',	'value'=>$post_excerpt,	'class'=>''],
				'post_password'	=> ['title'=>'密码',		'type'=>'text',		'value'=>$post_password,'class'=>'',	'description'=>'设置了密码保护，则前端必须输入密码才可查看'],
				'table_fields'	=> ['title'=>'字段',		'type'=>'mu-fields',	'value'=>$table_fields,	'group'=>true,	'fields'=>[
					'title'		=> ['title'=>'',	'type'=>'text',		'class'=>'',	'placeholder'=>'请输入字段名称'],
					'type'		=> ['title'=>'',	'type'=>'select',	'options'=>$type_options],
					'options'	=> ['title'=>'',	'type'=>'mu-text',	'class'=>'',	'placeholder'=>'请输入选项...',		'show_if'=>['key'=>'type', 'value'=>'select']],
					'url_for'	=> ['title'=>'',	'type'=>'text',		'class'=>'',	'placeholder'=>'链接字段应用于...',	'show_if'=>['key'=>'type', 'value'=>'url']],
					// 'required'	=> ['title'=>'',	'type'=>'checkbox',	'description'=>'必填'],
					'index'		=> ['title'=>'',	'type'=>'hidden'],
				]]
			];
		}elseif($action_key == 'bulk_edit'){
			if(empty($table_fields)){
				return new WP_Error('empty_table_fields', '请先在「表格设置」中添加字段。');
			}

			foreach ($table_fields as $table_field) {
				if($table_field['type'] == 'textarea'){
					return new WP_Error('bulk_edit_not_allowed', '含有「富文本」类型的字段，不能批量编辑。');
				}elseif($table_field['type'] == 'img'){
					return new WP_Error('bulk_edit_not_allowed', '含有「图片」类型的字段，不能批量编辑。');	
				}
			}

			
			$post_content	= get_post($post_id)->post_content;
			$table_content	= $post_content ? maybe_unserialize($post_content) : [];

			$value	= '';

			if($table_content){
				$field_indexs	= [];

				foreach ($table_fields as $table_field) {
					$field_indexs[]	= 'i'.$table_field['index'];		
				}

				foreach ($table_content as $table_row) {
					foreach ($field_indexs as $field_index) {
						$v	= $table_row[$field_index] ?: ' ';
						$value	.= $v."\n";
					}
					$value	.="\n";
				}
			}

			return ['table_content'=>['title'=>'',	'type'=>'textarea',	'rows'=>20,	'class'=>'large-text', 'value'=>$value]];
		}
	}

	public static function page_action($action_key=''){
		$post_id	= wpjam_get_data_parameter('post_id');

		if($post_id){
			$post	= get_post($post_id);

			if(empty($post)){
				return new WP_Error('invaild_post_id', '无效的 post_id');
			}
		}

		if($action_key == 'save_setting'){
			$post_status	= 'publish';
			$post_type		= 'template';
			$post_title		= wpjam_get_data_parameter('post_title');
			$post_name		= wpjam_get_data_parameter('post_name');
			$post_excerpt	= wpjam_get_data_parameter('post_excerpt');
			$post_password	= wpjam_get_data_parameter('post_password');
			$table_fields	= wpjam_get_data_parameter('table_fields') ?? [];

			if($table_fields){
				$indexs	= array_column($table_fields, 'index');
				$index	= max($indexs);

				foreach($table_fields as $key=> &$table_field){
					if(empty($table_field['title'])){
						unset($table_fields[$key]);
						continue;
					}

					if($table_field['type'] == 'select'){
						$table_field['options']	= array_filter($table_field['options']);
					}else{
						unset($table_field['options']);

						if($table_field['type'] != 'url'){
							unset($table_field['url_for']);
						}
					}

					if(empty($table_field['index'])){
						$index++;
						$table_field['index']	= $index;
					}
				}

				if($table_fields){
					$table_fields	= array_values($table_fields);
				}
			}

			$meta_input		= [
				'_template_type'	=> 'table',
				'_table_fields'		=> $table_fields ?: []
			];

			$post_data	= compact('post_type', 'post_title', 'post_name', 'post_excerpt', 'post_status', 'post_password', 'meta_input');

			if($post_id){
				return WPJAM_Post::update($post_id, $post_data);
			}else{
				$post_id	= WPJAM_Post::insert($post_data);
				return is_wp_error($post_id) ? $post_id : ['type'=>'redirect', 'url'=>admin_url('edit.php?post_type=template&page=wpjam-table&post_id='.$post_id)];
			}
		}elseif($action_key == 'bulk_edit'){
			if($table_content = trim(wpjam_get_data_parameter('table_content'))){
				$field_indexs	= [];

				foreach(self::get_table_fields() as $table_field){
					$field_indexs[]	= 'i'.$table_field['index'];		
				}

				$table_content	= str_replace("\r\n", "\n", $table_content);
				$table_content	= str_replace("\r\n", "\n", $table_content);

				$items	= [];
				$trs	= explode("\n\n", $table_content);

				$index	= 0; 
				foreach($trs as $tr){
					$index++;
					$tds	= explode("\n", $tr);
					$item	= [];
					foreach ($field_indexs as $i => $field_index) {
						$td	= $tds[$i] ?? '';
						$item[$field_index]	= trim($td);
					}

					$items[$index]	= $item;
				}

				
				$post_content	= maybe_serialize($items);
				$result			= WPJAM_Post::update($post_id, compact('post_content'));

				if(is_wp_error($result)){
					return $result;
				}	
			}

			return true;
		}	
	}

	public static function load_plugin_page(){
		if($post_id = wpjam_get_data_parameter('post_id')){
			wpjam_register_plugin_page_tab('content',	[
				'title'				=> '表格内容',
				'function'			=> 'list',
				'list_table_name'	=> 'table_content',
				'query_args'		=> ['post_id'],
				'plural'			=> 'table-contents',
				'singular'			=> 'table-content',
				'model'				=> 'WPJAM_Table_Content',
				'capability'		=> 'edit_others_posts',
				'sortable'			=> true
			]);

			wpjam_register_plugin_page_tab('bulk',	[
				'title'			=> '批量编辑',
				'function'		=> 'form',
				'form_name'		=> 'bulk_edit',
				'query_args'	=> ['post_id'],
				'summary'		=> '<p>批量编辑极其容易造成数据丢失和紊乱，批量编辑前请先做好备份。</p><p>批量编辑规则：</p><p>* 连续两个回车当做：<strong>一行</strong>。<br />* 单独一个回车当做：<strong>单元格</strong>。</p>'
			]);

			wpjam_register_plugin_page_tab('setting',	[
				'title'			=> '表格设置',
				'function'		=> 'form',
				'form_name'		=> 'save_setting',
				'query_args'	=> ['post_id']
			]);

			wpjam_register_page_action('bulk_edit', [
				'submit_text'	=> '批量编辑',
				'fields'		=> [self::class, 'get_fields'],
				'callback'		=> [self::class, 'page_action']
			]);
		}else{
			wpjam_register_plugin_page_tab('setting',	[
				'title'			=> '新建设置',
				'function'		=> 'form',
				'form_name'		=> 'save_setting'
			]);
		}

		wpjam_register_page_action('save_setting', [
			'submit_text'	=> $post_id ? '编辑' : '新建',
			'callback'		=> [self::class, 'page_action'],
			'fields'		=> [self::class, 'get_fields'],
		]);
	}
}

class WPJAM_Table_Content extends WPJAM_Model{
	private static $handler	= null;

	public static function get_handler(){
		if(is_null(static::$handler)){
			$post_id	= wpjam_get_data_parameter('post_id');

			static::$handler = new WPJAM_Content_Items($post_id);
		}

		return static::$handler;
	}

	public static function query_items($limit, $offset){
		if(empty(WPJAM_Table_Template::get_table_fields())){
			return new WP_Error('empty_table_fields', '请先在「表格设置」中添加字段。');
		}

		return parent::query_items($limit, $offset);
	}

	public static function render_item($item){
		$items	= self::get_all();
		$ids	= array_keys($items);
		$max	= count($ids);
		$i		= array_search($item['id'], $ids);

		$table_fields	= WPJAM_Table_Template::get_table_fields();
		$table_fields	= WPJAM_Table_Template::parse_table_fields($table_fields);
		
		foreach($table_fields as $table_field){
			$field_type		= $table_field['type'];
			$field_index	= 'i'.$table_field['index'];

			if($field_type == 'img'){
				if(!empty($item[$field_index])){
					$item[$field_index]	= wpjam_get_thumbnail($item[$field_index], '200x200');
					$item[$field_index]	= '<img src="'.$item[$field_index].'" width="100" height="100" />';
				}
			}elseif($field_type == 'textarea'){
				$item[$field_index]	= wpautop(do_shortcode($item[$field_index]));
			}

			if(isset($table_field['url'])){
				$url_index	= 'i'.$table_field['url']['index'];
				$url		= $item[$url_index] ?? '';

				$item[$field_index]	= '<a href="'.$url.'" target="_blank">'.$item[$field_index].'</a>';
			}
		}
		
		return $item;
	}

	public static function get_fields($action_key='', $id=0){
		$fields	= [];
		
		foreach(WPJAM_Table_Template::get_table_fields() as $table_field){
			$field_type		= $table_field['type'];
			$field_index	= 'i'.$table_field['index'];
			$field			= ['title'=>$table_field['title'],	'type'=>$table_field['type'],	'show_admin_column'=>true];

			if($table_field['type'] == 'select'){
				$field_options		= array_merge([''], $table_field['options']);
				$field['options']	= array_combine($field_options, $field_options);
			}elseif($table_field['type'] == 'img'){
				$field['item_type']	= 'url'; 
				$field['size']		= '200x200'; 
			}elseif($table_field['type'] == 'url'){
				if(!empty($table_field['url_for'])){
					$field['show_admin_column']	= false;
				}
			}

			$fields[$field_index]	= $field;
		}

		return $fields;
	}
}