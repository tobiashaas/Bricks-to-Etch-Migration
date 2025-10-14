<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_AMAZONS3Class_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_REMOTE_AMAZONS3'))
    define('WPVIVID_REMOTE_AMAZONS3','amazons3');
if(!defined('WPVIVID_AMAZONS3_DEFAULT_FOLDER'))
    define('WPVIVID_AMAZONS3_DEFAULT_FOLDER','/wpvivid_backup');

class WPvivid_AMAZONS3Class_addon extends WPvivid_Remote_addon
{
    public $options;
    public $bucket='';

    private $upload_chunk_size = 5242880;
    private $download_chunk_size = 5242880;

    public $current_file_size;
    public $current_file_name;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('WPVIVID_INIT_STORAGE_TAB_AMS3'))
            {
                add_action('wpvivid_add_storage_page',array($this,'wpvivid_add_storage_page_amazons3'), 12);
                add_action('wpvivid_edit_remote_page',array($this,'wpvivid_edit_storage_page_amazons3'), 12);
                add_filter('wpvivid_get_out_of_date_remote',array($this,'wpvivid_get_out_of_date_amazons3'),10,2);
                add_filter('wpvivid_storage_provider_tran',array($this,'wpvivid_storage_provider_amazons3'),10);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'),11);
                define('WPVIVID_INIT_STORAGE_TAB_AMS3',1);
            }
        }
        else
        {
            $this->options=$options;
        }
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_AMAZONS3] = 'WPvivid_AMAZONS3Class_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_AMAZONS3)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function wpvivid_add_storage_page_amazons3()
    {
        global $wpvivid_backup_pro;
        ?>
        <div id="storage_account_amazons3"  class="storage-account-page">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Amazon S3 Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="name" placeholder="Enter a unique alias: e.g. Amazon S3-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="password" class="regular-text" autocomplete="new-password" option="amazons3" name="access" placeholder="Amazon S3 access key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Amazon S3 access key.</i><a href="https://wpvivid.com/get-amazon-access-secret-key.html" target="_blank"> How to get an AmazonS3 access key.</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text form-control" autocomplete="new-password" option="amazons3" name="secret" placeholder="Amazon S3 secret key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Amazon S3 secret key.</i><a href="https://wpvivid.com/get-amazon-access-secret-key.html" target="_blank"> How to get an AmazonS3 secret key.</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="bucket" placeholder="Amazon S3 Bucket Name(e.g. test)" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="path" placeholder="Custom Path" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="amazons3" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="amazons3" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'amazons3', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="amazons3" name="chunk_size" placeholder="Chunk size" value="5" onkeyup="value=value.replace(/\D/g,'')" />MB
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
                                    <input type="checkbox" option="amazons3" name="default" checked />Set as the default remote storage.
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
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="amazons3" name="classMode" checked />Storage class: Standard (infrequent access).
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Check the option to use Amazon S3 Standard-Infrequent Access (S3 Standard-IA) storage class for data transfer.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="amazons3" name="sse" checked />Server-side encryption.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Check the option to use Amazon S3 server-side encryption to protect data.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="amazons3" name="uncheckdelete" />Do not check DeleteObject.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <div style="float: left; padding-right: 5px;">
                                    <i>Tick this option so WPvivid won't check s3:DeleteObject permission of the user in the authentication.</i>
                                </div>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <p>s3:DeleteObject is a permission for deleting objects from Amazon S3. Without it, you are not able to delete backups on S3 from WPvivid.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                                <div style="clear: both;"></div>
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
                            <i>Click the button to connect to Amazon S3 storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function wpvivid_edit_storage_page_amazons3()
    {
        ?>
        <div id="remote_storage_edit_amazons3">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Amazon S3 Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3" name="name" placeholder="Enter a unique alias: e.g. Amazon S3-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="password" class="regular-text" autocomplete="new-password" option="edit-amazons3" name="access" placeholder="Amazon S3 access key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Amazon S3 access key</i><a href="https://wpvivid.com/get-amazon-access-secret-key.html" target="_blank"> How to get an AmazonS3 access key.</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="edit-amazons3" name="secret" placeholder="Amazon S3 secret key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your Amazon S3 secret key</i><a href="https://wpvivid.com/get-amazon-access-secret-key.html" target="_blank"> How to get an AmazonS3 secret key.</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3" name="bucket" placeholder="Amazon S3 Bucket Name(e.g. test)" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3" name="path" placeholder="Custom Path" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="edit-amazons3" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="edit-amazons3" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'amazons3', 'edit'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-amazons3" name="chunk_size" placeholder="Chunk size" value="5" onkeyup="value=value.replace(/\D/g,'')" />MB
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
                                    <input type="checkbox" option="edit-amazons3" name="classMode" />Storage class: Standard (infrequent access).
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Check the option to use Amazon S3 Standard-Infrequent Access (S3 Standard-IA) storage class for data transfer.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="edit-amazons3" name="sse" />Server-side encryption.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Check the option to use Amazon S3 server-side encryption to protect data.</i>
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
        $amazons3 = $this -> getS3();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
            return $amazons3;
        $temp_file = md5(rand());
        try
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }

            if(isset($this->options['s3Path']))
            {
                $url=$this->options['s3Path'].$temp_file;
            }
            else
            {
                $url=$root_path.'/'.$this->options['path'].'/'.$temp_file;
            }

            if(!$amazons3 -> putObjectString($temp_file,$this -> bucket,$url))
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>'We successfully accessed the bucket, but create test file failed.');
            }

            if(isset($this->options['uncheckdelete']) && $this->options['uncheckdelete']){
            }
            else{
                if(!$amazons3 -> deleteObject($this -> bucket,$url))
                {
                    return array('result'=>WPVIVID_PRO_FAILED,'error'=>'We successfully accessed the bucket, and create test file succeed, but delete test file failed.');
                }
            }
        }catch(Exception $e){
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$e -> getMessage());
        }
        return array('result'=>WPVIVID_PRO_SUCCESS);
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

        if(!isset($this->options['access']))
        {
            $ret['error']="Warning: The access key for Amazon S3 is required.";
            return $ret;
        }

        $this->options['access']=sanitize_text_field($this->options['access']);

        if(empty($this->options['access']))
        {
            $ret['error']="Warning: The access key for Amazon S3 is required.";
            return $ret;
        }

        if(!isset($this->options['secret']))
        {
            $ret['error']="Warning: The storage secret key is required.";
            return $ret;
        }

        $this->options['secret']=sanitize_text_field($this->options['secret']);

        if(empty($this->options['secret']))
        {
            $ret['error']="Warning: The storage secret key is required.";
            return $ret;
        }
        $this->options['secret'] = base64_encode($this->options['secret']);
        $this->options['is_encrypt'] = 1;

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

        if(!isset($this->options['root_path']))
        {
            $ret['error']="Warning: A root path is required.";
            return $ret;
        }
        $this->options['root_path']=sanitize_text_field($this->options['root_path']);
        if(empty($this->options['root_path'])){
            $ret['error']="Warning: A root path is required.";
            return $ret;
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

    public function upload($task_id,$files,$callback='')
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $amazons3 = $this -> getS3();

        if(isset($this->options['chunk_size']))
            $this->upload_chunk_size = $this->options['chunk_size'];

        if(is_array($amazons3) && $amazons3['result'] == WPVIVID_PRO_FAILED)
            return $amazons3;

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

        foreach ($files as $file){
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
            $result = $this -> _put($task_id,$amazons3,$file,$callback);
            if($result['result'] !==WPVIVID_PRO_SUCCESS){
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploading '.basename($file).' failed.','notice');
                return $result;
            }
            else
            {
                WPvivid_Custom_Interface_addon::wpvivid_reset_backup_retry_times($task_id);
            }
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
            $upload_job['job_data'][basename($file)]['uploaded']=1;
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }
    private function _put($task_id,$amazons3,$file,$callback)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        $this -> current_file_size = filesize($file);
        $this -> current_file_name = basename($file);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if(isset($this->options['s3Path']))
        {
            $url=$this->options['s3Path'].$this -> current_file_name;
        }
        else
        {
            $url=$root_path.'/'.$this->options['path'].'/'.$this -> current_file_name;
        }
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog($url,'notice');

        $chunk_num = floor($this -> current_file_size / $this -> upload_chunk_size);
        if($this -> current_file_size % $this -> upload_chunk_size > 0) $chunk_num ++;

        for($times=0;$times<WPVIVID_PRO_REMOTE_CONNECT_RETRY_TIMES;$times++)
        {
            try
            {
                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading '.basename($file).'.',$upload_job['job_data']);

                if(true)
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Creating Multipart Upload.','notice');
                    if(!empty($upload_job['job_data'][basename($file)]['upload_id']))
                    {
                        $build_id = $upload_job['job_data'][basename($file)]['upload_id'];
                    }else{
                        $build_id = $amazons3 -> initiateMultipartUpload($this -> bucket,$url);
                        $upload_job['job_data'][basename($file)]['upload_id'] = $build_id;
                        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'InitiateMultipartUpload, created build id of '.basename($file).'.',$upload_job['job_data']);
                    }
                    if(!empty($upload_job['job_data'][basename($file)]['upload_chunks']))
                    {
                        $chunks = $upload_job['job_data'][basename($file)]['upload_chunks'];
                    }else{
                        $chunks = array();
                        $upload_job['job_data'][basename($file)]['upload_chunks'] = $chunks;
                        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start multipartupload of '.basename($file).'.',$upload_job['job_data']);
                    }

                    for($i =sizeof($chunks);$i <$chunk_num;$i ++)
                    {
                        $chunk_id = $amazons3 -> uploadPart($this -> bucket,$url,$build_id,$file,$i+1,$this ->upload_chunk_size);
                        if(!$chunk_id){
                            $chunks = array();
                            $upload_job['job_data'][basename($file)]['upload_chunks'] = $chunks;
                            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start multipartupload of '.basename($file).'.',$upload_job['job_data']);
                            return array('result' => WPVIVID_PRO_FAILED,'error' => 'upload '.$file.' failed.');
                        }
                        $chunks[] = $chunk_id;
                        $upload_job['job_data'][basename($file)]['upload_chunks'] = $chunks;
                        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Uploading '.basename($file).'.',$upload_job['job_data']);

                        $offset = (($i + 1) * $this -> upload_chunk_size) > $this -> current_file_size ? $this -> current_file_size : (($i + 1) * $this -> upload_chunk_size);
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
                    }
                    $result = $amazons3 -> completeMultipartUpload($this -> bucket,$url,$build_id,$chunks);
                }else{
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploaded files are less than 5M.','notice');
                    $input = $amazons3 -> inputFile($file);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('putObject input:'.json_encode($input).' bucket:'.$this->bucket.' url:'.$url,'notice');
                    $result = $amazons3 -> putObject($input,$this ->bucket,$url);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('putObject end:'.$result,'notice');
                }
            }catch(Exception $e)
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload put catch error code: '.json_encode($e -> getCode()),'notice');
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('upload put catch error: '.json_encode($e -> getMessage()),'notice');
                if(strstr($e -> getMessage(), 'upload ID may be invalid') ||
                    strstr($e -> getMessage(), 'We encountered an internal error'))
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('retry times: '.$times,'notice');
                    $upload_job['job_data'][basename($file)]['upload_id'] = '';
                    $upload_job['job_data'][basename($file)]['upload_chunks'] = '';
                    continue;
                }
                return array('result' => WPVIVID_PRO_FAILED,'error'=>$e -> getMessage());
            }
            if($result){
                break;
            }
            if(!$result && $times == (WPVIVID_PRO_REMOTE_CONNECT_RETRY_TIMES - 1))
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Uploading '.$file.' to Amazon S3 server failed. '.$file.' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
            sleep(WPVIVID_PRO_REMOTE_CONNECT_RETRY_INTERVAL);
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function download($file,$local_path,$callback = '')
    {
        try {
            global $wpvivid_plugin;
            $this->current_file_name = $file['file_name'];
            $this->current_file_size = $file['size'];

            if(isset($this->options['chunk_size']))
                $this->download_chunk_size = $this->options['chunk_size'];

            $wpvivid_plugin->wpvivid_download_log->WriteLog('Get amazons3 client.','notice');
            $amazons3 = $this->getS3();
            if (is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED) {
                return $amazons3;
            }
            if(isset($this->options['s3Path']))
            {
                $url=$this->options['s3Path']. $this -> current_file_name;
            }
            else
            {
                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }
                if(isset($file['remote_path']))
                {
                    $url=$root_path.'/'.$this->options['path'].'/'. $file['remote_path'].'/'.$this -> current_file_name;
                }
                else
                {
                    $url=$root_path.'/'.$this->options['path'].'/'. $this -> current_file_name;
                }
            }
            $file_path = trailingslashit($local_path) . $this->current_file_name;
            $start_offset = file_exists($file_path) ? filesize($file_path) : 0;
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.','notice');
            $fh = fopen($file_path, 'a');
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'] ,'notice');
            while ($start_offset < $this->current_file_size) {
                $last_byte = min($start_offset + $this->download_chunk_size - 1, $this->current_file_size - 1);
                $headers['Range'] = "bytes=$start_offset-$last_byte";
                $response = $amazons3->getObject($this->bucket,$url, $fh, $headers['Range']);
                if (!$response)
                    return array('result' => WPVIVID_PRO_FAILED, 'error' => 'download ' . $url. ' failed.');
                clearstatcache();
                $state = stat($file_path);
                $start_offset = $state['size'];

                if ((time() - $this->last_time) > 3) {
                    if (is_callable($callback)) {
                        call_user_func_array($callback, array($start_offset, $this->current_file_name,
                            $this->current_file_size, $this->last_time, $this->last_size));
                    }
                    $this->last_size = $start_offset;
                    $this->last_time = time();
                }
            }
            @fclose($fh);

            if(filesize($file_path) == $file['size']){
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

            if ($res !== TRUE) {
                @unlink($file_path);
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $file['file_name'] . ' failed. ' . $file['file_name'] . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }

            return array('result' => WPVIVID_PRO_SUCCESS);
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('result'=>WPVIVID_PRO_FAILED, 'error'=>$message);
        }
    }

    public function chunk_download($download_info,$callback)
    {
        try
        {
            $this -> current_file_name = $download_info['file_name'];
            $this -> current_file_size = $download_info['size'];
            $local_path = $download_info['local_path'];
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

            if(isset($this->options['chunk_size']))
                $this->download_chunk_size = $this->options['chunk_size'];

            $amazons3 = $this->getS3();
            if (is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
            {
                return $amazons3;
            }
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }

            $url=$root_path.'/'.$this->options['path'].'/'. $this -> current_file_name;

            $start_offset = file_exists($local_path) ? filesize($local_path) : 0;

            $time_limit = 30;
            $start_time = time();

            while ($start_offset < $this->current_file_size)
            {
                $last_byte = min($start_offset + $this->download_chunk_size - 1, $this->current_file_size - 1);
                $headers['Range'] = "bytes=$start_offset-$last_byte";
                $response = $amazons3->getObject($this->bucket,$url, $fh, $headers['Range']);
                if (!$response)
                    return array('result' => WPVIVID_PRO_FAILED, 'error' => 'download ' . $url. ' failed.');
                clearstatcache();
                $state = stat($local_path);
                $start_offset = $state['size'];

                if ((time() - $this->last_time) > 3)
                {
                    if (is_callable($callback)) {
                        call_user_func_array($callback, array($start_offset, $this->current_file_name,
                            $this->current_file_size, $this->last_time, $this->last_size));
                    }
                    $this->last_size = $start_offset;
                    $this->last_time = time();
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
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('result'=>WPVIVID_PRO_FAILED, 'error'=>$message);
        }
    }

    public function upload_rollback($file,$folder,$slug,$version)
    {
        $amazons3 = $this -> getS3();

        if(isset($this->options['chunk_size']))
            $this->upload_chunk_size = $this->options['chunk_size'];

        if(is_array($amazons3) && $amazons3['result'] == WPVIVID_PRO_FAILED)
            return $amazons3;

        $this -> current_file_size = filesize($file);
        $this -> current_file_name = basename($file);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $url=$root_path.'/'.$this->options['path'].'/rollback_ex/'.$folder.'/'.$slug.'/'.$version.'/'.basename($file);

        $chunk_num = floor($this -> current_file_size / $this -> upload_chunk_size);
        if($this -> current_file_size % $this -> upload_chunk_size > 0) $chunk_num ++;

        $build_id = $amazons3 -> initiateMultipartUpload($this -> bucket,$url);
        $chunks = array();

        for($i =sizeof($chunks);$i <$chunk_num;$i ++)
        {
            $chunk_id = $amazons3 -> uploadPart($this -> bucket,$url,$build_id,$file,$i+1,$this ->upload_chunk_size);
            if(!$chunk_id)
            {
                $chunks = array();
                $upload_job['job_data'][basename($file)]['upload_chunks'] = $chunks;
                return array('result' => WPVIVID_PRO_FAILED,'error' => 'upload '.$file.' failed.');
            }
            $chunks[] = $chunk_id;
        }
        $result = $amazons3 -> completeMultipartUpload($this -> bucket,$url,$build_id,$chunks);

        if(!$result)
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Uploading '.$file.' to Amazon S3 server failed. '.$file.' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function download_rollback($download_info)
    {
        $amazons3 = $this->getS3();
        if (is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }

        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];

        $type=$download_info['type'];
        $slug=$download_info['slug'];
        $version=$download_info['version'];

        $local_path = $download_info['local_path'];

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;
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

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $url=$root_path.'/'.$this->options['path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$this -> current_file_name;


        try
        {
            while ($start_offset < $this->current_file_size)
            {
                $last_byte = min($start_offset + $this->download_chunk_size - 1, $this->current_file_size - 1);
                $headers['Range'] = "bytes=$start_offset-$last_byte";
                $response = $amazons3->getObject($this->bucket,$url, $fh, $headers['Range']);
                if (!$response)
                    return array('result' => WPVIVID_PRO_FAILED, 'error' => 'download ' . $url. ' failed.');
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
        catch(Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
        }
    }

    public function cleanup($files)
    {
        $amazons3 = $this -> getS3();

        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
            return $amazons3;

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        foreach ($files as $file)
        {
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $url=$root_path.'/'.$this->options['path'].'/'. $file['remote_path'].'/'.$file['file_name'];
                }
                else
                {
                    $url=$root_path.'/'.$this->options['path'].'/'.$file['file_name'];
                }
            }
            else
            {
                if(isset($this->options['s3Path']))
                {
                    $url=$this->options['s3Path'].$file;
                }
                else
                {
                    $url=$root_path.'/'.$this->options['path'].'/'.$file;
                }
            }

            $amazons3 -> deleteObject($this -> bucket , $url);
        }
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        $amazons3 = $this -> getS3();

        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
            return $amazons3;

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $url=$root_path.'/'.$this->options['path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$slug.'.zip';
        $amazons3 -> deleteObject($this -> bucket , $url);

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    private function getS3()
    {
        if(isset($this->options['s3Path']))
        {
            $path_temp = str_replace('s3://','',$this->options['s3Path']);
            if (preg_match("#^/*([^/]+)/(.*)$#", $path_temp, $bmatches))
            {
                $this->bucket = $bmatches[1];
                if(empty($bmatches[2])){
                    $this->options['s3Path'] = '';
                }else{
                    $this->options['s3Path'] = trailingslashit($bmatches[2]);
                }
            } else {
                $this->bucket = $path_temp;
                $this->options['s3Path'] = '';
            }
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $secret = base64_decode($this->options['secret']);
            }
            else {
                $secret = $this->options['secret'];
            }

            if(class_exists("WPvivid_Base_S3_Ex"))
            {
                $amazons3 = new WPvivid_Base_S3_Ex($this->options['access'],$secret);
            }
            else
            {
                $amazons3 = new WPvivid_Base_S3($this->options['access'],$secret);
            }

            $amazons3 -> setExceptions();
            if($this->options['classMode'])
                $amazons3 -> setStorageClass();
            if($this->options['sse'])
                $amazons3 -> setServerSideEncryption();

            try{
                $region = $amazons3 -> getBucketLocation($this->bucket);
            }catch(Exception $e){
                return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
            }
            $endpoint = $this -> getEndpoint($region);
            if(!empty($endpoint))
                $amazons3 -> setEndpoint($endpoint);
            return $amazons3;
        }
        else
        {
            $this->bucket= $this->options['bucket'];
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $secret = base64_decode($this->options['secret']);
            }
            else {
                $secret = $this->options['secret'];
            }
            if(class_exists("WPvivid_Base_S3_Ex"))
            {
                $amazons3 = new WPvivid_Base_S3_Ex($this->options['access'],$secret);
            }
            else
            {
                $amazons3 = new WPvivid_Base_S3($this->options['access'],$secret);
            }
            $amazons3 -> setExceptions();
            if($this->options['classMode'])
                $amazons3 -> setStorageClass();
            if($this->options['sse'])
                $amazons3 -> setServerSideEncryption();

            try{
                $region = $amazons3 -> getBucketLocation($this->bucket);
            }catch(Exception $e){
                return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
            }

            $amazons3->setSignatureVersion('v4');
            $amazons3->setRegion($region);

            $endpoint = $this -> getEndpoint($region);
            if(!empty($endpoint))
                $amazons3 -> setEndpoint($endpoint);
            return $amazons3;
        }

    }
    private function getEndpoint($region){
        switch ($region) {
            case 'EU':
            case 'eu-west-1':
                $endpoint = 's3-eu-west-1.amazonaws.com';
                break;
            case 'US':
            case 'us-east-1':
                $endpoint = 's3.amazonaws.com';
                break;
            case 'eu-south-1':
                $endpoint = 's3.eu-south-1.amazonaws.com';
                break;
            case 'us-west-1':
            case 'us-east-2':
            case 'us-west-2':
            case 'eu-west-2':
            case 'eu-west-3':
            case 'ap-southeast-1':
            case 'ap-southeast-2':
            case 'ap-northeast-2':
            case 'sa-east-1':
            case 'ca-central-1':
            case 'us-gov-west-1':
            case 'eu-north-1':
            case 'eu-central-1':
                $endpoint = 's3-'.$region.'.amazonaws.com';
                break;
            case 'ap-northeast-1':
                $endpoint = 's3.'.$region.'.amazonaws.com';
                break;
            case 'ap-south-1':
                $endpoint = 's3.'.$region.'.amazonaws.com';
                break;
            case 'cn-north-1':
                $endpoint = 's3.'.$region.'.amazonaws.com.cn';
                break;
            case 'af-south-1':
                $endpoint = 's3.'.$region.'.amazonaws.com';
                break;
            default:
                $endpoint = 's3.amazonaws.com';
                break;
        }
        return $endpoint;
    }

    public function wpvivid_get_out_of_date_amazons3($out_of_date_remote, $remote)
    {
        if($remote['type'] == WPVIVID_REMOTE_AMAZONS3)
        {
            if(isset($remote['s3Path']))
                $out_of_date_remote = $remote['s3Path'];
            else
                $out_of_date_remote = $remote['path'];
        }
        return $out_of_date_remote;
    }

    public function wpvivid_storage_provider_amazons3($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_AMAZONS3){
            $storage_type = 'Amazon S3';
        }
        return $storage_type;
    }

    private function getS3Ex()
    {
        $this->bucket= $this->options['bucket'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $secret = base64_decode($this->options['secret']);
        }
        else {
            $secret = $this->options['secret'];
        }

        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR. 'addons2/backup_pro/class-wpvivid-base-s3-addon.php';

        if(class_exists("WPvivid_Base_S3_Ex"))
        {
            $amazons3 = new WPvivid_Base_S3_Ex($this->options['access'],$secret);
        }
        else
        {
            $amazons3 = new WPvivid_Base_S3($this->options['access'],$secret);
        }
        $amazons3 -> setExceptions();
        try
        {
            $region = $amazons3 -> getBucketLocation($this->bucket);
        }catch(Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
        }

        $amazons3->setSignatureVersion('v4');
        $amazons3->setRegion($region);

        $endpoint = $this -> getEndpoint($region);
        if(!empty($endpoint))
            $amazons3 -> setEndpoint($endpoint);
        return $amazons3;
    }

    public function scan_folder_backup($folder_type)
    {
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($folder_type === 'Common')
        {
            if(!isset($this->options['path']))
            {
                $ret['result']='failed';
                $ret['error']='path not found';
                return $ret;
            }

            $path=$root_path.'/'.$this->options['path'];

            $response=$this->_scan_folder_backup($path,$amazons3);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                $ret['path']=$response['path'];
            }
            else
            {
                return $response;
            }
        }
        else if($folder_type === 'Migrate')
        {
            $path=$root_path.'/migrate';
            $response=$this->_scan_folder_backup($path,$amazons3);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }
            else
            {
                return $response;
            }
        }
        else if($folder_type === 'Rollback'){
            $remote_folder=$root_path.'/'.$this->options['path'].'/rollback';
            $response=$this->_scan_folder_backup($remote_folder,$amazons3);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['rollback']= $response['backup'];
            }
            else
            {
                return $response;
            }
        }
        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function scan_child_folder_backup($sub_path)
    {
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }

        if(!isset($this->options['path']))
        {
            $ret['result']='failed';
            $ret['error']='path not found';
            return $ret;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path=$root_path.'/'.$this->options['path'];

        $response=$this->_scan_child_folder_backup($path,$sub_path,$amazons3);

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

    public function _scan_folder_backup($path,$amazons3)
    {
        $response = $amazons3 -> listObject($this -> bucket,$path);
        if($response['result']=='failed')
        {
            return $response;
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        $ret['backup']=array();
        $ret['path']=array();
        $files=array();
        foreach ($response['data'] as $item)
        {
            if(dirname($item['name'])==$path)
            {
                $file_data['file_name']=basename($item['name']);
                $file_data['size']=$item['size'];
                $files[]=$file_data;
            }
            else
            {
                $sub_path=dirname($item['name']);

                if(dirname($sub_path)==$path)
                {
                    if(!in_array(basename($sub_path),$ret['path']))
                        $ret['path'][]=basename($sub_path);
                    //$file_data['file_name']=basename($item['name']);
                    //$file_data['size']=$item['size'];
                    //$file_data['remote_path']=basename($sub_path);
                    //$files[]=$file_data;
                }
            }
        }
        if(!empty($files))
        {
            global $wpvivid_backup_pro;
            $ret['backup']=$wpvivid_backup_pro->func->get_backup($files);
        }

        return $ret;
    }

    public function _scan_child_folder_backup($path,$sub_path,$amazons3)
    {
        $response = $amazons3 -> listObject($this -> bucket,$path);
        if($response['result']=='failed')
        {
            return $response;
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        $ret['backup']=array();
        $ret['files']=array();
        foreach ($response['data'] as $item)
        {
            if(dirname($item['name'])==$path.'/'.$sub_path)
            {
                $file_data['file_name']=basename($item['name']);
                $file_data['size']=$item['size'];
                $file_data['remote_path']=$sub_path;
                $ret['files'][]=$file_data;
            }
        }
        if(!empty($ret['files']))
        {
            global $wpvivid_backup_pro;
            $ret['backup']=$wpvivid_backup_pro->func->get_backup($ret['files']);
        }

        return $ret;
    }

    public function scan_folder_backup_ex($folder_type)
    {
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }

        $path=array();
        if($folder_type=='all_backup')
        {
            $ret['result']='success';
            $ret['remote']=array();

            $response=$this->_get_common_backups($amazons3);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                $path=$response['path'];
            }

            $ret['migrate']=array();

            $response=$this->_get_migrate_backups($amazons3);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }

            $ret['rollback']=array();

            $response=$this->_get_rollback_backups($amazons3);
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
                        $response=$this->_get_incremental_backups($incremental_path,$amazons3);
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

            $response=$this->_get_common_backups($amazons3);
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

            $response=$this->_get_migrate_backups($amazons3);
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

            $response=$this->_get_rollback_backups($amazons3);
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

            $response=$this->_get_common_backups($amazons3);
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
                        $response=$this->_get_incremental_backups($incremental_path,$amazons3);
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

    public function _get_common_backups($amazons3)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path=$root_path.'/'.$this->options['path'];

        return $this->_scan_folder_backup($path,$amazons3);
    }

    public function _get_migrate_backups($amazons3)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path=$root_path.'/migrate';

        return $this->_scan_folder_backup($path,$amazons3);
    }

    public function _get_rollback_backups($amazons3)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path=$root_path.'/'.$this->options['path'].'/rollback';

        return $this->_scan_folder_backup($path,$amazons3);
    }

    public function _get_incremental_backups($incremental_path,$amazons3)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path=$root_path.'/'.$this->options['path'].'/'.$incremental_path;

        $ret=$this->_scan_folder_backup($path,$amazons3);
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
        try
        {
            $amazons3 = $this->getS3();
            if (is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED) {
                return $amazons3;
            }

            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }

            if($folder_type=='Manual')
            {
                $url=$root_path.'/'.$this->options['path'].'/'. $backup_info_file;
            }
            else if($folder_type=='Migrate')
            {
                $url=$root_path.'/migrate/'. $backup_info_file;
            }
            else if($folder_type=='Rollback')
            {
                $url=$root_path.'/'.$this->options['path'].'/rollback/'. $backup_info_file;
            }
            else if($folder_type=='Incremental')
            {
                $url=$root_path.'/'.$this->options['path'].'/'.$incremental_path.'/'. $backup_info_file;
            }
            else
            {
                $ret['result'] = 'failed';
                $ret['error'] = 'The selected remote storage does not support scanning.';
                return $ret;
            }

            $response = $amazons3->getObject($this->bucket,$url);
            if (!$response)
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'download ' . $url. ' failed.');

            $ret['result']='success';
            $ret['backup_info']=json_decode($response->body,1);
            return $ret;
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('result'=>WPVIVID_PRO_FAILED, 'error'=>$message);
        }
    }

    public function scan_rollback($type)
    {
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($type === 'plugins')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback_ex/plugins';

            $response=$this->_scan_folder($path,$amazons3);

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
            $path=$root_path.'/'.$this->options['path'].'/rollback_ex/themes';

            $response=$this->_scan_folder($path,$amazons3);

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

    public function _scan_folder($path,$amazons3)
    {
        $response = $amazons3 -> listObject($this -> bucket,$path);
        if($response['result']=='failed')
        {
            return $response;
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        $ret['path']=array();

        foreach ($response['data'] as $item)
        {
            $new_path=str_replace($path.'/','',$item['name']);
            $parts = explode('/',$new_path);

            if(!empty($parts))
            {
                if(!in_array($parts[0],$ret['path']))
                    $ret['path'][]=$parts[0];
            }
        }

        return $ret;
    }

    public function get_rollback_data($type,$slug)
    {
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($type === 'plugins')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback_ex/plugins/'.$slug;

            $response=$this->_scan_folder($path,$amazons3);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $response_path= $response['path'];
                if(!empty($response_path))
                {
                    foreach ($response_path as $version)
                    {
                        $version_path=$path.'/'.$version;
                        $response=$this->_scan_file($version_path,$slug.'.zip',$amazons3);
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
            $path=$root_path.'/'.$this->options['path'].'/rollback_ex/themes/'.$slug;

            $response=$this->_scan_folder($path,$amazons3);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $response_path= $response['path'];
                if(!empty($response_path))
                {
                    foreach ($response_path as $version)
                    {
                        $version_path=$path.'/'.$version;
                        $response=$this->_scan_file($version_path,$slug.'.zip',$amazons3);
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

    public function _scan_file($path,$file,$amazons3)
    {
        $response = $amazons3 -> listObject($this -> bucket,$path);
        if($response['result']=='failed')
        {
            return $response;
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;

        if(empty($response['data']))
        {
            $ret['result']='failed';
            $ret['error']='Failed to get file information.';
            return $ret;
        }


        foreach ($response['data'] as $item)
        {
            if(basename($item['name'])==$file)
            {
                $file_data['file_name']=basename($item['name']);
                $file_data['size']=$item['size'];
                $file_data['mtime']=strtotime($item['mtime']);
                $ret['file']=$file_data;
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

    public function delete_old_backup($backup_count,$db_count)
    {
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'];

        $response=$this->_scan_folder_backup($path,$amazons3);

        if(isset($response['backup']))
        {
            $backups=$response['backup'];
            $folders=$response['path'];
            global $wpvivid_backup_pro;
            $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
            $folders_count=apply_filters('wpvivid_get_backup_folders_count',0);
            $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);
            foreach ($folders as $folder)
            {
                $child_response=$this->_scan_child_folder_backup($path,$folder,$amazons3);
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
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'].'/rollback';

        $response=$this->_scan_folder_backup($path,$amazons3);

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
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return false;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        if($folder_type=== 'Common')
        {
            $path=$root_path.'/'.$this->options['path'];
        }
        else if($folder_type === 'Rollback')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback';
        }
        else
        {
            return false;
        }

        $response=$this->_scan_folder_backup($path,$amazons3);

        if(isset($response['backup']))
        {
            $backups=$response['backup'];
            global $wpvivid_backup_pro;
            $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
            if(!empty($files))
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
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return $amazons3;
        }
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($type=='Rollback')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback';

            $response=$this->_scan_folder_backup($path,$amazons3);

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
            $path=$root_path.'/'.$this->options['path'];

            $response=$this->_scan_folder_backup($path,$amazons3);

            if(isset($response['path']))
            {
                $folders=$response['path'];
                global $wpvivid_backup_pro;
                $files = array();
                $folders_count=$backup_count;
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);
                foreach ($folders as $folder)
                {
                    $child_response=$this->_scan_child_folder_backup($path,$folder,$amazons3);
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
            }
        }
        else
        {
            $path=$root_path.'/'.$this->options['path'];

            $response=$this->_scan_folder_backup($path,$amazons3);

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
        $amazons3 = $this->getS3Ex();
        if(is_array($amazons3) && $amazons3['result'] === WPVIVID_PRO_FAILED)
        {
            return false;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($type=='Rollback')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback';

            $response=$this->_scan_folder_backup($path,$amazons3);

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
            $path=$root_path.'/'.$this->options['path'];

            $response=$this->_scan_folder_backup($path,$amazons3);

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
            $path=$root_path.'/'.$this->options['path'];

            $response=$this->_scan_folder_backup($path,$amazons3);

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
