<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Send_to_site_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_REMOTE_SEND_TO_SITE_ADDON'))
    define('WPVIVID_REMOTE_SEND_TO_SITE_ADDON','send_to_site_ex');

if(!defined('WPVIVID_SEND_TO_SITE_UPLOAD_SIZE'))
    define('WPVIVID_SEND_TO_SITE_UPLOAD_SIZE', 2);

class WPvivid_Send_to_site_addon extends WPvivid_Remote_addon
{
    public $options;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('WPVIVID_INIT_SEND_TO_SITE_ADDON'))
            {
                add_action('plugins_loaded', array($this, 'plugins_loaded'), 9);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'),11);
                define('WPVIVID_INIT_SEND_TO_SITE_ADDON',1);
            }
        }
        else
        {
            $this->options=$options;
        }
    }

    public function plugins_loaded()
    {
        if (!empty($_POST) &&isset($_POST['wpvivid_action']))
        {
            @ini_set( 'display_errors', 0 );
            if($_POST['wpvivid_action']=='send_to_site_connect')
            {
                $this->send_to_site_connect();
            }
            else if($_POST['wpvivid_action']=='send_to_site_finish')
            {
                $this->send_to_site_finish();
            }
            else if($_POST['wpvivid_action']=='send_to_site')
            {
                $this->send_to_site();
            }
            else if($_POST['wpvivid_action']=='send_to_site_file_status')
            {
                $this->send_to_site_file_status();
            }
            else if($_POST['wpvivid_action']=='clear_backup_cache')
            {
                $this->clear_backup_cache();
            }
            die();
        }
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_SEND_TO_SITE_ADDON] = 'WPvivid_Send_to_site_addon';
        return $remote_collection;
    }

    public function test_connect()
    {
        return array('result' => WPVIVID_SUCCESS,'test'=>$this->options['url']);
    }

    public function upload($task_id, $files, $callback = '')
    {
        include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-crypt.php';

        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Connect site ','notice');
        $ret=$this->connect_site($task_id);
        if($ret['result']==WPVIVID_FAILED)
        {
            if($ret['error']=='The uploading backup already exists in Backups list.')
            {
                return array('result' =>WPVIVID_SUCCESS);
            }
            else
            {
                return $ret;
            }
        }
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',WPVIVID_REMOTE_SEND_TO_SITE);
        if(empty($upload_job))
        {
            $job_data=array();
            foreach ($files as $file)
            {
                $file_data['size']=filesize($file);
                $file_data['uploaded']=0;
                $job_data[basename($file)]=$file_data;
            }
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',WPVIVID_REMOTE_SEND_TO_SITE,WPVIVID_UPLOAD_UNDO,'Start uploading',$job_data);
            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',WPVIVID_REMOTE_SEND_TO_SITE);
        }

        foreach ($files as $file)
        {
            $wpvivid_plugin->set_time_limit($task_id);
            if(array_key_exists(basename($file),$upload_job['job_data']))
            {
                if($upload_job['job_data'][basename($file)]['uploaded']==1)
                    continue;
            }

            $this -> last_time = time();
            $this -> last_size = 0;

            if(!file_exists($file))
                return array('result' =>WPVIVID_FAILED,'error' =>$file.' not found. The file might has been moved, renamed or deleted. Please reload the list and verify the file exists.');
            $result=$this->_upload($task_id, $file,$callback);
            if($result['result'] !==WPVIVID_SUCCESS)
            {
                $this->wpvivid_clear_backup_cache($task_id);
                return $result;
            }
        }
        $result=$this->upload_finish($task_id);
        return $result;
        //return array('result' =>WPVIVID_SUCCESS);
    }

    public function _upload($task_id, $file,$callback)
    {
        $this -> current_file_size = filesize($file);
        $this -> current_file_name = basename($file);

        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',WPVIVID_REMOTE_SEND_TO_SITE);

        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start uploading '.basename($file),'notice');

        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',WPVIVID_REMOTE_SEND_TO_SITE,WPVIVID_UPLOAD_UNDO,'Start uploading '.basename($file).'.',$upload_job['job_data']);

        $file_size=filesize($file);
        $md5=md5_file($file);
        $handle=fopen($file,'rb');

        $ret=$this->get_file_status($task_id,basename($file),$file_size,$md5);

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog(json_encode($ret),'notice');

        if($ret['result']==WPVIVID_SUCCESS)
        {
            if($ret['file_status']['status']=='finished')
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload finished','notice');
                fclose($handle);
                $upload_job['job_data'][basename($file)]['uploaded']=1;
                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',WPVIVID_REMOTE_SEND_TO_SITE,WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
                return array('result' =>WPVIVID_SUCCESS);
            }
            else if($ret['file_status']['status']=='continue')
            {
                $offset=$ret['file_status']['offset'];
            }
            else
            {
                $offset=0;
            }
        }
        else
        {
            return $ret;
        }

        $retry_count=0;
        while (!feof($handle))
        {
            $general_setting=WPvivid_Setting::get_setting(true, "");
            if(!isset($general_setting['options']['wpvivid_common_setting']['migrate_size']) || empty($general_setting['options']['wpvivid_common_setting']['migrate_size'])){
                $general_setting['options']['wpvivid_common_setting']['migrate_size']=WPVIVID_SEND_TO_SITE_UPLOAD_SIZE;
            }
            $upload_size = $general_setting['options']['wpvivid_common_setting']['migrate_size'];
            $upload_size = intval($upload_size) * 1024;

            $ret=$this->send_chunk($task_id,$handle,basename($file),$offset,$upload_size,$file_size,$md5);
            if($ret['result']==WPVIVID_SUCCESS)
            {
                $status = WPvivid_taskmanager::get_backup_task_status($task_id);
                $status['resume_count']=0;
                WPvivid_taskmanager::update_backup_task_status($task_id, false, 'running', false, $status['resume_count']);
                if((time() - $this -> last_time) >3)
                {
                    if(is_callable($callback))
                    {
                        call_user_func_array($callback,array($offset,$this -> current_file_name,
                            $this->current_file_size,$this -> last_time,$this -> last_size));
                    }
                    $this -> last_size = $offset;
                    $this -> last_time = time();
                }

                if($ret['op']=='continue')
                {
                    continue;
                }
                else
                {
                    break;
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('send chunk failed.','warning');
                if($retry_count>3)
                {
                    if(isset($ret['http_code']))
                    {
                        if($ret['http_code']==413)
                        {
                            $ret['error']='Site migration failed. The receiving site can\'t receive the oversized data chunk. Please set the value of Chunk size to 512 KB in plugin settings. Then try again.';
                        }
                    }

                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload failed, retry times: '.$retry_count,'warning');

                    return $ret;
                }
                else
                {
                    if(isset($ret['http_code']))
                    {
                        if($ret['http_code']==413)
                        {
                            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Site migration failed. The receiving site can\'t receive the oversized data chunk. Please set the value of Chunk size to 512 KB in plugin settings. Then try again. Chunk size: '.size_format($offset),'warning');
                        }
                        else
                        {
                            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload file error offset:'.size_format($offset).' http error:'.$ret['http_code'],'warning');
                        }
                    }
                    else
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload file error offset:'.size_format($offset).' error:'.$ret['error'],'warning');
                    }

                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('reset offset: '.$offset,'warning');
                    if(fseek($handle, $offset)!==0)
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Seek file offset failed:'.size_format($offset),'notice');
                    }

                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload retry, retry times: '.$retry_count,'warning');

                    $retry_count++;
                }
            }
        }
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload finished','notice');
        fclose($handle);
        $upload_job['job_data'][basename($file)]['uploaded']=1;
        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',WPVIVID_REMOTE_SEND_TO_SITE,WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
        return array('result' =>WPVIVID_SUCCESS);
    }

    public function wpvivid_clear_backup_cache($task_id)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $json=array();

        $json['backup_id']=$task_id;
        $json=json_encode($json);
        $crypt=new WPvivid_crypt(base64_decode($this->options['token']));
        $data=$crypt->encrypt_message($json);

        $data=base64_encode($data);

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Failed upload backup, clear backup cache.','notice');

        global $wp_version;
        $args['user-agent'] ='WordPress/' . $wp_version . '; ' . get_bloginfo('url');
        $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'clear_backup_cache');
        $args['timeout']=30;
        $response=wp_remote_post($this->options['url'],$args);

        if ( is_wp_error( $response ) )
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']= $response->get_error_message();
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog( $ret['error'],'notice');
        }
        else
        {
            if($response['response']['code']==200)
            {
                $res=json_decode($response['body'],1);
                if($res!=null)
                {
                    if($res['result']==WPVIVID_SUCCESS)
                    {
                        $ret['result']=WPVIVID_SUCCESS;
                    }
                    else
                    {
                        $ret['result']=WPVIVID_FAILED;
                        $ret['error']= $res['error'];
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog( $ret['error'],'notice');
                    }
                }
                else
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']= 'Failed to parse returned data, unable to clear target site backup cache.';
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog( $ret['error'],'notice');
                }
            }
            else
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']= 'Clear backup cache error '.$response['response']['code'].' '.$response['body'];
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog( $ret['error'],'notice');
            }
        }
        return $ret;
    }

    public function connect_site($task_id)
    {
        $json=array();

        $json['backup_id']=$task_id;
        $json=json_encode($json);
        $crypt=new WPvivid_crypt(base64_decode($this->options['token']));
        $data=$crypt->encrypt_message($json);

        $data=base64_encode($data);
        global $wp_version;
        $args['user-agent'] ='WordPress/' . $wp_version . '; ' . get_bloginfo('url');
        $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site_connect');
        $args['timeout']=30;
        $response=wp_remote_post($this->options['url'],$args);

        if ( is_wp_error( $response ) )
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']= $response->get_error_message();
        }
        else
        {
            if($response['response']['code']==200)
            {
                global $wpvivid_plugin;

                $res=json_decode($response['body'],1);
                if($res!=null)
                {
                    if($res['result']==WPVIVID_SUCCESS)
                    {
                        $ret['result']=WPVIVID_SUCCESS;
                    }
                    else
                    {
                        $ret['result']=WPVIVID_FAILED;
                        $ret['error']= $res['error'];
                    }
                }
                else
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']= 'Failed to parse returned data, unable to establish connection with the target site.';
                }
            }
            else
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']= 'Upload error '.$response['response']['code'].' '.$response['body'];
            }
        }

        return $ret;
    }

    public function send_chunk($task_id,$file_handle,$file_name,&$offset,$size,$file_size,$md5)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $upload_size=min($size,$file_size-$offset);

        if(fseek($file_handle, $offset)!==0)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Seek file offset failed:'.size_format($offset),'notice');
        }

        $data=fread($file_handle,$upload_size);

        if($data===false)
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']='Read file error at:'.$offset;
            return $ret;
        }

        $json['backup_id']=$task_id;
        $json['name']=$file_name;
        $json['offset']=$offset;
        $json['size']=$upload_size;
        $json['file_size']=$file_size;
        $json['md5']=$md5;
        $json['data']=base64_encode($data);
        $json=json_encode($json);

        $crypt=new WPvivid_crypt(base64_decode($this->options['token']));
        $data=$crypt->encrypt_message($json);

        $data=base64_encode($data);

        global $wp_version;
        $args['user-agent'] ='WordPress/' . $wp_version . '; ' . get_bloginfo('url');
        $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site');
        $args['timeout']=30;

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('send chunk '.basename($file_name).' offset '.$offset,'notice');

        $response=wp_remote_post($this->options['url'],$args);

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('finished send chunk','notice');

        if ( is_wp_error( $response ) )
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']= $response->get_error_message();
        }
        else
        {
            if($response['response']['code']==200)
            {
                $res=json_decode($response['body'],1);
                if($res!=null)
                {
                    if($res['result']==WPVIVID_SUCCESS)
                    {
                        $offset=$offset+$upload_size;
                        $ret['result']=WPVIVID_SUCCESS;
                        $ret['op']=$res['op'];
                    }
                    else
                    {
                        $ret['result']=WPVIVID_FAILED;
                        $ret['error']= $res['error'];
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog( $ret['error'],'notice');
                    }

                }
                else
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']= 'Failed to parse returned data, chunk transfer failed.';
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('error send chunk failed','notice');
                }
            }
            else
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['http_code']=$response['response']['code'];
                $ret['error']= 'http error, error code:'.$response['response']['code'];
            }
        }
        return $ret;
    }

    public function upload_finish($task_id)
    {
        $task=WPvivid_taskmanager::get_task($task_id);
        $new_task=new WPvivid_New_Backup_Task($task_id);
        $setting=$new_task->get_setting();
        $is_merge=isset($setting['is_merge'])?$setting['is_merge']:false;
        $task['options']['backup_options']['ismerge']=$is_merge;
        $backup_result['result']='success';
        $files=$new_task->get_backup_files();
        foreach ($files as $file)
        {
            $file_data['file_name'] = basename($file);
            $file_data['size'] = filesize($file);
            $file_data['md5'] = md5_file($file);
            $backup_result['files'][] =$file_data;
        }

        if($is_merge)
        {
            $backup['key']='backup_merge';
        }
        else
        {
            $backup['key']='backup_migrate';
        }

        $backup['result']=$backup_result;
        $task['options']['backup_options']['backup'][]=$backup;

        $json=array();
        $task['options']['backup_options']['compress']['compress_type']='zip';
        $task['options']['save_local']=1;

        $json['backup']=$task;
        $json['backup_id']=$task_id;
        $json=json_encode($json);

        $crypt=new WPvivid_crypt(base64_decode($this->options['token']));
        $data=$crypt->encrypt_message($json);

        $data=base64_encode($data);
        global $wp_version;
        $args['user-agent'] ='WordPress/' . $wp_version . '; ' . get_bloginfo('url');
        $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site_finish');
        $args['timeout']=30;
        $response=wp_remote_post($this->options['url'],$args);
        if ( is_wp_error( $response ) )
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']= $response->get_error_message();
        }
        else
        {
            if($response['response']['code']==200)
            {
                $res=json_decode($response['body'],1);
                if($res!=null)
                {
                    if($res['result']==WPVIVID_SUCCESS)
                    {
                        $ret['result']=WPVIVID_SUCCESS;
                    }
                    else
                    {
                        $ret['result']=WPVIVID_FAILED;
                        $ret['error']= $res['error'];
                    }
                }
                else
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']= 'Failed to parse returned data, uploading backup chunks failed.';
                }
            }
            else
            {
                $ret['result']=WPVIVID_FAILED;
                //$ret['error']= 'Upload error '.$response['response']['code'].' '.$response['body'];
                $ret['error']= 'Upload error '.$response['response']['code'];
            }
        }

        return $ret;
    }

    public function send_to_site_connect()
    {
        include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-crypt.php';
        try {
            if (isset($_POST['wpvivid_content'])) {
                $default = array();
                $option = get_option('wpvivid_api_token', $default);
                if (empty($option)) {
                    die();
                }
                if ($option['expires'] != 0 && $option['expires'] < time()) {
                    die();
                }

                $crypt = new WPvivid_crypt(base64_decode($option['private_key']));
                $body = base64_decode($_POST['wpvivid_content']);
                $data = $crypt->decrypt_message($body);
                if (!is_string($data)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'Data decryption failed.';
                    echo json_encode($ret);
                    die();
                }

                $params = json_decode($data, 1);
                if (is_null($params)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'Data decode failed.';
                    echo json_encode($ret);
                    die();
                }

                if (isset($params['backup_id']))
                {
                    $backup_list=new WPvivid_New_BackupList();
                    if ($backup_list->get_backup_by_id($params['backup_id']) !== false)
                    {
                        $ret['result'] = WPVIVID_FAILED;
                        $ret['error'] = 'The uploading backup already exists in Backups list.';
                        echo json_encode($ret);
                    } else {
                        global $wpvivid_plugin;
                        global $wpvivid_backup_pro;
                        if (!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder() . $params['backup_id'] . '_backup_log.txt')) {
                            $wpvivid_backup_pro->wpvivid_pro_log->CreateLogFile($params['backup_id'] . '_backup', 'no_folder', 'transfer');
                            $wpvivid_backup_pro->wpvivid_pro_log->WriteLogHander();
                        } else {
                            $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile($params['backup_id'] . '_backup', 'no_folder');
                        }


                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Connect site success', 'notice');
                        $ret['result'] = WPVIVID_SUCCESS;
                        echo json_encode($ret);
                    }
                } else {
                    $ret['result'] = WPVIVID_SUCCESS;
                    echo json_encode($ret);
                }
            }
        }
        catch (Exception $e) {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']=$e->getMessage();
            echo json_encode($ret);
            die();
        }
        die();
    }

    public function send_to_site()
    {
        include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-crypt.php';
        $test_log=new WPvivid_Log_Ex_addon();
        $test_log->CreateLogFile('test_backup','no_folder','transfer');
        $test_log->WriteLog('test upload.','notice');
        try
        {
            if(isset($_POST['wpvivid_content']))
            {
                global $wpvivid_plugin;
                global $wpvivid_backup_pro;

                $default=array();
                $option=get_option('wpvivid_api_token',$default);
                if(empty($option))
                {
                    die();
                }
                if($option['expires'] !=0 && $option['expires']<time())
                {
                    die();
                }
                $crypt=new WPvivid_crypt(base64_decode($option['private_key']));
                $body=base64_decode($_POST['wpvivid_content']);
                $data=$crypt->decrypt_message($body);
                if (!is_string($data))
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']='The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                $params=json_decode($data,1);
                if(is_null($params))
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']='The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile($params['backup_id'].'_backup','no_folder','backup');
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('start upload.','notice');

                $file_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.str_replace('wpvivid','wpvivid_temp',$params['name']);

                if(!file_exists($file_path))
                {
                    $handle=fopen($file_path,'w');
                    fclose($handle);
                }

                $handle=fopen($file_path,'rb+');
                $offset=$params['offset'];
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Write file:'.$file_path.' offset:'.size_format($offset),'notice');
                if($offset)
                {
                    if(fseek($handle, $offset)===-1)
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Seek file offset failed:'.size_format($offset),'notice');
                    }
                }

                if (fwrite($handle,base64_decode($params['data'])) === FALSE)
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Write file :'.$file_path.' failed size:'.filesize($file_path),'notice');
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Write file:'.$file_path.' success size:'.filesize($file_path),'notice');
                }

                fclose($handle);


                if(filesize($file_path)>=$params['file_size'])
                {
                    if (md5_file($file_path) == $params['md5'])
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('rename temp file:'.$file_path.' to new name:'.WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$params['name'],'notice');
                        rename($file_path,WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$params['name']);

                        $ret['result']=WPVIVID_SUCCESS;
                        $ret['op']='finished';
                    } else {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('file md5 not match','notice');
                        $ret['result']=WPVIVID_FAILED;
                        $ret['error']='File md5 is not matched.';
                    }
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('continue size:'.filesize($file_path).' size1:'.$params['file_size'],'notice');
                    $ret['result']=WPVIVID_SUCCESS;
                    $ret['op']='continue';
                    //
                }

                echo json_encode($ret);
            }
        }
        catch (Exception $e)
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']=$e->getMessage();
            echo json_encode($ret);
            die();
        }

        die();
    }

    public function send_to_site_finish()
    {
        include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-crypt.php';
        try {
            if (isset($_POST['wpvivid_content'])) {
                $default = array();
                $option = get_option('wpvivid_api_token', $default);
                if (empty($option)) {
                    die();
                }
                if ($option['expires'] != 0 && $option['expires'] < time()) {
                    die();
                }
                $crypt = new WPvivid_crypt(base64_decode($option['private_key']));
                $body = base64_decode($_POST['wpvivid_content']);
                $data = $crypt->decrypt_message($body);

                if (!is_string($data)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }
                $params = json_decode($data, 1);
                if (is_null($params)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }
                global $wpvivid_plugin;
                global $wpvivid_backup_pro;
                $wpvivid_backup_pro->wpvivid_pro_log = new WPvivid_Log_Ex_addon();
                $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile($params['backup_id'] . '_backup', 'no_folder', 'backup');

                if(isset($params['backup']['options']['backup_options']['backup']))
                {
                    $backups = $params['backup']['options']['backup_options']['backup'];
                    $backup_data=array_shift($backups);

                    if(isset($backup_data['result']['files']))
                    {
                        $files = $backup_data['result']['files'];
                        foreach ($files as $file)
                        {
                            $file_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$file['file_name'];
                            if(!file_exists($file_path))
                            {
                                $ret['result'] = WPVIVID_FAILED;
                                $ret['error'] = 'File not found, file_name: '.$file_path;
                                echo json_encode($ret);
                                die();
                            }

                            if(isset($file['size']) && filesize($file_path) !== $file['size'])
                            {
                                $ret['result'] = WPVIVID_FAILED;
                                $ret['error'] = 'File size not match, file_name: '.$file_path.', source size: '.$file['size'].', target size: '.filesize($file_path);
                                echo json_encode($ret);
                                die();
                            }

                            if(isset($file['md5']) && md5_file($file_path) !== $file['md5'])
                            {
                                $ret['result'] = WPVIVID_FAILED;
                                $ret['error'] = 'File size not match, file_name: '.$file_path.', source md5: '.$file['md5'].', target md5: '.md5_file($file_path);
                                echo json_encode($ret);
                                die();
                            }
                        }
                    }
                    else
                    {
                        $ret['result'] = WPVIVID_FAILED;
                        $ret['error'] = 'Failed to get file data.';
                        echo json_encode($ret);
                        die();
                    }
                }
                else
                {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'Failed to get backup data.';
                    echo json_encode($ret);
                    die();
                }

                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload finished', 'notice');
                if (isset($params['backup']) && isset($params['backup_id']))
                {
                    $list = WPvivid_Setting::get_option('wpvivid_backup_list');
                    $backup_data = $this->get_backup_data_by_task($params['backup']);
                    $list[$params['backup_id']] = $backup_data;
                    WPvivid_Setting::update_option('wpvivid_backup_list', $list);
                    update_option('wpvivid_export_auto_migration_finish', '1', 'no');
                }
                $ret['result'] = WPVIVID_SUCCESS;
                echo json_encode($ret);
            }
        }
        catch (Exception $e) {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']=$e->getMessage();
            echo json_encode($ret);
            die();
        }
        die();
    }

    public function get_backup_data_by_task($task)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $backup_data=array();
        $backup_data['type']='Migration';
        $backup_data['create_time']=$task['status']['start_time'];
        $backup_data['manual_delete']=0;
        $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
        $backup_data['compress']['compress_type']=$task['options']['backup_options']['compress']['compress_type'];
        $backup_data['save_local']=$task['options']['save_local'];
        $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->log_file;
        $backup_data['backup']=$this->get_backup_result_by_task($task);
        $backup_data['remote']=array();
        $backup_data['lock']=0;
        $backup_data=apply_filters('wpvivid_get_backup_data_by_task',$backup_data,$task);
        return $backup_data;
    }

    public function get_backup_result_by_task($task)
    {
        $ret['result']=WPVIVID_SUCCESS;
        $ret['files']=array();
        foreach ($task['options']['backup_options']['backup'] as $backup_data)
        {
            if($task['options']['backup_options']['ismerge']==1)
            {
                if (!defined('WPVIVID_BACKUP_TYPE_MERGE'))
                    define('WPVIVID_BACKUP_TYPE_MERGE','backup_merge');
                if(WPVIVID_BACKUP_TYPE_MERGE==$backup_data['key'])
                {
                    $ret=$backup_data['result'];
                    if($ret['result']!==WPVIVID_SUCCESS)
                    {
                        return $ret;
                    }
                }
            }
            else
            {
                $ret['files']=array_merge($ret['files'],$backup_data['result']['files']);
            }
        }
        return $ret;
    }

    public function cleanup($files)
    {
        return array('result' => WPVIVID_SUCCESS);
    }

    public function download($file, $local_path, $callback = '')
    {
        return array('result' => WPVIVID_SUCCESS);
    }

    public function get_file_status($task_id,$file,$file_size,$md5)
    {
        $json=array();

        $json['backup_id']=$task_id;
        $json['name']=$file;
        $json['file_size']=$file_size;
        $json['md5']=$md5;
        $json=json_encode($json);
        $crypt=new WPvivid_crypt(base64_decode($this->options['token']));
        $data=$crypt->encrypt_message($json);
        $data=base64_encode($data);
        global $wp_version;
        $args['user-agent'] ='WordPress/' . $wp_version . '; ' . get_bloginfo('url');
        $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site_file_status');
        $args['timeout']=30;
        $response=wp_remote_post($this->options['url'],$args);
        if ( is_wp_error( $response ) )
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']= $response->get_error_message();
        }
        else
        {
            if($response['response']['code']==200)
            {
                global $wpvivid_plugin;

                $res=json_decode($response['body'],1);
                if($res!=null)
                {
                    if($res['result']==WPVIVID_SUCCESS)
                    {
                        $ret['result']=WPVIVID_SUCCESS;
                        $ret['file_status']=$res['file_status'];
                    }
                    else
                    {
                        $ret['result']=WPVIVID_FAILED;
                        $ret['error']= $res['error'];
                    }
                }
                else
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']= 'Failed to parse returned data, unable to retrieve file status of target site.';
                }
            }
            else
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']= 'Upload error '.$response['response']['code'].' '.$response['body'];
            }
        }
        return $ret;
    }

    public function send_to_site_file_status()
    {
        include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-crypt.php';
        try {
            if (isset($_POST['wpvivid_content'])) {
                $default = array();
                $option = get_option('wpvivid_api_token', $default);
                if (empty($option)) {
                    die();
                }
                if ($option['expires'] != 0 && $option['expires'] < time()) {
                    die();
                }

                $crypt = new WPvivid_crypt(base64_decode($option['private_key']));
                $body = base64_decode($_POST['wpvivid_content']);
                $data = $crypt->decrypt_message($body);
                if (!is_string($data)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                $params = json_decode($data, 1);
                if (is_null($params)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                $file_path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . str_replace('wpvivid', 'wpvivid_temp', $params['name']);

                $rename = true;

                if (!file_exists($file_path))
                {
                    $file_path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . $params['name'];
                    $rename = false;
                    $offset=false;
                }
                else
                {
                    $offset = filesize($file_path);
                }

                if (!$offset) {
                    $ret['result'] = WPVIVID_SUCCESS;
                    $ret['file_status']['status'] = 'start';
                    echo json_encode($ret);
                    die();
                }

                if (filesize($file_path) >= $params['file_size']) {
                    if (md5_file($file_path) == $params['md5']) {
                        if ($rename)
                            rename($file_path, WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . $params['name']);
                        $ret['result'] = WPVIVID_SUCCESS;
                        $ret['file_status']['status'] = 'finished';
                    } else {
                        $ret['result'] = WPVIVID_FAILED;
                        $ret['error'] = 'File md5 is not matched.';
                    }
                } else {
                    $ret['result'] = WPVIVID_SUCCESS;
                    $ret['file_status']['status'] = 'continue';
                    $ret['file_status']['offset'] = filesize($file_path);
                }
                echo json_encode($ret);
            }
        }
        catch (Exception $e) {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']=$e->getMessage();
            echo json_encode($ret);
            die();
        }
        die();
    }

    public function clear_backup_cache()
    {
        include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-crypt.php';
        try {
            if (isset($_POST['wpvivid_content'])) {
                $default = array();
                $option = get_option('wpvivid_api_token', $default);
                if (empty($option)) {
                    die();
                }
                if ($option['expires'] != 0 && $option['expires'] < time()) {
                    die();
                }

                $crypt = new WPvivid_crypt(base64_decode($option['private_key']));
                $body = base64_decode($_POST['wpvivid_content']);
                $data = $crypt->decrypt_message($body);

                if (!is_string($data)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }
                $params = json_decode($data, 1);
                if (is_null($params)) {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                global $wpvivid_plugin;
                global $wpvivid_backup_pro;
                $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile($params['backup_id'] . '_backup', 'no_folder', 'backup');

                $backup_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
                if(is_dir($backup_path))
                {
                    $handler = opendir($backup_path);
                    if($handler!==false)
                    {
                        while (($filename = readdir($handler)) !== false)
                        {
                            if ($filename != "." && $filename != "..")
                            {
                                if (is_dir($backup_path  . $filename))
                                {
                                    continue;
                                }
                                else {
                                    if (WPvivid_backup_pro_function::is_wpvivid_backup($filename))
                                    {
                                        if ($id =WPvivid_backup_pro_function::get_wpvivid_backup_id($filename))
                                        {
                                            $white_label_id = str_replace(apply_filters('wpvivid_white_label_file_prefix', 'wpvivid'), 'wpvivid', $id);
                                            if(isset($params['backup_id']))
                                            {
                                                $clear_backup_id = sanitize_text_field($params['backup_id']);

                                                if($id === $clear_backup_id || $white_label_id === $clear_backup_id)
                                                {
                                                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Clear backup file: '.$backup_path.$filename, 'notice');
                                                    @unlink($backup_path.$filename);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if($handler)
                            @closedir($handler);
                    }
                    $ret['result'] = WPVIVID_SUCCESS;
                }
                else{
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']='Failed to get local storage directory.';
                }
                echo json_encode($ret);
            }
        }
        catch (Exception $e) {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']=$e->getMessage();
            echo json_encode($ret);
            die();
        }
        die();
    }
}