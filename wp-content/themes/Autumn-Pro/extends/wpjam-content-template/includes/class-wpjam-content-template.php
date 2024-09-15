<?php
class WPJAM_Content_Template{
	public static function get_template($post, $text, $class=[]){
		$class[]	= 'content-template';
		
		if(post_password_required($post)){
			$text		= "\n".get_the_password_form($post)."\n".wpautop(do_shortcode($text));
			$class[]	= 'post-password-content-template';
		}else{
			if($type = wpjam_get_content_template_type($post->ID)){
				if($object	= wpjam_get_content_template_type_object($type)){
					$text		= call_user_func([$object->model, 'get_template'], $post, $text);
					$class[]	= $type.'-content-template';
				}else{
					return '';
				}
			}else{
				if(strpos($post->post_content, '[text]') !== false){
					$text	= str_replace('[text]', $text, $post->post_content);
				}else{
					$text	= $post->post_content."\n".$text;
				}
				
				$text	= $text ? "\n".wpautop(do_shortcode(do_blocks($text))) : '';

				if($post->post_password){
					$class[]	= 'post-password-content-template';
				}
			}
		}

		return '<div class="'.implode(' ', $class).'">'."\n".$text."\n</div>";
	}

	public static function register_api($json){
		if($json == 'template.get'){
			wpjam_register_api('template.get', [
				'title'		=> '内容模板详情',
				'modules'	=> [[
					'type'	=> 'post_type',
					'args'	=> '[module post_type="template" action="get" output="template"]'
				]]
			]);
		}
	}

	public static function filter_post_json($post_json, $post_id){
		if($post_json['post_type']	== 'template'){
			$post_json['template_type']	= wpjam_get_content_template_type($post_id) ?: 'content';

			if($post_json['template_type'] == 'table'){
				$post_content	= get_post($post_id)->post_content;
				$table_content	= $post_content ? maybe_unserialize($post_content) : [];
				$table_fields	= get_post_meta($post_id, '_table_fields', true);

				if($table_fields && $table_content){
					$table	= ['fields'=>array_values($table_fields), 'content'=>array_values($table_content)];
				}else{
					$table	= null;
				}

				$post_json['table']	= $table;
			}
			
			unset($post_json['content']);
			unset($post_json['related']);
		}

		return $post_json;
	}

	public static function template_shortcode_callback($atts, $text=''){
		if((is_singular() && get_the_ID() == get_queried_object_id()) || is_feed()){
			$atts	= shortcode_atts(['id'=>0, 'name'=>'',	'class'=>''], $atts);
			$class	= $atts['class'];
			$class	= $class ? (is_array($class) ? $class : [$class]) : [];

			if($atts['id']){
				$post	= get_post($atts['id']);

				if(empty($post) || $post->post_type != 'template'){
					return '';
				}

				return self::get_template($post, $text, $class);
			}elseif($atts['name']){
				if($post = get_page_by_path($atts['name'], OBJECT, 'template')){
					return self::get_template($post, $text, $class);
				}
			}
		}
			
		return '';
	}

	public static function field_shortcode_callback($atts, $text=''){
		if(doing_filter('the_content')){
			$atts		= shortcode_atts(['key'=>''], $atts);
			$meta_key	= $atts['key'];
			$post_id	= get_the_ID();

			return get_post_meta($post_id, $meta_key, true);	
		}
		
		return $text;
	}

	public static function password_shortcode_callback($atts, $text=''){
		remove_filter('post_password_required',	[self::class, 'filter_post_password_required'], 10, 2);

		if(post_password_required()){
			$text	= get_the_password_form();
		}else{
			$text	= $GLOBALS['post']->post_password ? wpautop($text) : '';
		}

		add_filter('post_password_required',	[self::class, 'filter_post_password_required'], 10, 2);
		
		return $text;
	}

	public static function filter_post_password_required($required, $post){
		return $required && !has_shortcode($post->post_content, 'password');
	}

	public static function filter_protected_title_format($protected_format, $post){
		return has_shortcode($post->post_content, 'password') ? '%s' : $protected_format;
	}
}

class WPJAM_Content_Template_Type{
	use WPJAM_Register_Trait;
}