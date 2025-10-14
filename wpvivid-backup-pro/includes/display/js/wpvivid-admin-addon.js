(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    $(document).ready(function () {

    });

})(jQuery);

function wpvivid_post_request_addon(ajax_data, callback, error_callback, time_out){
    if(typeof time_out === 'undefined')    time_out = 30000;
    ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
    jQuery.ajax({
        type: "post",
        url: wpvivid_ajax_object_addon.ajax_url,
        data: ajax_data,
        cache:false,
        success: function (data) {
            callback(data);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            error_callback(XMLHttpRequest, textStatus, errorThrown);
        },
        timeout: time_out
    });
}

function wpvivid_post_request(ajax_data, callback, error_callback, time_out){
    if(typeof time_out === 'undefined')    time_out = 30000;
    ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
    jQuery.ajax({
        type: "post",
        url: wpvivid_ajax_object_addon.ajax_url,
        data: ajax_data,
        success: function (data) {
            callback(data);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            error_callback(XMLHttpRequest, textStatus, errorThrown);
        },
        timeout: time_out
    });
}

var get_custom_table_retry = get_custom_table_retry || {};
get_custom_table_retry.manual_backup_retry = 0;
get_custom_table_retry.schedule_backup_retry = 0;
get_custom_table_retry.update_schedule_backup_retry = 0;
get_custom_table_retry.migration_backup_retry = 0;
get_custom_table_retry.export_site_retry = 0;
get_custom_table_retry.has_get_db_tables = false;

function wpvivid_refresh_custom_backup_info(parent_id, type){
    wpvivid_get_custom_backup_info(parent_id, type);
    var exec_time = 30 * 60 * 1000;
    setTimeout(function(){
        wpvivid_refresh_custom_backup_info(parent_id, type);
    }, exec_time);
}

function wpvivid_get_custom_backup_info(parent_id, type)
{
    if(type === 'manual_backup')
    {
        jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
    }
    else if(type === 'schedule_backup')
    {
        jQuery('#wpvivid_btn_create_general_schedule').css({'pointer-events': 'none', 'opacity': '0.4'});
    }
    else if(type === 'update_schedule_backup')
    {
        jQuery('#wpvivid_btn_update_general_schedule').css({'pointer-events': 'none', 'opacity': '0.4'});
    }
    else if(type === 'migration_backup')
    {
        jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
    }
    else if(type === 'export_site')
    {
        jQuery('#wpvivid_local_export_site').css({'pointer-events': 'none', 'opacity': '0.4'});
        jQuery('#wpvivid_remote_export_site').css({'pointer-events': 'none', 'opacity': '0.4'});
        jQuery('#wpvivid_migration_export_site').css({'pointer-events': 'none', 'opacity': '0.4'});
    }
    var ajax_data = {
        'action': 'wpvivid_get_database_themes_plugins_table',
        'type': type
    };
    wpvivid_post_request_addon(ajax_data, function (data)
    {
        if(type === 'manual_backup')
        {
            jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'schedule_backup')
        {
            jQuery('#wpvivid_btn_create_general_schedule').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'update_schedule_backup')
        {
            jQuery('#wpvivid_btn_update_general_schedule').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'migration_backup')
        {
            jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'export_site')
        {
            jQuery('#wpvivid_local_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
            jQuery('#wpvivid_remote_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
            jQuery('#wpvivid_migration_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        var jsonarray = jQuery.parseJSON(data);
        if(jsonarray.result == 'success'){
            if(type === 'export_site')
            {
                jQuery('#wpvivid_custom_local_export_site').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_custom_remote_export_site').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_custom_migration_export_site').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_custom_local_export_site').find('.wpvivid-custom-database-info').html(jsonarray.database_html);
                jQuery('#wpvivid_custom_remote_export_site').find('.wpvivid-custom-database-info').html(jsonarray.database_html);
                jQuery('#wpvivid_custom_migration_export_site').find('.wpvivid-custom-database-info').html(jsonarray.database_html);
            }
            else
            {
                jQuery('#'+parent_id).find('.wpvivid-custom-database-info').html('');
                jQuery('#'+parent_id).find('.wpvivid-custom-database-info').html(jsonarray.database_html);
                //jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-info').html('');
                //jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-info').html(jsonarray.themes_plugins_html);
            }
            get_custom_table_retry.has_get_db_tables = true;
        }
    }, function (XMLHttpRequest, textStatus, errorThrown) {
        var need_retry = false;
        if(type === 'manual_backup'){
            get_custom_table_retry.manual_backup_retry++;
            if(get_custom_table_retry.manual_backup_retry < 10){
                need_retry = true;
            }
            jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'schedule_backup'){
            get_custom_table_retry.schedule_backup_retry++;
            if(get_custom_table_retry.schedule_backup_retry < 10){
                need_retry = true;
            }
            jQuery('#wpvivid_btn_create_general_schedule').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'update_schedule_backup'){
            get_custom_table_retry.update_schedule_backup_retry++;
            if(get_custom_table_retry.update_schedule_backup_retry < 10){
                need_retry = true;
            }
            jQuery('#wpvivid_btn_update_general_schedule').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'migration_backup') {
            get_custom_table_retry.migration_backup_retry++;
            if(get_custom_table_retry.migration_backup_retry < 10){
                need_retry = true;
            }
            jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else if(type === 'export_site') {
            get_custom_table_retry.export_site_retry++;
            if(get_custom_table_retry.export_site_retry < 10){
                need_retry = true;
            }
            jQuery('#wpvivid_local_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
            jQuery('#wpvivid_remote_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
            jQuery('#wpvivid_migration_export_site').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        if(need_retry){
            setTimeout(function(){
                wpvivid_get_custom_backup_info(parent_id, type);
            }, 3000);
        }
        else{
            var refresh_btn = '<input type="submit" class="button-primary" value="Refresh" onclick="wpvivid_refresh_custom_database(\''+parent_id+'\', \''+type+'\');">';

            if(type === 'export_site')
            {
                jQuery('#wpvivid_custom_local_export_site').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_custom_remote_export_site').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_custom_migration_export_site').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_custom_local_export_site').find('.wpvivid-custom-database-info').html(refresh_btn);
                jQuery('#wpvivid_custom_remote_export_site').find('.wpvivid-custom-database-info').html(refresh_btn);
                jQuery('#wpvivid_custom_migration_export_site').find('.wpvivid-custom-database-info').html(refresh_btn);
            }
            else
            {
                jQuery('#'+parent_id).find('.wpvivid-custom-database-info').html('');
                jQuery('#'+parent_id).find('.wpvivid-custom-database-info').html(refresh_btn);
                jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-info').html('');
                jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-info').html(refresh_btn);
            }
        }
    });
}

function wpvivid_refresh_custom_database(parent_id, type){
    if(type === 'manual_backup'){
        get_custom_table_retry.manual_backup_retry = 0;
    }

    var custom_database_loading = '<div class="spinner is-active wpvivid-database-loading" style="margin: 0 5px 10px 0; float: left;"></div>' +
        '<div style="float: left;">Archieving database tables</div>' +
        '<div style="clear: both;"></div>';
    jQuery('#'+parent_id).find('.wpvivid-custom-database-info').html('');
    jQuery('#'+parent_id).find('.wpvivid-custom-database-info').html(custom_database_loading);

    var custom_themes_plugins_loading = '<div class="spinner is-active wpvivid-themes-plugins-loading" style="margin: 0 5px 10px 0; float: left;"></div>' +
        '<div style="float: left;">Archieving themes and plugins</div>' +
        '<div style="clear: both;"></div>';
    jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-info').html('');
    jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-info').html(custom_themes_plugins_loading);

    wpvivid_get_custom_backup_info(parent_id, type);
}

function wpvivid_ajax_data_transfer_addon(data_type){
    var json = {};
    jQuery('input:checkbox[option='+data_type+']').each(function() {
        var value = '0';
        var key = jQuery(this).prop('name');
        if(jQuery(this).prop('checked')) {
            value = '1';
        }
        else {
            value = '0';
        }
        json[key]=value;
    });
    jQuery('input:radio[option='+data_type+']').each(function() {
        if(jQuery(this).prop('checked'))
        {
            var key = jQuery(this).prop('name');
            var value = jQuery(this).prop('value');
            json[key]=value;
        }
    });
    jQuery('input:text[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    jQuery('textarea[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    jQuery('input:password[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    jQuery('select[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    return JSON.stringify(json);
}