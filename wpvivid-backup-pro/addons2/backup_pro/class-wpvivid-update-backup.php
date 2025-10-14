<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_update_backup
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class WPvivid_update_backup
{
    public $already_backup_auto_update;

    public function __construct()
    {
        //auto backup actions
        /*

        add_action('wpvivid_before_setup_page',array($this,'auto_backup_page'));


        add_action('wpvivid_auto_backup_addon',array($this, 'auto_backup_setting'),20);
        //auto backup filters
        add_filter('wpvivid_set_general_setting', array($this, 'set_general_setting'), 12, 3);
        add_filter('wpvivid_export_setting_addon', array($this, 'export_setting_addon'), 11);
        //ajax
        add_action('wp_ajax_wpvivid_start_auto_backup',array( $this,'auto_backup'));
        add_action('wp_ajax_wpvivid_start_auto_backup_now',array( $this,'auto_backup_now'));
        add_action('wp_ajax_wpvivid_auto_backup_list_tasks',array( $this,'list_tasks'));
        add_action('wp_ajax_wpvivid_update_auto_option', array($this, 'wpvivid_update_auto_option'));
        add_action('wp_ajax_wpvivid_get_custom_auto_backup_database_tables_info', array($this, 'get_custom_auto_backup_database_tables_info'));
        //auto update

        */

        /*
        add_action('core_upgrade_preamble', array( $this,'core_auto_backup_check'),10);
        add_action('load-themes.php', array($this, 'load_themes'),99);
        add_action('pre_auto_update', array($this, 'pre_auto_update'), 10, 2);
        add_action('wpvivid_wp_maybe_auto_update', array($this, 'wpvivid_wp_maybe_auto_update'), 10, 2);
        add_action('pre_current_active_plugins', array($this, 'pre_current_active_plugins'));
        */
    }

    /***** auto backup useful function begin *****/
    function show_auto_backup_page($backup_type, $backup_to_remote)
    {
        if($backup_type=='core')
        {
            $this->output_core_form();
        }
        else if($backup_type=='plugin')
        {
            $this->output_plugins_form();
        }
        else if($backup_type=='themes')
        {
            $this->output_themes_form();
        }
        $title='Start backing up the following files and database';
        WPvivid_Setting::update_option('update_auto_backup_remote', $backup_to_remote);
        ?>
        <div style="padding-right: 20px;">
            <h2><?php echo $title?></h2>

            <?php
            $plugin_list=array();
            $themes_list=array();
            if($backup_type=='core')
            {
                $this->output_core_content();
            }
            else if($backup_type=='plugin')
            {
                $this->output_plugin_content();

                if(isset($_REQUEST['slug']))
                {
                    $plugin_list[]=$_REQUEST['slug'];
                }
                else
                {
                    $plugin_list=$_POST['checked'];
                }
            }
            else if($backup_type=='themes')
            {
                $this->output_themes_content();

                if(isset($_REQUEST['slug']))
                {
                    $themes_list[]=$_REQUEST['slug'];
                }
                else
                {
                    $themes_list=$_POST['checked'];
                }
            }

            ?>
            <h2>The update will start after the backup is finished</h2>
            <div class="postbox" id="wpvivid_postbox_backup_percent">
                <div class="action-progress-bar" id="wpvivid_action_progress_bar">
                    <div class="action-progress-bar-percent" id="wpvivid_action_progress_bar_percent" style="height:24px; width:0;"></div>
                </div>
                <div style="margin-left:10px; float: left; width:100%;"><p id="wpvivid_current_doing">Ready to backup</p></div>
                <div style="clear: both;"></div>
            </div>

        </div>
        <script>
            var m_need_update=true;
            var task_retry_times=0;
            var wpvivid_prepare_backup=false;
            var running_backup_taskid='';
            var auto_backup_retry_times=0;

            function wpvivid_manage_task_ex()
            {
                if(m_need_update === true)
                {
                    m_need_update = false;
                    wpvivid_check_runningtask_ex();
                }
                else{
                    setTimeout(function()
                    {
                        wpvivid_manage_task_ex();
                    }, 3000);
                }
            }

            function wpvivid_check_runningtask_ex()
            {
                var ajax_data = {
                    'action': 'wpvivid_auto_new_backup_list_tasks',
                    'task_id': running_backup_taskid
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    setTimeout(function ()
                    {
                        wpvivid_manage_task_ex();
                    }, 3000);

                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        var b_has_data = false;
                        if (jsonarray.backup.data.length !== 0)
                        {
                            b_has_data = true;
                            task_retry_times = 0;
                            if (jsonarray.backup.result === 'success')
                            {
                                wpvivid_prepare_backup = false;
                                jQuery.each(jsonarray.backup.data, function (index, value)
                                {
                                    if (value.status.str === 'ready')
                                    {
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = true;
                                    }
                                    else if (value.status.str === 'running')
                                    {
                                        running_backup_taskid = index;
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = true;
                                    }
                                    else if (value.status.str === 'wait_resume')
                                    {
                                        running_backup_taskid = index;
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        if (value.data.next_resume_time !== 'get next resume time failed.')
                                        {
                                            wpvivid_resume_backup(index, value.data.next_resume_time);
                                        }
                                        else
                                        {
                                            wpvivid_delete_backup_task(index);
                                        }
                                    }
                                    else if (value.status.str === 'no_responds')
                                    {
                                        running_backup_taskid = index;
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = true;
                                    }
                                    else if (value.status.str === 'completed')
                                    {
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = false;
                                        finish_wpvivid_auto_backup();
                                    }
                                    else if (value.status.str === 'error')
                                    {
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        running_backup_taskid = '';
                                        m_need_update = true;
                                        location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard'); ?>';
                                    }
                                });
                            }
                        }

                        if (!b_has_data)
                        {
                            task_retry_times++;
                            if (task_retry_times < 5)
                            {
                                m_need_update = true;
                            }
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function ()
                    {
                        m_need_update = true;
                        wpvivid_manage_task_ex();
                    }, 3000);
                });
            }

            function wpvivid_start_auto_backup()
            {
                var plugins_list=[];
                <?php
                foreach ($plugin_list as $plugin)
                {
                ?>plugins_list.push('<?php echo $plugin;?>'); <?php
                }
                ?>
                var themes_list=[];
                <?php
                foreach ($themes_list as $themes)
                {
                ?>themes_list.push('<?php echo $themes;?>'); <?php
                }
                ?>
                var ajax_data = {
                    'action': 'wpvivid_start_new_auto_backup',
                    'backup':'<?php echo $backup_type; ?>',
                    'plugins':plugins_list,
                    'themes':themes_list,
                    'backup_to_remote':'<?php esc_html_e($backup_to_remote); ?>'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);

                    if (jsonarray.result === 'success')
                    {
                        running_backup_taskid=jsonarray.task_id;
                        wpvivid_start_auto_backup_now(running_backup_taskid);
                        wpvivid_manage_task_ex();
                    }
                    else
                    {
                        auto_backup_retry_times++;
                        if(auto_backup_retry_times>3)
                        {
                            if(typeof jsonarray.error !== 'undefined')
                            {
                                alert(jsonarray.error);
                            }
                            else
                            {
                                alert('Backup failed');
                            }
                            location.href='<?php echo apply_filters('wpvivid_get_admin_url', '').'plugins.php'; ?>';
                        }
                        else
                        {
                            wpvivid_start_auto_backup();
                        }
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    auto_backup_retry_times++;
                    if(auto_backup_retry_times>3)
                    {
                        alert('Backup failed');
                    }
                    else
                    {
                        wpvivid_start_auto_backup();
                    }
                });
            }

            function wpvivid_start_auto_backup_now(task_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_start_new_auto_backup_now',
                    'task_id':task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            function finish_wpvivid_auto_backup()
            {
                jQuery('#upgrade').click();
            }

            jQuery(document).ready(function ()
            {
                wpvivid_start_auto_backup();
                //finish_wpvivid_auto_backup();
            });
        </script>
        <?php
    }

    public function load_themes()
    {
        /*
        $need_auto_backup = self::check_need_auto_backup();
        if($need_auto_backup) {
            add_action('admin_footer', array($this, 'admin_load_themes'));
        }*/

        add_action('admin_footer', array($this, 'admin_load_themes'));
    }

    public function pre_current_active_plugins()
    {
        /*
        if (!current_user_can('update_plugins'))
            return;
        $need_auto_backup = self::check_need_auto_backup();
        if($need_auto_backup) {
            add_action('admin_footer', array($this, 'admin_update_plugin'));
        }
        */

        add_action('admin_footer', array($this, 'admin_update_plugin'));
    }

    public function core_auto_backup_check()
    {
        if (is_multisite())
        {
            if(!is_network_admin())
            {
                return ;
            }
        }

        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);

        /*
        if(!function_exists('get_plugins'))
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
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
            if(count($plugins) == 0){
                $plugins=get_plugins();
            }
        }
        else
        {
            $active_plugins = get_option('active_plugins');
            $plugins=get_plugins();
        }

        $has_installed = false;
        $has_actived = false;

        if(isset($plugins) && !empty($plugins))
        {
            foreach ($plugins as $key => $plugin)
            {
                if($plugin['Name'] === 'WPvivid Backup Plugin')
                {
                    $has_installed = true;
                    if(in_array($key, $active_plugins))
                    {
                        $has_actived = true;
                    }
                    continue;
                }
            }
        }

        if($has_installed && $has_actived) {
            $auto_backup_before_update = get_option('wpvivid_auto_backup_before_update', array());

            if (empty($auto_backup_before_update)) {
                $auto_backup_enable = '1';
                $auto_backup_local = 'checked';
                $auto_backup_remote = '';
            }
            else {
                if (isset($auto_backup_before_update['auto_backup_enable'])) {
                    $auto_backup_enable = $auto_backup_before_update['auto_backup_enable'];
                    if (isset($auto_backup_before_update['auto_backup'])) {
                        if ($auto_backup_before_update['auto_backup'] === 'local') {
                            $auto_backup_local = 'checked';
                            $auto_backup_remote = '';
                        } else {
                            $auto_backup_local = '';
                            $auto_backup_remote = 'checked';
                        }
                    } else {
                        $auto_backup_local = 'checked';
                        $auto_backup_remote = '';
                    }
                } else {
                    $auto_backup_enable = '1';
                    $auto_backup_local = 'checked';
                    $auto_backup_remote = '';
                }
            }
            if ($auto_backup_enable == '1') {
                $auto_backup_enable = 'checked';
                $auto_backup_style = 'pointer-events: auto; opacity: 1;';
                $auto_backup_display = 'display:block;';
            } else {
                $auto_backup_enable = '';
                $auto_backup_style = 'pointer-events: none; opacity: 0.4;';
                $auto_backup_display = 'display:none;';
            }

            $has_default_remote = true;
            $remoteslist = WPvivid_Setting::get_all_remote_options();
            if (empty($remoteslist['remote_selected'])) {
                $has_default_remote = false;
            }
            $backupdir = WPvivid_Custom_Interface_addon::wpvivid_get_local_backupdir();
        <?php
         <div class="updated" style="<?php esc_attr_e($auto_backup_display); ?>">
            <h3><?php _e('Auto-backup before updating', 'wpvivid'); ?></h3>

            <div style="margin-bottom: 10px;">
                <label>
                    <input type="checkbox" id="wpvivid_backup_before_update_enable" <?php esc_attr_e($auto_backup_enable) ?> />
                    <?php echo sprintf(__('%s Pro will automatically back up your plugins, themes or core files before you update them. It will only back up the files you want to update.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid Backup')); ?>
                </label>
            </div>

            <div style="margin-bottom: 10px;">
                <label for="wpvivid_backup_before_update">
                    <input type="radio" id="wpvivid_backup_before_update" name="wpvivid_bfu" value="local" style="<?php esc_attr_e($auto_backup_style); ?>" <?php esc_attr_e($auto_backup_local); ?> />
                    Save the backup to localhost: <?php _e(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $backupdir); ?>
                </label>
            </div>
            <?php
            if ($has_default_remote) {
                $style = 'pointer-events: auto; opacity: 1';
                $descript = '';
            } else {
                $style = 'pointer-events: none; opacity: 0.4;';
                $descript = '(Warning: There is no default remote storage configured. Please set it up first.)';
            }
            ?>
            <div style="margin-bottom: 10px; <?php esc_attr_e($style); ?>">
                <label for="wpvivid_backup_to_remote">
                    <input type="radio" id="wpvivid_backup_to_remote" name="wpvivid_bfu" value="remote" style="<?php esc_attr_e($auto_backup_style); ?>" <?php esc_attr_e($auto_backup_remote); ?> />
                    Send the backup to remote storage: rollback folder under the custom directory <?php _e($descript); ?>
                </label>
            </div>
        </div>
         <script>
            function wpvivid_update_auto_option()
            {
                var ajax_data = {
                    'action': 'wpvivid_update_auto_option',
                    'auto': '0'
                };
                ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
                jQuery.ajax({
                    type: "post",
                    url: wpvivid_ajax_object_addon.ajax_url,
                    data: ajax_data
                });
            }

            jQuery(document).ready(function ($) {
                var wpvivid_auto_backup_before_update = '<?php
                    if($auto_backup_enable === 'checked'){
                        $auto_backup_enable = '1';
                    }
                    else{
                        $auto_backup_enable = '0';
                    }
                    _e($auto_backup_enable);
                    ?>';
                jQuery('#wpvivid_backup_before_update_enable').click(function(){
                    if(jQuery('#wpvivid_backup_before_update_enable').prop('checked')){
                        jQuery('#wpvivid_backup_before_update').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_backup_to_remote').css({'pointer-events': 'auto', 'opacity': '1'});
                        wpvivid_auto_backup_before_update = '1';
                    }
                    else{
                        jQuery('#wpvivid_backup_before_update').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#wpvivid_backup_to_remote').css({'pointer-events': 'none', 'opacity': '0.4'});
                        wpvivid_auto_backup_before_update = '0';
                    }
                });
                $('form.upgrade[name="upgrade"]').submit(function () {
                    if(wpvivid_auto_backup_before_update === '1') {
                        jQuery('input:radio[name=wpvivid_bfu]').each(function () {
                            var backup_to_remote = 0;
                            if (jQuery(this).prop('checked')) {
                                if (jQuery(this).val() === 'local') {
                                    backup_to_remote = 0;
                                }
                                else if (jQuery(this).val() === 'remote') {
                                    backup_to_remote = 1;
                                }
                                $('form.upgrade[name="upgrade"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=core&backup_to_remote=' + backup_to_remote);
                            }
                        });
                    }
                    else{
                        wpvivid_update_auto_option();
                    }
                });

                $('form.upgrade[name="upgrade-plugins"]').submit(function () {
                    var itemsSelected = $( '#update-plugins-table' ).find( 'input[name="checked[]"]:checked' );

                    var is_wpvivid_backup = false;
                    var select_count = 0;
                    itemsSelected.each( function( index, element )
                    {
                        var $checkbox = $( element ), $itemRow = $checkbox.parents( 'tr' );
                        if($checkbox.val() === 'wpvivid-backuprestore/wpvivid-backuprestore.php' || $checkbox.val() === 'wpvivid-backup-pro/wpvivid-backup-pro.php'){
                            is_wpvivid_backup = true;
                        }
                        select_count++;
                    } );
                    if(is_wpvivid_backup === true && select_count < 2 || select_count === 0){
                        return;
                    }
                    else {
                        if(wpvivid_auto_backup_before_update === '1') {
                            jQuery('input:radio[name=wpvivid_bfu]').each(function () {
                                var backup_to_remote = 0;
                                if (jQuery(this).prop('checked')) {
                                    if (jQuery(this).val() === 'local') {
                                        backup_to_remote = 0;
                                    }
                                    else if (jQuery(this).val() === 'remote') {
                                        backup_to_remote = 1;
                                    }
                                    $('form.upgrade[name="upgrade-plugins"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=plugin&backup_to_remote=' + backup_to_remote);
                                }
                            });
                        }
                        else{
                            wpvivid_update_auto_option();
                        }
                    }
                });

                $('form.upgrade[name="upgrade-themes"]').submit(function () {
                    if(wpvivid_auto_backup_before_update === '1') {
                        jQuery('input:radio[name=wpvivid_bfu]').each(function () {
                            var backup_to_remote = 0;
                            if (jQuery(this).prop('checked')) {
                                if (jQuery(this).val() === 'local') {
                                    backup_to_remote = 0;
                                }
                                else if (jQuery(this).val() === 'remote') {
                                    backup_to_remote = 1;
                                }
                                $('form.upgrade[name="upgrade-themes"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=themes&backup_to_remote=' + backup_to_remote);
                            }
                        });
                    }
                    else{
                        wpvivid_update_auto_option();
                    }
                });
            });
        </script>
        ?>
        }
        */
        $auto_backup_core=get_option('wpvivid_plugins_auto_backup_core',false);

        ?>

        <script>
            jQuery(document).ready(function ($)
            {
                <?php
                if($auto_backup_core)
                {
                ?>
                $('form.upgrade[name="upgrade"]').submit(function ()
                {
                    $('form.upgrade[name="upgrade"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=core');
                });
                <?php
                }
                ?>

                <?php
                if($auto_backup_db_before_update)
                {
                    ?>
                    $('form.upgrade[name="upgrade-plugins"]').submit(function ()
                    {
                        $('form.upgrade[name="upgrade-plugins"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=plugins');
                    });

                    $('form.upgrade[name="upgrade-themes"]').submit(function ()
                    {
                        $('form.upgrade[name="upgrade-themes"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=themes');
                    });
                    <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public function admin_load_themes()
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if(!$auto_backup_db_before_update)
        {
            return;
        }
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($)
            {
                var wpvivid_updater = window.wp.updates;
                var wpvivid_backup_lock=false;
                jQuery(document).on('wp-theme-updating', function(e,args)
                {
                    wpvivid_update_plugins_test();
                });

                function wpvivid_update_plugins_test()
                {
                    if(wpvivid_backup_lock)
                    {
                        return;
                    }
                    wpvivid_updater.ajaxLocked=true;
                    wpvivid_backup_lock=true;
                    wpvivid_start_auto_backup();
                }

                var m_need_update=true;
                var task_retry_times=0;
                var wpvivid_prepare_backup=false;
                var running_backup_taskid='';
                var auto_backup_retry_times=0;

                function wpvivid_manage_task_ex()
                {
                    if(m_need_update === true)
                    {
                        m_need_update = false;
                        wpvivid_check_runningtask_ex();
                    }
                    else{
                        setTimeout(function()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);
                    }
                }

                function wpvivid_check_runningtask_ex()
                {
                    var ajax_data = {
                        'action': 'wpvivid_auto_new_backup_list_tasks',
                        'task_id': running_backup_taskid
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        setTimeout(function ()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);

                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);

                            var b_has_data = false;
                            if (jsonarray.backup.data.length !== 0)
                            {
                                b_has_data = true;
                                task_retry_times = 0;
                                if (jsonarray.backup.result === 'success')
                                {
                                    wpvivid_prepare_backup = false;
                                    jQuery.each(jsonarray.backup.data, function (index, value)
                                    {
                                        if (value.status.str === 'ready')
                                        {
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'running')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'wait_resume')
                                        {
                                            running_backup_taskid = index;
                                            if (value.data.next_resume_time !== 'get next resume time failed.')
                                            {
                                                wpvivid_resume_backup(index, value.data.next_resume_time);
                                            }
                                            else
                                            {
                                                wpvivid_delete_backup_task(index);
                                                finish_wpvivid_auto_backup();
                                            }
                                        }
                                        else if (value.status.str === 'no_responds')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'completed')
                                        {
                                            m_need_update = false;
                                            finish_wpvivid_auto_backup();
                                        }
                                        else if (value.status.str === 'error')
                                        {
                                            running_backup_taskid = '';
                                            m_need_update = true;
                                            finish_wpvivid_auto_backup();
                                        }
                                    });
                                }
                            }

                            if (!b_has_data)
                            {
                                task_retry_times++;
                                if (task_retry_times < 5)
                                {
                                    m_need_update = true;
                                }
                            }
                        }
                        catch(err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        setTimeout(function ()
                        {
                            m_need_update = true;
                            wpvivid_manage_task_ex();
                        }, 3000);
                    });
                }

                function wpvivid_start_auto_backup()
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup',
                        'backup':'db',
                        'backup_to_remote':0
                    };

                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            running_backup_taskid=jsonarray.task_id;
                            wpvivid_start_auto_backup_now(running_backup_taskid);
                            wpvivid_manage_task_ex();
                        }
                        else
                        {
                            auto_backup_retry_times++;
                            if(auto_backup_retry_times>3)
                            {
                                if(typeof jsonarray.error !== 'undefined')
                                {
                                    alert(jsonarray.error);
                                }
                                else
                                {
                                    alert('Backup failed');
                                }
                                location.href='<?php echo apply_filters('wpvivid_get_admin_url', '').'plugins.php'; ?>';
                            }
                            else
                            {
                                wpvivid_start_auto_backup();
                            }
                        }
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        auto_backup_retry_times++;
                        if(auto_backup_retry_times>3)
                        {
                            alert('Backup failed');
                        }
                        else
                        {
                            wpvivid_start_auto_backup();
                        }
                    });
                }

                function wpvivid_start_auto_backup_now(task_id)
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup_now',
                        'task_id':task_id
                    };
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                    });
                }

                function finish_wpvivid_auto_backup()
                {
                    wpvivid_updater.ajaxLocked=false;
                    wpvivid_updater.queueChecker();
                }
            });
        </script>
        <?php
    }

    public function admin_update_plugin()
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if(!$auto_backup_db_before_update)
        {
            return;
        }
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($)
            {
                var wpvivid_updater = window.wp.updates;
                var wpvivid_backup_lock=false;

                jQuery(document).on('wp-plugin-bulk-update-selected', function(event)
                {
                    // Find all the checkboxes which have been checked.
                    var is_wpvivid_backup=false;
                    var itemsSelected = $( '#bulk-action-form' ).find( 'input[name="checked[]"]:checked' );

                    var select_count = 0;
                    itemsSelected.each( function( index, element )
                    {
                        var $checkbox = $( element ),
                            $itemRow = $checkbox.parents( 'tr' );
                        // Only add update-able items to the update queue.
                        if ( ! $itemRow.hasClass( 'update' ) || $itemRow.find( 'notice-error' ).length)
                        {
                            // Un-check the box.
                            $checkbox.prop( 'checked', false );
                            return;
                        }

                        if($itemRow.data( 'plugin' ) === 'wpvivid-backuprestore/wpvivid-backuprestore.php' || $itemRow.data( 'plugin' ) === 'wpvivid-backup-pro/wpvivid-backup-pro.php')
                        {
                            is_wpvivid_backup = true;
                        }
                        select_count++;
                    } );

                    if(is_wpvivid_backup === true && select_count < 2)
                    {
                        return;
                    }
                    else
                    {
                        wpvivid_update_plugins_test();
                    }
                });

                $('tr.plugin-update-tr ').on('click', 'a', function(event)
                {
                    if($(this).hasClass('update-link'))
                    {
                        wpvivid_update_plugins_test();
                    }
                });

                function wpvivid_update_plugins_test()
                {
                    if(wpvivid_backup_lock)
                    {
                        return;
                    }
                    wpvivid_updater.ajaxLocked=true;
                    wpvivid_backup_lock=true;
                    wpvivid_start_auto_backup();
                }

                var m_need_update=true;
                var task_retry_times=0;
                var wpvivid_prepare_backup=false;
                var running_backup_taskid='';
                var auto_backup_retry_times=0;

                function wpvivid_manage_task_ex()
                {
                    if(m_need_update === true)
                    {
                        m_need_update = false;
                        wpvivid_check_runningtask_ex();
                    }
                    else{
                        setTimeout(function()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);
                    }
                }

                function wpvivid_check_runningtask_ex()
                {
                    var ajax_data = {
                        'action': 'wpvivid_auto_new_backup_list_tasks',
                        'task_id': running_backup_taskid
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        setTimeout(function ()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);

                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);

                            var b_has_data = false;
                            if (jsonarray.backup.data.length !== 0)
                            {
                                b_has_data = true;
                                task_retry_times = 0;
                                if (jsonarray.backup.result === 'success')
                                {
                                    wpvivid_prepare_backup = false;
                                    jQuery.each(jsonarray.backup.data, function (index, value)
                                    {
                                        if (value.status.str === 'ready')
                                        {
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'running')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'wait_resume')
                                        {
                                            running_backup_taskid = index;
                                            if (value.data.next_resume_time !== 'get next resume time failed.')
                                            {
                                                wpvivid_resume_backup(index, value.data.next_resume_time);
                                            }
                                            else
                                            {
                                                wpvivid_delete_backup_task(index);
                                                finish_wpvivid_auto_backup();
                                            }
                                        }
                                        else if (value.status.str === 'no_responds')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'completed')
                                        {
                                            m_need_update = false;
                                            finish_wpvivid_auto_backup();
                                        }
                                        else if (value.status.str === 'error')
                                        {
                                            running_backup_taskid = '';
                                            m_need_update = true;
                                            finish_wpvivid_auto_backup();
                                        }
                                    });
                                }
                            }

                            if (!b_has_data)
                            {
                                task_retry_times++;
                                if (task_retry_times < 5)
                                {
                                    m_need_update = true;
                                }
                            }
                        }
                        catch(err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        setTimeout(function ()
                        {
                            m_need_update = true;
                            wpvivid_manage_task_ex();
                        }, 3000);
                    });
                }

                function wpvivid_start_auto_backup()
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup',
                        'backup':'db',
                        'backup_to_remote':0
                    };

                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            running_backup_taskid=jsonarray.task_id;
                            wpvivid_start_auto_backup_now(running_backup_taskid);
                            wpvivid_manage_task_ex();
                        }
                        else
                        {
                            auto_backup_retry_times++;
                            if(auto_backup_retry_times>3)
                            {
                                if(typeof jsonarray.error !== 'undefined')
                                {
                                    alert(jsonarray.error);
                                }
                                else
                                {
                                    alert('Backup failed');
                                }
                                location.href='<?php echo apply_filters('wpvivid_get_admin_url', '').'plugins.php'; ?>';
                            }
                            else
                            {
                                wpvivid_start_auto_backup();
                            }
                        }
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        auto_backup_retry_times++;
                        if(auto_backup_retry_times>3)
                        {
                            alert('Backup failed');
                        }
                        else
                        {
                            wpvivid_start_auto_backup();
                        }
                    });
                }

                function wpvivid_start_auto_backup_now(task_id)
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup_now',
                        'task_id':task_id
                    };
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                    });
                }

                function finish_wpvivid_auto_backup()
                {
                    wpvivid_updater.ajaxLocked=false;
                    wpvivid_updater.queueChecker();
                }

            });


        </script>
        <?php
    }

    static public function check_need_auto_backup(){
        $auto_backup_before_update = get_option('wpvivid_auto_backup_before_update', array());
        $need_auto_backup = true;
        if (empty($auto_backup_before_update)) {
            $need_auto_backup = true;
        }
        else {
            if(!isset($auto_backup_before_update['auto_backup_enable'])){
                $need_auto_backup = true;
            }
            else{
                $need_auto_backup = $auto_backup_before_update['auto_backup_enable']=='1' ? true : false;
            }
        }
        return $need_auto_backup;
    }

    public function output_core_form()
    {
        $updates    = get_core_updates();
        foreach ( (array) $updates as $update )
        {
            $submit        = __( 'Update Now' );
            $current = false;
            if ( ! isset( $update->response ) || 'latest' == $update->response ) {
                $current = true;
            }
            if ( $current )
            {
                $form_action = 'update-core.php?action=do-core-reinstall';
            }
            else
            {
                $form_action   = 'update-core.php?action=do-core-upgrade';
            }

            //action=do-core-reinstall
            echo '<li style="display: none">';
            echo '<form method="post" action="' . $form_action . '" name="upgrade" class="upgrade">';
            wp_nonce_field( 'upgrade-core' );
            $name        = esc_attr( '_wpnonce' );
            echo '<input type="hidden" id="'.$name.'" name="'.$name.'" value="' . wp_create_nonce( 'upgrade-core' ) . '" />';
            $url=apply_filters('wpvivid_get_admin_url', '').'update-core.php';
            echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( wp_unslash( $url ) ) . '" />';
            echo '<p>';
            echo '<input name="version" value="' . esc_attr( $update->current ) . '" />';
            echo '<input name="locale" value="' . esc_attr( $update->locale ) . '" />';
            submit_button( $submit, '', 'upgrade', false );
            echo '</p>';
            echo '</form>';
            echo '</li>';
        }
    }

    public function output_plugins_form()
    {
        if(isset($_REQUEST['slug']))
        {
            $plugin_list[]=$_REQUEST['slug'];
        }
        else
        {
            $plugin_list=$_POST['checked'];
        }
        $form_action = 'update-core.php?action=do-plugin-upgrade';
        ?>
        <form method="post" action="<?php echo esc_url( $form_action ); ?>" name="upgrade-plugins" class="upgrade" style="display: none">
            <?php wp_nonce_field( 'upgrade-core' ); ?>
            <p><input id="upgrade" class="button" type="submit" value="<?php esc_attr_e( 'Update Plugins' ); ?>" name="upgrade" /></p>
            <table class="widefat updates-table" id="update-plugins-table">
                <tbody class="plugins">
                <?php
                foreach ( $plugin_list as $plugin_slug )
                {
                    // Get plugin compat for running version of WordPress.
                    ?>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" name="checked[]" value="<?php echo $plugin_slug; ?>" checked/>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </form>
        <?php
    }

    public function output_themes_form()
    {
        if(isset($_REQUEST['slug']))
        {
            $themes_list[]=$_REQUEST['slug'];
        }
        else
        {
            $themes_list=$_POST['checked'];
        }
        $form_action = 'update-core.php?action=do-theme-upgrade';
        ?>
        <form method="post" action="<?php echo esc_url( $form_action ); ?>" name="upgrade-themes" class="upgrade" style="display: none">
            <?php wp_nonce_field( 'upgrade-core' ); ?>
            <p><input id="upgrade" class="button" type="submit" value="<?php esc_attr_e( 'Update Themes' ); ?>" name="upgrade" /></p>
            <table class="widefat updates-table" id="update-thems-table">
                <tbody class="plugins">
                <?php
                foreach ( $themes_list as $themes )
                {
                    // Get plugin compat for running version of WordPress.
                    ?>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" name="checked[]" value="<?php echo $themes; ?>" checked/>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </form>
        <?php
    }

    public function output_core_content()
    {
        ?>
        <div class="postbox">
            <table class="widefat updates-table">
                <thead>
                <tr>
                    <td class="manage-column">
                        <span>backup content</span>
                    </td>
                </tr>
                </thead>
                <tbody class="plugins">
                <tr>
                    <td class="plugin-title">
                        <p>
                            <strong>Wordpress core files</strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title">
                        <p>
                            <strong>Database</strong>
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function output_plugin_content()
    {
        $checked_plugin_list=array();
        if(isset($_REQUEST['slug']))
        {
            $checked_plugin_list[$_REQUEST['slug']]=$_REQUEST['slug'];
        }
        else
        {
            foreach ($_POST['checked'] as $plug_slug)
            {
                $checked_plugin_list[$plug_slug]=$plug_slug;
            }
        }

        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        $plugins = get_plugin_updates();
        ?>
        <table class="widefat updates-table">
            <tbody class="plugins">
            <?php
            foreach ( (array) $plugins as $plugin_file => $plugin_data )
            {
                if(!array_key_exists($plugin_file,$checked_plugin_list))
                {
                    continue;
                }
                $plugin_data = (object) _get_plugin_data_markup_translate( $plugin_file, (array) $plugin_data, false, true );

                $icon = '<span class="dashicons dashicons-admin-plugins"></span>';
                $preferred_icons = array( 'svg', '2x', '1x', 'default' );
                foreach ( $preferred_icons as $preferred_icon ) {
                    if ( ! empty( $plugin_data->update->icons[ $preferred_icon ] ) ) {
                        $icon = '<img src="' . esc_url( $plugin_data->update->icons[ $preferred_icon ] ) . '" alt="" />';
                        break;
                    }
                }

                // Get the upgrade notice for the new plugin version.
                if ( isset( $plugin_data->update->upgrade_notice ) ) {
                    $upgrade_notice = '<br />' . strip_tags( $plugin_data->update->upgrade_notice );
                } else {
                    $upgrade_notice = '';
                }

                $details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_data->update->slug . '&section=changelog&TB_iframe=true&width=640&height=662' );
                ?>
                <tr>
                    <td class="plugin-title"><p>
                            <?php echo $icon; ?>
                            <strong><?php echo $plugin_data->Name; ?></strong>
                            <?php
                            /* translators: 1: plugin version, 2: new version */
                            printf(
                                __( 'You have version %1$s installed. Update to %2$s.' ),
                                $plugin_data->Version,
                                $plugin_data->update->new_version
                            );
                            echo ' ' . $upgrade_notice;
                            ?>
                        </p></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    public function output_themes_content()
    {
        $checked_theme_list=array();

        if(isset($_REQUEST['slug']))
        {
            $checked_theme_list[$_REQUEST['slug']]=$_REQUEST['slug'];
        }
        else
        {
            foreach ($_POST['checked'] as $plug_slug)
            {
                $checked_theme_list[$plug_slug]=$plug_slug;
            }
        }

        $themes = get_theme_updates();

        ?>
        <table class="widefat updates-table">
            <tbody class="plugins">
            <?php
            foreach ( $themes as $stylesheet => $theme )
            {
                if(!array_key_exists($stylesheet,$checked_theme_list))
                {
                    continue;
                }

                ?>
                <tr>
                    <td class="plugin-title"><p>
                            <img src="<?php echo esc_url( $theme->get_screenshot() ); ?>" width="85" height="64" class="updates-table-screenshot" alt="" />
                            <strong><?php echo $theme->display( 'Name' ); ?></strong>
                            <?php
                            /* translators: 1: theme version, 2: new version */
                            printf(
                                __( 'You have version %1$s installed. Update to %2$s.' ),
                                $theme->display( 'Version' ),
                                $theme->update['new_version']
                            );
                            ?>
                        </p></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    public function _list_tasks($task_id)
    {
        $ret=array();
        $list_tasks=array();
        $task=WPvivid_taskmanager::get_task($task_id);
        if($task!==false)
        {
            if($task['action']=='backup' || $task['action']=='backup_remote')
            {
                $backup=new WPvivid_Backup_Task($task['id']);
                $list_tasks[$task['id']]=$backup->get_backup_task_info($task['id']);

                $list_tasks[$task['id']]['progress_html'] = '<div class="action-progress-bar" id="wpvivid_action_progress_bar">
                                                <div class="action-progress-bar-percent" id="wpvivid_action_progress_bar_percent" style="height:24px;width:' . $list_tasks[$task['id']]['task_info']['backup_percent'] . '"></div>
                                             </div>                                                    
                                             <div style="margin-left:10px; float: left; width:100%;"><p id="wpvivid_current_doing">' . $list_tasks[$task['id']]['task_info']['descript'] . '</p></div>
                                             <div style="clear: both;"></div>';
            }
        }

        $ret['backup']['result']='success';
        $ret['backup']['data']=$list_tasks;
        return $ret;
    }
    /***** auto backup useful function end *****/

    /***** auto backup actions begin *****/

    public function auto_backup_page()
    {
        if(isset($_REQUEST['auto_backup'])&&$_REQUEST['auto_backup']==1)
        {
            $backup='';
            if(isset($_REQUEST['backup']))
            {
                $backup=$_REQUEST['backup'];
            }

            if(empty($backup))
            {
                echo 'Failed to retrieve the content for the backup, please try again.';
                die();
            }

            if(isset($_REQUEST['backup_to_remote']))
            {
                $backup_to_remote = $_REQUEST['backup_to_remote'];
            }
            else {
                $option = get_option('wpvivid_auto_backup_before_update',array());
                if(empty($option))
                {
                    $backup_to_remote = 0;
                }
                else
                {
                    if($option['auto_backup'] === 'remote')
                    {
                        $backup_to_remote = 1;
                    }
                    else
                    {
                        $backup_to_remote=0;
                    }
                }
            }
            $option['auto_backup_enable'] = 1;
            if($backup_to_remote == 1){
                $option['auto_backup'] = 'remote';
            }
            else{
                $option['auto_backup'] = 'local';
            }
            //$option['auto_backup']=1;
            //$option['backup_to_remote']=$backup_to_remote;
            WPvivid_Setting::update_option('wpvivid_auto_backup_before_update', $option);
            $this->show_auto_backup_page($backup, $backup_to_remote);
        }
    }

    public function auto_backup_setting()
    {
        $auto_backup_before_update = get_option('wpvivid_auto_backup_before_update',array());

        if(empty($auto_backup_before_update))
        {
            $auto_backup_enable = '1';
            $auto_backup_local = 'checked';
            $auto_backup_remote = '';
        }
        else
        {
            if(isset($auto_backup_before_update['auto_backup_enable'])){
                $auto_backup_enable = $auto_backup_before_update['auto_backup_enable'];
                if(isset($auto_backup_before_update['auto_backup'])){
                    if($auto_backup_before_update['auto_backup'] === 'local'){
                        $auto_backup_local = 'checked';
                        $auto_backup_remote = '';
                    }
                    else{
                        $auto_backup_local = '';
                        $auto_backup_remote = 'checked';
                    }
                }
                else{
                    $auto_backup_local = 'checked';
                    $auto_backup_remote = '';
                }
            }
            else{
                $auto_backup_enable = '1';
                $auto_backup_local = 'checked';
                $auto_backup_remote = '';
            }
        }
        if($auto_backup_enable == '1'){
            $auto_backup_enable = 'checked';
            $auto_backup_style = 'pointer-events: auto; opacity: 1;';
        }
        else{
            $auto_backup_enable = '';
            $auto_backup_style = 'pointer-events: none; opacity: 0.4;';
        }

        $has_default_remote = true;
        $remoteslist=WPvivid_Setting::get_all_remote_options();
        if(empty($remoteslist['remote_selected']))
        {
            $has_default_remote = false;
        }
        $descript = 'Send the backup to remote storage: rollback folder under the custom directory';

        ?>
        <tr>
            <td class="row-title" style="min-width:200px;"><label for="tablecell">Auto-backup before updating</label></td>
            <td>
                <p>
                    <label class="wpvivid-checkbox">
                        <span><?php echo sprintf(__('%s Pro will automatically back up your plugins, themes or core files before you update them. It will only back up the files you want to update.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid Backup')); ?></span>
                        <input type="checkbox" option="setting" name="auto_backup_enable" <?php esc_attr_e($auto_backup_enable) ?>>
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                </p>
                <p></p>
                <div id="wpvivid_auto_backup_block" style="padding-left:2em; <?php esc_attr_e($auto_backup_style); ?>">
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="setting" name="auto_backup" value="local" <?php esc_attr_e($auto_backup_local); ?> />Save the backup to localhost: <code><?php echo WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath(); ?></code>
                            <span class="wpvivid-radio-checkmark"></span>
                        </label>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">Send the backup to cloud storage: <code>rollback</code> folder under the custom directory
                            <input type="radio" option="setting"  name="auto_backup" value="remote" <?php esc_attr_e($auto_backup_remote); ?> />
                            <span class="wpvivid-radio-checkmark"></span>
                        </label>
                    </fieldset>
                </div>
                <p></p>
                <div id="wpvivid_custom_auto_backup">
                    <div>
                        <span>Select database table(s) you want to include in the auto-backup before updating.</span>
                        <span class="dashicons wpvivid-dashicons-grey wpvivid-handle-auto-backup-database-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                    </div>
                    <div class="wpvivid-custom-database-info wpvivid-auto-backup-database-detail" style="display: none;">
                        <div class="spinner is-active wpvivid-database-loading" style="margin: 0 5px 10px 0; float: left;"></div>
                        <div style="float: left;">Archieving database tables</div>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="clear:both;"></div>
                </div>
            </td>
        </tr>
        <script>
            var wpvivid_auto_backup_table = wpvivid_auto_backup_table || {};
            wpvivid_auto_backup_table.init_refresh = false;
            wpvivid_auto_backup_table.db_retry = false;
            wpvivid_auto_backup_table.is_get = false;

            jQuery('input:checkbox[option=setting][name=auto_backup_enable]').click(function(){
                if(jQuery(this).prop('checked')){
                    jQuery('#wpvivid_auto_backup_block').css({'pointer-events': 'auto', 'opacity': '1'});
                }
                else{
                    jQuery('#wpvivid_auto_backup_block').css({'pointer-events': 'none', 'opacity': '0.4'});
                }
            });

            jQuery('input:radio[option=setting][name=auto_backup]').click(function(){
                if(jQuery(this).val() === 'remote') {
                    if (typeof has_remote !== 'undefined') {
                        if (!has_remote) {
                            var descript = 'There is no default remote storage configured. Please set it up first.';
                            var ret = confirm(descript);
                            if (ret === true) {
                                wpvivid_click_switch_page('wrap', 'wpvivid_tab_remote_storage', true);
                            }
                            jQuery('input:radio[option=setting][name=auto_backup][value=local]').prop('checked', true);
                        }
                    }
                }
            });

            function wpvivid_handle_custom_open_close_ex(handle_obj, obj){
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

            jQuery('#wpvivid_custom_auto_backup').on('click', '.wpvivid-database-table-check', function()
            {
                if(jQuery(this).prop('checked')){
                    if(jQuery(this).hasClass('wpvivid-database-base-table-check')){
                        jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=base_db][name=auto_backup_database]').prop('checked', true);
                    }
                    else if(jQuery(this).hasClass('wpvivid-database-other-table-check')){
                        jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=other_db][name=auto_backup_database]').prop('checked', true);
                    }
                    else if(jQuery(this).hasClass('wpvivid-database-diff-prefix-table-check')){
                        jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=diff_prefix_db][name=auto_backup_database]').prop('checked', true);
                    }
                }
                else{
                    if (jQuery(this).hasClass('wpvivid-database-base-table-check')) {
                        jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=base_db][name=auto_backup_database]').prop('checked', false);
                    }
                    else if (jQuery(this).hasClass('wpvivid-database-other-table-check')) {
                        jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=other_db][name=auto_backup_database]').prop('checked', false);
                    }
                    else if (jQuery(this).hasClass('wpvivid-database-diff-prefix-table-check')) {
                        jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=diff_prefix_db][name=auto_backup_database]').prop('checked', false);
                    }
                }
            });

            jQuery('#wpvivid_custom_auto_backup').on("click", 'input:checkbox[option=base_db][name=auto_backup_database]', function()
            {
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=base_db][name=auto_backup_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-database-base-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-database-base-table-check').prop('checked', false);
                }
            });

            jQuery('#wpvivid_custom_auto_backup').on("click", 'input:checkbox[option=other_db][name=auto_backup_database]', function()
            {
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=other_db][name=auto_backup_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-database-other-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-database-other-table-check').prop('checked', false);
                }
            });

            jQuery('#wpvivid_custom_auto_backup').on("click", 'input:checkbox[option=diff_prefix_db][name=auto_backup_database]', function()
            {
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#wpvivid_custom_auto_backup').find('input:checkbox[option=diff_prefix_db][name=auto_backup_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-database-diff-prefix-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-database-diff-prefix-table-check').prop('checked', false);
                }
            });

            /*jQuery('#wpvivid_custom_auto_backup').on('click', '.wpvivid-select-base-table-button', function(){
                var text = jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-select-base-table-text').val();
                wpvivid_get_filter_database_list('base_table', text, 'auto_backup', 'wpvivid_custom_auto_backup');
            });

            jQuery('#wpvivid_custom_auto_backup').on('click', '.wpvivid-select-other-table-button', function(){
                var text = jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-select-other-table-text').val();
                wpvivid_get_filter_database_list('other_table', text, 'auto_backup', 'wpvivid_custom_auto_backup');
            });

            jQuery('#wpvivid_custom_auto_backup').on('click', '.wpvivid-select-diff-prefix-table-button', function(){
                var text = jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-select-diff-prefix-table-text').val();
                wpvivid_get_filter_database_list('diff_prefix_table', text, 'auto_backup', 'wpvivid_custom_auto_backup');
            });*/

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

            jQuery('#wpvivid_custom_auto_backup').on('click', '.wpvivid-select-base-table-button', function(){
                var text = jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-select-base-table-text').val();
                wpvivid_get_filter_database_list('base_table', text, 'auto_backup', 'wpvivid_custom_auto_backup');
            });

            jQuery('#wpvivid_custom_auto_backup').on('click', '.wpvivid-select-other-table-button', function(){
                var text = jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-select-other-table-text').val();
                wpvivid_get_filter_database_list('other_table', text, 'auto_backup', 'wpvivid_custom_auto_backup');
            });

            jQuery('#wpvivid_custom_auto_backup').on('click', '.wpvivid-select-diff-prefix-table-button', function(){
                var text = jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-select-diff-prefix-table-text').val();
                wpvivid_get_filter_database_list('diff_prefix_table', text, 'auto_backup', 'wpvivid_custom_auto_backup');
            });

            function wpvivid_refresh_auto_backup_database_table() {
                wpvivid_auto_backup_table.db_retry = 0;
                var custom_database_loading = '<div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>' +
                    '<div style="float: left;">Archieving database tables</div>' +
                    '<div style="clear: both;"></div>';
                jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-custom-database-info').html(custom_database_loading);
                wpvivid_get_custom_auto_backup_info();
            }

            function wpvivid_get_custom_auto_backup_info() {
                var ajax_data = {
                    'action': 'wpvivid_get_custom_auto_backup_database_tables_info'
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        wpvivid_auto_backup_table.is_get = true;
                        jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-custom-database-info').html('');
                        jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-custom-database-info').html(jsonarray.html);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var need_retry_custom_database = false;
                    wpvivid_auto_backup_table.db_retry++;
                    if(wpvivid_auto_backup_table.db_retry < 3){
                        need_retry_custom_database = true;
                    }
                    if(need_retry_custom_database) {
                        setTimeout(function(){
                            wpvivid_get_custom_auto_backup_info(parent_id);
                        }, 3000);
                    }
                    else{
                        var refresh_btn = '<input type="submit" class="button-primary" value="Refresh" onclick="wpvivid_refresh_auto_backup_database_table();">';
                        jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-custom-database-info').html('');
                        jQuery('#wpvivid_custom_auto_backup').find('.wpvivid-custom-database-info').html(refresh_btn);
                    }
                });
            }

            jQuery('.wpvivid-handle-auto-backup-database-detail').click(function(){
                var handle_obj = jQuery('.wpvivid-handle-auto-backup-database-detail');
                var obj = jQuery('.wpvivid-auto-backup-database-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj);
                var parent_id = 'wpvivid_custom_auto_backup';
                var type = 'auto_backup';
                if(!wpvivid_auto_backup_table.init_refresh)
                {
                    wpvivid_auto_backup_table.init_refresh=true;
                    wpvivid_get_custom_auto_backup_info();
                }
            });
        </script>
        <?php
    }
    /***** auto backup actions end *****/

    /***** auto backup filters begin *****/
    public function set_general_setting($setting_data, $setting, $options)
    {
        $setting['auto_backup_enable'] = intval($setting['auto_backup_enable']);
        $setting_data['wpvivid_auto_backup_before_update']['auto_backup_enable'] = $setting['auto_backup_enable'];
        $setting_data['wpvivid_auto_backup_before_update']['auto_backup'] = $setting['auto_backup'];

        $default = array();
        $auto_backup = get_option('wpvivid_auto_backup_before_update', $default);
        if(isset($setting['exclude-tables'])){
            $setting_data['wpvivid_auto_backup_before_update']['exclude-tables'] = $setting['exclude-tables'];
        }
        else{
            if(isset($auto_backup['exclude-tables'])){
                $setting_data['wpvivid_auto_backup_before_update']['exclude-tables'] = $auto_backup['exclude-tables'];
            }
            else{
                $setting_data['wpvivid_auto_backup_before_update']['exclude-tables'] = array();
            }
        }

        if(isset($setting['include-tables'])){
            $setting_data['wpvivid_auto_backup_before_update']['include-tables'] = $setting['include-tables'];
        }
        else{
            if(isset($auto_backup['include-tables'])){
                $setting_data['wpvivid_auto_backup_before_update']['include-tables'] = $auto_backup['include-tables'];
            }
            else{
                $setting_data['wpvivid_auto_backup_before_update']['include-tables'] = array();
            }
        }

        return $setting_data;
    }

    public function export_setting_addon($json)
    {
        $default = array();
        $auto_backup = get_option('wpvivid_auto_backup_before_update', $default);
        $json['data']['wpvivid_auto_backup_before_update'] = $auto_backup;
        return $json;
    }
    /***** auto backup filters end *****/

    /***** auto backup ajax begin *****/
    public function auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            $backup_options = array();
            if (is_null($backup_options))
            {
                die();
            }

            //select backup content
            $backup_options['backup_select']['other'] = 0;
            $backup_options['exclude_tables'] = array();
            $backup_options['include_plugins'] = array();
            $backup_options['include_themes'] = array();
            $backup_options['exclude_uploads'] = array();
            $backup_options['exclude_uploads_files'] = array();
            $backup_options['exclude_content'] = array();
            $backup_options['exclude_content_files'] = array();
            $backup_options['exclude_custom_other'] = array();
            $backup_options['exclude_custom_other_files'] = array();

            if ($_POST['backup'] == 'core') {
                $backup_options['backup_select']['core'] = 1;
                $backup_options['backup_select']['db'] = 1;

                $backup_options['backup_select']['themes'] = 0;
                $backup_options['backup_select']['plugin'] = 0;
                $backup_options['backup_select']['uploads'] = 0;
                $backup_options['backup_select']['content'] = 0;
                $backup_options['backup_select']['additional_db'] = 0;
            } else if ($_POST['backup'] == 'plugin') {
                foreach ($_POST['plugins'] as $plugin) {
                    $backup_options['include_plugins'][] = dirname($plugin);
                }

                if(count($backup_options['include_plugins']) === 1){
                    $backup_options['backup_prefix'] = current($backup_options['include_plugins']);
                }

                $backup_options['backup_select']['core'] = 0;
                $backup_options['backup_select']['db'] = 1;

                $backup_options['backup_select']['themes'] = 0;
                $backup_options['backup_select']['plugin'] = 1;
                $backup_options['backup_select']['uploads'] = 0;
                $backup_options['backup_select']['content'] = 0;
                $backup_options['backup_select']['additional_db'] = 0;
            } else if ($_POST['backup'] == 'themes') {
                foreach ($_POST['themes'] as $themes) {
                    $backup_options['include_themes'][] = $themes;
                }

                $backup_options['backup_select']['core'] = 0;
                $backup_options['backup_select']['db'] = 1;

                $backup_options['backup_select']['themes'] = 1;
                $backup_options['backup_select']['plugin'] = 0;
                $backup_options['backup_select']['uploads'] = 0;
                $backup_options['backup_select']['content'] = 0;
                $backup_options['backup_select']['additional_db'] = 0;
            }

            $backup_options['ismerge'] = '1';
            $backup_options['lock'] = '0';
            if ($_POST['backup_to_remote'] == 1) {
                $backup_type = 'Rollback';
                $action = 'backup_remote';
                $backup_options['backup_to'] = 'rollback_remote';
                $backup_options['local'] = 0;
                $backup_options['remote'] = 1;
            } else {
                $backup_type = 'Rollback';
                $action = 'backup';
                $backup_options['backup_to'] = 'local';
                $backup_options['local'] = 1;
                $backup_options['remote'] = 0;
            }

            $backup = new WPvivid_Backup_Task();
            $ret = $backup->new_backup_task($backup_options, $backup_type, $action);

            echo json_encode($ret);
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
        $wpvivid_backup_pro->ajax_check_security();
        try {
            if (!isset($_POST['task_id']) || empty($_POST['task_id']) || !is_string($_POST['task_id'])) {
                $ret['result'] = 'failed';
                $ret['error'] = __('Error occurred while parsing the request data. Please try to run backup again.', 'wpvivid');
                echo json_encode($ret);
                die();
            }
            $task_id = sanitize_key($_POST['task_id']);
            global $wpvivid_plugin;
            global $wpvivid_backup_pro;
            $wpvivid_backup_pro->func->flush($task_id);
            $wpvivid_plugin->backup($task_id);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function list_tasks()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            if (isset($_POST['task_id']) || !empty($_POST['task_id']))
            {
                $task_id=$_POST['task_id'];
                $ret=$this->_list_tasks($task_id);
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

    function wpvivid_update_auto_option(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try {
            if (isset($_POST['auto']) && is_string($_POST['auto'])) {
                $option['auto_backup_enable'] = 0;
                $option['auto_backup'] = 'local';
                WPvivid_Setting::update_option('wpvivid_auto_backup_before_update', $option);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    function get_custom_auto_backup_database_tables_info(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try {
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

            $auto_backup_before_update = get_option('wpvivid_auto_backup_before_update', array());

            $ret['result'] = 'success';
            $ret['html'] = '';
            $base_table = '';
            $other_table = '';
            $diff_perfix_table = '';
            $tables_info = array();
            $has_base_table = false;
            $has_other_table = false;
            $has_diff_prefix_table = false;
            $base_table_all_check = true;
            $other_table_all_check = true;
            $diff_prefix_table_all_check = true;
            foreach ($tables as $row) {
                $tables_info[$row["Name"]]["Rows"] = $row["Rows"];
                $tables_info[$row["Name"]]["Data_length"] = size_format($row["Data_length"] + $row["Index_length"], 2);

                if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1) {
                    $checked = '';
                    if (isset($auto_backup_before_update['include-tables']) && !empty($auto_backup_before_update['include-tables'])) {
                        if (in_array($row["Name"], $auto_backup_before_update['include-tables'])) {
                            $checked = 'checked';
                        }
                    }
                    if($checked == ''){
                        $diff_prefix_table_all_check = false;
                    }
                    $has_diff_prefix_table = true;

                    $diff_perfix_table .= '<div class="wpvivid-text-line">
                                                <input type="checkbox" option="diff_prefix_db" name="auto_backup_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                                <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                           </div>';
                }
                else{
                    $checked = 'checked';
                    if (isset($auto_backup_before_update['exclude-tables']) && !empty($auto_backup_before_update['exclude-tables'])) {
                        if (in_array($row["Name"], $auto_backup_before_update['exclude-tables'])) {
                            $checked = '';
                        }
                    }

                    if (in_array($row["Name"], $default_table)) {
                        if ($checked == '') {
                            $base_table_all_check = false;
                        }
                        $has_base_table = true;

                        $base_table .= '<div class="wpvivid-text-line">
                                            <input type="checkbox" option="base_db" name="auto_backup_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                            <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                        </div>';
                    } else {
                        if ($checked == '') {
                            $other_table_all_check = false;
                        }
                        $has_other_table = true;

                        $other_table .= '<div class="wpvivid-text-line">
                                            <input type="checkbox" option="other_db" name="auto_backup_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                            <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                         </div>';
                    }
                }
            }

            $ret['html'] = '<div style="padding-left:2em;margin-top:1em;">
                                        <div style="border-bottom:1px solid #eee;"></div>
                                     </div>';

            $base_table_html = '';
            $other_table_html = '';
            $diff_prefif_table_html = '';
            if ($has_base_table) {
                $base_all_check = '';
                if ($base_table_all_check) {
                    $base_all_check = 'checked';
                }
                $base_table_html .= '<div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;padding-left:2em;">
                                        <div>
                                            <p>
                                                <span class="dashicons dashicons-list-view wpvivid-dashicons-blue"></span>
                                                <label title="Check/Uncheck all">
                                                    <span><input type="checkbox" class="wpvivid-database-table-check wpvivid-database-base-table-check" '.esc_attr($base_all_check).'></span>
													<span><strong>Wordpress default tables</strong></span>
												</label>
                                            </p>
                                        </div>
                                        <div style="padding-bottom:0.5em;"><span><input type="text" class="wpvivid-select-base-table-text" placeholder="Filter Tables">
                                            <input type="button" value="Filter" class="button wpvivid-select-base-table-button" style="position: relative; z-index: 1;"></span>
                                        </div>
                                        <div class="wpvivid-database-base-list" style="height:250px;border:1px solid #eee;padding:0.2em 0.5em;overflow:auto;">
                                            '.$base_table.'
                                        </div>
                                        <div style="clear:both;"></div>
                                    </div>';
            }

            if ($has_other_table) {
                $other_all_check = '';
                if ($other_table_all_check) {
                    $other_all_check = 'checked';
                }

                if($has_diff_prefix_table){
                    $other_table_width = '40%';
                }
                else{
                    $other_table_width = '70%';
                }

                $other_table_html .= '<div style="width:'.$other_table_width.'; float:left;box-sizing:border-box;padding-left:0.5em;">
                                        <div>
                                            <p>
                                                <span class="dashicons dashicons-list-view wpvivid-dashicons-green"></span>
                                                <label title="Check/Uncheck all">
                                                    <span><input type="checkbox" class="wpvivid-database-table-check wpvivid-database-other-table-check" '.esc_attr($other_all_check).'></span>
                                                    <span><strong>Tables created by plugins or themes</strong></span>
                                                </label> 
                                            </p>
                                        </div>
                                        <div style="padding-bottom:0.5em;"><span><input type="text" class="wpvivid-select-other-table-text" placeholder="Filter Tables">
                                            <input type="button" value="Filter" class="button wpvivid-select-other-table-button" style="position: relative; z-index: 1;"></span>
                                        </div>
                                        <div class="wpvivid-database-other-list" style="height:250px;border:1px solid #eee;padding:0.2em 0.5em;overflow-y:auto;">
                                            '.$other_table.'
                                        </div>
                                     </div>';
            }

            if ($has_diff_prefix_table) {
                $diff_all_check = '';
                if($diff_prefix_table_all_check){
                    $diff_all_check = 'checked';
                }

                $diff_prefif_table_html .= '<div style="width:30%; float:left;box-sizing:border-box;padding-left:0.5em;">
                                                <div>
                                                    <p>
                                                    <span class="dashicons dashicons-list-view wpvivid-dashicons-orange"></span>
                                                    <label title="Check/Uncheck all">
                                                        <span><input type="checkbox" class="wpvivid-database-table-check wpvivid-database-diff-prefix-table-check" '.esc_attr($diff_all_check).'></span>
                                                        <span><strong>Tables With Different Prefix</strong></span>
                                                    </label>
                                                    </p>
                                                </div>
                                                <div style="padding-bottom:0.5em;"><span><input type="text" class="wpvivid-select-diff-prefix-table-text" placeholder="Filter Tables">
                                                    <input type="button" value="Filter" class="button wpvivid-select-diff-prefix-table-button" style="position: relative; z-index: 1;"></span>
                                                </div>
                                                <div class="wpvivid-database-diff-prefix-list" style="height:250px;border:1px solid #eee;padding:0.2em 0.5em;overflow:auto;">
                                                    '.$diff_perfix_table.'
                                                </div>
                                            </div>';
            }

            $ret['html'] .= $base_table_html . $other_table_html . $diff_prefif_table_html;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function pre_auto_update($type, $item)
    {
        /*
        $need_auto_backup = self::check_need_auto_backup();
        if($need_auto_backup)
        {

        }
        */

        if ('plugin' == $type || 'theme' == $type)
        {
            $this->auto_backup_pre_update($type, $item);
        }
        elseif ('core' == $type)
        {
            $this->auto_backup_pre_update($type, $item);
        }
    }

    public function auto_backup_pre_update($type, $item)
    {
        if(empty($this->already_backup_auto_update))
        {
            $backup_options = array();
            if (is_null($backup_options))
            {
                die();
            }

            //select backup content
            $backup_options['backup_select']['other'] = 0;
            $backup_options['exclude_tables'] = array();
            $backup_options['include_plugins'] = array();
            $backup_options['include_themes'] = array();
            $backup_options['exclude_uploads'] = array();
            $backup_options['exclude_uploads_files'] = array();
            $backup_options['exclude_content'] = array();
            $backup_options['exclude_content_files'] = array();
            $backup_options['exclude_custom_other'] = array();
            $backup_options['exclude_custom_other_files'] = array();

            $backup_options['backup_select']['core'] = 0;
            $backup_options['backup_select']['db'] = 1;

            $backup_options['backup_select']['themes'] = 0;
            $backup_options['backup_select']['plugin'] = 0;
            $backup_options['backup_select']['uploads'] = 0;
            $backup_options['backup_select']['content'] = 0;
            $backup_options['backup_select']['additional_db'] = 0;

            $backup_options['ismerge'] = '0';
            $backup_options['lock'] = '0';

            $backup_to_remote = 0;
            $backup_type = 'Rollback';
            $action = 'backup';
            $backup_options['backup_to'] = 'local';
            $backup_options['local'] = 1;
            $backup_options['remote'] = 0;

            /*
            $option = get_option('wpvivid_auto_backup_before_update',array());
            if(empty($option))
            {
                $backup_to_remote = 0;
            }
            else
            {
                if($option['auto_backup'] === 'remote')
                {
                    $backup_to_remote = 1;
                }
                else
                {
                    $backup_to_remote=0;
                }
            }

            if ($backup_to_remote == 1) {
                $backup_type = 'Rollback';
                $action = 'backup_remote';
                $backup_options['backup_to'] = 'rollback_remote';
                $backup_options['local'] = 0;
                $backup_options['remote'] = 1;
            } else {
                $backup_type = 'Rollback';
                $action = 'backup';
                $backup_options['backup_to'] = 'local';
                $backup_options['local'] = 1;
                $backup_options['remote'] = 0;
            }*/

            $backup = new WPvivid_Backup_Task();
            $ret = $backup->new_backup_task($backup_options, $backup_type, $action);

            if($ret['result']='success')
            {
                $task_id = $ret['task_id'];
                $this->wpvivid_check_auto_update(60, $task_id);
                global $wpvivid_plugin;
                //global $wpvivid_backup_pro;
                //$wpvivid_backup_pro->func->flush($task_id);
                $wpvivid_plugin->backup($task_id);
            }
        }
    }

    public function wpvivid_check_auto_update($how_long, $task_id)
    {
        wp_clear_scheduled_hook('wpvivid_wp_maybe_auto_update');
        if (!$how_long)
            return;
        $lock_result = get_option('auto_updater.lock');
        wp_schedule_single_event(time() + $how_long, 'wpvivid_wp_maybe_auto_update', array($lock_result, $task_id));
    }

    public function wpvivid_wp_maybe_auto_update($lock_value, $task_id)
    {
        wp_clear_scheduled_hook('wpvivid_wp_maybe_auto_update');
        $lock_result = get_option('auto_updater.lock');
        if ($lock_result != $lock_value)
            return;
        $ret=$this->_list_tasks($task_id);
        if($ret['backup']['data'][$task_id]['status']['str'] === 'completed' || $ret['backup']['data'][$task_id]['status']['str'] === 'error')
        {
            delete_option('auto_updater.lock');
            $this->already_backup_auto_update = true;
            wp_maybe_auto_update();
        }
        else if(empty($ret['backup']['data']))
        {
            delete_option('auto_updater.lock');
            $this->already_backup_auto_update = true;
            wp_maybe_auto_update();
        }
        else
        {
            wp_schedule_single_event(time() + 60, 'wpvivid_wp_maybe_auto_update', array($lock_result, $task_id));
        }
    }
}