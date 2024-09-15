<?php
class WPJAM_Card_Template{
	public static function get_template($post, $text){
		$content	= $post->post_content ? maybe_unserialize($post->post_content) : [];

		if(!$content){
			return '';
		}

		$card_type	= $content['card_type'] ?? 1;
		$thumbnail	= $content['thumbnail'] ?? '';
		$price		= $content['price'] ?? '';

		$card		= '';

		if($card_type == 1){
			$card	.= '<img preview="disabled" class="card-thumbnail" src="'.wpjam_get_thumbnail($thumbnail, '200x200').'" width="100" height="100" alt="'.esc_attr($post->post_title).'" />';

			if($post->post_title){
				$card	.= '<h3 class="card-title">'.$post->post_title.'</h3>';
			}
				
			if($post->post_excerpt){
				$card	.= '<p class="card-except">'.$post->post_excerpt.'</p>';
			}
			
			if($price){
				$card	.= '
				<span class="card-meta">
					<span class="card-price">￥'.$price.'</span>
					<span class="card-button">去选购</span>
				</span>';
			}
		}else{
			$card	.= '<img preview="disabled" class="card-banner" src="'.wpjam_get_thumbnail($thumbnail, '1200').'" alt="'.esc_attr($post->post_title).'" />';
		}

		$item	= $content['item'] ?? '';

		if($item){
			$platforms		= ['template'];
			$parse_backup	= false;

			if(did_action('weapp_loaded')){
				$platforms[]	= 'weapp';
				$parse_backup	= true;
			}

			$platform	= wpjam_get_current_platform($platforms);
			$parsed		= wpjam_parse_path_item($item, $platform, $parse_backup);
			$card		= wpjam_get_path_item_link_tag($parsed, $card);
		}else{
			$link	= $content['link'] ?? '';
			$weapp	= $content['weapp'] ?? [];

			if(is_weapp()){
				if($weapp['appid'] == 'weapp'){
					$card	= '<a href_type="weapp" href="'.$weapp['path'].'">'.$card.'</a>';
				}elseif($weapp['appid'] == 'webview'){
					$card	= '<a href_type="webview" href="'.$link.'">'.$card.'</a>';
				}else{
					$card	= '<a href_type="miniprograme" href="'.$weapp['path'].'" appid="'.$weapp['appid'].'">'.$card.'</a>';
				}
			}else{
				if($link){
					$card	= '<a href="'.$link.'">'.$card.'</a>';
				}
			}
		}

		return $card;
	}

	public static function register_paths(){
		wpjam_register_path('home',		['path_type'=>'template',	'title'=>'首页',			'path'=>user_trailingslashit(home_url())]);
		wpjam_register_path('category',	['path_type'=>'template',	'title'=>'分类页',		'path'=>'',	'page_type'=>'taxonomy',	'taxonomy'=>'category']);
		wpjam_register_path('post_tag',	['path_type'=>'template',	'title'=>'标签页',		'path'=>'',	'page_type'=>'taxonomy',	'taxonomy'=>'post_tag']);
		wpjam_register_path('author',	['path_type'=>'template',	'title'=>'作者页',		'path'=>'',	'page_type'=>'author']);
		wpjam_register_path('post',		['path_type'=>'template',	'title'=>'文章详情页',	'path'=>'',	'page_type'=>'post_type',	'post_type'=>'post']);
		wpjam_register_path('external', ['path_type'=>'template',	'title'=>'外部链接',		'path'=>'',	'fields'=>[
			'url'	=> ['title'=>'',	'type'=>'url',	'required'=>true,	'placeholder'=>'请输入外部链接地址，仅适用网页版。']
		]]);
	}

	public static function get_fields($action_key=''){
		$post_id	= wpjam_get_data_parameter('post_id');

		if($post_id){
			$post	= get_post($post_id);

			if(empty($post)){
				return new WP_Error('invaild_post_id', '无效的 post_id');
			}
		}

		if($action_key == 'save_setting'){
			if($post_id){
				$post_title		= $post->post_title;
				$post_name		= $post->post_name;
				$post_content	= $post->post_content;
				$content		= maybe_unserialize($post_content);
				$card_type		= $content['card_type'] ?? 1;
			}else{
				$post_title		= $post_name = '';
				$card_type		= 1;
			}

			$card_types	= [
				1=>'小图模式：图片显示在左侧，尺寸为200x200。',
				2=>'大图模式：图片全屏显示，高度自适应。'
			];

			return [
				'post_title'	=> ['title'=>'名称',	'type'=>'text',		'value'=>$post_title],
				'post_name'		=> ['title'=>'标识',	'type'=>'text',		'value'=>$post_name],
				'card_type'		=> ['title'=>'样式',	'type'=>'radio',	'value'=>$card_type,	'options'=>$card_types,	'sep'=>'<br /><br />'],
			];
		}else{
			$content	= $post->post_content ? maybe_unserialize($post->post_content) : [];
			$card_type	= $content['card_type'] ?? 1;
			$excerpt	= $post->post_excerpt;
			$thumbnail	= $content['thumbnail'] ?? '';
			$price		= $content['price'] ?? '';
			$item		= $content['item'] ?? [];

			$fields		= [
				'thumbnail'		=> ['title'=>'图片',	'type'=>'img',	'value'=>$thumbnail,	'item_type'=>'url',	'size'=>'200x200'],
				'post_excerpt'	=> ['title'=>'简介',	'type'=>'text',	'value'=>$excerpt,		'placeholder'=>'一句话简介...'],
				'price'			=> ['title'=>'价格',	'type'=>'text',	'value'=>$price,		'class'=>'',	'description'=>'输入价格会显示「去选购」按钮'],
			];

			if($card_type == 2){
				$fields['thumbnail']['size']	= '1200x0';
				$fields	= wpjam_array_except($fields, ['post_excerpt', 'price']);
			}

			$platforms	= ['template'];

			if(did_action('weapp_loaded')){
				$platforms[]	= 'weapp';
			}

			foreach(wpjam_get_path_fields($platforms) as $path_key => $path_field){
				if($path_field['type'] == 'fieldset'){
					foreach ($path_field['fields'] as $sub_key => &$field){
						$field['name']	= 'item['.$sub_key.']';
						$field['value']	= $item[$sub_key] ?? '';
					}
				}else{
					$path_field['name']		= 'item['.$path_key.']';
					$path_field['value']	= $item[$path_key] ?? '';
				}

				$fields[$path_key]	= $path_field;
			}

			return $fields;
		}
	}

	public static function page_action($action_key=''){
		$post_id		= wpjam_get_data_parameter('post_id');

		if($post_id){
			$post	= get_post($post_id);

			if(empty($post)){
				return new WP_Error('invaild_post_id', '无效的 post_id');
			}
		}

		if($action_key == 'save_setting'){
			$post_title		= wpjam_get_data_parameter('post_title');
			$post_name		= wpjam_get_data_parameter('post_name');
			$post_status	= 'publish';
	 
			$card_type		= wpjam_get_data_parameter('card_type', ['sanitize_callback'=>'intval',	'default'=>1]);
			$meta_input		= ['_template_type'=>'card'];

			if($post_id){
				$post_content	= $post->post_content;
				$content		= maybe_unserialize($post_content);
				$content		= array_merge($content, compact('card_type'));
				$post_content	= maybe_serialize($content);

				return WPJAM_Post::update($post_id, compact('post_title', 'post_name', 'post_content', 'post_status', 'meta_input'));
			}else{
				$post_type		= 'template';
				$post_content	= maybe_serialize(compact('card_type'));
				$post_id		= WPJAM_Post::insert(compact('post_type', 'post_title', 'post_name', 'post_content', 'post_status', 'meta_input'));

				return is_wp_error($post_id) ? $post_id : ['type'=>'redirect', 'url'=>admin_url('edit.php?post_type=template&page=wpjam-card&post_id='.$post_id)];
			}
		}else{
			$content	= maybe_unserialize($post->post_content);

			$content['thumbnail']	= wpjam_get_data_parameter('thumbnail',	['default'=>'']);
			$content['price']		= wpjam_get_data_parameter('price',		['default'=>'']);
			$content['item']		= wpjam_get_data_parameter('item');

			$post_content	= maybe_serialize($content);
			$post_excerpt	= wpjam_get_data_parameter('post_excerpt', ['default'=>'']);
			
			return WPJAM_Post::update($post_id, compact('post_excerpt', 'post_content'));
		}		
	}

	public static function load_plugin_page(){
		if($post_id = wpjam_get_data_parameter('post_id')){
			wpjam_register_plugin_page_tab('setting',	[
				'title'			=> '卡片设置',
				'function'		=> 'form',
				'form_name'		=> 'save_setting',
				'query_args'	=> ['post_id']
			]);

			wpjam_register_plugin_page_tab('content',	[
				'title'			=> '卡片内容',
				'function'		=> 'form',
				'form_name'		=> 'save_content',
				'query_args'	=> ['post_id']
			]);

			wpjam_register_page_action('save_content', [
				'submit_text'	=> '保存',
				'callback'		=> [self::class, 'page_action'],
				'fields'		=> [self::class, 'get_fields'],
			]);
		}else{
			wpjam_register_plugin_page_tab('setting',	[
				'title'			=> '新建卡片',
				'function'		=> 'form',
				'form_name'		=> 'save_setting'
			]);
		}

		wpjam_register_page_action('save_setting', [
			'submit_text'	=> $post_id ? '编辑' : '新建',
			'callback'		=> [self::class, 'page_action'],
			'fields'		=> [self::class, 'get_fields']
		]);
	}
}