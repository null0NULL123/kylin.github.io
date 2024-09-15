<?php
class WEIXIN_Post_Password extends WPJAM_Content_Template{
	public static function filter_password_form($output){
		$weixin_qrcode = wpjam_content_template_get_setting('weixin_qrcode');

		if(empty($weixin_qrcode)){
			return $output;
		}
		
		if(!preg_match('/<label for="pwbox-(.*?)">/i', $output, $match)){
			return $output;
		}

		$post_id	= $match[1];

		$qrcode	= wpjam_get_thumbnail($weixin_qrcode, '160x160');
		$qrcode	= '<img src="'.$weixin_qrcode.'" class="content-template-weixin-qrcode" width="80" height="80" />'; 

		$tip	= wpjam_content_template_get_setting('weixin_tip');
		$tip	= $tip ?: '下面内容受密码保护，扫码关注公众号，回复「[keyword]」获取密码。';
		$tip	= str_replace('[keyword]', 'PP'.$post_id, $tip);

		$label	= 'pwbox-' . $post_id;

		$post_password_form = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" class="post-password-form content-template-post-password-form" method="post">'.$qrcode.'<p>'.$tip.'</p>
		<label for="' . $label . '"><input name="post_password" id="' . $label . '" type="password" size="20" /></label>
		<input type="submit" name="Submit" value="' . esc_attr_x( 'Enter', 'post password form' ) . '" />
		</form>';

		if(get_post($post_id)->post_type == 'template'){
			return $post_password_form;
		}else{
			return '<div class="content-template post-password-content-template">'.$post_password_form.'</div>';
		}	
	}

	public static function on_weixin_reply_loaded(){
		weixin_register_reply('pp',	['type'=>'prefix',	'reply'=>'文章密码',	'callback'=>['WEIXIN_Post_Password', 'reply']]);

		weixin_register_response_type('post_password', '文章密码回复');
	}

	public static function reply($keyword, $weixin_reply){
		$post_id	= str_replace('pp', '', $keyword);
		
		if(!$post_id){
			$weixin_reply->text_reply('PP后面要跟上文章ID，比如：PP123。');
		}else{
			if($post = get_post($post_id)){
				$reply	= wpjam_content_template_get_setting('weixin_reply') ?: '密码是： [password]';
				$reply	= str_replace('[password]', $post->post_password, $reply);

				$weixin_reply->text_reply($reply);
			}else{
				$weixin_reply->text_reply('你查询的文章不存在，所以没密码。');	
			}
		}
		$weixin_reply->set_response('post_password');

		return true;
	}
}