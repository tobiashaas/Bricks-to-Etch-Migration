<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * No_need_load: yes
 * Interface Name: WPvivid_Log_Ex_addon
 */

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Log_Ex_addon
{
    public $log_file;
    public $log_file_handle;

    public function __construct()
    {
        $this->log_file_handle=false;
    }

    public function CreateLogFile($file_name,$type,$describe,$override=true)
    {
        if($type=='has_folder')
        {
            $this->log_file=$file_name;
        }
        else
        {
            $this->log_file=$this->GetSaveLogFolder().$file_name.'_log.txt';
        }
        if(file_exists($this->log_file) && $override)
        {
            @unlink( $this->log_file);
        }
        $this->log_file_handle = fopen($this->log_file, 'a');
        $offset=get_option('gmt_offset');
        $time =date("Y-m-d H:i:s",time()+$offset*60*60);
        $text='Log created: '.$time."\n";
        $text.='Type: '.$describe."\n";
        fwrite($this->log_file_handle,$text);

        return $this->log_file;
    }

    public function OpenLogFile($file_name,$type='no_folder',$delete=0)
    {
        if($type=='has_folder')
        {
            $this->log_file=$file_name;
        }
        else
        {
            $this->log_file=$this->GetSaveLogFolder().$file_name.'_log.txt';
        }
        if($delete==1)
        {
            unlink( $this->log_file);
        }
        $this->log_file_handle = fopen($this->log_file, 'a');

        return $this->log_file;
    }

    public function WriteLog($log,$type)
    {
        if ($this->log_file_handle)
        {
            $offset=get_option('gmt_offset');
            $time =date("Y-m-d H:i:s",time()+$offset*60*60);
            $text='['.$time.']'.'['.$type.']'.$log."\n";
            fwrite($this->log_file_handle,$text );
        }
    }

    public function CloseFile()
    {
        if ($this->log_file_handle)
        {
            fclose($this->log_file_handle);
            $this->log_file_handle=false;
        }
    }

    public function GetSaveLogFolder()
    {
        $options = get_option('wpvivid_common_setting');

        if(!isset($options['log_save_location']))
        {
            //WPvivid_Setting::set_default_common_option();
            $options['log_save_location']=WPVIVID_DEFAULT_LOG_DIR;
            update_option('wpvivid_common_setting', $options, 'no');

            $options = get_option('wpvivid_common_setting');
        }

        $log_path = WPvivid_Custom_Interface_addon::wpvivid_get_backuprestore_log_folder();
        if(!is_dir($log_path))
        {
            @mkdir($log_path,0777,true);
            @fopen($log_path.DIRECTORY_SEPARATOR.'index.html', 'x');
            $tempfile=@fopen($log_path.DIRECTORY_SEPARATOR.'.htaccess', 'x');
            if($tempfile)
            {
                $text="deny from all";
                fwrite($tempfile,$text );
            }
        }

        return $log_path.DIRECTORY_SEPARATOR;
    }

    public function WriteLogHander()
    {
        if ($this->log_file_handle)
        {
            global $wp_version;
            global $wpdb;

            $sapi_type=php_sapi_name();
            if($sapi_type=='cgi-fcgi'||$sapi_type==' fpm-fcgi') {
                $fcgi='On';
            }
            else {
                $fcgi='Off';
            }

            $options=get_option('wpvivid_common_setting');
            if(isset($options['max_execution_time'])) {
                $max_execution_time=$options['max_execution_time'];
            }
            else {
                $max_execution_time=WPVIVID_MAX_EXECUTION_TIME;
            }

            $log='server info fcgi:'.$fcgi.' max execution time: '.$max_execution_time.' wp version:'.$wp_version.' php version:'.phpversion().' db version:'.$wpdb->db_version().' php ini:safe_mode:'.ini_get('safe_mode').' ';
            $log.='memory_limit:'.ini_get('memory_limit').' memory_get_usage:'.size_format(memory_get_usage(),2).' memory_get_peak_usage:'.size_format(memory_get_peak_usage(),2);
            $log.=' extensions:';
            $loaded_extensions = get_loaded_extensions();
            if(!in_array('PDO', $loaded_extensions))
            {
                $log.='PDO not enabled ';
            }
            else
            {
                $log.='PDO enabled ';
            }
            if(!in_array('curl', $loaded_extensions))
            {
                $log.='curl not enabled ';
            }
            else
            {
                $log.='curl enabled ';
            }

            if(!in_array('zlib', $loaded_extensions)) {
                $log .= 'zlib not enabled ';
            }
            else
            {
                $log.='zlib enabled ';
            }

            $log.=' ';
            if(is_multisite())
            {
                $log.=' is_multisite:1';
            }
            else
            {
                $log.=' is_multisite:0';
            }

            $offset=get_option('gmt_offset');
            $time =date("Y-m-d H:i:s",time()+$offset*60*60);
            $text='['.$time.']'.'[notice]'.$log."\n";
            fwrite($this->log_file_handle,$text );
        }
    }

    public function get_error_log()
    {
        $dir=$this->GetSaveLogFolder();
        $dir=$dir.'error';
        $files=array();
        $handler=opendir($dir);
        if($handler === false){
            return $files;
        }
        $regex='#^wpvivid.*_log.txt#';
        while(($filename=readdir($handler))!==false)
        {
            if($filename != "." && $filename != "..")
            {
                if(is_dir($dir.$filename))
                {
                    continue;
                }
                else{
                    if(preg_match($regex,$filename))
                    {
                        $files[] = $dir.DIRECTORY_SEPARATOR.$filename;
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);
        return $files;
    }
}