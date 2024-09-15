<?php
class WPJAM_TinyMCE{
	public static function filter_mce_buttons($buttons){
		$insert_buttons	= [
			'italic'		=> ['underline', 'strikethrough', 'forecolor', 'backcolor', 'styleselect'],
			'alignright'	=> ['alignjustify'],
			'wp_more'		=> ['wp_page', 'hr']
		];

		foreach ($insert_buttons as $button_before => $_buttons) {
			$pos	= array_search($button_before, $buttons, true);

			if($pos !== false){
				$buttons	= array_merge(array_slice($buttons, 0, $pos+1), $_buttons, array_slice($buttons, $pos+1));
			}
		}

		return array_diff($buttons, ['formatselect']);
	}

	public static function filter_mce_buttons_2($buttons){
		return array_merge(['formatselect', 'fontsizeselect', 'fontselect', 'table'], array_diff($buttons, ['strikethrough', 'forecolor', 'hr']));
	}

	public static function filter_tiny_mce_before_init($mceInit){
		return array_merge($mceInit, [
			'paste_data_images'	=> true,
			'fontsize_formats'	=> '12px 14px 15px 16px 17px 18px 20px 22px 24px 28px 32px 36px 40px 48px',
			'font_formats'		=> "微软雅黑='微软雅黑';宋体='宋体';黑体='黑体';仿宋='仿宋';楷体='楷体';隶书='隶书';幼圆='幼圆';Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats",
		]);
	}

	public static function filter_mce_external_plugins($plugins){
		return array_merge($plugins, ['table'=>plugins_url('/mce/table/plugin.min.js', dirname(__FILE__))]);
	}

	public static function filter_content_save_pre($content){
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return $content;
		}

		if(preg_match_all('/<img src.*?"(data:image\/(.*?);base64,(.*?))".*?>/i', $content, $matches)){
			$search	= $replace	= $matches[1];
			$types	= $matches[2];
			$images	= $matches[3];
			$update	= false;

			foreach ($images as $i=>$image){
				$name	= time().'-'.wpjam_generate_random_string(8);
				$type	= $types[$i]; 
				$upload	= wp_upload_bits($name.'.'.$type, null, base64_decode($image));

				if(empty($upload['error'])){
					$replace[$i]= $upload['url'];
					$update		= true;

					$post_id	= $_POST['post_ID'] ?? 0;

					$attachment	= [
						'post_title'     => $name,
						'post_content'   => '',
						'post_type'      => 'attachment',
						'post_parent'    => $post_id,
						'post_mime_type' => $upload['type'],
						'guid'           => $upload['url'],
					];

					$id	= wp_insert_attachment($attachment, $upload['file'], $post_id);
					wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
				}
			}

			if($update){
				$content = str_replace($search, $replace, $content);

				if(is_multisite()){
					setcookie('wp-saving-post', $_POST['post_ID'].'-saved', time()+DAY_IN_SECONDS, ADMIN_COOKIE_PATH, false, is_ssl());
				}
			}
		}

		return $content;
	}
}