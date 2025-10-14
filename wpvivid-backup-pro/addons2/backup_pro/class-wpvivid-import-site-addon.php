<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Admin_load: yes
 * Need_init: yes
 * Interface Name: WPvivid_Import_Site_Page_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPvivid_Import_Backup_List extends WP_List_Table
{
    public $page_num;
    public $backup_list;
    public $soft_content;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'import_backup',
                'screen' => 'import_backup'
            )
        );
        $this->soft_content=false;
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

    public function get_columns()
    {
        $columns = array();
        $columns['wpvivid_backup'] = __( 'Backup', 'wpvivid' );
        return $columns;
    }

    public function _column_wpvivid_backup( $backup )
    {
        $localtime = $backup['create_time'];

        $offset=get_option('gmt_offset');
        $utc_time = $backup['create_time'] - $offset * 60 * 60;

        if(isset($backup['backup_prefix']) && !empty($backup['backup_prefix']))
        {
            $backup_prefix = $backup['backup_prefix'];
        }
        else{
            $backup_prefix = 'N/A';
        }

        $size=0;
        foreach ($backup['backup']['files'] as $file)
        {
            $size+=$file['size'];
        }
        $size=size_format($size,2);

        $html='
                    <div id="'.$backup['id'].'" type-string="'.$backup['content_detail'].'" backup-time="'.date('M-d-Y H:i', $localtime).'" backup-type="'.$backup['type'].'" backup-comment="'.$backup_prefix.'" backup-size="'.$size.'" style="border-bottom:1px solid #cccccc;">
                        <p><span class="dashicons dashicons-format-aside wpvivid-dashicons-orange"></span>
                            <span title="UTC:'.date('M-d-Y H:i', $utc_time).'"><strong>'.__(date('M-d-Y H:i', $localtime)).'</strong></span>
                            <span style="float:right;"><label><span class="wpvivid-restore-backup" style="cursor:pointer;"><span class="dashicons dashicons-update wpvivid-dashicons-orange"></span><span><strong>Restore</strong></span></label></span></span>
                        </p>
                        <p style="padding-left:2em;"><span><strong>Label: </strong></span><span>'.$backup_prefix.'</span>
                        <span style="padding:0 1em;"></span>
                        <span><strong>Size: </strong></span><span>'.$size.'</span></p>
					</div>
                ';
        echo $html;
    }

    public function set_backup_list($backup_list,$page_num=1,$soft_content=false)
    {
        $this->backup_list=$backup_list;
        $this->page_num=$page_num;
        $this->soft_content=$soft_content;
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

        $total_items =sizeof($this->backup_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->backup_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->backup_list,$this->soft_content);
    }

    public function get_backup_content($backup)
    {
        $ret['content_detail'] = 'Please download it to localhost for identification.';
        $ret['content'] = 'All';
        $has_db = false;
        $has_file = false;
        $type_list = array();
        $ismerge = false;
        //ismerge ( not all )
        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                $file_name = $value['file_name'];
                if(WPvivid_backup_pro_function::is_wpvivid_db_backup($file_name))
                {
                    $has_db = true;
                    if(!in_array('Database', $type_list)) {
                        $type_list[] = 'Database';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_themes_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Themes', $type_list)) {
                        $type_list[] = 'Themes';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_plugin_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Plugins', $type_list)) {
                        $type_list[] = 'Plugins';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_uploads_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('wp-content/uploads', $type_list)) {
                        $type_list[] = 'wp-content/uploads';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_content_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('wp-content', $type_list)) {
                        $type_list[] = 'wp-content';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_core_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Wordpress Core', $type_list)) {
                        $type_list[] = 'Wordpress Core';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_other_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Additional Folder', $type_list)) {
                        $type_list[] = 'Additional Folder';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_additional_db_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Additional Database', $type_list)) {
                        $type_list[] = 'Additional Database';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_all_backup($file_name))
                {
                    $ismerge = true;
                }
            }
        }
        //all
        if($ismerge)
        {
            $backup_id = $backup['key'];
            $backup_list=new WPvivid_New_BackupList();
            $backup = $backup_list->get_backup_by_id($backup_id);
            $backup_item = new WPvivid_New_Backup_Item($backup);
            $files=$backup_item->get_files(false);
            $files_info=array();
            foreach ($files as $file)
            {
                $files_info[$file]=$backup_item->get_file_info($file);
            }
            $info=array();
            foreach ($files_info as $file_name=>$file_info)
            {
                if(isset($file_info['has_child']))
                {
                    if(isset($file_info['child_file']))
                    {
                        foreach ($file_info['child_file'] as $child_file_name=>$child_file_info)
                        {
                            if(isset($child_file_info['file_type']))
                            {
                                $info['type'][] = $child_file_info['file_type'];
                            }
                        }
                    }
                }
                else {
                    if(isset($file_info['file_type']))
                    {
                        $info['type'][] = $file_info['file_type'];
                    }
                }
            }

            if(isset($info['type']))
            {
                foreach ($info['type'] as $backup_content)
                {
                    if ($backup_content === 'databases')
                    {
                        $has_db = true;
                        if(!in_array('Database', $type_list))
                        {
                            $type_list[] = 'Database';
                        }
                    }
                    if($backup_content === 'themes')
                    {
                        $has_file = true;
                        if(!in_array('Themes', $type_list))
                        {
                            $type_list[] = 'Themes';
                        }
                    }
                    if($backup_content === 'plugin')
                    {
                        $has_file = true;
                        if(!in_array('Plugins', $type_list))
                        {
                            $type_list[] = 'Plugins';
                        }
                    }
                    if($backup_content === 'upload')
                    {
                        $has_file = true;
                        if(!in_array('wp-content/uploads', $type_list))
                        {
                            $type_list[] = 'wp-content/uploads';
                        }
                    }
                    if($backup_content === 'wp-content')
                    {
                        $has_file = true;
                        if(!in_array('wp-content', $type_list))
                        {
                            $type_list[] = 'wp-content';
                        }
                    }
                    if($backup_content === 'wp-core')
                    {
                        $has_file = true;
                        if(!in_array('Wordpress Core', $type_list))
                        {
                            $type_list[] = 'Wordpress Core';
                        }
                    }
                    if($backup_content === 'custom')
                    {
                        $has_file = true;
                        if(!in_array('Additional Folder', $type_list))
                        {
                            $type_list[] = 'Additional Folder';
                        }
                    }
                    if($backup_content === 'additional_databases')
                    {
                        $has_file = true;
                        if(!in_array('Additional Database', $type_list))
                        {
                            $type_list[] = 'Additional Database';
                        }
                    }
                }
            }
        }

        if($has_db){
            $type_string = implode(",", $type_list);
            $ret['content_detail'] = $type_string;
            $ret['content'] = 'Database Only';
        }
        if($has_file){
            $type_string = implode(",", $type_list);
            $ret['content_detail'] = $type_string;
            $ret['content'] = 'WordPress Files Only';
        }
        if($has_db && $has_file){
            $type_string = implode(",", $type_list);
            $ret['content_detail'] = $type_string;
            $ret['content'] = 'Database & WordPress Files';
        }
        if(!$has_db && !$has_file)
        {
            if(isset($files) && !empty($files))
            {
                foreach ($files as $file)
                {
                    if (WPvivid_backup_pro_function::is_wpvivid_backup($file))
                    {
                        if (WPvivid_backup_pro_function::is_wpvivid_db_backup($file))
                        {
                            $has_db = true;
                            $type_list[] = 'Database';
                        } else {
                            $has_file = true;
                        }
                    }
                }
            }
            if($has_db && !$has_file){
                $type_string = implode(",", $type_list);
                $ret['content_detail'] = $type_string;
                $ret['content'] = 'Database Only';
            }
            else {
                $ret['content_detail'] = 'Please download it to localhost for identification.';
                $ret['content'] = 'All';
            }
        }
        return $ret;
    }

    private function _display_rows($backup_list,$soft_content=false)
    {
        $page=$this->get_pagenum();

        $page_backup_list=array();
        $temp_page_backup_list=array();

        foreach ( $backup_list as $key=>$backup)
        {
            $backup['key']=$key;
            $content_info = $this->get_backup_content($backup);
            $backup['content']=$content_info['content'];
            $backup['content_detail']=$content_info['content_detail'];
            $page_backup_list[$key]=$backup;
        }

        if($soft_content)
        {
            usort($page_backup_list, function ($a, $b)
            {
                if($a['content']!=$b['content'])
                {
                    if($a['content']=='All'||$a['content']=='Database & WordPress Files')
                    {
                        return -1;
                    }
                    else if($a['content']=='WordPress Files Only'&&$b['content']=='Database Only')
                    {
                        return -1;
                    }
                    else if($a['content']=='WordPress Files Only'&&$b['content']!='Database Only')
                    {
                        return 1;
                    }
                    else if($a['content']=='Database Only')
                    {
                        return 1;
                    }
                    else
                    {
                        if ($a['create_time'] == $b['create_time'])
                        {
                            return 0;
                        }

                        if($a['create_time'] > $b['create_time'])
                        {
                            return -1;
                        }
                        else
                        {
                            return 1;
                        }
                    }
                }
                else
                {
                    if ($a['create_time'] == $b['create_time'])
                    {
                        return 0;
                    }

                    if($a['create_time'] > $b['create_time'])
                    {
                        return -1;
                    }
                    else
                    {
                        return 1;
                    }
                }
            });
        }
        else
        {
            usort($page_backup_list, function ($a, $b)
            {
                if ($a['create_time'] == $b['create_time'])
                {
                    return 0;
                }

                if($a['create_time'] > $b['create_time'])
                {
                    return -1;
                }
                else
                {
                    return 1;
                }
            });
        }


        $count=0;
        while ( $count<$page )
        {
            $temp_page_backup_list = array_splice( $page_backup_list, 0, 10);
            $count++;
        }

        foreach ( $temp_page_backup_list as $key=>$backup)
        {
            //$backup['key']=$key;
            $this->single_row($backup);
        }
    }

    public function single_row($backup)
    {
        $row_style = 'display: table-row;';
        $class='';
        if ($backup['type'] == 'Migration' || $backup['type'] == 'Upload')
        {
            $class .= 'wpvivid-upload-tr';
        }
        ?>
        <tr style="<?php echo $row_style?>" class='wpvivid-backup-row <?php echo $class?>' id="<?php echo $backup['key'];?>">
            <?php $this->single_row_columns( $backup ); ?>
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
            /*$html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector-backuplist' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-backuplist" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';*/
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector-backuplist' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-backuplist" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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


            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo " data-wp-lists='list:$singular'";
                }
                ?>
            >
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>


        <?php
        $this->display_tablenav( 'bottom' );
    }
}

class WPvivid_Import_Site_Page_addon
{
    public $main_tab;

    public function __construct()
    {
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),12);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);
        add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));
        //ajax
        add_action('wp_ajax_wpvivid_reload_remote_backup', array($this, 'reload_remote_backup'));
        add_action('wp_ajax_wpvivid_reload_migration_backup', array($this, 'reload_migration_backup'));
        add_action('wp_ajax_wpvivid_new_generate_url', array($this, 'new_generate_url'));
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-import-site';
        $cap['display']='Import Site';
        $cap['index']=15;
        $cap['icon']='<span class="dashicons dashicons-download wpvivid-dashicons-grey"></span>';
        $cap['menu_slug']=strtolower(sprintf('%s-import-site', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-import-site';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-import-site';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_import_site');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Import Site');
            $submenu['menu_title'] = 'Import Site';

            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-import-site");

            $submenu['menu_slug'] = strtolower(sprintf('%s-import-site', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 5;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_import_site');
        if($display)
        {
            $menu['id'] = 'wpvivid_admin_menu_import';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Import Site';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-import-site');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-import-site');
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-import-site");
            $menu['index'] = 5;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function reload_remote_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try{
            if(isset($_POST['remote_id']) && !empty($_POST['remote_id']) && isset($_POST['folder']) && !empty($_POST['folder']))
            {
                set_time_limit(120);
                $remoteslist = WPvivid_Setting::get_all_remote_options();
                $remote_id = $_POST['remote_id'];
                $remote_folder = $_POST['folder'];

                if (empty($remote_id))
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'Failed to post remote stroage id. Please try again.';
                    echo json_encode($ret);
                    die();
                }

                if (empty($remote_folder))
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'Failed to post remote storage folder. Please try again.';
                    echo json_encode($ret);
                    die();
                }

                WPvivid_Setting::update_option('wpvivid_select_list_remote_id', $remote_id);
                WPvivid_Setting::update_option('wpvivid_remote_list', array());
                $remote_option = $remoteslist[$remote_id];

                global $wpvivid_plugin;

                $remote_collection=new WPvivid_Remote_collection_addon();
                $remote = $remote_collection->get_remote($remote_option);

                if (!method_exists($remote, 'scan_folder_backup'))
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The selected remote storage does not support scanning.';
                    echo json_encode($ret);
                    die();
                }

                if($remote_folder === 'Incremental')
                {
                    $remote_folder = 'Common';
                }

                if(isset($_POST['incremental_path'])&&!empty($_POST['incremental_path']))
                {
                    $incremental_path=$_POST['incremental_path'];
                    $ret = $remote->scan_child_folder_backup($incremental_path);
                }
                else
                {
                    $ret = $remote->scan_folder_backup($remote_folder);
                }

                if ($ret['result'] == WPVIVID_PRO_SUCCESS)
                {
                    global $wpvivid_backup_pro;
                    $wpvivid_backup_pro->func->rescan_remote_folder_set_migrate_backup($remote_id, $ret);
                }

                $ret['local_cache_files_size'] = apply_filters('wpvivid_get_local_cache_files_size', 0);

                $backup_list=new WPvivid_New_BackupList();
                $remote_list=$backup_list->get_all_remote_backup($remote_folder);

                $table=new WPvivid_Import_Backup_List();
                if(isset($_POST['page']))
                {
                    if(isset($_POST['incremental_path'])&&!empty($_POST['incremental_path']))
                    {
                        $table->set_backup_list($remote_list,$_POST['page'],true);
                    }
                    else
                    {
                        $table->set_backup_list($remote_list,$_POST['page']);
                    }

                }
                else
                {
                    if(isset($_POST['incremental_path'])&&!empty($_POST['incremental_path']))
                    {
                        $table->set_backup_list($remote_list,1,true);
                    }
                    else
                    {
                        $table->set_backup_list($remote_list,1);
                    }

                }
                $table->prepare_items();
                ob_start();
                $table->display();
                $ret['html'] = ob_get_clean();

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

    public function reload_migration_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            $backup_list=new WPvivid_New_BackupList();
            $backuplist=$backup_list->get_local_backup();
            $migrationlist=array();
            foreach ($backuplist as $key=>$value)
            {
                $value['create_time'] = $this->wpvivid_tran_backup_time_to_local($value);
                if($value['type'] === 'Migration')
                {
                    $migrationlist[$value['id']]=$value;
                }
            }
            $table=new WPvivid_Import_Backup_List();
            if(isset($_POST['page']))
            {
                $table->set_backup_list($migrationlist,$_POST['page']);
            }
            else
            {
                $table->set_backup_list($migrationlist);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();
            $ret['result']='success';
            $ret['html']=$html;
            $ret['test']=$backuplist;
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

    public function new_generate_url()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-mange-backup');
        try
        {
            include_once WPVIVID_PLUGIN_DIR.'/vendor/autoload.php';
            $expires=time()+3600;

            if(isset($_POST['expires']))
            {
                $expires_display=sanitize_text_field($_POST['expires']);
                if($expires_display=='1 month')
                {
                    $expires=time()+2592000;
                }
                else if($expires_display=='1 day')
                {
                    $expires=time()+86400;
                }
                else if($expires_display=='2 hour')
                {
                    $expires=time()+7200;
                }
                else if($expires_display=='8 hour')
                {
                    $expires=time()+28800;
                }
                else if($expires_display=='24 hour')
                {
                    $expires=time()+86400;
                }
                else if($expires_display=='Never')
                {
                    $expires=0;
                }
            }

            $key_size = 2048;
            $rsa = new Crypt_RSA();
            $keys = $rsa->createKey($key_size);
            $options['public_key']=base64_encode($keys['publickey']);
            $options['private_key']=base64_encode($keys['privatekey']);
            $options['expires']=$expires;
            $options['domain']=home_url();

            update_option('wpvivid_api_token',$options,'no');

            $url= $options['domain'];
            $url=$url.'?domain='.$options['domain'].'&token='.$options['public_key'].'&expires='.$expires;

            $ret['result']='success';
            $ret['url']=$url;
            echo wp_json_encode($ret);
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

    public function init_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Import Site', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-migrate wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">Import Site</span></p>
                                        <span class="about-description">Import a site from localhost(web server), remote storage or source site (auto-migration).</span>
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
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span>Localhost Storage Directory:</span>
                                        <span>
                                            <code>
                                                <?php
                                                _e(WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath());
                                                ?>
                                            </code>
                                        </span>
                                        <span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-setting', 'wpvivid-setting'); ?>">rename directory</a></span>
                                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                            <div class="wpvivid-bottom">
                                                <p>Click to change WPvivid Pro custom backup folder.</p>
                                                <i></i> <!-- do not delete this line -->
                                            </div>
                                        </span>
                                        <span><a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore'); ?>">or view backups list</a></span>
                                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
										<div class="wpvivid-bottom">
											<!-- The content you need -->
											<p>Click to browse and manage all your backups.</p>
											<i></i> <!-- do not delete this line -->
										</div>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $args['span_class']='dashicons dashicons-migrate wpvivid-dashicons-blue';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;display:block;';
                                    $args['is_parent_tab']=0;
                                    $tabs['import_from_upload']['title']='Import from Localhost(Web Server)';
                                    $tabs['import_from_upload']['slug']='import_from_upload';
                                    $tabs['import_from_upload']['callback']=array($this, 'output_import_from_upload');
                                    $tabs['import_from_upload']['args']=$args;

                                    $args['span_class']='dashicons dashicons-migrate wpvivid-dashicons-blue';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;
                                    $tabs['import_from_remote']['title']='Import from Remote Storage';
                                    $tabs['import_from_remote']['slug']='import_from_remote';
                                    $tabs['import_from_remote']['callback']=array($this, 'output_import_from_remote');
                                    $tabs['import_from_remote']['args']=$args;

                                    $args['span_class']='dashicons dashicons-migrate wpvivid-dashicons-blue';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;
                                    $tabs['import_from_migration']['title']='Import from Auto-Migration';
                                    $tabs['import_from_migration']['slug']='import_from_migration';
                                    $tabs['import_from_migration']['callback']=array($this, 'output_import_from_migration');
                                    $tabs['import_from_migration']['args']=$args;

                                    $args['span_class']='dashicons dashicons-admin-network wpvivid-dashicons-green';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;
                                    $tabs['general_key']['title']='Generate A Key';
                                    $tabs['general_key']['slug']='general_key';
                                    $tabs['general_key']['callback']=array($this, 'output_general_key');
                                    $tabs['general_key']['args']=$args;

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
                    do_action( 'wpvivid_page_add_sidebar', 'Import' );
                    ?>

                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($)
            {
                <?php
                $is_export_auto_migration_finish = get_option('wpvivid_export_auto_migration_finish', false);
                if($is_export_auto_migration_finish == '1')
                {
                    delete_option('wpvivid_export_auto_migration_finish');
                    ?>
                    jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'import_from_migration', 'import_from_migration' ]);
                    <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public function output_import_from_upload()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom: 10px;">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span>The files you want to upload need to be a backup created by WPvivid backup plugin. Make sure you will upload every part of a backup to the directory if the backup is split into many parts</span>
        </div>
        <div style="clear: both;"></div>

        <div style="display: block;" id="wpvivid_backup_uploader">
            <?php
            Wpvivid_BackupUploader_addon::upload_meta_box_ex();
            ?>
        </div>
        <?php
    }

    public function output_import_from_remote()
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

        $select_remote_id=get_option('wpvivid_select_list_remote_id', '');
        $path = '';
        if($select_remote_id==''){
            $first_remote_path = 'Common';
            foreach ($remoteslist as $key=>$value)
            {
                if($key === 'remote_selected')
                {
                    continue;
                }
                if(isset($value['custom_path']))
                {
                    if(isset($value['root_path'])){
                        $path = $value['path'].$value['root_path'].$value['custom_path'];
                    }
                    else{
                        $path = $value['path'].'wpvividbackuppro/'.$value['custom_path'];
                    }
                }
                else
                {
                    $path = $value['path'];
                }
                if($first_remote_path === 'Common'){
                    $first_remote_path = $path;
                }
            }
            $path = $first_remote_path;
        }
        else{
            if (isset($remoteslist[$select_remote_id]))
            {
                if(isset($remoteslist[$select_remote_id]['custom_path']))
                {
                    if(isset($remoteslist[$select_remote_id]['root_path'])){
                        $path = $remoteslist[$select_remote_id]['path'].$remoteslist[$select_remote_id]['root_path']. $remoteslist[$select_remote_id]['custom_path'];
                    }
                    else{
                        $path = $remoteslist[$select_remote_id]['path'].'wpvividbackuppro/'. $remoteslist[$select_remote_id]['custom_path'];
                    }
                }
                else
                {
                    $path = $remoteslist[$select_remote_id]['path'];
                }
            }
            else {
                $path='Common';
            }
        }
        $remote_storage_option = '';
        foreach ($remoteslist as $key=>$value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            $value['type']=apply_filters('wpvivid_storage_provider_tran', $value['type']);
            $remote_storage_option.='<option value="'.$key.'">'.$value['type'].' -> '.$value['name'].'</option>';
        }


        if($has_remote)
        {
            ?>
            <div>
                <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="background:#eaf1fe;border-radius:0.8em;">
                    <div style="border-bottom:1px solid #cccccc;padding-bottom:1em;">
                        <h1 style="text-align:center; color:#8300e9;">Import from Remote Storage</h1>
                        <p style="text-align:center;"><span>Display exported backups in </span>
                            <select id="wpvivid_select_import_remote_storage">
                                <?php _e($remote_storage_option); ?>
                            </select>
                            <span id="wpvivid_reload_remote_backup_list" class="dashicons dashicons-update" style="color:#8300e9;margin-top:0.2em;font-size:1.5em;cursor:pointer;" title="Reload the latest backup"></span>
                            <span class="spinner is-active" id="wpvivid_remote_backup_scaning" style="float: none; padding-top: 4px; margin-left: 4px; display: none;"></span>
                        </p>
                    </div>

                    <div id="wpvivid_remote_backup_list">
                        <?php
                        ?>
                    </div>

                </div>
            </div>

            <script>
                var is_switch_remote=false;
                jQuery('#wpvivid_tab_import_from_remote').on('click', function(){
                    if(!is_switch_remote)
                    {
                        is_switch_remote = true;
                        wpvivid_load_remote_backup_list();
                    }
                });

                function wpvivid_load_remote_backup_list()
                {
                    var remote_id = jQuery('#wpvivid_select_import_remote_storage').val();
                    var remote_folder = 'Migrate';
                    var ajax_data = {
                        'action': 'wpvivid_reload_remote_backup',
                        'remote_id': remote_id,
                        'folder': remote_folder
                    };
                    jQuery('#wpvivid_remote_backup_scaning').show();
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        jQuery('#wpvivid_remote_backup_scaning').hide();
                        jQuery('#wpvivid_remote_backup_list').html('');
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#wpvivid_remote_backup_list').html(jsonarray.html);
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
                        jQuery('#wpvivid_remote_backup_scaning').hide();
                        var error_message = wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                jQuery('#wpvivid_reload_remote_backup_list').on('click', function(){
                    wpvivid_load_remote_backup_list();
                });

                jQuery('#wpvivid_remote_backup_list').on("click",'.first-page',function() {
                    wpvivid_get_remote_backup_list_page('first');
                });

                jQuery('#wpvivid_remote_backup_list').on("click",'.prev-page',function() {
                    var page=parseInt(jQuery(this).attr('value'));
                    wpvivid_get_remote_backup_list_page(page-1);
                });

                jQuery('#wpvivid_remote_backup_list').on("click",'.next-page',function() {
                    var page=parseInt(jQuery(this).attr('value'));
                    wpvivid_get_remote_backup_list_page(page+1);
                });

                jQuery('#wpvivid_remote_backup_list').on("click",'.last-page',function() {
                    wpvivid_get_remote_backup_list_page('last');
                });

                jQuery('#wpvivid_remote_backup_list').on("keypress", '.current-page', function(){
                    if(event.keyCode === 13){
                        var page = jQuery(this).val();
                        wpvivid_get_remote_backup_list_page(page);
                    }
                });

                function wpvivid_get_remote_backup_list_page(page=0)
                {
                    if(page==0)
                    {
                        page =jQuery('#wpvivid_remote_backup_list').find('.current-page').val();
                    }
                    var remote_id = jQuery('#wpvivid_select_import_remote_storage').val();
                    var remote_folder = 'Migrate';
                    var ajax_data = {
                        'action': 'wpvivid_reload_remote_backup',
                        'remote_id': remote_id,
                        'folder': remote_folder,
                        'page': page
                    };
                    jQuery('#wpvivid_remote_backup_scaning').show();
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        jQuery('#wpvivid_remote_backup_scaning').hide();
                        jQuery('#wpvivid_remote_backup_list').html('');
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#wpvivid_remote_backup_list').html(jsonarray.html);
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
                        jQuery('#wpvivid_remote_backup_scaning').hide();
                        var error_message = wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                jQuery('#wpvivid_remote_backup_list').on('click', '.wpvivid-restore-backup', function(){
                    var Obj=jQuery(this);

                    var backup_id = Obj.closest('div').attr('id');
                    location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&&restore=1&backup_id='; ?>'+backup_id;
                });
            </script>
            <?php
        }
        else
        {
            ?>
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="background:#eaf1fe;border-radius:8px;">
                <p>There is no available remote storage added, please set an available account on <a href="<?php echo 'admin.php?page='.strtolower(sprintf('%s-remote', apply_filters('wpvivid_white_label_slug', 'wpvivid')));?>" style="cursor: pointer;">cloud storage</a> page</p>
            </div>
            <?php
        }
    }

    public function wpvivid_tran_backup_time_to_local($value)
    {
        $backup_time=$value['create_time'];
        if(isset($value['backup']['files'])){
            foreach ($value['backup']['files'] as $file_info){
                if(preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}/',$file_info['file_name'],$matches))
                {
                    $backup_date=$matches[0];
                }
                else
                {
                    $backup_date=$value['create_time'];
                }

                $time_array=explode('-',$backup_date);
                if(sizeof($time_array)>4){
                    $time=$time_array[0].'-'.$time_array[1].'-'.$time_array[2].' '.$time_array[3].':'.$time_array[4];
                    $backup_time=strtotime($time);
                }
                break;
            }
        }
        return $backup_time;
    }

    public function output_import_from_migration()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="background:#eaf1fe;border-radius:0.8em;">
            <div style="border-bottom:1px solid #cccccc;padding-bottom:1em;">
                <h1 style="text-align:center; color:#8300e9;">Import From Auto-Migration</h1>
                <p style="text-align:center;">
                    <label>
                        <span class="dashicons dashicons-update wpvivid-reload-migration-backup-list" style="margin-top:0.1em;color:#8300e9;font-size:1.5em;cursor:pointer;" title="Reload the latest backup"></span>
                        <span class="wpvivid-reload-migration-backup-list">Click here to reload the latest received backup</span>
                        <span class="spinner is-active" id="wpvivid_migration_backup_scaning" style="float: none; padding-top: 4px; margin-left: 4px; display: none;"></span>
                    </label>
                </p>
            </div>

            <div id="wpvivid_migration_backup_list">
            <?php
            $backup_list=new WPvivid_New_BackupList();
            $backuplist=$backup_list->get_local_backup();
            $migrationlist=array();
            foreach ($backuplist as $key=>$value)
            {
                $value['create_time'] = $this->wpvivid_tran_backup_time_to_local($value);
                if($value['type'] === 'Migration')
                {
                    $migrationlist[$value['id']]=$value;
                }
            }
            $table=new WPvivid_Import_Backup_List();
            $table->set_backup_list($migrationlist);
            $table->prepare_items();
            $table->display();
            ?>
            </div>

            <!--<div>
                <p style="text-align:center;">
                    <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                    <span>You can manage the backups on <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore'); ?>">Backup Manager Page</a></span>
                </p>
            </div>-->
        </div>
        <script>
            jQuery('.wpvivid-reload-migration-backup-list').on('click', function(){
                var ajax_data = {
                    'action': 'wpvivid_reload_migration_backup'
                };
                jQuery('#wpvivid_migration_backup_scaning').show();
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_migration_backup_scaning').hide();
                    jQuery('#wpvivid_migration_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_migration_backup_list').html(jsonarray.html);
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
                    jQuery('#wpvivid_migration_backup_scaning').hide();
                    var error_message = wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_migration_backup_list').on("click",'.first-page',function() {
                wpvivid_get_migration_backup_list_page('first');
            });

            jQuery('#wpvivid_migration_backup_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_migration_backup_list_page(page-1);
            });

            jQuery('#wpvivid_migration_backup_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_migration_backup_list_page(page+1);
            });

            jQuery('#wpvivid_migration_backup_list').on("click",'.last-page',function() {
                wpvivid_get_migration_backup_list_page('last');
            });

            jQuery('#wpvivid_migration_backup_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_get_migration_backup_list_page(page);
                }
            });

            function wpvivid_get_migration_backup_list_page(page=0)
            {
                if(page==0)
                {
                    page =jQuery('#wpvivid_migration_backup_list').find('.current-page').val();
                }
                var ajax_data = {
                    'action': 'wpvivid_reload_migration_backup',
                    'page':page
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_migration_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_migration_backup_list').html(jsonarray.html);
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
                    var error_message = wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_migration_backup_list').on('click', '.wpvivid-restore-backup', function(){
                var Obj=jQuery(this);

                var backup_id = Obj.closest('div').attr('id');
                location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-backup-and-restore', 'wpvivid-backup-and-restore').'&&restore=1&backup_id='; ?>'+backup_id;
            });
        </script>
        <?php
    }

    public function output_general_key()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow" >
            In order to allow another site to send a backup to this site, please generate a key below. Once the key is generated, this site is ready to receive a backup from another site. Then, please copy and paste the key in sending site and save it.
            <p></p>
            <div>
                <span>The key will expire in</span>
                <select id="wpvivid_generate_url_expires" style="margin-bottom: 2px;">
                    <option value="2 hour">2 hours</option>
                    <option selected="selected" value="8 hour">8 hours</option>
                    <option value="24 hour">24 hours</option>
                </select>
                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                    <div class="wpvivid-bottom">
                        <!-- The content you need -->
                        <p>Dolor sit amet, consectetur adipiscing elit.</p>
                        <i></i> <!-- do not delete this line -->
                    </div>
                </span>
            </div>
            <textarea id="wpvivid_test_remote_site_url_text" style="width: 100%; height: 140px;margin-bottom:1em;"></textarea>
            <input class="button-primary" id="wpvivid_generate_url" type="submit" value="Generate" onclick="wpvivid_click_generate_url();">
        </div>
        <script>
            jQuery("#wpvivid_test_remote_site_url_text").focus(function() {
                jQuery(this).select();
                jQuery(this).mouseup(function() {
                    jQuery(this).unbind("mouseup");
                    return false;
                });
            });
            function wpvivid_click_generate_url()
            {
                var expires=jQuery('#wpvivid_generate_url_expires').val();
                var ajax_data = {
                    'action': 'wpvivid_new_generate_url',
                    'expires':expires
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jsonarray.url=jsonarray.url.replace(/[\r\n]/g, "");
                            jQuery('#wpvivid_test_remote_site_url_text').val(jsonarray.url);
                        }
                        else
                        {
                            alert('Failed to generating key.');
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('generating key', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }
}