<?php
/*
Name: 文章类型转换器
URI: https://blog.wpjam.com/m/wpjam-post-type-switcher/
Description: 可以将文章在多种文章类型中进行转换。
Version: 1.0
*/
if(is_admin()){
	class WPJAM_Post_Type_Switcher{
		public static function get_options(){
			$options	= [];

			foreach(get_post_types(['show_ui'=>true], 'objects') as $post_type => $pt_obj){
				if(!in_array($post_type, ['attachment', 'wp_block']) && current_user_can($pt_obj->cap->publish_posts)){
					$options[$post_type]	= $pt_obj->labels->singular_name;
				}
			}

			return $options;
		}

		public static function callback($post_id, $data){
			return self::set_post_type($post_id, $data['post_type']);
		}

		public static function set_post_type($post_id, $post_type){
			if($post_type && get_post_type($post_id) != $post_type){
				if(!post_type_exists($post_type) || !current_user_can(get_post_type_object($post_type)->cap->publish_posts)){
					return new WP_Error('invalid_post_type', '无效的文章类型');
				}

				set_post_type($post_id, $post_type);

				return admin_url('post.php?post='.$post_id.'&action=edit');
			}

			return true;
		}

		public static function on_after_insert_post($post_id, $post){
			if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)){
				return;
			}

			$new_post_type	= wpjam_get_parameter('pts_post_type', ['method'=>'REQUEST', 'sanitize_callback'=>'sanitize_key']);

			self::set_post_type($post_id, $new_post_type);
		}

		public static function on_post_submitbox_misc_actions(){
			$current	= get_post_type();
			$pt_obj		= get_post_type_object($current);

			if(empty($pt_obj) || is_wp_error($pt_obj)){
				return; 
			}

			?>

			<div class="misc-pub-section post-type-switcher">
				<label for="pts_post_type">文章类型：</label>
				<strong id="post_type_display"><?php echo esc_html($pt_obj->labels->singular_name); ?></strong>

				<?php if(current_user_can($pt_obj->cap->publish_posts)){ ?>

				<a href="javascript:;" id="edit_post_type_switcher" class="hide-if-no-js"><?php _e( 'Edit' ); ?></a>

				<div id="post_type_select">
					<?php echo wpjam_get_field_html(['key'=>'pts_post_type',	'value'=>$current, 'options'=>self::get_options()]); ?>

					<a href="javascript:;" id="save_post_type_switcher" class="hide-if-no-js button"><?php _e( 'OK' ); ?></a>
					<a href="javascript:;" id="cancel_post_type_switcher" class="hide-if-no-js button-cancel"><?php _e( 'Cancel' ); ?></a>
				</div>

				<?php } ?>

			</div>

			<?php
		}

		public static function on_admin_head(){
			?>
			<script type="text/javascript">
			jQuery(function($){
				// $( '.misc-pub-section.curtime.misc-pub-section-last' ).removeClass( 'misc-pub-section-last' );
				$('#edit_post_type_switcher').on('click', function(e) {
					$(this).hide();
					$('#post_type_select').slideDown();
				});

				$('#save_post_type_switcher').on('click',  function(e) {
					$('#post_type_select').slideUp();
					$('#edit_post_type_switcher').show();
					$('#post_type_display').text($('#pts_post_type :selected').text());
				});

				$('#cancel_post_type_switcher').on('click',  function(e) {
					$('#post_type_select').slideUp();
					$('#edit_post_type_switcher').show();
				});
			});
			</script>
			<style type="text/css">
			#post_type_select{ margin-top: 3px; display: none; }
			#post-body .post-type-switcher::before{ content: '\f109'; font: 400 20px/1 dashicons; speak: none;  display: inline-block; padding: 0 2px 0 0; top: 0; left: -1px; position: relative; vertical-align: top; text-decoration: none !important; color: #888; }
			</style>
			<?php
		}


		public static function on_builtin_page_load($screen_base, $current_screen){
			if($screen_base == 'edit'){
				wpjam_register_list_table_action('set_post_type', [
					'title'			=> '修改文章类型',
					'page_title'	=> '修改文章类型',
					'response'		=> 'redirect',
					'callback'		=> [self::class, 'callback'],
					'fields'		=> ['post_type'=>['title'=>'文章类型',	'options'=>self::get_options()]]
				]);
			}elseif($screen_base == 'post' && $current_screen->post_type != 'attachment' && !$current_screen->is_block_editor){
				add_action('wp_after_insert_post',			[self::class, 'on_after_insert_post'], 999, 2);
				add_action('post_submitbox_misc_actions',	[self::class, 'on_post_submitbox_misc_actions']);
				add_action('admin_head',					[self::class, 'on_admin_head']);
			}
		}
	}

	add_action('wpjam_builtin_page_load', ['WPJAM_Post_Type_Switcher', 'on_builtin_page_load'], 10, 2);
}