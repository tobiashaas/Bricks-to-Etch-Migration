<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Admin_load: yes
 * Need_init: yes
 * Interface Name: WPvivid_Schedule_Display_Addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPvivid_Schedule_List extends WP_List_Table
{
    public $page_num;
    public $schedule_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'schedule',
                'screen' => 'schedule',
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
        /*$columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['wpvivid_status'] = __( 'Status', 'wpvivid' );
        $columns['wpvivid_backup_cycles'] =__( 'Backup Cycles', 'wpvivid'  );
        $columns['wpvivid_last_backup'] = __( 'Last Backup', 'wpvivid'  );
        $columns['wpvivid_next_backup'] = __( 'Next Backup', 'wpvivid'  );
        $columns['wpvivid_backup_type'] = __( 'Backup Type', 'wpvivid'  );
        $columns['wpvivid_storage'] = __( 'Storage', 'wpvivid'  );
        $columns['wpvivid_actions'] = __( 'Actions', 'wpvivid'  );*/
        $columns['wpvivid_backup_type'] = __( 'Backup Type', 'wpvivid'  );
        $columns['wpvivid_backup_cycles'] = __( 'Backup Cycles', 'wpvivid'  );
        $columns['wpvivid_last_backup'] = __( 'Last Backup', 'wpvivid'  );
        $columns['wpvivid_next_backup'] = __( 'Next Backup', 'wpvivid'  );
        $columns['wpvivid_storage'] = __( 'Storage', 'wpvivid'  );
        //$columns['wpvivid_status'] = __( 'Status', 'wpvivid' );
        $columns['wpvivid_on_off_control'] = __( 'On/off', 'wpvivid'  );
        $columns['wpvivid_actions'] = __( 'Actions', 'wpvivid'  );
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

    public function  column_cb( $schedule )
    {
        echo '<input type="checkbox" />';
        /*if ($schedule['status'] == 'Active')
        {
            echo '<input type="checkbox" checked/>';
        } else {
            echo '<input type="checkbox"/>';
        }*/
    }

    public function _column_wpvivid_status( $schedule )
    {
        if($schedule['status'] === 'Active')
        {
            $status = 'Scheduled';
        }
        else
        {
            $status = 'Disabled';
        }
        echo '<td class="wpvivid-schedule-status">'.$status.'</td>';
    }

    public function _column_wpvivid_backup_cycles( $schedule )
    {
        echo '<td>'.$schedule['backup_cycles'].'</td>';
    }

    public function _column_wpvivid_last_backup( $schedule )
    {
        echo '<td>'.$schedule['last_backup_time'].'</td>';
    }

    public function _column_wpvivid_next_backup( $schedule )
    {
        echo '<td>'.$schedule['next_start_time'].'</td>';
    }

    public function _column_wpvivid_backup_type( $schedule )
    {
        echo '<td>'.$schedule['schedule_backup_type'].'</td>';
    }

    public function _column_wpvivid_storage( $schedule )
    {
        echo '<td>'.$schedule['schedule_backup_to'].'</td>';
    }

    public function _column_wpvivid_on_off_control( $schedule )
    {
        if($schedule['status'] === 'Active')
        {
            $style = 'checked';
        }
        else
        {
            $style = '';
        }
        echo '<td>
                    <label class="wpvivid-switch" title="Enable/Disable the job">
                        <input class="wpvivid-schedule-on-off-control" type="checkbox" '.$style.'>
						<span class="wpvivid-slider wpvivid-round"></span>
				    </label>
               </td>';
    }

    public function _column_wpvivid_actions( $schedule )
    {
        if($schedule['status'] === 'Active')
        {
            $class = 'wpvivid-dashicons-green wpvivid-schedule-run';
            $style = 'checked; cursor: pointer;';
            $title = 'Go now';
        }
        else
        {
            $class = 'wpvivid-dashicons-grey';
            $style = 'cursor: pointer;';
            $title = '';
        }
        echo '<td>
                    <span class="dashicons dashicons-controls-play '.$class.'" style="'.$style.'" title="'.$title.'"></span>
                    <span class="dashicons dashicons-admin-generic wpvivid-dashicons-blue wpvivid-schedule-edit" style="cursor: pointer;" title="Edit the job"></span>
                    <span class="dashicons dashicons-trash wpvivid-dashicons-grey wpvivid-schedule-delete" style="cursor: pointer;" title="Delete the job"></span>
                </td>';
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
        /*if ($schedule['status'] == 'Active')
        {
            $class='schedule-item schedule-active';
        } else {
            $class='schedule-item';
        }*/
        $class='schedule-item';
        ?>
    <tr class="<?php echo $class;?>" slug="<?php echo $schedule['id'];?>">
        <?php $this->single_row_columns( $schedule ); ?>
        <?php
        if(isset($schedule['backup']['backup_prefix']))
        {
            ?>
            <tr><td colspan="7">Comment: <?php echo $schedule['backup']['backup_prefix']; ?></td></tr>
            <?php
        }
        ?>
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
            $css_type = 'margin: 0 0 1em 0';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'display: none;';
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

            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo " data-wp-lists='list:$singular'";
                }
                ?>
            >
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

            <tfoot>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </tfoot>

        </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }

    public function display_rows_or_placeholder() {
        if ( $this->has_items() ) {
            $this->display_rows();
        } else {
            echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
            _e( 'There is no schedule created yet.' );
            echo '</td></tr>';
        }
    }

    protected function get_table_classes()
    {
        return array( 'widefat plugin-install' );
    }
}

class WPvivid_Schedule_Display_Addon
{
    protected $schedule_type = array(
        'wpvivid_hourly'=>'Every hour',
        'wpvivid_2hours'=>'Every 2 hours',
        'wpvivid_4hours'=>'Every 4 hours',
        'wpvivid_6hours'=>'Every 6 hours',
        'wpvivid_8hours'=>'Every 8 hours',
        'wpvivid_12hours'       =>  '12Hours',
        'twicedaily'             =>  '12Hours',
        'wpvivid_daily'         =>   'Daily',
        'daily'                  =>   'Daily',
        'onceday'                =>   'Daily',
        'wpvivid_2days'=>'Every 2 days',
        'wpvivid_3days'=>'Every 3 days',
        'wpvivid_weekly'        =>   'Weekly',
        'weekly'                 =>   'Weekly',
        'wpvivid_fortnightly'  =>   'Fortnightly',
        'fortnightly'           =>   'Fortnightly',
        'wpvivid_monthly'      =>   'Monthly',
        'monthly'               =>    'Monthly',
        'montly'                =>    'Monthly'
    );

    public $main_tab;

    public $schedule_backup;
    public $update_schedule_backup;

    public function __construct()
    {
        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),11);

        add_filter('wpvivid_export_setting_addon', array($this, 'export_setting_addon'), 11);

        add_filter('wpvivid_add_schedule_backup_type_addon', array($this, 'wpvivid_schedulepage_add_backup_type'), 12, 2);

        add_filter('wpvivid_schedule_time',array($this, 'schedule_time'),11);

        add_filter('wpvivid_custom_backup_data_transfer', array($this, 'wpvivid_custom_backup_data_transfer'), 10, 3);

        add_filter('wpvivid_set_schedule_data', array($this, 'set_schedule_data'),10,2);

        add_action('wp_ajax_wpvivid_enable_schedule_backup_addon', array($this, 'enable_schedule_backup'));
        add_action('wp_ajax_wpvivid_set_schedule_addon',array($this,'set_schedule'));
        add_action('wp_ajax_wpvivid_run_schedule_addon', array($this, 'run_schedule'));
        add_action('wp_ajax_wpvivid_edit_schedule_addon', array($this, 'edit_schedule'));
        add_action('wp_ajax_wpvivid_delete_schedule_addon',array($this, 'delete_schedule'));
        add_action('wp_ajax_wpvivid_update_schedule_addon', array($this, 'update_schedule'));
        add_action('wp_ajax_wpvivid_save_schedule_status', array($this, 'save_schedule_status'));
        add_action('wp_ajax_wpvivid_enable_schedule',array($this, 'enable_schedule'));
        add_action('wp_ajax_wpvivid_get_schedule_list_page', array($this, 'get_schedule_list_page'));

        //dashboard
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        add_filter('wpvivid_get_general_schedule_status', array($this, 'get_general_schedule_status'));
    }

    public function get_general_schedule_status($status)
    {
        $schedules_list = self::wpvivid_get_schedule_list();
        if(!empty($schedules_list))
        {
            $general_schedule_enable = false;
            foreach ($schedules_list as $schedule)
            {
                if($schedule['status'] === 'Active')
                {
                    $general_schedule_enable = true;
                    $status = true;
                    break;
                }
            }
            if(!$general_schedule_enable)
            {
                $status = false;
            }
        }
        else
        {
            $status = false;
        }
        return $status;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-schedule';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-schedule';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_backup_schedule');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Backup Schedule');
            $submenu['menu_title'] = 'Backup Schedule';

            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-schedule");

            $submenu['menu_slug'] = strtolower(sprintf('%s-schedule', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 6;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_backup_schedule');
        if($display) {
            $menu['id'] = 'wpvivid_admin_menu_schedule';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Backup Schedule';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-schedule');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-schedule');
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-schedule");
            $menu['index'] = 6;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function export_setting_addon($json){
        $default = array();
        $schedules = get_option('wpvivid_schedule_addon_setting', $default);
        $json['data']['wpvivid_schedule_addon_setting'] = $schedules;
        if(isset($json['data']['wpvivid_schedule_addon_setting']) && !empty($json['data']['wpvivid_schedule_addon_setting'])) {
            foreach ($json['data']['wpvivid_schedule_addon_setting'] as $index => $value){
                if (wp_get_schedule($value['id'], array($value['id']))) {
                    $recurrence = wp_get_schedule($value['id'], array($value['id']));
                    $timestamp = wp_next_scheduled($value['id'], array($value['id']));
                    $json['data']['wpvivid_schedule_addon_setting'][$index]['recurrence'] = $recurrence;
                    $json['data']['wpvivid_schedule_addon_setting'][$index]['next_start'] = $timestamp;
                }
            }
        }
        return $json;
    }

    /***** schedule display filter begin *****/
    public function wpvivid_schedulepage_add_backup_type($html, $type)
    {
        ob_start();
        ?>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type); ?>" name="<?php esc_attr_e($type); ?>_backup_type" value="files+db" checked="checked">
            <span>Wordpress Files + Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type); ?>" name="<?php esc_attr_e($type); ?>_backup_type" value="db">
            <span>Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type); ?>" name="<?php esc_attr_e($type); ?>_backup_type" value="files">
            <span>Wordpress Files</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type); ?>" name="<?php esc_attr_e($type); ?>_backup_type" value="custom">
            <span>Custom content</span>
        </label>
        <?php
        $html .= ob_get_clean();
        return $html;
    }
    /***** schedule display filter end *****/

    /***** schedule filters begin *****/

    public function schedule_time()
    {
        $html='';

        $display_array = array("12Hours", "Daily", "Weekly", "Fortnightly", "Monthly");
        foreach($display_array as $display)
        {
            $schedule_check = $this->check_schedule_type($display);
            if($schedule_check['result'])
            {
                $html.=' <label><input type="radio" option="schedule" name="recurrence" value="'.$schedule_check['display'].'" />';
                $html.='<span>'.$display.'</span>';
                $html.='</label><br>';
            }
            else{
                $html.='<p>Warning: Unable to set '.$display.' backup schedule</p>';
            }
        }
        return $html;
    }

    public function check_schedule_type($display){
        $schedules = wp_get_schedules();
        $check_res = false;
        $ret = array();
        foreach ($this->schedule_type as $key => $value){
            if($value == $display){
                if(isset($schedules[$key])){
                    $check_res = true;
                    $ret['type']=$key;
                    break;
                }
            }
        }
        $ret['result']=$check_res;
        return $ret;
    }

    public function wpvivid_custom_backup_data_transfer($options, $data, $type)
    {
        if(!isset($data['database_check'])){
            $data['database_check'] = 0;
        }
        $options['backup_select']['db'] = intval($data['database_check']);
        if(!isset($data['database_list'])){
            $data['database_list'] = array();
        }
        $options['exclude_tables'] = $data['database_list'];
        if(!isset($data['additional_database_check'])){
            $data['additional_database_check'] = 0;
        }
        $options['backup_select']['additional_db'] = intval($data['additional_database_check']);

        if(!isset($data['themes_check'])){
            $data['themes_check'] = 0;
        }
        $options['backup_select']['themes'] = intval($data['themes_check']);
        $themes_exclude_list = array();
        if(isset($data['themes_list'])) {
            foreach ($data['themes_list'] as $key => $value){
                $themes_exclude_list[] = $key;
            }
        }
        else{
            $data['themes_list'] = array();
        }
        $options['exclude_themes'] = $themes_exclude_list;
        $themes_exclude_file_list=array();
        $themes_extension_tmp = array();
        if(isset($data['themes_extension']) && !empty($data['themes_extension']))
        {
            $str_tmp = explode(',', $data['themes_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $themes_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $themes_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['themes_extension'] = $themes_extension_tmp;
        }
        else{
            $data['themes_extension'] = array();
        }
        $options['exclude_themes_files'] = $themes_exclude_file_list;

        if(!isset($data['plugins_check'])){
            $data['plugins_check'] = 0;
        }
        $options['backup_select']['plugin'] = intval($data['plugins_check']);
        $plugins_exclude_list = array();
        if(isset($data['plugins_list'])) {
            foreach ($data['plugins_list'] as $key => $value){
                $plugins_exclude_list[] = $key;
            }
        }
        else{
            $data['plugins_list'] = array();
        }
        $options['exclude_plugins'] = $plugins_exclude_list;
        $plugins_exclude_file_list=array();
        $plugins_extension_tmp = array();
        if(isset($data['plugins_extension']) && !empty($data['plugins_extension']))
        {
            $str_tmp = explode(',', $data['plugins_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $plugins_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $plugins_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['plugins_extension'] = $plugins_extension_tmp;
        }
        else{
            $data['plugins_extension'] = array();
        }
        $options['exclude_plugins_files'] = $plugins_exclude_file_list;

        if(!isset($data['uploads_check'])){
            $data['uploads_check'] = 0;
        }
        $options['backup_select']['uploads'] = intval($data['uploads_check']);
        $upload_exclude_list = array();
        if(isset($data['uploads_list'])) {
            foreach ($data['uploads_list'] as $key => $value){
                $upload_exclude_list[] = $key;
            }
        }
        else{
            $data['uploads_list'] = array();
        }
        $options['exclude_uploads'] = $upload_exclude_list;
        $upload_exclude_file_list=array();
        $upload_extension_tmp = array();
        if(isset($data['upload_extension']) && !empty($data['upload_extension'])) {
            $str_tmp = explode(',', $data['upload_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $upload_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $upload_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['upload_extension'] = $upload_extension_tmp;
        }
        else{
            $data['upload_extension'] = array();
        }
        $options['exclude_uploads_files'] = $upload_exclude_file_list;

        if(!isset($data['content_check'])){
            $data['content_check'] = 0;
        }
        $options['backup_select']['content'] = intval($data['content_check']);
        $content_exclude_list=array();
        if(isset($data['content_list'])) {
            foreach ($data['content_list'] as $key => $value){
                $content_exclude_list[] = $key;
            }
        }
        else{
            $data['content_list'] = array();
        }
        $options['exclude_content'] = $content_exclude_list;
        $content_exclude_file_list=array();
        $content_extension_tmp = array();
        if(isset($data['content_extension']) && !empty($data['content_extension'])) {
            $str_tmp = explode(',', $data['content_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $content_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $content_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['content_extension'] = $content_extension_tmp;
        }
        else{
            $data['content_extension'] = array();
        }
        $options['exclude_content_files'] = $content_exclude_file_list;

        if(!isset($data['core_check'])){
            $data['core_check'] = 0;
        }
        $options['backup_select']['core'] = intval($data['core_check']);

        if(!isset($data['other_check'])){
            $data['other_check'] = 0;
        }
        $options['backup_select']['other'] = intval($data['other_check']);
        $other_include_list=array();
        if(isset($data['other_list'])) {
            foreach ($data['other_list'] as $key => $value){
                $other_include_list[] = $key;
            }
        }
        else{
            $data['other_list'] = array();
        }
        $options['custom_other_root'] = $other_include_list;
        $other_exclude_file_list=array();
        $other_extension_tmp = array();
        if(isset($data['other_extension']) && !empty($data['other_extension'])) {
            $str_tmp = explode(',', $data['other_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $other_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $other_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['other_extension'] = $other_extension_tmp;
        }
        else{
            $data['other_extension'] = array();
        }
        $options['exclude_custom_other_files'] = $other_exclude_file_list;
        $options['exclude_custom_other']=array();

        if($options['backup_select']['additional_db'] === 1){
            if($type === 'general_backup') {
                $history = WPvivid_Custom_Backup_Manager::wpvivid_get_custom_settings();
            }
            else if($type === 'incremental_backup_file'){
                $history = WPvivid_custom_backup_selector::get_incremental_file_settings();
            }
            else if($type === 'incremental_backup_db'){
                $history = WPvivid_custom_backup_selector::get_incremental_db_setting();
            }
            if(isset($history['additional_database_option']['additional_database_list']) && !empty($history['additional_database_option']['additional_database_list'])) {
                $options['additional_database_list'] = $history['additional_database_option']['additional_database_list'];
            }
            else{
                $options['additional_database_list'] = array();
            }
        }

        if($type === 'general_backup') {
            WPvivid_Custom_Interface_addon::update_custom_backup_setting($data);
        }
        else if($type === 'incremental_backup_file'){
            WPvivid_custom_backup_selector::set_incremental_file_settings($data);
        }
        else if($type === 'incremental_backup_db'){
            WPvivid_custom_backup_selector::set_incremental_db_setting($data);
        }

        return $options;
    }

    public static function set_schedule_data($schedule_data,$schedule)
    {
        $schedule_data['status'] = $schedule['status'];
        $schedule_data['type']=$schedule['recurrence'];
        $time['type']=$schedule['recurrence'];

        if(isset($schedule['week']))
        {
            $time['start_time']['week']=$schedule['week'];
            $schedule_data['week']=$schedule['week'];
        }
        else
            $time['start_time']['week']='mon';

        if(isset($schedule['day']))
        {
            $schedule_data['day']=$schedule['day'];
            $time['start_time']['day']=$schedule['day'];
        }
        else
            $time['start_time']['day']='01';

        if(isset($schedule['current_day']))
        {
            $schedule_data['current_day']=$schedule['current_day'];
            $time['start_time']['current_day']=$schedule['current_day'];
        }
        else
            $time['start_time']['current_day']="00:00";

        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
        $schedule_data['start_time']=$timestamp;

        $schedule_data['backup']=$schedule['backup'];
        return $schedule_data;
    }

    /***** schedule filters end *****/

    /***** useful function begin *****/
    public function init_schedule_hooks($schedule_hooks)
    {
        /*
        global $wpvivid_plugin;
        foreach ($schedule_hooks as $key=>$schedule_hook)
        {
            add_action($schedule_hook, array($wpvivid_plugin, 'main_schedule'));
        }
        */
    }

    public function check_schedule_option($data)
    {
        $ret['result']=WPVIVID_PRO_FAILED;

        if(isset($data['status'])){
            $ret['schedule']['status'] = $data['status'];
        }

        if(isset($data['recurrence']))
        {
            $recurrence= $this->check_schedule_recurrence($data['recurrence']);

            if($recurrence['result']=='success')
            {
                $ret['schedule']['recurrence'] =$data['recurrence'];
            }
            else {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']=$recurrence['error'];
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
                return $ret;
            }
        }

        $data['save_local_remote']=sanitize_text_field($data['save_local_remote']);

        if(!empty($data['save_local_remote']))
        {
            if($data['save_local_remote'] == 'remote')
            {
                $remote_storage=WPvivid_Setting::get_remote_options();
                if($remote_storage == false)
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']=__('There is no default remote storage configured. Please set it up first.', 'wpvivid');
                    $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
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

        if(isset($data['remote_id_select']) && $data['remote_id_select'] !== 'all'){
            /*$remoteslist=WPvivid_Setting::get_all_remote_options();
            $remote_options = array();
            $remote_options[$data['remote_id_select']] = $remoteslist[$data['remote_id_select']];
            $ret['schedule']['backup']['remote_options'] = $remote_options;*/
            $ret['schedule']['backup']['remote_id'] = $data['remote_id_select'];
        }

        if(isset($data['schedule_backup_backup_type']) && !empty($data['schedule_backup_backup_type']))
        {
            if($data['schedule_backup_backup_type'] === 'custom')
            {
                //$ret['schedule']['backup'] = apply_filters('wpvivid_custom_backup_data_transfer', $ret['schedule']['backup'], $data['custom_dirs'], 'general_backup');
                $ret['schedule']['backup'] = apply_filters('wpvivid_get_schedule_backup_data', $ret['schedule']['backup'], $data);

            }
            else {
                $ret['schedule']['backup']['backup_files'] = $data['schedule_backup_backup_type'];
                if(isset($data['exclude_files']))
                {
                    $ret['schedule']['backup']['exclude_files']=$data['exclude_files'];
                }

                if(isset($data['exclude_file_type']))
                {
                    $ret['schedule']['backup']['exclude_file_type']=$data['exclude_file_type'];
                }
            }
        }

        /*
        if(isset($data['schedule_backup_backup_type']) && !empty($data['schedule_backup_backup_type']))
        {
            if($data['schedule_backup_backup_type'] === 'custom')
            {
                $ret['schedule']['backup'] = apply_filters('wpvivid_custom_backup_data_transfer', $ret['schedule']['backup'], $data['custom_dirs'], 'general_backup');
            }
            else {
                $ret['schedule']['backup']['backup_files'] = $data['schedule_backup_backup_type'];
            }
        }
        else
        {
            $ret['error']=__('Not found select backup type. Please set it up first.', 'wpvivid');
            return $ret;
        }*/

        if(isset($data['backup_prefix']) && !empty($data['backup_prefix']))
        {
            $ret['schedule']['backup']['backup_prefix'] = $data['backup_prefix'];
        }

        if(isset($data['current_day_hour']) && isset($data['current_day_minute'])){
            $ret['schedule']['current_day'] = $data['current_day_hour'].':'.$data['current_day_minute'];
        }
        /*if(isset($data['current_day']))
        {
            $ret['schedule']['current_day']=$data['current_day'];
        }*/

        if(isset($data['week']))
        {
            $ret['schedule']['week']=$data['week'];
        }

        if(isset($data['day']))
        {
            $ret['schedule']['day']=$data['day'];
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function check_update_schedule_option($data)
    {
        $ret['result']=WPVIVID_PRO_FAILED;

        if(isset($data['status'])){
            $ret['schedule']['status'] = $data['status'];
        }

        if(isset($data['recurrence']))
        {
            $recurrence= $this->check_schedule_recurrence($data['recurrence']);

            if($recurrence['result']=='success')
            {
                $ret['schedule']['recurrence'] =$data['recurrence'];
            }
            else {
                $ret['result']=WPVIVID_PRO_FAILED;
                $ret['error']=$recurrence['error'];
                return $ret;
            }
        }

        $data['update_schedule_backup_save_local_remote']=sanitize_text_field($data['update_schedule_backup_save_local_remote']);

        if(!empty($data['update_schedule_backup_save_local_remote']))
        {
            if($data['update_schedule_backup_save_local_remote'] == 'remote')
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

        if(isset($data['update_schedule_backup_backup_type']) && !empty($data['update_schedule_backup_backup_type']))
        {
            if($data['update_schedule_backup_backup_type'] === 'custom')
            {
                //$ret['schedule']['backup'] = apply_filters('wpvivid_custom_backup_data_transfer', $ret['schedule']['backup'], $data['custom_dirs'], 'general_backup');
                $ret['schedule']['backup'] = apply_filters('wpvivid_get_schedule_backup_data', $ret['schedule']['backup'], $data);

            }
            else {
                $ret['schedule']['backup']['backup_files'] = $data['update_schedule_backup_backup_type'];
                if(isset($data['exclude_files']))
                {
                    $ret['schedule']['backup']['exclude_files']=$data['exclude_files'];
                }

                if(isset($data['exclude_file_type']))
                {
                    $ret['schedule']['backup']['exclude_file_type']=$data['exclude_file_type'];
                }
            }
        }
        else
        {
            $ret['error']=__('Not found select backup type. Please set it up first.', 'wpvivid');
            return $ret;
        }

        if(isset($data['backup_prefix']) && !empty($data['backup_prefix']))
        {
            $ret['schedule']['backup']['backup_prefix'] = $data['backup_prefix'];
        }

        if(isset($data['current_day_hour']) && isset($data['current_day_minute'])){
            $ret['schedule']['current_day'] = $data['current_day_hour'].':'.$data['current_day_minute'];
        }
        /*if(isset($data['current_day']))
        {
            $ret['schedule']['current_day']=$data['current_day'];
        }*/

        if(isset($data['week']))
        {
            $ret['schedule']['week']=$data['week'];
        }

        if(isset($data['day']))
        {
            $ret['schedule']['day']=$data['day'];
        }

        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function check_schedule_recurrence($recurrence)
    {
        $schedules = wp_get_schedules();
        $ret = array();
        foreach ($schedules as $key => $value)
        {
            if($key == $recurrence)
            {
                $ret['result']='success';
                return $ret;
            }
        }
        $ret['result']='failed';
        $ret['error']='WP Cron Recurrence not found';
        return $ret;
    }

    public function add_schedule($schedule)
    {
        $schedule_data=array();
        $schedule_data['id']=uniqid('wpvivid_schedule_event');

        $schedule['backup']['ismerge']=1;
        $schedule['backup']['lock']=0;

        $schedule_data= apply_filters('wpvivid_set_schedule_data',$schedule_data, $schedule);

        if($schedule_data===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
            return $ret;
        }

        if(wp_get_schedule($schedule_data['id'], array($schedule_data['id'])))
        {
            wp_clear_scheduled_hook($schedule_data['id'], array($schedule_data['id']));
            $timestamp = wp_next_scheduled($schedule_data['id'], array($schedule_data['id']));
            wp_unschedule_event($timestamp,$schedule_data['id'],array($schedule_data['id']));
        }

        if($schedule['status'] === 'Active'){
            if(wp_schedule_event($schedule_data['start_time'], $schedule_data['type'], $schedule_data['id'],array($schedule_data['id']))===false)
            {
                $ret['result']='failed';
                $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
                $ret['data']=$schedule_data;
                $ret['option']=$schedule;
                return $ret;
            }

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

            //WPvivid_Setting::update_option('wpvivid_enable_schedules', true);
            WPvivid_Setting::update_option('wpvivid_enable_incremental_schedules', false);
        }

        $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
        $schedules[$schedule_data['id']]=$schedule_data;
        WPvivid_Setting::update_option('wpvivid_schedule_addon_setting',$schedules);
        $ret['result']='success';
        $success_msg = 'You have successfully added a schedule.';
        $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', true, $success_msg);
        $ret['data']=$schedule_data;
        return $ret;

        /*if(wp_schedule_event($schedule_data['start_time'], $schedule_data['type'], $schedule_data['id'],array($schedule_data['id']))===false)
        {
            $ret['result']='failed';
            $ret['error']=__('Failed to create a schedule. Please try again later.', 'wpvivid');
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
            $ret['data']=$schedule_data;
            $ret['option']=$schedule;
            return $ret;
        }
        else {
            $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
            $schedules[$schedule_data['id']]=$schedule_data;
            WPvivid_Setting::update_option('wpvivid_schedule_addon_setting',$schedules);
            $ret['result']='success';
            $success_msg = 'You have successfully added a schedule.';
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', true, $success_msg);
            $ret['data']=$schedule_data;
            return $ret;
        }*/
    }

    public static function get_time_zone($offset)
    {
        $time_zone_array = array(
            '-12'=>'Etc/GMT+12',
            '-11'=>'Etc/GMT+11',
            '-10'=>'Etc/GMT+10',
            '-9' =>'Etc/GMT+9',
            '-8' =>'Etc/GMT+8',
            '-7' =>'Etc/GMT+7',
            '-6' =>'Etc/GMT+6',
            '-5' =>'Etc/GMT+5',
            '-4' =>'Etc/GMT+4',
            '-3' =>'Etc/GMT+3',
            '-2' =>'Etc/GMT+2',
            '-1' =>'Etc/GMT+1',
            '0'  =>'UTC',
            '1'  =>'Etc/GMT-1',
            '2'  =>'Etc/GMT-2',
            '3'  =>'Etc/GMT-3',
            '4'  =>'Etc/GMT-4',
            '5'  =>'Etc/GMT-5',
            '6'  =>'Etc/GMT-6',
            '7'  =>'Etc/GMT-7',
            '8'  =>'Etc/GMT-8',
            '9'  =>'Etc/GMT-9',
            '10' =>'Etc/GMT-10',
            '11' =>'Etc/GMT-11',
            '12' =>'Etc/GMT-12',
            '13' =>'Etc/GMT-13',
            '14' =>'Etc/GMT-14'
        );

        $time_zone = 'not_found';
        foreach ($time_zone_array as $key => $value)
        {
            if($key == $offset)
            {
                $time_zone = $value;
                break;
            }
        }
        return $time_zone;
    }

    public static function get_start_time($time,$local_time=true)
    {
        if(!is_array( $time ) )
        {
            return false;
        }

        if(!isset($time['type']))
        {
            return false;
        }

        $week=$time['start_time']['week'];
        $day=$time['start_time']['day'];
        $current_day=$time['start_time']['current_day'];

        $default_time_zone = date_default_timezone_get();
        $offset=get_option('gmt_offset');
        $time_zone = self::get_time_zone($offset);

        if($time_zone !== 'not_found')
        {
            date_default_timezone_set($time_zone);
            if((strtotime('now'))>strtotime($current_day)){
                $daily_start_time = $current_day.' +1 day';
            }
            else{
                $daily_start_time = $current_day;
            }

            $weekly_tmp = $week.' '.$current_day;
            if((strtotime('now'))>strtotime($weekly_tmp)) {
                $weekly_start_time = $week.' '.$weekly_tmp.' next week';
            }
            else{
                $weekly_start_time = $weekly_tmp;
            }

            $date_now = date("Y-m-",time());
            $monthly_tmp = $date_now.$day.' '.$current_day;
            if((strtotime('now'))>strtotime($monthly_tmp)){
                $date_now = date("Y-m-",strtotime('first day of next month'));
                $monthly_start_time = $date_now.$day.' '.$current_day;
            }
            else{
                $monthly_start_time = $monthly_tmp;
            }
        }
        else
        {
            $offset=$offset * 60 * 60;

            if((strtotime('now')+$offset)>strtotime($current_day)){
                $daily_start_time = $current_day.' +1 day';
            }
            else{
                $daily_start_time = $current_day;
            }

            $weekly_tmp = $week.' '.$current_day;
            if((strtotime('now')+$offset)>strtotime($weekly_tmp)) {
                $weekly_start_time = $week.' '.$weekly_tmp.' next week';
            }
            else{
                $weekly_start_time = $weekly_tmp;
            }

            $date_now = date("Y-m-",time());
            $monthly_tmp = $date_now.$day.' '.$current_day;
            if((strtotime('now')+$offset)>strtotime($monthly_tmp)){
                $date_now = date("Y-m-",strtotime('first day of next month'));
                $monthly_start_time = $date_now.$day.' '.$current_day;
            }
            else{
                $monthly_start_time = $monthly_tmp;
            }
        }

        $schedule_type_ex = array(
            'wpvivid_hourly'=>'Every hour',
            'wpvivid_2hours'=>'Every 2 hours',
            'wpvivid_4hours'=>'Every 4 hours',
            'wpvivid_6hours'=>'Every 6 hours',
            'wpvivid_8hours'=>'Every 8 hours',
            'wpvivid_12hours'=>'12Hours',
            'twicedaily'=>'12Hours',
            'wpvivid_daily'=>'Daily',
            'wpvivid_2days'=>'Every 2 days',
            'wpvivid_3days'=>'Every 3 days',
            'daily'=>'Daily',
            'onceday'=>'Daily',
            'wpvivid_weekly'=>'Weekly',
            'weekly'=>'Weekly',
            'wpvivid_fortnightly'=>'Fortnightly',
            'fortnightly'=>'Fortnightly',
            'wpvivid_monthly'=>'Monthly',
            'monthly'=>'Monthly',
            'montly'=>'Monthly'
        );

        $display_array = array(
            'Every hour'=>$daily_start_time,
            'Every 2 hours'=>$daily_start_time,
            'Every 4 hours'=>$daily_start_time,
            'Every 6 hours'=>$daily_start_time,
            'Every 8 hours'=>$daily_start_time,
            'Every 12 hours'=>$daily_start_time,
            'wpvivid_12hours'=>'12Hours',
            "12Hours"=>$daily_start_time,
            "Daily"=>$daily_start_time,
            "Every 2 days"=>$daily_start_time,
            'Every 3 days'=>$daily_start_time,
            "Weekly"=>$weekly_start_time,
            "Fortnightly"=>$weekly_start_time,
            "Monthly"=>$monthly_start_time
        );
        foreach ($schedule_type_ex as $key => $value)
        {
            if($key == $time['type'])
            {
                foreach ($display_array as $display_key => $display_value)
                {
                    if($value == $display_key)
                    {
                        if($local_time)
                        {
                            if($time_zone !== 'not_found')
                            {
                                $start_time = strtotime($display_value);
                                date_default_timezone_set($default_time_zone);
                                return $start_time;
                            }
                            else
                            {
                                $offset=get_option('gmt_offset');
                                $offset=$offset * 60 * 60;
                                return strtotime($display_value)-$offset;
                            }
                        }
                        else
                        {
                            if($time_zone !== 'not_found')
                            {
                                $start_time = strtotime($display_value);
                                date_default_timezone_set($default_time_zone);
                                return $start_time;
                            }
                            else
                            {
                                return strtotime($display_value);
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
    /***** useful function end *****/

    /***** schedule ajax begin *****/
    public function enable_schedule_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try
        {
            if (isset($_POST['enable'])) {
                if ($_POST['enable']) {
                    //WPvivid_Setting::update_option('wpvivid_enable_schedules', true);
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
                }
                else{
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
                }
                $ret['result'] = 'success';
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

    public function set_schedule()
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

                $ret=$this->add_schedule($ret['schedule']);

                $schedules_list = self::wpvivid_get_schedule_list();

                $table=new WPvivid_Schedule_List();
                $table->set_schedule_list($schedules_list);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();


                if(!empty($schedules_list)){
                    $ret['create_part'] = '<span>
                                                <select id="wpvivid_select_schedule_status">
                                                    <option value="0" selected="selected">Bulk actions</option>
                                                    <option value="activate">Activate</option>
                                                    <option value="deactivate">Deactivate</option>
                                                    <option value="delete">Delete</option>
                                                </select>
                                            </span>
                                            <span>
                                                <input id="wpvivid_click_save_schedule_changed" type="submit" class="button action" value="Apply" />
                                            </span>
                                            <span id="wpvivid_create_schedule_btn" style="padding:1em 1em 1em 0;">or, <a href="#">create a new schedule</a></span>';
                }
                else{
                    $ret['create_part'] = '<span><input class="button-primary" id="wpvivid_create_schedule_btn" type="submit" value="Create a new schedule" /></span>';
                }

                $ret['html'] = $html;
                $schedule_html = '';
                $ret['schedule_part'] = apply_filters('wpvivid_schedule_module', $schedule_html);
                $ret['schedule_enable'] = apply_filters('wpvivid_get_general_schedule_status', false);
                $ret['incremental_schedule_enable'] = get_option('wpvivid_enable_incremental_schedules', false);
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

        if(isset($schedule_backup_options['remote_id']))
        {
            $remote_id=$schedule_backup_options['remote_id'];
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $tmp_remote_option=array();
                $tmp_remote_option[$remote_id]=$remoteslist[$remote_id];
                $backup_options['remote_options']=$tmp_remote_option;
            }
        }
        else if(isset($schedule_backup_options['remote_options'])&&$schedule_backup_options['remote_options'])
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

    public function force_schedule_single_event( $hook, $args = array() )
    {
        $event = (object) array(
            'hook'      => $hook,
            'timestamp' => 1,
            'schedule'  => false,
            'args'      => $args,
        );

        $crons = _get_cron_array();

        if ( empty( $crons ) ) {
            $crons = array();
        }

        $key   = md5( serialize( $event->args ) );

        $crons[ $event->timestamp ][ $event->hook ][ $key ] = array(
            'schedule' => $event->schedule,
            'args'     => $event->args,
        );
        ksort( $crons );

        $result = _set_cron_array( $crons );

        // Not using the WP_Error from `_set_cron_array()` here so we can provide a more specific error message.
        if ( false === $result ) {
            return false;
        }

        return true;
    }

    public function run_schedule()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try{
            $schedule_id = $_POST['id'];
            $crons = _get_cron_array();

            if ( empty( $crons ) ) {
                $crons = array();
            }

            foreach ( $crons as $timestamp => $cronhooks )
            {
                foreach ( (array) $cronhooks as $hook => $args )
                {
                    if($hook === $schedule_id)
                    {
                        foreach($args as $sig => $arg)
                        {
                            $event = $arg;
                            $event['hook'] = $hook;
                            $event['timestamp'] = $timestamp;

                            $event = (object) $event;

                            delete_transient( 'doing_cron' );
                            $scheduled = $this->force_schedule_single_event( $hook, $event->args );

                            if ( !$scheduled )
                            {
                                $ret['result']='failed';
                                $ret['error']='Failed to add the schedule to the cron queue. Please try it again.';
                                $ret['error'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
                            }

                            $results = spawn_cron();
                            if($results)
                            {
                                $ret['result']='success';
                                $success_msg = 'The schedule has been triggered successfully.';
                                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', true, $success_msg);
                            }
                            else
                            {
                                $ret['result']='failed';
                                $ret['error']='Failed to run the schedule. Please try it again.';
                                $ret['error'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
                            }
                            echo json_encode($ret);
                            die();
                        }
                    }
                }
            }

            $ret['result']='failed';
            $ret['error']='The schedule does not exist in the cron queue. Please try to update the backup schedule then try it again.';
            $ret['error'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function edit_schedule()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            if (isset($_POST['id'])) {
                $schedule_id = $_POST['id'];
                $schedules = WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');

                //$html = $this->add_schedule_time_select($schedule_id);
                $ret['result'] = 'success';
                $ret['schedule_info'] = $schedules[$schedule_id];

                $ret['schedule_info']['backup'] = $this->get_backup_data_from_schedule($ret['schedule_info']['backup']);
                if (isset($ret['schedule_info']['current_day'])) {
                    $dt = DateTime::createFromFormat("H:i", $ret['schedule_info']['current_day']);
                    /*$offset = get_option('gmt_offset');
                    $hours = $dt->format('H');
                    $minutes = $dt->format('i');
                    $hour = (float)$hours + $offset;

                    $whole = floor($hour);
                    $fraction = $hour - $whole;
                    $minute = (float)(60 * ($fraction)) + (int)$minutes;

                    $hour = (int)$hour;
                    $minute = (int)$minute;

                    if ($minute >= 60) {
                        $hour = (int)$hour + 1;
                        $minute = (int)$minute - 60;
                    }

                    if ($hour >= 24) {
                        $hour = $hour - 24;
                    } else if ($hour < 0) {
                        $hour = 24 - abs($hour);
                    }

                    if ($hour < 10) {
                        $hour = '0' . (int)$hour;
                    } else {
                        $hour = (string)$hour;
                    }

                    if ($minute < 10) {
                        $minute = '0' . (int)$minute;
                    } else {
                        $minute = (string)$minute;
                    }*/
                    $hour = $dt->format('H');
                    $minute = $dt->format('i');
                    $ret['schedule_info']['hours'] = $hour;
                    $ret['schedule_info']['minute'] = $minute;
                } else {
                    $ret['schedule_info']['hours'] = '00';
                    $ret['schedule_info']['minute'] = '00';
                }

                //
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
                //

                $pref = 'update_schedule_backup';
                //$exclude['database_option']['exclude_table_list'] = '';
                $exclude['custom_dirs']['exclude-tables']='';
                $exclude['custom_dirs']['include-tables']='';
                $exclude['themes_option']['exclude_themes_list'] = '';
                $exclude['plugins_option']['exclude_plugins_list'] = '';
                if (isset($ret['schedule_info']['backup']['exclude_tables']))
                {
                    //$exclude['database_option']['exclude_table_list'] = $ret['schedule_info']['backup']['exclude_tables'];
                    $exclude['custom_dirs']['exclude-tables']=$ret['schedule_info']['backup']['exclude_tables'];
                }
                if(isset($ret['schedule_info']['backup']['custom_dirs']['exclude-tables']))
                {
                    $exclude['custom_dirs']['exclude-tables']=$ret['schedule_info']['backup']['custom_dirs']['exclude-tables'];
                }
                if(isset($ret['schedule_info']['backup']['custom_dirs']['include-tables']))
                {
                    $exclude['custom_dirs']['include-tables']=$ret['schedule_info']['backup']['custom_dirs']['include-tables'];
                }
                if (isset($ret['schedule_info']['backup']['exclude_themes'])) {
                    foreach ($ret['schedule_info']['backup']['exclude_themes'] as $index => $themes){
                        unset($ret['schedule_info']['backup']['exclude_themes'][$index]);
                        $ret['schedule_info']['backup']['exclude_themes'][$themes]['name'] = $themes;
                        if(is_dir($themes_path.$themes)){
                            $ret['schedule_info']['backup']['exclude_themes'][$themes]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            $ret['schedule_info']['backup']['exclude_themes'][$themes]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    }
                }

                if (isset($ret['schedule_info']['backup']['exclude_plugins'])) {
                    foreach ($ret['schedule_info']['backup']['exclude_plugins'] as $index => $plugins){
                        unset($ret['schedule_info']['backup']['exclude_plugins'][$index]);
                        $ret['schedule_info']['backup']['exclude_plugins'][$plugins]['name'] = $plugins;
                        if(is_dir($plugins_path.$plugins)){
                            $ret['schedule_info']['backup']['exclude_plugins'][$plugins]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            $ret['schedule_info']['backup']['exclude_plugins'][$plugins]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    }
                }

                if (isset($ret['schedule_info']['backup']['exclude_content'])) {
                    foreach ($ret['schedule_info']['backup']['exclude_content'] as $index => $content){
                        unset($ret['schedule_info']['backup']['exclude_content'][$index]);
                        $ret['schedule_info']['backup']['exclude_content'][$content]['name'] = $content;
                        if(is_dir($content_path.$content)){
                            $ret['schedule_info']['backup']['exclude_content'][$content]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            $ret['schedule_info']['backup']['exclude_content'][$content]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    }
                }

                if (isset($ret['schedule_info']['backup']['exclude_uploads'])) {
                    foreach ($ret['schedule_info']['backup']['exclude_uploads'] as $index => $uploads){
                        unset($ret['schedule_info']['backup']['exclude_uploads'][$index]);
                        $ret['schedule_info']['backup']['exclude_uploads'][$uploads]['name'] = $uploads;
                        if(is_dir($uploads_path.$uploads)){
                            $ret['schedule_info']['backup']['exclude_uploads'][$uploads]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            $ret['schedule_info']['backup']['exclude_uploads'][$uploads]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    }
                }

                if (isset($ret['schedule_info']['backup']['custom_other_root'])) {
                    foreach ($ret['schedule_info']['backup']['custom_other_root'] as $index => $additional){
                        unset($ret['schedule_info']['backup']['custom_other_root'][$index]);
                        $ret['schedule_info']['backup']['custom_other_root'][$additional]['name'] = $additional;
                        if(is_dir($home_path.$additional)){
                            $ret['schedule_info']['backup']['custom_other_root'][$additional]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            $ret['schedule_info']['backup']['custom_other_root'][$additional]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    }
                }

                /*if (isset($ret['schedule_info']['backup']['exclude_themes'])) {
                    $exclude['themes_option']['exclude_themes_list'] = $ret['schedule_info']['backup']['exclude_themes'];
                }
                if (isset($ret['schedule_info']['backup']['exclude_plugins'])) {
                    $exclude['plugins_option']['exclude_plugins_list'] = $ret['schedule_info']['backup']['exclude_plugins'];
                }*/

                $ret_db = WPvivid_Backup_Restore_Page_addon::_get_table_info_ex($exclude, $pref);
                if ($ret_db['result'] === 'success') {
                    $ret['database_html'] = $ret_db['database_html'];
                    /*$ret_themes_plugins = WPvivid_Backup_Restore_Page_addon::_get_themes_plugin_info($exclude, $pref);
                    if ($ret_themes_plugins['result'] === 'success') {
                        $ret['themes_plugins_html'] = $ret_themes_plugins['themes_plugins_html'];
                    } else {
                        $ret = $ret_themes_plugins;
                    }*/
                } else {
                    $ret = $ret_db;
                }

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

    public function delete_schedule()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            $schedule_id = $_POST['id'];
            $schedules = WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
            unset($schedules[$schedule_id]);

            if (wp_get_schedule($schedule_id, array($schedule_id))) {
                wp_clear_scheduled_hook($schedule_id, array($schedule_id));
                $timestamp = wp_next_scheduled($schedule_id, array($schedule_id));
                wp_unschedule_event($timestamp, $schedule_id, array($schedule_id));
            }
            WPvivid_Setting::update_option('wpvivid_schedule_addon_setting', $schedules);

            $ret['result'] = 'success';
            $success_msg = 'The schedule has been deleted successfully.';
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', true, $success_msg);

            $schedules_list = self::wpvivid_get_schedule_list();

            $table = new WPvivid_Schedule_List();
            $table->set_schedule_list($schedules_list);
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();

            if(!empty($schedules_list)){
                $ret['create_part'] = '<span>
                                            <select id="wpvivid_select_schedule_status">
                                                <option value="0" selected="selected">Bulk actions</option>
                                                <option value="activate">Activate</option>
                                                <option value="deactivate">Deactivate</option>
                                                <option value="delete">Delete</option>
                                            </select>
                                        </span>
                                        <span>
                                            <input id="wpvivid_click_save_schedule_changed" type="submit" class="button action" value="Apply" />
                                        </span>
                                        <span id="wpvivid_create_schedule_btn" style="padding:1em 1em 1em 0;">or, <a href="#">create a new schedule</a></span>';
            }
            else{
                $ret['create_part'] = '<span><input class="button-primary" id="wpvivid_create_schedule_btn" type="submit" value="Create a new schedule" /></span>';
            }

            $ret['html'] = $html;
            $schedule_html = '';
            $ret['schedule_part'] = apply_filters('wpvivid_schedule_module', $schedule_html);
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function update_schedule(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try
        {
            if(isset($_POST['schedule']) && !empty($_POST['schedule']) && isset($_POST['id']) && !empty($_POST['id']))
            {
                $json = $_POST['schedule'];
                $json = stripslashes($json);
                $schedule = json_decode($json, true);
                if (is_null($schedule))
                {
                    die();
                }
                $ret = $this->check_update_schedule_option($schedule);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
                    echo json_encode($ret);
                    die();
                }

                $schedule_id=$_POST['id'];
                $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');

                $schedules[$schedule_id]['type'] = $ret['schedule']['recurrence'];
                if(isset($ret['schedule']['week']))
                {
                    $schedules[$schedule_id]['week'] = $ret['schedule']['week'];
                }
                if(isset($ret['schedule']['day']))
                {
                    $schedules[$schedule_id]['day'] = $ret['schedule']['day'];
                }
                $schedules[$schedule_id]['current_day'] = $ret['schedule']['current_day'];

                $time['type']=$ret['schedule']['recurrence'];
                if(isset($schedules[$schedule_id]['week']))
                    $time['start_time']['week']=$schedules[$schedule_id]['week'];
                else
                    $time['start_time']['week']='mon';

                if(isset($schedules[$schedule_id]['day']))
                    $time['start_time']['day']=$schedules[$schedule_id]['day'];
                else
                    $time['start_time']['day']='01';

                if(isset($schedules[$schedule_id]['current_day']))
                    $time['start_time']['current_day']=$schedules[$schedule_id]['current_day'];
                else
                    $time['start_time']['current_day']="00:00";
                $timestamp=WPvivid_Schedule_addon::get_start_time($time);
                $schedules[$schedule_id]['start_time']=$timestamp;

                $ret['schedule']['backup']['ismerge'] = 1;
                $ret['schedule']['backup']['lock'] = 0;
                $schedules[$schedule_id]['backup'] = $ret['schedule']['backup'];
                //$schedules[$schedule_id]['status'] = 'Active';

                $has_error = false;
                if($schedules[$schedule_id]['status'] === 'Active')
                {
                    if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                    {
                        wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    }
                    if(wp_schedule_event($schedules[$schedule_id]['start_time'], $schedules[$schedule_id]['type'], $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']))===false)
                    {
                        $has_error = true;
                        $ret['result']='failed';
                        $ret['error']=__('Failed to update the schedule. Please try again later.', 'wpvivid');
                        $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
                    }
                    else {
                        $has_error = false;
                    }
                }
                if(!$has_error){
                    WPvivid_Setting::update_option('wpvivid_schedule_addon_setting',$schedules);
                    $ret['result']='success';
                    $success_msg = 'You have successfully updated the schedule.';
                    $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', true, $success_msg);
                    $ret['data']=$schedules;

                    $schedules_list = self::wpvivid_get_schedule_list();

                    $table=new WPvivid_Schedule_List();
                    $table->set_schedule_list($schedules_list);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();

                    $ret['html'] = $html;
                    $schedule_html = '';
                    $ret['schedule_part'] = apply_filters('wpvivid_schedule_module', $schedule_html);
                }

                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
            echo json_encode($ret);
        }

        die();
    }

    public function save_schedule_status()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try
        {
            if(isset($_POST['schedule_data']) && !empty($_POST['schedule_data']))
            {
                $json_schedule_data = $_POST['schedule_data'];
                $json_schedule_data = stripslashes($json_schedule_data);
                $schedule_data = json_decode($json_schedule_data, true);

                $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
                foreach ($schedule_data as $schedule_id => $schedule_status)
                {
                    $schedules[$schedule_id]['status'] = $schedule_status;
                    if($schedule_status === 'Active')
                    {
                        //WPvivid_Setting::update_option('wpvivid_enable_schedules', true);
                        WPvivid_Setting::update_option('wpvivid_enable_incremental_schedules', false);

                        if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                        {
                            wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        }

                        $time['type']=$schedules[$schedule_id]['type'];
                        if(isset($schedules[$schedule_id]['week']))
                        {
                            $time['start_time']['week']=$schedules[$schedule_id]['week'];
                        }
                        else
                        {
                            $time['start_time']['week']='mon';
                        }

                        if(isset($schedules[$schedule_id]['day']))
                        {
                            $time['start_time']['day']=$schedules[$schedule_id]['day'];
                        }
                        else
                            $time['start_time']['day']='01';

                        if(isset($schedules[$schedule_id]['current_day']))
                        {
                            $time['start_time']['current_day']=$schedules[$schedule_id]['current_day'];
                        }
                        else
                            $time['start_time']['current_day']="00:00";

                        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
                        $schedules[$schedule_id]['start_time']=$timestamp;

                        if (wp_schedule_event($schedules[$schedule_id]['start_time'], $schedules[$schedule_id]['type'], $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])) === false) {
                            $ret['result'] = 'failed';
                            $ret['error'] = __('Failed to save the schedule. Please try again later.', 'wpvivid');
                            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
                            echo json_encode($ret);
                            die();
                        }
                    }
                    else if($schedule_status === 'InActive'){
                        if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                        {
                            wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        }
                    }
                    else
                    {
                        if (wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']))) {
                            wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                            wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        }
                        unset($schedules[$schedule_id]);
                    }
                }
                WPvivid_Setting::update_option('wpvivid_schedule_addon_setting', $schedules);

                if(!empty($schedules)){
                    $ret['create_part'] = '<span>
                                                <select id="wpvivid_select_schedule_status">
                                                    <option value="0" selected="selected">Bulk actions</option>
                                                    <option value="activate">Activate</option>
                                                    <option value="deactivate">Deactivate</option>
                                                    <option value="delete">Delete</option>
                                                </select>
                                            </span>
                                            <span>
                                                <input id="wpvivid_click_save_schedule_changed" type="submit" class="button action" value="Apply" />
                                            </span>
                                            <span id="wpvivid_create_schedule_btn" style="padding:1em 1em 1em 0;">or, <a href="#">create a new schedule</a></span>';
                }
                else{
                    $ret['create_part'] = '<span><input class="button-primary" id="wpvivid_create_schedule_btn" type="submit" value="Create a new schedule" /></span>';
                }

                if(apply_filters('wpvivid_get_general_schedule_status',false))
                {
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
                }

                $ret['result']='success';
                $success_msg = 'You have successfully saved the changes.';
                $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', true, $success_msg);
                $schedule_html = '';
                $ret['schedule_part'] = apply_filters('wpvivid_schedule_module', $schedule_html);
                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            $ret['result']='failed';
            $ret['error']=$message;
            $ret['notice'] = apply_filters('wpvivid_set_schedule_notice', false, $ret['error']);
            echo json_encode($ret);
        }

        die();
    }

    public function enable_schedule()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try
        {
            if(isset($_POST['status']) && !empty($_POST['status']) && isset($_POST['id']) && !empty($_POST['id']))
            {
                $schedule_id=$_POST['id'];
                $status=$_POST['status'];
                $schedules=WPvivid_Setting::get_option('wpvivid_schedule_addon_setting');
                $schedules[$schedule_id]['status'] = $status;

                if($status === 'Active')
                {
                    if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                    {
                        wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    }
                    if (wp_schedule_event($schedules[$schedule_id]['start_time'], $schedules[$schedule_id]['type'], $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])) === false)
                    {
                        $ret['result'] = 'failed';
                        $ret['error'] = __('Update scheduled tasks failed. Please try again later.', 'wpvivid');
                        echo json_encode($ret);
                        die();
                    }
                }
                else
                {
                    if(wp_get_schedule($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id'])))
                    {
                        wp_clear_scheduled_hook($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        $timestamp = wp_next_scheduled($schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                        wp_unschedule_event($timestamp, $schedules[$schedule_id]['id'], array($schedules[$schedule_id]['id']));
                    }
                }

                WPvivid_Setting::update_option('wpvivid_schedule_addon_setting', $schedules);
                $ret['result']='success';
                echo json_encode($ret);
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

    public function get_schedule_list_page(){
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-schedule');
        try {
            $schedules_list = self::wpvivid_get_schedule_list();

            $page = $_POST['page'];
            $table = new WPvivid_Schedule_List();
            $table->set_schedule_list($schedules_list, $page);
            $table->prepare_items();
            ob_start();
            $table->display();
            $rows = ob_get_clean();
            $ret['result'] = 'success';
            $ret['rows'] = $rows;
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
    /***** schedule ajax end *****/

    public function init_page()
    {
        $remoteslist=WPvivid_Setting::get_all_remote_options();
        $has_remote = false;
        foreach ($remoteslist as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            else{
                $has_remote = true;
            }
        }
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Schedule', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-dashboard">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-calendar-alt wpvivid-dashicons-large wpvivid-dashicons-green"></span><span class="wpvivid-page-title">Backup Schedule</span></p>
                                        <span class="about-description">The page allows you to create general or incremental backup schedules.</span>
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
                                                    <!-- The content you need -->
                                                    <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpvivid-nav-bar wpvivid-clear-float">
                                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                                        <span> In order to ensure that the scheduled tasks can be performed as expected, it is best to complete a manual backup first to ensure that your server has sufficient resources. <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting').'&backup_retain'; ?>" style="text-decoration: none;">A shortcut to backup retention settings.</a></span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $args['span_class']='dashicons dashicons-chart-bar';
                                    $args['span_style']='color:red;padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='display:block;';
                                    $args['is_parent_tab']=0;
                                    $tabs['incremental_backup_schedules']['title']='Incremental Backup';
                                    $tabs['incremental_backup_schedules']['slug']='incremental_backup_schedules';
                                    $tabs['incremental_backup_schedules']['callback']=array($this, 'output_incremental_backup');
                                    $tabs['incremental_backup_schedules']['args']=$args;

                                    $args['span_class']='dashicons dashicons-backup';
                                    $args['span_style']='color:#007cba; padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='';
                                    $args['is_parent_tab']=0;
                                    $tabs['full_backup']['title']='General Backup';
                                    $tabs['full_backup']['slug']='full_backup';
                                    $tabs['full_backup']['callback']=array($this, 'output_full_backup');
                                    $tabs['full_backup']['args']=$args;

                                    /*$args['span_class']='dashicons dashicons-format-gallery';
                                    $args['span_style']='color:#8bc34a;padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='';
                                    $args['is_parent_tab']=0;
                                    $tabs['imgoptim']['title']='Image Optimization';
                                    $tabs['imgoptim']['slug']='imgoptim';
                                    $tabs['imgoptim']['callback']=array($this, 'output_image_optimization');
                                    $tabs['imgoptim']['args']=$args;

                                    $args['span_class']='dashicons dashicons-trash';
                                    $args['span_style']='color:orange;padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='';
                                    $args['is_parent_tab']=0;
                                    $tabs['unused_image_cleaner']['title']='Unused Image Cleaner';
                                    $tabs['unused_image_cleaner']['slug']='unused_image_cleaner';
                                    $tabs['unused_image_cleaner']['callback']=array($this, 'output_unused_image_cleaner');
                                    $tabs['unused_image_cleaner']['args']=$args;*/

                                    $args['span_class']='dashicons dashicons-backup';
                                    $args['span_style']='color:#007cba; padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='';
                                    $args['is_parent_tab']=0;
                                    $args['can_delete']=1;
                                    $args['hide']=1;
                                    $tabs['update_full_backup']['title']='Update Full Backup';
                                    $tabs['update_full_backup']['slug']='update_full_backup';
                                    $tabs['update_full_backup']['callback']=array($this, 'output_update_full_backup');
                                    $tabs['update_full_backup']['args']=$args;

                                    $tabs=apply_filters('wpvivid_schedule_tabs',$tabs);
                                    foreach ($tabs as $key=>$tab)
                                    {
                                        $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                                    }

                                    $this->main_tab->display();
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
            jQuery(document).ready(function ()
            {
                <?php
                $enable_schedules=apply_filters('wpvivid_get_general_schedule_status',false);
                if(isset($_REQUEST['incremental_backup_schedules']))
                {
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'incremental_backup_schedules', 'incremental_backup_schedules' ]);
                <?php
                }
                else if(isset($_REQUEST['full_backup'])||$enable_schedules)
                {
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'full_backup', 'full_backup' ]);
                <?php
                }
                ?>

                var has_remote = '<?php echo $has_remote; ?>';
                jQuery(document).on('wpvivid-has-default-remote', function(event, type) {
                    wpvivid_check_has_default_remote(has_remote, type);
                });

                function wpvivid_check_has_default_remote(has_remote, type){
                    if(!has_remote)
                    {
                        var descript = 'There is no default remote storage configured. Please set it up first.';
                        var ret = confirm(descript);
                        if(ret === true){
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote'); ?>';
                        }
                        jQuery('input:radio[option=incremental_backup][name=save_local_remote][value=local]').prop('checked', true);
                        jQuery('input:radio[option=schedule][name=schedule_save_local_remote][value=local]').prop('checked', true);
                        jQuery('input:radio[option=update_schedule_backup][name=update_schedule_backup_save_local_remote][value=local]').prop('checked', true);
                    }
                    else{
                        if(type === 'create_schedule'){
                            jQuery('#wpvivid_create_schedule_backup_remote_selector_part').show();
                        }
                        else if(type === 'update_schedule'){
                            jQuery('#wpvivid_update_schedule_backup_remote_selector_part').show();
                        }
                        else if(type === 'incremental_schedule'){
                            jQuery('#wpvivid_incremental_backup_remote_selector_part').show();
                        }
                    }
                }
            });
        </script>
        <?php
    }

    public function output_incremental_backup()
    {
        ?>

        <?php
    }

    public static function wpvivid_get_schedule_list(){
        $default = array();
        $schedules = get_option('wpvivid_schedule_addon_setting', $default);
        $schedules_list = array();
        if(!empty($schedules)){
            foreach ($schedules as $schedule)
            {
                if (!isset($schedule['week']))
                {
                    $schedule['week'] = 'N/A';
                }
                $recurrence = wp_get_schedules();
                if (isset($recurrence[$schedule['type']])) {
                    $schedule_type = $recurrence[$schedule['type']]['display'];
                    if ($schedule_type === 'Weekly')
                    {
                        if (isset($schedule['week']))
                        {
                            if ($schedule['week'] === 'sun')
                            {
                                $schedule_type = $schedule_type . '-Sunday';
                            } else if ($schedule['week'] === 'mon')
                            {
                                $schedule_type = $schedule_type . '-Monday';
                            } else if ($schedule['week'] === 'tue')
                            {
                                $schedule_type = $schedule_type . '-Tuesday';
                            } else if ($schedule['week'] === 'wed') {

                                $schedule_type = $schedule_type . '-Wednesday';
                            } else if ($schedule['week'] === 'thu')
                            {
                                $schedule_type = $schedule_type . '-Thursday';
                            } else if ($schedule['week'] === 'fri')
                            {
                                $schedule_type = $schedule_type . '-Friday';
                            } else if ($schedule['week'] === 'sat')
                            {
                                $schedule_type = $schedule_type . '-Saturday';
                            }
                        }
                    }
                } else {
                    $schedule_type = 'not found';
                }
                $schedule['backup_cycles'] = $schedule_type;

                if (isset($schedule['last_backup_time'])) {
                    $offset=get_option('gmt_offset');
                    $localtime = $schedule['last_backup_time'] + $offset * 60 * 60;
                    $last_backup_time = date("H:i:s - F-d-Y ", $localtime);
                } else {
                    $last_backup_time = 'N/A';
                }
                $schedule['last_backup_time'] = $last_backup_time;

                if ($schedule['status'] == 'Active') {
                    $timestamp = wp_next_scheduled($schedule['id'], array($schedule['id']));
                    if($timestamp===false)
                    {
                        if(isset($schedule['week']))
                        {
                            $time['start_time']['week']=$schedule['week'];
                        }
                        else
                            $time['start_time']['week']='mon';

                        if(isset($schedule['day']))
                        {
                            $schedule_data['day']=$schedule['day'];
                        }
                        else
                            $time['start_time']['day']='01';

                        if(isset($schedule['current_day']))
                        {
                            $schedule_data['current_day']=$schedule['current_day'];
                        }
                        else
                            $time['start_time']['current_day']="00:00";

                        $timestamp=WPvivid_Schedule_addon::get_start_time($time);
                        $schedule_data['start_time']=$timestamp;

                        wp_schedule_event($schedule['start_time'], $schedule['type'], $schedule['id'],array($schedule['id']));

                        $offset = get_option('gmt_offset');
                        $localtime = $schedule['start_time'] + $offset * 60 * 60;
                        $next_start = date("H:i:s - F-d-Y ", $localtime);
                    }
                    else {
                        $schedule['next_start'] = $timestamp;
                        $offset = get_option('gmt_offset');
                        $localtime = $schedule['next_start'] + $offset * 60 * 60;
                        $next_start = date("H:i:s - F-d-Y ", $localtime);
                    }
                } else {
                    $next_start = 'N/A';
                }
                $schedule['next_start_time'] = $next_start;

                if (isset($schedule['backup']['backup_files'])) {
                    $backup_type = $schedule['backup']['backup_files'];
                    if ($backup_type === 'files+db')
                    {
                        $backup_type = 'Database + Files (WordPress Files)';
                    } else if ($backup_type === 'files')
                    {
                        $backup_type = 'WordPress Files (Exclude Database)';
                    } else if ($backup_type === 'db')
                    {
                        $backup_type = 'Only Database';
                    }
                } else {
                    if (isset($schedule['backup']['backup_select'])) {
                        $has_db=false;
                        $has_file=false;
                        if($schedule['backup']['backup_select']['db'] == '1' || $schedule['backup']['backup_select']['additional_db'] == '1'){
                            $has_db = true;
                        }
                        if($schedule['backup']['backup_select']['themes'] == '1' || $schedule['backup']['backup_select']['plugin'] == '1' ||
                            $schedule['backup']['backup_select']['uploads'] == '1' || $schedule['backup']['backup_select']['content'] == '1' ||
                            $schedule['backup']['backup_select']['core'] == '1' || $schedule['backup']['backup_select']['other'] == '1'){
                            $has_file = true;
                        }
                        if($has_db && $has_file){
                            $backup_type = 'Database + Files (WordPress Files)';
                        }
                        else if($has_db){
                            $backup_type = 'Only Database';
                        }
                        else{
                            $backup_type = 'WordPress Files (Exclude Database)';
                        }
                    }
                    else{
                        $backup_type = '';
                    }
                }
                $schedule['schedule_backup_type'] = $backup_type;

                if (isset($schedule['backup']['local'])) {
                    if ($schedule['backup']['local'] == '1')
                    {
                        $backup_to = 'Localhost';
                    } else {
                        $backup_to = 'Remote';
                    }
                } else {
                    $backup_to = 'Localhost';
                }
                $schedule['schedule_backup_to'] = $backup_to;

                $schedules_list[] = $schedule;
            }
        }
        /*uasort($schedules_list, function ($a, $b)
        {
            $a_timestamp = wp_next_scheduled($a['id'], array($a['id']));
            if($a_timestamp != false) {
                $a['next_start'] = $a_timestamp;
            }
            else{
                $a['next_start'] = 0;
            }
            $b_timestamp = wp_next_scheduled($b['id'], array($b['id']));
            if($b_timestamp != false){
                $b['next_start'] = $b_timestamp;
            }
            else{
                $b['next_start'] = 0;
            }
            if ($a['next_start'] > $b['next_start'])
            {
                return 1;
            } else if ($a['next_start'] === $b['next_start'])
            {
                return 0;
            } else {
                return -1;
            }
        });*/
        return $schedules_list;
    }

    public function output_full_backup()
    {
        $first_auto_set_schedule = get_option('wpvivid_first_auto_set_schedule', '1');
        if($first_auto_set_schedule === '1')
        {
            update_option('wpvivid_first_auto_set_schedule', '0', 'no');
            $general_schedules = get_option('wpvivid_schedule_addon_setting', array());
            if(empty($general_schedules))
            {
                $schedule_file_db = array();
                $schedule_file_db['schedule_backup_backup_type']='files+db';
                $schedule_file_db['recurrence']='wpvivid_daily';
                $schedule_file_db['week']='mon';
                $schedule_file_db['day']='1';
                $schedule_file_db['current_day_hour']='00';
                $schedule_file_db['current_day_minute']='00';
                $schedule_file_db['save_local_remote']='local';
                $schedule_file_db['status']='InActive';
                $schedule_file_db['backup_prefix']='';
                $ret = $this->check_schedule_option($schedule_file_db);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                }
                else
                {
                    $this->add_schedule($ret['schedule']);
                }

                $schedule_file = array();
                $schedule_file['schedule_backup_backup_type']='files';
                $schedule_file['recurrence']='wpvivid_daily';
                $schedule_file['week']='mon';
                $schedule_file['day']='1';
                $schedule_file['current_day_hour']='05';
                $schedule_file['current_day_minute']='00';
                $schedule_file['save_local_remote']='local';
                $schedule_file['status']='InActive';
                $schedule_file['backup_prefix']='';
                $ret = $this->check_schedule_option($schedule_file);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                }
                else
                {
                    $this->add_schedule($ret['schedule']);
                }

                $schedule_db = array();
                $schedule_db['schedule_backup_backup_type']='db';
                $schedule_db['recurrence']='wpvivid_daily';
                $schedule_db['week']='mon';
                $schedule_db['day']='1';
                $schedule_db['current_day_hour']='10';
                $schedule_db['current_day_minute']='00';
                $schedule_db['save_local_remote']='local';
                $schedule_db['status']='InActive';
                $schedule_db['backup_prefix']='';
                $ret = $this->check_schedule_option($schedule_db);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                }
                else
                {
                    $this->add_schedule($ret['schedule']);
                }
            }
        }
        //
        $default = false;
        $schedules = get_option('wpvivid_schedule_addon_setting', $default);
        $local_time=date( 'H:i:s - F-d-Y ', current_time( 'timestamp', 0 ) );
        $utc_time=date( 'H:i:s - F-d-Y ', time() );
        $offset=get_option('gmt_offset');

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
            $default_prefix = $parse['host'].$path;
        }
        else{
            $default_prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
        }

        $enable_schedules_backups=apply_filters('wpvivid_get_general_schedule_status',false);
        if($enable_schedules_backups){
            $schedule_enable_status = 'checked';
        }
        else{
            $schedule_enable_status = '';
        }

        ?>
        <!--<div class="wpvivid-one-coloum" style="padding-top:0em;padding-left:0em;">
            <div class="wpvivid-two-col">
                <label class="wpvivid-switch">
                    <input type="checkbox" id="wpvivid_schedule_backup_switch" <?php esc_attr_e($schedule_enable_status); ?>>
                    <span class="wpvivid-slider wpvivid-round"></span>
                </label>
                <label>
                    <span>Enable General Backup Schedule</span>
                </label>
            </div>
            <div class="wpvivid-two-col">
                <span style="float:right;"></span>
            </div>
        </div>-->

        <div style="padding-bottom:1em;">
            <span>Enable one or more backup schedules, you have 3 pre-set schedules.</span>
        </div>

        <div class="wpvivid-one-coloum" style="padding: 0em;">
            <div id="wpvivid_schedule_create_notice"></div>
            <div id="wpvivid_schedule_save_notice"></div>
        </div>

        <div id="wpvivid_schedule_list">
            <?php
            $schedules_list = self::wpvivid_get_schedule_list();
            $table=new WPvivid_Schedule_List();
            $table->set_schedule_list($schedules_list);
            $table->prepare_items();
            $table->display();
            ?>
        </div>

        <div id="wpvivid_create_schedule_part" style="padding: 10px 0 10px 0;">
            <span><input class="button-primary" id="wpvivid_create_schedule_btn" type="submit" value="Create a job" /></span>
            <?php
            /*if(!empty($schedules_list)){
                ?>
                <span>
                    <select id="wpvivid_select_schedule_status">
                        <option value="activate" selected="selected">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="delete">Delete</option>
                    </select>
                </span>
                <span>
                    <input id="wpvivid_click_save_schedule_changed" type="submit" class="button action" value="Apply" />
                </span>
                <span id="wpvivid_create_schedule_btn" style="padding:1em 1em 1em 0;">or, <a href="#">create a new schedule</a></span>
                <?php
            }
            else{
                ?>
                <span><input class="button-primary" id="wpvivid_create_schedule_btn" type="submit" value="Create a new schedule" /></span>
                <?php
            }*/
            ?>
        </div>

        <div id="wpvivid_schedule_backup_deploy" style="display: none;">
            <table class="wp-list-table widefat plugin">
                <thead>
                <tr>
                    <th></th>
                    <th class="manage-column column-primary"><strong>Local Time </strong><a href="<?php esc_attr_e(admin_url().'options-general.php'); ?>">(Timezone Setting)</a></th>
                    <th class="manage-column column-primary"><strong>Universal Time (UTC)</strong></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th><strong>Current Time</strong></th>
                    <td>
                        <div>
                            <span><?php _e($local_time); ?></span>
                        </div>
                    </td>
                    <td>
                        <div>
                            <span><?php _e($utc_time); ?></span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><strong>Schedule Start Time</strong></th>
                    <td>
                        <span>
                            <select id="wpvivid_schedule_backup_rate_select" value="wpvivid_daily" option="schedule_backup" name="recurrence" onchange="click_select_rate('schedule_backup');">
                                <option value="wpvivid_hourly">Every hour</option>
                                <option value="wpvivid_2hours">Every 2 hours</option>
                                <option value="wpvivid_4hours">Every 4 hours</option>
                                <option value="wpvivid_8hours">Every 8 hours</option>
                                <option value="wpvivid_12hours">Every 12 hours</option>
                                <option value="wpvivid_daily">Daily</option>
                                <option value="wpvivid_2days">Every 2 days</option>
                                <option value="wpvivid_weekly" selected="selected">Weekly</option>
                                <option value="wpvivid_fortnightly">Fortnightly</option>
                                <option value="wpvivid_monthly">Every 30 days</option>
                            </select>
                        </span>
                        <span>
                            <select id="wpvivid_schedule_backup_start_week_select" option="schedule_backup" name="week">
                                <option value="sun">Sunday</option>
                                <option value="mon" selected="selected">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                            </select>
                        </span>
                        <span>
                            <span>
                                <select id="wpvivid_schedule_backup_start_day_select" option="schedule_backup" name="day" style="display: none;">
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
                        </span>
                        <span>
                            <span>
                                <select id="wpvivid_schedule_backup_hour" option="schedule_backup" name="current_day_hour" onchange="wpvivid_sync_time(false);">
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
                                <select id="wpvivid_schedule_backup_minute" option="schedule_backup" name="current_day_minute" onchange="wpvivid_sync_time(false);">
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
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <div>
                            <div style="float: left; margin-right: 10px;">
                                <span id="wpvivid_utc_time">00:00</span>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="3">
                        <small>
                            <span>The schedule will be performed at [(local time)</span><span id="wpvivid_local_time" style="margin-right: 0;">00:00</span><span>] [UTC</span><span id="wpvivid_utc_time_2" style="margin-right: 0;">00:00</span><span>] [Schedule Cycles:</span><span id="wpvivid_schedule_backup_cycles" style="margin-right: 0;">Daily</span>]
                        </small>
                    </th>
                </tr>
                </tfoot>
            </table>

            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-top:1em;">


                <div style="">
                    <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                    <div style="padding-left:2em;">
                        <label class="">
                            <input type="radio" option="schedule" name="schedule_save_local_remote" value="local" checked="checked" />Backup to localhost
                        </label>
                        <span style="padding: 0 1em;"></span>
                        <?php
                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-schedule-backup-remote'))
                        {
                            ?>
                            <label class="">
                                <input type="radio" option="schedule" name="schedule_save_local_remote" value="remote" />Backup to remote storage
                            </label>
                            <span style="padding: 0 0.2em;"></span>

                            <span id="wpvivid_create_schedule_backup_remote_selector_part" style="display: none;">
                                <select id="wpvivid_create_schedule_backup_remote_selector">
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
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <div style="">
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                    <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <?php
                        $fieldset_style = '';
                        ?>
                        <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                            <?php
                            $html = '';
                            echo apply_filters('wpvivid_add_schedule_backup_type_addon', $html, 'schedule_backup');
                            ?>
                        </fieldset>
                        <?php
                        ?>
                    </div>
                </div>

                <div id="wpvivid_custom_schedule_backup" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $this->schedule_backup = new WPvivid_Custom_Backup_Manager('wpvivid_custom_schedule_backup','schedule_backup', '1','0');
                        //$this->schedule_backup->output_custom_backup_table();
                        $this->schedule_backup->output_custom_backup_db_table();
                        $this->schedule_backup->output_custom_backup_file_table();
                        ?>
                    </div>
                </div>

                <!--Advanced Option (Exclude)-->
                <div id="wpvivid_custom_schedule_advanced_option">
                    <?php
                    $this->schedule_backup->wpvivid_set_advanced_id('wpvivid_custom_schedule_advanced_option');
                    $this->schedule_backup->output_advanced_option_table();
                    $this->schedule_backup->load_js();
                    ?>
                </div>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="schedule" name="backup_prefix" id="wpvivid_set_schedule_prefix" value="<?php echo $default_prefix;?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php echo $default_prefix;?>">
                    </p>
                </div>
            </div>

            <div class="wpvivid-clear-float" style="padding-top:1em;">
                <label>
                    <input type="checkbox" option="schedule" name="auto_active_schedule" checked />
                    <span>Activate this backup schedule once created</span>
                </label>
            </div>

            <div class="wpvivid-one-coloum wpvivid-clear-float" style="padding-bottom:0;padding-left:0;">
                <input class="button-primary" id="wpvivid_btn_create_general_schedule" type="submit" value="Create Now" onclick="wpvivid_click_create_schedule();" />
            </div>
        </div>


        <script>
            var wpvivid_schedule_backup_table = wpvivid_schedule_backup_table || {};
            wpvivid_schedule_backup_table.init_refresh = false;
            var wpvivid_update_schedule_backup_table = wpvivid_update_schedule_backup_table || {};
            wpvivid_update_schedule_backup_table.init_refresh = false;

            var time_offset='<?php echo $offset ?>';
            var edited_schedule_id='';

            jQuery('#wpvivid_schedule_list').on("click",'.first-page',function() {
                wpvivid_schedule_change_page('first');
            });

            jQuery('#wpvivid_schedule_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_schedule_change_page(page-1);
            });

            jQuery('#wpvivid_schedule_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_schedule_change_page(page+1);
            });

            jQuery('#wpvivid_schedule_list').on("click",'.last-page',function() {
                wpvivid_schedule_change_page('last');
            });

            jQuery('#wpvivid_schedule_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_schedule_change_page(page);
                }
            });

            function wpvivid_schedule_change_page(page){
                var ajax_data = {
                    'action':'wpvivid_get_schedule_list_page',
                    'page': page,
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_schedule_list').html(jsonarray.rows);
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('changing schedule page', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_schedule_list').on("click", '.wpvivid-schedule-run', function(){
                var Obj=jQuery(this);
                var id=Obj.closest('tr').attr('slug');
                wpvivid_run_schedule(id);
            });

            jQuery('#wpvivid_schedule_list').on("click",'.wpvivid-schedule-edit',function() {
                var Obj=jQuery(this);
                var id=Obj.closest('tr').attr('slug');
                wpvivid_open_edit_schedule(id);
            });

            jQuery('#wpvivid_schedule_list').on("click",'.wpvivid-schedule-delete',function() {
                var Obj=jQuery(this);
                var id=Obj.closest('tr').attr('slug');
                wpvivid_delete_schedule(id);
            });

            jQuery('#wpvivid_schedule_list').on("click", '.wpvivid-schedule-on-off-control', function(){
                var Obj=jQuery(this);
                var json = {};
                var schedule_id = '';
                var schedule_status = '';

                schedule_id = Obj.closest('tr').attr('slug');
                if(jQuery(this).prop('checked'))
                {
                    schedule_status = 'Active';
                    var descript = 'Enabling a general backup schedule will disable the incremental backup schedule, if any, are you sure to continue?';
                }
                else
                {
                    schedule_status = 'InActive';
                    var descript = 'Disabling the backup schedule will cause the backup not to run. Are you sure to continue?';
                }

                var ret = confirm(descript);
                if (ret !== true)
                {
                    if(schedule_status === 'Active')
                    {
                        Obj.prop('checked', false);
                    }
                    else
                    {
                        Obj.prop('checked', true);
                    }
                    return;
                }

                json[schedule_id] = schedule_status;
                schedule_status = JSON.stringify(json);

                var ajax_data= {
                    'action': 'wpvivid_save_schedule_status',
                    'schedule_data': schedule_status,
                };
                jQuery('#wpvivid_schedule_save_notice').html('');
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-schedule', 'wpvivid-schedule').'&full_backup'; ?>';
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_schedule_backup_switch').click(function(){
                if(jQuery('#wpvivid_schedule_backup_switch').prop('checked')){
                    var enable = 1;
                    var descript = 'Enabling full backup schedule will disable incremental backup schedule, if any, are you sure to continue?';
                }
                else{
                    var enable = 0;
                    var descript = 'Disabling full backup schedule will cause the scheduled full backup tasks to not run. Are you sure to continue?';
                }

                var ret = confirm(descript);
                if (ret !== true) {
                    if(enable === 1){
                        jQuery('#wpvivid_schedule_backup_switch').prop('checked', false);
                    }
                    else{
                        jQuery('#wpvivid_schedule_backup_switch').prop('checked', true);
                    }
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_enable_schedule_backup_addon',
                    'enable': enable
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-schedule', 'wpvivid-schedule').'&full_backup'; ?>';
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

            function wpvivid_run_schedule(id)
            {
                var descript = 'Are you sure to trigger this schedule now?';
                var ret = confirm(descript);
                if(ret === true)
                {
                    var ajax_data = {
                        'action': 'wpvivid_run_schedule_addon',
                        'id': id
                    };
                    jQuery('#wpvivid_schedule_save_notice').html('');
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#wpvivid_schedule_save_notice').html(jsonarray.notice);
                            }
                            else
                            {
                                jQuery('#wpvivid_schedule_save_notice').html(jsonarray.error);
                            }
                        }
                        catch (err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = wpvivid_output_ajaxerror('deleting schedule', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }

            function wpvivid_open_edit_schedule(id) {
                edited_schedule_id = id;
                var ajax_data = {
                    'action': 'wpvivid_edit_schedule_addon',
                    'id': id
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'update_full_backup', 'full_backup' ]);

                            jQuery('select[option=update_schedule_backup][name=current_day_hour]').each(function()
                            {
                                jQuery(this).val(jsonarray.schedule_info.hours);
                            });
                            jQuery('select[option=update_schedule_backup][name=current_day_minute]').each(function(){
                                jQuery(this).val(jsonarray.schedule_info.minute);
                            });
                            wpvivid_sync_time('update');

                            if(jsonarray.schedule_info.type === 'wpvivid_hourly')
                            {
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_hourly');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_2hours')
                            {
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_2hours');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_4hours')
                            {
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_4hours');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_8hours')
                            {
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_8hours');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_12hours')
                            {
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_12hours');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_daily')
                            {
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_daily');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_2days')
                            {
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_2days');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_weekly')
                            {
                                jQuery('#wpvivid_update_schedule_backup_start_week_select').show();
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_weekly');
                                jQuery('#wpvivid_update_schedule_backup_start_week_select').val(jsonarray.schedule_info.week);
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_fortnightly')
                            {
                                jQuery('#wpvivid_update_schedule_backup_start_week_select').show();
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_fortnightly');
                                jQuery('#wpvivid_update_schedule_backup_start_week_select').val(jsonarray.schedule_info.week);
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_monthly')
                            {
                                jQuery('#wpvivid_update_schedule_backup_start_day_select').show();
                                jQuery('#wpvivid_update_schedule_backup_rate_select').val('wpvivid_monthly');
                                jQuery('#wpvivid_update_schedule_backup_start_day_select').val(jsonarray.schedule_info.day);
                            }

                            //jQuery('#wpvivid_update_utc_time').html(jsonarray.schedule_info.current_day);

                            jQuery('#wpvivid_update_schedule_backup_start_week_select').hide();
                            jQuery('#wpvivid_update_schedule_backup_start_day_select').hide();
                            var select_value = jQuery('#wpvivid_update_schedule_backup_rate_select').val();
                            if(select_value === 'wpvivid_weekly' || select_value === 'wpvivid_fortnightly')
                            {
                                jQuery('#wpvivid_update_schedule_backup_start_week_select').show();
                            }
                            else if(select_value === 'wpvivid_monthly'){
                                jQuery('#wpvivid_update_schedule_backup_start_day_select').show();
                            }

                            if(typeof jsonarray.schedule_info.backup.backup_prefix !== 'undefined')
                            {
                                jQuery('input:text[option=update_schedule_backup][name=backup_prefix]').val(jsonarray.schedule_info.backup.backup_prefix);
                            }


                            if(typeof jsonarray.schedule_info.backup.backup_files !== 'undefined') {
                                jQuery('#wpvivid_custom_update_schedule_backup').hide();
                                wpvivid_update_schedule_backup_table.init_refresh = false;
                                if (jsonarray.schedule_info.backup.backup_files == 'files+db') {
                                    jQuery('input[option=update_schedule_backup][name=update_schedule_backup_backup_type][value=\'files+db\']').prop('checked', true);
                                }
                                else if (jsonarray.schedule_info.backup.backup_files == 'custom') {
                                    jQuery('input[option=update_schedule_backup][name=update_schedule_backup_backup_type][value=custom]').prop('checked', true);
                                    jQuery('#wpvivid_custom_update_schedule_backup').show();
                                    wpvivid_update_schedule_backup_table.init_refresh = true;
                                    wpvivid_display_schedule_setting(jsonarray.schedule_info.backup);
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-info').html('');
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-info').html(jsonarray.database_html);
                                }
                                else {
                                    jQuery('input[option=update_schedule_backup][name=update_schedule_backup_backup_type][value=' + jsonarray.schedule_info.backup.backup_files + ']').prop('checked', true);
                                }
                            }
                            else{
                                jQuery('input[option=update_schedule_backup][name=update_schedule_backup_backup_type][value=custom]').prop('checked', true);
                                jQuery('#wpvivid_custom_update_schedule_backup').show();
                                wpvivid_update_schedule_backup_table.init_refresh = true;
                                wpvivid_display_schedule_setting(jsonarray.schedule_info.backup);
                                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-info').html('');
                                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-info').html(jsonarray.database_html);
                            }
                            if(jsonarray.schedule_info.backup.local == 1){
                                jQuery('input[option=update_schedule_backup][name=update_schedule_backup_save_local_remote][value=local]').prop('checked', true);
                                jQuery('#wpvivid_update_schedule_backup_remote_selector_part').hide();
                            }
                            else{
                                jQuery('input[option=update_schedule_backup][name=update_schedule_backup_save_local_remote][value=remote]').prop('checked', true);
                                jQuery('#wpvivid_update_schedule_backup_remote_selector_part').show();
                                if(typeof jsonarray.schedule_info.backup.remote_options !== 'undefined'){
                                    jQuery.each(jsonarray.schedule_info.backup.remote_options, function(remote_id, remote_option){
                                        jQuery('#wpvivid_update_schedule_backup_remote_selector').val(remote_id);
                                    });
                                }
                                else
                                {
                                    jQuery('#wpvivid_update_schedule_backup_remote_selector').val('all');
                                }
                            }

                            if(typeof jsonarray.schedule_info.backup.exclude_files !== 'undefined')
                            {
                                var exclude_list = '';
                                jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.wpvivid-custom-exclude-list').html('');
                                jQuery.each(jsonarray.schedule_info.backup.exclude_files, function(index, value)
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
                                jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.wpvivid-custom-exclude-list').append(exclude_list);
                            }

                            jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.wpvivid-custom-exclude-extension').val('');
                            if(typeof jsonarray.schedule_info.backup.exclude_file_type !== 'undefined')
                            {
                                jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.wpvivid-custom-exclude-extension').val(jsonarray.schedule_info.backup.exclude_file_type);
                            }
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('editing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_delete_schedule(id) {
                var descript = 'Are you sure to remove this schedule?';
                var ret = confirm(descript);
                if(ret === true) {
                    var ajax_data = {
                        'action': 'wpvivid_delete_schedule_addon',
                        'id': id
                    };
                    jQuery('#wpvivid_schedule_save_notice').html('');
                    wpvivid_post_request_addon(ajax_data, function (data) {
                        wpvivid_handle_schedule_info(data);
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = wpvivid_output_ajaxerror('deleting schedule', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }

            function wpvivid_handle_schedule_info(data) {
                try
                {
                    jQuery('#wpvivid_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_schedule_save_notice').html(jsonarray.notice);
                        jQuery('#wpvivid_schedule_list').html(jsonarray.html);
                        jQuery('#wpvivid_backup_schedule_part').html(jsonarray.schedule_part);
                        if(typeof jsonarray.create_part !== 'undefined'){
                            jQuery('#wpvivid_create_schedule_part').html(jsonarray.create_part);
                        }
                    }
                    else {
                        jQuery('#wpvivid_schedule_save_notice').html(jsonarray.notice);
                    }
                }
                catch (err)
                {
                    alert(err);
                }
            }

            jQuery('#wpvivid_create_schedule_part').on('click', '#wpvivid_click_save_schedule_changed', function(){
                var wpvivid_select_schedule_status = jQuery('#wpvivid_select_schedule_status').val();
                if(wpvivid_select_schedule_status == '0'){
                    alert('Please select at least one item to perform this action on.');
                    return;
                }
                else{
                    var json = {};
                    var schedule_id = '';
                    var schedule_status = '';
                    var need_update = false;

                    if(wpvivid_select_schedule_status === 'activate'){
                        schedule_status = 'Active';
                    }
                    else if(wpvivid_select_schedule_status === 'deactivate'){
                        schedule_status = 'InActive';
                    }
                    else{
                        schedule_status = 'Delete';
                    }

                    jQuery('#wpvivid_schedule_list tbody').find('tr').each(function(){
                        if(!jQuery(this).hasClass('no-items')) {
                            if (jQuery(this).children().children().prop('checked')) {
                                need_update = true;
                                schedule_id = jQuery(this).attr('slug');
                                json[schedule_id] = schedule_status;
                            }
                        }
                    });

                    schedule_status = JSON.stringify(json);
                    if(need_update === true){
                        var ajax_data= {
                            'action': 'wpvivid_save_schedule_status',
                            'schedule_data': schedule_status,
                        };
                        jQuery('#wpvivid_schedule_save_notice').html('');
                        wpvivid_post_request_addon(ajax_data, function(data)
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-schedule', 'wpvivid-schedule').'&full_backup'; ?>';
                        }, function(XMLHttpRequest, textStatus, errorThrown)
                        {
                            var error_message = wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            });

            function wpvivid_click_save_schedule_changed() {
                var json = {};
                var schedule_id = '';
                var schedule_status = '';
                var need_update = false;

                jQuery('#wpvivid_schedule_list tbody').find('tr').each(function(){
                    if(!jQuery(this).hasClass('no-items')) {
                        need_update = true;
                        schedule_id = jQuery(this).attr('slug');
                        if (jQuery(this).children().children().prop('checked')) {
                            schedule_status = 'Active';
                        }
                        else {
                            schedule_status = 'InActive';
                        }
                        json[schedule_id] = schedule_status;
                    }
                });

                schedule_status = JSON.stringify(json);
                if(need_update === true){
                    var ajax_data= {
                        'action': 'wpvivid_save_schedule_status',
                        'schedule_data': schedule_status,
                    };
                    jQuery('#wpvivid_schedule_save_notice').html('');
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-schedule', 'wpvivid-schedule').'&full_backup'; ?>';
                    }, function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }

            function click_select_rate(type){
                jQuery('#wpvivid_'+type+'_start_week_select').hide();
                jQuery('#wpvivid_'+type+'_start_day_select').hide();
                var select_value = jQuery('#wpvivid_'+type+'_rate_select').val();
                if(select_value === 'wpvivid_weekly' || select_value === 'wpvivid_fortnightly')
                {
                    jQuery('#wpvivid_'+type+'_start_week_select').show();
                }
                else if(select_value === 'wpvivid_monthly'){
                    jQuery('#wpvivid_'+type+'_start_day_select').show();
                }

                var backup_cycles = 'Daily';
                switch(select_value){
                    case 'wpvivid_hourly':
                        backup_cycles = 'Every hour';
                        break;
                    case 'wpvivid_2hours':
                        backup_cycles = 'Every 2 hours';
                        break;
                    case 'wpvivid_4hours':
                        backup_cycles = 'Every 4 hours';
                        break;
                    case 'wpvivid_8hours':
                        backup_cycles = 'Every 8 hours';
                        break;
                    case 'wpvivid_12hours':
                        backup_cycles = 'Every 12 hours';
                        break;
                    case 'wpvivid_daily':
                        backup_cycles = 'Daily';
                        break;
                    case 'wpvivid_2days':
                        backup_cycles = 'Every 2 days';
                        break;
                    case 'wpvivid_weekly':
                        backup_cycles = 'Weekly';
                        break;
                    case 'wpvivid_fortnightly':
                        backup_cycles = 'Fortnightly';
                        break;
                    case 'wpvivid_monthly':
                        backup_cycles = 'Every 30 days';
                        break;
                    default:
                        backup_cycles = 'Daily';
                        break;
                }
                jQuery('#wpvivid_'+type+'_cycles').html(backup_cycles);
            }

            function wpvivid_sync_time(type) {
                var option_name = 'schedule_backup';
                var local_time_id = 'wpvivid_local_time';
                var utc_time_id = 'wpvivid_utc_time';
                if(type == 'update'){
                    option_name = 'update_schedule_backup';
                    local_time_id = 'wpvivid_update_local_time';
                    utc_time_id = 'wpvivid_update_utc_time';
                }
                var hour='00';
                var minute='00';
                jQuery('select[option='+option_name+'][name=current_day_hour]').each(function()
                {
                    hour=jQuery(this).val();
                });
                jQuery('select[option='+option_name+'][name=current_day_minute]').each(function(){
                    minute=jQuery(this).val();
                });
                var time=hour+":"+minute;

                jQuery('#'+local_time_id).html(time);

                hour=Number(hour)-Number(time_offset);

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

                time=Hours+":"+Minutes;
                jQuery('#'+utc_time_id).html(time);
                jQuery('#'+utc_time_id+'_2').html(time);
            }

            <?php
            $general_setting=WPvivid_Setting::get_setting(true, "");
            if(isset($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui'])){
                if($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui']){
                    $use_new_custom_backup_ui = '1';
                }
                else{
                    $use_new_custom_backup_ui = '0';
                }
            }
            else{
                $use_new_custom_backup_ui = '0';
            }
            ?>
            var use_new_custom_backup_ui = '<?php echo $use_new_custom_backup_ui; ?>';

            function wpvivid_check_backup_option_avail(type){
                if(type === 'schedule_backup'){
                    var parent_id = 'wpvivid_custom_schedule_backup';
                    var option = 'schedule_backup';
                    var name = 'schedule_backup_database';
                }
                else if(type === 'update_schedule_backup'){
                    var parent_id = 'wpvivid_custom_update_schedule_backup';
                    var option = 'update_schedule_backup';
                    var name = 'update_schedule_backup_database';
                }

                var check_status = true;

                //check is backup db or files
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-part').prop('checked')){
                    var has_db_item = false;
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                        has_db_item = true;
                        var has_local_table_item = false;
                        jQuery('#'+parent_id).find('input:checkbox[name='+name+']').each(function(index, value){
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

            function wpvivid_check_additional_folder_valid(type){
                if(type === 'schedule_backup'){
                    var parent_id = 'wpvivid_custom_schedule_backup';
                    var option = 'schedule_backup';
                    var name = 'schedule_backup_backup_type';
                }
                if(use_new_custom_backup_ui == '1'){
                    var check_status = true;
                }
                else{
                    var check_status = false;
                    if(jQuery('input:radio[option='+option+'][name='+name+'][value=custom]').prop('checked')){
                        if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                            jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function(){
                                check_status = true;
                            });
                        }
                        else{
                            check_status = true;
                        }
                        if(check_status === false){
                            alert('Please select at least one item under the additional files/folder option, or deselect the option.');
                        }
                    }
                    else{
                        check_status = true;
                    }
                }
                return check_status;
            }

            function wpvivid_check_additional_db_valid(type){
                if(type === 'schedule_backup'){
                    var parent_id = 'wpvivid_custom_schedule_backup';
                    var option = 'schedule_backup';
                    var name = 'schedule_backup_backup_type';
                }
                if(use_new_custom_backup_ui == '1'){
                    var check_status = true;
                }
                else{
                    var check_status = false;
                    if(jQuery('input:radio[option='+option+'][name='+name+'][value=custom]').prop('checked')){
                        if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                            jQuery('#'+parent_id).find('.wpvivid-additional-database-list div').find('span:eq(2)').each(function(){
                                check_status = true;
                            });
                        }
                        else{
                            check_status = true;
                        }
                        if(check_status === false){
                            alert('Please select at least one item under the additional database option, or deselect the option.');
                        }
                    }
                    else{
                        check_status = true;
                    }
                }
                return check_status;
            }

            function wpvivid_create_custom_setting_ex(custom_type){
                if(custom_type === 'schedule_backup'){
                    var parent_id = 'wpvivid_custom_schedule_backup';
                    var db_name = 'schedule_backup_database';
                }
                else if(custom_type === 'update_schedule_backup'){
                    var parent_id = 'wpvivid_custom_update_schedule_backup';
                    var db_name = 'update_schedule_backup_database';
                }

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
                jQuery('input[name='+db_name+'][type=checkbox]').each(function(index, value){
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

            function wpvivid_create_custom_setting(custom_type){
                if(custom_type === 'schedule_backup'){
                    var parent_id = 'wpvivid_custom_schedule_backup';
                    var db_name = 'schedule_backup_database';
                    var theme_name = 'schedule_backup_themes';
                    var plugin_name = 'schedule_backup_plugins';
                }
                else if(custom_type === 'update_schedule_backup'){
                    var parent_id = 'wpvivid_custom_update_schedule_backup';
                    var db_name = 'update_schedule_backup_database';
                    var theme_name = 'update_schedule_backup_themes';
                    var plugin_name = 'update_schedule_backup_plugins';
                }
                var json = {};
                //core
                json['core_check'] = '0';
                json['core_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                    json['core_check'] = '1';
                }
                //database
                json['database_check'] = '0';
                json['database_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                    json['database_check'] = '1';
                }
                jQuery('input[name='+db_name+'][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['database_list'].push(jQuery(value).val());
                    }
                });
                //themes & plugins
                json['themes_check'] = '0';
                json['plugins_check'] = '0';
                json['themes_list'] = Array();
                json['plugins_list'] = Array();
                json['exclude_themes_folder'] = Array();
                json['exclude_plugins_folder'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-check').prop('checked')){
                    jQuery('input:checkbox[option=themes][name='+theme_name+']').each(function(){
                        if(jQuery(this).prop('checked')){
                            json['themes_check'] = '1';
                        }
                    });
                    jQuery('input[name='+theme_name+'][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['themes_list'].push(jQuery(value).val());
                        }
                    });
                    jQuery('input:checkbox[option=plugins][name='+plugin_name+']').each(function(){
                        if(jQuery(this).prop('checked')){
                            json['plugins_check'] = '1';
                        }
                    });
                    jQuery('input[name='+plugin_name+'][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['plugins_list'].push(jQuery(value).val());
                        }
                    });
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-folder ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['exclude_themes_folder'].push(folder_name);
                });
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-folder ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['exclude_plugins_folder'].push(folder_name);
                });
                //uploads
                json['uploads_check'] = '0';
                json['uploads_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['uploads_list'][folder_name] = {};
                    json['uploads_list'][folder_name]['name'] = folder_name;
                    json['uploads_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                });
                json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                //content
                json['content_check'] = '0';
                json['content_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['content_list'][folder_name] = {};
                    json['content_list'][folder_name]['name'] = folder_name;
                    json['content_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                });
                json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                //additional folder
                json['other_check'] = '0';
                json['other_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['other_check'] = '1';
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['other_list'][folder_name] = {};
                    json['other_list'][folder_name]['name'] = folder_name;
                    json['other_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                });
                json['other_extension'] = jQuery('#'+parent_id).find('.wpvivid-additional-folder-extension').val();
                //additional database
                json['additional_database_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                    json['additional_database_check'] = '1';
                }
                return json;
            }

            function wpvivid_click_create_schedule(){
                var schedule_data = '';
                schedule_data = wpvivid_ajax_data_transfer('schedule_backup');
                schedule_data = JSON.parse(schedule_data);
                var exclude_dirs = wpvivid_get_exclude_json('wpvivid_custom_schedule_advanced_option');
                var custom_option = {
                    'exclude_files': exclude_dirs
                };
                jQuery.extend(schedule_data, custom_option);

                var exclude_file_type = wpvivid_get_exclude_file_type('wpvivid_custom_schedule_advanced_option');
                var exclude_file_type_option = {
                    'exclude_file_type': exclude_file_type
                };
                jQuery.extend(schedule_data, exclude_file_type_option);
                schedule_data = JSON.stringify(schedule_data);
                jQuery('input:radio[option=schedule_backup][name=schedule_backup_backup_type]').each(function ()
                {
                    if (jQuery(this).prop('checked'))
                    {
                        var value = jQuery(this).prop('value');
                        if (value === 'custom')
                        {
                            schedule_data = JSON.parse(schedule_data);
                            var custom_dirs = wpvivid_get_custom_setting_json_ex('wpvivid_custom_schedule_backup');
                            var custom_option = {
                                'custom_dirs': custom_dirs
                            };
                            jQuery.extend(schedule_data, custom_option);
                            schedule_data = JSON.stringify(schedule_data);
                        }
                    }
                });
                jQuery('input:radio[option=schedule][name=schedule_save_local_remote]').each(function ()
                {
                    if (jQuery(this).prop('checked'))
                    {
                        schedule_data = JSON.parse(schedule_data);
                        if (this.value === 'remote')
                        {
                            var remote_id_select = jQuery('#wpvivid_create_schedule_backup_remote_selector').val();
                            var local_remote_option = {
                                'save_local_remote': this.value,
                                'remote_id_select': remote_id_select
                            };
                        }
                        else
                        {
                            var local_remote_option = {
                                'save_local_remote': this.value
                            };
                        }
                        jQuery.extend(schedule_data, local_remote_option);
                        schedule_data = JSON.stringify(schedule_data);
                    }
                });
                //var utc_time = jQuery('#wpvivid_utc_time').html();
                //var arr = new Array();
                //arr = utc_time.split(':');
                schedule_data = JSON.parse(schedule_data);
                //schedule_data['current_day_hour'] = arr[0];
                //schedule_data['current_day_minute'] = arr[1];

                if (jQuery('input:checkbox[option=schedule][name=auto_active_schedule]').prop('checked'))
                {
                    var enable_disable_option = {
                        'status': 'Active'
                    };
                }
                else
                {
                    var enable_disable_option = {
                        'status': 'InActive'
                    };
                }

                jQuery.extend(schedule_data, enable_disable_option);

                var backup_prefix = jQuery('input:text[option=schedule][name=backup_prefix]').val();
                var backup_prefix_option = {
                    'backup_prefix': backup_prefix
                };
                jQuery.extend(schedule_data, backup_prefix_option);
                schedule_data = JSON.stringify(schedule_data);

                wpvivid_set_backup_history(schedule_data);

                var ajax_data = {
                    'action': 'wpvivid_set_schedule_addon',
                    'schedule': schedule_data
                };
                jQuery('#wpvivid_schedule_create_notice').html('');
                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        jQuery('#wpvivid_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#wpvivid_schedule_create_notice').html(jsonarray.notice);
                            jQuery('#wpvivid_schedule_list').html(jsonarray.html);
                            jQuery('#wpvivid_backup_schedule_part').html(jsonarray.schedule_part);
                            jQuery('#wpvivid_schedule_backup_deploy').hide();

                            if (jsonarray.schedule_enable) {
                                jQuery('#wpvivid_schedule_backup_switch').prop('checked', true);
                            }
                            else {
                                jQuery('#wpvivid_schedule_backup_switch').prop('checked', false);
                            }

                            if (jsonarray.incremental_schedule_enable) {
                                jQuery('#wpvivid_incremental_backup_switch').prop('checked', true);
                            }
                            else {
                                jQuery('#wpvivid_incremental_backup_switch').prop('checked', false);
                            }
                        }
                        else {
                            jQuery('#wpvivid_schedule_create_notice').html(jsonarray.notice);
                        }
                        if (typeof jsonarray.create_part !== 'undefined') {
                            jQuery('#wpvivid_create_schedule_part').html(jsonarray.create_part);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                    alert(error_message);
                });



                /*var check_status = wpvivid_check_backup_option_avail('schedule_backup');
                if(check_status) {
                    var schedule_data = '';
                    schedule_data = wpvivid_ajax_data_transfer('schedule_backup');
                    jQuery('input:radio[option=schedule_backup][name=schedule_backup_backup_type]').each(function () {
                        if (jQuery(this).prop('checked')) {
                            var value = jQuery(this).prop('value');
                            if (value === 'custom') {
                                schedule_data = JSON.parse(schedule_data);
                                var custom_dirs = wpvivid_create_custom_setting_ex('schedule_backup');
                                var custom_option = {
                                    'custom_dirs': custom_dirs
                                };
                                jQuery.extend(schedule_data, custom_option);
                                schedule_data = JSON.stringify(schedule_data);
                            }
                        }
                    });

                    jQuery('input:radio[option=schedule][name=schedule_save_local_remote]').each(function () {
                        if (jQuery(this).prop('checked')) {
                            schedule_data = JSON.parse(schedule_data);
                            if (this.value === 'remote') {
                                var remote_id_select = jQuery('#wpvivid_create_schedule_backup_remote_selector').val();
                                var local_remote_option = {
                                    'save_local_remote': this.value,
                                    'remote_id_select': remote_id_select
                                };
                            }
                            else {
                                var local_remote_option = {
                                    'save_local_remote': this.value
                                };
                            }
                            jQuery.extend(schedule_data, local_remote_option);
                            schedule_data = JSON.stringify(schedule_data);
                        }
                    });

                    var utc_time = jQuery('#wpvivid_utc_time').html();
                    var arr = new Array();
                    arr = utc_time.split(':');
                    schedule_data = JSON.parse(schedule_data);
                    schedule_data['current_day_hour'] = arr[0];
                    schedule_data['current_day_minute'] = arr[1];

                    if (jQuery('input:checkbox[option=schedule][name=auto_active_schedule]').prop('checked')) {
                        var enable_disable_option = {
                            'status': 'Active'
                        };
                    }
                    else {
                        var enable_disable_option = {
                            'status': 'InActive'
                        };
                    }

                    jQuery.extend(schedule_data, enable_disable_option);

                    var backup_prefix = jQuery('input:text[option=schedule][name=backup_prefix]').val();
                    var backup_prefix_option = {
                        'backup_prefix': backup_prefix
                    };
                    jQuery.extend(schedule_data, backup_prefix_option);

                    schedule_data = JSON.stringify(schedule_data);
                    var ajax_data = {
                        'action': 'wpvivid_set_schedule_addon',
                        'schedule': schedule_data
                    };
                    jQuery('#wpvivid_schedule_create_notice').html('');
                    wpvivid_post_request_addon(ajax_data, function (data) {
                        try {
                            jQuery('#wpvivid_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#wpvivid_schedule_create_notice').html(jsonarray.notice);
                                jQuery('#wpvivid_schedule_list').html(jsonarray.html);
                                jQuery('#wpvivid_backup_schedule_part').html(jsonarray.schedule_part);
                                jQuery('#wpvivid_schedule_backup_deploy').hide();

                                if (jsonarray.schedule_enable) {
                                    jQuery('#wpvivid_schedule_backup_switch').prop('checked', true);
                                }
                                else {
                                    jQuery('#wpvivid_schedule_backup_switch').prop('checked', false);
                                }

                                if (jsonarray.incremental_schedule_enable) {
                                    jQuery('#wpvivid_incremental_backup_switch').prop('checked', true);
                                }
                                else {
                                    jQuery('#wpvivid_incremental_backup_switch').prop('checked', false);
                                }
                            }
                            else {
                                jQuery('#wpvivid_schedule_create_notice').html(jsonarray.notice);
                            }
                            if (typeof jsonarray.create_part !== 'undefined') {
                                jQuery('#wpvivid_create_schedule_part').html(jsonarray.create_part);
                            }
                        }
                        catch (err) {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                        alert(error_message);
                    });
                }*/
            }

            jQuery('input:radio[option=schedule_backup][name=schedule_backup_backup_type]').click(function(){
                if(this.value === 'custom'){
                    jQuery('#wpvivid_custom_schedule_backup').show();
                    jQuery( document ).trigger( 'wpvivid_refresh_schedule_backup_tables', 'schedule_backup' );
                }
                else{
                    jQuery('#wpvivid_custom_schedule_backup').hide();
                }
            });

            jQuery('input:radio[option=schedule][name=schedule_save_local_remote]').click(function(){
                var value = jQuery(this).val();
                if(value === 'remote'){
                    jQuery( document ).trigger( 'wpvivid-has-default-remote', 'create_schedule' );
                }
                else{
                    jQuery('#wpvivid_create_schedule_backup_remote_selector_part').hide();
                }
            });

            jQuery('#wpvivid_create_schedule_part').on('click', '#wpvivid_create_schedule_btn', function(){
                jQuery('#wpvivid_schedule_backup_deploy').show();
            });

            jQuery('#wpvivid_set_schedule_prefix').on("keyup", function(){
                var manual_prefix = jQuery('#wpvivid_set_schedule_prefix').val();
                if(manual_prefix === ''){
                    manual_prefix = '*';
                    jQuery('#wpvivid_schedule_prefix').html(manual_prefix);
                }
                else{
                    var reg = RegExp(/wpvivid/, 'i');
                    if (manual_prefix.match(reg)) {
                        jQuery('#wpvivid_set_schedule_prefix').val('');
                        jQuery('#wpvivid_schedule_prefix').html('*');
                        alert('You can not use word \'wpvivid\' to comment the backup.');
                    }
                    else{
                        jQuery('#wpvivid_schedule_prefix').html(manual_prefix);
                    }
                }
            });

            function wpvivid_recalc_schedule_backup_size(website_item_arr, custom_option)
            {
                if(website_item_arr.length > 0)
                {
                    console.log(website_item_arr);
                    var website_item = website_item_arr.shift();
                    var ajax_data = {
                        'action': 'wpvivid_recalc_backup_size',
                        'website_item': website_item,
                        'custom_option': custom_option
                    };

                    wpvivid_post_request_addon(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                if(website_item === 'database')
                                {
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-database-size').html(jsonarray.database_size);
                                }
                                if(website_item === 'core')
                                {
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-core-size').html(jsonarray.core_size);
                                }
                                if(website_item === 'content')
                                {
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-content-size').html(jsonarray.content_size);
                                }
                                if(website_item === 'themes')
                                {
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-themes-size').html(jsonarray.themes_size);
                                }
                                if(website_item === 'plugins')
                                {
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-plugins-size').html(jsonarray.plugins_size);
                                }
                                if(website_item === 'uploads')
                                {
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-uploads-size').html(jsonarray.uploads_size);
                                }
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-additional-folder-size').html(jsonarray.additional_size);
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-total-file-size').html(jsonarray.total_file_size);
                                    jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                                    jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_schedule_backup_size(website_item_arr, custom_option);
                            }
                            else
                            {
                                alert(jsonarray.error);
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_schedule_backup_size(website_item_arr, custom_option);
                            }
                        }
                        catch (err) {
                            alert(err);
                            if(website_item === 'additional_folder')
                            {
                                jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                            wpvivid_recalc_schedule_backup_size(website_item_arr, custom_option);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        if(website_item === 'additional_folder')
                        {
                            jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                        wpvivid_recalc_schedule_backup_size(website_item_arr, custom_option);
                    });
                }
            }

            jQuery('#wpvivid_recalc_schedule_backup_size').click(function(){
                var custom_dirs = wpvivid_create_custom_setting_ex('schedule_backup');
                var custom_option = {
                    'custom_dirs': custom_dirs
                };
                var custom_option = JSON.stringify(custom_option);

                jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-database-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-core-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-themes-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-plugins-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-uploads-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-content-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-additional-folder-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-total-file-size').html('calculating');
                jQuery('#wpvivid_custom_schedule_backup').find('.wpvivid-total-exclude-file-size').html('calculating');

                var website_item_arr = new Array('database', 'core', 'content', 'themes', 'plugins', 'uploads', 'additional_folder');

                wpvivid_recalc_schedule_backup_size(website_item_arr, custom_option);
            });

            jQuery(document).ready(function () {
                jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});

                wpvivid_sync_time(false);

                var parent_id = 'wpvivid_custom_schedule_backup';
                var type = 'schedule_backup';
                if(!wpvivid_schedule_backup_table.init_refresh){
                    wpvivid_schedule_backup_table.init_refresh = true;
                    wpvivid_refresh_custom_backup_info(parent_id, type);
                    wpvivid_get_website_all_size();
                    jQuery('#'+parent_id).find('.wpvivid-database-loading').addClass('is-active');
                    jQuery('#'+parent_id).find('.wpvivid-themes-plugins-loading').addClass('is-active');
                }

                /*jQuery(document).on('wpvivid_refresh_schedule_backup_tables', function(event, type){
                    var parent_id = 'wpvivid_custom_schedule_backup';
                    if(type === 'schedule_backup'){
                        parent_id = 'wpvivid_custom_schedule_backup';
                    }
                    event.stopPropagation();
                    if(!wpvivid_schedule_backup_table.init_refresh){
                        wpvivid_schedule_backup_table.init_refresh = true;
                        wpvivid_refresh_custom_backup_info(parent_id, type);
                        jQuery('#'+parent_id).find('.wpvivid-database-loading').addClass('is-active');
                        jQuery('#'+parent_id).find('.wpvivid-themes-plugins-loading').addClass('is-active');
                    }
                });*/

                var parent_id = 'wpvivid_custom_update_schedule_backup';
                var type = 'update_schedule_backup';
                if(!wpvivid_update_schedule_backup_table.init_refresh){
                    wpvivid_update_schedule_backup_table.init_refresh = true;
                    wpvivid_refresh_custom_backup_info(parent_id, type);
                    jQuery('#'+parent_id).find('.wpvivid-database-loading').addClass('is-active');
                    jQuery('#'+parent_id).find('.wpvivid-themes-plugins-loading').addClass('is-active');
                }

                /*jQuery(document).on('wpvivid_refresh_update_schedule_backup_tables', function(event, type){
                    var parent_id = 'wpvivid_custom_update_schedule_backup';
                    if(type === 'update_schedule_backup'){
                        parent_id = 'wpvivid_custom_update_schedule_backup';
                    }
                    event.stopPropagation();
                    if(!wpvivid_update_schedule_backup_table.init_refresh){
                        wpvivid_update_schedule_backup_table.init_refresh = true;
                        wpvivid_refresh_custom_backup_info(parent_id, type);
                        jQuery('#'+parent_id).find('.wpvivid-database-loading').addClass('is-active');
                        jQuery('#'+parent_id).find('.wpvivid-themes-plugins-loading').addClass('is-active');
                    }
                });*/
            });
        </script>
        <?php
    }

    public function output_image_optimization()
    {
        ?>
        <h3>Coming Soon...</h3>
        <?php
    }

    public function output_unused_image_cleaner()
    {
        ?>
        <h3>Coming Soon...</h3>
        <?php
    }

    public function output_update_full_backup()
    {
        $local_time=date( 'H:i:s - F-d-Y ', current_time( 'timestamp', 0 ) );
        $utc_time=date( 'H:i:s - F-d-Y ', time() );
        $offset=get_option('gmt_offset');

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
            $default_prefix = $parse['host'].$path;
        }
        else{
            $default_prefix = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
        }
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-bottom:1em;">
            <table class="wp-list-table widefat plugin">
                <thead>
                <tr>
                    <th></th>
                    <th class="manage-column column-primary"><strong>Local Time </strong><a href="<?php esc_attr_e(admin_url().'options-general.php'); ?>">(Timezone Setting)</a></th>
                    <th class="manage-column column-primary"><strong>Universal Time (UTC)</strong></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th><strong>Current Time</strong></th>
                    <td>
                        <div>
                            <span><?php _e($local_time); ?></span>
                        </div>
                    </td>
                    <td>
                        <div>
                            <span><?php _e($utc_time); ?></span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><strong>Schedule Start Time</strong></th>
                    <td>
                        <span>
                            <select id="wpvivid_update_schedule_backup_rate_select" option="update_schedule_backup" name="recurrence" onchange="click_select_rate('update_schedule_backup');">
                                <option value="wpvivid_hourly">Every hour</option>
                                <option value="wpvivid_2hours">Every 2 hours</option>
                                <option value="wpvivid_4hours">Every 4 hours</option>
                                <option value="wpvivid_8hours">Every 8 hours</option>
                                <option value="wpvivid_12hours">Every 12 hours</option>
                                <option value="wpvivid_daily">Daily</option>
                                <option value="wpvivid_2days">Every 2 days</option>
                                <option value="wpvivid_weekly" selected="selected">Weekly</option>
                                <option value="wpvivid_fortnightly">Fortnightly</option>
                                <option value="wpvivid_monthly">Every 30 days</option>
                            </select>
                        </span>
                        <span>
                            <select id="wpvivid_update_schedule_backup_start_week_select" option="update_schedule_backup" name="week">
                                <option value="sun">Sunday</option>
                                <option value="mon" selected="selected">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                            </select>
                        </span>
                        <span>
                            <span>
                                <select id="wpvivid_update_schedule_backup_start_day_select" option="update_schedule_backup" name="day" style="display: none;">
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
                        </span>
                        <span>
                            <span>
                                <select id="wpvivid_update_schedule_backup_hour" option="update_schedule_backup" name="current_day_hour" onchange="wpvivid_sync_time('update');">
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
                                <select id="wpvivid_update_schedule_backup_minute" option="update_schedule_backup" name="current_day_minute" onchange="wpvivid_sync_time('update');">
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
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <div>
                            <div style="float: left; margin-right: 10px;">
                                <span id="wpvivid_update_utc_time">00:00</span>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="3">
                        <small>
                            <span>The schedule will be performed at [(local time)</span><span id="wpvivid_update_local_time" style="margin-right: 0;">00:00</span><span>] [UTC</span><span id="wpvivid_update_utc_time_2" style="margin-right: 0;">00:00</span><span>] [Schedule Cycles:</span><span id="wpvivid_update_schedule_backup_cycles" style="margin-right: 0;">Daily</span>]
                        </small>
                    </th>
                </tr>
                </tfoot>
            </table>

            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-top:1em;">

                <div style="">
                    <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                    <div style="padding-left:2em;">
                        <label class="">
                            <input type="radio" option="update_schedule_backup" name="update_schedule_backup_save_local_remote" value="local" checked="checked" />Backup to localhost
                        </label>
                        <span style="padding: 0 1em;"></span>
                        <?php
                        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-schedule-backup-remote'))
                        {
                            ?>
                            <label class="">
                                <input type="radio" option="update_schedule_backup" name="update_schedule_backup_save_local_remote" value="remote" />Backup to remote storage
                            </label>
                            <span style="padding: 0 0.2em;"></span>

                            <span id="wpvivid_update_schedule_backup_remote_selector_part" style="display: none;">
                                <select id="wpvivid_update_schedule_backup_remote_selector">
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
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <div style="">
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                    <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <?php
                        $fieldset_style = '';
                        ?>
                        <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                            <?php
                            $html = '';
                            echo apply_filters('wpvivid_add_schedule_backup_type_addon', $html, 'update_schedule_backup');
                            ?>
                        </fieldset>
                        <?php
                        ?>
                    </div>
                </div>

                <div id="wpvivid_custom_update_schedule_backup" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $this->update_schedule_backup = new WPvivid_Custom_Backup_Manager('wpvivid_custom_update_schedule_backup','update_schedule_backup', '0', '0');
                        //$this->update_schedule_backup->output_custom_backup_table();
                        $this->update_schedule_backup->output_custom_backup_db_table();
                        $this->update_schedule_backup->output_custom_backup_file_table();
                        ?>
                    </div>
                </div>
                <!--Advanced Option (Exclude)-->
                <div id="wpvivid_custom_update_schedule_advanced_option">
                    <?php
                    $this->update_schedule_backup->wpvivid_set_advanced_id('wpvivid_custom_update_schedule_advanced_option');
                    $this->update_schedule_backup->output_advanced_option_table();
                    $this->update_schedule_backup->load_js();
                    ?>
                </div>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="update_schedule_backup" name="backup_prefix" id="wpvivid_set_update_schedule_prefix" value="<?php echo $default_prefix;?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php echo $default_prefix;?>">
                    </p>
                </div>
            </div>

            <div class="wpvivid-one-coloum wpvivid-clear-float" style="padding-bottom:0;padding-left:0;">
                <input class="button-primary" id="wpvivid_btn_update_general_schedule" type="submit" value="Update Schedule" onclick="wpvivid_click_update_schedule();" />
            </div>
        </div>
        <script>
            <?php
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
            var path_arr = {};
            path_arr['core'] = '<?php echo $home_path; ?>';
            path_arr['content'] = '<?php echo $content_path; ?>';
            path_arr['uploads'] = '<?php echo $uploads_path; ?>';
            path_arr['themes'] = '<?php echo $themes_path; ?>';
            path_arr['plugins'] = '<?php echo $plugins_path; ?>';

            function wpvivid_display_schedule_setting(backupinfo) {
                if (backupinfo.backup_files == 'files+db') {
                    var core_check = true;
                    var database_check = true;
                    var themes_check = true;
                    var plugin_check = true;
                    var uploads_check = true;
                    var content_check = true;
                    var other_check = false;
                    var additional_db = false;
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-core-check').prop('checked', core_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-check').prop('checked', database_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-themes-check').prop('checked', themes_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-plugins-check').prop('checked', plugin_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-uploads-check').prop('checked', uploads_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-content-check').prop('checked', content_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-folder-check').prop('checked', other_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-database-check').prop('checked', additional_db);
                }
                else if(backupinfo.backup_files == 'files'){
                    var core_check = true;
                    var database_check = false;
                    var themes_check = true;
                    var plugin_check = true;
                    var uploads_check = true;
                    var content_check = true;
                    var other_check = false;
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-core-check').prop('checked', core_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-check').prop('checked', database_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-themes-check').prop('checked', themes_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-plugins-check').prop('checked', plugin_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-uploads-check').prop('checked', uploads_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-content-check').prop('checked', content_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-folder-check').prop('checked', other_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-database-check').prop('checked', additional_db);
                }
                else if(backupinfo.backup_files == 'db'){
                    var core_check = false;
                    var database_check = true;
                    var themes_check = false;
                    var plugin_check = false;
                    var uploads_check = false;
                    var content_check = false;
                    var other_check = false;
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-core-check').prop('checked', core_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-check').prop('checked', database_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-themes-check').prop('checked', themes_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-plugins-check').prop('checked', plugin_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-uploads-check').prop('checked', uploads_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-content-check').prop('checked', content_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-folder-check').prop('checked', other_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-database-check').prop('checked', additional_db);
                }
                else{
                    var database_check = true;
                    var additional_db = true;

                    var core_check = true;
                    var content_check = true;
                    var themes_check = true;
                    var plugin_check = true;
                    var uploads_check = true;
                    var other_check = true;

                    var custom_all_check = true;

                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-database-detail').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-themes-plugins-detail').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-uploads-detail').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-content-detail').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-additional-folder-detail').css({'pointer-events': 'auto', 'opacity': '1'});

                    if(backupinfo.custom_dirs.database_check != 1){
                        database_check = false;
                    }
                    if(backupinfo.custom_dirs.additional_database_check != 1){
                        additional_db = false;
                    }
                    else{
                        jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-additional-database-list').html();
                        var html='';
                        jQuery.each(backupinfo.custom_dirs.additional_database_list, function (db_name, db_info)
                        {
                            html+='<div class="wpvivid-text-line" database-name="'+db_name+'" database-host="'+db_info.db_host+'" database-user="'+db_info.db_user+'" database-pass="'+db_info.db_pass+'">' +
                                '<span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-additional-database-remove" database-name="'+db_name+'"></span>' +
                                '<span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue wpvivid-icon-16px-nopointer"></span>' +
                                '<span class="wpvivid-text-line" option="additional_db_custom" name="'+db_name+'">'+db_name+'@'+db_info.db_host+'</span>' +
                                '</div>';
                            jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-additional-database-list').html(html);
                        });
                    }
                    if(backupinfo.custom_dirs.core_check != 1){
                        core_check = false;
                    }
                    if(backupinfo.custom_dirs.content_check != 1){
                        content_check = false;
                    }
                    if(backupinfo.custom_dirs.themes_check != 1){
                        themes_check = false;
                    }
                    if(backupinfo.custom_dirs.plugins_check != 1){
                        plugin_check = false;
                    }
                    if(backupinfo.custom_dirs.uploads_check != 1){
                        uploads_check = false;
                    }
                    if(backupinfo.custom_dirs.other_check != 1){
                        other_check = false;
                    }

                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-database-check').prop('checked', database_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-database-check').prop('checked', additional_db);

                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-core-check').prop('checked', core_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-content-check').prop('checked', content_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-themes-check').prop('checked', themes_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-plugins-check').prop('checked', plugin_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-uploads-check').prop('checked', uploads_check);
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-additional-folder-check').prop('checked', other_check);

                    var include_other = '';
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-include-additional-folder-list').html('');
                    jQuery.each(backupinfo.custom_dirs.other_list, function(index ,value){
                        var type = 'folder';
                        var class_span = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        include_other += "<div class='wpvivid-text-line' type='"+type+"'>" +
                            "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                            "<span class='"+class_span+"'></span>" +
                            "<span class='wpvivid-text-line'>" + value + "</span>" +
                            "</div>";
                    });
                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-custom-include-additional-folder-list').append(include_other);
                }
            }

            function wpvivid_click_update_schedule(){
                var schedule_data = '';
                schedule_data = wpvivid_ajax_data_transfer('update_schedule_backup');
                schedule_data = JSON.parse(schedule_data);
                var exclude_dirs = wpvivid_get_exclude_json('wpvivid_custom_update_schedule_advanced_option');
                var custom_option = {
                    'exclude_files': exclude_dirs
                };
                jQuery.extend(schedule_data, custom_option);

                var exclude_file_type = wpvivid_get_exclude_file_type('wpvivid_custom_update_schedule_advanced_option');
                var exclude_file_type_option = {
                    'exclude_file_type': exclude_file_type
                };
                jQuery.extend(schedule_data, exclude_file_type_option);
                schedule_data = JSON.stringify(schedule_data);

                jQuery('input:radio[option=update_schedule_backup][name=update_schedule_backup_backup_type]').each(function ()
                {
                    if (jQuery(this).prop('checked'))
                    {
                        var value = jQuery(this).prop('value');
                        if (value === 'custom')
                        {
                            schedule_data = JSON.parse(schedule_data);
                            var custom_dirs = wpvivid_get_custom_setting_json_ex('wpvivid_custom_update_schedule_backup');
                            var custom_option = {
                                'custom_dirs': custom_dirs
                            };
                            jQuery.extend(schedule_data, custom_option);
                            schedule_data = JSON.stringify(schedule_data);
                        }
                    }
                });
                jQuery('input:radio[option=update_schedule_backup][name=update_schedule_backup_save_local_remote]').each(function ()
                {
                    if (jQuery(this).prop('checked'))
                    {
                        schedule_data = JSON.parse(schedule_data);
                        if(this.value === 'remote')
                        {
                            var remote_id_select = jQuery('#wpvivid_update_schedule_backup_remote_selector').val();
                            var local_remote_option = {
                                'save_local_remote': this.value,
                                'remote_id_select': remote_id_select
                            };
                        }
                        else
                        {
                            var local_remote_option = {
                                'save_local_remote': this.value
                            };
                        }
                        jQuery.extend(schedule_data, local_remote_option);
                        schedule_data = JSON.stringify(schedule_data);
                    }
                });

                //var utc_time = jQuery('#wpvivid_update_utc_time').html();
                //var arr = new Array();
                //arr = utc_time.split(':');
                schedule_data = JSON.parse(schedule_data);
                //schedule_data['current_day_hour'] = arr[0];
                //schedule_data['current_day_minute'] = arr[1];

                var enable_disable_option = {
                    'status': 'Active'
                };
                jQuery.extend(schedule_data, enable_disable_option);

                var backup_prefix=jQuery('input:text[option=update_schedule_backup][name=backup_prefix]').val();
                var backup_prefix_option = {
                    'backup_prefix': backup_prefix
                };
                jQuery.extend(schedule_data, backup_prefix_option);
                schedule_data = JSON.stringify(schedule_data);
                var ajax_data = {
                    'action': 'wpvivid_update_schedule_addon',
                    'id': edited_schedule_id,
                    'schedule': schedule_data
                };
                jQuery('#wpvivid_schedule_save_notice').html('');
                wpvivid_post_request_addon(ajax_data, function (data) {
                    wpvivid_handle_schedule_info(data);
                    jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-delete',[ 'update_full_backup', 'full_backup' ]);

                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('input:radio[option=update_schedule_backup][name=update_schedule_backup_backup_type]').click(function(){
                if(this.value === 'custom'){
                    jQuery('#wpvivid_custom_update_schedule_backup').show();
                    jQuery( document ).trigger( 'wpvivid_refresh_update_schedule_backup_tables', 'update_schedule_backup' );
                }
                else{
                    jQuery('#wpvivid_custom_update_schedule_backup').hide();
                }
            });

            jQuery('input:radio[option=update_schedule_backup][name=update_schedule_backup_save_local_remote]').click(function(){
                var value = jQuery(this).val();
                if(value === 'remote'){
                    jQuery( document ).trigger( 'wpvivid-has-default-remote', 'update_schedule' );
                }
                else{
                    jQuery('#wpvivid_update_schedule_backup_remote_selector_part').hide();
                }
            });

            jQuery('#wpvivid_set_update_schedule_prefix').on("keyup", function(){
                var manual_prefix = jQuery('#wpvivid_set_update_schedule_prefix').val();
                if(manual_prefix === ''){
                    manual_prefix = '*';
                    jQuery('#wpvivid_update_schedule_backup_prefix').html(manual_prefix);
                }
                else{
                    var reg = RegExp(/wpvivid/, 'i');
                    if (manual_prefix.match(reg)) {
                        jQuery('#wpvivid_set_update_schedule_prefix').val('');
                        jQuery('#wpvivid_update_schedule_backup_prefix').html('*');
                        alert('You can not use word \'wpvivid\' to comment the backup.');
                    }
                    else{
                        jQuery('#wpvivid_update_schedule_backup_prefix').html(manual_prefix);
                    }
                }
            });

            function wpvivid_recalc_update_schedule_backup_size(website_item_arr, custom_option)
            {
                if(website_item_arr.length > 0)
                {
                    console.log(website_item_arr);
                    var website_item = website_item_arr.shift();
                    var ajax_data = {
                        'action': 'wpvivid_recalc_backup_size',
                        'website_item': website_item,
                        'custom_option': custom_option
                    };

                    wpvivid_post_request_addon(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                if(website_item === 'database')
                                {
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-database-size').html(jsonarray.database_size);
                                }
                                if(website_item === 'core')
                                {
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-core-size').html(jsonarray.core_size);
                                }
                                if(website_item === 'content')
                                {
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-content-size').html(jsonarray.content_size);
                                }
                                if(website_item === 'themes')
                                {
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-themes-size').html(jsonarray.themes_size);
                                }
                                if(website_item === 'plugins')
                                {
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-plugins-size').html(jsonarray.plugins_size);
                                }
                                if(website_item === 'uploads')
                                {
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-uploads-size').html(jsonarray.uploads_size);

                                }
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_custom_manual_backup').find('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-additional-folder-size').html(jsonarray.additional_size);
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-total-file-size').html(jsonarray.total_file_size);
                                    jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                                    jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_update_schedule_backup_size(website_item_arr, custom_option);
                            }
                            else
                            {
                                alert(jsonarray.error);
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_recalc_update_schedule_backup_size(website_item_arr, custom_option);
                            }
                        }
                        catch (err) {
                            alert(err);
                            if(website_item === 'additional_folder')
                            {
                                jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                            wpvivid_recalc_update_schedule_backup_size(website_item_arr, custom_option);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        if(website_item === 'additional_folder')
                        {
                            jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                        wpvivid_recalc_update_schedule_backup_size(website_item_arr, custom_option);
                    });
                }
            }

            jQuery('#wpvivid_recalc_update_schedule_backup_size').click(function(){
                var custom_dirs = wpvivid_create_custom_setting_ex('update_schedule_backup');
                var custom_option = {
                    'custom_dirs': custom_dirs
                };
                var custom_option = JSON.stringify(custom_option);

                jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-database-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-core-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-themes-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-plugins-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-uploads-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-content-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-additional-folder-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-total-file-size').html('calculating');
                jQuery('#wpvivid_custom_update_schedule_backup').find('.wpvivid-total-exclude-file-size').html('calculating');

                var website_item_arr = new Array('database', 'core', 'content', 'themes', 'plugins', 'uploads', 'additional_folder');

                wpvivid_recalc_update_schedule_backup_size(website_item_arr, custom_option);
            });

            jQuery(document).ready(function () {
                wpvivid_sync_time(false);
            });
        </script>
        <?php
    }
}