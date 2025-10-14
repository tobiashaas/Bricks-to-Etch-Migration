<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}


class WPvivid_Installation
{
    public $auto_update;

    public function __construct()
    {
        $this->auto_update=false;

        //add_action('wp_ajax_wpvivid_init_plugin_install',array( $this,'init_plugin_install'));
        //add_action('wp_ajax_wpvivid_dashboard_login_and_install',array( $this,'login_and_init_plugin_install'));
        //add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        //add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'dashboard');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Dashboard');
            $submenu['menu_title'] = 'Installer';
            if (current_user_can('administrator'))
            {
                $submenu['capability'] = 'administrator';
            } else {
                $submenu['capability'] = 'wpvivid-can-install-plugins';
            }
            $submenu['menu_slug'] = strtolower(sprintf('%s-installer', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 20;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }

        return $submenus;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-installer';
        if(is_multisite())
        {
            $screen['screen_id']='wpvivid-plugin_page_wpvivid-installer-network';
        }
        else
        {
            $screen['screen_id']='wpvivid-plugin_page_wpvivid-installer';
        }

        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function login_and_init_plugin_install()
    {
        if(!isset($_POST['plugins']))
        {
            $ret['result']='failed';
            $ret['error']='Please select the plugin from the list to install.';
            echo json_encode($ret);
            die();
        }

        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-install-plugins');

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

            $ret=$server->login($license,true);
            if($ret['result']=='success')
            {
                update_option('wpvivid_dashboard_info',$ret['status'],'no');
                update_option('wpvivid_last_update_time',time(),'no');
                update_option('wpvivid_last_login_time',time(),'no');
            }
            else
            {
                echo json_encode($ret);
                die();
            }
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']= $e->getMessage();
            echo json_encode($ret);
            die();
        }

        $info=get_option('wpvivid_dashboard_info',array());

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
            $ret['href']=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-installer', 'wpvivid-installer');
        }
        else
        {
            update_option('wpvivid_plugin_install_cache',$plugin_install_cache,'no');
            $ret['result']='success';
            $ret['cache']=$plugin_install_cache;
            $ret['href']=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-installer', 'wpvivid-installer').'&install=1';
        }

        echo json_encode($ret);

        die();
    }

    public function init_plugin_install()
    {
        if(!isset($_POST['plugins']))
        {
            die();
        }
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-install-plugins');

        $info=get_option('wpvivid_dashboard_info',array());

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
            $ret['href']=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-installer', 'wpvivid-installer');
            $wpvivid_backup_pro->updater->update_site_transient_update_plugins();

        }
        else
        {
            update_option('wpvivid_plugin_install_cache',$plugin_install_cache,'no');
            $ret['result']='success';
            $ret['cache']=$plugin_install_cache;
            $ret['href']=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-installer', 'wpvivid-installer').'&install=1';
        }

        echo json_encode($ret);

        die();
    }

    public function init_page()
    {
        $user_info=get_option('wpvivid_pro_user',false);
        if($user_info===false)
        {
            $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-license', 'wpvivid-license').'&check_update=1';
            ?>
            <script>
                location.href='<?php echo $url;?>';
            </script>
            <?php
        }

        $dashboard_info = get_option('wpvivid_dashboard_info', array());
        if(empty($dashboard_info))
        {
            $url=apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-license', 'wpvivid-license').'&check_update=1';
            ?>
            <script>
                location.href='<?php echo $url;?>';
            </script>
            <?php
        }

        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Installer', 'wpvivid' ); ?></h1>
            <div id="wpvivid_pro_notice">
            </div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <?php $this->welcome_bar();?>
                                <div style="clear: both;"></div>
                                <div class="wpvivid-canvas wpvivid-clear-float" >
                                    <div class="wpvivid-one-coloum" style="padding-top:0;">
                                        <?php $this->progress_bar();?>
                                        <?php $this->task_list();?>
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
        <?php
    }

    public function welcome_bar()
    {
        ?>
        <div class="wpvivid-welcome-bar wpvivid-clear-float">
            <div class="wpvivid-welcome-bar-left">
                <p>
                    <span class="dashicons dashicons-plugins-checked wpvivid-dashicons-large wpvivid-dashicons-green"></span>
                    <span class="wpvivid-page-title"><?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Plugins Installer</span>
                </p>
                <span class="about-description">The installer helps you install <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> plugins with one-click</span>
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
        <div class="wpvivid-nav-bar wpvivid-clear-float">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span>You have to <strong>enter the license again</strong> when you need to re-install plugins. It's a protection mechanism for preventing unauthorized installing.</span>
        </div>
        <?php
    }

    public function progress_bar()
    {
        if(isset($_REQUEST['install'])&&$_REQUEST['install'])
        {
            ?>
            <div>
                <p>
                <span class="wpvivid-span-progress" id="wpvivid_plugin_progress">
                    <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress">0% completed</span>
                </span>
                </p>
                <p>
                    <span class="dashicons dashicons-admin-generic wpvivid-dashicons-green"></span>
                    <span id="wpvivid_plugin_progress_text">Start to install......</span>
                </p>
            </div>
            <div style="clear: both;"></div>
            <?php
        }
    }

    public function task_list()
    {
        if(isset($_REQUEST['install'])&&$_REQUEST['install'])
        {
            $plugin_install_cache=get_option('wpvivid_plugin_install_cache',array());
            if(empty($plugin_install_cache)||empty($plugin_install_cache['plugins']))
            {
                return;
            }

            $plugin_install_cache=get_option('wpvivid_plugin_install_cache',array());

            $plugins=$plugin_install_cache['plugins'];
            $complete= $plugin_install_cache['complete'];

            ?>
            <table class="widefat">
                <thead>
                <tr>
                    <th>Plugins</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php

                foreach ($complete as $item)
                {
                    ?>
                    <tr>
                        <td style="width: 80%">
                            <b><a href=""><?php echo $item['name']?></a></b>
                            <span><?php echo $item['description']?></span>
                        </td>
                        <td><?php
                            echo '<span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span>Installed';
                            ?>
                        </td>
                    </tr>
                    <?php
                }

                foreach ($plugins as $item)
                {
                    ?>
                    <tr>
                        <td style="width: 80%">
                            <b><a href=""><?php echo $item['name']?></a></b>
                            <span><?php echo $item['description']?></span>
                        </td>
                        <td><?php
                            echo '<span class="dashicons dashicons-admin-plugins wpvivid-dashicons-grey"></span>Waiting for installation';
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php

            if(!class_exists('WPvivid_Plugin_Installer'))
            {
                include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/installer/class-wpvivid-installer.php';
            }
            $installer=new WPvivid_Plugin_Installer();
            $installer->run_installation();
        }
        else
        {
            $dashboard_info=get_option('wpvivid_dashboard_info',array());
            $last_login_time=get_option('wpvivid_last_login_time',0);
            $plugins=$this->get_plugins_status($dashboard_info);

            /*
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
            }*/

            $need_login=false;
            /*
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
            }*/

            ?>
            <table class="widefat" id="wpvivid_intall_plugins_list">
                <thead>
                <tr>
                    <th class="row-title"></th>
                    <th>Plugins</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($plugins as $item)
                {
                    if($item['status']=='Installed'||$item['status']=='Up to date')
                    {
                        $check='';
                    }
                    else
                    {
                        $check='checked';
                    }
                    ?>
                    <tr>
                        <td>
                            <?php
                            if($item['status']=='Not available')
                            {
                                ?>
                                <input option="install" name="plugin" value="<?php echo $item['slug'];?>" type="checkbox" disabled/>
                                <?php
                            }
                            else
                            {
                                ?>
                                <input option="install" name="plugin" value="<?php echo $item['slug'];?>" type="checkbox" <?php echo $check?> />
                                <?php
                            }
                            ?>
                        </td>
                        <td style="width: 80%">
                            <b><a href=""><?php echo apply_filters('wpvivid_white_label_string', $item['name']); ?></a></b>
                            <span><?php echo apply_filters('wpvivid_white_label_string', $item['info']); ?></span>
                        </td>
                        <td><?php
                            if($item['status']=='Installed'||$item['status']=='Up to date')
                            {
                                echo '<span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span>';
                            }
                            else
                            {
                                echo '<span class="dashicons dashicons-admin-plugins wpvivid-dashicons-grey"></span>';
                            }
                            echo $item['status'];
                            ?>
                        </td>
                    </tr>
                    <?php
                    if($item['requires_plugins']!==false)
                    {
                        ?>
                        <tr>
                            <th class="check-column"></th>
                        <?php
                        foreach ($item['requires_plugins'] as $plugin)
                        {
                            ?>
                            <td style="width: 80%">
                                <b><a href=""><?php echo apply_filters('wpvivid_white_label_string', $plugin['name']); ?></a></b>
                                <span><?php echo apply_filters('wpvivid_white_label_string', $plugin['description']); ?></span>
                            </td>
                            <td><?php
                                if($plugin['status']=='Installed'||$plugin['status']=='Up to date')
                                {
                                    echo '<span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span>';
                                }
                                else
                                {
                                    echo '<span class="dashicons dashicons-admin-plugins wpvivid-dashicons-grey"></span>';
                                }
                                echo $plugin['status']
                                ?>
                            </td>
                            <?php
                        }
                        ?>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
            <div>
                <?php
                if($need_login)
                {
                    ?>
                    <div style="margin-top: 1em;margin-bottom:1em;">
                        <p>
                            <span><input type="password" class="" id="wpvivid_account_license" placeholder="Enter the license again" autocomplete="new-password" required=""/></span>
                            <span><input class="button-primary" id="wpvivid_need_login_install_plugin" type="button" value="Install Now"/></span>
                        </p>
                    </div>
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
                    <div style="clear:both;"></div>
                    <?php
                }
                else
                {
                    ?>
                    <p>
                        <input class="button-primary" style="width: 120px" type="submit" id="wpvivid_install_plugin" value="Install Now">
                    </p>
                    <?php
                }
                ?>

            </div>
            <script>
                jQuery('#wpvivid_install_plugin').click(function()
                {
                    wpvivid_install_plugin();
                });

                jQuery('#wpvivid_need_login_install_plugin').click(function()
                {
                    wpvivid_dashboard_login_and_install();
                });

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

                function wpvivid_dashboard_login_and_install()
                {
                    var license = jQuery('#wpvivid_account_license').val();

                    var json = {};
                    json['plugins_list'] = Array();
                    jQuery('#wpvivid_intall_plugins_list').find('input:checkbox[option=install][name=plugin]').each(function()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            json['plugins_list'].push(jQuery(this).val());
                        }
                    });

                    var ajax_data={
                        'action':'wpvivid_dashboard_login_and_install',
                        'license':license,
                        'plugins':json['plugins_list'],
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
                            location.href=jsonarray.href;
                        }
                        else
                        {
                            wpvivid_lock_login(false,jsonarray.error);
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown)
                    {
                    });
                }

                function wpvivid_lock_login(lock,error='')
                {
                    if(lock)
                    {
                        jQuery('#wpvivid_need_login_install_plugin').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#wpvivid_login_box_progress').show();
                        jQuery('#wpvivid_login_error_msg_box').hide();
                    }
                    else
                    {
                        jQuery('#wpvivid_log_progress_text').html('');
                        jQuery('#wpvivid_login_box_progress').hide();
                        jQuery('#wpvivid_need_login_install_plugin').css({'pointer-events': 'auto', 'opacity': '1'});

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

                function wpvivid_install_plugin()
                {
                    var json = {};
                    json['plugins_list'] = Array();
                    jQuery('#wpvivid_intall_plugins_list').find('input:checkbox[option=install][name=plugin]').each(function()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            json['plugins_list'].push(jQuery(this).val());
                        }
                    });

                    var ajax_data={
                        'action':'wpvivid_init_plugin_install',
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
            </script>
            <?php
        }
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
            $plugins[$slug]=$plugin;
        }
        return $plugins;
    }

    public function sidebar()
    {
        do_action( 'wpvivid_add_sidebar_dashboard' );
    }

    public function progress($progress)
    {
        if($this->auto_update)
            return;
        $html="<span class='wpvivid-span-processed-progress' style='width:$progress%;'>$progress% completed</span>";
        echo '<script> jQuery("#wpvivid_plugin_progress").html("'.$html.'");</script>';
        wp_ob_end_flush_all();
        flush();
    }
}