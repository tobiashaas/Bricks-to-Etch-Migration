<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Load_Admin_Remote
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR')) {
    die;
}

class WPvivid_Load_Admin_Remote
{
    public function __construct()
    {
        if(is_admin())
        {
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-amazons3-plus-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-b2-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-ftpclass-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-ftpclass-2-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-google-drive-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-nextcloud-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-one-drive-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-one-drive-with-shared-drives-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-pcloud-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-s3compat-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-sftpclass-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-wasabi-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-webdav-addon.php';
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-dropbox-addon.php';
            $remote=new WPvivid_Remote_collection_addon();
            $remote->load_hooks();
        }
    }

    public function load_file()
    {
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-amazons3-plus-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-b2-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-ftpclass-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-ftpclass-2-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-google-drive-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-nextcloud-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-one-drive-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-one-drive-with-shared-drives-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-pcloud-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-s3compat-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-sftpclass-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-wasabi-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-webdav-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-dropbox-addon.php';
    }

    public function load()
    {
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-amazons3-plus-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-b2-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-ftpclass-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-ftpclass-2-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-google-drive-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-nextcloud-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-one-drive-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-one-drive-with-shared-drives-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-pcloud-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-s3compat-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-sftpclass-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-wasabi-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-webdav-addon.php';
        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR .'addons2/backup_pro/class-wpvivid-dropbox-addon.php';
        $remote=new WPvivid_Remote_collection_addon();
        $remote->load_hooks();
    }
}

class WPvivid_Remote_collection_addon
{
    private $remote_collection=array();

    public function __construct()
    {
        $this->remote_collection=$this->init_remotes($this->remote_collection);
        //$this->remote_collection=apply_filters('wpvivid_remote_register',$this->remote_collection);
        //$this->load_hooks();
    }

    public function get_remote($remote)
    {
        if(is_array($remote)&&array_key_exists('type',$remote)&&array_key_exists($remote['type'],$this->remote_collection))
        {
            $class_name =$this->remote_collection[$remote['type']];

            if(class_exists($class_name))
            {
                $object = new $class_name($remote);
                return $object;
            }
        }
        $object = new $this ->remote_collection['default']();
        return  $object;
    }

    public function add_remote($remote_option)
    {
        $remote=$this->get_remote($remote_option);

        $ret=$remote->sanitize_options();

        if($ret['result']=='success')
        {
            $remote_option=$ret['options'];
            $ret=$remote->test_connect();
            if($ret['result']=='success')
            {
                $ret=array();
                $default=$remote_option['default'];
                $id=WPvivid_Setting::add_remote_options($remote_option);
                if($default==1)
                {
                    $remote_ids[]=$id;
                    $remote_ids=apply_filters('wpvivid_before_add_user_history',$remote_ids);
                    WPvivid_Setting::update_user_history('remote_selected',$remote_ids);
                    $schedule_data = WPvivid_Setting::get_option('wpvivid_schedule_setting');
                    if(!empty($schedule_data['enable'])) {
                        if ($schedule_data['enable'] == 1) {
                            $schedule_data['backup']['local'] = 0;
                            $schedule_data['backup']['remote'] = 1;
                        }
                        WPvivid_Setting::update_option('wpvivid_schedule_setting', $schedule_data);
                    }
                }
                $ret['result']=WPVIVID_SUCCESS;
            }
            else {
                $id = uniqid('wpvivid-');
                $log_file_name = $id . '_add_remote';
                $log = new WPvivid_Log_Ex_addon();
                $log->CreateLogFile($log_file_name, 'no_folder', 'Add Remote Test Connection');
                $log->WriteLog('Remote Type: '.$remote_option['type'], 'notice');
                if(isset($ret['error'])) {
                    $log->WriteLog($ret['error'], 'notice');
                }
                $log->CloseFile();
                WPvivid_error_log::create_error_log($log->log_file);
            }
        }

        return $ret;
    }

    public function update_remote($id,$remote_option)
    {
        $remote=$this->get_remote($remote_option);

        $old_remote=WPvivid_Setting::get_remote_option($id);

        $ret=$remote->sanitize_options($old_remote['name']);
        if($ret['result']=='success')
        {
            $remote_option=$ret['options'];
            $ret=$remote->test_connect();
            if($ret['result']=='success')
            {
                $ret=array();
                WPvivid_Setting::update_remote_option($id,$remote_option);
                $ret['result']=WPVIVID_SUCCESS;
            }
        }

        return $ret;
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection['amazons3'] = 'WPvivid_AMAZONS3Class_addon';
        $remote_collection['b2'] = 'WPvivid_B2_addon';
        $remote_collection['dropbox']='WPvivid_Dropbox_addon';
        $remote_collection['sftp']='WPvivid_SFTPClass_addon';
        $remote_collection['ftp']='WPvivid_FTPClass_addon';
        $remote_collection['ftp2']='WPvivid_FTPClass_2_addon';
        $remote_collection['googledrive'] = 'Wpvivid_Google_drive_addon';
        $remote_collection['nextcloud'] = 'WPvivid_Nextcloud_addon';
        $remote_collection['onedrive'] = 'WPvivid_one_drive_addon';
        $remote_collection['onedrive_shared'] = 'WPvivid_one_drive_with_shared_drives_addon';
        $remote_collection['pCloud']='WPvivid_pCloud_addon';
        $remote_collection['s3compat'] = 'Wpvivid_S3Compat_addon';
        $remote_collection['webdav'] = 'WPvivid_WebDav_addon';
        $remote_collection['wasabi'] = 'Wpvivid_WasabiS3_addon';
        $remote_collection['send_to_site_ex'] = 'WPvivid_Send_to_site_addon';
        return $remote_collection;
    }

    public function load_hooks()
    {
        foreach ($this->remote_collection as $class_name)
        {
            if($class_name=='WPvivid_Send_to_site_addon')
                continue;
            $object = new $class_name();
        }
    }
}