<?php
/*
Name: 文章目录
URI: https://blog.wpjam.com/m/wpjam-toc/
Description: 自动根据文章内容里的子标题提取出文章目录，并显示在内容前。
Version: 1.0
*/
class WPJAM_Toc_Setting{
	use WPJAM_Setting_Trait;

	private function __construct(){
		$this->init('wpjam-toc');
	}

	public function get_fields(){
		return [
			'depth'		=> ['title'=>'显示到第几级',	'type'=>'select',	'value'=>6,	'options'=>['1'=>'h1','2'=>'h2','3'=>'h3','4'=>'h4','5'=>'h5','6'=>'h6']],
			'individual'=> ['title'=>'目录单独设置',	'type'=>'checkbox',	'value'=>1,	'description'=>'在每篇文章编辑页面单独设置是否显示文章目录以及显示到第几级。'],
			'position'	=> ['title'=>'目录显示位置',	'type'=>'select',	'value'=>'content',	'options'=>['content'=>'显示在文章内容前面','function'=>'调用函数wpjam_get_toc()显示']],
			'auto'		=> ['title'=>'脚本自动插入',	'type'=>'checkbox', 'value'=>1,	'description'=>'自动插入文章目录的 JavaScript 和 CSS 代码，请点击这里获取<a href="https://blog.wpjam.com/m/toc-js-css-code/" target="_blank">文章目录的默认 JS 和 CSS</a>。'],
			'script'	=> ['title'=>'JS代码',		'type'=>'textarea',	'show_if'=>['key'=>'auto', 'value'=>'1'],	'description'=>'如果你没有选择自动插入脚本，可以将下面的 JavaScript 代码复制你主题的 JavaScript 文件中。'],
			'css'		=> ['title'=>'CSS代码',		'type'=>'textarea',	'show_if'=>['key'=>'auto', 'value'=>'1'],	'description'=>'根据你的主题对下面的 CSS 代码做适当的修改。<br />如果你没有选择自动插入脚本，可以将下面的 CSS 代码复制你主题的 CSS 文件中。'],
			'copyright'	=> ['title'=>'版权信息',		'type'=>'checkbox', 'value'=>1,	'description'=>'在文章目录下面显示版权信息。']
		];
	}
}

class WPJAM_Toc{
	private $items	= [];

	public function __construct(&$content, $depth=6, $post_id=null){
		$regex		= $depth == 1 ? '#<h1(.*?)>(.*?)</h1>#' : '#<h([1-'.$depth.'])(.*?)>(.*?)</h\1>#';
		$content	= preg_replace_callback($regex, [$this, 'add_item'], $content);

		if($post_id){
			self::$instances[$post_id]	= $this;
		}
	}

	public function get_toc(){
		if(empty($this->items)){
			return '';
		}

		$index		= '<ul>'."\n";
		$prev_depth	= 0;
		$to_depth	= 0;

		foreach($this->items as $i => $item){
			$depth	= $item['depth'];

			if($prev_depth){
				if($depth == $prev_depth){
					$index .= '</li>'."\n";
				}elseif($depth > $prev_depth){
					$to_depth++;
					$index .= '<ul>'."\n";
				}else{
					$to_depth2 = ($to_depth > ($prev_depth - $depth))? ($prev_depth - $depth) : $to_depth;

					if($to_depth2){
						for($i=0; $i<$to_depth2; $i++){
							$index .= '</li>'."\n".'</ul>'."\n";
							$to_depth--;
						}
					}

					$index .= '</li>';
				}
			}

			$prev_depth	= $depth;

			$index .= '<li><a href="#toc-'.($i+1).'">'.$item['text'].'</a>';
		}

		for($i=0; $i<=$to_depth; $i++){
			$index .= '</li>'."\n".'</ul>'."\n";
		}

		return $index;
	}

	public function add_item($matches){
		$this->items[]	= ['text'=>trim(strip_tags($matches[3])), 'depth'=>$matches[1]];

		return '<h'.$matches[1].' '.$matches[2].'><a name="toc-'.count($this->items).'"></a>'.$matches[3].'</h'.$matches[1].'>';
	}

	private static $instances;

	public static function get_instance($post_id){
		return self::$instances[$post_id] ?? null;
	}

	public static function filter_content($content){
		if(doing_filter('get_the_excerpt') || !is_singular() || get_the_ID() != get_queried_object_id()){
			return $content;
		}

		$depth		= wpjam_toc_get_setting('depth');
		$post_id	= get_the_ID();

		if(wpjam_toc_get_setting('individual')){
			if(get_post_meta($post_id, 'toc_hidden', true)){
				return $content;
			}

			if(metadata_exists('post', $post_id, 'toc_depth')){
				$depth = get_post_meta($post_id, 'toc_depth', true);
			}
		}

		$object	= new WPJAM_Toc($content, $depth, $post_id);

		if(wpjam_toc_get_setting('position') != 'function' && !has_shortcode($content, 'toc')){
			if($toc	= $object->get_toc()){
				$toc	= '<p><strong>文章目录</strong><span>[隐藏]</span></p>'."\n".$toc;

				if(wpjam_toc_get_setting('copyright')){
					$toc	.= '<a href="http://blog.wpjam.com/project/wpjam-basic/"><small>WPJAM TOC</small></a>'."\n";
				}

				$content	= '<div id="toc">'."\n".$toc.'</div>'."\n".$content;
			}
		}

		return $content;
	}

	public static function on_head(){
		if(is_singular() && wpjam_toc_get_setting('auto')){
			echo '<script type="text/javascript">'."\n".wpjam_toc_get_setting('script')."\n".'</script>'."\n";
			echo '<style type="text/css">'."\n".wpjam_toc_get_setting('css')."\n".'</style>'."\n";
		}
	}

	public static function on_builtin_page_load($screen_base, $current_screen){
		if($screen_base == 'post' && $current_screen->post_type != 'attachment'){
			wpjam_register_post_option('wpjam-toc', [
				'title'		=> '文章目录',
				'context'	=> 'side',
				'fields'	=> [
					'toc_hidden'	=> ['title'=>'',		'type'=>'checkbox',	'description'=>'不显示文章目录'],
					'toc_depth'		=> ['title'=>'显示到：',	'type'=>'select',	'options'=>[''=>'默认','1'=>'h1','2'=>'h2','3'=>'h3','4'=>'h4','5'=>'h5','6'=>'h6'],	'show_if'=>['key'=>'toc_hidden', 'value'=>0]]
				]
			]);
		}
	}
}

function wpjam_toc_get_setting($name, $default=null){
	return WPJAM_Toc_Setting::get_instance()->get_setting($name, $default);
}

function wpjam_get_toc(){
	if($object = WPJAM_Toc::get_instance(get_the_ID())){
		return $object->get_toc();
	}

	return '';
}

add_action('wp_loaded', function(){
	add_shortcode('toc', 'wpjam_get_toc');

	add_filter('the_content',	['WPJAM_Toc', 'filter_content']);
	add_action('wp_head', 		['WPJAM_Toc', 'on_head']);

	if(is_admin() && (!is_multisite() || !is_network_admin())){
		wpjam_register_plugin_page_tab('toc', [
			'title'			=> '文章目录',
			'function'		=> 'option',
			'option_name'	=> 'wpjam-toc',
			'plugin_page'	=> 'wpjam-posts',
			'model'			=> 'WPJAM_Toc_Setting',
			'summary'		=> '文章目录扩展自动根据文章内容的子标题提取出文章目录，并显示在内容前，详细介绍请点击：<a href="https://blog.wpjam.com/m/wpjam-toc/" target="_blank">文章目录扩展</a>。'
		]);

		if(wpjam_toc_get_setting('individual')){
			add_action('wpjam_builtin_page_load', ['WPJAM_Toc', 'on_builtin_page_load'], 10, 2);
		}
	}
});