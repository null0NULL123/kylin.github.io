<?php
class WPJAM_Post_Tag_Groups_Admin extends WPJAM_Model{
	private static $handler;

	public static function get_handler(){
		if(is_null(static::$handler)){
			static::$handler	= new WPJAM_Option('wpjam_post_tag_groups', ['total'=>50, 'unique_key'=>'slug', 'unique_title'=>'别名']);
		}

		return static::$handler;
	}

	public static function query_items($limit, $offset){
		list('items' => $items, 'total' => $total) = parent::query_items($limit, $offset);

		$tags	= array_merge(...array_column($items, 'tags'));

		WPJAM_Term::get_by_ids($tags);

        return compact('items', 'total');
    }

	public static function render_item($item){
		$item['name']	= $item['name'].'（'.$item['slug'].'）';
		
		$tags	= WPJAM_Term::get_by_ids($item['tags']);
		$tags	= array_column($tags, 'name');

		$item['tags']	= implode('&emsp;', $tags);
		$item['row_actions']['view']	= '<a href="'.user_trailingslashit(home_url('tag-group/'.$item['slug'])).'" target="_blank">查看</a>';

		return $item;
	}

	public static function get_actions(){
		return  [
			'add'	=> ['title'=>'新增',	'last'=>true],
			'edit'	=> ['title'=>'编辑'],
			'delete'=> ['title'=>'删除',	'direct'=>true,	'confirm'=>true,	'bulk'=>true],
			'view'	=> ['title'=>'查看'],
		];
	}

	public static function get_fields($action_key='', $id=0){
		$options	= ['related'=>'显示含有任意一个标签的文章，并按相关度排序','or'=>'显示含有任意一个标签的文章，并按发布时间排序','and'=>'仅显示含有全部标签的文章'];

		return [
			'name'		=> ['title'=>'名称',	'type'=>'text',	'class'=>'',	'show_admin_column'=>true],
			'slug'		=> ['title'=>'别名',	'type'=>'text',	'class'=>''],
			'tags'		=> array_merge(wpjam_get_term_id_field('post_tag'), ['type'=>'mu-text',	'max_items'=>50, 'show_admin_column'=>true]),
			'relation'	=> ['title'=>'列表',	'type'=>'select',	'options'=>$options,	'show_admin_column'=>true]
		];
	}
}