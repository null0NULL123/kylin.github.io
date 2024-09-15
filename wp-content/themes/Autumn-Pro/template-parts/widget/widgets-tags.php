<?php

//标签云-热门标签
function xintheme_hot_tag_list( $num = null){
    $num = $num ? $num : 14;
    
    $output = '<ul class="tagcloud">';
    $tags = get_tags(array("number" => $num,
        "orderby"=>"count",
		"order" => "DESC",
    ));
    foreach($tags as $tag){
        $count = intval( $tag->count );
        $name = $tag->name;
        $output .= '<li><a href="'. esc_attr( get_tag_link( $tag->term_id ) ) .'" class="tag-item" title="#'. $name . '# 共有'. $tag->count .'篇文章">'.$name.' <!--sup>（'. $tag->count .'）</sup--></a></li>';

    }
    $output .= '</ul>';
    return $output;

}

add_action('widgets_init', function(){register_widget('xintheme_tag' );});
class xintheme_tag extends WP_Widget {
	function __construct() {
		$widget_ops = array( 'classname' => 'widget_tag_cloud', 'description' => '「Autumn-Pro」主题自带标签云小工具，显示热门标签，可自定义显示数量' );
		parent::__construct('xintheme_tag', '「Autumn-Pro」热门标签 ', $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );
		echo $before_widget;
		$title = apply_filters('widget_name', $instance['title']);
		$count = $instance['count'];
		echo $before_title.$title.$after_title; 
		echo xintheme_hot_tag_list($count);
		echo $after_widget;
	}

	function form($instance) {
	    $instance = wp_parse_args( (array) $instance, array( 
			'title' => '热门标签',
			'count' => '15',
			) 
		);
?>

<p>
	<label> 名称：
		<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" class="widefat" />
	</label>
</p>
<p>
	<label> 显示数量：
		<input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="number" value="<?php echo $instance['count']; ?>" class="widefat" />
	</label>
</p>
<?php
	}
}