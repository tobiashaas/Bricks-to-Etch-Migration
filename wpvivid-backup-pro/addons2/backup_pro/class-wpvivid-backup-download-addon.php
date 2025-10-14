<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_Backup_Download_Addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Backup_Download_Addon
{
    private $task;

    public function __construct()
    {
        add_action('wp_ajax_wpvivid_init_new_download_page', array($this, 'init_new_download_page'));
        add_action('wp_ajax_wpvivid_download_file2', array($this, 'download_file2'));
        add_action('wp_ajax_wpvivid_download_types', array($this, 'download_types'));
        add_action('wp_ajax_wpvivid_download_files', array($this, 'download_files'));
        add_action('wp_ajax_wpvivid_download_incremental_backup', array($this, 'download_incremental_backup'));
        add_action('wp_ajax_wpvivid_get_ready_download_files', array($this, 'get_ready_download_files'));
        add_action('wp_ajax_wpvivid_get_ready_download_files_url', array($this, 'get_ready_download_files_url'));
        //
        add_action('wp_ajax_wpvivid_delete_tmp_download_files', array($this, 'delete_tmp_download_files'));

        add_action('wp_ajax_wpvivid_prepare_download_file', array($this, 'prepare_download_file'));
        add_action('wp_ajax_wpvivid_prepare_download_types', array($this, 'prepare_download_types'));
        add_action('wp_ajax_wpvivid_prepare_download_files', array($this, 'prepare_download_files'));
        add_action('wp_ajax_wpvivid_prepare_incremental_backup', array($this, 'prepare_incremental_backup'));
        //
        add_action('wp_ajax_wpvivid_incremental_backup_scan', array($this, 'incremental_backup_scan'));
        //
        add_action('wp_ajax_wpvivid_get_prepare_download_progress', array($this, 'get_prepare_download_progress'));
        add_action('admin_head', array($this, 'my_admin_custom_styles'));
    }

    public function my_admin_custom_styles()
    {
        $output_css = '<style type="text/css">    
                        .column-wpvivid_i_date { width:14% }
                        .column-wpvivid_i_type { width:12% }
                        .column-wpvivid_i_file_size { width:10% }                       
                        </style>';
        echo $output_css;
    }

    public function init_new_download_page()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
            {
                $backup_id = sanitize_key($_POST['backup_id']);
                $backup_list=new WPvivid_New_BackupList();
                $backup = $backup_list->get_backup_by_id($backup_id);

                if ($backup === false)
                {
                    $ret['result'] = WPVIVID_PRO_FAILED;
                    $ret['error'] = 'backup id not found';
                    echo json_encode($ret);
                    die();
                }
                $ret['is_old_remote_backup']=false;
                $ret['is_incremental']=false;

                if($backup['type']=='Incremental')
                {
                    $incremental_data=$this->get_incremental_data($backup);
                    if($incremental_data===false)
                    {
                        if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
                        {
                            $types=$backup['backup_info']['types'];
                        }
                        else
                        {
                            $types=$this->get_backup_type_list($backup);
                        }

                        if(!empty($backup['remote']))
                        {
                            $ret['is_old_remote_backup']=$this->is_old_remote_backup($backup);
                        }
                        $ret['types']=$types;
                        $ret['backup']=$backup;
                        $files_list = new WPvivid_Types_Files_List($types,$backup_id);

                        ob_start();
                        $files_list->display();
                        $ret['list'] = ob_get_clean();
                    }
                    else
                    {
                        $files_list = new WPvivid_Incremental_Files_List();
                        $files_list->set_files_list($incremental_data,$backup_id);
                        $files_list->prepare_items();
                        ob_start();
                        $files_list->display();
                        $ret['list'] = ob_get_clean();
                        $ret['is_incremental']=true;
                        $ret['full_backup_start_time']=date("F-d-Y H:i",$incremental_data['full_backup_start_time']);
                        $ret['start_date']=date("Y-m-d",$incremental_data['full_backup_start_time']);
                        $ret['end_date']=date("Y-m-d",$incremental_data['last_backup_time']);

                        $ret['start_time']=date("H:i",$incremental_data['full_backup_start_time']);
                        $ret['end_time']=date("H:i",$incremental_data['last_backup_time']+60);
                    }
                }
                else
                {
                    if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
                    {
                        $types=$backup['backup_info']['types'];
                    }
                    else
                    {
                        $types=$this->get_backup_type_list($backup);
                    }

                    if(!empty($backup['remote']))
                    {
                        $ret['is_old_remote_backup']=$this->is_old_remote_backup($backup);
                    }
                    $ret['types']=$types;
                    $ret['backup']=$backup;
                    $files_list = new WPvivid_Types_Files_List($types,$backup_id);

                    ob_start();
                    $files_list->display();
                    $ret['list'] = ob_get_clean();
                }

                delete_option('wpvivid_ready_to_download_files');
                delete_option('wpvivid_prepare_download_files_task');
                $ret['result'] = 'success';
                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function incremental_backup_scan()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['start_date']) && !empty($_POST['start_date']) && is_string($_POST['start_date']))
        {
            $start_date = sanitize_key($_POST['start_date']);
        }
        else
        {
            die();
        }

        if (isset($_POST['start_time']) && !empty($_POST['start_time']) && is_string($_POST['start_time']))
        {
            $start_time = sanitize_key($_POST['start_time']);
        }
        else
        {
            die();
        }

        if (isset($_POST['end_date']) && !empty($_POST['end_date']) && is_string($_POST['end_date']))
        {
            $end_date = sanitize_key($_POST['end_date']);
        }
        else
        {
            die();
        }

        if (isset($_POST['end_time']) && !empty($_POST['end_time']) && is_string($_POST['end_time']))
        {
            $end_time = sanitize_key($_POST['end_time']);
        }
        else
        {
            die();
        }

        $backup_start_time=strtotime($start_date.' '.$start_time);
        $backup_end_time=strtotime($end_date.' '.$end_time);

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        if ($backup === false)
        {
            $ret['result'] = WPVIVID_PRO_FAILED;
            $ret['error'] = 'backup id not found';
            echo json_encode($ret);
            die();
        }

        $incremental_data=$this->get_incremental_data_ex($backup,$backup_start_time,$backup_end_time);

        if($incremental_data===false)
        {
            $ret['result'] = WPVIVID_PRO_FAILED;
            $ret['error'] = 'incremental data not found';
            echo json_encode($ret);
            die();
        }
        else
        {
            $files_list = new WPvivid_Incremental_Files_List();
            if(isset($_POST['page']))
            {
                $files_list->set_files_list($incremental_data,$backup_id,$_POST['page']);
            }
            else
            {
                $files_list->set_files_list($incremental_data,$backup_id);
            }

            $files_list->prepare_items();
            ob_start();
            $files_list->display();
            $ret['list'] = ob_get_clean();
        }


        $ret['result'] = 'success';
        echo json_encode($ret);

        die();
    }

    public function get_incremental_data($backup)
    {
        $find=false;
        $last_version=0;
        $incremental_data['full_backup_start_time']=0;
        $incremental_data['last_backup_time']=0;
        $incremental_data['incremental_backup_versions']=array();

        if(empty($backup['remote']))
        {
            $backup_item = new WPvivid_New_Backup_Item($backup);

            foreach ($backup['backup']['files'] as $file)
            {
                $file_info=$backup_item->get_file_info($file['file_name']);
                $data['file_name']=$file['file_name'];
                $data['size']=$file['size'];
                if(isset($file_info['version']))
                {
                    $find=true;
                    if($file_info['version']>=$last_version)
                    {
                        $incremental_data['last_backup_time']=$file_info['backup_time'];
                        $last_version=$file_info['version'];
                    }

                    if($file_info['version']==0)
                    {
                        $incremental_data['full_backup_start_time']=$file_info['backup_time'];
                    }

                    if(isset($incremental_data['incremental_backup_versions'][$file_info['version']]))
                    {
                        $data['version']=$file_info['version'];

                        $incremental_data['incremental_backup_versions'][$file_info['version']]['files'][]=$data;
                    }
                    else
                    {
                        $data['version']=$file_info['version'];

                        $incremental_data['incremental_backup_versions'][$file_info['version']]['backup_time']=$file_info['backup_time'];
                        $incremental_data['incremental_backup_versions'][$file_info['version']]['version']=$file_info['version'];
                        $incremental_data['incremental_backup_versions'][$file_info['version']]['files'][]=$data;
                    }
                }
            }

            if($find)
            {
                ksort($incremental_data['incremental_backup_versions']);
                return $incremental_data;
            }
            else
            {
                return false;
            }
        }
        else
        {
            foreach ($backup['backup']['files'] as $file)
            {
                $data['file_name']=$file['file_name'];
                $data['size']=$file['size'];
                $backup_time=0;

                if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file['file_name'],$matches))
                {
                    $date=$matches[0];
                    $time_array=explode('-',$date);
                    if(sizeof($time_array)>4)
                        $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    else
                        $time=$backup['date'];
                    $backup_time=strtotime($time);
                }

                $version=$backup_time;
                if($version>=$last_version)
                {
                    $incremental_data['last_backup_time']=$backup_time;
                    $last_version=$version;
                }

                if(isset($incremental_data['incremental_backup_versions'][$version]))
                {
                    $data['version']=$version;
                    $incremental_data['incremental_backup_versions'][$version]['files'][]=$data;
                }
                else
                {
                    $data['version']=$version;

                    $incremental_data['incremental_backup_versions'][$version]['backup_time']=$backup_time;
                    $incremental_data['incremental_backup_versions'][$version]['version']=$backup_time;
                    $incremental_data['incremental_backup_versions'][$version]['files'][]=$data;
                }
            }

            $min_key=min(array_keys($incremental_data['incremental_backup_versions']));
            $incremental_data['full_backup_start_time']=$incremental_data['incremental_backup_versions'][$min_key]['backup_time'];

            $min_incremental_backup_versions=$incremental_data['incremental_backup_versions'][$min_key];
            $min_incremental_backup_versions['version']=0;
            $incremental_data['incremental_backup_versions'][0]=$min_incremental_backup_versions;
            unset($incremental_data['incremental_backup_versions'][$min_key]);

            ksort($incremental_data['incremental_backup_versions']);
            return $incremental_data;
        }
    }

    public function get_incremental_data_ex($backup,$backup_start_time,$backup_end_time)
    {
        $find=false;
        $incremental_data['incremental_backup_versions']=array();

        if(empty($backup['remote']))
        {
            $backup_item = new WPvivid_New_Backup_Item($backup);

            foreach ($backup['backup']['files'] as $file)
            {
                $file_info=$backup_item->get_file_info($file['file_name']);
                $data['file_name']=$file['file_name'];
                $data['size']=$file['size'];
                if(isset($file_info['version']))
                {
                    $find=true;
                    if($file_info['version']>0)
                    {
                        if($file_info['backup_time']<$backup_start_time||$file_info['backup_time']>$backup_end_time)
                        {
                            continue;
                        }
                    }

                    if(isset($incremental_data['incremental_backup_versions'][$file_info['version']]))
                    {
                        $data['version']=$file_info['version'];

                        $incremental_data['incremental_backup_versions'][$file_info['version']]['files'][]=$data;
                    }
                    else
                    {
                        $data['version']=$file_info['version'];

                        $incremental_data['incremental_backup_versions'][$file_info['version']]['backup_time']=$file_info['backup_time'];
                        $incremental_data['incremental_backup_versions'][$file_info['version']]['version']=$file_info['version'];
                        $incremental_data['incremental_backup_versions'][$file_info['version']]['files'][]=$data;
                    }
                }
            }

            if($find)
            {
                return $incremental_data;
            }
            else
            {
                return false;
            }
        }
        else
        {
            foreach ($backup['backup']['files'] as $file)
            {
                $data['file_name']=$file['file_name'];
                $data['size']=$file['size'];
                $backup_time=0;

                if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file['file_name'],$matches))
                {
                    $date=$matches[0];
                    $time_array=explode('-',$date);
                    if(sizeof($time_array)>4)
                        $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    else
                        $time=$backup['date'];
                    $backup_time=strtotime($time);
                }

                $version=$backup_time;

                if(isset($incremental_data['incremental_backup_versions'][$version]))
                {
                    $data['version']=$version;
                    $incremental_data['incremental_backup_versions'][$version]['files'][]=$data;
                }
                else
                {
                    $data['version']=$version;

                    $incremental_data['incremental_backup_versions'][$version]['backup_time']=$backup_time;
                    $incremental_data['incremental_backup_versions'][$version]['version']=$backup_time;
                    $incremental_data['incremental_backup_versions'][$version]['files'][]=$data;
                }
            }

            $min_key=min(array_keys($incremental_data['incremental_backup_versions']));
            $incremental_data['full_backup_start_time']=$incremental_data['incremental_backup_versions'][$min_key]['backup_time'];

            $min_incremental_backup_versions=$incremental_data['incremental_backup_versions'][$min_key];
            $min_incremental_backup_versions['version']=0;
            $incremental_data['incremental_backup_versions'][0]=$min_incremental_backup_versions;
            unset($incremental_data['incremental_backup_versions'][$min_key]);

            ksort($incremental_data['incremental_backup_versions']);

            $tmp_versions=$incremental_data['incremental_backup_versions'];
            foreach ($tmp_versions as $version=>$data)
            {
                if($version>0)
                {
                    if($data['backup_time']<$backup_start_time||$data['backup_time']>$backup_end_time)
                    {
                        unset($incremental_data['incremental_backup_versions'][$version]);
                    }
                }
            }

            return $incremental_data;
        }
    }

    public function delete_tmp_download_files()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        if($this->is_merge($backup))
        {
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
            $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

            $backup_item = new WPvivid_New_Backup_Item($backup);

            foreach ($backup['backup']['files'] as $file)
            {
                $file_name = $file['file_name'];

                if(!file_exists($backup_item->get_backup_path($file_name)))
                {
                    continue;
                }

                $file_info=$backup_item->get_file_info($file_name);
                if(isset($file_info['has_child'])&&isset($file_info['child_file']))
                {
                    foreach ($file_info['child_file'] as $child_file_name=>$child_file_info)
                    {
                        if (file_exists($path.$child_file_name))
                        {
                            @unlink($path.$child_file_name);
                        }
                    }
                }
            }
        }

        $ret['result']='success';
        echo json_encode($ret);

        die();
    }

    public function download_file2()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['file_name']) && !empty($_POST['file_name']) && is_string($_POST['file_name']))
        {
            $file_name = sanitize_text_field($_POST['file_name']);
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $prepare=false;
        $file_data=$this->get_download_file_data($file_name,$backup);

        if (file_exists($path.$file_name))
        {
            if($file_data!==false)
            {
                if (filesize($path.$file_name) == $file_data['size'])
                {
                    $prepare=true;
                }
                else
                {
                    if(!empty($backup['remote']))
                    {
                        @unlink($path.$file_name);
                    }
                }
            }
        }

        $files[]=$file_name;
        update_option('wpvivid_ready_to_download_files',$files,'no');

        if($prepare)
        {
            $ret['result']='success';
            $ret['prepare']=true;
            echo json_encode($ret);
        }
        else
        {
            $ret['result']='success';
            $ret['prepare']=false;
            echo json_encode($ret);
        }


        die();
    }

    public function download_types()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['types']) && !empty($_POST['types']))
        {
            $types = $_POST['types'];
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $prepare=true;
        $files=array();

        if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
        {
            $backup_info=$backup['backup_info'];
        }
        else
        {
            $backup_info=$this->get_backup_type_list($backup);
        }

        if(empty($backup_info))
        {
            $ret['result']='success';
            $ret['prepare']=false;
            echo json_encode($ret);
            die();
        }

        if($this->is_merge($backup)&&$this->is_download_all_types($backup_info,$types))
        {
            foreach ($backup['backup']['files'] as $key => $file_data)
            {
                if (file_exists($path.$file_data['file_name']))
                {
                    if (filesize($path.$file_data['file_name']) == $file_data['size'])
                    {
                        $files[]=$file_data['file_name'];
                    }
                    else
                    {
                        $prepare=false;
                        if(!empty($backup['remote']))
                        {
                            @unlink($path.$file_data['file_name']);
                        }

                        $files[]=$file_data['file_name'];
                    }
                }
                else
                {
                    $prepare=false;
                    $files[]=$file_data['file_name'];
                }
            }
        }
        else
        {
            if(isset($backup_info['types']))
            {
                foreach ($backup_info['types'] as $type=>$info)
                {
                    if(array_key_exists($type,$types))
                    {
                        foreach ($info['files'] as $file_data)
                        {
                            if (file_exists($path.$file_data['file_name']))
                            {
                                if (filesize($path.$file_data['file_name']) == $file_data['size'])
                                {
                                    $files[]=$file_data['file_name'];
                                }
                                else
                                {
                                    $prepare=false;
                                    if(!empty($backup['remote']))
                                    {
                                        @unlink($path.$file_data['file_name']);
                                    }

                                    $files[]=$file_data['file_name'];
                                }
                            }
                            else
                            {
                                $prepare=false;
                                $files[]=$file_data['file_name'];
                            }
                        }
                    }
                }
            }
            else
            {
                foreach ($backup_info as $type=>$info)
                {
                    if(array_key_exists($type,$types))
                    {
                        foreach ($info['files'] as $file_data)
                        {
                            if (file_exists($path.$file_data['file_name']))
                            {
                                if (filesize($path.$file_data['file_name']) == $file_data['size'])
                                {
                                    $files[]=$file_data['file_name'];
                                }
                                else
                                {
                                    $prepare=false;
                                    if(!empty($backup['remote']))
                                    {
                                        @unlink($path.$file_data['file_name']);
                                    }

                                    $files[]=$file_data['file_name'];
                                }
                            }
                            else
                            {
                                $prepare=false;
                                $files[]=$file_data['file_name'];
                            }
                        }
                    }
                }
            }
        }

        update_option('wpvivid_ready_to_download_files',$files,'no');
        if($prepare)
        {
            $ret['result']='success';
            $ret['prepare']=true;
            $ret['files']=$files;
            echo json_encode($ret);
        }
        else
        {
            $ret['result']='success';
            $ret['prepare']=false;
            echo json_encode($ret);
        }

        die();
    }

    public function download_files()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['files']) && !empty($_POST['files']))
        {
            $download_files = $_POST['files'];
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $prepare=true;
        $files=array();
        foreach ($backup['backup']['files'] as $key => $file_data)
        {
            if(in_array($file_data['file_name'],$download_files))
            {
                if (file_exists($path.$file_data['file_name']))
                {
                    if (filesize($path.$file_data['file_name']) == $file_data['size'])
                    {
                        $files[]=$file_data['file_name'];
                    }
                    else
                    {
                        $prepare=false;
                        if(!empty($backup['remote']))
                        {
                            @unlink($path.$file_data['file_name']);
                        }

                        $files[]=$file_data['file_name'];
                    }
                }
                else
                {
                    $prepare=false;
                    $files[]=$file_data['file_name'];
                }
            }

        }

        update_option('wpvivid_ready_to_download_files',$files,'no');

        if($prepare)
        {
            $ret['result']='success';
            $ret['prepare']=true;
            $ret['files']=$files;
            echo json_encode($ret);
        }
        else
        {
            $ret['result']='success';
            $ret['prepare']=false;
            echo json_encode($ret);
        }

        die();
    }

    public function download_incremental_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['full_backup_check']))
        {
            $full_backup_check = $_POST['full_backup_check'];
            if($full_backup_check=='true')
            {
                $full_backup_check=true;
            }
            else
            {
                $full_backup_check=false;
            }
        }
        else
        {
            die();
        }

         if (isset($_POST['incremental_backup_check']))
         {
             $incremental_backup_check = $_POST['incremental_backup_check'];
             if($incremental_backup_check=='true')
             {
                 $incremental_backup_check=true;
             }
             else
             {
                 $incremental_backup_check=false;
             }
         }
         else
         {
             die();
         }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $download_files=$this->get_incremental_files($backup,$full_backup_check,$incremental_backup_check);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $prepare=true;
        $files=array();
        foreach ($backup['backup']['files'] as $key => $file_data)
        {
            if(in_array($file_data['file_name'],$download_files))
            {
                if (file_exists($path.$file_data['file_name']))
                {
                    if (filesize($path.$file_data['file_name']) == $file_data['size'])
                    {
                        $files[]=$file_data['file_name'];
                    }
                    else
                    {
                        $prepare=false;
                        if(!empty($backup['remote']))
                        {
                            @unlink($path.$file_data['file_name']);
                        }

                        $files[]=$file_data['file_name'];
                    }
                }
                else
                {
                    $prepare=false;
                    $files[]=$file_data['file_name'];
                }
            }

        }

        update_option('wpvivid_ready_to_download_files',$files,'no');

        if(empty($files))
        {
            $ret['result']='failed';
            $ret['error']='No Incremental Backups available for download';
            echo json_encode($ret);
            die();
        }

        if($prepare)
        {
            $ret['result']='success';
            $ret['prepare']=true;
            $ret['files']=$files;
            echo json_encode($ret);
        }
        else
        {
            $ret['result']='success';
            $ret['prepare']=false;
            echo json_encode($ret);
        }

        die();
    }

    public function get_incremental_files($backup,$full_backup_check,$incremental_backup_check)
    {
        $files=array();

        if(empty($backup['remote']))
        {
            $backup_item = new WPvivid_New_Backup_Item($backup);
            foreach ($backup['backup']['files'] as $file)
            {
                $file_info=$backup_item->get_file_info($file['file_name']);
                $data['file_name']=$file['file_name'];
                $data['size']=$file['size'];
                if(isset($file_info['version']))
                {
                    if($file_info['version']==0)
                    {
                        if($full_backup_check)
                        {
                            $files[]=$file['file_name'];
                        }

                    }
                    else
                    {
                        if($incremental_backup_check)
                        {
                            $files[]=$file['file_name'];
                        }
                    }
                }
            }

            return $files;
        }
        else
        {
            $incremental_backup_versions=array();

            foreach ($backup['backup']['files'] as $file)
            {
                $data['file_name']=$file['file_name'];
                $data['size']=$file['size'];
                $backup_time=0;

                if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file['file_name'],$matches))
                {
                    $date=$matches[0];
                    $time_array=explode('-',$date);
                    if(sizeof($time_array)>4)
                        $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    else
                        $time=$backup['date'];
                    $backup_time=strtotime($time);
                }

                $version=$backup_time;

                if(isset($incremental_backup_versions[$version]))
                {
                    $data['version']=$version;
                    $incremental_backup_versions[$version]['files'][]=$data;
                }
                else
                {
                    $data['version']=$version;

                    $incremental_backup_versions[$version]['backup_time']=$backup_time;
                    $incremental_backup_versions[$version]['version']=$backup_time;
                    $incremental_backup_versions[$version]['files'][]=$data;
                }
            }

            $min_key=min(array_keys($incremental_backup_versions));

            $min_incremental_backup_versions=$incremental_backup_versions[$min_key];
            $min_incremental_backup_versions['version']=0;
            $incremental_backup_versions[0]=$min_incremental_backup_versions;
            unset($incremental_backup_versions[$min_key]);

            ksort($incremental_backup_versions);

            $files=array();

            if($full_backup_check)
            {
                foreach ( $incremental_backup_versions[0]['files'] as $file)
                {
                    $files[]=$file['file_name'];
                }
            }

            if($incremental_backup_check)
            {
                foreach ($incremental_backup_versions as $version=>$data)
                {
                    if($version==0)
                        continue;

                    foreach ( $data['files'] as $file)
                    {
                        $files[]=$file['file_name'];
                    }
                }
            }

            return $files;
        }

    }

    public function prepare_download_file()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['file_name']) && !empty($_POST['file_name']) && is_string($_POST['file_name']))
        {
            $file_name = sanitize_text_field($_POST['file_name']);
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $files=array();
        if(!file_exists($path.$file_name))
        {
            if($this->is_child_file($backup,$file_name))
            {
                $file['file_name']=$file_name;

                $file['need_extra']=true;
                $parent_file=$this->get_parent_file($backup,$file_name);
                if(empty($parent_file))
                {
                    $ret['result']='failed';
                    $ret['error']='get parent file failed';
                    $ret['test1']=$backup;
                    echo json_encode($ret);
                    die();
                }
                $file['parent_file']=$parent_file;
                if(file_exists($path.$parent_file)&&filesize($path.$parent_file)===$this->get_parent_file_size($backup,$parent_file))
                {
                    $file['need_download']=false;
                }
                else
                {
                    $file['need_download']=true;
                    $file['need_download_file']=$parent_file;
                }
                $files[]=$file;
            }
            else
            {
                $file['file_name']=$file_name;

                $file['need_extra']=false;
                $file['need_download']=true;
                $file['need_download_file']=$file_name;
                $files[]=$file;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='no need prepare';
            echo json_encode($ret);
            die();
        }

        $ret=$this->init_prepare_files_task($files,$backup);
        $ret['test1']=$files;
        if($ret['result']=='success')
        {
            $this->flush($ret);
            $this->do_prepare_files_task();
        }
        else
        {
            echo json_encode($ret);
        }


        die();
    }

    public function prepare_download_types()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['types']) && !empty($_POST['types']))
        {
            $types = $_POST['types'];
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $files=array();
        $download_files=array();

        if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
        {
            $backup_info=$backup['backup_info'];
        }
        else
        {
            $backup_info=$this->get_backup_type_list($backup);
        }

        if($this->is_merge($backup)&&$this->is_download_all_types($backup_info,$types))
        {
            foreach ($backup['backup']['files'] as $key => $file_data)
            {
                if (!file_exists($path.$file_data['file_name']))
                {
                    $file['file_name']=$file_data['file_name'];

                    $file['need_extra']=false;
                    $file['need_download']=true;
                    $file['need_download_file']=$file_data['file_name'];
                    $files[]=$file;
                }
            }
        }
        else
        {
            if(isset($backup_info['types']))
            {
                foreach ($backup_info['types'] as $type=>$info)
                {
                    if(array_key_exists($type,$types))
                    {
                        foreach ($info['files'] as $file_data)
                        {
                            if(!file_exists($path.$file_data['file_name']))
                            {
                                if($this->is_child_file($backup,$file_data['file_name']))
                                {
                                    $file['file_name']=$file_data['file_name'];

                                    $file['need_extra']=true;
                                    $parent_file=$this->get_parent_file($backup,$file_data['file_name']);
                                    if(empty($parent_file))
                                    {
                                        $ret['result']='failed';
                                        $ret['error']='get parent file failed';
                                        echo json_encode($ret);
                                        die();
                                    }
                                    $file['parent_file']=$parent_file;

                                    if(file_exists($path.$parent_file)&&filesize($path.$parent_file)===$this->get_parent_file_size($backup,$parent_file))
                                    {
                                        $file['need_download']=false;
                                    }
                                    else
                                    {
                                        if(!in_array($parent_file,$download_files))
                                        {
                                            $file['need_download']=true;
                                            $file['need_download_file']=$parent_file;
                                            $download_files[]=$parent_file;
                                        }
                                        else
                                        {
                                            $file['need_download']=false;
                                        }
                                    }
                                    $files[]=$file;
                                }
                                else
                                {
                                    $file['file_name']=$file_data['file_name'];

                                    $file['need_extra']=false;
                                    $file['need_download']=true;
                                    $file['need_download_file']=$file_data['file_name'];
                                    $files[]=$file;
                                }
                            }
                        }
                    }
                }
            }
            else
            {
                foreach ($backup_info as $type=>$info)
                {
                    if(array_key_exists($type,$types))
                    {
                        foreach ($info['files'] as $file_data)
                        {
                            if(!file_exists($path.$file_data['file_name']))
                            {
                                if($this->is_child_file($backup,$file_data['file_name']))
                                {
                                    $file['file_name']=$file_data['file_name'];

                                    $file['need_extra']=true;
                                    $parent_file=$this->get_parent_file($backup,$file_data['file_name']);
                                    if(empty($parent_file))
                                    {
                                        $ret['result']='failed';
                                        $ret['error']='get parent file failed';
                                        echo json_encode($ret);
                                        die();
                                    }
                                    $file['parent_file']=$parent_file;

                                    if(file_exists($path.$parent_file)&&filesize($path.$parent_file)===$this->get_parent_file_size($backup,$parent_file))
                                    {
                                        $file['need_download']=false;
                                    }
                                    else
                                    {
                                        if(!in_array($parent_file,$download_files))
                                        {
                                            $file['need_download']=true;
                                            $file['need_download_file']=$parent_file;
                                            $download_files[]=$parent_file;
                                        }
                                        else
                                        {
                                            $file['need_download']=false;
                                        }
                                    }
                                    $files[]=$file;
                                }
                                else
                                {
                                    $file['file_name']=$file_data['file_name'];

                                    $file['need_extra']=false;
                                    $file['need_download']=true;
                                    $file['need_download_file']=$file_data['file_name'];
                                    $files[]=$file;
                                }
                            }
                        }
                    }
                }
            }

        }

        if(empty($files))
        {
            $ret['result']='failed';
            $ret['error']='no need prepare';
            echo json_encode($ret);
            die();
        }

        $ret=$this->init_prepare_files_task($files,$backup);

        if($ret['result']=='success')
        {
            $this->flush($ret);
            $this->do_prepare_files_task();
        }
        else
        {
            echo json_encode($ret);
        }

        die();
    }

    public function prepare_download_files()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['files']) && !empty($_POST['files']))
        {
            $download_files = $_POST['files'];
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $files=array();

        foreach ($backup['backup']['files'] as $key => $file_data)
        {
            if(in_array($file_data['file_name'],$download_files))
            {
                if(file_exists($path.$file_data['file_name'])&&filesize($path.$file_data['file_name'])===$file_data['size'])
                {
                   continue;
                }

                $file['file_name']=$file_data['file_name'];
                $file['need_extra']=false;
                $file['need_download']=true;
                $file['need_download_file']=$file_data['file_name'];
                $files[]=$file;
            }
        }


        if(empty($files))
        {
            $ret['result']='failed';
            $ret['error']='no need prepare';
            echo json_encode($ret);
            die();
        }

        $ret=$this->init_prepare_files_task($files,$backup);

        if($ret['result']=='success')
        {
            $this->flush($ret);
            $this->do_prepare_files_task();
        }
        else
        {
            echo json_encode($ret);
        }

        die();
    }

    public function prepare_incremental_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
        {
            $backup_id = sanitize_key($_POST['backup_id']);
        }
        else
        {
            die();
        }

        if (isset($_POST['full_backup_check']))
        {
            $full_backup_check = $_POST['full_backup_check'];
            if($full_backup_check=='true')
            {
                $full_backup_check=true;
            }
            else
            {
                $full_backup_check=false;
            }
        }
        else
        {
            die();
        }

        if (isset($_POST['incremental_backup_check']))
        {
            $incremental_backup_check = $_POST['incremental_backup_check'];
            if($incremental_backup_check=='true')
            {
                $incremental_backup_check=true;
            }
            else
            {
                $incremental_backup_check=false;
            }
        }
        else
        {
            die();
        }

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $files=array();
        $download_files=$this->get_incremental_files($backup,$full_backup_check,$incremental_backup_check);

        foreach ($backup['backup']['files'] as $key => $file_data)
        {
            if(in_array($file_data['file_name'],$download_files))
            {
                if(file_exists($path.$file_data['file_name'])&&filesize($path.$file_data['file_name'])===$file_data['size'])
                {
                    continue;
                }

                $file['file_name']=$file_data['file_name'];
                $file['need_extra']=false;
                $file['need_download']=true;
                $file['need_download_file']=$file_data['file_name'];
                $files[]=$file;
            }
        }

        if(empty($files))
        {
            $ret['result']='failed';
            $ret['error']='no need prepare';
            echo json_encode($ret);
            die();
        }

        $ret=$this->init_prepare_files_task($files,$backup);

        if($ret['result']=='success')
        {
            $this->flush($ret);
            $this->do_prepare_files_task();
        }
        else
        {
            echo json_encode($ret);
        }

        die();
    }

    public function get_parent_file_size($backup,$file_name)
    {
        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                if($value['file_name']==$file_name)
                {
                    return $value['size'];
                }
            }
        }

        return false;
    }

    public function is_child_file($backup,$file_name)
    {
        $is_child_file = true;

        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                if($value['file_name']==$file_name)
                {
                    return false;
                }
            }
        }

        return $is_child_file;
    }

    public function get_parent_file($backup,$file_name)
    {
        if(isset($backup['backup_info']['types']['All']))
        {
            foreach ($backup['backup_info']['types']['All']['info'] as $parent_file=>$file_info)
            {
                foreach ($file_info as $child_file_info)
                {
                    if($child_file_info['file_name']==$file_name)
                    {
                        return $parent_file;
                    }
                }
            }
        }

        $backup_item = new WPvivid_New_Backup_Item($backup);
        $files_info=array();
        foreach ($backup['backup']['files'] as $file)
        {
            $parent_file = $file['file_name'];
            $files_info[$parent_file]=$backup_item->get_file_info($parent_file);
            $files_info[$parent_file]['size']=$file['size'];
        }

        foreach ($files_info as $parent_file=>$file_info)
        {
            if(isset($file_info['has_child']))
            {
                if(isset($file_info['child_file']))
                {
                    foreach ($file_info['child_file'] as $child_file_name=>$child_file_info)
                    {
                        if($child_file_name==$file_name)
                        {
                            return $parent_file;
                        }
                    }
                }
            }
        }

        return '';
    }

    public function init_prepare_files_task($files,$backup)
    {
        $this->task=array();

        $this->task['status']['start_time']=time();
        $this->task['status']['run_time']=time();
        $this->task['status']['timeout']=time();
        $this->task['status']['str']='running';
        $this->task['status']['resume_count']=0;
        $this->task['status']['current_job']=false;
        $this->task['backup']=$backup;
        $this->task['jobs']=array();
        $index=1;
        foreach ($files as $file)
        {
            if($file['need_download'])
            {
                $this->task['jobs'][$index]['type']='download';
                $this->task['jobs'][$index]['download_file']=$file['need_download_file'];
                $this->task['jobs'][$index]['finished']=0;
                $this->task['jobs'][$index]['progress']=0;
                $index++;
            }

            if($file['need_extra'])
            {
                $this->task['jobs'][$index]['type']='extra';
                $this->task['jobs'][$index]['parent_file']=$file['parent_file'];
                $this->task['jobs'][$index]['extra_file']=$file['file_name'];
                $this->task['jobs'][$index]['finished']=0;
                $this->task['jobs'][$index]['progress']=0;
                $index++;
            }
        }

        update_option('wpvivid_prepare_download_files_task',$this->task,'no');

        $ret['result']='success';
        $ret['test']=$this->task;
        return $ret;
    }

    public function do_prepare_files_task()
    {
        $finished=true;

        foreach ($this->task['jobs'] as $job)
        {
            if($job['finished']==0)
            {
                $finished=false;
                break;
            }
        }

        if (!$finished)
        {
            $job_key=false;
            foreach ($this->task['jobs'] as $key=>$job)
            {
                if($job['finished']==0)
                {
                    $job_key=$key;
                    break;
                }
            }

            $ret=$this->do_prepare_files_job($job_key);
            if($ret['result']=='success')
            {
                $this->task['status']['resume_count']=0;
                $this->task['status']['str']='ready';
                $this->update_task();
            }
            else
            {
                $this->task['status']['str']='error';
                $this->task['status']['error']=$ret['error'];
                $this->update_task();
            }

            return $ret;
        }

        $ret['result']='success';
        return $ret;
    }

    public function do_prepare_files_job($key)
    {
        $this->task['status']['str']='running';
        $this->task['status']['current_job']=$key;
        $this->update_task();

        $job=$this->task['jobs'][$key];

        if($job['type']=='download')
        {
            $ret=$this->do_download_file($key);
            if($ret['result']=='success')
            {
               if($ret['finished']==1)
               {
                   $this->task['jobs'][$key]['finished']=1;
                   $this->update_task();
                   return $ret;
               }
               else
               {
                   return $ret;
               }
            }
            else
            {
                return $ret;
            }
        }
        else if($job['type']=='extra')
        {
            $ret=$this->do_extra_file($job);
            if($ret['result']!='success')
            {
                return $ret;
            }
        }

        $this->task['jobs'][$key]['finished']=1;

        $this->update_task();

        $ret['result']='success';
        return $ret;
    }

    public function do_download_file($key)
    {
        if(!isset($this->task['jobs'][$key]['download']))
        {
            $backup_item = new WPvivid_New_Backup_Item($this->task['backup']);
            $root_path=$backup_item->get_download_local_path();
            if(!file_exists($root_path))
            {
                @mkdir($root_path);
            }

            if(empty($this->task['backup']['remote']))
            {
                $ret['result']='failed';
                $ret['error']='backup file not exists';
                return $ret;
            }

            $remotes=$this->task['backup']['remote'];
            $remote_option=array_shift($remotes);

            if(is_null($remote_option))
            {
                return array('result' => WPVIVID_FAILED ,'error'=>'Retrieving the cloud storage information failed while downloading backups. Please try again later.');
            }

            if($this->task['backup']['type']=='Incremental')
            {
                if(isset($this->task['backup']['incremental_path']) && !empty($this->task['backup']['incremental_path']))
                {
                    if($remote_option['type']=='ftp'||$remote_option['type']=='sftp')
                    {
                        if(isset($remote_option['custom_path']))
                        {
                            $remote_option['custom_path']=$remote_option['custom_path'].'/'.$this->task['backup']['incremental_path'];
                        }
                        else
                        {
                            $remote_option['path']=$remote_option['path'].'/'.$this->task['backup']['incremental_path'];
                        }
                    }
                    else
                    {
                        $remote_option['path']=$remote_option['path'].'/'.$this->task['backup']['incremental_path'];
                    }
                }
            }

            if(file_exists($root_path.$this->task['jobs'][$key]['download_file']))
            {
                unlink($root_path.$this->task['jobs'][$key]['download_file']);
            }

            $this->task['jobs'][$key]['download']['remote_option']=$remote_option;
            $this->task['jobs'][$key]['download']['file_name']=$this->task['jobs'][$key]['download_file'];
            foreach ($this->task['backup']['backup']['files'] as $file)
            {
                if($this->task['jobs'][$key]['download']['file_name']==$file['file_name'])
                {
                    $this->task['jobs'][$key]['download']['size']=$file['size'];
                    break;
                }
            }
            $this->task['jobs'][$key]['download']['root_path']=$root_path;
            $tmp_name= uniqid('wpvividtmp-');
            $this->task['jobs'][$key]['download']['local_path']=$root_path.$tmp_name;
            $this->task['jobs'][$key]['download']['offset']=0;
            $this->update_task();
        }

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($this->task['jobs'][$key]['download']['remote_option']);

        $ret=$remote->chunk_download($this->task['jobs'][$key]['download'],array($this,'download_callback_v2'));
        if($ret['result']=='success')
        {
            $result['result']='success';
            $result['finished']=$ret['finished'];
            $this->task['jobs'][$key]['download']['offset']=$ret['offset'];
            $this->update_task();
            return $result;
        }
        else
        {
            return $ret;
        }
    }

    public function download_callback_v2($offset,$current_name,$current_size,$last_time,$last_size)
    {
        $key=$this->task['status']['current_job'];
        $this->task['jobs'][$key]['progress']= intval(($offset/$current_size)* 100) ;
        $this->task['jobs'][$key]['progress_text']='Downloading file:'.$current_name.' from remote storage | Total size:'.size_format($current_size,2).' Downloaded Size:'.size_format($offset,2);
        $this->update_task();
    }

    public function do_extra_file($job)
    {
        set_time_limit(300);

        $backup_item = new WPvivid_New_Backup_Item($this->task['backup']);
        $root_path=$backup_item->get_download_local_path();
        if(!file_exists($root_path))
        {
            @mkdir($root_path);
        }

        $extract_files[]=$job['extra_file'];

        if (!class_exists('WPvivid_PclZip'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';

        if(!defined('PCLZIP_TEMPORARY_DIR'))
            define(PCLZIP_TEMPORARY_DIR,dirname($root_path));

        $archive = new WPvivid_PclZip($root_path.$job['parent_file']);

        $zip_ret = $archive->extract(WPVIVID_PCLZIP_OPT_BY_NAME,$extract_files,WPVIVID_PCLZIP_OPT_PATH, $root_path,WPVIVID_PCLZIP_OPT_REPLACE_NEWER,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,16);
        if(!$zip_ret)
        {
            $ret['result']='failed';
            $ret['error'] = $archive->errorInfo(true);

        }
        else
        {
            $ret['result']='success';
        }

        return $ret;
    }

    private function flush($ret)
    {
        $json=json_encode($ret);
        if(!headers_sent())
        {
            header('Content-Length: '.strlen($json));
            header('Connection: close');
            header('Content-Encoding: none');
        }


        if (session_id())
            session_write_close();
        echo $json;

        if(function_exists('fastcgi_finish_request'))
        {
            fastcgi_finish_request();
        }
        else
        {
            if(ob_get_level()>0)
                ob_flush();
            flush();
        }
    }

    private function update_task()
    {
        $this->task['status']['run_time']=time();
        update_option('wpvivid_prepare_download_files_task',$this->task,'no');
    }

    public function get_prepare_download_progress()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        $this->task=get_option('wpvivid_prepare_download_files_task',array());

        if(empty($this->task))
        {
            $ret['result']='failed';
            $ret['error']='task not found';
            echo json_encode($ret);
            die();
        }

        if($this->task['status']['str']=='error')
        {
            $ret['result']='failed';
            $ret['error']=$this->task['status']['error'];
            echo json_encode($ret);
            die();
        }

        $ret['result']='success';
        $ret['test']=$this->task;
        $finished=true;

        foreach ($this->task['jobs'] as $job)
        {
            if($job['finished']==0)
            {
                $finished=false;
                break;
            }
        }

        if($finished)
        {
            $ret['finished']=true;
            $ret['progress']='100% completed';
            $ret['width']='100%';
            $ret['html']='Prepare download files completed.';
            $ret['set_timeout']=true;
            echo json_encode($ret);
            die();
        }
        else
        {
            $ret['finished']=false;

            /*
            $i_finished_backup_count=0;
            $i_sum=count($this->task['jobs']);
            foreach ($this->task['jobs'] as $job)
            {
                if($job['finished']==1)
                {
                    $i_finished_backup_count++;
                }
            }
            $i_progress=intval(($i_finished_backup_count/$i_sum)*100);

            */

            $key=$this->task['status']['current_job'];
            $job=$this->task['jobs'][$key];

            $i_progress=max(10,$job['progress']);
            $ret['progress']=$i_progress.'% completed';
            $ret['width']=$i_progress.'%';

            $key=$this->task['status']['current_job'];
            $job=$this->task['jobs'][$key];
            if($job['type']=='download')
            {
                if(isset($job['progress_text']))
                {
                    $ret['html']=$job['progress_text'];
                }
                else
                {
                    $ret['html']='Downloading file:'.$job['download_file'].' from remote storage...';
                }
            }
            else if($job['type']=='extra')
            {
                $ret['html']='Unzipping file:'.$job['extra_file'].' from '.$job['parent_file'].'...';
            }
        }

        $ret['set_timeout']=true;

        if($this->task['status']['str']=='ready')
        {
            $this->flush($ret);
            $this->do_prepare_files_task();
            die();
        }
        else
        {
            echo json_encode($ret);
            die();
        }
    }

    public function get_backup_type_list($backup)
    {
        $type_list=array();
        $ismerge = false;

        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                $file_name=$value['file_name'];
                if(WPvivid_backup_pro_function::is_wpvivid_db_backup($file_name))
                {
                    $type_list['Database']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_themes_backup($file_name))
                {
                    $type_list['themes']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_plugin_backup($file_name))
                {
                    $type_list['plugins']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_uploads_backup($file_name))
                {
                    $type_list['uploads']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_content_backup($file_name))
                {
                    $type_list['wp-content']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_core_backup($file_name))
                {
                    $type_list['Wordpress Core']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_other_backup($file_name))
                {
                    $type_list['Others']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_additional_db_backup($file_name))
                {
                    $type_list['Additional Databases']['files'][$file_name]=$value;
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_all_backup($file_name))
                {
                    $ismerge = true;
                }
            }
        }
        //all
        if($ismerge)
        {
            $backup_item = new WPvivid_New_Backup_Item($backup);
            $info=array();
            $files_info=array();
            foreach ($backup['backup']['files'] as $file)
            {
                $file_name = $file['file_name'];

                $path=$backup_item->get_backup_path($file_name);
                if(!file_exists($path))
                {
                    $data['file_name']=$file['file_name'];
                    $data['size']=$file['size'];
                    $info['type']['All parts'][$data['file_name']] = $data;
                    continue;
                }
                $files_info[$file_name]=$backup_item->get_file_info($file_name);
                $files_info[$file_name]['size']=$file['size'];
            }

            if(!empty($files_info))
            {
                foreach ($files_info as $file_name=>$file_info)
                {
                    if(isset($file_info['has_child']))
                    {
                        if(isset($file_info['child_file']))
                        {
                            foreach ($file_info['child_file'] as $child_file_name=>$child_file_info)
                            {
                                if(isset($child_file_info['file_type']))
                                {
                                    $data['file_name']=$child_file_name;
                                    $data['size']=$this->get_child_file_size($backup,$file_name,$child_file_name);
                                    $info['type'][$child_file_info['file_type']][] = $data;
                                }
                            }
                        }
                    }
                    else
                    {
                        if(isset($file_info['file_type']))
                        {
                            $data['file_name']=$file_name;
                            $data['size']=$file_info['size'];
                            $info['type'][$file_info['file_type']][] = $file_name;
                        }
                    }
                }
            }

            if(isset($info['type']))
            {
                foreach ($info['type'] as $backup_content=>$files)
                {
                    if ($backup_content === 'databases')
                    {
                        if(!array_key_exists('Database', $type_list))
                        {
                            $type_list['Database']['files']=$files;
                        }
                        else
                        {
                            $type_list['Database']['files']=array_merge($type_list['Database']['files'],$files);
                        }
                    }
                    if($backup_content === 'themes')
                    {
                        if(!array_key_exists('themes', $type_list))
                        {
                            $type_list['themes']['files']=$files;
                        }
                        else
                        {
                            $type_list['themes']['files']=array_merge($type_list['themes']['files'],$files);
                        }
                    }
                    if($backup_content === 'plugin')
                    {
                        if(!array_key_exists('plugins', $type_list))
                        {
                            $type_list['plugins']['files']=$files;
                        }
                        else
                        {
                            $type_list['plugins']['files']=array_merge($type_list['plugins']['files'],$files);
                        }
                    }
                    if($backup_content === 'upload')
                    {
                        if(!array_key_exists('uploads', $type_list))
                        {
                            $type_list['uploads']['files']=$files;
                        }
                        else
                        {
                            $type_list['uploads']['files']=array_merge($type_list['uploads']['files'],$files);
                        }
                    }
                    if($backup_content === 'wp-content')
                    {
                        if(!array_key_exists('wp-content', $type_list))
                        {
                            $type_list['wp-content']['files']=$files;
                        }
                        else
                        {
                            $type_list['wp-content']['files']=array_merge($type_list['wp-content']['files'],$files);
                        }
                    }
                    if($backup_content === 'wp-core')
                    {
                        if(!array_key_exists('Wordpress Core', $type_list))
                        {
                            $type_list['Wordpress Core']['files']=$files;
                        }
                        else
                        {
                            $type_list['Wordpress Core']['files']=array_merge($type_list['Wordpress Core']['files'],$files);
                        }
                    }
                    if($backup_content === 'custom')
                    {
                        if(!array_key_exists('Others', $type_list))
                        {
                            $type_list['Others']['files']=$files;
                        }
                        else
                        {
                            $type_list['Others']['files']=array_merge($type_list['Others']['files'],$files);
                        }
                    }
                    if($backup_content === 'additional_databases')
                    {
                        if(!array_key_exists('Others', $type_list))
                        {
                            $type_list['Additional Databases']['files']=$files;
                        }
                        else
                        {
                            $type_list['Additional Databases']['files']=array_merge($type_list['Additional Databases']['files'],$files);
                        }
                    }

                    if($backup_content === 'All parts')
                    {
                        $type_list['All parts']['files']=$files;
                    }
                }
            }
        }

        return $type_list;
    }

    public function is_old_remote_backup($backup)
    {
        $ismerge = false;
        $is_old_remote_backup=false;

        if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
        {
            return false;
        }

        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                $file_name=$value['file_name'];
                if(WPvivid_backup_pro_function::is_wpvivid_all_backup($file_name))
                {
                    $ismerge = true;
                    break;
                }
            }
        }
        //all
        if($ismerge)
        {
            $backup_item = new WPvivid_New_Backup_Item($backup);
            foreach ($backup['backup']['files'] as $file)
            {
                $file_name = $file['file_name'];

                $path=$backup_item->get_backup_path($file_name);
                if(file_exists($path))
                {
                    continue;
                }
                else
                {
                    $is_old_remote_backup=true;
                    break;
                }
            }
        }

        return $is_old_remote_backup;
    }

    public function get_child_file_size($backup,$file_name,$child_file_name)
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);

        $path.=$file_name;
        if (!class_exists('WPvivid_PclZip'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';

        $archive = new WPvivid_PclZip($path);

        $list = $archive->listContent();

        foreach ($list as $item)
        {
            if (basename($item['filename']) === $child_file_name)
            {
                return $item['size'];
            }
        }
        return 0;
    }

    public function get_ready_download_files()
    {
        try
        {
            $backup_id = sanitize_key($_POST['backup_id']);
            $ignore_md5 = sanitize_text_field($_POST['ignore_md5']);
            $backup_list=new WPvivid_New_BackupList();
            $backup=$backup_list->get_backup_by_id($backup_id);
            $files=get_option('wpvivid_ready_to_download_files',array());
            if(!$backup||empty($files))
            {
                $ret['result']='failed';
                $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid');
                echo json_encode($ret);
                die();
            }
            $backup_item=new WPvivid_New_Backup_Item($backup);
            $local_path=$backup_item->get_download_local_path();
            $file_arr = array();
            foreach ($files as $file)
            {
                $file_size = filesize($local_path.$file);
                $file_name = $file;
                $file_arr[$file_name]['file_name'] = $file_name;
                $file_arr[$file_name]['file_size'] = $file_size;
                if($ignore_md5 == '0')
                {
                    $file_arr[$file_name]['file_md5'] = md5_file($local_path.$file);
                }
                else
                {
                    $wpvivid_download_backup_ignore_md5=get_option('wpvivid_download_backup_ignore_md5', '0');
                    if($wpvivid_download_backup_ignore_md5 !== '1')
                    {
                        update_option('wpvivid_download_backup_ignore_md5', '1', 'no');
                    }
                }
            }
            $ret['result'] = 'success';
            $ret['files'] = $file_arr;
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_ready_download_files_url()
    {
        try
        {
            $backup_id = sanitize_key($_POST['backup_id']);
            $backup_list=new WPvivid_New_BackupList();
            $backup=$backup_list->get_backup_by_id($backup_id);
            $files=get_option('wpvivid_ready_to_download_files',array());
            if(!$backup||empty($files))
            {
                $ret['result']='failed';
                $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid');
                echo json_encode($ret);
                die();
            }

            $urls = array();
            foreach ($files as $file)
            {
                $file_name = $file;
                $urls[$file_name]['file_name'] = $file_name;
            }
            $ret['result'] = 'success';
            $ret['urls'] = $urls;
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_download_file_data($file_name,$backup)
    {
        if($this->is_merge($backup))
        {
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
            $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);
            $backup_item = new WPvivid_New_Backup_Item($backup);
            foreach ($backup['backup']['files'] as $file)
            {
                if($file_name==$file['file_name'])
                {
                    return $file;
                }
                else
                {
                    if(file_exists($path.$file['file_name']))
                    {
                        if(filesize($path.$file['file_name'])!=$file['size'])
                        {
                            return false;
                        }

                        $file_info=$backup_item->get_file_info($file['file_name']);
                        if(isset($file_info['has_child'])&&isset($file_info['child_file']))
                        {
                            foreach ($file_info['child_file'] as $child_file_name=>$child_file_info)
                            {
                                if($child_file_name==$file_name)
                                {
                                    $data['file_name']=$child_file_name;
                                    $data['size']=$this->get_child_file_size($backup,$file['file_name'],$child_file_name);
                                    return $data;
                                }
                            }
                        }
                    }
                }
            }

            if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
            {
                $types=$backup['backup_info']['types'];
                foreach ($types as $type)
                {
                    foreach ($type['files'] as $file)
                    {
                        if($file_name==$file['file_name'])
                        {
                            return $file;
                        }
                    }
                }
            }
        }
        else
        {
            foreach ($backup['backup']['files'] as $value)
            {
                if($file_name==$value['file_name'])
                {
                    return $value;
                }
            }
        }

        return false;
    }

    public function is_merge($backup)
    {
        $ismerge = false;

        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                $file_name=$value['file_name'];
                if(WPvivid_backup_pro_function::is_wpvivid_all_backup($file_name))
                {
                    $ismerge = true;
                }
            }
        }

        return $ismerge;
    }

    public function is_download_all_types($backup_info,$types)
    {
        $all=true;

        if(isset($backup_info['types']))
        {
            foreach ($backup_info['types'] as $type=>$info)
            {
                if($type=='All')
                {
                    continue;
                }

                if(!array_key_exists($type,$types))
                {
                    $all=false;
                }
            }
        }
        else
        {
            foreach ($backup_info as $type=>$info)
            {
                if($type=='All')
                {
                    continue;
                }

                if(!array_key_exists($type,$types))
                {
                    $all=false;
                }
            }
        }

        return $all;
    }
}

class WPvivid_Backup_Download_UI
{
    public $container_id;

    public function __construct($container_id)
    {
        $this->container_id=$container_id;
    }

    public function output_download_page()
    {
        ?>

        <div id="wpvivid_download_page">
            <div id="wpvivid_download_progress" class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom:1em;display: none">
                <div>
                    <p>
                    <span class="wpvivid-span-progress">
                        <span class="wpvivid-span-processed-progress" style="width: 0%">0% completed</span>
                    </span>
                    </p>
                    <p>
                        <span class="dashicons dashicons-cloud"></span>
                        <span id="wpvivid_download_progress_text"></span>
                    </p>
                    <p></p>
                    <div>
                        <input id="wpvivid_cancel_download" class="button-primary" type="submit" value="Cancel">
                    </div>
                </div>
            </div>
            <div id="wpvivid_old_remote_backup" class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom: 10px;display: none">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span>We detected this backup was from an old version,currently only supports full downloads or file downloads individually.</span>
            </div>
            <div id="wpvivid_incremental_backup_box" class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="display: none;box-shadow:0 1px 1px rgba(0,0,0,.04);border:1px solid #c3c4c7">
                <div style="width:4rem;height:4rem; float:left;">
                    <span class="dashicons  dashicons-cloud-upload wpvivid-dashicons-blue" style="font-size:3rem;"></span>
                </div>
                <div style="float:left;">
                    <h2 style="padding-left:0;">
                    <span>
                        <code>Incremental Backup Cycle</code> started on <span id="wpvivid_incremental_backup_started_time"></span>
                    </span>
                    </h2>
                    <p>
                    <span>
                        <input id="wpvivid_incremental_full_backup_check" type="checkbox" checked/>
                    </span>
                        <span>Full Backup</span>
                        <span style="padding:0 1rem;"></span>
                        <span>
                        <input id="wpvivid_incremental_backup_check" type="checkbox" checked/>
                    </span>
                        <span>All of Incremental Backups</span>
                        <span>
                        <input type="submit" id="wpvivid_incremental_backup_download" class="button action" value="Download"/>
                    </span>
                    </p>
                </div>
                <div style="clear:both"></div>
            </div>
            <div id="wpvivid_incremental_backup_tablenav_top" class="tablenav top" style="display: none">
                <div class="alignleft actions bulkactions">
                    <p>
                        <span>Display the <code><strong>incremental backups</strong></code> created from:</span>
                        <span>
                        <input id="wpvivid_incremental_backup_date_start" type="date">
                    </span>
                        <span>
                        <input type="time" id="wpvivid_incremental_backup_time_start">
                    </span>
                        <span>to</span>
                        <span>
                        <input id="wpvivid_incremental_backup_date_end" type="date">
                    </span>
                        <span>
                        <input type="time" id="wpvivid_incremental_backup_time_end">
                    </span>
                        <span>
                        <input type="submit" id="wpvivid_incremental_backup_scan" class="button action" value="Apply">
                    </span>
                    </p>
                </div>
            </div>
            <div id="wpvivid_download_tablenav_top" style="margin-bottom:0.5rem;">
                <label for="wpvivid_select_bulk_download" class="screen-reader-text">Select bulk action</label>
                <select id="wpvivid_select_bulk_download">
                    <option value="download">Download</option>
                </select>
                <input type="submit" class="button action wpvivid-select-bulk wpvivid_bulk_download" value="Apply">
            </div>
            <div class="wpvivid-local-remote-backup-list wpvivid-element-space-bottom" id="wpvivid_download_list">
            </div>

            <div id="wpvivid_incremental_backup_tablenav_bottom" class="alignleft actions bulkactions" style="display: none;margin-top:0.5rem;">
                <label for="wpvivid_select_bulk_download" class="screen-reader-text">Select bulk action</label>
                <select id="wpvivid_select_bulk_download">
                    <option value="download">Download</option>
                </select>
                <input type="submit" class="button action wpvivid-select-bulk wpvivid_bulk_download" value="Apply">
            </div>
        </div>
        <div id="wpvivid_download_load_page" style="display: none;">
            <div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>
            <div style="float: left; margin-top: 2px;">Loading…</div>
            <div style="clear: both;"></div>
        </div>

        <script>
            var wpvivid_download_backup_id='';
            var wpvivid_download_cancel=false;
            jQuery('#<?php echo $this->container_id?>').on("click",".nav-tab-delete-img-addon",function(event)
            {
                wpvivid_cancel_download();
            });

            jQuery('#wpvivid_backup_list').on("click",'.wpvivid-view-backup',function()
            {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                wpvivid_init_download_page(backup_id);
            });

            function wpvivid_init_download_page(backup_id)
            {
                var ajax_data = {
                    'action':'wpvivid_init_new_download_page',
                    'backup_id':backup_id,
                };

                jQuery('#wpvivid_download_page').hide();
                jQuery('#wpvivid_download_load_page').show();
                jQuery( document ).trigger( '<?php echo $this->container_id ?>-show',[ 'download_backup', 'all_backups' ]);

                jQuery('#wpvivid_incremental_backup_box').hide();
                jQuery('#wpvivid_incremental_backup_tablenav_top').hide();

                jQuery('#wpvivid_download_tablenav_top').show();

                jQuery('#wpvivid_old_remote_backup').hide();
                jQuery('#wpvivid_download_progress').hide();
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    jQuery('#wpvivid_download_page').show();
                    jQuery('#wpvivid_download_load_page').hide();
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_download_backup_id=backup_id;
                            jQuery('#wpvivid_download_list').html(jsonarray.list);
                            if(jsonarray.is_old_remote_backup)
                            {
                                jQuery('#wpvivid_old_remote_backup').show();
                            }

                            if(jsonarray.is_incremental)
                            {
                                jQuery('#wpvivid_incremental_backup_box').show();
                                jQuery('#wpvivid_incremental_backup_tablenav_top').show();

                                jQuery('#wpvivid_download_tablenav_top').hide();

                                jQuery('#wpvivid_incremental_backup_date_start').val(jsonarray.start_date);
                                jQuery('#wpvivid_incremental_backup_date_end').val(jsonarray.end_date);

                                jQuery('#wpvivid_incremental_backup_time_start').val(jsonarray.start_time);
                                jQuery('#wpvivid_incremental_backup_time_end').val(jsonarray.end_time);

                                jQuery('#wpvivid_incremental_backup_started_time').html(jsonarray.full_backup_start_time);
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_download_page').show();
                    jQuery('#wpvivid_download_load_page').hide();
                    var error_message = wpvivid_output_ajaxerror('initializing download information', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_format_size(size)
            {
                if (size >= 1073741824) {
                    size = (size / 1073741824).toFixed(2) + ' GB';
                } else if (size >= 1048576) {
                    size = (size / 1048576).toFixed(2) + ' MB';
                } else if (size >= 1024) {
                    size = (size / 1024).toFixed(2) + ' KB';
                } else {
                    size = size + ' B';
                }
                return size;
            }

            jQuery('#wpvivid_download_list').on("click",'.wpvivid-expand',function()
            {
                wpvivid_close_all_tr();

                var type=jQuery(this).closest('tr').data('type');
                var totalSize = jQuery(this).closest('tr').data('total-size');
                var fileSize = jQuery(this).closest('tr').data('file-size');

                jQuery(this).closest('tr').addClass('active');
                jQuery(this).closest('tr').find('.size-placeholder').text(wpvivid_format_size(fileSize));

                jQuery('#wpvivid_download_list').find('.wpvivid-sub-tr').each(function()
                {
                    var ptype=jQuery(this).data('ptype');
                    if(type==ptype)
                    {
                        jQuery(this).show();
                        jQuery(this).addClass('active');
                    }
                });

                jQuery(this).removeClass('wpvivid-expand');
                jQuery(this).removeClass('dashicons-plus-alt2');
                jQuery(this).addClass('wpvivid-close');
                jQuery(this).addClass('dashicons-minus');
                //
            });

            jQuery('#wpvivid_download_list').on("click",'.wpvivid-close',function()
            {
                var type=jQuery(this).closest('tr').data('type');
                jQuery(this).closest('tr').removeClass('active');

                jQuery(this).closest('tr').find('.size-placeholder').text(wpvivid_format_size(jQuery(this).closest('tr').data('total-size')));

                jQuery('#wpvivid_download_list').find('.wpvivid-sub-tr').each(function()
                {
                    var ptype=jQuery(this).data('ptype');
                    if(type==ptype)
                    {
                        jQuery(this).hide();
                        jQuery(this).removeClass('active');
                    }
                });

                jQuery(this).removeClass('wpvivid-close');
                jQuery(this).removeClass('dashicons-minus');
                jQuery(this).addClass('wpvivid-expand');
                jQuery(this).addClass('dashicons-plus-alt2');
            });

            jQuery('.wpvivid_bulk_download').click(function()
            {
                var dowload_types = {};
                var is_empty=true;
                jQuery('#wpvivid_download_list .check-column input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var type =jQuery(this).closest('tr').data('type');
                        dowload_types[type] = type;
                        is_empty=false;
                    }
                });

                if(is_empty)
                {
                    return;
                }

                wpvivid_download_types_files(dowload_types);
            });

            jQuery('#wpvivid_download_list').on("click",'.wpvivid_bulk_download2',function()
            {
                var download_files = {};
                var is_empty=true;
                jQuery('#wpvivid_download_list .check-column input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var file =jQuery(this).closest('tr').data('filename');
                        download_files[file] = file;
                        is_empty=false;
                    }
                });

                if(is_empty)
                {
                    return;
                }
                wpvivid_download_files(download_files);
            });

            jQuery('#wpvivid_download_list').on("click",'.wpvivid-download',function()
            {
                var file_name=jQuery(this).closest('tr').data('filename');
                var backup_id=jQuery(this).closest('table').data('id');
                wpvivid_download_file(backup_id,file_name);
            });

            jQuery('#wpvivid_cancel_download').click(function()
            {
                wpvivid_cancel_download();
            });

            jQuery('#wpvivid_incremental_backup_scan').click(function()
            {
                wpvivid_get_incremental_backup_list(1);
            });

            jQuery('#wpvivid_download_list').on("click",'.first-page',function()
            {
                wpvivid_get_incremental_backup_list('first');
            });

            jQuery('#wpvivid_download_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_incremental_backup_list(page-1);
            });

            jQuery('#wpvivid_download_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_incremental_backup_list(page+1);
            });

            jQuery('#wpvivid_download_list').on("click",'.last-page',function()
            {
                wpvivid_get_incremental_backup_list('last');
            });

            jQuery('#wpvivid_download_list').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    wpvivid_get_incremental_backup_list(page);
                }
            });

            function wpvivid_get_incremental_backup_list(page)
            {
                var start_date=jQuery('#wpvivid_incremental_backup_date_start').val();
                var end_date=jQuery('#wpvivid_incremental_backup_date_end').val();

                var start_time=jQuery('#wpvivid_incremental_backup_time_start').val();
                var end_time=jQuery('#wpvivid_incremental_backup_time_end').val();

                var ajax_data = {
                    'action':'wpvivid_incremental_backup_scan',
                    'backup_id':wpvivid_download_backup_id,
                    'start_date':start_date,
                    'start_time':start_time,
                    'end_date':end_date,
                    'end_time':end_time,
                    'page':page
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_download_list').html(jsonarray.list);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('backup scan', textStatus, errorThrown);
                    alert(error_message);
                });
            }


            jQuery('#wpvivid_incremental_backup_download').click(function()
            {
                if(jQuery('#wpvivid_incremental_full_backup_check').prop('checked'))
                {
                    var full_backup_check=true;
                }
                else
                {
                    var full_backup_check=false;
                }

                if(jQuery('#wpvivid_incremental_backup_check').prop('checked'))
                {
                    var incremental_backup_check=true;
                }
                else
                {
                    var incremental_backup_check=false;
                }

                if(full_backup_check||incremental_backup_check)
                {
                    var ajax_data = {
                        'action':'wpvivid_download_incremental_backup',
                        'backup_id':wpvivid_download_backup_id,
                        'full_backup_check':full_backup_check,
                        'incremental_backup_check':incremental_backup_check,
                    };

                    jQuery('#wpvivid_download_progress').show();
                    wpvivid_download_cancel=false;
                    jQuery('#wpvivid_cancel_download').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('.wpvivid-span-processed-progress').html("0% completed");
                    jQuery(".wpvivid-span-processed-progress").width( "0%" );
                    jQuery('#wpvivid_download_progress_text').html("Preparing...");
                    wpvivid_lock_download_list();
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                if(jsonarray.prepare)
                                {
                                    start_download_ready_files(wpvivid_download_backup_id);
                                }
                                else
                                {
                                    wpvivid_prepare_incremental_backup(full_backup_check,incremental_backup_check);
                                }
                            }
                            else
                            {
                                jQuery('#wpvivid_download_progress').hide();
                                wpvivid_unlock_download_list();
                                alert(jsonarray.error);
                            }
                        }
                        catch(err)
                        {
                            jQuery('#wpvivid_download_progress').hide();
                            wpvivid_unlock_download_list();
                            alert(err);
                        }
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('download file', textStatus, errorThrown);
                        jQuery('#wpvivid_download_progress').hide();
                        wpvivid_unlock_download_list();
                        alert(error_message);
                    });
                }

            });

            function wpvivid_download_types_files(types)
            {
                var ajax_data = {
                    'action':'wpvivid_download_types',
                    'backup_id':wpvivid_download_backup_id,
                    'types':types,
                };

                jQuery('#wpvivid_download_progress').show();
                wpvivid_download_cancel=false;
                jQuery('#wpvivid_cancel_download').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");
                wpvivid_lock_download_list();
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.prepare)
                            {
                                start_download_ready_files(wpvivid_download_backup_id);
                            }
                            else
                            {
                                wpvivid_prepare_types_files(types);
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('download file', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_download_files(download_files)
            {
                var ajax_data = {
                    'action':'wpvivid_download_files',
                    'backup_id':wpvivid_download_backup_id,
                    'files':download_files,
                };

                jQuery('#wpvivid_download_progress').show();
                wpvivid_download_cancel=false;
                jQuery('#wpvivid_cancel_download').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");
                wpvivid_lock_download_list();
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.prepare)
                            {
                                start_download_ready_files(wpvivid_download_backup_id);
                            }
                            else
                            {
                                wpvivid_prepare_files(download_files);
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('download file', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_download_file(backup_id,file_name)
            {
                var ajax_data = {
                    'action':'wpvivid_download_file2',
                    'backup_id':backup_id,
                    'file_name':file_name,
                };

                jQuery('#wpvivid_download_progress').show();
                wpvivid_download_cancel=false;
                jQuery('#wpvivid_cancel_download').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");
                wpvivid_lock_download_list();
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.prepare)
                            {
                                start_download_ready_files(backup_id);
                            }
                            else
                            {
                                wpvivid_prepare_download_file(backup_id,file_name);
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                            jQuery('#wpvivid_download_progress').hide();
                            wpvivid_unlock_download_list();
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        jQuery('#wpvivid_download_progress').hide();
                        wpvivid_unlock_download_list();
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('download file', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#wpvivid_download_progress').hide();
                    wpvivid_unlock_download_list();
                });
            }

            function wpvivid_prepare_files(files)
            {
                var ajax_data = {
                    'action':'wpvivid_prepare_download_files',
                    'backup_id':wpvivid_download_backup_id,
                    'files':files,
                };

                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_get_prepare_download_progress();
                        }
                        else
                        {
                            jQuery('#wpvivid_download_progress').hide();
                            wpvivid_unlock_download_list();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_download_progress').hide();
                        wpvivid_unlock_download_list();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_prepare_download_progress();
                });
            }

            function wpvivid_prepare_incremental_backup(full_backup_check,incremental_backup_check)
            {
                var ajax_data = {
                    'action':'wpvivid_prepare_incremental_backup',
                    'backup_id':wpvivid_download_backup_id,
                    'full_backup_check':full_backup_check,
                    'incremental_backup_check':incremental_backup_check
                };

                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_get_prepare_download_progress();
                        }
                        else
                        {
                            jQuery('#wpvivid_download_progress').hide();
                            wpvivid_unlock_download_list();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_download_progress').hide();
                        wpvivid_unlock_download_list();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_prepare_download_progress();
                });
            }

            function wpvivid_prepare_download_file(backup_id,file_name)
            {
                var ajax_data = {
                    'action':'wpvivid_prepare_download_file',
                    'backup_id':backup_id,
                    'file_name':file_name,
                };

                wpvivid_download_backup_id=backup_id;

                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_get_prepare_download_progress();
                        }
                        else
                        {
                            jQuery('#wpvivid_download_progress').hide();
                            wpvivid_unlock_download_list();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_download_progress').hide();
                        wpvivid_unlock_download_list();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_prepare_download_progress();
                });
            }

            function wpvivid_prepare_types_files(types)
            {
                var ajax_data = {
                    'action':'wpvivid_prepare_download_types',
                    'backup_id':wpvivid_download_backup_id,
                    'types':types,
                };

                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_download_progress_text').html("Preparing...");

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_get_prepare_download_progress();
                        }
                        else
                        {
                            jQuery('#wpvivid_download_progress').hide();
                            wpvivid_unlock_download_list();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_download_progress').hide();
                        wpvivid_unlock_download_list();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_prepare_download_progress();
                });
            }

            function wpvivid_get_prepare_download_progress()
            {
                if(wpvivid_download_cancel)
                {
                    jQuery('#wpvivid_download_progress').hide();
                    wpvivid_unlock_download_list();
                    wpvivid_delete_tmp_download_files();
                    alert("Download canceled.");
                    return;
                }

                var ajax_data = {
                    'action':'wpvivid_get_prepare_download_progress'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('.wpvivid-span-processed-progress').html(jsonarray.progress);
                            jQuery(".wpvivid-span-processed-progress").width(jsonarray.width);
                            jQuery('#wpvivid_download_progress_text').html(jsonarray.html);

                            if(jsonarray.finished)
                            {
                                start_download_ready_files(wpvivid_download_backup_id);
                            }
                            else
                            {
                                if(jsonarray.set_timeout)
                                {
                                    setTimeout(function ()
                                    {
                                        wpvivid_get_prepare_download_progress();
                                    }, 1000);
                                }
                                else
                                {
                                    wpvivid_get_prepare_download_progress();
                                }
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_download_progress').hide();
                            wpvivid_unlock_download_list();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_download_progress').hide();
                        wpvivid_unlock_download_list();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_prepare_download_progress();
                });
            }

            function wpvivid_cancel_download()
            {
                wpvivid_download_cancel=true;
                jQuery('#wpvivid_cancel_download').css({'pointer-events': 'none', 'opacity': '0.4'});
            }

            function wpvivid_close_all_tr()
            {
                jQuery('#wpvivid_download_list').find('.wpvivid-sub-tr').each(function()
                {
                    jQuery(this).hide();
                    jQuery(this).removeClass('active');
                });

                jQuery('#wpvivid_download_list').find('.wpvivid-close').each(function()
                {
                    jQuery(this).closest('tr').removeClass('active');
                    jQuery(this).removeClass('wpvivid-close');
                    jQuery(this).removeClass('dashicons-minus');
                    jQuery(this).addClass('wpvivid-expand');
                    jQuery(this).addClass('dashicons-plus-alt2');
                });
            }

            function wpvivid_lock_download_list()
            {
                jQuery('#wpvivid_download_list').find('.wpvivid-download').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_download_list').find('.wpvivid_bulk_download2').css({'pointer-events': 'none', 'opacity': '0.4'});
                //
                jQuery('.wpvivid_bulk_download').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_incremental_backup_download').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_incremental_backup_scan').css({'pointer-events': 'none', 'opacity': '0.4'});
            }

            function wpvivid_unlock_download_list()
            {
                jQuery('#wpvivid_download_list').find('.wpvivid-download').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('#wpvivid_download_list').find('.wpvivid_bulk_download2').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('.wpvivid_bulk_download').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('#wpvivid_incremental_backup_download').css({'pointer-events': 'auto', 'opacity': '1'});
                jQuery('#wpvivid_incremental_backup_scan').css({'pointer-events': 'auto', 'opacity': '1'});
            }
        </script>
        <?php
        $this->download_tools_ex();
    }

    public function download_tools_ex()
    {
        $wpvivid_ignore_md5=get_option('wpvivid_download_backup_ignore_md5', '0');
        ?>
        <a id="wpvivid_a_link" style="display: none;"></a>
        <script>
            var wpvivid_download_list = Array();
            var wpvivid_downloading = false;
            var wpvivid_current_retry = 0;
            var wpvivid_max_retry = 3;
            var wpvivid_offset_size = 0;
            var wpvivid_chunk_size = 2*1024*1024;
            var wpvivid_file_name;
            var wpvivid_file_size;
            var wpvivid_file_md5;
            var wpvivid_file_data;
            var wpvivid_dl_method = 0;
            var wpvivid_dl_blob_array = [];
            var wpvivid_download_backup_ignore_md5='<?php echo $wpvivid_ignore_md5; ?>';

            if(window.webkitRequestFileSystem)
            {
                window.requestFileSystem  = window.webkitRequestFileSystem;
                wpvivid_dl_method = 1;
            }
            else if ("download" in document.createElementNS("http://www.w3.org/1999/xhtml", "a"))
            {
                wpvivid_dl_method = 2;
            }
            else
            {
                wpvivid_dl_method = 3;
            }

            function wpvivid_post_request_file(ajax_data, callback, error_callback, time_out)
            {
                if(typeof time_out === 'undefined')    time_out = 30000;
                ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
                jQuery.ajax({
                    type: "post",
                    url: wpvivid_ajax_object_addon.ajax_url,
                    data: ajax_data,
                    cache:false,
                    async: false,
                    dataType: "binary",
                    success: function (data) {
                        callback(data);
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        error_callback(XMLHttpRequest, textStatus, errorThrown);
                    },
                    timeout: time_out
                });
            }

            function wpvivid_get_next_download()
            {
                if(wpvivid_downloading)
                {
                    return;
                }

                if(wpvivid_download_cancel)
                {
                    wpvivid_delete_tmp_download_files();
                    jQuery('#wpvivid_download_progress').hide();
                    wpvivid_unlock_download_list();
                    alert("Download canceled.");
                    return;
                }

                if(wpvivid_download_list.length > 0)
                {
                    var download_info = wpvivid_download_list.shift();
                    wpvivid_file_name = download_info.file_name;
                    wpvivid_file_size = download_info.file_size;
                    wpvivid_file_md5  = download_info.file_md5;
                    wpvivid_offset_size = 0;
                    wpvivid_dl_blob_array = [];
                    wpvivid_start_download();
                }
                else
                {
                    wpvivid_delete_tmp_download_files();
                    alert('All files of the backup have been downloaded successfully.');
                    jQuery('.wpvivid-local-site-download-all').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_download_progress').hide();
                    wpvivid_unlock_download_list();
                    wpvivid_init_download_page(wpvivid_download_backup_id);
                }
            }

            function wpvivid_download_retry()
            {
                if(wpvivid_current_retry < wpvivid_max_retry)
                {
                    wpvivid_start_download();
                    return true;
                }
                else
                {
                    jQuery('#wpvivid_download_progress').hide();
                    wpvivid_unlock_download_list();
                    return false;
                }
            }

            function wpvivid_start_download()
            {
                if(wpvivid_download_cancel)
                {
                    wpvivid_delete_tmp_download_files();
                    jQuery('#wpvivid_download_progress').hide();
                    wpvivid_unlock_download_list();
                    alert("Download canceled.");
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_read_file_content',
                    'file_name': wpvivid_file_name,
                    'chunk_size': wpvivid_chunk_size,
                    'offset_size': wpvivid_offset_size
                };
                wpvivid_post_request_file(ajax_data, function (data)
                {
                    wpvivid_current_retry=0;
                    try
                    {
                        wpvivid_file_data = data;
                        create_download_file();
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (code)
                {
                    wpvivid_current_retry++;
                    if(!wpvivid_download_retry())
                    {
                        alert('http error: '+code);
                    }
                });
            }

            function create_download_file()
            {
                if(wpvivid_dl_method==1)
                {
                    window.requestFileSystem(window.TEMPORARY, 50*1024*1024, initFSDownloadBackup, errorHandler);
                }
                else if(wpvivid_dl_method==2)
                {
                    wpvivid_blob_downloading();
                }
            }

            function wpvivid_blob_downloading()
            {
                wpvivid_downloading = true;
                var file_name = wpvivid_file_name;
                var file_data = wpvivid_file_data;
                wpvivid_dl_blob_array.push(file_data);
                var percent = parseInt((wpvivid_offset_size / wpvivid_file_size) * 100);
                if(percent > 100) percent = 100;

                jQuery('.wpvivid-span-processed-progress').html(percent+'%&nbspcompleted');
                jQuery(".wpvivid-span-processed-progress").width(percent+'%' );
                jQuery('#wpvivid_download_progress_text').html('Downloading: '+wpvivid_file_name);

                if(wpvivid_offset_size < wpvivid_file_size)
                {
                    wpvivid_offset_size += wpvivid_chunk_size;
                    wpvivid_start_download();
                }
                else
                {
                    var a = document.getElementById('wpvivid_a_link');
                    var url=window.URL.createObjectURL(new Blob(wpvivid_dl_blob_array));
                    a.download = file_name;
                    a.href = url;
                    a.click();
                    setTimeout(function()
                    {
                        window.URL.revokeObjectURL(document.getElementById('wpvivid_a_link').href);
                    },100);
                    wpvivid_downloading = false;
                    wpvivid_dl_blob_array = [];
                    wpvivid_get_next_download();
                }
            }

            function initFSDownloadBackup(fs)
            {
                wpvivid_downloading = true;
                var file_name = wpvivid_file_name;
                var file_data = wpvivid_file_data;

                function createDir(rootDir, folders)
                {
                    rootDir.getDirectory(folders[0], {create: true/*, exclusive: true*/}, function(dirEntry) {
                        if (folders.length) {
                            createDir(dirEntry, folders.slice(1));
                        }
                    }, errorHandler);
                }
                createDir(fs.root, 'wpvividbackups'.split('/'));

                fs.root.getFile('wpvividbackups/'+file_name, {create: true, exclusive: false}, function(fileEntry)
                {
                    fileEntry.createWriter(function(fileWriter)
                    {
                        fileWriter.onerror = function(e)
                        {
                            fileEntry.remove(function() {
                                console.log('Delete success');
                            }, errorHandler);

                            console.log('Write failed: ' + e.toString());
                            wpvivid_downloading=false;
                            if(!wpvivid_download_retry())
                            {
                                alert('Download failed: ' + e.toString());
                            }
                        }

                        fileWriter.onwriteend = function()
                        {
                            var percent = parseInt((wpvivid_offset_size / wpvivid_file_size) * 100);
                            if(percent > 100) percent = 100;

                            jQuery('.wpvivid-span-processed-progress').html(percent+'%&nbspcompleted');
                            jQuery(".wpvivid-span-processed-progress").width(percent+'%' );
                            jQuery('#wpvivid_download_progress_text').html('Downloading: '+wpvivid_file_name);
                            if(wpvivid_offset_size < wpvivid_file_size)
                            {
                                wpvivid_offset_size += wpvivid_chunk_size;
                                wpvivid_start_download();
                            }
                            else
                            {
                                readAndMD5();
                            }
                        }

                        let data = new Blob([file_data], { type: "application/zip" });
                        fileWriter.seek(wpvivid_offset_size);
                        fileWriter.write(data);

                    }, errorHandler);
                }, errorHandler);

                function readAndMD5()
                {
                    fs.root.getFile('wpvividbackups/'+file_name, {}, function(fileEntry)
                    {
                        fileEntry.file( function(file)
                        {
                            var blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice;
                            var chunkSize = 2097152;
                            var chunks = Math.ceil(file.size / chunkSize);
                            var currentChunk = 0;

                            var spark = new SparkMD5.ArrayBuffer();
                            var fileReader = new FileReader();

                            fileReader.onload = function (e)
                            {
                                spark.append(e.target.result);
                                currentChunk++;

                                if (currentChunk < chunks) {
                                    loadNext();
                                }
                                else {
                                    var a = document.getElementById('wpvivid_a_link');
                                    var url = fileEntry.toURL();
                                    a.download = file_name;
                                    a.href = url;
                                    a.click();

                                    wpvivid_downloading = false;
                                    wpvivid_get_next_download();
                                    /*
                                    var md5 = spark.end();
                                    if (md5 === wpvivid_file_md5)
                                    {
                                        var a = document.getElementById('wpvivid_a_link');
                                        var url = fileEntry.toURL();
                                        a.download = file_name;
                                        a.href = url;
                                        a.click();

                                        wpvivid_downloading = false;
                                        wpvivid_get_next_download();
                                    }
                                    else
                                    {
                                        console.log(md5+':'+wpvivid_file_md5);
                                        console.log('MD5 not match.');
                                    }*/
                                }
                            };

                            fileReader.onerror = function () {
                                console.warn('oops, something went wrong.');
                            };

                            function loadNext() {
                                var start = currentChunk * chunkSize,
                                    end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;

                                fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
                            }

                            loadNext();
                        });
                    });
                }
            }

            function errorHandler(e)
            {
                var msg = '';

                switch (e.code) {
                    case FileReader.QUOTA_EXCEEDED_ERR:
                        msg = 'QUOTA_EXCEEDED_ERR';
                        break;
                    case FileReader.NOT_FOUND_ERR:
                        msg = 'NOT_FOUND_ERR';
                        break;
                    case FileReader.SECURITY_ERR:
                        msg = 'SECURITY_ERR';
                        break;
                    case FileReader.INVALID_MODIFICATION_ERR:
                        msg = 'INVALID_MODIFICATION_ERR';
                        break;
                    case FileReader.INVALID_STATE_ERR:
                        msg = 'INVALID_STATE_ERR';
                        break;
                    default:
                        msg = 'Unknown Error';
                        break;
                }
                console.log('Error: ' + msg+' Code '+e.code);
            }

            function start_download_ready_files(backup_id)
            {
                if(wpvivid_dl_method==0)
                {
                    alert("We have detected that your browser does not support bulk downloading of files, please download the backup files one by one.");
                    wpvivid_unlock_download_list();
                    jQuery('#wpvivid_download_progress').hide();
                    return;
                }


                if(wpvivid_dl_method==3)
                {
                    var ajax_data = {
                        'action': 'wpvivid_get_ready_download_files_url',
                        'backup_id': backup_id
                    };

                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery.each(jsonarray.urls, function(index, value)
                                {
                                    var a = document.getElementById('wpvivid_a_link');
                                    var url=ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_backup_ex&backup_id='+backup_id+'&file_name='+value.file_name;
                                    a.download = value.file_name;
                                    a.href = url;
                                    a.click();
                                });

                                alert('All files of the backup have been downloaded successfully.');
                                jQuery('.wpvivid-local-site-download-all').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_download_progress').hide();
                                wpvivid_unlock_download_list();
                                wpvivid_init_download_page(backup_id);
                            }
                            else
                            {
                                alert(jsonarray.error);
                                wpvivid_unlock_download_list();
                                jQuery('#wpvivid_download_progress').hide();
                            }
                        }
                        catch (err)
                        {
                            alert(err);
                            wpvivid_unlock_download_list();
                            jQuery('#wpvivid_download_progress').hide();
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('get need download files', textStatus, errorThrown);
                        alert(error_message);
                        wpvivid_unlock_download_list();
                        jQuery('#wpvivid_download_progress').hide();
                    });
                }

                var ajax_data = {
                    'action': 'wpvivid_get_ready_download_files',
                    'backup_id': backup_id,
                    'ignore_md5':wpvivid_download_backup_ignore_md5
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_download_list = Array();
                            wpvivid_dl_blob_array = [];

                            var num = 0;
                            jQuery.each(jsonarray.files, function(index, value){
                                if(wpvivid_download_backup_ignore_md5 === '1')
                                {
                                    wpvivid_download_list[num] = new Array('file_name', 'file_size');
                                    wpvivid_download_list[num]['file_name'] = value.file_name;
                                    wpvivid_download_list[num]['file_size'] = value.file_size;
                                }
                                else
                                {
                                    wpvivid_download_list[num] = new Array('file_name', 'file_size', 'file_md5');
                                    wpvivid_download_list[num]['file_name'] = value.file_name;
                                    wpvivid_download_list[num]['file_size'] = value.file_size;
                                    wpvivid_download_list[num]['file_md5']  = value.file_md5;
                                    console.log(value.file_md5);
                                }
                                num++;
                            });

                            jQuery('.wpvivid-span-processed-progress').html("0% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "0%" );
                            jQuery('#wpvivid_download_progress_text').html("Start downloading...");

                            wpvivid_get_next_download();
                        }
                        else
                        {
                            alert(jsonarray.error);
                            wpvivid_unlock_download_list();
                            jQuery('#wpvivid_download_progress').hide();
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                        wpvivid_unlock_download_list();
                        jQuery('#wpvivid_download_progress').hide();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    if(wpvivid_download_backup_ignore_md5 === '1')
                    {
                        var error_message = wpvivid_output_ajaxerror('get need download files', textStatus, errorThrown);
                        alert(error_message);
                        wpvivid_unlock_download_list();
                        jQuery('#wpvivid_download_progress').hide();
                    }
                    else
                    {
                        var error_message = 'Download timeout. This may be due to a MD5 calculation failure caused by server limits. Click OK to ignore MD5 calculation (permanently) and re-download.';
                        var ret = confirm(error_message);
                        if(ret === true)
                        {
                            wpvivid_download_backup_ignore_md5='1';
                            start_download_ready_files(backup_id);
                        }
                        else
                        {
                            wpvivid_unlock_download_list();
                            jQuery('#wpvivid_download_progress').hide();
                        }
                    }
                });
            }

            function wpvivid_delete_tmp_download_files()
            {
                var ajax_data = {
                    'action': 'wpvivid_delete_tmp_download_files',
                    'backup_id': wpvivid_download_backup_id,
                };

                wpvivid_post_request_file(ajax_data, function (data)
                {
                }, function (code)
                {
                });
            }
        </script>
        <?php
    }
}

class WPvivid_Backup_Download_TaskEx
{
    public $task;

    public function init_prepare_files_task($files,$backup)
    {
        $this->task=array();

        $this->task['status']['start_time']=time();
        $this->task['status']['run_time']=time();
        $this->task['status']['timeout']=time();
        $this->task['status']['str']='running';
        $this->task['status']['resume_count']=0;
        $this->task['status']['current_job']=false;
        $this->task['backup']=$backup;
        $this->task['jobs']=array();
        $index=1;
        foreach ($files as $file)
        {
            if($file['need_download'])
            {
                $this->task['jobs'][$index]['type']='download';
                $this->task['jobs'][$index]['download_file']=$file['need_download_file'];
                $this->task['jobs'][$index]['finished']=0;
                $this->task['jobs'][$index]['progress']=0;
                $index++;
            }
        }

        update_option('wpvivid_prepare_download_files_task',$this->task,'no');

        $ret['result']='success';
        $ret['test']=$this->task;
        return $ret;
    }

    public function do_prepare_files_task()
    {
        $finished=true;

        foreach ($this->task['jobs'] as $job)
        {
            if($job['finished']==0)
            {
                $finished=false;
                break;
            }
        }

        if (!$finished)
        {
            $job_key=false;
            foreach ($this->task['jobs'] as $key=>$job)
            {
                if($job['finished']==0)
                {
                    $job_key=$key;
                    break;
                }
            }

            $ret=$this->do_prepare_files_job($job_key);
            if($ret['result']=='success')
            {
                $this->task['status']['resume_count']=0;
                $this->task['status']['str']='ready';
                $this->update_task();
            }
            else
            {
                $this->task['status']['str']='error';
                $this->task['status']['error']=$ret['error'];
                $this->update_task();
            }

            return $ret;
        }

        $ret['result']='success';
        return $ret;
    }

    public function do_prepare_files_job($key)
    {
        $this->task['status']['str']='running';
        $this->task['status']['current_job']=$key;
        $this->update_task();

        $job=$this->task['jobs'][$key];

        if($job['type']=='download')
        {
            $ret=$this->do_download_file($key);
            if($ret['result']=='success')
            {
                if($ret['finished']==1)
                {
                    $this->task['jobs'][$key]['finished']=1;
                    $this->update_task();
                    return $ret;
                }
                else
                {
                    return $ret;
                }
            }
            else
            {
                return $ret;
            }
        }

        $this->task['jobs'][$key]['finished']=1;

        $this->update_task();

        $ret['result']='success';
        return $ret;
    }

    public function do_download_file($key)
    {
        if(!isset($this->task['jobs'][$key]['download']))
        {
            $backup_item = new WPvivid_New_Backup_Item($this->task['backup']);
            $root_path=$backup_item->get_download_local_path();
            if(!file_exists($root_path))
            {
                @mkdir($root_path);
            }

            $remotes=$this->task['backup']['remote'];
            $remote_option=array_shift($remotes);

            if(is_null($remote_option))
            {
                return array('result' => WPVIVID_FAILED ,'error'=>'Retrieving the cloud storage information failed while downloading backups. Please try again later.');
            }

            if($this->task['backup']['type']=='Incremental')
            {
                if(isset($this->task['backup']['incremental_path']) && !empty($this->task['backup']['incremental_path']))
                {
                    if($remote_option['type']=='ftp'||$remote_option['type']=='sftp')
                    {
                        if(isset($remote_option['custom_path']))
                        {
                            $remote_option['custom_path']=$remote_option['custom_path'].'/'.$this->task['backup']['incremental_path'];
                        }
                        else
                        {
                            $remote_option['path']=$remote_option['path'].'/'.$this->task['backup']['incremental_path'];
                        }
                    }
                    else
                    {
                        $remote_option['path']=$remote_option['path'].'/'.$this->task['backup']['incremental_path'];
                    }
                }
            }

            if(file_exists($root_path.$this->task['jobs'][$key]['download_file']))
            {
                unlink($root_path.$this->task['jobs'][$key]['download_file']);
            }

            $this->task['jobs'][$key]['download']['remote_option']=$remote_option;
            $this->task['jobs'][$key]['download']['file_name']=$this->task['jobs'][$key]['download_file'];
            foreach ($this->task['backup']['backup']['files'] as $file)
            {
                if($this->task['jobs'][$key]['download']['file_name']==$file['file_name'])
                {
                    $this->task['jobs'][$key]['download']['size']=$file['size'];
                    break;
                }
            }
            $this->task['jobs'][$key]['download']['root_path']=$root_path;
            $tmp_name= uniqid('wpvividtmp-');
            $this->task['jobs'][$key]['download']['local_path']=$root_path.$tmp_name;
            $this->task['jobs'][$key]['download']['offset']=0;
            $this->update_task();
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($this->task['jobs'][$key]['download']['remote_option']);

        $ret=$remote->chunk_download($this->task['jobs'][$key]['download'],array($this,'download_callback_v2'));
        if($ret['result']=='success')
        {
            $result['result']='success';
            $result['finished']=$ret['finished'];
            $this->task['jobs'][$key]['download']['offset']=$ret['offset'];
            $this->update_task();
            return $result;
        }
        else
        {
            return $ret;
        }
    }

    public function download_callback_v2($offset,$current_name,$current_size,$last_time,$last_size)
    {
        $key=$this->task['status']['current_job'];
        $this->task['jobs'][$key]['progress']= intval(($offset/$current_size)* 100) ;
        $this->task['jobs'][$key]['progress_text']='Downloading file:'.$current_name.' from remote storage | Total size:'.size_format($current_size,2).' Downloaded Size:'.size_format($offset,2);
        $this->update_task();
    }

    private function update_task()
    {
        $this->task['status']['run_time']=time();
        update_option('wpvivid_prepare_download_files_task',$this->task,'no');
    }
}