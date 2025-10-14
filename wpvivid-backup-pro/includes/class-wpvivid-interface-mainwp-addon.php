<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR')){
    die;
}

class WPvivid_Interface_MainWP_addon
{
    private $database_connect;
    private $incremental_database_connect;

    public function __construct(){
        $this->load_wpvivid_mainwp_sync_filter();
        $this->load_wpvivid_mainwp_upgrade_filter();
        $this->load_wpvivid_mainwp_backup_filter();
        $this->load_wpvivid_mainwp_backup_restore_filter();
        $this->load_wpvivid_mainwp_schedule_filter();
        $this->load_wpvivid_mainwp_remote_filter();
        $this->load_wpvivid_mainwp_setting_filter();
        $this->load_wpvivid_mainwp_report_filter();
        $this->load_wpvivid_mainwp_menu_filter();
        $this->load_wpvivid_mainwp_incremental_backup_filter();
        $this->load_wpvivid_mainwp_white_label_filter();

        $this->load_wpvivid_mainwp_ajax();
        $this->load_wpvivid_rollback_filter();
    }

    public function load_wpvivid_rollback_filter()
    {
        add_filter('wpvivid_achieve_rollback_remote_addon_mainwp', array($this, 'wpvivid_achieve_rollback_remote_addon_mainwp'));
    }

    public function wpvivid_achieve_rollback_remote_addon_mainwp()
    {
        $wpvivid_remote_list = WPvivid_Setting::get_all_remote_options();
        $ret['wpvivid_remote_list'] = $wpvivid_remote_list;
        return $ret;
    }

    public function load_wpvivid_mainwp_ajax()
    {
        //download backup by mainwp
        add_action('wp_ajax_wpvivid_download_backup_addon_mainwp', array($this, 'download_backup_addon_mainwp'));
    }

    public function load_wpvivid_mainwp_sync_filter(){
        add_filter('wpvivid_get_time_zone_addon_mainwp', array($this, 'wpvivid_get_time_zone_addon_mainwp'));
        add_filter('wpvivid_get_backup_custom_setting_mainwp', array($this, 'wpvivid_get_backup_custom_setting_mainwp'));
        add_filter('wpvivid_get_staging_setting_mainwp', array($this, 'wpvivid_get_staging_setting_mainwp'));
        add_filter('wpvivid_get_menu_capability_mainwp', array($this, 'wpvivid_get_menu_capability_mainwp'));
        add_filter('wpvivid_get_white_label_mainwp', array($this, 'wpvivid_get_white_label_mainwp'));
        add_filter('wpvivid_handle_mainwp_action', array($this, 'handle_mainwp_action'), 11, 2);
    }

    public function handle_mainwp_action( $ret, $data )
    {
        $action = sanitize_text_field($data['mwp_action']);
        if (has_filter($action)) {
            $ret = apply_filters($action, $data);
        } else {
            $ret['result'] = WPVIVID_PRO_FAILED;
            $ret['error'] = 'Unknown function';
        }
        return $ret;
    }

    public function load_wpvivid_mainwp_upgrade_filter(){
    }

    public function load_wpvivid_mainwp_backup_filter(){
        add_filter('wpvivid_get_database_tables_addon_mainwp', array($this, 'wpvivid_get_database_tables_addon_mainwp'));
        add_filter('wpvivid_get_themes_plugins_addon_mainwp', array($this, 'wpvivid_get_themes_plugins_addon_mainwp'));
        add_filter('wpvivid_get_uploads_tree_data_addon_mainwp', array($this, 'wpvivid_get_uploads_tree_data_addon_mainwp'));
        add_filter('wpvivid_get_content_tree_data_addon_mainwp', array($this, 'wpvivid_get_content_tree_data_addon_mainwp'));
        add_filter('wpvivid_get_content_tree_data_ex_addon_mainwp', array($this, 'wpvivid_get_content_tree_data_ex_addon_mainwp'));
        add_filter('wpvivid_get_custom_tree_data_ex_addon_mainwp', array($this, 'wpvivid_get_custom_tree_data_ex_addon_mainwp'));
        add_filter('wpvivid_get_additional_folder_tree_data_addon_mainwp', array($this, 'wpvivid_get_additional_folder_tree_data_addon_mainwp'));
        add_filter('wpvivid_connect_additional_database_addon_mainwp', array($this, 'wpvivid_connect_additional_database_addon_mainwp'));
        add_filter('wpvivid_add_additional_database_addon_mainwp', array($this, 'wpvivid_add_additional_database_addon_mainwp'));
        add_filter('wpvivid_remove_additional_database_addon_mainwp', array($this, 'wpvivid_remove_additional_database_addon_mainwp'));
        add_filter('wpvivid_get_database_by_filter_mainwp', array($this, 'wpvivid_get_database_by_filter_mainwp'));
        add_filter('wpvivid_update_backup_exclude_extension_addon_mainwp', array($this, 'wpvivid_update_backup_exclude_extension_addon_mainwp'));
        add_filter('wpvivid_get_default_remote_addon_mainwp', array($this, 'wpvivid_get_default_remote_addon_mainwp'));
        add_filter('wpvivid_get_remote_storage_addon_mainwp', array($this, 'wpvivid_get_remote_storage_addon_mainwp'));
        add_filter('wpvivid_prepare_backup_addon_mainwp', array($this, 'wpvivid_prepare_backup_addon_mainwp'));
        add_filter('wpvivid_backup_now_addon_mainwp', array($this, 'wpvivid_backup_now_addon_mainwp'));
        add_filter('wpvivid_list_tasks_addon_mainwp', array($this, 'wpvivid_list_tasks_addon_mainwp'));
        add_filter('wpvivid_delete_ready_task_addon_mainwp', array($this, 'wpvivid_delete_ready_task_addon_mainwp'));
        add_filter('wpvivid_backup_cancel_addon_mainwp', array($this, 'wpvivid_backup_cancel_addon_mainwp'));
        add_filter('wpvivid_prepare_backup_addon_mainwp_ex', array($this, 'wpvivid_prepare_backup_addon_mainwp_ex'));
        add_filter('wpvivid_backup_now_addon_mainwp_ex', array($this, 'wpvivid_backup_now_addon_mainwp_ex'));
        add_filter('wpvivid_list_tasks_addon_mainwp_ex', array($this, 'wpvivid_list_tasks_addon_mainwp_ex'));
    }

    public function load_wpvivid_mainwp_backup_restore_filter(){
        add_filter('wpvivid_achieve_local_backup_addon_mainwp', array($this, 'wpvivid_achieve_local_backup_addon_mainwp'));
        add_filter('wpvivid_achieve_remote_backup_info_addon_mainwp', array($this, 'wpvivid_achieve_remote_backup_info_addon_mainwp'));
        add_filter('wpvivid_achieve_remote_backup_addon_mainwp', array($this, 'wpvivid_achieve_remote_backup_addon_mainwp'));
        add_filter('wpvivid_achieve_backup_list_addon_mainwp', array($this, 'wpvivid_achieve_backup_list_addon_mainwp'));
        add_filter('wpvivid_scan_remote_backup_addon_mainwp', array($this, 'wpvivid_scan_remote_backup_addon_mainwp'));
        add_filter('wpvivid_scan_remote_backup_continue_addon_mainwp', array($this, 'wpvivid_scan_remote_backup_continue_addon_mainwp'));
        add_filter('wpvivid_delete_backup_ex_addon_mainwp', array($this, 'wpvivid_delete_backup_ex_addon_mainwp'));
        add_filter('wpvivid_delete_backup_array_ex_addon_mainwp', array($this, 'wpvivid_delete_backup_array_ex_addon_mainwp'));
        add_filter('wpvivid_set_security_lock_ex_addon_mainwp', array($this, 'wpvivid_set_security_lock_ex_addon_mainwp'));
        add_filter('wpvivid_new_init_download_page_addon_mainwp', array($this, 'wpvivid_new_init_download_page_addon_mainwp'));
        add_filter('wpvivid_new_prepare_download_backup_addon_mainwp', array($this, 'wpvivid_new_prepare_download_backup_addon_mainwp'));
        add_filter('wpvivid_new_get_download_progress_addon_mainwp', array($this, 'wpvivid_new_get_download_progress_addon_mainwp'));
        add_filter('wpvivid_set_security_lock_addon_mainwp', array($this, 'wpvivid_set_security_lock_addon_mainwp'));
        add_filter('wpvivid_set_remote_security_lock_addon_mainwp', array($this, 'wpvivid_set_remote_security_lock_addon_mainwp'));
        add_filter('wpvivid_delete_local_backup_addon_mainwp', array($this, 'wpvivid_delete_local_backup_addon_mainwp'));
        add_filter('wpvivid_delete_local_backup_array_addon_mainwp', array($this, 'wpvivid_delete_local_backup_array_addon_mainwp'));
        add_filter('wpvivid_delete_remote_backup_addon_mainwp', array($this, 'wpvivid_delete_remote_backup_addon_mainwp'));
        add_filter('wpvivid_delete_remote_backup_array_addon_mainwp', array($this, 'wpvivid_delete_remote_backup_array_addon_mainwp'));
        add_filter('wpvivid_view_log_addon_mainwp', array($this, 'wpvivid_view_log_addon_mainwp'));
        add_filter('wpvivid_init_download_page_addon_mainwp', array($this, 'wpvivid_init_download_page_addon_mainwp'));
        add_filter('wpvivid_prepare_download_backup_addon_mainwp', array($this, 'wpvivid_prepare_download_backup_addon_mainwp'));
        add_filter('wpvivid_get_download_progress_addon_mainwp', array($this, 'wpvivid_get_download_progress_addon_mainwp'));
        add_filter('wpvivid_rescan_local_folder_addon_mainwp', array($this, 'wpvivid_rescan_local_folder_addon_mainwp'));
    }

    public function load_wpvivid_mainwp_schedule_filter(){
        add_filter('wpvivid_get_schedules_addon_mainwp', array($this, 'wpvivid_get_schedules_addon_mainwp'));
        add_filter('wpvivid_create_schedule_addon_mainwp', array($this, 'wpvivid_create_schedule_addon_mainwp'));
        add_filter('wpvivid_update_schedule_addon_mainwp', array($this, 'wpvivid_update_schedule_addon_mainwp'));
        add_filter('wpvivid_delete_schedule_addon_mainwp', array($this, 'wpvivid_delete_schedule_addon_mainwp'));
        add_filter('wpvivid_save_schedule_status_addon_mainwp', array($this, 'wpvivid_save_schedule_status_addon_mainwp'));
        add_filter('wpvivid_sync_schedule_addon_mainwp', array($this, 'wpvivid_sync_schedule_addon_mainwp'));
    }

    public function load_wpvivid_mainwp_remote_filter(){
        add_filter('wpvivid_sync_remote_storage_addon_mainwp', array($this, 'wpvivid_sync_remote_storage_addon_mainwp'));
    }

    public function load_wpvivid_mainwp_setting_filter(){
        add_filter('wpvivid_set_general_setting_addon_mainwp', array($this, 'wpvivid_set_general_setting_addon_mainwp'));
    }

    public function load_wpvivid_mainwp_report_filter(){
        //add_filter('wpvivid_archieve_report_addon_mainwp', array($this, 'wpvivid_archieve_report_addon_mainwp'));
        add_filter('wpvivid_set_backup_report_addon_mainwp', array($this, 'wpvivid_set_backup_report_addon_mainwp'));
        add_filter('wpvivid_get_backup_report_addon_mainwp', array($this , 'wpvivid_get_backup_report_addon_mainwp'));
    }

    public function load_wpvivid_mainwp_menu_filter(){
        add_filter('wpvivid_set_menu_capability_addon_mainwp', array($this, 'wpvivid_set_menu_capability_addon_mainwp'));
        add_filter('wpvivid_get_menu_capability_addon', array($this, 'wpvivid_get_menu_capability_addon'));
    }

    public function load_wpvivid_mainwp_incremental_backup_filter(){
        add_filter('wpvivid_get_incremental_backup_mainwp', array($this, 'wpvivid_get_incremental_backup_mainwp'));
        add_filter('wpvivid_refresh_incremental_table_addon_mainwp', array($this, 'wpvivid_refresh_incremental_table_addon_mainwp'));
        add_filter('wpvivid_enable_incremental_backup_mainwp', array($this, 'wpvivid_enable_incremental_backup_mainwp'));
        add_filter('wpvivid_save_incremental_backup_schedule_mainwp', array($this, 'wpvivid_save_incremental_backup_schedule_mainwp'));
        add_filter('wpvivid_set_incremental_backup_schedule_mainwp', array($this, 'wpvivid_set_incremental_backup_schedule_mainwp'));
        add_filter('wpvivid_update_incremental_backup_exclude_extension_addon_mainwp', array($this, 'wpvivid_update_incremental_backup_exclude_extension_addon_mainwp'));
        add_filter('wpvivid_incremental_connect_additional_database_addon_mainwp', array($this, 'wpvivid_incremental_connect_additional_database_addon_mainwp'));
        add_filter('wpvivid_incremental_add_additional_database_addon_mainwp', array($this, 'wpvivid_incremental_add_additional_database_addon_mainwp'));
        add_filter('wpvivid_incremental_remove_additional_database_addon_mainwp', array($this, 'wpvivid_incremental_remove_additional_database_addon_mainwp'));
        add_filter('wpvivid_achieve_incremental_child_path_addon_mainwp', array($this, 'wpvivid_achieve_incremental_child_path_addon_mainwp'));
        add_filter('wpvivid_archieve_incremental_remote_folder_list_addon_mainwp', array($this, 'wpvivid_archieve_incremental_remote_folder_list_addon_mainwp'));
        add_filter('wpvivid_sync_incremental_schedule_addon_mainwp', array($this, 'wpvivid_sync_incremental_schedule_addon_mainwp'));
    }

    public function load_wpvivid_mainwp_white_label_filter(){
        add_filter('wpvivid_set_white_label_setting_addon_mainwp', array($this, 'wpvivid_set_white_label_setting_addon_mainwp'));
    }

    /***** wpvivid mainwp sync filter *****/
    public function wpvivid_get_time_zone_addon_mainwp($data){
        $time_zone = get_option('gmt_offset');
        return $time_zone;
    }

    public function wpvivid_get_backup_custom_setting_mainwp($data){
        $options = get_option('wpvivid_custom_backup_history', $data);
        return $options;
    }

    public function wpvivid_get_staging_setting_mainwp($data){
        $options = get_option('wpvivid_staging_options', $data);
        return $options;
    }

    public function wpvivid_get_menu_capability_mainwp($data){
        $menu_cap = get_option('wpvivid_menu_cap_mainwp', array());
        return $menu_cap;
    }

    public function wpvivid_get_white_label_mainwp($data){
        $white_label_setting = get_option('white_label_setting', array());
        return $white_label_setting;
    }

    /***** wpvivid mainwp backup filter *****/
    public function wpvivid_get_database_tables_addon_mainwp($data){
        global $wpdb;
        $exclude = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
        if (empty($exclude)) {
            $exclude = array();
        }

        if (is_multisite() && !defined('MULTISITE')) {
            $prefix = $wpdb->base_prefix;
        } else {
            $prefix = $wpdb->get_blog_prefix(0);
        }

        $default_table = array($prefix.'commentmeta', $prefix.'comments', $prefix.'links', $prefix.'options', $prefix.'postmeta', $prefix.'posts', $prefix.'term_relationships',
            $prefix.'term_taxonomy', $prefix.'termmeta', $prefix.'terms', $prefix.'usermeta', $prefix.'users');

        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);

        if (is_null($tables)) {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
            return $ret;
        }

        $tables_info = array();
        $base_table_array = array();
        $other_table_array = array();
        foreach ($tables as $row) {
            if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                continue;
            }

            $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
            $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);

            $checked = 'checked';
            if (!empty($exclude['database_option']['exclude_table_list'])) {
                if (in_array($row["Name"], $exclude['database_option']['exclude_table_list'])) {
                    $checked = '';
                }
            }

            if (in_array($row["Name"], $default_table)) {
                $base_table_array[] = array('table_name' => $row["Name"], 'table_row' => $row["Rows"], 'table_size' => $tables_info[$row["Name"]]["Data_length"], 'table_check' => $checked);
            }
            else {
                $other_table_array[] = array('table_name' => $row["Name"], 'table_row' => $row["Rows"], 'table_size' => $tables_info[$row["Name"]]["Data_length"], 'table_check' => $checked);
            }
        }

        $ret['result'] = 'success';
        $ret['base_tables'] = $base_table_array;
        $ret['other_tables'] = $other_table_array;
        return $ret;
    }

    public function wpvivid_get_themes_plugins_addon_mainwp($data){
        $custom_interface_addon = new WPvivid_Custom_Interface_addon();
        $exclude = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
        if(empty($exclude)){
            $exclude = array();
        }

        $themes_path = get_theme_root();
        $current_active_theme = get_stylesheet();
        $themes_info = array();
        $themes_array = array();

        $themes=wp_get_themes();
        foreach ($themes as $theme) {
            $file=$theme->get_stylesheet();
            $themes_info[$file] = $custom_interface_addon->get_theme_plugin_info($themes_path . DIRECTORY_SEPARATOR . $file);
            if($file===$current_active_theme) {
                $themes_info[$file]['active']=1;
            }
            else {
                $themes_info[$file]['active']=0;
            }
        }
        uasort ($themes_info,function($a, $b) {
            if($a['active']<$b['active']) {
                return 1;
            }
            if($a['active']>$b['active']) {
                return -1;
            }
            else {
                return 0;
            }
        });
        foreach ($themes_info as $file=>$info) {
            $checked = '';
            if($info['active']==1) {
                $checked = 'checked';
            }

            if (!empty($exclude['themes_option']['exclude_themes_list'])) {
                if (!in_array($file, $exclude['themes_option']['exclude_themes_list'])) {
                    $checked = 'checked';
                }
            }
            $themes_array[] = array('theme_name' => $file, 'theme_size' => size_format($info["size"], 2), 'theme_check' => $checked);
        }


        $path = WP_PLUGIN_DIR;
        $active_plugins = get_option('active_plugins');
        $plugin_info = array();
        $plugins_array = array();

        if(!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $plugins=get_plugins();
        foreach ($plugins as $key=>$plugin) {
            $slug=dirname($key);
            if($slug=='.'||$slug=='wpvivid-backuprestore'||$slug=='wpvivid-backup-pro')
                continue;
            $plugin_info[$slug]= $custom_interface_addon->get_theme_plugin_info($path . DIRECTORY_SEPARATOR . $slug);
            $plugin_info[$slug]['Name']=$plugin['Name'];
            $plugin_info[$slug]['slug']=$slug;

            if(in_array($key, $active_plugins)) {
                $plugin_info[$slug]['active']=1;
            }
            else{
                $plugin_info[$slug]['active']=0;
            }
        }

        uasort ($plugin_info,function($a, $b) {
            if($a['active']<$b['active']) {
                return 1;
            }
            if($a['active']>$b['active']) {
                return -1;
            }
            else {
                return 0;
            }
        });

        foreach ($plugin_info as $slug=>$info) {
            $checked = '';
            if($info['active']==1) {
                $checked = 'checked';
            }

            if (!empty($exclude['plugins_option']['exclude_plugins_list'])) {
                if (in_array($slug, $exclude['plugins_option']['exclude_plugins_list'])) {
                    $checked = '';
                }
            }

            $plugins_array[] = array('plugin_slug_name' => $info['slug'], 'plugin_display_name' => $info['Name'], 'plugin_size' => size_format($info["size"], 2), 'plugin_check' => $checked);
        }
        $ret['result'] = 'success';
        $ret['themes'] = $themes_array;
        $ret['plugins'] = $plugins_array;
        return $ret;
    }

    public function wpvivid_get_uploads_tree_data_addon_mainwp($data){
        $node_array = array();
        if($data['tree_node']['node']['id'] == '#') {
            $upload_dir = wp_upload_dir();
            $path = $upload_dir['basedir'];
            $path = str_replace('\\','/',$path);
            $path = $path.'/';

            $node_array[] = array(
                'text' => basename($path),
                'children' => true,
                'id' => $path,
                'icon' => 'jstree-folder',
                'state' => array(
                    'opened' => true
                )
            );
        }
        else{
            $path = $data['tree_node']['node']['id'];
        }

        $backup_dir=get_option('wpvivid_local_setting');
        if(!isset($backup_dir['path']))
        {
            $backup_dir['path']='wpvividbackups';
        }

        $path = trailingslashit( str_replace( '\\', '/', realpath ( $path ) ) );
        if ($dh = opendir($path)) {
            while(substr($path, -1) == '/'){
                $path = rtrim($path, '/');
            }
            $skip_paths = array(".", "..");

            while (($value = readdir($dh)) !== false)
            {
                trailingslashit( str_replace( '\\', '/', $value ) );
                if (!in_array($value, $skip_paths))
                {
                    $custom_dir = WP_CONTENT_DIR.'/'.$backup_dir['path'];
                    $custom_dir = str_replace('\\','/',$custom_dir);

                    $themes_dir = get_theme_root();
                    $themes_dir = trailingslashit( str_replace( '\\', '/', $themes_dir ) );
                    $themes_dir = rtrim($themes_dir, '/');

                    $plugin_dir = WP_PLUGIN_DIR;
                    $plugin_dir = trailingslashit( str_replace( '\\', '/', $plugin_dir ) );
                    $plugin_dir = rtrim($plugin_dir, '/');

                    $upload_dir = wp_upload_dir();
                    $upload_dir['basedir'] = trailingslashit( str_replace( '\\', '/', $upload_dir['basedir'] ) );
                    $upload_dir['basedir'] = rtrim($upload_dir['basedir'], '/');

                    $exclude_dir = array($themes_dir, $plugin_dir, $upload_dir['basedir'],$custom_dir);
                    if (is_dir($path. '/' . $value))
                    {
                        if(!in_array($path. '/' . $value, $exclude_dir))
                        {
                            $node['text'] = $value;
                            $node['children'] = true;
                            $node['id'] = $path . '/' . $value;
                            $node['icon'] = 'jstree-folder';
                            $node_array[] = $node;
                        }
                    }
                }
            }
        }
        $ret['nodes'] = $node_array;
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_get_content_tree_data_addon_mainwp($data){
        $node_array = array();
        if($data['tree_node']['node']['id'] == '#') {
            $content_dir = WP_CONTENT_DIR;
            $path = str_replace('\\','/',$content_dir);
            $path = $path.'/';

            $node_array[] = array(
                'text' => basename($path),
                'children' => true,
                'id' => $path,
                'icon' => 'jstree-folder',
                'state' => array(
                    'opened' => true
                )
            );
        }
        else{
            $path = $data['tree_node']['node']['id'];
        }

        $backup_dir=get_option('wpvivid_local_setting');
        if(!isset($backup_dir['path']))
        {
            $backup_dir['path']='wpvividbackups';
        }

        $path = trailingslashit( str_replace( '\\', '/', realpath ( $path ) ) );
        if ($dh = opendir($path)) {
            while(substr($path, -1) == '/'){
                $path = rtrim($path, '/');
            }
            $skip_paths = array(".", "..");

            while (($value = readdir($dh)) !== false)
            {
                trailingslashit( str_replace( '\\', '/', $value ) );
                if (!in_array($value, $skip_paths))
                {
                    $custom_dir = WP_CONTENT_DIR.'/'.$backup_dir['path'];
                    $custom_dir = str_replace('\\','/',$custom_dir);

                    $themes_dir = get_theme_root();
                    $themes_dir = trailingslashit( str_replace( '\\', '/', $themes_dir ) );
                    $themes_dir = rtrim($themes_dir, '/');

                    $plugin_dir = WP_PLUGIN_DIR;
                    $plugin_dir = trailingslashit( str_replace( '\\', '/', $plugin_dir ) );
                    $plugin_dir = rtrim($plugin_dir, '/');

                    $upload_dir = wp_upload_dir();
                    $upload_dir['basedir'] = trailingslashit( str_replace( '\\', '/', $upload_dir['basedir'] ) );
                    $upload_dir['basedir'] = rtrim($upload_dir['basedir'], '/');

                    $exclude_dir = array($themes_dir, $plugin_dir, $upload_dir['basedir'],$custom_dir);
                    if (is_dir($path. '/' . $value))
                    {
                        if(!in_array($path. '/' . $value, $exclude_dir))
                        {
                            $node['text'] = $value;
                            $node['children'] = true;
                            $node['id'] = $path . '/' . $value;
                            $node['icon'] = 'jstree-folder';
                            $node_array[] = $node;
                        }
                    }
                }
            }
        }
        $ret['nodes'] = $node_array;
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_get_content_tree_data_ex_addon_mainwp($data)
    {
        $node_array = array();
        if($data['tree_node']['node']['id'] == '#') {
            $content_dir = WP_CONTENT_DIR;
            $path = str_replace('\\','/',$content_dir);
            $path = $path.'/';

            $node_array[] = array(
                'text' => basename($path),
                'children' => true,
                'id' => $path,
                'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer',
                'state' => array(
                    'opened' => true
                )
            );
        }
        else{
            $path = $data['tree_node']['node']['id'];
        }

        //
        $path = trailingslashit(str_replace('\\', '/', realpath($path)));

        if ($dh = opendir($path)) {
            while (substr($path, -1) == '/') {
                $path = rtrim($path, '/');
            }
            $skip_paths = array(".", "..");

            while (($value = readdir($dh)) !== false) {
                trailingslashit(str_replace('\\', '/', $value));
                if (!in_array($value, $skip_paths)) {
                    $exclude_dir = array();
                    if (is_dir($path . '/' . $value)) {
                        if (!in_array($path . '/' . $value, $exclude_dir)) {
                            $node['text'] = $value;
                            $node['children'] = true;
                            $node['id'] = $path . '/' . $value;
                            $node['icon'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                            $node_array[] = $node;
                        }
                    }
                    else{
                        $node['text'] = $value;
                        $node['children'] = true;
                        $node['id'] = $path . '/' . $value;
                        $node['icon'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        $node_array[] = $node;
                    }
                }
            }
        }

        $ret['nodes'] = $node_array;
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_get_custom_tree_data_ex_addon_mainwp($data)
    {
        $node_array = array();
        if($data['tree_node']['node']['id'] == '#') {
            $path = ABSPATH;

            if (!empty($_POST['tree_node']['path'])) {
                $path = $_POST['tree_node']['path'];
            }

            if (isset($_POST['select_prev_dir']) && $_POST['select_prev_dir'] === '1') {
                $path = dirname($path);
            }

            $node_array[] = array(
                'text' => basename($path),
                'children' => true,
                'id' => $path,
                'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer',
                'state' => array(
                    'opened' => true
                )
            );
        }
        else{
            $path = $data['tree_node']['node']['id'];
        }

        $backup_dir=get_option('wpvivid_local_setting');
        if(!isset($backup_dir['path']))
        {
            $backup_dir['path']='wpvividbackups';
        }

        $path = trailingslashit( str_replace( '\\', '/', realpath ( $path ) ) );
        if ($dh = opendir($path)) {
            while(substr($path, -1) == '/'){
                $path = rtrim($path, '/');
            }
            $skip_paths = array(".", "..");
            $file_array = array();
            while (($value = readdir($dh)) !== false)
            {
                trailingslashit( str_replace( '\\', '/', $value ) );
                if (!in_array($value, $skip_paths))
                {
                    if (is_dir($path . '/' . $value)) {
                        $wp_admin_path = ABSPATH . 'wp-admin';
                        $wp_admin_path = str_replace('\\', '/', $wp_admin_path);

                        $wp_include_path = ABSPATH . 'wp-includes';
                        $wp_include_path = str_replace('\\', '/', $wp_include_path);

                        $content_dir = WP_CONTENT_DIR;
                        $content_dir = str_replace('\\', '/', $content_dir);
                        $content_dir = rtrim($content_dir, '/');

                        $lotties_dir = ABSPATH . 'lotties';
                        $lotties_dir = str_replace('\\', '/', $lotties_dir);

                        $exclude_dir = array($wp_admin_path, $wp_include_path, $content_dir, $lotties_dir);
                        if (!in_array($path . '/' . $value, $exclude_dir)) {
                            $node_array[] = array(
                                'text' => $value,
                                'children' => true,
                                'id' => $path . '/' . $value,
                                'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'
                            );
                        }

                    }
                    else {

                        $wp_admin_path = ABSPATH;
                        $wp_admin_path = str_replace('\\', '/', $wp_admin_path);
                        $wp_admin_path = rtrim($wp_admin_path, '/');
                        $skip_path = rtrim($path, '/');

                        if ($wp_admin_path == $skip_path) {
                            continue;
                        }
                        $file_array[] = array(
                            'text' => $value,
                            'children' => false,
                            'id' => $path . '/' . $value,
                            'type' => 'file',
                            'icon' => 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer'
                        );
                    }
                }
            }
            $node_array = array_merge($node_array, $file_array);
        }
        $ret['nodes'] = $node_array;
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_get_additional_folder_tree_data_addon_mainwp($data){
        $node_array = array();
        if($data['tree_node']['node']['id'] == '#') {
            if(!function_exists('get_home_path'))
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            $path = str_replace('\\','/',get_home_path());

            $node_array[] = array(
                'text' => basename($path),
                'children' => true,
                'id' => $path,
                'icon' => 'jstree-folder',
                'state' => array(
                    'opened' => true
                )
            );
        }
        else{
            $path = $data['tree_node']['node']['id'];
        }

        $path = trailingslashit( str_replace( '\\', '/', realpath ( $path ) ) );
        if ($dh = opendir($path))
        {
            while(substr($path, -1) == '/'){
                $path = rtrim($path, '/');
            }

            $skip_paths = array(".", "..");

            $file_array = array();

            while (($value = readdir($dh)) !== false) {
                trailingslashit( str_replace( '\\', '/', $value ) );

                if (!in_array($value, $skip_paths)) {
                    if (is_dir($path . '/' . $value))
                    {
                        $wp_admin_path = ABSPATH.'wp-admin';
                        $wp_admin_path = str_replace('\\','/',$wp_admin_path);

                        $wp_include_path = ABSPATH.'wp-includes';
                        $wp_include_path = str_replace('\\','/',$wp_include_path);

                        $content_dir = WP_CONTENT_DIR;
                        $content_dir = str_replace('\\','/',$content_dir);
                        $content_dir = rtrim($content_dir, '/');

                        $lotties_dir = ABSPATH . 'lotties';
                        $lotties_dir = str_replace('\\', '/', $lotties_dir);

                        $exclude_dir = array($wp_admin_path, $wp_include_path, $content_dir, $lotties_dir);
                        if(!in_array($path . '/' . $value, $exclude_dir))
                        {
                            $node_array[] = array(
                                'text' => $value,
                                'children' => true,
                                'id' => $path . '/' . $value,
                                'icon' => 'jstree-folder'
                            );
                        }

                    } else {

                        $wp_admin_path = ABSPATH;
                        $wp_admin_path = str_replace('\\','/',$wp_admin_path);
                        $wp_admin_path = rtrim($wp_admin_path, '/');
                        $skip_path = rtrim($path, '/');

                        if($wp_admin_path==$skip_path)
                        {
                            continue;
                        }
                        $file_array[] = array(
                            'text' => $value,
                            'children' => false,
                            'id' => $path . '/' . $value,
                            'type' => 'file',
                            'icon' => 'jstree-file'
                        );
                    }
                }
            }
            $node_array = array_merge($node_array, $file_array);
        }
        $ret['nodes'] = $node_array;
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_connect_additional_database_addon_mainwp($data){
        try {
            $db_user = $data['db_user'];
            $db_pass = $data['db_pass'];
            $db_host = $data['db_host'];

            $ret['result']=WPVIVID_FAILED;
            $ret['error']='Unknown Error';
            $this->database_connect = new WPvivid_Additional_DB_Method($db_user, $db_pass, $db_host);
            $ret = $this->database_connect->wpvivid_do_connect();

            if($ret['result']===WPVIVID_SUCCESS){
                $databases = $this->database_connect->wpvivid_show_additional_databases();
                $default_exclude_database = array('information_schema', 'performance_schema', 'mysql', 'sys', DB_NAME);
                $database_array = array();
                foreach ($databases as $database) {
                    if (!in_array($database, $default_exclude_database)) {
                        $database_array[] = $database;
                    }
                }
                $ret['database_array'] = $database_array;
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        catch (Error $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_add_additional_database_addon_mainwp($data){
        try {
            $db_user = $data['db_user'];
            $db_pass = $data['db_pass'];
            $db_host = $data['db_host'];
            $db_list = $data['additional_database_list'];

            $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
            if (empty($history)) {
                $history = array();
            }
            foreach ($db_list as $database){
                $history['additional_database_option']['additional_database_list'][$database]['db_user'] = $db_user;
                $history['additional_database_option']['additional_database_list'][$database]['db_pass'] = $db_pass;
                $history['additional_database_option']['additional_database_list'][$database]['db_host'] = $db_host;
            }
            update_option('wpvivid_custom_backup_history', $history, 'no');

            if(!is_null($this->database_connect)){
                $this->database_connect->close();
            }

            $db_list = array();
            if(isset($history['additional_database_option']) && isset($history['additional_database_option']['additional_database_list'])) {
                $db_list = $history['additional_database_option']['additional_database_list'];
            }
            $ret['result']=WPVIVID_SUCCESS;
            $ret['data'] = $db_list;
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_remove_additional_database_addon_mainwp($data){
        try {
            if(!is_null($this->database_connect)){
                $this->database_connect->close();
            }
            $database_name = $data['database_name'];
            $history = get_option('wpvivid_custom_backup_history');
            if (empty($history)) {
                $history = array();
            }
            if(isset($history['additional_database_option'])) {
                if(isset($history['additional_database_option']['additional_database_list'][$database_name])){
                    unset($history['additional_database_option']['additional_database_list'][$database_name]);
                }
            }
            update_option('wpvivid_custom_backup_history', $history, 'no');
            $db_list = array();
            if(isset($history['additional_database_option']) && isset($history['additional_database_option']['additional_database_list'])) {
                $db_list = $history['additional_database_option']['additional_database_list'];
            }
            $ret['result']=WPVIVID_SUCCESS;
            $ret['data'] = $db_list;
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result'] = 'failed';
            $ret['error'] = $message;
        }
        return $ret;
    }

    public function wpvivid_get_database_by_filter_mainwp($data){
        try {
            $table_type  = $data['table_type'];
            $filter_text = $data['filter_text'];
            $option_type = $data['option_type'];

            global $wpdb;
            if($option_type !== 'incremental_backup')
            {
                $exclude = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
            }
            else
            {
                $exclude = WPvivid_Custom_Backup_Manager::get_incremental_db_setting();
            }

            if (empty($exclude)) {
                $exclude = array();
            }

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
            $table_array = array();
            foreach ($tables as $row) {
                if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                    continue;
                }

                $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
                $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);

                $checked = 'checked';
                if (!empty($exclude['database_option']['exclude_table_list'])) {
                    if (in_array($row["Name"], $exclude['database_option']['exclude_table_list'])) {
                        $checked = '';
                    }
                }

                if($table_type === 'base_table')
                {
                    if (in_array($row["Name"], $default_table))
                    {
                        $table_array[] = array('table_name' => $row["Name"], 'table_row' => $row["Rows"], 'table_size' => $tables_info[$row["Name"]]["Data_length"], 'table_check' => $checked);
                    }
                }
                else if($table_type === 'other_table')
                {
                    if (!in_array($row["Name"], $default_table))
                    {
                        $table_array[] = array('table_name' => $row["Name"], 'table_row' => $row["Rows"], 'table_size' => $tables_info[$row["Name"]]["Data_length"], 'table_check' => $checked);
                    }
                }
            }

            $ret['result'] = 'success';
            $ret['database_tables'] = $table_array;
            //$ret['base_tables'] = $base_table_array;
            //$ret['other_tables'] = $other_table_array;
            return $ret;
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result'] = 'failed';
            $ret['error'] = $message;
        }
        return $ret;
    }

    public function wpvivid_update_backup_exclude_extension_addon_mainwp($data){
        $exclude = get_option('wpvivid_custom_backup_history');
        if(empty($exclude)){
            $exclude = array();
        }
        $type = $data['type'];
        $value = $data['exclude_content'];
        if($type === 'uploads'){
            $exclude['uploads_option']['uploads_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $exclude['uploads_option']['uploads_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        else if($type === 'content'){
            $exclude['content_option']['content_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $exclude['content_option']['content_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        else if($type === 'others'){
            $exclude['other_option']['other_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $exclude['other_option']['other_extension_list'][] = $str_tmp[$index];
                }
            }
        }

        update_option('wpvivid_custom_backup_history', $exclude, 'no');

        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_get_default_remote_addon_mainwp($data){
        global $wpvivid_plugin;
        $ret['result']='success';
        $ret['remote_storage_type']=$wpvivid_plugin->function_realize->_get_default_remote_storage();
        return $ret;
    }

    public function wpvivid_get_remote_storage_addon_mainwp($data){
        $ret['result']='success';

        $upload_options=get_option('wpvivid_upload_setting');
        $options=get_option('wpvivid_user_history');
        if(array_key_exists('remote_selected',$options))
        {
            $upload_options['remote_selected'] = $options['remote_selected'];
        }
        else
        {
            $upload_options['remote_selected'] = array();
        }

        $ret['remoteslist'] = $upload_options;

        $has_remote = false;
        foreach ($ret['remoteslist'] as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            else
            {
                $has_remote = true;
            }
        }
        $ret['has_remote'] = $has_remote;

        $remoteslist=WPvivid_Setting::get_all_remote_options();

        return $ret;
    }

    public function pre_backup($backup_options)
    {
        global $wpvivid_plugin;

        if(apply_filters('wpvivid_need_clean_oldest_backup',true,$backup_options))
        {
            $wpvivid_plugin->clean_oldest_backup();
        }
        //do_action('wpvivid_clean_oldest_backup',$backup_options);

        if(WPvivid_taskmanager::is_tasks_backup_running())
        {
            $ret['result']='failed';
            $ret['error']=__('A task is already running. Please wait until the running task is complete, and try again.', 'wpvivid');
            return $ret;
        }

        if(!class_exists('WPvivid_Backup_Task_Ex'))
        {
            include WPVIVID_BACKUP_PRO_PLUGIN_DIR . '/addons2/backup_pro/class-wpvivid-backup-task-addon.php';
        }

        $backup=new WPvivid_Backup_Task_Ex();
        $ret=$backup->new_backup_task($backup_options,$backup_options['type'],$backup_options['action']);
        return $ret;
    }

    public function wpvivid_prepare_backup_addon_mainwp($data){
        global $wpvivid_plugin;
        $wpvivid_plugin->end_shutdown_function=false;
        register_shutdown_function(array($wpvivid_plugin,'deal_prepare_shutdown_error'));
        try
        {
            $backup_options = $data['backup'];
            if (is_null($backup_options))
            {
                $wpvivid_plugin->end_shutdown_function=true;
                $ret['error']='Invalid parameter param:'.$backup_options;
                return $ret;
            }

            $backup_options = apply_filters('wpvivid_custom_backup_options', $backup_options);

            if(!isset($backup_options['type']))
            {
                $backup_options['type']='Manual';
                $backup_options['action']='backup';
            }

            $ret = $wpvivid_plugin->check_backup_option($backup_options, $backup_options['type']);
            if($ret['result']!=WPVIVID_SUCCESS)
            {
                $wpvivid_plugin->end_shutdown_function=true;
                return $ret;
            }

            $ret=$this->pre_backup($backup_options);
            if($ret['result']=='success')
            {
                $ret['check']=$wpvivid_plugin->check_backup($ret['task_id'],$backup_options);
                if(isset($ret['check']['result']) && $ret['check']['result'] == WPVIVID_FAILED)
                {
                    $wpvivid_plugin->end_shutdown_function=true;
                    $ret['error'] = $ret['check']['error'];
                    return $ret;
                }
            }
            $wpvivid_plugin->end_shutdown_function=true;
        }
        catch (Exception $error)
        {
            $wpvivid_plugin->end_shutdown_function=true;
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
        }
        return $ret;
    }

    public function wpvivid_backup_now_addon_mainwp($data){
        $task_id = $data['task_id'];
        global $wpvivid_plugin;
        if (!isset($task_id)||empty($task_id)||!is_string($task_id))
        {
            $ret['error']=__('Error occurred while parsing the request data. Please try to run backup again.', 'wpvivid');
            return $ret;
        }
        $task_id=sanitize_key($task_id);
        /*$ret['result']='success';
        $txt = '<mainwp>' . base64_encode( serialize( $ret ) ) . '</mainwp>';
        // Close browser connection so that it can resume AJAX polling
        header( 'Content-Length: ' . ( ( ! empty( $txt ) ) ? strlen( $txt ) : '0' ) );
        header( 'Connection: close' );
        header( 'Content-Encoding: none' );
        if ( session_id() ) {
            session_write_close();
        }
        echo $txt;
        // These two added - 19-Feb-15 - started being required on local dev machine, for unknown reason (probably some plugin that started an output buffer).
        if ( ob_get_level() ) {
            ob_end_flush();
        }
        flush();*/
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->func->flush($task_id, true);
        //Start backup site
        $wpvivid_plugin->backup($task_id);
        $ret['result']='success';
    }

    public function wpvivid_list_tasks_addon_mainwp(){
        global $wpvivid_plugin;
        $list_tasks=array();
        $ret['need_refresh_remote']=false;
        $ret['success_notice_html']=false;
        $ret['error_notice_html']=false;
        $tasks=get_option('wpvivid_task_list', array());
        foreach ($tasks as $task){
            $backup=new WPvivid_Backup_Task($task['id']);
            $list_tasks[$task['id']]=$backup->get_backup_task_info($task['id']);

            if($list_tasks[$task['id']]['task_info']['need_next_schedule']===true)
            {
                $timestamp = wp_next_scheduled(WPVIVID_TASK_MONITOR_EVENT,array($task['id']));
                if($timestamp===false)
                {
                    $wpvivid_plugin->add_monitor_event($task['id'],20);
                }
            }
            if($list_tasks[$task['id']]['task_info']['need_update_last_task']===true)
            {
                $task_msg = WPvivid_taskmanager::get_task($task['id']);
                $wpvivid_plugin->update_last_backup_task($task_msg);
                if($task['type'] === 'Cron')
                {
                    $schedule_id = false;
                    $schedule_id = get_option('wpvivid_current_schedule_id', $schedule_id);
                    if(isset($schedule_id))
                    {
                        //update last backup time
                        do_action('wpvivid_update_schedule_last_time_addon', $schedule_id, $task_msg['status']['start_time']);
                    }
                }
            }
        }
        $finished_tasks=get_option('wpvivid_backup_finished_tasks',array());
        if(!empty($finished_tasks))
        {
            foreach ($finished_tasks as $finished_task)
            {
                if($finished_task['status']=='completed')
                {
                    $ret['success_notice_html'] = true;
                }
                else if($finished_task['status']=='error')
                {
                    $ret['error_notice_html'] = $finished_task['tmp_msg'];
                }
            }

            $ret['need_refresh_remote'] = get_option('wpvivid_backup_remote_need_update', false);

            $tasks=get_option('wpvivid_task_list', array());
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
            update_option('wpvivid_task_list',$tasks,'no');
            delete_option('wpvivid_backup_finished_tasks');
        }
        $ret['tasks']=$list_tasks;
        return $ret;
    }

    public function pre_new_backup($backup_options)
    {
        $ret=apply_filters('wpvivid_pre_new_backup_for_mainwp', $backup_options);
        return $ret;
    }

    public function wpvivid_prepare_backup_addon_mainwp_ex($data){
        global $wpvivid_plugin;
        $wpvivid_plugin->end_shutdown_function=false;
        register_shutdown_function(array($wpvivid_plugin,'deal_prepare_shutdown_error'));
        try
        {
            $backup_options = $data['backup'];
            if (is_null($backup_options))
            {
                $wpvivid_plugin->end_shutdown_function=true;
                $ret['error']='Invalid parameter param:'.$backup_options;
                return $ret;
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
            if(!isset($backup_options['type']))
            {
                $backup_options['type']='Manual';
            }

            $ret=$this->pre_new_backup($backup_options);
            $wpvivid_plugin->end_shutdown_function=true;
        }
        catch (Exception $error)
        {
            $wpvivid_plugin->end_shutdown_function=true;
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
        }
        return $ret;
    }

    public function wpvivid_backup_now_addon_mainwp_ex($data){
        $task_id = $data['task_id'];
        do_action('wpvivid_backup_now_for_mainwp', $task_id);
        $ret['result']='success';
        return $ret;
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

    public function wpvivid_list_tasks_addon_mainwp_ex()
    {
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

        $list_tasks=array();

        foreach ($tasks as $task)
        {
            if(!isset($task['id']))
            {
                continue;
            }

            $backup_task=new WPvivid_New_Backup_Task($task['id']);
            $list_tasks[$task['id']]=$backup_task->get_backup_task_info();
            $list_tasks[$task['id']]['id']=$task['id'];
            if($list_tasks[$task['id']]['task_info']['need_next_schedule']===true)
            {
                $timestamp = wp_next_scheduled('wpvivid_task_monitor_event_ex',array($task['id']));
                if($timestamp===false)
                {
                    $this->add_monitor_event($task['id'],20);
                }
            }

            if($list_tasks[$task['id']]['status']['str']=='ready'||$list_tasks[$task['id']]['status']['str']=='running'||$list_tasks[$task['id']]['status']['str']=='wait_resume'||$list_tasks[$task['id']]['status']['str']=='no_responds')
            {
                $ret['running_backup_taskid']=$task['id'];

                if($list_tasks[$task['id']]['status']['str']=='wait_resume')
                {
                    $ret['wait_resume']=true;
                    $ret['next_resume_time']=$list_tasks[$task['id']]['data']['next_resume_time'];
                }

                if($list_tasks[$task['id']]['status']['str']=='no_responds')
                {
                    $ret['task_no_response']=true;
                }
            }

            if($list_tasks[$task['id']]['status']['str']=='completed')
            {
                $finished_tasks[$task['id']]=$task;
                $backup_success_count++;
            }
            else if($list_tasks[$task['id']]['status']['str']=='error')
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

        if($backup_success_count>0)
        {
            $ret['success_notice_html'] = true;;
        }

        if($backup_failed_count>0)
        {
            $notice_msg = $backup_failed_count.' backup task(s) have been failed.';
            $ret['error_notice_html'] = $notice_msg;
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

        $ret['tasks']=$list_tasks;
        return $ret;
    }

    public function wpvivid_delete_ready_task_addon_mainwp($data){
        try {
            WPvivid_taskmanager::delete_ready_task();
            $ret['result'] = 'success';
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result'] = 'failed';
            $ret['error'] = $message;
        }
        return $ret;
    }

    public function wpvivid_backup_cancel_addon_mainwp($data){
        global $wpvivid_plugin;
        $ret=$wpvivid_plugin->function_realize->_backup_cancel();
        return $ret;
    }

    /***** wpvivid mainwp backup restore filter *****/
    public function wpvivid_achieve_local_backup_addon_mainwp($data){
        try
        {
            if(isset($data['folder']) && !empty($data['folder']))
            {
                $backup_folder = $data['folder'];
                $backuplist=WPvivid_Backuplist::get_backuplist('wpvivid_backup_list');
                if($backup_folder === 'wpvivid')
                {
                    $localbackuplist=array();
                    foreach ($backuplist as $key=>$value)
                    {
                        if($value['type'] === 'Rollback' || $value['type'] === 'Incremental')
                        {
                            continue;
                        }
                        else
                        {
                            $localbackuplist[$key]=$value;
                        }
                    }
                    $ret['list_data']=$localbackuplist;
                }
                elseif($backup_folder === 'rollback')
                {
                    $rollbackuplist=array();
                    foreach ($backuplist as $key=>$value)
                    {
                        if($value['type'] === 'Rollback')
                        {
                            $rollbackuplist[$key]=$value;
                        }
                    }
                    $ret['list_data']=$rollbackuplist;
                }
                elseif($backup_folder === 'incremental')
                {
                    $incrementallist=array();
                    foreach ($backuplist as $key=>$value){
                        //$value['create_time'] = $this->wpvivid_tran_backup_time_to_local($value);
                        if($value['type'] === 'Incremental') {
                            $incrementallist[$key]=$value;
                        }
                    }
                    $ret['list_data']=$incrementallist;
                }
                $ret['result']='success';
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_achieve_remote_backup_info_addon_mainwp($data){
        try{
            $upload_options=get_option('wpvivid_upload_setting');
            $options=get_option('wpvivid_user_history');
            if(isset($options['remote_selected'])){
                $upload_options['remote_selected'] = $options['remote_selected'];
            }
            else{
                $upload_options['remote_selected'] = array();
            }

            $remoteslist=$upload_options;
            $select_remote_id=get_option('wpvivid_select_list_remote_id', '');
            if($select_remote_id === ''){
                foreach ($remoteslist as $key => $value)
                {
                    if($key === 'remote_selected')
                    {
                        continue;
                    }
                    else {
                        $select_remote_id = $key;
                    }
                }
            }
            $remote_folder = 'Common';

            $select_remote_list=array();
            if(isset($remoteslist[$select_remote_id])) {
                update_option('wpvivid_remote_list', array(), 'no');
                $remote_option = $remoteslist[$select_remote_id];

                global $wpvivid_plugin;

                $remote_collection=new WPvivid_Remote_collection_addon();
                $remote = $remote_collection->get_remote($remote_option);

                if (!method_exists($remote, 'scan_folder_backup')) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The selected remote storage does not support scanning.';
                    return $ret;
                }

                $ret = $remote->scan_folder_backup($remote_folder);
                if ($ret['result'] == WPVIVID_SUCCESS) {
                    global $wpvivid_backup_pro;
                    $wpvivid_backup_pro->func->rescan_remote_folder_set_backup($select_remote_id, $ret);
                }

                $list = get_option('wpvivid_remote_list', array());

                foreach ($list as $key => $item) {
                    if ($item['type'] == $remote_folder) {
                        $select_remote_list[$key] = $item;
                    }
                }
            }
            $ret['remote_list'] = $remoteslist;
            $ret['select_remote_id'] = $select_remote_id;
            $ret['select_list_data'] = $select_remote_list;
            $ret['result'] = 'success';
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_achieve_remote_backup_addon_mainwp($data){
        try
        {
            if(isset($data['remote_id']) && !empty($data['remote_id']) && isset($data['folder']) && !empty($data['folder']))
            {
                set_time_limit(120);

                $upload_options=get_option('wpvivid_upload_setting');
                $options=get_option('wpvivid_user_history');
                if(isset($options['remote_selected'])){
                    $upload_options['remote_selected'] = $options['remote_selected'];
                }
                else{
                    $upload_options['remote_selected'] = array();
                }

                $remoteslist = $upload_options;
                $remote_id = $data['remote_id'];
                $remote_folder = $data['folder'];

                if (empty($remote_id))
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'Failed to post remote stroage id. Please try again.';
                    return $ret;
                }

                if (empty($remote_folder))
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'Failed to post remote storage folder. Please try again.';
                    return $ret;
                }

                update_option('wpvivid_select_list_remote_id', $remote_id, 'no');
                update_option('wpvivid_remote_list', array(), 'no');
                $remote_option = $remoteslist[$remote_id];

                global $wpvivid_plugin;

                $remote_collection=new WPvivid_Remote_collection_addon();
                $remote = $remote_collection->get_remote($remote_option);

                if (!method_exists($remote, 'scan_folder_backup'))
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The selected remote storage does not support scanning.';
                    return $ret;
                }


                //
                if($remote_folder === 'Incremental')
                {
                    $remote_folder = 'Common';
                }

                if(isset($data['incremental_path'])&&!empty($data['incremental_path']))
                {
                    $incremental_path=$data['incremental_path'];
                    $ret = $remote->scan_child_folder_backup($incremental_path);
                }
                else
                {
                    $ret = $remote->scan_folder_backup($remote_folder);
                }
                //


                if ($ret['result'] == WPVIVID_SUCCESS)
                {
                    global $wpvivid_backup_pro;
                    $wpvivid_backup_pro->func->rescan_remote_folder_set_backup($remote_id, $ret);
                }

                $ret['local_cache_files_size'] = apply_filters('wpvivid_get_local_cache_files_size', 0);

                $list=get_option('wpvivid_remote_list', array());
                $remote_list=array();

                foreach ($list as $key=>$item)
                {
                    if($item['type']==$remote_folder)
                    {
                        $remote_list[$key]=$item;
                    }
                }
                $ret['list_data'] = $remote_list;

                $ret['incremental_list'] = false;
                if(isset($ret['path']) && !empty($ret['path']))
                {
                    $path_list = array();
                    foreach ($ret['path'] as $path) {
                        if (preg_match('/.*_.*_.*_to_.*_.*_.*$/', $path)){
                            $og_path=$path;
                            $path = preg_replace("/_to_.*_.*_.*/", "", $path);
                            $path = preg_replace("/_/", "-", $path);
                            $path = strtotime($path);
                            $temp['og_path']=$og_path;
                            $temp['path']=$path;
                            $path_list[] = $temp;
                        }
                    }

                    uasort ($path_list,function($a, $b) {
                        if($a['path']>$b['path']) {
                            return -1;
                        }
                        else if($a['path']===$b['path']) {
                            return 0;
                        }
                        else {
                            return 1;
                        }
                    });

                    $ret['incremental_list'] = $path_list;
                }

                $ret['result'] = 'success';
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function _get_backup_list($backup_storage,$backup_folder,$remote_storage)
    {
        $backup_list=new WPvivid_New_BackupList();
        if($backup_storage=='all_backups')
        {
            if($backup_folder=='all_backup')
            {
                $backups=$backup_list->get_all_backup();
            }
            else
            {
                $backups=$backup_list->get_all_backup_ex($backup_folder);
            }
        }
        else if($backup_storage=='localhost')
        {
            if($backup_folder=='all_backup')
            {
                $backups=$backup_list->get_local_backup();
            }
            else
            {
                $backups=$backup_list->get_local_backup_ex($backup_folder);
            }
        }
        else if($backup_storage=='cloud')
        {
            if($remote_storage=='all_backup')
            {
                if($backup_folder=='all_backup')
                {
                    $backups=$backup_list->get_all_remote_backup();
                }
                else
                {
                    $backups=$backup_list->get_all_remote_backup($backup_folder);
                }
            }
            else
            {
                if($backup_folder=='all_backup')
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_storage);
                }
                else
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_storage,$backup_folder);
                }
            }
        }
        else
        {
            $backups=array();
        }

        if(!empty($backups))
        {
            foreach ($backups as $index=>$backup)
            {
                $content['db']=false;
                $content['files']=false;
                $content['custom']=false;

                if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
                {
                    foreach ($backup['backup_info']['types'] as $type=>$data)
                    {
                        if($type==='Database')
                        {
                            $content['db']=true;
                        }
                        else if($type==='Additional Database')
                        {
                            $content['db']=true;
                        }
                        else if($type==='Others')
                        {
                            $content['custom']=true;
                        }
                        else
                        {
                            $content['files']=true;
                        }
                    }

                    $backups[$index]['has_db']=$content['db'];
                    $backups[$index]['has_files']=$content['files'];
                    $backups[$index]['has_custom']=$content['custom'];
                    continue;
                }

                $has_db = false;
                $has_file = false;
                $has_custom = false;
                $type_list = array();
                $ismerge = false;

                if(isset($backup['backup']['files']))
                {
                    foreach ($backup['backup']['files'] as $key => $value)
                    {
                        $file_name = $value['file_name'];
                        if(WPvivid_backup_pro_function::is_wpvivid_db_backup($file_name))
                        {
                            $has_db = true;
                            if(!in_array('Database', $type_list))
                            {
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
                            $has_custom = true;
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
                    $backup_item = new WPvivid_New_Backup_Item($backup);
                    $files_info=array();
                    foreach ($backup['backup']['files'] as $file)
                    {
                        $file_name = $file['file_name'];
                        $files_info[$file_name]=$backup_item->get_file_info($file_name);
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
                                $has_custom = true;
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

                $content['db']=$has_db;
                $content['files']=$has_file;
                $content['custom']=$has_custom;

                $backups[$index]['has_db']=$content['db'];
                $backups[$index]['has_files']=$content['files'];
                $backups[$index]['has_custom']=$content['custom'];
            }
        }

        return $backups;
    }

    public function wpvivid_achieve_backup_list_addon_mainwp($data){
        try{
            if(isset($data['backup_storage']) && !empty($data['backup_storage']) &&
                isset($data['backup_folder']) && !empty($data['backup_folder']) &&
                isset($data['remote_storage']) && !empty($data['remote_storage'])){
                $backup_storage = $data['backup_storage'];
                $backup_folder = $data['backup_folder'];
                $remote_storage = $data['remote_storage'];
                $backups=$this->_get_backup_list($backup_storage,$backup_folder,$remote_storage);

                if(isset($data['get_remote_storage']) && $data['get_remote_storage'] == '1')
                {
                    $wpvivid_remote_list = WPvivid_Setting::get_all_remote_options();
                    $ret['wpvivid_remote_list'] = $wpvivid_remote_list;
                }

                $ret['backups'] = $backups;
                $ret['result'] = 'success';
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function _scan_remote_backup($remote_id,$backup_folder)
    {
        set_time_limit(120);
        $remoteslist = WPvivid_Setting::get_all_remote_options();
        $remote_option = $remoteslist[$remote_id];

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote = $remote_collection->get_remote($remote_option);

        if (!method_exists($remote, 'scan_folder_backup_ex'))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        if($backup_folder=='all_backup')
        {
            $remote_list = get_option('wpvivid_new_remote_list',array());
            $remote_list[$remote_id]=array();
            update_option('wpvivid_new_remote_list',$remote_list,'no');
        }
        else
        {
            $remote_list = get_option('wpvivid_new_remote_list',array());
            if(isset($remote_list[$remote_id]) && !empty($remote_list[$remote_id]))
            {
                foreach ($remote_list[$remote_id] as $backup_id => $remote_backup)
                {
                    if(isset($remote_backup['type']) && $remote_backup['type'] === $backup_folder)
                    {
                        unset($remote_list[$remote_id][$backup_id]);
                    }
                }
            }
            update_option('wpvivid_new_remote_list',$remote_list,'no');
        }

        $ret = $remote->scan_folder_backup_ex($backup_folder);

        if ($ret['result'] == 'success')
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

            $task_list=array();
            if(!empty($ret['remote']))
            {
                foreach ($ret['remote'] as $id=>$backup)
                {
                    $backup_data=array();
                    $backup_data['type']='Manual';
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
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }

            if(!empty($ret['migrate']))
            {
                foreach ($ret['migrate'] as $id=>$backup)
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
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }

            if(!empty($ret['rollback']))
            {
                foreach ($ret['rollback'] as $id=>$backup)
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
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }

            if(!empty($ret['incremental']))
            {
                foreach ($ret['incremental'] as $id=>$backup)
                {
                    $backup_data=array();
                    $backup_data['type']='Incremental';
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
                    $backup_data['remote']=$remote_options;
                    $backup_data['incremental_path']=$backup['incremental_path'];
                    $backup_data['lock']=0;
                    $backup_data['backup_info_file']=isset($backup['backup_info_file'])?$backup['backup_info_file']:'';
                    $task_list[$id]=$backup_data;
                }
            }

            $scan_task_data['list']=$task_list;
            $scan_task_data['remote_id']=$remote_id;
            $scan_task_data['backup_folder']=$backup_folder;
            update_option('wpvivid_scan_remote_task',$scan_task_data,'no');

            $ret['result']='success';
            $ret['backups']=array();
            $ret['finished']=false;
            $ret['test']=$scan_task_data;
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function wpvivid_scan_remote_backup_addon_mainwp($data){
        try{
            if(isset($data['backup_folder']) && !empty($data['backup_folder']) &&
                isset($data['remote_storage']) && !empty($data['remote_storage'])){

                $backup_folder = $data['backup_folder'];
                $remote_storage = $data['remote_storage'];
                $scan_ret=$this->_scan_remote_backup($remote_storage,$backup_folder);
                if($scan_ret['result']=='success')
                {
                    $backups=$scan_ret['backups'];
                }
                else
                {
                    $backups=array();
                }

                $ret['finished'] = $scan_ret['finished'];
                $ret['backups'] = $backups;
                $ret['result'] = 'success';
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function _scan_remote_backup_continue()
    {
        $scan_task_data=get_option('wpvivid_scan_remote_task');
        $remote_id=$scan_task_data['remote_id'];
        $backup_folder=$scan_task_data['backup_folder'];
        $remoteslist = WPvivid_Setting::get_all_remote_options();
        $remote_option = $remoteslist[$remote_id];

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote = $remote_collection->get_remote($remote_option);

        if (!method_exists($remote, 'get_backup_info'))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            return $ret;
        }

        $time_limit = 21;
        $start_time = time();

        foreach ($scan_task_data['list'] as $backup_id =>$backup_data)
        {
            if(isset($backup_data['backup_info']))
            {
                continue;
            }

            if(empty($backup_data['backup_info_file']))
            {
                $backup_data['backup_info']=array();
            }
            else
            {
                if($backup_data['type']=='Incremental')
                {
                    $ret=$remote->get_backup_info($backup_data['backup_info_file'],$backup_data['type'],$backup_data['incremental_path']);
                }
                else
                {
                    $ret=$remote->get_backup_info($backup_data['backup_info_file'],$backup_data['type']);
                }

                if($ret['result']=='success')
                {
                    $backup_data['backup_info']=$ret['backup_info'];
                    $backup_data['type']=$ret['backup_info']['type'];
                }
                else
                {
                    $backup_data['backup_info']=array();
                }
            }

            $scan_task_data['list'][$backup_id]=$backup_data;
            update_option('wpvivid_scan_remote_task',$scan_task_data,'no');

            $remote_list = get_option('wpvivid_new_remote_list',array());
            $remote_list[$remote_id][$backup_id]=$backup_data;
            update_option('wpvivid_new_remote_list',$remote_list,'no');

            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                $backup_list=new WPvivid_New_BackupList();
                if($backup_folder=='all_backup')
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_id);
                }
                else
                {
                    $backups=$backup_list->get_remote_backup_ex($remote_id,$backup_folder);
                }

                $ret['result']='success';
                $ret['backups']=$backups;
                $ret['finished']=false;
                return $ret;
            }
        }

        $backup_list=new WPvivid_New_BackupList();
        if($backup_folder=='all_backup')
        {
            $backups=$backup_list->get_remote_backup_ex($remote_id);
        }
        else
        {
            $backups=$backup_list->get_remote_backup_ex($remote_id,$backup_folder);
        }

        if(!empty($backups))
        {
            foreach ($backups as $index=>$backup)
            {
                $content['db']=false;
                $content['files']=false;
                $content['custom']=false;

                if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
                {
                    foreach ($backup['backup_info']['types'] as $type=>$data)
                    {
                        if($type==='Database')
                        {
                            $content['db']=true;
                        }
                        else if($type==='Additional Database')
                        {
                            $content['db']=true;
                        }
                        else if($type==='Others')
                        {
                            $content['custom']=true;
                        }
                        else
                        {
                            $content['files']=true;
                        }
                    }

                    $backups[$index]['has_db']=$content['db'];
                    $backups[$index]['has_files']=$content['files'];
                    $backups[$index]['has_custom']=$content['custom'];
                    continue;
                }

                $has_db = false;
                $has_file = false;
                $has_custom = false;
                $type_list = array();
                $ismerge = false;

                if(isset($backup['backup']['files']))
                {
                    foreach ($backup['backup']['files'] as $key => $value)
                    {
                        $file_name = $value['file_name'];
                        if(WPvivid_backup_pro_function::is_wpvivid_db_backup($file_name))
                        {
                            $has_db = true;
                            if(!in_array('Database', $type_list))
                            {
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
                            $has_custom = true;
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
                    $backup_item = new WPvivid_New_Backup_Item($backup);
                    $files_info=array();
                    foreach ($backup['backup']['files'] as $file)
                    {
                        $file_name = $file['file_name'];
                        $files_info[$file_name]=$backup_item->get_file_info($file_name);
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
                                $has_custom = true;
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

                $content['db']=$has_db;
                $content['files']=$has_file;
                $content['custom']=$has_custom;

                $backups[$index]['has_db']=$content['db'];
                $backups[$index]['has_files']=$content['files'];
                $backups[$index]['has_custom']=$content['custom'];
            }
        }

        $ret['result']='success';
        $ret['backups']=$backups;
        $ret['finished']=true;
        return $ret;
    }

    public function wpvivid_scan_remote_backup_continue_addon_mainwp($data)
    {
        try{
            $scan_ret=$this->_scan_remote_backup_continue();
            if($scan_ret['result']=='success')
            {
                $backups=$scan_ret['backups'];
            }
            else
            {
                $backups=array();
            }

            $ret['finished'] = $scan_ret['finished'];
            $ret['backups'] = $backups;
            $ret['result'] = 'success';
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_delete_backup_ex_addon_mainwp($data)
    {
        try{
            if(isset($data['backup_id']) && !empty($data['backup_id']) &&
                isset($data['backup_storage']) && !empty($data['backup_storage']) &&
                isset($data['backup_folder']) && !empty($data['backup_folder']) &&
                isset($data['remote_storage']) && !empty($data['remote_storage'])){

                $backup_id = $data['backup_id'];
                $backup_list=new WPvivid_New_BackupList();

                $remote_storage = $data['remote_storage'];
                $backup=$backup_list->get_local_backup($backup_id);
                if($backup===false)
                {
                    if($remote_storage!='all_backup')
                    {
                        $backup=$backup_list->get_remote_backup($remote_storage,$backup_id);
                    }
                    else
                    {
                        $backup=$backup_list->get_remote_backup_by_id($backup_id);
                    }

                    if($backup===false)
                    {
                        $ret['result']='failed';
                        $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid');
                        return $ret;
                    }
                }

                $backup_item=new WPvivid_New_Backup_Item($backup);
                $files=$backup_item->get_files();
                foreach ($files as $file)
                {
                    if (file_exists($file))
                    {
                        @unlink($file);
                    }
                }
                $files=array();
                if(isset($backup['backup']['files']))
                {
                    //file_name
                    foreach ($backup['backup']['files'] as $file)
                    {
                        if($backup['type']=='Incremental')
                        {
                            if(isset($backup['incremental_path']))
                            {
                                $file['remote_path']=$backup['incremental_path'];
                            }
                        }

                        $files[]=$file;
                        if (file_exists($this->get_backup_path($backup_item, $file['file_name'])))
                        {
                            @unlink($this->get_backup_path($backup_item, $file['file_name']));
                        }
                    }
                }
                else
                {
                    $files=$backup_item->get_files();
                    foreach ($files as $file)
                    {
                        if (file_exists($file))
                        {
                            @unlink($file);
                        }
                    }
                }

                if($remote_storage!='all_backup')
                {
                    $backup_list->delete_backup($backup_id,$remote_storage);
                }
                else
                {
                    $backup_list->delete_backup($backup_id);
                }

                if(!empty($backup['remote']))
                {
                    if(isset($backup['backup_info_file'])&&!empty($backup['backup_info_file']))
                    {
                        if($backup['type']=='Incremental')
                        {
                            if(isset($backup['incremental_path']))
                            {
                                $file['remote_path']=$backup['incremental_path'];
                            }
                        }
                        $file['file_name']=$backup['backup_info_file'];
                        $files[]=$file;
                    }

                    foreach($backup['remote'] as $remote)
                    {
                        WPvivid_downloader::delete($remote,$files);
                    }
                }

                $backup_storage = $data['backup_storage'];
                $backup_folder = $data['backup_folder'];
                $backups=$this->_get_backup_list($backup_storage,$backup_folder,$remote_storage);

                $ret['backups'] = $backups;
                $ret['result'] = 'success';
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_delete_backup_array_ex_addon_mainwp($data)
    {
        try{
            if(isset($data['backup_id']) && !empty($data['backup_id']) &&
                isset($data['backup_storage']) && !empty($data['backup_storage']) &&
                isset($data['backup_folder']) && !empty($data['backup_folder']) &&
                isset($data['remote_storage']) && !empty($data['remote_storage'])){
                $remote_storage = $data['remote_storage'];

                $backup_ids = $data['backup_id'];
                $backup_list=new WPvivid_New_BackupList();
                $ret = array();

                $start_time=time();
                $time_limit = 21;

                foreach ($backup_ids as $backup_id)
                {
                    $backup=$backup_list->get_local_backup($backup_id);
                    if($backup===false)
                    {
                        if($remote_storage!='all_backup')
                        {
                            $backup=$backup_list->get_remote_backup($remote_storage,$backup_id);
                        }
                        else
                        {
                            $backup=$backup_list->get_remote_backup_by_id($backup_id);
                        }

                        if($backup===false)
                        {
                            continue;
                        }
                    }
                    $backup_item=new WPvivid_New_Backup_Item($backup);
                    $files=$backup_item->get_files();
                    foreach ($files as $file)
                    {
                        if (file_exists($file))
                        {
                            @unlink($file);
                        }
                    }
                    $files=array();
                    if(isset($backup['backup']['files']))
                    {
                        //file_name
                        foreach ($backup['backup']['files'] as $file)
                        {
                            if($backup['type']=='Incremental')
                            {
                                if(isset($backup['incremental_path']))
                                {
                                    $file['remote_path']=$backup['incremental_path'];
                                }
                            }
                            $files[]=$file;

                            if (file_exists($this->get_backup_path($backup_item, $file['file_name'])))
                            {
                                @unlink($this->get_backup_path($backup_item, $file['file_name']));
                            }
                        }
                    }
                    else
                    {
                        $files=$backup_item->get_files();
                        foreach ($files as $file)
                        {
                            if (file_exists($file))
                            {
                                @unlink($file);
                            }
                        }
                    }

                    if($remote_storage!='all_backup')
                    {
                        $backup_list->delete_backup($backup_id,$remote_storage);
                    }
                    else
                    {
                        $backup_list->delete_backup($backup_id);
                    }


                    if(!empty($backup['remote']))
                    {
                        if(isset($backup['backup_info_file'])&&!empty($backup['backup_info_file']))
                        {
                            if($backup['type']=='Incremental')
                            {
                                if(isset($backup['incremental_path']))
                                {
                                    $file['remote_path']=$backup['incremental_path'];
                                }
                            }
                            $file['file_name']=$backup['backup_info_file'];
                            $files[]=$file;
                        }

                        foreach($backup['remote'] as $remote)
                        {
                            WPvivid_downloader::delete($remote,$files);
                        }
                    }

                    $time_taken = microtime(true) - $start_time;
                    if($time_taken >= $time_limit)
                    {
                        $ret['result']='success';
                        $ret['continue']=1;
                        echo json_encode($ret);
                        die();
                    }
                }

                $backup_storage = $data['backup_storage'];
                $backup_folder = $data['backup_folder'];
                $backups=$this->_get_backup_list($backup_storage,$backup_folder,$remote_storage);

                $ret['backups'] = $backups;
                $ret['result'] = 'success';
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_set_security_lock_ex_addon_mainwp($data)
    {
        try{
            $backup_id = $data['backup_id'];
            if ($data['lock'] == 0 || $data['lock'] == 1) {
                $lock = $data['lock'];
            }
            else {
                $lock = 0;
            }

            $backup_list=new WPvivid_New_BackupList();
            $backup_data = $backup_list->get_local_backup($backup_id);

            if($backup_data !== false)
            {
                if ($lock == 1)
                {
                    $backup_data['lock'] = 1;
                }
                else
                {
                    $backup_data['lock'] = 0;
                }
                $backup_list->add_local_backup($backup_id,$backup_data);
            }
            else
            {
                $backup_data=$backup_list->get_remote_backup_by_id($backup_id);
                if($backup_data !== false)
                {
                    $backup_lock=get_option('wpvivid_remote_backups_lock',array());
                    if($lock)
                    {
                        $backup_lock[$backup_id]=1;
                    }
                    else
                    {
                        unset($backup_lock[$backup_id]);
                    }
                    update_option('wpvivid_remote_backups_lock',$backup_lock,'no');
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='backup not found';
                    return $ret;
                }
            }

            $ret['result'] = 'success';
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function init_download($backup_id)
    {
        if(empty($backup_id))
        {
            $ret['result']=WPVIVID_SUCCESS;
            $ret['data']=array();
            return $ret;
        }
        $ret['result']=WPVIVID_SUCCESS;
        //$backup=WPvivid_Backuplist::get_backup_by_id($backup_id);
        $backup_list=new WPvivid_New_BackupList();
        $backup=$backup_list->get_backup_by_id($backup_id);
        if($backup===false)
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']='backup id not found';
            return $ret;
        }

        $backup_item=new WPvivid_New_Backup_Item($backup);
        //$ret=$backup_item->get_download_backup_files($backup_id);
        $files=array();
        $files=self::get_backup_files($backup);
        if(empty($files)){
            $ret['error']='Failed to get backup files.';
        }
        else{
            $ret['result']=WPVIVID_SUCCESS;
            $ret['files']=$files;
        }

        if($ret['result']==WPVIVID_SUCCESS){
            $ret=$backup_item->get_download_progress($backup_id, $ret['files']);
            WPvivid_taskmanager::update_download_cache($backup_id,$ret);
        }
        return $ret;
    }

    public static function get_backup_files($backup)
    {
        $files=array();
        if(isset($backup['backup']['files'])){
            $files=$backup['backup']['files'];
        }
        else{
            if(isset($backup['backup']['ismerge'])) {
                if ($backup['backup']['ismerge'] == 1) {
                    if(isset($backup['backup']['data']['meta']['files'])){
                        $files=$backup['backup']['data']['meta']['files'];
                    }
                }
            }
        }
        asort($files);
        uasort($files, function ($a, $b) {
            $file_name_1 = $a['file_name'];
            $file_name_2 = $b['file_name'];
            $index_1 = 0;
            if(preg_match('/wpvivid-.*_.*_.*\.part.*\.zip$/', $file_name_1)) {
                if (preg_match('/part.*$/', $file_name_1, $matches)) {
                    $index_1 = $matches[0];
                    $index_1 = preg_replace("/part/","", $index_1);
                    $index_1 = preg_replace("/.zip/","", $index_1);
                }
            }
            $index_2 = 0;
            if(preg_match('/wpvivid-.*_.*_.*\.part.*\.zip$/', $file_name_2)) {
                if (preg_match('/part.*$/', $file_name_2, $matches)) {
                    $index_2 = $matches[0];
                    $index_2 = preg_replace("/part/", "", $index_2);
                    $index_2 = preg_replace("/.zip/", "", $index_2);
                }
            }
            if($index_1 !== 0 && $index_2 === 0){
                return -1;
            }
            if($index_1 === 0 && $index_2 !== 0){
                return 1;
            }
        });
        return $files;
    }

    public function wpvivid_new_init_download_page_addon_mainwp($data)
    {
        $backup_id = $data['backup_id'];
        global $wpvivid_plugin;
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id)) {
            $ret['error']='Invalid parameter param:'.$backup_id;
            return $ret;
        }
        else {
            $backup_list=new WPvivid_New_BackupList();
            $backup=$backup_list->get_backup_by_id($backup_id);
            if($backup===false)
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']='backup id not found';
                return $ret;
            }
            $backup_item=new WPvivid_New_Backup_Item($backup);
            //$backup_files=$backup_item->get_download_backup_files($backup_id);
            $files=array();
            $files=self::get_backup_files($backup);
            if(empty($files)){
                $backup_files['error']='Failed to get backup files.';
            }
            else{
                $backup_files['result']=WPVIVID_SUCCESS;
                $backup_files['files']=$files;
            }
            if($backup_files['result']==WPVIVID_SUCCESS){
                $ret['result'] = WPVIVID_SUCCESS;
                $remote=$backup_item->get_remote();
                foreach ($backup_files['files'] as $file){
                    $path = $this->get_backup_path($backup_item, $file['file_name']);
                    //$path = $backup_item->get_local_path() . $file['file_name'];
                    if(file_exists($path)) {
                        if(filesize($path) == $file['size']) {
                            if(WPvivid_taskmanager::get_download_task_v2($file['file_name']))
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            $ret['files'][$file['file_name']]['status']='completed';
                            $ret['files'][$file['file_name']]['size']=size_format(filesize($path),2);
                            $ret['files'][$file['file_name']]['download_path']=$path;
                            $download_url=$this->get_backup_url($backup_item, $file['file_name']);
                            $ret['files'][$file['file_name']]['download_url']=$download_url;
                            continue;
                        }
                    }
                    $ret['files'][$file['file_name']]['size']=size_format($file['size'],2);
                    if(empty($remote))
                    {
                        $ret['files'][$file['file_name']]['status']='file_not_found';
                    }
                    else {
                        $task = WPvivid_taskmanager::get_download_task_v2($file['file_name']);
                        if ($task === false)
                        {
                            $ret['files'][$file['file_name']]['status']='need_download';
                        } else {
                            $ret['result'] = WPVIVID_SUCCESS;
                            if($task['status'] === 'running')
                            {
                                $ret['files'][$file['file_name']]['status']='running';
                                $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                                if(file_exists($path)){
                                    $ret['files'][$file['file_name']]['downloaded_size']=size_format(filesize($path),2);
                                }
                                else{
                                    $ret['files'][$file['file_name']]['downloaded_size']='0';
                                }
                            }
                            elseif($task['status'] === 'timeout')
                            {
                                $ret['files'][$file['file_name']]['status']='timeout';
                                $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            }
                            elseif($task['status'] === 'completed')
                            {
                                $ret['files'][$file['file_name']]['status']='completed';
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            }
                            elseif($task['status'] === 'error')
                            {
                                $ret['files'][$file['file_name']]['status']='error';
                                $ret['files'][$file['file_name']]['error'] = $task['error'];
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            }
                        }
                    }
                }
            }
            else
            {
                $ret=$backup_files;
            }
        }
        return $ret;
    }

    public function ready_download($download_info)
    {
        //$backup=WPvivid_Backuplist::get_backup_by_id($download_info['backup_id']);
        $backup_list=new WPvivid_New_BackupList();
        $backup=$backup_list->get_backup_by_id($download_info['backup_id']);
        if(!$backup)
        {
            return false;
        }

        $file_info=false;

        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $file)
            {
                if ($file['file_name'] == $download_info['file_name'])
                {
                    $file_info= $file;
                    break;
                }
            }
        }
        else if ($backup['backup']['ismerge'] == 1)
        {
            $backup_files = $backup['backup']['data']['meta']['files'];
            foreach ($backup_files as $file)
            {
                if ($file['file_name'] == $download_info['file_name'])
                {
                    $file_info = $file;
                    break;
                }
            }
        } else {
            foreach ($backup['backup']['data']['type'] as $type)
            {
                $backup_files = $type['files'];
                foreach ($backup_files as $file) {
                    if ($file['file_name'] == $download_info['file_name'])
                    {
                        $file_info = $file;
                        break;
                    }
                }
            }
        }

        if($file_info==false)
        {
            return false;
        }

        $local_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $need_download_files=array();

        $local_file=$local_path.$file_info['file_name'];
        if(file_exists($local_file))
        {
            if(filesize($local_file)!=$file_info['size'])
            {
                if(filesize($local_file)>$file_info['size'])
                {
                    @unlink($local_file);
                }
                $need_download_files[$file_info['file_name']]=$file_info;
            }
        }
        else {
            $need_download_files[$file_info['file_name']]=$file_info;
        }


        if(empty($need_download_files))
        {
            delete_option('wpvivid_download_cache');
        }
        else
        {
            if(WPvivid_taskmanager::is_download_task_running_v2($download_info['file_name']))
            {
                global $wpvivid_backup_pro;
                $wpvivid_backup_pro->wpvivid_pro_log->WriteLog('has a downloading task,exit download.','test');
                return false;
            }
            else
            {
                WPvivid_taskmanager::delete_download_task_v2($download_info['file_name']);
                $task=WPvivid_taskmanager::new_download_task_v2($download_info['file_name']);
            }
        }

        foreach ($need_download_files as $file)
        {
            if($backup['type'] === 'Incremental')
            {
                if(isset($backup['incremental_path']) && !empty($backup['incremental_path']))
                {
                    foreach ($backup['remote'] as $remote_id => $remote_option)
                    {
                        if($remote_option['type']=='ftp'||$remote_option['type']=='sftp')
                        {
                            if(isset($remote_option['custom_path']))
                            {
                                $remote_option['custom_path']=$remote_option['custom_path'].'/'.$backup['incremental_path'];
                            }
                            else
                            {
                                $remote_option['path']=$remote_option['path'].'/'.$backup['incremental_path'];
                            }
                        }
                        else
                        {
                            $remote_option['path']=$remote_option['path'].'/'.$backup['incremental_path'];
                        }
                        $backup['remote'][$remote_id]=$remote_option;
                    }
                }
            }

            $ret=$this->download_ex($task,$backup['remote'],$file,$local_path);
            if($ret['result']==WPVIVID_FAILED)
            {
                return false;
            }
        }

        return true;
    }

    public function download_ex(&$task,$remotes,$file,$local_path)
    {
        $this->task=$task;

        $remote_option=array_shift($remotes);

        if(is_null($remote_option))
        {
            return array('result' => WPVIVID_FAILED ,'error'=>'Retrieving the cloud storage information failed while downloading backups. Please try again later.');
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $ret=$remote->download($file,$local_path,array($this,'download_callback_v2'));

        if($ret['result']==WPVIVID_SUCCESS)
        {
            $progress=100;
            $wpvivid_plugin->wpvivid_download_log->WriteLog('Download completed.', 'notice');
            WPvivid_taskmanager::update_download_task_v2( $task,$progress,'completed');
            return $ret;
        }
        else
        {
            $progress=0;
            $message=$ret['error'];
            if($wpvivid_plugin->wpvivid_download_log)
            {
                $wpvivid_plugin->wpvivid_download_log->WriteLog('Download failed, ' . $message ,'error');
                $wpvivid_plugin->wpvivid_download_log->CloseFile();
                WPvivid_error_log::create_error_log($wpvivid_plugin->wpvivid_download_log->log_file);
            }
            else {
                $id = uniqid('wpvivid-');
                $log_file_name = $id . '_download';
                $log = new WPvivid_Log_Ex_addon();
                $log->CreateLogFile($log_file_name, 'no_folder', 'download');
                $log->WriteLog($message, 'notice');
                $log->CloseFile();
                WPvivid_error_log::create_error_log($log->log_file);
            }
            WPvivid_taskmanager::update_download_task_v2($task,$progress,'error',$message);
            return $ret;
        }
    }

    public function download_callback_v2($offset,$current_name,$current_size,$last_time,$last_size)
    {
        global $wpvivid_plugin;
        $progress= floor(($offset/$current_size)* 100) ;
        $text='Total size:'.size_format($current_size,2).' downloaded:'.size_format($offset,2);
        $this->task['download_descript']=$text;
        $wpvivid_plugin->wpvivid_download_log->WriteLog('Total Size: '.$current_size.', Downloaded Size: '.$offset ,'notice');
        WPvivid_taskmanager::update_download_task_v2( $this->task,$progress,'running');
    }

    public function wpvivid_new_prepare_download_backup_addon_mainwp($data)
    {
        $backup_id = $data['backup_id'];
        $file_name = $data['file_name'];
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id))
        {
            $ret['error']='Invalid parameter param:'.$backup_id;
            return $ret;
        }
        if(!isset($file_name)||empty($file_name)||!is_string($file_name))
        {
            $ret['error']='Invalid parameter param:'.$file_name;
            return $ret;
        }
        $download_info=array();
        $download_info['backup_id']=sanitize_key($backup_id);
        $download_info['file_name'] = $file_name;

        @set_time_limit(600);
        if (session_id())
            session_write_close();
        try
        {
            //$downloader=new WPvivid_downloader();
            //$downloader->ready_download($download_info);
            $this->ready_download($download_info);
        }
        catch (Exception $e)
        {
            $message = 'A exception ('.get_class($e).') occurred '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
            error_log($message);
            return array('error'=>$message);
        }
        catch (Error $e)
        {
            $message = 'A error ('.get_class($e).') has occurred: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
            error_log($message);
            return array('error'=>$message);
        }

        $ret['result']='success';
        return $ret;
    }

    public function wpvivid_new_get_download_progress_addon_mainwp($data)
    {
        $backup_id = $data['backup_id'];
        $ret['result'] = WPVIVID_SUCCESS;
        $ret['files']=array();
        $ret['need_update']=false;

        $backup_list=new WPvivid_New_BackupList();
        $backup=$backup_list->get_backup_by_id($backup_id);
        if($backup===false)
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']='backup id not found';
            return $ret;
        }

        $backup_item=new WPvivid_New_Backup_Item($backup);

        //$backup_files=$backup_item->get_download_backup_files($backup_id);
        $files=array();
        $files=self::get_backup_files($backup);
        if(empty($files)){
            $ret['result']=WPVIVID_FAILED;
            $ret['error']='Failed to get backup files.';
        }
        else{
            $backup_files['result']=WPVIVID_SUCCESS;
            $backup_files['files']=$files;
            foreach ($backup_files['files'] as $file)
            {
                $ret['files'][$file['file_name']]['file_size']=size_format($file['size'],2);
                $path = $this->get_backup_path($backup_item, $file['file_name']);
                //$path = $backup_item->get_local_path() . $file['file_name'];
                if(file_exists($path)){
                    $ret['files'][$file['file_name']]['downloaded_size']=size_format(filesize($path),2);
                }
                else{
                    $ret['files'][$file['file_name']]['downloaded_size']='0';
                }
                $task = WPvivid_taskmanager::get_download_task_v2($file['file_name']);
                if ($task === false) {
                    $ret['files'][$file['file_name']]['status']='need_download';
                }
                else{
                    if($task['status'] === 'running') {
                        $ret['files'][$file['file_name']]['status'] = 'running';
                        $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                    }
                    elseif($task['status'] === 'timeout') {
                        $ret['files'][$file['file_name']]['status']='timeout';
                        $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                        WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                    }
                    elseif($task['status'] === 'completed') {
                        $ret['files'][$file['file_name']]['status']='completed';
                        WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                    }
                    elseif($task['status'] === 'error') {
                        $ret['files'][$file['file_name']]['status']='error';
                        $ret['files'][$file['file_name']]['error'] = $task['error'];
                        WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                    }
                }
            }
        }

        return $ret;
    }

    public function download_backup_addon_mainwp()
    {
        try {
            if (isset($_REQUEST['backup_id']) && isset($_REQUEST['file_name'])) {
                if (!empty($_REQUEST['backup_id']) && is_string($_REQUEST['backup_id'])) {
                    $backup_id = sanitize_key($_REQUEST['backup_id']);
                } else {
                    die();
                }

                if (!empty($_REQUEST['file_name']) && is_string($_REQUEST['file_name'])) {
                    //$file_name=sanitize_file_name($_REQUEST['file_name']);
                    $file_name = $_REQUEST['file_name'];
                } else {
                    die();
                }

                $cache = WPvivid_taskmanager::get_download_cache($backup_id);
                if ($cache === false) {
                    $this->init_download($backup_id);
                    $cache = WPvivid_taskmanager::get_download_cache($backup_id);
                }
                $path = false;
                if (array_key_exists($file_name, $cache['files'])) {
                    if ($cache['files'][$file_name]['status'] == 'completed') {
                        $path = $cache['files'][$file_name]['download_path'];
                    }
                }
                if ($path !== false) {
                    if (file_exists($path)) {
                        if (session_id())
                            session_write_close();

                        $size = filesize($path);
                        if (!headers_sent()) {
                            header('Content-Description: File Transfer');
                            header('Content-Type: application/zip');
                            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                            header('Cache-Control: must-revalidate');
                            header('Content-Length: ' . $size);
                            header('Content-Transfer-Encoding: binary');
                        }

                        if ($size < 1024 * 1024 * 60) {
                            ob_end_clean();
                            readfile($path);
                            exit;
                        } else {
                            ob_end_clean();
                            $download_rate = 1024 * 10;
                            $file = fopen($path, "r");
                            while (!feof($file)) {
                                @set_time_limit(20);
                                // send the current file part to the browser
                                print fread($file, round($download_rate * 1024));
                                // flush the content to the browser
                                ob_flush();
                                flush();

                                // sleep one second
                                sleep(1);
                            }
                            fclose($file);
                            exit;
                        }
                    }
                }
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        $admin_url = admin_url();
        echo '<a href="'.$admin_url.'admin.php?page=WPvivid">file not found. please retry again.</a>';
        die();
    }

    public function wpvivid_set_security_lock_addon_mainwp($data){
        $backup_id = $data['backup_id'];
        if ($data['lock'] == 0 || $data['lock'] == 1) {
            $lock = $data['lock'];
        }
        else {
            $lock = 0;
        }

        $backup = WPvivid_Backuplist::get_backuplist_by_id($data['backup_id']);
        if($backup !== false)
        {
            $list = $backup['list_data'];
            if (array_key_exists($backup_id, $list))
            {
                $ret['result'] = 'success';
                if ($lock == 1)
                {
                    $list[$backup_id]['lock'] = 1;
                    $lock_status = 'lock';
                }
                else {
                    if (array_key_exists('lock', $list[$backup_id]))
                    {
                        unset($list[$backup_id]['lock']);
                    }
                    $lock_status = 'unlock';
                }
                $ret['lock_status'] = $lock_status;
                update_option($backup['list_name'], $list, 'no');
            }
            else
            {
                $ret['result'] = 'failed';
                $ret['error']='backup not found';
            }
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error']='backup not found';
        }

        return $ret;
    }

    public function wpvivid_set_remote_security_lock_addon_mainwp($data){
        $backup_id = $data['backup_id'];
        if ($data['lock'] == 0 || $data['lock'] == 1) {
            $lock = $data['lock'];
        }
        else {
            $lock = 0;
        }
        $backup_lock=get_option('wpvivid_remote_backups_lock');

        if($lock)
        {
            $backup_lock[$backup_id]=1;
            $lock_status = 'lock';
        }
        else
        {
            unset($backup_lock[$backup_id]);
            $lock_status = 'unlock';
        }

        update_option('wpvivid_remote_backups_lock',$backup_lock,'no');

        $ret['result'] = 'success';
        $ret['lock_status'] = $lock_status;

        return $ret;
    }

    public function wpvivid_delete_local_backup_addon_mainwp($data){
        $backup_id = $data['backup_id'];
        //$backup=WPvivid_Backuplist::get_backup_by_id($backup_id);
        $backup_list=new WPvivid_New_BackupList();
        $backup=$backup_list->get_backup_by_id($backup_id);
        if(!$backup) {
            $ret['result']='failed';
            $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid');
            return $ret;
        }
        $backup_item=new WPvivid_New_Backup_Item($backup);
        $files=$backup_item->get_files();
        foreach ($files as $file) {
            if (file_exists($file))
            {
                @unlink($file);
            }
        }
        WPvivid_Backuplist::delete_backup($backup_id);

        $backup_folder = $data['folder'];
        $backuplist=WPvivid_Backuplist::get_backuplist('wpvivid_backup_list');
        if($backup_folder === 'wpvivid')
        {
            $localbackuplist=array();
            foreach ($backuplist as $key=>$value)
            {
                if($value['type'] === 'Rollback' || $value['type'] === 'Incremental')
                {
                    continue;
                }
                else
                {
                    $localbackuplist[$key]=$value;
                }
            }
            $ret['list_data']=$localbackuplist;
        }
        elseif($backup_folder === 'rollback')
        {
            $rollbackuplist=array();
            foreach ($backuplist as $key=>$value)
            {
                if($value['type'] === 'Rollback')
                {
                    $rollbackuplist[$key]=$value;
                }
            }
            $ret['list_data']=$rollbackuplist;
        }
        elseif($backup_folder === 'incremental')
        {
            $incrementalbackuplist=array();
            foreach ($backuplist as $key=>$value)
            {
                if($value['type'] === 'Incremental')
                {
                    $incrementalbackuplist[$key]=$value;
                }
            }
            $ret['list_data']=$incrementalbackuplist;
        }
        $ret['result']='success';
        return $ret;
    }

    public function wpvivid_delete_local_backup_array_addon_mainwp($data){
        $backup_ids = $data['backup_id'];
        foreach ($backup_ids as $backup_id) {
            //$backup=WPvivid_Backuplist::get_backup_by_id($backup_id);
            $backup_list=new WPvivid_New_BackupList();
            $backup=$backup_list->get_backup_by_id($backup_id);
            if(!$backup)
            {
                continue;
            }
            $backup_item=new WPvivid_New_Backup_Item($backup);
            $files=$backup_item->get_files();
            foreach ($files as $file) {
                if (file_exists($file))
                {
                    @unlink($file);
                }
            }
            WPvivid_Backuplist::delete_backup($backup_id);
        }

        $backup_folder = $data['folder'];
        $html='';
        $backuplist=WPvivid_Backuplist::get_backuplist('wpvivid_backup_list');
        if($backup_folder === 'wpvivid')
        {
            $localbackuplist=array();
            foreach ($backuplist as $key=>$value)
            {
                if($value['type'] === 'Rollback' || $value['type'] === 'Incremental')
                {
                    continue;
                }
                else
                {
                    $localbackuplist[$key]=$value;
                }
            }
            $ret['list_data']=$localbackuplist;
        }
        elseif($backup_folder === 'rollback')
        {
            $rollbackuplist=array();
            foreach ($backuplist as $key=>$value)
            {
                if($value['type'] === 'Rollback')
                {
                    $rollbackuplist[$key]=$value;
                }
            }
            $ret['list_data']=$rollbackuplist;
        }
        elseif($backup_folder === 'incremental')
        {
            $incrementalbackuplist=array();
            foreach ($backuplist as $key=>$value)
            {
                if($value['type'] === 'Incremental')
                {
                    $incrementalbackuplist[$key]=$value;
                }
            }
            $ret['list_data']=$incrementalbackuplist;
        }
        $ret['result']='success';
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

    public function get_backup_url($backup_item, $file_name)
    {
        $path = $backup_item->get_local_path() . $file_name;

        if (file_exists($path)) {
            return $backup_item->get_local_url() . $file_name;
        }
        else{
            $local_setting = get_option('wpvivid_local_setting', array());
            if(!empty($local_setting))
            {
                $url = content_url().DIRECTORY_SEPARATOR.$local_setting['path'].DIRECTORY_SEPARATOR.$file_name;
            }
            else {
                $url = content_url().DIRECTORY_SEPARATOR.'wpvividbackups'.DIRECTORY_SEPARATOR.$file_name;
            }
        }
        return $url;
    }

    public function wpvivid_delete_remote_backup_addon_mainwp($data){
        $backup_id = $data['backup_id'];
        //$backup=WPvivid_Backuplist::get_backup_by_id($backup_id);
        $backup_list=new WPvivid_New_BackupList();
        $backup=$backup_list->get_backup_by_id($backup_id);
        if(!$backup) {
            $ret['result']='failed';
            $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid');
            return $ret;
        }
        $backup_item=new WPvivid_New_Backup_Item($backup);
        $files=array();
        if(isset($backup['backup']['files']))
        {
            //file_name
            foreach ($backup['backup']['files'] as $file)
            {
                $files[]=$file;

                if (file_exists($this->get_backup_path($backup_item, $file['file_name'])))
                {
                    @unlink($this->get_backup_path($backup_item, $file['file_name']));
                }
            }
        }
        else
        {
            $files=$backup_item->get_files();
            foreach ($files as $file)
            {
                if (file_exists($file))
                {
                    @unlink($file);
                }
            }
        }

        WPvivid_Backuplist::delete_backup($backup_id);
        if(!empty($backup['remote']))
        {
            foreach($backup['remote'] as $remote)
            {
                WPvivid_downloader::delete($remote,$files);
            }
        }

        $remote_folder = $data['folder'];
        $ret['local_cache_files_size'] = apply_filters('wpvivid_get_local_cache_files_size', 0);
        $list=get_option('wpvivid_remote_list', array());
        $remote_list=array();

        if($remote_folder === 'Incremental'){
            $remote_folder = 'Common';
        }

        foreach ($list as $key=>$item)
        {
            if($item['type']==$remote_folder)
            {
                $remote_list[$key]=$item;
            }
        }
        $ret['list_data']=$remote_list;
        $ret['result']='success';
        return $ret;
    }

    public function wpvivid_delete_remote_backup_array_addon_mainwp($data){
        $backup_ids = $data['backup_id'];
        foreach ($backup_ids as $backup_id){
            @set_time_limit(45);
            //$backup=WPvivid_Backuplist::get_backup_by_id($backup_id);
            $backup_list=new WPvivid_New_BackupList();
            $backup=$backup_list->get_backup_by_id($backup_id);
            if(!$backup) {
                continue;
            }
            $backup_item=new WPvivid_New_Backup_Item($backup);
            $files=$backup_item->get_files();
            foreach ($files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            if(!empty($backup['remote'])) {
                WPvivid_Backuplist::delete_backup($backup_id);
                $files=$backup_item->get_files(false);
                foreach($backup['remote'] as $remote) {
                    WPvivid_downloader::delete($remote,$files);
                }
            }
            else {
                WPvivid_Backuplist::delete_backup($backup_id);
            }
        }
        $remote_folder = $data['folder'];
        $ret['local_cache_files_size'] = apply_filters('wpvivid_get_local_cache_files_size', 0);
        $list=get_option('wpvivid_remote_list', array());
        $remote_list=array();

        if($remote_folder === 'Incremental'){
            $remote_folder = 'Common';
        }

        foreach ($list as $key=>$item)
        {
            if($item['type']==$remote_folder)
            {
                $remote_list[$key]=$item;
            }
        }

        $ret['list_data']=$remote_list;
        $ret['result']='success';
        return $ret;
    }

    public function wpvivid_view_log_addon_mainwp($data){
        global $wpvivid_plugin;
        $log = $data['log'];
        $loglist=$wpvivid_plugin->get_log_list_ex();

        if(isset($loglist['log_list']['file'][$log]))
        {
            $log=$loglist['log_list']['file'][$log];
        }
        else
        {
            $ret['result'] = 'failed';
            $ret['error'] = __('Log does not exist. It might have been deleted or lost during a website migration.', 'wpvivid');
            return $ret;
        }

        $path=$log['path'];

        if (!file_exists($path))
        {
            $ret['result'] = 'failed';
            $ret['error'] = __('Log does not exist. It might have been deleted or lost during a website migration.', 'wpvivid');
            return $ret;
        }

        $file = fopen($path, 'r');

        if (!$file) {
            $ret['result'] = 'failed';
            $ret['error'] = __('Unable to open the log file.', 'wpvivid');
            return $ret;
        }

        $buffer = '';
        while (!feof($file)) {
            $buffer .= fread($file, 1024);
        }
        fclose($file);

        $ret['result'] = 'success';
        $ret['data'] = $buffer;
        return $ret;
    }

    public function wpvivid_init_download_page_addon_mainwp($data){
        $backup_id = $data['backup_id'];
        global $wpvivid_plugin;
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id)) {
            $ret['error']='Invalid parameter param:'.$backup_id;
            return $ret;
        }
        else {
            //$backup=WPvivid_Backuplist::get_backup_by_id($backup_id);
            $backup_list=new WPvivid_New_BackupList();
            $backup=$backup_list->get_backup_by_id($backup_id);
            if($backup===false)
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['error']='backup id not found';
                return $ret;
            }
            $backup_item=new WPvivid_New_Backup_Item($backup);
            $backup_files=$backup_item->get_download_backup_files($backup_id);
            if($backup_files['result']==WPVIVID_SUCCESS){
                $ret['result'] = WPVIVID_SUCCESS;
                $remote=$backup_item->get_remote();
                foreach ($backup_files['files'] as $file){
                    $path = $this->get_backup_path($backup_item, $file['file_name']);
                    //$path = $backup_item->get_local_path() . $file['file_name'];
                    if(file_exists($path)) {
                        if(filesize($path) == $file['size']) {
                            if(WPvivid_taskmanager::get_download_task_v2($file['file_name']))
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            $ret['files'][$file['file_name']]['status']='completed';
                            $ret['files'][$file['file_name']]['size']=size_format(filesize($path),2);
                            $ret['files'][$file['file_name']]['download_path']=$path;
                            $download_url=$this->get_backup_url($backup_item, $file['file_name']);
                            $ret['files'][$file['file_name']]['download_url']=$download_url;
                            continue;
                        }
                    }
                    $ret['files'][$file['file_name']]['size']=size_format($file['size'],2);
                    if(empty($remote))
                    {
                        $ret['files'][$file['file_name']]['status']='file_not_found';
                    }
                    else {
                        $task = WPvivid_taskmanager::get_download_task_v2($file['file_name']);
                        if ($task === false)
                        {
                            $ret['files'][$file['file_name']]['status']='need_download';
                        } else {
                            $ret['result'] = WPVIVID_SUCCESS;
                            if($task['status'] === 'running')
                            {
                                $ret['files'][$file['file_name']]['status']='running';
                                $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                                if(file_exists($path)){
                                    $ret['files'][$file['file_name']]['downloaded_size']=size_format(filesize($path),2);
                                }
                                else{
                                    $ret['files'][$file['file_name']]['downloaded_size']='0';
                                }
                            }
                            elseif($task['status'] === 'timeout')
                            {
                                $ret['files'][$file['file_name']]['status']='timeout';
                                $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            }
                            elseif($task['status'] === 'completed')
                            {
                                $ret['files'][$file['file_name']]['status']='completed';
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            }
                            elseif($task['status'] === 'error')
                            {
                                $ret['files'][$file['file_name']]['status']='error';
                                $ret['files'][$file['file_name']]['error'] = $task['error'];
                                WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                            }
                        }
                    }
                }
            }
            else
            {
                $ret=$backup_files;
            }
        }
        return $ret;
    }

    public function wpvivid_prepare_download_backup_addon_mainwp($data){
        $backup_id = $data['backup_id'];
        $file_name = $data['file_name'];
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id))
        {
            $ret['error']='Invalid parameter param:'.$backup_id;
            return $ret;
        }
        if(!isset($file_name)||empty($file_name)||!is_string($file_name))
        {
            $ret['error']='Invalid parameter param:'.$file_name;
            return $ret;
        }
        $download_info=array();
        $download_info['backup_id']=sanitize_key($backup_id);
        $download_info['file_name'] = $file_name;

        @set_time_limit(600);
        if (session_id())
            session_write_close();
        try
        {
            $downloader=new WPvivid_downloader();
            $downloader->ready_download($download_info);
        }
        catch (Exception $e)
        {
            $message = 'A exception ('.get_class($e).') occurred '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
            error_log($message);
            return array('error'=>$message);
        }
        catch (Error $e)
        {
            $message = 'A error ('.get_class($e).') has occurred: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
            error_log($message);
            return array('error'=>$message);
        }

        $ret['result']='success';
        return $ret;
    }

    public function wpvivid_get_download_progress_addon_mainwp($data){
        $backup_id = $data['backup_id'];
        $ret['result'] = WPVIVID_SUCCESS;
        $ret['files']=array();
        $ret['need_update']=false;

        //$backup=WPvivid_Backuplist::get_backup_by_id($backup_id);
        $backup_list=new WPvivid_New_BackupList();
        $backup=$backup_list->get_backup_by_id($backup_id);
        if($backup===false)
        {
            $ret['result']=WPVIVID_FAILED;
            $ret['error']='backup id not found';
            return $ret;
        }

        $backup_item=new WPvivid_New_Backup_Item($backup);

        $backup_files=$backup_item->get_download_backup_files($backup_id);
        foreach ($backup_files['files'] as $file)
        {
            $ret['files'][$file['file_name']]['file_size']=size_format($file['size'],2);
            $path = $this->get_backup_path($backup_item, $file['file_name']);
            //$path = $backup_item->get_local_path() . $file['file_name'];
            if(file_exists($path)){
                $ret['files'][$file['file_name']]['downloaded_size']=size_format(filesize($path),2);
            }
            else{
                $ret['files'][$file['file_name']]['downloaded_size']='0';
            }
            $task = WPvivid_taskmanager::get_download_task_v2($file['file_name']);
            if ($task === false) {
                $ret['files'][$file['file_name']]['status']='need_download';
            }
            else{
                if($task['status'] === 'running') {
                    $ret['files'][$file['file_name']]['status'] = 'running';
                    $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                }
                elseif($task['status'] === 'timeout') {
                    $ret['files'][$file['file_name']]['status']='timeout';
                    $ret['files'][$file['file_name']]['progress_text']=$task['progress_text'];
                    WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                }
                elseif($task['status'] === 'completed') {
                    $ret['files'][$file['file_name']]['status']='completed';
                    WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                }
                elseif($task['status'] === 'error') {
                    $ret['files'][$file['file_name']]['status']='error';
                    $ret['files'][$file['file_name']]['error'] = $task['error'];
                    WPvivid_taskmanager::delete_download_task_v2($file['file_name']);
                }
            }
        }
        return $ret;
    }

    public function wpvivid_rescan_local_folder_addon_mainwp($data){
        $upload_addon = new Wpvivid_BackupUploader_addon();
        $ret = $upload_addon->_rescan_local_folder_set_backup();
        return $ret;
    }

    /***** wpvivid mainwp schedule filter *****/
    public static function archieve_schedules_info_mainwp(){
        $default = array();
        $schedules = get_option('wpvivid_schedule_addon_setting', $default);
        $schedules_list = array();
        foreach ($schedules as $schedule)
        {
            $recurrence = wp_get_schedules();
            if (isset($recurrence[$schedule['type']]))
            {
                $schedule_type = $recurrence[$schedule['type']]['display'];
                if ($schedule_type === 'Weekly')
                {
                    if (isset($schedule['week']))
                    {
                        if ($schedule['week'] === 'sun')
                        {
                            $schedule_type = $schedule_type . '-Sunday';
                        } else if ($schedule['week'] === 'mon')
                        {
                            $schedule_type = $schedule_type . '-Monday';
                        } else if ($schedule['week'] === 'tue')
                        {
                            $schedule_type = $schedule_type . '-Tuesday';
                        } else if ($schedule['week'] === 'wed') {

                            $schedule_type = $schedule_type . '-Wednesday';
                        } else if ($schedule['week'] === 'thu')
                        {
                            $schedule_type = $schedule_type . '-Thursday';
                        } else if ($schedule['week'] === 'fri')
                        {
                            $schedule_type = $schedule_type . '-Friday';
                        } else if ($schedule['week'] === 'sat')
                        {
                            $schedule_type = $schedule_type . '-Saturday';
                        }
                    }
                }
            } else {
                $schedule_type = 'not found';
            }
            $schedule['schedule_cycles'] = $schedule_type;

            if (isset($schedule['last_backup_time']))
            {
                $offset=get_option('gmt_offset');
                $localtime = $schedule['last_backup_time'] + $offset * 60 * 60;
                $last_backup_time = date("H:i:s - F-d-Y ", $localtime);
            } else {
                $last_backup_time = 'N/A';
            }
            $schedule['last_backup_time'] = $last_backup_time;

            if ($schedule['status'] == 'Active')
            {
                $timestamp = wp_next_scheduled($schedule['id'], array($schedule['id']));
                if($timestamp===false)
                {
                    if(isset($schedule['week']))
                    {
                        $time['start_time']['week']=$schedule['week'];
                    }
                    else
                        $time['start_time']['week']='mon';

                    if(isset($schedule['day']))
                    {
                        $schedule_data['day']=$schedule['day'];
                    }
                    else
                        $time['start_time']['day']='01';

                    if(isset($schedule['current_day']))
                    {
                        $schedule_data['current_day']=$schedule['current_day'];
                    }
                    else
                        $time['start_time']['current_day']="00:00";

                    $timestamp=WPvivid_Schedule_addon::get_start_time($time);
                    $schedule_data['start_time']=$timestamp;

                    wp_schedule_event($schedule['start_time'], $schedule['type'], $schedule['id'],array($schedule['id']));

                    $offset = get_option('gmt_offset');
                    $localtime = $schedule['start_time'] + $offset * 60 * 60;
                    $next_start = date("H:i:s - F-d-Y ", $localtime);
                }
                else {
                    $schedule['next_start'] = $timestamp;
                    $offset = get_option('gmt_offset');
                    $localtime = $schedule['next_start'] + $offset * 60 * 60;
                    $next_start = date("H:i:s - F-d-Y ", $localtime);
                }
            } else {
                $next_start = 'N/A';
            }
            $schedule['next_start_time'] = $next_start;

            if(isset($schedule['current_day'])){
                $dt = DateTime::createFromFormat("H:i", $schedule['current_day']);
                $offset=get_option('gmt_offset');
                $hours=$dt->format('H');
                $minutes=$dt->format('i');

                $hour=(float)$hours+$offset;

                $whole = floor($hour);
                $fraction = $hour - $whole;
                $minute=(float)(60*($fraction))+(int)$minutes;

                $hour=(int)$hour;
                $minute=(int)$minute;

                if($minute>=60)
                {
                    $hour=(int)$hour+1;
                    $minute=(int)$minute-60;
                }

                if($hour>=24)
                {
                    $hour=$hour-24;
                }
                else if($hour<0)
                {
                    $hour=24-abs ($hour);
                }

                if($hour<10)
                {
                    $hour='0'.(int)$hour;
                }
                else
                {
                    $hour=(string)$hour;
                }

                if($minute<10)
                {
                    $minute='0'.(int)$minute;
                }
                else
                {
                    $minute=(string)$minute;
                }

                $schedule['hours']=$hour;
                $schedule['minute']=$minute;
            }
            else{
                $schedule['hours']='00';
                $schedule['minute']='00';
            }

            if(isset($schedule['backup']['remote_id']))
            {
                $remote_id=$schedule['backup']['remote_id'];
                $remote_list = get_option('wpvivid_upload_setting');
                if(isset($remote_list[$remote_id]))
                {
                    $tmp_remote_option=array();
                    $tmp_remote_option[$remote_id]=$remote_list[$remote_id];
                    $schedule['backup']['remote_options']=$tmp_remote_option;
                }
            }
            $schedules_list[] = $schedule;
        }

        uasort($schedules_list, function ($a, $b)
        {
            $a_timestamp = wp_next_scheduled($a['id'], array($a['id']));
            $a['next_start'] = $a_timestamp;
            $b_timestamp = wp_next_scheduled($b['id'], array($b['id']));
            $b['next_start'] = $b_timestamp;
            if ($a['next_start'] > $b['next_start'])
            {
                return 1;
            } else if ($a['next_start'] === $b['next_start'])
            {
                return 0;
            } else {
                return -1;
            }
        });
        return $schedules_list;
    }

    public static function wpvivid_enable_schedule(){
        //update_option('wpvivid_enable_schedules', true);
        update_option('wpvivid_enable_incremental_schedules', false, 'no');

        $need_remove_schedules = array();
        $crons = _get_cron_array();
        foreach ($crons as $cronhooks) {
            foreach ($cronhooks as $hook_name => $hook_schedules) {
                if (preg_match('#wpvivid_incremental_.*#', $hook_name)) {
                    foreach ($hook_schedules as $data) {
                        $need_remove_schedules[$hook_name] = $data['args'];
                    }
                }
            }
        }

        foreach ($need_remove_schedules as $hook_name => $args) {
            wp_clear_scheduled_hook($hook_name, $args);
            $timestamp = wp_next_scheduled($hook_name, $args);
            wp_unschedule_event($timestamp, $hook_name, array($args));
        }
    }

    public function wpvivid_get_schedules_addon_mainwp($data){
        $ret['result'] = 'success';
        $ret['schedule_info'] = self::archieve_schedules_info_mainwp();
        return $ret;
    }

    public function wpvivid_create_schedule_addon_mainwp($data){
        $schedule_addon = new WPvivid_Schedule_Display_Addon();
        $json = $data['schedule'];
        $json = stripslashes($json);
        $schedule = json_decode($json, true);

        if(isset($schedule['custom_dirs']['themes_list'])){
            foreach ($schedule['custom_dirs']['themes_list'] as $index => $theme){
                if(!isset($value['type'])){
                    $schedule['custom_dirs']['themes_list'][$theme]['name'] = $theme;
                    $schedule['custom_dirs']['themes_list'][$theme]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    unset($schedule['custom_dirs']['themes_list'][$index]);
                }
            }
        }
        if(isset($schedule['custom_dirs']['plugins_list'])){
            foreach ($schedule['custom_dirs']['plugins_list'] as $index => $plugin){
                if(!isset($value['type'])){
                    $schedule['custom_dirs']['plugins_list'][$plugin]['name'] = $plugin;
                    $schedule['custom_dirs']['plugins_list'][$plugin]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    unset($schedule['custom_dirs']['plugins_list'][$index]);
                }
            }
        }

        $ret = $schedule_addon->check_schedule_option($schedule);
        if($ret['result']!=WPVIVID_SUCCESS)
        {
            return $ret;
        }
        $ret=$schedule_addon->add_schedule($ret['schedule']);
        self::wpvivid_enable_schedule();
        $ret['schedule_info'] = self::archieve_schedules_info_mainwp();
        $ret['enable_incremental_schedules'] = get_option('wpvivid_enable_incremental_schedules',false);
        $ret['incremental_schedules'] = get_option('wpvivid_incremental_schedules', array());
        $ret['incremental_backup_data'] = get_option('wpvivid_incremental_backup_data', array());
        $ret['incremental_output_msg']=$this->wpvivid_get_incremental_output_msg();
        return $ret;
    }

    public function wpvivid_update_schedule_addon_mainwp($data){
        $schedule_addon = new WPvivid_Schedule_Display_Addon();
        $json = $data['schedule'];
        $json = stripslashes($json);
        $schedule = json_decode($json, true);

        if(isset($schedule['custom_dirs']['themes_list'])){
            foreach ($schedule['custom_dirs']['themes_list'] as $index => $theme){
                if(!isset($value['type'])){
                    $schedule['custom_dirs']['themes_list'][$theme]['name'] = $theme;
                    $schedule['custom_dirs']['themes_list'][$theme]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    unset($schedule['custom_dirs']['themes_list'][$index]);
                }
            }
        }
        if(isset($schedule['custom_dirs']['plugins_list'])){
            foreach ($schedule['custom_dirs']['plugins_list'] as $index => $plugin){
                if(!isset($value['type'])){
                    $schedule['custom_dirs']['plugins_list'][$plugin]['name'] = $plugin;
                    $schedule['custom_dirs']['plugins_list'][$plugin]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    unset($schedule['custom_dirs']['plugins_list'][$index]);
                }
            }
        }

        if(!isset($schedule['update_schedule_backup_save_local_remote']))
        {
            if(isset($schedule['save_local_remote']))
            {
                $schedule['update_schedule_backup_save_local_remote'] = $schedule['save_local_remote'];
            }
        }

        $ret = $schedule_addon->check_update_schedule_option($schedule);
        if($ret['result']!=WPVIVID_SUCCESS)
        {
            return $ret;
        }

        $schedule_id=$schedule['schedule_id'];
        $schedules=get_option('wpvivid_schedule_addon_setting');

        $schedules[$schedule_id]['type'] = $ret['schedule']['recurrence'];
        if(isset($ret['schedule']['week']))
        {
            $schedules[$schedule_id]['week'] = $ret['schedule']['week'];
        }
        if(isset($ret['schedule']['day']))
        {
            $schedules[$schedule_id]['day'] = $ret['schedule']['day'];
        }
        $schedules[$schedule_id]['current_day'] = $ret['schedule']['current_day'];

        $time['type']=$ret['schedule']['recurrence'];
        if(isset($schedules[$schedule_id]['week']))
            $time['start_time']['week']=$schedules[$schedule_id]['week'];
        else
            $time['start_time']['week']='mon';

        if(isset($schedules[$schedule_id]['day']))
            $time['start_time']['day']=$schedules[$schedule_id]['day'];
        else
            $time['start_time']['day']='01';

        if(isset($schedules[$schedule_id]['current_day']))
            $time['start_time']['current_day']=$schedules[$schedule_id]['current_day'];
        else
            $time['start_time']['current_day']="00:00";
        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
        $schedules[$schedule_id]['start_time']=$timestamp;

        $ret['schedule']['backup']['ismerge'] = 1;
        $ret['schedule']['backup']['lock'] = 0;
        $schedules[$schedule_id]['backup'] = $ret['schedule']['backup'];

        if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
        {
            $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
            wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
        }
        if(wp_schedule_event($schedules[$schedule_id]['start_time'], $schedules[$schedule_id]['type'], $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']))===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Failed to update the schedule. Please try again later.', 'wpvivid');
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], true);
        }
        else {
            update_option('wpvivid_schedule_addon_setting',$schedules,'no');
            $ret['result']='success';
            $ret['schedule_info'] = self::archieve_schedules_info_mainwp();
        }

        return $ret;
    }

    public function wpvivid_delete_schedule_addon_mainwp($data){
        $schedule_id = $data['schedule_id'];
        $schedules=get_option('wpvivid_schedule_addon_setting');
        unset($schedules[$schedule_id]);

        if(wp_get_schedule($schedule_id, array($schedule_id)))
        {
            wp_clear_scheduled_hook($schedule_id, array($schedule_id));
            $timestamp = wp_next_scheduled($schedule_id, array($schedule_id));
            wp_unschedule_event($timestamp, $schedule_id, array($schedule_id));
        }
        update_option('wpvivid_schedule_addon_setting',$schedules,'no');

        $ret['result']='success';
        $ret['schedule_info'] = self::archieve_schedules_info_mainwp();
        return $ret;
    }

    public function wpvivid_save_schedule_status_addon_mainwp($data){
        try
        {
            $need_enable_schedule = false;
            $json_schedule_data = $data['schedule_data'];
            $json_schedule_data = stripslashes($json_schedule_data);
            $schedule_data = json_decode($json_schedule_data, true);
            $schedules=get_option('wpvivid_schedule_addon_setting');
            foreach ($schedule_data as $schedule_id => $schedule_status)
            {
                $schedules[$schedule_id]['status'] = $schedule_status;

                if($schedule_status === 'Active')
                {
                    $need_enable_schedule = true;
                    if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                    {
                        $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    }
                    if (wp_schedule_event($schedules[$schedule_id]['start_time'], $schedules[$schedule_id]['type'], $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])) === false) {
                        $ret['result'] = 'failed';
                        $ret['error'] = __('Failed to save the schedule. Please try again later.', 'wpvivid');
                        $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], true);
                        return $ret;
                    }
                }
                else
                {
                    if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                    {
                        $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    }
                }
            }
            update_option('wpvivid_schedule_addon_setting', $schedules, 'no');
            if($need_enable_schedule){
                self::wpvivid_enable_schedule();
            }
            $ret['result']='success';
            $ret['schedule_info'] = self::archieve_schedules_info_mainwp();
            $ret['enable_incremental_schedules'] = get_option('wpvivid_enable_incremental_schedules',false);
            $ret['incremental_schedules'] = get_option('wpvivid_incremental_schedules', array());
            $ret['incremental_backup_data'] = get_option('wpvivid_incremental_backup_data', array());
            $ret['incremental_output_msg']=$this->wpvivid_get_incremental_output_msg();
            return $ret;

        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], true);
            return $ret;
        }
    }

    public function wpvivid_sync_schedule_addon_mainwp($data){
        if(isset($data['schedule']) && !empty($data['schedule'])){
            $new_schedules = $data['schedule'];
            $old_schedules=get_option('wpvivid_schedule_addon_setting');
            if(!empty($old_schedules)) {
                if (isset($data['default_setting'])) {
                    $default_setting = $data['default_setting'];
                } else {
                    $default_setting = 'default_only';
                }
                if ($default_setting === 'default_only') {
                    foreach ($old_schedules as $old_schedule_id => $schedule) {
                        $old_schedules[$old_schedule_id]['status'] = 'InActive';
                        if (wp_get_schedule($old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']))) {
                            wp_clear_scheduled_hook($old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']));
                            $timestamp = wp_next_scheduled($old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']));
                            wp_unschedule_event($timestamp, $old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']));
                        }
                        unset($old_schedules[$old_schedule_id]);
                    }
                }
            }
            foreach ($new_schedules as $new_schedule_id => $new_schedule){
                if(isset($new_schedule['start_time_local_utc'])){
                    $start_time_local_utc = $new_schedule['start_time_local_utc'];
                }
                else{
                    $start_time_local_utc = 'utc';
                }
                $time['type'] = $new_schedule['type'];
                $time['start_time']['week'] = $new_schedule['week'];
                $time['start_time']['day'] = $new_schedule['day'];
                /*if($start_time_local_utc === 'utc') {
                    $time['start_time']['current_day'] = $new_schedule['current_day'];
                }
                else{
                    $offset=get_option('gmt_offset');
                    $utc_time = strtotime($new_schedule['current_day']) - $offset * 60 * 60;
                    $time['start_time']['current_day'] = date("H:i", $utc_time);
                }*/
                $time['start_time']['current_day'] = $new_schedule['current_day'];
                $timestamp = WPvivid_Schedule_addon::get_start_time($time);
                $new_schedules[$new_schedule_id]['current_day'] = $time['start_time']['current_day'];
                $new_schedules[$new_schedule_id]['start_time'] = $timestamp;
                if (isset($new_schedule['backup']['backup_select'])) {
                    $new_schedule['backup']['backup_select']['db'] = intval($new_schedule['backup']['backup_select']['db']);
                    $new_schedule['backup']['backup_select']['themes'] = intval($new_schedule['backup']['backup_select']['themes']);
                    $new_schedule['backup']['backup_select']['plugin'] = intval($new_schedule['backup']['backup_select']['plugin']);
                    $new_schedule['backup']['backup_select']['uploads'] = intval($new_schedule['backup']['backup_select']['uploads']);
                    $new_schedule['backup']['backup_select']['content'] = intval($new_schedule['backup']['backup_select']['content']);
                    $new_schedule['backup']['backup_select']['core'] = intval($new_schedule['backup']['backup_select']['core']);
                    $new_schedules[$new_schedule_id]['backup']['backup_select'] = $new_schedule['backup']['backup_select'];
                }
                unset($new_schedules[$new_schedule_id]['start_time_local_utc']);

                if(isset($new_schedule['backup']['exclude_files']))
                {
                    $exclude_path=explode("\n", $new_schedule['backup']['exclude_files']);
                    unset($new_schedule['backup']['exclude_files']);
                    $new_schedule['backup']['exclude_files'] = array();
                    foreach ($exclude_path as $item)
                    {
                        $item = str_replace('/wp-content', WP_CONTENT_DIR, $item);
                        $item = str_replace('\\', '/', $item);
                        if(file_exists($item))
                        {
                            $arr['path'] = $item;
                            if(is_dir($item)){
                                $arr['type'] = 'folder';
                            }
                            if(is_file($item)){
                                $arr['type'] = 'file';
                            }
                            $new_schedule['backup']['exclude_files'][] = $arr;
                        }
                    }
                    $new_schedules[$new_schedule_id]['backup']['exclude_files'] = $new_schedule['backup']['exclude_files'];
                }
            }
            if(!empty($old_schedules)) {
                $new_schedules = array_merge($new_schedules, $old_schedules);
            }
            update_option('wpvivid_schedule_addon_setting',$new_schedules,'no');
        }
        else{
            $old_schedules=get_option('wpvivid_schedule_addon_setting');
            if(!empty($old_schedules)) {
                foreach ($old_schedules as $old_schedule_id => $schedule) {
                    $old_schedules[$old_schedule_id]['status'] = 'InActive';
                    if (wp_get_schedule($old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']))) {
                        wp_clear_scheduled_hook($old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']));
                        $timestamp = wp_next_scheduled($old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']));
                        wp_unschedule_event($timestamp, $old_schedules[$old_schedule_id]['id'], array($old_schedules[$old_schedule_id]['id']));
                    }
                    unset($old_schedules[$old_schedule_id]);
                }
            }
            update_option('wpvivid_schedule_addon_setting',array(),'no');
        }
        WPvivid_Schedule_addon::reset_schedule();
        self::wpvivid_enable_schedule();
        $ret['result']='success';
        return $ret;
    }

    /***** wpvivid mainwp remote filter *****/
    public function wpvivid_sync_remote_storage_addon_mainwp($data){
        $json = $data['remote'];
        $json = stripslashes($json);
        $remote = json_decode($json, true);
        $remote['custom_path'] = $data['custom_path'];

        if($remote['type'] === 'ftp') {
            $res = explode(':', $remote['server']);
            if (sizeof($res) > 1) {
                $remote['host'] = $res[0];
            } else {
                $remote['host'] = $res[0];
            }
        }
        if($remote['type'] === 'amazons3' || $remote['type'] === 's3compat' || $remote['type'] === 'wasabi' || $remote['type'] === 'b2' ||
            $remote['type'] === 'webdav' || $remote['type'] === 'nextcloud'){
            $remote['path'] = $remote['custom_path'];
            unset($remote['custom_path']);
        }
        else{
            $remote['path']=trailingslashit($remote['path']);
            $remote['custom_path']=untrailingslashit($remote['custom_path']);
        }

        if($remote['type'] === 'amazons3'){
            if(isset($remote['chunk_size'])){
                $remote['chunk_size'] = $remote['chunk_size'] * 1024 * 1024;
            }
            else{
                $remote['chunk_size'] = 5 * 1024 * 1024;
            }
        }

        if($remote['type'] === 'b2'){
            if(isset($remote['chunk_size'])){
                $remote['chunk_size'] = $remote['chunk_size'] * 1024 * 1024;
            }
            else{
                $remote['chunk_size'] = 3 * 1024 * 1024;
            }
        }

        if($remote['type'] === 'webdav'){
            if(isset($remote['chunk_size'])){
                $remote['chunk_size'] = $remote['chunk_size'] * 1024 * 1024;
            }
            else{
                $remote['chunk_size'] = 3 * 1024 * 1024;
            }
        }

        if($remote['type'] === 'nextcloud'){
            if(isset($remote['chunk_size'])){
                $remote['chunk_size'] = $remote['chunk_size'] * 1024 * 1024;
            }
            else{
                $remote['chunk_size'] = 3 * 1024 * 1024;
            }
        }

        $remote_list = get_option('wpvivid_upload_setting');

        $has_find = false;
        $default = false;
        $default_append = false;
        foreach ( $remote_list as $key => $remote_option ){
            if($key=='remote_selected') {
                continue;
            }
            if($remote_option['name'] === $remote['name']) {
                //update
                $has_find = true;
                $remote['id']=$key;
                $remote_list[$key] = $remote;
                update_option('wpvivid_upload_setting', $remote_list, 'no');
                $id = $key;
            }
        }
        if(!$has_find){
            //insert
            $id=uniqid('wpvivid-remote-');
            $remote['id']=$id;
            $remote_list[$id] = $remote;
            update_option('wpvivid_upload_setting', $remote_list, 'no');
        }

        $wpvivid_user_history=get_option('wpvivid_user_history');
        if(isset($wpvivid_user_history['remote_selected'])){
            $default_select = $wpvivid_user_history['remote_selected'];
        }
        else{
            $default_select = array();
        }
        if($data['default_setting'] === 'default_append'){
            if(array_search($id, $default_select) === false){
                $default_select[] = $id;
            }
            $options=get_option('wpvivid_user_history');
            $options['remote_selected']=$default_select;
            update_option('wpvivid_user_history',$options,'no');
        }
        else if($data['default_setting'] === 'default_only'){
            $new_default_select = array();
            $new_default_select[] = $id;
            $options=get_option('wpvivid_user_history');
            $options['remote_selected']=$new_default_select;
            update_option('wpvivid_user_history',$options,'no');
        }

        $ret['result']='success';
        $ret['remote']['upload']=get_option('wpvivid_upload_setting');
        $ret['remote']['history']=get_option('wpvivid_user_history');

        return $ret;
    }

    /***** wpvivid mainwp setting filter *****/
    public function wpvivid_set_general_setting_addon_mainwp($data){
        $setting = $data['setting'];
        $ret=array();
        try {
            if(isset($setting)&&!empty($setting)) {
                $json_setting = $setting;
                $json_setting = stripslashes($json_setting);
                $setting = json_decode($json_setting, true);
                if (is_null($setting)) {
                    $ret['error']='bad parameter';
                    return $ret;
                }

                if(!isset($setting['wpvivid_common_setting']['backup_prefix']) || empty($setting['wpvivid_common_setting']['backup_prefix']))
                {
                    $home_url_prefix=get_home_url();
                    $parse = parse_url($home_url_prefix);
                    $path = '';
                    if(isset($parse['path'])) {
                        $parse['path'] = str_replace('/', '_', $parse['path']);
                        $parse['path'] = str_replace('.', '_', $parse['path']);
                        $path = $parse['path'];
                    }
                    $parse['host'] = str_replace('/', '_', $parse['host']);
                    $setting['wpvivid_common_setting']['backup_prefix'] = $parse['host'].$path;
                }

                if(isset($setting['wpvivid_email_setting_addon']['mail_title']) && $setting['wpvivid_email_setting_addon']['mail_title'] === 'child-site')
                {
                    global $wpvivid_backup_pro;
                    $default_mail_title = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
                    $setting['wpvivid_email_setting_addon']['mail_title'] = $default_mail_title;
                }

                if(isset($setting['wpvivid_rollback_remote']) && $setting['wpvivid_rollback_remote'] === 1)
                {
                    if(!isset($setting['wpvivid_rollback_remote_id']))
                    {
                        $remoteslist=get_option('wpvivid_upload_setting');
                        $options=get_option('wpvivid_user_history', array());
                        if(array_key_exists('remote_selected',$options))
                        {
                            $remoteslist['remote_selected'] = $options['remote_selected'];
                        }
                        else
                        {
                            $remoteslist['remote_selected'] = array();
                        }

                        if(!empty($remoteslist))
                        {
                            foreach ($remoteslist as $key=>$remote_option)
                            {
                                if($key=='remote_selected')
                                {
                                    continue;
                                }
                                $setting['wpvivid_rollback_remote_id'] = $key;
                                break;
                            }
                        }
                    }
                }

                foreach ($setting as $option_name=>$option)
                {
                    update_option($option_name,$option,'no');
                }
            }

            $ret['result']='success';
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('error'=>$message);
        }
        return $ret;
    }

    /***** wpvivid mainwp report filter *****/
    public function wpvivid_archieve_report_addon_mainwp($data){
        $message = get_option('wpvivid_last_msg', array());
        $ret = array();
        if(!empty($message['id'])) {
            $ret['id'] = $message['id'];
            $ret['status'] = $message['status'];
            $ret['status']['start_time'] = date("M d, Y H:i", $ret['status']['start_time']);
            $ret['status']['run_time'] = date("M d, Y H:i", $ret['status']['run_time']);
            $ret['status']['timeout'] = date("M d, Y H:i", $ret['status']['timeout']);
            if(isset($message['options']['log_file_name']))
                $ret['log_file_name'] = $message['options']['log_file_name'];
            else
                $ret['log_file_name'] ='';
        }

        $message=$ret;
        if(empty($message)){
            $last_message=__('The last backup message not found.', 'wpvivid');
        }
        else{
            $offset=get_option('gmt_offset');
            $localtime = strtotime($message['status']['start_time']) + $offset * 60 * 60;
            if($message['status']['str'] == 'completed'){
                $backup_status='Succeeded';
                $last_message=$backup_status.', (Local Time) '.date("l, F-d-Y H:i", $localtime);
            }
            elseif($message['status']['str'] == 'error'){
                $backup_status='Failed';
                $last_message=$backup_status.', (Local Time) '.date("l, F-d-Y H:i", $localtime);
            }
            elseif($message['status']['str'] == 'cancel'){
                $backup_status='Failed';
                $last_message=$backup_status.', (Local Time) '.date("l, F-d-Y H:i", $localtime);
            }
            else{
                $last_message=__('The last backup message not found.', 'wpvivid');
            }
        }
        $ret['last_backup_message'] = $last_message;
        return $ret;
    }

    public function wpvivid_set_backup_report_addon_mainwp($data){
        $task_id = $data['id'];
        $option = array();
        $option[$task_id]['task_id'] = $task_id;
        $option[$task_id]['backup_time'] = $data['status']['start_time'];
        if($data['status']['str'] == 'running'){
            $option[$task_id]['status'] = 'Succeeded';
        }
        elseif($data['status']['str'] == 'completed'){
            $option[$task_id]['status'] = 'Succeeded';
        }
        elseif($data['status']['str'] == 'error'){
            $option[$task_id]['status'] = 'Failed, '.$data['status']['error'];
        }
        elseif($data['status']['str'] == 'cancel'){
            $option[$task_id]['status'] = 'Canceled';
        }
        else{
            $option[$task_id]['status'] = 'The last backup message not found.';
        }

        $backup_reports = get_option('wpvivid_backup_reports', array());
        if(!empty($backup_reports)){
            foreach ($option as $key => $value){
                $backup_reports[$key] = $value;
                $backup_reports = $this->clean_out_of_date_report($backup_reports, 10);
                update_option('wpvivid_backup_reports', $backup_reports, 'no');
            }
        }
        else{
            update_option('wpvivid_backup_reports', $option, 'no');
        }
    }

    public function clean_out_of_date_report($backup_reports, $max_report_count)
    {
        $size=sizeof($backup_reports);
        while($size>$max_report_count)
        {
            $oldest_id=self::get_oldest_backup_id($backup_reports);

            if($oldest_id!='not set')
            {
                unset($backup_reports[$oldest_id]);
            }
            $new_size=sizeof($backup_reports);
            if($new_size==$size)
            {
                break;
            }
            else
            {
                $size=$new_size;
            }
        }
        return $backup_reports;
    }

    public static function get_oldest_backup_id($report_list)
    {
        $oldest_id='not set';
        $oldest=0;
        foreach ($report_list as $key=>$value)
        {
            if ($oldest == 0) {
                $oldest = $value['backup_time'];
                $oldest_id = $key;
            } else {
                if ($oldest > $value['backup_time']) {
                    $oldest_id = $key;
                }
            }
        }
        return $oldest_id;
    }

    public function wpvivid_get_backup_report_addon_mainwp($data){
        $backup_reports = get_option('wpvivid_backup_reports', array());
        if(!empty($backup_reports)){
            $data = $backup_reports;
        }
        return $data;
    }

    /***** load_wpvivid_mainwp_menu_filter *****/
    public function wpvivid_set_menu_capability_addon_mainwp($data){
        $menu_cap = $data['menu_cap'];
        $ret=array();
        try {
            if(isset($menu_cap)&&!empty($menu_cap)) {
                $json_setting = $menu_cap;
                $json_setting = stripslashes($json_setting);
                $menu_cap = json_decode($json_setting, true);
                if (is_null($menu_cap)) {
                    $ret['error']='bad parameter';
                    return $ret;
                }
                update_option('wpvivid_menu_cap_mainwp', $menu_cap, 'no');
            }

            $ret['result']='success';
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('error'=>$message);
        }
        return $ret;
    }

    public function wpvivid_get_menu_capability_addon($menu){
        if(isset($_REQUEST['from-mainwp'])){
            $display = true;
            return $display;
        }
        $menu_cap = get_option('wpvivid_menu_cap_mainwp', false);
        if(isset($menu_cap) && !empty($menu_cap)){
            if(isset($menu_cap[$menu])){
                if($menu_cap[$menu] == '1'){
                    $display = true;
                }
                else{
                    $display = false;
                }
            }
            else{
                $display = true;
            }
        }
        else{
            $display = true;
        }
        return $display;
    }

    /***** wpvivid mainwp incremental backup filter *****/
    public function wpvivid_get_incremental_output_msg(){
        $schedules=get_option('wpvivid_incremental_schedules');
        if(empty($schedules))
        {
            $files_next_start='';
            $db_next_start='';
            $files_schedule='';
            $db_schedule='';
            $all_schedule='';
            $next_start_of_all_files='';
        }
        else
        {
            $schedule=array_shift($schedules);

            $files_schedule=$schedule['incremental_files_recurrence'];
            $db_schedule=$schedule['incremental_db_recurrence'];
            $all_schedule=$schedule['incremental_recurrence'];

            $offset = get_option('gmt_offset');
            $files_schedule_id=$schedule['files_schedule_id'];
            $db_schedule_id=$schedule['db_schedule_id'];
            $timestamp = wp_next_scheduled($files_schedule_id, array($schedule['id']));
            $files_next_start = $timestamp;

            $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());

            if(empty($incremental_backup_data))
            {
                if($files_next_start==false)
                {
                    $next_start_of_all_files='now';
                }
                else
                {
                    $next_start_of_all_files=$files_next_start;
                }

            }
            else if(isset($incremental_backup_data[$schedule['id']])&&isset($incremental_backup_data[$schedule['id']]['files']))
            {
                if($incremental_backup_data[$schedule['id']]['files']['first_backup'])
                {
                    $next_start_of_all_files=$files_next_start;
                }
                else
                {
                    $next_start_of_all_files=$incremental_backup_data[$schedule['id']]['files']['next_start'];
                }
            }
            else
            {
                $next_start_of_all_files=$files_next_start;
            }

            if($next_start_of_all_files !== false)
            {
                if($next_start_of_all_files=='now')
                {
                    $next_start_of_all_files=time();
                }
                $next_start_of_all_files = $next_start_of_all_files + $offset * 60 * 60;
                if ($next_start_of_all_files > 0) {
                    $next_start_of_all_files = date("H:i:s - F-d-Y ", $next_start_of_all_files);
                } else {
                    $next_start_of_all_files = 'N/A';
                }
            }
            else{
                $next_start_of_all_files = 'N/A';
            }

            if($files_next_start !== false) {
                $localtime = $files_next_start + $offset * 60 * 60;
                if ($localtime > 0) {
                    $files_next_start = date("H:i:s - F-d-Y ", $localtime);
                } else {
                    $files_next_start = 'N/A';
                }
            }
            else{
                $files_next_start = 'N/A';
            }
            $timestamp = wp_next_scheduled($db_schedule_id, array($schedule['id']));
            $db_next_start = $timestamp;
            if($db_next_start !== false) {
                $localtime = $db_next_start + $offset * 60 * 60;
                if ($localtime > 0) {
                    $db_next_start = date("H:i:s - F-d-Y ", $localtime);
                } else {
                    $db_next_start = 'N/A';
                }
            }
            else{
                $db_next_start = 'N/A';
            }

            $recurrence = wp_get_schedules();

            if (isset($recurrence[$files_schedule]))
            {
                $files_schedule = $recurrence[$files_schedule]['display'];
            }
            if (isset($recurrence[$db_schedule]))
            {
                $db_schedule = $recurrence[$db_schedule]['display'];
            }
            if (isset($recurrence[$all_schedule]))
            {
                $all_schedule = $recurrence[$all_schedule]['display'];
            }
        }

        $message=get_option('wpvivid_incremental_last_msg');
        if(empty($message))
        {
            $last_message='N/A.';
        }
        else {
            $offset=get_option('gmt_offset');
            $time = $message['status']['start_time'] + ($offset * 60 * 60);
            if(isset($message['incremental_backup_files']))
            {
                $backup_files='Backup '.$message['incremental_backup_files'].' ';
            }
            else
            {
                $backup_files='';
            }
            $time=', (Local Time) '.date("l, F-d-Y H:i", $time);

            if(isset($message['no_files']))
            {
                $nofile=' No file need to be backup. ';
            }
            else
            {
                $nofile='';
            }

            if($message['status']['str'] == 'completed')
            {
                $last_message=$backup_files.'Succeeded'.$nofile.$time;
            }
            else if($message['status']['str'] == 'error')
            {
                $last_message=$backup_files.'Failed'.$nofile.$time;
            }
            else if($message['status']['str'] == 'cancel')
            {
                $last_message=$backup_files.'Failed'.$nofile.$time;
            }
            else{
                $last_message=__('N/A.', 'wpvivid');
            }
        }

        $ret['files_next_start']=$files_next_start;
        $ret['db_next_start']=$db_next_start;
        $ret['files_schedule']=$files_schedule;
        $ret['db_schedule']=$db_schedule;
        $ret['all_schedule']=$all_schedule;
        $ret['next_start_of_all_files']=$next_start_of_all_files;
        $ret['last_message']=$last_message;
        return $ret;
    }

    public static function archieve_incremental_schedules_info_mainwp()
    {
        $enable_incremental_schedules=get_option('wpvivid_enable_incremental_schedules', false);
        $incremental_schedules=get_option('wpvivid_incremental_schedules');
        $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());
        if(!empty($incremental_schedules)){
            $offset = get_option('gmt_offset');
            $schedule=array_shift($incremental_schedules);

            $full_backup_schedule=$schedule['incremental_recurrence'];
            $incremental_backup_schedule=$schedule['incremental_files_recurrence'];
            $database_backup_schedule=$schedule['incremental_db_recurrence'];

            $recurrence = wp_get_schedules();
            if (isset($recurrence[$full_backup_schedule]))
            {
                $full_backup_schedule = $recurrence[$full_backup_schedule]['display'];
            }
            if (isset($recurrence[$incremental_backup_schedule]))
            {
                $incremental_backup_schedule = $recurrence[$incremental_backup_schedule]['display'];
            }
            if (isset($recurrence[$database_backup_schedule]))
            {
                $database_backup_schedule = $recurrence[$database_backup_schedule]['display'];
            }

            if($enable_incremental_schedules){
                $files_schedule_id=$schedule['files_schedule_id'];
                $db_schedule_id=$schedule['db_schedule_id'];
                $timestamp = wp_next_scheduled($files_schedule_id, array($schedule['id']));
                $files_next_start = $timestamp;

                //full backup
                if(empty($incremental_backup_data)) {
                    if($files_next_start==false) {
                        $next_start_of_full_backup='now';
                    }
                    else {
                        $next_start_of_full_backup=$files_next_start;
                    }

                }
                else if(isset($incremental_backup_data[$schedule['id']])&&isset($incremental_backup_data[$schedule['id']]['files'])) {
                    if($incremental_backup_data[$schedule['id']]['files']['first_backup']) {
                        $next_start_of_full_backup=$files_next_start;
                    }
                    else {
                        $next_start_of_full_backup=$incremental_backup_data[$schedule['id']]['files']['next_start'];
                    }
                }
                else {
                    $next_start_of_full_backup=$files_next_start;
                }

                if($next_start_of_full_backup !== false) {
                    if($next_start_of_full_backup=='now') {
                        $next_start_of_full_backup=time();
                    }
                    $next_start_of_full_backup = $next_start_of_full_backup + $offset * 60 * 60;
                    if ($next_start_of_full_backup > 0) {
                        $next_start_of_full_backup = date("H:i:s - F-d-Y ", $next_start_of_full_backup);
                    } else {
                        $next_start_of_full_backup = 'N/A';
                    }
                }
                else{
                    $next_start_of_full_backup = 'N/A';
                }

                //incremental backup
                if($files_next_start !== false) {
                    $localtime = $files_next_start + $offset * 60 * 60;
                    if ($localtime > 0) {
                        $next_start_of_incremental_backup = date("H:i:s - F-d-Y ", $localtime);
                    } else {
                        $next_start_of_incremental_backup = 'N/A';
                    }
                }
                else{
                    $next_start_of_incremental_backup = 'N/A';
                }

                //database backup
                $timestamp = wp_next_scheduled($db_schedule_id, array($schedule['id']));
                $db_next_start = $timestamp;
                if($db_next_start !== false) {
                    $localtime = $db_next_start + $offset * 60 * 60;
                    if ($localtime > 0) {
                        $next_start_of_database_backup = date("H:i:s - F-d-Y ", $localtime);
                    } else {
                        $next_start_of_database_backup = 'N/A';
                    }
                }
                else{
                    $next_start_of_database_backup = 'N/A';
                }
            }
            else{
                $next_start_of_full_backup = 'N/A';
                $next_start_of_incremental_backup = 'N/A';
                $next_start_of_database_backup = 'N/A';
            }


            $full_backup['backup_type'] = 'Full Backup';
            $full_backup['backup_cycles'] = $full_backup_schedule;
            $full_backup['backup_last_time'] = 'N/A';
            $full_backup['backup_next_time'] = $next_start_of_full_backup;

            $incremental_backup['backup_type'] = 'Incremental Backup';
            $incremental_backup['backup_cycles'] = $incremental_backup_schedule;
            $incremental_backup['backup_last_time'] = 'N/A';
            $incremental_backup['backup_next_time'] = $next_start_of_incremental_backup;

            $database_backup['backup_type'] = 'Database Backup';
            $database_backup['backup_cycles'] = $database_backup_schedule;
            $database_backup['backup_last_time'] = 'N/A';
            $database_backup['backup_next_time'] = $next_start_of_database_backup;

            $incremental_schedules_list[] = $full_backup;
            $incremental_schedules_list[] = $incremental_backup;
            $incremental_schedules_list[] = $database_backup;
        }
        else{
            $full_backup['backup_type'] = 'Full Backup';
            $full_backup['backup_cycles'] = 'Weekly';
            $full_backup['backup_last_time'] = 'N/A';
            $full_backup['backup_next_time'] = 'N/A';

            $incremental_backup['backup_type'] = 'Incremental Backup';
            $incremental_backup['backup_cycles'] = 'Every hour';
            $incremental_backup['backup_last_time'] = 'N/A';
            $incremental_backup['backup_next_time'] = 'N/A';

            $database_backup['backup_type'] = 'Database Backup';
            $database_backup['backup_cycles'] = 'Weekly';
            $database_backup['backup_last_time'] = 'N/A';
            $database_backup['backup_next_time'] = 'N/A';

            $incremental_schedules_list[] = $full_backup;
            $incremental_schedules_list[] = $incremental_backup;
            $incremental_schedules_list[] = $database_backup;
        }

        $incremental_schedules_list = apply_filters('wpvivid_get_incremental_last_backup_message', $incremental_schedules_list);
        return $incremental_schedules_list;
    }

    public function wpvivid_get_incremental_backup_mainwp($data){
        /*$default = WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT;
        $data['enable_incremental_schedules']=get_option('wpvivid_enable_incremental_schedules',false);
        $data['incremental_schedules']=get_option('wpvivid_incremental_schedules');
        $data['incremental_history']=get_option('wpvivid_incremental_backup_history', array());
        $data['incremental_backup_data']=get_option('wpvivid_incremental_backup_data',array());
        $data['incremental_remote_backup_count']=get_option('wpvivid_incremental_remote_backup_count_addon', $default);
        $data['incremental_output_msg']=$this->wpvivid_get_incremental_output_msg();
        return $data;*/
        $ret['result'] = 'success';
        $ret['schedule_info'] = self::archieve_incremental_schedules_info_mainwp();
        return $ret;
    }

    public function wpvivid_get_db_tables_addon_mainwp()
    {
        global $wpdb;
        if (is_multisite() && !defined('MULTISITE')) {
            $prefix = $wpdb->base_prefix;
        } else {
            $prefix = $wpdb->get_blog_prefix(0);
        }
        $default_table = array($prefix . 'commentmeta', $prefix . 'comments', $prefix . 'links', $prefix . 'options', $prefix . 'postmeta', $prefix . 'posts', $prefix . 'term_relationships',
            $prefix . 'term_taxonomy', $prefix . 'termmeta', $prefix . 'terms', $prefix . 'usermeta', $prefix . 'users');

        $tables = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);

        if (is_null($tables)) {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to retrieve the table information for the database. Please try again.';
            echo json_encode($ret);
            die();
        }


        $ret['result'] = 'success';
        $ret['html'] = '';

        $custom_incremental_history = WPvivid_custom_backup_selector::get_incremental_db_setting();
        if (!isset($custom_incremental_history) || empty($custom_incremental_history)) {
            $custom_incremental_history = array();
        }

        $tables_info = array();
        $base_table_array = array();
        $other_table_array = array();
        foreach ($tables as $row) {
            if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                continue;
            }

            $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
            $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);

            $checked = 'checked';
            if (!empty($custom_incremental_history['database_option']['exclude_table_list'])) {
                if (in_array($row["Name"], $custom_incremental_history['database_option']['exclude_table_list'])) {
                    $checked = '';
                }
            }

            if (in_array($row["Name"], $default_table)) {
                $base_table_array[] = array('table_name' => $row["Name"], 'table_row' => $row["Rows"], 'table_size' => $tables_info[$row["Name"]]["Data_length"], 'table_check' => $checked);
            }
            else {
                $other_table_array[] = array('table_name' => $row["Name"], 'table_row' => $row["Rows"], 'table_size' => $tables_info[$row["Name"]]["Data_length"], 'table_check' => $checked);
            }
        }
        $ret['base_tables'] = $base_table_array;
        $ret['other_tables'] = $other_table_array;
        return $ret;
    }

    public function wpvivid_get_theme_plugin_tables_addon_mainwp()
    {
        $custom_interface_addon = new WPvivid_Custom_Interface_addon();
        $custom_incremental_history = WPvivid_custom_backup_selector::get_incremental_file_settings();
        if (!isset($custom_incremental_history) || empty($custom_incremental_history)) {
            $custom_incremental_history = array();
        }
        $themes_path = get_theme_root();
        $current_active_theme = get_stylesheet();
        $themes_info = array();
        $themes_array = array();

        $themes=wp_get_themes();
        if (!empty($themes)) {
            $has_themes = true;
        }
        foreach ($themes as $theme) {
            $file=$theme->get_stylesheet();
            $themes_info[$file] = $custom_interface_addon->get_theme_plugin_info($themes_path . DIRECTORY_SEPARATOR . $file);
            $themes_info[$file]['active'] = 1;
        }
        uasort($themes_info, function ($a, $b) {
            if ($a['active'] < $b['active']) {
                return 1;
            }
            if ($a['active'] > $b['active']) {
                return -1;
            } else {
                return 0;
            }
        });
        foreach ($themes_info as $file=>$info) {
            $checked = '';
            if($info['active']==1) {
                $checked = 'checked';
            }

            if (!empty($custom_incremental_history['themes_option']['exclude_themes_list'])) {
                if (in_array($file, $custom_incremental_history['themes_option']['exclude_themes_list'])) {
                    $checked = '';
                }
            }
            $themes_array[] = array('theme_name' => $file, 'theme_size' => size_format($info["size"], 2), 'theme_check' => $checked);
        }


        $path = WP_PLUGIN_DIR;
        $active_plugins = get_option('active_plugins');
        $plugin_info = array();
        $plugins_array = array();

        if(!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $plugins=get_plugins();
        foreach ($plugins as $key=>$plugin) {
            $slug=dirname($key);
            if($slug=='.'||$slug=='wpvivid-backuprestore'||$slug=='wpvivid-backup-pro')
                continue;
            $plugin_info[$slug]= $custom_interface_addon->get_theme_plugin_info($path . DIRECTORY_SEPARATOR . $slug);
            $plugin_info[$slug]['Name']=$plugin['Name'];
            $plugin_info[$slug]['slug']=$slug;
            $plugin_info[$slug]['active'] = 1;
        }

        uasort ($plugin_info,function($a, $b) {
            if($a['active']<$b['active']) {
                return 1;
            }
            if($a['active']>$b['active']) {
                return -1;
            }
            else {
                return 0;
            }
        });

        foreach ($plugin_info as $slug=>$info) {
            $checked = '';
            if($info['active']==1) {
                $checked = 'checked';
            }

            if (!empty($custom_incremental_history['plugins_option']['exclude_plugins_list'])) {
                if (in_array($slug, $custom_incremental_history['plugins_option']['exclude_plugins_list'])) {
                    $checked = '';
                }
            }

            $plugins_array[] = array('plugin_slug_name' => $info['slug'], 'plugin_display_name' => $info['Name'], 'plugin_size' => size_format($info["size"], 2), 'plugin_check' => $checked);
        }
        $ret['themes'] = $themes_array;
        $ret['plugins'] = $plugins_array;
        return $ret;
    }

    public function wpvivid_refresh_incremental_table_addon_mainwp($data){
        $ret['database_tables'] = $this->wpvivid_get_db_tables_addon_mainwp();
        return $ret;
    }

    public function wpvivid_enable_incremental_backup_mainwp($data){
        $enable = $data['enable'];
        if ($enable) {
            self::wpvivid_enable_incremental_schedule_ex('1');
            /*update_option('wpvivid_enable_incremental_schedules', true);

            $schedules = get_option('wpvivid_schedule_addon_setting');
            foreach ($schedules as $schedule_id => $schedule) {
                $schedules[$schedule_id]['status'] = 'InActive';
                if (wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']))) {
                    wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                }
            }
            update_option('wpvivid_schedule_addon_setting', $schedules);*/

        } else {
            self::wpvivid_enable_incremental_schedule_ex('0');
            /*update_option('wpvivid_enable_incremental_schedules', false);

            $need_remove_schedules = array();
            $crons = _get_cron_array();
            foreach ($crons as $cronhooks) {
                foreach ($cronhooks as $hook_name => $hook_schedules) {
                    if (preg_match('#wpvivid_incremental_.*#', $hook_name)) {
                        foreach ($hook_schedules as $data) {
                            $need_remove_schedules[$hook_name] = $data['args'];
                        }
                    }
                }
            }

            foreach ($need_remove_schedules as $hook_name => $args) {
                wp_clear_scheduled_hook($hook_name, $args);
                $timestamp = wp_next_scheduled($hook_name, $args);
                wp_unschedule_event($timestamp, $hook_name, array($args));
            }

            $schedules = array();
            update_option('wpvivid_incremental_schedules', $schedules);
            update_option('wpvivid_incremental_backup_data', array());
            $ret['schedule_info'] = self::archieve_schedules_info_mainwp();
            $ret['incremental_schedules'] = get_option('wpvivid_incremental_schedules', array());
            $ret['incremental_backup_data'] = get_option('wpvivid_incremental_backup_data', array());
            $ret['incremental_output_msg']=$this->wpvivid_get_incremental_output_msg();*/
        }
        $ret['schedule_info'] = self::archieve_incremental_schedules_info_mainwp();
        $ret['enable_incremental_schedules']=get_option('wpvivid_enable_incremental_schedules',false);
        $ret['incremental_schedules']=get_option('wpvivid_incremental_schedules');
        $ret['incremental_backup_data']=get_option('wpvivid_incremental_backup_data',array());
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_save_incremental_backup_schedule_mainwp($data){
        $json = $data['schedule'];
        $json = stripslashes($json);
        $schedule = json_decode($json, true);
        if (is_null($schedule))
        {
            die();
        }
        $schedule_addon = new WPvivid_Incremental_Backup_Display_addon();
        $ret = $schedule_addon->check_schedule_option($schedule);
        if($ret['result']!=WPVIVID_PRO_SUCCESS)
        {
            return $ret;
        }

        $ret=$schedule_addon->add_incremental_schedule($ret['schedule'], true);
        $ret['schedule_info'] = self::archieve_incremental_schedules_info_mainwp();
        $ret['enable_incremental_schedules']=get_option('wpvivid_enable_incremental_schedules',false);
        $ret['incremental_schedules']=get_option('wpvivid_incremental_schedules');
        $ret['incremental_backup_data']=get_option('wpvivid_incremental_backup_data',array());
        return $ret;
    }

    public static function wpvivid_enable_incremental_schedule(){
        update_option('wpvivid_enable_incremental_schedules', true, 'no');
        //update_option('wpvivid_enable_schedules', false);

        $schedules = get_option('wpvivid_schedule_addon_setting');
        foreach ($schedules as $schedule_id => $schedule) {
            $schedules[$schedule_id]['status'] = 'InActive';
            if (wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']))) {
                wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
            }
        }
        update_option('wpvivid_schedule_addon_setting', $schedules, 'no');

        $need_remove_schedules=array();
        $crons = _get_cron_array();
        foreach ( $crons as $cronhooks )
        {
            foreach ($cronhooks as $hook_name=>$hook_schedules)
            {
                if(preg_match('#wpvivid_incremental_.*#',$hook_name))
                {
                    foreach ($hook_schedules as $data)
                    {
                        $need_remove_schedules[$hook_name]=$data['args'];
                    }
                }
            }
        }

        foreach ($need_remove_schedules as $hook_name=>$args)
        {
            wp_clear_scheduled_hook($hook_name, $args);
            $timestamp = wp_next_scheduled($hook_name, array($args));
            wp_unschedule_event($timestamp,$hook_name,array($args));
        }

        $incremental_schedules=get_option('wpvivid_incremental_schedules');
        $schedule_data=array_shift($incremental_schedules);

        //
        $incremental_backup = new WPvivid_Incremental_Backup_addon();
        $schedule_data = $incremental_backup->reset_imcremental_schedule_start_time($schedule_data);
        //

        $is_mainwp=false;
        if(wp_get_schedule($schedule_data['files_schedule_id'], array($schedule_data['id'])))
        {
            wp_clear_scheduled_hook($schedule_data['files_schedule_id'], array($schedule_data['id']));
            $timestamp = wp_next_scheduled($schedule_data['files_schedule_id'], array($schedule_data['id']));
            wp_unschedule_event($timestamp,$schedule_data['files_schedule_id'],array($schedule_data['id']));
        }

        if(wp_get_schedule($schedule_data['db_schedule_id'], array($schedule_data['id'])))
        {
            wp_clear_scheduled_hook($schedule_data['db_schedule_id'], array($schedule_data['id']));
            $timestamp = wp_next_scheduled($schedule_data['db_schedule_id'], array($schedule_data['id']));
            wp_unschedule_event($timestamp,$schedule_data['db_schedule_id'],array($schedule_data['id']));
        }


        if(wp_schedule_event($schedule_data['db_start_time'], $schedule_data['incremental_db_recurrence'], $schedule_data['db_schedule_id'],array($schedule_data['id']))===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
            $ret['data']=$schedule_data;
            $ret['option']=$schedule;
            return $ret;
        }

        if(isset($schedule_data['incremental_files_start_backup'])&&$schedule_data['incremental_files_start_backup'])
        {
            if(wp_schedule_single_event(time() + 10, $schedule_data['files_schedule_id'],array($schedule_data['id']))===false)
            {
                $ret['result']='failed';
                $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                $ret['data']=$schedule_data;
                $ret['option']=$schedule;
                return $ret;
            }

            if(wp_schedule_single_event(time() + 10, $schedule_data['db_schedule_id'],array($schedule_data['id']))===false)
            {
                $ret['result']='failed';
                $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                $ret['data']=$schedule_data;
                $ret['option']=$schedule;
                return $ret;
            }
        }

        if(wp_schedule_event($schedule_data['files_start_time'], $schedule_data['incremental_files_recurrence'], $schedule_data['files_schedule_id'],array($schedule_data['id']))===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
            $ret['data']=$schedule_data;
            $ret['option']=$schedule;
            return $ret;
        }

        if(wp_schedule_single_event($schedule_data['files_start_time'] + 600, $schedule_data['db_schedule_id'],array($schedule_data['id']))===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
            $ret['data']=$schedule_data;
            $ret['option']=$schedule;
            return $ret;
        }
    }

    public static function reset_imcremental_schedule_start_time($schedule)
    {
        //set file start time
        if(isset($schedule['incremental_recurrence'])){
            $time['type']=$schedule['incremental_recurrence'];
        }
        else{
            $time['type']='wpvivid_weekly';
        }
        if(isset($schedule['incremental_recurrence_week'])) {
            $time['start_time']['week']=$schedule['incremental_recurrence_week'];
        }
        else
            $time['start_time']['week']='mon';
        if(isset($schedule['incremental_recurrence_day'])) {
            $time['start_time']['day']=$schedule['incremental_recurrence_day'];
        }
        else
            $time['start_time']['day']='01';
        if(isset($schedule['files_current_day'])) {
            $time['start_time']['current_day']=$schedule['files_current_day'];
        }
        else
            $time['start_time']['current_day']="00:00";

        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
        $schedule['files_start_time']=$timestamp;

        //set db start time
        if(isset($schedule['incremental_db_recurrence'])){
            $time['type']=$schedule['incremental_db_recurrence'];
        }
        else{
            $time['type']='wpvivid_weekly';
        }
        if(isset($schedule['incremental_db_recurrence_week'])) {
            $time['start_time']['week']=$schedule['incremental_db_recurrence_week'];
        }
        else
            $time['start_time']['week']='mon';
        if(isset($schedule['incremental_db_recurrence_day'])) {
            $time['start_time']['day']=$schedule['incremental_db_recurrence_day'];
        }
        else
            $time['start_time']['day']='01';
        if(isset($schedule['db_current_day'])) {
            $time['start_time']['current_day']=$schedule['db_current_day'];
        }
        else
            $time['start_time']['current_day']="00:00";
        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
        $schedule['db_start_time']=$timestamp;

        return $schedule;
    }

    public static function wpvivid_enable_incremental_schedule_ex($incremental_schedule_enable)
    {
        if($incremental_schedule_enable == '1')
        {
            update_option('wpvivid_enable_incremental_schedules', true, 'no');


            $schedules = get_option('wpvivid_schedule_addon_setting');
            foreach ($schedules as $schedule_id => $schedule) {
                $schedules[$schedule_id]['status'] = 'InActive';
                if (wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']))) {
                    wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                }
            }
            update_option('wpvivid_schedule_addon_setting', $schedules, 'no');

            $need_remove_schedules=array();
            $crons = _get_cron_array();
            foreach ( $crons as $cronhooks )
            {
                foreach ($cronhooks as $hook_name=>$hook_schedules)
                {
                    if(preg_match('#wpvivid_incremental_.*#',$hook_name))
                    {
                        foreach ($hook_schedules as $data)
                        {
                            $need_remove_schedules[$hook_name]=$data['args'];
                        }
                    }
                }
            }

            foreach ($need_remove_schedules as $hook_name=>$args)
            {
                wp_clear_scheduled_hook($hook_name, $args);
                $timestamp = wp_next_scheduled($hook_name, array($args));
                wp_unschedule_event($timestamp,$hook_name,array($args));
            }

            $incremental_schedules=get_option('wpvivid_incremental_schedules');
            $schedule_data=array_shift($incremental_schedules);

            //
            //$incremental_backup = new WPvivid_Incremental_Backup_addon();
            $schedule_data = self::reset_imcremental_schedule_start_time($schedule_data);
            //

            $is_mainwp=false;
            if(wp_get_schedule($schedule_data['files_schedule_id'], array($schedule_data['id'])))
            {
                wp_clear_scheduled_hook($schedule_data['files_schedule_id'], array($schedule_data['id']));
                $timestamp = wp_next_scheduled($schedule_data['files_schedule_id'], array($schedule_data['id']));
                wp_unschedule_event($timestamp,$schedule_data['files_schedule_id'],array($schedule_data['id']));
            }

            if(wp_get_schedule($schedule_data['db_schedule_id'], array($schedule_data['id'])))
            {
                wp_clear_scheduled_hook($schedule_data['db_schedule_id'], array($schedule_data['id']));
                $timestamp = wp_next_scheduled($schedule_data['db_schedule_id'], array($schedule_data['id']));
                wp_unschedule_event($timestamp,$schedule_data['db_schedule_id'],array($schedule_data['id']));
            }


            if(wp_schedule_event($schedule_data['db_start_time'], $schedule_data['incremental_db_recurrence'], $schedule_data['db_schedule_id'],array($schedule_data['id']))===false)
            {
                $ret['result']='failed';
                $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                $ret['data']=$schedule_data;
                $ret['option']=$schedule;
                return $ret;
            }

            if(isset($_POST['start_immediate'])&&$_POST['start_immediate'])
            {
                if(wp_schedule_single_event(time() + 10, $schedule_data['files_schedule_id'],array($schedule_data['id']))===false)
                {
                    $ret['result']='failed';
                    $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                    $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                    $ret['data']=$schedule_data;
                    $ret['option']=$schedule;
                    return $ret;
                }

                if(wp_schedule_single_event(time() + 10, $schedule_data['db_schedule_id'],array($schedule_data['id']))===false)
                {
                    $ret['result']='failed';
                    $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                    $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                    $ret['data']=$schedule_data;
                    $ret['option']=$schedule;
                    return $ret;
                }
            }

            if(wp_schedule_event($schedule_data['files_start_time'], $schedule_data['incremental_files_recurrence'], $schedule_data['files_schedule_id'],array($schedule_data['id']))===false)
            {
                $ret['result']='failed';
                $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                $ret['data']=$schedule_data;
                $ret['option']=$schedule;
                return $ret;
            }

            if(wp_schedule_single_event($schedule_data['files_start_time'] + 600, $schedule_data['db_schedule_id'],array($schedule_data['id']))===false)
            {
                $ret['result']='failed';
                $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                $ret['data']=$schedule_data;
                $ret['option']=$schedule;
                return $ret;
            }

            $offset = get_option('gmt_offset');
            if($schedule_data['files_start_time'] !== false) {
                $localtime = $schedule_data['files_start_time'] + $offset * 60 * 60;
                if ($localtime > 0) {
                    $ret['data']['next_start_of_all_files'] = $ret['data']['files_next_start'] = date("H:i:s - F-d-Y ", $localtime);
                } else {
                    $ret['data']['next_start_of_all_files'] = $ret['data']['files_next_start'] = 0;
                }
            }
            else{
                $ret['data']['next_start_of_all_files'] = $ret['data']['files_next_start'] = 0;
            }

            if($schedule_data['db_start_time'] !== false) {
                $localtime = $schedule_data['db_start_time'] + $offset * 60 * 60;
                if ($localtime > 0) {
                    $ret['data']['db_next_start'] = date("H:i:s - F-d-Y ", $localtime);
                } else {
                    $ret['data']['db_next_start'] = 0;
                }
            }
            else{
                $ret['data']['db_next_start'] = 0;
            }

            $recurrence = wp_get_schedules();
            $files_schedule=$schedule_data['incremental_files_recurrence'];
            $db_schedule=$schedule_data['incremental_db_recurrence'];
            $all_schedule=$schedule_data['incremental_recurrence'];
            if (isset($recurrence[$files_schedule]))
            {
                $ret['data']['files_schedule'] = $recurrence[$files_schedule]['display'];
            }
            if (isset($recurrence[$db_schedule]))
            {
                $ret['data']['db_schedule'] = $recurrence[$db_schedule]['display'];
            }
            if (isset($recurrence[$all_schedule]))
            {
                $ret['data']['all_schedule'] = $recurrence[$all_schedule]['display'];
            }

            $full_backup['backup_type'] = 'Full Backup';
            $full_backup['backup_cycles'] = $ret['data']['all_schedule'];
            $full_backup['backup_last_time'] = 'N/A';
            $full_backup['backup_next_time'] = $ret['data']['next_start_of_all_files'];

            $incremental_backup['backup_type'] = 'Incremental Backup';
            $incremental_backup['backup_cycles'] = $ret['data']['files_schedule'];
            $incremental_backup['backup_last_time'] = 'N/A';
            $incremental_backup['backup_next_time'] = $ret['data']['files_next_start'];

            $database_backup['backup_type'] = 'Database Backup';
            $database_backup['backup_cycles'] = $ret['data']['db_schedule'];
            $database_backup['backup_last_time'] = 'N/A';
            $database_backup['backup_next_time'] = $ret['data']['db_next_start'];

            $incremental_schedules_list[] = $full_backup;
            $incremental_schedules_list[] = $incremental_backup;
            $incremental_schedules_list[] = $database_backup;
        }
        else
        {
            update_option('wpvivid_enable_incremental_schedules', false, 'no');

            $need_remove_schedules = array();
            $crons = _get_cron_array();
            foreach ($crons as $cronhooks) {
                foreach ($cronhooks as $hook_name => $hook_schedules) {
                    if (preg_match('#wpvivid_incremental_.*#', $hook_name)) {
                        foreach ($hook_schedules as $data) {
                            $need_remove_schedules[$hook_name] = $data['args'];
                        }
                    }
                }
            }

            foreach ($need_remove_schedules as $hook_name => $args) {
                wp_clear_scheduled_hook($hook_name, $args);
                $timestamp = wp_next_scheduled($hook_name, $args);
                wp_unschedule_event($timestamp, $hook_name, array($args));
            }

            $incremental_schedules=get_option('wpvivid_incremental_schedules');
            $schedule_data=array_shift($incremental_schedules);

            $recurrence = wp_get_schedules();
            $files_schedule=$schedule_data['incremental_files_recurrence'];
            $db_schedule=$schedule_data['incremental_db_recurrence'];
            $all_schedule=$schedule_data['incremental_recurrence'];
            if (isset($recurrence[$files_schedule]))
            {
                $ret['data']['files_schedule'] = $recurrence[$files_schedule]['display'];
            }
            if (isset($recurrence[$db_schedule]))
            {
                $ret['data']['db_schedule'] = $recurrence[$db_schedule]['display'];
            }
            if (isset($recurrence[$all_schedule]))
            {
                $ret['data']['all_schedule'] = $recurrence[$all_schedule]['display'];
            }
            $full_backup['backup_type'] = 'Full Backup';
            $full_backup['backup_cycles'] = $ret['data']['all_schedule'];
            $full_backup['backup_last_time'] = 'N/A';
            $full_backup['backup_next_time'] = 'N/A';

            $incremental_backup['backup_type'] = 'Incremental Backup';
            $incremental_backup['backup_cycles'] = $ret['data']['files_schedule'];
            $incremental_backup['backup_last_time'] = 'N/A';
            $incremental_backup['backup_next_time'] = 'N/A';

            $database_backup['backup_type'] = 'Database Backup';
            $database_backup['backup_cycles'] = $ret['data']['db_schedule'];
            $database_backup['backup_last_time'] = 'N/A';
            $database_backup['backup_next_time'] = 'N/A';

            $incremental_schedules_list[] = $full_backup;
            $incremental_schedules_list[] = $incremental_backup;
            $incremental_schedules_list[] = $database_backup;

            $schedules = array();

            update_option('wpvivid_incremental_backup_data', array(), 'no');
        }
    }

    public function wpvivid_set_incremental_backup_schedule_mainwp($data){
        $schedule_addon = new WPvivid_Incremental_Backup_Display_addon();
        $json = $data['schedule'];
        $json = stripslashes($json);
        $schedule = json_decode($json, true);
        if (is_null($schedule))
        {
            die();
        }

        if(isset($schedule['custom']['files']['themes_list'])){
            foreach ($schedule['custom']['files']['themes_list'] as $index => $theme){
                if(!isset($value['type'])){
                    $schedule['custom']['files']['themes_list'][$theme]['name'] = $theme;
                    $schedule['custom']['files']['themes_list'][$theme]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    unset($schedule['custom']['files']['themes_list'][$index]);
                }
            }
        }
        if(isset($schedule['custom']['files']['plugins_list'])){
            foreach ($schedule['custom']['files']['plugins_list'] as $index => $plugin){
                if(!isset($value['type'])){
                    $schedule['custom']['files']['plugins_list'][$plugin]['name'] = $plugin;
                    $schedule['custom']['files']['plugins_list'][$plugin]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    unset($schedule['custom']['files']['plugins_list'][$index]);
                }
            }
        }
        $ret = $schedule_addon->check_schedule_option($schedule);
        if($ret['result']!=WPVIVID_SUCCESS)
        {
            echo json_encode($ret);
            die();
        }

        if(isset($data['incremental_remote_retain']) && !empty($data['incremental_remote_retain'])){
            $incremental_remote_retain = intval($data['incremental_remote_retain']);
            update_option('wpvivid_incremental_remote_backup_count_addon', $incremental_remote_retain, 'no');
        }

        $ret=$schedule_addon->add_incremental_schedule($ret['schedule'], true);
        self::wpvivid_enable_incremental_schedule();
        $ret['schedule_info'] = self::archieve_schedules_info_mainwp();
        $ret['incremental_schedules'] = get_option('wpvivid_incremental_schedules', array());
        $ret['incremental_backup_data'] = get_option('wpvivid_incremental_backup_data', array());
        $ret['incremental_output_msg']=$this->wpvivid_get_incremental_output_msg();
        return $ret;
    }

    public function wpvivid_update_incremental_backup_exclude_extension_addon_mainwp($data){
        $history = WPvivid_custom_backup_selector::get_incremental_setting();
        if (empty($history)) {
            $history = array();
        }
        $type = $data['type'];
        $value = $data['exclude_content'];
        if($type === 'upload'){
            $history['incremental_file']['uploads_option']['uploads_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['incremental_file']['uploads_option']['uploads_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        else if($type === 'content'){
            $history['incremental_file']['content_option']['content_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['incremental_file']['content_option']['content_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        else if($type === 'additional-folder'){
            $history['incremental_file']['other_option']['other_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['incremental_file']['other_option']['other_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        update_option('wpvivid_incremental_backup_history', $history, 'no');
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_incremental_connect_additional_database_addon_mainwp($data){
        try {
            $db_user = $data['db_user'];
            $db_pass = $data['db_pass'];
            $db_host = $data['db_host'];

            $ret['result']=WPVIVID_FAILED;
            $ret['error']='Unknown Error';
            $this->incremental_database_connect = new WPvivid_Additional_DB_Method($db_user, $db_pass, $db_host);
            $ret = $this->incremental_database_connect->wpvivid_do_connect();

            if($ret['result']===WPVIVID_SUCCESS){
                $databases = $this->incremental_database_connect->wpvivid_show_additional_databases();
                $default_exclude_database = array('information_schema', 'performance_schema', 'mysql', 'sys', DB_NAME);
                $database_array = array();
                foreach ($databases as $database) {
                    if (!in_array($database, $default_exclude_database)) {
                        $database_array[] = $database;
                    }
                }
                $ret['database_array'] = $database_array;
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        catch (Error $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_incremental_add_additional_database_addon_mainwp($data){
        try {
            $db_user = $data['db_user'];
            $db_pass = $data['db_pass'];
            $db_host = $data['db_host'];
            $db_list = $data['additional_database_list'];

            $history = WPvivid_custom_backup_selector::get_incremental_setting();
            if (empty($history)) {
                $history = array();
            }
            foreach ($db_list as $database){
                $history['incremental_db']['additional_database_option']['additional_database_list'][$database]['db_user'] = $db_user;
                $history['incremental_db']['additional_database_option']['additional_database_list'][$database]['db_pass'] = $db_pass;
                $history['incremental_db']['additional_database_option']['additional_database_list'][$database]['db_host'] = $db_host;
            }
            update_option('wpvivid_incremental_backup_history', $history, 'no');

            if(!is_null($this->incremental_database_connect)){
                $this->incremental_database_connect->close();
            }

            $db_list = array();
            if(isset($history['incremental_db']['additional_database_option']) && isset($history['incremental_db']['additional_database_option']['additional_database_list'])) {
                $db_list = $history['incremental_db']['additional_database_option']['additional_database_list'];
            }
            $ret['result']=WPVIVID_SUCCESS;
            $ret['data'] = $db_list;
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
        }
        return $ret;
    }

    public function wpvivid_incremental_remove_additional_database_addon_mainwp($data){
        try {
            if(!is_null($this->incremental_database_connect)){
                $this->incremental_database_connect->close();
            }
            $database_name = $data['database_name'];

            $history = WPvivid_custom_backup_selector::get_incremental_setting();
            if (empty($history)) {
                $history = array();
            }
            if(isset($history['incremental_db']['additional_database_option'])) {
                if(isset($history['incremental_db']['additional_database_option']['additional_database_list'][$database_name])){
                    unset($history['incremental_db']['additional_database_option']['additional_database_list'][$database_name]);
                }
            }
            update_option('wpvivid_incremental_backup_history', $history, 'no');

            $db_list = array();
            if(isset($history['incremental_db']['additional_database_option']) && isset($history['incremental_db']['additional_database_option']['additional_database_list'])) {
                $db_list = $history['incremental_db']['additional_database_option']['additional_database_list'];
            }
            $ret['result']=WPVIVID_SUCCESS;
            $ret['data'] = $db_list;
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result'] = 'failed';
            $ret['error'] = $message;
        }
        return $ret;
    }

    public function wpvivid_achieve_incremental_child_path_addon_mainwp($data){
        set_time_limit(120);

        $upload_options=get_option('wpvivid_upload_setting');
        $options=get_option('wpvivid_user_history');
        if(isset($options['remote_selected'])){
            $upload_options['remote_selected'] = $options['remote_selected'];
        }
        else{
            $upload_options['remote_selected'] = array();
        }

        $remoteslist = $upload_options;
        $remote_id = $data['remote_id'];
        $incremental_path = $data['incremental_path'];

        if (empty($remote_id))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to post remote stroage id. Please try again.';
            echo json_encode($ret);
            die();
        }

        if (empty($incremental_path))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to post remote storage incremental path. Please try again.';
            echo json_encode($ret);
            die();
        }

        update_option('wpvivid_select_list_remote_id', $remote_id, 'no');
        update_option('wpvivid_remote_list', array(), 'no');
        $remote_option = $remoteslist[$remote_id];

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote = $remote_collection->get_remote($remote_option);

        if (!method_exists($remote, 'scan_folder_backup'))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            echo json_encode($ret);
            die();
        }

        $ret = $remote->scan_child_folder_backup($incremental_path);
        if ($ret['result'] == WPVIVID_SUCCESS)
        {
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->func->rescan_remote_folder_set_backup($remote_id, $ret);
        }

        $ret['local_cache_files_size'] = apply_filters('wpvivid_get_local_cache_files_size', 0);


        $list=get_option('wpvivid_remote_list', array());
        $remote_list=array();

        foreach ($list as $key=>$item)
        {
            if($item['type']=='Common')
            {
                $remote_list[$key]=$item;
                $remote_list[$key]['type']='Incremental';
            }
        }
        $ret['list_data'] = $remote_list;
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_archieve_incremental_remote_folder_list_addon_mainwp($data){
        set_time_limit(120);

        $upload_options=get_option('wpvivid_upload_setting');
        $options=get_option('wpvivid_user_history');
        if(isset($options['remote_selected'])){
            $upload_options['remote_selected'] = $options['remote_selected'];
        }
        else{
            $upload_options['remote_selected'] = array();
        }

        $remoteslist = $upload_options;
        $remote_id = $data['remote_id'];
        $remote_folder = $data['folder'];

        if (empty($remote_id))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to post remote stroage id. Please try again.';
            echo json_encode($ret);
            die();
        }

        if (empty($remote_folder))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'Failed to post remote storage folder. Please try again.';
            echo json_encode($ret);
            die();
        }

        update_option('wpvivid_select_list_remote_id', $remote_id, 'no');
        update_option('wpvivid_remote_list', array(), 'no');
        $remote_option = $remoteslist[$remote_id];

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote = $remote_collection->get_remote($remote_option);

        if (!method_exists($remote, 'scan_folder_backup'))
        {
            $ret['result'] = 'failed';
            $ret['error'] = 'The selected remote storage does not support scanning.';
            echo json_encode($ret);
            die();
        }

        $ret = $remote->scan_folder_backup($remote_folder);
        if ($ret['result'] == WPVIVID_SUCCESS)
        {
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->func->rescan_remote_folder_set_backup($remote_id, $ret);
        }

        $ret['local_cache_files_size'] = apply_filters('wpvivid_get_local_cache_files_size', 0);

        $ret['incremental_list'] = false;
        if(isset($ret['path']) && !empty($ret['path'])){
            $path_list = array();
            foreach ($ret['path'] as $path) {
                if (preg_match('/.*_.*_.*_to_.*_.*_.*$/', $path)){
                    $og_path=$path;
                    $path = preg_replace("/_to_.*_.*_.*/", "", $path);
                    $path = preg_replace("/_/", "-", $path);
                    $path = strtotime($path);
                    $temp['og_path']=$og_path;
                    $temp['path']=$path;
                    $path_list[] = $temp;
                }
            }

            uasort ($path_list,function($a, $b) {
                if($a['path']>$b['path']) {
                    return -1;
                }
                else if($a['path']===$b['path']) {
                    return 0;
                }
                else {
                    return 1;
                }
            });

            $ret['incremental_list'] = $path_list;
        }
        $ret['result'] = 'success';
        return $ret;
    }

    public function wpvivid_sync_incremental_schedule_addon_mainwp($data){
        $incremental_schedule = $data['schedule'];
        /*if(isset($incremental_schedule['incremental_remote_backup_count'])){
            update_option('wpvivid_incremental_remote_backup_count_addon', intval($incremental_schedule['incremental_remote_backup_count']));
        }
        if(isset($incremental_schedule['incremental_history'])){
            update_option('wpvivid_incremental_backup_history', $incremental_schedule['incremental_history']);
        }
        update_option('wpvivid_enable_incremental_schedules',true);*/

        $incremental_schedule_enable = '0';

        $new_incremental_schedule = array();
        $new_incremental_schedule_id = '';
        foreach ($incremental_schedule['incremental_schedules'] as $incremental_schedule_id => $incremental_schedule_data){
            $new_incremental_schedule_id = $incremental_schedule_id;


            $incremental_schedule_enable = $incremental_schedule_data['incremental_backup_status'];

            /*if($incremental_schedule_data['file_start_time_zone'] === 'utc') {
                //$incremental_schedule_data['files_current_day'] = $incremental_schedule_data['files_current_day'];
                $offset=get_option('gmt_offset');
                $utc_time = strtotime($incremental_schedule_data['files_current_day']) + $offset * 60 * 60;
                $incremental_schedule_data['files_current_day_hour'] = date("H", $utc_time);
                $incremental_schedule_data['files_current_day_minute'] = date("i", $utc_time);
            }
            else{
                $offset=get_option('gmt_offset');
                $utc_time = strtotime($incremental_schedule_data['files_current_day']) - $offset * 60 * 60;
                $incremental_schedule_data['files_current_day'] = date("H:i", $utc_time);
            }

            if($incremental_schedule_data['db_start_time_zone'] === 'utc'){
                $offset=get_option('gmt_offset');
                $utc_time = strtotime($incremental_schedule_data['db_current_day']) + $offset * 60 * 60;
                $incremental_schedule_data['db_current_day_hour'] = date("H", $utc_time);
                $incremental_schedule_data['db_current_day_minute'] = date("i", $utc_time);
            }
            else{
                $offset=get_option('gmt_offset');
                $utc_time = strtotime($incremental_schedule_data['db_current_day']) - $offset * 60 * 60;
                $incremental_schedule_data['db_current_day'] = date("H:i", $utc_time);
            }*/

            $new_incremental_schedule[$incremental_schedule_id]['id'] = $incremental_schedule_data['id'];
            $new_incremental_schedule[$incremental_schedule_id]['files_schedule_id'] = $incremental_schedule_data['files_schedule_id'];
            $new_incremental_schedule[$incremental_schedule_id]['db_schedule_id'] = $incremental_schedule_data['db_schedule_id'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_recurrence'] = $incremental_schedule_data['incremental_recurrence'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_recurrence_week'] = $incremental_schedule_data['incremental_recurrence_week'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_recurrence_day'] = $incremental_schedule_data['incremental_recurrence_day'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_files_recurrence'] = $incremental_schedule_data['incremental_files_recurrence'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_db_recurrence'] = $incremental_schedule_data['incremental_db_recurrence'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_db_recurrence_week'] = $incremental_schedule_data['incremental_db_recurrence_week'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_db_recurrence_day'] = $incremental_schedule_data['incremental_db_recurrence_day'];
            $new_incremental_schedule[$incremental_schedule_id]['db_current_day'] = $incremental_schedule_data['db_current_day'];
            $new_incremental_schedule[$incremental_schedule_id]['files_current_day'] = $incremental_schedule_data['files_current_day'];
            $new_incremental_schedule[$incremental_schedule_id]['incremental_files_start_backup'] = $incremental_schedule_data['incremental_files_start_backup'];
            $new_incremental_schedule[$incremental_schedule_id]['files_current_day_hour'] = $incremental_schedule_data['files_current_day_hour'];
            $new_incremental_schedule[$incremental_schedule_id]['files_current_day_minute'] = $incremental_schedule_data['files_current_day_minute'];
            $new_incremental_schedule[$incremental_schedule_id]['db_current_day_hour'] = $incremental_schedule_data['db_current_day_hour'];
            $new_incremental_schedule[$incremental_schedule_id]['db_current_day_minute'] = $incremental_schedule_data['db_current_day_minute'];

            $new_incremental_schedule[$incremental_schedule_id]['backup_files'] = $incremental_schedule_data['backup_files'];
            $new_incremental_schedule[$incremental_schedule_id]['backup_files']['exclude_files'] = array();
            $new_incremental_schedule[$incremental_schedule_id]['backup_files']['exclude_file_type'] = '';

            $new_incremental_schedule[$incremental_schedule_id]['backup_db'] = $incremental_schedule_data['backup_db'];
            $new_incremental_schedule[$incremental_schedule_id]['backup_db']['exclude_files'] = array();
            $new_incremental_schedule[$incremental_schedule_id]['backup_db']['exclude_file_type'] = '';

            if(isset($incremental_schedule_data['exclude_files']) && !empty($incremental_schedule_data['exclude_files']))
            {
                $exclude_path=explode("\n", $incremental_schedule_data['exclude_files']);
                foreach ($exclude_path as $item)
                {
                    $item = str_replace('/wp-content', WP_CONTENT_DIR, $item);
                    $item = str_replace('\\', '/', $item);
                    if(file_exists($item))
                    {
                        $arr['path'] = $item;
                        if(is_dir($item)){
                            $arr['type'] = 'folder';
                        }
                        if(is_file($item)){
                            $arr['type'] = 'file';
                        }
                        $new_incremental_schedule[$incremental_schedule_id]['backup_files']['exclude_files'][] = $arr;
                        $new_incremental_schedule[$incremental_schedule_id]['backup_db']['exclude_files'][] = $arr;
                    }
                }
            }

            if(isset($incremental_schedule_data['exclude_file_type']) && !empty($incremental_schedule_data['exclude_file_type']))
            {
                $new_incremental_schedule[$incremental_schedule_id]['backup_files']['exclude_file_type'] = $incremental_schedule_data['exclude_file_type'];
                $new_incremental_schedule[$incremental_schedule_id]['backup_db']['exclude_file_type'] = $incremental_schedule_data['exclude_file_type'];
            }

            $new_incremental_schedule[$incremental_schedule_id]['backup'] = $incremental_schedule_data['backup'];

            //
            //set file start time
            if(isset($incremental_schedule_data['incremental_recurrence'])){
                $time['type']=$incremental_schedule_data['incremental_recurrence'];
            }
            else{
                $time['type']='wpvivid_weekly';
            }
            if(isset($incremental_schedule_data['incremental_recurrence_week'])) {
                $time['start_time']['week']=$incremental_schedule_data['incremental_recurrence_week'];
            }
            else
                $time['start_time']['week']='mon';
            if(isset($incremental_schedule_data['incremental_recurrence_day'])) {
                $time['start_time']['day']=$incremental_schedule_data['incremental_recurrence_day'];
            }
            else
                $time['start_time']['day']='01';
            if(isset($incremental_schedule_data['files_current_day'])) {
                $time['start_time']['current_day']=$incremental_schedule_data['files_current_day'];
            }
            else
                $time['start_time']['current_day']="00:00";
            $timestamp=WPvivid_Schedule_addon::get_start_time($time);
            $new_incremental_schedule[$incremental_schedule_id]['files_start_time']=$timestamp;

            //set db start time
            if(isset($incremental_schedule_data['incremental_db_recurrence'])){
                $time['type']=$incremental_schedule_data['incremental_db_recurrence'];
            }
            else{
                $time['type']='wpvivid_weekly';
            }
            if(isset($incremental_schedule_data['incremental_db_recurrence_week'])) {
                $time['start_time']['week']=$incremental_schedule_data['incremental_db_recurrence_week'];
            }
            else
                $time['start_time']['week']='mon';
            if(isset($incremental_schedule_data['incremental_db_recurrence_day'])) {
                $time['start_time']['day']=$incremental_schedule_data['incremental_db_recurrence_day'];
            }
            else
                $time['start_time']['day']='01';
            if(isset($incremental_schedule_data['db_current_day'])) {
                $time['start_time']['current_day']=$incremental_schedule_data['db_current_day'];
            }
            else
                $time['start_time']['current_day']="00:00";
            $timestamp=WPvivid_Schedule_addon::get_start_time($time);
            $new_incremental_schedule[$incremental_schedule_id]['db_start_time']=$timestamp;
            //
        }

        $schedule_data = $new_incremental_schedule[$new_incremental_schedule_id];
        $schedules=array();
        $schedules[$schedule_data['id']]=$schedule_data;
        update_option('wpvivid_incremental_schedules',$schedules,'no');
        update_option('wpvivid_incremental_backup_data',array(),'no');

        self::wpvivid_enable_incremental_schedule_ex($incremental_schedule_enable);

        $ret['result']='success';
        return $ret;
    }

    /***** wpvivid mainwp white label filter *****/
    public function wpvivid_set_white_label_setting_addon_mainwp($data){
        $setting = $data['setting'];
        $ret=array();
        try {
            if(isset($setting)&&!empty($setting)) {
                $json_setting = $setting;
                $json_setting = stripslashes($json_setting);
                $setting = json_decode($json_setting, true);
                if (is_null($setting)) {
                    $ret['error']='bad parameter';
                    return $ret;
                }
                update_option('white_label_setting', $setting, 'no');
            }

            $ret['result']='success';
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('error'=>$message);
        }
        return $ret;
    }
}