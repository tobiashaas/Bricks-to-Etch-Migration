<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Admin_load: yes
 * Need_init: yes
 * Interface Name: WPvivid_Migrate_MU_Single_Site_Display
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPvivid_MU_Single_Site_List extends WP_List_Table
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
                'plural' => 'mu_site',
                'screen' => 'mu_site',
            )
        );
    }

    public function set_parent($parent)
    {
        $this->parent=$parent;
    }

    public function set_list($list,$type,$page_num=1)
    {
        $this->list=$list;
        $this->type=$type;
        $this->page_num=$page_num;
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped' );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

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
                //$class[] = 'check-column';
            }
            $tag='th';
            //$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
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
            'blogname'    => __( 'Site Url' ),
            'title' => __( 'Site Title' ),
            'description'  => __( 'Site Description')
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

    public function column_cb( $subsite )
    {
        $subsite_id = get_object_vars($subsite)["blog_id"];
        $blogname = get_object_vars($subsite)["domain"].get_object_vars($subsite)["path"];
        ?>
        <label for="blog_<?php echo $subsite_id; ?>">
            <input type="checkbox" name="<?php echo esc_attr( $this->type ); ?>" value="<?php echo esc_attr( $subsite_id ); ?>" />
        </label>
        <?php
    }

    public function column_id( $subsite )
    {
        $subsite_id = get_object_vars($subsite)["blog_id"];
        echo $subsite_id;
    }

    public function column_blogname( $subsite )
    {
        $subsite_id = get_object_vars($subsite)["blog_id"];
        $blogname    = untrailingslashit( get_object_vars($subsite)['domain'] . get_object_vars($subsite)['path'] );
        ?>
        <strong>
            <a href="<?php echo esc_url( network_admin_url( 'site-info.php?id=' .$subsite_id ) ); ?>" class="edit"><?php echo $blogname; ?></a>
        </strong>
        <?php
    }

    public function column_tables_folders( $subsite )
    {
        $subsite_id = get_object_vars($subsite)["blog_id"];
        $disable='';

        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( $this->type ); ?>_tables" value="<?php echo esc_attr( $subsite_id ); ?>" <?php echo esc_attr( $disable ); ?>/>
            Tables /
        </label>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( $this->type ); ?>_folders" value="<?php echo esc_attr( $subsite_id ); ?>" <?php echo esc_attr( $disable ); ?>/>
            Folders
        </label>
        <?php
    }

    public function column_title( $subsite )
    {
        switch_to_blog( get_object_vars($subsite)["blog_id"] );
        echo ( get_option( 'blogname' ) ) ;
        restore_current_blog();
    }

    public function column_description( $subsite ) {
        switch_to_blog( get_object_vars($subsite)["blog_id"] );
        echo (  get_option( 'blogdescription ' ) ) ;
        restore_current_blog();
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
                'per_page'    => 5,
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
            $temp_page_list = array_splice( $page_list, 0, 5);
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

class WPvivid_MU_Single_Site_Custom_List extends WP_List_Table
{
    public $parent_id;
    public $option;
    public $custom_uploads_path;
    public $custom_content_path;
    public $custom_additional_file_path;

    public function set_parent_id($parent_id, $option){
        $this->parent_id = $parent_id;
        $this->option=$option;
    }

    public function is_calc_website_size()
    {
        $wpvivid_common_setting = get_option('wpvivid_common_setting', array());
        if(isset($wpvivid_common_setting) && !empty($wpvivid_common_setting))
        {
            if(isset($wpvivid_common_setting['estimate_backup'])&&$wpvivid_common_setting['estimate_backup'])
            {
                $is_calc = true;
            }
            else
            {
                $is_calc = false;
            }
        }
        else
        {
            $is_calc = true;
        }
        return $is_calc;
    }

    public function display_rows()
    {
        $core_check = 'checked="checked"';
        $database_check = 'checked="checked"';
        $themes_check = 'checked="checked"';
        $plugins_check = 'checked="checked"';
        $uploads_check = 'checked="checked"';
        $content_check = 'checked="checked"';
        $additional_folder_check = '';
        $additional_database_check = '';
        $database_part_check = 'checked="checked"';
        $file_part_check = 'checked="checked"';
        $exclude_part_check = 'checked="checked"';

        $themes_exclude_extension = '';
        $plugins_exclude_extension = '';
        $uploads_exclude_extension = '';
        $content_exclude_extension = '';
        $additional_folder_exclude_extension  = '';

        ?>
        <div>
            <span><input type="checkbox" class="wpvivid-custom-database-part" <?php esc_attr_e($database_part_check); ?> disabled></span>
            <span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue"></span>
            <span class="wpvivid-handle-database-detail" style="cursor:pointer;"><strong>Databases</strong></span>
            <span class="wpvivid-handle-database-detail" style="cursor:pointer;"> (</span><span class="wpvivid-database-size">calculating</span><span>)</span>
            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                <div class="wpvivid-bottom">
                    <!-- The content you need -->
                    <p>Won't back up any tables or additional databases if you uncheck this.</p>
                    <i></i> <!-- do not delete this line -->
                </div>
            </span>
            <span class="dashicons dashicons-arrow-down-alt2 wpvivid-dashicons-grey wpvivid-handle-database-detail" style="cursor:pointer;"></span>
        </div>

        <div class="wpvivid-database-detail" style="display: none;">
            <!--  database begin  -->
            <div style="padding-left:2em;">
                <p><span><input type="checkbox" class="wpvivid-custom-database-check" <?php esc_attr_e($database_check); ?> disabled><span class="wpvivid-handle-base-database-detail" style="cursor:pointer;"><strong>Tables In The WordPress Database</strong></span></span></p>
            </div>
            <div style="clear:both;"></div>
            <!--  database end  -->
        </div>

        <!--  files begin  -->
        <div style="margin-top:1em;">
            <span><input type="checkbox" class="wpvivid-custom-file-part" <?php esc_attr_e($file_part_check); ?> disabled></span>
            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
            <span class="wpvivid-handle-file-detail" style="cursor:pointer;"><strong>Files & Folders</strong></span>
            <span class="wpvivid-handle-file-detail" style="cursor:pointer;"> (</span><span class="wpvivid-total-file-size wpvivid-size">calculating</span><span>)</span>
            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                <div class="wpvivid-bottom">
                    <!-- The content you need -->
                    <p>Won't back up any files or folders if you uncheck this.</p>
                    <i></i> <!-- do not delete this line -->
                </div>
            </span>
            <span class="dashicons dashicons-arrow-down-alt2 wpvivid-dashicons-grey wpvivid-handle-file-detail" style="cursor:pointer;"></span>
        </div>
        <div class="wpvivid-file-detail" style="padding-left:2em; display: none;">
            <p><span><input class="wpvivid-custom-core-check" type="checkbox" <?php esc_attr_e($core_check); ?> disabled><span><strong>Wordpress Core<span> (</span><span class="wpvivid-core-size">calculating</span><span>)</span>: </strong>includes <code>wp-admin</code> folder,<code>wp-includes</code> folder and all other essential files.</span></span></p>
            <p><span><input class="wpvivid-custom-themes-check" type="checkbox" <?php esc_attr_e($themes_check); ?>><span><strong>Themes<span> (</span><span class="wpvivid-themes-size">calculating</span><span>)</span>: </strong>includes all folders of themes.</span></p>
            <p><span><input class="wpvivid-custom-plugins-check" type="checkbox" <?php esc_attr_e($plugins_check); ?>><span><strong>Plugins<span> (</span><span class="wpvivid-plugins-size">calculating</span><span>)</span>: </strong>includes all folders of plugins.</span></p>
            <p><span><input class="wpvivid-custom-content-check" type="checkbox" <?php esc_attr_e($content_check); ?>><span><strong>Wp-content<span> (</span><span class="wpvivid-content-size">calculating</span><span>)</span>: </strong>everything in <code>wp-content</code> <strong>except for</strong> <code>themes</code>, <code>plugins</code> and <code>uploads</code> folders.</span></span></p>
            <p><span><input class="wpvivid-custom-uploads-check" type="checkbox" <?php esc_attr_e($uploads_check); ?> disabled><span><strong>Uploads<span> (</span><span class="wpvivid-uploads-size">calculating</span><span>)</span>: </strong>includes images, videos, and any other files such as PDF documents, MS Word docs, and GIFs.</span></span></p>
            <p>
                <span><input class="wpvivid-custom-additional-folder-check" type="checkbox" <?php esc_attr_e($additional_folder_check); ?>><span><strong>Non-WordPress Files/Folders<span> (</span><span class="wpvivid-additional-folder-size">calculating</span><span>)</span>: </strong>all folders/files in root directory of your website except for Wordpress core folders/files.</span></span>
                <span class="dashicons dashicons-arrow-down-alt2 wpvivid-dashicons-grey wpvivid-handle-additional-folder-detail" style="cursor:pointer;"></span>
            </p>

            <p></p>

            <div class="wpvivid-additional-folder-detail" style="display: none;">
                <div style="padding-left:2em;margin-top:1em;">
                    <div style="border-bottom:1px solid #eee;border-top:1px solid #eee;">
                        <p><span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span><span><code>CTRL</code> + <code>Left Click</code> to select multiple files or folders.</span></p>
                    </div>
                </div>
                <div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;padding-left:2em;">
                    <div>
                        <p>
                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                            <span><strong>Folders</strong></span>
                            <span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-refresh-include-tree">Refresh<span>
                        </p>
                    </div>


                    <div class="wpvivid-custom-additional-folder-tree-info" style="margin-top:10px;height:250px;border:1px solid #eee;padding:0.2em 0.5em;overflow:auto;">Tree Viewer

                    </div>

                    <div style="clear:both;"></div>
                    <div style="padding:1em 0 0 0;"><input class="button-primary wpvivid-include-additional-folder-btn" type="submit" value="Include Files/Folders"></div>
                </div>
                <div style="width:70%; float:left;box-sizing:border-box;padding-left:0.5em;">
                    <div>
                        <p>
                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                            <span><strong>Non-WordPress Files/Folders Will Be Backed Up</strong></span>
                        </p>
                    </div>
                    <div class="wpvivid-custom-include-additional-folder-list" style="height:250px;border:1px solid #eee;padding:0.2em 0.5em;overflow-y:auto;"></div>
                    <div style="padding:1em 0 0 0;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-include-list" style="float:right;">Empty Included Files/Folders</span></div>
                </div>
            </div>
        </div>
        <div style="clear:both;"></div>
        <!--  files end  -->

        <div style="box-sizing:border-box; margin-top:1em;">
            <!--  exclude tree begin  -->
            <div style="margin-top:1em;">
                <span><input type="checkbox" class="wpvivid-custom-exclude-part" <?php esc_attr_e($exclude_part_check); ?>></span>
                <span class="dashicons dashicons-portfolio wpvivid-dashicons-grey"></span>
                <span class="wpvivid-handle-tree-detail" style="cursor:pointer;"><strong>Exclude Files/Folders </strong></span>
                <span class="wpvivid-handle-file-detail" style="cursor:pointer;"> (</span><span class="wpvivid-total-exclude-file-size">calculating</span><span>)</span>
                <span class="dashicons dashicons-arrow-down-alt2 wpvivid-dashicons-grey wpvivid-handle-tree-detail" style="cursor:pointer;"></span>
            </div>
            <div class="wpvivid-tree-detail" style="display: none;">
                <div style="padding-left:2em;margin-top:1em;">
                    <div style="border-bottom:1px solid #eee;border-top:1px solid #eee;">
                        <p><span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span><span><code>CTRL</code> + <code>Left Click</code> to select multiple files or folders.</span></p>
                    </div>
                </div>

                <div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;padding-left:2em;">
                    <div>
                        <p>
                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                            <span><strong>Folders</strong></span>
                            <span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-refresh-exclude-tree">Refresh<span>
                        </p>
                    </div>
                    <div style="height:250px;">
                        <div>
                            <select name="action" id="wpvivid_custom_tree_selector" style="width:100%;border:1px solid #aaa;">
                                <option value="content" selected>wp-content</option>
                                <option value="themes">themes</option>
                                <option value="plugins">plugins</option>
                                <option value="uploads">uploads</option>
                            </select>
                        </div>
                        <div class="wpvivid-custom-exclude-tree-info" style="margin-top:10px;height:210px;border:1px solid #eee;padding:0.2em 0.5em;overflow:auto;">Tree Viewer
                        </div>
                    </div>
                    <div style="clear:both;"></div>
                    <div style="padding:1.5em 0 0 0;"><input class="button-primary wpvivid-custom-tree-exclude-btn" type="submit" value="Exclude Files/Folders"></div>
                </div>
                <div style="width:70%; float:left;box-sizing:border-box;padding-left:0.5em;">
                    <div>
                        <p>
                            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                            <span><strong>Excluded Files/Folders/File Types</strong></span>
                        </p>
                    </div>

                    <!-- content -->
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-content-module">
                        <input type="text" class="wpvivid-content-extension" style="width:100%; border:1px solid #aaa;" value="" placeholder="Exclude file types, separate by comma - for example: gif, jpg, webp, pdf" />
                    </div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-content-module wpvivid-custom-exclude-content-list" style="margin-top:10px;height:210px;border:1px solid #eee;padding:0.2em 0.5em;overflow-y:auto;"></div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-content-module" style="padding:1em 0 0 0;display: none;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-exclude-list" style="float:right;">Empty Excluded Files/Folders</span></div>

                    <!-- themes -->
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-themes-module" style="display: none;">
                        <input type="text" class="wpvivid-themes-extension" style="width:100%; border:1px solid #aaa;" value="" placeholder="Exclude file types, separate by comma - for example: gif, jpg, webp, pdf" />
                    </div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-themes-module wpvivid-custom-exclude-themes-list" style="margin-top:10px;height:210px;border:1px solid #eee;padding:0.2em 0.5em;overflow-y:auto;display: none;"></div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-themes-module" style="padding:1em 0 0 0;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-exclude-list" style="float:right;">Empty Excluded Files/Folders</span></div>

                    <!-- plugins -->
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-plugins-module" style="display: none;">
                        <input type="text" class="wpvivid-plugins-extension" style="width:100%; border:1px solid #aaa;" value="" placeholder="Exclude file types, separate by comma - for example: gif, jpg, webp, pdf" />
                    </div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-plugins-module wpvivid-custom-exclude-plugins-list" style="margin-top:10px;height:210px;border:1px solid #eee;padding:0.2em 0.5em;overflow-y:auto;display: none;"></div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-plugins-module" style="padding:1em 0 0 0;display: none;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-exclude-list" style="float:right;">Empty Excluded Files/Folders</span></div>

                    <!-- uploads -->
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-uploads-module" style="display: none;">
                        <input type="text" class="wpvivid-uploads-extension" style="width:100%; border:1px solid #aaa;" value="" placeholder="Exclude file types, separate by comma - for example: gif, jpg, webp, pdf" />
                    </div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-uploads-module wpvivid-custom-exclude-uploads-list" style="margin-top:10px;height:210px;border:1px solid #eee;padding:0.2em 0.5em;overflow-y:auto;display: none;"></div>
                    <div class="wpvivid-custom-exclude-module wpvivid-custom-exclude-uploads-module" style="padding:1em 0 0 0;display: none;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue wpvivid-clear-custom-exclude-list" style="float:right;">Empty Excluded Files/Folders</span></div>
                </div>

            </div>
            <div style="clear:both;"></div>
            <!--  exculde tree end  -->
        </div>
        <?php
    }

    public function load_js()
    {
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
        $additional_file_path = str_replace('\\','/',get_home_path());
        $this->custom_additional_file_path = $additional_file_path;
        //
        ?>
        <script>
            var path_arr = {};
            path_arr['core'] = '<?php echo $home_path; ?>';
            path_arr['content'] = '<?php echo $content_path; ?>';
            path_arr['uploads'] = '<?php echo $uploads_path; ?>';
            path_arr['themes'] = '<?php echo $themes_path; ?>';
            path_arr['plugins'] = '<?php echo $plugins_path; ?>';

            function wpvivid_handle_custom_open_close_ex(handle_obj, obj, parent_id)
            {
                if(obj.is(":hidden")) {
                    handle_obj.each(function(){
                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2')){
                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                        }
                    });
                    obj.show();
                }
                else{
                    handle_obj.each(function(){
                        if(jQuery(this).hasClass('dashicons-arrow-up-alt2')){
                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                            jQuery(this).addClass('dashicons-arrow-down-alt2');
                        }
                    });
                    obj.hide();
                }
            }

            function wpvivid_init_single_site_custom_include_tree(tree_path, parent_id, refresh=0)
            {
                if (refresh) {
                    jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-tree-info').on('activate_node.jstree', function (e, data) {
                    }).jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id,
                                    'path': tree_path
                                };
                                var ajax_data = {
                                    'action': 'wpvivid_get_single_mu_custom_dir_additional_info',
                                    'tree_node': tree_node
                                };
                                ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: wpvivid_ajax_object_addon.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        callback.call(this, jsonarray.nodes);
                                        jQuery('#'+parent_id).find('.wpvivid-include-additional-folder-btn').attr('disabled', false);
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        },
                        "plugins": ["sort"],
                        "sort": function(a, b) {
                            a1 = this.get_node(a);
                            b1 = this.get_node(b);
                            if (a1.icon === b1.icon) {
                                return (a1.text.toLowerCase() > b1.text.toLowerCase()) ? 1 : -1;
                            } else {
                                return (a1.icon > b1.icon) ? 1 : -1;
                            }
                        }
                    });
                }
            }

            function wpvivid_init_single_site_custom_exclude_tree(tree_path, parent_id, refresh=0)
            {
                if (refresh) {
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-tree-info').on('activate_node.jstree', function (event, data) {
                    }).jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id,
                                    'path': tree_path
                                };
                                var ajax_data = {
                                    'action': 'wpvivid_get_single_mu_custom_dir_uploads_info',
                                    'tree_node': tree_node
                                };
                                ajax_data.nonce=wpvivid_ajax_object_addon.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: wpvivid_ajax_object_addon.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        callback.call(this, jsonarray.nodes);
                                        jQuery('#'+parent_id).find('.wpvivid-custom-tree-exclude-btn').attr('disabled', false);
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        },
                        "plugins": ["sort"],
                        "sort": function(a, b) {
                            a1 = this.get_node(a);
                            b1 = this.get_node(b);
                            if (a1.icon === b1.icon) {
                                return (a1.text.toLowerCase() > b1.text.toLowerCase()) ? 1 : -1;
                            } else {
                                return (a1.icon > b1.icon) ? 1 : -1;
                            }
                        }
                    });
                }
            }

            function wpvivid_change_custom_exclude_info(type, parent_id)
            {
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-module').hide();
                if(type === 'themes'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-module').show();
                }
                else if(type === 'plugins'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-module').show();
                }
                else if(type === 'content'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-module').show();
                }
                else if(type === 'uploads'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-module').show();
                }
            }

            function wpvivid_check_tree_repeat(tree_type, value, parent_id)
            {
                if(tree_type === 'themes'){
                    var list = 'wpvivid-custom-exclude-themes-list';
                }
                else if(tree_type === 'plugins'){
                    var list = 'wpvivid-custom-exclude-plugins-list';
                }
                else if(tree_type === 'content'){
                    var list = 'wpvivid-custom-exclude-content-list';
                }
                else if(tree_type === 'uploads'){
                    var list = 'wpvivid-custom-exclude-uploads-list';
                }
                else if(tree_type === 'additional-folder'){
                    var list = 'wpvivid-custom-include-additional-folder-list';
                }

                var brepeat = false;
                jQuery('#'+parent_id).find('.'+list+' div').find('span:eq(2)').each(function (){
                    if (value === this.innerHTML) {
                        brepeat = true;
                    }
                });
                return brepeat;
            }

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-database-detail', function()
            {
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-database-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-base-database-detail', function()
            {
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-base-database-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-base-database-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-file-detail', function()
            {
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-file-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-file-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-additional-folder-detail', function()
            {
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-additional-folder-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-additional-folder-detail');
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
                wpvivid_init_single_site_custom_include_tree('<?php echo $home_path; ?>', '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-handle-tree-detail', function()
            {
                var handle_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-handle-tree-detail');
                var obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-tree-detail');
                var value = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo $this->parent_id; ?>');
                wpvivid_init_single_site_custom_exclude_tree(path_arr[value], '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('change', '#wpvivid_custom_tree_selector', function()
            {
                var value = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-exclude-tree-info').jstree("destroy").empty();
                wpvivid_init_single_site_custom_exclude_tree(path_arr[value], '<?php echo $this->parent_id; ?>');
                wpvivid_change_custom_exclude_info(value, '<?php echo $this->parent_id; ?>');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-refresh-include-tree', function()
            {
                var value = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                wpvivid_init_single_site_custom_include_tree(path_arr[value], '<?php echo $this->parent_id; ?>', 1);
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-refresh-exclude-tree', function()
            {
                var value = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                wpvivid_init_single_site_custom_exclude_tree(path_arr[value], '<?php echo $this->parent_id; ?>', 1);
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-include-additional-folder-btn', function()
            {
                var select_folders = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-additional-folder-tree-info').jstree(true).get_selected(true);
                var tree_path = '<?php echo $home_path; ?>';
                var list_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-include-additional-folder-list');
                var tree_type = 'additional-folder';

                jQuery.each(select_folders, function (index, select_item) {
                    if (select_item.id !== tree_path) {
                        var value = select_item.id;
                        value = value.replace(tree_path, '');
                        if (!wpvivid_check_tree_repeat(tree_type, value, '<?php echo $this->parent_id; ?>')) {
                            var class_name = select_item.icon;
                            if(class_name === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                var type = 'folder';
                            }
                            else{
                                var type = 'file';
                            }
                            var tr = "<div class='wpvivid-text-line' type='"+type+"'>" +
                                "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                                "<span class='"+class_name+"'></span>" +
                                "<span class='wpvivid-text-line'>" + value + "</span>" +
                                "</div>";
                            list_obj.append(tr);
                        }
                    }
                });
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-custom-tree-exclude-btn', function()
            {
                var select_folders = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-exclude-tree-info').jstree(true).get_selected(true);
                var tree_type = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                var tree_path = path_arr[tree_type];
                if(tree_type === 'themes'){
                    var list_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-exclude-themes-list');
                }
                else if(tree_type === 'plugins'){
                    var list_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-exclude-plugins-list');
                }
                else if(tree_type === 'content'){
                    var list_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-exclude-content-list');
                }
                else if(tree_type === 'uploads'){
                    var list_obj = jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-exclude-uploads-list');
                }

                jQuery.each(select_folders, function (index, select_item) {
                    if (select_item.id !== tree_path) {
                        var value = select_item.id;
                        value = value.replace(tree_path, '');
                        if (!wpvivid_check_tree_repeat(tree_type, value, '<?php echo $this->parent_id; ?>')) {
                            var class_name = select_item.icon;
                            if(class_name === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                var type = 'folder';
                            }
                            else{
                                var type = 'file';
                            }
                            var tr = "<div class='wpvivid-text-line' type='"+type+"'>" +
                                "<span class='dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree'></span>" +
                                "<span class='"+class_name+"'></span>" +
                                "<span class='wpvivid-text-line'>" + value + "</span>" +
                                "</div>";
                            list_obj.append(tr);
                        }
                    }
                });
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-remove-custom-exlcude-tree', function()
            {
                jQuery(this).parent().remove();
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-clear-custom-include-list', function()
            {
                jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-custom-include-additional-folder-list').html('');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-clear-custom-exclude-list', function()
            {
                var tree_type = jQuery('#<?php echo $this->parent_id; ?>').find('#wpvivid_custom_tree_selector').val();
                if(tree_type === 'themes'){
                    var list = 'wpvivid-custom-exclude-themes-list';
                }
                else if(tree_type === 'plugins'){
                    var list = 'wpvivid-custom-exclude-plugins-list';
                }
                else if(tree_type === 'content'){
                    var list = 'wpvivid-custom-exclude-content-list';
                }
                else if(tree_type === 'uploads'){
                    var list = 'wpvivid-custom-exclude-uploads-list';
                }
                jQuery('#<?php echo $this->parent_id; ?>').find('.'+list).html('');
            });

            jQuery('#<?php echo $this->parent_id; ?>').on('click', '.wpvivid-database-table-check', function()
            {
                if(jQuery(this).prop('checked')){
                    if(jQuery(this).hasClass('wpvivid-database-base-table-check')){
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]').prop('checked', true);
                    }
                    else if(jQuery(this).hasClass('wpvivid-database-other-table-check')){
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]').prop('checked', true);
                    }
                    else if(jQuery(this).hasClass('wpvivid-database-diff-prefix-table-check')){
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]').prop('checked', true);
                    }
                }
                else{
                    if (jQuery(this).hasClass('wpvivid-database-base-table-check')) {
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]').prop('checked', false);
                    }
                    else if (jQuery(this).hasClass('wpvivid-database-other-table-check')) {
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]').prop('checked', false);
                    }
                    else if (jQuery(this).hasClass('wpvivid-database-diff-prefix-table-check')) {
                        jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]').prop('checked', false);
                    }
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on("click", 'input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]', function()
            {
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=base_db][name=<?php echo $this->option; ?>_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-base-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-base-table-check').prop('checked', false);
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on("click", 'input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]', function()
            {
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=other_db][name=<?php echo $this->option; ?>_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-other-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-other-table-check').prop('checked', false);
                }
            });

            jQuery('#<?php echo $this->parent_id; ?>').on("click", 'input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]', function()
            {
                if(jQuery(this).prop('checked')){
                    var all_check = true;
                    jQuery('#<?php echo $this->parent_id; ?>').find('input:checkbox[option=diff_prefix_db][name=<?php echo $this->option; ?>_database]').each(function(){
                        if(!jQuery(this).prop('checked')){
                            all_check = false;
                        }
                    });
                    if(all_check){
                        jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-diff-prefix-table-check').prop('checked', true);
                    }
                }
                else{
                    jQuery('#<?php echo $this->parent_id; ?>').find('.wpvivid-database-diff-prefix-table-check').prop('checked', false);
                }
            });

            var is_get_mu_database_size = false;
            var is_get_mu_files_size = false;

            function wpvivid_get_website_size(website_item_arr)
            {
                if(website_item_arr.length > 0)
                {
                    console.log(website_item_arr);
                    var website_item = website_item_arr.shift();
                    var ajax_data = {
                        'action': 'wpvivid_get_website_size',
                        'website_item': website_item
                    };
                    wpvivid_post_request_addon(ajax_data, function(data){
                        try{
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result == 'success') {
                                if(website_item === 'database')
                                {
                                    jQuery('.wpvivid-database-size').html(jsonarray.database_size);
                                }
                                if(website_item === 'core')
                                {
                                    jQuery('.wpvivid-core-size').html(jsonarray.core_size);
                                }
                                if(website_item === 'content')
                                {
                                    jQuery('.wpvivid-content-size').html(jsonarray.content_size);
                                }
                                if(website_item === 'themes')
                                {
                                    jQuery('.wpvivid-themes-size').html(jsonarray.themes_size);
                                }
                                if(website_item === 'plugins')
                                {
                                    jQuery('.wpvivid-plugins-size').html(jsonarray.plugins_size);
                                }
                                if(website_item === 'uploads')
                                {
                                    jQuery('.wpvivid-uploads-size').html(jsonarray.uploads_size);
                                }
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('.wpvivid-additional-folder-size').html(jsonarray.additional_size);
                                    jQuery('.wpvivid-total-file-size').html(jsonarray.total_file_size);
                                    jQuery('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                                    jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_get_website_size(website_item_arr);
                            }
                            else {
                                alert(jsonarray.error);
                                if(website_item === 'additional_folder')
                                {
                                    jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                }
                                wpvivid_get_website_size(website_item_arr);
                            }
                        }
                        catch (err) {
                            alert(err);
                            if(website_item === 'additional_folder')
                            {
                                jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                            wpvivid_get_website_size(website_item_arr);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        if(website_item === 'additional_folder')
                        {
                            jQuery('#wpvivid_recalc_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_recalc_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_recalc_update_schedule_backup_size').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                        wpvivid_get_website_size(website_item_arr);
                    });
                }
            }

            jQuery(document).ready(function ()
            {
                if('<?php echo $this->is_calc_website_size(); ?>')
                {
                    var website_item_arr = new Array('database', 'core', 'content', 'themes', 'plugins', 'uploads', 'additional_folder');
                    wpvivid_get_website_size(website_item_arr);
                }
                else
                {
                    if(!'<?php echo $this->is_calc_website_size(); ?>')
                    {
                        jQuery('.wpvivid-database-size').hide();
                        jQuery('.wpvivid-database-size').prev().hide();
                        jQuery('.wpvivid-database-size').next().hide();

                        jQuery('.wpvivid-core-size').hide();
                        jQuery('.wpvivid-core-size').prev().hide();
                        jQuery('.wpvivid-core-size').next().hide();

                        jQuery('.wpvivid-content-size').hide();
                        jQuery('.wpvivid-content-size').prev().hide();
                        jQuery('.wpvivid-content-size').next().hide();

                        jQuery('.wpvivid-themes-size').hide();
                        jQuery('.wpvivid-themes-size').prev().hide();
                        jQuery('.wpvivid-themes-size').next().hide();

                        jQuery('.wpvivid-plugins-size').hide();
                        jQuery('.wpvivid-plugins-size').prev().hide();
                        jQuery('.wpvivid-plugins-size').next().hide();

                        jQuery('.wpvivid-uploads-size').hide();
                        jQuery('.wpvivid-uploads-size').prev().hide();
                        jQuery('.wpvivid-uploads-size').next().hide();

                        jQuery('.wpvivid-additional-folder-size').hide();
                        jQuery('.wpvivid-additional-folder-size').prev().hide();
                        jQuery('.wpvivid-additional-folder-size').next().hide();

                        jQuery('.wpvivid-total-file-size').hide();
                        jQuery('.wpvivid-total-file-size').prev().hide();
                        jQuery('.wpvivid-total-file-size').next().hide();

                        jQuery('.wpvivid-total-exclude-file-size').hide();
                        jQuery('.wpvivid-total-exclude-file-size').prev().hide();
                        jQuery('.wpvivid-total-exclude-file-size').next().hide();

                        jQuery('#wpvivid_recalc_backup_size').hide();
                        jQuery('#wpvivid_recalc_backup_size').next().hide();

                        jQuery('#wpvivid_recalc_migration_size').hide();
                        jQuery('#wpvivid_recalc_migration_size').next().hide();

                        jQuery('#wpvivid_recalc_schedule_backup_size').hide();
                        jQuery('#wpvivid_recalc_schedule_backup_size').next().hide();

                        jQuery('#wpvivid_recalc_update_schedule_backup_size').hide();
                        jQuery('#wpvivid_recalc_update_schedule_backup_size').next().hide();
                    }
                }
            });
        </script>
        <?php
    }
}

class WPvivid_Migrate_MU_Single_Site_Display
{
    public function __construct()
    {
        //new
        add_action('wpvivid_select_mu_single_site', array($this, 'select_mu_single_site'), 12, 4);

        add_filter('wpvivid_add_backup_type_addon', array($this, 'wpvivid_backuppage_add_backup_type'), 13, 2);
        //add_filter('wpvivid_export_site_content_addon', array($this, 'export_site_content_addon'), 13, 2);

        add_action('wp_ajax_wpvivid_get_single_mu_list', array($this, 'get_mu_list'));
        add_action('wp_ajax_wpvivid_get_single_mu_selected_list', array($this, 'get_mu_selected_list'));

        add_action('wp_ajax_wpvivid_get_single_mu_custom_themes_plugins_info', array($this, 'get_custom_themes_plugins_info'));
        add_action('wp_ajax_wpvivid_get_single_mu_custom_dir_uploads_info', array($this, 'get_custom_dir_uploads_info'));
        add_action('wp_ajax_wpvivid_get_single_mu_custom_dir_additional_info', array($this, 'get_custom_dir_additional_info'));
        add_action('wp_ajax_wpvivid_update_single_mu_exclude_extension', array($this, 'update_single_mu_exclude_extension'));
        //manual_backup
    }

    public function get_folder_size($root,$size)
    {
        $count = 0;
        if(is_dir($root))
        {
            $handler = opendir($root);
            if($handler!==false)
            {
                while (($filename = readdir($handler)) !== false)
                {
                    if ($filename != "." && $filename != "..") {
                        $count++;

                        if (is_dir($root . DIRECTORY_SEPARATOR . $filename))
                        {
                            $size=self::get_folder_size($root . DIRECTORY_SEPARATOR . $filename,$size);
                        } else {
                            $size+=filesize($root . DIRECTORY_SEPARATOR . $filename);
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }

        }
        return $size;
    }
    //old

    //new
    public function select_mu_single_site($mu_single_list_id, $type)
    {
        if(!is_multisite())
        {
            return ;
        }

        if($type!=='manual_backup' && $type!=='local_export_site' && $type!=='remote_export_site' && $type!=='migration_export_site')
        {
            return ;
        }

        ?>

        <p></p>

        <script>
            var archieve_info = {};
            archieve_info.db_retry    = 0;
            archieve_info.theme_retry = 0;

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.first-page',function() {
                wpvivid_get_mu_single_list('first', '<?php echo $mu_single_list_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_mu_single_list(page-1, '<?php echo $mu_single_list_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_mu_single_list(page+1, '<?php echo $mu_single_list_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.last-page',function() {
                wpvivid_get_mu_single_list('last', '<?php echo $mu_single_list_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_get_mu_single_list(page, '<?php echo $mu_single_list_id; ?>');
                }
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid-mu-single-search-submit').click(function()
            {
                wpvivid_get_single_mu_site_list('<?php echo $mu_single_list_id; ?>');
            });

            function wpvivid_get_single_mu_site_list(mu_single_list_id)
            {
                var search = jQuery('#'+mu_single_list_id).find('.wpvivid-mu-single-site-search-input').val();
                var ajax_data = {
                    'action': 'wpvivid_get_single_mu_list',
                    'search':search
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#'+mu_single_list_id).find('.wpvivid_mu_single_site_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#'+mu_single_list_id).find('.wpvivid_mu_single_site_list').html(jsonarray.html);
                            wpvivid_set_single_site_list_default(mu_single_list_id);
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

            function wpvivid_set_single_site_list_default(mu_single_list_id)
            {
                var site_id;
                jQuery('#'+mu_single_list_id).find('input[name=mu_site][type=checkbox]').each(function(index, value)
                {
                    jQuery(value).prop('checked', true);
                    //site_id=jQuery(this).val();
                    //wpvivid_get_mu_custom_themes_plugins_info(site_id);
                    return false;
                });
            }

            function wpvivid_get_mu_single_list(page, mu_single_list_id)
            {
                if(page==0)
                {
                    page =jQuery('#'+mu_single_list_id).find('.wpvivid_mu_single_site_list').find('.current-page').val();
                }
                var search = jQuery('#wpvivid-mu-single-site-search-input').val();
                var ajax_data = {
                    'action': 'wpvivid_get_single_mu_list',
                    'search':search,
                    'page':page
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#'+mu_single_list_id).find('.wpvivid_mu_single_site_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#'+mu_single_list_id).find('.wpvivid_mu_single_site_list').html(jsonarray.html);
                            wpvivid_set_single_site_list_default(mu_single_list_id);
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

            jQuery('#<?php echo $mu_single_list_id; ?>').on("click",'[name=mu_site]',function()
            {
                jQuery('#<?php echo $mu_single_list_id; ?>').find('input:checkbox[name=mu_site]').prop('checked', false);
                jQuery(this).prop('checked', true);
                //var site_id;
                //site_id=jQuery(this).val();

                //wpvivid_get_mu_custom_themes_plugins_info(site_id);
            });

            function wpvivid_get_mu_custom_themes_plugins_info(site_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_get_single_mu_custom_themes_plugins_info',
                    'id':'',
                    'subsite':site_id
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        //jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-themes-plugins-info').html('');
                        //jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-themes-plugins-info').html(jsonarray.html);
                        jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-exclude-themes-list').html('');
                        jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-exclude-themes-list').html(jsonarray.themes_list);
                        jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-exclude-plugins-list').html('');
                        jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-exclude-plugins-list').html(jsonarray.plugins_list);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var need_retry_custom_themes = false;
                    archieve_info.theme_retry++;
                    var retry_times = archieve_info.theme_retry;
                    if(retry_times < 10){
                        need_retry_custom_themes = true;
                    }
                    if(need_retry_custom_themes)
                    {
                        setTimeout(function()
                        {
                            wpvivid_get_mu_custom_themes_plugins_info(site_id);
                        }, 3000);
                    }
                    else
                    {
                        var refresh_btn = '<input type="submit" class="button-primary" value="Refresh" onclick="wpvivid_get_mu_custom_themes_plugins_info(\''+site_id+'\');">';
                        jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-themes-plugins-info').html('');
                        jQuery('#wpvivid_custom_mu_single_list').find('.wpvivid-custom-themes-plugins-info').html(refresh_btn);
                    }
                });
            }

            jQuery(document).ready(function ()
            {

            });
        </script>
        <?php
    }

    public function wpvivid_backuppage_add_backup_type($html, $type_name)
    {
        if(!is_multisite())
        {
            return $html;
        }
        $html = '';
        ob_start();
        ?>

        <label style="padding-right:2em;">
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="files+db" checked="checked">
            <span>Wordpress Files + Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="db">
            <span>Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="files">
            <span>Wordpress Files</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="custom" checked="checked">
            <span>Custom content</span>
        </label>
        <label>
            <input type="radio" option="backup" name="<?php esc_attr_e($type_name); ?>" value="mu">
            <span> For the purpose of moving a subsite to a single install</span>
        </label>

        <!--<div style="clear:both;"></div>
        <label class="wpvivid-radio" style="float:left; padding-right:1em;">Childsite: Migrate a MU child site to a single WP install
            <input type="radio" option="backup" name="backup_files" value="mu">
            <span class="wpvivid-radio-checkmark"></span>
        </label>
        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
            <div class="wpvivid-bottom">
                <p>This option allows you to migrate a childsite from a WordPress Multisite network to a standalone WordPress installation.</p>
                <i></i>
            </div>
        </span>-->
        <?php
        $html .= ob_get_clean();
        return $html;
    }

    public function export_site_content_addon($html, $type_name)
    {
        if(!is_multisite())
        {
            return $html;
        }
        $html = '';
        ob_start();
        ?>

        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="files+db" checked="checked">
            <span>Wordpress Files + Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="db">
            <span>Database</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="files">
            <span>Wordpress Files</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="mu">
            <span> For the purpose of moving a subsite to a single install</span>
        </label>
        <label style="padding-right:2em;">
            <input type="radio" option="<?php esc_attr_e($type_name); ?>" name="<?php esc_attr_e($type_name); ?>" value="custom">
            <span>Custom content</span>
        </label>


        <?php
        $html .= ob_get_clean();
        return $html;
    }

    public function get_site( $site = null )
    {
        if ( empty( $site ) ) {
            $site = get_current_blog_id();
        }

        if ( $site instanceof WP_Site ) {
            $_site = $site;
        } elseif ( is_object( $site ) ) {
            $_site = new WP_Site( $site );
        } else {
            $_site = WP_Site::get_instance( $site['blog_id'] );
        }

        if ( ! $_site ) {
            return null;
        }

        $_site = apply_filters( 'get_site', $_site );
        return $_site;
    }

    public function get_mu_list()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );

        try
        {
            //$args = array();
            $args = array(
                'number' => 999,
            );
            $list = array();


            if(isset($_POST['search']))
            {
                $search = sanitize_text_field($_POST['search']);

                if($search === '')
                {
                    $subsites=get_sites($args);
                }
                else
                {
                    $subsites = array();
                    global $wpdb;
                    $sql       = $wpdb->prepare( "SELECT * FROM {$wpdb->blogs}" );
                    $reg_blogs = $wpdb->get_results( $sql );

                    if(!empty($reg_blogs))
                    {
                        foreach ( $reg_blogs as $value )
                        {
                            $subsite['blog_id'] = $value->blog_id;
                            $subsite['domain']  = $value->domain;
                            $subsite['path']    = $value->path;
                            $subsite['site_id'] = $value->site_id;
                            $subsite['registered'] = $value->registered;
                            $subsite['last_updated'] = $value->last_updated;
                            $subsite['public']  = $value->public;
                            $subsite['archived']    = $value->archived;
                            $subsite['mature'] = $value->mature;
                            $subsite['spam'] = $value->spam;
                            $subsite['deleted']  = $value->deleted;
                            $subsite['lang_id']    = $value->lang_id;

                            switch_to_blog( $value->blog_id );

                            if (preg_match('/'.$search.'/', $subsite['domain'], $matches))
                            {
                                $subsites[] = $subsite;
                            }
                            else if (preg_match('/'.$search.'/', $subsite['path'], $matches))
                            {
                                $subsites[] = $subsite;
                            }
                            else if (preg_match('/'.$search.'/', get_option( 'blogname' ), $matches))
                            {
                                $subsites[] = $subsite;
                            }

                            restore_current_blog();
                        }
                    }
                    $subsites = array_map( array($this, 'get_site'), $subsites );
                }
            }
            else
            {
                $subsites=get_sites($args);
            }

            $mu_site_list=new WPvivid_MU_Single_Site_List();

            foreach ($subsites as $subsite)
            {
                $list[]=$subsite;
            }

            if(isset($_POST['page']))
            {
                $mu_site_list->set_list($list,'mu_site',$_POST['page']);
            }
            else
            {
                $mu_site_list->set_list($list,'mu_site');
            }

            $mu_site_list->prepare_items();
            ob_start();
            $mu_site_list->display();
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

    public function get_mu_selected_list()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );

        try
        {
            $args = array();
            $list = array();

            $subsites=get_sites($args);

            $mu_site_list=new WPvivid_MU_Single_Site_List();

            $site_id=$_POST['site_id'];

            foreach ($subsites as $subsite)
            {
                if($site_id==get_object_vars($subsite)["blog_id"])
                    $list[]=$subsite;
            }

            $mu_site_list->set_list($list,'mu_selected_site');

            $mu_site_list->prepare_items();
            ob_start();
            $mu_site_list->display();
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

    public function get_custom_themes_plugins_info()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try
        {
            $single_mu_option = array();

            $checkbox_disable = ' disabled';
            $themes_path = get_theme_root();
            $has_themes = false;
            $themes_table = '';
            $themes_table_html = '';
            $themes_count = 0;
            $themes_all_check = 'checked';
            $themes_info = array();

            $themes = wp_get_themes();

            $themes_list = '';
            $plugins_list = '';

            if (!empty($themes))
            {
                $has_themes = true;
            }

            foreach ($themes as $theme)
            {
                $file = $theme->get_stylesheet();
                $themes_info[$file] = $this->get_theme_plugin_info($themes_path . DIRECTORY_SEPARATOR . $file);
                $parent=$theme->parent();
                $themes_info[$file]['parent']=$parent;
                $themes_info[$file]['parent_file']=$theme->get_template();
                $themes_info[$file]['child']=array();

                if(isset($_POST['subsite']))
                {
                    switch_to_blog($_POST['subsite']);
                    $ct = wp_get_theme();
                    if( $ct->get_stylesheet()==$file)
                    {
                        $themes_info[$file]['active'] = 1;
                    }
                    else
                    {
                        $themes_info[$file]['active'] = 0;
                    }
                    restore_current_blog();
                }
                else
                {
                    $themes_info[$file]['active'] = 1;
                }
            }

            foreach ($themes_info as $file => $info)
            {
                if($info['active']&&$info['parent']!=false)
                {
                    $themes_info[$info['parent_file']]['active']=1;
                    $themes_info[$info['parent_file']]['child'][]=$file;
                }
            }

            foreach ($themes_info as $file => $info) {
                $checked = '';

                if ($info['active'] == 1) {
                    $checked = 'checked';
                }

                if (!empty($single_mu_option['themes_list'])) {
                    if (in_array($file, $single_mu_option['themes_list'])) {
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

                if($checked === '')
                {
                    $themes_list .= '<div class="wpvivid-text-line" type="folder">
                                    <span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree"></span><span class="dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer"></span><span class="wpvivid-text-line">'.esc_html($file).'</span>
                                </div>';
                }

                $themes_count++;
            }
            $themes_table .= '<div style="clear:both;"></div>';
            $ret['result'] = 'success';
            $ret['themes_info'] = $themes_info;
            if ($has_themes) {
                $themes_table_html .= '<div class="wpvivid-custom-database-wp-table-header" style="border:1px solid #e5e5e5;">
                                        <label class="wpvivid-checkbox">
                                        <input type="checkbox" class="wpvivid-themes-plugins-table-check wpvivid-themes-table-check" ' . esc_attr($themes_all_check . $checkbox_disable) . ' style="opacity: 0" />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>Themes
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
            $plugin_count = 0;
            $plugins_all_check = 'checked';
            $plugin_info = array();

            if (!function_exists('get_plugins'))
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $plugins = get_plugins();

            if (!empty($plugins)) {
                $has_plugins = true;
            }

            if(isset($_POST['subsite']))
            {
                switch_to_blog($_POST['subsite']);
                $current   = get_option( 'active_plugins', array() );
                restore_current_blog();
            }
            else
            {
                $current   = get_option( 'active_plugins', array() );
            }


            foreach ($plugins as $key => $plugin)
            {
                $slug = dirname($key);
                if ($slug == '.')
                    continue;
                $plugin_info[$slug] = $this->get_theme_plugin_info($path . DIRECTORY_SEPARATOR . $slug);
                $plugin_info[$slug]['Name'] = $plugin['Name'];
                $plugin_info[$slug]['slug'] = $slug;

                if(isset($_POST['subsite']))
                {
                    if(in_array($key,$current))
                    {
                        $plugin_info[$slug]['active'] = 1;
                    }
                    else
                    {
                        if ( is_plugin_active_for_network( $key ) )
                        {
                            $plugin_info[$slug]['active'] = 1;
                        }
                        else
                        {
                            $plugin_info[$slug]['active'] = 0;
                        }

                    }
                }
                else
                {
                    $plugin_info[$slug]['active'] = 1;
                }
            }

            foreach ($plugin_info as $slug => $info)
            {
                $checked = '';

                if ($info['active'] == 1) {
                    $checked = 'checked';
                }

                if (!empty($single_mu_option['plugins_list'])) {
                    if (in_array($slug, $single_mu_option['plugins_list'])) {
                        $checked = '';
                    }
                }

                if (empty($checked)) {
                    $plugins_all_check = '';
                }

                $disable_check = '';
                if ($info['slug'] == 'wpvivid-backuprestore' || $info['slug'] == 'wpvivid-backup-pro' || $slug == 'wpvividdashboard')
                {
                    $checked = '';
                    $disable_check = 'disabled';
                }
                $plugins_table .= '<div class="wpvivid-custom-database-table-column">
                                        <label class="wpvivid-checkbox" style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;" 
                                        title="' . esc_html($info['Name']) . '|Size:' . size_format($info["size"], 2) . '">
                                        <input type="checkbox" option="plugins" name="Plugins" value="' . esc_attr($info['slug']) . '" ' . esc_html($checked) . ' ' . $disable_check . ' />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>
                                        ' . esc_html($info['Name']) . '|Size:' . size_format($info["size"], 2) . '</label>
                                    </div>';

                if($checked === '')
                {
                    $plugins_list .= '<div class="wpvivid-text-line" type="folder">
                                    <span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree"></span><span class="dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer"></span><span class="wpvivid-text-line">'.esc_html($slug).'</span>
                                  </div>';
                }
                $plugin_count++;
            }

            $plugins_table .= '<div style="clear:both;"></div>';
            $ret['plugin_info'] = $plugin_info;
            if ($has_plugins) {
                $plugins_table_html .= '<div class="wpvivid-custom-database-other-table-header" style="border:1px solid #e5e5e5;">
                                        <label class="wpvivid-checkbox">
                                        <input type="checkbox" class="wpvivid-themes-plugins-table-check wpvivid-plugins-table-check" ' . esc_attr($plugins_all_check . $checkbox_disable) . ' style="opacity: 0" />
                                        <span class="wpvivid-checkbox-checkmark" style="top: 5px;"></span>Plugins
                                        </label>
                                     </div>
                                     <div class="wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        ' . $plugins_table . '
                                     </div>';
            }
            $ret['html'] .= $plugins_table_html;

            $ret['themes_list'] = $themes_list;
            $ret['plugins_list'] = $plugins_list;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_theme_plugin_info($root)
    {
        $theme_info['size']=$this->get_folder_size($root,0);
        return $theme_info;
    }

    public function get_custom_dir_uploads_info()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try {
            $node_array = array();

            if ($_POST['tree_node']['node']['id'] == '#') {
                $path = ABSPATH;

                if (!empty($_POST['tree_node']['path'])) {
                    $path = $_POST['tree_node']['path'];
                }

                $node_array[] = array(
                    'text' => basename($path),
                    'children' => true,
                    'id' => $path,
                    'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer',
                    'state' => array(
                        'opened' => true
                    )
                );
            } else {
                $path = $_POST['tree_node']['node']['id'];
            }

            $path = trailingslashit(str_replace('\\', '/', realpath($path)));

            if ($dh = opendir($path)) {
                while (substr($path, -1) == '/') {
                    $path = rtrim($path, '/');
                }
                $skip_paths = array(".", "..");

                while (($value = readdir($dh)) !== false) {
                    trailingslashit(str_replace('\\', '/', $value));
                    if (!in_array($value, $skip_paths)) {
                        $custom_dir = WP_CONTENT_DIR . '/' . WPvivid_Custom_Interface_addon::wpvivid_get_content_backupdir();
                        $custom_dir = str_replace('\\', '/', $custom_dir);

                        $themes_dir = get_theme_root();
                        $themes_dir = trailingslashit(str_replace('\\', '/', $themes_dir));
                        //$themes_dir = str_replace($content_dir, '', $themes_dir);
                        $themes_dir = rtrim($themes_dir, '/');

                        $plugin_dir = WP_PLUGIN_DIR;
                        $plugin_dir = trailingslashit(str_replace('\\', '/', $plugin_dir));
                        //$plugin_dir = str_replace($content_dir, '', $plugin_dir);
                        $plugin_dir = rtrim($plugin_dir, '/');

                        $upload_dir = wp_upload_dir();
                        $upload_dir['basedir'] = trailingslashit(str_replace('\\', '/', $upload_dir['basedir']));
                        $upload_dir['basedir'] = rtrim($upload_dir['basedir'], '/');
                        $subsite_dir = rtrim($upload_dir['basedir'], '/') . '/' . 'sites';

                        $exclude_dir = array($themes_dir, $plugin_dir, $upload_dir['basedir'], $custom_dir, $subsite_dir);
                        if (is_dir($path . '/' . $value)) {
                            if (!in_array($path . '/' . $value, $exclude_dir)) {
                                $node['text'] = $value;
                                $node['children'] = true;
                                $node['id'] = $path . '/' . $value;
                                $node['icon'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                                $node_array[] = $node;
                            }
                        }
                        else{
                            $node['text'] = $value;
                            $node['children'] = true;
                            $node['id'] = $path . '/' . $value;
                            $node['icon'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                            $node_array[] = $node;
                        }
                    }
                }
            }

            $ret['nodes'] = $node_array;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_custom_dir_additional_info()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try {
            $node_array = array();

            if ($_POST['tree_node']['node']['id'] == '#') {
                $path = ABSPATH;

                if (!empty($_POST['tree_node']['path'])) {
                    $path = $_POST['tree_node']['path'];
                }

                if (isset($_POST['select_prev_dir']) && $_POST['select_prev_dir'] === '1') {
                    $path = dirname($path);
                }

                $node_array[] = array(
                    'text' => basename($path),
                    'children' => true,
                    'id' => $path,
                    'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer',
                    'state' => array(
                        'opened' => true
                    )
                );
            } else {
                $path = $_POST['tree_node']['node']['id'];
            }

            $path = trailingslashit(str_replace('\\', '/', realpath($path)));

            if ($dh = opendir($path)) {
                while (substr($path, -1) == '/') {
                    $path = rtrim($path, '/');
                }

                $skip_paths = array(".", "..");

                $file_array = array();

                while (($value = readdir($dh)) !== false) {
                    trailingslashit(str_replace('\\', '/', $value));

                    if (!in_array($value, $skip_paths)) {
                        if (is_dir($path . '/' . $value)) {
                            $wp_admin_path = ABSPATH . 'wp-admin';
                            $wp_admin_path = str_replace('\\', '/', $wp_admin_path);

                            $wp_include_path = ABSPATH . 'wp-includes';
                            $wp_include_path = str_replace('\\', '/', $wp_include_path);

                            $content_dir = WP_CONTENT_DIR;
                            $content_dir = str_replace('\\', '/', $content_dir);
                            $content_dir = rtrim($content_dir, '/');

                            $lotties_dir = ABSPATH . 'lotties';
                            $lotties_dir = str_replace('\\', '/', $lotties_dir);

                            $exclude_dir = array($wp_admin_path, $wp_include_path, $content_dir, $lotties_dir);
                            if (!in_array($path . '/' . $value, $exclude_dir)) {
                                $node_array[] = array(
                                    'text' => $value,
                                    'children' => true,
                                    'id' => $path . '/' . $value,
                                    'icon' => 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'
                                );
                            }

                        } else {

                            $wp_admin_path = ABSPATH;
                            $wp_admin_path = str_replace('\\', '/', $wp_admin_path);
                            $wp_admin_path = rtrim($wp_admin_path, '/');
                            $skip_path = rtrim($path, '/');

                            if ($wp_admin_path == $skip_path) {
                                continue;
                            }
                            $file_array[] = array(
                                'text' => $value,
                                'children' => false,
                                'id' => $path . '/' . $value,
                                'type' => 'file',
                                'icon' => 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer'
                            );
                        }
                    }
                }
                $node_array = array_merge($node_array, $file_array);
            }

            $ret['nodes'] = $node_array;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function update_single_mu_exclude_extension()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try {
            if (isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']) &&
                isset($_POST['exclude_content']) && !empty($_POST['exclude_content']) && is_string($_POST['exclude_content'])) {
                $type = sanitize_text_field($_POST['type']);
                $value = sanitize_text_field($_POST['exclude_content']);

                $single_mu_option = self::wpvivid_get_single_mu_history();
                if (empty($single_mu_option)) {
                    $single_mu_option = array();
                }

                if ($type === 'upload') {
                    $single_mu_option['upload_extension'] = array();
                    $str_tmp = explode(',', $value);
                    for ($index = 0; $index < count($str_tmp); $index++) {
                        if (!empty($str_tmp[$index])) {
                            $single_mu_option['upload_extension'][] = $str_tmp[$index];
                        }
                    }
                } else if ($type === 'content') {
                    $single_mu_option['content_extension'] = array();
                    $str_tmp = explode(',', $value);
                    for ($index = 0; $index < count($str_tmp); $index++) {
                        if (!empty($str_tmp[$index])) {
                            $single_mu_option['content_extension'][] = $str_tmp[$index];
                        }
                    }
                } else if ($type === 'additional_file') {
                    $single_mu_option['additional_file_extension'] = array();
                    $str_tmp = explode(',', $value);
                    for ($index = 0; $index < count($str_tmp); $index++) {
                        if (!empty($str_tmp[$index])) {
                            $single_mu_option['additional_file_extension'][] = $str_tmp[$index];
                        }
                    }
                }

                self::wpvivid_set_single_mu_history($single_mu_option);

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

    public static function wpvivid_set_single_mu_history($option){
        update_option('wpvivid_single_mu_history', $option, 'no');
    }

    public static function wpvivid_get_single_mu_history(){
        $options = get_option('wpvivid_single_mu_history', array());
        return $options;
    }
}