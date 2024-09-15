<?php
class WPJAM_Sticky_Posts{
	public static function filter_the_posts($posts, $wp_query){
		if(!$wp_query->is_main_query() || $wp_query->query_vars['ignore_sticky_posts']){
			return $posts;
		}

		$page	= absint($wp_query->query_vars['paged']) ?: 1;

		if($page > 1){
			return $posts;
		}

		if($wp_query->is_home){
			$sticky_posts	= get_option('sticky_posts');

			if(is_array($sticky_posts) && !empty($sticky_posts)){
				$sticky_posts	= array_unique($sticky_posts);
				$stickies		= array_fill_keys($sticky_posts, null);

				foreach($posts as $i => $post) {
					if(in_array($post->ID, $sticky_posts)){ 
						$stickies[$post->ID] = $post;
						unset($posts[$i]);
					}
				}

				return array_values(array_merge(array_filter($stickies), $posts));
			}
		}elseif($wp_query->is_category && !$wp_query->is_tag && !$wp_query->is_tax){
			if($term_id	= $wp_query->get_queried_object_id()){
				$sticky_posts	= get_term_meta($term_id, 'sticky_posts', true);

				if(is_array($sticky_posts) && !empty($sticky_posts)){
					$sticky_posts	= array_unique($sticky_posts);
					$stickies		= WPJAM_Post::get_by_ids($sticky_posts);

					for($i = 0; $i<count($posts); $i++){
						if(in_array($posts[$i]->ID, $sticky_posts, true)){
							array_splice($posts, $i, 1);
						}
					}

					return array_values(array_merge(array_filter($stickies), $posts));
				}
			}
		}

		return $posts;
	}

	public static function get_option_fields(){
		return [
			'sticky_posts'	=> ['title'=>'',	'type'=>'mu-text',	'data_type'=>'post_type',	'post_type'=>get_taxonomy('category')->object_type],
		];
	}

	public static function get_term_fields($term_id){
		return ['sticky_posts'	=> [
			'title'			=> '置顶文章',
			'type'			=> 'mu-text',
			'data_type'		=> 'post_type',
			'post_type'		=> get_taxonomy('category')->object_type,
			'query_args'	=> ['cat'=>$term_id],
			'action'		=> 'edit',
			'style'			=> 'max-width:calc(100% - 100px);'
		]];
	}

	public static function on_plugin_page_load($plugin_page, $current_tab){
		if($plugin_page == 'wpjam-posts'){
			wpjam_register_plugin_page_tab('sticky', [
				'title'			=> '置顶文章',
				'function'		=> 'option',
				'order'			=> 19,
				'option_name'	=> 'sticky_posts',
				'load_callback'	=> [self::class, 'load_option_page']
			]);
		}
	}

	public static function load_option_page(){
		wpjam_register_option('sticky_posts', [
			'option_type'	=> 'single', 
			'summary'		=> 'WordPress 置顶文章默认按照发布时间排序，这里可以通过拖动设置置顶文章的显示顺序。', 
			'fields'		=> [self::class, 'get_option_fields']
		]);
	}

	public static function on_builtin_page_load($screen_base, $current_screen){
		if(!in_array($screen_base, ['edit-tags', 'term']) || $current_screen->taxonomy != 'category'){
			return;
		}

		wpjam_register_term_option('sticky_posts', [
			'title'			=> '置顶文章',
			'page_title'	=> '置顶文章',
			'submit_text'	=> '设置',
			'list_table'	=> true,
			'fields'		=> [self::class, 'get_term_fields']
		]);

		add_action('admin_footer', function(){
		?>
		<script type="text/javascript">
		jQuery(function($){
			$('body').on('wpjam_autocomplete_selected', function(event, item){
				console.log(item);
				wpjam_iframe('http://jam.wpweixin.com/?preview&p='+item.value, {width:400, height:600});
			});
		});
		</script>
		<?php
		});
	}
}