<?php
class WPJAM_Platform_Hidden extends WPJAM_Bit{
	private $id;
	private $type;

	public function __construct($id, $type='post'){
		$this->id	= $id;
		$this->type	= $type;

		if($this->type == 'post'){
			$bit	= (int)(get_post($id)->platform ?? 0);
		}elseif($this->type == 'term'){
			$bit	= (int)get_term_meta($id, 'platform', true);
		}

		$this->set_bit($bit);
	}

	public function save(){
		$bit	= $this->get_bit();

		if($this->type == 'post'){
			return wp_update_post(['ID'=>$this->id, 'platform'=>$bit]);
		}elseif($this->type == 'term'){
			if($bit){
				return update_term_meta($this->id, 'platform', $bit);
			}else{
				return delete_term_meta($this->id, 'platform');
			}
		}
	}
}

class WPJAM_Platform_Hidden_Setting{
	use WPJAM_Setting_Trait;

	private function __construct(){
		$this->init('wpjam-platform-hide', true);
	}

	public static function get_fields($id, $type='post'){
		$ids	= is_array($id) ? $id : [$id];
		$_id	= current($ids);

		if($type == 'post'){
			$post_type	= get_post($_id)->post_type;
			$lable		= get_post_type_object($post_type)->label;
		}elseif($type == 'term'){
			$taxonomy	= get_term($_id)->taxonomy;
			$lable		= get_taxonomy($taxonomy)->label;
		}
		
		if(!is_array($id)){
			$ph_obj	= new WPJAM_Platform_Hidden($id, $type);	
		}

		$platforms	= self::get_instance()->get_setting('platforms');
		$options	= WPJAM_Platform::get_options('bit');

		foreach($platforms as $platform){
			$value	= is_array($id) ? 0 : $ph_obj->has($platform);
			$name	= $options[$platform];

			$fields['hidden_'.$platform]	= ['title'=>$name, 'type'=>'checkbox', 'value'=>$value, 'description'=>$name.$lable.'列表页不显示该'.$lable];
		}

		return $fields;
	}

	public static function hide($id, $data, $type='post'){
		$platforms	= self::get_instance()->get_setting('platforms');
		$ids		= is_array($id) ? $id : [$id];

		foreach ($ids as $_id) {
			$ph_obj	= new WPJAM_Platform_Hidden($_id, $type);

			foreach($platforms as $platform){
				if(!empty($data['hidden_'.$platform])){
					$ph_obj->add($platform);
				}else{
					$ph_obj->remove($platform);
				}
			}

			$ph_obj->save();
		}

		return true;
	}

	public static function column_callback($id, $type){
		$platforms	= self::get_instance()->get_setting('platforms');
		$options	= WPJAM_Platform::get_options('bit');
		$ph_obj		= new WPJAM_Platform_Hidden($id, $type);

		$values		= [];

		foreach($platforms as $platform){
			if($ph_obj->has($platform)){
				$values[]	= $options[$platform];
			}
		}

		return ($values ? implode("，", $values).'上隐藏' : '所有平台都不隐藏').'<br />（'.wpjam_get_list_table_row_action('hide', ['id'=>$id, 'title'=>'修改']).'）';
	}

	public static function post_column_callback($post_id){
		return self::column_callback($post_id, 'post');
	}

	public static function term_column_callback($term_id){
		if(get_term($term_id)->parent){
			return '';
		}

		return self::column_callback($term_id, 'term');
	}

	public static function get_post_fields($post_id){
		return self::get_fields($post_id, 'post');
	}

	public static function get_term_fields($term_id){
		return self::get_fields($term_id, 'term');
	}

	public static function ajax_hide_post($post_id, $data){
		return self::hide($post_id, $data, 'post');
	}
	
	public static function ajax_hide_term($term_id, $data){
		return self::hide($term_id, $data, 'term');
	}

	public static function on_restrict_manage_posts($post_type){
		$platforms	= self::get_instance()->get_setting('platforms');
		$options	= WPJAM_Platform::get_options('bit');
		$options	= wp_array_slice_assoc($options, $platforms);

		echo wpjam_get_field_html([
			'title'		=>'',
			'key'		=>'hide',
			'type'		=>'select',
			'value'		=>$_REQUEST['hide'] ?? 0,
			'options'	=>[0=>'隐藏',-1=>'未设置']+$options
		]);
	}

	public static function filter_insert_post_data($data, $postarr){
		if(isset($postarr['platform'])){
			$data['platform']	= $postarr['platform'];
		}
			
		return $data;
	}

	public static function filter_posts_where($where, $wp_query){
		$platforms	= self::get_instance()->get_setting('platforms');

		if($wp_query->is_single || !$platforms){
			return $where;
		}

		global $wpdb;

		if(is_admin()){
			if($hide = (int)wpjam_get_data_parameter('hide', ['default'=>0])){
				if($hide == -1){
					$where .= ' AND '.$wpdb->posts.'.platform = 0';
				}else{
					$where .= ' AND ( '.$wpdb->posts.'.platform & '.$hide.' ='.$hide.' )';
				}
			}
		}else{
			$post_type	= $wp_query->get('post_type') ?: 'post';
			
			if(!$post_type || is_array($post_type) || !post_type_supports($post_type, 'hide')){
				return $where;
			}

			if($platform_hidden	= wpjam_get_current_platform($platforms, 'bit')){
				$where .= ' AND ( '.$wpdb->posts.'.platform & '.$platform_hidden.' !='.$platform_hidden.' )';
			}
		}

		return $where;
	}

	public static function filter_terms($terms, $args, $max_depth) {
		$platforms	= self::get_instance()->get_setting('platforms');

		if(is_admin() || empty($terms) || !$platforms){
			return $terms;
		}
		
		$tax_obj	= get_taxonomy($args['taxonomy']);

		if(empty($tax_obj->hide)){
			return $terms;
		}

		if($platform_hidden	= wpjam_get_current_platform($platforms, 'bit')){

			if(isset($args['parent']) && ($max_depth != -1 && $max_depth != 1)){
				$parent	= $args['parent'];
			}else{
				$parent	= 0;
			}

			foreach($terms as $i => &$term){
				if($parent && $parent == $term['id']){
					continue;
				}
				
				$wpjam_platform_hidden	= new WPJAM_Platform_Hidden($term['id'], 'term');
				if($wpjam_platform_hidden->has($platform_hidden)){
					unset($terms[$i]);
				}
			}
		}

		return array_values($terms);
	}

	public static function filter_register_post_type_args($args, $post_type){
		$post_types	= self::get_instance()->get_setting('post_types');

		if($post_types && in_array($post_type, $post_types)){

			$args['supports']	= $args['supports'] ?? [];
		
			if(!in_array('hide',  $args['supports'])){
				$args['supports'][]	= 'hide';
			}else{
				// wpjam_update_setting('wpjam-platform-hide', 'post_types', array_diff($post_types, [$post_type]));
			}
		}

		return $args;
	}

	public static function on_plugin_page_load($plugin_page, $current_tab){
		if($plugin_page == 'wpjam-posts'){
			wpjam_register_plugin_page_tab('platform-hide', [
				'title'			=> '文章隐藏',	
				'function'		=> 'option',	
				'option_name'	=> 'wpjam-platform-hide',
				'load_callback'	=> [self::class, 'load_option_page']
			]);
		}
	}

	public static function load_option_page(){
		$post_types = self::get_instance()->get_setting('post_types') ?: [];
		$pt_options	= [];
		$pt_objects	= get_post_types(['show_ui'=>true], 'objects');
		$pt_objects	= wpjam_array_except($pt_objects, ['attachment', 'wp_block', 'template']);

		foreach($pt_objects as $post_type => $pt_object){
			if(($post_types && in_array($post_type, $post_types)) || !post_type_supports($post_type, 'hide')){
				$pt_options[$post_type]	= $pt_object->label;
			}
		}

		$fields['platforms']	= ['title'=>'设置的平台',		'type'=>'checkbox',	'options'=>WPJAM_Platform::get_options('bit')];
		$fields['post_types']	= ['title'=>'支持的文章类型',	'type'=>'checkbox',	'options'=>$pt_options];

		$summary	= '文章隐藏插件设置文章在文章列表页不显示，并且可以根据不同平台进行设置，详细介绍请点击：<a href="https://blog.wpjam.com/project/wpjam-platform-hide/" target="_blank">文章隐藏插件</a>。';

		wpjam_register_option('wpjam-platform-hide', compact('fields', 'summary'));

		global $wpdb;

		$table	= $wpdb->posts;

		if(!$wpdb->query("SHOW COLUMNS FROM `{$table}` WHERE field='platform'")){
			$wpdb->query("ALTER TABLE `{$table}` ADD COLUMN platform int(10) NOT NULL DEFAULT 0");
			$wpdb->query("ALTER TABLE `{$table}` ADD KEY `platform_idx` (`platform`);");
		}

		if(isset($_GET['reset'])){
			delete_option('wpjam-platform-hide');
		}
	}

	public static function on_builtin_page_load($screen_base, $current_screen){
		if(!in_array($screen_base, ['edit', 'edit-tags']) || !self::get_instance()->get_setting('platforms')){
			return;
		}

		if($screen_base == 'edit'){
			if(post_type_supports($current_screen->post_type, 'hide')){
				wpjam_register_list_table_action('hide', [
					'title'			=> '隐藏',	
					'bulk'			=> true,
					'row_action'	=> false,
					'fields'		=> [self::class, 'get_post_fields'],
					'callback'		=> [self::class, 'ajax_hide_post']
				]);

				wpjam_register_list_table_column('platform_hidden', [
					'title'				=> '隐藏',	
					'column_callback'	=> [self::class, 'post_column_callback']
				]);

				add_action('restrict_manage_posts', [self::class, 'on_restrict_manage_posts']);

				add_action('admin_enqueue_scripts', function(){
					wp_add_inline_style('list-tables', 'th.column-platform_hidden{width: 56px;}');
				});
			}
		}elseif($screen_base == 'edit-tags'){
			if(!empty(get_taxonomy($current_screen->taxonomy)->hide)){
				wpjam_register_list_table_action('hide', [
					'title'			=> '隐藏',	
					'bulk'			=> true,
					'row_action'	=> false,
					'parent'		=> 0,
					'fields'		=> [self::class, 'get_term_fields'],
					'callback'		=> [self::class, 'ajax_hide_term']
				]);

				wpjam_register_list_table_column('platform_hidden', [
					'title'				=> '隐藏',	
					'column_callback'	=> [self::class, 'term_column_callback']
				]);

				add_action('admin_enqueue_scripts', function(){
					wp_add_inline_style('list-tables', 'th.column-platform_hidden{width: 112px;}');
				});
			}
		}
	}
}