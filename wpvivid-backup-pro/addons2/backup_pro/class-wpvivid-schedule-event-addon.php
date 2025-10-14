<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Schedule_Event_Addon
 */

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Schedule_Event_Addon
{
    public function __construct()
    {
        add_action('wpvivid_clean_remote_schedule_event',array( $this,'clean_remote_schedule_event'));
        add_action('wpvivid_clean_remote_schedule_single_event', array($this, 'clean_remote_schedule_event'));

        add_action('wpvivid_check_incremental_schedule_exist_event',array( $this,'check_schedule_incremental_exist_event'));

        if(!defined( 'DOING_CRON' ))
        {
            if(wp_get_schedule('wpvivid_check_incremental_schedule_exist_event')===false)
            {
                wp_schedule_event(time()+3600, 'daily', 'wpvivid_check_incremental_schedule_exist_event');
            }
        }

        $options=get_option('wpvivid_common_setting',array());

        if(isset($options['clean_local_storage']))
        {
            add_action('wpvivid_clean_local_storage_event',array( $this,'clean_local_storage_event'));

            if($options['clean_local_storage']['log'] || $options['clean_local_storage']['backup_cache']|| $options['clean_local_storage']['junk_files'])
            {
                $recurrence=$options['clean_local_storage']['recurrence'];
                if(!defined( 'DOING_CRON' ))
                {
                    if(wp_get_schedule('wpvivid_clean_local_storage_event')===false)
                    {
                        $offset=get_option('gmt_offset');
                        $timestamp=strtotime('00:00');
                        $timestamp=$timestamp+$offset*60*60;
                        wp_schedule_event($timestamp, $recurrence, 'wpvivid_clean_local_storage_event');
                    }
                }
            }
            else
            {
                if(wp_get_schedule('wpvivid_clean_local_storage_event'))
                {
                    wp_clear_scheduled_hook('wpvivid_clean_local_storage_event');
                    $timestamp = wp_next_scheduled('wpvivid_clean_local_storage_event');
                    wp_unschedule_event($timestamp,'wpvivid_clean_local_storage_event');
                }
            }
        }
    }

    public function clean_local_storage_event()
    {
        global $wpvivid_backup_pro;
        $backup_list=new WPvivid_New_BackupList();

        $delete_files = array();
        $delete_folder=array();

        $options=get_option('wpvivid_common_setting',array());

        if(isset($options['clean_local_storage']))
        {
            $options=$options['clean_local_storage'];
        }
        else
        {
            die();
        }

        if($options['log']==1)
        {
            $log_dir=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder();
            $error_log_dir=$log_dir.DIRECTORY_SEPARATOR.'error';
            $log_files=array();
            $temp=array();
            $this -> get_dir_files($log_files,$temp,$log_dir,array('file' => '&wpvivid-&'),array(),array(),0,false);
            $this -> get_dir_files($log_files,$temp,$error_log_dir,array('file' => '&wpvivid-&'),array(),array(),0,false);
            foreach ($log_files as $file)
            {
                $file_name=basename($file);
                $id=substr ($file_name,0,21);
                if($backup_list->get_backup_by_id($id)===false)
                {
                    $delete_files[]=$file;
                }
            }
        }

        if($options['backup_cache']==1)
        {
            $remote_backups=$backup_list->get_all_remote_backup();
            foreach ($remote_backups as $id=>$backup)
            {
                $backup_item = new WPvivid_New_Backup_Item($backup);
                $backup_item->cleanup_local_backup();
            }

            WPvivid_tools::clean_junk_cache();
        }

        if($options['junk_files']==1)
        {
            $list=$backup_list->get_all_backup();
            $files=array();
            foreach ($list as $backup_id => $backup)
            {
                $backup_item = new WPvivid_New_Backup_Item($backup);
                $file=$backup_item->get_files(false);
                foreach ($file as $filename)
                {
                    $files[]=$filename;
                }
            }

            $dir=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
            $path=str_replace('/',DIRECTORY_SEPARATOR,$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder());
            if(substr($path, -1) == DIRECTORY_SEPARATOR)
            {
                $path = substr($path, 0, -1);
            }
            $folder[]= $path;
            $except_regex['file'][]='&wpvivid-&';
            $except_regex['file'][]='&wpvivid_temp-&';
            $except_regex['file'][]='&'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-&';
            $except_regex['file'][]='&'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'_temp-&';
            $this -> get_dir_files($delete_files,$delete_folder,$dir,$except_regex,$files,$folder,0,false);
        }

        if(!empty($delete_files))
        {
            foreach ($delete_files as $file)
            {
                if(file_exists($file))
                    @unlink($file);
            }
        }

        if(!empty($delete_folder))
        {
            foreach ($delete_folder as $folder)
            {
                if(file_exists($folder))
                    WPvivid_tools::deldir($folder,'',true);
            }
        }

        die();
    }

    public function get_dir_files(&$files,&$folder,$path,$except_regex,$exclude_files=array(),$exclude_folder=array(),$exclude_file_size=0,$flag = true)
    {
        $handler=opendir($path);
        if($handler===false)
            return;
        while(($filename=readdir($handler))!==false)
        {
            if($filename != "." && $filename != "..")
            {
                $dir=str_replace('/',DIRECTORY_SEPARATOR,$path.DIRECTORY_SEPARATOR.$filename);


                if(in_array($dir,$exclude_folder))
                {
                    continue;
                }
                else if(is_dir($path.DIRECTORY_SEPARATOR.$filename))
                {
                    if($except_regex!==false)
                    {
                        if($this -> regex_match($except_regex['file'],$path.DIRECTORY_SEPARATOR.$filename,$flag)){
                            continue;
                        }
                        $folder[]=$path.DIRECTORY_SEPARATOR.$filename;
                    }else
                    {
                        $folder[]=$path.DIRECTORY_SEPARATOR.$filename;
                    }
                    $this->get_dir_files($files ,$folder, $path.DIRECTORY_SEPARATOR.$filename,$except_regex,$exclude_folder);
                }else {
                    if($except_regex===false||!$this -> regex_match($except_regex['file'] ,$path.DIRECTORY_SEPARATOR.$filename,$flag))
                    {
                        if(in_array($filename,$exclude_files))
                        {
                            continue;
                        }
                        if($exclude_file_size==0)
                        {
                            $files[] = $path.DIRECTORY_SEPARATOR.$filename;
                        }
                        else if(filesize($path.DIRECTORY_SEPARATOR.$filename)<$exclude_file_size*1024*1024)
                        {
                            $files[] = $path.DIRECTORY_SEPARATOR.$filename;
                        }
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);

    }

    private function regex_match($regex_array,$filename,$flag){
        if($flag){
            if(empty($regex_array)){
                return false;
            }
            if(is_array($regex_array)){
                foreach ($regex_array as $regex)
                {
                    if(preg_match($regex,$filename))
                    {
                        return true;
                    }
                }
            }else{
                if(preg_match($regex_array,$filename))
                {
                    return true;
                }
            }
            return false;
        }else{
            if(empty($regex_array)){
                return true;
            }
            if(is_array($regex_array)){
                foreach ($regex_array as $regex)
                {
                    if(preg_match($regex,$filename))
                    {
                        return false;
                    }
                }
            }else{
                if(preg_match($regex_array,$filename))
                {
                    return false;
                }
            }
            return true;
        }
    }

    public function clean_remote_schedule_event($backup_count=0,$db_count=0)
    {
        $load=new WPvivid_Load_Admin_Remote();
        $load->load();

        $remoteslist=WPvivid_Setting::get_all_remote_options();
        foreach ($remoteslist as $key=>$remote_option)
        {
            if($key=='remote_selected')
            {
                continue;
            }
            if(in_array($key, $remoteslist['remote_selected']))
            {
                set_time_limit(300);
                global $wpvivid_plugin;

                $remote_collection=new WPvivid_Remote_collection_addon();
                $remote = $remote_collection->get_remote($remote_option);
                try
                {
                    if (method_exists($remote, 'delete_old_backup_ex'))
                    {
                        $backup_count=$this->get_backup_retain_count('Manual',$remote_option,false);
                        $db_count=$this->get_backup_db_retain_count('Manual',$remote_option,false);
                        do_action('wpvivid_schedule_scan_remote_backup', $key, 'Manual', $backup_count, $db_count);

                        $backup_count=$this->get_backup_retain_count('Cron',$remote_option,false);
                        $db_count=$this->get_backup_db_retain_count('Cron',$remote_option,false);
                        do_action('wpvivid_schedule_scan_remote_backup', $key, 'Cron', $backup_count, $db_count);

                        $backup_count=$this->get_backup_retain_count('Rollback',$remote_option,false);
                        $db_count=$this->get_backup_db_retain_count('Rollback',$remote_option,false);
                        $remote->delete_old_backup_ex('Rollback',$backup_count,$db_count);

                        $backup_count=$this->get_backup_retain_count('Incremental',$remote_option,false);
                        $db_count=$this->get_backup_db_retain_count('Incremental',$remote_option,false);
                        $remote->delete_old_backup_ex('Incremental',$backup_count,$db_count);
                    }
                    else if(method_exists($remote, 'delete_old_backup'))
                    {
                        $option=WPvivid_Setting::get_option('wpvivid_common_setting');
                        if(isset($remote_option['backup_retain']))
                            $backup_count = $remote_option['backup_retain'];
                        else if (isset($option['max_remote_backup_count']))
                            $backup_count = $option['max_remote_backup_count'];
                        else
                            $backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;

                        if(isset($remote_option['backup_db_retain']))
                            $db_count = $remote_option['backup_db_retain'];
                        else if(isset($option['max_remote_backup_db_count']))
                            $db_count = $option['max_remote_backup_db_count'];
                        else
                            $db_count = 30;

                        $backup_count = intval($backup_count);
                        $db_count = intval($db_count);
                        $remote->delete_old_backup($backup_count,$db_count);
                    }
                }
                catch (Exception $e)
                {
                    continue;
                }
            }
        }
        die();
    }

    public function get_backup_retain_count($type,$remote_option,$force_reduce=false)
    {
        $option=get_option('wpvivid_common_setting');

        if($type=='Manual')
        {
            if(isset($remote_option['use_remote_retention']) && $remote_option['use_remote_retention'] == '1')
            {
                if(isset($remote_option['backup_retain']))
                {
                    $backup_count = $remote_option['backup_retain'];
                }
                else
                {
                    $backup_count = 30;
                }
            }
            else if(isset($option['manual_max_remote_backup_count']))
            {
                $backup_count = $option['manual_max_remote_backup_count'];
            }
            else if(isset($option['max_remote_backup_count']))
            {
                $backup_count = $option['max_remote_backup_count'];
            }
            else
            {
                $backup_count = 30;
            }
            if($backup_count==0)
            {
                $backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
            }
        }
        else if($type=='Cron')
        {
            if(isset($remote_option['use_remote_retention']) && $remote_option['use_remote_retention'] == '1')
            {
                if(isset($remote_option['backup_retain']))
                {
                    $backup_count = $remote_option['backup_retain'];
                }
                else
                {
                    $backup_count = 30;
                }
            }
            else if(isset($option['schedule_max_remote_backup_count']))
            {
                $backup_count = $option['schedule_max_remote_backup_count'];
            }
            else if(isset($option['max_remote_backup_count']))
            {
                $backup_count = $option['max_remote_backup_count'];
            }
            else
            {
                $backup_count = 30;
            }
            if($backup_count==0)
            {
                $backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
            }
        }
        else if($type=='Rollback')
        {
            if(isset($remote_option['use_remote_retention']) && $remote_option['use_remote_retention'] == '1')
            {
                if(isset($remote_option['backup_rollback_retain']))
                {
                    $backup_count = $remote_option['backup_rollback_retain'];
                }
                else
                {
                    $backup_count = 30;
                }
            }
            else if(isset($option['rollback_max_remote_backup_count']))
            {
                $backup_count = $option['rollback_max_remote_backup_count'];
            }
            else
            {
                $backup_count = 30;
            }
            if($backup_count==0)
            {
                $backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
            }
        }
        else if($type=='Incremental')
        {
            $incremental_remote_backup_count = WPvivid_Setting::get_option('wpvivid_incremental_remote_backup_count_addon', 3);

            if(isset($remote_option['use_remote_retention']) && $remote_option['use_remote_retention'] == '1')
            {
                if(isset($remote_option['backup_incremental_retain']))
                {
                    $backup_count = $remote_option['backup_incremental_retain'];
                }
                else
                {
                    $backup_count = $incremental_remote_backup_count;
                }
            }
            else if(isset($option['incremental_max_remote_backup_count']))
            {
                $backup_count = $option['incremental_max_remote_backup_count'];
            }
            else
            {
                $backup_count = $incremental_remote_backup_count;
            }
        }
        else
        {
            $backup_count=0;
        }

        if($force_reduce)
        {
            if ($backup_count - 1 > 0)
            {
                $backup_count = $backup_count - 1;
            }
        }

        return $backup_count;
    }

    public function get_backup_db_retain_count($type,$remote_option,$force_reduce=false)
    {
        $option=get_option('wpvivid_common_setting');

        if($type=='Manual')
        {
            if(isset($remote_option['use_remote_retention']) && $remote_option['use_remote_retention'] == '1')
            {
                if(isset($remote_option['backup_db_retain']))
                {
                    $db_count = $remote_option['backup_db_retain'];
                }
                else
                {
                    $db_count = 30;
                }
            }
            else if(isset($option['manual_max_remote_backup_db_count']))
            {
                $db_count = $option['manual_max_remote_backup_db_count'];
            }
            else if(isset($option['max_remote_backup_db_count']))
            {
                $db_count = $option['max_remote_backup_db_count'];
            }
            else
            {
                $db_count = 30;
            }
            if($db_count==0)
            {
                $db_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
            }
        }
        else if($type=='Cron')
        {
            if(isset($remote_option['use_remote_retention']) && $remote_option['use_remote_retention'] == '1')
            {
                if(isset($remote_option['backup_db_retain']))
                {
                    $db_count = $remote_option['backup_db_retain'];
                }
                else
                {
                    $db_count = 30;
                }
            }
            else if(isset($option['schedule_max_remote_backup_db_count']))
            {
                $db_count = $option['schedule_max_remote_backup_db_count'];
            }
            else if(isset($option['max_remote_backup_db_count']))
            {
                $db_count = $option['max_remote_backup_db_count'];
            }
            else
            {
                $db_count = 30;
            }
            if($db_count==0)
            {
                $db_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
            }
        }
        else if($type=='Rollback')
        {
            if(isset($remote_option['use_remote_retention']) && $remote_option['use_remote_retention'] == '1')
            {
                if(isset($remote_option['backup_rollback_retain']))
                {
                    $db_count = $remote_option['backup_rollback_retain'];
                }
                else
                {
                    $db_count = 30;
                }
            }
            else if(isset($option['rollback_max_remote_backup_count']))
            {
                $db_count = $option['rollback_max_remote_backup_count'];
            }
            else
            {
                $db_count = 30;
            }
            if($db_count==0)
            {
                $db_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
            }
        }
        else if($type=='Incremental')
        {
            $db_count=0;
        }
        else
        {
            $db_count=0;
        }

        if($force_reduce)
        {
            if ($db_count - 1 > 0)
            {
                $db_count = $db_count - 1;
            }
        }

        return $db_count;
    }

    public function check_schedule_incremental_exist_event()
    {
        $enable_incremental_schedules=WPvivid_Setting::get_option('wpvivid_enable_incremental_schedules', false);
        if($enable_incremental_schedules)
        {
            $incremental_schedules=WPvivid_Setting::get_option('wpvivid_incremental_schedules');
            $schedule_data=array_shift($incremental_schedules);

            if(!wp_get_schedule($schedule_data['files_schedule_id'], array($schedule_data['id'])))
            {
                if(wp_schedule_event($schedule_data['files_start_time'], $schedule_data['incremental_files_recurrence'], $schedule_data['files_schedule_id'],array($schedule_data['id']))===false)
                {
                    $ret['result']='failed';
                    $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                }
            }

            if(!wp_get_schedule($schedule_data['db_schedule_id'], array($schedule_data['id'])))
            {
                if(wp_schedule_event($schedule_data['db_start_time'], $schedule_data['incremental_db_recurrence'], $schedule_data['db_schedule_id'],array($schedule_data['id']))===false)
                {
                    $ret['result']='failed';
                    $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                }
            }
        }
    }
}