<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Migration_Page_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_PRO_REMOTE_SEND_TO_SITE'))
    define('WPVIVID_PRO_REMOTE_SEND_TO_SITE','send_to_site');

class WPvivid_Migration_Page_addon
{
    public $main_tab;

    public function __construct()
    {
        //add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),11);

        add_filter('wpvivid_put_transfer_key', array($this, 'wpvivid_put_transfer_key'), 11);

        add_action('wp_ajax_wpvivid_send_backup_to_site_addon',array( $this,'send_backup_to_site'));
        add_action('wp_ajax_wpvivid_hide_auto_migration_success_notice', array($this, 'hide_auto_migration_success_notice'));

        //init
        //add_action('wpvivid_backup_do_js_addon', array($this, 'wpvivid_backup_do_js_addon'), 11);
        //dashboard
        //add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        //add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        //add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));
    }

    public function wpvivid_put_transfer_key($html)
    {
        $options=WPvivid_Setting::get_option('wpvivid_saved_api_token');
        if(empty($options)){
            ob_start();
            ?>
            <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-top:1em;">
                <span>
                    <h2>Step 1: Paste the key below:
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <h3>How to get a site key?</h3>
                                <!-- The content you need -->
                                <p>1. Go to the destination site > WPvivid Plugin > Auto-Migration tab > Generate A Key sub-tab.</p>
                                <p>2. Generate a key by clicking Generate button and copy it.</p>
                                <p>3. Go back to this page and paste the key into the field below and click Save button.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </h2>
                </span>
                <textarea type="text" id="wpvivid_transfer_key_text" onkeyup="wpvivid_check_key(this.value)" style="width: 100%; height: 140px; margin-bottom:1em;"></textarea>
                <input class="button-primary" id="wpvivid_save_url_button" type="submit" value="Save" onclick="wpvivid_click_save_site_url();">

                <p></p>
                <div>
                    <span>Tips: Some web hosts may restrict the connection between the two sites, so you may get a 403 error or unstable connection issue when performing auto migration. In that case, it is recommended to use <a href="https://docs.wpvivid.com/custom-migration-overview.html" target="_blank" style="text-decoration: none;">the 'manual transfer' or 'migrate via remote storage' option</a> to migrate.</span>
                </div>

            </div>
            <?php
            $html = ob_get_clean();
        }
        else{
            $token='';
            $source_dir='';
            $target_dir='';
            $key_status='';
            foreach ($options as $key => $value)
            {
                $token = $value['token'];
                $source_dir=home_url();
                $target_dir=$value['domain'];
                $expires=$value['expires'];

                if ($expires != 0 && time() > $expires) {
                    $key_status='<span>Error: The key has expired. Please delete it first and paste a new one.</span>';
                }
                else{
                    $time_diff = $expires - time();
                    $key_status = '<p><span>The key will expire in: </span><span>'.date("H:i:s",$time_diff).'</span></p>
                                   <p><span>Connection Status:</span><span class="wpvivid-rectangle wpvivid-green">OK</span></p>
                                   <p><span>Now you can transfer the site <code>'.$source_dir.'</code> to the site <code>'.$target_dir.'</code></span></p>';
                }
            }
            ob_start();
            ?>
            <div class="wpvivid-one-coloum wpvivid-workflow">
            <span>
                <h2>Step 1: Paste the key below:
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                        <div class="wpvivid-bottom">
                            <h3>How to get a site key?</h3>
                            <!-- The content you need -->
                            <p>1. Go to the destination site > WPvivid Plugin > Auto-Migration tab > Generate A Key sub-tab.</p>
                            <p>2. Generate a key by clicking Generate button and copy it.</p>
                            <p>3. Go back to this page and paste the key into the field below and click Save button.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                </h2>
            </span>
                <span>Key:</span>
                <input type="text" id="wpvivid_send_remote_site_url_text" value="<?php echo $token; ?>" readonly="readonly">
                <input class="button-primary" id="wpvivid_delete_key_button" type="submit" value="Delete" onclick="wpvivid_click_delete_transfer_key();">
                <p>
                    <?php echo $key_status; ?>
                </p>
            </div>

            <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-top:1em;">
                <span>
                    <h2>
                        <span style="line-height: 30px;">Step 2: Select what to migrate</span>
                        <!--<input class="button" type="submit" id="wpvivid_recalc_migration_size" value="Re-Calc" />
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip" style="margin-top: 4px;">
                            <div class="wpvivid-bottom">
                                <p>Recalculate sizes of the contents to be backed up after you finish selecting them.</p>
                                <i></i>
                            </div>
                        </span>-->
                    </h2>
                </span>
                <p></p>

                <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                    <?php
                    if(!is_multisite())
                    {
                        $fieldset_style = '';
                        ?>
                        <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                            <?php
                            $html = '';
                            echo apply_filters('wpvivid_add_backup_type_addon', $html, 'backup_files');
                            ?>
                        </fieldset>
                        <?php
                    }
                    else{
                        $fieldset_style = '';
                        ?>
                        <div style="padding:1em 1em 1em 1em;margin-bottom:1em;background:#eaf1fe;border-radius:0.8em;">
                            <div style="">
                                <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                                    <?php
                                    $html = '';
                                    echo apply_filters('wpvivid_add_backup_type_addon', $html, 'backup_files');
                                    ?>
                                </fieldset>
                            </div>
                            <div id="wpvivid_custom_manual_backup_mu_single_site_list" style="display: none;">
                                <p>Choose the childsite you want to migrate</p>
                                <p>
                                    <span style="padding-right:0.2em;">
                                        <input type="search" style="margin-bottom: 4px; width:300px;" id="wpvivid-mu-single-site-search-input" placeholder="Enter title, url or description" name="s" value="">
                                    </span>
                                    <span><input type="submit" id="wpvivid-mu-single-search-submit" class="button" value="Search"></span>
                                </p>

                                <div id="wpvivid_mu_single_site_list"></div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div id="wpvivid_custom_migration_backup" style="display: none;">
                    <?php
                    $general_setting=WPvivid_Setting::get_setting(true, "");
                    if(isset($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui'])){
                        if($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui']){
                            $use_new_custom_backup_ui = '1';
                        }
                        else{
                            $use_new_custom_backup_ui = '';
                        }
                    }
                    else{
                        $use_new_custom_backup_ui = '';
                    }
                    if($use_new_custom_backup_ui == '1'){
                        $custom_backup_manager = new WPvivid_Custom_Backup_Manager_Ex('wpvivid_custom_migration_backup','migration_backup');
                    }
                    else{
                        $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_migration_backup','migration_backup', '1', '0');
                    }
                    //$custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_migration_backup','migration_backup');
                    $custom_backup_manager->output_custom_backup_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>
                <div id="wpvivid_custom_migration_backup_mu_single_site" style="display: none;">
                    <?php
                    $type = 'manual_backup';
                    do_action('wpvivid_custom_backup_setting', 'wpvivid_custom_manual_backup_mu_single_site_list', 'wpvivid_custom_migration_backup_mu_single_site', $type, '0');
                    ?>
                </div>
            </div>

            <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-top:1em;">
                <span>
                    <h2>Step 3: Perform the migration
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The unstable connection between sites could cause a failure of files transfer. In this case, uploading backups to destination site is a good alternative to the automatic website migration.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </h2></span>
                <p></p>
                <div>
                    <input class="button-primary" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" id="wpvivid_quickbackup_btn" type="submit" value="Clone then Transfer">
                    <div class="wpvivid-element-space-bottom" style="text-align: left;">
                        <div>
                            <p>
                                <span class="dashicons dashicons-pressthis wpvivid-dashicons-orange"></span><span>1. In order to successfully complete the migration, you'd better deactivate <a href="https://wpvivid.com/best-redirect-plugins.html" target="_blank" style="text-decoration: none;">301 redirect plugin</a>, <a href="https://wpvivid.com/8-best-wordpress-firewall-plugins.html" target="_blank" style="text-decoration: none;">firewall and security plugin</a>, and <a href="https://wpvivid.com/best-free-wordpress-caching-plugins.html" target="_blank" style="text-decoration: none;">caching plugin</a> (if they exist) before transferring website.</span>
                            </p>
                            <p>
                                <span class="dashicons dashicons-pressthis wpvivid-dashicons-orange"></span><span>2. Please migrate website with the manual way when using <strong>Local by Flywheel</strong> environment.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
        }
        return $html;
    }

    public function send_backup_to_site()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-migrate');
        try
        {
            if(isset($_POST['backup'])&&!empty($_POST['backup']))
            {
                $options = WPvivid_Setting::get_option('wpvivid_saved_api_token');

                if (empty($options)) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'A key is required.';
                    echo json_encode($ret);
                    die();
                }

                $url = '';
                foreach ($options as $key => $value) {
                    $url = $value['url'];
                }

                if ($url === '') {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                if ($options[$url]['expires'] != 0 && $options[$url]['expires'] < time()) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The key has expired.';
                    echo json_encode($ret);
                    die();
                }

                $json['test_connect']=1;
                $json=json_encode($json);
                $crypt=new WPvivid_crypt(base64_decode($options[$url]['token']));
                $data=$crypt->encrypt_message($json);
                $data=base64_encode($data);
                $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site_connect');
                $response=wp_remote_post($url,$args);

                if ( is_wp_error( $response ) )
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']= $response->get_error_message();
                    echo json_encode($ret);
                    die();
                }
                else
                {
                    if($response['response']['code']==200) {
                        $res=json_decode($response['body'],1);
                        if($res!=null) {
                            if($res['result']==WPVIVID_PRO_SUCCESS) {
                            }
                            else {
                                $ret['result']=WPVIVID_PRO_FAILED;
                                $ret['error']= $res['error'];
                                echo json_encode($ret);
                                die();
                            }
                        }
                        else {
                            $ret['result']=WPVIVID_PRO_FAILED;
                            $ret['error']= 'failed to parse returned data, unable to establish connection with the target site.';
                            $ret['response']=$response;
                            echo json_encode($ret);
                            die();
                        }
                    }
                    else {
                        $ret['result']=WPVIVID_PRO_FAILED;
                        $ret['error']= 'upload error '.$response['response']['code'].' '.$response['body'];
                        echo json_encode($ret);
                        die();
                    }
                }

                $json = $_POST['backup'];
                $json = stripslashes($json);
                $backup_options = json_decode($json, true);
                if (is_null($backup_options))
                {
                    die();
                }

                $remote_option['url'] = $options[$url]['url'];
                $remote_option['token'] = $options[$url]['token'];
                $remote_option['type'] = WPVIVID_PRO_REMOTE_SEND_TO_SITE;
                $remote_options['temp'] = $remote_option;
                $backup_options['remote_options'] = $remote_options;
                $backup_options = apply_filters('wpvivid_custom_backup_options', $backup_options);
                if(!isset($backup_options['type']))
                {
                    $backup_options['type']='Manual';
                    $backup_options['action']='backup';
                }

                global $wpvivid_plugin;

                $ret = $wpvivid_plugin->check_backup_option($backup_options, $backup_options['type']);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    echo json_encode($ret);
                    die();
                }

                if(isset($_POST['is_export']))
                {
                    $backup_options['is_export'] = true;
                }

                //$ret=$wpvivid_plugin->pre_backup($backup_options);
                $ret=$this->pre_backup($backup_options);
                if($ret['result']=='success')
                {
                    //Check the website data to be backed up
                    $ret['check']=$wpvivid_plugin->check_backup($ret['task_id'],$backup_options);
                    if(isset($ret['check']['result']) && $ret['check']['result'] == WPVIVID_PRO_FAILED)
                    {
                        echo json_encode(array('result' => WPVIVID_PRO_FAILED,'error' => $ret['check']['error']));
                        die();
                    }

                    $html = '';
                    $ret['html'] = $html;
                }
                echo json_encode($ret);
                die();
            }
        }
        catch (Exception $error)
        {
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            $id=uniqid('wpvivid-');
            $log_file_name=$id.'_backup';
            $log=new WPvivid_Log_Ex_addon();
            $log->CreateLogFile($log_file_name,'no_folder','backup');
            $log->WriteLog($message,'notice');
            $log->CloseFile();
            WPvivid_error_log::create_error_log($log->log_file);
            error_log($message);
            echo json_encode($ret);
            die();
        }
        die();
    }

    public function pre_backup($backup_options)
    {
        global $wpvivid_plugin;
        if(apply_filters('wpvivid_need_clean_oldest_backup',true,$backup_options))
        {
            $wpvivid_plugin->clean_oldest_backup();
        }
        //do_action('wpvivid_clean_oldest_backup',$backup_options);

        if(WPvivid_taskmanager::is_tasks_backup_running())
        {
            $ret['result']='failed';
            $ret['error']=__('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid');
            return $ret;
        }

        if(!class_exists('WPvivid_Backup_Task_Ex'))
        {
            include WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'addons2/backup_pro/class-wpvivid-backup-task-addon.php';
        }

        $backup=new WPvivid_Backup_Task_Ex();
        $ret=$backup->new_backup_task($backup_options,$backup_options['type'],$backup_options['action']);
        return $ret;
    }

    public function hide_auto_migration_success_notice()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try
        {
            update_option('wpvivid_display_auto_migration_success_notice', false, 'no');
            $ret['result']='success';
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            echo json_encode($ret);
        }
        die();
    }
}