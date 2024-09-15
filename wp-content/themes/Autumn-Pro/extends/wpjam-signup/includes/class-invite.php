<?php
add_action('wpjam_user_signuped',	['WPJAM_Invite', 'on_user_signuped'], 10, 2);

class WPJAM_Invite{
	public static function on_user_signuped($user, $args){
		$invite_key	= $args['invite'] ?? '';

		if($invite_key){
			$instance	= self::get_instance();
			$invite		= $instance->use($invite_key);

			if(!is_wp_error($user)){
				do_action('wpjam_user_invite', $invite, $user);	
			}
		}
	}

	public static function ajax_response(){
		$role	= wpjam_get_data_parameter('role', ['sanitize_callback'=>'sanitize_key']);
		$key 	= self::get_instance()->add($role);

		if(is_wp_error($key)){
			return $key;
		}

		return '<div>
			<h2>邀请链接</h2>
			<p><pre><code>'.home_url('wp-login.php?invite_key='.$key).'</code></pre></p>
			<p>链接只能使用一次，请复制链接发给相关用户，支持微信端及电脑端邀请，6小时内有效。</p>
		</div>';
	}

	private static $instance = null;

	public static function get_instance(){
		if(is_null(self::$instance)){
			self::$instance = new self();
		}
		
		return self::$instance;
	}

	private $invites;

	private function __construct(){
		if(is_multisite()){
			$this->invites	= get_site_option('wpjam_user_invites') ?: [];
		}else{
			$this->invites	= get_option('wpjam_user_invites') ?: [];
		}
	}

	private function save(){
		if(is_multisite()){
			return update_site_option('wpjam_user_invites', $this->invites);
		}else{
			return update_option('wpjam_user_invites', $this->invites);
		}
	}

	public function add($role, $args=[]){
		if($this->invites){
			$this->invites	= array_filter($this->invites, function($invite){ return $invite['time'] > time(); });
			$this->invites	= $this->invites ?: [];

			if(is_multisite() && $this->invites){
				$blog_invites	 = array_filter($this->invites, function($invite){ return $invite['blog_id'] == get_current_blog_id(); });

				if(count($blog_invites) > 10){
					return new WP_Error('too_many_invites', '您已经生成了10个邀请链接了，用完再说哈！:-)');
				}
			}
		}

		$invite		= [
			'time'		=> time()+8*HOUR_IN_SECONDS,
			'blog_id'	=> get_current_blog_id(),
			'role'		=> $role,
			'args'		=> $args,
		];

		$invite_key	= wp_generate_password(32, false, false);

		$this->invites[$invite_key]	= $invite;

		$this->save();
		
		return $invite_key;
	}

	public function exists($invite_key){
		if($invite_key && !empty($this->invites) && !empty($this->invites[$invite_key]) && $this->invites[$invite_key]['time'] > time()){
			return $this->invites[$invite_key];
		}else{
			return false;
		}
	}

	public function use($invite_key){
		if(empty($this->invites) || empty($this->invites[$invite_key])){
			return null;
		}

		$invite	= $this->invites[$invite_key];

		unset($this->invites[$invite_key]);

		$this->save();

		return $invite;
	}
}