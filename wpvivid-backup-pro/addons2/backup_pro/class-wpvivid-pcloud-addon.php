<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_pCloud_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_REMOTE_PCLOUD'))
{
    define('WPVIVID_REMOTE_PCLOUD','pCloud');
}

class WPvivid_pCloud_addon extends WPvivid_Remote_addon
{
    private $options;
    private $upload_chunk_size = 10485760;
    private $download_chunk_size = 10485760;
    public $add_remote;
    public function __construct($options = array())
    {
        if(empty($options))
        {
            if(!defined('WPVIVID_INIT_STORAGE_TAB_PCLOUD'))
            {
                add_action('init', array($this, 'handle_auth_actions'));
                add_action('wp_ajax_wpvivid_pcloud_add_remote',array( $this,'finish_add_remote'));
                add_action('wpvivid_add_storage_page_pcloud', array($this, 'wpvivid_add_storage_page_pcloud'));
                add_action('wpvivid_add_storage_page',array($this,'wpvivid_add_storage_page_pcloud'), 10);
                add_action('wpvivid_edit_remote_page',array($this,'wpvivid_edit_storage_page_pcloud'), 10);
                add_filter('wpvivid_get_out_of_date_remote',array($this,'wpvivid_get_out_of_date_pcloud'),10,2);
                add_filter('wpvivid_storage_provider_tran',array($this,'wpvivid_storage_provider_pcloud'),10);
                add_filter('wpvivid_get_root_path',array($this,'wpvivid_get_root_path_pcloud'),10);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'),11);
                define('WPVIVID_INIT_STORAGE_TAB_PCLOUD',1);
            }
        }else{
            $this -> options = $options;
        }
        $this->add_remote=false;
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_PCLOUD]='WPvivid_pCloud_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_PCLOUD)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function handle_auth_actions()
    {
        if(isset($_GET['action']))
        {
            if($_GET['action'] === 'wpvivid_pro_pcloud_auth')
            {
                if(!apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-remote'))
                {
                    return;
                }

                try {
                    $rand_id = substr(md5(time().rand()), 0,13);
                    $auth_id = 'wpvivid-auth-'.$rand_id;
                    $area = sanitize_text_field($_GET['area']);
                    $remote_options['auth_id']=$auth_id;
                    $remote_options['area']=$area;
                    set_transient('pcloud_auth_id', $remote_options, 900);
                    $state = apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page=' .sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')). '&action=wpvivid_pro_pcloud_finish_auth&sub_page=cloud_storage_pcloud&area='.$area.'&auth_id='.$auth_id;
                    $url = $this->getAuthorizeCodeUrl($state);
                    header('Location: ' . filter_var($url, FILTER_SANITIZE_URL));
                }
                catch (Exception $e){
                    _e('<div class="notice notice-error"><p>'.$e->getMessage().'</p></div>');
                }
            }
            else if($_GET['action'] === 'wpvivid_pro_pcloud_finish_auth')
            {
                $tmp_options = get_transient('pcloud_auth_id');
                if($tmp_options === false)
                {
                    return;
                }
                else if($tmp_options['auth_id'] !== $_GET['auth_id'])
                {
                    delete_transient('pcloud_auth_id');
                    return;
                }
                try
                {
                    $remoteslist = WPvivid_Setting::get_all_remote_options();
                    foreach ($remoteslist as $key => $value)
                    {
                        if (isset($value['auth_id']) && isset($_GET['auth_id']) && $value['auth_id'] == $_GET['auth_id']) {
                            _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the pCloud account as your remote storage.</p></div>');
                            return;
                        }
                    }

                    if($tmp_options['area']===$_GET['area'])
                    {
                        if(empty($_GET['code']))
                        {
                            if(empty($tmp_options['token']))
                            {
                                header('Location: ' . apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page=' . sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')) . '&action=wpvivid_pro_pcloud_drive&result=error&resp_msg=' . 'Get pCloud token failed.');

                                return;
                            }
                        }
                        else
                        {
                            $tmp_options['type'] = WPVIVID_REMOTE_PCLOUD;
                            $tmp_options['token'] = base64_encode($_GET['code']);
                            $tmp_options['is_encrypt'] = 1;
                            set_transient('pcloud_auth_id', $tmp_options, 900);
                        }
                        $this->add_remote=true;
                    }
                    else
                    {
                        return;
                    }
                }
                catch (Exception $e){
                    _e('<div class="notice notice-error"><p>'.$e->getMessage().'</p></div>');
                }
            }
            else if($_GET['action']=='wpvivid_pro_pcloud_drive')
            {
                try {
                    if (isset($_GET['result'])) {
                        if ($_GET['result'] == 'success') {
                            //add_action('show_notice', array($this, 'wpvivid_show_notice_add_pcloud_success'));
                            _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the pCloud account as your remote storage.</p></div>');
                        } else if ($_GET['result'] == 'error') {
                            //add_action('show_notice', array($this, 'wpvivid_show_notice_add_pcloud_error'));
                            global $wpvivid_plugin;
                            $wpvivid_plugin->wpvivid_handle_remote_storage_error($_GET['resp_msg'], 'Add pCloud Remote');
                            _e('<div class="notice notice-error"><p>'.$_GET['resp_msg'].'</p></div>');
                        }
                    }
                }
                catch (Exception $e){
                    _e('<div class="notice notice-error"><p>'.$e->getMessage().'</p></div>');
                }
            }
        }
    }

    public function getAuthorizeCodeUrl($state)
    {
        $appKey='7vmaUO2QwuB';
        //$appKey='YSDgtlceijX';
        $params['client_id']=$appKey;
        $params['response_type']='code';
        $params['state']=$state;
        if(isset($_GET['area'])){
            if($_GET['area'] === 'area_us'){
                $params["redirect_uri"] = 'https://auth.wpvivid.com/pcloud/';
            }
            else{
                $params["redirect_uri"] = 'https://auth.wpvivid.com/pcloud_eu/';
            }
        }
        else{
            $params["redirect_uri"] = 'https://auth.wpvivid.com/pcloud/';
        }
        return "https://my.pcloud.com/oauth2/authorize?".http_build_query($params);
    }

    public function test_connect()
    {
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function sanitize_options($skip_name='')
    {
        $ret['result']=WPVIVID_PRO_FAILED;

        if(!isset($this->options['name']))
        {
            $ret['error']="Warning: An alias for remote storage is required.";
            return $ret;
        }

        $this->options['name']=sanitize_text_field($this->options['name']);

        if(empty($this->options['name']))
        {
            $ret['error']="Warning: An alias for remote storage is required.";
            return $ret;
        }

        if(isset($this->options['chunk_size']))
        {
            $this->options['chunk_size']=$this->options['chunk_size']*1024*1024;
        }

        $remoteslist=WPvivid_Setting::get_all_remote_options();
        foreach ($remoteslist as $key=>$value)
        {
            if(isset($value['name'])&&$value['name'] == $this->options['name']&&$skip_name!=$value['name'])
            {
                $ret['error']="Warning: The alias already exists in storage list.";
                return $ret;
            }
        }
        $ret['result']=WPVIVID_PRO_SUCCESS;
        $ret['options']=$this->options;
        return $ret;
    }

    public function upload($task_id, $files, $callback = '')
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        if(isset($this->options['chunk_size']))
            $this->upload_chunk_size = $this->options['chunk_size'];

        $ret=$this->check_folder($this->options['path']);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        if(empty($upload_job))
        {
            $job_data=array();
            foreach ($files as $file)
            {
                $file_data['size']=filesize($file);
                $file_data['uploaded']=0;
                $job_data[basename($file)]=$file_data;
            }
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading',$job_data);
            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        }

        foreach ($files as $file)
        {
            if(is_array($upload_job['job_data']) &&array_key_exists(basename($file),$upload_job['job_data']))
            {
                if($upload_job['job_data'][basename($file)]['uploaded']==1)
                    continue;
            }

            $this -> last_time = time();
            $this -> last_size = 0;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start uploading '.basename($file),'notice');
            $wpvivid_plugin->set_time_limit($task_id);
            if(!file_exists($file))
                return array('result' =>WPVIVID_PRO_FAILED,'error' =>$file.' not found. The file might has been moved, renamed or deleted. Please reload the list and verify the file exists.');
            $result = $this -> _upload($task_id,$file,$folder_id,$callback);
            if($result['result'] !==WPVIVID_PRO_SUCCESS)
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploading '.basename($file).' failed.','notice');
                return $result;
            }
            else
            {
                WPvivid_Custom_Interface_addon::wpvivid_reset_backup_retry_times($task_id);
            }
            $upload_job['job_data'][basename($file)]['uploaded']=1;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
        }
        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading completed.',$upload_job['job_data']);
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function check_folder($path)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $ret=$this->get_folder_id($root_path,0);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $root_folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($path,$root_folder_id);

        return $ret;
    }

    public function get_folder_id($path,$folder_id)
    {
        $params = array(
            "folderid" => $folder_id
        );
        if(dirname($path)=='.')
        {
            $response=$this->remote_get('listfolder',$params);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                if(isset($response['body']['metadata']))
                {
                    $metadata=$response['body']['metadata'];
                    foreach ($metadata['contents'] as $data)
                    {
                        if($data['isfolder']&&$data['name']==$path)
                        {
                            $ret['result']=WPVIVID_PRO_SUCCESS;
                            $ret['folder_id']=$data['folderid'];
                            return $ret;
                        }
                    }

                    return $this->create_folder($path,$folder_id);
                }
                else
                {
                    return array('result' => WPVIVID_PRO_FAILED,'error'=> 'listfolder failed not found metadata.');
                }
            }
            else
            {
                return $response;
            }
        }
        else
        {
            $custom_path=dirname($path);
            $ret=$this->get_folder_id($custom_path,$folder_id);

            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }

            $folder_id=$ret['folder_id'];
            $custom_path2=basename($path);
            return $this->get_folder_id($custom_path2,$folder_id);
        }
    }

    public function create_folder($name,$folder_id)
    {
        $params = array(
            "name" => $name,
            "folderid" => $folder_id
        );

        $response = $this->remote_get('createfolder',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            if(isset($response['body']['metadata']))
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['folder_id']=$response['body']['metadata']['folderid'];
                return $ret;
            }
            else
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'createfolder failed not found metadata.');
            }
        }
        else {
            return $response;
        }
    }

    public function delete_file_by_name($file_name,$folder_id)
    {
        $ret=$this->get_file_id($file_name,$folder_id);
        if($ret['result']===WPVIVID_PRO_SUCCESS)
        {
            $file_id=$ret['file_id'];
            return $this->delete_file_by_id($file_id);
        }
        else
        {
            return $ret;
        }
    }

    public function get_file_id($file_name,$folder_id)
    {
        $params = array(
            "folderid" => $folder_id
        );
        $response=$this->remote_get('listfolder',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            if(isset($response['body']['metadata']))
            {
                $metadata=$response['body']['metadata'];
                foreach ($metadata['contents'] as $data)
                {
                    if($data['isfolder']==false&&$data['name']==$file_name)
                    {
                        $ret['result']=WPVIVID_PRO_SUCCESS;
                        $ret['file_id']=$data['fileid'];
                        return $ret;
                    }
                }
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'file not found.');
            }
            else
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'listfolder failed not found metadata.');
            }
        }
        else
        {
            return $response;
        }
    }

    public function delete_file_by_id($file_id)
    {
        $params = array(
            "fileid" => $file_id
        );
        $response=$this->remote_get('deletefile',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function _upload($task_id,$local_file,$folder_id,$callback)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check if the server already has the same name file.','notice');

        $this->current_file_size = filesize($local_file);
        $this->current_file_name = basename($local_file);

        $this->delete_file_by_name(basename($local_file),$folder_id);

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Creating upload session.','notice');

        $ret=$this->createUpload();

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $upload_id=$ret['upload_id'];
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('get upload id success:'.json_encode($ret),'notice');

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Ready to start uploading files.','notice');

        $params = array(
            "uploadid" => $upload_id,
            "uploadoffset" => 0
        );

        $file = fopen($local_file, "r");
        while (!feof($file))
        {
            $retry=0;
            $content = fread($file, $this->upload_chunk_size);
            while($retry<5)
            {
                $ret=$this->upload_chunk($params, $content);
                if($ret['result']==WPVIVID_PRO_SUCCESS)
                {
                    break;
                }
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('offset trys:'.$retry,'notice');
                $retry++;
            }

            if($ret['result']==WPVIVID_PRO_SUCCESS)
            {
                if((time() - $this -> last_time) >3)
                {
                    if(is_callable($callback))
                    {
                        call_user_func_array($callback,array($params["uploadoffset"],$this -> current_file_name,
                            $this->current_file_size,$this -> last_time,$this -> last_size));
                    }
                    $this -> last_size = $params["uploadoffset"];
                    $this -> last_time = time();
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload failed after try times:'.$retry,'notice');
                return $ret;
            }
            $params["uploadoffset"] +=$this->upload_chunk_size;
        }
        fclose($file);

        $ret=$this->save_uploaded_file($upload_id, basename($local_file), $folder_id);

        return $ret;
    }

    public function _upload_ex($task_id,$local_file,$folder_id,$callback)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check if the server already has the same name file.','notice');

        $this->current_file_size = filesize($local_file);
        $this->current_file_name = basename($local_file);

        $this->delete_file_by_name(basename($local_file),$folder_id);

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Creating upload session.','notice');

        $ret=$this->createUpload();

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $upload_id=$ret['upload_id'];
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('get upload id success:'.json_encode($ret),'notice');

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Ready to start uploading files.','notice');

        $params = array(
            "uploadid" => $upload_id,
            "uploadoffset" => 0
        );

        $file = fopen($local_file, "r");
        while (!feof($file))
        {
            $retry_delay = 2;
            $retry=0;
            $content = fread($file, $this->upload_chunk_size);
            while($retry<10)
            {
                $ret=$this->upload_chunk($params, $content);
                if($ret['result']==WPVIVID_PRO_SUCCESS)
                {
                    break;
                }
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('offset trys:'.$retry,'notice');
                $retry++;
                $retry_delay *= 2;
                sleep($retry_delay);
            }

            if($ret['result']==WPVIVID_PRO_SUCCESS)
            {
                if((time() - $this -> last_time) >3)
                {
                    if(is_callable($callback))
                    {
                        call_user_func_array($callback,array($params["uploadoffset"],$this -> current_file_name,
                            $this->current_file_size,$this -> last_time,$this -> last_size));
                    }
                    $this -> last_size = $params["uploadoffset"];
                    $this -> last_time = time();
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload failed after try times:'.$retry,'notice');
                return $ret;
            }
            $params["uploadoffset"] +=$this->upload_chunk_size;
        }
        fclose($file);

        $ret=$this->save_uploaded_file($upload_id, basename($local_file), $folder_id);

        return $ret;
    }

    public function createUpload()
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $response=$this->remote_get('upload_create',array());

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            if(isset($response['body']['uploadid']))
            {
                $upload_id=$response['body']['uploadid'];

                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['upload_id']=$upload_id;
                return $ret;
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('isset not have upload_id.','notice');
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']='isset not have upload_id';
                return $ret;
            }


        }
        else
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('remote_get failed.','notice');
            return $response;
        }
    }

    public function upload_chunk($params,$content)
    {
        return $this->remote_put("upload_write", $params, $content);
    }

    public function save_uploaded_file($upload_id,$path,$folderId)
    {
        $params = array(
            "uploadid" => $upload_id,
            "name" => $path,
            "folderid" => $folderId
        );

        $response=$this->remote_get('upload_save',$params);

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function remote_get($method,$params,$timeout=30)
    {
        global $wpvivid_plugin;

        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $params['access_token']=base64_decode($this->options['token']);
        }
        else{
            $params['access_token']=$this->options['token'];
        }

        if(isset($this->options['area'])){
            if($this->options['area'] === 'area_us'){
                $url='https://api.pcloud.com/'.$method;
            }
            else{
                $url='https://eapi.pcloud.com/'.$method;
            }
        }
        else{
            $url='https://api.pcloud.com/'.$method;
        }
        $url .= "?".http_build_query($params);
        $args['timeout']=$timeout;
        $response=wp_remote_get($url,$args);
        if(!is_wp_error($response))
        {
            $body=json_decode($response['body'],1);
            if($body!=null)
            {
                if($body['result']==0)
                {
                    $ret['result']=WPVIVID_PRO_SUCCESS;
                    $ret['body']=$body;
                    return $ret;
                }
                else
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']= $body['error'];
                    return $ret;
                }
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']= $response;
                return $ret;
            }
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']=$response->get_error_message();
            return $ret;
        }
    }

    private function remote_put($method,$params,$content,$timeout=30)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $params['access_token']=base64_decode($this->options['token']);
        }
        else{
            $params['access_token']=$this->options['token'];
        }

        if(isset($this->options['area'])){
            if($this->options['area'] === 'area_us'){
                $url='https://api.pcloud.com/'.$method;
            }
            else{
                $url='https://eapi.pcloud.com/'.$method;
            }
        }
        else{
            $url='https://api.pcloud.com/'.$method;
        }
        $url .= "?".http_build_query($params);
        $args['method']='PUT';
        $args['headers']=array('content-type' => 'Content-Type: text/html');
        $args['body']=$content;
        $args['timeout']=$timeout;

        $response=wp_remote_post($url,$args);
        if(!is_wp_error($response))
        {
            $body=json_decode($response['body'],1);
            if($body!=null)
            {
                if($body['result']==0)
                {
                    $ret['result']=WPVIVID_PRO_SUCCESS;
                    $ret['body']=$body;
                    return $ret;
                }
                else
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']= $body['error'];
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('body error:'.json_encode($body),'notice');
                    return $ret;
                }
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']= $response;
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('response error:'.$ret['error'],'notice');
                return $ret;
            }
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']=$response->get_error_message();
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('remote put failed:'.$ret['error'],'notice');
            return $ret;
        }
    }

    public function download($file, $local_path, $callback = '')
    {
        $this -> current_file_name = $file['file_name'];
        $this -> current_file_size = $file['size'];

        if(isset($this->options['chunk_size']))
            $this->download_chunk_size = $this->options['chunk_size'];

        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Remote type: pCloud.','notice');

        if(isset($file['remote_path']))
        {
            $path=$this->options['path'].'/'.$file['remote_path'];
        }
        else
        {
            $path=$this->options['path'];
        }

        $ret=$this->check_folder($path);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $ret=$this->get_file_id($file['file_name'],$folder_id);
        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $file_path=$local_path.$file['file_name'];
        @unlink($file_path);
        @unlink($file_path.'.download');

        $file_id=$ret['file_id'];

        return $this->_download($file_id,$local_path,$file,$callback);
    }

    public function _download($file_id,$local_path,$file,$callback)
    {
        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_download_log->WriteLog('get file link.','notice');
        $ret = $this->get_link($file_id);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $link=$ret['link'];

        $chunk_size=$this->download_chunk_size;

        $source = fopen($link, "rb");
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.','notice');
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'] ,'notice');
        $file_handle = fopen($local_path.$file['file_name'].'.download', "wb");
        $downloaded_start=0;
        while (!feof($source))
        {
            $content = fread($source, $chunk_size);
            fwrite($file_handle, $content);
            $downloaded_start+=strlen($content);
            if((time() - $this -> last_time) >3)
            {
                if(is_callable($callback))
                {
                    call_user_func_array($callback,array($downloaded_start,$this -> current_file_name,
                        $this->current_file_size,$this -> last_time,$this -> last_size));
                }
                $this -> last_size = $downloaded_start;
                $this -> last_time = time();
            }
        }
        fclose($file_handle);
        fclose($source);
        rename($local_path.$file['file_name'].'.download', $local_path.$file['file_name']);

        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function chunk_download($download_info,$callback)
    {
        set_time_limit(500);

        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];
        $local_path = $download_info['local_path'];

        if(isset($this->options['chunk_size']))
            $this->download_chunk_size = $this->options['chunk_size'];

        if(file_exists($local_path))
        {
            unlink($local_path);
        }
        $offset =  0;

        $download_chunk_size =  $this->download_chunk_size;

        $fh = fopen($local_path, 'a');

        if(filesize($local_path) ==  $this -> current_file_size)
        {
            @fclose($fh);
            rename($local_path, $download_info['root_path'].$download_info['file_name']);

            $result['result']='success';
            $result['finished']=1;
            $result['offset']=$this -> current_file_size;
            return $result;
        }

        $path=$this->options['path'];
        $ret=$this->check_folder($path);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $ret=$this->get_file_id($download_info['file_name'],$folder_id);
        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $file_id=$ret['file_id'];
        $ret = $this->get_link($file_id);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $link=$ret['link'];

        $source = fopen($link, "rb");
        //fseek($source,$offset);

        //$time_limit = 30;
        //$start_time = time();

        while (!feof($source))
        {
            $content = fread($source, $download_chunk_size);
            fwrite($fh, $content);
            $offset+=strlen($content);
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

            /*
            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                @fclose($fh);
                $result['result']='success';
                $result['finished']=0;
                $result['offset']=$offset;
                return $result;
            }
            */
        }

        fclose($fh);
        fclose($source);
        clearstatcache();

        if(filesize($local_path) != $this -> current_file_size)
        {
            @unlink($local_path);
            return array('result' => 'failed', 'error' => 'Downloading ' . basename($local_path) . ' failed. ' . basename($local_path) . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }
        else
        {
            rename($local_path, $download_info['root_path'].$download_info['file_name']);

            $result['result']='success';
            $result['finished']=1;
            $result['offset']=$this -> current_file_size;
            return $result;
        }
    }

    public function get_link($file_id)
    {
        $params = array(
            "fileid" => $file_id
        );

        $response = $this->remote_get("getfilelink", $params);

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['link']='https://'.$response['body']['hosts'][0].$response['body']['path'];
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function upload_rollback($file,$folder,$slug,$version)
    {
        $options = $this -> options;

        if(isset($this->options['chunk_size']))
            $this->upload_chunk_size = $this->options['chunk_size'];

        $ret=$this->check_rollback_folder($this->options['path'],$folder,$slug,$version);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $this->current_file_size = filesize($file);
        $this->current_file_name = basename($file);

        $this->delete_file_by_name(basename($file),$folder_id);

        $ret=$this->createUpload();

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $upload_id=$ret['upload_id'];
        $params = array(
            "uploadid" => $upload_id,
            "uploadoffset" => 0
        );

        $file_handle = fopen($file, "r");
        while (!feof($file_handle))
        {
            $content = fread($file_handle, $this->upload_chunk_size);
            $ret=$this->upload_chunk($params, $content);

            if($ret['result']!=WPVIVID_PRO_SUCCESS)
            {
                return $ret;
            }

            $params["uploadoffset"] +=$this->upload_chunk_size;
        }
        fclose($file_handle);

        $ret=$this->save_uploaded_file($upload_id, basename($file), $folder_id);

        return $ret;

    }

    public function check_rollback_folder($path,$folder,$slug,$version)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $ret=$this->get_folder_id($root_path,0);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $root_folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($path,$root_folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id("rollback_ex",$folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($folder,$folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($slug,$folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($version,$folder_id);

        return $ret;
    }

    public function download_rollback($download_info)
    {
        $type=$download_info['type'];
        $slug=$download_info['slug'];
        $version=$download_info['version'];

        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];
        $local_path = $download_info['local_path'];

        $options = $this->options;

        $offset = file_exists($local_path) ? filesize($local_path) : 0;
        $download_chunk_size = 1*1024*1024;
        $fh = fopen($local_path, 'a');

        if(filesize($local_path) ==  $this -> current_file_size)
        {
            @fclose($fh);
            rename($local_path, $download_info['root_path'].$download_info['file_name']);

            $result['result']='success';
            $result['finished']=1;
            $result['offset']=$this -> current_file_size;
            return $result;
        }

        $path=$this->options['path'];
        $ret=$this->check_rollback_folder($path,$type,$slug,$version);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $ret=$this->get_file_id($download_info['file_name'],$folder_id);
        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $file_id=$ret['file_id'];
        $ret = $this->get_link($file_id);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $link=$ret['link'];

        $source = fopen($link, "rb");

        while (!feof($source))
        {
            $content = fread($source, $download_chunk_size);
            fwrite($fh, $content);
            $offset+=strlen($content);
        }

        fclose($fh);
        fclose($source);
        clearstatcache();

        if(filesize($local_path) != $this -> current_file_size)
        {
            @unlink($local_path);
            return array('result' => 'failed', 'error' => 'Downloading ' . basename($local_path) . ' failed. ' . basename($local_path) . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }
        else
        {
            rename($local_path, $download_info['root_path'].$download_info['file_name']);

            $result['result']='success';
            $result['finished']=1;
            $result['offset']=$this -> current_file_size;
            return $result;
        }
    }

    public function cleanup($files)
    {
        set_time_limit(120);

        $ret=$this->check_folder($this->options['path']);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        foreach ($files as $file)
        {
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $remote_path=$file['remote_path'];
                    $ret=$this->get_folder_id($remote_path,$folder_id);
                    if($ret['result']===WPVIVID_PRO_FAILED)
                    {
                       continue;
                    }
                    else
                    {
                        $temp_folder_id=$ret['folder_id'];
                    }
                }
                else
                {
                    $temp_folder_id=$folder_id;
                }

                $this->delete_file_by_name($file['file_name'],$temp_folder_id);
            }
            else
            {
                $this->delete_file_by_name($file,$folder_id);
            }
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        $ret=$this->check_rollback_folder($this->options['path'],$type,$slug,$version);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $this->delete_file_by_name($slug.'.zip',$folder_id);

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_folder($folders)
    {
        set_time_limit(120);

        $ret=$this->check_folder($this->options['path']);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $params = array(
            "folderid" => $folder_id
        );
        $response=$this->remote_get('listfolder',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            if(isset($response['body']['metadata']))
            {
                $metadata=$response['body']['metadata'];
                foreach ($metadata['contents'] as $data)
                {
                    if($data['isfolder']==true&&in_array($data['name'],$folders))
                    {
                        $this->delete_folder($data['folderid']);
                    }
                }
            }
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function delete_folder($folder_id)
    {
        $params = array(
            "folderid" => $folder_id
        );
        $response=$this->remote_get('deletefolder',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function wpvivid_add_storage_page_pcloud()
    {
        global $wpvivid_backup_pro;
        if($this->add_remote)
        {
            ?>
            <div id="storage_account_pcloud" class="storage-account-page">
                <div style="color:#8bc34a; padding: 0 10px 10px 0;">
                    <strong>Authentication is done, please continue to enter the storage information, then click 'Add Now' button to save it.</strong>
                </div>
                <div style="padding: 0 10px 10px 0;">
                    <strong>Enter Your pCloud Information</strong>
                </div>
                <table class="wp-list-table widefat plugins" style="width:100%;">
                    <tbody>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="pcloud" name="name" placeholder="Enter a unique alias: e.g. pCloud-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>A name to help you identify the storage if you have multiple remote storage connected.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" autocomplete="off" option="pcloud" name="root_path" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Customize a root directory in your pCloud for holding %s directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="pcloud" name="path" placeholder="pCloud Folder" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_/]/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Customize the directory where you want to store backups. By default it takes your current website domain or url.</i>
                            </div>
                        </td>
                    </tr>

                    <!--<tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="pcloud" name="backup_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of non-database only and non-incremental backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="pcloud" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'pcloud', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="pcloud" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>The block size of uploads and downloads. Reduce it if you encounter a timeout when transferring files.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="pcloud" name="default" checked />Set as the default remote storage.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Once checked, all this sites backups sent to a remote storage destination will be uploaded to this storage by default.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input id="wpvivid_pcloud_auth" class="button-primary" type="submit" value="Add Now">
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Click the button to add the storage.</i>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <script>
                function wpvivid_check_pcloud_storage_alias(storage_alias)
                {
                    var bfind=true;
                    jQuery('#wpvivid_remote_storage_list tr').each(function (i)
                    {
                        jQuery(this).children('td').each(function (j)
                        {
                            if (j == 1)
                            {
                                if (jQuery(this).text() == storage_alias)
                                {
                                    bfind=false;
                                }
                            }
                        });
                    });

                    return bfind;
                }

                jQuery('#wpvivid_pcloud_auth').click(function()
                {
                    wpvivid_pcloud_auth();
                });

                function wpvivid_pcloud_auth()
                {
                    wpvivid_settings_changed = false;
                    var name='';
                    var path = '';
                    var root_path='';
                    var chunk_size='';
                    var area = '';
                    var backup_retain='';
                    var backup_db_retain='';
                    var backup_incremental_retain='';
                    var backup_rollback_retain='';
                    var bdefault = '0';
                    jQuery("input:checkbox[option=pcloud][name=default]").each(function(){
                        var key = jQuery(this).prop('name');
                        if(jQuery(this).prop('checked')) {
                            bdefault = '1';
                        }
                        else {
                            bdefault = '0';
                        }
                    });
                    var use_remote_retention = '0';
                    jQuery('input:checkbox[option=pcloud][name=use_remote_retention]').each(function()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            use_remote_retention = '1';
                        }
                        else
                        {
                            use_remote_retention = '0';
                        }
                    });
                    jQuery('input:text[option=pcloud]').each(function()
                    {
                        var type = jQuery(this).prop('name');
                        if(type == 'name')
                        {
                            name = jQuery(this).val();
                        }
                        if(type == 'path')
                        {
                            path = jQuery(this).val();
                        }
                        if(type=='root_path')
                        {
                            root_path = jQuery(this).val();
                        }
                        if(type == 'chunk_size')
                        {
                            chunk_size = jQuery(this).val();
                        }
                        if(type==='backup_retain')
                        {
                            backup_retain = jQuery(this).val();
                        }
                        if(type==='backup_db_retain')
                        {
                            backup_db_retain = jQuery(this).val();
                        }
                        if(type==='backup_incremental_retain')
                        {
                            backup_incremental_retain = jQuery(this).val();
                        }
                        if(type==='backup_rollback_retain')
                        {
                            backup_rollback_retain = jQuery(this).val();
                        }
                    });
                    jQuery('select[option=pcloud]').each(function()
                    {
                        var type = jQuery(this).prop('name');
                        if(type == 'area')
                        {
                            area = jQuery(this).val();
                        }
                    });
                    if(name == '')
                    {
                        alert('Warning: An alias for remote storage is required.');
                    }
                    else if(wpvivid_check_pcloud_storage_alias(name) === false)
                    {
                        alert("Warning: The alias already exists in storage list.");
                    }
                    else if(root_path == '')
                    {
                        alert('The backup folder name cannot be empty.');
                    }
                    else if(root_path == '/')
                    {
                        alert('The backup folder name cannot be \'/\'.');
                    }
                    else if(path == '')
                    {
                        alert('The backup folder name cannot be empty.');
                    }
                    else if(path == '/')
                    {
                        alert('The backup folder name cannot be \'/\'.');
                    }
                    else if(use_remote_retention == '1' && backup_retain == ''){
                        alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                    }
                    else if(use_remote_retention == '1' && backup_db_retain == ''){
                        alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                    }
                    else if(use_remote_retention == '1' && backup_incremental_retain == ''){
                        alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                    }
                    else if(use_remote_retention == '1' && backup_rollback_retain == ''){
                        alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                    }
                    else
                    {
                        var ajax_data;
                        var remote_from = wpvivid_ajax_data_transfer('pcloud');
                        ajax_data = {
                            'action': 'wpvivid_pcloud_add_remote',
                            'remote': remote_from
                        };
                        jQuery('#wpvivid_pcloud_auth').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#wpvivid_remote_notice').html('');
                        wpvivid_post_request_addon(ajax_data, function (data)
                        {
                            try
                            {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success')
                                {
                                    jQuery('#wpvivid_pcloud_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('input:text[option=pcloud]').each(function(){
                                        jQuery(this).val('');
                                    });
                                    jQuery('input:password[option=pcloud]').each(function(){
                                        jQuery(this).val('');
                                    });
                                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote').'&action=wpvivid_pro_pcloud_drive&result=success'; ?>';
                                }
                                else if (jsonarray.result === 'failed')
                                {
                                    jQuery('#wpvivid_remote_notice').html(jsonarray.notice);
                                    jQuery('#wpvivid_pcloud_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                            }
                            catch (err)
                            {
                                alert(err);
                                jQuery('#wpvivid_pcloud_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                            }

                        }, function (XMLHttpRequest, textStatus, errorThrown)
                        {
                            var error_message = wpvivid_output_ajaxerror('adding the remote storage', textStatus, errorThrown);
                            alert(error_message);
                            jQuery('#wpvivid_pcloud_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                        });
                    }
                }

                jQuery('input:text[option=pcloud][name=chunk_size]').on("keyup", function(){
                    var regExp = /^([1-9]|1[0-9]|2[0-9]|30)$/g;
                    var input_value = jQuery('input:text[option=pcloud][name=chunk_size]').val();
                    if(!regExp.test(input_value) && input_value !== ''){
                        alert('Only a number from 1-30 is allowed.');
                        jQuery('input:text[option=pcloud][name=chunk_size]').val('');
                    }
                });
            </script>
            <?php
        }
        else
        {
            ?>
            <div id="storage_account_pcloud" class="storage-account-page">
                <div style="padding: 0 10px 10px 0;">
                    <strong>To add pCloud, please get pCloud authentication first. Once authenticated, you will be redirected to this page, then you can add storage information and save it</strong>
                </div>
                <table class="wp-list-table widefat plugins" style="width:100%;">
                    <tbody>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <select option="pcloud" name="area" style="margin-bottom:5px;">
                                    <option value="area_us">United States</option>
                                    <option value="area_eu">European Union</option>
                                </select>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Choose your pCloud account's region</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input onclick="wpvivid_pcloud_auth();" class="button-primary" type="submit" value="Authenticate with pCloud">
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Click to get pCloud authentication.</i>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div style="padding: 10px 0 0 0;">
                    <span>Tip: Get a 404 or 403 error after authorization? Please read this <a href="https://docs.wpvivid.com/http-403-error-authorizing-cloud-storage.html">doc</a>.</span>
                </div>
            </div>
            <script>
                function wpvivid_pcloud_auth()
                {
                    var area = 'area_us';
                    jQuery('select[option=pcloud]').each(function()
                    {
                        var type = jQuery(this).prop('name');
                        if(type === 'area')
                        {
                            area = jQuery(this).val();
                        }
                    });
                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote').'&action=wpvivid_pro_pcloud_auth&area='; ?>'+area;
                }
            </script>
            <?php
        }
    }

    public function wpvivid_edit_storage_page_pcloud()
    {
        ?>
        <div id="remote_storage_edit_pCloud">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your pCloud Information</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-pCloud" name="name" placeholder="Enter a unique alias: e.g. pCloud-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>A name to help you identify the storage if you have multiple remote storage connected.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" autocomplete="off" option="edit-pCloud" name="root_path" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i><?php echo sprintf(__('Customize a root directory in your pCloud for holding %s directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-pCloud" name="path" placeholder="pCloud Folder" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_/]/g,'')" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Customize the directory where you want to store backups. By default it takes your current website domain or url.</i>
                        </div>
                    </td>
                </tr>

                <!--<tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="edit-pCloud" name="backup_retain" value="30" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Total number of non-database only and non-incremental backup copies to be retained in this storage.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="edit-pCloud" name="backup_db_retain" value="30" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Total number of database backup copies to be retained in this storage.</i>
                        </div>
                    </td>
                </tr>-->
                <?php do_action('wpvivid_remote_storage_backup_retention', 'pCloud', 'edit'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-pCloud" name="chunk_size" placeholder="Chunk size" value="10" onkeyup="value=value.replace(/\D/g,'')" />MB
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>The block size of uploads and downloads. Reduce it if you encounter a timeout when transferring files.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input onclick="wpvivid_pcloud_drive_update_auth();" class="button-primary" type="submit" value="Save Changes">
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Click the button to save the changes.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <script>
            function wpvivid_pcloud_drive_update_auth()
            {
                var name='';
                var path = '';
                var root_path='';
                var chunk_size='';
                var backup_retain='';
                var backup_db_retain='';
                var backup_incremental_retain='';
                var backup_rollback_retain='';
                jQuery('input:text[option=edit-pCloud]').each(function()
                {
                    var key = jQuery(this).prop('name');
                    if(key==='name')
                    {
                        name = jQuery(this).val();
                    }
                    if(key==='path')
                    {
                        path = jQuery(this).val();
                    }
                    if(key==='root_path')
                    {
                        root_path = jQuery(this).val();
                    }
                    if(key==='chunk_size')
                    {
                        chunk_size = jQuery(this).val();
                    }
                    if(key==='backup_retain')
                    {
                        backup_retain = jQuery(this).val();
                    }
                    if(key==='backup_db_retain')
                    {
                        backup_db_retain = jQuery(this).val();
                    }
                    if(key==='backup_incremental_retain')
                    {
                        backup_incremental_retain = jQuery(this).val();
                    }
                    if(key==='backup_rollback_retain')
                    {
                        backup_rollback_retain = jQuery(this).val();
                    }
                });
                var use_remote_retention = '0';
                jQuery('input:checkbox[option=edit-pCloud][name=use_remote_retention]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        use_remote_retention = '1';
                    }
                    else
                    {
                        use_remote_retention = '0';
                    }
                });
                if(name == ''){
                    alert('Warning: An alias for remote storage is required.');
                }
                else if(root_path == '')
                {
                    alert('The backup folder name cannot be empty.');
                }
                else if(root_path == '/')
                {
                    alert('The backup folder name cannot be \'/\'.');
                }
                else if(path == '')
                {
                    alert('The backup folder name cannot be empty.');
                }
                else if(path == '/')
                {
                    alert('The backup folder name cannot be \'/\'.');
                }
                else if(use_remote_retention == '1' && backup_retain == ''){
                    alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                }
                else if(use_remote_retention == '1' && backup_db_retain == ''){
                    alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                }
                else if(use_remote_retention == '1' && backup_incremental_retain == ''){
                    alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                }
                else if(use_remote_retention == '1' && backup_rollback_retain == ''){
                    alert('Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.');
                }
                else
                {
                    wpvivid_edit_remote_storage();
                }
            }

            jQuery('input:text[option=edit-pCloud][name=chunk_size]').on("keyup", function(){
                var regExp = /^([1-9]|1[0-9]|2[0-9]|30)$/g;
                var input_value = jQuery('input:text[option=edit-pCloud][name=chunk_size]').val();
                if(!regExp.test(input_value) && input_value !== ''){
                    alert('Only a number from 1-30 is allowed.');
                    jQuery('input:text[option=edit-pCloud][name=chunk_size]').val('');
                }
            });
        </script>
        <?php
    }

    public function wpvivid_get_out_of_date_pcloud($out_of_date_remote, $remote)
    {
        if($remote['type'] == WPVIVID_REMOTE_PCLOUD)
        {
            $root_path=apply_filters('wpvivid_get_root_path', $remote['type']);
            $out_of_date_remote = $root_path.$remote['path'];
        }
        return $out_of_date_remote;
    }

    public function wpvivid_storage_provider_pcloud($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_PCLOUD)
        {
            $storage_type = 'pCloud';
        }
        return $storage_type;
    }

    public function wpvivid_get_root_path_pcloud($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_PCLOUD)
        {
            $storage_type = 'apps/wpvividbackuppro';
        }
        return $storage_type;
    }

    public function wpvivid_show_notice_add_pcloud_success(){
        _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the pCloud account as your remote storage.</p></div>');
    }

    public function wpvivid_show_notice_add_pcloud_error()
    {
        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_handle_remote_storage_error($_GET['resp_msg'], 'Add pCloud Remote');
        _e('<div class="notice notice-error"><p>'.$_GET['resp_msg'].'</p></div>');
    }

    public function scan_folder_backup($folder_type)
    {
        set_time_limit(120);
        $ret=array();
        $ret['path']=array();

        if($folder_type === 'Common')
        {
            $path=$this->options['path'];
            $response=$this->_scan_folder_backup($path);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                $ret['path']=$response['path'];
            }
            else
            {
                if(isset($response['code'])&&$response['code']==404)
                {
                    $ret['remote']=array();
                    $ret['path']=array();
                }
                else
                {
                    return $response;
                }
            }
        }
        else if($folder_type === 'Migrate')
        {
            $path='migrate';
            $response=$this->_scan_folder_backup($path);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }
            else
            {
                if(isset($response['code'])&&$response['code']==404)
                {
                    $ret['migrate']=array();
                }
                else
                {
                    return $response;
                }
            }
        }
        else if($folder_type === 'Rollback')
        {

            $remote_folder=$this->options['path'].'/rollback';
            $response=$this->_scan_folder_backup($remote_folder);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['rollback']=$response['backup'];
            }
            else
            {
                if(isset($response['code'])&&$response['code']==404)
                {
                    $ret['rollback']=array();
                }
                else
                {
                    return $response;
                }
            }
        }
        $ret['result']=WPVIVID_PRO_SUCCESS;

        return $ret;
    }

    public function scan_child_folder_backup($sub_path)
    {
        set_time_limit(120);
        $ret=array();

        $path=$this->options['path'];
        $response=$this->_scan_child_folder_backup($path,$sub_path);

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['remote']= $response['backup'];
        }
        else
        {
            if(isset($response['code'])&&$response['code']==404)
            {
                $ret['remote']=array();
            }
            else
            {
                return $response;
            }
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function _scan_folder_backup($path)
    {
        global $wpvivid_plugin;

        $ret=$this->check_folder($path);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $params = array(
            "folderid" => $folder_id
        );
        $response=$this->remote_get('listfolder',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            if(isset($response['body']['metadata']))
            {
                $metadata=$response['body']['metadata'];
                foreach ($metadata['contents'] as $data)
                {
                    if($data['isfolder']===true)
                    {
                        $ret['path'][]=$data['name'];
                    }
                    else
                    {
                        $file_data['file_name']=$data['name'];
                        $file_data['size']=$data['size'];
                        $files[]=$file_data;
                    }
                }

                if(!empty($files))
                {
                    global $wpvivid_backup_pro;
                    $ret['backup']=$wpvivid_backup_pro->func->get_backup($files);
                }
            }

            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function _scan_child_folder_backup($path,$sub_path)
    {
        global $wpvivid_plugin;

        $ret=$this->check_folder($path.'/'.$sub_path);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $params = array(
            "folderid" => $folder_id
        );

        $response=$this->remote_get('listfolder',$params);

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            $ret['files']=array();

            if(isset($response['body']['metadata']))
            {
                $metadata=$response['body']['metadata'];
                foreach ($metadata['contents'] as $data)
                {
                    if($data['isfolder']===true)
                    {
                        continue;
                    }
                    else
                    {
                        $file_data['file_name']=$data['name'];
                        $file_data['size']=$data['size'];
                        $file_data['remote_path']=$sub_path;
                        $ret['files'][]=$file_data;
                    }
                }

                if(!empty($ret['files']))
                {
                    global $wpvivid_backup_pro;
                    $ret['backup']=$wpvivid_backup_pro->func->get_backup($ret['files']);
                }
            }

            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function scan_folder_backup_ex($folder_type)
    {
        if($folder_type=='all_backup')
        {
            $ret['result']='success';
            $ret['remote']=array();

            $response=$this->_get_common_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                $path=$response['path'];
            }

            $ret['migrate']=array();

            $response=$this->_get_migrate_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }

            $ret['rollback']=array();

            $response=$this->_get_rollback_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['rollback']= $response['backup'];
            }

            $ret['incremental']=array();

            if(!empty($path))
            {
                foreach ($path as $incremental_path)
                {
                    if (preg_match('/.*_.*_.*_to_.*_.*_.*$/', $incremental_path))
                    {
                        $response=$this->_get_incremental_backups($incremental_path);
                        if($response['result']==WPVIVID_PRO_SUCCESS)
                        {
                            $ret['incremental']= array_merge($ret['incremental'],$response['backup']);
                        }
                    }
                }
            }
        }
        else if($folder_type=='Manual'||$folder_type=='Cron')
        {
            $ret['result']='success';
            $ret['remote']=array();

            $response=$this->_get_common_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
            }
            else if(isset($response['code'])&&$response['code']==404)
            {
                $ret['remote']=array();
                $ret['path']=array();
            }
            else
            {
                return $response;
            }

            $ret['migrate']=array();
            $ret['rollback']=array();
        }
        else if($folder_type=='Migrate')
        {
            $ret['result']='success';
            $ret['migrate']=array();

            $response=$this->_get_migrate_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }
            else if(isset($response['code'])&&$response['code']==404)
            {
                $ret['migrate']=array();
            }
            else
            {
                return $response;
            }
        }
        else if($folder_type=='Rollback')
        {
            $ret['result']='success';
            $ret['rollback']=array();

            $response=$this->_get_rollback_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['rollback']= $response['backup'];
            }
            else if(isset($response['code'])&&$response['code']==404)
            {
                $ret['rollback']=array();
            }
            else
            {
                return $response;
            }
        }
        else if($folder_type=='Incremental')
        {
            $ret['result']='success';

            $response=$this->_get_common_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $path=$response['path'];
            }
            else
            {
                return $response;
            }

            $ret['remote']=array();
            $ret['migrate']=array();
            $ret['rollback']=array();

            $ret['incremental']=array();

            if(!empty($path))
            {
                foreach ($path as $incremental_path)
                {
                    if (preg_match('/.*_.*_.*_to_.*_.*_.*$/', $incremental_path))
                    {
                        $response=$this->_get_incremental_backups($incremental_path);
                        $ret['incremental']= array_merge($ret['incremental'],$response['backup']);
                    }
                }
            }
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        return $ret;
    }

    public function _get_common_backups()
    {
        $path=$this->options['path'];

        return $this->_scan_folder_backup($path);
    }

    public function _get_migrate_backups()
    {
        $path='migrate';

        return $this->_scan_folder_backup($path);
    }

    public function _get_rollback_backups()
    {
        $path=$this->options['path'].'/rollback';

        return $this->_scan_folder_backup($path);
    }

    public function _get_incremental_backups($incremental_path)
    {
        $path=$this->options['path'].'/'.$incremental_path;

        $ret=$this->_scan_folder_backup($path);
        if($ret['result']==WPVIVID_PRO_SUCCESS)
        {
            foreach ($ret['backup'] as  $id=>$backup_data)
            {
                $ret['backup'][$id]['incremental_path']=$incremental_path;
            }
        }
        return $ret;
    }

    public function scan_rollback($type)
    {
        if($type === 'plugins')
        {
            $ret=$this->get_rollback_type_folder($type);
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }

            $folder_id=$ret['folder_id'];
            $response=$this->_scan_folder($folder_id);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['rollback']= $response['path'];
            }
            else
            {
                $ret['rollback']=array();
            }
        }
        else if($type === 'themes')
        {
            $ret=$this->get_rollback_type_folder($type);
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }

            $folder_id=$ret['folder_id'];
            $response=$this->_scan_folder($folder_id);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['rollback']= $response['path'];
            }
            else
            {
                $ret['rollback']=array();
            }
        }

        $ret['result']='success';
        return $ret;
    }

    public function _scan_folder($folder_id)
    {
        $params = array(
            "folderid" => $folder_id
        );
        $response=$this->remote_get('listfolder',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['path']=array();
            if(isset($response['body']['metadata']))
            {
                $metadata=$response['body']['metadata'];
                foreach ($metadata['contents'] as $data)
                {
                    if($data['isfolder']===true)
                    {
                        $ret['path'][]=$data['name'];
                    }
                }
            }

            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function get_rollback_type_folder($folder)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $ret=$this->get_folder_id($root_path,0);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $path=$this->options['path'];
        $root_folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($path,$root_folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id("rollback_ex",$folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($folder,$folder_id);

        return $ret;
    }

    public function get_rollback_slug_folder($folder,$slug)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $ret=$this->get_folder_id($root_path,0);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $path=$this->options['path'];
        $root_folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($path,$root_folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id("rollback_ex",$folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($folder,$folder_id);
        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];
        $ret=$this->get_folder_id($slug,$folder_id);

        return $ret;
    }

    public function get_rollback_data($type,$slug)
    {
        if($type === 'plugins')
        {
            $ret=$this->get_rollback_slug_folder($type,$slug);
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }

            $folder_id=$ret['folder_id'];
            $response=$this->_scan_folder($folder_id);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path= $response['path'];
                if(!empty($path))
                {
                    foreach ($path as $version)
                    {
                        $response=$this->_scan_file($folder_id,$version,$slug);
                        if($response['result']=='success')
                        {
                            $ret['data']['version'][$version]['upload']=true;
                            $ret['data']['version'][$version]['file']['file_name']=$slug.'.zip';
                            $ret['data']['version'][$version]['file']['size']=$response['file']['size'];
                            $ret['data']['version'][$version]['file']['modified']=$response['file']['mtime'];
                        }
                    }
                }
            }
            else
            {
                $ret['data']=array();
            }
        }
        else if($type === 'themes')
        {
            $ret=$this->get_rollback_slug_folder($type,$slug);
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }

            $folder_id=$ret['folder_id'];
            $response=$this->_scan_folder($folder_id);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path= $response['path'];
                if(!empty($path))
                {
                    foreach ($path as $version)
                    {
                        $response=$this->_scan_file($folder_id,$version,$slug);
                        if($response['result']=='success')
                        {
                            $ret['data']['version'][$version]['upload']=true;
                            $ret['data']['version'][$version]['file']['file_name']=$slug.'.zip';
                            $ret['data']['version'][$version]['file']['size']=$response['file']['size'];
                            $ret['data']['version'][$version]['file']['modified']=$response['file']['mtime'];
                        }
                    }
                }
            }
            else
            {
                $ret['data']=array();
            }
        }

        $ret['result']='success';
        return $ret;
    }

    public function _scan_file($folder_id,$version,$slug)
    {
        $ret=$this->get_folder_id($version,$folder_id);

        if($ret['result']==WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $params = array(
            "folderid" => $folder_id
        );
        $response=$this->remote_get('listfolder',$params);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $file_name=$slug.'.zip';
            if(isset($response['body']['metadata']))
            {
                $metadata=$response['body']['metadata'];
                foreach ($metadata['contents'] as $data)
                {
                    if($data['isfolder']===true)
                    {
                        continue;
                    }
                    else
                    {
                        if($data['name']==$file_name)
                        {
                            $file_data['file_name']=$data['name'];
                            $file_data['size']=$data['size'];
                            $file_data['mtime']=strtotime($data['modified']);
                            $ret['file']=$file_data;
                            break;
                        }
                    }
                }
            }

            if(!isset($ret['file']))
            {
                $ret['result']='failed';
                $ret['error']='Failed to get file information.';
                return $ret;
            }

            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function get_backup_info($backup_info_file,$folder_type,$incremental_path='')
    {
        set_time_limit(120);

        if($folder_type=='Manual')
        {
            $path=$this->options['path'];
        }
        else if($folder_type=='Migrate')
        {
            $path='migrate';
        }
        else if($folder_type=='Rollback')
        {
            $path=$this->options['path'].'/rollback';
        }
        else if($folder_type=='Incremental')
        {
            $path=$this->options['path'].'/'.$incremental_path;
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        $ret=$this->check_folder($path);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $folder_id=$ret['folder_id'];

        $ret=$this->get_file_id($backup_info_file,$folder_id);
        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $local_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$backup_info_file;
        @unlink($local_path);
        $local_handle = fopen($local_path, 'a');

        $file_id=$ret['file_id'];
        $ret = $this->get_link($file_id);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $link=$ret['link'];

        $chunk_size=$this->download_chunk_size;

        $source = fopen($link, "rb");

        if(!$source)
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            return $ret;
        }
        else
        {
            $downloaded_start=0;
            while (!feof($source))
            {
                $content = fread($source, $chunk_size);
                fwrite($local_handle, $content);
                $downloaded_start+=strlen($content);
            }
            fclose($local_handle);
            fclose($source);

            $ret['result']='success';
            $ret['backup_info']=json_decode(file_get_contents($local_path),1);
            @unlink($local_path);
            return $ret;
        }
    }

    public function delete_old_backup($backup_count,$db_count)
    {
        $ret=array();

        $path=$this->options['path'];
        $response=$this->_scan_folder_backup($path);

        if(isset($response['backup'])||isset($response['path']))
        {
            $backups=$response['backup'];
            $folders=$response['path'];

            global $wpvivid_backup_pro;
            $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
            $folders_count=apply_filters('wpvivid_get_backup_folders_count',0);
            $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);

            foreach ($folders as $folder)
            {
                $child_response=$this->_scan_child_folder_backup($path,$folder);
                if(isset($child_response['files']))
                {
                    $files=array_merge($files,$child_response['files']);
                }
            }
            if(!empty($files))
            {
                $this->cleanup($files);
            }

            if(!empty($folders))
            {
                $this->cleanup_folder($folders);
            }
        }

        $path=$this->options['path'].'/rollback';
        $response=$this->_scan_folder_backup($path);

        if(isset($response['backup']))
        {
            $backups=$response['backup'];

            global $wpvivid_backup_pro;
            $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
            if(!empty($files))
            {
                $this->cleanup($files);
            }
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function check_old_backups($backup_count,$db_count,$folder_type='Common')
    {
        $path = $this->options['path'];

        if($folder_type === 'Common')
        {
            $path=$this->options['path'];
        }
        else if($folder_type === 'Rollback')
        {

            $path=$this->options['path'].'/rollback';
        }

        $response = $this->_scan_folder_backup($path);

        if (isset($response['backup']))
        {
            $backups = $response['backup'];

            global $wpvivid_backup_pro;
            $files = $wpvivid_backup_pro->func->get_old_backup_files($backups, $backup_count,$db_count);
            if (!empty($files))
            {
                return true;
            }
            else if(isset($response['path'])&&$folder_type=== 'Common')
            {
                $folders=$response['path'];
                $folders_count=apply_filters('wpvivid_get_backup_folders_count',0);
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);
                if(!empty($folders))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    public function finish_add_remote()
    {
        global $wpvivid_backup_pro,$wpvivid_plugin;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-remote');
        try {
            if (empty($_POST) || !isset($_POST['remote']) || !is_string($_POST['remote'])) {
                die();
            }

            $tmp_remote_options = get_transient('pcloud_auth_id');
            if($tmp_remote_options === false)
            {
                die();
            }
            delete_transient('pcloud_auth_id');
            if(empty($tmp_remote_options)||$tmp_remote_options['type']!==WPVIVID_REMOTE_PCLOUD)
            {
                die();
            }

            $json = $_POST['remote'];
            $json = stripslashes($json);
            $remote_options = json_decode($json, true);
            if (is_null($remote_options)) {
                die();
            }

            $remote_options=array_merge($remote_options,$tmp_remote_options);

            $remote_collection=new WPvivid_Remote_collection_addon();
            $ret = $remote_collection->add_remote($remote_options);

            if ($ret['result'] == 'success') {
                $html = '';
                $html = apply_filters('wpvivid_add_remote_storage_list', $html);
                $ret['html'] = $html;
                $pic = '';
                $pic = apply_filters('wpvivid_schedule_add_remote_pic', $pic);
                $ret['pic'] = $pic;
                $dir = '';
                $dir = apply_filters('wpvivid_get_remote_directory', $dir);
                $ret['dir'] = $dir;
                $schedule_local_remote = '';
                $schedule_local_remote = apply_filters('wpvivid_schedule_local_remote', $schedule_local_remote);
                $ret['local_remote'] = $schedule_local_remote;
                $remote_storage = '';
                $remote_storage = apply_filters('wpvivid_remote_storage', $remote_storage);
                $ret['remote_storage'] = $remote_storage;
                $remote_select_part = '';
                $remote_select_part = apply_filters('wpvivid_remote_storage_select_part', $remote_select_part);
                $ret['remote_select_part'] = $remote_select_part;
                $default = array();
                $remote_array = apply_filters('wpvivid_archieve_remote_array', $default);
                $ret['remote_array'] = $remote_array;
                $success_msg = __('You have successfully added a remote storage.', 'wpvivid-backuprestore');
                $ret['notice'] = apply_filters('wpvivid_add_remote_notice', true, $success_msg);
            }
            else{
                $ret['notice'] = apply_filters('wpvivid_add_remote_notice', false, $ret['error']);
            }

        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        echo json_encode($ret);
        die();
    }

    public function delete_old_backup_ex($type,$backup_count,$db_count)
    {
        $ret=array();

        if($type=='Rollback')
        {
            $path=$this->options['path'].'/rollback';
            $response=$this->_scan_folder_backup($path);

            if(isset($response['backup']))
            {
                $backups=$response['backup'];

                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
                if(!empty($files))
                {
                    global $wpvivid_backup_pro;
                    $backup_info_array=$wpvivid_backup_pro->func->get_backup($files);
                    if(isset($backup_info_array) && !empty($backup_info_array))
                    {
                        $backup_list=new WPvivid_New_BackupList();
                        foreach ($backup_info_array as $backup_id => $backup_info)
                        {
                            $backup_list->delete_backup($backup_id,$this->options['id']);
                        }
                    }
                    $this->cleanup($files);
                }
            }
        }
        else if($type=='Incremental')
        {
            $path=$this->options['path'];
            $response=$this->_scan_folder_backup($path);

            if(isset($response['path']))
            {
                $folders=$response['path'];

                global $wpvivid_backup_pro;
                $files = array();
                $folders_count=$backup_count;
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);

                foreach ($folders as $folder)
                {
                    $child_response=$this->_scan_child_folder_backup($path,$folder);
                    if(isset($child_response['files']))
                    {
                        $files=array_merge($files,$child_response['files']);
                    }
                }
                if(!empty($files))
                {
                    global $wpvivid_backup_pro;
                    $backup_info_array=$wpvivid_backup_pro->func->get_backup($files);
                    if(isset($backup_info_array) && !empty($backup_info_array))
                    {
                        $backup_list=new WPvivid_New_BackupList();
                        foreach ($backup_info_array as $backup_id => $backup_info)
                        {
                            $backup_list->delete_backup($backup_id,$this->options['id']);
                        }
                    }
                    $this->cleanup($files);
                }

                if(!empty($folders))
                {
                    $this->cleanup_folder($folders);
                }
            }
        }
        else
        {
            $path=$this->options['path'];
            $response=$this->_scan_folder_backup($path);

            if(isset($response['backup']))
            {
                $backups=$response['backup'];

                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
                if(!empty($files))
                {
                    global $wpvivid_backup_pro;
                    $backup_info_array=$wpvivid_backup_pro->func->get_backup($files);
                    if(isset($backup_info_array) && !empty($backup_info_array))
                    {
                        $backup_list=new WPvivid_New_BackupList();
                        foreach ($backup_info_array as $backup_id => $backup_info)
                        {
                            $backup_list->delete_backup($backup_id,$this->options['id']);
                        }
                    }
                    $this->cleanup($files);
                }
            }
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function check_old_backups_ex($type,$backup_count,$db_count)
    {
        if($type=='Rollback')
        {
            $path=$this->options['path'].'/rollback';
            $response=$this->_scan_folder_backup($path);

            if(isset($response['backup']))
            {
                $backups=$response['backup'];

                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
                if(!empty($files))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else if($type=='Incremental')
        {
            $path=$this->options['path'];
            $response=$this->_scan_folder_backup($path);

            if(isset($response['path']))
            {
                $folders=$response['path'];

                global $wpvivid_backup_pro;
                $folders_count=$backup_count;
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);

                if(!empty($folders))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            $path=$this->options['path'];
            $response=$this->_scan_folder_backup($path);

            if(isset($response['backup']))
            {
                $backups=$response['backup'];

                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
                if(!empty($files))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        return false;
    }
}