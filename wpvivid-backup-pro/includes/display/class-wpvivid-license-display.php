<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Pro_License
{
    public function __construct()
    {
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);

        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        add_action('wp_ajax_wpvivid_dashboard_login',array( $this,'dashboard_login'));
        add_action('wp_ajax_wpvivid_dashboard_login_direct',array($this, 'dashboard_login_direct'));
        add_action('wp_ajax_wpvivid_dashboard_active',array( $this,'dashboard_active'));
        add_action('wp_ajax_wpvivid_check_update_plugin',array( $this,'check_update_plugin'));
        add_action('wp_ajax_wpvivid_update_dashboard',array( $this,'update_dashboard'));
        add_action('wp_ajax_wpvivid_dashboard_remove_plugin',array( $this,'remove_plugin'));
        //
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_pro_page');
        if($display)
        {
            if(apply_filters('wpvivid_show_dashboard_addons',true))
            {
                $submenu['parent_slug'] = $parent_slug;
                $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Dashboard');
                $submenu['menu_title'] = 'License';
                $submenu['capability'] = 'administrator';
                $submenu['menu_slug'] = strtolower(sprintf('%s-license', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
                $submenu['index'] = 21;
                $submenu['function'] = array($this, 'init_page');
                $submenus[$submenu['menu_slug']] = $submenu;
            }
        }
        return $submenus;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-license';
        if(is_multisite())
        {
            $screen['screen_id']='wpvivid-plugin_page_wpvivid-license-network';
        }
        else
        {
            $screen['screen_id']='wpvivid-plugin_page_wpvivid-license';
        }

        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function init_page()
    {
        $first_install=get_option('wpvivid_plugins_first_install',false);

        if($first_install===false||$first_install=='step1')
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

            if($user_info!==false)
            {
                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard');
                $url.='&first=1';
                update_option('wpvivid_plugins_first_install','step2','no');
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
        }

        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - License', 'wpvivid' ); ?></h1>
            <div id="wpvivid_pro_notice">
            </div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <?php $this->welcome_bar();?>
                                <div class="wpvivid-nav-bar wpvivid-clear-float">
                                    <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                                    <span> Tip: You can use either a father license or a child license to activate <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> plugins.</span>
                                </div>
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <div class="wpvivid-one-coloum">
                                        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                                            <?php $this->status_bar();?>
                                            <?php $this->user_bar();?>
                                        </div>
                                        <div style="clear: both;"></div>
                                        <?php $this->plugin_list();?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- sidebar -->
                    <?php $this->sidebar(); ?>
                    <!-- #postbox-container-1 .postbox-container -->
                </div>
            </div>
        </div>
        <script>
            function wpvivid_display_pro_notice(notice_type, notice_message)
            {
                if(notice_type === 'Success')
                {
                    var div = "<div class='notice notice-success is-dismissible inline'><p>" + notice_message + "</p>" +
                        "<button type='button' class='notice-dismiss' onclick='click_dismiss_pro_notice(this);'>" +
                        "<span class='screen-reader-text'>Dismiss this notice.</span>" +
                        "</button>" +
                        "</div>";
                }
                else{
                    var div = "<div class=\"notice notice-error inline\"><p>Error: " + notice_message + "</p></div>";
                }
                jQuery('#wpvivid_pro_notice').show();
                jQuery('#wpvivid_pro_notice').html(div);
            }
            function wpvivid_dashboard_output_ajaxerror(action, textStatus, errorThrown)
            {
                action = 'trying to establish communication with your server';
                var error_msg = "wpvivid_request: "+ textStatus + "(" + errorThrown + "): an error occurred when " + action + ". " +
                    "This error may be request not reaching or server not responding. Please try again later.";
                //"This error could be caused by an unstable internet connection. Please try again later.";
                return error_msg;
            }
        </script>
        <?php
    }

    public function welcome_bar()
    {
        ?>
        <div class="wpvivid-welcome-bar wpvivid-clear-float">
            <div class="wpvivid-welcome-bar-left">
                <p><span class="dashicons dashicons-admin-network wpvivid-dashicons-large wpvivid-dashicons-green"></span><span class="wpvivid-page-title">License</span></p>
                <span class="about-description">This tab allows you to activate <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> pro license and get plugin updates and support.</span>
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
        </div>
        <?php
    }

    public function status_bar()
    {
        if(isset($_REQUEST['auto_update']))
        {
            if($_REQUEST['auto_update']==1)
            {
                update_option('wpvivid_dashboard_auto_update','on','no');
            }
            else if($_REQUEST['auto_update']==0)
            {
                update_option('wpvivid_dashboard_auto_update','off','no');
            }
        }

        $current_version=WPVIVID_BACKUP_PRO_VERSION;

        $auto_update =get_option('wpvivid_dashboard_auto_update', false);

        if($auto_update === false||$auto_update=='off')
        {
            $auto_update_class = 'wpvivid-green';
            $auto_update_text = 'Turn On';
            $auto_update_status = 'Disabled';
        }
        else{
            $auto_update_class = 'wpvivid-grey';
            $auto_update_text = 'Turn Off';
            $auto_update_status = 'Enabled';
        }

        if($auto_update=='on')
        {
            $auto_update_switch_url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-license', 'wpvivid-license').'&auto_update=0';
        }
        else
        {
            $auto_update_switch_url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-license', 'wpvivid-license').'&auto_update=1';
        }

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

        if(empty($dashboard_info))
        {
            $active_status='N/A';
            $version_compare='';
        }
        else
        {
            if(isset($dashboard_info['check_active'])&&$dashboard_info['check_active'])
            {
                $active_status='Active';
            }
            else
            {
                $active_status='Inactive';
            }

            $version_compare = ' (Latest Version)';

            if(isset($dashboard_info['dashboard']))
            {
                if(version_compare(WPVIVID_BACKUP_PRO_VERSION, $dashboard_info['dashboard']['version'], '<'))
                {
                    $version_compare = '(Latest Version Available: '.$dashboard_info['dashboard']['version'].')';
                }
            }
        }

        ?>
        <div class="wpvivid-two-col">
            <p>
                <span class="dashicons dashicons-awards wpvivid-dashicons-blue"></span>
                <span>Current Version: </span><span><?php echo $current_version; ?></span>
                <span><?php echo $version_compare; ?></span>
            </p>
            <p>
                <span class="dashicons dashicons-update-alt wpvivid-dashicons-blue"></span>
                <span>Automatic Updates: </span>
                <span id="auto_update_status"><?php _e($auto_update_status); ?></span>
                <span class="wpvivid-rectangle <?php esc_attr_e($auto_update_class); ?>" id="wpvivid_auto_update_switch" title="Click here to disable automatic updates of WPvivid Plugin" style="cursor:pointer;">
                                                        <?php _e($auto_update_text); ?>
                                                    </span>
            </p>
            <p>
                <span class="dashicons dashicons-yes-alt wpvivid-dashicons-blue"></span>
                <span>Status: </span>
                <span><?php echo $active_status; ?></span>
            </p>
        </div>
        <script>
            jQuery('#wpvivid_auto_update_switch').click(function()
            {
                location.href='<?php echo $auto_update_switch_url;?>';
            });
        </script>
        <?php
    }

    public function user_bar()
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
        <div class="wpvivid-two-col" style="padding-right:1em;">
            <?php $this->sign_out_bar();?>
            <?php
            if($user_info===false)
            {
                $this->login_form();
            }
            else
            {
                $this->logged();
            }
            ?>
        </div>
        <?php
    }

    public function sign_out_bar()
    {
        if(isset($_REQUEST['sign_out']))
        {
            delete_option('wpvivid_pro_user');
            delete_option('wpvivid_plugin_install_cache');
            delete_option('wpvivid_dashboard_info');
            $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-license', 'wpvivid-license');
            ?>
            <script>
                location.href='<?php echo $url;?>';
            </script>
            <?php
        }
        $white_label_setting=get_option('white_label_setting',array());
        if(empty($white_label_setting))
        {
            $white_label_website_protocol='https';
            $white_label_website='wpvivid.com/my-account';
        }
        else
        {
            $white_label_website_protocol = empty($white_label_setting['white_label_website_protocol']) ? 'https' : $white_label_setting['white_label_website_protocol'];
            $white_label_website = empty($white_label_setting['white_label_website']) ? 'wpvivid.com/my-account' : $white_label_setting['white_label_website'];
        }
        $signout_url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-license', 'wpvivid-license').'&sign_out=1';
        ?>
        <span class="dashicons dashicons-businessman wpvivid-dashicons-green"></span>
        <span><a href="<?php echo esc_html($white_label_website_protocol); ?>://<?php echo esc_html($white_label_website); ?>" target="_blank">My Account</a></span>
        <span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span>
        <span><a href="#" id="wpvivid_dashboard_signout">Sign Out</a></span>
        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
            <div class="wpvivid-bottom">
                <!-- The content you need -->
                <p>Sign out or switch to another account. Once signed out, you will need to re-enter the credentials to get WPvivid Pro authorization.</p>
                <i></i> <!-- do not delete this line -->
            </div>
        </span>
        <script>
            jQuery('#wpvivid_dashboard_signout').click(function()
            {
                var descript = 'Are you sure you want to sign out?';
                var ret = confirm(descript);
                if(ret === true)
                {
                    location.href='<?php echo $signout_url;?>';
                }
            });
        </script>
        <?php
    }

    public function sidebar()
    {
        do_action( 'wpvivid_add_sidebar_dashboard' );
    }

    public function login_form()
    {
        ?>
        <form action="">
            <div style="margin-top: 10px; margin-bottom: 15px;">
                <input type="password" class="regular-text" id="wpvivid_account_license" placeholder="License" autocomplete="new-password" required="">
            </div>
            <div style="margin-bottom: 10px; float: left; margin-left: 0; margin-right: 10px;">
                <input class="button-primary" id="wpvivid_active_btn" type="button" value="Activate">
            </div>
            <div style="clear:both;"></div>
            <div id="wpvivid_login_box_progress" style="display: none">
                <p>
                    <span class="dashicons dashicons-admin-network wpvivid-dashicons-green"></span>
                    <span id="wpvivid_log_progress_text"></span>
                </p>
            </div>
            <div id="wpvivid_login_error_msg_box" style="display: none">
                <p>
                    <span class="dashicons dashicons-info wpvivid-dashicons-grey"></span>
                    <span id="wpvivid_login_error_msg"></span>
                </p>
            </div>
            <div style="clear: both;"></div>
        </form>
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

    public function logged()
    {
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

        if(empty($dashboard_info))
        {
            $info = get_option('wpvivid_pro_user', false);
            ?>
            <p><input id="wpvivid_check_update_plugin" type="button" class="button-primary ud_connectsubmit" value="Check Update"></p>
            <div id="wpvivid_user_info_box_progress" style="display: none">
                <p>
                    <span class="dashicons dashicons-admin-network wpvivid-dashicons-green"></span>
                    <span id="wpvivid_user_info_log_progress_text"></span>
                </p>
            </div>
            <div id="wpvivid_user_info_error_msg_box" style="display: none">
                <p>
                    <span class="dashicons dashicons-info wpvivid-dashicons-grey"></span>
                    <span id="wpvivid_user_info_error_msg"></span>
                </p>
            </div>
            <div style="clear: both;"></div>
            <script>
                jQuery('#wpvivid_check_update_plugin').click(function()
                {
                    wpvivid_check_update_plugin();
                });

                function wpvivid_check_update_plugin($slug)
                {
                    var ajax_data={
                        'action':'wpvivid_check_update_plugin',
                    };
                    jQuery('#wpvivid_check_update_plugin').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_user_info_log_progress_text').html('Checking Update...');
                    jQuery('#wpvivid_user_info_box_progress').show();
                    jQuery('#wpvivid_user_info_error_msg_box').hide();
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        jQuery('#wpvivid_check_update_plugin').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_user_info_box_progress').hide();


                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            location.reload();
                        }
                        else
                        {
                            jQuery('#wpvivid_user_info_error_msg_box').show();
                            jQuery('#wpvivid_user_info_error_msg').html(jsonarray.error);
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        jQuery('#wpvivid_check_update_plugin').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_user_info_box_progress').hide();
                        jQuery('#wpvivid_user_info_log_progress_text').html('');

                        jQuery('#wpvivid_change_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                        var error_message = wpvivid_dashboard_output_ajaxerror('check update', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                jQuery(document).ready(function ()
                {
                    wpvivid_check_update_plugin();
                });
            </script>
            <?php
        }
        else
        {
            if(isset($_REQUEST['check_update']))
            {
                $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard');
                $url.='&first=1';
                ?>
                <script>
                    location.href='<?php echo $url;?>';
                </script>
                <?php
            }

            $need_update=false;
            if(isset($dashboard_info['dashboard']))
            {
                if(version_compare($dashboard_info['dashboard']['version'],WPVIVID_BACKUP_PRO_VERSION, '>'))
                {
                    $need_update=true;
                }
            }

            if($need_update)
            {
                $plugin_basename= plugin_basename( WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'wpvivid-backup-pro.php' );
                //$url=wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $plugin_basename, 'upgrade-plugin_' . $plugin_basename);
                $url=apply_filters('wpvivid_get_admin_url', '').'plugins.php?s=wpvivid&plugin_status=all';
                ?>
                <p>
                    <a href="<?php echo $url; ?>">
                        <input type="button" class="button-primary ud_connectsubmit" value="update">
                    </a>
                </p>
                <div id="wpvivid_user_info_box_progress" style="display: none">
                    <p>
                        <span class="dashicons dashicons-admin-network wpvivid-dashicons-green"></span>
                        <span id="wpvivid_user_info_log_progress_text"></span>
                    </p>
                </div>
                <div id="wpvivid_user_info_error_msg_box" style="display: none">
                    <p>
                        <span class="dashicons dashicons-info wpvivid-dashicons-grey"></span>
                        <span id="wpvivid_user_info_error_msg"></span>
                    </p>
                </div>
                <div style="clear: both;"></div>
                <script>
                    jQuery('#wpvivid_dashboard_update').click(function()
                    {
                        wpvivid_dashboard_update();
                    });

                    function wpvivid_dashboard_update()
                    {
                        var ajax_data={
                            'action':'wpvivid_update_dashboard',
                        };
                        jQuery('#wpvivid_dashboard_update').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#wpvivid_user_info_log_progress_text').html('Checking Update...');
                        jQuery('#wpvivid_user_info_box_progress').show();
                        jQuery('#wpvivid_user_info_error_msg_box').hide();
                        wpvivid_post_request_addon(ajax_data, function(data)
                        {
                            jQuery('#wpvivid_dashboard_update').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_user_info_box_progress').hide();
                            jQuery('#wpvivid_user_info_log_progress_text').html('');

                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                location.href=jsonarray.url;
                            }
                            else
                            {
                                jQuery('#wpvivid_user_info_error_msg_box').show();
                                jQuery('#wpvivid_user_info_error_msg').html(jsonarray.error);
                            }
                        }, function(XMLHttpRequest, textStatus, errorThrown)
                        {
                            jQuery('#wpvivid_dashboard_update').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_user_info_box_progress').hide();
                            jQuery('#wpvivid_user_info_log_progress_text').html('');

                            jQuery('#wpvivid_change_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = wpvivid_dashboard_output_ajaxerror('check update', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                </script>
                <?php
            }
            else
            {
                ?>
                <p><input id="wpvivid_check_update_plugin" type="button" class="button-primary ud_connectsubmit" value="Check Update"></p>
                <div id="wpvivid_user_info_box_progress" style="display: none">
                    <p>
                        <span class="dashicons dashicons-admin-network wpvivid-dashicons-green"></span>
                        <span id="wpvivid_user_info_log_progress_text"></span>
                    </p>
                </div>
                <div id="wpvivid_user_info_error_msg_box" style="display: none">
                    <p>
                        <span class="dashicons dashicons-info wpvivid-dashicons-grey"></span>
                        <span id="wpvivid_user_info_error_msg"></span>
                    </p>
                </div>
                <div style="clear: both;"></div>
                <script>
                    jQuery('#wpvivid_check_update_plugin').click(function()
                    {
                        wpvivid_check_update_plugin();
                    });

                    function wpvivid_check_update_plugin($slug)
                    {
                        var ajax_data={
                            'action':'wpvivid_check_update_plugin',
                        };
                        jQuery('#wpvivid_check_update_plugin').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#wpvivid_user_info_log_progress_text').html('Checking Update...');
                        jQuery('#wpvivid_user_info_box_progress').show();
                        jQuery('#wpvivid_user_info_error_msg_box').hide();
                        wpvivid_post_request_addon(ajax_data, function(data)
                        {
                            jQuery('#wpvivid_check_update_plugin').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_user_info_box_progress').hide();
                            jQuery('#wpvivid_user_info_log_progress_text').html('');

                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                location.reload();
                            }
                            else
                            {
                                jQuery('#wpvivid_user_info_error_msg_box').show();
                                jQuery('#wpvivid_user_info_error_msg').html(jsonarray.error);
                            }
                        }, function(XMLHttpRequest, textStatus, errorThrown)
                        {
                            jQuery('#wpvivid_check_update_plugin').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_user_info_box_progress').hide();
                            jQuery('#wpvivid_user_info_log_progress_text').html('');

                            jQuery('#wpvivid_change_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = wpvivid_dashboard_output_ajaxerror('check update', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                </script>
                <?php
            }
        }


    }

    public function plugin_list()
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
            return;

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

        if(empty($dashboard_info))
            return;

        $plugins=$this->get_plugins_status($dashboard_info);

        ?>
        <div>
            <p>Click <b>Uninstall</b> to deactivate and remove the addon.</p>
        </div>
        <table class="widefat" style="margin-top:1em;">
            <thead>
            <tr>
                <th>Addons</th>
                <th>Status</th>
                <th style="text-align:center;">Current Version/Latest Version</th>
                <th>Update</th>
                <th>Remove</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($plugins as $slug=>$item)
            {
                ?>
                <tr>
                    <td><a href=""><b><?php echo apply_filters('wpvivid_white_label_string', $item['name']); ?></b></a></span></td>
                    <td><?php echo $item['status']?></td>
                    <td style="text-align:center;"><span><?php echo $item['current_version'].' / '.$item['latest_version'];?></span></td>
                    <td><a href="<?php echo 'admin.php?page='.strtolower(sprintf('%s-dashboard', apply_filters('wpvivid_white_label_slug', 'wpvivid'))).'&first=1';?>"><?php echo $item['action']?></a></td>
                    <?php
                    if($item['delete'])
                    {
                        ?><td><a data-id="<?php echo $item['plugin_slug'];?>" class="wpvivid_dashboard_remove_plugin" href="#">Uninstall</a></td><?php
                    }
                    else
                    {
                        ?><td><a></a></td><?php
                    }
                    ?>

                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <script>
            //
            jQuery('.wpvivid_dashboard_remove_plugin').click(function()
            {
                wpvivid_dashboard_remove_plugin(jQuery(this));
            });

            function wpvivid_dashboard_remove_plugin(obj)
            {
                var slug=obj.data("id");
                obj.html('Uninstalling...');
                jQuery('.wpvivid_dashboard_remove_plugin').css({'pointer-events': 'none', 'opacity': '0.4'});
                var ajax_data={
                    'action':'wpvivid_dashboard_remove_plugin',
                    'slug':slug
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    jQuery('.wpvivid_dashboard_remove_plugin').css({'pointer-events': 'auto', 'opacity': '1'});

                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        obj.html('Success');
                        location.reload();
                    }
                    else
                    {
                        obj.html('failed');
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('.wpvivid_dashboard_remove_plugin').css({'pointer-events': 'auto', 'opacity': '1'});
                    obj.html('Uninstall');
                });
            }
        </script>
        <?php
    }

    public function get_plugins_status($dashboard_info)
    {
        global $wpvivid_backup_pro;
        $plugins=array();
        if(empty($dashboard_info['plugins']))
        {
            return array();
        }
        foreach ($dashboard_info['plugins'] as $slug=>$info)
        {
            $plugin['name']=$info['name'];
            $status=$wpvivid_backup_pro->addons_loader->get_plugin_status($info);
            $plugin['current_version']=$wpvivid_backup_pro->addons_loader->get_plugin_version($info);
            $plugin['latest_version']=$wpvivid_backup_pro->addons_loader->get_plugin_latest_version($info);

            $plugin['status']=$status['status'];
            $plugin['action']=$status['action'];
            $plugin['delete']=$status['delete'];

            $plugin['plugin_slug']=$slug;

            $plugins[$slug]=$plugin;
        }
        return $plugins;
    }

    public function ajax_check_security($role='administrator')
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        $check=is_admin()&&current_user_can($role);
        $check=apply_filters('wpvivid_ajax_check_security',$check);
        if(!$check)
        {
            die();
        }
    }

    public function dashboard_login()
    {
        $this->ajax_check_security();

        try
        {
            if(isset($_POST['license']))
            {
                if(empty($_POST['license']))
                {
                    $ret['result']='failed';
                    $ret['error']='A license is required.';
                    echo json_encode($ret);
                    die();
                }

                $license=$_POST['license'];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Retrieving user information failed. Please try again later.';
                echo json_encode($ret);
                die();
            }


            $server=new WPvivid_Dashboard_Connect_server();

            $ret=$server->login($license,true, false);
            if($ret['result']=='success')
            {
                if($ret['status']['check_active'])
                {
                    $info['token']=$ret['user_info'];

                    if(is_multisite())
                    {
                        if(is_main_site())
                        {
                            update_option('wpvivid_pro_user',$info,'no');
                            update_option('wpvivid_dashboard_info',$ret['status'],'no');
                            update_option('wpvivid_last_update_time',time(),'no');
                            update_option('wpvivid_last_login_time',time(),'no');
                        }
                        else
                        {
                            switch_to_blog(get_main_site_id());
                            update_option('wpvivid_pro_user',$info,'no');
                            update_option('wpvivid_dashboard_info',$ret['status'],'no');
                            update_option('wpvivid_last_update_time',time(),'no');
                            update_option('wpvivid_last_login_time',time(),'no');
                            restore_current_blog();
                        }
                    }
                    else
                    {
                        update_option('wpvivid_pro_user',$info,'no');
                        update_option('wpvivid_dashboard_info',$ret['status'],'no');
                        update_option('wpvivid_last_update_time',time(),'no');
                        update_option('wpvivid_last_login_time',time(),'no');
                    }

                    $result['result']='success';
                    $result['need_active']=false;
                }
                else
                {
                    $result['result']='success';
                    $result['need_active']=true;
                }
            }
            else
            {
                $result=$ret;
            }

            echo json_encode($result);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']= $e->getMessage();
            echo json_encode($ret);
        }

        die();
    }

    public function dashboard_login_direct()
    {
        $this->ajax_check_security();

        try
        {
            if(isset($_POST['license']))
            {
                if(empty($_POST['license']))
                {
                    $ret['result']='failed';
                    $ret['error']='A license is required.';
                    echo json_encode($ret);
                    die();
                }

                $license=$_POST['license'];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Retrieving user information failed. Please try again later.';
                echo json_encode($ret);
                die();
            }


            $server=new WPvivid_Dashboard_Connect_server();

            $ret=$server->login_direct($license,true, false);
            if($ret['result']=='success')
            {
                if($ret['status']['check_active'])
                {
                    $info['token']=$ret['user_info'];

                    if(is_multisite())
                    {
                        if(is_main_site())
                        {
                            update_option('wpvivid_pro_user',$info,'no');
                            update_option('wpvivid_dashboard_info',$ret['status'],'no');
                            update_option('wpvivid_last_update_time',time(),'no');
                            update_option('wpvivid_last_login_time',time(),'no');
                        }
                        else
                        {
                            switch_to_blog(get_main_site_id());
                            update_option('wpvivid_pro_user',$info,'no');
                            update_option('wpvivid_dashboard_info',$ret['status'],'no');
                            update_option('wpvivid_last_update_time',time(),'no');
                            update_option('wpvivid_last_login_time',time(),'no');
                            restore_current_blog();
                        }
                    }
                    else
                    {
                        update_option('wpvivid_pro_user',$info,'no');
                        update_option('wpvivid_dashboard_info',$ret['status'],'no');
                        update_option('wpvivid_last_update_time',time(),'no');
                        update_option('wpvivid_last_login_time',time(),'no');
                    }

                    $result['result']='success';
                    $result['need_active']=false;
                }
                else
                {
                    $result['result']='success';
                    $result['need_active']=true;
                }
            }
            else
            {
                $result=$ret;
            }

            echo json_encode($result);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']= $e->getMessage();
            echo json_encode($ret);
        }

        die();
    }

    public function dashboard_active()
    {
        $this->ajax_check_security();

        try
        {
            if(isset($_POST['license']))
            {
                if(empty($_POST['license']))
                {
                    $ret['result']='failed';
                    $ret['error']='A license is required.';
                    echo json_encode($ret);
                    die();
                }

                $license=$_POST['license'];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Retrieving user information failed. Please try again later.';
                echo json_encode($ret);
                die();
            }


            $server=new WPvivid_Dashboard_Connect_server();

            $ret=$server->active_site($license,true);
            if($ret['result']=='success')
            {
                $info['token']=$ret['user_info'];
                if(is_multisite())
                {
                    if(is_main_site())
                    {
                        update_option('wpvivid_pro_user',$info,'no');
                        update_option('wpvivid_dashboard_info',$ret['status'],'no');
                        update_option('wpvivid_last_update_time',time(),'no');
                        update_option('wpvivid_last_login_time',time(),'no');
                    }
                    else
                    {
                        switch_to_blog(get_main_site_id());
                        update_option('wpvivid_pro_user',$info,'no');
                        update_option('wpvivid_dashboard_info',$ret['status'],'no');
                        update_option('wpvivid_last_update_time',time(),'no');
                        update_option('wpvivid_last_login_time',time(),'no');
                        restore_current_blog();
                    }
                }
                else
                {
                    update_option('wpvivid_pro_user',$info,'no');
                    update_option('wpvivid_dashboard_info',$ret['status'],'no');
                    update_option('wpvivid_last_update_time',time(),'no');
                    update_option('wpvivid_last_login_time',time(),'no');
                }
                $result['result']='success';
                $result['need_active']=false;
            }
            else
            {
                $result=$ret;
            }

            echo json_encode($result);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']= $e->getMessage();
            echo json_encode($ret);
        }

        die();
    }

    public function check_update_plugin()
    {
        $this->ajax_check_security();

        try
        {
            if(is_multisite())
            {
                if(is_main_site())
                {
                    $info= get_option('wpvivid_pro_user',false);
                }
                else
                {
                    switch_to_blog(get_main_site_id());
                    $info= get_option('wpvivid_pro_user',false);
                    restore_current_blog();
                }
            }
            else
            {
                $info= get_option('wpvivid_pro_user',false);
            }

            if($info===false)
            {
                $ret['result']='failed';
                $ret['error']='need login.';
                echo json_encode($ret);
                die();
            }

            $user_info=$info['token'];

            $server=new WPvivid_Dashboard_Connect_server();
            $ret=$server->login($user_info,false);

            if($ret['result']=='success')
            {
                if($ret['status']['check_active'])
                {
                    global $wpvivid_backup_pro;

                    if(is_multisite())
                    {
                        if(is_main_site())
                        {
                            update_option('wpvivid_dashboard_info',$ret['status'],'no');
                        }
                        else
                        {
                            switch_to_blog(get_main_site_id());
                            update_option('wpvivid_dashboard_info',$ret['status'],'no');
                            restore_current_blog();
                        }
                    }
                    else
                    {
                        update_option('wpvivid_dashboard_info',$ret['status'],'no');
                    }

                    $wpvivid_backup_pro->updater->update_site_transient_update_plugins();
                }
                else
                {
                    delete_option('wpvivid_pro_user');
                    delete_option('wpvivid_dashboard_info');
                }
            }
            else
            {
                $this->handle_server_error($ret);
            }

            echo json_encode($ret);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']= $e->getMessage();
            echo json_encode($ret);
        }

        die();
    }

    public function update_dashboard()
    {
        $this->ajax_check_security();
        try
        {
            if(is_multisite())
            {
                if(is_main_site())
                {
                    $info= get_option('wpvivid_pro_user',false);
                }
                else
                {
                    switch_to_blog(get_main_site_id());
                    $info= get_option('wpvivid_pro_user',false);
                    restore_current_blog();
                }
            }
            else
            {
                $info= get_option('wpvivid_pro_user',false);
            }

            if($info===false)
            {
                $ret['result']='failed';
                $ret['error']='need login.';
                echo json_encode($ret);
                die();
            }

            $ret['result']='success';

            $plugin_basename= plugin_basename( WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'wpvivid-backup-pro.php' );
            $url=wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $plugin_basename, 'upgrade-plugin_' . $plugin_basename);

            $ret['url']=$url;

            echo json_encode($ret);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']= $e->getMessage();
            echo json_encode($ret);
        }

        die();
    }

    public function remove_plugin()
    {
        $this->ajax_check_security();

        if(!isset($_POST['slug']))
        {
            die();
        }

        if( ! function_exists('delete_plugins') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

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

        if(empty($dashboard_info))
            return;

        $plugin=$dashboard_info['plugins'][$_POST['slug']];

        if($plugin['install']['is_plugin'])
        {
            $plugins=array();
            $plugins[]=$plugin['install']['plugin_slug'];
            if(is_plugin_active($plugin['install']['plugin_slug']))
            {
                if(is_multisite())
                {
                    deactivate_plugins($plugin['install']['plugin_slug'],true);
                }
                else
                {
                    deactivate_plugins($plugin['install']['plugin_slug'],true);
                }
            }

            $success=delete_plugins($plugins);
            if(!is_wp_error($success))
            {
                $ret['result']='success';
            }
            else
            {
                $ret['result']='failed';
                $ret['error']=$success->get_error_message();
            }
        }
        else
        {
            $folder=$plugin['install']['data']['folder'];
            $pro_plugin_path=WPVIVID_BACKUP_PRO_PLUGIN_DIR. $folder.'/';
            if(file_exists($pro_plugin_path))
            {
                $this->deldir($pro_plugin_path);
            }
            $ret['result']='success';
        }

        echo json_encode($ret);
        die();
    }

    function deldir($path)
    {

        if(is_dir($path))
        {
            $p = scandir($path);
            foreach($p as $val)
            {
                if($val !="." && $val !="..")
                {
                    if(is_dir($path.$val))
                    {
                        $this->deldir($path.$val.'/');
                    }else{
                        @unlink($path.$val);
                    }
                }
            }
        }
    }
    public function handle_server_error($error)
    {
        if(isset($error['error_code']))
        {
            if($error['error_code']==109||$error['error_code']==108||$error['error_code']==107)
            {
                delete_option('wpvivid_pro_user');
                delete_option('wpvivid_dashboard_info');
            }
        }
    }
}