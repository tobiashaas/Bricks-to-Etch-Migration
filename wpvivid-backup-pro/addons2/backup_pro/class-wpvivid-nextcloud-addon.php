<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_Nextcloud_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_REMOTE_NEXTCLOUD'))
    define('WPVIVID_REMOTE_NEXTCLOUD','nextcloud');
if(!defined('WPVIVID_NEXTCLOUD_DEFAULT_FOLDER'))
    define('WPVIVID_NEXTCLOUD_DEFAULT_FOLDER','/');

class WPvivid_Nextcloud_addon extends WPvivid_Remote_addon
{
    public $options;
    private $chunk_size = 3*1024*1024;
    public $callback;
    private $url;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('WPVIVID_INIT_STORAGE_TAB_NEXTCLOUD'))
            {
                add_filter('wpvivid_get_out_of_date_remote',array($this,'get_out_of_date_nextcloud'),10,2);
                add_action('wpvivid_edit_remote_page',array($this,'edit_storage_page_nextcloud'), 18);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'),11);
                define('WPVIVID_INIT_STORAGE_TAB_NEXTCLOUD',1);
            }
        }
        else
        {
            $this->options=$options;
        }
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_NEXTCLOUD] = 'WPvivid_Nextcloud_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_NEXTCLOUD)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function add_storage_page_nextcloud()
    {
        global $wpvivid_backup_pro;
        ?>
        <div id="storage_account_nextcloud"  class="storage-account-page">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Nextcloud Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="nextcloud" name="name" placeholder="Enter a unique alias: e.g. NEXTCLOUD-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" autocomplete="off" option="nextcloud" name="host" placeholder="Host" style="width:220px;" />
                                <span style="width:10px;">/</span>
                                <input type="text" value="remote.php/dav" style="width:120px;" readonly="readonly" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the storage hostname.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="nextcloud" name="username" placeholder="Username" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the username.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="off" option="nextcloud" name="password" placeholder="Password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="nextcloud" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize a root directory in the storage for holding WPvivid backup directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="nextcloud" name="path" placeholder="Custom Path" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize the name of folder under the parent folder where you want to store %s backups.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'nextcloud', 'add'); ?>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="nextcloud" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
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
                                    <input type="checkbox" option="nextcloud" name="default" checked />Set as the default remote storage.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Once checked, all this sites backups sent to a remote storage destination will be uploaded to this storage by default.</i>
                            </div>
                        </td>
                    </tr>
                </form>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input class="button-primary" option="add-remote" type="submit" value="Test and Add" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Click the button to connect to Nextcloud storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function edit_storage_page_nextcloud()
    {
        global $wpvivid_backup_pro;
        ?>
        <div id="remote_storage_edit_nextcloud">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Nextcloud Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud" name="name" placeholder="Enter a unique alias: e.g. NEXTCLOUD-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud" name="host" placeholder="Host" style="width:220px;" />
                                <span style="width:10px;">/</span>
                                <input type="text" value="remote.php/dav" style="width:120px;" readonly="readonly" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the storage hostname.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud" name="username" placeholder="Username" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the username.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="off" option="edit-nextcloud" name="password" placeholder="Password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize a root directory in the storage for holding WPvivid backup directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud" name="path" placeholder="Custom Path" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize the name of folder under the parent folder where you want to store %s backups.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'nextcloud', 'edit'); ?>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-nextcloud" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
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
                                    <input type="checkbox" option="edit-nextcloud" name="default" checked />Set as the default remote storage.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Once checked, all this sites backups sent to a remote storage destination will be uploaded to this storage by default.</i>
                            </div>
                        </td>
                    </tr>
                </form>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input class="button-primary" option="edit-remote" type="submit" value="Save Changes" />
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
        <?php
    }

    public function test_connect()
    {
        try
        {
            $url=$this->get_remote_url();
            $ret=$this->remote_mkdir($url.'/'.$this->options['root_path']);
            if($ret['result']=='failed')
            {
                return $ret;
            }
            $ret=$this->remote_mkdir($url.'/'.$this->options['root_path'].'/'.$this->options['path']);
            if($ret['result']=='failed')
            {
                return $ret;
            }
            $url=$url.'/'.$this->options['root_path'].'/'.$this->options['path'];

            $test_file = $url.'/'.md5(time().rand()).'.txt';
            if ($this->remote_open($test_file))
            {
                $this->remote_unlink($test_file);
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Failed to create a test file. Please make sure the storage credentials you enter are correct and the folder has sufficient permissions.';
                $ret['test']=$url;
                return $ret;
            }

            $ret['result']='success';
            return $ret;
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']='Exception:'.$e->getMessage();
            return $ret;
        }
    }

    public function get_remote_url()
    {
        //$url = parse_url($this->options['host']);
        //$real_url=$url['scheme'].'://'.$url['host'];
        $real_url=untrailingslashit($this->options['host']);
        $real_url.='/remote.php/dav/files/'.$this->options['username'];

        return $real_url;
    }

    public function get_upload_url($file_name)
    {
        $file_uid='wpvivid-'.md5($file_name);

        $real_url=untrailingslashit($this->options['host']);
        $real_url.='/remote.php/dav/uploads/'.$this->options['username'].'/'.$file_uid;

        $this->remote_mkdir($real_url);
        return $real_url;
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

        if(!isset($this->options['host']))
        {
            $ret['error']="Warning: The hostname for Nextcloud is required.";
            return $ret;
        }

        $this->options['host']=sanitize_text_field($this->options['host']);

        if(empty($this->options['username']))
        {
            $ret['error']="Warning: The username for Nextcloud is required.";
            return $ret;
        }

        if(!isset($this->options['password']))
        {
            $ret['error']="Warning: The password is required.";
            return $ret;
        }

        $this->options['password']=sanitize_text_field($this->options['password']);

        if(empty($this->options['password']))
        {
            $ret['error']="Warning: The password is required.";
            return $ret;
        }

        if(isset($this->options['port']))
        {
            $this->options['port']=sanitize_text_field($this->options['port']);
        }

        if(!isset($this->options['root_path'])||empty($this->options['root_path']))
        {
            $this->options['root_path']='';
            $this->options['is_empty_root_path']=true;
        }
        else
        {
            $this->options['root_path']=sanitize_text_field($this->options['root_path']);
        }


        if(!isset($this->options['path']))
        {
            $ret['error']="Warning: A directory name is required.";
            return $ret;
        }

        $this->options['path']=sanitize_text_field($this->options['path']);

        if(empty($this->options['path'])){
            $ret['error']="Warning: A directory name is required.";
            return $ret;
        }

        if (!isset($this->options['backup_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }

        $this->options['backup_retain'] = sanitize_text_field($this->options['backup_retain']);

        if (empty($this->options['backup_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }

        if (!isset($this->options['backup_db_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }

        $this->options['backup_db_retain'] = sanitize_text_field($this->options['backup_db_retain']);

        if (empty($this->options['backup_db_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }

        //
        if (!isset($this->options['backup_incremental_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }

        $this->options['backup_incremental_retain'] = sanitize_text_field($this->options['backup_incremental_retain']);

        if (empty($this->options['backup_incremental_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }

        if (!isset($this->options['backup_rollback_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }

        $this->options['backup_rollback_retain'] = sanitize_text_field($this->options['backup_rollback_retain']);

        if (empty($this->options['backup_rollback_retain'])) {
            $ret['error'] = "Warning: You have not set the backup retention policy for this storage. Please set the policy or uncheck the option.";
            return $ret;
        }
        //

        $ret['result']=WPVIVID_PRO_SUCCESS;
        $ret['options']=$this->options;
        return $ret;
    }

    public function upload($task_id, $files, $callback = '')
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);

        if(empty($upload_job))
        {
            $job_data=array();
            foreach ($files as $file)
            {
                $file_data['size']=filesize($file);
                $file_data['uploaded']=0;
                $file_data['offset']=0;
                $job_data[basename($file)]=$file_data;
            }
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading',$job_data);
            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        }

        $this->url=$this->get_remote_url();

        $ret=$this->check_folder();
        if($ret['result']!='success')
        {
            return $ret;
        }

        $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];

        foreach ($files as $file)
        {
            if(is_array($upload_job['job_data'])&&array_key_exists(basename($file),$upload_job['job_data']))
            {
                if($upload_job['job_data'][basename($file)]['uploaded']==1)
                    continue;
            }

            $this -> last_time = time();
            $this -> last_size = 0;

            if(!file_exists($file))
                return array('result' =>WPVIVID_PRO_FAILED,'error' =>$file.' not found. The file might has been moved, renamed or deleted. Please reload the list and verify the file exists.');
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start uploading '.basename($file),'notice');
            $wpvivid_plugin->set_time_limit($task_id);
            $result=$this->_upload($task_id,$file,$callback);
            if($result['result'] !==WPVIVID_PRO_SUCCESS)
            {
                return $result;
            }
            else
            {
                WPvivid_Custom_Interface_addon::wpvivid_reset_backup_retry_times($task_id);
            }
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    function isNextcloudWebDAV()
    {
        $baseUrl  = untrailingslashit($this->options['host']);
        $username = $this->options['username'];
        $password = $this->options['password'];

        $result = [
            'is_nextcloud' => false,
            'detected_by' => null,
            'version' => null,
            'raw_response' => null,
        ];

        //method 1
        $ocsUrl = rtrim($baseUrl, '/') . '/ocs/v2.php/cloud/capabilities?format=json';
        $ch = curl_init($ocsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'OCS-APIRequest: true'
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result['raw_response'] = $response;

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['ocs']['data']['server']['version'])) {
                $result['is_nextcloud'] = true;
                $result['detected_by'] = 'ocs';
                $result['version'] = $data['ocs']['data']['server']['version'];
                return $result;
            }
        }

        //method 2
        $webdavUrl = rtrim($baseUrl, '/') . '/remote.php/webdav/';

        $xmlRequest = <<<XML
<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop><d:current-user-principal/></d:prop>
</d:propfind>
XML;

        $ch = curl_init($webdavUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Depth: 0',
            'Content-Type: application/xml',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
        $response = curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        $result['raw_response'] = $response;

        if (stripos($response, 'nextcloud') !== false || stripos($response, 'nc:') !== false) {
            $result['is_nextcloud'] = true;
            $result['detected_by'] = 'propfind';
        }

        return $result;
    }

    public function _upload($task_id,$file,$callback)
    {
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        $this -> current_file_size = filesize($file);
        $this -> current_file_name = basename($file);

        if($this -> current_file_size > $this -> chunk_size)
        {
            //$file_url=$this->url.'/'.basename($file);
            try
            {
                $resume_url=$this->get_upload_url(basename($file));
                $offset= $upload_job['job_data'][basename($file)]['offset'];

                $ret=$this->upload_resume($task_id,$resume_url,$file,$offset,$callback);
                if($ret['result'] === WPVIVID_PRO_FAILED && (isset($ret['http_code']) && $ret['http_code']===400))
                {
                    global $wpvivid_backup_pro;
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload http code: '.$ret['http_code'],'notice');
                    $check_is_nextcloud = $this->isNextcloudWebDAV();
                    if($check_is_nextcloud['is_nextcloud'] === true)
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check is Nextcloud, start upload resume nextcloud','notice');
                        $resume_url=$this->get_upload_url(basename($file));
                        $ret=$this->upload_resume($task_id,$resume_url,$file,$offset,$callback,true);
                    }
                }
            }
            catch (Exception $e)
            {
                $ret['result']='failed';
                $ret['error']=$e->getMessage();
                return $ret;
            }

        }
        else
        {
            $ret = $this -> upload_file($file);
            if($ret['result']!='success')
            {
                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_FAILED,'Uploading '.basename($file).' failed.',$upload_job['job_data']);
            }
            else
            {
                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
            }
        }

        return $ret;
    }

    public function upload_resume($task_id,$resume_url,$file,$offset,$callback,$nextcloud_method=false)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);

        $fb=fopen($file,'rb');

        $file_size=filesize($file);
        //$upload_size=$this->chunk_size;
        $upload_size=1*1024*1024;
        $upload_end=min($offset+$upload_size-1,$file_size-1);
        //$file_url=$this->url.'/'.basename($file);
        while(true)
        {
            if($nextcloud_method)
            {
                $ret=$this->upload_chunk_ex($resume_url,$fb,$offset,$upload_end,$upload_size,$file_size);
            }
            else
            {
                $ret=$this->upload_chunk($resume_url,$fb,$offset,$upload_end,$upload_size,$file_size);
            }

            if($ret['result']==WPVIVID_SUCCESS)
            {
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

                $upload_job['job_data'][basename($file)]['offset']=$offset;
                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Uploading '.basename($file).' offset:'.size_format(min($offset,$file_size)),$upload_job['job_data']);
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('uploading '.basename($file).' offset:'.size_format(min($offset,$file_size)),'notice');
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
                fclose($fb);
                $this->remove_upload_url($resume_url);
                return $ret;
            }

        }

        fclose($fb);
        $ret = $this->upload_finish($resume_url,$file);
        if($ret['result']==WPVIVID_SUCCESS)
        {
            $upload_job['job_data'][basename($file)]['uploaded']=1;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
            return array('result' =>WPVIVID_SUCCESS);
        }
        else
        {
            $this->remove_upload_url($resume_url);
            return $ret;
        }
    }

    public function upload_chunk_ex($url,$file_handle,&$uploaded,&$upload_end,$upload_size,$file_size,$retry_count=0)
    {
        $upload_size=min($upload_size,$file_size-$uploaded);

        if ($uploaded)
            fseek($file_handle, $uploaded);

        //
        $chunk_data = fread($file_handle, $upload_size);
        $chunk_url=$url.'/'.str_pad($uploaded, 15, '0', STR_PAD_LEFT).'-'.str_pad($upload_end, 15, '0', STR_PAD_LEFT);

        $curl = curl_init($chunk_url);
        curl_setopt($curl, CURLOPT_USERPWD, "{$this->options['username']}:{$this->options['password']}");
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $mem = fopen('php://temp', 'rw+');
        fwrite($mem, $chunk_data);
        rewind($mem);
        curl_setopt($curl, CURLOPT_INFILE, $mem);
        curl_setopt($curl, CURLOPT_INFILESIZE, strlen($chunk_data));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        fclose($mem);

        if($response!==false)
        {
            curl_close($curl);
            if($http_code==200||$http_code==201||$http_code==204)
            {
                $uploaded += $upload_size;
                $upload_end=min($uploaded+$upload_size-1,$file_size-1);

                if ($uploaded >= $file_size)
                {
                    $ret['result']=WPVIVID_SUCCESS;
                    $ret['op']='finished';
                }
                else
                {
                    $ret['result']=WPVIVID_SUCCESS;
                    $ret['op']='continue';
                }

                return $ret;
            }
            else if($http_code==400)
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
                return $ret;
            }
            else if($http_code==501)
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
                return $ret;
            }
            else
            {
                if($retry_count<WPVIVID_ONEDRIVE_RETRY_TIMES)
                {
                    $retry_count++;
                    return $this->upload_chunk($url,$file_handle,$uploaded,$upload_end,$upload_size,$file_size,$retry_count);
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='Uploading files failed, error code: '.$http_code;
                    return $ret;
                }
            }
        }
        else
        {
            if($retry_count<6)
            {
                $retry_count++;
                return $this->upload_chunk($url,$file_handle,$uploaded,$upload_end,$upload_size,$file_size,$retry_count);
            }
            else
            {
                $ret['result']='failed';
                $ret['error']=curl_error($curl);
                curl_close($curl);
                return $ret;
            }
        }
    }

    public function upload_chunk($url,$file_handle,&$uploaded,&$upload_end,$upload_size,$file_size,$retry_count=0)
    {
        $curl = curl_init();


        $upload_size=min($upload_size,$file_size-$uploaded);

        if ($uploaded)
            fseek($file_handle, $uploaded);

        $file_url=$url.'/'.str_pad($uploaded, 15, '0', STR_PAD_LEFT).'-'.str_pad($upload_end, 15, '0', STR_PAD_LEFT);
        $headers = array(
            "Content-Length: $upload_size",
            "Content-Type: application/octet-stream",

        );

        $options = array(
            CURLOPT_URL        => $file_url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_PUT        => true,
            CURLOPT_INFILE     => $file_handle,
            CURLOPT_INFILESIZE => $upload_size,
            CURLOPT_RETURNTRANSFER=>true,
        );

        curl_setopt_array($curl, $options);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($response!==false)
        {
            curl_close($curl);
            if($http_code==200||$http_code==201||$http_code==204)
            {
                $uploaded += $upload_size;
                $upload_end=min($uploaded+$upload_size-1,$file_size-1);

                if ($uploaded >= $file_size)
                {
                    $ret['result']=WPVIVID_SUCCESS;
                    $ret['op']='finished';
                }
                else
                {
                    $ret['result']=WPVIVID_SUCCESS;
                    $ret['op']='continue';
                }

                return $ret;
            }
            else if($http_code==400)
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
                $ret['http_code']=$http_code;
                return $ret;
            }
            else if($http_code==501)
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
                return $ret;
            }
            else
            {
                if($retry_count<WPVIVID_ONEDRIVE_RETRY_TIMES)
                {
                    $retry_count++;
                    return $this->upload_chunk($url,$file_handle,$uploaded,$upload_end,$upload_size,$file_size,$retry_count);
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='Uploading files failed, error code: '.$http_code;
                    return $ret;
                }
            }
        }
        else
        {
            if($retry_count<6)
            {
                $retry_count++;
                return $this->upload_chunk($url,$file_handle,$uploaded,$upload_end,$upload_size,$file_size,$retry_count);
            }
            else
            {
                $ret['result']='failed';
                $ret['error']=curl_error($curl);
                curl_close($curl);
                return $ret;
            }
        }

    }

    public function upload_finish($resume_url,$file)
    {
        $file_url=$this->url.'/'.basename($file);
        $curl = curl_init();
        $headers = array(
            "Destination:".$file_url,
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'MOVE');
        curl_setopt($curl, CURLOPT_URL, $resume_url.'/.file');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);

        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($response!==false)
        {
            curl_close($curl);
            if($http_code==200||$http_code==201||$http_code==204||$http_code==423)
            {
                $ret['result']=WPVIVID_SUCCESS;
                return $ret;
            }
            else if($http_code==400)
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
                return $ret;
            }
            else if($http_code==501)
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
                return $ret;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
            curl_close($curl);
            return $ret;
        }
    }

    public function check_folder()
    {
        $ret=$this->remote_mkdir($this->url.'/'.$this->options['root_path']);
        if($ret['result']=='failed')
        {
            return $ret;
        }

        $ret=$this->remote_mkdir($this->url.'/'.$this->options['root_path'].'/'.$this->options['path']);
        if($ret['result']=='failed')
        {
            return $ret;
        }

        $ret['result']='success';
        return $ret;
    }

    public function upload_file($file)
    {
        $fp=fopen($file,'rb');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$this->url.'/'.basename($file));
        curl_setopt($curl, CURLOPT_INFILE, $fp);
        curl_setopt($curl, CURLOPT_INFILESIZE, filesize($file));
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec ($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!==false)
        {
            if($http_code==200||$http_code==201||$http_code==206||$http_code==416)
            {
                $ret['result']='success';
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Uploading files failed, error code: '.$http_code;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
        }
        curl_close ($curl);

        return $ret;
    }

    public function chunk_download($download_info,$callback)
    {
        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];
        $local_path = $download_info['local_path'];
        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url=$this->url.'/'.$this->options['path'].'/'.$download_info['file_name'];
        }
        else
        {
            $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/'.$download_info['file_name'];
        }

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;
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

        $time_limit = 30;
        $start_time = time();

        while ($start_offset < $this->current_file_size)
        {
            $last_byte = min($start_offset + $download_chunk_size - 1, $this->current_file_size);

            $ret = $this->download_file_part($this->url,$start_offset,$last_byte);
            if ($ret['result']=='failed')
            {
                return array('result' => 'failed', 'error' => 'Downloading ' . basename($local_path) . ' failed.' . $ret['error']);
            }

            if (!fwrite($fh, $ret['content']))
            {
                return array('result' =>  'failed', 'error' => 'Downloading ' . basename($local_path)  . ' failed.fwrite failed');
            }

            clearstatcache();
            $state = stat($local_path);
            $start_offset = $state['size'];

            if((time() - $this -> last_time) >3)
            {
                if(is_callable($callback))
                {
                    call_user_func_array($callback,array($start_offset,$this -> current_file_name,
                        $this->current_file_size,$this -> last_time,$this -> last_size));
                }
                $this -> last_size = $start_offset;
                $this -> last_time = time();
            }

            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                @fclose($fh);
                $result['result']='success';
                $result['finished']=0;
                $result['offset']=$start_offset;
                return $result;
            }
        }

        @fclose($fh);
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

    public function download($file, $local_path, $callback = '')
    {
        $this -> current_file_name = $file['file_name'];
        $this -> current_file_size = $file['size'];
        $this->callback=$callback;
        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Remote type: Nextcloud.','notice');

        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url=$this->url.'/'.$this->options['path'];
        }
        else
        {
            $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        if(isset($file['remote_path']))
        {
            $this->url=$this->url.'/'.$file['remote_path'];
        }

        $file_path=$local_path.$file['file_name'];
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'] ,'notice');

        return $this->download_file($this->url.'/'.$file['file_name'],$file_path,$file['size']);
    }

    public function download_file($url,$local_path,$size)
    {
        global $wpvivid_plugin;

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;

        $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.','notice');

        $fh = fopen($local_path, 'a');

        $this->current_file_size = $size;
        $download_chunk_size = 1*1024*1024;

        while ($start_offset < $this->current_file_size)
        {
            $last_byte = min($start_offset + $download_chunk_size - 1, $this->current_file_size);

            $ret = $this->download_file_part($url,$start_offset,$last_byte);
            if ($ret['result']=='failed')
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . basename($local_path) . ' failed.' . $ret['error']);
            }

            if (!fwrite($fh, $ret['content']))
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . basename($local_path)  . ' failed.fwrite failed');
            }

            if((time() - $this -> last_time) >3)
            {
                if(is_callable($this->callback))
                {
                    call_user_func_array($this->callback,array($start_offset,$this -> current_file_name,
                        $this->current_file_size,$this -> last_time,$this -> last_size));
                }
                $this -> last_size = $start_offset;
                $this -> last_time = time();
            }

            clearstatcache();
            $state = stat($local_path);
            $start_offset = $state['size'];

        }
        @fclose($fh);

        if(filesize($local_path) != $size)
        {
            @unlink($local_path);
            return array('result' => 'failed', 'error' => 'Downloading ' . basename($local_path) . ' failed. ' . basename($local_path) . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }
        else{
            return array('result' => 'success');
        }
    }

    public function download_file_part($url,$offset,$last_byte)
    {
        global $wpvivid_plugin;

        $curl = curl_init();

        $headers = array(
            "Range: bytes=$offset-$last_byte",
        );
        $args['timeout']=60;
        $args['headers']=$headers;

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        //$wpvivid_plugin->wpvivid_download_log->WriteLog('offset:'.$offset,'notice');
        //$wpvivid_plugin->wpvivid_download_log->WriteLog('last_byte:'.$last_byte,'notice');
        //$wpvivid_plugin->wpvivid_download_log->WriteLog('http:'.$http_code,'notice');
        //$wpvivid_plugin->wpvivid_download_log->WriteLog('size:'.strlen($response),'notice');

        if($response!==false)
        {
            curl_close($curl);

            if($http_code==200)
            {
                $size=$last_byte-$offset;
                $data = substr($response, $offset, $size);
            }
            else if($http_code==206)
            {
                $data = $response;
            }
            else if($http_code==416)
            {
                $data = "";
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Downloading files failed, error code: '.$http_code;
                return $ret;
            }

            $ret['content']=$data;
            $ret['result']='success';
            return $ret;
        }
        else
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
            curl_close($curl);
            return $ret;
        }
    }

    public function upload_rollback($file,$folder,$slug,$version)
    {
        $this->url=$this->get_remote_url();
        $this->check_rollback_folder($folder,$slug,$version);

        $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/'.$folder.'/'.$slug.'/'.$version;

        return $this -> upload_file($file);
    }

    public function check_rollback_folder($folder,$slug,$version)
    {
        $path=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex';
        $ret=$this->remote_mkdir($path);
        if($ret['result']=='failed')
        {
            return $ret;
        }

        $path=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/'.$folder;
        $ret=$this->remote_mkdir($path);
        if($ret['result']=='failed')
        {
            return $ret;
        }

        $path=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/'.$folder.'/'.$slug;
        $ret=$this->remote_mkdir($path);
        if($ret['result']=='failed')
        {
            return $ret;
        }

        $path=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/'.$folder.'/'.$slug.'/'.$version;
        $ret=$this->remote_mkdir($path);
        if($ret['result']=='failed')
        {
            return $ret;
        }

        $ret['result']='success';
        return $ret;
    }

    public function download_rollback($download_info)
    {
        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];

        $type=$download_info['type'];
        $slug=$download_info['slug'];
        $version=$download_info['version'];

        $local_path = $download_info['local_path'];

        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url=$this->url.'/'.$this->options['path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$download_info['file_name'];

        }
        else
        {
            $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$download_info['file_name'];
        }

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;
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

        $time_limit = 30;
        $start_time = time();

        while ($start_offset < $this->current_file_size)
        {
            $last_byte = min($start_offset + $download_chunk_size - 1, $this->current_file_size);

            $ret = $this->download_file_part($this->url,$start_offset,$last_byte);
            if ($ret['result']=='failed')
            {
                return array('result' => 'failed', 'error' => 'Downloading ' . basename($local_path) . ' failed.' . $ret['error']);
            }

            if (!fwrite($fh, $ret['content']))
            {
                return array('result' =>  'failed', 'error' => 'Downloading ' . basename($local_path)  . ' failed.fwrite failed');
            }

            clearstatcache();
            $state = stat($local_path);
            $start_offset = $state['size'];

            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                @fclose($fh);
                $result['result']='success';
                $result['finished']=0;
                $result['offset']=$start_offset;
                return $result;
            }
        }

        @fclose($fh);
        clearstatcache();

        if(filesize($local_path) != $this -> current_file_size)
        {
            @unlink($local_path);
            return array('result' => 'failed', 'error' => 'Downloading ' . basename($local_path) . ' failed. ' . basename($local_path) . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }
        else
        {
            $new_path=$download_info['root_path'].$download_info['file_name'];
            rename($local_path, $new_path);

            $result['result']='success';
            $result['finished']=1;
            $result['offset']=$this -> current_file_size;
            return $result;
        }
    }

    public function cleanup($files)
    {
        $root_url=$this->get_remote_url();
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $root_url=$root_url.'/'.$this->options['path'];
        }
        else
        {
            $root_url=$root_url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        foreach ($files as $file)
        {
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $file_url=$root_url.'/'.$file['remote_path'];
                    $file_url=$file_url.'/'.$file['file_name'];
                }
                else
                {
                    $file_url=$root_url.'/'.$file['file_name'];
                }
            }
            else
            {
                $file_url=$root_url.'/'.basename($file);
            }

            $this->remote_unlink($file_url);
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        $root_url=$this->get_remote_url();
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $root_url=$root_url.'/'.$this->options['path'];
        }
        else
        {
            $root_url=$root_url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        $file_url=$root_url.'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$slug.'.zip';
        $this->remote_unlink($file_url);

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function scan_folder_backup($folder_type)
    {
        $this->url=$this->get_remote_url();
        $ret['path']=array();
        $ret['remote']=array();

        if($folder_type === 'Common')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url= $this->url.'/'.$this->options['path'];
            }
            else
            {
                $this->url= $this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
            }

            $response=$this->_scan_folder_backup($this->url);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                $ret['path']=$response['path'];
            }
            else
            {
                $ret['remote']=array();
            }
        }
        else if($folder_type === 'Migrate')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.'/migrate';
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/migrate';
            }

            $response=$this->_scan_folder_backup($this->url);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }
            else
            {
                $ret['migrate']=array();
            }
        }
        else if($folder_type === 'Rollback')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.$this->options['path'].'/rollback';
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback';
            }

            $response=$this->_scan_folder_backup($this->url);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['rollback']= $response['backup'];
            }
            else
            {
                $ret['rollback']=array();
            }
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function _scan_folder_backup($url)
    {
        $ret=$this->remote_list($url);

        if($ret['result']=='success')
        {
            $ret['backup']=array();
            if(!empty($ret['files']))
            {
                global $wpvivid_backup_pro;
                $ret['backup']=$wpvivid_backup_pro->func->get_backup($ret['files']);
            }
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function scan_child_folder_backup($sub_path)
    {
        $ret['remote']=array();
        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url=$this->url.'/'.$this->options['path'];
        }
        else
        {
            $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        $response=$this->_scan_child_folder_backup($this->url,$sub_path);

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['remote']= $response['backup'];
        }
        else
        {
            return $response;
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function _scan_child_folder_backup($url,$sub_path)
    {
        $ret=$this->remote_list_sub_path($url,$sub_path);

        if($ret['result']=='success')
        {
            $ret['backup']=array();
            if(!empty($ret['files']))
            {
                global $wpvivid_backup_pro;
                $tmp_array=array();
                foreach ($ret['files'] as $file_data)
                {
                    $file_data['remote_path']=$sub_path;
                    $tmp_array[]=$file_data;
                }
                $ret['files']=$tmp_array;
                $ret['backup']=$wpvivid_backup_pro->func->get_backup($ret['files']);
            }
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function scan_folder_backup_ex($folder_type)
    {
        $this->url=$this->get_remote_url();

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
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $url= $this->url.'/'.$this->options['path'];
        }
        else
        {
            $url= $this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        return $this->_scan_folder_backup($url);
    }

    public function _get_migrate_backups()
    {
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $url=$this->url.'/migrate';
        }
        else
        {
            $url=$this->url.'/'.$this->options['root_path'].'/migrate';
        }

        return $this->_scan_folder_backup($url);
    }

    public function _get_rollback_backups()
    {
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $url=$this->url.$this->options['path'].'/rollback';
        }
        else
        {
            $url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback';
        }


        return $this->_scan_folder_backup($url);
    }

    public function _get_incremental_backups($incremental_path)
    {
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $url=$this->url.$this->options['path'].'/'.$incremental_path;
        }
        else
        {
            $url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/'.$incremental_path;
        }


        $ret=$this->_scan_folder_backup($url);
        if($ret['result']==WPVIVID_PRO_SUCCESS)
        {
            foreach ($ret['backup'] as  $id=>$backup_data)
            {
                $ret['backup'][$id]['incremental_path']=$incremental_path;
            }
        }
        return $ret;
    }

    public function get_backup_info($backup_info_file,$folder_type,$incremental_path='')
    {
        $this->url=$this->get_remote_url();

        if($folder_type=='Manual')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url= $this->url.'/'.$this->options['path'];
            }
            else
            {
                $this->url= $this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
            }
        }
        else if($folder_type=='Migrate')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.'/migrate';
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/migrate';
            }
        }
        else if($folder_type=='Rollback')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.$this->options['path'].'/rollback';
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback';
            }
        }
        else if($folder_type=='Incremental')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.$this->options['path'].'/'.$incremental_path;
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/'.$incremental_path;
            }
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        $url= $this->url.'/'.$backup_info_file;

        $ret=$this->download_info_file($url);
        if ($ret['result']=='failed')
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . basename($backup_info_file) . ' failed.' . $ret['error']);
        }
        else
        {
            $ret['backup_info']=json_decode($ret['content'],1);
        }

        return $ret;
    }

    public function download_info_file($url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!==false)
        {
            curl_close($curl);

            if($http_code==200)
            {
                $data = $response;
            }
            else if($http_code==206)
            {
                $data = $response;
            }
            else if($http_code==416)
            {
                $data = "";
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Downloading files failed, error code: '.$http_code;
                return $ret;
            }

            $ret['content']=$data;
            $ret['result']='success';
            return $ret;
        }
        else
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
            curl_close($curl);
            return $ret;
        }
    }

    public function scan_rollback($type)
    {
        $this->url=$this->get_remote_url();

        if($type === 'plugins')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.$this->options['path'].'/rollback_ex/plugins';
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/plugins';
            }

            $response=$this->_scan_folder($this->url);

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
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.$this->options['path'].'/rollback_ex/themes';
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/themes';
            }

            $response=$this->_scan_folder($this->url);

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

    public function _scan_folder($url)
    {
        $response=$this->remote_list($url);

        if($response['result']=='success')
        {
            $ret['result']='success';
            $ret['path']=$response['path'];
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function get_rollback_data($type,$slug)
    {
        $this->url=$this->get_remote_url();

        if($type === 'plugins')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.$this->options['path'].'/rollback_ex/plugins/'.$slug;
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/plugins/'.$slug;
            }

            $response=$this->_scan_folder($this->url);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path= $response['path'];
                if(!empty($path))
                {
                    foreach ($path as $version)
                    {
                        $url=$this->url.'/'.$version.'/'.$slug.'.zip';
                        $response=$this->_scan_file($url);
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
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $this->url=$this->url.$this->options['path'].'/rollback_ex/themes/'.$slug;
            }
            else
            {
                $this->url=$this->url.'/'.$this->options['root_path'].'/'.$this->options['path'].'/rollback_ex/themes/'.$slug;
            }

            $response=$this->_scan_folder($this->url);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path= $response['path'];
                if(!empty($path))
                {
                    foreach ($path as $version)
                    {
                        $url=$this->url.'/'.$version.'/'.$slug.'.zip';
                        $response=$this->_scan_file($url);
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

    public function _scan_file($url)
    {
        try
        {
            if($stat=$this->remote_stat($url))
            {
                $ret['file']=$stat;
                $ret['result']='success';
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Failed to get file information.';
                return $ret;
            }
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']=$e->getMessage();
            return $ret;
        }
    }

    public function delete_old_backup($backup_count,$db_count)
    {
        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url= $this->url.'/'.$this->options['path'];
        }
        else
        {
            $this->url= $this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }
        $response=$this->_scan_folder_backup($this->url);
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
                $child_response=$this->_scan_child_folder_backup($this->url,$folder);
                if(isset($child_response['files']))
                {
                    $files=array_merge($files,$child_response['files']);
                }
            }
            if(!empty($files))
            {
                $this->cleanup($files);
            }
        }

        $this->url=$this->url.'/rollback';

        $response=$this->_scan_folder_backup($this->url);
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
        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url= $this->url.'/'.$this->options['path'];
        }
        else
        {
            $this->url= $this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        if($folder_type === 'Common')
        {
        }
        else if($folder_type === 'Rollback'){

            $this->url= $this->url.'/rollback';
        }
        else
        {
            return false;
        }

        $response=$this->_scan_folder_backup($this->url);

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

    public function delete_old_backup_ex($type,$backup_count,$db_count)
    {
        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url= $this->url.'/'.$this->options['path'];
        }
        else
        {
            $this->url= $this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        if($type=='Rollback')
        {
            $this->url=$this->url.'/rollback';

            $response=$this->_scan_folder_backup($this->url);
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
            //$path=$root_path.'/'.$this->options['path'];

            $response=$this->_scan_folder_backup($this->url);

            if(isset($response['path']))
            {
                $folders=$response['path'];

                global $wpvivid_backup_pro;
                $files = array();
                $folders_count=$backup_count;
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);

                foreach ($folders as $folder)
                {
                    $child_response=$this->_scan_child_folder_backup($this->url,$folder);
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
                    $this->cleanup($folders);
                }
            }
        }
        else
        {
            //$path=$root_path.'/'.$this->options['path'];

            $response=$this->_scan_folder_backup($this->url);

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
        $this->url=$this->get_remote_url();

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $this->url= $this->url.'/'.$this->options['path'];
        }
        else
        {
            $this->url= $this->url.'/'.$this->options['root_path'].'/'.$this->options['path'];
        }

        if($type=='Rollback')
        {
            $this->url=$this->url.'/rollback';

            $response=$this->_scan_folder_backup($this->url);
            if(isset($response['backup']))
            {
                $backups=$response['backup'];
                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);

                if(!empty($files))
                {
                    return false;
                }
                else
                {
                    return true;
                }
            }
        }
        else if($type=='Incremental')
        {
            $response=$this->_scan_folder_backup($this->url);

            if(isset($response['path']))
            {
                $folders=$response['path'];

                global $wpvivid_backup_pro;
                $files = array();
                $folders_count=$backup_count;
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);

                if(!empty($folders))
                {
                    return false;
                }
                else
                {
                    return true;
                }
            }
        }
        else
        {
            $response=$this->_scan_folder_backup($this->url);

            if(isset($response['backup']))
            {
                $backups=$response['backup'];

                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
                if(!empty($files))
                {
                    return false;
                }
                else
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function remove_upload_url($url)
    {
        $url=trailingslashit($url);
        $this->remote_unlink($url);
    }

    public function remote_open($url)
    {
        if($this->remote_is_file($url))
        {
            return true;
        }
        else
        {
            $curl = curl_init();
            $headers = array(
                "Content-Length: 0",
            );
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_HEADER,true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_PUT, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
            $response=curl_exec($curl);
            $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            if($response!==false)
            {
                curl_close($curl);
                if($http_code==200||$http_code==201)
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
                throw new Exception(curl_error($curl));
            }
        }
    }

    public function remote_stat($url)
    {
        $curl = curl_init();

        $headers = array(
            "Depth: 0",
            "Content-Type text/xml",
        );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!==false)
        {
            curl_close($curl);
            if($http_code==207)
            {
                $propinfo = new WPvivid_Nextcloud_parse_propfind_response($response);
                $stat = $propinfo->stat();
                unset($propinfo);
                return $stat;
            }
            else if($http_code==404)
            {
                return false;
            }
            else
            {
                return false;
            }
        }
        else
        {
            throw new Exception(curl_error($curl));
        }
    }

    public function remote_unlink($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($response===false)
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
            curl_close($curl);
            return $ret;
        }
        else
        {
            curl_close($curl);
            $ret['result']='success';
            return $ret;
        }
    }

    public function remote_list($url)
    {
        $ret['files']=array();
        $ret['path']=array();

        $url=trailingslashit($url);

        $parsed_url = parse_url($url);
        $url_path= $parsed_url["path"];

        $curl = curl_init();
        $headers = array(
            "Depth: 1",
            "Content-Type text/xml",
        );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!==false)
        {
            curl_close($curl);
            if($http_code==207)
            {
                $propinfo = new WPvivid_Nextcloud_parse_propfind_response($response);

                $urls=$propinfo->urls;

                foreach ($urls as $file_url=>$data)
                {
                    if(!isset($data['size']))
                    {
                        if($file_url==""||$file_url==$url_path||$file_url==$url)
                        {
                            continue;
                        }
                        $ret['path'][]=basename($file_url);
                    }
                    else
                    {
                        $file_data['file_name']=basename($file_url);
                        $file_data['size']=$data['size'];
                        $ret['files'][]=$file_data;
                    }
                }

                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
            else if($http_code==404)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
            else
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
        }
        return $ret;
    }

    public function remote_list_sub_path($url,$sub_path)
    {
        $ret['files']=array();

        $dir_url=trailingslashit($url.'/'.$sub_path);

        $curl = curl_init();
        $headers = array(
            "Depth: 1"
        );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($curl, CURLOPT_URL, $dir_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!==false)
        {
            curl_close($curl);
            if($http_code==207)
            {
                $propinfo = new WPvivid_Nextcloud_parse_propfind_response($response);

                $urls=$propinfo->urls;
                foreach ($urls as $url=>$data)
                {
                    if(!isset($data['size']))
                    {
                        continue;
                    }
                    else
                    {
                        $file_data['file_name']=basename($url);
                        $file_data['size']=$data['size'];
                        $file_data['remote_path']=$sub_path;
                        $ret['files'][]=$file_data;
                    }
                }

                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
            else if($http_code==404)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
            else
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
        }
        return $ret;
    }

    public function remote_is_file($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!==false)
        {
            curl_close($curl);
            if($http_code==207)
            {
                return true;
            }
            else if($http_code==404)
            {
                return false;
            }
            else
            {
                return false;
            }
        }
        else
        {
            throw new Exception(curl_error($curl));
        }
    }

    public function remote_mkdir($url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'MKCOL');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $this->options['username'].':'.$this->options['password']);
        $response=curl_exec($curl);
        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!==false)
        {
            curl_close($curl);
            if($http_code==200||$http_code==201||$http_code==301||$http_code==405)
            {
                $ret['result']='success';
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']="Creating a backup folder failed, error code: ".$http_code;
                return $ret;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']=curl_error($curl);
            curl_close($curl);
            return $ret;
        }
    }

    public function get_out_of_date_nextcloud($out_of_date_remote, $remote)
    {
        if($remote['type'] == WPVIVID_REMOTE_NEXTCLOUD)
        {
            $root_path=apply_filters('wpvivid_get_root_path', $remote['type']);
            $out_of_date_remote = $root_path.$remote['path'];
        }
        return $out_of_date_remote;
    }
}

class WPvivid_Nextcloud_parse_propfind_response
{
    public $urls;
    public $_depth;
    public $success;
    public $_tmpprop;
    public $_tmphref;
    public $_tmpvals;
    public $_tmpdata;
    public $_tmpstat;
    // get requested properties as array containing name/namespace pairs
    public function __construct($response)
    {
        $this->urls = array();

        $this->_depth = 0;

        $xml_parser = xml_parser_create_ns("UTF-8", " ");
        xml_set_element_handler($xml_parser,
            array(&$this, "_startElement"),
            array(&$this, "_endElement"));
        xml_set_character_data_handler($xml_parser,
            array(&$this, "_data"));
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING,
            false);
        $this->success = xml_parse($xml_parser, $response, true);
        xml_parser_free($xml_parser);
        unset($this->_depth);
    }


    function _startElement($parser, $name, $attrs)
    {
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->success = false;
        } else {
            $ns  = "";
            $tag = $name;
        }

        switch ($this->_depth) {
            case '2':
                switch ($tag) {
                    case 'propstat':
                        $this->_tmpprop = array("mode" => 0100666 /* all may read and write (for now) */);
                        break;
                }
        }

        $this->_depth++;
    }

    function _endElement($parser, $name)
    {
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->success = false;
        } else {
            $ns  = "";
            $tag = $name;
        }

        $this->_depth--;

        switch ($this->_depth) {
            case '1':
                switch ($tag) {
                    case 'response':
                        $this->urls[$this->_tmphref] = $this->_tmpvals;
                        unset($this->_tmphref);
                        unset($this->_tmpvals);
                        break;
                }
                break;
            case '2':
                switch ($tag) {
                    case 'href':
                        $this->_tmphref = $this->_tmpdata;
                        break;
                }
            case 'propstat':
                if (isset($this->_tmpstat) && strstr($this->_tmpstat, " 200 ")) {
                    $this->_tmpvals = $this->_tmpprop;
                }
                unset($this->_tmpstat);
                unset($this->_tmpprop);
                break;
            case '3':
                switch ($tag) {
                    case 'status':
                        $this->_tmpstat = $this->_tmpdata;
                        break;
                }
            case '4':
                switch ($tag) {
                    case 'getlastmodified':
                        $this->_tmpprop['atime'] = strtotime($this->_tmpdata);
                        $this->_tmpprop['mtime'] = strtotime($this->_tmpdata);
                        break;
                    case 'creationdate':
                        $t = preg_split("/[^[:digit:]]/", $this->_tmpdata);
                        $this->_tmpprop['ctime'] = mktime($t[3], $t[4], $t[5], $t[1], $t[2], (int)$t[0]);
                        unset($t);
                        break;
                    case 'getcontentlength':
                        $this->_tmpprop['size'] = $this->_tmpdata;
                        break;
                }
            case '5':
                switch ($tag) {
                    case 'collection':
                        $this->_tmpprop['mode'] &= ~0100000; // clear S_IFREG
                        $this->_tmpprop['mode'] |= 040000; // set S_IFDIR
                        break;
                }
        }

        unset($this->_tmpdata);
    }

    function _data($parser, $data)
    {
        $this->_tmpdata = $data;
    }

    function stat($href = false)
    {
        if ($href) {
        } else {
            reset($this->urls);
            return current($this->urls);
        }
    }
}