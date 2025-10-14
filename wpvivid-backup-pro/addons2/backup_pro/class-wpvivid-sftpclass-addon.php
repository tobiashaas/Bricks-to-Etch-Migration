<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_SFTPClass_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
if(!defined('WPVIVID_REMOTE_SFTP'))
    define('WPVIVID_REMOTE_SFTP','sftp');

class WPvivid_SFTPClass_addon extends WPvivid_Remote_addon
{
    private $package_size = 10;
    private $timeout = 20;
    private $error_str = false;
    private $callback;
    private $options = array();

    public function __construct($options = array())
    {
        if (empty($options)) {
            if (!defined('WPVIVID_INIT_STORAGE_TAB_SFTP')) {
                add_action('wpvivid_add_storage_page', array($this, 'wpvivid_add_storage_page_sftp'), 15);
                add_action('wpvivid_edit_remote_page', array($this, 'wpvivid_edit_storage_page_sftp'), 15);
                add_filter('wpvivid_get_out_of_date_remote', array($this, 'wpvivid_get_out_of_date_sftp'), 10, 2);
                add_filter('wpvivid_storage_provider_tran', array($this, 'wpvivid_storage_provider_sftp'), 10);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'), 11);
                define('WPVIVID_INIT_STORAGE_TAB_SFTP', 1);
            }
        } else {
            $this->options = $options;
        }
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_SFTP] = 'WPvivid_SFTPClass_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_SFTP)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function wpvivid_add_storage_page_sftp()
    {
        global $wpvivid_backup_pro;
        ?>
        <div id="storage_account_sftp" class="storage-account-page">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your SFTP Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="sftp" name="name" placeholder="Enter a unique alias: e.g. SFTP-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="sftp" name="host" placeholder="Server Address" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="sftp" name="username" placeholder="User Name" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the user name.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="sftp" name="password" placeholder="User Password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the user password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="sftp" name="port" placeholder="Port" onkeyup="value=value.replace(/\D/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the server port.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="sftp" name="path" placeholder="Absolute path must exist(e.g. /var)" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="sftp" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="sftp" name="custom_path" placeholder="Custom Path(e.g. myfolder)" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="sftp" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="sftp" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'sftp', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="sftp" name="default" checked />Set as the default remote storage.
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
                            <i>Click the button to connect to SFTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function wpvivid_edit_storage_page_sftp()
    {
        ?>
        <div id="remote_storage_edit_sftp">
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your SFTP Account</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-sftp" name="name" placeholder="Enter a unique alias: e.g. SFTP-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-sftp" name="host" placeholder="Server Address" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-sftp" name="username" placeholder="User Name" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the user name.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="edit-sftp" name="password" placeholder="User Password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the user password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-sftp" name="port" placeholder="Port" onkeyup="value=value.replace(/\D/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Enter the server port.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-sftp" name="path" placeholder="Absolute path must exist(e.g. /var)" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-sftp" name="root_path" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-sftp" name="custom_path" placeholder="Custom Path(e.g. myfolder)" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="edit-sftp" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="edit-sftp" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'sftp', 'edit'); ?>

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
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $path = $this->options['path'];

        $port = empty($this->options['port']) ? 22 : $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);
        if (!is_subclass_of($conn, 'Net_SSH2')) {
            return $conn;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $ret= $this->do_chdir($conn,$path);
        if($ret['result']=='success')
        {
            $path= $this->options['path'].$root_path.'/';
            $str = $this->do_chdir($conn,$path);
            if ($str['result'] == WPVIVID_PRO_SUCCESS)
            {
                $path=$path.$this->options['custom_path'];
                $str = $this->do_chdir($conn,$path);
                if ($str['result'] == WPVIVID_PRO_SUCCESS)
                {
                    if ($conn->put(trailingslashit($path) . 'testfile', 'test data', NET_SFTP_STRING))
                    {
                        $this->_delete($conn, trailingslashit($path) . 'testfile');

                        $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';
                        $this->do_chdir($conn,$path);
                        return array('result' => WPVIVID_PRO_SUCCESS);
                    }
                    return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Failed to create a test file. Please try again later.');
                } else {
                    return $str;
                }
            }
            else {
                return $str;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function sanitize_options($skip_name = '')
    {
        $ret['result'] = WPVIVID_PRO_FAILED;
        if (!isset($this->options['name'])) {
            $ret['error'] = "Warning: An alias for remote storage is required.";
            return $ret;
        }

        $this->options['name'] = sanitize_text_field($this->options['name']);

        if (empty($this->options['name'])) {
            $ret['error'] = "Warning: An alias for remote storage is required.";
            return $ret;
        }

        $remoteslist = WPvivid_Setting::get_all_remote_options();
        foreach ($remoteslist as $key => $value) {
            if (isset($value['name']) && $value['name'] == $this->options['name'] && $skip_name != $value['name']) {
                $ret['error'] = "Warning: The alias already exists in storage list.";
                return $ret;
            }
        }

        if (!isset($this->options['host'])) {
            $ret['error'] = "Warning: The IP Address is required.";
            return $ret;
        }

        $this->options['host'] = sanitize_text_field($this->options['host']);

        if (empty($this->options['host'])) {
            $ret['error'] = "Warning: The IP Address is required.";
            return $ret;
        }

        if (!isset($this->options['username'])) {
            $ret['error'] = "Warning: The username is required.";
            return $ret;
        }

        $this->options['username'] = sanitize_text_field($this->options['username']);

        if (empty($this->options['username'])) {
            $ret['error'] = "Warning: The username is required.";
            return $ret;
        }

        if (!isset($this->options['password']) || empty($this->options['password'])) {
            $ret['error'] = "Warning: The password is required.";
            return $ret;
        }

        //$this->options['password'] = sanitize_text_field($this->options['password']);

        if (empty($this->options['password'])) {
            $ret['error'] = "Warning: The password is required.";
            return $ret;
        }
        $this->options['password'] = base64_encode($this->options['password']);
        $this->options['is_encrypt'] = 1;

        if (!isset($this->options['port'])) {
            $ret['error'] = "Warning: The port number is required.";
            return $ret;
        }

        $this->options['port'] = sanitize_text_field($this->options['port']);

        if (empty($this->options['port'])) {
            $ret['error'] = "Warning: The port number is required.";
            return $ret;
        }

        if (!isset($this->options['path']) || empty($this->options['path'])) {
            $ret['error'] = "Warning: The storage path is required.";
            return $ret;
        }

        $this->options['path'] = sanitize_text_field($this->options['path']);
        $this->options['path']=trailingslashit($this->options['path']);

        if (empty($this->options['path'])) {
            $ret['error'] = "Warning: The storage path is required.";
            return $ret;
        }

        if($this->options['path']=='/')
        {
            $ret['error']="Warning: Root directory is forbidden to set to '/'.";
            return $ret;
        }

        if (!isset($this->options['root_path']) || empty($this->options['root_path'])) {
            $ret['error'] = "Warning: The root path is required.";
            return $ret;
        }

        $this->options['root_path'] = sanitize_text_field($this->options['root_path']);
        $this->options['root_path']=trailingslashit($this->options['root_path']);

        if (empty($this->options['root_path'])) {
            $ret['error'] = "Warning: The root path is required.";
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

        $ret['result'] = WPVIVID_PRO_SUCCESS;
        $ret['options'] = $this->options;
        return $ret;
    }

    function do_connect($host, $username, $password, $port)
    {
        include_once WPVIVID_PLUGIN_DIR . '/includes/customclass/class-wpvivid-extend-sftp.php';
        $conn = new WPvivid_Net_SFTP($host, $port, $this->timeout);
        $conn->setTimeout($this->timeout);
        $ret = $conn->login($username, $password);
        if (!$ret) {
            return array('result' => WPVIVID_PRO_FAILED, 'error' => 'The connection failed because of incorrect credentials or server connection timeout. Please try again.');
        }

        return $conn;
    }

    function do_chdir($conn, $path)
    {
        // See if the directory now exists
        if (!$conn->chdir($path))
        {
            @$conn->mkdir($path,-1,true);
            if(!$conn->chdir($path))
            {
                @$conn->disconnect();
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
            }
        }

        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    function _delete($conn, $file)
    {
        $result = $conn->delete($file, true);
        return $result;
    }

    public function upload($task_id, $files, $callback = '')
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $this->callback = $callback;
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $path = $this->options['path'];
        $port = $this->options['port'];

        $upload_job = WPvivid_taskmanager::get_backup_sub_task_progress($task_id, 'upload', $this->options['id']);

        if (empty($upload_job)) {
            $job_data = array();
            foreach ($files as $file) {
                $file_data['size'] = filesize($file);
                $file_data['uploaded'] = 0;
                $job_data[basename($file)] = $file_data;
            }
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id, 'upload', $this->options['id'], WPVIVID_UPLOAD_UNDO, 'Start uploading', $job_data);
            $upload_job = WPvivid_taskmanager::get_backup_sub_task_progress($task_id, 'upload', $this->options['id']);
        }

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Connecting to server ' . $host, 'notice');
        $conn = $this->do_connect($host, $username, $password, $port);

        if (is_array($conn) && $conn['result'] == WPVIVID_PRO_FAILED) {
            return $conn;
        }
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

            $path= $this->options['path'].untrailingslashit($root_path).'/'.$this->options['custom_path'];
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('chdir '.$path,'notice');
            $ret = $this->do_chdir($conn,$path);
            if ($ret['result'] !== WPVIVID_PRO_SUCCESS)
            {
                return $ret;
            }
        }

        foreach ($files as $key => $file)
        {
            if (is_array($upload_job['job_data']) && array_key_exists(basename($file), $upload_job['job_data']))
            {
                if ($upload_job['job_data'][basename($file)]['uploaded'] == 1)
                    continue;
            }
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start uploading ' . basename($file), 'notice');
            $this->last_time = time();
            $this->last_size = 0;

            if (!file_exists($file))
                return array('result' => WPVIVID_PRO_FAILED, 'error' => $file . ' not found. The file might has been moved, renamed or deleted. Please back it up again.');

            $wpvivid_plugin->set_time_limit($task_id);

            for ($i = 0; $i < WPVIVID_PRO_REMOTE_CONNECT_RETRY_TIMES; $i++) {
                $this->last_time = time();
                $this->current_file_name = basename($file);
                $this->current_file_size = filesize($file);

                WPvivid_taskmanager::update_backup_sub_task_progress($task_id, 'upload', $this->options['id'], WPVIVID_UPLOAD_UNDO, 'Start uploading ' . basename($file) . '.', $upload_job['job_data']);

                $result = $conn->put(trailingslashit($path) . basename($file), $file, NET_SFTP_LOCAL_FILE | NET_SFTP_RESUME_START, -1, -1, array($this, 'upload_callback'));

                if ($result) {
                    WPvivid_Custom_Interface_addon::wpvivid_reset_backup_retry_times($task_id);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading ' . basename($file), 'notice');
                    $upload_job['job_data'][basename($file)]['uploaded'] = 1;
                    WPvivid_taskmanager::update_backup_sub_task_progress($task_id, 'upload', $this->options['id'], WPVIVID_UPLOAD_SUCCESS, 'Uploading ' . basename($file) . ' completed.', $upload_job['job_data']);
                    break;
                }
                else
                {
                    $last_error = error_get_last();
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload failed, get last error: '.json_encode($last_error).', retry times: '.$i, 'notice');
                    $sftperrors=$conn->getSFTPErrors();
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('getSFTPErrors: '.json_encode($sftperrors), 'notice');
                    $lastsftperror=$conn->getLastSFTPError();
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('getLastSFTPError: '.json_encode($lastsftperror), 'notice');
                }

                if (!$result && $i == (WPVIVID_PRO_REMOTE_CONNECT_RETRY_TIMES - 1)) {
                    $conn->disconnect();
                    return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Uploading ' . $file . ' to SFTP server failed. ' . $file . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
                }
                sleep(WPVIVID_PRO_REMOTE_CONNECT_RETRY_INTERVAL);
            }
        }
        $conn->disconnect();
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function download($file, $local_path, $callback = '')
    {
        try {
            global $wpvivid_plugin;
            $this->callback = $callback;
            $this->current_file_name = $file['file_name'];
            $this->current_file_size = $file['size'];

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
            $port = empty($this->options['port']) ? 22 : $this->options['port'];
            $local_path = trailingslashit($local_path) . $file['file_name'];
            $remote_file_name = trailingslashit($path) . $file['file_name'];
            $wpvivid_plugin->wpvivid_download_log->WriteLog('from path:'.$path, 'notice');
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Connecting SFTP server.', 'notice');
            $conn = $this->do_connect($host, $username, $password, $port);

            if (!is_subclass_of($conn, 'Net_SSH2')) {
                return $conn;
            }
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.', 'notice');
            $local_file = fopen($local_path, 'ab');
            if (!$local_file) {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Unable to create the local file. Please make sure the folder is writable and try again.');
            }
            $stat = fstat($local_file);
            $offset = $stat['size'];

            $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'], 'notice');
            $result = $conn->get($remote_file_name, $local_file, $offset, -1, array($this, 'download_callback'));
            @fclose($local_file);

            if (filesize($local_path) == $file['size']) {
                if ($wpvivid_plugin->wpvivid_check_zip_valid()) {
                    $res = TRUE;
                } else {
                    $res = FALSE;
                }
            } else {
                $res = FALSE;
            }

            if ($result && $res) {
                return array('result' => WPVIVID_PRO_SUCCESS);
            } else {
                return array('result' => WPVIVID_PRO_FAILED, 'error' => 'Downloading ' . $remote_file_name . ' failed. ' . $remote_file_name . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
        } catch (Exception $error) {
            $message = 'An exception has occurred. class: ' . get_class($error) . ';msg: ' . $error->getMessage() . ';code: ' . $error->getCode() . ';line: ' . $error->getLine() . ';in_file: ' . $error->getFile() . ';';
            error_log($message);
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $message);
        }
    }

    public function chunk_download($download_info,$callback)
    {
        try
        {
            set_time_limit(120);

            global $wpvivid_plugin;
            $this->callback = $callback;
            $this -> current_file_name = $download_info['file_name'];
            $this -> current_file_size = $download_info['size'];

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

            $port = empty($this->options['port']) ? 22 : $this->options['port'];

            $local_path = $download_info['local_path'];
            $remote_file = trailingslashit($path) . $download_info['file_name'];

            $conn = $this->do_connect($host, $username, $password, $port);

            if (!is_subclass_of($conn, 'Net_SSH2')) {
                return $conn;
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
                $conn->get($remote_file, $fh, $start_offset, $download_chunk_size, array($this, 'download_callback'));

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
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: ' . get_class($error) . ';msg: ' . $error->getMessage() . ';code: ' . $error->getCode() . ';line: ' . $error->getLine() . ';in_file: ' . $error->getFile() . ';';
            error_log($message);
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $message);
        }
    }

    public function delete($remote, $files)
    {
        $host = $remote['options']['host'];
        $username = $remote['options']['username'];
        if(isset($remote['options']['is_encrypt']) && $remote['options']['is_encrypt'] == 1){
            $password = base64_decode($remote['options']['password']);
        }
        else {
            $password = $remote['options']['password'];
        }

        if(!isset($remote['options']['custom_path']))
        {
            $path = $remote['options']['path'];
        }
        else
        {
            $root_path='wpvividbackuppro';
            if(isset($this->options['root_path']))
            {
                $root_path=$this->options['root_path'];
            }
            $path = $remote['options']['path'].$root_path.'/'.$remote['options']['custom_path'];
        }
        $port = empty($remote['options']['port']) ? 22 : $remote['options']['port'];

        $conn = $this->do_connect($host, $username, $password, $port);
        if (!is_subclass_of($conn, 'Net_SSH2')) {
            return $conn;
        }
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
            $file = trailingslashit($file_path) . $file_name;
            $this->_delete($conn, $file);
        }
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function get_last_error()
    {
        if ($this->error_str === false) {
            $this->error_str = 'connection time out.';
        }
        return $this->error_str;
    }

    public function upload_callback($offset)
    {
        if ((time() - $this->last_time) > 3) {
            if (is_callable($this->callback)) {
                call_user_func_array($this->callback, array($offset, $this->current_file_name,
                    $this->current_file_size, $this->last_time, $this->last_size));
            }
            $this->last_size = $offset;
            $this->last_time = time();
        }
    }

    public function download_callback($offset)
    {
        if ((time() - $this->last_time) > 3) {
            if (is_callable($this->callback)) {
                call_user_func_array($this->callback, array($offset, $this->current_file_name,
                    $this->current_file_size, $this->last_time, $this->last_size));
            }
            $this->last_size = $offset;
            $this->last_time = time();
        }
    }

    public function upload_rollback($file,$folder,$slug,$version)
    {
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1)
        {
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }

        $port = $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);

        if (is_array($conn) && $conn['result'] == WPVIVID_PRO_FAILED) {
            return $conn;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $root_path=untrailingslashit($root_path);

        if(isset($this->options['custom_path']))
        {
            $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/'.$folder.'/'.$slug.'/'.$version.'/';
        }
        else
        {
            $path=$this->options['path'].$root_path.'/rollback_ex/'.$folder.'/'.$slug.'/'.$version.'/';
        }

        $ret = $this -> do_chdir($conn , $path);

        if($ret['result'] !== "success")
            return $ret;

        $this -> current_file_name = basename($file);
        $this -> current_file_size = filesize($file);
        $result = $conn->put(trailingslashit($path) . basename($file), $file, NET_SFTP_LOCAL_FILE | NET_SFTP_RESUME_START, -1, -1);

        if ($result)
        {
            $conn->disconnect();
            return array('result'=>WPVIVID_PRO_SUCCESS);
        }
        else
        {
            $last_error = error_get_last();
            $conn->disconnect();
            return array('result' => "failed", 'error' => 'Uploading ' . $file . ' to SFTP server failed. ' . $file . ' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
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

        $start_offset = file_exists($local_path) ? filesize($local_path) : 0;
        $fh = fopen($local_path, 'a');
        $download_chunk_size = 1*1024*1024;

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

        if(isset($this->options['custom_path']))
        {
            $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$this -> current_file_name;
        }
        else
        {
            $path=$this->options['path'].$root_path.'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$this -> current_file_name;
        }

        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }

        $port = empty($this->options['port']) ? 22 : $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);

        if (!is_subclass_of($conn, 'Net_SSH2')) {
            return $conn;
        }

        while ($start_offset < $this->current_file_size)
        {
            $conn->get($path, $fh, $start_offset, $download_chunk_size);

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
        $remote['options'] = $this->options;
        return $this->delete($remote, $files);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        $remote['options'] = $this->options;
        $host = $remote['options']['host'];
        $username = $remote['options']['username'];
        if(isset($remote['options']['is_encrypt']) && $remote['options']['is_encrypt'] == 1){
            $password = base64_decode($remote['options']['password']);
        }
        else {
            $password = $remote['options']['password'];
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $root_path=untrailingslashit($root_path);

        if(isset($this->options['custom_path']))
        {
            $path= $remote['options']['path'].$root_path.'/'.$remote['options']['custom_path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$slug.'.zip';
        }
        else
        {
            $path=$remote['options']['path'].$root_path.'/rollback_ex/'.$type.'/'.$slug.'/'.$version.'/'.$slug.'.zip';
        }

        $port = empty($remote['options']['port']) ? 22 : $remote['options']['port'];

        $conn = $this->do_connect($host, $username, $password, $port);
        if (!is_subclass_of($conn, 'Net_SSH2'))
        {
            return $conn;
        }

        $this->_delete($conn, $path);

        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function wpvivid_get_out_of_date_sftp($out_of_date_remote, $remote)
    {
        if ($remote['type'] == WPVIVID_REMOTE_SFTP) {
            $out_of_date_remote = $remote['path'];
        }
        return $out_of_date_remote;
    }

    public function wpvivid_storage_provider_sftp($storage_type)
    {
        if ($storage_type == WPVIVID_REMOTE_SFTP) {
            $storage_type = 'SFTP';
        }
        return $storage_type;
    }

    public function scan_folder_backup($folder_type)
    {
        try
        {
            if (empty($this->options['port']))
                $this->options['port'] = 22;
            $host = $this->options['host'];
            $username = $this->options['username'];
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $password = base64_decode($this->options['password']);
            }
            else {
                $password = $this->options['password'];
            }
            $port = $this->options['port'];

            $conn = $this->do_connect($host, $username, $password, $port);
            if (!is_subclass_of($conn, 'Net_SSH2'))
            {
                return $conn;
            }

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
            else if($folder_type === 'Rollback'){

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
            if (empty($this->options['port']))
                $this->options['port'] = 22;
            $host = $this->options['host'];
            $username = $this->options['username'];
            if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
                $password = base64_decode($this->options['password']);
            }
            else {
                $password = $this->options['password'];
            }
            $port = $this->options['port'];

            $conn = $this->do_connect($host, $username, $password, $port);
            if (!is_subclass_of($conn, 'Net_SSH2'))
            {
                return $conn;
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
        $ret['result'] = WPVIVID_PRO_SUCCESS;
        $ret['backup'] = array();
        $ret['path']=array();
        if (!is_subclass_of($conn, 'Net_SSH2'))
        {
            return $conn;
        }
        $list = $conn->rawlist($path);
        if ($list == false)
        {
            return $ret;
        }

        $files=array();
        foreach ($list as $file)
        {
            if ($file['type'] == 1)
            {
                $file_data['file_name'] = $file['filename'];
                $file_data['size'] = $file['size'];
                $files[] = $file_data;
            }
            else if($file['type'] == 2)
            {
                if($file['filename']=='.'||$file['filename']=='..'||$file['filename']=='rollback')
                {
                    continue;
                }
                $ret['path'][]=$file['filename'];
                //$ret_child=$this->_scan_child_folder_backup($path,$file['filename'],$conn);
                //if($ret_child['result']==WPVIVID_PRO_SUCCESS)
                //{
                //    $files= array_merge($files,$ret_child['files']);
                //}
            }
        }

        if (!empty($files))
        {
            global $wpvivid_backup_pro;
            $ret['backup'] = $wpvivid_backup_pro->func->get_backup($files);
        }
        return $ret;
    }

    public function _scan_child_folder_backup($path,$sub_path,$conn)
    {
        $ret['result'] = WPVIVID_PRO_SUCCESS;
        $ret['backup']=array();
        $ret['files']=array();

        if (!is_subclass_of($conn, 'Net_SSH2'))
        {
            return $conn;
        }

        $list = $conn->rawlist($path.'/'.$sub_path);

        if ($list == false)
        {
            return $ret;
        }

        foreach ($list as $file)
        {
            if ($file['type'] == 1)
            {
                $file_data['file_name'] = $file['filename'];
                $file_data['size'] = $file['size'];
                $file_data['remote_path']=$sub_path;
                $ret['files'][]= $file_data;
            }
        }

        if (!empty($ret['files']))
        {
            global $wpvivid_backup_pro;
            $ret['backup'] = $wpvivid_backup_pro->func->get_backup($ret['files']);
        }

        return $ret;
    }

    public function scan_folder_backup_ex($folder_type)
    {
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);
        if (!is_subclass_of($conn, 'Net_SSH2'))
        {
            return $conn;
        }

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
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback';


        return $this->_scan_folder_backup($path,$conn);
    }

    public function _get_incremental_backups($incremental_path,$conn)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path = $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/'.$incremental_path;

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
        try {
            global $wpvivid_plugin;

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
            $port = empty($this->options['port']) ? 22 : $this->options['port'];

            $remote_file = trailingslashit($path) . $backup_info_file;

            $conn = $this->do_connect($host, $username, $password, $port);

            if (!is_subclass_of($conn, 'Net_SSH2'))
            {
                return $conn;
            }

            $local_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$backup_info_file;
            @unlink($local_path);
            $local_handle = fopen($local_path, 'a');

            $status = $conn->get($remote_file, $local_handle);
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
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: ' . get_class($error) . ';msg: ' . $error->getMessage() . ';code: ' . $error->getCode() . ';line: ' . $error->getLine() . ';in_file: ' . $error->getFile() . ';';
            error_log($message);
            return array('result' => WPVIVID_PRO_FAILED, 'error' => $message);
        }
    }

    public function scan_rollback($type)
    {
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1)
        {
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }

        $port = $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);

        if (is_array($conn) && $conn['result'] == WPVIVID_PRO_FAILED)
        {
            return $conn;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $root_path=untrailingslashit($root_path);

        if($type === 'plugins')
        {
            if(isset($this->options['custom_path']))
            {
                $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/plugins';
            }
            else
            {
                $path = $this->options['path'].$root_path.'/rollback_ex/plugins';
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
            if(isset($this->options['custom_path']))
            {
                $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/themes';
            }
            else
            {
                $path=$root_path.'/'.$this->options['path'].'/rollback_ex/themes';
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
        $ret['result'] = WPVIVID_PRO_SUCCESS;

        $ret['path']=array();
        if (!is_subclass_of($conn, 'Net_SSH2'))
        {
            return $conn;
        }
        $list = $conn->rawlist($path);
        if ($list == false)
        {
            return $ret;
        }

        foreach ($list as $file)
        {
            if($file['type'] == 2)
            {
                if($file['filename']=='.'||$file['filename']=='..')
                {
                    continue;
                }

                $ret['path'][]=$file['filename'];
            }
        }

        return $ret;
    }

    public function get_rollback_data($type,$slug)
    {
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1)
        {
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }

        $port = $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);

        if (is_array($conn) && $conn['result'] == WPVIVID_PRO_FAILED)
        {
            return $conn;
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $root_path=untrailingslashit($root_path);

        if($type === 'plugins')
        {
            if(isset($this->options['custom_path']))
            {
                $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/plugins/'.$slug;
            }
            else
            {
                $path = $this->options['path'].$root_path.'/rollback_ex/plugins/'.$slug;
            }

            $response=$this->_scan_folder($path,$conn);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path_list= $response['path'];
                if(!empty($path))
                {
                    foreach ($path_list as $version)
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
            if(isset($this->options['custom_path']))
            {
                $path= $this->options['path'].$root_path.'/'.$this->options['custom_path'].'/rollback_ex/themes/'.$slug;
            }
            else
            {
                $path = $this->options['path'].$root_path.'/rollback_ex/themes/'.$slug;
            }

            $response=$this->_scan_folder($path,$conn);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path_list= $response['path'];
                if(!empty($path_list))
                {
                    foreach ($path_list as $version)
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
        $ret['result'] = WPVIVID_PRO_SUCCESS;

        $ret['path']=array();
        if (!is_subclass_of($conn, 'Net_SSH2'))
        {
            return $conn;
        }
        $list = $conn->rawlist($path);
        if ($list == false)
        {
            return $ret;
        }

        foreach ($list as $file)
        {
            if ($file['type'] == 1)
            {
                if($file['filename']==$file_name)
                {
                    $file_data['file_name']=$file['filename'];
                    $file_data['size']=$file['size'];
                    $file_data['mtime']=$file['mtime'];
                    $ret['file']=$file_data;
                    break;
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

    public function delete_old_backup($backup_count,$db_count)
    {
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = $this->options['port'];
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $conn = $this->do_connect($host, $username, $password, $port);

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
                $this->cleanup($folders);
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
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);
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
        else if($folder_type === 'Rollback'){

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

        return false;
    }

    public function delete_old_backup_ex($type,$backup_count,$db_count)
    {
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = $this->options['port'];
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $conn = $this->do_connect($host, $username, $password, $port);

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
                    $this->cleanup($folders);
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
        if (empty($this->options['port']))
            $this->options['port'] = 22;
        $host = $this->options['host'];
        $username = $this->options['username'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1){
            $password = base64_decode($this->options['password']);
        }
        else {
            $password = $this->options['password'];
        }
        $port = $this->options['port'];

        $conn = $this->do_connect($host, $username, $password, $port);
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