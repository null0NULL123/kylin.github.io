<?php
function wpjam_get_verification_code($num,$w,$h) {
	$font = __DIR__.'/static/Candice.ttf';
	// 去掉了 0 1 O l u v 等
	$str = "23456789abcdefghijkmnpqrstwxyz";
	$code = '';
	for ($i = 0; $i < $num; $i++) {
		$code .= $str[mt_rand(0, strlen($str)-1)];
	}
	//创建图片，定义颜色值
	Header("Content-type: image/PNG");
	$im = imagecreate($w, $h);
	$black = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
	$gray = imagecolorallocate($im, 254, 207, 107);
	$bgcolor = imagecolorallocate($im, 235, 236, 237);

	//画背景
	imagefilledrectangle($im, 0, 0, $w, $h, $bgcolor);
	//画边框
	imagerectangle($im, 0, 0, $w-1, $h-1, $bgcolor);
	//imagefill($im, 0, 0, $bgcolor);

	//随机绘制两条虚线，起干扰作用 
    $style = array ($black,$black,$black,$black,$black, 
        $gray,$gray,$gray,$gray,$gray 
    ); 
    imagesetstyle($im, $style); 
    $y1 = rand(0, $h); 
    $y2 = rand(0, $h); 
    $y3 = rand(0, $h); 
    $y4 = rand(0, $h); 
    imageline($im, 0, $y1, $w, $y3, IMG_COLOR_STYLED); 
    imageline($im, 0, $y2, $w, $y4, IMG_COLOR_STYLED); 


	//在画布上随机生成大量点，起干扰作用;
	for ($i = 0; $i < 20; $i++) {
		imagesetpixel($im, rand(0, $w), rand(0, $h), $black);
	}
	//将字符随机显示在画布上,字符的水平间距和位置都按一定波动范围随机生成
	$strx = rand(3, 8);
	for ($i = 0; $i < $num; $i++) {
		$black = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
		$strpos = rand(1, 6);
		imagettftext($im, 22, 1, $strx, 26, $black, $font, substr($code, $i, 1));
		$strx += 22;
	}
	imagepng($im);
	imagedestroy($im);

	return $code;
}

if(!isset($_SESSION)){
    session_start();
}

$_SESSION["verification_code"] = wpjam_get_verification_code(4,90,30);