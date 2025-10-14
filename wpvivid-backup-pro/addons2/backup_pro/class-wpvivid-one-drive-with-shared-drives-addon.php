<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_one_drive_with_shared_drives_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES'))
{
    define('WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES','onedrive_shared');
}

if(!defined('WPVIVID_ONEDRIVE_DEFAULT_FOLDER'))
{
    define('WPVIVID_ONEDRIVE_DEFAULT_FOLDER','wpvivid_backup');
}

if(!defined('WPVIVID_ONEDRIVE_UPLOAD_SIZE'))
{
    define('WPVIVID_ONEDRIVE_UPLOAD_SIZE',1024*1024*2);
}

if(!defined('WPVIVID_ONEDRIVE_DOWNLOAD_SIZE'))
{
    define('WPVIVID_ONEDRIVE_DOWNLOAD_SIZE',1024*1024*2);
}

if(!defined('WPVIVID_ONEDRIVE_RETRY_TIMES'))
{
    define('WPVIVID_ONEDRIVE_RETRY_TIMES','3');
}

class WPvivid_one_drive_with_shared_drives_addon extends WPvivid_Remote_addon
{
    public $options;
    public $callback;
    public $add_remote;
    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('WPVIVID_INIT_STORAGE_TAB_ONE_DRIVE_SHARED_DRIVES'))
            {
                add_action('init', array($this, 'handle_auth_actions'));
                add_action('wp_ajax_wpvivid_one_drive_shared_add_remote',array( $this,'finish_add_remote'));
                add_action('wpvivid_add_storage_page_onedrive_shared_drives', array($this, 'wpvivid_add_storage_page_one_drive'));
                add_action('wpvivid_add_storage_page',array($this,'wpvivid_add_storage_page_one_drive'), 11);
                add_action('wpvivid_edit_remote_page',array($this,'wpvivid_edit_storage_page_one_drive'), 11);
                add_filter('wpvivid_get_out_of_date_remote',array($this,'wpvivid_get_out_of_date_one_drive'),10,2);
                add_filter('wpvivid_storage_provider_tran',array($this,'wpvivid_storage_provider_one_drive'),10);
                add_filter('wpvivid_get_root_path',array($this,'wpvivid_get_root_path_one_drive'),10);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'),11);
                define('WPVIVID_INIT_STORAGE_TAB_ONE_DRIVE_SHARED_DRIVES',1);
            }
        }
        else
        {
            $this->options=$options;
        }
        $this->add_remote=false;
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES] = 'WPvivid_one_drive_with_shared_drives_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function handle_auth_actions()
    {
        if (isset($_GET['action']))
        {
            if($_GET['action']=='wpvivid_pro_one_drive_shared_auth')
            {
                if(!apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-remote'))
                {
                    return;
                }

                try {
                    $rand_id = substr(md5(time().rand()), 0,13);
                    $auth_id = 'wpvivid-auth-'.$rand_id;
                    $remote_options['auth_id']=$auth_id;
                    set_transient('shared_onedrive_auth_id', $remote_options, 900);
                    $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize'
                        . '?client_id=' . urlencode('37668be9-b55f-458f-b6a3-97e6f8aa10c9')
                        . '&scope=' . urlencode('offline_access files.readwrite')
                        . '&response_type=code'
                        . '&redirect_uri=' . urlencode('https://auth.wpvivid.com/onedrive_v2/')
                        . '&state=' . urlencode(apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='.sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')).'&action=wpvivid_pro_one_drive_shared_finish_auth&sub_page=cloud_storage_onedrive_shared&auth_id='.$auth_id)
                        . '&display=popup'
                        . '&locale=en';
                    header('Location: ' . esc_url_raw($url));
                }
                catch (Exception $e){
                    _e('<div class="notice notice-error"><p>'.$e->getMessage().'</p></div>');
                }
            }
            else if($_GET['action']=='wpvivid_pro_one_drive_shared_finish_auth')
            {
                $tmp_options = get_transient('shared_onedrive_auth_id');
                if($tmp_options === false)
                {
                    return;
                }
                else if($tmp_options['auth_id'] !== $_GET['auth_id'])
                {
                    delete_transient('shared_onedrive_auth_id');
                    return;
                }
                try
                {
                    if (isset($_GET['auth_error']))
                    {
                        $error = urldecode($_GET['auth_error']);
                        header('Location: ' . apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page=' . sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')) . '&action=wpvivid_pro_one_drive_shared&result=error&resp_msg=' . $error);
                        return;
                    }

                    $remoteslist = WPvivid_Setting::get_all_remote_options();
                    foreach ($remoteslist as $key => $value)
                    {
                        if (isset($value['auth_id']) && isset($_GET['auth_id']) && $value['auth_id'] == $_GET['auth_id'])
                        {
                            _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the Microsoft OneDrive account as your remote storage.</p></div>');
                            return;
                        }
                    }

                    if(empty($_POST['refresh_token']))
                    {
                        if(empty($tmp_options['token']['refresh_token']))
                        {
                            $err = 'No refresh token was received from OneDrive, which means that you entered client secret incorrectly, or that you did not re-authenticated yet after you corrected it. Please authenticate again.';
                            header('Location: '.admin_url().'admin.php?page='.sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')).'&action=wpvivid_pro_one_drive_shared&result=error&resp_msg='.$err);

                            return;
                        }
                    }
                    else
                    {
                        $tmp_options['type'] = WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES;
                        $tmp_options['token']['access_token']=base64_encode($_POST['access_token']);
                        $tmp_options['token']['refresh_token']=base64_encode($_POST['refresh_token']);
                        $tmp_options['token']['expires']=time()+$_POST['expires_in'];
                        $tmp_options['is_encrypt'] = 1;
                        set_transient('shared_onedrive_auth_id', $tmp_options, 900);
                    }
                    $this->add_remote=true;
                }
                catch (Exception $e){
                    _e('<div class="notice notice-error"><p>'.$e->getMessage().'</p></div>');
                }
            }
            else if($_GET['action']=='wpvivid_pro_one_drive_shared')
            {
                try {
                    if (isset($_GET['result'])) {
                        if ($_GET['result'] == 'success') {
                            //add_action('show_notice', array($this, 'wpvivid_show_notice_add_onedrive_success'));
                            _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the Microsoft OneDrive account as your remote storage.</p></div>');
                        } else if ($_GET['result'] == 'error') {
                            //add_action('show_notice', array($this, 'wpvivid_show_notice_add_onedrive_error'));
                            global $wpvivid_plugin;
                            $wpvivid_plugin->wpvivid_handle_remote_storage_error($_GET['resp_msg'], 'Add OneDrive Remote');
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
    public function wpvivid_show_notice_add_onedrive_success(){
        _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the Microsoft OneDrive account as your remote storage.</p></div>');
    }
    public function wpvivid_show_notice_add_onedrive_error(){
        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_handle_remote_storage_error($_GET['resp_msg'], 'Add OneDrive Remote');
        _e('<div class="notice notice-error"><p>'.$_GET['resp_msg'].'</p></div>');
    }

    public function wpvivid_add_storage_page_one_drive()
    {
        global $wpvivid_backup_pro;
        if($this->add_remote)
        {
            ?>
            <div id="storage_account_one_drive" class="storage-account-page">
                <div style="color:#8bc34a; padding: 0 10px 10px 0;">
                    <strong>Authentication is done, please continue to enter the storage information, then click 'Add Now' button to save it.</strong>
                </div>
                <div style="padding: 0 10px 10px 0;">
                    <strong>Enter Your Microsoft OneDrive Information</strong>
                </div>
                <table class="wp-list-table widefat plugins" style="width:100%;">
                    <tbody>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="one_drive" name="name" placeholder="Enter a unique alias: e.g. SharedDrive-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <select option="one_drive" name="drive_id">
                                <?php
                                $drives=$this->get_tmp_drive_list();
                                foreach ($drives as $drive)
                                {
                                    if(empty($drive['name']))
                                    {
                                        $drive['name']="my drive";
                                    }
                                    echo '<option value="'.$drive["id"].'">'.$drive['name'].'</option>';
                                }
                                ?>
                                </select>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Select a shared drive you want to store backups to.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" autocomplete="off" option="one_drive" name="root_path" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Customize a root directory in the shared drive for holding %s directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="one_drive" name="path" placeholder="One Drive Folder" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_/]/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Customize the directory where you want to store backups. By default it takes your current website domain or url.</i>
                            </div>
                        </td>
                    </tr>

                    <?php do_action('wpvivid_remote_storage_backup_retention', 'one_drive', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="one_drive" name="default" checked />Set as the default remote storage.
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
                                <input id="wpvivid_one_drive_auth" class="button-primary" type="submit" value="Add Now">
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
                function wpvivid_check_onedrive_storage_alias(storage_alias)
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

                jQuery('#wpvivid_one_drive_auth').click(function()
                {
                    wpvivid_one_drive_auth();
                });

                function wpvivid_one_drive_auth()
                {
                    wpvivid_settings_changed = false;
                    var name='';
                    var root_path='';
                    var path='';
                    var backup_retain='';
                    var backup_db_retain='';
                    var backup_incremental_retain='';
                    var backup_rollback_retain='';
                    jQuery('input:text[option=one_drive]').each(function()
                    {
                        var key = jQuery(this).prop('name');
                        if(key==='name')
                        {
                            name = jQuery(this).val();
                        }
                        if(key==='root_path')
                        {
                            root_path = jQuery(this).val();
                        }
                        if(key==='path')
                        {
                            path = jQuery(this).val();
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

                    var remote_default='0';

                    jQuery('input:checkbox[option=one_drive][name=default]').each(function()
                    {
                        if(jQuery(this).prop('checked')) {
                            remote_default='1';
                        }
                        else {
                            remote_default='0';
                        }
                    });

                    var use_remote_retention = '0';
                    jQuery('input:checkbox[option=one_drive][name=use_remote_retention]').each(function()
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
                    else if(wpvivid_check_onedrive_storage_alias(name) === false){
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
                    else {
                        var ajax_data;
                        var remote_from = wpvivid_ajax_data_transfer('one_drive');
                        ajax_data = {
                            'action': 'wpvivid_one_drive_shared_add_remote',
                            'remote': remote_from
                        };
                        jQuery('#wpvivid_one_drive_auth').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#wpvivid_remote_notice').html('');
                        wpvivid_post_request_addon(ajax_data, function (data)
                        {
                            try
                            {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success')
                                {
                                    jQuery('#wpvivid_one_drive_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('input:text[option=one_drive]').each(function(){
                                        jQuery(this).val('');
                                    });
                                    jQuery('input:password[option=one_drive]').each(function(){
                                        jQuery(this).val('');
                                    });
                                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote').'&action=wpvivid_pro_one_drive&result=success'; ?>';
                                }
                                else if (jsonarray.result === 'failed')
                                {
                                    jQuery('#wpvivid_remote_notice').html(jsonarray.notice);
                                    jQuery('#wpvivid_one_drive_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                            }
                            catch (err)
                            {
                                alert(err);
                                jQuery('#wpvivid_one_drive_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                            }

                        }, function (XMLHttpRequest, textStatus, errorThrown)
                        {
                            var error_message = wpvivid_output_ajaxerror('adding the remote storage', textStatus, errorThrown);
                            alert(error_message);
                            jQuery('#wpvivid_one_drive_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                        });
                    }
                }
            </script>
            <?php
        }
        else
        {
            ?>
            <div id="storage_account_one_drive" class="storage-account-page">
                <div style="padding: 0 10px 10px 0;">
                    <strong>To add OneDrive Shared Drives, please get Microsoft authentication first. Once authenticated, you will be redirected to this page, then you can add storage information and save it.</strong>
                </div>
                <table class="wp-list-table widefat plugins" style="width:100%;">
                    <tbody>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input onclick="wpvivid_one_drive_auth();" class="button-primary" type="submit" value="Authenticate with Microsoft OneDrive">
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Click to get Microsoft authentication.</i>
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
                function wpvivid_one_drive_auth()
                {
                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote').'&action=wpvivid_pro_one_drive_shared_auth'; ?>';
                }
            </script>
            <?php
        }
    }

    public function wpvivid_edit_storage_page_one_drive()
    {
        ?>
        <div id="remote_storage_edit_onedrive" >
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Microsoft OneDrive Information</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-onedrive_shared" name="name" placeholder="Enter a unique alias: e.g. SharedDrive-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                            <input type="text" class="regular-text" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" autocomplete="off" option="edit-onedrive_shared" name="root_path" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i><?php echo sprintf(__('Customize a root directory in your OneDrive for holding %s directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-onedrive_shared" name="path" placeholder="One Drive Folder" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_/]/g,'')" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Customize the directory where you want to store backups. By default it takes your current website domain or url.</i>
                        </div>
                    </td>
                </tr>

                <?php do_action('wpvivid_remote_storage_backup_retention', 'onedrive_shared', 'edit'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input onclick="wpvivid_one_drive_update_auth();" class="button-primary" type="submit" value="Save Changes">
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
            function wpvivid_one_drive_update_auth()
            {
                var name='';
                var root_path = '';
                var path='';
                var backup_retain='';
                var backup_db_retain='';
                var backup_incremental_retain='';
                var backup_rollback_retain='';
                jQuery('input:text[option=edit-onedrive_shared]').each(function()
                {
                    var key = jQuery(this).prop('name');
                    if(key==='name')
                    {
                        name = jQuery(this).val();
                    }
                    if(key==='root_path')
                    {
                        root_path = jQuery(this).val();
                    }
                    if(key==='path')
                    {
                        path = jQuery(this).val();
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
                jQuery('input:checkbox[option=edit-onedrive_shared][name=use_remote_retention]').each(function()
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
        </script>
        <?php
    }

    public function get_tmp_drive_list()
    {
        $tmp_remote_options = get_transient('shared_onedrive_auth_id');
        $this->options=$tmp_remote_options;
        $url='https://graph.microsoft.com/v1.0/me/drives';

        $drivers=array();

        $response=$this->remote_get($url);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['url']=$url;
            $body=$response['body'];
            if(isset($body['value']))
            {
                foreach ($body['value'] as $item)
                {
                    $drivers[]=$item;
                }
            }
        }
        return $drivers;
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

    public function test_connect()
    {
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function upload($task_id, $files, $callback = '')
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        if($this->need_refresh())
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('The token expired and will go to the server to refresh the token.','notice');
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

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

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'];
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check upload folder '.$path,'notice');
        $ret=$this->check_folder($path);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

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

            if($this->need_refresh())
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('The token expired and will go to the server to refresh the token.','notice');
                $ret=$this->refresh_token();
                if($ret['result']===WPVIVID_PRO_FAILED)
                {
                    return $ret;
                }
            }
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup($files)
    {
        set_time_limit(120);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'];
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $ret=$this->get_files_id($files,$path);
        if($ret['result']==WPVIVID_PRO_SUCCESS)
        {
            $ids=$ret['ids'];
            foreach ($ids as $id)
            {
                $this->delete_file($id);
            }
        }
        else
        {
            return $ret;
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        set_time_limit(120);

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'].'/rollback_ex/'.$type.'/'.$slug.'/'.$version;
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $files[]=$slug.'.zip';
        $ret=$this->get_files_id($files,$path);
        if($ret['result']==WPVIVID_PRO_SUCCESS)
        {
            $ids=$ret['ids'];
            foreach ($ids as $id)
            {
                $this->delete_file($id);
            }
        }
        else
        {
            return $ret;
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function set_token()
    {
        $remote_options=WPvivid_Setting::get_remote_option($this->options['id']);
        if($remote_options!==false)
        {
            $this->options['token']=$remote_options['token'];
            if(isset($remote_options['is_encrypt']))
            {
                $this->options['is_encrypt']=$remote_options['is_encrypt'];
            }
        }
    }

    public function download($file, $local_path, $callback = '')
    {
        $this -> current_file_name = $file['file_name'];
        $this -> current_file_size = $file['size'];
        $this->callback=$callback;
        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Remote type: OneDrive.','notice');
        $this->set_token();
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if(isset($file['remote_path']))
        {
            $path=$root_path.'/'.$this->options['path'].'/'.$file['remote_path'];
        }
        else
        {
            $path=$root_path.'/'.$this->options['path'];
        }
        $wpvivid_plugin->wpvivid_download_log->WriteLog('download from:'.$path,'notice');

        $ret=$this->check_file($file['file_name'],$path);
        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $file_path=$local_path.$file['file_name'];
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.','notice');
        $fh = fopen($file_path, 'a');
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'] ,'notice');
        $downloaded_start=filesize($file_path);
        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.'/'.$file['file_name'].':/content';
        $download_size=WPVIVID_ONEDRIVE_DOWNLOAD_SIZE;
        $size=$file['size'];
        while($downloaded_start<$size)
        {
            $ret=$this->download_loop($url,$downloaded_start,$download_size,$size);
            if($ret['result']!=WPVIVID_PRO_SUCCESS)
            {
                return $ret;
            }

            fwrite($fh,$ret['body']);
        }

        fclose($fh);
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function download_loop($url,&$downloaded_start,$download_size,$file_size,$retry_count=0)
    {
        $downloaded_end=min($downloaded_start+$download_size-1,$file_size-1);
        $response=$this->remote_get_download_backup($url,$downloaded_start,$downloaded_end,false,30);

        if((time() - $this -> last_time) >3)
        {
            if(is_callable($this->callback))
            {
                call_user_func_array($this->callback,array($downloaded_start,$this -> current_file_name,
                    $this->current_file_size,$this -> last_time,$this -> last_size));
            }
            $this -> last_size = $downloaded_start;
            $this -> last_time = time();
        }

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $downloaded_start=$downloaded_end+1;
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['body']=$response['body'];
            return $ret;
        }
        else
        {
            if($retry_count<WPVIVID_ONEDRIVE_RETRY_TIMES)
            {
                $retry_count++;
                return $this->download_loop($url,$downloaded_start,$download_size,$file_size,$retry_count);
            }
            else
            {
                return $response;
            }
        }
    }

    public function remote_get_download_backup($url,$downloaded_start,$downloaded_end,$decode=true,$timeout=30,$except_code=array())
    {
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        if(empty($except_code))
        {
            $except_code=array(200,201,202,204,206);
        }

        $curl = curl_init();
        $curl_options = array(
            CURLOPT_URL			=> $url,
            CURLOPT_HTTPHEADER 	=> array(
                'Authorization: Bearer ' . $access_token,
                'Range: '."bytes=$downloaded_start-$downloaded_end"
            ),
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_SSL_VERIFYPEER => true
        );
        $curl_options[CURLOPT_CAINFO] = WPVIVID_BACKUP_PRO_PLUGIN_DIR.'includes/resources/cacert.pem';
        $curl_options[CURLOPT_FOLLOWLOCATION] = true;
        curl_setopt_array($curl, $curl_options);
        $result = curl_exec($curl);
        $http_info = curl_getinfo($curl);
        $http_code = array_key_exists('http_code', $http_info) ? (int) $http_info['http_code'] : null;
        if($result !== false)
        {
            curl_close($curl);
            if($http_code==401)
            {
                $this->refresh_token();
                $ret=$this->remote_get_download_backup($url,$downloaded_start,$downloaded_end,$decode,$timeout,$except_code);
                return $ret;
            }
            else
            {
                if(in_array($http_code,$except_code))
                {
                    $ret['result']=WPVIVID_PRO_SUCCESS;
                    if($decode)
                        $ret['body']=json_decode($result,1);
                    else
                        $ret['body']=$result;
                    return $ret;
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='Download files failed, error code: '.$http_code;
                    return $ret;
                }
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

    public function need_refresh()
    {
        if(time()+120> $this->options['token']['expires'])
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function refresh_token()
    {
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $refresh_token=base64_decode($this->options['token']['refresh_token']);
        }
        else{
            $refresh_token=$this->options['token']['refresh_token'];
        }

        $args['method']='POST';
        $args['wpvivid_refresh_token']=1;
        $args['timeout']=30;
        $args['sslverify']=FALSE;
        $args['body']=array( 'wpvivid_refresh_token' => '1', 'refresh_token' => $refresh_token);
        $response=wp_remote_post('https://auth.wpvivid.com/onedrive_v2/',$args);
        if(!is_wp_error($response) && ($response['response']['code'] == 200))
        {
            $json =stripslashes($response['body']);
            $json_ret =json_decode($json,true);
            if($json_ret['result']=='success')
            {
                $remote_options=WPvivid_Setting::get_remote_option($this->options['id']);
                $json_ret['token']['access_token']=base64_encode($json_ret['token']['access_token']);
                $json_ret['token']['refresh_token']=base64_encode($json_ret['token']['refresh_token']);
                $this->options['token']=$json_ret['token'];
                $this->options['is_encrypt']=1;
                $this->options['token']['expires']=time()+ $json_ret['token']['expires_in'];
                if($remote_options!==false)
                {
                    $remote_options['is_encrypt']=1;
                    $remote_options['token']=$json_ret['token'];
                    $remote_options['token']['expires']=time()+ $json_ret['token']['expires_in'];
                    WPvivid_Setting::update_remote_option($this->options['id'],$remote_options);

                    $schedules = get_option('wpvivid_schedule_addon_setting', array());
                    if(!empty($schedules))
                    {
                        foreach ($schedules as $schedule_id=>$schedule_data)
                        {
                            if($schedule_data['backup']['remote'] === 1 && isset($schedule_data['backup']['remote_options']))
                            {
                                foreach ($schedule_data['backup']['remote_options'] as $remote_id=>$remote_data)
                                {
                                    if($remote_id === $this->options['id'])
                                    {
                                        $schedules[$schedule_id]['backup']['remote_options'][$remote_id] = $remote_options;
                                        update_option('wpvivid_schedule_addon_setting', $schedules,'no');
                                    }
                                }
                            }
                        }
                    }
                }

                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
            else{
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']=$json_ret['error'];
                return $ret;
            }
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            if ( is_wp_error( $response ) )
            {
                $ret['error']= $response->get_error_message();
            }
            else
            {
                $ret['error']=$response['response']['message'];
            }
            return $ret;
        }
    }

    private function check_folder($folder)
    {
        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$folder.'?$select=id,name,folder';
        $response=$this->remote_get($url);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            return $ret;
        }
        else
        {
            if(isset($response['code'])&&$response['code'] ==404)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
            else
            {
                return $response;
            }
        }
    }

    private function check_file($file,$folder)
    {
        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$folder.'/'.$file.'?$select=id,name,size';
        $response=$this->remote_get($url);
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

    public function chunk_download($download_info,$callback)
    {
        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];
        $this->callback=$callback;

        $local_path = $download_info['local_path'];

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path=$root_path.'/'.$this->options['path'];
        $path=rtrim($path, '/');

        $ret=$this->check_file($download_info['file_name'],$path);
        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        clearstatcache();
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

        $this->set_token();
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $time_limit = 30;
        $start_time = time();

        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.'/'.$download_info['file_name'].':/content';
        $download_size=WPVIVID_ONEDRIVE_DOWNLOAD_SIZE;
        $size=$download_info['size'];

        while($start_offset<$size)
        {
            $ret=$this->download_loop($url,$start_offset,$download_size,$size);
            if($ret['result']!=WPVIVID_PRO_SUCCESS)
            {
                return $ret;
            }

            fwrite($fh,$ret['body']);

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

        fclose($fh);

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
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'];
        $path = $path.'/rollback_ex/'.$folder.'/'.$slug.'/'.$version;
        $ret=$this->check_folder($path);

        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        $this -> current_file_size = filesize($file);
        $this -> current_file_name = basename($file);

        $this->delete_file_by_name($path,basename($file));

        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        $path=$path.'/'.basename($file);
        $args['method']='PUT';
        $args['headers']=array( 'Authorization' => 'bearer '.$access_token,'content-type' => 'application/zip');
        $args['timeout']=30;

        $data=file_get_contents($file);
        $args['body']=$data;

        $response=wp_remote_post('https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.':/content',$args);

        if(!is_wp_error($response) && ($response['response']['code'] == 200||$response['response']['code'] == 201))
        {
            return array('result' =>"success");
        }
        else
        {
            $ret['result']='failed';
            if ( is_wp_error( $response ) )
            {
                $ret['error']= $response->get_error_message();
            }
            else
            {
                $error=json_decode($response['body'],1);
                $ret['error']=$error['error']['message'];
            }
            return $ret;
        }

    }

    public function download_rollback($download_info)
    {
        $type=$download_info['type'];
        $slug=$download_info['slug'];
        $version=$download_info['version'];

        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];
        $local_path = $download_info['local_path'];

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        $path=$root_path.'/'.$this->options['path'];
        $path=rtrim($path, '/');
        $path = $path.'/rollback_ex/'.$type.'/'.$slug.'/'.$version;

        $ret=$this->check_file($download_info['file_name'],$path);
        if($ret['result']===WPVIVID_PRO_FAILED)
        {
            return $ret;
        }

        clearstatcache();
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

        $this->set_token();
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']==="failed")
            {
                return $ret;
            }
        }

        $time_limit = 30;
        $start_time = time();

        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.'/'.$download_info['file_name'].':/content';
        $download_size=WPVIVID_ONEDRIVE_DOWNLOAD_SIZE;
        $size=$download_info['size'];

        while($start_offset<$size)
        {
            $ret=$this->download_loop($url,$start_offset,$download_size,$size);
            if($ret['result']!=WPVIVID_PRO_SUCCESS)
            {
                return $ret;
            }

            fwrite($fh,$ret['body']);

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

        fclose($fh);

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

    private function _upload($task_id,$local_file,$remote_path,$callback)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $this -> current_file_size = filesize($local_file);
        $this -> current_file_name = basename($local_file);

        //$wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check if the server already has the same name file.','notice');

        //$this->delete_file_by_name($remote_path,basename($local_file));

        $file_size=filesize($local_file);

        //small file
        if($file_size<1024*1024*4)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploaded files are less than 4M.','notice');
            $ret=$this->upload_small_file($local_file,$remote_path,$task_id);
            return $ret;
        }
        else
        {
            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
            if(empty( $upload_job['job_data'][basename($local_file)]['uploadUrl']))
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Creating upload session.','notice');
                //big file
                $ret=$this->create_upload_session(basename($local_file),$remote_path);

                if($ret['result']===WPVIVID_PRO_FAILED)
                {
                    return $ret;
                }

                $upload_job['job_data'][basename($local_file)]['uploadUrl']=$ret['session_url'];
                $session_url=$ret['session_url'];

                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Created upload session',$upload_job['job_data']);
            }
            else
            {
                $session_url=$upload_job['job_data'][basename($local_file)]['uploadUrl'];
            }

            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Ready to start uploading files.','notice');
            $ret=$this->upload_resume($session_url,$local_file,$task_id,$callback);

            return $ret;
        }
    }

    private function upload_small_file($file,$remote_path,$task_id)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);

        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        $path=$remote_path.'/'.basename($file);
        $args['method']='PUT';
        $args['headers']=array( 'Authorization' => 'bearer '.$access_token,'content-type' => 'application/zip');
        $args['timeout']=30;

        $data=file_get_contents($file);
        $args['body']=$data;

        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading '.basename($file).'.',$upload_job['job_data']);

        $response=wp_remote_post('https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.':/content',$args);

        if(!is_wp_error($response) && ($response['response']['code'] == 200||$response['response']['code'] == 201))
        {
            $upload_job['job_data'][basename($file)]['uploaded']=1;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
            WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
            return array('result' =>WPVIVID_PRO_SUCCESS);
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            if ( is_wp_error( $response ) )
            {
                $ret['error']= $response->get_error_message();
            }
            else
            {
                $error=json_decode($response['body'],1);
                $ret['error']=$error['error']['message'];
            }
            return $ret;
        }
    }

    private function upload_resume($session_url,$file,$task_id,$callback)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);

        $ret=$this->get_upload_offset($session_url);

        if($ret['result']=='failed')
        {
            return $ret;
        }

        $offset=$ret['offset'];
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('offset '.$offset,'notice');

        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading '.basename($file).'.',$upload_job['job_data']);

        $file_size=filesize($file);
        $handle=fopen($file,'rb');
        $upload_size=WPVIVID_ONEDRIVE_UPLOAD_SIZE;
        $upload_end=min($offset+$upload_size-1,$file_size-1);
        while(true)
        {
            $ret=$this->upload_loop($session_url,$handle,$offset,$upload_end,$upload_size,$file_size);

            if($ret['result']==WPVIVID_PRO_SUCCESS)
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
                return $ret;
            }
        }

        fclose($handle);
        $upload_job['job_data'][basename($file)]['uploaded']=1;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    private function get_upload_offset($uploadUrl)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('uploadUrl: '.$uploadUrl,'notice');

        $url=$uploadUrl;
        $response=$this->remote_get_ex($url);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            if($response['code']==200)
            {
                $ranges=$response['body']['nextExpectedRanges'];

                if (is_array($ranges))
                {
                    $range = $ranges[0];
                } else {
                    $range=$ranges;
                }

                if (preg_match('/^(\d+)/', $range, $matches))
                {
                    $uploaded = $matches[1];
                    $ret['result']='success';
                    $ret['offset']=$uploaded;
                    return $ret;
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='get offset failed';
                    return $ret;
                }
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='get offset failed';
                return $ret;
            }
        }
        else
        {
            return $response;
        }
    }

    private function create_upload_session($file,$remote_path)
    {
        $path=$remote_path.'/'.basename($file);
        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.':/createUploadSession';
        $response=$this->remote_post($url);

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $upload_session=$response['body']['uploadUrl'];

            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['session_url']=$upload_session;
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    private function upload_loop($url,$file_handle,&$uploaded,&$upload_end,$upload_size,$file_size,$retry_count=0)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $upload_size=min($upload_size,$file_size-$uploaded);

        if ($uploaded)
            fseek($file_handle, $uploaded);

        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        $headers = array(
            "Content-Length: $upload_size",
            "Content-Range: bytes $uploaded-$upload_end/".$file_size,
        );
        //$headers[] = 'Authorization: Bearer ' . $access_token;

        $options = array(
            CURLOPT_URL        => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_PUT        => true,
            CURLOPT_INFILE     => $file_handle,
            CURLOPT_INFILESIZE => $upload_size,
            CURLOPT_RETURNTRANSFER=>true,
        );

        curl_setopt_array($curl, $options);

        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $response=curl_exec($curl);

        $http_code = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($response!=false)
        {
            curl_close($curl);
            if($http_code==202)
            {
                $json=json_decode($response,1);
                $ranges=$json['nextExpectedRanges'];

                if (is_array($ranges))
                {
                    $range = $ranges[0];
                } else {
                    $range=$ranges;
                }

                if (preg_match('/^(\d+)/', $range, $matches))
                {
                    $uploaded = $matches[1];
                    $upload_end=min($uploaded+$upload_size-1,$file_size-1);
                }

                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['op']='continue';
                return $ret;
            }
            else if($http_code==200||$http_code==201)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['op']='finished';
                return $ret;
            }
            else
            {
                if($retry_count<WPVIVID_ONEDRIVE_RETRY_TIMES)
                {
                    $error=json_decode($response,1);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('http code is not 200, start retry. http core :'.$http_code.', error: '.json_encode($error),'notice');
                    $ret=$this->get_upload_offset($url);

                    if($ret['result']=='failed')
                    {
                        return $ret;
                    }

                    $uploaded=$ret['offset'];
                    $upload_end=min($uploaded+$upload_size-1,$file_size-1);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('offset '.$uploaded,'notice');
                    $retry_count++;
                    return $this->upload_loop($url,$file_handle,$uploaded,$upload_end,$upload_size,$file_size,$retry_count);
                }
                else
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $error=json_decode($response,1);
                    $ret['error']=$error['error']['message'];
                    return $ret;
                }
            }
        }
        else
        {
            if($retry_count<WPVIVID_ONEDRIVE_RETRY_TIMES)
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('http no response, start retry.','notice');
                $ret=$this->get_upload_offset($url);

                if($ret['result']=='failed')
                {
                    return $ret;
                }

                $uploaded=$ret['offset'];
                $upload_end=min($uploaded+$upload_size-1,$file_size-1);
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('offset '.$uploaded,'notice');
                $retry_count++;
                return $this->upload_loop($url,$file_handle,$uploaded,$upload_end,$upload_size,$file_size,$retry_count);
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']=curl_error($curl);
                curl_close($curl);
                return $ret;
            }
        }
    }

    private function get_files_id($files,$path)
    {
        $ret['ids']=array();
        foreach ($files as $file)
        {
            $file_path=$path;
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $file_path=$path.'/'.$file['remote_path'];
                }
                $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$file_path.'/'.$file['file_name'].'?$select=id';
            }
            else
            {
                $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$file_path.'/'.$file.'?$select=id';
            }
            $response=$this->remote_get($url);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                if($response['code']==200)
                {
                    $ret['ids'][]=$response['body']['id'];
                }
            }
            else
            {
                continue;
            }
        }

        if(sizeof($ret['ids'])==0)
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']='file not found';
        }
        else
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
        }

        return $ret;
    }

    private function delete_file($id)
    {
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        $args['method']='DELETE';
        $args['headers']=array( 'Authorization' => 'bearer '.$access_token);
        $args['timeout']=30;

        $response = wp_remote_request( 'https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/items/'.$id,$args);

        if(!is_wp_error($response) && ($response['response']['code'] == 204))
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            if ( is_wp_error( $response ) )
            {
                $ret['error']= $response->get_error_message();
            }
            else
            {
                $ret['error']= $response['body'];
            }
            return $ret;
        }
    }

    public function remote_get($url,$header=array(),$decode=true,$timeout=30,$except_code=array())
    {
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        if(empty($except_code))
        {
            $except_code=array(200,201,202,204,206);
        }
        $args['timeout']=$timeout;
        $args['headers']['Authorization']= 'bearer '.$access_token;
        $args['headers']= $args['headers']+$header;
        $response=wp_remote_get($url,$args);

        if(!is_wp_error($response))
        {
            if($response['response']['code']==401)
            {
                $this->refresh_token();

                if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
                    $access_token=base64_decode($this->options['token']['access_token']);
                }
                else{
                    $access_token=$this->options['token']['access_token'];
                }

                $args=array();
                $args['timeout']=$timeout;
                $args['headers']['Authorization']= 'bearer '.$access_token;
                $args['headers']= $args['headers']+$header;
                $response=wp_remote_get($url,$args);
            }

            $ret['code']=$response['response']['code'];
            if(in_array($response['response']['code'],$except_code))
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                if($decode)
                    $ret['body']=json_decode($response['body'],1);
                else
                    $ret['body']=$response['body'];
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $error=json_decode($response['body'],1);
                if(isset($error['error']['message']))
                {
                    $ret['error']=$error['error']['message'].' http code:'.$response['response']['code'];
                }
                else
                {
                    $ret['error']='http code:'.$response['response']['code'];
                }
            }
            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']=$response->get_error_message();
            return $ret;
        }
    }

    public function remote_get_ex($url,$header=array(),$decode=true,$timeout=30,$except_code=array())
    {
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        if(empty($except_code))
        {
            $except_code=array(200,201,202,204,206);
        }
        $args['timeout']=$timeout;
        $args['headers']=$header;
        $response=wp_remote_get($url,$args);

        if(!is_wp_error($response))
        {
            if($response['response']['code']==401)
            {
                $this->refresh_token();

                if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
                    $access_token=base64_decode($this->options['token']['access_token']);
                }
                else{
                    $access_token=$this->options['token']['access_token'];
                }

                $args=array();
                $args['timeout']=$timeout;
                $args['headers']['Authorization']= 'bearer '.$access_token;
                $args['headers']= $args['headers']+$header;
                $response=wp_remote_get($url,$args);
            }

            $ret['code']=$response['response']['code'];
            if(in_array($response['response']['code'],$except_code))
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                if($decode)
                    $ret['body']=json_decode($response['body'],1);
                else
                    $ret['body']=$response['body'];
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $error=json_decode($response['body'],1);
                $ret['error']=$error['error']['message'].' http code:'.$response['response']['code'];
            }
            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']=$response->get_error_message();
            return $ret;
        }
    }

    private function remote_post($url,$header=array(),$body=null,$except_code=array())
    {
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $access_token=base64_decode($this->options['token']['access_token']);
        }
        else{
            $access_token=$this->options['token']['access_token'];
        }

        if(empty($except_code))
        {
            $except_code=array(200,201,202,204,206);
        }

        $args['method']='POST';
        $args['headers']=array( 'Authorization' => 'bearer '.$access_token,'content-type' => 'application/json');
        $args['headers']=$args['headers']+$header;
        if(!is_null($body))
        {
            $args['body']=$body;
        }
        $args['timeout']=30;

        $response=wp_remote_post($url,$args);

        if(!is_wp_error($response))
        {
            if($response['response']['code']==401)
            {
                $this->refresh_token();

                if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
                    $access_token=base64_decode($this->options['token']['access_token']);
                }
                else{
                    $access_token=$this->options['token']['access_token'];
                }

                $args=array();
                $args['method']='POST';
                $args['headers']=array( 'Authorization' => 'bearer '.$access_token,'content-type' => 'application/json');
                $args['headers']=$args['headers']+$header;
                if(!is_null($body))
                {
                    $args['body']=$body;
                }
                $args['timeout']=30;
                $response=wp_remote_post($url,$args);
            }

            $ret['code']=$response['response']['code'];

            if(in_array($response['response']['code'],$except_code))
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['body']=json_decode($response['body'],1);
            }
            else
            {
                $ret['result']=WPVIVID_PRO_FAILED;
                $error=json_decode($response['body'],1);
                $ret['error']=$error['error']['message'];
            }
            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']=$response->get_error_message();
            return $ret;
        }
    }

    private function delete_file_by_name($folder,$file_name)
    {
        $files[]=$file_name;
        $ret=$this->get_files_id($files,$folder);

        if($ret['result']==WPVIVID_PRO_SUCCESS)
        {
            $ids=$ret['ids'];
            foreach ($ids as $id)
            {
                $ret=$this->delete_file($id);
                if($ret['result']==WPVIVID_PRO_FAILED)
                {
                    return $ret;
                }
            }
        }
        else
        {
            return $ret;
        }

        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function wpvivid_get_out_of_date_one_drive($out_of_date_remote, $remote)
    {
        if($remote['type'] == WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES)
        {
            $root_path=apply_filters('wpvivid_get_root_path', $remote['type']);
            $out_of_date_remote = $root_path.$remote['path'];
        }
        return $out_of_date_remote;
    }

    public function wpvivid_storage_provider_one_drive($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES)
        {
            $storage_type = 'OneDrive Shared Drives';
        }
        return $storage_type;
    }
    public function wpvivid_get_root_path_one_drive($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES)
        {
            $storage_type = 'root/';
        }
        return $storage_type;
    }

    public function scan_folder_backup($folder_type)
    {
        set_time_limit(120);
        $ret=array();
        $ret['path']=array();

        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/';
        if($folder_type === 'Common')
        {
            $path=$path.$this->options['path'];
            $response=$this->_scan_folder_backup($path);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                if(isset($response['path']))
                {
                    $ret['path']=$response['path'];
                }
                else
                {
                    $ret['path']=array();
                }
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
            $path=$path.'migrate';
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
        else if($folder_type === 'Rollback'){

            $remote_folder=$path.$this->options['path'].'/rollback';
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

        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'];
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
        if(!empty($path))
            $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.':/children?$select=id,name,folder,size';
        else
            $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root/children?$select=id,name,folder,size';

        $files=array();
        $ret['backup']=array();

        do
        {
            $need_next = false;
            $response=$this->remote_get($url);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['url']=$url;
                $body=$response['body'];
                if(isset($body['value'])){
                    foreach ($body['value'] as $item)
                    {
                        if(isset($item['folder']))
                        {
                            $ret['path'][]=$item['name'];
                            //$ret_child=$this->_scan_child_folder_backup($path,$item['name']);
                            //if($ret_child['result']==WPVIVID_PRO_SUCCESS)
                            //{
                            //   $files= array_merge($files,$ret_child['files']);
                            //}
                        }
                        else
                        {
                            $file_data['file_name']=$item['name'];
                            $file_data['size']=$item['size'];
                            $files[]=$file_data;
                        }
                    }
                }
                if(isset($body['@odata.nextLink'])){
                    $need_next = true;
                    $url = $body['@odata.nextLink'];
                }
            }
            else
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                return $ret;
            }
        }while($need_next == true);

        if($ret['result']=='success')
        {
            if(!empty($files))
            {
                global $wpvivid_backup_pro;
                $ret['backup']=$wpvivid_backup_pro->func->get_backup($files);
            }
            return $ret;
        }
        else{
            return $response;
        }
    }

    public function _scan_child_folder_backup($path,$sub_path)
    {
        if(!empty($path))
            $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.'/'.$sub_path.':/children?$select=id,name,folder,size';
        else
            $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root/children?$select=id,name,folder,size';

        $response=$this->remote_get($url);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            $ret['files']=array();
            $body=$response['body'];

            if(isset($body['value']))
            {
                foreach ($body['value'] as $item)
                {
                    if(isset($item['folder']))
                    {
                        continue;
                    }
                    else
                    {
                        $file_data['file_name']=$item['name'];
                        $file_data['size']=$item['size'];
                        $file_data['remote_path']=$sub_path;
                        $ret['files'][]=$file_data;
                    }
                }
            }

            if(!empty($ret['files']))
            {
                global $wpvivid_backup_pro;
                $ret['backup']=$wpvivid_backup_pro->func->get_backup($ret['files']);
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
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $path=array();

        if($folder_type=='all_backup')
        {
            $ret['result']='success';
            $ret['remote']=array();

            $response=$this->_get_common_backups();
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                if(isset($response['path']))
                {
                    $path=$response['path'];
                }
                else
                {
                    $path=array();
                }
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
                if(isset($response['path']))
                {
                    $path=$response['path'];
                }
                else
                {
                    $path=array();
                }
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
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'];

        return $this->_scan_folder_backup($path);
    }

    public function _get_migrate_backups()
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/migrate';

        return $this->_scan_folder_backup($path);
    }

    public function _get_rollback_backups()
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'].'/rollback';

        return $this->_scan_folder_backup($path);
    }

    public function _get_incremental_backups($incremental_path)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'].'/'.$incremental_path;

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

    public function get_backup_info($backup_info_file,$folder_type,$incremental_path='')
    {
        $this->set_token();
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }


        if($folder_type=='Manual')
        {
            $path=$root_path.'/'.$this->options['path'];
        }
        else if($folder_type=='Migrate')
        {
            $path=$root_path.'/migrate';
        }
        else if($folder_type=='Rollback')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback';
        }
        else if($folder_type=='Incremental')
        {
            $path=$root_path.'/'.$this->options['path'].'/'.$incremental_path;
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.'/'.$backup_info_file.':/content';

        $response=$this->remote_get($url,array(),false,30);

        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup_info']=json_decode($response['body'],1);
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function scan_rollback($type)
    {
        $ret=array();
        $ret['path']=array();

        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'].'/rollback_ex/';

        if($type === 'plugins')
        {
            $path=$path.'plugins';

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
            $path=$path.'themes';

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
        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.$path.':/children?$select=id,name,folder,size';
        $ret['path']=array();

        do{
            $need_next = false;
            $response=$this->remote_get($url);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $ret['url']=$url;
                $body=$response['body'];
                if(isset($body['value']))
                {
                    foreach ($body['value'] as $item)
                    {
                        if(isset($item['folder']))
                        {
                            $ret['path'][]=$item['name'];
                        }
                    }
                }
                if(isset($body['@odata.nextLink']))
                {
                    $need_next = true;
                    $url = $body['@odata.nextLink'];
                }
            }
            else{
                $ret['result']=WPVIVID_PRO_FAILED;
            }
        }while($need_next == true);

        if($ret['result']=="success")
        {
            return $ret;
        }
        else
        {
            return $response;
        }
    }

    public function get_rollback_data($type,$slug)
    {
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'].'/rollback_ex/';

        if($type === 'plugins')
        {
            $path=$path.'plugins/'.$slug;
            $response=$this->_scan_folder($path);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $versions= $response['path'];
                if(!empty($versions))
                {
                    foreach ($versions as $version)
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
            $path=$path.'themes/'.$slug;
            $response=$this->_scan_folder($path);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $versions= $response['path'];
                if(!empty($versions))
                {
                    foreach ($versions as $version)
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
        $url='https://graph.microsoft.com/v1.0/drives/'.$this->options['drive_id'].'/root:/'.dirname($path).':/children?$select=id,name,folder,size,createdDateTime';

        $ret['path']=array();

        $response=$this->remote_get($url);
        if($response['result']==WPVIVID_PRO_SUCCESS)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['url']=$url;
            $body=$response['body'];
            if(isset($body['value']))
            {
                foreach ($body['value'] as $item)
                {
                    if(isset($item['folder']))
                    {
                        continue;
                    }
                    else
                    {
                        if($item['name']==basename($path))
                        {
                            $file_data['file_name']=$item['name'];
                            $file_data['size']=$item['size'];
                            $file_data['mtime']=strtotime($item['createdDateTime']);
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

    public function delete_old_backup($backup_count,$db_count)
    {
        $ret=array();
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/'.$this->options['path'];

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

        $path=$root_path.'/'.$this->options['path'].'/rollback';

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
        if ($this->need_refresh())
        {
            $ret = $this->refresh_token();
            if ($ret['result'] === WPVIVID_PRO_FAILED)
            {
                return false;
            }
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $path=$root_path.'/';
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

    public function finish_add_remote()
    {
        global $wpvivid_backup_pro,$wpvivid_plugin;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-remote');
        try {
            if (empty($_POST) || !isset($_POST['remote']) || !is_string($_POST['remote'])) {
                die();
            }

            $tmp_remote_options = get_transient('shared_onedrive_auth_id');
            if($tmp_remote_options === false)
            {
                die();
            }
            delete_transient('shared_onedrive_auth_id');
            if(empty($tmp_remote_options)||$tmp_remote_options['type']!==WPVIVID_REMOTE_ONEDRIVE_SHARED_DRIVES)
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
        if($this->need_refresh())
        {
            $ret=$this->refresh_token();
            if($ret['result']===WPVIVID_PRO_FAILED)
            {
                return $ret;
            }
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($type=='Rollback')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback';

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
            $path=$root_path.'/'.$this->options['path'];

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
            $path=$root_path.'/'.$this->options['path'];

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
        if ($this->need_refresh())
        {
            $ret = $this->refresh_token();
            if ($ret['result'] === WPVIVID_PRO_FAILED)
            {
                return false;
            }
        }

        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }

        if($type=='Rollback')
        {
            $path=$root_path.'/'.$this->options['path'].'/rollback';

            $response=$this->_scan_folder_backup($path);
            if(isset($response['backup']))
            {
                $backups=$response['backup'];
                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);

                if (!empty($files))
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

            $response=$this->_scan_folder_backup($path);

            if(isset($response['backup']))
            {
                $backups=$response['backup'];

                global $wpvivid_backup_pro;
                $files = $wpvivid_backup_pro->func->get_old_backup_files($backups,$backup_count,$db_count);
                if (!empty($files))
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