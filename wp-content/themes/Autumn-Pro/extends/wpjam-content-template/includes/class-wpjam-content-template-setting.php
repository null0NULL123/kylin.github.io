<?php
class WPJAM_Content_Template_Setting{
	use WPJAM_Setting_Trait;

	private function __construct(){
		$this->init('wpjam-content-template');
	}

	public function register(){
		wpjam_register_post_type('template',	[
			'label'			=> $this->get_setting('title', '内容模板'),
			'public'		=> false,
			// 'publicly_queryable'=>is_admin(),
			'show_ui'		=> true,
			'has_archive'	=> false,
			'rewrite'		=> false,
			'query_var'		=> false,
			'menu_position'	=> 50,
			'menu_icon'		=> 'dashicons-edit',
			'supports'		=> ['title','editor'],
		]);
	}

	public function get_style(){
		$style	= '';

		if($card_style = $this->get_setting('card_style')){
			$style	.= $card_style;
		}

		if(did_action('weixin_loaded') && ($weixin_style = $this->get_setting('weixin_style'))){
			$style	.= "\n".$weixin_style;
		}

		return $style;
	}

	public function add_style(){
		if($style = $this->get_style()){
			if(did_action('wpjam_static')){
				wpjam_register_static('wpjam-content-template-style', ['title'=>'内容模板',	'type'=>'style',	'source'=>'value',	'value'=>$style]);
			}else{
				add_action('wp_head', function(){
					echo "\n".'<style type="text/css">'."\n".$this->get_style()."\n".'</style>'."\n";
				});
			}
		}	
	}

	public function filter_the_content($content) {
		if(doing_filter('get_the_excerpt')){ 
			return $content;
		}

		if((is_singular() && get_the_ID() == get_queried_object_id()) || is_feed()){
			$key	= is_feed() ? 'feed' : get_post_type();

			foreach (['top', 'bottom'] as $position){
				$template	= $this->get_setting($key.'_'.$position);

				if(empty($template)){
					continue;
				}

				if($position == 'top'){
					if($template && is_numeric($template)){
						$content	= '[template id="'.$template.'"]'."\n\n".$content;
					}
				}else{
					foreach ($template as $t) {
						if($t && is_numeric($t)){
							$content	.= "\n\n".'[template id="'.$t.'"]';
						}
					}
				}	
			}
		}

		return $content;
	}

	public function get_fields($id='', $action_key=0){
		if($action_key == 'add'){
		
			$types = [];

			foreach(WPJAM_Content_Template_Type::get_registereds() as $type=>$object){
				$types[$type]	= $object->to_array();

				$types[$type]['title']	= ' <span class="dashicons dashicons-'.$object->dashicon.'"></span> '.$object->title;
			}
			
			return ['template_type'	=> ['title'=>'',	'type'=>'radio',	'options'=>$types,	'sep'=>'<br /><br />']];
		}
	}

	public function list_action($id, $data, $action_key=''){
		if($action_key == 'add'){
			$template_type	= $data['template_type'] ?: 'content';

			if($template_type == 'content'){
				return admin_url('post-new.php?post_type=template&template_type=content');
			}else{
				return admin_url('edit.php?post_type=template&page=wpjam-'.$template_type);
			}
		}
	}

	public function on_pre_get_posts($query){
		if($query->is_main_query()){
			if($type = wpjam_get_data_parameter('template_type',	['sanitize_callback'=>'sanitize_key'])){
				$query->set('meta_key', '_template_type');

				if($type == 'content'){
					$query->set('meta_compare', 'NOT EXISTS');
				}else{
					$query->set('meta_value', $type);
				}
			}
		}
	}

	public function filter_views($views){
		$current	= wpjam_get_data_parameter('template_type');

		foreach(WPJAM_Content_Template_Type::get_registereds() as $type => $object) {
			$query_args	= $type == 'content' ? ['meta_compare'=>'NOT EXISTS'] : ['meta_value'=>$type];
			$query_args	= array_merge(['no_found_rows'=>false, 'post_type'=>'template',	'meta_key'=>'_template_type'], $query_args);
			$query		= wpjam_query($query_args);

			if($count = $query->found_posts){
				$class	= ($current && $current == $type) ?' class="current"' : '';
				$views[$type.'-content']	='<a href="edit.php?post_type=template&template_type='.$type.'"'.$class.'><span class="dashicons dashicons-'.$object->dashicon.'"></span> '.$object->title.'模板<span class="count">（'.$count.'）</span></a>';
			}
		}

		return $views;
	}

	public function filter_single_row($single_row, $post_id){
		if($type = wpjam_get_content_template_type($post_id)){
			return str_replace(
				'href="'.admin_url('post.php?post='.$post_id.'&amp;action=edit').'"',
				'href="'.admin_url('edit.php?post_type=template&amp;page=wpjam-'.$type.'&post_id='.$post_id).'"',
				$single_row
			);
		}

		return $single_row;
	}

	public function column_callback($post_id, $column_name){
		if($column_name == 'shortcode'){
			$shortcode	= '[template id="'.$post_id.'"]';
			$post_name	= get_post($post_id)->post_name;

			if($post_name && $post_name != $post_id && strpos($post_name, '%') === false){
				$shortcode	.= "\n\n".'[template name="'.$post_name.'"]';
			}

			return wpautop($shortcode);
		}elseif($column_name == 'template_type'){
			$type	= wpjam_get_content_template_type($post_id) ?: 'content';

			if($object = wpjam_get_content_template_type_object($type)){
				return '<span class="dashicons dashicons-'.$object->dashicon.'"></span>  '.$object->title.'模板';
			}

			return '';
		}
	}

	public function add_editor_button($editor_id){
		echo ' '.wpjam_get_page_button('insert_content_template', ['data'=>['editor_id'=>$editor_id]]);
		?>
		<script type="text/javascript">
		jQuery(function($){
			$('body').on('submit', "#template_form", function(e){
				e.preventDefault();	// 阻止事件默认行为。
				wp.media.editor.insert("\n"+'[template id="'+$('#template_id').val()+'"]'+"\n");
				tb_remove();
			});
		});
		</script>

		<?php
	}

	public function on_builtin_page_load($screen_base, $current_screen){
		if(!in_array($screen_base, ['edit', 'post'])){
			return;
		}

		if($current_screen->post_type == 'template'){
			if($screen_base == 'edit'){
				wpjam_register_list_table_action('add', [
					'title'		=> '新建',
					'response'	=> 'redirect',
					'width'		=> 400,
					'fields'	=> [$this, 'get_fields'],
					'callback'	=> [$this, 'list_action']
				]);

				add_filter('disable_months_dropdown', '__return_true');

				add_filter('views_edit-template',	[$this, 'filter_views'], 1, 2);
				add_filter('wpjam_single_row',		[$this, 'filter_single_row'], 10, 2);

				add_action('pre_get_posts',			[$this, 'on_pre_get_posts']);

				wpjam_register_list_table_column('template_type',	['title'=>'类型',	'column_callback'=>[$this, 'column_callback']]);
				wpjam_register_list_table_column('shortcode',		['title'=>'短代码',	'column_callback'=>[$this, 'column_callback']]);
			}else{
				remove_meta_box('slugdiv', 'template', null);

				$post_id	= $_GET['post'] ?? 0;
				$post_name	= ($post_id && ($_post = get_post($post_id))) ? $_post->post_name : '';

				wpjam_register_post_option('name_meta_box', [
					'title'				=> '标识',
					'context'			=> 'side',
					'update_callback'	=> '__return_null',
					'data'				=> ['post_name'=>$post_name],
					'fields'			=> [
						'post_name'	=> ['title'=>'',	'type'=>'text',	'class'=>'',	'style'=>'width:98%;'],
					]
				]);
			}
		}elseif($current_screen->post_type != 'attachment' && $screen_base == 'post') {
			if(current_user_can('edit_posts')){
				wpjam_register_page_action('insert_content_template', [
					'page_title'	=> '插入模板',
					'form_id'		=> 'template_form',
					'submit_text'	=> '插入',
					'button_text'	=> '<span class="dashicons dashicons-edit content-template-button" style="width:18px; height:18px; vertical-align:text-bottom;"></span> 插入模板', 
					'class'			=> 'button',
					'width'			=> 420,
					'fields'		=> [
						'template'	=> ['title'=>'',	'type'=>'fieldset',	'fields'=>[
							'template_id'	=> array_merge(wpjam_get_post_id_field('template'), ['title'=>'选择模板']),
							'template_view'	=> ['title'=>' ',	'type'=>'view',	'value'=>'请点击选择或者输入关键字查询后选择...'],
						]],
					],
				]);

				add_action('media_buttons',	[$this, 'add_editor_button']);
			}
		}
	}

	public static function load_option_page(){
		$fields		= ['title'	=> ['title'=>'内容模板名称',	'type'=>'text',	'class'=>'all-options',	'value'=>'内容模板']];

		foreach(get_post_types(['show_ui'=>true,'public'=>true], 'objects') as $post_type => $pt_obj){
			if($post_type == 'attachment'){
				continue;
			}

			$fields[$post_type.'_set']	= ['title'=>$pt_obj->label,	'type'=>'fieldset',	'fields'=>[
				$post_type.'_top'		=> array_merge(wpjam_get_post_id_field('template'), ['title'=>'顶部模板']),
				$post_type.'_bottom'	=> array_merge(wpjam_get_post_id_field('template'), ['title'=>'底部模板',	'type'=>'mu-text'])
			]];
		}

		$fields['feed_set']	= ['title'=>'Feed',	'type'=>'fieldset',	'fields'=>[
			'feed_top'		=> array_merge(wpjam_get_post_id_field('template'), ['title'=>'顶部模板']),
			'feed_bottom'	=> array_merge(wpjam_get_post_id_field('template'), ['title'=>'底部模板',	'type'=>'mu-text'])
		]];

		$sections	= ['content'=>[
			'title'		=> '内容模板', 
			'fields'	=> $fields
		]];

		$weixin_tip		= '扫码关注公众号，回复「[keyword]」获取文章密码。';
		$weixin_reply	= '密码是： [password]';
		$weixin_style	= '
		div.post-password-content-template{margin-bottom: 20px; padding:10px; background: #EDF3DE;}
		form.content-template-post-password-form:after{display: block; content: " "; clear: both;}
		form.content-template-post-password-form img{float: left; margin-right:10px;}
		form.content-template-post-password-form input[type="password"]{ border: 1px solid #EDE8E2; padding: 6px;}
		form.content-template-post-password-form input[type="submit"]{padding: 8px; background: #1BA6B2; color: #fff; border: 0; text-shadow: none; line-height: 1;}';

		$card_style	= '
		.card-content-template { border: 1px solid #ddd; padding: 10px; border-radius:4px; box-sizing: border-box; min-height:122px; margin-bottom: 20px; box-shadow:0 0 6px 0 #999;}
		.card-content-template:after{ content:" "; clear:both; }
		.card-content-template .card-thumbnail{float:left; margin: 0 10px 10px 0;}
		.card-content-template .card-title{font-size:16px; margin: 0; line-height:1.5;}
		.card-content-template .card-except{font-size:14px; margin: 10px 0; overflow: hidden; white-space: nowrap;  text-overflow:ellipsis}
		.card-content-template .card-price{float:left; font-weight:bold;}
		.card-content-template .card-button{ background: #8d4fdb; color: #fff; float: right; margin-right:4px; padding: 2px 4px; border-radius: 4px;}
		';

		$post_password_fields	= [
			'weixin_qrcode'	=> ['title'=>'公众号二维码',	'type'=>'img',		'item_type'=>'url',	'size'=>'160x160'],
			'weixin_tip'	=> ['title'=>'扫码提示文本',	'type'=>'textarea',	'rows'=>3,	'class'=>'',	'value'=>$weixin_tip,	'description'=>'<br />使用[keyword]代替回复关键字'],
			'weixin_reply'	=> ['title'=>'公众号回复内容',	'type'=>'textarea',	'rows'=>3,	'class'=>'',	'value'=>$weixin_reply,	'description'=>'<br />使用[password]代替文章密码'],
			'weixin_style'	=> ['title'=>'前端样式',		'type'=>'textarea',	'value'=>$weixin_style,	'description'=>'也可以修改主题的样式文件']
		];

		$card_template_fields	= [
			'card_style'	=> ['title'=>'',		'type'=>'textarea',	'rows'=>6,	'value'=>$card_style,	'description'=>'也可以修改主题的样式文件']
		];

		$sections['post_password']	= [
			'title'		=>'密码保护', 
			'summary'	=>'设置了密码保护的内容模板，可以公众号通过自定义回复获取密码。',
			'fields'	=>$post_password_fields,	
		];

		$sections['card']	= [
			'title'		=>'卡片样式', 
			'fields'	=>$card_template_fields,	
		];

		if(!did_action('weixin_loaded')){
			unset($sections['post_password']);
		}

		wpjam_register_option('wpjam-content-template', compact('sections'));
	}

	public static function filter_submenu_file($submenu_file, $parent_file){
		$template_types	= WPJAM_Content_Template_Type::get_registereds([], 'names');

		if(in_array(str_replace('wpjam-', '', $GLOBALS['plugin_page']), $template_types)){
			if(!empty($_GET['post_id'])){
				$submenu_file	= $parent_file;
			}
		}

		return $submenu_file;
	}

	public static function add_menu_pages(){
		$title		= self::get_instance()->get_setting('title', '内容模板');
		$post_id	= wpjam_get_data_parameter('post_id');

		$page_title_prefix	= $post_id ? '编辑' : '新建';

		foreach(WPJAM_Content_Template_Type::get_registereds() as $type=>$object){
			if($type == 'content'){
				continue;
			}

			$menu_title	= $object->title;

			if($title == '内容模板'){
				$menu_title	.= '模板';
			}

			wpjam_add_menu_page('wpjam-'.$type, [
				'parent'		=> 'templates',
				'menu_title'	=> '新建'.$menu_title,
				'page_title'	=> $page_title_prefix.$menu_title,
				'template_type'	=> $type,
				'function'		=> 'tab',
				'load_callback'	=> [$object->model, 'load_plugin_page']
			]);
		}

		wpjam_add_menu_page('wpjam-template-setting', [
			'parent'		=> 'templates',
			'menu_title'	=> $title.'设置',
			'function'		=> 'option',
			'option_name'	=> 'wpjam-content-template',
			'load_callback'	=> [self::class, 'load_option_page']
		]);

		add_filter('submenu_file', [self::class, 'filter_submenu_file'], 10, 2);
	}
}