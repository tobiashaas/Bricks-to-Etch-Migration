<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_BackupList_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_BackupList_addon
{
    public $main_tab;
    public $log_tab;
    public $download_ui;

    public function __construct()
    {
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);
        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),11);
        //filters
        add_filter('wpvivid_archieve_remote_array', array($this, 'archieve_remote_array'), 11);
        add_filter('wpvivid_check_remove_restore_database', array($this, 'wpvivid_check_remove_restore_database'), 11, 2);
        add_filter('wpvivid_get_backup_data_by_task',array($this,'get_backup_data_by_task'),10, 2);

        //actions
        add_action('wpvivid_update_backup',array($this, 'update_backup_item'),11, 3);

        //new ajax
        add_action('wp_ajax_wpvivid_get_backup_list', array($this, 'get_backup_list_ex'));
        add_action('wp_ajax_wpvivid_scan_remote_backup', array($this, 'scan_remote_backup'));
        add_action('wp_ajax_wpvivid_scan_remote_backup_continue', array($this, 'scan_remote_backup_continue'));
        add_action('wp_ajax_wpvivid_delete_backup_ex',array( $this,'delete_backup_ex'));
        add_action('wp_ajax_wpvivid_delete_backup_array_ex', array($this, 'delete_backup_array_ex'));
        //old ajax
        add_action('wp_ajax_wpvivid_set_security_lock_ex',array( $this,'set_security_lock_ex'));

        add_action('wp_ajax_wpvivid_download_backup_ex',array($this,'download_backup_ex'));
        add_action('wp_ajax_wpvivid_download_all_backup_ex', array($this, 'download_all_backup_ex'));

        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));
        add_action('admin_head', array($this, 'my_admin_custom_styles'));
        add_filter('wpvivid_export_setting_addon', array($this, 'export_setting_addon'), 11);
    }

    public function export_setting_addon($json)
    {
        $remote_list = get_option('wpvivid_new_remote_list',array());
        $json['data']['wpvivid_new_remote_list'] = $remote_list;

        return $json;
    }

    public function my_admin_custom_styles()
    {
        ?>
        <style type="text/css">
            .updates-table tbody td.wpvivid-check-column, .widefat tbody th.wpvivid-check-column, .widefat tfoot td.wpvivid-check-column, .widefat thead td.wpvivid-check-column {
                padding: 11px 0 0 3px;
            }
            .widefat tfoot td.wpvivid-check-column, .widefat thead td.wpvivid-check-column {
                padding-top: 4px;
                vertical-align: middle;
            }
            .widefat .wpvivid-check-column {
                width: 2.2em;
                padding: 6px 0 25px;
                vertical-align: top;
            }
        </style>
        <?php
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-mange-backup';
        $cap['display']='Backups Manager & Restoration';
        $cap['index']=8;
        $cap['icon']='<span class="dashicons dashicons-database wpvivid-dashicons-grey"></span>';
        $cap['menu_slug']=strtolower(sprintf('%s-backup-and-restore', apply_filters('wpvivid_white_label_slug', 'wpvivid')));

        $cap_list[$cap['slug']]=$cap;

        $cap['slug']='wpvivid-can-mange-local-backup';
        $cap['display']='Manage backups in localhost & Restore backups';
        $cap['index']=9;
        $cap['menu_slug']=strtolower(sprintf('%s-localhost-backup-and-restore', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['icon']='<strong>-----</strong>';
        $cap_list[$cap['slug']]=$cap;

        $cap['slug']='wpvivid-can-mange-remote-backup';
        $cap['display']='Manage backups in remote & Restore backups';
        $cap['index']=10;
        $cap['menu_slug']=strtolower(sprintf('%s-remote-backup-and-restore', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['icon']='<strong>-----</strong>';
        $cap_list[$cap['slug']]=$cap;

        $cap['slug']='wpvivid-can-mange-download-localhost-backup';
        $cap['display']='Download & restore backups from localhost';
        $cap['index']=11;
        $cap['menu_slug']=strtolower(sprintf('%s-download-localhost-backup', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['icon']='<strong>-----</strong>';
        $cap_list[$cap['slug']]=$cap;

        $cap['slug']='wpvivid-can-mange-download-remote-backup';
        $cap['display']='Download & restore backups from remote storage';
        $cap['index']=12;
        $cap['menu_slug']=strtolower(sprintf('%s-download-remote-backup', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['icon']='<strong>-----</strong>';
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-backup-and-restore';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-backup-and-restore';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_backup_restore');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Backup Manager');
            $submenu['menu_title'] = 'Backup Manager';

            /*
            if( apply_filters('wpvivid_is_user_super_admin',true))
            {
                $submenu['capability'] = 'administrator';
            }
            else {
                $submenu['capability'] = 'wpvivid-can-mange-backup';
            }
             else if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-local-backup'))
            {
                $submenu['capability'] = 'wpvivid-can-mange-local-backup';
            }
            else if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-remote-backup'))
            {
                $submenu['capability'] = 'wpvivid-can-mange-remote-backup';
            }
             */

            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-backup-and-restore");

            $submenu['menu_slug'] = strtolower(sprintf('%s-backup-and-restore', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 7;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_backup_restore');
        if($display) {
            $menu['id'] = 'wpvivid_admin_menu_backup_restore';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Backup Manager';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-backup-and-restore');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid').'-backup-and-restore';

            /*
            if( apply_filters('wpvivid_is_user_super_admin',true))
            {
                $menu['capability'] = 'administrator';
            }
            else {
                $menu['capability'] = 'wpvivid-can-mange-backup';
            }

             else if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-local-backup'))
            {
                $menu['capability'] = 'wpvivid-can-mange-local-backup';
            }
            else if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-remote-backup'))
            {
                $menu['capability'] = 'wpvivid-can-mange-remote-backup';
            }
             */

            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-backup-and-restore");

            $menu['index'] = 7;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function init_page()
    {
        if(isset($_REQUEST['restore']))
        {
            do_action('wpvivid_output_restore_page');
            return;
        }
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Backup Manager & Restoration', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-update-alt wpvivid-dashicons-large wpvivid-dashicons-green"></span><span class="wpvivid-page-title">Backup Manager & Restoration</span></p>
                                        <span class="about-description">The page allows you to browse and manage all your backups, upload backups and restore the website from backups.</span>
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
                                                    <!-- The content you need -->
                                                    <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpvivid-nav-bar wpvivid-clear-float">
                                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                                        <span> Please <strong>do not</strong> change the backup file name, otherwise, the plugin will <strong>be unable to</strong> recognize the backup to perform restore or migration.</span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $first_tab=false;
                                    $args['span_class']='dashicons dashicons-admin-home wpvivid-dashicons-orange';
                                    $args['span_style']='color:orange; padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;display:block;';
                                    $args['is_parent_tab']=0;

                                    if(apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-backup'))
                                    {
                                        $first_tab=true;
                                        $tabs['all_backups']['title']='All Backups';
                                        $tabs['all_backups']['slug']='all_backups';
                                        $tabs['all_backups']['callback']=array($this, 'output_all_backups');
                                        $tabs['all_backups']['args']=$args;
                                    }

                                    $args['span_class']='dashicons dashicons-upload wpvivid-dashicons-green';
                                    $args['span_style']='padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;

                                    if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-local-backup') && apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-backup') )
                                    {
                                        $tabs['upload_backup']['title']='Upload';
                                        $tabs['upload_backup']['slug']='upload_backup';
                                        $tabs['upload_backup']['callback']=array($this, 'output_upload_backup');
                                        $tabs['upload_backup']['args']=$args;
                                    }

                                    $args['span_class']='dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey';
                                    $tabs['backup_log_list']['title']='Logs';
                                    $tabs['backup_log_list']['slug']='backup_log_list';
                                    $tabs['backup_log_list']['callback']=array($this, 'output_backup_log_list');
                                    $tabs['backup_log_list']['args']=$args;

                                    $args['span_class']='dashicons dashicons-arrow-down-alt wpvivid-dashicons-grey';
                                    $args['can_delete']=1;
                                    $args['hide']=1;
                                    $tabs['download_backup']['title']='Download';
                                    $tabs['download_backup']['slug']='download_backup';
                                    $this->download_ui=new WPvivid_Backup_Download_UI($this->main_tab->container_id);
                                    $tabs['download_backup']['callback']=array($this->download_ui, 'output_download_page');
                                    $tabs['download_backup']['args']=$args;

                                    $args['span_class']='dashicons dashicons-image-rotate wpvivid-dashicons-grey';
                                    $args['can_delete']=1;
                                    $args['hide']=1;
                                    $tabs['restore_backup']['title']='Restore';
                                    $tabs['restore_backup']['slug']='restore_backup';
                                    $tabs['restore_backup']['callback']=array($this, 'output_restore_backup');
                                    $tabs['restore_backup']['args']=$args;

                                    $args['span_class']='';
                                    $args['span_style']='';
                                    $args['can_delete']=1;
                                    $args['hide']=1;
                                    $tabs['view_detail']['title']='Content';
                                    $tabs['view_detail']['slug']='view_detail';
                                    $tabs['view_detail']['callback']=array($this, 'output_view_detail');
                                    $tabs['view_detail']['args']=$args;

                                    $args['span_class']='';
                                    $args['span_style']='';
                                    $args['can_delete']=1;
                                    $args['hide']=1;
                                    $tabs['open_log']['title']='Logs';
                                    $tabs['open_log']['slug']='open_log';
                                    $tabs['open_log']['callback']=array($this, 'output_open_log');
                                    $tabs['open_log']['args']=$args;

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
                    do_action( 'wpvivid_backup_pro_add_sidebar' );
                    ?>

                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($)
            {
                <?php
                if(isset($_REQUEST['log']))
                {
                $log=$_REQUEST['log'];
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'backup_log_list', 'backup_log_list' ]);
                wpvivid_open_log("<?php echo $log ?>","backup_log_list");
                <?php
                }
                ?>

                <?php
                if(isset($_REQUEST['local_restore']))
                {
                ?>
                var restoredata = '<?php echo $_REQUEST['restoredata']; ?>';
                <?php
                }
                ?>

                <?php
                if(isset($_REQUEST['remote_restore']))
                {
                ?>
                var restoredata = '<?php echo $_REQUEST['restoredata']; ?>';
                <?php
                }
                else
                {
                ?>
                //wpvivid_get_remote_backup_folder();
                <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public function output_all_backups()
    {
        $remoteslist=WPvivid_Setting::get_all_remote_options();

        ?>
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <div>
                    <span style="float: left;">
                        <select id="wpvivid_select_backup_storage">
                            <option value="all_backups" selected="selected">All Backups</option>
                            <option value="localhost">Localhost</option>
                            <option value="cloud">Cloud Storage</option>
                        </select>
                    </span>
                    <span id="wpvivid_select_remote_storage_box" style="float: left;display: none">
                        <select id="wpvivid_select_remote_storage">
                            <option value="all_backup" selected="selected">--</option>
                            <?php
                            foreach ($remoteslist as $key => $value)
                            {
                                if($key === 'remote_selected')
                                {
                                    continue;
                                }

                                echo '<option value="'.$value['id'].'">'.$value['name'].'</option>';
                            }
                            ?>
                        </select>
                    </span>
                    <span style="float: left;">
                        <select id="wpvivid_select_backup_folder">
                            <option value="all_backup" selected="selected">--</option>
                            <option value="Manual">Manual Backups</option>
                            <option value="Cron">Scheduled Backups</option>
                            <option id="wpvivid_uploaded_option" value="Upload">Uploaded Backups</option>
                            <option id="wpvivid_migrated_option" value="Migrate">Backups for Migration</option>
                            <option value="Rollback">Rollback Backups</option>
                            <option value="Incremental">Incremental</option>
                        </select>
                    </span>
                    <span>
                        <input id="wpvivid_get_scan_backup" type="submit" class="button action top-action" value="Scan">
                    </span>
                    <span class="spinner is-active" id="wpvivid_backup_scanning" style="float: left; display: none;"></span>
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                        <div class="wpvivid-bottom">
                            <!-- The content you need -->
                            <p>Choose the backups you want to browse.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                    <div style="clear: both;"></div>
                </div>
            </div>
            <div class="tablenav-pages">
                <input type="submit" class="button-primary" id="wpvivid_rescan_local_folder_btn" value="Scan uploaded backup or received backup">
            </div>
            <br class="clear">
        </div>
        <div class="wpvivid-local-remote-backup-list wpvivid-element-space-bottom" id="wpvivid_backup_list">
            <?php
            $backup_list=new WPvivid_New_BackupList();
            $all_backups=$backup_list->get_all_backup();
            $table=new WPvivid_New_Backup_List();
            $table->set_backup_list($all_backups);
            $table->prepare_items();
            $table->display();
            ?>
        </div>

        <div>
            <input class="button-primary" id="wpvivid-delete-localhost-array" type="submit" value="Delete the selected backups">
        </div>

        <script>
            jQuery("#wpvivid_select_backup_storage").change(function()
            {
                if(jQuery(this).val()=='cloud')
                {
                    jQuery("#wpvivid_select_remote_storage_box").show();
                    jQuery("#wpvivid_migrated_option").show();
                    jQuery("#wpvivid_uploaded_option").hide();

                    var backup_folder = jQuery('#wpvivid_select_backup_folder').val();
                    if(backup_folder=='uploaded')
                    {
                        jQuery('#wpvivid_select_backup_folder').val("all_backup").change();
                    }
                }
                else if(jQuery(this).val()=='localhost')
                {
                    jQuery("#wpvivid_select_remote_storage_box").hide();
                    jQuery("#wpvivid_migrated_option").hide();
                    jQuery("#wpvivid_uploaded_option").show();

                    var backup_folder = jQuery('#wpvivid_select_backup_folder').val();
                    if(backup_folder=='migrate')
                    {
                        jQuery('#wpvivid_select_backup_folder').val("all_backup").change();
                    }
                }
                else
                {
                    jQuery("#wpvivid_select_remote_storage_box").hide();
                    jQuery("#wpvivid_migrated_option").show();
                    jQuery("#wpvivid_uploaded_option").show();
                }
            });

            jQuery('#wpvivid_backup_list').on('click', '.wpvivid-lock', function()
            {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                if(Obj.hasClass('dashicons-lock'))
                {
                    var lock=0;
                }
                else
                {
                    var lock=1;
                }
                var ajax_data= {
                    'action': 'wpvivid_set_security_lock_ex',
                    'backup_id': backup_id,
                    'lock': lock
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(lock)
                            {
                                Obj.removeClass('dashicons-unlock');
                                Obj.addClass('dashicons-lock');
                            }
                            else
                            {
                                Obj.removeClass('dashicons-lock');
                                Obj.addClass('dashicons-unlock');
                            }
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            function wpvivid_backup_open_log(log)
            {
                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&log='; ?>'+log;
            }

            //wpvivid_get_scan_backup
            jQuery('#wpvivid_get_scan_backup').click(function()
            {
                var backup_storage = jQuery('#wpvivid_select_backup_storage').val();
                var remote_storage = jQuery('#wpvivid_select_remote_storage').val();
                if((backup_storage=='cloud')&&(remote_storage!='all_backup'))
                {
                    wpvivid_scan_remote_backup();
                }
                else
                {
                    wpvivid_get_backup_list();
                }

            });

            jQuery('#wpvivid_backup_list').on("click",'.first-page',function()
            {
                wpvivid_get_backup_list('first');
            });

            jQuery('#wpvivid_backup_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_backup_list(page-1);
            });

            jQuery('#wpvivid_backup_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_backup_list(page+1);
            });

            jQuery('#wpvivid_backup_list').on("click",'.last-page',function()
            {
                wpvivid_get_backup_list('last');
            });

            jQuery('#wpvivid_backup_list').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    wpvivid_get_backup_list(page);
                }
            });

            jQuery('#wpvivid_backup_list').on('click', 'thead tr td input', function()
            {
                wpvivid_control_backup_select(jQuery(this));
            });

            jQuery('#wpvivid_backup_list').on('click', 'tfoot tr td input', function()
            {
                wpvivid_control_backup_select(jQuery(this));
            });

            jQuery('#wpvivid_backup_list').on("click",'.backuplist-delete-backup',function()
            {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                var page =jQuery('#wpvivid_backup_list').find('.current-page').val();
                var backup_storage = jQuery('#wpvivid_select_backup_storage').val();
                var backup_folder = jQuery('#wpvivid_select_backup_folder').val();
                var remote_storage = jQuery('#wpvivid_select_remote_storage').val();

                var descript = '<?php _e('Are you sure to remove this backup? This backup will be deleted permanently.', 'wpvivid'); ?>';

                var ret = confirm(descript);
                if(ret === true)
                {
                    var ajax_data = {
                        'action': 'wpvivid_delete_backup_ex',
                        'backup_id': backup_id,
                        'backup_storage': backup_storage,
                        'backup_folder': backup_folder,
                        'remote_storage': remote_storage,
                        'page':page
                    };
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#wpvivid_backup_list').html(jsonarray.html);
                            }
                            else if(jsonarray.result === 'failed')
                            {
                                alert(jsonarray.error);
                            }
                        }
                        catch(err){
                            alert(err);
                        }

                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#wpvivid-delete-localhost-array').click(function() {
                var delete_backup_array = new Array();
                var count = 0;

                var page =jQuery('#wpvivid_backup_list').find('.current-page').val();
                var backup_storage = jQuery('#wpvivid_select_backup_storage').val();
                var backup_folder = jQuery('#wpvivid_select_backup_folder').val();
                var remote_storage = jQuery('#wpvivid_select_remote_storage').val();

                jQuery('#wpvivid_backup_list .wpvivid-backup-row input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        delete_backup_array[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });
                if( count === 0 )
                {
                    alert('<?php _e('Please select at least one item.','wpvivid'); ?>');
                }
                else
                {
                    var descript = '<?php _e('Are you sure to remove the selected backups? These backups will be deleted permanently.', 'wpvivid'); ?>';

                    var ret = confirm(descript);
                    if (ret === true)
                    {
                        var ajax_data = {
                            'action': 'wpvivid_delete_backup_array_ex',
                            'backup_id': delete_backup_array,
                            'backup_storage': backup_storage,
                            'backup_folder': backup_folder,
                            'remote_storage': remote_storage,
                            'page':page
                        };
                        jQuery('#wpvivid-delete-localhost-array').css({'pointer-events': 'none', 'opacity': '0.4'});
                        wpvivid_post_request_addon(ajax_data, function (data)
                        {
                            jQuery('#wpvivid-delete-localhost-array').css({'pointer-events': 'auto', 'opacity': '1'});
                            try
                            {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success')
                                {
                                    if(typeof jsonarray.continue !== 'undefined' && jsonarray.continue)
                                    {
                                        wpvivid_delete_backup_array_ex(delete_backup_array,backup_storage,backup_folder,remote_storage,page);
                                    }
                                    else
                                    {
                                        jQuery('#wpvivid_backup_list').html(jsonarray.html);
                                    }
                                }
                                else if(jsonarray.result === 'failed')
                                {
                                    alert(jsonarray.error);
                                }
                            }
                            catch(err){
                                alert(err);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            jQuery('#wpvivid-delete-localhost-array').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            });

            function wpvivid_delete_backup_array_ex(delete_backup_array,backup_storage,backup_folder,remote_storage,page)
            {
                var ajax_data = {
                    'action': 'wpvivid_delete_backup_array_ex',
                    'backup_id': delete_backup_array,
                    'backup_storage': backup_storage,
                    'backup_folder': backup_folder,
                    'remote_storage': remote_storage,
                    'page':page
                };
                jQuery('#wpvivid-delete-localhost-array').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid-delete-localhost-array').css({'pointer-events': 'auto', 'opacity': '1'});
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(typeof jsonarray.continue !== 'undefined' && jsonarray.continue)
                            {
                                wpvivid_delete_backup_array_ex(delete_backup_array,backup_storage,backup_folder,remote_storage,page);
                            }
                            else
                            {
                                jQuery('#wpvivid_backup_list').html(jsonarray.html);
                            }
                        }
                        else if(jsonarray.result === 'failed')
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid-delete-localhost-array').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_get_backup_list(page=0)
            {
                if(page==0)
                {
                    page =jQuery('#wpvivid_backup_list').find('.current-page').val();
                }

                //
                var backup_storage = jQuery('#wpvivid_select_backup_storage').val();
                var backup_folder = jQuery('#wpvivid_select_backup_folder').val();
                var remote_storage = jQuery('#wpvivid_select_remote_storage').val();

                jQuery('#wpvivid_backup_scanning').show();
                var ajax_data = {
                    'action': 'wpvivid_get_backup_list',
                    'backup_storage': backup_storage,
                    'backup_folder': backup_folder,
                    'remote_storage': remote_storage,
                    'page':page
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_backup_scanning').hide();
                    jQuery('#wpvivid_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_backup_list').html(jsonarray.html);
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
                    jQuery('#wpvivid_backup_scanning').hide();
                    var error_message = wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_scan_remote_backup()
            {
                jQuery('#wpvivid_backup_scanning').show();

                jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'none', 'opacity': '0.4'});

                var remote_storage = jQuery('#wpvivid_select_remote_storage').val();
                var backup_folder = jQuery('#wpvivid_select_backup_folder').val();

                var ajax_data = {
                    'action': 'wpvivid_scan_remote_backup',
                    'backup_folder': backup_folder,
                    'remote_storage': remote_storage
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_backup_list').html(jsonarray.html);
                            if(jsonarray.finished==false)
                            {
                                wpvivid_scan_remote_backup_continue();
                            }
                            else
                            {
                                jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_backup_scanning').hide();
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_backup_scanning').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_backup_scanning').hide();
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_backup_scanning').hide();
                    var error_message = wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_scan_remote_backup_continue()
            {
                var ajax_data = {
                    'action': 'wpvivid_scan_remote_backup_continue'
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_backup_list').html(jsonarray.html);
                            if(jsonarray.finished==false)
                            {
                                wpvivid_scan_remote_backup_continue();
                            }
                            else
                            {
                                jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_backup_scanning').hide();
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_backup_scanning').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_backup_scanning').hide();
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_backup_scanning').hide();
                    var error_message = wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_control_backup_select(obj)
            {
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('#wpvivid_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_backup_list tbody tr').each(function()
                    {
                        jQuery(this).children('th').each(function (j)
                        {
                            if (j == 0)
                            {
                                if(jQuery(this).parent().children('td').eq(0).find('.backuptime').find('span').eq(0).hasClass('dashicons-unlock'))
                                {
                                    jQuery(this).closest('tr').find('th input').prop('checked', true);
                                }
                                else
                                {
                                    jQuery(this).closest('tr').find('th input').prop('checked', false);
                                }
                            }
                        });
                    });
                }
                else
                {
                    jQuery('#wpvivid_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_backup_list tbody tr').each(function ()
                    {
                        jQuery(this).children('th').each(function (j)
                        {
                            if (j == 0)
                            {
                                jQuery(this).find("input[type=checkbox]").prop('checked', false);
                            }
                        });
                    });
                }
            }

            jQuery('#wpvivid_rescan_local_folder_btn').click(function()
            {
                wpvivid_rescan_local_folder();
            });

            function wpvivid_rescan_local_folder()
            {
                var ajax_data = {
                    'action': 'wpvivid_addon_rescan_local_folder'
                };
                jQuery('#wpvivid_rescan_local_folder_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_scanning_local_folder').addClass('is-active');
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_scanning_local_folder').removeClass('is-active');
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(typeof jsonarray.incomplete_backup !== 'undefined' && jsonarray.incomplete_backup.length > 0)
                        {
                            var incomplete_count = jsonarray.incomplete_backup.length;
                            alert('Failed to scan '+incomplete_count+' backup zips, the zips can be corrupted during creation or download process. Please check the zips.');
                        }
                        jQuery( document ).trigger( 'wpvivid_update_local_upload_backup');
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_scanning_local_folder').removeClass('is-active');
                    var error_message = wpvivid_output_ajaxerror('scanning backup list', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery(document).ready(function($)
            {
                jQuery(document).on('wpvivid_update_local_backup', function(event)
                {
                    jQuery('#wpvivid_select_backup_storage').val('localhost');
                    jQuery('#wpvivid_select_backup_folder').val('Upload');
                    wpvivid_get_backup_list();
                });

                jQuery(document).on('wpvivid_update_local_upload_backup', function(event)
                {
                    jQuery('#wpvivid_select_backup_storage').val('localhost');
                    jQuery('#wpvivid_select_backup_folder').val('Upload');
                    wpvivid_get_backup_list();
                });

                jQuery(document).on('wpvivid_update_local_all_backup', function(event)
                {
                    jQuery('#wpvivid_select_backup_storage').val('localhost');
                    jQuery('#wpvivid_select_backup_folder').val('Upload');
                    wpvivid_get_backup_list();
                });

                <?php
                if(isset($_REQUEST['localhost_backuplist']))
                {
                ?>
                jQuery('#wpvivid_select_backup_storage').val('localhost');
                jQuery('#wpvivid_select_backup_folder').val('Upload');
                wpvivid_get_backup_list();
                <?php
                }
                ?>

                <?php
                if(isset($_REQUEST['remote_backuplist']))
                {
                ?>
                jQuery('#wpvivid_select_backup_storage').val('cloud');
                jQuery('#wpvivid_select_backup_folder').val('all_backup');
                wpvivid_get_backup_list();
                <?php
                }
                ?>

                <?php
                if(isset($_REQUEST['localhost_allbackuplist']))
                {
                ?>
                jQuery('#wpvivid_select_backup_storage').val('localhost');
                jQuery('#wpvivid_select_backup_folder').val('Upload');
                wpvivid_get_backup_list();
                <?php
                }
                ?>
            });
        </script>
        <?php

    }

    public function archieve_remote_array($remote_array){
        $remoteslist=WPvivid_Setting::get_all_remote_options();
        foreach ($remoteslist as $key => $value){
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
            $remote_array[$key]['path'] = $path;
        }
        return $remote_array;
    }

    public function wpvivid_check_remove_restore_database($check_is_remove, $option){
        if(isset($option['remove_additional_database']) && !empty($option['remove_additional_database'])){
            foreach ($option['remove_additional_database'] as $database_name => $status){
                if($option['database'] === $database_name){
                    $check_is_remove = true;
                }
            }
        }
        return $check_is_remove;
    }

    public function get_backup_data_by_task($backup_data,$task){
        $prefix='';
        if(isset($backup_data['backup']['files']))
        {
            foreach ($backup_data['backup']['files'] as $file)
            {
                if(preg_match('#^.*_wpvivid-#',$file['file_name'],$matches))
                {
                    $prefix=$matches[0];
                    $prefix=substr($prefix,0,strlen($prefix)-strlen('_wpvivid-'));
                    break;
                }
            }
        }

        $backup_data['backup_prefix']=$prefix;
        return $backup_data;
    }
    /***** backup and restore filters end *****/

    /***** backup and restore actions begin *****/
    public function update_backup_item($id,$key,$data){
        $list = WPvivid_Setting::get_option('wpvivid_backup_list');
        if(array_key_exists($id,$list))
        {
            $list[$id][$key]=$data;
            WPvivid_Setting::update_option('wpvivid_backup_list',$list);
        }

        $list = WPvivid_Setting::get_option('wpvivid_staging_list');
        if(array_key_exists($id,$list))
        {
            $list[$id][$key]=$data;
            WPvivid_Setting::update_option('wpvivid_staging_list',$list);
        }

        $list = WPvivid_Setting::get_option('wpvivid_migrate_list');
        if(array_key_exists($id,$list))
        {
            $list[$id][$key]=$data;
            WPvivid_Setting::update_option('wpvivid_migrate_list',$list);
        }
    }
    /***** backup and restore actions end *****/

    /***** backup and restore userfule function begin *****/
    public function wpvivid_tran_backup_time_to_local($value)
    {
        $backup_time=$value['create_time'];
        if(isset($value['backup']['files'])){
            foreach ($value['backup']['files'] as $file_info){
                if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file_info['file_name'],$matches))
                {
                    $backup_date=$matches[0];
                }
                else
                {
                    $backup_date=$value['create_time'];
                }

                $time_array=explode('-',$backup_date);
                if(sizeof($time_array)>4){
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    $backup_time=strtotime($time);
                }
                break;
            }
        }
        return $backup_time;
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

    public function get_backup_url($backup_item, $file_name)
    {
        $path = $backup_item->get_local_path() . $file_name;

        if (file_exists($path)) {
            return $backup_item->get_local_url() . $file_name;
        }
        else{
            $local_setting = get_option('wpvivid_local_setting', array());
            if(!empty($local_setting))
            {
                $url = content_url().DIRECTORY_SEPARATOR.$local_setting['path'].DIRECTORY_SEPARATOR.$file_name;
            }
            else {
                $url = content_url().DIRECTORY_SEPARATOR.'wpvividbackups'.DIRECTORY_SEPARATOR.$file_name;
            }
        }
        return $url;
    }

    public function _get_backup_list($backup_storage,$backup_folder,$remote_storage)
    {
        $backup_list=new WPvivid_New_BackupList();
        if($backup_storage=='all_backups')
        {
            if($backup_folder=='all_backup')
            {
                $backups=$backup_list->get_all_backup();
            }
            else
            {
                $backups=$backup_list->get_all_backup_ex($backup_folder);
            }
        }
        else if($backup_storage=='localhost')
        {
            if($backup_folder=='all_backup')
            {
                $backups=$backup_list->get_local_backup();
            }
            else
            {
                $backups=$backup_list->get_local_backup_ex($backup_folder);
            }
        }
        else if($backup_storage=='cloud')
        {
            if($remote_storage=='all_backup')
            {
                if($backup_folder=='all_backup')
                {
                    $backups=$backup_list->get_all_remote_backup();
                }
                else
                {
                    $backups=$backup_list->get_all_remote_backup($backup_folder);
                }
            }
            else
            {
                if($backup_folder=='all_backup')
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_storage);
                }
                else
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_storage,$backup_folder);
                }
            }
        }
        else
        {
            $backups=array();
        }
        return $backups;
    }

    public function _achieve_remote_backups($remote_id,$remote_folder='',$incremental_path='')
    {
        set_time_limit(120);
        $remoteslist = WPvivid_Setting::get_all_remote_options();
        $remote_option = $remoteslist[$remote_id];
        $remote_folder= 'Common';

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote = $remote_collection->get_remote($remote_option);

        if (!method_exists($remote, 'scan_folder_backup'))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            echo json_encode($ret);
            die();
        }

        if($remote_folder === 'Incremental')
        {
            $remote_folder = 'Common';
        }

        if(!empty($incremental_path))
        {
            $ret = $remote->scan_child_folder_backup($incremental_path);
        }
        else
        {
            $ret = $remote->scan_folder_backup($remote_folder);
        }

        if ($ret['result'] == WPVIVID_PRO_SUCCESS)
        {
            $this->_rescan_remote_folder_set_backup($remote_id, $ret);
        }
    }

    public function _rescan_remote_folder_set_backup($remote_id,$backups)
    {
        $remote_ids[]=$remote_id;
        $remote_options=WPvivid_Setting::get_remote_options($remote_ids);

        $remote_options_migrate=array();
        $remote_options_rollback=array();
        foreach ($remote_options as $option)
        {
            $og_path=$option['path'];
            if(isset($option['custom_path']))
            {
                $og_custom_path=$option['custom_path'];
            }
            else
            {
                $og_custom_path='';
            }

            if(isset($option['custom_path']))
            {
                $option['custom_path']='migrate';
                $remote_options_migrate[]=$option;
            }
            else
            {
                $option['path']='migrate';
                $remote_options_migrate[]=$option;
            }

            if(isset($option['custom_path']))
            {
                $option['custom_path']=$og_custom_path.'/rollback';
                $option['path']= $og_path;
                $remote_options_rollback[]=$option;
            }
            else
            {
                $option['path']= $og_path.'/rollback';
                $remote_options_rollback[]=$option;
            }
        }

        $list=array();
        if(!empty($backups['remote']))
        {
            foreach ($backups['remote'] as $id=>$backup)
            {
                $backup_data=array();
                $backup_data['type']='Common';
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_data['create_time']=strtotime($time);
                $backup_data['manual_delete']=0;
                $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                $backup_data['compress']['compress_type']='zip';
                $backup_data['save_local']=0;
                $backup_data['backup_prefix']=$backup['backup_prefix'];

                global $wpvivid_backup_pro;
                if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                {

                }
                else
                {
                    $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                }

                $temp['result']='success';
                $temp['files']=array();
                foreach ($backup['files'] as $file)
                {
                    $add_file=array();
                    $add_file['file_name']=$file['file_name'];
                    $add_file['size']=$file['size'];
                    if(isset($file['remote_path']))
                    {
                        $add_file['remote_path']=$file['remote_path'];
                    }
                    $temp['files'][]=$add_file;
                }
                $backup_data['backup']=$temp;
                $backup_data['remote']=$remote_options;
                $backup_data['lock']=0;
                $list[$id]=$backup_data;
            }
        }

        if(!empty($backups['migrate']))
        {
            foreach ($backups['migrate'] as $id=>$backup)
            {
                $backup_data=array();
                $backup_data['type']='Migrate';
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_data['create_time']=strtotime($time);
                $backup_data['manual_delete']=0;
                $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                $backup_data['compress']['compress_type']='zip';
                $backup_data['save_local']=0;
                $backup_data['backup_prefix']=$backup['backup_prefix'];

                global $wpvivid_backup_pro;
                if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                {

                }
                else
                {
                    $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                }

                $temp['result']='success';
                $temp['files']=array();
                foreach ($backup['files'] as $file)
                {
                    $add_file=array();
                    $add_file['file_name']=$file['file_name'];
                    $add_file['size']=$file['size'];
                    $temp['files'][]=$add_file;
                }

                $backup_data['backup']=$temp;
                $backup_data['remote']=$remote_options_migrate;
                $backup_data['lock']=0;
                $list[$id]=$backup_data;
            }
        }

        if(!empty($backups['rollback']))
        {
            foreach ($backups['rollback'] as $id=>$backup)
            {
                $backup_data=array();
                $backup_data['type']='Rollback';
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_data['create_time']=strtotime($time);
                $backup_data['manual_delete']=0;
                $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                $backup_data['compress']['compress_type']='zip';
                $backup_data['save_local']=0;
                $backup_data['backup_prefix']=$backup['backup_prefix'];

                global $wpvivid_backup_pro;
                if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                {

                }
                else
                {
                    $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                }



                $temp['result']='success';
                $temp['files']=array();
                foreach ($backup['files'] as $file)
                {
                    $add_file=array();
                    $add_file['file_name']=$file['file_name'];
                    $add_file['size']=$file['size'];
                    $temp['files'][]=$add_file;
                }

                $backup_data['backup']=$temp;
                $backup_data['remote']=$remote_options_rollback;
                $backup_data['lock']=0;
                $list[$id]=$backup_data;
            }
        }
        $remote_list = get_option('wpvivid_new_remote_list',array());
        $remote_list[$remote_id]=$list;
        update_option('wpvivid_new_remote_list',$remote_list,'no');
    }

    public function get_backup_list_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            $backup_storage = isset($_POST['backup_storage'])?$_POST['backup_storage']:'all_backups';
            $backup_folder = isset($_POST['backup_folder'])?$_POST['backup_folder']:'all_backup';
            $remote_storage = isset($_POST['remote_storage'])?$_POST['remote_storage']:'all_backup';

            $html='';
            $backups=$this->_get_backup_list($backup_storage,$backup_folder,$remote_storage);

            $table=new WPvivid_New_Backup_List();
            if(isset($_POST['page']))
            {
                $table->set_backup_list($backups,$_POST['page']);
            }
            else
            {
                $table->set_backup_list($backups);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
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

    public function scan_remote_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            $backup_folder = isset($_POST['backup_folder'])?$_POST['backup_folder']:'all_backup';
            $remote_storage = isset($_POST['remote_storage'])?$_POST['remote_storage']:'all_backup';

            $ret=$this->_scan_remote_backup($remote_storage,$backup_folder);
            if($ret['result']=='success')
            {
                $backups=$ret['backups'];
                $table=new WPvivid_New_Backup_List();
                $table->set_backup_list($backups);

                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();
                $ret['html']=$html;
            }
            else
            {
                $ret['html']='';
            }

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

    public function scan_remote_backup_continue()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            $ret=$this->_scan_remote_backup_continue();
            if($ret['result']=='success')
            {
                $backups=$ret['backups'];
                $table=new WPvivid_New_Backup_List();
                $table->set_backup_list($backups);

                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();
                $ret['html']=$html;
            }
            else
            {
                $ret['html']='';
            }

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

    public function _scan_remote_backup($remote_id,$backup_folder)
    {
        set_time_limit(120);
        $remoteslist = WPvivid_Setting::get_all_remote_options();
        $remote_option = $remoteslist[$remote_id];

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote = $remote_collection->get_remote($remote_option);

        if (!method_exists($remote, 'scan_folder_backup_ex'))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        if($backup_folder=='all_backup')
        {
            $remote_list = get_option('wpvivid_new_remote_list',array());
            $remote_list[$remote_id]=array();
            update_option('wpvivid_new_remote_list',$remote_list,'no');
        }
        else
        {
            $remote_list = get_option('wpvivid_new_remote_list',array());
            if(isset($remote_list[$remote_id]) && !empty($remote_list[$remote_id]))
            {
                foreach ($remote_list[$remote_id] as $backup_id => $remote_backup)
                {
                    if(isset($remote_backup['type']) && $remote_backup['type'] === $backup_folder)
                    {
                        unset($remote_list[$remote_id][$backup_id]);
                    }
                }
            }
            update_option('wpvivid_new_remote_list',$remote_list,'no');
        }

        $ret = $remote->scan_folder_backup_ex($backup_folder);

        if ($ret['result'] == 'success')
        {
            $remote_ids[]=$remote_id;
            $remote_options=WPvivid_Setting::get_remote_options($remote_ids);

            $remote_options_migrate=array();
            $remote_options_rollback=array();
            foreach ($remote_options as $option)
            {
                $og_path=$option['path'];
                if(isset($option['custom_path']))
                {
                    $og_custom_path=$option['custom_path'];
                }
                else
                {
                    $og_custom_path='';
                }

                if(isset($option['custom_path']))
                {
                    $option['custom_path']='migrate';
                    $remote_options_migrate[]=$option;
                }
                else
                {
                    $option['path']='migrate';
                    $remote_options_migrate[]=$option;
                }

                if(isset($option['custom_path']))
                {
                    $option['custom_path']=$og_custom_path.'/rollback';
                    $option['path']= $og_path;
                    $remote_options_rollback[]=$option;
                }
                else
                {
                    $option['path']= $og_path.'/rollback';
                    $remote_options_rollback[]=$option;
                }
            }

            $task_list=array();
            if(!empty($ret['remote']))
            {
                foreach ($ret['remote'] as $id=>$backup)
                {
                    $backup_data=array();
                    $backup_data['type']='Manual';
                    $time_array=explode('-',$backup['date']);
                    if(sizeof($time_array)>4)
                        $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    else
                        $time=$backup['date'];
                    $backup_data['create_time']=strtotime($time);
                    $backup_data['manual_delete']=0;
                    $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                    $backup_data['compress']['compress_type']='zip';
                    $backup_data['save_local']=0;
                    $backup_data['backup_prefix']=$backup['backup_prefix'];

                    global $wpvivid_backup_pro;
                    if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                    {

                    }
                    else
                    {
                        $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                    }

                    $temp['result']='success';
                    $temp['files']=array();
                    foreach ($backup['files'] as $file)
                    {
                        $add_file=array();
                        $add_file['file_name']=$file['file_name'];
                        $add_file['size']=$file['size'];
                        if(isset($file['remote_path']))
                        {
                            $add_file['remote_path']=$file['remote_path'];
                        }
                        $temp['files'][]=$add_file;
                    }
                    $backup_data['backup']=$temp;
                    $backup_data['remote']=$remote_options;
                    $backup_data['lock']=0;
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }

            if(!empty($ret['migrate']))
            {
                foreach ($ret['migrate'] as $id=>$backup)
                {
                    $backup_data=array();
                    $backup_data['type']='Migrate';
                    $time_array=explode('-',$backup['date']);
                    if(sizeof($time_array)>4)
                        $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    else
                        $time=$backup['date'];
                    $backup_data['create_time']=strtotime($time);
                    $backup_data['manual_delete']=0;
                    $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                    $backup_data['compress']['compress_type']='zip';
                    $backup_data['save_local']=0;
                    $backup_data['backup_prefix']=$backup['backup_prefix'];

                    global $wpvivid_backup_pro;
                    if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                    {

                    }
                    else
                    {
                        $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                    }

                    $temp['result']='success';
                    $temp['files']=array();
                    foreach ($backup['files'] as $file)
                    {
                        $add_file=array();
                        $add_file['file_name']=$file['file_name'];
                        $add_file['size']=$file['size'];
                        $temp['files'][]=$add_file;
                    }

                    $backup_data['backup']=$temp;
                    $backup_data['remote']=$remote_options_migrate;
                    $backup_data['lock']=0;
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }

            if(!empty($ret['rollback']))
            {
                foreach ($ret['rollback'] as $id=>$backup)
                {
                    $backup_data=array();
                    $backup_data['type']='Rollback';
                    $time_array=explode('-',$backup['date']);
                    if(sizeof($time_array)>4)
                        $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    else
                        $time=$backup['date'];
                    $backup_data['create_time']=strtotime($time);
                    $backup_data['manual_delete']=0;
                    $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                    $backup_data['compress']['compress_type']='zip';
                    $backup_data['save_local']=0;
                    $backup_data['backup_prefix']=$backup['backup_prefix'];

                    global $wpvivid_backup_pro;
                    if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                    {

                    }
                    else
                    {
                        $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                    }

                    $temp['result']='success';
                    $temp['files']=array();
                    foreach ($backup['files'] as $file)
                    {
                        $add_file=array();
                        $add_file['file_name']=$file['file_name'];
                        $add_file['size']=$file['size'];
                        $temp['files'][]=$add_file;
                    }

                    $backup_data['backup']=$temp;
                    $backup_data['remote']=$remote_options_rollback;
                    $backup_data['lock']=0;
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }

            if(!empty($ret['incremental']))
            {
                foreach ($ret['incremental'] as $id=>$backup)
                {
                    $backup_data=array();
                    $backup_data['type']='Incremental';
                    $time_array=explode('-',$backup['date']);
                    if(sizeof($time_array)>4)
                        $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    else
                        $time=$backup['date'];
                    $backup_data['create_time']=strtotime($time);
                    $backup_data['manual_delete']=0;
                    $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                    $backup_data['compress']['compress_type']='zip';
                    $backup_data['save_local']=0;
                    $backup_data['backup_prefix']=$backup['backup_prefix'];

                    global $wpvivid_backup_pro;
                    if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                    {

                    }
                    else
                    {
                        $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                    }

                    $temp['result']='success';
                    $temp['files']=array();
                    foreach ($backup['files'] as $file)
                    {
                        $add_file=array();
                        $add_file['file_name']=$file['file_name'];
                        $add_file['size']=$file['size'];
                        $temp['files'][]=$add_file;
                    }

                    $backup_data['backup']=$temp;
                    $backup_data['remote']=$remote_options;
                    $backup_data['incremental_path']=$backup['incremental_path'];
                    $backup_data['lock']=0;
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }
            $scan_task_data['list']=$task_list;
            $scan_task_data['remote_id']=$remote_id;
            $scan_task_data['backup_folder']=$backup_folder;
            update_option('wpvivid_scan_remote_task',$scan_task_data,'no');

            $ret['result']='success';
            $ret['backups']=array();
            $ret['finished']=false;
            $ret['test']=$scan_task_data;
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function _scan_remote_backup_continue()
    {
        $scan_task_data=get_option('wpvivid_scan_remote_task');
        $remote_id=$scan_task_data['remote_id'];
        $backup_folder=$scan_task_data['backup_folder'];
        $remoteslist = WPvivid_Setting::get_all_remote_options();
        $remote_option = $remoteslist[$remote_id];

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote = $remote_collection->get_remote($remote_option);

        if (!method_exists($remote, 'get_backup_info'))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        $time_limit = 21;
        $start_time = time();

        foreach ($scan_task_data['list'] as $backup_id =>$backup_data)
        {
            if(isset($backup_data['backup_info']))
            {
                continue;
            }

            if(empty($backup_data['backup_info_file']))
            {
                $backup_data['backup_info']=array();
            }
            else
            {
                if($backup_data['type']=='Incremental')
                {
                    $ret=$remote->get_backup_info($backup_data['backup_info_file'],$backup_data['type'],$backup_data['incremental_path']);
                }
                else
                {
                    $ret=$remote->get_backup_info($backup_data['backup_info_file'],$backup_data['type']);
                }

                if($ret['result']=='success')
                {
                    $backup_data['backup_info']=$ret['backup_info'];
                    $backup_data['type']=$ret['backup_info']['type'];
                }
                else
                {
                    $backup_data['backup_info']=array();
                }
            }

            $scan_task_data['list'][$backup_id]=$backup_data;
            update_option('wpvivid_scan_remote_task',$scan_task_data,'no');

            $remote_list = get_option('wpvivid_new_remote_list',array());
            $remote_list[$remote_id][$backup_id]=$backup_data;
            update_option('wpvivid_new_remote_list',$remote_list,'no');

            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                $backup_list=new WPvivid_New_BackupList();
                if($backup_folder=='all_backup')
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_id);
                }
                else
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_id,$backup_folder);
                }

                $ret['result']='success';
                $ret['backups']=$backups;
                $ret['finished']=false;
                return $ret;
            }
        }

        $backup_list=new WPvivid_New_BackupList();
        if($backup_folder=='all_backup')
        {
            $backups=$backup_list->get_remote_backup_ex($remote_id);
        }
        else
        {
            $backups=$backup_list->get_remote_backup_ex($remote_id,$backup_folder);
        }

        $ret['result']='success';
        $ret['backups']=$backups;
        $ret['finished']=true;
        return $ret;
    }

    public function set_security_lock_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) && isset($_POST['lock']))
            {
                $backup_id = sanitize_key($_POST['backup_id']);
                if ($_POST['lock'] == 0 || $_POST['lock'] == 1)
                {
                    $lock = $_POST['lock'];
                } else {
                    $lock = 0;
                }

                $backup_list=new WPvivid_New_BackupList();
                $backup_data = $backup_list->get_local_backup($backup_id);

                if($backup_data !== false)
                {
                    if ($lock == 1)
                    {
                        $backup_data['lock'] = 1;
                    }
                    else
                    {
                        $backup_data['lock'] = 0;
                    }
                    $backup_list->add_local_backup($backup_id,$backup_data);
                }
                else
                {
                    $backup_data=$backup_list->get_remote_backup_by_id($backup_id);
                    if($backup_data !== false)
                    {
                        $backup_lock=get_option('wpvivid_remote_backups_lock',array());
                        if($lock)
                        {
                            $backup_lock[$backup_id]=1;
                        }
                        else
                        {
                            unset($backup_lock[$backup_id]);
                        }
                        update_option('wpvivid_remote_backups_lock',$backup_lock,'no');
                    }
                    else
                    {
                        $ret['result']='failed';
                        $ret['error']='backup not found';
                        echo json_encode($ret);
                        die();
                    }
                }

                $ret['result'] = 'success';
                echo json_encode($ret);
            }
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

    public function delete_backup_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (isset($_POST['backup_id']) && !empty($_POST['backup_id']))
            {
                $backup_id = sanitize_key($_POST['backup_id']);
                $backup_list=new WPvivid_New_BackupList();

                $remote_storage = isset($_POST['remote_storage'])?$_POST['remote_storage']:'all_backup';

                $backup=$backup_list->get_local_backup($backup_id);
                if($backup===false)
                {
                    if($remote_storage!='all_backup')
                    {
                        $backup=$backup_list->get_remote_backup($remote_storage,$backup_id);
                    }
                    else
                    {
                        $backup=$backup_list->get_remote_backup_by_id($backup_id);
                    }

                    if($backup===false)
                    {
                        $ret['result']='failed';
                        $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid');
                        echo json_encode($ret);
                        die();
                    }
                }
                $backup_item=new WPvivid_New_Backup_Item($backup);
                $files=$backup_item->get_files();
                foreach ($files as $file)
                {
                    if (file_exists($file))
                    {
                        @unlink($file);
                    }
                }
                $files=array();
                if(isset($backup['backup']['files']))
                {
                    //file_name
                    foreach ($backup['backup']['files'] as $file)
                    {
                        if($backup['type']=='Incremental')
                        {
                            if(isset($backup['incremental_path']))
                            {
                                $file['remote_path']=$backup['incremental_path'];
                            }
                        }

                        $files[]=$file;
                        if (file_exists($this->get_backup_path($backup_item, $file['file_name'])))
                        {
                            @unlink($this->get_backup_path($backup_item, $file['file_name']));
                        }
                    }
                }
                else
                {
                    $files=$backup_item->get_files();
                    foreach ($files as $file)
                    {
                        if (file_exists($file))
                        {
                            @unlink($file);
                        }
                    }
                }

                if($remote_storage!='all_backup')
                {
                    $backup_list->delete_backup($backup_id,$remote_storage);
                }
                else
                {
                    $backup_list->delete_backup($backup_id);
                }

                if(!empty($backup['remote']))
                {
                    if(isset($backup['backup_info_file'])&&!empty($backup['backup_info_file']))
                    {
                        if($backup['type']=='Incremental')
                        {
                            if(isset($backup['incremental_path']))
                            {
                                $file['remote_path']=$backup['incremental_path'];
                            }
                        }
                        $file['file_name']=$backup['backup_info_file'];
                        $files[]=$file;
                    }

                    foreach($backup['remote'] as $remote)
                    {
                        WPvivid_downloader::delete($remote,$files);
                    }
                }


                $backup_storage = isset($_POST['backup_storage'])?$_POST['backup_storage']:'all_backups';
                $backup_folder = isset($_POST['backup_folder'])?$_POST['backup_folder']:'all_backup';

                $backups=$this->_get_backup_list($backup_storage,$backup_folder,$remote_storage);

                $table=new WPvivid_New_Backup_List();
                if(isset($_POST['page']))
                {
                    $table->set_backup_list($backups,$_POST['page']);
                }
                else
                {
                    $table->set_backup_list($backups);
                }
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();

                $ret['result']='success';
                $ret['html']=$html;

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

    public function delete_backup_array_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_array($_POST['backup_id']))
            {
                $remote_storage = isset($_POST['remote_storage'])?$_POST['remote_storage']:'all_backup';

                $backup_ids = $_POST['backup_id'];
                $backup_list=new WPvivid_New_BackupList();
                $ret = array();

                $start_time=time();
                $time_limit = 21;

                foreach ($backup_ids as $backup_id)
                {
                    $backup=$backup_list->get_local_backup($backup_id);
                    if($backup===false)
                    {
                        if($remote_storage!='all_backup')
                        {
                            $backup=$backup_list->get_remote_backup($remote_storage,$backup_id);
                        }
                        else
                        {
                            $backup=$backup_list->get_remote_backup_by_id($backup_id);
                        }

                        if($backup===false)
                        {
                            continue;
                        }
                    }
                    $backup_item=new WPvivid_New_Backup_Item($backup);
                    $files=$backup_item->get_files();
                    foreach ($files as $file)
                    {
                        if (file_exists($file))
                        {
                            @unlink($file);
                        }
                    }
                    $files=array();
                    if(isset($backup['backup']['files']))
                    {
                        //file_name
                        foreach ($backup['backup']['files'] as $file)
                        {
                            if($backup['type']=='Incremental')
                            {
                                if(isset($backup['incremental_path']))
                                {
                                    $file['remote_path']=$backup['incremental_path'];
                                }
                            }
                            $files[]=$file;

                            if (file_exists($this->get_backup_path($backup_item, $file['file_name'])))
                            {
                                @unlink($this->get_backup_path($backup_item, $file['file_name']));
                            }
                        }
                    }
                    else
                    {
                        $files=$backup_item->get_files();
                        foreach ($files as $file)
                        {
                            if (file_exists($file))
                            {
                                @unlink($file);
                            }
                        }
                    }

                    if($remote_storage!='all_backup')
                    {
                        $backup_list->delete_backup($backup_id,$remote_storage);
                    }
                    else
                    {
                        $backup_list->delete_backup($backup_id);
                    }


                    if(!empty($backup['remote']))
                    {
                        if(isset($backup['backup_info_file'])&&!empty($backup['backup_info_file']))
                        {
                            if($backup['type']=='Incremental')
                            {
                                if(isset($backup['incremental_path']))
                                {
                                    $file['remote_path']=$backup['incremental_path'];
                                }
                            }
                            $file['file_name']=$backup['backup_info_file'];
                            $files[]=$file;
                        }

                        foreach($backup['remote'] as $remote)
                        {
                            WPvivid_downloader::delete($remote,$files);
                        }
                    }

                    $time_taken = microtime(true) - $start_time;
                    if($time_taken >= $time_limit)
                    {
                        $ret['result']='success';
                        $ret['continue']=1;
                        echo json_encode($ret);
                        die();
                    }
                }


                $backup_storage = isset($_POST['backup_storage'])?$_POST['backup_storage']:'all_backups';
                $backup_folder = isset($_POST['backup_folder'])?$_POST['backup_folder']:'all_backup';

                $backups=$this->_get_backup_list($backup_storage,$backup_folder,$remote_storage);

                $table=new WPvivid_New_Backup_List();
                if(isset($_POST['page']))
                {
                    $table->set_backup_list($backups,$_POST['page']);
                }
                else
                {
                    $table->set_backup_list($backups);
                }
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();

                $ret['result']='success';
                $ret['html']=$html;
                $ret['continue']=0;

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

    public function download_backup_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (isset($_REQUEST['backup_id']) && isset($_REQUEST['file_name']))
            {
                $backup_id = sanitize_key($_REQUEST['backup_id']);
                $file_name = $_REQUEST['file_name'];
                $backup_list=new WPvivid_New_BackupList();
                $backup = $backup_list->get_backup_by_id($backup_id);
                if($backup===false)
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']='backup id not found';
                    echo json_encode($ret);
                    die();
                }

                $backup_item=new WPvivid_New_Backup_Item($backup);
                //$path=$backup_item->get_local_path().$file_name;
                $path = $this->get_backup_path($backup_item, $file_name);
                if ($path !== false)
                {
                    if (file_exists($path))
                    {
                        if (session_id())
                            session_write_close();

                        $size = filesize($path);
                        if (!headers_sent())
                        {
                            header('Content-Description: File Transfer');
                            header('Content-Type: application/zip');
                            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                            header('Cache-Control: must-revalidate');
                            header('Content-Length: ' . $size);
                            header('Content-Transfer-Encoding: binary');
                        }

                        @ini_set( 'memory_limit', '1024M' );

                        if (ob_get_level() == 0) {
                            ob_start();
                        }

                        while (ob_get_level() > 0) {
                            ob_end_clean();
                        }

                        if ($size < 1024 * 1024 * 60) {
                            readfile($path);
                            exit;
                        } else {
                            $download_rate = 1024 * 10;
                            $file = fopen($path, "r");
                            while (!feof($file)) {
                                @set_time_limit(20);
                                // send the current file part to the browser
                                print fread($file, round($download_rate * 1024));
                                // flush the content to the browser
                                if (ob_get_level() > 0) {
                                    ob_flush();
                                }
                                flush();
                                // sleep one second
                                sleep(1);
                            }
                            fclose($file);
                            exit;
                        }
                    }
                }
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        echo __('file not found. please <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG).'">retry</a> again.');
        die();
    }

    public function download_all_backup_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (isset($_REQUEST['backup_id']))
            {
                $backup_id = sanitize_key($_REQUEST['backup_id']);
                $backup_list=new WPvivid_New_BackupList();
                $backup = $backup_list->get_backup_by_id($backup_id);
                if($backup===false)
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']='backup id not found';
                    echo json_encode($ret);
                    die();
                }

                @set_time_limit(300);
                $backup_file_array=array();
                foreach($backup['backup']['files'] as $files)
                {
                    $backup_file_array[]=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$files['file_name'];
                }

                $zip_file_name=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.'wpvivid-all-backups.zip';

                if(file_exists($zip_file_name))
                    @unlink($zip_file_name);

                $use_temp_size=16;
                $replace_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
                if (!class_exists('WPvivid_PclZip'))
                    include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';
                if (!class_exists('WPvivid_PclZip'))
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']='Class PclZip is not detected. Please update or reinstall your WordPress.';
                    echo json_encode($ret);
                    die();
                }
                $archive = new WPvivid_PclZip($zip_file_name);
                $ret = $archive -> add($backup_file_array,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,$use_temp_size);
                if(!$ret)
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']=$archive->errorInfo(true);
                    echo json_encode($ret);
                    die();
                }
                $size=filesize($zip_file_name);
                if($size===false)
                {
                    $size=size_format(disk_free_space(dirname($zip_file_name)),2);
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']='The file compression failed while backing up becuase of '.$zip_file_name.' file not found. Please try again. The available disk space: '.$size.'.';
                    echo json_encode($ret);
                    die();
                }
                else if($size==0)
                {
                    $size=size_format(disk_free_space(dirname($zip_file_name)),2);
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']='The file compression failed while backing up. The size of '.$zip_file_name.' file is 0. Please make sure there is an enough disk space to backup. Then try again. The available disk space: '.$size.'.';
                    echo json_encode($ret);
                    die();
                }

                $path=$zip_file_name;
                if (file_exists($path))
                {
                    if (session_id())
                        session_write_close();

                    $size = filesize($path);
                    if (!headers_sent())
                    {
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                        header('Cache-Control: must-revalidate');
                        header('Content-Length: ' . $size);
                        header('Content-Transfer-Encoding: binary');
                    }

                    $memory_limit = @ini_get('memory_limit');
                    $unit = strtoupper(substr($memory_limit, -1));
                    if ($unit == 'K')
                    {
                        $memory_limit_tmp = intval($memory_limit) * 1024;
                    }
                    else if ($unit == 'M')
                    {
                        $memory_limit_tmp = intval($memory_limit) * 1024 * 1024;
                    }
                    else if ($unit == 'G')
                    {
                        $memory_limit_tmp = intval($memory_limit) * 1024 * 1024 * 1024;
                    }
                    else{
                        $memory_limit_tmp = intval($memory_limit);
                    }
                    if ($memory_limit_tmp < 256 * 1024 * 1024)
                    {
                        @ini_set('memory_limit', '256M');
                    }

                    if ($size < 1024 * 1024 * 60) {
                        ob_end_clean();
                        readfile($path);
                        exit;
                    } else {
                        ob_end_clean();
                        $download_rate = 1024 * 10;
                        $file = fopen($path, "r");
                        while (!feof($file)) {
                            @set_time_limit(20);
                            // send the current file part to the browser
                            print fread($file, round($download_rate * 1024));
                            // flush the content to the browser
                            ob_flush();
                            flush();
                            // sleep one second
                            sleep(1);
                        }
                        fclose($file);
                        exit;
                    }
                }
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        echo __('file not found. please <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG).'">retry</a> again.');
        die();
    }

    function wpvivid_function_per_add_callback($p_event, &$p_header)
    {
        if(!file_exists($p_header['filename'])){
            return 0;
        }
        return 1;
    }

    /***** backup and restore ajax end *****/

    public function output_upload_backup()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom:1em;">
            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
            <span>Backup Directory:</span><span><code><?php _e(WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath()); ?></code></span>
            <span> | </span>
            <span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>">rename</a></span>
        </div>
        <div style="clear: both;"></div>
        <div style="display: block;" id="wpvivid_backup_uploader">
            <?php
            Wpvivid_BackupUploader_addon::upload_meta_box();
            ?>
        </div>
        <?php
    }

    public function output_backup_log_list()
    {
        $log_list = new WPvivid_Log_addon();
        $log_list->output_backup_restore_log_list();
        ?>
        <?php
        ?>
        <script>
            var wpvivid_backup_restore_type = '';
            var wpvivid_backup_restore_result = '';
            jQuery('#wpvivid_backup_log_list').on('click', '#wpvivid_search_backup_restore_log_btn', function(){
                wpvivid_backup_restore_type=jQuery('#wpvivid_backup_restore_log_type').val();
                wpvivid_backup_restore_result=jQuery('#wpvivid_backup_restore_log_result').val();

                if(wpvivid_backup_restore_type=='0')
                {
                    wpvivid_backup_restore_type='';
                }

                if(wpvivid_backup_restore_result=='0')
                {
                    wpvivid_backup_restore_result='';
                }
                wpvivid_log_change_page('first','backup&restore&transfer','wpvivid_backup_log_list');
            });

            function wpvivid_open_log(log,slug) {
                var ajax_data = {
                    'action':'wpvivid_view_log_ex',
                    'log': log
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    jQuery('#wpvivid_read_log_content').html("");
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success")
                        {
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'open_log', slug ]);
                            var log_data = jsonarray.data;
                            while (log_data.indexOf('\n') >= 0)
                            {
                                var iLength = log_data.indexOf('\n');
                                var log = log_data.substring(0, iLength);
                                log_data = log_data.substring(iLength + 1);
                                var insert_log = "<div style=\"clear:both;\">" + log + "</div>";
                                jQuery('#wpvivid_read_log_content').append(insert_log);
                            }
                        }
                        else
                        {
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'open_log', slug ]);
                            jQuery('#wpvivid_read_log_content').html(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        var div = "Reading the log failed. Please try again.";
                        jQuery('#wpvivid_read_log_content').html(div);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_download_log(log) {
                location.href =ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_log&log='+log;
            }

            function wpvivid_log_change_page(page,type,log_contenter) {
                var ajax_data = {
                    'action':'wpvivid_get_log_list_page',
                    'page': page,
                    'type': type,
                    'backup_restore_type': wpvivid_backup_restore_type,
                    'backup_restore_result': wpvivid_backup_restore_result
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#'+log_contenter).html(jsonarray.rows);
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                    if(wpvivid_backup_restore_type == ''){
                        wpvivid_backup_restore_type = '0';
                    }
                    if(wpvivid_backup_restore_result == ''){
                        wpvivid_backup_restore_result = '0';
                    }
                    jQuery('#wpvivid_backup_restore_log_type').val(wpvivid_backup_restore_type);
                    jQuery('#wpvivid_backup_restore_log_result').val(wpvivid_backup_restore_result);
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    if(wpvivid_backup_restore_type == ''){
                        wpvivid_backup_restore_type = '0';
                    }
                    if(wpvivid_backup_restore_result == ''){
                        wpvivid_backup_restore_result = '0';
                    }
                    jQuery('#wpvivid_backup_restore_log_type').val(wpvivid_backup_restore_type);
                    jQuery('#wpvivid_backup_restore_log_result').val(wpvivid_backup_restore_result);
                    var error_message = wpvivid_output_ajaxerror('changing log page', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function output_view_detail()
    {
        ?>
        <div style="margin-top: 10px;">
            <div id="wpvivid_init_content_info">
                <div style="float: left; height: 20px; line-height: 20px; margin-top: 4px;">Reading the backup contents</div>
                <div class="spinner" style="float: left;"></div>
                <div style="clear: both;"></div>
            </div>
            <div class="wpvivid-element-space-bottom" id="wpvivid_backup_content_list"></div>
        </div>
        <script>
            jQuery('#wpvivid_backup_list').on("click",'.wpvivid-backup-content',function() {
                var Obj=jQuery(this);
                var type_string = Obj.attr('type-string');
                wpvivid_show_backup_content_page(type_string, 'localhost_backuplist');
            });

            jQuery('#wpvivid_remote_backups_list').on("click",'.wpvivid-backup-content',function() {
                var Obj=jQuery(this);
                var type_string = Obj.attr('type-string');
                wpvivid_show_backup_content_page(type_string, 'remote_backuplist');
            });

            function wpvivid_show_backup_content_page(type_string, list_from) {
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'view_detail', list_from ]);
                var type_array = type_string.split(",");
                var ajax_data = {
                    'action': 'wpvivid_backup_content_display',
                    'backup_content_list': type_array
                };

                jQuery('#wpvivid_backup_content_list').html('');
                jQuery('#wpvivid_init_content_info').show();
                jQuery('#wpvivid_init_content_info').find('.spinner').addClass('is-active');
                var retry = '<input type="button" class="button button-primary" value="Read Again" onclick="wpvivid_show_backup_content_page(\''+type_string+'\', \''+list_from+'\');" />';
                wpvivid_post_request_addon(ajax_data, function (data) {
                    jQuery('#wpvivid_init_content_info').hide();
                    jQuery('#wpvivid_init_content_info').find('.spinner').removeClass('is-active');
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#wpvivid_backup_content_list').html(jsonarray.html);
                        }
                        else {
                            alert(jsonarray.error);
                            jQuery('#wpvivid_backup_content_list').html(retry);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#wpvivid_backup_content_list').html(retry);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_init_content_info').hide();
                    jQuery('#wpvivid_init_content_info').find('.spinner').removeClass('is-active');
                    var error_message = wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#wpvivid_backup_content_list').html(retry);
                });
            }
        </script>
        <?php
    }

    public function output_open_log()
    {
        ?>
        <div class="postbox restore_log" id="wpvivid_read_log_content" style="word-break: break-all;word-wrap: break-word;"></div>
        <?php
    }

    public function output_restore_backup()
    {
        ?>
        <script>
            jQuery('#wpvivid_backup_list').on("click",'.wpvivid-restore',function()
            {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                location.href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&restore=1&backup_id='; ?>"+backup_id;
            });

            jQuery('#wpvivid_remote_backups_list').on("click",'.wpvivid-restore',function() {

                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                location.href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&restore=1&backup_id='; ?>"+backup_id;
            });
        </script>
        <?php
    }
}

class WPvivid_New_BackupList
{
    public $backup_list;

    public function __construct()
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());
    }

    public function update_backup($backup_id,$key,$data)
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());

        if(isset($this->backup_list['local'][$backup_id]))
        {
            $this->backup_list['local'][$backup_id][$key]=$data;
            update_option('wpvivid_backup_list',$this->backup_list['local'],'no');
            return;
        }

        if(empty($this->backup_list['remote']))
            return;

        foreach($this->backup_list['remote'] as $remote_id=>$list)
        {
            if(isset($list[$backup_id]))
            {
                $this->backup_list['remote'][$remote_id][$backup_id][$key]=$data;
                update_option('wpvivid_new_remote_list',$this->backup_list['remote'],'no');
                return;
            }
        }
    }

    public function add_local_backup($backup_id,$backup_data)
    {
        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());
        $this->backup_list['local'][$backup_id]=$backup_data;
        update_option('wpvivid_backup_list',$this->backup_list['local'],'no');
    }

    public function add_remote_backup($remote_id,$backup_id,$backup_data)
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());
        $this->backup_list['remote'][$remote_id][$backup_id]=$backup_data;
        update_option('wpvivid_new_remote_list',$this->backup_list['remote'],'no');
    }

    public function remove_remote_option($remote_id)
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());
        unset($this->backup_list['remote'][$remote_id]);
        update_option('wpvivid_new_remote_list',$this->backup_list['remote'],'no');
    }

    public function get_local_backup($backup_id='')
    {
        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());

        if(empty($backup_id))
        {
            $list=array();

            foreach ($this->backup_list['local'] as $backup_id=>$backup_data)
            {
                $backup_data['id']=$backup_id;
                $list[$backup_id]=$backup_data;
            }
            return $this->soft_list($list);
        }
        else
        {
            if(isset($this->backup_list['local'][$backup_id]))
            {
                return $this->backup_list['local'][$backup_id];
            }
            else
            {
                return false;
            }
        }
    }

    public function get_remote_backup($remote_id,$backup_id='')
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        if(isset($this->backup_list['remote'][$remote_id]))
        {
            if(empty($backup_id))
            {
                $list=array();

                foreach ($this->backup_list['remote'][$remote_id] as $backup_id =>$backup_data)
                {
                    $backup_data['id']=$backup_id;
                    $list[$backup_id]=$backup_data;
                }

                return $this->soft_list($list);
            }
            else
            {
                if(isset($this->backup_list['remote'][$remote_id][$backup_id]))
                {
                    return $this->backup_list['remote'][$remote_id][$backup_id];
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            return false;
        }
    }

    public function get_local_backup_ex($type)
    {
        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());

        if(empty($this->backup_list['local']))
        {
            return array();
        }
        else
        {
            $list=array();

            foreach ($this->backup_list['local'] as $backup_id=>$backup_data)
            {
                if($backup_data['type']==$type)
                {
                    $backup_data['id']=$backup_id;
                    $list[$backup_id]=$backup_data;
                }
            }

            return $this->soft_list($list);
        }
    }

    public function get_remote_backup_ex($remote_id,$type='')
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        if(isset($this->backup_list['remote'][$remote_id]))
        {
            $list=array();

            foreach ($this->backup_list['remote'][$remote_id] as $backup_id=>$backup_data)
            {
                if(!empty($type))
                {
                    if($backup_data['type']==$type)
                    {
                        $backup_data['id']=$backup_id;
                        $list[$backup_id]=$backup_data;
                    }
                }
                else
                {
                    $backup_data['id']=$backup_id;
                    $list[$backup_id]=$backup_data;
                }
            }

            return $this->soft_list($list);
        }
        else
        {
            return false;
        }
    }

    public function get_all_remote_backup($type='')
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        $list=array();

        if(!empty($this->backup_list['remote']))
        {
            foreach ($this->backup_list['remote'] as $remote_id=>$remote_list)
            {
                foreach ($this->backup_list['remote'][$remote_id] as $backup_id =>$backup_data)
                {
                    if(!empty($type))
                    {
                        if($backup_data['type']==$type)
                        {
                            $backup_data['id']=$backup_id;
                            if(!isset($list[$backup_id]))
                            {
                                $list[$backup_id]=$backup_data;
                            }
                        }
                    }
                    else
                    {
                        $backup_data['id']=$backup_id;
                        if(!isset($list[$backup_id]))
                        {
                            $list[$backup_id]=$backup_data;
                        }
                    }
                }
            }
        }

        return $this->soft_list($list);
    }

    public function get_backup_by_id($backup_id)
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());

        if(isset($this->backup_list['local'][$backup_id]))
        {
            return $this->backup_list['local'][$backup_id];
        }

        if(empty($this->backup_list['remote']))
            return false;

        foreach($this->backup_list['remote'] as $remote_id=>$list)
        {
            if(isset($list[$backup_id]))
            {
                return $list[$backup_id];
            }
        }

        return false;
    }

    public function get_remote_backup_by_id($backup_id)
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        if(empty($this->backup_list['remote']))
            return false;

        foreach($this->backup_list['remote'] as $remote_id=>$list)
        {
            if(isset($list[$backup_id]))
            {
                return $list[$backup_id];
            }
        }

        return false;
    }

    public function soft_list($list)
    {
        usort($list, function ($a, $b)
        {
            if ($a['create_time'] == $b['create_time'])
            {
                return 0;
            }

            if($a['create_time'] > $b['create_time'])
            {
                return -1;
            }
            else
            {
                return 1;
            }
        });
        return $list;
    }

    public function get_all_backup()
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());
        $list=array();

        if(!empty($this->backup_list['remote']))
        {
            foreach ($this->backup_list['remote'] as $remote_id=>$remote_list)
            {
                foreach ($this->backup_list['remote'][$remote_id] as $backup_id =>$backup_data)
                {
                    $backup_data['id']=$backup_id;
                    if(!isset($list[$backup_id]))
                    {
                        $list[$backup_id]=$backup_data;
                    }
                }
            }
        }

        $remove_list=array();

        foreach ($this->backup_list['local'] as $backup_id=>$backup_data)
        {
            $backup_data['id']=$backup_id;
            if(isset($list[$backup_id]))
            {
                $remove_list[$backup_id]=$backup_id;
            }
            else
            {
                if($this->check_backup_exist($backup_data))
                {
                    $list[$backup_id]=$backup_data;
                }
                else
                {
                    $remove_list[$backup_id]=$backup_id;
                }
            }
        }

        if(!empty($remove_list))
        {
            foreach ($remove_list as $backup_id)
            {
                unset($this->backup_list['local'][$backup_id]);
            }
            update_option('wpvivid_backup_list',$this->backup_list['local'],'no');
        }


        return $this->soft_list($list);
    }

    public function get_all_backup_ex($type)
    {
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());

        $list=array();
        if(!empty($this->backup_list['remote']))
        {
            foreach ($this->backup_list['remote'] as $remote_id=>$remote_list)
            {
                foreach ($this->backup_list['remote'][$remote_id] as $backup_id =>$backup_data)
                {
                    if($backup_data['type']==$type)
                    {
                        $backup_data['id']=$backup_id;
                        if(!isset($list[$backup_id]))
                        {
                            $list[$backup_id]=$backup_data;
                        }
                    }
                }
            }
        }

        $remove_list=array();

        foreach ($this->backup_list['local'] as $backup_id=>$backup_data)
        {
            if($backup_data['type']==$type)
            {
                $backup_data['id']=$backup_id;
                if(isset($list[$backup_id]))
                {
                    $remove_list[$backup_id]=$backup_id;
                }
                else
                {
                    $list[$backup_id]=$backup_data;
                }
            }
        }

        if(!empty($remove_list))
        {
            foreach ($remove_list as $backup_id)
            {
                unset($this->backup_list['local'][$backup_id]);
            }
            update_option('wpvivid_backup_list',$this->backup_list['local'],'no');
        }

        return $this->soft_list($list);
    }

    public function delete_backup_log($key)
    {
        $wpvivid_common_setting = get_option('wpvivid_common_setting');
        if(isset($wpvivid_common_setting['auto_delete_backup_log']) && $wpvivid_common_setting['auto_delete_backup_log'])
        {
            $log_dir=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.'wpvivid_log';
            $handler=opendir($log_dir);
            if($handler===false)
                return;
            while(($filename=readdir($handler))!==false)
            {
                if($filename != "." && $filename != "..")
                {
                    if(is_dir($log_dir.DIRECTORY_SEPARATOR.$filename))
                    {
                    }
                    else
                    {
                        if(preg_match('/'.$key.'/', $filename))
                        {
                            if(file_exists($log_dir.DIRECTORY_SEPARATOR.$filename))
                                @unlink($log_dir.DIRECTORY_SEPARATOR.$filename);
                        }
                    }
                }
            }
            if($handler)
                @closedir($handler);
        }
    }

    public function delete_backup($key,$remote_id='')
    {
        $this->backup_list['local'] = get_option('wpvivid_backup_list',array());
        $this->backup_list['remote'] = get_option('wpvivid_new_remote_list',array());

        foreach ($this->backup_list['local'] as $k=>$backup)
        {
            if ($key == $k)
            {
                unset($this->backup_list['local'][$key]);
                update_option('wpvivid_backup_list',$this->backup_list['local'],'no');
                $this->delete_backup_log($key);
                return;
            }
        }

        if(empty($remote_id))
        {
            foreach ($this->backup_list['remote'] as $remote_id=>$remote_list)
            {
                foreach ($this->backup_list['remote'][$remote_id] as $k =>$backup)
                {
                    if ($key == $k)
                    {
                        unset($this->backup_list['remote'][$remote_id][$key]);
                        update_option('wpvivid_new_remote_list',$this->backup_list['remote'],'no');
                        $this->delete_backup_log($key);
                        return;
                    }
                }
            }
        }
        else
        {
            if(isset($this->backup_list['remote'][$remote_id]))
            {
                foreach ($this->backup_list['remote'][$remote_id] as $k =>$backup)
                {
                    if ($key == $k)
                    {
                        unset($this->backup_list['remote'][$remote_id][$key]);
                        update_option('wpvivid_new_remote_list',$this->backup_list['remote'],'no');
                        $this->delete_backup_log($key);
                        return;
                    }
                }
            }
        }

    }

    public function check_list($list)
    {
        foreach ($list as $backup_id=>$backup_data)
        {
            if($this->check_list_item($backup_data)===false)
            {
                unset($list[$backup_id]);
            }
        }
        return $list;
    }

    public function check_list_item($backup_data)
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup_data['local']['path']);

        foreach ($backup_data['backup']['files'] as $file)
        {
            if($this->check_is_a_wpvivid_backup($path.$file['file_name']) === false)
            {
                return false;
            }
        }

        return true;
    }

    public function check_backup_exist($backup_data)
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $all_exists=false;

        foreach ($backup_data['backup']['files'] as $file)
        {
            if(file_exists($path.$file['file_name']))
            {
                $all_exists=true;
            }
        }

        return $all_exists;
    }

    private function check_is_a_wpvivid_backup($file_name)
    {
        $ret=WPvivid_New_Backup_Item::get_backup_file_info($file_name);
        if($ret['result'] === WPVIVID_PRO_SUCCESS)
        {
            return true;
        }
        else {
            return false;
        }
    }
}