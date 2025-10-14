<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class WPvivid_Pro_Setting
{
    public $main_tab;

    public function __construct()
    {
        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),20);

        //dashboard
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 20, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 20);

        add_action('wpvivid_dashboard_menus_sidebar',array( $this,'setting_sidebar'),11);
    }

    public function get_plugins_status($dashboard_info)
    {
        global $wpvivid_backup_pro;
        $plugins=array();

        if(!empty($dashboard_info['plugins']))
        {
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
        }

        return $plugins;
    }

    public function is_backup_free_active()
    {
        if ( ! function_exists( 'is_plugin_active' ) )
        {
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
        }
        if(is_plugin_active('wpvivid-backuprestore/wpvivid-backuprestore.php'))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function has_backup_pro()
    {
        $dashboard_info=get_option('wpvivid_dashboard_info',array());
        $plugins=$this->get_plugins_status($dashboard_info);
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

    public function has_role_cap_pro()
    {
        $dashboard_info=get_option('wpvivid_dashboard_info',array());
        $plugins=$this->get_plugins_status($dashboard_info);
        $has=false;
        foreach ($plugins as $item)
        {
            if($item['slug'] === 'role_cap')
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

    public function setting_sidebar()
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_setting');
        $tabs=array();
        $tabs=apply_filters('wpvividdashboard_pro_setting_tab',$tabs);
        if(empty($tabs))
        {
            $display=false;
        }
        if($display)
        {
            if(($this->is_backup_free_active() && $this->has_backup_pro()) || $this->has_role_cap_pro())
            {
                if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-setting'))
                {
                    $url='admin.php?page='.strtolower(sprintf('%s-setting', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
                    ?>
                    <div class="wpvivid-four-cols">
                        <ul>
                            <li><span class="dashicons dashicons-admin-generic wpvivid-dashicons-middle wpvivid-dashicons-blue"></span>
                                <a href="<?php echo $url; ?>"><b>Setting</b></a>
                                <br>
                                Customize <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> settings to your preference and modify the parameters of the tasks to the limits of your web hosts.</li>
                        </ul>
                    </div>
                    <?php
                }
            }
        }
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-setting';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-setting';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_setting');

        $tabs=array();
        $tabs=apply_filters('wpvividdashboard_pro_setting_tab',$tabs);
        if(empty($tabs))
        {
            $display=false;
        }

        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Settings');
            $submenu['menu_title'] = 'Settings';

            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-setting");
            $submenu['menu_slug'] = strtolower(sprintf('%s-setting', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 15;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_setting');

        $tabs=array();
        $tabs=apply_filters('wpvividdashboard_pro_setting_tab',$tabs);
        if(empty($tabs))
        {
            $display=false;
        }

        if($display)
        {
            $menu['id'] = 'wpvivid_admin_menu_setting';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Settings';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-setting');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-setting');
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-setting");
            $menu['index'] = 15;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function init_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Settings', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-admin-generic wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">Settings</span></p>
                                        <span class="about-description">Settings for all <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> plugins.</span>
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
                                        <span> All default settings are optimized for most users, leave it as default or feel free to modify as per your preferences.</span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Setting_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-setting-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Setting_Tab_Page_Container_Ex();
                                    $this->main_tab->is_parent_tab=0;
                                    $tabs=array();
                                    $tabs=apply_filters('wpvividdashboard_pro_setting_tab',$tabs);

                                    if(!empty($tabs))
                                    {
                                        foreach ($tabs as $key=>$tab)
                                        {
                                            $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                                        }

                                        $this->main_tab->display();
                                    }
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
            jQuery(document).ready(function ($)
            {
                <?php
                if(isset($_REQUEST['tabs']))
                {
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id; ?>-show',[ '<?php echo $_REQUEST['tabs'];?>', '<?php echo $_REQUEST['tabs'];?>' ]);
                <?php
                }

                if(isset($_REQUEST['role_cap']))
                {
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'role_cap', 'role_cap' ]);
                <?php
                }

                if(isset($_REQUEST['backup_retain']))
                {
                ?>
                var top = jQuery("#wpvivid_tab_manual_backup").offset().top-jQuery("#wpvivid_tab_manual_backup").height();
                jQuery('html, body').animate({scrollTop:top}, 'slow');
                <?php
                }
                ?>
            });
        </script>
        <?php
    }
}