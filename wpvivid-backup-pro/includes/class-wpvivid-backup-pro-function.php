<?php
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class WPvivid_backup_pro_function
{
    public function get_backup($files)
    {
        $backup_ids=array();
        foreach ($files as $file_data)
        {
            if(preg_match('/wpvivid-.*_.*_.*\.zip$/',$file_data['file_name']) || preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_.*\.zip$/',$file_data['file_name']))
            {
                if(preg_match('/wpvivid-(.*?)_/',$file_data['file_name'],$matches) || preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-(.*?)_/',$file_data['file_name'],$matches))
                {
                    $id= $matches[0];
                    $id=substr($id,0,strlen($id)-1);

                    $backup_ids[$id]['is_db']=true;
                    if(preg_match('/wpvivid-.*_.*_backup_db\.zip$/',$file_data['file_name'],$matches) ||
                        preg_match('/wpvivid-.*_.*_backup_additional_db\.zip$/',$file_data['file_name'],$matches))
                    {
                        //$backup_ids[$id]['is_db']=true;
                    }
                    else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_db\.zip$/',$file_data['file_name'],$matches) ||
                        preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_additional_db\.zip$/',$file_data['file_name'],$matches))
                    {
                        //$backup_ids[$id]['is_db']=true;
                    }
                    else
                    {
                        $backup_ids[$id]['is_db']=false;
                        $backup_ids[$id]['has_file']=true;
                    }

                    if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file_data['file_name'],$matches))
                    {
                        $backup_ids[$id]['date']=$matches[0];
                    }
                    else
                    {
                        $backup_ids[$id]['date']='unrecognized';
                    }

                    if(!isset($backup_ids[$id]['backup_prefix']))
                    {
                        if(preg_match('#^.*_wpvivid-#',$file_data['file_name'],$matches))
                        {
                            $prefix=$matches[0];
                            $prefix=substr($prefix,0,strlen($prefix)-strlen('_wpvivid-'));
                            $backup_ids[$id]['backup_prefix']=$prefix;
                        }
                        else if(preg_match('#^.*_'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-#',$file_data['file_name'],$matches)){
                            $prefix=$matches[0];
                            $prefix=substr($prefix,0,strlen($prefix)-strlen('_'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-'));
                            $backup_ids[$id]['backup_prefix']=$prefix;
                        }
                        else
                        {
                            $backup_ids[$id]['backup_prefix']='';
                        }
                    }

                    $backup_ids[$id]['files'][]=$file_data;
                }
            }
            else if(preg_match('/wpvivid-.*_.*_.*\.json$/',$file_data['file_name']) || preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_.*\.json$/',$file_data['file_name']))
            {
                if(preg_match('/wpvivid-(.*?)_/',$file_data['file_name'],$matches) || preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-(.*?)_/',$file_data['file_name'],$matches))
                {
                    $id= $matches[0];
                    $id=substr($id,0,strlen($id)-1);
                    $backup_ids[$id]['backup_info_file']=$file_data['file_name'];
                }
            }
        }

        foreach ($backup_ids as $id=>$backup_data)
        {
            if(!isset($backup_data['files'])||empty($backup_data['files']))
            {
                unset($backup_ids[$id]);
            }
            if(isset($backup_data['has_file']) && $backup_data['has_file'])
            {
                $backup_ids[$id]['is_db']=false;
                unset($backup_ids[$id]['has_file']);
            }
        }
        return $backup_ids;
    }

    public function rescan_remote_folder_set_backup($remote_id,$backups)
    {
        $remote_ids[]=$remote_id;
        $remote_options=WPvivid_Setting::get_remote_options($remote_ids);

        $remote_options_migrate=array();
        $remote_options_rollback=array();
        foreach ($remote_options as $option)
        {
            $og_path=$option['path'];
            if(isset($option['custom_path']))
            {
                $og_custom_path=$option['custom_path'];
            }
            else
            {
                $og_custom_path='';
            }

            if(isset($option['custom_path']))
            {
                $option['custom_path']='migrate';
                $remote_options_migrate[]=$option;
            }
            else
            {
                $option['path']='migrate';
                $remote_options_migrate[]=$option;
            }

            if(isset($option['custom_path']))
            {
                $option['custom_path']=$og_custom_path.'/rollback';
                $option['path']= $og_path;
                $remote_options_rollback[]=$option;
            }
            else
            {
                $option['path']= $og_path.'/rollback';
                $remote_options_rollback[]=$option;
            }
        }

        $list=array();
        if(!empty($backups['remote']))
        {
            foreach ($backups['remote'] as $id=>$backup)
            {
                $backup_data=array();
                $backup_data['type']='Common';
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_data['create_time']=strtotime($time);
                $backup_data['manual_delete']=0;
                $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                $backup_data['compress']['compress_type']='zip';
                $backup_data['save_local']=0;
                $backup_data['backup_prefix']=$backup['backup_prefix'];

                global $wpvivid_backup_pro;
                if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                {

                }
                else
                {
                    $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                }

                $temp['result']='success';
                $temp['files']=array();
                foreach ($backup['files'] as $file)
                {
                    $add_file=array();
                    $add_file['file_name']=$file['file_name'];
                    $add_file['size']=$file['size'];
                    if(isset($file['remote_path']))
                    {
                        $add_file['remote_path']=$file['remote_path'];
                    }
                    $temp['files'][]=$add_file;
                }
                $backup_data['backup']=$temp;
                $backup_data['remote']=$remote_options;
                $backup_data['lock']=0;
                $list[$id]=$backup_data;
            }
        }

        if(!empty($backups['migrate']))
        {
            foreach ($backups['migrate'] as $id=>$backup)
            {
                $backup_data=array();
                $backup_data['type']='Migrate';
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_data['create_time']=strtotime($time);
                $backup_data['manual_delete']=0;
                $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                $backup_data['compress']['compress_type']='zip';
                $backup_data['save_local']=0;
                $backup_data['backup_prefix']=$backup['backup_prefix'];

                global $wpvivid_backup_pro;
                if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                {

                }
                else
                {
                    $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                }

                $temp['result']='success';
                $temp['files']=array();
                foreach ($backup['files'] as $file)
                {
                    $add_file=array();
                    $add_file['file_name']=$file['file_name'];
                    $add_file['size']=$file['size'];
                    $temp['files'][]=$add_file;
                }

                $backup_data['backup']=$temp;
                $backup_data['remote']=$remote_options_migrate;
                $backup_data['lock']=0;
                $list[$id]=$backup_data;
            }
        }

        if(!empty($backups['rollback']))
        {
            foreach ($backups['rollback'] as $id=>$backup)
            {
                $backup_data=array();
                $backup_data['type']='Rollback';
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_data['create_time']=strtotime($time);
                $backup_data['manual_delete']=0;
                $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                $backup_data['compress']['compress_type']='zip';
                $backup_data['save_local']=0;
                $backup_data['backup_prefix']=$backup['backup_prefix'];

                global $wpvivid_backup_pro;
                if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                {

                }
                else
                {
                    $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                }



                $temp['result']='success';
                $temp['files']=array();
                foreach ($backup['files'] as $file)
                {
                    $add_file=array();
                    $add_file['file_name']=$file['file_name'];
                    $add_file['size']=$file['size'];
                    $temp['files'][]=$add_file;
                }

                $backup_data['backup']=$temp;
                $backup_data['remote']=$remote_options_rollback;
                $backup_data['lock']=0;
                $list[$id]=$backup_data;
            }
        }
        WPvivid_Setting::update_option('wpvivid_remote_list',$list);
    }

    public function rescan_remote_folder_set_migrate_backup($remote_id,$backups)
    {
        $remote_ids[]=$remote_id;
        $remote_options=WPvivid_Setting::get_remote_options($remote_ids);

        $remote_options_migrate=array();
        $remote_options_rollback=array();
        foreach ($remote_options as $option)
        {
            $og_path=$option['path'];
            if(isset($option['custom_path']))
            {
                $og_custom_path=$option['custom_path'];
            }
            else
            {
                $og_custom_path='';
            }

            if(isset($option['custom_path']))
            {
                $option['custom_path']='migrate';
                $remote_options_migrate[]=$option;
            }
            else
            {
                $option['path']='migrate';
                $remote_options_migrate[]=$option;
            }

            if(isset($option['custom_path']))
            {
                $option['custom_path']=$og_custom_path.'/rollback';
                $option['path']= $og_path;
                $remote_options_rollback[]=$option;
            }
            else
            {
                $option['path']= $og_path.'/rollback';
                $remote_options_rollback[]=$option;
            }
        }

        $list=array();

        if(!empty($backups['migrate']))
        {
            foreach ($backups['migrate'] as $id=>$backup)
            {
                $backup_data=array();
                $backup_data['type']='Migrate';
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_data['create_time']=strtotime($time);
                $backup_data['manual_delete']=0;
                $backup_data['local']['path']=WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                $backup_data['compress']['compress_type']='zip';
                $backup_data['save_local']=0;
                $backup_data['backup_prefix']=$backup['backup_prefix'];

                global $wpvivid_backup_pro;
                if(!file_exists($wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt'))
                {

                }
                else
                {
                    $backup_data['log']=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder().$id.'_backup'.'_log.txt';
                }

                $temp['result']='success';
                $temp['files']=array();
                foreach ($backup['files'] as $file)
                {
                    $add_file=array();
                    $add_file['file_name']=$file['file_name'];
                    $add_file['size']=$file['size'];
                    $temp['files'][]=$add_file;
                }

                $backup_data['backup']=$temp;
                $backup_data['remote']=$remote_options_migrate;
                $backup_data['lock']=0;
                $list[$id]=$backup_data;
            }
        }

        $remote_list = get_option('wpvivid_new_remote_list',array());
        $remote_list[$remote_id]=$list;
        update_option('wpvivid_new_remote_list',$remote_list,'no');
    }

    public function get_old_backup_files($backups,$backup_count,$db_count)
    {
        $backups_list=array();
        $db_list=array();
        $files=array();
        $backups_lock=WPvivid_Setting::get_option('wpvivid_remote_backups_lock');

        foreach ($backups as $k=>$backup)
        {
            if(isset($backups_lock[$k]))
            {
                continue;
            }

            if($backup['is_db']==true)
            {
                $db_list[$k]=$backup;
            }
            else
            {
                $backups_list[$k]=$backup;
            }
        }

        $size=sizeof($backups_list);
        while($size>$backup_count)
        {
            $oldest_id='';
            $oldest=0;

            foreach ($backups_list as $k=>$backup)
            {
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_time=strtotime($time);
                if($oldest==0)
                {
                    $oldest=$backup_time;
                    $oldest_id=$k;
                }
                else
                {
                    if($oldest>$backup_time)
                    {
                        $oldest=$backup_time;
                        $oldest_id=$k;
                    }
                }
            }

            if(!empty($oldest_id))
            {
                foreach ($backups_list[$oldest_id]['files'] as $file)
                {
                    $files[]=$file;
                }

                if(isset($backups_list[$oldest_id]['backup_info_file'])&&!empty($backups_list[$oldest_id]['backup_info_file']))
                {
                    $file['file_name']=$backups_list[$oldest_id]['backup_info_file'];
                    $files[]=$file;
                }

                unset($backups_list[$oldest_id]);
            }

            $new_size=sizeof($backups_list);
            if($new_size==$size)
            {
                break;
            }
            else
            {
                $size=$new_size;
            }
        }

        $size=sizeof($db_list);
        while($size>$db_count)
        {
            $oldest_id='';
            $oldest=0;

            foreach ($db_list as $k=>$backup)
            {
                $time_array=explode('-',$backup['date']);
                if(sizeof($time_array)>4)
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                else
                    $time=$backup['date'];
                $backup_time=strtotime($time);
                if($oldest==0)
                {
                    $oldest=$backup_time;
                    $oldest_id=$k;
                }
                else
                {
                    if($oldest>$backup_time)
                    {
                        $oldest=$backup_time;
                        $oldest_id=$k;
                    }
                }
            }

            if(!empty($oldest_id))
            {
                foreach ($db_list[$oldest_id]['files'] as $file)
                {
                    $files[]=$file;
                }

                if(isset($db_list[$oldest_id]['backup_info_file'])&&!empty($db_list[$oldest_id]['backup_info_file']))
                {
                    $file['file_name']=$db_list[$oldest_id]['backup_info_file'];
                    $files[]=$file;
                }

                unset($db_list[$oldest_id]);
            }

            $new_size=sizeof($db_list);
            if($new_size==$size)
            {
                break;
            }
            else
            {
                $size=$new_size;
            }
        }

        return $files;
    }

    public function get_old_backup_folders($path_array,$backup_count)
    {
        $need_delete_folder = array();
        if(isset($path_array) && !empty($path_array)){
            $new_path_array = array();
            foreach ($path_array as $path)
            {
                if (!preg_match('/.*_.*_.*_to_.*_.*_.*$/', $path)){
                    unset($path_array[$path]);
                    continue;
                }
                $og_path=$path;
                $path = preg_replace("/_to_.*_.*_.*/", "", $path);
                $path = preg_replace("/_/", "-", $path);
                $temp['og_path']=$og_path;
                $temp['path']=$path;
                $new_path_array[] = $temp;
            }

            $size=sizeof($new_path_array);
            while($size > $backup_count)
            {
                $oldest_time = 0;
                $index = 0;
                $oldest_path = '';
                foreach ($new_path_array as $key => $value)
                {
                    $tran_time = strtotime($value['path']);
                    if ($oldest_time === 0) {
                        $oldest_time = $tran_time;
                        $oldest_path = $value['og_path'];
                        $index = $key;
                    }
                    else{
                        if($oldest_time > $tran_time){
                            $oldest_path = $value['og_path'];
                            $index = $key;
                        }
                    }
                }
                if(!empty($oldest_path)){
                    $need_delete_folder[] = $oldest_path;
                    unset($new_path_array[$index]);
                }
                $new_size=sizeof($new_path_array);
                if($new_size==$size) {
                    break;
                }
                else {
                    $size=$new_size;
                }
            }
        }
        return $need_delete_folder;
    }

    public function get_old_backup_folders_ex($type,$path_array,$backup_count)
    {
        $need_delete_folder = array();
        if(isset($path_array) && !empty($path_array))
        {
            $new_path_array = array();
            foreach ($path_array as $path)
            {
                if (!preg_match('/.*_.*_.*_to_.*_.*_.*$/', $path)){
                    unset($path_array[$path]);
                    continue;
                }
                $og_path=$path;
                $path = preg_replace("/_to_.*_.*_.*/", "", $path);
                $path = preg_replace("/_/", "-", $path);
                $temp['og_path']=$og_path;
                $temp['path']=$path;
                $new_path_array[] = $temp;
            }

            $size=sizeof($new_path_array);
            while($size > $backup_count)
            {
                $oldest_time = 0;
                $index = 0;
                $oldest_path = '';
                foreach ($new_path_array as $key => $value)
                {
                    $tran_time = strtotime($value['path']);
                    if ($oldest_time === 0) {
                        $oldest_time = $tran_time;
                        $oldest_path = $value['og_path'];
                        $index = $key;
                    }
                    else{
                        if($oldest_time > $tran_time){
                            $oldest_path = $value['og_path'];
                            $index = $key;
                        }
                    }
                }
                if(!empty($oldest_path)){
                    $need_delete_folder[] = $oldest_path;
                    unset($new_path_array[$index]);
                }
                $new_size=sizeof($new_path_array);
                if($new_size==$size) {
                    break;
                }
                else {
                    $size=$new_size;
                }
            }
        }
        return $need_delete_folder;
    }



    public function swtich_domain_to_folder_name($domain){
        $parse = parse_url($domain);
        $path = '';
        if(isset($parse['path'])) {
            $parse['path'] = str_replace('/', '_', $parse['path']);
            $parse['path'] = str_replace('.', '_', $parse['path']);
            $path = $parse['path'];
        }
        $parse['host'] = str_replace('/', '_', $parse['host']);
        $parse['host'] = str_replace('.', '_', $parse['host']);
        return $parse['host'].$path;
    }

    public function flush($txt, $from_mainwp=false) {
        if(!$from_mainwp) {
            $ret['result'] = 'success';
            $ret['task_id'] = $txt;
            $txt = json_encode($ret);
            if(!headers_sent()){
                header('Content-Length: '.( ( ! empty( $txt ) ) ? strlen( $txt ) : '0' ));
                header('Connection: close');
                header('Content-Encoding: none');
            }
        }
        else{
            $ret['result']='success';
            $txt = '<mainwp>' . base64_encode( serialize( $ret ) ) . '</mainwp>';
            if(!headers_sent()){
                header('Content-Length: '.( ( ! empty( $txt ) ) ? strlen( $txt ) : '0' ));
                header('Connection: close');
                //header('Content-Encoding: none');
                //header('Content-Encoding: gzip, deflate');    //identity
            }
        }

        if (session_id())
            session_write_close();
        echo $txt;

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

    public static function is_wpvivid_backup($file_name)
    {
        if (preg_match('/wpvivid-.*_.*_.*\.zip$/', $file_name))
        {
           return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_.*\.zip$/', $file_name))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_.*\.zip$/', $file_name))
        {
            return true;
        }
        else {
            return false;
        }
    }

    public static function get_wpvivid_backup_id($file_name)
    {
        if (preg_match('/wpvivid-(.*?)_/', $file_name, $matches))
        {
            $id = $matches[0];
            $id = substr($id, 0, strlen($id) - 1);
            return $id;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-(.*?)_/', $file_name, $matches))
        {
            $id = $matches[0];
            $id = substr($id, 0, strlen($id) - 1);
            return $id;
        }
        else if(preg_match('/(?:^[^_]+_)?([^_]+)_\d{4}-\d{2}-\d{2}-\d{2}-\d{2}/', $file_name, $matches))
        {
            $id = $matches[1];
            return $id;
        }
        else {
            return false;
        }
    }

    public static function is_wpvivid_db_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_db.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_db.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_db.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_themes_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_themes.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_themes.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_themes.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_plugin_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_plugin.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_plugin.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_plugin.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_uploads_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_uploads.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_uploads.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_uploads.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_content_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_content.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_content.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_content.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_core_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_core.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_core.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_core.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_other_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_other.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_other.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/wpvivid-.*_.*backup_custom_other.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*backup_custom_other.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_other.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*backup_custom_other.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_additional_db_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_additional_db.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_additional_db.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_additional_db.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function is_wpvivid_all_backup($file_name)
    {
        if(preg_match('/wpvivid-.*_.*_backup_all.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if(preg_match('/'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-.*_.*_backup_all.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else if (preg_match('/.*-.*_.*_backup_all.*\.zip$/',$file_name,$matches))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}