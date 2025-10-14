<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Plugin_Installer
{
    public $auto_update;

    public function run_installation()
    {
        set_time_limit(300);
        $this->load_installer();
        $plugin_install_cache=get_option('wpvivid_plugin_install_cache',array());

        if(empty($plugin_install_cache)||empty($plugin_install_cache['plugins']))
        {
            $this->show_message('not plugin need to be install.');
            return;
        }

        $plugin_data=array_shift($plugin_install_cache['plugins']);
        ?>
        <script>
            jQuery('#wpvivid_plugin_title').html("<?php echo $plugin_data['name'];?>");
        </script>
        <?php

        if(isset($plugin_data['install']['installer']))
        {
            if(class_exists($plugin_data['install']['installer']))
            {
                $installer=new $plugin_data['install']['installer'];
                $installer->init($plugin_data);
                $ret=$installer->run();
                if($ret['result']!='success')
                {
                    $this->show_message($ret['error']);
                    return;
                }
            }
            else
            {
                $this->show_message($plugin_data['name'].' not found install.');
                return;
            }
        }
        else
        {
            if($plugin_data['install']['is_plugin'])
            {
                $ret=$this->install_plugin($plugin_data,true);
                if($ret['result']!='success')
                {
                    $this->show_message($ret['error']);
                    return;
                }

                $this->active_plugin($plugin_data['install']['plugin_slug']);
            }
            else
            {
                if(isset($plugin_data['install']['data'])&&isset($plugin_data['install']['data']['addons']))
                {
                    $this->install_addons($plugin_data);
                }
            }
            $this->active_require_plugins($plugin_data);
        }

        if(empty($plugin_install_cache['plugins']))
        {
            $this->show_message('The installation is complete');
        }
        else
        {
            $this->show_message('Start next installation');
        }


        $plugin_install_cache['complete'][]=$plugin_data;
        update_option('wpvivid_plugin_install_cache',$plugin_install_cache,'no');

        if(empty($plugin_install_cache['plugins']))
        {
            //delete_option('wpvivid_last_login_time');

            $first_install=get_option('wpvivid_plugins_first_install',false);

            if($first_install===false||$first_install=='step2')
            {
                $url='admin.php?page='.strtolower(sprintf('%s-dashboard', apply_filters('wpvivid_white_label_slug', 'wpvivid'))).'&finish=1';
                update_option('wpvivid_plugins_first_install','step3','no');
            }
            else
            {
                $url='admin.php?page='.strtolower(sprintf('%s-dashboard', apply_filters('wpvivid_white_label_slug', 'wpvivid'))).'&finish=1';
            }
            ?>
            <script>
                location.href='<?php echo $url;?>';
            </script>
            <?php
            wp_ob_end_flush_all();
            flush();
        }
        else
        {
            //$url='admin.php?page='.strtolower(sprintf('%s-installer', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $url='admin.php?page='.strtolower(sprintf('%s-dashboard', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            ?>
            <script>
                location.href='<?php echo $url.'&install=1';?>';
            </script>
            <?php
            wp_ob_end_flush_all();
            flush();
        }
        return;
    }

    public function mainwp_install_plugin($plugin_data)
    {
        $this->auto_update=true;
        set_time_limit(300);

        $this->load_installer();

        if(isset($plugin_data['install']['installer']))
        {
            if(class_exists($plugin_data['install']['installer']))
            {
                $installer=new $plugin_data['install']['installer'];
                $installer->init($plugin_data);
                $ret=$installer->run();
                if($ret['result']!='success')
                {
                    return $ret;
                }
            }
            else
            {
                $ret['result']='failed';
                $ret['error']=$plugin_data['name'].' not found install.';
                return $ret;
            }
        }
        else
        {
            if($plugin_data['install']['is_plugin'])
            {
                $ret=$this->install_plugin($plugin_data,true);
                if($ret['result']!='success')
                {
                    return $ret;
                }

                $this->active_plugin($plugin_data['install']['plugin_slug']);
            }
            else
            {
                if(isset($plugin_data['install']['data'])&&isset($plugin_data['install']['data']['addons']))
                {
                    $ret=$this->install_addons($plugin_data);
                    if($ret['result']!='success')
                    {
                        return $ret;
                    }
                }
            }
            $this->active_require_plugins($plugin_data);
        }

        $ret['result']='success';
        return $ret;
    }

    public function auto_update()
    {
        set_time_limit(300);

        $this->load_installer();

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
            return;
        }

        $this->auto_update=true;
        if(isset($dashboard_info['dashboard']))
        {
            if(version_compare($dashboard_info['dashboard']['version'],WPVIVID_BACKUP_PRO_VERSION, '>'))
            {
                $this->update_dashboard();
                return;
            }
        }
        foreach ($dashboard_info['plugins'] as $slug=>$plugin_data)
        {
            global $wpvivid_backup_pro;
            $version=$wpvivid_backup_pro->addons_loader->get_plugin_version($plugin_data);

            if($version===false)
            {
                continue;
            }

            $latest_version=$wpvivid_backup_pro->addons_loader->get_plugin_latest_version($plugin_data);

            if(version_compare($latest_version,$version, '>'))
            {
                if(isset($plugin_data['install']['installer']))
                {
                    if(class_exists($plugin_data['install']['installer']))
                    {
                        $installer=new $plugin_data['install']['installer'];
                        $installer->init($plugin_data,$this->auto_update);
                        $installer->run();
                        return;
                    }
                    else
                    {
                        return;
                    }
                }
                else
                {
                    if($plugin_data['install']['is_plugin'])
                    {
                        $ret=$this->install_plugin($plugin_data,true);
                        if($ret['result']!=='success')
                        {
                            return;
                        }
                        else
                        {
                            $this->active_plugin($plugin_data['install']['plugin_slug']);
                            return;
                        }
                    }
                    else
                    {
                        if(isset($plugin_data['install']['data'])&&isset($plugin_data['install']['data']['addons']))
                        {
                            $this->install_addons($plugin_data);
                            return;
                        }
                    }
                }
            }
        }
    }

    public function install_plugin($plugin_data,$update=false)
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
        $ret=$this->get_package($plugin_data);
        if($ret['result']=='success')
        {
            $package=$ret['package'];
        }
        else
        {
            return $ret;
        }

        require_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/installer/class-wpvivid-plugin-installer-skin.php';

        $skin     = new WPvivid_Plugin_Installer_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        if($update)
        {
            $plugin_slug=$plugin_data['install']['plugin_slug'];

            if($plugin_slug=='wpvivid-backuprestore/wpvivid-backuprestore.php')
            {
                $remote_destination = WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.'wpvivid-backuprestore'.DIRECTORY_SEPARATOR;
            }
            else if($plugin_slug=='wpvivid-staging/wpvivid-staging.php')
            {
                $remote_destination = WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.'wpvivid-staging'.DIRECTORY_SEPARATOR;
            }
            else
            {
                $remote_destination='';
            }
            if(!empty($remote_destination))
            {
                WP_Filesystem();
                $upgrader->clear_destination($remote_destination);
            }
        }
        $return=$upgrader->install($package);
        if($return)
        {
            $ret['result']= 'success';
        } else {
            $ret['result'] = 'failed';
            if(is_wp_error( $return ))
            {
                $ret['error'] =$return->get_error_message() ;
            }
            else
            {
                $ret['error']='install failed '.$return;
            }
        }

        return $ret;
    }

    public function install_addons($plugin_data)
    {
        if(is_multisite())
        {
            if(is_main_site())
            {
                $user_info=get_option('wpvivid_pro_user',false);
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $user_info=get_option('wpvivid_pro_user',false);
                restore_current_blog();
            }
        }
        else
        {
            $user_info=get_option('wpvivid_pro_user',false);
        }

        if($user_info===false)
        {
            $ret['result']='failed';
            $ret['error']='not found user info.';
            return $ret;
        }

        $connect=new WPvivid_Dashboard_Connect_server();

        $path=WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.'wpvivid-backup-pro';

        if(!file_exists($path))
        {
            $ret['result']='failed';
            $ret['error']='not found wpvivid pro path';
            return $ret;
        }

        $this->show_message('Installing addons....');

        foreach ( $plugin_data['install']['data']['addons'] as $addon)
        {
            if($addon['active'])
            {
                $ret=$connect->install_addon($user_info['token'],$addon['slug'], $plugin_data['install']['data']['folder']);
                if($ret['result']=='failed')
                {
                    return $ret;
                }
            }
        }

        $ret['result']='success';
        return $ret;
    }

    public function active_require_plugins($plugin_data)
    {
        if( ! function_exists('get_plugins') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugins=get_plugins();

        if(isset($plugin_data['requires_plugins']))
        {
            $require_plugins=array();
            foreach ( $plugin_data['requires_plugins'] as $requires_plugin)
            {
                $slug=$requires_plugin['install']['plugin_slug'];
                $require_plugins[$slug]=$requires_plugin;
                if(isset($plugins[$slug]))
                {
                    if(is_multisite())
                    {
                        $result =activate_plugin( $slug ,'',true,false);
                    }
                    else
                    {
                        $result =activate_plugin( $slug ,'',false,false);
                    }

                    if(is_wp_error($result))
                    {
                        continue;
                    }
                    else
                    {
                        if($slug=='wpvivid-backuprestore/wpvivid-backuprestore.php')
                        {
                            delete_option('wpvivid_do_activation_redirect');
                        }
                        else if($slug=='wpvivid-backup-pro/wpvivid-backup-pro.php')
                        {
                            delete_option('wpvivid_pro_do_activation_redirect');
                        }
                    }
                }
            }
        }
    }

    public function update_dashboard()
    {
        if(is_multisite())
        {
            if(is_main_site())
            {
                $user_info=get_option('wpvivid_pro_user',false);
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $user_info=get_option('wpvivid_pro_user',false);
                restore_current_blog();
            }
        }
        else
        {
            $user_info=get_option('wpvivid_pro_user',false);
        }
        if($user_info===false)
        {
            $ret['result']='failed';
            $ret['error']='not found user info.';
            return $ret;
        }

        $connect=new WPvivid_Dashboard_Connect_server();

        $this->show_message('Installing dashboard....');
        $ret=$connect->update_dashboard($user_info['token']);
        if($ret['result']=='failed')
        {
            return $ret;
        }
        $ret['result']='success';
        return $ret;
    }

    public function get_package($plugin_data)
    {
        if(isset($plugin_data['install']['wordpress_package_url']))
        {
            include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
            $plugin=$plugin_data['install']['wordpress_package_url'];
            $api = plugins_api(
                'plugin_information',
                array(
                    'slug'   => $plugin,
                    'fields' => array(
                        'sections' => false,
                    ),
                )
            );

            if ( is_wp_error( $api ) )
            {
                $ret['result']='failed';
                $ret['error']=$api->get_error_message();
                return $ret;
            }

            $ret['result']='success';
            $ret['package']=$api->download_link;
            return $ret;
        }

        if(isset($plugin_data['install']['version']))
        {
            $version=$plugin_data['install']['version'];
        }
        else
        {
            $version=$plugin_data['version'];
        }

        $file=$plugin_data['install']['file'];
        $folder=$plugin_data['install']['folder'];
        $path=$folder.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.$file;

        $local_cache_folder=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'wpvivid_dashboard_cache'.DIRECTORY_SEPARATOR;
        $dashboard_local_cache_folder=WPVIVID_BACKUP_PRO_PLUGIN_DIR.'local_plugins'.DIRECTORY_SEPARATOR;
        if(file_exists($local_cache_folder.$path))
        {
            $ret['result']='success';
            $ret['package']=$local_cache_folder.$path;
            return $ret;
        }
        else if(file_exists($dashboard_local_cache_folder.$path))
        {
            $ret['result']='success';
            $ret['package']=$dashboard_local_cache_folder.$path;
            return $ret;
        }
        else
        {
            $des=$folder.DIRECTORY_SEPARATOR.$version;
            $ret=$this->download_package($folder,$des,$file);
            return $ret;
        }
    }

    public function show_message($message)
    {
        if($this->auto_update)
            return;
        if ( is_wp_error( $message ) ) {
            if ( $message->get_error_data() && is_string( $message->get_error_data() ) ) {
                $message = $message->get_error_message() . ': ' . $message->get_error_data();
            } else {
                $message = $message->get_error_message();
            }
        }
        echo '<script> jQuery("#wpvivid_plugin_progress_text").html("'.$message.'");</script>';

        wp_ob_end_flush_all();
        flush();
    }

    public function active_plugin($slug)
    {
        if(is_multisite())
        {
            $result =activate_plugin( $slug ,'',true,false);
        }
        else
        {
            $result =activate_plugin( $slug ,'',false,false);
        }

        if(is_wp_error($result))
        {
            $ret['result']='failed';
            $ret['error']=$result->get_error_message();
        }
        else
        {
            if($slug=='wpvivid-backuprestore/wpvivid-backuprestore.php')
            {
                delete_option('wpvivid_do_activation_redirect');
            }
            else if($slug=='wpvivid-backup-pro/wpvivid-backup-pro.php')
            {
                delete_option('wpvivid_pro_do_activation_redirect');
            }
            $ret['result']='success';
        }
        return $ret;
    }

    public function download_package($folder,$des,$file)
    {
        $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'wpvivid_dashboard_cache'.DIRECTORY_SEPARATOR.$des;
        if(!file_exists($path))
        {
            mkdir($path, 0777, true);
        }

        if(is_multisite())
        {
            if(is_main_site())
            {
                $user_info=get_option('wpvivid_pro_user',false);
            }
            else
            {
                switch_to_blog(get_main_site_id());
                $user_info=get_option('wpvivid_pro_user',false);
                restore_current_blog();
            }
        }
        else
        {
            $user_info=get_option('wpvivid_pro_user',false);
        }
        if($user_info===false)
        {
            $ret['result']='failed';
            $ret['error']='not found user info.';
            return $ret;
        }

        $connect=new WPvivid_Dashboard_Connect_server();

        //$this->show_message('download package....');

        $ret=$connect->download_package($user_info['token'],$folder,$des);
        if($ret['result']=='failed')
        {
            return $ret;
        }
        $ret['result']='success';
        $ret['package']=$path.DIRECTORY_SEPARATOR.$file;
        return $ret;
    }

    public function load_installer()
    {
        require_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/installer/class-wpvivid-backup-pro-installer.php';
    }
}