<?php
class WPJAM_Format{
	public static function get_supports(){
		return ['image', 'gallery', 'link', 'video', 'audio'];
	}

	public static function on_after_setup_theme(){
		add_theme_support('post-formats', self::get_supports());
	}

	public static function on_builtin_page_load($screen_base, $current_screen){
		if(!in_array($screen_base, ['edit', 'post']) || $current_screen->post_type != 'post'){
			return;
		}

		add_filter('gettext_with_context',	['WPJAM_Format', 'filter_gettext_with_context'], 10, 3);

		if($screen_base == 'edit'){
			$post_format_taxonomy	= get_taxonomy('post_format');
			$post_format_taxonomy->show_admin_column	= true;

			add_filter('post_column_taxonomy_links',	['WPJAM_Format', 'filter_taxonomy_links'], 10, 2);

			wp_add_inline_style('list-tables', 'th.column-taxonomy-post_format{width: 5.5em;}');

			wpjam_register_list_table_action('add', [
				'title'			=> '写文章', 
				'page_title'	=> '选择格式', 
				'submit_text'	=> '新建',	
				'capability'	=> 'edit_posts',
				'response'		=> 'redirect',
				'fields'		=> ['WPJAM_Format', 'get_fields'],
				'callback'		=> ['WPJAM_Format', 'add_post']
			]);
		}elseif($screen_base == 'post'){
			$post_id	= wpjam_get_admin_post_id();
			$format		= $post_id ? get_post_format($post_id) : '';

			if($current_screen->action != 'add' && $format){
				$data	= self::get_content($post_id);

				if(!empty($data) || get_post($post_id)->post_content == ''){
					add_filter('use_block_editor_for_post_type', '__return_false');

					remove_post_type_support('post', 'editor');
					remove_post_type_support('post', 'custom-fields');

					remove_meta_box('formatdiv', 'post', 'side');

					add_filter('wp_insert_post_data',	['WPJAM_Format', 'filter_insert_post_data'], 10, 2);

					wpjam_register_post_option('format_meta_box', [
						'title'				=> get_post_format_string($format).'设置',
						'post_type'			=> 'post',
						'context'			=> 'normal',
						'priority'			=> 'high',
						'update_callback'	=> '__return_null',
						'data'				=> $format == 'image' ? ['images'=>$data] : $data,
						'fields'			=> ['WPJAM_Format','get_fields']
					]);
				}	
			}else{
				remove_post_type_support('post', 'post-formats');
			}
		}
	}
	
	public static function get_content($post=null){
		$post	= get_post($post);
		$format	= get_post_format($post);

		if($format){
			if(!isset($post->format_content)){
				$format_content	= maybe_unserialize($post->post_content);

				if($format_content && is_array($format_content)){
					$post->format_content	= $format_content;	
				}else{
					$post->format_content	= [];
				}
			}

			return $post->format_content;
		}else{
			return [];
		}
	}

	public static function filter_insert_post_data($data, $postarr){
		$post_id	= $postarr['ID'] ?? 0;

		if($post_id && ($format	= get_post_format($post_id))){
			$fields	= self::get_fields($post_id);
			$value	= wpjam_validate_fields_value($fields);

			if($format == 'image'){
				if($value['images']){
					$data['post_content']	= wp_slash(maybe_serialize($value['images']));
				}
			}else{
				$data['post_content']	= wp_slash(maybe_serialize($value));	
			}
		}

		return $data;
	}

	public static function get_fields($post_id){
		if($post_id == 0){
			$options	= [''=>' <span class="post-format-icon post-format-standard"></span> '.'默认'];

			foreach(get_theme_support('post-formats')[0] as $format) {
				$options[$format]	=  ' <span class="post-format-icon post-format-'.$format.'"></span> '.get_post_format_string($format);
			}
			
			return ['post_format'	=> ['title'=>'',	'type'=>'radio',	'options'=>$options,	'sep'=>'<br /><br />']];
		}else{
			$format	= get_post_format($post_id);

			if($format == 'image'){
				return [
					'images'	=> ['title'=>'',	'type'=>'mu-fields',	'fields'=>[
						'image'		=> ['title'=>'图片',	'type'=>'img',		'item_type'=>'url'],		
						'text'		=> ['title'=>'说明',	'type'=>'textarea',	'rows'=>4],
						// 'featured'	=> ['title'=>'头图',	'type'=>'checkbox',	'class'=>'featured',	'description'=>'设为头图，默认不设则为第一张'],
					]]
				];
			}elseif($format == 'gallery'){
				return [
					'images'	=> ['title'=>'图片',	'type'=>'mu-img',	'item_type'=>'url',	'description'=>'前端显示为两个一组'],
					'text'		=> ['title'=>'说明',	'type'=>'textarea',	'rows'=>5],
				];
			}elseif($format == 'link'){
				return [
					'image'		=> ['title'=>'图片',	'type'=>'img',		'item_type'=>'url'],
					'link'		=> ['title'=>'链接',	'type'=>'url',		'class'=>'large-text',	'placeholder'=>'请输入和小程序关联的网站或者公众号链接'],
					'text'		=> ['title'=>'说明',	'type'=>'textarea',	'rows'=>5],
				];
			}elseif($format == 'video'){
				return [
					'image'		=> ['title'=>'图片',	'type'=>'img',		'item_type'=>'url'],
					'video'		=> ['title'=>'视频',	'type'=>'file',		'class'=>'',	'style'=>'width:80%',	'placeholder'=>'请输入视频文件源地址'],
					'text'		=> ['title'=>'说明',	'type'=>'textarea',	'rows'=>5],
				];
			}elseif($format == 'audio'){
				return [
					'image'		=> ['title'=>'图片',	'type'=>'img',		'item_type'=>'url'],
					'audio'		=> ['title'=>'音频',	'type'=>'file',		'class'=>'large-text',	'placeholder'=>'请输入音频文件源地址'],
					'text'		=> ['title'=>'说明',	'type'=>'textarea',	'rows'=>5],
				];
			}else{
				return [];
			}
		}	
	}

	public static function add_post($post_id, $data){
		$format	= $data['post_format'] ?? '';

		if($format){
			$post	= get_default_post_to_edit('post', true);

			set_post_format($post, $format);

			return admin_url('post.php?post='.$post->ID.'&action=edit');
		}else{
			return admin_url('post-new.php');
		}
	}

	public static function filter_gettext_with_context($translation, $text, $context){
		if($context == 'Post format' && $text == 'Image'){
			return '图集';
		}

		return $translation;
	}

	public static function filter_post_link($post_link, $post){
		if(!is_single($post->ID)){
			$format	= get_post_format($post);

			if($format == 'link'){
				$format_content	= self::get_content($post);

				if($format_content && isset($format_content['link'])){
					return $format_content['link'];	
				}else{
					return '';
				}
			}
		}

		return $post_link;
	}

	public static function filter_excerpt($text, $post=null){
		if(empty($text)){
			if($format_content = self::get_content($post)){
				if(get_post_format($post) == 'image'){
					$text	= $format_content[0]['text'] ?? '';
				}else{
					$text	= $format_content['text'] ?? '';
				}

				remove_all_filters('get_the_excerpt');
			}
		}

		return $text ? wp_strip_all_tags($text, true) : '';
	}

	public static function filter_content($content){
		if(doing_filter('get_the_excerpt')){ 
			return $content;
		}

		$post	= get_post();

		$format_content	= self::get_content($post);

		if(empty($format_content)){
			return $content;
		}

		$format	= get_post_format($post);

		if($format == 'image'){
			$content	= '';
			foreach ($format_content as $item) {
				if(!empty($item['image'])){
					$content	.= '<img src="'.wpjam_get_thumbnail($item['image'], 1200).'" alt="'.($item['text'] ?? '').'" />'."\n";
				}

				if(!empty($item['text'])){
					$content	.= $item['text']."\n";
				}

				$content	.= "\n";
			}

			return wpautop($content);
		}elseif($format == 'gallery'){
			$images	= $format_content['images'] ?? [];
			$text	= $format_content['text'] ?? '';

			$gallery	= '';

			if($images){
				$count	= count($images);

				if($count == 1){
					$gallery	= '<img src="'.wpjam_get_thumbnail($images[0], 1200).'" alt="'.$post->post_title.'">';
				}else{
					$per 	= ($count == 4) ? 2 : 3;
					$i		= 0;
					foreach ($images as $image) {
						$i ++;

						if($count == 2 || $count == 4){
							$style	= ' style="float:left; margin: 0 0 4% 0;  width:48%; paddding: 0;"';

							if($i%$per != 0){
								$style	= ' style="float:left; margin:0 4% 4% 0;  width:48%; paddding: 0;"';
							}

							$width	= 400;
						}else{
							$style	= ' style="float:left; margin: 0 0 2% 0;  width:32%; paddding: 0;"';

							if($i%$per != 0){
								$style	= ' style="float:left; margin:0 2% 2% 0;  width:32%; paddding: 0;"';
							}

							$width	= 300;
						}

						$gallery	.= '
						<dl class="gallery-item"'.$style.'>
							<dt class="gallery-icon">
								<a data-fancybox="gallery" href="'.wpjam_get_thumbnail($image, 1200).'"><img src="'.wpjam_get_thumbnail($image, [$width,$width]).'" alt="'.$post->post_title.'"></a>
							</dt>
						</dl>
						';

						if($i%$per == 0 && $i != $count){
							$gallery .= '<dl style="clear: both; margin:0; padding:0;"></dl>';
						}
					}

					$gallery .= '<dl style="clear: both; margin:0; padding:0;"></dl>';
					$gallery = '<div class="gallery" >'.$gallery.'</div>';	
				}
			}

			return $gallery.wpautop($text);
		}elseif($format == 'link'){
			$link	= $format_content['link'] ?? '';
			$text	= $format_content['text'] ?? '';

			return wpautop($text."\n\n".'<a  href="'.$link.'">戳这里查看详情</a>。');
		}elseif($format == 'video'){
			$video	= $format_content['video'] ?? '';
			$image	= $format_content['image'] ?? '';
			$text	= $format_content['text'] ?? '';

			if($video){
				if(wp_is_mobile()){
					$width	= '100%';
					$height	= 200;
				}else{
					$width	= 640;
					$height	= 400;
				}

				if(preg_match('#https://v.qq.com/x/page/(.*?).html#i',$video, $matches)){
					$qq_vid	= $matches[1];
				}elseif(preg_match('#https://v.qq.com/x/cover/.*/(.*?).html#i',$video, $matches)){
					$qq_vid	= $matches[1];
				}elseif(!filter_var($video, FILTER_VALIDATE_URL)){ 
					$qq_vid	= $video;
				}else{
					$qq_vid	= '';
				}

				if($qq_vid){
					$video	= '<iframe frameborder="0" width="'.$width.'" height="'.$height.'" src="https://v.qq.com/txp/iframe/player.html?vid='.$qq_vid.'" allowFullScreen="true"></iframe>'."\n\n";
				}else{
					$video	= '<video controls width="'.$width.'" height="'.$height.'"  poster="'.$image.'" src="'.$video.'"></video>'."\n\n";
				}
			}

			return wpautop($video.$text);
		}elseif($format == 'audio'){
			$audio	= $format_content['audio'] ?? '';
			$image	= $format_content['image'] ?? '';
			$text	= $format_content['text'] ?? '';

			if($audio){
				$audio	= '<audio controls src="'.$audio.'"></audio>'."\n\n";
			}

			return wpautop($audio.$text);
		}
	}

	public static function filter_post_thumbnail_url($thumbnail_url, $post){
		if($format_content	= self::get_content($post)){
			$format	= get_post_format($post);

			if($format == 'image'){
				foreach ($format_content as $item) {
					if(!empty($item['featured'])){
						return $item['image'];
					}
				}
				
				if($format_content[0]['image']){
					return $format_content[0]['image'];
				}
			}elseif($format == 'gallery'){
				if($images	= $format_content['images'] ?? []){
					return $images[0];
				}
			}else{
				if(!empty($format_content['image'])){
					return $format_content['image'];
				}
			}
		}

		return $thumbnail_url;
	}

	public static function filter_post_json($post_json, $post_id){
		$post_json['editable']	= false;

		$format = $post_json['format'] ?? '';

		if(empty($format)){
			return $post_json;
		}

		$format_content	= self::get_content($post_id);

		if(empty($format_content)){
			return $post_json;
		}

		if($user_id	= get_current_user_id()){
			if($post_json['author']['id'] == $user_id){
				if($post_json['status'] == 'publish' && current_user_can('edit_published_posts')){
					$post_json['editable']	= true;
				}elseif(current_user_can('edit_posts')){
					$post_json['editable']	= true;
				}
			}
		}

		if($format == 'image'){
			if(is_singular('post')){
				$images	= [];
				foreach($format_content as $item) {
					$image = $item['image'] ?? '';
					$images[]	=[
						'image'	=> $image ? wpjam_get_thumbnail($image, 1200) : '', 
						'raw'	=> $image,
						'text'	=> $item['text'] ?? ''
					];
				}

				$post_json['format_content']	= compact('images'); 
			}
		}elseif($format == 'gallery'){
			if(is_singular('post')){
				$images	= $format_content['images'] ?? [];
				$text	= $format_content['text'] ?? '';

				$gallery	= [];

				if($images){
					$count	= count($images);

					if($count == 1){
						$width	= 1200;
						$height	= 0;
					}elseif($count == 2 || $count == 4){
						$width	= $height = 400;
					}else{
						$width	= $height = 300;
					}

					$gallery	= array_map(function($image) use($width, $height){
						return [
							'thumbnail'	=> wpjam_get_thumbnail($image,  [$width, $height]), 
							'image'		=> wpjam_get_thumbnail($image, 1200),
							'raw'		=> wpjam_get_thumbnail($image)
						];
					}, $images);
				}

				$post_json['format_content']	= compact('gallery', 'text');
			}
		}elseif($format == 'link'){
			$text	= $format_content['text'] ?? '';
			$link	= $format_content['link'] ?? '';

			$post_json['format_content']	= compact('link', 'text');

			$post_json['editable']	= false;
		}elseif($format == 'video'){
			if(is_singular('post')){
				$text	= $format_content['text'] ?? '';
				$video	= $format_content['video'] ?? '';
				$raw	= $format_content['image'] ?? '';
				$poster	= $raw ? wpjam_get_thumbnail($raw, '640x400') : '';
				
				$video	= wpjam_get_video_mp4($video);

				$post_json['format_content']	= compact('video', 'poster', 'text');
			}

			$post_json['editable']	= false;
		}elseif($format == 'audio'){
			if(is_singular('post')){
				$title	= $post_json['title'];
				$text	= $format_content['text'] ?? '';
				$audio	= $format_content['audio'] ?? '';
				$raw	= $format_content['image'] ?? '';
				$poster	= $raw ? wpjam_get_thumbnail($raw, '100x100') : '';

				$post_json['format_content']	= compact('audio', 'poster', 'text', 'title');
			}

			$post_json['editable']	= false;
		}else{
			$post_json['editable']	= false;
		}

		return $post_json;
	}

	public static function filter_related_post_json($post_json, $post_id){
		$format	= get_post_format($post_id);

		$post_json['format']	= $format;

		if($format == 'link'){
			$format_content	= self::get_content(get_post($post_id));

			$text	= $format_content['text'] ?? '';
			$link	= $format_content['link'] ?? '';

			$post_json['post_url']	= $link;
			$post_json['format_content']	= compact('link', 'text');
		}

		return $post_json;
	}

	public static function filter_taxonomy_links($term_links, $taxonomy){
		if($taxonomy == 'post_format'){
			foreach($term_links as &$term_link){
				$term_link	= str_replace('post-format-', '', $term_link);
			}
		}

		return $term_links;
	}

	public static function get_parameters($format){
		if($format == 'gallery'){
			$text		= wpjam_get_parameter('text',	['method'=>'POST',	'sanitize_callback'=>'sanitize_textarea_field']);
			$images		= wpjam_get_parameter('images',	['method'=>'POST',	'required'=>true,	'sanitize_callback'=>['WPJAM_Format', 'gallery_sanitize_callback']]);

			$content	= compact('images', 'text');
		}elseif($format == 'video'){
			$image		= wpjam_get_parameter('image',	['method'=>'POST',	'required'=>true,	'sanitize_callback'=>'esc_url_raw']);
			$video		= wpjam_get_parameter('video',	['method'=>'POST',	'required'=>true,	'sanitize_callback'=>'esc_url_raw']);
			$text		= wpjam_get_parameter('text',	['method'=>'POST',	'sanitize_callback'=>'sanitize_textarea_field']);
			$content	= compact('image', 'video', 'text');
		}elseif($format == 'image'){
			$content	= wpjam_get_parameter('images',	['method'=>'POST', 'required'=>true,	'sanitize_callback'=>['WPJAM_Format', 'image_sanitize_callback']]);
		}else{
			return new WP_Error('invalid_format', '无效的文章格式');
		}

		$post_content	= maybe_serialize(wp_unslash($content));
		$post_title		= wpjam_get_parameter('title',	['method'=>'POST', 'required'=>true]);
		$post_status	= wpjam_get_parameter('status',	['method'=>'POST', 'required'=>true]);
		$category_ids	= wpjam_get_parameter('category_ids',	['method'=>'POST', 'required'=>true]);

		if(!current_user_can('publish_posts') && $post_status == 'publish'){
			$post_status = 'pending';
		}

		$tax_input		= ['category'=>$category_ids];

		return compact('post_title', 'post_content', 'post_status', 'tax_input');
	}

	public static function gallery_sanitize_callback($value){
		return array_filter(array_map('esc_url_raw', $value));
	}

	public static function image_sanitize_callback($value){
		return array_map(function($item){
			$item['image']	= esc_url_raw($item['image']);
			$item['text']	= sanitize_textarea_field($item['text']);

			return array_filter($item);
		}, $value);

		return array_filter($value);
	}

	public static function register_api($json){
		if($json == 'post.create'){
			if(!is_user_logged_in() || !current_user_can('edit_posts')){
				return new WP_Error('bad_authentication', '无权限');
			}

			wpjam_register_api('post.create', [
				'title'		=> '创建文章',
				'auth'		=> true,
				'callback'	=> ['WPJAM_Format', 'api_create_post']
			]);
		}elseif($json == 'post.update'){
			wpjam_register_api('post.update', [
				'title'		=> '编辑文章',
				'auth'		=> true,
				'callback'	=> ['WPJAM_Format', 'api_update_post']
			]);
		}elseif($json == 'post.create.list'){
			if(!is_user_logged_in() || !current_user_can('edit_posts')){
				return new WP_Error('bad_authentication', '无权限');
			}

			wpjam_register_api('post.create.list', [
				'title'			=> '我的文章',
				'page_title'	=> '我的文章',
				'share_title'	=> '我的文章',
				'auth'			=> true,
				'modules'		=> [[
					'type'	=> 'post_type',
					'args'	=> [
						'post_type'		=>'post',
						'post_status'	=>'any',
						'action'		=>'list',
						'thumbnail_size'=>'750x300',
						'author'		=>get_current_user_id(),
						'tax_query'		=>[['taxonomy'=>'post_format', 'field'=>'slug', 'terms'=>['post-format-image', 'post-format-gallery']]]
					]
				]]
			]);
		}
	}

	public static function api_create_post(){
		$format	= wpjam_get_parameter('format', ['method'=>'POST', 'required'=>true]);
		$data	= self::get_parameters($format);

		if(is_wp_error($data)){
			return $post_content;
		}

		$post_id	= WPJAM_Post::insert(array_merge($data+['post_author'=>get_current_user_id()]));

		if(is_wp_error($post_id)){
			return $post_id;
		}

		set_post_format($post_id, $format);

		$post_status	= get_post_status($post_id);

		if($post_status == 'publish'){
			$errmsg	= '文章发布成功';
		}elseif($post_status == 'draft'){
			$errmsg	= '草稿创建成功';
		}elseif($post_status == 'pending'){
			$errmsg	= '文章等待待审中';
		}

		return [
			'errcode'		=> 0,
			'errmsg'		=> $errmsg,
			'post_id'		=> $post_id,
			'post_status'	=> $post_status
		];
	}

	public static function api_update_post(){
		$post_id	= wpjam_get_parameter('post_id', ['method'=>'POST', 'type'=>'int', 'required'=>true]);
		$the_post	= wpjam_validate_post($post_id);

		if(is_wp_error($the_post)){
			return $the_post;
		}

		$user_id	= get_current_user_id();

		if($user_id != $the_post->post_author){
			return new WP_Error('not_allowed', '无权限');
		}

		$old_status	= $the_post->post_status ;

		if(!current_user_can('edit_published_posts') && $old_status == 'publish'){
			return new WP_Error('not_allowed', '你无权修改已发布的文章');
		}

		$format	= get_post_format($the_post);
		$data	= self::get_parameters($format);

		if(is_wp_error($data)){
			return $data;
		}

		$post_id	= WPJAM_Post::update($post_id, $data);

		if(is_wp_error($post_id)){
			return $post_id;
		}

		$post_status	= get_post_status($post_id);

		if($post_status == 'publish'){
			$errmsg	= ($old_status == 'publish' ? '文章修改成功' : '文章发布成功');
		}elseif($post_status == 'draft'){
			$errmsg	= ($old_status == 'draft' ? '草稿修改成功' : '文章设置为草稿');
		}elseif($post_status == 'pending'){
			$errmsg	= '文章等待审中';
		}

		return [
			'errcode'		=> 0,
			'errmsg'		=> $errmsg,
			'post_id'		=> $post_id,
			'post_status'	=> $post_status
		];
	}
}