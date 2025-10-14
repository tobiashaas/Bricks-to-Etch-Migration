<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_Debug_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class WPvivid_Debug_addon
{
    public $main_tab;

    public function __construct()
    {
        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),14);

        add_action('wp_ajax_wpvivid_send_debug_info_addon', array($this, 'wpvivid_send_debug_info'), 11);
        add_action('wp_ajax_wpvivid_create_debug_package_addon', array($this, 'create_debug_package'));

        //dashboard
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        add_action('wpvivid_dashboard_menus_sidebar',array( $this,'debug_sidebar'),12);
    }

    public function debug_sidebar()
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_debug');
        if($display)
        {
            if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-backup'))
            {
                $url='admin.php?page='.strtolower(sprintf('%s-debug', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
                ?>
                <div class="wpvivid-four-cols">
                    <ul>
                        <li><span class="dashicons dashicons-buddicons-replies wpvivid-dashicons-middle wpvivid-dashicons-red"></span>
                            <a href="<?php echo $url; ?>"><b>Debug</b></a>
                            <br>
                            Check the website debug information and send us the information to help debug problems.</li>
                    </ul>
                </div>
                <?php
            }
        }

    }


    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-debug';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-debug';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_debug');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Debug');
            $submenu['menu_title'] = 'Debug';
            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-debug");
            $submenu['menu_slug'] = strtolower(sprintf('%s-debug', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 16;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_debug');
        if($display) {
            $menu['id'] = 'wpvivid_admin_menu_debug';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Debug';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug');
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-debug");
            $menu['index'] = 16;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function wpvivid_send_debug_info_addon($user_email, $server_type, $host_provider, $comment, $enable_debug_attachment)
    {
        $send_to = apply_filters('wpvivid_white_label_email', 'pro.support@wpvivid.com');
        $subject = sprintf('Debug information from %s Pro', apply_filters('wpvivid_white_label_display', 'WPvivid Backup'));
        $body = '<div>User\'s email: '.$user_email.'.</div>';
        $body .= '<div>Server type: '.$server_type.'.</div>';
        $body .= '<div>Host provider: '.$host_provider.'.</div>';
        $body .= '<div>Comment: '.$comment.'.</div>';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $path = '';
        if($enable_debug_attachment == '1') {
            $log=new WPvivid_Log_Ex_addon();
            $files = $log->get_error_log();

            if (!class_exists('PclZip'))
                include_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');

            $path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . 'wpvivid_debug.zip';

            if (file_exists($path)) {
                @unlink($path);
            }

            if (!class_exists('WPvivid_PclZip'))
                include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';
            $archive = new WPvivid_PclZip($path);

            if (!empty($files)) {
                if (!$archive->add($files, WPVIVID_PCLZIP_OPT_REMOVE_ALL_PATH)) {
                    echo __($archive->errorInfo(true) . ' <a href="' . apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page=' . apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG) . '">retry</a>.');
                    exit;
                }
            }

            global $wpvivid_plugin;
            $server_info = json_encode($wpvivid_plugin->get_website_info());
            $server_file_path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . 'wpvivid_server_info.json';
            if (file_exists($server_file_path)) {
                @unlink($server_file_path);
            }
            $server_file = fopen($server_file_path, 'x');
            fclose($server_file);
            file_put_contents($server_file_path, $server_info);
            if (!$archive->add($server_file_path, WPVIVID_PCLZIP_OPT_REMOVE_ALL_PATH)) {
                echo __($archive->errorInfo(true) . ' <a href="' . apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page=' . apply_filters('wpvivid_white_label_slug', WPVIVID_PRO_PLUGIN_SLUG) . '">retry</a>.');
                exit;
            }
            @unlink($server_file_path);

            $attachments[] = $path;
        }
        else{
            $attachments = array();
        }

        if(wp_mail( $send_to, $subject, $body, $headers, $attachments)===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Unable to send email. Please check the configuration of email server.', 'wpvivid');
        }
        else
        {
            $ret['result']='success';
        }

        if (file_exists($path)) {
            @unlink($path);
        }

        return $ret;
    }

    public function wpvivid_send_debug_info()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            if (!isset($_POST['user_mail']) || empty($_POST['user_mail']))
            {
                $ret['result'] = 'failed';
                $ret['error'] = __('User\'s email address is required.', 'wpvivid');
            } else {
                $pattern = '/^[a-z0-9]+([._-][a-z0-9]+)*@([0-9a-z-]+\.[a-z]{2,14}(\.[a-z]{2})?)$/i';
                if (!preg_match($pattern, $_POST['user_mail'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = __('Please enter a valid email address.', 'wpvivid');
                } else {
                    $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
                    $ret = $this->wpvivid_send_debug_info_addon($_POST['user_mail'],$_POST['server_type'],$_POST['host_provider'],$_POST['comment'],$_POST['enable_debug_attachment']);
                }
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

    public function create_debug_package()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-backup');
        try {
            $log=new WPvivid_Log_Ex_addon();
            $files = $log->get_error_log();
            $staging_files = WPvivid_error_log::get_staging_error_log();

            if (!class_exists('WPvivid_PclZip'))
                include_once WPVIVID_PLUGIN_DIR . '/includes/zip/class-wpvivid-pclzip.php';

            $path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . 'wpvivid_debug.zip';

            if (file_exists($path)) {
                @wp_delete_file($path);
            }
            $archive = new WPvivid_PclZip($path);

            if (!empty($files)) {
                if (!$archive->add($files, WPVIVID_PCLZIP_OPT_REMOVE_ALL_PATH)) {
                    echo esc_html($archive->errorInfo(true)) . ' <a href="' . esc_url(admin_url()) . 'admin.php?page=WPvivid">retry</a>.';
                    exit;
                }
            }

            if (!empty($staging_files)) {
                if (!$archive->add($staging_files, WPVIVID_PCLZIP_OPT_REMOVE_ALL_PATH)) {
                    echo esc_html($archive->errorInfo(true)) . ' <a href="' . esc_url(admin_url()) . 'admin.php?page=WPvivid">retry</a>.';
                    exit;
                }
            }

            global $wpvivid_plugin;
            $server_info = wp_json_encode($wpvivid_plugin->get_website_info());
            $server_file_path = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . 'wpvivid_server_info.json';
            if (file_exists($server_file_path)) {
                @wp_delete_file($server_file_path);
            }
            $server_file = fopen($server_file_path, 'x');
            fclose($server_file);
            file_put_contents($server_file_path, $server_info);
            if (!$archive->add($server_file_path, WPVIVID_PCLZIP_OPT_REMOVE_ALL_PATH)) {
                echo esc_html($archive->errorInfo(true)) . ' <a href="' . esc_url(admin_url()) . 'admin.php?page=WPvivid">retry</a>.';
                exit;
            }
            @wp_delete_file($server_file_path);

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


            ob_end_clean();
            readfile($path);
            @wp_delete_file($path);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function init_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Debug', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons  dashicons-buddicons-replies wpvivid-dashicons-large wpvivid-dashicons-orange"></span><span class="wpvivid-page-title">Debug</span></p>
                                        <span class="about-description">This page provides detailed system information to assist in efficient troubleshooting.</span>
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
                                                    <!-- The content ou need -->
                                                    <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpvivid-nav-bar wpvivid-clear-float">
                                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                                        <span> Tips: You can download the debug information and send it us when you <a href="https://wpvivid.com/submit-ticket">submit a ticket</a>, it will help us troubleshoot the issue more efficiently.</span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <div class="wpvivid-one-coloum">
                                        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                                            <h2 style="padding-left:0;"><span class="dashicons dashicons-email-alt wpvivid-dashicons-green"></span>
                                                <span>Debug Information</span>
                                            </h2>
                                            <table class="widefat">
                                                <thead class="website-info-head">
                                                <tr>
                                                    <th class="row-title" style="min-width: 260px;">Website Info Key</th>
                                                    <th>Website Info Value</th>
                                                </tr>
                                                </thead>
                                                <tbody class="wpvivid-websiteinfo-list" id="wpvivid_websiteinfo_list">
                                                <?php
                                                global $wpvivid_plugin;
                                                $website_info=$wpvivid_plugin->get_website_info();
                                                if(!empty($website_info['data'])){
                                                    foreach ($website_info['data'] as $key=>$value) { ?>
                                                        <?php
                                                        $website_value='';
                                                        if (is_array($value)) {
                                                            foreach ($value as $arr_value) {
                                                                if (empty($website_value)) {
                                                                    $website_value = $website_value . $arr_value;
                                                                } else {
                                                                    $website_value = $website_value . ', ' . $arr_value;
                                                                }
                                                            }
                                                        }
                                                        else{
                                                            if($value === true || $value === false){
                                                                if($value === true) {
                                                                    $website_value = 'true';
                                                                }
                                                                else{
                                                                    $website_value = 'false';
                                                                }
                                                            }
                                                            else {
                                                                $website_value = $value;
                                                            }
                                                        }
                                                        ?>
                                                        <tr>
                                                            <td class="row-title tablelistcolumn"><label for="tablecell"><?php _e($key, 'wpvivid'); ?></label></td>
                                                            <td class="tablelistcolumn"><?php _e($website_value, 'wpvivid'); ?></td>
                                                        </tr>
                                                    <?php }} ?>
                                                </tbody>
                                            </table>
                                            <p></p>
                                            <input class="button-primary" type="submit" id="wpvivid_download_website_info" name="download-website-info" value="Download">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- sidebar -->
                    <?php
                    do_action( 'wpvivid_backup_pro_add_sidebar' );
                    ?>

                </div>
            </div>
        </div>
        <script>
            jQuery('#wpvivid_download_website_info').click(function(){
                wpvivid_download_website_info();
            });

            jQuery("#wpvivid_debug_type").change(function()
            {
                if(jQuery(this).val()=='sharehost')
                {
                    jQuery("#wpvivid_debug_host").show();
                }
                else
                {
                    jQuery("#wpvivid_debug_host").hide();
                }
            });

            function wpvivid_download_website_info(){
                wpvivid_location_href=true;
                location.href =ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_create_debug_package_addon';
            }

            function wpvivid_click_send_debug_info()
            {
                var wpvivid_user_mail = jQuery('#wpvivid_user_mail').val();
                var server_type = jQuery('#wpvivid_debug_type').val();
                var host_provider = jQuery('#wpvivid_host_provider').val();
                var comment = jQuery('#wpvivid_debug_comment').val();
                if(jQuery('#wpvivid_enable_debug_attachment').prop('checked')){
                    var enable_debug_attachment = '1';
                }
                else{
                    var enable_debug_attachment = '0';
                }
                var ajax_data = {
                    'action': 'wpvivid_send_debug_info_addon',
                    'user_mail': wpvivid_user_mail,
                    'server_type':server_type,
                    'host_provider':host_provider,
                    'comment':comment,
                    'enable_debug_attachment': enable_debug_attachment
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success") {
                            alert("Send succeeded.");
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('sending debug information', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_get_ini_memory_limit() {
                var ajax_data = {
                    'action': 'wpvivid_get_ini_memory_limit'
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        jQuery('#wpvivid_websiteinfo_list tr').each(function (i) {
                            jQuery(this).children('td').each(function (j) {
                                if (j == 0) {
                                    if (jQuery(this).html().indexOf('memory_limit') >= 0) {
                                        jQuery(this).next().html(data);
                                    }
                                }
                            });
                        });
                    }
                    catch (err) {
                        setTimeout(function () {
                            wpvivid_get_ini_memory_limit();
                        }, 3000);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        wpvivid_get_ini_memory_limit();
                    }, 3000);
                });
            }
            jQuery(document).ready(function ()
            {
                wpvivid_get_ini_memory_limit();
            });
        </script>
        <?php
    }
}