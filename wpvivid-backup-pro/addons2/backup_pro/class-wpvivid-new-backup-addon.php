<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_New_Backup_Page_addon
 */

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_New_Backup_Page_addon
{
    public $end_shutdown_function;
    public $current_task_id;
    public $task;
    public $backup_type_report = '';

    public function __construct()
    {
        //new backup
        add_action('wp_ajax_wpvivid_prepare_new_backup',array( $this,'prepare_new_backup'));
        add_action('wp_ajax_wpvivid_new_backup_now',array( $this,'backup_now'));
        add_action('wp_ajax_wpvivid_new_list_tasks_addon',array( $this,'list_tasks'), 11);
        add_action('wp_ajax_wpvivid_set_backup_history', array($this, 'set_backup_history'));
        add_action('wp_ajax_wpvivid_get_website_size_ex', array($this, 'get_website_size_ex'));
        add_action('wp_ajax_wpvivid_recalc_backup_size_ex', array($this, 'recalc_backup_size_ex'));
        add_action('wp_ajax_wpvivid_get_need_calc', array($this, 'get_need_calc'));
        add_action('wp_ajax_wpvivid_get_database_by_filter', array($this, 'get_database_by_filter'));

        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);
        add_filter('wpvivid_get_toolbar_menus',array($this,'get_toolbar_menus'),20);
        add_action('wpvivid_backup_pro_add_sidebar', array($this, 'add_sidebar'));
        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));
        //
        add_action('wpvivid_handle_new_backup_succeed',array($this,'handle_backup_succeed'),10);
        add_action('wpvivid_handle_new_backup_failed',array($this,'handle_backup_failed'),10);
        //
        add_action('wpvivid_task_monitor_event_ex',array( $this,'task_monitor'));
        add_action('wpvivid_new_backup_schedule_event',array( $this,'new_backup_schedule'));
        add_action('wpvivid_clean_backup_data_event',array($this,'clean_backup_data_event'));
        //
        add_action('init',array( $this,'plugin_loaded'));
        //
        add_filter('wpvivid_default_exclude_folders' ,array($this, 'default_exclude_folders'), 11);
        //
        add_action('wp_ajax_wpvivid_export_backup_to_site',array( $this,'export_backup_to_site'));
        //auto backup
        add_action('wp_ajax_wpvivid_start_new_auto_backup',array( $this,'auto_backup'));
        add_action('wp_ajax_wpvivid_start_new_auto_backup_now',array( $this,'auto_backup_now'));
        add_action('wp_ajax_wpvivid_auto_new_backup_list_tasks',array( $this,'auto_list_tasks'), 11);

        //wpvivid_set_remote_options_ex
        add_filter('wpvivid_set_remote_options_ex', array($this, 'set_remote_options'), 10, 2);
        //old backups
        add_filter('wpvivid_need_clean_oldest_backup_ex', array($this, 'need_clean_oldest_backup'), 20,2);
        //send mail
        add_action('wpvivid_do_mail_report',array($this, 'do_mail_report'));
        //
        add_filter('wpvivid_get_schedule_backup_data',array($this, 'get_schedule_backup_data'), 10, 2);
        //wpvivid_backup_cancel_ex
        add_action('wp_ajax_wpvivid_backup_cancel_ex',array( $this,'backup_cancel'));
        add_action('wp_ajax_wpvivid_shutdown_backup',array( $this,'shutdown_backup'));

        add_action('admin_notices', array($this, 'check_disk_free_space'));
        add_filter('wpvivid_check_backup_completeness', array($this, 'check_backup_completeness'), 9, 2);

        add_filter('wpvivid_get_auto_backup_menu', array($this, 'get_auto_backup_menu'), 10);

        add_filter('wpvivid_pre_new_backup_for_mainwp', array($this, 'pre_new_backup_for_mainwp'), 10);
        add_action('wpvivid_backup_now_for_mainwp', array($this, 'backup_now_for_mainwp'), 10);

        add_filter('wpvivid_export_setting_addon', array($this, 'export_setting_addon'), 11);
        add_filter('wpvivid_trim_import_info', array($this, 'trim_import_info'));

        add_action('wpvivid_check_admin_notices', array($this, 'check_admin_notices'));
    }

    public function check_admin_notices()
    {
        add_action('admin_notices', array($this, 'check_disk_free_space'));
    }

    public function get_auto_backup_menu($menu)
    {
        $menu['page_title']=apply_filters('wpvivid_white_label_display', 'WPvivid Plugin');
        $menu['menu_title']=apply_filters('wpvivid_white_label_display', 'WPvivid Plugin');

        $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-rollback");
        $menu['menu_slug'] = strtolower(sprintf('%s-backup', apply_filters('wpvivid_white_label_slug', 'wpvivid')));

        $menu['function']=array($this, 'init_page');
        $menu['icon_url']='dashicons-cloud';
        return $menu;
    }

    public function export_setting_addon($json)
    {
        $manual_exclude_list = get_option('wpvivid_manual_backup_history',array());
        $json['data']['wpvivid_manual_backup_history'] = $manual_exclude_list;
        $json['data']['wpvivid_site_abspath'] = ABSPATH;

        return $json;
    }

    public function trim_import_info($json)
    {
        if(isset($json['data']['wpvivid_site_abspath']) && !empty($json['data']['wpvivid_site_abspath']))
        {
            $old_site_abspath=$json['data']['wpvivid_site_abspath'];
            if(untrailingslashit($old_site_abspath) !== untrailingslashit(ABSPATH))
            {
                if(isset($json['data']['wpvivid_manual_backup_history']) && !empty($json['data']['wpvivid_manual_backup_history']))
                {
                    $manual_backup_history=$json['data']['wpvivid_manual_backup_history'];
                    if(!empty($manual_backup_history))
                    {
                        if(isset($manual_backup_history['exclude_files']) && !empty($manual_backup_history['exclude_files']))
                        {
                            $manual_backup_exclude_files = $manual_backup_history['exclude_files'];
                            $tmp_array=array();
                            foreach ($manual_backup_exclude_files as $index => $value)
                            {
                                $value['path']=str_replace(untrailingslashit($old_site_abspath), untrailingslashit(ABSPATH), $value['path']);
                                $tmp_array[]=$value;
                            }
                            $json['data']['wpvivid_manual_backup_history']['exclude_files']=$tmp_array;
                        }
                    }
                }
            }

            if(isset($json['data']['wpvivid_incremental_schedules']) && !empty($json['data']['wpvivid_incremental_schedules']))
            {
                $incremental_schedules=$json['data']['wpvivid_incremental_schedules'];
                foreach ($incremental_schedules as $incremental_id => $incremental_value)
                {
                    if(isset($incremental_value['backup_files']['exclude_files']))
                    {
                        $exclude_files=$incremental_value['backup_files']['exclude_files'];
                        $tmp_array=array();
                        foreach ($exclude_files as $index=>$value)
                        {
                            $value['path']=str_replace(untrailingslashit($old_site_abspath), untrailingslashit(ABSPATH), $value['path']);
                            $tmp_array[]=$value;
                        }
                        $json['data']['wpvivid_incremental_schedules'][$incremental_id]['backup_files']['exclude_files']=$tmp_array;
                    }
                }
            }
        }
        unset($json['data']['wpvivid_site_abspath']);

        if(isset($json['data']['wpvivid_incremental_schedules']) && !empty($json['data']['wpvivid_incremental_schedules']))
        {
            $incremental_schedules=$json['data']['wpvivid_incremental_schedules'];
            foreach ($incremental_schedules as $incremental_id => $incremental_value)
            {
                if(isset($incremental_value['backup']['backup_prefix']))
                {
                    global $wpvivid_backup_pro;
                    $backup_prefix = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
                    $json['data']['wpvivid_incremental_schedules'][$incremental_id]['backup']['backup_prefix']=$backup_prefix;
                }
            }
        }

        return $json;
    }

    public function check_disk_free_space()
    {
        $check_space = 1048576 * 35;
        $wpvivid_local_setting=get_option('wpvivid_local_setting', array());
        if(isset($wpvivid_local_setting['path']) && !empty($wpvivid_local_setting['path']))
        {
            $backup_dir = $wpvivid_local_setting['path'];
        }
        else
        {
            $backup_dir = 'wpvividbackups';
        }
        $disk_free_space = function_exists('disk_free_space') ? @disk_free_space(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_dir) : false;

        if ($disk_free_space != false)
        {
            if($check_space >= $disk_free_space)
            {
                echo '<div class="notice notice-warning">   
                            <p>Warning: We detected that you have less than 35MB of free disk space. This may cause the backup to fail, please free up or increase disk space.</p>
                       </div>';
            }
        }
    }

    public function check_backup_completeness($check_res, $task_id)
    {
        $check_res = true;
        $task=WPvivid_taskmanager::get_task($task_id);
        if(isset($task['setting']['is_merge']) && $task['setting']['is_merge'] == '1')
        {
            if(isset($task['jobs']))
            {
                foreach ($task['jobs'] as $job_info)
                {
                    if($job_info['backup_type'] === 'backup_merge')
                    {
                        if(isset($job_info['zip_file']) && !empty($job_info['zip_file']))
                        {
                            foreach ($job_info['zip_file'] as $zip_file_name => $zip_file_info)
                            {
                                if(!$this->check_backup_file_json($zip_file_name))
                                {
                                    $check_res = false;
                                }
                            }
                        }
                    }
                }
            }
        }
        else
        {
            if(isset($task['jobs']))
            {
                foreach ($task['jobs'] as $job_info)
                {
                    if(isset($job_info['zip_file']) && !empty($job_info['zip_file']))
                    {
                        foreach ($job_info['zip_file'] as $zip_file_name => $zip_file_info)
                        {
                            if(!$this->check_backup_file_json($zip_file_name))
                            {
                                $check_res = false;
                            }
                        }
                    }
                }
            }
        }
        return $check_res;
    }

    public function need_clean_oldest_backup($need,$backup_options)
    {
        if(isset($backup_options['remote'])&&$backup_options['remote'])
        {
            return false;
        }
        else if(isset($backup_options['remote_options']))
        {
            return false;
        }
        else
        {
            return $need;
        }
    }

    public function set_remote_options($remote_options, $options)
    {
        if($remote_options!==false&&isset($options['type']))
        {
            $remote_folder='';

            if($options['type']=='Manual')
            {
                return $remote_options;
            }
            else if($options['type']=='Migrate')
            {
                $remote_folder='migrate';
            }
            else if($options['type']=='Staging')
            {
                $remote_folder='staging';
            }
            else if($options['type']=='Rollback')
            {
                $remote_folder='rollback';
            }

            foreach ($remote_options as $key=>$remote_option)
            {
                if(!empty($remote_folder))
                {
                    if($options['type']=='Rollback')
                    {
                        if(isset($remote_options[$key]['custom_path']))
                        {
                            $remote_options[$key]['custom_path'] = untrailingslashit($remote_options[$key]['custom_path']).'/'.$remote_folder;
                        }
                        else
                        {
                            $remote_options[$key]['path'] = untrailingslashit($remote_options[$key]['path']).'/'.$remote_folder;
                        }
                    }
                    else if($options['type']=='Migrate'&&$remote_option['type']=='ftp2')
                    {
                        $remote_options[$key]['path'] = untrailingslashit($remote_options[$key]['path']).'/'.$remote_folder;
                    }
                    else {

                        if(isset($remote_options[$key]['custom_path']))
                        {
                            $remote_options[$key]['custom_path']=$remote_folder;
                        }
                        else
                        {
                            $remote_options[$key]['path'] =$remote_folder;
                        }
                    }
                }
            }
        }

        return $remote_options;
    }

    public function default_exclude_folders($folders)
    {
        $upload_dir = wp_upload_dir();
        $exclude_default = array();
        $exclude_default[0]['type'] = 'folder';
        $exclude_default[0]['path'] = $upload_dir['basedir'].'/'.'backwpup';    // BackWPup backup directory
        $exclude_default[1]['type'] = 'folder';
        $exclude_default[1]['path'] = $upload_dir['basedir'].'/'.'ShortpixelBackups';   //ShortpixelBackups
        $exclude_default[2]['type'] = 'folder';
        $exclude_default[2]['path'] = $upload_dir['basedir'].'/'.'backup';
        $exclude_default[3]['type'] = 'folder';
        $exclude_default[3]['path'] = $upload_dir['basedir'].'/'.'backwpup';    // BackWPup backup directory
        $exclude_default[4]['type'] = 'folder';
        $exclude_default[4]['path'] = $upload_dir['basedir'].'/'.'backup-guard';    // Wordpress Backup and Migrate Plugin backup directory
        $exclude_default[5]['type'] = 'folder';
        $exclude_default[5]['path'] = WP_CONTENT_DIR.'/'.'updraft';     // Updraft Plus backup directory
        $exclude_default[6]['type'] = 'folder';
        $exclude_default[6]['path'] = WP_CONTENT_DIR.'/'.'ai1wm-backups';   // All-in-one WP migration backup directory
        $exclude_default[7]['type'] = 'folder';
        $exclude_default[7]['path'] = WP_CONTENT_DIR.'/'.'backups';     // Xcloner backup directory
        $exclude_default[8]['type'] = 'folder';
        $exclude_default[8]['path'] = WP_CONTENT_DIR.'/'.'upgrade';
        $exclude_default[10]['type'] = 'folder';
        $exclude_default[10]['path'] = WP_CONTENT_DIR.'/'.'cache';
        $exclude_default[11]['type'] = 'folder';
        $exclude_default[11]['path'] = WP_CONTENT_DIR.'/'.'wphb-cache';
        $exclude_default[12]['type'] = 'folder';
        $exclude_default[12]['path'] = WP_CONTENT_DIR.'/'.'backup';
        $exclude_default[13]['type'] = 'folder';
        $exclude_default[13]['path'] = WP_CONTENT_DIR.'/'.'Dropbox_Backup';
        $exclude_default[14]['type'] = 'folder';
        $exclude_default[14]['path'] = WP_CONTENT_DIR.'/'.'mu-plugins';
        $exclude_default[15]['type'] = 'folder';
        $exclude_default[15]['path'] = WP_CONTENT_DIR.'/'.'backups-dup-pro';    // duplicator backup directory
        $exclude_default[16]['type'] = 'folder';
        $exclude_default[16]['path'] = WP_CONTENT_DIR.'/'.'backup-migration';
        $exclude_default[17]['type'] = 'folder';
        $exclude_default[17]['path'] = WP_CONTENT_DIR.'/'.'backups-dup-lite';
        $exclude_default[18]['type'] = 'folder';
        $exclude_default[18]['path'] = WP_PLUGIN_DIR.'/'.'wp-cerber';
        $exclude_default[19]['type'] = 'file';
        $exclude_default[19]['path'] = WP_CONTENT_DIR.'/'.'mysql.sql';  //mysql

        if(!empty($exclude_default))
        {
            foreach ($exclude_default as $index => $value)
            {
                $folders[$index]=$value;
            }
        }
        return $folders;
    }

    public function plugin_loaded()
    {
        $schedule_hooks=array();
        $schedule_hooks=apply_filters('init_wpvivid_schedule', $schedule_hooks);
        $this->init_schedule_hooks($schedule_hooks);
    }

    public function init_schedule_hooks($schedule_hooks)
    {
        foreach ($schedule_hooks as $key=>$schedule_hook)
        {
            add_action($schedule_hook, array($this, 'main_schedule'));
        }

        $schedule_db_hooks=array();
        $schedule_files_hooks=array();
        $schedules = get_option('wpvivid_incremental_schedules', array());
        foreach ($schedules as $schedule)
        {
            $schedule_db_hooks[$schedule['db_schedule_id']]=$schedule['db_schedule_id'];
            $schedule_files_hooks[$schedule['files_schedule_id']]=$schedule['files_schedule_id'];
        }

        foreach ($schedule_db_hooks as $key=>$schedule_hook)
        {
            add_action($schedule_hook, array($this, 'incremental_db_schedule'));
        }

        foreach ($schedule_files_hooks as $key=>$schedule_hook)
        {
            add_action($schedule_hook, array($this, 'incremental_files_schedule'));
        }
    }

    public function is_other_backup_task_running()
    {
        $tasks = get_option('wpvivid_task_list', array());
        foreach ($tasks as $task)
        {
            if ($task['status']['str']=='running'||$task['status']['str']=='no_responds'||$task['status']['str']=='wait_resume')
            {
                return true;
            }
        }
        return false;
    }

    public function has_backup_task_noreponse()
    {
        $tasks = get_option('wpvivid_task_list', array());
        foreach ($tasks as $task)
        {
            $current_time=time();
            $run_time=$task['status']['run_time'];
            $noreponse_time=$current_time-$run_time;
            if($noreponse_time >= 3600)
            {
                if ($task['status']['str']=='running'||$task['status']['str']=='no_responds'||$task['status']['str']=='wait_resume')
                {
                    unset($tasks[$task['id']]);
                }
            }
        }
        update_option('wpvivid_task_list', $tasks, 'no');
    }

    public function incremental_db_schedule($schedule_id='')
    {
        $this->has_backup_task_noreponse();
        if($this->is_other_backup_task_running())
        {
            die();
        }
        do_action('wpvivid_set_current_schedule_id', $schedule_id);

        $schedule_options=WPvivid_Schedule::get_schedule($schedule_id);
        if(empty($schedule_options))
        {
            die();
        }

        $backup=$schedule_options['backup_db'];
        $backup['remote'] = strval($schedule_options['backup']['remote']);

        if(isset($schedule_options['backup']['remote_id']))
        {
            $backup['remote_id']=$schedule_options['backup']['remote_id'];
        }
        else if(isset($schedule_options['backup']['remote_options']))
        {
            $backup['remote_options']=$schedule_options['backup']['remote_options'];
        }
        $backup['backup_prefix'] = isset($schedule_options['backup']['backup_prefix'])?$schedule_options['backup']['backup_prefix']:'';

        $backup['schedule_id']=$schedule_id;
        $backup['incremental_backup_db']=1;
        $backup['incremental_backup_files']='db';
        //$backup['incremental']=1;
        $backup = apply_filters('wpvivid_custom_backup_options_ex', $backup);
        $backup_options=$this->get_backup_data_from_schedule($backup);
        $backup_options['type']='Incremental';
        $ret = $this->pre_new_backup($backup_options);
        if ($ret['result'] == 'success')
        {
            $this->new_backup_schedule($ret['task_id']);
        }
        $this->end_shutdown_function=true;
        die();
    }

    public function incremental_files_schedule($schedule_id='')
    {
        $this->has_backup_task_noreponse();
        if($this->is_other_backup_task_running())
        {
            die();
        }
        do_action('wpvivid_set_current_schedule_id', $schedule_id);
        $schedule_options=WPvivid_Schedule::get_schedule($schedule_id);
        if(empty($schedule_options))
        {
            die();
        }
        $backup=$schedule_options['backup_files'];
        $backup['remote'] = strval($schedule_options['backup']['remote']);

        if(isset($schedule_options['backup']['remote_id']))
        {
            $backup['remote_id']=$schedule_options['backup']['remote_id'];
        }
        else if(isset($schedule_options['backup']['remote_options']))
        {
            $backup['remote_options']=$schedule_options['backup']['remote_options'];
        }
        $backup['backup_prefix'] = $schedule_options['backup']['backup_prefix'];

        $backup['incremental']=1;
        $backup['schedule_id']=$schedule_id;
        $backup['incremental_backup_files']='files';
        WPvivid_Incremental_Backup_addon::check_incremental_schedule('files',$schedule_id);
        $backup = apply_filters('wpvivid_custom_backup_options_ex', $backup);
        $backup=$this->get_backup_data_from_schedule($backup);
        $ret = $this->pre_new_backup($backup);
        if ($ret['result'] == 'success')
        {
            $this->new_backup_schedule($ret['task_id']);
        }
        $this->end_shutdown_function=true;
        die();
    }

    public function main_schedule($schedule_id='')
    {
        $this->has_backup_task_noreponse();
        if($this->is_other_backup_task_running())
        {
            die();
        }
        do_action('wpvivid_set_current_schedule_id', $schedule_id);
        $schedule_options=WPvivid_Schedule::get_schedule($schedule_id);
        if(empty($schedule_options))
        {
            die();
        }

        $remoteslist=WPvivid_Setting::get_all_remote_options();
        if(isset($schedule_options['backup']['remote_options']))
        {
            foreach ($schedule_options['backup']['remote_options'] as $remote_id => $remote_value)
            {
                if(isset($remote_value['type']) && $remote_value['type'] === 'onedrive')
                {
                    if(isset($remoteslist[$remote_id]))
                    {
                        $schedule_options['backup']['remote_options'][$remote_id] = $remoteslist[$remote_id];
                    }
                }
            }
        }

        $backup_options=$this->get_backup_data_from_schedule($schedule_options['backup']);
        $backup_options['type']='Cron';
        $ret = $this->pre_new_backup($backup_options);
        if ($ret['result'] == 'success')
        {
            $this->new_backup_schedule($ret['task_id']);
        }
        $this->end_shutdown_function=true;
        die();
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-backup';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-backup';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_manual_backup');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Manual Backup');
            $submenu['menu_title'] = 'Manual Backup';
            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-backup");

            $submenu['menu_slug'] = strtolower(sprintf('%s-backup', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 2;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        if (isset($toolbar_menus['wpvivid_admin_menu']) && isset($toolbar_menus['wpvivid_admin_menu']['child']))
        {
            if (isset($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_backup']))
            {
                //unset($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_backup']);
                $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_manual_backup');
                if($display)
                {
                    $admin_url = apply_filters('wpvivid_get_admin_url', '');
                    $menu = $toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_backup'];
                    $menu['title'] = 'Manual Backup';
                    $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-backup");
                    $menu['index'] = 2;
                    $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_slug', 'wpvivid').'-backup';
                    $toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_backup'] = $menu;
                }
            }
        }
        return $toolbar_menus;
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-backup';
        $cap['display']='Manual Backup';
        $cap['menu_slug']=strtolower(sprintf('%s-backup', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['icon']='<span class="dashicons dashicons-backup wpvivid-dashicons-grey"></span>';
        $cap['index']=4;
        $cap_list[$cap['slug']]=$cap;

        $cap['slug']='wpvivid-can-backup-remote';
        $cap['display']='Backup to cloud storage';
        $cap['menu_slug']=strtolower(sprintf('%s-backup-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['icon']='<strong>-----</strong>';
        $cap['index']=5;
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function add_sidebar()
    {
        if(apply_filters('wpvivid_show_sidebar',true))
        {
            $manual_backup_display        = apply_filters('wpvivid_get_menu_capability_addon', 'menu_manual_backup');
            $export_site_display          = apply_filters('wpvivid_get_menu_capability_addon', 'menu_export_site');
            $backup_restore_display       = apply_filters('wpvivid_get_menu_capability_addon', 'menu_backup_restore');
            $backup_schedule_display      = apply_filters('wpvivid_get_menu_capability_addon', 'menu_backup_schedule');
            $cloud_storage_display        = apply_filters('wpvivid_get_menu_capability_addon', 'menu_cloud_storage');
            $export_import_display        = apply_filters('wpvivid_get_menu_capability_addon', 'menu_export_import');
            $unused_image_cleaner_display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_unused_image_cleaner');
            ?>
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox  wpvivid-sidebar">
                        <h2 style="margin-top:0.5em;"><span class="dashicons dashicons-sticky wpvivid-dashicons-orange"></span>
                            <span><?php esc_attr_e(
                                    'Troubleshooting', 'WpAdminStyle'
                                ); ?></span></h2>
                        <div class="inside" style="padding-top:0;">
                            <ul class="" >
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-editor-help wpvivid-dashicons-orange" ></span>
                                    <a href="https://docs.wpvivid.com/troubleshooting"><b>Troubleshooting</b></a>
                                    <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-admin-generic wpvivid-dashicons-orange" ></span>
                                    <a href="https://docs.wpvivid.com/wpvivid-backup-pro-advanced-settings.html"><b>Adjust Advanced Settings </b></a>
                                    <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>

                            </ul>
                        </div>

                        <?php
                        if($manual_backup_display || $export_site_display || $backup_restore_display || $backup_schedule_display ||
                            $cloud_storage_display || $export_import_display || $unused_image_cleaner_display)
                        {
                            ?>
                            <h2><span class="dashicons dashicons-book-alt wpvivid-dashicons-orange" ></span>
                                <span><?php esc_attr_e(
                                        'Documentation', 'WpAdminStyle'
                                    ); ?></span></h2>
                            <div class="inside" style="padding-top:0;">
                                <ul class="">
                                    <?php
                                    if($manual_backup_display)
                                    {
                                        ?>
                                        <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-backup  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/manual-backup-overview.html"><b>Backup</b></a>
                                            <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-backup', 'wpvivid-backup')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        </li>
                                        <?php
                                    }

                                    if($export_site_display)
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-migrate  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/custom-migration-overview.html"><b>Auto-Migration</b></a>
                                            <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-export-site', 'wpvivid-export-site')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        </li>
                                        <?php
                                    }

                                    if($backup_restore_display)
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-editor-ul  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-backup-pro-backups-restore-overview.html"><b>Backup Manager</b></a>
                                            <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        </li>
                                        <?php
                                    }

                                    if($backup_schedule_display)
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-calendar-alt  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-backup-pro-schedule-overview.html"><b>Schedule</b></a>
                                            <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-schedule', 'wpvivid-schedule')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        </li>
                                        <?php
                                    }

                                    if($cloud_storage_display)
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-admin-site-alt3  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-backup-pro-cloud-storage-overview.html"><b>Cloud Storage</b></a>
                                            <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-remote', 'wpvivid-remote')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        </li>
                                        <?php
                                    }

                                    if($export_import_display)
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-randomize  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/export-content.html"><b>Export/Import</b></a>
                                            <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-export-import', 'wpvivid-export-import')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        </li>
                                        <?php
                                    }

                                    if($unused_image_cleaner_display)
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-code-standards  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/unused-images-cleaner.html"><b>Unused Image Cleaner</b></a>
                                            <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                            <?php
                        }
                        ?>

                        <?php
                        if(apply_filters('wpvivid_show_submit_ticket',true))
                        {
                            ?>
                            <h2>
                                <span class="dashicons dashicons-businesswoman wpvivid-dashicons-green"></span>
                                <span><?php esc_attr_e(
                                        'Support', 'WpAdminStyle'
                                    ); ?></span>
                            </h2>
                            <div class="inside">
                                <ul class="">
                                    <li><span class="dashicons dashicons-admin-comments wpvivid-dashicons-green"></span>
                                        <a href="https://wpvivid.com/submit-ticket"><b>Submit A Ticket</b></a>
                                        <br>
                                        The ticket system is for <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Pro users only. If you need any help with our plugin, submit a ticket and we will respond shortly.
                                    </li>
                                </ul>
                            </div>
                            <!-- .inside -->
                            <?php
                        }
                        ?>

                    </div>
                    <!-- .postbox -->

                </div>
                <!-- .meta-box-sortables -->

            </div>
            <?php
        }
    }

    public function init_page()
    {
        do_action('wpvivid_before_setup_page');

        if(isset($_REQUEST['auto_backup'])&&$_REQUEST['auto_backup']==1) {
            return;
        }
        $slug = apply_filters('wpvivid_access_white_label_slug', 'wpvivid_white_label');
        if(isset($_REQUEST[$slug])&&$_REQUEST[$slug]==1)
        {
            return;
        }

        ?>
        <div class="wrap wpvivid-canvas">
            <div class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Manual Backup', 'wpvivid' ); ?></h1>
            <div id="wpvivid_backup_notice"></div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <?php $this->welcome_bar();?>
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <!---  backup progress --->
                                    <?php
                                    $this->add_progress();
                                    $this->backup_content_selector();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    do_action( 'wpvivid_backup_pro_add_sidebar' );
                    ?>
                </div>
            </div>
        </div>
        <script>
            var m_need_update_addon=false;
            var wpvivid_prepare_backup=false;
            var running_backup_taskid='';
            var task_retry_times = 0;

            jQuery(document).ready(function ()
            {
                wpvivid_activate_cron_addon();
                wpvivid_manage_task_addon();
            });

            function wpvivid_activate_cron_addon(){
                var next_get_time = 3 * 60 * 1000;
                wpvivid_cron_task();
                setTimeout("wpvivid_activate_cron_addon()", next_get_time);
                setTimeout(function(){
                    m_need_update_addon=true;
                }, 10000);
            }

            function wpvivid_manage_task_addon()
            {
                if(m_need_update_addon === true)
                {
                    m_need_update_addon = false;
                    wpvivid_check_runningtask_addon();
                }
                else{
                    setTimeout(function()
                    {
                        wpvivid_manage_task_addon();
                    }, 3000);
                }
            }

            function wpvivid_check_runningtask_addon()
            {
                var ajax_data = {
                    'action': 'wpvivid_new_list_tasks_addon'
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    setTimeout(function ()
                    {
                        wpvivid_manage_task_addon();
                    }, 3000);
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        wpvivid_list_task_data(jsonarray);
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function ()
                    {
                        m_need_update_addon = true;
                        wpvivid_manage_task_addon();
                    }, 3000);
                }, 0);
            }

            function wpvivid_list_task_data(data)
            {
                var b_has_data = false;

                if(data.progress_html!==false)
                {
                    jQuery('#wpvivid_postbox_backup_percent').show();
                    jQuery('#wpvivid_postbox_backup_percent').html(data.progress_html);
                }
                else
                {
                    if(!wpvivid_prepare_backup)
                        jQuery('#wpvivid_postbox_backup_percent').hide();
                }

                var update_backup=false;
                if (data.success_notice_html !== false)
                {
                    jQuery('#wpvivid_backup_notice').show();
                    jQuery('#wpvivid_backup_notice').html(data.success_notice_html);
                    update_backup=true;

                    if(data.backup_finish_info !== false)
                    {
                        if(data.backup_finish_info.backup_finish_info === 'local')
                        {
                            jQuery('#wpvivid_local_backup_success_notice').show();

                            if(typeof data.backup_finish_info.local_backup_files !== 'undefined')
                            {
                                var is_set_all_download = false;
                                var local_site_html = '';
                                var local_site_all_download = '';
                                jQuery.each(data.backup_finish_info.local_backup_files, function(filename, fileinfo){
                                    local_site_html += '<div backup-id="'+data.task_id+'" file-name="'+filename+'" style="border-bottom:1px solid #cccccc;">' +
                                        '<p>' +
                                        '<span class="dashicons dashicons-format-aside wpvivid-dashicons-orange"></span>' +
                                        '<span>'+filename+'</span>' +
                                        '<span style="float:right;"><span class="dashicons dashicons-download wpvivid-dashicons-green"></span><span class="wpvivid-download-backup-file"><a href="#">Download</a></span></span>' +
                                        '</p>' +
                                        '<p style="padding-left:2em;"><span>Size: </span><span>'+fileinfo.size+'</span></p>' +
                                        '</div>';

                                    if(wpvivid_dl_method!=0)
                                    {
                                        if( !is_set_all_download )
                                        {
                                            is_set_all_download = true;
                                            local_site_all_download = '<div style="padding-top:1em;">' +
                                                '<p style="text-align:center;">' +
                                                '<span class="wpvivid-blue wpvivid-local-site-download-all" style="padding: 0.5em 2em;border-radius:8px; cursor:pointer;">Download All Parts</span>' +
                                                '<span> or </span><span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&localhost_allbackuplist'; ?>">View all backups</a></span>' +
                                                '</p>' +
                                                '</div>';

                                        }
                                    }

                                });
                                local_site_html += local_site_all_download;
                                jQuery('#wpvivid_local_backup_site_backup_list').html(local_site_html);
                            }
                        }
                        else if(data.backup_finish_info.backup_finish_info === 'remote')
                        {
                            jQuery('#wpvivid_remote_backup_success_notice').show();
                        }
                    }

                }
                if(data.error_notice_html !== false)
                {
                    jQuery('#wpvivid_backup_notice').show();
                    jQuery('#wpvivid_backup_notice').html(data.error_notice_html);
                    update_backup=true;
                }

                if(update_backup)
                {
                    jQuery( document ).trigger( 'wpvivid_update_local_backup');
                    jQuery( document ).trigger( 'wpvivid_update_log_list');
                }

                if(data.need_refresh_remote !== false){
                    jQuery( document ).trigger( 'wpvivid_update_remote_backup');
                }

                if(data.last_msg_html !== false)
                {
                    jQuery('#wpvivid_last_backup_msg').html(data.last_msg_html);
                }

                if(data.need_update)
                {
                    m_need_update_addon = true;
                }

                if(data.task_no_response)
                {
                    //jQuery('#wpvivid_current_doing').html('Task no response');
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                }

                if(data.running_backup_taskid!== '')
                {
                    b_has_data = true;
                    task_retry_times = 0;
                    running_backup_taskid = data.running_backup_taskid;
                    wpvivid_control_backup_lock();
                    if(data.wait_resume)
                    {
                        if (data.next_resume_time !== 'get next resume time failed.')
                        {
                            wpvivid_resume_backup(running_backup_taskid, data.next_resume_time);
                        }
                        else {
                            wpvivid_delete_backup_task(running_backup_taskid);
                        }
                    }
                }
                else
                {
                    if(!wpvivid_prepare_backup)
                    {
                        jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                        wpvivid_control_backup_unlock();
                    }
                    running_backup_taskid='';
                }
                if (!b_has_data)
                {
                    task_retry_times++;
                    if (task_retry_times < 5)
                    {
                        m_need_update_addon = true;
                    }
                }
            }

            jQuery('#wpvivid_set_manual_prefix').on("keyup", function(){
                var manual_prefix = jQuery('#wpvivid_set_manual_prefix').val();
                if(manual_prefix === ''){
                    manual_prefix = '*';
                    jQuery('#wpvivid_manual_prefix').html(manual_prefix);
                }
                else{
                    var reg = RegExp(/wpvivid/, 'i');
                    if (manual_prefix.match(reg)) {
                        jQuery('#wpvivid_set_manual_prefix').val('');
                        jQuery('#wpvivid_manual_prefix').html('*');
                        alert('You can not use word \'wpvivid\' to comment the backup.');
                    }
                    else{
                        jQuery('#wpvivid_manual_prefix').html(manual_prefix);
                    }
                }
            });

            function wpvivid_create_custom_setting_ex(custom_type){
                if(custom_type === 'manual_backup'){
                    var parent_id = 'wpvivid_custom_manual_backup';
                }
                var json = {};
                //exclude
                json['exclude_custom'] = '1';
                if(!jQuery('#'+parent_id).find('.wpvivid-custom-exclude-part').prop('checked')){
                    json['exclude_custom'] = '0';
                }

                //core
                json['core_check'] = '0';
                json['core_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                    json['core_check'] = '1';
                }

                //themes
                json['themes_check'] = '0';
                json['themes_list'] = {};
                json['themes_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                    json['themes_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['themes_list'][folder_name] = {};
                        json['themes_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['themes_extension'] = jQuery('#'+parent_id).find('.wpvivid-themes-extension').val();
                }

                //plugins
                json['plugins_check'] = '0';
                json['plugins_list'] = {};
                json['plugins_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                    json['plugins_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['plugins_list'][folder_name] = {};
                        json['plugins_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['plugins_extension'] = jQuery('#'+parent_id).find('.wpvivid-plugins-extension').val();
                }

                //content
                json['content_check'] = '0';
                json['content_list'] = {};
                json['content_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['content_list'][folder_name] = {};
                        json['content_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                }

                //uploads
                json['uploads_check'] = '0';
                json['uploads_list'] = {};
                json['upload_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['uploads_list'][folder_name] = {};
                        json['uploads_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                }

                //additional folders/files
                json['other_check'] = '0';
                json['other_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['other_check'] = '1';
                }
                if(json['other_check'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['other_list'][folder_name] = {};
                        json['other_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['other_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['other_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                }

                //database
                json['database_check'] = '0';
                json['database_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                    json['database_check'] = '1';
                }
                jQuery('input[name=manual_backup_database][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['database_list'].push(jQuery(value).val());
                    }
                });

                //additional database
                json['additional_database_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                    json['additional_database_check'] = '1';
                }

                return json;
            }

            /*function wpvivid_get_mu_site_setting(parent_id) {
                var json = {};
                json['mu_site_id']='';
                jQuery('#'+parent_id).find('input[name=mu_site][type=checkbox]').each(function(index, value)
                {
                    if(jQuery(value).prop('checked'))
                    {
                        json['mu_site_id']=jQuery(value).val();
                    }
                });
                return json;
            }*/

            function wpvivid_control_backup_lock(){
                jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
            }

            function wpvivid_control_backup_unlock(){
                jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
            }

            jQuery('#wpvivid_quickbackup_btn').on('click', function()
            {
                wpvivid_clear_notice('wpvivid_backup_notice');
                jQuery('#wpvivid_local_backup_success_notice').hide();
                jQuery('#wpvivid_remote_backup_success_notice').hide();
                var backup_data = wpvivid_ajax_data_transfer('backup');

                backup_data = JSON.parse(backup_data);
                var exclude_dirs = wpvivid_get_exclude_json('wpvivid_custom_manual_advanced_option');
                var custom_option = {
                    'exclude_files': exclude_dirs
                };
                jQuery.extend(backup_data, custom_option);

                var exclude_file_type = wpvivid_get_exclude_file_type('wpvivid_custom_manual_advanced_option');
                var exclude_file_type_option = {
                    'exclude_file_type': exclude_file_type
                };
                jQuery.extend(backup_data, exclude_file_type_option);
                backup_data = JSON.stringify(backup_data);

                jQuery('input:radio[option=backup]').each(function ()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var key = jQuery(this).prop('name');
                        var value = jQuery(this).prop('value');
                        if(value === 'custom')
                        {
                            backup_data = JSON.parse(backup_data);
                            var custom_dirs = wpvivid_get_custom_setting_json_ex('wpvivid_custom_manual_backup');
                            var custom_option = {
                                'custom_dirs': custom_dirs
                            };
                            jQuery.extend(backup_data, custom_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                        else if(value === 'mu')
                        {
                            backup_data = JSON.parse(backup_data);
                            var perent_id = 'wpvivid_custom_manual_backup_mu_single_site_list';
                            var mu_setting = wpvivid_get_mu_site_setting_ex(perent_id);
                            var custom_option = {
                                'mu_setting': mu_setting
                            };
                            jQuery.extend(backup_data, custom_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                    }
                });

                jQuery('input:radio[option=backup][name=backup_to]').each(function ()
                {
                    if (jQuery(this).prop('checked'))
                    {
                        if (this.value === 'remote')
                        {
                            backup_data = JSON.parse(backup_data);
                            var remote_id_select = jQuery('#wpvivid_manual_backup_remote_selector').val();
                            var local_remote_option = {
                                'remote_id_select': remote_id_select
                            };
                            jQuery.extend(backup_data, local_remote_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                    }
                });

                var ajax_data = {
                    'action': 'wpvivid_prepare_new_backup',
                    'backup': backup_data
                };

                wpvivid_control_backup_lock();
                jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_postbox_backup_percent').show();
                jQuery('#wpvivid_current_doing').html('Ready to backup. Progress: 0%, running time: 0 second.');
                var percent = '0%';
                jQuery('.wpvivid-span-processed-percent-progress').css('width', percent);
                jQuery('.wpvivid-span-processed-percent-progress').html(percent+' completed');
                jQuery('#wpvivid_backup_database_size').html('N/A');
                jQuery('#wpvivid_backup_file_size').html('N/A');
                jQuery('#wpvivid_current_doing').html('');
                wpvivid_prepare_backup = true;
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    wpvivid_prepare_backup = false;
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            wpvivid_set_backup_history(backup_data);
                            wpvivid_backup_now(jsonarray.task_id);
                        }
                        else
                        {
                            wpvivid_delete_ready_task(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        wpvivid_delete_ready_task(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_prepare_backup = false;
                    var error_message='Calculating the size of files, folder and database timed out. If you continue to receive this error, please go to the plugin settings, uncheck \'Calculate the size of files, folder and database before backing up\', save changes, then try again.';
                    wpvivid_delete_ready_task(error_message);
                });
            });

            function wpvivid_backup_now(task_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_new_backup_now',
                    'task_id': task_id
                };
                task_retry_times = 0;
                m_need_update_addon=true;
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            function wpvivid_delete_backup_task(task_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_delete_task',
                    'task_id': task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data){}, function(XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            function wpvivid_delete_ready_task(error)
            {
                var ajax_data={
                    'action': 'wpvivid_delete_ready_task'
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_add_notice('Backup', 'Error', error);
                            wpvivid_control_backup_unlock();
                            jQuery('#wpvivid_postbox_backup_percent').hide();
                        }
                    }
                    catch(err)
                    {
                        wpvivid_add_notice('Backup', 'Error', err);
                        wpvivid_control_backup_unlock();
                        jQuery('#wpvivid_postbox_backup_percent').hide();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function () {
                        wpvivid_delete_ready_task(error);
                    }, 3000);
                });
            }

            jQuery('#wpvivid_local_backup_site_backup_list').on("click", '.wpvivid-download-backup-file', function() {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('div').attr('backup-id');
                var file_name=Obj.closest('div').attr('file-name');
                location.href =ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_backup_ex&backup_id='+backup_id+'&file_name='+file_name;
            });

            jQuery('.wpvivid-hide-local-backup-site-notice').click(function()
            {
                jQuery('#wpvivid_local_backup_success_notice').hide();
            });

            jQuery('.wpvivid-hide-remote-backup-site-notice').click(function()
            {
                jQuery('#wpvivid_remote_backup_success_notice').hide();
            });
        </script>
        <?php
        $ret = $this->_list_tasks();
        ?>
        <script>
            var data = <?php echo json_encode($ret) ?>;
            wpvivid_list_task_data(data);
        </script>
        <?php
        $this->download_tools();
    }

    public function download_tools()
    {
        ?>
        <script>
            var wpvivid_download_list = Array();
            var wpvivid_downloading = false;
            var wpvivid_current_retry = 0;
            var wpvivid_max_retry = 3;
            var wpvivid_offset_size = 0;
            var wpvivid_chunk_size = 512*1024;
            var wpvivid_file_name;
            var wpvivid_file_size;
            var wpvivid_file_md5;
            var wpvivid_file_data;
            var wpvivid_dl_method = 0;
            var wpvivid_dl_blob_array = [];

            if(window.webkitRequestFileSystem)
            {
                window.requestFileSystem  = window.webkitRequestFileSystem;
                wpvivid_dl_method = 1;
            }
            else if ("download" in document.createElementNS("http://www.w3.org/1999/xhtml", "a"))
            {
                wpvivid_dl_method = 2;
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
                    alert('All files of the backup have been downloaded successfully.');
                    jQuery('.wpvivid-local-site-download-all').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_download_all_progress').remove();
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
                    jQuery('#wpvivid_download_all_progress').remove();
                    return false;
                }
            }

            function wpvivid_start_download()
            {
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
                jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').css('width', percent+'%');
                jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').html(percent+'%&nbspcompleted');
                jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-file-name').html('Downloading: '+wpvivid_file_name);
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
                            jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').css('width', percent+'%');
                            jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-processed-percent-progress').html(percent+'%&nbspcompleted');
                            jQuery('#wpvivid_download_all_progress').find('.wpvivid-span-download-file-name').html('Downloading: '+wpvivid_file_name);
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
                    fs.root.getFile('wpvividbackups/'+file_name, {}, function(fileEntry) {
                        fileEntry.file( function(file) {
                            var blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice;
                            var chunkSize = 2097152;
                            var chunks = Math.ceil(file.size / chunkSize);
                            var currentChunk = 0;

                            var spark = new SparkMD5.ArrayBuffer();
                            var fileReader = new FileReader();

                            fileReader.onload = function (e) {
                                spark.append(e.target.result);
                                currentChunk++;

                                if (currentChunk < chunks) {
                                    loadNext();
                                }
                                else {
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
                                    }
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

            jQuery('#wpvivid_local_backup_site_backup_list').on("click", '.wpvivid-local-site-download-all', function()
            {
                if(wpvivid_dl_method==0)
                {
                    alert("We have detected that your browser does not support bulk downloading of files, please download the backup files one by one.");
                    return;
                }
                var backup_id = jQuery('#wpvivid_local_backup_site_backup_list').find('div').attr('backup-id');
                var ajax_data = {
                    'action': 'wpvivid_get_need_download_files',
                    'backup_id': backup_id
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            var num = 0;
                            jQuery.each(jsonarray.files, function(index, value){
                                wpvivid_download_list[num] = new Array('file_name', 'file_size', 'file_md5');
                                wpvivid_download_list[num]['file_name'] = value.file_name;
                                wpvivid_download_list[num]['file_size'] = value.file_size;
                                wpvivid_download_list[num]['file_md5']  = value.file_md5;
                                console.log(value.file_md5);
                                num++;
                            });
                            jQuery('.wpvivid-local-site-download-all').css({'pointer-events': 'none', 'opacity': '0.4'});

                            jQuery('.wpvivid-local-site-download-all').closest('div').after('<div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_download_all_progress">' +
                                '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-download-processed-percent-progress" style="width: 0%">0%</span></span></p>' +
                                '<p>' +
                                '<span class="wpvivid-span-download-file-name">Preparing...</span>' +
                                '</p>' +
                                '</div>');

                            wpvivid_get_next_download();
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('get need download files', textStatus, errorThrown);
                    alert(error_message);
                });
            });
        </script>
        <?php
    }

    public function welcome_bar()
    {
        ?>
        <div class="wpvivid-welcome-bar wpvivid-clear-float">
            <div class="wpvivid-welcome-bar-left">
                <p><span class="dashicons dashicons-backup wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">Back Up Manually</span></p>
                <p><span class="about-description">The page allows you to manually create a backup of the website for restoration or migration.</span></p>
            </div>
            <div class="wpvivid-welcome-bar-right">
                <p></p>
                <div style="float:right;">
                    <span>Local Time:</span>
                    <span>
                        <a href="<?php esc_attr_e(apply_filters('wpvivid_get_admin_url', '').'options-general.php'); ?>">
                            <?php
                            $offset=get_option('gmt_offset');
                            echo date("l, F-d-Y H:i",time()+$offset*60*60);
                            ?>
                        </a>
                    </span>
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                        <div class="wpvivid-left">
                            <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                </div>
            </div>
            <div class="wpvivid-nav-bar wpvivid-clear-float">
                <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                <span>Local Storage Directory:</span>
                <span>
                    <code>
                        <?php
                        _e(WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath());
                        ?>
                    </code>
                </span>
                <span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>">rename directory</a></span>
                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                            <div class="wpvivid-bottom">
                                                <p>Click to change <?php _e(apply_filters('wpvivid_white_label_display', 'WPvivid Pro')); ?> custom backup folder.</p>
                                                <i></i> <!-- do not delete this line -->
                                            </div>
                                        </span>
                <span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore'); ?>">or view backups list</a></span>
                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                    <div class="wpvivid-bottom">
                        <!-- The content you need -->
                        <p>Click to browse and manage all your backups.</p>
                        <i></i> <!-- do not delete this line -->
                    </div>
            </div>
        </div>
        <?php
    }

    public function add_progress()
    {
        ?>
        <div class="wpvivid-one-coloum" id="wpvivid_postbox_backup_percent" style="display: none;">
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                <p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress">53% completed</span></span></p>
                <p>
                    <!--<span class="dashicons dashicons-list-view wpvivid-dashicons-blue wpvivid_estimate_backup_info"></span><span class="wpvivid_estimate_backup_info">Database Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_database_size">N/A</span>
                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange wpvivid_estimate_backup_info"></span><span class="wpvivid_estimate_backup_info">File Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_file_size">N/A</span>-->
                    <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Total Size:</span><span>N/A</span>
                    <span class="dashicons dashicons-upload wpvivid-dashicons-blue"></span><span>Uploaded:</span><span>N/A</span>
                    <span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span><span>Speed:</span><span>N/A</span>
                    <span class="dashicons dashicons-networking wpvivid-dashicons-green"></span><span>Network Connection:</span><span>OK</span>
                </p>
                <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>running</span></p>
                <div><input class="button-primary" id="wpvivid_backup_cancel_btn" type="submit" value="Cancel"></div>
            </div>
        </div>

        <script>
            jQuery('#wpvivid_postbox_backup_percent').on("click", "input", function()
            {
                if(jQuery(this).attr('id') === 'wpvivid_backup_cancel_btn')
                {
                    wpvivid_cancel_backup();
                }
            });

            function wpvivid_cancel_backup()
            {
                var ajax_data= {
                    'action': 'wpvivid_backup_cancel_ex'
                };
                jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.no_response)
                        {
                            var ret = confirm(jsonarray.msg);
                            if(ret === true)
                            {
                                wpvivid_termination_backup_task(jsonarray.task_id);
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_current_doing').html(jsonarray.msg);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('cancelling the backup', textStatus, errorThrown);
                    wpvivid_add_notice('Backup', 'Error', error_message);
                });
            }

            function wpvivid_termination_backup_task(task_id)
            {
                var ajax_data= {
                    'action': 'wpvivid_shutdown_backup',
                    'task_id': task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('terminationing the backup', textStatus, errorThrown);
                    wpvivid_add_notice('Backup', 'Error', error_message);
                });
            }
        </script>
        <?php
    }

    public function backup_finish_congralations_info()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_local_backup_success_notice" style="background:#eaf1fe;border-radius:8px;margin-bottom:1em;display: none;">
            <span style="cursor:pointer;"><span class="dashicons dashicons-dismiss wpvivid-dashicons-grey wpvivid-hide-local-backup-site-notice"></span></span>
            <h1 style="text-align:center; color:#8300e9; display: block;">Congratulations, exporting site succeeded</h1>
            <div style="border-bottom:1px solid #cccccc;">
                <p style="text-align:center;">
                    <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                    <span><strong>You can download all parts of the backup now, or download them later on <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&localhost_backuplist'; ?>">Backup Manager Page</a></strong></span>
                </p>
            </div>

            <div id="wpvivid_local_backup_site_backup_list">

            </div>
            <a id="wpvivid_a_link" style="display: none;"></a>
        </div>

        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_remote_backup_success_notice" style="background:#eaf1fe;border-radius:8px; margin-bottom:1em; display: none;">
            <h1 style="text-align:center; color:#8300e9; display: block;">Congratulations, exporting site succeeded</h1>

            <div style="">
                <p style="text-align:center;">
                    <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                    <span><strong>The backup has been sent to your remote storage, you can import it on another Wordpress site to complete migration, <a href="https://docs.wpvivid.com/wpvivid-backup-pro-migrate-site-manually.html">learn more...</a></strong></span></p>
                <p style="text-align:center;"><span><strong>You can download the backup on <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&remote_backuplist'; ?>">Backup Manager Page</a> anytime.</strong></span></p>
            </div>

            <div>
                <p style="text-align:center;"><span class="wpvivid-blue wpvivid-hide-remote-backup-site-notice" style="padding: 0.5em 2em;border-radius:8px; cursor:pointer;">I got it</span>
                </p>
            </div>
        </div>
        <?php
    }

    public function backup_content_selector()
    {
        ?>
        <div class="wpvivid-one-coloum">
            <?php $this->backup_finish_congralations_info(); ?>
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                <?php $this->backup_to();?>
                <div style="">
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                    <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <?php
                        if(!is_multisite())
                        {
                            $this->backup_type();
                        }
                        else
                        {
                            ?>
                            <div>
                                <div>
                                    <?php $this->backup_type(); ?>
                                </div>
                                <div id="wpvivid_custom_manual_backup_mu_single_site_list" style="display: none;">
                                    <p>Choose the childsite you want to backup</p>
                                    <p>
                                        <span style="padding-right:0.2em;">
                                            <input type="search" style="margin-bottom: 4px; width:300px;" class="wpvivid-mu-single-site-search-input" placeholder="Enter title, url or description" name="s" value="">
                                        </span>
                                        <span><input type="submit" class="button wpvivid-mu-single-search-submit" value="Search"></span>
                                    </p>
                                    <div class="wpvivid_mu_single_site_list"></div>
                                    <?php
                                    $type = 'manual_backup';
                                    do_action('wpvivid_select_mu_single_site', 'wpvivid_custom_manual_backup_mu_single_site_list', $type);
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div id="wpvivid_custom_manual_backup" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                    <?php
                    $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_manual_backup','manual_backup','1','0');
                    //$custom_backup_manager->output_custom_backup_table();
                    $custom_backup_manager->output_custom_backup_db_table();
                    $custom_backup_manager->output_custom_backup_file_table();
                    ?>
                    </div>
                </div>
                <!--<div id="wpvivid_custom_manual_backup_mu_single_site" style="display: none;">
                    <?php
                    //$type = 'manual_backup';
                    //do_action('wpvivid_custom_backup_setting', 'wpvivid_custom_manual_backup_mu_single_site_list', 'wpvivid_custom_manual_backup_mu_single_site', $type, '0');
                    ?>
                </div>-->

                <!--Advanced Option (Exclude)-->
                <div id="wpvivid_custom_manual_advanced_option">
                    <?php
                    $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_custom_manual_advanced_option');
                    $custom_backup_manager->output_advanced_option_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <?php
                        $general_setting=WPvivid_Setting::get_setting(true, "");
                        if(!isset($general_setting['options']['wpvivid_common_setting']['backup_prefix'])){
                            $home_url_prefix=get_home_url();
                            $parse = parse_url($home_url_prefix);
                            $path = '';
                            if(isset($parse['path'])) {
                                $parse['path'] = str_replace('/', '_', $parse['path']);
                                $parse['path'] = str_replace('.', '_', $parse['path']);
                                $path = $parse['path'];
                            }
                            $parse['host'] = str_replace('/', '_', $parse['host']);
                            $prefix = $parse['host'].$path;
                        }
                        else{
                            $prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                        }
                        ?>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="backup" name="backup_prefix" id="wpvivid_set_manual_prefix" value="<?php esc_attr_e($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php esc_attr_e($prefix); ?>">
                    </p>
                </div>
                <div style="border-top:1px solid #f1f1f1;padding-top:1em;">
                    <input class="button-primary" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" id="wpvivid_quickbackup_btn" type="submit" value="Backup Now">
                </div>
                <div style="text-align: left;">
                    <label class="wpvivid-checkbox">
                        <span>Marking this backup can only be deleted manually</span>
                        <input type="checkbox" id="wpvivid_backup_lock" option="backup" name="lock">
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                </div>
                <div style="clear:both;"></div>
            </div>
        </div>
        <?php
    }

    public function backup_to()
    {
        $remoteslist=WPvivid_Setting::get_all_remote_options();
        $has_remote = false;
        foreach ($remoteslist as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            else
            {
                if(in_array($key, $remoteslist['remote_selected']))
                {
                    $has_remote = true;
                }
            }
        }

        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup-remote'))
        {
            if(!$has_remote)
            {
                $default_backup_local = 'checked';
                $default_backup_remote = '';
                $default_remote_seletor = 'display: none;';
            }
            else
            {
                $general_setting=WPvivid_Setting::get_setting(true, "");
                if(isset($general_setting['options']['wpvivid_common_setting']['default_backup_local']))
                {
                    if($general_setting['options']['wpvivid_common_setting']['default_backup_local'])
                    {
                        $default_backup_local = 'checked';
                        $default_backup_remote = '';
                        $default_remote_seletor = 'display: none;';
                    }
                    else
                    {
                        $default_backup_local = '';
                        $default_backup_remote = 'checked';
                        $default_remote_seletor = '';
                    }
                }
                else
                {
                    $default_backup_local = 'checked';
                    $default_backup_remote = '';
                    $default_remote_seletor = 'display: none;';
                }
            }
        }
        else
        {
            $default_backup_local = 'checked';
            $default_backup_remote = '';
            $default_remote_seletor = 'display: none;';
        }

        ?>
        <div>
            <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
            <div style="padding-left:2em;">
                <label class="">
                    <input type="radio" option="backup" name="backup_to" value="local" <?php esc_attr_e($default_backup_local); ?> />Backup to localhost
                </label>
                <span style="padding:0 1em;"></span>
                <?php
                if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup-remote'))
                {
                    ?>
                    <label class="">
                        <input type="radio" option="backup" name="backup_to" value="remote" <?php esc_attr_e($default_backup_remote); ?> />Backup to remote storage
                    </label>
                    <span style="padding:0 0.2em;"></span>
                    <span id="wpvivid_manual_backup_remote_selector_part" style="<?php esc_attr_e($default_remote_seletor); ?>">
                        <select id="wpvivid_manual_backup_remote_selector">
                            <?php
                            $remoteslist=WPvivid_Setting::get_all_remote_options();
                            if(sizeof($remoteslist['remote_selected']) > 0)
                            {
                                $default_remote_count=0;
                                foreach ($remoteslist as $key=>$remote_option)
                                {
                                    if($key=='remote_selected')
                                    {
                                        continue;
                                    }

                                    if(in_array($key, $remoteslist['remote_selected']))
                                    {
                                        $default_remote_count++;
                                        if(!isset($remote_option['id']))
                                        {
                                            $remote_option['id'] = $key;
                                        }
                                        ?>
                                        <option value="<?php esc_attr_e($remote_option['id']); ?>" selected="selected"><?php echo $remote_option['name']; ?></option>
                                        <?php
                                    }
                                }
                                if($default_remote_count > 1)
                                {
                                    ?>
                                    <option value="all" selected="selected">All activated remote storage</option>
                                    <?php
                                }
                                else
                                {
                                    ?>
                                    <option value="all">All activated remote storage</option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </span>
                    <!--<span style="padding:0 1em;"></span>
                    <label class="">
                        <input type="radio" option="backup" name="backup_to" value="migrate_remote" />Migrate the site via remote storage
                    </label>-->
                    <?php
                }
                ?>
            </div>
        </div>
        <script>
            var has_remote = '<?php echo $has_remote; ?>';
            jQuery(document).on('wpvivid-has-default-remote', function(event)
            {
                wpvivid_check_has_default_remote(has_remote);
            });

            function wpvivid_check_has_default_remote(has_remote)
            {
                if(!has_remote)
                {
                    var descript = 'There is no default remote storage configured. Please set it up first.';
                    var ret = confirm(descript);
                    if(ret === true)
                    {
                        location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote'); ?>';
                    }
                    jQuery('input:radio[option=backup][name=backup_to][value=local]').prop('checked', true);
                }
                else
                {
                    jQuery('#wpvivid_manual_backup_remote_selector_part').show();
                }
            }

            jQuery('input:radio[option=backup][name=backup_to]').click(function(){
                var value = jQuery(this).val();
                if(value === 'remote'|| value === 'migrate_remote'){
                    jQuery( document ).trigger( 'wpvivid-has-default-remote');
                }
                else{
                    jQuery('#wpvivid_manual_backup_remote_selector_part').hide();
                }
            });
        </script>
        <?php
    }

    public function backup_type()
    {
        ?>
        <fieldset >
            <label style="padding-right:2em;">
                <input type="radio" option="backup" name="backup_files" value="files+db" checked="checked">
                <span>Wordpress Files + Database</span>
            </label>
            <label style="padding-right:2em;">
                <input type="radio" option="backup" name="backup_files" value="db">
                <span>Database</span>
            </label>
            <label style="padding-right:2em;">
                <input type="radio" option="backup" name="backup_files" value="files">
                <span>Wordpress Files</span>
            </label>
            <?php
            if(is_multisite())
            {
                ?>
                <label style="padding-right:2em;">
                    <input type="radio" option="backup" name="backup_files" value="mu">
                    <span> For the purpose of moving a subsite to a single install</span>
                </label>
                <?php
            }
            ?>
            <label style="padding-right:2em;">
                <input type="radio" option="backup" name="backup_files" value="custom">
                <span>Custom content</span>
            </label>
        </fieldset>
        <script>
            var wpvivid_manual_backup_table = wpvivid_manual_backup_table || {};
            wpvivid_manual_backup_table.init_refresh = false;
            jQuery('input:radio[option=backup][name=backup_files]').click(function(){
                if(this.value === 'custom'){
                    jQuery('#wpvivid_custom_manual_backup').show();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').hide();

                    var parent_id = 'wpvivid_custom_manual_backup';
                    var type = 'manual_backup';
                    if(!wpvivid_manual_backup_table.init_refresh)
                    {
                        wpvivid_manual_backup_table.init_refresh = true;
                        wpvivid_refresh_custom_backup_info(parent_id, type);
                        wpvivid_get_website_all_size();
                        jQuery('#'+parent_id).find('.wpvivid-database-loading').addClass('is-active');
                        jQuery('#'+parent_id).find('.wpvivid-themes-plugins-loading').addClass('is-active');
                    }
                }
                else if(this.value === 'mu'){
                    jQuery('#wpvivid_custom_manual_backup').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site').show();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').show();
                }
                else{
                    jQuery('#wpvivid_custom_manual_backup').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').hide();
                }
            });
        </script>
        <?php
    }

    //
    public function set_backup_history()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');

        try
        {
            if(isset($_POST['backup'])&&!empty($_POST['backup']))
            {
                $json = $_POST['backup'];
                $json = stripslashes($json);
                $backup_options = json_decode($json, true);
                if (is_null($backup_options))
                {
                    die();
                }

                $backup_history = array();
                $backup_history = get_option('wpvivid_manual_backup_history', $backup_history);
                if(isset($backup_options['exclude_files']))
                {
                    $backup_history['exclude_files'] = $backup_options['exclude_files'];
                }
                if(isset($backup_options['custom_dirs']))
                {
                    $backup_history['custom_dirs'] = $backup_options['custom_dirs'];
                }
                if(isset($backup_options['exclude_file_type']))
                {
                    $backup_history['exclude_file_type'] = $backup_options['exclude_file_type'];
                }
                WPvivid_Custom_Backup_Manager::wpvivid_set_new_backup_history($backup_history);

                $ret['result']='success';
                echo json_encode($ret);
                die();
            }
        }
        catch (Exception $error)
        {
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            echo json_encode($ret);
            die();
        }
    }

    public function get_website_size_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');

        try
        {
            if(isset($_POST['website_item'])&&!empty($_POST['website_item']))
            {
                $website_item = sanitize_key($_POST['website_item']);

                $ret['result']='success';

                $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                if(!empty($website_size))
                {
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                    }
                    else
                    {
                        $type = 'general';
                    }
                    if(isset($website_size[$type]['calctime']))
                    {
                        $calctime_bef=$website_size[$type]['calctime'];
                        $current_time=time();
                        if($current_time - $calctime_bef <= 12*60*60)
                        {
                            $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                            if(empty($website_size))
                                $website_size = array();

                            $database_size=isset($website_size[$type]['database_size'])?$website_size[$type]['database_size']:0;
                            $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                            $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                            $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                            $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                            $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                            $additional_size=isset($website_size[$type]['additional_size'])?$website_size[$type]['additional_size']:0;

                            $content_exclude_size=isset($website_size[$type]['content_exclude_size'])?$website_size[$type]['content_exclude_size']:0;
                            $themes_exclude_size=isset($website_size[$type]['themes_exclude_size'])?$website_size[$type]['themes_exclude_size']:0;
                            $plugins_exclude_size=isset($website_size[$type]['plugins_exclude_size'])?$website_size[$type]['plugins_exclude_size']:0;
                            $uploads_exclude_size=isset($website_size[$type]['uploads_exclude_size'])?$website_size[$type]['uploads_exclude_size']:0;

                            $ret['database_size'] = size_format($database_size, 2);
                            $ret['core_size'] = size_format($core_size, 2);
                            $ret['content_size'] = size_format($content_size, 2);
                            $ret['themes_size'] = size_format($themes_size, 2);
                            $ret['plugins_size'] = size_format($plugins_size, 2);
                            $ret['uploads_size'] = size_format($uploads_size, 2);
                            $ret['additional_size'] = size_format($additional_size, 2);
                            $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                            $ret['total_exclude_file_size'] = size_format($themes_exclude_size+$plugins_exclude_size+$uploads_exclude_size+$content_exclude_size, 2);
                            $ret['total_content_size'] = size_format($database_size+$core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                            echo json_encode($ret);
                            die();
                        }
                    }
                }

                if(empty($website_size))
                    $website_size = array();
                if($website_item === 'database')
                {
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $backup_history = WPvivid_custom_backup_selector::get_incremental_db_setting();
                    }
                    else
                    {
                        $type = 'general';
                        $backup_history = get_option('wpvivid_manual_backup_history');
                    }
                    if(empty($backup_history))
                    {
                        $is_select_db = true;
                        $exclude_table_list = array();
                    }
                    else
                    {
                        if($backup_history['custom_dirs']['database_check'] == '1')
                        {
                            $is_select_db = true;
                        }
                        else
                        {
                            $is_select_db = false;
                        }

                        if(!empty($backup_history['custom_dirs']['exclude-tables']))
                        {
                            $exclude_table_list = $backup_history['custom_dirs']['exclude-tables'];
                        }
                        else
                        {
                            $exclude_table_list = array();
                        }
                    }

                    $ret = $this->_get_custom_database_size($is_select_db, $exclude_table_list, false);
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['database_size'] = $ret['database_size'];
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['database_size'] = size_format($ret['database_size'], 2);
                }

                if($website_item === 'core')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            if($custom_incremental_file['core_option']['core_check'] == '1')
                            {
                                $is_select_core = true;
                            }
                            else
                            {
                                $is_select_core = false;
                            }
                        }
                        $backup_history = array();
                    }
                    else
                    {
                        $type = 'general';
                        $backup_history = get_option('wpvivid_manual_backup_history');

                        if(empty($backup_history))
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            if($backup_history['custom_dirs']['core_check'] == '1')
                            {
                                $is_select_core = true;
                            }
                            else
                            {
                                $is_select_core = false;
                            }
                        }
                    }
                    $core_folder_exclude_list = array('wp-admin', 'wp-includes', 'lotties');
                    $core_file_exclude_list = array('.htaccess', 'index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php',
                        'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
                    $core_exclude_list = array();
                    if($is_select_core)
                    {
                        $core_size = self::get_custom_path_size('core', $home_path, $core_folder_exclude_list, $core_file_exclude_list);
                    }
                    else
                    {
                        $core_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['core_size'] = $core_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['core_size'] = size_format($core_size, 2);
                }

                if($website_item === 'content')
                {
                    $content_dir = WP_CONTENT_DIR;
                    $path = str_replace('\\','/',$content_dir);
                    $content_path = $path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_content = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['content_option']['content_check'] == '1')
                            {
                                $is_select_content = true;
                            }
                            else
                            {
                                $is_select_content = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                        $backup_history = array();
                    }
                    else
                    {
                        $type = 'general';
                        $backup_history = get_option('wpvivid_manual_backup_history');

                        if(empty($backup_history))
                        {
                            $is_select_content = true;
                        }
                        else
                        {
                            if($backup_history['custom_dirs']['content_check'] == '1')
                            {
                                $is_select_content = true;
                            }
                            else
                            {
                                $is_select_content = false;
                            }
                        }
                    }


                    $local_setting = get_option('wpvivid_local_setting', array());
                    if(!empty($local_setting))
                    {
                        $content_folder_exclude_list = array('plugins', 'themes', 'uploads', 'wpvividbackups', $local_setting['path']);
                    }
                    else {
                        $content_folder_exclude_list = array('plugins', 'themes', 'uploads', 'wpvividbackups');
                    }
                    $content_file_exclude_list = array();
                    $this->get_exclude_list($backup_history, $website_item, $content_folder_exclude_list, $content_file_exclude_list);

                    //$content_exclude_size = self::_get_exclude_folder_file_size($content_path, $content_folder_exclude_list, $content_file_exclude_list);

                    if($is_select_content)
                    {
                        $content_size = self::get_custom_path_size('content', $content_path, $content_folder_exclude_list, $content_file_exclude_list);
                    }
                    else
                    {
                        $content_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['content_size'] = $content_size;
                    //$website_size[$type]['content_exclude_size'] = $content_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['content_size'] = size_format($content_size, 2);
                }

                if($website_item === 'uploads')
                {
                    $upload_dir = wp_upload_dir();
                    $path = $upload_dir['basedir'];
                    $path = str_replace('\\','/',$path);
                    $uploads_path = $path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_uploads = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['uploads_option']['uploads_check'] == '1')
                            {
                                $is_select_uploads = true;
                            }
                            else
                            {
                                $is_select_uploads = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                        $backup_history = array();
                    }
                    else
                    {
                        $type = 'general';
                        $backup_history = get_option('wpvivid_manual_backup_history');

                        if(empty($backup_history))
                        {
                            $is_select_uploads = true;
                        }
                        else
                        {
                            if($backup_history['custom_dirs']['uploads_check'] == '1')
                            {
                                $is_select_uploads = true;
                            }
                            else
                            {
                                $is_select_uploads = false;
                            }
                        }
                    }

                    $uploads_folder_exclude_list = array();
                    $uploads_file_exclude_list = array();
                    $this->get_exclude_list($backup_history, $website_item, $uploads_folder_exclude_list, $uploads_file_exclude_list);

                    //$uploads_exclude_size = self::_get_exclude_folder_file_size($uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);

                    if($is_select_uploads)
                    {
                        $uploads_size = self::get_custom_path_size('uploads', $uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);
                    }
                    else
                    {
                        $uploads_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['uploads_size'] = $uploads_size;
                    //$website_size[$type]['uploads_exclude_size'] = $uploads_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['uploads_size'] = size_format($uploads_size, 2);
                }

                if($website_item === 'themes')
                {
                    $themes_path = str_replace('\\','/', get_theme_root());
                    $themes_path = $themes_path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_themes = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['themes_option']['themes_check'] == '1')
                            {
                                $is_select_themes = true;
                            }
                            else
                            {
                                $is_select_themes = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                        $backup_history = array();
                    }
                    else
                    {
                        $type = 'general';
                        $backup_history = get_option('wpvivid_manual_backup_history');

                        if(empty($backup_history))
                        {
                            $is_select_themes = true;
                        }
                        else
                        {
                            if($backup_history['custom_dirs']['themes_check'] == '1')
                            {
                                $is_select_themes = true;
                            }
                            else
                            {
                                $is_select_themes = false;
                            }
                        }
                    }

                    $themes_folder_exclude_list = array();
                    $themes_file_exclude_list = array();
                    $this->get_exclude_list($backup_history, $website_item, $themes_folder_exclude_list, $themes_file_exclude_list);

                    //$themes_exclude_size = self::_get_exclude_folder_file_size($themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);

                    if($is_select_themes)
                    {
                        $themes_size = self::get_custom_path_size('themes', $themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);
                    }
                    else
                    {
                        $themes_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['themes_size'] = $themes_size;
                    //$website_size[$type]['themes_exclude_size'] = $themes_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['themes_size'] = size_format($themes_size, 2);
                }

                if($website_item === 'plugins')
                {
                    $plugins_path = str_replace('\\','/', WP_PLUGIN_DIR);
                    $plugins_path = $plugins_path.'/';

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_plugins = true;
                            $is_select_exclude_ex = true;
                        }
                        else
                        {
                            if($custom_incremental_file['plugins_option']['plugins_check'] == '1')
                            {
                                $is_select_plugins = true;
                            }
                            else
                            {
                                $is_select_plugins = false;
                            }

                            if($custom_incremental_file['exclude_custom'] == '1')
                            {
                                $is_select_exclude_ex = true;
                            }
                            else
                            {
                                $is_select_exclude_ex = false;
                            }
                        }
                        $backup_history = array();
                    }
                    else
                    {
                        $type = 'general';
                        $backup_history = get_option('wpvivid_manual_backup_history');

                        if(empty($backup_history))
                        {
                            $is_select_plugins = true;
                        }
                        else
                        {
                            if($backup_history['custom_dirs']['plugins_check'] == '1')
                            {
                                $is_select_plugins = true;
                            }
                            else
                            {
                                $is_select_plugins = false;
                            }
                        }
                    }

                    $plugins_folder_exclude_list = array();
                    $plugins_file_exclude_list = array();
                    $this->get_exclude_list($backup_history, $website_item, $plugins_folder_exclude_list, $plugins_file_exclude_list);

                    //$plugins_exclude_size = self::_get_exclude_folder_file_size($plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);

                    if($is_select_plugins)
                    {
                        $plugins_size = self::get_custom_path_size('plugins', $plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);
                    }
                    else
                    {
                        $plugins_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['plugins_size'] = $plugins_size;
                    //$website_size[$type]['plugins_exclude_size'] = $plugins_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['plugins_size'] = size_format($plugins_size, 2);
                }

                if($website_item === 'additional_folder')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());

                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        $custom_incremental_file = WPvivid_custom_backup_selector::get_incremental_file_settings();
                        if(empty($custom_incremental_file))
                        {
                            $is_select_additional = false;
                        }
                        else
                        {
                            if($custom_incremental_file['other_option']['other_check'] == '1')
                            {
                                $is_select_additional = true;
                            }
                            else
                            {
                                $is_select_additional = false;
                            }
                        }
                        $backup_history = array();
                    }
                    else
                    {
                        $type = 'general';
                        $backup_history = get_option('wpvivid_manual_backup_history');

                        if(empty($backup_history))
                        {
                            $is_select_additional = false;
                        }
                        else
                        {
                            if($backup_history['custom_dirs']['other_check'] == '1')
                            {
                                $is_select_additional = true;
                            }
                            else
                            {
                                $is_select_additional = false;
                            }
                        }
                    }

                    $additional_folder_include_list = array();
                    $additional_file_include_list = array();
                    $this->get_exclude_list($backup_history, $website_item, $additional_folder_include_list, $additional_file_include_list);
                    if($is_select_additional)
                    {
                        $additional_size = self::get_custom_path_size('additional', $home_path, $additional_folder_include_list, $additional_file_include_list);
                    }
                    else
                    {
                        $additional_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['additional_size'] = $additional_size;
                    $website_size[$type]['calctime'] = time();
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['additional_size'] = size_format($additional_size, 2);

                    $database_size=isset($website_size[$type]['database_size'])?$website_size[$type]['database_size']:0;
                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $additional_size=isset($website_size[$type]['additional_size'])?$website_size[$type]['additional_size']:0;

                    //$content_exclude_size=isset($website_size[$type]['content_exclude_size'])?$website_size[$type]['content_exclude_size']:0;
                    //$themes_exclude_size=isset($website_size[$type]['themes_exclude_size'])?$website_size[$type]['themes_exclude_size']:0;
                    //$plugins_exclude_size=isset($website_size[$type]['plugins_exclude_size'])?$website_size[$type]['plugins_exclude_size']:0;
                    //$uploads_exclude_size=isset($website_size[$type]['uploads_exclude_size'])?$website_size[$type]['uploads_exclude_size']:0;

                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                    //$ret['total_exclude_file_size'] = size_format($themes_exclude_size+$plugins_exclude_size+$uploads_exclude_size+$content_exclude_size, 2);
                    $ret['total_content_size'] = size_format($database_size+$core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                }

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

    public function get_need_calc()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try{
            if(isset($_POST['backup_data'])&&!empty($_POST['backup_data'])&&isset($_POST['calc_content'])&&!empty($_POST['calc_content']))
            {
                $json = $_POST['backup_data'];
                $json = stripslashes($json);
                $json = json_decode($json, true);

                $calc_content = $_POST['calc_content'];

                $ret['result'] = 'success';
                if($calc_content === 'database')
                {
                    if($json['custom_dirs']['database_check'] == '1')
                    {
                        $ret['database_calc'] = true;
                    }
                    else
                    {
                        $ret['database_calc'] = false;
                    }
                }
                else
                {
                    if($json['custom_dirs']['core_check'] == '1')
                    {
                        $ret['core_calc'] = true;
                    }
                    else
                    {
                        $ret['core_calc'] = false;
                    }

                    if($json['custom_dirs']['content_check'] == '1')
                    {
                        $ret['content_calc'] = true;
                    }
                    else
                    {
                        $ret['content_calc'] = false;
                    }

                    if($json['custom_dirs']['themes_check'] == '1')
                    {
                        $ret['themes_calc'] = true;
                    }
                    else
                    {
                        $ret['themes_calc'] = false;
                    }

                    if($json['custom_dirs']['plugins_check'] == '1')
                    {
                        $ret['plugins_calc'] = true;
                    }
                    else
                    {
                        $ret['plugins_calc'] = false;
                    }

                    if($json['custom_dirs']['uploads_check'] == '1')
                    {
                        $ret['uploads_calc'] = true;
                    }
                    else
                    {
                        $ret['uploads_calc'] = false;
                    }

                    if(!$ret['core_calc'] && !$ret['content_calc'] && !$ret['themes_calc'] && !$ret['plugins_calc'] && !$ret['uploads_calc'])
                    {
                        $ret['file_calc'] = false;
                    }
                    else
                    {
                        $ret['file_calc'] = true;
                    }
                }
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

    public function recalc_backup_size_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            if(isset($_POST['custom_option'])&&!empty($_POST['custom_option'])&&isset($_POST['website_item'])&&!empty($_POST['website_item']))
            {
                $json = $_POST['custom_option'];
                $json = stripslashes($json);
                $json = json_decode($json, true);

                $website_item = sanitize_key($_POST['website_item']);

                $ret['result']='success';

                if($website_item === 'database')
                {
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom_dirs']['database_check'] == '1')
                        {
                            $is_select_db = true;
                        }
                        else
                        {
                            $is_select_db = false;
                        }
                        $database_exclude_list = isset($json['custom_dirs']['exclude-tables']) ? $json['custom_dirs']['exclude-tables'] : array();
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['database_check'] == '1')
                        {
                            $is_select_db = true;
                        }
                        else
                        {
                            $is_select_db = false;
                        }
                        $database_exclude_list = isset($json['custom_dirs']['exclude-tables']) ? $json['custom_dirs']['exclude-tables'] : array();
                    }

                    $ret = $this->_get_custom_database_size($is_select_db, $database_exclude_list, true);
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['database_size'] = $ret['database_size'];
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['database_size'] = size_format($ret['database_size'], 2);

                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
                }

                if($website_item === 'core')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom_dirs']['core_check'] == '1')
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            $is_select_core = false;
                        }
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['core_check'] == '1')
                        {
                            $is_select_core = true;
                        }
                        else
                        {
                            $is_select_core = false;
                        }
                    }
                    $tmp_core_path = str_replace('\\','/', untrailingslashit($home_path).'/');
                    $core_folder_exclude_list = array($tmp_core_path.'wp-admin', $tmp_core_path.'wp-includes', $tmp_core_path.'lotties');
                    $core_file_exclude_list = array($tmp_core_path.'.htaccess', $tmp_core_path.'index.php', $tmp_core_path.'license.txt', $tmp_core_path.'readme.html', $tmp_core_path.'wp-activate.php',
                        $tmp_core_path.'wp-blog-header.php', $tmp_core_path.'wp-comments-post.php', $tmp_core_path.'wp-config.php', $tmp_core_path.'wp-config-sample.php',
                        $tmp_core_path.'wp-cron.php', $tmp_core_path.'wp-links-opml.php', $tmp_core_path.'wp-load.php', $tmp_core_path.'wp-login.php', $tmp_core_path.'wp-mail.php',
                        $tmp_core_path.'wp-settings.php', $tmp_core_path.'wp-signup.php', $tmp_core_path.'wp-trackback.php', $tmp_core_path.'xmlrpc.php');
                    $core_exclude_list = array();
                    if($is_select_core)
                    {
                        $core_size = self::get_custom_path_size('core', $home_path, $core_folder_exclude_list, $core_file_exclude_list);
                    }
                    else
                    {
                        $core_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['core_size'] = $core_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['core_size'] = size_format($core_size, 2);

                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
                }

                if($website_item === 'content')
                {
                    $content_dir = WP_CONTENT_DIR;
                    $path = str_replace('\\','/',$content_dir);
                    $content_path = $path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom_dirs']['content_check'] == '1')
                        {
                            $is_select_content = true;
                        }
                        else
                        {
                            $is_select_content = false;
                        }
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['content_check'] == '1')
                        {
                            $is_select_content = true;
                        }
                        else
                        {
                            $is_select_content = false;
                        }
                    }

                    $local_setting = get_option('wpvivid_local_setting', array());
                    if(!empty($local_setting))
                    {
                        $content_folder_exclude_list = array($content_path.'plugins', $content_path.'themes', $content_path.'uploads', $content_path.'wpvividbackups', $content_path.$local_setting['path'], $content_path.'wpvivid_image_optimization');
                    }
                    else {
                        $content_folder_exclude_list = array($content_path.'plugins', $content_path.'themes', $content_path.'uploads', $content_path.'wpvividbackups', $content_path.'wpvivid_image_optimization');
                    }
                    $content_file_exclude_list = array();

                    $this->get_exclude_list($json, $website_item, $content_folder_exclude_list, $content_file_exclude_list);

                    //$content_exclude_size = self::_get_exclude_folder_file_size($content_path, $content_folder_exclude_list, $content_file_exclude_list);

                    if($is_select_content)
                    {
                        $content_size = self::get_custom_path_size('content', $content_path, $content_folder_exclude_list, $content_file_exclude_list);
                    }
                    else
                    {
                        $content_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['content_size'] = $content_size;
                    //$website_size[$type]['content_exclude_size'] = $content_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['content_size'] = size_format($content_size, 2);

                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
                }

                if($website_item === 'themes')
                {
                    $themes_path = str_replace('\\','/', get_theme_root());
                    $themes_path = $themes_path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom_dirs']['themes_check'] == '1')
                        {
                            $is_select_themes = true;
                        }
                        else
                        {
                            $is_select_themes = false;
                        }
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['themes_check'] == '1')
                        {
                            $is_select_themes = true;
                        }
                        else
                        {
                            $is_select_themes = false;
                        }
                    }

                    $themes_folder_exclude_list = array();
                    $themes_file_exclude_list = array();
                    $this->get_exclude_list($json, $website_item, $themes_folder_exclude_list, $themes_file_exclude_list);

                    //$themes_exclude_size = self::_get_exclude_folder_file_size($themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);

                    if($is_select_themes)
                    {
                        $themes_size = self::get_custom_path_size('themes', $themes_path, $themes_folder_exclude_list, $themes_file_exclude_list);
                    }
                    else
                    {
                        $themes_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['themes_size'] = $themes_size;
                    //$website_size[$type]['themes_exclude_size'] = $themes_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['themes_size'] = size_format($themes_size, 2);

                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
                }

                if($website_item === 'plugins')
                {
                    $plugins_path = str_replace('\\','/', WP_PLUGIN_DIR);
                    $plugins_path = $plugins_path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom_dirs']['plugins_check'] == '1')
                        {
                            $is_select_plugins = true;
                        }
                        else
                        {
                            $is_select_plugins = false;
                        }
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['plugins_check'] == '1')
                        {
                            $is_select_plugins = true;
                        }
                        else
                        {
                            $is_select_plugins = false;
                        }
                    }

                    $plugins_folder_exclude_list = array();
                    $plugins_file_exclude_list = array();
                    $this->get_exclude_list($json, $website_item, $plugins_folder_exclude_list, $plugins_file_exclude_list);

                    //$plugins_exclude_size = self::_get_exclude_folder_file_size($plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);

                    if($is_select_plugins)
                    {
                        $plugins_size = self::get_custom_path_size('plugins', $plugins_path, $plugins_folder_exclude_list, $plugins_file_exclude_list);
                    }
                    else
                    {
                        $plugins_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['plugins_size'] = $plugins_size;
                    //$website_size[$type]['plugins_exclude_size'] = $plugins_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['plugins_size'] = size_format($plugins_size, 2);

                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
                }

                if($website_item === 'uploads')
                {
                    $upload_dir = wp_upload_dir();
                    $path = $upload_dir['basedir'];
                    $path = str_replace('\\','/',$path);
                    $uploads_path = $path.'/';
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom_dirs']['uploads_check'] == '1')
                        {
                            $is_select_uploads = true;
                        }
                        else
                        {
                            $is_select_uploads = false;
                        }
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['uploads_check'] == '1')
                        {
                            $is_select_uploads = true;
                        }
                        else
                        {
                            $is_select_uploads = false;
                        }
                    }

                    $uploads_folder_exclude_list = array();
                    $uploads_file_exclude_list = array();
                    $this->get_exclude_list($json, $website_item, $uploads_folder_exclude_list, $uploads_file_exclude_list);

                    //$uploads_exclude_size = self::_get_exclude_folder_file_size($uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);

                    if($is_select_uploads)
                    {
                        $uploads_size = self::get_custom_path_size('uploads', $uploads_path, $uploads_folder_exclude_list, $uploads_file_exclude_list);
                    }
                    else
                    {
                        $uploads_size = 0;
                    }
                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['uploads_size'] = $uploads_size;
                    //$website_size[$type]['uploads_exclude_size'] = $uploads_exclude_size;
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['uploads_size'] = size_format($uploads_size, 2);

                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
                }

                if($website_item === 'additional_folder')
                {
                    if(!function_exists('get_home_path'))
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    $home_path = str_replace('\\','/', get_home_path());
                    if(isset($_POST['incremental']))
                    {
                        $type = 'incremental';
                        if($json['custom_dirs']['other_check'] == '1')
                        {
                            $is_select_additional = true;
                        }
                        else
                        {
                            $is_select_additional = false;
                        }
                    }
                    else
                    {
                        $type = 'general';
                        if($json['custom_dirs']['other_check'] == '1')
                        {
                            $is_select_additional = true;
                        }
                        else
                        {
                            $is_select_additional = false;
                        }
                    }

                    $additional_folder_include_list = array();
                    $additional_file_include_list = array();
                    $this->get_exclude_list($json, $website_item, $additional_folder_include_list, $additional_file_include_list);

                    if($is_select_additional)
                    {
                        $additional_size = self::get_custom_path_size('additional', $home_path, $additional_folder_include_list, $additional_file_include_list);
                    }
                    else
                    {
                        $additional_size = 0;
                    }

                    $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
                    if(empty($website_size))
                        $website_size = array();
                    $website_size[$type]['additional_size'] = $additional_size;
                    $website_size[$type]['calctime'] = time();
                    update_option('wpvivid_custom_select_website_size_ex', $website_size, 'no');
                    $ret['additional_size'] = size_format($additional_size, 2);

                    //$database_size=isset($website_size[$type]['database_size'])?$website_size[$type]['database_size']:0;
                    $core_size=isset($website_size[$type]['core_size'])?$website_size[$type]['core_size']:0;
                    $content_size=isset($website_size[$type]['content_size'])?$website_size[$type]['content_size']:0;
                    $themes_size=isset($website_size[$type]['themes_size'])?$website_size[$type]['themes_size']:0;
                    $plugins_size=isset($website_size[$type]['plugins_size'])?$website_size[$type]['plugins_size']:0;
                    $uploads_size=isset($website_size[$type]['uploads_size'])?$website_size[$type]['uploads_size']:0;
                    $additional_size=isset($website_size[$type]['additional_size'])?$website_size[$type]['additional_size']:0;

                    //$content_exclude_size=isset($website_size[$type]['content_exclude_size'])?$website_size[$type]['content_exclude_size']:0;
                    //$themes_exclude_size=isset($website_size[$type]['themes_exclude_size'])?$website_size[$type]['themes_exclude_size']:0;
                    //$plugins_exclude_size=isset($website_size[$type]['plugins_exclude_size'])?$website_size[$type]['plugins_exclude_size']:0;
                    //$uploads_exclude_size=isset($website_size[$type]['uploads_exclude_size'])?$website_size[$type]['uploads_exclude_size']:0;

                    $ret['total_file_size'] = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                    //$ret['total_exclude_file_size'] = size_format($themes_exclude_size+$plugins_exclude_size+$uploads_exclude_size+$content_exclude_size, 2);
                    //$ret['total_content_size'] = size_format($database_size+$core_size+$themes_size+$plugins_size+$uploads_size+$content_size+$additional_size, 2);
                }

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

    public function get_database_by_filter()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try{
            if(isset($_POST['table_type'])&&isset($_POST['filter_text'])&& isset($_POST['option_type']))
            {
                $table_type  = sanitize_text_field($_POST['table_type']);
                $filter_text = sanitize_text_field($_POST['filter_text']);
                $option_type = sanitize_text_field($_POST['option_type']);

                global $wpdb;
                if (is_multisite() && !defined('MULTISITE')) {
                    $prefix = $wpdb->base_prefix;
                } else {
                    $prefix = $wpdb->get_blog_prefix(0);
                }
                $default_table = array($prefix.'commentmeta', $prefix.'comments', $prefix.'links', $prefix.'options', $prefix.'postmeta', $prefix.'posts', $prefix.'term_relationships',
                    $prefix.'term_taxonomy', $prefix.'termmeta', $prefix.'terms', $prefix.'usermeta', $prefix.'users');

                $tables = $wpdb->get_results('SHOW TABLE STATUS LIKE \'%'.$filter_text.'%\'', ARRAY_A);
                if (is_null($tables)) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
                    return $ret;
                }

                $tables_info = array();

                $ret['result'] = 'success';
                $html = '';

                if($option_type !== 'incremental_backup')
                {
                    $custom_setting = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
                }
                else
                {
                    $custom_setting = WPvivid_Custom_Backup_Manager::get_incremental_db_setting();
                }

                if (empty($custom_setting)) {
                    $custom_setting = array();
                }

                foreach ($tables as $row)
                {
                    $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
                    $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);

                    if($table_type === 'base_table')
                    {
                        if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1)
                        {
                        }
                        else
                        {
                            $checked = 'checked';
                            if (!empty($custom_setting['custom_dirs']['exclude-tables']))
                            {
                                if (in_array($row["Name"], $custom_setting['custom_dirs']['exclude-tables']))
                                {
                                    $checked = '';
                                }
                            }
                            if (in_array($row["Name"], $default_table))
                            {
                                $html .= '<div class="wpvivid-text-line">
                                                    <input type="checkbox" option="base_db" name="'.$option_type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                                    <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                                </div>';
                            }
                        }
                    }
                    else if($table_type === 'other_table')
                    {
                        if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1)
                        {
                        }
                        else
                        {
                            $checked = 'checked';
                            if (!empty($custom_setting['custom_dirs']['exclude-tables']))
                            {
                                if (in_array($row["Name"], $custom_setting['custom_dirs']['exclude-tables']))
                                {
                                    $checked = '';
                                }
                            }
                            if (!in_array($row["Name"], $default_table))
                            {
                                $html .= '<div class="wpvivid-text-line">
                                                    <input type="checkbox" option="other_db" name="'.$option_type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                                    <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                                 </div>';
                            }
                        }
                    }
                    else if($table_type === 'diff_prefix_table')
                    {
                        if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                            $checked = '';
                            if (!empty($custom_setting['custom_dirs']['include-tables']))
                            {
                                if (in_array($row["Name"], $custom_setting['custom_dirs']['include-tables']))
                                {
                                    $checked = 'checked';
                                }
                            }
                            $html .= '<div class="wpvivid-text-line">
                                                        <input type="checkbox" option="diff_prefix_db" name="'.$option_type.'_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                                        <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                                   </div>';
                        }
                    }
                }

                $ret['database_html'] = $html;
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

    public function get_exclude_list($data_json, $website_item, &$folder_list, &$file_list)
    {
        if(!empty($data_json))
        {
            if($website_item === 'additional_folder')
            {
                if(isset($data_json['custom_dirs']['other_list']) && !empty($data_json['custom_dirs']['other_list']))
                {
                    $folder_list = $data_json['custom_dirs']['other_list'];
                }
            }
            else
            {
                if(isset($data_json['exclude_files']) && !empty($data_json['exclude_files']))
                {
                    foreach ($data_json['exclude_files'] as $index => $value)
                    {
                        $exclude_path = $this->transfer_path($value['path']);

                        $content_dir = WP_CONTENT_DIR;
                        $path = str_replace('\\','/',$content_dir);
                        $content_path = $path.'/';

                        $upload_dir = wp_upload_dir();
                        $path = $upload_dir['basedir'];
                        $path = str_replace('\\','/',$path);
                        $uploads_path = $path.'/';

                        $themes_path = str_replace('\\','/', get_theme_root());
                        $themes_path = $themes_path.'/';

                        $plugins_path = str_replace('\\','/', WP_PLUGIN_DIR);
                        $plugins_path = $plugins_path.'/';

                        if($website_item === 'content')
                        {
                            if(preg_match('#'.$content_path.'#', $exclude_path) && !preg_match('#'.$uploads_path.'#', $exclude_path) && !preg_match('#'.$themes_path.'#', $exclude_path) && !preg_match('#'.$plugins_path.'#', $exclude_path))
                            {
                                if($value['type'] === 'folder')
                                {
                                    $folder_list[] = $exclude_path;
                                }
                                else if($value['type'] === 'file')
                                {
                                    $file_list[] = $exclude_path;
                                }
                            }
                        }
                        else if($website_item === 'uploads')
                        {
                            if(preg_match('#'.$uploads_path.'#', $exclude_path))
                            {
                                if($value['type'] === 'folder')
                                {
                                    $folder_list[] = $exclude_path;
                                }
                                else if($value['type'] === 'file')
                                {
                                    $file_list[] = $exclude_path;
                                }
                            }
                        }
                        else if($website_item === 'themes')
                        {
                            if(preg_match('#'.$themes_path.'#', $exclude_path))
                            {
                                if($value['type'] === 'folder')
                                {
                                    $folder_list[] = $exclude_path;
                                }
                                else if($value['type'] === 'file')
                                {
                                    $file_list[] = $exclude_path;
                                }
                            }
                        }
                        else if($website_item === 'plugins')
                        {
                            if(preg_match('#'.$plugins_path.'#', $exclude_path))
                            {
                                if($value['type'] === 'folder')
                                {
                                    $folder_list[] = $exclude_path;
                                }
                                else if($value['type'] === 'file')
                                {
                                    $file_list[] = $exclude_path;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function _get_exclude_folder_file_size($path, $folder_exclude_list, $file_exclude_list, $size = 0)
    {
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..") {
                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            if(in_array($filename, $folder_exclude_list))
                            {
                                $size+=self::_get_single_folder_size($path . DIRECTORY_SEPARATOR . $filename, 0);
                            }
                        }
                        else {
                            if(in_array($filename, $file_exclude_list))
                            {
                                $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }

    public static function _get_single_folder_size($path, $size = 0)
    {
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..")
                    {
                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            $size=self::_get_single_folder_size($path . DIRECTORY_SEPARATOR . $filename, $size);
                        }
                        else {
                            $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }

    public function _get_custom_database_size($is_select_db, $exclude_table_list, $recalc)
    {
        global $wpdb;
        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        if (is_null($tables)) {
            $ret['result'] = 'failed';
            $ret['database_size'] = 0;
            $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
            return $ret;
        }

        $db_size = 0;

        if($is_select_db)
        {
            $base_table_size = 0;

            foreach ($tables as $row) {
                if($recalc)
                {
                    if(!in_array($row['Name'], $exclude_table_list))
                    {
                        $base_table_size += ($row["Data_length"] + $row["Index_length"]);
                    }
                }
                else
                {
                    global $wpdb;
                    if (is_multisite() && !defined('MULTISITE')) {
                        $prefix = $wpdb->base_prefix;
                    } else {
                        $prefix = $wpdb->get_blog_prefix(0);
                    }
                    if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                        if (!empty($exclude_table_list))
                        {
                            if(!in_array($row['Name'], $exclude_table_list))
                            {
                                $base_table_size += ($row["Data_length"] + $row["Index_length"]);
                            }
                        }
                        else
                        {
                            $base_table_size += ($row["Data_length"] + $row["Index_length"]);
                        }
                    }
                    else
                    {
                        if(!in_array($row['Name'], $exclude_table_list))
                        {
                            $base_table_size += ($row["Data_length"] + $row["Index_length"]);
                        }
                    }
                }
            }
        }
        else
        {
            $base_table_size = 0;
        }


        $db_size = $base_table_size;

        $ret['database_size'] = $db_size;
        $ret['result']='success';

        return $ret;
    }

    public static function get_custom_path_size($type, $path, $folder_exclude_list, $file_exclude_list, $size=0){
        if(!function_exists('get_home_path'))
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        $home_path = str_replace('\\','/', get_home_path());
        $core_file_arr = array('.htaccess', 'index', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config.php', 'wp-config-sample.php',
            'wp-cron.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php');
        if(is_dir($path))
        {
            $handler = opendir($path);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..") {
                        if (is_dir($path . DIRECTORY_SEPARATOR . $filename))
                        {
                            if($type === 'core' && $home_path === $path || $type === 'additional')
                            {
                                if(in_array(str_replace('\\','/', untrailingslashit($path) . DIRECTORY_SEPARATOR . $filename), $folder_exclude_list))
                                {
                                    $size=self::get_custom_path_size($type, untrailingslashit($path) . DIRECTORY_SEPARATOR . $filename, $folder_exclude_list, $file_exclude_list, $size);
                                }
                            }
                            else
                            {
                                if(!in_array(str_replace('\\','/', untrailingslashit($path) . DIRECTORY_SEPARATOR . $filename), $folder_exclude_list))
                                {
                                    $size=self::get_custom_path_size($type, untrailingslashit($path) . DIRECTORY_SEPARATOR . $filename, $folder_exclude_list, $file_exclude_list, $size);
                                }
                            }
                        }
                        else {
                            if($type === 'core' || $type === 'additional')
                            {
                                if($home_path === $path){
                                    if(in_array(str_replace('\\','/', untrailingslashit($path) . DIRECTORY_SEPARATOR . $filename), $file_exclude_list)){
                                        $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                                    }
                                }
                                else{
                                    $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                                }
                            }
                            else
                            {
                                if(!in_array(str_replace('\\','/', untrailingslashit($path) . DIRECTORY_SEPARATOR . $filename), $file_exclude_list)){
                                    $size+=filesize($path . DIRECTORY_SEPARATOR . $filename);
                                }
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }

    public function prepare_new_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');

        try
        {
            if(isset($_POST['backup'])&&!empty($_POST['backup']))
            {
                $json = $_POST['backup'];
                $json = stripslashes($json);
                $backup_options = json_decode($json, true);
                if (is_null($backup_options))
                {
                    die();
                }

                if(isset($backup_options['backup_to']))
                {
                    if($backup_options['backup_to']=='remote')
                    {
                        $backup_options['remote']=1;
                        if(isset($backup_options['remote_id_select']))
                        {
                            if($backup_options['remote_id_select']=='all')
                            {

                            }
                            else
                            {
                                $remote_options_ids[]=$backup_options['remote_id_select'];
                                $backup_options['remote_options'] =WPvivid_Setting::get_remote_options($remote_options_ids);
                            }

                        }
                    }
                }

                if(isset($_POST['export']))
                {
                    $backup_options['export']=$_POST['export'];
                }

                if(isset($_POST['type']))
                {
                    $backup_options['type']=$_POST['type'];
                }
                else
                {
                    $backup_options['type']='Manual';
                }

                $ret=$this->pre_new_backup($backup_options);

                echo json_encode($ret);
                die();
            }
        }
        catch (Exception $error)
        {
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            $id=uniqid('wpvivid-');
            $log_file_name=$id.'_backup';
            $log=new WPvivid_Log_Ex_addon();
            $log->CreateLogFile($log_file_name,'no_folder','backup');
            $log->WriteLog($message,'notice');
            $log->CloseFile();
            WPvivid_error_log::create_error_log($log->log_file);
            error_log($message);
            echo json_encode($ret);
            die();
        }
    }

    public function get_backup_data_from_schedule($schedule_backup_options)
    {
        $backup_options=array();

        if(isset($schedule_backup_options['remote_id']))
        {
            $remote_id=$schedule_backup_options['remote_id'];
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $tmp_remote_option=array();
                $tmp_remote_option[$remote_id]=$remoteslist[$remote_id];
                $backup_options['remote_options']=$tmp_remote_option;
            }
        }
        else if(isset($schedule_backup_options['remote_options'])&&$schedule_backup_options['remote_options'])
        {
            $backup_options['remote_options']=$schedule_backup_options['remote_options'];
        }

        if(isset($schedule_backup_options['remote'])&&$schedule_backup_options['remote'])
        {
            $backup_options['remote']=1;
        }

        if(isset($schedule_backup_options['backup_files']))
        {
            $backup_options['backup_files']=$schedule_backup_options['backup_files'];
            if($schedule_backup_options['backup_files']=='custom')
            {
                $backup_options['custom_dirs']=$schedule_backup_options['custom_dirs'];
            }
        }
        else
        {
            if(isset($schedule_backup_options['backup_select']))//
            {
                $backup_options['backup_files']='custom';
                $custom_options=array();
                if(isset($schedule_backup_options['backup_select']['db'])&&$schedule_backup_options['backup_select']['db']==1)
                {
                    $custom_options['database_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['themes'])&&$schedule_backup_options['backup_select']['themes']==1)
                {
                    $custom_options['themes_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['plugin'])&&$schedule_backup_options['backup_select']['plugin']==1)
                {
                    $custom_options['plugins_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['uploads'])&&$schedule_backup_options['backup_select']['uploads']==1)
                {
                    $custom_options['uploads_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['content'])&&$schedule_backup_options['backup_select']['content']==1)
                {
                    $custom_options['content_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['core'])&&$schedule_backup_options['backup_select']['core']==1)
                {
                    $custom_options['core_check']=1;
                }

                if(isset($schedule_backup_options['backup_select']['other'])&&$schedule_backup_options['backup_select']['other']==1)
                {
                    $custom_options['other_check']=1;
                    if(isset($schedule_backup_options['custom_other_root']))
                    {
                        $custom_options['other_list']=$schedule_backup_options['custom_other_root'];
                    }
                }
                if(isset($schedule_backup_options['backup_select']['additional_db'])&&$schedule_backup_options['backup_select']['additional_db']==1)
                {
                    $custom_options['additional_database_check']=1;
                    if(isset($schedule_backup_options['additional_database_list']))
                    {
                        $backup_options['additional_database_list']=$schedule_backup_options['additional_database_list'];
                    }
                }


                $backup_options['custom_dirs']=$custom_options;
                if(isset($schedule_backup_options['additional_database_list']))
                {
                    $backup_options['custom_dirs']['additional_database_list']=$schedule_backup_options['additional_database_list'];
                }
                /*
                if(isset($options['backup_select']['mu_site'])&&$options['backup_select']['mu_site']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_mu_sites',$options);
                }
                */

            }
        }

        if(isset($schedule_backup_options['backup_prefix']))
        {
            $backup_options['backup_prefix']=$schedule_backup_options['backup_prefix'];
        }

        if(isset($schedule_backup_options['exclude_files']))
        {
            $backup_options['exclude_files']=$schedule_backup_options['exclude_files'];
        }
        else
        {
            $backup_options['exclude_files']=apply_filters('wpvivid_default_exclude_folders',array());
        }

        if(isset($schedule_backup_options['exclude_file_type']) && !empty($schedule_backup_options['exclude_file_type']))
        {
            $backup_options['exclude_file_type']=$schedule_backup_options['exclude_file_type'];
        }

        if(isset($schedule_backup_options['schedule_id']))
        {
            $backup_options['schedule_id']=$schedule_backup_options['schedule_id'];
        }

        if(isset($schedule_backup_options['incremental_backup_db']))
        {
            $backup_options['incremental_backup_db']=$schedule_backup_options['incremental_backup_db'];
        }

        if(isset($schedule_backup_options['incremental_backup_files']))
        {
            $backup_options['incremental_backup_files']=$schedule_backup_options['incremental_backup_files'];
        }

        if(isset($schedule_backup_options['incremental_options']))
        {
            $backup_options['incremental_options']=$schedule_backup_options['incremental_options'];
        }

        if(isset($schedule_backup_options['incremental']))
        {
            $backup_options['incremental']=$schedule_backup_options['incremental'];
        }

        return $backup_options;
    }

    public function pre_new_backup($backup_options)
    {
        if(apply_filters('wpvivid_need_clean_oldest_backup_ex',true,$backup_options))
        {
            $this->clean_oldest_backup($backup_options);
        }
        //do_action('wpvivid_clean_oldest_backup',$backup_options);

        if($this->is_tasks_backup_running())
        {
            $ret['result']='failed';
            $ret['error']=__('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid');
            return $ret;
        }

        if(isset($backup_options['backup_files']) && $backup_options['backup_files'] === 'custom')
        {
            if(isset($backup_options['custom_dirs']['additional_database_check']) &&
                $backup_options['custom_dirs']['additional_database_check'] &&
                empty($backup_options['custom_dirs']['additional_database_list']))
            {
                $ret['result']='failed';
                $ret['error']=__('Upon selecting the \'include additional database\' option, no extra database has been chosen. You must either specify an additional database or uncheck the option.', 'wpvivid');
                return $ret;
            }
        }

        $options=$this->get_backup_options($backup_options);
        $settings=$this->get_backup_settings($backup_options);
        $backup_content=$this->get_backup_content($backup_options);
        $backup=new WPvivid_New_Backup_Task();
        $ret=$backup->new_backup_task($options,$settings,$backup_content);
        return $ret;
    }

    public function clean_oldest_backup($backup_options)
    {
        $oldest_ids=array();
        if(isset($backup_options['incremental'])&&$backup_options['incremental'] == 1)
        {
            $backup_type = 'Incremental';
        }
        else if($backup_options['type'] === 'Manual')
        {
            $backup_type = 'Manual';
        }
        else if($backup_options['type'] === 'Cron')
        {
            $backup_type = 'Cron';
        }
        else if($backup_options['type'] === 'Rollback')
        {
            $backup_type = 'Rollback';
        }
        else if($backup_options['type'] === 'Incremental' || $backup_options['incremental'] == 1)
        {
            $backup_type = 'Incremental';
        }
        else
        {
            $backup_type = 'Manual';
        }

        if($backup_options['backup_files'] === 'db')
        {
            $backup_content = 'db';
        }
        else if($backup_options['backup_files'] === 'custom')
        {
            if(isset($backup_options['custom_dirs']['core_check']) && $backup_options['custom_dirs']['core_check'] == '1' ||
                isset($backup_options['custom_dirs']['themes_check']) && $backup_options['custom_dirs']['themes_check'] == '1' ||
                isset($backup_options['custom_dirs']['plugins_check']) && $backup_options['custom_dirs']['plugins_check'] == '1' ||
                isset($backup_options['custom_dirs']['content_check']) && $backup_options['custom_dirs']['content_check'] == '1' ||
                isset($backup_options['custom_dirs']['uploads_check']) && $backup_options['custom_dirs']['uploads_check'] == '1' ||
                isset($backup_options['custom_dirs']['other_check']) && $backup_options['custom_dirs']['other_check'] == '1')
            {
                $backup_content = 'file';
            }
            else
            {
                $backup_content = 'db';
            }
        }
        else
        {
            $backup_content = 'file';
        }
        $oldest_ids=apply_filters('wpvivid_get_oldest_backup_ids',$oldest_ids,false,$backup_type,$backup_content);
        if(!empty($oldest_ids))
        {
            foreach ($oldest_ids as $oldest_id)
            {
                $this->add_clean_backup_record_event($oldest_id);

                $backup_list=new WPvivid_New_BackupList();
                $backup_list->delete_backup($oldest_id);
            }
        }
    }

    private function add_clean_backup_record_event($backup_id)
    {
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        $tasks=WPvivid_Setting::get_option('clean_task');
        $tasks[$backup_id]=$backup;
        WPvivid_Setting::update_option('clean_task',$tasks);
        $resume_time=time()+60;

        $b=wp_schedule_single_event($resume_time,WPVIVID_CLEAN_BACKUP_RECORD_EVENT,array($backup_id));

        if($b===false)
        {
            $timestamp = wp_next_scheduled(WPVIVID_CLEAN_BACKUP_RECORD_EVENT,array($backup_id));

            if($timestamp!==false)
            {
                $resume_time=max($resume_time,$timestamp+10*60+10);

                $b=wp_schedule_single_event($resume_time,WPVIVID_CLEAN_BACKUP_RECORD_EVENT,array($backup_id));

                if($b===false)
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        return true;
    }

    public function get_backup_options($backup_options)
    {
        $options=$this->get_backup_default_options();
        if(isset($backup_options['remote_options']))
        {
            $options['remote_options'] =$backup_options['remote_options'];
        }
        else if(isset($backup_options['remote'])&&$backup_options['remote']==1)
        {
            $options['remote_options']=WPvivid_Setting::get_remote_options();
        }
        else
        {
            $options['remote_options']=false;
        }

        if(isset($backup_options['exclude_files']) && !empty($backup_options['exclude_files']))
        {
            $options['exclude_files']=$backup_options['exclude_files'];
        }

        if(isset($backup_options['exclude_file_type']) && !empty($backup_options['exclude_file_type']))
        {
            $exclude_ext = explode(',', $backup_options['exclude_file_type']);
            foreach ($exclude_ext as $ext)
            {
                $exclude_file['type']='ext';
                $exclude_file['path']=$ext;
                $options['exclude_files'][]=$exclude_file;
            }
        }
        //include_plugins
        if(isset($backup_options['include_plugins']) && !empty($backup_options['include_plugins']))
        {
            $options['include_plugins']=$backup_options['include_plugins'];
        }
        //include_themes
        if(isset($backup_options['include_themes']) && !empty($backup_options['include_themes']))
        {
            $options['include_themes']=$backup_options['include_themes'];
        }

        if(isset($backup_options['backup_prefix']) && !empty($backup_options['backup_prefix']))
        {
            $options['backup_prefix']=$backup_options['backup_prefix'];
        }

        if(isset($backup_options['custom_dirs']) && !empty($backup_options['custom_dirs']))
        {
            $custom_options=$backup_options['custom_dirs'];
        }
        else
        {
            $custom_options=array();
        }


        if(isset($custom_options['other_list']) && !empty($custom_options['other_list']))
        {
            $options['custom_other_root']=array();
            foreach ($custom_options['other_list'] as $path)
            {
                $options['custom_other_root'][]=$this -> transfer_path(ABSPATH.$path);
            }

        }

        if(isset($custom_options['additional_database_list']) && !empty($custom_options['additional_database_list']))
        {
            $options['additional_database_list']=$custom_options['additional_database_list'];
        }

        if(isset($custom_options['exclude-tables']) && !empty($custom_options['exclude-tables']))
        {
            $options['exclude-tables']=$custom_options['exclude-tables'];
        }

        if(isset($custom_options['include-tables']) && !empty($custom_options['include-tables']))
        {
            $options['include-tables']=$custom_options['include-tables'];
        }

        //$this->task['options']['site_id'] mu_setting
        if(isset($backup_options['mu_setting']) && isset($backup_options['mu_setting']['mu_site_id']))
        {
            $options['site_id']=$backup_options['mu_setting']['mu_site_id'];
        }
        $options['type']=isset($backup_options['type'])?$backup_options['type']:'Manual';

        if(isset($backup_options['export']))
        {
            $options['export']=$backup_options['export'];
        }

        if(isset($backup_options['incremental']))
        {
            $options['incremental']=$backup_options['incremental'];
        }

        if(isset($backup_options['incremental_backup_files']))
        {
            $options['incremental_backup_files']=$backup_options['incremental_backup_files'];
        }

        if(isset($backup_options['incremental_options']))
        {
            $options['incremental_options']=$backup_options['incremental_options'];
        }

        if(isset($backup_options['schedule_id']))
        {
            $options['schedule_id']=$backup_options['schedule_id'];
        }

        if(isset($backup_options['lock']))
        {
            $options['lock']=$backup_options['lock'];
        }

        return $options;
    }

    public function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode('/',$values);
    }

    public function get_backup_default_options()
    {
        $options=array();
        $common_setting=get_option('wpvivid_common_setting',array());
        if(isset($common_setting['domain_include'])&&$common_setting['domain_include'])
        {
            if (isset($common_setting['backup_prefix']))
            {
                $options['backup_prefix'] = $common_setting['backup_prefix'];
            }
            else {
                $home_url_prefix = get_home_url();
                $home_url_prefix = $this->parse_url_all($home_url_prefix);
                $options['backup_prefix'] = $home_url_prefix;
            }
        }
        else
        {
            $options['backup_prefix']='';
        }

        $options['encrypt_db']=isset($common_setting['encrypt_db'])?$common_setting['encrypt_db']:0;
        $options['encrypt_db_password']=isset($common_setting['encrypt_db_password'])?$common_setting['encrypt_db_password']:false;

        $local_setting=get_option('wpvivid_local_setting',array());
        //$options['save_local']=isset($local_setting['save_local'])?$local_setting['save_local']:false;
        $options['save_local']=isset($common_setting['retain_local'])?$common_setting['retain_local']:false;
        //
        return $options;
    }

    public function parse_url_all($url)
    {
        $parse = parse_url($url);
        //$path=str_replace('/','_',$parse['path']);
        $path = '';
        if(isset($parse['path'])) {
            $parse['path'] = str_replace('/', '_', $parse['path']);
            $path = $parse['path'];
        }
        return $parse['host'].$path;
    }

    public function get_backup_settings($backup_options)
    {
        return $this->get_backup_default_settings();
    }

    public function get_backup_default_settings()
    {
        $common_setting=get_option('wpvivid_common_setting',array());
        if(isset($common_setting['use_adaptive_settings'])&&$common_setting['use_adaptive_settings'])
        {
            $settings=$this->get_backup_adaptive_settings();
        }
        else
        {
            $settings['db_connect_method']=isset($common_setting['db_connect_method'])?$common_setting['db_connect_method']:'wpdb';
            $settings['memory_limit']=isset($common_setting['memory_limit'])?$common_setting['memory_limit']:'256M';
            $settings['max_execution_time']=isset($common_setting['max_execution_time'])?$common_setting['max_execution_time']:900;
            $settings['compress_file_use_cache']=isset($common_setting['compress_file_use_cache'])?$common_setting['compress_file_use_cache']:false;
            $settings['compress_file_count']=isset($common_setting['compress_file_count'])?$common_setting['compress_file_count']:500;
            $settings['max_backup_table']=isset($common_setting['max_backup_table'])?$common_setting['max_backup_table']:1000;
            $settings['max_file_size']=isset($common_setting['max_file_size'])?$common_setting['max_file_size']:200;
            $settings['max_sql_file_size']=isset($common_setting['max_sql_file_size'])?$common_setting['max_sql_file_size']:200;
            $settings['exclude_file_size']=isset($common_setting['exclude_file_size'])?$common_setting['exclude_file_size']:0;
            $settings['max_resume_count']=isset($common_setting['max_resume_count'])?$common_setting['max_resume_count']:6;
            $settings['is_merge']=isset($common_setting['ismerge'])?$common_setting['ismerge']:true;
            $settings['backup_symlink_folder']=isset($common_setting['backup_symlink_folder'])?$common_setting['backup_symlink_folder']:false;
            $settings['backup_database_use_primary_key']=isset($common_setting['backup_database_use_primary_key'])?$common_setting['backup_database_use_primary_key']:true;
            $settings['backup_upload_use_cm_store']=isset($common_setting['backup_upload_use_cm_store'])?$common_setting['backup_upload_use_cm_store']:true;

            if(isset($common_setting['zip_method']))
            {
                $settings['zip_method']=$common_setting['zip_method'];
            }
            else
            {
                if(class_exists('ZipArchive'))
                {
                    if(method_exists('ZipArchive', 'addFile'))
                    {
                        $settings['zip_method']='ziparchive';
                    }
                    else
                    {
                        $settings['zip_method']='pclzip';
                    }
                }
                else
                {
                    $settings['zip_method']='pclzip';
                }
            }
        }

        return $settings;
    }

    public function get_backup_adaptive_settings()
    {
        $options=get_option('wpvivid_common_setting',array());

        $options['db_connect_method']=isset($options['db_connect_method'])?$options['db_connect_method']:'wpdb';
        $options['memory_limit']=isset($options['memory_limit'])?$options['memory_limit']:'512M';
        $options['max_execution_time']=isset($options['max_execution_time'])?$options['max_execution_time']:300;
        $options['compress_file_use_cache']=isset($options['compress_file_use_cache'])?$options['compress_file_use_cache']:true;
        $options['compress_file_count']=isset($options['compress_file_count'])?$options['compress_file_count']:500;
        $options['max_backup_table']=isset($options['max_backup_table'])?$options['max_backup_table']:1000;
        $options['max_file_size']=isset($options['max_file_size'])?$options['max_file_size']:200;
        $options['max_sql_file_size']=isset($options['max_sql_file_size'])?$options['max_sql_file_size']:400;
        $options['is_merge']=isset($options['ismerge'])?$options['ismerge']:true;
        $options['exclude_file_size']=isset($options['exclude_file_size'])?$options['exclude_file_size']:0;
        $options['compress_level']=isset($options['compress_level'])?$options['compress_level']:false;
        $options['max_resume_count']=isset($options['max_resume_count'])?$options['max_resume_count']:9;
        $options['backup_symlink_folder']=isset($options['backup_symlink_folder'])?$options['backup_symlink_folder']:false;
        $options['backup_database_use_primary_key']=isset($options['backup_database_use_primary_key'])?$options['backup_database_use_primary_key']:true;
        $options['backup_upload_use_cm_store']=isset($options['backup_upload_use_cm_store'])?$options['backup_upload_use_cm_store']:true;

        if(isset($options['zip_method']))
        {
        }
        else
        {
            if(class_exists('ZipArchive'))
            {
                if(method_exists('ZipArchive', 'addFile'))
                {
                    $options['zip_method']='ziparchive';
                }
                else
                {
                    $options['zip_method']='pclzip';
                }
            }
            else
            {
                $options['zip_method']='pclzip';
            }
        }
        return $options;
    }

    public function update_backup_adaptive_settings($settings)
    {
        $options=get_option('wpvivid_common_setting');

        if(isset($settings['db_connect_method']))
        {
            $options['db_connect_method']=$settings['db_connect_method'];
        }

        if(isset($settings['memory_limit']))
        {
            $options['memory_limit']=$settings['memory_limit'];
        }

        if(isset($settings['max_execution_time']))
        {
            $options['max_execution_time']=$settings['max_execution_time'];
        }

        if(isset($settings['compress_file_use_cache']))
        {
            $options['compress_file_use_cache']=$settings['compress_file_use_cache'];
        }

        if(isset($settings['compress_file_count']))
        {
            $options['compress_file_count']=$settings['compress_file_count'];
        }

        if(isset($settings['max_file_size']))
        {
            $options['max_file_size']=$settings['max_file_size'];
        }

        if(isset($settings['max_sql_file_size']))
        {
            $options['max_sql_file_size']=$settings['max_sql_file_size'];
        }

        if(isset($settings['is_merge']))
        {
            $options['is_merge']=$settings['is_merge'];
        }

        if(isset($settings['exclude_file_size']))
        {
            $options['exclude_file_size']=$settings['exclude_file_size'];
        }

        if(isset($settings['compress_level']))
        {
            $options['compress_level']=$settings['compress_level'];
        }

        if(isset($settings['max_resume_count']))
        {
            $options['max_resume_count']=$settings['max_resume_count'];
        }

        update_option('wpvivid_common_setting',$options,'no');
    }

    public function get_backup_content($backup_options)
    {
        $backup_content=array();
        if(isset($backup_options['backup_files']))
        {
            if($backup_options['backup_files']=='files+db')
            {
                $backup_content['backup_db']='backup_db';
                $backup_content['backup_themes']='backup_themes';
                $backup_content['backup_plugin']='backup_plugin';
                $backup_content['backup_uploads']='backup_uploads';
                $backup_content['backup_content']='backup_content';
                $backup_content['backup_core']='backup_core';
            }
            else if($backup_options['backup_files']=='db')
            {
                $backup_content['backup_db']='backup_db';
            }
            else if($backup_options['backup_files']=='files')
            {
                $backup_content['backup_themes']='backup_themes';
                $backup_content['backup_plugin']='backup_plugin';
                $backup_content['backup_uploads']='backup_uploads';
                $backup_content['backup_content']='backup_content';
                $backup_content['backup_core']='backup_core';
            }
            else if($backup_options['backup_files']=='mu')
            {
                $backup_content['backup_db']='backup_db';
                $backup_content['backup_themes']='backup_themes';
                $backup_content['backup_plugin']='backup_plugin';
                $backup_content['backup_mu_site_uploads']='backup_mu_site_uploads';
                $backup_content['backup_content']='backup_content';
                $backup_content['backup_core']='backup_core';
            }
            else if($backup_options['backup_files']=='custom')
            {
                $custom_options=$backup_options['custom_dirs'];
                if(isset($custom_options['database_check'])&&$custom_options['database_check']==1)
                {
                    $backup_content['backup_db']='backup_db';
                }
                if(isset($custom_options['themes_check'])&&$custom_options['themes_check']==1)
                {
                    $backup_content['backup_themes']='backup_themes';
                }
                if(isset($custom_options['plugins_check'])&&$custom_options['plugins_check']==1)
                {
                    $backup_content['backup_plugin']='backup_plugin';
                }
                if(isset($custom_options['uploads_check'])&&$custom_options['uploads_check']==1)
                {
                    $backup_content['backup_uploads']='backup_uploads';
                }
                if(isset($custom_options['content_check'])&&$custom_options['content_check']==1)
                {
                    $backup_content['backup_content']='backup_content';
                }
                if(isset($custom_options['core_check'])&&$custom_options['core_check']==1)
                {
                    $backup_content['backup_core']='backup_core';
                }

                //other_check
                if(isset($custom_options['other_check'])&&$custom_options['other_check']==1)
                {
                    $backup_content['backup_custom_other']='backup_custom_other';
                }

                //additional_database_check
                if(isset($custom_options['additional_database_check'])&&$custom_options['additional_database_check']==1)
                {
                    $backup_content['backup_additional_db']='backup_additional_db';
                }
            }
        }
        return $backup_content;
    }

    public function is_tasks_backup_running($task_id='')
    {
        $tasks = get_option('wpvivid_task_list', array());

        if(empty($task_id))
        {
            foreach ($tasks as $task)
            {
                if ($task['status']['str']=='running'||$task['status']['str']=='no_responds')
                {
                    return true;
                }
            }
            return false;
        }
        else
        {
            if(isset($tasks[$task_id]))
            {
                $task=$tasks[$task_id];
                if ($task['status']['str']=='running'||$task['status']['str']=='no_responds')
                {
                    return true;
                }
            }
            return false;
        }
    }

    public function backup_now()
    {
        register_shutdown_function(array($this,'deal_backup_shutdown_error'));
        $this->end_shutdown_function=false;

        $task_id = sanitize_key($_POST['task_id']);
        $this->current_task_id=$task_id;
        global $wpvivid_plugin;

        if ($this->is_tasks_backup_running($task_id))
        {
            $ret['result'] = 'failed';
            $ret['error'] = __('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid-backuprestore');
            echo json_encode($ret);
            die();
        }

        try
        {
            WPvivid_taskmanager::update_backup_task_status($task_id,true,'running');
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->func->flush($task_id);
            $this->add_monitor_event($task_id);
            $this->task=new WPvivid_New_Backup_Task($task_id);
            $this->task->set_memory_limit();
            $this->task->set_time_limit();

            $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile($this->task->task['options']['log_file_name']);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start backing up.','notice');
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLogHander();

            if(!$this->task->is_backup_finished())
            {
                $ret=$this->backup();
                $this->task->clear_cache();
                if($ret['result']!='success')
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $ret['error'],'error');
                    $this->task->update_backup_task_status(false,'error',false,false,$ret['error']);
                    do_action('wpvivid_handle_new_backup_failed', $task_id);
                    $this->end_shutdown_function=true;
                    $this->clear_monitor_schedule($task_id);
                    die();
                }
            }

            if($this->task->need_upload())
            {
                $ret=$this->upload($task_id);
                if($ret['result'] == WPVIVID_SUCCESS)
                {
                    do_action('wpvivid_handle_new_backup_succeed',$task_id);
                    WPvivid_taskmanager::update_backup_task_status($task_id,false,'completed');
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploading the file ends with an error '. $ret['error'], 'error');
                    do_action('wpvivid_handle_new_backup_failed',$task_id);
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup completed.','notice');
                do_action('wpvivid_handle_new_backup_succeed', $task_id);
                WPvivid_taskmanager::update_backup_task_status($task_id,false,'completed');
            }
            $this->clear_monitor_schedule($task_id);
        }
        catch (Exception $error)
        {
            //catch error and stop task recording history
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            error_log($message);
            WPvivid_taskmanager::update_backup_task_status($task_id,false,'error',false,false,$message);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog($message,'error');
            do_action('wpvivid_handle_new_backup_failed',$task_id);
            $this->end_shutdown_function=true;
            die();
        }


        $this->end_shutdown_function=true;

        die();
    }

    public function pre_new_backup_for_mainwp($backup_options)
    {
        if(apply_filters('wpvivid_need_clean_oldest_backup_ex',true,$backup_options))
        {
            $this->clean_oldest_backup($backup_options);
        }

        if($this->is_tasks_backup_running())
        {
            $ret['result']='failed';
            $ret['error']=__('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid');
            return $ret;
        }

        $options=$this->get_backup_options($backup_options);
        $settings=$this->get_backup_settings($backup_options);
        $backup_content=$this->get_backup_content($backup_options);
        $backup=new WPvivid_New_Backup_Task();
        $ret=$backup->new_backup_task($options,$settings,$backup_content);
        return $ret;
    }

    public function backup_now_for_mainwp($task_id)
    {
        register_shutdown_function(array($this,'deal_backup_shutdown_error'));
        $this->end_shutdown_function=false;

        $this->current_task_id=$task_id;
        global $wpvivid_plugin;

        if ($this->is_tasks_backup_running($task_id))
        {
            $ret['result'] = 'failed';
            $ret['error'] = __('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid-backuprestore');
            echo json_encode($ret);
        }

        try
        {
            WPvivid_taskmanager::update_backup_task_status($task_id,true,'running');
            global $wpvivid_backup_pro;
            //$wpvivid_backup_pro->func->flush($task_id);
            $this->add_monitor_event($task_id);
            $this->task=new WPvivid_New_Backup_Task($task_id);
            $this->task->set_memory_limit();
            $this->task->set_time_limit();

            $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile($this->task->task['options']['log_file_name']);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start backing up.','notice');
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLogHander();

            if(!$this->task->is_backup_finished())
            {
                $ret=$this->backup();
                $this->task->clear_cache();
                if($ret['result']!='success')
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $ret['error'],'error');
                    $this->task->update_backup_task_status(false,'error',false,false,$ret['error']);
                    do_action('wpvivid_handle_new_backup_failed', $task_id);
                    $this->end_shutdown_function=true;
                    $this->clear_monitor_schedule($task_id);
                }
            }

            if($this->task->need_upload())
            {
                $ret=$this->upload($task_id);
                if($ret['result'] == WPVIVID_SUCCESS)
                {
                    do_action('wpvivid_handle_new_backup_succeed',$task_id);
                    WPvivid_taskmanager::update_backup_task_status($task_id,false,'completed');
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploading the file ends with an error '. $ret['error'], 'error');
                    do_action('wpvivid_handle_new_backup_failed',$task_id);
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup completed.','notice');
                do_action('wpvivid_handle_new_backup_succeed', $task_id);
                WPvivid_taskmanager::update_backup_task_status($task_id,false,'completed');
            }
            $this->clear_monitor_schedule($task_id);
        }
        catch (Exception $error)
        {
            //catch error and stop task recording history
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            error_log($message);
            WPvivid_taskmanager::update_backup_task_status($task_id,false,'error',false,false,$message);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog($message,'error');
            do_action('wpvivid_handle_new_backup_failed',$task_id);
            $this->end_shutdown_function=true;
        }
        $this->end_shutdown_function=true;
    }

    public function backup()
    {
        $ret['result']='success';

        $this->task->wpvivid_check_add_litespeed_server();

        $this->backup_type_report = '';

        while (!$this->task->is_backup_finished())
        {
            if($this->task->check_cancel_backup())
            {
                $this->end_shutdown_function=true;
                die();
            }

            $job=$this->task->get_next_job();
            if($job===false)
                break;

            $this->task->set_time_limit();

            $backup_type=$this->task->get_backup_job_type($job);
            $this->backup_type_report .= $backup_type.',';

            $ret=$this->task->do_backup_job($job);
            if($ret['result']!='success')
            {
                break;
            }
        }

        update_option('wpvivid_backup_report', $this->backup_type_report, 'no');

        if($ret['result']==='success')
        {
            global $wpvivid_plugin;
            remove_filter('wpvivid_check_backup_completeness', array($wpvivid_plugin, 'check_backup_completeness'));
            $check_res = apply_filters('wpvivid_check_backup_completeness', true, $this->task->task_id);
            if(!$check_res){
                $ret['result'] = WPVIVID_PRO_RESTORE_ERROR;
                $ret['error'] = 'We have detected that this backup is either corrupted or incomplete. Please make sure your server disk space is sufficient then create a new backup. In order to successfully back up/restore a website, the amount of free server disk space needs to be at least twice the size of the website';
            }
        }

        return $ret;
    }

    public function upload($task_id)
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;

        $load=new WPvivid_Load_Admin_Remote();
        $load->load();

        $files=$this->task->get_backup_files();
        $remote_options=$this->task->get_remote_options();

        $last_error='';
        $success=false;

        foreach ($remote_options as $key => $remote_option)
        {
            if($this->task->check_cancel_backup())
            {
                $this->end_shutdown_function=true;
                die();
            }

            if(!isset($remote_option['id']))
            {
                $remote_option['id'] = $key;
            }

            $remote_collection=new WPvivid_Remote_collection_addon();
            $remote=$remote_collection->get_remote($remote_option);

            $upload_job=WPvivid_taskmanager::get_backup_sub_task_progress($task_id,'upload',$remote_option['id']);
            if(!empty($upload_job))
            {
                if($upload_job['finished']==WPVIVID_UPLOAD_SUCCESS||$upload_job['finished']==WPVIVID_UPLOAD_FAILED)
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog($remote_option['type'].' already finished so skip it.','notice');
                    continue;
                }
            }

            try
            {
                $backup_info_file=$this->task->get_backup_info_file();
                $files[]=$backup_info_file;
                $result=$remote->upload($task_id,$files,array($this,'upload_callback'));
                if($result['result']==WPVIVID_PRO_SUCCESS)
                {
                    $success=true;
                    WPvivid_taskmanager::update_backup_task_status($task_id,false,'running',false,0);
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finish upload to '.$remote_option['type'],'notice');
                    WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$remote_option['id'],WPVIVID_UPLOAD_SUCCESS,'Finish upload to'.$remote_option['type']);
                    continue;
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finish upload to '.$remote_option['type'].' error:'.$result['error'],'notice');
                    WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$remote_option['id'],WPVIVID_UPLOAD_FAILED,'Finish upload to'.$remote_option['type']);
                    $remote ->cleanup($files);
                    //$error=true;
                    $last_error=$result['error'];
                    continue;

                }
            }
            catch (Exception $e)
            {
                //catch error and stop task recording history
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Finish upload to '.$remote_option['type'].' error:'.$e->getMessage(),'notice');
                WPvivid_taskmanager::update_backup_sub_task_progress($task_id,'upload',$remote_option['id'],WPVIVID_UPLOAD_FAILED,'Finish upload to'.$remote_option['type']);
                $last_error=$e->getMessage();
                continue;
            }
        }

        if(!$success)
        {
            WPvivid_taskmanager::update_backup_task_status($task_id,false,'error',false,false,$last_error);
            return array('result' => WPVIVID_PRO_FAILED , 'error' => $last_error);
        }
        else
        {
            WPvivid_taskmanager::update_backup_main_task_progress($task_id,'upload',100,1);
            WPvivid_taskmanager::update_backup_task_status($task_id,false,'completed');
            return array('result' => WPVIVID_PRO_SUCCESS);
        }
    }

    public function upload_callback($offset,$current_name,$current_size,$last_time,$last_size)
    {
        $job_data=array();
        $upload_data=array();
        $upload_data['offset']=$offset;
        $upload_data['current_name']=$current_name;
        $upload_data['current_size']=$current_size;
        $upload_data['last_time']=$last_time;
        $upload_data['last_size']=$last_size;
        $upload_data['descript']='Uploading '.$current_name;
        $v =( $offset - $last_size ) / (time() - $last_time);
        $v /= 1000;
        $v=round($v,2);

        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        $this->task->check_cancel_backup();

        $message='Uploading '.$current_name.' Total size: '.size_format($current_size,2).' Uploaded: '.size_format($offset,2).' speed:'.$v.'kb/s';
        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog($message,'notice');
        $progress=intval(($offset/$current_size)*100);
        WPvivid_taskmanager::update_backup_main_task_progress($this->current_task_id,'upload',$progress,0);
        WPvivid_taskmanager::update_backup_sub_task_progress($this->current_task_id,'upload','',WPVIVID_UPLOAD_UNDO,$message, $job_data, $upload_data);
    }

    public function new_backup_schedule($task_id)
    {
        $this->current_task_id=$task_id;
        if(empty($task_id))
        {
            die();
        }

        if ($this->is_tasks_backup_running($task_id))
        {
            $ret['result'] = 'failed';
            $ret['error'] = __('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid-backuprestore');
            echo json_encode($ret);
            die();
        }
        $this->end_shutdown_function=false;
        register_shutdown_function(array($this,'deal_backup_shutdown_error'));
        global $wpvivid_plugin;
        try
        {
            WPvivid_taskmanager::update_backup_task_status($task_id,true,'running');
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->func->flush($task_id);
            $this->add_monitor_event($task_id);
            $this->task=new WPvivid_New_Backup_Task($task_id);
            $this->task->set_memory_limit();
            $this->task->set_time_limit();

            $this->task->update_schedule_last_backup_time();

            $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile(WPvivid_taskmanager::get_task_options($task_id,'log_file_name'));
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Start backing up.','notice');
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLogHander();

            if(!$this->task->is_backup_finished())
            {
                $ret=$this->backup();
                $this->task->clear_cache();
                if($ret['result']!='success')
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $ret['error'],'error');
                    $this->task->update_backup_task_status(false,'error',false,false,$ret['error']);
                    do_action('wpvivid_handle_new_backup_failed', $task_id);
                    $this->end_shutdown_function=true;
                    $this->clear_monitor_schedule($task_id);
                    die();
                }
            }

            if($this->task->need_upload())
            {
                $ret=$this->upload($task_id);
                if($ret['result'] == WPVIVID_SUCCESS)
                {
                    do_action('wpvivid_handle_new_backup_succeed',$task_id);
                    $task=WPvivid_taskmanager::update_backup_task_status($task_id,false,'completed');
                }
                else
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Uploading the file ends with an error '. $ret['error'], 'error');
                    do_action('wpvivid_handle_new_backup_failed',$task_id);
                }
            }
            else
            {
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup completed.','notice');
                do_action('wpvivid_handle_new_backup_succeed', $task_id);
                WPvivid_taskmanager::update_backup_task_status($task_id,false,'completed');
            }
            $this->clear_monitor_schedule($task_id);
        }
        catch (Exception $error)
        {
            //catch error and stop task recording history
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            error_log($message);
            WPvivid_taskmanager::update_backup_task_status($task_id,false,'error',false,false,$message);
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog($message,'error');
            do_action('wpvivid_handle_new_backup_failed',$task_id);
            $this->end_shutdown_function=true;
            die();
        }

        $this->end_shutdown_function=true;

        die();
    }

    public function deal_backup_shutdown_error()
    {
        if($this->end_shutdown_function===false)
        {
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $options = get_option('wpvivid_task_list',array());
            if(!isset($options[$this->current_task_id]))
            {
                die();
            }

            $error = error_get_last();
            $resume_backup=false;
            $memory_limit=false;
            $max_execution_time=false;

            if (!is_null($error))
            {
                if (empty($error) || !in_array($error['type'], array(E_ERROR,E_RECOVERABLE_ERROR,E_CORE_ERROR,E_COMPILE_ERROR), true))
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('In shutdown function last message type:'.$error['type'].' str:'.$error['message'],'notice');
                }

                if(preg_match('/Allowed memory size of.*$/', $error['message']))
                {
                    $resume_backup=true;
                    $memory_limit=true;
                }
                else if(preg_match('/Maximum execution time of.*$/', $error['message']))
                {
                    $resume_backup=true;
                    $max_execution_time=true;
                }
            }

            $task= new WPvivid_New_Backup_Task($this->current_task_id);
            $status=$task->get_status();
            if($memory_limit===true)
            {
                if(!$task->check_memory_limit())
                {
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $error['message'],'error');
                    $task->update_backup_task_status(false,'error',false,$status['resume_count'],$error['message']);
                    do_action('wpvivid_handle_new_backup_failed', $this->current_task_id);
                    $resume_backup=false;
                }
            }

            if($max_execution_time===true)
            {
                $task->check_execution_time();
            }

            if($status['str']!='completed')
            {
                $max_resume_count=$task->get_max_resume_count();
                $status=$task->get_status();
                $status['resume_count']++;
                if($status['resume_count']>$max_resume_count)
                {
                    $message=__('Too many resumption attempts.', 'wpvivid-backuprestore');
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $message,'error');
                    $task->update_backup_task_status(false,'error',false,$status['resume_count'],$message);
                    if($resume_backup)
                        $task->check_timeout_backup_failed();
                    do_action('wpvivid_handle_new_backup_failed', $this->current_task_id);
                }
                else
                {
                    $message=__('Task timed out.', 'wpvivid-backuprestore');
                    $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Task timed out.','error');
                    $timestamp = wp_next_scheduled('wpvivid_new_backup_schedule_event',array($this->current_task_id));
                    if($timestamp===false)
                    {
                        $task->update_backup_task_status(false,'wait_resume',false,$status['resume_count']);
                        if($this->add_resume_event($this->current_task_id)===false)
                        {
                            $task->update_backup_task_status(false,'error',false,$status['resume_count'],$message);
                            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $message,'error');
                            if($resume_backup)
                                $task->check_timeout_backup_failed();
                            do_action('wpvivid_handle_new_backup_failed', $this->current_task_id);
                        }
                    }
                }
            }
        }

        die();
    }

    public function add_monitor_event($task_id,$next_time=120)
    {
        $resume_time=time()+$next_time;

        $timestamp = wp_next_scheduled('wpvivid_task_monitor_event_ex',array($task_id));

        if($timestamp===false)
        {
            $b = wp_schedule_single_event($resume_time, 'wpvivid_task_monitor_event_ex', array($task_id));
            if ($b === false)
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        return true;
    }

    public function clear_monitor_schedule($id)
    {
        $timestamp =wp_next_scheduled('wpvivid_task_monitor_event_ex',array($id));
        if($timestamp!==false)
        {
            wp_unschedule_event($timestamp,'wpvivid_task_monitor_event_ex',array($id));
        }
    }

    private function add_resume_event($task_id)
    {
        $resume_time=time()+10;

        $b=wp_schedule_single_event($resume_time,'wpvivid_new_backup_schedule_event',array($task_id));

        if($b===false)
        {
            $timestamp = wp_next_scheduled('wpvivid_new_backup_schedule_event',array($task_id));

            if($timestamp===false)
            {
                return false;
            }
            else
            {
                return true;
                //$resume_time=max($resume_time,$timestamp+10*60+10);
                //$b=wp_schedule_single_event($resume_time,'wpvivid_new_backup_schedule_event',array($task_id));
                //if($b===false)
                //{
                //    return false;
                //}
            }
        }
        return true;
    }

    public function list_tasks()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            $ret = $this->_list_tasks();

            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }

        die();
    }

    public function _list_tasks()
    {
        if($this->wpvivid_check_litespeed_server() && $this->wpvivid_check_litespeed_cache_plugin())
        {
            wp_cache_delete('wpvivid_task_list', 'options');
        }

        $tasks = get_option('wpvivid_task_list', array());
        $ret['result']='success';
        $ret['progress_html']=false;
        $ret['success_notice_html'] =false;
        $ret['error_notice_html'] =false;
        $ret['need_update']=false;
        $ret['last_msg_html']=false;
        $ret['running_backup_taskid']='';
        $ret['wait_resume']=false;
        $ret['next_resume_time']=false;
        $ret['need_refresh_remote']=false;
        $ret['backup_finish_info']=false;
        $ret['task_no_response']=false;

        $finished_tasks=array();
        $backup_success_count=0;
        $backup_failed_count=0;
        $success_log_file_name = '';
        $ret['test']=$tasks;
        foreach ($tasks as $task)
        {
            if(!isset($task['id']))
            {
                continue;
            }

            $ret['task_id']=$task['id'];
            $ret['need_update']=true;
            if(isset($task['options']['export']))
            {
                $ret['export'] =$task['options']['export'];
            }
            else
            {
                $ret['export'] ='';
            }
            $backup_task=new WPvivid_New_Backup_Task($task['id']);
            $info=$backup_task->get_backup_task_info();
            $ret['need_next_schedule']=$info['task_info']['need_next_schedule'];
            if($info['task_info']['need_next_schedule']===true)
            {
                $timestamp = wp_next_scheduled('wpvivid_task_monitor_event_ex',array($task['id']));
                if($timestamp===false)
                {
                    $this->add_monitor_event($task['id'],20);
                }
            }
            if($info['status']['str']=='ready'||$info['status']['str']=='running'||$info['status']['str']=='wait_resume'||$info['status']['str']=='no_responds')
            {
                $ret['running_backup_taskid']=$task['id'];

                if($info['status']['str']=='wait_resume')
                {
                    $ret['wait_resume']=true;
                    $ret['next_resume_time']=$info['data']['next_resume_time'];
                }

                if($info['status']['str']=='no_responds')
                {
                    $ret['task_no_response']=true;
                }

                $ret['progress_html'] = '<div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                                            <p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="width:'.$info['task_info']['backup_percent'].'">'.$info['task_info']['backup_percent'].' completed</span></span></p>
                                            <p>
                                                <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Total Size:</span><span>'.$info['task_info']['total'].'</span>
                                                <span class="dashicons dashicons-upload wpvivid-dashicons-blue"></span><span>Uploaded:</span><span>'.$info['task_info']['upload'].'</span>
                                                <span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span><span>Speed:</span><span>'.$info['task_info']['speed'].'</span>
                                                <span class="dashicons dashicons-networking wpvivid-dashicons-green"></span><span>Network Connection:</span><span>'.$info['task_info']['network_connection'].'</span>
                                            </p>
                                            <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span id="wpvivid_current_doing">'.$info['task_info']['descript'].'</span></p>
                                            <div><input class="button-primary" id="wpvivid_backup_cancel_btn" type="submit" value="Cancel" style="'.$info['task_info']['css_btn_cancel'].'"></div>
                                        </div>';
            }

            if($info['status']['str']=='completed')
            {
                $finished_tasks[$task['id']]=$task;
                $backup_success_count++;
                $success_log_file_name = $task['id'].'_backup_log.txt';
            }
            else if($info['status']['str']=='error')
            {
                $finished_tasks[$task['id']]=$task;
                $backup_failed_count++;
            }
        }

        if(!empty($ret['running_backup_taskid']))
        {
            $timestamp = wp_next_scheduled('wpvivid_task_monitor_event_ex',array($ret['running_backup_taskid']));
            if($timestamp===false)
            {
                $this->add_monitor_event($ret['running_backup_taskid'],20);
            }
        }

        if(!empty($finished_tasks))
        {
            $ret['backup_finish_info']=$this->_wpvivid_get_backup_finish_info($finished_tasks);
            foreach ($finished_tasks as $id => $finished_task)
            {
                if($finished_task['status']['str']=='completed'&&isset($finished_task['options']['export']))
                {
                    if($finished_task['options']['export']=='local_export_site')
                    {
                        $backup_id = $id;
                        $backup_list=new WPvivid_New_BackupList();
                        $backup = $backup_list->get_backup_by_id($backup_id);
                        if ($backup !== false)
                        {
                            $backup_item = new WPvivid_New_Backup_Item($backup);

                            $backup_files = $backup_item->get_download_backup_files($backup_id);
                            if ($backup_files['result'] == WPVIVID_PRO_SUCCESS)
                            {
                                foreach ($backup_files['files'] as $file)
                                {
                                    $path = $this->get_backup_path($backup_item, $file['file_name']);
                                    if (file_exists($path))
                                    {
                                        if (filesize($path) == $file['size'])
                                        {
                                            if (WPvivid_taskmanager::get_download_task_v2($file['file_name']))
                                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                                            $ret['local_export_files'][$file['file_name']]['status'] = 'completed';
                                            $ret['local_export_files'][$file['file_name']]['size'] = size_format(filesize($path), 2);
                                            $ret['local_export_file_complete'] = true;
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else if($finished_task['options']['export']=='remote_export_site')
                    {
                        $ret['remote_export_file_complete'] = true;
                    }
                    else if($finished_task['options']['export']=='auto_migrate')
                    {
                        $ret['migration_export_file_complete'] = true;
                        $notice_msg = 'Transfer succeeded. Please scan the backup list on the destination site to display the backup, then restore the backup.';
                        $ret['success_notice_html'] =__('<div class="notice notice-success notice-transfer-success is-dismissible inline" style="margin-bottom: 5px;"><p>'.$notice_msg.'</p></div>');
                        update_option('wpvivid_display_auto_migration_success_notice', true, 'no');
                    }
                }
            }
        }

        if($backup_success_count>0)
        {
            $log_url=apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-backup-and-restore').'&log='.$success_log_file_name;
            $notice_msg = $backup_success_count.' backup task(s) finished. Please switch to <a href="'.$log_url.'">Log</a> page to check the details.';
            $ret['success_notice_html'] =__('<div class="notice notice-success is-dismissible inline" style="margin-bottom: 5px;"><p>'.$notice_msg.'</p>
                                    <button type="button" class="notice-dismiss" onclick="click_dismiss_notice(this);">
                                    <span class="screen-reader-text">Dismiss this notice.</span>
                                    </button>
                                    </div>');
        }

        if($backup_failed_count>0)
        {
            $admin_url = apply_filters('wpvivid_get_admin_url', '');
            $notice_msg = $backup_failed_count.' backup task(s) have been failed. Please switch to <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'" >Website Info</a> page to send us the debug information.';
            $ret['error_notice_html'] =__('<div class="notice notice-error inline" style="margin-bottom: 5px;"><p>'.$notice_msg.'</p></div>');
        }

        $delete_ids=array();

        foreach ($tasks as $task)
        {
            if(array_key_exists($task['id'],$finished_tasks))
            {
                $delete_ids[]=$task['id'];
            }
        }
        foreach ($delete_ids as $id)
        {
            unset($tasks[$id]);
        }
        WPvivid_Setting::update_option('wpvivid_task_list',$tasks);

        return $ret;
    }

    public function _wpvivid_get_backup_finish_info($finished_tasks)
    {
        $ret['backup_finish_info']=false;

        foreach ($finished_tasks as $id => $finished_task)
        {
            if($finished_task['type']==='Manual' && $finished_task['status']['str']==='completed')
            {
                if($finished_task['options']['remote_options']===false)
                {
                    $ret['backup_finish_info']='local';

                    $backup_id = $id;
                    $backup_list=new WPvivid_New_BackupList();
                    $backup = $backup_list->get_backup_by_id($backup_id);
                    if ($backup !== false)
                    {
                        $backup_item = new WPvivid_New_Backup_Item($backup);

                        $backup_files = $backup_item->get_download_backup_files($backup_id);
                        if ($backup_files['result'] == WPVIVID_PRO_SUCCESS)
                        {
                            foreach ($backup_files['files'] as $file)
                            {
                                $path = $this->get_backup_path($backup_item, $file['file_name']);
                                if (file_exists($path))
                                {
                                    if (filesize($path) == $file['size'])
                                    {
                                        if (WPvivid_taskmanager::get_download_task_v2($file['file_name']))
                                            WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                                        $ret['local_backup_files'][$file['file_name']]['status'] = 'completed';
                                        $ret['local_backup_files'][$file['file_name']]['size'] = size_format(filesize($path), 2);
                                        continue;
                                    }
                                }
                            }
                        }
                    }
                }
                else
                {
                    $ret['backup_finish_info']='remote';
                }
            }
        }

        return $ret;
    }

    public static function wpvivid_transfer_backup_type($backup_type)
    {
        switch ($backup_type){
            case 'backup_db':
                $backup_type = 'database';
                break;
            case 'backup_themes':
                $backup_type = 'themes';
                break;
            case 'backup_plugin':
                $backup_type = 'plugins';
                break;
            case 'backup_uploads':
                $backup_type = 'uploads';
                break;
            case 'backup_content':
                $backup_type = 'wp-content';
                break;
            case 'backup_core':
                $backup_type = 'core';
                break;
            default:
                $backup_type = '';
                break;
        }
        return $backup_type;
    }

    public function check_backup_file_json($file_name)
    {
        $setting=get_option('wpvivid_common_setting',array());
        $zip_method=isset($setting['zip_method'])?$setting['zip_method']:'ziparchive';

        if($zip_method=='ziparchive'||empty($zip_method))
        {
            if(class_exists('ZipArchive'))
            {
                if(method_exists('ZipArchive', 'addFile'))
                {
                    $zip_method='ziparchive';
                }
                else
                {
                    $zip_method='pclzip';
                }
            }
            else
            {
                $zip_method='pclzip';
            }
        }
        else
        {
            $zip_method='pclzip';
        }

        if($zip_method=='ziparchive')
        {
            $backup_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
            $file_path=$backup_path.$file_name;

            $zip_object=new ZipArchive();
            $zip_object->open($file_path);

            $json=$zip_object->getFromName('wpvivid_package_info.json');
            if($json !== false)
            {
                $json = json_decode($json, 1);
                if (is_null($json))
                {
                    return false;
                }
                else
                {
                    return $json;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            if(!class_exists('WPvivid_ZipClass'))
                include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-zipclass.php';
            $zip=new WPvivid_ZipClass();

            $backup_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
            $file_path=$backup_path.$file_name;

            $ret=$zip->get_json_data($file_path);

            if($ret['result'] === 'success')
            {
                $json=$ret['json_data'];
                $json = json_decode($json, 1);
                if (is_null($json))
                {
                    return false;
                }
                else
                {
                    return $json;
                }
            }
            else
            {
                return false;
            }
        }
    }

    function wpvivid_update_mainwp_client_report()
    {
        $destination = "";
        $message = apply_filters('wpvivid_white_label_display', 'WPvivid').' backup finished';
        $backup_type = 'wpvivid database, plugins, themes';
        $backup_type = get_option('wpvivid_backup_report', false);
        $backup_type = explode(',', $backup_type);
        $tmp_backup_type = '';
        $backup_report = '';
        for($index=0; $index<count($backup_type); $index++)
        {
            if(!empty($backup_type[$index]))
            {
                $tmp_backup_type = self::wpvivid_transfer_backup_type($backup_type[$index]);
                if($tmp_backup_type !== '')
                {
                    $backup_report .= $tmp_backup_type . ',';
                }
            }
        }
        if($backup_report !== '')
        {
            $backup_report = rtrim($backup_report, ',');
        }
        else{
            $backup_report = apply_filters('wpvivid_white_label_display', 'WPvivid').' Backup';
        }
        $backup_time = time();
        do_action("wpvivid_backup", $destination , $message, __('Finished', 'mainwp-child-reports'), $backup_report, $backup_time);
        delete_option('wpvivid_backup_report');
    }

    public function handle_backup_succeed($task_id)
    {
        $task= new WPvivid_New_Backup_Task($task_id);
        $backup_list=new WPvivid_New_BackupList();

        $task->update_end_time();
        $setting=$task->get_setting();

        $common_setting=get_option('wpvivid_common_setting',array());
        if(isset($common_setting['use_adaptive_settings'])&&$common_setting['use_adaptive_settings'])
        {
            $this->update_backup_adaptive_settings($setting);
        }

        if($this->task->need_upload())
        {
            $task->update_incremental_backup_data();
            $task->set_remote_lock();

            $backup=false;
            $remote_options=$this->task->get_remote_options();
            foreach ($remote_options as $remote_id=>$remote_option)
            {
                $backup=$backup_list->get_remote_backup($remote_id,$task_id);
                if($backup!==false)
                {
                    break;
                }
            }

            if($backup!==false)
            {
                $task->add_exist_remote_backup($task_id);
            }
            else
            {
                $task->add_new_remote_backup();
            }

            if(!$this->task->is_save_local())
            {
                $task->clean_local_files();
            }
            do_action('wpvivid_clean_oldest_backup');
        }
        else
        {
            $backup=$backup_list->get_local_backup($task_id);
            if($backup!==false)
            {
                $task->add_exist_backup($task_id);
                $task->update_incremental_backup_data();
            }
            else
            {
                $task->add_new_backup();
                $task->update_incremental_backup_data();
            }

            set_time_limit(120);
            $backup_ids=array();
            $backup_ids=apply_filters('wpvivid_get_oldest_backup_ids',$backup_ids,true);
            global $wpvivid_plugin;
            if(!empty($backup_ids))
            {
                foreach ($backup_ids as $backup_id)
                {
                    WPvivid_Custom_Interface_addon::delete_backup_by_id($backup_id);
                }
            }
        }

        $task_msg = WPvivid_taskmanager::get_task($task_id);
        update_option('wpvivid_last_msg',$task_msg,'no');
        apply_filters('wpvivid_set_backup_report_addon_mainwp', $task_msg);

        $this->wpvivid_update_mainwp_client_report();
        $task->wpvivid_check_clear_litespeed_rule();


        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if ( is_plugin_active( 'mainwp-child/mainwp-child.php' ) && defined( 'WPVIVID_PLUGIN_DIR' ) ) {
            if ( class_exists( '\MainWP\Child\MainWP_Utility' ) )
            {
                $backup_time = $task->get_start_time();
                \MainWP\Child\MainWP_Utility::update_lasttime_backup( 'wpvivid', $backup_time );
            }
        }

        $files=$task->get_backup_files();
        if(!empty($files))
        {
            do_action('wpvivid_do_mail_report',$task_id);
            //$task->update_schedule_last_backup_time();
        }
    }

    public function handle_backup_failed($task_id)
    {
        $task= new WPvivid_New_Backup_Task($task_id);
        $task->update_end_time();
        $setting=$task->get_setting();

        $common_setting=get_option('wpvivid_common_setting',array());
        if(isset($common_setting['use_adaptive_settings'])&&$common_setting['use_adaptive_settings'])
        {
            $this->update_backup_adaptive_settings($setting);
        }

        $this->add_clean_backup_data_event($task_id);

        $task_msg = WPvivid_taskmanager::get_task($task_id);
        update_option('wpvivid_last_msg',$task_msg,'no');
        apply_filters('wpvivid_set_backup_report_addon_mainwp', $task_msg);
        $task->update_schedule_last_backup_time();

        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        if($wpvivid_backup_pro->wpvivid_pro_log)
        {
            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog($task_msg['status']['error'],'error');
            $wpvivid_backup_pro->wpvivid_pro_log->CloseFile();
            WPvivid_error_log::create_error_log($wpvivid_backup_pro->wpvivid_pro_log->log_file);
        }
        $task->wpvivid_check_clear_litespeed_rule();

        do_action('wpvivid_do_mail_report',$task_id);
    }

    public function add_clean_backup_data_event($task_id)
    {
        $task=WPvivid_taskmanager::get_task($task_id);
        $tasks=WPvivid_Setting::get_option('wpvivid_clean_task_ex');
        $tasks[$task_id]=$task;
        WPvivid_Setting::update_option('wpvivid_clean_task_ex',$tasks);

        $resume_time=time()+60;

        $b=wp_schedule_single_event($resume_time,'wpvivid_clean_backup_data_event',array($task_id));

        if($b===false)
        {
            $timestamp = wp_next_scheduled('wpvivid_clean_backup_data_event',array($task_id));

            if($timestamp!==false)
            {
                $resume_time=max($resume_time,$timestamp+10*60+10);

                $b=wp_schedule_single_event($resume_time,'wpvivid_clean_backup_data_event',array($task_id));

                if($b===false)
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        return true;
    }

    public function clean_backup_data_event($task_id)
    {
        $tasks=get_option('wpvivid_clean_task_ex',array());
        if(isset($tasks[$task_id]))
        {
            $task_data=$tasks[$task_id];
            unset($tasks[$task_id]);
        }
        update_option('wpvivid_clean_task_ex',$tasks,'no');

        if(!empty($task_data))
        {
            $task= new WPvivid_New_Backup_Task($task_id,$task_data);
            $task->clean_backup();

            $files=array();

            if($task->need_upload())
            {
                $backup_files=$task->get_backup_files();
                foreach ($backup_files as $file)
                {
                    $files[]=basename($file);
                }
                if(!empty($files))
                {
                    if(!class_exists('WPvivid_Upload'))
                        include_once WPVIVID_PLUGIN_DIR . '/includes/class-wpvivid-upload.php';
                    $upload=new WPvivid_Upload();
                    $upload->clean_remote_backup($task->get_remote_options(),$files);
                }
            }
            //clean upload
        }
    }

    public function task_monitor($task_id)
    {
        if(WPvivid_taskmanager::get_task($task_id)!==false)
        {
            $task=new WPvivid_New_Backup_Task($task_id);

            $status=$task->get_status();

            if($task->is_task_canceled())
            {
                $limit=$task->get_time_limit();

                $last_active_time=time()-$status['run_time'];
                if($last_active_time>180)
                {
                    if($task->check_cancel_backup())
                    {
                        $this->end_shutdown_function=true;
                        die();
                    }
                }
            }
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->wpvivid_pro_log->OpenLogFile(WPvivid_taskmanager::get_task_options($task_id,'log_file_name'));

            if($status['str']=='running'||$status['str']=='error'||$status['str']=='no_responds')
            {
                $limit=$task->get_time_limit();

                $time_spend=time()-$status['timeout'];
                $last_active_time=time()-$status['run_time'];
                if($time_spend>$limit&&$last_active_time>180)
                {
                    //time out
                    $max_resume_count=$task->get_max_resume_count();
                    $task->check_timeout();
                    $status['resume_count']++;
                    if($status['resume_count']>$max_resume_count)
                    {
                        $message=__('Too many resumption attempts.', 'wpvivid-backuprestore');
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $message,'error');
                        $task->update_backup_task_status(false,'error',false,$status['resume_count'],$message);
                        $task->check_timeout_backup_failed();
                        do_action('wpvivid_handle_new_backup_failed', $task_id);
                    }
                    else
                    {
                        $message=__('Task timed out.', 'wpvivid-backuprestore');
                        $task->update_backup_task_status(false,'wait_resume',false,$status['resume_count']);
                        if($this->add_resume_event($task_id)===false)
                        {
                            $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $message,'error');
                            $task->update_backup_task_status(false,'error',false,$status['resume_count'],$message);
                            $task->check_timeout_backup_failed();
                            do_action('wpvivid_handle_new_backup_failed', $task_id);
                        }
                    }
                }
                else
                {
                    $time_spend=time()-$status['run_time'];
                    if($time_spend>180)
                    {
                        $task->update_backup_task_status(false,'no_responds',false,$status['resume_count']);
                        $this->add_monitor_event($task_id);
                    }
                    else {
                        $this->add_monitor_event($task_id);
                    }
                }
            }
            else if($status['str']=='wait_resume')
            {
                $timestamp = wp_next_scheduled(WPVIVID_RESUME_SCHEDULE_EVENT,array($task_id));
                if($timestamp===false)
                {
                    $message = 'Task timed out (WebHosting).';
                    $task->update_backup_task_status(false, 'wait_resume', false, $status['resume_count']);
                    if ($this->add_resume_event($task_id)===false)
                    {
                        $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('Backup the file ends with an error '. $message,'error');
                        $task->update_backup_task_status(false, 'error', false, $status['resume_count'], $message);
                        $task->check_timeout_backup_failed();
                        do_action('wpvivid_handle_new_backup_failed', $task_id);
                    }
                }
            }
        }
    }

    public function export_backup_to_site()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            if(isset($_POST['backup'])&&!empty($_POST['backup']))
            {
                $options = WPvivid_Setting::get_option('wpvivid_saved_api_token');

                if (empty($options)) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'A key is required.';
                    echo json_encode($ret);
                    die();
                }

                $url = '';
                foreach ($options as $key => $value) {
                    $url = $value['url'];
                }

                if ($url === '') {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                if ($options[$url]['expires'] != 0 && $options[$url]['expires'] < time()) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The key has expired.';
                    echo json_encode($ret);
                    die();
                }

                $json['test_connect']=1;
                $json=json_encode($json);
                $crypt=new WPvivid_crypt(base64_decode($options[$url]['token']));
                $data=$crypt->encrypt_message($json);
                $data=base64_encode($data);
                $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site_connect');
                $response=wp_remote_post($url,$args);

                if ( is_wp_error( $response ) )
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']= $response->get_error_message();
                    echo json_encode($ret);
                    die();
                }
                else
                {
                    if($response['response']['code']==200) {
                        $res=json_decode($response['body'],1);
                        if($res!=null) {
                            if($res['result']==WPVIVID_PRO_SUCCESS) {
                            }
                            else {
                                $ret['result']=WPVIVID_PRO_FAILED;
                                $ret['error']= $res['error'];
                                echo json_encode($ret);
                                die();
                            }
                        }
                        else {
                            $ret['result']=WPVIVID_PRO_FAILED;
                            $ret['error']= 'failed to parse returned data, unable to establish connection with the target site.';
                            $ret['response']=$response;
                            echo json_encode($ret);
                            die();
                        }
                    }
                    else {
                        $ret['result']=WPVIVID_PRO_FAILED;
                        $ret['error']= 'upload error '.$response['response']['code'].' '.$response['body'];
                        echo json_encode($ret);
                        die();
                    }
                }

                $json = $_POST['backup'];
                $json = stripslashes($json);
                $backup_options = json_decode($json, true);
                if (is_null($backup_options))
                {
                    die();
                }

                $remote_option['url'] = $options[$url]['url'];
                $remote_option['token'] = $options[$url]['token'];
                $remote_option['type'] = WPVIVID_REMOTE_SEND_TO_SITE_ADDON;
                $remote_options['temp'] = $remote_option;
                $backup_options['remote_options'] = $remote_options;

                $backup_options['type']='Migrate';
                $backup_options['export']='auto_migrate';
                $ret=$this->pre_new_backup($backup_options);

                echo json_encode($ret);
                die();
            }
        }
        catch (Exception $error)
        {
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            $id=uniqid('wpvivid-');
            $log_file_name=$id.'_backup';
            $log=new WPvivid_Log_Ex_addon();
            $log->CreateLogFile($log_file_name,'no_folder','backup');
            $log->WriteLog($message,'notice');
            $log->CloseFile();
            WPvivid_error_log::create_error_log($log->log_file);
            error_log($message);
            echo json_encode($ret);
            die();
        }
        die();
    }

    public function auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            $backup_options = array();
            //select backup content

            if ($_POST['backup'] == 'core')
            {
                $backup_options['custom_dirs']['core_check'] = 1;
                $backup_options['custom_dirs']['database_check'] = 1;
            }
            else if ($_POST['backup'] == 'plugin')
            {
                $backup_options['include_plugins']=array();
                foreach ($_POST['plugins'] as $plugin)
                {
                    $backup_options['include_plugins'][] = dirname($plugin);
                }

                if(count($backup_options['include_plugins']) === 1)
                {
                    $backup_options['backup_prefix'] = current($backup_options['include_plugins']);
                    $backup_options['backup_prefix'] = str_replace('wpvivid-','',$backup_options['backup_prefix']);
                }

                $backup_options['custom_dirs']['database_check'] = 1;
                $backup_options['custom_dirs']['plugins_check'] = 1;
            }
            else if ($_POST['backup'] == 'themes')
            {
                foreach ($_POST['themes'] as $themes)
                {
                    $backup_options['include_themes'][] = $themes;
                }

                $backup_options['custom_dirs']['database_check'] = 1;
                $backup_options['custom_dirs']['themes_check'] = 1;
            }
            else if($_POST['backup'] == 'db')
            {
                $backup_options['custom_dirs']['database_check'] = 1;
            }
            //
            $auto_backup_before_update = get_option('wpvivid_auto_backup_before_update', array());
            if (isset($auto_backup_before_update['exclude-tables']) && !empty($auto_backup_before_update['exclude-tables'])) {
                $backup_options['custom_dirs']['exclude-tables'] = $auto_backup_before_update['exclude-tables'];
            }
            else {
                $backup_options['custom_dirs']['exclude-tables'] = array();
            }
            if (isset($auto_backup_before_update['include-tables']) && !empty($auto_backup_before_update['include-tables'])) {
                $backup_options['custom_dirs']['include-tables'] = $auto_backup_before_update['include-tables'];
            }
            else {
                $backup_options['custom_dirs']['include-tables'] = array();
            }
            //

            $rollback_remote = get_option('wpvivid_rollback_remote', 0);
            if ($rollback_remote)
            {
                $backup_options['type'] = 'Rollback';
                $backup_options['remote'] = 1;

                $remote_id = get_option('wpvivid_rollback_remote_id', 0);
                $remoteslist=WPvivid_Setting::get_all_remote_options();
                if(isset($remoteslist[$remote_id]))
                {
                    $backup_options['remote_options'][$remote_id] = $remoteslist[$remote_id];
                }

            } else {
                $backup_options['type'] = 'Rollback';
            }
            //Rollback
            $backup_options['backup_files']='custom';
            $ret=$this->pre_new_backup($backup_options);

            echo json_encode($ret);
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function auto_backup_now()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            if (!isset($_POST['task_id']) || empty($_POST['task_id']) || !is_string($_POST['task_id']))
            {
                $ret['result'] = 'failed';
                $ret['error'] = __('Error occurred while parsing the request data. Please try to run backup again.', 'wpvivid');
                echo json_encode($ret);
                die();
            }
            //$task_id = sanitize_key($_POST['task_id']);
            //global $wpvivid_backup_pro;
            //$wpvivid_backup_pro->func->flush($task_id);
            $this->backup_now();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function auto_list_tasks()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            if (isset($_POST['task_id']) || !empty($_POST['task_id']))
            {
                $task_id=$_POST['task_id'];
                $ret=$this->_list_tasks_ex($task_id);
            }
            else
            {
                $ret['backup']['result']='success';
                $ret['backup']['data']=array();
            }
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

    public function _list_tasks_ex($task_id)
    {
        if($this->wpvivid_check_litespeed_server() && $this->wpvivid_check_litespeed_cache_plugin())
        {
            wp_cache_delete('wpvivid_task_list', 'options');
        }

        $ret=array();
        $list_tasks=array();
        $task=WPvivid_taskmanager::get_task($task_id);
        if($task!==false)
        {
            $backup_task=new WPvivid_New_Backup_Task($task['id']);
            $info=$backup_task->get_backup_task_info();
            $list_tasks[$task['id']]=$info;
            $list_tasks[$task['id']]['progress_html'] = '<p>
                                            <span class="wpvivid-span-progress">
                                                <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width:' .$info['task_info']['backup_percent'] . '" ></span>
                                            </span>
                                        </p>
                                        <p>
                                            <span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span>
                                            <span>
                                            <span>' . $info['task_info']['descript'] . '</span>
                                        </span>
                                        </p>';
            $list_tasks[$task['id']]['progress_text']=$info['task_info']['progress_text'];
            $list_tasks[$task['id']]['progress_text2']=$info['task_info']['progress_text2'];
        }

        $ret['backup']['result']='success';
        $ret['backup']['data']=$list_tasks;
        return $ret;

    }

    public function get_backup_path($backup_item, $file_name)
    {
        $path = $backup_item->get_local_path() . $file_name;

        if (file_exists($path)) {
            return $path;
        }
        else{
            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR.$file_name;
        }
        return $path;
    }

    public function do_mail_report($task_id)
    {
        $task= new WPvivid_New_Backup_Task($task_id);
        $option=WPvivid_Setting::get_option('wpvivid_email_setting_addon');
        $tmp_email = array();
        if(!empty($option['send_to']))
        {
            foreach ($option['send_to'] as $email => $value)
            {
                $tmp_email[] = $email;
            }
            $option['send_to'] = $tmp_email;
        }

        if(empty($option))
        {
            return true;
        }

        if($option['email_enable'] == 0){
            return true;
        }

        if(empty($option['send_to']))
        {
            return true;
        }

        $status=$task->get_status();
        if($status['str']!=='error'&&$option['always']==false)
        {
            return true;
        }

        
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $subject = '';
        $subject =$this->set_mail_subject($task_id);

        $body = '';
        $body = $this->set_mail_body($task_id);

        $task_log=$this->task->task['options']['log_file_name'];

        if(isset($option['email_attach_log']))
        {
            if($option['email_attach_log'] == '1')
            {
                $attach_log = true;
            }
            else{
                $attach_log = false;
            }
        }
        else{
            $attach_log = true;
        }

        if($attach_log)
        {
            $wpvivid_log=new WPvivid_Log_Ex_addon();
            if($status['str']==='error')
            {
                $log_file_name= $wpvivid_log->GetSaveLogFolder().'error'.DIRECTORY_SEPARATOR.$task_log.'_log.txt';
            }
            else
            {
                $log_file_name= $wpvivid_log->GetSaveLogFolder().$task_log.'_log.txt';
            }
            $attachments[] = $log_file_name;
        }
        else{
            $attachments = array();
        }

        foreach ($option['send_to'] as $send_to)
        {
            if(wp_mail( $send_to, $subject, $body,$headers,$attachments)===false)
            {
                //
            }
        }

        return true;
    }

    public function set_mail_subject($task_id)
    {
        $task= new WPvivid_New_Backup_Task($task_id);
        $task_status=$task->get_status();
        if($task_status['str']!=='error')
        {
            $status='Succeeded';
        }
        else
        {
            $status='Failed';
        }

        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title'])){
            if($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title']){
                $wpvivid_use_mail_title = true;
            }
            else{
                $wpvivid_use_mail_title = false;
            }
        }
        else{
            $wpvivid_use_mail_title = true;
        }
        if($wpvivid_use_mail_title){
            global $wpvivid_backup_pro;
            $default_mail_title = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
            $mail_title = isset($general_setting['options']['wpvivid_email_setting_addon']['mail_title']) ? $general_setting['options']['wpvivid_email_setting_addon']['mail_title'] : $default_mail_title;
            $mail_title .= ': ';
        }
        else{
            $mail_title = '';
        }

        $offset=get_option('gmt_offset');
        $localtime=gmdate('m-d-Y H:i:s', $task->get_start_time()+$offset*60*60);
        $subject='['.$mail_title.'Backup '.$status.']'.$localtime.sprintf(' - By %s', apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin'));
        return $subject;
    }

    public function set_mail_body($task_id)
    {
        $task= new WPvivid_New_Backup_Task($task_id);
        $task_status=$task->get_status();
        if($task_status['str']!=='error')
        {
            $status='Succeeded';
        }
        else
        {
            $status='Failed. '.$task_status['error'];
        }

        $type=$this->task->task['type'];
        if($type === 'Cron')
        {
            $type = 'Cron-Schedule';
        }
        $offset=get_option('gmt_offset');
        $start_time=date("m-d-Y H:i:s",$task->get_start_time()+$offset*60*60);
        $end_time=date("m-d-Y H:i:s",time()+$offset*60*60);
        $running_time=($task->get_end_time()-$task->get_start_time()).'s';
        $remote_options=$task->get_remote_options();
        if($remote_options!==false)
        {
            //$remote_option=array_shift($remote_options);
            $remote_arr = array();
            foreach ($remote_options as $remote_id => $remote_value)
            {
                $remote_arr[]=apply_filters('wpvivid_storage_provider_tran', $remote_value['type']);
            }
            $remote = implode(", ", $remote_arr);
        }
        else
        {
            $remote='Localhost';
        }

        $jobs=$task->get_backup_jobs();
        $content='';

        foreach ($jobs as $index=>$job)
        {
            if($job['backup_type']=='backup_db')
            {
                $content .= 'Database, ';
            }
            else if($job['backup_type']=='backup_additional_db')
            {
                $content .= 'Additional Databases, ';
            }
            else if($job['backup_type']=='backup_themes')
            {
                $content .= 'Themes, ';
            }
            else if($job['backup_type']=='backup_plugin')
            {
                $content .= 'Plugins, ';
            }
            else if($job['backup_type']=='backup_mu_site_uploads')
            {
                $content .= 'Uploads, ';
            }
            else if($job['backup_type']=='backup_uploads')
            {
                $content .= 'Uploads, ';
            }
            else if($job['backup_type']=='backup_content')
            {
                $content .= 'WP-content, ';
            }
            else if($job['backup_type']=='backup_core')
            {
                $content .= 'WordPress Core, ';
            }
            else if($job['backup_type']=='backup_custom_other')
            {
                $content .= 'Non-wordpress Files/Folders, ';
            }

        }

        global $wpdb;
        $home_url = home_url();
        $db_home_url = home_url();
        $home_url_sql = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name = %s", 'home' ) );
        foreach ( $home_url_sql as $home ){
            $db_home_url = untrailingslashit($home->option_value);
        }
        if($home_url === $db_home_url)
        {
            $domain = $home_url;
        }
        else
        {
            $domain = $db_home_url;
        }
        $domain = strtolower($domain);

        if(apply_filters('wpvivid_show_dashboard_addons',true))
        {
            $logo_title='WPvivid.com';
        }
        else
        {
            $logo_title='';
        }

        if(apply_filters('wpvivid_white_label_email_report_enable_twitter', false))
        {
            $twitter_support_html = '<tr>
                                        <td style="padding-top:10px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p style="Margin-top:0px;margin-bottom:0px;font-size:13px;line-height:16px"><strong><a href="'.apply_filters('wpvivid_white_label_email_report_twitter_address', 'https://twitter.com/wpvividcom').'" style="text-decoration:none;color:#111111" target="_blank">24/7 Support: <u></u>Twitter<u></u></a></strong></p>
                                        </td>
                                     </tr>
                                     <tr>
                                        <td style="padding-top:0px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p class="m_764812426175198487customerinfo" style="Margin-top:5px;margin-bottom:0px;font-size:13px;line-height:16px">Or <u></u><a href="'.apply_filters('wpvivid_white_label_email_report_contact_us_address', 'https://wpvivid.com/contact-us').'">Email Us</a><u></u></p>
                                        </td>
                                     </tr>';
            $twitter_address_html = ' or <a href="'.apply_filters('wpvivid_white_label_email_report_twitter_address', 'https://twitter.com/wpvividcom').'">Twitter</a>';
        }
        else
        {
            $twitter_support_html = '<tr>
                                        <td style="padding-top:0px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p class="m_764812426175198487customerinfo" style="Margin-top:5px;margin-bottom:0px;font-size:13px;line-height:16px"><u></u><a href="'.apply_filters('wpvivid_white_label_email_report_contact_us_address', 'https://wpvivid.com/contact-us').'">Email Us</a><u></u></p>
                                        </td>
                                     </tr>';
            $twitter_address_html = '';
        }

        $body='
        <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td style="padding-bottom:20px">
                <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                    <table align="center" style="border-spacing:0;color:#111111;Margin:0 auto;width:100%;max-width:600px" bgcolor="#F5F7F8">
                        <tbody>
				        <tr>
                            <td bgcolor="#F5F7F8" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                <table width="73%" style="border-spacing:0;color:#111111" bgcolor="#F5F7F8">
                                    <tbody>
			                        <tr>
                                        <td style="padding-top:20px;padding-bottom:0px;padding-left:10px;padding-right:40px;width:100%;text-align:center;font-size:32px;color:#2ea3f2;line-height:32px;font-weight:bold;">
                                            <span><img src="'.apply_filters('wpvivid_white_label_email_report_logo_address', 'https://wpvivid.com/wp-content/uploads/2019/02/wpvivid-logo.png').'" title="'.$logo_title.'"></span>            
                                        </td>
                                    </tr>
                                    </tbody>
		                        </table>
                            </td>
                            <td width="100%" bgcolor="#F5F7F8" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                <table width="100%" style="border-spacing:0;color:#111111" bgcolor="#F5F7F8">
                                    <tbody>
                                    '.$twitter_support_html.'
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:center;font-size:32px;line-height:42px;font-weight:bold;">
                                                <span>Wordpress Backup Report</span>            
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>            
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"> </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td width="80" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="80" style="border-spacing:0;color:#111111;border-bottom-color:#ffcca8;border-bottom-width:2px;border-bottom-style:solid">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"></p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"> </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    You receive this email because you have enabled the email notification feature in '.apply_filters('wpvivid_white_label_display', 'WPvivid plugin').'. Backup Details:
                                                </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>   
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="background-color:#f5f7f8;padding-top:0;padding-right:0;padding-left:0;padding-bottom:0">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">		
                        <table bgcolor="#ffffff" width="100%" align="center" border="0" cellspacing="0" cellpadding="0" style="color:#111111;max-width:600px">
                            <tbody>
                            <tr>
                                <td bgcolor="#ffffff" align="left" style="padding-top:10px;padding-bottom:0;padding-right:40px;padding-left:40px;background-color:#ffffff">
                                    <table border="0" cellpadding="0" cellspacing="0" align="left" width="100%">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:10px;padding-right:0;padding-bottom:0;padding-left:20px">
                                                <table border="0" cellpadding="0" cellspacing="0" align="left">
                                                    <tbody>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Domain: </label><label>'.$domain.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup: </label><label>'.$status.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Type: </label><label>'.$type.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Start Time: </label><label>'.$start_time.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>End Time: </label><label>'.$end_time.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Running Time: </label><label>'.$running_time.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backed up to: </label><label>'.$remote.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Content: </label><label>'.$content.'</label></p>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>                     
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>     
          
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">             
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#757575">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    *'.apply_filters('wpvivid_white_label_display', 'WPvivid Backup plugin').' is a Wordpress plugin that it will help you back up your site to the leading cloud storage providers like Dropbox, Google Drive, Amazon S3, Microsoft OneDrive, FTP and SFTP.
                                                </p>
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    Plugin Page: <a href="'.apply_filters('wpvivid_white_label_email_report_plugin_page_address', 'https://wordpress.org/plugins/wpvivid-backuprestore/').'">'.apply_filters('wpvivid_white_label_email_report_plugin_page_address', 'https://wordpress.org/plugins/wpvivid-backuprestore/').'</a>
                                                </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>     
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                                <tr>
                                    <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                        <table width="100%" style="border-spacing:0;color:#111111">
                                            <tbody>
                                            <tr>
                                                <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                    <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"></p>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
                <tr>
                    <td bgcolor="#F5F7F8" style="background-color:#f5f7f8;padding-top:0;padding-right:0;padding-left:0;padding-bottom:0">
                        <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                            <table width="100%" align="center" border="0" cellspacing="0" cellpadding="0" style="color:#111111">
                                <tbody>
                                <tr>
                                    <td align="center" style="padding-top:40px;padding-bottom:0;padding-right:0px;padding-left:0px">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tbody>
                                            <tr>
                                                <td align="left" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/report-background.png" width="270" height="60" style="display:block;width:100%;max-width:270px;min-width:10px;height:60px" class="CToWUd">
                                                </td>
                                                <td width="60" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/female.png" width="60" height="60" style="display:block" class="CToWUd">
                                                </td>
                                                <td align="right" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/report-background.png" width="270" height="60" style="display:block;width:100%;max-width:270px;min-width:10px;height:60px" class="CToWUd">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>  
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <table bgcolor="#FFFFFF" width="100%" align="left" border="0" cellspacing="0" cellpadding="0" style="color:#111111">
                                <tbody>
                                <tr>
                                    <td bgcolor="#FFFFFF" align="left" style="padding-top:20px;padding-bottom:40px;padding-right:40px;padding-left:40px;background-color:#ffffff">     
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
                                            <tbody>
                                            <tr>
                                                <td align="center" style="padding-top:0px;padding-bottom:10px;padding-right:0;padding-left:0;text-align:center;font-size:18px;line-height:28px;font-weight:bold;">
                                                    <span>We\'re here to help you do your thing.</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding-top:0px;padding-bottom:0px;padding-right:0;padding-left:0;text-align:center">
                                                    <p style="text-align:center;margin-top:0px;margin-bottom:0px;gdsherpa-regular;;font-size:14px;line-height:24px">
                                                        <a href="'.apply_filters('wpvivid_white_label_email_report_contact_us_address', 'https://wpvivid.com/contact-us').'">Contact Us</a>'.$twitter_address_html.'
                                                    </p>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>        
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tbody>
                                    <tr>
                                        <td valign="top" style="font-size:0px;line-height:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <img src="https://wpvivid.com/wp-content/uploads/2019/03/unnamed6.jpg" width="600" height="5" style="display:block;width:100%;max-width:600px;min-width:10px;height:5px">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>        
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#F5F7F8" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#f5f7f8;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px">&nbsp;</p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>   
                    </div>
                </td>
            </tr>
            </tbody>
        </table>';
        return $body;
    }

    public function get_schedule_backup_data($options,$data)
    {
        $options['backup_files']='custom';
        if(isset($data['custom_dirs']))
        {
            $options['custom_dirs']=$data['custom_dirs'];
        }

        if(isset($data['exclude_files']))
        {
            $options['exclude_files']=$data['exclude_files'];
        }

        if(isset($data['exclude_file_type']))
        {
            $options['exclude_file_type']=$data['exclude_file_type'];
        }
        return $options;
    }

    public function backup_cancel()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        $json = $this->_backup_cancel();
        echo json_encode($json);
        die();
    }

    public function _backup_cancel()
    {
        try
        {
            $tasks = WPvivid_taskmanager::get_tasks();
            $no_responds=false;
            $task_id='';
            foreach ($tasks as $task)
            {
                $task_id = $task['id'];
                $backup_task=new WPvivid_New_Backup_Task($task['id']);
                $status=$backup_task->get_status();

                $file_name=$backup_task->task['options']['file_prefix'];
                $path=$backup_task->task['options']['dir'];
                $file =$path. DIRECTORY_SEPARATOR . $file_name . '_cancel';
                touch($file);

                $last_active_time=time()-$status['run_time'];
                if($last_active_time>180)
                {
                    $no_responds=true;
                }

                $timestamp = wp_next_scheduled('wpvivid_task_monitor_event_ex', array($task_id));

                if ($timestamp === false)
                {
                    $this->add_monitor_event($task_id);
                }
            }

            if($no_responds)
            {
                $ret['result'] = 'success';
                $ret['no_response'] = true;
                $ret['task_id'] = $task_id;
                $ret['msg'] = __('The backup is not responding for a while, do you want to force cancel it?', 'wpvivid-backuprestore');
            }
            else
            {
                $ret['result'] = 'success';
                $ret['no_response'] = false;
                $ret['task_id'] = $task_id;
                $ret['msg'] = __('The backup will be canceled after backing up the current chunk ends.', 'wpvivid-backuprestore');
            }

        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('result'=>'failed','error'=>$message);
        }

        return $ret;
    }

    public function shutdown_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');

        $task_id = sanitize_key($_POST['task_id']);
        $backup_task=new WPvivid_New_Backup_Task($task_id);
        if($backup_task->check_cancel_backup())
        {
            $ret['result'] = 'success';
        }
        else
        {
            $ret['result'] = 'failed';
        }

        echo json_encode($ret);
        die();
    }

    public function wpvivid_check_litespeed_server()
    {
        $litespeed=false;
        if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] )
        {
            $litespeed=true;
        }
        elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0 ) {
            $litespeed=true;
        }
        elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
            $litespeed=true;
        }

        return $litespeed;
    }

    public function wpvivid_check_litespeed_cache_plugin()
    {
        $litespeed_cache_plugin=false;
        if(!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $litespeed_cache_slug='litespeed-cache/litespeed-cache.php';
        if (is_multisite())
        {
            $active_plugins = array();
            //network active
            $mu_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
            if(!empty($mu_active_plugins)){
                foreach ($mu_active_plugins as $plugin_name => $data){
                    $active_plugins[] = $plugin_name;
                }
            }
            $plugins=get_mu_plugins();
            if(count($plugins) == 0 || !isset($plugins[$litespeed_cache_slug])){
                $plugins=get_plugins();
            }
        }
        else
        {
            $active_plugins = get_option('active_plugins');
            $plugins=get_plugins();
        }

        if(!empty($plugins))
        {
            if(isset($plugins[$litespeed_cache_slug]))
            {
                if(in_array($litespeed_cache_slug, $active_plugins))
                {
                    $litespeed_cache_plugin=true;
                }
                else
                {
                    $litespeed_cache_plugin=false;
                }
            }
            else
            {
                $litespeed_cache_plugin=false;
            }
        }
        else
        {
            $litespeed_cache_plugin=false;
        }

        return $litespeed_cache_plugin;
    }
}