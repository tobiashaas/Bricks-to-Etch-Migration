<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_addon_loader
{
    public function __construct()
    {
    }

    public function load_addons()
    {
        //$this->load_local_addon('backup_pro','wpvivid-backup-pro-all-in-one');
        if(is_multisite())
        {
            if(is_main_site())
            {
                $dashboard_info = get_option('wpvivid_dashboard_info', array());
            }
            else
            {
                $site_id=get_main_site_id();
                $dashboard_info = get_blog_option($site_id,'wpvivid_dashboard_info', array());
            }
        }
        else
        {
            $dashboard_info = get_option('wpvivid_dashboard_info', array());
        }


        if(empty($dashboard_info))
        {
            if(is_admin())
            {
                if(is_multisite())
                {
                    if(is_main_site())
                    {
                        $info = get_option('wpvivid_pro_user', array());
                    }
                    else
                    {
                        $site_id=get_main_site_id();
                        $info = get_blog_option($site_id,'wpvivid_pro_user', array());
                    }
                }
                else
                {
                    $info = get_option('wpvivid_pro_user', array());
                }

                if(!empty($info))
                {
                    $user_info=$info['token'];

                    $server=new WPvivid_Dashboard_Connect_server();
                    $ret=$server->login($user_info,false);

                    if($ret['result']=='success')
                    {
                        if($ret['status']['check_active'])
                        {
                            update_option('wpvivid_dashboard_info',$ret['status'],'no');
                        }
                    }
                }
            }
        }

        if(!isset($dashboard_info['plugins'])||empty($dashboard_info['plugins']))
        {
            $this->check_local_addon();
            return;
        }
        else
        {
            foreach ($dashboard_info['plugins'] as $slug=>$plugin)
            {
                $addons=array();
                if($this->is_plugin_requires($plugin))
                {
                    $addons=$this->get_plugin_addons($plugin);
                }
                if(!empty($addons))
                {
                    foreach ($addons as $folder=>$addon)
                    {
                        $this->load_local_addon($addon['folder'],$addon['slug']);
                    }
                }
            }
        }
    }

    public function check_local_addon()
    {
        if($this->is_local_plugin_requires('wpvivid-backuprestore/wpvivid-backuprestore.php'))
        {
            $this->load_local_addon('addons2\backup_pro','wpvivid-backup-pro-all-in-one');
        }
    }

    public function get_addons()
    {
        $dashboard_info = get_option('wpvivid_dashboard_info', array());

        if(empty($dashboard_info))
        {
            return array();
        }
        else
        {
            $local_addons=array();
            if(empty($dashboard_info['plugins']))
            {
                return array();
            }
            foreach ($dashboard_info['plugins'] as $slug=>$plugin)
            {
                $addons=$this->get_plugin_addons($plugin);
                if(!empty($addons))
                {
                    foreach ($addons as $folder=>$addon)
                    {
                        if($this->is_local_addon_exist($addon['folder'],$addon['slug']))
                        {
                            $local_addons[$addon['slug']]=$addon['slug'];
                        }
                    }
                }
            }
            return $local_addons;
        }
    }

    public function load_local_addon($folder,$slug)
    {
        if (is_dir(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder) && $dir_handle = opendir(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder))
        {
            while (false !== ($file = readdir($dir_handle)))
            {
                if (is_file(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder.'/' . $file) && preg_match('/\.php$/', $file))
                {
                    $addon_data = $this->get_addon_data(WPVIVID_BACKUP_PRO_PLUGIN_DIR .$folder. '/' . $file);
                    if (!empty($addon_data['WPvivid_addon']))
                    {
                        if(isset($addon_data['Name'])&&$addon_data['Name']==$slug)
                        {
                            if(empty($addon_data['No_need_load']))
                            {
                                if(!empty($addon_data['Admin_load']))
                                {
                                    if(is_admin())
                                    {
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder.'/' . $file;
                                    }
                                }
                                else
                                {
                                    include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder.'/' . $file;
                                }
                            }

                            if(!empty($addon_data['Need_init']))
                            {
                                if(!empty($addon_data['Admin_load']))
                                {
                                    if(is_admin())
                                    {
                                        new $addon_data['Interface_Name']();
                                    }
                                }
                                else
                                {
                                    new $addon_data['Interface_Name']();
                                }

                            }
                        }
                    }
                }
            }
            @closedir($dir_handle);
        }
    }

    public function get_plugin_addons($plugin)
    {
        $addons=array();
        if(isset($plugin['install']['data'])&&isset($plugin['install']['data']['addons']))
        {
            foreach ($plugin['install']['data']['addons'] as $addon_slug=>$addon)
            {
                $addon['folder']=$plugin['install']['data']['folder'];
                $addons[$addon_slug]=$addon;
            }
        }
        return $addons;
    }

    public function get_addon_data($file)
    {
        $default_headers = array(
            'Name' => 'Addon Name',
            'Version' => 'Version',
            'Description' => 'Description',
            'WPvivid_addon'=>'WPvivid addon',
            'Require'=>'Require',
            'Need_init'=>'Need_init',
            'No_need_load'=>'No_need_load',
            'Admin_load'=>'Admin_load',
            'Interface_Name'=>'Interface Name'
        );

        return  @get_file_data( $file, $default_headers);
    }

    public function is_local_plugin_requires($plugin)
    {
        if( ! function_exists('get_plugin_data') || ! function_exists('get_plugins'))
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $plugins=get_plugins();

        if(isset($plugins[$plugin])&&is_plugin_active($plugin))
        {
            $requires_plugins=true;
        }
        else
        {
            $requires_plugins=false;
        }
        return $requires_plugins;
    }

    public function is_plugin_requires($plugin)
    {
        if( ! function_exists('get_plugin_data') || ! function_exists('get_plugins'))
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $requires_plugins=true;
        $plugins=get_plugins();

        if(isset($plugin['requires_plugins']))
        {
            foreach ( $plugin['requires_plugins'] as $slug=>$requires_plugin)
            {
                $plugin_slug=$requires_plugin['install']['plugin_slug'];
                if(isset($plugins[$plugin_slug])&&is_plugin_active($plugin_slug))
                {
                    $plugin_data = get_plugin_data( WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.$plugin_slug, false, false);
                    $version=$plugin_data['Version'];
                    if(version_compare($requires_plugin['install']['requires_version'],$version,'>'))
                    {
                        $requires_plugins=false;
                    }
                }
                else
                {
                    $requires_plugins=false;
                }
            }
            return $requires_plugins;
        }
        else
        {
            return $requires_plugins;
        }
    }

    public function check_addons_exist($addons,$folder)
    {
        foreach ($addons as $addon_slug=>$addon)
        {
            if($addon['active'])
            {
                if($this->is_local_addon_exist($folder,$addon['slug']))
                {
                    continue;
                }
                else
                {
                    return false;
                }
            }
        }
        return true;
    }

    public function get_addons_version($addons,$folder)
    {
        $max_version='0';
        foreach ($addons as $addon_slug=>$addon)
        {
            $version=$this->get_addon_version($folder,$addon['slug']);
            if($version!==false)
            {
                if(version_compare($version,$max_version,'>'))
                {
                    $max_version=$version;
                }
            }
        }
        if($max_version=='0')
        {
            return false;
        }
        else
        {
            return $max_version;
        }
    }

    public function is_local_addon_exist($folder,$slug)
    {
        if (is_dir(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder) && $dir_handle = opendir(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder))
        {
            while (false !== ($file = readdir($dir_handle)))
            {
                if (is_file(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder.'/' . $file) && preg_match('/\.php$/', $file))
                {
                    $addon_data = $this->get_addon_data(WPVIVID_BACKUP_PRO_PLUGIN_DIR .$folder. '/' . $file);
                    if (!empty($addon_data['WPvivid_addon']))
                    {
                        if(isset($addon_data['Name'])&&$addon_data['Name']==$slug)
                        {
                            return true;
                        }
                    }
                }
            }
            @closedir($dir_handle);
        }
        return false;
    }

    public function get_addon_version($folder,$slug)
    {
        if (is_dir(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder) && $dir_handle = opendir(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder))
        {
            while (false !== ($file = readdir($dir_handle)))
            {
                if (is_file(WPVIVID_BACKUP_PRO_PLUGIN_DIR . $folder.'/' . $file) && preg_match('/\.php$/', $file))
                {
                    $addon_data = $this->get_addon_data(WPVIVID_BACKUP_PRO_PLUGIN_DIR .$folder. '/' . $file);
                    if (!empty($addon_data['WPvivid_addon']))
                    {
                        if(isset($addon_data['Name'])&&$addon_data['Name']==$slug)
                        {
                            return $addon_data['Version'];
                        }
                    }
                }
            }
            @closedir($dir_handle);
        }
        return false;
    }

    public function get_plugin_status($info)
    {
        if( ! function_exists('get_plugin_data') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $require_install=false;
        $force_update=false;
        $update=false;

        $plugins=get_plugins();

        if($info['install']['is_plugin']==true)
        {
            $slug=$info['install']['plugin_slug'];
            if(isset($plugins[$slug]))
            {
                $install=true;

                $plugin_data = get_plugin_data( WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.$slug, false, false);
                $version=$plugin_data['Version'];

                if(isset($info['install']['requires_version']))
                {
                    if(version_compare($info['install']['requires_version'],$version,'>'))
                    {
                        $force_update=true;
                    }
                }

                if(version_compare($info['install']['version'],$version,'>'))
                {
                    $update=true;
                }
            }
            else
            {
                $install=false;
            }

            $active=$info['active'];
        }
        else
        {
            if(isset($info['install']['data'])&&isset($info['install']['data']['addons']))
            {
                $addons=$info['install']['data']['addons'];
            }
            else
            {
                $addons=array();
            }

            if(empty($addons))
            {
                return false;
            }

            /*
            if($this->check_addons_exist($addons,$info['install']['data']['folder']))
            {
                $install=true;
            }
            else
            {
                $install=false;
            }*/

            $active=false;
            $install=false;

            foreach ($addons as $addon)
            {
                if($addon['active'])
                {
                    if($this->is_local_addon_exist($info['install']['data']['folder'],$addon['slug']))
                    {
                        $install=true;
                    }
                    else
                    {
                        $install=false;
                    }
                    $active=true;
                    break;
                }
            }

            if($install==true)
            {
                foreach ($addons as $addon)
                {
                    if($addon['active'])
                    {
                        $version=$this->get_addon_version($info['install']['data']['folder'],$addon['slug']);
                        if(isset($addon['requires_version']))
                        {
                            if(version_compare($info['requires_version'],$version,'>'))
                            {
                                $force_update=true;
                                break;
                            }
                        }

                        if(version_compare($addon['version'],$version,'>'))
                        {
                            $update=true;
                            break;
                        }
                    }
                }
            }

            if(isset($info['requires_plugins']))
            {
                foreach ( $info['requires_plugins'] as $requires_plugin)
                {
                    $slug=$requires_plugin['install']['plugin_slug'];
                    if(isset($plugins[$slug])&&isset($requires_plugin['install']['requires_version']))
                    {
                        $plugin_data = get_plugin_data( WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.$slug, false, false);
                        $version=$plugin_data['Version'];
                        if(version_compare($requires_plugin['install']['requires_version'],$version,'>'))
                        {
                            $require_install=true;
                            break;
                        }
                    }
                    else
                    {
                        $require_install=true;
                        break;
                    }
                }
            }
            else
            {
                $require_install=false;
            }
        }

        //$install
        if(!$active)
        {
            $ret['status']='Not available';
            $ret['action']='Not available';
            if($install)
            {
                $ret['delete']=true;
            }
            else
            {
                $ret['delete']=false;
            }
            return $ret;
        }

        if($install)
        {
            if($force_update)
            {
                $ret['status']='Force-update';
                $ret['action']='Update';
                $ret['delete']=false;
                return $ret;
            }

            if($require_install)
            {
                $ret['status']='Un-installed';
                $ret['action']='Install';
                $ret['delete']=false;
                return $ret;
            }

            if($update)
            {
                $ret['status']='Installed';
                if($info['install']['is_plugin']==true)
                {
                    if(is_plugin_active($info['install']['plugin_slug'])===false)
                    {
                        $ret['status']='Inactive';
                    }
                }
                if(isset($info['requires_plugins']))
                {
                    if($this->is_plugin_requires($info)===false)
                    {
                        $ret['status']='Inactive';
                    }
                }
                $ret['action']='Update';
                $ret['delete']=true;
                return $ret;
            }
            else
            {
                $ret['status']='Installed';
                if($info['install']['is_plugin']==true)
                {
                    if(is_plugin_active($info['install']['plugin_slug'])===false)
                    {
                        $ret['status']='Inactive';
                    }
                }
                if(isset($info['requires_plugins']))
                {
                    if($this->is_plugin_requires($info)===false)
                    {
                        $ret['status']='Inactive';
                    }
                }
                $ret['action']='Up to date';
                $ret['delete']=true;
                return $ret;
            }
        }
        else
        {
            $ret['status']='Un-installed';
            $ret['action']='Install';
            $ret['delete']=false;
            return $ret;
        }
    }

    public function get_plugin_version($info)
    {
        if($info['install']['is_plugin'])
        {
            if( ! function_exists('get_plugin_data') )
            {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            $plugins=get_plugins();

            $slug=$info['install']['plugin_slug'];
            if(isset($plugins[$slug]))
            {
                $install=true;
            }
            else
            {
                $install=false;
            }

            if($install)
            {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.$slug, false, false);
                return $plugin_data['Version'];
            }
            else
            {
                return 'NULL';
            }
        }
        else
        {
            if(isset($info['install']['data'])&&isset($info['install']['data']['addons']))
            {
                $addons=$info['install']['data']['addons'];
            }
            else
            {
                $addons=array();
            }

            if(empty($addons))
            {
                return 'NULL';
            }

            if($this->check_addons_exist($addons,$info['install']['data']['folder']))
            {
                $version=$this->get_addons_version($addons,$info['install']['data']['folder']);
                if($version===false)
                {
                    return 'NULL';
                }
                else
                {
                    return $version;
                }
            }
            else
            {
                return 'NULL';
            }
        }
    }

    public function get_plugin_latest_version($info)
    {
        if($info['install']['is_plugin'])
        {
            if(isset($info['install']['version']))
            {
                return $info['install']['version'];
            }
            else
            {
                return 'NULL';
            }
        }
        else
        {
            if(isset($info['install']['data'])&&isset($info['install']['data']['addons']))
            {
                $addons=$info['install']['data']['addons'];
            }
            else
            {
                $addons=array();
            }

            if(empty($addons))
            {
                return 'NULL';
            }

            $max_version='0';

            foreach ($addons as $addon)
            {
                $version=$addon['version'];
                if(version_compare($version,$max_version,'>'))
                {
                    $max_version=$version;
                }
            }

            if($max_version=='0')
            {
                return 'NULL';
            }
            else
            {
                return $max_version;
            }
        }

    }

    public function get_plugin_requires($info)
    {
        if( ! function_exists('get_plugins') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugins=get_plugins();

        if(isset($info['requires_plugins']))
        {
            $require_plugins=array();
            foreach ( $info['requires_plugins'] as $requires_plugin)
            {
                $slug=$requires_plugin['install']['plugin_slug'];
                $require_plugins[$slug]=$requires_plugin;
                if(isset($plugins[$slug]))
                {
                    $plugin_data = get_plugin_data( WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.$slug, false, false);
                    $version=$plugin_data['Version'];
                    if(version_compare($requires_plugin['install']['requires_version'],$version,'>'))
                    {
                        $require_plugins[$slug]['status']='Force-update';
                    }
                    else
                    {
                        $require_plugins[$slug]['status']='Installed';
                    }
                }
                else
                {
                    $require_plugins[$slug]['status']='Un-installed';
                }
            }

            return $require_plugins;
        }
        else
        {
            return false;
        }
    }

    public function is_plugin_free($info)
    {
        if(isset($info['install']))
        {
            if(isset($info['install']['is_free']))
            {
                return $info['install']['is_free'];
            }
        }
        return false;
    }

    public function is_plugin_install_available($plugin)
    {
        if($plugin['install']['is_plugin'])
        {
            $active=$plugin['active'];
        }
        else
        {
            if(isset($plugin['install']['data'])&&isset($plugin['install']['data']['addons']))
            {
                $addons=$plugin['install']['data']['addons'];
            }
            else
            {
                $addons=array();
            }

            if(empty($addons))
            {
                return false;
            }

            $active=false;

            foreach ($addons as $addon)
            {
                if($addon['active'])
                {
                    $active=true;
                    break;
                }
            }
        }


        return $active;
    }

    public function get_requires_plugins($plugin)
    {
        $requires_plugins=array();
        $plugins=get_plugins();

        if( ! function_exists('get_plugin_data') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        if(isset($plugin['requires_plugins']))
        {
            foreach ( $plugin['requires_plugins'] as $slug=>$requires_plugin)
            {
                $plugin_slug=$requires_plugin['install']['plugin_slug'];
                if(isset($plugins[$plugin_slug]))
                {
                    $plugin_data = get_plugin_data( WP_PLUGIN_DIR .DIRECTORY_SEPARATOR.$plugin_slug, false, false);
                    $version=$plugin_data['Version'];
                    if(version_compare($requires_plugin['install']['requires_version'],$version,'>'))
                    {
                        $requires_plugins[]=$requires_plugin;
                    }
                }
                else
                {
                    $requires_plugins[]=$requires_plugin;
                }
            }
            return $requires_plugins;
        }
        else
        {
            return array();
        }
    }
}