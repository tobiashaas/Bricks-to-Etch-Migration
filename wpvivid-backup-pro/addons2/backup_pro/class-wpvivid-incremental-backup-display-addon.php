<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Admin_load: yes
 * Need_init: yes
 * Interface Name: WPvivid_Incremental_Backup_Display_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPvivid_Incremental_Backup_list extends WP_List_Table
{
    public $page_num;
    public $schedule_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'incremental_schedule',
                'screen' => 'incremental_schedule',
            )
        );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb']))
        {
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

    public function get_columns()
    {
        $columns = array();
        $columns['wpvivid_backup_type'] = __( 'Backup Content', 'wpvivid' );
        $columns['wpvivid_backup_cycles'] = __( 'Cycles', 'wpvivid'  );
        $columns['wpvivid_last_backup'] = __( 'Latest Backup', 'wpvivid'  );
        $columns['wpvivid_next_backup'] = __( 'Next Backup', 'wpvivid'  );
        return $columns;
    }

    public function set_schedule_list($schedule_list,$page_num=1)
    {
        $this->schedule_list=$schedule_list;
        $this->page_num=$page_num;
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

        $total_items =sizeof($this->schedule_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->schedule_list);
    }

    public function _column_wpvivid_backup_type($schedule)
    {
        if($schedule['backup_type'] === 'Database Backup'){
            $display_type = 'Database (Full Backup)';
            echo '<td class="row-title">
                        <span>'.$display_type.'</span>
                   </td>';
        }
        else{
            if($schedule['backup_type'] === 'Full Backup'){
                $display_type = 'Files (Full Backup)';
            }
            else{
                $display_type = 'Files (Incremental Backup)';
            }
            echo '<td class="row-title"><label for="tablecell">'.$display_type.'</label></td>';
        }
    }

    public function _column_wpvivid_backup_cycles($schedule)
    {
        echo '<td>'.$schedule['backup_cycles'].'</td>';
    }

    public function _column_wpvivid_last_backup($schedule)
    {
        echo '<td>'.$schedule['backup_last_time'].'</td>';
    }

    public function _column_wpvivid_next_backup($schedule)
    {
        echo '<td>'.$schedule['backup_next_time'].'</td>';
    }

    public function display_rows()
    {
        $this->_display_rows( $this->schedule_list );
    }

    private function _display_rows($schedule_list)
    {
        $page=$this->get_pagenum();

        $page_schedule_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_schedule_list = array_splice( $schedule_list, 0, 10);
            $count++;
        }
        foreach ( $page_schedule_list as $schedule)
        {
            $this->single_row($schedule);
        }
    }

    public function single_row($schedule)
    {
        if ($schedule['backup_type'] == 'Incremental Backup')
        {
            $class='alternate';
        } else {
            $class='';
        }
        ?>
        <tr class="<?php echo $class;?>">
            <?php $this->single_row_columns( $schedule ); ?>
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
                "%s<input class='current-page' id='current-page-selector-schedule' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-schedule" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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
        if ( $total_pages >1) {
            ?>
            <div class="tablenav <?php echo esc_attr($which); ?>" style="<?php esc_attr_e($css_type); ?>">
                <?php
                $this->extra_tablenav($which);
                $this->pagination($which);
                ?>

                <br class="clear"/>
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
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody
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

    protected function get_table_classes()
    {
        return array( 'widefat plugin-install' );
    }
}

class WPvivid_Incremental_Backup_Display_addon
{
    public function __construct()
    {
        add_filter('wpvivid_schedule_tabs',array($this, 'add_schedule_tabs'),10);

        //add_action('init',array( $this,'init_schedule_hooks'));

        add_filter('wpvivid_incremental_additional_database_display', array($this, 'wpvivid_incremental_additional_database_display'), 10);
        add_filter('wpvivid_export_setting_addon', array($this, 'export_setting_addon'), 11);

        add_action('wp_ajax_wpvivid_get_custom_database_tables_info_ex',array($this, 'get_custom_database_tables_info_ex'));
        add_action('wp_ajax_wpvivid_get_custom_themes_plugins_info_ex',array($this, 'get_custom_themes_plugins_info_ex'));
        add_action('wp_ajax_wpvivid_connect_additional_database_ex', array($this, 'connect_additional_database_ex'));
        add_action('wp_ajax_wpvivid_add_additional_database_ex', array($this, 'add_additional_database_ex'));
        add_action('wp_ajax_wpvivid_remove_additional_database_ex', array($this, 'remove_additional_database_ex'));
        add_action('wp_ajax_wpvivid_update_incremental_exclude_extension', array($this, 'update_incremental_exclude_extension'));
        add_action('wp_ajax_wpvivid_save_incremental_backup_schedule', array($this, 'save_incremental_backup_schedule'));
        add_action('wp_ajax_wpvivid_set_incremental_backup_schedule',array($this,'set_incremental_backup_schedule'));
        add_action('wp_ajax_wpvivid_enable_incremental_backup',array($this,'enable_incremental_backup'));
        add_action('wp_ajax_wpvivid_edit_incremental_schedule_addon', array($this, 'edit_incremental_schedule_addon'));
    }

    public function add_schedule_tabs($tabs)
    {
        $args['span_class']='dashicons dashicons-chart-bar';
        $args['span_style']='color:red;padding-right:0.5em;margin-top:0.1em;';
        $args['div_style']='display:block;';
        $args['is_parent_tab']=0;
        $tabs['incremental_backup_schedules']['title']='Incremental Backup';
        $tabs['incremental_backup_schedules']['slug']='incremental_backup_schedules';
        $tabs['incremental_backup_schedules']['callback']=array($this, 'output_incremental_backup');
        $tabs['incremental_backup_schedules']['args']=$args;
        return $tabs;
    }

    /***** incremental backup display filter begin *****/
    public function wpvivid_incremental_additional_database_display($html){
        $html = '';
        $history = WPvivid_custom_backup_selector::get_incremental_db_setting();
        if (empty($history))
        {
            $history = array();
        }
        if(isset($history['additional_database_option']))
        {
            if(isset($history['additional_database_option']['additional_database_list']))
                foreach ($history['additional_database_option']['additional_database_list'] as $database => $db_info)
                {
                    $html .= '<div class="wpvivid-text-line"><span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-additional-database-remove" database-name="'.$database.'"></span><span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue wpvivid-icon-16px-nopointer"></span><span class="wpvivid-text-line" option="additional_db_custom" name="'.$database.'">'.$database.'@'.$db_info['db_host'].'</span></div>';
                }
        }
        return $html;
    }
    /***** incremental backup display filter end *****/

    /***** incremental backup filters begin *****/

    /***** incremental backup filters end *****/

    /***** useful function begin *****/

    public function check_schedule_option($data)
    {
        $ret['result']=WPVIVID_PRO_FAILED;

        $ret['schedule']['incremental_recurrence'] =$data['recurrence'];
        $ret['schedule']['incremental_recurrence_week'] =$data['recurrence_week'];
        $ret['schedule']['incremental_recurrence_day'] =$data['recurrence_day'];
        $ret['schedule']['incremental_files_recurrence'] =$data['incremental_files_recurrence'];
        $ret['schedule']['incremental_db_recurrence'] =$data['incremental_db_recurrence'];
        $ret['schedule']['incremental_db_recurrence_week'] = $data['incremental_db_recurrence_week'];
        $ret['schedule']['incremental_db_recurrence_day'] = $data['incremental_db_recurrence_day'];
        $ret['schedule']['incremental_files_start_backup'] = $data['incremental_files_start_backup'];

        if(isset($data['backup_db']))
        {
            $ret['schedule']['backup_db']=$data['backup_db'];
            $ret['schedule']['backup_db']['exclude_files']=$data['exclude_files'];
            $ret['schedule']['backup_db']['exclude_file_type']=$data['exclude_file_type'];
            //$ret['schedule']['backup_files'] = apply_filters('wpvivid_custom_backup_data_transfer', $ret['schedule']['backup_files'], $data['custom']['files'], 'incremental_backup_file');
        }
        if(isset($data['backup_files']))
        {
            $ret['schedule']['backup_files']=$data['backup_files'];
            $ret['schedule']['backup_files']['exclude_files']=$data['exclude_files'];
            $ret['schedule']['backup_files']['exclude_file_type']=$data['exclude_file_type'];
            //$ret['schedule']['backup_db'] = apply_filters('wpvivid_custom_backup_data_transfer', $ret['schedule']['backup_db'], $data['custom']['db'], 'incremental_backup_db');
        }
        $data['save_local_remote']=sanitize_text_field($data['save_local_remote']);

        if(!empty($data['save_local_remote']))
        {
            if($data['save_local_remote'] == 'remote')
            {
                $remote_storage=WPvivid_Setting::get_remote_options();
                if($remote_storage == false)
                {
                    $ret['error']=__('There is no default remote storage configured. Please set it up first.', 'wpvivid');
                    return $ret;
                }
                else
                {
                    $ret['schedule']['backup']['remote']=1;
                    $ret['schedule']['backup']['local']=0;
                }
            }
            else
            {
                $ret['schedule']['backup']['remote']=0;
                $ret['schedule']['backup']['local']=1;
            }
        }

        if(isset($data['remote_id_select']) && $data['remote_id_select'] !== 'all')
        {
            /*$remoteslist=WPvivid_Setting::get_all_remote_options();
            $remote_options = array();
            $remote_options[$data['remote_id_select']] = $remoteslist[$data['remote_id_select']];
            $ret['schedule']['backup']['remote_options'] = $remote_options;*/
            $ret['schedule']['backup']['remote_id'] = $data['remote_id_select'];
        }

        if(isset($data['backup_prefix']) && !empty($data['backup_prefix']))
        {
            $ret['schedule']['backup']['backup_prefix'] = $data['backup_prefix'];
        }

        if(isset($data['db_current_day']))
        {
            $ret['schedule']['db_current_day'] = $data['db_current_day'];
        }

        if(isset($data['files_current_day']))
        {
            $ret['schedule']['files_current_day'] = $data['files_current_day'];
        }

        $ret['schedule']['files_current_day_hour'] = $data['files_current_day_hour'];
        $ret['schedule']['files_current_day_minute'] = $data['files_current_day_minute'];
        $ret['schedule']['db_current_day_hour'] = $data['db_current_day_hour'];
        $ret['schedule']['db_current_day_minute'] = $data['db_current_day_minute'];

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function add_incremental_schedule($schedule, $is_mainwp=false)
    {
        $schedule_data=array();
        $schedule_data['id']=uniqid('wpvivid_incremental_schedule');
        $schedule_data['files_schedule_id']=uniqid('wpvivid_incremental_files_schedule_event');
        $schedule_data['db_schedule_id']=uniqid('wpvivid_incremental_db_schedule_event');

        $schedule['backup']['ismerge']=1;
        $schedule['backup']['lock']=0;
        $schedule_data= $this->set_incremental_schedule_data($schedule_data,$schedule);
        if($schedule_data===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
            return $ret;
        }
        $enable_incremental_schedules=WPvivid_Setting::get_option('wpvivid_enable_incremental_schedules',false);
        if($enable_incremental_schedules)
        {
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
                if(($schedule_data['db_start_time'] - $schedule_data['files_start_time']) <= 1200)
                {
                    $ret['error']=__('To avoid conflict, please set the start time of Database (Full Backup) at least 21 minutes later than the start time of Files (Full Backup).', 'wpvivid');
                }
                else
                {
                    $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                }
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error'], $is_mainwp);
                $ret['data']=$schedule_data;
                $ret['option']=$schedule;
                return $ret;
            }
        }

        $schedules=array();
        $schedules[$schedule_data['id']]=$schedule_data;
        WPvivid_Setting::update_option('wpvivid_incremental_schedules',$schedules);

        WPvivid_Setting::update_option('wpvivid_incremental_backup_data',array());

        $ret['result']='success';
        $success_msg = 'You have successfully added a schedule.';
        $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', true, $success_msg, $is_mainwp);

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

        if($enable_incremental_schedules){
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
        else{
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
        }

        $incremental_schedules_list = apply_filters('wpvivid_get_incremental_last_backup_message', $incremental_schedules_list);

        if(!$is_mainwp)
        {
            $table=new WPvivid_Incremental_Backup_list();
            $table->set_schedule_list($incremental_schedules_list);
            $table->prepare_items();
            ob_start();
            $table->display();
            $ret['incremental_backup_list'] = ob_get_clean();
        }

        return $ret;
    }

    public function set_incremental_schedule_data($schedule_data,$schedule)
    {
        $schedule_data['incremental_recurrence']=$schedule['incremental_recurrence'];
        $schedule_data['incremental_recurrence_week']=$schedule['incremental_recurrence_week'];
        $schedule_data['incremental_recurrence_day']=$schedule['incremental_recurrence_day'] ;
        $schedule_data['incremental_files_recurrence']=$schedule['incremental_files_recurrence'];
        $schedule_data['incremental_db_recurrence']=$schedule['incremental_db_recurrence'];
        $schedule_data['incremental_db_recurrence_week']=$schedule['incremental_db_recurrence_week'];
        $schedule_data['incremental_db_recurrence_day']=$schedule['incremental_db_recurrence_day'];
        $schedule_data['db_current_day']=$schedule['db_current_day'];
        $schedule_data['files_current_day']=$schedule['files_current_day'];
        $schedule_data['incremental_files_start_backup']=$schedule['incremental_files_start_backup'];
        $schedule_data['files_current_day_hour'] = $schedule['files_current_day_hour'];
        $schedule_data['files_current_day_minute'] = $schedule['files_current_day_minute'];
        $schedule_data['db_current_day_hour'] = $schedule['db_current_day_hour'];
        $schedule_data['db_current_day_minute'] = $schedule['db_current_day_minute'];

        $schedule_data['backup_files'] = $schedule['backup_files'];
        $schedule_data['backup_db'] = $schedule['backup_db'];

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

        $schedule_data['files_start_time']=$timestamp;
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
        $schedule_data['db_start_time']=$timestamp;

        $schedule_data['backup']=$schedule['backup'];
        return $schedule_data;
    }

    public static function check_incremental_schedule($backup_files,$schedule_id)
    {
        $schedule_options=WPvivid_Schedule::get_schedule($schedule_id);

        $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());

        if(isset($incremental_backup_data[$schedule_id])&&isset($incremental_backup_data[$schedule_id][$backup_files]))
        {
            if(time()>= $incremental_backup_data[$schedule_id][$backup_files]['next_start'])
            {
                $old_time=$incremental_backup_data[$schedule_id][$backup_files]['next_start'];
                $incremental_backup_data[$schedule_id][$backup_files]=array();
                $incremental_backup_data[$schedule_id][$backup_files]['first_backup']=true;
                $incremental_backup_data[$schedule_id][$backup_files]['versions']['version']=0;
                $incremental_backup_data[$schedule_id][$backup_files]['versions']['skip_files_time']=0;
                $incremental_backup_data[$schedule_id][$backup_files]['current_start']=$old_time;
                $recurrence = $schedule_options['incremental_recurrence'];
                if($recurrence=='wpvivid_2hours')
                {
                    $start_time = $old_time + 3600 * 2;
                    while( strtotime('now') > $start_time )
                    {
                        $start_time = $start_time + 3600 * 2;
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
                else if($recurrence=='wpvivid_6hours')
                {
                    $start_time = $old_time + 3600 * 6;
                    while( strtotime('now') > $start_time )
                    {
                        $start_time = $start_time + 3600 * 6;
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
                else if($recurrence=='wpvivid_12hours')
                {
                    $start_time = $old_time + 3600 * 12;
                    while( strtotime('now') > $start_time )
                    {
                        $start_time = $start_time + 3600 * 12;
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
                else if($recurrence=='wpvivid_daily')
                {
                    if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                        $start_time = $schedule_options[$backup_files.'_current_day'].' +1 day';
                    }
                    else{
                        $start_time = $schedule_options[$backup_files.'_current_day'];
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                else if($recurrence=='wpvivid_3days')
                {
                    if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                        $start_time = $schedule_options[$backup_files.'_current_day'].' +3 day';
                    }
                    else{
                        $start_time = $schedule_options[$backup_files.'_current_day'];
                    }
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                if($recurrence=='wpvivid_weekly')
                {
                    $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' next week';
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                else if($recurrence=='wpvivid_fortnightly')
                {
                    $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' +2 week';
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
                }
                else if($recurrence=='wpvivid_monthly')
                {
                    $day=$schedule_options['incremental_recurrence_day'];
                    if($day<10)
                    {
                        $day='0'.$day;
                    }

                    $date_now = date("Y-m-",time());
                    $monthly_tmp = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                    if(strtotime('now')>strtotime($monthly_tmp)){
                        $date_now = date("Y-m-",strtotime('+1 month'));
                        $monthly_start_time = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                    }
                    else{
                        $monthly_start_time = $monthly_tmp;
                    }
                    $start_time = strtotime($monthly_start_time);
                    //$start_time=strtotime(date('m', strtotime('+1 month')).'/'.$day.'/'.date('Y').' '.$schedule_options[$backup_files.'_current_day']);
                    $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
                }
            }
        }
        else
        {
            $incremental_backup_data[$schedule_id][$backup_files]['first_backup']=true;
            $incremental_backup_data[$schedule_id][$backup_files]['versions']['version']=0;
            $incremental_backup_data[$schedule_id][$backup_files]['versions']['skip_files_time']=0;
            $recurrence = $schedule_options['incremental_recurrence'];
            $incremental_backup_data[$schedule_id][$backup_files]['current_start']=time();

            if($recurrence=='wpvivid_2hours')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +2 hour';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_6hours')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +6 hour';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_12hours')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +12 hour';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_daily')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +1 day';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_3days')
            {
                if(strtotime('now')>strtotime($schedule_options[$backup_files.'_current_day'])){
                    $start_time = $schedule_options[$backup_files.'_current_day'].' +3 day';
                }
                else{
                    $start_time = $schedule_options[$backup_files.'_current_day'];
                }
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            if($recurrence=='wpvivid_weekly')
            {
                $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' next week';
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);

            }
            else if($recurrence=='wpvivid_fortnightly')
            {
                $start_time = $schedule_options['incremental_recurrence_week'].' '.$schedule_options[$backup_files.'_current_day'].' +2 week';
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=strtotime($start_time);
            }
            else if($recurrence=='wpvivid_monthly')
            {
                $day=$schedule_options['incremental_recurrence_day'];
                if($day<10)
                {
                    $day='0'.$day;
                }

                $date_now = date("Y-m-",time());
                $monthly_tmp = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                if(strtotime('now')>strtotime($monthly_tmp)){
                    $date_now = date("Y-m-",strtotime('+1 month'));
                    $monthly_start_time = $date_now.$day.' '.$schedule_options[$backup_files.'_current_day'];
                }
                else{
                    $monthly_start_time = $monthly_tmp;
                }
                $start_time = strtotime($monthly_start_time);
                //$start_time=strtotime(date('m', strtotime('+1 month')).'/'.$day.'/'.date('Y').' '.$schedule_options[$backup_files.'_current_day']);
                $incremental_backup_data[$schedule_id][$backup_files]['next_start']=$start_time;
            }
        }
        $incremental_backup_data[$schedule_id][$backup_files]['versions']['backup_time']=time();
        WPvivid_Setting::update_option('wpvivid_incremental_backup_data',$incremental_backup_data);
    }

    public function get_remote_folder()
    {
        $schedules= WPvivid_Setting::get_option('wpvivid_incremental_schedules',array());
        $schedule_options=array_shift($schedules);

        $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());
        $schedule_id=$schedule_options['id'];
        $backup_files='files';
        if(empty($incremental_backup_data))
        {
            self::check_incremental_schedule('files',$schedule_id);
            $incremental_backup_data=get_option('wpvivid_incremental_backup_data',array());
            $next_time=$incremental_backup_data[$schedule_id][$backup_files]['next_start'];
            $current_time=$incremental_backup_data[$schedule_id][$backup_files]['current_start'];
        }
        else
        {
            $next_time=$incremental_backup_data[$schedule_id][$backup_files]['next_start'];
            $current_time=$incremental_backup_data[$schedule_id][$backup_files]['current_start'];
        }

        $remote_folder1=date('Y_m_d',$current_time);
        $remote_folder2=date('Y_m_d',$next_time);
        $remote_folder=$remote_folder1.'_to_'.$remote_folder2;
        return $remote_folder;
    }

    public function reset_imcremental_schedule_start_time($schedule)
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
    /***** useful function end *****/

    public function get_custom_database_tables_info_ex(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
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


            $wpvivid_incremental_schedules = get_option('wpvivid_incremental_schedules');
            $wpvivid_incremental_schedule_data = array();
            foreach ($wpvivid_incremental_schedules as $id => $data)
            {
                $wpvivid_incremental_schedule_data = $data;
            }

            /*if (!isset($custom_incremental_history) || empty($custom_incremental_history)) {
                $custom_incremental_history = array();
            }*/

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
                    if (isset($wpvivid_incremental_schedule_data['backup_db']['custom_dirs']['include-tables']) && !empty($wpvivid_incremental_schedule_data['backup_db']['custom_dirs']['include-tables'])) {
                        if (in_array($row["Name"], $wpvivid_incremental_schedule_data['backup_db']['custom_dirs']['include-tables'])) {
                            $checked = 'checked';
                        }
                    }
                    if($checked == ''){
                        $diff_prefix_table_all_check = false;
                    }
                    $has_diff_prefix_table = true;

                    $diff_perfix_table .= '<div class="wpvivid-text-line">
                                                <input type="checkbox" option="diff_prefix_db" name="incremental_backup_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                                <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                           </div>';
                }
                else{
                    $checked = 'checked';
                    if (isset($wpvivid_incremental_schedule_data['backup_db']['custom_dirs']['exclude-tables']) && !empty($wpvivid_incremental_schedule_data['backup_db']['custom_dirs']['exclude-tables'])) {
                        if (in_array($row["Name"], $wpvivid_incremental_schedule_data['backup_db']['custom_dirs']['exclude-tables'])) {
                            $checked = '';
                        }
                    }

                    if (in_array($row["Name"], $default_table)) {
                        if ($checked == '') {
                            $base_table_all_check = false;
                        }
                        $has_base_table = true;

                        $base_table .= '<div class="wpvivid-text-line">
                                            <input type="checkbox" option="base_db" name="incremental_backup_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                            <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                        </div>';
                    } else {
                        if ($checked == '') {
                            $other_table_all_check = false;
                        }
                        $has_other_table = true;

                        $other_table .= '<div class="wpvivid-text-line">
                                            <input type="checkbox" option="other_db" name="incremental_backup_database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                            <span class="wpvivid-text-line">'.esc_html($row["Name"]).'|Rows:'.$row["Rows"].'|Size:'.$tables_info[$row["Name"]]["Data_length"].'</span>
                                         </div>';
                    }
                }
            }

            $ret['html'] = '<div style="padding-left:4em;margin-top:1em;">
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
                $base_table_html .= '<div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;padding-left:4em;">
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

    public function get_custom_themes_plugins_info_ex(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            $custom_incremental_history = WPvivid_custom_backup_selector::get_incremental_file_settings();
            if (!isset($custom_incremental_history) || empty($custom_incremental_history)) {
                $custom_incremental_history = array();
            }

            $themes_path = get_theme_root();
            $current_active_theme = get_stylesheet();
            $has_themes = false;
            $themes_table = '';
            $themes_table_html = '';
            $themes_all_check = 'checked';
            $themes_info = array();

            $themes = wp_get_themes();

            if (!empty($themes)) {
                $has_themes = true;
            }
            foreach ($themes as $theme) {
                $file = $theme->get_stylesheet();
                $themes_info[$file] = WPvivid_Backup_Restore_Page_addon::get_theme_plugin_info($themes_path . DIRECTORY_SEPARATOR . $file);
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

            foreach ($themes_info as $file => $info) {
                $checked = 'checked';

                if (!empty($custom_incremental_history['themes_option']['exclude_themes_list'])) {
                    if (in_array($file, $custom_incremental_history['themes_option']['exclude_themes_list'])) {
                        $checked = '';
                    }
                }

                if (empty($checked)) {
                    $themes_all_check = '';
                }
                $themes_table .= '<div class="wpvivid-custom-database-table-column">
                                        <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;"
                                        title="' . esc_html($file) . '|Size:' . size_format($info["size"], 2) . '">
                                        <input type="checkbox" option="themes" name="Themes" value="' . esc_attr($file) . '" ' . esc_html($checked) . ' />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                        ' . esc_html($file) . '|Size:' . size_format($info["size"], 2) . '</label></div>';
            }
            $themes_table .= '<div style="clear:both;"></div>';
            $ret['result'] = 'success';
            $ret['themes_info'] = $themes_info;
            if ($has_themes) {
                $themes_table_html .= '<div class="wpvivid-custom-database-wp-table-header" style="border:1px solid #e5e5e5;">
                                        <label class="wpvivid-checkbox">
                                        <input type="checkbox" class="wpvivid-themes-plugins-table-check wpvivid-themes-table-check" ' . esc_attr($themes_all_check) . ' />
                                        <span class="wpvivid-checkbox-checkmark"></span>Themes
                                        </label>
                                     </div>
                                     <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        ' . $themes_table . '
                                     </div>';
            }
            $ret['html'] = $themes_table_html;

            $ret['html'] .= '<div style="clear:both;"></div>';
            $ret['html'] .= '<div style="margin-bottom: 10px;"></div>';

            $has_plugins = false;
            $plugins_table = '';
            $plugins_table_html = '';
            $path = WP_PLUGIN_DIR;
            $active_plugins = get_option('active_plugins');
            $plugins_all_check = 'checked';
            $plugin_info = array();

            if (!function_exists('get_plugins'))
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $plugins = get_plugins();

            if (!empty($plugins)) {
                $has_plugins = true;
            }
            foreach ($plugins as $key => $plugin) {
                $slug = dirname($key);
                if ($slug == '.' || $slug == 'wpvivid-backuprestore' || $slug == 'wpvivid-backup-pro' || $slug == 'wpvividdashboard')
                    continue;
                $plugin_info[$slug] = WPvivid_Backup_Restore_Page_addon::get_theme_plugin_info($path . DIRECTORY_SEPARATOR . $slug);
                $plugin_info[$slug]['Name'] = $plugin['Name'];
                $plugin_info[$slug]['slug'] = $slug;
                $plugin_info[$slug]['active'] = 1;
            }

            uasort($plugin_info, function ($a, $b) {
                if ($a['active'] < $b['active']) {
                    return 1;
                }
                if ($a['active'] > $b['active']) {
                    return -1;
                } else {
                    return 0;
                }
            });

            foreach ($plugin_info as $slug => $info) {
                $checked = 'checked';

                if (!empty($custom_incremental_history['plugins_option']['exclude_plugins_list'])) {
                    if (in_array($slug, $custom_incremental_history['plugins_option']['exclude_plugins_list'])) {
                        $checked = '';
                    }
                }

                if (empty($checked)) {
                    $plugins_all_check = '';
                }

                $plugins_table .= '<div class="wpvivid-custom-database-table-column">
                                        <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;" 
                                        title="' . esc_html($info['Name']) . '|Size:' . size_format($info["size"], 2) . '">
                                        <input type="checkbox" option="plugins" name="Plugins" value="' . esc_attr($info['slug']) . '" ' . esc_html($checked) . ' />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                        ' . esc_html($info['Name']) . '|Size:' . size_format($info["size"], 2) . '</label>
                                    </div>';
            }

            $plugins_table .= '<div style="clear:both;"></div>';
            $ret['plugin_info'] = $plugin_info;
            if ($has_plugins) {
                $plugins_table_html .= '<div class="wpvivid-custom-database-other-table-header" style="border:1px solid #e5e5e5;">
                                        <label class="wpvivid-checkbox">
                                        <input type="checkbox" class="wpvivid-themes-plugins-table-check wpvivid-plugins-table-check" ' . esc_attr($plugins_all_check) . ' />
                                        <span class="wpvivid-checkbox-checkmark"></span>Plugins
                                        </label>
                                     </div>
                                     <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        ' . $plugins_table . '
                                     </div>';
            }
            $ret['html'] .= $plugins_table_html;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function connect_additional_database_ex(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            if (isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])) {
                $data = $_POST['database_info'];
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $db_user = sanitize_text_field($json['db_user']);
                $db_pass = sanitize_text_field($json['db_pass']);
                $db_host = sanitize_text_field($json['db_host']);

                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']='Unknown Error';

                $this->database_connect = new WPvivid_Additional_DB_Method($db_user, $db_pass, $db_host);
                $ret = $this->database_connect->wpvivid_do_connect();

                if($ret['result']===WPVIVID_PRO_SUCCESS){
                    $databases = $this->database_connect->wpvivid_show_additional_databases();
                    $default_exclude_database = array('information_schema', 'performance_schema', 'mysql', 'sys', DB_NAME);
                    $database_array = array();
                    foreach ($databases as $database) {
                        if (!in_array($database, $default_exclude_database)) {
                            $database_array[] = $database;
                        }
                    }

                    $database_html = '';
                    foreach ($database_array as $database){
                        $database_html .= '<div class="wpvivid-text-line"><span class="dashicons dashicons-plus-alt wpvivid-icon-16px wpvivid-add-additional-db" option="additional_db" name="'.$database.'"></span><span class="wpvivid-text-line">'.esc_html($database).'</span></div>';
                    }
                    $ret['html'] = $database_html;
                    $ret['result']=WPVIVID_PRO_SUCCESS;
                }
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        catch (Error $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function add_additional_database_ex(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            if (isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])) {
                $data = $_POST['database_info'];
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $db_user = sanitize_text_field($json['db_user']);
                $db_pass = sanitize_text_field($json['db_pass']);
                $db_host = sanitize_text_field($json['db_host']);
                $db_list = $json['additional_database_list'];

                $history = WPvivid_custom_backup_selector::get_incremental_setting();
                if (empty($history)) {
                    $history = array();
                }
                foreach ($db_list as $database){
                    $history['incremental_db']['additional_database_option']['additional_database_list'][$database]['db_user'] = $db_user;
                    $history['incremental_db']['additional_database_option']['additional_database_list'][$database]['db_pass'] = $db_pass;
                    $history['incremental_db']['additional_database_option']['additional_database_list'][$database]['db_host'] = $db_host;
                }
                WPvivid_Setting::update_option('wpvivid_incremental_backup_history', $history);

                if(!is_null($this->database_connect)){
                    $this->database_connect->close();
                }
                $ret['result']=WPVIVID_PRO_SUCCESS;
                $html = '';
                $html = apply_filters('wpvivid_incremental_additional_database_display', $html);
                $ret['html'] = $html;
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function remove_additional_database_ex(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            if (isset($_POST['database']) && !empty($_POST['database']) && is_string($_POST['database'])) {
                $database = sanitize_text_field($_POST['database']);
                if(!is_null($this->database_connect)){
                    $this->database_connect->close();
                }

                $history = WPvivid_custom_backup_selector::get_incremental_setting();
                if (empty($history)) {
                    $history = array();
                }
                if(isset($history['incremental_db']['additional_database_option'])) {
                    if(isset($history['incremental_db']['additional_database_option']['additional_database_list'][$database])){
                        unset($history['incremental_db']['additional_database_option']['additional_database_list'][$database]);
                    }
                }
                WPvivid_Setting::update_option('wpvivid_incremental_backup_history', $history);

                $ret['result']=WPVIVID_PRO_SUCCESS;
                $html = '';
                $html = apply_filters('wpvivid_incremental_additional_database_display', $html);
                $ret['html'] = $html;
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function update_incremental_exclude_extension(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try{
            if(isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']) &&
                isset($_POST['exclude_content']) && !empty($_POST['exclude_content']) && is_string($_POST['exclude_content'])){
                $type  = sanitize_text_field($_POST['type']);
                $value = sanitize_text_field($_POST['exclude_content']);

                $history = WPvivid_custom_backup_selector::get_incremental_setting();
                if (empty($history)) {
                    $history = array();
                }

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
                WPvivid_Setting::update_option('wpvivid_incremental_backup_history', $history);

                $ret['result'] = 'success';
                echo json_encode($ret);
            }
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function save_incremental_backup_schedule()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try{
            if(isset($_POST['schedule'])&&!empty($_POST['schedule']))
            {
                $json = $_POST['schedule'];
                $json = stripslashes($json);
                $schedule = json_decode($json, true);
                if (is_null($schedule))
                {
                    die();
                }
                $ret = $this->check_schedule_option($schedule);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    echo json_encode($ret);
                    die();
                }

                /*if(isset($_POST['incremental_remote_retain']) && !empty($_POST['incremental_remote_retain'])){
                    $incremental_remote_retain = intval($_POST['incremental_remote_retain']);
                    WPvivid_Setting::update_option('wpvivid_incremental_remote_backup_count_addon', $incremental_remote_retain);
                }*/

                $ret=$this->add_incremental_schedule($ret['schedule']);
                echo json_encode($ret);
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

    public function get_backup_data_from_schedule($schedule_backup_options)
    {
        $backup_options=array();

        if(isset($schedule_backup_options['remote_options'])&&$schedule_backup_options['remote_options'])
        {
            $backup_options['remote_options']=$schedule_backup_options['remote_options'];
        }

        if(isset($schedule_backup_options['remote'])&&$schedule_backup_options['remote'])
        {
            $backup_options['remote']=1;
        }

        if(isset($schedule_backup_options['local'])&&$schedule_backup_options['local'])
        {
            $backup_options['local']=1;
        }

        if(isset($schedule_backup_options['backup_files']))
        {
            $backup_options['backup_files']=$schedule_backup_options['backup_files'];
            if($schedule_backup_options['backup_files']=='custom')
            {
                $backup_options['custom_dirs']=$schedule_backup_options['custom_dirs'];
            }
        }
        else
        {
            if(isset($schedule_backup_options['backup_select']))//
            {
                $backup_options['backup_files']='custom';
                $custom_options=array();
                if(isset($schedule_backup_options['backup_select']['db'])&&$schedule_backup_options['backup_select']['db']==1)
                {
                    $custom_options['database_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['themes'])&&$schedule_backup_options['backup_select']['themes']==1)
                {
                    $custom_options['themes_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['plugin'])&&$schedule_backup_options['backup_select']['plugin']==1)
                {
                    $custom_options['plugins_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['uploads'])&&$schedule_backup_options['backup_select']['uploads']==1)
                {
                    $custom_options['uploads_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['content'])&&$schedule_backup_options['backup_select']['content']==1)
                {
                    $custom_options['content_check']=1;
                }
                if(isset($schedule_backup_options['backup_select']['core'])&&$schedule_backup_options['backup_select']['core']==1)
                {
                    $custom_options['core_check']=1;
                }

                if(isset($schedule_backup_options['backup_select']['other'])&&$schedule_backup_options['backup_select']['other']==1)
                {
                    $custom_options['other_check']=1;
                    if(isset($schedule_backup_options['custom_other_root']))
                    {
                        $custom_options['other_list']=$schedule_backup_options['custom_other_root'];
                    }
                }
                if(isset($schedule_backup_options['backup_select']['additional_db'])&&$schedule_backup_options['backup_select']['additional_db']==1)
                {
                    $custom_options['additional_database_check']=1;
                    if(isset($schedule_backup_options['additional_database_list']))
                    {
                        $backup_options['additional_database_list']=$schedule_backup_options['additional_database_list'];
                    }
                }

                $backup_options['custom_dirs']=$custom_options;
                /*
                if(isset($options['backup_select']['mu_site'])&&$options['backup_select']['mu_site']==1)
                {
                    $backup_options=apply_filters('wpvivid_set_custom_backup',$backup_options,'backup_mu_sites',$options);
                }
                */

            }
        }

        if(isset($schedule_backup_options['backup_prefix']))
        {
            $backup_options['backup_prefix']=$schedule_backup_options['backup_prefix'];
        }

        if(isset($schedule_backup_options['exclude_files']))
        {
            $backup_options['exclude_files']=$schedule_backup_options['exclude_files'];
        }
        else
        {
            $backup_options['exclude_files']=apply_filters('wpvivid_default_exclude_folders',array());
        }

        if(isset($schedule_backup_options['exclude_file_type']) && !empty($schedule_backup_options['exclude_file_type']))
        {
            $backup_options['exclude_file_type']=$schedule_backup_options['exclude_file_type'];
        }

        if(isset($schedule_backup_options['schedule_id']))
        {
            $backup_options['schedule_id']=$schedule_backup_options['schedule_id'];
        }

        if(isset($schedule_backup_options['incremental_backup_db']))
        {
            $backup_options['incremental_backup_db']=$schedule_backup_options['incremental_backup_db'];
        }

        if(isset($schedule_backup_options['incremental_backup_files']))
        {
            $backup_options['incremental_backup_files']=$schedule_backup_options['incremental_backup_files'];
        }

        if(isset($schedule_backup_options['incremental_options']))
        {
            $backup_options['incremental_options']=$schedule_backup_options['incremental_options'];
        }

        if(isset($schedule_backup_options['incremental']))
        {
            $backup_options['incremental']=$schedule_backup_options['incremental'];
        }

        return $backup_options;
    }

    public function edit_incremental_schedule_addon()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try{
            $ret['result'] = 'success';

            $incremental_schedules=WPvivid_Setting::get_option('wpvivid_incremental_schedules');
            $schedule_data=array_shift($incremental_schedules);

            $schedule_data['backup_files'] = $this->get_backup_data_from_schedule($schedule_data['backup_files']);
            $schedule_data['backup_db'] = $this->get_backup_data_from_schedule($schedule_data['backup_db']);

            $ret['recurrence'] = isset($schedule_data['incremental_recurrence']) ? $schedule_data['incremental_recurrence'] : 'wpvivid_weekly';
            $ret['incremental_files_recurrence'] = isset($schedule_data['incremental_files_recurrence']) ? $schedule_data['incremental_files_recurrence'] : 'wpvivid_hourly';
            $ret['incremental_db_recurrence'] = isset($schedule_data['incremental_db_recurrence']) ? $schedule_data['incremental_db_recurrence'] : 'wpvivid_weekly';

            $ret['incremental_files_recurrence_week'] = isset($schedule_data['incremental_recurrence_week']) ? $schedule_data['incremental_recurrence_week'] : 'mon';
            $ret['incremental_files_recurrence_day'] = isset($schedule_data['incremental_recurrence_day']) ? $schedule_data['incremental_recurrence_day'] : '1';
            $ret['incremental_db_recurrence_week'] = isset($schedule_data['incremental_db_recurrence_week']) ? $schedule_data['incremental_db_recurrence_week'] : 'mon';
            $ret['incremental_db_recurrence_day'] = isset($schedule_data['incremental_db_recurrence_day']) ? $schedule_data['incremental_db_recurrence_day'] : '1';

            $ret['files_current_day_hour'] = isset($schedule_data['files_current_day_hour']) ? $schedule_data['files_current_day_hour'] : '01';
            $ret['files_current_day_minute'] = isset($schedule_data['files_current_day_minute']) ? $schedule_data['files_current_day_minute'] : '00';
            $ret['db_current_day_hour'] = isset($schedule_data['db_current_day_hour']) ? $schedule_data['db_current_day_hour'] : '00';
            $ret['db_current_day_minute'] = isset($schedule_data['db_current_day_minute']) ? $schedule_data['db_current_day_minute'] : '00';

            if($schedule_data['backup']['remote'])
            {
                $ret['backup_to']='remote';
            }
            else
            {
                $ret['backup_to']='local';
            }

            if(isset($schedule_data['backup']['remote_id']))
            {
                $remote_id=$schedule_data['backup']['remote_id'];
                $remoteslist=WPvivid_Setting::get_all_remote_options();
                if(isset($remoteslist[$remote_id]))
                {
                    $tmp_remote_option=array();
                    $tmp_remote_option[$remote_id]=$remoteslist[$remote_id];
                    $ret['remote_options']=$tmp_remote_option;
                }
            }
            else if(isset($schedule_data['backup']['remote_options']))
            {
                $ret['remote_options'] = $schedule_data['backup']['remote_options'];
            }

            if(isset($schedule_data['backup']['backup_prefix']))
            {
                $ret['backup_prefix'] = $schedule_data['backup']['backup_prefix'];
            }
            else
            {
                $general_setting=WPvivid_Setting::get_setting(true, "");
                if(!isset($general_setting['options']['wpvivid_common_setting']['backup_prefix']))
                {
                    $home_url_prefix=get_home_url();
                    $parse = parse_url($home_url_prefix);
                    $path = '';
                    if(isset($parse['path']))
                    {
                        $parse['path'] = str_replace('/', '_', $parse['path']);
                        $parse['path'] = str_replace('.', '_', $parse['path']);
                        $path = $parse['path'];
                    }
                    $parse['host'] = str_replace('/', '_', $parse['host']);
                    $ret['backup_prefix'] = $parse['host'].$path;
                }
                else
                {
                    $ret['backup_prefix'] = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                }
            }

            $ret['incremental_files_start_backup'] = $schedule_data['incremental_files_start_backup'];
            $ret['backup_file_type']=isset($schedule_data['backup_files']['backup_files']) ? $schedule_data['backup_files']['backup_files'] : 'files';
            $ret['backup_db_type']=isset($schedule_data['backup_db']['backup_files']) ? $schedule_data['backup_db']['backup_files'] : 'db';

            if($ret['backup_file_type'] === 'custom')
            {
                $custom_dir=$schedule_data['backup_files']['custom_dirs'];
                if(isset($custom_dir['core_check']))
                {
                    $ret['core_check']=$custom_dir['core_check'];
                }
                else
                {
                    $ret['core_check']=0;
                }

                if(isset($custom_dir['content_check']))
                {
                    $ret['content_check']=$custom_dir['content_check'];
                }
                else
                {
                    $ret['content_check']=0;
                }

                if(isset($custom_dir['themes_check']))
                {
                    $ret['themes_check']=$custom_dir['themes_check'];
                }
                else
                {
                    $ret['themes_check']=0;
                }

                if(isset($custom_dir['plugins_check']))
                {
                    $ret['plugins_check']=$custom_dir['plugins_check'];
                }
                else
                {
                    $ret['plugins_check']=0;
                }

                if(isset($custom_dir['uploads_check']))
                {
                    $ret['uploads_check']=$custom_dir['uploads_check'];
                }
                else
                {
                    $ret['uploads_check']=0;
                }

                if(isset($custom_dir['other_check']))
                {
                    $ret['other_check']=$custom_dir['other_check'];
                }
                else
                {
                    $ret['other_check']=0;
                }

                if(isset($custom_dir['other_list']))
                {
                    $ret['other_list']=$custom_dir['other_list'];
                }
                else
                {
                    $ret['other_list']=array();
                }

            }
            if($ret['backup_db_type'] === 'custom')
            {
                $ret['database_check']=$schedule_data['backup_db']['custom_dirs']['database_check'];
                $ret['additional_database_check']=$schedule_data['backup_db']['custom_dirs']['additional_database_check'];
            }

            if(isset($schedule_data['backup_files']['exclude_files']))
            {
                $ret['exclude_files']=$schedule_data['backup_files']['exclude_files'];
            }

            if(isset($schedule_data['backup_files']['exclude_file_type']))
            {
                $ret['exclude_file_type']=$schedule_data['backup_files']['exclude_file_type'];
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

    public function set_incremental_backup_schedule()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try
        {
            if(isset($_POST['schedule'])&&!empty($_POST['schedule']))
            {
                $json = $_POST['schedule'];
                $json = stripslashes($json);
                $schedule = json_decode($json, true);
                if (is_null($schedule))
                {
                    die();
                }
                $ret = $this->check_schedule_option($schedule);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    echo json_encode($ret);
                    die();
                }

                /*if(isset($_POST['incremental_remote_retain']) && !empty($_POST['incremental_remote_retain'])){
                    $incremental_remote_retain = intval($_POST['incremental_remote_retain']);
                    WPvivid_Setting::update_option('wpvivid_incremental_remote_backup_count_addon', $incremental_remote_retain);
                }*/

                $ret=$this->add_incremental_schedule($ret['schedule']);

                if(isset($_POST['start'])&&$_POST['start'])
                {
                    WPvivid_Setting::update_option('wpvivid_enable_incremental_schedules',true);

                    $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
                    foreach ($schedules as $schedule_id => $schedule)
                    {
                        $schedules[$schedule_id]['status'] = 'InActive';
                        if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                        {
                            wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        }
                    }
                    WPvivid_Setting::update_option('wpvivid_schedule_addon_setting', $schedules);

                    $schedules_list = WPvivid_Schedule_addon::wpvivid_get_schedule_list();
                    $table=new WPvivid_Schedule_List();
                    $table->set_schedule_list($schedules_list);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                }

                echo json_encode($ret);
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

    public function enable_incremental_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            if (isset($_POST['enable'])) {
                if ($_POST['enable']) {
                    WPvivid_Setting::update_option('wpvivid_enable_incremental_schedules', true);
                    //WPvivid_Setting::update_option('wpvivid_enable_schedules', false);

                    $schedules = WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
                    foreach ($schedules as $schedule_id => $schedule) {
                        $schedules[$schedule_id]['status'] = 'InActive';
                        if (wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']))) {
                            wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        }
                    }
                    WPvivid_Setting::update_option('wpvivid_schedule_addon_setting', $schedules);

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

                    $incremental_schedules=WPvivid_Setting::get_option('wpvivid_incremental_schedules');
                    $schedule_data=array_shift($incremental_schedules);

                    //
                    $schedule_data = $this->reset_imcremental_schedule_start_time($schedule_data);
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
                else{
                    WPvivid_Setting::update_option('wpvivid_enable_incremental_schedules', false);

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

                    $incremental_schedules=WPvivid_Setting::get_option('wpvivid_incremental_schedules');
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
                    //WPvivid_Setting::update_option('wpvivid_incremental_schedules', $schedules);
                    WPvivid_Setting::update_option('wpvivid_incremental_backup_data', array());
                }

                $incremental_schedules_list = apply_filters('wpvivid_get_incremental_last_backup_message', $incremental_schedules_list);

                $table=new WPvivid_Incremental_Backup_list();
                $table->set_schedule_list($incremental_schedules_list);
                $table->prepare_items();
                ob_start();
                $table->display();
                $ret['incremental_backup_list'] = ob_get_clean();
                $ret['result'] = 'success';
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function output_incremental_backup()
    {
        $offset=get_option('gmt_offset');
        $enable_incremental_schedules=WPvivid_Setting::get_option('wpvivid_enable_incremental_schedules', false);
        $incremental_schedules=WPvivid_Setting::get_option('wpvivid_incremental_schedules');
        if(empty($incremental_schedules)){
            $schedule_data=array();
            $schedule_data['id']=uniqid('wpvivid_incremental_schedule');
            $schedule_data['files_schedule_id']=uniqid('wpvivid_incremental_files_schedule_event');
            $schedule_data['db_schedule_id']=uniqid('wpvivid_incremental_db_schedule_event');
            $schedule_data['incremental_recurrence']='wpvivid_weekly';
            $schedule_data['incremental_recurrence_week']='mon';
            $schedule_data['incremental_recurrence_day']='1';
            $schedule_data['incremental_files_recurrence']='wpvivid_hourly';
            $schedule_data['incremental_db_recurrence']='wpvivid_weekly';
            $schedule_data['incremental_db_recurrence_week']='mon';
            $schedule_data['incremental_db_recurrence_day']='1';
            $schedule_data['db_current_day']='00:00';
            $schedule_data['files_current_day']='01:00';
            $schedule_data['incremental_files_start_backup']='0';
            $schedule_data['files_current_day_hour'] = '01';
            $schedule_data['files_current_day_minute'] = '00';
            $schedule_data['db_current_day_hour'] = '00';
            $schedule_data['db_current_day_minute'] = '00';
            $schedule_data['backup_files']['backup_files']='files';
            $schedule_data['backup_files']['exclude_files']=apply_filters('wpvivid_default_exclude_folders',array());
            $schedule_data['backup_files']['exclude_file_type']='';
            $schedule_data['backup_db']['backup_files']='db';
            $schedule_data['backup_db']['exclude_files']=apply_filters('wpvivid_default_exclude_folders',array());
            $schedule_data['backup_db']['exclude_file_type']='';
            /*$schedule_data['backup_files']['backup_select']['themes']=1;
            $schedule_data['backup_files']['backup_select']['plugin']=1;
            $schedule_data['backup_files']['backup_select']['uploads']=1;
            $schedule_data['backup_files']['backup_select']['content']=1;
            $schedule_data['backup_files']['backup_select']['core']=1;
            $schedule_data['backup_files']['backup_select']['other']=0;
            $schedule_data['backup_files']['backup_select']['additional_db']=0;
            $schedule_data['backup_files']['exclude_tables']=array();
            $schedule_data['backup_files']['exclude_themes']=array();
            $schedule_data['backup_files']['exclude_plugins']=array();
            $schedule_data['backup_files']['exclude_uploads']=array();
            $schedule_data['backup_files']['exclude_uploads_files']=array();
            $schedule_data['backup_files']['exclude_content']=array();
            $schedule_data['backup_files']['exclude_content_files']=array();
            $schedule_data['backup_files']['custom_other_root']=array();
            $schedule_data['backup_files']['exclude_custom_other_files']=array();
            $schedule_data['backup_files']['exclude_custom_other']=array();
            $schedule_data['backup_db']['backup_select']['db']=1;
            $schedule_data['backup_db']['backup_select']['themes']=0;
            $schedule_data['backup_db']['backup_select']['plugin']=0;
            $schedule_data['backup_db']['backup_select']['uploads']=0;
            $schedule_data['backup_db']['backup_select']['content']=0;
            $schedule_data['backup_db']['backup_select']['core']=0;
            $schedule_data['backup_db']['backup_select']['other']=0;
            $schedule_data['backup_db']['backup_select']['additional_db']=0;
            $schedule_data['backup_db']['exclude_tables']=array();
            $schedule_data['backup_db']['exclude_themes']=array();
            $schedule_data['backup_db']['exclude_plugins']=array();
            $schedule_data['backup_db']['exclude_uploads']=array();
            $schedule_data['backup_db']['exclude_uploads_files']=array();
            $schedule_data['backup_db']['exclude_content']=array();
            $schedule_data['backup_db']['exclude_content_files']=array();
            $schedule_data['backup_db']['custom_other_root']=array();
            $schedule_data['backup_db']['exclude_custom_other_files']=array();
            $schedule_data['backup_db']['exclude_custom_other']=array();*/
            //set file start time
            $time['type']='wpvivid_weekly';
            $time['start_time']['week']='mon';
            $time['start_time']['day']='01';
            $time['start_time']['current_day']="01:00";
            $timestamp=WPvivid_Schedule_addon::get_start_time($time);
            $schedule_data['files_start_time']=$timestamp;
            //set db start time
            $time['type']='wpvivid_weekly';
            $time['start_time']['week']='mon';
            $time['start_time']['day']='01';
            $time['start_time']['current_day']="00:00";
            $timestamp=WPvivid_Schedule_addon::get_start_time($time);
            $schedule_data['db_start_time']=$timestamp;
            $schedule_data['backup']['remote']=0;
            $schedule_data['backup']['local']=1;
            $schedule_data['backup']['ismerge']=1;
            $schedule_data['backup']['lock']=0;
            $schedules=array();
            $schedules[$schedule_data['id']]=$schedule_data;
            WPvivid_Setting::update_option('wpvivid_incremental_schedules',$schedules);

            $general_setting=WPvivid_Setting::get_setting(true, "");
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
                $backup_prefix = $parse['host'].$path;
            }
            else{
                $backup_prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
            }
        }
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

            //
            $recurrence=$schedule['incremental_recurrence'];
            $incremental_files_recurrence=$schedule['incremental_files_recurrence'];
            $incremental_db_recurrence=$schedule['incremental_db_recurrence'];

            $incremental_files_recurrence_week=isset($schedule['incremental_recurrence_week']) ? $schedule['incremental_recurrence_week'] : 'mon';
            $incremental_files_recurrence_day=isset($schedule['incremental_recurrence_day']) ? $schedule['incremental_recurrence_day'] : '1';

            $incremental_db_recurrence_week=isset($schedule['incremental_db_recurrence_week']) ? $schedule['incremental_db_recurrence_week'] : 'mon';
            $incremental_db_recurrence_day=isset($schedule['incremental_db_recurrence_day']) ? $schedule['incremental_db_recurrence_day'] : '1';

            $db_current_day_hour=$schedule['db_current_day_hour'];
            $db_current_day_minute=$schedule['db_current_day_minute'];

            $files_current_day_hour=$schedule['files_current_day_hour'];
            $files_current_day_minute=$schedule['files_current_day_minute'];

            if($schedule['backup']['remote'])
            {
                $backup_to='remote';
            }
            else
            {
                $backup_to='local';
            }

            if(isset($schedule['backup']['backup_prefix'])){
                $backup_prefix = $schedule['backup']['backup_prefix'];
            }
            else{
                $general_setting=WPvivid_Setting::get_setting(true, "");
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
                    $backup_prefix = $parse['host'].$path;
                }
                else{
                    $backup_prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                }
            }

            $incremental_files_start_backup = $schedule['incremental_files_start_backup'];
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

            //
            $recurrence='wpvivid_weekly';
            $incremental_files_recurrence='wpvivid_hourly';
            $incremental_db_recurrence='wpvivid_weekly';
            $incremental_files_recurrence_week='mon';
            $incremental_files_recurrence_day='1';
            $incremental_db_recurrence_week='mon';
            $incremental_db_recurrence_day='1';
            $db_current_day_hour='00';
            $db_current_day_minute='00';
            $files_current_day_hour='01';
            $files_current_day_minute='00';
            $backup_to='local';
            $incremental_files_start_backup = '0';
        }

        $incremental_schedules_list = apply_filters('wpvivid_get_incremental_last_backup_message', $incremental_schedules_list);

        if($enable_incremental_schedules){
            $incremental_enable_status = 'checked';
            $auto_start_backup_display = 'display: none;';
        }
        else{
            $incremental_enable_status = '';
            $auto_start_backup_display = '';
        }
        if($incremental_files_start_backup){
            $incremental_files_start_backup_check = 'checked';
        }
        else{
            $incremental_files_start_backup_check = '';
        }
        ?>
        <div class="wpvivid-one-coloum" style="padding-top:0em;padding-left:0em;">
            <div class="wpvivid-two-col">
                <label class="wpvivid-switch">
                    <input type="checkbox" id="wpvivid_incremental_backup_switch" <?php esc_attr_e($incremental_enable_status); ?>>
                    <span class="wpvivid-slider wpvivid-round"></span>
                </label>
                <label>
                    <span>Enable Incremental Backup Schedule</span>
                </label>
            </div>
            <div class="wpvivid-two-col wpvivid-ignore" style="<?php esc_attr_e($auto_start_backup_display); ?>">
                <span style="float:right;">
                    <label>
                        <input type="checkbox" option="incremental_backup" name="incremental_files_start_backup" <?php esc_attr_e($incremental_files_start_backup_check); ?> />
                        <span>Perform a full backup immediately when enabling incremental backup</span>
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>With the option checked, the plugin will perform a full backup of website(files + db) immediately when you enable incremental backups.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </label>
                </span>
            </div>
        </div>

        <div id="wpvivid_incremental_schedule_backup_list" style="width:100%; white-space: nowrap;">
            <?php
            $table=new WPvivid_Incremental_Backup_list();
            $table->set_schedule_list($incremental_schedules_list);
            $table->prepare_items();
            $table->display();
            ?>
        </div>

        <div class="wpvivid-one-coloum wpvivid-clear-float" id="wpvivid_edit_incremental_backup" style="padding-bottom:1em;padding-left:0;">
            <input class="button-primary" id="wpvivid_change_incremental_schedule" type="button" value="Edit">
        </div>

        <div class="wpvivid-one-coloum wpvivid-workflow" id="wpvivid_incremental_backup_deploy" style="display: none;">
            <div class="wpvivid-one-coloum wpvivid-clear-float" style="padding-bottom:1em;padding-left:0;">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span><strong>Incremental Backup Strategy:</strong></span>
                <div style="padding-left:2em;border-sizing:border-box;">
                    <p><span><strong>Files: </strong></span><span>Weekly Full Backup + Hourly (or every 'xx' hours) Incremental Backup</span>
                    <p><span><strong>Database: </strong></span><span>Database cannot be incrementally backed up, you have to set a backup schedule for database separately.</span>
                </div>
            </div>

            <table class="widefat" style="margin-bottom:1em;">
                <thead>
                <tr>
                    <th class="row-title"></th>
                    <th>Backup Cycles</th>
                    <th>Start Time</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="row-title"><label for="tablecell">Files (Full Backup)</label></td>
                    <td>
                        <select id="wpvivid_incrementa_schedule_recurrence" option="incremental_backup" name="recurrence" onchange="change_incremental_backup_recurrence();">
                            <option value="wpvivid_6hours">Every 6 hours</option>
                            <option value="wpvivid_12hours">Every 12 hours</option>
                            <option value="wpvivid_daily">Daily</option>
                            <option value="wpvivid_3days">Every 3 days</option>
                            <option value="wpvivid_weekly" selected="selected">Weekly</option>
                            <option value="wpvivid_fortnightly">Fortnightly</option>
                            <option value="wpvivid_monthly">Every 30 days</option>
                        </select>
                    </td>
                    <td>
                        <span id="wpvivid_incrementa_schedule_backup_start_week">
                            <select option="incremental_backup" name="recurrence_week">
                                <option value="sun">Sunday</option>
                                <option value="mon" selected="selected">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                            </select>
                        </span>
                        <span id="wpvivid_incrementa_schedule_backup_start_day">
                            Day<select option="incremental_backup" name="recurrence_day">
                                <?php
                                $html='';
                                for($i=1;$i<31;$i++)
                                {
                                    $html.='<option value="'.$i.'">'.$i.'</option>';
                                }
                                echo $html;
                                ?>
                            </select>
                        </span>
                        <span>
                            Hour<select option="incremental_backup" name="files_current_day_hour" onchange="wpvivid_check_incremental_time('files');">
                                <?php
                                $html='';
                                for($hour=0; $hour<24; $hour++){
                                    $format_hour = sprintf("%02d", $hour);
                                    $html .= '<option value="'.$format_hour.'">'.$format_hour.'</option>';
                                }
                                echo $html;
                                ?>
                            </select>
                        </span>
                        <span>:</span>
                        <span>
                            Minutes<select option="incremental_backup" name="files_current_day_minute" onchange="wpvivid_check_incremental_time('files');">
                                <?php
                                $html='';
                                for($minute=0; $minute<60; $minute++){
                                    $format_minute = sprintf("%02d", $minute);
                                    $html .= '<option value="'.$format_minute.'">'.$format_minute.'</option>';
                                }
                                echo $html;
                                ?>
                            </select>
                        </span>
                    </td>
                </tr>

                <tr>
                    <td class="row-title"><label for="tablecell">Files (Incremental Backup)</label></td>
                    <td>
                        <select option="incremental_backup" name="incremental_files_recurrence">
                            <option value="wpvivid_hourly">Every hour</option>
                            <option value="wpvivid_2hours">Every 2 hours</option>
                            <option value="wpvivid_4hours">Every 4 hours</option>
                            <option value="wpvivid_8hours">Every 8 hours</option>
                            <option value="wpvivid_12hours">Every 12 hours</option>
                            <option value="wpvivid_daily" >Daily</option>
                        </select>
                    </td>
                    <td></td>
                </tr>

                <tr>
                    <td class="row-title"><label for="tablecell">Database Backup Cycle</label></td>
                    <td>
                        <select id="wpvivid_incrementa_schedule_db_recurrence" option="incremental_backup" name="incremental_db_recurrence" onchange="change_incremental_backup_db_recurrence();">
                            <option value="wpvivid_hourly">Every hour</option>
                            <option value="wpvivid_2hours">Every 2 hours</option>
                            <option value="wpvivid_4hours">Every 4 hours</option>
                            <option value="wpvivid_8hours">Every 8 hours</option>
                            <option value="wpvivid_12hours">Every 12 hours</option>
                            <option value="wpvivid_daily">Daily</option>
                            <option value="wpvivid_weekly" selected="selected">Weekly</option>
                            <option value="wpvivid_fortnightly">Fortnightly</option>
                            <option value="wpvivid_monthly">Every 30 days</option>
                        </select>
                    </td>
                    <td>
                        <span id="wpvivid_incrementa_schedule_backup_db_start_week">
                            <select option="incremental_backup" name="incremental_db_recurrence_week">
                                <option value="sun">Sunday</option>
                                <option value="mon" selected="selected">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                            </select>
                        </span>
                        <span id="wpvivid_incrementa_schedule_backup_db_start_day">
                            Day<select option="incremental_backup" name="incremental_db_recurrence_day">
                                <?php
                                $html='';
                                for($i=1;$i<31;$i++)
                                {
                                    $html.='<option value="'.$i.'">'.$i.'</option>';
                                }
                                echo $html;
                                ?>
                            </select>
                        </span>
                        <span>
                            Hour<select option="incremental_backup" name="db_current_day_hour" onchange="wpvivid_check_incremental_time('db');">
                                <?php
                                $html='';
                                for($hour=0; $hour<24; $hour++)
                                {
                                    $format_hour = sprintf("%02d", $hour);
                                    $html .= '<option value="'.$format_hour.'">'.$format_hour.'</option>';
                                }
                                echo $html;
                                ?>
                            </select>
                        </span>
                        <span>:</span>
                        <span>
                            Minutes<select option="incremental_backup" name="db_current_day_minute" onchange="wpvivid_check_incremental_time('db');">
                                <?php
                                $html='';
                                for($minute=0; $minute<60; $minute++)
                                {
                                    $format_minute = sprintf("%02d", $minute);
                                    $html .= '<option value="'.$format_minute.'">'.$format_minute.'</option>';
                                }
                                echo $html;
                                ?>
                            </select>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <div style="display: none;">
                <span id="wpvivid_incremental_files_utc_time">00:00</span>
                <span id="wpvivid_incremental_db_utc_time">00:00</span>
            </div>

            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-top:1em;">
                <div style="margin-bottom:1em;">
                    <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                    <div style="padding-left:2em;">
                        <label class="">
                            <input type="radio" option="incremental_backup" name="save_local_remote" value="local" checked="" />Save it to localhost
                        </label>
                        <span style="padding:0 1em;"></span>
                        <label class="">
                            <input type="radio" option="incremental_backup" name="save_local_remote" value="remote" />Send it to cloud storage
                        </label>
                        <span style="padding:0 0.2em;"></span>
                        <span id="wpvivid_incremental_backup_remote_selector_part" style="display: none;">
                            <select id="wpvivid_incremental_backup_remote_selector">
                                <?php
                                $remoteslist=WPvivid_Setting::get_all_remote_options();
                                foreach ($remoteslist as $key=>$remote_option)
                                {
                                    if($key=='remote_selected')
                                    {
                                        continue;
                                    }
                                    if(!isset($remote_option['id']))
                                    {
                                        $remote_option['id'] = $key;
                                    }
                                    ?>
                                    <option value="<?php esc_attr_e($remote_option['id']); ?>" selected="selected"><?php echo $remote_option['name']; ?></option>
                                    <?php
                                }
                                ?>
                                <option value="all">All activated remote storage</option>
                            </select>
                        </span>
                        <label style="display: none;">
                            <input type="checkbox" option="incremental_backup" name="lock" value="0" />
                        </label>
                    </div>
                </div>

                <!--<div>
                    <?php
                //$custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_incremental_backup_deploy','incremental_backup','1','0');
                //$custom_backup_manager->output_custom_backup_table();
                ?>
                </div>-->

                <div>
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Database Content</strong></span></p>
                    <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <fieldset>
                            <label style="padding-right:2em;">
                                <input type="radio" option="incremental_backup_db" name="backup_db" value="db" checked>
                                <span>Database</span>
                            </label>
                            <label style="padding-right:2em;">
                                <input type="radio" option="incremental_backup_db" name="backup_db" value="custom">
                                <span>Custom content</span>
                            </label>
                        </fieldset>
                    </div>
                </div>
                <div id="wpvivid_incremental_backup_db" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_incremental_backup_deploy','incremental_backup','1','0');
                        $custom_backup_manager->output_custom_backup_db_table();
                        ?>
                    </div>
                </div>

                <div>
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup File Content</strong></span></p>
                    <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <fieldset>
                            <label style="padding-right:2em;">
                                <input type="radio" option="incremental_backup_file" name="backup_file" value="files" checked>
                                <span>Wordpress Files</span>
                            </label>
                            <label style="padding-right:2em;">
                                <input type="radio" option="incremental_backup_file" name="backup_file" value="custom">
                                <span>Custom content</span>
                            </label>
                        </fieldset>
                    </div>
                </div>
                <div id="wpvivid_incremental_backup_file" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager->output_custom_backup_file_table();
                        ?>
                    </div>
                </div>

                <!--Advanced Option (Exclude)-->
                <div id="wpvivid_incremental_backup_advanced_option">
                    <?php
                    $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_incremental_backup_advanced_option');
                    $custom_backup_manager->output_advanced_option_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="incremental_backup" name="backup_prefix" id="wpvivid_set_incremental_schedule_prefix" value="<?php esc_attr_e($backup_prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php esc_attr_e($backup_prefix); ?>">
                    </p>
                </div>
            </div>

            <div class="wpvivid-one-coloum wpvivid-clear-float" style="padding-bottom:0;padding-left:0;">
                <div id="wpvivid_incremental_backup_schedule_create_notice"></div>
                <input class="button-primary" type="submit" value="Save Changes" onclick="wpvivid_click_save_incremental_schedule();">
            </div>
        </div>
        <script>
            <?php
            if($enable_incremental_schedules){
            ?>
            var wpvivid_start_incremental = 0;
            <?php
            }
            else{
            ?>
            var wpvivid_start_incremental = 1;
            <?php
            }
            ?>

            jQuery('#wpvivid_incremental_backup_switch').click(function(){
                if(jQuery('#wpvivid_incremental_backup_switch').prop('checked')){
                    var enable = 1;
                    var descript = 'Enabling incremental backup schedule will disable full backup schedules, if any, are you sure to continue?';
                }
                else{
                    var enable = 0;
                    var descript = 'Disabling incremental backup will cause the scheduled incremental backup task to not run. Are you sure to continue?';
                }

                var ret = confirm(descript);
                if (ret !== true) {
                    if(enable === 1){
                        jQuery('#wpvivid_incremental_backup_switch').prop('checked', false);
                    }
                    else{
                        jQuery('#wpvivid_incremental_backup_switch').prop('checked', true);
                    }
                    return;
                }

                if(jQuery('input:checkbox[option=incremental_backup][name=incremental_files_start_backup]').prop('checked')){
                    var start_immediate = '1';
                }
                else{
                    var start_immediate = '0';
                }
                jQuery('input:checkbox[option=incremental_backup][name=incremental_files_start_backup]').css({'pointer-events': 'none', 'opacity': '0.4'});
                var ajax_data = {
                    'action': 'wpvivid_enable_incremental_backup',
                    'enable': enable,
                    'start_immediate': start_immediate
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-schedule', 'wpvivid-schedule').'&incremental_backup_schedules'; ?>';
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_edit_incremental_backup').click(function(){
                if(jQuery('#wpvivid_incremental_backup_deploy').is(':hidden'))
                {
                    jQuery('#wpvivid_incremental_backup_deploy').show();
                    jQuery( document ).trigger( 'wpvivid_refresh_incremental_custom_backup_tables' );
                    wpvivid_display_incremental_schedule_setting();
                }
            });

            function wpvivid_display_incremental_schedule_setting()
            {
                var ajax_data = {
                    'action': 'wpvivid_edit_incremental_schedule_addon'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            var recurrence=jsonarray.recurrence;
                            var incremental_files_recurrence=jsonarray.incremental_files_recurrence;
                            var incremental_db_recurrence=jsonarray.incremental_db_recurrence;
                            var incremental_files_recurrence_week=jsonarray.incremental_files_recurrence_week;
                            var incremental_files_recurrence_day=jsonarray.incremental_files_recurrence_day;
                            var incremental_db_recurrence_week=jsonarray.incremental_db_recurrence_week;
                            var incremental_db_recurrence_day=jsonarray.incremental_db_recurrence_day;
                            var db_current_day_hour=jsonarray.db_current_day_hour;
                            var db_current_day_minute=jsonarray.db_current_day_minute;
                            var files_current_day_hour=jsonarray.files_current_day_hour;
                            var files_current_day_minute=jsonarray.files_current_day_minute;
                            var backup_to=jsonarray.backup_to;

                            jQuery('#wpvivid_incrementa_schedule_backup_start_week').hide();
                            jQuery('#wpvivid_incrementa_schedule_backup_start_day').hide();
                            jQuery('#wpvivid_incrementa_schedule_backup_db_start_week').hide();
                            jQuery('#wpvivid_incrementa_schedule_backup_db_start_day').hide();

                            jQuery('[option=incremental_backup][name=recurrence]').val(recurrence);
                            jQuery('[option=incremental_backup][name=incremental_files_recurrence]').val(incremental_files_recurrence);
                            jQuery('[option=incremental_backup][name=incremental_db_recurrence]').val(incremental_db_recurrence);
                            if(recurrence === 'wpvivid_weekly' || recurrence === 'wpvivid_fortnightly')
                            {
                                jQuery('#wpvivid_incrementa_schedule_backup_start_week').show();
                                jQuery('#wpvivid_incrementa_schedule_backup_start_day').hide();
                            }
                            else if(recurrence === 'wpvivid_monthly')
                            {
                                jQuery('#wpvivid_incrementa_schedule_backup_start_week').hide();
                                jQuery('#wpvivid_incrementa_schedule_backup_start_day').show();
                            }
                            if(incremental_db_recurrence === 'wpvivid_weekly' || incremental_db_recurrence === 'wpvivid_fortnightly')
                            {
                                jQuery('#wpvivid_incrementa_schedule_backup_db_start_week').show();
                                jQuery('#wpvivid_incrementa_schedule_backup_db_start_day').hide();
                            }
                            else if(incremental_db_recurrence === 'wpvivid_monthly')
                            {
                                jQuery('#wpvivid_incrementa_schedule_backup_db_start_week').hide();
                                jQuery('#wpvivid_incrementa_schedule_backup_db_start_day').show();
                            }

                            jQuery('[option=incremental_backup][name=recurrence_week]').val(incremental_files_recurrence_week);
                            jQuery('[option=incremental_backup][name=recurrence_day]').val(incremental_files_recurrence_day);
                            jQuery('[option=incremental_backup][name=incremental_db_recurrence_week]').val(incremental_db_recurrence_week);
                            jQuery('[option=incremental_backup][name=incremental_db_recurrence_day]').val(incremental_db_recurrence_day);

                            jQuery('[option=incremental_backup][name=files_current_day_hour]').val(files_current_day_hour);
                            jQuery('[option=incremental_backup][name=files_current_day_minute]').val(files_current_day_minute);
                            jQuery('[option=incremental_backup][name=db_current_day_hour]').val(db_current_day_hour);
                            jQuery('[option=incremental_backup][name=db_current_day_minute]').val(db_current_day_minute);

                            var db_current_day=get_wpvivid_sync_time('incremental_backup','db_current_day_hour','db_current_day_minute');
                            var files_current_day=get_wpvivid_sync_time('incremental_backup','files_current_day_hour','files_current_day_minute');
                            jQuery('#wpvivid_incremental_files_utc_time').html(files_current_day);
                            jQuery('#wpvivid_incremental_db_utc_time').html(db_current_day);

                            jQuery('[option=incremental_backup][name=save_local_remote]').each(function()
                            {
                                if(jQuery(this).val()===backup_to)
                                {
                                    jQuery(this).prop('checked',true);
                                    if(backup_to === 'remote')
                                    {
                                        jQuery('#wpvivid_incremental_backup_remote_selector_part').show();
                                        if(typeof jsonarray.remote_options !== 'undefined'){
                                            jQuery.each(jsonarray.remote_options, function(remote_id, remote_option){
                                                jQuery('#wpvivid_incremental_backup_remote_selector').val(remote_id);
                                            });
                                        }
                                        else
                                        {
                                            jQuery('#wpvivid_incremental_backup_remote_selector').val('all');
                                        }
                                    }
                                }
                                else
                                {
                                    jQuery(this).prop('checked',false);
                                }
                            });

                            jQuery('[option=incremental_backup_db][name=backup_db]').each(function()
                            {
                                if(jQuery(this).val() === jsonarray.backup_db_type)
                                {
                                    jQuery(this).prop('checked',true);
                                    if(jsonarray.backup_db_type === 'custom')
                                    {
                                        jQuery('#wpvivid_incremental_backup_db').show();
                                        var database_check = true;
                                        var additional_database_check = true;
                                        if(jsonarray.database_check != 1)
                                        {
                                            database_check = false;
                                        }
                                        if(jsonarray.additional_database_check != 1)
                                        {
                                            additional_database_check = false;
                                        }
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-database-check').prop('checked', database_check);
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-additional-database-check').prop('checked', additional_database_check);
                                    }
                                }
                                else
                                {
                                    jQuery(this).prop('checked',false);
                                }
                            });

                            jQuery('[option=incremental_backup_file][name=backup_file]').each(function()
                            {
                                if(jQuery(this).val() === jsonarray.backup_file_type)
                                {
                                    jQuery(this).prop('checked',true);
                                    if(jsonarray.backup_file_type === 'custom')
                                    {
                                        jQuery('#wpvivid_incremental_backup_file').show();
                                        var core_check = true;
                                        var content_check = true;
                                        var themes_check = true;
                                        var plugin_check = true;
                                        var uploads_check = true;
                                        var other_check = true;
                                        if(jsonarray.core_check != 1)
                                        {
                                            core_check = false;
                                        }
                                        if(jsonarray.content_check != 1)
                                        {
                                            content_check = false;
                                        }
                                        if(jsonarray.themes_check != 1)
                                        {
                                            themes_check = false;
                                        }
                                        if(jsonarray.plugins_check != 1)
                                        {
                                            plugin_check = false;
                                        }
                                        if(jsonarray.uploads_check != 1)
                                        {
                                            uploads_check = false;
                                        }
                                        if(jsonarray.other_check != 1)
                                        {
                                            other_check = false;
                                        }
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-core-check').prop('checked', core_check);
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-content-check').prop('checked', content_check);
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-themes-check').prop('checked', themes_check);
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-plugins-check').prop('checked', plugin_check);
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-uploads-check').prop('checked', uploads_check);
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-additional-folder-check').prop('checked', other_check);

                                        var include_other = '';
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-include-additional-folder-list').html('');
                                        jQuery.each(jsonarray.other_list, function(index ,value){
                                            var type = 'folder';
                                            var class_span = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                                            include_other += "<div class='wpvivid-text-line' type='"+type+"'>" +
                                                "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                                                "<span class='"+class_span+"'></span>" +
                                                "<span class='wpvivid-text-line'>" + value + "</span>" +
                                                "</div>";
                                        });
                                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-include-additional-folder-list').append(include_other);
                                    }
                                }
                                else
                                {
                                    jQuery(this).prop('checked',false);
                                }
                            });

                            if(typeof jsonarray.exclude_files !== 'undefined')
                            {
                                var exclude_list = '';
                                jQuery('#wpvivid_incremental_backup_advanced_option').find('.wpvivid-custom-exclude-list').html('');
                                jQuery.each(jsonarray.exclude_files, function(index, value)
                                {
                                    if(value.type === 'folder')
                                    {
                                        var class_span = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                                    }
                                    else
                                    {
                                        var class_span = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                                    }
                                    exclude_list += "<div class='wpvivid-text-line' type='"+value.type+"'>" +
                                        "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                                        "<span class='"+class_span+"'></span>" +
                                        "<span class='wpvivid-text-line'>" + value.path + "</span>" +
                                        "</div>";
                                });
                                jQuery('#wpvivid_incremental_backup_advanced_option').find('.wpvivid-custom-exclude-list').append(exclude_list);
                            }

                            if(typeof jsonarray.exclude_file_type !== 'undefined')
                            {
                                jQuery('#wpvivid_incremental_backup_advanced_option').find('.wpvivid-custom-exclude-extension').val(jsonarray.exclude_file_type);
                            }

                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('editing incremental schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function change_incremental_backup_recurrence() {
                jQuery('#wpvivid_incrementa_schedule_backup_start_day').hide();
                jQuery('#wpvivid_incrementa_schedule_backup_start_week').hide();
                jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_hourly]').show();
                jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_2hours]').show();
                jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_4hours]').show();
                jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_8hours]').show();
                jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_12hours]').show();
                jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_daily]').show();
                jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').val('wpvivid_hourly');
                var select_value = jQuery('#wpvivid_incrementa_schedule_recurrence').val();
                if(select_value === 'wpvivid_weekly' || select_value === 'wpvivid_fortnightly')
                {
                    jQuery('#wpvivid_incrementa_schedule_backup_start_week').show();
                }
                else if(select_value === 'wpvivid_monthly')
                {
                    jQuery('#wpvivid_incrementa_schedule_backup_start_day').show();
                }
                else if(select_value === 'wpvivid_6hours')
                {
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_4hours]').hide();
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_8hours]').hide();
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_12hours]').hide();
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_daily]').hide();
                }
                else if(select_value === 'wpvivid_12hours')
                {
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_8hours]').hide();
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_12hours]').hide();
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_daily]').hide();
                }
                else if(select_value === 'wpvivid_daily')
                {
                    jQuery('select[option=incremental_backup][name=incremental_files_recurrence]').find('option[value=wpvivid_daily]').hide();
                }
            }

            function change_incremental_backup_db_recurrence() {
                jQuery('#wpvivid_incrementa_schedule_backup_db_start_week').hide();
                jQuery('#wpvivid_incrementa_schedule_backup_db_start_day').hide();
                var select_value = jQuery('#wpvivid_incrementa_schedule_db_recurrence').val();
                if(select_value === 'wpvivid_weekly' || select_value === 'wpvivid_fortnightly') {
                    jQuery('#wpvivid_incrementa_schedule_backup_db_start_week').show();
                }
                else if(select_value === 'wpvivid_monthly'){
                    jQuery('#wpvivid_incrementa_schedule_backup_db_start_day').show();
                }
            }

            function wpvivid_check_incremental_time(type){
                var time_offset = '<?php echo $offset; ?>';
                var db_current_day=get_wpvivid_sync_time('incremental_backup','db_current_day_hour','db_current_day_minute');
                var files_current_day=get_wpvivid_sync_time('incremental_backup','files_current_day_hour','files_current_day_minute');
                if(db_current_day === files_current_day){
                    alert('You have set the same start time for the files incremental backup schedule and the database backup schedule. When there is a conflict of starting times for schedule tasks, only one task will be executed properly. Please make sure that the times are different.')
                }
                var files_current_day=get_wpvivid_sync_time('incremental_backup','files_current_day_hour','files_current_day_minute');
                jQuery('#wpvivid_incremental_files_utc_time').html(files_current_day);

                var db_current_day=get_wpvivid_sync_time('incremental_backup','db_current_day_hour','db_current_day_minute');
                jQuery('#wpvivid_incremental_db_utc_time').html(db_current_day);
            }

            function wpvivid_check_backup_option_avail_ex(parent_id){
                var check_status = true;

                //check is backup db or files
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-part').prop('checked')){
                    var has_db_item = false;
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                        has_db_item = true;
                        var has_local_table_item = false;
                        jQuery('#'+parent_id).find('input:checkbox[name=Database]').each(function(index, value){
                            if(jQuery(this).prop('checked')){
                                has_local_table_item = true;
                            }
                        });
                        if(!has_local_table_item){
                            check_status = false;
                            alert('Please select at least one table to back up. Or, deselect the option \'Tables In The WordPress Database\' under the option \'Databases Will Be Backed up\'.');
                            return check_status;
                        }
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                        has_db_item = true;
                        var has_additional_db = false;
                        jQuery('#'+parent_id).find('.wpvivid-additional-database-list div').find('span:eq(2)').each(function(){
                            has_additional_db = true;
                        });
                        if(!has_additional_db){
                            check_status = false;
                            alert('Please select at least one additional database to back up. Or, deselect the option \'Include Additional Databases\' under the option \'Databases Will Be Backed up\'.');
                            return check_status;
                        }
                    }
                    if(!has_db_item){
                        check_status = false;
                        alert('Please select at least one option from \'Tables In The WordPress Database\' and \'Additional Databases\' under the option \'Databases Will Be Backed up\'. Or, deselect the option \'Databases Will Be Backed up\'.');
                        return check_status;
                    }
                }
                if(jQuery('#'+parent_id).find('.wpvivid-custom-file-part').prop('checked')){
                    var has_file_item = false;
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                        has_file_item = true;
                        var has_additional_folder = false;
                        jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function(){
                            has_additional_folder = true;
                        });
                        if(!has_additional_folder){
                            check_status = false;
                            alert('Please select at least one additional file or folder under the option \'Files/Folders Will Be Backed up\', Or, deselect the option \'Non-WordPress Files/Folders\'.');
                            return check_status;
                        }
                    }
                    if(!has_file_item){
                        check_status = false;
                        alert('Please select at least one option under the option \'Files/Folders Will Be Backed up\'. Or, deselect the option \'Files/Folders Will Be Backed up\'.');
                        return check_status;
                    }
                }

                return check_status;
            }

            function wpvivid_click_save_incremental_schedule()
            {
                var schedule_data = wpvivid_ajax_data_transfer('incremental_backup');
                schedule_data = JSON.parse(schedule_data);
                var exclude_dirs = wpvivid_get_exclude_json('wpvivid_incremental_backup_advanced_option');
                var custom_option = {
                    'exclude_files': exclude_dirs
                };
                jQuery.extend(schedule_data, custom_option);

                var exclude_file_type = wpvivid_get_exclude_file_type('wpvivid_incremental_backup_advanced_option');
                var exclude_file_type_option = {
                    'exclude_file_type': exclude_file_type
                };
                jQuery.extend(schedule_data, exclude_file_type_option);

                //schedule_data = JSON.stringify(schedule_data);
                //schedule_data = JSON.parse(schedule_data);
                var backup_db = {};
                jQuery('input:radio[option=incremental_backup_db][name=backup_db]').each(function ()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var value = jQuery(this).prop('value');
                        backup_db['backup_files']=value;
                        if(value === 'custom')
                        {
                            backup_db['custom_dirs'] = wpvivid_create_incremental_json_ex('wpvivid_incremental_backup_deploy', 'database');
                        }
                    }
                });
                schedule_data['backup_db']=backup_db;
                var backup_files = {};
                jQuery('input:radio[option=incremental_backup_file][name=backup_file]').each(function (){
                    if(jQuery(this).prop('checked'))
                    {
                        var value = jQuery(this).prop('value');
                        backup_files['backup_files']=value;
                        if(value === 'custom')
                        {
                            backup_files['custom_dirs'] = wpvivid_create_incremental_json_ex('wpvivid_incremental_backup_deploy', 'files');
                        }
                    }
                });
                schedule_data['backup_files']=backup_files;

                jQuery('input:radio[option=incremental_backup][name=save_local_remote]').each(function ()
                {
                    if (jQuery(this).prop('checked'))
                    {
                        if (this.value === 'remote')
                        {
                            var remote_id_select = jQuery('#wpvivid_incremental_backup_remote_selector').val();
                            var local_remote_option = {
                                'remote_id_select': remote_id_select
                            };
                            jQuery.extend(schedule_data, local_remote_option);
                        }
                    }
                });

                var db_current_day=get_wpvivid_sync_time('incremental_backup','db_current_day_hour','db_current_day_minute');
                var files_current_day=get_wpvivid_sync_time('incremental_backup','files_current_day_hour','files_current_day_minute');
                var current_day = {
                    'db_current_day': db_current_day,
                    'files_current_day': files_current_day,
                };

                jQuery.extend(schedule_data, current_day);
                schedule_data = JSON.stringify(schedule_data);
                console.log(schedule_data);
                var ajax_data = {
                    'action': 'wpvivid_save_incremental_backup_schedule',
                    'schedule': schedule_data
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        jQuery('#wpvivid_incremental_backup_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_incremental_schedule_backup_list').html(jsonarray.incremental_backup_list);
                            jQuery('#wpvivid_incremental_backup_deploy').hide();
                        }
                        else {
                            if(typeof jsonarray.error !== undefined){
                                jQuery('#wpvivid_incremental_backup_schedule_create_notice').html(jsonarray.error);
                            }
                            else{
                                jQuery('#wpvivid_incremental_backup_schedule_create_notice').html(jsonarray.notice);
                            }
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            /*function wpvivid_click_create_incremental_schedule(){
                if(wpvivid_start_incremental === 0){
                    var descript = 'Update will reset schedule, continue?';
                    var ret = confirm(descript);
                    if (ret !== true) {
                        return;
                    }
                }
                var file_json = wpvivid_create_incremental_json('wpvivid_incremental_backup_deploy', 'files');
                var db_json = wpvivid_create_incremental_json('wpvivid_incremental_backup_deploy', 'database');

                var schedule_data = wpvivid_ajax_data_transfer('incremental_backup');
                schedule_data = JSON.parse(schedule_data);
                var db_current_day=get_wpvivid_sync_time('incremental_backup','db_current_day_hour','db_current_day_minute');
                var files_current_day=get_wpvivid_sync_time('incremental_backup','files_current_day_hour','files_current_day_minute');
                var current_day = {
                    'db_current_day': db_current_day,
                    'files_current_day': files_current_day,
                };
                var custom = {};
                custom['custom'] = {
                    'files': file_json,
                    'db': db_json,
                };
                jQuery.extend(schedule_data, current_day);
                jQuery.extend(schedule_data, custom);
                schedule_data = JSON.stringify(schedule_data);

                var incremental_remote_backup_retain = jQuery('#wpvivid_incremental_remote_max_backup_count').val();

                var ajax_data = {
                    'action': 'wpvivid_set_incremental_backup_schedule',
                    'schedule': schedule_data,
                    'start':wpvivid_start_incremental,
                    'incremental_remote_retain': incremental_remote_backup_retain
                };
                jQuery('#wpvivid_incremental_backup_schedule_create_notice').html('');
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        jQuery('#wpvivid_incremental_backup_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(wpvivid_start_incremental)
                            {
                                jQuery('#wpvivid_schedule_list').html(jsonarray.html);
                                wpvivid_start_incremental=0;
                            }

                            jQuery('#wpvivid_output_incremental_schedule').show();
                            jQuery('#wpvivid_create_new_incremental_schedule').hide();
                            jQuery('#wpvivid_incremental_backup_schedule_notice').html(jsonarray.notice);
                            var all_schedule=jsonarray.data.all_schedule;
                            var db_schedule=jsonarray.data.db_schedule;
                            var db_next_start=jsonarray.data.db_next_start;
                            var files_schedule=jsonarray.data.files_schedule;
                            var files_next_start=jsonarray.data.files_next_start;
                            var next_start_of_all_files=jsonarray.data.next_start_of_all_files;
                            init_incremental_page(all_schedule,next_start_of_all_files,files_schedule,files_next_start,db_schedule,db_next_start);
                        }
                        else {
                            jQuery('#wpvivid_incremental_backup_schedule_create_notice').html(jsonarray.notice);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }*/

            /*jQuery('input:radio[option=incremental_backup][name=save_local_remote]').click(function(){
                var value = jQuery(this).val();
                if(value === 'local'){
                    jQuery('#wpvivid_incremental_remote_backup_count_setting').hide();
                }
                else if(value === 'remote'){
                    jQuery('#wpvivid_incremental_remote_backup_count_setting').show();
                }
            });*/

            /*function wpvivid_create_incremental_json_ex(parent_id, incremental_type){
                var json = {};
                if(incremental_type === 'files'){
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
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                        json['themes_check'] = '1';
                    }
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

                    //plugins
                    json['plugins_check'] = '0';
                    json['plugins_list'] = {};
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                        json['plugins_check'] = '1';
                    }
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

                    //content
                    json['content_check'] = '0';
                    json['content_list'] = {};
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                        json['content_check'] = '1';
                    }
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

                    //uploads
                    json['uploads_check'] = '0';
                    json['uploads_list'] = {};
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                        json['uploads_check'] = '1';
                    }
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

                    //additional folders/files
                    json['other_check'] = '0';
                    json['other_list'] = {};
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                        json['other_check'] = '1';
                    }
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
                else if(incremental_type === 'database'){
                    //database
                    json['database_check'] = '0';
                    json['database_list'] = Array();
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                        json['database_check'] = '1';
                        jQuery('#'+parent_id).find('input:checkbox[name=Database]').each(function(){
                            if(!jQuery(this).prop('checked')){
                                json['database_list'].push(jQuery(this).val());
                            }
                        });
                    }

                    //additional database
                    json['additional_database_check'] = '0';
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                        json['additional_database_check'] = '1';
                    }
                }
                return json;
            }*/

            function wpvivid_create_incremental_json(parent_id, incremental_type){
                var json = {};
                jQuery('#'+parent_id).find('.wpvivid-custom-check').each(function(){
                    if(incremental_type === 'files'){
                        if(jQuery(this).hasClass('wpvivid-custom-core-check')){
                            json['core_list'] = Array();
                            if(jQuery(this).prop('checked')){
                                json['core_check'] = '1';
                            }
                            else{
                                json['core_check'] = '0';
                            }
                        }
                        else if(jQuery(this).hasClass('wpvivid-custom-themes-plugins-check')){
                            json['themes_list'] = Array();
                            json['plugins_list'] = Array();
                            var has_themes = false;
                            var has_plugins = false;
                            if(jQuery(this).prop('checked')){
                                json['themes_check'] = '0';
                                json['plugins_check'] = '0';
                                jQuery('#'+parent_id).find('input:checkbox[option=themes][name=Themes]').each(function(){
                                    has_themes = true;
                                    if(jQuery(this).prop('checked')){
                                        json['themes_check'] = '1';
                                    }
                                    else{
                                        json['themes_list'].push(jQuery(this).val());
                                    }
                                });
                                if(!has_themes){
                                    json['themes_check'] = '1';
                                }
                                jQuery('#'+parent_id).find('input:checkbox[option=plugins][name=Plugins]').each(function(){
                                    has_plugins = true;
                                    if(jQuery(this).prop('checked')) {
                                        json['plugins_check'] = '1';
                                    }
                                    else{
                                        json['plugins_list'].push(jQuery(this).val());
                                    }
                                });
                                if(!has_plugins){
                                    json['plugins_check'] = '1';
                                }
                            }
                            else{
                                json['themes_check'] = '0';
                                json['plugins_check'] = '0';
                            }
                        }
                        else if(jQuery(this).hasClass('wpvivid-custom-uploads-check')){
                            json['uploads_list'] = {};
                            if(jQuery(this).prop('checked')){
                                json['uploads_check'] = '1';
                                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list ul').find('li div:eq(1)').each(function(){
                                    var folder_name = this.innerHTML;
                                    json['uploads_list'][folder_name] = {};
                                    json['uploads_list'][folder_name]['name'] = folder_name;
                                    json['uploads_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                                });
                                json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                            }
                            else{
                                json['uploads_check'] = '0';
                            }
                        }
                        else if(jQuery(this).hasClass('wpvivid-custom-content-check')){
                            json['content_list'] = {};
                            if(jQuery(this).prop('checked')){
                                json['content_check'] = '1';
                                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list ul').find('li div:eq(1)').each(function(){
                                    var folder_name = this.innerHTML;
                                    json['content_list'][folder_name] = {};
                                    json['content_list'][folder_name]['name'] = folder_name;
                                    json['content_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                                });
                                json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                            }
                            else{
                                json['content_check'] = '0';
                            }
                        }
                        else if(jQuery(this).hasClass('wpvivid-custom-additional-folder-check')){
                            json['other_list'] = {};
                            if(jQuery(this).prop('checked')){
                                json['other_check'] = '1';
                                jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list ul').find('li div:eq(1)').each(function(){
                                    var folder_name = this.innerHTML;
                                    json['other_list'][folder_name] = {};
                                    json['other_list'][folder_name]['name'] = folder_name;
                                    json['other_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                                });
                                json['other_extension'] = jQuery('#'+parent_id).find('.wpvivid-additional-folder-extension').val();
                            }
                            else{
                                json['other_check'] = '0';
                            }
                        }
                    }
                    else if(incremental_type === 'database'){
                        if(jQuery(this).hasClass('wpvivid-custom-database-check')){
                            json['database_list'] = Array();
                            if(jQuery(this).prop('checked')){
                                json['database_check'] = '1';
                                jQuery('#'+parent_id).find('input:checkbox[name=Database]').each(function(){
                                    if(!jQuery(this).prop('checked')){
                                        json['database_list'].push(jQuery(this).val());
                                    }
                                });
                            }
                            else{
                                json['database_check'] = '0';
                            }
                        }
                        else if(jQuery(this).hasClass('wpvivid-custom-additional-database-check')){
                            if(jQuery(this).prop('checked')){
                                json['additional_database_check'] = '1';
                            }
                            else{
                                json['additional_database_check'] = '0';
                            }
                        }
                    }
                });
                return json;
            }

            function get_wpvivid_sync_time(option_name,current_day_hour,current_day_minute) {
                var hour='00';
                var minute='00';
                jQuery('select[option='+option_name+'][name='+current_day_hour+']').each(function()
                {
                    hour=jQuery(this).val();
                });
                jQuery('select[option='+option_name+'][name='+current_day_minute+']').each(function(){
                    minute=jQuery(this).val();
                });
                return hour+":"+minute;
                /*hour=Number(hour)-Number(time_offset);

                var Hours=Math.floor(hour);
                var Minutes=Math.floor(60*(hour-Hours));

                Minutes=Number(minute)+Minutes;
                if(Minutes>=60)
                {
                    Hours=Hours+1;
                    Minutes=Minutes-60;
                }

                if(Hours>=24)
                {
                    Hours=Hours-24;
                }
                else if(Hours<0)
                {
                    Hours=24-Math.abs(Hours);
                }
                if(Hours<10)
                {
                    Hours='0'+Hours;
                }

                if(Minutes<10)
                {
                    Minutes='0'+Minutes;
                }

                return Hours+":"+Minutes;*/
            }

            function wpvivid_refresh_incremental_database_table(){
                wpvivid_incremental_backup_table.db_retry = 0;
                var custom_database_loading = '<div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>' +
                    '<div style="float: left;">Archieving database tables</div>' +
                    '<div style="clear: both;"></div>';
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-database-info').html('');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-database-info').html(custom_database_loading);
                //jQuery('.wpvivid-custom-database-info').html('');
                //jQuery('.wpvivid-custom-database-info').html(custom_database_loading);
                wpvivid_get_incremental_database_table();
            }

            function wpvivid_get_incremental_database_table(){
                var ajax_data = {
                    'action': 'wpvivid_get_custom_database_tables_info_ex'
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-database-info').html('');
                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-database-info').html(jsonarray.html);
                        //jQuery('.wpvivid-custom-database-info').html('');
                        //jQuery('.wpvivid-custom-database-info').html(jsonarray.html);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var need_retry_custom_database = false;
                    wpvivid_incremental_backup_table.db_retry++;
                    if(wpvivid_incremental_backup_table.db_retry < 10){
                        need_retry_custom_database = true;
                    }
                    if(need_retry_custom_database) {
                        setTimeout(function(){
                            wpvivid_get_incremental_database_table();
                        }, 3000);
                    }
                    else{
                        var refresh_btn = '<input type="submit" class="button-primary" value="Refresh" onclick="wpvivid_refresh_incremental_database_table();">';
                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-database-info').html('');
                        jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-custom-database-info').html(refresh_btn);
                        //jQuery('.wpvivid-custom-database-info').html('');
                        //jQuery('.wpvivid-custom-database-info').html(refresh_btn);
                    }
                });
            }

            function wpvivid_refresh_incremental_themes_plugins_table(){
                wpvivid_incremental_backup_table.theme_plugins_retry = 0;
                var custom_themes_plugins_loading = '<div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>' +
                    '<div style="float: left;">Archieving themes and plugins</div>' +
                    '<div style="clear: both;"></div>';
                jQuery('.wpvivid-custom-themes-plugins-info').html('');
                jQuery('.wpvivid-custom-themes-plugins-info').html(custom_themes_plugins_loading);
                wpvivid_get_incremental_themes_plugins_table();
            }

            function wpvivid_get_incremental_themes_plugins_table(){
                var ajax_data = {
                    'action': 'wpvivid_get_custom_themes_plugins_info_ex'
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        jQuery('.wpvivid-custom-themes-plugins-info').html('');
                        jQuery('.wpvivid-custom-themes-plugins-info').html(jsonarray.html);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var need_retry_custom_themes = false;
                    wpvivid_incremental_backup_table.theme_plugins_retry++;
                    if(wpvivid_incremental_backup_table.theme_plugins_retry < 10){
                        need_retry_custom_themes = true;
                    }
                    if(need_retry_custom_themes) {
                        setTimeout(function(){
                            wpvivid_get_incremental_themes_plugins_table();
                        }, 3000);
                    }
                    else{
                        var refresh_btn = '<input type="submit" class="button-primary" value="Refresh" onclick="wpvivid_refresh_incremental_themes_plugins_table();">';
                        jQuery('.wpvivid-custom-themes-plugins-info').html('');
                        jQuery('.wpvivid-custom-themes-plugins-info').html(refresh_btn);
                    }
                });
            }

            function wpvivid_get_incremental_backup_table(){
                wpvivid_get_incremental_database_table();
                //wpvivid_get_incremental_themes_plugins_table();
            }

            function wpvivid_refresh_incremental_backup_table() {
                wpvivid_get_incremental_backup_table();
                var exec_time = 30 * 60 * 1000;
                setTimeout(function(){
                    wpvivid_refresh_incremental_backup_table();
                }, exec_time);
            }

            /*jQuery('#wpvivid_incremental_backup_files_select').click(function(){
                if(jQuery('#wpvivid_incremental_backup_files').is(":hidden")) {
                    jQuery(this).find('details').prop('open', true);
                    jQuery('#wpvivid_incremental_backup_files').show();
                    jQuery( document ).trigger( 'wpvivid_refresh_incremental_custom_backup_tables' );
                }
                else{
                    jQuery(this).find('details').prop('open', false);
                    jQuery('#wpvivid_incremental_backup_files').hide();
                }
            });

            jQuery('#wpvivid_incremental_backup_db_select').click(function () {
                if(jQuery('#wpvivid_incremental_backup_db').is(":hidden")) {
                    jQuery(this).find('details').prop('open', true);
                    jQuery('#wpvivid_incremental_backup_db').show();
                    jQuery( document ).trigger( 'wpvivid_refresh_incremental_custom_backup_tables' );
                }
                else{
                    jQuery(this).find('details').prop('open', false);
                    jQuery('#wpvivid_incremental_backup_db').hide();
                }
            });*/

            jQuery('#wpvivid_set_incremental_schedule_prefix').on("keyup", function(){
                var manual_prefix = jQuery('#wpvivid_set_incremental_schedule_prefix').val();
                if(manual_prefix === ''){
                    manual_prefix = '*';
                    jQuery('#wpvivid_incremental_schedule_prefix').html(manual_prefix);
                }
                else{
                    var reg = RegExp(/wpvivid/, 'i');
                    if (manual_prefix.match(reg)) {
                        jQuery('#wpvivid_set_incremental_schedule_prefix').val('');
                        jQuery('#wpvivid_incremental_schedule_prefix').html('*');
                        alert('You can not use word \'wpvivid\' to comment the backup.');
                    }
                    else{
                        jQuery('#wpvivid_incremental_schedule_prefix').html(manual_prefix);
                    }
                }
            });

            function wpvivid_recalc_incremental_backup_size(website_item_arr, custom_option)
            {
                if(website_item_arr.length > 0)
                {
                    console.log(website_item_arr);
                    var website_item = website_item_arr.shift();
                    var ajax_data = {
                        'action': 'wpvivid_recalc_backup_size',
                        'website_item': website_item,
                        'custom_option': custom_option,
                        'incremental': '1'
                    };
                    wpvivid_post_request_addon(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                if(website_item === 'database')
                                {
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-database-size').html(jsonarray.database_size);
                                }
                                if(website_item === 'core')
                                {
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-core-size').html(jsonarray.core_size);
                                }
                                if(website_item === 'content')
                                {
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-content-size').html(jsonarray.content_size);
                                }
                                if(website_item === 'themes')
                                {
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-themes-size').html(jsonarray.themes_size);
                                }
                                if(website_item === 'plugins')
                                {
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-plugins-size').html(jsonarray.plugins_size);
                                }
                                if(website_item === 'uploads')
                                {
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-uploads-size').html(jsonarray.uploads_size);
                                }
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-additional-folder-size').html(jsonarray.additional_size);
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-total-file-size').html(jsonarray.total_file_size);
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);

                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-additional-folder-size').html(jsonarray.additional_size);
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-total-file-size').html(jsonarray.total_file_size);
                                    jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                                    jQuery('#wpvivid_recalc_incremental_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_incremental_backup_size(website_item_arr, custom_option);
                            }
                            else
                            {
                                alert(jsonarray.error);
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_recalc_incremental_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_incremental_backup_size(website_item_arr, custom_option);
                            }
                        }
                        catch (err) {
                            alert(err);
                            if(website_item === 'additional_folder')
                            {
                                jQuery('#wpvivid_recalc_incremental_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                            wpvivid_recalc_incremental_backup_size(website_item_arr, custom_option);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        if(website_item === 'additional_folder')
                        {
                            jQuery('#wpvivid_recalc_incremental_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                        wpvivid_recalc_incremental_backup_size(website_item_arr, custom_option);
                    });
                }
            }

            jQuery('#wpvivid_recalc_incremental_backup_size').click(function(){
                var file_json = wpvivid_create_incremental_json_ex('wpvivid_incremental_backup_deploy', 'files');
                var db_json = wpvivid_create_incremental_json_ex('wpvivid_incremental_backup_deploy', 'database');

                var custom = {};
                custom['custom'] = {
                    'files': file_json,
                    'db': db_json,
                };
                var custom_option = JSON.stringify(custom);

                jQuery('#wpvivid_recalc_incremental_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-database-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-core-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-themes-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-plugins-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-uploads-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-content-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-additional-folder-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-total-file-size').html('calculating');
                jQuery('#wpvivid_incremental_backup_deploy').find('.wpvivid-incremental-total-exclude-file-size').html('calculating');

                var website_item_arr = new Array('database', 'core', 'content', 'themes', 'plugins', 'uploads', 'additional_folder');

                wpvivid_recalc_incremental_backup_size(website_item_arr, custom_option);
            });

            jQuery(document).ready(function (){
                jQuery('#wpvivid_recalc_incremental_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});

                var wpvivid_incremental_backup_table = wpvivid_incremental_backup_table || {};
                wpvivid_incremental_backup_table.init_refresh = false;
                wpvivid_incremental_backup_table.db_retry = 0;
                wpvivid_incremental_backup_table.theme_plugins_retry = 0;

                jQuery(document).on('wpvivid_refresh_incremental_custom_backup_tables', function(event, type){
                    event.stopPropagation();
                    if(!wpvivid_incremental_backup_table.init_refresh){
                        wpvivid_incremental_backup_table.init_refresh = true;
                        wpvivid_refresh_incremental_backup_table();
                    }
                });
            });

            jQuery('input:radio[option=incremental_backup][name=save_local_remote]').click(function(){
                var value = jQuery(this).val();
                if(value === 'remote'){
                    jQuery( document ).trigger( 'wpvivid-has-default-remote', 'incremental_schedule');
                }
            });

            jQuery('input:radio[option=incremental_backup_db][name=backup_db]').click(function(){
                var value = jQuery(this).val();
                if(value === 'db'){
                    jQuery( '#wpvivid_incremental_backup_db' ).hide();
                }
                else{
                    jQuery( '#wpvivid_incremental_backup_db' ).show();
                }
            });

            jQuery('input:radio[option=incremental_backup_file][name=backup_file]').click(function(){
                var value = jQuery(this).val();
                if(value === 'files'){
                    jQuery( '#wpvivid_incremental_backup_file' ).hide();
                }
                else{
                    jQuery( '#wpvivid_incremental_backup_file' ).show();
                }
            });
        </script>
        <?php
        //echo json_encode($incremental_backup_data);
        //echo json_encode($incremental_schedules);
    }

    public function export_setting_addon($json)
    {
        $default = array();

        $enable_incremental_schedules=get_option('wpvivid_enable_incremental_schedules', false);
        $incremental_schedules=get_option('wpvivid_incremental_schedules', $default);
        $incremental_backup_data=get_option('wpvivid_incremental_backup_data', $default);

        $json['data']['wpvivid_enable_incremental_schedules']=$enable_incremental_schedules;
        $json['data']['wpvivid_incremental_schedules']=$incremental_schedules;
        $json['data']['wpvivid_incremental_backup_data']=$incremental_backup_data;

        return $json;
    }
}