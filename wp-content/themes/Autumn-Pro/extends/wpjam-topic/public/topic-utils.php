<?php
class WPJAM_Topic_Setting{
	use WPJAM_Setting_Trait;

	private $types			= [];
	private $account_meta	= [];

	private function __construct(){
		$this->init('wpjam-topics');

		if($this->get_setting('topic_type')){
			$this->types	= array_values(wpjam_get_option('wpjam-topic-types'));
		}
	}

	public function get_setting($name, $default=null){
		if($name == 'account'){
			if(!did_action('wpjam_account_loaded')){
				return false;
			}
		}elseif(in_array($name, ['apply', 'topic_type'])){
			if(!$this->get_setting('account')){
				return false;
			}
		}

		return $this->settings[$name] ?? $default;
	}

	public function get_type($topic_type=null){
		if(is_null($topic_type)){
			return $this->types;
		}

		if(is_numeric($topic_type)){
			return $this->types[$topic_type] ?? [];
		}

		foreach($this->types as $type){
			if($topic_type == $type['slug']){
				return $type;
			}

			if(is_array($type['columns'])){
				foreach($type['columns'] as $column){
					if($column['slug'] == $topic_type){
						$type['sub_name']	= $column['name'];
						$type['sub_slug']	= $topic_type;
						
						return $type;		
					}
				}
			}
		}

		return [];
	}

	public static function sanitize_option($value){
		if(is_multisite() && is_network_admin()){
			return $value;
		}

		global $wpdb;
				
		$table	= $wpdb->posts;

		if(!$wpdb->query("SHOW COLUMNS FROM `{$table}` WHERE field='last_comment_time'")){
			$wpdb->query("ALTER TABLE `{$table}` ADD COLUMN last_comment_time int(10) NOT NULL default 0");
			$wpdb->query("ALTER TABLE `{$table}` ADD KEY `last_comment_time_idx` (`last_comment_time`);");
		}

		if(!empty($value['account'])){
			if(!$wpdb->query("SHOW COLUMNS FROM `{$table}` WHERE field='account_id'")){
				$wpdb->query("ALTER TABLE `{$table}` ADD COLUMN account_id bigint(20) NOT NULL default 0");
				$wpdb->query("ALTER TABLE `{$table}` ADD KEY `account_id_idx` (`account_id`);");
			}

			if(!empty($value['apply'])){
				if(!$wpdb->query("SHOW COLUMNS FROM `{$table}` WHERE field='apply_status'")){
					$wpdb->query("ALTER TABLE `{$table}` ADD COLUMN apply_status int(1) NOT NULL default 0");
					$wpdb->query("ALTER TABLE `{$table}` ADD KEY `apply_status_idx` (`apply_status`);");
				}
			}

			if(!empty($value['topic_type'])){
				if(!$wpdb->query("SHOW COLUMNS FROM `{$table}` WHERE field='topic_type'")){
					$wpdb->query("ALTER TABLE `{$table}` ADD COLUMN topic_type varchar(31) NOT NULL default ''");
					$wpdb->query("ALTER TABLE `{$table}` ADD KEY `topic_type_idx` (`topic_type`);");
				}
			}
		}

		if($wpdb->query("SHOW COLUMNS FROM `{$table}` WHERE field='last_comment_user'")){	// 改成 meta
			$posts	= $wpdb->get_results("SELECT ID, last_comment_user FROM {$table} WHERE last_comment_user != 0");

			foreach($posts as $post){
				if(!metadata_exists('post', $post->ID, 'last_comment_user')){
					update_post_meta($post->ID, 'last_comment_user', $post->last_comment_user);

					// echo "migrate {$post->ID} last_comment_user to meta \n";
				}
			}
		}

		return $value;
	}

	public static function get_fields(){
		if(is_multisite() && is_network_admin()){
			return [
				'global'	=>['title'=>'全局讨论组',	'type'=>'checkbox',	'description'=>'整个站点只有一个讨论组'],
				'blog_id'	=>['title'=>'站点ID',	'type'=>'number',	'description'=>'整个站点全局讨论组所在站点ID',	'show_if'=>['key'=>'global', 'value'=>1]]
			];
		}else{
			$fields	= [
				'features'	=> ['title'=>'功能',	'type'=>'fieldset',	'fields'=>[
					'audit'			=>['type'=>'checkbox',	'description'=>'帖子需要审核'],
					'comments'		=>['type'=>'checkbox',	'description'=>'开启评论回复功能',	'value'=>1],
					'account'		=>['type'=>'checkbox',	'description'=>'使用前台账号体系'],
					'apply'			=>['type'=>'checkbox',	'description'=>'开启申请合作功能',	'show_if'=>['key'=>'account', 'value'=>1]],
					'topic_type'	=>['type'=>'checkbox',	'description'=>'支持类型字段',		'show_if'=>['key'=>'account', 'value'=>1]]
				]],
				'name'		=> ['title'=>'名称',	'type'=>'fieldset',	'fields'=>[
					'topics_name'	=>['title'=>'讨论组',	'type'=>'text',	'value'=>'讨论组',	'class'=>'all-options'],
					'topic_name'	=>['title'=>'帖子',	'type'=>'text',	'value'=>'帖子',		'class'=>'all-options'],
					'group_name'	=>['title'=>'分组',	'type'=>'text',	'value'=>'分组',		'class'=>'all-options'],
				]],
				'pages'		=>['title'=>'页面',	'type'=>'mu-fields',	'show_if'=>['key'=>'account', 'value'=>1],	'group'=>1,	'fields'=>[
					'key'	=> ['type'=>'text',	'class'=>'',	'placehoder'=>'请输入页面KEY'],
					'id'	=> array_merge(wpjam_get_post_id_field('page'), ['title'=>''])
				]],
			];

			if(!did_action('wpjam_account_loaded')){
				unset($fields['pages']);
				unset($fields['features']['fields']['comments']);
				unset($fields['features']['fields']['account']);
				unset($fields['features']['fields']['apply']);
				unset($fields['features']['fields']['topic_type']);
			}

			return $fields;
		}
	}
}

function wpjam_get_topic_blog_id(){
	if(is_multisite()){
		$site_option	= wpjam_get_site_option('wpjam-topics');

		if(isset($site_option['global'])){
			if(!empty($site_option['global'])){
				$topic_blog_id	= $site_option['blog_id'] ?? 0;
			}else{
				$topic_blog_id	= get_current_blog_id();
			}
		}else{
			$topic_blog_id	= 0;
		}

		return apply_filters('wpjam_topic_blog_id', $topic_blog_id);
	}else{
		return get_current_blog_id();
	}
}

function wpjam_is_topic_blog(){
	return is_multisite() ? (get_current_blog_id() == wpjam_get_topic_blog_id()) : true;	
}

function wpjam_topic_switch_to_blog(){
	return is_multisite() ? switch_to_blog(wpjam_get_topic_blog_id()) : false;
}

function wpjam_topic_get_setting($name, $default=null){
	return WPJAM_Topic_Setting::get_instance()->get_setting($name, $default);
}

function wpjam_topic_update_setting($name, $value){
	return WPJAM_Topic_Setting::get_instance()->update_setting($name, $value);
}

function wpjam_topic_get_type_setting($topic_type=null){
	return  WPJAM_Topic_Setting::get_instance()->get_type($topic_type);
}