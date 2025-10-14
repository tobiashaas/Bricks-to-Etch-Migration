<?php

/**
 * @link              https://wpvivid.com
 * @since             1.9.0
 * @package           wpvivid
 *
 * @wordpress-plugin
 * Plugin Name:       WPvivid Plugins Pro
 * Description:       A centralized dashboard for accessing and managing all WPvivid plugins.
 * Version:           2.2.37
 * Author:            wpvivid.com
 * Author URI:        https://wpvivid.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/copyleft/gpl.html
 * Text Domain:       wpvivid
 * Domain Path:       /languages
 */

define('WPVIVID_BACKUP_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('WPVIVID_BACKUP_PRO_PLUGIN_URL', plugins_url('/',__FILE__));
define('WPVIVID_BACKUP_PRO_VERSION','2.2.37');

define('WPVIVID_PRO_PLUGIN_SLUG','WPvivid');

define('WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT', 30);
define('WPVIVID_DEFAULT_REMOTE_BACKUP_COUNT', 30);
define('WPVIVID_DEFAULT_INCREMENTAL_REMOTE_BACKUP_COUNT', 3);

define('WPVIVID_PRO_RESTORE_INIT','init');
define('WPVIVID_PRO_RESTORE_COMPLETED','completed');
define('WPVIVID_PRO_RESTORE_ERROR','error');
define('WPVIVID_PRO_RESTORE_WAIT','wait');

define('WPVIVID_PRO_MEMORY_LIMIT','512M');
define('WPVIVID_PRO_RESTORE_MEMORY_LIMIT','256M');
define('WPVIVID_PRO_MIGRATE_SIZE', '2048');

define('WPVIVID_PRO_TASK_MONITOR_EVENT','wpvivid_task_monitor_event');
define('WPVIVID_PRO_RESUME_RETRY_TIMES',6);
define('WPVIVID_PRO_REMOTE_CONNECT_RETRY_TIMES','3');
define('WPVIVID_PRO_REMOTE_CONNECT_RETRY_INTERVAL','3');

define('WPVIVID_PRO_SUCCESS','success');
define('WPVIVID_PRO_FAILED','failed');

define('WPVIVID_PRO_BACKUP_TYPE_DB','backup_db');
define('WPVIVID_PRO_BACKUP_TYPE_THEMES','backup_themes');
define('WPVIVID_PRO_BACKUP_TYPE_PLUGIN','backup_plugin');
define('WPVIVID_PRO_BACKUP_TYPE_UPLOADS','backup_uploads');
define('WPVIVID_PRO_BACKUP_TYPE_UPLOADS_FILES','backup_uploads_files');
define('WPVIVID_PRO_BACKUP_TYPE_CONTENT','backup_content');
define('WPVIVID_PRO_BACKUP_TYPE_CORE','backup_core');
define('WPVIVID_PRO_BACKUP_TYPE_OTHERS','backup_others');
define('WPVIVID_PRO_BACKUP_TYPE_MERGE','backup_merge');
define('WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT','wp-content');
define('WPVIVID_PRO_BACKUP_ROOT_CUSTOM','custom');
define('WPVIVID_PRO_BACKUP_ROOT_WP_ROOT','root');
define('WPVIVID_PRO_BACKUP_ROOT_WP_UPLOADS','uploads');


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
{
    die;
}

function wpvivid_pro_plugin_activate()
{
    add_option('wpvivid_pro_do_activation_redirect', true);
}

function wpvivid_pro_init_plugin_redirect()
{
    if (get_option('wpvivid_pro_do_activation_redirect', false))
    {
        delete_option('wpvivid_pro_do_activation_redirect');
        $url=apply_filters('wpvivid_backup_pro_activate_redirect_url','admin.php?page='.strtolower(sprintf('%s-dashboard', apply_filters('wpvivid_white_label_slug', 'wpvivid'))) );
        if (is_multisite())
        {
            wp_redirect(network_admin_url().$url);
        }
        else
        {
            wp_redirect(admin_url().$url);
        }
    }
}

function wpvivid_disable_plugin_redirect()
{
    if(get_option('wpvivid_do_activation_redirect', false))
    {
        delete_option('wpvivid_do_activation_redirect');
    }
}

register_activation_hook(__FILE__, 'wpvivid_pro_plugin_activate');
add_action('admin_init', 'wpvivid_pro_init_plugin_redirect');
add_action('plugins_loaded','wpvivid_disable_plugin_redirect');
require WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-backup-pro.php';

function run_wpvivid_backup_pro()
{
    $wpvivid_backup_pro=new WPvivid_backup_pro();
    $GLOBALS['wpvivid_backup_pro'] = $wpvivid_backup_pro;
}
run_wpvivid_backup_pro();