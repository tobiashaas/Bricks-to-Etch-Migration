<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Encrypt_DB
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class WPvivid_Encrypt_DB
{
    public function __construct()
    {
        add_filter('wpvivid_get_zip_object_class_ex', array($this, 'get_zip_object_class'),11,2);
    }

    public function get_zip_object_class($obj, $data)
    {
        global $wpvivid_backup_pro;

        $is_type_db = false;
        $is_type_db = apply_filters('wpvivid_check_type_database', $is_type_db, $data);
        if($is_type_db)
        {
            $general_setting=WPvivid_Setting::get_setting(true, "");
            if(isset($general_setting['options']['wpvivid_common_setting']['encrypt_db'])&&$general_setting['options']['wpvivid_common_setting']['encrypt_db'] == '1')
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('use encrypt db ','notice');

                return 'WPvivid_Encrypt_DB_PclZip_Class';
            }
        }
        return $obj;
    }
}

class WPvivid_Encrypt_DB_PclZip_Class
{
    public function zip($name,$files,$options,$json_info=false)
    {
        global $wpvivid_backup_pro;

        $ret_db=$this->encrypt_db($name,$files,$options);

        if($ret_db['result']=='success')
        {
            $files=array();
            $files[]=$ret_db['file_path'];
        }
        else
        {
            return $ret_db;
        }

        if(file_exists($name))
            @unlink($name);

        if (!class_exists('WPvivid_PclZip'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';
        $archive = new WPvivid_PclZip($name);

        if(isset($options['compress']['no_compress']))
        {
            $no_compress=$options['compress']['no_compress'];
        }
        else
        {
            $no_compress=1;
        }

        if(isset($options['compress']['use_temp_file']))
        {
            $use_temp_file=1;
        }
        else
        {
            $use_temp_file=0;
        }

        if(isset($options['compress']['use_temp_size']))
        {
            $use_temp_size=$options['compress']['use_temp_size'];
        }
        else
        {
            $use_temp_size=16;
        }

        if(isset($options['root_path']))
        {
            $replace_path=$options['root_path'];
        }
        else if(isset($options['root_flag']))
        {
            $replace_path=$this->get_root_flag_path($options['root_flag']);
        }
        else
        {
            $replace_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        }

        if($json_info!==false)
        {
            $temp_path = dirname($name).DIRECTORY_SEPARATOR.'wpvivid_package_info.json';
            if(file_exists($temp_path))
            {
                @unlink($temp_path);
            }
            $json_info['php_version'] = phpversion();
            global $wpdb;
            $json_info['mysql_version'] = $wpdb->db_version();
            $json_info['is_crypt_ex']=1;
            file_put_contents($temp_path,print_r(json_encode($json_info),true));
            $archive -> add($temp_path,WPVIVID_PCLZIP_OPT_REMOVE_PATH,dirname($temp_path));
            @unlink($temp_path);
        }

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Prepare to zip files. file: '.basename($name),'notice');

        if($no_compress)
        {
            if($use_temp_file==1)
            {
                if($use_temp_size!=0)
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,$use_temp_size);
                }
                else
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_ON);
                }
            }
            else
            {
                $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_OFF);
            }
        }
        else
        {
            if($use_temp_file==1)
            {
                if($use_temp_size!=0)
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,$use_temp_size);
                }
                else
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_TEMP_FILE_ON);
                }
            }
            else
            {
                $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_TEMP_FILE_OFF);
            }
        }

        if(!$ret)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Failed to add zip files, error: '.$archive->errorInfo(true),'notice');
            $size=size_format(disk_free_space(dirname($name)),2);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('disk_free_space : '.$size,'notice');
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$archive->errorInfo(true));
        }

        $size=filesize($name);
        if($size===false)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Failed to add zip files, error: file not found after backup success','error');
            $size=size_format(disk_free_space(dirname($name)),2);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('disk_free_space : '.$size,'notice');
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'The file compression failed while backing up becuase of '.$name.' file not found. Please try again. The available disk space: '.$size.'.');
        }
        else if($size==0)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Failed to add zip files, error: file size 0B after backup success','error');
            $size=size_format(disk_free_space(dirname($name)),2);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('disk_free_space : '.$size,'notice');
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'The file compression failed while backing up. The size of '.$name.' file is 0. Please make sure there is an enough disk space to backup. Then try again. The available disk space: '.$size.'.');
        }

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Adding zip files completed.'.basename($name).', filesize: '.size_format(filesize($name),2),'notice');
        $file_data = array();
        $file_data['file_name'] = basename($name);
        $file_data['size'] = filesize($name);

        @unlink($ret_db['file_path']);
        return array('result'=>WPVIVID_PRO_SUCCESS,'file_data'=>$file_data);
    }

    public function encrypt_db($name,$files,$options)
    {
        global $wpvivid_backup_pro;

        $general_setting=WPvivid_Setting::get_setting(true, "");
        $password=$general_setting['options']['wpvivid_common_setting']['encrypt_db_password'];
        $crypt=new WPvivid_Crypt_File($password);

        if(file_exists($name))
            @unlink($name);

        if (!class_exists('WPvivid_PclZip'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';
        $archive = new WPvivid_PclZip($name);

        if(isset($options['compress']['no_compress']))
        {
            $no_compress=$options['compress']['no_compress'];
        }
        else
        {
            $no_compress=1;
        }

        if(isset($options['compress']['use_temp_file']))
        {
            $use_temp_file=1;
        }
        else
        {
            $use_temp_file=0;
        }

        if(isset($options['compress']['use_temp_size']))
        {
            $use_temp_size=$options['compress']['use_temp_size'];
        }
        else
        {
            $use_temp_size=16;
        }

        if(isset($options['root_path']))
        {
            $replace_path=$options['root_path'];
        }
        else if(isset($options['root_flag']))
        {
            $replace_path=$this->get_root_flag_path($options['root_flag']);
        }
        else
        {
            $replace_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        }

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start encrypt db ','notice');

        if($no_compress)
        {
            if($use_temp_file==1)
            {
                if($use_temp_size!=0)
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,$use_temp_size);
                }
                else
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_ON);
                }
            }
            else
            {
                $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_OFF);
            }
        }
        else
        {
            if($use_temp_file==1)
            {
                if($use_temp_size!=0)
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,$use_temp_size);
                }
                else
                {
                    $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_TEMP_FILE_ON);
                }
            }
            else
            {
                $ret = $archive -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_CB_PRE_ADD,'wpvivid_function_per_add_callback',WPVIVID_PCLZIP_OPT_TEMP_FILE_OFF);
            }
        }

        if(!$ret)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Failed to add zip files, error: '.$archive->errorInfo(true),'notice');
            $size=size_format(disk_free_space(dirname($name)),2);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('disk_free_space : '.$size,'notice');
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>$archive->errorInfo(true));
        }

        $size=filesize($name);
        if($size===false)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Failed to add zip files, error: file not found after backup success','error');
            $size=size_format(disk_free_space(dirname($name)),2);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('disk_free_space : '.$size,'notice');
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'The file compression failed while backing up becuase of '.$name.' file not found. Please try again. The available disk space: '.$size.'.');
        }
        else if($size==0)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Failed to add zip files, error: file size 0B after backup success','error');
            $size=size_format(disk_free_space(dirname($name)),2);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('disk_free_space : '.$size,'notice');
            return array('result'=>WPVIVID_PRO_FAILED,'error'=>'The file compression failed while backing up. The size of '.$name.' file is 0. Please make sure there is an enough disk space to backup. Then try again. The available disk space: '.$size.'.');
        }

        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Encrypt db success','notice');

        $ret=$crypt->encrypt($name);

        return $ret;
    }

    public function get_root_flag_path($flag)
    {
        $path='';
        if($flag==WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT)
        {
            $path=WP_CONTENT_DIR;
        }
        else if($flag==WPVIVID_PRO_BACKUP_ROOT_CUSTOM)
        {
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        }
        else if($flag==WPVIVID_PRO_BACKUP_ROOT_WP_ROOT)
        {
            $path=ABSPATH;
        }
        return $path;
    }
}