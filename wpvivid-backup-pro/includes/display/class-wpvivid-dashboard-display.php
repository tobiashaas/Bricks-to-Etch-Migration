<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Dashboard
{
    public $installation;
    public $license;
    public function __construct()
    {
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);

        add_action('wpvivid_dashboard_menus_sidebar',array( $this,'license_sidebar'),14);
        add_action('wpvivid_dashboard_menus_sidebar',array( $this,'ticket_sidebar'),10);

        add_action('wpvivid_add_sidebar_dashboard', array($this, 'add_sidebar'));
        add_action('wpvivid_dashboard_menus_box',array($this, 'backup_pro_menu_box'),10);
        add_action('wpvivid_dashboard_addon_box', array($this, 'addon_box'), 10);

        add_filter('wpvivid_addon_page_url', array($this, 'addon_page_url'), 10, 2);
        add_filter('wpvivid_addon_page_title', array($this, 'addon_page_title'), 10, 2);

        add_action('wp_ajax_wpvivid_init_plugin_install_ex', array( $this,'init_plugin_install'));
        add_action('wp_ajax_wpvivid_activate_plugin', array( $this,'activate_plugin'));

        add_filter('wpvivid_check_install_addon', array($this, 'check_install_addon'), 10, 2);
    }

    public function check_install_addon($is_install, $check_slug)
    {
        global $wpvivid_backup_pro;

        if(is_multisite())
        {
            if(is_main_site())
            {
                $dashboard_info=get_option('wpvivid_dashboard_info',array());
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $dashboard_info=get_option('wpvivid_dashboard_info',array());
                restore_current_blog();
            }
        }
        else
        {
            $dashboard_info=get_option('wpvivid_dashboard_info',array());
        }

        if(isset($dashboard_info['plugins']) && !empty($dashboard_info['plugins']))
        {
            foreach ($dashboard_info['plugins'] as $slug=>$info)
            {
                if($slug === $check_slug)
                {
                    $status=$wpvivid_backup_pro->addons_loader->get_plugin_status($info);
                    if($status['status']!=='Un-installed')
                    {
                        $is_install=true;
                    }
                    else
                    {
                        $is_install=false;
                    }
                }
            }
        }

        return $is_install;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'dashboard');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Dashboard');
            $submenu['menu_title'] = 'Dashboard';

            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-dashboard");
            $submenu['menu_slug'] = strtolower(sprintf('%s-dashboard', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 1;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function init_plugin_install()
    {
        if(!isset($_POST['plugins']))
        {
            die();
        }
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-install-plugins');

        if(is_multisite())
        {
            if(is_main_site())
            {
                $info=get_option('wpvivid_dashboard_info',array());
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $info=get_option('wpvivid_dashboard_info',array());
                restore_current_blog();
            }
        }
        else
        {
            $info=get_option('wpvivid_dashboard_info',array());
        }

        if(empty($info))
        {
            $ret['result']='failed';
            $ret['error']='not found dashboard info';
            echo json_encode($ret);

            die();
        }

        $plugin_install_cache['plugins']=array();
        $plugin_install_cache['complete']=array();

        $plugins=$_POST['plugins'];

        if(empty($plugins))
        {
            $ret['result']='failed';
            $ret['error']='No selected plugin.';

            echo json_encode($ret);

            die();
        }

        foreach ($info['plugins'] as $slug=>$plugin)
        {
            if(in_array($slug,$plugins))
            {
                if($wpvivid_backup_pro->addons_loader->is_plugin_install_available($plugin))
                {
                    $plugin_install_cache['plugins']=array_merge($wpvivid_backup_pro->addons_loader->get_requires_plugins($plugin),$plugin_install_cache['plugins']);
                    $plugin_install_cache['plugins'][]=$plugin;
                }
            }
        }

        if(empty($plugin_install_cache['plugins']))
        {
            $ret['result']='success';
            $ret['href']=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard');
            $wpvivid_backup_pro->updater->update_site_transient_update_plugins();

        }
        else
        {
            update_option('wpvivid_plugin_install_cache',$plugin_install_cache,'no');
            $ret['result']='success';
            $ret['cache']=$plugin_install_cache;
            $ret['href']=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard').'&install=1';
        }

        echo json_encode($ret);

        die();
    }

    public function activate_plugin()
    {
        if(!isset($_POST['plugins']))
        {
            die();
        }
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('activate_plugin');

        if( ! function_exists('get_plugin_data') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        if(is_multisite())
        {
            if(is_main_site())
            {
                $info=get_option('wpvivid_dashboard_info',array());
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $info=get_option('wpvivid_dashboard_info',array());
                restore_current_blog();
            }
        }
        else
        {
            $info=get_option('wpvivid_dashboard_info',array());
        }

        if(empty($info))
        {
            $ret['result']='failed';
            $ret['error']='not found dashboard info';
            echo json_encode($ret);

            die();
        }

        $plugins=$_POST['plugins'];

        if(empty($plugins))
        {
            $ret['result']='failed';
            $ret['error']='No selected plugin.';

            echo json_encode($ret);

            die();
        }

        foreach ($plugins as $slug)
        {
            if(isset($info['plugins'][$slug]))
            {
                $plugin=$info['plugins'][$slug];
                if($plugin['install']['is_plugin']==true)
                {
                    activate_plugin($plugin['install']['plugin_slug']);
                }

                if(isset($plugin['requires_plugins']))
                {
                    foreach ( $plugin['requires_plugins'] as $requires_plugin)
                    {
                        activate_plugin($requires_plugin['install']['plugin_slug']);
                    }
                }
            }
        }

        $ret['result']='success';
        $ret['href']=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard').'&finish=1';
        echo json_encode($ret);

        die();
    }

    public function init_page()
    {

        $slug = apply_filters('wpvivid_access_white_label_slug', 'wpvivid_white_label');
        if(isset($_REQUEST[$slug])&&$_REQUEST[$slug]==1)
        {
            do_action('wpvivid_output_white_label_page');
            return;
        }

        $first_install=get_option('wpvivid_plugins_first_install',false);
        if($first_install===false)
        {
            if(is_multisite())
            {
                if(is_main_site())
                {
                    $user_info= get_option('wpvivid_pro_user',false);
                }
                else
                {
                    switch_to_blog(get_main_site_id());
                    $user_info= get_option('wpvivid_pro_user',false);
                    restore_current_blog();
                }
            }
            else
            {
                $user_info= get_option('wpvivid_pro_user',false);
            }
            if($user_info===false)
            {
                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-license', 'wpvivid-license');
                update_option('wpvivid_plugins_first_install','step1','no');
            }
            else
            {
                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard');
                $url.='&first=1';
                update_option('wpvivid_plugins_first_install','step2','no');
            }

            if (is_multisite())
            {
                $url=network_admin_url().$url;
            }
            else
            {
                $url=admin_url().$url;
            }

            ?>
            <script>
                location.href='<?php echo $url; ?>';
            </script>
            <?php
        }

        $this->dashboard_page();

        return ;
    }

    public function dashboard_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Dashboard', 'wpvivid' ); ?></h1>

            <?php $this->dashboard_welcome_panel(); ?>

            <div id="poststuff" style="">
                <div id="post-body" class="metabox-holder columns-2">
                    <?php $this->dashboard_menus_panel();?>
                    <?php $this->dashboard_menus_sidebar();?>
                </div>
                <br class="clear">
            </div>

        </div>
        <?php
    }

    public function dashboard_welcome_panel()
    {
        ?>
        <div class="wpvivid-welcome-panel wpvivid-clear-float">
            <div class="wpvivid-welcome-bar wpvivid-clear-float">
                <div class="wpvivid-welcome-bar-left">
                    <h2>Welcome To <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid Backup & Migration Plugin'); ?></h2>
                    <p class="about-description">Entrance for all main features</p>
                </div>
                <div class="wpvivid-welcome-bar-right">
                    <p style="text-align:right;">
                        <span>
                            <span>Local Time:</span>
                            <span>
                                <?php
                                $offset=get_option('gmt_offset');
                                echo date("l, F-d-Y H:i",time()+$offset*60*60);
                                ?>
                            </span>
                            <span class="dashicons dashicons-clock wpvivid-dashicons-blue"></span>
                        </span>
                    </p>
                    <?php
                    if(apply_filters('wpvivid_show_dashboard_addons',true))
                    {
                        ?>
                        <p style="text-align:right;">
                            <span>Version:</span><a href="https://wpvivid.com/wpvivid-backup-pro-changelog" target="_blank" title="Changelog"><span><?php _e(WPVIVID_BACKUP_PRO_VERSION); ?></span></a>
                            <span class="dashicons dashicons-welcome-learn-more wpvivid-dashicons-green"></span>
                        </p>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function dashboard_menus_panel()
    {
        ?>
        <div id="post-body-content">
            <div class="meta-box-sortables ui-sortable">
                <?php do_action('wpvivid_dashboard_menus_box');?>
                <?php
                    if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-install-plugins'))
                    {
                        if(apply_filters('wpvivid_show_dashboard_addons',true))
                        {
                            do_action('wpvivid_dashboard_addon_box');
                        }
                    }
                ?>
            </div>
        </div>

        <?php
    }

    public function dashboard_menus_sidebar()
    {
        if(apply_filters('wpvivid_show_sidebar',true))
        {
            ?>
            <div id="postbox-container-1" class="postbox-container">

                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox  wpvivid-sidebar-main">
                        <div class="inside">
                            <div>
                                <?php
                                do_action('wpvivid_dashboard_menus_sidebar');
                                ?>
                            </div>
                        </div>
                        <!-- .inside -->
                    </div>
                    <!-- .postbox -->
                </div>
                <!-- .meta-box-sortables -->
            </div>
            <?php
        }
    }

    public function license_sidebar()
    {
        if(apply_filters('wpvivid_show_dashboard_addons',true))
        {
            if(current_user_can('administrator'))
            {
                $url='admin.php?page='.strtolower(sprintf('%s-license', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
                ?>
                <div class="wpvivid-four-cols">
                    <ul>
                        <li><span class="dashicons dashicons-admin-network wpvivid-dashicons-middle wpvivid-dashicons-green"></span>
                            <a href="<?php echo $url; ?>"><b>License</b></a>
                            <?php
                            if(is_multisite())
                            {
                                if(is_main_site())
                                {
                                    $user_info= get_option('wpvivid_pro_user',false);
                                }
                                else
                                {
                                    switch_to_blog(get_main_site_id());
                                    $user_info= get_option('wpvivid_pro_user',false);
                                    restore_current_blog();
                                }
                            }
                            else
                            {
                                $user_info= get_option('wpvivid_pro_user',false);
                            }
                            if($user_info===false)
                            {
                                ?>
                                <span class="wpvivid-rectangle-small wpvivid-red">un-authorized</span>
                                <?php
                            }
                            else
                            {
                                ?>
                                <span class="wpvivid-rectangle-small wpvivid-green">Authorized</span>
                                <?php
                            }
                            ?>
                            <br>
                            Activate <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Pro license on the website, check update and enable automatic update.</li>
                    </ul>
                </div>
                <?php
            }
        }
    }

    public function ticket_sidebar()
    {
        if(apply_filters('wpvivid_show_submit_ticket',true))
        {
            ?>
            <div class="wpvivid-four-cols">
                <ul>
                    <li><span class="dashicons dashicons-admin-comments wpvivid-dashicons-middle wpvivid-dashicons-green"></span>
                        <a href="https://wpvivid.com/submit-ticket"><b>Submit a Ticket</b></a>
                        <br>
                        If you find a php error or a vulnerability in plugin, you can create ticket in hot support that we responded instantly</li>
                </ul>
            </div>
            <?php
        }
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

    public function backup_pro_menu_box()
    {
        $show=false;

        if(class_exists('WPvivid_Backup_Restore_Page_addon'))
        {
            $show=true;
            $backup=true;
        }
        else
        {
            $backup=false;
        }

        if(class_exists('WPvivid_Migration_Page_addon'))
        {
            $show=true;
            $migration=true;
        }
        else
        {
            $migration=false;
        }

        if(class_exists('WPvivid_BackupList_addon'))
        {
            $show=true;
            $backup_list=true;
        }
        else
        {
            $backup_list=false;
        }

        if(class_exists('WPvivid_Schedule_addon'))
        {
            $show=true;
            $schedule=true;
        }
        else
        {
            $schedule=false;
        }

        //WPvivid_Multi_Remote_addon WPvivid_Export_Import_addon

        if(class_exists('WPvivid_Multi_Remote_addon'))
        {
            $show=true;
            $remote=true;
        }
        else
        {
            $remote=false;
        }

        if(class_exists('WPvivid_Export_Import_addon'))
        {
            $show=true;
            $export=true;
        }
        else
        {
            $export=false;
        }

        if(class_exists('WPvivid_Uploads_Cleaner_addon'))
        {
            $show=true;
            $upload_cleaner=true;
        }
        else
        {
            $upload_cleaner=false;
        }

        if($show)
        {
            ?>
            <div class="wpvivid-dashboard" style="margin-bottom:1em;">
            <?php
            if($schedule)
            {
                $offset=get_option('gmt_offset');
                $enable_incremental_schedules=get_option('wpvivid_enable_incremental_schedules', false);
                $enable_schedules_backups=apply_filters('wpvivid_get_general_schedule_status',false);
                if($enable_schedules_backups||$enable_incremental_schedules)
                {
                    $dashicon="wpvivid-green";
                    $enable_status = 'Enabled';
                }
                else{
                    $dashicon="wpvivid-grey";
                    $enable_status = 'Disabled';
                }
                if($enable_schedules_backups)
                {
                    $type="General";
                }
                else if($enable_incremental_schedules)
                {
                    $type="Incremental";
                }
                else
                {
                    $type="";
                }
                //
                ?>
                <div class="wpvivid-one-coloum" style="border-bottom:1px solid #eee;background:#eaf1fe;">
                    <div style="padding-left:1em;">
                        <p><span class="dashicons dashicons-calendar-alt wpvivid-dashicons-green"></span>
                            <span><strong>Backup Schedule:</strong></span>
                            <span class="wpvivid-rectangle <?php echo $dashicon?>"><?php _e($enable_status)?></span>
                            <?php
                            if(!empty($type))
                            {
                                ?>
                                <span><strong>Type:</strong></span>
                                <span class="wpvivid-rectangle wpvivid-green"><?php _e($type)?></span>
                                <?php
                            }
                            ?>
                        <p>
                    </div>
                    <?php
                    if($enable_incremental_schedules)
                    {
                        $data=$this->get_incremental_schedules_data();
                        ?>
                        <div class="wpvivid-two-col">
                            <div style="padding:0 1em;">
                                <p><span class="dashicons dashicons-category wpvivid-dashicons-orange"></span>
                                    <span>Last Backup (Files): </span><span style="padding-right:0.2em"><?php echo $data['last_files_backup_time'] ?></span>
                                    <span style="padding-right:0.2em"><?php echo $data['last_files_backup_status']?></span></p>
                                <p><span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue"></span>
                                    <span>Last Backup (Database): </span><span><?php echo $data['last_db_backup_time'] ?></span></p>
                            </div>
                        </div>
                        <div class="wpvivid-two-col">
                            <div style="padding:0 1em;">
                                <p><span class="dashicons dashicons-category wpvivid-dashicons-grey"></span>
                                    <span>Next Backup (Files): </span><span><?php echo $data['next_files_backup'] ?></span></p>
                                <p><span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-grey"></span>
                                    <span>Next Backup (Database): </span><span><?php echo $data['next_db_backup'] ?></span></p>
                            </div>
                        </div>
                        <?php
                    }
                    else if($enable_schedules_backups)
                    {
                        $data=$this->get_general_schedules_data();
                        ?>
                        <div class="wpvivid-two-col">
                            <div style="padding:0 1em;">
                                <p><span class="dashicons dashicons-category wpvivid-dashicons-orange"></span>
                                    <span>Last Backup : </span><span style="padding-right:0.2em"><?php echo $data['last_backup_time']; ?></span>
                                    <span style="padding-right:0.2em"><?php echo $data['last_backup_status']; ?></span></p>
                            </div>
                        </div>
                        <div class="wpvivid-two-col">
                            <div style="padding:0 1em;">
                                <p><span class="dashicons dashicons-category wpvivid-dashicons-grey"></span>
                                    <span>Next Backup : </span><span><?php echo $data['next_backup_time']; ?></span></p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <div style="clear:both;"></div>
                <?php
            }
            ?>
                <div class="wpvivid-clear-float">
                    <div class="wpvivid-one-coloum">
                        <span>
                            <h1>
                                <span class="dashicons dashicons-list-view wpvivid-dashicons-blue" style="margin-top:0.3em;"></span>Backup & Migration
                                <span style="margin-left:2em; font-size:13px;float:right;"><span class="dashicons dashicons-update wpvivid-dashicons-green"></span><strong><a href="<?php esc_attr_e(apply_filters('wpvivid_get_admin_url', '').'plugins.php?s=wpvivid&plugin_status=all'); ?>">Update all</a></strong></span>
                            </h1>
                        </span>
                    </div>
                    <div>
                        <?php
                        if(apply_filters('wpvivid_show_dashboard_addons',true))
                        {
                            $show_learn_more_link = true;
                        }
                        else
                        {
                            $show_learn_more_link = false;
                        }
                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup'))
                        {
                            if($backup)
                            {
                                $help_url='https://docs.wpvivid.com/manual-backup-overview.html';
                                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup', 'wpvivid-backup');
                                if($show_learn_more_link)
                                {
                                    $learn_more='<span style="float: right;"><a href="'.$help_url.'">Learn more...</a></span>';
                                }
                                else
                                {
                                    $learn_more='';
                                }
                                echo '<div class="wpvivid-two-col wpvivid-dashboard-list">
                                                <span class="dashicons dashicons-backup wpvivid-dashicons-large wpvivid-dashicons-green"></span>
                                                <a href="'.$url.'"><b>Manual Backup</b></a>
                                                '.$learn_more.'
                                                <br>
                                                Create an on-demand backup of your website for restoration or migration.
                                            </div>';
                            }
                        }

                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-use-schedule'))
                        {
                            if($schedule)
                            {
                                $help_url='https://docs.wpvivid.com/wpvivid-backup-pro-schedule-overview.html';
                                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-schedule', 'wpvivid-schedule');
                                if($show_learn_more_link)
                                {
                                    $learn_more='<span style="float: right;"><a href="'.$help_url.'">Learn more...</a></span>';
                                }
                                else
                                {
                                    $learn_more='';
                                }
                                echo '<div class="wpvivid-two-col wpvivid-dashboard-list">
                                                <span class="dashicons dashicons-calendar-alt wpvivid-dashicons-large wpvivid-dashicons-green"></span>
                                                <a href="'.$url.'"><b>Schedule</b></a>
                                                '.$learn_more.'
                                                <br>
                                                Set up schedules to back up the site automatically: general or incremental backup schedules.
                                            </div>';
                            }
                        }

                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-backup'))
                        {
                            if($backup_list)
                            {
                                $help_url='https://docs.wpvivid.com/wpvivid-backup-pro-backups-restore-overview.html';
                                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore');
                                if($show_learn_more_link)
                                {
                                    $learn_more='<span style="float: right;"><a href="'.$help_url.'">Learn more...</a></span>';
                                }
                                else
                                {
                                    $learn_more='';
                                }
                                echo '<div class="wpvivid-two-col wpvivid-dashboard-list">
                                                <span class="dashicons dashicons dashicons-database wpvivid-dashicons-large wpvivid-dashicons-green"></span>
                                                <a href="'.$url.'"><b>Backup Manager</b></a>
                                                '.$learn_more.'
                                                <br>
                                                A centralized place for managing all your backups, uploading backups and restoring the backups.
                                            </div>';
                            }
                        }

                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-remote'))
                        {
                            if($remote)
                            {
                                $help_url='https://docs.wpvivid.com/wpvivid-backup-pro-cloud-storage-overview.html';
                                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote');
                                if($show_learn_more_link)
                                {
                                    $learn_more='<span style="float: right;"><a href="'.$help_url.'">Learn more...</a></span>';
                                }
                                else
                                {
                                    $learn_more='';
                                }
                                echo '<div class="wpvivid-two-col wpvivid-dashboard-list">
                                                <span class="dashicons dashicons-cloud wpvivid-dashicons-large wpvivid-dashicons-green"></span>
                                                <a href="'.$url.'"><b>Cloud Storage</b></a>
                                                '.$learn_more.'
                                                <br>
                                                Connect '.apply_filters('wpvivid_white_label_display', 'WPvivid').' to the leading cloud storage to store your website backups off-site.
                                            </div>';
                            }
                        }

                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-export-site'))
                        {
                            if($backup)
                            {
                                $help_url='https://docs.wpvivid.com/wpvivid-backup-pro-export-site.html';
                                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-export-site', 'wpvivid-export-site');
                                if($show_learn_more_link)
                                {
                                    $learn_more='<span style="float: right;"><a href="'.$help_url.'">Learn more...</a></span>';
                                }
                                else
                                {
                                    $learn_more='';
                                }
                                echo '<div class="wpvivid-two-col wpvivid-dashboard-list">
                                                <span class="dashicons dashicons-migrate wpvivid-dashicons-large wpvivid-dashicons-blue"></span>
                                                <a href="'.$url.'"><b>Export Site</b></a>
                                                '.$learn_more.'
                                                <br>
                                                Export the site to localhost(web server), remote storage or target site (auto-migration) for migration purpose.
                                            </div>';
                            }
                        }

                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-import-site'))
                        {
                            if($backup_list)
                            {
                                $help_url='https://docs.wpvivid.com/wpvivid-backup-pro-import-site.html';
                                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-import-site', 'wpvivid-import-site');
                                if($show_learn_more_link)
                                {
                                    $learn_more='<span style="float: right;"><a href="'.$help_url.'">Learn more...</a></span>';
                                }
                                else
                                {
                                    $learn_more='';
                                }
                                echo '<div class="wpvivid-two-col wpvivid-dashboard-list">
                                                <span class="dashicons dashicons-download wpvivid-dashicons-large wpvivid-dashicons-blue"></span>
                                                <a href="'.$url.'"><b>Import Site</b></a>
                                                '.$learn_more.'
                                                <br>
                                                Import a site from localhost(web server), remote storage or source site (auto-migration).
                                            </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    public function login_form()
    {
        if(is_multisite())
        {
            if(is_main_site())
            {
                $info=get_option('wpvivid_dashboard_info',array());
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $info=get_option('wpvivid_dashboard_info',array());
                restore_current_blog();
            }
        }
        else
        {
            $info=get_option('wpvivid_dashboard_info',array());
        }

        if(empty($info))
        {
            $text="Note: You can use either a father license or a child license to activate ".apply_filters('wpvivid_white_label_display', 'WPvivid')." plugins";
        }
        else
        {
            $text="Note: Please verify your ".apply_filters('wpvivid_white_label_display', 'WPvivid pro')." license again to avoid abuse of addons.";
        }
        ?>
        <div id="wpvivid_dashboard_form" style="padding:0 1em 0em 1em;">
            <div>
                <span><input type="text" id="wpvivid_account_license" placeholder="Enter a license key"><input type="submit" id="wpvivid_active_btn" class="button" value="Authenticate"></span></br>
                <div id="wpvivid_login_box_progress" style="display: none;">
                    <p>
                        <span class="dashicons dashicons-admin-network wpvivid-dashicons-green"></span>
                        <span id="wpvivid_log_progress_text"></span>
                    </p>
                </div>
                <div id="wpvivid_login_error_msg_box" style=";">
                    <p>
                        <span class="dashicons dashicons-info wpvivid-dashicons-grey"></span>
                        <span id="wpvivid_login_error_msg"><?php echo $text;?></span>
                    </p>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
        <script>
            var retry_times = 0;
            var max_retry_times = 3;

            jQuery('#wpvivid_active_btn').click(function()
            {
                wpvivid_dashboard_login();
            });

            function wpvivid_dashboard_login()
            {
                var license = jQuery('#wpvivid_account_license').val();
                var ajax_data={
                    'action':'wpvivid_dashboard_login',
                    'license':license,
                };

                var login_msg = '<?php echo sprintf(__('Logging in to your %s account', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid')); ?>';
                wpvivid_lock_login(true);
                wpvivid_login_progress(login_msg);
                jQuery('#wpvivid_pro_notice').hide();
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        //need_active
                        if(jsonarray.need_active)
                        {
                            retry_times=0;
                            wpvivid_login_progress('You have successfully logged in');
                            wpvivid_active_site();
                        }
                        else
                        {
                            retry_times=0;
                            wpvivid_login_progress('You have successfully logged in');
                            location.reload();
                        }
                    }
                    else
                    {
                        retry_times++;
                        if(retry_times<max_retry_times)
                        {
                            wpvivid_dashboard_login();
                        }
                        else
                        {
                            if (/cURL error 28/i.test(jsonarray.error))
                            {
                                wpvivid_dashboard_login_direct();
                            }
                            else
                            {
                                wpvivid_lock_login(false,jsonarray.error);
                            }
                        }
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    retry_times++;
                    if(retry_times<max_retry_times)
                    {
                        wpvivid_dashboard_login();
                    }
                    else
                    {
                        var error_message = wpvivid_dashboard_output_ajaxerror('check update', textStatus, errorThrown);
                        wpvivid_lock_login(false,error_message);
                    }
                });
            }

            function wpvivid_dashboard_login_direct()
            {
                var license = jQuery('#wpvivid_account_license').val();
                var ajax_data={
                    'action':'wpvivid_dashboard_login_direct',
                    'license':license,
                };

                var login_msg = '<?php echo sprintf(__('Logging in to your %s account', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid')); ?>';
                wpvivid_lock_login(true);
                wpvivid_login_progress(login_msg);
                jQuery('#wpvivid_pro_notice').hide();
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        //need_active
                        if(jsonarray.need_active)
                        {
                            retry_times=0;
                            wpvivid_login_progress('You have successfully logged in');
                            wpvivid_active_site();
                        }
                        else
                        {
                            retry_times=0;
                            wpvivid_login_progress('You have successfully logged in');
                            location.reload();
                        }
                    }
                    else
                    {
                        retry_times++;
                        if(retry_times<max_retry_times)
                        {
                            wpvivid_dashboard_login_direct();
                        }
                        else
                        {
                            wpvivid_lock_login(false,jsonarray.error);
                        }
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    retry_times++;
                    if(retry_times<max_retry_times)
                    {
                        wpvivid_dashboard_login_direct();
                    }
                    else
                    {
                        var error_message = wpvivid_dashboard_output_ajaxerror('check update', textStatus, errorThrown);
                        wpvivid_lock_login(false,error_message);
                    }
                });
            }

            function wpvivid_active_site()
            {
                var license = jQuery('#wpvivid_account_license').val();
                var ajax_data={
                    'action':'wpvivid_dashboard_active',
                    'license':license,
                };

                wpvivid_lock_login(true);
                wpvivid_login_progress('Activating your license on the current site');
                jQuery('#wpvivid_pro_notice').hide();
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        wpvivid_login_progress('Your license has been activated successfully');
                        location.reload();
                    }
                    else
                    {
                        wpvivid_lock_login(false,jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_dashboard_output_ajaxerror('check update', textStatus, errorThrown);
                    wpvivid_lock_login(false,error_message);
                });
            }

            function wpvivid_lock_login(lock,error='')
            {
                if(lock)
                {
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_login_box_progress').show();
                    jQuery('#wpvivid_login_error_msg_box').hide();
                }
                else
                {
                    jQuery('#wpvivid_log_progress_text').html('');
                    jQuery('#wpvivid_login_box_progress').hide();
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'auto', 'opacity': '1'});

                    if(error!=='')
                    {
                        //wpvivid_display_pro_notice('Error', error);
                        jQuery('#wpvivid_login_error_msg_box').show();
                        jQuery('#wpvivid_login_error_msg').html(error);
                    }
                }
            }

            function wpvivid_login_progress(log)
            {
                jQuery('#wpvivid_log_progress_text').html(log);
            }
        </script>
        <?php
    }

    public function get_plugins_status($dashboard_info)
    {
        global $wpvivid_backup_pro;
        $plugins=array();

        foreach ($dashboard_info['plugins'] as $slug=>$info)
        {
            $plugin['name']=$info['name'];
            $plugin['slug']=$slug;
            $status=$wpvivid_backup_pro->addons_loader->get_plugin_status($info);

            if($status['status']=='Installed'&&$status['action']=='Update')
            {
                $plugin['status']='Update now';
            }
            else
            {
                $plugin['status']=$status['status'];
            }

            $plugin['info']=$info['description'];
            $plugin['requires_plugins']=$wpvivid_backup_pro->addons_loader->get_plugin_requires($info);
            $plugin['is_free']=$wpvivid_backup_pro->addons_loader->is_plugin_free($info);
            $plugins[$slug]=$plugin;
        }
        return $plugins;
    }

    public function progress_bar()
    {
        if(isset($_REQUEST['install'])&&$_REQUEST['install'])
        {
            ?>
            <div style="padding:0 1em;">
                <div>
                    <span>
                        <strong>Installing addon: </strong>
                    </span>
                    <span id="wpvivid_plugin_title"></span>
                    <br>
                    <span class="wpvivid-span-progress" >
                        <span id="wpvivid_plugin_progress_text" class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress"></span>
                    </span>
                </div>
            </div>
            <?php
        }
        else
        {
            ?>
            <div class="wpvivid-install-addon-init-data" style="padding:0 1em; display: none;">
                <div>
                    <span>
                        <strong>Initializing data</strong>
                    </span>
                    <span id="wpvivid_plugin_title"></span>
                    <br>
                    <span class="wpvivid-span-progress" >
                        <span id="wpvivid_plugin_progress_text" class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress"></span>
                    </span>
                </div>
            </div>
            <?php
        }
    }

    public function addon_bar()
    {
        if(is_multisite())
        {
            if(is_main_site())
            {
                $last_login_time=get_option('wpvivid_last_login_time',0);
                $dashboard_info=get_option('wpvivid_dashboard_info',array());
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $last_login_time=get_option('wpvivid_last_login_time',0);
                $dashboard_info=get_option('wpvivid_dashboard_info',array());
                restore_current_blog();
            }
        }
        else
        {
            $last_login_time=get_option('wpvivid_last_login_time',0);
            $dashboard_info=get_option('wpvivid_dashboard_info',array());
        }

        $plugins=$this->get_plugins_status($dashboard_info);
        $all_installed=true;
        foreach ($plugins as $item)
        {
            if($item['status']=='Installed'||$item['status']=='Up to date'||$item['status']=='Update now')
            {
            }
            else
            {
                $all_installed=false;
            }
        }

        $need_login=false;

        if($last_login_time+60*60*24>time())
        {
            $need_login=false;
        }
        else
        {
            if($all_installed)
            {
                $need_login=false;
            }
            else
            {
                $need_login=true;
            }
        }

        if(isset($_REQUEST['install'])&&$_REQUEST['install'])
        {

        }
        else
        {
            if($need_login)
            {
                $this->login_form();
            }
        }

        ?>
        <div id="wpvivid_addons_list" style="padding: 0 1em 1em 1em;">
            <ul class="wpvivid-three-cols">
                <?php
                foreach ($plugins as $item)
                {
                    if($item['slug'] === 'backup_pro')
                        continue;
                    $this->output_addons($item,$need_login);
                }

                if($this->has_backup_pro($plugins))
                {
                    $this->output_backup_addons($plugins,$need_login);
                }
                else
                {
                    foreach ($plugins as $item)
                    {
                        if($item['slug'] === 'backup_pro')
                        {
                            $this->output_addons($item,$need_login);
                        }
                    }

                }
                ?>
            </ul>
        </div>
        <div style="clear:both;"></div>
        <div style="padding:1em;">
            <span class="dashicons dashicons-admin-plugins wpvivid-dashicons-small wpvivid-dashicons-blue"></span><span>= Installed and Activated</span>
            <span style="padding:0 0.5em"></span>
            <span class="dashicons dashicons-admin-plugins wpvivid-dashicons-small wpvivid-dashicons-grey"></span><span>= Not Installed</span>
        </div>
        <?php
        if(isset($_REQUEST['install'])&&$_REQUEST['install']||isset($_REQUEST['finish'])&&$_REQUEST['finish']||isset($_REQUEST['first'])&&$_REQUEST['first'])
        {
            ?>
            <script>
                jQuery(document).scrollTop(jQuery(document).height());
            </script>
            <?php
        }
        if(isset($_REQUEST['install'])&&$_REQUEST['install'])
        {
            $plugin_install_cache=get_option('wpvivid_plugin_install_cache',array());
            if(empty($plugin_install_cache)||empty($plugin_install_cache['plugins']))
            {
                return;
            }

            ?>
            <!--<div style="padding:1em;"></div>-->
            <script>
                jQuery(document).scrollTop(jQuery(document).height());
            </script>
            <?php

            if(!class_exists('WPvivid_Plugin_Installer'))
            {
                include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/installer/class-wpvivid-installer.php';
            }
            $installer=new WPvivid_Plugin_Installer();
            $installer->run_installation();
        }
    }

    public function output_addons($item,$need_login)
    {
        $status='';
        $class='';
        $span='';
        $background_class='';
        $can_href=false;
        $is_free_plugin=$item['is_free'];

        if($item['status']=='Not available')
        {
            $status='Unavailable';
            $class='wpvivid-dashicons-grey';
            $span='';
            $background_class='';
            $can_href=false;
        }
        else if ($item['status'] == 'Inactive')
        {
            $status='Activate';
            $class='wpvivid-dashicons-grey';
            $span='';
            $background_class='';
            $can_href=false;
        }
        else if($item['status']=='Installed'||$item['status']=='Up to date')
        {
            if($item['requires_plugins']!==false)
            {
                foreach ($item['requires_plugins'] as $plugin)
                {
                    if($plugin['status']=='Installed'||$plugin['status']=='Up to date')
                    {
                        $status='';
                        $class='wpvivid-dashicons-blue';
                        $span='';
                        $background_class='wpvivid-three-cols-active';
                        $can_href=true;
                    }
                    else
                    {
                        $status='Install';
                        $class='wpvivid-dashicons-grey';
                        $span='';
                        $background_class='';
                        $can_href=false;
                    }
                }
            }
            else
            {
                $status='';
                $class='wpvivid-dashicons-blue';
                $span='';
                $background_class='wpvivid-three-cols-active';
                $can_href=true;
            }
        }
        else
        {
            if($item['status']=='Un-installed')
            {
                $status='Install';

                $class='wpvivid-dashicons-grey';
                $span='';
                $background_class='';
                $can_href=false;
            }
            else if($item['status']=='Update now')
            {
                $status='Update';
                $class='wpvivid-dashicons-blue';
                $span='<span class="wpvivid-three-cols-update" title="There is a new version">1</span>';
                $background_class='wpvivid-three-cols-active';
                $can_href=true;
            }
            else
            {
                $status='';
                $class='wpvivid-dashicons-blue';
                $span='';
                $background_class='wpvivid-three-cols-active';
                $can_href=true;
            }
        }

        $page_url=apply_filters('wpvivid_addon_page_url', '',$item['slug']);
        $title=apply_filters('wpvivid_addon_page_title',$item['name'],$item['slug']);

        $is_install=isset($_REQUEST['install'])&&$_REQUEST['install'];

        if($is_install)
        {
            $install_class='';
        }
        else if($is_free_plugin)
        {
            $install_class='wpvivid-addons';
        }
        else if($need_login)
        {
            $install_class='wpvivid-need-login';
        }
        else
        {
            $install_class='wpvivid-addons';
        }
        ?>
        <li>
            <div class="wpvivid-three-cols-li <?php esc_attr_e($background_class); ?>" addon-type="<?php esc_attr_e($item['slug']); ?>">
                <span class="dashicons dashicons-admin-plugins wpvivid-dashicons-middle <?php esc_attr_e($class); ?>"></span>
                <?php _e($span); ?>
                <b>
                    <?php
                    if($can_href&&!empty($page_url))
                    {
                        ?>
                        <a href="<?php echo $page_url;?>"><?php echo $title;?></a>
                        <?php
                    }
                    else
                    {
                        ?>
                        <a><?php echo $title;?></a>
                        <?php
                    }
                    ?>
                </b>
                <a>
                    <small>
                        <span class="<?php esc_attr_e($install_class); ?> <?php echo $status; ?>" style="float: right;"><?php _e($status); ?></span>
                    </small>
                </a>
                <br>
                <span class="wpvivid-addon-info-text" title="<?php echo $item['info'];?>"><?php echo $item['info'];?></span>
            </div>
        </li>
        <?php
    }

    public function has_backup_pro($plugins)
    {
        //$dashboard_info=get_option('wpvivid_dashboard_info',array());
        //$plugins=$this->get_plugins_status($dashboard_info);
        $has=false;
        foreach ($plugins as $item)
        {
            if($item['slug'] === 'backup_pro')
            {
                if($item['status']=='Installed'||$item['status']=='Up to date')
                {
                    $has=true;
                }
                else if($item['status']=='Update now')
                {
                    $has=true;
                }
                else if($item['status']=='Inactive')
                {
                    $has=true;
                }
            }
        }
        return $has;
    }

    public function output_backup_addons($plugins,$need_login)
    {
        //$dashboard_info = get_option('wpvivid_dashboard_info', array());
        //$plugins = $this->get_plugins_status($dashboard_info);
        $item = $plugins['backup_pro'];

        $status = '';
        $class = '';
        $span = '';
        $background_class = '';
        $can_href = false;

        if ($item['status'] == 'Not available') {
            $status = 'Unavailable';
            $class = 'wpvivid-dashicons-grey';
            $span = '';
            $background_class = '';
            $can_href = false;
        }
        else if ($item['status'] == 'Inactive')
        {
            $status='Activate';
            $class='wpvivid-dashicons-grey';
            $span='';
            $background_class='';
            $can_href=false;
        }
        else if($item['status']=='Installed'||$item['status']=='Up to date')
        {
            if($item['requires_plugins']!==false)
            {
                foreach ($item['requires_plugins'] as $plugin)
                {
                    if($plugin['status']=='Installed'||$plugin['status']=='Up to date')
                    {
                        $status='';
                        $class='wpvivid-dashicons-blue';
                        $span='';
                        $background_class='wpvivid-three-cols-active';
                        $can_href=true;
                    }
                    else
                    {
                        $status='Install';
                        $class='wpvivid-dashicons-grey';
                        $span='';
                        $background_class='';
                        $can_href=false;
                    }
                }
            }
            else
            {
                $status='';
                $class='wpvivid-dashicons-blue';
                $span='';
                $background_class='wpvivid-three-cols-active';
                $can_href=true;
            }
        }
        else
        {
            if($item['status']=='Un-installed')
            {
                $status='Install';

                $class='wpvivid-dashicons-grey';
                $span='';
                $background_class='';
                $can_href=false;
            }
            else if($item['status']=='Update now')
            {
                $status='Update';
                $class='wpvivid-dashicons-blue';
                $span='<span class="wpvivid-three-cols-update" title="There is a new version">1</span>';
                $background_class='wpvivid-three-cols-active';
                $can_href=true;
            }
            else
            {
                $status='';
                $class='wpvivid-dashicons-blue';
                $span='';
                $background_class='wpvivid-three-cols-active';
                $can_href=true;
            }
        }

        $is_install=isset($_REQUEST['install'])&&$_REQUEST['install'];

        if($is_install)
        {
            $install_class='';
        }
        else if($need_login)
        {
            $install_class='wpvivid-need-login';
        }
        else
        {
            $install_class='wpvivid-addons';
        }
        ?>
        <li>
            <div class="wpvivid-three-cols-li <?php esc_attr_e($background_class); ?>" addon-type="backup_pro">
                <span class="dashicons dashicons-admin-plugins wpvivid-dashicons-middle <?php esc_attr_e($class); ?>"></span>
                <?php _e($span); ?>
                <b>
                    <?php
                    if($can_href)
                    {
                        ?>
                        <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner'); ?>">Unused Image Scanner</a>
                        <?php
                    }
                    else
                    {
                        ?>
                        <a>Unused Image Scanner</a>
                        <?php
                    }
                    ?>
                </b>
                <a><small><span class="<?php esc_attr_e($install_class); ?> <?php echo $status; ?>" style="float: right;"><?php _e($status); ?></span></small></a><br>
                <span class="wpvivid-addon-info-text" title="Analyze and find unused images in your media folder and delete them.">
                    Analyze and find unused images in your media folder and delete them.
                </span>
            </div>
        </li>
        <li>
            <div class="wpvivid-three-cols-li <?php esc_attr_e($background_class); ?>" addon-type="backup_pro">
                <span class="dashicons dashicons-admin-plugins wpvivid-dashicons-middle <?php esc_attr_e($class); ?>"></span>
                <?php _e($span); ?>
                <b>
                    <?php
                    if($can_href)
                    {
                        ?>
                        <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-export-import', 'wpvivid-export-import'); ?>">Export/Import Post or Page</a>
                        <?php
                    }
                    else
                    {
                        ?>
                        <a>Export/Import Post or Page</a>
                        <?php
                    }
                    ?>
                </b>
                <a><small><span class="<?php esc_attr_e($install_class); ?> <?php echo $status; ?>" style="float: right;"><?php _e($status); ?></span></small></a><br>
                <span class="wpvivid-addon-info-text" title="Export or import website content in bulk, including pages, posts, comments, terms, images and thumbnails.">
                    Export or import website content in bulk, including pages, posts, comments, terms, images and thumbnails.
                </span>
            </div>
        </li>
        <li>
            <div class="wpvivid-three-cols-li <?php esc_attr_e($background_class); ?>" addon-type="backup_pro">
                <span class="dashicons dashicons-admin-plugins wpvivid-dashicons-middle <?php esc_attr_e($class); ?>"></span>
                <?php _e($span); ?>
                <b>
                    <?php
                    if($can_href)
                    {
                        ?>
                        <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-export-import', 'wpvivid-export-import').'&url_replace'; ?>">URL Replacement</a>
                        <?php
                    }
                    else
                    {
                        ?>
                        <a>URL Replacement</a>
                        <?php
                    }
                    ?>
                </b>
                <a><small><span class="<?php esc_attr_e($install_class); ?> <?php echo $status; ?>" style="float: right;"><?php _e($status); ?></span></small></a><br>
                <span class="wpvivid-addon-info-text" title="Do a quick domain/url replacing in the database, with no need to perform a database migration.">
                    Do a quick domain/url replacing in the database, with no need to perform a database migration.
                </span>
            </div>
        </li>
        <li>
            <div class="wpvivid-three-cols-li <?php esc_attr_e($background_class); ?>" addon-type="backup_pro">
                <span class="dashicons dashicons-admin-plugins wpvivid-dashicons-middle <?php esc_attr_e($class); ?>"></span>
                <?php _e($span); ?>
                <b>
                    <?php
                    if($can_href)
                    {
                        ?>
                        <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-rollback', 'wpvivid-rollback'); ?>">Rollback</a>
                        <?php
                    }
                    else
                    {
                        ?>
                        <a>Rollback</a>
                        <?php
                    }
                    ?>
                </b>
                <a><small><span class="<?php esc_attr_e($install_class); ?> <?php echo $status; ?>" style="float: right;"><?php _e($status); ?></span></small></a><br>
                <span class="wpvivid-addon-info-text" title="Perform a return to a prior state of plugins, themes and Wordpress core.">
                   Perform a return to a prior state of plugins, themes and Wordpress core.
                </span>
            </div>
        </li>
        <?php
    }

    public function addon_form()
    {
        $this->progress_bar();
        $this->addon_bar();
        $error = "Please verify your ".apply_filters('wpvivid_white_label_display', 'WPvivid pro')." license again to avoid abuse of addons.";
        ?>
        <script>
            jQuery('.wpvivid-addons').on('click', function()
            {
                if(jQuery(this).hasClass('Activate'))
                {
                    var json = {};
                    json['plugins_list'] = Array();
                    var addon_type = jQuery(this).closest('div').attr('addon-type');
                    json['plugins_list'].push(addon_type);

                    var ajax_data={
                        'action':'wpvivid_activate_plugin',
                        'plugins':json['plugins_list'],
                    };

                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            location.href=jsonarray.href;
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown)
                    {
                    });
                }
                if(jQuery(this).hasClass('Install') || jQuery(this).hasClass('Update'))
                {
                    var json = {};
                    json['plugins_list'] = Array();

                    var addon_type = jQuery(this).closest('div').attr('addon-type');
                    json['plugins_list'].push(addon_type);

                    var ajax_data={
                        'action':'wpvivid_init_plugin_install_ex',
                        'plugins':json['plugins_list'],
                    };

                    jQuery('.wpvivid-install-addon-init-data').show();
                    jQuery('#wpvivid_dashboard_form').hide();
                    jQuery('.wpvivid-span-processed-percent-progress').css('width', '0%');

                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            location.href=jsonarray.href;
                        }
                        else
                        {
                            location.reload();
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        location.reload();
                    });
                }
            });

            jQuery('.wpvivid-need-login').on('click', function()
            {
                //alert("Please verify your WPvivid pro license again to avoid abuse of addons.");
                alert("<?php echo $error; ?>");
            });
        </script>
        <?php
    }

    public function addon_box()
    {
        if(is_multisite())
        {
            if(is_main_site())
            {
                $user_info= get_option('wpvivid_pro_user',false);
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $user_info= get_option('wpvivid_pro_user',false);
                restore_current_blog();
            }
        }
        else
        {
            $user_info= get_option('wpvivid_pro_user',false);
        }

        ?>
        <div class="wpvivid-dashboard">
            <div class="wpvivid-clear-float">
                <div class="wpvivid-one-coloum" style="padding:1em; box-sizing: border-box;">
                    <span>
                        <h1>
                            <span class="dashicons dashicons-list-view wpvivid-dashicons-blue" style="margin-top:0.3em;"></span>
                            <span>Addons/Tools</span>
                            <?php
                            if($user_info===false)
                            {

                            }
                            else
                            {
                                ?>
                                <span style="margin-left:2em; font-size:13px;float:right;">
                                    <span class="dashicons dashicons-update wpvivid-dashicons-green"></span>
                                    <strong>
                                        <a href="<?php esc_attr_e(apply_filters('wpvivid_get_admin_url', '').'plugins.php?s=wpvivid&plugin_status=all'); ?>">Update all</a>
                                    </strong>
                                </span>
                                <?php
                            }
                            ?>
                        </h1>
                    </span>
                </div>
                <div style="clear:both;"></div>

                <?php
                if($user_info===false)
                {
                    $this->login_form();
                }
                else
                {
                    $this->addon_form();
                }
                ?>
            </div>
        </div>
        <?php
    }
    //
    public function get_incremental_schedules_data()
    {
        $offset=get_option('gmt_offset');

        $data=apply_filters('wpvivid_get_incremental_data',array());
        if(empty($data))
        {
            $next_files_backup='N/A';
            $next_db_backup='N/A';
        }
        else
        {
            $next_db_backup = $data['database_backup']['backup_next_time'];
            if($next_db_backup==0)
            {
                $next_db_backup='N/A';
            }
            else
            {
                $next_db_backup = $next_db_backup + $offset * 60 * 60;
                $next_db_backup = date("H:i:s - F-d-Y ", $next_db_backup);
            }

            $next_incremental_backup= $data['incremental_backup']['backup_next_time'];
            $next_full_backup= $data['full_backup']['backup_next_time'];

            $next_files_backup=max($next_full_backup,$next_incremental_backup);
            if($next_files_backup==0)
            {
                $next_files_backup='N/A';
            }
            else
            {
                $next_files_backup = $next_files_backup + $offset * 60 * 60;
                $next_files_backup = date("H:i:s - F-d-Y ", $next_files_backup);
            }
        }

        $last_full_backup_time=0;
        $last_full_backup_status='';
        $last_incremental_backup_time=0;
        $last_incremental_backup_status='';
        $last_msg=get_option('wpvivid_full_backup_last_msg',array());
        if(!empty($last_msg))
        {
            $last_full_backup_time=$last_msg['status']['start_time'] ;
            if($last_msg['status']['str'] == 'completed')
            {
                $last_full_backup_status='Succeeded';
            }
            else if($last_msg['status']['str'] == 'error')
            {
                $last_full_backup_status='Failed';
            }
            else if($last_msg['status']['str'] == 'cancel')
            {
                $last_full_backup_status='Canceled';
            }
            else
            {
                $last_full_backup_status='Succeeded';
            }
        }

        $last_msg=get_option('wpvivid_incremental_backup_last_msg',array());
        if(!empty($last_msg))
        {
            $last_incremental_backup_time=$last_msg['status']['start_time'] ;
            if($last_msg['status']['str'] == 'completed')
            {
                $last_incremental_backup_status='Succeeded';
            }
            else if($last_msg['status']['str'] == 'error')
            {
                $last_incremental_backup_status='Failed';
            }
            else if($last_msg['status']['str'] == 'cancel')
            {
                $last_incremental_backup_status='Canceled';
            }
            else
            {
                $last_incremental_backup_status='Succeeded';
            }
        }

        if($last_incremental_backup_time>=$last_full_backup_time)
        {
            $last_files_backup_time=$last_incremental_backup_time;
            $last_files_backup_status=$last_incremental_backup_status;
        }
        else if($last_incremental_backup_time<$last_full_backup_time)
        {
            $last_files_backup_time=$last_full_backup_time;
            $last_files_backup_status=$last_full_backup_status;
        }
        else
        {
            $last_files_backup_time=0;
            $last_files_backup_status='';
        }

        if($last_files_backup_time>0)
        {
            $offset=get_option('gmt_offset');
            $last_files_backup_time = $last_files_backup_time + ($offset * 60 * 60);
            $last_files_backup_time=date("H:i:s - F-d-Y ", $last_files_backup_time);
        }
        else
        {
            $last_files_backup_time='N/A';
        }

        $last_db_backup_time=0;
        $last_msg=get_option('wpvivid_incremental_database_last_msg',array());
        if(!empty($last_msg))
        {
            $last_db_backup_time=$last_msg['status']['start_time'] ;
        }

        if($last_db_backup_time>0)
        {
            $offset=get_option('gmt_offset');
            $last_db_backup_time = $last_db_backup_time + ($offset * 60 * 60);
            $last_db_backup_time=date("H:i:s - F-d-Y ", $last_db_backup_time);
        }
        else
        {
            $last_db_backup_time='N/A';
        }

        $data['last_files_backup_time']=$last_files_backup_time;
        $data['last_files_backup_status']=$last_files_backup_status;
        $data['last_db_backup_time']=$last_db_backup_time;
        $data['next_files_backup']=$next_files_backup;
        $data['next_db_backup']=$next_db_backup;
        return $data;
    }

    public function get_general_schedules_data()
    {
        $offset=get_option('gmt_offset');

        $schedules = get_option('wpvivid_schedule_addon_setting', array());
        $avtived_schedules = array();

        $last_backup_time='N/A';
        $next_backup_time='N/A';
        $last_backup_status='';
        if(!empty($schedules)){
            foreach ($schedules as $schedule){
                if($schedule['status'] === 'Active'){
                    $avtived_schedules[] = $schedule;
                }
            }
            $avtived_schedules = $this->sort_list($avtived_schedules);
            foreach ($avtived_schedules as $schedule){
                $timestamp=wp_next_scheduled($schedule['id'], array($schedule['id']));
                if($timestamp !== false) {
                    $next_backup_time = date("H:i:s - F-d-Y ", $timestamp + $offset * 60 * 60);
                }
                else{
                    $next_backup_time = 'N/A';
                }
                break;
            }

            $message=get_option('wpvivid_general_schedule_data',array());
            if(!empty($message)){
                $last_backup_time = strtotime($message['status']['start_time']) + $offset * 60 * 60;
                $last_backup_time = date("H:i:s - F-d-Y ", $message['status']['start_time'] + $offset * 60 * 60);
                if($message['status']['str'] == 'completed'){
                    $last_backup_status='Succeeded';
                }
                elseif($message['status']['str'] == 'error'){
                    $last_backup_status='Failed';
                }
                elseif($message['status']['str'] == 'cancel'){
                    $last_backup_status='Failed';
                }
                else{
                    $last_backup_status='Succeeded';
                }
            }
        }

        $data['last_backup_time']=$last_backup_time;
        $data['next_backup_time']=$next_backup_time;
        $data['last_backup_status']=$last_backup_status;
        return $data;
    }

    public function sort_list($schedule)
    {
        uasort($schedule, function ($a, $b) {
            $a_timestamp = wp_next_scheduled($a['id'], array($a['id']));
            $a['next_start'] = $a_timestamp;
            $b_timestamp = wp_next_scheduled($b['id'], array($b['id']));
            $b['next_start'] = $b_timestamp;
            if ($a['next_start'] > $b['next_start']) {
                return 1;
            } else if ($a['next_start'] === $b['next_start']) {
                return 0;
            } else {
                return -1;
            }
        });

        return $schedule;
    }

    public function addon_page_url($url,$slug)
    {
        if($slug=='imgoptim_pro')
        {
            $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-imgoptim', 'wpvivid-imgoptim');
        }
        else if($slug=='staging_pro')
        {
            $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-staging', 'wpvivid-staging');
        }
        else if($slug=='white_label')
        {
            $url='';
        }
        else if($slug=='role_cap')
        {
            $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-capabilities', 'wpvivid-capabilities');
        }
        return $url;
    }

    public function addon_page_title($title,$slug)
    {
        if($slug=='imgoptim_pro')
        {
            $title='Image Optimization Pro';
        }
        else if($slug=='staging_pro')
        {
            $title='Staging';
        }
        else if($slug=='white_label')
        {
            $title='White Label';
        }
        else if($slug=='role_cap')
        {
            $title='Roles & Capabilities';
        }
        return $title;
    }
}