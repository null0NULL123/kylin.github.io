<?php
class WPJAM_Crons_Admin{
	public static function get_primary_key(){
		return 'cron_id';
	}

	public static function get($id){
		list($timestamp, $hook, $key)	= explode('--', $id);

		$wp_crons = _get_cron_array() ?: [];

		if(isset($wp_crons[$timestamp][$hook][$key])){
			$data	= $wp_crons[$timestamp][$hook][$key];

			$data['hook']		= $hook;
			$data['timestamp']	= $timestamp;
			$data['time']		= get_date_from_gmt(date('Y-m-d H:i:s', $timestamp));
			$data['cron_id']	= $id;
			$data['interval']	= $data['interval'] ?? 0;

			return $data;
		}else{
			return new WP_Error('cron_not_exist', '该定时作业不存在');
		}
	}

	public static function insert($data){
		if(!has_filter($data['hook'])){
			return new WP_Error('invalid_hook', '非法 hook');
		}

		$timestamp	= strtotime(get_gmt_from_date($data['time']));

		if($data['interval']){
			wp_schedule_event($timestamp, $data['interval'], $data['hook'], $data['_args']);
		}else{
			wp_schedule_single_event($timestamp, $data['hook'], $data['_args']);
		}

		return true;
	}

	public static function do($id){
		$data = self::get($id);

		if(is_wp_error($data)){
			return $data;
		}

		$result	= do_action_ref_array($data['hook'], $data['args']);

		if(is_wp_error($result)){
			return $result;
		}else{
			return true;
		}
	}

	public static function delete($id){
		$data = self::get($id);

		if(is_wp_error($data)){
			return $data;
		}

		return wp_unschedule_event($data['timestamp'], $data['hook'], $data['args']);
	}

	public static function query_items($limit, $offset){
		$items		= [];
		$wp_crons	= _get_cron_array() ?: [];

		foreach($wp_crons as $timestamp => $wp_cron){
			foreach ($wp_cron as $hook => $dings) {
				foreach($dings as $key=>$data) {
					if(!has_filter($hook)){
						wp_unschedule_event($timestamp, $hook, $data['args']);	// 系统不存在的定时作业，自动清理
						continue;
					}

					$schedule	= $schedules[$data['schedule']] ?? $data['interval']??'';
					// $args	= $data['args'] ? '('.implode(',', $data['args']).')' : '';

					$items[] = [
						'cron_id'	=> $timestamp.'--'.$hook.'--'.$key,
						'time'		=> get_date_from_gmt( date('Y-m-d H:i:s', $timestamp) ),
						// 'hook'		=> $hook.$args,
						'hook'		=> $hook,
						'interval'	=> $data['interval'] ?? 0
					];
				}
			}
		}

		return ['items'=>$items, 'total'=>count($items)];
	}

	public static function get_actions(){
		return [
			'add'		=> ['title'=>'新建',		'response'=>'list'],
			'do'		=> ['title'=>'立即执行',	'direct'=>true,	'confirm'=>true,	'bulk'=>2,		'response'=>'list'],
			'delete'	=> ['title'=>'删除',		'direct'=>true,	'confirm'=>true,	'bulk'=>true,	'response'=>'list']
		];
	}

	public static function get_fields($action_key='', $id=0){
		$schedule_options	= [0=>'只执行一次']+wp_list_pluck(wp_get_schedules(), 'display', 'interval');

		return [
			'hook'		=> ['title'=>'Hook',	'type'=>'text',		'show_admin_column'=>true],
			// '_args'		=> ['title'=>'参数',		'type'=>'mu-text',	'show_admin_column'=>true],
			'time'		=> ['title'=>'运行时间',	'type'=>'text',		'show_admin_column'=>true,	'value'=>current_time('mysql')],
			'interval'	=> ['title'=>'频率',		'type'=>'select',	'show_admin_column'=>true,	'options'=>$schedule_options],
		];
	}
}