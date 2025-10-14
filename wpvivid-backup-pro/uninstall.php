<?php

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}


function wpvivid_clear_dir($directory){
    if(file_exists($directory)){
        if($dir_handle=@opendir($directory)){
            while($filename=readdir($dir_handle)){
                if($filename!='.' && $filename!='..'){
                    $subFile=$directory."/".$filename;
                    if(is_dir($subFile)){
                        wpvivid_clear_dir($subFile);
                    }
                    if(is_file($subFile)){
                        unlink($subFile);
                    }
                }
            }
            closedir($dir_handle);
            rmdir($directory);
        }
    }
}

$wpvivid_common_setting = get_option('wpvivid_common_setting', array());
if(!empty($wpvivid_common_setting))
{
    if(isset($wpvivid_common_setting['uninstall_clear_folder']) && $wpvivid_common_setting['uninstall_clear_folder'])
    {
        $wpvivid_local_setting = get_option('wpvivid_local_setting', array());
        if(isset($wpvivid_local_setting['path']))
        {
            if($wpvivid_local_setting['path'] !== 'wpvividbackups')
            {
                wpvivid_clear_dir(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'wpvividbackups');
            }
            $wpvivid_local_setting['path']=basename($wpvivid_local_setting['path']);
            $source_array=array('.', '..', 'plugins', 'themes', 'uploads');
            if(!empty($wpvivid_local_setting['path']) && !in_array($wpvivid_local_setting['path'], $source_array))
            {
                if(is_dir(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$wpvivid_local_setting['path']))
                {
                    wpvivid_clear_dir(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $wpvivid_local_setting['path']);
                }
            }
        }
        else
        {
            wpvivid_clear_dir(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'wpvividbackups');
        }
    }
}

if(class_exists('WPvivid_Setting'))
{
    $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
    foreach ($schedules as $schedule_id => $schedule)
    {
        if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
        {
            wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
            $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
            wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
        }
    }
}


$need_remove_schedules=array();
$crons = _get_cron_array();

$wpvivid_hooks = array('wpvivid_dashboard_update_event', 'wpvivid_clean_remote_schedule_event', 'wpvivid_check_incremental_schedule_exist_event',
                        'wpvivid_check_rollback_event', 'wpvivid_imgoptim_auto_event', 'wpvivid_clean_local_storage_event');
foreach ( $crons as $cronhooks )
{
    foreach ($cronhooks as $hook_name=>$hook_schedules)
    {
        if(preg_match('#wpvivid_incremental_.*#',$hook_name))
        {
            foreach ($hook_schedules as $data)
            {
                $need_remove_schedules[$hook_name]=$data['args'];
            }
        }

        if(in_array($hook_name, $wpvivid_hooks))
        {
            foreach ($hook_schedules as $data)
            {
                $need_remove_schedules[$hook_name]=$data['args'];
            }
        }
    }
}

foreach ($need_remove_schedules as $hook_name=>$args)
{
    wp_clear_scheduled_hook($hook_name, $args);
    $timestamp = wp_next_scheduled($hook_name, array($args));
    wp_unschedule_event($timestamp,$hook_name,array($args));
}


delete_option('wpvivid_pro_addons_cache');

if(!function_exists('get_plugins'))
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');

$plugins=get_plugins();

delete_option('wpvivid_pro_user');
delete_option('wpvivid_connect_key');
delete_option('wpvivid_dashboard_info');
delete_option('wpvivid_plugins_first_install');
delete_option('wpvivid_connect_server_last_error');
delete_option('wpvivid_need_update_pro_notice');
delete_option('wpvivid_custom_backup_history');
delete_option('wpvivid_schedule_addon_setting');
delete_option('wpvivid_email_setting_addon');
delete_option('wpvivid_auto_backup_before_update');
//delete_option('wpvivid_staging_task_list');
//delete_option('wpvivid_staging_task_cancel');
delete_option('wpvivid_current_running_staging_task');
//delete_option('wpvivid_staging_options');
//delete_option('wpvivid_staging_history');
delete_option('wpvivid_select_list_remote_id');
delete_option('wpvivid_remote_list');
delete_option('wpvivid_last_update_time');
delete_option('wpvivid_active_info');
//delete_option('wpvivid_staging_list');
delete_option('wpvivid_migrate_list');
delete_option('wpvivid_current_schedule_id');
delete_option('wpvivid_backup_remote_need_update');
delete_option('wpvivid_default_mail');
delete_option('update_auto_backup_remote');
delete_option('wpvivid_backup_finished_tasks');
delete_option('wpvivid_remote_backups_lock');
delete_option('wpvivid_auto_update_addon');
delete_option('wpvivid_incremental_backup_history');
delete_option('wpvivid_enable_incremental_schedules');
delete_option('wpvivid_incremental_schedules');
delete_option('white_label_setting');
delete_option('wpvivid_menu_cap_mainwp');
delete_option('wpvivid_backup_reports');
delete_option('wpvivid_full_backup_last_msg');
delete_option('wpvivid_incremental_backup_last_msg');
delete_option('wpvivid_incremental_database_last_msg');
delete_option('wpvivid_first_auto_set_schedule');