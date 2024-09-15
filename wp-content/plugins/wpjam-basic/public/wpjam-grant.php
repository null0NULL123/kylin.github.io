<?php
class WPJAM_Grants_Admin{
	public static function render_item($appid, $secret=''){
		$secret	= $secret ? '<p class="secret" id="secret_'.$appid.'" style="display:block;">'.$secret.'</p>' : '<p class="secret" id="secret_'.$appid.'"></p>';

		$caches	= $GLOBALS['wpjam_grant']->cache_get($appid);
		$times	= $caches['token.grant'] ?? 0;

		return '
		<table class="form-table widefat striped" id="table_'.$appid.'">
			<tbody>
				<tr>
					<th>AppID</th>
					<td class="appid">'.$appid.'</td>
					<td>'.wpjam_get_page_button('delete_grant', ['data'=>compact('appid')]).'</td>
				</tr>
				<tr>
					<th>Secret</th>
					<td>出于安全考虑，Secret不再被明文保存，忘记密钥请点击重置：'.$secret.'</td>
					<td>'.wpjam_get_page_button('reset_secret', ['data'=>compact('appid')]).'</td>
				</tr>
				<tr>
					<th>用量</th>
					<td>鉴权接口已被调用了 <strong>'.$times.'</strong> 次，更多接口调用统计请点击'.wpjam_get_page_button('get_stats', ['data'=>compact('appid')]).'</td>
					<td>'.wpjam_get_page_button('clear_quota', ['data'=>compact('appid')]).'</td>
				</tr>
			</tbody>
		</table>
		';
	}

	public static function render_create_item($count=0){
		return '
		<table class="form-table widefat striped" id="create_grant" style="'. ($count >=3 ? 'display: none;' : '').'">
			<tbody>
				<tr>
					<th>创建</th>
					<td>点击右侧创建按钮可创建 AppID/Secret，最多可创建三个：</td>
					<td>'.wpjam_get_page_button('create_grant').'</td>
				</tr>
			</tbody>
		</table>
		';
	}

	public static function get_fields($name){
		$appid	= wpjam_get_data_parameter('appid');
		$caches	= $GLOBALS['wpjam_grant']->cache_get($appid) ?: [];
		$fields	= [];

		if($appid){
			$fields['appid']	= ['title'=>'APPID',	'type'=>'view', 'value'=>$appid];

			$caches['token.grant']	= $caches['token.grant'] ?? 0;
		}

		if($caches){
			foreach($caches as $json => $times){
				$fields[$json]	= ['title'=>$json,	'type'=>'view', 'value'=>$times];
			}
		}else{
			$fields['no']	= ['type'=>'view', 'value'=>'暂无数据'];
		}

		return $fields;
	}

	public static function ajax_clear_quota(){
		$appid	= wpjam_get_data_parameter('appid');
		$GLOBALS['wpjam_grant']->cache_delete($appid);

		wpjam_send_json(['errmsg'=>'接口已清零']);
	}

	public static function ajax_reset_secret(){
		$appid	= wpjam_get_data_parameter('appid');
		$secret	= $GLOBALS['wpjam_grant']->reset_secret($appid);

		if(is_wp_error($secret)){
			wpjam_send_json($secret);
		}else{
			wpjam_send_json(compact('appid', 'secret'));
		}
	}

	public static function ajax_create_grant(){
		$appid	= $GLOBALS['wpjam_grant']->add();

		if(is_wp_error($appid)){
			wpjam_send_json($appid);
		}

		$secret	= $GLOBALS['wpjam_grant']->reset_secret($appid);
		
		$table 	= self::render_item($appid, $secret);
		$rest	= 3 - count($GLOBALS['wpjam_grant']->get_items());

		wpjam_send_json(compact('table', 'rest'));
	}

	public static function ajax_delete_grant(){
		$appid	= wpjam_get_data_parameter('appid');
		$result	= $GLOBALS['wpjam_grant']->delete($appid);

		if(is_wp_error($result)){
			wpjam_send_json($result);
		}else{
			wpjam_send_json(compact('appid'));
		}
	}

	public static function load_plugin_page(){
		wpjam_register_page_action('reset_secret', [
			'button_text'	=> '重置',
			'class'			=> 'button',
			'direct'		=> true,
			'confirm'		=> true,
			'callback'		=> [self::class, 'ajax_reset_secret']
		]);

		wpjam_register_page_action('delete_grant', [
			'button_text'	=> '删除',
			'class'			=> 'button',
			'direct'		=> true,
			'confirm'		=> true,
			'callback'		=> [self::class, 'ajax_delete_grant']
		]);

		wpjam_register_page_action('create_grant', [
			'button_text'	=> '创建',
			'class'			=> 'button',
			'direct'		=> true,
			'confirm'		=> true,
			'callback'		=> [self::class, 'ajax_create_grant']
		]);

		wpjam_register_page_action('get_stats', [
			'button_text'	=> '用量',
			'submit_text'	=> '',
			'class'			=> '',
			'width'			=> 500,
			'fields'		=> [self::class, 'get_fields']
		]);

		wpjam_register_page_action('clear_quota', [
			'button_text'	=> '清零',
			'class'			=> 'button button-primary',
			'direct'		=> true,
			'confirm'		=> true,
			'callback'		=> [self::class, 'ajax_clear_quota']
		]);

		$doc	= '
		<p>access_token 是开放接口的全局<strong>接口调用凭据</strong>，第三方调用各接口时都需使用 access_token，开发者需要进行妥善保存。</p>
		<p>access_token 的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的 access_token 失效。</p>

		<h4>请求地址</h4>

		<p><code>'.home_url('/api/').'token/grant.json?appid=APPID&secret=APPSECRET</code></p>

		<h4>参数说明<h4>

		'.do_shortcode('[table th=1 class="form-table striped"]
		参数	
		是否必须
		说明

		appid
		是
		第三方用户凭证

		secret
		是
		第三方用户证密钥。
		[/table]').'
		
		<h4>返回说明</h4>

		<p><code>
			{"errcode":0,"access_token":"ACCESS_TOKEN","expires_in":7200}
		</code></p>';

		wpjam_register_page_action('access_token', [
			'button_text'	=> '接口文档',
			'submit_text'	=> '',
			'page_title'	=> '获取access_token',
			'class'			=> 'page-title-action button',
			'fields'		=> ['access_token'=>['type'=>'view', 'value'=>$doc]], 
		]);
	}

	public static function plugin_page(){
		echo '<div class="card">';

		echo '<h3>开发者 ID<span class="page-actions">'.wpjam_get_page_button('access_token').'</span></h3>';

		if($items = $GLOBALS['wpjam_grant']->get_items()){
			foreach($items as $item){
				echo self::render_item($item['appid']);
			} 
		}

		echo self::render_create_item(count($items));
		
		echo '</div>';
	}
}

add_action('admin_head', function(){ ?>
	<style type="text/css">
	div.card {max-width:640px; width:640px;}
	
	div.card .form-table{margin: 20px 0; border: none;}
	div.card .form-table th{width: 60px; padding-left: 10px;}

	table.form-table code{display: block; padding: 5px 10px; font-size: smaller; }

	td.appid{font-weight: bold;}
	p.secret{display: none; background: #ffc; padding:4px 8px; font-weight: bold;}
	h3 span.page-actions{display: flex; align-content: center; justify-content: space-between; float:right;}
	</style>

	<script type="text/javascript">
	jQuery(function($){
		$('body').on('page_action_success', function(e, response){
			if(response.page_action == 'reset_secret'){
				$('p#secret_'+response.appid).show().html(response.secret);
			}else if(response.page_action == 'create_grant'){
				$('table#create_grant').before(response.table);
				if(response.rest == 0){
					$('table#create_grant').hide();
				}
			}else if(response.page_action == 'delete_grant'){
				$('table#table_'+response.appid).remove();
				
				$('table#create_grant').show();
			}
		});
	});
	</script>
<?php });