<?php
class WPJAM_Var{
	public $data	= [];

	public static $instance	= null;

	private function __construct(){
		$this->data	= self::parse_user_agent();
	}

	public function __get($name){
		$value	= $this->data[$name] ?? null;
		
		return apply_filters('wpjam_determine_'.$name.'_var', $value);
	}

	public function __isset($key){
		return $this->$key !== null;
	}

	public static function get_instance(){
		if(is_null(self::$instance)){
			self::$instance	= new self();
		}

		return self::$instance;
	}

	public static function get_ip(){
		return $_SERVER['REMOTE_ADDR'] ??'';
	}

	public static function parse_ip($ip=''){
		$ip	= $ip ?: self::get_ip();

		if($ip == 'unknown'){
			return false;
		}

		$ipdata	= IP::find($ip);

		return [
			'ip'		=> $ip,
			'country'	=> $ipdata['0'] ?? '',
			'region'	=> $ipdata['1'] ?? '',
			'city'		=> $ipdata['2'] ?? '',
			'isp'		=> '',
		];
	}

	public static function parse_user_agent($user_agent='', $referer=''){
		$user_agent	= $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
		$user_agent	= $user_agent.' ';	// 为了特殊情况好匹配
		$referer	= $referer ?: $_SERVER['HTTP_REFERER'] ?? '';

		$os = $device =  $app = $browser = '';
		$os_version = $browser_version = $app_version = 0;

		if(strpos($user_agent, 'iPhone') !== false){
			$device	= 'iPhone';
			$os 	= 'iOS';
		}elseif(strpos($user_agent, 'iPad') !== false){
			$device	= 'iPad';
			$os 	= 'iOS';
		}elseif(strpos($user_agent, 'iPod') !== false){
			$device	= 'iPod';
			$os 	= 'iOS';
		}elseif(strpos($user_agent, 'Android') !== false){
			$os		= 'Android';

			if(preg_match('/Android ([0-9\.]{1,}?); (.*?) Build\/(.*?)[\)\s;]{1}/i', $user_agent, $matches)){
				if(!empty($matches[1]) && !empty($matches[2])){
					$os_version	= trim($matches[1]);

					$device		= $matches[2];

					if(strpos($device,';')!==false){
						$device	= substr($device, strpos($device,';')+1, strlen($device)-strpos($device,';'));
					}

					$device		= trim($device);
					// $build	= trim($matches[3]);
				}
			}
		}elseif(stripos($user_agent, 'Windows NT')){
			$os		= 'Windows';
		}elseif(stripos($user_agent, 'Macintosh')){
			$os		= 'Macintosh';
		}elseif(stripos($user_agent, 'Windows Phone')){
			$os		= 'Windows Phone';
		}elseif(stripos($user_agent, 'BlackBerry') || stripos($user_agent, 'BB10')){
			$os		= 'BlackBerry';
		}elseif(stripos($user_agent, 'Symbian')){
			$os		= 'Symbian';
		}else{
			$os		= 'unknown';
		}

		if($os == 'iOS'){
			if(preg_match('/OS (.*?) like Mac OS X[\)]{1}/i', $user_agent, $matches)){
				$os_version	= (float)(trim(str_replace('_', '.', $matches[1])));
			}
		}

		if(strpos($user_agent, 'MicroMessenger') !== false){
			if(strpos($referer, 'https://servicewechat.com') !== false){
				$app	= 'weapp';
			}else{
				$app	= 'weixin';
			}

			if(preg_match('/MicroMessenger\/(.*?)\s/', $user_agent, $matches)){
				$app_version = $matches[1];
			}

			if(preg_match('/NetType\/(.*?)\s/', $user_agent, $matches)){
				$net_type = $matches[1];
			}
		}elseif(strpos($user_agent, 'ToutiaoMicroApp') !== false || strpos($referer, 'https://tmaservice.developer.toutiao.com') !== false){
			$app	= 'bytedance';
		}

		if(strpos($user_agent, 'Lynx') !== false){
			$browser	= 'lynx';
		}elseif(stripos($user_agent, 'safari') !== false){
			$browser	= 'safrai';

			if(preg_match('/Version\/(.*?)\s/i', $user_agent, $matches)){
				$browser_version	= (float)(trim($matches[1]));
			}
		}elseif(strpos($user_agent, 'Edge') !== false){
			$browser	= 'edge';

			if(preg_match('/Edge\/(.*?)\s/i', $user_agent, $matches)){
				$browser_version	= (float)(trim($matches[1]));
			}
		}elseif(stripos($user_agent, 'chrome')){
			$browser	= 'chrome';

			if(preg_match('/Chrome\/(.*?)\s/i', $user_agent, $matches)){
				$browser_version	= (float)(trim($matches[1]));
			}
		}elseif(stripos($user_agent, 'Firefox') !== false){
			$browser	= 'firefox';

			if(preg_match('/Firefox\/(.*?)\s/i', $user_agent, $matches)){
				$browser_version	= (float)(trim($matches[1]));
			}
		}elseif(strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false){
			$browser	= 'ie';
		}elseif(strpos($user_agent, 'Gecko') !== false){
			$browser	= 'gecko';
		}elseif(strpos($user_agent, 'Opera') !== false){
			$browser	= 'opera';
		}

		return compact('os', 'device', 'app', 'browser', 'os_version', 'browser_version', 'app_version');
	}
}

class WPJAM_Bit{
	protected $bit;

	public function __construct($bit=0){
		$this->bit	= $bit;
	}

	public function __get($name){
		return $name == 'bit' ? $this->bit : null;
	}

	public function __isset($name){
		return $name == 'bit';
	}

	public function has($bit){
		return ($this->bit & $bit) == $bit;
	}

	public function add($bit){
		$this->bit = $this->bit | (int)$bit;

		return $this->bit;
	}

	public function remove($bit){
		$this->bit = $this->bit & (~(int)$bit);

		return $this->bit;
	}

	protected function set_bit($bit){
		$this->bit	= $bit;
	}

	protected function get_bit(){
		return $this->bit;
	}
}

class WPJAM_Crypt{
	private $method		= 'aes-256-cbc';
	private $key 		= '';
	private $iv			= '';
	private $options	= OPENSSL_ZERO_PADDING;
	private $block_size	= 32;	// 注意 PHP 默认 aes cbc 算法的 block size 都是 16 位

	public function __construct($args=[]){
		foreach ($args as $key => $value) {
			if(in_array($key, ['key', 'method', 'options', 'iv', 'block_size'])){
				$this->$key	= $value;
			}
		}
	}

	public function encrypt($text){
		if($this->options == OPENSSL_ZERO_PADDING && $this->block_size){
			$text	= $this->pkcs7_pad($text, $this->block_size);	//使用自定义的填充方式对明文进行补位填充
		}

		return openssl_encrypt($text, $this->method, $this->key, $this->options, $this->iv);
	}

	public function decrypt($encrypted_text){
		try{
			$text	= openssl_decrypt($encrypted_text, $this->method, $this->key, $this->options, $this->iv);
		}catch(Exception $e){
			return new WP_Error('decrypt_aes_failed', 'aes 解密失败');
		}

		if($this->options == OPENSSL_ZERO_PADDING && $this->block_size){
			$text	= $this->pkcs7_unpad($text, $this->block_size);	//去除补位字符
		}

		return $text;
	}

	public static function pkcs7_pad($text, $block_size=32){	//对需要加密的明文进行填充 pkcs#7 补位
		//计算需要填充的位数
		$amount_to_pad	= $block_size - (strlen($text) % $block_size);
		$amount_to_pad	= $amount_to_pad ?: $block_size;

		//获得补位所用的字符
		return $text . str_repeat(chr($amount_to_pad), $amount_to_pad);
	}

	public static function pkcs7_unpad($text, $block_size){	//对解密后的明文进行补位删除
		$pad	= ord(substr($text, -1));

		if($pad < 1 || $pad > $block_size){
			$pad	= 0;
		}

		return substr($text, 0, (strlen($text) - $pad));
	}

	public static function weixin_pad($text, $appid){
		$random = self::generate_random_string(16);		//获得16位随机字符串，填充到明文之前
		return $random.pack("N", strlen($text)).$text.$appid;
	}

	public static function weixin_unpad($text, &$appid){	//去除16位随机字符串,网络字节序和AppId
		$text		= substr($text, 16, strlen($text));
		$len_list	= unpack("N", substr($text, 0, 4));
		$text_len	= $len_list[1];
		$appid		= substr($text, $text_len + 4);
		return substr($text, 4, $text_len);
	}

	public static function sha1(...$args){
		sort($args, SORT_STRING);

		return sha1(implode($args));
	}

	public static function generate_weixin_signature($token, &$timestamp='', &$nonce='', $encrypt_msg=''){
		$timestamp	= $timestamp ?: time();
		$nonce		= $nonce ?: self::generate_random_string(8);
		return self::sha1($encrypt_msg, $token, $timestamp, $nonce);
	}

	public static function generate_random_string($length){
		$alphabet	= "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		$max		= strlen($alphabet);
		$token		= '';

		for($i = 0; $i < $length; $i++){
			$token	.= $alphabet[self::crypto_rand_secure(0, $max - 1)];
		}

		return $token;
	}

	private static function crypto_rand_secure($min, $max){
		$range	= $max - $min;

		if($range < 1){
			return $min;
		}

		$log	= ceil(log($range, 2));
		$bytes	= (int)($log / 8) + 1;		// length in bytes
		$bits	= (int)$log + 1;			// length in bits
		$filter	= (int)(1 << $bits) - 1;	// set all lower bits to 1

		do {
			$rnd	= hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd	= $rnd & $filter;	// discard irrelevant bits
		}while($rnd > $range);

		return $min + $rnd;
	}
}

class WPJAM_Cache_Group{
	private $group;

	public function __construct($group, $global=false){
		$this->group	= $group;

		if($global){
			wp_cache_add_global_groups($group);
		}
	}

	public function cache_get($key){
		return wp_cache_get($key, $this->group);
	}

	public function cache_add($key, $value, $time=DAY_IN_SECONDS){
		return wp_cache_add($key, $value, $this->group, $time);
	}

	public function cache_set($key, $value, $time=DAY_IN_SECONDS){
		return wp_cache_set($key, $value, $this->group, $time);
	}

	public function cache_delete($key){
		return wp_cache_delete($key, $this->group);
	}

	private static $instances	= [];

	public static function get_instance($group, $global=false){
		if(!isset(self::$instances[$group])){
			self::$instances[$group]	= new self($group, $global);
		}

		return self::$instances[$group];
	}
}

class WPJAM_ListCache{
	private $key;

	public function __construct($key){
		$this->key	= $key;
	}

	private function get_items(&$cas_token){
		$items	= wp_cache_get_with_cas($this->key, 'wpjam_list_cache', $cas_token);

		if($items === false){
			$items	= [];
			wp_cache_add($this->key, [], 'wpjam_list_cache', DAY_IN_SECONDS);
			$items	= wp_cache_get_with_cas($this->key, 'wpjam_list_cache', $cas_token);
		}

		return $items;
	}

	private function set_items($cas_token, $items){
		return wp_cache_cas($cas_token, $this->key, $items, 'wpjam_list_cache', DAY_IN_SECONDS);
	}

	public function get_all(){
		$items	= wp_cache_get($this->key, 'wpjam_list_cache');
		return $items ?: [];
	}

	public function get($k){
		$items = $this->get_all();
		return $items[$k]??false;  
	}

	public function add($item, $k=null){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items	= $this->get_items($cas_token);

			if($k!==null){
				if(isset($items[$k])){
					return false;
				}

				$items[$k]	= $item;
			}else{
				$items[]	= $item;
			}

			$result	= $this->set_items($cas_token, $items);

			$retry	 -= 1;
		}while (!$result && $retry > 0);

		return $result;
	}

	public function increment($k, $offset=1){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items		= $this->get_items($cas_token);
			$items[$k]	= $items[$k]??0; 
			$items[$k]	= $items[$k]+$offset;

			$result	= $this->set_items($cas_token, $items);

			$retry	 -= 1;
		}while (!$result && $retry > 0);

		return $result;
	}

	public function decrement($k, $offset=1){
		return $this->increment($k, 0-$offset);
	}

	public function set($item, $k){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items		= $this->get_items($cas_token);
			$items[$k]	= $item;
			$result		= $this->set_items($cas_token, $items);
			$retry 		-= 1;
		}while(!$result && $retry > 0);

		return $result;
	}

	public function remove($k){
		$cas_token	= '';
		$retry		= 10;

		do{
			$items	= $this->get_items($cas_token);
			if(!isset($items[$k])){
				return false;
			}
			unset($items[$k]);
			$result	= $this->set_items($cas_token, $items);
			$retry 	-= 1;
		}while(!$result && $retry > 0);

		return $result;
	}

	public function empty(){
		$cas_token		= '';
		$retry	= 10;

		do{
			$items	= $this->get_items($cas_token);
			if($items == []){
				return [];
			}
			$result	= $this->set_items($cas_token, []);
			$retry 	-= 1;
		}while(!$result && $retry > 0);

		if($result){
			return $items;
		}

		return $result;
	}
}

class WPJAM_Cache{
	/* HTML 片段缓存
	Usage:

	if (!WPJAM_Cache::output('unique-key')) {
		functions_that_do_stuff_live();
		these_should_echo();
		WPJAM_Cache::store(3600);
	}
	*/
	public static function output($key) {
		$output	= get_transient($key);
		if(!empty($output)) {
			echo $output;
			return true;
		} else {
			ob_start();
			return false;
		}
	}

	public static function store($key, $cache_time='600') {
		$output = ob_get_flush();
		set_transient($key, $output, $cache_time);
		echo $output;
	}
}

class IP{
	private static $ip = null;
	private static $fp = null;
	private static $offset = null;
	private static $index = null;
	private static $cached = [];

	public static function find($ip){
		if (empty( $ip ) === true) {
			return 'N/A';
		}

		$nip	= gethostbyname($ip);
		$ipdot	= explode('.', $nip);

		if ($ipdot[0] < 0 || $ipdot[0] > 255 || count($ipdot) !== 4) {
			return 'N/A';
		}

		if (isset( self::$cached[$nip] ) === true) {
			return self::$cached[$nip];
		}

		if (self::$fp === null) {
			self::init();
		}

		$nip2 = pack('N', ip2long($nip));

		$tmp_offset	= (int) $ipdot[0] * 4;
		$start		= unpack('Vlen',
			self::$index[$tmp_offset].self::$index[$tmp_offset + 1].self::$index[$tmp_offset + 2].self::$index[$tmp_offset + 3]);

		$index_offset = $index_length = null;
		$max_comp_len = self::$offset['len'] - 1024 - 4;
		for ($start = $start['len'] * 8 + 1024; $start < $max_comp_len; $start += 8) {
			if (self::$index[$start].self::$index[$start+1].self::$index[$start+2].self::$index[$start+3] >= $nip2) {
				$index_offset = unpack('Vlen',
					self::$index[$start+4].self::$index[$start+5].self::$index[$start+6]."\x0");
				$index_length = unpack('Clen', self::$index[$start+7]);

				break;
			}
		}

		if ($index_offset === null) {
			return 'N/A';
		}

		fseek(self::$fp, self::$offset['len'] + $index_offset['len'] - 1024);

		self::$cached[$nip] = explode("\t", fread(self::$fp, $index_length['len']));

		return self::$cached[$nip];
	}

	private static function init(){
		if(self::$fp === null){
			self::$ip = new self();

			self::$fp = fopen(WP_CONTENT_DIR.'/uploads/17monipdb.dat', 'rb');
			if (self::$fp === false) {
				throw new Exception('Invalid 17monipdb.dat file!');
			}

			self::$offset = unpack('Nlen', fread(self::$fp, 4));
			if (self::$offset['len'] < 4) {
				throw new Exception('Invalid 17monipdb.dat file!');
			}

			self::$index = fread(self::$fp, self::$offset['len'] - 4);
		}
	}

	public function __destruct(){
		if(self::$fp !== null){
			fclose(self::$fp);
		}
	}
}

wp_cache_add_global_groups('wpjam_list_cache');