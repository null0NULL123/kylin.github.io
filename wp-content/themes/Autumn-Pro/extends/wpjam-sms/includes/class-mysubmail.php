<?php
class WPJAM_MySubMail{
	private $appid = '';
	private $appkey = '';
	private static $projects = [];
	private static $subhook_keys = [];
	private $enpoint = 'https://api.mysubmail.com/';

	public function __construct($appid, $appkey){
		$this->appid	= $appid;
		$this->appkey	= $appkey;
	}

	public function xsend($args){
		$params = wp_array_slice_assoc($args, ['to', 'project', 'vars']);
		return $this->http_request('message/xsend.json', 'POST', $params);
	}

	public function xsend_international($args){
		$params = wp_array_slice_assoc($args, ['to', 'project', 'vars']);
		return $this->http_request('internationalsms/xsend.json', 'POST', $params);
	}

	// public function add_template($args){
	// 	return $this->http_request($params);
	// }

	public function http_request($api, $method, $params = []){
		$body = array_merge([
			'appid'     => $this->appid,
			'signature' => $this->appkey,
		], (array) $params);

		return wpjam_remote_request($this->enpoint . $api, array(
			'method'	=> $method,
			'body'		=> $body,
		));
	}

	public static function get_projects(){
		return self::$projects;
	}

	public static function register_project($key, $args){
		self::$projects[$key]	= $args;
	}

	public static function unregister_project($key){
		unset(self::$projects[$key]);
	}

	public function get_template($template_id = '')
    {
        return $this->http_request('message/template.json', 'GET', compact('template_id'));
    }

    public function update_template($template)
    {
		$params = wp_array_slice_assoc($template, ['sms_title', 'sms_signature', 'sms_content', 'template_id']);
        return $this->http_request('message/template.json', "PUT", $params);
    }

    public function create_template($template)
    {
		$params = wp_array_slice_assoc($template, ['sms_title', 'sms_signature', 'sms_content']);
        return $this->http_request('message/template.json', "POST", $params);
    }

    public function delete_template($template_id)
    {
        return $this->http_request('message/template.json', "DELETE", compact('template_id'));
	}

	public static function register_subhook_key($appid, $key)
	{
		self::$subhook_keys[$appid]	= $key;
	}
	
	public static function event_verify($data)
	{
		$config = [];
		foreach (['wpjam_international_submail', 'wpjam_mysubmail'] as $options_name) {
			$appid       = wpjam_get_setting($options_name, 'appid');
			$subhook_key = wpjam_get_setting($options_name, 'subhook_key');
			$config[$appid] = $subhook_key;
		}

		$subhook_key = $config[$data['app']] ?? '';
		if (empty($subhook_key)) return false;
		
		$sign = md5($data['token'] ?? '' . $subhook_key);
		return $sign == $data['signature'];
	}
}