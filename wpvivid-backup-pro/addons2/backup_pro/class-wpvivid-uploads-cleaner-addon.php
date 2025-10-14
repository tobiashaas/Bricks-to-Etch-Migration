<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_Uploads_Cleaner_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WPvivid_Unused_Upload_Files_List_Ex extends WP_List_Table
{
    public $list;
    public $type;
    public $page_num;
    public $parent;

    public function __construct( $args = array() )
    {
        global $wpdb;
        parent::__construct(
            array(
                'plural' => 'upload_files',
                'screen' => 'upload_files',
            )
        );
    }

    public function set_parent($parent)
    {
        $this->parent=$parent;
    }

    public function set_list($list,$page_num=1)
    {
        $this->list=$list;
        $this->page_num=$page_num;
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped' );
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
            $tag='th';
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
        $sites_columns = array(
            'cb'          => __( ' ' ),
            'thumb'    =>__( 'Thumbnail' ),
            'path'    => __( 'Path' ),
            //'folder' => __( 'Folder' ),
            'size'=>__( 'Size' )
        );

        return $sites_columns;
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

    public function column_cb( $item )
    {
        $html='<input type="checkbox" name="uploads" value="'.$item['id'].'" />';
        echo $html;
    }

    public function column_thumb($item)
    {
        $supported_image = array(
            'gif',
            'jpg',
            'jpeg',
            'png',
            'webp'
        );

        $upload_dir=wp_upload_dir();

        $path=$upload_dir['basedir'].DIRECTORY_SEPARATOR.$item['path'];

        $ext = strtolower(pathinfo($item['path'], PATHINFO_EXTENSION));
        if (in_array($ext, $supported_image)&&file_exists( $path ))
        {
            echo "<a target='_blank' href='" . $upload_dir['baseurl'].'/'.$item['path'] .
                "'><img style='max-width: 48px; max-height: 48px;' src='" .
                $upload_dir['baseurl'].'/'.$item['path'] . "' />";
        }
        else {
            echo '<span class="dashicons dashicons-no-alt"></span>';
        }

    }

    public function column_path( $item )
    {
        $item['path']=esc_html($item['path']);
        echo '...\uploads\\'.$item['path'];
    }

    public function column_folder( $item )
    {
        if($item['folder']=='.')
        {
            echo 'Uploads root';
        }
        else
        {
            echo $item['folder'];
        }
    }

    public function column_size( $item )
    {
        $upload_dir=wp_upload_dir();
        $file_name=$upload_dir['basedir'].DIRECTORY_SEPARATOR.$item['path'];

        if(file_exists($file_name))
        {
            echo size_format(filesize($file_name),2);
        }
        else
        {
            echo 'file not found';
        }

    }

    public function has_items()
    {
        return !empty($this->list);
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $total_items =sizeof($this->list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 20,
            )
        );
    }

    public function display_rows()
    {
        $this->_display_rows( $this->list );
    }

    private function _display_rows( $list )
    {
        $page=$this->get_pagenum();

        $page_list=$list;
        $temp_page_list=array();

        $count=0;
        while ( $count<$page )
        {
            $temp_page_list = array_splice( $page_list, 0, 20);
            $count++;
        }

        foreach ( $temp_page_list as $key=>$item)
        {
            $this->single_row($item);
        }
    }

    public function single_row($item)
    {
        ?>
        <tr>
            <?php $this->single_row_columns( $item ); ?>
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
                "%s<input class='current-page'  type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label  class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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
        $scanner=new WPvivid_Uploads_Scanner();
        $folders=$scanner->get_all_folder();

        $css_type = '';
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
            $css_type = 'padding:0 0 1em 0;';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'padding:1em 0 0 0;';
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <div>
                    <span>
                        <select name="action" id="wpvivid_uc_bulk_action">
                            <option value="-1">Bulk Actions</option>
                            <option value="wpvivid_isolate_selected_image">Isolate selected images</option>
                            <option value="wpvivid_isolate_list_image">Isolate all images</option>
                        </select>
                    </span>
                    <span><input id="wpvivid_isolate_image" type="submit" class="button action" value="Apply"></span>
                    <span><input type="text" id="wpvivid_result_list_search"></span>
                    <span>
                        <select id="wpvivid_result_list_folder">
                            <option selected="selected" value="0">All Folders</option>
                            <?php
                            if(!empty($folders))
                            {
                                asort($folders);
                                foreach ($folders as $folder)
                                {
                                    echo "<option value='$folder'>$folder</option>";
                                }
                            }
                            ?>
                        </select>
                    </span>
                    <span><input id="wpvivid_result_list_search_btn" type="submit" class="button action" value="Search"></span>
                </div>
                <div id="wpvivid_isolate_progress" style="margin-top: 4px; display: none;">
                    <div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>
                    <div style="float: left; margin-top: 2px;">Isolating images...</div>
                    <div style="clear: both;"></div>
                </div>
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>

                <br class="clear" />
            </div>
            <?php
        }
        else
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <div>
                    <span>
                        <select name="action" id="wpvivid_uc_bulk_action">
                            <option value="-1">Bulk Actions</option>
                            <option value="wpvivid_isolate_selected_image">Isolate selected images</option>
                            <option value="wpvivid_isolate_list_image">Isolate all images</option>
                        </select>
                    </span>
                    <span><input id="wpvivid_isolate_image" type="submit" class="button action" value="Apply"></span>
                    <span><input type="text" id="wpvivid_result_list_search"></span>
                    <span>
                        <select id="wpvivid_result_list_folder">
                            <option selected="selected" value="0">All Folders</option>
                            <?php
                            if(!empty($folders))
                            {
                                asort($folders);
                                foreach ($folders as $folder)
                                {
                                    echo "<option value='$folder'>$folder</option>";
                                }
                            }
                            ?>
                        </select>
                    </span>
                    <span><input id="wpvivid_result_list_search_btn" type="submit" class="button action" value="Search"></span>
                </div>
                <div id="wpvivid_isolate_progress" style="margin-top: 4px; display: none;">
                    <div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>
                    <div style="float: left; margin-top: 2px;">Isolating images...</div>
                    <div style="clear: both;"></div>
                </div>
                <br class="clear" />
            </div>
            <?php
        }
    }

    public function display() {
        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" >
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
                <?php $this->print_column_headers( false ); ?>
            </tr>
            </tfoot>

        </table>
        <?php
    }
}

class WPvivid_Isolate_Files_List_Ex extends WP_List_Table
{
    public $list;
    public $type;
    public $page_num;
    public $parent;

    public function __construct( $args = array() )
    {
        global $wpdb;
        parent::__construct(
            array(
                'plural' => 'upload_files',
                'screen' => 'upload_files',
            )
        );
    }

    public function set_parent($parent)
    {
        $this->parent=$parent;
    }

    public function set_list($list,$page_num=1)
    {
        $this->list=$list;
        $this->page_num=$page_num;
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped' );
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
            $tag='th';
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
        $sites_columns = array(
            'cb'          => __( ' ' ),
            'thumb'    =>__( 'Thumbnail' ),
            'path'    => __( 'Path' ),
            //'folder' => __( 'Folder' ),
            'size'=>__( 'Size' )
        );

        return $sites_columns;
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

    public function column_cb( $item )
    {
        $html='<input type="checkbox" name="uploads" />';
        echo $html;
    }

    public function column_thumb($item)
    {
        $supported_image = array(
            'gif',
            'jpg',
            'jpeg',
            'png',
            'webp'
        );



        $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.WPVIVID_UPLOADS_ISO_DIR.DIRECTORY_SEPARATOR.$item['path'];

        $ext = strtolower(pathinfo($item['path'], PATHINFO_EXTENSION));
        if (in_array($ext, $supported_image)&&file_exists( $path ))
        {
            echo "<a target='_blank' href='" . WP_CONTENT_URL.'/'.WPVIVID_UPLOADS_ISO_DIR.'/'.$item['path'] .
                "'><img style='max-width: 48px; max-height: 48px;' src='" .
                WP_CONTENT_URL.'/'.WPVIVID_UPLOADS_ISO_DIR.'/'.$item['path'] . "' />";
        }
        else {
            echo '<span class="dashicons dashicons-no-alt"></span>';
        }

    }

    public function column_path( $item )
    {
        $item['path']=esc_html($item['path']);
        echo '...\uploads\\'.$item['path'];
    }

    public function column_folder( $item )
    {
        if($item['folder']=='.')
        {
            echo 'Uploads root';
        }
        else
        {
            echo $item['folder'];
        }
    }

    public function column_size( $item )
    {
        $file_name=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.WPVIVID_UPLOADS_ISO_DIR.DIRECTORY_SEPARATOR.$item['path'];

        if(file_exists($file_name))
        {
            echo size_format(filesize($file_name),2);
        }
        else
        {
            echo 'file not found';
        }

    }

    public function has_items()
    {
        return !empty($this->list);
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $total_items =sizeof($this->list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 20,
            )
        );
    }

    public function display_rows()
    {
        $this->_display_rows( $this->list );
    }

    private function _display_rows( $list )
    {
        $page=$this->get_pagenum();

        $page_list=$list;
        $temp_page_list=array();

        $count=0;
        while ( $count<$page )
        {
            $temp_page_list = array_splice( $page_list, 0, 20);
            $count++;
        }

        foreach ( $temp_page_list as $key=>$item)
        {
            $this->single_row($item);
        }
    }

    public function single_row($item)
    {
        ?>
        <tr path="<?php echo $item['path']?>">
            <?php $this->single_row_columns( $item ); ?>
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
                "%s<input class='current-page'  type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label  class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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
        $iso=new WPvivid_Isolate_Files();
        $result=$iso->get_isolate_folder();

        $css_type = '';
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
            $css_type = 'padding:0 0 1em 0;';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'padding:1em 0 0 0;';
        }

        $total_pages     = $this->_pagination_args['total_pages'];

        $admin_url = apply_filters('wpvivid_get_admin_url', '');

        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <div>
                    <span>
                        <select name="action" id="wpvivid_uc_iso_bulk_action">
                        <option value="-1">Bulk Actions</option>
                        <option value="wpvivid_restore_selected_image">Restore selected images</option>
                        <option value="wpvivid_restore_list_image">Restore all images</option>
                        <option value="wpvivid_delete_selected_image">Delete selected images</option>
                        <option value="wpvivid_delete_list_image">Delete all images</option>
                    </select>
                    </span>
                    <span><input id="wpvivid_restore_delete_image" type="submit" class="button action" value="Apply"></span>
                    <span><input type="text" id="wpvivid_iso_list_search"></span>
                    <span>
                        <select id="wpvivid_iso_list_folder">
                        <option selected="selected" value="0">All Folders</option>
                            <?php
                            asort($result['folders']);
                            foreach ($result['folders'] as $folder)
                            {
                                echo "<option value='$folder'>$folder</option>";
                            }
                            ?>
                    </select>
                    </span>
                    <span><input id="wpvivid_iso_list_search_btn" type="submit" class="button" value="Search"></span>
                </div>
                <div id="wpvivid_restore_delete_progress" style="margin-top: 4px; display: none;">
                    <div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>
                    <div id="wpvivid_restore_delete_text" style="float: left; margin-top: 2px;">Restoring images...</div>
                    <div style="clear: both;"></div>
                </div>
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>
                <br class="clear" />
            </div>
            <?php
        }
        else
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <div>
                    <span>
                        <select name="action" id="wpvivid_uc_iso_bulk_action">
                        <option value="-1">Bulk Actions</option>
                        <option value="wpvivid_restore_selected_image">Restore selected images</option>
                        <option value="wpvivid_restore_list_image">Restore all images</option>
                        <option value="wpvivid_delete_selected_image">Delete selected images</option>
                        <option value="wpvivid_delete_list_image">Delete all images</option>
                    </select>
                    </span>
                    <span><input id="wpvivid_restore_delete_image" type="submit" class="button action" value="Apply"></span>
                    <span><input type="text" id="wpvivid_iso_list_search"></span>
                    <span>
                        <select id="wpvivid_iso_list_folder">
                        <option selected="selected" value="0">All Folders</option>
                            <?php
                            asort($result['folders']);
                            foreach ($result['folders'] as $folder)
                            {
                                echo "<option value='$folder'>$folder</option>";
                            }
                            ?>
                    </select>
                    </span>
                    <span><input id="wpvivid_iso_list_search_btn" type="submit" class="button" value="Search"></span>
                </div>
                <div id="wpvivid_restore_delete_progress" style="margin-top: 4px; display: none;">
                    <div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>
                    <div id="wpvivid_restore_delete_text" style="float: left; margin-top: 2px;">Restoring images...</div>
                    <div style="clear: both;"></div>
                </div>
                <br class="clear" />
            </div>
            <?php
        }
    }

    public function display() {
        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" >
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
                <?php $this->print_column_headers( false ); ?>
            </tr>
            </tfoot>

        </table>
        <?php
    }
}

class WPvivid_Uploads_Cleaner_addon
{
    public $main_tab;
    public $end_shutdown_scan_file_function;
    public $end_shutdown_unused_file_function;
    public $end_shutdown_delete_image_function;

    public function __construct()
    {
        //filters
        add_filter('wpvividdashboard_pro_setting_tab', array($this, 'setting_tab'), 13);
        add_filter('wpvivid_set_general_setting', array($this, 'set_general_setting'), 11, 3);

        //ajax
        add_action('wp_ajax_wpvivid_start_scan_uploads_files_task_ex', array($this, 'start_scan_uploads_files_task'));
        add_action('wp_ajax_wpvivid_scan_uploads_files_from_post_ex',array($this, 'scan_uploads_files_from_post'));
        add_action('wp_ajax_wpvivid_get_scan_upload_files_progress_ex', array($this, 'get_scan_upload_files_progress_ex'));
        add_action('wp_ajax_wpvivid_start_unused_files_task_ex',array($this, 'start_unused_files_task'));
        add_action('wp_ajax_wpvivid_unused_files_task_ex',array($this, 'unused_files_task'));
        add_action('wp_ajax_wpvivid_get_unused_files_progress_ex', array($this, 'get_unused_files_progress_ex'));
        add_action('wp_ajax_wpvivid_get_result_list_ex',array($this, 'get_result_list'));
        add_action('wp_ajax_wpvivid_uc_add_exclude_files_ex',array($this, 'add_exclude_files'));
        add_action('wp_ajax_wpvivid_isolate_selected_image_ex',array($this, 'isolate_selected_image'));
        add_action('wp_ajax_wpvivid_start_isolate_all_image_ex',array($this, 'start_isolate_all_image'));
        add_action('wp_ajax_wpvivid_isolate_all_image_ex',array($this, 'isolate_all_image'));
        add_action('wp_ajax_wpvivid_get_iso_list_ex',array($this, 'get_iso_list'));
        add_action('wp_ajax_wpvivid_delete_selected_image_ex',array($this, 'delete_selected_image'));
        add_action('wp_ajax_wpvivid_start_delete_all_image_ex',array($this, 'delete_all_image'));
        add_action('wp_ajax_wpvivid_delete_all_image_ex',array($this, 'delete_all_image'));
        add_action('wp_ajax_wpvivid_get_delete_image_progress_ex', array($this, 'get_delete_image_progress_ex'));
        add_action('wp_ajax_wpvivid_restore_selected_image_ex',array($this, 'restore_selected_image'));
        add_action('wp_ajax_wpvivid_start_restore_all_image_ex',array($this, 'restore_all_image'));
        add_action('wp_ajax_wpvivid_restore_all_image_ex',array($this, 'restore_all_image'));
        //dashboard
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),99);

        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));

        add_filter('wpvivid_uc_scan_include_files_regex',array($this,'scan_include_files_regex'),10);
        add_filter('wpvivid_uc_scan_exclude_files_regex',array($this,'scan_exclude_files_regex'),11);

        $this->end_shutdown_scan_file_function=true;
        $this->end_shutdown_unused_file_function=true;
        $this->end_shutdown_delete_image_function=true;
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-use-image-cleaner';
        $cap['display']='Unused Image Cleaner';
        $cap['menu_slug']=strtolower(sprintf('%s-image-cleaner', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap['index']=16;
        $cap['icon']='<span class="dashicons dashicons-format-gallery wpvivid-dashicons-grey"></span>';
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        if(isset($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_cleaner']) && !empty($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_cleaner'])){
            unset($toolbar_menus['wpvivid_admin_menu']['child']['wpvivid_admin_menu_cleaner']);
        }

        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_unused_image_cleaner');
        if($display)
        {
            $admin_url = apply_filters('wpvivid_get_admin_url', '');
            $menu['id'] = 'wpvivid_admin_menu_cleaner';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Unused Image Cleaner';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-image-cleaner');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid').'-image-cleaner';
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-image-cleaner");
            $menu['index'] = 13;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']=strtolower(sprintf('%s-image-cleaner', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $screen['screen_id']='wpvivid-plugin_page_'.strtolower(sprintf('%s-image-cleaner', apply_filters('wpvivid_white_label_slug', 'wpvivid')));;
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_unused_image_cleaner');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Unused Image Cleaner');
            $submenu['menu_title'] = 'Unused Image Cleaner';
            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-image-cleaner");
            $submenu['menu_slug'] = strtolower(sprintf('%s-image-cleaner', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 13;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    /***** unused image cleaner useful function begin *****/
    private function transfer_path($path){
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode(DIRECTORY_SEPARATOR,$values);
    }

    public function output_unused_image_cleaner()
    {
        $scan_limit=get_option('wpvivid_uc_scan_limit',20);
        $files_limit=get_option('wpvivid_uc_files_limit',100);

        $default_file_types=array();
        $default_file_types[]='png';
        $default_file_types[]='jpg';
        $default_file_types[]='jpeg';
        $scan_file_types=get_option('wpvivid_uc_scan_file_types',$default_file_types);

        $quick_scan=get_option('wpvivid_uc_quick_scan',false);

        if($quick_scan)
        {
            $quick_scan='checked';
        }
        else
        {
            $quick_scan='';
        }

        $delete_media_when_delete_file=get_option('wpvivid_uc_delete_media_when_delete_file',true);

        if($delete_media_when_delete_file)
        {
            $delete_media_when_delete_file='checked';
        }
        else
        {
            $delete_media_when_delete_file='';
        }

        $ignore_webp=get_option('wpvivid_uc_ignore_webp',false);

        if($ignore_webp)
        {
            $ignore_webp='checked';
        }
        else
        {
            $ignore_webp='';
        }

        ?>
        <div>
            <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">General</label></td>
                    <td>
                        <p></p>
                        <div>
                            <label class="wpvivid-checkbox">
                                <span>Enable Quick Scan</span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Checking this option will speed up your scans but may produce lower accuracy.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                                <input type="checkbox" id="wpvivid_uc_quick_scan" option="setting" name="wpvivid_uc_quick_scan" <?php esc_attr_e($quick_scan); ?> />
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </div>

                        <p></p>
                        <div>
                            <label class="wpvivid-checkbox">
                                <span>Delete Unused Image URL in Database</span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>With this option checked, when the image is deleted, the corresponding image url in the database that is not used anywhere on your website will also be deleted.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                                <input type="checkbox" id="wpvivid_uc_delete_media_when_delete_file" option="setting" name="wpvivid_uc_delete_media_when_delete_file" <?php esc_attr_e($delete_media_when_delete_file); ?> />
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </div>

                        <p></p>
                        <div>
                            <label class="wpvivid-checkbox">
                                <span>Ignore webp files</span>
                                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                    <div class="wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Do not scan webp files.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                                <input type="checkbox" id="wpvivid_uc_ignore_webp" option="setting" name="wpvivid_uc_ignore_webp" <?php esc_attr_e($ignore_webp); ?> />
                                <span class="wpvivid-checkbox-checkmark"></span>
                            </label>
                        </div>

                        <p></p>
                        <div>
                            <input type="text" placeholder="20" id="wpvivid_uc_scan_limit" option="setting" name="wpvivid_uc_scan_limit" value="<?php esc_attr_e($scan_limit); ?>" onkeyup="value=value.replace(/\D/g,'')" />
                            <span>Posts Quantity Processed Per Request</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                                <div class="wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Set how many posts to process per request. The value should be set depending on your server performance and the recommended value is 20.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </div>

                        <p></p>
                        <div><input type="text" placeholder="100" id="wpvivid_uc_files_limit" option="setting" name="wpvivid_uc_files_limit" value="<?php esc_attr_e($files_limit); ?>" onkeyup="value=value.replace(/\D/g,'')" />
                            <span>Media Files Quantity Processed Per Request</span>
                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                                <div class="wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Set how many media files to process per request. The value should be set depending on your server performance and the recommended value is 100.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>

                <?php
                if(defined('WPVIVID_PLUGIN_VERSION'))
                {
                    if(version_compare(WPVIVID_PLUGIN_VERSION,'0.9.89','>='))
                    {
                        $exclude_path=get_option('wpvivid_uc_exclude_files_regex', '');
                        ?>
                        <tr>
                            <td class="row-title" style="min-width:200px;">
                                <span>Exclude images by folder path</span>
                            </td>
                            <td>
                                <textarea placeholder="Example:&#10;/wp-content/uploads/19/03/&#10;/wp-content/uploads/19/04/" option="setting" name="wpvivid_uc_exclude_files_regex" style="width:100%; height:200px; overflow-x:auto;"><?php echo $exclude_path; ?></textarea>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </table>
        </div>
        <div style="padding:1em 1em 0 0;"><input class="button-primary wpvivid_setting_uploads_cleaner_save" type="submit" value="Save Changes"></div>
        <script>
            jQuery('.wpvivid_setting_uploads_cleaner_save').click(function()
            {
                wpvivid_set_general_settings();
            });
        </script>
        <?php
    }
    /***** unused image cleaner useful function end *****/

    /***** unused image cleaner filters begin *****/
    public function setting_tab($tabs){
        if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-use-image-cleaner'))
        {
            $args['span_class']='dashicons dashicons-code-standards wpvivid-dashicons-green';
            $args['span_style']='padding-right:0.5em;margin-top:0.1em;';
            $tabs['unused_image_cleaner']['title']='Unused Image Cleaner';
            $tabs['unused_image_cleaner']['slug']='unused_image_cleaner';
            $tabs['unused_image_cleaner']['callback']=array($this, 'output_unused_image_cleaner');
            $tabs['unused_image_cleaner']['args']=$args;
        }
        return $tabs;
    }

    public function set_general_setting($setting_data, $setting, $options){
        if(isset($setting['wpvivid_uc_scan_limit']))
            $setting_data['wpvivid_uc_scan_limit'] = intval($setting['wpvivid_uc_scan_limit']);

        if(isset($setting['wpvivid_uc_files_limit']))
            $setting_data['wpvivid_uc_files_limit'] = intval($setting['wpvivid_uc_files_limit']);

        if(isset($setting['wpvivid_uc_scan_file_types'])&&is_array($setting['wpvivid_uc_scan_file_types']))
            $setting_data['wpvivid_uc_scan_file_types'] = $setting['wpvivid_uc_scan_file_types'];

        if(isset($setting['wpvivid_uc_post_types'])&&is_array($setting['wpvivid_uc_post_types']))
            $setting_data['wpvivid_uc_post_types'] = $setting['wpvivid_uc_post_types'];

        if(isset($setting['wpvivid_uc_quick_scan']))
            $setting_data['wpvivid_uc_quick_scan'] = boolval($setting['wpvivid_uc_quick_scan']);

        if(isset($setting['wpvivid_uc_delete_media_when_delete_file']))
            $setting_data['wpvivid_uc_delete_media_when_delete_file'] = boolval($setting['wpvivid_uc_delete_media_when_delete_file']);

        if(isset($setting['wpvivid_uc_exclude_files_regex']))
            $setting_data['wpvivid_uc_exclude_files_regex'] = $setting['wpvivid_uc_exclude_files_regex'];

        if(isset($setting['wpvivid_uc_ignore_webp']))
            $setting_data['wpvivid_uc_ignore_webp'] = boolval($setting['wpvivid_uc_ignore_webp']);

        return $setting_data;
    }
    /***** unused image cleaner filters end *****/

    /***** unused image cleaner ajax begin *****/
    public function deal_shutdown_scan_file_error()
    {
        if($this->end_shutdown_scan_file_function===false)
        {
            $error = error_get_last();
            if (!is_null($error))
            {
                if(preg_match('/Allowed memory size of.*$/', $error['message']))
                {
                    $ret['result']='failed';
                    $ret['error']=$error['message'];

                    $task=get_option('scan_unused_files_task',array());
                    $task['status']='failed';
                    $task['error']=$error['message'];
                    update_option('scan_unused_files_task',$task,'no');
                    echo json_encode($ret);
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']=$error['message'];

                    $message= 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];

                    $task=get_option('scan_unused_files_task',array());
                    $task['status']='failed';
                    $task['error']=$message;
                    update_option('scan_unused_files_task',$task,'no');
                    echo json_encode($ret);
                }
            }
            else
            {
                $ret['result']='failed';
                $ret['error']=$error['message'];

                $message= 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];

                $task=get_option('scan_unused_files_task',array());
                $task['status']='failed';
                $task['error']=$message;
                update_option('scan_unused_files_task',$task,'no');
                echo json_encode($ret);
            }

            die();
        }
    }

    public function deal_shutdown_unused_file_error()
    {
        if($this->end_shutdown_unused_file_function===false)
        {
            $error = error_get_last();
            if (!is_null($error))
            {
                if(preg_match('/Allowed memory size of.*$/', $error['message']))
                {
                    $ret['result']='failed';
                    $ret['error']=$error['message'];

                    $task=get_option('unused_uploads_task',array());
                    $task['status']='failed';
                    $task['error']=$error['message'];
                    update_option('unused_uploads_task',$task,'no');
                    echo json_encode($ret);
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']=$error['message'];

                    $message= 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];

                    $task=get_option('unused_uploads_task',array());
                    $task['status']='failed';
                    $task['error']=$message;
                    update_option('unused_uploads_task',$task,'no');
                    echo json_encode($ret);
                }
            }
            else
            {
                $ret['result']='failed';
                $ret['error']=$error['message'];

                $message= 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];

                $task=get_option('unused_uploads_task',array());
                $task['status']='failed';
                $task['error']=$message;
                update_option('unused_uploads_task',$task,'no');
                echo json_encode($ret);
            }

            die();
        }
    }

    public function deal_shutdown_delete_image_error()
    {
        if($this->end_shutdown_delete_image_function===false)
        {
            $error = error_get_last();
            if (!is_null($error))
            {
                if(preg_match('/Allowed memory size of.*$/', $error['message']))
                {
                    $ret['result']='failed';
                    $ret['error']=$error['message'];

                    $task=get_option('wpvivid_delete_unused_image_task',array());
                    $task['status']='failed';
                    $task['error']=$error['message'];
                    update_option('wpvivid_delete_unused_image_task',$task,'no');
                    echo json_encode($ret);
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']=$error['message'];

                    $message= 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];

                    $task=get_option('wpvivid_delete_unused_image_task',array());
                    $task['status']='failed';
                    $task['error']=$message;
                    update_option('wpvivid_delete_unused_image_task',$task,'no');
                    echo json_encode($ret);
                }
            }
            else
            {
                $ret['result']='failed';
                $ret['error']=$error['message'];

                $message= 'type: '. $error['type'] . ', ' . $error['message'] . ' file:' . $error['file'] . ' line:' . $error['line'];

                $task=get_option('wpvivid_delete_unused_image_task',array());
                $task['status']='failed';
                $task['error']=$message;
                update_option('wpvivid_delete_unused_image_task',$task,'no');
                echo json_encode($ret);
            }

            die();
        }
    }

    public function start_scan_uploads_files_task()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $this->end_shutdown_scan_file_function=false;
        register_shutdown_function(array($this,'deal_shutdown_scan_file_error'));

        try
        {
            set_time_limit(120);
            $uploads_scanner=new WPvivid_Uploads_Scanner();
            $uploads_scanner->init_scan_task();
            $uploads_files=array();

            $uploads_files[0]=$uploads_scanner->scan_sidebars_widgets();
            $files=$uploads_scanner->scan_termmeta_thumbnail();
            $uploads_files[0]=array_merge($uploads_files[0],$files);
            $files=$uploads_scanner->scan_divi_options();
            $uploads_files[0]=array_merge($uploads_files[0],$files);

            $site_icon_id = (int) get_option( 'site_icon' );
            if($site_icon_id)
            {
                $files=$uploads_scanner->get_attachment_size($site_icon_id);
                $uploads_files[0]=array_merge($uploads_files[0],$files);
            }
            $site_logo_id = get_option( 'site_logo' );
            if($site_logo_id)
            {
                $files=$uploads_scanner->get_attachment_size($site_logo_id);
                $uploads_files[0]=array_merge($uploads_files[0],$files);
            }

            if(defined('WPVIVID_PLUGIN_VERSION'))
            {
                if(version_compare(WPVIVID_PLUGIN_VERSION,'0.9.67','>='))
                {
                    $files=$uploads_scanner->scan_image_from_nextend();
                    $uploads_files[0]=array_merge($uploads_files[0],$files);
                }
            }

            $count=$uploads_scanner->get_post_count();
            $start=0;
            $limit=min(get_option('wpvivid_uc_scan_limit',20),$count);
            $posts=$uploads_scanner->get_posts($start,$limit);

            $start_time = time();
            $scan_post_count = 0;

            foreach ($posts as $post)
            {
                $media=$uploads_scanner->get_media_from_post_content($post);
                if(!empty($media))
                {
                    $uploads_files[$post]=$media;
                }
                $media=$uploads_scanner->get_media_from_post_meta($post);
                if(!empty($media))
                {
                    if(isset($uploads_files[$post]))
                        $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                    else
                        $uploads_files[$post]=$media;
                }

                $media=$uploads_scanner->get_media_from_post_meta_elementor($post);

                if(!empty($media))
                {
                    if(isset($uploads_files[$post]))
                        $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                    else
                        $uploads_files[$post]=$media;
                }

                $media=$uploads_scanner->get_media_from_post_custom_meta($post);

                if(!empty($media))
                {
                    if(isset($uploads_files[$post]))
                        $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                    else
                        $uploads_files[$post]=$media;
                }

                //fix theme WpResidence
                if (method_exists('WPvivid_Uploads_Scanner', 'get_media_from_wpresidence'))
                {
                    $media=$uploads_scanner->get_media_from_wpresidence($post);

                    if(!empty($media))
                    {
                        if(isset($uploads_files[$post]))
                            $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                        else
                            $uploads_files[$post]=$media;
                    }
                }

                //fix breakdance page builder
                if (method_exists('WPvivid_Uploads_Scanner', 'get_media_from_breakdance'))
                {
                    $media=$uploads_scanner->get_media_from_breakdance($post);

                    if(!empty($media))
                    {
                        if(isset($uploads_files[$post]))
                            $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                        else
                            $uploads_files[$post]=$media;
                    }
                }

                //oxygen images
                if (method_exists('WPvivid_Uploads_Scanner', 'get_media_from_oxygen'))
                {
                    $media=$uploads_scanner->get_media_from_oxygen($post);

                    if(!empty($media))
                    {
                        if(isset($uploads_files[$post]))
                            $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                        else
                            $uploads_files[$post]=$media;
                    }
                }

                $scan_post_count++;
                $current_time = time();
                if($current_time - $start_time >= 21)
                {
                    break;
                }
            }
            //$start+=$limit;
            $start+=$scan_post_count;

            $result['result']='success';
            if($count == 0){
                $result['percent']=0;
            }
            else{
                $result['percent']=intval(($start/$count)*100);
            }
            $result['total_posts']=$start;
            $result['scanned_posts']=$count;
            $result['descript']='Scanning files from posts';
            $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_posts'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_posts'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';


            if($start>=$count)
            {
                $uploads_scanner->update_scan_task($uploads_files,$start,'finished',100);
                $result['start']=$start;
                $result['status']='finished';
                $result['continue']=0;
                $result['log']='scan upload files finished'.PHP_EOL;
            }
            else
            {
                $uploads_scanner->update_scan_task($uploads_files,$start,'continue');
                $result['start']=$start;
                $result['status']='running';
                $result['continue']=1;
                $result['log']='scanned posts:'.$start.PHP_EOL.'total posts:'.$count.PHP_EOL;
            }

            echo json_encode($result);
            $this->end_shutdown_scan_file_function=true;
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            $this->end_shutdown_scan_file_function=true;
            die();
        }
        die();
    }

    public function scan_uploads_files_from_post()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $this->end_shutdown_scan_file_function=false;
        register_shutdown_function(array($this,'deal_shutdown_scan_file_error'));

        $task=get_option('scan_unused_files_task',array());
        $task['status']='running';
        update_option('scan_unused_files_task',$task,'no');

        set_time_limit(120);

        $uploads_scanner=new WPvivid_Uploads_Scanner();

        $count=$uploads_scanner->get_post_count();

        $start=intval($_POST['start']);

        $limit=min(get_option('wpvivid_uc_scan_limit',20),$count);

        $posts=$uploads_scanner->get_posts($start,$limit);

        $uploads_files=array();

        $start_time = time();
        $scan_post_count = 0;

        foreach ($posts as $post)
        {
            $media=$uploads_scanner->get_media_from_post_content($post);

            if(!empty($media))
            {
                $uploads_files[$post]=$media;
            }

            $media=$uploads_scanner->get_media_from_post_meta($post);

            if(!empty($media))
            {
                if(isset($uploads_files[$post]))
                    $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                else
                    $uploads_files[$post]=$media;
            }

            $media=$uploads_scanner->get_media_from_post_meta_elementor($post);

            if(!empty($media))
            {
                if(isset($uploads_files[$post]))
                    $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                else
                    $uploads_files[$post]=$media;
            }

            $media=$uploads_scanner->get_media_from_post_custom_meta($post);

            if(!empty($media))
            {
                if(isset($uploads_files[$post]))
                    $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                else
                    $uploads_files[$post]=$media;
            }

            //fix theme WpResidence
            if (method_exists('WPvivid_Uploads_Scanner', 'get_media_from_wpresidence'))
            {
                $media=$uploads_scanner->get_media_from_wpresidence($post);

                if(!empty($media))
                {
                    if(isset($uploads_files[$post]))
                        $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                    else
                        $uploads_files[$post]=$media;
                }
            }

            //fix breakdance page builder
            if (method_exists('WPvivid_Uploads_Scanner', 'get_media_from_breakdance'))
            {
                $media=$uploads_scanner->get_media_from_breakdance($post);

                if(!empty($media))
                {
                    if(isset($uploads_files[$post]))
                        $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                    else
                        $uploads_files[$post]=$media;
                }
            }

            //oxygen images
            if (method_exists('WPvivid_Uploads_Scanner', 'get_media_from_oxygen'))
            {
                $media=$uploads_scanner->get_media_from_oxygen($post);

                if(!empty($media))
                {
                    if(isset($uploads_files[$post]))
                        $uploads_files[$post]=array_merge($uploads_files[$post],$media);
                    else
                        $uploads_files[$post]=$media;
                }
            }

            $scan_post_count++;
            $current_time = time();
            if($current_time - $start_time >= 21)
            {
                break;
            }
        }

        //$start+=$limit;
        $start+=$scan_post_count;

        $percent = intval(($start/$count)*100);
        if($percent > 100)
        {
            $percent = 100;
        }

        $result['result']='success';
        $result['percent']=$percent;
        $result['total_posts']=$start;
        $result['scanned_posts']=$count;
        $result['descript']='Scanning files from posts';
        $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_posts'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_posts'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';

        if($start>=$count)
        {
            $uploads_scanner->update_scan_task($uploads_files,$start,'finished',100);
            $result['start']=$start;
            $result['status']='finished';
            $result['continue']=0;
            $result['log']='scan upload files finished'.PHP_EOL;
        }
        else
        {
            $uploads_scanner->update_scan_task($uploads_files,$start,'continue');
            $result['start']=$start;
            $result['status']='running';
            $result['continue']=1;
            $result['log']='scanned posts:'.$start.PHP_EOL.'total posts:'.$count.PHP_EOL;
        }

        $ret=$uploads_scanner->get_unused_uploads_progress();
        $result['total_folders']=$ret['total_folders'];
        $result['scanned_folders']=$ret['scanned_folders'];
        $result['percent']=$ret['percent'];

        echo json_encode($result);
        $this->end_shutdown_scan_file_function=true;
        die();
    }

    public function get_scan_upload_files_progress_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $task=get_option('scan_unused_files_task',array());

        $uploads_scanner=new WPvivid_Uploads_Scanner();
        $count=$uploads_scanner->get_post_count();

        if(isset($task['offset']))
        {
            $offset = $task['offset'];
            $percent = intval(($offset/$count)*100);
            if($percent > 100)
            {
                $percent = 100;
            }
        }
        else
        {
            $offset = 0;
            $percent = 0;
        }

        $result['result']='success';
        $result['percent']=$percent;
        $result['total_posts']=$offset;
        $result['scanned_posts']=$count;
        $result['start'] = $offset;
        $result['descript']='Scanning files from posts';
        $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_posts'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_posts'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';

        if(isset($task['status']))
        {
            $result['status'] = $task['status'];
            if($task['status'] === 'finished')
            {
                $result['result']='success';
                $result['log']='scan upload files finished'.PHP_EOL;
            }
            else if($task['status'] === 'failed')
            {
                $result['result']='failed';
                $result['log']='scan failed, error: '.$task['error'].PHP_EOL;
                $error='scan failed, error: '.$task['error'];
                $result['error'] =__('<div class="notice notice-error inline" style="margin-bottom: 5px;"><p>'.$error.'</p></div>');
            }
            else
            {
                $result['result']='success';
                $result['log']='scanned posts:'.$offset.PHP_EOL.'total posts:'.$count.PHP_EOL;
            }
        }
        else
        {
            $result['status'] = 'running';
        }

        echo json_encode($result);
        die();
    }

    public function start_unused_files_task()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        set_time_limit(120);

        $uploads_scanner=new WPvivid_Uploads_Scanner();

        $result=$uploads_scanner->get_folders();

        $uploads_scanner->init_unused_uploads_task($result['folders']);

        $result['result']='success';
        $result['status']='running';
        $result['continue']=1;
        $result['log']='scanning files'.PHP_EOL;

        $ret=$uploads_scanner->get_unused_uploads_progress();
        $result['total_folders']=$ret['total_folders'];
        $result['scanned_folders']=$ret['scanned_folders'];
        $result['percent']=$ret['percent'];
        $result['descript']='Scanning upload folder.';
        $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_folders'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_folders'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';
        //$result['.']=$files;
        echo json_encode($result);
        die();
    }

    public function unused_files_task()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $this->end_shutdown_unused_file_function=false;
        register_shutdown_function(array($this,'deal_shutdown_unused_file_error'));

        set_time_limit(120);

        $uploads_scanner=new WPvivid_Uploads_Scanner();

        $ret=$uploads_scanner->get_unfinished_folder();

        if($ret===false)
        {
            $uploads_scanner->update_unused_uploads_task(array(),'.',1,0,'finished',100);
            $result['result']='success';
            $result['status']='finished';
            $result['log']='scanning files finished'.PHP_EOL;
            $result['percent']=100;
            $result['continue']=0;
        }
        else
        {
            $start_time = time();

            $task=get_option('unused_uploads_task',array());
            $task['status']='running';
            update_option('unused_uploads_task',$task,'no');

            $size=0;
            $folder=$ret['folder'];
            $offset=$ret['offset'];
            $total=$ret['total'];
            $files=$uploads_scanner->get_files($folder);

            $upload_folder = wp_upload_dir();

            $root_path =$upload_folder['basedir'];

            $start=0;
            $count=0;
            $limit=get_option('wpvivid_uc_files_limit',100);

            $unused_files=array();
            foreach ($files as $file)
            {
                $current_time = time();
                if($current_time - $start_time >= 21)
                {
                    $uploads_scanner->update_unused_uploads_task($unused_files,$folder,0,$start,'continue',0,$size);

                    $result['result']='success';
                    $result['status']='running';
                    $result['continue']=1;
                    $task=get_option('unused_uploads_task',array());
                    $result['task']=$task;
                    $result[$folder]=$unused_files;
                    $result['log']='scanning folder '.$folder.PHP_EOL.'scanned files:'.$start.PHP_EOL;
                    $ret=$uploads_scanner->get_unused_uploads_progress();
                    $result['total_folders']=$ret['total_folders'];
                    $result['scanned_folders']=$ret['scanned_folders'];
                    $result['percent']=$ret['percent'];

                    $result['descript']='Scanning upload folder:'.$folder.'<br>'.$start.' files have been scanned in '.$total.' files';
                    $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_folders'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_folders'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';
                    echo json_encode($result);
                    $this->end_shutdown_unused_file_function=true;
                    die();
                }

                if($count>$limit)
                {
                    $uploads_scanner->update_unused_uploads_task($unused_files,$folder,0,$start,'continue',0,$size);

                    $result['result']='success';
                    $result['status']='running';
                    $result['continue']=1;
                    $task=get_option('unused_uploads_task',array());
                    $result['task']=$task;
                    $result[$folder]=$unused_files;
                    $result['log']='scanning folder '.$folder.PHP_EOL.'scanned files:'.$start.PHP_EOL;
                    $ret=$uploads_scanner->get_unused_uploads_progress();
                    $result['total_folders']=$ret['total_folders'];
                    $result['scanned_folders']=$ret['scanned_folders'];
                    $result['percent']=$ret['percent'];

                    $result['descript']='Scanning upload folder:'.$folder.'<br>'.$start.' files have been scanned in '.$total.' files';
                    $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_folders'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_folders'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';
                    echo json_encode($result);
                    $this->end_shutdown_unused_file_function=true;
                    die();
                }

                if($start>=$offset)
                {
                    if(!$uploads_scanner->is_uploads_files_exist($file))
                    {
                        $unused_files[]=$file;
                        $size+=filesize($root_path.DIRECTORY_SEPARATOR . $file);
                    }
                    $count++;
                }
                $start++;
            }

            $uploads_scanner->update_unused_uploads_task($unused_files,$folder,1,0,'continue',0,$size);

            $result['result']='success';
            $result['status']='running';
            $result['continue']=1;
            $result[$folder]=$unused_files;
            $result['log']='scanning folder '.$folder.PHP_EOL.'scanned files:'.$start.PHP_EOL;
            $ret=$uploads_scanner->get_unused_uploads_progress();
            $result['total_folders']=$ret['total_folders'];
            $result['scanned_folders']=$ret['scanned_folders'];
            $result['percent']=$ret['percent'];

            $upload_folder = wp_upload_dir();

            $result['descript']='Scanning upload folder:'.$folder.'<br>'.$start.' files have been scanned in '.$total.' files';
            $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_folders'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_folders'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';
        }
        echo json_encode($result);
        $this->end_shutdown_unused_file_function=true;
        die();
    }

    public function get_unused_files_progress_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $uploads_scanner=new WPvivid_Uploads_Scanner();
        $ret=$uploads_scanner->get_unfinished_folder();

        $size=0;
        $folder=$ret['folder'];
        $offset=$ret['offset'];
        $total=$ret['total'];

        $result['result']='success';
        //$result['status']='running';
        $result['continue']=1;
        //$result[$folder]=$unused_files;
        $result['log']='scanning folder '.$folder.PHP_EOL.'scanned files:'.$offset.PHP_EOL;
        $ret=$uploads_scanner->get_unused_uploads_progress();
        $result['total_folders']=$ret['total_folders'];
        $result['scanned_folders']=$ret['scanned_folders'];
        $result['percent']=$ret['percent'];
        $result['descript']='Scanning upload folder:'.$folder.'<br>'.$offset.' files have been scanned in '.$total.' files';
        $result['progress_html'] = '<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width:'.$result['percent'].'%">'.$result['percent'].'% completed</span></span></p>
                                        <p>
                                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>'.$result['total_folders'].'</span>
                                            <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>'.$result['scanned_folders'].'</span>
                                        </p>
                                        <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>'.$result['descript'].'</span></p>
                                        <div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';

        $task=get_option('unused_uploads_task',array());

        if(isset($task['status']))
        {
            $result['status'] = $task['status'];
            if($task['status'] === 'finished')
            {
                $result['result']='success';
                $result['log']='scan upload files finished'.PHP_EOL;
            }
            else if($task['status'] === 'failed')
            {
                $result['result']='failed';
                $result['log']='scan failed, error: '.$task['error'].PHP_EOL;
                $error='scan failed, error: '.$task['error'];
                $result['error'] =__('<div class="notice notice-error inline" style="margin-bottom: 5px;"><p>'.$error.'</p></div>');
            }
            else
            {
                $result['result']='success';
            }
        }
        else
        {
            $result['status']='running';
        }

        echo json_encode($result);
        die();
    }

    public function get_result_list()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');
        try
        {
            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $list=new WPvivid_Unused_Upload_Files_List_Ex();
            $scanner=new WPvivid_Uploads_Scanner();
            $result=$scanner->get_scan_result($search,$folder);

            if(isset($_POST['page']))
            {
                $list->set_list($result,$_POST['page']);
            }
            else
            {
                $list->set_list($result);
            }

            $list->prepare_items();
            ob_start();
            $list->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
            if(empty($result))
            {
                $ret['empty']=1;
            }
            else
            {
                $ret['empty']=0;
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

    public function add_exclude_files()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $json = $_POST['selected'];
        $json = stripslashes($json);
        $json = json_decode($json, true);

        $selected_list=$json['selected'];

        $sanitize_list=array();
        foreach ($selected_list as $item)
        {
            $sanitize_list[]=intval($item);
        }

        $scanner=new WPvivid_Uploads_Scanner();
        $files=$scanner->get_selected_files_list($sanitize_list);

        $list=new WPvivid_Unused_Upload_Files_List_Ex();

        if($files===false||empty($files))
        {

        }
        else
        {
            $options=get_option('wpvivid_uc_exclude_files_regex',array());

            $options=array_merge($files,$options);

            update_option('wpvivid_uc_exclude_files_regex',$options,'no');

            $scanner->delete_selected_files_list($sanitize_list);
        }


        $search='';
        if(isset($_POST['search']))
        {
            $search=$_POST['search'];
        }

        $folder='';
        if(isset($_POST['folder']))
        {
            $folder=$_POST['folder'];
        }

        $result=$scanner->get_scan_result($search,$folder);

        $list->set_list($result);

        $list->prepare_items();
        ob_start();
        $list->display();
        $html = ob_get_clean();

        $ret['result']='success';
        $ret['html']=$html;
        echo json_encode($ret);
        die();
    }

    public function isolate_selected_image()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        try
        {
            $json = $_POST['selected'];
            $json = stripslashes($json);
            $json = json_decode($json, true);

            $selected_list=$json['selected'];
            $sanitize_list=array();
            foreach ($selected_list as $item)
            {
                $sanitize_list[]=intval($item);
            }

            $scanner=new WPvivid_Uploads_Scanner();
            $files=$scanner->get_selected_files_list($sanitize_list);

            if($files===false||empty($files))
            {

            }
            else
            {
                $iso=new WPvivid_Isolate_Files();
                $result=$iso->isolate_files($files);

                if($result['result']=='success')
                {
                    $scanner->delete_selected_files_list($sanitize_list);
                }
                else
                {
                    echo json_encode($result);
                    die();
                }
            }


            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $list=new WPvivid_Unused_Upload_Files_List_Ex();
            $scanner=new WPvivid_Uploads_Scanner();
            $result=$scanner->get_scan_result($search,$folder);

            $list->set_list($result);

            $list->prepare_items();
            ob_start();
            $list->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;

            $list=new WPvivid_Isolate_Files_List_Ex();
            $iso=new WPvivid_Isolate_Files();
            $result=$iso->get_isolate_files($search,'');
            if(isset($_POST['page']))
            {
                $list->set_list($result,$_POST['page']);
            }
            else
            {
                $list->set_list($result);
            }

            $list->prepare_items();
            ob_start();
            $list->display();
            $iso = ob_get_clean();
            $ret['iso']=$iso;
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

    public function start_isolate_all_image()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        try
        {
            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $iso=new WPvivid_Isolate_Files();
            $scanner=new WPvivid_Uploads_Scanner();

            $offset=0;
            $count=100;

            $iso->init_isolate_task();
            $files=$scanner->get_all_files_list($search,$folder,$offset,$count);

            if($files===false||empty($files))
            {
                $iso->update_isolate_task(0,'finished',100);

                $result['result']='success';
                $result['status']='finished';
                $result['continue']=0;

                echo json_encode($result);
                die();
            }
            else
            {
                $offset+=$count;
                $result=$iso->isolate_files($files);

                $scanner->delete_all_files_list($search,$folder,$count);

                if($result['result']=='success')
                {
                    $iso->update_isolate_task($offset);
                }
                else
                {
                    echo json_encode($result);
                    die();
                }
            }

            $ret['result']='success';
            $ret['status']='running';
            $ret['continue']=1;
            echo json_encode($ret);
            die();
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function isolate_all_image()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');
        try
        {
            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $iso=new WPvivid_Isolate_Files();
            $scanner=new WPvivid_Uploads_Scanner();

            $offset=$iso->get_isolate_task_offset();

            if($offset===false)
            {
                $result['result']='success';
                $result['status']='finished';
                $result['continue']=0;

                echo json_encode($result);
                die();
            }
            $start=0;
            $count=100;
            $files=$scanner->get_all_files_list($search,$folder,$start,$count);

            if($files===false||empty($files))
            {
                $iso->update_isolate_task(0,'finished',100);

                $result['result']='success';
                $result['status']='finished';
                $result['continue']=0;

                echo json_encode($result);
                die();
            }
            else
            {
                $offset+=$count;
                $result=$iso->isolate_files($files);
                $scanner->delete_all_files_list($search,$folder,$count);

                if($result['result']=='success')
                {
                    $iso->update_isolate_task($offset);
                }
                else
                {
                    echo json_encode($result);
                    die();
                }
            }

            $ret['result']='success';
            $ret['status']='running';
            $ret['continue']=1;
            echo json_encode($ret);
            die();
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_iso_list()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        try
        {
            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $folder = str_replace('\\\\', '\\', $folder);

            $list=new WPvivid_Isolate_Files_List_Ex();
            $iso=new WPvivid_Isolate_Files();
            $result=$iso->get_isolate_files($search,$folder);
            if(isset($_POST['page']))
            {
                $list->set_list($result,$_POST['page']);
            }
            else
            {
                $list->set_list($result);
            }

            $list->prepare_items();
            ob_start();
            $list->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
            if(empty($result))
            {
                $ret['empty']=1;
            }
            else
            {
                $ret['empty']=0;
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

    public function delete_selected_image()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        try
        {
            set_time_limit(120);

            $json = $_POST['selected'];
            $json = stripslashes($json);
            $json = json_decode($json, true);

            $files=$json['selected'];

            $iso=new WPvivid_Isolate_Files();

            $iso->delete_files($files);

            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $folder = str_replace('\\\\', '\\', $folder);

            $list=new WPvivid_Isolate_Files_List_Ex();
            $iso=new WPvivid_Isolate_Files();
            $result=$iso->get_isolate_files($search,$folder);
            if(isset($_POST['page']))
            {
                $list->set_list($result,$_POST['page']);
            }
            else
            {
                $list->set_list($result);
            }

            $list->prepare_items();
            ob_start();
            $list->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
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

    public function delete_all_image()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $this->end_shutdown_delete_image_function=false;
        register_shutdown_function(array($this,'deal_shutdown_delete_image_error'));

        try
        {
            set_time_limit(120);

            $task = get_option('wpvivid_delete_unused_image_task', array());
            $task['status'] = 'running';
            update_option('wpvivid_delete_unused_image_task', $task, 'no');

            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $iso=new WPvivid_Isolate_Files();

            $count=1000;

            $files=$iso->get_isolate_files($search,$folder,$count);

            if($files===false||empty($files))
            {
                $task = get_option('wpvivid_delete_unused_image_task', array());
                $task['status'] = 'finished';
                update_option('wpvivid_delete_unused_image_task', $task, 'no');

                $result['result']='success';
                $result['status']='finished';
                $result['continue']=0;

                echo json_encode($result);
                $this->end_shutdown_delete_image_function=true;
                die();
            }
            else
            {
                $iso->delete_files_ex($files);
            }

            $task = get_option('wpvivid_delete_unused_image_task', array());
            $task['status'] = 'continue';
            update_option('wpvivid_delete_unused_image_task', $task, 'no');

            $ret['result']='success';
            $ret['status']='running';
            $ret['continue']=1;
            echo json_encode($ret);
            $this->end_shutdown_delete_image_function=true;
            die();
        }
        catch (Exception $error)
        {
            $task = get_option('wpvivid_delete_unused_image_task', array());
            $task['status'] = 'continue';
            update_option('wpvivid_delete_unused_image_task', $task, 'no');

            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            $this->end_shutdown_delete_image_function=true;
        }
        die();
    }

    public function get_delete_image_progress_ex()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        $ret['result']='success';

        $task = get_option('wpvivid_delete_unused_image_task', array());
        if(isset($task['status']))
        {
            $ret['status'] = $task['status'];
            if($task['status'] === 'finished')
            {
                $ret['result']='success';
            }
            else if($task['status'] === 'failed')
            {
                $ret['result']='failed';
                $error='scan failed, error: '.$task['error'];
                $ret['error'] =__('<div class="notice notice-error inline" style="margin-bottom: 5px;"><p>'.$error.'</p></div>');
            }
            else
            {
                $ret['result']='success';
            }
        }
        else
        {
            $ret['status'] = 'running';
        }

        echo json_encode($ret);
        die();
    }

    public function restore_selected_image()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        try
        {
            $json = $_POST['selected'];
            $json = stripslashes($json);
            $json = json_decode($json, true);

            $files=$json['selected'];

            $iso=new WPvivid_Isolate_Files();
            $iso->restore_files($files);

            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $folder = str_replace('\\\\', '\\', $folder);

            $list=new WPvivid_Isolate_Files_List_Ex();
            $iso=new WPvivid_Isolate_Files();
            $result=$iso->get_isolate_files($search,$folder);
            if(isset($_POST['page']))
            {
                $list->set_list($result,$_POST['page']);
            }
            else
            {
                $list->set_list($result);
            }

            $list->prepare_items();
            ob_start();
            $list->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
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

    public function restore_all_image()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-use-image-cleaner');

        try
        {
            $search='';
            if(isset($_POST['search']))
            {
                $search=$_POST['search'];
            }

            $folder='';
            if(isset($_POST['folder']))
            {
                $folder=$_POST['folder'];
            }

            $iso=new WPvivid_Isolate_Files();

            $count=100;

            $files=$iso->get_isolate_files($search,$folder,$count);

            if($files===false||empty($files))
            {
                $result['result']='success';
                $result['status']='finished';
                $result['continue']=0;

                echo json_encode($result);
                die();
            }
            else
            {
                $iso->restore_files_ex($files);
            }

            $ret['result']='success';
            $ret['status']='running';
            $ret['continue']=1;
            echo json_encode($ret);
            die();
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }
    /***** unused image cleaner ajax end *****/

    public function wpvivid_check_jet_engine()
    {
        if (!function_exists('get_plugins'))
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active_plugins = get_option('active_plugins');
        $plugins=get_plugins();
        $jet_engine_slug='jet-engine/jet-engine.php';

        if(!empty($plugins))
        {
            if(isset($plugins[$jet_engine_slug]))
            {
                if(in_array($jet_engine_slug, $active_plugins))
                {
                    _e('<div class="notice notice-warning inline" style="margin: 10px 0 0 0;"><p><strong>Warning:</strong> We detected that you use Jet Engine plugin on this site, 
                                                            it may have compatibility issues with our plugin, which can result in an inaccuracy of the scan result, 
                                                            so we recommend not using this feature yet.
                                                            </p></div>');
                }
            }
        }
    }

    public function init_page()
    {
        $scan=new WPvivid_Uploads_Scanner();
        $scan->check_table_exist();
        $scan->check_unused_uploads_files_table_exist();

        $abs=$this->transfer_path(ABSPATH);
        $upload_dir=wp_upload_dir();
        $med_path=$this->transfer_path($upload_dir['basedir']);
        $med_path=str_replace($abs,'...'.DIRECTORY_SEPARATOR,$med_path);

        $iso=new WPvivid_Isolate_Files();
        $iso_path=$this->transfer_path(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.WPVIVID_UPLOADS_ISO_DIR);
        $iso_path=str_replace($abs,'...'.DIRECTORY_SEPARATOR,$iso_path);
        $iso_path=apply_filters('wpvivid_white_label_log_name', $iso_path);

        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Unused Image Cleaner', 'wpvivid' ); ?></h1>
            <?php $this->wpvivid_check_jet_engine(); ?>
            <div id="wpvivid_scan_notice"></div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p></p>
                                        <div>
                                            <span class="dashicons dashicons-code-standards wpvivid-dashicons-large wpvivid-dashicons-green"></span>
                                            <span class="wpvivid-page-title">Unused Image Cleaner</span>
                                        </div>
                                        <p></p>
                                        <span class="about-description">Scan your media folder (uploads) to find unused images and isolate specific or all unused images.</span>
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
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span>Media library path</span><span><code><?php _e($med_path); ?></code></span>
                                        <span>, </span>
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span>Isolated folder Path</span><span><code><?php _e($iso_path); ?></code></span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $args['span_class']='dashicons dashicons-code-standards';
                                    $args['span_style']='color:#007cba; padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;display:block;';
                                    $args['is_parent_tab']=0;
                                    $tabs['scan']['title']='Scan';
                                    $tabs['scan']['slug']='scan';
                                    $tabs['scan']['callback']=array($this, 'output_scan');
                                    $tabs['scan']['args']=$args;

                                    $args['span_class']='dashicons dashicons-list-view';
                                    $args['span_style']='color:red;padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;
                                    $tabs['isolate']['title']='Media Isolated';
                                    $tabs['isolate']['slug']='isolate';
                                    $tabs['isolate']['callback']=array($this, 'output_isolate');
                                    $tabs['isolate']['args']=$args;

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
            jQuery(document).ready(function($) {
                <?php
                if (isset($_GET['tab']))
                {
                $tab=$_GET['tab'];
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show','<?php echo $tab?>');
                <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public function output_scan()
    {
        $scanner=new WPvivid_Uploads_Scanner();
        $count=$scanner->get_scan_result_count();
        $size=$scanner->get_scan_result_size();
        $total_size='';
        if($count===false) {
            $last_scan='';
            $total_size='';
        }
        else {
            $last_scan="<span>$count</span>";
            if($size!==false) {
                $total_size=$size;
            }
        }
        $upload_dir=wp_upload_dir();

        $path=$this->transfer_path($upload_dir['basedir']);
        $abs=$this->transfer_path(ABSPATH);

        $path=str_replace($abs,'...'.DIRECTORY_SEPARATOR,$path);

        $folders=$scanner->get_all_folder();
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $progress_bar='<p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress" style="width: 0%;">0% completed</span></span></p><p><span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span><span>Total Posts:</span><span>N/A</span><span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Scanned:</span><span>N/A</span></p><p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>Ready to scan</span></p><div><input class="button-primary" id="wpvivid_uc_cancel" type="submit" value="Cancel"></div>';

        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
            <div id="wpvivid_uc_scan">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span>Clicking the 'Scan' button to find unused images in your media folder. Currently it only scans JPG and PNG images.</span>
                <div class="wpvivid-two-col">
                    <!--<p><span class="dashicons dashicons-calendar wpvivid-dashicons-blue"></span><span><strong>Schedule:</strong></span><span class="wpvivid-rectangle wpvivid-grey">Disabled</span><span><a href="#">setting</a></span></p>-->
                    <!--<p><span class="dashicons dashicons-images-alt2 wpvivid-dashicons-green"></span><span><strong>Scanned Files:</strong></span><span>23423</span></p>-->
                    <p><span class="dashicons dashicons-category wpvivid-dashicons-blue"></span><span><strong>Scanned Images Total Size: </strong></span><?php _e($total_size); ?></p>
                    <p><span class="dashicons dashicons-code-standards wpvivid-dashicons-green"></span><span><strong>Unused Image(s) Found: </strong></span><?php _e($last_scan); ?></p>
                </div>
                <div class="wpvivid-two-col" style="text-align:center;">
                    <p><input class="button-primary" style="width: 200px; height: 50px; font-size: 20px;" id="wpvivid_start_scan" type="submit" value="Scan"></p>
                    <p><code>Note: Please don't refresh the page while scanning.</code></p>
                </div>
            </div>

            <div id="wpvivid_uc_progress" style="display: none;">
                <?php echo $progress_bar; ?>
            </div>
        </div>
        <div style="clear: both;"></div>

        <div id="wpvivid_scan_result_list" style="padding:1em 0 1em 0;">
            <?php
            $result=$scanner->get_scan_result('','');
            $list = new WPvivid_Unused_Upload_Files_List_Ex();
            $list->set_list($result);
            $list->prepare_items();
            $list ->display();
            ?>
        </div>

        <script>
            var wpvivid_cancel=false;
            var wpvivid_result_list_search='';
            var wpvivid_result_list_folder='';

            jQuery('#wpvivid_uc_progress').on("click",'#wpvivid_uc_cancel',function() {
                wpvivid_cancel_scan();
            });

            function wpvivid_cancel_scan() {
                wpvivid_cancel=true;
                jQuery('#wpvivid_uc_cancel').prop('disabled', true);
            }

            jQuery('#wpvivid_start_scan').click(function() {
                wpvivid_start_scan();
            });

            function wpvivid_start_scan() {
                jQuery('#wpvivid_scan_notice').hide();
                jQuery('#wpvivid_scan_notice').html('');

                jQuery('#wpvivid_uc_progress').show();

                jQuery('#wpvivid_uc_progress').html('<?php echo $progress_bar?>');
                jQuery('#wpvivid_uc_scan').hide();
                jQuery('#wpvivid_uc_cancel').prop('disabled', false);

                wpvivid_cancel=false;

                var ajax_data = {
                    'action': 'wpvivid_start_scan_uploads_files_task_ex'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_scan_upload_files_progress_ex();
                    }, 1000);
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_scan_upload_files_progress_ex();
                    }, 1000);
                });
            }

            function wpvivid_restart_scan(start) {
                if(wpvivid_cancel)
                {
                    jQuery('#wpvivid_uc_progress').hide();
                    jQuery('#wpvivid_uc_scan').show();
                    jQuery('#wpvivid_uc_cancel').prop('disabled', false);
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_scan_uploads_files_from_post_ex',
                    'start':start
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_scan_upload_files_progress_ex();
                    }, 1000);
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_scan_upload_files_progress_ex();
                    }, 1000);
                });
            }

            function wpvivid_get_scan_upload_files_progress_ex()
            {
                var ajax_data = {
                    'action':'wpvivid_get_scan_upload_files_progress_ex',
                };

                wpvivid_post_request(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_uc_progress').html(jsonarray.progress_html);

                            if(jsonarray.status=='continue')
                            {
                                wpvivid_restart_scan(jsonarray.start);
                            }
                            else if(jsonarray.status=='finished')
                            {
                                wpvivid_start_unused_files_task();
                            }
                            else if(jsonarray.status=='running')
                            {
                                setTimeout(function(){
                                    wpvivid_get_scan_upload_files_progress_ex();
                                }, 1000);
                            }
                            else if(jsonarray.status=='no_responds')
                            {
                                setTimeout(function(){
                                    wpvivid_get_scan_upload_files_progress_ex();
                                }, 1000);
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_uc_progress').hide();
                            jQuery('#wpvivid_uc_scan').show();
                            jQuery('#wpvivid_scan_notice').show();
                            jQuery('#wpvivid_scan_notice').html(jsonarray.error);
                        }
                    }
                    catch(err){
                        setTimeout(function()
                        {
                            wpvivid_get_scan_upload_files_progress_ex();
                        }, 3000);
                    }

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_scan_upload_files_progress_ex();
                    }, 3000);
                });
            }

            function wpvivid_start_unused_files_task() {
                if(wpvivid_cancel)
                {
                    jQuery('#wpvivid_uc_progress').hide();
                    jQuery('#wpvivid_uc_scan').show();
                    jQuery('#wpvivid_uc_cancel').prop('disabled', false);
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_start_unused_files_task_ex'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_uc_progress').html(jsonarray.progress_html);
                            if(jsonarray.continue)
                            {
                                wpvivid_unused_files_task();
                            }
                            else
                            {
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner'); ?>';
                            }
                        }
                        else if (jsonarray.result === 'failed')
                        {
                            alert(jsonarray.error);
                            jQuery('#wpvivid_uc_progress').hide();
                            jQuery('#wpvivid_uc_scan').show();
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        jQuery('#wpvivid_uc_progress').hide();
                        jQuery('#wpvivid_uc_scan').show();
                    }

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('scan files', textStatus, errorThrown);
                    alert(error_message);

                    jQuery('#wpvivid_uc_progress').hide();
                    jQuery('#wpvivid_uc_scan').show();
                });
            }

            function wpvivid_unused_files_task() {
                if(wpvivid_cancel)
                {
                    jQuery('#wpvivid_uc_progress').hide();
                    jQuery('#wpvivid_uc_scan').show();
                    jQuery('#wpvivid_uc_cancel').prop('disabled', false);
                    jQuery('#wpvivid_uc_scan_log').html("");
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_unused_files_task_ex'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_unused_files_progress_ex();
                    }, 1000);
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_unused_files_progress_ex();
                    }, 1000);
                });
            }

            function wpvivid_get_unused_files_progress_ex()
            {
                var ajax_data = {
                    'action':'wpvivid_get_unused_files_progress_ex',
                };

                wpvivid_post_request(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_uc_progress').html(jsonarray.progress_html);

                            if(jsonarray.status=='continue')
                            {
                                wpvivid_unused_files_task();
                            }
                            else if(jsonarray.status=='finished')
                            {
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner'); ?>';
                            }
                            else if(jsonarray.status=='running')
                            {
                                setTimeout(function(){
                                    wpvivid_get_unused_files_progress_ex();
                                }, 1000);
                            }
                            else if(jsonarray.status=='no_responds')
                            {
                                setTimeout(function(){
                                    wpvivid_get_unused_files_progress_ex();
                                }, 1000);
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_uc_progress').hide();
                            jQuery('#wpvivid_uc_scan').show();
                            jQuery('#wpvivid_scan_notice').show();
                            jQuery('#wpvivid_scan_notice').html(jsonarray.error);
                        }
                    }
                    catch(err){
                        setTimeout(function()
                        {
                            wpvivid_get_unused_files_progress_ex();
                        }, 3000);
                    }

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_scan_upload_files_progress_ex();
                    }, 3000);
                });
            }

            jQuery('#wpvivid_scan_result_list').on('click', '#wpvivid_result_list_search_btn', function() {
                wpvivid_result_list_search=jQuery('#wpvivid_result_list_search').val();
                wpvivid_result_list_folder=jQuery('#wpvivid_result_list_folder').val();
                if(wpvivid_result_list_folder=='0')
                {
                    wpvivid_result_list_folder='';
                }

                if(wpvivid_result_list_folder=='root')
                {
                    wpvivid_result_list_folder='.';
                }

                wpvivid_get_result_list('first');
            });

            function wpvivid_get_result_list(page) {
                var ajax_data = {
                    'action': 'wpvivid_get_result_list_ex',
                    'page':page,
                    'search':wpvivid_result_list_search,
                    'folder':wpvivid_result_list_folder
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.empty)
                            {
                                jQuery('#wpvivid_result_list_search').val('');
                                wpvivid_result_list_search='';
                                alert('No items found.');
                                //jQuery('#wpvivid_scan_result_list').html(old_html);
                            }
                            else
                            {
                                jQuery('#wpvivid_scan_result_list').html(jsonarray.html);
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
                    var error_message = wpvivid_output_ajaxerror('get list', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_scan_result_list').on("click",'.wpvivid-no-item',function() {
                wpvivid_result_list_search='';
                jQuery('#wpvivid_result_list_search').val('');
                wpvivid_get_result_list('first');
            });

            jQuery('#wpvivid_scan_result_list').on("click",'.first-page',function() {
                wpvivid_get_result_list('first');
            });

            jQuery('#wpvivid_scan_result_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_result_list(page-1);
            });

            jQuery('#wpvivid_scan_result_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_result_list(page+1);
            });

            jQuery('#wpvivid_scan_result_list').on("click",'.last-page',function() {
                wpvivid_get_result_list('last');
            });

            jQuery('#wpvivid_scan_result_list').on("keypress", '.current-page', function() {
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_get_result_list(page);
                }
            });

            jQuery('#wpvivid_scan_result_list').on("click", '#wpvivid_isolate_image',function() {
                var selected=jQuery('#wpvivid_uc_bulk_action').val();

                if(selected=='wpvivid_isolate_selected_image')
                {
                    wpvivid_isolate_selected_image();
                }
                else if(selected=='wpvivid_isolate_list_image')
                {
                    wpvivid_start_isolate_all_image();
                }
                else if(selected=='wpvivid_ignore_selected_image')
                {
                    wpvivid_ignore_selected_image();
                }
            });

            function wpvivid_ignore_selected_image() {
                var json = {};
                json['selected']=Array();
                jQuery('input[name=uploads][type=checkbox]').each(function(index, value)
                {
                    if(jQuery(value).prop('checked'))
                    {
                        json['selected'].push(jQuery(value).val())
                    }
                });
                var selected= JSON.stringify(json);

                jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', true);
                //jQuery('#wpvivid_isolate_selected_image').prop('disabled', true);
                //jQuery('#wpvivid_isolate_list_image').prop('disabled', true);
                var ajax_data = {
                    'action': 'wpvivid_uc_add_exclude_files_ex',
                    'selected':selected,
                    'search':wpvivid_result_list_search,
                    'folder':wpvivid_result_list_folder
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);

                    jQuery('#wpvivid_scan_result_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_scan_result_list').html(jsonarray.html);
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
                    jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                    var error_message = wpvivid_output_ajaxerror('add options', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_isolate_selected_image() {
                var json = {};
                json['selected']=Array();
                jQuery('input[name=uploads][type=checkbox]').each(function(index, value)
                {
                    if(jQuery(value).prop('checked'))
                    {
                        json['selected'].push(jQuery(value).val())
                    }
                });
                var selected= JSON.stringify(json);

                //jQuery('#wpvivid_isolate_selected_image').prop('disabled', true);
                //jQuery('#wpvivid_isolate_list_image').prop('disabled', true);
                jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', true);
                var ajax_data = {
                    'action': 'wpvivid_isolate_selected_image_ex',
                    'selected':selected,
                    'search':wpvivid_result_list_search,
                    'folder':wpvivid_result_list_folder
                };
                jQuery('#wpvivid_isolate_progress').show();
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_isolate_progress').hide();
                    //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                    jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                    jQuery('#wpvivid_scan_result_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_scan_result_list').html(jsonarray.html);
                            jQuery('#wpvivid_iso_files_list').html(jsonarray.iso);
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
                    jQuery('#wpvivid_isolate_progress').hide();
                    jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                    var error_message = wpvivid_output_ajaxerror('add isolate files', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_start_isolate_all_image() {
                var ajax_data = {
                    'action': 'wpvivid_start_isolate_all_image_ex',
                    'search':wpvivid_result_list_search,
                    'folder':wpvivid_result_list_folder
                };
                jQuery('#wpvivid_isolate_progress').show();
                //jQuery('#wpvivid_isolate_selected_image').prop('disabled', true);
                //jQuery('#wpvivid_isolate_list_image').prop('disabled', true);
                jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', true);
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_isolate_progress').hide();
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.continue)
                            {
                                wpvivid_isolate_all_image();
                            }
                            else
                            {
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner'); ?>';
                            }
                        }
                        else if (jsonarray.result === 'failed')
                        {
                            alert(jsonarray.error);
                            jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                            //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                            //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                        //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                        //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_isolate_progress').hide();
                    jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);

                    var error_message = wpvivid_output_ajaxerror('add isolate files', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_isolate_all_image() {
                var ajax_data = {
                    'action': 'wpvivid_isolate_all_image_ex',
                    'search':wpvivid_result_list_search,
                    'folder':wpvivid_result_list_folder
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.continue)
                            {
                                wpvivid_isolate_all_image();
                            }
                            else
                            {
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner'); ?>';
                            }
                        }
                        else if (jsonarray.result === 'failed')
                        {
                            alert(jsonarray.error);
                            jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                            //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                            //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                        //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                        //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                    }

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('add isolate files', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#wpvivid_scan_result_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_isolate_list_image').prop('disabled', false);
                });
            }

            jQuery('#wpvivid_rescan').click(function() {
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show','scan');
            });

            jQuery(document).ready(function($) {
                jQuery('#wpvivid_uc_scan').show();
                jQuery('#wpvivid_uc_progress').hide();
            });
        </script>
        <?php
    }

    public function output_isolate()
    {
        $iso=new WPvivid_Isolate_Files();
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span>Note: Once deleted, images will be lost permanently. The action cannot be undone, unless you have <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore'); ?>">a backup</a> in place.
        </div>
        <div style="clear: both;"></div>
        <div id="wpvivid_iso_files_list" style="padding:1em 0 1em 0">
            <?php
            $files=$iso->get_isolate_files();
            $list = new WPvivid_Isolate_Files_List_Ex();

            $list->set_list($files);
            $list->prepare_items();
            $list ->display();
            ?>
        </div>

        <script>
            var wpvivid_iso_list_search='';
            var wpvivid_iso_list_folder='';

            jQuery('#wpvivid_iso_files_list').on('click', '#wpvivid_iso_list_search_btn', function(){
                wpvivid_iso_list_search=jQuery('#wpvivid_iso_list_search').val();
                wpvivid_iso_list_folder=jQuery('#wpvivid_iso_list_folder').val();
                if(wpvivid_iso_list_folder=='0')
                {
                    wpvivid_iso_list_folder='';
                }

                if(wpvivid_iso_list_folder=='root')
                {
                    wpvivid_iso_list_folder='.';
                }

                wpvivid_get_iso_list('first');
            });

            function wpvivid_get_iso_list(page) {
                var ajax_data = {
                    'action': 'wpvivid_get_iso_list_ex',
                    'page':page,
                    'search':wpvivid_iso_list_search,
                    'folder':wpvivid_iso_list_folder
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    //jQuery('#wpvivid_iso_files_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.empty)
                            {
                                jQuery('#wpvivid_iso_list_search').val('');
                                wpvivid_iso_list_search='';
                                alert('No items found.');
                            }
                            else
                            {
                                jQuery('#wpvivid_iso_files_list').html(jsonarray.html);
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
                    var error_message = wpvivid_output_ajaxerror('get list', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_iso_files_list').on("click",'.first-page',function() {
                wpvivid_get_iso_list('first');
            });

            jQuery('#wpvivid_iso_files_list').on("click",'.wpvivid-no-item',function() {
                wpvivid_iso_list_search='';
                jQuery('#wpvivid_iso_files_list').val('');
                wpvivid_get_iso_list('first');
            });

            jQuery('#wpvivid_iso_files_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_iso_list(page-1);
            });

            jQuery('#wpvivid_iso_files_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_iso_list(page+1);
            });

            jQuery('#wpvivid_iso_files_list').on("click",'.last-page',function() {
                wpvivid_get_iso_list('last');
            });

            jQuery('#wpvivid_iso_files_list').on("keypress", '.current-page', function() {
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_get_iso_list(page);
                }
            });

            jQuery('#wpvivid_iso_files_list').on("click", '#wpvivid_restore_delete_image',function() {
                var selected=jQuery('#wpvivid_uc_iso_bulk_action').val();

                if(selected=='wpvivid_delete_selected_image')
                {
                    wpvivid_delete_selected_image();
                }
                else if(selected=='wpvivid_delete_list_image')
                {
                    wpvivid_start_delete_all_image();
                }
                else if(selected=='wpvivid_restore_selected_image')
                {
                    wpvivid_restore_selected_image();
                }
                else if(selected=='wpvivid_restore_list_image')
                {
                    wpvivid_start_restore_all_image();
                }
            });

            function wpvivid_delete_selected_image() {
                var json = {};
                json['selected']=Array();
                jQuery('input[name=uploads][type=checkbox]').each(function(index, value)
                {
                    if(jQuery(value).prop('checked'))
                    {
                        jQuery(value).closest('tr');
                        var path = jQuery(this).closest('tr').attr('path');
                        json['selected'].push(path)
                    }
                });
                var selected= JSON.stringify(json);
                jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', true);

                var ajax_data = {
                    'action': 'wpvivid_delete_selected_image_ex',
                    'selected':selected,
                    'search':wpvivid_iso_list_search,
                    'folder':wpvivid_iso_list_folder
                };
                jQuery('#wpvivid_restore_delete_progress').show();
                jQuery('#wpvivid_restore_delete_text').html('Deleting images...');
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);

                    jQuery('#wpvivid_iso_files_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_iso_files_list').html(jsonarray.html);
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
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_delete_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_delete_list_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_list_image').prop('disabled', false);

                    var error_message = wpvivid_output_ajaxerror('delete files', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_start_delete_all_image() {
                var ajax_data = {
                    'action': 'wpvivid_start_delete_all_image_ex',
                    'search':wpvivid_iso_list_search,
                    'folder':wpvivid_iso_list_folder
                };
                jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', true);
                jQuery('#wpvivid_restore_delete_progress').show();
                jQuery('#wpvivid_restore_delete_text').html('Deleting images...');
                jQuery('#wpvivid_scan_notice').hide();
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    setTimeout(function()
                    {
                        wpvivid_get_delete_image_progress_ex();
                    }, 1000);
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    setTimeout(function()
                    {
                        wpvivid_get_delete_image_progress_ex();
                    }, 1000);
                });
            }

            function wpvivid_delete_all_image() {
                var ajax_data = {
                    'action': 'wpvivid_delete_all_image_ex',
                    'search':wpvivid_iso_list_search,
                    'folder':wpvivid_iso_list_folder
                };
                jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', true);

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_delete_image_progress_ex();
                    }, 1000);
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_delete_image_progress_ex();
                    }, 1000);
                });
            }

            function wpvivid_get_delete_image_progress_ex()
            {
                var ajax_data = {
                    'action':'wpvivid_get_delete_image_progress_ex',
                };

                wpvivid_post_request(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.status=='continue')
                            {
                                wpvivid_delete_all_image();
                            }
                            else if(jsonarray.status=='finished')
                            {
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner').'&tab=isolate'; ?>';
                            }
                            else if(jsonarray.status=='running')
                            {
                                setTimeout(function(){
                                    wpvivid_get_delete_image_progress_ex();
                                }, 1000);
                            }
                            else if(jsonarray.status=='no_responds')
                            {
                                setTimeout(function(){
                                    wpvivid_get_delete_image_progress_ex();
                                }, 1000);
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                            jQuery('#wpvivid_scan_notice').show();
                            jQuery('#wpvivid_scan_notice').html(jsonarray.error);
                        }
                    }
                    catch(err){
                        setTimeout(function()
                        {
                            wpvivid_get_delete_image_progress_ex();
                        }, 3000);
                    }

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function()
                    {
                        wpvivid_get_scan_upload_files_progress_ex();
                    }, 3000);
                });
            }

            function wpvivid_restore_selected_image() {
                var json = {};
                json['selected']=Array();
                jQuery('input[name=uploads][type=checkbox]').each(function(index, value)
                {
                    if(jQuery(value).prop('checked'))
                    {
                        jQuery(value).closest('tr');
                        var path = jQuery(this).closest('tr').attr('path');
                        json['selected'].push(path)
                    }
                });
                var selected= JSON.stringify(json);
                jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', true);

                var ajax_data = {
                    'action': 'wpvivid_restore_selected_image_ex',
                    'selected':selected,
                    'search':wpvivid_iso_list_search,
                    'folder':wpvivid_iso_list_folder
                };
                jQuery('#wpvivid_restore_delete_progress').show();
                jQuery('#wpvivid_restore_delete_text').html('Restoring images...');
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);

                    jQuery('#wpvivid_iso_files_list').html('');
                    try
                    {

                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_iso_files_list').html(jsonarray.html);
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
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_delete_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_delete_list_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_list_image').prop('disabled', false);

                    var error_message = wpvivid_output_ajaxerror('restore files', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_start_restore_all_image() {
                var ajax_data = {
                    'action': 'wpvivid_start_restore_all_image_ex',
                    'search':wpvivid_iso_list_search,
                    'folder':wpvivid_iso_list_folder
                };
                jQuery('#wpvivid_restore_delete_progress').show();
                jQuery('#wpvivid_restore_delete_text').html('Restoring images...');
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.continue)
                            {
                                wpvivid_restore_all_image();
                            }
                            else
                            {
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner').'&tab=isolate'; ?>';
                            }
                        }
                        else if (jsonarray.result === 'failed')
                        {
                            alert(jsonarray.error);
                            jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                        //jQuery('#wpvivid_delete_selected_image').prop('disabled', false);
                        //jQuery('#wpvivid_delete_list_image').prop('disabled', false);
                        //jQuery('#wpvivid_restore_selected_image').prop('disabled', false);
                        //jQuery('#wpvivid_restore_list_image').prop('disabled', false);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_restore_delete_progress').hide();
                    jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_delete_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_delete_list_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_list_image').prop('disabled', false);

                    var error_message = wpvivid_output_ajaxerror('restore files', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_restore_all_image() {
                var ajax_data = {
                    'action': 'wpvivid_restore_all_image_ex',
                    'search':wpvivid_iso_list_search,
                    'folder':wpvivid_iso_list_folder
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.continue)
                            {
                                wpvivid_restore_all_image();
                            }
                            else
                            {
                                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-image-cleaner', 'wpvivid-image-cleaner').'&tab=isolate'; ?>';
                            }
                        }
                        else if (jsonarray.result === 'failed')
                        {
                            alert(jsonarray.error);
                            jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                            //jQuery('#wpvivid_delete_selected_image').prop('disabled', false);
                            //jQuery('#wpvivid_delete_list_image').prop('disabled', false);
                            //jQuery('#wpvivid_restore_selected_image').prop('disabled', false);
                            //jQuery('#wpvivid_restore_list_image').prop('disabled', false);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                    }

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('restore files', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#wpvivid_iso_files_list').find('.action').prop('disabled', false);
                    //jQuery('#wpvivid_delete_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_delete_list_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_selected_image').prop('disabled', false);
                    //jQuery('#wpvivid_restore_list_image').prop('disabled', false);
                });
            }
        </script>
        <?php
    }


    public function scan_exclude_files_regex($regex)
    {
        $files=get_option('wpvivid_uc_exclude_files_regex','');
        if(!empty($files))
        {
            /*foreach ($files as $file)
            {
                $regex[]='#'.$file.'$#';
            }*/
            $files=explode("\n", $files);
            foreach ($files as $item)
            {
                if(!empty($item))
                {
                    $item=rtrim($item, '/');
                    $regex[]='#'.preg_quote($this -> transfer_path($item), '/').'#';
                }
            }
        }
        $ignore_webp=get_option('wpvivid_uc_ignore_webp',false);
        if($ignore_webp)
        {
            $regex[]='/\b(\.webp|\.WEBP)\b/';
        }

        return $regex;
    }

    public function scan_include_files_regex($regex)
    {
        $default_file_types=array();
        $default_file_types[]='png';
        $default_file_types[]='jpg';
        $default_file_types[]='jpeg';
        $scan_file_types=get_option('wpvivid_uc_scan_file_types',$default_file_types);

        $regex=array();
        foreach ($scan_file_types as $scan_file_type)
        {
            $regex[]='#.*\.'.$scan_file_type.'#';
        }

        return $regex;
    }
}