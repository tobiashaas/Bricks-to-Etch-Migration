<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Rollback_Addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Rollback_Addon
{
    public $main_tab;
    public $already_backup_auto_update;

    public function __construct()
    {
        $this->already_backup_auto_update=false;

        add_filter( 'upgrader_pre_install', array( $this, 'backup' ), 10, 2 );
        //
        add_action('pre_auto_update',array($this,'auto_core_backup'),20,3);
        //
        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));
        //add_action('init', array($this, 'init_rollback'));
        $this->check_schedule();
        add_action('wpvivid_check_rollback_event',array( $this,'check_rollback_event'));

        //db backup
        add_action('core_upgrade_preamble', array( $this,'core_auto_backup_check'),10);
        add_action('load-themes.php', array($this, 'load_themes'),99);
        add_action('pre_auto_update', array($this, 'pre_auto_update'), 10, 2);
        add_action('wpvivid_wp_maybe_auto_update', array($this, 'wpvivid_wp_maybe_auto_update'), 10, 2);
        add_action('pre_current_active_plugins', array($this, 'pre_current_active_plugins'));

        //send email report
        add_action('wpvivid_do_rollback_mail_report', array($this, 'do_rollback_mail_report'), 10, 4);
        add_action('wpvivid_send_mail_plugin_update_single_event', array($this, 'send_mail_plugin_update_event'));
        add_action('wpvivid_send_mail_theme_update_single_event', array($this, 'send_mail_theme_update_event'));
        //export rollback setting
        add_action('wpvivid_rollback_upload_single_event',array($this, 'rollback_upload_event'), 10);
        add_action('wpvivid_rollback_upload_event',array($this, 'rollback_upload_event'), 10);
        //
        add_action('init', array($this, 'init_rollback_setting'));
    }

    public function init_rollback_setting()
    {
        $old_rollback_version=get_option('wpvivid_rollback_version', '0.0');
        if(version_compare($old_rollback_version, '1.0.1', '<'))
        {
            $rollback_delete_local=get_option("wpvivid_rollback_delete_local", 'not_set');
            if($rollback_delete_local === 'not_set')
            {
                update_option('wpvivid_rollback_retain_local', 0, 'no');
            }
            else
            {
                if($rollback_delete_local)
                {
                    update_option('wpvivid_rollback_retain_local', 0, 'no');
                }
                else
                {
                    update_option('wpvivid_rollback_retain_local', 1, 'no');
                }
            }
            update_option('wpvivid_rollback_version', '1.0.1', 'no');
        }
    }

    public function rollback_upload_event()
    {
        set_time_limit(300);

        $rollback_data=get_option("wpvivid_rollback_plugin_data",array());
        $rollback_retain_local=get_option('wpvivid_rollback_retain_local',0);
        //
        if(!empty($rollback_data))
        {
            foreach ($rollback_data as $slug=>$data)
            {
                if(!isset($data['version']))
                {
                    continue;
                }

                foreach ($data['version'] as $version=>$version_data)
                {
                    if($version_data['upload']===false)
                    {
                        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/plugins/'.$slug.'/'.$version;
                        $file_name=$path.'/'.$slug.'.zip';
                        if(file_exists($file_name))
                        {
                            $ret=$this->upload_rollback_file($file_name,'plugins',$slug,$version);
                            if($ret['result']=='success')
                            {
                                $rollback_data[$slug]['version'][$version]['upload']=true;
                                $rollback_data[$slug]['version'][$version]['file']['size']=filesize($file_name);
                                $rollback_data[$slug]['version'][$version]['file']['modified']=filemtime($file_name);
                                $rollback_data[$slug]['version'][$version]['file']['file_name']=basename($file_name);
                                update_option("wpvivid_rollback_plugin_data",$rollback_data,'no');
                                if(!$rollback_retain_local)
                                {
                                    @unlink($file_name);
                                    @rmdir($path);
                                }
                            }
                        }
                    }
                }
            }
        }

        $rollback_data=get_option("wpvivid_rollback_theme_data",array());
        if(!empty($rollback_data))
        {
            foreach ($rollback_data as $slug=>$data)
            {
                if(!isset($data['version']))
                {
                    continue;
                }

                foreach ($data['version'] as $version=>$version_data)
                {
                    if($version_data['upload']===false)
                    {
                        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/themes/'.$slug.'/'.$version;
                        $file_name=$path.'/'.$slug.'.zip';
                        if(file_exists($file_name))
                        {
                            $ret=$this->upload_rollback_file($file_name,'themes',$slug,$version);
                            if($ret['result']=='success')
                            {
                                $rollback_data[$slug]['version'][$version]['upload']=true;
                                $rollback_data[$slug]['version'][$version]['file']['size']=filesize($file_name);
                                $rollback_data[$slug]['version'][$version]['file']['modified']=filemtime($file_name);
                                $rollback_data[$slug]['version'][$version]['file']['file_name']=basename($file_name);
                                update_option("wpvivid_rollback_theme_data",$rollback_data,'no');
                                if(!$rollback_retain_local)
                                {
                                    @unlink($file_name);
                                    @rmdir($path);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function upload_rollback_file($file_name,$type,$slug,$version)
    {
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='no need upload';
                return $ret;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='no need upload';
            return $ret;
        }

        $load=new WPvivid_Load_Admin_Remote();
        $load->load_file();

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $result=$remote->upload_rollback($file_name,$type,$slug,$version);
        if($result['result']=='success')
        {
            $ret['result']='success';
            return $ret;
        }
        else
        {
            return $result;
        }


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
                    var itemsSelected = $( '#update-plugins-table' ).find( 'input[name="checked[]"]:checked' );
                    var select_count = 0;
                    var is_wpvivid_backup=false;
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

                    $('form.upgrade[name="upgrade-plugins"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=plugins');
                });

                $('form.upgrade[name="upgrade-themes"]').submit(function ()
                {
                    var itemsSelected = $( '#update-themes-table' ).find( 'input[name="checked[]"]:checked' );
                    var select_count = 0;
                    itemsSelected.each( function( index, element )
                    {
                        var $checkbox = $( element ), $itemRow = $checkbox.parents( 'tr' );
                        select_count++;
                    } );

                    if(select_count === 0){
                        return;
                    }

                    $('form.upgrade[name="upgrade-themes"]').attr('action', 'admin.php?page=<?php echo strtolower(apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG)).'-backup'; ?>&auto_backup=1&backup=themes');
                });
                <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public static function wpvivid_rollback_get_siteurl()
    {
        $wpvivid_siteurl = array();
        $wpvivid_siteurl['home_url'] = home_url();
        $wpvivid_siteurl['plug_url'] = plugins_url();
        $wpvivid_siteurl['site_url'] = get_option( 'siteurl' );
        return $wpvivid_siteurl;
    }

    public function load_themes()
    {
        add_action('admin_footer', array($this, 'admin_load_themes'));
    }

    public function admin_load_themes()
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if(!$auto_backup_db_before_update)
        {
            return;
        }
        ?>
        <div id="wpvivid_dialog_modal" title="Backup Database">
            <h2>Please Wait...</h2>
            <div style="padding:1rem 0.5rem;">
                <div class="wpvivid-loader" style="float:left;"></div>
                <div style="padding-left:3rem;margin-top:0.68rem;" id="wpvivid_backup_progress_text">
                    Backing up database...
                </div>
                <div style="clear:both;"></div>
            </div>
            <div style="padding-left:0.4rem">
                <p>
                    <button id="wpvivid_backup_cancel" class="button">Cancel</button>
                    <span id="wpvivid_backup_progress_text2">Running time: 0 second(s)</span>
                </p>
            </div>
        </div>
        <script type="text/javascript">
            var wpvivid_siteurl = '<?php
                $wpvivid_siteurl = array();
                $wpvivid_siteurl=self::wpvivid_rollback_get_siteurl();
                echo esc_url($wpvivid_siteurl['site_url']);
                ?>';

            function wpvivid_rollback_cron_task()
            {
                jQuery.get(wpvivid_siteurl+'/wp-cron.php');
            }

            jQuery(document).ready(function($)
            {
                var wpvivid_updater = window.wp.updates;
                var wpvivid_backup_lock=false;

                $( "#wpvivid_dialog_modal" ).dialog({
                    dialogClass: 'noTitleStuff',
                    width:300,
                    modal: false,
                });

                $("#wpvivid_dialog_modal").dialog("close");

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
                    $( "#wpvivid_dialog_modal" ).dialog('open');
                    wpvivid_start_auto_backup();
                }

                var m_need_update=true;
                var task_retry_times=0;
                var wpvivid_prepare_backup=false;
                var running_backup_taskid='';
                var auto_backup_retry_times=0;

                function wpvivid_rollback_resume_backup(backup_id, next_resume_time)
                {
                    if(next_resume_time < 0){
                        next_resume_time = 0;
                    }
                    next_resume_time = next_resume_time * 1000;
                    setTimeout("wpvivid_rollback_cron_task()", next_resume_time);
                    setTimeout(function(){
                        task_retry_times = 0;
                        m_need_update=true;
                    }, next_resume_time);
                }

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
                                                wpvivid_rollback_resume_backup(index, value.data.next_resume_time);
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

                                        jQuery('#wpvivid_backup_progress_text').html(value.progress_text);
                                        jQuery('#wpvivid_backup_progress_text2').html(value.progress_text2);
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
                    setTimeout(function ()
                    {
                        $("#wpvivid_dialog_modal").dialog("close");
                    }, 1000);
                }
            });
        </script>
        <?php
    }

    public function pre_current_active_plugins()
    {
        add_action('admin_footer', array($this, 'admin_update_plugin'));
    }

    public function admin_update_plugin()
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if(!$auto_backup_db_before_update)
        {
            return;
        }
        ?>
        <div id="wpvivid_dialog_modal">
            <h2>Please Wait...</h2>
            <div style="padding:1rem 0.5rem;">
                <div class="wpvivid-loader" style="float:left;"></div>
                <div style="padding-left:3rem;margin-top:0.68rem;" id="wpvivid_backup_progress_text">
                    Backing up database...
                </div>
                <div style="clear:both;"></div>
            </div>
            <div style="padding-left:0.4rem">
                <p>
                    <button id="wpvivid_backup_cancel" class="button">Cancel</button>
                    <span id="wpvivid_backup_progress_text2">Running time: 0 second(s)</span>
                </p>
            </div>
        </div>
        <script type="text/javascript">
            var wpvivid_siteurl = '<?php
                $wpvivid_siteurl = array();
                $wpvivid_siteurl=self::wpvivid_rollback_get_siteurl();
                echo esc_url($wpvivid_siteurl['site_url']);
                ?>';

            function wpvivid_rollback_cron_task()
            {
                jQuery.get(wpvivid_siteurl+'/wp-cron.php');
            }

            jQuery(document).ready(function($)
            {
                $( "#wpvivid_dialog_modal" ).dialog({
                    dialogClass: 'noTitleStuff',
                    width:300,
                    modal: false,
                });

                $("#wpvivid_dialog_modal").dialog("close");

                jQuery('#wpvivid_backup_cancel').click(function()
                {
                    wpvivid_updater.ajaxLocked=false;
                    wpvivid_updater.queueChecker();
                    $("#wpvivid_dialog_modal").dialog('close');
                });

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
                    $( "#wpvivid_dialog_modal" ).dialog('open');
                    wpvivid_start_auto_backup();
                }

                var m_need_update=true;
                var task_retry_times=0;
                var wpvivid_prepare_backup=false;
                var running_backup_taskid='';
                var auto_backup_retry_times=0;

                function wpvivid_rollback_resume_backup(backup_id, next_resume_time)
                {
                    if(next_resume_time < 0){
                        next_resume_time = 0;
                    }
                    next_resume_time = next_resume_time * 1000;
                    setTimeout("wpvivid_rollback_cron_task()", next_resume_time);
                    setTimeout(function(){
                        task_retry_times = 0;
                        m_need_update=true;
                    }, next_resume_time);
                }

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
                                                wpvivid_rollback_resume_backup(index, value.data.next_resume_time);
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

                                        jQuery('#wpvivid_backup_progress_text').html(value.progress_text);
                                        jQuery('#wpvivid_backup_progress_text2').html(value.progress_text2);
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
                    setTimeout(function ()
                    {
                        $("#wpvivid_dialog_modal").dialog("close");
                    }, 1000);
                }

            });


        </script>
        <?php
    }

    public function do_rollback_mail_report($backup_status, $backup_content, $backup_slug, $backup_path)
    {
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

        if($option['email_enable'] == 0)
        {
            return true;
        }

        if(empty($option['send_to']))
        {
            return true;
        }

        if($backup_status!=='error'&&$option['always']==false)
        {
            return true;
        }

        if($backup_content === 'core')
        {
            $headers = array('Content-Type: text/html; charset=UTF-8');

            $subject = '';
            $subject =$this->set_mail_subject($backup_status);

            $body = '';
            $body = $this->set_mail_body($backup_status, $backup_content, $backup_slug, $backup_path);

            $attachments = array();
            foreach ($option['send_to'] as $send_to)
            {
                if(wp_mail( $send_to, $subject, $body,$headers,$attachments)===false)
                {
                    //
                }
            }
        }
        else
        {
            if($backup_content === 'plugin')
            {
                $send_mail_plugin_option=get_option('wpvivid_send_mail_plugin_option', array());
                if(isset($backup_slug) && !empty($backup_slug))
                {
                    $send_mail_plugin_option[$backup_slug]['name']=$backup_slug;
                    $send_mail_plugin_option[$backup_slug]['result']=$backup_status;
                    $send_mail_plugin_option[$backup_slug]['path']=$backup_path;
                    update_option('wpvivid_send_mail_plugin_option', $send_mail_plugin_option, 'no');

                    if(wp_next_scheduled('wpvivid_send_mail_plugin_update_single_event')===false)
                    {
                        wp_schedule_single_event(time() + 600, 'wpvivid_send_mail_plugin_update_single_event');
                    }
                }
            }
            if($backup_content === 'theme')
            {
                $send_mail_theme_option=get_option('wpvivid_send_mail_theme_option', array());
                if(isset($backup_slug) && !empty($backup_slug))
                {
                    $send_mail_theme_option[$backup_slug]['name']=$backup_slug;
                    $send_mail_theme_option[$backup_slug]['result']=$backup_status;
                    $send_mail_theme_option[$backup_slug]['path']=$backup_path;
                    update_option('wpvivid_send_mail_theme_option', $send_mail_theme_option, 'no');

                    if(wp_next_scheduled('wpvivid_send_mail_theme_update_single_event')===false)
                    {
                        wp_schedule_single_event(time() + 600, 'wpvivid_send_mail_theme_update_single_event');
                    }
                }
            }
        }

        return true;
    }

    public function send_mail_plugin_update_event()
    {
        $send_mail_plugin_option=get_option('wpvivid_send_mail_plugin_option', array());
        if(!empty($send_mail_plugin_option))
        {
            $backup_plugin_success=array();
            $backup_plugin_failed=array();
            $backup_plugin_path='';
            foreach ($send_mail_plugin_option as $slug => $plugin_info)
            {
                if($plugin_info['result']==='Succeeded')
                {
                    $backup_plugin_success[]=$plugin_info['name'];
                }
                else
                {
                    $backup_plugin_failed[]=$plugin_info['name'];
                }
                $backup_plugin_path=$plugin_info['path'];
            }

            $option=get_option('wpvivid_email_setting_addon');
            $tmp_email = array();
            if(!empty($option['send_to']))
            {
                foreach ($option['send_to'] as $email => $value)
                {
                    $tmp_email[] = $email;
                }
                $option['send_to'] = $tmp_email;
            }
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $subject =$this->set_plugin_theme_mail_subject();
            $body = $this->set_plugin_theme_mail_body('plugin', $backup_plugin_success, $backup_plugin_failed, $backup_plugin_path);
            $attachments = array();
            foreach ($option['send_to'] as $send_to)
            {
                if(wp_mail( $send_to, $subject, $body,$headers,$attachments)===false)
                {
                    //
                }
            }
            update_option('wpvivid_send_mail_plugin_option', array(), 'no');
        }
    }

    public function send_mail_theme_update_event()
    {
        $send_mail_theme_option=get_option('wpvivid_send_mail_theme_option', array());
        if(!empty($send_mail_theme_option))
        {
            $backup_theme_success=array();
            $backup_theme_failed=array();
            $backup_theme_path='';
            foreach ($send_mail_theme_option as $slug => $theme_info)
            {
                if($theme_info['result']==='Succeeded')
                {
                    $backup_theme_success[]=$theme_info['name'];
                }
                else
                {
                    $backup_theme_failed[]=$theme_info['name'];
                }
                $backup_theme_path=$theme_info['path'];
            }

            $option=get_option('wpvivid_email_setting_addon');
            $tmp_email = array();
            if(!empty($option['send_to']))
            {
                foreach ($option['send_to'] as $email => $value)
                {
                    $tmp_email[] = $email;
                }
                $option['send_to'] = $tmp_email;
            }
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $subject =$this->set_plugin_theme_mail_subject();
            $body = $this->set_plugin_theme_mail_body('theme', $backup_theme_success, $backup_theme_failed, $backup_theme_path);
            $attachments = array();
            foreach ($option['send_to'] as $send_to)
            {
                if(wp_mail( $send_to, $subject, $body,$headers,$attachments)===false)
                {
                    //
                }
            }
            update_option('wpvivid_send_mail_theme_option', array(), 'no');
        }
    }

    public function set_mail_subject($backup_status)
    {
        if($backup_status!=='error')
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
        $localtime=gmdate('m-d-Y H:i:s', time()+$offset*60*60);
        $subject='['.$mail_title.'Backup '.$status.']'.$localtime.sprintf(' - By %s', apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin'));
        return $subject;
    }

    public function set_plugin_theme_mail_subject()
    {
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
        }
        else{
            $mail_title = '';
        }

        $offset=get_option('gmt_offset');
        $localtime=gmdate('m-d-Y H:i:s', time()+$offset*60*60);
        $subject='['.$mail_title.']'.$localtime.sprintf(' - By %s', apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin'));
        return $subject;
    }

    public function set_mail_body($backup_status, $backup_content, $backup_slug, $backup_path)
    {
        if($backup_status!=='error')
        {
            $status='Succeeded';
        }
        else
        {
            $status='Failed';
        }

        $offset=get_option('gmt_offset');
        $end_time=date("m-d-Y H:i:s",time()+$offset*60*60);

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

        $backup_type = 'Rollback';

        if($backup_content === 'core')
        {
            $backup_what = 'WordPress Core';
        }
        else
        {
            $backup_what = $backup_slug;
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
                                                <span>WordPress Backup Report</span>            
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
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Time: </label><label>'.$end_time.'</label></p>
                                                        </td>
                                                    </tr>
                                                     <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Type: </label><label>'.$backup_type.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Content: </label><label>'.$backup_what.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup To: </label><label>'.$backup_path.'</label></p>
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

    public function set_plugin_theme_mail_body($backup_what, $backup_success, $backup_failed, $backup_path='')
    {
        $offset=get_option('gmt_offset');
        $end_time=date("m-d-Y H:i:s",time()+$offset*60*60);

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

        $backup_type = 'Rollback';

        $backup_success_html='';
        $backup_failed_html='';
        if($backup_what === 'plugin')
        {
            $backup_plugin_count=0;
            if(!empty($backup_success))
            {
                $backup_success_content='';
                foreach ($backup_success as $content_name)
                {
                    if($backup_success_content==='')
                    {
                        $backup_success_content.=$content_name;
                    }
                    else
                    {
                        $backup_success_content.=', '.$content_name;
                    }
                    $backup_plugin_count++;
                }
                $backup_success_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Succeeded: </label><label>'.$backup_success_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if(!empty($backup_failed))
            {
                $backup_failed_content='';
                foreach ($backup_failed as $content_name)
                {
                    if($backup_failed_content==='')
                    {
                        $backup_failed_content.=$content_name;
                    }
                    else
                    {
                        $backup_failed_content.=', '.$content_name;
                    }
                    $backup_plugin_count++;
                }
                $backup_failed_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup <strong>Failed:</strong> </label><label>'.$backup_failed_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if($backup_plugin_count > 1)
            {
                $backup_content='Plugins';
            }
            else
            {
                $backup_content='Plugin';
            }
        }

        if($backup_what === 'theme')
        {
            $backup_theme_count=0;
            if(!empty($backup_success))
            {
                $backup_success_content='';
                foreach ($backup_success as $content_name)
                {
                    if($backup_success_content==='')
                    {
                        $backup_success_content.=$content_name;
                    }
                    else
                    {
                        $backup_success_content.=', '.$content_name;
                    }
                    $backup_theme_count++;
                }
                $backup_success_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Succeeded: </label><label>'.$backup_success_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if(!empty($backup_failed))
            {
                $backup_failed_content='';
                foreach ($backup_failed as $content_name)
                {
                    if($backup_failed_content==='')
                    {
                        $backup_failed_content.=$content_name;
                    }
                    else
                    {
                        $backup_failed_content.=', '.$content_name;
                    }
                    $backup_theme_count++;
                }
                $backup_failed_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup <strong>Failed:</strong> </label><label>'.$backup_failed_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if($backup_theme_count > 1)
            {
                $backup_content='Themes';
            }
            else
            {
                $backup_content='Theme';
            }
        }

        if(!empty($backup_path))
        {
            $backup_path='<tr>
                            <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup To: </label><label>'.$backup_path.'</label></p>
                            </td>
                          </tr>';
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
                                                <span>WordPress Backup Report</span>            
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
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Content: </label><label>'.$backup_content.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Time: </label><label>'.$end_time.'</label></p>
                                                        </td>
                                                    </tr>
                                                     <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Type: </label><label>'.$backup_type.'</label></p>
                                                        </td>
                                                    </tr>
                                                    '.$backup_success_html.'
                                                    '.$backup_failed_html.'
                                                    '.$backup_path.'
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

    public function check_schedule()
    {
        if(!defined( 'DOING_CRON' ))
        {
            if(wp_get_schedule('wpvivid_check_rollback_event')===false)
            {
                if(wp_schedule_event(time()+30, 'daily', 'wpvivid_check_rollback_event')===false)
                {
                    return false;
                }
            }

            if(wp_get_schedule('wpvivid_rollback_upload_event')===false)
            {
                if(wp_schedule_event(time()+30, 'daily', 'wpvivid_rollback_upload_event')===false)
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function check_rollback_event()
    {
        set_time_limit(300);
        $this->check_plugins_versions();
        $this->check_themes_versions();
        $this->check_core_versions();
    }

    public function init_rollback()
    {
        $init=get_option('wpvivid_init_rollback_setting',false);
        if(!$init)
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $all_plugins     = get_plugins();

            $plugins_auto_backup_status=array();

            foreach ((array) $all_plugins as $plugin_file => $plugin_data)
            {
                if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
                {
                    continue;
                }

                $slug=$this->get_plugin_slug($plugin_file);

                if(is_plugin_active($plugin_file))
                {
                    $plugins_auto_backup_status[ $slug ]['enable_auto_backup']= true;
                }
            }

            update_option('wpvivid_plugins_auto_backup_status',$plugins_auto_backup_status,'no');

            $themes =wp_get_themes();

            $themes_auto_backup_status=array();

            foreach ($themes as $key=>$theme)
            {
                if ( get_stylesheet() === $key)
                {
                    $themes_auto_backup_status[$key]=true;
                }
            }

            update_option('wpvivid_themes_auto_backup_status',$themes_auto_backup_status,'no');
            update_option('wpvivid_init_rollback_setting',true,'no');
        }
    }

    public function check_plugins_versions()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $all_plugins     = get_plugins();
        $counts=get_option('wpvivid_max_rollback_count',array());
        $rollback_retain_local=get_option('wpvivid_rollback_retain_local',0);
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/plugins/';
        $max_plugins_count=isset($counts['max_plugins_count'])?$counts['max_plugins_count']:5;
        $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
        foreach ((array) $all_plugins as $plugin_file => $plugin_data)
        {
            $plugin['slug']=$this->get_plugin_slug($plugin_file);
            $plugin['rollback']=$this->get_rollback_data($plugin_file);
            if(isset($rollback_plugin_data[$plugin['slug']]))
                $plugin['rollback_data']=$rollback_plugin_data[$plugin['slug']];
            else
                $plugin['rollback_data']=array();

            $rollback_version=array();
            if(!empty($plugin['rollback']))
            {
                foreach ($plugin['rollback'] as $version=>$file)
                {
                    $rollback_version[$version]=$version;
                }
            }

            if(!empty($plugin['rollback_data']))
            {
                foreach ($plugin['rollback_data']['version'] as $version=>$data)
                {
                    if($data['upload'])
                    {
                        $rollback_version[$version]=$version;
                        if(!$rollback_retain_local)
                        {
                            if(file_exists($path.$plugin['slug'].'/'.$version.'/'.$plugin['slug'].'.zip'))
                            {
                                @unlink($path.$plugin['slug'].'/'.$version.'/'.$plugin['slug'].'.zip');
                                @rmdir($path.$plugin['slug'].'/'.$version);
                            }
                        }
                    }

                }
            }

            if(!empty($rollback_version))
            {
                if(sizeof($rollback_version)>$max_plugins_count)
                {
                    $this->delete_old_plugins_rollback($plugin,$max_plugins_count);
                }
            }
        }
    }

    public function delete_old_plugins_rollback($plugin,$max_plugins_count)
    {
        $slug=$plugin['slug'];
        $rollback_data=$plugin['rollback'];

        $rollback_version=array();
        if(!empty($rollback_data))
        {
            foreach ($rollback_data as $version=>$file)
            {
                $rollback_version[$version]=$version;
            }
        }

        if(!empty($plugin['rollback_data']))
        {
            foreach ($plugin['rollback_data']['version'] as $version=>$data)
            {
                if($data['upload'])
                    $rollback_version[$version]=$version;
            }
        }

        uksort($rollback_version, function ($a, $b)
        {
            if($a==$b)
                return 0;

            if (version_compare($a,$b,'>'))
                return 1;
            else
                return -1;
        });

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/plugins/'.$slug ;

        $size=sizeof($rollback_version);
        while($size>$max_plugins_count)
        {
            foreach ($rollback_version as $version)
            {
                if(file_exists($path.'/'.$version.'/'.$slug.'.zip'))
                {
                    @unlink($path.'/'.$version.'/'.$slug.'.zip');
                    @rmdir($path.'/'.$version);
                }

                if(!empty($plugin['rollback_data']))
                {
                    if(isset($plugin['rollback_data']['version'][$version]))
                    {
                        $data=$plugin['rollback_data']['version'][$version];
                        if($data['upload'])
                        {
                            $this->cleanup_remote_rollback("plugins",$slug,$version);
                        }
                        $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
                        unset($rollback_plugin_data[$slug]['version'][$version]);
                        update_option('wpvivid_rollback_plugin_data',$rollback_plugin_data,'no');
                    }
                }

                unset($rollback_version[$version]);
                break;
            }
            $size=sizeof($rollback_version);
        }
    }

    public function cleanup_remote_rollback($type,$slug,$version)
    {
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='success';
                return $ret;
            }
        }
        else
        {
            $ret['result']='success';
            return $ret;
        }

        $load=new WPvivid_Load_Admin_Remote();
        $load->load_file();

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $result=$remote->cleanup_rollback($type,$slug,$version);
        if($result['result']=='success')
        {
            $ret['result']='success';
            return $ret;
        }
        else
        {
            return $result;
        }
    }

    public function check_themes_versions()
    {
        $themes =wp_get_themes();

        $counts=get_option('wpvivid_max_rollback_count',array());
        $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());
        $max_themes_count=isset($counts['max_themes_count'])?$counts['max_themes_count']:5;

        foreach ($themes as $key=>$theme)
        {
            $theme_data["slug"]=$key;
            $theme_data['rollback']=$this->get_rollback_data($key,'themes');
            if(isset($rollback_theme_data[$key]))
                $theme_data['rollback_data']=$rollback_theme_data[$key];
            else
                $theme_data['rollback_data']=array();

            $rollback_version=array();
            if(!empty($theme_data['rollback']))
            {
                foreach ($theme_data['rollback'] as $version=>$file)
                {
                    $rollback_version[$version]=$version;
                }
            }

            if(!empty($theme_data['rollback_data']))
            {
                foreach ($theme_data['rollback_data']['version'] as $version=>$data)
                {
                    if($data['upload'])
                        $rollback_version[$version]=$version;
                }
            }

            if(!empty($rollback_version))
            {
                if(sizeof($rollback_version)>$max_themes_count)
                {
                    $this->delete_old_theme_rollback($theme_data,$max_themes_count);
                }
            }
        }
    }

    public function delete_old_theme_rollback($theme_data,$max_themes_count)
    {
        $slug=$theme_data['slug'];
        $rollback_data=$theme_data['rollback'];

        $rollback_version=array();
        if(!empty($rollback_data))
        {
            foreach ($rollback_data as $version=>$file)
            {
                $rollback_version[$version]=$version;
            }
        }

        if(!empty($theme_data['rollback_data']))
        {
            foreach ($theme_data['rollback_data']['version'] as $version=>$data)
            {
                if($data['upload'])
                    $rollback_version[$version]=$version;
            }
        }

        uksort($rollback_version, function ($a, $b)
        {
            if($a==$b)
                return 0;

            if (version_compare($a,$b,'>'))
                return 1;
            else
                return -1;
        });

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/themes/'.$slug ;

        $size=sizeof($rollback_version);
        while($size>$max_themes_count)
        {
            foreach ($rollback_version as $version)
            {
                if(file_exists($path.'/'.$version.'/'.$slug.'.zip'))
                {
                    @unlink($path.'/'.$version.'/'.$slug.'.zip');
                    @rmdir($path.'/'.$version);
                }

                if(!empty($theme_data['rollback_data']))
                {
                    if(isset($theme_data['rollback_data']['version'][$version]))
                    {
                        $data=$theme_data['rollback_data']['version'][$version];
                        if($data['upload'])
                        {
                            $this->cleanup_remote_rollback("themes",$slug,$version);
                        }
                        $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());
                        unset($rollback_theme_data[$slug]['version'][$version]);
                        update_option('wpvivid_rollback_theme_data',$rollback_theme_data,'no');
                    }
                }

                unset($rollback_version[$version]);
                break;
            }
            $size=sizeof($rollback_version);
        }
    }

    public function check_core_versions()
    {
        $core_list=$this->get_core_data();

        $counts=get_option('wpvivid_max_rollback_count',array());
        $max_core_count=isset($counts['max_core_count'])?$counts['max_core_count']:5;
        if(!empty($core_list))
        {
            if(sizeof($core_list)>$max_core_count)
            {
                $this->delete_old_core_rollback($core_list,$max_core_count);
            }
        }
    }

    public function delete_old_core_rollback($core_list,$max_core_count)
    {
        uksort($core_list, function ($a, $b)
        {
            if($a==$b)
                return 0;

            if (version_compare($a,$b,'>'))
                return 1;
            else
                return -1;
        });

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core/' ;

        $size=sizeof($core_list);
        while($size>$max_core_count)
        {
            foreach ($core_list as $version=>$data)
            {
                if(file_exists($path.'/'.$version.'/wordpress.zip'))
                {
                    @unlink($path.'/'.$version.'/wordpress.zip');
                    @rmdir($path.'/'.$version);
                }
                unset($core_list[$version]);
                break;
            }
            $size=sizeof($core_list);
        }
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-rollback';
        $cap['display']='Rollback';
        $cap['menu_slug']=strtolower(sprintf('%s-rollback', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['icon']='<span class="dashicons dashicons-update wpvivid-dashicons-grey"></span>';
        $cap['index']=13;
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function auto_core_backup($type, $item, $context)
    {
        $auto_backup_core=get_option('wpvivid_plugins_auto_backup_core',false);
        if($auto_backup_core===false)
        {
            return;
        }

        if ( 'core' === $type )
        {
            $this->backup_core();
        }
    }

    public function backup_core()
    {
        set_time_limit(300);

        $replace_path=$this -> transfer_path(ABSPATH);
        $files=$this->get_core_files();

        if (!class_exists('WPvivid_PclZip'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';

        require ABSPATH . WPINC . '/version.php';
        global $wp_version;
        $version=$wp_version;

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/core/'.$version;
        $file_name='wordpress.zip';
        if(!file_exists($path))
        {
            @mkdir($path,0777,true);
        }

        if(file_exists($path.'/'.$file_name))
        {
            @unlink($path.'/'.$file_name);
        }

        $pclzip=new WPvivid_PclZip($path.'/'.$file_name);
        $ret = $pclzip -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,16);
        if (!$ret)
        {
            do_action('wpvivid_do_rollback_mail_report', 'error', 'core', '', $path);
            $last_error = $pclzip->errorInfo(true);
            $backup_ret['result']='failed';
            $backup_ret['error'] = $last_error;
            return $backup_ret;
        }
        else
        {
            do_action('wpvivid_do_rollback_mail_report', 'Succeeded', 'core', '', $path);
            $backup_ret['result']='success';
            return $backup_ret;
        }
    }

    public function get_core_files()
    {
        $root_path=$this->transfer_path(ABSPATH);
        $root_path=untrailingslashit($root_path);

        $include_regex=array();
        $include_regex[]='#^'.preg_quote($this -> transfer_path(ABSPATH.'wp-admin'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(ABSPATH.'wp-includes'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(ABSPATH.'lotties'), '/').'#';
        $exclude_regex=array();
        $exclude_regex[]='#^'.preg_quote($this -> transfer_path(ABSPATH).'/'.'wp-config.php', '/').'#';
        $exclude_regex[]='#^'.preg_quote($this -> transfer_path(ABSPATH).'/'.'.htaccess', '/').'#';
        $files=array();
        $this->_get_files($root_path,$files,$exclude_regex,$include_regex);
        return $files;
    }

    public function _get_files($path,&$files,$exclude_regex,$include_regex)
    {
        $handler = opendir($path);

        if($handler===false)
            return;

        while (($filename = readdir($handler)) !== false)
        {
            if ($filename != "." && $filename != "..")
            {
                if (is_dir($path . '/' . $filename) && !@is_link($path . '/' . $filename))
                {
                    if ($this->regex_match($include_regex, $path . '/' . $filename, 1))
                    {
                        $this->_get_files($path . '/' . $filename,$files,$exclude_regex,$include_regex);
                    }
                }
                else
                {
                    if(is_readable($path . '/' . $filename) && !@is_link($path . '/' . $filename))
                    {
                        if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                        {
                            $files[]=$this->transfer_path($path . '/' . $filename);
                        }
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);

        return;
    }

    private function regex_match($regex_array,$string,$mode)
    {
        if(empty($regex_array))
        {
            return true;
        }

        if($mode==0)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return false;
                }
            }

            return true;
        }

        if($mode==1)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function backup($response, $args)
    {
        if ( is_wp_error( $response ) )
        {
            return $response;
        }

        $plugin = isset( $args['plugin'] ) ? $args['plugin'] : '';
        $theme = isset( $args['theme'] ) ? $args['theme'] : '';

        if(!empty($plugin))
        {
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR .'/'.$plugin, false, true);
            $version=$plugin_data['Version'];

            $slug=dirname($plugin);
            if($slug=='.')
            {
                $plugin_dir=WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugin;

                $slug = pathinfo($plugin, PATHINFO_FILENAME);
                $parent_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/plugins';
                $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/plugins/'.$slug.'/'.$version;
                $file_name=$slug.'zip';
            }
            else
            {
                $plugin_dir=WP_PLUGIN_DIR.'/'.$slug;
                $parent_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/plugins';
                $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/plugins/'.$slug.'/'.$version;
                $file_name=$slug.'.zip';
            }

            if($this->get_enable_auto_backup_status($slug))
            {
                $plugin_dir=$this->transfer_path($plugin_dir);
                $replace_path=$this->transfer_path(WP_PLUGIN_DIR.'/');

                if (!class_exists('WPvivid_PclZip'))
                    include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';

                if(!file_exists($path))
                {
                    @mkdir($path,0777,true);
                }

                if(file_exists($path.'/'.$file_name))
                {
                    @unlink($path.'/'.$file_name);
                }

                $pclzip=new WPvivid_PclZip($path.'/'.$file_name);
                $ret = $pclzip -> add($plugin_dir,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,16);
                if (!$ret)
                {
                    do_action('wpvivid_do_rollback_mail_report', 'error', 'plugin', $slug, $parent_path);
                    $last_error = $pclzip->errorInfo(true);
                    return new WP_Error('rollback_backup_failed',$last_error);
                }
                else
                {
                    $rollback_data=get_option("wpvivid_rollback_plugin_data",array());
                    $rollback_data[$slug]['update_time']=time();
                    $rollback_data[$slug]['last_update_version']=$version;
                    $rollback_data[$slug]['version'][$version]['upload']=false;
                    update_option('wpvivid_rollback_plugin_data',$rollback_data,'no');
                    if(wp_get_schedule('wpvivid_rollback_upload_single_event')===false)
                    {
                        wp_schedule_single_event(time()+180,'wpvivid_rollback_upload_single_event');
                    }
                    //wp_schedule_single_event
                    do_action('wpvivid_do_rollback_mail_report', 'Succeeded', 'plugin', $slug, $parent_path);
                }
            }
        }

        if(!empty($theme))
        {
            if($this->get_theme_enable_auto_backup_status($theme))
            {
                $wp_theme=wp_get_theme($theme);
                $version=$wp_theme->display( 'Version' );

                $parent_path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/themes';
                $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/themes/'.$theme.'/'.$version;
                $file_name=$theme.'.zip';

                $theme_root=$this->transfer_path(get_theme_root());
                $theme_dir=$theme_root.'/'.$theme;
                $replace_path=$this->transfer_path($theme_root.'/');

                if (!class_exists('WPvivid_PclZip'))
                    include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';

                if(!file_exists($path))
                {
                    @mkdir($path,0777,true);
                }

                if(file_exists($path.'/'.$file_name))
                {
                    @unlink($path.'/'.$file_name);
                }

                $pclzip=new WPvivid_PclZip($path.'/'.$file_name);
                $ret = $pclzip -> add($theme_dir,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,16);
                if (!$ret)
                {
                    do_action('wpvivid_do_rollback_mail_report', 'error', 'theme', $theme, $parent_path);
                    $last_error = $pclzip->errorInfo(true);
                    return new WP_Error('rollback_backup_failed',$last_error);
                }
                else
                {
                    $rollback_data=get_option("wpvivid_rollback_theme_data",array());
                    $rollback_data[$theme]['update_time']=time();
                    $rollback_data[$theme]['last_update_version']=$version;
                    $rollback_data[$theme]['version'][$version]['upload']=false;
                    update_option('wpvivid_rollback_theme_data',$rollback_data,'no');
                    if(wp_get_schedule('wpvivid_rollback_upload_single_event')===false)
                    {
                        wp_schedule_single_event(time()+180,'wpvivid_rollback_upload_single_event');
                    }
                    do_action('wpvivid_do_rollback_mail_report', 'Succeeded', 'theme', $theme, $parent_path);
                }
            }
        }

        return $response;
    }

    public function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode('/',$values);
    }

    public function get_plugin_slug($file)
    {
        $plugin=dirname($file);
        if($plugin=='.')
        {
            $slug = pathinfo($file, PATHINFO_FILENAME);
        }
        else
        {
            $slug=$plugin;
        }

        return $slug;
    }

    public function get_rollback_data($slug,$type='plugins')
    {
        $plugin=dirname($slug);
        if($plugin=='.')
        {
            $plugin = pathinfo($slug, PATHINFO_FILENAME);
        }

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/'.$type.'/'.$plugin;
        if(file_exists($path))
        {
            $rollback=array();
            $plugin_dir  = @opendir( $path );

            while ( ( $file = readdir( $plugin_dir ) ) !== false )
            {
                if ( '.' === substr( $file, 0, 1 ) )
                {
                    continue;
                }

                if ( is_dir( $path . '/' . $file ) )
                {
                    if(file_exists($path . '/' . $file.'/'.$plugin.'.zip'))
                    {
                        $rollback[$file]=$plugin.'.zip';
                    }
                }
            }

            closedir( $plugin_dir );
            return $rollback;
        }
        else
        {
            return array();
        }
    }

    public function get_core_data()
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core';
        $core_list=array();

        if(file_exists($path))
        {
            $core_dir  = @opendir( $path );

            while ( ( $file = readdir( $core_dir ) ) !== false )
            {
                if ( '.' === substr( $file, 0, 1 ) )
                {
                    continue;
                }

                if ( is_dir( $path . '/' . $file ) )
                {
                    if(file_exists($path . '/' . $file.'/wordpress.zip'))
                    {
                        $file_name=$path . '/' . $file.'/wordpress.zip';
                        $info['id']=$file;
                        $info['version']=$file;
                        $info['date']=date('M d Y h:i A', filemtime($file_name));
                        $info['size']=size_format(filesize($file_name),2);
                        $core_list[$file]=$info;
                    }
                }
            }

            closedir( $core_dir );
        }

        return $core_list;
    }

    public function get_enable_auto_backup_status($slug)
    {
        $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());
        if(isset($plugins_auto_backup_status[$slug]))
        {
            return $plugins_auto_backup_status[$slug];
        }
        else
        {
            $auto_enable_new_plugin = get_option('wpvivid_auto_enable_new_plugin', false);
            if($auto_enable_new_plugin)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function get_theme_enable_auto_backup_status($slug)
    {
        $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
        if(isset($themes_auto_backup_status[$slug]))
        {
            return $themes_auto_backup_status[$slug];
        }
        else
        {
            return false;
        }
    }

    public function pre_auto_update($type, $item)
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if($auto_backup_db_before_update)
        {
            $this->auto_backup_db_pre_update();
        }
    }

    public function auto_backup_db_pre_update()
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


            $rollback_remote = get_option('wpvivid_rollback_remote', 0);
            if ($rollback_remote)
            {
                $backup_to_remote = 1;
            } else {
                $backup_to_remote = 0;
            }

            if ($backup_to_remote == 1)
            {
                $backup_type = 'Rollback';
                $action = 'backup_remote';
                $backup_options['backup_to'] = 'rollback_remote';
                $backup_options['local'] = 0;
                $backup_options['remote'] = 1;

                $remote_id = get_option('wpvivid_rollback_remote_id', 0);
                $remoteslist=WPvivid_Setting::get_all_remote_options();
                if(isset($remoteslist[$remote_id]))
                {
                    $backup_options['remote_options'][$remote_id] = $remoteslist[$remote_id];
                }
            }
            else
            {
                $backup_type = 'Rollback';
                $action = 'backup';
                $backup_options['backup_to'] = 'local';
                $backup_options['local'] = 1;
                $backup_options['remote'] = 0;
            }

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
}

function wpvivid_pro_function_pre_core_extract_callback($p_event, &$p_header)
{
    $plugins = substr(WP_PLUGIN_DIR, strpos(WP_PLUGIN_DIR, 'wp-content/'));

    $path = str_replace('\\','/',WP_CONTENT_DIR);
    $content_path = $path.'/';
    if(strpos($p_header['filename'], $content_path.'advanced-cache.php')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'], $content_path.'db.php')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'], $content_path.'object-cache.php')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'],$plugins.'/wpvivid-backuprestore')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'],'wp-config.php')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'],'wpvivid_package_info.json')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'],'.htaccess')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'],'.user.ini')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'],'wordfence-waf.php')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'], $content_path.'mu-plugins/endurance-browser-cache.php')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'], $content_path.'mu-plugins/endurance-page-cache.php')!==false)
    {
        return 0;
    }

    if(strpos($p_header['filename'], $content_path.'mu-plugins/endurance-php-edge.php')!==false)
    {
        return 0;
    }

    return 1;
}