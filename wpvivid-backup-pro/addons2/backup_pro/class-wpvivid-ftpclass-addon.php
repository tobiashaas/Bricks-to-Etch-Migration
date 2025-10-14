<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_FTPClass_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
if(!defined('WPVIVID_REMOTE_FTP'))
    define('WPVIVID_REMOTE_FTP','ftp');

class WPvivid_FTPClass_addon extends WPvivid_Remote_addon
{
    private $time_out = 20;
    private $callback;
    private $options=array();

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if (!defined('WPVIVID_INIT_STORAGE_TAB_FTP')) {
                add_action('wpvivid_add_storage_page',array($this,'wpvivid_add_storage_page_ftp'), 14);
                add_action('wpvivid_edit_remote_page',array($this,'wpvivid_edit_storage_page_ftp'), 14);
                add_filter('wpvivid_get_out_of_date_remote',array($this,'wpvivid_get_out_of_date_ftp'),10,2);
                add_filter('wpvivid_storage_provider_tran',array($this,'wpvivid_storage_provider_ftp'),10);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'), 11);
                define('WPVIVID_INIT_STORAGE_TAB_FTP', 1);
            }

        }else{
            $this->options = $options;
        }
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_FTP] = 'WPvivid_FTPClass_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_FTP)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function wpvivid_add_storage_page_ftp()
    {
        global $wpvivid_backup_pro;
        ?>
        <div id="storage_account_ftp" class="storage-account-page">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your FTP Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="ftp" name="name" placeholder="Enter a unique alias: e.g. FTP-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="ftp" name="server" placeholder="Server Address" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the server address.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="ftp" name="port" value="21" placeholder="FTP server port" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the custom server port.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="ftp" name="username" placeholder="FTP login" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your FTP server user name.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="ftp" name="password" placeholder="FTP password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the FTP server password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="ftp" name="path" placeholder="Absolute path must exist(e.g. /home/username/)" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Enter an existing absolute path in which you want to create a parent folder for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="ftp" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Customize a parent folder under the absolute path for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="ftp" name="custom_path" placeholder="Custom Path(e.g. myfolder)" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Customize the name of folder under the parent folder where you want to store %s backups.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></i>
                            </div>
                        </td>
                    </tr>

                    <!--<tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="ftp" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="ftp" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'ftp', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="ftp" name="default" checked />Set as the default remote storage.
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
                                    <input type="checkbox" option="ftp" name="use_ftps" />Check this option to enable FTP-SSL connection.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Check this option to enable FTP-SSL connection while transferring files. Make sure the FTP server you are configuring supports FTPS connections.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="ftp" name="passive" checked />Uncheck this to enable FTP active mode.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Uncheck the option to use FTP active mode when transferring files. Make sure the FTP server you are configuring supports the active FTP mode.</i>
                            </div>
                        </td>
                    </tr>
                </form>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input class="button-primary" type="submit" option="add-remote" value="Test and Add">
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Click the button to connect to FTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function wpvivid_edit_storage_page_ftp()
    {
        ?>
        <div id="remote_storage_edit_ftp">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your FTP Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-ftp" name="name" placeholder="Enter a unique alias: e.g. FTP-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-ftp" name="server" placeholder="Server Address" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the server address.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-ftp" name="port" placeholder="FTP server port" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the custom server port.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-ftp" name="username" placeholder="FTP login" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter your FTP server user name.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="edit-ftp" name="password" placeholder="FTP password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the FTP server password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-ftp" name="path" placeholder="Absolute path must exist(e.g. /home/username/)" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Enter an existing absolute path in which you want to create a parent folder for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-ftp" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Customize a parent folder under the absolute path for holding %s folders.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-ftp" name="custom_path" placeholder="Custom Path(e.g. myfolder)"/>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Customize the name of folder under the parent folder where you want to store %s backups.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></i>
                            </div>
                        </td>
                    </tr>

                    <!--<tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="edit-ftp" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="edit-ftp" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'ftp', 'edit'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="edit-ftp" name="use_ftps" />Check this option to enable FTP-SSL connection.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Check this option to enable FTP-SSL connection while transferring files. Make sure the FTP server you are configuring supports FTPS connections.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="edit-ftp" name="passive" checked />Uncheck this to enable FTP active mode.
                                </label>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Uncheck the option to use FTP active mode when transferring files. Make sure the FTP server you are configuring supports the active FTP mode.</i>
                            </div>
                        </td>
                    </tr>
                </form>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input class="button-primary" type="submit" option="edit-remote" value="Save Changes">
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
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $path = $this->options['path'];
        $port = empty($this->options['port'])?21:$this->options['port'];
        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);
        $ret= $this->do_chdir($conn,$path);
        if($ret['result']=='success')
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }

            $path=$this->options['path'].$root_path.'/';
            $ret= $this->do_chdir($conn,$path);

            if($ret['result']=='success')
            {
                $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'];
                $ret=$this->do_chdir($conn,$path);
                if($ret['result']=='success')
                {
                    $temp_file = md5(rand());
                    $temp_path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$temp_file;
                    file_put_contents($temp_path,print_r($temp_file,true));
                    if(! ftp_put($conn,trailingslashit($path).$temp_file,$temp_path,FTP_BINARY))
                    {
                        return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Failed to add FTP storage. It can be because the FTP folder permissions are insufficient, or calling PHP ftp_put function of your web server failed. Please make sure the folder has write permission and the ftp_put function works properly.');
                    }
                    @unlink($temp_path);
                    @ftp_delete($conn,trailingslashit($path).$temp_file);

                    $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';
                    $ret=$this->do_chdir($conn,$path);
                    return $ret;
                }
                else
                {
                    return $ret;
                }
            }
            else
            {
                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function sanitize_options($skip_name='')
    {
        $ret['result']=WPVIVID_PRO_FAILED;
        if(!isset($this->options['name']))
        {
            $ret['error']=__('Warning: An alias for remote storage is required.','wpvivid');
            return $ret;
        }

        $this->options['name']=sanitize_text_field($this->options['name']);

        if(empty($this->options['name']))
        {
            $ret['error']=__('Warning: An alias for remote storage is required.','wpvivid');
            return $ret;
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

        $this->options['server']=sanitize_text_field($this->options['server']);

        if(empty($this->options['server']))
        {
            $ret['error']="Warning: The FTP server is required.";
            return $ret;
        }

        $res = explode(':',$this -> options['server']);
        if(sizeof($res) > 1)
        {
            $this ->options['host'] = $res[0];

        }else{
            $this -> options['host'] = $res[0];
        }

        if(!isset($this->options['port'])){
            $ret['error']="Warning: The servers port is required.";
            return $ret;
        }

        if(empty($this->options['port'])){
            $ret['error']="Warning: The servers port is required.";
            return $ret;
        }

        if(!isset($this->options['username']))
        {
            $ret['error']="Warning: The FTP login is required.";
            return $ret;
        }

        $this->options['username']=sanitize_text_field($this->options['username']);

        if(empty($this->options['username']))
        {
            $ret['error']="Warning: The FTP login is required.";
            return $ret;
        }

        if(!isset($this->options['password'])||empty($this->options['password']))
        {
            $ret['error']="Warning: The FTP password is required.";
            return $ret;
        }

        //$this->options['password']=$this->options['password'];

        if(empty($this->options['password']))
        {
            $ret['error']="Warning: The FTP password is required.";
            return $ret;
        }
        $this->options['password'] = base64_encode($this->options['password']);
        $this->options['is_encrypt'] = 1;

        if(!isset($this->options['path'])||empty($this->options['path']))
        {
            $ret['error']="Warning: The storage path is required.";
            return $ret;
        }

        $this->options['path']=sanitize_text_field($this->options['path']);
        $this->options['path']=trailingslashit($this->options['path']);
        if(empty($this->options['path']))
        {
            $ret['error']="Warning: The storage path is required.";
            return $ret;
        }

        if($this->options['path']=='/')
        {
            $ret['error']="Warning: Root directory is forbidden to set to '/'.";
            return $ret;
        }

        if(!isset($this->options['root_path'])||empty($this->options['root_path']))
        {
            $ret['error']="Warning: The root path is required.";
            return $ret;
        }
        $this->options['root_path'] = sanitize_text_field($this->options['root_path']);
        $this->options['root_path']=trailingslashit($this->options['root_path']);
        if(empty($this->options['root_path']))
        {
            $ret['error']="Warning: The root path is required.";
            return $ret;
        }

        if($this->options['root_path'] == '/')
        {
            $ret['error']="The backup folder name cannot be '/'";
            return $ret;
        }

        if(!isset($this->options['custom_path'])||empty($this->options['custom_path']))
        {
            $ret['error']="Warning: The custom path is required.";
            return $ret;
        }

        $this->options['custom_path']=sanitize_text_field($this->options['custom_path']);
        $this->options['custom_path']=untrailingslashit($this->options['custom_path']);
        if(empty($this->options['custom_path']))
        {
            $ret['error']="Warning: The custom path is required.";
            return $ret;
        }

        if($this->options['custom_path'] == '/')
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

    public function do_connect($server,$username,$password,$port = 21)
    {
        if(isset($this->options['use_ftps'])&&$this->options['use_ftps'])
        {
            $conn = ftp_ssl_connect($server, $port, $this ->time_out);
        }
        else
        {
            $conn = ftp_connect( $server, $port, $this ->time_out );
        }


        if($conn)
        {
            if(ftp_login($conn,$username,$password))
            {
                return $conn;
            }
            else
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Login failed. You have entered the incorrect credential(s). Please try again.');
            }
        }
        else{
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Login failed. The connection has timed out. Please try again later.');
        }
    }
    public function do_chdir($conn,$path)
    {
        @ftp_chdir($conn,'/');
        if(!@ftp_chdir($conn,$path))
        {
            $parts = explode('/',$path);
            foreach($parts as $part){
                if($part !== '') {
                    if (!@ftp_chdir($conn, $part)) {
                        if (!ftp_mkdir($conn, $part)) {
                            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
                        }

                        if (!@ftp_chdir($conn, $part)) {
                            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
                        }
                    }
                }
            }

            /*if ( ! ftp_mkdir( $conn, $path ) )
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
            }
            if (!@ftp_chdir($conn,$path))
            {
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
            }*/
        }

        return array('result'=>WPVIVID_PRO_SUCCESS);
    }

    public function upload($task_id,$files,$callback = '')
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $this -> callback = $callback;

        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $path = $this->options['path'];

        $port = empty($this->options['port'])?21:$this->options['port'];

        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        if(empty($upload_job))
        {
            $job_data=array();
            foreach ($files as $file)
            {
                if(!file_exists($file))
                    return array('result'=>WPVIVID_PRO_FAILED,'error'=>$file.' not found. The file might has been moved, renamed or deleted. Please back it up again.');
                $file_data['size']=filesize($file);
                $file_data['uploaded']=0;
                $job_data[basename($file)]=$file_data;
            }
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading.',$job_data);
            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        }
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Connecting to server '.$host,'notice');
        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);

        if(!isset($this->options['custom_path']))
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('chdir '.$path,'notice');
            $ret= $this->do_chdir($conn,$path);
            if($ret['result'] !== WPVIVID_PRO_SUCCESS)
                return $ret;
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }

            $root_path=untrailingslashit($root_path);

            $path=$this->options['path'].$root_path.'/';
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('chdir '.$path,'notice');
            $ret = $this -> do_chdir($conn , $path);
            if($ret['result'] !== WPVIVID_PRO_SUCCESS)
                return $ret;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('chdir '.$path.$this->options['custom_path'],'notice');
            $ret = $this -> do_sub_chdir($conn , $path,$this->options['custom_path']);
            if($ret['result'] !== WPVIVID_PRO_SUCCESS)
                return $ret;
            $path.=$this->options['custom_path'];
        }


        $flag = true;
        $error = '';
        foreach ($files as $key => $file)
        {
            if(is_array($upload_job['job_data']) && array_key_exists(basename($file),$upload_job['job_data']))
            {
                if($upload_job['job_data'][basename($file)]['uploaded']==1)
                    continue;
            }
            $this ->last_time = time();
            $this -> last_size = 0;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start uploading '.basename($file),'notice');
            $remote_file = trailingslashit($path).basename($file);
            if(!file_exists($file))
                return array('result'=>WPVIVID_PRO_FAILED,'error'=>$file.' not found. The file might has been moved, renamed or deleted. Please back it up again.');

            $wpvivid_plugin->set_time_limit($task_id);

            for($i =0;$i <WPVIVID_PRO_REMOTE_CONNECT_RETRY_TIMES;$i ++)
            {
                $this -> current_file_name = basename($file);
                $this -> current_file_size = filesize($file);
                $this -> last_time = time();
                $this -> last_size = 0;
                $local_handle = fopen($file,'rb');
                if(!$local_handle)
                {
                    return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Failed to open '.$this->current_file_name.'.');
                }
                $status = ftp_nb_fput($conn,$remote_file,$local_handle,FTP_BINARY,0);
                while ($status == FTP_MOREDATA)
                {
                    $status = ftp_nb_continue($conn);
                    if((time() - $this -> last_time) >3)
                    {
                        if(is_callable($callback)){
                            call_user_func_array($callback,array(ftell($local_handle),$this -> current_file_name,
                                $this->current_file_size,$this -> last_time,$this -> last_size));
                        }
                        $this -> last_size = ftell($local_handle);
                        $this -> last_time = time();
                    }
                }
                if ($status != FTP_FINISHED)
                {
                    return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Uploading '.$remote_file.' to FTP server failed. '.$remote_file.' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
                }

                if($status == FTP_FINISHED)
                {
                    WPvivid_Custom_Interface_addon::wpvivid_reset_backup_retry_times($task_id);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
                    $upload_job['job_data'][basename($file)]['uploaded']=1;
                    WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
                    break;
                }

                if($status != FTP_FINISHED && $i == (WPVIVID_PRO_REMOTE_CONNECT_RETRY_TIMES - 1))
                {
                    $flag = false;
                    $error = 'Uploading '.basename($file).' to FTP server failed. '.basename($file).' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.';
                    break 2;
                }
                sleep(WPVIVID_PRO_REMOTE_CONNECT_RETRY_INTERVAL);
            }
        }

        if($flag){
            return array('result'=>WPVIVID_PRO_SUCCESS);
        }else{
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$error);
        }
    }

    public function do_sub_chdir($conn,$path,$sub_path)
    {
        if(!@ftp_chdir($conn,$path.$sub_path))
        {
            $this->ftp_mksubdirs($conn,$path,$sub_path);
        }

        return array('result'=>WPVIVID_PRO_SUCCESS);
    }
    public function ftp_mksubdirs($ftpcon,$ftpbasedir,$ftpath)
    {
        @ftp_chdir($ftpcon, $ftpbasedir); // /var/www/uploads
        $parts = explode('/',$ftpath); // 2013/06/11/username
        foreach($parts as $part){
            if(!@ftp_chdir($ftpcon, $part)){
                ftp_mkdir($ftpcon, $part);
                ftp_chdir($ftpcon, $part);
                //ftp_chmod($ftpcon, 0777, $part);
            }
        }
    }
    public function download($file,$local_path,$callback = '')
    {
        try {
            global $wpvivid_plugin;
            $passive = $this->options['passive'];
            $host = $this->options['host'];
            $username = $this->options['username'];
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $password = base64_decode($this->options['password']);
            }
            else {
                $password = $this->options['password'];
            }
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'];
            }
            else
            {

                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }

                $root_path=untrailingslashit($root_path);

                if(isset($file['remote_path']))
                {
                    $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/'.$file['remote_path'];
                }
                else
                {
                    $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
                }
            }
            $port = empty($this->options['port']) ? 21 : $this->options['port'];

            $local_path = trailingslashit($local_path) . $file['file_name'];
            $remote_file = trailingslashit($path) . $file['file_name'];

            $this->current_file_name = $file['file_name'];
            $this->current_file_size = $file['size'];

            $wpvivid_plugin->wpvivid_download_log->WriteLog('Connecting FTP server.','notice');
            $conn = $this->do_connect($host, $username, $password, $port);
            if (is_array($conn) && array_key_exists('result', $conn)) {
                return $conn;
            }

            ftp_pasv($conn, $passive);
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.','notice');
            $local_handle = fopen($local_path, 'ab');
            if (!$local_handle) {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Unable to create the local file. Please make sure the folder is writable and try again.');
            }

            $stat = fstat($local_handle);
            $offset = $stat['size'];
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'] ,'notice');
            $status = ftp_nb_fget($conn, $local_handle, $remote_file, FTP_BINARY, $offset);
            while ($status == FTP_MOREDATA) {
                $status = ftp_nb_continue($conn);
                if ((time() - $this->last_time) > 3) {
                    if (is_callable($callback)) {
                        call_user_func_array($callback, array(ftell($local_handle), $this->current_file_name,
                            $this->current_file_size, $this->last_time, $this->last_size));
                    }
                    $this->last_size = ftell($local_handle);
                    $this->last_time = time();
                }
            }

            if(filesize($local_path) == $file['size']){
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

            if ($status != FTP_FINISHED || $res !== TRUE) {
                @unlink($local_path);
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $remote_file . ' failed. ' . $remote_file . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }

            ftp_close($conn);
            fclose($local_handle);
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
            global $wpvivid_plugin;
            $passive = $this->options['passive'];
            $host = $this->options['host'];
            $username = $this->options['username'];
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $password = base64_decode($this->options['password']);
            }
            else {
                $password = $this->options['password'];
            }
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'];
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
                    $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/'.$file['remote_path'];
                }
                else
                {
                    $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
                }
            }
            $port = empty($this->options['port']) ? 21 : $this->options['port'];

            $remote_file = trailingslashit($path) . $download_info['file_name'];
            $this -> current_file_name = $download_info['file_name'];
            $this -> current_file_size = $download_info['size'];
            $local_path = $download_info['local_path'];

            $conn = $this->do_connect($host, $username, $password, $port);
            if (is_array($conn) && array_key_exists('result', $conn))
            {
                return $conn;
            }

            ftp_pasv($conn, $passive);

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

            $time_limit = 30;
            $start_time = time();

            $status = ftp_nb_fget($conn, $fh, $remote_file, FTP_BINARY, $offset);
            while ($status == FTP_MOREDATA)
            {
                $status = ftp_nb_continue($conn);

                clearstatcache();
                $start_offset = ftell($fh);

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
                    ftp_close($conn);
                    fclose($fh);

                    $result['result']='success';
                    $result['finished']=0;
                    $result['offset']=$start_offset;
                    return $result;
                }
            }

            if ($status != FTP_FINISHED )
            {
                @unlink($local_path);
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $remote_file . ' failed. ' . $remote_file . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }

            ftp_close($conn);
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
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1)
        {
            $password = base64_decode($this->options['password']);
        }
        else
        {
            $password = $this->options['password'];
        }

        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $root_path=untrailingslashit($root_path);
        if(!isset($this->options['custom_path']))
        {
            $path=$this->options['path'].$root_path.'/rollback_ex/'.$folder.'/'.$slug.'/'.$version.'/';
        }
        else
        {
            $path=$this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/'.$folder.'/'.$slug.'/'.$version.'/';
        }

        $ret = $this -> do_chdir($conn , $path);

        if($ret['result'] !== WPVIVID_PRO_SUCCESS)
            return $ret;

        $this -> current_file_name = basename($file);
        $this -> current_file_size = filesize($file);
        $this -> last_time = time();
        $this -> last_size = 0;
        $local_handle = fopen($file,'rb');
        if(!$local_handle)
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Failed to open '.$this->current_file_name.'.');
        }

        $remote_file = trailingslashit($path).basename($file);

        $status = ftp_nb_fput($conn,$remote_file,$local_handle,FTP_BINARY,0);
        while ($status == FTP_MOREDATA)
        {
            $status = ftp_nb_continue($conn);
        }

        if ($status != FTP_FINISHED)
        {
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'Uploading '.$remote_file.' to FTP server failed. '.$remote_file.' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }

        if($status == FTP_FINISHED)
        {
            return array('result'=>WPVIVID_PRO_SUCCESS);
        }

        if($status != FTP_FINISHED)
        {
            $error = 'Uploading '.basename($file).' to FTP server failed. '.basename($file).' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.';
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$error);
        }

        return array('result'=>WPVIVID_PRO_SUCCESS);
    }

    public function download_rollback($download_info)
    {
        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];

        $type=$download_info['type'];
        $slug=$download_info['slug'];
        $version=$download_info['version'];

        $local_path = $download_info['local_path'];

        $offset = file_exists($local_path) ? filesize($local_path) : 0;
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
        $root_path=untrailingslashit($root_path);

        if(!isset($this->options['custom_path']))
        {
            $path=$this->options['path'].$root_path.'/rollback_ex/'.$type.'/'.$slug.'/'.$version;
        }
        else
        {
            $path=$this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version;
        }

        $passive = $this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }

        $port = empty($this->options['port']) ? 21 : $this->options['port'];
        $remote_file = trailingslashit($path) . $download_info['file_name'];
        $conn = $this->do_connect($host, $username, $password, $port);
        if (is_array($conn) && array_key_exists('result', $conn))
        {
            return $conn;
        }

        ftp_pasv($conn, $passive);

        $status = ftp_nb_fget($conn, $fh, $remote_file, FTP_BINARY, $offset);
        while ($status == FTP_MOREDATA)
        {
            $status = ftp_nb_continue($conn);

            clearstatcache();
            $start_offset = ftell($fh);

            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                ftp_close($conn);
                fclose($fh);

                $result['result']='success';
                $result['finished']=0;
                $result['offset']=$start_offset;
                return $result;
            }
        }

        if ($status != FTP_FINISHED )
        {
            @unlink($local_path);
            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $remote_file . ' failed. ' . $remote_file . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }

        ftp_close($conn);
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
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }

        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;

        foreach ($files as $file)
        {
            $file_path=$path;
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $file_path=$path.'/'.$file['remote_path'];
                }
                $file_name=$file['file_name'];
            }
            else
            {
                $file_name=$file;
            }

            @ftp_delete($conn,trailingslashit($file_path).$file_name);
        }
        return array('result'=>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1)
        {
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'].$root_path.'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$slug.'.zip';
        }
        else
        {

            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$slug.'.zip';
        }

        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;

        @ftp_delete($conn,$path);

        return array('result'=>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_folder($folders)
    {
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }

        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;

        foreach ($folders as $file)
        {
            $file_path=$path;

            @ftp_rmdir($conn,trailingslashit($file_path).$file);
        }
        return array('result'=>WPVIVID_PRO_SUCCESS);
    }

    public function wpvivid_get_out_of_date_ftp($out_of_date_remote, $remote)
    {
        if($remote['type'] == WPVIVID_REMOTE_FTP){
            $out_of_date_remote = $remote['path'];
        }
        return $out_of_date_remote;
    }

    public function wpvivid_storage_provider_ftp($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_FTP){
            $storage_type = 'FTP';
        }
        return $storage_type;
    }

    public function scan_folder_backup($folder_type)
    {
        try
        {
            $passive =$this->options['passive'];
            $host = $this->options['host'];
            $username = $this->options['username'];
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $password = base64_decode($this->options['password']);
            }
            else {
                $password = $this->options['password'];
            }
            $port = empty($this->options['port'])?21:$this->options['port'];

            $conn = $this -> do_connect($host,$username,$password,$port);
            if(is_array($conn) && array_key_exists('result',$conn))
                return $conn;
            ftp_pasv($conn,$passive);

            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }

            if($folder_type === 'Common')
            {
                if(!isset($this->options['custom_path']))
                {
                    $path = $this->options['path'];
                }
                else
                {
                    $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
                }
                $response=$this->_scan_folder_backup($path,$conn);
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
                $path = $this->options['path'].$root_path.'/migrate';
                $response=$this->_scan_folder_backup($path,$conn);

                if($response['result']==WPVIVID_PRO_SUCCESS)
                {
                    $ret['migrate']= $response['backup'];
                }
                else
                {
                    return $response;
                }
            }
            else if($folder_type === 'Rollback')
            {
                $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';
                $response=$this->_scan_folder_backup($path,$conn);

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
        catch (Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $e->getMessage());
        }
    }

    public function scan_child_folder_backup($sub_path)
    {
        try
        {
            $passive =$this->options['passive'];
            $host = $this->options['host'];
            $username = $this->options['username'];
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $password = base64_decode($this->options['password']);
            }
            else {
                $password = $this->options['password'];
            }
            $port = empty($this->options['port'])?21:$this->options['port'];

            $conn = $this -> do_connect($host,$username,$password,$port);
            if(is_array($conn) && array_key_exists('result',$conn))
                return $conn;
            ftp_pasv($conn,$passive);

            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }

            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'];
            }
            else
            {
                $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
            }

            $response=$this->_scan_child_folder_backup($path,$sub_path,$conn);
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
        catch (Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $e->getMessage());
        }
    }

    public function _scan_folder_backup($path,$conn)
    {
        try
        {
            $ret['result'] = WPVIVID_PRO_SUCCESS;
            $ret['backup'] = array();
            $ret['path']=array();
            $files=array();
            @ftp_chdir($conn,$path);
            $path = '.';
            $list =ftp_rawlist($conn,$path);
            if ($list == false)
            {
                return $ret;
            }
            else
            {
                foreach ($list as $file)
                {
                    $chunks = preg_split("/\s+/", $file);
                    if(!empty($chunks[0]))
                    {
                        list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time'],$item['filename']) = $chunks;
                        $item['type'] = $chunks[0][0] === 'd' ? 'directory' : 'file';

                        if ($item['type'] == 'file')
                        {
                            $file_data['file_name'] = $item['filename'];
                            $file_data['size'] = $item['size'];
                            $files[] = $file_data;
                        }
                        else if($item['type']=='directory')
                        {
                            if($item['filename']=='rollback')
                                continue;
                            $ret['path'][]=$item['filename'];
                            //$ret_child=$this->_scan_child_folder_backup($path,$item['filename'],$conn);
                            //$ret['test'] = $ret_child;
                            //if($ret_child['result']==WPVIVID_PRO_SUCCESS)
                            //{
                            //    $files= array_merge($files,$ret_child['files']);
                            //}
                        }
                    }
                }
            }

            if (!empty($files))
            {
                global $wpvivid_backup_pro;
                $ret['backup'] = $wpvivid_backup_pro->func->get_backup($files);
            }

            return $ret;
        }
        catch (Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $e->getMessage());
        }
    }

    public function _scan_child_folder_backup($path,$sub_path,$conn)
    {
        try
        {
            $ret['result'] = WPVIVID_PRO_SUCCESS;
            $ret['backup'] = array();
            $ret['files']=array();
            $list =ftp_rawlist($conn,$path.'/'.$sub_path);

            $ret['url'] = $list;
            if ($list == false)
            {
                return $ret;
            }
            else
            {
                foreach ($list as $file)
                {
                    $chunks = preg_split("/\s+/", $file);
                    if(!empty($chunks[0]))
                    {
                        list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time'],$item['filename']) = $chunks;
                        $item['type'] = $chunks[0][0] === 'd' ? 'directory' : 'file';

                        if ($item['type'] == 'file')
                        {
                            $file_data['file_name'] = $item['filename'];
                            $file_data['size'] = $item['size'];
                            $file_data['remote_path']=$sub_path;
                            $ret['files'][] = $file_data;
                        }
                    }
                }
            }

            if (!empty($ret['files']))
            {
                global $wpvivid_backup_pro;
                $ret['backup'] = $wpvivid_backup_pro->func->get_backup($ret['files']);
            }

            return $ret;
        }
        catch (Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $e->getMessage());
        }
    }

    public function scan_folder_backup_ex($folder_type)
    {
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1)
        {
            $password = base64_decode($this->options['password']);
        }
        else
        {
            $password = $this->options['password'];
        }
        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);

        if($folder_type=='all_backup')
        {
            $ret['result']='success';
            $ret['remote']=array();

            $response=$this->_get_common_backups($conn);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                $path=$response['path'];
            }

            $ret['migrate']=array();

            $response=$this->_get_migrate_backups($conn);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }

            $ret['rollback']=array();

            $response=$this->_get_rollback_backups($conn);
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
                        $response=$this->_get_incremental_backups($incremental_path,$conn);
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

            $response=$this->_get_common_backups($conn);
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

            $response=$this->_get_migrate_backups($conn);
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

            $response=$this->_get_rollback_backups($conn);
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

            $response=$this->_get_common_backups($conn);
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
                        $response=$this->_get_incremental_backups($incremental_path,$conn);
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

    public function _get_common_backups($conn)
    {
        $root_path='wpvividbackuppro';

        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }

        return $this->_scan_folder_backup($path,$conn);
    }

    public function _get_migrate_backups($conn)
    {
        $root_path='wpvividbackuppro';

        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path = $this->options['path'].$root_path.'/migrate';

        return $this->_scan_folder_backup($path,$conn);
    }

    public function _get_rollback_backups($conn)
    {
        $root_path='wpvividbackuppro';

        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }

        $path = $path.'/rollback';

        return $this->_scan_folder_backup($path,$conn);
    }

    public function _get_incremental_backups($incremental_path,$conn)
    {
        $root_path='wpvividbackuppro';

        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }

        $path = $path.'/'.$incremental_path;

        $ret=$this->_scan_folder_backup($path,$conn);
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
        global $wpvivid_plugin;
        $passive = $this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }

        if($folder_type=='Manual')
        {
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'];
            }
            else
            {
                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }
                $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
            }
        }
        else if($folder_type=='Migrate')
        {
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'].'/migrate';
            }
            else
            {
                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }
                $path = $this->options['path'].$root_path.'/migrate';
            }
        }
        else if($folder_type=='Rollback')
        {
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'].'/rollback';
            }
            else
            {
                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }
                $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';
            }
        }
        else if($folder_type=='Incremental')
        {
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'].'/'.$incremental_path;
            }
            else
            {
                $root_path='wpvividbackuppro';
                if(isset($this->options['root_path']))
                {
                    $root_path=$this->options['root_path'];
                }
                $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/'.$incremental_path;
            }
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        $port = empty($this->options['port']) ? 21 : $this->options['port'];

        $remote_file = trailingslashit($path) . $backup_info_file;

        $conn = $this->do_connect($host, $username, $password, $port);
        if (is_array($conn) && array_key_exists('result', $conn))
        {
            return $conn;
        }

        ftp_pasv($conn, $passive);

        $local_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$backup_info_file;
        @unlink($local_path);
        $local_handle = fopen($local_path, 'a');
        if (!$local_handle)
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Unable to create the local file. Please make sure the folder is writable and try again.');
        }

        $status = ftp_fget($conn, $local_handle, $remote_file, FTP_BINARY);

        fclose($local_handle);
        ftp_close($conn);

        if($status===false)
        {
            @unlink($local_path);
            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $remote_file . ' failed. ' . $remote_file . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
        }
        else
        {
            $ret['result']='success';
            $ret['backup_info']=json_decode(file_get_contents($local_path),1);
            @unlink($local_path);
            return $ret;
        }
    }

    public function scan_rollback($type)
    {
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $root_path=untrailingslashit($root_path);

        if($type === 'plugins')
        {
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'].$root_path.'/rollback_ex/plugins';
            }
            else
            {
                $path=$this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/plugins';
            }
            $response=$this->_scan_folder($path,$conn);
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
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'].$root_path.'/rollback_ex/themes';
            }
            else
            {
                $path=$this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/themes';
            }

            $response=$this->_scan_folder($path,$conn);

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

    public function _scan_folder($path,$conn)
    {
        try
        {
            $ret['result'] = WPVIVID_PRO_SUCCESS;
            $ret['path']=array();
            //@ftp_chdir($conn,$path);
            //$path = '.';
            $list =ftp_rawlist($conn,$path);
            if ($list == false)
            {
                return $ret;
            }
            else
            {
                foreach ($list as $file)
                {
                    $chunks = preg_split("/\s+/", $file);
                    if(!empty($chunks[0]))
                    {
                        list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time'],$item['filename']) = $chunks;
                        $item['type'] = $chunks[0][0] === 'd' ? 'directory' : 'file';

                        if($item['type']=='directory')
                        {
                            $ret['path'][]=$item['filename'];
                        }
                    }
                }
            }

            return $ret;
        }
        catch (Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $e->getMessage());
        }
    }

    public function get_rollback_data($type,$slug)
    {
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $root_path=untrailingslashit($root_path);

        if($type === 'plugins')
        {
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'].$root_path.'/rollback_ex/plugins/'.$slug;
            }
            else
            {
                $path=$this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/plugins/'.$slug;
            }

            $response=$this->_scan_folder($path,$conn);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $response_path= $response['path'];
                if(!empty($response_path))
                {
                    foreach ($response_path as $version)
                    {
                        $version_path=$path.'/'.$version;
                        $response=$this->_scan_file($version_path,$slug.'.zip',$conn);
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
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'].$root_path.'/rollback_ex/themes/'.$slug;
            }
            else
            {
                $path=$this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/themes/'.$slug;
            }

            $response=$this->_scan_folder($path,$conn);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $response_path= $response['path'];
                if(!empty($response_path))
                {
                    foreach ($response_path as $version)
                    {
                        $version_path=$path.'/'.$version;
                        $response=$this->_scan_file($version_path,$slug.'.zip',$conn);
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

    public function _scan_file($path,$file_name,$conn)
    {
        try
        {
            $ret['result'] = WPVIVID_PRO_SUCCESS;
            //@ftp_chdir($conn,$path);
            //$path = '.';
            $list =ftp_rawlist($conn,$path);
            if ($list == false)
            {
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
                foreach ($list as $file)
                {
                    $chunks = preg_split("/\s+/", $file);
                    if(!empty($chunks[0]))
                    {
                        list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time'],$item['filename']) = $chunks;
                        $item['type'] = $chunks[0][0] === 'd' ? 'directory' : 'file';
                        if ($item['type'] == 'file')
                        {
                            if($item['filename']==$file_name)
                            {
                                $file_data['file_name']=$item['filename'];
                                $file_data['size']=$item['size'];
                                if(gettype($item['time']) === 'string')
                                {
                                    $file_data['mtime']=strtotime($item['time']);
                                }
                                else
                                {
                                    $file_data['mtime']=$item['time'];
                                }
                                $ret['file']=$file_data;
                                break;
                            }
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
        catch (Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $e->getMessage());
        }
    }

    public function delete_old_backup($backup_count,$db_count)
    {
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }
        $response=$this->_scan_folder_backup($path,$conn);

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
                $child_response=$this->_scan_child_folder_backup($path,$folder,$conn);
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

        $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';

        $response=$this->_scan_folder_backup($path,$conn);

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
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return false;
        ftp_pasv($conn,$passive);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($folder_type === 'Common')
        {
            if(!isset($this->options['custom_path']))
            {
                $path = $this->options['path'];
            }
            else
            {
                $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
            }
        }
        else if($folder_type === 'Rollback')
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';
        }
        else
        {
            return false;
        }

        $response=$this->_scan_folder_backup($path,$conn);

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
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }

        if($type=='Rollback')
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';

            $response=$this->_scan_folder_backup($path,$conn);

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
            $response=$this->_scan_folder_backup($path,$conn);

            if(isset($response['path']))
            {
                $folders=$response['path'];

                global $wpvivid_backup_pro;
                $files = array();
                $folders_count=$backup_count;
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);
                foreach ($folders as $folder)
                {
                    $child_response=$this->_scan_child_folder_backup($path,$folder,$conn);
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
            $response=$this->_scan_folder_backup($path,$conn);

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
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = empty($this->options['port'])?21:$this->options['port'];

        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return false;
        ftp_pasv($conn,$passive);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        if(!isset($this->options['custom_path']))
        {
            $path = $this->options['path'];
        }
        else
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'];
        }

        if($type=='Rollback')
        {
            $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';

            $response=$this->_scan_folder_backup($path,$conn);

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
            $response=$this->_scan_folder_backup($path,$conn);

            if(isset($response['path']))
            {
                $folders=$response['path'];

                global $wpvivid_backup_pro;
                $files = array();
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
            $response=$this->_scan_folder_backup($path,$conn);

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