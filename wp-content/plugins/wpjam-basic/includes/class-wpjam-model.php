<?php
abstract class WPJAM_Model{
	protected $data	= [];

	public function __construct(array $data=[]){
		$this->data	= $data;
	}

	public function __get($key){
		return $this->get_data($key);
	}

	public function __set($key, $value){
		$this->set_data($key, $value);
	}

	public function __isset($key){
		return isset($this->data[$key]);
	}

	public function __unset($key){
		unset($this->data[$key]);
	}

	public function get_data($key=''){
		if($key){
			return $this->data[$key] ?? null;
		}else{
			return $this->data;
		}
	}

	public function set_data($key, $value){
		if(self::get_primary_key() == $key){
			trigger_error('不能修改主键的值');
			wp_die('不能修改主键的值');
		}

		$this->data[$key]	= $value;

		return $this;
	}

	public function to_array(){
		return $this->data;
	}

	public function save($data=[]){
		if($data){
			$this->data = array_merge($this->data, $data);
		}

		$primary_key	= self::get_primary_key();

		$id	= $this->data[$primary_key] ?? null;

		if($id){
			$result	= static::update($id, $this->data);
		}else{
			$result	= $id = static::insert($this->data);
		}

		if(!is_wp_error($result)){
			$this->data	= static::get($id);
		}

		return $result;
	}

	public static function find($id){
		return static::get_instance($id);
	}

	public static function get_instance($id){
		if($id){
			if($data = static::get($id)){
				return new static($data);
			}
		}

		return null;
	}

	public static function get_handler(){
		return static::$handler;
	}

	public static function set_handler($handler){
		static::$handler	= $handler;
	}
	
	// get($id)
	// get_by($field, $value, $order='ASC')
	// get_by_ids($ids)
	// get_searchable_fields()
	// get_filterable_fields()
	// update_caches($values)
	// insert($data)
	// insert_multi($datas)
	// update($id, $data)
	// delete($id)
	// move($id, $data)
	// get_primary_key()
	// get_cache_key($key)
	// get_last_changed
	// get_cache_group
	// cache_get($key)
	// cache_set($key, $data, $cache_time=DAY_IN_SECONDS)
	// cache_add($key, $data, $cache_time=DAY_IN_SECONDS)
	// cache_delete($key)
	public static function __callStatic($method, $args){
		if(in_array($method, ['item_callback', 'render_item', 'parse_item', 'render_date'])){
			return $args[0];
		}elseif($method == 'query_data'){
			$args	= $args[0];

			if(!isset($args['orderby'])){
				$args['orderby']	= wpjam_get_data_parameter('orderby');	
			}

			if(!isset($args['order'])){
				$args['order']		= wpjam_get_data_parameter('order');	
			}

			if(!isset($args['search'])){
				$args['search']		= wpjam_get_data_parameter('s');	
			}

			foreach(static::get_filterable_fields() as $filter_key){
				if(!isset($args[$filter_key])){
					$args[$filter_key]	= wpjam_get_data_parameter($filter_key);
				}
			}
			
			$_query = new WPJAM_Query(static::get_handler(), $args);

			return ['items'=>$_query->items, 'total'=>$_query->total];
		}

		return self::call_handler_method($method, ...$args);
	}

	protected static function call_handler_method($method, ...$args){
		$method_map	= [
			'list'		=> 'query_items',
			'get_ids'	=> 'get_by_ids',
			'get_all'	=> 'get_results'
		];

		if(isset($method_map[$method])){
			$method	= $method_map[$method];
		}

		$handler	= static::get_handler();

		if(method_exists($handler, $method) || method_exists($handler, '__call')){
			// WPJAM_DB 可能因为 cache 设置为 false
			// 不能直接调用 WPJAM_DB 的 cache_xxx 方法
			if(in_array($method, ['cache_get', 'cache_set', 'cache_add', 'cache_delete'])){
				$method	.= '_force';
			}

			return call_user_func_array([$handler, $method], $args);
		}else{
			return new WP_Error('undefined_method', '「'.$method.'」方法未定义');
		}
	}

	public static function Query($args=[]){
		$handler	= static::get_handler();

		if($args){
			return new WPJAM_Query($handler, $args);
		}else{
			return $handler;
		}
	}

	public static function get_list_cache(){
		return new WPJAM_listCache(self::get_cache_group());
	}

	public static function get_one_by($field, $value, $order='ASC'){
		$items = static::get_by($field, $value, $order);
		return $items ? current($items) : [];
	}

	public static function delete_by($field, $value){
		return static::get_handler()->delete([$field=>$value]);
	}

	public static function delete_multi($ids){
		$handler	= static::get_handler();

		if(method_exists($handler, 'delete_multi')){
			return $handler->delete_multi($ids);
		}elseif($ids){
			foreach($ids as $id){
				$result	= $handler->delete($id);

				if(is_wp_error($result)){
					return $result;
				}
			}

			return true;
		}
	}

	public static function get_by_cache_keys($values){
		_deprecated_function(__METHOD__, 'WPJAM Basic 4.4', 'WPJAM_Model::update_caches');
		return static::update_caches($values);
	}
}

class WPJAM_Query{
	public $query;
	public $query_vars;
	public $request;
	public $items;
	public $total		= 0;
	public $handler;

	public function __construct($handler, $query=''){
		$this->handler	= $handler;

		if(!empty($query)){
			$this->query($query);
		}
	}

	public function __call($name, $args){
		return $this->handler->$name(...$args);
	}

	public function __get($key){
		if($key == 'datas'){
			return $this->items;
		}elseif($key == 'found_rows'){
			return $this->total;
		}elseif($key == 'max_num_pages'){
			if($this->total && $this->query_vars['number'] && $this->query_vars['number'] != -1){
				return ceil($this->total / $this->query_vars['number']);
			}

			return 0;
		}elseif($key == 'next_cursor'){
			if($this->items){
				$orderby	= $this->query_vars['orderby'];

				return (int)(end($this->items)[$orderby]);
			}

			return 0;
		}else{
			return null;
		}
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public function query($query){
		$this->query		= $query;
		$this->query_vars	= wp_parse_args($query, [
			'number'	=> 50,
			'orderby'	=> $this->get_primary_key()
		]);

		if($this->get_meta_type()){
			$meta_query	= new WP_Meta_Query();
			$meta_query->parse_query_vars($query);

			$this->set_meta_query($meta_query);
			$this->query_vars	= wpjam_array_except($this->query_vars, ['meta_key', 'meta_value', 'meta_value_num', 'meta_compare', 'meta_query']);
		}

		$this->query_vars	= apply_filters_ref_array('wpjam_query_vars', [$this->query_vars, $this]);

		$orderby 	= $this->query_vars['orderby'];
		$fields		= wpjam_array_pull($this->query_vars, 'fields');

		$total_required	= false;
		$cache_required	= $orderby != 'rand';

		foreach($this->query_vars as $key => $value){
			if(is_null($value)){
				continue;
			}

			if(strpos($key, '__in_set')){
				$this->find_in_set($value, str_replace('__in_set', '', $key));
			}elseif(strpos($key, '__in')){
				$this->where_in(str_replace('__in', '', $key), $value);
			}elseif(strpos($key, '__not_in')){
				$this->where_not_in(str_replace('__not_in', '', $key), $value);
			}elseif(is_array($value)){
				$this->where($key, $value);
			}elseif($key == 'number'){
				if($value != -1){
					$total_required	= true;

					$this->limit($value);
				}
			}elseif($key == 'offset'){
				$total_required	= true;

				$this->offset($value);
			}elseif($key == 'orderby'){
				$this->orderby($value);
			}elseif($key == 'order'){
				$this->order($value);
			}elseif($key == 'first'){
				$this->where_gt($orderby, $value);
			}elseif($key == 'cursor'){
				if($value > 0){
					$this->where_lt($orderby, $value);
				}
			}elseif($key == 'search'){
				$this->search($value);
			}else{
				$this->where($key, $value);
			}
		}

		if($total_required){
			$this->found_rows(true);
		}

		$clauses	= apply_filters_ref_array('wpjam_clauses', [$this->get_clauses($fields), &$this]);
		$request	= apply_filters_ref_array('wpjam_request', [$this->get_sql_by_clauses($clauses), &$this]);

		$this->request	= $request;

		if($cache_required){
			$last_changed	= $this->get_last_changed();
			$cache_group	= $this->get_cache_group();
			$cache_prefix	= $this->get_cache_prefix();
			$key			= md5(maybe_serialize($this->query).$request);
			$cache_key		= 'wpjam_query:'.$key.':'.$last_changed;
			$cache_key		= $cache_prefix ? $cache_prefix.':'.$cache_key : $cache_key;

			$result			= wp_cache_get($cache_key, $cache_group);
		}else{
			$result			= false;
		}

		if($result === false){
			$items	= $GLOBALS['wpdb']->get_results($request, ARRAY_A);
			$result	= ['items'=>$this->filter_results($items)];

			if($total_required){
				$result['total']	= $this->find_total();
			}

			if($cache_required){
				wp_cache_set($cache_key, $result, $cache_group, DAY_IN_SECONDS);
			}
		}else{
			// 兼容代码
			$result['items']	= $result['items'] ?? $result['datas'];

			if($total_required){
				$result['total']	= $result['total'] ?? $result['found_rows'];
			}
		}

		$this->items	= apply_filters_ref_array('wpjam_queried_items', [$result['items'], &$this]);
		
		if($total_required){
			$this->total	= $result['total'];
		}

		return $this->items;
	}
}

abstract class WPJAM_Items{
	protected $args	= 0;
	protected $items	= [];

	abstract public function get_items();
	abstract public function update_items($items);

	public function delete_items(){
		return true;
	}

	public function __construct($args=[]){
		if(!isset($args['max_items'])){
			$args['max_items']	= $args['total'] ?? 0;	// 兼容
		}

		$this->args = wp_parse_args($args, [
			'primary_key'	=> 'id',
			'primary_title'	=> 'ID'
		]);

		$this->items	= $this->get_items();
	}

	public function __get($key){
		return $this->args[$key] ?? null;
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public function get_primary_key(){
		return $this->primary_key;
	}

	public function get_results(){
		return $this->parse_items();
	}

	public function save(){
		return $this->update_items($this->items);
	}

	public function reset(){
		$result	= $this->delete_items();

		if($result && !is_wp_error($result)){
			$this->items	= $this->get_items();
		}

		return $result;
	}

	public function parse_items($items=null){
		$items	= $items ?? $this->items;

		if($items && is_array($items)){
			foreach($items as $id => &$item){
				$item[$this->primary_key]	= $id;
			}

			unset($item);
		}else{
			$items	= [];
		}

		return $items;
	}

	public function exists($value){
		return $this->items ? in_array($value, array_column($this->items, $this->unique_key)) : false;
	}

	public function get($id){
		return $this->items[$id] ?? false;
	}

	public function is_over_max_items(){
		if($this->max_items && count($this->items) >= $this->max_items){
			return new WP_Error('over_total', '最大允许数量：'.$this->max_items);
		}

		return false;
	}

	public function insert($item, $last=false){
		$result	= $this->is_over_max_items();

		if($result && is_wp_error($result)){
			return $result;
		}

		$item	= wpjam_array_filter($item, function($v){ return !is_null($v); });

		if(in_array($this->primary_key, ['option_key', 'id'])){
			if($this->unique_key){
				if(empty($item[$this->unique_key])){
					return new WP_Error('empty_'.$this->unique_key, $this->unique_title.'不能为空');
				}

				if($this->exists($item[$this->unique_key])){
					return new WP_Error('duplicate_'.$this->unique_key, $this->unique_title.'重复');
				}
			}

			if($this->items){
				$ids	= array_keys($this->items);
				$ids	= array_map(function($id){	return (int)(str_replace('option_key_', '', $id)); }, $ids);

				$id		= max($ids);
				$id		= $id+1;
			}else{
				$id		= 1;
			}

			if($this->primary_key == 'option_key'){
				$id		= 'option_key_'.$id;
			}

			$item[$this->primary_key]	= $id;
		}else{
			if(empty($item[$this->primary_key])){
				return new WP_Error('empty_'.$this->primary_key, $this->primary_title.'不能为空');
			}

			$id	= $item[$this->primary_key];

			if(isset($this->items[$id])){
				return new WP_Error('duplicate_'.$this->primary_key, $this->primary_title.'值重复');
			}
		}

		if($last){
			$this->items[$id]	= $item;
		}else{
			$this->items		= [$id=>$item]+$this->items;
		}

		$result	= $this->save();

		if(is_wp_error($result)){
			return $result;
		}

		return $id;
	}

	public function update($id, $item){
		if(!isset($this->items[$id])){
			return new WP_Error('invalid_'.$this->primary_key, $this->primary_title.'为「'.$id.'」的数据的不存在');
		}

		if(in_array($this->primary_key, ['option_key', 'id'])){
			if($this->unique_key && isset($item[$this->unique_key])){
				if(empty($item[$this->unique_key])){
					return new WP_Error('empty_'.$this->unique_key, $this->unique_title.'不能为空');
				}

				if($item[$this->unique_key] != $this->items[$id][$this->unique_key]){
					if($this->exists($item[$this->unique_key])){
						return new WP_Error('duplicate_'.$this->unique_key, $this->unique_title.'重复');
					}
				}
			}
		}

		$item[$this->primary_key] = $id;

		$item	= wp_parse_args($item, $this->items[$id]);
		$item	= wpjam_array_filter($item, function($v){ return !is_null($v); });

		$this->items[$id]	= $item;

		return $this->save();
	}

	public function delete($id){
		if(!isset($this->items[$id])){
			return new WP_Error('invalid_'.$this->primary_key, $this->primary_title.'为「'.$id.'」的数据的不存在');
		}

		$this->items	= wpjam_array_except($this->items, $id);

		return $this->save();
	}

	public function move($id, $data){
		$items	= $this->items;

		if(empty($items) || empty($items[$id])){
			return new WP_Error('key_not_exists', $id.'的值不存在');
		}

		$next	= $data['next'] ?? false;
		$prev	= $data['prev'] ?? false;

		if(!$next && !$prev){
			return new WP_Error('invalid_move', '无效移动位置');
		}

		$item	= wpjam_array_pull($items, $id);

		if($next){
			if(empty($items[$next])){
				return new WP_Error('key_not_exists', $next.'的值不存在');
			}

			$offset	= array_search($next, array_keys($items));

			if($offset){
				$items	= array_slice($items, 0, $offset, true) +  [$id => $item] + array_slice($items, $offset, null, true);
			}else{
				$items	= [$id => $item] + $items;
			}
		}else{
			if(empty($items[$prev])){
				return new WP_Error('key_not_exists', $prev.'的值不存在');
			}

			$offset	= array_search($prev, array_keys($items));
			$offset ++;

			if($offset){
				$items	= array_slice($items, 0, $offset, true) +  [$id => $item] + array_slice($items, $offset, null, true);
			}else{
				$items	= [$id => $item] + $items;
			}
		}

		$this->items	= $items;

		return $this->save();
	}

	public function query_items($limit, $offset){
		return ['items'=>$this->parse_items(), 'total'=>count($this->items)];
	}
}

class WPJAM_Option_Items extends WPJAM_Items{
	private $option_name;
	
	public function __construct($option_name, $args=[]){
		$this->option_name	= $option_name;

		if(!is_array($args)){
			$args	= ['primary_key' => $args];
		}else{
			$args	= wp_parse_args($args, ['primary_key'=>'option_key']);
		}

		parent::__construct($args);
	}

	public function get_items(){
		return get_option($this->option_name) ?: [];
	}

	public function update_items($items){
		if($items && in_array($this->get_primary_key(), ['option_key','id'])){
			foreach ($items as &$item){
				unset($item[$this->get_primary_key()]);
			}

			unset($item);
		}

		return update_option($this->option_name, $items);
	}

	public function delete_items(){
		return delete_option($this->option_name);
	}
}

class WPJAM_Meta_Items extends WPJAM_Items{
	private $meta_type;
	private $object_id;
	private $meta_key;

	public function __construct($meta_type, $object_id, $meta_key, $args=[]){
		$this->meta_type	= $meta_type;
		$this->object_id	= $object_id;
		$this->meta_key		= $meta_key;

		parent::__construct($args);
	}

	public function get_items(){
		return get_metadata($this->meta_type, $this->object_id, $this->meta_key, true) ?: [];
	}

	public function update_items($items){
		if($items && in_array($this->get_primary_key(), ['option_key','id'])){
			foreach($items as &$item){
				unset($item[$this->get_primary_key()]);
				unset($item[$this->meta_type.'_id']);
			}

			unset($item);
		}

		return update_metadata($this->meta_type, $this->object_id, $this->meta_key, $items);
	}

	public function delete_items(){
		return delete_metadata($this->meta_type, $this->object_id, $this->meta_key);
	}
}

class WPJAM_Content_Items extends WPJAM_Items{
	private $post_id;

	public function __construct($post_id, $args=[]){
		$this->post_id	= $post_id;

		parent::__construct($args);
	}

	public function get_items(){
		$_post	= get_post($this->post_id);

		return ($_post && $_post->post_content) ? maybe_unserialize($_post->post_content) : [];
	}

	public function update_items($items){
		if($items && in_array($this->get_primary_key(), ['option_key','id'])){
			foreach($items as &$item){
				unset($item[$this->get_primary_key()]);
				unset($item['post_id']);
			}

			unset($item);

			$content	= maybe_serialize($items);
		}else{
			$content	= '';
		}
		
		return WPJAM_Post::update($this->post_id, ['post_content'=>$content]);
	}

	public function delete_items(){
		return WPJAM_Post::update($this->post_id, ['post_content'=>'']);
	}
}

class_alias('WPJAM_Option_Items', 'WPJAM_Option');
class_alias('WPJAM_Items', 'WPJAM_Item');