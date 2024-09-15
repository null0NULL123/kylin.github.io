<?php
add_action('wp_ajax_nopriv_create-bigger-image','get_bigger_img');
add_action('wp_ajax_create-bigger-image','get_bigger_img');

function substr_ext($str, $start = 0, $length, $charset = 'utf-8', $suffix = ''){
    if (function_exists('mb_substr')) {
        return mb_substr($str, $start, $length, $charset) . $suffix;
    }
    if (function_exists('iconv_substr')) {
        return iconv_substr($str, $start, $length, $charset) . $suffix;
    }
    $re['utf-8'] = '/[-]|[?-?][?-?]|[?-?][?-?]{2}|[?-?][?-?]{3}/';
    $re['gb2312'] = '/[-]|[?-?][?-?]/';
    $re['gbk'] = '/[-]|[?-?][@-?]/';
    $re['big5'] = '/[-]|[?-?]([@-~]|?-?])/';
    preg_match_all($re[$charset], $str, $match);
    $slice = join('', array_slice($match[0], $start, $length));
    return $slice . $suffix;
}

function xintheme_str_encode($string){
    return $string;
	$len = strlen($string);
    $buf = '';
    $i = 0;
    while ($i < $len) {
        if (ord($string[$i]) <= 127) {
            $buf .= $string[$i];
        } elseif (ord($string[$i]) < 192) {
            $buf .= '&#xfffd;';
        } elseif (ord($string[$i]) < 224) {
            $buf .= sprintf('&#%d;', ord($string[$i + 0]) + ord($string[$i + 1]));
            $i = $i + 1;
            $i += 1;
        } elseif (ord($string[$i]) < 240) {
            ord($string[$i + 2]);
            $buf .= sprintf('&#%d;', ord($string[$i + 0]) + ord($string[$i + 1]) + ord($string[$i + 2]));
            $i = $i + 2;
            $i += 2;
        } else {
            ord($string[$i + 2]);
            ord($string[$i + 3]);
            $buf .= sprintf('&#%d;', ord($string[$i + 0]) + ord($string[$i + 1]) + ord($string[$i + 2]) + ord($string[$i + 3]));
            $i = $i + 3;
            $i += 3;
        }
        $i = $i + 1;
    }
    return $buf;
}

function draw_txt_to($card, $pos, $str, $iswrite, $font_file){
    $_str_h = $pos['top'];
    $fontsize = $pos['fontsize'];
    $width = $pos['width'];
    $margin_lift = $pos['left'];
    $hang_size = $pos['hang_size'];
    $temp_string = '';
    $tp = 0;
    $font_color = imagecolorallocate($card, $pos['color'][0], $pos['color'][1], $pos['color'][2]);
    $i = 0;
    while ($i < mb_strlen($str)) {
        $box = imagettfbbox($fontsize, 0, $font_file, xintheme_str_encode($temp_string));
        $_string_length = $box[2] - $box[0];
        $temptext = mb_substr($str, $i, 1);
        $temp = imagettfbbox($fontsize, 0, $font_file, xintheme_str_encode($temptext));
        if ($_string_length + $temp[2] - $temp[0] < $width) {
            $temp_string .= mb_substr($str, $i, 1);
            if ($i == mb_strlen($str) - 1) {
                $_str_h = $_str_h + $hang_size;
                $_str_h += $hang_size;
                $tp = $tp + 1;
                if ($iswrite) {
                    imagettftext($card, $fontsize, 0, $margin_lift, $_str_h, $font_color, $font_file, xintheme_str_encode($temp_string));
                }
            }
        } else {
            $texts = mb_substr($str, $i, 1);
            $isfuhao = preg_match('/[\\pP]/u', $texts) ? true : false;
            if ($isfuhao) {
                $temp_string .= $texts;
                $f = mb_substr($str, $i + 1, 1);
                $fh = preg_match('/[\\pP]/u', $f) ? true : false;
                if ($fh) {
                    $temp_string .= $f;
                    $i = $i + 1;
                }
            } else {
                $i = $i + -1;
            }
            $tmp_str_len = mb_strlen($temp_string);
            $s = mb_substr($temp_string, $tmp_str_len - 1, 1);
            if (is_firstfuhao($s)) {
                $temp_string = rtrim($temp_string, $s);
                $i = $i + -1;
            }
            $_str_h = $_str_h + $hang_size;
            $_str_h += $hang_size;
            $tp = $tp + 1;
            if ($iswrite) {
                imagettftext($card, $fontsize, 0, $margin_lift, $_str_h, $font_color, $font_file, xintheme_str_encode($temp_string));
            }
            $temp_string = '';
        }
        $i = $i + 1;
    }
    return $tp * $hang_size;
}

function is_firstfuhao($str){
    $fuhaos = array('0' => '"', '1' => '“', '2' => '\'', '3' => '<', '4' => '《');
    return in_array($str, $fuhaos);
}

//生成封面
function create_bigger_image($post_id,$date,$title,$content,$head_img,$qrcode_img=null,$author){
	$im = imagecreatetruecolor(800,1360);      //设置海报整体的宽高
	$white = imagecolorallocate($im,255,255,255);    // 海报背景色
	$gray = imagecolorallocate($im,239,239,239);     // 海报水平图文分割线颜色
	$red = imagecolorallocate($im,240,66,66);     // 海报水平图文分割线颜色
	$foot_text_color = imagecolorallocate($im,153,153,153);    // 海报左下角文字（网站副标题）颜色
	$black = imagecolorallocate($im,0,0,0);      // 设置偏移标题的字体颜色
	$title_text_color = imagecolorallocate($im,255,51,51);    // 不知道有啥用的参数。。。
	$poster_font = get_template_directory().'/static/fonts/anzhuo.TTF';      // 海报中用到的字体
	imagefill($im,0,0,$white);      //设置海报底色填充
	$head_img = imagecreatefromstring(file_get_contents($head_img));      // 海报头部图片宽高尺寸
	imagecopy($im,$head_img,0,0,0,0,800,520);      // 海报头部图片框宽高尺寸
	$day = $date['day'];        // 获取海报中显示的文章发布日期（天）
	$day_width = imagettfbbox(80,0,$poster_font,$day);      // 计算并返回一个包围着 TrueType 文本范围的虚拟方框的像素大小（字体大小,旋转角度，字体文件，文本字符）
    $day_width = abs($day_width[2]-$day_width[0]);
	$year = $date['year'];      // 获取海报中显示的文章发布日期（年）
	$year_width = imagettfbbox(24,0,$poster_font,$year);      // 计算并返回一个包围着 TrueType 文本范围的虚拟方框的像素大小（字体大小,旋转角度，字体文件，文本字符）
	$year_width = abs($year_width[2]-$year_width[0]);
	$day_left = ($year_width-$day_width)/2;          // 海报头部图片悬浮日期（天）距离左侧边缘
	imagettftext($im,80,0,30+$day_left,420,$white,$poster_font,$day);      // 海报头部图片中绘制日期（天）（源图像，字体大小，旋转角度，X轴坐标，Y轴坐标，字体颜色，字体文件，文本字符）
	imageline($im,30,440,30+$year_width,440,$white);      // 海报头部图片中绘制日期间隔线的属性
	imagettftext($im,24,0,30,480,$white,$poster_font,$year);      // 海报头部图片中绘制日期（年）（源图像，字体大小，旋转角度，X轴坐标，Y轴坐标，字体颜色，字体文件，文本字符）
	$title = xintheme_str_encode($title);
  	
	$title_conf = array('color'=>array('0'=>0,'1'=>0,'2'=>0),'fontsize'=>28,'width'=>740,'left'=>30,'top'=>540,'hang_size'=>24);
 	draw_txt_to($im,$title_conf ,$title,true,$poster_font);     // 在海报上绘制文章标题 

	$des_conf = array('color'=>array('0'=>99,'1'=>99,'2'=>99),'fontsize'=>20,'width'=>740,'left'=>30,'top'=>660,'hang_size'=>18);
	draw_txt_to($im,$des_conf,$content,true,$poster_font);    // 在海报上绘制文章摘要 
	
	// 作者
	$meta = ' @ '. $author.'';
	$meta_conf = array('color'=>array('0'=>99,'1'=>99,'2'=>99),'fontsize'=>22,'width'=>740,'left'=>30,'top'=>900,'hang_size'=>18);
	draw_txt_to($im,$meta_conf,''.$meta,true,$poster_font); 
	//$style = array();
	//imagesetstyle($im,$style);
	imageline($im,0,1020,800,1020,$gray);      // 文章摘要下方间隔线条设置（源图像，X1坐标，Y1坐标，X2坐标，Y2坐标，线条颜色）
	// 获取海报底部网站描述文字（网站副标题）
	$foot_text = wpjam_theme_get_setting('poster_txt');
	$foot_text = xintheme_str_encode($foot_text);
	// 获取海报底部 Logo 文件
	$poster_logo = wpjam_theme_get_setting('poster_logo');
	$logo_img = imagecreatefromstring(file_get_contents($poster_logo));
	
	//Logo图片
	imagecopy($im,$logo_img,30,1120,0,0,240,50);
	imagettftext($im,20,0,30,1240,$foot_text_color,$poster_font,$foot_text);      // 网站描述文字（副标题）（源图像，字体大小，旋转角度，X轴坐标，Y轴坐标，字体颜色，字体文件，文本字符）
	$qrcode_str = file_get_contents($qrcode_img);
	$qrcode_size = getimagesizefromstring($qrcode_str);
	$qrcode_img = imagecreatefromstring($qrcode_str);
	imagecopyresized($im,$qrcode_img,590,1100,0,0,180,180,$qrcode_size[0],$qrcode_size[1]);    // 复制并重定义二维码尺寸（源图像，目标图像，源X轴，源Y轴，目标X轴，目标Y轴，宽度，高度）
	// 上传生成的海报图片至指定文件夹
	$upload_dir = wp_upload_dir();
	$poster_dir = $upload_dir['basedir'].'/posterimg';
	if (!is_dir($poster_dir)){
		wp_mkdir_p($poster_dir);
	}
	$filename='/poster-'.$post_id.'.png';
	$file=$poster_dir.$filename;
	imagepng($im,$file);
	require_once ABSPATH.'wp-admin/includes/image.php';
	require_once ABSPATH.'wp-admin/includes/file.php';
	require_once ABSPATH.'wp-admin/includes/media.php';

	$src = $upload_dir['baseurl'].'/posterimg'.$filename;
	error_reporting(0);
	imagedestroy($im);
	if(is_wp_error($src)){
		return false;
	}
	return $src;
}

function get_bigger_img(){
	$post_id = sanitize_text_field($_POST['id']);
	if(wp_verify_nonce($_POST['nonce'],'xintheme-create-bigger-image-'.$post_id)){
		get_the_time('d',$post_id);
		get_the_time('Y/m',$post_id);
		$date = array('day'=>get_the_time('d',$post_id),'year'=>get_the_time('Y/m',$post_id));
		$post_title = get_the_title($post_id);
		$post_author_id = get_post($post_id)->post_author;
		$post_author = get_the_author_meta('display_name',$post_author_id);
		$title = substr_ext($post_title,0,28,'utf-8','');
		$author = substr_ext($post_author,0,30,'utf-8','');
		$post = get_post($post_id);
		$content = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		$content = substr_ext(strip_tags(strip_shortcodes($content)),0,140,'utf-8','...');

		//文章摘要
		$content = str_replace(PHP_EOL,'',strip_tags(apply_filters('the_excerpt',$content)));
		//文章缩略图
		$head_img = wpjam_get_post_thumbnail_url($post,array(800,520), $crop=1);
		// 获取海报底部二维码图片
		$qrcode_img = get_template_directory_uri().'/public/qrcode?data='.get_the_permalink($post_id);

		$result = create_bigger_image($post_id,$date,$title,$content,$head_img,$qrcode_img,$author);
		if($result){
			$pic = '&pic='.urlencode($result);
			if(get_post_meta($post_id,'poster_img',true)){
				update_post_meta($post_id,'poster_img',$result);
			}else{
				add_post_meta($post_id,'poster_img',$result);
			}
			$msg=array('state'=>200,'src'=>$result);
		}else{
			$msg=array('state'=>404,'tips'=>'ERROR:404,封面生成失败，请稍后再试！');
		}
    }else{
		$msg=array('state'=>404,'tips'=>'ERROR:404,图片消失啦~请联系管理员解决此问题！');
	}
	echo json_encode($msg);
	exit(0);
}