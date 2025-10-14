<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Setting_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class WPvivid_Setting_addon
{
    public $main_tab;

    public function __construct()
    {
        //add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),11);
        //dashboard
        //add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        //add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);
        //add_action('wpvivid_dashboard_menus_sidebar',array( $this,'setting_sidebar'),11);

        //ajax
        add_action('wp_ajax_wpvivid_add_send_mail', array($this, 'add_send_mail'));
        add_action('wp_ajax_wpvivid_set_general_setting_addon', array($this, 'set_general_setting'));
        add_action('wp_ajax_wpvivid_junk_files_info_ex',array( $this,'junk_files_info_ex'));
        add_action('wp_ajax_wpvivid_clean_local_storage_ex',array( $this,'clean_local_storage_ex'));
        add_action('wp_ajax_wpvivid_addon_clean_out_of_date_backup',array($this,'clean_out_of_date_backup'));
        add_action('wp_ajax_wpvivid_check_outside_backup_folder', array($this, 'check_outside_backup_folder'));

        //filters
        add_filter('wpvivid_set_general_setting', array($this, 'wpvivid_set_general_setting'), 9, 3);
        add_filter('wpvivid_get_setting_addon', array($this, 'get_setting_addon'), 11);
        add_filter('wpvivid_get_mail_option_addon', array($this, 'get_mail_option_addon'), 11);
        add_filter('wpvivid_export_setting_addon', array($this, 'export_setting_addon'), 11);
        add_filter('wpvivid_check_setting_addon', array($this, 'wpvivid_check_setting_addon'), 11);
        add_filter('wpvivid_set_mail_subject', array($this, 'set_mail_subject'), 11, 2);
        add_filter('wpvivid_set_mail_body', array($this, 'set_mail_body'),11, 2);
        add_filter('wpvivid_get_oldest_backup_ids', array($this, 'get_oldest_backup_ids'), 11, 4);

        add_filter('wpvividdashboard_pro_setting_tab', array($this, 'setting_tab'), 10);
        add_filter('wpvivid_trim_import_info', array($this, 'trim_import_info'));
    }

    public function setting_tab($tabs)
    {
        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup'))
        {
            $args['span_class']='dashicons dashicons-backup wpvivid-dashicons-blue';
            $args['span_style']='padding-right:0.5em;margin-top:0.1em;';
            $args['is_parent_tab']=0;
            $tabs['general_setting']['title']='Backup';
            $tabs['general_setting']['slug']='general_setting';
            $tabs['general_setting']['callback']=array($this, 'output_general_setting');
            $tabs['general_setting']['args']=$args;

            $args['span_class']='dashicons dashicons-admin-settings wpvivid-dashicons-green';
            $args['span_style']='color:red;padding-right:0.5em;margin-top:0.1em;';
            $tabs['advance_setting']['title']='Backup (Advanced)';
            $tabs['advance_setting']['slug']='advance_setting';
            $tabs['advance_setting']['callback']=array($this, 'output_advance_setting');
            $tabs['advance_setting']['args']=$args;
        }
        return $tabs;
    }

    public function trim_import_info($json)
    {
        if(isset($json['data']['wpvivid_email_setting_addon']) && !empty($json['data']['wpvivid_email_setting_addon']))
        {
            if(isset($json['data']['wpvivid_email_setting_addon']['mail_title']))
            {
                global $wpvivid_backup_pro;
                $default_mail_title = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
                $json['data']['wpvivid_email_setting_addon']['mail_title'] = $default_mail_title;
            }
        }
        return $json;
    }

    public function wpvivid_set_general_setting($setting_data, $setting, $options)
    {
        global $wpvivid_plugin;
        remove_filter('wpvivid_set_general_setting', array($wpvivid_plugin, 'wpvivid_set_general_setting'));

        $setting_data['wpvivid_common_setting']['backup_params'] = $setting['backup_params'];
        if($setting_data['wpvivid_common_setting']['backup_params'] === 'low')
        {
            $setting_data['wpvivid_common_setting']['compress_file_count'] = '500';
            $setting_data['wpvivid_common_setting']['max_file_size'] = '200';
            $setting_data['wpvivid_common_setting']['max_backup_table'] = '1000';
            $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '400';
            $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
            $setting_data['wpvivid_common_setting']['max_execution_time'] = 300;
            $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
            $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
        }
        else if($setting_data['wpvivid_common_setting']['backup_params'] === 'mid')
        {
            $setting_data['wpvivid_common_setting']['compress_file_count'] = '2000';
            $setting_data['wpvivid_common_setting']['max_file_size'] = '1024';
            $setting_data['wpvivid_common_setting']['max_backup_table'] = '3000';
            $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '1024';
            $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
            $setting_data['wpvivid_common_setting']['max_execution_time'] = 500;
            $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
            $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
        }
        else if($setting_data['wpvivid_common_setting']['backup_params'] === 'high')
        {
            $setting_data['wpvivid_common_setting']['compress_file_count'] = '10000';
            $setting_data['wpvivid_common_setting']['max_file_size'] = '4080';
            $setting_data['wpvivid_common_setting']['max_backup_table'] = '6000';
            $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '4080';
            $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
            $setting_data['wpvivid_common_setting']['max_execution_time'] = 900;
            $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
            $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
        }
        else if($setting_data['wpvivid_common_setting']['backup_params'] === 'custom')
        {
            $setting_data['wpvivid_common_setting']['compress_file_count'] = $setting['compress_file_count'];
            $setting_data['wpvivid_common_setting']['max_file_size'] = $setting['max_file_size'];
            $setting_data['wpvivid_common_setting']['max_backup_table'] = $setting['max_backup_table'];
            $setting_data['wpvivid_common_setting']['max_sql_file_size'] = $setting['max_sql_file_size'];
            $setting_data['wpvivid_common_setting']['exclude_file_size'] = intval($setting['exclude_file_size']);
            $setting_data['wpvivid_common_setting']['max_execution_time'] = intval($setting['max_execution_time']);
            $setting_data['wpvivid_common_setting']['memory_limit'] = $setting['memory_limit'].'M';
            $setting_data['wpvivid_common_setting']['migrate_size'] = $setting['migrate_size'];
        }

        //$setting['restore_max_execution_time'] = intval($setting['restore_max_execution_time']);

        if(isset($setting['max_resume_count'])){
            $setting['max_resume_count'] = intval($setting['max_resume_count']);
        }
        else{
            $setting['max_resume_count'] = 6;
        }

        $setting_data['wpvivid_email_setting_addon']['send_to'] = $setting['send_to'];
        $setting_data['wpvivid_email_setting_addon']['always'] = $setting['always'];
        $email_enable = '0';
        foreach($setting['send_to'] as $email => $value){
            if($value['email_enable'] == '1'){
                $email_enable = '1';
            }
        }
        $setting_data['wpvivid_email_setting_addon']['email_enable'] = $email_enable;
        $setting_data['wpvivid_email_setting_addon']['use_mail_title'] = $setting['use_mail_title'];
        $setting_data['wpvivid_email_setting_addon']['mail_title'] = $setting['mail_title'];
        $setting_data['wpvivid_email_setting_addon']['email_attach_log'] = $setting['email_attach_log'];
        $setting_data['wpvivid_common_setting']['use_adaptive_settings'] = $setting['use_adaptive_settings'];
        $setting_data['wpvivid_common_setting']['auto_delete_backup_log'] = $setting['auto_delete_backup_log'];
        $setting_data['wpvivid_common_setting']['backup_database_use_primary_key'] = $setting['backup_database_use_primary_key'];
        $setting_data['wpvivid_common_setting']['backup_upload_use_cm_store'] = $setting['backup_upload_use_cm_store'];

        $setting_data['wpvivid_common_setting']['local_backup_folder'] = $setting['local_backup_folder'];
        $setting_data['wpvivid_local_setting']['path'] = $setting['path'];
        $setting_data['wpvivid_local_setting']['outside_path'] = $setting['outside_path'];
        if($setting['local_backup_folder'] === 'content_folder')
        {
            $setting_data['wpvivid_common_setting']['log_save_location'] = $setting['path'].'/wpvivid_log';
            if($options['options']['wpvivid_local_setting']['path'] !== $setting['path'])
            {
                if(file_exists(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$options['options']['wpvivid_local_setting']['path']))
                {
                    @rename(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$options['options']['wpvivid_local_setting']['path'], WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$setting['path']);
                }
            }
        }
        else
        {
            $setting_data['wpvivid_common_setting']['log_save_location_outside'] = $setting['outside_path'].'/wpvivid_log';
        }

        $setting_data['wpvivid_local_setting']['save_local'] = $options['options']['wpvivid_local_setting']['save_local'];

        //$setting_data['wpvivid_common_setting']['restore_max_execution_time'] = $setting['restore_max_execution_time'];
        $setting_data['wpvivid_common_setting']['clean_old_remote_before_backup'] = $setting['clean_old_remote_before_backup'];
        $setting_data['wpvivid_common_setting']['show_admin_bar'] = $setting['show_admin_bar'];
        if(isset($setting['domain_include'])){
            $setting_data['wpvivid_common_setting']['domain_include'] = $setting['domain_include'];
        }
        $setting_data['wpvivid_common_setting']['estimate_backup'] = $setting['estimate_backup'];
        $setting_data['wpvivid_common_setting']['max_resume_count'] = $setting['max_resume_count'];

        //$setting_data['wpvivid_common_setting']['restore_memory_limit'] = $setting['restore_memory_limit'].'M';
        $setting_data['wpvivid_common_setting']['ismerge'] = $setting['ismerge'];
        $setting_data['wpvivid_common_setting']['zip_method'] = $setting['zip_method'];
        $setting_data['wpvivid_common_setting']['backup_prefix'] = $setting['backup_prefix'];
        $setting_data['wpvivid_common_setting']['db_connect_method'] = $setting['db_connect_method'];
        $setting_data['wpvivid_common_setting']['retain_local'] = $setting['retain_local'];
        $setting_data['wpvivid_common_setting']['remove_out_of_date'] = $setting['remove_out_of_date'];
        $setting_data['wpvivid_common_setting']['uninstall_clear_folder'] = $setting['uninstall_clear_folder'];
        $setting_data['wpvivid_common_setting']['hide_admin_update_notice'] = $setting['hide_admin_update_notice'];
        $setting_data['wpvivid_common_setting']['backup_symlink_folder'] = $setting['backup_symlink_folder'];

        $setting_data['wpvivid_common_setting']['encrypt_db'] = $setting['encrypt_db'];
        $setting_data['wpvivid_common_setting']['encrypt_db_password'] = $setting['encrypt_db_password'];

        $setting_data['wpvivid_common_setting']['default_backup_local'] = $setting['default_backup_local'];

        $setting_data['wpvivid_common_setting']['manual_max_backup_count'] = intval($setting['manual_max_backup_count']);
        $setting_data['wpvivid_common_setting']['manual_max_backup_db_count'] = intval($setting['manual_max_backup_db_count']);
        $setting_data['wpvivid_common_setting']['manual_max_remote_backup_count'] = intval($setting['manual_max_remote_backup_count']);
        $setting_data['wpvivid_common_setting']['manual_max_remote_backup_db_count'] = intval($setting['manual_max_remote_backup_db_count']);

        $setting_data['wpvivid_common_setting']['schedule_max_backup_count'] = intval($setting['schedule_max_backup_count']);
        $setting_data['wpvivid_common_setting']['schedule_max_backup_db_count'] = intval($setting['schedule_max_backup_db_count']);
        $setting_data['wpvivid_common_setting']['schedule_max_remote_backup_count'] = intval($setting['schedule_max_remote_backup_count']);
        $setting_data['wpvivid_common_setting']['schedule_max_remote_backup_db_count'] = intval($setting['schedule_max_remote_backup_db_count']);

        $setting_data['wpvivid_common_setting']['incremental_max_backup_count'] = intval($setting['incremental_max_backup_count']);
        $setting_data['wpvivid_common_setting']['incremental_max_db_count'] = intval($setting['incremental_max_db_count']);
        $setting_data['wpvivid_common_setting']['incremental_max_remote_backup_count'] = intval($setting['incremental_max_remote_backup_count']);

        if(isset($options['options']['wpvivid_common_setting']['rollback_max_backup_count']))
            $setting_data['wpvivid_common_setting']['rollback_max_backup_count'] = intval($options['options']['wpvivid_common_setting']['rollback_max_backup_count']);
        if(isset($options['options']['wpvivid_common_setting']['rollback_max_remote_backup_count']))
            $setting_data['wpvivid_common_setting']['rollback_max_remote_backup_count'] = intval($options['options']['wpvivid_common_setting']['rollback_max_remote_backup_count']);

        if(isset($setting['clean_local_storage_recurrence']))
            $setting_data['wpvivid_common_setting']['clean_local_storage']['recurrence']=$setting['clean_local_storage_recurrence'];
        if(isset($setting['clean_local_storage_log']))
            $setting_data['wpvivid_common_setting']['clean_local_storage']['log']= intval($setting['clean_local_storage_log']);
        if(isset($setting['clean_local_storage_backup_cache']))
            $setting_data['wpvivid_common_setting']['clean_local_storage']['backup_cache']= intval($setting['clean_local_storage_backup_cache']);
        if(isset($setting['clean_local_storage_junk_files']))
            $setting_data['wpvivid_common_setting']['clean_local_storage']['junk_files']= intval($setting['clean_local_storage_junk_files']);

        return $setting_data;
    }

    public function get_setting_addon($get_options){
        $get_options[] = 'wpvivid_email_setting_addon';
        $get_options[] = 'wpvivid_auto_update_addon';
        return $get_options;
    }

    public function get_mail_option_addon($option){
        $option=WPvivid_Setting::get_option('wpvivid_email_setting_addon');
        $tmp_email = array();
        if(!empty($option['send_to'])){
            foreach ($option['send_to'] as $email => $value){
                $tmp_email[] = $email;
            }
            $option['send_to'] = $tmp_email;
        }
        return $option;
    }

    public function export_setting_addon($json)
    {
        $default = array();
        $schedules = get_option('wpvivid_email_setting_addon', $default);
        $json['data']['wpvivid_email_setting_addon'] = $schedules;

        $default_count = WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT;
        $incremental_remote_backup_count = get_option('wpvivid_incremental_remote_backup_count_addon', $default_count);
        $json['data']['wpvivid_incremental_remote_backup_count_addon'] = $incremental_remote_backup_count;

        return $json;
    }

    public function wpvivid_check_setting_addon($res)
    {
        $ret = 'addon';
        return $ret;
    }

    public function set_mail_subject($subject, $task)
    {
        $status=$task['status']['str'];
        if($status=='completed')
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
        $localtime=gmdate('m-d-Y H:i:s', $task['status']['start_time']+$offset*60*60);
        $subject='['.$mail_title.'Backup '.$status.']'.$localtime.sprintf(' - By %s', apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin'));
        return $subject;
    }

    public function set_mail_body($body, $task){
        $status=$task['status']['str'];
        if($status=='completed')
        {
            $status='Succeeded';
        }
        else
        {
            $status='Failed. '.$task['status']['error'];
        }
        $type=$task['type'];
        if($type === 'Cron')
        {
            $type = 'Cron-Schedule';
        }
        $offset=get_option('gmt_offset');
        $start_time=date("m-d-Y H:i:s",$task['status']['start_time']+$offset*60*60);
        $end_time=date("m-d-Y H:i:s",time()+$offset*60*60);
        $running_time=($task['status']['run_time']-$task['status']['start_time']).'s';
        $remote_options= $task['options']['remote_options'];
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

        $content='';
        $backup_options=$task['options']['backup_options'];
        if($backup_options!==false)
        {
            if(isset($backup_options['backup']['backup_custom_db'])){
                $content .= 'Database, ';
            }
            if(isset($backup_options['backup']['backup_custom_themes'])){
                $content .= 'Themes, ';
            }
            if(isset($backup_options['backup']['backup_custom_plugin'])){
                $content .= 'Plugins, ';
            }
            if(isset($backup_options['backup']['backup_custom_uploads'])){
                $content .= 'Uploads, ';
            }
            if(isset($backup_options['backup']['backup_custom_content'])){
                $content .= 'WP-content, ';
            }
            if(isset($backup_options['backup']['backup_custom_core'])){
                $content .= 'WordPress Core, ';
            }
            if($content !== ''){
                $content=substr($content,0,-2);
            }
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
                                            <span><img src="https://wpvivid.com/wp-content/uploads/2019/02/wpvivid-logo.png" title="WPvivid.com"></span>            
                                        </td>
                                    </tr>
                                    </tbody>
		                        </table>
                            </td>
                            <td width="100%" bgcolor="#F5F7F8" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                <table width="100%" style="border-spacing:0;color:#111111" bgcolor="#F5F7F8">
                                    <tbody>
                                    <tr>
                                        <td style="padding-top:10px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p style="Margin-top:0px;margin-bottom:0px;font-size:13px;line-height:16px"><strong><a href="https://twitter.com/wpvividcom" style="text-decoration:none;color:#111111" target="_blank">24/7 Support: <u></u>Twitter<u></u></a></strong></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:0px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p class="m_764812426175198487customerinfo" style="Margin-top:5px;margin-bottom:0px;font-size:13px;line-height:16px">Or <u></u><a href="https://wpvivid.com/contact-us">Email Us</a><u></u></p>
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
                        <table bgcolor="#ffffff" width="100%" align="left" border="0" cellspacing="0" cellpadding="0" style="color:#111111">
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
                                                    Plugin Page: <a href="https://wordpress.org/plugins/wpvivid-backuprestore/">https://wordpress.org/plugins/wpvivid-backuprestore/</a>
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
                                                        <a href="https://wpvivid.com/contact-us">Contact Us</a> or <a href="https://twitter.com/wpvividcom">Twitter</a>
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

    public function get_oldest_backup_ids($oldest_ids,$multiple,$backup_type=false,$backup_content=false)
    {
        $oldest_ids=array();
        if($backup_type === false)
        {
            $manual_oldest_backup_ids=$this->get_oldest_backup_ids_ex('Manual',$multiple,$backup_content);
            $oldest_ids=array_merge($oldest_ids,$manual_oldest_backup_ids);
            $cron_oldest_backup_ids=$this->get_oldest_backup_ids_ex('Cron',$multiple,$backup_content);
            $oldest_ids=array_merge($oldest_ids,$cron_oldest_backup_ids);
            $rollback_oldest_backup_ids=$this->get_oldest_backup_ids_ex('Rollback',$multiple,$backup_content);
            $oldest_ids=array_merge($oldest_ids,$rollback_oldest_backup_ids);
            $incremental_oldest_backup_ids=$this->get_oldest_backup_ids_ex('Incremental',$multiple,$backup_content);
            $oldest_ids=array_merge($oldest_ids,$incremental_oldest_backup_ids);
        }
        else
        {
            $oldest_backup_ids=$this->get_oldest_backup_ids_ex($backup_type,$multiple,$backup_content);
            $oldest_ids=array_merge($oldest_ids,$oldest_backup_ids);
        }

        return $oldest_ids;
        /*
        $option=get_option('wpvivid_common_setting');

        if(isset($option['max_backup_count']))
            $backup_count = $option['max_backup_count'];
        else
            $backup_count = 30;
        if(isset($option['max_backup_db_count']))
            $db_count = $option['max_backup_db_count'];
        else
            $db_count = 30;

        if($multiple)
        {
            $backup_ids = $this->get_out_of_date_backup($backup_count);
            if(!empty($backup_ids))
                $oldest_ids=array_merge($oldest_ids,$backup_ids);
            $backup_ids = $this->get_out_of_date_db($db_count);
            if(!empty($backup_ids))
                $oldest_ids=array_merge($oldest_ids,$backup_ids);

            if(empty($oldest_ids))
            {
                return false;
            }
            else
            {
                return $oldest_ids;
            }
        }
        else
        {
            $backup_ids = $this->get_out_of_date_backup($backup_count-1);
            if(!empty($backup_ids))
                $oldest_ids[]=array_shift($backup_ids);
            $backup_ids = $this->get_out_of_date_db($db_count-1);
            if(!empty($backup_ids))
                $oldest_ids[]=array_shift($backup_ids);
            if(empty($oldest_ids))
            {
                return false;
            }
            else
            {
                return $oldest_ids;
            }
        }*/
    }

    public function get_oldest_backup_ids_ex($type,$multiple,$backup_content=false)
    {
        $oldest_ids=array();
        $option=get_option('wpvivid_common_setting');
        if($type=='Manual')
        {
            if(isset($option['manual_max_backup_count']))
            {
                $backup_count = $option['manual_max_backup_count'];
            }
            else
            {
                if(isset($option['max_backup_count']))
                    $backup_count = $option['max_backup_count'];
                else
                    $backup_count = 30;
            }

            if(isset($option['manual_max_backup_db_count']))
            {
                $db_count = $option['manual_max_backup_db_count'];
            }
            else
            {
                if(isset($option['max_backup_db_count']))
                    $db_count = $option['max_backup_db_count'];
                else
                    $db_count = 30;
            }
        }
        else if($type=='Cron')
        {
            if(isset($option['schedule_max_backup_count']))
            {
                $backup_count = $option['schedule_max_backup_count'];
            }
            else
            {
                if(isset($option['max_backup_count']))
                    $backup_count = $option['max_backup_count'];
                else
                    $backup_count = 30;
            }

            if(isset($option['schedule_max_backup_db_count']))
            {
                $db_count = $option['schedule_max_backup_db_count'];
            }
            else
            {
                if(isset($option['max_backup_db_count']))
                    $db_count = $option['max_backup_db_count'];
                else
                    $db_count = 30;
            }
        }
        else if($type=='Rollback')
        {
            if(isset($option['rollback_max_backup_count']))
            {
                $backup_count = $option['rollback_max_backup_count'];
            }
            else
            {
                if(isset($option['max_backup_count']))
                    $backup_count = $option['max_backup_count'];
                else
                    $backup_count = 30;
            }

            $db_count=$backup_count;
        }
        else if($type=='Incremental')
        {
            $incremental_remote_backup_count = WPvivid_Setting::get_option('wpvivid_incremental_remote_backup_count_addon', 3);
            if(isset($option['incremental_max_backup_count']))
            {
                $backup_count = $option['incremental_max_backup_count'];
            }
            else
            {
                $backup_count = $incremental_remote_backup_count;
            }

            if(isset($option['incremental_max_db_count']))
            {
                $db_count = $option['incremental_max_db_count'];
            }
            else
            {
                $db_count = 30;
            }
        }
        else
        {
            $backup_count=0;
            $db_count=0;
        }

        if(!$multiple)
        {
            if($backup_count!==0)
                $backup_count=$backup_count-1;
            if($db_count!==0)
                $db_count=$db_count-1;
        }

        /*if($backup_count!==0)
        {
            $backup_ids = $this->get_out_of_date_backup_ex($type,$backup_count,false);
            if(!empty($backup_ids))
                $oldest_ids=array_merge($oldest_ids,$backup_ids);
        }

        if($db_count!==0)
        {
            $backup_ids = $this->get_out_of_date_backup_ex($type,$db_count,true);
            if(!empty($backup_ids))
                $oldest_ids=array_merge($oldest_ids,$backup_ids);
        }*/

        if($backup_content === false)
        {
            if($backup_count!==0)
            {
                $backup_ids = $this->get_out_of_date_backup_ex($type,$backup_count,false);
                if(!empty($backup_ids))
                    $oldest_ids=array_merge($oldest_ids,$backup_ids);
            }

            if($db_count!==0)
            {
                $backup_ids = $this->get_out_of_date_backup_ex($type,$db_count,true);
                if(!empty($backup_ids))
                    $oldest_ids=array_merge($oldest_ids,$backup_ids);
            }
        }
        else if($backup_content === 'file')
        {
            if($backup_count!==0)
            {
                $backup_ids = $this->get_out_of_date_backup_ex($type,$backup_count,false);
                if(!empty($backup_ids))
                    $oldest_ids=array_merge($oldest_ids,$backup_ids);
            }
        }
        else if($backup_content === 'db')
        {
            if($db_count!==0)
            {
                $backup_ids = $this->get_out_of_date_backup_ex($type,$db_count,true);
                if(!empty($backup_ids))
                    $oldest_ids=array_merge($oldest_ids,$backup_ids);
            }
        }

        if(empty($oldest_ids))
        {
            return array();
        }
        else
        {
            return $oldest_ids;
        }
    }
    /***** setting filters end *****/

    /***** setting useful function begin *****/
    public function check_setting_option($data)
    {
        $ret['result']=WPVIVID_PRO_FAILED;
        if(!isset($data['max_file_size']))
        {
            $ret['error']=__('The value of \'split a backup every this size\' can\'t be empty.', 'wpvivid');
            return $ret;
        }

        $data['max_file_size']=sanitize_text_field($data['max_file_size']);

        if(empty($data['max_file_size']) && $data['max_file_size'] != '0')
        {
            $ret['error']=__('The value of \'split a backup every this size\' can\'t be empty.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['max_backup_table']))
        {
            $ret['error']=__('\'The number of database tables compressed to each zip\' cannot be empty.', 'wpvivid');
            return $ret;
        }

        $data['max_backup_table']=sanitize_text_field($data['max_backup_table']);

        if(empty($data['max_backup_table']) && $data['max_backup_table'] != '0')
        {
            $ret['error']=__('\'The number of database tables compressed to each zip\' cannot be empty.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['exclude_file_size']))
        {
            $ret['error']=__('The value of \'exclude files lager than this size\' can\'t be empty.', 'wpvivid');
        }

        $data['exclude_file_size']=sanitize_text_field($data['exclude_file_size']);

        if(empty($data['exclude_file_size']) && $data['exclude_file_size'] != '0')
        {
            $ret['error']=__('The value of \'exclude files lager than this size\' can\'t be empty.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['max_execution_time']))
        {
            $ret['error']=__('The value of \'maximum PHP script execution time for a backup task\' can\'t be empty.', 'wpvivid');
        }

        $data['max_execution_time']=sanitize_text_field($data['max_execution_time']);

        if(empty($data['max_execution_time']) && $data['max_execution_time'] != '0')
        {
            $ret['error']=__('The value of \'maximum PHP script execution time for a backup task\' can\'t be empty.', 'wpvivid');
            return $ret;
        }

        /*if(!isset($data['restore_max_execution_time']))
        {
            $ret['error']=__('The value of \'maximum PHP script execution time for a restore task\' can\'t be empty.', 'wpvivid');
        }
        $data['restore_max_execution_time']=sanitize_text_field($data['restore_max_execution_time']);
        if(empty($data['restore_max_execution_time']) && $data['restore_max_execution_time'] != '0')
        {
            $ret['error']=__('The value of \'maximum PHP script execution time for a restore task\' can\'t be empty.', 'wpvivid');
            return $ret;
        }*/

        if(!isset($data['memory_limit']))
        {
            $ret['error']=__('The value of \'maximum PHP memory for a backup task\' can\'t be empty.', 'wpvivid');
        }
        $data['memory_limit']=sanitize_text_field($data['memory_limit']);
        if(empty($data['memory_limit']) && $data['memory_limit'] != '0')
        {
            $ret['error']=__('The value of \'maximum PHP memory for a backup task\' can\'t be empty.', 'wpvivid');
            return $ret;
        }

        /*if(!isset($data['restore_memory_limit']))
        {
            $ret['error']=__('The value of \'maximum PHP memory for a restore task\' can\'t be empty.', 'wpvivid');
        }
        $data['restore_memory_limit']=sanitize_text_field($data['restore_memory_limit']);
        if(empty($data['restore_memory_limit']) && $data['restore_memory_limit'] != '0')
        {
            $ret['error']=__('The value of \'maximum PHP memory for a restore task\' can\'t be empty.', 'wpvivid');
            return $ret;
        }*/

        if(!isset($data['migrate_size']))
        {
            $ret['error']=__('The value of \'chunk size\' can\'t be empty.', 'wpvivid');
        }
        $data['migrate_size']=sanitize_text_field($data['migrate_size']);
        if(empty($data['migrate_size']) && $data['migrate_size'] != '0')
        {
            $ret['error']=__('The value of \'chunk size\' can\'t be empty.', 'wpvivid');
            return $ret;
        }

        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-use-image-cleaner'))
        {
            if(!isset($data['wpvivid_uc_scan_limit']))
            {
                $ret['error']=__('The value of \'Posts Quantity Processed Per Request\' can\'t be empty.', 'wpvivid');
            }
            $data['wpvivid_uc_scan_limit']=sanitize_text_field($data['wpvivid_uc_scan_limit']);
            if(empty($data['wpvivid_uc_scan_limit']) && $data['wpvivid_uc_scan_limit'] != '0')
            {
                $ret['error']=__('The value of \'Posts Quantity Processed Per Request\' can\'t be empty.', 'wpvivid');
                return $ret;
            }

            if(!isset($data['wpvivid_uc_files_limit']))
            {
                $ret['error']=__('The value of \'Media Files Quantity Processed Per Request\' can\'t be empty.', 'wpvivid');
            }
            $data['wpvivid_uc_files_limit']=sanitize_text_field($data['wpvivid_uc_files_limit']);
            if(empty($data['wpvivid_uc_files_limit']) && $data['wpvivid_uc_files_limit'] != '0')
            {
                $ret['error']=__('The value of \'Media Files Quantity Processed Per Request\' can\'t be empty.', 'wpvivid');
                return $ret;
            }
        }

        if(!isset($data['path']))
        {
            $ret['error']=__('The local storage path is required.', 'wpvivid');
        }

        $data['path']=sanitize_text_field($data['path']);

        if(empty($data['path']))
        {
            $ret['error']=__('The local storage path is required.', 'wpvivid');
            return $ret;
        }

        if(isset($data['domain_include'])){
            if($data['domain_include'] == '1'){
                if(!isset($data['backup_prefix']) || empty($data['backup_prefix'])){
                    $ret['error']=__('A prefix for backup files is required. Please enter a valid prefix or uncheck the option.', 'wpvivid');
                    return $ret;
                }
            }
        }

        if(isset($data['use_mail_title'])){
            if($data['use_mail_title'] == '1'){
                if(!isset($data['mail_title']) || empty($data['mail_title'])){
                    $ret['error']=__('Please specify a custom title to emails or disable the customization for email title.', 'wpvivid');
                    return $ret;
                }
            }
        }

        if(isset($data['db_connect_method']) && $data['db_connect_method'] === 'pdo') {
            if (class_exists('PDO')) {
                $extensions = get_loaded_extensions();
                if (!array_search('pdo_mysql', $extensions)) {
                    $ret['error'] = __('The pdo_mysql extension is not detected. Please install the extension first or choose wpdb option for Database connection method.', 'wpvivid');
                    return $ret;
                }
            } else {
                $ret['error'] = __('The pdo_mysql extension is not detected. Please install the extension first or choose wpdb option for Database connection method.', 'wpvivid');
                return $ret;
            }
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public static function is_db_backup($backup)
    {
        if(isset($backup['backup_info']['types']))
        {
            $has_db = false;
            $has_file = false;
            foreach ($backup['backup_info']['types'] as $backup_type => $backup_data)
            {
                if($backup_type==='Database')
                {
                    $has_db=true;
                }
                else if($backup_type==='Additional Databases')
                {
                    $has_db=true;
                }
                else if($backup_type==='Others')
                {
                    $has_file=true;
                }
                else if($backup_type==='themes' || $backup_type==='plugins' || $backup_type==='uploads' || $backup_type==='wp-content' || $backup_type==='Wordpress Core')
                {
                    $has_file=true;
                }
            }
            if($has_file)
            {
                return false;
            }
            else if($has_db)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            $backup_item=new WPvivid_New_Backup_Item($backup);

            $files=$backup_item->get_files(false);
            if(sizeof($files)==1)
            {
                $file=array_shift($files);
                if(WPvivid_backup_pro_function::is_wpvivid_db_backup($file))
                {
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
    }

    public function get_out_of_date_backup_ex($type,$max_count,$db_only)
    {
        $list = get_option('wpvivid_backup_list');
        $backups_list=array();
        foreach ($list as $k=>$backup)
        {
            if($backup['type']!==$type)
            {
                continue;
            }

            if (!empty($backup['lock'])&&$backup['lock'] != 0)
            {
                continue;
            }

            if($db_only)
            {
                if(self::is_db_backup($backup))
                {
                    $backups_list[$k]=$backup;
                }
            }
            else
            {
                if(!self::is_db_backup($backup))
                {
                    $backups_list[$k]=$backup;
                }
            }

        }
        $size=sizeof($backups_list);
        $out_of_date_list=array();
        //if($max_count==0)
            //return $out_of_date_list;

        while($size>$max_count)
        {
            $oldest_id=WPvivid_Backuplist::get_oldest_backup_id($backups_list);
            if(!empty($oldest_id))
            {
                $out_of_date_list[]=$oldest_id;
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
        return $out_of_date_list;
    }

    public static function get_out_of_date_backup($max_count)
    {
        $list = get_option('wpvivid_backup_list');
        $backups_list=array();
        foreach ($list as $k=>$backup)
        {
            if(!self::is_db_backup($backup))
            {
                $backups_list[$k]=$backup;
            }
        }
        $size=sizeof($backups_list);
        $out_of_date_list=array();

        if($max_count==0)
            return $out_of_date_list;

        while($size>$max_count)
        {
            $oldest_id=WPvivid_Backuplist::get_oldest_backup_id($backups_list);

            if(!empty($oldest_id))
            {
                $out_of_date_list[]=$oldest_id;
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
        return $out_of_date_list;
    }

    public static function get_out_of_date_db($max_count)
    {
        $list = get_option('wpvivid_backup_list');
        $db_list=array();
        foreach ($list as $k=>$backup)
        {
            if(self::is_db_backup($backup))
            {
                $db_list[$k]=$backup;
            }
        }

        $size=sizeof($db_list);
        $out_of_date_list=array();

        if($max_count==0)
            return $out_of_date_list;

        while($size>$max_count)
        {
            $oldest_id=WPvivid_Backuplist::get_oldest_backup_id($db_list);

            if(!empty($oldest_id))
            {
                $out_of_date_list[]=$oldest_id;
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

        return $out_of_date_list;
    }

    public function _junk_files_info()
    {
        global $wpvivid_plugin;
        global $wpvivid_backup_pro;
        try
        {
            $ret['log_path'] = $log_dir = $wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder();
            $log_dir_byte = $this->GetDirectorySize($ret['log_path']);
            $ret['log_dir_size'] = size_format($log_dir_byte,2);

            $ret['old_files_path'] = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . WPVIVID_DEFAULT_ROLLBACK_DIR;
            $dir = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
            $ret['junk_path'] = $dir;

            $backup_dir_byte = $this->GetDirectorySize($dir);
            $ret['backup_dir_size'] = size_format($backup_dir_byte,2);

            $ret['sum_size'] = size_format($backup_dir_byte + $log_dir_byte,2);
        }
        catch (Exception $e)
        {
            $ret['log_path'] = $log_dir = $wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder();
            $ret['old_files_path'] = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . WPVIVID_DEFAULT_ROLLBACK_DIR;
            $dir = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
            $ret['junk_path'] = $dir;
            $ret['sum_size'] = '0';
        }
        return $ret;
    }

    private function GetDirectorySize($path)
    {
        $bytes_total = 0;
        $path = realpath($path);
        if($path!==false && $path!='' && file_exists($path))
        {
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object)
            {
                $bytes_total += $object->getSize();
            }
        }
        return $bytes_total;
    }

    public function get_dir_files(&$files,&$folder,$path,$except_regex,$exclude_files=array(),$exclude_folder=array(),$exclude_file_size=0,$flag = true)
    {
        $handler=opendir($path);
        if($handler===false)
            return;
        while(($filename=readdir($handler))!==false)
        {
            if($filename != "." && $filename != "..")
            {
                $dir=str_replace('/',DIRECTORY_SEPARATOR,$path.DIRECTORY_SEPARATOR.$filename);


                if(in_array($dir,$exclude_folder))
                {
                    continue;
                }
                else if(is_dir($path.DIRECTORY_SEPARATOR.$filename))
                {
                    if($except_regex!==false)
                    {
                        if($this -> regex_match($except_regex['file'],$path.DIRECTORY_SEPARATOR.$filename,$flag)){
                            continue;
                        }
                        $folder[]=$path.DIRECTORY_SEPARATOR.$filename;
                    }else
                    {
                        $folder[]=$path.DIRECTORY_SEPARATOR.$filename;
                    }
                    $this->get_dir_files($files ,$folder, $path.DIRECTORY_SEPARATOR.$filename,$except_regex,$exclude_folder);
                }else {
                    if($except_regex===false||!$this -> regex_match($except_regex['file'] ,$path.DIRECTORY_SEPARATOR.$filename,$flag))
                    {
                        if(in_array($filename,$exclude_files))
                        {
                            continue;
                        }
                        if($exclude_file_size==0)
                        {
                            $files[] = $path.DIRECTORY_SEPARATOR.$filename;
                        }
                        else if(filesize($path.DIRECTORY_SEPARATOR.$filename)<$exclude_file_size*1024*1024)
                        {
                            $files[] = $path.DIRECTORY_SEPARATOR.$filename;
                        }
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);

    }

    private function regex_match($regex_array,$filename,$flag){
        if($flag){
            if(empty($regex_array)){
                return false;
            }
            if(is_array($regex_array)){
                foreach ($regex_array as $regex)
                {
                    if(preg_match($regex,$filename))
                    {
                        return true;
                    }
                }
            }else{
                if(preg_match($regex_array,$filename))
                {
                    return true;
                }
            }
            return false;
        }else{
            if(empty($regex_array)){
                return true;
            }
            if(is_array($regex_array)){
                foreach ($regex_array as $regex)
                {
                    if(preg_match($regex,$filename))
                    {
                        return false;
                    }
                }
            }else{
                if(preg_match($regex_array,$filename))
                {
                    return false;
                }
            }
            return true;
        }
    }

    function rrmdir($src)
    {
        if(!file_exists($src))
        {
            return ;
        }

        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $src . '/' . $file;
                if ( is_dir($full) ) {
                    $this->rrmdir($full);
                }
                else {
                    @unlink($full);
                }
            }
        }
        closedir($dir);
        @rmdir($src);
    }
    /***** setting useful function end *****/

    /***** setting ajax begin *****/
    public function add_send_mail()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            if (isset($_POST['send_to']) && !empty($_POST['send_to']) && is_string($_POST['send_to']))
            {
                $send_to = sanitize_email($_POST['send_to']);
                if (empty($send_to))
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = __('Invalid email address', 'wpvivid');
                    echo json_encode($ret);
                } else {
                    $subject = 'WPvivid Test Mail';
                    $body = 'This is a test mail from WPvivid backup plugin';
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    if (wp_mail($send_to, $subject, $body, $headers) === false)
                    {
                        $ret['result'] = 'failed';
                        $ret['error'] = __('Unable to send email. Please check the configuration of email server.', 'wpvivid');
                    } else {
                        $ret['result'] = 'success';
                        $ret['html']  =  '';
                        $ret['html'] .= '<tr>';
                        $ret['html'] .= '<td class="row-title" option="email_list"><label for="tablecell">'.$send_to.'</label></td>';
                        $ret['html'] .= '<td onclick="wpvivid_remove_mail(this);">';
                        $ret['html'] .= '<a href="#"><span class="dashicons dashicons-trash wpvivid-dashicons-grey"></span></a>';
                        $ret['html'] .= '</td>';
                        $ret['html'] .= '</tr>';
                    }
                    echo json_encode($ret);
                }
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        die();
    }

    public function set_general_setting()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('manage_options');

        $ret=array();

        try
        {
            if(isset($_POST['setting'])&&!empty($_POST['setting']))
            {
                $json_setting = $_POST['setting'];
                $json_setting = stripslashes($json_setting);
                $setting = json_decode($json_setting, true);
                if (is_null($setting))
                {
                    echo 'json decode failed';
                    die();
                }
                $ret = $this->check_setting_option($setting);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    echo json_encode($ret);
                    die();
                }
                $options=WPvivid_Setting::get_setting(true, "");
                $setting_data = array();
                $setting_data= apply_filters('wpvivid_set_general_setting',$setting_data, $setting, $options);
                $ret['setting']=WPvivid_Setting::update_setting($setting_data);

                if(isset($setting['incremental_remote_retain'])){
                    $incremental_remote_retain = intval($setting['incremental_remote_retain']);
                    WPvivid_Setting::update_option('wpvivid_incremental_remote_backup_count_addon', $incremental_remote_retain);
                }

                if($setting_data['wpvivid_common_setting']['remove_out_of_date']) {
                    if (isset($_POST['backup_retain_changed']) && $_POST['backup_retain_changed'] == '1') {
                        set_time_limit(120);
                        $backup_ids = array();
                        $backup_ids = apply_filters('wpvivid_get_oldest_backup_ids', $backup_ids, true);
                        global $wpvivid_plugin;
                        foreach ($backup_ids as $backup_id) {
                            WPvivid_Custom_Interface_addon::delete_backup_by_id($backup_id);
                        }
                        WPvivid_Setting::update_option('wpvivid_backup_remote_need_update', true);
                    }
                }
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        echo json_encode($ret);
        die();
    }

    public function junk_files_info_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
            $ret['result'] = 'success';
            $ret['data'] = $this->_junk_files_info_ex();
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        die();
    }

    public function clean_local_storage_ex()
    {
        global $wpvivid_backup_pro,$wpvivid_plugin;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');

        try
        {
            $backup_list=new WPvivid_New_BackupList();
            if(!isset($_POST['options'])||empty($_POST['options'])||!is_string($_POST['options']))
            {
                die();
            }
            $options=$_POST['options'];
            $options =stripslashes($options);
            $options=json_decode($options,true);
            if(is_null($options))
            {
                die();
            }
            if($options['log']=='0' && $options['backup_cache']=='0' && $options['junk_files']=='0' && $options['old_files']=='0')
            {
                $ret['result']=WPVIVID_FAILED;
                $ret['msg']=__('Choose at least one type of junk files for deleting.', 'wpvivid');
                echo json_encode($ret);
                die();
            }
            $delete_files = array();
            $delete_folder=array();
            if($options['log']=='1')
            {
                $log_dir=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder();
                $error_log_dir=$log_dir.DIRECTORY_SEPARATOR.'error';
                $log_files=array();
                $temp=array();
                if(file_exists($log_dir))
                {
                    $this -> get_dir_files($log_files,$temp,$log_dir,array('file' => '&wpvivid-&'),array(),array(),0,false);
                }
                if(file_exists($error_log_dir))
                {
                    $this -> get_dir_files($log_files,$temp,$error_log_dir,array('file' => '&wpvivid-&'),array(),array(),0,false);
                }
                foreach ($log_files as $file)
                {
                    $file_name=basename($file);
                    $id=substr ($file_name,0,21);
                    if($backup_list->get_backup_by_id($id)===false)
                    {
                        $delete_files[]=$file;
                    }
                }


            }

            if($options['backup_cache']=='1')
            {
                $remote_backups=$backup_list->get_all_remote_backup();
                foreach ($remote_backups as $id=>$backup)
                {
                    $backup_item = new WPvivid_New_Backup_Item($backup);
                    $backup_item->cleanup_local_backup();
                }

                WPvivid_tools::clean_junk_cache();
            }

            if($options['junk_files']=='1')
            {
                $list=$backup_list->get_all_backup();
                $files=array();
                foreach ($list as $backup_id => $backup)
                {
                    $backup_item = new WPvivid_New_Backup_Item($backup);
                    $file=$backup_item->get_files(false);
                    foreach ($file as $filename)
                    {
                        $files[]=$filename;
                    }
                }

                $dir=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
                $path=str_replace('/',DIRECTORY_SEPARATOR,$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder());
                if(substr($path, -1) == DIRECTORY_SEPARATOR)
                {
                    $path = substr($path, 0, -1);
                }
                $folder[]= $path;
                $except_regex['file'][]='&wpvivid-&';
                $except_regex['file'][]='&wpvivid_temp-&';
                $except_regex['file'][]='&'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-&';
                $except_regex['file'][]='&'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'_temp-&';
                $this -> get_dir_files($delete_files,$delete_folder,$dir,$except_regex,$files,$folder,0,false);
            }

            foreach ($delete_files as $file)
            {
                if(file_exists($file))
                    @unlink($file);
            }

            foreach ($delete_folder as $folder)
            {
                if(file_exists($folder))
                    WPvivid_tools::deldir($folder,'',true);
            }

            $ret['result']='success';
            $ret['msg']=__('The selected junk files have been deleted.', 'wpvivid');
            $ret['data']=$this->_junk_files_info_ex();

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

    public function clean_out_of_date_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try
        {
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

            if(wp_get_schedule('wpvivid_clean_remote_schedule_single_event')===false)
            {
                wp_schedule_single_event(time() + 10, 'wpvivid_clean_remote_schedule_single_event');
            }

            update_option('wpvivid_backup_remote_need_update', true, 'no');

            $ret['result'] = 'success';
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

    public function wpvivid_check_is_subdirectory($parentDir, $childDir)
    {
        $parentDir = realpath($parentDir);
        $childDir = realpath($childDir);
        if ($parentDir === false || $childDir === false)
        {
            return false;
        }

        return strpos($childDir, $parentDir) === 0;
    }

    public function check_outside_backup_folder()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        $ret=array();
        try
        {
            if(!isset($_POST['outside_path'])||empty($_POST['outside_path']))
            {
                die();
            }
            $outside_path=sanitize_text_field($_POST['outside_path']);

            $outside_path = str_replace('\\\\', '\\', $outside_path);
            $outside_path = str_replace('\\', '/', $outside_path);
            $pattern = '#^(?:[a-zA-Z]:/|/)(?:[^<>:"|?*\0/]+/)*[^<>:"|?*\0/]+/?$#';

            if (!preg_match($pattern, $outside_path)) {
                $ret['result']='failed';
                $ret['error']='Invalid directory path format.';
                echo json_encode($ret);
                die();
            }

            if(file_exists($outside_path))
            {
                $test_file_path = untrailingslashit($outside_path).DIRECTORY_SEPARATOR.'wpvivid_test_file.txt';
                $file_handle = fopen($test_file_path, 'wb');
                if (!$file_handle)
                {
                    $ret['result']='failed';
                    $ret['error']='Failed to create a test file in the directory. Please check and make sure that the directory has read, write and user group permissions.';
                    echo json_encode($ret);
                }
                else
                {
                    @fclose($file_handle);
                    @unlink($test_file_path);
                    $ret['result'] = 'success';
                    echo json_encode($ret);
                }
            }
            else
            {
                $mk_res=mkdir($outside_path,0755,true);
                if($mk_res)
                {
                    $test_file_path = untrailingslashit($outside_path).DIRECTORY_SEPARATOR.'wpvivid_test_file.txt';
                    $file_handle = fopen($test_file_path, 'wb');
                    if (!$file_handle)
                    {
                        $ret['result']='failed';
                        $ret['error']='Failed to write a test file in the directory. Please check and make sure that the directory has read, write and user group permissions.';
                        echo json_encode($ret);
                    }
                    else
                    {
                        @fclose($file_handle);
                        @unlink($test_file_path);
                        $ret['result'] = 'success';
                        echo json_encode($ret);
                    }
                }
                else
                {
                    if (ini_get('open_basedir'))
                    {
                        $open_basedir_array = explode(PATH_SEPARATOR, ini_get('open_basedir'));
                        $is_subdirectory=false;
                        foreach ($open_basedir_array as $open_basedir)
                        {
                            if (@is_dir($open_basedir))
                            {
                                if ($this->wpvivid_check_is_subdirectory($open_basedir, $outside_path))
                                {
                                    $is_subdirectory=true;
                                }
                            }
                        }
                        if(!$is_subdirectory)
                        {
                            $ret['result']='failed';
                            $ret['error']='The directory is inaccessible. Please configure \'open_basedir\' to allow access to the directory or disable \'open_basedir\'.';
                            echo json_encode($ret);
                        }
                        else
                        {
                            $ret['result']='failed';
                            $ret['error']='The directory you entered does not exist and could not be created. Please ensure its parent directory has read and write and user group permissions, or try creating the full directory manually.';
                            echo json_encode($ret);
                        }
                    }
                    else
                    {
                        $ret['result']='failed';
                        $ret['error']='The directory you entered does not exist and could not be created. Please ensure its parent directory has read and write and user group permissions, or try creating the full directory manually.';
                        echo json_encode($ret);
                    }
                }
            }
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
    /***** setting ajax end *****/

    public function output_manual_backup_count_setting_page()
    {
        $options=get_option('wpvivid_common_setting');
        if(isset($options['manual_max_backup_count']))
            $manual_max_backup_count = $options['manual_max_backup_count'];
        else
            $manual_max_backup_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $manual_max_backup_count=intval($manual_max_backup_count);

        if(isset($options['manual_max_backup_db_count']))
            $manual_max_backup_db_count = $options['manual_max_backup_db_count'];
        else
            $manual_max_backup_db_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $manual_max_backup_db_count=intval($manual_max_backup_db_count);

        if(isset($options['manual_max_remote_backup_count']))
            $max_remote_backup_count = $options['manual_max_remote_backup_count'];
        else if(isset($options['max_remote_backup_count']))
            $max_remote_backup_count =$options['max_remote_backup_count'];
        else
            $max_remote_backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        $max_remote_backup_count=intval($max_remote_backup_count);
        if($max_remote_backup_count==0)
        {
            $max_remote_backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        }

        if(isset($options['manual_max_remote_backup_db_count']))
            $max_remote_backup_db_count = $options['manual_max_remote_backup_db_count'];
        else if(isset($options['max_remote_backup_db_count']))
            $max_remote_backup_db_count = $options['max_remote_backup_db_count'];
        else
            $max_remote_backup_db_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        $max_remote_backup_db_count=intval($max_remote_backup_db_count);
        if($max_remote_backup_db_count==0)
        {
            $max_remote_backup_db_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        }

        ?>
        <div>
            <div>
                <p>Manual Backup</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="manual_max_backup_count" id="manual_max_backup_count" value="<?php esc_attr_e($manual_max_backup_count); ?>"> (localhost)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="manual_max_backup_db_count" id="manual_max_backup_db_count" value="<?php esc_attr_e($manual_max_backup_db_count); ?>"> (localhost)Database backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="manual_max_remote_backup_count" id="manual_max_remote_backup_count" value="<?php esc_attr_e($max_remote_backup_count); ?>"> (remote storage)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="manual_max_remote_backup_db_count" id="manual_max_remote_backup_db_count" value="<?php esc_attr_e($max_remote_backup_db_count); ?>"> (remote storage)Database backups retained.</p>
            </div>
        </div>
        <?php
    }

    public function output_schedule_backup_count_setting_page()
    {
        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_common_setting']['schedule_max_backup_count']))
            $schedule_max_backup_count = $general_setting['options']['wpvivid_common_setting']['schedule_max_backup_count'];
        else
            $schedule_max_backup_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $schedule_max_backup_count=intval($schedule_max_backup_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['schedule_max_backup_db_count']))
            $schedule_max_backup_db_count = $general_setting['options']['wpvivid_common_setting']['schedule_max_backup_db_count'];
        else
            $schedule_max_backup_db_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $schedule_max_backup_db_count=intval($schedule_max_backup_db_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['schedule_max_remote_backup_count']))
            $max_remote_backup_count = $general_setting['options']['wpvivid_common_setting']['schedule_max_remote_backup_count'];
        else if(isset($general_setting['options']['wpvivid_common_setting']['max_remote_backup_count']))
            $max_remote_backup_count = $general_setting['options']['wpvivid_common_setting']['max_remote_backup_count'];
        else
            $max_remote_backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        $max_remote_backup_count=intval($max_remote_backup_count);
        if($max_remote_backup_count==0)
        {
            $max_remote_backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        }
        if(isset($general_setting['options']['wpvivid_common_setting']['schedule_max_remote_backup_db_count']))
            $max_remote_backup_db_count = $general_setting['options']['wpvivid_common_setting']['schedule_max_remote_backup_db_count'];
        else if(isset($general_setting['options']['wpvivid_common_setting']['max_remote_backup_db_count']))
            $max_remote_backup_db_count = $general_setting['options']['wpvivid_common_setting']['max_remote_backup_db_count'];
        else
            $max_remote_backup_db_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        $max_remote_backup_db_count=intval($max_remote_backup_db_count);

        ?>
        <div>
            <div>
                <p>Schedule(General)</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="schedule_max_backup_count" id="schedule_max_backup_count" value="<?php esc_attr_e($schedule_max_backup_count); ?>"> (localhost)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="schedule_max_backup_db_count" id="schedule_max_backup_db_count" value="<?php esc_attr_e($schedule_max_backup_db_count); ?>"> (localhost)Database backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="schedule_max_remote_backup_count" id="schedule_max_remote_backup_count" value="<?php esc_attr_e($max_remote_backup_count); ?>"> (remote storage)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="setting" name="schedule_max_remote_backup_db_count" id="schedule_max_remote_backup_db_count" value="<?php esc_attr_e($max_remote_backup_db_count); ?>"> (remote storage)Database backups retained.</p>
            </div>
        </div>
        <?php
    }

    public function output_incremental_schedule_backup_count_setting_page()
    {
        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_common_setting']['incremental_max_db_count']))
            $incremental_max_db_count = $general_setting['options']['wpvivid_common_setting']['incremental_max_db_count'];
        else
            $incremental_max_db_count = WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT;
        $incremental_max_db_count=intval($incremental_max_db_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['incremental_max_backup_count']))
            $incremental_max_backup_count = $general_setting['options']['wpvivid_common_setting']['incremental_max_backup_count'];
        else
            $incremental_max_backup_count = WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT;
        $incremental_max_backup_count=intval($incremental_max_backup_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['incremental_max_remote_backup_count']))
            $incremental_max_remote_backup_count = $general_setting['options']['wpvivid_common_setting']['incremental_max_remote_backup_count'];
        else
            $incremental_max_remote_backup_count = WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT;
        $incremental_max_remote_backup_count=intval($incremental_max_remote_backup_count);
        ?>
        <div>
            <div>
                <p>Schedule(Incremental)</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="3" option="setting" name="incremental_max_db_count" id="incremental_max_db_count" value="<?php esc_attr_e($incremental_max_db_count); ?>"> (localhost)Incremental Database Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="3" option="setting" name="incremental_max_backup_count" id="incremental_max_backup_count" value="<?php esc_attr_e($incremental_max_backup_count); ?>"> (localhost) Cycles of incremental backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="3" option="setting" name="incremental_max_remote_backup_count" id="incremental_max_remote_backup_count" value="<?php esc_attr_e($incremental_max_remote_backup_count); ?>"> (remote storage) Cycles of incremental backups retained.</p>
            </div>
        </div>
        <?php
    }

    public function check_is_a_wpvivid_backup($file_name)
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

    public function check_file_is_a_wpvivid_backup($file_name,&$backup_id)
    {
        if (WPvivid_backup_pro_function::is_wpvivid_backup($file_name))
        {
            if ($id =WPvivid_backup_pro_function::get_wpvivid_backup_id($file_name))
            {
                $backup_list=new WPvivid_New_BackupList();
                $list=$backup_list->get_all_remote_backup();
                foreach ($list as $backup_id => $backup_value)
                {
                    if($backup_id === $id)
                    {
                        return false;
                    }
                }
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

    public function get_wpvivid_backup_size()
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $backups=array();
        $count = 0;
        $ret_size = 0;
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
                                if($this->check_is_a_wpvivid_backup($path.$filename) === true)
                                {
                                    $backups[$backup_id]['files'][] = $filename;
                                }
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }
            if(!empty($backups))
            {
                foreach ($backups as $backup_id =>$backup)
                {
                    $backup_data['result']='success';
                    $backup_data['files']=array();
                    if(empty($backup['files']))
                        continue;
                    foreach ($backup['files'] as $file)
                    {
                        $ret_size += filesize($path.$file);
                    }
                }
            }
        }
        else{
            $ret_size = 0;
        }
        return $ret_size;
    }

    public function _junk_files_info_ex()
    {
        try {
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;

            $memory_limit = @ini_get('memory_limit');
            $unit = strtoupper(substr($memory_limit, -1));
            if ($unit == 'K')
            {
                $memory_limit_tmp = intval($memory_limit) * 1024;
            }
            else if ($unit == 'M')
            {
                $memory_limit_tmp = intval($memory_limit) * 1024 * 1024;
            }
            else if ($unit == 'G')
            {
                $memory_limit_tmp = intval($memory_limit) * 1024 * 1024 * 1024;
            }
            else{
                $memory_limit_tmp = intval($memory_limit);
            }
            if ($memory_limit_tmp < 256 * 1024 * 1024)
            {
                @ini_set('memory_limit', '256M');
            }

            $log_dir=$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder();
            $log_dir_byte = $this->GetDirectorySize($log_dir);
            $ret['log_dir_size'] = $wpvivid_plugin->formatBytes($log_dir_byte);

            $ret['backup_cache_size'] = 0;
            $home_url_prefix=get_home_url();
            $parse = parse_url($home_url_prefix);
            $tmppath = '';
            if(isset($parse['path'])) {
                $parse['path'] = str_replace('/', '_', $parse['path']);
                $parse['path'] = str_replace('.', '_', $parse['path']);
                $tmppath = $parse['path'];
            }
            $home_url_prefix = $parse['host'].$tmppath;
            $path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
            $backup_dir_byte = $this->GetDirectorySize($path);
            $ret['sum_size'] = $wpvivid_plugin->formatBytes($backup_dir_byte);

            $handler=opendir($path);
            if($handler===false)
            {
                $ret['backup_cache_size'] = 0;
            }
            while(($filename=readdir($handler))!==false)
            {
                if(preg_match('#pclzip-.*\.tmp#', $filename)){
                    $ret['backup_cache_size'] += filesize($path.DIRECTORY_SEPARATOR.$filename);
                }
                if(preg_match('#pclzip-.*\.gz#', $filename)){
                    $ret['backup_cache_size'] += filesize($path.DIRECTORY_SEPARATOR.$filename);
                }
            }
            @closedir($handler);

            $backup_list=new WPvivid_New_BackupList();
            $list = $backup_list->get_all_remote_backup();
            $remote_files=array();
            foreach ($list as $backup_id => $backup)
            {
                if(isset($backup['lock']) && $backup['lock'] == '1')
                {
                    continue;
                }

                $backup_item = new WPvivid_New_Backup_Item($backup);
                $file=$backup_item->get_files(false);
                foreach ($file as $filename)
                {
                    if(file_exists($path.DIRECTORY_SEPARATOR.$filename))
                    {
                        $ret['backup_cache_size'] += filesize($path.DIRECTORY_SEPARATOR.$filename);
                    }
                }
            }
            $ret['backup_cache_size'] = $wpvivid_plugin->formatBytes($ret['backup_cache_size']);


            $ret['junk_size'] = 0;
            $delete_files  = array();
            $delete_folder = array();
            $list=$backup_list->get_all_backup();
            $files=array();
            foreach ($list as $backup_id => $backup)
            {
                $backup_item = new WPvivid_New_Backup_Item($backup);
                $file=$backup_item->get_files(false);
                foreach ($file as $filename)
                {
                    $files[]=$filename;
                }
            }

            $dir=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
            $path=str_replace('/',DIRECTORY_SEPARATOR,$wpvivid_backup_pro->wpvivid_pro_log->GetSaveLogFolder());
            if(substr($path, -1) == DIRECTORY_SEPARATOR)
            {
                $path = substr($path, 0, -1);
            }
            $folder[]= $path;
            $except_regex['file'][]='&wpvivid-&';
            $except_regex['file'][]='&wpvivid_temp-&';
            $except_regex['file'][]='&'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'-&';
            $except_regex['file'][]='&'.apply_filters('wpvivid_white_label_file_prefix', 'wpvivid').'_temp-&';
            $this -> get_dir_files($delete_files,$delete_folder,$dir,$except_regex,$files,$folder,0,false);

            foreach ($delete_files as $file)
            {
                if(file_exists($file))
                {
                    $ret['junk_size'] += filesize($file);
                }
            }

            foreach ($delete_folder as $folder)
            {
                if(file_exists($folder))
                {
                    $ret['junk_size'] += $this->GetDirectorySize($folder);
                }
            }
            $ret['junk_size'] = $wpvivid_plugin->formatBytes($ret['junk_size']);

            $ret['backup_size'] = $this->get_wpvivid_backup_size();
            $ret['backup_size'] = $wpvivid_plugin->formatBytes($ret['backup_size']);
        }
        catch (Exception $e)
        {
            $ret['log_path'] = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() .DIRECTORY_SEPARATOR . 'wpvivid_log';
            $ret['old_files_path'] = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . WPVIVID_DEFAULT_ROLLBACK_DIR;
            $dir = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
            $ret['junk_path'] = $dir;
            $ret['sum_size'] = '0';
            $ret['log_dir_size'] = '0';
            $ret['backup_cache_size'] = '0';
            $ret['junk_size'] = '0';
            $ret['backup_size'] = '0';
        }
        return $ret;
    }

    public function output_general_setting()
    {
        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_common_setting']['max_backup_count']))
            $display_backup_count = $general_setting['options']['wpvivid_common_setting']['max_backup_count'];
        else
            $display_backup_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $display_backup_count=intval($display_backup_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['max_remote_backup_count']))
            $display_remote_backup_count = $general_setting['options']['wpvivid_common_setting']['max_remote_backup_count'];
        else
            $display_remote_backup_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        $display_remote_backup_count=intval($display_remote_backup_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['max_backup_db_count']))
            $display_backup_db_count = $general_setting['options']['wpvivid_common_setting']['max_backup_db_count'];
        else
            $display_backup_db_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $display_backup_db_count=intval($display_backup_db_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['max_remote_backup_db_count']))
            $display_remote_backup_db_count = $general_setting['options']['wpvivid_common_setting']['max_remote_backup_db_count'];
        else
            $display_remote_backup_db_count = WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT;
        $display_remote_backup_db_count=intval($display_remote_backup_db_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['clean_old_remote_before_backup'])){
            if($general_setting['options']['wpvivid_common_setting']['clean_old_remote_before_backup']){
                $clean_old_remote_before_backup = 'checked';
            }
            else{
                $clean_old_remote_before_backup = '';
            }
        }
        else{
            $clean_old_remote_before_backup = 'checked';
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['estimate_backup'])&&$general_setting['options']['wpvivid_common_setting']['estimate_backup']){
            $wpvivid_setting_estimate_backup='checked';
        }
        else{
            $wpvivid_setting_estimate_backup='';
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['show_admin_bar'])){
            $show_admin_bar = 'checked';
        }
        else{
            if($general_setting['options']['wpvivid_common_setting']['show_admin_bar']){
                $show_admin_bar = 'checked';
            }
            else{
                $show_admin_bar = '';
            }
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['ismerge'])){
            $wpvivid_ismerge = 'checked';
        }
        else{
            if($general_setting['options']['wpvivid_common_setting']['ismerge'] == '1'){
                $wpvivid_ismerge = 'checked';
            }
            else{
                $wpvivid_ismerge = '';
            }
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['retain_local'])){
            $wpvivid_retain_local = '';
        }
        else{
            if($general_setting['options']['wpvivid_common_setting']['retain_local'] == '1'){
                $wpvivid_retain_local = 'checked';
            }
            else{
                $wpvivid_retain_local = '';
            }
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['remove_out_of_date'])){
            $wpvivid_remove_out_of_date = '';
        }
        else{
            if($general_setting['options']['wpvivid_common_setting']['remove_out_of_date'] == '1'){
                $wpvivid_remove_out_of_date = 'checked';
            }
            else{
                $wpvivid_remove_out_of_date = '';
            }
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['uninstall_clear_folder'])){
            $uninstall_clear_folder = '';
        }
        else{
            if($general_setting['options']['wpvivid_common_setting']['uninstall_clear_folder'] == '1'){
                $uninstall_clear_folder = 'checked';
            }
            else{
                $uninstall_clear_folder = '';
            }
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['domain_include'])){
            $wpvivid_domain_include = 'checked';
            $prefix_input_style = '';
        }
        else{
            if($general_setting['options']['wpvivid_common_setting']['domain_include']){
                $wpvivid_domain_include = 'checked';
                $prefix_input_style = '';
            }
            else{
                $wpvivid_domain_include = '';
                $prefix_input_style = 'readonly="readonly"';
            }
        }

        $wpvivid_setting_email_always='';
        $wpvivid_setting_email_failed='';
        if(isset($general_setting['options']['wpvivid_email_setting_addon']['always'])) {
            if ($general_setting['options']['wpvivid_email_setting_addon']['always']) {
                $wpvivid_setting_email_always = 'checked';
            } else {
                $wpvivid_setting_email_failed = 'checked';
            }
        }
        else{
            $wpvivid_setting_email_always = 'checked';
        }
        if(isset($general_setting['options']['wpvivid_email_setting_addon']['email_attach_log'])){
            if ($general_setting['options']['wpvivid_email_setting_addon']['email_attach_log']) {
                $wpvivid_email_attach_log = 'checked';
            } else {
                $wpvivid_email_attach_log = '';
            }
        }
        else{
            $wpvivid_email_attach_log = 'checked';
        }
        if(isset($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title'])){
            if($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title']){
                $wpvivid_use_mail_title = 'checked';
                $wpvivid_mail_title_style = '';
            }
            else{
                $wpvivid_use_mail_title = '';
                $wpvivid_mail_title_style = 'readonly="readonly"';
            }
        }
        else{
            $wpvivid_use_mail_title = 'checked';
            $wpvivid_mail_title_style = '';
        }
        global $wpvivid_backup_pro;
        $default_mail_title = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
        $mail_title = isset($general_setting['options']['wpvivid_email_setting_addon']['mail_title']) ? $general_setting['options']['wpvivid_email_setting_addon']['mail_title'] : $default_mail_title;

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

        if(isset($general_setting['options']['wpvivid_common_setting']['encrypt_db']))
        {
            if($general_setting['options']['wpvivid_common_setting']['encrypt_db'] == '1')
            {
                $encrypt_db_check='checked';
                $encrypt_db_disable='';
            }
            else{
                $encrypt_db_check='';
                $encrypt_db_disable='readonly="readonly"';
            }

        }
        else
        {
            $encrypt_db_check='';
            $encrypt_db_disable='readonly="readonly"';
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['encrypt_db_password']))
        {
            $password=$general_setting['options']['wpvivid_common_setting']['encrypt_db_password'];
        }
        else
        {
            $password='';
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['default_backup_local']))
        {
            if($general_setting['options']['wpvivid_common_setting']['default_backup_local']){
                $default_backup_local = 'checked';
                $default_backup_remote = '';
            }
            else{
                $default_backup_local = '';
                $default_backup_remote = 'checked';
            }
        }
        else{
            $default_backup_local = 'checked';
            $default_backup_remote = '';
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['local_backup_folder']))
        {
            if($general_setting['options']['wpvivid_common_setting']['local_backup_folder'] === 'content_folder')
            {
                $backup_content_folder = 'checked';
                $backup_outside_folder = '';
                $hide_part_content_folder = '';
                $hide_part_outside_folder = 'display: none;';
            }
            else if($general_setting['options']['wpvivid_common_setting']['local_backup_folder'] === 'outside_folder')
            {
                $backup_content_folder = '';
                $backup_outside_folder = 'checked';
                $hide_part_content_folder = 'display: none;';
                $hide_part_outside_folder = '';
            }
            else
            {
                $backup_content_folder = 'checked';
                $backup_outside_folder = '';
                $hide_part_content_folder = '';
                $hide_part_outside_folder = 'display: none;';
            }
        }
        else
        {
            $backup_content_folder = 'checked';
            $backup_outside_folder = '';
            $hide_part_content_folder = '';
            $hide_part_outside_folder = 'display: none;';
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['hide_admin_update_notice']))
        {
            if($general_setting['options']['wpvivid_common_setting']['hide_admin_update_notice'])
            {
                $hide_admin_update_notice = 'checked';
            }
            else
            {
                $hide_admin_update_notice = '';
            }
        }
        else
        {
            $hide_admin_update_notice = '';
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['backup_symlink_folder'])){
            $backup_symlink_folder = '';
        }
        else{
            if($general_setting['options']['wpvivid_common_setting']['backup_symlink_folder'] == '1'){
                $backup_symlink_folder = 'checked';
            }
            else{
                $backup_symlink_folder = '';
            }
        }

        $default = WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT;
        $incremental_remote_backup_count = WPvivid_Setting::get_option('wpvivid_incremental_remote_backup_count_addon', $default);

        if(isset($general_setting['options']['wpvivid_common_setting']['clean_local_storage']))
        {
            $clean_local_storage=$general_setting['options']['wpvivid_common_setting']['clean_local_storage'];

            if(isset($clean_local_storage['recurrence']))
                $clean_local_storage_recurrence=$clean_local_storage['recurrence'];
            else
                $clean_local_storage_recurrence='wpvivid_weekly';
            if(isset($clean_local_storage['log'])&&$clean_local_storage['log'])
                $clean_local_storage_log='checked';
            else
                $clean_local_storage_log='';
            if(isset($clean_local_storage['backup_cache'])&&$clean_local_storage['backup_cache'])
                $clean_local_storage_backup_cache='checked';
            else
                $clean_local_storage_backup_cache='';
            if(isset($clean_local_storage['junk_files'])&&$clean_local_storage['junk_files'])
                $clean_local_storage_junk_files='checked';
            else
                $clean_local_storage_junk_files='';
        }
        else
        {
            $clean_local_storage_recurrence='wpvivid_weekly';
            $clean_local_storage_log='';
            $clean_local_storage_backup_cache='';
            $clean_local_storage_junk_files='';
        }


        global $wpvivid_plugin;
        //$out_of_date=$wpvivid_plugin->_get_out_of_date_info();
        //$junk_file=$wpvivid_plugin->_junk_files_info();
        //$junk_file=$this->_junk_files_info_ex();

        $dir=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
        $out_of_date['web_server']=$dir;

        $junk_file['log_path'] = $dir .DIRECTORY_SEPARATOR . 'wpvivid_log';
        $junk_file['old_files_path'] = $dir . DIRECTORY_SEPARATOR . WPVIVID_DEFAULT_ROLLBACK_DIR;
        $junk_file['junk_path'] = $dir;
        $junk_file['sum_size'] = '0';
        $junk_file['log_dir_size'] = '0';
        $junk_file['backup_cache_size'] = '0';
        $junk_file['junk_size'] = '0';
        $junk_file['backup_size'] = '0';

        if(isset($general_setting['options']['wpvivid_local_setting']['outside_path']))
        {
            $wpvivid_option_outside_backup_dir=$general_setting['options']['wpvivid_local_setting']['outside_path'];
        }
        else
        {
            $wpvivid_option_outside_backup_dir='';
        }

        ?>
        <div>
            <table class="wp-list-table widefat plugins" style="border-left:none;border-top:none;border-right:none;">
                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">General</label></td>
                    <td>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Remove the oldest backups stored in remote storage before creating a backup if the current backups reached the limit of backup retention for remote storage. It is recommended to uncheck this option if there is a unstable connection between your site and remote storage</span>
                                <input type="checkbox" option="setting" name="clean_old_remote_before_backup" id="wpvivid_clean_old_remote" <?php esc_attr_e($clean_old_remote_before_backup); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Calculate the size of files, folder and database before backing up</span>
                                <input type="checkbox" option="setting" name="estimate_backup" id="wpvivid_estimate_backup" value="1" <?php esc_attr_e($wpvivid_setting_estimate_backup, 'wpvivid'); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Show <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> backup plugin on top admin bar</span>
                                <input type="checkbox" option="setting" name="show_admin_bar" <?php esc_attr_e($show_admin_bar); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Merge all the backup files into single package when a backup completes. This will save great disk spaces, though takes longer time. We recommended you check the option especially on sites with insufficient server resources.</span>
                                <input type="checkbox" option="setting" name="ismerge" <?php esc_attr_e($wpvivid_ismerge); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Keep storing the backups in localhost after uploading to remote storage</span>
                                <input type="checkbox" option="setting" name="retain_local" <?php esc_attr_e($wpvivid_retain_local); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>The out-of-date backups will be removed if the current value of backup retention is lower than the previous one, which is irreversible</span>
                                <input type="checkbox" option="setting" name="remove_out_of_date" <?php esc_attr_e($wpvivid_remove_out_of_date); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Delete the /<?php echo $general_setting['options']['wpvivid_local_setting']['path']; ?> folder when deleting <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Backup Pro. Caution: This folder may contain <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Pro and Free backups, once deleted, any backups in it will be permanently lost!</span>
                                <input type="checkbox" option="setting" name="uninstall_clear_folder" <?php esc_attr_e($uninstall_clear_folder); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Do not show the plugin update notice on my website pages.</span>
                                <input type="checkbox" option="setting" name="hide_admin_update_notice" <?php esc_attr_e($hide_admin_update_notice); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Backup symlink folders, recommended not check this option</span>
                                <input type="checkbox" option="setting" name="backup_symlink_folder" <?php esc_attr_e($backup_symlink_folder); ?>>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Backup Retention</label></td>
                    <td>
                        <?php
                        if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                        $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                        $args['span_class']='';
                        $args['span_style']='';
                        $args['div_style']='display:block;';
                        $args['is_parent_tab']=0;
                        $tabs['manual_backup']['title']='Manual Backup';
                        $tabs['manual_backup']['slug']='manual_backup';
                        $tabs['manual_backup']['callback']=array($this, 'output_manual_backup_count_setting_page');
                        $tabs['manual_backup']['args']=$args;

                        $args['div_style']='';
                        $tabs['general_schedule']['title']='Schedule(General)';
                        $tabs['general_schedule']['slug']='general_schedule';
                        $tabs['general_schedule']['callback']=array($this, 'output_schedule_backup_count_setting_page');
                        $tabs['general_schedule']['args']=$args;

                        $tabs['incremental_schedule']['title']='Schedule(Incremental)';
                        $tabs['incremental_schedule']['slug']='incremental_schedule';
                        $tabs['incremental_schedule']['callback']=array($this, 'output_incremental_schedule_backup_count_setting_page');
                        $tabs['incremental_schedule']['args']=$args;

                        foreach ($tabs as $key=>$tab)
                        {
                            $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                        }

                        $this->main_tab->display();
                        ?>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Default location for backups:</label></td>
                    <td>
                        <p>Set the default location for backups:</p>
                        <p></p>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="default_backup_local" value="1" <?php esc_attr_e($default_backup_local); ?> />Localhost(web server)
                                <span class="wpvivid-radio-checkmark"></span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">Cloud Storage
                                <input type="radio" option="setting" name="default_backup_local" value="0" <?php esc_attr_e($default_backup_remote); ?> />
                                <span class="wpvivid-radio-checkmark"></span>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <?php do_action('wpvivid_auto_backup_addon'); ?>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Backup folder</label></td>
                    <td>
                        <p>Set backup folder</p>
                        <p></p>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="local_backup_folder" value="content_folder" <?php esc_attr_e($backup_content_folder); ?> />Backup to wp-content folder
                                <span class="wpvivid-radio-checkmark"></span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">Backup to outside the website directory
                                <input type="radio" option="setting" name="local_backup_folder" value="outside_folder" <?php esc_attr_e($backup_outside_folder); ?> />
                                <span class="wpvivid-radio-checkmark"></span>
                            </label>
                        </fieldset>
                        <div id="wpvivid_part_backup_content_folder" style="<?php esc_attr_e($hide_part_content_folder); ?>">
                            <input type="text" placeholder="wpvividbackups" option="setting" name="path" id="wpvivid_option_backup_dir" value="<?php esc_attr_e($general_setting['options']['wpvivid_local_setting']['path'], 'wpvivid'); ?>" style="width: 290px;" onkeyup="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" /> Name your folder, this folder must be writable for creating backup files.
                        </div>
                        <div id="wpvivid_part_backup_outside_folder" style="<?php esc_attr_e($hide_part_outside_folder); ?>">
                            <p>
                                <code>Store backups outside the website directory. Enter the full path to your desired directory, for example, if your website directory is /var/www/html/wordpress, then the path can be /var/www/html/wpvividbackups.</code>
                            </p>
                            <input type="text" placeholder="/var/www/html/wpvividbackups" option="setting" name="outside_path" id="wpvivid_option_outside_backup_dir" value="<?php esc_attr_e($wpvivid_option_outside_backup_dir, 'wpvivid'); ?>" style="width: 290px;" />
                            <input class="button-secondary" id="wpvivid_check_outside_backup_folder" type="submit" value="Test Path">
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                                <div class="wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Notes:</p>
                                    <p>1. The parent folder of the specified path should have read, write permissions and user group permissions. This allows the plugin to create the backup folder. For example, if you enter /var/www/html/wpvividbackups, make sure /var/www/html has the necessary permissions.</p>
                                    <p>2. Use unique backup folders for each website to prevent accidental deletion of backups from other sites.</p>
                                    <p>3. After entering the path, click the "Test Path" button to verify its availability.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </div>
                        <p></p>
                        <div>
                            <input type="text" id="wpvivid_backup_prefix" placeholder="Enter prefix (e.g. test)" value="<?php esc_attr_e($prefix); ?>" option="setting" name="backup_prefix" style="width: 290px;" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" /> Add a prefix to all backup files
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                                <div class="wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Only letters (except for wpvivid) and numbers are allowed. This will help you identify backups if you store backups of many websites in one directory.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Database encryption</label></td>
                    <td>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span>Enable database encryption</span>
                                <input type="checkbox" id="wpvivid_encrypt_db" option="setting" name="encrypt_db" <?php esc_attr_e($encrypt_db_check); ?> />
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <input type="password" class="all-options" id="wpvivid_encrypt_db_pw" option="setting" name="encrypt_db_password" value="<?php esc_attr_e($password); ?>" <?php esc_attr_e($encrypt_db_disable); ?> /> Enter a password here to encrypt your database backups.
                        </p>
                        <p>
                            <code>The password is also required to decrypt your backups, we are not able to reset it for you or decrypt your backups, so please do write it down and store it safely. Backups encrypted with this option can only be decrypted with <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Backup Pro.</code>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Email report</label></td>
                    <td>
                        <div style="padding:0 1em 1em 0;">
                            <span class="dashicons  dashicons-warning wpvivid-dashicons-red"></span>
                            <span>Configure you email server(SMTP) with a <a href="https://wpvivid.com/8-best-smtp-plugins-for-wordpress.html">WordPress SMTP plugin</a> before using the feature</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                <div class="wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>WordPress uses the PHP Mail function to send its emails by default, which is not supported by many hosts and can cause issues if it is not set properly.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </div>
                        <p>
                            <input type="text" placeholder="example@yourdomain.com" option="setting" name="send_to" class="regular-text" id="wpvivid_mail">
                            <input class="button-secondary" id="wpvivid_send_email_test" type="submit" value="Test and Add" title="Send an email for testing mail function">
                        </p>
                        <div id="wpvivid_send_email_res" style="display: none;"></div>
                        <div>
                            <table class="widefat">
                                <tr>
                                    <th class="row-title">Email Address</th>
                                    <th>Action</th>
                                </tr>
                                <tbody id="wpvivid_email_list">
                                <?php
                                if(isset($general_setting['options']['wpvivid_email_setting_addon']['send_to'])){
                                    foreach ($general_setting['options']['wpvivid_email_setting_addon']['send_to'] as $mail => $value){
                                        if($value['email_enable'] === '1'){
                                            $check = 'checked';
                                        }
                                        else{
                                            $check = '';
                                        }
                                        ?>
                                        <tr>
                                            <td class="row-title" option="email_list"><label for="tablecell"><?php _e($value['email_address']); ?></label></td>
                                            <td onclick="wpvivid_remove_mail(this);"><a href="#"><span class="dashicons dashicons-trash wpvivid-dashicons-grey"></span></a></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="padding:1em 1em 0 0;">
                            <p></p>
                            <fieldset>
                                <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                    <input type="radio" option="setting" name="always" value="1" <?php esc_attr_e($wpvivid_setting_email_always, 'wpvivid'); ?> />Always send an email notification when a backup is complete
                                    <span class="wpvivid-radio-checkmark"></span>
                                </label>
                                <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                    <input type="radio" option="setting" name="always" value="0" <?php esc_attr_e($wpvivid_setting_email_failed, 'wpvivid'); ?> />Only send an email notification when a backup fails
                                    <span class="wpvivid-radio-checkmark"></span>
                                </label>
                            </fieldset>

                            <p>
                                <label class="wpvivid-checkbox">
                                    <span>Attach the log when sending a report</span>
                                    <input type="checkbox" option="setting" name="email_attach_log" <?php esc_attr_e($wpvivid_email_attach_log); ?> />
                                    <span class="wpvivid-checkbox-checkmark"></span>
                                </label>
                            </p>

                            <div>
                                <label class="wpvivid-checkbox">
                                    <span>Comment the email subject</span>
                                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                        <div class="wpvivid-bottom">
                                            <!-- The content you need -->
                                            <p>Add a custom subject to WPvivid backup email reports for easy identification. The default subject is the domain name of the current website.</p>
                                            <i></i> <!-- do not delete this line -->
                                        </div>
                                    </span>
                                    <input type="checkbox" option="setting" name="use_mail_title" <?php esc_attr_e($wpvivid_use_mail_title); ?> /><span class="wpvivid-checkbox-checkmark"></span>
                                </label>
                            </div>
                            <p><input type="text" id="wpvivid_mail_title" option="setting" name="mail_title" value="<?php esc_attr_e($mail_title); ?>" placeholder="www.domain.com" <?php esc_attr_e($wpvivid_mail_title_style); ?> /></p>
                            <p>
                                <span>e.g. [</span><span><?php _e($mail_title); ?></span><span><?php echo sprintf(__(': Backup Succeeded]12-04-2019 07:04:57 - By %s.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin')); ?></span>
                            </p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Export/Import settings</label></td>
                    <td>
                        <table class="widefat" style="border:none;box-shadow:none;">
                            <tr>
                                <td>
                                    <p><input id="wpvivid_setting_export" type="button" name="" value="Export"><?php echo sprintf(__('Click \'Export\' button to save %s settings on your local computer.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></p>
                                </td>
                                <td>
                                    <p><input type="file" name="fileTrans" id="wpvivid_select_import_file"></p>
                                    <p><input id="wpvivid_setting_import" type="button" name="" value="Import"><?php echo sprintf(__('Importing the json file can help you set %s\'s configuration on another wordpress site quickly.', 'wpvivid'), apply_filters('wpvivid_white_label_display', WPVIVID_PRO_PLUGIN_SLUG)); ?></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php echo sprintf(__('Web-server disk space in use by %s', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid')); ?></label></td>
                    <td>
                        <p>
                            <span><?php _e('Total Size:', 'wpvivid'); ?></span>
                            <span class="wpvivid-size-calc" id="wpvivid_junk_sum_size"><?php _e($junk_file['sum_size'], 'wpvivid'); ?></span>
                            <span style="margin-left: 5px;"><?php _e( 'Backup Size:', 'wpvivid' ); ?></span>
                            <span class="wpvivid-size-calc" id="wpvivid_backup_size"><?php _e($junk_file['backup_size'], 'wpvivid'); ?></span>
                            <input class="button-secondary" id="wpvivid_calculate_size" style="margin-left:10px;" type="submit" name="Calculate-Sizes" value="<?php esc_attr_e( 'Calculate Sizes', 'wpvivid' ); ?>" />
                        </p>

                        <p>
                            <label class="wpvivid-checkbox">
                                <span><?php _e( 'Logs Size:', 'wpvivid' ); ?></span>
                                <span class="wpvivid-size-calc" id="wpvivid_log_size"><?php _e($junk_file['log_dir_size'], 'wpvivid'); ?></span>
                                <input type="checkbox" id="wpvivid_junk_log" option="setting" name="clean_local_storage_log" value="junk-log" <?php esc_attr_e($clean_local_storage_log); ?> />
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>

                        <p>
                            <label class="wpvivid-checkbox">
                                <span><?php _e( 'Backup Cache Size:', 'wpvivid' ); ?></span>
                                <span class="wpvivid-size-calc" id="wpvivid_backup_cache_size"><?php _e($junk_file['backup_cache_size'], 'wpvivid'); ?></span>
                                <input type="checkbox" id="wpvivid_junk_backup_cache" option="setting" name="clean_local_storage_backup_cache" value="junk-backup-cache" <?php esc_attr_e($clean_local_storage_backup_cache); ?>/>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>
                        <p>
                            <label class="wpvivid-checkbox">
                                <span><?php _e( 'Junk Size:', 'wpvivid' ); ?></span>
                                <span class="wpvivid-size-calc" id="wpvivid_junk_size"><?php _e($junk_file['junk_size'], 'wpvivid'); ?></span>
                                <input type="checkbox" id="wpvivid_junk_file" option="setting" name="clean_local_storage_junk_files" value="junk-files" <?php esc_attr_e($clean_local_storage_junk_files); ?>/>
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </p>

                        <p>
                            <input class="button-primary" id="wpvivid_clean_junk_file" type="submit" name="Empty-all-files" value="<?php esc_attr_e( 'Empty Now', 'wpvivid' ); ?>" />
                            or Empty per
                            <select id="wpvivid_clean_local_storage" option="setting" name="clean_local_storage_recurrence">
                                <option value="wpvivid_hourly">Every hour</option>
                                <option value="wpvivid_2hours">Every 2 hours</option>
                                <option value="wpvivid_4hours">Every 4 hours</option>
                                <option value="wpvivid_8hours">Every 8 hours</option>
                                <option value="wpvivid_12hours">Every 12 hours</option>
                                <option value="wpvivid_daily">Daily</option>
                                <option value="wpvivid_2days">Every 2 days</option>
                                <option value="wpvivid_weekly">Weekly</option>
                                <option value="wpvivid_fortnightly">Fortnightly</option>
                                <option value="wpvivid_monthly">Every 30 days</option>
                            </select>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php _e('Remove out-of-date backups', 'wpvivid'); ?></label></td>
                    <td>
                        <p>
                            <span><?php _e('Web Server Directory:', 'wpvivid'); ?></span><span id="wpvivid_out_of_date_local_path"><?php _e($out_of_date['web_server'], 'wpvivid'); ?></span>
                        </p>

                        <p>
                            <span style="margin-right: 2px;"><?php _e('Remote Storage Directory:', 'wpvivid'); ?></span>
                            <span id="wpvivid_out_of_date_remote_path">
                                <?php
                                $wpvivid_get_remote_directory = '';
                                $wpvivid_get_remote_directory = apply_filters('wpvivid_get_remote_directory', $wpvivid_get_remote_directory);
                                echo $wpvivid_get_remote_directory;
                                ?>
                            </span>
                        </p>

                        <p>
                            <input class="button-primary" id="wpvivid_delete_out_of_backup" style="margin-right:10px;" type="submit" name="delete-out-of-backup" value="<?php esc_attr_e( 'Remove', 'wpvivid' ); ?>" />
                        </p>

                        <p><?php _e('The action is irreversible! It will remove all backups which are out-of-date (including local web server and remote storage) if they exist.', 'wpvivid'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <div style="padding:1em 1em 0 0;"><input class="button-primary wpvivid_setting_general_save" type="submit" value="Save Changes"></div>
        <script>
            var backup_retain_changed = '0';
            var local_backup_count = '<?php echo $display_backup_count; ?>';
            var remote_backup_count = '<?php echo $display_remote_backup_count; ?>';
            var local_db_backup_count = '<?php echo $display_backup_db_count; ?>';
            var remote_db_backup_count = '<?php echo $display_remote_backup_db_count; ?>';

            jQuery('#wpvivid_check_outside_backup_folder').click(function(){
                var outside_path = jQuery('input[option=setting][name=outside_path]').val();
                var ajax_data={
                    'action': 'wpvivid_check_outside_backup_folder',
                    'outside_path': outside_path
                };
                wpvivid_post_request_addon(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success") {
                            has_check_outside_folder = true;
                            alert('Success: Path is valid and accessible!');
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('checking outside backup folder', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('input:radio[option=setting][name=local_backup_folder]').click(function() {
                if(this.value === 'content_folder'){
                    jQuery('#wpvivid_part_backup_content_folder').show();
                    jQuery('#wpvivid_part_backup_outside_folder').hide();
                }
                else if(this.value === 'outside_folder'){
                    jQuery('#wpvivid_part_backup_content_folder').hide();
                    jQuery('#wpvivid_part_backup_outside_folder').show();
                }
                else{
                    jQuery('#wpvivid_part_backup_content_folder').show();
                    jQuery('#wpvivid_part_backup_outside_folder').hide();
                }
            });

            jQuery('#wpvivid_calculate_size').click(function(){
                wpvivid_calculate_diskspaceused();
            });

            jQuery('#wpvivid_clean_junk_file').click(function(){
                wpvivid_clean_junk_files();
            });

            /*function wpvivid_set_max_remote_backup_count(obj)
            {
                var max_remote_backup_count = jQuery(obj).val();
                jQuery('#manual_max_remote_backup_count').val(max_remote_backup_count);
                jQuery('#schedule_max_remote_backup_count').val(max_remote_backup_count);
            }

            function wpvivid_set_max_remote_backup_db_count(obj)
            {
                var max_remote_backup_db_count = jQuery(obj).val();
                jQuery('#manual_max_remote_backup_db_count').val(max_remote_backup_db_count);
                jQuery('#schedule_max_remote_backup_db_count').val(max_remote_backup_db_count);
            }*/

            /**
             * Calculate the server disk space in use by WPvivid.
             */
            function wpvivid_calculate_diskspaceused(){
                var ajax_data={
                    'action': 'wpvivid_junk_files_info_ex'
                };
                var current_size = jQuery('#wpvivid_junk_sum_size').html();
                jQuery('#wpvivid_calculate_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_clean_junk_file').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('.wpvivid-size-calc').html("calculating...");
                wpvivid_post_request_addon(ajax_data, function(data){
                    jQuery('#wpvivid_calculate_size').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_clean_junk_file').css({'pointer-events': 'auto', 'opacity': '1'});
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success") {
                            jQuery('#wpvivid_junk_sum_size').html(jsonarray.data.sum_size);
                            jQuery('#wpvivid_log_size').html(jsonarray.data.log_dir_size);
                            jQuery('#wpvivid_backup_cache_size').html(jsonarray.data.backup_cache_size);
                            jQuery('#wpvivid_junk_size').html(jsonarray.data.junk_size);
                            jQuery('#wpvivid_backup_size').html(jsonarray.data.backup_size);
                        }
                    }
                    catch(err){
                        //alert(err);
                        jQuery('#wpvivid_calculate_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_clean_junk_file').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_junk_sum_size').html(current_size);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('calculating server disk space in use by WPvivid', textStatus, errorThrown);
                    //alert(error_message);
                    jQuery('#wpvivid_calculate_size').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_clean_junk_file').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_junk_sum_size').html(current_size);
                });
            }

            /**
             * Clean junk files created during backups and restorations off your web server disk.
             */
            function wpvivid_clean_junk_files(){
                var descript = 'The selected item(s) will be permanently deleted. Are you sure you want to continue?';
                var ret = confirm(descript);
                if(ret === true){
                    //var option_data = wpvivid_ajax_data_transfer('junk-files');
                    var json = {};
                    if(jQuery('input:checkbox[option=setting][name=clean_local_storage_log]').prop('checked'))
                    {
                        json['log']='1';
                    }
                    else
                    {
                        json['log']='0';
                    }

                    if(jQuery('input:checkbox[option=setting][name=clean_local_storage_backup_cache]').prop('checked'))
                    {
                        json['backup_cache']='1';
                    }
                    else
                    {
                        json['backup_cache']='0';
                    }

                    if(jQuery('input:checkbox[option=setting][name=clean_local_storage_junk_files]').prop('checked'))
                    {
                        json['junk_files']='1';
                    }
                    else
                    {
                        json['junk_files']='0';
                    }
                    var option_data = JSON.stringify(json);

                    var ajax_data = {
                        'action': 'wpvivid_clean_local_storage_ex',
                        'options': option_data
                    };
                    jQuery('#wpvivid_calculate_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_clean_junk_file').css({'pointer-events': 'none', 'opacity': '0.4'});
                    wpvivid_post_request_addon(ajax_data, function (data) {
                        jQuery('#wpvivid_calculate_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_clean_junk_file').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('input[option="junk-files"]').prop('checked', false);
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            alert(jsonarray.msg);
                            if (jsonarray.result === "success") {
                                jQuery('#wpvivid_junk_sum_size').html(jsonarray.data.sum_size);
                                jQuery('#wpvivid_log_size').html(jsonarray.data.log_dir_size);
                                jQuery('#wpvivid_backup_cache_size').html(jsonarray.data.backup_cache_size);
                                jQuery('#wpvivid_junk_size').html(jsonarray.data.junk_size);
                                jQuery('#wpvivid_backup_size').html(jsonarray.data.backup_size);
                                jQuery('#wpvivid_loglist').html("");
                                jQuery('#wpvivid_loglist').append(jsonarray.html);
                                wpvivid_log_count = jsonarray.log_count;
                            }
                        }
                        catch(err){
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = wpvivid_output_ajaxerror('cleaning out junk files', textStatus, errorThrown);
                        alert(error_message);
                        jQuery('#wpvivid_calculate_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_clean_junk_file').css({'pointer-events': 'auto', 'opacity': '1'});
                    });
                }
            }

            jQuery('#wpvivid_delete_out_of_backup').click(function(){
                wpvivid_delete_out_of_date_backups();
            });
            /**
             * This function will delete out of date backups.
             */
            function wpvivid_delete_out_of_date_backups(){
                var ajax_data={
                    'action': 'wpvivid_addon_clean_out_of_date_backup'
                };
                jQuery('#wpvivid_delete_out_of_backup').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function(data){
                    jQuery('#wpvivid_delete_out_of_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success") {
                            alert("Out of date backups have been removed.");
                        }
                    }
                    catch(err){
                        alert(err);
                        jQuery('#wpvivid_delete_out_of_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('deleting out of date backups', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#wpvivid_delete_out_of_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                });
            }

            jQuery(document).ready(function ()
            {
                jQuery('#wpvivid_clean_local_storage').val('<?php esc_attr_e($clean_local_storage_recurrence); ?>');
                wpvivid_calculate_diskspaceused();
            });
        </script>
        <?php
    }

    public function output_advance_setting()
    {
        $general_setting=WPvivid_Setting::get_setting(true, "");
        $common_setting=$general_setting['options']['wpvivid_common_setting'];
        if(!isset($general_setting['options']['wpvivid_common_setting']['use_adaptive_settings']))
        {
            $use_adaptive_settings = '';
        }
        else
        {
            if($general_setting['options']['wpvivid_common_setting']['use_adaptive_settings'])
            {
                $use_adaptive_settings = 'checked';
            }
            else{
                $use_adaptive_settings = '';
            }
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['auto_delete_backup_log']))
        {
            $auto_delete_backup_log = '';
        }
        else
        {
            if($general_setting['options']['wpvivid_common_setting']['auto_delete_backup_log'])
            {
                $auto_delete_backup_log = 'checked';
            }
            else
            {
                $auto_delete_backup_log = '';
            }
        }

        $compress_file_count=isset($common_setting['compress_file_count'])?$common_setting['compress_file_count']:500;
        $max_file_size=isset($common_setting['max_file_size'])?$common_setting['max_file_size']:200;
        $max_backup_table=isset($common_setting['max_backup_table'])?$common_setting['max_backup_table']:1000;
        $max_sql_file_size=isset($common_setting['max_sql_file_size'])?$common_setting['max_sql_file_size']:400;
        $exclude_file_size=isset($common_setting['exclude_file_size'])?$common_setting['exclude_file_size']:0;
        $max_execution_time=isset($common_setting['max_execution_time'])?$common_setting['max_execution_time']:900;
        $memory_limit=isset($common_setting['memory_limit'])?$common_setting['memory_limit']:'512M';
        $restore_memory_limit=isset($common_setting['restore_memory_limit'])?$common_setting['restore_memory_limit']:'256M';
        $migrate_size=isset($common_setting['migrate_size'])?$common_setting['migrate_size']:WPVIVID_PRO_MIGRATE_SIZE;

        //
        if(!isset($general_setting['options']['wpvivid_common_setting']['max_resume_count'])){
            $wpvivid_max_resume_count = WPVIVID_PRO_RESUME_RETRY_TIMES;
        }
        else{
            $wpvivid_max_resume_count = intval($general_setting['options']['wpvivid_common_setting']['max_resume_count']);
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['db_connect_method']))
        {
            if($general_setting['options']['wpvivid_common_setting']['db_connect_method'] === 'wpdb')
            {
                $db_method_wpdb = 'checked';
                $db_method_pdo  = '';
            }
            else{
                $db_method_wpdb = '';
                $db_method_pdo  = 'checked';
            }
        }
        else{
            $db_method_wpdb = 'checked';
            $db_method_pdo  = '';
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['zip_method']))
        {
            if($general_setting['options']['wpvivid_common_setting']['zip_method'] === 'ziparchive')
            {
                $zip_method_archive = 'checked';
                $zip_method_pclzip  = '';
            }
            else{
                $zip_method_archive = '';
                $zip_method_pclzip  = 'checked';
            }
        }
        else
        {
            if(class_exists('ZipArchive'))
            {
                if(method_exists('ZipArchive', 'addFile'))
                {
                    $zip_method_archive = 'checked';
                    $zip_method_pclzip  = '';
                }
                else
                {
                    $zip_method_archive = '';
                    $zip_method_pclzip  = 'checked';
                }
            }
            else
            {
                $zip_method_archive = '';
                $zip_method_pclzip  = 'checked';
            }
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['restore_max_execution_time']))
        {
            $restore_max_execution_time = intval($general_setting['options']['wpvivid_common_setting']['restore_max_execution_time']);
        }
        else{
            $restore_max_execution_time = 1800;
        }


        if(isset($general_setting['options']['wpvivid_common_setting']['backup_params']))
        {
            if($general_setting['options']['wpvivid_common_setting']['backup_params'] === 'low')
            {
                $backup_params_low    = 'checked';
                $backup_params_mid    = '';
                $backup_params_high   = '';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
            else if($general_setting['options']['wpvivid_common_setting']['backup_params'] === 'mid')
            {
                $backup_params_low    = '';
                $backup_params_mid    = 'checked';
                $backup_params_high   = '';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
            else if($general_setting['options']['wpvivid_common_setting']['backup_params'] === 'high')
            {
                $backup_params_low    = '';
                $backup_params_mid    = '';
                $backup_params_high   = 'checked';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
            else if($general_setting['options']['wpvivid_common_setting']['backup_params'] === 'custom')
            {
                $backup_params_low    = '';
                $backup_params_mid    = '';
                $backup_params_high   = '';
                $backup_params_custom = 'checked';
                $backup_custom_setting_display = '';
            }
            else
            {
                $backup_params_low    = 'checked';
                $backup_params_mid    = '';
                $backup_params_high   = '';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
        }
        else if(isset($general_setting['options']['wpvivid_common_setting']['compress_file_count']))
        {
            $backup_params_low    = '';
            $backup_params_mid    = '';
            $backup_params_high   = '';
            $backup_params_custom = 'checked';
            $backup_custom_setting_display = '';
        }
        else
        {
            $backup_params_low    = 'checked';
            $backup_params_mid    = '';
            $backup_params_high   = '';
            $backup_params_custom = '';
            $backup_custom_setting_display = 'display: none;';
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['backup_database_use_primary_key']))
        {
            $backup_database_use_primary_key = 'checked';
        }
        else
        {
            if($general_setting['options']['wpvivid_common_setting']['backup_database_use_primary_key'])
            {
                $backup_database_use_primary_key = 'checked';
            }
            else
            {
                $backup_database_use_primary_key = '';
            }
        }

        if(!isset($general_setting['options']['wpvivid_common_setting']['backup_upload_use_cm_store']))
        {
            $backup_upload_use_cm_store = 'checked';
        }
        else
        {
            if($general_setting['options']['wpvivid_common_setting']['backup_upload_use_cm_store'])
            {
                $backup_upload_use_cm_store = 'checked';
            }
            else
            {
                $backup_upload_use_cm_store = '';
            }
        }

        ?>
        <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Learning Mode</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <span>Enable Learning Mode</span>
                            <input type="checkbox" option="setting" name="use_adaptive_settings" <?php esc_attr_e($use_adaptive_settings); ?> />
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </p>
                    <p><code>Designed for servers with limited resources. Enabling it can improve backup success rates, but may result in longer backup time.</code></p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Auto Delete Backup Log</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <span>Automatically delete corresponding logs when deleting backups</span>
                            <input type="checkbox" option="setting" name="auto_delete_backup_log" <?php esc_attr_e($auto_delete_backup_log); ?> />
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Large Database Mode</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <span>This mode optimizes backup process for high-volume data. You can try to enable it if backups fail or time out due to a very large database</span>
                            <input type="checkbox" option="setting" name="backup_database_use_primary_key" <?php esc_attr_e($backup_database_use_primary_key); ?> />
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Large Uploads Mode</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <span>This mode improves backup performance for extensive media libraries. You can try to enable it if backups are slow or fail because of a large number of files in the uploads folder. This mode only supports ZipArchive, it will not work with PclZip</span>
                            <input type="checkbox" option="setting" name="backup_upload_use_cm_store" <?php esc_attr_e($backup_upload_use_cm_store); ?> />
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Database access method</label></td>
                <td>
                    <div>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="db_connect_method" value="wpdb" <?php esc_attr_e($db_method_wpdb); ?> /><strong>WPDB</strong>
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>WPDB option has a better compatibility, but the speed of backup and restore is slower.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;"><strong>PDO</strong>
                                <input type="radio" option="setting" name="db_connect_method" value="pdo" <?php esc_attr_e($db_method_pdo); ?> />
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>It is recommended to choose PDO option if pdo_mysql extension is installed on your server, which lets you backup and restore your site faster.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                        </fieldset>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Backup compression method</label></td>
                <td>
                    <div>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="zip_method" value="ziparchive" <?php esc_attr_e($zip_method_archive); ?> /><strong>ZipArchive</strong>
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>ZipArchive has a better flexibility which provides a higher backup success rate and speed. It is also the default zip method WPvivid pro uses. Using this method requires the ZIP extension to be installed within your PHP.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;"><strong>PclZip</strong>
                                <input type="radio" option="setting" name="zip_method" value="pclzip" <?php esc_attr_e($zip_method_pclzip); ?> />
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>PclZip is a much slower but more stable zip method that is included in every WordPress install. WPvivid will automatically switch to PclZip if the ZIP extension is not installed within your PHP.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                        </fieldset>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Backup performance mode</label></td>
                <td>
                    <div>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="backup_params" value="low" <?php esc_attr_e($backup_params_low); ?> /><strong>Low (Balanced)</strong>
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Low (Balanced): Use this default setting for minimal server resource usage, but expect longer backup times. Best for shared hosting or limited resources. Backups are split into 200MB chunks.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="backup_params" value="mid" <?php esc_attr_e($backup_params_mid); ?> /><strong>Mid (Standard)</strong>
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Mid (Standard): This mode offers a good balance between backup speed and resource usage. It's suitable for most web hosting environments.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="backup_params" value="high" <?php esc_attr_e($backup_params_high); ?> /><strong>High (Accelerated)</strong>
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>High (Accelerated): This mode uses more server resources to reduce backup time, but is only recommended for dedicated servers. If backups time out or get stuck, consider Mid or Low mode. Backups are split into 4GB chunks.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="setting" name="backup_params" value="custom" <?php esc_attr_e($backup_params_custom); ?> /><strong>Custom (Advanced)</strong>
                                <span class="wpvivid-radio-checkmark"></span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Custom (Advanced): This mode allows fine-tuning of backup parameters. Incorrect configuration can lead to backup failures. It is recommended to use only with specific guidance from our support team.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                        </fieldset>
                    </div>
                    <p></p>
                    <div id="wpvivid_custom_backup_params" style="<?php esc_attr_e($backup_custom_setting_display); ?>">
                        <div>
                            <span><input type="text" placeholder="<?php esc_attr_e($compress_file_count); ?>" option="setting" name="compress_file_count" id="compress_file_count" class="all-options" value="<?php esc_attr_e($compress_file_count); ?>" onkeyup="value=value.replace(/\D/g,'')"></span><span>The number of files compressed to the backup zip each time</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>When taking a backup, the plugin will compress this number of files to the backup zip each time. The default value is 500. The lower the value, the longer time the backup will take, but the higher the backup success rate. If you encounter a backup timeout issue, try to decrease this value.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="200" option="setting" name="max_file_size" id="wpvivid_max_zip" class="all-options" value="<?php esc_attr_e(str_replace('M', '', $max_file_size), 'wpvivid'); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, split a backup every this size</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Some web hosting providers limit large zip files (e.g. 200MB), and therefore splitting your backup into many parts is an ideal way to avoid hitting the limitation if you are running a big website. Please try to adjust the value if you are encountering backup errors. When you set a value of 0MB, backups will be split every 4GB.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="1000" option="setting" name="max_backup_table" id="wpvivid_max_backup_table" class="all-options" value="<?php esc_attr_e($max_backup_table); ?>" onkeyup="value=value.replace(/\D/g,'')">The number of database tables compressed to each zip</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>When taking a database backup, the plugin will compress this number of tables to each backup zip. The default value is 1000 which works for most websites.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="400" option="setting" name="max_sql_file_size" class="all-options" value="<?php esc_attr_e(str_replace('M', '', $max_sql_file_size), 'wpvivid'); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, split a sql file every this size</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Some web hosting providers limit large files, therefore, splitting your sql files into many parts is an ideal way to avoid hitting the limitation. Please try to adjust the value if you get a backup timeout error. The default value is 400, and it is not recommended to set the value lower than 100.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="0" option="setting" name="exclude_file_size" id="wpvivid_ignore_large" class="all-options" value="<?php esc_attr_e($exclude_file_size, 'wpvivid'); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, exclude files larger than this size</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Using the option will ignore the file larger than the certain size in MB when backing up, '0' (zero) means unlimited.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="900" option="setting" name="max_execution_time" id="wpvivid_option_timeout" class="all-options" value="<?php esc_attr_e($max_execution_time, 'wpvivid'); ?>" onkeyup="value=value.replace(/\D/g,'')">Seconds</span><span>, maximum PHP script execution time for a backup task</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The time-out is not your server PHP time-out. With the execution time exhausted, our plugin will shut the process of backup down. If the progress of backup encounters a time-out, that means you have a medium or large sized website, please try to scale the value bigger.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <!--<div>
                            <span><input type="text" placeholder="1800" option="setting" name="restore_max_execution_time" class="all-options" value="<?php esc_attr_e($restore_max_execution_time); ?>" onkeyup="value=value.replace(/\D/g,'')">Seconds</span><span>, maximum PHP script execution time for a restore task</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <p>The time-out is not your server PHP time-out. With the execution time exhausted, our plugin will shut the process of restore down. If the progress of restore encounters a time-out, that means you have a medium or large sized website, please try to scale the value bigger.</p>
                                <i></i>
                            </div>
                        </span>
                        </div>
                        <p></p>-->
                        <div>
                            <span><input type="text" placeholder="256" option="setting" name="memory_limit" class="all-options" value="<?php esc_attr_e(str_replace('M', '', $memory_limit), 'wpvivid'); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, maximum PHP memory for a backup task</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Adjust this value to apply for a temporary PHP memory limit for WPvivid backup plugin to run a backup. We set this value to 256M by default. Increase the value if you encounter a memory exhausted error. Note: some web hosting providers may not support this.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <!--<div>
                            <span><input type="text" placeholder="256" option="setting" name="restore_memory_limit" class="all-options" value="<?php esc_attr_e(str_replace('M', '', $restore_memory_limit), 'wpvivid'); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, maximum PHP memory for a restore task</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <p>Adjust this value to apply for a temporary PHP memory limit for WPvivid backup plugin in restore process. We set this value to 256M by default. Increase the value if you encounter a memory exhausted error. Note: some web hosting providers may not support this</p>
                                <i></i>
                            </div>
                        </span>
                        </div>
                        <p></p>-->
                        <div>
                            <span><input type="text" placeholder="2048" option="setting" name="migrate_size" class="all-options" value="<?php esc_attr_e($migrate_size); ?>" onkeyup="value=value.replace(/\D/g,'')">KB</span><span>, chunk size</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>e.g.  if you choose a chunk size of 2MB, a 8MB file will use 4 chunks. Decreasing this value will break the ISP's transmission limit, for example:512KB</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <div style="padding:1em 1em 0 0;"><input class="button-primary wpvivid_setting_general_save" type="submit" value="Save Changes"></div>
        <script>
            var old_local_backup_folder = jQuery('input[option=setting][name=local_backup_folder]:checked').val();
            console.log(old_local_backup_folder);
            var old_outside_path = jQuery('input[option=setting][name=outside_path]').val();
            console.log(old_outside_path);
            var has_check_outside_folder = false;

            jQuery('input:radio[option=setting][name=backup_params]').click(function()
            {
                if(jQuery(this).prop('checked'))
                {
                    var value = jQuery(this).prop('value');
                    if(value=='custom')
                    {
                        jQuery('#wpvivid_custom_backup_params').show();
                    }
                    else
                    {
                        jQuery('#wpvivid_custom_backup_params').hide();
                    }
                }
            });

            jQuery('.wpvivid_setting_general_save').click(function(){
                var new_local_backup_folder = jQuery('input[option=setting][name=local_backup_folder]:checked').val();
                if(old_local_backup_folder === 'content_folder' && new_local_backup_folder === 'outside_folder')
                {
                    if(!has_check_outside_folder)
                    {
                        alert('Please verify the directory accessibility by clicking the \'Test Path\' button. Untested paths may lead to backup failures.');
                        return;
                    }
                }

                if(new_local_backup_folder === 'outside_folder')
                {
                    var new_outside_path = jQuery('input[option=setting][name=outside_path]').val();
                    if(old_outside_path !== new_outside_path)
                    {
                        if(!has_check_outside_folder)
                        {
                            alert('Please ensure the directory accessibility by clicking the \'Test Path\' button. Untested paths may lead to backup failures.');
                            return;
                        }
                    }
                }

                wpvivid_set_general_settings();
                wpvivid_settings_changed = false;
            });

            function wpvivid_set_general_settings()
            {
                var compress_file_count = jQuery('#compress_file_count').val();
                if(parseInt(compress_file_count) > 10000 || parseInt(compress_file_count) < 10)
                {
                    alert('\'The number of files compressed to the backup zip each time\' should be 10 - 10000.');
                    return;
                }

                var max_backup_table = jQuery('#wpvivid_max_backup_table').val();
                if(parseInt(max_backup_table) < 100)
                {
                    alert('\'The number of database tables compressed to each zip\' should be larger than 100.');
                    return;
                }

                var new_local_backup_count = jQuery('#wpvivid_max_backup_count').val();
                var new_remote_backup_count = jQuery('#wpvivid_remote_max_backup_count').val();
                var new_local_db_backup_count = jQuery('#wpvivid_max_backup_db_count').val();
                var new_remote_db_backup_count = jQuery('#wpvivid_remote_max_backup_db_count').val();
                if(parseInt(new_local_backup_count) < parseInt(local_backup_count)){
                    backup_retain_changed = '1';
                }
                if(parseInt(new_remote_backup_count) < parseInt(remote_backup_count)){
                    backup_retain_changed = '1';
                }
                if(parseInt(new_local_db_backup_count) < parseInt(local_db_backup_count)){
                    backup_retain_changed = '1';
                }
                if(parseInt(new_remote_db_backup_count) < parseInt(remote_db_backup_count)){
                    backup_retain_changed = '1';
                }

                var json = {};
                json['send_to']={};
                var email_array = {};
                var email_check = '';
                jQuery('#wpvivid_email_list tr').each(function(){
                    /*if(jQuery(this).find('th input').prop('checked')){
                        email_check = '1';
                    }
                    else{
                        email_check = '0';
                    }*/
                    email_check = '1';
                    var email_send_to = jQuery(this).find('td:eq(0) label').html();
                    email_array['email_address'] = email_send_to;
                    email_array['email_enable'] = email_check;
                    json['send_to'][email_send_to] = email_array;
                    email_array = {};
                });

                var setting_data = wpvivid_ajax_data_transfer_addon('setting');
                var json1 = JSON.parse(setting_data);

                jQuery.extend(json1, json);
                setting_data=JSON.stringify(json1);

                /*if(typeof wpvivid_auto_backup_table.is_get !== 'undefined' && wpvivid_auto_backup_table.is_get === true)
                {
                    var auto_backup_json = {};
                    auto_backup_json['exclude-tables'] = Array();
                    auto_backup_json['include-tables'] = Array();
                    jQuery('#wpvivid_custom_auto_backup').find('input[option=base_db][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            auto_backup_json['exclude-tables'].push(jQuery(value).val());
                        }
                    });
                    jQuery('#wpvivid_custom_auto_backup').find('input[option=other_db][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            auto_backup_json['exclude-tables'].push(jQuery(value).val());
                        }
                    });
                    jQuery('#wpvivid_custom_auto_backup').find('input[option=diff_prefix_db][type=checkbox]').each(function(index, value){
                        if(jQuery(value).prop('checked')){
                            auto_backup_json['include-tables'].push(jQuery(value).val());
                        }
                    });
                    var json2 = JSON.parse(setting_data);
                    jQuery.extend(json2, auto_backup_json);
                    setting_data=JSON.stringify(json2);
                }*/

                var ajax_data = {
                    'action': 'wpvivid_set_general_setting_addon',
                    'setting': setting_data,
                    'backup_retain_changed': backup_retain_changed
                };
                jQuery('.wpvivid_setting_general_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('.wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success')
                        {

                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>';
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('.wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('.wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('input:checkbox[option=setting][name=use_mail_title]').on("click", function(){
                if(jQuery(this).prop('checked')){
                    jQuery('#wpvivid_mail_title').attr('readonly', false);
                }
                else{
                    jQuery('#wpvivid_mail_title').attr('readonly', true);
                }
            });

            jQuery('#wpvivid_mail_title').on("keyup", function(){
                var mail_title = jQuery(this).val();
                if(mail_title === ''){
                    mail_title = '*';
                }
                jQuery('.wpvivid-mail-title').html(mail_title);
            });

            jQuery('#wpvivid_backup_prefix').on("keyup", function(){
                var backup_prefix = jQuery('#wpvivid_backup_prefix').val();
                var reg = RegExp(/wpvivid/, 'i');
                if (backup_prefix.match(reg)) {
                    jQuery('#wpvivid_backup_prefix').val('');
                    alert('You can not use word \'wpvivid\' to comment the backup.');
                }
            });

            jQuery('.wpvivid-backup-count-retention').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery(this).val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery(this).val('');
                }
            });

            jQuery('#wpvivid_max_backup_count').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery('#wpvivid_max_backup_count').val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery('#wpvivid_max_backup_count').val('');
                }
            });

            jQuery('#wpvivid_remote_max_backup_count').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery('#wpvivid_remote_max_backup_count').val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery('#wpvivid_remote_max_backup_count').val('');
                }
            });

            jQuery('#wpvivid_max_backup_db_count').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery('#wpvivid_max_backup_db_count').val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery('#wpvivid_max_backup_db_count').val('');
                }
            });

            jQuery('#wpvivid_remote_max_backup_db_count').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery('#wpvivid_remote_max_backup_db_count').val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery('#wpvivid_remote_max_backup_db_count').val('');
                }
            });

            jQuery('input:checkbox[option=setting][name=domain_include]').click(function(){
                if(jQuery(this).prop('checked')){
                    jQuery('#wpvivid_backup_prefix').attr('readonly', false);
                }
                else{
                    jQuery('#wpvivid_backup_prefix').attr('readonly', true);
                }
            });

            jQuery('#wpvivid_send_email_test').click(function()
            {
                wpvivid_add_email();
            });

            jQuery('#wpvivid_encrypt_db').click(function()
            {
                if(jQuery(this).prop('checked'))
                {
                    jQuery('#wpvivid_encrypt_db_pw').attr('readonly', false);
                }
                else{
                    jQuery('#wpvivid_encrypt_db_pw').attr('readonly', true);
                }
            });

            function wpvivid_email_all_check(){
                var all_check = true;
                jQuery('#wpvivid_email_list tr').each(function(){
                    if(jQuery(this).find('th input').prop('checked')){
                    }
                    else{
                        all_check = false;
                    }
                });
                if(all_check){
                    jQuery('#wpvivid_email_select_all').prop('checked', true);
                }
                else{
                    jQuery('#wpvivid_email_select_all').prop('checked', false);
                }
            }
            wpvivid_email_all_check();

            function wpvivid_add_email()
            {
                var mail = jQuery('#wpvivid_mail').val();
                if(mail !== '') {
                    var repeat = false;
                    jQuery('#wpvivid_email_list tr').each(function(){
                        var email_address = jQuery(this).find('td:eq(0)').find('label').html();
                        if(mail === email_address){
                            repeat = true;
                        }
                    });
                    if(!repeat) {
                        var ajax_data = {
                            'action': 'wpvivid_add_send_mail',
                            'send_to': mail
                        };
                        jQuery('#wpvivid_send_email_res').hide();
                        wpvivid_post_request_addon(ajax_data, function (data) {
                            try {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success') {
                                    jQuery('#wpvivid_email_list').append(jsonarray.html);
                                    jQuery('#wpvivid_send_email_res').show();
                                    jQuery('#wpvivid_send_email_res').html('Test succeeded.');
                                }
                                else {
                                    jQuery('#wpvivid_send_email_res').show();
                                    jQuery('#wpvivid_send_email_res').html('Test failed, ' + jsonarray.error);
                                }
                                wpvivid_email_all_check();
                            }
                            catch (err) {
                                alert(err);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = wpvivid_output_ajaxerror('sending test mail', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                    else{
                        alert('Email alreay in list.');
                    }
                }
                else{
                    alert('Mail is required.');
                }
            }

            function wpvivid_remove_mail(obj){
                jQuery(obj).parents("tr:first").remove();
            }

            jQuery('#wpvivid_setting_export').click(function(){
                wpvivid_export_settings();
            });

            jQuery('#wpvivid_setting_import').click(function(){
                wpvivid_import_settings();
            });

            function wpvivid_export_settings() {
                wpvivid_location_href=true;
                location.href =ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_export_setting&setting=1&history=1&review=0';
            }

            function wpvivid_import_settings(){
                var files = jQuery('input[name="fileTrans"]').prop('files');

                if(files.length == 0){
                    alert('Choose a settings file and import it by clicking Import button.');
                    return;
                }
                else{
                    var reader = new FileReader();
                    reader.readAsText(files[0], "UTF-8");
                    reader.onload = function(evt){
                        var fileString = evt.target.result;
                        var ajax_data = {
                            'action': 'wpvivid_import_setting',
                            'data': fileString
                        };
                        wpvivid_post_request_addon(ajax_data, function(data){
                            try {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success') {
                                    alert('The plugin settings were imported successfully.');
                                    if(typeof jsonarray.slug === 'undefined'){
                                        location.reload();
                                    }
                                    else{
                                        location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>';
                                    }
                                }
                                else {
                                    alert('Error: ' + jsonarray.error);
                                }
                            }
                            catch(err){
                                alert(err);
                            }
                        }, function(XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = wpvivid_output_ajaxerror('importing the previously-exported settings', textStatus, errorThrown);
                            jQuery('#wpvivid_display_log_content').html(error_message);
                        });
                    }
                }
            }
        </script>
        <?php
    }

    public function output_image_optimization()
    {
        ?>
        <p>Coming Soon...</p>
        <?php
    }

    public function output_staging_setting()
    {
        $options=get_option('wpvivid_staging_options',array());

        $staging_db_insert_count   = isset($options['staging_db_insert_count']) ? $options['staging_db_insert_count'] : WPVIVID_STAGING_DB_INSERT_COUNT;
        $staging_db_replace_count  = isset($options['staging_db_replace_count']) ? $options['staging_db_replace_count'] : WPVIVID_STAGING_DB_REPLACE_COUNT;
        $staging_file_copy_count   = isset($options['staging_file_copy_count']) ? $options['staging_file_copy_count'] : WPVIVID_STAGING_FILE_COPY_COUNT;
        $staging_exclude_file_size = isset($options['staging_exclude_file_size']) ? $options['staging_exclude_file_size'] : WPVIVID_STAGING_MAX_FILE_SIZE;
        $staging_memory_limit      = isset($options['staging_memory_limit']) ? $options['staging_memory_limit'] : WPVIVID_STAGING_MEMORY_LIMIT;
        $staging_memory_limit      = str_replace('M', '', $staging_memory_limit);
        $staging_max_execution_time= isset($options['staging_max_execution_time']) ? $options['staging_max_execution_time'] : WPVIVID_STAGING_MAX_EXECUTION_TIME;
        $staging_resume_count      = isset($options['staging_resume_count']) ? $options['staging_resume_count'] : WPVIVID_STAGING_RESUME_COUNT;
        $staging_request_timeout      = isset($options['staging_request_timeout']) ? $options['staging_request_timeout'] : WPVIVID_STAGING_REQUEST_TIMEOUT_EX;

        $staging_not_need_login=isset($options['not_need_login']) ? $options['not_need_login'] : true;
        if($staging_not_need_login)
        {
            $staging_not_need_login_check='checked';
        }
        else
        {
            $staging_not_need_login_check='';
        }
        $staging_overwrite_permalink = isset($options['staging_overwrite_permalink']) ? $options['staging_overwrite_permalink'] : false;
        if($staging_overwrite_permalink){
            $staging_overwrite_permalink_check = 'checked';
        }
        else{
            $staging_overwrite_permalink_check = '';
        }
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="padding-top: 0;">
            <div class="wpvivid-element-space-bottom"><strong><?php _e('DB Copy Count', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" class="all-options" option="setting" name="staging_db_insert_count" value="<?php esc_attr_e($staging_db_insert_count); ?>"
                       placeholder="10000" onkeyup="value=value.replace(/\D/g,'')" />
            </div>
            <div class="wpvivid-element-space-bottom">
                <?php _e( 'Number of DB rows, that are copied within one ajax query. The higher value makes the database copy process faster. 
                Please try a high value to find out the highest possible value. If you encounter timeout errors, try lower values until no 
                more errors occur.', 'wpvivid' ); ?>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('DB Replace Count', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" class="all-options" option="setting" name="staging_db_replace_count" value="<?php esc_attr_e($staging_db_replace_count); ?>"
                       placeholder="5000" onkeyup="value=value.replace(/\D/g,'')" />
            </div>
            <div class="wpvivid-element-space-bottom">
                <?php _e( 'Number of DB rows, that are processed within one ajax query. The higher value makes the DB replacement process faster. 
                If timeout erros occur, decrease the value because this process consumes a lot of memory.', 'wpvivid' ); ?>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('File Copy Count', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" class="all-options" option="setting" name="staging_file_copy_count" value="<?php esc_attr_e($staging_file_copy_count); ?>"
                       placeholder="500" onkeyup="value=value.replace(/\D/g,'')" />
            </div>
            <div class="wpvivid-element-space-bottom">
                <?php _e( 'Number of files to copy that will be copied within one ajax request. The higher value makes the file file copy process faster. 
                Please try a high value to find out the highest possible value. If you encounter timeout errors, try lower values until no more errors occur.', 'wpvivid' ); ?>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Max File Size', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" class="all-options" option="setting" name="staging_exclude_file_size" value="<?php esc_attr_e($staging_exclude_file_size); ?>"
                       placeholder="30" onkeyup="value=value.replace(/\D/g,'')" />MB
            </div>
            <div class="wpvivid-element-space-bottom">
                <?php _e( 'Maximum size of the files copied to a staging site. All files larger than this value will be ignored. If you set the value of 0 MB, all files will be copied to a staging site.', 'wpvivid' ); ?>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Staging Memory Limit', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" class="all-options" option="setting" name="staging_memory_limit" value="<?php esc_attr_e($staging_memory_limit); ?>"
                       placeholder="256" onkeyup="value=value.replace(/\D/g,'')" />MB
            </div>
            <div class="wpvivid-element-space-bottom">
                <?php echo sprintf(__('Adjust this value to apply for a temporary PHP memory limit for %s while creating a staging site. 
                We set this value to 256M by default. Increase the value if you encounter a memory exhausted error. Note: some web hosting 
                providers may not support this.', 'wpvivid'), apply_filters( 'wpvivid_white_label_display', 'WPvivid backup plugin' )); ?>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('PHP Script Execution Timeout', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" class="all-options" option="setting" name="staging_max_execution_time" value="<?php esc_attr_e($staging_max_execution_time); ?>"
                       placeholder="900" onkeyup="value=value.replace(/\D/g,'')" />
            </div>
            <div class="wpvivid-element-space-bottom">
                <?php _e( 'The time-out is not your server PHP time-out. With the execution time exhausted, our plugin will shut down the progress of 
                creating a staging site. If the progress  encounters a time-out, that means you have a medium or large sized website. Please try to 
                scale the value bigger.', 'wpvivid' ); ?>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Delay Between Requests', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" class="all-options" option="setting" name="staging_request_timeout" value="<?php esc_attr_e($staging_request_timeout); ?>"
                       placeholder="1000" onkeyup="value=value.replace(/\D/g,'')" />ms
            </div>
            <div class="wpvivid-element-space-bottom">
                <?php _e( 'A lower value will help speed up the process of creating a staging site. However, if your server has a limit on the number of requests, a higher value is recommended.', 'wpvivid' ); ?>
            </div>

            <div class="wpvivid-element-space-bottom">
                <strong>Retrying </strong>
                <select option="setting" name="staging_resume_count">
                    <?php
                    for($resume_count=3; $resume_count<10; $resume_count++){
                        if($resume_count === $staging_resume_count){
                            _e('<option selected="selected" value="'.$resume_count.'">'.$resume_count.'</option>');
                        }
                        else{
                            _e('<option value="'.$resume_count.'">'.$resume_count.'</option>');
                        }
                    }
                    ?>
                </select><strong><?php _e(' times when encountering a time-out error', 'wpvivid'); ?></strong>
            </div>

            <div class="wpvivid-element-space-bottom">
                <label>
                    <input type="checkbox" option="setting" name="not_need_login" <?php esc_attr_e($staging_not_need_login_check); ?> />
                    <span><strong><?php _e('Anyone can visit the staging site', 'wpvivid'); ?></strong></span>
                </label>
            </div>

            <div class="wpvivid-element-space-bottom">
                <span>When the option is checked, anyone will be able to visit the staging site without the need to login. Uncheck it to request a login to visit the staging site.</span>
            </div>

            <div class="wpvivid-element-space-bottom">
                <label>
                    <input type="checkbox" option="setting" name="staging_overwrite_permalink" <?php esc_attr_e($staging_overwrite_permalink_check); ?> />
                    <span><strong><?php _e('Keep permalink when transferring website', 'wpvivid'); ?></strong></span>
                </label>
            </div>

            <div class="wpvivid-element-space-bottom">
                <span>When checked, this option allows you to keep the current permalink structure when you create a staging site or push a staging site to live.</span>
            </div>

            <div><input class="button-primary wpvividstg_save_setting" type="submit" value="<?php esc_attr_e( 'Save Changes', 'wpvivid' ); ?>" /></div>
        </div>

        <script>
            jQuery('.wpvividstg_save_setting').click(function()
            {
                wpvividstg_save_setting();
            });

            function wpvividstg_save_setting()
            {
                var setting_data = wpvivid_ajax_data_transfer('setting');
                var ajax_data = {
                    'action': 'wpvividstg_save_setting',
                    'setting': setting_data,
                };
                jQuery('.wpvividstg_save_setting').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('.wpvividstg_save_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success')
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>';
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                        jQuery('.wpvividstg_save_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('.wpvividstg_save_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }
}