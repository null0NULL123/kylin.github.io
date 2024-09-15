<?php
class WPJAM_Taxonomy_Setting{
	use WPJAM_Setting_Trait;

	private function __construct(){
		$this->init('wpjam_taxonomy_setting');
	}
}

function wpjam_taxonomy_get_setting($setting_name, $default=null){
	return WPJAM_Taxonomy_Setting::get_instance()->get_setting($setting_name, $default);
}

function wpjam_taxonomy_delete_setting($setting_name){
	return WPJAM_Taxonomy_Setting::get_instance()->delete_setting($setting_name);
}

function wpjam_get_tag_group($slug){
	$tag_groups	= get_option('wpjam_post_tag_groups') ?: [];

	foreach($tag_groups as $tag_group){
		if($tag_group['slug'] == $slug){
			return $tag_group;
		}
	}

	return null;
}

function wpjam_is_taxonomy_sortable($taxonomy){
	if(empty($taxonomy)){
		return false;
	}elseif(is_array($taxonomy)){
		if(count($taxonomy) > 1 || empty(current($taxonomy))){
			return false;
		}

		$taxonomy	= current($taxonomy);
	}

	if(is_taxonomy_hierarchical($taxonomy)){
		return (bool)get_taxonomy($taxonomy)->sortable;
	}

	return false;
}