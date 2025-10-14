<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: Wpvivid_Google_drive_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_REMOTE_GOOGLEDRIVE'))
    define('WPVIVID_REMOTE_GOOGLEDRIVE','googledrive');
if(!defined('WPVIVID_GOOGLEDRIVE_DEFAULT_FOLDER'))
    define('WPVIVID_GOOGLEDRIVE_DEFAULT_FOLDER','wpvivid_backup');
if(!defined('WPVIVID_GOOGLEDRIVE_UPLOAD_SIZE'))
    define('WPVIVID_GOOGLEDRIVE_UPLOAD_SIZE',1024*1024*2);
if(!defined('WPVIVID_GOOGLE_NEED_PHP_VERSION'))
    define('WPVIVID_GOOGLE_NEED_PHP_VERSION','5.5');



class Wpvivid_Google_drive_addon extends WPvivid_Remote_addon
{
    public $options;

    public $google_drive_secrets;

    public $add_remote;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('WPVIVID_INIT_STORAGE_TAB_GOOGLE_DRIVE'))
            {
                add_action('init', array($this, 'handle_auth_actions'));
                //wpvivid_google_drive_add_remote
                add_action('wp_ajax_wpvivid_google_drive_add_remote',array( $this,'finish_add_remote'));
                add_action('wpvivid_add_storage_page_google_drive', array($this, 'wpvivid_add_storage_page_google_drive'));
                add_action('wpvivid_add_storage_page',array($this,'wpvivid_add_storage_page_google_drive'), 9);
                add_filter('wpvivid_pre_add_remote',array($this, 'pre_add_remote'),10,2);
                add_action('wpvivid_edit_remote_page',array($this,'wpvivid_edit_storage_page_google_drive'), 9);
                add_filter('wpvivid_get_out_of_date_remote',array($this,'wpvivid_get_out_of_date_google_drive'),10,2);
                add_filter('wpvivid_storage_provider_tran',array($this,'wpvivid_storage_provider_google_drive'),10);
                add_filter('wpvivid_get_root_path',array($this,'wpvivid_get_root_path_google_drive'),10);
                add_filter('wpvivid_remote_register', array($this, 'init_remotes'),11);
                define('WPVIVID_INIT_STORAGE_TAB_GOOGLE_DRIVE',1);
            }
        }
        else
        {
            $this->options=$options;
        }
        $this->add_remote=false;
        $this->google_drive_secrets = array("web"=>array(
            "client_id"=>"134809148507-32crusepgace4h6g47ota99jjrvf4j1u.apps.googleusercontent.com",
            "project_id"=>"wpvivid-auth",
            "auth_uri"=>"https://accounts.google.com/o/oauth2/auth",
            "token_uri"=>"https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url"=>"https://www.googleapis.com/oauth2/v1/certs",
            "client_secret"=>"GmD5Kmg_1fTcf0ciNEomposy",
            "redirect_uris"=>array("https://auth.wpvivid.com/google_drive_v2/")
        ));
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection[WPVIVID_REMOTE_GOOGLEDRIVE] = 'Wpvivid_Google_drive_addon';
        return $remote_collection;
    }

    public function pre_add_remote($remote,$id)
    {
        if($remote['type']==WPVIVID_REMOTE_GOOGLEDRIVE)
        {
            $remote['id']=$id;
        }

        return $remote;
    }

    public function handle_auth_actions()
    {
        if (isset($_GET['action']))
        {
            if($_GET['action']=='wpvivid_pro_google_drive_auth')
            {
                if(!apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-remote'))
                {
                    return;
                }

                $rand_id = substr(md5(time().rand()), 0,13);
                $auth_id = 'wpvivid-auth-'.$rand_id;
                $res = $this -> compare_php_version();
                if($res['result'] == WPVIVID_PRO_FAILED){
                    _e('<div class="notice notice-warning is-dismissible"><p>'.$res['error'].'</p></div>');
                    return ;
                }
                try {
                    include_once WPVIVID_PLUGIN_DIR . '/vendor/autoload.php';
                    if(class_exists('WPvivid_Google_Client'))
                    {
                        $client = new WPvivid_Google_Client();
                    }
                    else
                    {
                        $client = new Google_Client();
                    }
                    $client->setAuthConfig($this->google_drive_secrets);
                    $client->setApprovalPrompt('force');
                    if(class_exists('WPvivid_Google_Service_Drive'))
                    {
                        $client->addScope(WPvivid_Google_Service_Drive::DRIVE_FILE);
                    }
                    else
                    {
                        $client->addScope(Google_Service_Drive::DRIVE_FILE);
                    }
                    $client->setAccessType('offline');
                    $client->setState(apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='.sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')).'&action=wpvivid_pro_google_drive_finish_auth&sub_page=cloud_storage_google_drive&auth_id='.$auth_id);
                    $auth_url = $client->createAuthUrl();
                    $remote_options['auth_id']=$auth_id;
                    set_transient('google_drive_auth_id', $remote_options, 900);
                    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
                }
                catch (Exception $e){
                    if($e->getMessage() === 'file does not exist'){
                        $error_msg = 'Authentication failed, the client_secrets.json file is missing. Please make sure the client_secrets.json file is in wpvivid-backuprestore\includes\customclass directory.';
                        _e('<div class="notice notice-error"><p>'.$error_msg.'</p></div>');
                    }
                    else if($e->getMessage() === 'invalid json for auth config'){
                        $error_msg = 'Authentication failed, the format of the client_secrets.json file is incorrect. Please delete and re-install the plugin to recreate the file.';
                        _e('<div class="notice notice-error"><p>'.$error_msg.'</p></div>');
                    }
                    else{
                        _e('<div class="notice notice-error"><p>'.$e->getMessage().'</p></div>');
                    }
                }
            }
            else if($_GET['action']=='wpvivid_pro_google_drive_finish_auth')
            {
                $tmp_options = get_transient('google_drive_auth_id');
                if($tmp_options === false)
                {
                    return;
                }
                else if($tmp_options['auth_id'] !== $_GET['auth_id'])
                {
                    delete_transient('google_drive_auth_id');
                    return;
                }
                try {
                    if(isset($_GET['error']))
                    {
                        header('Location: '.apply_filters('wpvivid_get_admin_url', '').'admin.php?page='.sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')).'&action=wpvivid_pro_google_drive&result=error&resp_msg='.$_GET['error']);

                        return;
                    }
                    $remoteslist = WPvivid_Setting::get_all_remote_options();
                    foreach ($remoteslist as $key => $value)
                    {
                        if (isset($value['auth_id']) && isset($_GET['auth_id']) && $value['auth_id'] == sanitize_text_field($_GET['auth_id']))
                        {
                            _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the Google Drive account as your remote storage.</p></div>');
                            return;
                        }
                    }

                    if(empty($_POST['refresh_token']))
                    {
                        if(empty($tmp_options['token']['refresh_token']))
                        {
                            $err = 'No refresh token was received from Google, which means that you entered client secret incorrectly, or that you did not re-authenticated yet after you corrected it. Please authenticate again.';
                            header('Location: '.admin_url().'admin.php?page='.sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')).'&action=wpvivid_pro_google_drive&result=error&resp_msg='.$err);

                            return;
                        }
                    }
                    else
                    {
                        $tmp_options['type'] = WPVIVID_REMOTE_GOOGLEDRIVE;
                        $tmp_options['token']['access_token'] = base64_encode(sanitize_text_field($_POST['access_token']));
                        $tmp_options['token']['expires_in'] = sanitize_text_field($_POST['expires_in']);
                        $tmp_options['token']['refresh_token'] = base64_encode(sanitize_text_field($_POST['refresh_token']));
                        $tmp_options['token']['scope'] = sanitize_text_field($_POST['scope']);
                        $tmp_options['token']['token_type'] = sanitize_text_field($_POST['token_type']);
                        $tmp_options['token']['created'] = sanitize_text_field($_POST['created']);
                        $tmp_options['is_encrypt'] = 1;
                        set_transient('google_drive_auth_id', $tmp_options, 900);
                    }
                    $this->add_remote=true;
                }
                catch (Exception $e){
                    _e('<div class="notice notice-error"><p>'.$e->getMessage().'</p></div>');
                }
            }
            else if($_GET['action']=='wpvivid_pro_google_drive')
            {
                try {
                    if (isset($_GET['result'])) {
                        if ($_GET['result'] == 'success') {
                            //add_action('show_notice', array($this, 'wpvivid_show_notice_add_google_drive_success'));
                            _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the Google Drive account as your remote storage.</p></div>');
                        } else if ($_GET['result'] == 'error') {
                            //add_action('show_notice', array($this, 'wpvivid_show_notice_add_google_drive_error'));
                            global $wpvivid_plugin;
                            $wpvivid_plugin->wpvivid_handle_remote_storage_error($_GET['resp_msg'], 'Add Google Drive Remote');
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
    public function wpvivid_show_notice_add_google_drive_success(){
        _e('<div class="notice notice-success is-dismissible"><p>You have authenticated the Google Drive account as your remote storage.</p></div>');
    }
    public function wpvivid_show_notice_add_google_drive_error(){
        global $wpvivid_plugin;
        $wpvivid_plugin->wpvivid_handle_remote_storage_error($_GET['resp_msg'], 'Add Google Drive Remote');
        _e('<div class="notice notice-error"><p>'.$_GET['resp_msg'].'</p></div>');
    }

    public function wpvivid_add_storage_page_google_drive()
    {
        global $wpvivid_backup_pro;
        if($this->add_remote)
        {
            ?>
            <div id="storage_account_google_drive" class="storage-account-page">
                <div style="color:#8bc34a; padding: 0 10px 10px 0;">
                    <strong>Authentication is done, please continue to enter the storage information, then click 'Add Now' button to save it.</strong>
                </div>
                <div style="padding: 0 10px 10px 0;">
                    <strong>Enter Your Google Drive Information</strong>
                </div>
                <table class="wp-list-table widefat plugins" style="width:100%;">
                    <tbody>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="googledrive" name="name" placeholder="Enter a unique alias: e.g. Google Drive-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" autocomplete="off" option="googledrive" name="root_path" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i><?php echo sprintf(__('Customize a root directory in your Google Drive for holding %s directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="googledrive" name="path" placeholder="Google Drive Folder" value="<?php esc_attr_e($wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url())); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_/]/g,'')" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Customize the directory where you want to store backups. By default it takes your current website domain or url.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="googledrive" name="chunk_size" placeholder="Chunk size" value="2" onkeyup="value=value.replace(/\D/g,'')" />MB
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>The block size of downloads. Reduce it if you encounter a timeout when transferring files.</i>
                            </div>
                        </td>
                    </tr>

                    <!--<tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="googledrive" name="backup_retain" value="30" />
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
                                <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="googledrive" name="backup_db_retain" value="30" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Total number of database backup copies to be retained in this storage.</i>
                            </div>
                        </td>
                    </tr>-->
                    <?php do_action('wpvivid_remote_storage_backup_retention', 'googledrive', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="googledrive" name="default" checked />Set as the default remote storage.
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
                                <input id="wpvivid_google_drive_auth" class="button-primary" type="submit" value="Add Now" />
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
                function wpvivid_check_google_drive_storage_alias(storage_alias)
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

                jQuery('#wpvivid_google_drive_auth').click(function()
                {
                    wpvivid_google_drive_auth();
                });

                function wpvivid_google_drive_auth()
                {
                    wpvivid_settings_changed = false;
                    var name='';
                    var path='';
                    var root_path='';
                    var chunk_size='';
                    var backup_retain='';
                    var backup_db_retain='';
                    var backup_incremental_retain='';
                    var backup_rollback_retain='';
                    jQuery('input:text[option=googledrive]').each(function()
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

                    var remote_default='0';

                    jQuery('input:checkbox[option=googledrive][name=default]').each(function()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            remote_default='1';
                        }
                        else
                        {
                            remote_default='0';
                        }
                    });

                    var use_remote_retention = '0';
                    jQuery('input:checkbox[option=googledrive][name=use_remote_retention]').each(function()
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
                    else if(wpvivid_check_google_drive_storage_alias(name) === false)
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
                    else {
                        var ajax_data;
                        var remote_from = wpvivid_ajax_data_transfer('googledrive');
                        ajax_data = {
                            'action': 'wpvivid_google_drive_add_remote',
                            'remote': remote_from
                        };
                        jQuery('#wpvivid_google_drive_auth').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#wpvivid_remote_notice').html('');
                        wpvivid_post_request_addon(ajax_data, function (data)
                        {
                            try
                            {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success')
                                {

                                    jQuery('input:text[option=googledrive]').each(function(){
                                        jQuery(this).val('');
                                    });
                                    jQuery('input:password[option=googledrive]').each(function(){
                                        jQuery(this).val('');
                                    });
                                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote').'&action=wpvivid_pro_google_drive&result=success'; ?>';
                                }
                                else if (jsonarray.result === 'failed')
                                {
                                    jQuery('#wpvivid_remote_notice').html(jsonarray.notice);
                                    jQuery('#wpvivid_google_drive_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('html, body').animate({scrollTop:top}, 'slow');
                                }
                            }
                            catch (err)
                            {
                                alert(err);
                                jQuery('#wpvivid_google_drive_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                            }

                        }, function (XMLHttpRequest, textStatus, errorThrown)
                        {
                            var error_message = wpvivid_output_ajaxerror('adding the remote storage', textStatus, errorThrown);
                            alert(error_message);
                            jQuery('#wpvivid_google_drive_auth').css({'pointer-events': 'auto', 'opacity': '1'});
                        });
                    }
                }

                jQuery('input:text[option=googledrive][name=chunk_size]').on("keyup", function(){
                    var regExp = /^([1-9]|1[0-9]|2[0-9]|30)$/g;
                    var input_value = jQuery('input:text[option=googledrive][name=chunk_size]').val();
                    if(!regExp.test(input_value) && input_value !== ''){
                        alert('Only a number from 1-30 is allowed.');
                        jQuery('input:text[option=googledrive][name=chunk_size]').val('');
                    }
                });
            </script>
            <?php
        }
        else
        {
            ?>
            <div id="storage_account_google_drive" class="storage-account-page">
                <div style="padding: 0 10px 10px 0;">
                    <strong>To add Google Drive, please get Google authentication first. Once authenticated, you will be redirected to this page, then you can add storage information and save it</strong>
                </div>
                <table class="wp-list-table widefat plugins" style="width:100%;">
                    <tbody>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="wpvivid-storage-form">
                                <input onclick="wpvivid_google_drive_auth();" class="button-primary" type="submit" value="Authenticate with Google Drive" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="wpvivid-storage-form-desc">
                                <i>Click to get Google authentication.</i>
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
                function wpvivid_google_drive_auth()
                {
                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote').'&action=wpvivid_pro_google_drive_auth'; ?>';
                }
            </script>
            <?php
        }
    }

    public function wpvivid_edit_storage_page_google_drive()
    {
        ?>
        <div id="remote_storage_edit_googledrive" >
            <div style="padding: 0 10px 10px 0;">
                <strong>Enter Your Google Drive Information</strong>
            </div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-googledrive" name="name" placeholder="Enter a unique alias: e.g. Google Drive-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                            <input type="text" class="regular-text" value="<?php esc_attr_e(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" autocomplete="off" option="edit-googledrive" name="root_path" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i><?php echo sprintf(__('Customize a root directory in your Google Drive for holding %s directories.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid backup')); ?></i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-googledrive" name="path" placeholder="Google Drive Folder" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_/]/g,'')" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Customize the directory where you want to store backups. By default it takes your current website domain or url.</i>
                            <!--<i><span style="padding: 0; margin: 0;">Specify a name for the folder where you want to store backups. Google Drive Folder:</span><span option="googledrive" name="path">
                                <?php
                                $root_path=apply_filters('wpvivid_get_root_path', WPVIVID_REMOTE_GOOGLEDRIVE);
                                _e($root_path.WPVIVID_GOOGLEDRIVE_DEFAULT_FOLDER);
                                ?>
                            </span></i>-->
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-googledrive" name="chunk_size" placeholder="Chunk size" onkeyup="value=value.replace(/\D/g,'')" />MB
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>The block size of downloads. Reduce it if you encounter a timeout when transferring files.</i>
                        </div>
                    </td>
                </tr>

                <!--<tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="edit-googledrive" name="backup_retain" value="30" />
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
                            <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="edit-googledrive" name="backup_db_retain" value="30" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="wpvivid-storage-form-desc">
                            <i>Total number of database backup copies to be retained in this storage.</i>
                        </div>
                    </td>
                </tr>-->
                <?php do_action('wpvivid_remote_storage_backup_retention', 'googledrive', 'edit'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="wpvivid-storage-form">
                            <input onclick="wpvivid_google_drive_update_auth();" class="button-primary" type="submit" value="Save Changes" />
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
            function wpvivid_google_drive_update_auth()
            {
                var name='';
                var path='';
                var root_path='';
                var chunk_size='';
                var backup_retain='';
                var backup_db_retain='';
                var backup_incremental_retain='';
                var backup_rollback_retain='';
                jQuery('input:text[option=edit-googledrive]').each(function()
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
                jQuery('input:checkbox[option=edit-googledrive][name=use_remote_retention]').each(function()
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
                else {
                    wpvivid_edit_remote_storage();
                }
            }

            jQuery('input:text[option=edit-googledrive][name=chunk_size]').on("keyup", function(){
                var regExp = /^([1-9]|1[0-9]|2[0-9]|30)$/g;
                var input_value = jQuery('input:text[option=edit-googledrive][name=chunk_size]').val();
                if(!regExp.test(input_value) && input_value !== ''){
                    alert('Only a number from 1-30 is allowed.');
                    jQuery('input:text[option=edit-googledrive][name=chunk_size]').val('');
                }
            });
        </script>
        <?php
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

    public function test_connect()
    {
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function upload($task_id, $files, $callback = '')
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $client=$this->get_client();
        if($client['result'] == WPVIVID_PRO_FAILED){
            return $client;
        }
        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check upload folder '.$path,'notice');
        $folder_id=$this->get_folder($service,$path);

        if($folder_id==false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
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
            $retry_times = 0;
            $result=$this->_upload($task_id, $file,$client,$service,$folder_id, $callback, $retry_times);
            if($result['result'] !==WPVIVID_PRO_SUCCESS)
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload failed, result'.json_encode($result),'notice');
                return $result;
            }
            else
            {
                WPvivid_Custom_Interface_addon::wpvivid_reset_backup_retry_times($task_id);
            }

            $ref=$this->check_token($client, $service);
            if($ref['result']=!WPVIVID_PRO_SUCCESS)
            {
                return $ref;
            }
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function _upload($task_id, $file,$client,$service,$folder_id, $callback = '', $retry_times=0)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check if the server already has the same name file.','notice');
        if(!$this->delete_exist_file($folder_id,$file,$service))
        {
            return array('result' =>WPVIVID_PRO_FAILED,'error'=>'Uploading '.$file.' to Google Drive server failed. '.$file.' might be deleted or network doesn\'t work properly . Please verify the file and confirm the network connection and try again later.');
        }

        $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$this->options['id']);
        $this -> current_file_size = filesize($file);
        $this -> current_file_name = basename($file);

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Initiate a resumable upload session.','notice');
        if(class_exists('WPvivid_Google_Service_Drive_DriveFile'))
        {
            $fileMetadata = new WPvivid_Google_Service_Drive_DriveFile(array(
                'name' => basename($file),
                'parents' => array($folder_id)));
        }
        else
        {
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name' => basename($file),
                'parents' => array($folder_id)));
        }
        $chunk_size = 1 * 1024 * 1024;
        $client->setDefer(true);
        $request = $service->files->create($fileMetadata);
        if(class_exists('WPvivid_Google_Http_MediaFileUpload'))
        {
            $media = new WPvivid_Google_Http_MediaFileUpload(
                $client,
                $request,
                'text/plain',
                null,
                true,
                $chunk_size
            );
        }
        else
        {
            $media = new Google_Http_MediaFileUpload(
                $client,
                $request,
                'text/plain',
                null,
                true,
                $chunk_size
            );
        }
        $media->setFileSize(filesize($file));
        $status = false;
        $handle = fopen($file, "rb");

        WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_UNDO,'Start uploading '.basename($file).'.',$upload_job['job_data']);

        $offset=0;

        try{
            while (!$status && !feof($handle))
            {
                $chunk = fread($handle, $chunk_size);
                $status = $media->nextChunk($chunk);
                $offset+=strlen($chunk);
                $retry_times=0;

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

            fclose($handle);
            $client->setDefer(false);
            if ($status != false)
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finished uploading '.basename($file),'notice');
                $upload_job['job_data'][basename($file)]['uploaded']=1;
                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$this->options['id'],WPVIVID_UPLOAD_SUCCESS,'Uploading '.basename($file).' completed.',$upload_job['job_data']);
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload success.','notice');
                return array('result' =>WPVIVID_PRO_SUCCESS);
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload failed.','notice');
                return array('result' =>WPVIVID_PRO_FAILED,'error'=>'Uploading '.$file.' to Google Drive server failed. '.$file.' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
        }
        catch (WPvivid_Google_Service_Exception $e)
        {
            $retry_times++;
            fclose($handle);
            $client->setDefer(false);
            $message = 'A exception ('.get_class($e).') occurred '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().') ';
            if($retry_times < 15)
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload Google_Service_Exception, '.$message.', retry times: '.$retry_times,'notice');
                return $this->_upload($task_id, $file,$client,$service,$folder_id, $callback, $retry_times);
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload Google_Service_Exception, retry times: '.$retry_times,'notice');
                return array('result' =>WPVIVID_PRO_FAILED,'error'=>$message);
            }
        }
        catch (Google_Service_Exception $e)
        {
            $retry_times++;
            fclose($handle);
            $client->setDefer(false);
            $message = 'A exception ('.get_class($e).') occurred '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().') ';
            if($retry_times < 15)
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload Google_Service_Exception, '.$message.', retry times: '.$retry_times,'notice');
                return $this->_upload($task_id, $file,$client,$service,$folder_id, $callback, $retry_times);
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload Google_Service_Exception, retry times: '.$retry_times,'notice');
                return array('result' =>WPVIVID_PRO_FAILED,'error'=>$message);
            }
        }
    }

    public function check_token(&$client, &$service)
    {
        if ($client->isAccessTokenExpired())
        {
            // Refresh the token if possible, else fetch a new one.
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Refresh the token.','notice');
            if ($client->getRefreshToken())
            {
                $tmp_refresh_token = $client->getRefreshToken();
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $token=$client->getAccessToken();

                if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
                    $tmp_refresh_token = base64_decode($tmp_refresh_token);
                }

                /*
                $args = array(
                    'refresh_token' => $tmp_refresh_token
                );

                $result = wp_remote_post("https://auth.wpvivid.com/google_drive_v2/", array(
                    'timeout' => 60,
                    'body' => $args
                ));

                if (is_wp_error($result))
                {
                    return array('result' => WPVIVID_PRO_SUCCESS,'data' => false);
                }
                else
                {
                    $token = wp_remote_retrieve_body($result);
                    $token = json_decode($token, true);
                    if(!is_null($token))
                    {
                        $client->setAccessToken($token);
                    }
                    else
                    {
                        return array('result' => WPVIVID_PRO_SUCCESS,'data' => false);
                    }
                }
                */

                $remote_options=WPvivid_Setting::get_remote_option($this->options['id']);
                $this->options['token']=json_decode(json_encode($token),1);
                $this->options['token']['access_token']=base64_encode($this->options['token']['access_token']);
                $this->options['is_encrypt']=1;
                if($remote_options!==false)
                {
                    if(!isset($this->options['token']['refresh_token'])){
                        $this->options['token']['refresh_token'] = base64_encode($tmp_refresh_token);
                    }
                    else{
                        $this->options['token']['refresh_token']=base64_encode($this->options['token']['refresh_token']);
                    }
                    $remote_options['token']=$this->options['token'];
                    $remote_options['is_encrypt']=1;
                    WPvivid_Setting::update_remote_option($this->options['id'],$remote_options);

                    $client=$this->get_client();
                    if($client['result'] == WPVIVID_PRO_FAILED){
                        return $client;
                    }
                    $client = $client['data'];

                    if($client===false)
                    {
                        return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
                    }
                    if(class_exists('WPvivid_Google_Service_Drive'))
                    {
                        $service = new WPvivid_Google_Service_Drive($client);
                    }
                    else
                    {
                        $service = new Google_Service_Drive($client);
                    }
                }
                return array('result' => WPVIVID_PRO_SUCCESS);
            }
            else
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=>'get refresh token failed');
            }
        }
        else
        {
            return array('result' => WPVIVID_PRO_SUCCESS);
        }
    }

    public function get_client()
    {
        $res = $this -> compare_php_version();
        if($res['result'] == WPVIVID_PRO_FAILED){
            return $res;
        }

        $token=$this->options['token'];
        if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
            $token['access_token'] = base64_decode($this->options['token']['access_token']);
            $token['refresh_token'] = base64_decode($this->options['token']['refresh_token']);
        }

        include_once WPVIVID_PLUGIN_DIR.'/vendor/autoload.php';
        if(class_exists('WPvivid_Google_Client'))
        {
            $client = new WPvivid_Google_Client();
        }
        else
        {
            $client = new Google_Client();
        }
        $client->setConfig('access_type','offline');
        $client->setAuthConfig($this->google_drive_secrets);
        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $client->addScope(WPvivid_Google_Service_Drive::DRIVE_FILE);//
        }
        else
        {
            $client->addScope(Google_Service_Drive::DRIVE_FILE);//
        }
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired())
        {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken())
            {
                $tmp_refresh_token = $client->getRefreshToken();
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $token=$client->getAccessToken();

                if(isset($this->options['is_encrypt']) && $this->options['is_encrypt'] == 1) {
                    $tmp_refresh_token = base64_decode($tmp_refresh_token);
                }

                /*
                $args = array(
                    'refresh_token' => $tmp_refresh_token
                );

                $result = wp_remote_post("https://auth.wpvivid.com/google_drive_v2/", array(
                    'timeout' => 60,
                    'body' => $args
                ));

                if (is_wp_error($result))
                {
                    return array('result' => WPVIVID_PRO_SUCCESS,'data' => false);
                }
                else
                {
                    $token = wp_remote_retrieve_body($result);
                    $token = json_decode($token, true);
                    if(!is_null($token))
                    {
                        $client->setAccessToken($token);
                    }
                    else
                    {
                        return array('result' => WPVIVID_PRO_SUCCESS,'data' => false);
                    }
                }
                */

                $remote_options=WPvivid_Setting::get_remote_option($this->options['id']);
                $this->options['token']=json_decode(json_encode($token),1);
                $this->options['token']['access_token']=base64_encode($this->options['token']['access_token']);
                $this->options['is_encrypt']=1;
                if($remote_options!==false)
                {
                    if(!isset($this->options['token']['refresh_token'])){
                        $this->options['token']['refresh_token'] = base64_encode($tmp_refresh_token);
                    }
                    else{
                        $this->options['token']['refresh_token']=base64_encode($this->options['token']['refresh_token']);
                    }
                    $remote_options['token']=$this->options['token'];
                    $remote_options['is_encrypt']=1;
                    WPvivid_Setting::update_remote_option($this->options['id'],$remote_options);
                }
                return array('result' => WPVIVID_PRO_SUCCESS,'data' => $client);
            }
            else
            {
                return array('result' => WPVIVID_PRO_SUCCESS,'data' => false);
            }
        }
        else
        {
            return array('result' => WPVIVID_PRO_SUCCESS,'data' => $client);
        }
    }

    private function get_folder($service,$path)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $wpvivid_root_folder_id=$this->get_folder_id($service,$root_path);
        if($wpvivid_root_folder_id===false)
        {
            return false;
        }

        if(dirname($path)=='.')
        {
            return $this->get_folder_id($service,$path,$wpvivid_root_folder_id);
        }
        else
        {
            $custom_path=dirname($path);
            $custom_path2=basename($path);
            $custom_path_id=$this->get_folder_id($service,$custom_path,$wpvivid_root_folder_id);
            if($custom_path_id===false)
            {
                return false;
            }
           return $this->get_folder_id($service,$custom_path2,$custom_path_id);
        }
    }

    private function get_folder_id($service,$path,$root_id='root')
    {
        $response = $service->files->listFiles(array(
            'q' => "name ='$path' and '$root_id' in parents and mimeType = 'application/vnd.google-apps.folder'",
            'fields' => 'nextPageToken, files(id, name,mimeType)',
        ));

        $folder_id='';

        if(sizeof($response->getFiles())==0)
        {
            $option['name']=$path;
            $option['mimeType']= 'application/vnd.google-apps.folder';
            if($root_id!='root')
            {
                $option['parents']=array($root_id);
            }
            if(class_exists('WPvivid_Google_Service_Drive_DriveFile'))
            {
                $fileMetadata = new WPvivid_Google_Service_Drive_DriveFile($option);
            }
            else
            {
                $fileMetadata = new Google_Service_Drive_DriveFile($option);
            }
            $file = $service->files->create($fileMetadata, array(
                'fields' => 'id'));
            $folder_id=$file->id;
        }
        else
        {
            foreach ($response->getFiles() as $file)
            {
                $folder_id=$file->getId();
                break;
            }
        }

        if(empty($folder_id))
            return false;
        else
            return $folder_id;
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

    public function download( $file, $local_path, $callback = '')
    {
        try
        {
            global $wpvivid_plugin;
            $this -> current_file_name = $file['file_name'];
            $this -> current_file_size = $file['size'];
            $this->set_token();
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Google Drive get client.','notice');
            $client=$this->get_client();
            if($client['result'] == WPVIVID_PRO_FAILED)
                return $client;
            $client = $client['data'];

            if($client===false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
            }

            if(class_exists('WPvivid_Google_Service_Drive'))
            {
                $service = new WPvivid_Google_Service_Drive($client);
            }
            else
            {
                $service = new Google_Service_Drive($client);
            }

            if(isset($file['remote_path']))
            {
                $path=$this->options['path'].'/'.$file['remote_path'];
            }
            else
            {
                $path=$this->options['path'];
            }

            $wpvivid_plugin->wpvivid_download_log->WriteLog('Create local file.','notice');
            $folder_id=$this->get_folder($service,$path);

            if($folder_id==false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
            }

            $response = $service->files->listFiles(array(
                'q' => "name='".$file['file_name']."' and '".$folder_id."' in parents",
                'fields' => 'files(id,size,webContentLink)'
            ));

            if(sizeof($response->getFiles())==0)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
            else
            {
                $fileSize=$file['size'];
                $file_id='';
                foreach ($response->getFiles() as $file)
                {
                    $file_id=$file->getId();
                    break;
                }
                $wpvivid_plugin->wpvivid_download_log->WriteLog('Get download url.','notice');
                $download_url=$this->get_download_url($client,$file_id);

                if(!empty($file_id)||!empty($download_url))
                {
                    $file_path = trailingslashit($local_path).$this -> current_file_name;

                    if(file_exists($file_path))
                    {
                        $offset = filesize($file_path);
                    }
                    else
                    {
                        $offset=0;
                    }

                    $fh = fopen($file_path, 'a');
                    $upload_size = isset($this->options['chunk_size']) ? $this->options['chunk_size'] : WPVIVID_GOOGLEDRIVE_UPLOAD_SIZE;
                    $http = $client->authorize();
                    $wpvivid_plugin->wpvivid_download_log->WriteLog('Downloading file ' . $file['file_name'] . ', Size: ' . $file['size'] ,'notice');
                    while ($offset < $fileSize)
                    {
                        $upload_end=min($offset+$upload_size-1,$fileSize-1);

                        if ($offset > 0)
                        {
                            $options['headers']['Range']='bytes='.$offset.'-'.$upload_end;
                        } else {
                            $options['headers']['Range']='bytes=0-'.$upload_end;
                        }

                        if ( class_exists( 'WPvividGuzzleHttp\Psr7\Request' ) )
                        {
                            $request = new WPvividGuzzleHttp\Psr7\Request('GET', $download_url,$options['headers']);
                        }
                        else{
                            $request = new GuzzleHttp\Psr7\Request('GET', $download_url,$options['headers']);
                        }

                        $http_request = $http->send($request);
                        $http_response=$http_request->getStatusCode();
                        if (200 == $http_response || 206 == $http_response)
                        {
                            fwrite($fh, $http_request->getBody()->getContents(),$upload_size);
                            $offset=$upload_end + 1;
                        }
                        else
                        {
                            throw new Exception('Failed to obtain any new data at size: '.$offset.' http code:'.$http_response);
                        }

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
                    fclose($fh);
                }
                else
                {
                    return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
                }
            }
        }catch(Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
        }

        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function get_download_url($client,$file_id)
    {
        $http = $client->authorize();
        $url='https://www.googleapis.com/drive/v2/files/'.$file_id;

        if ( class_exists( 'WPvividGuzzleHttp\Psr7\Request' ) )
        {
            $request = new WPvividGuzzleHttp\Psr7\Request('GET', $url);
        }
        else{
            $request = new GuzzleHttp\Psr7\Request('GET', $url);
        }

        $http_request = $http->send($request);

        $http_response=$http_request->getStatusCode();
        if (200 == $http_response)
        {
            $json=$http_request->getBody()->getContents();
            $json=json_decode($json,1);
            $download_url=$json['downloadUrl'];
            return $download_url;
        }
        else
        {
            throw new Exception('Failed to use v2 api');
        }
    }

    public function delete_exist_file($folder_id,$file,$service)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $client=$this->get_client();
        if($client['result'] == WPVIVID_PRO_FAILED)
            return false;
        $client = $client['data'];

        if($client===false)
        {
            return false;
        }

        try{
            $delete_files = $service->files->listFiles(array(
                'q' => "name='".basename($file)."' and '".$folder_id."' in parents",
                'fields' => 'nextPageToken, files(id, name,mimeType)',
            ));

            if(sizeof($delete_files->getFiles())==0)
            {
                return true;
            }
            else
            {
                foreach ($delete_files->getFiles() as $file_google_drive)
                {
                    $file_id=$file_google_drive->getId();
                    $service->files->delete($file_id);
                    return true;
                }
            }
        }
        catch(Exception $error)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('listFiles exception.','notice');
            return true;
        }

        return false;
    }

    public function chunk_download($download_info,$callback)
    {
        $this -> current_file_name = $download_info['file_name'];
        $this -> current_file_size = $download_info['size'];

        $local_path = $download_info['local_path'];
        $path=$this->options['path'];

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

        try
        {
            $this->set_token();
            $client=$this->get_client();
            if($client['result'] == WPVIVID_PRO_FAILED)
                return $client;
            $client = $client['data'];

            if($client===false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
            }

            if(class_exists('WPvivid_Google_Service_Drive'))
            {
                $service = new WPvivid_Google_Service_Drive($client);
            }
            else
            {
                $service = new Google_Service_Drive($client);
            }

            $folder_id=$this->get_folder($service,$path);

            if($folder_id==false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
            }

            $response = $service->files->listFiles(array(
                'q' => "name='".$this -> current_file_name."' and '".$folder_id."' in parents",
                'fields' => 'files(id,size,webContentLink)'
            ));

            if(sizeof($response->getFiles())==0)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
            else
            {
                $fileSize=$this -> current_file_size;
                $file_id='';
                foreach ($response->getFiles() as $file)
                {
                    $file_id=$file->getId();
                    break;
                }

                $download_url=$this->get_download_url($client,$file_id);

                if(!empty($file_id)||!empty($download_url))
                {
                    $upload_size = isset($this->options['chunk_size']) ? $this->options['chunk_size'] : WPVIVID_GOOGLEDRIVE_UPLOAD_SIZE;
                    $http = $client->authorize();

                    while ($offset < $fileSize)
                    {
                        $upload_end=min($offset+$upload_size-1,$fileSize-1);

                        if ($offset > 0)
                        {
                            $options['headers']['Range']='bytes='.$offset.'-'.$upload_end;
                        } else {
                            $options['headers']['Range']='bytes=0-'.$upload_end;
                        }

                        if ( class_exists( 'WPvividGuzzleHttp\Psr7\Request' ) )
                        {
                            $request = new WPvividGuzzleHttp\Psr7\Request('GET', $download_url,$options['headers']);
                        }
                        else{
                            $request = new GuzzleHttp\Psr7\Request('GET', $download_url,$options['headers']);
                        }

                        $http_request = $http->send($request);
                        $http_response=$http_request->getStatusCode();
                        if (200 == $http_response || 206 == $http_response)
                        {
                            fwrite($fh, $http_request->getBody()->getContents(),$upload_size);
                            $offset=$upload_end + 1;
                        }
                        else
                        {
                            throw new Exception('Failed to obtain any new data at size: '.$offset.' http code:'.$http_response);
                        }

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

                        $time_taken = microtime(true) - $start_time;
                        if($time_taken >= $time_limit)
                        {
                            @fclose($fh);
                            $result['result']='success';
                            $result['finished']=0;
                            $result['offset']=$offset;
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
                else
                {
                    return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
                }
            }
        }
        catch(Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
        }
    }

    private function get_rollback_folder($service,$path,$folder,$slug,$version)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $wpvivid_root_folder_id=$this->get_folder_id($service,$root_path);
        if($wpvivid_root_folder_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,$path,$wpvivid_root_folder_id);
        if($path_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,"rollback_ex",$path_id);
        if($path_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,$folder,$path_id);
        if($path_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,$slug,$path_id);
        if($path_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,$version,$path_id);
        if($path_id===false)
        {
            return false;
        }

        return $path_id;
    }

    public function upload_rollback($file,$folder,$slug,$version)
    {
        global $wpvivid_plugin;

        $client=$this->get_client();
        if($client['result'] == WPVIVID_PRO_FAILED){
            return $client;
        }
        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];
        $folder_id=$this->get_rollback_folder($service,$path,$folder,$slug,$version);

        if($folder_id==false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
        }

        if(class_exists('WPvivid_Google_Service_Drive_DriveFile'))
        {
            $fileMetadata = new WPvivid_Google_Service_Drive_DriveFile(array(
                'name' => basename($file),
                'parents' => array($folder_id)));
        }
        else
        {
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name' => basename($file),
                'parents' => array($folder_id)));
        }
        $chunk_size = 1 * 1024 * 1024;
        $client->setDefer(true);
        $request = $service->files->create($fileMetadata);
        if(class_exists('WPvivid_Google_Http_MediaFileUpload'))
        {
            $media = new WPvivid_Google_Http_MediaFileUpload(
                $client,
                $request,
                'text/plain',
                null,
                true,
                $chunk_size
            );
        }
        else
        {
            $media = new Google_Http_MediaFileUpload(
                $client,
                $request,
                'text/plain',
                null,
                true,
                $chunk_size
            );
        }
        $media->setFileSize(filesize($file));
        $status = false;
        $handle = fopen($file, "rb");

        $offset=0;

        try
        {
            while (!$status && !feof($handle))
            {
                $chunk = fread($handle, $chunk_size);
                $status = $media->nextChunk($chunk);
                $offset+=strlen($chunk);
            }

            fclose($handle);
            $client->setDefer(false);
            if ($status != false)
            {
                return array('result' =>'success');
            }
            else
            {
                return array('result' =>'failed','error'=>'Uploading '.$file.' to Google Drive server failed. '.$file.' might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
        }
        catch (WPvivid_Google_Service_Exception $e)
        {
            fclose($handle);
            $client->setDefer(false);
            $message = 'A exception ('.get_class($e).') occurred '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().') ';
            return array('result' =>'failed','error'=>$message);
        }
        catch (Google_Service_Exception $e)
        {
            fclose($handle);
            $client->setDefer(false);
            $message = 'A exception ('.get_class($e).') occurred '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().') ';
            return array('result' =>'failed','error'=>$message);
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
        $path=$this->options['path'];

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

        try
        {
            $this->set_token();
            $client=$this->get_client();
            if($client['result'] == WPVIVID_PRO_FAILED)
                return $client;
            $client = $client['data'];

            if($client===false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
            }

            if(class_exists('WPvivid_Google_Service_Drive'))
            {
                $service = new WPvivid_Google_Service_Drive($client);
            }
            else
            {
                $service = new Google_Service_Drive($client);
            }

            $folder_id=$this->get_rollback_folder($service,$path,$type,$slug,$version);

            if($folder_id==false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
            }

            $response = $service->files->listFiles(array(
                'q' => "name='".$this -> current_file_name."' and '".$folder_id."' in parents",
                'fields' => 'files(id,size,webContentLink)'
            ));

            if(sizeof($response->getFiles())==0)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
            else
            {
                $fileSize=$this -> current_file_size;
                $file_id='';
                foreach ($response->getFiles() as $file)
                {
                    $file_id=$file->getId();
                    break;
                }

                $download_url=$this->get_download_url($client,$file_id);

                if(!empty($file_id)||!empty($download_url))
                {
                    $upload_size = isset($this->options['chunk_size']) ? $this->options['chunk_size'] : WPVIVID_GOOGLEDRIVE_UPLOAD_SIZE;
                    $http = $client->authorize();

                    while ($offset < $fileSize)
                    {
                        $upload_end=min($offset+$upload_size-1,$fileSize-1);

                        if ($offset > 0)
                        {
                            $options['headers']['Range']='bytes='.$offset.'-'.$upload_end;
                        } else {
                            $options['headers']['Range']='bytes=0-'.$upload_end;
                        }

                        if ( class_exists( 'WPvividGuzzleHttp\Psr7\Request' ) )
                        {
                            $request = new WPvividGuzzleHttp\Psr7\Request('GET', $download_url,$options['headers']);
                        }
                        else{
                            $request = new GuzzleHttp\Psr7\Request('GET', $download_url,$options['headers']);
                        }

                        $http_request = $http->send($request);
                        $http_response=$http_request->getStatusCode();
                        if (200 == $http_response || 206 == $http_response)
                        {
                            fwrite($fh, $http_request->getBody()->getContents(),$upload_size);
                            $offset=$upload_end + 1;
                        }
                        else
                        {
                            throw new Exception('Failed to obtain any new data at size: '.$offset.' http code:'.$http_response);
                        }


                        $time_taken = microtime(true) - $start_time;
                        if($time_taken >= $time_limit)
                        {
                            @fclose($fh);
                            $result['result']='success';
                            $result['finished']=0;
                            $result['offset']=$offset;
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
                else
                {
                    return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
                }
            }
        }
        catch(Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
        }
    }

    public function cleanup($files)
    {
        set_time_limit(120);

        $client=$this->get_client();
        if($client['result'] == WPVIVID_PRO_FAILED)
            return $client;
        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];
        $folder_id=$this->get_folder($service,$path);

        if($folder_id==false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
        }

        foreach ($files as $file)
        {
            $file_folder_id=$folder_id;
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $file_folder_id=$this->get_folder_id($service,$file['remote_path'],$folder_id);
                }
                $file_name=$file['file_name'];
            }
            else
            {
                $file_name=$file;
            }

            $delete_files = $service->files->listFiles(array(
                'q' => "name='".$file_name."' and '".$file_folder_id."' in parents",
                'fields' => 'nextPageToken, files(id, name,mimeType)',
            ));

            if(sizeof($delete_files->getFiles())==0)
            {
                continue;
            }
            else
            {
                foreach ($delete_files->getFiles() as $file_google_drive)
                {
                    $file_id=$file_google_drive->getId();
                    $service->files->delete($file_id);
                }
            }
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback_db($files)
    {
        set_time_limit(120);

        $client=$this->get_client();
        if($client['result'] == WPVIVID_PRO_FAILED)
            return $client;
        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'].'/rollback';
        $folder_id=$this->get_folder($service,$path);
        if($folder_id==false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
        }

        foreach ($files as $file)
        {
            $file_folder_id=$folder_id;
            if(is_array($file))
            {
                if(isset($file['remote_path']))
                {
                    $file_folder_id=$this->get_folder_id($service,$file['remote_path'],$folder_id);
                }
                $file_name=$file['file_name'];
            }
            else
            {
                $file_name=$file;
            }

            $delete_files = $service->files->listFiles(array(
                'q' => "name='".$file_name."' and '".$file_folder_id."' in parents",
                'fields' => 'nextPageToken, files(id, name,mimeType)',
            ));

            if(sizeof($delete_files->getFiles())==0)
            {
                continue;
            }
            else
            {
                foreach ($delete_files->getFiles() as $file_google_drive)
                {
                    $file_id=$file_google_drive->getId();
                    $service->files->delete($file_id);
                }
            }
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_folders($folders)
    {
        set_time_limit(120);

        $client=$this->get_client();
        if($client['result'] == WPVIVID_PRO_FAILED)
            return $client;
        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];
        $folder_id=$this->get_folder($service,$path);

        if($folder_id==false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
        }

        foreach ($folders as $folder)
        {
            $file_folder_id=$this->get_folder_id($service,$folder,$folder_id);
            $service->files->delete($file_folder_id);
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function cleanup_rollback($type,$slug,$version)
    {
        $client=$this->get_client();
        if($client['result'] == WPVIVID_PRO_FAILED)
            return $client;
        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];
        $folder_id=$this->get_rollback_folder($service,$path,$type,$slug,$version);

        if($folder_id==false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
        }

        $file_name=$slug.".zip";

        $delete_files = $service->files->listFiles(array(
            'q' => "name='".$file_name."' and '".$folder_id."' in parents",
            'fields' => 'nextPageToken, files(id, name,mimeType)',
        ));

        if(sizeof($delete_files->getFiles())!=0)
        {
            foreach ($delete_files->getFiles() as $file_google_drive)
            {
                $file_id=$file_google_drive->getId();
                $service->files->delete($file_id);
            }
        }
        return array('result' =>WPVIVID_PRO_SUCCESS);
    }

    public function wpvivid_get_out_of_date_google_drive($out_of_date_remote, $remote)
    {
        if($remote['type'] == WPVIVID_REMOTE_GOOGLEDRIVE){
            $root_path=apply_filters('wpvivid_get_root_path', $remote['type']);
            $out_of_date_remote = $root_path.$remote['path'];
        }
        return $out_of_date_remote;
    }

    public function wpvivid_storage_provider_google_drive($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_GOOGLEDRIVE){
            $storage_type = 'Google Drive';
        }
        return $storage_type;
    }

    public function wpvivid_get_root_path_google_drive($storage_type)
    {
        if($storage_type == WPVIVID_REMOTE_GOOGLEDRIVE)
        {
            $storage_type = 'root/';
        }
        return $storage_type;
    }

    private function compare_php_version(){
        if(version_compare(WPVIVID_GOOGLE_NEED_PHP_VERSION,phpversion()) > 0){
            return array('result' => WPVIVID_PRO_FAILED,error => 'The required PHP version is higher than '.WPVIVID_GOOGLE_NEED_PHP_VERSION.'. After updating your PHP version, please try again.');
        }
        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function scan_folder_backup($folder_type)
    {
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return $client;
        }

        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $ret['path']=array();

        if($folder_type === 'Common')
        {
            $path=$this->options['path'];

            $response=$this->_scan_folder_backup($path,$service);

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
            $response=$this->_scan_folder_backup('migrate',$service);

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

            $remote_folder=$this->options['path'].'/rollback';
            $response=$this->_scan_folder_backup($remote_folder,$service);

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
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return $client;
        }

        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];

        $response=$this->_scan_child_folder_backup($path,$sub_path,$service);

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

    public function _scan_folder_backup($path,$service)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check upload folder '.$path,'notice');
        $folder_id=$this->get_folder($service,$path);

        //
        $result = array();
        $page_token = null;
        do {
            try {
                $parameters = array('q' => '"'.$folder_id.'" in parents', 'pageSize' => 200, 'fields' => 'nextPageToken, files(id, name, size, mimeType)');
                if ($page_token) {
                    $parameters['pageToken'] = $page_token;
                }
                $response = $service->files->listFiles($parameters);
                $result = array_merge($result, $response->getFiles());
                $page_token = $response->getNextPageToken();
            } catch (Exception $e) {
                $page_token = null;
            }
        } while ($page_token);

        if(!empty($result))
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            $files=array();

            foreach ($result as $file)
            {
                if($file->mimeType=='application/vnd.google-apps.folder')
                {
                    $ret['path'][]=$file->name;
                }
                else
                {
                    $file_data['file_name']=$file->name;
                    $file_data['size']=$file->size;
                    $files[]=$file_data;
                }
            }

            if(!empty($files))
            {
                global $wpvivid_backup_pro;
                $ret['backup']=$wpvivid_backup_pro->func->get_backup($files);
            }

            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            return $ret;
        }
        //

        /*$response = $service->files->listFiles(array(
            'q' => '"'.$folder_id.'" in parents',
            'pageToken' => null,
            'pageSize' => 100,
            //'fields' => 'files(id, name,size,mimeType)'
            ));
        if(sizeof($response->getFiles())==0)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            $files=array();

            foreach ($response->getFiles() as $file)
            {
                if($file->mimeType=='application/vnd.google-apps.folder')
                {
                    $ret['path'][]=$file->name;
                    //$ret_child=$this->_scan_child_folder_backup($path,$file->name,$service);
                    //if($ret_child['result']==WPVIVID_PRO_SUCCESS)
                    //{
                    //    $files= array_merge($files,$ret_child['files']);
                    //}
                }
                else
                {
                    $file_data['file_name']=$file->name;
                    $file_data['size']=$file->size;
                    $files[]=$file_data;
                }
            }

            if(!empty($files))
            {
                global $wpvivid_backup_pro;
                $ret['backup']=$wpvivid_backup_pro->func->get_backup($files);
            }

            return $ret;
        }*/
    }

    public function _scan_child_folder_backup($path,$sub_path,$service)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Check upload folder '.$path,'notice');
        $folder_id=$this->get_folder($service,$path.'/'.$sub_path);

        //
        $result = array();
        $page_token = null;
        do {
            try {
                $parameters = array('q' => '"'.$folder_id.'" in parents', 'pageSize' => 200, 'fields' => 'nextPageToken, files(id, name, size, mimeType)');
                if ($page_token) {
                    $parameters['pageToken'] = $page_token;
                }
                $response = $service->files->listFiles($parameters);
                $result = array_merge($result, $response->getFiles());
                $page_token = $response->getNextPageToken();
            } catch (Exception $e) {
                $page_token = null;
            }
        } while ($page_token);

        if(!empty($result))
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['files']=array();
            $ret['backup']=array();

            foreach ($result as $file)
            {
                if($file->mimeType=='application/vnd.google-apps.folder')
                {
                    continue;
                }
                else
                {
                    $file_data['file_name']=$file->name;
                    $file_data['size']=$file->size;
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
        else
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            return $ret;
        }
        //

        /*$response = $service->files->listFiles(array(
            'q' => "'".$folder_id."' in parents",
            'pageSize' => 1000,
            'fields' => 'files(id, name,size,mimeType)'));
        if(sizeof($response->getFiles())==0)
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['backup']=array();
            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['files']=array();
            $ret['backup']=array();
            foreach ($response->getFiles() as $file)
            {
                if($file->mimeType=='application/vnd.google-apps.folder')
                {
                    continue;
                }
                else
                {
                    $file_data['file_name']=$file->name;
                    $file_data['size']=$file->size;
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
        }*/
    }

    public function scan_folder_backup_ex($folder_type)
    {
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return $client;
        }

        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        if($folder_type=='all_backup')
        {
            $ret['result']='success';
            $ret['remote']=array();

            $response=$this->_get_common_backups($service);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['remote']= $response['backup'];
                $path=$response['path'];
            }

            $ret['migrate']=array();

            $response=$this->_get_migrate_backups($service);
            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['migrate']= $response['backup'];
            }

            $ret['rollback']=array();

            $response=$this->_get_rollback_backups($service);
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
                        $response=$this->_get_incremental_backups($service,$incremental_path);
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

            $response=$this->_get_common_backups($service);
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

            $response=$this->_get_migrate_backups($service);
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

            $response=$this->_get_rollback_backups($service);
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

            $response=$this->_get_common_backups($service);
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
                        $response=$this->_get_incremental_backups($service,$incremental_path);
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

    public function _get_common_backups($service)
    {
        $path=$this->options['path'];

        return $this->_scan_folder_backup($path,$service);
    }

    public function _get_migrate_backups($service)
    {
        return $this->_scan_folder_backup('migrate',$service);
    }

    public function _get_rollback_backups($service)
    {
        $remote_folder=$this->options['path'].'/rollback';

        return $this->_scan_folder_backup($remote_folder,$service);
    }

    public function _get_incremental_backups($service,$incremental_path)
    {
        $path=$this->options['path'];

        $ret=$this->_scan_child_folder_backup($path,$incremental_path,$service);
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

        $ret=$this->download_info_file($path,$backup_info_file);
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

    public function download_info_file($path,$backup_info_file)
    {
        try
        {
            global $wpvivid_plugin;

            $this->set_token();
            $client=$this->get_client();
            if($client['result'] == WPVIVID_PRO_FAILED)
                return $client;
            $client = $client['data'];

            if($client===false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
            }

            if(class_exists('WPvivid_Google_Service_Drive'))
            {
                $service = new WPvivid_Google_Service_Drive($client);
            }
            else
            {
                $service = new Google_Service_Drive($client);
            }

            $folder_id=$this->get_folder($service,$path);

            if($folder_id==false)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Unable to create the local file. Please make sure the folder is writable and try again.');
            }

            $response = $service->files->listFiles(array(
                'q' => "name='".$backup_info_file."' and '".$folder_id."' in parents",
                'fields' => 'files(id,size,webContentLink)'
            ));

            if(sizeof($response->getFiles())==0)
            {
                return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
            }
            else
            {
                $file_id='';
                foreach ($response->getFiles() as $file)
                {
                    $file_id=$file->getId();
                    break;
                }
                $wpvivid_plugin->wpvivid_download_log->WriteLog('Get download url.','notice');
                $download_url=$this->get_download_url($client,$file_id);

                if(!empty($file_id)||!empty($download_url))
                {
                    if ( class_exists( 'WPvividGuzzleHttp\Psr7\Request' ) )
                    {
                        $request = new WPvividGuzzleHttp\Psr7\Request('GET', $download_url);
                    }
                    else{
                        $request = new GuzzleHttp\Psr7\Request('GET', $download_url);
                    }

                    $http = $client->authorize();
                    $http_request = $http->send($request);
                    $http_response=$http_request->getStatusCode();
                    if (200 == $http_response || 206 == $http_response)
                    {
                        $ret['result']='success';
                        $ret['content']=$http_request->getBody()->getContents();
                        return $ret;
                    }
                    else
                    {
                        throw new Exception('Failed to obtain any new data http code:'.$http_response);
                    }
                }
                else
                {
                    return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Downloading file failed. The file might be deleted or network doesn\'t work properly. Please verify the file and confirm the network connection and try again later.');
                }
            }
        }catch(Exception $e)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error' => $e -> getMessage());
        }

        return array('result' => WPVIVID_PRO_SUCCESS);
    }

    public function scan_rollback($type)
    {
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return $client;
        }

        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];

        if($type === 'plugins')
        {
            $folder_id=$this->get_rollback_folder_id($service,$path,"plugins");
            $response=$this->_scan_folder($service,$folder_id);

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
            $folder_id=$this->get_rollback_folder_id($service,$path,"themes");
            $response=$this->_scan_folder($service,$folder_id);

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

    private function get_rollback_folder_id($service,$path,$folder)
    {
        $root_path='wpvividbackuppro';
        if(isset($this->options['root_path']))
        {
            $root_path=$this->options['root_path'];
        }
        $wpvivid_root_folder_id=$this->get_folder_id($service,$root_path);
        if($wpvivid_root_folder_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,$path,$wpvivid_root_folder_id);
        if($path_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,"rollback_ex",$path_id);
        if($path_id===false)
        {
            return false;
        }

        $path_id=$this->get_folder_id($service,$folder,$path_id);
        if($path_id===false)
        {
            return false;
        }

        return $path_id;
    }

    public function _scan_folder($service,$folder_id)
    {
        $result = array();
        $page_token = null;
        do {
            try {
                $parameters = array('q' => '"'.$folder_id.'" in parents', 'pageSize' => 200, 'fields' => 'nextPageToken, files(id, name, size, mimeType)');
                if ($page_token) {
                    $parameters['pageToken'] = $page_token;
                }
                $response = $service->files->listFiles($parameters);
                $result = array_merge($result, $response->getFiles());
                $page_token = $response->getNextPageToken();
            } catch (Exception $e) {
                $page_token = null;
            }
        } while ($page_token);

        if(!empty($result))
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['path']=array();

            foreach ($result as $file)
            {
                if($file->mimeType=='application/vnd.google-apps.folder')
                {
                    $ret['path'][]=$file->name;
                }
            }

            return $ret;
        }
        else
        {
            $ret['result']=WPVIVID_PRO_SUCCESS;
            $ret['path']=array();
            return $ret;
        }
    }

    public function get_rollback_data($type,$slug)
    {
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return $client;
        }

        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];

        if($type === 'plugins')
        {
            $folder_id=$this->get_rollback_folder_id($service,$path,"plugins");
            $slug_id=$this->get_folder_id($service,$slug,$folder_id);
            $response=$this->_scan_folder($service,$slug_id);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path= $response['path'];
                if(!empty($path))
                {
                    foreach ($path as $version)
                    {
                        $version_id=$this->get_folder_id($service,$version,$slug_id);
                        $response=$this->_scan_file($service,$slug.'.zip',$version_id);
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
            $folder_id=$this->get_rollback_folder_id($service,$path,"themes");
            $slug_id=$this->get_folder_id($service,$slug,$folder_id);
            $response=$this->_scan_folder($service,$slug_id);

            if($response['result']==WPVIVID_PRO_SUCCESS)
            {
                $ret['data']=array();
                $path= $response['path'];
                if(!empty($path))
                {
                    foreach ($path as $version)
                    {
                        $version_id=$this->get_folder_id($service,$version,$slug_id);
                        $response=$this->_scan_file($service,$slug.'.zip',$version_id);
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

    public function _scan_file($service,$file,$folder_id)
    {
        $result = array();
        $page_token = null;
        do
        {
            try
            {
                $parameters = array('q' => "name='".$file."' and '".$folder_id."' in parents", 'pageSize' => 200, 'fields' => 'nextPageToken, files(id, name, size, mimeType, modifiedTime)');
                if ($page_token) {
                    $parameters['pageToken'] = $page_token;
                }
                $response = $service->files->listFiles($parameters);
                $result = array_merge($result, $response->getFiles());
                $page_token = $response->getNextPageToken();
            } catch (Exception $e) {
                $page_token = null;
            }
        } while ($page_token);

        if(!empty($result))
        {
            $ret['result']='success';

            foreach ($result as $file)
            {
                if($file->mimeType=='application/vnd.google-apps.folder')
                {
                    continue;
                }
                else
                {
                    $file_data['file_name']=$file->name;
                    $file_data['size']=$file->size;
                    $file_data['mtime']=strtotime($file->modifiedTime);
                    $ret['file']=$file_data;
                    break;
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
            $ret['result']='failed';
            $ret['error']='Failed to get file information.';
            return $ret;
        }
    }

    public function delete_old_backup($backup_count,$db_count)
    {
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return $client;
        }

        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        $path=$this->options['path'];

        $response=$this->_scan_folder_backup($path,$service);

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
                $child_response=$this->_scan_child_folder_backup($path,$folder,$service);
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
                $this->cleanup_folders($folders);
            }
        }

        $path=$this->options['path'].'/rollback';

        $response=$this->_scan_folder_backup($path,$service);

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
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return false;
        }

        $client = $client['data'];

        if($client===false)
        {
            return false;
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        if($folder_type === 'Common')
        {
            $path=$this->options['path'];
        }
        else if($folder_type === 'Rollback')
        {

            $path=$this->options['path'].'/rollback';
        }
        else
        {
            return false;
        }

        $response=$this->_scan_folder_backup($path,$service);

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

    public function finish_add_remote()
    {
        global $wpvivid_backup_pro,$wpvivid_plugin;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-remote');
        try {
            if (empty($_POST) || !isset($_POST['remote']) || !is_string($_POST['remote'])) {
                die();
            }

            $tmp_remote_options = get_transient('google_drive_auth_id');
            if($tmp_remote_options === false)
            {
                die();
            }
            delete_transient('google_drive_auth_id');
            if(empty($tmp_remote_options)||$tmp_remote_options['type']!==WPVIVID_REMOTE_GOOGLEDRIVE)
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
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return $client;
        }

        $client = $client['data'];

        if($client===false)
        {
            return array('result' => WPVIVID_PRO_FAILED,'error'=> 'Token refresh failed.');
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        if($type=='Rollback')
        {
            $path=$this->options['path'].'/rollback';

            $response=$this->_scan_folder_backup($path,$service);

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
                    $this->cleanup_rollback_db($files);
                }
            }
        }
        else if($type=='Incremental')
        {
            $path=$this->options['path'];
            $response=$this->_scan_folder_backup($path,$service);

            if(isset($response['path']))
            {
                $folders=$response['path'];
                $files=array();
                global $wpvivid_backup_pro;
                $folders_count=$backup_count;
                $folders=$wpvivid_backup_pro->func->get_old_backup_folders($folders,$folders_count);
                foreach ($folders as $folder)
                {
                    $child_response=$this->_scan_child_folder_backup($path,$folder,$service);
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
                    $this->cleanup_folders($folders);
                }
            }
        }
        else
        {
            $path=$this->options['path'];
            $response=$this->_scan_folder_backup($path,$service);

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

                if(!empty($folders))
                {
                    $this->cleanup_folders($folders);
                }
            }
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function check_old_backups_ex($type,$backup_count,$db_count)
    {
        $client=$this->get_client();

        if($client['result'] == WPVIVID_PRO_FAILED)
        {
            return false;
        }

        $client = $client['data'];

        if($client===false)
        {
            return false;
        }

        if(class_exists('WPvivid_Google_Service_Drive'))
        {
            $service = new WPvivid_Google_Service_Drive($client);
        }
        else
        {
            $service = new Google_Service_Drive($client);
        }

        if($type=='Rollback')
        {
            $path=$this->options['path'].'/rollback';

            $response=$this->_scan_folder_backup($path,$service);

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
            $response=$this->_scan_folder_backup($path,$service);

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
            $response=$this->_scan_folder_backup($path,$service);

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