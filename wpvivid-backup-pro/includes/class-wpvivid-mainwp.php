<?php

class WPvivid_MainWP_Function
{
    public function __construct()
    {
        //mainwp filters
        add_filter('wpvivid_get_wpvivid_pro_url', array($this, 'wpvivid_get_wpvivid_pro_url'), 11, 2);
        add_filter('wpvivid_check_is_pro_mainwp', array($this, 'wpvivid_check_is_pro_mainwp'), 11);
        add_filter('wpvivid_get_wpvivid_info_addon_mainwp', array($this, 'wpvivid_get_wpvivid_info_addon_mainwp'), 11);
        add_filter('wpvivid_upgrade_plugin_addon_mainwp_v2', array($this, 'wpvivid_upgrade_plugin_addon_mainwp_v2'), 11);
        add_filter('wpvivid_init_upgrade_plugin_addon_mainwp_v2', array($this, 'init_upgrade_plugin_addon_mainwp_v2'), 11);
        add_filter('wpvivid_get_upgrade_progress_addon_mainwp_v2', array($this, 'wpvivid_get_upgrade_progress_addon_mainwp_v2'), 11);
        add_filter('wpvivid_login_account_addon_mainwp', array($this, 'wpvivid_login_account_addon_mainwp'));
        add_filter('wpvivid_get_wpvivid_info_addon_mainwp_ex', array($this, 'wpvivid_get_wpvivid_info_addon_mainwp_ex'), 11);

        add_filter('wpvivid_get_mainwp_sync_data', array($this, 'get_mainwp_sync_data'), 9);
        add_filter( 'mainwp_site_sync_others_data', array( $this, 'sync_others_data' ), 99, 2 );
    }

    public function get_mainwp_need_install_plugins()
    {
        global $wpvivid_backup_pro;
        $dashboard_info=get_option('wpvivid_dashboard_info',array());
        if(empty($dashboard_info))
        {
            return array();
        }

        $plugin_install_cache = array();
        foreach ($dashboard_info['plugins'] as $slug=>$plugin)
        {
            if($wpvivid_backup_pro->addons_loader->is_plugin_install_available($plugin))
            {
                //check is installed
                $status=$wpvivid_backup_pro->addons_loader->get_plugin_status($plugin);
                if($status['status']=='Installed'&&$status['action']=='Up to date')
                {

                }
                else
                {
                    $plugin_install_cache=array_merge($wpvivid_backup_pro->addons_loader->get_requires_plugins($plugin), $plugin_install_cache);
                    $plugin_install_cache[]=$plugin;
                }
            }
        }
        return $plugin_install_cache;
    }

    public static function wpvivid_get_upgrade_tasks(){
        $default = array();
        $options = get_option('wpvivid_upgrade_plugin_task_mainwp', $default);
        return $options;
    }

    public static function wpvivid_update_upgrade_task($site_id, $options){
        $upgrade_tasks = self::wpvivid_get_upgrade_tasks();
        $upgrade_tasks[$site_id]=$options;
        update_option('wpvivid_upgrade_plugin_task_mainwp', $upgrade_tasks, 'no');
    }

    public function wpvivid_get_incremental_output_msg(){
        $schedules=get_option('wpvivid_incremental_schedules');
        if(empty($schedules))
        {
            $files_next_start='';
            $db_next_start='';
            $files_schedule='';
            $db_schedule='';
            $all_schedule='';
            $next_start_of_all_files='';
        }
        else
        {
            $schedule=array_shift($schedules);

            $files_schedule=$schedule['incremental_files_recurrence'];
            $db_schedule=$schedule['incremental_db_recurrence'];
            $all_schedule=$schedule['incremental_recurrence'];

            $offset = get_option('gmt_offset');
            $files_schedule_id=$schedule['files_schedule_id'];
            $db_schedule_id=$schedule['db_schedule_id'];
            $timestamp = wp_next_scheduled($files_schedule_id, array($schedule['id']));
            $files_next_start = $timestamp;

            $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());

            if(empty($incremental_backup_data))
            {
                if($files_next_start==false)
                {
                    $next_start_of_all_files='now';
                }
                else
                {
                    $next_start_of_all_files=$files_next_start;
                }

            }
            else if(isset($incremental_backup_data[$schedule['id']])&&isset($incremental_backup_data[$schedule['id']]['files']))
            {
                if($incremental_backup_data[$schedule['id']]['files']['first_backup'])
                {
                    $next_start_of_all_files=$files_next_start;
                }
                else
                {
                    $next_start_of_all_files=$incremental_backup_data[$schedule['id']]['files']['next_start'];
                }
            }
            else
            {
                $next_start_of_all_files=$files_next_start;
            }

            if($next_start_of_all_files !== false)
            {
                if($next_start_of_all_files=='now')
                {
                    $next_start_of_all_files=time();
                }
                $next_start_of_all_files = $next_start_of_all_files + $offset * 60 * 60;
                if ($next_start_of_all_files > 0) {
                    $next_start_of_all_files = date("H:i:s - F-d-Y ", $next_start_of_all_files);
                } else {
                    $next_start_of_all_files = 'N/A';
                }
            }
            else{
                $next_start_of_all_files = 'N/A';
            }

            if($files_next_start !== false) {
                $localtime = $files_next_start + $offset * 60 * 60;
                if ($localtime > 0) {
                    $files_next_start = date("H:i:s - F-d-Y ", $localtime);
                } else {
                    $files_next_start = 'N/A';
                }
            }
            else{
                $files_next_start = 'N/A';
            }
            $timestamp = wp_next_scheduled($db_schedule_id, array($schedule['id']));
            $db_next_start = $timestamp;
            if($db_next_start !== false) {
                $localtime = $db_next_start + $offset * 60 * 60;
                if ($localtime > 0) {
                    $db_next_start = date("H:i:s - F-d-Y ", $localtime);
                } else {
                    $db_next_start = 'N/A';
                }
            }
            else{
                $db_next_start = 'N/A';
            }

            $recurrence = wp_get_schedules();

            if (isset($recurrence[$files_schedule]))
            {
                $files_schedule = $recurrence[$files_schedule]['display'];
            }
            if (isset($recurrence[$db_schedule]))
            {
                $db_schedule = $recurrence[$db_schedule]['display'];
            }
            if (isset($recurrence[$all_schedule]))
            {
                $all_schedule = $recurrence[$all_schedule]['display'];
            }
        }

        $message=get_option('wpvivid_incremental_last_msg');
        if(empty($message))
        {
            $last_message='N/A.';
        }
        else {
            $offset=get_option('gmt_offset');
            $time = $message['status']['start_time'] + ($offset * 60 * 60);
            if(isset($message['incremental_backup_files']))
            {
                $backup_files='Backup '.$message['incremental_backup_files'].' ';
            }
            else
            {
                $backup_files='';
            }
            $time=', (Local Time) '.date("l, F-d-Y H:i", $time);

            if(isset($message['no_files']))
            {
                $nofile=' No file need to be backup. ';
            }
            else
            {
                $nofile='';
            }

            if($message['status']['str'] == 'completed')
            {
                $last_message=$backup_files.'Succeeded'.$nofile.$time;
            }
            else if($message['status']['str'] == 'error')
            {
                $last_message=$backup_files.'Failed'.$nofile.$time;
            }
            else if($message['status']['str'] == 'cancel')
            {
                $last_message=$backup_files.'Failed'.$nofile.$time;
            }
            else{
                $last_message=__('N/A.', 'wpvivid');
            }
        }

        $ret['files_next_start']=$files_next_start;
        $ret['db_next_start']=$db_next_start;
        $ret['files_schedule']=$files_schedule;
        $ret['db_schedule']=$db_schedule;
        $ret['all_schedule']=$all_schedule;
        $ret['next_start_of_all_files']=$next_start_of_all_files;
        $ret['last_message']=$last_message;
        return $ret;
    }

    public function wpvivid_get_wpvivid_pro_url($url, $type)
    {
        if($type === 'wasabi' || $type === 'pCloud') {
            $url = WPVIVID_BACKUP_PRO_PLUGIN_URL;
        }
        return $url;
    }

    public function wpvivid_check_is_pro_mainwp()
    {
        $ret['check_pro'] = false;
        $ret['check_install'] = true;
        $ret['check_login'] = false;
        $ret['latest_version'] = false;
        $dashboard_info=get_option('wpvivid_dashboard_info', false);
        if($dashboard_info!==false)
        {
            if(isset($dashboard_info['check_active']))
            {
                if ($dashboard_info['check_active'])
                {
                    $ret['check_pro'] = true;
                    $ret['latest_version'] = $dashboard_info['dashboard']['version'];
                }
            }
        }
        $user_info=get_option('wpvivid_pro_user',false);
        if($user_info===false)
        {
            $ret['check_login'] = false;
        }
        else {
            $ret['check_login'] = true;
        }
        if(!$dashboard_info['check_active']){
            $ret['check_login'] = false;
        }
        return $ret;
    }

    public function wpvivid_get_wpvivid_info_addon_mainwp($data){
        $plugin_install_cache=$this->get_mainwp_need_install_plugins();

        if(empty($plugin_install_cache))
        {
            $ret['need_update'] = false;
        }
        else
        {
            $ret['need_update'] = true;
        }
        $ret['current_version'] = WPVIVID_BACKUP_PRO_VERSION;
        return $ret;
    }

    public function wpvivid_upgrade_plugin_addon_mainwp_v2($data)
    {
        try
        {
            $site_id = $data['site_id'];
            $options = self::wpvivid_get_upgrade_tasks();
            $upgrade_task=$options[$site_id];
            $upgrade_task['start_time'] = time();
            $upgrade_task['site_id'] = $site_id;
            $upgrade_task['status'] = 'running';
            set_time_limit(180);

            $info= get_option('wpvivid_pro_user',false);
            if($info===false)
            {
                $ret['result']='failed';
                $ret['error']='not found user info.';
                $upgrade_task['status'] = 'error';
                $upgrade_task['error'] = $ret['error'];
                self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
                return $ret;
            }

            $dashboard_info=get_option('wpvivid_dashboard_info',array());
            if(empty($dashboard_info))
            {
                $ret['result']='failed';
                $ret['error']='not found dashboard info.';
                $upgrade_task['status'] = 'error';
                $upgrade_task['error'] = $ret['error'];
                self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
                return $ret;
            }

            if(empty($upgrade_task['plugin_install_cache']))
            {
                $ret['result']='success';
                $upgrade_task['status'] = 'completed';
                self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
            }

            if(!class_exists('WPvivid_Plugin_Installer'))
                require_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/installer/class-wpvivid-installer.php';

            $installer=new WPvivid_Plugin_Installer();

            $plugin_data=array_shift($upgrade_task['plugin_install_cache']);
            $ret=$installer->mainwp_install_plugin($plugin_data);
            if($ret['result']!=='success')
            {
                $upgrade_task['status'] = 'error';
                $upgrade_task['error'] = $ret['error'];
                self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
                return $ret;
            }

            $ret['result']='success';
            $upgrade_task['status'] = 'completed';
            self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']=$e->getMessage();
            $upgrade_task['status'] = 'error';
            $upgrade_task['error'] = $ret['error'];
            self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
        }
        return $ret;
    }

    public function init_upgrade_plugin_addon_mainwp_v2($data)
    {
        try
        {
            $site_id = $data['site_id'];
            $upgrade_task = array();
            $upgrade_task['start_time'] = time();
            $upgrade_task['site_id'] = $site_id;
            $upgrade_task['status'] = 'running';
            $upgrade_task['plugin_install_cache'] =array();
            self::wpvivid_update_upgrade_task($site_id, $upgrade_task);

            $info= get_option('wpvivid_pro_user',false);
            if($info===false)
            {
                $ret['result']='failed';
                $ret['error']='not found user info.';
                $upgrade_task['status'] = 'error';
                $upgrade_task['error'] = $ret['error'];
                self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
                return $ret;
            }

            $dashboard_info=get_option('wpvivid_dashboard_info',array());
            if(empty($dashboard_info))
            {
                $ret['result']='failed';
                $ret['error']='not found dashboard info.';
                $upgrade_task['status'] = 'error';
                $upgrade_task['error'] = $ret['error'];
                self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
                return $ret;
            }

            $upgrade_task['plugin_install_cache'] = $this->get_mainwp_need_install_plugins();

            if(empty($upgrade_task['plugin_install_cache']))
            {
                $ret['result']='success';
                $upgrade_task['status'] = 'completed';
                self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
                return $ret;
            }

            $ret['result']='success';
            self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']=$e->getMessage();
            $upgrade_task['status'] = 'error';
            $upgrade_task['error'] = $ret['error'];
            self::wpvivid_update_upgrade_task($site_id, $upgrade_task);
        }
        return $ret;
    }

    public function wpvivid_get_upgrade_progress_addon_mainwp_v2($data)
    {
        try
        {
            $need_delete_opt = false;
            $options = self::wpvivid_get_upgrade_tasks();
            $site_id = $data['site_id'];
            $ret['result']='success';
            if(isset($options[$site_id]) && !empty($options[$site_id]))
            {
                $time_spend=time()-$options[$site_id]['start_time'];
                if($time_spend > 180)
                {
                    $options[$site_id]['status'] = 'no_responds';
                    $options[$site_id]['error'] = 'Not responding for a long time.';
                    $need_delete_opt = true;
                }
                if($options[$site_id]['status'] === 'completed')
                {
                    $file_path = WPVIVID_BACKUP_PRO_PLUGIN_DIR.'/wpvivid-backup-pro.php';
                    $default_headers = array(
                        'Version' => 'Version'
                    );
                    $addon_data = get_file_data( $file_path, $default_headers);
                    if(!empty($addon_data['Version']))
                    {
                        $ret['current_version'] = $addon_data['Version'];
                    }
                    else{
                        $ret['current_version'] = WPVIVID_BACKUP_PRO_VERSION;
                    }

                    $plugin_install_cache=$options[$site_id]['plugin_install_cache'];

                    if(empty($plugin_install_cache))
                    {
                        $need_delete_opt = true;
                        $ret['need_update'] = false;
                    }
                    else
                    {
                        $ret['need_update'] = true;
                    }
                }
                if($options[$site_id]['status'] === 'error')
                {
                    $need_delete_opt = true;
                }
                $ret['upgrade_task'] = $options[$site_id];
                if($need_delete_opt){
                    unset($options[$site_id]);
                    update_option('wpvivid_upgrade_plugin_task_mainwp', $options, 'no');
                }
            }
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']=$e->getMessage();
        }
        return $ret;
    }

    public function wpvivid_login_account_addon_mainwp($data){
        try
        {
            if(isset($data['login_info']['wpvivid_dashboard_info'])){
                update_option('wpvivid_dashboard_info', $data['login_info']['wpvivid_dashboard_info'], 'no');
            }
            if(isset($data['login_info']['wpvivid_pro_user'])){
                update_option('wpvivid_pro_user', $data['login_info']['wpvivid_pro_user'], 'no');
            }
            update_option('wpvivid_last_update_time', time(), 'no');
            $plugin_install_cache=$this->get_mainwp_need_install_plugins();

            if(empty($plugin_install_cache))
            {
                $ret['need_update'] = false;
            }
            else
            {
                $ret['need_update'] = true;
            }
            $ret['current_version'] = WPVIVID_BACKUP_PRO_VERSION;
            $ret['result']='success';
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']=$e->getMessage();
        }
        return $ret;
    }

    public function get_plugins_status($dashboard_info)
    {
        global $wpvivid_backup_pro;
        $plugins=array();
        foreach ($dashboard_info['plugins'] as $slug=>$info)
        {
            $plugin['name']=$info['name'];
            $status=$wpvivid_backup_pro->addons_loader->get_plugin_status($info);
            $plugin['current_version']=$wpvivid_backup_pro->addons_loader->get_plugin_version($info);
            $plugin['latest_version']=$wpvivid_backup_pro->addons_loader->get_plugin_latest_version($info);

            $plugin['status']=$status['status'];
            $plugin['action']=$status['action'];
            $plugin['delete']=$status['delete'];

            $plugin['plugin_slug']=$slug;

            $plugins[$slug]=$plugin;
        }
        return $plugins;
    }

    public function wpvivid_get_wpvivid_info_addon_mainwp_ex($data)
    {
        global $wpvivid_backup_pro;
        //$data['backup_custom_setting'] = get_option('wpvivid_custom_backup_history', $data['backup_custom_setting']);
        $data['backup_custom_setting_ex'] = get_option('wpvivid_manual_backup_history', $data['backup_custom_setting']);
        $data['menu_capability'] = get_option('wpvivid_menu_cap_mainwp', $data['menu_capability']);
        $data['white_label_setting'] = get_option('white_label_setting', $data['white_label_setting']);
        $data['incremental_backup_setting']['enable_incremental_schedules']=get_option('wpvivid_enable_incremental_schedules',false);
        $data['incremental_backup_setting']['incremental_schedules']=get_option('wpvivid_incremental_schedules');
        $data['incremental_backup_setting']['incremental_history']=get_option('wpvivid_incremental_backup_history', array());
        $data['incremental_backup_setting']['incremental_backup_data']=get_option('wpvivid_incremental_backup_data',array());
        $data['incremental_backup_setting']['incremental_remote_backup_count']=get_option('wpvivid_incremental_remote_backup_count_addon', WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT);
        $data['incremental_backup_setting']['incremental_output_msg']=$this->wpvivid_get_incremental_output_msg();
        $data['last_backup_report'] = get_option('wpvivid_backup_reports', $data['last_backup_report']);
        $data['schedule_addon'] = get_option('wpvivid_schedule_addon_setting', $data['schedule_addon']);
        $data['setting_addon']['wpvivid_staging_options'] = get_option('wpvivid_staging_options', $data['setting_addon']['wpvivid_staging_options']);
        $data['setting_addon']['wpvivid_auto_backup_before_update'] = get_option('wpvivid_auto_backup_before_update',array());
        $data['setting_addon']['wpvivid_optimization_options'] = get_option('wpvivid_optimization_options',array());

        $data['setting_addon']['wpvivid_uc_scan_limit']=get_option('wpvivid_uc_scan_limit',20);
        $data['setting_addon']['wpvivid_uc_files_limit']=get_option('wpvivid_uc_files_limit',100);
        $data['setting_addon']['wpvivid_uc_quick_scan']=get_option('wpvivid_uc_quick_scan',false);
        $data['setting_addon']['wpvivid_uc_delete_media_when_delete_file']=get_option('wpvivid_uc_delete_media_when_delete_file',true);
        $data['setting_addon']['wpvivid_uc_ignore_webp']=get_option('wpvivid_uc_ignore_webp',false);

        $wpvivid_common_setting = get_option('wpvivid_common_setting');
        if(isset($wpvivid_common_setting['rollback_max_backup_count']))
            $rollback_max_backup_count = $wpvivid_common_setting['rollback_max_backup_count'];
        else
            $rollback_max_backup_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $data['setting_addon']['wpvivid_max_rollback_count']=get_option('wpvivid_max_rollback_count',array());
        $data['setting_addon']['wpvivid_auto_backup_db_before_update']=get_option('wpvivid_auto_backup_db_before_update', false);
        $data['setting_addon']['rollback_max_backup_count']=intval($rollback_max_backup_count);
        $data['setting_addon']['wpvivid_rollback_retain_local']=get_option('wpvivid_rollback_retain_local', 0);
        $data['setting_addon']['wpvivid_rollback_remote']=get_option('wpvivid_rollback_remote', 0);
        $data['setting_addon']['wpvivid_rollback_remote_id']=get_option('wpvivid_rollback_remote_id', 0);

        $data['time_zone']=get_option('gmt_offset');
        $data['is_install'] = true;
        $user_info=get_option('wpvivid_pro_user',false);
        if($user_info===false)
        {
            $data['is_login'] = false;
        }
        else {
            $data['is_login'] = true;
        }
        $dashboard_info=get_option('wpvivid_dashboard_info', array());
        if(!empty($dashboard_info))
        {
            if(isset($dashboard_info['check_active']))
            {
                if ($dashboard_info['check_active'])
                {
                    $data['is_pro'] = true;
                    $data['latest_version'] = $dashboard_info['dashboard']['version'];
                }
                else{
                    $data['is_login'] = false;
                }
            }
            else{
                $data['is_login'] = false;
            }
            $plugins=$this->get_plugins_status($dashboard_info);
            $data['addons_info'] = $plugins;
            $data['dashboard_version'] = WPVIVID_BACKUP_PRO_VERSION;
            $data['current_version'] = WPVIVID_BACKUP_PRO_VERSION;
        }
        else{
            $data['is_login'] = false;
            $data['addons_info'] = array();
            $data['dashboard_version'] = WPVIVID_BACKUP_PRO_VERSION;
            $data['current_version'] = WPVIVID_BACKUP_PRO_VERSION;
        }
        if(is_multisite())
        {
            $data['is_mu'] = true;
        }
        else
        {
            $data['is_mu'] = false;
        }

        return $data;
    }

    public function get_mainwp_sync_data($information)
    {
        global $wpvivid_plugin;
        remove_filter('wpvivid_get_mainwp_sync_data', array($wpvivid_plugin, 'get_mainwp_sync_data'));

        $data['setting']['wpvivid_compress_setting']=get_option('wpvivid_compress_setting');
        $data['setting']['wpvivid_local_setting']=get_option('wpvivid_local_setting');
        $data['setting']['wpvivid_common_setting']=get_option('wpvivid_common_setting');
        $data['setting']['wpvivid_email_setting']=get_option('wpvivid_email_setting');
        $data['setting']['cron_backup_count']=get_option('cron_backup_count');
        $data['schedule']=get_option('wpvivid_schedule_setting');
        $data['remote']['upload']=get_option('wpvivid_upload_setting');
        $data['remote']['history']=get_option('wpvivid_user_history');

        $data['setting_addon'] = $data['setting'];
        $data['setting_addon']['wpvivid_staging_options']=array();
        $data['backup_custom_setting']=array();
        $data['menu_capability']=array();
        $data['white_label_setting']=array();
        $data['incremental_backup_setting']=array();
        $data['last_backup_report']=array();
        $data['schedule_addon']=array();
        $data['time_zone']=false;
        $data['is_pro']=false;
        $data['is_install']=false;
        $data['is_login']=false;
        $data['latest_version']='';
        $data['current_version']='';
        $data['dashboard_version'] = '';
        $data['addons_info'] = array();
        $data=apply_filters('wpvivid_get_wpvivid_info_addon_mainwp_ex', $data);

        $information['syncWPvividSetting']=$data;
        return $information;
    }

    public function sync_others_data( $information, $data = array() )
    {
        try {

            if ( isset( $data['syncWPvividData'] ) ) {
                $information['syncWPvividData']         = 1;
                $information                            = apply_filters('wpvivid_get_mainwp_sync_data', $information);
            }
        } catch ( \Exception $e ) {

        }

        return $information;
    }
}