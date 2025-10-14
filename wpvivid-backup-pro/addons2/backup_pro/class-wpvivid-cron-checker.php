<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * No_need_load: yes
 * Version: 2.2.37
 */

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class WPvivid_Debug_Schedule_List extends WP_List_Table
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
        $columns['wpvivid_id'] = __( 'ID', 'wpvivid' );
        $columns['wpvivid_name'] =__( 'Name', 'wpvivid'  );
        $columns['wpvivid_status'] = __( 'Status', 'wpvivid'  );
        $columns['wpvivid_start_at'] = __( 'Start at', 'wpvivid'  );
        $columns['wpvivid_start'] = __( 'Start', 'wpvivid'  );
        $columns['wpvivid_end'] = __( 'End', 'wpvivid'  );

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

    public function _column_wpvivid_id( $schedule )
    {
        echo '<td>'.$schedule['id'].'</td>';
    }

    public function _column_wpvivid_name( $schedule )
    {
        echo '<td>'.$schedule['hook_name'].'</td>';
    }

    public function _column_wpvivid_status( $schedule )
    {
        echo '<td>'.$schedule['status'].'</td>';
    }

    public function _column_wpvivid_start_at( $schedule )
    {
        $offset=get_option('gmt_offset');

        echo '<td>'.date("H:i:s - F-d-Y ", $schedule['start_at']+$offset*60*60).'</td>';
    }

    public function _column_wpvivid_start( $schedule )
    {
        $offset=get_option('gmt_offset');

        echo '<td>'.date("H:i:s - F-d-Y ", $schedule['start']+$offset*60*60).'</td>';
    }

    public function _column_wpvivid_end( $schedule )
    {
        $offset=get_option('gmt_offset');

        echo '<td>'.date("H:i:s - F-d-Y ", $schedule['end']+$offset*60*60).'</td>';
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
        if ($schedule['status'] == 'Active')
        {
            $class='schedule-item schedule-active';
        } else {
            $class='schedule-item';
        }
        ?>
        <tr class="<?php echo $class;?>" slug="<?php echo $schedule['id'];?>">
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

class WPvivid_No_Responds_Schedule_List extends WP_List_Table
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
        $columns['wpvivid_id'] = __( 'ID', 'wpvivid' );
        $columns['wpvivid_name'] =__( 'Name', 'wpvivid'  );
        $columns['wpvivid_status'] = __( 'Status', 'wpvivid'  );
        $columns['wpvivid_start_at'] = __( 'Start at', 'wpvivid'  );

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

    public function _column_wpvivid_id( $schedule )
    {
        echo '<td>'.$schedule['id'].'</td>';
    }

    public function _column_wpvivid_name( $schedule )
    {
        echo '<td>'.$schedule['hook_name'].'</td>';
    }

    public function _column_wpvivid_status( $schedule )
    {
        echo '<td>'.$schedule['status'].'</td>';
    }

    public function _column_wpvivid_start_at( $schedule )
    {
        $offset=get_option('gmt_offset');

        echo '<td>'.date("H:i:s - F-d-Y ", $schedule['start_at']+$offset*60*60).'</td>';
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
        if ($schedule['status'] == 'Active')
        {
            $class='schedule-item schedule-active';
        } else {
            $class='schedule-item';
        }
        ?>
        <tr class="<?php echo $class;?>" slug="<?php echo $schedule['id'];?>">
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

class WPvivid_Cron_Checker
{
    public function __construct()
    {
        add_action('init',array($this,'check_wp_cron_status'));
        add_filter('cron_schedules',array( $this,'cron_schedules'),100);
        add_action('wpvivid_check_schedule_status',array( $this,'check_schedule_status'));
        $this->check_status_schedule();

        if ( defined( 'DOING_CRON' ) && DOING_CRON )
        {
            add_action( 'init', array( $this,'init_cron_monitor') );
        }

        $option=get_option('wpvivid_check_cron_status',array());

        if(!empty($option))
        {
            add_action( 'admin_notices', array($this,'show_notices'));
            add_action('wpvivid_check_admin_notices', array($this, 'check_admin_notices'));
        }

        add_filter('wpvivid_schedule_tabs',array($this, 'add_schedule_tabs'),12);
    }

    public function check_admin_notices()
    {
        add_action( 'admin_notices', array($this,'show_notices'));
    }

    public function add_schedule_tabs($tabs)
    {
        $args['span_class']='dashicons dashicons-chart-bar';
        $args['span_style']='color:red;padding-right:0.5em;margin-top:0.1em;';
        $args['is_parent_tab']=0;
        $tabs['debug']['title']='Debug';
        $tabs['debug']['slug']='debug';
        $tabs['debug']['callback']=array($this, 'init_page');
        $tabs['debug']['args']=$args;
        return $tabs;
    }

    public function init_page()
    {
        $schedules=$this->get_schedule_logs();

        $table=new WPvivid_Debug_Schedule_List();
        $table->set_schedule_list($schedules);
        $table->prepare_items();
        $table->display();

        $no_responds_schedule=$this->get_no_responds_schedule();

        if(!empty($no_responds_schedule))
        {
            echo '<p>No responds schedules</p>';
            echo '<p></p>';
            $table=new WPvivid_No_Responds_Schedule_List();
            $table->set_schedule_list($no_responds_schedule);
            $table->prepare_items();
            $table->display();
        }
    }

    public function get_no_responds_schedule()
    {
        $no_responds_schedule=array();
        $crons = wp_get_ready_cron_jobs();
        if ( empty( $crons ) )
        {
            return $no_responds_schedule;
        }

        $gmt_time = microtime( true );

        $id=1;

        foreach ( $crons as $timestamp => $cronhooks )
        {
            if ( $timestamp > $gmt_time )
            {
                break;
            }

            $time=$gmt_time-$timestamp;
            if($time>3600)
            {
                foreach ( $cronhooks as $hook => $keys )
                {
                    if ( $this->is_wpvivid_cron_job($hook)===false )
                    {
                        continue;
                    }
                    $schedule['id']=$id;
                    $schedule['hook_name']=$hook;
                    $schedule['start_at']=$timestamp;
                    $schedule['status']='no responds';
                    $no_responds_schedule[]=$schedule;
                    $id++;
                }
            }
        }

        return $no_responds_schedule;
    }

    public function get_delayed_schedule_logs()
    {
        global  $wpdb ;

        $table_name=$this->get_table_name();
        $query="select * from $table_name";

        $delayed_schedule=array();

        $results= $wpdb->get_results($query,ARRAY_A);
        if(!empty($results))
        {
            foreach ($results as $schedule)
            {
                $time=$schedule['start']-$schedule['start_at'];
                if($time>3600)
                {
                    $delayed_schedule[]=$schedule;
                }
            }
        }

        return $delayed_schedule;
    }

    public function get_schedule_logs()
    {
        global  $wpdb ;

        $table_name=$this->get_table_name();
        $query="select * from $table_name";

        $schedules=array();

        $results= $wpdb->get_results($query,ARRAY_A);
        if(!empty($results))
        {
            foreach ($results as $schedule)
            {
                $time=$schedule['start']-$schedule['start_at'];
                if($time>3600)
                {
                    $schedule['status']='delayed';
                }
                $schedules[]=$schedule;
            }
        }

        return $schedules;
    }

    public function check_wp_cron_status()
    {
        if ( defined( 'DOING_CRON' ) && DOING_CRON )
        {
            return;
        }

        $cached = get_transient( 'wpvivid-check-wp-cron-cache' );

        if($cached)
        {
            return;
        }

        set_transient( 'wpvivid-check-wp-cron-cache', current_time( 'mysql' ), 86400 );

        $result=$this->test_cron_spawn();

        if($result['result']=='failed')
        {
            update_option('wpvivid_check_cron_status',$result,'no');
        }
        else
        {
            delete_option('wpvivid_check_cron_status');
        }
    }

    public function test_cron_spawn()
    {
        $doing_wp_cron = sprintf( '%.22F', microtime( true ) );

        $cron_request = apply_filters( 'cron_request', array(
            'url'  => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ),
            'key'  => $doing_wp_cron,
            'args' => array(
                'timeout'   => 3,
                'blocking'  => true,
                'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
            ),
        ) );

        $cron_request['args']['blocking'] = true;
        $result = wp_remote_post( $cron_request['url'], $cron_request['args'] );

        if ( is_wp_error( $result ) )
        {
            $ret['result']='failed';
            $ret['error']=$result->get_error_message();
            return $ret;
        }
        else if ( wp_remote_retrieve_response_code( $result ) >= 300 )
        {
            $ret['result']='failed';
            $ret['error']='Unexpected HTTP response code:'.wp_remote_retrieve_response_code( $result );
            return $ret;
        }
        else
        {
            $ret['result']='success';
            return $ret;
        }
    }

    public function show_notices()
    {
        $option=get_option('wpvivid_check_cron_status',array());
        echo '<div class="notice notice-warning">
                        <p>' . $option['error'] . '</p>
                   </div>';
    }

    public function show_schedule_status_notices()
    {
        $schedules=get_option('wpvivid_schedule_warining',array());

        if(!empty($schedules))
        {
            foreach ($schedules as $id=>$schedule)
            {
                if($schedule['run_timestamp']>$schedule['next_timestamp'])
                {
                    $time=$schedule['run_timestamp']-$schedule['next_timestamp'];
                    if($time>3600*12)
                    {
                        echo '<div class="notice notice-warning">
                        <p>schedule:' . $id . ' was started at least one day behind the expected time</p>
                   </div>';
                    }
                    else if($time>3600)
                    {
                        echo '<div class="notice notice-warning">
                        <p>schedule：' . $id . ' was started at least one hour behind the expected time</p>
                   </div>';
                    }
                }
                else
                {
                    if($schedule['time_now']-$schedule['next_timestamp']>3600*12)
                    {
                        echo '<div class="notice notice-warning">
                        <p>schedule:' . $id . ' has not started one day after the estimated time</p>
                   </div>';
                    }
                    else if($schedule['time_now']-$schedule['next_timestamp']>3600)
                    {
                        echo '<div class="notice notice-warning">
                        <p>schedule:' . $id . ' has not started one hour after the estimated time</p>
                   </div>';
                    }
                }
            }
        }

    }

    public function check_status_schedule()
    {
        if(!defined( 'DOING_CRON' ))
        {
            if(wp_get_schedule('wpvivid_check_schedule_status')===false)
            {
                if(wp_schedule_event(time()+30, 'wpvivid_weekly', 'wpvivid_check_schedule_status')===false)
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function init_cron_monitor()
    {
        $doing_cron_key = get_transient( 'doing_cron' );
        if ( !empty( $doing_cron_key ) )
        {
            // transient is already set, lets go!
            $this->monitor_runs();
        }
    }

    public function monitor_runs()
    {
        register_shutdown_function( array($this,'deal_shutdown_error'));
        global $wpvivid_doing_cron_key;
        $wpvivid_doing_cron_key = get_transient( 'doing_cron' );

        if ( empty ( $wpvivid_doing_cron_key ) )
        {
            // shouldn't get here
            return;
        }

        $crons = wp_get_ready_cron_jobs();
        if ( empty( $crons ) )
        {
            // shouldn't get here
            return;
        }

        $gmt_time = microtime( true );

        foreach ( $crons as $timestamp => $cronhooks )
        {
            if ( $timestamp > $gmt_time )
            {
                break;
            }

            $start_at=$timestamp;
            foreach ( $cronhooks as $hook => $keys )
            {
                if ( $this->is_wpvivid_cron_job($hook)===false )
                {
                    continue;
                }

                add_action( $hook,function() use ( $hook,$start_at ) {
                    $this->start_cron_job( $hook ,$start_at);
                }, 0);
                add_action( $hook,function() use ( $hook ) {
                    $this->end_cron_job( $hook );
                }, PHP_INT_MAX);
            }
        }
    }

    public function deal_shutdown_error()
    {
        global $wpvivid_doing_cron_key;
        $last_error = error_get_last();
        $has_error = false;
        $error_message = null;
        if ( $last_error != null && $last_error['type'] === E_ERROR ) {
            $error_message = $last_error['message'];
        }
        if ( empty( $error_message ) ) {
            $error_message = false;
        }

        $hooks_in_progress = $this->get_hooks_in_progress( $wpvivid_doing_cron_key );

        if(!empty($hooks_in_progress))
        {
            foreach ( $hooks_in_progress as $hook_name )
            {
                $this->end_cron_job($hook_name['hook_name']);

                if ( $error_message!==false )
                {
                    $this->add_error_cron_job( $wpvivid_doing_cron_key, $hook_name ,$error_message);
                }
            }
        }
    }

    public function check_schedule_status()
    {
        die();
    }

    public function get_hooks_in_progress($doing_cron_key)
    {
        global  $wpdb ;

        $table_name=$this->get_table_name();
        $query="select hook_name from $table_name where cron_key='$doing_cron_key'";

        return $wpdb->get_results($query,ARRAY_A);
    }

    public function cron_schedules($schedules)
    {
        if(!isset($schedules["wpvivid_weekly"]))
        {
            $schedules["wpvivid_weekly"] = array(
                'interval' => 604800,
                'display' => __('Weekly'));
        }

        return $schedules;
    }

    public function is_wpvivid_cron_job($hook)
    {
        if(strpos($hook,'wpvivid_')!==false)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function start_cron_job($hook,$timestamp)
    {
        global $wpvivid_doing_cron_key,$wpdb;

        $params = array(
            'cron_key'  => $wpvivid_doing_cron_key,
            'hook_name' => $hook,
            'start'     => time(),
            'end'=>0,
            'start_at'=>$timestamp,
            'status'=>'running'
        );

        $table_name=$this->get_table_name();
        $wpdb->insert( $table_name, $params );

    }

    public function end_cron_job($hook)
    {
        global $wpvivid_doing_cron_key,$wpdb;

        $data['end']=time();
        $data['status']='end';
        $where['cron_key']=$wpvivid_doing_cron_key;
        $where['hook_name']=$hook;

        $table_name=$this->get_table_name();
        $wpdb->update( $table_name, $data, $where);
    }

    public function add_error_cron_job($wpvivid_doing_cron_key,$hook,$error)
    {
        global $wpvivid_doing_cron_key,$wpdb;

        $params = array(
            'cron_key'  => $wpvivid_doing_cron_key,
            'hook_name' => $hook,
            'error'     => $error
        );

        $table_name=$this->get_error_table_name();
        $wpdb->insert( $table_name, $params );
    }

    public function get_table_name()
    {
        global $wpdb ;
        $table_name= $wpdb->prefix . 'wpvivid_cron_logs';

        if(!class_exists('dbDelta'))
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
        {
            $sql = "CREATE TABLE $table_name (
                id int NOT NULL AUTO_INCREMENT,
                cron_key text NOT NULL,
                hook_name text NOT NULL,
                start INT NOT NULL,
                end INT NOT NULL,
                start_at INT NOT NULL,
                status text NOT NULL,
                PRIMARY KEY (id)
                );";
            //reference to upgrade.php file
            dbDelta( $sql );
        }
        return $table_name;
    }

    public function get_error_table_name()
    {
        global $wpdb ;
        $table_name= $wpdb->prefix . 'wpvivid_cron_error_logs';

        if(!class_exists('dbDelta'))
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
        {
            $sql = "CREATE TABLE $table_name (
                id int NOT NULL AUTO_INCREMENT,
                cron_key text NOT NULL,
                hook_name text NOT NULL,
                error text NOT NULL,
                PRIMARY KEY (id)
                );";
            //reference to upgrade.php file
            dbDelta( $sql );
        }
        return $table_name;
    }
}