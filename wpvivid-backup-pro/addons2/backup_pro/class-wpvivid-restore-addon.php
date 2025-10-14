<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_Restore_addon
 */

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPvivid_Incremental_Files_Restore_List_Ex extends WP_List_Table
{
    public $page_num;
    public $versions_list;
    public $backup_id;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'imcremental_files_restore',
                'screen' => 'imcremental_files_restore'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped' );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb'])) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox"/>';
            $cb_counter++;
        }

        foreach ( $columns as $column_key => $column_display_name )
        {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) )
            {
                $class[] = 'hidden';
            }

            if ( $column_key === $primary )
            {
                $class[] = 'column-primary';
            }

            if ( $column_key === 'cb' )
            {
                $class[] = 'check-column';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( ! empty( $class ) )
            {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function set_versions($versions_list,$page_num=1)
    {
        $this->versions_list=$versions_list;
        $this->page_num=$page_num;
    }

    public function get_columns()
    {
        $columns = array();
        $columns['wpvivid_create_date'] = __( 'Creation Date', 'wpvivid' );
        $columns['wpvivid_type'] = __( 'Type', 'wpvivid' );
        return $columns;
    }

    public function _column_wpvivid_create_date( $version )
    {
        if($version['version']==0)
        {
            $html='<td><label><input type="radio" option="restore_options" name="restore_version" value="'.$version['version'].'" checked><span class="dashicons dashicons-clock wpvivid-dashicons-blue"></span><span>'.$version['date'].'</span></label></td>';
        }
        else
        {
            $html='<td><label><input type="radio" option="restore_options" name="restore_version" value="'.$version['version'].'"><span class="dashicons dashicons-clock wpvivid-dashicons-blue"></span><span>'.$version['date'].'</span></label></td>';
        }

        echo $html;
    }

    public function _column_wpvivid_type( $version )
    {
        if($version['version']==0)
        {
            $html='<td><span>Full Backup</span></td>';
        }
        else
        {
            $html='<td><span>Incremental</span></td>';
        }

        echo $html;
    }

    public function get_pagenum()
    {
        if($this->page_num=='first')
        {
            $this->page_num=1;
        }
        else if($this->page_num=='last')
        {
            $this->page_num=$this->_pagination_args['total_pages'];
        }
        $pagenum = $this->page_num ? $this->page_num : 0;

        if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
        {
            $pagenum = $this->_pagination_args['total_pages'];
        }

        return max( 1, $pagenum );
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $total_items =sizeof($this->versions_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->versions_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->versions_list);
    }

    private function _display_rows($versions_list)
    {
        $page=$this->get_pagenum();

        $page_file_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_file_list = array_splice( $versions_list, 0, 10);
            $count++;
        }
        foreach ( $page_file_list as $key=>$file)
        {
            $this->single_row($file);
        }
    }

    public function single_row($version)
    {
        ?>
        <tr slug="<?php echo $version['version']?>" type="incremental">
            <?php $this->single_row_columns( $version ); ?>
        </tr>
        <?php
    }

    protected function pagination( $which )
    {
        if ( empty( $this->_pagination_args ) )
        {
            return;
        }

        $total_items     = $this->_pagination_args['total_items'];
        $total_pages     = $this->_pagination_args['total_pages'];
        $infinite_scroll = false;
        if ( isset( $this->_pagination_args['infinite_scroll'] ) )
        {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ( 'top' === $which && $total_pages > 1 )
        {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }

        $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

        $current              = $this->get_pagenum();

        $page_links = array();

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='first-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='prev-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector-filelist' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-filelist" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        $page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='next-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='last-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }

    protected function display_tablenav( $which ) {
        $css_type = '';
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
            $css_type = 'margin: 0 0 10px 0';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'margin: 10px 0 0 0';
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>

                <br class="clear" />
            </div>
            <?php
        }
    }

    public function display()
    {
        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
            <thead>
            <tr>
                <?php //$this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo " data-wp-lists='list:$singular'";
                }
                ?>
            >
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

        </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }

    public function display_rows_or_placeholder() {
        if ( $this->has_items() ) {
            $this->display_rows();
        } else {
            echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
            _e( 'There is no \'Incremental Backup\' created.' );
            echo '</td></tr>';
        }
    }
}


class WPvivid_Restore_addon
{
    public $main_tab;
    public $log;
    public $end_shutdown_function;
    public $backup_id;
    public $backup_data;

    public function __construct()
    {
        add_action('wp_ajax_wpvivid_init_restore_task',array($this,'init_restore_task'));
        add_action('wp_ajax_wpvivid_reset_plugin',array($this,'reset_plugin'));
        //
        add_action('wp_ajax_wpvivid_do_restore',array($this,'do_restore'));
        add_action('wp_ajax_nopriv_wpvivid_do_restore',array( $this,'do_restore'));

        add_action('wp_ajax_wpvivid_get_restore_progress_ex',array( $this,'get_restore_progress'));
        add_action('wp_ajax_nopriv_wpvivid_get_restore_progress_ex',array( $this,'get_restore_progress'));

        add_action('wp_ajax_wpvivid_finish_restore',array( $this,'finish_restore'));
        add_action('wp_ajax_nopriv_wpvivid_finish_restore',array( $this,'finish_restore'));

        add_action('wp_ajax_wpvivid_restore_failed',array( $this,'restore_failed'));
        add_action('wp_ajax_nopriv_wpvivid_restore_failed',array( $this,'restore_failed'));
        //
        add_action('wp_ajax_wpvivid_view_restore_log_ex', array($this, 'view_log_ex'));

        //add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        //add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        add_action('wpvivid_output_restore_page',array($this, 'init_page'));
        add_action('wp_ajax_wpvivid_restore_retrieve_to_local',array($this,'retrieve_backup_to_local'));
        add_action('wp_ajax_wpvivid_download_restore_file_ex',array($this,'download_restore_file'));
        add_action('wp_ajax_wpvivid_get_download_restore_progress_ex',array($this,'get_download_restore_progress_ex'));

        add_action('wp_ajax_wpvivid_get_restore_version_page_ex2',array($this,'get_restore_version_page'));
        //
        add_action('wp_ajax_wpvivid_init_restore_page_step2', array($this, 'init_restore_page_step2'));

    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-test-restore';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-test-restore';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $submenu['parent_slug'] = $parent_slug;
        $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Test Restoration');
        $submenu['menu_title'] = 'Test Restoration';
        $submenu['capability'] = 'administrator';
        $submenu['menu_slug'] = strtolower(sprintf('%s-test-restore', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $submenu['index'] = 5;
        $submenu['function'] = array($this, 'init_page');
        $submenus[$submenu['menu_slug']] = $submenu;
        return $submenus;
    }

    public function init_page()
    {
        if (!function_exists('get_plugins'))
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!function_exists('request_filesystem_credentials'))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('get_plugins'))
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!function_exists('request_filesystem_credentials'))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if(isset($_REQUEST['backup_id']))
        {
            $this->backup_id=sanitize_key($_REQUEST['backup_id']);
        }
        else
        {
            return;
        }

        $this->init_backup_data();
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Restoration', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <div class="wpvivid-one-coloum" style="background:#eaf1fe;">
                                        <div style="font-size:2em; font-weight:800;text-align:center;padding-top:2em;margin-bottom:1em;">
                                            <span>Restoration</span>
                                        </div>
                                        <div>
                                            <div class="wpvivid-stepper-wrapper">
                                                <div id="wpvivid_restore_step1" class="wpvivid-stepper-item completed">
                                                    <div class="wpvivid-step-counter">1</div>
                                                    <div class="wpvivid-step-name">Prepare</div>
                                                </div>
                                                <div id="wpvivid_restore_step2" class="wpvivid-stepper-item active">
                                                    <div class="wpvivid-step-counter">2</div>
                                                    <div class="wpvivid-step-name">Restore</div>
                                                </div>
                                                <div id="wpvivid_restore_step3" class="wpvivid-stepper-item active">
                                                    <div class="wpvivid-step-counter">3</div>
                                                    <div class="wpvivid-step-name">Done</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    $this->restore_step1();
                                    $this->restore_step2();
                                    $this->restore_step3();
                                    ?>
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
            var backup_id='<?php echo $this->backup_id?>';
            var init_step2=false;
            jQuery('#wpvivid_restore_next_step_2').click(function()
            {
                jQuery('#wpvivid_restore_page_1').hide();
                jQuery('#wpvivid_restore_page_2').show();

                jQuery('#wpvivid_restore_step1').removeClass('completed');
                jQuery('#wpvivid_restore_step1').addClass('active');

                jQuery('#wpvivid_restore_step2').removeClass('active');
                jQuery('#wpvivid_restore_step2').addClass('completed');

                jQuery('#wpvivid_restore_step3').removeClass('completed');
                jQuery('#wpvivid_restore_step3').addClass('active');

                <?php
                if(isset($this->backup_data['has_version'])&&$this->backup_data['has_version']==true)
                {
                    ?>
                    jQuery('#wpvivid_restore_version_part').show();
                    jQuery('#wpvivid_restore_version_select').show();
                    jQuery('#wpvivid_restore_folders_part').hide();
                    jQuery('#wpvivid_restore_now').hide();
                    jQuery('#wpvivid_restore_version_select').show();
                    //
                    <?php
                }
                ?>

                if(init_step2==false)
                {
                    init_step2=true;
                    wpvivid_init_restore_page_step2();
                }
            });

            function wpvivid_init_restore_page_step2()
            {
                var ajax_data = {
                    'action':'wpvivid_init_restore_page_step2',
                    'backup_id':backup_id
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);

                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_restore_info').html(jsonarray.html);
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('init restore page', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('.wpvivid_restore_detail').click(function()
            {
                jQuery('#'+jQuery(this).data("id")).toggle();
            });

            jQuery('#wpvivid_restore_info').on('click', '#wpvivid_restore_prev_step_1', function()
            {
                jQuery('#wpvivid_restore_page_1').show();
                jQuery('#wpvivid_restore_page_2').hide();

                jQuery('#wpvivid_restore_step1').removeClass('active');
                jQuery('#wpvivid_restore_step1').addClass('completed');

                jQuery('#wpvivid_restore_step2').removeClass('completed');
                jQuery('#wpvivid_restore_step2').addClass('active');

                jQuery('#wpvivid_restore_step3').removeClass('completed');
                jQuery('#wpvivid_restore_step3').addClass('active');
            });


            function wpvivid_ajax_data_transfer_restore(data_type)
            {
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
                return json;
            }

            jQuery('#wpvivid_restore_info').on('click', '#wpvivid_restore_now', function()
            {
                wpvivid_init_restore();
            });

            function wpvivid_init_restore()
            {
                var restore_options = {};
                var selected={};
                var plugin_check=false;
                var themes_check=false;
                var databases_check=false;
                var additional_databases=false;

                jQuery('input:checkbox[option=restore_options][name=wp-core]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        selected['wp-core']=1;
                    }
                    else
                    {
                        selected['wp-core']=0;
                    }
                });
                jQuery('input:checkbox[option=restore_options][name=wp-content]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        selected['wp-content']=1;
                    }
                    else
                    {
                        selected['wp-content']=0;
                    }
                });
                jQuery('input:checkbox[option=restore_options][name=upload]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        selected['upload']=1;
                    }
                    else
                    {
                        selected['upload']=0;
                    }
                });

                jQuery('input:checkbox[option=restore_options][name=custom]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        selected['custom']=1;
                    }
                    else
                    {
                        selected['custom']=0;
                    }
                });

                //
                jQuery('input:checkbox[option=restore_options][name=databases]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        databases_check=true;
                        selected['databases']=1;
                    }
                    else
                    {
                        selected['databases']=0;
                    }
                });
                //
                jQuery('input:checkbox[option=restore_options][name=additional_databases]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        additional_databases=true;
                        selected['additional_databases']=1;
                    }
                    else
                    {
                        selected['additional_databases']=0;
                    }
                });

                jQuery('input:checkbox[option=restore_options][name=plugins]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        plugin_check=true;
                        selected['plugin']=1;
                    }
                    else
                    {
                        selected['plugin']=0;
                    }
                });
                jQuery('input:checkbox[option=restore_options][name=themes]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        themes_check=true;
                        selected['themes']=1;
                    }
                    else
                    {
                        selected['themes']=0;
                    }
                });

                jQuery('input:radio[option=restore_options][name=restore_version]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var value = jQuery(this).prop('value');
                        restore_options['restore_version']=value;
                    }
                });

                restore_options['restore_detail_options']=wpvivid_ajax_data_transfer_restore('restore_detail_options');

                if(restore_options['restore_detail_options']['restore_level']=='custom')
                {
                    restore_options['restore_custom_detail_options']=wpvivid_ajax_data_transfer_restore('restore_custom_detail_options');
                }
                restore_options['selected']=selected;

                if(jQuery('input:checkbox[option=restore_option_delete_local][name=delete_local]').prop('checked'))
                {
                    restore_options['delete_local']='1';
                }
                else
                {
                    restore_options['delete_local']='0';
                }

                if(plugin_check)
                {
                    var plugin_options= {};
                    var remove_plugins={};
                    jQuery('input:checkbox[option=restore_plugin_options]').each(function () {
                        var value = '0';
                        var key = jQuery(this).prop('name');
                        if (jQuery(this).prop('checked'))
                        {
                        } else {
                            remove_plugins[key] = value;
                        }
                    });
                    plugin_options['remove_plugins']=remove_plugins;
                    restore_options['plugin']=plugin_options;
                }

                if(themes_check)
                {
                    var themes_options= {};
                    var remove_themes={};
                    jQuery('input:checkbox[option=restore_themes_options]').each(function () {
                        var value = '0';
                        var key = jQuery(this).prop('name');
                        if (jQuery(this).prop('checked'))
                        {
                        } else {
                            remove_themes[key] = value;
                        }
                    });
                    themes_options['remove_themes']=remove_themes;
                    restore_options['themes']=themes_options;
                }

                if(databases_check)
                {
                    var tables_options= {};
                    var exclude_tables={};
                    jQuery('input:checkbox[option=restore_tables_options]').each(function () {
                        var value = '0';
                        var key = jQuery(this).prop('name');
                        if (jQuery(this).prop('checked'))
                        {
                        } else {
                            tables_options[key] = value;
                        }
                    });
                    exclude_tables['exclude_tables']=tables_options;
                    restore_options['databases']=exclude_tables;
                    console.log(restore_options);
                }

                if(additional_databases)
                {
                    var additional_databases_options= {};

                    jQuery('input:text[option=additional_databases]').each(function(){
                        var obj = {};
                        var key = jQuery(this).prop('name');
                        var value = jQuery(this).val();
                        additional_databases_options[key]=value;
                    });

                    jQuery('input:password[option=additional_databases]').each(function(){
                        var obj = {};
                        var key = jQuery(this).prop('name');
                        var value = jQuery(this).val();
                        additional_databases_options[key]=value;
                    });

                    restore_options['additional_databases']=additional_databases_options;
                }
                var ajax_data = {
                    'action':'wpvivid_init_restore_task',
                    'backup_id': backup_id,
                    'restore_options':restore_options
                };

                jQuery('#wpvivid_restore_progress').show();
                jQuery('#wpvivid_restore_themes_info').hide();
                jQuery('#wpvivid_restore_plugins_info').hide();
                jQuery('#wpvivid_restore_tables_info').hide();
                jQuery('#wpvivid_restore_info *').attr('disabled', true);

                jQuery('.wpvivid_restore_progress').show();
                jQuery('.wpvivid_restore_detail').show();
                jQuery('.wpvivid_restore_progress_detail').show();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            wpvivid_do_restore();
                        }
                        else {
                            jQuery('#wpvivid_restore_info *').attr('disabled', false);
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('init restore task', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_reset_plugin()
            {
                var ajax_data = {
                    'action':'wpvivid_reset_plugin'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                        }
                        else {
                        }
                    }
                    catch (err)
                    {
                        //alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    //var error_message = wpvivid_output_ajaxerror('init restore task', textStatus, errorThrown);
                    //alert(error_message);
                });
            }

            function wpvivid_do_restore()
            {
                var ajax_data = {
                    'action':'wpvivid_do_restore',
                    'wpvivid_restore':'1'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    setTimeout(function(){
                        wpvivid_get_restore_progress();
                    }, 1000);
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_restore_progress();
                });
            }

            function wpvivid_get_restore_progress()
            {
                var ajax_data = {
                    'action':'wpvivid_get_restore_progress_ex',
                    'wpvivid_restore':'1'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            wpvivid_output_progress(jsonarray);

                            if(jsonarray.status=='ready')
                            {
                                wpvivid_do_restore();
                            }
                            else if(jsonarray.status=='sub task finished')
                            {
                                wpvivid_do_restore();
                            }
                            else if(jsonarray.status=='task finished')
                            {
                                wpvivid_finish_restore();
                            }
                            else if(jsonarray.status=='doing sub task')
                            {
                                setTimeout(function(){
                                    wpvivid_get_restore_progress();
                                }, 2000);
                            }
                            else if(jsonarray.status=='no response')
                            {
                                setTimeout(function(){
                                    wpvivid_get_restore_progress();
                                }, 2000);
                            }
                        }
                        else {
                            wpvivid_restore_failed();
                        }
                    }
                    catch (err)
                    {
                        setTimeout(function(){
                            wpvivid_get_restore_progress();
                        }, 2000);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function(){
                        wpvivid_get_restore_progress();
                    }, 2000);
                });
            }

            function wpvivid_restore_failed()
            {
                var ajax_data = {
                    'action':'wpvivid_restore_failed',
                    'wpvivid_restore':'1'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    jQuery('#wpvivid_restore_page_1').hide();
                    jQuery('#wpvivid_restore_page_2').hide();
                    jQuery('#wpvivid_restore_page_3').show();
                    jQuery('#wpvivid_restore_failed').show();
                    jQuery('#wpvivid_restore_success').hide();

                    jQuery('#wpvivid_restore_failed_msg').html(data);

                    jQuery('#wpvivid_restore_step1').removeClass('completed');
                    jQuery('#wpvivid_restore_step1').addClass('active');

                    jQuery('#wpvivid_restore_step2').removeClass('completed');
                    jQuery('#wpvivid_restore_step2').addClass('active');

                    jQuery('#wpvivid_restore_step3').removeClass('active');
                    jQuery('#wpvivid_restore_step3').addClass('completed');

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_restore_page_1').hide();
                    jQuery('#wpvivid_restore_page_2').hide();
                    jQuery('#wpvivid_restore_page_3').show();
                    jQuery('#wpvivid_restore_failed').show();
                    jQuery('#wpvivid_restore_success').hide();
                    jQuery('#wpvivid_restore_failed_msg').html(XMLHttpRequest.responseText);

                    jQuery('#wpvivid_restore_step1').removeClass('completed');
                    jQuery('#wpvivid_restore_step1').addClass('active');

                    jQuery('#wpvivid_restore_step2').removeClass('completed');
                    jQuery('#wpvivid_restore_step2').addClass('active');

                    jQuery('#wpvivid_restore_step3').removeClass('active');
                    jQuery('#wpvivid_restore_step3').addClass('completed');
                });
            }

            function wpvivid_finish_restore()
            {
                var ajax_data = {
                    'action':'wpvivid_finish_restore',
                    'wpvivid_restore':'1'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    jQuery('#wpvivid_restore_page_1').hide();
                    jQuery('#wpvivid_restore_page_2').hide();
                    jQuery('#wpvivid_restore_page_3').show();
                    jQuery('#wpvivid_restore_success').show();
                    jQuery('#wpvivid_restore_failed').hide();

                    jQuery('#wpvivid_restore_finished_msg').html(data);

                    jQuery('#wpvivid_restore_step1').removeClass('completed');
                    jQuery('#wpvivid_restore_step1').addClass('active');

                    jQuery('#wpvivid_restore_step2').removeClass('completed');
                    jQuery('#wpvivid_restore_step2').addClass('active');

                    jQuery('#wpvivid_restore_step3').removeClass('active');
                    jQuery('#wpvivid_restore_step3').addClass('completed');

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_restore_page_1').hide();
                    jQuery('#wpvivid_restore_page_2').hide();
                    jQuery('#wpvivid_restore_page_3').show();
                    jQuery('#wpvivid_restore_success').show();
                    jQuery('#wpvivid_restore_failed').hide();

                    jQuery('#wpvivid_restore_finished_msg').html(XMLHttpRequest.responseText);

                    jQuery('#wpvivid_restore_step1').removeClass('completed');
                    jQuery('#wpvivid_restore_step1').addClass('active');

                    jQuery('#wpvivid_restore_step2').removeClass('completed');
                    jQuery('#wpvivid_restore_step2').addClass('active');

                    jQuery('#wpvivid_restore_step3').removeClass('active');
                    jQuery('#wpvivid_restore_step3').addClass('completed');
                });
            }

            function wpvivid_output_progress(jsonarray)
            {
                jQuery('#wpvivid_main_progress').html(jsonarray.main_progress);

                for (var key in  jsonarray.sub_tasks_progress)
                {
                    if (!jsonarray.sub_tasks_progress.hasOwnProperty(key))
                        continue;

                    var obj = jsonarray.sub_tasks_progress[key];
                    jQuery('#'+key).html(obj);
                }

                for (var key in  jsonarray.sub_tasks_progress_detail)
                {
                    if (!jsonarray.sub_tasks_progress_detail.hasOwnProperty(key))
                        continue;

                    var obj = jsonarray.sub_tasks_progress_detail[key];
                    if(obj.show)
                    {
                        jQuery('#'+key).show();
                    }
                    else
                    {
                        jQuery('#'+key).hide();
                    }
                    jQuery('#'+key).html(obj.html);
                }
            }

            jQuery('#wpvivid_restore_page_2').on("click",'.first-page',function()
            {
                wpvivid_restore_version_change_page('first');
            });

            jQuery('#wpvivid_restore_page_2').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_restore_version_change_page(page-1);
            });

            jQuery('#wpvivid_restore_page_2').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_restore_version_change_page(page+1);
            });

            jQuery('#wpvivid_restore_page_2').on("click",'.last-page',function()
            {
                wpvivid_restore_version_change_page('last');
            });

            jQuery('#wpvivid_restore_page_2').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_restore_version_change_page(page);
                }
            });

            function wpvivid_restore_version_change_page(page)
            {
                var ajax_data = {
                    'action':'wpvivid_get_restore_version_page_ex2',
                    'backup_id':backup_id,
                    'page':page
                };

                jQuery('#wpvivid_restore_version_selector').html('');

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_restore_version_selector').html(jsonarray.html);
                        }
                        else{
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('initializing download information', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function init_backup_data()
    {
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($this->backup_id);
        if($backup===false)
        {
            $this->backup_data=false;
            return;
        }

        $this->backup_data['create_time']=date('M-d-Y H:i', $backup['create_time']);
        $this->backup_data['comment']=isset($backup['backup_prefix'])?$backup['backup_prefix']:'N/A';
        $this->backup_data['type']=$backup['type'];
        if(isset($backup['remote'])&&!empty($backup['remote']))
            $this->backup_data['location']='Cloud Storage';
        else
            $this->backup_data['location']='Localhost';
        $this->backup_data['size']=0;
        foreach ($backup['backup']['files'] as $file_name=>$file_data)
        {
            $this->backup_data['size']+=$file_data['size'];
        }

        $backup_item = new WPvivid_New_Backup_Item($backup);
        $ret = $backup_item->check_backup_files();
        if($ret['result']=='need_download')
        {
            $this->backup_data['need_download']=true;
        }
        else
        {
            $this->backup_data['need_download']=false;
        }

        $this->backup_data['php_version']=false;
        $this->backup_data['mysql_version']=false;
        $this->backup_data['wp_version']=false;

        if($this->backup_data['need_download']===true)
        {
            $this->backup_data['restore_info']=false;
        }
        else
        {
            $this->get_backup_data($backup_item);
            $this->get_backup_zero_date($backup_item);
            $this->get_backup_is_mu($backup_item);
            return;
            /*
            $backup_file_info=$this->get_restore_files_info($backup_item,false,true);

            $root_path=$backup_item->get_local_path();
            $offset = get_option('gmt_offset');
            foreach ($backup_file_info as $type=>$files_info)
            {
                $this->backup_data['restore_info'][$type]['size']=0;

                if($type=='databases'||$type=='db')
                {
                    $this->backup_data['has_db']=true;
                }
                if($type=='wp-core')
                {
                    $this->backup_data['has_core']=true;
                }
                foreach ($files_info['files'] as $file)
                {
                    if(isset($file['options']['php_version']))
                    {
                        //7.3.27-1~deb10u1
                        preg_match("/((?:[0-9]+\.?)+)/i",  $file['options']['php_version'], $matches);
                        $this->backup_data['php_version']= $matches[1];
                    }
                    if(isset($file['options']['mysql_version']))
                    {
                        $this->backup_data['mysql_version']= $file['options']['mysql_version'];
                    }

                    if(isset($file['options']['wp_version']))
                    {
                        $this->backup_data['wp_version']=$file['options']['wp_version'];
                    }

                    if(file_exists($root_path.$file['file_name']))
                    {
                        $this->backup_data['restore_info'][$type]['size']+=filesize($root_path.$file['file_name']);
                    }

                    if(isset($file['options']['themes']))
                    {
                        $this->backup_data['restore_info'][$type]['themes']=$file['options']['themes'];
                    }

                    if(isset($file['options']['plugin']))
                    {
                        $this->backup_data['restore_info'][$type]['plugins']=$file['options']['plugin'];
                    }

                    if(isset($file['options']['tables']))
                    {
                        $this->backup_data['restore_info'][$type]['tables']=$file['options']['tables'];
                    }

                    if(isset($file['has_version'])&&$file['has_version']==true)
                    {
                        $this->backup_data['has_version']=true;
                        $version=$file['version'];
                        $localtime = $file['options']['backup_time'] + $offset * 60 * 60;
                        $localtime = __(date('M d, Y H:i', $localtime));
                        $this->backup_data['versions'][$version]['version']=$version;
                        $this->backup_data['versions'][$version]['date']=$localtime;
                    }

                    if(isset($file['options']['is_crypt'])&&$file['options']['is_crypt']==1)
                    {
                        $this->backup_data['is_db_crypt']=true;
                    }
                }
            }
            */
        }
    }

    public function restore_step1()
    {
        ?>
        <div class="wpvivid-one-coloum" id="wpvivid_restore_page_1">
            <div style="display:none;">
                <div style="padding-bottom:1em;border-bottom:1px solid #eaf1fe;">
                    <h2>Step One: Prepare for Restoring</h2>
                </div>
            </div>
            <?php $this->show_backup_info($this->backup_id);  ?>
        </div>
        <?php
    }

    public function show_backup_info($backup_id)
    {
        ?>
        <?php
        if(isset($this->backup_data['find_zero_date']) && $this->backup_data['find_zero_date'])
        {
            $db_method = new WPvivid_DB_Method();
            $ret_sql_mode = $db_method->get_sql_mode();
            if(preg_match('/NO_ZERO_DATE/', $ret_sql_mode['mysql_mode']))
            {
                ?>
                <div id="wpvivid_restore_warning_info" class="wpvivid-one-coloum" style="border:2px solid orange; margin-bottom:1em; border-radius:0.3em;">
                    <span style="color:orange;">Warning: We have detected that the backup contains zero dates '0000-00-00', while NO_ZERO_DATE which forbids zero dates is enabled on the current server, which may cause a restore failure. It is recommended to temporarily disable NO_ZERO_DATE on the server.</span>
                </div>
                <?php
            }
        }

        if(is_multisite())
        {
            if(isset($this->backup_data['is_mu_site']) && !$this->backup_data['is_mu_site'])
            {
                ?>
                <div id="wpvivid_restore_warning_info" class="wpvivid-one-coloum" style="border:2px solid orange; margin-bottom:1em; border-radius:0.3em;">
                    <span style="color:orange;">Warning: We've detected that the site in the backup is a single-site WordPress installation, but your current site is a Multisite network. WPvivid currently does not support restoring backups from single-site installations to Multisite networks.</span>
                </div>
                <?php
            }
        }

        ?>
        <div id="wpvivid_restore_warning_info" class="wpvivid-one-coloum" style="border:2px solid orange; margin-bottom:1em; border-radius:0.3em;display: none">
            <span style="color:orange;">Warning: </span><span id="wpvivid_restore_warning_msg"></span>
        </div>
        <div class="wpvivid-two-col" style="padding: 0 1em;">
            <p><span><strong>Creation Date: </strong></span><span><strong><?php echo $this->backup_data['create_time'];?></strong></span><p>
            <p><span>Type: </span><span><?php echo $this->backup_data['type'];?></span><p>
            <p><span>Comment: </span><span><?php echo $this->backup_data['comment'];?></span><p>
            <p><span>Backup Size: </span><span><?php echo size_format($this->backup_data['size'],2);?></span><p>
            <p>
                <span>Backup Location: </span><span><?php echo $this->backup_data['location'];?></span>
                <?php
                if($this->backup_data['need_download'])
                {
                    ?>
                    <span id="wpvivid_need_download_progress_btn">
                        <input id="wpvivid_restore_retrieve_to_local" type="submit" class="button action top-action" value="Retrieve to localhost">
                    </span>
                    <?php
                }
                ?>
            </p>
        </div>
        <?php
        if($this->backup_data['need_download'])
        {
            $php_version=phpversion();
            preg_match("/((?:[0-9]+\.?)+)/i",  $php_version, $matches);
            global $wpdb;
            $mysql_version = $wpdb->db_version();
            $backup_mysql_version='NULL';
            $backup_php_version='NULL';
            $backup_wp_version='NULL';
            $wp_version = get_bloginfo( 'version' );
            ?>
            <div id="wpvivid_restore_backup_detail" class="wpvivid-two-col" style="padding: 0 1em;">
                <div style="background:#f1f1f1;padding:1em;"><span><strong>System Information:</strong></span></div>
                <div id="wpvivid_restore_system_info">
                    <div class="wpvivid-two-col">
                        <p>Backup:</p>
                        <p><span>PHP Version: </span><span><?php echo $backup_php_version;?></span></p>
                        <p><span>MYSQL Version: </span><span><?php echo $backup_mysql_version;?></span></p>
                        <p><span>Wordpress Version: </span><span><?php echo $backup_wp_version; ?></span></p>
                    </div>
                    <div class="wpvivid-two-col">
                        <p>Restore:</p>
                        <p><span>PHP Version: </span><span><?php echo $php_version;?></span></p>
                        <p><span>MYSQL Version: </span><span><?php echo $mysql_version;?></span></p>
                        <p><span>Wordpress Version: </span><span><?php echo $wp_version;?></span></p>
                    </div>
                </div>
            </div>
            <div style="clear:both;"></div>
            <div id="wpvivid_need_download_progress" style="display: none">
                <div>
                    <p>
                    <span class="wpvivid-span-progress">
                        <span class="wpvivid-span-processed-progress" style="width: 0%">0% completed</span>
                    </span>
                    </p>
                    <p>
                        <span class="dashicons dashicons-cloud"></span>
                        <span id="wpvivid_download_progress_text"></span>
                    </p>
                    <p></p>
                    <div>
                        <input id="wpvivid_restore_cancel_download" class="button-primary" type="submit" value="Cancel">
                    </div>
                </div>
            </div>
            <script>
                var backup_id='<?php echo $backup_id?>';
                var download_array=[];
                var download_index=0;
                var download_progress_retry=0;
                var wpvivid_restore_download_cancel=false;
                jQuery('#wpvivid_restore_cancel_download').click(function()
                {
                    var descript = 'Are you sure you want to cancel download?';
                    var ret = confirm(descript);
                    if(ret === true){
                        wpvivid_restore_download_cancel=true;
                        jQuery('#wpvivid_restore_cancel_download').css({'pointer-events': 'none', 'opacity': '0.4'});
                    }

                });

                jQuery('#wpvivid_restore_retrieve_to_local').click(function()
                {
                    wpvivid_restore_download_cancel=false;
                    var ajax_data = {
                        'action': 'wpvivid_restore_retrieve_to_local',
                        'backup_id': backup_id
                    };
                    jQuery('#wpvivid_restore_retrieve_to_local').css({'pointer-events': 'none', 'opacity': '0.4'});

                    jQuery('#wpvivid_need_download_progress').show();
                    jQuery('.wpvivid-span-processed-progress').html("0% completed");
                    jQuery(".wpvivid-span-processed-progress").width( "0%" );
                    jQuery('#wpvivid_download_progress_text').html("Preparing...");


                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                wpvivid_get_restore_download_progress();
                            }
                            else
                            {
                                jQuery('#wpvivid_need_download_progress').hide();
                                jQuery('#wpvivid_restore_retrieve_to_local').css({'pointer-events': 'auto', 'opacity': '1'});
                                alert(jsonarray.error);
                            }
                        }
                        catch(err)
                        {
                            wpvivid_get_restore_download_progress();
                        }

                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        wpvivid_get_restore_download_progress();
                    });
                });

                function wpvivid_get_restore_download_progress()
                {
                    if(wpvivid_restore_download_cancel)
                    {
                        jQuery('#wpvivid_need_download_progress').hide();
                        jQuery('#wpvivid_restore_retrieve_to_local').css({'pointer-events': 'auto', 'opacity': '1'});

                        alert("Download canceled.");
                        return;
                    }

                    var ajax_data = {
                        'action':'wpvivid_get_prepare_download_progress'
                    };

                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('.wpvivid-span-processed-progress').html(jsonarray.progress);
                                jQuery(".wpvivid-span-processed-progress").width(jsonarray.width);
                                jQuery('#wpvivid_download_progress_text').html(jsonarray.html);

                                if(jsonarray.finished)
                                {
                                    location.reload();
                                }
                                else
                                {
                                    if(jsonarray.set_timeout)
                                    {
                                        setTimeout(function ()
                                        {
                                            wpvivid_get_restore_download_progress();
                                        }, 1000);
                                    }
                                    else
                                    {
                                        wpvivid_get_restore_download_progress();
                                    }
                                }
                            }
                            else
                            {
                                jQuery('#wpvivid_need_download_progress').hide();
                                jQuery('#wpvivid_restore_retrieve_to_local').css({'pointer-events': 'auto', 'opacity': '1'});
                                alert(jsonarray.error);
                            }
                        }
                        catch(err)
                        {
                            jQuery('#wpvivid_need_download_progress').hide();
                            jQuery('#wpvivid_restore_retrieve_to_local').css({'pointer-events': 'auto', 'opacity': '1'});
                            alert(err);
                        }
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        wpvivid_get_restore_download_progress();
                    });
                }


            </script>
            <?php
        }
        else
        {
            $this->show_backup_detail();
        }
    }

    public function show_backup_detail()
    {
        $php_version=phpversion();
        preg_match("/((?:[0-9]+\.?)+)/i",  $php_version, $matches);
        $php_version= $matches[1];
        $php_version_warning=false;
        if($this->backup_data['php_version']==false)
        {
            $backup_php_version='NULL';
        }
        else
        {
            $backup_php_version=$this->backup_data['php_version'];

            $is_php7=false;
            $backup_is_php7=false;

            if(version_compare($php_version,'7.0.0','>='))
            {
                $is_php7=true;
            }

            if(version_compare($backup_php_version,'7.0.0','>='))
            {
                $backup_is_php7=true;
            }

            if($is_php7!==$backup_is_php7)
            {
                $php_version_warning = 'There may be a serious incompatibility between the PHP versions in your backup file and on the current website. [PHP version in the backup file]:'.$backup_php_version.', [PHP version of the current website]:'.$php_version;
            }
            else
            {
                if(version_compare($backup_php_version,$php_version,'>='))
                {
                    $php_version_warning = false;
                }
                else
                {
                    $php_version_warning = false;
                    //$php_version_warning = 'There may be a incompatibility between the PHP versions in your backup file and on the current website. [PHP version in the backup file]:'.$backup_php_version.', [PHP version of the current website]:'.$php_version;
                }
            }
        }

        global $wpdb;
        $mysql_version = $wpdb->db_version();
        if($this->backup_data['mysql_version']==false)
        {
            $backup_mysql_version='NULL';
        }
        else
        {
            $backup_mysql_version=$this->backup_data['mysql_version'];
        }

        if($this->backup_data['wp_version']==false)
        {
            $backup_wp_version='N/A';
        }
        else
        {
            $backup_wp_version=$this->backup_data['wp_version'];
        }

        $wp_version = get_bloginfo( 'version' );

        $general_setting=WPvivid_Setting::get_setting(true, "");

        if(!isset($general_setting['options']['wpvivid_common_setting']['restore_memory_limit']))
        {
            $restore_memory_limit=WPVIVID_PRO_RESTORE_MEMORY_LIMIT;
        }
        else
        {
            $restore_memory_limit=$general_setting['options']['wpvivid_common_setting']['restore_memory_limit'];
        }

        if(isset($general_setting['options']['wpvivid_common_setting']['restore_max_execution_time']))
        {
            $restore_max_execution_time = intval($general_setting['options']['wpvivid_common_setting']['restore_max_execution_time']);
        }
        else{
            $restore_max_execution_time = 1800;
        }

        if(class_exists('PDO'))
        {
            $extensions=get_loaded_extensions();
            if(array_search('pdo_mysql',$extensions))
            {
                $db_method_pdo  = 'checked';
                $db_method_wpdb = '';
            }
            else{
                $db_method_pdo  = '';
                $db_method_wpdb = 'checked';
            }
        }
        else{
            $db_method_pdo  = '';
            $db_method_wpdb = 'checked';
        }


        ?>
        <div id="wpvivid_restore_backup_detail" class="wpvivid-two-col" style="padding: 0 1em;">
            <div style="background:#f1f1f1;padding:1em;"><span><strong>System Information:</strong></span></div>
            <div id="wpvivid_restore_system_info">
                <div class="wpvivid-two-col">
                    <p>In Backup:</p>
                    <p><span>PHP Version: </span><span><?php echo $backup_php_version;?></span></p>
                    <p><span>MYSQL Version: </span><span><?php echo $backup_mysql_version;?></span></p>
                    <p><span>Wordpress Version: </span><span><?php echo $backup_wp_version; ?></span></p>
                </div>
                <div class="wpvivid-two-col">
                    <p>Restore To:</p>
                    <p><span>PHP Version: </span><span><?php echo $php_version;?></span></p>
                    <p><span>MYSQL Version: </span><span><?php echo $mysql_version;?></span></p>
                    <p><span>Wordpress Version: </span><span><?php echo $wp_version;?></span></p>
                </div>
            </div>
        </div>
        <div style="clear:both;"></div>
        <div style="padding:0 1em 1em 1em;background:#eaf1fe;">
            <div style="padding:1em 0 0 0;">
                <span><strong>Advanced Settings</strong></span>
                <span id="wpvivid_restore_advanced_setting" class="dashicons wpvivid-dashicons-grey wpvivid-handle-tree-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </div>
            <div id="wpvivid_restore_setting" style="display: none">
                <div style="margin-top:1em;">
                    <p><span><strong>Database access method: </strong></span></p>
                    <fieldset>
                        <label style="float:left; padding-right:1em;">
                            <input type="radio" option="restore_detail_options" name="db_connect_method" value="wpdb" <?php esc_attr_e($db_method_wpdb); ?>><strong>WPDB</strong>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
								<div class="wpvivid-bottom">
								<!-- The content you need -->
									<p>WPDB option has a better compatibility, but the speed of backup and restore is slower.</p>
									<i></i> <!-- do not delete this line -->
								</div>
							</span>
                        </label>
                        <label style="float:left; padding-right:1em;"><strong>PDO</strong>
                            <input type="radio" option="restore_detail_options" name="db_connect_method" value="pdo" <?php esc_attr_e($db_method_pdo); ?> >
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
                <div style="margin-top:1em;">
                    <p><span><strong>Choose a server resource consumption mode</strong></span></p>
                    <fieldset>
                        <label style="float:left; padding-right:1em;">
                            <input type="radio" option="restore_detail_options" name="restore_level" value="low"><strong>Low</strong>
                        </label>
                        <label style="float:left; padding-right:1em;">
                            <input type="radio" option="restore_detail_options" name="restore_level" value="mid" checked><strong>Mid</strong>
                        </label>
                        <label style="float:left; padding-right:1em;">
                            <input type="radio" option="restore_detail_options" name="restore_level" value="high">
                            <strong>High</strong>
                        </label>
                        <label style="float:left; padding-right:1em;">
                            <input type="radio" option="restore_detail_options" name="restore_level" value="custom">
                            <strong>Custom</strong>
                        </label>
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p><strong>Low</strong>: The restoration will use less server resources, but take longer time. It is designed for shared hosting.</p>
                                <p><strong>Mid</strong>: Default mode, suitable for most web hosting.</p>
                                <p><strong>High</strong>: Consume more server resources for faster restoration speed. It is designed for dedicated hosting</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </fieldset>
                </div>
                <p></p>
                <div id="wpvivid_custom_restore_setting" style="display: none">
                    <p></p>
                    <div>
                        <input type="text" option="restore_custom_detail_options" name="restore_max_execution_time" placeholder="1000" value="<?php echo $restore_max_execution_time?>"> seconds, maximum PHP script execution time for a restore task.
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The time limit here is not your server PHP time limit, it only works for WPvivid restore tasks. With the time exhausted, our plugin will shut restore down. If you encounter a restore timeout, that means you have a medium or large sized website, please try to increase the value to 3600s or higher.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>
                    <p></p>
                    <div>
                        <input type="text" option="restore_custom_detail_options" name="restore_memory_limit" placeholder="256" value="<?php echo $restore_memory_limit?>"> MB, maximum PHP memory for a restore task.
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Adjust this value to apply for a temporary PHP memory limit for WPvivid restore tasks. Increase the value if you encounter a memory exhausted error.</p>
                                <p>Note: some web hosting providers may not support it.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>
                    <p></p>
                    <div>
                        <input type="text" option="restore_custom_detail_options" name="max_allowed_packet" placeholder="32" value="32"> MB, maximum size of data to be transmitted per request.
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The bigger it is, the bigger packet size is allowed, the faster the restoration will be. The recommended value is 32 MB or higher..</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>
                    <p></p>
                    <div>
                        <input type="text" option="restore_custom_detail_options" name="sql_file_buffer_pre_request" placeholder="5" value="5"> MB, maximum size of sql file to be imported per request
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The smaller it is, the slower the restoration will be, but the lower the chance of a timeout error.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>
                    <p></p>
                    <div>
                        <input type="text" option="restore_custom_detail_options" name="replace_rows_pre_request" placeholder="10000" value="10000"> rows, maximum rows of data to be processed per request.
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The smaller it is, the slower the restoration will be, but the lower the chance of a timeout error.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>
                    <p></p>
                    <div>
                        <input type="checkbox" option="restore_custom_detail_options" name="use_index"> Extract files by index.
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Specify the number of files to be extracted per request. The lower the number is, the slower the restoration, but the lower the chance of a timeout error or restore failure.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>
                    <p style="padding-left:1em;">
                        <input type="text" readonly option="restore_custom_detail_options" name="unzip_files_pre_request" placeholder="1000" value="1000"> files are unzipped every PHP request.
                    </p>
                    <script>
                        jQuery('input:checkbox[option=restore_custom_detail_options][name=use_index]').click(function()
                        {
                            if(jQuery(this).prop('checked'))
                            {
                                jQuery('input:text[option=restore_custom_detail_options][name=unzip_files_pre_request]').attr('readonly', false);
                            }
                            else
                            {
                                jQuery('input:text[option=restore_custom_detail_options][name=unzip_files_pre_request]').attr('readonly', true);
                            }
                        });
                    </script>
                </div>
                <script>
                    jQuery('input:radio[option=restore_detail_options][name=restore_level]').click(function()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            var value = jQuery(this).prop('value');
                            if(value=='custom')
                            {
                                jQuery('#wpvivid_custom_restore_setting').show();
                            }
                            else
                            {
                                jQuery('#wpvivid_custom_restore_setting').hide();
                            }
                        }
                    });


                </script>

                <p></p>
                <div>
                    <input type="checkbox" option="restore_detail_options" name="restore_htaccess"> Restore the .htaccess file in the backup.
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>We have generated a new .htaccess file for the restoration. If you still want to use the original .htaccess file, you should know what you are doing. Because that may cause some errors, especially if it's a migration to a new domain.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                </div>

                <p></p>
                <div>
                    <input type="checkbox" option="restore_detail_options" name="restore_reset"> Clean up old files before restoring.
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                        <div class="wpvivid-bottom">
                            <!-- The content you need -->
                            <p>With the option checked, our plugin will delete old files on the current site, including plugin files, theme files, and all files under the wp-content folder(except for WPvivid backup files), to a state of a new WordPress installation before running the restoration.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                </div>
                <p></p>
                    <div>
                        <input type="checkbox" option="restore_detail_options" name="restore_db_reset"> Empty the database before restoring.
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>With the option checked, our plugin will reset the current database to a state of a new WordPress installation before running the restoration.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>
                    <p></p>
                    <div>
                        <input type="checkbox" option="restore_detail_options" name="replace_table_character_set"> Auto replace not exist character set.
                    </div>
                    <?php
                    $general_setting=WPvivid_Setting::get_setting(true, "");
                    $password=isset($general_setting['options']['wpvivid_common_setting']['encrypt_db_password'])?$general_setting['options']['wpvivid_common_setting']['encrypt_db_password']:'';
                    ?>
                    <p><input type="text" option="restore_detail_options" name="restore_db_password" value="<?php esc_attr_e($password); ?>"> Enter your password to decrypt the database backup. Ignore it if you didn't enable encryption for this backup.</p>
            </div>
        </div>
        <div style="padding:1em 1em 0 0;">
            <?php
            $next_step_btn_style = 'pointer-events: auto; opacity: 1;';
            if(is_multisite())
            {
                if(isset($this->backup_data['is_mu_site']) && !$this->backup_data['is_mu_site'])
                {
                    $next_step_btn_style = 'pointer-events: none; opacity: 0.4;';
                }
                else
                {
                    $next_step_btn_style = 'pointer-events: auto; opacity: 1;';
                }
            }
            ?>
            <input id="wpvivid_restore_next_step_2" class="button-primary wpvivid_setting_general_save" type="submit" value="Next Step" title="Next Step" style="<?php esc_attr_e($next_step_btn_style); ?>">
        </div>
        <script>
            jQuery('#wpvivid_restore_advanced_setting').click(function()
            {
                if(jQuery(this).hasClass('dashicons-arrow-down-alt2'))
                {
                    jQuery(this).removeClass('dashicons-arrow-down-alt2');
                    jQuery(this).addClass('dashicons-arrow-up-alt2');
                }
                else
                {
                    jQuery(this).removeClass('dashicons-arrow-up-alt2');
                    jQuery(this).addClass('dashicons-arrow-down-alt2');
                }

                jQuery('#wpvivid_restore_setting').toggle();

            });

            <?php
            if($php_version_warning!==false)
            {
                ?>
            jQuery('#wpvivid_restore_warning_info').show();
            jQuery('#wpvivid_restore_warning_msg').html('<?php echo $php_version_warning;?>');
                <?php
            }
            ?>
        </script>
        <?php
    }

    public function retrieve_backup_to_local()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if(!isset($_POST['backup_id'])||empty($_POST['backup_id'])||!is_string($_POST['backup_id']))
        {
            die();
        }

        $backup_id=sanitize_key($_POST['backup_id']);
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        if($backup===false)
        {
            $ret['result']='failed';
            $ret['error']='backup not found';
            echo json_encode($ret);
            die();
        }

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().DIRECTORY_SEPARATOR;
        $path=apply_filters('wpvivid_get_site_wpvivid_path',$path,$backup['local']['path']);
        $files=array();

        foreach ($backup['backup']['files'] as $key => $file_data)
        {
            if(file_exists($path.$file_data['file_name'])&&filesize($path.$file_data['file_name'])===$file_data['size'])
            {
                continue;
            }

            $file['file_name']=$file_data['file_name'];
            $file['need_extra']=false;
            $file['need_download']=true;
            $file['need_download_file']=$file_data['file_name'];
            $files[]=$file;
        }


        if(empty($files))
        {
            $ret['result']='failed';
            $ret['error']='no need prepare';
            echo json_encode($ret);
            die();
        }

        $task=new WPvivid_Backup_Download_TaskEx();
        $ret=$task->init_prepare_files_task($files,$backup);

        if($ret['result']=='success')
        {
            $this->flush($ret);
            $task->do_prepare_files_task();
        }
        else
        {
            echo json_encode($ret);
        }

        die();
    }

    private function flush($ret)
    {
        $json=json_encode($ret);
        if(!headers_sent())
        {
            header('Content-Length: '.strlen($json));
            header('Connection: close');
            header('Content-Encoding: none');
        }


        if (session_id())
            session_write_close();
        echo $json;

        if(function_exists('fastcgi_finish_request'))
        {
            fastcgi_finish_request();
        }
        else
        {
            ob_flush();
        }
    }

    public function download_restore_file()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (!isset($_POST['backup_id']) || empty($_POST['backup_id']) || !is_string($_POST['backup_id'])
                || !isset($_POST['file_name']) || empty($_POST['file_name']) || !is_string($_POST['file_name']))
            {
                die();
            }

            @set_time_limit(600);

            $backup_id = sanitize_key($_POST['backup_id']);
            $backup_list=new WPvivid_New_BackupList();
            $backup = $backup_list->get_backup_by_id($backup_id);
            if (!$backup)
            {
                echo json_encode(array('result' => WPVIVID_FAILED, 'error' => 'backup not found'));
                die();
            }

            $backup_item = new WPvivid_New_Backup_Item($backup);
            $remote_option = $backup_item->get_remote();
            if ($remote_option === false)
            {
                echo json_encode(array('result' => WPVIVID_FAILED, 'error' => 'Retrieving the cloud storage information failed while downloading backups. Please try again later.'));
                die();
            }

            $download_info = array();
            $download_info['backup_id'] = sanitize_key($_POST['backup_id']);
            $download_info['file_name'] = $_POST['file_name'];
            if (session_id())
                session_write_close();
            $downloader = new WPvivid_downloader();
            $downloader->ready_download($download_info);

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

    public function get_download_restore_progress_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        try
        {
            if (!isset($_POST['file_name']))
            {
                die();
            }

            $file_name = $_POST['file_name'];
            $file_size = $_POST['size'];

            wp_cache_delete('notoptions', 'options');
            wp_cache_delete('alloptions', 'options');
            wp_cache_delete('wpvivid_download_task_v2', 'options');

            $task = WPvivid_taskmanager::get_download_task_v2($file_name);
            $sub_progress=0;
            if ($task === false)
            {
                $check_status = false;
                $local_storage_dir = WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath();
                $local_file=$local_storage_dir.DIRECTORY_SEPARATOR.$file_name;
                if(file_exists($local_file))
                {
                    if(filesize($local_file)==$file_size)
                    {
                        $check_status = true;
                    }
                }


                if($check_status)
                {
                    $ret['result'] = WPVIVID_SUCCESS;
                    $ret['status'] = 'completed';
                    $sub_progress=100;
                }
                else {
                    $ret['result'] = WPVIVID_FAILED;
                    $ret['error'] = 'not found download file';
                }
            }
            else
            {
                $ret['result'] = WPVIVID_SUCCESS;
                $ret['status'] = $task['status'];
                $ret['error'] = $task['error'];

                if ($task['status'] === 'running')
                {
                    if(strpos($task['progress_text'],'start download file')!==false)
                    {
                        $sub_progress=0;
                    }
                    else
                    {
                        $sub_progress=$task['progress_text'];
                    }
                }
                elseif ($task['status'] === 'timeout')
                {
                    $sub_progress=0;
                }
                elseif ($task['status'] === 'completed')
                {
                    $sub_progress=100;
                    WPvivid_taskmanager::delete_download_task_v2($file_name);
                }
                elseif ($task['status'] === 'error')
                {
                    $sub_progress=0;
                    WPvivid_taskmanager::delete_download_task_v2($file_name);
                }
            }

            $backup_id = sanitize_key($_POST['backup_id']);

            $progress=$this->get_backup_progress($backup_id,$file_name,$sub_progress);
            $ret['list']=$progress['list'];
            $ret['main_progress']=$progress['main_progress'];
            $ret['sub_progress']=$progress['sub_progress'];
            $ret['finished']=$progress['finished'];
            $ret['total']=$progress['total'];
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

    public function get_backup_progress($backup_id,$file_name,$sub_progress)
    {
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        $backup_item = new WPvivid_New_Backup_Item($backup);
        $html='';

        $total=sizeof($backup['backup']['files']);
        $finished=0;
        $progress=0;

        foreach ($backup['backup']['files'] as $key=>$file)
        {
            if(WPvivid_taskmanager::is_download_task_running_v2($file['file_name']))
            {
                if($file_name==$file['file_name'])
                {
                    $progress=$sub_progress;
                }
                $html.='<p>';
                $html.='<span><strong>Retrieving:</strong> '.$file['file_name'].'</span>';
                $html.='</span></p>';
            }

            $path = $backup_item->get_download_local_path() . $file['file_name'];
            if(file_exists($path))
            {
                if(filesize($path) == $file['size'])
                {
                    $need_download=false;
                }
                else {
                    $need_download=true;
                }
            }
            else
            {
                $need_download=true;
            }

            if(!$need_download)
            {
                $finished++;
                $progress=0;
            }
        }

        if($total>1)
        {
            $main_progress=intval(($finished/$total)*100+($progress/$total));
        }
        else
        {
            $main_progress=$progress;
        }
        $ret['main_progress']=min(100,$main_progress);
        $ret['sub_progress']=$sub_progress;
        $ret['finished']=$finished;
        $ret['total']=$total;
        $ret['list']=$html;
        return $ret;
    }

    public function restore_step2()
    {
        ?>
        <div class="wpvivid-one-coloum" id="wpvivid_restore_page_2" style="display: none">
            <div style="display:none;">
                <div style="padding-bottom:1em;"><h2>Step Two: Restoring</h2></div>
            </div>
            <div id="wpvivid_restore_progress" style="margin-bottom:1em;margin-top:1em;display: none;">
                <div style="padding-top:1em;border-top:1px solid #cccccc;">
                    <span class="dashicons dashicons-update wpvivid-dashicons-green"></span>
                    <span>Restoring: overall progress</span>
                </div>
                <div style="padding: 1em 0;border-bottom:1px solid #cccccc;">
                    <span class="wpvivid-span-progress" id="wpvivid_main_progress">
                        <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="width: 0%;">0% completed</span>
                    </span>
                </div>
            </div>
            <div id="wpvivid_restore_info">
                <div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>
                <div style="float: left; margin-top: 2px;">Loading…</div>
                <div style="clear: both;"></div>
                <?php
                //if($this->backup_data['restore_info']!==false)
                //$this->output_restore_info();
                ?>
            </div>
        </div>
        <?php
    }

    public function init_restore_page_step2()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        if(!isset($_POST['backup_id'])||empty($_POST['backup_id'])||!is_string($_POST['backup_id']))
        {
            die();
        }

        $backup_id=sanitize_key($_POST['backup_id']);

        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);

        if(isset($backup['remote'])&&!empty($backup['remote']))
            $this->backup_data['location']='Cloud Storage';
        else
            $this->backup_data['location']='Localhost';

        $backup_item = new WPvivid_New_Backup_Item($backup);

        $backup_file_info=$this->get_restore_files_info($backup_item,false,true);
        $root_path=$backup_item->get_download_local_path();
        $offset = get_option('gmt_offset');
        foreach ($backup_file_info as $type=>$files_info)
        {
            $this->backup_data['restore_info'][$type]['size']=0;

            if($type=='databases'||$type=='db')
            {
                $this->backup_data['has_db']=true;
            }
            if($type=='wp-core')
            {
                $this->backup_data['has_core']=true;
            }
            foreach ($files_info['files'] as $file)
            {
                if(isset($file['options']['php_version']))
                {
                    //7.3.27-1~deb10u1
                    preg_match("/((?:[0-9]+\.?)+)/i",  $file['options']['php_version'], $matches);
                    $this->backup_data['php_version']= $matches[1];
                }
                if(isset($file['options']['mysql_version']))
                {
                    $this->backup_data['mysql_version']= $file['options']['mysql_version'];
                }

                if(isset($file['options']['wp_version']))
                {
                    $this->backup_data['wp_version']=$file['options']['wp_version'];
                }

                if(file_exists($root_path.$file['file_name']))
                {
                    $this->backup_data['restore_info'][$type]['size']+=filesize($root_path.$file['file_name']);
                }

                if(isset($file['options']['themes']))
                {
                    $this->backup_data['restore_info'][$type]['themes']=$file['options']['themes'];
                }

                if(isset($file['options']['plugin']))
                {
                    $this->backup_data['restore_info'][$type]['plugins']=$file['options']['plugin'];
                }

                if(isset($file['options']['tables']))
                {
                    $this->backup_data['restore_info'][$type]['tables']=$file['options']['tables'];
                }

                if(isset($file['has_version'])&&$file['has_version']==true)
                {
                    $this->backup_data['has_version']=true;
                    $version=$file['version'];
                    $localtime = $file['options']['backup_time'] + $offset * 60 * 60;
                    $localtime = __(date('M d, Y H:i', $localtime));
                    $this->backup_data['versions'][$version]['version']=$version;
                    $this->backup_data['versions'][$version]['date']=$localtime;
                }

                if((isset($file['options']['is_crypt'])&&$file['options']['is_crypt']==1) ||
                    (isset($file['options']['is_crypt_ex'])&&$file['options']['is_crypt_ex']==1))
                {
                    $this->backup_data['is_db_crypt']=true;
                }
            }
        }

        ob_start();
        $this->output_restore_info();
        $html = ob_get_clean();

        $ret['result']='success';
        $ret['html']=$html;
        echo json_encode($ret);
        die();
    }

    public function output_restore_info()
    {
        if(isset($this->backup_data['has_version'])&&$this->backup_data['has_version']==true)
        {
            $this->output_restore_incremental_info();
            return;
        }

        $has_folder=false;
        $has_databases=false;

        if(isset( $this->backup_data['restore_info']['databases'])
            ||isset( $this->backup_data['restore_info']['additional_databases']))
        {
            $has_databases=true;
        }

        if(isset( $this->backup_data['restore_info']['wp-core'])
            ||isset( $this->backup_data['restore_info']['themes'])
            ||isset( $this->backup_data['restore_info']['plugin'])
            ||isset( $this->backup_data['restore_info']['wp-content'])
            ||isset( $this->backup_data['restore_info']['upload'])
            ||isset( $this->backup_data['restore_info']['custom']))
        {
            $has_folder=true;
        }

        if($has_folder)
        {
            ?>
            <div style="margin-bottom:1em;">

                <div style="background:#eaf1fe;padding:1em 1em;">
                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                    <span><strong>Folders & Files</strong></span>
                </div>
                <div style="padding:0 0 0 2em;width:100%;  box-sizing:border-box;">
                    <?php

                    if(isset( $this->backup_data['restore_info']['themes']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="themes" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Themes</span>
                                <span id="wpvivid_restore_themes_size">
                                <?php
                                if($this->backup_data['restore_info']['themes']['size']>0)
                                {
                                    echo ' ('.size_format($this->backup_data['restore_info']['themes']['size'],2).')';
                                }
                                ?>
                                </span>
                                <?php
                                if(isset( $this->backup_data['restore_info']['themes']['themes']))
                                {
                                ?>
                                    <span id="wpvivid_restore_themes_detail" class="dashicons wpvivid-dashicons-grey wpvivid-handle-base-database-detail  dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                        <div class="wpvivid-bottom">
							        <!-- The content you need -->
                                        <p>Won't restore any files or folders if you uncheck this.</p>
                                        <i></i> <!-- do not delete this line -->
                                        </div>
                                    </span>
                                    <?php
                                }
                                ?>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_themes_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                 <span id="wpvivid_restore_themes_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                 </span>
                                </p>
                            </div>
                            <?php
                            if(isset( $this->backup_data['restore_info']['themes']['themes']))
                            {
                                ?>
                                <div id="wpvivid_restore_themes_info" style="display: none;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                    <?php
                                    foreach ($this->backup_data['restore_info']['themes']['themes'] as $theme)
                                    {
                                        ?>
                                        <div class="wpvivid-text-line">
                                            <input option="restore_themes_options" name="<?php echo $theme['slug']?>" type="checkbox" value="<?php echo $theme['slug']?>" checked>
                                            <span class="wpvivid-text-line"><?php echo $theme['slug']?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <script>
                                    jQuery('#wpvivid_restore_themes_detail').click(function()
                                    {
                                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2'))
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                                        }
                                        else
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                                            jQuery(this).addClass('dashicons-arrow-down-alt2');

                                        }
                                        jQuery('#wpvivid_restore_themes_info').toggle();
                                    });
                                </script>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['plugin']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span>
                                    <input option="restore_options" name="plugins" type="checkbox" checked>
                                </span>
                                <span style="padding-right:0.5em;">Plugins</span>
                                <span id="wpvivid_restore_plugins_size">
                                    <?php
                                    if($this->backup_data['restore_info']['plugin']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['plugin']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <?php
                                if(isset( $this->backup_data['restore_info']['plugin']['plugins']))
                                {
                                    ?>
                                    <span id="wpvivid_restore_plugins_detail" class="dashicons wpvivid-dashicons-grey wpvivid-handle-base-database-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                        <div class="wpvivid-bottom">
							            <!-- The content you need -->
                                        <p>Won't restore any files or folders if you uncheck this.</p>
                                        <i></i> <!-- do not delete this line -->
                                        </div>
                                    </span>
                                    <?php
                                }
                                ?>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_plugin_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_plugin_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                    </span>
                                </p>
                            </div>
                            <?php
                            if(isset( $this->backup_data['restore_info']['plugin']['plugins']))
                            {
                                ?>
                                <div id="wpvivid_restore_plugins_info" style="display:none;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                    <?php
                                    foreach ($this->backup_data['restore_info']['plugin']['plugins'] as $plugin)
                                    {
                                        ?>
                                        <div class="wpvivid-text-line">
                                            <input option="restore_plugin_options" name="<?php echo $plugin['slug']?>" type="checkbox" value="<?php echo $plugin['slug']?>" checked>
                                            <span class="wpvivid-text-line"><?php echo $plugin['slug']?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <script>
                                    jQuery('#wpvivid_restore_plugins_detail').click(function()
                                    {
                                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2'))
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                                        }
                                        else
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                                            jQuery(this).addClass('dashicons-arrow-down-alt2');

                                        }
                                        jQuery('#wpvivid_restore_plugins_info').toggle();
                                    });
                                </script>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['wp-content']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="wp-content" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Wp-content</span>
                                <span id="wpvivid_restore_wp_content_size">
                                <?php
                                if($this->backup_data['restore_info']['wp-content']['size']>0)
                                {
                                    echo ' ('.size_format($this->backup_data['restore_info']['wp-content']['size'],2).')';
                                }
                                ?>
                                </span>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_wp_content_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_wp_content_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                </p>
                            </div>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['upload']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="upload" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Uploads</span>
                                <span id="wpvivid_restore_upload_size">
                                <?php
                                if($this->backup_data['restore_info']['upload']['size']>0)
                                {
                                    echo ' ('.size_format($this->backup_data['restore_info']['upload']['size'],2).')';
                                }
                                ?>
                                </span>
                                <span style="float:right;">
                                <span class="wpvivid_restore_progress" id="wpvivid_restore_upload_progress" style="display: none">
                                </span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_upload_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                </p>
                            </div>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['wp-core']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="wp-core" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Wordpress Core</span>
                                <span id="wpvivid_restore_core_size">
                                    <?php
                                    if($this->backup_data['restore_info']['wp-core']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['wp-core']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_core_progress" style="display: none"></span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_core_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                    </span>
                                </p>
                            </div>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['custom']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="custom" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Additional Folders/Files</span>
                                <span id="wpvivid_restore_custom_size">
                                    <?php
                                    if($this->backup_data['restore_info']['custom']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['custom']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_custom_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                <span id="wpvivid_restore_custom_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                </span>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }

        if($has_databases)
        {
            ?>
            <div>
                <div style="background:#eaf1fe;padding:1em 1em;">
                    <span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue"></span>
                    <span><strong>Database(s)</strong></span>
                </div>

                <div style="padding:0 0 0 2em;width:100%;  box-sizing:border-box;">
                    <?php
                    if(isset( $this->backup_data['restore_info']['databases']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="databases" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Wordpress database</span>
                                <span id="wpvivid_restore_databases_size">
                                    <?php
                                    if($this->backup_data['restore_info']['databases']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['databases']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <?php
                                if(isset( $this->backup_data['restore_info']['databases']['tables']))
                                {
                                    ?>
                                    <span id="wpvivid_restore_tables_detail" class="dashicons wpvivid-dashicons-grey wpvivid-handle-base-database-detail  dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                        <div class="wpvivid-bottom">
							        <!-- The content you need -->
                                        <p>Won't restore any tables if you uncheck this.</p>
                                        <i></i> <!-- do not delete this line -->
                                        </div>
                                    </span>
                                    <?php
                                }
                                ?>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_databases_progress" style="display: none"></span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_databases_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                    </span>
                                </p>
                            </div>
                            <?php
                            if(isset( $this->backup_data['restore_info']['databases']['tables']))
                            {
                                ?>
                                <div id="wpvivid_restore_tables_info" class="striped" style="display: none;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                    <div>
                                        <input type="checkbox" id="wpvivid_restore_tables_all_check" checked>
                                        <span>Select all tables</span>
                                    </div>
                                    <div style="margin-top:0.5em; margin-bottom: 0.5em;">
                                        <div style="border-bottom:1px solid rgb(204, 204, 204);"></div>
                                    </div>
                                    <?php
                                    foreach ($this->backup_data['restore_info']['databases']['tables'] as $table)
                                    {
                                        ?>
                                        <div class="wpvivid-text-line">
                                            <input option="restore_tables_options" name="<?php echo $table['name']?>" type="checkbox" value="<?php echo $table['name']?>" checked>
                                            <span class="wpvivid-text-line"><?php echo $table['name'].'|Rows:'.$table['rows'].'|Size:'.size_format($table['size'],2).''?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <script>
                                    jQuery('#wpvivid_restore_tables_all_check').click(function(){
                                        if(jQuery(this).prop('checked'))
                                        {
                                            jQuery('input:checkbox[option=restore_tables_options]').prop('checked', true);
                                        }
                                        else
                                        {
                                            jQuery('input:checkbox[option=restore_tables_options]').prop('checked', false);
                                        }
                                    });

                                    jQuery('input:checkbox[option=restore_tables_options]').click(function(){
                                        if(jQuery(this).prop('checked'))
                                        {
                                            var table_all_check=true;
                                            jQuery('input:checkbox[option=restore_tables_options]').each(function (){
                                                if(!jQuery(this).prop('checked'))
                                                {
                                                    table_all_check=false;
                                                }
                                            });
                                            if(table_all_check)
                                            {
                                                jQuery('#wpvivid_restore_tables_all_check').prop('checked', true);
                                            }
                                            else
                                            {
                                                jQuery('#wpvivid_restore_tables_all_check').prop('checked', false);
                                            }
                                        }
                                        else
                                        {
                                            jQuery('#wpvivid_restore_tables_all_check').prop('checked', false);
                                        }
                                    });

                                    jQuery('#wpvivid_restore_tables_detail').click(function()
                                    {
                                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2'))
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                                        }
                                        else
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                                            jQuery(this).addClass('dashicons-arrow-down-alt2');

                                        }
                                        jQuery('#wpvivid_restore_tables_info').toggle();
                                    });
                                </script>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['additional_databases']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="additional_databases" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Additional Database</span>
                                <span id="wpvivid_restore_additional_db_size">
                                    <?php
                                    if( $this->backup_data['restore_info']['additional_databases']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['additional_databases']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_additional_db_progress" style="display: none"></span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_additional_db_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="wpvivid-additional-database-detail">
                            <div style="padding-left:4em;">
                                <div style="border-bottom:1px solid #eee;border-top:1px solid #eee;">
                                    <p>
                                        <span>Host: </span><span><input type="text" option="additional_databases" name="host" style="width: 120px;"></span>
                                        <span>User Name: </span><span><input type="text" option="additional_databases" name="user" style="width: 120px;"></span>
                                        <span>Password: </span><span><input type="password" option="additional_databases" name="pw" style="width: 120px;"></span>
                                        <span>Database: </span><span><input type="text" option="additional_databases" name="db" style="width: 120px;"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }


        ?>
        <div style="padding:1em 1em 0 0;">
            <input id="wpvivid_restore_prev_step_1" class="button-primary wpvivid_setting_general_save" type="submit" value="Previous Step" title="Previous Step">
            <span> </span>
            <input id="wpvivid_restore_now" class="button-primary wpvivid_setting_general_save" type="submit" value="Restore Now" title="Restore Now">
        </div>


        <?php
        if(isset($this->backup_data['location']) && $this->backup_data['location'] === 'Cloud Storage')
        {
            ?>
            <p></p>
            <div>
                <label>
                    <input type="checkbox" option="restore_option_delete_local" name="delete_local"> Delete the local copy of the remote backup immediately after the restoration is successful.
                </label>
            </div>
            <?php
        }
        ?>

        <?php
    }

    public function output_restore_incremental_info()
    {
        $version_list=new WPvivid_Incremental_Files_Restore_List_Ex();

        usort($this->backup_data['versions'], function ($a, $b)
        {
            if ($a['version'] == $b['version'])
            {
                return 0;
            }

            if($a['version'] > $b['version'])
            {
                return 1;
            }
            else
            {
                return -1;
            }
        });
        $version_list->set_versions($this->backup_data['versions']);
        $version_list->prepare_items();

        ?>
        <div id="wpvivid_restore_version_part">
            <div style="background:#eaf1fe;padding:1em 1em; margin-bottom:1em;">
                <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                <span><strong>Choose a restore point (files)</strong></span>
            </div>
            <div id="wpvivid_restore_version_selector" style="padding-left:1em; border-left:5px solid #eaf1fe;">
                <?php  $version_list->display();?>
            </div>
        </div>
        <div id="wpvivid_restore_folders_part" style="display: none">
            <div style="margin-bottom:1em;">
                <div style="background:#eaf1fe;padding:1em 1em;">
                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                    <span><strong>Folders & Files</strong></span>
                </div>
                <div style="padding:0 0 0 2em;width:100%;  box-sizing:border-box;">
                    <?php

                    if(isset( $this->backup_data['restore_info']['themes']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="themes" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Themes</span>
                                <span id="wpvivid_restore_themes_size">
                                <?php
                                if($this->backup_data['restore_info']['themes']['size']>0)
                                {
                                    echo ' ('.size_format($this->backup_data['restore_info']['themes']['size'],2).')';
                                }
                                ?>
                                </span>
                                <?php
                                if(isset( $this->backup_data['restore_info']['themes']['themes']))
                                {
                                    ?>
                                    <span id="wpvivid_restore_themes_detail" class="dashicons wpvivid-dashicons-grey wpvivid-handle-base-database-detail  dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                        <div class="wpvivid-bottom">
							        <!-- The content you need -->
                                        <p>Won't restore any files or folders if you uncheck this.</p>
                                        <i></i> <!-- do not delete this line -->
                                        </div>
                                    </span>
                                    <?php
                                }
                                ?>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_themes_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                 <span id="wpvivid_restore_themes_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                 </span>
                                </p>
                            </div>
                            <?php
                            if(isset( $this->backup_data['restore_info']['themes']['themes']))
                            {
                                ?>
                                <div id="wpvivid_restore_themes_info" style="display: none;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                    <?php
                                    foreach ($this->backup_data['restore_info']['themes']['themes'] as $theme)
                                    {
                                        ?>
                                        <div class="wpvivid-text-line">
                                            <input option="restore_themes_options" name="<?php echo $theme['slug']?>" type="checkbox" value="<?php echo $theme['slug']?>" checked>
                                            <span class="wpvivid-text-line"><?php echo $theme['slug']?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <script>
                                    jQuery('#wpvivid_restore_themes_detail').click(function()
                                    {
                                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2'))
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                                        }
                                        else
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                                            jQuery(this).addClass('dashicons-arrow-down-alt2');

                                        }
                                        jQuery('#wpvivid_restore_themes_info').toggle();
                                    });
                                </script>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['plugin']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span>
                                    <input option="restore_options" name="plugins" type="checkbox" checked>
                                </span>
                                <span style="padding-right:0.5em;">Plugins</span>
                                <span id="wpvivid_restore_plugins_size">
                                    <?php
                                    if($this->backup_data['restore_info']['plugin']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['plugin']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <?php
                                if(isset( $this->backup_data['restore_info']['plugin']['plugins']))
                                {
                                    ?>
                                    <span id="wpvivid_restore_plugins_detail" class="dashicons wpvivid-dashicons-grey wpvivid-handle-base-database-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                        <div class="wpvivid-bottom">
							            <!-- The content you need -->
                                        <p>Won't restore any files or folders if you uncheck this.</p>
                                        <i></i> <!-- do not delete this line -->
                                        </div>
                                    </span>
                                    <?php
                                }
                                ?>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_plugin_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_plugin_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                    </span>
                                </p>
                            </div>
                            <?php
                            if(isset( $this->backup_data['restore_info']['plugin']['plugins']))
                            {
                                ?>
                                <div id="wpvivid_restore_plugins_info" style="display:none;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                    <?php
                                    foreach ($this->backup_data['restore_info']['plugin']['plugins'] as $plugin)
                                    {
                                        ?>
                                        <div class="wpvivid-text-line">
                                            <input option="restore_plugin_options" name="<?php echo $plugin['slug']?>" type="checkbox" value="<?php echo $plugin['slug']?>" checked>
                                            <span class="wpvivid-text-line"><?php echo $plugin['slug']?></span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <script>
                                    jQuery('#wpvivid_restore_plugins_detail').click(function()
                                    {
                                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2'))
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                                        }
                                        else
                                        {
                                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                                            jQuery(this).addClass('dashicons-arrow-down-alt2');

                                        }
                                        jQuery('#wpvivid_restore_plugins_info').toggle();
                                    });
                                </script>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['wp-content']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="wp-content" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Wp-content</span>
                                <span id="wpvivid_restore_wp_content_size">
                                <?php
                                if($this->backup_data['restore_info']['wp-content']['size']>0)
                                {
                                    echo ' ('.size_format($this->backup_data['restore_info']['wp-content']['size'],2).')';
                                }
                                ?>
                                </span>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_wp_content_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_wp_content_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                </p>
                            </div>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['upload']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="upload" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Uploads</span>
                                <span id="wpvivid_restore_upload_size">
                                <?php
                                if($this->backup_data['restore_info']['upload']['size']>0)
                                {
                                    echo ' ('.size_format($this->backup_data['restore_info']['upload']['size'],2).')';
                                }
                                ?>
                                </span>
                                <span style="float:right;">
                                <span class="wpvivid_restore_progress" id="wpvivid_restore_upload_progress" style="display: none">
                                </span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_upload_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                </p>
                            </div>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['wp-core']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="wp-core" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Wordpress Core</span>
                                <span id="wpvivid_restore_core_size">
                                    <?php
                                    if($this->backup_data['restore_info']['wp-core']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['wp-core']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_core_progress" style="display: none"></span>
                                </span>
                                <p>
                                    <span id="wpvivid_restore_core_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                    </span>
                                </p>
                            </div>
                        </div>
                        <?php
                    }

                    if(isset( $this->backup_data['restore_info']['custom']))
                    {
                        ?>
                        <div style="border-bottom:1px solid #cccccc;">
                            <p></p>
                            <div>
                                <span><input option="restore_options" name="custom" type="checkbox" checked></span>
                                <span style="padding-right:0.5em;">Additional Folders/Files</span>
                                <span id="wpvivid_restore_custom_size">
                                    <?php
                                    if($this->backup_data['restore_info']['custom']['size']>0)
                                    {
                                        echo ' ('.size_format($this->backup_data['restore_info']['custom']['size'],2).')';
                                    }
                                    ?>
                                </span>
                                <span style="float:right;">
                                    <span class="wpvivid_restore_progress" id="wpvivid_restore_custom_progress" style="display: none">
                                    </span>
                                </span>
                                <p>
                                <span id="wpvivid_restore_custom_progress_detail" class="wpvivid_restore_progress_detail" style="display: none">
                                </span>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php

        ?>
        <div style="padding:1em 1em 0 0;">
            <input id="wpvivid_restore_prev_step_1" class="button-primary wpvivid_setting_general_save" type="submit" value="Previous Step" title="Previous Step">
            <span> </span>
            <input id="wpvivid_restore_version_select" class="button-primary wpvivid_setting_general_save" type="submit" value="Next" title="Next">
            <input id="wpvivid_restore_now" class="button-primary wpvivid_setting_general_save" type="submit" value="Restore Now" title="Restore Now" style="display: none">
        </div>

        <?php
        if(isset($this->backup_data['location']) && $this->backup_data['location'] === 'Cloud Storage')
        {
            ?>
            <p></p>
            <div>
                <label>
                    <input type="checkbox" option="restore_option_delete_local" name="delete_local"> Delete the local copy of the remote backup immediately after the restoration is successful.
                </label>
            </div>
            <?php
        }
        ?>

        <script>
            jQuery('#wpvivid_restore_version_select').click(function()
            {
                var restore_version='';
                jQuery('input:radio[option=restore_options][name=restore_version]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var value = jQuery(this).prop('value');
                        restore_version=value;
                    }
                });

                if(restore_version=='')
                {
                    alert('Please choose a restore point');
                }
                else
                {
                    jQuery('#wpvivid_restore_now').show();
                    jQuery('#wpvivid_restore_version_part').hide();
                    jQuery('#wpvivid_restore_version_select').hide();
                    jQuery('#wpvivid_restore_folders_part').show();
                }

            });
        </script>
        <?php
    }

    public function get_restore_version_page(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try {
            if (isset($_POST['page']))
            {
                $page = $_POST['page'];
            } else {
                $page = 1;
            }

            if (isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']))
            {
                $backup_id = $_POST['backup_id'];
            } else {
                die();
            }

            $backup_list=new WPvivid_New_BackupList();
            $backup = $backup_list->get_backup_by_id($backup_id);

            $backup_item = new WPvivid_New_Backup_Item($backup);

            $files = $backup_item->get_files(false);
            $files_info = array();

            foreach ($files as $file) {
                $files_info[$file] = $backup_item->get_file_info($file);
            }
            $info = array();

            $has_version = false;
            foreach ($files_info as $file_name => $file_info) {
                if (isset($file_info['has_child'])) {
                    if (isset($file_info['child_file'])) {
                        $version_num = 0;
                        foreach ($file_info['child_file'] as $child_file_name => $child_file_info) {
                            if (isset($child_file_info['file_type'])) {
                                $info[$child_file_info['file_type']]['files'][] = $child_file_name;
                                if ($child_file_info['file_type'] == 'themes') {
                                    if (isset($info[$child_file_info['file_type']]['list']))
                                        $info[$child_file_info['file_type']]['list'] = array_merge($info[$child_file_info['file_type']]['list'], $child_file_info['themes']);
                                    else
                                        $info[$child_file_info['file_type']]['list'] = $child_file_info['themes'];
                                } else if ($child_file_info['file_type'] == 'plugin') {
                                    if (isset($info[$child_file_info['file_type']]['list']))
                                        $info[$child_file_info['file_type']]['list'] = array_merge($info[$child_file_info['file_type']]['list'], $child_file_info['plugin']);
                                    else
                                        $info[$child_file_info['file_type']]['list'] = $child_file_info['plugin'];
                                } else if ($child_file_info['file_type'] == 'additional_databases') {
                                    $info[$child_file_info['file_type']]['list'][] = $child_file_info['database'];
                                }

                                if (isset($child_file_info['version'])) {
                                    $info[$child_file_info['file_type']]['version'][$child_file_info['version']] = $child_file_info['backup_time'];
                                    $has_version = true;
                                    $version_num = $child_file_info['version'];
                                }
                            }
                        }
                        if ($has_version) {
                            $info['file_size'][$version_num] = filesize(WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath() . DIRECTORY_SEPARATOR . $file_name);
                        }
                    }
                } else {
                    if (isset($file_info['file_type'])) {
                        $info[$file_info['file_type']]['files'][] = $file_name;
                        if ($file_info['file_type'] == 'themes' && isset($file_info['themes'])) {
                            $info[$file_info['file_type']]['list'] = $file_info['themes'];
                        } else if ($file_info['file_type'] == 'plugin' && isset($file_info['plugin'])) {
                            $info[$file_info['file_type']]['list'] = $file_info['plugin'];
                        } else if ($file_info['file_type'] == 'additional_databases' && isset($file_info['database'])) {
                            $info[$file_info['file_type']]['list'][] = $file_info['database'];
                        }

                        if (isset($file_info['version'])) {
                            $info[$file_info['file_type']]['version'][$file_info['version']] = $file_info['backup_time'];
                        }
                    }
                }
            }

            $versions = array();
            $offset = get_option('gmt_offset');

            foreach ($info as $type_name => $type) {
                if (isset($type['version'])) {
                    foreach ($type['version'] as $version => $backup_time) {
                        $localtime = $backup_time + $offset * 60 * 60;
                        $localtime = __(date('M d, Y H:i', $localtime));
                        $versions[$version]['version'] = $version;
                        $versions[$version]['date'] = $localtime;
                    }
                }
            }

            usort($versions, function ($a, $b)
            {
                if ($a['version'] == $b['version'])
                {
                    return 0;
                }

                if($a['version'] > $b['version'])
                {
                    return 1;
                }
                else
                {
                    return -1;
                }
            });

            $ret['result'] = WPVIVID_PRO_SUCCESS;
            $version_list = new WPvivid_Incremental_Files_Restore_List_Ex();
            $version_list->set_versions($versions, $page);
            $version_list->prepare_items();
            ob_start();
            $version_list->display();
            $ret['html'] = ob_get_clean();
            $ret['page'] = $version_list->get_pagenum();
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function restore_step3()
    {
        ?>
        <div class="wpvivid-one-coloum" id="wpvivid_restore_page_3" style="display: none">
            <div id="wpvivid_restore_success">
                <div style="font-size:4em; font-weight:900; color:#8bc34a; text-align:center;padding-top:1.5em;">
                    <span>Congratulations !!!</span>
                </div>
                <div id="wpvivid_restore_finished_msg" style="width:600px; text-align:center; margin: 5em auto; border-top:5px solid #eaf1fe;border-bottom:5px solid #eaf1fe;">
                </div>
            </div>
            <div id="wpvivid_restore_failed" style="display: none">
                <div style="font-size:2em; font-weight:900; color:orange; text-align:center;padding-top:1.5em;">
                    <span>Oops, The restoration seems to have encountered a problem:(</span>
                </div>
                <div id="wpvivid_restore_failed_msg" style="width:600px; text-align:center; margin: 5em auto; border-top:5px solid #eaf1fe;border-bottom:5px solid #eaf1fe;">
                </div>
            </div>
            <div id="wpvivid_restore_open_log" class="wpvivid-one-coloum" style="padding-top: 0px; display: none;">
                <div class="postbox restore_log" id="wpvivid_restore_read_log_content">
                </div>
            </div>
        </div>
        <script>
            jQuery('#wpvivid_restore_page_3').on('click', '.wpvivid-restore-view-log', function()
            {
                var id=jQuery(this).data("id");

                var ajax_data = {
                    'action':'wpvivid_view_restore_log_ex',
                    'log': id
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    jQuery('#wpvivid_restore_open_log').show();
                    jQuery('#wpvivid_restore_read_log_content').html("");

                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success")
                        {
                            var log_data = jsonarray.data;
                            while (log_data.indexOf('\n') >= 0)
                            {
                                var iLength = log_data.indexOf('\n');
                                var log = log_data.substring(0, iLength);
                                log_data = log_data.substring(iLength + 1);
                                var insert_log = "<div style=\"clear:both;\">" + log + "</div>";
                                jQuery('#wpvivid_restore_read_log_content').append(insert_log);
                            }

                            jQuery('html, body').animate({
                                scrollTop: jQuery("#wpvivid_restore_read_log_content").offset().top
                            }, 500);
                        }
                        else
                        {
                            jQuery('#wpvivid_restore_read_log_content').html(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        var div = "Reading the log failed. Please try again.";
                        jQuery('#wpvivid_restore_read_log_content').html(div);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_restore_page_3').on('click', '#wpvivid_restore_retry', function()
            {
                location.reload();
            });
        </script>
        <?php
    }

    public function view_log_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            if (isset($_POST['log']) && !empty($_POST['log']) && is_string($_POST['log']))
            {
                $path = sanitize_text_field($_POST['log']);

                if (!file_exists($path))
                {
                    $json['result'] = 'failed';
                    $json['error'] = __('Log does not exist. It might have been deleted or lost during a website migration.', 'wpvivid');
                    echo json_encode($json);
                    die();
                }

                $file = fopen($path, 'r');

                if (!$file) {
                    $json['result'] = 'failed';
                    $json['error'] = __('Unable to open the log file.', 'wpvivid');
                    echo json_encode($json);
                    die();
                }

                $buffer = '';
                while (!feof($file)) {
                    $buffer .= fread($file, 1024);
                }
                fclose($file);

                $json['result'] = 'success';
                $json['data'] = $buffer;
                echo json_encode($json);
            } else {
                $json['result'] = 'failed';
                $json['error'] = __('Reading the log failed. Please try again.', 'wpvivid');
                echo json_encode($json);
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

    public function init_restore_task()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        if(!isset($_POST['backup_id'])||empty($_POST['backup_id'])||!is_string($_POST['backup_id']))
        {
            die();
        }

        $backup_id=sanitize_key($_POST['backup_id']);

        $restore_options=array();
        if(isset($_POST['restore_options']))
        {
            foreach ($_POST['restore_options'] as $key=>$option)
            {
                $restore_options[$key]=$option;
            }
        }
        /*
        if(isset($_POST['restore_reset']))
        {
            $restore_options['restore_reset']=$_POST['restore_reset'];
            if(isset($_POST['restore_reset_type']))
            {
                $restore_options['restore_reset_type']=$_POST['restore_reset_type'];
            }
            else
            {
                $restore_options['restore_reset_type']='all';
            }
        }
        else
        {
            $restore_options['restore_reset']=false;
        }*/

        /*
        if(isset($_POST['themes_options']))
        {
            $json = stripslashes($_POST['themes_options']);
            $themes_options = json_decode($json, 1);
            if(!is_null($themes_options))
            {
                $restore_options['remove_themes']=$themes_options;
            }
        }
        if(isset($_POST['plugin_options']))
        {
            $json = stripslashes($_POST['plugin_options']);
            $plugin_options = json_decode($json, 1);
            if(!is_null($plugin_options))
            {
                $restore_options['remove_plugin']=$plugin_options;
            }
        }
        if(isset($_POST['additional_database_options']))
        {
            $json = stripslashes($_POST['additional_database_options']);
            $additional_database_options = json_decode($json, 1);
            if(!is_null($additional_database_options)){
                $restore_options['additional_database']=$additional_database_options;
            }
        }
        if(isset($_POST['additional_database_remove_options']))
        {
            $json = stripslashes($_POST['additional_database_remove_options']);
            $additional_database_options = json_decode($json, 1);
            if(!is_null($additional_database_options)){
                $restore_options['remove_additional_database']=$additional_database_options;
            }
        }


        if(isset($_POST['is_mu'])&&$_POST['is_mu'])
        {
            if(isset($_POST['restore_mu_options']))
            {
                $json = stripslashes($_POST['restore_mu_options']);
                $restore_options['restore_mu_options']=json_decode($json, 1);
            }
        }
        */

        //restore_options[selected][additional_databases]
        if(isset($restore_options['selected']['additional_databases'])&&$restore_options['selected']['additional_databases']==1)
        {
            $additional_databases_options=$restore_options['additional_databases'];

            if(!isset($additional_databases_options['host'])||empty($additional_databases_options['host']))
            {
                $ret['result']='failed';
                $ret['error']='host is require.';
                echo json_encode($ret);
                die();
            }

            if(!isset($additional_databases_options['user'])||empty($additional_databases_options['user']))
            {
                $ret['result']='failed';
                $ret['error']='user is require.';
                echo json_encode($ret);
                die();
            }

            if(!isset($additional_databases_options['db'])||empty($additional_databases_options['db']))
            {
                $ret['result']='failed';
                $ret['error']='db is require.';
                echo json_encode($ret);
                die();
            }

            if(!isset($additional_databases_options['pw'])||empty($additional_databases_options['pw']))
            {
                $ret['result']='failed';
                $ret['error']='pw is require.';
                echo json_encode($ret);
                die();
            }

            $db_user = sanitize_text_field($additional_databases_options['user']);
            $db_pass = sanitize_text_field($additional_databases_options['pw']);
            $db_host = sanitize_text_field($additional_databases_options['host']);
            $db=sanitize_text_field($additional_databases_options['db']);
            $database_connect = new WPvivid_Additional_DB_Method($db_user, $db_pass, $db_host);
            $ret = $database_connect->wpvivid_do_connect();

            if($ret['result']==='success')
            {
                $find=false;
                $databases = $database_connect->wpvivid_show_additional_databases();
                foreach ($databases as $database)
                {
                    if($db==$database)
                    {
                        $find=true;
                    }
                }
                if(!$find)
                {
                    $ret['result']='failed';
                    $ret['error']='database not found.';
                    echo json_encode($ret);
                    die();
                }
            }
            else
            {
                echo json_encode($ret);
                die();
            }
        }

        if(isset($restore_options['restore_version']))
        {
            $restore_version=$restore_options['restore_version'];
        }
        else
        {
            $restore_version=0;
        }

        $ret=$this->create_restore_task($backup_id,$restore_options,$restore_version);

        $this->write_litespeed_rule();
        $this->deactivate_plugins();
        $this->deactivate_theme();

        if(!file_exists(WPMU_PLUGIN_DIR.'/a-wpvivid-restore-mu-plugin-check.php'))
        {
            if(file_exists(WPMU_PLUGIN_DIR))
                copy(WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/mu-plugins/a-wpvivid-restore-mu-plugin-check.php',WPMU_PLUGIN_DIR.'/a-wpvivid-restore-mu-plugin-check.php');
        }

        echo json_encode($ret);
        die();
    }

    public function reset_plugin()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');

        if (!function_exists('get_plugins'))
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!function_exists('request_filesystem_credentials'))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $wpvivid_backup_pro='wpvivid-backup-pro/wpvivid-backup-pro.php';
        $wpvivid_backup='wpvivid-backuprestore/wpvivid-backuprestore.php';

        $all_plugins = get_option( 'active_plugins', array() );
        unset($all_plugins[$wpvivid_backup_pro]);
        unset($all_plugins[$wpvivid_backup]);

        if (!empty($all_plugins))
        {
            update_option( 'active_plugins', $all_plugins );
        }

        $delete_plugins=new WPvivid_Restore_File_addon();
        $delete_plugins->_delete_plugins($all_plugins);

        $ret['result']='success';
        echo json_encode($ret);
        die();
    }

    public function do_restore()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        ini_set('display_errors', false);
        error_reporting(-1);
        register_shutdown_function(array($this,'deal_restore_shutdown_error'));

        try
        {
            if($this->check_restore_task()==false)
            {
                $ret['result']='failed';
                $ret['error']='restore task has error';
                echo json_encode($ret);
                $this->end_shutdown_function=true;
                die();
            }

            $this->_enable_maintenance_mode();

            $this->set_restore_environment();

            $ret=$this->_do_restore();

            $this->_disable_maintenance_mode();
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $restore_task=get_option('wpvivid_restore_task',array());
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);

            $this->_disable_maintenance_mode();

            $ret['result']='failed';
            $ret['error']=$message;
            $restore_task['status']='error';
            $restore_task['error']=$ret['error'];
            update_option('wpvivid_restore_task',$restore_task,'no');
            echo json_encode($ret);
        }

        die();
    }

    public function _do_restore()
    {
        $ret['result']='success';

        $restore_task=get_option('wpvivid_restore_task',array());
        $this->log=new WPvivid_Log_Ex_addon();
        $this->log->OpenLogFile( $restore_task['log'],'has_folder');

        if(empty($restore_task))
        {
            $ret['result']='failed';
            $ret['error']='task empty';
            return $ret;
        }

        $restore_task['do_sub_task']=false;

        foreach ($restore_task['sub_tasks'] as $key=>$sub_task)
        {
            if($sub_task['finished']==1)
            {
                continue;
            }
            else
            {
                $restore_task['do_sub_task']=$key;
                break;
            }
        }

        if($restore_task['do_sub_task']===false)
        {
            $ret['result']='failed';
            $ret['error']='no sub task';
            $restore_task['status']='error';
            $restore_task['error']=$ret['error'];
            update_option('wpvivid_restore_task',$restore_task,'no');
            return $ret;
        }
        else
        {
            $restore_task['status']='doing sub task';
            $restore_task['update_time']=time();
            update_option('wpvivid_restore_task',$restore_task,'no');
            return $this->do_sub_task();
        }
    }

    public function deal_restore_shutdown_error()
    {
        $error = error_get_last();

        if (!is_null($error))
        {
            if(preg_match('/Allowed memory size of.*$/', $error['message']))
            {
                $restore_task=get_option('wpvivid_restore_task',array());

                $restore_detail_options=$restore_task['restore_detail_options'];
                $db_connect_method=$restore_detail_options['db_connect_method'];
                if($db_connect_method === 'wpdb')
                {
                    $key=$restore_task['do_sub_task'];
                    if($key!==false)
                    {
                        if($restore_task['sub_tasks'][$key]['type']==='databases'||$restore_task['sub_tasks'][$key]['type']==='additional_databases')
                        {
                            global $wpdb;
                            $wpdb->get_results('COMMIT');
                        }
                    }
                }

                $restore_task['status']='error';
                $restore_task['error']=$error['message'];
                $restore_task['error_memory_limit']=true;
                update_option('wpvivid_restore_task',$restore_task,'no');
            }
        }

        die();
    }

    public function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode(DIRECTORY_SEPARATOR,$values);
    }

    public function do_sub_task()
    {
        $restore_task=get_option('wpvivid_restore_task',array());

        $key=$restore_task['do_sub_task'];

        $sub_task=$restore_task['sub_tasks'][$key];

        if($sub_task['type']=='databases')
        {
            $this->log->WriteLog('Start restoring '.$sub_task['type'].'.','notice');

            $restore_db=new WPvivid_Restore_DB_Addon($this->log);
            $ret=$restore_db->restore($sub_task,$restore_task['backup_id']);
            if($ret['result']=='success')
            {
                $this->log->WriteLog('End restore '.$sub_task['type'].'.','notice');
                $restore_task=get_option('wpvivid_restore_task',array());
                $restore_task['sub_tasks'][$key]=$ret['sub_task'];
                $restore_task['status']='sub task finished';
                $restore_task['update_time']=time();
                update_option('wpvivid_restore_task',$restore_task,'no');
            }
            else
            {
                $restore_task=get_option('wpvivid_restore_task',array());
                $restore_task['status']='error';
                $restore_task['error']=$ret['error'];
                wp_cache_flush();
                update_option('wpvivid_restore_task',$restore_task,'no');
            }
        }
        else if($sub_task['type']=='additional_databases')
        {
            $this->log->WriteLog('Start restoring '.$sub_task['type'].'.','notice');

            $restore_db=new WPvivid_Restore_DB_Addon($this->log);
            $ret=$restore_db->restore($sub_task,$restore_task['backup_id']);
            if($ret['result']=='success')
            {
                $this->log->WriteLog('End restore '.$sub_task['type'].'.','notice');
                $restore_task=get_option('wpvivid_restore_task',array());
                $restore_task['sub_tasks'][$key]=$ret['sub_task'];
                $restore_task['status']='sub task finished';
                $restore_task['update_time']=time();
                update_option('wpvivid_restore_task',$restore_task,'no');
            }
            else
            {
                $restore_task=get_option('wpvivid_restore_task',array());
                $restore_task['status']='error';
                $restore_task['error']=$ret['error'];
                wp_cache_flush();
                update_option('wpvivid_restore_task',$restore_task,'no');
            }
        }
        else
        {
            $this->log->WriteLog('Start restoring '.$sub_task['type'].'.','notice');

            $restore_file=new WPvivid_Restore_File_addon($this->log);
            $ret=$restore_file->restore($sub_task,$restore_task['backup_id']);
            if($ret['result']=='success')
            {
                $this->log->WriteLog('End restore '.$sub_task['type'].'.','notice');
                $restore_task=get_option('wpvivid_restore_task',array());
                $restore_task['sub_tasks'][$key]=$ret['sub_task'];
                $restore_task['status']='sub task finished';
                $restore_task['update_time']=time();
                update_option('wpvivid_restore_task',$restore_task,'no');
            }
            else
            {
                $restore_task=get_option('wpvivid_restore_task',array());
                $restore_task['status']='error';
                $restore_task['error']=$ret['error'];
                $this->log->WriteLog('End restore '.$sub_task['type'].' error:'.$ret['error'],'notice');
                update_option('wpvivid_restore_task',$restore_task,'no');
            }
        }


        return $ret;
    }

    public function init_filesystem()
    {
        $credentials = request_filesystem_credentials(wp_nonce_url(admin_url('admin.php')."?page=WPvivid", 'wpvivid-nonce'));

        if ( ! WP_Filesystem($credentials) )
        {
            return false;
        }
        return true;
    }

    public function _enable_maintenance_mode()
    {
        //enable maintenance mode by create the .maintenance file.
        //If your wordpress version is greater than 4.6, use the enable_maintenance_mode filter to make our ajax request pass
        $this->init_filesystem();
        global $wp_filesystem;
        $file = $wp_filesystem->abspath() . '.maintenance';
        $maintenance_string = '<?php $upgrading = ' . (time()+1200) . ';';
        $maintenance_string.='global $wp_version;';
        $maintenance_string.='$version_check=version_compare($wp_version,4.6,\'>\' );';
        $maintenance_string.='if($version_check)';
        $maintenance_string.='{';
        $maintenance_string.='if(!function_exists(\'enable_maintenance_mode_filter\'))';
        $maintenance_string.='{';
        $maintenance_string.='function enable_maintenance_mode_filter($enable_checks,$upgrading)';
        $maintenance_string.='{';
        $maintenance_string.='if(is_admin()&&isset($_POST[\'wpvivid_restore\']))';
        $maintenance_string.='{';
        $maintenance_string.='return false;';
        $maintenance_string.='}';
        $maintenance_string.='return $enable_checks;';
        $maintenance_string.='}';
        $maintenance_string.='}';
        $maintenance_string.='add_filter( \'enable_maintenance_mode\',\'enable_maintenance_mode_filter\',10, 2 );';
        $maintenance_string.='}';
        $maintenance_string.='else';
        $maintenance_string.='{';
        $maintenance_string.='if(is_admin()&&isset($_POST[\'wpvivid_restore\']))';
        $maintenance_string.='{';
        $maintenance_string.='global $upgrading;';
        $maintenance_string.='$upgrading=0;';
        $maintenance_string.='return 1;';
        $maintenance_string.='}';
        $maintenance_string.='}';
        if ($wp_filesystem->exists( $file ) )
        {
            $wp_filesystem->delete($file);
        }
        $wp_filesystem->put_contents($file, $maintenance_string, FS_CHMOD_FILE);
    }

    public function _disable_maintenance_mode()
    {
        $this->init_filesystem();
        global $wp_filesystem;
        $file = $wp_filesystem->abspath() . '.maintenance';
        if ($wp_filesystem->exists( $file ))
        {
            $wp_filesystem->delete($file);
        }
    }

    public function write_litespeed_rule($open=true)
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

        if($litespeed)
        {
            if (function_exists('insert_with_markers'))
            {
                $home_path     = get_home_path();
                $htaccess_file = $home_path . '.htaccess';

                if ( ( ! file_exists( $htaccess_file ) && is_writable( $home_path ) ) || is_writable( $htaccess_file ) )
                {
                    if ( got_mod_rewrite() )
                    {
                        if($open)
                        {
                            $line=array();
                            $line[]='<IfModule Litespeed>';
                            $line[]='RewriteEngine On';
                            $line[]='RewriteRule .* - [E=noabort:1, E=noconntimeout:1]';
                            $line[]='</IfModule>';
                            insert_with_markers($htaccess_file,'WPvivid_Restore',$line);
                        }
                        else
                        {
                            insert_with_markers($htaccess_file,'WPvivid_Restore','');
                        }

                    }
                }
            }
        }
    }

    public function set_restore_environment()
    {
        $restore_task=get_option('wpvivid_restore_task',array());

        $restore_detail_options=$restore_task['restore_detail_options'];
        $memory_limit = $restore_detail_options['restore_memory_limit'];
        $restore_max_execution_time= $restore_detail_options['restore_max_execution_time'];

        @set_time_limit($restore_max_execution_time);

        @ini_set('memory_limit', $memory_limit);
    }

    public function get_restore_progress()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );

        $restore_task=get_option('wpvivid_restore_task',array());

        if($this->check_restore_task()==false)
        {
            $ret['result']='failed';
            $ret['error']='restore task has error';
            $ret['test']=$restore_task;
            echo json_encode($ret);
            die();
        }

        $ret['test']=$restore_task;
        if($restore_task['status']=='error')
        {
            $ret['result']='failed';
            $ret['error']=$restore_task['error'];
            echo json_encode($ret);
            die();
        }

        $key=$restore_task['do_sub_task'];

        if($key===false)
        {
            $ret['result']='success';
            $ret['do_sub_task']=false;
            $ret['status']='ready';
        }
        else
        {
            if(isset($restore_task['sub_tasks'][$key]))
            {
                $sub_task=$restore_task['sub_tasks'][$key];
                $do_sub_task=$sub_task['type'];
                if($sub_task['finished']==1)
                {
                    $ret['result']='success';
                    $ret['do_sub_task']=$do_sub_task;
                    if($this->check_task_finished())
                    {
                        $ret['status']='task finished';
                    }
                    else
                    {
                        $ret['status']='sub task finished';
                    }

                }
                else
                {
                    $ret['result']='success';
                    $ret['do_sub_task']=$do_sub_task;

                    if($restore_task['status']=='sub task finished')
                    {
                        $ret['status']='sub task finished';
                    }
                    else
                    {
                        $common_setting = WPvivid_Setting::get_option('wpvivid_common_setting');

                        if(isset($common_setting['restore_max_execution_time']))
                        {
                            $setting_restore_max_execution_time = intval($common_setting['restore_max_execution_time']);
                        }
                        else{
                            $setting_restore_max_execution_time = WPVIVID_RESTORE_MAX_EXECUTION_TIME;
                        }

                        $restore_detail_options=$restore_task['restore_detail_options'];
                        $restore_max_execution_time= isset($restore_detail_options['restore_max_execution_time'])?$restore_detail_options['restore_max_execution_time']:$setting_restore_max_execution_time;


                        if(time()-$restore_task['update_time']>$restore_max_execution_time)
                        {
                            $restore_task['restore_timeout_count']++;
                            update_option('wpvivid_restore_task',$restore_task,'no');
                            if($restore_task['restore_timeout_count']>6)
                            {
                                $ret['result']='failed';
                                $ret['error']='restore timeout';
                            }
                            else
                            {
                                $ret['status']='sub task finished';
                            }
                        }
                        else if(time()-$restore_task['update_time']>180)
                        {
                            $ret['status']='no response';
                        }
                        else
                        {
                            $ret['status']='doing sub task';
                        }
                    }
                }

                if($ret['result']=='success')
                {
                    $ret['main_msg']='doing restore '.$sub_task['type'];

                    $finished=0;
                    $total=count($restore_task['sub_tasks']);
                    $sub_tasks_progress=array();
                    $sub_tasks_progress_detail=array();
                    if($total==0)
                    {
                        $main_progress=0;
                    }
                    else
                    {
                        $sub_progress=0;
                        foreach ($restore_task['sub_tasks'] as $key=>$sub_task)
                        {
                            if($sub_task['type']=='themes')
                            {
                                $sub_progress_id='wpvivid_restore_themes_progress';
                                $sub_progress_detail_id='wpvivid_restore_themes_progress_detail';
                            }
                            else if($sub_task['type']=='plugin')
                            {
                                $sub_progress_id='wpvivid_restore_plugin_progress';
                                $sub_progress_detail_id='wpvivid_restore_plugin_progress_detail';
                            }
                            else if($sub_task['type']=='wp-content')
                            {
                                $sub_progress_id='wpvivid_restore_wp_content_progress';
                                $sub_progress_detail_id='wpvivid_restore_wp_content_progress_detail';
                            }
                            else if($sub_task['type']=='upload')
                            {
                                $sub_progress_id='wpvivid_restore_upload_progress';
                                $sub_progress_detail_id='wpvivid_restore_upload_progress_detail';
                            }
                            else if($sub_task['type']=='wp-core')
                            {
                                $sub_progress_id='wpvivid_restore_core_progress';
                                $sub_progress_detail_id='wpvivid_restore_core_progress_detail';
                            }
                            else if($sub_task['type']=='custom')
                            {
                                $sub_progress_id='wpvivid_restore_custom_progress';
                                $sub_progress_detail_id='wpvivid_restore_custom_progress_detail';
                            }
                            else if($sub_task['type']=='db'||$sub_task['type']=='databases')
                            {
                                $sub_progress_id='wpvivid_restore_databases_progress';
                                $sub_progress_detail_id='wpvivid_restore_databases_progress_detail';
                            }
                            else if($sub_task['type']=='additional_databases')
                            {
                                $sub_progress_id='wpvivid_restore_additional_db_progress';
                                $sub_progress_detail_id='wpvivid_restore_additional_db_progress_detail';
                            }
                            else
                            {
                                $sub_progress_id='';
                                $sub_progress_detail_id='';
                            }

                            if($sub_task['finished']==1)
                            {
                                $finished++;
                                $sub_progress+=100;
                                $sub_task_progress='Completed - 100%<span class="dashicons dashicons-yes" style="color:#8bc34a;"></span>';
                            }
                            else
                            {
                                if($sub_task['unzip_file']['last_action']=='waiting...')
                                {
                                    $sub_task_progress='waiting...';
                                }
                                else if($sub_task['type']=='databases')
                                {
                                    if($sub_task['unzip_file']['unzip_finished']==0)
                                    {
                                        $sub_task_progress= $sub_task['unzip_file']['last_action'].' - 0%';
                                    }
                                    else
                                    {
                                        if($restore_task['is_migrate'])
                                        {
                                            $file_size=0;
                                            $read_size=0;

                                            foreach ($sub_task['exec_sql']['sql_files'] as $sql_file)
                                            {
                                                $file_size+=$sql_file['sql_file_size'];
                                                $read_size+=$sql_file['sql_offset'];
                                            }

                                            $progress1=intval(($read_size/ $file_size)*50);
                                            $progress2=0;
                                            if(!empty($sub_task['exec_sql']['replace_tables']))
                                            {
                                                $need_replace_table = sizeof($sub_task['exec_sql']['replace_tables']);
                                                $replaced_tables=0;
                                                foreach ($sub_task['exec_sql']['replace_tables'] as $replace_table)
                                                {
                                                    if ($replace_table['finished'] == 1)
                                                    {
                                                        $replaced_tables++;
                                                    }
                                                }
                                                $progress2=intval(($replaced_tables/ $need_replace_table)*50);
                                            }

                                            $progress=$progress1+$progress2;

                                            $sub_progress+=$progress;
                                            $sub_task_progress= $sub_task['exec_sql']['last_action'].' - '.$progress.'%';
                                        }
                                        else
                                        {
                                            $file_size=0;
                                            $read_size=0;

                                            foreach ($sub_task['exec_sql']['sql_files'] as $sql_file)
                                            {
                                                $file_size+=$sql_file['sql_file_size'];
                                                $read_size+=$sql_file['sql_offset'];
                                            }

                                            $progress=intval(($read_size/ $file_size)*100);

                                            $sub_progress+=$progress;
                                            $sub_task_progress= $sub_task['exec_sql']['last_action'].' - '.$progress.'%';
                                        }
                                    }
                                }
                                else
                                {
                                    $files=$sub_task['unzip_file']['files'];
                                    $files_finished=0;
                                    $files_total=count($sub_task['unzip_file']['files']);
                                    foreach ($files as $index=>$file)
                                    {
                                        if ($file['finished'] == 1)
                                        {
                                            $files_finished++;
                                        }
                                    }

                                    if(isset($sub_task['unzip_file']['sum'])&&$sub_task['unzip_file']['start'])
                                    {
                                        $sum=$sub_task['unzip_file']['sum'];
                                        $start=$sub_task['unzip_file']['start'];

                                        if($sum>0)
                                        {
                                            $file_progress=intval((($start/$sum)*100)/$files_total);
                                        }
                                        else
                                        {
                                            $file_progress=0;
                                        }
                                    }
                                    else
                                    {
                                        $file_progress=0;
                                    }
                                    $progress=intval(($files_finished/ $files_total)*100)+$file_progress;
                                    $progress=min(100,$progress);
                                    $sub_progress+=$progress;
                                    $sub_task_progress= $sub_task['unzip_file']['last_action'].' - '.$progress.'%';
                                }
                            }

                            if(!empty($sub_progress_id))
                            {
                                $sub_tasks_progress[$sub_progress_id]=$sub_task_progress;
                            }

                            if(!empty($sub_progress_id))
                            {
                                $sub_tasks_progress_detail[$sub_progress_detail_id]['html']=$sub_task['last_msg'];
                                if($do_sub_task==$sub_task['type'])
                                {
                                    $sub_tasks_progress_detail[$sub_progress_detail_id]['show']=true;
                                }
                                else
                                {
                                    $sub_tasks_progress_detail[$sub_progress_detail_id]['show']=false;
                                }
                            }
                        }
                        $main_progress=intval($sub_progress/$total);
                        //$main_progress=intval(($finished/$total)*100);
                        $main_progress=min($main_progress,100);
                    }
                    $ret['sub_tasks_progress']=$sub_tasks_progress;
                    $ret['sub_tasks_progress_detail']=$sub_tasks_progress_detail;

                    $ret['main_task_progress_total']=$total;
                    $ret['main_task_progress_finished']=$finished;
                    $ret['main_progress']='<span class="wpvivid-span-processed-progress wpvivid-span-processed-restore-percent-progress" style="width: '.$main_progress.'%;">'.$main_progress.'% completed</span>';
                }
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='sub task not found';
            }
        }

        echo json_encode($ret);
        die();
    }

    public function get_log_content($restore_log_file)
    {
        $file =fopen($restore_log_file,'r');

        if(!$file)
        {
            return '';
        }

        $buffer='';
        while(!feof($file))
        {
            $buffer .= fread($file,1024);
        }
        fclose($file);

        return $buffer;
    }

    public function check_task_finished()
    {
        $restore_task=get_option('wpvivid_restore_task',array());

        $finished=false;

        foreach ($restore_task['sub_tasks'] as $sub_task)
        {
            if($sub_task['finished']==1)
            {
                $finished=true;
            }
            else
            {
                $finished=false;
                break;
            }
        }
        return $finished;
    }

    public function deal_restore_finish_shutdown_error()
    {
        $error = error_get_last();
        if (!is_null($error))
        {
            if (empty($error) || !in_array($error['type'], array(E_ERROR,E_RECOVERABLE_ERROR,E_CORE_ERROR,E_COMPILE_ERROR), true))
            {
                $error = false;
            }

            if ($error !== false)
            {
                $message = 'type: '. $error['type'] . ', ' . $error['message'];
                $error_msg='<p style="font-size:1.5em;">Error Info:'.$message.'</p>';
                echo $error_msg;
            }
        }

        die();
    }

    public function finish_restore()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );

        register_shutdown_function(array($this,'deal_restore_finish_shutdown_error'));
        ini_set('display_errors', 0);

        echo '<p style="font-size:1.5em;"><span>The restoration has been successfully completed.</span></p>';

        $this->_disable_maintenance_mode();
        $this->write_litespeed_rule(false);

        if(file_exists(WPMU_PLUGIN_DIR.'/a-wpvivid-restore-mu-plugin-check.php'))
        {
            @unlink(WPMU_PLUGIN_DIR.'/a-wpvivid-restore-mu-plugin-check.php');
        }

        $plugins= get_option( 'wpvivid_save_active_plugins', array() );

        $ret=$this->check_restore_db();

        $this->delete_temp_files();
        delete_transient( 'wp_core_block_css_files' );

        $restore_task=get_option('wpvivid_restore_task',array());

        if($restore_task['is_migrate'])
        {
            $this->check_force_ssl();
            $this->check_admin_plugins();
            $this->flush_elementor_cache();
            $this->regenerate_css_files();
            if(!is_multisite())
            {
                if (function_exists('save_mod_rewrite_rules'))
                {
                    if(isset($restore_task['restore_options']['restore_detail_options']['restore_htaccess'])&&$restore_task['restore_options']['restore_detail_options']['restore_htaccess'])
                    {
                        //
                    }
                    else
                    {
                        if (file_exists(get_home_path() . '.htaccess'))
                        {
                            $htaccess_data = file_get_contents(get_home_path() . '.htaccess');
                            $line = '';
                            if (preg_match('#AddHandler application/x-httpd-php.*#', $htaccess_data, $matcher))
                            {
                                $line = PHP_EOL . $matcher[0];

                                if (preg_match('#<IfModule mod_suphp.c>#', $htaccess_data, $matcher)) {
                                    $line .= PHP_EOL . '<IfModule mod_suphp.c>';
                                    if (preg_match('#suPHP_ConfigPath .*#', $htaccess_data, $matcher)) {
                                        $line .= PHP_EOL . $matcher[0];
                                    }
                                    $line .= PHP_EOL . '</IfModule>';
                                }
                            }
                            else if (preg_match('#AddHandler application/x-httpd-ea-php.*#', $htaccess_data, $matcher))
                            {
                                $line_temp = PHP_EOL . $matcher[0];

                                if (preg_match('#<IfModule mime_module>#', $htaccess_data, $matcher))
                                {
                                    $line .= PHP_EOL . '<IfModule mime_module>';
                                    $line .= $line_temp.PHP_EOL;
                                    $line .= PHP_EOL . '</IfModule>';
                                }
                            }
                            @rename(get_home_path() . '.htaccess', get_home_path() . '.htaccess_old');
                            save_mod_rewrite_rules();
                            if (!empty($line))
                                file_put_contents(get_home_path() . '.htaccess', $line, FILE_APPEND);
                        }
                        else
                        {
                            save_mod_rewrite_rules();
                        }
                    }

                    if(file_exists(get_home_path() . '.user.ini'))
                    {
                        @rename(get_home_path() . '.user.ini', get_home_path() . '.user.ini_old');
                        save_mod_rewrite_rules();
                    }
                }

            }
        }

        if($ret['has_db'])
        {
            $this->active_plugins();
        }
        else
        {
            $this->active_plugins($plugins);
            $this->check_active_theme();
        }

        $this->active_mu_single_plugin($restore_task);

        if($restore_task['is_migrate'])
        {
            //$html.='<p style="font-size:1.5em;"><span>Save permalinks structure:</span><span><a href="'.admin_url('options-permalink.php').'" target="_blank">click here</a></span></p>';
            if($this->check_oxygen())
            {
                echo '<p style="font-size:1.5em;"><span>The restoration is almost complete, but there is a little bit job to do.</span></p>';
                echo '<p style="font-size:1.5em;"><span>We found that your website is using the Oxygen page builder. In order to restore this backup perfectly, please follow</span><span><a href="https://oxygenbuilder.com/documentation/other/importing-exporting/#resigning" target="_blank"> the guide </a>to regenerate the css.</span></p>';
            }

            if($this->check_divi())
            {
                $this->clean_divi_cache();
                echo '<p style="font-size:1.5em;"><span>The restoration is almost complete, but there is a little bit job to do.</span></p>';
                echo '<p style="font-size:1.5em;"><span>We found that your website is using the Divi theme. In order to restore this backup perfectly,</span><span>please follow<a href="https://divitheme.net/clear-divi-cache/" target="_blank"> the guide </a>to clean up the Divi cache</span></p>';
            }
        }

        if(isset( $restore_task['restore_options']['delete_local'])&& $restore_task['restore_options']['delete_local'])
        {
            $backup_id=$restore_task['backup_id'];
            $backup_list=new WPvivid_New_BackupList();
            $backup = $backup_list->get_backup_by_id($backup_id);
            if($backup!==false)
            {
                $backup_item = new WPvivid_New_Backup_Item($backup);
                if($backup_item->get_remote()!==false)
                {
                    $files=$backup_item->get_files(true);
                    foreach ($files as $file)
                    {
                        @unlink($file);
                    }
                }
            }
        }

        $siteurl = get_option( 'siteurl' );
        echo '<p style="font-size:1.5em;"><span><a href="'.$siteurl.'" target="_blank">Visit Site</a></span></p>';

        echo '<p style="font-size:1.5em;"><span><a class="wpvivid-restore-view-log" href="#" data-id="'.$restore_task['log'].'">View Log</a></span></p>';

        delete_option('wpvivid_restore_task');

        wp_cache_flush();
        die();
    }

    public function restore_failed()
    {
        register_shutdown_function(array($this,'deal_restore_finish_shutdown_error'));

        echo '<p style="font-size:1.5em;"><span>Please adjust the advanced settings before restoring and retry.</span></p>';


        $this->_disable_maintenance_mode();
        $this->write_litespeed_rule(false);
        if(file_exists(WPMU_PLUGIN_DIR.'/a-wpvivid-restore-mu-plugin-check.php'))
        {
            @unlink(WPMU_PLUGIN_DIR.'/a-wpvivid-restore-mu-plugin-check.php');
        }
        $plugins= get_option( 'wpvivid_save_active_plugins', array() );

        $this->check_active_theme();

        $this->delete_temp_tables();
        $this->delete_temp_files();

        $this->active_plugins($plugins);

        $restore_task=get_option('wpvivid_restore_task',array());

        $restore_detail_options=$restore_task['restore_detail_options'];
        $unzip_files_pre_request=$restore_detail_options['unzip_files_pre_request'];

        if($restore_task['status']=='error')
        {
            echo '<p style="font-size:1.5em;">Error Info:'.$restore_task['error'].'</p>';
            if(isset($restore_task['error_memory_limit']))
            {
                echo '<p style="font-size:1.5em;">Memory exhausted during restoring..</p>';
            }
            else if(isset($restore_task['error_mu_require_file']))
            {
                echo '<p style="font-size:1.5em;">Restore must-use plugin '.$restore_task['error_mu_require_file'].' error.Plugin require file not found..</p>';
            }
            else
            {
                /*
                $key=$restore_task['do_sub_task'];
                $sub_task=$restore_task['sub_tasks'][$key];
                $type=$sub_task['type'];

                if($type==='databases'||$type==='additional_databases')
                {
                    if($sub_task['unzip_file']['unzip_finished']==0)
                    {
                        echo '<p style="font-size:1.5em;">Unzipping file:'.$sub_task['unzip_file']['last_unzip_file'].'</p>';
                    }
                    else
                    {
                        echo '<p style="font-size:1.5em;">restore sql file offset:'.$sub_task['exec_sql']['sql_offset'].'</p>';
                        if(!empty($sub_task['exec_sql']['current_table']))
                        {
                            echo '<p style="font-size:1.5em;">restore table:'.$sub_task['exec_sql']['current_table'].'</p>';
                            if($sub_task['exec_sql']['current_need_replace_table'])
                            {
                                echo '<p style="font-size:1.5em;">replace rows at:'.$sub_task['exec_sql']['current_replace_row'].'</p>';
                            }
                        }
                    }
                }
                else
                {
                    echo '<p style="font-size:1.5em;">Unzipping file:'.$sub_task['unzip_file']['last_unzip_file'].'</p>';
                    if($unzip_files_pre_request===false)
                    {
                        echo '<p style="font-size:1.5em;">files count:'.$sub_task['unzip_file']['last_unzip_file_index'].'</p>';
                    }
                }
                */
            }
        }
        else
        {
            $key=$restore_task['do_sub_task'];

            if($key===false)
            {
            }
            else
            {
                if(isset($restore_task['sub_tasks'][$key]))
                {
                    //$error_msg='restore sub task '.$restore_task['sub_tasks'][$key]['type'].' timeout.';
                    if($restore_task['sub_tasks'][$key]['type']==='databases'||$restore_task['sub_tasks'][$key]['type']==='additional_databases')
                    {
                        echo 'Sql file importing time out.';
                        //$error_msg.='<p style="font-size:1.5em;">Pleases try to increase your max_allowed_packet(recommend 32M)</p>';
                        //$error_msg.='<p style="font-size:1.5em;">or reduce SQL buffer will be processed every PHP request(recommend 5M)</p>';
                        //$error_msg.='<p style="font-size:1.5em;">or reduce maximum rows of data in MYSQL table will be imported every time when restoring(recommend 10000)</p>';
                    }
                    else
                    {
                        echo 'File extracting time out.';
                        //$error_msg.='<p style="font-size:1.5em;">Pleases try to check user unzip files using index,and set files are unzipped every PHP request(recommend 1000)</p>';
                        //$error_msg.='<p style="font-size:1.5em;">and increase your PHP - max execution time(900s)</p>';
                    }
                }
                else
                {
                    //$error_msg='';
                    echo 'Restoring time out.';
                }
            }
        }

        echo '<p><input class="button-primary" id="wpvivid_restore_retry" type="submit" value="Try it again"></p>';
        echo '<p style="font-size:1.5em;"><span><a class="wpvivid-restore-view-log" href="#" data-id="'.$restore_task['log'].'">View Log</a></span></p>';
        delete_option('wpvivid_restore_task');
        wp_cache_flush();

        die();
    }

    public function check_oxygen()
    {
        if (!function_exists('get_plugins'))
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( ( $plugins = get_plugins() ) )
        {
            foreach ( $plugins as $key => $plugin )
            {
                if ( $key === 'oxygen/functions.php' )
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function check_divi()
    {
        $themes=wp_get_themes();
        foreach ($themes as $key=>$theme)
        {
            if ( $key === 'Divi' )
            {
                return true;
            }
        }
        return false;
    }

    public function clean_divi_cache()
    {
        $_post_id = '*';
        $_owner   = '*';
        $_slug    = '*';

        $cache_dir= WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'et-cache';

        $files = array_merge(
        // Remove any CSS files missing a parent folder.
            (array) glob( "{$cache_dir}/et-{$_owner}-*" ),
            // Remove CSS files for individual posts or all posts if $post_id set to 'all'.
            (array) glob( "{$cache_dir}/{$_post_id}/et-{$_owner}-{$_slug}*" ),
            // Remove CSS files that contain theme builder template CSS.
            // Multiple directories need to be searched through since * doesn't match / in the glob pattern.
            (array) glob( "{$cache_dir}/*/et-{$_owner}-{$_slug}-*tb-{$_post_id}*" ),
            (array) glob( "{$cache_dir}/*/*/et-{$_owner}-{$_slug}-*tb-{$_post_id}*" ),
            (array) glob( "{$cache_dir}/*/*/*/et-{$_owner}-{$_slug}-*tb-{$_post_id}*" ),
            (array) glob( "{$cache_dir}/*/et-{$_owner}-{$_slug}-*tb-for-{$_post_id}*" ),
            (array) glob( "{$cache_dir}/*/*/et-{$_owner}-{$_slug}-*tb-for-{$_post_id}*" ),
            (array) glob( "{$cache_dir}/*/*/*/et-{$_owner}-{$_slug}-*tb-for-{$_post_id}*" ),
            // Remove Dynamic CSS files for categories, tags, authors, archives, homepage post feed and search results.
            (array) glob( "{$cache_dir}/taxonomy/*/*/et-{$_owner}-dynamic*" ),
            (array) glob( "{$cache_dir}/author/*/et-{$_owner}-dynamic*" ),
            (array) glob( "{$cache_dir}/archive/et-{$_owner}-dynamic*" ),
            (array) glob( "{$cache_dir}/search/et-{$_owner}-dynamic*" ),
            (array) glob( "{$cache_dir}/notfound/et-{$_owner}-dynamic*" ),
            (array) glob( "{$cache_dir}/home/et-{$_owner}-dynamic*" )
        );

        $this->_remove_files_in_directory( $files, $cache_dir );

        $this->remove_empty_directories($cache_dir );

        delete_option( '_et_builder_global_feature_cache' );

        $post_meta_caches = array(
            'et_enqueued_post_fonts',
            '_et_dynamic_cached_shortcodes',
            '_et_dynamic_cached_attributes',
            '_et_builder_module_features_cache',
        );

        // Clear post meta caches.
        foreach ( $post_meta_caches as $post_meta_cache ) {
            if ( ! empty( $post_id ) ) {
                delete_post_meta( $post_id, $post_meta_cache );
            } else {
                delete_post_meta_by_key( $post_meta_cache );
            }
        }
    }

    public function remove_empty_directories( $path ) {
        $path = realpath( $path );

        if ( empty( $path ) ) {
            // $path doesn't exist
            return;
        }

        $path        = $this->normalize_path( $path );
        $content_dir = $this->normalize_path( WP_CONTENT_DIR );

        if ( 0 !== strpos( $path, $content_dir ) || $content_dir === $path ) {
            return;
        }

        $this->_remove_empty_directories($path);
    }

    public function _remove_empty_directories($path)
    {
        if ( ! is_dir( $path ) ) {
            return false;
        }

        $empty              = true;
        $directory_contents = glob( untrailingslashit( $path ) . '/*' );

        foreach ( (array) $directory_contents as $item ) {
            if ( ! $this->_remove_empty_directories( $item ) ) {
                $empty = false;
            }
        }

        return $empty ? @rmdir( $path ) : false;
    }

    public function _remove_files_in_directory( $files, $cache_dir )
    {
        $cache_dir=$this->normalize_path( $cache_dir );

        foreach ( $files as $file )
        {
            $file =$this->normalize_path( $file );

            if ( ! $this->starts_with( $file, $cache_dir ) ) {
                // File is not located inside cache directory so skip it.
                continue;
            }

            if ( is_file( $file ) )
            {
                @unlink($file);
            }
        }
    }

    public function starts_with( $string, $substring ) {
        return 0 === strpos( $string, $substring );
    }

    public function normalize_path( $path = '' )
    {
        $path = (string) $path;
        $path = str_replace( '..', '', $path );

        if ( function_exists( 'wp_normalize_path' ) ) {
            return wp_normalize_path( $path );
        }

        return str_replace( '\\', '/', $path );
    }

    public function flush_elementor_cache()
    {
        $wp_upload_dir=wp_upload_dir( null, false );
        $path =  $wp_upload_dir['basedir'] . '/elementor/css/' . '*';

        foreach ( glob( $path ) as $file_path ) {
            unlink( $file_path );
        }

        $google_font_path = $wp_upload_dir['basedir'] . '/elementor/google-fonts';
        $google_font_folder = array('css', 'fonts');
        foreach ($google_font_folder as $folder)
        {
            $path = $google_font_path.'/'.$folder.'/'.'*';
            foreach ( glob( $path ) as $file_path ) {
                wp_delete_file( $file_path );
            }
        }

        delete_post_meta_by_key( '_elementor_css' );
        delete_post_meta_by_key( '_elementor_inline_svg' );
        delete_post_meta_by_key( '_elementor_element_cache' );
        delete_post_meta_by_key( '_elementor_page_assets' );
        delete_option( '_elementor_global_css' );
        delete_option( 'elementor-custom-breakpoints-files' );
        delete_option( '_elementor_assets_data' );
        delete_option( '_elementor_local_google_fonts' );
    }

    public function regenerate_css_files()
    {
        delete_option( 'generateblocks_dynamic_css_posts' );
    }

    public function check_force_ssl()
    {
        $plugins=array();
        if ( ! is_ssl() )
        {
            $plugins[]='really-simple-ssl/rlrsssl-really-simple-ssl.php';
            $plugins[]='wordpress-https/wordpress-https.php';
            $plugins[]='wp-force-ssl/wp-force-ssl.php';
            $plugins[]='force-https-littlebizzy/force-https.php';

            $current = get_option( 'active_plugins', array() );

            foreach ( $plugins as $plugin )
            {
                if ( ( $key = array_search( $plugin, $current ) ) !== false )
                {
                    unset( $current[ $key ] );
                }
            }

            update_option( 'active_plugins', $current );

            if ( get_option( 'woocommerce_force_ssl_checkout' ) )
            {
                update_option( 'woocommerce_force_ssl_checkout', 'no' );
            }
        }

    }

    public function check_admin_plugins()
    {
        $plugins=array();
        $plugins[]='wps-hide-login/wps-hide-login.php';
        $plugins[]='lockdown-wp-admin/lockdown-wp-admin.php';
        $plugins[]='rename-wp-login/rename-wp-login.php';
        $plugins[]='change-wp-admin-login/change-wp-admin-login.php';
        $plugins[]='hide-my-wp/index.php';
        $plugins[]='hide-login-page/hide-login-page.php';
        $plugins[]='wp-hide-security-enhancer/wp-hide.php';
        //
        $current = get_option( 'active_plugins', array() );

        foreach ( $plugins as $plugin )
        {
            if ( ( $key = array_search( $plugin, $current ) ) !== false )
            {
                unset( $current[ $key ] );
            }
        }

        update_option( 'active_plugins', $current );
    }

    public function delete_temp_tables()
    {
        $restore_task=get_option('wpvivid_restore_task',array());
        $this->log=new WPvivid_Log_Ex_addon();
        $this->log->OpenLogFile( $restore_task['log'],'has_folder');
        foreach ($restore_task['sub_tasks'] as $sub_task)
        {
            if($sub_task['type']=='databases')
            {
                $restore_db=new WPvivid_Restore_DB_Addon($this->log);
                $restore_db->remove_tmp_table($sub_task);
            }
        }

        $ret['result']='success';
        return $ret;
    }

    public function check_restore_db()
    {
        $has_db=false;

        $restore_task=get_option('wpvivid_restore_task',array());
        $this->log=new WPvivid_Log_Ex_addon();
        $this->log->OpenLogFile( $restore_task['log'],'has_folder');
        foreach ($restore_task['sub_tasks'] as $sub_task)
        {
            if($sub_task['type']=='databases')
            {
                $has_db=true;

                $restore_db=new WPvivid_Restore_DB_Addon($this->log);
                $current_setting = WPvivid_Setting::export_setting_to_json();

                $ret=$restore_db->rename_db($sub_task);
                WPvivid_Setting::import_json_to_setting($current_setting);
                do_action('wpvivid_reset_schedule');
                do_action('wpvivid_do_after_restore_db');

                if($restore_task['is_migrate'] == '1')
                {
                    $option_name = 'wpvivid_staging_task_list';
                    global $wpdb;
                    $result = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $option_name));
                    if(!$result)
                    {
                        $this->log->WriteLog('Delete migration option failed.', 'notice');
                    }
                }

                if($ret['result']!='success')
                {
                    $this->log->WriteLog('Restore database failed:'.$ret['error'],'notice');
                    $restore_db->remove_tmp_table($sub_task);
                    return $ret;
                }
                break;
            }
        }


        $ret['result']='success';
        $ret['has_db']=$has_db;
        return $ret;
    }

    public function delete_temp_files()
    {
        $restore_task=get_option('wpvivid_restore_task',array());
        $this->log=new WPvivid_Log_Ex_addon();
        $this->log->OpenLogFile( $restore_task['log'],'has_folder');
        $this->log->WriteLog('Deleting temp files.','notice');
        $backup_id=$restore_task['backup_id'];
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        $backup_item=new WPvivid_New_Backup_Item($backup);
        foreach($restore_task['sub_tasks'] as $key => $task)
        {
            foreach ($task['unzip_file']['files'] as $file)
            {
                if(isset($file['has_child']))
                {
                    $path= $backup_item->get_download_local_path().$file['file_name'];
                    //$this->log->WriteLog('clean file:'.$path,'notice');
                    if(file_exists($path))
                    {
                        @unlink($path);
                    }
                }
            }
        }
    }

    public function check_restore_task()
    {
        $restore_task=get_option('wpvivid_restore_task',array());

        if(empty($restore_task))
        {
            return false;
        }

        $backup_id=$restore_task['backup_id'];
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        if($backup===false)
        {
            return false;
        }

        if(empty($restore_task['sub_tasks']))
        {
            return false;
        }

        if($restore_task['do_sub_task']===false)
        {
            return true;
        }
        else
        {
            $sub_task_key=$restore_task['do_sub_task'];
            if(isset($restore_task['sub_tasks'][$sub_task_key]))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function scan_last_restore()
    {
        $restore_task=get_option('wpvivid_restore_task',array());

        if(empty($restore_task))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function create_restore_task($backup_id,$restore_options,$restore_version)
    {
        $restore_task=array();
        $restore_task['backup_id']=$backup_id;
        $restore_task['restore_options']=$restore_options;
        $restore_task['update_time']=time();
        $restore_task['restore_timeout_count']=0;
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        if($backup===false)
        {
            $ret['result']='failed';
            $ret['error']='backup not found';
            return $ret;
        }


        $backup_item = new WPvivid_New_Backup_Item($backup);
        $backup_file_info=$this->get_restore_files_info($backup_item,$restore_version,true);
        foreach ($backup_file_info as $key=>$files_info)
        {
            usort ($files_info['files'],function($a, $b)
            {
                if(isset($a['has_version']) && $a['has_version'] && isset($b['has_version']) && $b['has_version'])
                {
                    if($a['version']>$b['version'])
                    {
                        return 1;
                    }
                    if($a['version']<$b['version'])
                    {
                        return -1;
                    }
                    else
                    {
                        return 0;
                    }
                }
                else
                {
                    return 0;
                }
            });
            $backup_file_info[$key]['files']=$files_info['files'];
        }

        $sub_tasks=array();

        if(isset($restore_options['selected']))
            $selected=$restore_options['selected'];
        else
            $selected=array();

        $b_reset_plugin=false;

        foreach ($backup_file_info as $key=>$files_info)
        {
            if(!empty($selected))
            {
                if(isset($selected[$key])&&$selected[$key]==1)
                {

                }
                else
                {
                    continue;
                }

            }

            $task['type']=$key;
            if(isset($restore_options[$key]))
                $task['options']=$restore_options[$key];
            else
                $task['options']=array();

            $task['options']['restore_reset']=true;
            if($key=='themes')
            {
                $task['priority']=1;
                $task['unzip_file']['files']=$files_info['files'];
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['unzip_file']['last_unzip_file_index']=0;
            }
            else if($key=='plugin')
            {
                $task['priority']=2;
                $task['unzip_file']['files']=$files_info['files'];
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['unzip_file']['last_unzip_file_index']=0;

                $b_reset_plugin=isset($restore_options['restore_detail_options']['restore_reset'])?$restore_options['restore_detail_options']['restore_reset']:false;;
            }
            else if($key=='wp-content')
            {
                $task['priority']=3;
                $task['unzip_file']['files']=$files_info['files'];
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['unzip_file']['last_unzip_file_index']=0;
            }
            else if($key=='upload')
            {
                $task['priority']=4;
                $task['unzip_file']['files']=$files_info['files'];
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['unzip_file']['last_unzip_file_index']=0;
            }
            else if($key=='wp-core')
            {
                $task['priority']=5;
                $task['unzip_file']['files']=$files_info['files'];
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['unzip_file']['last_unzip_file_index']=0;
            }
            else if($key=='custom')
            {
                $task['priority']=6;
                $task['unzip_file']['files']=$files_info['files'];
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['unzip_file']['last_unzip_file_index']=0;
            }
            else if($key=='db'||$key=='databases')
            {
                $task['type']='databases';

                $task['unzip_file']['files']=$files_info['files'];

                $task['options']=array_merge($task['options'],$task['unzip_file']['files'][0]['options']);

                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';

                $task['exec_sql']['init_sql_finished']=0;
                $task['exec_sql']['create_snapshot_finished']=0;
                $task['exec_sql']['exec_sql_finished']=0;
                $task['exec_sql']['replace_rows_finished']=0;

                $task['exec_sql']['current_table']='';
                $task['exec_sql']['current_old_table']='';
                $task['exec_sql']['replace_tables']=array();
                //$task['exec_sql']['current_replace_table_finish']=false;
                //$task['exec_sql']['current_need_replace_table']=false;
                //$task['exec_sql']['current_replace_row']=0;

                $task['exec_sql']['last_action']='waiting...';
                $task['exec_sql']['last_query']='';

                $uid=$this->create_db_uid();
                if($uid===false)
                {
                    $ret['result']='failed';
                    $ret['error']='create db uid failed';
                    return $ret;
                }
                $task['exec_sql']['db_id']=$uid;
                $task['exec_sql']['sql_files']=array();
                $task['priority']=8;
                $restore_task['restore_db']=1;
            }
            else if($key=='additional_databases')
            {
                $task['type']='additional_databases';
                $task['unzip_file']['files']=$files_info['files'];
                $task['options']=array_merge($task['options'],$task['unzip_file']['files'][0]['options']);
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['exec_sql']['init_sql_finished']=0;
                $task['exec_sql']['create_snapshot_finished']=0;
                $task['exec_sql']['exec_sql_finished']=0;
                $task['exec_sql']['current_table']='';
                $task['exec_sql']['current_old_table']='';
                $task['exec_sql']['current_replace_table_finish']=false;
                $task['exec_sql']['current_need_replace_table']=false;
                $task['exec_sql']['current_replace_row']=0;
                $task['exec_sql']['last_action']='waiting...';
                $task['exec_sql']['last_query']='';
                $uid=$this->create_db_uid();
                if($uid===false)
                {
                    $ret['result']='failed';
                    $ret['error']='create db uid failed';
                    return $ret;
                }
                $task['exec_sql']['db_id']=$uid;
                $task['exec_sql']['sql_files']=array();
                $task['priority']=9;
                $restore_task['restore_db']=1;
            }
            else
            {
                $task['priority']=7;
                $task['unzip_file']['files']=$files_info['files'];
                $task['unzip_file']['unzip_finished']=0;
                $task['unzip_file']['last_action']='waiting...';
                $task['unzip_file']['last_unzip_file']='';
                $task['unzip_file']['last_unzip_file_index']=0;
            }

            $restore_reset=isset($restore_options['restore_detail_options']['restore_reset'])?$restore_options['restore_detail_options']['restore_reset']:false;
            $task['finished']=0;
            $task['last_msg']='waiting...';
            if($restore_reset)
            {
                $task['restore_reset']=true;
                $task['restore_reset_finished']=false;
            }
            else
            {
                $task['restore_reset']=false;
            }

            $restore_htaccess=isset($restore_options['restore_detail_options']['restore_htaccess'])?$restore_options['restore_detail_options']['restore_htaccess']:false;
            if($restore_htaccess)
            {
                $task['options']['restore_htaccess']=true;
            }
            else
            {
                $task['options']['restore_htaccess']=false;
            }

            $sub_tasks[]=$task;
        }
        usort($sub_tasks, function ($a, $b)
        {
            if ($a['priority'] == $b['priority'])
                return 0;

            if ($a['priority'] > $b['priority'])
                return 1;
            else
                return -1;
        });

        $restore_task['is_migrate'] = $backup_item->check_migrate_file();
        $restore_task['sub_tasks']=$sub_tasks;
        $restore_task['do_sub_task']=false;

        if($restore_options['restore_detail_options']['restore_level']=='custom')
        {
            $restore_task['restore_detail_options']=array_merge($restore_options['restore_custom_detail_options'],$restore_options['restore_detail_options']);
        }
        else
        {
            $restore_task['restore_detail_options']=$this->get_default_restore_options($restore_options['restore_detail_options']);
        }
        $id=uniqid('wpvivid-');
        $log_file_name=$id.'_restore_log.txt';
        $this->log=new WPvivid_Log_Ex_addon();
        $log_file=$this->log->GetSaveLogFolder().$log_file_name;
        $restore_task['log']=$log_file;

        $restore_task['last_log']='Init restore task completed.';
        $this->log->WriteLog($restore_task['last_log'],'notice');
        $restore_task['status']='ready';
        update_option('wpvivid_restore_task',$restore_task,'no');
        $ret['result']='success';
        $ret['reset_plugin']=$b_reset_plugin;
        $ret['task']=$restore_task;
        return $ret;
    }

    public function get_default_restore_options($restore_detail_options)
    {
        if($restore_detail_options['restore_level']=='low')
        {
            $restore_detail_options['max_allowed_packet']=32;
            $restore_detail_options['replace_rows_pre_request']=1000;
            $restore_detail_options['restore_max_execution_time']=1800;
            $restore_detail_options['restore_memory_limit']='512M';
            $restore_detail_options['sql_file_buffer_pre_request']='1';
            $restore_detail_options['use_index']=1;
            $restore_detail_options['unzip_files_pre_request']=100;
        }
        else if($restore_detail_options['restore_level']=='mid')
        {
            $restore_detail_options['max_allowed_packet']=32;
            $restore_detail_options['replace_rows_pre_request']=10000;
            $restore_detail_options['restore_max_execution_time']=1800;
            $restore_detail_options['restore_memory_limit']='512M';
            $restore_detail_options['sql_file_buffer_pre_request']='5';
            $restore_detail_options['use_index']=1;
            $restore_detail_options['unzip_files_pre_request']=1000;
        }
        else if($restore_detail_options['restore_level']=='high')
        {
            $restore_detail_options['max_allowed_packet']=32;
            $restore_detail_options['replace_rows_pre_request']=10000;
            $restore_detail_options['restore_max_execution_time']=1800;
            $restore_detail_options['restore_memory_limit']='512M';
            $restore_detail_options['sql_file_buffer_pre_request']='10';
            $restore_detail_options['use_index']=0;
            $restore_detail_options['unzip_files_pre_request']=10000;
        }
        return $restore_detail_options;
    }

    public function create_db_uid()
    {
        global $wpdb;
        $count = 0;

       do
        {
            $count++;
            $uid = sprintf('%06x', mt_rand(0, 0xFFFFFF));

            $verify_db = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', array('%' . $uid . '%')));
        } while (!empty($verify_db) && $count < 10);

        if ($count == 10)
        {
            $uid = false;
        }

        return $uid;
    }

    public function get_backup_data($backup_item)
    {
        $files=$backup_item->get_files(false);
        foreach ($files as $file)
        {
            $file_info=$backup_item->get_file_info($file);
            if(isset($file_info['version']))
            {
                $this->backup_data['has_version']=true;
            }
            else
            {
                $this->backup_data['has_version']=false;
            }

            if(isset($file_info['php_version']))
            {
                //7.3.27-1~deb10u1
                preg_match("/((?:[0-9]+\.?)+)/i",  $file_info['php_version'], $matches);
                $this->backup_data['php_version']= $matches[1];
            }
            if(isset($file_info['mysql_version']))
            {
                $this->backup_data['mysql_version']= $file_info['mysql_version'];
            }

            if(isset($file_info['wp_version']))
            {
                $this->backup_data['wp_version']=$file_info['wp_version'];
            }
            break;
        }
    }

    public function get_backup_zero_date($backup_item)
    {
        $files=$backup_item->get_files(false);
        foreach ($files as $file)
        {
            $file_info=$backup_item->get_file_info($file);
            if (isset($file_info['has_child']))
            {
                if (isset($file_info['child_file']))
                {
                    foreach ($file_info['child_file'] as $child_file_name => $child_file_info)
                    {
                        if(isset($child_file_info['find_zero_date']) && $child_file_info['find_zero_date'] == 1)
                        {
                            $this->backup_data['find_zero_date']=true;
                        }
                    }
                }
            }
            else
            {
                if(isset($file_info['find_zero_date']) && $file_info['find_zero_date'] == 1)
                {
                    $this->backup_data['find_zero_date']=true;
                }
            }
        }
    }

    public function get_backup_is_mu($backup_item)
    {
        $files=$backup_item->get_files(false);
        foreach ($files as $file)
        {
            $file_info=$backup_item->get_file_info($file);
            if (isset($file_info['has_child']))
            {
                if (isset($file_info['child_file']))
                {
                    foreach ($file_info['child_file'] as $child_file_name => $child_file_info)
                    {
                        if(isset($child_file_info['find_zero_date']) && $child_file_info['find_zero_date'] == 1)
                        {
                            $this->backup_data['find_zero_date']=true;
                        }
                        if(isset($child_file_info['is_mu_site']))
                        {
                            if($child_file_info['is_mu_site'] == 1)
                            {
                                $this->backup_data['is_mu_site']=true;
                            }
                            else
                            {
                                $this->backup_data['is_mu_site']=false;
                            }
                        }
                    }
                }
            }
            else
            {
                if(isset($file_info['find_zero_date']) && $file_info['find_zero_date'] == 1)
                {
                    $this->backup_data['find_zero_date']=true;
                }
                if(isset($file_info['is_mu_site']))
                {
                    if($file_info['is_mu_site'] == 1)
                    {
                        $this->backup_data['is_mu_site']=true;
                    }
                    else
                    {
                        $this->backup_data['is_mu_site']=false;
                    }
                }
            }
        }
    }

    public function is_incremental_backup($backup_item)
    {
        $files=$backup_item->get_files(false);
        $files_info=array();

        foreach ($files as $file)
        {
            $files_info[$file]=$backup_item->get_file_info($file);
            if(isset($file_info['version']))
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        return false;
    }

    public function get_restore_files_info($backup_item,$restore_version=0,$use_index=0)
    {
        $files=$backup_item->get_files(false);
        $files_info=array();

        foreach ($files as $file)
        {
            $files_info[$file]=$backup_item->get_file_info($file);
        }
        $info=array();
        $added_files=array();

        foreach ($files_info as $file_name=>$file_info)
        {
            if(isset($file_info['has_child']))
            {
                $info=$this->get_has_child_file_info($info,$file_name,$file_info,$added_files,$restore_version,$use_index);
            }
            else
            {
                if(isset($file_info['file_type']))
                {
                    if(isset($file_info['version']))
                    {
                        if($restore_version===false)
                        {
                            if (!in_array($file_name, $added_files))
                            {
                                $file_data['file_name']=$file_name;
                                $file_data['version']=$file_info['version'];
                                $file_data['has_version']=true;
                                $file_data['finished']=0;
                                if($use_index)
                                {
                                    $file_data['index']=0;
                                }
                                $file_data['options']=$file_info;
                                $info[$file_info['file_type']]['files'][]= $file_data;
                                $added_files[]=$file_name;
                            }
                        }
                        else
                        {
                            $version=$restore_version;
                            if($version>=$file_info['version'])
                            {
                                if (!in_array($file_name, $added_files))
                                {
                                    $file_data['file_name']=$file_name;
                                    $file_data['version']=$version;
                                    $file_data['has_version']=true;
                                    $file_data['finished']=0;
                                    if($use_index)
                                    {
                                        $file_data['index']=0;
                                    }
                                    $file_data['options']=$file_info;
                                    $info[$file_info['file_type']]['files'][]= $file_data;
                                    $added_files[]=$file_name;
                                }
                            }
                        }
                    }
                    else
                    {
                        if (!in_array($file_name, $added_files))
                        {
                            $file_data['file_name']=$file_name;
                            $file_data['version']=0;
                            $file_data['finished']=0;
                            if($use_index)
                            {
                                $file_data['index']=0;
                            }
                            $file_data['options']=$file_info;
                            $info[$file_info['file_type']]['files'][]= $file_data;
                            $added_files[]=$file_name;
                        }
                    }
                }
            }
        }
        return $info;
    }

    public function get_has_child_file_info($info,$file_name,$file_info,&$added_files,$restore_version=0,$use_index=0)
    {
        foreach ($file_info['child_file'] as $child_file_name=>$child_file_info)
        {
            if(isset($child_file_info['file_type']))
            {
                if(isset($child_file_info['version']))
                {
                    $info=$this->get_file_version_info($info,$file_name,$file_info,$child_file_name,$child_file_info,$restore_version,$added_files,$use_index);
                }
                else
                {
                    if (!in_array($child_file_name, $added_files))
                    {
                        $file_data['file_name']=$child_file_name;
                        $file_data['version']=0;
                        $file_data['parent_file']=$file_name;
                        $file_data['has_child']=1;
                        $file_data['extract_child_finished']=0;
                        $file_data['finished']=0;
                        if($use_index)
                        {
                            $file_data['index']=0;
                        }
                        $file_data['options']=$file_info['child_file'][$child_file_name];
                        $info[$child_file_info['file_type']]['files'][]=$file_data;
                        $added_files[]=$child_file_name;
                    }
                }
            }
        }
        return $info;
    }

    public function get_file_version_info($info,$file_name,$file_info,$child_file_name,$child_file_info,$restore_version,&$added_files,$use_index)
    {
        if($restore_version===false||$restore_version>=$child_file_info['version'])
        {
            if (!in_array($child_file_name, $added_files))
            {
                $file_data['file_name']=$child_file_name;
                $file_data['version']=$child_file_info['version'];
                $file_data['has_version']=true;
                $file_data['parent_file']=$file_name;
                $file_data['has_child']=1;
                $file_data['finished']=0;
                if($use_index)
                {
                    $file_data['index']=0;
                }
                $file_data['options']=$file_info['child_file'][$child_file_name];
                $info[$child_file_info['file_type']]['files'][]=$file_data;
                $added_files[]=$child_file_name;
            }
        }

        return $info;
    }

    public function deactivate_plugins()
    {
        if(is_multisite())
        {
            $current =  get_site_option( 'active_sitewide_plugins' );
            update_option( 'wpvivid_save_active_plugins', $current, 'no' );

            $wpvivid_backup_pro='wpvivid-backup-pro/wpvivid-backup-pro.php';
            $wpvivid_backup='wpvivid-backuprestore/wpvivid-backuprestore.php';

            if (array_key_exists($wpvivid_backup_pro, $current)!== false)
            {
                unset($current[$wpvivid_backup_pro]);
            }

            if (array_key_exists($wpvivid_backup, $current) !== false)
            {
                unset($current[$wpvivid_backup]);
            }
            deactivate_plugins($current, true, true);
        }
        else
        {
            $current = get_option( 'active_plugins', array() );
            update_option( 'wpvivid_save_active_plugins', $current, 'no' );

            $wpvivid_backup_pro='wpvivid-backup-pro/wpvivid-backup-pro.php';
            $wpvivid_backup='wpvivid-backuprestore/wpvivid-backuprestore.php';

            if (($key = array_search($wpvivid_backup_pro, $current)) !== false)
            {
                unset($current[$key]);
            }

            if (($key = array_search($wpvivid_backup, $current)) !== false)
            {
                unset($current[$key]);
            }
            deactivate_plugins($current, true, false);
        }

        if(file_exists(WPMU_PLUGIN_DIR))
        {
            if(file_exists(WP_CONTENT_DIR.'/wpvivid_mu_plugins'))
            {
                $this->rrmdir(WP_CONTENT_DIR.'/wpvivid_mu_plugins');
            }

            rename(WPMU_PLUGIN_DIR,WP_CONTENT_DIR.'/wpvivid_mu_plugins');
            mkdir(WPMU_PLUGIN_DIR);
        }
    }

    public function deactivate_theme()
    {
        $current_template = get_option( 'template', '' );
        update_option( 'wpvivid_save_theme_template', $current_template, 'no' );
        $current_stylesheet = get_option( 'stylesheet', '' );
        update_option( 'wpvivid_save_theme_stylesheet', $current_stylesheet, 'no' );
        $current_theme = get_option( 'current_theme', '' );
        update_option( 'wpvivid_save_current_theme', $current_theme, 'no' );

        update_option('template', '');
        update_option('stylesheet', '');
        update_option('current_theme', '');
    }

    public function check_active_theme()
    {
        $save_template = get_option( 'wpvivid_save_theme_template', '' );
        $save_stylesheet = get_option( 'wpvivid_save_theme_stylesheet', '' );
        $save_theme = get_option( 'wpvivid_save_current_theme', '' );

        $themes_path = get_theme_root();
        if(file_exists($themes_path . DIRECTORY_SEPARATOR . $save_stylesheet))
        {
            update_option('template', $save_template);
            update_option('stylesheet', $save_stylesheet);
            update_option('current_theme', $save_theme);
        }
    }

    public function active_plugins($plugins=array())
    {
        wp_cache_flush();

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if(is_multisite())
        {
            $active_sitewide_plugins =  get_site_option( 'active_sitewide_plugins' );
            $current=array();
            $current[]='wpvivid-backuprestore/wpvivid-backuprestore.php';
            $current[]='wpvivid-backup-pro/wpvivid-backup-pro.php';
            $current=apply_filters('wpvivid_enable_plugins_list',$current);

            foreach ($active_sitewide_plugins as $plugin=>$data)
            {
                $current[]=$plugin;
            }
            // Add plugins
            if(!empty($plugins))
            {
                foreach ( $plugins as $plugin=>$data )
                {
                    if ( ! in_array( $plugin, $current ) && ! is_wp_error( validate_plugin( $plugin ) ) ) {
                        $current[] = $plugin;
                    }
                }
            }
            activate_plugins($current,'',true,true);
        }
        else
        {
            $current = get_option( 'active_plugins', array() );
            $plugin_list=array();
            $plugin_list[]='wpvivid-backuprestore/wpvivid-backuprestore.php';
            $plugin_list[]='wpvivid-backup-pro/wpvivid-backup-pro.php';
            $plugin_list=apply_filters('wpvivid_enable_plugins_list',$plugin_list);

            $current=array_merge($plugin_list,$current);
            // Add plugins
            if(!empty($plugins))
            {
                foreach ( $plugins as $plugin )
                {
                    if ( ! in_array( $plugin, $current ) && ! is_wp_error( validate_plugin( $plugin ) ) ) {
                        $current[] = $plugin;
                    }
                }
            }
            activate_plugins($current,'',false,true);
        }

        if(file_exists(WP_CONTENT_DIR.'/wpvivid_mu_plugins'))
        {
            $this->rrmdir(WPMU_PLUGIN_DIR);
            rename(WP_CONTENT_DIR.'/wpvivid_mu_plugins',WPMU_PLUGIN_DIR);
        }

        $search_plugin='cswt-forest-retreats-v3/cswt-forest-retreats-v3.php';
        if (in_array($search_plugin, $current))
        {
            if ( file_exists( ABSPATH . 'wp-config.php' ) )
            {
                $config_file=ABSPATH . 'wp-config.php';

                $config_data=file_get_contents($config_file);

                $home_url = home_url();
                global $wpdb;
                $home_url_sql = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name = %s", 'home' ) );
                foreach ( $home_url_sql as $home ){
                    $home_url = $home->option_value;
                }
                $home_url = untrailingslashit($home_url);

                $root_dir_line = 'define (\'ROOTDIR\', dirname(__FILE__) . \'/\');';
                $root_url_line = 'define (\'ROOTURL\', \''.$home_url.'/\');';
                $file_log_line = 'define (\'EPLOGFILE\', ROOTDIR . \'logs/endpointlog.txt\');';

                $pos=strpos($config_data,'/* That\'s all, stop editing! Happy publishing. */');
                if($pos!==false)
                {
                    $string_start=substr($config_data, 0, $pos);
                    $string_end=substr($config_data, $pos);

                    $new_data='';

                    $pattern     = "/define\s*\(\s*'ROOTDIR'\s*,\s*(.*)\s*\);.*/";
                    if (!preg_match($pattern, $config_data, $matches))
                    {
                        $new_data.=$root_dir_line.PHP_EOL;
                    }

                    $pattern     = "/define\s*\(\s*'ROOTURL'\s*,\s*(.*)\s*\);.*/";
                    if (!preg_match($pattern, $config_data, $matches))
                    {
                        $new_data.=$root_url_line.PHP_EOL;
                    }

                    $pattern     = "/define\s*\(\s*'EPLOGFILE'\s*,\s*(.*)\s*\);.*/";
                    if (!preg_match($pattern, $config_data, $matches))
                    {
                        $new_data.=$file_log_line.PHP_EOL;
                    }

                    if($new_data!=='')
                    {
                        $new_string=$string_start.PHP_EOL.$new_data.$string_end;
                        file_put_contents($config_file, $new_string);
                    }
                }
            }
        }

        $local_google_fonts='local-google-fonts/local-google-fonts.php';
        if (in_array($local_google_fonts, $current) )
        {
            $upload_dir = wp_get_upload_dir();
            $folder     = $upload_dir['error'] ? WP_CONTENT_DIR . '/uploads/fonts' : $upload_dir['basedir'] . '/fonts';
            if ( is_dir( $folder ) )
            {
                $wpvivid_wp_filesystem = $this->wpvivid_wp_filesystem();
                $wpvivid_wp_filesystem->delete( $folder, true );
            }

            delete_option( 'local_google_fonts_buffer' );
        }
    }

    public function active_mu_single_plugin($restore_task)
    {
        if(isset($restore_task['sub_tasks']))
        {
            foreach ($restore_task['sub_tasks'] as $key=>$sub_task)
            {
                if($sub_task['type'] === 'databases')
                {
                    if(isset($sub_task['options']['network_plugins']) && !empty($sub_task['options']['network_plugins']) && isset($sub_task['options']['mu_migrate']))
                    {
                        $active_sitewide_plugins = $sub_task['options']['network_plugins'];
                        $current=array();

                        foreach ($active_sitewide_plugins as $plugin=>$data)
                        {
                            //wpvivid free and pro already active in active_plugins() function
                            if($plugin !== 'wpvivid-backuprestore/wpvivid-backuprestore.php' && $plugin !== 'wpvivid-backup-pro/wpvivid-backup-pro.php')
                            {
                                $current[]=$plugin;
                            }
                        }

                        if(!empty($current))
                        {
                            activate_plugins($current,'',false,true);
                        }
                    }
                }
            }
        }
    }

    public function wpvivid_wp_filesystem()
    {
        global $wp_filesystem;

        if ( ! function_exists( '\WP_Filesystem' ) ) {
            include ABSPATH . 'wp-admin/includes/file.php';
        }

        \WP_Filesystem();

        return $wp_filesystem;
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir))
        {
            $objects = scandir($dir);
            foreach ($objects as $object)
            {
                if ($object != "." && $object != "..")
                {
                    if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                        $this->rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                    else
                        unlink($dir. DIRECTORY_SEPARATOR .$object);
                }
            }
            rmdir($dir);
        }
    }
}