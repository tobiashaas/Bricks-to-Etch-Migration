<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Schedule_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Schedule_addon
{
    protected $schedule_type = array(
        'wpvivid_hourly'=>'Every hour',
        'wpvivid_2hours'=>'Every 2 hours',
        'wpvivid_4hours'=>'Every 4 hours',
        'wpvivid_6hours'=>'Every 6 hours',
        'wpvivid_8hours'=>'Every 8 hours',
        'wpvivid_12hours'       =>  '12Hours',
        'twicedaily'             =>  '12Hours',
        'wpvivid_daily'         =>   'Daily',
        'daily'                  =>   'Daily',
        'onceday'                =>   'Daily',
        'wpvivid_2days'=>'Every 2 days',
        'wpvivid_3days'=>'Every 3 days',
        'wpvivid_weekly'        =>   'Weekly',
        'weekly'                 =>   'Weekly',
        'wpvivid_fortnightly'  =>   'Fortnightly',
        'fortnightly'           =>   'Fortnightly',
        'wpvivid_monthly'      =>   'Monthly',
        'monthly'               =>    'Monthly',
        'montly'                =>    'Monthly'
    );

    public $main_tab;

    public $schedule_backup;
    public $update_schedule_backup;

    public function __construct()
    {
        $this->disable_original_schedule();
        //add_filter('wpvivid_get_oldest_backup_ids', array($this, 'get_oldest_backup_ids'), 11, 2);

        //add_action('init',array( $this,'plugin_loaded'));
        add_action('wpvivid_set_current_schedule_id', array($this, 'set_current_schedule_id'), 11);
        add_action('wpvivid_reset_schedule', array($this, 'reset_schedule'), 11);
        add_action('wpvivid_update_schedule_last_time_addon', array($this, 'wpvivid_update_schedule_last_time_addon'), 11, 2);

        add_filter('cron_schedules',array( $this,'cron_schedules'),100);
        add_filter('wpvivid_archieve_schedule_add_settings', array($this, 'archieve_schedule_add_settings'), 11);

        add_filter('wpvivid_set_schedule_notice', array($this, 'set_schedule_notice'), 11, 3);
        add_filter('init_wpvivid_schedule',array( $this,'init_schedules'),10);
        add_filter('wpvivid_get_schedule', array($this, 'get_schedule'),11,2);

        add_filter('wpvivid_trim_import_info', array($this, 'trim_import_info'));


        //dashboard
        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-use-schedule';
        $cap['display']='Schedule';
        $cap['menu_slug']=strtolower(sprintf('%s-schedule', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['index']=6;
        $cap['icon']='<span class="dashicons dashicons-calendar-alt wpvivid-dashicons-grey"></span>';
        $cap_list[$cap['slug']]=$cap;

        $cap['slug']='wpvivid-can-schedule-backup-remote';
        $cap['display']='Backup to cloud storage';
        $cap['menu_slug']=strtolower(sprintf('%s-schedule-backup-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['index']=7;
        $cap['icon']='<strong>-----</strong>';
        $cap_list[$cap['slug']]=$cap;
        return $cap_list;
    }

    public function disable_original_schedule()
    {
        if(wp_get_schedule('wpvivid_main_schedule_event'))
        {
            wp_clear_scheduled_hook('wpvivid_main_schedule_event');
            $timestamp = wp_next_scheduled('wpvivid_main_schedule_event');
            wp_unschedule_event($timestamp,'wpvivid_main_schedule_event');
        }
    }

    public function plugin_loaded()
    {
        $schedule_hooks=array();
        $schedule_hooks=apply_filters('init_wpvivid_schedule', $schedule_hooks);
        $this->init_schedule_hooks($schedule_hooks);
    }

    public function set_current_schedule_id($schedule_id){
        WPvivid_Setting::update_option('wpvivid_current_schedule_id', $schedule_id);
    }

    public static function reset_schedule()
    {
        $default=array();
        $schedules = get_option('wpvivid_schedule_addon_setting', $default);
        foreach ($schedules as $schedule)
        {
            if($schedule['status'] === 'Active')
            {
                $timestamp=wp_next_scheduled($schedule['id'], array($schedule['id']));
                if($timestamp===false)
                {
                    if(isset($schedule['week']))
                    {
                        $time['start_time']['week']=$schedule['week'];
                    }
                    else
                    {
                        $time['start_time']['week']='mon';
                    }

                    if(isset($schedule['day']))
                    {
                        $time['start_time']['day']=$schedule['day'];
                    }
                    else
                    {
                        $time['start_time']['day']='01';
                    }


                    if(isset($schedule['current_day']))
                    {
                        $time['start_time']['current_day']=$schedule['current_day'];
                    }
                    else
                        $time['start_time']['current_day']="00:00";

                    $timestamp=WPvivid_Schedule_addon::get_start_time($time);

                    wp_schedule_event($timestamp, $schedule['type'], $schedule['id'],array($schedule['id']));
                }
            }
        }

        return true;
    }

    public function wpvivid_update_schedule_last_time_addon($schedule_id, $last_backup_time){
        $default=array();
        $schedules = get_option('wpvivid_schedule_addon_setting', $default);
        foreach ($schedules as $schedule){
            if($schedule['id'] === $schedule_id){
                $schedules[$schedule_id]['last_backup_time'] = $last_backup_time;
                WPvivid_Setting::update_option('wpvivid_schedule_addon_setting', $schedules);
            }
        }
    }

    public function cron_schedules($schedules)
    {
        if(!isset($schedules["wpvivid_hourly"])){
            $schedules["wpvivid_hourly"] = array(
                'interval' => 3600,
                'display' => __('Every hour'));
        }

        if(!isset($schedules["wpvivid_2hours"])){
            $schedules["wpvivid_2hours"] = array(
                'interval' => 3600*2,
                'display' => __('Every 2 hours'));
        }

        if(!isset($schedules["wpvivid_4hours"])){
            $schedules["wpvivid_4hours"] = array(
                'interval' => 3600*4,
                'display' => __('Every 4 hours'));
        }

        if(!isset($schedules['wpvivid_6hours'])){
            $schedules['wpvivid_6hours'] = array(
                'interval' => 3600*6,
                'display' => __('Every 6 hours'));
        }

        if(!isset($schedules["wpvivid_8hours"])){
            $schedules["wpvivid_8hours"] = array(
                'interval' => 3600*8,
                'display' => __('Every 8 hours'));
        }

        if(!isset($schedules["wpvivid_12hours"])){
            $schedules["wpvivid_12hours"] = array(
                'interval' => 3600*12,
                'display' => __('Every 12 hours'));
        }

        if(!isset($schedules["wpvivid_daily"])){
            $schedules["wpvivid_daily"] = array(
                'interval' => 86400,
                'display' => __('Daily'));
        }

        if(!isset($schedules["wpvivid_2days"])){
            $schedules["wpvivid_2days"] = array(
                'interval' => 86400*2,
                'display' => __('Every 2 days'));
        }

        if(!isset($schedules["wpvivid_3days"])){
            $schedules["wpvivid_3days"] = array(
                'interval' => 86400*3,
                'display' => __('Every 3 days'));
        }

        if(!isset($schedules["wpvivid_weekly"])){
            $schedules["wpvivid_weekly"] = array(
                'interval' => 604800,
                'display' => __('Weekly'));
        }

        if(!isset($schedules["wpvivid_fortnightly"])){
            $schedules["wpvivid_fortnightly"] = array(
                'interval' => 604800*2,
                'display' => __('Fortnightly'));
        }

        if(!isset($schedules["wpvivid_monthly"])){
            $schedules["wpvivid_monthly"] = array(
                'interval' => 2592000,
                'display' => __('Monthly'));
        }

        return $schedules;
    }

    public function archieve_schedule_add_settings($schedule_setting){
        $schedule_setting = get_option('wpvivid_schedule_addon_setting', $schedule_setting);
        return $schedule_setting;
    }
    /***** schedule display filter begin *****/

    /***** schedule display filter end *****/

    /***** schedule filters begin *****/
    public function set_schedule_notice($notice_type, $message, $is_mainwp=false)
    {
        if($is_mainwp){
            $style = 'margin: 0; padding-top: 10px; margin-bottom: 10px;';
            $fun = 'mwp_click_dismiss_notice(this);';
        }
        else{
            $style = 'margin-top: 0; margin-bottom: 10px;';
            $fun = 'click_dismiss_notice(this);';
        }
        $html = '';
        if($notice_type)
        {
            $html .= __('<div class="notice notice-success is-dismissible inline" style="'.$style.'"><p>'.$message.'</p>
                                    <button type="button" class="notice-dismiss" onclick="'.$fun.'">
                                    <span class="screen-reader-text">Dismiss this notice.</span>
                                    </button>
                                    </div>');

        }
        else{
            $html .= __('<div class="notice notice-error"><p>' . $message . '</p></div>');
        }
        return $html;
    }

    public function init_schedules($schedule_hooks)
    {
        $default=array();
        $schedules = get_option('wpvivid_schedule_addon_setting', $default);

        if(!empty($schedules))
        {
            foreach ($schedules as $schedule)
            {
                $schedule_hooks[$schedule['id']]=$schedule['id'];
            }
        }

        return $schedule_hooks;
    }

    public function get_schedule($schedule,$schedule_id)
    {
        $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
        if(array_key_exists($schedule_id,$schedules))
        {
            return $schedules[$schedule_id];
        }
        else
        {
            return $schedule;
        }
    }

    public function trim_import_info($json)
    {
        global $wpvivid_backup_pro;
        if(isset($json['data']['wpvivid_schedule_addon_setting']) && !empty($json['data']['wpvivid_schedule_addon_setting']))
        {
            foreach ($json['data']['wpvivid_schedule_addon_setting'] as $schedule_id=>$schedule_data)
            {
                if($schedule_data['backup']['remote'] === 1 && isset($schedule_data['backup']['remote_options']))
                {
                    foreach ($schedule_data['backup']['remote_options'] as $remote_id=>$remote_data)
                    {
                        if(isset($remote_data['custom_path']))
                        {
                            $json['data']['wpvivid_schedule_addon_setting'][$schedule_id]['backup']['remote_options'][$remote_id]['custom_path'] = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
                        }
                        else if(isset($remote_data['path']))
                        {
                            $json['data']['wpvivid_schedule_addon_setting'][$schedule_id]['backup']['remote_options'][$remote_id]['path'] = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
                        }
                    }
                }
            }
        }
        return $json;
    }
    /***** schedule filters end *****/

    /***** useful function begin *****/
    public function init_schedule_hooks($schedule_hooks)
    {
        /*
        global $wpvivid_plugin;
        foreach ($schedule_hooks as $key=>$schedule_hook)
        {
            add_action($schedule_hook, array($wpvivid_plugin, 'main_schedule'));
        }
        */
    }

    public static function get_time_zone($offset)
    {
        $time_zone_array = array(
            '-12'=>'Etc/GMT+12',
            '-11'=>'Etc/GMT+11',
            '-10'=>'Etc/GMT+10',
            '-9' =>'Etc/GMT+9',
            '-8' =>'Etc/GMT+8',
            '-7' =>'Etc/GMT+7',
            '-6' =>'Etc/GMT+6',
            '-5' =>'Etc/GMT+5',
            '-4' =>'Etc/GMT+4',
            '-3' =>'Etc/GMT+3',
            '-2' =>'Etc/GMT+2',
            '-1' =>'Etc/GMT+1',
            '0'  =>'UTC',
            '1'  =>'Etc/GMT-1',
            '2'  =>'Etc/GMT-2',
            '3'  =>'Etc/GMT-3',
            '4'  =>'Etc/GMT-4',
            '5'  =>'Etc/GMT-5',
            '6'  =>'Etc/GMT-6',
            '7'  =>'Etc/GMT-7',
            '8'  =>'Etc/GMT-8',
            '9'  =>'Etc/GMT-9',
            '10' =>'Etc/GMT-10',
            '11' =>'Etc/GMT-11',
            '12' =>'Etc/GMT-12',
            '13' =>'Etc/GMT-13',
            '14' =>'Etc/GMT-14'
        );

        $time_zone = 'not_found';
        foreach ($time_zone_array as $key => $value)
        {
            if($key == $offset)
            {
                $time_zone = $value;
                break;
            }
        }
        return $time_zone;
    }

    public static function get_start_time($time,$local_time=true)
    {
        if(!is_array( $time ) )
        {
            return false;
        }

        if(!isset($time['type']))
        {
            return false;
        }

        $week=$time['start_time']['week'];
        $day=$time['start_time']['day'];
        $current_day=$time['start_time']['current_day'];

        $default_time_zone = date_default_timezone_get();
        $offset=get_option('gmt_offset');
        $time_zone = self::get_time_zone($offset);

        if($time_zone !== 'not_found')
        {
            date_default_timezone_set($time_zone);
            if((strtotime('now'))>strtotime($current_day)){
                $daily_start_time = $current_day.' +1 day';
            }
            else{
                $daily_start_time = $current_day;
            }

            $weekly_tmp = $week.' '.$current_day;
            if((strtotime('now'))>strtotime($weekly_tmp)) {
                $weekly_start_time = $week.' '.$weekly_tmp.' next week';
            }
            else{
                $weekly_start_time = $weekly_tmp;
            }

            $date_now = date("Y-m-",time());
            $monthly_tmp = $date_now.$day.' '.$current_day;
            if((strtotime('now'))>strtotime($monthly_tmp)){
                $date_now = date("Y-m-",strtotime('first day of next month'));
                $monthly_start_time = $date_now.$day.' '.$current_day;
            }
            else{
                $monthly_start_time = $monthly_tmp;
            }
        }
        else
        {
            $offset=$offset * 60 * 60;

            if((strtotime('now')+$offset)>strtotime($current_day)){
                $daily_start_time = $current_day.' +1 day';
            }
            else{
                $daily_start_time = $current_day;
            }

            $weekly_tmp = $week.' '.$current_day;
            if((strtotime('now')+$offset)>strtotime($weekly_tmp)) {
                $weekly_start_time = $week.' '.$weekly_tmp.' next week';
            }
            else{
                $weekly_start_time = $weekly_tmp;
            }

            $date_now = date("Y-m-",time());
            $monthly_tmp = $date_now.$day.' '.$current_day;
            if((strtotime('now')+$offset)>strtotime($monthly_tmp)){
                $date_now = date("Y-m-",strtotime('first day of next month'));
                $monthly_start_time = $date_now.$day.' '.$current_day;
            }
            else{
                $monthly_start_time = $monthly_tmp;
            }
        }

        $schedule_type_ex = array(
            'wpvivid_hourly'=>'Every hour',
            'wpvivid_2hours'=>'Every 2 hours',
            'wpvivid_4hours'=>'Every 4 hours',
            'wpvivid_6hours'=>'Every 6 hours',
            'wpvivid_8hours'=>'Every 8 hours',
            'wpvivid_12hours'=>'12Hours',
            'twicedaily'=>'12Hours',
            'wpvivid_daily'=>'Daily',
            'wpvivid_2days'=>'Every 2 days',
            'wpvivid_3days'=>'Every 3 days',
            'daily'=>'Daily',
            'onceday'=>'Daily',
            'wpvivid_weekly'=>'Weekly',
            'weekly'=>'Weekly',
            'wpvivid_fortnightly'=>'Fortnightly',
            'fortnightly'=>'Fortnightly',
            'wpvivid_monthly'=>'Monthly',
            'monthly'=>'Monthly',
            'montly'=>'Monthly'
        );

        $display_array = array(
            'Every hour'=>$daily_start_time,
            'Every 2 hours'=>$daily_start_time,
            'Every 4 hours'=>$daily_start_time,
            'Every 6 hours'=>$daily_start_time,
            'Every 8 hours'=>$daily_start_time,
            'Every 12 hours'=>$daily_start_time,
            'wpvivid_12hours'=>'12Hours',
            "12Hours"=>$daily_start_time,
            "Daily"=>$daily_start_time,
            "Every 2 days"=>$daily_start_time,
            'Every 3 days'=>$daily_start_time,
            "Weekly"=>$weekly_start_time,
            "Fortnightly"=>$weekly_start_time,
            "Monthly"=>$monthly_start_time
        );
        foreach ($schedule_type_ex as $key => $value)
        {
            if($key == $time['type'])
            {
                foreach ($display_array as $display_key => $display_value)
                {
                    if($value == $display_key)
                    {
                        if($local_time)
                        {
                            if($time_zone !== 'not_found')
                            {
                                $start_time = strtotime($display_value);
                                date_default_timezone_set($default_time_zone);
                                return $start_time;
                            }
                            else
                            {
                                $offset=get_option('gmt_offset');
                                $offset=$offset * 60 * 60;
                                return strtotime($display_value)-$offset;
                            }
                        }
                        else
                        {
                            if($time_zone !== 'not_found')
                            {
                                $start_time = strtotime($display_value);
                                date_default_timezone_set($default_time_zone);
                                return $start_time;
                            }
                            else
                            {
                                return strtotime($display_value);
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
    /***** useful function end *****/

    /***** schedule ajax begin *****/
    /***** schedule ajax end *****/
}