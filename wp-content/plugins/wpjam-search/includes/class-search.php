<?php
class WPJAM_Search{
	use WPJAM_Setting_Trait;

	private $search_metas	= [];

	private function __construct(){
		$this->init('wpjam-search');

		if($this->get_setting('meta_search')){
			$this->search_metas	= $this->get_setting('search_metas');
		}
	}

	public function incresement($cache_key){
		$cache_group	= 'wpjam_search_limit';

		$times		= wp_cache_get($cache_key, $cache_group);
		$max		= $this->get_setting('max', 30);
		$block_mins	= $this->get_setting('block_mins', 5);
		
		if($times > $max){
			return new WP_Error('too_many_search_requests', '已达文章搜索次数上限，请'.$block_mins.'分钟后重试！');
		}else{
			$times		= $times ?: 1;
			$cache_time	= $times == $max ? MINUTE_IN_SECONDS*$block_mins : MINUTE_IN_SECONDS;

			wp_cache_set($cache_key, $times+1, $cache_group, $cache_time);
		}
	}

	public function on_template_redirect(){
		if($this->get_setting('redirect')){
			if(is_search() && get_query_var('module') == '') {
				if(empty($GLOBALS['wp_query']->query['s'])){
					wp_redirect(home_url());
				}else{
					$paged	= get_query_var('paged');
					if ($GLOBALS['wp_query']->post_count == 1 && empty($paged)) {
						wp_redirect(get_permalink($GLOBALS['wp_query']->posts['0']->ID));
					}
				}
			}
		}			
	}

	public function filter_request($query_args){
		if(!empty($query_args['s'])){
			if(current_user_can('manage_options') && $this->get_setting('admin')){
				return $query_args;
			}

			if($this->get_setting('disabled')){
				$wp_error	= new WP_Error('search_disabled', '搜索已关闭');
			}elseif($this->get_setting('length')){
				$keyword_length	= mb_strwidth(preg_replace('/[\x00-\x7F]/','',$query_args['s']),'utf-8')+str_word_count($query_args['s'])*2;

				if($keyword_length > $this->get_setting('length')){
					$wp_error	= new WP_Error('search_keyword_too_long', '搜索关键字太长');
				}
			}

			if(!isset($wp_error)){
				$cache_key	= wpjam_get_ip();
				$result		= $this->incresement($cache_key);
				$wp_error	= is_wp_error($result) ? $result : null;
			}

			if($wp_error){
				if(wpjam_is_json_request()){
					wpjam_send_json($wp_error);
				}else{
					wp_die($wp_error);
				}
			}	
		}

		return $query_args;
	}

	public function on_pre_get_posts($wp_query){
		if(!empty($wp_query->query_vars['s'])){
			$wp_query->query_vars['s']	= wpjam_strip_invalid_text($wp_query->query_vars['s']);	// 去掉搜索中非法字符串
		}
	}

	public function filter_clauses($clauses, $wp_query){
		if($wp_query->is_search()){
			$posts_table	= $GLOBALS['wpdb']->posts;

			if($this->get_setting('id_search', 1) && is_admin()){
				$search_term	= $wp_query->query['s'];

				if(is_numeric($search_term)){
					$id_where	= '('.$posts_table.'.ID = '.$search_term.')';
				}elseif(preg_match("/^(\d+)(,\s*\d+)*\$/", $search_term)){
					$id_where	= '('.$posts_table.'.ID in ('.$search_term.'))';
				}else{
					$id_where	= '';
				}

				if($id_where){
					$clauses['where'] = str_replace('('.$posts_table.'.post_title LIKE', $id_where.' OR ('.$posts_table.'.post_title LIKE', $clauses['where']);
				}
			}

			if($this->get_setting('title_only')){
				$clauses['where']	= preg_replace('/OR \('.$posts_table.'\.(post_content|post_excerpt) LIKE (.*?)\)/', '', $clauses['where']);
			}

			if($search_metas = $wp_query->get('search_metas')){
				$this->search_metas	= wp_parse_list($search_metas);
			}

			if($this->search_metas){
				$clauses['where']	= preg_replace_callback('/\('.$posts_table.'.post_title (LIKE|NOT LIKE) (.*?)\)/', [$this, 'meta_search_callback'], $clauses['where']);
			}
		}

		return $clauses;
	}

	public function meta_search_callback($matches){
		$search_metas	= "'".implode("', '", $this->search_metas)."'";
		$posts_table	= $GLOBALS['wpdb']->posts;
		$postmeta_table	= $GLOBALS['wpdb']->postmeta;

		return "EXISTS (SELECT * FROM {$postmeta_table} WHERE {$postmeta_table}.post_id={$posts_table}.ID AND meta_key IN ({$search_metas}) AND meta_value ".$matches[1]." ".$matches[2].") OR ".$matches[0];
	}

	public function filter_weixin_query($args){
		if(!empty($args['s'])){
			$cache_key	= $GLOBALS['weixin_reply']->get_openid();
			$result		= $this->incresement($cache_key);

			if(is_wp_error($result)){
				$GLOBALS['weixin_reply']->text_reply($result->get_error_message());
				exit;
			}
		}

		return $args;
	}

	public function filter_document_title_parts($title){
		if(is_search() && $this->get_setting('result')){
			$title['title']	= '搜索结果';
		}

		return $title;
	}

	public function add_menu_page(){
		wpjam_add_basic_sub_page('wpjam-search', [
			'menu_title'	=> '搜索优化',
			'function'		=> 'option',
			'option_name'	=> 'wpjam-search',
			'order'			=> 11,
			'fields'		=> [
				'limit'		=> ['title'=>'限制屏蔽',	'type'=>'fieldset',	'fields'=>[
					'max_view'		=> ['type'=>'view',		'group'=>'limit',	'value'=>'每IP每分钟最多搜索'],
					'max'			=> ['type'=>'number',	'group'=>'limit',	'class'=>'small-text',	'description'=>'次，',	'value'=>30],
					'block_view'	=> ['type'=>'view',		'group'=>'limit',	'value'=>'达到上限之后屏蔽'],
					'block_mins'	=> ['type'=>'number',	'group'=>'limit',	'class'=>'small-text',	'description'=>'分钟。',	'value'=>5],
					'disabled'		=> ['type'=>'checkbox',	'description'=>'在遭受搜索攻击的时候，可以直接关闭搜索。'],
					'admin'			=> ['type'=>'checkbox',	'description'=>'站点管理员搜索功能不受影响。'],
				]],
				'enhance'	=> ['title'=>'功能增强',	'type'=>'fieldset',	'fields'=>[
					'length_view'	=> ['type'=>'view',		'group'=>'length',	'value'=>'搜索关键词最长'],
					'length'		=> ['type'=>'number',	'group'=>'length',	'class'=>'small-text',	'description'=>'字节，一个汉字算两个，一个英文单词算两个，空格不算。'],
					'redirect'		=> ['type'=>'checkbox',	'description'=>'当搜索关键词为空时重定向到首页，只有一篇文章时重定向到文章。'],
					'result'		=> ['type'=>'checkbox',	'description'=>'搜索结果页面标题不显示关键字，只显示「搜索结果」四个字。'],
					'title_only'	=> ['type'=>'checkbox',	'description'=>'只搜索文章标题，不搜索文章内容和摘要。'],
					'id_search'		=> ['type'=>'checkbox',	'description'=>'后台文章列表支持搜索ID，如<code>123</code>，多个ID用「,」分隔开，如<code>123,456,678</code>。',	'value'=>1],
					'meta_search'	=> ['type'=>'checkbox',	'description'=>'支持搜索自定义字段，注意开启之后搜索效率会明显下降。'],
					'search_metas'	=> ['type'=>'mu-text',	'placeholder'=>'请输入支持的 meta_key',	'class'=>'all-options',	'show_if'=>['key'=>'meta_search','value'=>1]]
				]]
			]
		]);
	}
}
