<?php
if(!class_exists('WP_List_Table')){
	include ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

include WPJAM_BASIC_PLUGIN_DIR.'includes/class-wpjam-list-table.php';
include WPJAM_BASIC_PLUGIN_DIR.'includes/class-wpjam-builtin.php';
include WPJAM_BASIC_PLUGIN_DIR.'includes/class-wpjam-menu-page.php';
include WPJAM_BASIC_PLUGIN_DIR.'includes/class-wpjam-chart.php';

function wpjam_admin_load($current_screen=null){
	if($object = WPJAM_Plugin_Page::get_current()){
		$object->load();
	}elseif($current_screen){
		WPJAM_Builtin_Page::load($current_screen);
	}
}

function wpjam_generate_query_data($query_args){
	$query_data	= [];

	foreach($query_args as $query_arg){
		$query_data[$query_arg]	= wpjam_get_data_parameter($query_arg);
	}

	return $query_data;
}

function wpjam_get_page_summary($type='page'){
	return (string)get_current_screen()->get_option($type.'_summary');
}

function wpjam_set_page_summary($summary, $type='page', $append=true){
	$summary	= ($append ? wpjam_get_page_summary($type) : '').$summary;

	add_screen_option($type.'_summary', $summary);
}

function wpjam_set_plugin_page_summary($summary, $append=true){
	wpjam_set_page_summary($summary, 'page', $append);
}

function wpjam_set_builtin_page_summary($summary, $append=true){
	wpjam_set_page_summary($summary, 'page', $append);
}

function wpjam_get_plugin_page_setting($key='', $using_tab=false){
	if($object = WPJAM_Plugin_Page::get_current()){
		$is_tab	= $object->function == 'tab';

		if(str_ends_with($key, '_name')){
			$using_tab	= $is_tab;
			$default	= $GLOBALS['plugin_page'];
		}else{
			$using_tab	= $using_tab ? $is_tab : false;
			$default	= null;
		}

		if($using_tab){
			if($tab_object = $object->current_tab){
				return $key ? ($tab_object->$key ?: $default) : $tab_object->to_array();
			}
		}else{
			return $key ? ($object->$key ?: $default) : $object->to_array();
		}
	}

	return null;
}

function wpjam_get_plugin_page_type(){
	return wpjam_get_plugin_page_setting('function');
}

function wpjam_get_current_tab_setting($key=''){
	return wpjam_get_plugin_page_setting($key, true);
}

function wpjam_get_plugin_page_query_data(){
	$value	= wpjam_get_plugin_page_setting('query_data') ?: [];
		
	if($query_data = wpjam_get_current_tab_setting('query_data', true)){
		$value	= array_merge($value, $query_data);
	}

	return $value ?: [];
}

function wpjam_admin_tooltip($text, $tooltip){
	return '<div class="wpjam-tooltip">'.$text.'<div class="wpjam-tooltip-text">'.wpautop($tooltip).'</div></div>';
}

function wpjam_get_referer(){
	$referer	= wp_get_original_referer();
	$referer	= $referer ?: wp_get_referer();

	$removable_query_args	= array_merge(wp_removable_query_args(), ['_wp_http_referer', 'action', 'action2', '_wpnonce']);

	return remove_query_arg($removable_query_args, $referer);	
}

function wpjam_register_page_action($name, $args){
	if(WPJAM_Page_Action::get($name)){
		trigger_error('Page Action 「'.$name.'」已经注册。');
	}

	return WPJAM_Page_Action::register($name, $args);
}

function wpjam_unregister_page_action($name){
	WPJAM_Page_Action::unregister($name);
}

function wpjam_get_page_form($name, $args=[]){
	$instance	= WPJAM_Page_Action::get($name);
	return $instance ? $instance->get_form($args) : '';
}

function wpjam_get_page_button($name, $args=[]){
	$instance	= WPJAM_Page_Action::get($name);
	return $instance ? $instance->get_button($args) : '';
}

function wpjam_register_list_table($name, $args=[]){
	return WPJAM_List_Table_Setting::register($name, $args);
}

function wpjam_register_list_table_action($name, $args){
	return WPJAM_List_Table_Action::register($name, $args);
}

function wpjam_unregister_list_table_action($name){
	WPJAM_List_Table_Action::unregister($name);
}

function wpjam_register_list_table_column($name, $field){
	return WPJAM_List_Table_Column::pre_register($name, $field);
}

function wpjam_unregister_list_table_column($name, $field=[]){
	WPJAM_List_Table_Column::unregister_pre($name, $field);
}

function wpjam_register_plugin_page($name, $args){
	return WPJAM_Plugin_Page::register($name, $args);
}

function wpjam_register_plugin_page_tab($name, $args){
	return WPJAM_Plugin_Page::register_tab($name, $args);
}

function wpjam_get_list_table_filter_link($filters, $title, $class=''){
	return $GLOBALS['wpjam_list_table']->get_filter_link($filters, $title, $class);
}

function wpjam_get_list_table_row_action($action, $args=[]){
	return $GLOBALS['wpjam_list_table']->get_row_action($action, $args);
}

function wpjam_render_list_table_column_items($id, $items, $args=[]){
	return $GLOBALS['wpjam_list_table']->render_column_items($id, $items, $args);
}

function wpjam_call_list_table_model_method($method, ...$args){
	return $GLOBALS['wpjam_list_table']->call_model_method($method, ...$args);
}

function wpjam_register_dashboard($name, $args){
	return WPJAM_Dashboard_Setting::register($name, $args);
}

function wpjam_unregister_dashboard($name){
	WPJAM_Dashboard_Setting::unregister($name);
}

function wpjam_register_dashboard_widget($name, $args){
	return WPJAM_Dashboard_Widget::register($name, $args);
}

function wpjam_unregister_dashboard_widget($name){
	WPJAM_Dashboard_Widget::unregister($name);
}

function wpjam_get_admin_post_id(){
	if(isset($_GET['post'])){
		return (int)$_GET['post'];
	}elseif(isset($_POST['post_ID'])){
		return (int)$_POST['post_ID'];
	}else{
		return 0;
	}
}

function wpjam_line_chart($counts_array, $labels, $args=[], $type = 'Line'){
	WPJAM_Chart::line($counts_array, $labels, $args, $type);
}

function wpjam_bar_chart($counts_array, $labels, $args=[]){
	wpjam_line_chart($counts_array, $labels, $args, 'Bar');
}

function wpjam_donut_chart($counts, $args=[]){
	WPJAM_Chart::donut($counts, $args);
}

function wpjam_get_chart_parameter($key){
	return WPJAM_Chart::get_parameter($key);
}

function wpjam_get_ajax_screen_id(){
	if(isset($_POST['screen_id'])){
		$screen_id	= $_POST['screen_id'];
	}elseif(isset($_POST['screen'])){
		$screen_id	= $_POST['screen'];	
	}else{
		$ajax_action	= $_REQUEST['action'] ?? '';

		if($ajax_action == 'fetch-list'){
			$screen_id	= $_GET['list_args']['screen']['id'];
		}elseif($ajax_action == 'inline-save-tax'){
			$screen_id	= 'edit-'.sanitize_key($_POST['taxonomy']);
		}elseif($ajax_action == 'get-comments'){
			$screen_id	= 'edit-comments';
		}else{
			$screen_id	= false;
		}
	}

	return $screen_id;
}

function wpjam_admin_enqueue_scripts(){
	$screen	= get_current_screen();
	
	if($screen->base == 'customize'){
		return;
	}elseif($screen->base == 'post'){
		wp_enqueue_media(['post'=>wpjam_get_admin_post_id()]);
	}else{
		wp_enqueue_media();
	}

	$ver	= get_plugin_data(WPJAM_BASIC_PLUGIN_FILE)['Version'];
	$static	= WPJAM_BASIC_PLUGIN_URL.'static';

	wp_enqueue_script('thickbox');
	wp_enqueue_style('thickbox');

	wp_enqueue_style('wpjam-style',		$static.'/style.css',	['wp-color-picker', 'editor-buttons'], $ver);
	wp_enqueue_script('wpjam-script',	$static.'/script.js',	['jquery', 'thickbox', 'wp-backbone', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-ui-autocomplete', 'wp-color-picker'], $ver);
	wp_enqueue_script('wpjam-form',		$static.'/form.js',	['wpjam-script', 'mce-view'], $ver);

	$setting	= [
		'screen_base'	=> $screen->base,
		'screen_id'		=> $screen->id,
		'post_type'		=> $screen->post_type,
		'taxonomy'		=> $screen->taxonomy,
	];

	$params		= wpjam_array_except($_REQUEST, array_merge(wp_removable_query_args(),['page', 'tab', 'post_type', 'taxonomy', '_wp_http_referer', '_wpnonce']));
	$params		= array_filter($params, function($param){ return !empty($param) || is_numeric($param); });

	if($GLOBALS['plugin_page']){
		$setting['plugin_page']	= $GLOBALS['plugin_page'];
		$setting['current_tab']	= $GLOBALS['current_tab'] ?? null;
		$setting['admin_url']	= $GLOBALS['current_admin_url'] ?? '';

		if($query_data = wpjam_get_plugin_page_query_data()){
			$params	= wpjam_array_except($params, array_keys($query_data));

			$setting['query_data']	= array_map('sanitize_textarea_field', $query_data);
		}
	}

	$setting['params']	= $params ? array_map('sanitize_textarea_field', $params) : new stdClass();

	if(!empty($GLOBALS['wpjam_list_table'])){
		$setting['list_table']	= $screen->get_option('wpjam_list_table');
	}

	wp_localize_script('wpjam-script', 'wpjam_page_setting', $setting);
}

add_action('wp_loaded', function(){	// 内部的 hook 使用 优先级 9，因为内嵌的 hook 优先级要低
	if($GLOBALS['pagenow'] == 'options.php'){
		// 为了实现多个页面使用通过 option 存储。这个可以放弃了，使用 AJAX + Redirect
		// 注册设置选项，选用的是：'admin_action_' . $_REQUEST['action'] hook，
		// 因为在这之前的 admin_init 检测 $plugin_page 的合法性
		add_action('admin_action_update', function(){
			add_action('current_screen',	'wpjam_admin_load', 9);

			$referer_origin	= parse_url(wpjam_get_referer());

			if(!empty($referer_origin['query'])){
				$referer_args	= wp_parse_args($referer_origin['query']);

				if(!empty($referer_args['page'])){
					WPJAM_Menu_Page::init($referer_args['page']);	// 实现多个页面使用同个 option 存储。

					set_current_screen($_POST['screen_id']);
				}
			}
		}, 9);
	}elseif(wp_doing_ajax()){
		add_action('admin_init', function(){
			add_action('current_screen',	'wpjam_admin_load', 9);

			if($screen_id = wpjam_get_ajax_screen_id()){
				if('-network' === substr($screen_id, -8)){
					if(!defined('WP_NETWORK_ADMIN')){
						define('WP_NETWORK_ADMIN', true);
					}
				}elseif('-user' === substr($screen_id, -5)){
					if(!defined('WP_USER_ADMIN')){
						define('WP_USER_ADMIN', true);
					}
				}

				if(isset($_POST['plugin_page'])){
					WPJAM_Menu_Page::init($_POST['plugin_page']);
				}

				if($screen_id == 'upload'){
					$GLOBALS['hook_suffix']	= $screen_id;

					set_current_screen();
				}else{
					set_current_screen($screen_id);
				}
			}
			
			add_action('wp_ajax_wpjam-page-action',	['WPJAM_Page_Action', 'ajax_response']);
			add_action('wp_ajax_wpjam-query', 		['WPJAM_Field_Data_Type', 'ajax_query']);
		}, 9);
	}else{
		$admin_menu_action	= (is_multisite() && is_network_admin()) ? 'network_admin_menu' : 'admin_menu';	

		add_action($admin_menu_action,	['WPJAM_Menu_Page', 'render'], 9);
		add_action('current_screen',	'wpjam_admin_load', 9);

		add_action('admin_enqueue_scripts', 'wpjam_admin_enqueue_scripts', 9);
		add_action('print_media_templates', ['WPJAM_Field',	'print_media_templates'], 9);

		add_filter('wpjam_html', ['WPJAM_Queried_Menu', 'filter_html']);

		add_filter('set-screen-option', function($status, $option, $value){
			return isset($_GET['page']) ? $value : $status;
		}, 9, 3);
	}
});

add_action('wpjam_list_table_load', function($wpjam_list_table){
	if($wpjam_list_table->data_type == 'post_type'){
		WPJAM_Post_Option::register_list_table_action($wpjam_list_table->post_type);
	}elseif($wpjam_list_table->data_type	== 'taxonomy'){
		WPJAM_Term_Option::register_list_table_action($wpjam_list_table->taxonomy);
	}

	if(is_array($wpjam_list_table->per_page)){
		add_screen_option('per_page', $wpjam_list_table->per_page);
	}

	if($wpjam_list_table->style){
		wp_add_inline_style('list-tables', $wpjam_list_table->style);
	}

	$screen_option	= get_current_screen()->get_option('wpjam_list_table') ?: [];
	$screen_option	= wp_parse_args($screen_option, ['ajax'=>true, 'form_id'=>'list_table_form']);

	if(($query_id = wpjam_get_parameter('id', ['sanitize_callback'=>'sanitize_text_field'])) 
		&& !$wpjam_list_table->current_action()
	){
		$screen_option['query_id']	= $query_id;
	}

	if($sortable = $wpjam_list_table->sortable){
		$action_args	= is_array($sortable) ? wpjam_array_pull($sortable, 'action_args', []) : [];

		wpjam_register_list_table_action('move',	array_merge($action_args, ['page_title'=>'拖动',		'direct'=>true,	'dashicon'=>'move']));
		wpjam_register_list_table_action('up',		array_merge($action_args, ['page_title'=>'向上移动',	'direct'=>true,	'dashicon'=>'arrow-up-alt']));
		wpjam_register_list_table_action('down',	array_merge($action_args, ['page_title'=>'向下移动',	'direct'=>true,	'dashicon'=>'arrow-down-alt']));

		$screen_option['sortable']	= is_array($sortable) ? $sortable : ['items'=>' >tr'];
	}

	if($left_key = $wpjam_list_table->left_key){
		$screen_option['left_key']	= $left_key;
	}

	add_screen_option('wpjam_list_table', $screen_option);

	if(is_array($wpjam_list_table->actions)){
		foreach($wpjam_list_table->actions as $key => $action){
			wpjam_register_list_table_action($key, wp_parse_args($action, ['order'=>10.5]));
		}
	}

	$fields	= $wpjam_list_table->call_model_method('get_fields') ?: [];

	foreach($fields as $key => $field){
		if(!empty($field['show_admin_column'])){
			wpjam_register_list_table_column($key, wp_parse_args($field, ['order'=>10.5]));
		}

		if($field['type'] == 'fieldset' && wpjam_array_get($field, 'fieldset_type') != 'array'){
			foreach($field['fields'] as $sub_key => $sub_field){
				if(!empty($sub_field['show_admin_column'])){
					wpjam_register_list_table_column($sub_key, wp_parse_args($sub_field, ['order'=>10.5]));
				}
			}
		}
	}
});

