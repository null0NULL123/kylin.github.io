<?php
/*
Plugin Name: WPJAM 草稿分享
Plugin URI: http://blog.wpjam.com/project/wpjam-share-draft/
Description: 可以给草稿生成一个临时链接，发给朋友快速查看。
Version: 2.0
Author: Denis
Author URI: http://blog.wpjam.com/
*/
add_action('plugins_loaded', function(){
	if(wp_installing() || !did_action('wpjam_loaded') || class_exists('WPJAM_Share_Draft')){
		return;
	}

	class WPJAM_Share_Draft{
		private $links;

		private function __construct(){
			$this->links	= get_option('wpjam_share_draft_links') ?: [];
		}

		private function save(){
			return update_option('wpjam_share_draft_links', $this->links);
		}

		private static $instance = null;

		public static function get_instance(){
			if(is_null(self::$instance)){
				self::$instance = new self();
			}
			
			return self::$instance;
		}

		public function add($post_id, $time){
			if($this->links){
				$this->links	= array_filter($this->links, function($link){ return $link['time'] && $link['time'] > time(); });
				$this->links	= $this->links ?: [];
			}

			$secret	= wp_generate_password(8, false, false);
			$time	= $time ? $time + time() : 0;

			$this->links[$post_id]	= ['time'=>$time,	'secret'=>$secret];

			$this->save();
			
			return $secret;
		}

		public function remove($post_id){
			if(isset($this->links[$post_id])){
				unset($this->links[$post_id]);
				$this->save();
			}
		}

		public function exists($post_id){
			if(!empty($this->links) && !empty($this->links[$post_id])){
				if($this->links[$post_id]['time'] == 0 || $this->links[$post_id]['time'] > time()){
					return $this->links[$post_id];
				}
			}

			return false;
		}

		public static function get_fields($post_id){
			if($share_link = self::get_instance()->exists($post_id)){

				$share_time	= $share_link['time'] ? ($share_link['time']-time()).'秒' : '长期有效';

				return [
					'share_link'	=> ['title'=>'分享链接',	'type'=>'view',	'value'=>add_query_arg('secret', $share_link['secret'], get_permalink($post_id))],
					'share_time'	=> ['title'=>'剩余时间',	'type'=>'view',	'value'=>$share_time],
				];
			}else{
				return [
					'share_time'	=> ['title'=>'分享时长',	'type'=>'number',	'description'=>'0或者不填则长期有效']
				];
			}	
		}

		public static function get_submit_text($post_id){
			return self::get_instance()->exists($post_id) ? '重置' : '生成';	
		}

		public static function generate($post_id, $data){
			$instance	= self::get_instance();

			if($instance->exists($post_id)){
				$instance->remove($post_id);
			}else{
				$instance->add($post_id, $data['share_time']);
			}

			return true;
		}

		public static function filter_the_posts($posts, $wp_query){
			if($wp_query->is_main_query() && is_singular() && isset($_GET['p']) && isset($_GET['secret'])){
				if(($share_link	= self::get_instance()->exists($wp_query->query_vars['p'])) && $share_link['secret'] == $_GET['secret']){
					$posts	= [get_post($wp_query->query_vars['p'])];
				}
			}

			return $posts;	
		}
	}

	if(is_admin()){
		add_action('wpjam_builtin_page_load', function ($screen_base, $current_screen){
			if($screen_base == 'edit'){
				if(is_post_type_viewable($current_screen->post_type)){
					wpjam_register_list_table_action('share_draft', [
						'title'			=> '草稿分享链接',
						'page_title'	=> '草稿分享链接',
						'post_status'	=> 'draft',
						'submit_text'	=> ['WPJAM_Share_Draft', 'get_submit_text'],
						'fields'		=> ['WPJAM_Share_Draft', 'get_fields'],
						'callback'		=> ['WPJAM_Share_Draft', 'generate']
					]);
				}
			}
		}, 10, 2);
	}else{
		add_filter('the_posts',		['WPJAM_Share_Draft', 'filter_the_posts'], 10, 2);
	}
});