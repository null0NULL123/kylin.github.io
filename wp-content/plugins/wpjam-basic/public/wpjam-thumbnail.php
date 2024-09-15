<?php
class WPJAM_Thumbnail_Setting{
	use WPJAM_Setting_Trait;

	private function __construct(){
		$this->init('wpjam-thumbnail');
	}

	public function get_fields(){
		$tax_options	= [];

		foreach(get_object_taxonomies('post', 'objects') as $tax => $object){
			if($object->show_ui && $object->public){
				$tax_options[$tax]	= ['title'=>$object->label,	'show_if'=>['key'=>'term_thumbnail_taxonomies','value'=>$tax,'postfix'=>'']];
			}
		}

		$order_options	= [
			''			=> '请选择来源',
			'first'		=> '第一张图',
			'post_meta'	=> '自定义字段',
			'term'		=>[
				'title'		=> '分类缩略图',
				'show_if'	=> ['key'=>'term_thumbnail_type', 'compare'=>'IN', 'value'=>['img','image'], 'postfix'=>'']
			]
		];

		$term_show_if	= ['key'=>'term_thumbnail_type', 'compare'=>'!=', 'value'=>''];

		return [
			'auto'		=> ['title'=>'缩略图设置',	'type'=>'radio',	'sep'=>'<br />',	'options'=>[
				0	=>'修改主题代码，手动使用 <a href="https://blog.wpjam.com/m/wpjam-basic-thumbnail-functions/" target="_blank">WPJAM 的相关缩略图函数</a>。',
				1	=>'无需修改主题，程序自动使用 WPJAM 的缩略图设置。'
			]],
			'default'	=> ['title'=>'默认缩略图',	'type'=>'mu-img',	'item_type'=>'url'],
			'term_set'	=> ['title'=>'分类缩略图',	'type'=>'fieldset',	'fields'=>[
				'term_thumbnail_type'		=> ['type'=>'select',	'options'=>[''=>'关闭分类缩略图', 'img'=>'本地媒体模式', 'image'=>'输入图片链接模式']],
				'term_thumbnail_taxonomies'	=> ['type'=>'checkbox',	'show_if'=>$term_show_if,	'title'=>'支持的分类模式：',	'options'=>wp_list_pluck($tax_options, 'title')],
				'term_thumbnail_width'		=> ['type'=>'number',	'show_if'=>$term_show_if,	'group'=>'term',	'title'=>'缩略图尺寸：',	'class'=>'small-text'],
				'term_thumbnail_plus'		=> ['type'=>'view',		'show_if'=>$term_show_if,	'group'=>'term',	'title'=>'<span class="dashicons dashicons-no-alt"></span>'],
				'term_thumbnail_height'		=> ['type'=>'number',	'show_if'=>$term_show_if,	'group'=>'term',	'class'=>'small-text']
			]],
			'post_set'	=> ['title'=>'文章缩略图',	'type'=>'fieldset',	'fields'=>[
				'post_thumbnail_view'	=> ['type'=>'view',	'value'=>'首先使用文章特色图片，如未设置，将按照下面的顺序获取：'],
				'post_thumbnail_orders'	=> ['type'=>'mu-fields',	'group'=>true,	'max_items'=>5,	'fields'=>[
					'type'		=> ['type'=>'select',	'class'=>'post_thumbnail_order_type',		'options'=>$order_options],
					'taxonomy'	=> ['type'=>'select',	'class'=>'post_thumbnail_order_taxonomy',	'show_if'=>['key'=>'type', 'value'=>'term'],	'options'=>[''=>'请选择分类模式']+$tax_options],
					'post_meta'	=> ['type'=>'text',		'class'=>'post_thumbnail_order_post_meta all-options',	'show_if'=>['key'=>'type', 'value'=>'post_meta'],	'placeholder'=>'请输入自定义字段的 meta_key'],
				]]
			]]
		];
	}
}

class WPJAM_Thumbnail{
	public static function has_term_thumbnail($taxonomy){
		if($taxonomies = wpjam_thumbnail_get_setting('term_thumbnail_taxonomies')){
			if(in_array($taxonomy, $taxonomies) && is_object_in_taxonomy('post', $taxonomy)){
				return true;
			}
		}

		return false;
	}

	public static function filter_term_thumbnail_url($thumbnail_url, $term){
		if(self::has_term_thumbnail($term->taxonomy)){
			return get_term_meta($term->term_id, 'thumbnail', true);
		}

		return $thumbnail_url;
	}

	public static function filter_post_thumbnail_url($thumbnail_url, $post){
		if(!is_object_in_taxonomy($post, 'category')){
			return $thumbnail_url;
		}

		foreach(wpjam_thumbnail_get_setting('post_thumbnail_orders', []) as $order){
			if($order['type'] == 'first'){
				if($value = wpjam_get_post_first_image_url($post)){
					return $value;
				}
			}elseif($order['type'] == 'post_meta'){
				if($order['post_meta']){
					if($value = get_post_meta($post->ID, $order['post_meta'], true)){
						return $value;
					}
				}
			}elseif($order['type'] == 'term'){
				if($order['taxonomy'] && is_object_in_taxonomy($post, $order['taxonomy'])){
					if($terms = get_the_terms($post, $order['taxonomy'])){
						foreach($terms as $term){
							if($value = wpjam_get_term_thumbnail_url($term)){
								return $value;
							}
						}
					}
				}
			}
		}

		return $thumbnail_url ?: wpjam_get_default_thumbnail_url();
	}

	public static function filter_has_post_thumbnail($has_thumbnail, $post){
		return $has_thumbnail ?: (bool)wpjam_get_post_thumbnail_url($post);
	}

	public static function filter_post_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr){
		if(!post_type_supports(get_post_type($post_id), 'thumbnail') || empty($html)){
			$thumbnail_url	= wpjam_get_post_thumbnail_url($post_id, wpjam_parse_size($size, 2));

			if(!$thumbnail_url){
				return $html;
			}

			$size_class		= $size;

			if(is_array($size_class)){
				$size_class	= join('x', $size_class);
			}

			$default_attr	= [
				'src'	=> $thumbnail_url,
				'class'	=> "attachment-$size_class size-$size_class wp-post-image"
			];

			if(wp_lazy_loading_enabled('img', 'wp_get_attachment_image')){
				$default_attr['loading']	= 'lazy';
			}

			$attr	= wp_parse_args($attr, $default_attr);

			if(array_key_exists('loading', $attr) && !$attr['loading']){
				unset($attr['loading']);
			}

			$size		= wpjam_parse_size($size);
			$hwstring	= image_hwstring($size['width'], $size['height']);

			$attr	= array_map('esc_attr', $attr);
			$html	= rtrim("<img $hwstring");

			foreach($attr as $name => $value){
				$html	.= " $name=" . '"' . $value . '"';
			}

			$html	.= ' />';
		}

		return $html;
	}

	public static function on_builtin_page_load($screen_base, $current_screen){
		if(in_array($screen_base, ['edit-tags', 'term']) && self::has_term_thumbnail($current_screen->taxonomy)){
			$field	= ['title'=>'缩略图',	'width'=>500,	'list_table'=>true,	'row_action'=>false];

			if(wpjam_thumbnail_get_setting('term_thumbnail_type') == 'img'){
				$field['type']		= 'img';
				$field['item_type']	= 'url';

				$width	= wpjam_thumbnail_get_setting('term_thumbnail_width', 200);
				$height	= wpjam_thumbnail_get_setting('term_thumbnail_height', 200);

				if($width || $height){
					$field['size']			= $width.'x'.$height;
					$field['description']	= '尺寸：'.$field['size'];
				}
			}else{
				$field['type']	= 'image';
			}

			wpjam_register_term_option('thumbnail', $field);
		}
	}
}

function wpjam_thumbnail_get_setting($name, $default=null){
	return WPJAM_Thumbnail_Setting::get_instance()->get_setting($name, $default);
}

// 1. $img_url 
// 2. $img_url, array('width'=>100, 'height'=>100)	// 这个为最标准版本
// 3. $img_url, 100x100
// 4. $img_url, 100
// 5. $img_url, array(100,100)
// 6. $img_url, array(100,100), $crop=1, $ratio=1
// 7. $img_url, 100, 100, $crop=1, $ratio=1
function wpjam_get_thumbnail($img_url, ...$args){
	$img_url	= wpjam_zh_urlencode($img_url);	// 中文名
	$args_num	= count($args);

	if($args_num == 0){
		// 1. $img_url 简单替换一下 CDN 域名

		$thumb_args = [];
	}elseif($args_num == 1){
		// 2. $img_url, ['width'=>100, 'height'=>100]	// 这个为最标准版本
		// 3. $img_url, [100,100]
		// 4. $img_url, 100x100
		// 5. $img_url, 100

		$thumb_args = wpjam_parse_size($args[0]);
	}else{
		if(is_numeric($args[0])){
			// 6. $img_url, 100, 100, $crop=1, $ratio=1

			$width	= $args[0] ?? 0;
			$height	= $args[1] ?? 0;
			$crop	= $args[2] ?? 1;
			// $ratio	= $args[4] ?? 1;
		}else{
			// 7. $img_url, array(100,100), $crop=1, $ratio=1

			$size	= wpjam_parse_size($args[0]);
			$width	= $size['width'];
			$height	= $size['height'];
			$crop	= $args[1] ?? 1;
			// $ratio	= $args[3]??1;
		}

		// $width		= (int)($width)*$ratio;
		// $height		= (int)($height)*$ratio;

		$thumb_args = compact('width','height','crop');
	}

	$thumb_args	= apply_filters('wpjam_thumbnail_args', $thumb_args);

	return apply_filters('wpjam_thumbnail', $img_url, $thumb_args);
}

function wpjam_parse_size($size, $ratio=1){
	$max_width	= $GLOBALS['content_width'] ?? 0;
	$max_width	= (int)($max_width*$ratio);

	$_wp_additional_image_sizes = wp_get_additional_image_sizes();

	if(is_array($size)){
		if(wpjam_is_assoc_array($size)){
			$size['width']	= !empty($size['width']) ? $size['width']*$ratio : 0;
			$size['height']	= !empty($size['height']) ? $size['height']*$ratio : 0;
			$size['crop']	= $size['crop'] ?? (!empty($size['width']) && !empty($size['height']));

			return $size;
		}else{
			$width	= (int)($size[0]??0);
			$height	= (int)($size[1]??0);
		}
	}else{
		$size	= strtolower($size);
		$size	= str_replace('*', 'x', $size);

		if(strpos($size, 'x')){
			$size	= explode('x', $size);
			$width	= (int)$size[0];
			$height	= (int)$size[1];
		}elseif(is_numeric($size)){
			$width	= $size;
			$height	= 0;
			$crop	= false;
		}elseif($size == 'thumb' || $size == 'thumbnail'){
			$width	= (int)get_option('thumbnail_size_w') ?: 100;
			$height = (int)get_option('thumbnail_size_h') ?: 100;
			$crop	= get_option('thumbnail_crop');
		}elseif($size == 'medium'){
			$width	= (int)get_option('medium_size_w') ?: 300;
			$height	= (int)get_option('medium_size_h') ?: 300;
			$crop	= false;
		}elseif( $size == 'medium_large' ) {
			$width	= (int)get_option('medium_large_size_w');
			$height	= (int)get_option('medium_large_size_h');
			$crop	= false;

			if($max_width > 0){
				$width	= min($max_width, $width);
			}
		}elseif($size == 'large'){
			$width	= (int)get_option('large_size_w') ?: 1024;
			$height	= (int)get_option('large_size_h') ?: 1024;
			$crop	= false;

			if($max_width > 0) {
				$width	= min($max_width, $width);
			}
		}elseif(isset($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes[$size])){
			$width	= (int)$_wp_additional_image_sizes[$size]['width'];
			$height	= (int)$_wp_additional_image_sizes[$size]['height'];
			$crop	= $_wp_additional_image_sizes[$size]['crop'];

			if($max_width > 0){
				$width	= min($max_width, $width);
			}
		}else{
			$width	= 0;
			$height	= 0;
			$crop	= false;
		}
	}

	$crop	= $crop ?? ($width && $height);

	$width	= $width * $ratio;
	$height	= $height * $ratio;

	return compact('width', 'height', 'crop');
}

function wpjam_get_default_thumbnail_url($size='full', $crop=1){	// 默认缩略图
	$default	= wpjam_thumbnail_get_setting('default') ?: '';

	if($default && is_array($default)){
		$default	= $default[array_rand($default)];
	}

	if($default = apply_filters('wpjam_default_thumbnail_url', $default)){
		return wpjam_get_thumbnail($default, $size, $crop);
	}

	return '';
}

add_action('wp_loaded', function(){
	add_filter('wpjam_term_thumbnail_url',	['WPJAM_Thumbnail', 'filter_term_thumbnail_url'],	1,	2);
	add_filter('wpjam_post_thumbnail_url',	['WPJAM_Thumbnail', 'filter_post_thumbnail_url'],	1,	2);

	if(wpjam_thumbnail_get_setting('auto')){
		add_filter('has_post_thumbnail',	['WPJAM_Thumbnail', 'filter_has_post_thumbnail'],	10,	2);
		add_filter('post_thumbnail_html',	['WPJAM_Thumbnail', 'filter_post_thumbnail_html'],	10,	5);
	}

	if(is_admin()){
		add_action('wpjam_builtin_page_load',	['WPJAM_Thumbnail', 'on_builtin_page_load'],	1,	2);
	}
});