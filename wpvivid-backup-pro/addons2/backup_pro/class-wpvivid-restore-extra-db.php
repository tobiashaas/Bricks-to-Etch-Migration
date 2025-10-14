<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Admin_load: yes
 * Version: 2.2.37
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Restore_Extra_DB_Addon
{
    private $host;
    private $username;
    private $password;
    private $database;
    private $charset;

    private $support_engines;
    private $support_charsets;
    private $support_collates;

    private $default_engines;
    private $default_charsets;
    private $default_collates;

    private $wpdb;

    public $log;
    public $sum;

    public function __construct($log=false)
    {
        $this->log=$log;
    }

    public function set_db_info($host = 'localhost', $username = 'root', $password = '', $database = 'test', $charset = 'utf8')
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->charset = $charset;
    }

    public function execute_extra_sql_file($sql_file_name,$local_path,$sub_task)
    {
        $this->wpdb=new wpdb($this->username, $this->password, $this->database, $this->host);
        $sql_file=$local_path.$sql_file_name;

        $this->sum=filesize($sql_file);

        $option=$sub_task['options'];

        $this->check_max_allow_packet();

        $this->support_engines = array();
        $this->support_charsets = array();
        $this->support_collates = array();

        $this->default_engines = array();
        $this->default_charsets = array();
        $this->default_collates = array();

        $this->default_engines = isset($option['default_engine']) ? $option['default_engine'] : 'MyISAM';
        $this->default_charsets = isset($option['default_charsets']) ? $option['default_charsets'] : DB_CHARSET;
        $this->default_collates = isset($option['default_collations']) ? $option['default_collations'] : DB_COLLATE;

        $result =  $this->wpdb->get_results("SHOW ENGINES", OBJECT_K);
        foreach ($result as $key => $value)
        {
            $this->support_engines[] = $key;
        }

        $result =  $this->wpdb->get_results("SHOW CHARACTER SET", OBJECT_K);
        foreach ($result as $key => $value)
        {
            $this->support_charsets[] = $key;
        }

        $result =  $this->wpdb->get_results("SHOW COLLATION", OBJECT_K);
        foreach ($result as $key => $value)
        {
            $this->support_collates[$key] = $value;
        }

        $this->wpdb->query('SET FOREIGN_KEY_CHECKS=0;');

        $sql_handle = fopen($sql_file, 'r');
        if ($sql_handle === false)
        {
            $ret['result'] = WPVIVID_FAILED;
            $ret['error'] = 'file not found. file name:' . $sql_file;
            return $ret;
        }

        fseek($sql_handle,$sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset']);

        $line_num = 0;
        $query = '';

        $restore_task=get_option('wpvivid_restore_task',array());
        $restore_detail_options=$restore_task['restore_detail_options'];
        $sql_file_buffer_pre_request=$restore_detail_options['sql_file_buffer_pre_request'];

        $max_buffer_size=$sql_file_buffer_pre_request*1024*1024;

        $current_offset=$sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset'];
        $progress_offset=$current_offset;

        $this->execute_sql('START TRANSACTION');

        while (!feof($sql_handle))
        {
            if(empty($query))
            {
                $sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset']=ftell($sql_handle);
                $sub_task['exec_sql']['last_action']='Importing';
                $sub_task['last_msg']='<span><strong>Importing sql file:</strong></span><span>'.size_format($sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset'],2).'/'.size_format($this->sum,2).'</span>';

                $read_offset=$sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset']-$current_offset;

                if($read_offset>$max_buffer_size)
                {
                    fclose($sql_handle);
                    $this->execute_sql('COMMIT');
                    $this->log->WriteLog('End read sql file offset:'.$sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset'],'notice');
                    $sub_task['exec_sql']['sql_files'][$sql_file_name]['finished']=0;
                    $ret['result']='success';
                    $ret['sub_task']=$sub_task;
                    return $ret;
                }
                else
                {
                    if($sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset']-$progress_offset>1024*100)
                    {
                        $progress_offset=$sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset'];
                        $this->update_sub_task($sub_task);
                    }
                }
            }

            $line = fgets($sql_handle);
            $line_num++;
            $startWith = substr(trim($line), 0, 2);
            $startWithEx = substr(trim($line), 0, 3);
            $endWith = substr(trim($line), -1, 1);
            $line = rtrim($line);

            if (empty($line) || $startWith == '--' || ($startWith == '/*' && $startWithEx != '/*!') || $startWith == '//')
            {
                continue;
            }

            $query = $query . $line;
            if ($endWith == ';')
            {
                if (preg_match('#^\\s*CREATE TABLE#', $query))
                {
                    $sub_task['exec_sql']['current_table']=$this->create_table($query);
                    $this->log->WriteLog('Create table:'.$sub_task['exec_sql']['current_table'],'notice');
                } else if (preg_match('#^\\s*LOCK TABLES#', $query))
                {
                    //$this->lock_table($query);
                } else if (preg_match('#^\\s*INSERT INTO#', $query))
                {
                    $this->insert($query);
                } else if (preg_match('#^\\s*DROP TABLE #', $query))
                {
                    $this->drop_table($query);
                } else if (preg_match('#\/*!#', $query))
                {
                    if ($this->execute_sql($query) === false)
                    {
                        $query = '';
                        continue;
                    }
                } else {
                    if ($this->execute_sql($query) === false)
                    {
                        $query = '';
                        continue;
                    }
                }
                $query = '';
            }
        }

        $this->execute_sql('COMMIT');

        $sub_task['exec_sql']['sql_files'][$sql_file_name]['sql_offset']=ftell($sql_handle);
        $sub_task['exec_sql']['sql_files'][$sql_file_name]['finished']=1;
        fclose($sql_handle);
        $this->log->WriteLog('Finish read sql file.','notice');
        $sub_task['exec_sql']['last_action']='Importing';
        $this->update_sub_task($sub_task);
        $ret['result']='success';
        $ret['sub_task']=$sub_task;

        $ret['result'] = WPVIVID_SUCCESS;
        return $ret;
    }

    public function check_max_allow_packet()
    {
        $restore_task=get_option('wpvivid_restore_task',array());
        $restore_detail_options=$restore_task['restore_detail_options'];
        $max_allowed_packet=$restore_detail_options['max_allowed_packet'];
        $set_max_allowed_packet=$max_allowed_packet*1024*1024;

        $max_allowed_packet =$this->wpdb->get_var("SELECT @@session.max_allowed_packet");

        if($max_allowed_packet!==null)
        {
            if($max_allowed_packet<$set_max_allowed_packet)
            {
                $query='set global max_allowed_packet='.$set_max_allowed_packet;
                $test=$this->wpdb->get_results($query);

                $this->wpdb->db_connect();
            }
        }
    }

    private function create_table($query)
    {
        $table_name='';
        if (preg_match('/^\s*CREATE TABLE +\`?([^\`]*)\`?/i', $query, $matches))
        {
            $table_name = $matches[1];
        }

        if (preg_match('/ENGINE=([^\s;]+)/', $query, $matches))
        {
            $engine = $matches[1];
            $replace_engine=true;
            foreach ($this->support_engines as $support_engine)
            {
                if(strtolower($engine)==strtolower($support_engine))
                {
                    $replace_engine=false;
                    break;
                }
            }

            if($replace_engine!==false)
            {
                if(!empty($this->default_engines))
                    $replace_engine=$this->default_engines[0];
            }

            if($replace_engine!==false)
            {
                $query=str_replace("ENGINE=$engine", "ENGINE=$replace_engine", $query);
            }
        }

        if (preg_match('/CHARSET ([^\s;]+)/', $query, $matches)||preg_match('/CHARSET=([^\s;]+)/', $query, $matches))
        {
            $charset = $matches[1];
            $replace_charset=true;
            foreach ($this->support_charsets as $support_charset)
            {
                if(strtolower($charset)==strtolower($support_charset))
                {
                    $replace_charset=false;
                    break;
                }
            }

            if($replace_charset)
            {
                $replace_charset=$this->default_charsets[0];
            }

            if($replace_charset!==false)
            {
                $query=str_replace("CHARSET=$charset", "CHARSET=$replace_charset", $query);
                $query=str_replace("CHARSET $charset", "CHARSET=$replace_charset", $query);
                $charset=$replace_charset;
            }

            $collate='';

            if (preg_match('/ COLLATE ([a-zA-Z0-9._-]+)/i', $query, $matches))
            {
                $collate = $matches[1];
            }
            else if(preg_match('/ COLLATE=([a-zA-Z0-9._-]+)/i', $query, $matches))
            {
                $collate = $matches[1];
            }

            if(!empty($collate))
            {
                $replace_collate=true;
                foreach ($this->support_collates as $key=>$support_collate)
                {
                    if(strtolower($charset)==strtolower($support_collate->Charset)&&strtolower($collate)==strtolower($key)) {
                        $replace_collate=false;
                        break;
                    }
                }

                if($replace_collate) {
                    $replace_collate=false;
                    foreach ($this->support_collates as $key=>$support_collate) {
                        if(strtolower($charset)==strtolower($support_collate->Charset)) {
                            if($support_collate->Default=='Yes') {
                                $replace_collate=$key;
                            }
                        }
                    }

                    if($replace_collate==false) {
                        foreach ($this->support_collates as $key=>$support_collate) {
                            if(strtolower($charset)==strtolower($support_collate->Charset)) {
                                $replace_collate=$key;
                                break;
                            }
                        }
                    }
                }

                if($replace_collate!==false)
                {
                    $query=str_replace("COLLATE $collate", "COLLATE $replace_collate", $query);
                    $query=str_replace("COLLATE=$collate", "COLLATE=$replace_collate", $query);
                }
            }
        }
        else
        {
            if (preg_match('/ COLLATE ([a-zA-Z0-9._-]+)/i', $query, $matches)) {
                $collate = $matches[1];
            }
            else if(preg_match('/ COLLATE=([a-zA-Z0-9._-]+)/i', $query, $matches)) {
                $collate = $matches[1];
            }

            if(!empty($collate)) {
                $replace_collate=true;
                foreach ($this->support_collates as $key=>$support_collate) {
                    if(strtolower($collate)==strtolower($key)) {
                        $replace_collate=false;
                        break;
                    }
                }

                if($replace_collate) {
                    $replace_collate=false;
                    foreach ($this->support_collates as $key=>$support_collate) {
                        if(strtolower($this->default_charsets[0])==strtolower($support_collate->Charset)) {
                            if($support_collate->Default=='Yes') {
                                $replace_collate=$key;
                            }
                        }
                    }

                    if($replace_collate==false) {
                        foreach ($this->support_collates as $key=>$support_collate) {
                            if(strtolower($this->default_charsets[0])==strtolower($support_collate->Charset)) {
                                $replace_collate=$key;
                                break;
                            }
                        }
                    }
                }

                if($replace_collate!==false)
                {
                    $query=str_replace("COLLATE $collate", "COLLATE $replace_collate", $query);
                    $query=str_replace("COLLATE=$collate", "COLLATE=$replace_collate", $query);
                }
            }
        }

        $this->execute_sql($query);
        return $table_name;
    }

    private function lock_table($query)
    {

        if (preg_match('/^\s*LOCK TABLES +\`?([^\`]*)\`?/i', $query, $matches))
        {
            $table_name = $matches[1];
        }
        $this->execute_sql($query);
    }

    private function insert($query)
    {
        $this->execute_sql($query);
    }

    private function drop_table($query)
    {
        if (preg_match('/^\s*DROP TABLE IF EXISTS +\`?([^\`]*)\`?\s*;/i', $query, $matches))
        {
            $table_name = $matches[1];
        }
        $this->execute_sql($query);
    }

    private function execute_sql($query)
    {
        if ($this->wpdb->get_results($query)===false)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function errorInfo()
    {
        global $wpvivid_additional_db;
        return $wpvivid_additional_db->last_error;
    }

    public function update_sub_task($sub_task=false)
    {
        $restore_task=get_option('wpvivid_restore_task',array());

        if($restore_task['do_sub_task']!==false)
        {
            $key=$restore_task['do_sub_task'];
            $restore_task['update_time']=time();
            if($sub_task!==false)
                $restore_task['sub_tasks'][$key]=$sub_task;
            update_option('wpvivid_restore_task',$restore_task,'no');
        }
    }
}