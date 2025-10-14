<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_Backup_Restore_Page_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Backup_Restore_Page_addon
{
    private $database_connect;

    public function __construct()
    {

        add_action('wpvivid_handle_backup_succeed',array($this,'wpvivid_handle_backup_succeed'),11);
        add_action('wpvivid_handle_upload_succeed',array($this,'wpvivid_handle_backup_succeed'),11);
        add_action('wpvivid_handle_backup_failed',array($this,'wpvivid_handle_backup_failed'),11, 2);
        $this->load_display_filters();
        $this->load_backup_filters();
        $this->load_backup_actions();
        $this->load_backup_ajax();
        //init
        add_action('wpvivid_backup_do_js_addon', array($this, 'wpvivid_backup_do_js_addon'), 11);

        //dashboard
        //add_action('wpvivid_backup_pro_add_sidebar', array($this, 'add_sidebar'));
        //add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));

    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-backup';
        $cap['display']='Create manual backups';
        $cap['menu_slug']=strtolower(sprintf('%s-backup', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_list[$cap['slug']]=$cap;

        $cap['slug']='wpvivid-can-backup-remote';
        $cap['display']='Back up to remote storage';
        $cap['menu_slug']=strtolower(sprintf('%s-backup-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function add_sidebar()
    {
        if(apply_filters('wpvivid_show_sidebar',true))
        {
            ?>
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox  wpvivid-sidebar">
                        <h2 style="margin-top:0.5em;"><span class="dashicons dashicons-sticky wpvivid-dashicons-orange"></span>
                            <span><?php esc_attr_e(
                                    'Troubleshooting', 'WpAdminStyle'
                                ); ?></span></h2>
                        <div class="inside" style="padding-top:0;">
                            <ul class="" >
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-editor-help wpvivid-dashicons-orange" ></span>
                                    <a href="https://docs.wpvivid.com/troubleshooting"><b>Troubleshooting</b></a>
                                    <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-admin-generic wpvivid-dashicons-orange" ></span>
                                    <a href="https://docs.wpvivid.com/wpvivid-backup-pro-advanced-settings.html"><b>Adjust Advanced Settings </b></a>
                                    <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>

                            </ul>
                        </div>

                        <h2><span class="dashicons dashicons-book-alt wpvivid-dashicons-orange" ></span>
                            <span><?php esc_attr_e(
                                    'Documentation', 'WpAdminStyle'
                                ); ?></span></h2>
                        <div class="inside" style="padding-top:0;">
                            <ul class="">
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-backup  wpvivid-dashicons-grey"></span>
                                    <a href="https://docs.wpvivid.com/manual-backup-overview.html"><b>Backup</b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-backup', 'wpvivid-backup')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li><span class="dashicons dashicons-migrate  wpvivid-dashicons-grey"></span>
                                    <a href="https://docs.wpvivid.com/custom-migration-overview.html"><b>Auto-Migration</b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-export-site', 'wpvivid-export-site')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li><span class="dashicons dashicons-editor-ul  wpvivid-dashicons-grey"></span>
                                    <a href="https://docs.wpvivid.com/wpvivid-backup-pro-backups-restore-overview.html"><b>Backup Manager</b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li><span class="dashicons dashicons-calendar-alt  wpvivid-dashicons-grey"></span>
                                    <a href="https://docs.wpvivid.com/wpvivid-backup-pro-schedule-overview.html"><b>Schedule</b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-schedule', 'wpvivid-schedule')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li><span class="dashicons dashicons-admin-site-alt3  wpvivid-dashicons-grey"></span>
                                    <a href="https://docs.wpvivid.com/wpvivid-backup-pro-cloud-storage-overview.html"><b>Cloud Storage</b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-remote', 'wpvivid-remote')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li><span class="dashicons dashicons-randomize  wpvivid-dashicons-grey"></span>
                                    <a href="https://docs.wpvivid.com/export-content.html"><b>Export/Import</b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-export-import', 'wpvivid-export-import')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li><span class="dashicons dashicons-code-standards  wpvivid-dashicons-grey"></span>
                                    <a href="https://docs.wpvivid.com/unused-images-cleaner.html"><b>Unused Image Cleaner</b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                            </ul>
                        </div>

                        <?php
                        if(apply_filters('wpvivid_show_submit_ticket',true))
                        {
                            ?>
                            <h2>
                                <span class="dashicons dashicons-businesswoman wpvivid-dashicons-green"></span>
                                <span><?php esc_attr_e(
                                        'Support', 'WpAdminStyle'
                                    ); ?></span>
                            </h2>
                            <div class="inside">
                                <ul class="">
                                    <li><span class="dashicons dashicons-admin-comments wpvivid-dashicons-green"></span>
                                        <a href="https://wpvivid.com/submit-ticket"><b>Submit A Ticket</b></a>
                                        <br>
                                        The ticket system is for <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Pro users only. If you need any help with our plugin, submit a ticket and we will respond shortly.
                                    </li>
                                </ul>
                            </div>
                            <!-- .inside -->
                            <?php
                        }
                        ?>

                    </div>
                    <!-- .postbox -->

                </div>
                <!-- .meta-box-sortables -->

            </div>
            <?php
        }
    }

    public function load_display_filters()
    {
        add_filter('wpvivid_add_backup_type_addon', array($this, 'wpvivid_backuppage_add_backup_type'), 12, 2);
    }

    public function load_backup_filters()
    {
        add_filter('wpvivid_check_backup_options_valid',array($this, 'check_backup_options_valid'),11,3);
        add_filter('wpvivid_custom_backup_options',array($this, 'custom_backup_options'), 11);
        add_filter('wpvivid_exclude_db_table', array($this, 'exclude_table'),11,2);
        add_filter('wpvivid_set_backup_type', array($this, 'set_backup_type'), 10, 2);
        add_filter('wpvivid_custom_set_backup', array($this, 'set_backup_ex'), 10);
        add_filter('wpvivid_set_remote_options', array($this, 'set_remote_options'), 10, 2);
        add_filter('wpvivid_get_custom_need_backup_files', array($this, 'get_need_backup_files'), 10, 3);
        add_filter('wpvivid_get_custom_need_backup_files_size', array($this, 'get_need_backup_files_size'), 10, 3);
        add_filter('wpvivid_exclude_plugins',array($this,'exclude_plugins'),10);
        add_filter('wpvivid_enable_plugins_list',array($this,'enable_plugins_list'),10);
        add_filter('wpvivid_need_clean_oldest_backup', array($this, 'need_clean_oldest_backup'), 10,2);
        add_filter('wpvivid_check_backup_size',array($this, 'check_backup_size'),10,2);
        add_filter('wpvivid_set_backup_ismerge', array($this, 'set_backup_ismerge'), 10,2);
        add_filter('wpvivid_set_custom_backup', array($this, 'set_custom_backup'), 10,3);
        add_filter('wpvivid_get_backup_exclude_regex',array($this, 'get_backup_exclude_regex'),10,2);
        add_filter('wpvivid_get_backup_exclude_files_regex',array($this, 'get_backup_exclude_files_regex'),10,2);
        add_filter('wpvivid_archieve_database_info', array($this, 'wpvivid_archieve_database_info'), 11, 2);
        add_filter('wpvivid_check_type_database', array($this, 'wpvivid_check_type_database'), 11, 2);
        add_filter('wpvivid_check_additional_database', array($this, 'wpvivid_check_additional_database'), 11, 2);
        add_filter('wpvivid_additional_database_display_ex', array($this, 'wpvivid_additional_database_display_ex'), 10);
        add_filter('wpvivid_check_backup_completeness', array($this, 'check_backup_completeness'), 10, 2);
        add_filter('wpvivid_additional_server_list', array($this, 'wpvivid_additional_server_list'), 10);
        add_filter('wpvivid_additional_database_list', array($this, 'wpvivid_additional_database_list'), 10);
    }

    public function load_backup_actions()
    {
        add_action('wpvivid_action_white_label_edit_path', array($this, 'edit_path'));
    }

    public function load_backup_ajax()
    {
        add_action('wp_ajax_wpvivid_get_database_themes_plugins_table', array($this, 'get_database_themes_plugins_table'));
        add_action('wp_ajax_wpvivid_get_custom_dir_uploads', array($this, 'get_custom_dir_uploads'));
        add_action('wp_ajax_wpvivid_get_custom_dir', array($this, 'get_custom_dir'));
        add_action('wp_ajax_wpvivid_get_custom_tree_dir', array($this, 'get_custom_tree_dir'));
        add_action('wp_ajax_wpvivid_update_backup_exclude_extension', array($this, 'update_backup_exclude_extension'));
        add_action('wp_ajax_wpvivid_connect_additional_database', array($this, 'connect_additional_database'));
        add_action('wp_ajax_wpvivid_add_additional_database', array($this, 'add_additional_database'));
        add_action('wp_ajax_wpvivid_remove_additional_database', array($this, 'remove_additional_database'));
        add_action('wp_ajax_wpvivid_prepare_backup_ex',array( $this,'prepare_backup'));
        add_action('wp_ajax_wpvivid_list_tasks_addon',array( $this,'list_tasks_addon'), 11);
        add_action('wp_ajax_wpvivid_get_website_size', array($this, 'get_website_size'));
        add_action('wp_ajax_wpvivid_recalc_backup_size', array($this, 'recalc_backup_size'));


    }

    public function edit_path($white_label_slug)
    {
        $local_setting=get_option('wpvivid_local_setting',array());
        $search = 'wpvivid';
        $label_slug = strtolower($white_label_slug);
        $local_setting['path'] = str_replace($search, $label_slug, $local_setting['path']);
        update_option('wpvivid_local_setting', $local_setting, 'no');
    }

    public function wpvivid_handle_backup_succeed($task)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        if($task['action'] === 'transfer')
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload finished. Delete task '.$task['id'], 'notice');
        }
        else if($task['action'] === 'auto_transfer')
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Upload finished. Delete task '.$task['id'], 'notice');
        }
        else if($task['action'] === 'backup_remote')
        {
            WPvivid_Setting::update_option('wpvivid_backup_remote_need_update', true);
            $wpvivid_plugin->wpvivid_analysis_backup($task);
            $backup_lock=WPvivid_Setting::get_option('wpvivid_remote_backups_lock');
            $backup_id = $task['id'];
            $lock = $task['options']['lock'];
            if($lock)
            {
                $backup_lock[$backup_id]=1;
            }
            else {
                unset($backup_lock[$backup_id]);
            }
            WPvivid_Setting::update_option('wpvivid_remote_backups_lock',$backup_lock);
            do_action('wpvivid_clean_oldest_backup');
        }
        else if($task['type']=='Cron')
        {
            $task_msg = WPvivid_taskmanager::get_task($task['id']);
            $schedule_id = false;
            $schedule_id = get_option('wpvivid_current_schedule_id', $schedule_id);
            if(isset($schedule_id))
            {
                //update last backup time
                do_action('wpvivid_update_schedule_last_time_addon', $schedule_id, $task_msg['status']['start_time']);
            }

            $remote_options = WPvivid_taskmanager::get_task_options($task['id'], 'remote_options');
            if($remote_options != false)
            {
                $backup_list=new WPvivid_New_BackupList();
                $backup_list->delete_backup($task['id']);
                WPvivid_Setting::update_option('wpvivid_backup_remote_need_update', true);
            }
            update_option('wpvivid_general_schedule_data', $task_msg, 'no');
        }
        //mu
        $task_msg = WPvivid_taskmanager::get_task($task['id']);
        $wpvivid_plugin->update_last_backup_task($task_msg);
        $tasks=get_option('wpvivid_backup_finished_tasks',array());
        $tasks[$task['id']]['status']='completed';

        if(isset($task['is_export']))
        {
            if($task['action'] === 'backup')
            {
                $tasks[$task['id']]['action_type']='backup';
            }
            else if($task['action'] === 'backup_remote')
            {
                $tasks[$task['id']]['action_type']='backup_remote';
            }
            else if($task['action'] === 'auto_transfer')
            {
                $tasks[$task['id']]['action_type']='auto_transfer';
            }
            else
            {
                $tasks[$task['id']]['action_type']='other';
            }
            $tasks[$task['id']]['is_export'] = true;
        }
        else
        {
            if($task['action'] === 'auto_transfer')
            {
                $tasks[$task['id']]['action_type']='auto_transfer';
            }
            else {
                $tasks[$task['id']]['action_type']='other';
            }
        }
        WPvivid_Setting::update_option('wpvivid_backup_finished_tasks',$tasks);

        //Cron
        WPvivid_Schedule::clear_monitor_schedule($task['id']);

        $destination = "";
        $message = 'WPvivid backup finished';
        $backup_type = 'wpvivid database, plugins, themes';
        $backup_type = WPvivid_Setting::get_option('wpvivid_backup_report', false);
        $backup_type = explode(',', $backup_type);
        $tmp_backup_type = '';
        $backup_report = '';
        for($index=0; $index<count($backup_type); $index++)
        {
            if(!empty($backup_type[$index]))
            {
                $tmp_backup_type = self::wpvivid_transfer_backup_type($backup_type[$index]);
                if($tmp_backup_type !== '')
                {
                    $backup_report .= $tmp_backup_type . ',';
                }
            }
        }
        if($backup_report !== '')
        {
            $backup_report = rtrim($backup_report, ',');
        }
        else{
            $backup_report = 'WPvivid Backup';
        }
        $backup_time = time();
        do_action("wpvivid_backup", $destination , $message, __('Finished', 'mainwp-child-reports'), $backup_report, $backup_time);
        delete_option('wpvivid_backup_report');
    }

    public function wpvivid_handle_backup_failed($task, $need_set_low_resource_mode)
    {
        global $wpvivid_plugin;

        $tasks=get_option('wpvivid_backup_finished_tasks',array());
        $tasks[$task['id']]['status']='error';

        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload'])&&$general_setting['options']['wpvivid_compress_setting']['subpackage_plugin_upload'])
        {
            $need_notice = true;
        }
        else {
            $need_notice = false;
        }

        $admin_url = apply_filters('wpvivid_get_admin_url', '');

        if($need_set_low_resource_mode&&$need_notice)
        {
            $notice_msg1 = 'Backup failed, it seems due to insufficient server resource or hitting server limit. Please navigate to Settings > Advanced > ';
            $notice_msg2 = 'optimization mode for web hosting/shared hosting';
            $notice_msg3 = ' to enable it and try again';
            $tasks[$task['id']]['error_msg']=__('<div class="notice notice-error inline"><p>'.$notice_msg1.'<strong>'.$notice_msg2.'</strong>'.$notice_msg3.'</p></div>');
            $tasks[$task['id']]['tmp_msg']=$notice_msg1.$notice_msg2.$notice_msg3;
        }
        else if($need_set_low_resource_mode)
        {
            $notice_msg = 'Backup failed, it seems due to insufficient server resource or hitting server limit.';
            $tasks[$task['id']]['error_msg'] = __('<div class="notice notice-error inline"><p>' . $notice_msg . ', Please switch to <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'" >Website Info</a> page to send us the debug information. </p></div>');
            $tasks[$task['id']]['tmp_msg']=$notice_msg;
        }
        else{
            $notice_msg = 'Backup error: '.$task['status']['error'].', task id: '.$task['id'];
            $tasks[$task['id']]['error_msg']=__('<div class="notice notice-error inline"><p>'.$notice_msg.', Please switch to <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'">Website Info</a> page to send us the debug information. </p></div>');
            $tasks[$task['id']]['tmp_msg']=$notice_msg;
        }

        if($task['type']=='Cron')
        {
            $task_msg = WPvivid_taskmanager::get_task($task['id']);
            update_option('wpvivid_general_schedule_data', $task_msg, 'no');
        }

        $task_msg = WPvivid_taskmanager::get_task($task['id']);
        $wpvivid_plugin->update_last_backup_task($task_msg);

        WPvivid_Setting::update_option('wpvivid_backup_finished_tasks',$tasks);
        delete_option('wpvivid_backup_report');
    }
    /***** backup display filter begin *****/
    public function wpvivid_backuppage_add_backup_type($html, $type_name)
    {
        ob_start();
        ?>
        <label style="padding-right:2em;">
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="files+db" checked="checked">
            <span>Wordpress Files + Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="db">
            <span>Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="files">
            <span>Wordpress Files</span>
        </label>
        <label>
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="custom">
            <span>Custom content</span>
        </label>
        <?php
        $html .= ob_get_clean();
        return $html;
    }
    /***** backup display filter end *****/

    /***** backup filters begin *****/
    public function check_backup_options_valid($ret,$data,$backup_method)
    {
        $ret['result']=WPVIVID_PRO_FAILED;
        if(!isset($data['backup_select']) && !isset($data['backup_files']))
        {
            $ret['error']=__('A backup type is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['local']) && !isset($data['remote']))
        {
            $ret['error']=__('Choose at least one storage location for backups.', 'wpvivid');
            return $ret;
        }

        $data['local']=sanitize_text_field($data['local']);
        $data['remote']=sanitize_text_field($data['remote']);

        if(empty($data['local']) && empty($data['remote']))
        {
            $ret['error']=__('Choose at least one storage location for backups.', 'wpvivid');
            return $ret;
        }

        if($backup_method == 'Manual')
        {
            if ($data['remote'] === '1')
            {
                $remote_storage = WPvivid_Setting::get_remote_options();
                if ($remote_storage == false)
                {
                    $ret['error'] = __('There is no default remote storage configured. Please set it up first.', 'wpvivid');
                    return $ret;
                }
            }
        }
        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function custom_backup_options($options)
    {
        if(isset($options['backup_to']))
        {
            if($options['backup_to']=='local')
            {
                $options['local']=1;
                $options['remote']=false;
                $options['type']='Manual';
                $options['action']='backup';
            }
            else if($options['backup_to']=='migrate_remote')
            {
                $options['local']=0;
                $options['remote']=1;
                $options['type']='Migrate';
                $options['action']='transfer';
            }
            else if($options['backup_to']=='auto_migrate')
            {
                $options['local']=0;
                $options['remote']=1;
                $options['type']='Migrate';
                $options['action']='auto_transfer';
            }
            else if($options['backup_to']=='remote')
            {
                $options['local']=0;
                $options['remote']=1;
                $options['type']='Manual';
                $options['action']='backup_remote';
            }
        }

        if(isset($options['backup_files']))
        {
            if($options['backup_files'] == 'custom')
            {
                //custom_dirs
                $options['backup_select']['db'] = intval($options['custom_dirs']['database_check']);
                $options['backup_select']['additional_db'] = intval($options['custom_dirs']['additional_database_check']);
                $options['exclude_tables'] = $options['custom_dirs']['database_list'];
                if($options['backup_select']['additional_db'] === 1){
                    $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
                    $options['additional_database_list'] = $history['additional_database_option']['additional_database_list'];
                }

                $options['backup_select']['themes'] = intval($options['custom_dirs']['themes_check']);
                $options['backup_select']['plugin'] = intval($options['custom_dirs']['plugins_check']);
                $options['backup_select']['uploads'] = intval($options['custom_dirs']['uploads_check']);
                $options['backup_select']['content'] = intval($options['custom_dirs']['content_check']);
                $options['backup_select']['other'] = intval($options['custom_dirs']['other_check']);
                $options['backup_select']['core'] = intval($options['custom_dirs']['core_check']);

                $themes_exclude_list=array();
                if(isset($options['custom_dirs']['themes_list']))
                {
                    foreach ($options['custom_dirs']['themes_list'] as $key => $value){
                        $themes_exclude_list[] = $key;
                    }
                }
                $themes_exclude_file_list=array();
                $themes_extension_tmp = array();
                if(isset($options['custom_dirs']['themes_extension']) && !empty($options['custom_dirs']['themes_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['themes_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $themes_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $themes_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['custom_dirs']['themes_extension'] = $themes_extension_tmp;
                }

                $plugins_exclude_list=array();
                if(isset($options['custom_dirs']['plugins_list']))
                {
                    foreach ($options['custom_dirs']['plugins_list'] as $key => $value){
                        $plugins_exclude_list[] = $key;
                    }
                }
                $plugins_exclude_file_list=array();
                $plugins_extension_tmp = array();
                if(isset($options['custom_dirs']['plugins_extension']) && !empty($options['custom_dirs']['plugins_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['plugins_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $plugins_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $plugins_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['custom_dirs']['plugins_extension'] = $plugins_extension_tmp;
                }

                $upload_exclude_list=array();
                if(isset($options['custom_dirs']['uploads_list']))
                {
                    foreach ($options['custom_dirs']['uploads_list'] as $key => $value){
                        $upload_exclude_list[] = $key;
                    }
                }
                $upload_exclude_file_list=array();
                $upload_extension_tmp = array();
                if(isset($options['custom_dirs']['upload_extension']) && !empty($options['custom_dirs']['upload_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['upload_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $upload_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $upload_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['custom_dirs']['upload_extension'] = $upload_extension_tmp;
                }

                $content_exclude_list=array();
                if(isset($options['custom_dirs']['content_list']))
                {
                    foreach ($options['custom_dirs']['content_list'] as $key => $value){
                        $content_exclude_list[] = $key;
                    }
                }

                $content_exclude_file_list=array();
                $content_extension_tmp = array();
                if(isset($options['custom_dirs']['content_extension']) && !empty($options['custom_dirs']['content_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['content_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $content_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $content_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['custom_dirs']['content_extension'] = $content_extension_tmp;
                }

                $other_include_list=array();
                if(isset($options['custom_dirs']['other_list']))
                {
                    foreach ($options['custom_dirs']['other_list'] as $key => $value){
                        $other_include_list[] = $key;
                    }
                }
                $other_exclude_file_list=array();
                $other_extension_tmp = array();
                if(isset($options['custom_dirs']['other_extension']) && !empty($options['custom_dirs']['other_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['other_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $other_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $other_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['custom_dirs']['other_extension'] = $other_extension_tmp;
                }

                if(isset($options['custom_dirs']['exclude_themes_folder'])){
                    $options['exclude_themes_folder'] = $options['custom_dirs']['exclude_themes_folder'];
                }
                if(isset($options['custom_dirs']['exclude_plugins_folder'])){
                    $options['exclude_plugins_folder'] = $options['custom_dirs']['exclude_plugins_folder'];
                }

                $options['exclude_themes_files']=$themes_exclude_file_list;
                $options['exclude_themes']=$themes_exclude_list;
                $options['exclude_plugins_files']=$plugins_exclude_file_list;
                $options['exclude_plugins']=$plugins_exclude_list;
                $options['exclude_uploads_files']=$upload_exclude_file_list;
                $options['exclude_uploads'] = $upload_exclude_list;
                $options['exclude_content_files']=$content_exclude_file_list;
                $options['exclude_content'] = $content_exclude_list;
                $options['exclude_custom_other_files']=$other_exclude_file_list;
                $options['custom_other_root'] = $other_include_list;
                $options['exclude_custom_other']=array();

                unset($options['backup_files']);
                WPvivid_Custom_Interface_addon::update_custom_backup_setting($options['custom_dirs']);
            }
            else if($options['backup_files'] == 'mu')
            {
                $options['backup_select']['mu_site'] =1;

                $themes_exclude_list=array();
                if(isset($options['mu_setting']['themes_list']))
                {
                    foreach ($options['mu_setting']['themes_list'] as $key => $value){
                        $themes_exclude_list[] = $key;
                    }
                }
                $themes_exclude_file_list=array();
                $themes_extension_tmp = array();
                if(isset($options['mu_setting']['themes_extension']) && !empty($options['mu_setting']['themes_extension']))
                {
                    $str_tmp = explode(',', $options['mu_setting']['themes_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $themes_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $themes_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['mu_setting']['themes_extension'] = $themes_extension_tmp;
                }

                $plugins_exclude_list=array();
                if(isset($options['mu_setting']['plugins_list']))
                {
                    foreach ($options['mu_setting']['plugins_list'] as $key => $value){
                        $plugins_exclude_list[] = $key;
                    }
                }
                $plugins_exclude_file_list=array();
                $plugins_extension_tmp = array();
                if(isset($options['mu_setting']['plugins_extension']) && !empty($options['mu_setting']['plugins_extension']))
                {
                    $str_tmp = explode(',', $options['mu_setting']['plugins_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $plugins_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $plugins_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['mu_setting']['plugins_extension'] = $plugins_extension_tmp;
                }

                $upload_exclude_list=array();
                if(isset($options['mu_setting']['uploads_list']))
                {
                    foreach ($options['mu_setting']['uploads_list'] as $key => $value){
                        $upload_exclude_list[] = $key;
                    }
                }

                $upload_exclude_file_list=array();
                $upload_extension_tmp = array();
                if(isset($options['mu_setting']['upload_extension']) && !empty($options['mu_setting']['upload_extension']))
                {
                    $str_tmp = explode(',', $options['mu_setting']['upload_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $upload_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $upload_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['mu_setting']['upload_extension'] = $upload_extension_tmp;
                }

                $content_exclude_list=array();
                if(isset($options['mu_setting']['content_list']))
                {
                    foreach ($options['mu_setting']['content_list'] as $key => $value){
                        $content_exclude_list[] = $key;
                    }
                }

                $content_exclude_file_list=array();
                $content_extension_tmp = array();
                if(isset($options['mu_setting']['content_extension']) && !empty($options['mu_setting']['content_extension']))
                {
                    $str_tmp = explode(',', $options['mu_setting']['content_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $content_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $content_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['mu_setting']['content_extension'] = $content_extension_tmp;
                }

                $other_include_list=array();
                if(isset($options['mu_setting']['additional_file_list']))
                {
                    foreach ($options['mu_setting']['additional_file_list'] as $key => $value){
                        $other_include_list[] = $key;
                    }
                }

                $other_exclude_file_list=array();
                $other_extension_tmp = array();
                if(isset($options['mu_setting']['additional_file_extension']) && !empty($options['mu_setting']['additional_file_extension']))
                {
                    $str_tmp = explode(',', $options['mu_setting']['additional_file_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $other_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $other_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    $options['mu_setting']['other_extension'] = $other_extension_tmp;
                }

                $options['exclude_themes_files']=$themes_exclude_file_list;
                $options['exclude_themes']=$themes_exclude_list;
                $options['exclude_plugins_files']=$plugins_exclude_file_list;
                $options['exclude_plugins']=$plugins_exclude_list;
                $options['exclude_uploads_files']=$upload_exclude_file_list;
                $options['exclude_uploads'] = $upload_exclude_list;
                $options['exclude_content_files']=$content_exclude_file_list;
                $options['exclude_content'] = $content_exclude_list;
                $options['exclude_custom_other_files']=$other_exclude_file_list;
                $options['custom_other_root'] = $other_include_list;
                $options['exclude_custom_other']=array();

                unset($options['backup_files']);
            }
        }
        return $options;
    }

    public function exclude_table($exclude,$data)
    {
        if(isset( $data['blog_prefix']))
        {
            if($data['is_main_site'])
            {
                $exclude = array('/^(?!' . $data['blog_prefix'] . ')/i');
                $exclude[] ='/^' . $data['blog_prefix'] . '\d+_/';
            }
            else
            {
                $exclude = array('/^(?!' . $data['blog_prefix'] . ')/i');
            }

        }

        if(isset($data['exclude_tables']) && isset($data['dump_db']))
        {
            foreach ($data['exclude_tables'] as $table)
            {
                $exclude[] = $table;
            }
        }
        if(isset($data['dump_additional_db']))
        {
            $exclude = array();
        }

        if($data['key'] === 'backup_custom_db') {
            global $wpdb;
            if (is_multisite() && !defined('MULTISITE')) {
                $prefix = $wpdb->base_prefix;
            } else {
                $prefix = $wpdb->get_blog_prefix(0);
            }
            $exclude = array_merge(array_diff($exclude, array('/^(?!' . $prefix . ')/i')));
        }

        return $exclude;
    }

    public function set_backup_type($backup_options,$options)
    {
        if(isset($options['backup_select']))
        {
            if(isset($options['backup_select']['db'])&&$options['backup_select']['db']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_db',$options);
            }
            if(isset($options['backup_select']['themes'])&&$options['backup_select']['themes']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_themes',$options);
            }
            if(isset($options['backup_select']['plugin'])&&$options['backup_select']['plugin']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_plugin',$options);
            }
            if(isset($options['backup_select']['uploads'])&&$options['backup_select']['uploads']==1)
            {
                if(isset($backup_options['compress']['subpackage_plugin_upload'])&&$backup_options['compress']['subpackage_plugin_upload'])
                {
                    $backup_type='backup_custom_uploads_files';
                }
                else{
                    $backup_type='backup_custom_uploads';
                }
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,$backup_type,$options);
            }
            if(isset($options['backup_select']['content'])&&$options['backup_select']['content']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_content',$options);
            }
            if(isset($options['backup_select']['core'])&&$options['backup_select']['core']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_core',$options);
            }
            if(isset($options['backup_select']['other'])&&$options['backup_select']['other']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_custom_other',$options);
            }
            if(isset($options['backup_select']['additional_db'])&&$options['backup_select']['additional_db']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_additional_db',$options);
            }

            if(isset($options['backup_select']['mu_site'])&&$options['backup_select']['mu_site']==1)
            {
                $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_mu_sites',$options);
            }
            //
        }
        return $backup_options;
    }

    public function set_backup_ex($backup_options)
    {
        if(isset($backup_options['dump_db']))
        {
            $remote_ids=WPvivid_Setting::get_user_history('remote_selected');
            $upload_options=WPvivid_Setting::get_option('wpvivid_upload_setting');

            $backup_options['json_info']['export_setting']=array();
            $backup_options['json_info']['export_setting']['remote_selected']=$remote_ids;
            $backup_options['json_info']['export_setting']['wpvivid_upload_setting']=$upload_options;
        }
        if(isset($backup_options['uploads_subpackage'])||isset($backup_options['plugin_subpackage']))
        {
            $backup_options['resume_packages'] = 1;
        }
        else if($backup_options['key']==WPVIVID_PRO_BACKUP_TYPE_MERGE)
        {
            $backup_options['resume_packages'] = 1;
        }
        return $backup_options;
    }

    public function set_remote_options($remote_options, $options)
    {
        if($remote_options!==false&&isset($options['backup_to']))
        {
            $remote_folder='';

            if($options['backup_to']=='local')
            {
                return $remote_options;
            }
            else if($options['backup_to']=='migrate_remote')
            {
                $remote_folder='migrate';
            }
            else if($options['backup_to']=='staging_remote')
            {
                $remote_folder='staging';
            }
            else if($options['backup_to']=='rollback_remote'){

                $remote_folder='rollback';
            }
            else if($options['backup_to']=='auto_migrate')
            {
                return $remote_options;
            }

            foreach ($remote_options as $key=>$remote_option)
            {
                if(!empty($remote_folder))
                {
                    if($options['backup_to']=='rollback_remote')
                    {
                        if(isset($remote_options[$key]['custom_path']))
                        {
                            $remote_options[$key]['custom_path'] = $remote_options[$key]['custom_path'].'/'.$remote_folder;
                        }
                        else
                        {
                            $remote_options[$key]['path'] = $remote_options[$key]['path'].'/'.$remote_folder;
                        }
                    }
                    else {

                        if(isset($remote_options[$key]['custom_path']))
                        {
                            $remote_options[$key]['custom_path']=$remote_folder;
                        }
                        else
                        {
                            $remote_options[$key]['path'] =$remote_folder;
                        }
                    }
                }
            }
        }

        return $remote_options;
    }

    public function get_need_backup_files($files,$backup_data,$option)
    {
        if(isset($backup_data['files_root']))
            $root=$backup_data['files_root'];
        else
            return $files;

        if(isset($backup_data['exclude_regex']))
            $exclude_folder_regex=$backup_data['exclude_regex'];
        else
            $exclude_folder_regex=array();

        if(isset($backup_data['include_regex']))
            $include_folder_regex=$backup_data['include_regex'];
        else
            $include_folder_regex=array();

        if(isset($backup_data['exclude_files_regex']))
            $exclude_files_regex=$backup_data['exclude_files_regex'];
        else
            $exclude_files_regex=array();

        if(isset($backup_data['skip_files_time']))
        {
            $skip_files_time=$backup_data['skip_files_time'];
        }
        else
        {
            $skip_files_time=0;
        }

        $exclude_file_size=$option['exclude_file_size'];

        if(isset($backup_data['uploads_subpackage']))
        {
            $files[]=$root;
            return $this->get_need_uploads_backup_folder($files,$root,$exclude_folder_regex,$include_folder_regex,$exclude_files_regex,$exclude_file_size);
        }

        if(isset($backup_data['plugin_subpackage']))
        {
            return $this->get_need_backup_folder($files,$root,$exclude_folder_regex,$include_folder_regex,$exclude_files_regex,$exclude_file_size);
        }

        if(isset($backup_data['custom_other']))
        {
            foreach ($backup_data['custom_other_root'] as $custom_root)
            {
                $files=$this->get_file_list($files,$custom_root,$exclude_folder_regex,$include_folder_regex,$exclude_files_regex,$exclude_file_size,$skip_files_time);
            }
            return $files;
        }

        return $this->get_file_list($files,$root,$exclude_folder_regex,$include_folder_regex,$exclude_files_regex,$exclude_file_size,$skip_files_time);
    }

    public function get_need_backup_files_size($files,$backup_data,$option)
    {
        if(isset($backup_data['files_root']))
            $root=$backup_data['files_root'];
        else
            return $files;

        if(isset($backup_data['exclude_regex']))
            $exclude_folder_regex=$backup_data['exclude_regex'];
        else
            $exclude_folder_regex=array();

        if(isset($backup_data['include_regex']))
            $include_folder_regex=$backup_data['include_regex'];
        else
            $include_folder_regex=array();

        if(isset($backup_data['exclude_files_regex']))
            $exclude_files_regex=$backup_data['exclude_files_regex'];
        else
            $exclude_files_regex=array();

        if(isset($backup_data['skip_files_time']))
        {
            $skip_files_time=$backup_data['skip_files_time'];
        }
        else
        {
            $skip_files_time=0;
        }

        $exclude_file_size=$option['exclude_file_size'];

        if(isset($backup_data['custom_other']))
        {
            foreach ($backup_data['custom_other_root'] as $custom_root)
            {
                $files=$this->get_file_list($files,$custom_root,$exclude_folder_regex,$include_folder_regex,$exclude_files_regex,$exclude_file_size,$skip_files_time);
            }
            return $files;
        }

        return $this->get_file_list($files,$root,$exclude_folder_regex,$include_folder_regex,$exclude_files_regex,$exclude_file_size,$skip_files_time);
    }

    public function exclude_plugins($exclude_plugins)
    {
        $exclude_plugins[]='wpvivid-backuprestore';
        //$exclude_plugins[]='wp-cerber';
        $exclude_plugins[]='.';
        $exclude_plugins[]='wpvivid-backup-pro';
        $exclude_plugins[]='wpvividdashboard';
        //$exclude_plugins[]='wpvivid-staging';
        return $exclude_plugins;
    }

    public function enable_plugins_list($plugin_list)
    {
        $plugin_list[]='wpvivid-backup-pro/wpvivid-backup-pro.php';
        return $plugin_list;
    }

    public function need_clean_oldest_backup($need,$backup_options)
    {
        if($backup_options['action']=='backup_remote')
        {
            return false;
        }
        else
        {
            return $need;
        }
    }

    public function check_backup_size($check,$backup_option)
    {
        if(isset($backup_option['backup_select']))
        {
            if(isset($backup_option['backup_select']['db'])&&$backup_option['backup_select']['db'])
            {
                $check['check_db']=true;
            }

            if(isset($backup_option['backup_select']['themes'])&&$backup_option['backup_select']['themes'])
            {
                $check['check_file']=true;
            }
            else if(isset($backup_option['backup_select']['plugin'])&&$backup_option['backup_select']['plugin'])
            {
                $check['check_file']=true;
            }
            else if(isset($backup_option['backup_select']['uploads'])&&$backup_option['backup_select']['uploads'])
            {
                $check['check_file']=true;
            }
            else if(isset($backup_option['backup_select']['content'])&&$backup_option['backup_select']['content'])
            {
                $check['check_file']=true;
            }
            else if(isset($backup_option['backup_select']['other'])&&$backup_option['backup_select']['other'])
            {
                $check['check_file']=true;
            }
            else if(isset($backup_option['backup_select']['core'])&&$backup_option['backup_select']['core'])
            {
                $check['check_file']=true;
            }
        }
        return $check;
    }

    public function set_backup_ismerge($ismerge,$options)
    {
        if(isset($options['backup_files'])&&$options['backup_files']=='db')
        {
            $ismerge=0;
        }

        if(isset($options['backup_select'])&&isset($options['backup_select']['db']))
        {
            $counts = array_count_values($options['backup_select']);
            if($counts[1]==1&&$options['backup_select']['db']==1)
            {
                $ismerge=0;
            }
        }
        return $ismerge;
    }

    public function set_custom_backup($backup_options,$backup_type,$options)
    {
        $backup_data['key']=$backup_type;
        $backup_data['result']=false;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;

        if($backup_type=='backup_custom_db')
        {
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_CUSTOM;
            if(isset($options['exclude_tables']))
            {
                $backup_data['exclude_tables']=$options['exclude_tables'];
            }
            else
            {
                $backup_data['exclude_tables']=array();
            }

            $backup_data['dump_db']=1;
            $backup_data['sql_file_name']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_options['dir'].DIRECTORY_SEPARATOR.$backup_options['prefix'].'_backup_db.sql';
            $backup_data['json_info']['dump_db']=1;
            $backup_data['json_info']['file_type']='databases';
            if(is_multisite())
            {
                $backup_data['json_info']['is_mu']=1;
            }
            else
            {
                $backup_data['json_info']['home_url']=home_url();
            }
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_db';
        }
        else if($backup_type=='backup_additional_db')
        {
            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1'){
                $history = WPvivid_Custom_Backup_Manager_Ex::wpvivid_get_custom_settings_ex();
                if(empty($history)){
                    $history = array();
                }
                $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_CUSTOM;
                if(isset($history['additional_database_option'][$options['custom_dirs']['additional_server_select']]['additional_database_list'])){
                    foreach ($history['additional_database_option'][$options['custom_dirs']['additional_server_select']]['additional_database_list'] as $database => $db_info){
                        if (in_array($database, $options['custom_dirs']['additional_database_list'])) {
                            $backup_data['additional_database_list'][$database]=$db_info;
                        }
                    }
                }
                else{
                    $backup_data['additional_database_list']=array();
                }

                $backup_data['dump_additional_db']=1;
                foreach ($backup_data['additional_database_list'] as $database => $db_info)
                {
                    $backup_data['json_info']['database'][] = $database;
                    $sql_info['database'] = $database;
                    $sql_info['host'] = $db_info['db_host'];
                    $sql_info['user'] = $db_info['db_user'];
                    $sql_info['pass'] = $db_info['db_pass'];
                    $sql_info['file_name'] = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_options['dir'].DIRECTORY_SEPARATOR.$backup_options['prefix'].'_backup_additional_db_'.$database.'.sql';
                    $backup_data['sql_file_name'][] = $sql_info;
                }
            }*/
            //else{
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_CUSTOM;
            if(isset($options['additional_database_list']))
            {
                $backup_data['additional_database_list']=$options['additional_database_list'];
            }
            else
            {
                $backup_data['additional_database_list']=array();
            }

            $backup_data['dump_additional_db']=1;
            foreach ($backup_data['additional_database_list'] as $database => $db_info)
            {
                $backup_data['json_info']['database'][] = $database;
                $sql_info['database'] = $database;
                $sql_info['host'] = $db_info['db_host'];
                $sql_info['user'] = $db_info['db_user'];
                $sql_info['pass'] = $db_info['db_pass'];
                $sql_info['file_name'] = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_options['dir'].DIRECTORY_SEPARATOR.$backup_options['prefix'].'_backup_additional_db_'.$database.'.sql';
                $backup_data['sql_file_name'][] = $sql_info;
            }
            //}

            $backup_data['json_info']['dump_additional_db']=1;
            $backup_data['json_info']['file_type']='additional_databases';
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_additional_db';
        }
        else if($backup_type=='backup_custom_themes')
        {
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT;
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_themes';
            $backup_data['files_root']=$this->transfer_path(get_theme_root());
            $backup_data['exclude_regex']=array();

            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1')
            {
                $themes_path = str_replace('\\','/', get_theme_root());
                $themes_path = $themes_path.'/';

                if(isset($options['custom_dirs']['exclude_list']))
                {
                    foreach($options['custom_dirs']['exclude_list'] as $key => $value)
                    {
                        if(strpos($key, $themes_path) !== false){
                            $themes = str_replace($themes_path, '', $key);
                            $options['exclude_themes'][] = $themes;
                            $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'$#';
                        }
                    }
                }
            }*/
            //else{
            if(isset($options['exclude_themes']))
            {
                foreach ($options['exclude_themes'] as $themes)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'$#';
                }
            }
            if(isset($options['exclude_themes_files']))
            {
                foreach ($options['exclude_themes_files'] as $file)
                {
                    $backup_data['exclude_files_regex'][]='#'.$file.'#';
                }
            }
            //
            if(isset($options['exclude_themes_folder']))
            {
                foreach ($options['exclude_themes_folder'] as $theme_folder)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$theme_folder), '/').'#';
                }
            }
            //
            $backup_data['include_regex']=array();
            if(isset($options['include_themes']))
            {
                foreach ($options['include_themes'] as $themes)
                {
                    $backup_data['include_regex'][]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'#';
                }
            }
            //}
            $backup_data['json_info']['file_type']='themes';
            $backup_data['json_info']['themes']=$this->get_themes_list($options['exclude_themes'],false);
        }
        else if($backup_type=='backup_custom_plugin')
        {
            if(isset($backup_data['compress']['subpackage_plugin_upload'])&&$backup_data['compress']['subpackage_plugin_upload'])
            {
                $backup_data['plugin_subpackage']=1;
            }
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT;
            $backup_data['prefix']=$backup_options['prefix'].'_backup_plugin';
            $backup_data['files_root']=$this->transfer_path(WP_PLUGIN_DIR);

            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1'){
                $plugins_path = str_replace('\\','/', WP_PLUGIN_DIR);
                $plugins_path = $plugins_path.'/';

                if(isset($options['custom_dirs']['exclude_list']))
                {
                    foreach($options['custom_dirs']['exclude_list'] as $key => $value)
                    {
                        if(strpos($key, $plugins_path) !== false){
                            $plugins = str_replace($plugins_path, '', $key);
                            $options['exclude_plugins'][] = $plugins;
                        }
                    }
                    if(isset($options['exclude_plugins']))
                    {
                        $exclude_plugins=$options['exclude_plugins'];
                    }
                    else
                    {
                        $exclude_plugins=array();
                    }
                }
                else{
                    $exclude_plugins=array();
                }
                $exclude_plugins=apply_filters('wpvivid_exclude_plugins',$exclude_plugins);
                $exclude_regex=array();
                foreach ($exclude_plugins as $exclude_plugin)
                {
                    $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$exclude_plugin), '/').'#';
                }

                $backup_data['exclude_regex']=$exclude_regex;
                if(isset($options['exclude_plugins']))
                {
                    foreach ($options['exclude_plugins'] as $plugins)
                    {
                        $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                    }
                }

                if(isset($options['exclude_plugins_folder']))
                {
                    foreach ($options['exclude_plugins_folder'] as $plugin_folder)
                    {
                        $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugin_folder), '/').'#';
                    }
                }

                $backup_data['include_regex']=array();
                $include_plugins_array = array();
                if(isset($options['include_plugins']))
                {
                    $include_plugins_array = $options['include_plugins'];
                    foreach ($options['include_plugins'] as $plugins)
                    {
                        $backup_data['include_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                    }
                }
            }*/
            //else{
            if(isset($options['exclude_plugins']))
            {
                $exclude_plugins=$options['exclude_plugins'];
            }
            else
            {
                $exclude_plugins=array();
            }

            $exclude_plugins=apply_filters('wpvivid_exclude_plugins',$exclude_plugins);
            $exclude_regex=array();
            foreach ($exclude_plugins as $exclude_plugin)
            {
                $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$exclude_plugin), '/').'#';
            }

            $backup_data['exclude_regex']=$exclude_regex;
            if(isset($options['exclude_plugins']))
            {
                foreach ($options['exclude_plugins'] as $plugins)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                }
            }

            if(isset($options['exclude_plugins_files']))
            {
                foreach ($options['exclude_plugins_files'] as $file)
                {
                    $backup_data['exclude_files_regex'][]='#'.$file.'#';
                }
            }

            if(isset($options['exclude_plugins_folder']))
            {
                foreach ($options['exclude_plugins_folder'] as $plugin_folder)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugin_folder), '/').'#';
                }
            }

            $backup_data['include_regex']=array();
            $include_plugins_array = array();
            if(isset($options['include_plugins']))
            {
                $include_plugins_array = $options['include_plugins'];
                foreach ($options['include_plugins'] as $plugins)
                {
                    $backup_data['include_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
                }
            }
            //}
            $backup_data['json_info']['file_type']='plugin';
            $backup_data['json_info']['plugin']=$this->get_plugins_list($exclude_plugins,$include_plugins_array,false);
        }
        else if($backup_type=='backup_custom_uploads')
        {
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT;
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_uploads';
            $upload_dir = wp_upload_dir();
            $backup_data['files_root']=$this -> transfer_path($upload_dir['basedir']);
            $exclude_regex=array();
            $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,$backup_type);
            $backup_data['exclude_regex']=$exclude_regex;

            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1'){
                $upload_dir = wp_upload_dir();
                $path = $upload_dir['basedir'];
                $path = str_replace('\\','/',$path);
                $uploads_path = $path.'/';

                if(isset($options['custom_dirs']['exclude_list']))
                {
                    foreach($options['custom_dirs']['exclude_list'] as $key => $value)
                    {
                        if(strpos($key, $uploads_path) !== false){
                            $uploads = str_replace($uploads_path, '', $key);
                            $options['exclude_uploads'][] = $uploads;
                            $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.$uploads), '/').'$#';
                        }
                    }
                }

                $backup_data['exclude_files_regex']=array();
                $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],$backup_type);
                $upload_exclude_file_list=array();
                $upload_extension_tmp = array();
                if(isset($options['custom_dirs']['file_type_extension']) && !empty($options['custom_dirs']['file_type_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['file_type_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $upload_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $upload_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    if(isset($upload_exclude_file_list))
                    {
                        foreach ($upload_exclude_file_list as $file)
                        {
                            $backup_data['exclude_files_regex'][]='#'.$file.'#';
                        }
                    }
                }
            }*/
            //else{
            if(isset($options['exclude_uploads']))
            {
                foreach ($options['exclude_uploads'] as $uploads)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.$uploads), '/').'$#';
                }
            }

            $backup_data['exclude_files_regex']=array();
            $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],$backup_type);
            if(isset($options['exclude_uploads_files']))
            {
                foreach ($options['exclude_uploads_files'] as $file)
                {
                    $backup_data['exclude_files_regex'][]='#'.$file.'#';
                }
            }
            //}

            $backup_data['include_regex']=array();
            $backup_data['json_info']['file_type']='upload';
        }
        else if($backup_type=='backup_custom_uploads_files')
        {
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT;
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_uploads';
            $backup_data['uploads_subpackage']=1;
            $upload_dir = wp_upload_dir();
            $backup_data['files_root']=$this -> transfer_path($upload_dir['basedir']);
            $exclude_regex=array();
            $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,$backup_type);
            $backup_data['exclude_regex']=$exclude_regex;

            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1'){
                $upload_dir = wp_upload_dir();
                $path = $upload_dir['basedir'];
                $path = str_replace('\\','/',$path);
                $uploads_path = $path.'/';

                if(isset($options['custom_dirs']['exclude_list']))
                {
                    foreach($options['custom_dirs']['exclude_list'] as $key => $value)
                    {
                        if(strpos($key, $uploads_path) !== false){
                            $uploads = str_replace($uploads_path, '', $key);
                            $options['exclude_uploads'][] = $uploads;
                            $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.$uploads), '/').'$#';
                        }
                    }
                }

                $backup_data['exclude_files_regex']=array();
                $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],$backup_type);
                $upload_exclude_file_list=array();
                $upload_extension_tmp = array();
                if(isset($options['custom_dirs']['file_type_extension']) && !empty($options['custom_dirs']['file_type_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['file_type_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $upload_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $upload_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    if(isset($upload_exclude_file_list))
                    {
                        foreach ($upload_exclude_file_list as $file)
                        {
                            $backup_data['exclude_files_regex'][]='#'.$file.'#';
                        }
                    }
                }
            }*/
            //else{
            if(isset($options['exclude_uploads']))
            {
                foreach ($options['exclude_uploads'] as $uploads)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.$uploads), '/').'#';
                }
            }

            $backup_data['exclude_files_regex']=array();
            $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],$backup_type);
            if(isset($options['exclude_uploads_files']))
            {
                foreach ($options['exclude_uploads_files'] as $file)
                {
                    $backup_data['exclude_files_regex'][]='#'.$file.'#';
                }
            }
            //}
            $backup_data['include_regex']=array();
            $backup_data['json_info']['file_type']='upload';
        }
        else if($backup_type=='backup_custom_content')
        {
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_ROOT;
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_content';
            $backup_data['files_root']=$this -> transfer_path(WP_CONTENT_DIR);
            $exclude_regex=array();
            $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,$backup_type);
            $backup_data['exclude_regex']=$exclude_regex;
            $backup_data['include_regex']=array();
            $backup_data['exclude_files_regex']=array();
            $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],$backup_type);

            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1'){
                $content_dir = WP_CONTENT_DIR;
                $path = str_replace('\\','/',$content_dir);
                $content_path = $path.'/';

                if(isset($options['custom_dirs']['exclude_list']))
                {
                    foreach($options['custom_dirs']['exclude_list'] as $key => $value)
                    {
                        if(strpos($key, $content_path) !== false){
                            $content = str_replace($content_path, '', $key);
                            $options['exclude_content'][] = $content;
                            $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$content), '/').'#';
                        }
                    }
                }

                $content_exclude_file_list=array();
                $content_extension_tmp = array();
                if(isset($options['custom_dirs']['file_type_extension']) && !empty($options['custom_dirs']['file_type_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['file_type_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $content_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $content_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    if(isset($content_exclude_file_list))
                    {
                        foreach ($content_exclude_file_list as $file)
                        {
                            $backup_data['exclude_files_regex'][]='#'.$file.'#';
                        }
                    }
                }
            }*/
            //else{
            if(isset($options['exclude_content']))
            {
                foreach ($options['exclude_content'] as $uploads)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$uploads), '/').'#';
                }
            }

            if(isset($options['exclude_content_files']))
            {
                foreach ($options['exclude_content_files'] as $file)
                {
                    $backup_data['exclude_files_regex'][]='#'.$file.'#';
                }
            }
            //}

            $backup_data['json_info']['file_type']='wp-content';
        }
        else if($backup_type=='backup_custom_core')
        {
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_ROOT;
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_core';
            if(!function_exists('get_home_path'))
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            $backup_data['files_root']=$this -> transfer_path(get_home_path());
            $backup_data['json_info']['include_path'][]='wp-includes';
            $backup_data['json_info']['include_path'][]='wp-admin';
            $backup_data['json_info']['include_path'][]='lotties';
            $backup_data['json_info']['wp_core']=1;
            $backup_data['json_info']['home_url']=home_url();

            $include_regex[]='#^'.preg_quote($this -> transfer_path(get_home_path().'wp-admin'), '/').'#';
            $include_regex[]='#^'.preg_quote($this->transfer_path(get_home_path().'wp-includes'), '/').'#';
            $include_regex[]='#^'.preg_quote($this->transfer_path(get_home_path().'lotties'), '/').'#';
            $exclude_regex=array();
            $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,$backup_type);
            $backup_data['exclude_regex']=$exclude_regex;
            $backup_data['include_regex']=$include_regex;
            $backup_data['exclude_files_regex']=array();
            $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],$backup_type);

            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1'){
                if(!function_exists('get_home_path'))
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                $home_path = str_replace('\\','/', get_home_path());

                if(isset($options['custom_dirs']['exclude_list']))
                {
                    foreach($options['custom_dirs']['exclude_list'] as $key => $value)
                    {
                        if(strpos($key, $home_path) !== false){
                            $core = str_replace($home_path, '', $key);
                            $options['exclude_core'][] = $core;
                            $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_home_path().$core), '/').'#';
                        }
                    }
                }
            }*/

            $backup_data['json_info']['file_type']='wp-core';
        }
        else if($backup_type=='backup_custom_other')
        {
            $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_ROOT;
            $backup_data['custom_other']=1;
            $backup_data['prefix'] = $backup_options['prefix'] . '_backup_other';
            if(!function_exists('get_home_path'))
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            $backup_data['files_root']=$this -> transfer_path(get_home_path());
            $backup_data['json_info']['home_url']=home_url();

            $backup_data['exclude_regex']=array();
            $backup_data['include_regex']=array();
            $backup_data['exclude_files_regex']=array();

            /*if(isset($options['custom_dirs']['use_new_ui']) && $options['custom_dirs']['use_new_ui'] == '1'){
                if(!function_exists('get_home_path'))
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                $home_path = str_replace('\\','/', get_home_path());

                $path = trailingslashit(str_replace('\\', '/', realpath($home_path)));

                if ($dh = opendir($path)) {
                    while (substr($path, -1) == '/') {
                        $path = rtrim($path, '/');
                    }
                    $skip_paths = array(".", "..");
                    while (($value = readdir($dh)) !== false) {
                        trailingslashit(str_replace('\\', '/', $value));

                        if (!in_array($value, $skip_paths)) {
                            if (is_dir($path . '/' . $value)) {
                                $wp_admin_path = ABSPATH . 'wp-admin';
                                $wp_admin_path = str_replace('\\', '/', $wp_admin_path);

                                $wp_include_path = ABSPATH . 'wp-includes';
                                $wp_include_path = str_replace('\\', '/', $wp_include_path);

                                $content_dir = WP_CONTENT_DIR;
                                $content_dir = str_replace('\\', '/', $content_dir);
                                $content_dir = rtrim($content_dir, '/');

                                $exclude_dir = array($wp_admin_path, $wp_include_path, $content_dir);
                                if (!in_array($path . '/' . $value, $exclude_dir)) {
                                    $backup_data['custom_other_root'][]= $this -> transfer_path(get_home_path().$value);
                                }
                            }
                        }
                    }
                }

                if(isset($options['custom_dirs']['exclude_list']))
                {
                    foreach($options['custom_dirs']['exclude_list'] as $key => $value)
                    {
                        if(strpos($key, $home_path) !== false){
                            $additional = str_replace($home_path, '', $key);
                            $options['exclude_custom_other'][] = $additional;
                            $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_home_path().$additional), '/').'#';
                        }
                    }
                }

                $other_exclude_file_list=array();
                $other_extension_tmp = array();
                if(isset($options['custom_dirs']['file_type_extension']) && !empty($options['custom_dirs']['file_type_extension']))
                {
                    $str_tmp = explode(',', $options['custom_dirs']['file_type_extension']);
                    for($index=0; $index<count($str_tmp); $index++){
                        if(!empty($str_tmp[$index])) {
                            $other_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                            $other_extension_tmp[] = $str_tmp[$index];
                        }
                    }
                    if(isset($other_exclude_file_list))
                    {
                        foreach ($other_exclude_file_list as $file)
                        {
                            $backup_data['exclude_files_regex'][]='#'.$file.'#';
                        }
                    }
                }
            }*/
            //else{
            if(isset($options['exclude_custom_other']))
            {
                foreach ($options['exclude_custom_other'] as $other)
                {
                    $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_home_path().$other), '/').'#';
                }
            }

            if(isset($options['exclude_custom_other_files']))
            {
                foreach ($options['exclude_custom_other_files'] as $file)
                {
                    $backup_data['exclude_files_regex'][]='#'.$file.'#';
                }
            }

            if(isset($options['custom_other_root']))
            {
                foreach ($options['custom_other_root'] as $other)
                {
                    $backup_data['custom_other_root'][]=$this -> transfer_path(get_home_path().$other);
                }
            }
            //}

            $backup_data['json_info']['file_type']='custom';
        }
        else
        {
            $backup_data=false;
        }
        if($backup_data!==false)
        {
            $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
            $backup_options['backup'][$backup_data['key']]=$backup_data;
        }
        return $backup_options;
    }

    public function get_backup_exclude_regex($exclude_regex,$backup_type)
    {
        if($backup_type=='backup_custom_uploads_files'||$backup_type=='backup_custom_uploads')
        {
            /*Replaced by check_custom_backup_default_exclude hook*/

            //$upload_dir = wp_upload_dir();
            //$backup_data['files_root']=$this -> transfer_path($upload_dir['basedir']);
            //$exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir']).DIRECTORY_SEPARATOR.'ShortpixelBackups', '/').'#';//ShortpixelBackups
            //$exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir']).DIRECTORY_SEPARATOR.'backup', '/').'#';
            //$exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir']).DIRECTORY_SEPARATOR.'backwpup', '/').'#';  // BackWPup backup directory
            //$exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir']).DIRECTORY_SEPARATOR.'backup-guard', '/').'#';  // Wordpress Backup and Migrate Plugin backup directory
        }
        else if($backup_type=='backup_custom_content')
        {
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'updraft', '/').'#';   // Updraft Plus backup directory
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'ai1wm-backups', '/').'#'; // All-in-one WP migration backup directory
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'backups', '/').'#'; // Xcloner backup directory
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'upgrade', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'wpvivid', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir(), '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'plugins', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'cache', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'wphb-cache', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'backup', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'Dropbox_Backup', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'backups-dup-pro', '/').'#';

            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'backup-migration', '/').'#';
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.'backups-dup-lite', '/').'#';

            if(defined('WPVIVID_UPLOADS_ISO_DIR'))
            {
                $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR).DIRECTORY_SEPARATOR.WPVIVID_UPLOADS_ISO_DIR, '/').'#';
            }
            $upload_dir = wp_upload_dir();
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir']), '/').'$#';
            $exclude_regex[]='#^'.preg_quote($this->transfer_path(get_theme_root()), '/').'#';
        }

        return $exclude_regex;
    }

    public function get_backup_exclude_files_regex($exclude_files_regex,$backup_type)
    {
        if($backup_type=='backup_custom_content')
        {
            $exclude_files_regex[]='#mysql.sql#';
        }
        else if($backup_type=='backup_custom_core')
        {
            $exclude_files_regex[]='#pclzip-.*\.tmp#';
            $exclude_files_regex[]='#pclzip-.*\.gz#';
            $exclude_files_regex[]='#session_mm_cgi-fcgi#';
        }
        return $exclude_files_regex;
    }

    public function wpvivid_archieve_database_info($databases, $data){
        if(isset($data['dump_additional_db'])){
            $databases =$data['sql_file_name'];
        }
        return $databases;
    }

    public function wpvivid_check_type_database($is_type_db, $data){
        if(isset($data['dump_additional_db'])){
            $is_type_db = true;
        }
        return $is_type_db;
    }

    public function wpvivid_check_additional_database($is_additional_db, $data){
        if(isset($data['dump_additional_db'])){
            $is_additional_db = true;
        }
        return $is_additional_db;
    }

    public function wpvivid_additional_database_display_ex($html){
        $html = '';
        $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
        if (empty($history))
        {
            $history = array();
        }
        if(isset($history['additional_database_option']))
        {
            if(isset($history['additional_database_option']['additional_database_list']))
                foreach ($history['additional_database_option']['additional_database_list'] as $database => $db_info)
                {
                    $html .= '<div style="border: 1px solid #e5e5e5; border-bottom: 0; height: 30px; line-height: 30px;">
                                <div class="wpvivid-additional-database" option="additional_db_custom" name="'.$database.'" style="margin-left: 10px; float: left;">'.$database.'</div>
                                <div class="wpvivid-additional-database wpvivid-additional-database-remove" database-name="'.$database.'" style="margin-right: 10px; float: right; cursor: pointer;">X</div>
                                <div style="clear: both;"></div>
                              </div>';
                }
        }
        return $html;
    }

    public function check_backup_completeness($check_res, $task_id){
        $task=WPvivid_taskmanager::get_task($task_id);
        if(isset($task['options']['backup_options']['ismerge'])){
            if($task['options']['backup_options']['ismerge'] == '1'){
                foreach ($task['options']['backup_options']['backup']['backup_merge']['result']['files'] as $file_info){
                    $file_name = $file_info['file_name'];
                    if(!$this->check_backup_file_json($file_name)){
                        $check_res = false;
                    }
                }
            }
            else{
                foreach ($task['options']['backup_options']['backup'] as $key => $value){
                    foreach ($value['result']['files'] as $file_info){
                        $file_name = $file_info['file_name'];
                        if(!$this->check_backup_file_json($file_name)){
                            $check_res = false;
                        }
                    }
                }
            }
        }
        return $check_res;
    }

    public function wpvivid_additional_server_list($html){
        $html = '';
        $history = WPvivid_Custom_Backup_Manager_Ex::wpvivid_get_custom_settings_ex();
        if (empty($history))
        {
            $history = array();
        }

        $select_server = '';
        if(isset($history['additional_database_option']['selected_server'])){
            $select_server = $history['additional_database_option']['selected_server'];
        }
        if(isset($history['additional_database_option'])){
            foreach ($history['additional_database_option'] as $server => $db_list){
                if($server === 'selected_server'){
                    continue;
                }

                if($server === $select_server){
                    $html .= '<div class="wpvivid-text-line wpvivid-text-selected" server="'.$server.'"><span class="dashicons dashicons-trash wpvivid-icon-16px"></span><span class="wpvivid-text-line">'.$server.'</span></div>';
                }
                else{
                    $html .= '<div class="wpvivid-text-line" server="'.$server.'"><span class="dashicons dashicons-trash wpvivid-icon-16px"></span><span class="wpvivid-text-line">'.$server.'</span></div>';
                }
            }
        }
        return $html;
    }

    public function wpvivid_additional_database_list($html){
        $html = '';
        $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
        if(empty($history)){
            $history = array();
        }

        if(isset($history['additional_database_option'])){
            if(isset($history['additional_database_option']['additional_database_list'])) {
                foreach ($history['additional_database_option']['additional_database_list'] as $database => $db_info)
                {
                    $html .= '<div class="wpvivid-text-line" database-name="'.$database.'" database-host="'.$db_info['db_host'].'" database-user="'.$db_info['db_user'].'" database-pass="'.$db_info['db_pass'].'"><span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-additional-database-remove" database-name="'.$database.'"></span><span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue wpvivid-icon-16px-nopointer"></span><span class="wpvivid-text-line" option="additional_db_custom" name="'.$database.'">'.$database.'@'.$db_info['db_host'].'</span></div>';
                }
            }
        }
        return $html;
    }
    /***** backup filters end *****/

    /***** useful function begin *****/
    public function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode(DIRECTORY_SEPARATOR,$values);
    }

    public function get_themes_list($exclude_themes,$get_size=true)
    {
        $themes_list=array();
        $list=wp_get_themes();
        foreach ($list as $key=>$item)
        {
            if(!empty($exclude_themes) && in_array($key,$exclude_themes))
            {
                continue;
            }
            $themes_list[$key]['slug']=$key;
            if($get_size)
                $themes_list[$key]['size']=self::get_folder_size(get_theme_root().DIRECTORY_SEPARATOR.$key,0);
        }
        return $themes_list;
    }

    public function get_plugins_list($exclude_plugins,$include_plugins=array(),$get_size=true)
    {
        if(!empty($include_plugins))
        {
            $plugins_list=array();
            if(!function_exists('get_plugins'))
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $list=get_plugins();

            foreach ($list as $key=>$item)
            {
                if(in_array(dirname($key),$include_plugins))
                {
                    $plugins_list[dirname($key)]['slug']=dirname($key);
                    if($get_size)
                        $plugins_list[dirname($key)]['size']=self::get_folder_size(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.dirname($key),0);
                }
            }
            return $plugins_list;
        }
        else
        {
            $plugins_list=array();
            if(!function_exists('get_plugins'))
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $list=get_plugins();

            $exclude_plugins=array();
            $exclude_plugins=apply_filters('wpvivid_exclude_plugins',$exclude_plugins);

            foreach ($list as $key=>$item)
            {
                if(in_array(dirname($key),$exclude_plugins))
                {
                    continue;
                }
                $plugins_list[dirname($key)]['slug']=dirname($key);
                if($get_size)
                    $plugins_list[dirname($key)]['size']=self::get_folder_size(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.dirname($key),0);
            }
            return $plugins_list;
        }
    }

    public function get_need_uploads_backup_folder($files,$root,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size)
    {
        $this->getUploadsFolder($files,$root,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size);
        return $files;
    }

    public function getUploadsFolder(&$files,$path,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size=0,$include_dir = true)
    {
        $count = 0;
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..")
                    {
                        $count++;

                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            if ($this->regex_match($exclude_regex, $path . DIRECTORY_SEPARATOR . $filename, 0))
                            {
                                if ($this->regex_match($include_regex, $path . DIRECTORY_SEPARATOR . $filename, 1))
                                {
                                    $this->getUploadsFolder($files,$path . DIRECTORY_SEPARATOR . $filename,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size,$include_dir);
                                }
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        if($include_dir && $count == 0)
        {
            $files[] = $path;
        }
    }

    public function get_need_backup_folder($files,$root,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size)
    {
        $this->getFolder($files,$root,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size);
        return $files;
    }

    public function getFolder(&$files,$path,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size=0,$include_dir = true)
    {
        $count = 0;
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..")
                    {
                        $count++;

                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            if ($this->regex_match($exclude_regex, $path . DIRECTORY_SEPARATOR . $filename, 0))
                            {
                                if ($this->regex_match($include_regex, $path . DIRECTORY_SEPARATOR . $filename, 1))
                                {
                                    $files[] = $path . DIRECTORY_SEPARATOR . $filename;
                                }
                            }
                        } else {
                            if($this->regex_match($exclude_files_regex, $filename, 0))
                            {
                                if ($exclude_file_size == 0)
                                {
                                    if(is_readable($path . DIRECTORY_SEPARATOR . $filename))
                                    {
                                        $files[] = $path . DIRECTORY_SEPARATOR . $filename;
                                    }
                                    else
                                    {
                                        global $wpvivid_backup_pro;
                                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('file not readable:' . $path . DIRECTORY_SEPARATOR . $filename, 'notice');
                                    }
                                } else {
                                    if(is_readable($path . DIRECTORY_SEPARATOR . $filename))
                                    {
                                        if (filesize($path . DIRECTORY_SEPARATOR . $filename) < $exclude_file_size * 1024 * 1024)
                                        {
                                            $files[] = $path . DIRECTORY_SEPARATOR . $filename;
                                        }
                                    }
                                    else
                                    {
                                        global $wpvivid_backup_pro;
                                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('file not readable:' . $path . DIRECTORY_SEPARATOR . $filename, 'notice');
                                    }
                                }
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        if($include_dir && $count == 0)
        {
            $files[] = $path;
        }
    }

    public function get_file_list($files,$root,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size,$skip_files_time)
    {
        $this->getFileLoop($files,$root,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size,true,$skip_files_time);
        return $files;
    }

    public function getFileLoop(&$files,$path,$exclude_regex,$include_regex,$exclude_files_regex,$exclude_file_size=0,$include_dir = true,$skip_files_time=0)
    {
        $path=rtrim( $path, '/\\' );
        $count = 0;
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..")
                    {
                        $count++;

                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            if ($this->regex_match($exclude_regex, $path . DIRECTORY_SEPARATOR . $filename, 0))
                            {
                                if ($this->regex_match($include_regex, $path . DIRECTORY_SEPARATOR . $filename, 1))
                                {
                                    $this->getFileLoop($files, $path . DIRECTORY_SEPARATOR . $filename, $exclude_regex, $include_regex,$exclude_files_regex, $exclude_file_size, $include_dir,$skip_files_time);
                                }
                            }
                        }
                        else {
                            if($this->regex_match($exclude_files_regex, $filename, 0))
                            {
                                if ($this->regex_match($exclude_regex, $path . DIRECTORY_SEPARATOR . $filename, 0))
                                {
                                    if ($exclude_file_size == 0||(filesize($path . DIRECTORY_SEPARATOR . $filename) < $exclude_file_size * 1024 * 1024))
                                    {
                                        if(is_readable($path . DIRECTORY_SEPARATOR . $filename))
                                        {
                                            if($skip_files_time>0)
                                            {
                                                $file_time=@filemtime($path . DIRECTORY_SEPARATOR . $filename);
                                                if($file_time>0&&$file_time>$skip_files_time)
                                                {
                                                    $files[] = $path . DIRECTORY_SEPARATOR . $filename;
                                                }
                                            }
                                            else
                                            {
                                                $files[] = $path . DIRECTORY_SEPARATOR . $filename;
                                            }
                                        }
                                        else
                                        {
                                            global $wpvivid_backup_pro;
                                            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('file not readable:' . $path . DIRECTORY_SEPARATOR . $filename, 'notice');
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

        }
        if($include_dir && $count == 0)
        {
            $file_time=@filemtime($path);
            if($file_time>0&&$file_time>$skip_files_time)
            {
                $files[] = $path;
            }
        }
    }

    private function regex_match($regex_array,$string,$mode)
    {
        if(empty($regex_array))
        {
            return true;
        }

        if($mode==0)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return false;
                }
            }

            return true;
        }

        if($mode==1)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public static function wpvivid_transfer_backup_type($backup_type)
    {
        switch ($backup_type){
            case 'backup_db':
                $backup_type = 'database';
                break;
            case 'backup_themes':
                $backup_type = 'themes';
                break;
            case 'backup_plugin':
                $backup_type = 'plugins';
                break;
            case 'backup_uploads':
                $backup_type = 'uploads';
                break;
            case 'backup_content':
                $backup_type = 'wp-content';
                break;
            case 'backup_core':
                $backup_type = 'core';
                break;
            default:
                $backup_type = '';
                break;
        }
        return $backup_type;
    }

    public function check_backup_file_json($file_name){
        if(!class_exists('WPvivid_ZipClass'))
            include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
        $zip=new WPvivid_ZipClass();

        $general_setting=WPvivid_Setting::get_setting(true, "");
        $backup_folder = $general_setting['options']['wpvivid_local_setting']['path'];
        $backup_path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_folder.DIRECTORY_SEPARATOR;
        $file_path=$backup_path.$file_name;

        $ret=$zip->get_json_data($file_path);

        if($ret['result'] === WPVIVID_PRO_SUCCESS) {
            $json=$ret['json_data'];
            $json = json_decode($json, 1);
            if (is_null($json)) {
                return false;
            } else {
                return $json;
            }
        }
        elseif($ret['result'] === WPVIVID_PRO_FAILED){
            return false;
        }
    }
    /***** useful function end *****/

    public static function _get_table_info_ex($custom_setting, $type){
        global $wpdb;
        if (is_multisite() && !defined('MULTISITE')) {
            $prefix = $wpdb->base_prefix;
        } else {
            $prefix = $wpdb->get_blog_prefix(0);
        }
        $default_table = array($prefix.'commentmeta', $prefix.'comments', $prefix.'links', $prefix.'options', $prefix.'postmeta', $prefix.'posts', $prefix.'term_relationships',
            $prefix.'term_taxonomy', $prefix.'termmeta', $prefix.'terms', $prefix.'usermeta', $prefix.'users');

        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        if (is_null($tables)) {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
            return $ret;
        }

        $tables_info = array();
        $ret['result'] = 'success';
        $ret['database_html'] = '';
        $base_table = '';
        $other_table = '';
        $diff_perfix_table = '';
        $has_base_table = false;
        $has_other_table = false;
        $has_diff_prefix_table = false;
        $base_table_all_check = true;
        $other_table_all_check = true;
        $diff_prefix_table_all_check = true;

        foreach ($tables as $row) {
            $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
            $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);

            if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                $checked = '';
                if (!empty($custom_setting['custom_dirs']['include-tables'])) {
                    if (in_array($row["Name"], $custom_setting['custom_dirs']['include-tables'])) {
                        $checked = 'checked';
                    }
                }
                if($checked == ''){
                    $diff_prefix_table_all_check = false;
                }
                $has_diff_prefix_table = true;

                $diff_perfix_table .= '<div class="wpvivid-text-line">
                                            <input type="checkbox" option="diff_prefix_db" name="'.$type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' >
                                            <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                       </div>';
            }
            else{
                $checked = 'checked';
                if (!empty($custom_setting['custom_dirs']['exclude-tables'])) {
                    if (in_array($row["Name"], $custom_setting['custom_dirs']['exclude-tables'])) {
                        $checked = '';
                    }
                }
                if (in_array($row["Name"], $default_table)) {
                    if($checked == ''){
                        $base_table_all_check = false;
                    }
                    $has_base_table = true;

                    $base_table .= '<div class="wpvivid-text-line">
                                        <input type="checkbox" option="base_db" name="'.$type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' >
                                        <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                    </div>';
                } else {
                    if($checked == ''){
                        $other_table_all_check = false;
                    }
                    $has_other_table = true;

                    $other_table .= '<div class="wpvivid-text-line">
                                        <input type="checkbox" option="other_db" name="'.$type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' >
                                        <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                     </div>';
                }
            }
        }

        $ret['database_html'] = '<div style="padding-left:2em;margin-top:1em;">
								    <div style="border-bottom:1px solid rgb(204, 204, 204);"></div>
								 </div>';

        $base_table_html = '';
        $other_table_html = '';
        $diff_prefif_table_html = '';
        if ($has_base_table) {
            $base_all_check = '';
            if($base_table_all_check){
                $base_all_check = 'checked';
            }

            $base_table_html .= '<div style="width:30%;float:left;box-sizing:border-box;padding-left:2em;padding-right:0.5em;">
                                    <div>
                                        <p>
                                            <span class="dashicons dashicons-list-view wpvivid-dashicons-blue"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="wpvivid-database-table-check wpvivid-database-base-table-check" '.esc_attr($base_all_check).'></span>
												<span><strong>Wordpress default tables</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="padding-bottom:0.5em;"><span><input type="text" class="wpvivid-select-base-table-text" placeholder="Filter Tables">
									    <input type="button" value="Filter" class="button wpvivid-select-base-table-button" style="position: relative; z-index: 1;"></span>
									</div>
                                    <div class="wpvivid-database-base-list" style="height:250px;border:1px solid rgb(204, 204, 204);padding:0.2em 0.5em;overflow:auto;">
                                        '.$base_table.'
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>';
        }

        if ($has_other_table) {
            $other_all_check = '';
            if($other_table_all_check){
                $other_all_check = 'checked';
            }

            if($has_diff_prefix_table){
                $other_table_width = '40%';
            }
            else{
                $other_table_width = '70%';
            }

            $other_table_html .= '<div style="width:'.$other_table_width.'; float:left;box-sizing:border-box;padding-left:0.5em;">
                                    <div>
                                        <p>
                                            <span class="dashicons dashicons-list-view wpvivid-dashicons-green"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="wpvivid-database-table-check wpvivid-database-other-table-check" '.esc_attr($other_all_check).'></span>
												<span><strong>Tables created by plugins or themes</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="padding-bottom:0.5em;"><span><input type="text" class="wpvivid-select-other-table-text" placeholder="Filter Tables">
									    <input type="button" value="Filter" class="button wpvivid-select-other-table-button" style="position: relative; z-index: 1;"></span>
									</div>
                                    <div class="wpvivid-database-other-list" style="height:250px;border:1px solid rgb(204, 204, 204);padding:0.2em 0.5em;overflow:auto;">
                                        '.$other_table.'
                                    </div>
                                 </div>';
        }

        if ($has_diff_prefix_table) {
            $diff_all_check = '';
            if($diff_prefix_table_all_check){
                $diff_all_check = 'checked';
            }

            $diff_prefif_table_html .= '<div style="width:30%; float:left;box-sizing:border-box;padding-left:0.5em;padding-right:1em;">
                                            <div>
												<p>
												<span class="dashicons dashicons-list-view wpvivid-dashicons-orange"></span>
												<label title="Check/Uncheck all">
													<span><input type="checkbox" class="wpvivid-database-table-check wpvivid-database-diff-prefix-table-check" '.esc_attr($diff_all_check).'></span>
													<span><strong>Tables With Different Prefix</strong></span>
												</label>
											    </p>
											</div>
											<div style="padding-bottom:0.5em;"><span><input type="text" class="wpvivid-select-diff-prefix-table-text" placeholder="Filter Tables">
                                                <input type="button" value="Filter" class="button wpvivid-select-diff-prefix-table-button" style="position: relative; z-index: 1;"></span>
                                            </div>
											<div class="wpvivid-database-diff-prefix-list" style="height:250px;border:1px solid rgb(204, 204, 204);padding:0.2em 0.5em;overflow:auto;">
											    '.$diff_perfix_table.'
                                            </div>
                                        </div>';
        }

        $ret['database_html'] .= $base_table_html . $other_table_html . $diff_prefif_table_html;
        $ret['tables_info'] = $tables_info;
        return $ret;
    }

    public static function _get_table_info($custom_setting, $type){
        global $wpdb;
        if (is_multisite() && !defined('MULTISITE')) {
            $prefix = $wpdb->base_prefix;
        } else {
            $prefix = $wpdb->get_blog_prefix(0);
        }
        $default_table = array($prefix.'commentmeta', $prefix.'comments', $prefix.'links', $prefix.'options', $prefix.'postmeta', $prefix.'posts', $prefix.'term_relationships',
            $prefix.'term_taxonomy', $prefix.'termmeta', $prefix.'terms', $prefix.'usermeta', $prefix.'users');

        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        if (is_null($tables)) {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
            return $ret;
        }

        $tables_info = array();
        $ret['result'] = 'success';
        $ret['database_html'] = '';
        $base_table = '';
        $other_table = '';
        $diff_perfix_table = '';
        $has_base_table = false;
        $has_other_table = false;
        $has_diff_prefix_table = false;
        $base_table_all_check = true;
        $other_table_all_check = true;
        $diff_prefix_table_all_check = true;

        foreach ($tables as $row) {
            $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
            $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);

            if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                $checked = '';
                if (!empty($custom_setting['database_option']['exclude_table_list'])) {
                    if (!in_array($row["Name"], $custom_setting['database_option']['exclude_table_list'])) {
                        $checked = 'checked';
                    }
                }
                if($checked == ''){
                    $diff_prefix_table_all_check = false;
                }
                $has_diff_prefix_table = true;

                $diff_perfix_table .= '<div class="wpvivid-custom-database-table-column">
                                            <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;" 
                                            title="'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'">
                                                <input type="checkbox" option="diff_prefix_db" name="'.$type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                                <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                                '.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'
                                            </label>
                                       </div>';
            }
            else{
                $checked = 'checked';
                if (!empty($custom_setting['database_option']['exclude_table_list'])) {
                    if (in_array($row["Name"], $custom_setting['database_option']['exclude_table_list'])) {
                        $checked = '';
                    }
                }
                if (in_array($row["Name"], $default_table)) {
                    if($checked == ''){
                        $base_table_all_check = false;
                    }
                    $has_base_table = true;

                    $base_table .= '<div class="wpvivid-custom-database-table-column">
                                        <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;" 
                                        title="'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'">
                                        <input type="checkbox" option="base_db" name="'.$type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                        '.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'
                                        </label>
                                    </div>';
                } else {
                    if($checked == ''){
                        $other_table_all_check = false;
                    }
                    $has_other_table = true;
                    $other_table .= '<div class="wpvivid-custom-database-table-column">
                                        <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;"
                                        title="'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'">
                                        <input type="checkbox" option="other_db" name="'.$type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                        '.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'
                                        </label>
                                    </div>';
                }
            }
        }

        $base_table .= '<div style="clear:both;"></div>';
        $other_table .= '<div style="clear:both;"></div>';
        $diff_perfix_table .= '<div style="clear:both;"></div>';

        $base_table_html = '';
        $other_table_html = '';
        $diff_prefif_table_html = '';
        if ($has_base_table) {
            $base_all_check = '';
            if($base_table_all_check){
                $base_all_check = 'checked';
            }
            $base_table_html .= '<div class="wpvivid-custom-database-wp-table-header" style="border:1px solid #e5e5e5;">
                                        <div>
                                            <div style="float: left; margin-right: 10px;">
                                                <label class="wpvivid-checkbox">
                                                <input type="checkbox" class="wpvivid-database-table-check wpvivid-database-base-table-check" '.esc_attr($base_all_check).' />
                                                <span class="wpvivid-checkbox-checkmark"></span>WordPress Tables
                                                </label>
                                            </div>
                                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                                <div class="wpvivid-bottom">
                                                    <p>The tables are created by WordPress. Select all unless you are a WordPress specialist.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                            <div style="clear: both;"></div>
                                        </div>
                                     </div>
                                     <div style="clear: both;"></div>
                                     <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        '.$base_table.'
                                     </div>';
        }

        if ($has_other_table) {
            $other_all_check = '';
            if($other_table_all_check){
                $other_all_check = 'checked';
            }
            $other_table_html .= '<div class="wpvivid-custom-database-other-table-header" style="border:1px solid #e5e5e5;">
                                        <div>
                                            <div style="float: left; margin-right: 10px;">
                                                <label class="wpvivid-checkbox">
                                                <input type="checkbox" class="wpvivid-database-table-check wpvivid-database-other-table-check" '.esc_attr($other_all_check).' />
                                                <span class="wpvivid-checkbox-checkmark"></span>Tables created by plugins or themes
                                                </label>
                                            </div>
                                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                                <div class="wpvivid-bottom">
                                                    <p>Other tables are created by your plugins or themes, please select with caution.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                            <div style="clear: both;"></div>
                                        </div>
                                     </div>
                                     <div style="clear: both;"></div>
                                     <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        '.$other_table.'
                                     </div>';
        }

        if($has_diff_prefix_table){
            $diff_prefix_all_check = '';
            if($diff_prefix_table_all_check){
                $diff_prefix_all_check = 'checked';
            }
            $diff_prefif_table_html .= '<div class="wpvivid-custom-database-other-table-header" style="border:1px solid #e5e5e5;">
                                            <div>
                                                <div style="float: left; margin-right: 10px;">
                                                    <label class="wpvivid-checkbox">
                                                    <input type="checkbox" class="wpvivid-database-table-check wpvivid-database-diff-prefix-table-check" '.esc_attr($diff_prefix_all_check).' />
                                                    <span class="wpvivid-checkbox-checkmark"></span>Different Prefix Tables
                                                    </label>
                                                </div>
                                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                                    <div class="wpvivid-bottom">
                                                        <p>Tables with a different prefix from the prefix of the current WordPress tables.</p>
                                                        <i></i> <!-- do not delete this line -->
                                                    </div>
                                                </span>
                                                <div style="clear: both;"></div>
                                            </div>
                                        </div>
                                        <div style="clear: both;"></div>
                                        <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                            '.$diff_perfix_table.'
                                        </div>';
        }

        $div = '<div style="clear:both;"></div>';
        $div .= '<div style="margin-bottom: 10px;"></div>';
        $ret['database_html'] = $base_table_html . $div . $other_table_html . $div . $diff_prefif_table_html;
        $ret['tables_info'] = $tables_info;
        return $ret;
    }

    public static function get_theme_plugin_info($root)
    {
        $theme_info['size']=self::get_folder_size($root,0);
        return $theme_info;
    }

    public static function get_folder_size($root,$size)
    {
        $count = 0;
        if(is_dir($root))
        {
            $handler = opendir($root);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..") {
                        $count++;

                        if (is_dir($root . DIRECTORY_SEPARATOR . $filename))
                        {
                            $size=self::get_folder_size($root . DIRECTORY_SEPARATOR . $filename,$size);
                        } else {
                            $size+=filesize($root . DIRECTORY_SEPARATOR . $filename);
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }

    public static function _get_themes_plugin_info($custom_setting, $type){
        $themes_path = get_theme_root();
        $current_active_theme = get_stylesheet();
        $has_themes = false;
        $themes_table = '';
        $themes_table_html = '';
        $themes_count=0;
        $themes_all_check = 'checked';
        $themes_info = array();

        $themes=wp_get_themes();
        if(!empty($themes))
        {
            $has_themes=true;
        }
        foreach ($themes as $theme)
        {
            $file=$theme->get_stylesheet();
            $themes_info[$file] = self::get_theme_plugin_info($themes_path . DIRECTORY_SEPARATOR . $file);
            if($file===$current_active_theme)
            {
                $themes_info[$file]['active']=1;
            }
            else
            {
                $themes_info[$file]['active']=0;
            }
        }

        uasort ($themes_info,function($a, $b)
        {
            if($a['active']<$b['active'])
            {
                return 1;
            }
            if($a['active']>$b['active'])
            {
                return -1;
            }
            else
            {
                return 0;
            }
        });

        foreach ($themes_info as $file=>$info)
        {
            $checked = '';

            if($info['active']==1)
            {
                $checked = 'checked';
                $active_theme='';
            }
            else{
                $active_theme='';
            }

            if (!empty($custom_setting['themes_option']['exclude_themes_list']))
            {
                if (!in_array($file, $custom_setting['themes_option']['exclude_themes_list']))
                {
                    $checked = 'checked';
                }
            }

            if(empty($checked))
            {
                $themes_all_check='';
            }
            $themes_table .= '<div class="wpvivid-custom-database-table-column">
                                        <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;"
                                        title="'.esc_html($file).$active_theme.'|Size:'.size_format($info["size"], 2).'">
                                        <input type="checkbox" option="themes" name="'.$type.'_themes" value="'.esc_attr($file).'" '.esc_html($checked).' />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                        '.esc_html($file).$active_theme.'|Size:'.size_format($info["size"], 2).'</label></div>';
            $themes_count++;
        }
        $themes_table .= '<div style="clear:both;"></div>';
        $ret['result'] = 'success';
        $ret['themes_info'] = $themes_info;
        if($has_themes)
        {
            $themes_table_html .= '<div class="wpvivid-custom-database-wp-table-header" style="border:1px solid #e5e5e5;">
                                        <label class="wpvivid-checkbox">
                                        <input type="checkbox" class="wpvivid-themes-plugins-table-check wpvivid-themes-table-check" '.esc_attr($themes_all_check).' />
                                        <span class="wpvivid-checkbox-checkmark"></span>Themes
                                        </label>
                                     </div>
                                     <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        '.$themes_table.'
                                     </div>';
        }
        $ret['themes_plugins_html'] = $themes_table_html;

        $ret['themes_plugins_html'] .= '<div style="clear:both;"></div>';
        $ret['themes_plugins_html'] .= '<div style="margin-bottom: 10px;"></div>';

        if (!empty($custom_setting['themes_option']['exclude_themes_folder']))
        {
            $exclude_themes_folder = '<div class="wpvivid-custom-theme-plugin-table wpvivid-custom-theme-table">
                                            '.WPvivid_Custom_Interface_addon::wpvivid_get_exclude_themes_folder().'
                                      </div>';
        }
        else{
            $exclude_themes_folder = '';
        }
        $ret['themes_plugins_html'] .= '<div class="wpvivid-element-space-bottom">
                                            <div style="float: left; margin-right: 10px;">
                                                <input type="text" class="regular-text wpvivid-custom-exclude-themes-folder" placeholder="Exclude a theme subdirectory, e.g. twentytwenty/test" />
                                                <input type="button" class="wpvivid-custom-exclude-themes-folder-save" value="Save" />
                                            </div>
                                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                                                <div class="wpvivid-bottom">
                                                    <p>Exclude a subdirectory of a theme directory from the backup or migration by entering the path. You don\'t need to enter the full path, simple enter themedirectory/subdirectory, e.g. twentytwenty/test</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                            <div style="clear: both;"></div>
                                        </div>'.$exclude_themes_folder;

        $has_plugins = false;
        $plugins_table = '';
        $plugins_table_html = '';
        $path = WP_PLUGIN_DIR;
        $active_plugins = get_option('active_plugins');
        $plugin_count=0;
        $plugins_all_check = 'checked';
        $plugin_info = array();

        if(!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $plugins=get_plugins();

        if(!empty($plugins))
        {
            $has_plugins=true;
        }
        foreach ($plugins as $key=>$plugin)
        {
            $slug=dirname($key);
            if($slug=='.'||$slug=='wpvivid-backuprestore'||$slug=='wpvivid-backup-pro' || $slug == 'wpvividdashboard')
                continue;
            $plugin_info[$slug]= self::get_theme_plugin_info($path . DIRECTORY_SEPARATOR . $slug);
            $plugin_info[$slug]['Name']=$plugin['Name'];
            $plugin_info[$slug]['slug']=$slug;

            if(in_array($key, $active_plugins))
            {
                $plugin_info[$slug]['active']=1;
            }
            else{
                $plugin_info[$slug]['active']=0;
            }
        }

        uasort ($plugin_info,function($a, $b)
        {
            if($a['active']<$b['active'])
            {
                return 1;
            }
            if($a['active']>$b['active'])
            {
                return -1;
            }
            else
            {
                return 0;
            }
        });

        foreach ($plugin_info as $slug=>$info)
        {
            $checked = '';

            if($info['active']==1)
            {
                $checked = 'checked';
                $active_plugin='';
            }
            else{
                $active_plugin='';
            }

            if (!empty($custom_setting['plugins_option']['exclude_plugins_list']))
            {
                if (in_array($slug, $custom_setting['plugins_option']['exclude_plugins_list']))
                {
                    $checked = '';
                }
            }

            if(empty($checked))
            {
                $plugins_all_check = '';
            }
            $plugins_table .= '<div class="wpvivid-custom-database-table-column">
                                        <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;" 
                                        title="'.esc_html($info['Name']).$active_plugin.'|Size:'.size_format($info["size"], 2).'">
                                        <input type="checkbox" option="plugins" name="'.$type.'_plugins" value="'.esc_attr($info['slug']).'" '.esc_html($checked).' />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                        '.esc_html($info['Name']).$active_plugin.'|Size:'.size_format($info["size"], 2).'</label>
                                    </div>';
            $plugin_count++;
        }

        $plugins_table .= '<div style="clear:both;"></div>';
        $ret['plugin_info'] = $plugin_info;
        if($has_plugins){
            $plugins_table_html .= '<div class="wpvivid-custom-database-other-table-header" style="border:1px solid #e5e5e5;">
                                        <label class="wpvivid-checkbox">
                                        <input type="checkbox" class="wpvivid-themes-plugins-table-check wpvivid-plugins-table-check" '.esc_attr($plugins_all_check).' />
                                        <span class="wpvivid-checkbox-checkmark"></span>Plugins
                                        </label>
                                     </div>
                                     <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        '.$plugins_table.'
                                     </div>';
        }

        $ret['themes_plugins_html'] .= $plugins_table_html;

        if (!empty($custom_setting['plugins_option']['exclude_plugins_folder']))
        {
            $exclude_plugins_folder = '<div class="wpvivid-custom-theme-plugin-table wpvivid-custom-plugin-table">
                                            '.WPvivid_Custom_Interface_addon::wpvivid_get_exclude_plugins_folder().'
                                      </div>';
        }
        else{
            $exclude_plugins_folder = '';
        }
        $ret['themes_plugins_html'] .= '<div class="wpvivid-element-space-bottom" style="margin-top: 10px;">
                                            <div style="float: left; margin-right: 10px;">
                                                <input type="text" class="regular-text wpvivid-custom-exclude-plugins-folder" placeholder="Exclude a plugin subdirectory, e.g. wpvividbackups/test" />
                                                <input type="button" class="wpvivid-custom-exclude-plugins-folder-save" value="Save" />
                                            </div>
                                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                                                <div class="wpvivid-bottom">
                                                    <p>Exclude a subdirectory of a plugin directory from the backup or migration by entering the path. You don\'t need to enter the full path, simple enter plugindirectory/subdirectory, e.g. wpvividbackups/test</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                            <div style="clear: both;"></div>
                                        </div>'.$exclude_plugins_folder;

        return $ret;
    }

    public function get_database_themes_plugins_table(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            if (isset($_POST['type']) && !empty($_POST['type'])) {
                $type = sanitize_text_field($_POST['type']);

                $custom_setting = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
                if (empty($custom_setting)) {
                    $custom_setting = array();
                }
                $ret_db = self::_get_table_info_ex($custom_setting, $type);

                $ret = $ret_db;

                if ($ret_db['result'] === 'success') {
                    $ret['database_html'] = $ret_db['database_html'];
                    /*$ret_themes_plugins = self::_get_themes_plugin_info($custom_setting, $type);
                    if ($ret['result'] === 'success') {
                        $ret['themes_plugins_html'] = $ret_themes_plugins['themes_plugins_html'];
                    }
                    else{
                        $ret = $ret_themes_plugins;
                    }*/
                } else {
                    $ret = $ret_db;
                }

                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_custom_tree_dir(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try{
            $node_array = array();

            if ($_POST['tree_node']['node']['id'] == '#') {
                $path = ABSPATH;

                if (!empty($_POST['tree_node']['path'])) {
                    $path = $_POST['tree_node']['path'];
                }

                $node_array[] = array(
                    'text' => basename($path),
                    'children' => true,
                    'id' => $path,
                    'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer',
                    'state' => array(
                        'opened' => true
                    )
                );
            } else {
                $path = $_POST['tree_node']['node']['id'];
            }

            $path = trailingslashit(str_replace('\\', '/', realpath($path)));

            if ($dh = opendir($path)) {
                while (substr($path, -1) == '/') {
                    $path = rtrim($path, '/');
                }
                $skip_paths = array(".", "..");

                while (($value = readdir($dh)) !== false) {
                    trailingslashit(str_replace('\\', '/', $value));
                    if (!in_array($value, $skip_paths)) {
                        $exclude_dir = array();
                        if (is_dir($path . '/' . $value)) {
                            if (!in_array($path . '/' . $value, $exclude_dir)) {
                                $node['text'] = $value;
                                $node['children'] = true;
                                $node['id'] = $path . '/' . $value;
                                $node['icon'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                                $node_array[] = $node;
                            }
                        }
                        else{
                            $node['text'] = $value;
                            $node['children'] = true;
                            $node['id'] = $path . '/' . $value;
                            $node['icon'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                            $node_array[] = $node;
                        }
                    }
                }
            }

            $ret['nodes'] = $node_array;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_custom_dir_uploads(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            $node_array = array();

            if ($_POST['tree_node']['node']['id'] == '#') {
                $path = ABSPATH;

                if (!empty($_POST['tree_node']['path'])) {
                    $path = $_POST['tree_node']['path'];
                }

                $node_array[] = array(
                    'text' => basename($path),
                    'children' => true,
                    'id' => $path,
                    'icon' => 'jstree-folder',
                    'state' => array(
                        'opened' => true
                    )
                );
            } else {
                $path = $_POST['tree_node']['node']['id'];
            }

            $path = trailingslashit(str_replace('\\', '/', realpath($path)));

            if ($dh = opendir($path)) {
                while (substr($path, -1) == '/') {
                    $path = rtrim($path, '/');
                }
                $skip_paths = array(".", "..");

                while (($value = readdir($dh)) !== false) {
                    trailingslashit(str_replace('\\', '/', $value));
                    if (!in_array($value, $skip_paths)) {
                        $custom_dir = WP_CONTENT_DIR . '/' . WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                        $custom_dir = str_replace('\\', '/', $custom_dir);

                        $themes_dir = get_theme_root();
                        $themes_dir = trailingslashit(str_replace('\\', '/', $themes_dir));
                        //$themes_dir = str_replace($content_dir, '', $themes_dir);
                        $themes_dir = rtrim($themes_dir, '/');

                        $plugin_dir = WP_PLUGIN_DIR;
                        $plugin_dir = trailingslashit(str_replace('\\', '/', $plugin_dir));
                        //$plugin_dir = str_replace($content_dir, '', $plugin_dir);
                        $plugin_dir = rtrim($plugin_dir, '/');

                        $upload_dir = wp_upload_dir();
                        $upload_dir['basedir'] = trailingslashit(str_replace('\\', '/', $upload_dir['basedir']));
                        $upload_dir['basedir'] = rtrim($upload_dir['basedir'], '/');

                        $exclude_dir = array($themes_dir, $plugin_dir, $upload_dir['basedir'], $custom_dir);
                        if (is_dir($path . '/' . $value)) {
                            if (!in_array($path . '/' . $value, $exclude_dir)) {
                                $node['text'] = $value;
                                $node['children'] = true;
                                $node['id'] = $path . '/' . $value;
                                $node['icon'] = 'jstree-folder';
                                $node_array[] = $node;
                            }
                        }
                    }
                }
            }

            $ret['nodes'] = $node_array;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_custom_dir(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            $node_array = array();

            if ($_POST['tree_node']['node']['id'] == '#') {
                $path = ABSPATH;

                if (!empty($_POST['tree_node']['path'])) {
                    $path = $_POST['tree_node']['path'];
                }

                if (isset($_POST['select_prev_dir']) && $_POST['select_prev_dir'] === '1') {
                    $path = dirname($path);
                }

                $node_array[] = array(
                    'text' => basename($path),
                    'children' => true,
                    'id' => $path,
                    'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer',
                    'state' => array(
                        'opened' => true
                    )
                );
            } else {
                $path = $_POST['tree_node']['node']['id'];
            }

            $path = trailingslashit(str_replace('\\', '/', realpath($path)));

            if ($dh = opendir($path)) {
                while (substr($path, -1) == '/') {
                    $path = rtrim($path, '/');
                }

                $skip_paths = array(".", "..");

                $file_array = array();

                while (($value = readdir($dh)) !== false) {
                    trailingslashit(str_replace('\\', '/', $value));

                    if (!in_array($value, $skip_paths)) {
                        if (is_dir($path . '/' . $value)) {
                            $wp_admin_path = ABSPATH . 'wp-admin';
                            $wp_admin_path = str_replace('\\', '/', $wp_admin_path);

                            $wp_include_path = ABSPATH . 'wp-includes';
                            $wp_include_path = str_replace('\\', '/', $wp_include_path);

                            $content_dir = WP_CONTENT_DIR;
                            $content_dir = str_replace('\\', '/', $content_dir);
                            $content_dir = rtrim($content_dir, '/');

                            $lotties_dir = ABSPATH . 'lotties';
                            $lotties_dir = str_replace('\\', '/', $lotties_dir);

                            $exclude_dir = array($wp_admin_path, $wp_include_path, $content_dir, $lotties_dir);
                            if (!in_array($path . '/' . $value, $exclude_dir)) {
                                $node_array[] = array(
                                    'text' => $value,
                                    'children' => true,
                                    'id' => $path . '/' . $value,
                                    'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'
                                );
                            }

                        } else {

                            $wp_admin_path = ABSPATH;
                            $wp_admin_path = str_replace('\\', '/', $wp_admin_path);
                            $wp_admin_path = rtrim($wp_admin_path, '/');
                            $skip_path = rtrim($path, '/');

                            if ($wp_admin_path == $skip_path) {
                                continue;
                            }
                            $file_array[] = array(
                                'text' => $value,
                                'children' => false,
                                'id' => $path . '/' . $value,
                                'type' => 'file',
                                'icon' => 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer'
                            );
                        }
                    }
                }
                $node_array = array_merge($node_array, $file_array);
            }

            $ret['nodes'] = $node_array;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function update_backup_exclude_extension(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            if (isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']) &&
                isset($_POST['exclude_content']) && !empty($_POST['exclude_content']) && is_string($_POST['exclude_content'])) {
                $type = sanitize_text_field($_POST['type']);
                $value = sanitize_text_field($_POST['exclude_content']);

                $exclude = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
                if (empty($exclude)) {
                    $exclude = array();
                }
                if ($type === 'upload') {
                    $exclude['uploads_option']['uploads_extension_list'] = array();
                    $str_tmp = explode(',', $value);
                    for ($index = 0; $index < count($str_tmp); $index++) {
                        if (!empty($str_tmp[$index])) {
                            $exclude['uploads_option']['uploads_extension_list'][] = $str_tmp[$index];
                        }
                    }
                } else if ($type === 'content') {
                    $exclude['content_option']['content_extension_list'] = array();
                    $str_tmp = explode(',', $value);
                    for ($index = 0; $index < count($str_tmp); $index++) {
                        if (!empty($str_tmp[$index])) {
                            $exclude['content_option']['content_extension_list'][] = $str_tmp[$index];
                        }
                    }
                } else if ($type === 'additional-folder') {
                    $exclude['other_option']['other_extension_list'] = array();
                    $str_tmp = explode(',', $value);
                    for ($index = 0; $index < count($str_tmp); $index++) {
                        if (!empty($str_tmp[$index])) {
                            $exclude['other_option']['other_extension_list'][] = $str_tmp[$index];
                        }
                    }
                }

                WPvivid_Setting::update_option('wpvivid_custom_backup_history', $exclude);

                $ret['result'] = 'success';
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function connect_additional_database(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            if (isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])) {
                $data = $_POST['database_info'];
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $db_user = sanitize_text_field($json['db_user']);
                $db_pass = sanitize_text_field($json['db_pass']);
                $db_host = sanitize_text_field($json['db_host']);

                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']='Unknown Error';

                $this->database_connect = new WPvivid_Additional_DB_Method($db_user, $db_pass, $db_host);
                $ret = $this->database_connect->wpvivid_do_connect();

                if($ret['result']===WPVIVID_PRO_SUCCESS){
                    $databases = $this->database_connect->wpvivid_show_additional_databases();
                    $default_exclude_database = array('information_schema', 'performance_schema', 'mysql', 'sys', DB_NAME);
                    $database_array = array();
                    foreach ($databases as $database) {
                        if (!in_array($database, $default_exclude_database)) {
                            $database_array[] = $database;
                        }
                    }
                    $database_html = '';
                    foreach ($database_array as $database){
                        $database_html .= '<div class="wpvivid-text-line"><span class="dashicons dashicons-plus-alt wpvivid-icon-16px wpvivid-add-additional-db" option="additional_db" name="'.$database.'"></span><span class="wpvivid-text-line">'.esc_html($database).'</span></div>';
                    }
                    $ret['html'] = $database_html;
                    $ret['result']=WPVIVID_PRO_SUCCESS;
                }
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        catch (Error $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function add_additional_database(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            if (isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])) {
                $data = $_POST['database_info'];
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $db_user = sanitize_text_field($json['db_user']);
                $db_pass = sanitize_text_field($json['db_pass']);
                $db_host = sanitize_text_field($json['db_host']);
                $db_list = $json['additional_database_list'];

                $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
                if (empty($history)) {
                    $history = array();
                }
                foreach ($db_list as $database){
                    $history['additional_database_option']['additional_database_list'][$database]['db_user'] = $db_user;
                    $history['additional_database_option']['additional_database_list'][$database]['db_pass'] = $db_pass;
                    $history['additional_database_option']['additional_database_list'][$database]['db_host'] = $db_host;
                }
                WPvivid_Setting::update_option('wpvivid_custom_backup_history', $history);

                if(!is_null($this->database_connect)){
                    $this->database_connect->close();
                }
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $html = '';
                $html = apply_filters('wpvivid_additional_database_list', $html);
                $ret['html'] = $html;
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function remove_additional_database(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            if (isset($_POST['database']) && !empty($_POST['database']) && is_string($_POST['database'])) {
                $database = sanitize_text_field($_POST['database']);
                if(!is_null($this->database_connect)){
                    $this->database_connect->close();
                }

                $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
                if (empty($history)) {
                    $history = array();
                }
                if(isset($history['additional_database_option'])) {
                    if(isset($history['additional_database_option']['additional_database_list'][$database])){
                        unset($history['additional_database_option']['additional_database_list'][$database]);
                    }
                }
                WPvivid_Setting::update_option('wpvivid_custom_backup_history', $history);

                $ret['result']=WPVIVID_PRO_SUCCESS;
                $html = '';
                $html = apply_filters('wpvivid_additional_database_list', $html);
                $ret['html'] = $html;
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function add_progress()
    {
        ?>
        <div class="wpvivid-one-coloum" id="wpvivid_postbox_backup_percent" style="display: none;">
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                <p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress">53% completed</span></span></p>
                <p>
                    <!--<span class="dashicons dashicons-list-view wpvivid-dashicons-blue wpvivid_estimate_backup_info"></span><span class="wpvivid_estimate_backup_info">Database Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_database_size">N/A</span>
                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange wpvivid_estimate_backup_info"></span><span class="wpvivid_estimate_backup_info">File Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_file_size">N/A</span>-->
                    <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Total Size:</span><span>N/A</span>
                    <span class="dashicons dashicons-upload wpvivid-dashicons-blue"></span><span>Uploaded:</span><span>N/A</span>
                    <span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span><span>Speed:</span><span>N/A</span>
                    <span class="dashicons dashicons-networking wpvivid-dashicons-green"></span><span>Network Connection:</span><span>OK</span>
                </p>
                <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>running</span></p>
                <div><input class="button-primary" id="wpvivid_backup_cancel_btn" type="submit" value="Cancel"></div>
            </div>
        </div>

        <script>
            jQuery('#wpvivid_postbox_backup_percent').on("click", "input", function() {
                if(jQuery(this).attr('id') === 'wpvivid_backup_cancel_btn')
                {
                    wpvivid_cancel_backup();
                }
            });

            function wpvivid_cancel_backup() {
                var ajax_data= {
                    'action': 'wpvivid_backup_cancel'
                };
                jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        jQuery('#wpvivid_current_doing').html(jsonarray.msg);
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('cancelling the backup', textStatus, errorThrown);
                    wpvivid_add_notice('Backup', 'Error', error_message);
                });
            }
        </script>
        <?php
    }

    public function prepare_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        global $wpvivid_plugin;
        $wpvivid_plugin->end_shutdown_function=false;
        register_shutdown_function(array($wpvivid_plugin,'deal_prepare_shutdown_error'));
        try
        {
            if(isset($_POST['backup'])&&!empty($_POST['backup']))
            {
                $json = $_POST['backup'];
                $json = stripslashes($json);
                $backup_options = json_decode($json, true);
                if (is_null($backup_options))
                {
                    $wpvivid_plugin->end_shutdown_function=true;
                    die();
                }

                $backup_options = apply_filters('wpvivid_custom_backup_options', $backup_options);
                if(!isset($backup_options['type']))
                {
                    $backup_options['type']='Manual';
                    $backup_options['action']='backup';
                }

                $ret = $wpvivid_plugin->check_backup_option($backup_options, $backup_options['type']);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    $wpvivid_plugin->end_shutdown_function=true;
                    echo json_encode($ret);
                    die();
                }

                if(isset($_POST['is_export']))
                {
                    $backup_options['is_export'] = true;
                }

                if(isset($backup_options['remote_id_select'])){
                    $remoteslist=WPvivid_Setting::get_all_remote_options();
                    $remote_options = array();
                    $remote_options[$backup_options['remote_id_select']] = $remoteslist[$backup_options['remote_id_select']];
                    $backup_options['remote_options'] = $remote_options;
                }

                $ret=$this->pre_backup($backup_options);
                if($ret['result']=='success')
                {
                    //Check the website data to be backed up
                    /*
                    $ret['check']=$wpvivid_plugin->check_backup($ret['task_id'],$backup_options);
                    if(isset($ret['check']['result']) && $ret['check']['result'] == WPVIVID_PRO_FAILED)
                    {
                        $wpvivid_plugin->end_shutdown_function=true;
                        echo json_encode(array('result' => WPVIVID_PRO_FAILED,'error' => $ret['check']['error']));
                        die();
                    }*/

                    $html = '';
                    $html = apply_filters('wpvivid_add_backup_list', $html);
                    $ret['html'] = $html;
                }
                $wpvivid_plugin->end_shutdown_function=true;
                echo json_encode($ret);
                die();
            }
        }
        catch (Exception $error)
        {
            $wpvivid_plugin->end_shutdown_function=true;
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

    public function list_tasks_addon()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            $ret = $this->_list_tasks_addon();

            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }

        die();
    }

    public function _list_tasks_addon()
    {
        global $wpvivid_plugin;
        $tasks=WPvivid_Setting::get_tasks();
        $ret['result']='success';
        $ret['progress_html']=false;
        $ret['success_notice_html'] =false;
        $ret['error_notice_html'] =false;
        $ret['need_update']=false;
        $ret['last_msg_html']=false;
        $ret['running_backup_taskid']='';
        $ret['wait_resume']=false;
        $ret['next_resume_time']=false;
        $ret['need_refresh_remote']=false;
        foreach ($tasks as $task)
        {
            $ret['task_id']=$task['id'];
            $ret['need_update']=true;
            $ret['action'] = $task['action'];
            $backup=new WPvivid_Backup_Task($task['id']);
            $info=$backup->get_backup_task_info($task['id']);

            if($info['task_info']['need_next_schedule']===true)
            {
                $timestamp = wp_next_scheduled(WPVIVID_PRO_TASK_MONITOR_EVENT,array($task['id']));
                if($timestamp===false)
                {
                    $wpvivid_plugin->add_monitor_event($task['id'],20);
                }
            }
            if($info['status']['str']=='ready'||$info['status']['str']=='running'||$info['status']['str']=='wait_resume'||$info['status']['str']=='no_responds')
            {
                $ret['running_backup_taskid']=$task['id'];

                if($info['status']['str']=='wait_resume')
                {
                    $ret['wait_resume']=true;
                    $ret['next_resume_time']=$info['data']['next_resume_time'];
                }
                //<span class="dashicons dashicons-list-view wpvivid-dashicons-blue wpvivid_estimate_backup_info" style="'.$info['task_info']['display_estimate_backup'].'"></span><span class="wpvivid_estimate_backup_info" style="'.$info['task_info']['display_estimate_backup'].'">Database Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_database_size" style="'.$info['task_info']['display_estimate_backup'].'">'.$info['task_info']['db_size'].'</span>
                //                                                <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange wpvivid_estimate_backup_info" style="'.$info['task_info']['display_estimate_backup'].'"></span><span class="wpvivid_estimate_backup_info" style="'.$info['task_info']['display_estimate_backup'].'">File Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_file_size" style="'.$info['task_info']['display_estimate_backup'].'">'.$info['task_info']['file_size'].'</span>
                //
                $ret['progress_html'] = '<div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                                            <p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="width:'.$info['task_info']['backup_percent'].'">'.$info['task_info']['backup_percent'].' completed</span></span></p>
                                            <p>
                                                <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Total Size:</span><span>'.$info['task_info']['total'].'</span>
                                                <span class="dashicons dashicons-upload wpvivid-dashicons-blue"></span><span>Uploaded:</span><span>'.$info['task_info']['upload'].'</span>
                                                <span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span><span>Speed:</span><span>'.$info['task_info']['speed'].'</span>
                                                <span class="dashicons dashicons-networking wpvivid-dashicons-green"></span><span>Network Connection:</span><span>'.$info['task_info']['network_connection'].'</span>
                                            </p>
                                            <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span id="wpvivid_current_doing">'.$info['task_info']['descript'].'</span></p>
                                            <div><input class="button-primary" id="wpvivid_backup_cancel_btn" type="submit" value="Cancel" style="'.$info['task_info']['css_btn_cancel'].'"></div>
                                        </div>';
            }
        }

        $finished_tasks=get_option('wpvivid_backup_finished_tasks',array());
        if(!empty($finished_tasks))
        {
            $backup_success_count=0;
            $transfer_success_count=0;
            $backup_failed_count=0;
            $success_log_file_name = '';
            foreach ($finished_tasks as $id => $finished_task)
            {
                if($finished_task['status']=='completed')
                {
                    if(!isset($finished_task['is_export']))
                    {
                        if($finished_task['action_type'] == 'auto_transfer'){
                            $transfer_success_count++;
                        }
                        else{
                            $backup_success_count++;
                            $success_log_file_name = $id.'_backup_log.txt';
                        }
                    }
                    else
                    {
                        if($finished_task['action_type'] === 'backup')
                        {
                            $backup_id = $id;
                            $backup_list=new WPvivid_New_BackupList();
                            $backup = $backup_list->get_backup_by_id($backup_id);
                            if ($backup !== false) {
                                $backup_item = new WPvivid_New_Backup_Item($backup);

                                $backup_files = $backup_item->get_download_backup_files($backup_id);
                                if ($backup_files['result'] == WPVIVID_PRO_SUCCESS)
                                {
                                    foreach ($backup_files['files'] as $file)
                                    {
                                        $path = $this->get_backup_path($backup_item, $file['file_name']);
                                        if (file_exists($path))
                                        {
                                            if (filesize($path) == $file['size'])
                                            {
                                                if (WPvivid_taskmanager::get_download_task_v2($file['file_name']))
                                                    WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                                                $ret['local_export_files'][$file['file_name']]['status'] = 'completed';
                                                $ret['local_export_files'][$file['file_name']]['size'] = size_format(filesize($path), 2);
                                                $ret['local_export_file_complete'] = true;
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        else if($finished_task['action_type'] === 'backup_remote')
                        {
                            $ret['remote_export_file_complete'] = true;
                        }
                        else if($finished_task['action_type'] === 'auto_transfer')
                        {
                            $ret['migration_export_file_complete'] = true;
                        }
                        $success_log_file_name = $id.'_backup_log.txt';
                    }
                }
                else if($finished_task['status']=='error')
                {
                    $backup_failed_count++;
                    $ret['error_notice_html'] =$finished_task['error_msg'];
                }
            }

            if($transfer_success_count>0)
            {
                $notice_msg = 'Transfer succeeded. Please scan the backup list on the destination site to display the backup, then restore the backup.';
                $ret['success_notice_html'] =__('<div class="notice notice-success notice-transfer-success is-dismissible inline" style="margin-bottom: 5px;"><p>'.$notice_msg.'</p></div>');
                update_option('wpvivid_display_auto_migration_success_notice', true, 'no');
            }

            if($backup_success_count>0)
            {
                $log_url=apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-backup-and-restore').'&log='.$success_log_file_name;
                $notice_msg = $backup_success_count.' backup task(s) finished. Please switch to <a href="'.$log_url.'">Log</a> page to check the details.';
                $ret['success_notice_html'] =__('<div class="notice notice-success is-dismissible inline" style="margin-bottom: 5px;"><p>'.$notice_msg.'</p>
                                    <button type="button" class="notice-dismiss" onclick="click_dismiss_notice(this);">
                                    <span class="screen-reader-text">Dismiss this notice.</span>
                                    </button>
                                    </div>');
            }

            if($backup_failed_count>1)
            {
                $admin_url = apply_filters('wpvivid_get_admin_url', '');
                $notice_msg = $backup_success_count.' backup task(s) have been failed. Please switch to <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'" >Website Info</a> page to send us the debug information.';
                $ret['error_notice_html'] =__('<div class="notice notice-error inline" style="margin-bottom: 5px;"><p>'.$notice_msg.'</p></div>');
            }

            $ret['need_refresh_remote'] = WPvivid_Setting::get_option('wpvivid_backup_remote_need_update', false);

            $tasks=WPvivid_Setting::get_tasks();
            $delete_ids=array();
            foreach ($tasks as $task)
            {
                if(array_key_exists($task['id'],$finished_tasks))
                {
                    $delete_ids[]=$task['id'];
                }
            }
            foreach ($delete_ids as $id)
            {
                unset($tasks[$id]);
            }
            WPvivid_Setting::update_option('wpvivid_task_list',$tasks);
            delete_option('wpvivid_backup_finished_tasks');

            $html='';
            $html=apply_filters('wpvivid_get_last_backup_message', $html);
            $ret['last_msg_html']=$html;
        }

        return $ret;
    }

    public function get_backup_path($backup_item, $file_name)
    {
        $path = $backup_item->get_local_path() . $file_name;

        if (file_exists($path)) {
            return $path;
        }
        else{
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$file_name;
        }
        return $path;
    }

    public function get_exclude_folder_file_list($backup_type, $type)
    {
        $option_type = 'themes_option';
        $list_type = 'exclude_themes_list';
        if($backup_type == 'themes'){
            $option_type = 'themes_option';
            $list_type = 'exclude_themes_list';
        }
        else if($backup_type == 'plugins'){
            $option_type = 'plugins_option';
            $list_type = 'exclude_plugins_list';
        }
        else if($backup_type == 'content'){
            $option_type = 'content_option';
            $list_type = 'exclude_content_list';
        }
        else if($backup_type == 'uploads'){
            $option_type = 'uploads_option';
            $list_type = 'exclude_uploads_list';
        }
        else if($backup_type == 'additional'){
            $option_type = 'other_option';
            $list_type = 'include_other_list';
        }

        if($type === 'incremental')
        {
            $exclude_path = WPvivid_custom_backup_selector::get_incremental_file_settings();
        }
        else
        {
            $exclude_path = get_option('wpvivid_custom_backup_history');
        }

        if(empty($exclude_path))
        {
            $current_active_theme = get_stylesheet();
            $themes = wp_get_themes();
            foreach ($themes as $theme) {
                $file = $theme->get_stylesheet();
                if ($file !== $current_active_theme) {
                    $exclude_path['themes_option']['exclude_themes_list'][] = $file;
                }
            }

            $active_plugins = get_option('active_plugins');
            $plugins = get_plugins();
            foreach ($plugins as $key => $plugin) {
                $slug = dirname($key);
                if ($slug == '.' || $slug == 'wpvivid-backuprestore' || $slug == 'wpvivid-backup-pro' || $slug == 'wpvividdashboard')
                    continue;
                if (!in_array($key, $active_plugins)) {
                    $exclude_path['plugins_option']['exclude_plugins_list'][] = $slug;
                }
            }
        }

        if(isset($exclude_path[$option_type][$list_type]) && !empty($exclude_path[$option_type][$list_type]))
        {
            $exclude_list = $exclude_path[$option_type][$list_type];
        }
        else{
            $exclude_list = array();
        }

        return $exclude_list;
    }

    public function get_website_size()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try{
            if(isset($_POST['website_item'])&&!empty($_POST['website_item']))
            {
                $website_item = sanitize_key($_POST['website_item']);

                $ret['result']='success';

                $website_size = get_option('wpvivid_custom_select_website_size', array());
                if(!empty($website_size))
                {
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                    }
                    else
                    {
                        $type = 'general';
                    }
                    if(isset($website_size[$type]['calctime']))
                    {
                        $calctime_bef=$website_size[$type]['calctime'];
                        $current_time=time();
                        if($current_time - $calctime_bef <= 12*60*60)
                        {
                            $website_size = get_option('wpvivid_custom_select_website_size', array());
                            if(empty($website_size))
                                $website_size = array();

                            $database_size=isset($website_size[$type]['database_size'])?$website_size[$type]['database_size']:0;
                            $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                            $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                            $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                            $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                            $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                            $additional_size=isset($website_size[$type]['additional_size'])?$website_size[$type]['additional_size']:0;

                            $content_exclude_size=isset($website_size[$type]['content_exclude_size'])?$website_size[$type]['content_exclude_size']:0;
                            $themes_exclude_size=isset($website_size[$type]['themes_exclude_size'])?$website_size[$type]['themes_exclude_size']:0;
                            $plugins_exclude_size=isset($website_size[$type]['plugins_exclude_size'])?$website_size[$type]['plugins_exclude_size']:0;
                            $uploads_exclude_size=isset($website_size[$type]['uploads_exclude_size'])?$website_size[$type]['uploads_exclude_size']:0;

                            $ret['database_size'] = size_format($database_size, 2);
                            $ret['core_size'] = size_format($core_size, 2);
                            $ret['content_size'] = size_format($content_size, 2);
                            $ret['themes_size'] = size_format($themes_size, 2);
                            $ret['plugins_size'] = size_format($plugins_size, 2);
                            $ret['uploads_size'] = size_format($uploads_size, 2);
                            $ret['additional_size'] = size_format($additional_size, 2);
                            $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                            $ret['total_exclude_file_size'] = size_format($themes_exclude_size+$plugins_exclude_size+$uploads_exclude_size+$content_exclude_size, 2);
                            $ret['total_content_size'] = size_format($database_size+$core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                            echo json_encode($ret);
                            die();
                        }
                    }
                }

                if(empty($website_size))
                    $website_size = array();
                if($website_item === 'database')
                {
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $exclude_path = WPvivid_custom_backup_selector::get_incremental_db_setting();
                    }
                    else
                    {
                        $type = 'general';
                        $exclude_path = get_option('wpvivid_custom_backup_history');
                    }
                    if(empty($exclude_path))
                    {
                        $is_select_db = true;
                        $is_select_db_additional = false;
                        $exclude_table_list = array();
                    }
                    else
                    {
                        if($exclude_path['database_option']['database_check'] == '1')
                        {
                            $is_select_db = true;
                        }
                        else
                        {
                            $is_select_db = false;
                        }

                        if($exclude_path['additional_database_option']['additional_database_check'] == '1')
                        {
                            $is_select_db_additional = true;
                        }
                        else
                        {
                            $is_select_db_additional = false;
                        }

                        if(!empty($exclude_path['database_option']['exclude_table_list']))
                        {
                            $exclude_table_list = $exclude_path['database_option']['exclude_table_list'];
                        }
                        else
                        {
                            $exclude_table_list = array();
                        }
                    }

                    $ret = $this->_get_custom_database_size($is_select_db, $is_select_db_additional, $exclude_table_list, false);
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['database_size'] = $ret['database_size'];
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['database_size'] = size_format($ret['database_size'], 2);
                }

                if($website_item === 'core')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            if($custom_incremental_file['core_option']['core_check'] == '1')
                            {
                                $is_select_core = true;
                            }
                            else
                            {
                                $is_select_core = false;
                            }
                        }
                    }
                    else
                    {
                        $type = 'general';
                        $exclude_path = get_option('wpvivid_custom_backup_history');

                        if(empty($exclude_path))
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            if($exclude_path['core_option']['core_check'] == '1')
                            {
                                $is_select_core = true;
                            }
                            else
                            {
                                $is_select_core = false;
                            }
                        }
                    }
                    $core_folder_exclude_list = array('wp-admin', 'wp-includes', 'lotties');
                    $core_file_exclude_list = array('.htaccess', 'index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php',
                        'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
                    $core_exclude_list = array();
                    if($is_select_core)
                    {
                        $core_size = self::get_custom_path_size('core', $home_path, $core_folder_exclude_list, $core_file_exclude_list);
                    }
                    else
                    {
                        $core_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['core_size'] = $core_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['core_size'] = size_format($core_size, 2);
                }

                if($website_item === 'content')
                {
                    $content_dir = WP_CONTENT_DIR;
                    $path = str_replace('\\','/',$content_dir);
                    $content_path = $path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_content = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['content_option']['content_check'] == '1')
                            {
                                $is_select_content = true;
                            }
                            else
                            {
                                $is_select_content = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }
                    else
                    {
                        $type = 'general';
                        $exclude_path = get_option('wpvivid_custom_backup_history');

                        if(empty($exclude_path))
                        {
                            $is_select_content = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($exclude_path['content_option']['content_check'] == '1')
                            {
                                $is_select_content = true;
                            }
                            else
                            {
                                $is_select_content = false;
                            }

                            if($exclude_path['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }

                    $local_setting = get_option('wpvivid_local_setting', array());
                    if(!empty($local_setting))
                    {
                        $content_folder_exclude_list = array('plugins', 'themes', 'uploads', 'wpvividbackups', $local_setting['path']);
                    }
                    else {
                        $content_folder_exclude_list = array('plugins', 'themes', 'uploads', 'wpvividbackups');
                    }
                    $content_folder_exclude_list_ex = array();
                    $content_file_exclude_list = array();
                    $content_exclude_list = $this->get_exclude_folder_file_list('content', $type);
                    if($is_select_exclude_ex)
                    {
                        if(!empty($content_exclude_list))
                        {
                            foreach ($content_exclude_list as $key => $value)
                            {
                                if (isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $content_folder_exclude_list[] = $key;
                                        $content_folder_exclude_list_ex[] = $key;
                                    }
                                    else{
                                        $content_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $content_folder_exclude_list[] = $key;
                                    $content_folder_exclude_list_ex[] = $key;
                                }
                            }
                        }
                        $content_exclude_size = self::_get_exclude_folder_file_size($content_path, $content_folder_exclude_list_ex, $content_file_exclude_list);
                    }
                    else
                    {
                        $content_exclude_size = 0;
                    }
                    if($is_select_content)
                    {
                        $content_size = self::get_custom_path_size('content', $content_path, $content_folder_exclude_list, $content_file_exclude_list);
                    }
                    else
                    {
                        $content_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['content_size'] = $content_size;
                    $website_size[$type]['content_exclude_size'] = $content_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['content_size'] = size_format($content_size, 2);
                }

                if($website_item === 'themes')
                {
                    $themes_path = str_replace('\\','/', get_theme_root());
                    $themes_path = $themes_path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_themes = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['themes_option']['themes_check'] == '1')
                            {
                                $is_select_themes = true;
                            }
                            else
                            {
                                $is_select_themes = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }
                    else
                    {
                        $type = 'general';
                        $exclude_path = get_option('wpvivid_custom_backup_history');

                        if(empty($exclude_path))
                        {
                            $is_select_themes = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($exclude_path['themes_option']['themes_check'] == '1')
                            {
                                $is_select_themes = true;
                            }
                            else
                            {
                                $is_select_themes = false;
                            }

                            if($exclude_path['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }

                    $themes_folder_exclude_list = array();
                    $themes_file_exclude_list = array();
                    $themes_exclude_list = $this->get_exclude_folder_file_list('themes', $type);
                    if($is_select_exclude_ex)
                    {
                        if(!empty($themes_exclude_list))
                        {
                            foreach ($themes_exclude_list as $key => $value)
                            {
                                if(isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $themes_folder_exclude_list[] = $key;
                                    }
                                    else{
                                        $themes_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $themes_folder_exclude_list[] = $value;
                                }
                            }
                        }
                        $themes_exclude_size = self::_get_exclude_folder_file_size($themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);
                    }
                    else
                    {
                        $themes_exclude_size = 0;
                    }
                    if($is_select_themes)
                    {
                        $themes_size = self::get_custom_path_size('themes', $themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);
                    }
                    else
                    {
                        $themes_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['themes_size'] = $themes_size;
                    $website_size[$type]['themes_exclude_size'] = $themes_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['themes_size'] = size_format($themes_size, 2);
                }

                if($website_item === 'plugins')
                {
                    $plugins_path = str_replace('\\','/', WP_PLUGIN_DIR);
                    $plugins_path = $plugins_path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_plugins = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['plugins_option']['plugins_check'] == '1')
                            {
                                $is_select_plugins = true;
                            }
                            else
                            {
                                $is_select_plugins = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }
                    else
                    {
                        $type = 'general';
                        $exclude_path = get_option('wpvivid_custom_backup_history');

                        if(empty($exclude_path))
                        {
                            $is_select_plugins = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($exclude_path['plugins_option']['plugins_check'] == '1')
                            {
                                $is_select_plugins = true;
                            }
                            else
                            {
                                $is_select_plugins = false;
                            }

                            if($exclude_path['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }

                    $plugins_folder_exclude_list = array();
                    $plugins_file_exclude_list = array();
                    $plugins_exclude_list = $this->get_exclude_folder_file_list('plugins', $type);
                    if($is_select_exclude_ex)
                    {
                        if(!empty($plugins_exclude_list))
                        {
                            foreach ($plugins_exclude_list as $key => $value)
                            {
                                if(isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $plugins_folder_exclude_list[] = $key;
                                    }
                                    else{
                                        $plugins_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $plugins_folder_exclude_list[] = $value;
                                }
                            }
                        }
                        $plugins_exclude_size = self::_get_exclude_folder_file_size($plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);
                    }
                    else
                    {
                        $plugins_exclude_size = 0;
                    }
                    if($is_select_plugins)
                    {
                        $plugins_size = self::get_custom_path_size('plugins', $plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);
                    }
                    else
                    {
                        $plugins_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['plugins_size'] = $plugins_size;
                    $website_size[$type]['plugins_exclude_size'] = $plugins_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['plugins_size'] = size_format($plugins_size, 2);
                }

                if($website_item === 'uploads')
                {
                    $upload_dir = wp_upload_dir();
                    $path = $upload_dir['basedir'];
                    $path = str_replace('\\','/',$path);
                    $uploads_path = $path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_uploads = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['uploads_option']['uploads_check'] == '1')
                            {
                                $is_select_uploads = true;
                            }
                            else
                            {
                                $is_select_uploads = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }
                    else
                    {
                        $type = 'general';
                        $exclude_path = get_option('wpvivid_custom_backup_history');

                        if(empty($exclude_path))
                        {
                            $is_select_uploads = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($exclude_path['uploads_option']['uploads_check'] == '1')
                            {
                                $is_select_uploads = true;
                            }
                            else
                            {
                                $is_select_uploads = false;
                            }

                            if($exclude_path['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                    }

                    $uploads_folder_exclude_list = array();
                    $uploads_file_exclude_list = array();
                    $uploads_exclude_list = $this->get_exclude_folder_file_list('uploads', $type);
                    if($is_select_exclude_ex)
                    {
                        if(!empty($uploads_exclude_list))
                        {
                            foreach ($uploads_exclude_list as $key => $value)
                            {
                                if (isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $uploads_folder_exclude_list[] = $key;
                                    }
                                    else{
                                        $uploads_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $uploads_folder_exclude_list[] = $key;
                                }
                            }
                        }
                        $uploads_exclude_size = self::_get_exclude_folder_file_size($uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);
                    }
                    else
                    {
                        $uploads_exclude_size = 0;
                    }
                    if($is_select_uploads)
                    {
                        $uploads_size = self::get_custom_path_size('uploads', $uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);
                    }
                    else
                    {
                        $uploads_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['uploads_size'] = $uploads_size;
                    $website_size[$type]['uploads_exclude_size'] = $uploads_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['uploads_size'] = size_format($uploads_size, 2);
                }

                if($website_item === 'additional_folder')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_additional = false;
                        }
                        else
                        {
                            if($custom_incremental_file['other_option']['other_check'] == '1')
                            {
                                $is_select_additional = true;
                            }
                            else
                            {
                                $is_select_additional = false;
                            }
                        }
                    }
                    else
                    {
                        $type = 'general';
                        $exclude_path = get_option('wpvivid_custom_backup_history');

                        if(empty($exclude_path))
                        {
                            $is_select_additional = false;
                        }
                        else
                        {
                            if($exclude_path['other_option']['other_check'] == '1')
                            {
                                $is_select_additional = true;
                            }
                            else
                            {
                                $is_select_additional = false;
                            }
                        }
                    }

                    $additional_folder_include_list = array();
                    $additional_file_include_list = array();
                    $additional_include_list = $this->get_exclude_folder_file_list('additional', $type);
                    if(!empty($additional_include_list))
                    {
                        foreach ($additional_include_list as $key => $value)
                        {
                            if (isset($value['type']))
                            {
                                if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                    $additional_folder_include_list[] = $key;
                                }
                                else{
                                    $additional_file_include_list[] = $key;
                                }
                            }
                            else
                            {
                                $additional_folder_include_list[] = $key;
                            }
                        }
                    }
                    if($is_select_additional)
                    {
                        $additional_size = self::get_custom_path_size('additional', $home_path, $additional_folder_include_list, $additional_file_include_list);
                    }
                    else
                    {
                        $additional_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['additional_size'] = $additional_size;
                    $website_size[$type]['calctime'] = time();
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['additional_size'] = size_format($additional_size, 2);

                    $database_size=isset($website_size[$type]['database_size'])?$website_size[$type]['database_size']:0;
                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $additional_size=isset($website_size[$type]['additional_size'])?$website_size[$type]['additional_size']:0;

                    $content_exclude_size=isset($website_size[$type]['content_exclude_size'])?$website_size[$type]['content_exclude_size']:0;
                    $themes_exclude_size=isset($website_size[$type]['themes_exclude_size'])?$website_size[$type]['themes_exclude_size']:0;
                    $plugins_exclude_size=isset($website_size[$type]['plugins_exclude_size'])?$website_size[$type]['plugins_exclude_size']:0;
                    $uploads_exclude_size=isset($website_size[$type]['uploads_exclude_size'])?$website_size[$type]['uploads_exclude_size']:0;

                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                    $ret['total_exclude_file_size'] = size_format($themes_exclude_size+$plugins_exclude_size+$uploads_exclude_size+$content_exclude_size, 2);
                    $ret['total_content_size'] = size_format($database_size+$core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                }

                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function recalc_backup_size()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            if(isset($_POST['custom_option'])&&!empty($_POST['custom_option'])&&isset($_POST['website_item'])&&!empty($_POST['website_item']))
            {
                $json = $_POST['custom_option'];
                $json = stripslashes($json);
                $json = json_decode($json, true);

                $website_item = sanitize_key($_POST['website_item']);

                $ret['result']='success';

                if($website_item === 'database')
                {
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom']['db']['database_check'] == '1')
                        {
                            $is_select_db = true;
                        }
                        else
                        {
                            $is_select_db = false;
                        }

                        if($json['custom']['db']['additional_database_check'] == '1')
                        {
                            $is_select_db_additional = true;
                        }
                        else
                        {
                            $is_select_db_additional = false;
                        }

                        $database_exclude_list = isset($json['custom']['db']['database_list']) ? $json['custom']['db']['database_list'] : array();
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['database_check'] == '1')
                        {
                            $is_select_db = true;
                        }
                        else
                        {
                            $is_select_db = false;
                        }

                        if($json['custom_dirs']['additional_database_check'] == '1')
                        {
                            $is_select_db_additional = true;
                        }
                        else
                        {
                            $is_select_db_additional = false;
                        }

                        $database_exclude_list = isset($json['custom_dirs']['database_list']) ? $json['custom_dirs']['database_list'] : array();
                    }

                    $ret = $this->_get_custom_database_size($is_select_db, $is_select_db_additional, $database_exclude_list, true);
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['database_size'] = $ret['database_size'];
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['database_size'] = size_format($ret['database_size'], 2);
                }

                if($website_item === 'core')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom']['files']['core_check'] == '1')
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            $is_select_core = false;
                        }
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['core_check'] == '1')
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            $is_select_core = false;
                        }
                    }

                    $core_folder_exclude_list = array('wp-admin', 'wp-includes', 'lotties');
                    $core_file_exclude_list = array('.htaccess', 'index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php',
                        'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
                    $core_exclude_list = array();
                    if($is_select_core)
                    {
                        $core_size = self::get_custom_path_size('core', $home_path, $core_folder_exclude_list, $core_file_exclude_list);
                    }
                    else
                    {
                        $core_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['core_size'] = $core_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['core_size'] = size_format($core_size, 2);
                }

                if($website_item === 'content')
                {
                    $content_dir = WP_CONTENT_DIR;
                    $path = str_replace('\\','/',$content_dir);
                    $content_path = $path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom']['files']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom']['files']['content_check'] == '1')
                        {
                            $is_select_content = true;
                        }
                        else
                        {
                            $is_select_content = false;
                        }

                        $content_exclude_list = isset($json['custom']['files']['content_list']) ? $json['custom']['files']['content_list'] : array();
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom_dirs']['content_check'] == '1')
                        {
                            $is_select_content = true;
                        }
                        else
                        {
                            $is_select_content = false;
                        }

                        $content_exclude_list = isset($json['custom_dirs']['content_list']) ? $json['custom_dirs']['content_list'] : array();
                    }

                    $local_setting = get_option('wpvivid_local_setting', array());
                    if(!empty($local_setting))
                    {
                        $content_folder_exclude_list = array('plugins', 'themes', 'uploads', 'wpvividbackups', $local_setting['path']);
                    }
                    else {
                        $content_folder_exclude_list = array('plugins', 'themes', 'uploads', 'wpvividbackups');
                    }
                    $content_folder_exclude_list_ex = array();
                    $content_file_exclude_list = array();
                    if($is_select_exclude_ex)
                    {
                        if(!empty($content_exclude_list))
                        {
                            foreach ($content_exclude_list as $key => $value)
                            {
                                if (isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $content_folder_exclude_list[] = $key;
                                        $content_folder_exclude_list_ex[] = $key;
                                    }
                                    else{
                                        $content_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $content_folder_exclude_list[] = $key;
                                    $content_folder_exclude_list_ex[] = $key;
                                }
                            }
                        }
                        $content_exclude_size = self::_get_exclude_folder_file_size($content_path, $content_folder_exclude_list_ex, $content_file_exclude_list);
                    }
                    else
                    {
                        $content_exclude_size = 0;
                    }
                    if($is_select_content)
                    {
                        $content_size = self::get_custom_path_size('content', $content_path, $content_folder_exclude_list, $content_file_exclude_list);
                    }
                    else
                    {
                        $content_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['content_size'] = $content_size;
                    $website_size[$type]['content_exclude_size'] = $content_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['content_size'] = size_format($content_size, 2);
                    $ret['content_size'] = size_format($content_size, 2);
                }

                if($website_item === 'themes')
                {
                    $themes_path = str_replace('\\','/', get_theme_root());
                    $themes_path = $themes_path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom']['files']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom']['files']['themes_check'] == '1')
                        {
                            $is_select_themes = true;
                        }
                        else
                        {
                            $is_select_themes = false;
                        }

                        $themes_exclude_list = isset($json['custom']['files']['themes_list']) ? $json['custom']['files']['themes_list'] : array();
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom_dirs']['themes_check'] == '1')
                        {
                            $is_select_themes = true;
                        }
                        else
                        {
                            $is_select_themes = false;
                        }

                        $themes_exclude_list = isset($json['custom_dirs']['themes_list']) ? $json['custom_dirs']['themes_list'] : array();
                    }

                    $themes_folder_exclude_list = array();
                    $themes_file_exclude_list = array();
                    if($is_select_exclude_ex)
                    {
                        if(!empty($themes_exclude_list))
                        {
                            foreach ($themes_exclude_list as $key => $value)
                            {
                                if(isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $themes_folder_exclude_list[] = $key;
                                    }
                                    else{
                                        $themes_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $themes_folder_exclude_list[] = $key;
                                }
                            }
                        }
                        $themes_exclude_size = self::_get_exclude_folder_file_size($themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);
                    }
                    else
                    {
                        $themes_exclude_size = 0;
                    }
                    if($is_select_themes)
                    {
                        $themes_size = self::get_custom_path_size('themes', $themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);
                    }
                    else
                    {
                        $themes_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['themes_size'] = $themes_size;
                    $website_size[$type]['themes_exclude_size'] = $themes_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['themes_size'] = size_format($themes_size, 2);
                }

                if($website_item === 'plugins')
                {
                    $plugins_path = str_replace('\\','/', WP_PLUGIN_DIR);
                    $plugins_path = $plugins_path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom']['files']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom']['files']['plugins_check'] == '1')
                        {
                            $is_select_plugins = true;
                        }
                        else
                        {
                            $is_select_plugins = false;
                        }

                        $plugins_exclude_list = isset($json['custom']['files']['plugins_list']) ? $json['custom']['files']['plugins_list'] : array();
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom_dirs']['plugins_check'] == '1')
                        {
                            $is_select_plugins = true;
                        }
                        else
                        {
                            $is_select_plugins = false;
                        }

                        $plugins_exclude_list = isset($json['custom_dirs']['plugins_list']) ? $json['custom_dirs']['plugins_list'] : array();
                    }

                    $plugins_folder_exclude_list = array();
                    $plugins_file_exclude_list = array();
                    if($is_select_exclude_ex)
                    {
                        if(!empty($plugins_exclude_list))
                        {
                            foreach ($plugins_exclude_list as $key => $value)
                            {
                                if(isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $plugins_folder_exclude_list[] = $key;
                                    }
                                    else{
                                        $plugins_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $plugins_folder_exclude_list[] = $key;
                                }
                            }
                        }
                        $plugins_exclude_size = self::_get_exclude_folder_file_size($plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);
                    }
                    else
                    {
                        $plugins_exclude_size = 0;
                    }
                    if($is_select_plugins)
                    {
                        $plugins_size = self::get_custom_path_size('plugins', $plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);
                    }
                    else
                    {
                        $plugins_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['plugins_size'] = $plugins_size;
                    $website_size[$type]['plugins_exclude_size'] = $plugins_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['plugins_size'] = size_format($plugins_size, 2);
                }

                if($website_item === 'uploads')
                {
                    $upload_dir = wp_upload_dir();
                    $path = $upload_dir['basedir'];
                    $path = str_replace('\\','/',$path);
                    $uploads_path = $path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom']['files']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom']['files']['uploads_check'] == '1')
                        {
                            $is_select_uploads = true;
                        }
                        else
                        {
                            $is_select_uploads = false;
                        }

                        $uploads_exclude_list = isset($json['custom']['files']['uploads_list']) ? $json['custom']['files']['uploads_list'] : array();
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['exclude_custom'] == '1')
                        {
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            $is_select_exclude_ex = false;
                        }

                        if($json['custom_dirs']['uploads_check'] == '1')
                        {
                            $is_select_uploads = true;
                        }
                        else
                        {
                            $is_select_uploads = false;
                        }

                        $uploads_exclude_list = isset($json['custom_dirs']['uploads_list']) ? $json['custom_dirs']['uploads_list'] : array();
                    }

                    $uploads_folder_exclude_list = array();
                    $uploads_file_exclude_list = array();
                    if($is_select_exclude_ex)
                    {
                        if(!empty($uploads_exclude_list))
                        {
                            foreach ($uploads_exclude_list as $key => $value)
                            {
                                if (isset($value['type']))
                                {
                                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                        $uploads_folder_exclude_list[] = $key;
                                    }
                                    else{
                                        $uploads_file_exclude_list[] = $key;
                                    }
                                }
                                else
                                {
                                    $uploads_folder_exclude_list[] = $key;
                                }
                            }
                        }
                        $uploads_exclude_size = self::_get_exclude_folder_file_size($uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);
                    }
                    else
                    {
                        $uploads_exclude_size = 0;
                    }
                    if($is_select_uploads)
                    {
                        $uploads_size = self::get_custom_path_size('uploads', $uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);
                    }
                    else
                    {
                        $uploads_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['uploads_size'] = $uploads_size;
                    $website_size[$type]['uploads_exclude_size'] = $uploads_exclude_size;
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['uploads_size'] = size_format($uploads_size, 2);
                }

                if($website_item === 'additional_folder')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom']['files']['other_check'] == '1')
                        {
                            $is_select_additional = true;
                        }
                        else
                        {
                            $is_select_additional = false;
                        }

                        $additional_include_list = isset($json['custom']['files']['other_list']) ? $json['custom']['files']['other_list'] : array();
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['other_check'] == '1')
                        {
                            $is_select_additional = true;
                        }
                        else
                        {
                            $is_select_additional = false;
                        }

                        $additional_include_list = isset($json['custom_dirs']['other_list']) ? $json['custom_dirs']['other_list'] : array();
                    }

                    $additional_folder_include_list = array();
                    $additional_file_include_list = array();
                    if(!empty($additional_include_list))
                    {
                        foreach ($additional_include_list as $key => $value)
                        {
                            if (isset($value['type']))
                            {
                                if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                    $additional_folder_include_list[] = $key;
                                }
                                else{
                                    $additional_file_include_list[] = $key;
                                }
                            }
                            else
                            {
                                $additional_folder_include_list[] = $key;
                            }
                        }
                    }
                    if($is_select_additional)
                    {
                        $additional_size = self::get_custom_path_size('additional', $home_path, $additional_folder_include_list, $additional_file_include_list);
                    }
                    else
                    {
                        $additional_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['additional_size'] = $additional_size;
                    $website_size[$type]['calctime'] = time();
                    update_option('wpvivid_custom_select_website_size', $website_size, 'no');
                    $ret['additional_size'] = size_format($additional_size, 2);

                    $database_size=isset($website_size[$type]['database_size'])?$website_size[$type]['database_size']:0;
                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $additional_size=isset($website_size[$type]['additional_size'])?$website_size[$type]['additional_size']:0;

                    $content_exclude_size=isset($website_size[$type]['content_exclude_size'])?$website_size[$type]['content_exclude_size']:0;
                    $themes_exclude_size=isset($website_size[$type]['themes_exclude_size'])?$website_size[$type]['themes_exclude_size']:0;
                    $plugins_exclude_size=isset($website_size[$type]['plugins_exclude_size'])?$website_size[$type]['plugins_exclude_size']:0;
                    $uploads_exclude_size=isset($website_size[$type]['uploads_exclude_size'])?$website_size[$type]['uploads_exclude_size']:0;

                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                    $ret['total_exclude_file_size'] = size_format($themes_exclude_size+$plugins_exclude_size+$uploads_exclude_size+$content_exclude_size, 2);
                    $ret['total_content_size'] = size_format($database_size+$core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                }

                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public static function get_custom_path_size($type, $path, $folder_exclude_list, $file_exclude_list, $size=0){
        if(!function_exists('get_home_path'))
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        $home_path = str_replace('\\','/', get_home_path());
        $core_file_arr = array('.htaccess', 'index', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php',
            'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..") {
                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            if($type === 'core' && $home_path === $path || $type === 'additional')
                            {
                                if(in_array($filename, $folder_exclude_list))
                                {
                                    $size=self::get_custom_path_size($type, $path . DIRECTORY_SEPARATOR . $filename, $folder_exclude_list, $file_exclude_list, $size);
                                }
                            }
                            else
                            {
                                if(!in_array($filename, $folder_exclude_list))
                                {
                                    $size=self::get_custom_path_size($type, $path . DIRECTORY_SEPARATOR . $filename, $folder_exclude_list, $file_exclude_list, $size);
                                }
                            }
                        }
                        else {
                            if($type === 'core' || $type === 'additional')
                            {
                                if($home_path === $path){
                                    if(in_array($filename, $file_exclude_list)){
                                        $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                                    }
                                }
                                else{
                                    $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                                }
                            }
                            else
                            {
                                if(!in_array($filename, $file_exclude_list)){
                                    $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                                }
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }

    public function _get_custom_database_size($is_select_db, $is_select_db_additional, $exclude_table_list, $recalc)
    {
        global $wpdb;
        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        if (is_null($tables)) {
            $ret['result'] = 'failed';
            $ret['database_size'] = 0;
            $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
            return $ret;
        }

        $db_size = 0;

        if($is_select_db)
        {
            $base_table_size = 0;

            foreach ($tables as $row) {
                if($recalc)
                {
                    if(!in_array($row['Name'], $exclude_table_list))
                    {
                        $base_table_size += ($row["Data_length"] + $row["Index_length"]);
                    }
                }
                else
                {
                    global $wpdb;
                    if (is_multisite() && !defined('MULTISITE')) {
                        $prefix = $wpdb->base_prefix;
                    } else {
                        $prefix = $wpdb->get_blog_prefix(0);
                    }
                    if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                        if (!empty($exclude_table_list))
                        {
                            if(!in_array($row['Name'], $exclude_table_list))
                            {
                                $base_table_size += ($row["Data_length"] + $row["Index_length"]);
                            }
                        }
                    }
                    else
                    {
                        if(!in_array($row['Name'], $exclude_table_list))
                        {
                            $base_table_size += ($row["Data_length"] + $row["Index_length"]);
                        }
                    }
                }
            }
        }
        else
        {
            $base_table_size = 0;
        }

        if($is_select_db_additional)
        {
            $additional_table_size = 0;
            $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
            if(empty($history)){
                $history = array();
            }
            if(isset($history['additional_database_option'])){
                if(isset($history['additional_database_option']['additional_database_list'])) {
                    foreach ($history['additional_database_option']['additional_database_list'] as $database => $db_info)
                    {
                        $db_user = $db_info['db_user'];
                        $db_pass = $db_info['db_pass'];
                        $db_host = $db_info['db_host'];
                        $additional_db = new wpdb($db_user, $db_pass, $database, $db_host);
                        $tables = $additional_db->get_results('SHOW TABLE STATUS', ARRAY_A);
                        if (is_null($tables)) {
                            continue;
                        }
                        foreach ($tables as $row) {
                            $additional_table_size += ($row["Data_length"] + $row["Index_length"]);
                        }
                    }
                }
            }
        }
        else
        {
            $additional_table_size = 0;
        }

        $db_size = $base_table_size+$additional_table_size;

        $ret['database_size'] = $db_size;
        $ret['result']='success';

        return $ret;
    }

    public static function _get_exclude_folder_file_size($path, $folder_exclude_list, $file_exclude_list, $size = 0)
    {
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..") {
                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            if(in_array($filename, $folder_exclude_list))
                            {
                                $size+=self::_get_single_folder_size($path . DIRECTORY_SEPARATOR . $filename, 0);
                            }
                        }
                        else {
                            if(in_array($filename, $file_exclude_list))
                            {
                                $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }

    public static function _get_single_folder_size($path, $size = 0)
    {
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..") {
                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            $size=self::_get_single_folder_size($path . DIRECTORY_SEPARATOR . $filename, $size);
                        }
                        else {
                            $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }

    public function wpvivid_backup_do_js_addon()
    {
        $ret = $this->_list_tasks_addon();
        ?>
        <script>
            <?php
            $general_setting=WPvivid_Setting::get_setting(true, "");
            if(!isset($general_setting['options']['wpvivid_common_setting']['estimate_backup'])&&$general_setting['options']['wpvivid_common_setting']['estimate_backup'] == 0)
            {
            ?>
            jQuery('.wpvivid_estimate_backup_info').hide();
            <?php
            }
            ?>
            var data = <?php echo json_encode($ret) ?>;
            wpvivid_list_task_data(data);
        </script>
        <?php
    }

    public function init_page()
    {
        if ( ! function_exists( 'is_plugin_active' ) )
        {
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
        }
        do_action('wpvivid_before_setup_page');


        if(isset($_REQUEST['auto_backup'])&&$_REQUEST['auto_backup']==1) {
            return;
        }
        $slug = apply_filters('wpvivid_access_white_label_slug', 'wpvivid_white_label');
        if(isset($_REQUEST[$slug])&&$_REQUEST[$slug]==1) {
            return;
        }

        $remoteslist=WPvivid_Setting::get_all_remote_options();
        $has_remote = false;
        foreach ($remoteslist as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            else
            {
                $has_remote = true;
            }
        }

        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup-remote'))
        {
            if($has_remote)
            {
                $default_backup_local = 'checked';
                $default_backup_remote = '';
            }
            else
            {
                $general_setting=WPvivid_Setting::get_setting(true, "");
                if(isset($general_setting['options']['wpvivid_common_setting']['default_backup_local']))
                {
                    if($general_setting['options']['wpvivid_common_setting']['default_backup_local'])
                    {
                        $default_backup_local = 'checked';
                        $default_backup_remote = '';
                    }
                    else
                    {
                        $default_backup_local = '';
                        $default_backup_remote = 'checked';
                    }
                }
                else
                {
                    $default_backup_local = 'checked';
                    $default_backup_remote = '';
                }
            }
        }
        else
        {
            $default_backup_local = 'checked';
            $default_backup_remote = '';
        }

        $options = get_option('wpvivid_custom_backup_history');

        ?>
        <div class="wrap wpvivid-canvas">
            <div class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Manual Backup', 'wpvivid' ); ?></h1>
            <div id="wpvivid_backup_notice"></div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <?php $this->welcome_bar();?>
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <!---  backup progress --->
                                    <?php
                                    $this->add_progress();
                                    ?>

                                    <div class="wpvivid-one-coloum">
                                        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                                            <div style="">
                                                <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                                                <div style="padding-left:2em;">
                                                    <label class="">
                                                        <input type="radio" option="backup" name="backup_to" value="local" <?php esc_attr_e($default_backup_local); ?> />Backup to localhost
                                                    </label>
                                                    <span style="padding:0 0.2em;"></span>
                                                    <?php
                                                    if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup-remote'))
                                                    {
                                                        ?>
                                                        <label class="">
                                                            <input type="radio" option="backup" name="backup_to" value="remote" <?php esc_attr_e($default_backup_remote); ?> />Backup to remote storage
                                                        </label>
                                                        <!--<span style="padding:0 1em;"></span>
                                                        <label class="">
                                                            <input type="radio" option="backup" name="backup_to" value="migrate_remote" />Migrate the site via remote storage
                                                        </label>-->
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                            </div>

                                            <div style="">
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
                                                        <div>
                                                            <div style="">
                                                                <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                                                                    <?php
                                                                    $html = '';
                                                                    echo apply_filters('wpvivid_add_backup_type_addon', $html, 'backup_files');
                                                                    ?>
                                                                </fieldset>
                                                            </div>
                                                            <div id="wpvivid_custom_manual_backup_mu_single_site_list" style="display: none;">
                                                                <p>Choose the childsite you want to backup</p>
                                                                <p>
                                                                    <span style="padding-right:0.2em;">
                                                                        <input type="search" style="margin-bottom: 4px; width:300px;" class="wpvivid-mu-single-site-search-input" placeholder="Enter title, url or description" name="s" value="">
                                                                    </span>
                                                                    <span><input type="submit" class="button wpvivid-mu-single-search-submit" value="Search"></span>
                                                                </p>

                                                                <div class="wpvivid_mu_single_site_list"></div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>

                                            </div>

                                            <div id="wpvivid_custom_manual_backup" style="display: none;">
                                                <?php
                                                $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_manual_backup','manual_backup','1','0');
                                                $custom_backup_manager->output_custom_backup_table();
                                                $custom_backup_manager->load_js();
                                                ?>
                                            </div>
                                            <div id="wpvivid_custom_manual_backup_mu_single_site" style="display: none;">
                                                <?php
                                                $type = 'manual_backup';
                                                do_action('wpvivid_custom_backup_setting', 'wpvivid_custom_manual_backup_mu_single_site_list', 'wpvivid_custom_manual_backup_mu_single_site', $type, '0');
                                                ?>
                                            </div>
                                            <div>
                                                <p>
                                                    <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                                                    <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="backup" name="backup_prefix" id="wpvivid_set_manual_prefix" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="Mybackup">
                                                </p>
                                            </div>
                                            <div style="margin-bottom:-1em;border-top:1px solid #f1f1f1;padding-top:1em;">
                                                <input class="button-primary" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" id="wpvivid_quickbackup_btn" type="submit" value="Backup Now">
                                                <div class="wpvivid-element-space-bottom" style="text-align: left; display: none;">
                                                    <label class="wpvivid-checkbox">
                                                        <span>Marking this backup can only be deleted manually</span>
                                                        <input type="checkbox" id="wpvivid_backup_lock" option="backup" name="lock">
                                                        <span class="wpvivid-checkbox-checkmark"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div style="clear:both;"></div>
                                            <!--<div class="wpvivid-two-col">
                                                <span>
                                                    <h2>Step 1: Choose backup location</h2>
                                                </span>
                                                <p></p>
                                                <fieldset>
                                                    <label class="wpvivid-radio">
                                                        <input type="radio" option="backup" name="backup_to" value="local" <?php esc_attr_e($default_backup_local); ?> />Save it to localhost
                                                        <span class="wpvivid-radio-checkmark"></span>
                                                    </label>
                                                    <?php
                                                    if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup-remote'))
                                                    {
                                                        ?>
                                                        <label class="wpvivid-radio">Send it to remote storage
                                                            <input type="radio" option="backup" name="backup_to" value="remote" <?php esc_attr_e($default_backup_remote); ?> />
                                                            <span class="wpvivid-radio-checkmark"></span>
                                                        </label>
                                                        <label class="wpvivid-radio">Migrate the site via remote storage, <a href="https://wpvivid.com/wpvivid-backup-pro-migrate-wordpress-site-via-remote-storage">read more...</a>
                                                            <input type="radio" option="backup" name="backup_to" value="migrate_remote">
                                                            <span class="wpvivid-radio-checkmark"></span>
                                                        </label>
                                                        <?php
                                                    }
                                                    ?>
                                                </fieldset>
                                            </div>

                                            <div class="wpvivid-two-col">
                                                <span>(Optional) Comment the backup</span>
                                                <p></p>
                                                <div>
                                                    <span>
                                                        <label>Comment the backup: </label>
                                                        <label><input type="text" option="backup" name="backup_prefix" id="wpvivid_set_manual_prefix" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')"></label>
                                                    </span>
                                                    <p>To comment scheduled backups for better identification, please go to the <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>">setting page</a>.</p>
                                                    <p><span>Sample:</span><span id="wpvivid_manual_prefix">*</span><span>_<?php echo apply_filters('wpvivid_white_label_plugin_name', 'wpvivid'); ?>-5ceb938b6dca9_2019-05-27-07-36_backup_all.zip</span></p>
                                                </div>
                                            </div>-->
                                        </div>
                                    </div>

                                    <!--<div class="wpvivid-one-coloum">
                                        <div class="wpvivid-workflow">
                                            <span>
                                                <h2>
                                                    <span style="line-height: 30px;">Step 2: Select what to back up</span>
                                                    <input class="button" type="submit" id="wpvivid_recalc_backup_size" value="Re-Calc" />
                                                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip" style="margin-top: 4px;">
                                                        <div class="wpvivid-bottom">
                                                            <p>Recalculate sizes of the contents to be backed up after you finish selecting them.</p>
                                                            <i></i>
                                                        </div>
                                                    </span>
                                                </h2>
                                            </span>
                                            <p></p>

                                            <?php
                                            if(!is_multisite())
                                            {
                                                $fieldset_style = 'display: none;';
                                                ?>
                                                <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                                                    <?php
                                                    /*$html = '';
                                                    echo apply_filters('wpvivid_add_backup_type_addon', $html);*/
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
                                                            /*$html = '';
                                                            echo apply_filters('wpvivid_add_backup_type_addon', $html);*/
                                                            ?>
                                                        </fieldset>
                                                    </div>
                                                    <div id="wpvivid_custom_manual_backup_mu_single_site_list" style="display: none;">
                                                        <p>Choose the childsite you want to backup</p>
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
                                            <div id="wpvivid_custom_manual_backup">
                                                <?php
                                                /*$general_setting=WPvivid_Setting::get_setting(true, "");
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
                                                    $custom_backup_manager = new WPvivid_Custom_Backup_Manager_Ex('wpvivid_custom_manual_backup','manual_backup');
                                                }
                                                else{
                                                    $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_manual_backup','manual_backup','1');
                                                }

                                                $custom_backup_manager->output_custom_backup_table();
                                                $custom_backup_manager->load_js();*/
                                                ?>
                                            </div>
                                            <div id="wpvivid_custom_manual_backup_mu_single_site" style="display: none;">
                                                <?php
                                                /*$type = 'manual_backup';
                                                do_action('wpvivid_custom_backup_setting', $type);*/
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="wpvivid-one-coloum">
                                        <div class="wpvivid-workflow">
                                            <span><h2>Step 3: Perform the backup</h2></span>
                                            <p></p>
                                            <div>
                                                <input class="button-primary" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" id="wpvivid_quickbackup_btn" type="submit" value="Backup Now"><span style="padding:1em;">or, <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-schedule', 'wpvivid-schedule'); ?>">create a backup schedule</a></span>
                                                <div class="wpvivid-element-space-bottom" style="text-align: left;">
                                                    <label class="wpvivid-checkbox">
                                                        <span>Marking this backup can only be deleted manually</span>
                                                        <input type="checkbox" id="wpvivid_backup_lock" option="backup" name="lock">
                                                        <span class="wpvivid-checkbox-checkmark"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>-->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- sidebar -->
                    <?php
                    do_action( 'wpvivid_backup_pro_add_sidebar' );
                    ?>
                </div>
            </div>
        </div>
        <script>
            var m_need_update_addon=false;
            var wpvivid_prepare_backup=false;
            var running_backup_taskid='';
            var task_retry_times = 0;

            jQuery(document).ready(function (){
                jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});

                wpvivid_activate_cron_addon();
                wpvivid_manage_task_addon();

                var wpvivid_manual_backup_table = wpvivid_manual_backup_table || {};
                wpvivid_manual_backup_table.init_refresh = false;

                var parent_id = 'wpvivid_custom_manual_backup';
                var type = 'manual_backup';
                if(!wpvivid_manual_backup_table.init_refresh){
                    wpvivid_manual_backup_table.init_refresh = true;
                    wpvivid_refresh_custom_backup_info(parent_id, type);
                    jQuery('#'+parent_id).find('.wpvivid-database-loading').addClass('is-active');
                    jQuery('#'+parent_id).find('.wpvivid-themes-plugins-loading').addClass('is-active');
                }

                var has_remote = '<?php echo $has_remote; ?>';
                jQuery(document).on('wpvivid-has-default-remote', function(event) {
                    wpvivid_check_has_default_remote(has_remote);
                });
            });

            function wpvivid_recalc_backup_size(website_item_arr, custom_option)
            {
                if(website_item_arr.length > 0)
                {
                    console.log(website_item_arr);
                    var website_item = website_item_arr.shift();
                    var ajax_data = {
                        'action': 'wpvivid_recalc_backup_size',
                        'website_item': website_item,
                        'custom_option': custom_option
                    };

                    wpvivid_post_request_addon(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                if(website_item === 'database')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-database-size').html(jsonarray.database_size);
                                }
                                if(website_item === 'core')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-core-size').html(jsonarray.core_size);
                                }
                                if(website_item === 'content')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-content-size').html(jsonarray.content_size);
                                }
                                if(website_item === 'themes')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-themes-size').html(jsonarray.themes_size);
                                }
                                if(website_item === 'plugins')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-plugins-size').html(jsonarray.plugins_size);
                                }
                                if(website_item === 'uploads')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-uploads-size').html(jsonarray.uploads_size);
                                }
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-additional-folder-size').html(jsonarray.additional_size);
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-total-file-size').html(jsonarray.total_file_size);
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                                    jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_backup_size(website_item_arr, custom_option);
                            }
                            else
                            {
                                alert(jsonarray.error);
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_backup_size(website_item_arr, custom_option);
                            }
                        }
                        catch (err) {
                            alert(err);
                            if(website_item === 'additional_folder')
                            {
                                jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                            wpvivid_recalc_backup_size(website_item_arr, custom_option);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        if(website_item === 'additional_folder')
                        {
                            jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                        wpvivid_recalc_backup_size(website_item_arr, custom_option);
                    });
                }
            }

            jQuery('#wpvivid_recalc_backup_size').click(function(){
                var custom_dirs = wpvivid_create_custom_setting_ex('manual_backup');
                var custom_option = {
                    'custom_dirs': custom_dirs
                };
                var custom_option = JSON.stringify(custom_option);

                jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-database-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-core-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-themes-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-plugins-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-uploads-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-content-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-additional-folder-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-total-file-size').html('calculating');
                jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-total-exclude-file-size').html('calculating');

                var website_item_arr = new Array('database', 'core', 'content', 'themes', 'plugins', 'uploads', 'additional_folder');

                wpvivid_recalc_backup_size(website_item_arr, custom_option);
            });

            jQuery('input:radio[option=backup][name=backup_to]').click(function(){
                var value = jQuery(this).val();
                if(value === 'remote'|| value === 'migrate_remote'){
                    jQuery( document ).trigger( 'wpvivid-has-default-remote');
                }
            });

            function wpvivid_check_has_default_remote(has_remote){
                if(!has_remote)
                {
                    var descript = 'There is no default remote storage configured. Please set it up first.';
                    var ret = confirm(descript);
                    if(ret === true)
                    {
                        location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote'); ?>';
                    }
                    jQuery('input:radio[option=backup][name=backup_to][value=local]').prop('checked', true);
                }
            }

            function wpvivid_activate_cron_addon(){
                var next_get_time = 3 * 60 * 1000;
                wpvivid_cron_task();
                setTimeout("wpvivid_activate_cron_addon()", next_get_time);
                setTimeout(function(){
                    m_need_update_addon=true;
                }, 10000);
            }

            function wpvivid_manage_task_addon() {
                if(m_need_update_addon === true){
                    m_need_update_addon = false;
                    wpvivid_check_runningtask_addon();
                }
                else{
                    setTimeout(function(){
                        wpvivid_manage_task_addon();
                    }, 3000);
                }
            }

            function wpvivid_check_runningtask_addon() {
                var ajax_data = {
                    'action': 'wpvivid_list_tasks_addon'
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    setTimeout(function ()
                    {
                        wpvivid_manage_task_addon();
                    }, 3000);
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        wpvivid_list_task_data(jsonarray);
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function ()
                    {
                        m_need_update_addon = true;
                        wpvivid_manage_task_addon();
                    }, 3000);
                });

            }

            function wpvivid_list_task_data(data) {
                var b_has_data = false;

                if(data.action !== 'auto_transfer'){
                    if(data.progress_html!==false)
                    {
                        jQuery('#wpvivid_postbox_backup_percent').show();
                        jQuery('#wpvivid_postbox_backup_percent').html(data.progress_html);
                    }
                    else
                    {
                        if(!wpvivid_prepare_backup)
                            jQuery('#wpvivid_postbox_backup_percent').hide();
                    }

                    var update_backup=false;
                    if (data.success_notice_html !== false)
                    {
                        jQuery('#wpvivid_backup_notice').show();
                        jQuery('#wpvivid_backup_notice').append(data.success_notice_html);
                        update_backup=true;
                    }
                    if(data.error_notice_html !== false)
                    {
                        jQuery('#wpvivid_backup_notice').show();
                        jQuery('#wpvivid_backup_notice').append(data.error_notice_html);
                        update_backup=true;
                    }

                    if(update_backup)
                    {
                        jQuery( document ).trigger( 'wpvivid_update_local_backup');
                        jQuery( document ).trigger( 'wpvivid_update_log_list');
                    }

                    if(data.need_refresh_remote !== false){
                        jQuery( document ).trigger( 'wpvivid_update_remote_backup');
                    }

                    if(data.last_msg_html !== false)
                    {
                        jQuery('#wpvivid_last_backup_msg').html(data.last_msg_html);
                    }

                    if(data.need_update)
                    {
                        m_need_update_addon = true;
                    }

                    if(data.running_backup_taskid!== '')
                    {
                        b_has_data = true;
                        task_retry_times = 0;
                        running_backup_taskid = data.running_backup_taskid;
                        wpvivid_control_backup_lock();
                        if(data.wait_resume)
                        {
                            if (data.next_resume_time !== 'get next resume time failed.')
                            {
                                wpvivid_resume_backup(running_backup_taskid, data.next_resume_time);
                            }
                            else {
                                wpvivid_delete_backup_task(running_backup_taskid);
                            }
                        }
                    }
                    else
                    {
                        if(!wpvivid_prepare_backup)
                        {
                            jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                            if(get_custom_table_retry.has_get_db_tables)
                                wpvivid_control_backup_unlock();
                        }
                        running_backup_taskid='';
                    }
                    if (!b_has_data)
                    {
                        task_retry_times++;
                        if (task_retry_times < 5)
                        {
                            m_need_update_addon = true;
                        }
                    }
                }
            }

            jQuery('input:radio[option=backup][name=backup_files]').click(function(){
                if(this.value === 'custom'){
                    jQuery('#wpvivid_custom_manual_backup').show();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').hide();
                    jQuery( document ).trigger( 'wpvivid_refresh_manual_backup_tables', 'manual_backup' );
                }
                else if(this.value === 'mu'){
                    jQuery('#wpvivid_custom_manual_backup').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site').show();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').show();
                }
                else{
                    jQuery('#wpvivid_custom_manual_backup').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').hide();
                }
            });

            jQuery('#wpvivid_set_manual_prefix').on("keyup", function(){
                var manual_prefix = jQuery('#wpvivid_set_manual_prefix').val();
                if(manual_prefix === ''){
                    manual_prefix = '*';
                    jQuery('#wpvivid_manual_prefix').html(manual_prefix);
                }
                else{
                    var reg = RegExp(/wpvivid/, 'i');
                    if (manual_prefix.match(reg)) {
                        jQuery('#wpvivid_set_manual_prefix').val('');
                        jQuery('#wpvivid_manual_prefix').html('*');
                        alert('You can not use word \'wpvivid\' to comment the backup.');
                    }
                    else{
                        jQuery('#wpvivid_manual_prefix').html(manual_prefix);
                    }
                }
            });

            <?php
            $general_setting=WPvivid_Setting::get_setting(true, "");
            if(isset($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui'])){
                if($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui']){
                    $use_new_custom_backup_ui = '1';
                }
                else{
                    $use_new_custom_backup_ui = '0';
                }
            }
            else{
                $use_new_custom_backup_ui = '0';
            }

            ?>
            var use_new_custom_backup_ui = '<?php echo $use_new_custom_backup_ui; ?>';

            function wpvivid_check_additional_folder_valid(type){
                if(type === 'manual_backup'){
                    var parent_id = 'wpvivid_custom_manual_backup';
                }
                if(use_new_custom_backup_ui == '1'){
                    var check_status = true;
                }
                else{
                    var check_status = false;
                    if(jQuery('input:radio[option=backup][name=backup_files][value=custom]').prop('checked')){
                        if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                            jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function(){
                                check_status = true;
                            });
                        }
                        else{
                            check_status = true;
                        }
                        if(check_status === false){
                            alert('Please select at least one item under the additional files/folder option, or deselect the option.');
                        }
                    }
                    else{
                        check_status = true;
                    }
                }
                return check_status;
            }

            function wpvivid_check_additional_db_valid(type){
                if(type === 'manual_backup'){
                    var parent_id = 'wpvivid_custom_manual_backup';
                }
                if(use_new_custom_backup_ui == '1'){
                    var check_status = true;
                }
                else{
                    var check_status = false;
                    if(jQuery('input:radio[option=backup][name=backup_files][value=custom]').prop('checked')){
                        if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                            jQuery('#'+parent_id).find('.wpvivid-additional-database-list div').find('span:eq(2)').each(function(){
                                check_status = true;
                            });
                        }
                        else{
                            check_status = true;
                        }
                        if(check_status === false){
                            alert('Please select at least one item under the additional database option, or deselect the option.');
                        }
                    }
                    else{
                        check_status = true;
                    }
                }

                return check_status;
            }

            function wpvivid_create_custom_setting_ex(custom_type){
                if(custom_type === 'manual_backup'){
                    var parent_id = 'wpvivid_custom_manual_backup';
                }
                var json = {};
                //exclude
                json['exclude_custom'] = '1';
                if(!jQuery('#'+parent_id).find('.wpvivid-custom-exclude-part').prop('checked')){
                    json['exclude_custom'] = '0';
                }

                //core
                json['core_check'] = '0';
                json['core_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                    json['core_check'] = '1';
                }

                //themes
                json['themes_check'] = '0';
                json['themes_list'] = {};
                json['themes_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                    json['themes_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['themes_list'][folder_name] = {};
                        json['themes_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['themes_extension'] = jQuery('#'+parent_id).find('.wpvivid-themes-extension').val();
                }

                //plugins
                json['plugins_check'] = '0';
                json['plugins_list'] = {};
                json['plugins_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                    json['plugins_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['plugins_list'][folder_name] = {};
                        json['plugins_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['plugins_extension'] = jQuery('#'+parent_id).find('.wpvivid-plugins-extension').val();
                }

                //content
                json['content_check'] = '0';
                json['content_list'] = {};
                json['content_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['content_list'][folder_name] = {};
                        json['content_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                }

                //uploads
                json['uploads_check'] = '0';
                json['uploads_list'] = {};
                json['upload_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['uploads_list'][folder_name] = {};
                        json['uploads_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                }

                //additional folders/files
                json['other_check'] = '0';
                json['other_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['other_check'] = '1';
                }
                if(json['other_check'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['other_list'][folder_name] = {};
                        json['other_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['other_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['other_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                }

                //database
                json['database_check'] = '0';
                json['database_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                    json['database_check'] = '1';
                }
                jQuery('input[name=manual_backup_database][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['database_list'].push(jQuery(value).val());
                    }
                });

                //additional database
                json['additional_database_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                    json['additional_database_check'] = '1';
                }

                return json;
            }

            function wpvivid_get_mu_site_setting(parent_id) {
                var json = {};
                json['mu_site_id']='';
                jQuery('input[name=mu_site][type=checkbox]').each(function(index, value)
                {
                    if(jQuery(value).prop('checked'))
                    {
                        json['mu_site_id']=jQuery(value).val();
                    }
                });

                json['exclude_custom'] = '1';
                if(!jQuery('#'+parent_id).find('.wpvivid-custom-exclude-part').prop('checked')){
                    json['exclude_custom'] = '0';
                }

                //themes
                json['themes_check'] = '0';
                json['themes_list'] = {};
                json['themes_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                    json['themes_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['themes_list'][folder_name] = {};
                        json['themes_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['themes_extension'] = jQuery('#'+parent_id).find('.wpvivid-themes-extension').val();
                }

                //plugins
                json['plugins_check'] = '0';
                json['plugins_list'] = {};
                json['plugins_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                    json['plugins_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['plugins_list'][folder_name] = {};
                        json['plugins_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['plugins_extension'] = jQuery('#'+parent_id).find('.wpvivid-plugins-extension').val();
                }

                //content
                json['content_check'] = '0';
                json['content_list'] = {};
                json['content_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['content_list'][folder_name] = {};
                        json['content_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                }

                //uploads
                json['uploads_check'] = '0';
                json['uploads_list'] = {};
                json['upload_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['uploads_list'][folder_name] = {};
                        json['uploads_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                }

                //additional folders/files
                json['additional_file_check'] = '0';
                json['additional_file_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['additional_file_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['additional_file_list'][folder_name] = {};
                        json['additional_file_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['additional_file_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['additional_file_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                }

                return json;
            }

            function wpvivid_control_backup_lock(){
                jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
            }

            function wpvivid_control_backup_unlock(){
                jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
            }

            jQuery('#wpvivid_quickbackup_btn').on('click', function(){
                wpvivid_clear_notice('wpvivid_backup_notice');
                var check_status = wpvivid_check_backup_option_avail('manual_backup');
                var check_status = true;
                if(check_status){
                    var backup_data = wpvivid_ajax_data_transfer('backup');
                    var action = 'wpvivid_prepare_backup_ex';
                    jQuery('input:radio[option=backup]').each(function (){
                        if(jQuery(this).prop('checked')){
                            var key = jQuery(this).prop('name');
                            var value = jQuery(this).prop('value');
                            if(value === 'custom'){
                                backup_data = JSON.parse(backup_data);
                                var custom_dirs = wpvivid_create_custom_setting_ex('manual_backup');
                                var custom_option = {
                                    'custom_dirs': custom_dirs
                                };
                                jQuery.extend(backup_data, custom_option);
                                backup_data = JSON.stringify(backup_data);
                            }
                            else if(value === 'mu'){
                                backup_data = JSON.parse(backup_data);
                                var perent_id = 'wpvivid_custom_manual_backup_mu_single_site';//'wpvivid_custom_mu_single_list';
                                var mu_setting = wpvivid_get_mu_site_setting(perent_id);
                                var custom_option = {
                                    'mu_setting': mu_setting
                                };
                                console.log(custom_option);
                                jQuery.extend(backup_data, custom_option);
                                backup_data = JSON.stringify(backup_data);
                                console.log(backup_data);
                            }
                        }
                    });

                    var ajax_data = {
                        'action': action,
                        'backup': backup_data
                    };
                    console.log(ajax_data);

                    wpvivid_control_backup_lock();
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_postbox_backup_percent').show();
                    jQuery('#wpvivid_current_doing').html('Ready to backup. Progress: 0%, running time: 0 second.');
                    var percent = '0%';
                    jQuery('.wpvivid-span-processed-percent-progress').css('width', percent);
                    jQuery('.wpvivid-span-processed-percent-progress').html(percent+' completed');
                    jQuery('#wpvivid_backup_database_size').html('N/A');
                    jQuery('#wpvivid_backup_file_size').html('N/A');
                    jQuery('#wpvivid_current_doing').html('');
                    wpvivid_prepare_backup = true;
                    wpvivid_post_request_addon(ajax_data, function (data) {
                        wpvivid_prepare_backup = false;
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'failed') {
                                wpvivid_delete_ready_task(jsonarray.error);
                            }
                            else if (jsonarray.result === 'success') {
                                /*
                                var descript = '';
                                if (jsonarray.check.alert_db === true || jsonarray.check.alter_files === true) {
                                    descript = 'The database (the dumping SQL file) might be too large, backing up the database may run out of server memory and result in a backup failure.\n' +
                                        'One or more files might be too large, backing up the file(s) may run out of server memory and result in a backup failure.\n' +
                                        'Click OK button and continue to back up.';
                                    var ret = confirm(descript);
                                    if (ret === true) {
                                        wpvivid_backup_now(jsonarray.task_id);
                                    }
                                    else {
                                        jQuery('#wpvivid_backup_cancel_btn').css({
                                            'pointer-events': 'auto',
                                            'opacity': '1'
                                        });
                                        wpvivid_control_backup_unlock();
                                        jQuery('#wpvivid_postbox_backup_percent').hide();
                                    }
                                }
                                else {
                                    wpvivid_backup_now(jsonarray.task_id);
                                }*/
                                wpvivid_backup_now(jsonarray.task_id);
                            }
                        }
                        catch (err) {
                            wpvivid_delete_ready_task(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        wpvivid_prepare_backup = false;
                        //var error_message = wpvivid_output_ajaxerror('preparing the backup', textStatus, errorThrown);
                        var error_message='Calculating the size of files, folder and database timed out. If you continue to receive this error, please go to the plugin settings, uncheck \'Calculate the size of files, folder and database before backing up\', save changes, then try again.';
                        wpvivid_delete_ready_task(error_message);
                    });
                }
            });

            function wpvivid_backup_now(task_id){
                var ajax_data = {
                    'action': 'wpvivid_backup_now',
                    'task_id': task_id
                };
                task_retry_times = 0;
                m_need_update_addon=true;
                wpvivid_post_request_addon(ajax_data, function(data){
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            function wpvivid_delete_backup_task(task_id){
                var ajax_data = {
                    'action': 'wpvivid_delete_task',
                    'task_id': task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data){}, function(XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            function wpvivid_delete_ready_task(error){
                var ajax_data={
                    'action': 'wpvivid_delete_ready_task'
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            wpvivid_add_notice('Backup', 'Error', error);
                            wpvivid_control_backup_unlock();
                            jQuery('#wpvivid_postbox_backup_percent').hide();
                        }
                    }
                    catch(err){
                        wpvivid_add_notice('Backup', 'Error', err);
                        wpvivid_control_backup_unlock();
                        jQuery('#wpvivid_postbox_backup_percent').hide();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        wpvivid_delete_ready_task(error);
                    }, 3000);
                });
            }

            function wpvivid_check_backup_option_avail(type){
                if(type === 'manual_backup'){
                    var parent_id = 'wpvivid_custom_manual_backup';
                }

                var check_status = true;

                //check is backup db or files
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-part').prop('checked')){
                    var has_db_item = false;
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked'))
                    {
                        has_db_item = true;
                        var has_local_table_item = false;
                        jQuery('#'+parent_id).find('input:checkbox[name=manual_backup_database]').each(function(index, value)
                        {
                            if(jQuery(this).prop('checked'))
                            {
                                has_local_table_item = true;
                            }
                        });
                        if(!has_local_table_item)
                        {
                            check_status = false;
                            alert('Please select at least one table to back up. Or, deselect the option \'Tables In The WordPress Database\' under the option \'Databases Will Be Backed up\'.');
                            return check_status;
                        }
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked'))
                    {
                        has_db_item = true;
                        var has_additional_db = false;
                        jQuery('#'+parent_id).find('.wpvivid-additional-database-list div').find('span:eq(2)').each(function()
                        {
                            has_additional_db = true;
                        });
                        if(!has_additional_db)
                        {
                            check_status = false;
                            alert('Please select at least one additional database to back up. Or, deselect the option \'Include Additional Databases\' under the option \'Databases Will Be Backed up\'.');
                            return check_status;
                        }
                    }
                    if(!has_db_item){
                        check_status = false;
                        alert('Please select at least one option from \'Tables In The WordPress Database\' and \'Additional Databases\' under the option \'Databases Will Be Backed up\'. Or, deselect the option \'Databases Will Be Backed up\'.');
                        return check_status;
                    }
                }
                if(jQuery('#'+parent_id).find('.wpvivid-custom-file-part').prop('checked')){
                    var has_file_item = false;
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                        has_file_item = true;
                        var has_additional_folder = false;
                        jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function(){
                            has_additional_folder = true;
                        });
                        if(!has_additional_folder){
                            check_status = false;
                            alert('Please select at least one additional file or folder under the option \'Files/Folders Will Be Backed up\', Or, deselect the option \'Non-WordPress Files/Folders\'.');
                            return check_status;
                        }
                    }
                    if(!has_file_item){
                        check_status = false;
                        alert('Please select at least one option under the option \'Files/Folders Will Be Backed up\'. Or, deselect the option \'Files/Folders Will Be Backed up\'.');
                        return check_status;
                    }
                }

                return check_status;
            }
        </script>
        <?php
        do_action('wpvivid_backup_do_js_addon');
    }

    public function welcome_bar()
    {
        ?>
        <div class="wpvivid-welcome-bar wpvivid-clear-float">
            <div class="wpvivid-welcome-bar-left">
                <p><span class="dashicons dashicons-backup wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">Back Up Manually</span></p>
                <p><span class="about-description">The page allows you to manually create a backup of the website for restoration or migration.</span></p>
            </div>
            <div class="wpvivid-welcome-bar-right">
                <p></p>
                <div style="float:right;">
                    <span>Local Time:</span>
                    <span>
                        <a href="<?php esc_attr_e(apply_filters('wpvivid_get_admin_url', '').'options-general.php'); ?>">
                            <?php
                            $offset=get_option('gmt_offset');
                            echo date("l, F-d-Y H:i",time()+$offset*60*60);
                            ?>
                        </a>
                    </span>
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                        <div class="wpvivid-left">
                            <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                </div>
            </div>
            <div class="wpvivid-nav-bar wpvivid-clear-float">
                <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                <span>Local Storage Directory:</span>
                <span>
                    <code>
                        <?php
                        _e(WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath());
                        ?>
                    </code>
                </span>
                <span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>">rename directory</a></span>
                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                            <div class="wpvivid-bottom">
                                                <p>Click to change WPvivid Pro custom backup folder.</p>
                                                <i></i> <!-- do not delete this line -->
                                            </div>
                                        </span>
                <span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore'); ?>">or view backups list</a></span>
                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                    <div class="wpvivid-bottom">
                        <!-- The content you need -->
                        <p>Click to browse and manage all your backups.</p>
                        <i></i> <!-- do not delete this line -->
                    </div>
            </div>
        </div>
        <?php
    }
}