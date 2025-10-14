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
class WPvivid_custom_backup_selector
{
    public $id;
    public $option;

    public function __construct( $id,$option )
    {
        $this->id=$id;
        $this->option=$option;
    }

    public static function get_incremental_setting(){
        $history = get_option('wpvivid_incremental_backup_history', array());
        return $history;
    }

    public static function set_incremental_file_settings($options){
        $history = get_option('wpvivid_incremental_backup_history', array());

        $custom_option['database_check_ex'] = isset($options['database_check_ex']) ? $options['database_check_ex'] : 0;
        $custom_option['folder_check_ex'] = isset($options['folder_check_ex']) ? $options['folder_check_ex'] : 0;
        $custom_option['exclude_custom'] = isset($options['exclude_custom']) ? $options['exclude_custom'] : 0;

        $custom_option['database_option']['database_check'] = isset($options['database_check']) ? $options['database_check'] : 0;
        $custom_option['database_option']['exclude_table_list'] = isset($options['database_list']) ? $options['database_list'] : array();

        $custom_option['themes_option']['themes_check'] = isset($options['themes_check']) ? $options['themes_check'] : 0;
        $custom_option['themes_option']['exclude_themes_list'] = isset($options['themes_list']) ? $options['themes_list'] : array();
        $custom_option['themes_option']['themes_extension_list'] = isset($options['themes_extension']) ? $options['themes_extension'] : array();

        $custom_option['plugins_option']['plugins_check'] = isset($options['plugins_check']) ? $options['plugins_check'] : 0;
        $custom_option['plugins_option']['exclude_plugins_list'] = isset($options['plugins_list']) ? $options['plugins_list'] : array();
        $custom_option['plugins_option']['plugins_extension_list'] = isset($options['plugins_extension']) ? $options['plugins_extension'] : array();

        $custom_option['uploads_option']['uploads_check'] = isset($options['uploads_check']) ? $options['uploads_check'] : 0;
        $custom_option['uploads_option']['exclude_uploads_list'] = isset($options['uploads_list']) ? $options['uploads_list'] : array();
        $custom_option['uploads_option']['uploads_extension_list'] = isset($options['upload_extension']) ? $options['upload_extension'] : array();

        $custom_option['content_option']['content_check'] = isset($options['content_check']) ? $options['content_check'] : 0;
        $custom_option['content_option']['exclude_content_list'] = isset($options['content_list']) ? $options['content_list'] : array();
        $custom_option['content_option']['content_extension_list'] = isset($options['content_extension']) ? $options['content_extension'] : array();

        $custom_option['core_option']['core_check'] = isset($options['core_check']) ? $options['core_check'] : 0;

        $custom_option['other_option']['other_check'] = isset($options['other_check']) ? $options['other_check'] : 0;
        $custom_option['other_option']['include_other_list'] = isset($options['other_list']) ? $options['other_list'] : array();
        $custom_option['other_option']['other_extension_list'] = isset($options['other_extension']) ? $options['other_extension'] : array();

        $custom_option['additional_database_option']['additional_database_check'] = isset($options['additional_database_check']) ? $options['additional_database_check'] : 0;
        if(isset($history['incremental_file']['additional_database_option'])) {
            $custom_option['additional_database_option'] = $history['incremental_file']['additional_database_option'];
        }

        $history['incremental_file'] = $custom_option;
        WPvivid_Setting::update_option('wpvivid_incremental_backup_history', $history);
    }

    public static function get_incremental_file_settings()
    {
        $history = get_option('wpvivid_incremental_backup_history', array());
        if(isset($history['incremental_file'])){
            $options = $history['incremental_file'];
        }
        else{
            $options = array();
        }
        return $options;
    }

    public static function set_incremental_db_setting($options){
        $history = get_option('wpvivid_incremental_backup_history', array());

        $custom_option['database_check_ex'] = isset($options['database_check_ex']) ? $options['database_check_ex'] : 0;
        $custom_option['folder_check_ex'] = isset($options['folder_check_ex']) ? $options['folder_check_ex'] : 0;
        $custom_option['exclude_custom'] = isset($options['exclude_custom']) ? $options['exclude_custom'] : 0;

        $custom_option['database_option']['database_check'] = isset($options['database_check']) ? $options['database_check'] : 0;
        $custom_option['database_option']['exclude_table_list'] = isset($options['database_list']) ? $options['database_list'] : array();

        $custom_option['themes_option']['themes_check'] = isset($options['themes_check']) ? $options['themes_check'] : 0;
        $custom_option['themes_option']['exclude_themes_list'] = isset($options['themes_list']) ? $options['themes_list'] : array();
        $custom_option['themes_option']['themes_extension_list'] = isset($options['themes_extension']) ? $options['themes_extension'] : array();

        $custom_option['plugins_option']['plugins_check'] = isset($options['plugins_check']) ? $options['plugins_check'] : 0;
        $custom_option['plugins_option']['exclude_plugins_list'] = isset($options['plugins_list']) ? $options['plugins_list'] : array();
        $custom_option['plugins_option']['plugins_extension_list'] = isset($options['plugins_extension']) ? $options['plugins_extension'] : array();

        $custom_option['uploads_option']['uploads_check'] = isset($options['uploads_check']) ? $options['uploads_check'] : 0;
        $custom_option['uploads_option']['exclude_uploads_list'] = isset($options['uploads_list']) ? $options['uploads_list'] : array();
        $custom_option['uploads_option']['uploads_extension_list'] = isset($options['upload_extension']) ? $options['upload_extension'] : array();

        $custom_option['content_option']['content_check'] = isset($options['content_check']) ? $options['content_check'] : 0;
        $custom_option['content_option']['exclude_content_list'] = isset($options['content_list']) ? $options['content_list'] : array();
        $custom_option['content_option']['content_extension_list'] = isset($options['content_extension']) ? $options['content_extension'] : array();

        $custom_option['core_option']['core_check'] = isset($options['core_check']) ? $options['core_check'] : 0;

        $custom_option['other_option']['other_check'] = isset($options['other_check']) ? $options['other_check'] : 0;
        $custom_option['other_option']['include_other_list'] = isset($options['other_list']) ? $options['other_list'] : array();
        $custom_option['other_option']['other_extension_list'] = isset($options['other_extension']) ? $options['other_extension'] : array();

        if(isset($history['incremental_db']['additional_database_option'])) {
            $custom_option['additional_database_option'] = $history['incremental_db']['additional_database_option'];
        }
        $custom_option['additional_database_option']['additional_database_check'] = isset($options['additional_database_check']) ? $options['additional_database_check'] : 0;

        $history['incremental_db'] = $custom_option;
        WPvivid_Setting::update_option('wpvivid_incremental_backup_history', $history);
    }

    public static function get_incremental_db_setting(){
        $history = get_option('wpvivid_incremental_backup_history', array());
        if(isset($history['incremental_db'])){
            $options = $history['incremental_db'];
        }
        else{
            $options = array();
        }
        return $options;
    }
}