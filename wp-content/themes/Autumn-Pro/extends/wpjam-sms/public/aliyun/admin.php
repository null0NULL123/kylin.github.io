<?php
if(empty($GLOBALS['current_tab'])){
	wpjam_register_plugin_page_tab('aliyun', ['title'=>'阿里云短信通知',	'function'=>'option',	'option_name'=>'wpjam_aliyun_sms',	'tab_file'=>__DIR__.'/admin.php']);
}elseif($GLOBALS['current_tab'] == 'aliyun'){
	include __DIR__ . '/aliyun.php';

	add_filter('wpjam_aliyun_sms_setting', function(){
		$fields	= [
			'access_key_id'		=> ['title'=>'Access Key ID',		'type'=>'text'],
			'access_key_secret'	=> ['title'=>'Access Key Secret',	'type'=>'text'],
			'sign_name'			=> ['title'=>'短信签名',				'type'=>'text',	'class'=>'all-options',	'description'=>'请先在阿里云短信服务后台创建，然后填回到这里。'],
		];

		$access_key_id		= wpjam_aliyun_sms_get_setting('access_key_id');
		$access_key_secret	= wpjam_aliyun_sms_get_setting('access_key_secret');

		if($access_key_id && $access_key_secret){
			$template_fields	= [];
			$template_create	= false;
			$templates			= wpjam_get_aliyun_sms_templates();
			foreach ($templates as $key => $args){
				$template_key	= $key == 'code' ? 'template' : $key.'_template';

				$template_fields[$template_key]	= ['title'=>'',	'type'=>'text',	'class'=>'all-options',	'description'=>$args['TemplateName'].'模板'];

				if(!wpjam_aliyun_sms_get_setting($template_key)){
					$template_create	= true;
				}
			}

			if($template_fields){
				if($template_create){
					$create_button	= wpjam_get_ajax_button([
						'action'      => 'create_templates',
						'class'       => 'button',
						'button_text' => '创建',
						'direct'      => true,
						'confirm'     => true,
					]);

					$templates_view	= '<p>一键创建所有短信模板，然后到阿里云短信服务后台，待审核通过后，复制模版CODE填回：'.$create_button.'<br />请勿重复点击，否则会创建多条重复的模板。</p>';

					$template_fields['view']	= ['title'=>'',	'type'=>'view',	'value'=>$templates_view];	
				}
				
				$fields['template_fieldset']	= ['title'=>'短信模板',	'type'=>'fieldset',	'fields'=>$template_fields];
			}
		}

		$ajax		= false;
		$summary	= wpautop('1、点击这里注册<a href="https://wpjam.com/go/aliyun-sms/">阿里云短信服务</a>。
		2、点击这里获取 <a href="https://usercenter.console.aliyun.com/#/manage/ak">Access Key ID 和 Access Key Secret</a>。
		3、按照要求在阿里云短信服务后台创建短信签名，然后填回到下面对应的选项中。');

		return compact('fields', 'ajax', 'summary');
	});

	add_filter('pre_update_option_wpjam_aliyun_sms', function($value){
		$access_key_id		= $value['access_key_id'];
		$access_key_secret	= $value['access_key_secret'];

		if($access_key_id && $access_key_secret){
			$aliyun_sms = new WPJAM_AliyunSMS($access_key_id, $access_key_secret);
			$sign_name	= $value['sign_name'];

			if($sign_name){
				$result	= $aliyun_sms->query_sign(['SignName'=>$sign_name]);
				if(is_wp_error($result)){
					wp_die($result);
				}
			}
		}

		return $value;
	});

	add_action('wpjam_page_action', function($action){
		if($action == 'create_templates'){
			$data	= [];

			$access_key_id		= wpjam_aliyun_sms_get_setting('access_key_id');
			$access_key_secret	= wpjam_aliyun_sms_get_setting('access_key_secret');

			if(!$access_key_id || !$access_key_secret){
				wpjam_send_json(['errcode'=>'invalid_access_key', 'errmsg'=>'非法 Access Key ID 或者 Secret']);
			}

			$aliyun_sms	= new WPJAM_AliyunSMS($access_key_id, $access_key_secret);

			$templates	= WPJAM_AliyunSMS::get_templates();
			$data		= [];
			foreach ($templates as $key => $args){
				$template_key	= $key == 'code' ? 'template' : $key.'_template';

				if(wpjam_aliyun_sms_get_setting($template_key)){
					continue;
				}

				$result	= $aliyun_sms->add_template($args);

				if(is_wp_error($result)){
					wpjam_send_json($result);
				}

				$data[$key]	= $template_code = $result['TemplateCode'];

				wpjam_aliyun_sms_update_setting($template_key, $template_code);
			}

			wpjam_send_json(compact('data'));
		}
	});

	add_action('admin_head', function(){
		?>
		<script type="text/javascript">
		jQuery(function($){
			$('body').on('page_action_success', function(e, response){
				var action	= response.page_action;

				if(action == 'create_templates'){
					$.each(response.data, function(key, value){
						if(key == 'code'){
							$('#template').val(value);
						}else{
							$('#'+key+'_template').val(value);
						}
					});
				}
			});
		});
		</script>
		<?php
	});

	WPJAM_AliyunSMS::register_template('code', [
		'TemplateType'		=> 0,
		'TemplateName'		=> '验证码',
		'TemplateContent'	=> '您的验证码为： ${code}，该验证码5分钟内有效，请勿泄露于他人。',
		'Remark'			=> '用户登录验证'
	]);

	do_action('wpjam_init_aliyun_sms_templates');
}