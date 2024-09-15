<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, target-densityDpi=device-dpi" />
    <title><?php echo wpjam_theme_get_setting('maintenance_title');?></title>
    <link rel="shortcut icon" href="<?php echo wpjam_theme_get_setting('favicon');?>"/>
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('template_directory'); ?>/maintenance/css/style.css"/>
    <script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/maintenance/js/script.js"></script>
	<script type="text/javascript">
		var countDownDate = new Date("<?php echo wpjam_theme_get_setting('maintenance_time');?>").getTime();
	</script>
</head>

<body class="white-font">
    <div class="hidden_overflow gradient_violet">
	<div class="container">
        <div class="count-block">
            <div class="head-area">
                <a href="<?php echo get_option('home'); ?>" class="logo mob_logo"><img src="<?php echo wpjam_theme_get_setting('maintenance_logo');?>" alt=""></a>
                <h2 class="time-left-txt"><?php echo wpjam_theme_get_setting('maintenance_title');?></h2>
            </div>
            <div class="middle-area">
                <div class="countdown-row">
                    <a href="<?php echo get_option('home'); ?>" class="logo"><img src="<?php echo wpjam_theme_get_setting('maintenance_logo');?>" alt=""></a
                    ><div class="counting-row">
                        <div class="slot-type">
                            <span class="num" id="day">00</span>
                            <span class="param">天</span>
                        </div
                        ><div class="slot-type">
                            <span class="num" id="hour">00</span>
                            <span class="param">小时</span>
                        </div
                        ><div class="slot-type">
                            <span class="num" id="min">00</span>
                            <span class="param">分钟</span>
                        </div
                        ><div class="slot-type">
                            <div class="num _INVISIBLE_" id="second">00</div>
                            <span class="param">秒</span>
                        </div>
                    </div>
                    <div class="seconds-holder">
                        <div class="circle-holder">
                            <div class="dark_digit IE_HIDE">
                                <img src="<?php bloginfo('template_directory'); ?>/maintenance/css/secondwhite.svg" class="round" alt="">
                            </div>
                            <svg class="dark_digit" width="100%" height="100%">
                                <g id="clipPath">
                                    <image xlink:href="<?php bloginfo('template_directory'); ?>/maintenance/css/secondwhite.svg" width="100%" height="100%" transform="" class="round" id="digitalsecond" alt="">
                                        <animateTransform attributeName="transform"
                                        attributeType="XML"
                                        type="rotate"
                                        dur="10s"
                                        repeatCount="indefinite"/>
                                    </image>
                                </g>
                                <defs>
                                    <clipPath id="hero-clip">
                                        <rect x="94%" y="47.2%" fill="#ff0000" width="110" height="64"/>
                                    </clipPath>
                                </defs>
                            </svg>
                            <div class="down_opacity_circle">
                                <img src="<?php bloginfo('template_directory'); ?>/maintenance/css/secondtrans_.svg" class="round" id="digitalsecond" alt="">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="countdown-caption">
                    <?php echo wpjam_theme_get_setting('maintenance_container');?>
                </div>
            </div>
            <footer>
                <p class="copyright-txt">
                    Powered By&nbsp;<a href="http://www.xintheme.com" target="_blank">XinTheme</a>&nbsp;+&nbsp;<a href="https://blog.wpjam.com/" target="_blank">WordPress 果酱</a>
                </p>
            </footer>
        </div>
    </div>
</div>
</body>
</html>
