<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Updater
{
    public $plugin_basename;

    public function __construct()
    {
        $this->plugin_basename        = plugin_basename( WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'wpvivid-backup-pro.php' );
        add_action( 'load-plugins.php', array( $this, 'setup_pro_update_row' ),99);
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_site_transient_update_plugins' ) );

        if($this->need_update())
        {
            add_action( 'admin_notices', array($this,'show_update_notices'));
            add_action('wpvivid_check_admin_notices', array($this, 'check_admin_notices'));
        }

        $this->check_update_schedule();

        add_action('wpvivid_dashboard_update_event',array( $this,'check_dashboard_update_event'));
        add_action('wpvivid_auto_update_schedule',array( $this,'auto_update_event'));

        add_filter('http_request_args', array($this, 'http_request_args'), 10, 2);

        add_filter( 'site_transient_update_plugins', array( $this, 'site_transient_update_plugins' ) );
    }

    public function check_admin_notices()
    {
        add_action( 'admin_notices', array($this,'show_update_notices'));
    }

    public function site_transient_update_plugins($_transient_data)
    {
        if(!apply_filters('wpvivid_show_dashboard_addons',true))
        {
            if ( ! is_object( $_transient_data ) )
            {
                $_transient_data = new stdClass;
            }
            $free_plugin_name = 'wpvivid-backuprestore/wpvivid-backuprestore.php';
            $free_version_info=new stdClass();
            $free_version_info->icons['1x']='';
            $free_version_info->icons['2x']='';
            if(isset($_transient_data->response[ $free_plugin_name ]) && !empty($_transient_data->response[ $free_plugin_name ]))
            {
                $_transient_data->response[ $free_plugin_name ]->slug='wpvivid-backup-pro';
                $_transient_data->response[ $free_plugin_name ]->icons=$free_version_info->icons;
            }
        }

        return $_transient_data;
    }

    public function http_request_args($parsed_args, $url)
    {
        if (preg_match('/update.wpvivid.com/', $url))
        {
            $parsed_args['sslverify']=false;
        }
        return $parsed_args;
    }

    public function need_update()
    {
        global $wpvivid_backup_pro;
        $dashboard_info=get_option('wpvivid_dashboard_info',array());
        if(empty($dashboard_info))
        {
            return false;
        }

        if(isset($dashboard_info['dashboard']))
        {
            if(version_compare(WPVIVID_BACKUP_PRO_VERSION,$dashboard_info['dashboard']['version'], '<'))
            {
                return true;
            }
        }

        return false;
    }

    public function show_update_notices()
    {
        if (is_multisite())
        {
            if(!is_network_admin())
            {
                return ;
            }
        }

        if(get_current_screen()->id === 'plugins')
        {
            return ;
        }

        $wpvivid_common_setting = get_option('wpvivid_common_setting', array());
        if(!empty($wpvivid_common_setting))
        {
            if(isset($wpvivid_common_setting['hide_admin_update_notice']) && $wpvivid_common_setting['hide_admin_update_notice'])
            {
                return;
            }
        }

        global $pagenow;

        if($pagenow=='update.php')
        {
            return ;
        }

        if($this->need_update())
        {
            $dashboard_info=get_option('wpvivid_dashboard_info',array());
            if($dashboard_info!==false)
            {
                $version=$dashboard_info['dashboard']['version'];
                $show_time = get_option('wpvivid_need_update_pro_notice', false);

                if(time()>$show_time)
                {
                    if(is_admin() && current_user_can('administrator'))
                    {
                        $plugin_basename= plugin_basename( WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'wpvivid-backup-pro.php' );
                        //$url=wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $plugin_basename, 'upgrade-plugin_' . $plugin_basename);
                        $url=apply_filters('wpvivid_get_admin_url', '').'plugins.php?s=wpvivid&plugin_status=all';
                        $message = '<div class="notice notice-warning notice-need-update-pro is-dismissible" style="padding: 11px 15px;">';
                        $message .= sprintf(__('There is a new version of %s Pro available.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid Plugins')).' <a href="'. $url. '">Update now</a> to Version ' . $version . ' </div>';
                        echo wp_kses_post($message);
                    }
                }


            }
            ?>
            <script>
                jQuery(document).on('click', '.notice-need-update-pro .notice-dismiss', function(){
                    var ajax_data = {
                        'action': 'wpvivid_hide_need_update_pro_notice'
                    };
                    var time_out = 30000;
                    jQuery.ajax({
                        type: "post",
                        url: '<?php echo admin_url('admin-ajax.php');?>',
                        data: ajax_data,
                        success: function (data) {
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                        },
                        timeout: time_out
                    });
                });
            </script>
            <?php
        }
        else
        {
            delete_option('wpvivid_need_update_pro_notice');
        }
    }

    public function check_update_schedule()
    {
        if(!defined( 'DOING_CRON' ))
        {
            if(wp_get_schedule('wpvivid_pro_update_event')!==false)
            {
                wp_clear_scheduled_hook('wpvivid_pro_update_event');
                $timestamp = wp_next_scheduled('wpvivid_pro_update_event');
                wp_unschedule_event($timestamp,'wpvivid_pro_update_event');
            }

            if(wp_get_schedule('wpvivid_dashboard_update_event')===false)
            {
                if(wp_schedule_event(time()+30, 'daily', 'wpvivid_dashboard_update_event')===false)
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function check_dashboard_update_event()
    {
        $info= get_option('wpvivid_pro_user',false);

        if($info===false)
        {
            die();
        }

        if(isset($info['token']))
        {
            $user_info=$info['token'];
        }
        else
        {
            $user_info=$info['password'];
        }
        $server=new WPvivid_Dashboard_Connect_server();
        $ret=$server->login($user_info,false);

        if($ret['result']=='success')
        {
            if($ret['status']['check_active'])
            {
                update_option('wpvivid_dashboard_info',$ret['status'],'no');

                $this->update_site_transient_update_plugins();

                $auto_update=get_option('wpvivid_dashboard_auto_update','off');
                if($auto_update=='on')
                {
                    if($this->need_update())
                    {
                        wp_schedule_single_event( time() + 60, 'wpvivid_auto_update_schedule' );
                    }
                }
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

        die();
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

    public function auto_update_event()
    {
        $auto_update=get_option('wpvivid_dashboard_auto_update','off');

        if($auto_update=='off')
        {
            die();
        }

        $this->update_pro();

        die();
    }

    public function update_pro()
    {
        if( ! function_exists('plugins_api') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        }

        if(!class_exists('WP_Upgrader'))
            require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

        //require_once( ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php' );
        if(!class_exists('Plugin_Upgrader'))
            require_once( ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php' );

        if(!function_exists('request_filesystem_credentials'))
        {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $skin = new Automatic_Upgrader_Skin;
        $upgrader = new Plugin_Upgrader( $skin );

        $upgrade_result = $upgrader->upgrade(
            $this->plugin_basename,
            array(
                'clear_update_cache'           => false,
                // Always use partial builds if possible for core updates.
                'pre_check_md5'                => false,
                // Only available for core updates.
                'attempt_rollback'             => true,
                // Allow relaxed file ownership in some scenarios
                'allow_relaxed_file_ownership' => false,
            )
        );
    }

    public function setup_pro_update_row()
    {
        remove_action( 'after_plugin_row_' . $this->plugin_basename, 'wp_plugin_update_row', 10);
        add_action( 'after_plugin_row_'.$this->plugin_basename, array( $this, 'pro_update_row' ), 10, 2 );

        //If set whitelabel, hide view details
        if(!apply_filters('wpvivid_show_dashboard_addons',true))
        {
            $free_wpvivid_slug='wpvivid-backuprestore/wpvivid-backuprestore.php';
            remove_action( 'after_plugin_row_'.$free_wpvivid_slug, 'wp_plugin_update_row', 10, 2);
            add_action( 'after_plugin_row_'.$free_wpvivid_slug, array($this, 'free_update_row'), 11, 2 );
        }
    }

    public function update_site_transient_update_plugins()
    {
        remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_site_transient_update_plugins' ) );

        $update_cache = get_site_transient( 'update_plugins' );

        $update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

        $version_info=$this->get_version();

        if ( false === $version_info )
        {
            return;
        }

        if ( version_compare(WPVIVID_BACKUP_PRO_VERSION, $version_info->new_version, '<' ) )
        {

            $update_cache->response[ $this->plugin_basename ] = $version_info;

        }

        $update_cache->last_checked = time();
        $update_cache->checked[ $this->plugin_basename ] = WPVIVID_BACKUP_PRO_VERSION;

        set_site_transient( 'update_plugins', $update_cache );

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_site_transient_update_plugins' ) );
    }

    public function set_site_transient_update_plugins($_transient_data)
    {
        global $pagenow;

        if ( ! is_object( $_transient_data ) )
        {
            $_transient_data = new stdClass;
        }

        $version_info=$this->get_version();

        if($version_info===false)
        {
            return $_transient_data;
        }

        if ( version_compare( WPVIVID_BACKUP_PRO_VERSION, $version_info->new_version, '<' ) ) {

            $_transient_data->response[ $this->plugin_basename ] = $version_info;

        }

        $_transient_data->last_checked           = time();
        $_transient_data->checked[  $this->plugin_basename ] = WPVIVID_BACKUP_PRO_VERSION;

        return $_transient_data;
    }

    public function get_version()
    {
        $dashboard_info = get_option('wpvivid_dashboard_info', array());

        if(empty($dashboard_info)||!isset($dashboard_info['version_info']))
        {
            $info= get_option('wpvivid_pro_user',false);

            if($info!==false)
            {
                $user_info=$info['token'];
                $server=new WPvivid_Dashboard_Connect_server();
                $ret=$server->login($user_info,false);
                if($ret['result']=='success')
                {
                    update_option('wpvivid_dashboard_info',$ret['status'],'no');
                    $dashboard_info=$ret['status'];
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

        if(!isset($dashboard_info['version_info']))
        {
            return false;
        }

        $version_info=new stdClass();

        foreach ($dashboard_info['version_info'] as $key => $value)
        {
            $version_info->$key = $value;
        }

        $connect=new WPvivid_Dashboard_Connect_server();
        $version_info->download_link=$connect->get_download_link();
        $version_info->package=$connect->get_download_link();
        return $version_info;
    }

    public function pro_update_row($file, $plugin_data)
    {
        if( ! current_user_can( 'update_plugins' ) )
        {
            return;
        }

        if ( $this->plugin_basename != $file )
        {
            return;
        }

        remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_site_transient_update_plugins' ) );

        $update_cache = get_site_transient( 'update_plugins' );

        $update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

        if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->plugin_basename ] ) )
        {
            $version_info=$this->get_version();

            if ( false === $version_info )
            {
                return;
            }

            if ( version_compare(WPVIVID_BACKUP_PRO_VERSION, $version_info->new_version, '<' ) )
            {
                $update_cache->response[ $this->plugin_basename ] = $version_info;
                $update_cache->last_checked = time();
                $update_cache->checked[ $this->plugin_basename ] = WPVIVID_BACKUP_PRO_VERSION;
                set_site_transient( 'update_plugins', $update_cache );
            }

        } else {
            $version_info = $update_cache->response[ $this->plugin_basename ];
        }

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_site_transient_update_plugins' ) );

        if ( ! empty( $update_cache->response[ $this->plugin_basename ] ) && version_compare( WPVIVID_BACKUP_PRO_VERSION, $version_info->new_version, '<' ) )
        {
            // build a plugin list row, with update notification
            $wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );

            if ( is_network_admin() )
            {
                $active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
            } else {
                $active_class = is_plugin_active( $file ) ? ' active' : '';
            }

            echo '<tr class="plugin-update-tr' . $active_class . ' " id="' . esc_attr( 'wpvivid-plugins-pro' ) . '-update" data-slug="' . esc_attr( 'wpvivid-plugins-pro' ) . '" data-plugin="' . esc_attr( $file ) . '">';
            echo '<td colspan="' . esc_attr( $wp_list_table->get_column_count() ) . '" class="plugin-update colspanchange">';
            echo '<div class="update-message notice inline notice-warning notice-alt"><p>';
            $admin_url = apply_filters('wpvivid_get_admin_url', ''). 'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-license');

            if ( empty( $version_info->download_link ) )
            {
                printf(
                    __( 'There is a new version of %1$s available.<a href="%2$s" %3$s> Update now</a>' ),
                    $plugin_data['Name'],
                    $admin_url,
                    sprintf(
                        'aria-label="%s"',
                        /* translators: %s: plugin name */
                        esc_attr( sprintf( __( 'Update %s now' ), $plugin_data['Name'] ) )
                    )
                );
            } else {
                printf(
                    __( 'There is a new version of %1$s available.<a href="%2$s" class="update-link" %3$s> Update now%4$s.', 'wpvivid' ),
                    $plugin_data['Name'],
                    esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->plugin_basename, 'upgrade-plugin_' . $this->plugin_basename ) ),
                    sprintf(
                        'aria-label="%s"',
                        /* translators: %s: plugin name */
                        esc_attr( sprintf( __( 'Update %s now' ), $plugin_data['Name'] ) )
                    ),
                    '</a>'
                );
            }

            do_action( "in_plugin_update_message-{$file}", $plugin_data, $version_info );

            echo '</p></div></td></tr>';
        }
    }

    public function free_update_row( $file, $plugin_data ) {
        $current = get_site_transient( 'update_plugins' );

        if ( ! isset( $current->response[ $file ] ) ) {
            return false;
        }

        $response = $current->response[ $file ];

        $plugins_allowedtags = array(
            'a'       => array(
                'href'  => array(),
                'title' => array(),
            ),
            'abbr'    => array( 'title' => array() ),
            'acronym' => array( 'title' => array() ),
            'code'    => array(),
            'em'      => array(),
            'strong'  => array(),
        );

        $plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );
        $plugin_slug = isset( $response->slug ) ? $response->slug : $response->id;

        if ( isset( $response->slug ) ) {
            $details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&section=changelog' );
        } elseif ( isset( $response->url ) ) {
            $details_url = $response->url;
        } else {
            $details_url = $plugin_data['PluginURI'];
        }

        $details_url = add_query_arg(
            array(
                'TB_iframe' => 'true',
                'width'     => 600,
                'height'    => 800,
            ),
            $details_url
        );

        /** @var WP_Plugins_List_Table $wp_list_table */
        $wp_list_table = _get_list_table(
            'WP_Plugins_List_Table',
            array(
                'screen' => get_current_screen(),
            )
        );

        if ( is_network_admin() || ! is_multisite() ) {
            if ( is_network_admin() ) {
                $active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
            } else {
                $active_class = is_plugin_active( $file ) ? ' active' : '';
            }

            $requires_php   = isset( $response->requires_php ) ? $response->requires_php : null;
            $compatible_php = is_php_version_compatible( $requires_php );
            $notice_type    = $compatible_php ? 'notice-warning' : 'notice-error';

            printf(
                '<tr class="plugin-update-tr%s" id="%s" data-slug="%s" data-plugin="%s">' .
                '<td colspan="%s" class="plugin-update colspanchange">' .
                '<div class="update-message notice inline %s notice-alt"><p>',
                $active_class,
                esc_attr( $plugin_slug . '-update' ),
                esc_attr( $plugin_slug ),
                esc_attr( $file ),
                esc_attr( $wp_list_table->get_column_count() ),
                $notice_type
            );

            if ( ! current_user_can( 'update_plugins' ) ) {
                printf(
                /* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number. */
                    __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.' ),
                    $plugin_name,
                    esc_url( $details_url ),
                    sprintf(
                        'class="thickbox open-plugin-details-modal" aria-label="%s"',
                        /* translators: 1: Plugin name, 2: Version number. */
                        esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) )
                    ),
                    esc_attr( $response->new_version )
                );
            } elseif ( empty( $response->package ) ) {
                printf(
                /* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number. */
                    __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>. <em>Automatic update is unavailable for this plugin.</em>' ),
                    $plugin_name,
                    esc_url( $details_url ),
                    sprintf(
                        'class="thickbox open-plugin-details-modal" aria-label="%s"',
                        /* translators: 1: Plugin name, 2: Version number. */
                        esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) )
                    ),
                    esc_attr( $response->new_version )
                );
            } else {
                if ( $compatible_php ) {
                    printf(
                    /* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number, 5: Update URL, 6: Additional link attributes. */
                    //__( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s" %6$s>update now123</a>.' ),
                        __( 'There is a new version of %1$s available. <a href="%5$s" %6$s>update now</a>.' ),
                        $plugin_name,
                        esc_url( $details_url ),
                        sprintf(
                            'class="thickbox open-plugin-details-modal" aria-label="%s"',
                            /* translators: 1: Plugin name, 2: Version number. */
                            esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) )
                        ),
                        esc_attr( $response->new_version ),
                        wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ),
                        sprintf(
                            'class="update-link" aria-label="%s"',
                            /* translators: %s: Plugin name. */
                            esc_attr( sprintf( _x( 'Update %s now', 'plugin' ), $plugin_name ) )
                        )
                    );
                } else {
                    printf(
                    /* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number 5: URL to Update PHP page. */
                        __( 'There is a new version of %1$s available, but it does not work with your version of PHP. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s">learn more about updating PHP</a>.' ),
                        $plugin_name,
                        esc_url( $details_url ),
                        sprintf(
                            'class="thickbox open-plugin-details-modal" aria-label="%s"',
                            /* translators: 1: Plugin name, 2: Version number. */
                            esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $response->new_version ) )
                        ),
                        esc_attr( $response->new_version ),
                        esc_url( wp_get_update_php_url() )
                    );
                    wp_update_php_annotation( '<br><em>', '</em>' );
                }
            }

            /**
             * Fires at the end of the update message container in each
             * row of the plugins list table.
             *
             * The dynamic portion of the hook name, `$file`, refers to the path
             * of the plugin's primary file relative to the plugins directory.
             *
             * @since 2.8.0
             *
             * @param array  $plugin_data An array of plugin metadata. See get_plugin_data()
             *                            and the {@see 'plugin_row_meta'} filter for the list
             *                            of possible values.
             * @param object $response {
             *     An object of metadata about the available plugin update.
             *
             *     @type string   $id           Plugin ID, e.g. `w.org/plugins/[plugin-name]`.
             *     @type string   $slug         Plugin slug.
             *     @type string   $plugin       Plugin basename.
             *     @type string   $new_version  New plugin version.
             *     @type string   $url          Plugin URL.
             *     @type string   $package      Plugin update package URL.
             *     @type string[] $icons        An array of plugin icon URLs.
             *     @type string[] $banners      An array of plugin banner URLs.
             *     @type string[] $banners_rtl  An array of plugin RTL banner URLs.
             *     @type string   $requires     The version of WordPress which the plugin requires.
             *     @type string   $tested       The version of WordPress the plugin is tested against.
             *     @type string   $requires_php The version of PHP which the plugin requires.
             * }
             */
            do_action( "in_plugin_update_message-{$file}", $plugin_data, $response ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            echo '</p></div></td></tr>';
        }
    }
}