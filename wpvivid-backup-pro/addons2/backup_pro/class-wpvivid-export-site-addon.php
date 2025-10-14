<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Admin_load: yes
 * Need_init: yes
 * Interface Name: WPvivid_Export_Site_Page_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Export_Site_Page_addon
{
    public $main_tab;

    public function __construct()
    {
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),11);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);
        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));

        $this->load_display_filters();
        $this->load_display_sidebar_action();
        $this->load_backup_ajaxs();

        //init
        add_action('wpvivid_export_do_js_addon', array($this, 'wpvivid_export_do_js_addon'), 11);
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-export-site';
        $cap['display']='Export Site';
        $cap['index']=14;
        $cap['icon']='<span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span>';
        $cap['menu_slug']=strtolower(sprintf('%s-export-site', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-export-site';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-export-site';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_export_site');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Export Site');
            $submenu['menu_title'] = 'Export Site';
            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-export-site");

            $submenu['menu_slug'] = strtolower(sprintf('%s-export-site', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 4;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_export_site');
        if($display)
        {
            $menu['id'] = 'wpvivid_admin_menu_export';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Export Site';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-export-site');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-export-site');
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-export-site");

            $menu['index'] = 4;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function load_display_filters()
    {
        add_filter('wpvivid_export_site_content_addon', array($this, 'export_site_content_addon'), 12, 2);
        add_filter('wpvivid_export_site_migration_addon', array($this, 'wpvivid_export_site_migration_addon'), 11);
    }

    public function load_display_sidebar_action()
    {
        add_action('wpvivid_page_add_sidebar', array($this, 'add_sidebar'), 10);
    }

    public function add_sidebar($type)
    {
        if(apply_filters('wpvivid_show_sidebar',true))
        {
            $href = '#';
            if($type === 'Export')
            {
                $href = 'https://docs.wpvivid.com/wpvivid-backup-pro-export-site.html';
            }
            else if($type === 'Import')
            {
                $href = 'https://docs.wpvivid.com/wpvivid-backup-pro-import-site.html';
            }
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
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span>
                                    <a href="<?php echo $href; ?>"><b><?php echo $type; ?></b></a>
                                    <?php
                                    if($type === 'Export')
                                    {
                                        ?>
                                        <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-export-site', 'wpvivid-export-site')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        <?php
                                    }
                                    else if($type === 'Import')
                                    {
                                        ?>
                                        <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-import-site', 'wpvivid-import-site')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        <?php
                                    }
                                    ?>
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

    public function load_backup_ajaxs()
    {
        add_action('wp_ajax_wpvivid_test_connect_export_site',array( $this,'test_connect_site'));
        add_action('wp_ajax_wpvivid_delete_export_site_transfer_key',array($this, 'delete_transfer_key'));

        add_action('wp_ajax_wpvivid_get_need_download_files', array($this, 'get_need_download_files'));
        add_action('wp_ajax_wpvivid_read_file_content',array($this, 'read_file_content'));
    }

    public function wpvivid_export_do_js_addon()
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

    public function add_monitor_event($task_id,$next_time=120)
    {
        $resume_time=time()+$next_time;

        $timestamp = wp_next_scheduled('wpvivid_task_monitor_event_ex',array($task_id));

        if($timestamp===false)
        {
            $b = wp_schedule_single_event($resume_time, 'wpvivid_task_monitor_event_ex', array($task_id));
            if ($b === false)
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        return true;
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
            if(isset($task['action']))
            {
                $ret['action'] = $task['action'];
            }

            $backup_task=new WPvivid_New_Backup_Task($task['id']);
            $info=$backup_task->get_backup_task_info();
            $ret['need_next_schedule']=$info['task_info']['need_next_schedule'];
            if($info['task_info']['need_next_schedule']===true)
            {
                $timestamp = wp_next_scheduled('wpvivid_task_monitor_event_ex',array($task['id']));
                if($timestamp===false)
                {
                    $this->add_monitor_event($task['id'],20);
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

                if($info['status']['str']=='no_responds')
                {
                    $ret['task_no_response']=true;
                }

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
                    if($finished_task['action_type'] == 'auto_transfer'){
                        $transfer_success_count++;
                    }
                    else{
                        $backup_success_count++;
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

    /******  export filters begin  ******/
    public function export_site_content_addon($html, $type_name)
    {
        ob_start();
        ?>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="files+db" checked="checked">
            <span>Wordpress Files + Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="db">
            <span>Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="files">
            <span>Wordpress Files</span>
        </label>
        <?php
        if(is_multisite())
        {
            ?>
            <label style="padding-right:2em;">
                <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="mu">
                <span> For the purpose of moving a subsite to a single install</span>
            </label>
            <?php
        }
        ?>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="custom">
            <span>Custom content</span>
        </label>
        <?php
        $html .= ob_get_clean();
        return $html;
    }

    public function wpvivid_export_site_migration_addon()
    {
        $options=WPvivid_Setting::get_option('wpvivid_saved_api_token');
        if(empty($options))
        {
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
        else
        {
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
            $this->add_progress('migration');
            ?>
            <div id="wpvivid_migration_export_site_error_notice"></div>

            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_migration_export_site_success_notice" style="background:#eaf1fe;border-radius:8px; margin-bottom:1em; display: none;">
                <h1 style="text-align:center; color:#8300e9; display: block;">Congratulations, exporting site succeeded</h1>

                <div style="">
                    <p style="text-align:center;">
                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                        <span><strong>The backup has been sent to your target Wordpress site, you can import it on target site, <a href="https://docs.wpvivid.com/wpvivid-backup-pro-auto-migration.html" target='_blank'>learn more...</a></strong></span>
                    </p>
                </div>

                <div>
                    <p style="text-align:center;"><span class="wpvivid-blue wpvivid-hide-migration-export-site-notice" style="padding: 0.5em 2em;border-radius:8px; cursor:pointer;">I got it</span>
                    </p>
                </div>
            </div>

            <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-bottom: 10px;">
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

            <div class="wpvivid-one-coloum wpvivid-workflow">
                <div>
                    <div>
                        <p>
                            <span class="dashicons dashicons-admin-site-alt wpvivid-dashicons-blue"></span>
                            <span>The backup will be sent to </span><span>your website <?php echo $target_dir; ?> directly</span>
                        </p>
                    </div>

                    <div style="padding:1em 1em 1em 1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <div>
                            <fieldset>
                                <?php
                                $html = '';
                                echo apply_filters('wpvivid_export_site_content_addon', $html, 'migration_export_site');
                                ?>
                            </fieldset>
                        </div>
                        <?php
                        if(is_multisite())
                        {
                            ?>
                            <div id="wpvivid_custom_migration_export_site_mu_single_site_list" style="display: none;">
                                <p>Choose the childsite you want to backup</p>
                                <p>
                                    <span style="padding-right:0.2em;">
                                        <input type="search" style="margin-bottom: 4px; width:300px;" class="wpvivid-mu-single-site-search-input" placeholder="Enter title, url or description" name="s" value="">
                                    </span>
                                    <span><input type="submit" class="button wpvivid-mu-single-search-submit" value="Search"></span>
                                </p>
                                <div class="wpvivid_mu_single_site_list">
                                    <?php
                                    $type = 'migration_export_site';
                                    do_action('wpvivid_select_mu_single_site', 'wpvivid_custom_migration_export_site_mu_single_site_list', $type);
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                    <div id="wpvivid_custom_migration_export_site" style="display: none;">
                        <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_migration_export_site','export_site','0','0');
                        //$custom_backup_manager->output_custom_backup_table();
                        $custom_backup_manager->output_custom_backup_db_table();
                        $custom_backup_manager->output_custom_backup_file_table();
                        ?>
                        </div>
                    </div>

                    <!--Advanced Option (Exclude)-->
                    <div id="wpvivid_custom_migration_export_advanced_option">
                        <?php
                        $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_custom_migration_export_advanced_option');
                        $custom_backup_manager->output_advanced_option_table();
                        $custom_backup_manager->load_js();
                        ?>
                    </div>

                    <div>
                        <p>
                            <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                            <?php
                            $general_setting=WPvivid_Setting::get_setting(true, "");
                            if(!isset($general_setting['options']['wpvivid_common_setting']['backup_prefix'])){
                                $home_url_prefix=get_home_url();
                                $parse = parse_url($home_url_prefix);
                                $path = '';
                                if(isset($parse['path'])) {
                                    $parse['path'] = str_replace('/', '_', $parse['path']);
                                    $parse['path'] = str_replace('.', '_', $parse['path']);
                                    $path = $parse['path'];
                                }
                                $parse['host'] = str_replace('/', '_', $parse['host']);
                                $prefix = $parse['host'].$path;
                            }
                            else{
                                $prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                            }
                            ?>
                            <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="migration_export_site" name="backup_prefix" id="wpvivid_set_migration_export_site_prefix" value="<?php esc_attr_e($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php esc_attr_e($prefix); ?>">
                        </p>
                    </div>

                    <div style="margin-bottom:-1em;border-top:1px solid #f1f1f1;padding-top:1em;">
                        <input type="submit" class="button-primary" id="wpvivid_migration_export_site" value="Export Now" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" >
                    </div>
                    <div class="wpvivid-element-space-bottom" style="text-align: left; display: none;">
                        <label class="wpvivid-checkbox">
                            <span>Marking this backup can only be deleted manually</span>
                            <input type="checkbox" option="migration_export_site" name="lock">
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </div>
                    <div style="clear:both;"></div>

                </div>
            </div>
            <?php
            $html = ob_get_clean();
        }
        return $html;
    }

    /******  export filters end  ******/

    /******  export ajax begin  ******/
    public function test_connect_site()
    {
        if(isset($_POST['url']))
        {
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->ajax_check_security('wpvivid-can-export-site');

            $url=strtok($_POST['url'],'?');

            if (filter_var($url, FILTER_VALIDATE_URL) === FALSE)
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']='The key is invalid.';
                echo json_encode($ret);
                die();
            }

            if($url==home_url())
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']='The key generated by this site cannot be added into this site.';
                echo json_encode($ret);
                die();
            }

            $query=parse_url ($_POST['url'],PHP_URL_QUERY);
            if($query===null)
            {
                $query=strtok('?');
            }
            parse_str($query,$query_arr);
            $token=$query_arr['token'];
            $expires=$query_arr['expires'];
            $domain=$query_arr['domain'];

            if ($expires != 0 && time() > $expires) {
                $ret['result'] = 'failed';
                $ret['error'] = 'The key has expired.';
                echo json_encode($ret);
                die();
            }

            $json['test_connect']=1;
            $json=json_encode($json);
            $crypt=new WPvivid_crypt(base64_decode($token));
            $data=$crypt->encrypt_message($json);
            if($data===false)
            {
                $ret['result'] = 'failed';
                $ret['error'] = 'Data encryption failed.';
                echo json_encode($ret);
                die();
            }
            $data=base64_encode($data);

            $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site_connect');
            $args['timeout']=30;
            $response=wp_remote_post($url,$args);

            if ( is_wp_error( $response ) )
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']= $response->get_error_message();
            }
            else
            {
                if($response['response']['code']==200)
                {
                    $res=json_decode($response['body'],1);
                    if($res!=null)
                    {
                        if($res['result']==WPVIVID_SUCCESS)
                        {
                            $ret['result']=WPVIVID_SUCCESS;

                            $options=WPvivid_Setting::get_option('wpvivid_saved_api_token');

                            $options[$url]['token']=$token;
                            $options[$url]['url']=$url;
                            $options[$url]['expires']=$expires;
                            $options[$url]['domain']=$domain;

                            delete_option('wpvivid_saved_api_token');
                            WPvivid_Setting::update_option('wpvivid_saved_api_token',$options);

                            $html='';
                            $i=0;
                            foreach ($options as $key=>$site)
                            {
                                $check_status='';
                                if($key==$url)
                                {
                                    $check_status='checked';
                                }

                                if($site['expires']>time())
                                {
                                    $date=date("l, F d, Y H:i", $site['expires']);
                                }
                                else
                                {
                                    $date='Token has expired';
                                }

                                $i++;
                                $html = apply_filters('wpvivid_export_site_migration_addon', $html);
                            }
                            $ret['html']= $html;

                        }
                        else
                        {
                            $ret['result']=WPVIVID_FAILED;
                            $ret['error']= $res['error'];
                        }
                    }
                    else
                    {
                        $ret['result']=WPVIVID_FAILED;
                        $ret['error']= $response['body'];
                        //$ret['error']= 'failed to parse returned data. Unable to retrieve the correct authorization data via HTTP request.';
                    }
                }
                else
                {
                    $ret['result']=WPVIVID_FAILED;
                    $ret['error']= 'upload error '.$response['response']['code'].' '.$response['body'];
                    //$response['body']
                }
            }

            echo json_encode($ret);
        }
        die();
    }

    public function delete_transfer_key()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-export-site');
        $ret['result']=WPVIVID_SUCCESS;
        delete_option('wpvivid_saved_api_token');
        $html='';
        $html = apply_filters('wpvivid_export_site_migration_addon', $html);
        $ret['html']=$html;
        echo json_encode($ret);
        die();
    }

    public function get_need_download_files()
    {
        try
        {
            $backup_id = sanitize_key($_POST['backup_id']);

            $backup_list=new WPvivid_New_BackupList();
            $backup = $backup_list->get_backup_by_id($backup_id);
            if(!$backup)
            {
                $ret['result']='failed';
                $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid');
                echo json_encode($ret);
                die();
            }
            $backup_item=new WPvivid_New_Backup_Item($backup);
            $files=$backup_item->get_files();
            $local_path=$backup_item->get_download_local_path();
            $file_arr = array();
            foreach ($files as $file)
            {
                $file_size = filesize($file);
                $file_name = str_replace($local_path, '', $file);
                $file_arr[$file_name]['file_name'] = $file_name;
                $file_arr[$file_name]['file_size'] = $file_size;
                $file_arr[$file_name]['file_md5']  = md5_file($file);
            }
            $ret['result'] = 'success';
            $ret['files'] = $file_arr;
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function read_file_content()
    {
        try
        {
            if (isset($_POST['file_name']) && !empty($_POST['file_name']))
            {
                $ret['result'] = WPVIVID_PRO_SUCCESS;

                $file_name = sanitize_text_field($_POST['file_name']);
                $file_path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$file_name;
                if(file_exists($file_path))
                {
                    if(isset($_POST['offset_size']))
                    {
                        $offset_size = sanitize_text_field($_POST['offset_size']);
                    }
                    else
                    {
                        $offset_size = 0;
                    }

                    if(isset($_POST['chunk_size']))
                    {
                        $chunk_size = sanitize_text_field($_POST['chunk_size']);
                    }
                    else
                    {
                        $chunk_size = 512*1024;
                    }

                    /*$size = filesize($file_path);
                    if (!headers_sent())
                    {
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                        header('Cache-Control: must-revalidate');
                        header('Content-Length: ' . $size);
                        header('Content-Transfer-Encoding: binary');
                        header('Transfer-Encoding: chunked');
                    }

                    ob_clean();
                    ob_flush();
                    flush();*/

                    header('Content-Type: application/octet-stream');
                    header('Content-Transfer-Encoding: binary');

                    if (ob_get_level() == 0) {
                        ob_start();
                    }

                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }

                    $file_handle = fopen($file_path,'rb');
                    if($file_handle===false)
                    {
                        $ret['result']=WPVIVID_PRO_FAILED;
                        $ret['error']='file not found. file name:'.$file_name;
                    }
                    else
                    {
                        fseek($file_handle, $offset_size);
                        print(@fread($file_handle, $chunk_size));
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        fclose($file_handle);
                        die();
                    }
                }
                else
                {
                    $ret['result'] = WPVIVID_PRO_FAILED;
                    $ret['error'] = 'File not found. File name: '.$file_name;
                }

                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>WPVIVID_PRO_FAILED, 'error'=>$message));
        }
        die();
    }
    /******  export ajax end  ******/

    public function init_page()
    {
        do_action('wpvivid_before_setup_page');
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Export Site', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-migrate wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">Export Site</span></p>
                                        <span class="about-description">Export the site to localhost(web server), remote storage or target site (auto-migration) for migration purpose.</span>
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
                                                    <!-- The content ou need -->
                                                    <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpvivid-nav-bar wpvivid-clear-float">
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span>Localhost Storage Directory:</span>
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





                                <div class="wpvivid-canvas wpvivid-clear-float">

                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $args['span_class']='dashicons dashicons-migrate wpvivid-dashicons-blue';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;display:block;';
                                    $args['is_parent_tab']=0;
                                    $tabs['localhost']['title']='Export to Localhost';
                                    $tabs['localhost']['slug']='localhost';
                                    $tabs['localhost']['callback']=array($this, 'output_export_to_localhost');
                                    $tabs['localhost']['args']=$args;

                                    $args['span_class']='dashicons dashicons-migrate wpvivid-dashicons-blue';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;
                                    $tabs['remote']['title']='Export to Remote Storage';
                                    $tabs['remote']['slug']='remote';
                                    $tabs['remote']['callback']=array($this, 'output_export_to_remote');
                                    $tabs['remote']['args']=$args;

                                    $args['span_class']='dashicons dashicons-migrate wpvivid-dashicons-blue';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;
                                    $tabs['migration']['title']='Export to Target Site(auto-migration)';
                                    $tabs['migration']['slug']='migration';
                                    $tabs['migration']['callback']=array($this, 'output_export_to_migration');
                                    $tabs['migration']['args']=$args;

                                    foreach ($tabs as $key=>$tab)
                                    {
                                        $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                                    }

                                    $this->main_tab->display();
                                    ?>

                                </div>




                            </div>
                        </div>
                    </div>

                    <!-- sidebar -->
                    <?php
                    do_action( 'wpvivid_page_add_sidebar', 'Export' );
                    ?>

                </div>
            </div>
        </div>

        <script>
            var m_need_update_addon=false;
            var wpvivid_prepare_backup=false;
            var running_backup_taskid='';
            var task_retry_times = 0;

            jQuery(document).ready(function($)
            {
                <?php
                if (isset($_GET['tab']))
                {
                $tab=$_GET['tab'];
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show','<?php echo $tab?>');
                <?php
                }
                ?>
                wpvivid_activate_cron_addon();
                wpvivid_manage_task_addon();

                var wpvivid_export_site_table = wpvivid_export_site_table || {};
                wpvivid_export_site_table.init_refresh = false;

                var parent_id = 'wpvivid_custom_local_export_site';
                var type = 'export_site';
                if(!wpvivid_export_site_table.init_refresh){
                    wpvivid_export_site_table.init_refresh = true;
                    wpvivid_refresh_custom_backup_info(parent_id, type);
                    wpvivid_get_website_all_size();
                    jQuery('#wpvivid_custom_local_export_site').find('.wpvivid-database-loading').addClass('is-active');
                    jQuery('#wpvivid_custom_remote_export_site').find('.wpvivid-database-loading').addClass('is-active');
                    jQuery('#wpvivid_custom_migration_export_site').find('.wpvivid-database-loading').addClass('is-active');
                    //jQuery('#wpvivid_custom_local_export_site').find('.wpvivid-themes-plugins-loading').addClass('is-active');
                }
            });

            function wpvivid_activate_cron_addon()
            {
                var next_get_time = 3 * 60 * 1000;
                wpvivid_cron_task();
                setTimeout("wpvivid_activate_cron_addon()", next_get_time);
                setTimeout(function() {
                    m_need_update_addon=true;
                }, 10000);
            }

            function wpvivid_manage_task_addon()
            {
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

            function wpvivid_check_runningtask_addon()
            {
                var ajax_data = {
                    'action': 'wpvivid_new_list_tasks_addon'
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

            jQuery('#wpvivid_local_export_site_backup_list').on("click", '.wpvivid-download-export-file', function() {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('div').attr('backup-id');
                var file_name=Obj.closest('div').attr('file-name');
                location.href =ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_backup_ex&backup_id='+backup_id+'&file_name='+file_name;
            });

            function wpvivid_list_task_data(data)
            {
                var b_has_data = false;

                var progress_id = 'wpvivid_local_export_site_progress';
                var error_notice_id = 'wpvivid_local_export_site_error_notice';
                if(data.export === 'local_export_site')
                {
                    progress_id = 'wpvivid_local_export_site_progress';
                    error_notice_id = 'wpvivid_local_export_site_error_notice';
                }
                else if(data.export === 'remote_export_site')
                {
                    progress_id = 'wpvivid_remote_export_site_progress';
                    error_notice_id = 'wpvivid_remote_export_site_error_notice';
                }
                else if(data.export === 'auto_migrate')
                {
                    progress_id = 'wpvivid_migration_export_site_progress';
                    error_notice_id = 'wpvivid_migration_export_site_error_notice';
                }

                if(data.progress_html!==false)
                {
                    jQuery('#'+progress_id).show();
                    jQuery('#'+progress_id).html(data.progress_html);
                }
                else
                {
                    if(!wpvivid_prepare_backup)
                        jQuery('#'+progress_id).hide();
                }

                var update_backup=false;
                /*if (data.success_notice_html !== false)
                {
                    jQuery('#'+notice_id).show();
                    jQuery('#'+notice_id).append(data.success_notice_html);
                    update_backup=true;
                }*/
                if(data.error_notice_html !== false)
                {
                    jQuery('#'+error_notice_id).show();
                    jQuery('#'+error_notice_id).html(data.error_notice_html);
                    update_backup=true;
                }
                if ( typeof data.local_export_file_complete !== 'undefined' )
                {
                    var is_set_all_download = false;
                    var export_site_html = '';
                    var export_site_all_download = '';
                    jQuery.each(data.local_export_files, function(filename, fileinfo){
                        export_site_html += '<div backup-id="'+data.task_id+'" file-name="'+filename+'" style="border-bottom:1px solid #cccccc;">' +
                            '<p>' +
                            '<span class="dashicons dashicons-format-aside wpvivid-dashicons-orange"></span>' +
                            '<span>'+filename+'</span>' +
                            '<span style="float:right;"><span class="dashicons dashicons-download wpvivid-dashicons-green"></span><span class="wpvivid-download-export-file"><a href="#">Download</a></span></span>' +
                            '</p>' +
                            '<p style="padding-left:2em;"><span>Size: </span><span>'+fileinfo.size+'</span></p>' +
                            '</div>';
                        if(wpvivid_dl_method!=0)
                        {
                            if( !is_set_all_download )
                            {
                                is_set_all_download = true;
                                export_site_all_download = '<div style="padding-top:1em;">' +
                                    '<p style="text-align:center;">' +
                                    '<span class="wpvivid-blue wpvivid-export-site-download-all" style="padding: 0.5em 2em;border-radius:8px; cursor:pointer;">Download All Parts</span>' +
                                    '<span> or </span><span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&localhost_allbackuplist'; ?>">View all backups</a></span>' +
                                    '</p>' +
                                    '</div>';

                            }
                        }
                    });
                    export_site_html += export_site_all_download;
                    jQuery('#wpvivid_local_export_site_backup_list').html(export_site_html);
                    jQuery('#wpvivid_local_export_site_success_notice').show();
                    update_backup=true;
                }

                if( typeof data.remote_export_file_complete !== 'undefined' )
                {
                    jQuery('#wpvivid_remote_export_site_success_notice').show();
                    update_backup=true;
                }

                if( typeof data.migration_export_file_complete !== 'undefined' )
                {
                    jQuery('#wpvivid_migration_export_site_success_notice').show();
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

                if(data.task_no_response)
                {
                    //jQuery('#wpvivid_current_doing').html('Task no response');
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
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
                        jQuery('.wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
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
        </script>
        <?php
        $this->download_tools();
        do_action('wpvivid_export_do_js_addon');
    }

    public function download_tools()
    {
        ?>
        <script>
            var wpvivid_download_list = Array();
            var wpvivid_downloading = false;
            var wpvivid_current_retry = 0;
            var wpvivid_max_retry = 3;
            var wpvivid_offset_size = 0;
            var wpvivid_chunk_size = 512*1024;
            var wpvivid_file_name;
            var wpvivid_file_size;
            var wpvivid_file_md5;
            var wpvivid_file_data;
            var wpvivid_dl_method = 0;
            var wpvivid_dl_blob_array = [];
            
            if(window.webkitRequestFileSystem)
            {
                window.requestFileSystem  = window.webkitRequestFileSystem;
                wpvivid_dl_method = 1;
            }
            else if ("download" in document.createElementNS("http://www.w3.org/1999/xhtml", "a"))
            {
                wpvivid_dl_method = 2;
            }

            function wpvivid_post_request_file(ajax_data, callback, error_callback, time_out)
            {
                if(typeof time_out === 'undefined')    time_out = 30000;
                ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
                jQuery.ajax({
                    type: "post",
                    url: wpvivid_ajax_object_addon.ajax_url,
                    data: ajax_data,
                    cache:false,
                    async: false,
                    dataType: "binary",
                    success: function (data) {
                        callback(data);
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        error_callback(XMLHttpRequest, textStatus, errorThrown);
                    },
                    timeout: time_out
                });
            }

            function wpvivid_get_next_download()
            {
                if(wpvivid_downloading)
                {
                    return;
                }

                if(wpvivid_download_list.length > 0)
                {
                    var download_info = wpvivid_download_list.shift();
                    wpvivid_file_name = download_info.file_name;
                    wpvivid_file_size = download_info.file_size;
                    wpvivid_file_md5  = download_info.file_md5;
                    wpvivid_offset_size = 0;
                    wpvivid_dl_blob_array = [];
                    wpvivid_start_download();
                }
                else
                {
                    alert('All files of the backup have been downloaded successfully.');
                    jQuery('.wpvivid-export-site-download-all').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_download_all_progress').remove();
                }
            }

            function wpvivid_download_retry()
            {
                if(wpvivid_current_retry < wpvivid_max_retry)
                {
                    wpvivid_start_download();
                    return true;
                }
                else
                {
                    jQuery('#wpvivid_download_all_progress').remove();
                    return false;
                }
            }

            function wpvivid_start_download()
            {
                var ajax_data = {
                    'action': 'wpvivid_read_file_content',
                    'file_name': wpvivid_file_name,
                    'chunk_size': wpvivid_chunk_size,
                    'offset_size': wpvivid_offset_size
                };
                wpvivid_post_request_file(ajax_data, function (data)
                {
                    wpvivid_current_retry=0;
                    try
                    {
                        wpvivid_file_data = data;
                        create_download_file();
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (code)
                {
                    wpvivid_current_retry++;
                    if(!wpvivid_download_retry())
                    {
                        alert('http error: '+code);
                    }
                });
            }

            function create_download_file()
            {
                if(wpvivid_dl_method==1)
                {
                    window.requestFileSystem(window.TEMPORARY, 50*1024*1024, initFSDownloadBackup, errorHandler);
                }
                else if(wpvivid_dl_method==2)
                {
                    wpvivid_blob_downloading();
                }
            }

            function wpvivid_blob_downloading()
            {
                wpvivid_downloading = true;
                var file_name = wpvivid_file_name;
                var file_data = wpvivid_file_data;
                wpvivid_dl_blob_array.push(file_data);
                var percent = parseInt((wpvivid_offset_size / wpvivid_file_size) * 100);
                if(percent > 100) percent = 100;
                jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').css('width', percent+'%');
                jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').html(percent+'%&nbspcompleted');
                jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-file-name').html('Downloading: '+wpvivid_file_name);
                if(wpvivid_offset_size < wpvivid_file_size)
                {
                    wpvivid_offset_size += wpvivid_chunk_size;
                    wpvivid_start_download();
                }
                else
                {
                    var a = document.getElementById('wpvivid_a_link');
                    var url=window.URL.createObjectURL(new Blob(wpvivid_dl_blob_array));
                    a.download = file_name;
                    a.href = url;
                    a.click();
                    setTimeout(function()
                    {
                        window.URL.revokeObjectURL(document.getElementById('wpvivid_a_link').href);
                    },100);
                    wpvivid_downloading = false;
                    wpvivid_dl_blob_array = [];
                    wpvivid_get_next_download();
                }
            }
            
            function initFSDownloadBackup(fs)
            {
                wpvivid_downloading = true;
                var file_name = wpvivid_file_name;
                var file_data = wpvivid_file_data;

                function createDir(rootDir, folders)
                {
                    rootDir.getDirectory(folders[0], {create: true, exclusive: true}, function(dirEntry) {
                        if (folders.length) {
                            createDir(dirEntry, folders.slice(1));
                        }
                    }, errorHandler);
                }
                createDir(fs.root, 'wpvividbackups'.split('/'));

                fs.root.getFile('wpvividbackups/'+file_name, {create: true, exclusive: false}, function(fileEntry)
                {
                    fileEntry.createWriter(function(fileWriter)
                    {
                        fileWriter.onerror = function(e)
                        {
                            fileEntry.remove(function() {
                                console.log('Delete success');
                            }, errorHandler);

                            console.log('Write failed: ' + e.toString());
                            wpvivid_downloading=false;
                            if(!wpvivid_download_retry())
                            {
                                alert('Download failed: ' + e.toString());
                            }
                        }

                        fileWriter.onwriteend = function()
                        {
                            var percent = parseInt((wpvivid_offset_size / wpvivid_file_size) * 100);
                            if(percent > 100) percent = 100;
                            jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').css('width', percent+'%');
                            jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').html(percent+'%&nbspcompleted');
                            jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-file-name').html('Downloading: '+wpvivid_file_name);

                            if(wpvivid_offset_size < wpvivid_file_size)
                            {
                                wpvivid_offset_size += wpvivid_chunk_size;
                                wpvivid_start_download();
                            }
                            else
                            {
                                readAndMD5();
                            }
                        }

                        let data = new Blob([file_data], { type: "application/zip" });
                        fileWriter.seek(wpvivid_offset_size);
                        fileWriter.write(data);

                    }, errorHandler);
                }, errorHandler);

                function readAndMD5()
                {
                    fs.root.getFile('wpvividbackups/'+file_name, {}, function(fileEntry) {
                        fileEntry.file( function(file) {
                            var blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice;
                            var chunkSize = 2097152;
                            var chunks = Math.ceil(file.size / chunkSize);
                            var currentChunk = 0;

                            var spark = new SparkMD5.ArrayBuffer();
                            var fileReader = new FileReader();

                            fileReader.onload = function (e) {
                                spark.append(e.target.result);
                                currentChunk++;

                                if (currentChunk < chunks) {
                                    loadNext();
                                }
                                else {
                                    var md5 = spark.end();
                                    if (md5 === wpvivid_file_md5)
                                    {
                                        var a = document.getElementById('wpvivid_a_link');
                                        var url = fileEntry.toURL();
                                        a.download = file_name;
                                        a.href = url;
                                        a.click();

                                        wpvivid_downloading = false;
                                        wpvivid_get_next_download();
                                    }
                                    else
                                    {
                                        console.log(md5+':'+wpvivid_file_md5);
                                        console.log('MD5 not match.');
                                    }
                                }
                            };

                            fileReader.onerror = function () {
                                console.warn('oops, something went wrong.');
                            };

                            function loadNext() {
                                var start = currentChunk * chunkSize,
                                    end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;

                                fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
                            }

                            loadNext();
                        });
                    });
                }
            }

            function errorHandler(e)
            {
                var msg = '';

                switch (e.code) {
                    case FileReader.QUOTA_EXCEEDED_ERR:
                        msg = 'QUOTA_EXCEEDED_ERR';
                        break;
                    case FileReader.NOT_FOUND_ERR:
                        msg = 'NOT_FOUND_ERR';
                        break;
                    case FileReader.SECURITY_ERR:
                        msg = 'SECURITY_ERR';
                        break;
                    case FileReader.INVALID_MODIFICATION_ERR:
                        msg = 'INVALID_MODIFICATION_ERR';
                        break;
                    case FileReader.INVALID_STATE_ERR:
                        msg = 'INVALID_STATE_ERR';
                        break;
                    default:
                        msg = 'Unknown Error';
                        break;
                }
                console.log('Error: ' + msg+' Code '+e.code);
            }

            jQuery('#wpvivid_local_export_site_backup_list').on("click", '.wpvivid-export-site-download-all', function()
            {
                if(wpvivid_dl_method==0)
                {
                    alert("We have detected that your browser does not support bulk downloading of files, please download the backup files one by one.");
                    return;
                }
                var backup_id = jQuery('#wpvivid_local_export_site_backup_list').find('div').attr('backup-id');
                var ajax_data = {
                    'action': 'wpvivid_get_need_download_files',
                    'backup_id': backup_id
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            var num = 0;
                            jQuery.each(jsonarray.files, function(index, value){
                                wpvivid_download_list[num] = new Array('file_name', 'file_size', 'file_md5');
                                wpvivid_download_list[num]['file_name'] = value.file_name;
                                wpvivid_download_list[num]['file_size'] = value.file_size;
                                wpvivid_download_list[num]['file_md5']  = value.file_md5;
                                console.log(value.file_md5);
                                num++;
                            });
                            jQuery('.wpvivid-export-site-download-all').css({'pointer-events': 'none', 'opacity': '0.4'});

                            jQuery('.wpvivid-export-site-download-all').closest('div').after('<div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_download_all_progress">' +
                                '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-download-processed-percent-progress" style="width: 0%">0%</span></span></p>' +
                                '<p>' +
                                '<span class="wpvivid-span-download-file-name">Preparing...</span>' +
                                '</p>' +
                                '</div>');

                            wpvivid_get_next_download();
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('get need download files', textStatus, errorThrown);
                    alert(error_message);
                });
            });
        </script>
        <?php
    }

    public function add_progress($type)
    {
        $progress_id = 'wpvivid_local_export_site_progress';
        if($type === 'local')
        {
            //wpvivid_postbox_backup_percent
            $progress_id = 'wpvivid_local_export_site_progress';
        }
        else if($type === 'remote')
        {
            $progress_id = 'wpvivid_remote_export_site_progress';
        }
        else if($type === 'migration')
        {
            $progress_id = 'wpvivid_migration_export_site_progress';
        }
        ?>
        <div class="wpvivid-one-coloum wpvivid-export-site-progress" id="<?php esc_attr_e($progress_id); ?>" style="display: none; padding: 0 0 1em 0;">
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                <p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress">5% completed</span></span></p>
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
            /*jQuery('#wpvivid_postbox_backup_percent').on("click", "input", function() {
                if(jQuery(this).attr('id') === 'wpvivid_backup_cancel_btn')
                {
                    wpvivid_cancel_backup();
                }
            });*/
            jQuery('.wpvivid-backup-cancel-btn').on('click', function(){
                wpvivid_cancel_backup();
            });

            jQuery('.wpvivid-export-site-progress').on("click", "input", function()
            {
                if(jQuery(this).attr('id') === 'wpvivid_backup_cancel_btn')
                {
                    wpvivid_cancel_backup();
                }
            });

            jQuery('#wpvivid_backup_cancel_btn').on('click', function(){
                wpvivid_cancel_backup();
            });

            function wpvivid_cancel_backup() {
                var ajax_data= {
                    'action': 'wpvivid_backup_cancel_ex'
                };
                jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function(data){
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.no_response)
                        {
                            var ret = confirm(jsonarray.msg);
                            if(ret === true)
                            {
                                wpvivid_termination_backup_task(jsonarray.task_id);
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_current_doing').html(jsonarray.msg);
                        }
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

            function wpvivid_termination_backup_task(task_id)
            {
                var ajax_data= {
                    'action': 'wpvivid_shutdown_backup',
                    'task_id': task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('terminationing the backup', textStatus, errorThrown);
                    wpvivid_add_notice('Backup', 'Error', error_message);
                });
            }
        </script>
        <?php
    }

    public function output_export_to_localhost()
    {
        $this->add_progress('local');
        ?>
        <div id="wpvivid_local_export_site_error_notice"></div>

        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom: 10px;">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span>Export the site to zip file(s). You can download them to your computer.</span>
        </div>

        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_local_export_site_success_notice" style="background:#eaf1fe;border-radius:8px;margin-bottom:1em;display: none;">
            <span style="cursor:pointer;"><span class="dashicons dashicons-dismiss wpvivid-dashicons-grey wpvivid-hide-local-export-site-notice"></span></span>
            <h1 style="text-align:center; color:#8300e9; display: block;">Congratulations, exporting site succeeded</h1>
            <div style="border-bottom:1px solid #cccccc;">
                <p style="text-align:center;">
                    <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                    <span><strong>You can download all parts of the backup now, or download them later on <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&localhost_backuplist'; ?>">Backup Manager Page</a></strong></span>
                </p>
            </div>

            <div id="wpvivid_local_export_site_backup_list">

            </div>
        </div>

        <div class="wpvivid-one-coloum wpvivid-workflow">
            <div>
                <div style="padding:1em 1em 1em 1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                    <div>
                        <fieldset>
                            <?php
                            $html = '';
                            echo apply_filters('wpvivid_export_site_content_addon', $html, 'local_export_site');
                            ?>
                        </fieldset>
                    </div>
                    <?php
                    if(is_multisite())
                    {
                    ?>
                    <div id="wpvivid_custom_local_export_site_mu_single_site_list" style="display: none;">
                        <p>Choose the childsite you want to backup</p>
                        <p>
                            <span style="padding-right:0.2em;">
                                <input type="search" style="margin-bottom: 4px; width:300px;" class="wpvivid-mu-single-site-search-input" placeholder="Enter title, url or description" name="s" value="">
                            </span>
                            <span><input type="submit" class="button wpvivid-mu-single-search-submit" value="Search"></span>
                        </p>
                        <div class="wpvivid_mu_single_site_list">
                            <?php
                            $type = 'local_export_site';
                            do_action('wpvivid_select_mu_single_site', 'wpvivid_custom_local_export_site_mu_single_site_list', $type);
                            ?>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                </div>

                <div id="wpvivid_custom_local_export_site" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                    <?php
                    $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_local_export_site','export_site','1','0');
                    //$custom_backup_manager->output_custom_backup_table();
                    $custom_backup_manager->output_custom_backup_db_table();
                    $custom_backup_manager->output_custom_backup_file_table();
                    ?>
                    </div>
                </div>

                <!--Advanced Option (Exclude)-->
                <div id="wpvivid_custom_local_export_advanced_option">
                    <?php
                    $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_custom_local_export_advanced_option');
                    $custom_backup_manager->output_advanced_option_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <?php
                        $general_setting=WPvivid_Setting::get_setting(true, "");
                        if(!isset($general_setting['options']['wpvivid_common_setting']['backup_prefix'])){
                            $home_url_prefix=get_home_url();
                            $parse = parse_url($home_url_prefix);
                            $path = '';
                            if(isset($parse['path'])) {
                                $parse['path'] = str_replace('/', '_', $parse['path']);
                                $parse['path'] = str_replace('.', '_', $parse['path']);
                                $path = $parse['path'];
                            }
                            $parse['host'] = str_replace('/', '_', $parse['host']);
                            $prefix = $parse['host'].$path;
                        }
                        else{
                            $prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                        }
                        ?>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="local_export_site" name="backup_prefix" id="wpvivid_set_local_export_site_prefix" value="<?php esc_attr_e($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php esc_attr_e($prefix); ?>">
                    </p>
                </div>

                <div style="margin-bottom:-1em;border-top:1px solid #f1f1f1;padding-top:1em;">
                    <input type="submit" class="button-primary" id="wpvivid_local_export_site" value="Export Now" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" >
                </div>
                <div class="wpvivid-element-space-bottom" style="text-align: left; display: none;">
                    <label class="wpvivid-checkbox">
                        <span>Marking this backup can only be deleted manually</span>
                        <input type="checkbox" option="local_export_site" name="lock">
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                </div>
                <div style="clear:both;"></div>
                <a id="wpvivid_a_link" style="display: none;"></a>
            </div>
        </div>

        <script>
            jQuery('.wpvivid-hide-local-export-site-notice').click(function()
            {
                jQuery('#wpvivid_local_export_site_success_notice').hide();
            });

            jQuery('input:radio[option=local_export_site][name=local_export_site]').click(function()
            {
                if(this.value === 'custom'){
                    jQuery('#wpvivid_custom_local_export_site').show();
                    jQuery('#wpvivid_custom_local_export_site_mu_single_site').hide();
                    jQuery('#wpvivid_custom_local_export_site_mu_single_site_list').hide();
                    jQuery( document ).trigger( 'wpvivid_refresh_manual_backup_tables', 'manual_backup' );
                }
                else if(this.value === 'mu'){
                    jQuery('#wpvivid_custom_local_export_site').hide();
                    jQuery('#wpvivid_custom_local_export_site_mu_single_site').show();
                    jQuery('#wpvivid_custom_local_export_site_mu_single_site_list').show();
                }
                else{
                    jQuery('#wpvivid_custom_local_export_site').hide();
                    jQuery('#wpvivid_custom_local_export_site_mu_single_site').hide();
                    jQuery('#wpvivid_custom_local_export_site_mu_single_site_list').hide();
                }
            });

            jQuery('#wpvivid_set_local_export_site_prefix').on("keyup", function()
            {
                var manual_prefix = jQuery('#wpvivid_set_local_export_site_prefix').val();
                if(manual_prefix !== ''){
                    var reg = RegExp(/wpvivid/, 'i');
                    if (manual_prefix.match(reg)) {
                        jQuery('#wpvivid_set_local_export_site_prefix').val('');
                        alert('You can not use word \'wpvivid\' to comment the backup.');
                    }
                }
            });

            function wpvivid_check_backup_option_avail(type)
            {
                if(type === 'local_export_site'){
                    var parent_id = 'wpvivid_custom_local_export_site';
                }
                else if(type === 'remote_export_site')
                {
                    var parent_id = 'wpvivid_custom_remote_export_site';
                }
                else if(type === 'migration_export_site')
                {
                    var parent_id = 'wpvivid_custom_migration_export_site';
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

            function wpvivid_create_custom_setting_ex(custom_type)
            {
                if(custom_type === 'local_export_site')
                {
                    var parent_id = 'wpvivid_custom_local_export_site';
                }
                else if(custom_type === 'remote_export_site')
                {
                    var parent_id = 'wpvivid_custom_remote_export_site';
                }
                else if(custom_type === 'migration_export_site')
                {
                    var parent_id = 'wpvivid_custom_migration_export_site';
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

            function wpvivid_get_mu_site_setting(parent_id)
            {
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

            function wpvivid_control_backup_lock()
            {
                jQuery('#wpvivid_local_export_site').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_remote_export_site').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_migration_export_site').css({'pointer-events': 'none', 'opacity': '0.4'});
            }

            function wpvivid_control_backup_unlock()
            {
                jQuery('#wpvivid_local_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('#wpvivid_remote_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('#wpvivid_migration_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
            }

            function wpvivid_delete_backup_task(task_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_delete_task',
                    'task_id': task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data){}, function(XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            function wpvivid_delete_ready_task(error, error_notice_id)
            {
                var ajax_data={
                    'action': 'wpvivid_delete_ready_task'
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#'+error_notice_id).show();
                            jQuery('#'+error_notice_id).html('<div class="notice notice-error inline"><p>' + error + '</p></div>');
                            wpvivid_control_backup_unlock();
                            jQuery('#wpvivid_local_export_site_progress').hide();
                            jQuery('#wpvivid_remote_export_site_progress').hide();
                            jQuery('#wpvivid_migration_export_site_progress').hide();
                        }
                    }
                    catch(err){
                        jQuery('#'+error_notice_id).show();
                        jQuery('#'+error_notice_id).html('<div class="notice notice-error inline"><p>' + err + '</p></div>');
                        wpvivid_control_backup_unlock();
                        jQuery('#wpvivid_local_export_site_progress').hide();
                        jQuery('#wpvivid_remote_export_site_progress').hide();
                        jQuery('#wpvivid_migration_export_site_progress').hide();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        wpvivid_delete_ready_task(error, error_notice_id);
                    }, 3000);
                });
            }

            function wpvivid_backup_now(task_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_new_backup_now',
                    'task_id': task_id
                };
                task_retry_times = 0;
                m_need_update_addon=true;
                wpvivid_post_request_addon(ajax_data, function(data){
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            jQuery('#wpvivid_local_export_site').on('click', function()
            {
                jQuery('#wpvivid_local_export_site_success_notice').hide();
                jQuery('#wpvivid_local_export_site_error_notice').hide();

                var backup_data = wpvivid_ajax_data_transfer('local_export_site');
                backup_data = JSON.parse(backup_data);
                backup_data['backup_files'] = backup_data['local_export_site'];
                backup_data['backup_to'] = 'local';
                var exclude_dirs = wpvivid_get_exclude_json('wpvivid_custom_local_export_advanced_option');
                var custom_option = {
                    'exclude_files': exclude_dirs
                };
                jQuery.extend(backup_data, custom_option);

                var exclude_file_type = wpvivid_get_exclude_file_type('wpvivid_custom_local_export_advanced_option');
                var exclude_file_type_option = {
                    'exclude_file_type': exclude_file_type
                };
                jQuery.extend(backup_data, exclude_file_type_option);
                backup_data = JSON.stringify(backup_data);
                var action = 'wpvivid_prepare_new_backup';
                jQuery('input:radio[option=local_export_site]').each(function ()
                {
                    if(jQuery(this).prop('checked')){
                        var key = jQuery(this).prop('name');
                        var value = jQuery(this).prop('value');
                        if(value === 'custom'){
                            backup_data = JSON.parse(backup_data);
                            var custom_dirs = wpvivid_get_custom_setting_json_ex('wpvivid_custom_local_export_site');
                            var custom_option = {
                                'custom_dirs': custom_dirs
                            };
                            jQuery.extend(backup_data, custom_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                        else if(value === 'mu'){
                            backup_data = JSON.parse(backup_data);
                            var perent_id = 'wpvivid_custom_local_export_site_mu_single_site_list';
                            var mu_setting = wpvivid_get_mu_site_setting_ex(perent_id);
                            var custom_option = {
                                'mu_setting': mu_setting
                            };
                            jQuery.extend(backup_data, custom_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                    }
                });
                var ajax_data = {
                    'action': action,
                    'backup': backup_data,
                    'export': 'local_export_site'
                };
                wpvivid_control_backup_lock();
                jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_local_export_site_progress').show();
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
                            wpvivid_delete_ready_task(jsonarray.error, 'wpvivid_local_export_site_error_notice');
                        }
                        else if (jsonarray.result === 'success') {
                            wpvivid_set_backup_history(backup_data);
                            wpvivid_backup_now(jsonarray.task_id);
                        }
                    }
                    catch (err) {
                        wpvivid_delete_ready_task(err, 'wpvivid_local_export_site_error_notice');
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    wpvivid_prepare_backup = false;
                    //var error_message = wpvivid_output_ajaxerror('preparing the backup', textStatus, errorThrown);
                    var error_message='Calculating the size of files, folder and database timed out. If you continue to receive this error, please go to the plugin settings, uncheck \'Calculate the size of files, folder and database before backing up\', save changes, then try again.';
                    wpvivid_delete_ready_task(error_message, 'wpvivid_local_export_site_error_notice');
                });
            });
        </script>
        <?php
    }

    public function output_export_to_remote()
    {
        $remoteslist=WPvivid_Setting::get_all_remote_options();
        $has_remote = false;
        foreach ($remoteslist as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            else{
                $has_remote = true;
            }
        }

        $select_remote_id=get_option('wpvivid_select_list_remote_id', '');
        $path = '';
        if($select_remote_id==''){
            $first_remote_path = 'Common';
            foreach ($remoteslist as $key=>$value)
            {
                if($key === 'remote_selected')
                {
                    continue;
                }
                if(isset($value['custom_path']))
                {
                    if(isset($value['root_path'])){
                        $path = $value['path'].$value['root_path'].$value['custom_path'];
                    }
                    else{
                        $path = $value['path'].'wpvividbackuppro/'.$value['custom_path'];
                    }
                }
                else
                {
                    $path = $value['path'];
                }
                if($first_remote_path === 'Common'){
                    $first_remote_path = $path;
                }
            }
            $path = $first_remote_path;
        }
        else{
            if (isset($remoteslist[$select_remote_id]))
            {
                if(isset($remoteslist[$select_remote_id]['custom_path']))
                {
                    if(isset($remoteslist[$select_remote_id]['root_path'])){
                        $path = $remoteslist[$select_remote_id]['path'].$remoteslist[$select_remote_id]['root_path']. $remoteslist[$select_remote_id]['custom_path'];
                    }
                    else{
                        $path = $remoteslist[$select_remote_id]['path'].'wpvividbackuppro/'. $remoteslist[$select_remote_id]['custom_path'];
                    }
                }
                else
                {
                    $path = $remoteslist[$select_remote_id]['path'];
                }
            }
            else {
                $path='Common';
            }
        }
        $remote_storage_option = '';
        foreach ($remoteslist as $key=>$value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            $value['type']=apply_filters('wpvivid_storage_provider_tran', $value['type']);
            $remote_storage_option.='<option value="'.$key.'">'.$value['type'].' -> '.$value['name'].'</option>';
        }

        if($has_remote)
        {
            $this->add_progress('remote');
            ?>
            <div id="wpvivid_remote_export_site_error_notice"></div>

            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_remote_export_site_success_notice" style="background:#eaf1fe;border-radius:8px; margin-bottom:1em; display: none;">
                <h1 style="text-align:center; color:#8300e9; display: block;">Congratulations, exporting site succeeded</h1>

                <div style="">
                    <p style="text-align:center;">
                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                        <span><strong>The backup has been sent to your remote storage, you can import it on another Wordpress site to complete migration, <a href="https://docs.wpvivid.com/wpvivid-backup-pro-migrate-wordpress-site-via-remote-storage.html" target='_blank'>learn more...</a></strong></span></p>
                    <p style="text-align:center;"><span><strong>You can download the backup on <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&remote_backuplist'; ?>">Backup Manager Page</a> anytime.</strong></span></p>
                </div>

                <div>
                    <p style="text-align:center;"><span class="wpvivid-blue wpvivid-hide-remote-export-site-notice" style="padding: 0.5em 2em;border-radius:8px; cursor:pointer;">I got it</span>
                    </p>
                </div>
            </div>

            <div class="wpvivid-one-coloum wpvivid-workflow">
                <div>
                    <p>
                        <span class="dashicons dashicons-admin-site-alt wpvivid-dashicons-blue" style="margin-top:0.2em;"></span>
                        <span>The backup will be sent to </span>
                        <span>
                            <select id="wpvivid_select_export_remote_storage">
                                <?php _e($remote_storage_option); ?>
                            </select>
                        </span>
                    </p>
                </div>

                <div style="padding:1em 1em 1em 1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                    <div>
                        <fieldset>
                            <?php
                            $html = '';
                            echo apply_filters('wpvivid_export_site_content_addon', $html, 'remote_export_site');
                            ?>
                        </fieldset>
                    </div>
                    <?php
                    if(is_multisite())
                    {
                        ?>
                        <div id="wpvivid_custom_remote_export_site_mu_single_site_list" style="display: none;">
                            <p>Choose the childsite you want to backup</p>
                            <p>
                            <span style="padding-right:0.2em;">
                                <input type="search" style="margin-bottom: 4px; width:300px;" class="wpvivid-mu-single-site-search-input" placeholder="Enter title, url or description" name="s" value="">
                            </span>
                                <span><input type="submit" class="button wpvivid-mu-single-search-submit" value="Search"></span>
                            </p>
                            <div class="wpvivid_mu_single_site_list">
                                <?php
                                $type = 'remote_export_site';
                                do_action('wpvivid_select_mu_single_site', 'wpvivid_custom_remote_export_site_mu_single_site_list', $type);
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div id="wpvivid_custom_remote_export_site" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                    <?php
                    $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_remote_export_site','export_site','0','0');
                    //$custom_backup_manager->output_custom_backup_table();
                    $custom_backup_manager->output_custom_backup_db_table();
                    $custom_backup_manager->output_custom_backup_file_table();
                    ?>
                    </div>
                </div>

                <!--Advanced Option (Exclude)-->
                <div id="wpvivid_custom_remote_export_advanced_option">
                    <?php
                    $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_custom_remote_export_advanced_option');
                    $custom_backup_manager->output_advanced_option_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <?php
                        $general_setting=WPvivid_Setting::get_setting(true, "");
                        if(!isset($general_setting['options']['wpvivid_common_setting']['backup_prefix'])){
                            $home_url_prefix=get_home_url();
                            $parse = parse_url($home_url_prefix);
                            $path = '';
                            if(isset($parse['path'])) {
                                $parse['path'] = str_replace('/', '_', $parse['path']);
                                $parse['path'] = str_replace('.', '_', $parse['path']);
                                $path = $parse['path'];
                            }
                            $parse['host'] = str_replace('/', '_', $parse['host']);
                            $prefix = $parse['host'].$path;
                        }
                        else{
                            $prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                        }
                        ?>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="remote_export_site" name="backup_prefix" id="wpvivid_set_remote_export_site_prefix" value="<?php esc_attr_e($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php esc_attr_e($prefix); ?>">
                    </p>
                </div>

                <div style="margin-bottom:-1em;border-top:1px solid #f1f1f1;padding-top:1em;">
                    <input type="submit" class="button-primary" id="wpvivid_remote_export_site" value="Export Now" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" >
                </div>
                <div class="wpvivid-element-space-bottom" style="text-align: left; display: none;">
                    <label class="wpvivid-checkbox">
                        <span>Marking this backup can only be deleted manually</span>
                        <input type="checkbox" option="remote_export_site" name="lock">
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                </div>
                <div style="clear:both;"></div>
            </div>
            <script>
                jQuery('.wpvivid-hide-remote-export-site-notice').click(function()
                {
                    jQuery('#wpvivid_remote_export_site_success_notice').hide();
                });

                jQuery('input:radio[option=remote_export_site][name=remote_export_site]').click(function()
                {
                    if(this.value === 'custom'){
                        jQuery('#wpvivid_custom_remote_export_site').show();
                        jQuery('#wpvivid_custom_remote_export_site_mu_single_site').hide();
                        jQuery('#wpvivid_custom_remote_export_site_mu_single_site_list').hide();
                        jQuery( document ).trigger( 'wpvivid_refresh_manual_backup_tables', 'manual_backup' );
                    }
                    else if(this.value === 'mu'){
                        jQuery('#wpvivid_custom_remote_export_site').hide();
                        jQuery('#wpvivid_custom_remote_export_site_mu_single_site').show();
                        jQuery('#wpvivid_custom_remote_export_site_mu_single_site_list').show();
                    }
                    else{
                        jQuery('#wpvivid_custom_remote_export_site').hide();
                        jQuery('#wpvivid_custom_remote_export_site_mu_single_site').hide();
                        jQuery('#wpvivid_custom_remote_export_site_mu_single_site_list').hide();
                    }
                });

                jQuery('#wpvivid_set_remote_export_site_prefix').on("keyup", function()
                {
                    var manual_prefix = jQuery('#wpvivid_set_remote_export_site_prefix').val();
                    if(manual_prefix !== ''){
                        var reg = RegExp(/wpvivid/, 'i');
                        if (manual_prefix.match(reg)) {
                            jQuery('#wpvivid_set_remote_export_site_prefix').val('');
                            alert('You can not use word \'wpvivid\' to comment the backup.');
                        }
                    }
                });

                jQuery('#wpvivid_remote_export_site').on('click', function()
                {
                    jQuery('#wpvivid_remote_export_site_success_notice').hide();
                    jQuery('#wpvivid_remote_export_site_error_notice').hide();

                    var backup_data = wpvivid_ajax_data_transfer('remote_export_site');
                    backup_data = JSON.parse(backup_data);
                    var remote_id_select = jQuery('#wpvivid_select_export_remote_storage').val();
                    backup_data['backup_files'] = backup_data['remote_export_site'];
                    backup_data['backup_to'] = 'remote';
                    backup_data['remote_id_select'] = remote_id_select;
                    var exclude_dirs = wpvivid_get_exclude_json('wpvivid_custom_remote_export_advanced_option');
                    var custom_option = {
                        'exclude_files': exclude_dirs
                    };
                    jQuery.extend(backup_data, custom_option);

                    var exclude_file_type = wpvivid_get_exclude_file_type('wpvivid_custom_remote_export_advanced_option');
                    var exclude_file_type_option = {
                        'exclude_file_type': exclude_file_type
                    };
                    jQuery.extend(backup_data, exclude_file_type_option);
                    backup_data = JSON.stringify(backup_data);
                    var action = 'wpvivid_prepare_new_backup';
                    jQuery('input:radio[option=remote_export_site]').each(function ()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            var key = jQuery(this).prop('name');
                            var value = jQuery(this).prop('value');
                            if(value === 'custom')
                            {
                                backup_data = JSON.parse(backup_data);
                                var custom_dirs = wpvivid_get_custom_setting_json_ex('wpvivid_custom_remote_export_site');
                                var custom_option = {
                                    'custom_dirs': custom_dirs
                                };
                                jQuery.extend(backup_data, custom_option);
                                backup_data = JSON.stringify(backup_data);
                            }
                            else if(value === 'mu')
                            {
                                backup_data = JSON.parse(backup_data);
                                var perent_id = 'wpvivid_custom_remote_export_site_mu_single_site_list';
                                var mu_setting = wpvivid_get_mu_site_setting_ex(perent_id);
                                var custom_option = {
                                    'mu_setting': mu_setting
                                };
                                jQuery.extend(backup_data, custom_option);
                                backup_data = JSON.stringify(backup_data);
                            }
                        }
                    });
                    var ajax_data = {
                        'action': action,
                        'backup': backup_data,
                        'type':'Migrate',
                        'export': 'remote_export_site'
                    };
                    wpvivid_control_backup_lock();
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_remote_export_site_progress').show();
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
                                wpvivid_delete_ready_task(jsonarray.error, 'wpvivid_remote_export_site_error_notice');
                            }
                            else if (jsonarray.result === 'success') {
                                wpvivid_set_backup_history(backup_data);
                                wpvivid_backup_now(jsonarray.task_id);
                            }
                        }
                        catch (err) {
                            wpvivid_delete_ready_task(err, 'wpvivid_remote_export_site_error_notice');
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        wpvivid_prepare_backup = false;
                        //var error_message = wpvivid_output_ajaxerror('preparing the backup', textStatus, errorThrown);
                        var error_message='Calculating the size of files, folder and database timed out. If you continue to receive this error, please go to the plugin settings, uncheck \'Calculate the size of files, folder and database before backing up\', save changes, then try again.';
                        wpvivid_delete_ready_task(error_message, 'wpvivid_remote_export_site_error_notice');
                    });
                });
            </script>
            <?php
        }
        else
        {
            ?>
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="background:#eaf1fe;border-radius:8px;">
                <p>There is no available remote storage added, please set an available account on <a href="<?php echo 'admin.php?page='.strtolower(sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')));?>" style="cursor: pointer;">cloud storage</a> page</p>
            </div>
            <?php
        }
    }

    public function output_export_to_migration()
    {
        ?>
        <div id="wpvivid_migration_export_site_transfer_key">
            <?php
            $html = '';
            echo apply_filters('wpvivid_export_site_migration_addon', $html);
            ?>
        </div>
        <script>
            jQuery('.wpvivid-hide-migration-export-site-notice').click(function()
            {
                jQuery('#wpvivid_migration_export_site_success_notice').hide();
            });

            var source_site ='<?php echo site_url(); ?>';
            function wpvivid_check_key(value)
            {
                var pos = value.indexOf('?');
                var site_url = value.substring(0, pos);
                if(site_url === source_site)
                {
                    alert('The key generated by this site cannot be added into this site.');
                    jQuery('#wpvivid_save_url_button').prop('disabled', true);
                }
                else{
                    jQuery("#wpvivid_save_url_button").prop('disabled', false);
                }
            }

            function wpvivid_click_save_site_url()
            {
                var url= jQuery('#wpvivid_transfer_key_text').val();
                var ajax_data =
                    {
                        'action': 'wpvivid_test_connect_export_site',
                        'url':url
                    };

                jQuery("#wpvivid_save_url_button").prop('disabled', true);
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery("#wpvivid_save_url_button").prop('disabled', false);
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result==='success')
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-export-site&tab=migration', 'wpvivid-export-site&tab=migration'); ?>';
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery("#wpvivid_save_url_button").prop('disabled', false);
                    var error_message = wpvivid_output_ajaxerror('saving key', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_click_delete_transfer_key()
            {
                var ajax_data = {
                    'action': 'wpvivid_delete_export_site_transfer_key'
                };

                jQuery("#wpvivid_delete_key_button").css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery("#wpvivid_delete_key_button").css({'pointer-events': 'none', 'opacity': '0.4'});
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result==='success')
                        {
                            jQuery('#wpvivid_migration_export_site_transfer_key').html(jsonarray.html);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery("#wpvivid_delete_key_button").css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('deleting key', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('input:radio[option=migration_export_site][name=migration_export_site]').click(function()
            {
                if(this.value === 'custom'){
                    jQuery('#wpvivid_custom_migration_export_site').show();
                    jQuery('#wpvivid_custom_migration_export_site_mu_single_site').hide();
                    jQuery('#wpvivid_custom_migration_export_site_mu_single_site_list').hide();
                    jQuery( document ).trigger( 'wpvivid_refresh_manual_backup_tables', 'manual_backup' );
                }
                else if(this.value === 'mu'){
                    jQuery('#wpvivid_custom_migration_export_site').hide();
                    jQuery('#wpvivid_custom_migration_export_site_mu_single_site').show();
                    jQuery('#wpvivid_custom_migration_export_site_mu_single_site_list').show();
                }
                else{
                    jQuery('#wpvivid_custom_migration_export_site').hide();
                    jQuery('#wpvivid_custom_migration_export_site_mu_single_site').hide();
                    jQuery('#wpvivid_custom_migration_export_site_mu_single_site_list').hide();
                }
            });

            jQuery('#wpvivid_set_migration_export_site_prefix').on("keyup", function()
            {
                var manual_prefix = jQuery('#wpvivid_set_migration_export_site_prefix').val();
                if(manual_prefix !== ''){
                    var reg = RegExp(/wpvivid/, 'i');
                    if (manual_prefix.match(reg)) {
                        jQuery('#wpvivid_set_migration_export_site_prefix').val('');
                        alert('You can not use word \'wpvivid\' to comment the backup.');
                    }
                }
            });

            jQuery('#wpvivid_migration_export_site').on('click', function()
            {
                jQuery('#wpvivid_migration_export_site_success_notice').hide();
                jQuery('#wpvivid_migration_export_site_error_notice').hide();

                var backup_data = wpvivid_ajax_data_transfer('migration_export_site');
                backup_data = JSON.parse(backup_data);
                backup_data['backup_files'] = backup_data['migration_export_site'];
                backup_data['backup_to'] = 'auto_migrate';
                var exclude_dirs = wpvivid_get_exclude_json('wpvivid_custom_migration_export_advanced_option');
                var custom_option = {
                    'exclude_files': exclude_dirs
                };
                jQuery.extend(backup_data, custom_option);

                var exclude_file_type = wpvivid_get_exclude_file_type('wpvivid_custom_migration_export_advanced_option');
                var exclude_file_type_option = {
                    'exclude_file_type': exclude_file_type
                };
                jQuery.extend(backup_data, exclude_file_type_option);
                backup_data = JSON.stringify(backup_data);
                var action = 'wpvivid_export_backup_to_site';
                jQuery('input:radio[option=migration_export_site]').each(function ()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var key = jQuery(this).prop('name');
                        var value = jQuery(this).prop('value');
                        if(value === 'custom')
                        {
                            backup_data = JSON.parse(backup_data);
                            var custom_dirs = wpvivid_get_custom_setting_json_ex('wpvivid_custom_migration_export_site');
                            var custom_option = {
                                'custom_dirs': custom_dirs
                            };
                            jQuery.extend(backup_data, custom_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                        else if(value === 'mu')
                        {
                            backup_data = JSON.parse(backup_data);
                            var perent_id = 'wpvivid_custom_migration_export_site_mu_single_site_list';
                            var mu_setting = wpvivid_get_mu_site_setting_ex(perent_id);
                            var custom_option = {
                                'mu_setting': mu_setting
                            };
                            jQuery.extend(backup_data, custom_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                    }
                });
                var ajax_data = {
                    'action': action,
                    'backup': backup_data,
                    'export': 'auto_migrate'
                };
                wpvivid_control_backup_lock();
                jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_migration_export_site_progress').show();
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
                            wpvivid_delete_ready_task(jsonarray.error, 'wpvivid_migration_export_site_error_notice');
                        }
                        else if (jsonarray.result === 'success') {
                            wpvivid_set_backup_history(backup_data);
                            wpvivid_backup_now(jsonarray.task_id);
                        }
                    }
                    catch (err) {
                        wpvivid_delete_ready_task(err, 'wpvivid_migration_export_site_error_notice');
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    wpvivid_prepare_backup = false;
                    //var error_message = wpvivid_output_ajaxerror('preparing the backup', textStatus, errorThrown);
                    var error_message='Calculating the size of files, folder and database timed out. If you continue to receive this error, please go to the plugin settings, uncheck \'Calculate the size of files, folder and database before backing up\', save changes, then try again.';
                    wpvivid_delete_ready_task(error_message, 'wpvivid_migration_export_site_error_notice');
                });
            });
        </script>
        <?php
    }
}