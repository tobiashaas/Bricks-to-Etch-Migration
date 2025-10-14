<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 */

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_New_Backup_Task
{
    public $task;
    public $task_id;
    public $current_job;
    public $current_db;

    public function __construct($task_id=false,$task=array())
    {
        $this->task_id=false;
        $this->current_job=false;

        if(empty($task))
        {
            if(!empty($task_id))
            {
                $default = array();
                $options = get_option('wpvivid_task_list', $default);
                if(isset($options[$task_id]))
                {
                    $this->task=$options[$task_id];
                    $this->task_id=$task_id;
                }
            }
        }
        else
        {
            $this->task_id=$task_id;
            $this->task=$task;
        }

    }

    public function get_new_id()
    {
        $rand_id = substr(md5(time().rand()), 0,13);
        return 'wpvivid-'.$rand_id;
    }

    public function new_backup_task($options,$settings,$backup_content)
    {
        $this->task=array();
        if(isset($options['incremental']))
        {
            if(!$options['incremental_options']['first_backup'])
            {
                $id=$options['incremental_options']['exist_backup_id'];
                $this->task['id']=$id;
            }
            else
            {
                $id=$this->get_new_id();
                $this->task['id']=$id;
            }
            $this->task['incremental']=1;
            $this->task['schedule_id']=$options['schedule_id'];
            $this->task['type']='Incremental';
        }
        else
        {
            $id=$this->get_new_id();
            $this->task['id']=$id;
            $this->task['type']=isset($options['type'])?$options['type']:'';
        }

        if(isset($options['lock']))
        {
            $this->task['options']['lock']=$options['lock'];
        }
        else
        {
            $this->task['options']['lock']=0;
        }

        if(isset($options['incremental_backup_files']))
        {
            $this->task['incremental_backup_files']=$options['incremental_backup_files'];
        }

        $this->task['status']['task_start_time']=time();
        $this->task['status']['task_end_time']=time();
        $this->task['status']['start_time']=time();
        $this->task['status']['run_time']=time();
        $this->task['status']['timeout']=time();
        $this->task['status']['str']='ready';
        $this->task['status']['resume_count']=0;

        $this->set_backup_option($options);
        $this->task['setting']=$settings;

        $this->task['data']['doing']='backup';
        $this->task['data']['backup']['doing']='';
        $this->task['data']['backup']['progress']=0;
        $this->task['data']['backup']['sub_job']=array();
        $this->task['data']['upload']['doing']='';
        $this->task['data']['upload']['finished']=0;
        $this->task['data']['upload']['progress']=0;
        $this->task['data']['upload']['job_data']=array();
        $this->task['data']['upload']['sub_job']=array();

        $this->init_backup_job($backup_content);

        delete_option('wpvivid_task_list');
        WPvivid_Setting::update_task($id,$this->task);
        $ret['result']='success';
        $ret['task']=$this->task;
        $ret['task_id']=$this->task['id'];

        return $ret;
    }

    public function get_start_time()
    {
        return $this->task['status']['task_start_time'];
    }

    public function get_end_time()
    {
        return $this->task['status']['task_end_time'];
    }

    public function update_end_time()
    {
        $this->task['status']['task_end_time']=time();
        $this->update_task();
    }

    public function set_backup_option($options)
    {
        $offset=get_option('gmt_offset');
        $this->task['options']=$options;

        $general_setting=WPvivid_Setting::get_setting(true, "");

        if(isset($options['backup_prefix']) && !empty($options['backup_prefix']))
        {
            $this->task['options']['backup_prefix']=$options['backup_prefix'];
        }
        else
        {
            if(isset($general_setting['options']['wpvivid_common_setting']['domain_include'])&&$general_setting['options']['wpvivid_common_setting']['domain_include'])
            {
                $check_addon = apply_filters('wpvivid_check_setting_addon', 'not_addon');
                if (isset($general_setting['options']['wpvivid_common_setting']['backup_prefix']) && $check_addon == 'addon')
                {
                    $this->task['options']['backup_prefix'] = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                }
                else {
                    $home_url_prefix = get_home_url();
                    $home_url_prefix = $this->parse_url_all($home_url_prefix);
                    $this->task['options']['backup_prefix'] = $home_url_prefix;
                }
            }
            else
            {
                $this->task['options']['backup_prefix']='';
            }
        }

        if(empty($this->task['options']['backup_prefix']))
        {
            $this->task['options']['file_prefix'] = $this->task['id'] . '_' . date('Y-m-d-H-i', time()+$offset*60*60);
        }
        else
        {
            $this->task['options']['file_prefix'] =  $this->task['options']['backup_prefix'] . '_' . $this->task['id'] . '_' . date('Y-m-d-H-i', time()+$offset*60*60);
        }
        $this->task['options']['file_prefix'] = apply_filters('wpvivid_backup_file_prefix',$this->task['options']['file_prefix'],$this->task['options']['backup_prefix'],$this->task['id'],$this->task['status']['start_time']);

        $this->task['options']['log_file_name']=$this->task['id'].'_backup';

        if(!class_exists('WPvivid_Log_Ex_addon'))
        {
            include WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'addons2/backup_pro/class-wpvivid-log-ex-addon.php';
        }
        $log=new WPvivid_Log_Ex_addon();
        if(isset($this->task['incremental_backup_files']))
        {
            $override = false;
        }
        else
        {
            $override = true;
        }
        $log->CreateLogFile($this->task['options']['log_file_name'],'no_folder','backup', $override);
        $this->task['options']['log_file_path']=$log->log_file;
        $this->task['options']['prefix']=$this->task['options']['file_prefix'];
        $this->task['options']['dir']=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        $this->task['options']['backup_dir']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
        $exclude_files=isset($options['exclude_files'])?$options['exclude_files']:array();

        $this->task['options']['exclude-tables']=isset($options['exclude-tables'])?$options['exclude-tables']:array();
        $this->task['options']['exclude-tables']=$this->default_exclude_table($this->task['options']['exclude-tables']);

        $this->task['options']['include-tables']=isset($options['include-tables'])?$options['include-tables']:array();

        $this->task['options']['exclude_files']=$this->get_exclude_files($exclude_files);
        $this->task['options']['include_files']=$this->get_include_files();

        $this->task['options']['include_plugins']=isset($options['include_plugins'])?$options['include_plugins']:array();
        $this->task['options']['include_themes']=isset($options['include_themes'])?$options['include_themes']:array();
        $this->task['options']['save_local'] =isset($options['save_local'])?$options['save_local']:false;
        if(isset($options['custom_other_include_files']))
        {
            $this->task['options']['custom_other_include_files']=$options['custom_other_include_files'];
        }
        else
        {
            $this->task['options']['custom_other_include_files']=array();
        }

        if(isset($options['custom_other_root']))
        {
            $this->task['options']['custom_other_root']=$options['custom_other_root'];
        }
        else
        {
            $this->task['options']['custom_other_root']=array();
        }

        if(isset($options['site_id']))
        {
            $this->task['options']['site_id']=$options['site_id'];
        }
        $log->CloseFile();

        $this->task['options']['remote_options'] = apply_filters('wpvivid_set_remote_options_ex', $this->task['options']['remote_options'],$this->task['options']);
    }

    public function default_exclude_table($exclude_tables)
    {
        global $wpdb;
        $exclude_tables[]=$wpdb->base_prefix."wpvivid_log";
        $exclude_tables[]=$wpdb->base_prefix."wpvivid_increment_big_ids";
        $exclude_tables[]=$wpdb->base_prefix."wpvivid_options";
        $exclude_tables[]=$wpdb->base_prefix."wpvivid_record_task";
        $exclude_tables[]=$wpdb->base_prefix."wpvivid_merge_db";
        $exclude_tables[]=$wpdb->base_prefix."wpvivid_merge_ids";
        return $exclude_tables;
    }

    public function parse_url_all($url)
    {
        $parse = parse_url($url);
        //$path=str_replace('/','_',$parse['path']);
        $path = '';
        if(isset($parse['path'])) {
            $parse['path'] = str_replace('/', '_', $parse['path']);
            $path = $parse['path'];
        }
        return $parse['host'].$path;
    }

    public function init_backup_job($backup_content)
    {
        $index=0;
        $this->task['jobs']=array();

        $has_db=false;
        if(isset($backup_content['backup_db']))
        {
            $this->task['jobs'][$index]['backup_type']='backup_db';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['mysql_file_index']=1;
            $has_db=true;
            $index++;
        }

        if(isset($backup_content['backup_additional_db']))
        {
            $this->task['jobs'][$index]['backup_type']='backup_additional_db';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['mysql_file_index']=1;
            $index++;
        }

        if(isset($backup_content['backup_themes']))
        {
            $this->task['jobs'][$index]['backup_type']='backup_themes';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['index']=0;
            $index++;
        }

        if(isset($backup_content['backup_plugin']))
        {
            $this->task['jobs'][$index]['backup_type']='backup_plugin';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['index']=0;
            $index++;
        }

        if(is_multisite())
        {
            if(isset($backup_content['backup_mu_site_uploads']))
            {
                $this->task['jobs'][$index]['backup_type']='backup_mu_site_uploads';
                $this->task['jobs'][$index]['finished']=0;
                $this->task['jobs'][$index]['progress']=0;
                $this->task['jobs'][$index]['file_index']=1;
                $this->task['jobs'][$index]['index']=0;
                $index++;
                if(!is_main_site($this->task['options']['site_id']))
                {
                    if(isset($backup_content['backup_uploads']))
                    {
                        $this->task['jobs'][$index]['backup_type']='backup_uploads';
                        $this->task['jobs'][$index]['finished']=0;
                        $this->task['jobs'][$index]['progress']=0;
                        $this->task['jobs'][$index]['file_index']=1;
                        $this->task['jobs'][$index]['index']=0;
                        $index++;
                    }
                }
            }
            else
            {
                if(isset($backup_content['backup_uploads']))
                {
                    $this->task['jobs'][$index]['backup_type']='backup_uploads';
                    $this->task['jobs'][$index]['finished']=0;
                    $this->task['jobs'][$index]['progress']=0;
                    $this->task['jobs'][$index]['file_index']=1;
                    $this->task['jobs'][$index]['index']=0;
                    $index++;
                }
            }
        }
        else
        {
            if(isset($backup_content['backup_uploads']))
            {
                $this->task['jobs'][$index]['backup_type']='backup_uploads';
                $this->task['jobs'][$index]['finished']=0;
                $this->task['jobs'][$index]['progress']=0;
                $this->task['jobs'][$index]['file_index']=1;
                $this->task['jobs'][$index]['index']=0;
                $index++;
            }
        }


        if(isset($backup_content['backup_content']))
        {
            $this->task['jobs'][$index]['backup_type']='backup_content';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['index']=0;
            $index++;
        }

        if(isset($backup_content['backup_core']))
        {
            $this->task['jobs'][$index]['backup_type']='backup_core';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['index']=0;
            $index++;
        }

        if(isset($backup_content['backup_custom_other']))
        {
            $this->task['jobs'][$index]['backup_type']='backup_custom_other';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['index']=0;
            $index++;
        }

        $is_merge=$this->task['setting']['is_merge'];
        if(count($this->task['jobs'])==1)
        {
            $is_merge=false;
        }

        if(isset($this->task['options']['incremental_backup_files']))
        {
            if($this->task['options']['incremental_backup_files']=='db')
            {
                $is_merge=false;
            }
        }

        if($is_merge)
        {
            $this->task['jobs'][$index]['backup_type']='backup_merge';
            $this->task['jobs'][$index]['finished']=0;
            $this->task['jobs'][$index]['progress']=0;
            $this->task['jobs'][$index]['file_index']=1;
            $this->task['jobs'][$index]['child_file']=array();
            $this->task['jobs'][$index]['index']=0;
        }
    }

    public function get_exclude_files($exclude_files=array())
    {
        $exclude_plugins=array();
        $exclude_plugins=apply_filters('wpvivid_exclude_plugins',$exclude_plugins);
        $exclude_regex=array();
        foreach ($exclude_plugins as $exclude_plugin)
        {
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.'/'.$exclude_plugin), '/').'#';
        }

        foreach ($exclude_files as $exclude_file)
        {
            if($exclude_file['type']=='file'||$exclude_file['type']=='folder')
            {
                if(file_exists($exclude_file['path']))
                {
                    $exclude_regex[]='#^'.preg_quote($this -> transfer_path($exclude_file['path']), '/').'#';
                }
                else
                {
                    $path=WP_CONTENT_DIR.'/'.$exclude_file['path'];
                    if(file_exists($path))
                    {
                        $exclude_regex[]='#^'.preg_quote($this -> transfer_path($path), '/').'#';
                    }
                }
            }
            else if($exclude_file['type']=='ext')
            {
                $exclude_regex[]='#^.*\.'.$exclude_file['path'].'$#';
            }
        }

        $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).'/'.'wpvivid', '/').'#';
        $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).'/'.WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir(), '/').'#';

        if(defined('WPVIVID_UPLOADS_ISO_DIR'))
        {
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).'/'.WPVIVID_UPLOADS_ISO_DIR, '/').'#';
        }

        return $exclude_regex;
    }

    public function get_backup_type_exclude_files($backup_type)
    {
        $exclude_regex=array();

        if($backup_type=='backup_content')
        {
            $upload_dir = wp_upload_dir();
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).'/'.'plugins', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir']), '/').'$#';
            $exclude_regex[]='#^'.preg_quote($this->transfer_path(get_theme_root()), '/').'#';
        }
        else if($backup_type=='backup_uploads')
        {
            if(is_multisite()&&isset($this->task['options']['site_id']))
            {
                $upload_dir = wp_upload_dir();
                $exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].'/'.'sites'), '/').'#';
            }
        }
        else if($backup_type=='backup_mu_site_uploads')
        {
            $site_id=$this->task['options']['site_id'];
            $upload_dir = $this->get_site_upload_dir($site_id);
            if(is_main_site($site_id))
            {
                $exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].'/'.'sites'), '/').'#';
            }
        }
        else if($backup_type=='backup_custom_other')
        {

        }
        return $exclude_regex;
    }

    public function get_include_files()
    {
        $include_regex[]='#^'.preg_quote($this -> transfer_path(ABSPATH.'wp-admin'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(ABSPATH.'wp-includes'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(ABSPATH.'lotties'), '/').'#';

        return $include_regex;
    }

    public function set_memory_limit()
    {
        $memory_limit=isset($this->task['setting']['memory_limit'])?$this->task['setting']['memory_limit']:WPVIVID_PRO_MEMORY_LIMIT;
        @ini_set('memory_limit', $memory_limit);
    }

    public function is_backup_finished()
    {
        $finished=true;

        foreach ($this->task['jobs'] as $job)
        {
            if($job['finished']==0)
            {
                $finished=false;
                break;
            }
        }
        return $finished;
    }

    public function update_sub_task_progress($progress)
    {
        $this->task['status']['run_time']=time();
        $this->task['status']['str']='running';
        $this->task['data']['doing']='backup';
        $sub_job_name=$this->task['jobs'][$this->current_job]['backup_type'];
        $this->task['data']['backup']['doing']=$sub_job_name;
        $this->task['data']['backup']['sub_job'][$sub_job_name]['progress']=$progress;
        if(!isset( $this->task['data']['backup']['sub_job'][$sub_job_name]['job_data']))
        {
            $this->task['data']['backup']['sub_job'][$sub_job_name]['job_data']=array();
        }
        $this->update_task();
    }

    public function get_next_job()
    {
        $job_key=false;
        foreach ($this->task['jobs'] as $key=>$job)
        {
            if($job['finished']==0)
            {
                $job_key=$key;
                break;
            }
        }
        return $job_key;
    }

    //for mainwp client report
    public function get_backup_job_type($key)
    {
        if(!isset($this->task['jobs'][$key]))
        {
            return false;
        }
        $job=$this->task['jobs'][$key];
        return $job['backup_type'];
    }

    public function do_backup_job($key)
    {
        if(!isset($this->task['jobs'][$key]))
        {
            $ret['result']='failed';
            $ret['error']='not found job';
            return $ret;
        }

        //backup_type
        $this->current_job=$key;
        $job=$this->task['jobs'][$key];
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Prepare to backup '.$job['backup_type'].' files.','notice');

        $this->update_sub_task_progress(sprintf(__('Start backing up %s.', 'wpvivid-backuprestore'),$job['backup_type']));

        if($job['backup_type']=='backup_db')
        {
            $ret=$this->do_backup_db();
            if($ret['result']!='success')
            {
                return $ret;
            }
            else
            {
                $this->rename_backup_files($key);
            }
        }
        else if($job['backup_type']=='backup_additional_db')
        {
            $ret=$this->do_backup_additional_db();
            if($ret['result']!='success')
            {
                return $ret;
            }
            else
            {
                $this->rename_backup_files($key);
            }
        }
        else if($job['backup_type']=='backup_merge')
        {
            $ret=$this->do_backup_merge();
            if($ret['result']!='success')
            {
                return $ret;
            }
            else
            {
                $this->rename_backup_files($key);
            }
        }
        else
        {
            $ret=$this->do_backup_files($job['backup_type']);
            if($ret['result']!='success')
            {
                return $ret;
            }
            else
            {
                $this->rename_backup_files($key);
            }
        }

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backing up '.$job['backup_type'].' completed.','notice');
        $this->task['jobs'][$key]['finished']=1;
        $this->task['status']['resume_count']=0;

        $this->update_sub_task_progress(sprintf(__('Backing up %s finished.', 'wpvivid-backuprestore'),$job['backup_type']));
        $this->update_main_progress();

        $ret['result']='success';
        return $ret;
    }

    public function rename_backup_files($key)
    {
        if(isset($this->task['jobs'][$key]['zip_file']))
        {
            if(count($this->task['jobs'][$key]['zip_file'])==1)
            {
                $backup_type=$this->task['jobs'][$key]['backup_type'];
                $file_prefix=$this->task['options']['file_prefix'];

                $old_file=array_shift($this->task['jobs'][$key]['zip_file']);

                if($backup_type=='backup_merge')
                {
                    $backup_type='backup_all';
                }
                $filename=$file_prefix.'_'.$backup_type.'.zip';
                $zip['filename']=$filename;
                $zip['finished']=1;
                $this->task['jobs'][$key]['zip_file']=array();
                $this->task['jobs'][$key]['zip_file'][$filename]=$zip;

                $path=$this->task['options']['dir'].'/';
                rename($path.$old_file['filename'],$path.$filename);

                if($backup_type=='backup_all')
                {
                    $this->task['merge_info'][basename($filename)]=$this->task['merge_info'][basename($old_file['filename'])];
                    unset($this->task['merge_info'][basename($old_file['filename'])]);
                }

                $this->update_task();
            }
        }
    }

    public function update_main_progress()
    {
        $i_finished_backup_count=0;
        $i_sum=count($this->task['jobs']);
        foreach ($this->task['jobs'] as $job)
        {
            if($job['finished']==1)
            {
                $i_finished_backup_count++;
            }
        }
        $i_progress=intval(($i_finished_backup_count/$i_sum)*100);
        $this->task['data']['backup']['progress']=$i_progress;
        $this->update_task();
    }

    public function update_database_progress($i_progress)
    {
        $this->task['data']['backup']['progress']=$i_progress;
        $this->update_task();
    }

    public function delete_canceled_backup_files($task_id)
    {
        $path = $this->task['options']['dir'];
        $handler=opendir($path);
        if($handler!==false)
        {
            while(($filename=readdir($handler))!==false)
            {
                if(preg_match('#'.$task_id.'#',$filename) || preg_match('#'.apply_filters('wpvivid_fix_wpvivid_free', $task_id).'#',$filename))
                {
                    @unlink($path.'/'.$filename);
                }
            }
            @closedir($handler);
        }
    }

    public function update_task()
    {
        //wp_cache_flush();
        $default = array();
        $tasks = get_option('wpvivid_task_list', $default);
        if(array_key_exists ($this->task_id, $tasks))
        {
            $this->task['status']['run_time']=time();
            WPvivid_Setting::update_task($this->task_id,$this->task);
        }
        else
        {
            $this->delete_canceled_backup_files($this->task_id);
        }
    }

    public function do_backup_merge()
    {
        $root_path=$this->get_backup_root('backup_merge');

        $files=$this->get_merge_files($root_path);

        if(empty($files))
        {
            $ret['result']='success';
            return $ret;
        }

        $max_zip_file_size= $this->task['setting']['max_file_size']*1024*1024;


        $path=$this->task['options']['dir'].'/';

        $zip_method=isset($this->task['setting']['zip_method'])?$this->task['setting']['zip_method']:'ziparchive';
        $zip=new WPvivid_Zip_Addon($zip_method);

        $zip_file_name=$path.$this->get_zip_file('backup_merge');

        $numItems = count($files);
        $i = 0;
        $index=$this->get_zipped_file_index();
        foreach ($files as $file)
        {
            if($this->check_cancel_backup())
            {
                die();
            }

            if($i<$index)
            {
                $i++;
                continue;
            }

            if($max_zip_file_size==0)
                $max_zip_file_size = 4 * 1024 * 1024 * 1024;

            if(!file_exists($zip_file_name) || filesize($zip_file_name) == 0)
            {
                $zip->add_file($zip_file_name,$file,basename($file),dirname($file));
                $i++;

                $child_json=$this->get_file_json($file);
                $this->update_merge_zipped_file_index($i,basename($file),$child_json);

                if($i === $numItems)
                {
                    continue;
                }

                if((filesize($zip_file_name)>$max_zip_file_size) || ($i >= 55000))
                {
                    $json=array();
                    $json=$this->get_json_info('backup_merge',$json);
                    $this->update_zip_file(basename($zip_file_name),1,$json);
                    $zip_file_name=$path.$this->add_zip_file('backup_merge',basename($zip_file_name));
                }
            }
            else if(((filesize($zip_file_name) + filesize($file)) < $max_zip_file_size) && $i < 55000)
            {
                $zip->add_file($zip_file_name,$file,basename($file),dirname($file));
                $i++;

                $child_json=$this->get_file_json($file);
                $this->update_merge_zipped_file_index($i,basename($file),$child_json);

                if($i === $numItems)
                {
                    continue;
                }
            }
            else
            {
                $json=array();
                $json=$this->get_json_info('backup_merge',$json);
                $this->update_zip_file(basename($zip_file_name),1,$json);
                $zip_file_name=$path.$this->add_zip_file('backup_merge',basename($zip_file_name));

                $zip->add_file($zip_file_name,$file,basename($file),dirname($file));
                $i++;

                $child_json=$this->get_file_json($file);
                $this->update_merge_zipped_file_index($i,basename($file),$child_json);

                if($i === $numItems)
                {
                    continue;
                }
            }

            /*$zip->add_file($zip_file_name,$file,basename($file),dirname($file));
            $i++;

            $child_json=$this->get_file_json($file);
            $this->update_merge_zipped_file_index($i,basename($file),$child_json);

            if($i === $numItems)
            {
                continue;
            }

            if($max_zip_file_size !== 0 && (filesize($zip_file_name)>$max_zip_file_size))
            {
                $json=array();
                $json=$this->get_json_info('backup_merge',$json);
                $this->update_zip_file(basename($zip_file_name),1,$json);
                $zip_file_name=$path.$this->add_zip_file('backup_merge',basename($zip_file_name));
            }*/
        }

        $json=array();
        $json=$this->get_json_info('backup_merge',$json);
        $this->update_zip_file(basename($zip_file_name),1,$json);

        foreach ($this->task['jobs'][$this->current_job]['child_file'] as $file=>$child_json)
        {
            $data['file_name']=$file;
            $data['size']=filesize($path=$root_path.'/'.$file);
            $this->task['merge_info'][basename($zip_file_name)][]=$data;
        }

        foreach ($files as $file)
        {
            @unlink($file);
        }
        $ret['result']='success';
        return $ret;
    }

    public function do_backup_files($backup_type)
    {
        $root_path=$this->get_backup_root($backup_type);
        $exclude_files=$this->get_backup_type_exclude_files($backup_type);
        $backup_symlink_folder=$this->task['setting']['backup_symlink_folder'];
        if($root_path===false)
        {
            $ret['result']='failed';
            $ret['error']='backup type not found';
            return $ret;
        }
        $compress_file_use_cache= $this->task['setting']['compress_file_use_cache'];

        $replace_path=$this->get_replace_path($backup_type);

        if($compress_file_use_cache)
        {
            if(!$this->check_cache_files())
            {
                $this->clean_zip_files();

                if($backup_type=='backup_core')
                {
                    $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$this->task['options']['include_files']);
                }
                else if($backup_type=='backup_custom_other')
                {
                    $folders=array();
                    foreach ($this->task['options']['custom_other_root'] as $root_path)
                    {
                        $root_path=untrailingslashit($root_path);
                        $folders=array_merge($folders,$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$this->task['options']['custom_other_include_files']));
                    }
                }
                else if($backup_type=='backup_plugin')
                {
                    if(!empty($this->task['options']['include_plugins']))
                    {
                        $include_regex=array();
                        foreach ($this->task['options']['include_plugins'] as $plugins)
                        {
                            $include_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                        }
                        $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$include_regex);
                    }
                    else
                    {
                        $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files);
                    }
                }
                else if($backup_type=='backup_themes')
                {
                    if(!empty($this->task['options']['include_themes']))
                    {
                        $include_regex=array();
                        foreach ($this->task['options']['include_themes'] as $themes)
                        {
                            $include_regex[]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'#';
                        }
                        $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$include_regex);
                    }
                    else
                    {
                        $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files);
                    }
                }
                else
                {
                    $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files);
                }

                $cache_file_prefix=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.$this->task['options']['file_prefix'].'_'.$backup_type.'_';

                if($backup_type=='backup_core')
                {
                    $ret=$this->create_cache_files($cache_file_prefix,$root_path,$backup_symlink_folder,$exclude_files,$this->task['options']['include_files']);
                }
                else if($backup_type=='backup_custom_other')
                {
                    $ret=$this->create_custom_other_cache_files($cache_file_prefix,$this->task['options']['custom_other_root'],$backup_symlink_folder,$exclude_files,$this->task['options']['custom_other_include_files']);
                }
                else if($backup_type=='backup_plugin')
                {
                    if(!empty($this->task['options']['include_plugins']))
                    {
                        $include_regex=array();
                        foreach ($this->task['options']['include_plugins'] as $plugins)
                        {
                            $include_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                        }
                        $ret=$this->create_cache_files($cache_file_prefix,$root_path,$backup_symlink_folder,$exclude_files,$include_regex);
                    }
                    else
                    {
                        $ret=$this->create_cache_files($cache_file_prefix,$root_path,$backup_symlink_folder,$exclude_files);
                    }
                }
                else if($backup_type=='backup_themes')
                {
                    if(!empty($this->task['options']['include_themes']))
                    {
                        $include_regex=array();
                        foreach ($this->task['options']['include_themes'] as $themes)
                        {
                            $include_regex[]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'#';
                        }
                        $ret=$this->create_cache_files($cache_file_prefix,$root_path,$backup_symlink_folder,$exclude_files,$include_regex);
                    }
                    else
                    {
                        $ret=$this->create_cache_files($cache_file_prefix,$root_path,$backup_symlink_folder,$exclude_files);
                    }
                }
                else
                {
                    $ret=$this->create_cache_files($cache_file_prefix,$root_path,$backup_symlink_folder,$exclude_files);
                }

                if($ret['is_empty']===true)
                {
                    $ret['result']='success';
                    $this->clean_tmp_files();
                    return $ret;
                }

                $ret=$this->_backup_empty_folder($folders,$backup_type);

                if($ret['result']!='success')
                {
                    return $ret;
                }
            }

            $ret=$this->_backup_files_use_cache($backup_type,$replace_path);
        }
        else
        {
            if($backup_type=='backup_core')
            {
                $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$this->task['options']['include_files']);
            }
            else if($backup_type=='backup_custom_other')
            {
                $folders=array();
                foreach ($this->task['options']['custom_other_root'] as $root_path)
                {
                    $root_path=untrailingslashit($root_path);
                    $folders=array_merge($folders,$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$this->task['options']['custom_other_include_files']));
                }
            }
            else if($backup_type=='backup_plugin')
            {
                if(!empty($this->task['options']['include_plugins']))
                {
                    $include_regex=array();
                    foreach ($this->task['options']['include_plugins'] as $plugins)
                    {
                        $include_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                    }
                    $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$include_regex);
                }
                else
                {
                    $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files);
                }
            }
            else if($backup_type=='backup_themes')
            {
                if(!empty($this->task['options']['include_themes']))
                {
                    $include_regex=array();
                    foreach ($this->task['options']['include_themes'] as $themes)
                    {
                        $include_regex[]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'#';
                    }
                    $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files,$include_regex);
                }
                else
                {
                    $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files);
                }
            }
            else
            {
                $folders=$this->get_empty_folders($root_path,$replace_path,$backup_symlink_folder,$exclude_files);
            }

            if($backup_type=='backup_core')
            {
                $files=$this->get_files($root_path,$backup_symlink_folder,$exclude_files,$this->task['options']['include_files']);
            }
            else if($backup_type=='backup_custom_other')
            {
                $files=$this->get_custom_other_files($this->task['options']['custom_other_root'],$backup_symlink_folder,$exclude_files,$this->task['options']['custom_other_include_files']);
            }
            else if($backup_type=='backup_plugin')
            {
                if(!empty($this->task['options']['include_plugins']))
                {
                    $include_regex=array();
                    foreach ($this->task['options']['include_plugins'] as $plugins)
                    {
                        $include_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                    }
                    $files=$this->get_files($root_path,$backup_symlink_folder,$exclude_files,$include_regex);
                }
                else
                {
                    $files=$this->get_files($root_path,$backup_symlink_folder,$exclude_files);
                }
            }
            else if($backup_type=='backup_themes')
            {
                if(!empty($this->task['options']['include_themes']))
                {
                    $include_regex=array();
                    foreach ($this->task['options']['include_themes'] as $themes)
                    {
                        $include_regex[]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'#';
                    }
                    $files=$this->get_files($root_path,$backup_symlink_folder,$exclude_files,$include_regex);
                }
                else
                {
                    $files=$this->get_files($root_path,$backup_symlink_folder,$exclude_files);
                }
            }
            else
            {
                $files=$this->get_files($root_path,$backup_symlink_folder,$exclude_files);
            }

            $replace_path=$this->get_replace_path($backup_type);
            if(empty($files))
            {
                $ret['result']='success';
                return $ret;
            }
            else
            {
                $ret=$this->_backup_empty_folder($folders,$backup_type);

                if($ret['result']!='success')
                {
                    return $ret;
                }

                $ret=$this->_backup_files($files,$replace_path,$backup_type);
            }
        }

        return $ret;
    }

    public function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode('/',$values);
    }

    public function get_backup_root($backup_type)
    {
        if($backup_type=='backup_themes')
        {
            return $this->transfer_path(get_theme_root());
        }
        else if($backup_type=='backup_plugin')
        {
            return $this->transfer_path(WP_PLUGIN_DIR);
        }
        else if($backup_type=='backup_uploads')
        {
            $upload_dir = wp_upload_dir();
            return $this -> transfer_path($upload_dir['basedir']);
        }
        else if($backup_type=='backup_mu_site_uploads')
        {
            $upload_dir = $this->get_site_upload_dir($this->task['options']['site_id']);
            return $this -> transfer_path($upload_dir['basedir']);
        }
        else if($backup_type=='backup_content')
        {
            return $this -> transfer_path(WP_CONTENT_DIR);
        }
        else if($backup_type=='backup_core')
        {
            return $this -> transfer_path(ABSPATH);
        }
        else if($backup_type=='backup_custom_other')
        {
            return $this -> transfer_path(ABSPATH);
        }
        else if($backup_type=='backup_merge')
        {
            return $this -> transfer_path(WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath());
        }
        else
        {
            return false;
        }
    }

    public function get_replace_path($backup_type)
    {
        if($backup_type=='backup_themes')
        {
            return $this->transfer_path(WP_CONTENT_DIR.'/');
        }
        else if($backup_type=='backup_plugin')
        {
            return $this->transfer_path(WP_CONTENT_DIR.'/');
        }
        else if($backup_type=='backup_uploads')
        {
            return $this->transfer_path(WP_CONTENT_DIR.'/');
        }
        else if($backup_type=='backup_mu_site_uploads')
        {
            $upload_dir = $this->get_site_upload_dir($this->task['options']['site_id']);
            return $this -> transfer_path($upload_dir['basedir'].'/');
        }
        else if($backup_type=='backup_content')
        {
            return $this->transfer_path(WP_CONTENT_DIR.'/');
        }
        else if($backup_type=='backup_custom_other')
        {
            return $this -> transfer_path(ABSPATH);
        }
        else if($backup_type=='backup_core')
        {
            return $this -> transfer_path(ABSPATH);
        }
        else
        {
            return false;
        }
    }

    public function get_site_upload_dir($site_id, $time = null, $create_dir = true, $refresh_cache = false)
    {
        static $cache = array(), $tested_paths = array();

        $key = sprintf( '%d-%s',$site_id, (string) $time );

        if ( $refresh_cache || empty( $cache[ $key ] ) ) {
            $cache[ $key ] = $this->_wp_upload_dir( $site_id,$time );
        }

        /**
         * Filters the uploads directory data.
         *
         * @since 2.0.0
         *
         * @param array $uploads Array of upload directory data with keys of 'path',
         *                       'url', 'subdir, 'basedir', and 'error'.
         */
        $uploads = apply_filters( 'upload_dir', $cache[ $key ] );

        if ( $create_dir ) {
            $path = $uploads['path'];

            if ( array_key_exists( $path, $tested_paths ) ) {
                $uploads['error'] = $tested_paths[ $path ];
            } else {
                if ( ! wp_mkdir_p( $path ) ) {
                    if ( 0 === strpos( $uploads['basedir'], ABSPATH ) ) {
                        $error_path = str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir'];
                    } else {
                        $error_path = basename( $uploads['basedir'] ) . $uploads['subdir'];
                    }

                    $uploads['error'] = sprintf(
                    /* translators: %s: directory path */
                        __( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
                        esc_html( $error_path )
                    );
                }

                $tested_paths[ $path ] = $uploads['error'];
            }
        }

        return $uploads;
    }

    public function _wp_upload_dir($site_id, $time = null ) {
        $siteurl     = get_option( 'siteurl' );
        $upload_path = trim( get_option( 'upload_path' ) );

        if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
            $dir = WP_CONTENT_DIR . '/uploads';
        } elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
            // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
            $dir = path_join( ABSPATH, $upload_path );
        } else {
            $dir = $upload_path;
        }

        if ( ! $url = get_option( 'upload_url_path' ) ) {
            if ( empty( $upload_path ) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) ) {
                $url = WP_CONTENT_URL . '/uploads';
            } else {
                $url = trailingslashit( $siteurl ) . $upload_path;
            }
        }

        /*
         * Honor the value of UPLOADS. This happens as long as ms-files rewriting is disabled.
         * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
         */
        if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
            $dir = ABSPATH . UPLOADS;
            $url = trailingslashit( $siteurl ) . UPLOADS;
        }

        // If multisite (and if not the main site in a post-MU network)
        if ( is_multisite() && ! ( is_main_network() && is_main_site($site_id) && defined( 'MULTISITE' ) ) ) {
            if ( ! get_site_option( 'ms_files_rewriting' ) ) {
                /*
                 * If ms-files rewriting is disabled (networks created post-3.5), it is fairly
                 * straightforward: Append sites/%d if we're not on the main site (for post-MU
                 * networks). (The extra directory prevents a four-digit ID from conflicting with
                 * a year-based directory for the main site. But if a MU-era network has disabled
                 * ms-files rewriting manually, they don't need the extra directory, as they never
                 * had wp-content/uploads for the main site.)
                 */

                if ( defined( 'MULTISITE' ) ) {
                    $ms_dir = '/sites/' . $site_id;
                } else {
                    $ms_dir = '/' . $site_id;
                }

                $dir .= $ms_dir;
                $url .= $ms_dir;
            } elseif ( defined( 'UPLOADS' ) && ! ms_is_switched() ) {
                /*
                 * Handle the old-form ms-files.php rewriting if the network still has that enabled.
                 * When ms-files rewriting is enabled, then we only listen to UPLOADS when:
                 * 1) We are not on the main site in a post-MU network, as wp-content/uploads is used
                 *    there, and
                 * 2) We are not switched, as ms_upload_constants() hardcodes these constants to reflect
                 *    the original blog ID.
                 *
                 * Rather than UPLOADS, we actually use BLOGUPLOADDIR if it is set, as it is absolute.
                 * (And it will be set, see ms_upload_constants().) Otherwise, UPLOADS can be used, as
                 * as it is relative to ABSPATH. For the final piece: when UPLOADS is used with ms-files
                 * rewriting in multisite, the resulting URL is /files. (#WP22702 for background.)
                 */

                if ( defined( 'BLOGUPLOADDIR' ) ) {
                    $dir = untrailingslashit( BLOGUPLOADDIR );
                } else {
                    $dir = ABSPATH . UPLOADS;
                }
                $url = trailingslashit( $siteurl ) . 'files';
            }
        }

        $basedir = $dir;
        $baseurl = $url;

        $subdir = '';
        if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
            // Generate the yearly and monthly dirs
            if ( ! $time ) {
                $time = current_time( 'mysql' );
            }
            $y      = substr( $time, 0, 4 );
            $m      = substr( $time, 5, 2 );
            $subdir = "/$y/$m";
        }

        $dir .= $subdir;
        $url .= $subdir;

        return array(
            'path'    => $dir,
            'url'     => $url,
            'subdir'  => $subdir,
            'basedir' => $basedir,
            'baseurl' => $baseurl,
            'error'   => false,
        );
    }

    public function check_cache_files()
    {
        if($this->current_job!==false)
        {
            if(isset($this->task['jobs'][$this->current_job]['cache_files']))
            {
                return true;
            }
        }
        return false;
    }

    public function clean_tmp_files()
    {
        if($this->current_job!==false)
        {
            if(isset($this->task['jobs'][$this->current_job]['cache_files']))
            {
                foreach ($this->task['jobs'][$this->current_job]['cache_files'] as $cache_file)
                {
                    @unlink($cache_file['name']);
                }
            }

            if(isset($this->task['jobs'][$this->current_job]['mysql_dump_files']))
            {
                $files=$this->task['jobs'][$this->current_job]['mysql_dump_files'];
                if(count($files)==1)
                {
                    $path=$this->task['options']['dir'].'/';
                    if($this->task['jobs'][$this->current_job]['backup_type']=='backup_additional_db')
                    {
                        $new_file=$this->task['options']['file_prefix'].'_backup_additional_db.sql';
                        $new_file_zip=$this->task['options']['file_prefix'].'_backup_additional_db.sql.zip';
                    }
                    else
                    {
                        $new_file=$this->task['options']['file_prefix'].'_backup_db.sql';
                        $new_file_zip=$this->task['options']['file_prefix'].'_backup_db.sql.zip';
                    }

                    @unlink($path.$new_file);
                    @unlink($path.$new_file_zip);
                    if($this->task['options']['encrypt_db']==1)
                    {
                        @unlink($path.$new_file.'.crypt');
                        @unlink($path.$new_file_zip.'.crypt');
                    }
                }
                else
                {
                    $path=$this->task['options']['dir'].'/';
                    foreach ($files as $file)
                    {
                        $file_zip=$file.'.zip';
                        @unlink($path.$file);
                        @unlink($path.$file_zip);
                        if($this->task['options']['encrypt_db']==1)
                        {
                            @unlink($path.$file.'.crypt');
                            @unlink($path.$file_zip.'.crypt');
                        }
                    }
                }
            }
        }
    }

    public function get_empty_folders($root_path,$replace_path,$backup_symlink_folder=false,$exclude_files=array(),$include_files=array())
    {
        $folder=array();
        $exclude_regex=array_merge($this->task['options']['exclude_files'],$exclude_files);
        $root_path=untrailingslashit($root_path);
        $this->_get_folders($root_path,$replace_path,$folder,$backup_symlink_folder,$exclude_regex,$include_files);
        return $folder;
    }

    public function _get_folders($path,$replace_path,&$folders,$backup_symlink_folder=false,$exclude_regex=array(),$include_regex=array())
    {
        $handler = opendir($path);

        if($handler===false)
            return;

        while (($filename = readdir($handler)) !== false)
        {
            if ($filename != "." && $filename != "..")
            {
                if (is_dir($path . '/' . $filename))
                {
                    if($backup_symlink_folder == 1 || ($backup_symlink_folder == 0 && !@is_link($path . '/' . $filename)))
                    {
                        if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                        {
                            if ($this->regex_match($include_regex, $path . '/' . $filename, 1))
                            {
                                $folders[]=str_replace($replace_path,'',$this->transfer_path($path . '/' . $filename));
                                $this->_get_folders($path . '/' . $filename,$replace_path,$folders,$backup_symlink_folder,$exclude_regex,$include_regex);
                            }
                        }
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);

        return;
    }

    public function _backup_empty_folder($folders,$backup_type)
    {
        $file_prefix=$this->task['options']['file_prefix'];
        $file_index=$this->get_file_index();
        $zip_file_name=$file_prefix.'_'.$backup_type.'.part'.sprintf('%03d',($file_index)).'.zip';

        $zip_method=isset($this->task['setting']['zip_method'])?$this->task['setting']['zip_method']:'ziparchive';
        $zip=new WPvivid_Zip_Addon($zip_method);

        return $zip->addEmptyDir($zip_file_name,$folders);
    }

    public function create_cache_files($file_prefix,$root_path,$backup_symlink_folder=false,$exclude_files=array(),$include_files=array())
    {
        $number=1;
        $cache_file_handle=false;
        $max_cache_file_size=16*1024*1024;

        $exclude_regex=array_merge($this->task['options']['exclude_files'],$exclude_files);

        $exclude_file_size=$this->task['setting']['exclude_file_size'];

        $root_path=untrailingslashit($root_path);

        if(isset($this->task['options']['incremental_options']))
        {
            $skip_files_time=$this->task['options']['incremental_options']['versions']['skip_files_time'];
        }
        else
        {
            $skip_files_time=0;
        }

        $files=$this->get_file_cache($root_path,$file_prefix,$cache_file_handle,$max_cache_file_size,$number,$backup_symlink_folder,$exclude_regex,$include_files,$exclude_file_size,$skip_files_time);

        if($this->current_job!==false)
        {
            foreach ($files as $file)
            {
                $file_data['name']=$file;
                $file_data['index']=0;
                $file_data['finished']=0;
                $this->task['jobs'][$this->current_job]['cache_files'][$file_data['name']]=$file_data;
            }

            $this->update_task();
        }

        $ret['result']='success';
        $ret['is_empty']=$this->is_cache_empty($files);
        return $ret;
    }

    public function is_cache_empty($files)
    {
        $empty=true;
        foreach ($files as $file)
        {
            if(filesize($file)>0)
            {
                $empty=false;
                break;
            }
        }
        return $empty;
    }

    public function create_custom_other_cache_files($file_prefix,$custom_other_root,$backup_symlink_folder=false,$exclude_files=array(),$include_files=array())
    {
        $number=1;
        $cache_file_handle=false;
        $max_cache_file_size=16*1024*1024;

        $exclude_regex=array_merge($this->task['options']['exclude_files'],$exclude_files);

        $exclude_file_size=$this->task['setting']['exclude_file_size'];

        if(isset($this->task['options']['incremental_options']))
        {
            $skip_files_time=$this->task['options']['incremental_options']['versions']['skip_files_time'];
        }
        else
        {
            $skip_files_time=0;
        }

        $files=array();
        foreach ($custom_other_root as $root_path)
        {
            $files1=$this->get_file_cache($root_path,$file_prefix,$cache_file_handle,$max_cache_file_size,$number,$backup_symlink_folder,$exclude_regex,$include_files,$exclude_file_size,$skip_files_time);
            $files=array_merge($files,$files1);
        }

        if($this->current_job!==false)
        {
            foreach ($files as $file)
            {
                $file_data['name']=$file;
                $file_data['index']=0;
                $file_data['finished']=0;
                $this->task['jobs'][$this->current_job]['cache_files'][$file_data['name']]=$file_data;
            }

            $this->update_task();
        }

        $ret['result']='success';
        $ret['is_empty']=$this->is_cache_empty($files);
        return $ret;
    }

    public function update_files_cache($file_data)
    {
        if($this->current_job!==false)
        {
            $this->task['jobs'][$this->current_job]['cache_files'][$file_data['name']]=$file_data;
            $this->task['status']['resume_count']=0;
            $this->update_task();
        }
    }

    public function get_files_cache_list()
    {
        if($this->current_job!==false)
        {
            return $this->task['jobs'][$this->current_job]['cache_files'];
        }
        else
        {
            return array();
        }
    }

    public function get_files_from_cache($cache_file,$index,$max_count)
    {
        $files=array();
        $file = new SplFileObject($cache_file);
        $file->seek($index);

        $file->setFlags( \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD );

        $count=0;

        while(!$file->eof())
        {
            $src = $file->fgets();

            $src=trim($src,PHP_EOL);

            if(empty($src))
                continue;

            if(!file_exists($src))
            {
                continue;
            }

            $files[$src]=$src;
            $count++;

            if($count>$max_count)
            {
                break;
            }
        }

        $ret['eof']=$file->eof();
        $ret['files']=$files;
        return $ret;
    }

    public function get_files_from_cache_by_size($cache_file,$index,$max_zip_file_size, $use_pclzip)
    {
        $files=array();
        $file = new SplFileObject($cache_file);
        //$file->seek($index);
        if (version_compare(PHP_VERSION, '8.0.1', '>=') || $index == 0) {
            $file->seek($index);
        } else {
            if( $index == 1 ){
                $file->rewind(); // Ensure to go at first row before exit
                $file->fgets(); // Read line 0. Cursor remains now at line 1
            } else {
                $file->seek($index-1);
            }
        }

        $file->setFlags( \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD );

        $current_size=0;
        $current=$index;
        $current_file_index = 0;
        while(!$file->eof())
        {
            $src = $file->fgets();
            $src=trim($src,PHP_EOL);
            if(empty($src))
            {
                continue;
            }

            if(!file_exists($src))
            {
                continue;
            }

            if($max_zip_file_size==0)
                $max_zip_file_size = 4 * 1024 * 1024 * 1024;

            if($current_size > 0)
            {
                $current_size+=filesize($src);
            }

            if($current_size == 0)
            {
                $current++;
                $current_file_index++;
                $files[$src]=$src;
                $current_size+=filesize($src);
            }
            else if(($current_size>$max_zip_file_size) || ($use_pclzip && ($current_file_index >= 55000)))
            {
                break;
            }
            else
            {
                $current++;
                $current_file_index++;
                $files[$src]=$src;
            }

            /*$current_size+=filesize($src);
            $files[$src]=$src;

            if($max_zip_file_size==0)
                continue;

            if($current_size>$max_zip_file_size)
                break;*/
        }

        $ret['eof']=$file->eof();
        $ret['index']=$current;
        $ret['files']=$files;
        return $ret;
    }

    public function get_files_count($files,$index,$add_files_count)
    {
        $add_files = array_slice($files,$index,$add_files_count);

        if($index+$add_files_count>count($files))
        {
            $eof=true;
        }
        else
        {
            $eof=false;
        }
        $ret['eof']=$eof;
        $ret['files']=$add_files;
        return $ret;
    }

    public function get_files_size($files,$index,$max_zip_file_size,$use_pclzip)
    {
        $current=0;
        $current_file_index = 0;
        $current_size=0;
        $add_files=array();
        foreach ($files as $file)
        {
            if($current<$index)
            {
                $current++;
                continue;
            }

            if($max_zip_file_size==0)
                $max_zip_file_size = 4 * 1024 * 1024 * 1024;

            if($current_size > 0)
            {
                $current_size+=filesize($file);
            }

            if($current_size == 0)
            {
                $current++;
                $current_file_index++;
                $add_files[]=$file;
                $current_size+=filesize($file);
            }
            else if(($current_size>$max_zip_file_size) || ($use_pclzip && ($current_file_index >= 55000)))
            {
                break;
            }
            else
            {
                $current++;
                $current_file_index++;
                $add_files[]=$file;
            }

            /*$current++;
            $current_size+=filesize($file);
            $add_files[]=$file;

            if($max_zip_file_size==0)
                continue;

            if($current_size>$max_zip_file_size)
                break;*/
        }

        if($current>=count($files))
        {
            $eof=true;
        }
        else
        {
            $eof=false;
        }
        $ret['eof']=$eof;
        $ret['index']=$current;
        $ret['files']=$add_files;
        return $ret;
    }

    public function get_file_cache($path,$cache_prefix,&$cache_file_handle,$max_cache_file_size,&$number,$backup_symlink_folder,$exclude_files,$include_files,$exclude_file_size,$skip_files_time)
    {
        $files=array();

        if(!$cache_file_handle)
        {
            $cache_file=$cache_prefix.$number.'.cache';
            $cache_file_handle=fopen($cache_file,'a');
            $files[] = $cache_file;
        }
        $handler = opendir($path);

        if($handler===false)
            return $files;

        while (($filename = readdir($handler)) !== false)
        {
            if ($filename != "." && $filename != "..")
            {
                if (is_dir($path . '/' . $filename))
                {
                    if($backup_symlink_folder == 1 || ($backup_symlink_folder == 0 && !@is_link($path . '/' . $filename)))
                    {
                        if($this->regex_match($exclude_files, $this->transfer_path($path . '/' . $filename), 0))
                        {
                            if ($this->regex_match($include_files, $path . '/' . $filename, 1))
                            {
                                $files2=$this->get_file_cache($path . '/' . $filename,$cache_prefix,$cache_file_handle,$max_cache_file_size,$number,$backup_symlink_folder,$exclude_files,$include_files,$exclude_file_size,$skip_files_time);
                                $files=array_merge($files,$files2);
                            }
                        }
                    }
                }
                else
                {
                    if($this->regex_match($exclude_files, $this->transfer_path($path . '/' . $filename), 0))
                    {
                        if(is_readable($path . '/' . $filename))
                        {
                            if($backup_symlink_folder == 1 || ($backup_symlink_folder == 0 && !@is_link($path . '/' . $filename)))
                            {
                                if ($exclude_file_size != 0)
                                {
                                    if (filesize($path . '/' . $filename) < $exclude_file_size * 1024 * 1024)
                                    {
                                        $add=true;
                                    }
                                    else
                                    {
                                        $add=false;
                                    }
                                }
                                else
                                {
                                    $add=true;
                                }

                                if($add)
                                {
                                    if($skip_files_time>0)
                                    {
                                        $file_time=filemtime($path . '/' . $filename);
                                        if($file_time>0&&$file_time>$skip_files_time)
                                        {
                                            $line = $this->transfer_path($path . '/' . $filename).PHP_EOL;
                                            fwrite($cache_file_handle, $line);
                                        }
                                    }
                                    else
                                    {
                                        $line = $this->transfer_path($path . '/' . $filename).PHP_EOL;
                                        fwrite($cache_file_handle, $line);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);

        return $files;
    }

    public function get_zip_file($backup_type)
    {
        if($this->current_job!==false)
        {
            if(!isset($this->task['jobs'][$this->current_job]['zip_file']))
            {
                $file_prefix=$this->task['options']['file_prefix'];
                if($backup_type=='backup_merge')
                {
                    $backup_type='backup_all';
                }
                $filename=$file_prefix.'_'.$backup_type.'.part'.sprintf('%03d',($this->task['jobs'][$this->current_job]['file_index'])).'.zip';
                $zip['filename']=$filename;
                $zip['finished']=0;
                $this->task['jobs'][$this->current_job]['zip_file'][$filename]=$zip;
                $this->update_task();
                return $filename;
            }
            else
            {
                foreach ($this->task['jobs'][$this->current_job]['zip_file'] as $zip)
                {
                    if( $zip['finished']==0)
                    {
                        return $zip['filename'];
                    }
                }

                return false;
            }

        }
        else
        {
            return false;
        }
    }

    public function add_zip_file($backup_type,$old_zip_file='')
    {
        if($this->current_job!==false)
        {
            $this->task['jobs'][$this->current_job]['file_index']++;
            $file_prefix=$this->task['options']['file_prefix'];
            if($backup_type=='backup_merge')
            {
                $backup_type='backup_all';
                $root_path=$this->get_backup_root('backup_merge');
                foreach ($this->task['jobs'][$this->current_job]['child_file'] as $file=>$child_json)
                {
                    $data['file_name']=$file;
                    $data['size']=filesize($path=$root_path.'/'.$file);
                    $this->task['merge_info'][$old_zip_file][]=$data;
                }
                $this->task['jobs'][$this->current_job]['child_file']=array();
            }

            $filename=$file_prefix.'_'.$backup_type.'.part'.sprintf('%03d',($this->task['jobs'][$this->current_job]['file_index'])).'.zip';

            $zip['filename']=$filename;
            $zip['finished']=0;
            $this->task['jobs'][$this->current_job]['zip_file'][$filename]=$zip;
            $this->task['status']['resume_count']=0;
            $this->update_task();
            $this->set_time_limit();
            return $filename;
        }
        else
        {
            return false;
        }
    }

    public function update_zip_file($zip_name,$finished,$json=array())
    {
        if($this->current_job!==false)
        {
            if($json!==false)
                $this->add_json_file($zip_name,$json);
            $this->task['jobs'][$this->current_job]['zip_file'][$zip_name]['finished']=$finished;
            $this->task['jobs'][$this->current_job]['zip_file'][$zip_name]['json']=$json;
            $this->update_task();
        }
    }

    public function add_json_file($zip_name,$json)
    {
        $zip_method=isset($this->task['setting']['zip_method'])?$this->task['setting']['zip_method']:'ziparchive';
        $zip=new WPvivid_Zip_Addon($zip_method);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        return $zip->add_json_file($path.$zip_name,$json);
    }

    public function get_backup_info_file()
    {
        $backup_info=array();
        $backup_info['id']=$this->task_id;
        $backup_info['type']=$this->task['type'];

        $types_info=array();
        foreach ($this->task['jobs'] as $job)
        {
            $type='';
            if($job['backup_type']=='backup_merge')
            {
                $type='All';
            }
            else if($job['backup_type']=='backup_db')
            {
                $type='Database';
            }
            else if($job['backup_type']=='backup_additional_db')
            {
                $type='Additional Databases';
            }
            else if($job['backup_type']=='backup_themes')
            {
                $type='themes';
            }
            else if($job['backup_type']=='backup_plugin')
            {
                $type='plugins';
            }
            else if($job['backup_type']=='backup_mu_site_uploads'||$job['backup_type']=='backup_uploads')
            {
                $type='uploads';
            }
            else if($job['backup_type']=='backup_content')
            {
                $type='wp-content';
            }
            else if($job['backup_type']=='backup_core')
            {
                $type='Wordpress Core';
            }
            else if($job['backup_type']=='backup_custom_other')
            {
                $type='Others';
            }

            if(empty($type))
                continue;

            if(isset($job['zip_file']))
            {
                foreach ($job['zip_file'] as $zip)
                {
                    $data['file_name']=$zip['filename'];
                    $root_path=$this->get_backup_root('backup_merge');
                    $path=$root_path.'/'.$zip['filename'];
                    if(file_exists($path))
                    {
                        $data['size']=filesize($path);
                    }
                    else
                    {
                        $data['size']=$this->get_merged_file_size($data['file_name']);
                    }

                    $types_info[$type]['files'][]=$data;
                }
            }
            $json=array();
            if($job['backup_type']=='backup_merge')
            {
                $json= $this->task['merge_info'];
            }
            else
            {
                $json=$this->get_json_info($job['backup_type'],$json);
            }

            $types_info[$type]['info']=$json;
        }
        $backup_info['types']=$types_info;
        $root_path=$this->get_backup_root('backup_merge');
        $backup_info_file=$root_path.'/'.$this->task['options']['file_prefix'].'_backup_info.json';
        file_put_contents($backup_info_file,json_encode($backup_info));
        return $backup_info_file;
    }

    public function get_backup_info()
    {
        $backup_info=array();
        $backup_info['id']=$this->task_id;
        $backup_info['type']=$this->task['type'];

        $types_info=array();
        foreach ($this->task['jobs'] as $job)
        {
            $type='';
            if($job['backup_type']=='backup_merge')
            {
                $type='All';
            }
            else if($job['backup_type']=='backup_db')
            {
                $type='Database';
            }
            else if($job['backup_type']=='backup_additional_db')
            {
                $type='Additional Databases';
            }
            else if($job['backup_type']=='backup_themes')
            {
                $type='themes';
            }
            else if($job['backup_type']=='backup_plugin')
            {
                $type='plugins';
            }
            else if($job['backup_type']=='backup_mu_site_uploads'||$job['backup_type']=='backup_uploads')
            {
                $type='uploads';
            }
            else if($job['backup_type']=='backup_content')
            {
                $type='wp-content';
            }
            else if($job['backup_type']=='backup_core')
            {
                $type='Wordpress Core';
            }
            else if($job['backup_type']=='backup_custom_other')
            {
                $type='Others';
            }

            if(empty($type))
                continue;

            if(isset($job['zip_file']))
            {
                foreach ($job['zip_file'] as $zip)
                {
                    $data['file_name']=$zip['filename'];
                    $root_path=$this->get_backup_root('backup_merge');
                    $path=$root_path.'/'.$zip['filename'];
                    if(file_exists($path))
                    {
                        $data['size']=filesize($path);
                    }
                    else
                    {
                        $data['size']=$this->get_merged_file_size($data['file_name']);
                    }

                    $types_info[$type]['files'][]=$data;
                }
            }
            $json=array();
            if($job['backup_type']=='backup_merge')
            {
                $json= $this->task['merge_info'];
            }
            else
            {
                $json=$this->get_json_info($job['backup_type'],$json);
            }

            $types_info[$type]['info']=$json;
        }
        $backup_info['types']=$types_info;

        return $backup_info;
    }

    public function get_merged_file_size($file_name)
    {
        if(isset($this->task['merge_info']))
        {
            foreach ($this->task['merge_info'] as $zip_file=>$files_data)
            {
                foreach ($files_data as $data)
                {
                    if($file_name===$data['file_name'])
                    {
                        return $data['size'];
                    }
                }
            }
        }
        return 0;
    }

    public function _backup_files_use_cache($backup_type,$replace_path)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $max_zip_file_size= $this->task['setting']['max_file_size']*1024*1024;
        $add_files_count=$this->task['setting']['compress_file_count'];
        $path=$this->task['options']['dir'].'/';
        $files_cache_list=$this->get_files_cache_list();

        $zip_method=isset($this->task['setting']['zip_method'])?$this->task['setting']['zip_method']:'ziparchive';
        $zip=new WPvivid_Zip_Addon($zip_method);

        if($zip_method=='ziparchive')
        {
            if($zip->check_ziparchive_available())
            {
                $use_pclzip=false;
            }
            else
            {
                $use_pclzip=true;
            }
        }
        else
        {
            $use_pclzip=true;
        }

        $zip_file_name=$path.$this->get_zip_file($backup_type);
        $json=array();
        $json=$this->get_json_info($backup_type,$json);

        $numItems = count($files_cache_list);
        $i = 0;

        foreach ($files_cache_list as $cache_file)
        {
            $i++;

            if($cache_file['finished']==1)
                continue;
            $eof=false;
            while(!$eof)
            {
                if ($this->check_cancel_backup())
                {
                    die();
                }

                if ($use_pclzip)
                {
                    $files_cache = $this->get_files_from_cache_by_size($cache_file['name'], $cache_file['index'], $max_zip_file_size, $use_pclzip);
                    $eof = $files_cache['eof'];
                    $files = $files_cache['files'];
                    $cache_file['index'] = $files_cache['index'];

                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:' . basename($zip_file_name) . ' index:' . $cache_file['index'], 'notice');
                    $zip->add_files($zip_file_name, $replace_path, $files, false,true, $json);
                    //$cache_file['index'] += $add_files_count;
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:' . basename($zip_file_name) . ' success. index:' . $cache_file['index'] . ' file size:' . size_format(filesize($zip_file_name), 2), 'notice');

                    $this->update_zip_file(basename($zip_file_name), 1, false);
                    $this->update_files_cache($cache_file);
                    if ($i === $numItems && $eof)
                    {
                        continue;
                    }

                    $zip_file_name = $path . $this->add_zip_file($backup_type);
                }
                else
                {
                    if($backup_type === 'backup_uploads')
                    {
                        $backup_upload_use_cm_store=isset($this->task['setting']['backup_upload_use_cm_store'])?$this->task['setting']['backup_upload_use_cm_store']:false;
                        if($backup_upload_use_cm_store)
                        {
                            $use_cm_store=true;
                        }
                        else
                        {
                            $use_cm_store=false;
                        }
                    }
                    else
                    {
                        $use_cm_store=false;
                    }

                    //$files_cache = $this->get_files_from_cache($cache_file['name'], $cache_file['index'], $add_files_count);
                    $files_cache = $this->get_files_from_cache_by_size($cache_file['name'], $cache_file['index'], $max_zip_file_size, $use_pclzip);
                    $eof = $files_cache['eof'];
                    $files = $files_cache['files'];
                    $cache_file['index'] = $files_cache['index'];
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:' . basename($zip_file_name) . ' index:' . $cache_file['index'], 'notice');
                    $zip->add_files($zip_file_name, $replace_path, $files, $use_cm_store);
                    //$cache_file['index'] += $add_files_count;
                    $this->update_files_cache($cache_file);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:' . basename($zip_file_name) . ' success. index:' . $cache_file['index'] . ' file size:' . size_format(filesize($zip_file_name), 2), 'notice');

                    if ($i === $numItems && $eof)
                    {
                        continue;
                    }

                    $this->update_zip_file(basename($zip_file_name), 1, $json);
                    $zip_file_name = $path . $this->add_zip_file($backup_type);

                    /*if ($max_zip_file_size !== 0 && (filesize($zip_file_name) > $max_zip_file_size))
                    {
                        $this->update_zip_file(basename($zip_file_name), 1, $json);
                        $zip_file_name = $path . $this->add_zip_file($backup_type);
                    }*/
                }
            }
            $cache_file['finished']=1;
            $this->update_files_cache($cache_file);
        }

        if(!$use_pclzip)
        {
            $this->update_zip_file(basename($zip_file_name),1,$json);
        }

        $this->clean_tmp_files();

        $ret['result']='success';
        return $ret;
    }

    public function get_json_info($backup_type,$json)
    {
        global $wpdb;
        if($backup_type=='backup_themes')
        {
            $json['file_type']='themes';
            $json['root_flag']='wp-content';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
            $json['themes']=$this->get_themes_list();
        }
        else if($backup_type=='backup_plugin')
        {
            $json['file_type']='plugin';
            $json['root_flag']='wp-content';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
            $json['plugin']=$this->get_plugins_list();
        }
        else if($backup_type=='backup_uploads')
        {
            $json['file_type']='upload';
            $json['root_flag']='wp-content';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
        }
        else if($backup_type=='backup_mu_site_uploads')
        {
            $json['file_type']='upload';
            $json['root_flag']='wpvivid_mu_upload';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
            $json['site_id']=$this->task['options']['site_id'];
            $json['home_url']=get_home_url($this->task['options']['site_id']);
            $json['site_url']=get_site_url($this->task['options']['site_id']);
        }
        else if($backup_type=='backup_content')
        {
            $json['file_type']='wp-content';
            $json['root_flag']='wp-content';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
        }
        else if($backup_type=='backup_core')
        {
            $json['file_type']='wp-core';
            $json['include_path'][]='wp-includes';
            $json['include_path'][]='wp-admin';
            $json['include_path'][]='lotties';
            $json['wp_core']=1;
            $json['root_flag']='root';
            $json['home_url']=home_url();
        }
        else if($backup_type=='backup_custom_other')
        {
            $json['file_type']='custom';
            $json['root_flag']='root';
            $json['home_url']=home_url();
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
        }
        else if($backup_type=='backup_db')
        {
            global $wpdb;
            $json['dump_db']=1;
            $json['file_type']='databases';
            if(isset($this->task['options']['site_id']))
            {
                $json['site_id']=$this->task['options']['site_id'];
                global $wpdb;
                $site_prefix= $wpdb->get_blog_prefix($this->task['options']['site_id']);
                $json['home_url']=get_home_url($this->task['options']['site_id']);
                $json['site_url']=get_site_url($this->task['options']['site_id']);
                $json['blog_prefix']=$site_prefix;
                $json['mu_migrate']=1;
                $json['base_prefix']=$wpdb->get_blog_prefix(0);
                if(is_multisite())
                {
                    $mu_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
                    $json['network_plugins']=$mu_active_plugins;
                }
            }
            else
            {
                $json['home_url']=home_url();
            }

            $json['root_flag']='custom';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
            if(is_multisite())
            {
                $json['is_mu']=1;
            }

            //encrypt_db
            if(isset($this->task['options']['encrypt_db'])&&$this->task['options']['encrypt_db'])
            {
                $json['is_crypt_ex']=1;
            }

        }
        else if($backup_type=='backup_additional_db')
        {
            $json['dump_additional_db']=1;
            $json['file_type']='additional_databases';
            $json['home_url']=home_url();
            $json['root_flag']='custom';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );

            //encrypt_db
            if(isset($this->task['options']['encrypt_db'])&&$this->task['options']['encrypt_db'])
            {
                $json['is_crypt_ex']=1;
            }
        }
        else if($backup_type=='backup_merge')
        {
            $json['has_child']=1;
            $json['home_url']=home_url();
            $json['root_flag']='custom';
            $json['php_version']=phpversion();
            $json['mysql_version']=$wpdb->db_version();
            $json['wp_version'] = get_bloginfo( 'version' );
            $json['child_file']=array();
            foreach ($this->task['jobs'][$this->current_job]['child_file'] as $file=>$child_json)
            {
                $json['child_file'][$file]=$child_json;
            }
        }

        if(is_multisite())
        {
            $json['is_mu_site']=1;
        }
        else
        {
            $json['is_mu_site']=0;
        }

        if(isset($this->task['options']['incremental_options']))
        {
            $json['version']=$this->task['options']['incremental_options']['versions']['version'];
            $json['skip_files_time']=$this->task['options']['incremental_options']['versions']['skip_files_time'];
            $json['backup_time']=$this->task['options']['incremental_options']['versions']['backup_time'];
        }

        return $json;
    }

    public function get_themes_list()
    {
        $themes_list=array();
        $list=wp_get_themes();
        foreach ($list as $key=>$item)
        {
            $path=$this -> transfer_path(get_theme_root().'/'.$key);

            if($this->regex_match($this->task['options']['exclude_files'],$path, 0))
            {
                $themes_list[$key]['slug']=$key;
            }
        }
        return $themes_list;
    }

    public function get_plugins_list()
    {
        $plugins_list=array();
        if(!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $list=get_plugins();

        foreach ($list as $key=>$item)
        {
            if(dirname($key)=='.')
                continue;

            $path=$this -> transfer_path(WP_PLUGIN_DIR.'/'.$key);

            if($this->regex_match($this->task['options']['exclude_files'],$path, 0))
            {
                $plugins_list[dirname($key)]['slug']=dirname($key);
            }
        }
        return $plugins_list;
    }

    public function get_files($root_path,$backup_symlink_folder=false,$exclude_files=array(),$include_files=array())
    {
        $files=array();
        $exclude_regex=array_merge($this->task['options']['exclude_files'],$exclude_files);
        $exclude_file_size=$this->task['setting']['exclude_file_size'];
        $root_path=untrailingslashit($root_path);

        if(isset($this->task['options']['incremental_options']))
        {
            $skip_files_time=$this->task['options']['incremental_options']['versions']['skip_files_time'];
        }
        else
        {
            $skip_files_time=0;
        }

        $this->_get_files($root_path,$files,$backup_symlink_folder,$exclude_regex,$include_files,$exclude_file_size,$skip_files_time);

        return $files;
    }

    public function get_custom_other_files($custom_other_root,$backup_symlink_folder=false,$exclude_files=array(),$include_files=array())
    {
        $files=array();
        $exclude_regex=array_merge($this->task['options']['exclude_files'],$exclude_files);
        $exclude_file_size=$this->task['setting']['exclude_file_size'];

        if(isset($this->task['options']['incremental_options']))
        {
            $skip_files_time=$this->task['options']['incremental_options']['versions']['skip_files_time'];
        }
        else
        {
            $skip_files_time=0;
        }

        foreach ($custom_other_root as $root_path)
        {
            $root_path=untrailingslashit($root_path);
            $this->_get_files($root_path,$files,$backup_symlink_folder,$exclude_regex,$include_files,$exclude_file_size,$skip_files_time);

        }

        return $files;
    }

    public function _get_files($path,&$files,$backup_symlink_folder,$exclude_regex,$include_regex,$exclude_file_size,$skip_files_time)
    {
        $handler = opendir($path);

        if($handler===false)
            return;

        while (($filename = readdir($handler)) !== false)
        {
            if ($filename != "." && $filename != "..")
            {
                if (is_dir($path . '/' . $filename))
                {
                    if($backup_symlink_folder == 1 || ($backup_symlink_folder == 0 && !@is_link($path . '/' . $filename)))
                    {
                        if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                        {
                            if ($this->regex_match($include_regex, $path . '/' . $filename, 1))
                            {
                                $this->_get_files($path . '/' . $filename,$files,$backup_symlink_folder,$exclude_regex,$include_regex,$exclude_file_size,$skip_files_time);
                            }
                        }
                    }
                }
                else
                {
                    if(is_readable($path . '/' . $filename))
                    {
                        if($backup_symlink_folder == 1 || ($backup_symlink_folder == 0 && !@is_link($path . '/' . $filename)))
                        {
                            if($skip_files_time>0)
                            {
                                $file_time=filemtime($path . '/' . $filename);
                                if($file_time>0&&$file_time>$skip_files_time)
                                {
                                    if ($exclude_file_size == 0)
                                    {
                                        if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                                        {
                                            $files[]=$this->transfer_path($path . '/' . $filename);
                                        }
                                    }
                                    else
                                    {
                                        if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                                        {
                                            if (filesize($path . '/' . $filename) < $exclude_file_size * 1024 * 1024)
                                            {
                                                $files[]=$this->transfer_path($path . '/' . $filename);
                                            }
                                        }
                                    }
                                }
                            }
                            else
                            {
                                if ($exclude_file_size == 0)
                                {
                                    if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                                    {
                                        $files[]=$this->transfer_path($path . '/' . $filename);
                                    }
                                }
                                else
                                {
                                    if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                                    {
                                        if (filesize($path . '/' . $filename) < $exclude_file_size * 1024 * 1024)
                                        {
                                            $files[]=$this->transfer_path($path . '/' . $filename);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);

        return;
    }

    public function get_merge_files($root_path)
    {
        $files=array();
        foreach ($this->task['jobs'] as $job)
        {
            if($job['backup_type']=='backup_merge')
                continue;

            if(isset($job['zip_file']))
            {
                foreach ($job['zip_file'] as $zip)
                {
                    if( $zip['finished']!=0)
                    {
                        $files[]=$root_path.'/'.$zip['filename'];
                    }
                }
            }
        }

        return $files;
    }

    private function regex_match($regex_array,$string,$mode)
    {
        if(empty($regex_array))
        {
            return true;
        }

        if($mode==0)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return false;
                }
            }

            return true;
        }

        if($mode==1)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function _backup_files($files,$replace_path,$backup_type)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $max_zip_file_size= $this->task['setting']['max_file_size']*1024*1024;
        $add_files_count=$this->task['setting']['compress_file_count'];
        $path=$this->task['options']['dir'].'/';

        $zip_method=isset($this->task['setting']['zip_method'])?$this->task['setting']['zip_method']:'ziparchive';
        $zip=new WPvivid_Zip_Addon($zip_method);

        if($zip_method=='ziparchive')
        {
            if($zip->check_ziparchive_available())
            {
                $use_pclzip=false;
            }
            else
            {
                $use_pclzip=true;
            }
        }
        else
        {
            $use_pclzip=true;
        }

        $zip_file_name=$path.$this->get_zip_file($backup_type);
        $json=array();
        $json=$this->get_json_info($backup_type,$json);
        $eof=false;
        $index=$this->get_zipped_file_index();
        while(!$eof)
        {
            if($this->check_cancel_backup())
            {
                die();
            }

            if($use_pclzip)
            {
                $files_count=$this->get_files_size($files,$index,$max_zip_file_size,$use_pclzip);
                $eof=$files_count['eof'];
                $index=$files_count['index'];
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:'.basename($zip_file_name).' index:'.$index,'notice');
                $ret=$zip->add_files($zip_file_name,$replace_path,$files_count['files'],false,true,$json);
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:'.basename($zip_file_name).' success. index:'.$index.' file size:'.size_format(filesize($zip_file_name),2),'notice');

                if($ret['result']!='success')
                {
                    return $ret;
                }

                $this->update_zip_file(basename($zip_file_name),1,false);
                $this->update_zipped_file_index($index);

                if($eof)
                {
                    continue;
                }

                $zip_file_name=$path.$this->add_zip_file($backup_type);
            }
            else
            {
                if($backup_type === 'backup_uploads')
                {
                    $backup_upload_use_cm_store=isset($this->task['setting']['backup_upload_use_cm_store'])?$this->task['setting']['backup_upload_use_cm_store']:false;
                    if($backup_upload_use_cm_store)
                    {
                        $use_cm_store=true;
                    }
                    else
                    {
                        $use_cm_store=false;
                    }
                }
                else
                {
                    $use_cm_store=false;
                }

                //$files_count=$this->get_files_count($files,$index,$add_files_count);
                $files_count=$this->get_files_size($files,$index,$max_zip_file_size,$use_pclzip);
                $eof=$files_count['eof'];
                //$index+=$add_files_count;
                $index=$files_count['index'];
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:'.basename($zip_file_name).' index:'.$index,'notice');
                $ret=$zip->add_files($zip_file_name,$replace_path,$files_count['files'],$use_cm_store);
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing zip file:'.basename($zip_file_name).' success. index:'.$index.' file size:'.size_format(filesize($zip_file_name),2),'notice');
                if($ret['result']!='success')
                {
                    return $ret;
                }
                $this->update_zipped_file_index($index);
                if($eof)
                {
                    continue;
                }

                $this->update_zip_file(basename($zip_file_name),1,$json);
                $zip_file_name=$path.$this->add_zip_file($backup_type);

                /*if($max_zip_file_size !== 0 && (filesize($zip_file_name)>$max_zip_file_size))
                {
                    $this->update_zip_file(basename($zip_file_name),1,$json);
                    $zip_file_name=$path.$this->add_zip_file($backup_type);
                }*/
            }
        }

        if(!$use_pclzip)
            $this->update_zip_file(basename($zip_file_name),1,$json);

        $ret['result']='success';
        return $ret;
    }

    public function do_backup_db()
    {
        if(!class_exists('WPvividTypeAdapterFactory'))
        {
            include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-mysqldump-method.php';
        }
        $this->task['dump_setting']=$this->init_db_backup_setting();
        if(!class_exists('WPvivid_Mysqldump_Addon'))
        {
            include WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'addons2/backup_pro/class-wpvivid-mysqldump-addon.php';
        }
        $dump = new WPvivid_Mysqldump_Addon($this,$this->task['dump_setting']);
        $dump->connect();
        if(!isset($this->task['jobs'][$this->current_job]['sub_jobs']))
        {
            $ret=$dump->init_job();
            if($ret===false)
            {
                $ret['result']='failed';
                $ret['error']='tables not found.';
                return $ret;
            }
        }

        if($this->check_cancel_backup())
        {
            die();
        }

        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start exporting database.','notice');
        $ret= $dump->start_jobs();
        if($ret['result']=='success')
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Exporting database finished.','notice');
            $files=$this->get_mysql_dump_files();
            $jobs=$this->get_current_sub_job();
            $tables=array();
            foreach ( $jobs as $job)
            {
                $table['name']=$job['name'];
                $table['size']=$job['size'];
                $table['rows']=$job['rows'];
                $tables[]=$table;
            }
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start compressing database','notice');
            $find_zero_date=$dump->is_has_zero_date();
            $ret=$this->zip_mysql_dump_files($files,$tables,$find_zero_date);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Compressing database completed','notice');
        }

        $this->clean_tmp_files();

        return $ret;
    }

    public function get_current_mysql_file_index()
    {
        if($this->current_job!==false)
        {
            return $this->task['jobs'][$this->current_job]['mysql_file_index'];
        }
        else
        {
            return 1;
        }
    }

    public function reset_mysql_file_index()
    {
        $this->task['jobs'][$this->current_job]['mysql_file_index']=1;
        $this->update_task();
    }

    public function get_mysql_dump_files()
    {
        if($this->current_job!==false)
        {
            $files=$this->task['jobs'][$this->current_job]['mysql_dump_files'];
            if(count($files)==1)
            {
                $path=$this->task['options']['dir'].'/';

                $file=array_shift($files);
                if($this->task['jobs'][$this->current_job]['backup_type']=='backup_additional_db')
                {
                    $new_file=$this->task['options']['file_prefix'].'_backup_additional_db.sql';
                }
                else
                {
                    $new_file=$this->task['options']['file_prefix'].'_backup_db.sql';
                }

                rename($path.$file,$path.$new_file);

                $dump_files=array();
                $dump_files[]=$path.$new_file;
            }
            else
            {
                $dump_files=array();
                $path=$this->task['options']['dir'].'/';

                foreach ($files as $file)
                {
                    $dump_files[]=$path.$file;
                }
            }

            return $dump_files;
        }
        else
        {
            return array();
        }

    }

    public function add_mysql_dump_files($name_file_name)
    {
        if($this->current_job!==false)
        {
            $this->task['jobs'][$this->current_job]['mysql_dump_files'][]=$name_file_name;
            $this->task['jobs'][$this->current_job]['mysql_file_index']++;
            $this->update_task();
        }
    }

    public function get_file_index()
    {
        if($this->current_job!==false)
        {
            return $this->task['jobs'][$this->current_job]['file_index'];
        }
        else
        {
            return 1;
        }
    }

    public function get_zipped_file_index()
    {
        if($this->current_job!==false)
        {
            return $this->task['jobs'][$this->current_job]['index'];
        }
        else
        {
            return 0;
        }
    }

    public function update_zipped_file_index($index)
    {
        if($this->current_job!==false)
        {
            $this->task['jobs'][$this->current_job]['index']=$index;
            $this->task['status']['resume_count']=0;
            $this->update_task();
        }
    }

    public function update_merge_zipped_file_index($index,$file,$json)
    {
        if($this->current_job!==false)
        {
            $this->task['jobs'][$this->current_job]['index']=$index;
            $this->task['status']['resume_count']=0;
            $this->task['jobs'][$this->current_job]['child_file'][$file]=$json;
            $this->update_task();
        }
    }

    public function zip_mysql_dump_files($files,$tables,$find_zero_date=false)
    {
        foreach ($files as $file)
        {
            $json['files'][]=basename($file);
        }
        $json['tables']=$tables;
        $json=$this->get_json_info('backup_db',$json);
        $max_zip_file_size= $this->task['setting']['max_file_size']*1024*1024;
        $path=$this->task['options']['dir'].'/';

        $zip_method=isset($this->task['setting']['zip_method'])?$this->task['setting']['zip_method']:'ziparchive';
        $zip=new WPvivid_Zip_Addon($zip_method);

        $zip_file_name=$path.$this->get_zip_file('backup_db');

        $numItems = count($files);
        $i = 0;

        if($this->task['options']['encrypt_db']==1)
        {
            $crypt=new WPvivid_Crypt_File($this->task['options']['encrypt_db_password']);
        }

        foreach ($files as $file)
        {
            if($this->task['options']['encrypt_db']==1)
            {
                $zip_sql_file_name=$file.'.zip';
                $ret=$zip->add_file($zip_sql_file_name,$file,basename($file),dirname($file));
                if($ret['result']!='success')
                {
                    return $ret;
                }
                $ret=$crypt->encrypt($zip_sql_file_name);
                if($ret['result']!='success')
                {
                    return $ret;
                }
                $ret=$zip->add_file($zip_file_name, $ret['file_path'],basename( $ret['file_path']),dirname($ret['file_path']));
                if($ret['result']!='success')
                {
                    return $ret;
                }
            }
            else
            {
                $ret=$zip->add_file($zip_file_name,$file,basename($file),dirname($file));
                if($ret['result']!='success')
                {
                    return $ret;
                }
            }

            if(++$i === $numItems)
            {
                continue;
            }

            if($max_zip_file_size !== 0 && (filesize($zip_file_name)>$max_zip_file_size))
            {
                $this->update_zip_file(basename($zip_file_name),1,$json);
                $zip_file_name=$path.$this->add_zip_file('backup_db');
            }
        }

        if($find_zero_date)
        {
            $json['find_zero_date']=1;
        }

        $this->update_zip_file(basename($zip_file_name),1,$json);
        $ret['result']='success';
        return $ret;
    }

    public function update_current_sub_job($jobs)
    {
        if($this->current_job!==false)
        {
            if($this->task['jobs'][$this->current_job]['backup_type']=='backup_additional_db')
            {
                if($this->current_db!==false)
                {
                    $this->task['jobs'][$this->current_job][$this->current_db]['sub_jobs']=$jobs;
                    $this->update_task();
                }
            }
            else
            {
                $this->task['jobs'][$this->current_job]['sub_jobs']=$jobs;
                $this->update_task();
            }
        }
    }

    public function get_current_sub_job()
    {
        if($this->current_job!==false)
        {
            if($this->task['jobs'][$this->current_job]['backup_type']=='backup_additional_db')
            {
                if($this->current_db!==false)
                {
                    return $this->task['jobs'][$this->current_job][$this->current_db]['sub_jobs'];
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return $this->task['jobs'][$this->current_job]['sub_jobs'];
            }
        }
        else
        {
            return false;
        }
    }

    public function init_db_backup_setting()
    {
        global $wpdb;

        $dump_setting['database'] = DB_NAME;
        $dump_setting['host'] = DB_HOST;
        $dump_setting['user'] = DB_USER;
        $dump_setting['pass'] = DB_PASSWORD;

        if(is_multisite()&&isset($this->task['options']['site_id']))
        {
            $dump_setting['site_url']=get_site_url($this->task['options']['site_id']);
            $dump_setting['home_url']=get_home_url($this->task['options']['site_id']);
        }
        else
        {
            $dump_setting['site_url']=get_site_url();
            $dump_setting['home_url']=get_home_url();
        }

        $dump_setting['content_url']=content_url();

        if(is_multisite()&&isset($this->task['options']['site_id']))
        {
            $dump_setting['prefix']=$wpdb->get_blog_prefix($this->task['options']['site_id']);
        }
        else
        {
            if (is_multisite() && !defined('MULTISITE'))
            {
                $dump_setting['prefix'] = $wpdb->base_prefix;
            } else {
                $dump_setting['prefix'] = $wpdb->get_blog_prefix(0);
            }
        }

        $db_connect_method = isset($this->task['setting']['db_connect_method']) ? $this->task['setting']['db_connect_method'] : 'wpdb';
        if ($db_connect_method === 'wpdb')
        {
            $dump_setting['db_connect_method']='wpdb';
        }
        else
        {
            $dump_setting['db_connect_method']='mysql';
        }

        $dump_setting['file_prefix']=$this->task['options']['file_prefix'];
        $dump_setting['path']=$this->task['options']['dir'];
        $dump_setting['max_backup_table']=$this->task['setting']['max_backup_table'];
        $dump_setting['max_file_size']=$this->task['setting']['max_sql_file_size']*1024*1024;
        $dump_setting['backup_database_use_primary_key']=$this->task['setting']['backup_database_use_primary_key'];

        $dump_setting['exclude-tables']=isset($this->task['options']['exclude-tables'])?$this->task['options']['exclude-tables']:array();
        $dump_setting['include-tables']=isset($this->task['options']['include-tables'])?$this->task['options']['include-tables']:array();
        if(is_multisite()&&isset($this->task['options']['site_id']))
        {
            $main_site_id=get_main_site_id();
            if(($main_site_id !== intval($this->task['options']['site_id'])) && ($wpdb->get_blog_prefix($this->task['options']['site_id']) !== $wpdb->get_blog_prefix(0)))
            {
                $dump_setting['include-tables'][]=$wpdb->get_blog_prefix(0).'users';
                $dump_setting['include-tables'][]=$wpdb->get_blog_prefix(0).'usermeta';
            }
        }

        return $dump_setting;
    }

    public function update_status($status)
    {
        $this->task['status']['str']=$status;
        $this->task['status']['run_time']=time();
        WPvivid_Setting::update_task($this->task_id,$this->task);
    }

    public function get_status()
    {
        return $this->task['status'];
    }

    public function set_time_limit()
    {
        //max_execution_time
        @set_time_limit( $this->task['setting']['max_execution_time']);
        $this->task['status']['timeout']=time();
        $this->update_task();
    }

    public function get_time_limit()
    {
        return $this->task['setting']['max_execution_time'];
    }

    public function get_max_resume_count()
    {
        return $this->task['setting']['max_resume_count'];
    }

    public function update_backup_task_status($reset_start_time=false,$status='',$reset_timeout=false,$resume_count=false,$error='')
    {
        $this->task['status']['run_time']=time();
        if($reset_start_time)
            $this->task['status']['start_time']=time();
        if(!empty($status))
        {
            $this->task['status']['str']=$status;
        }
        if($reset_timeout)
            $this->task['status']['timeout']=time();
        if($resume_count!==false)
        {
            $this->task['status']['resume_count']=$resume_count;
        }

        if(!empty($error))
        {
            $this->task['status']['error']=$error;
        }

        $this->update_task();
    }

    public function get_setting()
    {
        return $this->task['setting'];
    }

    public function get_unfinished_job()
    {
        $job=false;
        if(!$this->is_backup_finished())
        {
            $job_key=$this->get_next_job();
            if($job_key!==false)
            {
                $job=$this->task['jobs'][$job_key];
            }
        }

        return $job;
    }

    public function clean_zip_files()
    {
        if($this->current_job!==false)
        {
            if(isset($this->task['jobs'][$this->current_job]['zip_file']))
            {
                $path=$this->task['options']['dir'].'/';

                foreach ($this->task['jobs'][$this->current_job]['zip_file'] as $zip)
                {
                    @unlink($path.$zip['filename']);
                }

                unset($this->task['jobs'][$this->current_job]['zip_file']);
                $this->task['jobs'][$this->current_job]['file_index']=1;
                $this->update_task();
            }
        }
    }

    public function need_upload()
    {
        //remote_options
        if($this->task['options']['remote_options']===false)
        {
            return false;
        }
        else
        {
            $files=$this->get_backup_files();
            if(empty($files))
            {
                return false;
            }
            else
            {
                return true;
            }
        }
    }

    public function is_upload_finished()
    {
        $b_finished=true;

        if(array_key_exists('upload',$this->task['data']))
        {
            foreach ($this->task['data']['upload']['sub_job'] as $upload_job)
            {
                if($upload_job['finished']!=1)
                {
                    $b_finished=false;
                    break;
                }
            }
        }
        else
        {
            $b_finished=false;
        }

        return $b_finished;
    }

    public function get_remote_options()
    {
        return $this->task['options']['remote_options'];
    }

    public function get_backup_files()
    {
        $files=array();
        $root_path=$this -> transfer_path(WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath());
        foreach ($this->task['jobs'] as $job)
        {
            if($job['backup_type']=='backup_merge')
            {
                $files=array();
                if(isset($job['zip_file']))
                {
                    foreach ($job['zip_file'] as $zip)
                    {
                        $files[]=$root_path.'/'.$zip['filename'];
                    }
                }
                break;
            }

            if(isset($job['zip_file']))
            {
                foreach ($job['zip_file'] as $zip)
                {
                    $files[]=$root_path.'/'.$zip['filename'];
                }
            }
        }

        return $files;
    }

    public function do_backup_additional_db()
    {
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-mysqldump-additional-db-method.php';
        $find_zero_date=false;
        foreach ($this->task['options']['additional_database_list'] as $database=> $db_info)
        {
            if($this->is_backup_additional_db_finished($database))
            {
                continue;
            }

            $this->set_additional_db_database($database);

            $this->task['dump_setting']=$this->init_additional_db_backup_setting($database,$db_info);
            if(!class_exists('WPvivid_Mysqldump_Addon'))
            {
                include WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'addons2/backup_pro/class-wpvivid-mysqldump-addon.php';
            }
            $dump = new WPvivid_Mysqldump_Addon($this,$this->task['dump_setting']);
            $dump->connect();
            if(!isset($this->task['jobs'][$this->current_job][$database]))
            {
                $dump->init_job();
            }

            $ret= $dump->start_jobs();
            if($ret['result']!='success')
            {
                return $ret;
            }
            else
            {
                $this->reset_mysql_file_index();
            }

            $find_zero_date=$dump->is_has_zero_date();
        }

        $files=$this->get_mysql_dump_files();

        $ret=$this->zip_additional_db_dump_files($files, $find_zero_date);
        $this->clean_tmp_files();

        return $ret;
    }

    public function init_additional_db_backup_setting($database,$db_info)
    {
        $dump_setting['database'] = $database;
        $dump_setting['host'] = $db_info['db_host'];
        $dump_setting['user'] = $db_info['db_user'];
        $dump_setting['pass'] = $db_info['db_pass'];
        $dump_setting['site_url']=get_site_url();
        $dump_setting['home_url']=get_home_url();
        $dump_setting['content_url']=content_url();
        $dump_setting['is_additional_db']=true;

        $db_connect_method = isset($this->task['setting']['db_connect_method']) ? $this->task['setting']['db_connect_method'] : 'wpdb';
        if ($db_connect_method === 'wpdb')
        {
            $dump_setting['db_connect_method']='wpdb';
        }
        else
        {
            $dump_setting['db_connect_method']='mysql';
        }

        $dump_setting['file_prefix']=$this->task['options']['file_prefix'].'_'.$database;
        $dump_setting['path']=$this->task['options']['dir'];
        $dump_setting['max_file_size']=$this->task['setting']['max_sql_file_size']*1024*1024;
        $dump_setting['backup_database_use_primary_key']=$this->task['setting']['backup_database_use_primary_key'];
        return $dump_setting;
    }

    public function is_backup_additional_db_finished($database)
    {
        if(isset($this->task['jobs'][$this->current_job][$database]))
        {
            return $this->task['jobs'][$this->current_job][$database]['finished'];
        }
        else
        {
            return false;
        }
    }

    public function set_additional_db_database($database)
    {
        $this->current_db=$database;
    }

    public function zip_additional_db_dump_files($files, $find_zero_date=false)
    {
        $json=array();
        foreach ($files as $file)
        {
            $json['files'][]=basename($file);
        }

        $json=$this->get_json_info('backup_additional_db',$json);
        $max_zip_file_size= $this->task['setting']['max_file_size']*1024*1024;
        $path=$this->task['options']['dir'].'/';
        $zip=new WPvivid_Zip_Addon();

        $zip_file_name=$path.$this->get_zip_file('backup_additional_db');

        $numItems = count($files);
        $i = 0;

        if($this->task['options']['encrypt_db']==1)
        {
            $crypt=new WPvivid_Crypt_File($this->task['options']['encrypt_db_password']);
        }

        foreach ($files as $file)
        {
            if($this->task['options']['encrypt_db']==1)
            {
                $zip_sql_file_name=$file.'.zip';
                $ret=$zip->add_file($zip_sql_file_name,$file,basename($file),dirname($file));
                $ret=$crypt->encrypt($zip_sql_file_name);
                $zip->add_file($zip_file_name, $ret['file_path'],basename( $ret['file_path']),dirname($ret['file_path']));
            }
            else
            {
                $zip->add_file($zip_file_name,$file,basename($file),dirname($file));
            }

            if(++$i === $numItems)
            {
                continue;
            }

            if($max_zip_file_size !== 0 && (filesize($zip_file_name)>$max_zip_file_size))
            {
                $this->update_zip_file(basename($zip_file_name),1,$json);
                $zip_file_name=$path.$this->add_zip_file('backup_additional_db');
            }
        }

        if($find_zero_date)
        {
            $json['find_zero_date']=1;
        }
        $this->update_zip_file(basename($zip_file_name),1,$json);
        $ret['result']='success';
        return $ret;
    }

    public function add_new_backup()
    {
        $files=$this->get_backup_files();

        if(empty($files))
        {
            return;
        }

        $backup_data=array();
        $backup_data['type']=$this->task['type'];

        $offset=get_option('gmt_offset');

        $backup_data['create_time']=$this->task['status']['start_time'] + $offset * 60 * 60;
        $backup_data['manual_delete']=0;
        $backup_data['local']['path']=$this->task['options']['backup_dir'];
        $backup_data['compress']['compress_type']='zip';
        $backup_data['save_local']=1;
        if(isset($this->task['options']['backup_prefix']))
        {
            $backup_data['backup_prefix'] = $this->task['options']['backup_prefix'];
        }

        $backup_data['log']=$this->task['options']['log_file_path'];

        $backup_result['result']='success';

        foreach ($files as $file)
        {
            $file_data['file_name'] = basename($file);
            $file_data['size'] = filesize($file);
            $backup_result['files'][] =$file_data;
        }
        $backup_data['backup']=$backup_result;
        $backup_data['remote']=array();
        $backup_data['backup_info']=$this->get_backup_info();
        if(isset($this->task['options']['lock']))
        {
            $backup_data['lock'] = $this->task['options']['lock'];
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup_list->add_local_backup($this->task['id'],$backup_data);

        /*
        $backup_list='wpvivid_backup_list';
        $backup_list=apply_filters('get_wpvivid_backup_list_name',$backup_list,$this->task['id']);
        $list = WPvivid_Setting::get_option($backup_list);
        $list[$this->task['id']]=$backup_data;
        WPvivid_Setting::update_option($backup_list,$list);
        */
    }

    public function add_exist_backup($backup_id,$type='Common')
    {
        $files=$this->get_backup_files();

        if(empty($files))
        {
            return;
        }
        $backup_list=new WPvivid_New_BackupList();
        $backup_data=$backup_list->get_local_backup($backup_id);
        $backup_result['files']=array();
        foreach ($files as $file)
        {
            $file_data['file_name'] = basename($file);
            $file_data['size'] = filesize($file);
            $backup_result['files'][] =$file_data;
        }
        $backup_data['backup']['files']=array_merge($backup_data['backup']['files'],$backup_result['files']);
        $backup_list->add_local_backup($this->task['id'],$backup_data);
    }

    public function set_remote_lock()
    {
        $backup_lock=get_option('wpvivid_remote_backups_lock');
        if($backup_lock !== false)
        {
            $backup_id = $this->task['id'];
            if(isset($this->task['options']['lock']))
            {
                $lock = $this->task['options']['lock'];
            }
            else
            {
                $lock = 0;
            }
            if($lock)
            {
                $backup_lock[$backup_id]=1;
            }
            else {
                unset($backup_lock[$backup_id]);
            }
            update_option('wpvivid_remote_backups_lock',$backup_lock,'no');
        }
    }

    public function add_new_remote_backup()
    {
        $files=$this->get_backup_files();

        if(empty($files))
        {
            return;
        }

        if(isset($this->task['options']['export'])&&$this->task['options']['export']=='auto_migrate')
        {
            return;
        }

        $backup_data=array();
        $backup_data['type']=$this->task['type'];
        $offset=get_option('gmt_offset');

        $backup_data['create_time']=$this->task['status']['start_time'] + $offset * 60 * 60;
        $backup_data['manual_delete']=0;
        $backup_data['local']['path']=$this->task['options']['backup_dir'];
        $backup_data['compress']['compress_type']='zip';
        $backup_data['save_local']=1;
        if(isset($this->task['options']['backup_prefix']))
        {
            $backup_data['backup_prefix'] = $this->task['options']['backup_prefix'];
        }

        $backup_data['log']=$this->task['options']['log_file_path'];

        $backup_result['result']='success';

        foreach ($files as $file)
        {
            $file_data['file_name'] = basename($file);
            $file_data['size'] = filesize($file);
            $backup_result['files'][] =$file_data;
        }
        $backup_data['backup']=$backup_result;
        $remote_options=$this->get_remote_options();
        $backup_data['remote']=$remote_options;
        $backup_data['backup_info']=$this->get_backup_info();
        $backup_data['backup_info_file']=$this->task['options']['file_prefix'].'_backup_info.json';
        if(isset($this->task['options']['lock']))
        {
            $backup_data['lock'] = $this->task['options']['lock'];
        }

        $backup_list=new WPvivid_New_BackupList();

        foreach ($remote_options as $remote_id=>$remote_option)
        {
            $backup_data['remote']=array();
            $backup_data['remote'][$remote_id]=$remote_option;
            $backup_list->add_remote_backup($remote_id,$this->task['id'],$backup_data);
        }

        /*
        $backup_list='wpvivid_backup_list';
        $backup_list=apply_filters('get_wpvivid_backup_list_name',$backup_list,$this->task['id']);
        $list = WPvivid_Setting::get_option($backup_list);
        $list[$this->task['id']]=$backup_data;
        WPvivid_Setting::update_option($backup_list,$list);
        */
    }

    public function add_exist_remote_backup($backup_id)
    {
        $files=$this->get_backup_files();

        if(empty($files))
        {
            return;
        }
        $backup_list=new WPvivid_New_BackupList();
        $remote_options=$this->get_remote_options();
        foreach ($remote_options as $remote_id=>$remote_option)
        {
            $backup_data=$backup_list->get_remote_backup($remote_id,$backup_id);

            $backup_result['files']=array();
            foreach ($files as $file)
            {
                $file_data['file_name'] = basename($file);
                $file_data['size'] = filesize($file);
                $backup_result['files'][] =$file_data;
            }
            $backup_data['backup']['files']=array_merge($backup_data['backup']['files'],$backup_result['files']);

            $backup_list->add_remote_backup($remote_id,$this->task['id'],$backup_data);
        }
    }

    public function clean_backup()
    {
        if(empty($this->task_id))
        {
            return;
        }

        $path = $this->task['options']['dir'];
        $handler=opendir($path);
        if($handler!==false)
        {
            while(($filename=readdir($handler))!==false)
            {
                if(preg_match('#'.$this->task_id.'#',$filename) || preg_match('#'.apply_filters('wpvivid_fix_wpvivid_free', $this->task_id).'#',$filename))
                {
                    @unlink($path.'/'.$filename);
                }
            }
            @closedir($handler);
        }
    }

    public function get_backup_task_info()
    {
        $list_tasks['status']=$this->task['status'];
        $list_tasks['is_canceled']=$this->is_task_canceled();
        $list_tasks['data']=$this->get_backup_tasks_progress();
        //
        $list_tasks['task_info']['need_next_schedule']=false;
        if($list_tasks['status']['str']=='running'||$list_tasks['status']['str']=='no_responds')
        {
            if($list_tasks['data']['running_stamp']>180)
            {
                $list_tasks['task_info']['need_next_schedule'] = true;
            }
            else{
                $list_tasks['task_info']['need_next_schedule'] = false;
            }
        }

        $list_tasks['task_info']['display_estimate_backup'] = '';

        $list_tasks['task_info']['backup_percent']=$list_tasks['data']['progress'].'%';
        //
        $list_tasks['task_info']['db_size']=0;
        $list_tasks['task_info']['file_size']=0;

        $list_tasks['task_info']['descript']='';
        $list_tasks['task_info']['css_btn_cancel']='pointer-events: auto; opacity: 1;';
        $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
        $list_tasks['task_info']['total'] = 'N/A';
        $list_tasks['task_info']['upload'] = 'N/A';
        $list_tasks['task_info']['speed'] = 'N/A';
        $list_tasks['task_info']['network_connection'] = 'N/A';

        $list_tasks['task_info']['need_update_last_task']=false;

        $list_tasks['task_info']['progress_text']=$list_tasks['data']['descript'];
        $list_tasks['task_info']['progress_text2']="Running time: ".$list_tasks['data']['running_time'];

        if($list_tasks['status']['str']=='ready')
        {
            $list_tasks['task_info']['descript']=__('Ready to backup. Progress: 0%, running time: 0second.','wpvivid-backuprestore');
            $list_tasks['task_info']['css_btn_cancel']='pointer-events: none; opacity: 0.4;';
            $list_tasks['task_info']['css_btn_log']='pointer-events: none; opacity: 0.4;';

            $list_tasks['task_info']['progress_text']="Ready to backup. Progress: 0%";
            $list_tasks['task_info']['progress_text2']="Running time: 0 second.";
        }
        else if($list_tasks['status']['str']=='running')
        {
            if($list_tasks['is_canceled'] == false)
            {
                if($list_tasks['data']['type'] == 'upload')
                {
                    if(isset($list_tasks['data']['upload_data']) && !empty($list_tasks['data']['upload_data']))
                    {
                        $descript = $list_tasks['data']['upload_data']['descript'];
                        $offset = $list_tasks['data']['upload_data']['offset'];
                        $current_size = $list_tasks['data']['upload_data']['current_size'];
                        $last_time = $list_tasks['data']['upload_data']['last_time'];
                        $last_size = $list_tasks['data']['upload_data']['last_size'];
                        $speed = ($offset - $last_size) / (time() - $last_time);
                        $speed /= 1000;
                        $speed = round($speed, 2);
                        $speed .= 'kb/s';
                        if(!empty($current_size)) {
                            $list_tasks['task_info']['total'] = size_format($current_size,2);
                        }
                        if(!empty($offset)) {
                            $list_tasks['task_info']['upload'] = size_format($offset, 2);
                        }
                    }
                    else{
                        $descript = 'Start uploading.';
                        $speed = '0kb/s';
                        $list_tasks['task_info']['total'] = 'N/A';
                        $list_tasks['task_info']['upload'] = 'N/A';
                    }

                    $list_tasks['task_info']['speed'] = $speed;
                    $list_tasks['task_info']['descript'] = $descript.' '.__('Progress: ', 'wpvivid-backuprestore') . $list_tasks['task_info']['backup_percent'] . ', ' . __('running time: ', 'wpvivid-backuprestore') . $list_tasks['data']['running_time'];

                    $time_spend=time()-$list_tasks['status']['run_time'];
                    if($time_spend>30)
                    {
                        $list_tasks['task_info']['network_connection']='Retrying';
                    }
                    else
                    {
                        $list_tasks['task_info']['network_connection']='OK';
                    }
                }
                else {
                    $list_tasks['task_info']['descript'] = $list_tasks['data']['descript'] . ' '. __('Progress: ', 'wpvivid-backuprestore') . $list_tasks['task_info']['backup_percent'] . ', '. __('running time: ', 'wpvivid-backuprestore') . $list_tasks['data']['running_time'];
                }
                $list_tasks['task_info']['css_btn_cancel']='pointer-events: auto; opacity: 1;';
                $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
            }
            else{
                $list_tasks['task_info']['descript']=__('The backup will be canceled after backing up the current chunk ends.','wpvivid-backuprestore');
                $list_tasks['task_info']['css_btn_cancel']='pointer-events: none; opacity: 0.4;';
                $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
            }
        }
        else if($list_tasks['status']['str']=='wait_resume')
        {
            $list_tasks['task_info']['descript']='Task '.$this->task_id.' timed out, backup task will retry in '.$list_tasks['data']['next_resume_time'].' seconds, retry times: '.$list_tasks['status']['resume_count'].'.';
            $list_tasks['task_info']['css_btn_cancel']='pointer-events: auto; opacity: 1;';
            $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
        }
        else if($list_tasks['status']['str']=='no_responds')
        {
            if($list_tasks['is_canceled'] == false)
            {
                $list_tasks['task_info']['descript']='Task , '.$list_tasks['data']['doing'].' is not responding. Progress: '.$list_tasks['task_info']['backup_percent'].', running time: '.$list_tasks['data']['running_time'];
                $list_tasks['task_info']['css_btn_cancel']='pointer-events: auto; opacity: 1;';
                $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
            }
            else{
                $list_tasks['task_info']['descript']=__('The backup will be canceled after backing up the current chunk ends.','wpvivid-backuprestore');
                $list_tasks['task_info']['css_btn_cancel']='pointer-events: none; opacity: 0.4;';
                $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
            }
        }
        else if($list_tasks['status']['str']=='completed')
        {
            $list_tasks['task_info']['descript']='Task '.$this->task_id.' completed.';
            $list_tasks['task_info']['css_btn_cancel']='pointer-events: auto; opacity: 1;';
            $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
            $list_tasks['task_info']['need_update_last_task']=true;
        }
        else if($list_tasks['status']['str']=='error')
        {
            $list_tasks['task_info']['descript']='Backup error: '.$list_tasks['status']['error'];
            $list_tasks['task_info']['css_btn_cancel']='pointer-events: auto; opacity: 1;';
            $list_tasks['task_info']['css_btn_log']='pointer-events: auto; opacity: 1;';
            $list_tasks['task_info']['need_update_last_task']=true;
        }

        return $list_tasks;
    }

    public function is_task_canceled()
    {
        $file_name=$this->task['options']['file_prefix'];

        $file =$this->task['options']['dir'].'/'. $file_name . '_cancel';

        if (file_exists($file))
        {
            return true;
        }
        return false;
    }

    public function check_cancel_backup()
    {
        if($this->is_task_canceled())
        {
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup cancelled.','notice');

            $this->update_status('cancel');
            $this->clean_tmp_files();

            $tasks=WPvivid_Setting::get_option('wpvivid_clean_task_ex');
            $tasks[$this->task_id]=$this->task;
            WPvivid_Setting::update_option('wpvivid_clean_task_ex',$tasks);

            $resume_time=time()+60;

            $b=wp_schedule_single_event($resume_time,'wpvivid_clean_backup_data_event',array($this->task_id));

            if($b===false)
            {
                $timestamp = wp_next_scheduled('wpvivid_clean_backup_data_event',array($this->task_id));

                if($timestamp!==false)
                {
                    $resume_time=max($resume_time,$timestamp+10*60+10);
                    wp_schedule_single_event($resume_time,'wpvivid_clean_backup_data_event',array($this->task_id));
                }
            }

            $timestamp =wp_next_scheduled('wpvivid_task_monitor_event_ex',array($this->task_id));
            if($timestamp!==false)
            {
                wp_unschedule_event($timestamp,'wpvivid_task_monitor_event_ex',array($this->task_id));
            }
            wp_cache_flush();
            WPvivid_taskmanager::delete_task($this->task_id);
            wp_cache_flush();

            $this->wpvivid_check_clear_litespeed_rule();

            return true;
        }
        else
        {
            return false;
        }
    }

    public function get_backup_tasks_progress()
    {
        $current_time=date("Y-m-d H:i:s");
        $create_time=date("Y-m-d H:i:s",$this->task['status']['start_time']);
        $time_diff=strtotime($current_time)-strtotime($create_time);
        $running_time='';
        if(date("G",$time_diff) > 0){
            $running_time .= date("G",$time_diff).' hour(s)';
        }
        if(intval(date("i",$time_diff)) > 0){
            $running_time .= intval(date("i",$time_diff)).' min(s)';
        }
        if(intval(date("s",$time_diff)) > 0){
            $running_time .= intval(date("s",$time_diff)).' second(s)';
        }
        else
        {
            $running_time .= '0 second(s)';
        }
        $next_resume_time=$this->get_next_resume_time();

        $ret['type']=$this->task['data']['doing'];
        $ret['progress']=$this->task['data'][$ret['type']]['progress'];
        $ret['doing']=$this->task['data'][$ret['type']]['doing'];
        if(isset($this->task['data'][$ret['type']]['sub_job'][$ret['doing']]['progress']))
        {
            $ret['descript']=__($this->task['data'][$ret['type']]['sub_job'][$ret['doing']]['progress'], 'wpvivid-backuprestore');

        }
        else
        {
            $ret['descript']='';
        }
        if(isset($this->task['data'][$ret['type']]['sub_job'][$ret['doing']]['upload_data']))
            $ret['upload_data']=$this->task['data'][$ret['type']]['sub_job'][$ret['doing']]['upload_data'];
        $this->task['data'][$ret['type']]['sub_job'][$ret['doing']]['upload_data']=false;
        $ret['running_time']=$running_time;
        $ret['running_stamp']=$time_diff;
        $ret['next_resume_time']=$next_resume_time;
        return $ret;
    }

    public function get_next_resume_time()
    {
        $timestamp=wp_next_scheduled(WPVIVID_RESUME_SCHEDULE_EVENT,array($this->task_id));
        if($timestamp!==false)
        {
            return $timestamp-time();
        }
        else
        {
            return false;
        }
    }

    public function clear_cache()
    {
        $path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        $handler=opendir($path);
        if($handler!==false)
        {
            while(($filename=readdir($handler))!==false)
            {
                if(preg_match('#pclzip-.*\.tmp#', $filename)){
                    @unlink($path.DIRECTORY_SEPARATOR.$filename);
                }
                if(preg_match('#pclzip-.*\.gz#', $filename)){
                    @unlink($path.DIRECTORY_SEPARATOR.$filename);
                }
            }
            @closedir($handler);
        }
    }

    public function is_save_local()
    {
        return isset($this->task['options']['save_local'])?$this->task['options']['save_local']:false;
    }

    public function clean_local_files()
    {
        $path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        $handler=opendir($path);
        if($handler!==false)
        {
            while(($filename=readdir($handler))!==false)
            {
                if(preg_match('#'.$this->task_id.'#',$filename) || preg_match('#'.apply_filters('wpvivid_fix_wpvivid_free', $this->task_id).'#',$filename))
                {
                    @unlink($path.DIRECTORY_SEPARATOR.$filename);
                }
            }
            @closedir($handler);
        }
    }

    public function get_backup_jobs()
    {
        return $this->task['jobs'];
    }

    public function get_file_json($file)
    {
        if(!class_exists('WPvivid_ZipClass'))
            include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
        $zip=new WPvivid_ZipClass();

        $ret=$zip->get_json_data($file);
        if($ret['result'] === WPVIVID_SUCCESS)
        {
            $json=$ret['json_data'];
            $json = json_decode($json, 1);
            if (is_null($json))
            {
                return false;
            } else {
                return $json;
            }
        }
        else
        {
            return array();
        }
    }
    //adaptive settings
    public function check_memory_limit()
    {
        $current_memory_limit=$this->task['setting']['memory_limit'];
        $current_memory_int = (int) filter_var($current_memory_limit, FILTER_SANITIZE_NUMBER_INT);
        if($current_memory_int<512)
        {
            $this->task['setting']['memory_limit']='512M';
            $this->update_task();
            return true;
        }
        else if($current_memory_int<1024)
        {
            $this->task['setting']['memory_limit']=($current_memory_int+100).'M';
            $this->update_task();
            return true;
        }
        else
        {
            return false;
        }
    }

    public function check_timeout()
    {
        $job=$this->get_unfinished_job();
        if($job!==false)
        {
            if($job['backup_type']=='backup_db'||$job['backup_type']=='backup_additional_db')
            {
                if($this->task['setting']['max_sql_file_size']>200)
                {
                    $this->task['setting']['max_sql_file_size']=200;
                }
                else
                {
                    $this->task['setting']['max_sql_file_size']=max(10,$this->task['setting']['max_sql_file_size']-50);
                }
                $this->update_task();
            }
            else
            {
                //if($this->task['setting']['compress_file_use_cache']==false)
                //{
                //    $this->task['setting']['compress_file_use_cache']=true;
                //}

                if($this->task['setting']['compress_file_count']>=1000)
                {
                    $this->task['setting']['compress_file_count']=800;
                }
                else if($this->task['setting']['compress_file_count']>=800)
                {
                    $this->task['setting']['compress_file_count']=500;
                }
                else if($this->task['setting']['compress_file_count']>=500)
                {
                    $this->task['setting']['compress_file_count']=300;
                }
                else
                {
                    $this->task['setting']['compress_file_count']=100;
                }

                if($this->task['setting']['max_file_size']>200)
                {
                    $this->task['setting']['max_file_size']=200;
                }

                if($this->task['setting']['exclude_file_size']==0)
                {
                    $this->task['setting']['exclude_file_size']=200;
                }
                $this->update_task();
            }
        }
    }

    public function check_execution_time()
    {
        $this->task['setting']['max_execution_time']=$this->task['setting']['max_execution_time']+120;
        $this->update_task();
    }

    public function check_timeout_backup_failed()
    {
        //$job=$this->get_unfinished_job();
        //if($job!==false)
        //{
            //if($job['backup_type']=='backup_merge')
            //{
                //$this->task['setting']['is_merge']=false;
            //}
        //}

        $max_resume_count=$this->get_max_resume_count();
        $status=$this->get_status();
        $status['resume_count']++;
        if($status['resume_count']>$max_resume_count)
        {
            $this->task['setting']['max_resume_count']=max(20,$this->task['setting']['max_resume_count']+3);
            $this->update_task();
        }
    }

    public function update_incremental_backup_data()
    {
        if(!isset($this->task['incremental']))
        {
            return;
        }
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

        $files=$this->get_backup_files();
        if(!empty($files))
        {
            $data['versions']['version']++;
            $data['versions']['skip_files_time']=time();
            $data['versions']['backup_time']=time();
        }

        $incremental_backup_data[$this->task['schedule_id']][$backup_files]=$data;
        WPvivid_Setting::update_option('wpvivid_incremental_backup_data',$incremental_backup_data);
    }

    public function update_schedule_last_backup_time()
    {
        if($this->task['type']==='Cron')
        {
            $task_msg = WPvivid_taskmanager::get_task($this->task['id']);
            $schedule_id = false;
            $schedule_id = get_option('wpvivid_current_schedule_id', $schedule_id);
            if(isset($schedule_id))
            {
                //update last backup time
                do_action('wpvivid_update_schedule_last_time_addon', $schedule_id, $task_msg['status']['start_time']);
            }

            $remote_options = WPvivid_taskmanager::get_task_options($this->task['id'], 'remote_options');
            if($remote_options != false)
            {
                $backup_list=new WPvivid_New_BackupList();
                $backup_list->delete_backup($this->task['id']);
                WPvivid_Setting::update_option('wpvivid_backup_remote_need_update', true);
            }
            update_option('wpvivid_general_schedule_data', $task_msg, 'no');
        }
        else if($this->task['type']==='Incremental')
        {
            if($this->task['incremental_backup_files']==='db')
            {
                WPvivid_Setting::update_option('wpvivid_incremental_database_last_msg',$this->task);
            }
            else if($this->task['incremental_backup_files']==='files')
            {
                if(isset($this->task['options']['incremental_options']['versions']['version']) && $this->task['options']['incremental_options']['versions']['version'] > 0)
                {
                    WPvivid_Setting::update_option('wpvivid_incremental_backup_last_msg',$this->task);
                }
                else
                {
                    WPvivid_Setting::update_option('wpvivid_full_backup_last_msg',$this->task);
                }
            }
        }
    }

    public function wpvivid_check_add_litespeed_server()
    {
        $litespeed=false;
        if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] )
        {
            $litespeed=true;
        }
        elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0 ) {
            $litespeed=true;
        }
        elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
            $litespeed=true;
        }

        if($litespeed)
        {
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('LiteSpeed Server.','notice');

            if ( ! function_exists( 'got_mod_rewrite' ) )
            {
                require_once ABSPATH . 'wp-admin/includes/misc.php';
            }

            if(function_exists('insert_with_markers'))
            {
                if(!function_exists('get_home_path'))
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                $home_path     = get_home_path();
                $htaccess_file = $home_path . '.htaccess';

                if ( ( ! file_exists( $htaccess_file ) && is_writable( $home_path ) ) || is_writable( $htaccess_file ) )
                {
                    if ( got_mod_rewrite() )
                    {
                        $line[]='<IfModule Litespeed>';
                        $line[]='RewriteEngine On';
                        $line[]='RewriteRule .* - [E=noabort:1, E=noconntimeout:1]';
                        $line[]='</IfModule>';
                        insert_with_markers($htaccess_file,'WPvivid Rewrite Rule for LiteSpeed',$line);
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Add LiteSpeed Rule','notice');
                    }
                    else
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('mod_rewrite not found.','notice');
                    }
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('.htaccess file not exists or not writable.','notice');
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('insert_with_markers function not exists.','notice');
            }
        }
    }

    public function wpvivid_check_clear_litespeed_rule()
    {
        $litespeed=false;
        if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] )
        {
            $litespeed=true;
        }
        elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0 ) {
            $litespeed=true;
        }
        elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
            $litespeed=true;
        }

        if($litespeed)
        {
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('LiteSpeed Server.','notice');

            if ( ! function_exists( 'got_mod_rewrite' ) )
            {
                require_once ABSPATH . 'wp-admin/includes/misc.php';
            }

            if(function_exists('insert_with_markers'))
            {
                if(!function_exists('get_home_path'))
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                $home_path     = get_home_path();
                $htaccess_file = $home_path . '.htaccess';

                if ( ( ! file_exists( $htaccess_file ) && is_writable( $home_path ) ) || is_writable( $htaccess_file ) )
                {
                    if ( got_mod_rewrite() )
                    {
                        insert_with_markers($htaccess_file,'WPvivid Rewrite Rule for LiteSpeed','');
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Clear LiteSpeed Rule','notice');
                    }
                    else
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('mod_rewrite not found.','notice');
                    }
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('.htaccess file not exists or not writable.','notice');
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('insert_with_markers function not exists.','notice');
            }
        }
    }

    public function wpvivid_disable_litespeed_cache_for_backup()
    {
        if (defined('LSCWP_V'))
        {
            do_action( 'litespeed_disable_all', 'stop for backup' );
        }
    }
}

class WPvivid_New_Backup_Item
{
    private $config;

    public function __construct($options)
    {
        $this->config=$options;
    }

    public function get_backup_type()
    {
        return $this->config['type'];
    }

    public function get_backup_path($file_name)
    {
        $path = $this->get_local_path() . $file_name;

        if (file_exists($path)) {
            return $path;
        }
        else{
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$file_name;
        }
        return $path;
    }

    public function get_download_local_path()
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        return $path;
    }

    public function get_files($has_dir=true)
    {
        $files=array();
        if(isset($this->config['backup']['files']))
        {
            //file_name
            foreach ($this->config['backup']['files'] as $file)
            {
                if($has_dir)
                    $files[]=$this->get_backup_path($file['file_name']);
                else
                    $files[]=$file['file_name'];
            }
        }
        else{
            if(isset($this->config['backup']['data']['meta']['files']))
            {
                foreach ($this->config['backup']['data']['meta']['files'] as $file)
                {
                    if($has_dir)
                        $files[]=$this->get_backup_path($file['file_name']);
                    else
                        $files[]=$file['file_name'];
                }
            }
        }
        return $files;
    }

    public function is_lock()
    {
        if(isset($this->config['lock']))
        {
            return $this->config['lock'];
        }
        else{
            return false;
        }
    }

    public function check_backup_files()
    {
        global $wpvivid_plugin;

        $b_has_data=false;
        $tmp_data=array();
        if(isset($this->config['backup']['files']))
        {
            $b_has_data = true;
            $tmp_data = $this->config['backup']['files'];
        }
        else if(isset($this->config['backup']['data']['meta']['files'])){
            $b_has_data = true;
            $tmp_data = $this->config['backup']['data']['meta']['files'];
        }

        if($b_has_data)
        {
            $b_need_download=false;
            $b_not_found=false;
            $b_test=false;
            foreach ($tmp_data as $file)
            {
                $need_download=false;
                $path=$this->get_backup_path($file['file_name']);
                if(file_exists($path))
                {
                    if(filesize($path) == $file['size'])
                    {
                        if($wpvivid_plugin->wpvivid_check_zip_valid())
                        {
                            $res = TRUE;
                        }
                        else{
                            $res = FALSE;
                        }
                    }
                    else {
                        $res = FALSE;
                    }
                    if ($res !== TRUE)
                    {
                        $need_download=true;
                    }
                }
                else
                {
                    $b_test=true;
                    $need_download=true;
                }

                if($need_download)
                {
                    if(empty($this->config['remote']))
                    {
                        $b_not_found=true;
                        $ret['files'][$file['file_name']]['status']='file_not_found';
                        $ret['files'][$file['file_name']]['size']=$file['size'];
                    }
                    else
                    {
                        $b_need_download=true;
                        WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                        $ret['files'][$file['file_name']]['status']='need_download';
                        $ret['files'][$file['file_name']]['size']=$file['size'];
                    }
                }
            }

            if($b_not_found)
            {
                $ret['result']=WPVIVID_FAILED;
                if($b_test)
                    $ret['error']='Backup files doesn\'t exist. Restore failed.';
                else
                    $ret['error']='Backup doesn\'t exist in both web server and remote storage. Restore failed.';
            }
            else if($b_need_download)
            {
                $ret['result']='need_download';
            }
            else
            {
                $ret['result']=WPVIVID_SUCCESS;
            }
        }
        else
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']='Unknown error.';
        }

        return $ret;
    }

    public function check_migrate_file()
    {
        if(isset($this->config['backup']['files']))
        {
            $tmp_data = $this->config['backup']['files'];
            if(!class_exists('WPvivid_ZipClass'))
                include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
            $zip=new WPvivid_ZipClass();

            foreach ($tmp_data as $file)
            {
                $path=$this->get_backup_path($file['file_name']);
                if(file_exists($path))
                {
                    $ret=$zip->get_json_data($path);
                    if($ret['result'] === WPVIVID_SUCCESS) {
                        $json=$ret['json_data'];
                        $json = json_decode($json, 1);
                        if (!is_null($json)) {
                            if (isset($json['home_url']) && home_url() != $json['home_url']) {
                                return 1;
                            }
                        }
                        else{
                            return 0;
                        }
                    }
                    elseif($ret['result'] === WPVIVID_FAILED){
                        return 0;
                    }
                }
            }
            return 0;
        }
        else
        {
            return 0;
        }

    }

    public function is_display_migrate_option(){
        if(isset($this->config['backup']['files']))
        {
            $tmp_data = $this->config['backup']['files'];
            if(!class_exists('WPvivid_ZipClass'))
                include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
            $zip=new WPvivid_ZipClass();

            foreach ($tmp_data as $file)
            {
                $path=$this->get_backup_path($file['file_name']);
                if(file_exists($path))
                {
                    $ret=$zip->get_json_data($path);
                    if($ret['result'] === WPVIVID_SUCCESS) {
                        $json=$ret['json_data'];
                        $json = json_decode($json, 1);
                        if (!is_null($json)) {
                            if (isset($json['home_url'])){
                                return false;
                            }
                            else{
                                return true;
                            }
                        }
                        else{
                            return true;
                        }
                    }
                    elseif($ret['result'] === WPVIVID_FAILED){
                        return true;
                    }
                }
            }
            return true;
        }
        else
        {
            return true;
        }
    }

    public function get_local_path()
    {
        $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$this->config['local']['path'].DIRECTORY_SEPARATOR;
        return $path;
    }

    public function get_local_url()
    {
        $url=content_url().DIRECTORY_SEPARATOR.$this->config['local']['path'].DIRECTORY_SEPARATOR;
        return $url;
    }

    public function get_remote()
    {
        $remote_option=array_shift($this->config['remote']);

        if(is_null($remote_option))
        {
            return false;
        }
        else
        {
            return $remote_option;
        }
    }

    public function get_file_info($file_name)
    {
        if(!class_exists('WPvivid_ZipClass'))
            include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
        $zip=new WPvivid_ZipClass();

        $path=$this->get_backup_path($file_name);

        $ret=$zip->get_json_data($path);
        if($ret['result'] === WPVIVID_SUCCESS) {
            $json=$ret['json_data'];
            $json = json_decode($json, 1);
            if (is_null($json)) {
                return false;
            } else {
                return $json;
            }
        }
        elseif($ret['result'] === WPVIVID_FAILED){
            return false;
        }
    }

    public static function get_backup_file_info($file_name)
    {
        if(!class_exists('WPvivid_ZipClass'))
            include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
        $zip=new WPvivid_ZipClass();
        $ret=$zip->get_json_data($file_name);
        if($ret['result'] === WPVIVID_SUCCESS)
        {
            $json=$ret['json_data'];
            $json = json_decode($json, 1);
            if (is_null($json)) {
                return array('result'=>WPVIVID_FAILED,'error'=>'Failed to decode json');
            } else {
                return array('result'=>WPVIVID_SUCCESS,'json_data'=>$json);
            }
        }
        elseif($ret['result'] === WPVIVID_FAILED){
            return $ret;
        }
    }

    public static function get_backup_files($backup){
        $files=array();
        if(isset($backup['backup']['files'])){
            $files=$backup['backup']['files'];
        }
        else{
            if(isset($backup['backup']['ismerge'])) {
                if ($backup['backup']['ismerge'] == 1) {
                    if(isset($backup['backup']['data']['meta']['files'])){
                        $files=$backup['backup']['data']['meta']['files'];
                    }
                }
            }
        }
        asort($files);
        uasort($files, function ($a, $b) {
            $file_name_1 = $a['file_name'];
            $file_name_2 = $b['file_name'];
            $index_1 = 0;
            if(preg_match('/wpvivid-.*_.*_.*\.part.*\.zip$/', $file_name_1)) {
                if (preg_match('/part.*$/', $file_name_1, $matches)) {
                    $index_1 = $matches[0];
                    $index_1 = preg_replace("/part/","", $index_1);
                    $index_1 = preg_replace("/.zip/","", $index_1);
                }
            }
            $index_2 = 0;
            if(preg_match('/wpvivid-.*_.*_.*\.part.*\.zip$/', $file_name_2)) {
                if (preg_match('/part.*$/', $file_name_2, $matches)) {
                    $index_2 = $matches[0];
                    $index_2 = preg_replace("/part/", "", $index_2);
                    $index_2 = preg_replace("/.zip/", "", $index_2);
                }
            }
            if($index_1 !== 0 && $index_2 === 0){
                return -1;
            }
            if($index_1 === 0 && $index_2 !== 0){
                return 1;
            }
        });
        return $files;
    }

    public function get_download_backup_files($backup_id){
        $ret['result']=WPVIVID_FAILED;
        $data=array();
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        if(!$backup)
        {
            $ret['error']='Backup id not found.';
            return $ret;
        }

        $files=array();
        $files = self::get_backup_files($backup);
        if(empty($files)){
            $ret['error']='Failed to get backup files.';
        }
        else{
            $ret['result']=WPVIVID_SUCCESS;
            $ret['files']=$files;
        }
        return $ret;
    }

    public function get_download_progress($backup_id, $files){
        global $wpvivid_plugin;
        $b_need_download=false;
        $b_not_found=false;
        $file_count=0;
        $file_part_num=1;
        $check_type='';
        foreach ($files as $file)
        {
            $need_download=false;
            $path=$this->get_backup_path($file['file_name']);
            $download_url=content_url().DIRECTORY_SEPARATOR.$this->config['local']['path'].DIRECTORY_SEPARATOR.$file['file_name'];
            if(file_exists($path)) {
                if(filesize($path) == $file['size']){
                    if($wpvivid_plugin->wpvivid_check_zip_valid()) {
                        $res = TRUE;
                    }
                    else{
                        $res = FALSE;
                    }
                }
                else{
                    $res = FALSE;
                }
                if ($res !== TRUE)
                {
                    $need_download=true;
                }
            }
            else {
                $need_download=true;
            }
            if($file_part_num < 10){
                $format_part=sprintf("%02d", $file_part_num);
            }
            else{
                $format_part=$file_part_num;
            }
            if($need_download) {
                if(empty($this->config['remote'])) {
                    $b_not_found=true;
                    $ret['result'] = WPVIVID_SUCCESS;
                    $ret['files'][$file['file_name']]['status']='file_not_found';
                }
                else{
                    $task = WPvivid_taskmanager::get_download_task_v2($file['file_name']);
                    $ret['task']=$task;
                    if ($task === false) {
                        $ret['result'] = WPVIVID_SUCCESS;
                        $ret['files'][$file['file_name']]['status']='need_download';
                        $ret['files'][$file['file_name']]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                                  <span>Part'.$format_part.'</span></br>
                                                                  <span id=\''.$backup_id.'-text-part-'.$file_part_num.'\'><a onclick="wpvivid_prepare_download(\''.$file_part_num.'\', \''.$backup_id.'\', \''.$file['file_name'].'\');" style="cursor: pointer;">Prepare to Download</a></span></br>
                                                                  <div style="width:100%;height:5px; background-color:#dcdcdc;"><div id=\''.$backup_id.'-progress-part-'.$file_part_num.'\' style="background-color:#0085ba; float:left;width:0;height:5px;"></div></div>
                                                                  <span>size:</span><span>'.$wpvivid_plugin->formatBytes($file['size']).'</span>
                                                                  </div>';
                    } else {
                        $ret['result'] = WPVIVID_SUCCESS;
                        if($task['status'] === 'running'){
                            $ret['files'][$file['file_name']]['status'] = 'running';
                            $ret['files'][$file['file_name']]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                                            <span>Part'.$format_part.'</span></br>
                                                                            <span id=\''.$backup_id.'-text-part-'.$file_part_num.'\'><a >Retriving(remote storage to web server)</a></span></br>
                                                                            <div style="width:100%;height:5px; background-color:#dcdcdc;"><div id=\''.$backup_id.'-progress-part-'.$file_part_num.'\' style="background-color:#0085ba; float:left;width:'.$task['progress_text'].'%;height:5px;"></div></div>
                                                                            <span>size:</span><span>'.$wpvivid_plugin->formatBytes($file['size']).'</span>
                                                                            </div>';
                            $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                        }
                        elseif($task['status'] === 'timeout'){
                            $ret['files'][$file['file_name']]['status']='timeout';
                            $ret['files'][$file['file_name']]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                                            <span>Part'.$format_part.'</span></br>
                                                                            <span id=\''.$backup_id.'-text-part-'.$file_part_num.'\'><a onclick="wpvivid_prepare_download(\''.$file_part_num.'\', \''.$backup_id.'\', \''.$file['file_name'].'\');" style="cursor: pointer;">Prepare to Download</a></span></br>
                                                                            <div style="width:100%;height:5px; background-color:#dcdcdc;"><div id=\''.$backup_id.'-progress-part-'.$file_part_num.'\' style="background-color:#0085ba; float:left;width:'.$task['progress_text'].'%;height:5px;"></div></div>
                                                                            <span>size:</span><span>'.$wpvivid_plugin->formatBytes($file['size']).'</span>
                                                                            </div>';
                            $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                            WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                        }
                        elseif($task['status'] === 'completed'){
                            $ret['files'][$file['file_name']]['status']='completed';
                            $ret['files'][$file['file_name']]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                                 <span>Part'.$format_part.'</span></br>
                                                                 <span id=\''.$backup_id.'-text-part-'.$file_part_num.'\'><a onclick="wpvivid_download(\''.$backup_id.'\', \''.$check_type.'\', \''.$file['file_name'].'\');" style="cursor: pointer;">Download</a></span></br>
                                                                 <div style="width:100%;height:5px; background-color:#dcdcdc;"><div id=\''.$backup_id.'-progress-part-'.$file_part_num.'\' style="background-color:#0085ba; float:left;width:100%;height:5px;"></div></div>
                                                                 <span>size:</span><span>'.$wpvivid_plugin->formatBytes($file['size']).'</span>
                                                                 </div>';
                            WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                        }
                        elseif($task['status'] === 'error'){
                            $ret['files'][$file['file_name']]['status']='error';
                            $ret['files'][$file['file_name']]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                                        <span>Part'.$format_part.'</span></br>
                                                                        <span id=\''.$backup_id.'-text-part-'.$file_part_num.'\'><a onclick="wpvivid_prepare_download(\''.$file_part_num.'\', \''.$backup_id.'\', \''.$file['file_name'].'\');" style="cursor: pointer;">Prepare to Download</a></span></br>
                                                                        <div style="width:100%;height:5px; background-color:#dcdcdc;"><div id=\''.$backup_id.'-progress-part-'.$file_part_num.'\' style="background-color:#0085ba; float:left;width:0;height:5px;"></div></div>
                                                                        <span>size:</span><span>'.$wpvivid_plugin->formatBytes($file['size']).'</span>
                                                                        </div>';
                            $ret['files'][$file['file_name']]['error'] = $task['error'];
                            WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                        }
                    }
                }
            }
            else{
                $ret['result'] = WPVIVID_SUCCESS;
                if(WPvivid_taskmanager::get_download_task_v2($file['file_name']))
                    WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                $ret['files'][$file['file_name']]['status']='completed';
                $ret['files'][$file['file_name']]['download_path']=$path;
                $ret['files'][$file['file_name']]['download_url']=$download_url;
                $ret['files'][$file['file_name']]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                                 <span>Part'.$format_part.'</span></br>
                                                                 <span id=\''.$backup_id.'-text-part-'.$file_part_num.'\'><a onclick="wpvivid_download(\''.$backup_id.'\', \''.$check_type.'\', \''.$file['file_name'].'\');" style="cursor: pointer;">Download</a></span></br>
                                                                 <div style="width:100%;height:5px; background-color:#dcdcdc;"><div id=\''.$backup_id.'-progress-part-'.$file_part_num.'\' style="background-color:#0085ba; float:left;width:100%;height:5px;"></div></div>
                                                                 <span>size:</span><span>'.$wpvivid_plugin->formatBytes($file['size']).'</span>
                                                                 </div>';
            }
            $ret['files'][$file['file_name']]['size']=$wpvivid_plugin->formatBytes($file['size']);
            $file_count++;
            $file_part_num++;
        }
        if ($file_count % 2 != 0) {
            $file_count++;
            if($file_count < 10){
                $format_part=sprintf("%02d", $file_count);
            }
            else{
                $format_part=$file_count;
            }
            $ret['place_html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px; color:#cccccc;">
                                   <span>Part'.$format_part.'</span></br>
                                   <span>Download</span></br>
                                   <div style="width:100%;height:5px; background-color:#dcdcdc;"><div style="background-color:#0085ba; float:left;width:0;height:5px;"></div></div>
                                   <span>size:</span><span>0</span>
                                   </div>';
        }
        else{
            $ret['place_html']='';
        }
        return $ret;
    }

    public function cleanup_local_backup()
    {
        $files=array();
        $download_dir=$this->config['local']['path'];
        $file=$this->get_files(false);

        foreach ($file as $filename)
        {
            $files[] = $filename;
        }

        foreach ($files as $file)
        {
            $download_path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$file;
            if (file_exists($download_path))
            {
                @wp_delete_file($download_path);
            }
            else{
                $backup_dir=WPvivid_Setting::get_backupdir();
                $download_path = WP_CONTENT_DIR .DIRECTORY_SEPARATOR . $backup_dir . DIRECTORY_SEPARATOR . $file;
                if (file_exists($download_path))
                {
                    @wp_delete_file($download_path);
                }
            }
        }
    }

    public function cleanup_remote_backup()
    {
        if(!empty($this->config['remote']))
        {
            $files=$this->get_files(false);
            foreach($this->config['remote'] as $remote)
            {
                if(!class_exists('WPvivid_downloader'))
                    include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-downloader.php';
                WPvivid_downloader::delete($remote,$files);
            }
        }
    }

}