<?php
class WPJAM_AliyunSMS{
	private $access_key_id = '';
	private $access_key_secret = '';
	private static $templates = [];

	public function __construct($access_key_id, $access_key_secret){
		$this->access_key_id		= $access_key_id;
		$this->access_key_secret	= $access_key_secret;
	}

	public function send($params){
		$params['Action']	= 'SendSms';
		$result	= $this->http_request($params);

		if(is_wp_error($result)){
			if($result->get_error_code() == 'isv.AMOUNT_NOT_ENOUGH'){
				WPJAM_Notice::add(array(
					'key'		=> 'aliyun_sms_amount_not_enough',
					'type'		=> 'success',
					'notice'	=> '阿里云短信服务账户余额不足，短信通知发送失败，请点击<a href="https://wpjam.com/go/aliyun-sms/" target="_blank">这里充值</a>！',
				));		
			}
		}

		return $result;
	}

	public function query_sign($params){
		$params['Action']	= 'QuerySmsSign';
		return $this->http_request($params);
	}

	public function add_template($params){
		$params['Action']	= 'AddSmsTemplate';
		return $this->http_request($params);
	}

	public function http_request($params){
		$params['Format']			= 'JSON';
		$params['Format']			= 'JSON';
		$params['SignatureMethod']	= 'HMAC-SHA1';
		$params['SignatureNonce']	= uniqid(mt_rand(0,0xffff), true);
		$params['SignatureVersion']	= '1.0';
		$params['Version']			= '2017-05-25';
		$params['AccessKeyId']		= $this->access_key_id;
		$params['Timestamp']		= gmdate("Y-m-d\TH:i:s\Z");
		$params['Signature']		= $this->generate_signature($params);

		$err_args = ['errcode'=>'Code', 'errmsg'=>'Message', 'success'=>'OK'];

		return wpjam_remote_request('http://dysmsapi.aliyuncs.com/?'.http_build_query($params),[],$err_args);
	}

	private function encode($string) {
		$string	= urlencode($string);
		$string	= preg_replace('/\+/', '%20', $string);
		$string	= preg_replace('/\*/', '%2A', $string);
		$string	= preg_replace('/%7E/', '~', $string);

		return $string;
	}

	private function generate_signature($params) {
		ksort($params);

		$query_string = '';

		foreach($params as $key => $value){
			$query_string	.= '&' . $this->encode($key) . '=' . $this->encode($value);
		}

		$string_to_signature	= 'GET&%2F&' . $this->encode(substr($query_string, 1 ));

		return base64_encode(hash_hmac('sha1', $string_to_signature, $this->access_key_secret.'&', true));
	}

	public static function get_templates(){
		return self::$templates;
	}

	public static function register_template($key, $args){
		self::$templates[$key]	= $args;
	}

	public static function unregister_template($key){
		unset(self::$templates[$key]);
	}
}