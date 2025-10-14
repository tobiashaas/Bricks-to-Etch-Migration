<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Incremental_Backup_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Incremental_Backup_addon
{
    public function __construct()
    {
        //add_action('init',array( $this,'init_schedule_hooks'));
        add_action('wpvivid_handle_backup_succeed',array($this,'handle_backup_succeed'),10);
        add_action('wpvivid_handle_upload_succeed',array($this,'handle_backup_succeed'),10);
        add_action('wpvivid_handle_backup_failed',array($this,'handle_backup_failed'),10, 2);

        add_filter('wpvivid_custom_backup_options',array($this, 'custom_backup_options'), 12);
        add_filter('wpvivid_custom_backup_options_ex',array($this, 'custom_backup_options'), 12);
        add_filter('wpvivid_check_backup_options_valid',array($this, 'check_backup_options_valid'),11,4);
        add_filter('wpvivid_set_backup_type', array($this, 'set_backup_type'), 11, 2);
        add_filter('wpvivid_custom_set_backup', array($this, 'set_backup'), 10,2);
        add_filter('wpvivid_set_backup_ismerge', array($this, 'set_backup_ismerge'), 10,2);
        add_filter('wpvivid_get_schedule', array($this, 'get_schedule'),11,2);
        add_filter('wpvivid_backup_update_result', array($this, 'backup_update_result'),10,2);
        add_filter('wpvivid_set_remote_options', array($this, 'set_remote_options'), 11, 2);
        add_filter('wpvivid_set_remote_options_ex', array($this, 'set_remote_options'), 11, 2);
        add_filter('wpvivid_set_incremental_backup_file_name', array($this, 'set_incremental_backup_file_name'), 11, 3);
        add_filter('wpvivid_get_backup_folders_count', array($this, 'get_backup_folders_count'));
        add_filter('wpvivid_get_incremental_last_backup_message', array($this, 'get_incremental_last_backup_message'));
        add_filter('wpvivid_get_incremental_data', array($this, 'get_incremental_data'));
        add_action('wpvivid_reset_schedule', array($this, 'reset_schedule'), 11);
    }

    public function init_schedule_hooks()
    {
        $schedule_db_hooks=array();
        $schedule_files_hooks=array();
        $schedules = get_option('wpvivid_incremental_schedules', array());
        foreach ($schedules as $schedule)
        {
            $schedule_db_hooks[$schedule['db_schedule_id']]=$schedule['db_schedule_id'];
            $schedule_files_hooks[$schedule['files_schedule_id']]=$schedule['files_schedule_id'];
        }

        foreach ($schedule_db_hooks as $key=>$schedule_hook)
        {
            add_action($schedule_hook, array($this, 'incremental_db_schedule'));
        }

        foreach ($schedule_files_hooks as $key=>$schedule_hook)
        {
            add_action($schedule_hook, array($this, 'incremental_files_schedule'));
        }
    }

    public function handle_backup_succeed($task)
    {
        if($task['action']=='incremental')
        {
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Incremental backup finished.', 'notice');
            if(!class_exists('WPvivid_Backup_Task_Ex'))
            {
                include WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'addons2/backup_pro/class-wpvivid-backup-task-addon.php';
            }

            $backup_task=new WPvivid_Backup_Task_Ex($task['id']);
            $res=$backup_task->get_backup_result();
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('files:'.json_encode($res['files']), 'notice');
            if(!empty($res['files']))
            {
                $backup_list=new WPvivid_New_BackupList();
                $backup = $backup_list->get_backup_by_id($task['id']);
                if($backup!==false)
                {
                    $backup['backup']['files']=array_merge($backup['backup']['files'],$res['files']);
                    $backup_list->update_backup($task['id'],'backup', $backup['backup']);
                }
                else
                {
                    $remote_options = WPvivid_taskmanager::get_task_options($task['id'], 'remote_options');
                    if($remote_options != false)
                    {
                        do_action('wpvivid_clean_oldest_backup');
                        WPvivid_Setting::update_option('wpvivid_backup_remote_need_update', true);
                    }
                    else
                    {
                        $backup_task->add_new_backup();
                    }
                }
                if(isset($task['options']['backup_options']['backup']['backup_custom_themes']['json_info']['version']) && $task['options']['backup_options']['backup']['backup_custom_themes']['json_info']['version'] > 0){
                    WPvivid_Setting::update_option('wpvivid_incremental_backup_last_msg',$task);
                }
                else{
                    WPvivid_Setting::update_option('wpvivid_full_backup_last_msg',$task);
                }
            }
            else
            {
                $task['no_files']=true;
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Not found new files to backup.', 'notice');
            }
            $backup_task->update_incremental_backup_data();
        }
        else if($task['action'] === 'backup' && $task['type'] === 'Incremental' || $task['action'] === 'backup_remote' && $task['type'] === 'Incremental'){
            //database
            WPvivid_Setting::update_option('wpvivid_incremental_database_last_msg',$task);
        }
    }

    public function handle_backup_failed($task)
    {
        if($task['action']=='incremental')
        {
            if(isset($task['options']['backup_options']['backup']['backup_custom_themes']['json_info']['version']) && $task['options']['backup_options']['backup']['backup_custom_themes']['json_info']['version'] > 0){
                WPvivid_Setting::update_option('wpvivid_full_backup_last_msg',$task);
            }
            else{
                WPvivid_Setting::update_option('wpvivid_incremental_backup_last_msg',$task);
            }
        }
        else if($task['action'] === 'backup' && $task['type'] === 'Incremental'){
            //database
            WPvivid_Setting::update_option('wpvivid_incremental_database_last_msg',$task);
        }
    }

    public function incremental_db_schedule($schedule_id='')
    {
        global $wpvivid_plugin;

        do_action('wpvivid_set_current_schedule_id', $schedule_id);
        $wpvivid_plugin->end_shutdown_function=false;
        register_shutdown_function(array($wpvivid_plugin,'deal_prepare_shutdown_error'));
        $schedule_options=WPvivid_Schedule::get_schedule($schedule_id);
        if(empty($schedule_options))
        {
            $wpvivid_plugin->end_shutdown_function=true;
            die();
        }
        try
        {
            $backup=$schedule_options['backup_db'];
            $backup['local'] = strval($schedule_options['backup']['local']);
            $backup['remote'] = strval($schedule_options['backup']['remote']);
            $backup['lock'] = strval($schedule_options['backup']['lock']);
            $backup['backup_prefix'] = $schedule_options['backup']['backup_prefix'];

            $backup['type']='Incremental';
            if($backup['remote'])
            {
                $backup['action']='backup_remote';
            }
            else
            {
                $backup['action']='backup';
            }

            $backup['schedule_id']=$schedule_id;
            $backup['incremental_backup_db']=1;
            $backup['incremental_backup_files']='db';
            $backup = apply_filters('wpvivid_custom_backup_options', $backup);
            $ret = $wpvivid_plugin->check_backup_option($backup, $backup['type']);
            if ($ret['result'] != WPVIVID_PRO_SUCCESS)
            {
                $wpvivid_plugin->end_shutdown_function=true;
                echo json_encode($ret);
                die();
            }

            $ret = $this->pre_backup($backup);
            if ($ret['result'] == 'success')
            {
                //Check the website data to be backed up.
                $wpvivid_plugin->check_backup($ret['task_id'], $backup);
                global $wpvivid_backup_pro;
                $wpvivid_backup_pro->func->flush($ret['task_id']);
                //start backup task.
                $wpvivid_plugin->backup($ret['task_id']);
            }

            $wpvivid_plugin->end_shutdown_function=true;
            die();
        }
        catch (Exception $error)
        {
            $wpvivid_plugin->end_shutdown_function=true;
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            $id=uniqid('wpvivid-');
            $log_file_name=$id.'_backup';
            $log=new WPvivid_Log_Ex_addon();
            $log->CreateLogFile($log_file_name,'no_folder','backup');
            $log->WriteLog($message,'notice');
            $log->CloseFile();
            WPvivid_error_log::create_error_log($log->log_file);
            error_log($message);
            echo json_encode($ret);
            die();
        }
    }

    public function incremental_files_schedule($schedule_id='')
    {
        global $wpvivid_plugin;

        do_action('wpvivid_set_current_schedule_id', $schedule_id);
        $wpvivid_plugin->end_shutdown_function=false;
        register_shutdown_function(array($wpvivid_plugin,'deal_prepare_shutdown_error'));

        $schedule_options=WPvivid_Schedule::get_schedule($schedule_id);
        if(empty($schedule_options))
        {
            $wpvivid_plugin->end_shutdown_function=true;
            die();
        }
        try
        {
            $backup=$schedule_options['backup_files'];
            $backup['local'] = strval($schedule_options['backup']['local']);
            $backup['remote'] = strval($schedule_options['backup']['remote']);
            $backup['ismerge'] = strval($schedule_options['backup']['ismerge']);
            $backup['lock'] = strval($schedule_options['backup']['lock']);
            $backup['backup_prefix'] = $schedule_options['backup']['backup_prefix'];

            $backup['type']='Incremental';
            $backup['incremental']=1;
            $backup['schedule_id']=$schedule_id;
            $backup['incremental_backup_files']='files';
            self::check_incremental_schedule('files',$schedule_id);
            $backup = apply_filters('wpvivid_custom_backup_options', $backup);
            $ret = $wpvivid_plugin->check_backup_option($backup, $backup['type']);
            if ($ret['result'] != WPVIVID_PRO_SUCCESS)
            {
                $wpvivid_plugin->end_shutdown_function=true;
                echo json_encode($ret);
                die();
            }

            $ret = $this->pre_backup($backup);
            if ($ret['result'] == 'success')
            {
                //Check the website data to be backed up.
                $wpvivid_plugin->check_backup($ret['task_id'], $backup);
                global $wpvivid_backup_pro;
                $wpvivid_backup_pro->func->flush($ret['task_id']);
                //start backup task.
                $wpvivid_plugin->backup($ret['task_id']);
            }

            $wpvivid_plugin->end_shutdown_function=true;
            die();
        }
        catch (Exception $error)
        {
            $wpvivid_plugin->end_shutdown_function=true;
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            $id=uniqid('wpvivid-');
            $log_file_name=$id.'_backup';
            $log=new WPvivid_Log_Ex_addon();
            $log->CreateLogFile($log_file_name,'no_folder','backup');
            $log->WriteLog($message,'notice');
            $log->CloseFile();
            WPvivid_error_log::create_error_log($log->log_file);
            error_log($message);
            echo json_encode($ret);
            die();
        }
    }

    /***** incremental backup display filter begin *****/
    public function wpvivid_incremental_additional_database_display($html){
        $html = '';
        $history = WPvivid_custom_backup_selector::get_incremental_db_setting();
        if (empty($history))
        {
            $history = array();
        }
        if(isset($history['additional_database_option']))
        {
            if(isset($history['additional_database_option']['additional_database_list']))
                foreach ($history['additional_database_option']['additional_database_list'] as $database => $db_info)
                {
                    $html .= '<div class="wpvivid-text-line"><span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-additional-database-remove" database-name="'.$database.'"></span><span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue wpvivid-icon-16px-nopointer"></span><span class="wpvivid-text-line" option="additional_db_custom" name="'.$database.'">'.$database.'@'.$db_info['db_host'].'</span></div>';
                }
        }
        return $html;
    }
    /***** incremental backup display filter end *****/

    /***** incremental backup filters begin *****/
    public function custom_backup_options($options)
    {
        if(isset($options['incremental'])&&$options['incremental'])
        {
            $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());
            $backup_files=$options['incremental_backup_files'];

            if(isset($incremental_backup_data[$options['schedule_id']])&&isset($incremental_backup_data[$options['schedule_id']]['exist_backup_id']))
            {
                $options['incremental_options']['exist_backup_id']=$incremental_backup_data[$options['schedule_id']]['exist_backup_id'];
            }

            if(isset($incremental_backup_data[$options['schedule_id']])&&isset($incremental_backup_data[$options['schedule_id']][$backup_files]))
            {
                $options['incremental_options']=$incremental_backup_data[$options['schedule_id']][$backup_files];
            }
            $options['type']='Incremental';
            $options['action']='incremental';

            //unset($options['backup_files']);
        }

        return $options;
    }

    public function check_backup_options_valid($ret,$data,$backup_method)
    {
        $ret['result']=WPVIVID_PRO_FAILED;
        if(!isset($data['incremental_backup_files']) && !isset($data['backup_files'])&&!isset($data['backup_select']))
        {
            $ret['error']=__('A backup type is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['local']) && !isset($data['remote']))
        {
            $ret['error']=__('Choose at least one storage location for backups.', 'wpvivid');
            return $ret;
        }

        $data['local']=sanitize_text_field($data['local']);
        $data['remote']=sanitize_text_field($data['remote']);

        if(empty($data['local']) && empty($data['remote']))
        {
            $ret['error']=__('Choose at least one storage location for backups.', 'wpvivid');
            return $ret;
        }

        if($backup_method == 'Manual')
        {
            if ($data['remote'] === '1')
            {
                $remote_storage = WPvivid_Setting::get_remote_options();
                if ($remote_storage == false)
                {
                    $ret['error'] = __('There is no default remote storage configured. Please set it up first.', 'wpvivid');
                    return $ret;
                }
            }
        }
        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function set_backup_type($backup_options,$options)
    {
        if(isset($options['incremental_backup_files']))
        {
            if($options['incremental_backup_files']=='files')
            {
                if($options['backup_select']['themes']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_themes',$options);
                }
                if($options['backup_select']['plugin']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_plugin',$options);
                }
                if($options['backup_select']['uploads']==1)
                {
                    if(isset($backup_options['compress']['subpackage_plugin_upload'])&&$backup_options['compress']['subpackage_plugin_upload'])
                    {
                        $backup_type='backup_custom_uploads_files';
                    }
                    else{
                        $backup_type='backup_custom_uploads';
                    }
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,$backup_type,$options);
                }
                if($options['backup_select']['content']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_content',$options);
                }
                if($options['backup_select']['core']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_core',$options);
                }
                if($options['backup_select']['other']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_other',$options);
                }
            }
            else if($options['incremental_backup_files']=='db')
            {
                if($options['backup_select']['db']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_db',$options);
                }
                if($options['backup_select']['additional_db']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_additional_db',$options);
                }

            }
        }

        return $backup_options;
    }

    public function set_backup($backup_options,$options)
    {
        if(isset($options['incremental'])&&$options['incremental'])
        {
            if(isset($options['incremental_options']['versions']))
            {
                $backup_options['skip_files_time']=$options['incremental_options']['versions']['skip_files_time'];
                $backup_options['json_info']['version']=$options['incremental_options']['versions']['version'];
                $backup_options['json_info']['backup_time']=$options['incremental_options']['versions']['backup_time'];
            }
            else
            {
                $backup_options['json_info']['version']=0;
                $backup_options['json_info']['backup_time']=time();
            }
        }

        return $backup_options;
    }

    public function set_backup_ismerge($ismerge,$options)
    {
        if(isset($options['incremental_backup_files']))
        {
            if($options['incremental_backup_files']=='db')
            {
                $ismerge=0;
            }
        }
        return $ismerge;
    }

    public function get_schedule($schedule,$schedule_id)
    {
        $schedules=WPvivid_Setting::get_option('wpvivid_incremental_schedules');
        if(array_key_exists($schedule_id,$schedules))
        {
            return $schedules[$schedule_id];
        }
        else
        {
            return $schedule;
        }
    }

    public function backup_update_result($result,$backup_data)
    {
        if($result['result']==WPVIVID_PRO_SUCCESS)
        {
            if(empty($result['files']))
            {
                return $result;
            }

            $result['backup_time']=time();
        }

        return $result;
    }

    public function set_remote_options($remote_options, $options)
    {
        if($remote_options!==false)
        {
            if(isset($options['incremental'])||isset($options['incremental_backup_files']))
            {
                $remote_folder=$this->get_remote_folder();

                foreach ($remote_options as $key=>$remote_option)
                {
                    if(isset($remote_options[$key]['custom_path']))
                    {
                        $remote_options[$key]['custom_path'].='/'.$remote_folder;
                    }
                    else
                    {
                        $remote_options[$key]['path']=untrailingslashit($remote_options[$key]['path']).'/'.$remote_folder;
                    }
                }
            }
        }
        return $remote_options;
    }

    public function set_incremental_backup_file_name($file_name, $prefix, $task_type)
    {
        if($task_type === 'Incremental') {
            $file_name = $prefix . '_incremental_backup_all';
        }
        return $file_name;
    }

    public function get_backup_folders_count($incremental_remote_backup_count)
    {
        $default = WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT;
        $option=get_option('wpvivid_common_setting');
        if(isset($option['incremental_max_remote_backup_count']))
        {
            $incremental_remote_backup_count=$option['incremental_max_remote_backup_count'];
        }
        else
        {
            $incremental_remote_backup_count = WPvivid_Setting::get_option('wpvivid_incremental_remote_backup_count_addon', $default);
        }
        return $incremental_remote_backup_count;
    }

    public function get_incremental_last_backup_message($incremental_schedules_list){
        foreach ($incremental_schedules_list as $key => $value){
            if($value['backup_type'] === 'Full Backup'){
                $message=WPvivid_Setting::get_option('wpvivid_full_backup_last_msg');
                if(empty($message))
                {
                    $last_message='N/A';
                }
                else {
                    $offset=get_option('gmt_offset');
                    $time = $message['status']['start_time'] + ($offset * 60 * 60);
                    $time=date("H:i:s - F-d-Y ", $time);

                    if($message['status']['str'] == 'completed') {
                        $last_message=$time;
                    }
                    else if($message['status']['str'] == 'error') {
                        $last_message=$time;
                    }
                    else if($message['status']['str'] == 'cancel') {
                        $last_message=$time;
                    }
                    else{
                        $last_message=$time;
                    }
                }
                $incremental_schedules_list[$key]['backup_last_time'] = $last_message;
            }
            else if($value['backup_type'] === 'Incremental Backup'){
                $message=WPvivid_Setting::get_option('wpvivid_incremental_backup_last_msg');
                if(empty($message))
                {
                    $last_message='N/A';
                }
                else {
                    $offset=get_option('gmt_offset');
                    $time = $message['status']['start_time'] + ($offset * 60 * 60);
                    $time=date("H:i:s - F-d-Y ", $time);

                    if($message['status']['str'] == 'completed') {
                        $last_message=$time;
                    }
                    else if($message['status']['str'] == 'error') {
                        $last_message=$time;
                    }
                    else if($message['status']['str'] == 'cancel') {
                        $last_message=$time;
                    }
                    else{
                        $last_message=$time;
                    }
                }
                $incremental_schedules_list[$key]['backup_last_time'] = $last_message;
            }
            else if($value['backup_type'] === 'Database Backup'){
                $message=WPvivid_Setting::get_option('wpvivid_incremental_database_last_msg');
                if(empty($message))
                {
                    $last_message='N/A';
                }
                else {
                    $offset=get_option('gmt_offset');
                    $time = $message['status']['start_time'] + ($offset * 60 * 60);
                    $time=date("H:i:s - F-d-Y ", $time);

                    if($message['status']['str'] == 'completed') {
                        $last_message=$time;
                    }
                    else if($message['status']['str'] == 'error') {
                        $last_message=$time;
                    }
                    else if($message['status']['str'] == 'cancel') {
                        $last_message=$time;
                    }
                    else{
                        $last_message=$time;
                    }
                }
                $incremental_schedules_list[$key]['backup_last_time'] = $last_message;
            }
        }
        return $incremental_schedules_list;
    }
    /***** incremental backup filters end *****/

    /***** useful function begin *****/
    public function pre_backup($backup_options)
    {
        global $wpvivid_plugin;
        if(apply_filters('wpvivid_need_clean_oldest_backup',true,$backup_options))
        {
            $wpvivid_plugin->clean_oldest_backup();
        }
        //do_action('wpvivid_clean_oldest_backup',$backup_options);

        if(WPvivid_taskmanager::is_tasks_backup_running())
        {
            $ret['result']='failed';
            $ret['error']=__('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid');
            return $ret;
        }

        if(!class_exists('WPvivid_Backup_Task_Ex'))
        {
            include WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'addons2/backup_pro/class-wpvivid-backup-task-addon.php';
        }

        $backup=new WPvivid_Backup_Task_Ex();
        $ret=$backup->new_backup_task($backup_options,$backup_options['type'],$backup_options['action']);
        return $ret;
    }

    public static function check_incremental_schedule($backup_files,$schedule_id)
    {
        $schedule_options=WPvivid_Schedule::get_schedule($schedule_id);

        $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());

        if(isset($incremental_backup_data[$schedule_id])&&isset($incremental_backup_data[$schedule_id][$backup_files]))
        {
            if(time()>= $incremental_backup_data[$schedule_id][$backup_files]['next_start'])
            {
                $old_time=$incremental_backup_data[$schedule_id][$backup_files]['next_start'];
                $incremental_backup_data[$schedule_id][$backup_files]=array();
                $incremental_backup_data[$schedule_id][$backup_files]['first_backup']=true;
                $incremental_backup_data[$schedule_id][$backup_files]['versions']['version']=0;
                $incremental_backup_data[$schedule_id][$backup_files]['versions']['skip_files_time']=0;
                $incremental_backup_data[$schedule_id][$backup_files]['current_start']=$old_time;
                $recurrence = $schedule_options['incremental_recurrence'];
                if($recurrence=='wpvivid_2hours')
                {
                    $start_time = $old_time + 3600 * 2;
                    while( strtotime('now') > $start_time )
                    {
                        $start_time = $start_time + 3600 * 2;
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
                else if($recurrence=='wpvivid_6hours')
                {
                    $start_time = $old_time + 3600 * 6;
                    while( strtotime('now') > $start_time )
                    {
                        $start_time = $start_time + 3600 * 6;
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
                else if($recurrence=='wpvivid_12hours')
                {
                    $start_time = $old_time + 3600 * 12;
                    while( strtotime('now') > $start_time )
                    {
                        $start_time = $start_time + 3600 * 12;
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
                else if($recurrence=='wpvivid_daily')
                {
                    if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                        $start_time = $schedule_options[$backup_files.'_current_day'].' +1 day';
                    }
                    else{
                        $start_time = $schedule_options[$backup_files.'_current_day'];
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                else if($recurrence=='wpvivid_3days')
                {
                    if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                        $start_time = $schedule_options[$backup_files.'_current_day'].' +3 day';
                    }
                    else{
                        $start_time = $schedule_options[$backup_files.'_current_day'];
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                if($recurrence=='wpvivid_weekly')
                {
                    $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' next week';
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                else if($recurrence=='wpvivid_fortnightly')
                {
                    $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' +2 week';
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                else if($recurrence=='wpvivid_monthly')
                {
                    $day=$schedule_options['incremental_recurrence_day'];
                    if($day<10)
                    {
                        $day='0'.$day;
                    }

                    $date_now = date("Y-m-",time());
                    $monthly_tmp = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                    if(strtotime('now')>strtotime($monthly_tmp)){
                        $date_now = date("Y-m-",strtotime('+1 month'));
                        $monthly_start_time = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                    }
                    else{
                        $monthly_start_time = $monthly_tmp;
                    }
                    $start_time = strtotime($monthly_start_time);
                    //$start_time=strtotime(date('m', strtotime('+1 month')).'/'.$day.'/'.date('Y').' '.$schedule_options[$backup_files.'_current_day']);
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
            }
        }
        else
        {
            $incremental_backup_data[$schedule_id][$backup_files]['first_backup']=true;
            $incremental_backup_data[$schedule_id][$backup_files]['versions']['version']=0;
            $incremental_backup_data[$schedule_id][$backup_files]['versions']['skip_files_time']=0;
            $recurrence = $schedule_options['incremental_recurrence'];
            $incremental_backup_data[$schedule_id][$backup_files]['current_start']=time();

            if($recurrence=='wpvivid_2hours')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +2 hour';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_6hours')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +6 hour';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_12hours')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +12 hour';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_daily')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +1 day';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_3days')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +3 day';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            if($recurrence=='wpvivid_weekly')
            {
                $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' next week';
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);

            }
            else if($recurrence=='wpvivid_fortnightly')
            {
                $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' +2 week';
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_monthly')
            {
                $day=$schedule_options['incremental_recurrence_day'];
                if($day<10)
                {
                    $day='0'.$day;
                }

                $date_now = date("Y-m-",time());
                $monthly_tmp = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                if(strtotime('now')>strtotime($monthly_tmp)){
                    $date_now = date("Y-m-",strtotime('+1 month'));
                    $monthly_start_time = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                }
                else{
                    $monthly_start_time = $monthly_tmp;
                }
                $start_time = strtotime($monthly_start_time);
                //$start_time=strtotime(date('m', strtotime('+1 month')).'/'.$day.'/'.date('Y').' '.$schedule_options[$backup_files.'_current_day']);
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
            }
        }
        $incremental_backup_data[$schedule_id][$backup_files]['versions']['backup_time']=time();
        WPvivid_Setting::update_option('wpvivid_incremental_backup_data',$incremental_backup_data);
    }

    public function get_remote_folder()
    {
        $schedules= WPvivid_Setting::get_option('wpvivid_incremental_schedules',array());
        $schedule_options=array_shift($schedules);

        $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());
        $schedule_id=$schedule_options['id'];
        $backup_files='files';
        if(empty($incremental_backup_data))
        {
            self::check_incremental_schedule('files',$schedule_id);
            $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());
            $next_time=$incremental_backup_data[$schedule_id][$backup_files]['next_start'];
            $current_time=$incremental_backup_data[$schedule_id][$backup_files]['current_start'];
        }
        else
        {
            $next_time=$incremental_backup_data[$schedule_id][$backup_files]['next_start'];
            $current_time=$incremental_backup_data[$schedule_id][$backup_files]['current_start'];
        }

        $remote_folder1=date('Y_m_d',$current_time);
        $remote_folder2=date('Y_m_d',$next_time);
        $remote_folder=$remote_folder1.'_to_'.$remote_folder2;
        return $remote_folder;
    }

    public function reset_imcremental_schedule_start_time($schedule)
    {
        //set file start time
        if(isset($schedule['incremental_recurrence'])){
            $time['type']=$schedule['incremental_recurrence'];
        }
        else{
            $time['type']='wpvivid_weekly';
        }
        if(isset($schedule['incremental_recurrence_week'])) {
            $time['start_time']['week']=$schedule['incremental_recurrence_week'];
        }
        else
            $time['start_time']['week']='mon';
        if(isset($schedule['incremental_recurrence_day'])) {
            $time['start_time']['day']=$schedule['incremental_recurrence_day'];
        }
        else
            $time['start_time']['day']='01';
        if(isset($schedule['files_current_day'])) {
            $time['start_time']['current_day']=$schedule['files_current_day'];
        }
        else
            $time['start_time']['current_day']="00:00";

        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
        $schedule['files_start_time']=$timestamp;

        //set db start time
        if(isset($schedule['incremental_db_recurrence'])){
            $time['type']=$schedule['incremental_db_recurrence'];
        }
        else{
            $time['type']='wpvivid_weekly';
        }
        if(isset($schedule['incremental_db_recurrence_week'])) {
            $time['start_time']['week']=$schedule['incremental_db_recurrence_week'];
        }
        else
            $time['start_time']['week']='mon';
        if(isset($schedule['incremental_db_recurrence_day'])) {
            $time['start_time']['day']=$schedule['incremental_db_recurrence_day'];
        }
        else
            $time['start_time']['day']='01';
        if(isset($schedule['db_current_day'])) {
            $time['start_time']['current_day']=$schedule['db_current_day'];
        }
        else
            $time['start_time']['current_day']="00:00";
        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
        $schedule['db_start_time']=$timestamp;

        return $schedule;
    }
    /***** useful function end *****/

    public function get_incremental_data($data)
    {
        $enable_incremental_schedules=WPvivid_Setting::get_option('wpvivid_enable_incremental_schedules', false);
        $incremental_schedules=WPvivid_Setting::get_option('wpvivid_incremental_schedules');
        $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());
        if(!empty($incremental_schedules))
        {
            $schedule=array_shift($incremental_schedules);

            $full_backup_schedule=$schedule['incremental_recurrence'];
            $incremental_backup_schedule=$schedule['incremental_files_recurrence'];
            $database_backup_schedule=$schedule['incremental_db_recurrence'];

            if($enable_incremental_schedules)
            {
                $files_schedule_id=$schedule['files_schedule_id'];
                $db_schedule_id=$schedule['db_schedule_id'];
                $timestamp = wp_next_scheduled($files_schedule_id, array($schedule['id']));
                $files_next_start = $timestamp;

                //full backup
                if(empty($incremental_backup_data)) {
                    if($files_next_start==false) {
                        $next_start_of_full_backup='now';
                    }
                    else {
                        $next_start_of_full_backup=$files_next_start;
                    }

                }
                else if(isset($incremental_backup_data[$schedule['id']])&&isset($incremental_backup_data[$schedule['id']]['files'])) {
                    if($incremental_backup_data[$schedule['id']]['files']['first_backup']) {
                        $next_start_of_full_backup=$files_next_start;
                    }
                    else {
                        $next_start_of_full_backup=$incremental_backup_data[$schedule['id']]['files']['next_start'];
                    }
                }
                else {
                    $next_start_of_full_backup=$files_next_start;
                }

                if($next_start_of_full_backup !== false)
                {
                    if($next_start_of_full_backup=='now')
                    {
                        $next_start_of_full_backup=time();
                    }
                }
                else{
                    $next_start_of_full_backup = 0;
                }

                //incremental backup
                if($files_next_start !== false)
                {
                    $next_start_of_incremental_backup=$files_next_start;
                }
                else{
                    $next_start_of_incremental_backup = 0;
                }

                //database backup
                $timestamp = wp_next_scheduled($db_schedule_id, array($schedule['id']));
                $db_next_start = $timestamp;
                if($db_next_start !== false) {
                    $next_start_of_database_backup=$db_next_start;
                }
                else{
                    $next_start_of_database_backup = 0;
                }
            }
            else{
                $next_start_of_full_backup = 'N/A';
                $next_start_of_incremental_backup = 'N/A';
                $next_start_of_database_backup = 'N/A';
            }

            $full_backup['backup_next_time'] = $next_start_of_full_backup;
            $incremental_backup['backup_next_time'] = $next_start_of_incremental_backup;
            $database_backup['backup_next_time'] = $next_start_of_database_backup;

            $incremental_schedules_list['full_backup'] = $full_backup;
            $incremental_schedules_list['incremental_backup'] = $incremental_backup;
            $incremental_schedules_list['database_backup'] = $database_backup;
        }
        else{
            $full_backup['backup_next_time'] = 'N/A';
            $incremental_backup['backup_next_time'] = 'N/A';
            $database_backup['backup_next_time'] = 'N/A';

            $incremental_schedules_list['full_backup'] = $full_backup;
            $incremental_schedules_list['incremental_backup'] = $incremental_backup;
            $incremental_schedules_list['database_backup'] = $database_backup;
        }


        return $incremental_schedules_list;
    }

    public function reset_schedule()
    {
        $default = array();
        $enable_incremental_schedules=get_option('wpvivid_enable_incremental_schedules', false);
        $incremental_schedules=get_option('wpvivid_incremental_schedules', $default);

        $need_remove_schedules = array();
        $crons = _get_cron_array();
        foreach ($crons as $cronhooks) {
            foreach ($cronhooks as $hook_name => $hook_schedules) {
                if (preg_match('#wpvivid_incremental_.*#', $hook_name)) {
                    foreach ($hook_schedules as $data) {
                        $need_remove_schedules[$hook_name] = $data['args'];
                    }
                }
            }
        }

        foreach ($need_remove_schedules as $hook_name => $args) {
            wp_clear_scheduled_hook($hook_name, $args);
            $timestamp = wp_next_scheduled($hook_name, $args);
            wp_unschedule_event($timestamp, $hook_name, array($args));
        }

        if($enable_incremental_schedules && !empty($incremental_schedules))
        {
            $schedule_data=array_shift($incremental_schedules);
            $schedule_data = $this->reset_imcremental_schedule_start_time($schedule_data);
            if(wp_get_schedule($schedule_data['files_schedule_id'], array($schedule_data['id'])))
            {
                wp_clear_scheduled_hook($schedule_data['files_schedule_id'], array($schedule_data['id']));
                $timestamp = wp_next_scheduled($schedule_data['files_schedule_id'], array($schedule_data['id']));
                wp_unschedule_event($timestamp,$schedule_data['files_schedule_id'],array($schedule_data['id']));
            }

            if(wp_get_schedule($schedule_data['db_schedule_id'], array($schedule_data['id'])))
            {
                wp_clear_scheduled_hook($schedule_data['db_schedule_id'], array($schedule_data['id']));
                $timestamp = wp_next_scheduled($schedule_data['db_schedule_id'], array($schedule_data['id']));
                wp_unschedule_event($timestamp,$schedule_data['db_schedule_id'],array($schedule_data['id']));
            }

            wp_schedule_event($schedule_data['db_start_time'], $schedule_data['incremental_db_recurrence'], $schedule_data['db_schedule_id'],array($schedule_data['id']));
            wp_schedule_event($schedule_data['files_start_time'], $schedule_data['incremental_files_recurrence'], $schedule_data['files_schedule_id'],array($schedule_data['id']));

            update_option('wpvivid_incremental_backup_data',array(),'no');
        }
    }
}