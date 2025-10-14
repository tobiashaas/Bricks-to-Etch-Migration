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
class WPvivid_Custom_Backup_Manager
{
    public $parent_id;
    public $advanced_id;
    public $option;
    public $is_get_size;
    public $is_mu_single;
    private $custom_interface;
    private $get_website_size_retry=0;

    public function __construct( $parent_id, $option, $is_get_size, $is_mu_single )
    {
        $this->custom_interface = new WPvivid_Custom_Interface_addon();
        $this->parent_id=$parent_id;
        $this->option=$option;
        $this->is_get_size=$is_get_size;
        $this->is_mu_single=$is_mu_single;
    }

    public function wpvivid_set_advanced_id($advanced_id)
    {
        $this->advanced_id = $advanced_id;
    }

    public static function wpvivid_get_custom_settings()
    {
        return get_option('wpvivid_custom_backup_history',array());
    }

    public static function wpvivid_get_new_backup_history()
    {
        return get_option('wpvivid_manual_backup_history',array());
    }

    public static function wpvivid_set_new_backup_history($backup_history)
    {
        update_option('wpvivid_manual_backup_history', $backup_history, 'no');
    }

    public static function get_incremental_file_settings()
    {
        $history = get_option('wpvivid_incremental_backup_history', array());
        if(isset($history['incremental_file']))
        {
            $options = $history['incremental_file'];
        }
        else{
            $options = array();
        }
        return $options;
    }

    public static function get_incremental_db_setting()
    {
        $history = get_option('wpvivid_incremental_backup_history', array());
        if(isset($history['incremental_db']))
        {
            $options = $history['incremental_db'];
        }
        else
        {
            $options = array();
        }
        return $options;
    }

    public static function get_incremental_option($type)
    {
        $options = array();
        $incremental_schedules = get_option('wpvivid_incremental_schedules', array());
        if($type === 'files')
        {
            if(!empty($incremental_schedules))
            {
                $schedule = array_shift($incremental_schedules);
                if(isset($schedule['backup_files']['custom_dirs']))
                {
                    $options['custom_dirs'] = $schedule['backup_files']['custom_dirs'];
                }
            }
        }
        else
        {
            if(!empty($incremental_schedules))
            {
                $schedule = array_shift($incremental_schedules);
                if(isset($schedule['backup_db']['custom_dirs']))
                {
                    $options['custom_dirs'] = $schedule['backup_db']['custom_dirs'];
                }
            }
        }
        return $options;
    }

    public function is_calc_website_size()
    {
        $wpvivid_common_setting = get_option('wpvivid_common_setting', array());
        if(isset($wpvivid_common_setting) && !empty($wpvivid_common_setting))
        {
            if(isset($wpvivid_common_setting['estimate_backup'])&&$wpvivid_common_setting['estimate_backup'])
            {
                $is_calc = true;
            }
            else
            {
                $is_calc = false;
            }
        }
        else
        {
            $is_calc = true;
        }
        return $is_calc;
    }

    public function output_custom_backup_db_table()
    {
        if($this->is_mu_single === '1')
        {
            $type = 'manual_backup';
            $database_check = 'checked="checked"';
            $additional_database_check = '';
        }
        else
        {
            $database_check = 'checked="checked"';
            $additional_database_check = '';

            if($this->option === 'manual_backup' || $this->option === 'migration_backup' ||
                $this->option === 'schedule_backup' || $this->option === 'update_schedule_backup' ||
                $this->option === 'export_site')
            {
                $type = 'manual_backup';
                $custom_backup_history = self::wpvivid_get_new_backup_history();
                if(isset($custom_backup_history) && !empty($custom_backup_history))
                {
                    if(isset($custom_backup_history['custom_dirs']['database_check'])) {
                        if ($custom_backup_history['custom_dirs']['database_check'] != '1') {
                            $database_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['additional_database_check'])) {
                        if ($custom_backup_history['custom_dirs']['additional_database_check'] == '1') {
                            $additional_database_check = 'checked="checked"';
                        }
                    }
                }
            }
            else
            {
                $type = 'incremental_backup';
                $custom_incremental_db_history = self::get_incremental_option('db');
                if(isset($custom_incremental_db_history) && !empty($custom_incremental_db_history)){
                    if(isset($custom_incremental_db_history['custom_dirs']['database_check']))
                    {
                        if ($custom_incremental_db_history['custom_dirs']['database_check'] != '1')
                        {
                            $database_check = '';
                        }
                    }

                    if(!empty($custom_incremental_db_history['additional_database_option']))
                    {
                        if(isset($custom_incremental_db_history['additional_database_option']['additional_database_check']))
                        {
                            if ($custom_incremental_db_history['additional_database_option']['additional_database_check'] == '1')
                            {
                                $additional_database_check = 'checked';
                            }
                        }
                    }
                }
            }
        }

        if($database_check === '')
        {
            $database_style = 'display: none;';
        }
        else
        {
            $database_style = '';
        }

        if($type === 'manual_backup')
        {
            $key = 'general';
        }
        else
        {
            $key = 'incremental';
        }
        $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
        $database_size=isset($website_size[$key]['database_size'])?$website_size[$key]['database_size']:0;
        $database_size = size_format($database_size, 2);
        if(isset($website_size[$key]) && !empty($website_size[$key]))
        {
        }
        else
        {
            $database_style = 'display: none;';
        }

        ?>

        <div style="">
            <span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue"></span>
            <span><strong>Databases</strong></span>
            <span style="<?php esc_attr_e($database_style); ?>"> (</span><span class="wpvivid-database-size wpvivid-size" style="<?php esc_attr_e($database_style); ?>"><?php _e($database_size); ?></span><span style="<?php esc_attr_e($database_style); ?>">) </span>
            <span class="wpvivid-recalc-database-backup-size" style="padding:0.5em;">
                <span class="wpvivid-grey" style="padding:0.2em 0.6em;cursor:pointer; border-radius:3px;">calc</span>
            </span>
        </div>

        <!-- Database Tables -->
        <div style="padding-left:2em;">
            <p><span><input type="checkbox" class="wpvivid-custom-database-check" <?php esc_attr_e($database_check); ?>><span class="wpvivid-handle-base-database-detail" style="cursor:pointer;"><strong>Database</strong></span></span>
                <span class="dashicons wpvivid-dashicons-grey wpvivid-handle-base-database-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </p>
        </div>

        <div class="wpvivid-custom-database-info wpvivid-base-database-detail" style="display: none;">
            <div class="spinner is-active wpvivid-database-loading" style="margin: 0 5px 10px 0; float: left;"></div>
            <div style="float: left;">Archieving database tables</div>
            <div style="clear: both;"></div>
        </div>
        <div style="clear:both;"></div>

        <!-- Additional Database -->
        <div style="padding-left:2em;">
            <p>
                <span><input type="checkbox" class="wpvivid-custom-additional-database-check" <?php esc_attr_e($additional_database_check); ?>><span class="wpvivid-handle-additional-database-detail" style="cursor:pointer;"><strong><span style="color:green;"><i>(optional)</i></span>Include Additional Databases</strong></span></span>
                <span class="dashicons wpvivid-dashicons-grey wpvivid-handle-additional-database-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </p>
        </div>
        <div class="wpvivid-additional-database-detail" style="display: none;">
            <div style="padding-left:2em;padding-right:1em;">
                <div style="padding: 0px 1em 1em; border: 1px solid rgb(204, 204, 204);">
                    <div style="border-bottom:1px solid #ccc;">
                        <p>
                            <span>Host: </span><span><input type="text" class="wpvivid-additional-database-host" style="width: 120px;"></span>
                            <span>User Name: </span><span><input type="text" class="wpvivid-additional-database-user" style="width: 120px;"></span>
                            <span>Password: </span><span><input type="password" class="wpvivid-additional-database-pass" style="width: 120px;"></span>
                            <span><input type="submit" value="Connect" class="button button-primary wpvivid-connect-additional-database" ></span>
                        </p>
                    </div>
                    <div style="width:50%;float:left;box-sizing:border-box;padding-right:0.5em;">
                        <div>
                            <p><span class="dashicons dashicons-excerpt-view wpvivid-dashicons-blue"></span>
                                <span><strong>Databases</strong></span>
                                <span>( click "<span class="dashicons dashicons-plus-alt wpvivid-icon-16px"></span>" icon to add the database to backup list )</span>
                            </p>
                        </div>
                        <div class="wpvivid-additional-database-add" style="height:100px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;"></div>
                        <div style="clear:both;"></div>
                    </div>
                    <div style="width:50%; float:left;box-sizing:border-box;padding-left:0.5em;">
                        <div>
                            <p>
                                <span class="dashicons dashicons-list-view wpvivid-dashicons-orange"></span>
                                <span><strong>Databases will be backed up</strong></span>
                                <span>( click <span class="dashicons dashicons-trash wpvivid-icon-16px"></span> icon to exclude the database )</span>
                            </p>
                        </div>
                        <div class="wpvivid-additional-database-list" style="height:100px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                            <?php
                            $html = '';
                            $html = apply_filters('wpvivid_additional_database_list', $html);
                            echo $html;
                            ?>
                        </div>
                    </div>
                    <div style="clear:both;"></div>
                </div>
            </div>
        </div>
        <div style="clear:both;"></div>

        <?php
    }

    public function output_custom_backup_file_table()
    {
        if($this->is_mu_single === '1')
        {
            $type = 'manual_backup';
            $core_check = 'checked="checked"';
            $themes_check = 'checked="checked"';
            $plugins_check = 'checked="checked"';
            $uploads_check = 'checked="checked"';
            $content_check = 'checked="checked"';
            $additional_folder_check = '';
        }
        else
        {
            $core_check = 'checked="checked"';
            $themes_check = 'checked="checked"';
            $plugins_check = 'checked="checked"';
            $uploads_check = 'checked="checked"';
            $content_check = 'checked="checked"';
            $additional_folder_check = '';

            if($this->option === 'manual_backup' || $this->option === 'migration_backup' ||
                $this->option === 'schedule_backup' || $this->option === 'update_schedule_backup' ||
                $this->option === 'export_site')
            {
                $type = 'manual_backup';
                $custom_backup_history = self::wpvivid_get_new_backup_history();
                if(isset($custom_backup_history) && !empty($custom_backup_history))
                {
                    if(isset($custom_backup_history['custom_dirs']['core_check'])) {
                        if ($custom_backup_history['custom_dirs']['core_check'] != '1') {
                            $core_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['themes_check'])) {
                        if($custom_backup_history['custom_dirs']['themes_check'] != '1'){
                            $themes_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['plugins_check'])){
                        if ($custom_backup_history['custom_dirs']['plugins_check'] != '1') {
                            $plugins_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['uploads_check'])) {
                        if ($custom_backup_history['custom_dirs']['uploads_check'] != '1') {
                            $uploads_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['content_check'])) {
                        if ($custom_backup_history['custom_dirs']['content_check'] != '1') {
                            $content_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['other_check'])) {
                        if ($custom_backup_history['custom_dirs']['other_check'] == '1') {
                            $additional_folder_check = 'checked="checked"';
                        }
                    }
                }
            }
            else
            {
                $type = 'incremental_backup';
                $custom_incremental_file_history = self::get_incremental_option('files');
                if(isset($custom_incremental_file_history) && !empty($custom_incremental_file_history)) {
                    if(isset($custom_incremental_file_history['custom_dirs']['core_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['core_check'] != '1')
                        {
                            $core_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['themes_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['themes_check'] != '1')
                        {
                            $themes_check = '';
                        }
                    }
                    if(isset($custom_incremental_file_history['custom_dirs']['plugins_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['plugins_check'] != '1')
                        {
                            $plugins_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['uploads_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['uploads_check'] != '1')
                        {
                            $uploads_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['content_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['content_check'] != '1')
                        {
                            $content_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['other_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['other_check'] == '1')
                        {
                            $additional_folder_check = 'checked';
                        }
                    }
                }
            }
        }

        if($core_check === '')
        {
            $core_style = 'display: none;';
        }
        else
        {
            $core_style = '';
        }

        if($content_check === '')
        {
            $content_style = 'display: none;';
        }
        else
        {
            $content_style = '';
        }

        if($themes_check === '')
        {
            $themes_style = 'display: none;';
        }
        else
        {
            $themes_style = '';
        }

        if($plugins_check === '')
        {
            $plugins_style = 'display: none;';
        }
        else
        {
            $plugins_style = '';
        }

        if($uploads_check === '')
        {
            $uploads_style = 'display: none;';
        }
        else
        {
            $uploads_style = '';
        }

        if($core_check === '' && $content_check === '' && $themes_check === '' && $plugins_check === '' && $uploads_check === '')
        {
            $file_style = 'display: none;';
        }
        else
        {
            $file_style = '';
        }

        if($type = 'manual_backup')
        {
            $key = 'general';
        }
        else
        {
            $key = 'incremental';
        }
        $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
        $core_size=isset($website_size[$key]['core_size'])?$website_size[$key]['core_size']:0;
        $content_size=isset($website_size[$key]['content_size'])?$website_size[$key]['content_size']:0;
        $themes_size=isset($website_size[$key]['themes_size'])?$website_size[$key]['themes_size']:0;
        $plugins_size=isset($website_size[$key]['plugins_size'])?$website_size[$key]['plugins_size']:0;
        $uploads_size=isset($website_size[$key]['uploads_size'])?$website_size[$key]['uploads_size']:0;
        $file_size = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
        $core_size = size_format($core_size, 2);
        $themes_size = size_format($themes_size, 2);
        $plugins_size = size_format($plugins_size, 2);
        $uploads_size = size_format($uploads_size, 2);
        $content_size = size_format($content_size, 2);
        if(isset($website_size[$key]) && !empty($website_size[$key]))
        {
        }
        else
        {
            $core_style = 'display: none;';
            $content_style = 'display: none;';
            $themes_style = 'display: none;';
            $plugins_style = 'display: none;';
            $uploads_style = 'display: none;';
            $file_style = 'display: none;';
        }

        ?>

        <div style="margin-top:1em;">
            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
            <span><strong>Files & Folders </strong></span>
            <span style="<?php esc_attr_e($file_style); ?>"> (</span><span class="wpvivid-total-content-size wpvivid-size wpvivid-file-size" style="<?php esc_attr_e($file_style); ?>"><?php _e($file_size); ?></span><span style="<?php esc_attr_e($file_style); ?>">)</span>
            <span class="wpvivid-recalc-file-backup-size" style="padding:0.5em;">
                <span class="wpvivid-grey" style="padding:0.2em 0.6em;cursor:pointer; border-radius:3px;">calc</span>
            </span>
        </div>

        <div style="padding-left:2em;">
            <p><span><input type="checkbox" class="wpvivid-custom-core-check" <?php esc_attr_e($core_check); ?>><span><strong>Wordpress Core</strong><span style="<?php esc_attr_e($core_style); ?>"> (</span><span class="wpvivid-core-size wpvivid-size wpvivid-file-size" style="<?php esc_attr_e($core_style); ?>"><?php _e($core_size); ?></span><span style="<?php esc_attr_e($core_style); ?>">)</span></span></span></p>
            <p><span><input type="checkbox" class="wpvivid-custom-content-check" <?php esc_attr_e($content_check); ?>><span><strong>wp-content</strong><span style="<?php esc_attr_e($content_style); ?>"> (</span><span class="wpvivid-content-size wpvivid-size wpvivid-file-size" style="<?php esc_attr_e($content_style); ?>"><?php _e($content_size); ?></span><span style="<?php esc_attr_e($content_style); ?>">)</span></span></span></p>
            <p><span><input type="checkbox" class="wpvivid-custom-themes-check" <?php esc_attr_e($themes_check); ?>><span><strong>themes</strong><span style="<?php esc_attr_e($themes_style); ?>"> (</span><span class="wpvivid-themes-size wpvivid-size wpvivid-file-size" style="<?php esc_attr_e($themes_style); ?>"><?php _e($themes_size); ?></span><span style="<?php esc_attr_e($themes_style); ?>">)</span></span></span></p>
            <p><span><input type="checkbox" class="wpvivid-custom-plugins-check" <?php esc_attr_e($plugins_check); ?>><span><strong>plugins</strong><span style="<?php esc_attr_e($plugins_style); ?>"> (</span><span class="wpvivid-plugins-size wpvivid-size wpvivid-file-size" style="<?php esc_attr_e($plugins_style); ?>"><?php _e($plugins_size); ?></span><span style="<?php esc_attr_e($plugins_style); ?>">)</span></span></span></p>
            <p><span><input type="checkbox" class="wpvivid-custom-uploads-check" <?php esc_attr_e($uploads_check); ?>><span><strong>uploads</strong><span style="<?php esc_attr_e($uploads_style); ?>"> (</span><span class="wpvivid-uploads-size wpvivid-size wpvivid-file-size" style="<?php esc_attr_e($uploads_style); ?>"><?php _e($uploads_size); ?></span><span style="<?php esc_attr_e($uploads_style); ?>">)</span></span></span></p>
            <p>
                <input type="checkbox" class="wpvivid-custom-additional-folder-check" <?php esc_attr_e($additional_folder_check); ?>>
                <span class="wpvivid-handle-additional-folder-detail" style="cursor:pointer;"><strong><span style="color:green;">(optional)</span>Include Non-wordpress Files/Folders</strong></span>
                <span class="dashicons wpvivid-dashicons-grey wpvivid-handle-additional-folder-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </p>
        </div>
        <div style="clear:both;"></div>

        <div class="wpvivid-additional-folder-detail" style="display: none;">
            <div style="padding-left:2em;padding-right:1em;">
                <div style="padding: 0 1em 1em; border: 1px solid rgb(204, 204, 204);">
                    <div>
                        <div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;">
                            <div>
                                <p>
                                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                    <span><strong>Folders</strong></span>
                                    <span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-refresh-include-tree">Refresh<span>
                                </p>
                            </div>

                            <div style="height:250px;">
                                <div class="wpvivid-custom-additional-folder-tree-info" style="margin-top:10px;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow:auto;">Tree Viewer</div>
                            </div>
                            <div style="clear:both;"></div>

                            <div style="padding:1.5em 0 0 0;"><input class="button-primary wpvivid-include-additional-folder-btn" type="submit" value="Include Files/Folders"></div>
                        </div>
                        <div style="width:70%; float:left;box-sizing:border-box;padding-left:0.5em;">
                            <div>
                                <p>
                                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                    <span><strong>Non-WordPress Files/Folders Will Be Backed Up</strong></span>
                                </p>
                            </div>
                            <div class="wpvivid-custom-include-additional-folder-list" style="height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                <?php
                                if($this->is_mu_single !== '1')
                                {
                                    //echo $this->custom_interface->wpvivid_get_exclude_list_ex('additional-folder', $type);
                                    echo $this->custom_interface->wpvivid_get_include_list($type);
                                }
                                ?>
                            </div>
                            <div style="padding:1em 0 0 0;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-include-list" style="float:right;">Empty Included Files/Folders</span></div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                    <div style="clear:both;"></div>
                    <div style="padding:1em 0 0 0;">
                        <span><code>CTRL</code> + <code>Left Click</code> to select multiple files or folders.</span>
                    </div>
                </div>
            </div>
        </div>
        <div style="clear:both;"></div>

        <?php
    }

    public function output_custom_backup_table()
    {
        if($this->is_mu_single === '1')
        {
            $type = 'manual_backup';
            $core_check = 'checked="checked"';
            $database_check = 'checked="checked"';
            $themes_check = 'checked="checked"';
            $plugins_check = 'checked="checked"';
            $uploads_check = 'checked="checked"';
            $content_check = 'checked="checked"';
            $additional_folder_check = '';
            $additional_database_check = '';
            $exclude_part_check = 'checked="checked"';
            $themes_exclude_extension = '';
            $plugins_exclude_extension = '';
            $uploads_exclude_extension = '';
            $content_exclude_extension = '';
        }
        else
        {
            $core_check = 'checked="checked"';
            $database_check = 'checked="checked"';
            $themes_check = 'checked="checked"';
            $plugins_check = 'checked="checked"';
            $uploads_check = 'checked="checked"';
            $content_check = 'checked="checked"';
            $additional_folder_check = '';
            $additional_database_check = '';
            $exclude_part_check = 'checked="checked"';
            $themes_exclude_extension = '';
            $plugins_exclude_extension = '';
            $uploads_exclude_extension = '';
            $content_exclude_extension = '';

            if($this->option === 'manual_backup' || $this->option === 'migration_backup' ||
                $this->option === 'schedule_backup' || $this->option === 'update_schedule_backup' ||
                $this->option === 'export_site')
            {
                $type = 'manual_backup';
                $custom_backup_history = self::wpvivid_get_custom_settings();
                if(isset($custom_backup_history) && !empty($custom_backup_history))
                {
                    if(isset($custom_backup_history['core_option']['core_check'])) {
                        if ($custom_backup_history['core_option']['core_check'] != '1') {
                            $core_check = '';
                        }
                    }
                    if(isset($custom_backup_history['database_option']['database_check'])) {
                        if ($custom_backup_history['database_option']['database_check'] != '1') {
                            $database_check = '';
                        }
                    }
                    if(isset($custom_backup_history['themes_option']['themes_check'])) {
                        if($custom_backup_history['themes_option']['themes_check'] != '1'){
                            $themes_check = '';
                        }
                    }
                    if(isset($custom_backup_history['plugins_option']['plugins_check'])){
                        if ($custom_backup_history['plugins_option']['plugins_check'] != '1') {
                            $plugins_check = '';
                        }
                    }
                    if(isset($custom_backup_history['uploads_option']['uploads_check'])) {
                        if ($custom_backup_history['uploads_option']['uploads_check'] != '1') {
                            $uploads_check = '';
                        }
                    }
                    if(isset($custom_backup_history['content_option']['content_check'])) {
                        if ($custom_backup_history['content_option']['content_check'] != '1') {
                            $content_check = '';
                        }
                    }
                    if(isset($custom_backup_history['other_option']['other_check'])) {
                        if ($custom_backup_history['other_option']['other_check'] == '1') {
                            $additional_folder_check = 'checked="checked"';
                        }
                    }
                    if(!empty($custom_backup_history['additional_database_option'])) {
                        if(isset($custom_backup_history['additional_database_option']['additional_database_check'])) {
                            if ($custom_backup_history['additional_database_option']['additional_database_check'] == '1') {
                                $additional_database_check = 'checked="checked"';
                            }
                        }
                    }

                    if(isset($custom_backup_history['exclude_custom'])){
                        if($custom_backup_history['exclude_custom'] !== '1'){
                            $exclude_part_check = '';
                        }
                    }

                    if(isset($custom_backup_history['themes_option']['themes_extension_list']) && !empty($custom_backup_history['themes_option']['themes_extension_list'])){
                        $themes_exclude_extension = implode(",", $custom_backup_history['themes_option']['themes_extension_list']);
                    }
                    if(isset($custom_backup_history['plugins_option']['plugins_extension_list']) && !empty($custom_backup_history['plugins_option']['plugins_extension_list'])){
                        $plugins_exclude_extension = implode(",", $custom_backup_history['plugins_option']['plugins_extension_list']);
                    }
                    if(isset($custom_backup_history['uploads_option']['uploads_extension_list']) && !empty($custom_backup_history['uploads_option']['uploads_extension_list'])){
                        $uploads_exclude_extension = implode(",", $custom_backup_history['uploads_option']['uploads_extension_list']);
                    }
                    if(isset($custom_backup_history['content_option']['content_extension_list']) && !empty($custom_backup_history['content_option']['content_extension_list'])){
                        $content_exclude_extension = implode(",", $custom_backup_history['content_option']['content_extension_list']);
                    }
                    if(isset($custom_backup_history['other_option']['other_extension_list']) && !empty($custom_backup_history['other_option']['other_extension_list'])){
                        $additional_folder_exclude_extension = implode(",", $custom_backup_history['other_option']['other_extension_list']);
                    }
                }
            }
            else
            {
                $type = 'incremental_backup';
                $custom_incremental_db_history = self::get_incremental_db_setting();
                $custom_incremental_file_history = self::get_incremental_file_settings();

                if(isset($custom_incremental_db_history) && !empty($custom_incremental_db_history)){
                    if(isset($custom_incremental_db_history['database_option']['database_check']))
                    {
                        if ($custom_incremental_db_history['database_option']['database_check'] != '1')
                        {
                            $database_check = '';
                        }
                    }

                    if(!empty($custom_incremental_db_history['additional_database_option']))
                    {
                        if(isset($custom_incremental_db_history['additional_database_option']['additional_database_check']))
                        {
                            if ($custom_incremental_db_history['additional_database_option']['additional_database_check'] == '1')
                            {
                                $additional_database_check = 'checked';
                            }
                        }
                    }
                }

                if(isset($custom_incremental_file_history) && !empty($custom_incremental_file_history)) {
                    if(isset($custom_incremental_file_history['core_option']['core_check']))
                    {
                        if ($custom_incremental_file_history['core_option']['core_check'] != '1')
                        {
                            $core_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['themes_option']['themes_check']))
                    {
                        if ($custom_incremental_file_history['themes_option']['themes_check'] != '1')
                        {
                            $themes_check = '';
                        }
                    }
                    if(isset($custom_incremental_file_history['plugins_option']['plugins_check']))
                    {
                        if ($custom_incremental_file_history['plugins_option']['plugins_check'] != '1')
                        {
                            $plugins_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['uploads_option']['uploads_check']))
                    {
                        if ($custom_incremental_file_history['uploads_option']['uploads_check'] != '1')
                        {
                            $uploads_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['content_option']['content_check']))
                    {
                        if ($custom_incremental_file_history['content_option']['content_check'] != '1')
                        {
                            $content_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['other_option']['other_check']))
                    {
                        if ($custom_incremental_file_history['other_option']['other_check'] == '1')
                        {
                            $additional_folder_check = 'checked';
                        }
                    }

                    if(isset($custom_incremental_file_history['exclude_custom'])){
                        if($custom_incremental_file_history['exclude_custom'] !== '1'){
                            $exclude_part_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['themes_option']['themes_extension_list']) && !empty($custom_incremental_file_history['themes_option']['themes_extension_list'])){
                        $themes_exclude_extension = implode(",", $custom_incremental_file_history['themes_option']['themes_extension_list']);
                    }
                    if(isset($custom_incremental_file_history['plugins_option']['plugins_extension_list']) && !empty($custom_incremental_file_history['plugins_option']['plugins_extension_list'])){
                        $plugins_exclude_extension = implode(",", $custom_incremental_file_history['plugins_option']['plugins_extension_list']);
                    }
                    if(isset($custom_incremental_file_history['uploads_option']['uploads_extension_list']) && !empty($custom_incremental_file_history['uploads_option']['uploads_extension_list'])){
                        $uploads_exclude_extension = implode(",", $custom_incremental_file_history['uploads_option']['uploads_extension_list']);
                    }
                    if(isset($custom_incremental_file_history['content_option']['content_extension_list']) && !empty($custom_incremental_file_history['content_option']['content_extension_list'])){
                        $content_exclude_extension = implode(",", $custom_incremental_file_history['content_option']['content_extension_list']);
                    }
                    if(isset($custom_incremental_file_history['other_option']['other_extension_list']) && !empty($custom_incremental_file_history['other_option']['other_extension_list'])){
                        $additional_folder_exclude_extension = implode(",", $custom_incremental_file_history['other_option']['other_extension_list']);
                    }
                }
            }
        }

        ?>
        <div>
            <div>
                <span class="dashicons dashicons-forms wpvivid-dashicons-blue"></span>
                <span><strong>Custom content</strong></span>
                <span>(</span><span class="wpvivid-total-content-size wpvivid-size">calculating</span><span>)</span>
                <span class="wpvivid-recalc-backup-size" style="padding:0.5em;">
                    <span class="wpvivid-grey" style="padding:0.2em 0.6em;cursor:pointer; border-radius:3px;">re-calc</span>
                </span>
            </div>

            <!-- Database -->
            <div style="padding-left:2em;">
                <p>
                    <span>
                        <input type="checkbox" class="wpvivid-custom-database-check" <?php esc_attr_e($database_check); ?>>
                        <span>
                            <strong class="wpvivid-handle-base-database-detail" style="cursor:pointer;">Database</strong>
                            <span> (</span><span class="wpvivid-database-size wpvivid-size">calculating</span><span>)</span>
                        </span>
                    </span>
                    <?php
                    if($this->is_mu_single !== '1')
                    {
                        ?>
                        <span class="dashicons dashicons-arrow-down-alt2 wpvivid-dashicons-grey wpvivid-handle-base-database-detail" style="cursor:pointer;"></span>
                        <?php
                    }
                    ?>
                </p>
            </div>
            <?php
            if($this->is_mu_single !== '1')
            {
                ?>
                <div class="wpvivid-custom-database-info wpvivid-base-database-detail" style="display: none;">
                    <div class="spinner is-active wpvivid-database-loading" style="margin: 0 5px 10px 0; float: left;"></div>
                    <div style="float: left;">Archieving database tables</div>
                    <div style="clear: both;"></div>
                </div>
                <?php
            }
            ?>
            <div style="clear:both;"></div>
            <!-- File -->
            <div style="padding-left:2em;">
                <p>
                    <span>
                        <input class="wpvivid-custom-core-check" type="checkbox" <?php esc_attr_e($core_check); ?>>
                        <span>
                            <strong>Wordpress Core</strong>
                            <span> (</span><span class="wpvivid-core-size wpvivid-size">calculating</span><span>)</span>
                        </span>
                    </span>
                </p>
                <p>
                    <span>
                        <input class="wpvivid-custom-content-check" type="checkbox" <?php esc_attr_e($content_check); ?>>
                        <span>
                            <strong>wp-content</strong>
                            <span> (</span><span class="wpvivid-content-size wpvivid-size">calculating</span><span>)</span>
                        </span>
                    </span>
                </p>
                <p>
                    <span>
                        <input class="wpvivid-custom-themes-check" type="checkbox" <?php esc_attr_e($themes_check); ?>>
                        <span>
                            <strong>themes</strong>
                            <span> (</span><span class="wpvivid-themes-size wpvivid-size">calculating</span><span>)</span>
                        </span>
                    </span>
                </p>
                <p>
                    <span>
                        <input class="wpvivid-custom-plugins-check" type="checkbox" <?php esc_attr_e($plugins_check); ?>>
                        <span>
                            <strong>plugins</strong>
                            <span> (</span><span class="wpvivid-plugins-size wpvivid-size">calculating</span><span>)</span>
                        </span>
                    </span>
                </p>
                <p>
                    <span>
                        <input class="wpvivid-custom-uploads-check" type="checkbox" <?php esc_attr_e($uploads_check); ?>>
                        <span>
                            <strong>uploads</strong>
                            <span> (</span><span class="wpvivid-uploads-size wpvivid-size">calculating</span><span>)</span>
                        </span>
                    </span>
                </p>
            </div>
            <div style="clear:both;"></div>

            <!-- Advanced -->
            <div style="padding-left:2em;">
                <p>
                    <label style="cursor:pointer;" title="Click to expand">
                        <span><strong>Advanced Options</strong></span>
                        <span class="dashicons dashicons-arrow-down-alt wpvivid-dashicons-green" style="margin-top:2px;"></span>
                    </label>
                </p>
            </div>
            <!-- Non-wordpress -->
            <div style="padding-left:2em;">
                <p>
                    <input class="wpvivid-custom-additional-folder-check" type="checkbox" <?php esc_attr_e($additional_folder_check); ?>>
                    <span class="wpvivid-handle-additional-folder-detail" style="cursor:pointer;"><strong>Include Non-wordpress Files/Folders</strong></span>
                    <span class="dashicons dashicons-arrow-down-alt2 wpvivid-dashicons-grey wpvivid-handle-additional-folder-detail" style="cursor:pointer;"></span>
                </p>
                <div class="wpvivid-additional-folder-detail" style="padding:0 1em 1em;border:1px solid #ccc;display: none;">
                    <div>
                        <div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;">
                            <div>
                                <p>
                                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                    <span><strong>Folders</strong></span>
                                    <span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-refresh-include-tree">Refresh<span>
                                </p>
                            </div>

                            <div style="height:250px;">
                                <div class="wpvivid-custom-additional-folder-tree-info" style="margin-top:10px;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow:auto;">Tree Viewer</div>
                            </div>
                            <div style="clear:both;"></div>

                            <div style="padding:1.5em 0 0 0;"><input class="button-primary wpvivid-include-additional-folder-btn" type="submit" value="Include Files/Folders"></div>
                        </div>
                        <div style="width:70%; float:left;box-sizing:border-box;padding-left:0.5em;">
                            <div>
                                <p>
                                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                    <span><strong>Non-WordPress Files/Folders Will Be Backed Up</strong></span>
                                </p>
                            </div>
                            <div class="wpvivid-custom-include-additional-folder-list" style="height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                <?php
                                if($this->is_mu_single !== '1')
                                {
                                    echo $this->custom_interface->wpvivid_get_exclude_list_ex('additional-folder', $type);
                                }
                                ?>
                            </div>
                            <div style="padding:1em 0 0 0;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-include-list" style="float:right;">Empty Included Files/Folders</span></div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                    <div style="clear:both;"></div>
                    <div style="padding:1em 0 0 0;">
                        <span><code>CTRL</code> + <code>Left Click</code> to select multiple files or folders.</span>
                    </div>
                </div>
            </div>
            <!-- Additional Database -->
            <?php
            if($this->is_mu_single !== '1')
            {
                ?>
                <div style="padding-left:2em;">
                    <p>
                        <input class="wpvivid-custom-additional-database-check" type="checkbox" <?php esc_attr_e($additional_database_check); ?>>
                        <span class="wpvivid-handle-additional-database-detail" style="cursor:pointer;"><strong>Include Additional Databases</strong></span>
                        <span class="dashicons dashicons-arrow-down-alt2 wpvivid-dashicons-grey wpvivid-handle-additional-database-detail" style="cursor:pointer;"></span>
                    </p>
                    <div class="wpvivid-additional-database-detail" style="padding:0 1em 1em;border:1px solid #ccc;display: none;">
                        <div style="border-bottom:1px solid #ccc;">
                            <p>
                                <span>Host: </span><span><input type="text" class="wpvivid-additional-database-host" style="width: 120px;"></span>
                                <span>User Name: </span><span><input type="text" class="wpvivid-additional-database-user" style="width: 120px;"></span>
                                <span>Password: </span><span><input type="password" class="wpvivid-additional-database-pass" style="width: 120px;"></span>
                                <span><input type="submit" value="Connect" class="button button-primary wpvivid-connect-additional-database" ></span>
                            </p>
                        </div>
                        <div style="width:50%;float:left;box-sizing:border-box;padding-right:0.5em;">
                            <div>
                                <p><span class="dashicons dashicons-excerpt-view wpvivid-dashicons-blue"></span>
                                    <span><strong>Databases</strong></span>
                                    <span>( click "<span class="dashicons dashicons-plus-alt wpvivid-icon-16px"></span>" icon to add the database to backup list )</span>
                                </p>
                            </div>
                            <div class="wpvivid-additional-database-add" style="height:100px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;"></div>
                            <div style="clear:both;"></div>
                        </div>
                        <div style="width:50%; float:left;box-sizing:border-box;padding-left:0.5em;">
                            <div>
                                <p>
                                    <span class="dashicons dashicons-list-view wpvivid-dashicons-orange"></span>
                                    <span><strong>Databases will be backed up</strong></span>
                                    <span>( click <span class="dashicons dashicons-trash wpvivid-icon-16px"></span> icon to exclude the database )</span>
                                </p>
                            </div>
                            <div class="wpvivid-additional-database-list" style="height:100px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                <?php
                                $html = '';
                                $html = apply_filters('wpvivid_additional_database_list', $html);
                                echo $html;
                                ?>
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    public function output_advanced_option_table()
    {
        $exclude_file_type = '';
        if($this->is_mu_single === '1')
        {
            $type = 'manual_backup';
        }
        else
        {
            if($this->option === 'manual_backup' || $this->option === 'migration_backup' ||
                $this->option === 'schedule_backup' || $this->option === 'update_schedule_backup' ||
                $this->option === 'local_export_site' || $this->option === 'remote_export_site' ||
                $this->option === 'migration_export_site' || $this->option === 'export_site')
            {
                $type = 'manual_backup';
                $custom_backup_history = self::wpvivid_get_new_backup_history();
                if(isset($custom_backup_history) && !empty($custom_backup_history))
                {
                    if(isset($custom_backup_history['exclude_file_type']))
                    {
                        $exclude_file_type = $custom_backup_history['exclude_file_type'];
                    }
                }
            }
            else
            {
                $type = 'incremental_backup';
                $custom_incremental_file_history = self::get_incremental_file_settings();
            }
        }
        ?>
        <div>
            <p>
                <span class="dashicons dashicons-admin-generic wpvivid-dashicons-blue"></span>
                <span class="wpvivid-handle-advanced-option-detail" style="cursor:pointer;"><strong>Advanced Settings</strong></span>
                <span class="dashicons wpvivid-dashicons-grey wpvivid-handle-advanced-option-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </p>
        </div>

        <div class="wpvivid-advanced-option-detail" style="padding-left:2em; display: none;">
            <p>
                <span class="wpvivid-handle-tree-detail" style="cursor:pointer;"><strong>Exclude Files/Folders Inside /wp-content/ Folder</strong></span>
                <span class="dashicons wpvivid-dashicons-grey wpvivid-handle-tree-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </p>
            <div class="wpvivid-tree-detail" style="padding:0 1em 1em;border:1px solid #ccc; display: none;">
                <div>
                    <div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;">
                        <div>
                            <p>
                                <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                <span><strong>Folders</strong></span>
                                <span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-refresh-exclude-tree">Refresh<span>
                            </p>
                        </div>

                        <div style="height:250px;">
                            <div class="wpvivid-custom-exclude-tree-info" style="margin-top:10px;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow:auto;">Tree Viewer</div>
                        </div>
                        <div style="clear:both;"></div>

                        <div style="padding:1.5em 0 0 0;"><input class="button-primary wpvivid-custom-tree-exclude-btn" type="submit" value="Exclude Files/Folders/File Types"></div>
                    </div>
                    <div style="width:70%; float:left;box-sizing:border-box;padding-left:0.5em;">
                        <div>
                            <p>
                                <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                <span><strong>Excluded Files/Folders</strong></span>
                            </p>
                        </div>
                        <div class="wpvivid-custom-exclude-list" style="margin-top:10px;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                            <?php
                            if($this->is_mu_single !== '1')
                            {
                                echo $this->custom_interface->wpvivid_get_exclude_list($type);
                            }
                            ?>
                        </div>

                        <div style="padding:1em 0 0 0;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-exclude-list" style="float:right;">Empty Excluded Files/Folders</span></div>
                    </div>
                    <div style="clear:both;"></div>
                </div>
                <div style="clear:both;"></div>
                <div style="padding:1em 0 0 0;">
                    <span><code>CTRL</code> + <code>Left Click</code> to select multiple files or folders.</span>
                </div>
            </div>

            <div>
                <p>
                    <span class="wpvivid-handle-exclude-file-type-detail" style="cursor:pointer;"><strong>Exclude File Types</strong></span>
                    <span class="dashicons wpvivid-dashicons-grey wpvivid-handle-exclude-file-type-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                </p>
            </div>
            <div class="wpvivid-exclude-file-type-detail" style="display: none;">
                <input class="wpvivid-custom-exclude-extension" style="width:100%; padding: 0.5em;border:1px solid #ccc;" value="<?php esc_attr_e($exclude_file_type); ?>" placeholder="Exclude file types, separate by comma - for example: gif, jpg, webp, pdf" />
            </div>
        </div>
        <?php
    }

    public function load_js()
    {
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['basedir'];
        $path = str_replace('\\','/',$path);
        $uploads_path = $path.'/';

        $content_dir = WP_CONTENT_DIR;
        $path = str_replace('\\','/',$content_dir);
        $content_path = $path.'/';

        if(!function_exists('get_home_path'))
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        $home_path = str_replace('\\','/', get_home_path());

        $themes_path = str_replace('\\','/', get_theme_root());
        $themes_path = $themes_path.'/';

        $plugins_path = str_replace('\\','/', WP_PLUGIN_DIR);
        $plugins_path = $plugins_path.'/';
        ?>
        <script>
            var path_arr = {};
            path_arr['core'] = '<?php echo $home_path; ?>';
            path_arr['content'] = '<?php echo $content_path; ?>';
            path_arr['uploads'] = '<?php echo $uploads_path; ?>';
            path_arr['themes'] = '<?php echo $themes_path; ?>';
            path_arr['plugins'] = '<?php echo $plugins_path; ?>';

            function wpvivid_handle_custom_open_close_ex(handle_obj, obj, parent_id){
                if(obj.is(":hidden")) {
                    handle_obj.each(function(){
                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2')){
                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                        }
                    });
                    obj.show();
                }
                else{
                    handle_obj.each(function(){
                        if(jQuery(this).hasClass('dashicons-arrow-up-alt2')){
                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                            jQuery(this).addClass('dashicons-arrow-down-alt2');
                        }
                    });
                    obj.hide();
                }
            }

            function wpvivid_init_custom_include_tree(tree_path, parent_id, is_mu_single, refresh=0) {
                if (refresh) {
                    jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-tree-info').on('activate_node.jstree', function (e, data) {
                    }).jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id,
                                    'path': tree_path
                                };
                                var ajax_data = {
                                    'action': 'wpvivid_get_custom_dir',
                                    'tree_node': tree_node,
                                    'is_mu_single': is_mu_single
                                };
                                ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: wpvivid_ajax_object_addon.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        callback.call(this, jsonarray.nodes);
                                        jQuery('#'+parent_id).find('.wpvivid-include-additional-folder-btn').attr('disabled', false);
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        },
                        "plugins": ["sort"],
                        "sort": function(a, b) {
                            a1 = this.get_node(a);
                            b1 = this.get_node(b);
                            if (a1.icon === b1.icon) {
                                return (a1.text.toLowerCase() > b1.text.toLowerCase()) ? 1 : -1;
                            } else {
                                return (a1.icon > b1.icon) ? 1 : -1;
                            }
                        }
                    });
                }
            }

            function wpvivid_init_custom_exclude_tree(tree_path, parent_id, is_mu_single, refresh=0) {
                if (refresh) {
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-tree-info').on('activate_node.jstree', function (event, data) {
                    }).jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id,
                                    'path': tree_path
                                };
                                var ajax_data = {
                                    'action': 'wpvivid_get_custom_tree_dir',
                                    'tree_node': tree_node,
                                    'is_mu_single': is_mu_single
                                };
                                ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: wpvivid_ajax_object_addon.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        callback.call(this, jsonarray.nodes);
                                        jQuery('#'+parent_id).find('.wpvivid-custom-tree-exclude-btn').attr('disabled', false);
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        },
                        "plugins": ["sort"],
                        "sort": function(a, b) {
                            a1 = this.get_node(a);
                            b1 = this.get_node(b);
                            if (a1.icon === b1.icon) {
                                return (a1.text.toLowerCase() > b1.text.toLowerCase()) ? 1 : -1;
                            } else {
                                return (a1.icon > b1.icon) ? 1 : -1;
                            }
                        },
                    });
                }
            }

            function wpvivid_change_custom_exclude_info(type, parent_id){
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-module').hide();
                if(type === 'themes'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-module').show();
                }
                else if(type === 'plugins'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-module').show();
                }
                else if(type === 'content'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-module').show();
                }
                else if(type === 'uploads'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-module').show();
                }
            }

            function wpvivid_check_tree_repeat(tree_type, value, parent_id) {
                if(tree_type === 'themes'){
                    var list = 'wpvivid-custom-exclude-themes-list';
                }
                else if(tree_type === 'plugins'){
                    var list = 'wpvivid-custom-exclude-plugins-list';
                }
                else if(tree_type === 'content'){
                    var list = 'wpvivid-custom-exclude-content-list';
                }
                else if(tree_type === 'uploads'){
                    var list = 'wpvivid-custom-exclude-uploads-list';
                }
                else if(tree_type === 'additional-folder'){
                    var list = 'wpvivid-custom-include-additional-folder-list';
                }
                else if(tree_type === 'exclude-folder'){
                    var list = 'wpvivid-custom-exclude-list';
                }

                var brepeat = false;
                jQuery('#'+parent_id).find('.'+list+' div').find('span:eq(2)').each(function (){
                    if (value === this.innerHTML) {
                        brepeat = true;
                    }
                });
                return brepeat;
            }

            function wpvivid_get_filter_database_list(table_type, text, option, parent_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_get_database_by_filter',
                    'table_type': table_type,
                    'filter_text': text,
                    'option_type': option
                };

                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(table_type === 'base_table')
                            {
                                jQuery('#'+parent_id).find('.wpvivid-database-base-list').html(jsonarray.database_html);
                            }
                            else if(table_type === 'other_table')
                            {
                                jQuery('#'+parent_id).find('.wpvivid-database-other-list').html(jsonarray.database_html);
                            }
                            else if(table_type === 'diff_prefix_table')
                            {
                                jQuery('#'+parent_id).find('.wpvivid-database-diff-prefix-list').html(jsonarray.database_html);
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('get list', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-handle-advanced-option-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-handle-advanced-option-detail');
                var obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-advanced-option-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->advanced_id; ?>');
                wpvivid_init_custom_exclude_tree('<?php echo $content_path; ?>', '<?php echo $this->advanced_id; ?>', '<?php echo $this->is_mu_single; ?>');
                var showContent = jQuery('.wpvivid-custom-exclude-list');
                showContent[0].scrollTop = showContent[0].scrollHeight;
            });

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-handle-tree-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-handle-tree-detail');
                var obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-tree-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->advanced_id; ?>');
            });

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-handle-exclude-file-type-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-handle-exclude-file-type-detail');
                var obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-exclude-file-type-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->advanced_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-database-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-database-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-base-database-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-base-database-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-base-database-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-additional-database-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-additional-database-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-file-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-file-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-file-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-additional-folder-detail', function(){
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-additional-folder-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-folder-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
                wpvivid_init_custom_include_tree('<?php echo $home_path; ?>', '<?php echo $this->parent_id; ?>', '<?php echo $this->is_mu_single; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-select-base-table-button', function(){
                var text = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-select-base-table-text').val();
                wpvivid_get_filter_database_list('base_table', text, '<?php echo $this->option; ?>', '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-select-other-table-button', function(){
                var text = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-select-other-table-text').val();
                wpvivid_get_filter_database_list('other_table', text, '<?php echo $this->option; ?>', '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-select-diff-prefix-table-button', function(){
                var text = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-select-diff-prefix-table-text').val();
                wpvivid_get_filter_database_list('diff_prefix_table', text, '<?php echo $this->option; ?>', '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-connect-additional-database', function(){
                var db_user = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-user').val();
                var db_pass = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-pass').val();
                var db_host = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-host').val();
                if(db_user !== ''){
                    if(db_host !== ''){
                        var db_json = {};
                        db_json['db_user'] = db_user;
                        db_json['db_pass'] = db_pass;
                        db_json['db_host'] = db_host;
                        var db_connect_info = JSON.stringify(db_json);
                        var ajax_data = {
                            'action': 'wpvivid_connect_additional_database',
                            'database_info': db_connect_info,
                            'use_new_ui': '1'
                        };
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-connect-additional-database').css({'pointer-events': 'none', 'opacity': '0.4'});
                        wpvivid_post_request_addon(ajax_data, function (data) {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray !== null) {
                                if (jsonarray.result === 'success') {
                                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-add').html(jsonarray.html);
                                }
                                else {
                                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                    alert(jsonarray.error);
                                }
                            }
                            else {
                                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                alert('Login Failed. Please check the credentials you entered and try again.');
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                    else{
                        alert('Host is required.');
                    }
                }
                else{
                    alert('User Name is required.');
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-add-additional-db', function(){
                var db_name = jQuery(this).attr('name');
                var db_user = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-user').val();
                var db_pass = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-pass').val();
                var db_host = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-host').val();
                if(db_user !== ''){
                    if(db_host !== ''){
                        var db_json = {};
                        db_json['db_user'] = db_user;
                        db_json['db_pass'] = db_pass;
                        db_json['db_host'] = db_host;
                        db_json['additional_database_list'] = Array();
                        db_json['additional_database_list'].push(db_name);

                        var database_info = JSON.stringify(db_json);
                        var ajax_data = {
                            'action': 'wpvivid_add_additional_database',
                            'database_info': database_info
                        };
                        wpvivid_post_request_addon(ajax_data, function (data) {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result == 'success') {
                                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-list').html('');
                                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-list').append(jsonarray.html);
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                    else{
                        alert('Host is required.');
                    }
                }
                else{
                    alert('User Name is required.');
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-additional-database-remove', function(){
                var database = jQuery(this).attr('database-name');
                var ajax_data = {
                    'action': 'wpvivid_remove_additional_database',
                    'database': database
                };
                jQuery(this).css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function(data){
                    jQuery(this).css({'pointer-events': 'auto', 'opacity': '1'});
                    var jsonarray = jQuery.parseJSON(data);
                    if(jsonarray.result == 'success'){
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-list').html('');
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-database-list').append(jsonarray.html);
                    }
                    else{
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(this).css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('change', '#wpvivid_custom_tree_selector', function(){
                var value = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-exclude-tree-info').jstree("destroy").empty();
                wpvivid_init_custom_exclude_tree(path_arr[value], '<?php echo $this->parent_id; ?>', '<?php echo $this->is_mu_single; ?>');
                wpvivid_change_custom_exclude_info(value, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-refresh-include-tree', function(){
                var value = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                wpvivid_init_custom_include_tree(path_arr[value], '<?php echo $this->parent_id; ?>', '<?php echo $this->is_mu_single; ?>', 1);
            });

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-refresh-exclude-tree', function(){
                var value = jQuery('#<?php echo $this->advanced_id; ?>').find('#wpvivid_custom_tree_selector').val();
                wpvivid_init_custom_exclude_tree('<?php echo $content_path; ?>', '<?php echo $this->advanced_id; ?>', '<?php echo $this->is_mu_single; ?>', 1);
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-include-additional-folder-btn', function(){
                var select_folders = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-additional-folder-tree-info').jstree(true).get_selected(true);
                var tree_path = '<?php echo $home_path; ?>';
                var list_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-include-additional-folder-list');
                var tree_type = 'additional-folder';

                jQuery.each(select_folders, function (index, select_item) {
                    if (select_item.id !== tree_path) {
                        var value = select_item.id;
                        value = value.replace(tree_path, '');
                        if (!wpvivid_check_tree_repeat(tree_type, value, '<?php echo $this->parent_id; ?>')) {
                            var class_name = select_item.icon;
                            if(class_name === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                var type = 'folder';
                            }
                            else{
                                var type = 'file';
                            }
                            var tr = "<div class='wpvivid-text-line' type='"+type+"'>" +
                                "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                                "<span class='"+class_name+"'></span>" +
                                "<span class='wpvivid-text-line'>" + value + "</span>" +
                                "</div>";
                            list_obj.append(tr);
                        }
                    }
                });
            });

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-custom-tree-exclude-btn', function(){
                var select_folders = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-custom-exclude-tree-info').jstree(true).get_selected(true);
                var tree_type = 'exclude-folder';
                var list_obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-custom-exclude-list');
                jQuery.each(select_folders, function (index, select_item) {
                    var value = select_item.id;
                    if (!wpvivid_check_tree_repeat(tree_type, value, '<?php echo $this->advanced_id; ?>')) {
                        var class_name = select_item.icon;
                        if(class_name === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                            var type = 'folder';
                        }
                        else{
                            var type = 'file';
                        }
                        var tr = "<div class='wpvivid-text-line' type='"+type+"'>" +
                            "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                            "<span class='"+class_name+"'></span>" +
                            "<span class='wpvivid-text-line'>" + value + "</span>" +
                            "</div>";
                        list_obj.append(tr);
                        var showContent = jQuery('.wpvivid-custom-exclude-list');
                        showContent[0].scrollTop = showContent[0].scrollHeight;
                    }
                });
            });

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-custom-exclude-extension-add', function(){
                var value = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-custom-exclude-extension').val();
                if(value !== '')
                {
                    var tree_type = 'exclude-folder';
                    var list_obj = jQuery('#<?php echo $this->advanced_id; ?>').find('.wpvivid-custom-exclude-list');
                    if (!wpvivid_check_tree_repeat(tree_type, value, '<?php echo $this->advanced_id; ?>')) {
                        var class_name = 'dashicons dashicons-media-code wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        var type = 'ext';
                        var tr = "<div class='wpvivid-text-line' type='"+type+"'>" +
                            "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                            "<span class='"+class_name+"'></span>" +
                            "<span class='wpvivid-text-line'>" + value + "</span>" +
                            "</div>";
                        list_obj.append(tr);
                        var showContent = jQuery('.wpvivid-custom-exclude-list');
                        showContent[0].scrollTop = showContent[0].scrollHeight;
                    }
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-remove-custom-exlcude-tree', function(){
                jQuery(this).parent().remove();
            });

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-remove-custom-exlcude-tree', function(){
                jQuery(this).parent().remove();
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-clear-custom-include-list', function(){
                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-include-additional-folder-list').html('');
            });

            jQuery('#<?php echo $this->advanced_id; ?>').on('click', '.wpvivid-clear-custom-exclude-list', function(){
                var list = 'wpvivid-custom-exclude-list';
                jQuery('#<?php echo $this->advanced_id; ?>').find('.'+list).html('');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-database-table-check', function(){
                if(jQuery(this).prop('checked')){
                    if(jQuery(this).hasClass('wpvivid-database-base-table-check')){
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]').prop('checked', true);
                    }
                    else if(jQuery(this).hasClass('wpvivid-database-other-table-check')){
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]').prop('checked', true);
                    }
                    else if(jQuery(this).hasClass('wpvivid-database-diff-prefix-table-check')){
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]').prop('checked', true);
                    }
                }
                else{
                    if (jQuery(this).hasClass('wpvivid-database-base-table-check')) {
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]').prop('checked', false);
                    }
                    else if (jQuery(this).hasClass('wpvivid-database-other-table-check')) {
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]').prop('checked', false);
                    }
                    else if (jQuery(this).hasClass('wpvivid-database-diff-prefix-table-check')) {
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]').prop('checked', false);
                    }
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on("click", 'input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]', function(){
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-base-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-base-table-check').prop('checked', false);
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on("click", 'input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]', function(){
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-other-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-other-table-check').prop('checked', false);
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on("click", 'input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]', function(){
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-diff-prefix-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-diff-prefix-table-check').prop('checked', false);
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-recalc-database-backup-size', function(){
                if('<?php echo $this->parent_id; ?>' === 'wpvivid_incremental_backup_deploy')
                {
                    var type = 'incremental';
                    var advanced_id = 'wpvivid_incremental_backup_advanced_option';

                    var exclude_dirs = wpvivid_get_exclude_json(advanced_id);
                    var backup_data = {
                        'exclude_files': exclude_dirs
                    };
                    backup_data = JSON.stringify(backup_data);

                    backup_data = JSON.parse(backup_data);
                    var file_json = wpvivid_create_incremental_json_ex('<?php echo $this->parent_id; ?>', 'files');
                    var db_json = wpvivid_create_incremental_json_ex('<?php echo $this->parent_id; ?>', 'database');
                    var custom_dirs = {};
                    jQuery.extend(custom_dirs, file_json);
                    jQuery.extend(custom_dirs, db_json);
                    var custom_option = {
                        'custom_dirs': custom_dirs
                    };
                    jQuery.extend(backup_data, custom_option);
                    backup_data = JSON.stringify(backup_data);
                }
                else
                {
                    var type = 'general';

                    if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_manual_backup')
                    {
                        var advanced_id = 'wpvivid_custom_manual_advanced_option';
                    }
                    else if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_schedule_backup')
                    {
                        var advanced_id = 'wpvivid_custom_schedule_advanced_option';
                    }
                    else if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_update_schedule_backup')
                    {
                        var advanced_id = 'wpvivid_custom_update_schedule_advanced_option';
                    }

                    else if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_local_export_site')
                    {
                        var advanced_id = 'wpvivid_custom_local_export_advanced_option';
                    }
                    else if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_remote_export_site')
                    {
                        var advanced_id = 'wpvivid_custom_remote_export_advanced_option';
                    }
                    else if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_migration_export_site')
                    {
                        var advanced_id = 'wpvivid_custom_migration_export_advanced_option';
                    }

                    var exclude_dirs = wpvivid_get_exclude_json(advanced_id);
                    var backup_data = {
                        'exclude_files': exclude_dirs
                    };
                    backup_data = JSON.stringify(backup_data);

                    backup_data = JSON.parse(backup_data);
                    var custom_dirs = wpvivid_get_custom_setting_json_ex('<?php echo $this->parent_id; ?>');
                    var custom_option = {
                        'custom_dirs': custom_dirs
                    };
                    jQuery.extend(backup_data, custom_option);
                    backup_data = JSON.stringify(backup_data);
                }

                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-recalc-database-backup-size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-size').html('calculating');

                wpvivid_get_need_calc(type, backup_data, 'database', '<?php echo $this->parent_id; ?>');

                var website_item_arr = new Array('database');

                var total_file_size = 0;
                wpvivid_recalc_backup_size_ex(website_item_arr, backup_data, '<?php echo $this->parent_id; ?>', type, 'database', total_file_size);
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-recalc-file-backup-size', function(){
                if('<?php echo $this->parent_id; ?>' === 'wpvivid_incremental_backup_deploy')
                {
                    var type = 'incremental';
                    var advanced_id = 'wpvivid_incremental_backup_advanced_option';

                    var exclude_dirs = wpvivid_get_exclude_json(advanced_id);
                    var backup_data = {
                        'exclude_files': exclude_dirs
                    };
                    backup_data = JSON.stringify(backup_data);

                    backup_data = JSON.parse(backup_data);
                    var file_json = wpvivid_create_incremental_json_ex('<?php echo $this->parent_id; ?>', 'files');
                    var db_json = wpvivid_create_incremental_json_ex('<?php echo $this->parent_id; ?>', 'database');
                    var custom_dirs = {};
                    jQuery.extend(custom_dirs, file_json);
                    jQuery.extend(custom_dirs, db_json);
                    var custom_option = {
                        'custom_dirs': custom_dirs
                    };
                    jQuery.extend(backup_data, custom_option);
                    backup_data = JSON.stringify(backup_data);
                }
                else
                {
                    var type = 'general';

                    if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_manual_backup')
                    {
                        var advanced_id = 'wpvivid_custom_manual_advanced_option';
                    }

                    var exclude_dirs = wpvivid_get_exclude_json(advanced_id);
                    var backup_data = {
                        'exclude_files': exclude_dirs
                    };
                    backup_data = JSON.stringify(backup_data);

                    backup_data = JSON.parse(backup_data);
                    var custom_dirs = wpvivid_get_custom_setting_json_ex('<?php echo $this->parent_id; ?>');
                    var custom_option = {
                        'custom_dirs': custom_dirs
                    };
                    jQuery.extend(backup_data, custom_option);
                    backup_data = JSON.stringify(backup_data);
                }

                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-recalc-file-backup-size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-file-size').html('calculating');

                wpvivid_get_need_calc(type, backup_data, 'file', '<?php echo $this->parent_id; ?>');

                var website_item_arr = new Array('core', 'content', 'themes', 'plugins', 'uploads');

                var total_file_size = 0;
                wpvivid_recalc_backup_size_ex(website_item_arr, backup_data, '<?php echo $this->parent_id; ?>', type, 'file', total_file_size);
            });

            function wpvivid_get_need_calc(type, backup_data, what, parent_id)
            {
                if(type === 'incremental')
                {
                    var ajax_data = {
                        'action': 'wpvivid_get_need_calc',
                        'backup_data': backup_data,
                        'calc_content': what,
                        'incremental': '1'
                    };
                }
                else
                {
                    var ajax_data = {
                        'action': 'wpvivid_get_need_calc',
                        'backup_data': backup_data,
                        'calc_content': what
                    };
                }

                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            if(what === 'database')
                            {
                                var need_calc_list = new Array('database');

                                jQuery.each(need_calc_list, function (index, value) {
                                    if(value === 'database')
                                    {
                                        var json_reponse = jsonarray.database_calc;
                                    }
                                    if(json_reponse)
                                    {
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').show();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').prev().show();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').next().show();
                                    }
                                    else
                                    {
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').hide();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').prev().hide();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').next().hide();
                                    }
                                });
                            }
                            else
                            {
                                var need_calc_list = new Array('core', 'content', 'themes', 'plugins', 'uploads');

                                jQuery.each(need_calc_list, function (index, value) {
                                    if(value === 'core')
                                    {
                                        var json_reponse = jsonarray.core_calc;
                                    }
                                    if(value === 'content')
                                    {
                                        var json_reponse = jsonarray.content_calc;
                                    }
                                    if(value === 'themes')
                                    {
                                        var json_reponse = jsonarray.themes_calc;
                                    }
                                    if(value === 'plugins')
                                    {
                                        var json_reponse = jsonarray.plugins_calc;
                                    }
                                    if(value === 'uploads')
                                    {
                                        var json_reponse = jsonarray.uploads_calc;
                                    }
                                    if(json_reponse)
                                    {
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').show();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').prev().show();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').next().show();
                                    }
                                    else
                                    {
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').hide();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').prev().hide();
                                        jQuery('#'+parent_id).find('.wpvivid-'+value+'-size').next().hide();
                                    }
                                });

                               if(jsonarray.file_calc)
                               {
                                   jQuery('#'+parent_id).find('.wpvivid-total-content-size').show();
                                   jQuery('#'+parent_id).find('.wpvivid-total-content-size').prev().show();
                                   jQuery('#'+parent_id).find('.wpvivid-total-content-size').next().show();
                               }
                               else
                               {
                                   jQuery('#'+parent_id).find('.wpvivid-total-content-size').hide();
                                   jQuery('#'+parent_id).find('.wpvivid-total-content-size').prev().hide();
                                   jQuery('#'+parent_id).find('.wpvivid-total-content-size').next().hide();
                               }
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('getting need calc', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#<?php echo $this->parent_id; ?>').on("click", '.wpvivid-recalc-backup-size', function(){
                if('<?php echo $this->parent_id; ?>' === 'wpvivid_incremental_backup_deploy')
                {
                    var type = 'incremental';
                    var file_json = wpvivid_create_incremental_json_ex('<?php echo $this->parent_id; ?>', 'files');
                    var db_json = wpvivid_create_incremental_json_ex('<?php echo $this->parent_id; ?>', 'database');

                    var custom = {};
                    custom['custom'] = {
                        'files': file_json,
                        'db': db_json,
                    };
                    var custom_option = JSON.stringify(custom);
                }
                else
                {
                    var type = 'general';

                    if('<?php echo $this->parent_id; ?>' === 'wpvivid_custom_manual_backup')
                    {
                        var advanced_id = 'wpvivid_custom_manual_advanced_option';
                    }

                    var exclude_dirs = wpvivid_get_exclude_json(advanced_id);
                    var backup_data = {
                        'exclude_files': exclude_dirs
                    };
                    jQuery.extend(backup_data, custom_option);
                    backup_data = JSON.stringify(backup_data);

                    backup_data = JSON.parse(backup_data);
                    var custom_dirs = wpvivid_get_custom_setting_json_ex('<?php echo $this->parent_id; ?>');
                    var custom_option = {
                        'custom_dirs': custom_dirs
                    };
                    jQuery.extend(backup_data, custom_option);
                    backup_data = JSON.stringify(backup_data);
                }

                jQuery('.wpvivid-recalc-backup-size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-size').html('calculating');

                var website_item_arr = new Array('database', 'core', 'content', 'themes', 'plugins', 'uploads', 'additional_folder');

                wpvivid_recalc_backup_size_ex(website_item_arr, backup_data, '<?php echo $this->parent_id; ?>', type);
            });

            //  old version
            function wpvivid_get_custom_setting_json(parent_id){
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

            //  new version
            function wpvivid_get_custom_setting_json_ex(parent_id){
                var json = {};
                //core
                json['core_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                    json['core_check'] = '1';
                }

                //themes
                json['themes_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                    json['themes_check'] = '1';
                }

                //plugins
                json['plugins_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                    json['plugins_check'] = '1';
                }

                //content
                json['content_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }

                //uploads
                json['uploads_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }

                //additional folders/files
                json['other_check'] = '0';
                json['other_list'] = [];
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['other_check'] = '1';
                }
                if(json['other_check'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['other_list'].push(folder_name);
                    });
                }

                //database
                json['database_check'] = '0';
                json['exclude-tables'] = Array();
                json['include-tables'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                    json['database_check'] = '1';
                }
                jQuery('#'+parent_id).find('input[option=base_db][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['exclude-tables'].push(jQuery(value).val());
                    }
                });
                jQuery('#'+parent_id).find('input[option=other_db][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['exclude-tables'].push(jQuery(value).val());
                    }
                });
                jQuery('#'+parent_id).find('input[option=diff_prefix_db][type=checkbox]').each(function(index, value){
                    if(jQuery(value).prop('checked')){
                        json['include-tables'].push(jQuery(value).val());
                    }
                });

                //additional database
                json['additional_database_check'] = '0';
                json['additional_database_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                    json['additional_database_check'] = '1';
                }
                jQuery('#'+parent_id).find('.wpvivid-additional-database-list').find('div').each(function(index, value){
                    var database_name = jQuery(this).attr('database-name');
                    var database_host = jQuery(this).attr('database-host');
                    var database_user = jQuery(this).attr('database-user');
                    var database_pass = jQuery(this).attr('database-pass');
                    json['additional_database_list'][database_name] = {};
                    json['additional_database_list'][database_name]['db_host'] = database_host;
                    json['additional_database_list'][database_name]['db_user'] = database_user;
                    json['additional_database_list'][database_name]['db_pass'] = database_pass;
                });

                return json;
            }

            function wpvivid_get_mu_site_setting_ex(parent_id)
            {
                var json = {};
                json['mu_site_id']='';
                jQuery('#'+parent_id).find('input[name=mu_site]').each(function(){
                    if(jQuery(this).prop('checked'))
                    {
                        json['mu_site_id']=jQuery(this).val();
                    }
                });
                return json;
            }

            function wpvivid_get_exclude_json(advanced_id)
            {
                var json = [];
                jQuery('#'+advanced_id).find('.wpvivid-custom-exclude-list div').find('span:eq(2)').each(function ()
                {
                    var item={};
                    item['path']=this.innerHTML;
                    var type = jQuery(this).closest('div').attr('type');
                    item['type']=type;
                    json.push(item);
                });
                return json;
            }

            function wpvivid_get_exclude_file_type(advanced_id)
            {
                var exclude_file_type = jQuery('#'+advanced_id).find('.wpvivid-custom-exclude-extension').val();
                return exclude_file_type;
            }

            function wpvivid_create_incremental_json_ex(parent_id, incremental_type){
                var json = {};
                if(incremental_type === 'files'){
                    //core
                    json['core_check'] = '0';
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                        json['core_check'] = '1';
                    }

                    //themes
                    json['themes_check'] = '0';
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                        json['themes_check'] = '1';
                    }

                    //plugins
                    json['plugins_check'] = '0';
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                        json['plugins_check'] = '1';
                    }

                    //content
                    json['content_check'] = '0';
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                        json['content_check'] = '1';
                    }

                    //uploads
                    json['uploads_check'] = '0';
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                        json['uploads_check'] = '1';
                    }

                    //additional folders/files
                    json['other_check'] = '0';
                    json['other_list'] = [];
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                        json['other_check'] = '1';
                    }
                    if(json['other_check'] == '1'){
                        jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                            var folder_name = this.innerHTML;
                            json['other_list'].push(folder_name);
                        });
                    }
                }
                else if(incremental_type === 'database'){
                    //database
                    json['database_check'] = '0';
                    json['exclude-tables'] = Array();
                    json['include-tables'] = Array();
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                        json['database_check'] = '1';
                    }
                    jQuery('#'+parent_id).find('input[option=base_db][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['exclude-tables'].push(jQuery(value).val());
                        }
                    });
                    jQuery('#'+parent_id).find('input[option=other_db][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['exclude-tables'].push(jQuery(value).val());
                        }
                    });
                    jQuery('#'+parent_id).find('input[option=diff_prefix_db][type=checkbox]').each(function(index, value){
                        if(jQuery(value).prop('checked')){
                            json['include-tables'].push(jQuery(value).val());
                        }
                    });

                    //additional database
                    json['additional_database_check'] = '0';
                    json['additional_database_list'] = {};
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                        json['additional_database_check'] = '1';
                    }
                    jQuery('#'+parent_id).find('.wpvivid-additional-database-list').find('div').each(function(index, value){
                        var database_name = jQuery(this).attr('database-name');
                        var database_host = jQuery(this).attr('database-host');
                        var database_user = jQuery(this).attr('database-user');
                        var database_pass = jQuery(this).attr('database-pass');
                        json['additional_database_list'][database_name] = {};
                        json['additional_database_list'][database_name]['db_host'] = database_host;
                        json['additional_database_list'][database_name]['db_user'] = database_user;
                        json['additional_database_list'][database_name]['db_pass'] = database_pass;
                    });
                }
                return json;
            }

            function wpvivid_recalc_backup_size_ex(website_item_arr, custom_option, parent_id, type, what, total_file_size)
            {
                if(website_item_arr.length > 0)
                {
                    console.log(website_item_arr);
                    if(type === 'incremental')
                    {
                        var website_item = website_item_arr.shift();
                        var ajax_data = {
                            'action': 'wpvivid_recalc_backup_size_ex',
                            'website_item': website_item,
                            'custom_option': custom_option,
                            'incremental': '1'
                        };
                    }
                    else
                    {
                        var website_item = website_item_arr.shift();
                        var ajax_data = {
                            'action': 'wpvivid_recalc_backup_size_ex',
                            'website_item': website_item,
                            'custom_option': custom_option
                        };
                    }

                    wpvivid_post_request_addon(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                if(website_item === 'database')
                                {
                                    jQuery('#'+parent_id).find('.wpvivid-database-size').html(jsonarray.database_size);
                                }
                                if(website_item === 'core')
                                {
                                    jQuery('#'+parent_id).find('.wpvivid-core-size').html(jsonarray.core_size);
                                }
                                if(website_item === 'content')
                                {
                                    jQuery('#'+parent_id).find('.wpvivid-content-size').html(jsonarray.content_size);
                                }
                                if(website_item === 'themes')
                                {
                                    jQuery('#'+parent_id).find('.wpvivid-themes-size').html(jsonarray.themes_size);
                                }
                                if(website_item === 'plugins')
                                {
                                    jQuery('#'+parent_id).find('.wpvivid-plugins-size').html(jsonarray.plugins_size);
                                }
                                if(website_item === 'uploads')
                                {
                                    jQuery('#'+parent_id).find('.wpvivid-uploads-size').html(jsonarray.uploads_size);
                                }
                                wpvivid_recalc_backup_size_ex(website_item_arr, custom_option, parent_id, type, what, jsonarray.total_file_size);
                            }
                            else
                            {
                                alert(jsonarray.error);
                                wpvivid_recalc_backup_size_ex(website_item_arr, custom_option, parent_id, type, what, total_file_size);
                            }
                        }
                        catch (err) {
                            alert(err);
                            wpvivid_recalc_backup_size_ex(website_item_arr, custom_option, parent_id, type, what, total_file_size);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        wpvivid_recalc_backup_size_ex(website_item_arr, custom_option, parent_id, type, what, total_file_size);
                    });
                }
                else
                {
                    if(what === 'database')
                    {
                        jQuery('#'+parent_id).find('.wpvivid-recalc-database-backup-size').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                    else if(what === 'file')
                    {
                        jQuery('#'+parent_id).find('.wpvivid-total-content-size').html(total_file_size);
                        jQuery('#'+parent_id).find('.wpvivid-recalc-file-backup-size').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }
            }

            function wpvivid_set_backup_history(backup_data)
            {
                var ajax_data = {
                    'action': 'wpvivid_set_backup_history',
                    'backup': backup_data
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            function wpvivid_get_website_size(website_item_arr)
            {
                if(website_item_arr.length > 0)
                {
                    console.log(website_item_arr);
                    var website_item = website_item_arr.shift();
                    var ajax_data = {
                        'action': 'wpvivid_get_website_size_ex',
                        'website_item': website_item
                    };
                    wpvivid_post_request_addon(ajax_data, function(data){
                        try{
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result == 'success')
                            {
                                if(website_item === 'database')
                                {
                                    jQuery('.wpvivid-database-size').html(jsonarray.database_size);
                                }
                                if(website_item === 'core')
                                {
                                    jQuery('.wpvivid-core-size').html(jsonarray.core_size);
                                }
                                if(website_item === 'content')
                                {
                                    jQuery('.wpvivid-content-size').html(jsonarray.content_size);
                                }
                                if(website_item === 'themes')
                                {
                                    jQuery('.wpvivid-themes-size').html(jsonarray.themes_size);
                                }
                                if(website_item === 'plugins')
                                {
                                    jQuery('.wpvivid-plugins-size').html(jsonarray.plugins_size);
                                }
                                if(website_item === 'uploads')
                                {
                                    jQuery('.wpvivid-uploads-size').html(jsonarray.uploads_size);
                                }
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('.wpvivid-additional-folder-size').html(jsonarray.additional_size);
                                    jQuery('.wpvivid-total-file-size').html(jsonarray.total_file_size);
                                    jQuery('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                                    jQuery('.wpvivid-total-content-size').html(jsonarray.total_file_size);
                                    jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_get_website_size(website_item_arr);
                            }
                            else {
                                alert(jsonarray.error);
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_get_website_size(website_item_arr);
                            }
                        }
                        catch (err) {
                            alert(err);
                            if(website_item === 'additional_folder')
                            {
                                jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                            wpvivid_get_website_size(website_item_arr);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        if(website_item === 'additional_folder')
                        {
                            jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                        wpvivid_get_website_size(website_item_arr);
                    });
                }
            }

            function wpvivid_get_website_all_size()
            {
                if('<?php echo $this->is_calc_website_size(); ?>')
                {
                    //var website_item_arr = new Array('database', 'core', 'content', 'themes', 'plugins', 'uploads', 'additional_folder');
                    //wpvivid_get_website_size(website_item_arr);
                }
            }

            jQuery(document).ready(function ()
            {
                if(!'<?php echo $this->is_calc_website_size(); ?>')
                {
                    jQuery('.wpvivid-size').hide();
                    jQuery('.wpvivid-size').prev().hide();
                    jQuery('.wpvivid-size').next().hide();
                }
            });
        </script>
        <?php
    }
}

