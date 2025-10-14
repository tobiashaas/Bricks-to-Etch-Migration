<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_B2_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_REMOTE_B2'))
    define('WPVIVID_REMOTE_B2','b2');
if(!defined('WPVIVID_B2_DEFAULT_FOLDER'))
    define('WPVIVID_B2_DEFAULT_FOLDER','/');

class WPvivid_B2_addon extends WPvivid_Remote_addon
{
    public $options;
    private $chunk_size = 5*1024*1024;
    public $callback;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('WPVIVID_INIT_STORAGE_TAB_B2'))
            {
                add_filter('wpvivid_get_out_of_date_remote',array($this,'get_out_of_date_b2'),10,2);
                add_action('wpvivid_edit_remote_page',array($this,'edit_storage_page_b2'), 17);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'),11);
                define('WPVIVID_INIT_STORAGE_TAB_B2',1);
            }
        }
        else
        {
            $this->options=$options;
        }
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_B2] = 'WPvivid_B2_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_B2)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function add_storage_page_b2()
    {
        global $wpvivid_backup_pro;
        ?>
        <div id="storage_account_b2"  class="storage-account-page">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Backblaze Storage Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2" name="name" placeholder="Enter a unique alias: e.g. B2-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="password" class="regular-text" autocomplete="new-password" option="b2" name="appkeyid" placeholder="Application key id" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Application Key ID.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="b2" name="appkey" placeholder="Application key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Application Key.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2" name="bucket" placeholder="Backblaze Bucket Name(e.g. test)" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Enter an existing Bucket in which you want to create a parent folder for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2" name="path" placeholder="Custom Path" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize the name of folder under the parent folder where you want to store %s backups.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></span></i>
                            </div>
                        </td>
                    </tr>

                    <!--<tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="b2" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="b2" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'b2', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="b2" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
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
                                    <input type="checkbox" option="b2" name="default" checked />Set as the default remote storage.
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
                            <i>Click the button to connect to B2 storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function edit_storage_page_b2()
    {
        global $wpvivid_backup_pro;
        ?>
        <div id="remote_storage_edit_b2">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Backblaze Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2" name="name" placeholder="Enter a unique alias: e.g. B2-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="password" class="regular-text" autocomplete="new-password" option="edit-b2" name="appkeyid" placeholder="Application key id" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Application Key ID.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="edit-b2" name="appkey" placeholder="Application key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Application Key.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2" name="bucket" placeholder="Backblaze Bucket Name(e.g. test)" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Enter an existing Bucket in which you want to create a parent folder for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2" name="path" placeholder="Custom Path" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(__('Customize the name of folder under the parent folder where you want to store %s backups.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></span></i>
                            </div>
                        </td>
                    </tr>

                    <!--<tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="edit-b2" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="edit-b2" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'b2', 'edit'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-b2" name="chunk_size" placeholder="Chunk size" value="5" onkeyup="value=value.replace(/\D/g,'')" />MB
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
                                    <input type="checkbox" option="edit-b2" name="default" checked />Set as the default remote storage.
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
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        return array('result' => WPVIVID_PRO_SUCCESS);

    }

    public function sanitize_options($skip_name='')
    {
        $ret['result']=WPVIVID_PRO_FAILED;
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

        if(!isset($this->options['appkeyid']))
        {
            $ret['error']="Warning: The app key id for Backblaze is required.";
            return $ret;
        }

        $this->options['appkeyid']=sanitize_text_field($this->options['appkeyid']);

        if(empty($this->options['appkeyid']))
        {
            $ret['error']="Warning: The app key id for Backblaze is required.";
            return $ret;
        }

        if(!isset($this->options['appkey']))
        {
            $ret['error']="Warning: The storage app key is required.";
            return $ret;
        }

        $this->options['appkey']=sanitize_text_field($this->options['appkey']);

        if(empty($this->options['appkey']))
        {
            $ret['error']="Warning: The storage app key is required.";
            return $ret;
        }

        if(!isset($this->options['bucket']))
        {
            $ret['error']="Warning: A Bucket name is required.";
            return $ret;
        }

        $this->options['bucket']=sanitize_text_field($this->options['bucket']);

        if(empty($this->options['bucket']))
        {
            $ret['error']="Warning: A Bucket name is required.";
            return $ret;
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

        if($this->options['root_path'] == '/')
        {
            $ret['error']="The backup folder name cannot be '/'";
            return $ret;
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

        if($this->options['path'] == '/')
        {
            $ret['error']="The backup folder name cannot be '/'";
            return $ret;
        }

        if(isset($this->options['use_remote_retention']) && $this->options['use_remote_retention'] == '1')
        {
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
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        $ret['options']=$this->options;
        return $ret;
    }

    public function get_root_path()
    {
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            return $this->options['path'];
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            return $root_path.'/'.$this->options['path'];
        }
    }

    public function upload($task_id,$files,$callback='')
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
                $job_data[basename($file)]=$file_data;
            }
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading',$job_data);
            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        }

        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $path=$this->get_root_path();

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
            $result=$this->_upload($task_id, $file,$path,$callback);
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

    private function _upload($task_id,$local_file,$remote_path,$callback)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $this -> current_file_size = filesize($local_file);
        $this -> current_file_name = basename($local_file);

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check if the server already has the same name file.','notice');

        $this->delete_exist_file($remote_path.'/'.basename($local_file));

        $file_size=filesize($local_file);

        //small file
        if($file_size<1024*1024*5)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploaded files are less than 5M.','notice');

            $ret=$this->get_upload_url();
            if($ret['result']!='success')
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploaded small file get upload url failed.','notice');
                return $ret;
            }

            return $this->upload_small_file($local_file,$remote_path,$task_id);
        }
        else
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start upload large file.','notice');

            $ret=$this->start_large_file($remote_path.'/'.basename($local_file));
            if($ret['result']!='success')
            {
                return $ret;
            }

            $fileId=$ret['fileId'];

            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Get upload part url.','notice');

            $ret=$this->get_upload_part_url($ret['fileId']);
            if($ret['result']!='success')
            {
                return $ret;
            }

            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Ready to start uploading files.','notice');

            $ret=$this->upload_resume($local_file,$task_id,$callback);

            if($ret['result']!='success')
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload_resume failed: '.json_encode($ret),'notice');
                return $ret;
            }

            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finish large file upload.','notice');

            $ret=$this->finish_large_file($fileId,$ret['sha1_of_parts']);

            if($ret['result']!='success')
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('finish_large_file failed: '.json_encode($ret),'notice');
                return $ret;
            }

            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
            $upload_job['job_data'][basename($local_file)]['uploaded']=1;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($local_file),'notice');
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($local_file).' completed.',$upload_job['job_data']);

            return $ret;
        }
    }

    public function download($file,$local_path,$callback = '')
    {
        $this -> current_file_name = $file['file_name'];
        $this -> current_file_size = $file['size'];
        $this->callback=$callback;
        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Remote type: Backblaze.','notice');

        $path=$this->get_root_path();

        if(isset($file['remote_path']))
        {
            $path=$path.'/'.$file['remote_path'];
        }

        $wpvivid_plugin->wpvivid_download_log->WriteLog('download from:'.$path,'notice');

        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $file_path=$local_path.$file['file_name'];
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'] ,'notice');

        $ret=$this->download_file($path.'/'.$file['file_name'],$file_path,$file['size']);

        if($ret['result']!=WPVIVID_PRO_SUCCESS)
        {
            return $ret;
        }
        else
        {
            return array('result' => WPVIVID_PRO_SUCCESS);
        }
    }

    public function chunk_download($download_info,$callback)
    {
        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];
        $local_path = $download_info['local_path'];

        $this->callback=$callback;

        $path=$this->get_root_path().'/'. $download_info['file_name'];

        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;
        $download_chunk_size = $this->chunk_size;
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
            $last_byte = min($start_offset + $download_chunk_size - 1, $this->current_file_size - 1);

            $ret = $this->download_file_part($path,$start_offset,$last_byte);
            if ($ret['result']=='failed')
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $download_info['file_name']. ' failed.' . $ret['error']);
            }

            if (!fwrite($fh, $ret['content']))
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $download_info['file_name'] . ' failed.');
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

    public function upload_rollback($file,$folder,$slug,$version)
    {
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->get_upload_url();
        if($ret['result']!='success')
        {
            return $ret;
        }

        $remote_path=$this->get_root_path();
        $remote_path=$remote_path.'/rollback_ex/'.$folder.'/'.$slug.'/'.$version;

        $handle=fopen($file,'rb');
        $read_file = fread($handle,filesize($file));
        $path=$remote_path.'/'.basename($file);
        $path = rawurlencode($path);
        $sha1_of_file_data = sha1_file($file);

        $api_url = $this->options['uploadUrl'];
        $auth_token =   $this->options['upload_authorizationToken'];

        $headers['Authorization'] = $auth_token;

        $headers['X-Bz-File-Name'] = $path;
        $headers['Content-Type'] = 'application/zip';
        $headers['X-Bz-Content-Sha1'] = $sha1_of_file_data;
        $headers['X-Bz-Info-Author'] = "unknown";
        $headers['X-Bz-Server-Side-Encryption'] = "AES256";

        $ret=$this->remote_post('',$headers,$read_file,60,$api_url);

        if($ret['result']=='success')
        {
            return array('result' =>'success');
        }
        else
        {
            return $ret;
        }

    }

    public function download_rollback($download_info)
    {
        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];

        $type=$download_info['type'];
        $slug=$download_info['slug'];
        $version=$download_info['version'];

        $local_path = $download_info['local_path'];

        $path=$this->get_root_path();
        $path=$path.'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$download_info['file_name'];

        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;
        $download_chunk_size = $this->chunk_size;
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
            $last_byte = min($start_offset + $download_chunk_size - 1, $this->current_file_size - 1);

            $ret = $this->download_file_part($path,$start_offset,$last_byte);
            if ($ret['result']=='failed')
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $download_info['file_name']. ' failed.' . $ret['error']);
            }

            if (!fwrite($fh, $ret['content']))
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $download_info['file_name'] . ' failed.');
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
            rename($local_path, $download_info['root_path'].$download_info['file_name']);

            $result['result']='success';
            $result['finished']=1;
            $result['offset']=$this -> current_file_size;
            return $result;
        }
    }

    public function cleanup($files)
    {
        if(!isset($this->options['authorizationToken']))
        {
            $ret=$this->auth();
            if($ret['result']!='success')
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
            }

            $ret=$this->list_buckets();
            if($ret['result']!='success')
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
            }
        }

        $path=$this->get_root_path().'/';

        foreach ($files as $file)
        {
            $file_path=$path;
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $file_path=$path.$file['remote_path'].'/';
                }
                $file_name=$file['file_name'];
            }
            else
            {
                $file_name=$file;
            }
            $this->delete_exist_file($file_path.$file_name);
        }
        return array('result'=>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        if(!isset($this->options['authorizationToken']))
        {
            $ret=$this->auth();
            if($ret['result']!='success')
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
            }

            $ret=$this->list_buckets();
            if($ret['result']!='success')
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
            }
        }
        $path=$this->get_root_path().'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$slug.'.zip';

        $this->delete_exist_file($path);
        return array('result'=>WPVIVID_PRO_SUCCESS);
    }

    public function get_out_of_date_b2($out_of_date_remote, $remote)
    {
        if($remote['type'] == WPVIVID_REMOTE_B2)
        {
            $root_path=apply_filters('wpvivid_get_root_path', $remote['type']);
            $out_of_date_remote = $root_path.$remote['path'];
        }
        return $out_of_date_remote;
    }

    public function scan_folder_backup($folder_type)
    {
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=array();
        $ret['path']=array();
        if($folder_type === 'Common')
        {
            $path=$this->get_root_path().'/';
            $response=$this->_scan_folder_backup($path);

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
                $path='migrate';
            }
            else
            {
                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }
                $path=$root_path.'/migrate';
            }

            $response=$this->_scan_folder_backup($path);

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
            $path=$this->get_root_path().'/';

            $remote_folder=$path.'rollback';
            $response=$this->_scan_folder_backup($remote_folder);

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

    public function _scan_folder_backup($path)
    {
        $ret=$this->list_files($path);

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
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        if(!isset($this->options['path']))
        {
            $ret['result']='failed';
            $ret['error']='path not found';
            return $ret;
        }

        $path=$this->get_root_path();

        $response=$this->_scan_child_folder_backup($path,$sub_path);

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

    public function _scan_child_folder_backup($path,$sub_path)
    {
        $ret=$this->list_files($path.'/'.$sub_path);

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
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

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
        $path=$this->get_root_path().'/';

        return $this->_scan_folder_backup($path);
    }

    public function _get_migrate_backups()
    {
        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $path='migrate';
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            $path=$root_path.'/migrate';
        }


        return $this->_scan_folder_backup($path);
    }

    public function _get_rollback_backups()
    {
        $path=$this->get_root_path().'/';

        $remote_folder=$path.'rollback';

        return $this->_scan_folder_backup($remote_folder);
    }

    public function _get_incremental_backups($incremental_path)
    {

        $path=$this->get_root_path().'/';

        $remote_folder=$path.$incremental_path;

        $ret=$this->_scan_folder_backup($remote_folder);
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
        if($folder_type=='Manual')
        {
            $path=$this->get_root_path().'/'. $backup_info_file;
        }
        else if($folder_type=='Migrate')
        {
            if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
            {
                $path='migrate/'.$backup_info_file;
            }
            else
            {
                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }
                $path=$root_path.'/migrate/'.$backup_info_file;
            }
        }
        else if($folder_type=='Rollback')
        {
            $path=$this->get_root_path().'/';

            $path=$path.'rollback/'. $backup_info_file;
        }
        else if($folder_type=='Incremental')
        {
            $path=$this->get_root_path().'/';

            $path=$path.$incremental_path.'/'. $backup_info_file;
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $url =  $this->options['downloadUrl'] . "/file/" . $this->options['bucket'] . "/" . $path;
        $headers = array();
        $headers['Authorization'] =$this->options['authorizationToken'];
        $args['timeout']=30;
        $args['headers']=$headers;

        $response=wp_remote_get($url,$args);

        if(!is_wp_error($response))
        {
            if($response['response']['code']==200||$response['response']['code']==206)
            {
                $ret['result']='success';
                $ret['backup_info']=json_decode($response['body'],1);
                return $ret;
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']= $response['response']['message'];
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

    public function scan_rollback($type)
    {
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $path=$this->get_root_path();

        if($type === 'plugins')
        {
            $path=$path.'/rollback_ex/plugins';

            $response=$this->_scan_folder($path);

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
            $path=$path.'/rollback_ex/themes';

            $response=$this->_scan_folder($path);

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

    public function _scan_folder($path)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $bucket_id =  $this->options['bucketId'];
        $data = array("bucketId" => $bucket_id,'prefix'=>$path);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;

        $ret=$this->remote_post('b2_list_file_names',$headers,$post_fields,30,$api_url);

        if($ret['result']=='success')
        {
            if(isset($ret['body']['files']))
            {
                $folders=array();
                foreach ($ret['body']['files'] as $file)
                {
                    $new_path=str_replace($path.'/','',$file['fileName']);
                    $parts = explode('/',$new_path);

                    if(!empty($parts))
                    {
                        if(!in_array($parts[0],$folders))
                            $folders[]=$parts[0];
                    }
                }

                while(isset($ret['body']['nextFileName']) && !empty($ret['body']['nextFileName']))
                {
                    $data = array("bucketId" => $bucket_id,'startFileName'=>$ret['body']['nextFileName'],'prefix'=>$path);
                    $post_fields = json_encode($data);
                    $ret=$this->remote_post('b2_list_file_names',$headers,$post_fields,30,$api_url);
                    foreach ($ret['body']['files'] as $file)
                    {
                        $new_path=str_replace($path.'/','',$file['fileName']);
                        $parts = explode('/',$new_path);

                        if(!empty($parts))
                        {
                            if(!in_array($parts[0],$folders))
                                $folders[]=$parts[0];
                        }
                    }
                }

                $ret_list['result']='success';
                $ret_list['path']=$folders;
                return $ret_list;
            }
            else
            {
                $ret_list['result']='success';
                $ret_list['path']=array();
                return $ret_list;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function get_rollback_data($type,$slug)
    {
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $path=$this->get_root_path();

        if($type === 'plugins')
        {
            $path=$path.'/rollback_ex/plugins/'.$slug;

            $response=$this->_scan_folder($path);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $response_path= $response['path'];
                if(!empty($path))
                {
                    foreach ($response_path as $version)
                    {
                        $url=$path.'/'.$version.'/'.$slug.'.zip';
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
            $path=$path.'/rollback_ex/themes/'.$slug;

            $response=$this->_scan_folder($path);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $response_path= $response['path'];
                if(!empty($path))
                {
                    foreach ($response_path as $version)
                    {
                        $url=$path.'/'.$version.'/'.$slug.'.zip';
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

    public function _scan_file($path)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $bucket_id =  $this->options['bucketId'];
        $data = array("bucketId" => $bucket_id,'prefix'=>$path);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;

        $ret=$this->remote_post('b2_list_file_names',$headers,$post_fields,30,$api_url);
        if($ret['result']=='success')
        {
            if(isset($ret['body']['files']))
            {
                foreach ($ret['body']['files'] as $file)
                {
                    if(basename($file['fileName'])==basename($path))
                    {
                        $file_data['file_name']=basename($file['fileName']);
                        $file_data['size']=$file['contentLength'];
                        $file_data['mtime']=intval($file['uploadTimestamp']/1000);
                        $ret_list['file']=$file_data;
                        break;
                    }
                }

                if(!isset($ret_list['file']))
                {
                    $ret_list['result']='failed';
                    $ret_list['error']='Failed to get file information.';
                    return $ret_list;
                }

                $ret_list['result']='success';
                return $ret_list;
            }
            else
            {
                $ret_list['result']='failed';
                $ret_list['error']='Failed to get file information.';
                return $ret_list;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function delete_old_backup($backup_count,$db_count)
    {
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $path=$this->get_root_path();

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
                $this->cleanup($folders);
            }
        }

        $path=$this->get_root_path().'/'.$this->options['path'].'/rollback';

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
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $path='';
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            $path=$root_path.'/';
        }

        if($folder_type === 'Common')
        {
            $path=$path.$this->options['path'];
        }
        else if($folder_type === 'Rollback'){

            $path=$path.$this->options['path'].'/rollback';
        }
        else
        {
            return false;
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

    public function delete_old_backup_ex($type,$backup_count,$db_count)
    {
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $path=$this->options['path'];
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            $path=$root_path.'/'.$this->options['path'];
        }

        if($type=='Rollback')
        {
            $path=$path.'/rollback';

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
            //$path=$root_path.'/'.$this->options['path'];

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
                    $this->cleanup($folders);
                }
            }
        }
        else
        {
            //$path=$root_path.'/'.$this->options['path'];
            $path = $path.'/';
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
        $ret=$this->auth();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        $ret=$this->list_buckets();
        if($ret['result']!='success')
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$ret['error']);
        }

        if(isset($this->options['is_empty_root_path'])&&$this->options['is_empty_root_path'])
        {
            $path=$this->options['path'];
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            $path=$root_path.'/'.$this->options['path'];
        }

        if($type=='Rollback')
        {
            $path=$path.'/rollback';

            $response=$this->_scan_folder_backup($path);
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
            $response=$this->_scan_folder_backup($path);

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
            $response=$this->_scan_folder_backup($path);

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

    public function auth()
    {
        $appkeyid = $this->options['appkeyid'];
        $appkey= $this->options['appkey'];

        $credentials = base64_encode($appkeyid . ":" . $appkey);
        $headers['Accept']='application/json';
        $headers['Authorization']="Basic " . $credentials;

        $ret=$this->remote_get('b2_authorize_account',$headers);
        if($ret['result']=='success')
        {
            $body=$ret['body'];
            $this->options['authorizationToken']=$body['authorizationToken'];
            $this->options['downloadUrl']=$body['downloadUrl'];
            $this->options['apiUrl']=$body['apiUrl'];
            //$this->options['s3ApiUrl']=$body['s3ApiUrl'];
            $this->options['accountId']=$body['accountId'];

            if(isset($body['allowed']['bucketName']))
            {
                if($body['allowed']['bucketName']==$this->options['bucket'])
                {
                    $this->options['bucketId']=$body['allowed']['bucketId'];
                }
            }

            $ret['result']='success';
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function list_buckets()
    {
        if(isset($this->options['bucketId']))
        {
            $ret['result']='success';
            return $ret;
        }

        $api_url = $this->options['apiUrl'].  "/b2api/v2/";

        $headers['Authorization'] = $this->options['authorizationToken'];
        $data = array("accountId" => $this->options['accountId']);
        $post_fields = json_encode($data);

        $ret= $this->remote_post('b2_list_buckets',$headers,$post_fields,30,$api_url);

        if($ret['result']=='success')
        {
            $find=false;
            foreach ($ret['body']['buckets'] as $bucket)
            {
                if($bucket['bucketName']==$this->options['bucket'])
                {
                    $this->options['bucketId']=$bucket['bucketId'];
                    $find=true;
                }
            }

            if($find)
            {
                $ret['result']='success';
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='not found bucket';
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function list_files($path)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $bucket_id =  $this->options['bucketId'];
        $data = array("bucketId" => $bucket_id,'prefix'=>$path);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;

        $ret=$this->remote_post('b2_list_file_names',$headers,$post_fields,30,$api_url);
        if($ret['result']=='success')
        {
            if(isset($ret['body']['files']))
            {
                $files=array();
                $folders=array();
                foreach ($ret['body']['files'] as $file)
                {
                    if(dirname($file['fileName'])==untrailingslashit($path))
                    {
                        $file_data['file_name']=basename($file['fileName']);
                        $file_data['size']=$file['contentLength'];
                        $files[]=$file_data;
                    }
                    else
                    {
                        $sub_path=dirname($file['fileName']);

                        if(dirname($sub_path)==untrailingslashit($path))
                        {
                            if(!in_array(basename($sub_path),$folders))
                                $folders[]=basename($sub_path);
                        }
                    }
                }

                while(isset($ret['body']['nextFileName']) && !empty($ret['body']['nextFileName']))
                {
                    $data = array("bucketId" => $bucket_id,'startFileName'=>$ret['body']['nextFileName'],'prefix'=>$path);
                    $post_fields = json_encode($data);
                    $ret=$this->remote_post('b2_list_file_names',$headers,$post_fields,30,$api_url);
                    foreach ($ret['body']['files'] as $file)
                    {
                        if(dirname($file['fileName'])==untrailingslashit($path))
                        {
                            $file_data['file_name']=basename($file['fileName']);
                            $file_data['size']=$file['contentLength'];
                            $files[]=$file_data;
                        }
                        else
                        {
                            $sub_path=dirname($file['fileName']);

                            if(dirname($sub_path)==untrailingslashit($path))
                            {
                                if(!in_array(basename($sub_path),$folders))
                                    $folders[]=basename($sub_path);
                            }
                        }
                    }
                }

                $ret_list['result']='success';
                $ret_list['files']=$files;
                $ret_list['path']=$folders;
                return $ret_list;
            }
            else
            {
                $ret_list['result']='success';
                $ret_list['files']=array();
                $ret_list['path']=array();
                return $ret_list;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function start_large_file($remote_path)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $bucket_id =  $this->options['bucketId'];
        $data = array("fileName" => $remote_path, "bucketId" => $bucket_id, "contentType" => 'application/zip');
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;
        $headers[] = "Accept: application/json";

        $ret=$this->remote_post('b2_start_large_file',$headers,$post_fields,30,$api_url);

        if($ret['result']=='success')
        {
            if(isset($ret['body']['fileId']))
            {
                $ret['result']='success';
                $ret['fileId']=$ret['body']['fileId'];
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='start_large_file failed';
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function finish_large_file($file_id,$sha1_of_parts)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];

        $data = array("fileId" => $file_id, "partSha1Array" => $sha1_of_parts);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;

        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('remote_post headers: '.json_encode($headers).', post_fields: '.$post_fields.', api_url: '.$api_url, 'notice');
        $ret=$this->remote_post('b2_finish_large_file',$headers,$post_fields,60,$api_url);
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('remote_post result: '.json_encode($ret), 'notice');
        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            $this->get_file_info($file_id);
            return $ret;
        }
    }

    public function get_file_info($fileId)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $data = array("fileId" => $fileId);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('get_file_info remote_post headers: '.json_encode($headers).', post_fields: '.$post_fields.', api_url: '.$api_url, 'notice');
        $ret=$this->remote_post('b2_get_file_info',$headers,$post_fields,30,$api_url);
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('get_file_info remote_post result: '.json_encode($ret), 'notice');
        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function get_upload_url()
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $bucket_id =  $this->options['bucketId'];
        $data = array("bucketId" => $bucket_id);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;

        $ret=$this->remote_post('b2_get_upload_url',$headers,$post_fields,30,$api_url);

        if($ret['result']=='success')
        {
            if(isset($ret['body']['uploadUrl']))
            {
                $ret['result']='success';
                $this->options['uploadUrl']=$ret['body']['uploadUrl'];
                $this->options['upload_authorizationToken']=$ret['body']['authorizationToken'];
                //
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='not found upload url';
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function get_upload_part_url($fileId)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $data = array("fileId" => $fileId);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;
        $headers[] = "Accept: application/json";

        $ret=$this->remote_post('b2_get_upload_part_url',$headers,$post_fields,30,$api_url);

        if($ret['result']=='success')
        {
            if(isset($ret['body']['fileId']))
            {
                $ret['result']='success';
                $ret['fileId']=$ret['body']['fileId'];
                $this->options['uploadUrl']=$ret['body']['uploadUrl'];
                $this->options['upload_authorizationToken']=$ret['body']['authorizationToken'];
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='get_upload_part_url failed';
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function delete_exist_file($path)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $bucket_id =  $this->options['bucketId'];
        $data = array("bucketId" => $bucket_id,'prefix'=>$path,'startFileName'=>$path);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;
        $headers['Content-Type'] = 'application/json';
        $ret=$this->remote_post('b2_list_file_versions',$headers,$post_fields,30,$api_url);
        if($ret['result']=='success')
        {
            if(isset($ret['body']['files']))
            {
                foreach ($ret['body']['files'] as $file)
                {
                    $fileId=$file['fileId'];
                    $test=$this->delete_file($fileId,$path);
                }

                $ret['result']='success';
                return $ret;
            }
            else
            {
                $ret['result']='success';
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function delete_file($file_id,$path)
    {
        $api_url = $this->options['apiUrl'].  "/b2api/v2/";
        $auth_token =   $this->options['authorizationToken'];
        $data = array("fileId" => $file_id, "fileName" => $path);
        $post_fields = json_encode($data);
        $headers['Authorization'] = $auth_token;

        $ret=$this->remote_post('b2_delete_file_version',$headers,$post_fields,30,$api_url);

        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function upload_small_file($file,$remote_path,$task_id)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading '.basename($file).'.',$upload_job['job_data']);

        $ret['result']='failed';
        $ret['error']='upload small file error unknown';

        $retry=0;
        while($retry<15)
        {
            $ret=$this->upload_small_file_loop($file,$remote_path);
            if($ret['result']==WPVIVID_PRO_SUCCESS)
            {
                break;
            }
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('failed ret:'.json_encode($ret),'notice');
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload_small_file_loop trys:'.$retry,'notice');
            $retry++;
            if(isset($ret['error']) && $ret['error'] == 'Service Unavailable')
            {
                sleep(30);
            }
        }

        if($ret['result']=='success')
        {
            $upload_job['job_data'][basename($file)]['uploaded']=1;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
            return array('result' =>WPVIVID_PRO_SUCCESS);
        }
        else
        {
            return $ret;
        }
    }

    public function upload_small_file_loop($file,$remote_path)
    {
        $handle=fopen($file,'rb');
        $read_file = fread($handle,filesize($file));
        $path=$remote_path.'/'.basename($file);
        $path = rawurlencode($path);
        $sha1_of_file_data = sha1_file($file);

        $api_url = $this->options['uploadUrl'];
        $auth_token =   $this->options['upload_authorizationToken'];

        $headers['Authorization'] = $auth_token;

        $headers['X-Bz-File-Name'] = $path;
        $headers['Content-Type'] = 'application/zip';
        $headers['X-Bz-Content-Sha1'] = $sha1_of_file_data;
        $headers['X-Bz-Info-Author'] = "unknown";
        $headers['X-Bz-Server-Side-Encryption'] = "AES256";

        $ret=$this->remote_post('',$headers,$read_file,60,$api_url);

        if($ret['result']=='success')
        {
            return array('result' =>WPVIVID_PRO_SUCCESS);
        }
        else
        {
            return $ret;
        }
    }

    public function upload_resume($file_name,$task_id,$callback)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading '.basename($file_name).'.',$upload_job['job_data']);

        $minimum_part_size=1024*1024*5;
        $local_file = $file_name;
        $local_file_size = filesize($local_file);
        $sha1_of_parts = Array();
        $total_bytes_sent = 0;
        $bytes_sent_for_part = $minimum_part_size;
        $part_no = 1;
        $file_handle = fopen($local_file, "r");

        $ret['result']='failed';
        $ret['error']='upload error unknown';

        while($total_bytes_sent < $local_file_size)
        {
            if (($local_file_size - $total_bytes_sent) < $minimum_part_size)
            {
                $bytes_sent_for_part = ($local_file_size - $total_bytes_sent);
            }

            //
            $retry=0;
            while($retry<15)
            {
                $ret=$this->upload_loop($file_handle,$total_bytes_sent,$bytes_sent_for_part,$part_no);
                if($ret['result']==WPVIVID_PRO_SUCCESS)
                {
                    break;
                }
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('failed ret:'.json_encode($ret),'notice');
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('part_no trys:'.$retry,'notice');
                $retry++;
                if(isset($ret['error']) && $ret['error'] == 'Service Unavailable')
                {
                    sleep(30);
                }
            }
            //
            //$ret=$this->upload_loop($file_handle,$total_bytes_sent,$bytes_sent_for_part,$part_no);

            if($ret['result']=='success')
            {
                if((time() - $this -> last_time) >3)
                {
                    if(is_callable($callback))
                    {
                        call_user_func_array($callback,array($total_bytes_sent,$this -> current_file_name,
                            $this->current_file_size,$this -> last_time,$this -> last_size));
                    }
                    $this -> last_size = $total_bytes_sent;
                    $this -> last_time = time();
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload failed after try times:'.$retry,'notice');
                return $ret;
            }
            $sha1_of_parts[]=$ret['sha1'];
            $part_no++;
            $total_bytes_sent = $bytes_sent_for_part + $total_bytes_sent;
        }

        fclose($file_handle);

        return array('result' =>WPVIVID_PRO_SUCCESS,'sha1_of_parts'=>$sha1_of_parts);
    }

    public function download_file($file_name,$local_path,$size)
    {
        global $wpvivid_plugin;

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;

        $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.','notice');

        $fh = fopen($local_path, 'a');

        $this->current_file_size = $size;
        $download_chunk_size = $this->chunk_size;
        while ($start_offset < $this->current_file_size)
        {
            $last_byte = min($start_offset + $download_chunk_size - 1, $this->current_file_size - 1);

            $ret = $this->download_file_part($file_name,$start_offset,$last_byte);
            if ($ret['result']=='failed')
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $file_name. ' failed.' . $ret['error']);
            }
            if (!fwrite($fh, $ret['content']))
            {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $file_name . ' failed.');
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

        if(filesize($local_path) !== $size)
        {
            @unlink($local_path);
            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $file_name . ' failed. ' . $file_name . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }
        else{
            return array('result' => WPVIVID_PRO_SUCCESS);
        }

    }

    public function download_file_part($file_name,$start_offset,$last_byte)
    {
        $url =  $this->options['downloadUrl'] . "/file/" . $this->options['bucket'] . "/" . $file_name;
        $headers = array();
        $headers['Authorization'] =$this->options['authorizationToken'];
        $headers['Range'] ="bytes=$start_offset-$last_byte";

        $args['timeout']=30;
        $args['headers']=$headers;

        $response=wp_remote_get($url,$args);

        if(!is_wp_error($response))
        {
            if($response['response']['code']==200||$response['response']['code']==206)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['content']=$response['body'];
                return $ret;
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']= $response['response']['message'];
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

    public function upload_loop($file_handle,$total_bytes_sent,$bytes_sent_for_part,$part_no)
    {
        fseek($file_handle, $total_bytes_sent);
        $data_part = fread($file_handle, $bytes_sent_for_part);
        $sha1=sha1($data_part);
        $api_url = $this->options['uploadUrl'];
        $auth_token = $this->options['upload_authorizationToken'];

        $headers['Accept'] = "application/json";
        $headers['Authorization'] = $auth_token;
        $headers['Content-Length'] = $bytes_sent_for_part;
        $headers['X-Bz-Part-Number'] = $part_no;
        $headers['X-Bz-Content-Sha1'] =$sha1;
        global $wpvivid_plugin;
        $ret=$this->remote_post('', $headers, $data_part,300, $api_url);
        if($ret['result']=='success')
        {
            if(isset( $ret['body']['contentSha1']))
            {
                $ret['sha1']= $ret['body']['contentSha1'];
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='upload_loop failed';
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function remote_get($method,$headers,$timeout=30,$url='')
    {
        if(empty($url))
        {
            $url='https://api.backblazeb2.com/b2api/v2/';
        }

        $url=$url.$method;

        $args['timeout']=$timeout;
        $args['headers']=$headers;

        $response=wp_remote_get($url,$args);

        if(!is_wp_error($response))
        {
            if($response['response']['code']==200)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['body']=json_decode($response['body'],1);
                if($ret['body']==null)
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']= $response;
                    return $ret;
                }
                else
                {
                    return $ret;
                }
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']= $response['response']['message'];
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

    private function remote_post($method,$headers,$content,$timeout=30,$url='')
    {
        global $wpvivid_backup_pro;

        if(empty($url))
        {
            $url='https://api.backblazeb2.com/b2api/v2/';
        }

        $url=$url.$method;

        $args['body']=$content;
        $args['timeout']=$timeout;
        $args['headers']=$headers;

        $response=wp_remote_post($url,$args);

        if(!is_wp_error($response))
        {
            if($response['response']['code']==200)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['body']=json_decode($response['body'],1);
                if($ret['body']==null)
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']= $response;
                    return $ret;
                }
                else
                {
                    return $ret;
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('remote_post error result: '.json_encode($response), 'notice');

                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']=$response['response']['message'];
                return $ret;
            }
        }
        else
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('remote_post_error: '.json_encode($response),'notice');

            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']=$response->get_error_message();
            return $ret;
        }
    }
}