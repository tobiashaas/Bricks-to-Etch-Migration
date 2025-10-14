<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Admin_load: yes
 * Need_init: yes
 * Interface Name: Wpvivid_BackupUploader_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class Wpvivid_BackupUploader_addon
{
    public function __construct()
    {
        //upload_meta_box
        add_action('wp_ajax_wpvivid_is_backup_file',array($this,'is_backup_file'));

        add_action('wp_ajax_wpvivid_addon_upload_files',array($this,'upload_files'));
        add_action('wp_ajax_wpvivid_addon_upload_files_finish',array($this,'upload_files_finish'));
        add_action('wp_ajax_wpvivid_addon_delete_upload_incomplete_backup', array($this, 'delete_upload_incomplete_backup'));
        add_action('wp_ajax_wpvivid_addon_rescan_local_folder',array($this,'rescan_local_folder_set_backup'));
        add_action('wp_ajax_wpvivid_cancel_upload_backup_addon', array($this, 'cancel_upload_backup_addon'));

        //upload_meta_box_ex
        add_action('wp_ajax_wpvivid_addon_upload_files_ex',array($this,'upload_files_ex'));
        add_action('wp_ajax_wpvivid_addon_get_file_id_ex',array($this,'get_file_id'));
        add_action('wp_ajax_wpvivid_addon_upload_files_finish_ex',array($this,'upload_files_finish_ex'));
        add_action('wp_ajax_wpvivid_restore_upload_backup_ex', array($this, 'restore_upload_backup_ex'));
        add_action('wp_ajax_wpvivid_addon_delete_upload_incomplete_backup_ex', array($this, 'delete_upload_incomplete_backup_ex'));
        add_action('wp_ajax_wpvivid_cancel_upload_backup_addon_ex', array($this, 'cancel_upload_backup_addon_ex'));
        //add_action('wp_ajax_wpvivid_addon_get_backup_count',array($this,'get_backup_count'));



        add_action('wpvivid_rebuild_backup_list', array($this, 'wpvivid_rebuild_backup_list'), 9);
    }

    public function is_backup_file()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-mange-backup");

        try
        {
            if (isset($_POST['file_name']))
            {
                if (WPvivid_backup_pro_function::is_wpvivid_backup($_POST['file_name']))
                {
                    $ret['result'] = WPVIVID_PRO_SUCCESS;

                    $filePath = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$_POST['file_name'];
                    if(file_exists($filePath))
                    {
                        $ret['is_exists']=true;
                    }
                    else
                    {
                        $ret['is_exists']=false;
                    }
                }
                else
                {
                    $ret['result'] = WPVIVID_PRO_FAILED;
                    $ret['error'] = $_POST['file_name'] . ' is not created by WPvivid backup plugin.';
                }
            }
            else
            {
                $ret['result'] = WPVIVID_PRO_FAILED;
                $ret['error'] = 'Failed to post file name.';
            }

            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            echo json_encode(array('result'=>'failed','error'=>$message));
        }

        die();
    }

    public function get_file_id()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-import-site");
        try {
            if (isset($_POST['file_name'])) {
                if (WPvivid_backup_pro_function::is_wpvivid_backup($_POST['file_name']))
                {
                    if ($id =WPvivid_backup_pro_function::get_wpvivid_backup_id($_POST['file_name']))
                    {
                        $backup_list=new WPvivid_New_BackupList();
                        if ($backup_list->get_backup_by_id($id) === false)
                        {
                            $ret['result'] = WPVIVID_PRO_SUCCESS;
                            $ret['id'] = $id;
                        } else {
                            $ret['result'] = WPVIVID_PRO_FAILED;
                            $ret['error'] = 'The uploading backup already exists in Backups list.';
                        }
                    } else {
                        $ret['result'] = WPVIVID_PRO_FAILED;
                        $ret['error'] = $_POST['file_name'] . ' is not created by WPvivid backup plugin.';
                    }
                } else {
                    $ret['result'] = WPVIVID_PRO_FAILED;
                    $ret['error'] = $_POST['file_name'] . ' is not created by WPvivid backup plugin.';
                }
            } else {
                $ret['result'] = WPVIVID_PRO_FAILED;
                $ret['error'] = 'Failed to post file name.';
            }

            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function check_file_is_a_wpvivid_backup($file_name,&$backup_id)
    {
        if (WPvivid_backup_pro_function::is_wpvivid_backup($file_name))
        {
            if ($id =WPvivid_backup_pro_function::get_wpvivid_backup_id($file_name))
            {
                $white_label_id = str_replace(apply_filters('wpvivid_white_label_file_prefix', 'wpvivid'), 'wpvivid', $id);
                $backuplist=new WPvivid_New_BackupList();
                if($backuplist->get_backup_by_id($id)===false &&$backuplist->get_backup_by_id($white_label_id)===false)
                {
                    $backup_id=$id;
                    return true;
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
        else
        {
            return false;
        }
    }

    public function upload_files()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-mange-backup");
        try
        {
            $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
            $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

            $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];

            $filePath = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$fileName;
            $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");

            if ($out)
            {
                // Read binary input stream and append it to temp file
                $options['test_form'] =true;
                $options['action'] ='wpvivid_addon_upload_files';
                $options['test_type'] = false;
                $options['ext'] = 'zip';
                $options['type'] = 'application/zip';

                add_filter('upload_dir', array($this, 'upload_dir'));

                $status = wp_handle_upload($_FILES['async-upload'],$options);

                remove_filter('upload_dir', array($this, 'upload_dir'));

                $in = @fopen($status['file'], "rb");

                if ($in)
                {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                }
                else
                {
                    echo json_encode(array('result'=>'failed','error'=>"Failed to open tmp file.path:".$status['file']));
                    die();
                }

                @fclose($in);
                @fclose($out);

                @unlink($status['file']);
            }
            else
            {
                echo json_encode(array('result'=>'failed','error'=>"Failed to open input stream.path:{$filePath}.part"));
                die();
            }

            if (!$chunks || $chunk == $chunks - 1)
            {
                // Strip the temp .part suffix off
                rename("{$filePath}.part", $filePath);
            }

            echo json_encode(array('result' => WPVIVID_PRO_SUCCESS));
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function upload_files_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-import-site");
        try
        {
            $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
            $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

            $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : $_FILES["file"]["name"];

            $filePath = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$fileName;
            $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");

            if ($out)
            {
                // Read binary input stream and append it to temp file
                $options['test_form'] =true;
                $options['action'] ='wpvivid_addon_upload_files_ex';
                $options['test_type'] = false;
                $options['ext'] = 'zip';
                $options['type'] = 'application/zip';

                add_filter('upload_dir', array($this, 'upload_dir'));

                $status = wp_handle_upload($_FILES['async-upload'],$options);

                remove_filter('upload_dir', array($this, 'upload_dir'));

                $in = @fopen($status['file'], "rb");

                if ($in)
                {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                }
                else
                {
                    echo json_encode(array('result'=>'failed','error'=>"Failed to open tmp file.path:".$status['file']));
                    die();
                }

                @fclose($in);
                @fclose($out);

                @unlink($status['file']);
            }
            else
            {
                echo json_encode(array('result'=>'failed','error'=>"Failed to open input stream.path:{$filePath}.part"));
                die();
            }

            if (!$chunks || $chunk == $chunks - 1)
            {
                // Strip the temp .part suffix off
                rename("{$filePath}.part", $filePath);
            }

            echo json_encode(array('result' => WPVIVID_PRO_SUCCESS));
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function upload_files_finish()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-mange-backup");
        try {
            $ret = $this->_rescan_local_folder_set_backup();
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

    public function upload_files_finish_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-import-site");
        try {
            $ret = $this->_rescan_local_folder_set_backup();
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

    public function delete_upload_incomplete_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-mange-backup");
        try {
            if(isset($_POST['incomplete_backup'])&&!empty($_POST['incomplete_backup']))
            {
                $json = $_POST['incomplete_backup'];
                $json = stripslashes($json);
                $incomplete_backup = json_decode($json, true);

                if(is_array($incomplete_backup) && !empty($incomplete_backup))
                {
                    $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
                    foreach ($incomplete_backup as $backup)
                    {
                        $backup = basename($backup);
                        if (preg_match('/wpvivid-.*_.*_.*\.zip$/', $backup))
                        {
                            @unlink($path.$backup);
                        }
                        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_.*\.zip$/', $backup))
                        {
                            @unlink($path.$backup);
                        }
                    }
                }

                $ret['result']='success';
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

    public function delete_upload_incomplete_backup_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-import-site");
        try {
            if(isset($_POST['incomplete_backup'])&&!empty($_POST['incomplete_backup']))
            {
                $json = $_POST['incomplete_backup'];
                $json = stripslashes($json);
                $incomplete_backup = json_decode($json, true);

                if(is_array($incomplete_backup) && !empty($incomplete_backup))
                {
                    $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
                    foreach ($incomplete_backup as $backup)
                    {
                        $backup = basename($backup);
                        if (preg_match('/wpvivid-.*_.*_.*\.zip$/', $backup))
                        {
                            @unlink($path.$backup);
                        }
                        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_.*\.zip$/', $backup))
                        {
                            @unlink($path.$backup);
                        }
                    }
                }

                $ret['result']='success';
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

    function clean_tmp_files($path, $filename)
    {
        $handler=opendir($path);
        if($handler!==false)
        {
            while(($file=readdir($handler))!==false)
            {
                if (!is_dir($path.$file) && preg_match('/wpvivid-.*_.*_.*\.tmp$/', $file))
                {
                    $iPos = strrpos($file, '_');
                    $file_temp = substr($file, 0, $iPos);
                    if($file_temp === $filename) {
                        @unlink($path.$file);
                    }
                }
            }
            @closedir($handler);
        }

    }

    function check_wpvivid_file_info($file_name, &$backup_id, &$need_update=false)
    {
        if(WPvivid_backup_pro_function::is_wpvivid_backup($file_name))
        {
            if($id=WPvivid_backup_pro_function::get_wpvivid_backup_id($file_name))
            {
                $backup_id=$id;
                $backup_list=new WPvivid_New_BackupList();
                if($backup_list->get_backup_by_id($id)===false)
                {
                    $need_update = false;
                    return true;
                }
                else
                {
                    $is_remote_backup = false;

                    if($backup_list->get_remote_backup_by_id($id))
                    {
                        $is_remote_backup = true;
                    }

                    if($is_remote_backup)
                    {
                        $need_update = false;
                        return false;
                    }
                    else {
                        $need_update = true;
                        return true;
                    }
                }
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

    function wpvivid_check_remove_update_backup($path)
    {
        $backup_list = WPvivid_Setting::get_option('wpvivid_backup_list');
        $remove_backup_array = array();
        $update_backup_array = array();
        $tmp_file_array = array();
        $backup_id_array = array();

        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..")
                    {
                        if (!is_dir($path  . $filename))
                        {
                            if($this->check_wpvivid_file_info($filename,$backup_id, $need_update))
                            {
                                $backup_id_array[] = $backup_id;
                                $backup_id_array[] = str_replace(apply_filters('wpvivid_white_label_file_prefix', 'wpvivid'), 'wpvivid', $backup_id);
                                if($need_update)
                                {
                                    if($this->check_is_a_wpvivid_backup($path.$filename) === true)
                                    {
                                        if(!in_array($filename, $tmp_file_array))
                                        {
                                            $add_file['file_name']=$filename;
                                            $add_file['size']=filesize($path.$filename);
                                            $tmp_file_array[] = $filename;
                                            $update_backup_array[$backup_id]['files'][]=$add_file;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if($handler)
                {
                    @closedir($handler);
                }
            }
        }

        foreach ($backup_list as $key => $value)
        {
            if(!in_array($key, $backup_id_array))
            {
                $remove_backup_array[] = $key;
            }
        }

        $this->wpvivid_remove_update_local_backup_list($remove_backup_array, $update_backup_array);
        return true;
    }

    function wpvivid_remove_update_local_backup_list($remove_backup_array, $update_backup_array)
    {
        $backup_list = WPvivid_Setting::get_option('wpvivid_backup_list');
        foreach ($remove_backup_array as $remove_backup_id)
        {
            unset($backup_list[$remove_backup_id]);
        }
        foreach ($update_backup_array as $update_backup_id => $data)
        {
            $backup_list[$update_backup_id]['backup']['files'] = $data['files'];
        }
        WPvivid_Setting::update_option('wpvivid_backup_list', $backup_list);
    }

    function _rescan_local_folder_set_backup()
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $this->wpvivid_check_remove_update_backup($path);
        $backups=array();
        $count = 0;
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..")
                    {
                        $count++;

                        if (is_dir($path  . $filename))
                        {
                            continue;
                        } else {
                            if($this->check_file_is_a_wpvivid_backup($filename,$backup_id))
                            {
                                if($this->zip_check_sum($path . $filename))
                                {
                                    if($this->check_is_a_wpvivid_backup($path.$filename) === true)
                                    {
                                        $backups[$backup_id]['files'][] = $filename;
                                    }
                                    else
                                    {
                                        $ret['incomplete_backup'][] = $filename;
                                    }
                                }
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }
        }
        else{
            $ret['result']=WPVIVID_PRO_FAILED;
            $ret['error']='Failed to get local storage directory.';
        }
        if(!empty($backups))
        {
            foreach ($backups as $backup_id =>$backup)
            {
                $backup_data['result']='success';
                $backup_data['files']=array();
                $perfix='';
                if(empty($backup['files']))
                    continue;
                $time=false;
                foreach ($backup['files'] as $file)
                {
                    if($time===false)
                    {
                        if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file,$matches))
                        {
                            $backup_time=$matches[0];
                            $time_array=explode('-',$backup_time);
                            if(sizeof($time_array)>4)
                                $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                            else
                                $time=$backup_time;
                            $time=strtotime($time);
                        }
                        else
                        {
                            $time=time();
                        }
                    }

                    $add_file['file_name']=$file;
                    $add_file['size']=filesize($path.$file);
                    $backup_data['files'][]=$add_file;
                    if(empty($perfix))
                    {
                        $perfix=$this->get_prefix($file);
                    }
                }

                $this->add_new_upload_backup($backup_id,$backup_data,$time,$perfix,'');
            }
        }
        $ret['result']=WPVIVID_PRO_SUCCESS;
        $ret['backups']=$backups;
        return $ret;
    }

    function rescan_local_folder_set_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-mange-backup");
        try {
            $ret = $this->_rescan_local_folder_set_backup();
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

    public function wpvivid_rebuild_backup_list()
    {
        global $wpvivid_plugin;
        remove_action('wpvivid_rebuild_backup_list', array($wpvivid_plugin->backup_uploader, 'wpvivid_rebuild_backup_list'));
        $this->_rescan_local_folder_set_backup();
    }

    public function add_new_upload_backup($task_id,$backup,$time,$perfix,$log='')
    {
        $backup_data=array();
        $backup_data['type']='Upload';
        $backup_data['create_time']=$time;
        $backup_data['manual_delete']=0;
        $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
        $backup_data['compress']['compress_type']='zip';
        $backup_data['save_local']=1;
        $backup_data['log']='';

        $backup_data['backup']=$backup;
        $backup_data['remote']=array();
        $backup_data['lock']=0;
        $backup_data['backup_prefix'] = $perfix;
        $backup_list='wpvivid_backup_list';

        $backup_list=apply_filters('get_wpvivid_backup_list_name',$backup_list,$task_id,$backup_data);

        $list = WPvivid_Setting::get_option($backup_list);
        $list[$task_id]=$backup_data;
        WPvivid_Setting::update_option($backup_list,$list);
    }

    public function get_prefix($file_name)
    {
        if(preg_match('#^.*_wpvivid-#',$file_name,$matches))
        {
            $prefix=$matches[0];
            $prefix=substr($prefix,0,strlen($prefix)-strlen('_wpvivid-'));
            return $prefix;
        }
        else
        {
            return '';
        }
    }

    static function rescan_local_folder()
    {
        ?>
        <div class="wpvivid-element-space-bottom">
            <div style="float: left; margin-bottom: 10px; margin-right: 10px;">
                <input type="submit" class="button-primary" id="wpvivid_rescan_local_folder_btn" value="Scan uploaded backup or received backup" onclick="wpvivid_rescan_local_folder();" style="float: left;" />
            </div>
            <small>
                <div class="wpvivid_tooltip" style="float: left; margin: 8px 0 0 0;">?
                    <div class="wpvivid_tooltiptext">Scan all uploaded or received backups in directory <?php echo WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath(); ?></div>
                </div>
            </small>
            <div class="spinner" id="wpvivid_scanning_local_folder" style="float: left;"></div>
            <div style="clear: both;"></div>
        </div>
        <script type="text/javascript">
            function wpvivid_rescan_local_folder()
            {
                var ajax_data = {
                    'action': 'wpvivid_addon_rescan_local_folder'
                };
                jQuery('#wpvivid_rescan_local_folder_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_scanning_local_folder').addClass('is-active');
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_scanning_local_folder').removeClass('is-active');
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(typeof jsonarray.incomplete_backup !== 'undefined' && jsonarray.incomplete_backup.length > 0)
                        {
                            var incomplete_count = jsonarray.incomplete_backup.length;
                            alert('Failed to scan '+incomplete_count+' backup zips, the zips can be corrupted during creation or download process. Please check the zips.');
                        }
                        jQuery( document ).trigger( 'wpvivid_update_local_backup');
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_scanning_local_folder').removeClass('is-active');
                    var error_message = wpvivid_output_ajaxerror('scanning backup list', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function wpvivid_tran_backup_time_to_local($value)
    {
        $backup_time=$value['create_time'];
        if(isset($value['backup']['files'])){
            foreach ($value['backup']['files'] as $file_info){
                if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file_info['file_name'],$matches))
                {
                    $backup_date=$matches[0];
                }
                else
                {
                    $backup_date=$value['create_time'];
                }

                $time_array=explode('-',$backup_date);
                if(sizeof($time_array)>4){
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    $backup_time=strtotime($time);
                }
                break;
            }
        }
        return $backup_time;
    }

    public function wpvivid_get_backup_content($backup)
    {
        $content_detail = 'Please download it to localhost for identification.';
        $has_db = false;
        $has_file = false;
        $type_list = array();
        $ismerge = false;
        //ismerge ( not all )
        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                $file_name = $value['file_name'];
                if(WPvivid_backup_pro_function::is_wpvivid_db_backup($file_name))
                {
                    $has_db = true;
                    if(!in_array('Database', $type_list)) {
                        $type_list[] = 'Database';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_themes_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Themes', $type_list)) {
                        $type_list[] = 'Themes';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_plugin_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Plugins', $type_list)) {
                        $type_list[] = 'Plugins';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_uploads_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('wp-content/uploads', $type_list)) {
                        $type_list[] = 'wp-content/uploads';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_content_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('wp-content', $type_list)) {
                        $type_list[] = 'wp-content';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_core_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Wordpress Core', $type_list)) {
                        $type_list[] = 'Wordpress Core';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_other_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Additional Folder', $type_list)) {
                        $type_list[] = 'Additional Folder';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_additional_db_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Additional Database', $type_list)) {
                        $type_list[] = 'Additional Database';
                    }
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
            $backup_id = $backup['key'];
            $backup_list=new WPvivid_New_BackupList();
            $backup = $backup_list->get_backup_by_id($backup_id);
            $backup_item = new WPvivid_New_Backup_Item($backup);
            $files=$backup_item->get_files(false);
            $files_info=array();
            foreach ($files as $file)
            {
                $files_info[$file]=$backup_item->get_file_info($file);
            }
            $info=array();
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
                                $info['type'][] = $child_file_info['file_type'];
                            }
                        }
                    }
                }
                else {
                    if(isset($file_info['file_type']))
                    {
                        $info['type'][] = $file_info['file_type'];
                    }
                }
            }

            if(isset($info['type']))
            {
                foreach ($info['type'] as $backup_content)
                {
                    if ($backup_content === 'databases')
                    {
                        $has_db = true;
                        if(!in_array('Database', $type_list))
                        {
                            $type_list[] = 'Database';
                        }
                    }
                    if($backup_content === 'themes')
                    {
                        $has_file = true;
                        if(!in_array('Themes', $type_list))
                        {
                            $type_list[] = 'Themes';
                        }
                    }
                    if($backup_content === 'plugin')
                    {
                        $has_file = true;
                        if(!in_array('Plugins', $type_list))
                        {
                            $type_list[] = 'Plugins';
                        }
                    }
                    if($backup_content === 'upload')
                    {
                        $has_file = true;
                        if(!in_array('wp-content/uploads', $type_list))
                        {
                            $type_list[] = 'wp-content/uploads';
                        }
                    }
                    if($backup_content === 'wp-content')
                    {
                        $has_file = true;
                        if(!in_array('wp-content', $type_list))
                        {
                            $type_list[] = 'wp-content';
                        }
                    }
                    if($backup_content === 'wp-core')
                    {
                        $has_file = true;
                        if(!in_array('Wordpress Core', $type_list))
                        {
                            $type_list[] = 'Wordpress Core';
                        }
                    }
                    if($backup_content === 'custom')
                    {
                        $has_file = true;
                        if(!in_array('Additional Folder', $type_list))
                        {
                            $type_list[] = 'Additional Folder';
                        }
                    }
                    if($backup_content === 'additional_databases')
                    {
                        $has_file = true;
                        if(!in_array('Additional Database', $type_list))
                        {
                            $type_list[] = 'Additional Database';
                        }
                    }
                }
            }
        }

        if($has_db){
            $type_string = implode(",", $type_list);
            $content_detail = $type_string;
        }
        if($has_file){
            $type_string = implode(",", $type_list);
            $content_detail = $type_string;
        }
        if($has_db && $has_file){
            $type_string = implode(",", $type_list);
            $content_detail = $type_string;
        }
        if(!$has_db && !$has_file)
        {
            if(isset($files) && !empty($files))
            {
                foreach ($files as $file)
                {
                    if (WPvivid_backup_pro_function::is_wpvivid_backup($file))
                    {
                        if (WPvivid_backup_pro_function::is_wpvivid_db_backup($file))
                        {
                            $has_db = true;
                            $type_list[] = 'Database';
                        } else {
                            $has_file = true;
                        }
                    }
                }
            }
            if($has_db && !$has_file){
                $type_string = implode(",", $type_list);
                $content_detail = $type_string;
            }
            else {
                $content_detail = 'Please download it to localhost for identification.';
            }
        }
        return $content_detail;
    }

    public function restore_upload_backup_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-import-site");
        try{
            if(isset($_POST['upload_backup_id'])&&!empty($_POST['upload_backup_id'])){
                $ret['result'] = WPVIVID_PRO_FAILED;
                $ret['error']='unknown error';

                $backup_id = sanitize_text_field($_POST['upload_backup_id']);

                $backup_list=new WPvivid_New_BackupList();
                $backups=$backup_list->get_all_backup();
                foreach ($backups as $key=>$value)
                {
                    if($value['id'] === $backup_id)
                    {
                        $localtime = $this->wpvivid_tran_backup_time_to_local($value);
                        $localtime = date('M-d-Y H:i', $localtime);

                        if(isset($value['backup_prefix']) && !empty($value['backup_prefix']))
                        {
                            $backup_prefix = $value['backup_prefix'];
                        }
                        else{
                            $backup_prefix = 'N/A';
                        }

                        $size=0;
                        foreach ($value['backup']['files'] as $file)
                        {
                            $size+=$file['size'];
                        }
                        $size=size_format($size,2);

                        $ret['result'] = WPVIVID_PRO_SUCCESS;
                        $ret['backup_id']      = $value['id'];
                        $ret['type_string']    = $this->wpvivid_get_backup_content($value);
                        $ret['backup_time']    = $localtime;
                        $ret['backup_type']    = $value['type'];
                        $ret['backup_comment'] = $backup_prefix;
                        $ret['backup_size']    = $size;
                        $ret['backup_list']    = 'localhost_backuplist';
                        break;
                    }
                }
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function cancel_upload_backup_addon()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-mange-backup");
        try{
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
            if(is_dir($path))
            {
                $handler = opendir($path);
                if($handler!==false)
                {
                    while (($filename = readdir($handler)) !== false)
                    {
                        if ($filename != "." && $filename != "..")
                        {
                            if (is_dir($path  . $filename))
                            {
                                continue;
                            }
                            else
                            {
                                if (preg_match('/.*\.tmp$/', $filename))
                                {
                                    @unlink($path  . $filename);
                                }

                                if (preg_match('/.*\.part$/', $filename))
                                {
                                    @unlink($path  . $filename);
                                }
                            }
                        }
                    }
                    if($handler)
                        @closedir($handler);
                }
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function cancel_upload_backup_addon_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security("wpvivid-can-import-site");
        try{
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
            if(is_dir($path))
            {
                $handler = opendir($path);
                if($handler!==false)
                {
                    while (($filename = readdir($handler)) !== false)
                    {
                        if ($filename != "." && $filename != "..")
                        {
                            if (is_dir($path  . $filename))
                            {
                                continue;
                            }
                            else
                            {
                                if (preg_match('/.*\.tmp$/', $filename))
                                {
                                    @unlink($path  . $filename);
                                }

                                if (preg_match('/.*\.part$/', $filename))
                                {
                                    @unlink($path  . $filename);
                                }
                            }
                        }
                    }
                    if($handler)
                        @closedir($handler);
                }
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    static function upload_meta_box()
    {
        ?>
        <div id="wpvivid_plupload-upload-ui" class="hide-if-no-js" style="margin-bottom: 10px;">
            <div id="drag-drop-area">
                <div class="drag-drop-inside">
                    <p class="drag-drop-info"><?php _e('Drop files here'); ?></p>
                    <p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
                    <p class="drag-drop-buttons"><input id="wpvivid_select_file_button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></p>
                </div>
            </div>
        </div>
        <div id="wpvivid_uploaded_file_list" class="hide-if-no-js" style="margin-bottom: 10px;"></div>
        <div id="wpvivid_upload_file_list" class="hide-if-no-js" style="margin-bottom: 10px;"></div>
        <div style="margin-bottom: 10px;">
            <input type="submit" class="button-primary" id="wpvivid_upload_submit_btn" value="Upload" onclick="wpvivid_submit_upload();" />
            <input type="submit" class="button-primary" id="wpvivid_stop_upload_btn" value="Cancel" onclick="wpvivid_cancel_upload();" />
        </div>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span><strong><?php echo 'Please make sure to upload all the backup parts in a set (if any), at a time.'; ?></strong></span>
        </div>
        <div style="clear: both;"></div>
        <?php
        $chunk_size = min(wp_max_upload_size(), 1048576*2);
        $plupload_init = array(
            'browse_button'       => 'wpvivid_select_file_button',
            'container'           => 'wpvivid_plupload-upload-ui',
            'drop_element'        => 'drag-drop-area',
            'file_data_name'      => 'async-upload',
            'max_retries'		    => 3,
            'multiple_queues'     => true,
            'max_file_size'       => '10Gb',
            'chunk_size'        => $chunk_size.'b',
            'url'                 => admin_url('admin-ajax.php'),
            'multipart'           => true,
            'urlstream_upload'    => true,
            // additional post data to send to our ajax hook
            'multipart_params'    => array(
                '_ajax_nonce' => wp_create_nonce('wpvivid_ajax'),
                'action'      => 'wpvivid_addon_upload_files',            // the ajax action name
            ),
        );

        // we should probably not apply this filter, plugins may expect wp's media uploader...
        $plupload_init = apply_filters('plupload_init', $plupload_init);
        $upload_file_image = includes_url( '/images/media/archive.png' );
        ?>

        <script type="text/javascript">
            var uploader;

            function wpvivid_stop_upload()
            {
                var ajax_data = {
                    'action': 'wpvivid_cancel_upload_backup_addon',
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery("#wpvivid_select_file_button").prop('disabled', false);
                    jQuery('#wpvivid_upload_file_list').html("");
                    jQuery('#wpvivid_upload_submit_btn').hide();
                    jQuery('#wpvivid_stop_upload_btn').hide();
                    wpvivid_init_upload_list();
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('cancelling upload backups', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#wpvivid_upload_file_list').html("");
                    jQuery('#wpvivid_upload_submit_btn').hide();
                    jQuery('#wpvivid_stop_upload_btn').hide();
                    wpvivid_init_upload_list();
                });
            }

            function wpvivid_check_plupload_added_files(up,files)
            {
                var repeat_files = '';
                var exist_files = '';
                var file_count=files.length;
                var current_scan=0;
                var exist_count=0;
                plupload.each(files, function(file)
                {
                    var brepeat=false;
                    var file_list = jQuery('#wpvivid_upload_file_list span');
                    file_list.each(function (index, value)
                    {
                        if (value.innerHTML === file.name)
                        {
                            brepeat=true;
                        }
                    });

                    if(!brepeat)
                    {
                        var ajax_data = {
                            'action': 'wpvivid_is_backup_file',
                            'file_name':file.name
                        };
                        wpvivid_post_request_addon(ajax_data, function (data)
                        {
                            current_scan++;
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === "success")
                            {
                                if(jsonarray.is_exists==true)
                                {
                                    exist_count++;
                                    if(exist_files === '') {
                                        exist_files += file.name;
                                    }
                                    else {
                                        exist_files += ', ' + file.name;
                                    }
                                    wpvivid_file_uploaded_queued(file);
                                    uploader.removeFile(file);
                                }
                                else
                                {
                                    wpvivid_fileQueued( file );
                                }
                                if(file_count === exist_count)
                                {
                                    alert("The backup already exists in target folder.");
                                    exist_files = '';
                                }
                                else if((file_count === current_scan) && (exist_files !== ''))
                                {
                                    alert(exist_files + " already exist in target folder.");
                                    exist_files = '';
                                }
                            }
                            else if(jsonarray.result === "failed")
                            {
                                uploader.removeFile(file);
                                alert(jsonarray.error);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown)
                        {
                            current_scan++;
                            var error_message = wpvivid_output_ajaxerror('uploading backups', textStatus, errorThrown);
                            uploader.removeFile(file);
                            alert(error_message);
                        });
                    }
                    else{
                        current_scan++;
                        if(repeat_files === ''){
                            repeat_files += file.name;
                        }
                        else{
                            repeat_files += ', ' + file.name;
                        }
                    }
                });
                if(repeat_files !== ''){
                    alert(repeat_files + " already exists in upload list.");
                    repeat_files = '';
                }
            }

            function wpvivid_fileQueued(file)
            {
                jQuery('#wpvivid_upload_file_list').append(
                    '<div id="' + file.id + '" style="width: 100%; height: 36px; background: #f1f1f1; margin-bottom: 1px;">' +
                    '<img src=" <?php echo $upload_file_image; ?> " alt="" style="float: left; margin: 2px 10px 0 3px; max-width: 40px; max-height: 32px;">' +
                    '<div style="line-height: 36px; float: left; margin-left: 5px;"><span>' + file.name + '</span></div>' +
                    '<div class="fileprogress" style="line-height: 36px; float: right; margin-right: 5px;"></div>' +
                    '</div>' +
                    '<div style="clear: both;"></div>'
                );
                jQuery('#wpvivid_upload_submit_btn').show();
                jQuery('#wpvivid_stop_upload_btn').show();
                jQuery("#wpvivid_upload_submit_btn").prop('disabled', false);
            }

            function wpvivid_file_uploaded_queued(file)
            {
                jQuery('#'+file.id).remove();
            }

            function wpvivid_delete_incomplete_backups(incomplete_backup)
            {
                var ajax_data = {
                    'action': 'wpvivid_addon_delete_upload_incomplete_backup',
                    'incomplete_backup': incomplete_backup
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            function wpvivid_init_upload_list()
            {
                uploader = new plupload.Uploader(<?php echo json_encode($plupload_init); ?>);

                // checks if browser supports drag and drop upload, makes some css adjustments if necessary
                uploader.bind('Init', function(up)
                {
                    var uploaddiv = jQuery('#wpvivid_plupload-upload-ui');

                    if(up.features.dragdrop){
                        uploaddiv.addClass('drag-drop');
                        jQuery('#drag-drop-area')
                            .bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
                            .bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

                    }else{
                        uploaddiv.removeClass('drag-drop');
                        jQuery('#drag-drop-area').unbind('.wp-uploader');
                    }
                });

                uploader.init();
                // a file was added in the queue

                uploader.bind('FilesAdded', wpvivid_check_plupload_added_files);

                uploader.bind('Error', function(up, error)
                {
                    alert('Upload ' + error.file.name +' error, error code: ' + error.code + ', ' + error.message);
                    wpvivid_stop_upload();
                });

                uploader.bind('FileUploaded', function(up, file, response)
                {
                    var jsonarray = jQuery.parseJSON(response.response);
                    if(jsonarray.result == 'failed')
                    {
                        alert('upload ' + file.name + ' failed, ' + jsonarray.error);

                        uploader.stop();
                        wpvivid_stop_upload();
                    }
                    else
                    {
                        wpvivid_file_uploaded_queued(file);
                    }
                });

                uploader.bind('UploadProgress', function(up, file)
                {
                    jQuery('#' + file.id + " .fileprogress").html(file.percent + "%");
                });

                uploader.bind('UploadComplete',function(up, files)
                {
                    jQuery('#wpvivid_upload_file_list').html("");
                    jQuery('#wpvivid_upload_submit_btn').hide();
                    jQuery('#wpvivid_stop_upload_btn').hide();
                    jQuery("#wpvivid_select_file_button").prop('disabled', false);
                    var ajax_data = {
                        'action': 'wpvivid_addon_upload_files_finish'
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if(jsonarray.result === 'success')
                            {
                                if(typeof jsonarray.incomplete_backup !== 'undefined' && jsonarray.incomplete_backup.length > 0)
                                {
                                    var incomplete_count = jsonarray.incomplete_backup.length;
                                    var incomplete_backup = JSON.stringify(jsonarray.incomplete_backup);
                                    wpvivid_delete_incomplete_backups(incomplete_backup);
                                    alert('Failed to scan '+incomplete_count+' backup zips, the zips can be corrupted during creation or download process. Please check the zips.');
                                }
                                else
                                {
                                    alert('The upload has completed.');
                                }
                                jQuery( document ).trigger( 'wpvivid_update_local_backup');
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&localhost_backuplist'; ?>';
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
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('refreshing backup list', textStatus, errorThrown);
                        alert(error_message);
                    });
                    plupload.each(files, function(file)
                    {
                        if(typeof file === 'undefined')
                        {

                        }
                        else
                        {
                            uploader.removeFile(file.id);
                        }
                    });
                });

                uploader.bind('Destroy', function(up, file)
                {
                    wpvivid_stop_upload();
                });
            }

            jQuery(document).ready(function($)
            {
                // create the uploader and pass the config from above
                jQuery('#wpvivid_upload_submit_btn').hide();
                jQuery('#wpvivid_stop_upload_btn').hide();
                wpvivid_init_upload_list();
            });

            function wpvivid_submit_upload()
            {
                jQuery("#wpvivid_upload_submit_btn").prop('disabled', true);
                jQuery("#wpvivid_select_file_button").prop('disabled', true);
                uploader.refresh();
                uploader.start();
            }

            function wpvivid_cancel_upload()
            {
                uploader.destroy();
            }
        </script>
        <?php
    }

    public function upload_dir($uploads)
    {
        $uploads['path'] = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        return $uploads;
    }

    private function check_is_a_wpvivid_backup($file_name)
    {
        $ret=WPvivid_New_Backup_Item::get_backup_file_info($file_name);
        if($ret['result'] === WPVIVID_PRO_SUCCESS)
        {
            return true;
        }
        else {
            return $ret['error'];
        }
    }

    private function zip_check_sum($file_name)
    {
        return true;
    }

    static function upload_meta_box_ex()
    {
        ?>
        <div id="wpvivid_plupload-upload-ui-ex" class="hide-if-no-js" style="margin-bottom: 10px;">
            <div id="drag-drop-area">
                <div class="drag-drop-inside">
                    <p class="drag-drop-info"><?php _e('Drag & Drop files to import'); ?></p>
                    <p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
                    <p class="drag-drop-buttons"><input id="wpvivid_select_file_button_ex" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></p>
                </div>
            </div>
        </div>

        <div id="wpvivid_upload_file_list_part" style="display: none;">
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="background:#eaf1fe;border-radius:0.8em;">
                <h1 class="wpvivid_upload_complete_notice" style="text-align:center; color:#8300e9; display: none;">Importing succeeded</h1>
                <div style="border-bottom:1px solid #cccccc;">
                    <p class="wpvivid_upload_complete_notice" style="text-align:center; display: none;">
                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                        <span><strong>You can restore the backup to the site now, or restore it later on <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore'); ?>">Backup Manager Page</a></strong></span>
                    </p>
                </div>

                <div id="wpvivid_upload_file_list_ex"></div>

                <div>
                    <p style="text-align:center;"><span id="wpvivid_restore_upload_backup_now" class="wpvivid-blue" onclick="wpvivid_restore_upload_backup_now();" style="padding: 0.5em 2em;border-radius:0.4em; cursor:pointer;display: none;">Restore Now</span></p>
                </div>
            </div>
        </div>

        <div style="clear: both;"></div>
        <div style="margin-top: 10px;">
            <input type="submit" class="button-primary" id="wpvivid_upload_submit_btn_ex" value="Upload" onclick="wpvivid_submit_upload_ex();" />
            <input type="submit" class="button-primary" id="wpvivid_stop_upload_btn_ex" value="Cancel" onclick="wpvivid_stop_upload_ex();" />
        </div>
        <div style="clear: both;"></div>
        <?php
        $chunk_size = min(wp_max_upload_size()-1024, 1048576*2);
        $plupload_init_ex = array(
            'runtimes'            => 'html5,silverlight,flash,html4',
            'browse_button'       => 'wpvivid_select_file_button_ex',
            'container'           => 'wpvivid_plupload-upload-ui-ex',
            'drop_element'        => 'drag-drop-area',
            'file_data_name'      => 'async-upload',
            'max_retries'		    => 3,
            'multiple_queues'     => true,
            'max_file_size'       => '10Gb',
            'chunk_size'        => $chunk_size.'b',
            'url'                 => admin_url('admin-ajax.php'),
            'flash_swf_url'       => includes_url('js/plupload/plupload.flash.swf'),
            'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
            'multipart'           => true,
            'urlstream_upload'    => true,
            // additional post data to send to our ajax hook
            'multipart_params'    => array(
                '_ajax_nonce' => wp_create_nonce('wpvivid_ajax'),
                'action'      => 'wpvivid_addon_upload_files_ex',            // the ajax action name
            ),
        );

        if (is_file(ABSPATH.WPINC.'/js/plupload/Moxie.swf')) {
            $plupload_init_ex['flash_swf_url'] = includes_url('js/plupload/Moxie.swf');
        } else {
            $plupload_init_ex['flash_swf_url'] = includes_url('js/plupload/plupload.flash.swf');
        }

        if (is_file(ABSPATH.WPINC.'/js/plupload/Moxie.xap')) {
            $plupload_init_ex['silverlight_xap_url'] = includes_url('js/plupload/Moxie.xap');
        } else {
            $plupload_init_ex['silverlight_xap_url'] = includes_url('js/plupload/plupload.silverlight.swf');
        }

        // we should probably not apply this filter, plugins may expect wp's media uploader...
        $plupload_init_ex = apply_filters('plupload_init', $plupload_init_ex);
        $upload_file_image = includes_url( '/images/media/archive.png' );
        ?>


        <script type="text/javascript">
            var uploader_ex;

            var wpvivid_upload_id_ex='';
            var wpvivid_upload_complete_id='';

            function wpvivid_init_upload_list_ex()
            {
                uploader_ex = new plupload.Uploader(<?php echo json_encode($plupload_init_ex); ?>);

                // checks if browser supports drag and drop upload, makes some css adjustments if necessary
                uploader_ex.bind('Init', function(up)
                {
                    var uploaddiv = jQuery('#wpvivid_plupload-upload-ui-ex');

                    if(up.features.dragdrop){
                        uploaddiv.addClass('drag-drop');
                        jQuery('#drag-drop-area')
                            .bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
                            .bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

                    }else{
                        uploaddiv.removeClass('drag-drop');
                        jQuery('#drag-drop-area').unbind('.wp-uploader');
                    }
                });
                uploader_ex.init();
                // a file was added in the queue

                function wpvivid_calc_file_size(size)
                {
                    var num = 1024.00;

                    if(size < num)
                        return size + 'B';
                    if(size < Math.pow(num, 2))
                        return (size / num).toFixed(2) + 'K';
                    if(size < Math.pow(num, 3))
                        return (size / Math.pow(num, 2)).toFixed(2) + 'M';
                    if(size < Math.pow(num, 4))
                        return (size / Math.pow(num, 3)).toFixed(2) + 'G';
                    return (size / Math.pow(num, 4)).toFixed(2) + 'T';
                }

                function wpvivid_check_plupload_added_files_ex(up,files)
                {
                    if(wpvivid_upload_id_ex==='')
                    {
                        var file=files[0];

                        var ajax_data = {
                            'action': 'wpvivid_addon_get_file_id_ex',
                            'file_name':file.name
                        };
                        wpvivid_post_request_addon(ajax_data, function (data)
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === "success")
                            {
                                wpvivid_upload_id_ex=jsonarray.id;
                                wpvivid_check_plupload_added_files_ex(up,files);
                            }
                            else if(jsonarray.result === "failed")
                            {
                                uploader_ex.removeFile(file);
                                alert(jsonarray.error);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown)
                        {
                            var error_message = wpvivid_output_ajaxerror('uploading backups', textStatus, errorThrown);
                            uploader_ex.removeFile(file);
                            alert(error_message);
                        });
                    }
                    else
                    {
                        jQuery('#wpvivid_upload_file_list_part').show();

                        var repeat_files = '';
                        plupload.each(files, function(file)
                        {
                            var brepeat=false;
                            var file_list = jQuery('#wpvivid_upload_file_list_ex').find('div').find('span:eq(1)');
                            file_list.each(function (index, value) {
                                if (value.innerHTML === file.name) {
                                    brepeat=true;
                                }
                            });
                            if(!brepeat) {
                                var wpvivid_file_regex = new RegExp(wpvivid_upload_id_ex + '_.*_.*\\.zip$');
                                if (wpvivid_file_regex.test(file.name)) {
                                    jQuery('#wpvivid_upload_file_list_ex').append(
                                        '<div id="' + file.id + '" style="border-bottom:1px solid #cccccc;">' +
                                        '<p>' +
                                        '<span class="dashicons dashicons-format-aside wpvivid-dashicons-orange"></span>' +
                                        '<span>' + file.name + '</span>' +
                                        '<span class="fileprogress" style="float:right;"><span>Size: </span><span>' + wpvivid_calc_file_size(file.size) + '</span></span>' +
                                        '</p>' +
                                        '</div>'
                                    );
                                    jQuery('#wpvivid_upload_submit_btn_ex').show();
                                    jQuery('#wpvivid_stop_upload_btn_ex').show();
                                    jQuery("#wpvivid_upload_submit_btn_ex").prop('disabled', false);
                                }
                                else {
                                    alert(file.name + " is not belong to the backup package uploaded.");
                                    uploader_ex.removeFile(file);
                                }
                            }
                            else{
                                if(repeat_files === ''){
                                    repeat_files += file.name;
                                }
                                else{
                                    repeat_files += ', ' + file.name;
                                }
                            }
                        });
                        if(repeat_files !== ''){
                            alert(repeat_files + " already exists in upload list.");
                            repeat_files = '';
                        }
                    }
                }

                uploader_ex.bind('FilesAdded', wpvivid_check_plupload_added_files_ex);

                uploader_ex.bind('Error', function(up, error)
                {
                    alert('Upload ' + error.file.name +' error, error code: ' + error.code + ', ' + error.message);
                    console.log(error);
                });

                uploader_ex.bind('FileUploaded', function(up, file, response)
                {
                    var jsonarray = jQuery.parseJSON(response.response);
                    if(jsonarray.result == 'failed'){
                        alert('upload ' + file.name + ' failed, ' + jsonarray.error);
                    }
                });

                uploader_ex.bind('UploadProgress', function(up, file)
                {
                    jQuery('#' + file.id + " .fileprogress").html(file.percent + "%");
                });

                uploader_ex.bind('UploadComplete',function(up, files)
                {
                    //jQuery('#wpvivid_upload_file_list_ex').html("");
                    jQuery('#wpvivid_upload_submit_btn_ex').hide();
                    jQuery('#wpvivid_stop_upload_btn_ex').hide();
                    //jQuery("#wpvivid_select_file_button_ex").prop('disabled', false);
                    var ajax_data = {
                        'action': 'wpvivid_addon_upload_files_finish_ex',
                        'files':JSON.stringify(files)
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if(jsonarray.result === 'success')
                            {
                                wpvivid_upload_complete_id = wpvivid_upload_id_ex;
                                wpvivid_upload_id_ex = '';
                                if(typeof jsonarray.incomplete_backup !== 'undefined' && jsonarray.incomplete_backup.length > 0)
                                {
                                    var incomplete_count = jsonarray.incomplete_backup.length;
                                    var incomplete_backup = JSON.stringify(jsonarray.incomplete_backup);
                                    wpvivid_delete_incomplete_backups_ex(incomplete_backup);
                                    alert('Failed to scan '+incomplete_count+' backup zips, the zips can be corrupted during creation or download process. Please check the zips.');
                                }
                                else
                                {
                                    alert('The upload has completed.');
                                }
                                jQuery( document ).trigger( 'wpvivid_update_local_backup');
                                jQuery('.wpvivid_upload_complete_notice').show();
                                jQuery('#wpvivid_restore_upload_backup_now').show();
                                //location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&localhost_backuplist'; ?>';
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
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('refreshing backup list', textStatus, errorThrown);
                        alert(error_message);
                    });
                    plupload.each(files, function(file)
                    {
                        if(typeof file === 'undefined')
                        {

                        }
                        else
                        {
                            uploader_ex.removeFile(file.id);
                        }
                    });
                });

                uploader_ex.bind('Destroy', function(up, file)
                {
                    var ajax_data = {
                        'action': 'wpvivid_cancel_upload_backup_addon_ex',
                        'upload_id': wpvivid_upload_id_ex
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        jQuery("#wpvivid_select_file_button_ex").prop('disabled', false);
                        jQuery('#wpvivid_upload_file_list_ex').html("");
                        jQuery('#wpvivid_upload_submit_btn_ex').hide();
                        jQuery('#wpvivid_stop_upload_btn_ex').hide();
                        wpvivid_init_upload_list_ex();
                        wpvivid_upload_id_ex='';
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('cancelling upload backups', textStatus, errorThrown);
                        alert(error_message);
                        jQuery('#wpvivid_upload_file_list_ex').html("");
                        jQuery('#wpvivid_upload_submit_btn_ex').hide();
                        jQuery('#wpvivid_stop_upload_btn_ex').hide();
                        wpvivid_init_upload_list_ex();
                        wpvivid_upload_id_ex='';
                    });
                });
            }

            jQuery(document).ready(function($)
            {
                // create the uploader and pass the config from above
                jQuery('#wpvivid_upload_submit_btn_ex').hide();
                jQuery('#wpvivid_stop_upload_btn_ex').hide();
                wpvivid_init_upload_list_ex();
            });

            function wpvivid_delete_incomplete_backups_ex(incomplete_backup)
            {
                var ajax_data = {
                    'action': 'wpvivid_addon_delete_upload_incomplete_backup_ex',
                    'incomplete_backup': incomplete_backup
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            function wpvivid_submit_upload_ex()
            {
                jQuery("#wpvivid_upload_submit_btn_ex").prop('disabled', true);
                jQuery("#wpvivid_select_file_button_ex").prop('disabled', true);
                uploader_ex.refresh();
                uploader_ex.start();
            }

            function wpvivid_stop_upload_ex()
            {
                uploader_ex.destroy();
            }

            function wpvivid_restore_upload_backup_now()
            {
                var ajax_data = {
                    'action': 'wpvivid_restore_upload_backup_ex',
                    'upload_backup_id': wpvivid_upload_complete_id
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === 'success')
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&&restore=1&backup_id='; ?>'+jsonarray.backup_id;
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
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('refreshing backup list', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }
}