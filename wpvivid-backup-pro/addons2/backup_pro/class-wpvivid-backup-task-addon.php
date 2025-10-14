<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
if(class_exists('WPvivid_Backup_Task'))
{
    class WPvivid_Backup_Task_Ex extends WPvivid_Backup_Task
    {
        public function new_backup_task($options,$type,$action='backup')
        {
            if(isset($options['incremental']))
            {
                if(!$options['incremental_options']['first_backup'])
                {
                    $id=$options['incremental_options']['exist_backup_id'];
                    $this->task=false;
                    $this->task['id']=$id;
                }
                else
                {
                    $id=uniqid('wpvivid-');
                    $this->task=false;
                    $this->task['id']=$id;
                }
                $this->task['schedule_id']=$options['schedule_id'];
                $this->task['incremental_backup_files']=$options['incremental_backup_files'];
            }
            else
            {
                $id=uniqid('wpvivid-');
                $this->task=false;
                $this->task['id']=$id;
            }

            $this->task['action']=$action;
            $this->task['type']=$type;

            $this->task['status']['start_time']=time();
            $this->task['status']['run_time']=time();
            $this->task['status']['timeout']=time();
            $this->task['status']['str']='ready';
            $this->task['status']['resume_count']=0;

            if(isset($options['is_export']))
            {
                $this->task['is_export'] = true;
            }

            if(isset($options['remote']))
            {
                if($options['remote']=='1')
                {
                    if(isset($options['remote_options']))
                    {
                        $this->task['options']['remote_options']=$options['remote_options'];
                    }
                    else
                    {
                        $this->task['options']['remote_options']=WPvivid_Setting::get_remote_options();
                    }

                }
                else {
                    $this->task['options']['remote_options']=false;
                }
            }
            else
            {
                $this->task['options']['remote_options']=false;
            }

            $this->task['options']['remote_options'] = apply_filters('wpvivid_set_remote_options', $this->task['options']['remote_options'],$options);

            if(isset($options['local']))
            {
                if($options['local']=='1')
                {
                    $this->task['options']['save_local']=1;
                }
                else
                {
                    $this->task['options']['save_local']=0;
                }
            }
            else
            {
                $this->task['options']['save_local']=1;
            }

            if(isset($options['lock']))
            {
                $this->task['options']['lock']=$options['lock'];
            }
            else
            {
                $this->task['options']['lock']=0;
            }

            $general_setting=WPvivid_Setting::get_setting(true, "");

            if(isset($options['backup_prefix']) && !empty($options['backup_prefix']))
            {
                $backup_prefix=$options['backup_prefix'];
            }
            else
            {
                if(isset($general_setting['options']['wpvivid_common_setting']['domain_include'])&&$general_setting['options']['wpvivid_common_setting']['domain_include'])
                {
                    $check_addon = apply_filters('wpvivid_check_setting_addon', 'not_addon');
                    if (isset($general_setting['options']['wpvivid_common_setting']['backup_prefix']) && $check_addon == 'addon')
                    {
                        $backup_prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                    }
                    else {
                        $home_url_prefix = get_home_url();
                        $home_url_prefix = $this->parse_url_all($home_url_prefix);
                        $backup_prefix = $home_url_prefix;
                    }
                }
                else
                {
                    $backup_prefix='';
                }
            }
            $this->task['options']['backup_prefix']=$backup_prefix;
            $offset=get_option('gmt_offset');
            if(empty($backup_prefix))
                $this->task['options']['file_prefix'] = $this->task['id'] . '_' . date('Y-m-d-H-i', $this->task['status']['start_time']+$offset*60*60);
            else
                $this->task['options']['file_prefix'] = $backup_prefix . '_' . $this->task['id'] . '_' . date('Y-m-d-H-i', $this->task['status']['start_time']+$offset*60*60);

            $this->task['options']['file_prefix'] = apply_filters('wpvivid_backup_file_prefix',$this->task['options']['file_prefix'],$backup_prefix,$this->task['id'],$this->task['status']['start_time']);

            $this->task['options']['backup_options']['ismerge']=1;

            if(isset($options['ismerge']))
            {
                $this->task['options']['backup_options']['ismerge']=$options['ismerge'];
            }
            else
            {
                if(isset($general_setting['options']['wpvivid_common_setting']['ismerge']))
                {
                    $this->task['options']['backup_options']['ismerge']=intval($general_setting['options']['wpvivid_common_setting']['ismerge']);
                }
            }
            $this->task['options']['backup_options']['ismerge']=apply_filters('wpvivid_set_backup_ismerge',$this->task['options']['backup_options']['ismerge'],$options);

            $this->task['options']['log_file_name']=$id.'_backup';
            $log=new WPvivid_Log_Ex_addon();
            $log->CreateLogFile($this->task['options']['log_file_name'],'no_folder','backup');
            //$log->WriteLog(get_home_path(),'test');
            $this->task['options']['backup_options']['prefix']=$this->task['options']['file_prefix'];
            $this->task['options']['backup_options']['compress']=WPvivid_Setting::get_option('wpvivid_compress_setting');

            $this->task['options']['backup_options']['dir']=WPvivid_Custom_Interface_addon::wpvivid_get_local_backupdir();
            $this->task['options']['backup_options']['backup']=array();

            if(isset($options['backup_files']))
            {
                //$this->task['options']['backup_options']['backup_type'] = $options['backup_files'];
                if($options['backup_files']=='files+db')
                {
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_DB);
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_THEMES);
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_PLUGIN);
                    $general_setting=WPvivid_Setting::get_setting(true, "");
                    if(isset($general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload']) && !empty($general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload'])){
                        if($general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload']){
                            $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_UPLOADS_FILES);
                            //$this->set_backup(WPVIVID_BACKUP_TYPE_UPLOADS_FILES_OTHER);
                        }
                        else{
                            $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_UPLOADS);
                        }
                    }
                    else{
                        $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_UPLOADS);
                    }
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_CONTENT);
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_CORE);
                }
                else if($options['backup_files']=='files')
                {
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_THEMES);
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_PLUGIN);
                    $general_setting=WPvivid_Setting::get_setting(true, "");
                    if(isset($general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload']) && !empty($general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload'])){
                        if($general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload']){
                            $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_UPLOADS_FILES);
                            //$this->set_backup(WPVIVID_BACKUP_TYPE_UPLOADS_FILES_OTHER);
                        }
                        else{
                            $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_UPLOADS);
                        }
                    }
                    else{
                        $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_UPLOADS);
                    }
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_CONTENT);
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_CORE);
                }
                else if($options['backup_files']=='db')
                {
                    $this->set_backup(WPVIVID_PRO_BACKUP_TYPE_DB);
                }
            }
            else
            {
                //$this->task['options']['backup_options']['backup_type'] = 'Custom';
                $this->task['options']['backup_options'] = apply_filters('wpvivid_set_backup_type', $this->task['options']['backup_options'],$options);
            }
            $this->task['data']['doing']='backup';
            $this->task['data']['backup']['doing']='';
            $this->task['data']['backup']['finished']=0;
            $this->task['data']['backup']['progress']=0;
            $this->task['data']['backup']['job_data']=array();
            $this->task['data']['backup']['sub_job']=array();
            $this->task['data']['backup']['db_size']='0';
            $this->task['data']['backup']['files_size']['sum']='0';
            $this->task['data']['upload']['doing']='';
            $this->task['data']['upload']['finished']=0;
            $this->task['data']['upload']['progress']=0;
            $this->task['data']['upload']['job_data']=array();
            $this->task['data']['upload']['sub_job']=array();
            WPvivid_Setting::update_task($id,$this->task);
            $ret['result']='success';
            $ret['task_id']=$this->task['id'];
            $log->CloseFile();
            return $ret;
        }

        public function update_incremental_backup_data()
        {
            $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());

            $backup_files=$this->task['incremental_backup_files'];

            if(isset($incremental_backup_data[$this->task['schedule_id']])&&$incremental_backup_data[$this->task['schedule_id']][$backup_files])
            {
                $data=$incremental_backup_data[$this->task['schedule_id']][$backup_files];
            }
            else
            {
                $data=array();
            }

            $data['first_backup']=false;
            $data['exist_backup_id']=$this->task['id'];

            foreach ( $this->task['options']['backup_options']['backup'] as $key=>$backup)
            {
                if(empty($backup['result']['files']))
                {
                    continue;
                }
                else
                {
                    $data['versions']['version']++;
                    $data['versions']['skip_files_time']=time();
                    $data['versions']['backup_time']=time();
                    break;
                }
            }


            /*
            foreach ( $this->task['options']['backup_options']['backup'] as $key=>$backup)
            {
                if(empty($backup['result']['files']))
                    continue;

                if(isset($data[$key]))
                {
                    $data[$key]['version']++;
                }
                else
                {
                    $data[$key]['version']=1;
                }

                if(isset($backup['result']['backup_time']))
                    $data[$key]['backup_time']=$backup['result']['backup_time'];
                else
                    $data[$key]['backup_time']=0;
            }
            */

            $incremental_backup_data[$this->task['schedule_id']][$backup_files]=$data;
            WPvivid_Setting::update_option('wpvivid_incremental_backup_data',$incremental_backup_data);
        }
    }
}