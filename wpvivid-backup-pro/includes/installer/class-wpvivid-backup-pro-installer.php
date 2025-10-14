<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Backup_Pro_Installer
{
    public $slug;
    public $addons;
    public $plugin;
    public $auto_update;

    public function __construct()
    {
        $this->auto_update=false;
    }

    public function init($plugin,$auto_update=false)
    {
        if(isset($this->plugin['install']['is_plugin']))
            $this->slug=$plugin['install']['plugin_slug'];
        else
            $this->slug='';
        if(isset($plugin['install']['data']['addons']))
            $this->addons=$plugin['install']['data']['addons'];
        else
            $this->addons=array();
        $this->plugin=$plugin;
        $this->auto_update=$auto_update;
    }

    public function run()
    {
        if($this->plugin['install']['is_plugin'])
        {
            $ret=$this->install_plugin($this->plugin,true);
            if($ret['result']!='success')
            {
                return $ret;
            }

            $ret=$this->active_plugin($this->slug);
            if($ret['result']!='success')
            {
                return $ret;
            }
        }

        $ret=$this->install_addons();

        $this->active_require_plugins($this->plugin);

        if($ret['result']!='success')
        {
            return $ret;
        }

        return $ret;
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

    public function get_package($plugin_data)
    {
        $version=$plugin_data['version'];
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

        $this->show_message('download package....');

        $ret=$connect->download_package($user_info['token'],$folder,$des);
        if($ret['result']=='failed')
        {
            return $ret;
        }
        $ret['result']='success';
        $ret['package']=$path.DIRECTORY_SEPARATOR.$file;
        return $ret;
    }

    public function install_addons()
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

        foreach ( $this->addons as $addon)
        {
            if($addon['active'])
            {
                $ret=$connect->install_addon($user_info['token'],$addon['slug'], $this->plugin['install']['data']['folder']);
                if($ret['result']=='failed')
                {
                    return $ret;
                }
            }
        }

        $ret['result']='success';
        return $ret;
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
            $ret['result']='success';
        }
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
}