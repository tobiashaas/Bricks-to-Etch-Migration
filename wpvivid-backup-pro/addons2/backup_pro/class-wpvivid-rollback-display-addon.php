<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Admin_load: yes
 * Need_init: yes
 * Interface Name: WPvivid_Rollback_Display_Addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPvivid_Rollback_Plugins_List extends WP_List_Table
{
    public $page_num;
    public $plugins_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'wpvivid_plugins',
                'screen' => 'wpvivid_plugins'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat plugins striped wpvivid-backup-list' );
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
                $class[] = 'wpvivid-check-column';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';
            if ( ! empty( $class ) )
            {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            if ( $column_key === 'wpvivid_status' )
            {
                $column_display_name='<div>Status
													<span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
													<div class="wpvivid-bottom">
														<!-- The content you need -->
														<p>Enable/Disable "Back up the plugin(s) before update".</p>
														<i></i> <!-- do not delete this line -->
													</div>
													</span>
													</div>';
            }
            if($column_key=="wpvivid_version_backup")
            {
                $style="style='text-align: center;'";
            }
            else
            {
                $style="";
            }

            echo "<$tag $scope $id $class $style>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['wpvivid_status'] = __( 'Status', 'wpvivid' );
        $columns['wpvivid_plugins'] = __( 'Plugins', 'wpvivid' );
        $columns['wpvivid_date2'] = __( 'Date', 'wpvivid' );
        $columns['wpvivid_version'] =__( 'Current/Latest', 'wpvivid'  );
        $columns['wpvivid_version_backup'] = __( 'Versioning Backups', 'wpvivid' );
        $columns['wpvivid_rollback'] = __( 'Rollback', 'wpvivid' );
        $columns['wpvivid_location'] = __( 'Location', 'wpvivid' );
        return $columns;
    }

    public function column_cb( $plugin )
    {
        $html='<input type="checkbox"/>';
        echo $html;
    }

    public function _column_wpvivid_status( $plugin )
    {
        if(isset($plugin['enable_auto_backup'])&&$plugin['enable_auto_backup'])
        {
            $enable = 'checked';
        }
        else
        {
            $enable = '';
        }
        ?>
        <td scope="col" class="manage-column column-wpvivid_backup column-primary">
            <label class="wpvivid-switch" title="Enable/Disable the job">
                <input class="wpvivid-enable-auto-backup" type="checkbox" <?php echo $enable;?> />
                <span class="wpvivid-slider wpvivid-round"></span>
            </label>
        </td>
        <?php
    }

    public function _column_wpvivid_plugins( $plugin )
    {
        if(strlen($plugin['Name'])>30)
        {
            if (function_exists('mb_substr'))
            {
                $str = mb_substr($plugin['Name'], 0, 30);
            }
            else
            {
                $str = substr($plugin['Name'], 0, 30);
            }
            ?>
            <td scope="col" id="wpvivid_content" class="manage-column"><span><?php echo $str?></span><span>...</span></td>
            <?php
        }
        else
        {
            $plugin['Name'] = apply_filters('wpvivid_white_label_display_pro_ex', $plugin['Name']);
            ?>
            <td scope="col" id="wpvivid_content" class="manage-column"><?php echo $plugin['Name']?></td>
            <?php
        }
    }

    public function _column_wpvivid_date2( $plugin )
    {
        ?>
        <td scope="col" class="manage-column">
            <span>
                <?php
                if(isset($plugin['rollback_data']['update_time']))
                {
                    $offset=get_option('gmt_offset');
                    $time =date("Y-m-d H:i:s",$plugin['rollback_data']['update_time']+$offset*60*60);
                    echo $time;
                }
                else
                {
                    echo "N/A";
                }
                ?>
            </span>
        </td>
        <?php
    }

    public function _column_wpvivid_version( $plugin )
    {
        ?>
        <td scope="col" class="manage-column wpvivid-version">
            <span class="current-version"><?php echo $plugin['Version']?></span><span style="padding:0 0.3rem">|</span><span><?php echo $plugin['response']['new_version']?></span>
        </td>
        <?php
    }

    public function _column_wpvivid_version_backup( $plugin )
    {
        ?>
        <td scope="col" class="manage-column">
            <span class="wpvivid-view-plugin-versions dashicons dashicons-visibility wpvivid-dashicons-grey" title="View details" style="cursor:pointer;margin: 0 auto;display: table;"></span>
        </td>
        <?php
    }

    public function _column_wpvivid_rollback( $plugin )
    {
        $rollback=$plugin['rollback'];
        $rollback_data=$plugin['rollback_data'];
        if(!empty($rollback_data)&&!empty($rollback_data['version']))
        {
            $versions=$rollback_data['version'];
        }
        else
        {
            $versions=array();
        }

        $rollback_version=array();
        if(!empty($rollback))
        {
            foreach ($rollback as $version=>$file)
            {
                $rollback_version[$version]=$version;
            }
        }

        if(!empty($versions))
        {
            foreach ($versions as $version=>$data)
            {
                if($data['upload'])
                    $rollback_version[$version]=$version;
            }
        }
        ?>
        <td scope="col" class="manage-column">
            <select>
                <option value="-" selected="selected">-</option>
                <?php
                foreach ($rollback_version as $version)
                {
                    echo '<option value="'.$version.'">'.$version.'</option>';
                }
                ?>
            </select>
            <input class="wpvivid-plugin-rollback button action" type="submit" value="Rollback">
        </td>
        <?php
    }

    public function _column_wpvivid_location( $plugin )
    {
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
                $type=$remote_option['type'];
                $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
                if($type=='amazons3')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='b2')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='dropbox')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='ftp')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='googledrive')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='nextcloud')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='onedrive')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='pCloud')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='s3compat')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='sftp')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='wasabi')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='webdav')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\" class=\"dashicons dashicons-cloud-upload wpvivid-dashicons-blue\"></span>";
                }
                echo "<td>";
                $icon=$this->get_storage_icon();
                echo $icon;
                echo "</td>";
            }
            else
            {
                echo "<td>";
                echo "<span style='margin: 0 auto;display: table;' title='Localhost' class='dashicons dashicons-desktop wpvivid-dashicons-blue'></span>";
                echo "</td>";
            }
        }
        else
        {
            echo "<td>";
            echo "<span style='margin: 0 auto;display: table;' title='Localhost' class='dashicons dashicons-desktop wpvivid-dashicons-blue'></span>";
            echo "</td>";
        }
    }

    public function get_storage_icon()
    {
        $type="";
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
                $type=$remote_option['type'];
            }
        }

        $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
        if($type=='amazons3')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='b2')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='dropbox')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='ftp')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='googledrive')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='nextcloud')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='onedrive')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='pCloud')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='s3compat')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='sftp')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='wasabi')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='webdav')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\" class=\"dashicons dashicons-cloud-upload wpvivid-dashicons-blue\"></span>";
        }
        return $icon;
    }

    public function set_list($plugins,$page_num=1)
    {
        $this->plugins_list=$plugins;
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

        if(empty($this->plugins_list))
        {
            $total_items=0;
        }
        else
        {
            $total_items =sizeof($this->plugins_list);
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 999,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->plugins_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->plugins_list);
    }

    private function _display_rows($plugins_list)
    {
        $page=$this->get_pagenum();

        $page_plugins_list=array();
        $temp_page_plugins_list=array();

        if(empty($plugins_list))
        {
            return;
        }

        foreach ( $plugins_list as $key=>$plugin)
        {
            $page_plugins_list[$key]=$plugin;
        }

        $count=0;
        while ( $count<$page )
        {
            $temp_page_plugins_list = array_splice( $page_plugins_list, 0, 999);
            $count++;
        }

        foreach ( $temp_page_plugins_list as $key=>$plugin)
        {
            $plugin['plugin_file']=$key;
            $this->single_row($plugin);
        }
    }

    public function single_row($plugin)
    {
        $row_style = 'display: table-row;';

        if(is_plugin_active($plugin['plugin_file']))
        {
            $class='active';
        }
        else
        {
            $class='';
        }
        ?>
        <tr style="<?php echo $row_style?>" class='wpvivid-backup-row <?php echo $class?>' id="<?php echo $plugin['slug'];?>">
            <?php $this->single_row_columns( $plugin ); ?>
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
            $action_id="wpvivid_plugins_bulk_action";
            $btn_id="wpvivid_apply_plugins_bulk_action";
        }
        else if( 'bottom' === $which ) {
            $css_type = 'margin: 10px 0 0 0';
            $action_id="wpvivid_plugins_bulk_action_bottom";
            $btn_id="wpvivid_apply_plugins_bulk_action_bottom";
        }
        else
        {
            $action_id="wpvivid_plugins_bulk_action";
            $btn_id="wpvivid_apply_plugins_bulk_action";
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <div class="alignleft actions bulkactions" style="padding:0.5rem 0;">
                    <label for="wpvivid_plugins_bulk_action" class="screen-reader-text">Select bulk action</label>
                    <select class="wpvivid-plugins-bulk-action" id="<?php echo esc_attr( $action_id ); ?>">
                        <option value="-1" selected="selected">Bulk Actions</option>
                        <option value="enable">Enable</option>
                        <option value="disable">Disable</option>
                        <option value="enable_all">Enable All</option>
                        <option value="disable_all">Disable All</option>
                        <option value="enable_active">Enable Active</option>
                        <option value="delete">Delete</option>
                    </select>
                    <input type="submit" id="<?php echo esc_attr( $btn_id ); ?>" class="button action" value="Apply">
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
            <div class="alignleft actions bulkactions" style="padding:0.5rem 0;">
                <label for="wpvivid_plugins_bulk_action" class="screen-reader-text">Select bulk action</label>
                <select class="wpvivid-plugins-bulk-action" id="<?php echo esc_attr( $action_id ); ?>">
                    <option value="-1" selected="selected">Bulk Actions</option>
                    <option value="enable">Enable</option>
                    <option value="disable">Disable</option>
                    <option value="enable_all">Enable All</option>
                    <option value="disable_all">Disable All</option>
                    <option value="enable_active">Enable Active</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" id="<?php echo esc_attr( $btn_id ); ?>" class="button action" value="Apply">
            </div>
            <?php
        }
    }
}

class WPvivid_Themes_List extends WP_List_Table
{
    public $page_num;
    public $themes_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'wpvivid_themes',
                'screen' => 'wpvivid_themes'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat plugins striped wpvivid-backup-list' );
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
                $class[] = 'wpvivid-check-column';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( ! empty( $class ) )
            {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            if($column_key=="wpvivid_status")
            {
                $column_display_name='<div>Status
													<span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top" style="padding-top: 0px;">
													<div class="wpvivid-bottom">
														<!-- The content you need -->
														<p>Enable/Disable "Back up the theme(s) before update".</p>
														<i></i> <!-- do not delete this line -->
													</div>
													</span>
													</div>';
            }

            if($column_key=="wpvivid_version_backup")
            {
                $style="style='text-align: center;'";
            }
            else
            {
                $style="";
            }

            echo "<$tag $scope $id $class $style>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['wpvivid_status'] = __( 'Status', 'wpvivid' );
        $columns['wpvivid_themes'] = __( 'Themes', 'wpvivid' );
        $columns['wpvivid_date'] = __( 'Date', 'wpvivid' );
        $columns['wpvivid_version'] =__( 'Current/Latest', 'wpvivid'  );
        $columns['wpvivid_version_backup'] = __( 'Versioning Backups', 'wpvivid' );
        $columns['wpvivid_rollback'] = __( 'Rollback', 'wpvivid' );
        $columns['wpvivid_location'] = __( 'Location', 'wpvivid' );
        return $columns;
    }

    public function column_cb( $theme )
    {
        $html='<input type="checkbox"/>';
        echo $html;
    }

    public function _column_wpvivid_status( $theme )
    {
        if(isset($theme['enable_auto_backup'])&&$theme['enable_auto_backup'])
        {
            $enable = 'checked';
        }
        else
        {
            $enable = '';
        }
        ?>
        <td scope="col" class="manage-column column-primary">
            <label class="wpvivid-switch" title="Enable/Disable the job">
                <input class="wpvivid-enable-auto-backup" type="checkbox" <?php echo $enable;?> />
                <span class="wpvivid-slider wpvivid-round"></span>
            </label>
        </td>
        <?php
    }

    public function _column_wpvivid_themes( $theme )
    {
        if(strlen($theme['name'])>30)
        {
            if (function_exists('mb_substr'))
            {
                $str = mb_substr($theme['name'], 0, 30);
            }
            else
            {
                $str = substr($theme['name'], 0, 30);
            }
            ?>
            <td scope="col" id="wpvivid_content" class="manage-column"><span><?php echo $str?></span><span>...</span></td>
            <?php
        }
        else
        {
            ?>
            <td scope="col" id="wpvivid_content" class="manage-column"><?php echo $theme['name']?></td>
            <?php
        }
    }

    public function _column_wpvivid_date( $theme )
    {
        ?>
        <td scope="col" class="manage-column">
            <span>
                <?php
                if(isset($theme['rollback_data']['update_time']))
                {
                    $offset=get_option('gmt_offset');
                    $time =date("Y-m-d H:i:s",$theme['rollback_data']['update_time']+$offset*60*60);
                    echo $time;
                }
                else
                {
                    echo "N/A";
                }
                ?>
            </span>
        </td>
        <?php
    }

    public function _column_wpvivid_version( $theme )
    {
        ?>
        <td scope="col" class="manage-column">
            <span class="current-version"><?php echo $theme['version']?></span><span style="padding:0 0.3rem">|</span><span><?php echo $theme['new_version']?></span>
        </td>
        <?php
    }

    public function _column_wpvivid_version_backup( $theme )
    {
        ?>
        <td scope="col" class="manage-column">
            <span class="wpvivid-view-theme-versions dashicons dashicons-visibility wpvivid-dashicons-grey" title="View details" style="cursor:pointer;margin: 0 auto;display: table;"></span>
        </td>
        <?php
    }

    public function _column_wpvivid_rollback( $theme )
    {
        $rollback=$theme['rollback'];
        $rollback_data=$theme['rollback_data'];
        if(!empty($rollback_data)&&!empty($rollback_data['version']))
        {
            $versions=$rollback_data['version'];
        }
        else
        {
            $versions=array();
        }

        $rollback_version=array();
        if(!empty($rollback))
        {
            foreach ($rollback as $version=>$file)
            {
                $rollback_version[$version]=$version;
            }
        }

        if(!empty($versions))
        {
            foreach ($versions as $version=>$data)
            {
                if($data['upload'])
                    $rollback_version[$version]=$version;
            }
        }
        ?>
        <td scope="col" class="manage-column">
            <select>
                <option value="-" selected="selected">-</option>
                <?php
                foreach ($rollback_version as $version)
                {
                    echo '<option value="'.$version.'">'.$version.'</option>';
                }
                ?>
            </select>
            <input class="wpvivid-theme-rollback button action" type="submit" value="Rollback">
        </td>
        <?php
    }

    public function _column_wpvivid_location( $plugin )
    {
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
                $type=$remote_option['type'];
                $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
                if($type=='amazons3')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='b2')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='dropbox')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='ftp')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='googledrive')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='nextcloud')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='onedrive')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='pCloud')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='s3compat')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='sftp')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='wasabi')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else if($type=='webdav')
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
                }
                else
                {
                    $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\" class=\"dashicons dashicons-cloud-upload wpvivid-dashicons-blue\"></span>";
                }
                echo "<td>";
                $icon=$this->get_storage_icon();
                echo $icon;
                echo "</td>";
            }
            else
            {
                echo "<td>";
                echo "<span style='margin: 0 auto;display: table;' title='Localhost' class='dashicons dashicons-desktop wpvivid-dashicons-blue'></span>";
                echo "</td>";
            }
        }
        else
        {
            echo "<td>";
            echo "<span style='margin: 0 auto;display: table;' title='Localhost' class='dashicons dashicons-desktop wpvivid-dashicons-blue'></span>";
            echo "</td>";
        }
    }

    public function get_storage_icon()
    {
        $type="";
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
                $type=$remote_option['type'];
            }
        }

        $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
        if($type=='amazons3')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='b2')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='dropbox')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='ftp')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='googledrive')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='nextcloud')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='onedrive')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='pCloud')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='s3compat')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='sftp')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='wasabi')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else if($type=='webdav')
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\"><img src='$assets_url/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /></span>";
        }
        else
        {
            $icon="<span style=\"margin: 0 auto;display: table;\" title=\"Cloud\" class=\"dashicons dashicons-cloud-upload wpvivid-dashicons-blue\"></span>";
        }
        return $icon;
    }

    public function set_list($plugins,$page_num=1)
    {
        $this->themes_list=$plugins;
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

        if(empty($this->themes_list))
        {
            $total_items=0;
        }
        else
        {
            $total_items =sizeof($this->themes_list);
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 30,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->themes_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->themes_list);
    }

    private function _display_rows($themes_list)
    {
        $page=$this->get_pagenum();

        $page_themes_list=array();
        $temp_page_themes_list=array();

        if(empty($themes_list))
        {
            return;
        }

        foreach ( $themes_list as $key=>$theme)
        {
            $page_themes_list[$key]=$theme;
        }

        $count=0;
        while ( $count<$page )
        {
            $temp_page_themes_list = array_splice( $page_themes_list, 0, 30);
            $count++;
        }

        foreach ( $temp_page_themes_list as $key=>$theme)
        {
            $this->single_row($theme);
        }
    }

    public function single_row($theme)
    {
        $row_style = 'display: table-row;';

        if ( get_stylesheet() === $theme['slug'] )
        {
            $class='active';
        }
        else
        {
            $class='';
        }
        ?>
        <tr style="<?php echo $row_style?>" class='wpvivid-backup-row <?php echo $class?>' id="<?php echo $theme['slug'];?>">
            <?php $this->single_row_columns( $theme ); ?>
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
            $action_id="wpvivid_themes_bulk_action";
            $btn_id="wpvivid_apply_themes_bulk_action";
        }
        else if( 'bottom' === $which ) {
            $css_type = 'margin: 10px 0 0 0';
            $action_id="wpvivid_themes_bulk_action_bottom";
            $btn_id="wpvivid_apply_themes_bulk_action_bottom";
        }
        else
        {
            $action_id="wpvivid_themes_bulk_action";
            $btn_id="wpvivid_apply_themes_bulk_action";
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <div class="alignleft actions bulkactions" style="padding:0.5rem 0;">
                    <label for="wpvivid_themes_bulk_action" class="screen-reader-text">Select bulk action</label>
                    <select class="wpvivid-themes-bulk-action" id="<?php echo esc_attr( $action_id ); ?>">
                        <option value="-1" selected="selected">Bulk Actions</option>
                        <option value="enable">Enable</option>
                        <option value="disable">Disable</option>
                        <option value="enable_all">Enable All</option>
                        <option value="disable_all">Disable All</option>
                        <option value="enable_active">Enable Active</option>
                        <option value="delete">Delete</option>
                    </select>
                    <input type="submit" id="<?php echo esc_attr( $btn_id ); ?>" class="button action" value="Apply">
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
            <div class="alignleft actions bulkactions" style="padding:0.5rem 0;">
                <label for="wpvivid_themes_bulk_action" class="screen-reader-text">Select bulk action</label>
                <select class="wpvivid-themes-bulk-action" id="<?php echo esc_attr( $action_id ); ?>">
                    <option value="-1" selected="selected">Bulk Actions</option>
                    <option value="enable">Enable</option>
                    <option value="disable">Disable</option>
                    <option value="enable_all">Enable All</option>
                    <option value="disable_all">Disable All</option>
                    <option value="enable_active">Enable Active</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" id="<?php echo esc_attr( $btn_id ); ?>" class="button action" value="Apply">
            </div>
            <?php
        }
    }
}

class WPvivid_Core_List extends WP_List_Table
{
    public $page_num;
    public $core_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'wpvivid_core',
                'screen' => 'wpvivid_core'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped plugins' );
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
                $class[] = 'wpvivid-check-column';
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
        $columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['wpvivid_version'] = __( 'Version', 'wpvivid' );
        $columns['wpvivid_modified'] = __( 'Modified', 'wpvivid' );
        $columns['wpvivid_size'] =__( 'Size', 'wpvivid'  );
        $columns['wpvivid_action'] = __( 'Action', 'wpvivid' );
        return $columns;
    }

    public function column_cb( $core )
    {
        $html='<input type="checkbox"/>';
        echo $html;
    }

    public function _column_wpvivid_version( $core )
    {
        ?>
        <td scope="col" class="manage-column column-primary">
            <strong>
                <?php echo $core['version'];?>
            </strong>
        </td>
        <?php
    }

    public function _column_wpvivid_modified( $core )
    {
        ?>
        <td scope="col" class="column-description desc">
            <?php echo $core['date']?>
        </td>
        <?php
    }

    public function _column_wpvivid_size( $core )
    {
        ?>
        <td scope="col" class="column-description desc">
            <?php echo $core['size']?>
        </td>
        <?php
    }

    public function _column_wpvivid_action( $core )
    {
        ?>
        <td class="column-description desc">
            <span class="dashicons dashicons-download wpvivid-dashicons-blue wpvivid-core-download" title="Download" style="cursor:pointer;"></span>
            <span class="dashicons dashicons-update-alt wpvivid-dashicons-blue wpvivid-rollback-core-version" style="cursor:pointer;" title="Rollback"></span>
        </td>
        <?php
    }

    public function set_list($core_list,$page_num=1)
    {
        $this->core_list=$core_list;
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

        if(empty($this->core_list))
        {
            $total_items=0;
        }
        else
        {
            $total_items =sizeof($this->core_list);
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 30,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->core_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->core_list);
    }

    private function _display_rows($core_list)
    {
        $page=$this->get_pagenum();

        $page_core_list=array();
        $temp_page_core_list=array();

        if(empty($core_list))
        {
            return;
        }

        foreach ( $core_list as $key=>$core)
        {
            $page_core_list[$key]=$core;
        }

        $count=0;
        while ( $count<$page )
        {
            $temp_page_core_list = array_splice( $page_core_list, 0, 30);
            $count++;
        }

        foreach ( $temp_page_core_list as $key=>$core)
        {
            $this->single_row($core);
        }
    }

    public function single_row($core)
    {
        $row_style = 'display: table-row;';

        $class='';
        ?>
        <tr style="<?php echo $row_style?>" class='wpvivid-backup-row <?php echo $class?>' id="<?php echo $core['id'];?>" data-version="<?php echo $core['id'];?>">
            <?php $this->single_row_columns( $core ); ?>
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
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <label for="wpvivid_rollback_core_bulk_action_select" class="screen-reader-text">Select bulk action</label>
                        <select name="action2" id="wpvivid_rollback_core_bulk_action_select">
                            <option value="-1">Bulk actions</option>
                            <option value="delete">Delete permanently</option>
                        </select>
                        <input type="submit" id="wpvivid_rollback_core_bulk_action" class="button action" value="Apply">
                    </div>
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
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <label for="wpvivid_rollback_core_bulk_action_select" class="screen-reader-text">Select bulk action</label>
                    <select name="action2" id="wpvivid_rollback_core_bulk_action_select">
                        <option value="-1">Bulk actions</option>
                        <option value="delete">Delete permanently</option>
                    </select>
                    <input type="submit" id="wpvivid_rollback_core_bulk_action" class="button action" value="Apply">
                </div>
            </div>
            <?php
        }
    }
}

class WPvivid_Rollback_List extends WP_List_Table
{
    public $page_num;
    public $rollback_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'wpvivid_rollback',
                'screen' => 'wpvivid_rollback'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped plugins' );
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
                $class[] = 'wpvivid-check-column';
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
        $columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['wpvivid_version'] = __( 'Version', 'wpvivid' );
        $columns['wpvivid_modified'] = __( 'Modified', 'wpvivid' );
        $columns['wpvivid_size'] =__( 'Size', 'wpvivid'  );
        $columns['wpvivid_action'] = __( 'Action', 'wpvivid' );
        return $columns;
    }

    public function column_cb( $rollback )
    {
        $html='<input type="checkbox"/>';
        echo $html;
    }

    public function _column_wpvivid_version( $rollback )
    {
        ?>
        <td class="plugin-title column-primary"><strong><?php echo $rollback['version']?></strong></td>
        <?php
    }

    public function _column_wpvivid_modified( $rollback )
    {
        ?>
        <td class="column-description desc"><?php echo $rollback['date']?></td>
        <?php
    }

    public function _column_wpvivid_size( $rollback )
    {
        ?>
        <td class="column-description desc"><?php echo $rollback['size']?></td>
        <?php
    }

    public function _column_wpvivid_action( $rollback )
    {
        ?>
        <td class="column-description desc">
            <span class="dashicons dashicons-download wpvivid-dashicons-blue wpvivid-rollback-download" title="Download" style="cursor:pointer;"></span>
            <span class="dashicons dashicons-update-alt wpvivid-dashicons-blue wpvivid-rollback-version" style="cursor:pointer;" title="Rollback"></span>
        </td>
        <?php
    }

    public function set_list($rollback_list,$page_num=1)
    {
        $this->rollback_list=$rollback_list;
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

        if(empty($this->plugins_list))
        {
            $total_items=0;
        }
        else
        {
            $total_items =sizeof($this->plugins_list);
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 30,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->rollback_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->rollback_list);
    }

    private function _display_rows($rollback_list)
    {
        $page=$this->get_pagenum();

        $page_rollback_list=array();
        $temp_page_rollback_list=array();

        if(empty($rollback_list))
        {
            return;
        }

        foreach ( $rollback_list as $key=>$rollback)
        {
            $page_rollback_list[$key]=$rollback;
        }

        $count=0;
        while ( $count<$page )
        {
            $temp_page_rollback_list = array_splice( $page_rollback_list, 0, 30);
            $count++;
        }

        foreach ( $temp_page_rollback_list as $key=>$rollback)
        {
            $this->single_row($rollback);
        }
    }

    public function single_row($rollback)
    {
        $row_style = 'display: table-row;';
        $class='';
        ?>
        <tr style="<?php echo $row_style?>" class='wpvivid-backup-row <?php echo $class?>' data-slug="<?php echo $rollback['slug'];?>" data-version="<?php echo $rollback['version'];?>">
            <?php $this->single_row_columns( $rollback ); ?>
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
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <label for="wpvivid_rollback_bulk_action_select_<?php echo esc_attr( $which ); ?>" class="screen-reader-text">Select bulk action</label>
                        <select name="action2" id="wpvivid_rollback_bulk_action_select_<?php echo esc_attr( $which ); ?>">
                            <option value="-1">Bulk actions</option>
                            <option value="delete">Delete permanently</option>
                        </select>
                        <input type="submit" id="wpvivid_rollback_bulk_action_<?php echo esc_attr( $which ); ?>" class="button action" value="Apply">
                    </div>
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
            <div class="tablenav <?php echo esc_attr( $which ); ?>">
                <div class="alignleft actions bulkactions">
                    <label for="wpvivid_rollback_bulk_action_select_<?php echo esc_attr( $which ); ?>" class="screen-reader-text">Select bulk action</label>
                    <select name="action2" id="wpvivid_rollback_bulk_action_select_<?php echo esc_attr( $which ); ?>">
                        <option value="-1">Bulk actions</option>
                        <option value="delete">Delete permanently</option>
                    </select>
                    <input type="submit" id="wpvivid_rollback_bulk_action_<?php echo esc_attr( $which ); ?>" class="button action" value="Apply">
                </div>
            </div>
            <?php
        }
    }
}

class WPvivid_Rollback_Display_Addon
{
    public $main_tab;
    public $already_backup_auto_update;

    public function __construct()
    {
        $this->already_backup_auto_update=false;

        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);
        add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),11);
        add_action('wp_ajax_wpvivid_rollback_plugin', array($this, 'rollback_plugin'));
        add_action('wp_ajax_wpvivid_rollback_theme', array($this, 'rollback_theme'));

        add_action('wp_ajax_wpvivid_enable_auto_backup', array($this, 'enable_auto_backup'));
        add_action('wp_ajax_wpvivid_plugins_enable_all_auto_backup', array($this, 'plugins_enable_all_auto_backup'));
        add_action('wp_ajax_wpvivid_themes_enable_all_auto_backup', array($this, 'themes_enable_all_auto_backup'));
        add_action('wp_ajax_wpvivid_plugins_delete_rollback_backup', array($this, 'plugins_delete_rollback_backup'));
        add_action('wp_ajax_wpvivid_themes_delete_rollback_backup', array($this, 'themes_delete_rollback_backup'));
        //
        add_action('wp_ajax_wpvivid_theme_enable_auto_backup', array($this, 'theme_enable_auto_backup'));
        //
        add_action('wp_ajax_wpvivid_view_plugin_versions', array($this, 'view_plugin_versions'));
        add_action('wp_ajax_wpvivid_view_theme_versions', array($this, 'view_theme_versions'));
        add_action('wp_ajax_wpvivid_plugins_enable_auto_backup', array($this, 'plugins_enable_auto_backup'));
        add_action('wp_ajax_wpvivid_themes_enable_auto_backup', array($this, 'themes_enable_auto_backup'));
        //
        add_action('wp_ajax_wpvivid_get_plugins_list', array($this, 'get_plugins_list'));
        add_action('wp_ajax_wpvivid_get_themes_list', array($this, 'get_themes_list'));

        add_action('wp_ajax_wpvivid_download_rollback', array($this, 'download_rollback'));
        add_action('wp_ajax_wpvivid_download_core_rollback', array($this, 'download_core_rollback'));
        //
        add_action('wp_ajax_wpvivid_get_rollback_list', array($this, 'get_rollback_list'));
        add_action('wp_ajax_wpvivid_delete_rollback', array($this, 'delete_rollback'));
        //
        add_action('wpvivid_before_setup_page',array($this,'auto_backup_page'));
        //
        add_action('wp_ajax_wpvivid_start_core_backup', array($this, 'start_backup_core'));

        add_action('wp_ajax_wpvivid_enable_core_auto_backup', array($this, 'enable_core_auto_backup'));

        add_action('wp_ajax_wpvivid_rollback_core', array($this, 'rollback_core'));
        add_action('wp_ajax_wpvivid_do_rollback_core', array($this, 'do_rollback_core'));
        add_action('wp_ajax_wpvivid_get_rollback_core_progress', array($this, 'get_rollback_core_progress'));
        add_action('wp_ajax_wpvivid_delete_core_rollback', array($this, 'delete_core_rollback'));
        add_action('wp_ajax_wpvivid_get_core_list', array($this, 'get_core_list'));
        //
        //add_action('init', array($this, 'init_rollback'));

        add_action('wp_ajax_wpvivid_set_rollback_setting', array($this, 'set_rollback_setting'));

        //send email report
        //export rollback setting
        add_filter('wpvivid_export_setting_addon', array($this, 'export_dashboard_info'), 11);
        //
        add_action('wp_ajax_wpvivid_prepare_rollback_file',array($this, 'prepare_rollback_file'), 10);
        add_action('wp_ajax_wpvivid_get_rollback_download_progress',array($this, 'get_rollback_download_progress'), 10);
        //
        add_action('wp_ajax_wpvivid_rollback_plugins_sync', array( $this,'rollback_plugins_sync'),10);
        add_action('wp_ajax_wpvivid_rollback_plugins_sync_continue', array( $this,'rollback_plugins_sync_continue'),10);

        add_action('wp_ajax_wpvivid_rollback_themes_sync', array( $this,'rollback_themes_sync'),10);
        add_action('wp_ajax_wpvivid_rollback_themes_sync_continue', array( $this,'rollback_themes_sync_continue'),10);

        //
        add_action('init', array($this, 'init_rollback_setting'));
    }

    public function init_rollback_setting()
    {
        $old_rollback_version=get_option('wpvivid_rollback_version', '0.0');
        if(version_compare($old_rollback_version, '1.0.1', '<'))
        {
            $rollback_delete_local=get_option("wpvivid_rollback_delete_local", 'not_set');
            if($rollback_delete_local === 'not_set')
            {
                update_option('wpvivid_rollback_retain_local', 0, 'no');
            }
            else
            {
                if($rollback_delete_local)
                {
                    update_option('wpvivid_rollback_retain_local', 0, 'no');
                }
                else
                {
                    update_option('wpvivid_rollback_retain_local', 1, 'no');
                }
            }
            update_option('wpvivid_rollback_version', '1.0.1', 'no');
        }
    }

    public function upload_rollback_file($file_name,$type,$slug,$version)
    {
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='no need upload';
                return $ret;
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='no need upload';
            return $ret;
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $result=$remote->upload_rollback($file_name,$type,$slug,$version);
        if($result['result']=='success')
        {
            $ret['result']='success';
            return $ret;
        }
        else
        {
            return $result;
        }


    }

    public static function wpvivid_rollback_get_siteurl()
    {
        $wpvivid_siteurl = array();
        $wpvivid_siteurl['home_url'] = home_url();
        $wpvivid_siteurl['plug_url'] = plugins_url();
        $wpvivid_siteurl['site_url'] = get_option( 'siteurl' );
        return $wpvivid_siteurl;
    }

    public function admin_load_themes()
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if(!$auto_backup_db_before_update)
        {
            return;
        }
        ?>
        <div id="wpvivid_dialog_modal" title="Backup Database">
            <h2>Please Wait...</h2>
            <div style="padding:1rem 0.5rem;">
                <div class="wpvivid-loader" style="float:left;"></div>
                <div style="padding-left:3rem;margin-top:0.68rem;" id="wpvivid_backup_progress_text">
                    Backing up database...
                </div>
                <div style="clear:both;"></div>
            </div>
            <div style="padding-left:0.4rem">
                <p>
                    <button id="wpvivid_backup_cancel" class="button">Cancel</button>
                    <span id="wpvivid_backup_progress_text2">Running time: 0 second(s)</span>
                </p>
            </div>
        </div>
        <script type="text/javascript">
            var wpvivid_siteurl = '<?php
                $wpvivid_siteurl = array();
                $wpvivid_siteurl=self::wpvivid_rollback_get_siteurl();
                echo esc_url($wpvivid_siteurl['site_url']);
                ?>';

            function wpvivid_rollback_cron_task()
            {
                jQuery.get(wpvivid_siteurl+'/wp-cron.php');
            }

            jQuery(document).ready(function($)
            {
                var wpvivid_updater = window.wp.updates;
                var wpvivid_backup_lock=false;

                $( "#wpvivid_dialog_modal" ).dialog({
                    dialogClass: 'noTitleStuff',
                    width:300,
                    modal: false,
                });

                $("#wpvivid_dialog_modal").dialog("close");

                jQuery(document).on('wp-theme-updating', function(e,args)
                {
                    wpvivid_update_plugins_test();
                });

                function wpvivid_update_plugins_test()
                {
                    if(wpvivid_backup_lock)
                    {
                        return;
                    }
                    wpvivid_updater.ajaxLocked=true;
                    wpvivid_backup_lock=true;
                    $( "#wpvivid_dialog_modal" ).dialog('open');
                    wpvivid_start_auto_backup();
                }

                var m_need_update=true;
                var task_retry_times=0;
                var wpvivid_prepare_backup=false;
                var running_backup_taskid='';
                var auto_backup_retry_times=0;

                function wpvivid_rollback_resume_backup(backup_id, next_resume_time)
                {
                    if(next_resume_time < 0){
                        next_resume_time = 0;
                    }
                    next_resume_time = next_resume_time * 1000;
                    setTimeout("wpvivid_rollback_cron_task()", next_resume_time);
                    setTimeout(function(){
                        task_retry_times = 0;
                        m_need_update=true;
                    }, next_resume_time);
                }

                function wpvivid_manage_task_ex()
                {
                    if(m_need_update === true)
                    {
                        m_need_update = false;
                        wpvivid_check_runningtask_ex();
                    }
                    else{
                        setTimeout(function()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);
                    }
                }

                function wpvivid_check_runningtask_ex()
                {
                    var ajax_data = {
                        'action': 'wpvivid_auto_new_backup_list_tasks',
                        'task_id': running_backup_taskid
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        setTimeout(function ()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);

                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);

                            var b_has_data = false;
                            if (jsonarray.backup.data.length !== 0)
                            {
                                b_has_data = true;
                                task_retry_times = 0;
                                if (jsonarray.backup.result === 'success')
                                {
                                    wpvivid_prepare_backup = false;
                                    jQuery.each(jsonarray.backup.data, function (index, value)
                                    {
                                        if (value.status.str === 'ready')
                                        {
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'running')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'wait_resume')
                                        {
                                            running_backup_taskid = index;
                                            if (value.data.next_resume_time !== 'get next resume time failed.')
                                            {
                                                wpvivid_rollback_resume_backup(index, value.data.next_resume_time);
                                            }
                                            else
                                            {
                                                wpvivid_delete_backup_task(index);
                                                finish_wpvivid_auto_backup();
                                            }
                                        }
                                        else if (value.status.str === 'no_responds')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'completed')
                                        {
                                            m_need_update = false;
                                            finish_wpvivid_auto_backup();
                                        }
                                        else if (value.status.str === 'error')
                                        {
                                            running_backup_taskid = '';
                                            m_need_update = true;
                                            finish_wpvivid_auto_backup();
                                        }

                                        jQuery('#wpvivid_backup_progress_text').html(value.progress_text);
                                        jQuery('#wpvivid_backup_progress_text2').html(value.progress_text2);
                                    });
                                }
                            }

                            if (!b_has_data)
                            {
                                task_retry_times++;
                                if (task_retry_times < 5)
                                {
                                    m_need_update = true;
                                }
                            }
                        }
                        catch(err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        setTimeout(function ()
                        {
                            m_need_update = true;
                            wpvivid_manage_task_ex();
                        }, 3000);
                    });
                }

                function wpvivid_start_auto_backup()
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup',
                        'backup':'db',
                        'backup_to_remote':0
                    };

                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            running_backup_taskid=jsonarray.task_id;
                            wpvivid_start_auto_backup_now(running_backup_taskid);
                            wpvivid_manage_task_ex();
                        }
                        else
                        {
                            auto_backup_retry_times++;
                            if(auto_backup_retry_times>3)
                            {
                                if(typeof jsonarray.error !== 'undefined')
                                {
                                    alert(jsonarray.error);
                                }
                                else
                                {
                                    alert('Backup failed');
                                }
                                location.href='<?php echo apply_filters('wpvivid_get_admin_url', '').'plugins.php'; ?>';
                            }
                            else
                            {
                                wpvivid_start_auto_backup();
                            }
                        }
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        auto_backup_retry_times++;
                        if(auto_backup_retry_times>3)
                        {
                            alert('Backup failed');
                        }
                        else
                        {
                            wpvivid_start_auto_backup();
                        }
                    });
                }

                function wpvivid_start_auto_backup_now(task_id)
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup_now',
                        'task_id':task_id
                    };
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                    });
                }

                function finish_wpvivid_auto_backup()
                {
                    wpvivid_updater.ajaxLocked=false;
                    wpvivid_updater.queueChecker();
                    setTimeout(function ()
                    {
                        $("#wpvivid_dialog_modal").dialog("close");
                    }, 1000);
                }
            });
        </script>
        <?php
    }

    public function admin_update_plugin()
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if(!$auto_backup_db_before_update)
        {
            return;
        }
        ?>
        <div id="wpvivid_dialog_modal">
            <h2>Please Wait...</h2>
            <div style="padding:1rem 0.5rem;">
                <div class="wpvivid-loader" style="float:left;"></div>
                <div style="padding-left:3rem;margin-top:0.68rem;" id="wpvivid_backup_progress_text">
                    Backing up database...
                </div>
                <div style="clear:both;"></div>
            </div>
            <div style="padding-left:0.4rem">
                <p>
                    <button id="wpvivid_backup_cancel" class="button">Cancel</button>
                    <span id="wpvivid_backup_progress_text2">Running time: 0 second(s)</span>
                </p>
            </div>
        </div>
        <script type="text/javascript">
            var wpvivid_siteurl = '<?php
                $wpvivid_siteurl = array();
                $wpvivid_siteurl=self::wpvivid_rollback_get_siteurl();
                echo esc_url($wpvivid_siteurl['site_url']);
                ?>';

            function wpvivid_rollback_cron_task()
            {
                jQuery.get(wpvivid_siteurl+'/wp-cron.php');
            }

            jQuery(document).ready(function($)
            {
                $( "#wpvivid_dialog_modal" ).dialog({
                    dialogClass: 'noTitleStuff',
                    width:300,
                    modal: false,
                });

                $("#wpvivid_dialog_modal").dialog("close");

                jQuery('#wpvivid_backup_cancel').click(function()
                {
                    wpvivid_updater.ajaxLocked=false;
                    wpvivid_updater.queueChecker();
                    $("#wpvivid_dialog_modal").dialog('close');
                });

                var wpvivid_updater = window.wp.updates;
                var wpvivid_backup_lock=false;

                jQuery(document).on('wp-plugin-bulk-update-selected', function(event)
                {
                    // Find all the checkboxes which have been checked.
                    var is_wpvivid_backup=false;
                    var itemsSelected = $( '#bulk-action-form' ).find( 'input[name="checked[]"]:checked' );

                    var select_count = 0;
                    itemsSelected.each( function( index, element )
                    {
                        var $checkbox = $( element ),
                            $itemRow = $checkbox.parents( 'tr' );
                        // Only add update-able items to the update queue.
                        if ( ! $itemRow.hasClass( 'update' ) || $itemRow.find( 'notice-error' ).length)
                        {
                            // Un-check the box.
                            $checkbox.prop( 'checked', false );
                            return;
                        }

                        if($itemRow.data( 'plugin' ) === 'wpvivid-backuprestore/wpvivid-backuprestore.php' || $itemRow.data( 'plugin' ) === 'wpvivid-backup-pro/wpvivid-backup-pro.php')
                        {
                            is_wpvivid_backup = true;
                        }
                        select_count++;
                    } );

                    if(is_wpvivid_backup === true && select_count < 2)
                    {
                        return;
                    }
                    else
                    {
                        wpvivid_update_plugins_test();
                    }
                });

                $('tr.plugin-update-tr ').on('click', 'a', function(event)
                {
                    if($(this).hasClass('update-link'))
                    {
                        wpvivid_update_plugins_test();
                    }
                });

                function wpvivid_update_plugins_test()
                {
                    if(wpvivid_backup_lock)
                    {
                        return;
                    }
                    wpvivid_updater.ajaxLocked=true;
                    wpvivid_backup_lock=true;
                    $( "#wpvivid_dialog_modal" ).dialog('open');
                    wpvivid_start_auto_backup();
                }

                var m_need_update=true;
                var task_retry_times=0;
                var wpvivid_prepare_backup=false;
                var running_backup_taskid='';
                var auto_backup_retry_times=0;

                function wpvivid_rollback_resume_backup(backup_id, next_resume_time)
                {
                    if(next_resume_time < 0){
                        next_resume_time = 0;
                    }
                    next_resume_time = next_resume_time * 1000;
                    setTimeout("wpvivid_rollback_cron_task()", next_resume_time);
                    setTimeout(function(){
                        task_retry_times = 0;
                        m_need_update=true;
                    }, next_resume_time);
                }

                function wpvivid_manage_task_ex()
                {
                    if(m_need_update === true)
                    {
                        m_need_update = false;
                        wpvivid_check_runningtask_ex();
                    }
                    else{
                        setTimeout(function()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);
                    }
                }

                function wpvivid_check_runningtask_ex()
                {
                    var ajax_data = {
                        'action': 'wpvivid_auto_new_backup_list_tasks',
                        'task_id': running_backup_taskid
                    };
                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        setTimeout(function ()
                        {
                            wpvivid_manage_task_ex();
                        }, 3000);

                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);

                            var b_has_data = false;
                            if (jsonarray.backup.data.length !== 0)
                            {
                                b_has_data = true;
                                task_retry_times = 0;
                                if (jsonarray.backup.result === 'success')
                                {
                                    wpvivid_prepare_backup = false;
                                    jQuery.each(jsonarray.backup.data, function (index, value)
                                    {
                                        if (value.status.str === 'ready')
                                        {
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'running')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'wait_resume')
                                        {
                                            running_backup_taskid = index;
                                            if (value.data.next_resume_time !== 'get next resume time failed.')
                                            {
                                                wpvivid_rollback_resume_backup(index, value.data.next_resume_time);
                                            }
                                            else
                                            {
                                                wpvivid_delete_backup_task(index);
                                                finish_wpvivid_auto_backup();
                                            }
                                        }
                                        else if (value.status.str === 'no_responds')
                                        {
                                            running_backup_taskid = index;
                                            m_need_update = true;
                                        }
                                        else if (value.status.str === 'completed')
                                        {
                                            m_need_update = false;
                                            finish_wpvivid_auto_backup();
                                        }
                                        else if (value.status.str === 'error')
                                        {
                                            running_backup_taskid = '';
                                            m_need_update = true;
                                            finish_wpvivid_auto_backup();
                                        }

                                        jQuery('#wpvivid_backup_progress_text').html(value.progress_text);
                                        jQuery('#wpvivid_backup_progress_text2').html(value.progress_text2);
                                    });
                                }
                            }

                            if (!b_has_data)
                            {
                                task_retry_times++;
                                if (task_retry_times < 5)
                                {
                                    m_need_update = true;
                                }
                            }
                        }
                        catch(err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        setTimeout(function ()
                        {
                            m_need_update = true;
                            wpvivid_manage_task_ex();
                        }, 3000);
                    });
                }

                function wpvivid_start_auto_backup()
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup',
                        'backup':'db',
                        'backup_to_remote':0
                    };

                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            running_backup_taskid=jsonarray.task_id;
                            wpvivid_start_auto_backup_now(running_backup_taskid);
                            wpvivid_manage_task_ex();
                        }
                        else
                        {
                            auto_backup_retry_times++;
                            if(auto_backup_retry_times>3)
                            {
                                if(typeof jsonarray.error !== 'undefined')
                                {
                                    alert(jsonarray.error);
                                }
                                else
                                {
                                    alert('Backup failed');
                                }
                                location.href='<?php echo apply_filters('wpvivid_get_admin_url', '').'plugins.php'; ?>';
                            }
                            else
                            {
                                wpvivid_start_auto_backup();
                            }
                        }
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        auto_backup_retry_times++;
                        if(auto_backup_retry_times>3)
                        {
                            alert('Backup failed');
                        }
                        else
                        {
                            wpvivid_start_auto_backup();
                        }
                    });
                }

                function wpvivid_start_auto_backup_now(task_id)
                {
                    var ajax_data = {
                        'action': 'wpvivid_start_new_auto_backup_now',
                        'task_id':task_id
                    };
                    wpvivid_post_request_addon(ajax_data, function(data)
                    {
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                    });
                }

                function finish_wpvivid_auto_backup()
                {
                    wpvivid_updater.ajaxLocked=false;
                    wpvivid_updater.queueChecker();
                    setTimeout(function ()
                    {
                        $("#wpvivid_dialog_modal").dialog("close");
                    }, 1000);
                }

            });


        </script>
        <?php
    }

    public function export_dashboard_info($json)
    {
        $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());
        $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
        $init=get_option('wpvivid_init_rollback_setting',false);
        $rollback_plugin_data=get_option("wpvivid_rollback_plugin_data",array());
        $rollback_theme_data=get_option("wpvivid_rollback_theme_data",array());
        $counts=get_option('wpvivid_max_rollback_count',array());
        $auto_backup_core=get_option('wpvivid_plugins_auto_backup_core',false);
        $rollback_retain_local=get_option('wpvivid_rollback_retain_local',0);
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        $remote_id = get_option('wpvivid_rollback_remote_id', 0);
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        $auto_enable_new_plugin = get_option('wpvivid_auto_enable_new_plugin', false);
        $json['data']['wpvivid_plugins_auto_backup_status']   = $plugins_auto_backup_status;
        $json['data']['wpvivid_themes_auto_backup_status']    = $themes_auto_backup_status;
        $json['data']['wpvivid_init_rollback_setting']        = $init;
        $json['data']['wpvivid_rollback_plugin_data']         = $rollback_plugin_data;
        $json['data']['wpvivid_rollback_theme_data']          = $rollback_theme_data;
        $json['data']['wpvivid_max_rollback_count']           = $counts;
        $json['data']['wpvivid_plugins_auto_backup_core']     = $auto_backup_core;
        $json['data']['wpvivid_rollback_retain_local']        = $rollback_retain_local;
        $json['data']['wpvivid_rollback_remote']              = $rollback_remote;
        $json['data']['wpvivid_rollback_remote_id']           = $remote_id;
        $json['data']['wpvivid_auto_backup_db_before_update'] = $auto_backup_db_before_update;
        $json['data']['wpvivid_auto_enable_new_plugin']       = $auto_enable_new_plugin;
        return $json;
    }

    public function set_mail_subject($backup_status)
    {
        if($backup_status!=='error')
        {
            $status='Succeeded';
        }
        else
        {
            $status='Failed';
        }

        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title'])){
            if($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title']){
                $wpvivid_use_mail_title = true;
            }
            else{
                $wpvivid_use_mail_title = false;
            }
        }
        else{
            $wpvivid_use_mail_title = true;
        }
        if($wpvivid_use_mail_title){
            global $wpvivid_backup_pro;
            $default_mail_title = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
            $mail_title = isset($general_setting['options']['wpvivid_email_setting_addon']['mail_title']) ? $general_setting['options']['wpvivid_email_setting_addon']['mail_title'] : $default_mail_title;
            $mail_title .= ': ';
        }
        else{
            $mail_title = '';
        }

        $offset=get_option('gmt_offset');
        $localtime=gmdate('m-d-Y H:i:s', time()+$offset*60*60);
        $subject='['.$mail_title.'Backup '.$status.']'.$localtime.sprintf(' - By %s', apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin'));
        return $subject;
    }

    public function set_plugin_theme_mail_subject()
    {
        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title'])){
            if($general_setting['options']['wpvivid_email_setting_addon']['use_mail_title']){
                $wpvivid_use_mail_title = true;
            }
            else{
                $wpvivid_use_mail_title = false;
            }
        }
        else{
            $wpvivid_use_mail_title = true;
        }
        if($wpvivid_use_mail_title){
            global $wpvivid_backup_pro;
            $default_mail_title = $wpvivid_backup_pro->func->swtich_domain_to_folder_name(home_url());
            $mail_title = isset($general_setting['options']['wpvivid_email_setting_addon']['mail_title']) ? $general_setting['options']['wpvivid_email_setting_addon']['mail_title'] : $default_mail_title;
        }
        else{
            $mail_title = '';
        }

        $offset=get_option('gmt_offset');
        $localtime=gmdate('m-d-Y H:i:s', time()+$offset*60*60);
        $subject='['.$mail_title.']'.$localtime.sprintf(' - By %s', apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin'));
        return $subject;
    }

    public function set_mail_body($backup_status, $backup_content, $backup_slug, $backup_path)
    {
        if($backup_status!=='error')
        {
            $status='Succeeded';
        }
        else
        {
            $status='Failed';
        }

        $offset=get_option('gmt_offset');
        $end_time=date("m-d-Y H:i:s",time()+$offset*60*60);

        global $wpdb;
        $home_url = home_url();
        $db_home_url = home_url();
        $home_url_sql = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name = %s", 'home' ) );
        foreach ( $home_url_sql as $home ){
            $db_home_url = untrailingslashit($home->option_value);
        }
        if($home_url === $db_home_url)
        {
            $domain = $home_url;
        }
        else
        {
            $domain = $db_home_url;
        }
        $domain = strtolower($domain);

        $backup_type = 'Rollback';

        if($backup_content === 'core')
        {
            $backup_what = 'WordPress Core';
        }
        else
        {
            $backup_what = $backup_slug;
        }

        $body='
        <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td style="padding-bottom:20px">
                <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                    <table align="center" style="border-spacing:0;color:#111111;Margin:0 auto;width:100%;max-width:600px" bgcolor="#F5F7F8">
                        <tbody>
				        <tr>
                            <td bgcolor="#F5F7F8" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                <table width="73%" style="border-spacing:0;color:#111111" bgcolor="#F5F7F8">
                                    <tbody>
			                        <tr>
                                        <td style="padding-top:20px;padding-bottom:0px;padding-left:10px;padding-right:40px;width:100%;text-align:center;font-size:32px;color:#2ea3f2;line-height:32px;font-weight:bold;">
                                            <span><img src="https://wpvivid.com/wp-content/uploads/2019/02/wpvivid-logo.png" title="WPvivid.com"></span>            
                                        </td>
                                    </tr>
                                    </tbody>
		                        </table>
                            </td>
                            <td width="100%" bgcolor="#F5F7F8" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                <table width="100%" style="border-spacing:0;color:#111111" bgcolor="#F5F7F8">
                                    <tbody>
                                    <tr>
                                        <td style="padding-top:10px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p style="Margin-top:0px;margin-bottom:0px;font-size:13px;line-height:16px"><strong><a href="https://twitter.com/wpvividcom" style="text-decoration:none;color:#111111" target="_blank">24/7 Support: <u></u>Twitter<u></u></a></strong></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:0px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p class="m_764812426175198487customerinfo" style="Margin-top:5px;margin-bottom:0px;font-size:13px;line-height:16px">Or <u></u><a href="https://wpvivid.com/contact-us">Email Us</a><u></u></p>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:center;font-size:32px;line-height:42px;font-weight:bold;">
                                                <span>WordPress Backup Report</span>            
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>            
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"> </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td width="80" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="80" style="border-spacing:0;color:#111111;border-bottom-color:#ffcca8;border-bottom-width:2px;border-bottom-style:solid">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"></p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"> </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    You receive this email because you have enabled the email notification feature in '.apply_filters('wpvivid_white_label_display', 'WPvivid plugin').'. Backup Details:
                                                </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>   
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="background-color:#f5f7f8;padding-top:0;padding-right:0;padding-left:0;padding-bottom:0">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">		
                        <table bgcolor="#ffffff" width="100%" align="center" border="0" cellspacing="0" cellpadding="0" style="color:#111111;max-width:600px">
                            <tbody>
                            <tr>
                                <td bgcolor="#ffffff" align="left" style="padding-top:10px;padding-bottom:0;padding-right:40px;padding-left:40px;background-color:#ffffff">
                                    <table border="0" cellpadding="0" cellspacing="0" align="left" width="100%">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:10px;padding-right:0;padding-bottom:0;padding-left:20px">
                                                <table border="0" cellpadding="0" cellspacing="0" align="left">
                                                    <tbody>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Domain: </label><label>'.$domain.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup: </label><label>'.$status.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Time: </label><label>'.$end_time.'</label></p>
                                                        </td>
                                                    </tr>
                                                     <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Type: </label><label>'.$backup_type.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Content: </label><label>'.$backup_what.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup To: </label><label>'.$backup_path.'</label></p>
                                                        </td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>                     
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>     
          
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">             
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#757575">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    *'.apply_filters('wpvivid_white_label_display', 'WPvivid Backup plugin').' is a Wordpress plugin that it will help you back up your site to the leading cloud storage providers like Dropbox, Google Drive, Amazon S3, Microsoft OneDrive, FTP and SFTP.
                                                </p>
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    Plugin Page: <a href="https://wordpress.org/plugins/wpvivid-backuprestore/">https://wordpress.org/plugins/wpvivid-backuprestore/</a>
                                                </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>     
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                                <tr>
                                    <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                        <table width="100%" style="border-spacing:0;color:#111111">
                                            <tbody>
                                            <tr>
                                                <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                    <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"></p>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
                <tr>
                    <td bgcolor="#F5F7F8" style="background-color:#f5f7f8;padding-top:0;padding-right:0;padding-left:0;padding-bottom:0">
                        <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                            <table width="100%" align="center" border="0" cellspacing="0" cellpadding="0" style="color:#111111">
                                <tbody>
                                <tr>
                                    <td align="center" style="padding-top:40px;padding-bottom:0;padding-right:0px;padding-left:0px">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tbody>
                                            <tr>
                                                <td align="left" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/report-background.png" width="270" height="60" style="display:block;width:100%;max-width:270px;min-width:10px;height:60px" class="CToWUd">
                                                </td>
                                                <td width="60" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/female.png" width="60" height="60" style="display:block" class="CToWUd">
                                                </td>
                                                <td align="right" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/report-background.png" width="270" height="60" style="display:block;width:100%;max-width:270px;min-width:10px;height:60px" class="CToWUd">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>  
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <table bgcolor="#FFFFFF" width="100%" align="left" border="0" cellspacing="0" cellpadding="0" style="color:#111111">
                                <tbody>
                                <tr>
                                    <td bgcolor="#FFFFFF" align="left" style="padding-top:20px;padding-bottom:40px;padding-right:40px;padding-left:40px;background-color:#ffffff">     
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
                                            <tbody>
                                            <tr>
                                                <td align="center" style="padding-top:0px;padding-bottom:10px;padding-right:0;padding-left:0;text-align:center;font-size:18px;line-height:28px;font-weight:bold;">
                                                    <span>We\'re here to help you do your thing.</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding-top:0px;padding-bottom:0px;padding-right:0;padding-left:0;text-align:center">
                                                    <p style="text-align:center;margin-top:0px;margin-bottom:0px;gdsherpa-regular;;font-size:14px;line-height:24px">
                                                        <a href="https://wpvivid.com/contact-us">Contact Us</a> or <a href="https://twitter.com/wpvividcom">Twitter</a>
                                                    </p>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>        
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tbody>
                                    <tr>
                                        <td valign="top" style="font-size:0px;line-height:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <img src="https://wpvivid.com/wp-content/uploads/2019/03/unnamed6.jpg" width="600" height="5" style="display:block;width:100%;max-width:600px;min-width:10px;height:5px">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>        
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#F5F7F8" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#f5f7f8;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px">&nbsp;</p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>   
                    </div>
                </td>
            </tr>
            </tbody>
        </table>';
        return $body;
    }

    public function set_plugin_theme_mail_body($backup_what, $backup_success, $backup_failed, $backup_path='')
    {
        $offset=get_option('gmt_offset');
        $end_time=date("m-d-Y H:i:s",time()+$offset*60*60);

        global $wpdb;
        $home_url = home_url();
        $db_home_url = home_url();
        $home_url_sql = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_name = %s", 'home' ) );
        foreach ( $home_url_sql as $home ){
            $db_home_url = untrailingslashit($home->option_value);
        }
        if($home_url === $db_home_url)
        {
            $domain = $home_url;
        }
        else
        {
            $domain = $db_home_url;
        }
        $domain = strtolower($domain);

        $backup_type = 'Rollback';

        $backup_success_html='';
        $backup_failed_html='';
        if($backup_what === 'plugin')
        {
            $backup_plugin_count=0;
            if(!empty($backup_success))
            {
                $backup_success_content='';
                foreach ($backup_success as $content_name)
                {
                    if($backup_success_content==='')
                    {
                        $backup_success_content.=$content_name;
                    }
                    else
                    {
                        $backup_success_content.=', '.$content_name;
                    }
                    $backup_plugin_count++;
                }
                $backup_success_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Succeeded: </label><label>'.$backup_success_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if(!empty($backup_failed))
            {
                $backup_failed_content='';
                foreach ($backup_failed as $content_name)
                {
                    if($backup_failed_content==='')
                    {
                        $backup_failed_content.=$content_name;
                    }
                    else
                    {
                        $backup_failed_content.=', '.$content_name;
                    }
                    $backup_plugin_count++;
                }
                $backup_failed_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup <strong>Failed:</strong> </label><label>'.$backup_failed_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if($backup_plugin_count > 1)
            {
                $backup_content='Plugins';
            }
            else
            {
                $backup_content='Plugin';
            }
        }

        if($backup_what === 'theme')
        {
            $backup_theme_count=0;
            if(!empty($backup_success))
            {
                $backup_success_content='';
                foreach ($backup_success as $content_name)
                {
                    if($backup_success_content==='')
                    {
                        $backup_success_content.=$content_name;
                    }
                    else
                    {
                        $backup_success_content.=', '.$content_name;
                    }
                    $backup_theme_count++;
                }
                $backup_success_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Succeeded: </label><label>'.$backup_success_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if(!empty($backup_failed))
            {
                $backup_failed_content='';
                foreach ($backup_failed as $content_name)
                {
                    if($backup_failed_content==='')
                    {
                        $backup_failed_content.=$content_name;
                    }
                    else
                    {
                        $backup_failed_content.=', '.$content_name;
                    }
                    $backup_theme_count++;
                }
                $backup_failed_html='<tr>
                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup <strong>Failed:</strong> </label><label>'.$backup_failed_content.'</label></p>
                                        </td>
                                      </tr>';
            }

            if($backup_theme_count > 1)
            {
                $backup_content='Themes';
            }
            else
            {
                $backup_content='Theme';
            }
        }

        if(!empty($backup_path))
        {
            $backup_path='<tr>
                            <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup To: </label><label>'.$backup_path.'</label></p>
                            </td>
                          </tr>';
        }

        $body='
        <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td style="padding-bottom:20px">
                <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                    <table align="center" style="border-spacing:0;color:#111111;Margin:0 auto;width:100%;max-width:600px" bgcolor="#F5F7F8">
                        <tbody>
				        <tr>
                            <td bgcolor="#F5F7F8" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                <table width="73%" style="border-spacing:0;color:#111111" bgcolor="#F5F7F8">
                                    <tbody>
			                        <tr>
                                        <td style="padding-top:20px;padding-bottom:0px;padding-left:10px;padding-right:40px;width:100%;text-align:center;font-size:32px;color:#2ea3f2;line-height:32px;font-weight:bold;">
                                            <span><img src="https://wpvivid.com/wp-content/uploads/2019/02/wpvivid-logo.png" title="WPvivid.com"></span>            
                                        </td>
                                    </tr>
                                    </tbody>
		                        </table>
                            </td>
                            <td width="100%" bgcolor="#F5F7F8" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                <table width="100%" style="border-spacing:0;color:#111111" bgcolor="#F5F7F8">
                                    <tbody>
                                    <tr>
                                        <td style="padding-top:10px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p style="Margin-top:0px;margin-bottom:0px;font-size:13px;line-height:16px"><strong><a href="https://twitter.com/wpvividcom" style="text-decoration:none;color:#111111" target="_blank">24/7 Support: <u></u>Twitter<u></u></a></strong></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:0px;padding-bottom:0px;padding-left:10px;padding-right:0px;background-color:#f5f7f8;width:100%;text-align:right">
                                            <p class="m_764812426175198487customerinfo" style="Margin-top:5px;margin-bottom:0px;font-size:13px;line-height:16px">Or <u></u><a href="https://wpvivid.com/contact-us">Email Us</a><u></u></p>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:center;font-size:32px;line-height:42px;font-weight:bold;">
                                                <span>WordPress Backup Report</span>            
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>            
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"> </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td width="80" style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="80" style="border-spacing:0;color:#111111;border-bottom-color:#ffcca8;border-bottom-width:2px;border-bottom-style:solid">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"></p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:0px;padding-right:0px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"> </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    You receive this email because you have enabled the email notification feature in '.apply_filters('wpvivid_white_label_display', 'WPvivid plugin').'. Backup Details:
                                                </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>   
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="background-color:#f5f7f8;padding-top:0;padding-right:0;padding-left:0;padding-bottom:0">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">		
                        <table bgcolor="#ffffff" width="100%" align="center" border="0" cellspacing="0" cellpadding="0" style="color:#111111;max-width:600px">
                            <tbody>
                            <tr>
                                <td bgcolor="#ffffff" align="left" style="padding-top:10px;padding-bottom:0;padding-right:40px;padding-left:40px;background-color:#ffffff">
                                    <table border="0" cellpadding="0" cellspacing="0" align="left" width="100%">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:10px;padding-right:0;padding-bottom:0;padding-left:20px">
                                                <table border="0" cellpadding="0" cellspacing="0" align="left">
                                                    <tbody>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Domain: </label><label>'.$domain.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Content: </label><label>'.$backup_content.'</label></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Time: </label><label>'.$end_time.'</label></p>
                                                        </td>
                                                    </tr>
                                                     <tr>
                                                        <td valign="top" align="left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                                            <p style="text-align:left;Margin-top:0px;Margin-bottom:0px;gdsherpa-regular;font-size:14px;line-height:24px"><label>Backup Type: </label><label>'.$backup_type.'</label></p>
                                                        </td>
                                                    </tr>
                                                    '.$backup_success_html.'
                                                    '.$backup_failed_html.'
                                                    '.$backup_path.'
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>                     
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>     
          
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">             
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#757575">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:20px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    *'.apply_filters('wpvivid_white_label_display', 'WPvivid Backup plugin').' is a Wordpress plugin that it will help you back up your site to the leading cloud storage providers like Dropbox, Google Drive, Amazon S3, Microsoft OneDrive, FTP and SFTP.
                                                </p>
                                                <p style="gdsherpa-regular;margin-top:0px;font-size:14px;line-height:24px;margin-bottom:0px">
                                                    Plugin Page: <a href="https://wordpress.org/plugins/wpvivid-backuprestore/">https://wordpress.org/plugins/wpvivid-backuprestore/</a>
                                                </p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>     
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#FFFFFF" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                                <tr>
                                    <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                        <table width="100%" style="border-spacing:0;color:#111111">
                                            <tbody>
                                            <tr>
                                                <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#ffffff;width:100%;text-align:left">
                                                    <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px"></p>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
                <tr>
                    <td bgcolor="#F5F7F8" style="background-color:#f5f7f8;padding-top:0;padding-right:0;padding-left:0;padding-bottom:0">
                        <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                            <table width="100%" align="center" border="0" cellspacing="0" cellpadding="0" style="color:#111111">
                                <tbody>
                                <tr>
                                    <td align="center" style="padding-top:40px;padding-bottom:0;padding-right:0px;padding-left:0px">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tbody>
                                            <tr>
                                                <td align="left" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/report-background.png" width="270" height="60" style="display:block;width:100%;max-width:270px;min-width:10px;height:60px" class="CToWUd">
                                                </td>
                                                <td width="60" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/female.png" width="60" height="60" style="display:block" class="CToWUd">
                                                </td>
                                                <td align="right" valign="bottom">
                                                    <img src="https://wpvivid.com/wp-content/uploads/2019/03/report-background.png" width="270" height="60" style="display:block;width:100%;max-width:270px;min-width:10px;height:60px" class="CToWUd">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>  
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <table bgcolor="#FFFFFF" width="100%" align="left" border="0" cellspacing="0" cellpadding="0" style="color:#111111">
                                <tbody>
                                <tr>
                                    <td bgcolor="#FFFFFF" align="left" style="padding-top:20px;padding-bottom:40px;padding-right:40px;padding-left:40px;background-color:#ffffff">     
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
                                            <tbody>
                                            <tr>
                                                <td align="center" style="padding-top:0px;padding-bottom:10px;padding-right:0;padding-left:0;text-align:center;font-size:18px;line-height:28px;font-weight:bold;">
                                                    <span>We\'re here to help you do your thing.</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding-top:0px;padding-bottom:0px;padding-right:0;padding-left:0;text-align:center">
                                                    <p style="text-align:center;margin-top:0px;margin-bottom:0px;gdsherpa-regular;;font-size:14px;line-height:24px">
                                                        <a href="https://wpvivid.com/contact-us">Contact Us</a> or <a href="https://twitter.com/wpvividcom">Twitter</a>
                                                    </p>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>        
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tbody>
                                    <tr>
                                        <td valign="top" style="font-size:0px;line-height:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
                                            <img src="https://wpvivid.com/wp-content/uploads/2019/03/unnamed6.jpg" width="600" height="5" style="display:block;width:100%;max-width:600px;min-width:10px;height:5px">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>        
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#F5F7F8">
            <tbody>
            <tr>
                <td bgcolor="#F5F7F8" style="padding-top:0px;padding-bottom:0px">
                    <div style="max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;padding-left:20px;padding-right:20px">
                        <table bgcolor="#F5F7F8" align="center" style="border-spacing:0;color:#111111;margin:0 auto;width:100%;max-width:600px">
                            <tbody>
                            <tr>
                                <td style="padding-top:0;padding-bottom:0;padding-right:0;padding-left:0">
                                    <table width="100%" style="border-spacing:0;color:#111111">
                                        <tbody>
                                        <tr>
                                            <td style="padding-top:40px;padding-bottom:0px;padding-left:40px;padding-right:40px;background-color:#f5f7f8;width:100%;text-align:left">
                                                <p style="margin-top:0px;line-height:0px;margin-bottom:0px;font-size:4px">&nbsp;</p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>   
                    </div>
                </td>
            </tr>
            </tbody>
        </table>';
        return $body;
    }

    public function init_rollback()
    {
        $init=get_option('wpvivid_init_rollback_setting',false);
        if(!$init)
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $all_plugins     = get_plugins();

            $plugins_auto_backup_status=array();

            foreach ((array) $all_plugins as $plugin_file => $plugin_data)
            {
                if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
                {
                    continue;
                }

                $slug=$this->get_plugin_slug($plugin_file);

                if(is_plugin_active($plugin_file))
                {
                    $plugins_auto_backup_status[ $slug ]['enable_auto_backup']= true;
                }
            }

            update_option('wpvivid_plugins_auto_backup_status',$plugins_auto_backup_status,'no');

            $themes =wp_get_themes();

            $themes_auto_backup_status=array();

            foreach ($themes as $key=>$theme)
            {
                if ( get_stylesheet() === $key)
                {
                    $themes_auto_backup_status[$key]=true;
                }
            }

            update_option('wpvivid_themes_auto_backup_status',$themes_auto_backup_status,'no');
            update_option('wpvivid_init_rollback_setting',true,'no');
        }
    }

    public function check_plugins_versions()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $all_plugins     = get_plugins();
        $counts=get_option('wpvivid_max_rollback_count',array());
        $rollback_retain_local=get_option('wpvivid_rollback_retain_local',0);
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/plugins/';
        $max_plugins_count=isset($counts['max_plugins_count'])?$counts['max_plugins_count']:5;
        $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
        foreach ((array) $all_plugins as $plugin_file => $plugin_data)
        {
            $plugin['slug']=$this->get_plugin_slug($plugin_file);
            $plugin['rollback']=$this->get_rollback_data($plugin_file);
            if(isset($rollback_plugin_data[$plugin['slug']]))
                $plugin['rollback_data']=$rollback_plugin_data[$plugin['slug']];
            else
                $plugin['rollback_data']=array();

            $rollback_version=array();
            if(!empty($plugin['rollback']))
            {
                foreach ($plugin['rollback'] as $version=>$file)
                {
                    $rollback_version[$version]=$version;
                }
            }

            if(!empty($plugin['rollback_data']))
            {
                foreach ($plugin['rollback_data']['version'] as $version=>$data)
                {
                    if($data['upload'])
                    {
                        $rollback_version[$version]=$version;
                        if(!$rollback_retain_local)
                        {
                            if(file_exists($path.$plugin['slug'].'/'.$version.'/'.$plugin['slug'].'.zip'))
                            {
                                @unlink($path.$plugin['slug'].'/'.$version.'/'.$plugin['slug'].'.zip');
                            }
                        }
                    }

                }
            }

            if(!empty($rollback_version))
            {
                if(sizeof($rollback_version)>$max_plugins_count)
                {
                    $this->delete_old_plugins_rollback($plugin,$max_plugins_count);
                }
            }
        }
    }

    public function delete_old_plugins_rollback($plugin,$max_plugins_count)
    {
        $slug=$plugin['slug'];
        $rollback_data=$plugin['rollback'];

        $rollback_version=array();
        if(!empty($rollback_data))
        {
            foreach ($rollback_data as $version=>$file)
            {
                $rollback_version[$version]=$version;
            }
        }

        if(!empty($plugin['rollback_data']))
        {
            foreach ($plugin['rollback_data']['version'] as $version=>$data)
            {
                if($data['upload'])
                    $rollback_version[$version]=$version;
            }
        }

        uksort($rollback_version, function ($a, $b)
        {
            if($a==$b)
                return 0;

            if (version_compare($a,$b,'>'))
                return 1;
            else
                return -1;
        });

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/plugins/'.$slug ;

        $size=sizeof($rollback_version);
        while($size>$max_plugins_count)
        {
            foreach ($rollback_version as $version)
            {
                if(file_exists($path.'/'.$version.'/'.$slug.'.zip'))
                {
                    @unlink($path.'/'.$version.'/'.$slug.'.zip');
                    @rmdir($path.'/'.$version);
                }

                if(!empty($plugin['rollback_data']))
                {
                    if(isset($plugin['rollback_data']['version'][$version]))
                    {
                        $data=$plugin['rollback_data']['version'][$version];
                        if($data['upload'])
                        {
                            $this->cleanup_remote_rollback("plugins",$slug,$version);
                        }
                        $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
                        unset($rollback_plugin_data[$slug]['version'][$version]);
                        update_option('wpvivid_rollback_plugin_data',$rollback_plugin_data,'no');
                    }
                }

                unset($rollback_version[$version]);
                break;
            }
            $size=sizeof($rollback_version);
        }
    }

    public function cleanup_remote_rollback($type,$slug,$version)
    {
        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='success';
                return $ret;
            }
        }
        else
        {
            $ret['result']='success';
            return $ret;
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $result=$remote->cleanup_rollback($type,$slug,$version);
        if($result['result']=='success')
        {
            $ret['result']='success';
            return $ret;
        }
        else
        {
            return $result;
        }
    }

    public function check_themes_versions()
    {
        $themes =wp_get_themes();

        $counts=get_option('wpvivid_max_rollback_count',array());
        $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());
        $max_themes_count=isset($counts['max_themes_count'])?$counts['max_themes_count']:5;

        foreach ($themes as $key=>$theme)
        {
            $theme_data["slug"]=$key;
            $theme_data['rollback']=$this->get_rollback_data($key,'themes');
            if(isset($rollback_theme_data[$key]))
                $theme_data['rollback_data']=$rollback_theme_data[$key];
            else
                $theme_data['rollback_data']=array();

            $rollback_version=array();
            if(!empty($theme_data['rollback']))
            {
                foreach ($theme_data['rollback'] as $version=>$file)
                {
                    $rollback_version[$version]=$version;
                }
            }

            if(!empty($theme_data['rollback_data']))
            {
                foreach ($theme_data['rollback_data']['version'] as $version=>$data)
                {
                    if($data['upload'])
                        $rollback_version[$version]=$version;
                }
            }

            if(!empty($rollback_version))
            {
                if(sizeof($rollback_version)>$max_themes_count)
                {
                    $this->delete_old_theme_rollback($theme_data,$max_themes_count);
                }
            }
        }
    }

    public function delete_old_theme_rollback($theme_data,$max_themes_count)
    {
        $slug=$theme_data['slug'];
        $rollback_data=$theme_data['rollback'];

        $rollback_version=array();
        if(!empty($rollback_data))
        {
            foreach ($rollback_data as $version=>$file)
            {
                $rollback_version[$version]=$version;
            }
        }

        if(!empty($theme_data['rollback_data']))
        {
            foreach ($theme_data['rollback_data']['version'] as $version=>$data)
            {
                if($data['upload'])
                    $rollback_version[$version]=$version;
            }
        }

        uksort($rollback_version, function ($a, $b)
        {
            if($a==$b)
                return 0;

            if (version_compare($a,$b,'>'))
                return 1;
            else
                return -1;
        });

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/themes/'.$slug ;

        $size=sizeof($rollback_version);
        while($size>$max_themes_count)
        {
            foreach ($rollback_version as $version)
            {
                if(file_exists($path.'/'.$version.'/'.$slug.'.zip'))
                {
                    @unlink($path.'/'.$version.'/'.$slug.'.zip');
                    @rmdir($path.'/'.$version);
                }

                if(!empty($theme_data['rollback_data']))
                {
                    if(isset($theme_data['rollback_data']['version'][$version]))
                    {
                        $data=$theme_data['rollback_data']['version'][$version];
                        if($data['upload'])
                        {
                            $this->cleanup_remote_rollback("themes",$slug,$version);
                        }
                        $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());
                        unset($rollback_theme_data[$slug]['version'][$version]);
                        update_option('wpvivid_rollback_theme_data',$rollback_theme_data,'no');
                    }
                }

                unset($rollback_version[$version]);
                break;
            }
            $size=sizeof($rollback_version);
        }
    }

    public function check_core_versions()
    {
        $core_list=$this->get_core_data();

        $counts=get_option('wpvivid_max_rollback_count',array());
        $max_core_count=isset($counts['max_core_count'])?$counts['max_core_count']:5;
        if(!empty($core_list))
        {
            if(sizeof($core_list)>$max_core_count)
            {
                $this->delete_old_core_rollback($core_list,$max_core_count);
            }
        }
    }

    public function delete_old_core_rollback($core_list,$max_core_count)
    {
        uksort($core_list, function ($a, $b)
        {
            if($a==$b)
                return 0;

            if (version_compare($a,$b,'>'))
                return 1;
            else
                return -1;
        });

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core/' ;

        $size=sizeof($core_list);
        while($size>$max_core_count)
        {
            foreach ($core_list as $version=>$data)
            {
                if(file_exists($path.'/'.$version.'/wordpress.zip'))
                {
                    @unlink($path.'/'.$version.'/wordpress.zip');
                    @rmdir($path.'/'.$version);
                }
                unset($core_list[$version]);
                break;
            }
            $size=sizeof($core_list);
        }
    }

    public function auto_backup_page()
    {
        if(isset($_REQUEST['auto_backup'])&&$_REQUEST['auto_backup']==1)
        {
            if(isset($_REQUEST['backup']))
            {
                $backup=$_REQUEST['backup'];
            }

            if(empty($backup))
            {
                echo 'Failed to retrieve the content for the backup, please try again.';
                die();
            }

            if($backup=='core')
            {
                $this->output_core_form();
            }
            else if($backup=='plugins')
            {
                $this->output_plugins_form();
            }
            else if($backup=='themes')
            {
                $this->output_themes_form();
            }
            $this->show_auto_backup_page($backup);
        }
    }

    public function show_auto_backup_page($backup)
    {
        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);

        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Rollback', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <h2>The update will start after the backup core files is finished</h2>
                                    <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" id="wpvivid_postbox_backup_percent">
                                        <p>
                                            <span class="wpvivid-span-progress">
                                                <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: 0%" >0% completed</span>
                                            </span>
                                        </p>
                                        <p>
                                            <span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span>
                                            <span>
                                            <span>Backing up...</span>
                                        </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            var wpvivid_siteurl = '<?php
                $wpvivid_siteurl = array();
                $wpvivid_siteurl=self::wpvivid_rollback_get_siteurl();
                echo esc_url($wpvivid_siteurl['site_url']);
                ?>';

            function wpvivid_rollback_cron_task()
            {
                jQuery.get(wpvivid_siteurl+'/wp-cron.php');
            }

            var m_need_update=true;
            var task_retry_times=0;
            var wpvivid_prepare_backup=false;
            var running_backup_taskid='';
            var auto_backup_retry_times=0;

            function wpvivid_rollback_resume_backup(backup_id, next_resume_time)
            {
                if(next_resume_time < 0){
                    next_resume_time = 0;
                }
                next_resume_time = next_resume_time * 1000;
                setTimeout("wpvivid_rollback_cron_task()", next_resume_time);
                setTimeout(function(){
                    task_retry_times = 0;
                    m_need_update=true;
                }, next_resume_time);
            }

            function wpvivid_manage_task_ex()
            {
                if(m_need_update === true)
                {
                    m_need_update = false;
                    wpvivid_check_runningtask_ex();
                }
                else{
                    setTimeout(function()
                    {
                        wpvivid_manage_task_ex();
                    }, 3000);
                }
            }

            function wpvivid_check_runningtask_ex()
            {
                var ajax_data = {
                    'action': 'wpvivid_auto_new_backup_list_tasks',
                    'task_id': running_backup_taskid
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    setTimeout(function ()
                    {
                        wpvivid_manage_task_ex();
                    }, 3000);

                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        var b_has_data = false;
                        if (jsonarray.backup.data.length !== 0)
                        {
                            b_has_data = true;
                            task_retry_times = 0;
                            if (jsonarray.backup.result === 'success')
                            {
                                wpvivid_prepare_backup = false;
                                jQuery.each(jsonarray.backup.data, function (index, value)
                                {
                                    if (value.status.str === 'ready')
                                    {
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = true;
                                    }
                                    else if (value.status.str === 'running')
                                    {
                                        running_backup_taskid = index;
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = true;
                                    }
                                    else if (value.status.str === 'wait_resume')
                                    {
                                        running_backup_taskid = index;
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        if (value.data.next_resume_time !== 'get next resume time failed.')
                                        {
                                            wpvivid_rollback_resume_backup(index, value.data.next_resume_time);
                                        }
                                        else
                                        {
                                            wpvivid_delete_backup_task(index);
                                        }
                                    }
                                    else if (value.status.str === 'no_responds')
                                    {
                                        running_backup_taskid = index;
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = true;
                                    }
                                    else if (value.status.str === 'completed')
                                    {
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        m_need_update = false;
                                        finish_wpvivid_auto_backup();
                                    }
                                    else if (value.status.str === 'error')
                                    {
                                        jQuery('#wpvivid_postbox_backup_percent').html(value.progress_html);
                                        running_backup_taskid = '';
                                        m_need_update = true;
                                        location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-dashboard', 'wpvivid-dashboard'); ?>';
                                    }
                                });
                            }
                        }

                        if (!b_has_data)
                        {
                            task_retry_times++;
                            if (task_retry_times < 5)
                            {
                                m_need_update = true;
                            }
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function ()
                    {
                        m_need_update = true;
                        wpvivid_manage_task_ex();
                    }, 3000);
                });
            }

            function wpvivid_start_auto_backup()
            {
                var ajax_data = {
                    'action': 'wpvivid_start_new_auto_backup',
                    'backup':'db',
                    'backup_to_remote':'0'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);

                    if (jsonarray.result === 'success')
                    {
                        running_backup_taskid=jsonarray.task_id;
                        wpvivid_start_auto_backup_now(running_backup_taskid);
                        wpvivid_manage_task_ex();
                    }
                    else
                    {
                        auto_backup_retry_times++;
                        if(auto_backup_retry_times>3)
                        {
                            if(typeof jsonarray.error !== 'undefined')
                            {
                                alert(jsonarray.error);
                            }
                            else
                            {
                                alert('Backup failed');
                            }
                            location.href='<?php echo apply_filters('wpvivid_get_admin_url', '').'plugins.php'; ?>';
                        }
                        else
                        {
                            wpvivid_start_auto_backup();
                        }
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    auto_backup_retry_times++;
                    if(auto_backup_retry_times>3)
                    {
                        alert('Backup failed');
                    }
                    else
                    {
                        wpvivid_start_auto_backup();
                    }
                });
            }

            function wpvivid_start_auto_backup_now(task_id)
            {
                var ajax_data = {
                    'action': 'wpvivid_start_new_auto_backup_now',
                    'task_id':task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            function finish_wpvivid_auto_backup()
            {
                <?php
                if($backup=='core')
                {
                ?>
                wpvivid_start_core_backup();
                <?php
                }
                else
                {
                ?>
                jQuery('#upgrade').click();
                <?php
                }
                ?>
            }

            jQuery(document).ready(function ()
            {
                <?php
                if($auto_backup_db_before_update)
                {
                ?>
                wpvivid_start_auto_backup();
                <?php
                }
                else
                {
                ?>
                wpvivid_start_core_backup();
                <?php
                }
                ?>

            });

            function wpvivid_start_core_backup()
            {
                var ajax_data = {
                    'action': 'wpvivid_start_core_backup'
                };

                wpvivid_simulate_backup_core_progress();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    finish_wpvivid_core_auto_backup();
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    finish_wpvivid_core_auto_backup();
                });
            }

            var wpvivid_b_backup_core_finished=false;
            function wpvivid_simulate_backup_core_progress()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=500;

                var timer = setInterval(function ()
                {
                    currentProgess += steps;
                    if(wpvivid_b_backup_core_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Backup WordPress core completed Successful</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Backing up WordPress core...</span></span></p>';
                    }

                    jQuery("#wpvivid_postbox_backup_percent").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function finish_wpvivid_core_auto_backup()
            {
                jQuery('#upgrade').click();
            }


        </script>
        <?php
    }

    public function output_core_form()
    {
        $updates    = get_core_updates();
        foreach ( (array) $updates as $update )
        {
            $submit        = __( 'Update Now' );
            $current = false;
            if ( ! isset( $update->response ) || 'latest' == $update->response ) {
                $current = true;
            }
            if ( $current )
            {
                $form_action = 'update-core.php?action=do-core-reinstall';
            }
            else
            {
                $form_action   = 'update-core.php?action=do-core-upgrade';
            }

            //action=do-core-reinstall
            echo '<li style="display: none">';
            echo '<form method="post" action="' . $form_action . '" name="upgrade" class="upgrade">';
            wp_nonce_field( 'upgrade-core' );
            $name        = esc_attr( '_wpnonce' );
            echo '<input type="hidden" id="'.$name.'" name="'.$name.'" value="' . wp_create_nonce( 'upgrade-core' ) . '" />';
            $url=apply_filters('wpvivid_get_admin_url', '').'update-core.php';
            echo '<input type="hidden" name="_wp_http_referer" value="' . esc_attr( wp_unslash( $url ) ) . '" />';
            echo '<p>';
            echo '<input name="version" value="' . esc_attr( $update->current ) . '" />';
            echo '<input name="locale" value="' . esc_attr( $update->locale ) . '" />';
            submit_button( $submit, '', 'upgrade', false );
            echo '</p>';
            echo '</form>';
            echo '</li>';
        }
    }

    public function output_plugins_form()
    {
        if(isset($_REQUEST['slug']))
        {
            $plugin_list[]=$_REQUEST['slug'];
        }
        else
        {
            $plugin_list=$_POST['checked'];
        }
        $form_action = 'update-core.php?action=do-plugin-upgrade';
        ?>
        <form method="post" action="<?php echo esc_url( $form_action ); ?>" name="upgrade-plugins" class="upgrade" style="display: none">
            <?php wp_nonce_field( 'upgrade-core' ); ?>
            <p><input id="upgrade" class="button" type="submit" value="<?php esc_attr_e( 'Update Plugins' ); ?>" name="upgrade" /></p>
            <table class="widefat updates-table" id="update-plugins-table">
                <tbody class="plugins">
                <?php
                foreach ( $plugin_list as $plugin_slug )
                {
                    // Get plugin compat for running version of WordPress.
                    ?>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" name="checked[]" value="<?php echo $plugin_slug; ?>" checked/>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </form>
        <?php
    }

    public function output_themes_form()
    {
        if(isset($_REQUEST['slug']))
        {
            $themes_list[]=$_REQUEST['slug'];
        }
        else
        {
            $themes_list=$_POST['checked'];
        }
        $form_action = 'update-core.php?action=do-theme-upgrade';
        ?>
        <form method="post" action="<?php echo esc_url( $form_action ); ?>" name="upgrade-themes" class="upgrade" style="display: none">
            <?php wp_nonce_field( 'upgrade-core' ); ?>
            <p><input id="upgrade" class="button" type="submit" value="<?php esc_attr_e( 'Update Themes' ); ?>" name="upgrade" /></p>
            <table class="widefat updates-table" id="update-thems-table">
                <tbody class="plugins">
                <?php
                foreach ( $themes_list as $themes )
                {
                    // Get plugin compat for running version of WordPress.
                    ?>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" name="checked[]" value="<?php echo $themes; ?>" checked/>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </form>
        <?php
    }

    public function start_backup_core()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        $ret=$this->backup_core();
        echo json_encode($ret);
        die();
    }

    public function backup_core()
    {
        set_time_limit(300);

        $replace_path=$this -> transfer_path(ABSPATH);
        $files=$this->get_core_files();

        if (!class_exists('WPvivid_PclZip'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';

        require ABSPATH . WPINC . '/version.php';
        global $wp_version;
        $version=$wp_version;

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/rollback/core/'.$version;
        $file_name='wordpress.zip';
        if(!file_exists($path))
        {
            @mkdir($path,0777,true);
        }

        if(file_exists($path.'/'.$file_name))
        {
            @unlink($path.'/'.$file_name);
        }

        $pclzip=new WPvivid_PclZip($path.'/'.$file_name);
        $ret = $pclzip -> add($files,WPVIVID_PCLZIP_OPT_REMOVE_PATH,$replace_path,WPVIVID_PCLZIP_OPT_NO_COMPRESSION,WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,16);
        if (!$ret)
        {
            do_action('wpvivid_do_rollback_mail_report', 'error', 'core', '', $path);
            $last_error = $pclzip->errorInfo(true);
            $backup_ret['result']='failed';
            $backup_ret['error'] = $last_error;
            return $backup_ret;
        }
        else
        {
            do_action('wpvivid_do_rollback_mail_report', 'Succeeded', 'core', '', $path);
            $backup_ret['result']='success';
            return $backup_ret;
        }
    }

    public function get_core_files()
    {
        $root_path=$this->transfer_path(ABSPATH);
        $root_path=untrailingslashit($root_path);

        $include_regex=array();
        $include_regex[]='#^'.preg_quote($this -> transfer_path(ABSPATH.'wp-admin'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(ABSPATH.'wp-includes'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(ABSPATH.'lotties'), '/').'#';
        $exclude_regex=array();
        $exclude_regex[]='#^'.preg_quote($this -> transfer_path(ABSPATH).'/'.'wp-config.php', '/').'#';
        $exclude_regex[]='#^'.preg_quote($this -> transfer_path(ABSPATH).'/'.'.htaccess', '/').'#';
        $files=array();
        $this->_get_files($root_path,$files,$exclude_regex,$include_regex);
        return $files;
    }

    public function _get_files($path,&$files,$exclude_regex,$include_regex)
    {
        $handler = opendir($path);

        if($handler===false)
            return;

        while (($filename = readdir($handler)) !== false)
        {
            if ($filename != "." && $filename != "..")
            {
                if (is_dir($path . '/' . $filename) && !@is_link($path . '/' . $filename))
                {
                    if ($this->regex_match($include_regex, $path . '/' . $filename, 1))
                    {
                        $this->_get_files($path . '/' . $filename,$files,$exclude_regex,$include_regex);
                    }
                }
                else
                {
                    if(is_readable($path . '/' . $filename) && !@is_link($path . '/' . $filename))
                    {
                        if($this->regex_match($exclude_regex, $this->transfer_path($path . '/' . $filename), 0))
                        {
                            $files[]=$this->transfer_path($path . '/' . $filename);
                        }
                    }
                }
            }
        }
        if($handler)
            @closedir($handler);

        return;
    }

    private function regex_match($regex_array,$string,$mode)
    {
        if(empty($regex_array))
        {
            return true;
        }

        if($mode==0)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return false;
                }
            }

            return true;
        }

        if($mode==1)
        {
            foreach ($regex_array as $regex)
            {
                if(preg_match($regex,$string))
                {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode('/',$values);
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-rollback';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-rollback';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_rollback');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Rollback');
            $submenu['menu_title'] = 'Rollback';

            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-rollback");
            $submenu['menu_slug'] = strtolower(sprintf('%s-rollback', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 14;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }

        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_rollback');
        if($display)
        {
            $admin_url = apply_filters('wpvivid_get_admin_url', '');
            $menu['id'] = 'wpvivid_admin_menu_backup_rollback';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Rollback';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-rollback');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid').'-rollback';

            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-rollback");

            $menu['index'] = 7;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function init_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Rollback', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p>
                                            <span class="dashicons dashicons-update-alt wpvivid-dashicons-large wpvivid-dashicons-blue"></span>
                                            <span class="wpvivid-page-title">Rollback</span>
                                        </p>
                                        <span class="about-description">Perform a return to a prior state of plugins, themes and Wordpress core.</span>
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
                                        <span class="dashicons  dashicons-editor-help wpvivid-dashicons-orange"></span>
                                        <span>
                            <strong>Please do not close or refresh the page when a rollback task is running.</strong>
                        </span>
                                    </div>
                                </div>
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $args['span_class']='dashicons dashicons-admin-plugins';
                                    $args['span_style']='color:#007cba; padding-right:0.5em;margin-top:0.2em;';
                                    $args['div_style']='padding-top:0;display:block;';
                                    $args['is_parent_tab']=0;

                                    $tabs['plugins']['title']='Plugins';
                                    $tabs['plugins']['slug']='plugins';
                                    $tabs['plugins']['callback']=array($this, 'output_plugins');
                                    $tabs['plugins']['args']=$args;

                                    $args['span_class']='dashicons dashicons-admin-appearance';
                                    $args['div_style']='';

                                    $tabs['themes']['title']='Themes';
                                    $tabs['themes']['slug']='themes';
                                    $tabs['themes']['callback']=array($this, 'output_themes');
                                    $tabs['themes']['args']=$args;

                                    $args['span_class']='dashicons dashicons-wordpress';

                                    $tabs['core']['title']='Wordpress Core';
                                    $tabs['core']['slug']='core';
                                    $tabs['core']['callback']=array($this, 'output_core');
                                    $tabs['core']['args']=$args;

                                    $args['span_class']='dashicons dashicons-admin-plugins';

                                    $tabs['settings']['title']='Settings';
                                    $tabs['settings']['slug']='settings';
                                    $tabs['settings']['callback']=array($this, 'output_settings');
                                    $tabs['settings']['args']=$args;

                                    $args['span_class']='dashicons dashicons-admin-plugins';
                                    $args['can_delete']=1;
                                    $args['hide']=1;
                                    $tabs['version_backup']['title']='Versioning Backups';
                                    $tabs['version_backup']['slug']='version_backup';
                                    $tabs['version_backup']['callback']=array($this, 'output_version_backup');
                                    $tabs['version_backup']['args']=$args;

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
                    <?php
                    $this->add_footer();
                    ?>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($)
            {
                <?php
                if(isset($_REQUEST['tabs']))
                {
                $tab=$_REQUEST['tabs'];
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ '<?php echo $tab ?>', 'plugins' ]);
                <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public function add_sidebar()
    {
        if(apply_filters('wpvivid_show_sidebar',true))
        {
            $href = 'https://docs.wpvivid.com/wpvivid-backup-pro-rollback-overview.html';

            ?>
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox  wpvivid-sidebar">
                        <h2 style="margin-top:0.5em;"><span class="dashicons dashicons-sticky wpvivid-dashicons-orange"></span>
                            <span><?php esc_attr_e(
                                    'Troubleshooting', 'WpAdminStyle'
                                ); ?></span></h2>
                        <div class="inside" style="padding-top:0;">
                            <ul class="" >
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-editor-help wpvivid-dashicons-orange" ></span>
                                    <a href="https://docs.wpvivid.com/troubleshooting"><b>Troubleshooting</b></a>
                                    <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-admin-generic wpvivid-dashicons-orange" ></span>
                                    <a href="https://docs.wpvivid.com/wpvivid-backup-pro-advanced-settings.html"><b>Adjust Advanced Settings </b></a>
                                    <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                                </li>

                            </ul>
                        </div>

                        <h2><span class="dashicons dashicons-book-alt wpvivid-dashicons-orange" ></span>
                            <span><?php esc_attr_e(
                                    'Documentation', 'WpAdminStyle'
                                ); ?></span></h2>
                        <div class="inside" style="padding-top:0;">
                            <ul class="">
                                <li style="border-top:1px solid #f1f1f1;"><span class="dashicons dashicons-update wpvivid-dashicons-grey"></span>
                                    <a href="<?php echo $href; ?>"><b><?php echo 'Rollback'; ?></b></a>
                                    <small><span style="float: right;"><a href="<?php echo esc_url(apply_filters('wpvivid_white_label_page_redirect', apply_filters('wpvivid_get_admin_url', '').'admin.php?page=wpvivid-rollback', 'wpvivid-rollback')); ?>" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>

                                </li>
                            </ul>
                        </div>

                        <?php
                        if(apply_filters('wpvivid_show_submit_ticket',true))
                        {
                            ?>
                            <h2>
                                <span class="dashicons dashicons-businesswoman wpvivid-dashicons-green"></span>
                                <span><?php esc_attr_e(
                                        'Support', 'WpAdminStyle'
                                    ); ?></span>
                            </h2>
                            <div class="inside">
                                <ul class="">
                                    <li><span class="dashicons dashicons-admin-comments wpvivid-dashicons-green"></span>
                                        <a href="https://wpvivid.com/submit-ticket"><b>Submit A Ticket</b></a>
                                        <br>
                                        The ticket system is for <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Pro users only. If you need any help with our plugin, submit a ticket and we will respond shortly.
                                    </li>
                                </ul>
                            </div>
                            <!-- .inside -->
                            <?php
                        }
                        ?>

                    </div>
                    <!-- .postbox -->

                </div>
                <!-- .meta-box-sortables -->

            </div>
            <?php
        }
    }

    public function add_footer()
    {
        if(apply_filters('wpvivid_show_sidebar',true))
        {
            $href = 'https://docs.wpvivid.com/wpvivid-backup-pro-rollback-overview.html';
            ?>
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox wpvivid-footer wpvivid-footer-two-cols">
                        <div class="wpvivid-footer-two-cols-left">
                            <strong><span class="dashicons dashicons-book-alt wpvivid-dashicons-orange" ></span>
                                <span><?php esc_attr_e(
                                        'Documentation', 'WpAdminStyle'
                                    ); ?></span></strong>
                            <div class="wpvivid-footer-two-cols-left-list">
                                <ul>
                                    <li><span class="dashicons dashicons-backup  wpvivid-dashicons-green" ></span>
                                        <a href="https://docs.wpvivid.com/manual-backup-overview.html"><b>Backup</b></a>
                                    </li>
                                    <li><span class="dashicons dashicons-migrate  wpvivid-dashicons-blue"></span>
                                        <a href="https://docs.wpvivid.com/custom-migration-overview.html"><b>Auto-Migration</b></a>
                                    </li>
                                    <li><span class="dashicons dashicons-editor-ul  wpvivid-dashicons-green"></span>
                                        <a href="https://docs.wpvivid.com/wpvivid-backup-pro-backups-restore-overview.html"><b>Backup Manager</b></a>
                                    </li>
                                    <li><span class="dashicons dashicons-calendar-alt  wpvivid-dashicons-green"></span>
                                        <a href="https://docs.wpvivid.com/wpvivid-backup-pro-schedule-overview.html"><b>Schedule</b></a>
                                    </li>
                                    <li><span class="dashicons dashicons-admin-site-alt3  wpvivid-dashicons-green"></span>
                                        <a href="https://docs.wpvivid.com/wpvivid-backup-pro-cloud-storage-overview.html"><b>Cloud Storage</b></a>
                                    </li>
                                    <li><span class="dashicons dashicons-randomize  wpvivid-dashicons-green"></span>
                                        <a href="https://docs.wpvivid.com/export-content.html"><b>Export/Import</b></a>
                                    </li>
                                    <li><span class="dashicons dashicons-code-standards  wpvivid-dashicons-green"></span>
                                        <a href="https://docs.wpvivid.com/unused-images-cleaner.html"><b>Unused Image Cleaner</b></a>
                                    </li>
                                    <li><span class="dashicons dashicons-update wpvivid-dashicons-grey" ></span>
                                        <a href="<?php echo $href; ?>"><b><?php echo 'Rollback'; ?></b></a>
                                    </li>
                                    <?php
                                    if(apply_filters('wpvivid_check_install_addon', false, 'imgoptim_pro'))
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-format-gallery  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-image-optimization-pro-overview.html"><b><?php _e('Image Bulk Optimization', 'wpvivid-imgoptim'); ?></b></a>
                                        </li>
                                        <li><span class="dashicons dashicons-update  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-image-optimization-free-lazyload-images.html"><b><?php _e('Lazy Loading', 'wpvivid-imgoptim'); ?></b></a>
                                        </li>
                                        <li><span class="dashicons dashicons-admin-site  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-image-optimization-pro-integrate-cdn.html"><b><?php _e('CDN Integration', 'wpvivid-imgoptim'); ?></b></a>
                                        </li>
                                        <li>
                                            <span class="dashicons dashicons-format-image  wpvivid-dashicons-grey"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-image-optimization-pro-convert-to-webp.html"><b><?php _e('Convert Images to WebP', 'wpvivid-imgoptim'); ?></b></a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <?php
                                    if(apply_filters('wpvivid_check_install_addon', false, 'staging_pro'))
                                    {
                                        ?>
                                        <li><span class="dashicons dashicons-welcome-view-site  wpvivid-dashicons-blue"></span>
                                            <a href="https://docs.wpvivid.com/wpvivid-backup-pro-create-staging-site.html"><b>WPvivid Staging</b></a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <div class="wpvivid-footer-two-cols-right">
                            <strong><span class="dashicons dashicons-sticky wpvivid-dashicons-orange" ></span>
                                <span><?php esc_attr_e(
                                        'Troubleshooting', 'WpAdminStyle'
                                    ); ?></span></strong>
                            <div class="wpvivid-footer-two-cols-right-list" style="margin-bottom:1rem">
                                <ul>
                                    <li><span class="dashicons dashicons-editor-help  wpvivid-dashicons-orange" ></span>
                                        <a href="https://docs.wpvivid.com/troubleshooting"><b>Troubleshooting</b></a>
                                        <br>
                                    </li>
                                    <li><span class="dashicons dashicons-admin-generic  wpvivid-dashicons-orange" ></span>
                                        <a href="https://docs.wpvivid.com/wpvivid-backup-pro-advanced-settings.html"><b>Adjust Advanced Settings </b></a>
                                        <br>
                                    </li>
                                </ul>
                            </div>

                            <?php
                            if(apply_filters('wpvivid_show_submit_ticket',true))
                            {
                                ?>
                                <strong><span class="dashicons dashicons-businesswoman wpvivid-dashicons-green"></span>
                                    <span><?php esc_attr_e(
                                            'Support', 'WpAdminStyle'
                                        ); ?></span></strong>
                                <div class="wpvivid-footer-two-cols-right-list">

                                    <ul class="">
                                        <li style="border-bottom: 0;"><span class="dashicons dashicons-admin-comments wpvivid-dashicons-green" ></span>
                                            <a href="https://wpvivid.com/submit-ticket"><b>Submit a Ticket</b></a>
                                            The ticket system is for WPvivid Pro users only. If you need any help with our plugin, submit a ticket and we will respond shortly.
                                        </li>
                                    </ul>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    public function output_plugins()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $all_plugins     = get_plugins();
        $current = get_site_transient( 'update_plugins' );
        $plugins = array();

        $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());

        $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
        foreach ((array) $all_plugins as $plugin_file => $plugin_data)
        {
            if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
            {
                continue;
            }

            if ( isset( $current->response[ $plugin_file ] ) )
            {
                $plugins[ $plugin_file ]= $plugin_data;
                $plugins[ $plugin_file ]['response']= (array)$current->response[ $plugin_file ];
            }
            else if( isset( $current->no_update[ $plugin_file ] ) )
            {
                $plugins[ $plugin_file ]= $plugin_data;
                $plugins[ $plugin_file ]['response']= (array)$current->no_update[ $plugin_file ];
            }
            else
            {
                $plugins[ $plugin_file ]= $plugin_data;
                $plugins[ $plugin_file ]['response']['new_version']='-';
            }

            $plugins[ $plugin_file ]['slug']=$this->get_plugin_slug($plugin_file);

            if(isset($plugins_auto_backup_status[$plugins[ $plugin_file ]['slug']]))
            {
                $plugins[ $plugin_file ]['enable_auto_backup']= $plugins_auto_backup_status[$plugins[ $plugin_file ]['slug']];
            }
            else
            {
                $auto_enable_new_plugin = get_option('wpvivid_auto_enable_new_plugin', false);
                if($auto_enable_new_plugin)
                {
                    $plugins[ $plugin_file ]['enable_auto_backup']= true;
                }
                else
                {
                    $plugins[ $plugin_file ]['enable_auto_backup']= false;
                }
            }

            if(isset($plugins[ $plugin_file ]['slug']))
            {
                if(isset($rollback_plugin_data[$plugins[ $plugin_file ]['slug']]))
                {
                    $plugins[ $plugin_file ]['rollback_data']=$rollback_plugin_data[$plugins[ $plugin_file ]['slug']];
                }
                else
                {
                    $plugins[ $plugin_file ]['rollback_data']=array();
                }
            }
            else
            {
                $plugins[ $plugin_file ]['rollback_data']=array();
            }

            $plugins[ $plugin_file ]['rollback']=$this->get_rollback_data($plugin_file);
        }
        ?>
        <div id="wpvivid_backup_plugin_progress" class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="display: none">
            <p>
                <span class="wpvivid-span-progress">
                    <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: 0%" >0% completed</span>
                </span>
            </p>
            <p>
                <span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span>
                <span>
                    <span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Rolling back the plugin...</span>
                </span>
            </p>
        </div>
        <div id="wpvivid_plugins_list">
            <?php
            $table=new WPvivid_Rollback_Plugins_List();
            $table->set_list($plugins);
            $table->prepare_items();
            $table->display();
            ?>
        </div>
        <script>
            var wpvivid_current_rollback_slug='';
            var wpvivid_b_rollback_finished=false;
            var wpvivid_b_download_rollback_finished=false;
            var wpvivid_current_type='';
            jQuery('#wpvivid_plugins_list').on("click",'.wpvivid-enable-auto-backup',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').attr('id');
                var enable=true;
                if (Obj.is(":checked"))
                {
                    enable=true;
                }
                else
                {
                    enable=false;
                }

                wpvivid_enable_auto_backup(slug,enable,Obj);
            });

            jQuery('#wpvivid_plugins_list').on("click",'.wpvivid-plugin-rollback',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').attr('id');
                var version=Obj.prev('select').val();
                if(version!=='-')
                {
                    var descript = '<?php _e('Are you sure to rollback the plugin?', 'wpvivid'); ?>';

                    var ret = confirm(descript);
                    if(ret === true)
                    {
                        //wpvivid_rollback_plugin(slug,version,Obj);
                        wpvivid_plugins_prepare_rollback_file(slug,version,Obj);
                    }
                }
            });

            jQuery('#wpvivid_plugins_list').on("click",'.wpvivid-view-plugin-versions',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').attr('id');

                wpvivid_view_plugin_versions(slug);
            });

            function wpvivid_plugins_delete_rollback_backup()
            {
                var plugins= new Array();
                var count = 0;

                jQuery('#wpvivid_plugins_list th input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        plugins[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });

                if( count === 0 )
                {
                    alert('<?php _e('Please select at least one item.','wpvivid'); ?>');
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_plugins_delete_rollback_backup',
                    'plugins': plugins,
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_plugins_list').html(jsonarray.html);
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('plugins enable', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_plugins_enable_auto_backup(enable)
            {
                var plugins= new Array();
                var count = 0;

                jQuery('#wpvivid_plugins_list th input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        plugins[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });

                if( count === 0 )
                {
                    alert('<?php _e('Please select at least one item.','wpvivid'); ?>');
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_plugins_enable_auto_backup',
                    'plugins': plugins,
                    'enable': enable,
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_plugins_list th input').each(function (i)
                        {
                            if(jQuery(this).prop('checked'))
                            {
                                if(enable=='enable')
                                {
                                    jQuery(this).parent().next().children().children(".wpvivid-enable-auto-backup").prop('checked', true);

                                }
                                else
                                {
                                    jQuery(this).parent().next().children().children(".wpvivid-enable-auto-backup").prop('checked', false);

                                }
                            }
                        });
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('plugins enable', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_simulate_rollback_progress()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=500;

                var timer = setInterval(function ()
                {
                    if(wpvivid_b_rollback_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rolling back the plugin completed successfully.</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rolling back the plugin...</span></span></p>';
                    }

                    jQuery("#wpvivid_backup_plugin_progress").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function wpvivid_simulate_plugins_download_rollback_progress_ex()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=3000;

                var timer = setInterval(function ()
                {
                    if(wpvivid_b_download_rollback_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Preparing rollback file has completed successfully.</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Preparing rollback file...</span></span></p>';
                    }

                    jQuery("#wpvivid_backup_plugin_progress").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function wpvivid_plugins_prepare_rollback_file(slug,version,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_prepare_rollback_file',
                    'slug':slug,
                    'version':version,
                    'type':'plugins'
                };

                wpvivid_b_download_rollback_finished=false;
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file...");
                jQuery('#wpvivid_backup_plugin_progress').show();
                wpvivid_simulate_plugins_download_rollback_progress_ex();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished)
                        {
                            wpvivid_b_download_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file has completed successfully.");
                            wpvivid_rollback_plugin(slug,version,Obj);
                        }
                        else
                        {
                            wpvivid_get_plugins_rollback_download_progress(slug,version,Obj);
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_backup_plugin_progress').hide();
                        alert(jsonarray.error);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_plugins_rollback_download_progress(slug,version,Obj);
                });
            }

            function wpvivid_get_plugins_rollback_download_progress(slug,version,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_get_rollback_download_progress'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished)
                        {
                            wpvivid_b_download_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file has completed successfully.");
                            wpvivid_rollback_plugin(slug,version,Obj);
                        }
                        else
                        {
                            setTimeout(function ()
                            {
                                wpvivid_get_plugins_rollback_download_progress(slug,version,Obj);
                            }, 1000);
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_backup_plugin_progress').hide();
                        alert(jsonarray.error);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_plugins_rollback_download_progress(slug,version,Obj);
                });
            }

            function wpvivid_rollback_plugin(slug,version,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_rollback_plugin',
                    'slug':slug,
                    'version':version
                };

                wpvivid_b_rollback_finished=false;
                jQuery('#wpvivid_backup_plugin_progress').show();
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_rollback_progress_text').html("Rolling back the plugin...");

                wpvivid_simulate_rollback_progress();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_b_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('#wpvivid_rollback_progress_text').html("Rollback has completed successfully.");
                            var span=Obj.parent().prev().prev().children(".current-version");
                            span.html(version);
                            setTimeout(function()
                            {
                                jQuery('#wpvivid_backup_plugin_progress').hide();
                                alert("Rolling back the plugin completed successfully.");
                            }, 1200);
                        }
                        else
                        {
                            jQuery('#wpvivid_backup_plugin_progress').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_backup_plugin_progress').hide();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_backup_plugin_progress').hide();
                    var error_message = wpvivid_output_ajaxerror('rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_enable_auto_backup(slug,enable,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_enable_auto_backup',
                    'slug':slug,
                    'enable':enable
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {

                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('enable auto backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_view_plugin_versions(slug)
            {
                var ajax_data = {
                    'action':'wpvivid_view_plugin_versions',
                    'slug':slug
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_current_type='plugins';
                            wpvivid_current_rollback_slug=slug;
                            jQuery('#wpvivid_rollback_detail').html(jsonarray.detail);
                            jQuery('#wpvivid_rollback_backup_list').html(jsonarray.backup_list);
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'version_backup', 'plugins' ]);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('get plugin detail', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_plugins_list').on("click",'#wpvivid_apply_plugins_bulk_action',function()
            {
                var action=jQuery('#wpvivid_plugins_bulk_action').val();
                if(action=='-1')
                {

                }
                else
                {
                    if(action=="enable_all")
                    {
                        wpvivid_plugins_enable_all_auto_backup(action);
                    }
                    else if(action=="disable_all")
                    {
                        wpvivid_plugins_enable_all_auto_backup(action);
                    }
                    else if(action=="enable_active")
                    {
                        wpvivid_plugins_enable_all_auto_backup(action);
                    }
                    else if(action=="delete")
                    {
                        wpvivid_plugins_delete_rollback_backup(action);
                    }
                    else
                    {
                        wpvivid_plugins_enable_auto_backup(action);
                    }
                }
            });

            jQuery('#wpvivid_plugins_list').on("click",'#wpvivid_apply_plugins_bulk_action_bottom',function()
            {
                var action=jQuery('#wpvivid_plugins_bulk_action_bottom').val();
                if(action=='-1')
                {

                }
                else
                {
                    if(action=="enable_all")
                    {
                        wpvivid_plugins_enable_all_auto_backup(action);
                    }
                    else if(action=="disable_all")
                    {
                        wpvivid_plugins_enable_all_auto_backup(action);
                    }
                    else if(action=="enable_active")
                    {
                        wpvivid_plugins_enable_all_auto_backup(action);
                    }
                    else if(action=="delete")
                    {
                        wpvivid_plugins_delete_rollback_backup(action);
                    }
                    else
                    {
                        wpvivid_plugins_enable_auto_backup(action);
                    }
                }
            });

            function wpvivid_plugins_enable_all_auto_backup(action)
            {
                var ajax_data = {
                    'action': 'wpvivid_plugins_enable_all_auto_backup',
                    'enable': action,
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_get_plugins_list();
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
                    var error_message = wpvivid_output_ajaxerror('plugins enable', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_plugins_list').on('click', 'thead tr td input', function()
            {
                wpvivid_control_plugins_select(jQuery(this));
            });

            jQuery('#wpvivid_plugins_list').on('click', 'tfoot tr td input', function()
            {
                wpvivid_control_plugins_select(jQuery(this));
            });

            function wpvivid_control_plugins_select(obj)
            {
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('#wpvivid_plugins_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_plugins_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_plugins_list tbody tr').each(function()
                    {
                        jQuery(this).find('th input').prop('checked', true);
                    });
                }
                else
                {
                    jQuery('#wpvivid_plugins_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_plugins_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_plugins_list tbody tr').each(function ()
                    {
                        jQuery(this).find('th input').prop('checked', false);
                    });
                }
            }

            jQuery('#wpvivid_plugins_list').on("click",'.first-page',function()
            {
                wpvivid_get_plugins_list('first');
            });

            jQuery('#wpvivid_plugins_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_plugins_list(page-1);
            });

            jQuery('#wpvivid_plugins_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_plugins_list(page+1);
            });

            jQuery('#wpvivid_plugins_list').on("click",'.last-page',function()
            {
                wpvivid_get_plugins_list('last');
            });

            jQuery('#wpvivid_plugins_list').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    wpvivid_get_plugins_list(page);
                }
            });

            function wpvivid_get_plugins_list(page=0)
            {
                if(page==0)
                {
                    page =jQuery('#wpvivid_plugins_list').find('.current-page').val();
                }

                var ajax_data = {
                    'action': 'wpvivid_get_plugins_list',
                    'page':page
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_plugins_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_plugins_list').html(jsonarray.html);
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
                    var error_message = wpvivid_output_ajaxerror('achieving plugins', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            wpvivid_get_plugins_list();
        </script>
        <?php
    }

    public function output_themes()
    {
        $current = get_site_transient( 'update_themes' );
        $themes =wp_get_themes();
        $themes_list=array();

        $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
        $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());

        foreach ($themes as $key=>$theme)
        {
            $stylesheet=$theme->get_stylesheet();
            $them_data["name"]=$theme->display( 'Name' );
            $them_data["version"]=$theme->display( 'Version' );
            $them_data["slug"]=$key;

            if ( isset( $current->response[ $stylesheet ] ) )
            {
                $update=(array)$current->response[ $stylesheet ];
                $them_data["new_version"]=$update['new_version'];
            }
            else if( isset( $current->no_update[ $stylesheet ] ) )
            {
                $update=(array)$current->no_update[ $stylesheet ];
                $them_data["new_version"]=$update['new_version'];
            }
            else
            {
                $them_data['new_version']='-';
            }

            $them_data['rollback']=$this->get_rollback_data($key,'themes');

            if(isset($rollback_theme_data[$key]))
            {
                $them_data['rollback_data']=$rollback_theme_data[$key];
            }
            else
            {
                $them_data['rollback_data']=array();
            }

            if(isset($themes_auto_backup_status[$key]))
            {
                $them_data['enable_auto_backup']= $themes_auto_backup_status[$key];
            }
            else
            {
                $them_data['enable_auto_backup']= false;
            }

            $themes_list[ $stylesheet ]= $them_data;
        }
        ?>
        <div id="wpvivid_backup_themes_progress" class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="display: none">
            <p>
                <span class="wpvivid-span-progress">
                    <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: 0%" >0% completed</span>
                </span>
            </p>
            <p>
                <span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span>
                <span>
                    <span>Rolling back the theme...</span>
                </span>
            </p>
        </div>
        <div id="wpvivid_themes_list">
            <?php
            $table=new WPvivid_Themes_List();
            $table->set_list($themes_list);
            $table->prepare_items();
            $table->display();
            ?>
        </div>
        <script>
            jQuery('#wpvivid_themes_list').on("click",'.wpvivid-enable-auto-backup',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').attr('id');
                var enable=true;
                if (Obj.is(":checked"))
                {
                    enable=true;
                }
                else
                {
                    enable=false;
                }

                wpvivid_theme_enable_auto_backup(slug,enable,Obj);
            });

            jQuery('#wpvivid_themes_list').on("click",'.wpvivid-theme-rollback',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').attr('id');
                var version=Obj.prev('select').val();
                if(version!=='-')
                {
                    var descript = '<?php _e('Are you sure to rollback the theme?', 'wpvivid'); ?>';

                    var ret = confirm(descript);
                    if(ret === true)
                    {
                        wpvivid_themes_prepare_rollback_file(slug,version,Obj);
                    }
                }
            });

            jQuery('#wpvivid_themes_list').on("click",'.wpvivid-view-theme-versions',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').attr('id');

                wpvivid_view_theme_versions(slug);
            });

            function wpvivid_themes_delete_rollback_backup()
            {
                var themes= new Array();
                var count = 0;

                jQuery('#wpvivid_themes_list th input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        themes[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });

                if( count === 0 )
                {
                    alert('<?php _e('Please select at least one item.','wpvivid'); ?>');
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_themes_delete_rollback_backup',
                    'themes': themes,
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_themes_list').html(jsonarray.html);
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('themes enable', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_themes_enable_auto_backup(enable)
            {
                var themes= new Array();
                var count = 0;

                jQuery('#wpvivid_themes_list th input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        themes[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });

                if( count === 0 )
                {
                    alert('<?php _e('Please select at least one item.','wpvivid'); ?>');
                    return;
                }

                var ajax_data = {
                    'action': 'wpvivid_themes_enable_auto_backup',
                    'themes': themes,
                    'enable': enable,
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        jQuery('#wpvivid_themes_list th input').each(function (i)
                        {
                            if(jQuery(this).prop('checked'))
                            {
                                if(enable=='enable')
                                {
                                    jQuery(this).parent().next().children().children(".wpvivid-enable-auto-backup").prop('checked', true);

                                }
                                else
                                {
                                    jQuery(this).parent().next().children().children(".wpvivid-enable-auto-backup").prop('checked', false);

                                }
                            }
                        });
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('themes enable', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_simulate_theme_rollback_progress()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=500;

                var timer = setInterval(function ()
                {
                    if(wpvivid_b_rollback_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rolling back the theme completed successfully.</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rolling back the theme...</span></span></p>';
                    }

                    jQuery("#wpvivid_backup_themes_progress").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function wpvivid_rollback_theme(slug,version,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_rollback_theme',
                    'slug':slug,
                    'version':version
                };

                wpvivid_b_rollback_finished=false;
                jQuery('#wpvivid_backup_themes_progress').show();
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_rollback_progress_text').html("Rolling back the theme...");

                wpvivid_simulate_theme_rollback_progress();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_b_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('#wpvivid_rollback_progress_text').html("Rolling back the theme completed successfully.");
                            var span=Obj.parent().prev().prev().children(".current-version");
                            span.html(version);
                            setTimeout(function()
                            {
                                jQuery('#wpvivid_backup_themes_progress').hide();
                                alert("Rolling back the theme completed successfully.");
                            }, 1200);
                        }
                        else
                        {
                            jQuery('#wpvivid_backup_themes_progress').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_backup_themes_progress').hide();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_backup_themes_progress').hide();
                    var error_message = wpvivid_output_ajaxerror('rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_simulate_themes_download_rollback_progress_ex()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=500;

                var timer = setInterval(function ()
                {
                    if(wpvivid_b_download_rollback_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Preparing rollback file has completed successfully.</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Preparing rollback file...</span></span></p>';
                    }

                    jQuery("#wpvivid_backup_themes_progress").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function wpvivid_themes_prepare_rollback_file(slug,version,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_prepare_rollback_file',
                    'slug':slug,
                    'version':version,
                    'type':'themes'
                };

                wpvivid_b_download_rollback_finished=false;
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file...");
                jQuery('#wpvivid_backup_themes_progress').show();
                wpvivid_simulate_themes_download_rollback_progress_ex();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished)
                        {
                            wpvivid_b_download_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file has completed successfully.");
                            wpvivid_rollback_theme(slug,version,Obj);
                        }
                        else
                        {
                            wpvivid_get_themes_rollback_download_progress(slug,version,Obj);
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_backup_themes_progress').hide();
                        alert(jsonarray.error);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_themes_rollback_download_progress(slug,version,Obj);
                });
            }

            function wpvivid_get_themes_rollback_download_progress(slug,version,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_get_rollback_download_progress'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished)
                        {
                            wpvivid_b_download_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file has completed successfully.");
                            wpvivid_rollback_theme(slug,version,Obj);
                        }
                        else
                        {
                            setTimeout(function ()
                            {
                                wpvivid_get_themes_rollback_download_progress(slug,version,Obj);
                            }, 1000);
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_backup_themes_progress').hide();
                        alert(jsonarray.error);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_themes_rollback_download_progress(slug,version,Obj);
                });
            }

            function wpvivid_theme_enable_auto_backup(slug,enable,Obj)
            {
                var ajax_data = {
                    'action':'wpvivid_theme_enable_auto_backup',
                    'slug':slug,
                    'enable':enable
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {

                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('enable auto backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_view_theme_versions(slug)
            {
                var ajax_data = {
                    'action':'wpvivid_view_theme_versions',
                    'slug':slug
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_current_type='themes';
                            wpvivid_current_rollback_slug=slug;
                            jQuery('#wpvivid_rollback_detail').html(jsonarray.detail);
                            jQuery('#wpvivid_rollback_backup_list').html(jsonarray.backup_list);
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'version_backup', 'themes' ]);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('get themes detail', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_themes_list').on("click",'#wpvivid_apply_themes_bulk_action_bottom',function()
            {
                var action=jQuery('#wpvivid_themes_bulk_action_bottom').val();
                if(action=='-1')
                {
                    return;
                }
                else
                {
                    if(action=="enable_all")
                    {
                        wpvivid_themes_enable_all_auto_backup(action);
                    }
                    else if(action=="disable_all")
                    {
                        wpvivid_themes_enable_all_auto_backup(action);
                    }
                    else if(action=="enable_active")
                    {
                        wpvivid_themes_enable_all_auto_backup(action);
                    }
                    else if(action=="delete")
                    {
                        wpvivid_themes_delete_rollback_backup(action);
                    }
                    else
                    {
                        wpvivid_themes_enable_auto_backup(action);
                    }
                }
            });

            jQuery('#wpvivid_themes_list').on("click",'#wpvivid_apply_themes_bulk_action',function()
            {
                var action=jQuery('#wpvivid_themes_bulk_action').val();
                if(action=='-1')
                {
                    return;
                }
                else
                {
                    if(action=="enable_all")
                    {
                        wpvivid_themes_enable_all_auto_backup(action);
                    }
                    else if(action=="disable_all")
                    {
                        wpvivid_themes_enable_all_auto_backup(action);
                    }
                    else if(action=="enable_active")
                    {
                        wpvivid_themes_enable_all_auto_backup(action);
                    }
                    else if(action=="delete")
                    {
                        wpvivid_themes_delete_rollback_backup(action);
                    }
                    else
                    {
                        wpvivid_themes_enable_auto_backup(action);
                    }
                }
            });

            function wpvivid_themes_enable_all_auto_backup(action)
            {
                var ajax_data = {
                    'action': 'wpvivid_themes_enable_all_auto_backup',
                    'enable': action,
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_get_themes_list();
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
                    var error_message = wpvivid_output_ajaxerror('plugins enable', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_themes_list').on('click', 'thead tr td input', function()
            {
                wpvivid_control_themes_select(jQuery(this));
            });

            jQuery('#wpvivid_themes_list').on('click', 'tfoot tr td input', function()
            {
                wpvivid_control_themes_select(jQuery(this));
            });

            function wpvivid_control_themes_select(obj)
            {
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('#wpvivid_themes_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_themes_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_themes_list tbody tr').each(function()
                    {
                        jQuery(this).find('th input').prop('checked', true);
                    });
                }
                else
                {
                    jQuery('#wpvivid_themes_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_themes_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_themes_list tbody tr').each(function ()
                    {
                        jQuery(this).find('th input').prop('checked', false);
                    });
                }
            }

            jQuery('#wpvivid_themes_list').on("click",'.first-page',function()
            {
                wpvivid_get_themes_list('first');
            });

            jQuery('#wpvivid_themes_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_themes_list(page-1);
            });

            jQuery('#wpvivid_themes_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_themes_list(page+1);
            });

            jQuery('#wpvivid_themes_list').on("click",'.last-page',function()
            {
                wpvivid_get_themes_list('last');
            });

            jQuery('#wpvivid_themes_list').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    wpvivid_get_themes_list(page);
                }
            });

            function wpvivid_get_themes_list(page=0)
            {
                if(page==0)
                {
                    page =jQuery('#wpvivid_themes_list').find('.current-page').val();
                }

                var ajax_data = {
                    'action': 'wpvivid_get_themes_list',
                    'page':page
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_themes_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_themes_list').html(jsonarray.html);
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
                    var error_message = wpvivid_output_ajaxerror('achieving themes', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function output_core()
    {
        $updates = get_core_updates();
        $current_version=get_bloginfo( 'version' );
        if ( isset( $updates[0]->version ) && version_compare( $updates[0]->version, $current_version, '>' ) )
        {
            $new_version=$updates[0]->version;
        }
        else
        {
            $new_version=$current_version;
        }

        $enable_core_auto_backup=get_option('wpvivid_plugins_auto_backup_core',false);

        if($enable_core_auto_backup)
        {
            $enable_check="checked";
        }
        else
        {
            $enable_check="";
        }
        ?>
        <div id="wpvivid_backup_core_progress" class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="display: none">
            <p>
                <span class="wpvivid-span-progress">
                    <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: 0%" >0% completed</span>
                </span>
            </p>
            <p>
                <span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span>
                <span>
                    <span id="wpvivid_rollback_core_progress_text">Rolling back WordPress core...</span>
                </span>
            </p>
        </div>
        <table class="wp-list-table widefat plugins" style="margin-bottom:0.5rem;">
            <tbody id="the-list" data-wp-lists="list:plugin">
            <tr class="active">
                <th style="width:2rem;">
                    <span class="dashicons dashicons-wordpress-alt wpvivid-dashicons-large wpvivid-dashicons-blue"></span>
                </th>
                <td class="column-description desc">
                    <div class="eum-plugins-name-actions"><h4 class="eum-plugins-name" style="margin:0;">Wordpress Core</h4></div>
                    <div class="active second plugin-version-author-uri">
                        <div><span>Current Version </span><strong><span style="color: orange;"><?php echo $current_version;?></span></strong> | <span>Latest Version </span><strong><span style="color: green;"><?php echo $new_version;?></span></strong> </div>
                    </div>
                    <div>
                        <label class="wpvivid-switch" title="Enable/Disable the job">
                            <input id="wpvivid_enable_core_auto_backup" type="checkbox" <?php echo $enable_check;?> >
                            <span class="wpvivid-slider wpvivid-round"></span>
                        </label>
                        <span>Enable "Backup the <strong>Wordpress Core</strong> before update".</span>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <div id="wpvivid_core_backup_list">
            <?php
            $core_list=$this->get_core_data();
            $table=new WPvivid_Core_List();
            $table->set_list($core_list);
            $table->prepare_items();
            $table->display();
            ?>
        </div>
        <a id="wpvivid_a_core_link" style="display: none;"></a>


        <script>
            jQuery('#wpvivid_enable_core_auto_backup').click(function()
            {
                if(jQuery(this).prop('checked'))
                {
                    var enable=true;
                }
                else
                {
                    var enable=false;
                }

                wpvivid_enable_core_auto_backup(enable);
            });

            function wpvivid_enable_core_auto_backup(enable)
            {
                var ajax_data = {
                    'action': 'wpvivid_enable_core_auto_backup',
                    'enable': enable,
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('enable', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_core_backup_list').on("click",'.wpvivid-core-download',function()
            {
                var Obj=jQuery(this);
                var version=Obj.closest('tr').data('version');

                wpvivid_download_core_rollback(version);
            });

            function wpvivid_download_core_rollback(version)
            {
                var a = document.getElementById('wpvivid_a_core_link');
                var url=ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_core_rollback&version='+version;
                a.href = url;
                a.download = 'wordpress'+version+'.zip';
                a.click();
            }

            jQuery('#wpvivid_core_backup_list').on("click",'.wpvivid-rollback-core-version',function()
            {
                var Obj=jQuery(this);
                var version=Obj.closest('tr').data('version');
                var descript = '<?php _e('Are you sure to rollback wordpress core?', 'wpvivid'); ?>';
                var ret = confirm(descript);
                if (ret === true)
                {
                    wpvivid_rollback_core(version);
                }
            });

            function wpvivid_simulate_core_rollback_progress()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=500;

                var timer = setInterval(function ()
                {
                    if(wpvivid_b_rollback_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rolling back WordPress core completed successfully.</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rolling back WordPress core...</span></span></p>';
                    }

                    jQuery("#wpvivid_backup_core_progress").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function wpvivid_rollback_core(version)
            {
                var ajax_data = {
                    'action':'wpvivid_rollback_core',
                    'version': version
                };

                wpvivid_b_rollback_finished=false;
                jQuery('#wpvivid_backup_core_progress').show();
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_rollback_core_progress_text').html("Rolling back WordPress core...");

                wpvivid_simulate_core_rollback_progress();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_do_rollback_core();
                        }
                        else
                        {
                            jQuery('#wpvivid_backup_core_progress').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_backup_core_progress').hide();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_backup_core_progress').hide();
                    var error_message = wpvivid_output_ajaxerror('rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_do_rollback_core()
            {
                var ajax_data = {
                    'action':'wpvivid_do_rollback_core'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    setTimeout(function(){
                        wpvivid_get_rollback_core_progress();
                    }, 1000);
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_rollback_core_progress();
                });
            }

            function wpvivid_get_rollback_core_progress()
            {
                var ajax_data = {
                    'action':'wpvivid_get_rollback_core_progress'
                };
                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.status=='running')
                            {
                                setTimeout(function(){
                                    wpvivid_get_rollback_core_progress();
                                }, 2000);
                            }
                            else if(jsonarray.status=='completed')
                            {
                                wpvivid_b_rollback_finished=true;
                                jQuery('.wpvivid-span-processed-progress').html("100% completed");
                                jQuery(".wpvivid-span-processed-progress").width( "100%" );
                                jQuery('#wpvivid_rollback_progress_text').html("Rolling back WordPress core completed successfully.");
                                alert("Rollback has completed successfully.");
                                location.reload();
                            }
                        }
                        else {
                            jQuery('#wpvivid_backup_core_progress').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        setTimeout(function(){
                            wpvivid_get_rollback_core_progress();
                        }, 2000);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function(){
                        wpvivid_get_rollback_core_progress();
                    }, 2000);
                });
            }

            jQuery('#wpvivid_core_backup_list').on("click",'#wpvivid_rollback_core_bulk_action',function()
            {
                var action=jQuery('#wpvivid_rollback_core_bulk_action_select').val();
                if(action=='-1')
                {
                    return;
                }
                else
                {
                    wpvivid_delete_core_rollback();
                }
            });

            function wpvivid_delete_core_rollback()
            {
                var versions= new Array();
                var count = 0;

                jQuery('#wpvivid_core_backup_list th input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        versions[count]=jQuery(this).closest('tr').data('version');
                        count++;
                    }
                });

                if( count === 0 )
                {
                    alert('<?php _e('Please select at least one item.','wpvivid'); ?>');
                    return;
                }

                var ajax_data = {
                    'action':'wpvivid_delete_core_rollback',
                    'versions':versions,
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_core_backup_list').html(jsonarray.html);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_core_backup_list').on("click",'.first-page',function()
            {
                wpvivid_get_core_list('first');
            });

            jQuery('#wpvivid_core_backup_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_core_list(page-1);
            });

            jQuery('#wpvivid_core_backup_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_core_list(page+1);
            });

            jQuery('#wpvivid_core_backup_list').on("click",'.last-page',function()
            {
                wpvivid_get_core_list('last');
            });

            jQuery('#wpvivid_core_backup_list').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    wpvivid_get_core_list(page);
                }
            });

            function wpvivid_get_core_list(page=0)
            {
                if(page==0)
                {
                    page =jQuery('#wpvivid_core_backup_list').find('.current-page').val();
                }

                var ajax_data = {
                    'action': 'wpvivid_get_core_list',
                    'page':page
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_core_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_core_backup_list').html(jsonarray.html);
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
                    var error_message = wpvivid_output_ajaxerror('achieving core backups', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_core_backup_list').on('click', 'thead tr td input', function()
            {
                wpvivid_control_core_select(jQuery(this));
            });

            jQuery('#wpvivid_core_backup_list').on('click', 'tfoot tr td input', function()
            {
                wpvivid_control_core_select(jQuery(this));
            });

            function wpvivid_control_core_select(obj)
            {
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('#wpvivid_core_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_core_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_core_backup_list tbody tr').each(function()
                    {
                        jQuery(this).find('th input').prop('checked', true);
                    });
                }
                else
                {
                    jQuery('#wpvivid_core_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_core_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_core_backup_list tbody tr').each(function ()
                    {
                        jQuery(this).find('th input').prop('checked', false);
                    });
                }
            }
        </script>
        <?php
    }

    public function output_settings()
    {
        $counts=get_option('wpvivid_max_rollback_count',array());

        $max_plugins_count=isset($counts['max_plugins_count'])?$counts['max_plugins_count']:5;
        $max_themes_count=isset($counts['max_themes_count'])?$counts['max_themes_count']:5;
        $max_core_count=isset($counts['max_core_count'])?$counts['max_core_count']:5;

        $auto_backup_db_before_update = get_option('wpvivid_auto_backup_db_before_update', false);
        if($auto_backup_db_before_update)
        {
            $auto_backup_db_before_update=' checked';
            $auto_backup_db_setting_display='';
        }
        else
        {
            $auto_backup_db_before_update="";
            $auto_backup_db_setting_display='display: none;';
        }

        $auto_enable_new_plugin = get_option('wpvivid_auto_enable_new_plugin', false);
        if($auto_enable_new_plugin)
        {
            $auto_enable_new_plugin='checked';
        }
        else
        {
            $auto_enable_new_plugin='';
        }

        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        $remote_id = get_option('wpvivid_rollback_remote_id', 0);

        $remoteslist=WPvivid_Setting::get_all_remote_options();
        $has_remote = false;
        foreach ($remoteslist as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            else
            {
                $has_remote = true;
            }
        }

        if(!$has_remote)
        {
            $default_remote_seletor = 'display: none;';
        }
        else
        {
            if($rollback_remote)
            {
                $default_remote_seletor = '';
            }
            else
            {
                $default_remote_seletor = 'display: none;';
            }
        }


        $rollback_retain_local = get_option('wpvivid_rollback_retain_local', 0);
        if($rollback_retain_local)
        {
            $rollback_retain_local = 'checked';
        }
        else
        {
            $rollback_retain_local = '';
        }

        $general_setting=WPvivid_Setting::get_setting(true, "");
        if(isset($general_setting['options']['wpvivid_common_setting']['rollback_max_backup_count']))
            $rollback_max_backup_count = $general_setting['options']['wpvivid_common_setting']['rollback_max_backup_count'];
        else
            $rollback_max_backup_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $rollback_max_backup_count=intval($rollback_max_backup_count);

        if(isset($general_setting['options']['wpvivid_common_setting']['rollback_max_remote_backup_count']))
            $rollback_max_remote_backup_count = $general_setting['options']['wpvivid_common_setting']['rollback_max_remote_backup_count'];
        else
            $rollback_max_remote_backup_count = WPVIVID_DEFAULT_LOCAL_BACKUP_COUNT;
        $rollback_max_remote_backup_count=intval($rollback_max_remote_backup_count);

        ?>
        <div>
            <p>Note: Once the retention is set up and reached, the oldest versioning backup will be deleted accordingly through a daily cron.</p>
            <p>
                <input type="text" class="wpvivid-rollback-count-retention" id="wpvivid_max_plugins_count" value="<?php echo $max_plugins_count;?>"> versioning backups retained for plugins.
            </p>
            <p>
                <input type="text" class="wpvivid-rollback-count-retention" id="wpvivid_max_themes_count" value="<?php echo $max_themes_count;?>"> versioning backups retained for themes.
            </p>
            <p>
                <input type="text" class="wpvivid-rollback-count-retention"  id="wpvivid_max_core_count" value="<?php echo $max_core_count;?>"> versioning backups retained for Wordpress core.
            </p>
        </div>
        <div style="margin-bottom: 10px;">
            <p id="wpvivid_manual_backup_remote_selector_part">
                <select id="wpvivid_manual_backup_remote_selector">

                    <?php

                    if($rollback_remote)
                    {
                        ?>
                        <option value="-1">Choose cloud storage</option>
                        <?php
                    }
                    else
                    {
                        ?>
                        <option value="-1" selected="selected" >Choose cloud storage</option>
                        <?php
                    }

                    $remoteslist=WPvivid_Setting::get_all_remote_options();
                    if(!empty($remoteslist))
                    {
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

                            if($remote_id==$key&&$rollback_remote)
                            {
                                ?>
                                <option value="<?php esc_attr_e($remote_option['id']); ?>" selected="selected"><?php echo $remote_option['name']; ?></option>
                                <?php
                            }
                            else
                            {
                                ?>
                                <option value="<?php esc_attr_e($remote_option['id']); ?>" ><?php echo $remote_option['name']; ?></option>
                                <?php
                            }

                        }
                    }
                    ?>
                </select>
                <span>Choose cloud storage for versioning backups (files + database). Add cloud storage on <a href="<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote'); ?>">this page</a> if you haven't. </span>
            </p>
            <p>
                <label>
                    <input type="checkbox" class="wpvivid-rollback-remote" id="wpvivid_rollback_retain_local" <?php echo $rollback_retain_local; ?>> Keep storing the backups in localhost after uploading to remote storage.
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="wpvivid_auto_backup_db_before_update" <?php esc_attr_e($auto_backup_db_before_update) ?> />
                    <span>Back up the database before updates. You can manage the backups in Backup Manager.</span>
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="wpvivid_auto_enable_new_plugin" <?php esc_attr_e($auto_enable_new_plugin); ?> />
                    <span>Automatically enable 'Auto backup before update' for all new plugins installed on this site.</span>
                </label>
            </p>
            <div id="wpvivid_auto_backup_db_count_display" style="<?php echo esc_attr($auto_backup_db_setting_display); ?>">
                <p>
                    <label>
                        <input type="text" class="wpvivid-backup-count-retention" placeholder="30" id="wpvivid_rollback_max_backup_count" value="<?php esc_attr_e($rollback_max_backup_count); ?>">
                        <span> database backups retained in localhost.</span>
                    </label>
                </p>
                <p>
                    <label>
                        <input type="text" class="wpvivid-backup-count-retention" placeholder="30" id="wpvivid_rollback_max_remote_backup_count" value="<?php esc_attr_e($rollback_max_remote_backup_count); ?>">
                        <span> database backups retained in remote storage.</span>
                    </label>
                </p>
            </div>
            <p>
                <input class="button-primary" id="wpvivid_rollback_file_sync" type="submit" value="Sync Versioning Backups">
                <span>Versioning backups information can be lost after site migration or restoration. Use this function to sync versioning backups when the information is lost.</span>
            </p>
        </div>
        <div style="margin-top:1rem;">
            <input class="button-primary" id="wpvivid_submit_rollback_setting" type="submit" value="Save Changes">
        </div>
        <div style="clear: both;"></div>
        <script>
            var has_remote = '<?php echo $has_remote; ?>';

            jQuery('#wpvivid_rollback_remote').click(function()
            {
                if(jQuery('#wpvivid_rollback_remote').prop('checked'))
                {
                    if(!has_remote)
                    {
                        var descript = 'There is no default remote storage configured. Please set it up first.';
                        var ret = confirm(descript);
                        if(ret === true)
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-remote', 'wpvivid-remote'); ?>';
                        }
                        jQuery('#wpvivid_rollback_remote').prop('checked', false);
                    }
                    else
                    {
                        jQuery('#wpvivid_manual_backup_remote_selector_part').show();
                    }
                }
                else
                {
                    jQuery('#wpvivid_manual_backup_remote_selector_part').hide();
                }
            });

            jQuery('.wpvivid-rollback-count-retention').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery(this).val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery(this).val('');
                }
            });

            jQuery('#wpvivid_submit_rollback_setting').click(function(){
                wpvivid_submit_rollback_setting();
            });

            function wpvivid_submit_rollback_setting()
            {
                var max_plugins_count = jQuery('#wpvivid_max_plugins_count').val();
                var max_themes_count = jQuery('#wpvivid_max_themes_count').val();
                var max_core_count = jQuery('#wpvivid_max_core_count').val();

                var auto_backup_before_update = '0';
                if(jQuery('#wpvivid_auto_backup_db_before_update').prop('checked'))
                {
                    auto_backup_before_update = '1';
                }
                else
                {
                    auto_backup_before_update = '0';
                }

                var rollback_retain_local = '0';
                if(jQuery('#wpvivid_rollback_retain_local').prop('checked'))
                {
                    rollback_retain_local = '1';
                }
                else
                {
                    rollback_retain_local = '0';
                }

                var auto_enable_new_plugin = '0';
                if(jQuery('#wpvivid_auto_enable_new_plugin').prop('checked'))
                {
                    auto_enable_new_plugin = '1';
                }
                else
                {
                    auto_enable_new_plugin = '0';
                }

                var rollback_remote='0';
                var remote_id=0;
                remote_id = jQuery('#wpvivid_manual_backup_remote_selector').val();

                if(remote_id=="-1")
                {
                    rollback_remote='0';
                }
                else
                {
                    rollback_remote='1';
                }

                var rollback_max_backup_count = jQuery('#wpvivid_rollback_max_backup_count').val();
                var rollback_max_remote_backup_count = jQuery('#wpvivid_rollback_max_remote_backup_count').val();

                var ajax_data = {
                    'action': 'wpvivid_set_rollback_setting',
                    'max_plugins_count': max_plugins_count,
                    'max_themes_count': max_themes_count,
                    'max_core_count' : max_core_count,
                    'auto_backup_before_update':auto_backup_before_update,
                    'rollback_retain_local':rollback_retain_local,
                    'auto_enable_new_plugin':auto_enable_new_plugin,
                    'rollback_remote':rollback_remote,
                    'rollback_max_backup_count':rollback_max_backup_count,
                    'rollback_max_remote_backup_count':rollback_max_remote_backup_count,
                    'remote_id':remote_id
                };
                jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'none', 'opacity': '0.4'});

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);


                        if (jsonarray.result === 'success')
                        {
                            if(jsonarray.remote_change)
                            {
                                wpvivid_rollback_plugins_sync();
                            }
                            else
                            {
                                jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                        }
                        else
                        {
                            jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('changing settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_rollback_file_sync').click(function()
            {
                wpvivid_rollback_plugins_sync();
            });

            function wpvivid_rollback_plugins_sync()
            {
                var ajax_data = {
                    'action': 'wpvivid_rollback_plugins_sync',
                };
                jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);

                    if (jsonarray.result === 'success')
                    {
                        wpvivid_get_rollback_plugins_continue();
                    }
                    else
                    {
                        jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('changing settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_get_rollback_plugins_continue()
            {
                var ajax_data = {
                    'action': 'wpvivid_rollback_plugins_sync_continue'
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished==false)
                        {
                            wpvivid_get_rollback_plugins_continue();
                        }
                        else
                        {
                            //alert("Rollback sync has completed successfully.");
                            //jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                            wpvivid_get_rollback_themes();
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('sync rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_get_rollback_themes()
            {
                var ajax_data = {
                    'action': 'wpvivid_rollback_themes_sync',
                };
                jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'none', 'opacity': '0.4'});

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);

                    if (jsonarray.result === 'success')
                    {
                        wpvivid_get_rollback_themes_continue();
                    }
                    else
                    {
                        jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('changing settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_get_rollback_themes_continue()
            {
                var ajax_data = {
                    'action': 'wpvivid_rollback_themes_sync_continue'
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished==false)
                        {
                            wpvivid_get_rollback_themes_continue();
                        }
                        else
                        {
                            jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-rollback', 'wpvivid-rollback'); ?>&tabs=settings';
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_rollback_file_sync').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#wpvivid_submit_rollback_setting').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('sync rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_auto_backup_db_before_update').on('click', function(){
                if(jQuery(this).prop('checked'))
                {
                    jQuery('#wpvivid_auto_backup_db_count_display').show();
                }
                else
                {
                    jQuery('#wpvivid_auto_backup_db_count_display').hide();
                }
            });
        </script>
        <?php
    }

    public function set_rollback_setting()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['max_plugins_count']))
        {
            $max_plugins_count = sanitize_key($_POST['max_plugins_count']);
        }
        else
        {
            die();
        }

        if (isset($_POST['max_themes_count']))
        {
            $max_themes_count = sanitize_key($_POST['max_themes_count']);
        }
        else
        {
            die();
        }

        if (isset($_POST['max_core_count']))
        {
            $max_core_count = sanitize_key($_POST['max_core_count']);
        }
        else
        {
            die();
        }

        if (isset($_POST['auto_backup_before_update']))
        {
            $auto_backup_before_update = sanitize_key($_POST['auto_backup_before_update']);
            if($auto_backup_before_update=="1")
            {
                $auto_backup_before_update=true;
            }
            else
            {
                $auto_backup_before_update=false;
            }
        }
        else
        {
            die();
        }

        if (isset($_POST['rollback_retain_local']))
        {
            $rollback_retain_local = sanitize_key($_POST['rollback_retain_local']);
            if($rollback_retain_local=='1')
            {
                $rollback_retain_local=1;
            }
            else
            {
                $rollback_retain_local=0;
            }
        }
        else
        {
            die();
        }

        if(isset($_POST['auto_enable_new_plugin']))
        {
            $auto_enable_new_plugin = sanitize_key($_POST['auto_enable_new_plugin']);
            if($auto_enable_new_plugin=='1')
            {
                $auto_enable_new_plugin=true;
            }
            else
            {
                $auto_enable_new_plugin=false;
            }
        }
        else
        {
            die();
        }

        if (isset($_POST['rollback_remote']))
        {
            $rollback_remote = sanitize_key($_POST['rollback_remote']);
            if($rollback_remote=="1")
            {
                $rollback_remote=1;
            }
            else
            {
                $rollback_remote=0;
            }
        }
        else
        {
            die();
        }

        $wpvivid_common_setting=get_option('wpvivid_common_setting');
        if (isset($_POST['rollback_max_backup_count']))
        {
            $rollback_max_backup_count=sanitize_key($_POST['rollback_max_backup_count']);
            $wpvivid_common_setting['rollback_max_backup_count']=intval($rollback_max_backup_count);
        }
        else
        {
            die();
        }
        if (isset($_POST['rollback_max_remote_backup_count']))
        {
            $rollback_max_remote_backup_count = sanitize_key($_POST['rollback_max_remote_backup_count']);
            $wpvivid_common_setting['rollback_max_remote_backup_count']=intval($rollback_max_remote_backup_count);
        }
        else
        {
            die();
        }
        update_option('wpvivid_common_setting', $wpvivid_common_setting, 'no');

        if (isset($_POST['remote_id']))
        {
            $remote_id = sanitize_key($_POST['remote_id']);
        }
        else
        {
            die();
        }
        //
        $counts=get_option('wpvivid_max_rollback_count',array());

        $counts['max_plugins_count']=$max_plugins_count;
        $counts['max_themes_count']=$max_themes_count;
        $counts['max_core_count']=$max_core_count;

        $remote_change=false;
        if($rollback_remote)
        {
            $old_remote_id=get_option('wpvivid_rollback_remote_id',0);
            if($old_remote_id!=$remote_id)
            {
                $remote_change=true;
            }
        }


        update_option('wpvivid_max_rollback_count',$counts,'no');
        update_option('wpvivid_max_rollback_count',$counts,'no');
        update_option('wpvivid_auto_backup_db_before_update', $auto_backup_before_update, 'no');
        update_option('wpvivid_rollback_retain_local', $rollback_retain_local, 'no');
        update_option('wpvivid_auto_enable_new_plugin', $auto_enable_new_plugin, 'no');
        update_option('wpvivid_rollback_remote', $rollback_remote, 'no');
        update_option('wpvivid_rollback_remote_id', $remote_id, 'no');

        $ret['result']='success';
        $ret['remote_change']=$remote_change;
        echo json_encode($ret);
        die();
    }

    public function output_version_backup()
    {
        ?>

        <div id="wpvivid_rollback_detail">
        </div>

        <div id="wpvivid_rollback_progress" class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="display: none">
            <p>
                <span class="wpvivid-span-progress">
                    <span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: 0%" >0% completed</span>
                </span>
            </p>
            <p>
                <span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span>
                <span>
                    <span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Rollback ing...</span>
                </span>
            </p>
        </div>

        <div id="wpvivid_rollback_backup_list">
        </div>
        <a id="wpvivid_a_link" style="display: none;"></a>
        <script>
            //
            jQuery('#wpvivid_rollback_backup_list').on("click",'.wpvivid-rollback-download',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').data('slug');
                var version=Obj.closest('tr').data('version');

                wpvivid_prepare_rollback_file(wpvivid_current_type,slug,version,'download');
                //wpvivid_download_rollback(slug,version,wpvivid_current_type);
            });

            jQuery('#wpvivid_rollback_backup_list').on("click",'.wpvivid-rollback-version',function()
            {
                var Obj=jQuery(this);
                var slug=Obj.closest('tr').data('slug');
                var version=Obj.closest('tr').data('version');

                wpvivid_prepare_rollback_file(wpvivid_current_type,slug,version,'rollback');
            });

            function wpvivid_simulate_download_rollback_progress_ex()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=500;

                var timer = setInterval(function ()
                {
                    if(wpvivid_b_rollback_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Preparing rollback file has completed successfully.</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text" class="wpvivid-rollback-progress-text">Preparing rollback file...</span></span></p>';
                    }

                    jQuery("#wpvivid_rollback_progress").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function wpvivid_prepare_rollback_file(type,slug,version,next_action)
            {
                var ajax_data = {
                    'action':'wpvivid_prepare_rollback_file',
                    'slug':slug,
                    'version':version,
                    'type':type
                };

                wpvivid_b_rollback_finished=false;
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file...");
                jQuery('#wpvivid_rollback_progress').show();
                wpvivid_simulate_download_rollback_progress_ex();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished)
                        {
                            wpvivid_b_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file has completed successfully.");

                            if(next_action=="download")
                            {
                                jQuery('#wpvivid_rollback_progress').hide();
                                wpvivid_download_rollback(slug,version,wpvivid_current_type);
                            }
                            else
                            {
                                wpvivid_rollback_version(slug,version,type);
                            }
                        }
                        else
                        {
                            wpvivid_get_rollback_download_progress(type,slug,version,next_action);
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_rollback_progress').hide();
                        alert(jsonarray.error);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_rollback_download_progress(type,slug,version,next_action);
                });
            }

            function wpvivid_get_rollback_download_progress(type,slug,version,next_action)
            {
                var ajax_data = {
                    'action':'wpvivid_get_rollback_download_progress'
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.finished)
                        {
                            wpvivid_b_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('.wpvivid-rollback-progress-text').html("Preparing rollback file has completed successfully.");

                            if(next_action=="download")
                            {
                                wpvivid_download_rollback(slug,version,wpvivid_current_type);
                            }
                            else
                            {
                                wpvivid_rollback_version(slug,version,type);
                            }
                        }
                        else
                        {
                            setTimeout(function ()
                            {
                                wpvivid_get_rollback_download_progress(type,slug,version,next_action);
                            }, 1000);
                        }
                    }
                    else
                    {
                        jQuery('#wpvivid_rollback_progress').hide();
                        alert(jsonarray.error);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    wpvivid_get_rollback_download_progress(type,slug,version,next_action);
                });
            }

            function wpvivid_download_rollback(slug,version,type)
            {
                var a = document.getElementById('wpvivid_a_link');
                var url=ajaxurl+'?_wpnonce='+wpvivid_ajax_object_addon.ajax_nonce+'&action=wpvivid_download_rollback&slug='+slug+'&version='+version+'&type='+type;
                a.href = url;
                a.download = slug+'.zip';
                a.click();
            }

            function wpvivid_simulate_rollback_progress_ex()
            {
                var MaxProgess = 95,
                    currentProgess = 0,
                    steps = 1,
                    time_steps=500;

                var timer = setInterval(function ()
                {
                    if(wpvivid_b_rollback_finished)
                    {
                        currentProgess=100;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rollback has completed successfully.</span></span></p>';
                    }
                    else
                    {
                        currentProgess += steps;
                        var progress_html='<p><span class="wpvivid-span-progress">' +
                            '<span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress" style="background:#007cba;width: '+currentProgess+'%">' +
                            currentProgess+'% completed</span></span></p><p>' +
                            '<span><span id="wpvivid_rollback_progress_text">Rollback ing...</span></span></p>';
                    }

                    jQuery("#wpvivid_rollback_progress").html(progress_html);
                    if (currentProgess >= MaxProgess)
                    {
                        clearInterval(timer);
                    }
                }, time_steps);
            }

            function wpvivid_rollback_version(slug,version,type)
            {
                if(type=='plugins')
                {
                    var action='wpvivid_rollback_plugin';
                }
                else
                {
                    var action='wpvivid_rollback_theme';
                }
                var ajax_data = {
                    'action':action,
                    'slug':slug,
                    'version':version
                };

                wpvivid_b_rollback_finished=false;
                jQuery('#wpvivid_rollback_progress').show();
                jQuery('.wpvivid-span-processed-progress').html("0% completed");
                jQuery(".wpvivid-span-processed-progress").width( "0%" );
                jQuery('#wpvivid_rollback_progress_text').html("Rollback ing...");

                wpvivid_simulate_rollback_progress_ex();

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_b_rollback_finished=true;
                            jQuery('.wpvivid-span-processed-progress').html("100% completed");
                            jQuery(".wpvivid-span-processed-progress").width( "100%" );
                            jQuery('#wpvivid_rollback_progress_text').html("Rollback has completed successfully.");

                            setTimeout(function()
                            {
                                jQuery('#wpvivid_rollback_detail').find('.wpvivid-rollback-current-version').html(version);
                                jQuery('#wpvivid_rollback_progress').hide();
                                alert("Rollback has completed successfully.");
                            }, 1200);

                            //wpvivid_get_plugins_list();
                            //wpvivid_get_themes_list();
                        }
                        else
                        {
                            jQuery('#wpvivid_rollback_progress').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_rollback_progress').hide();
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_rollback_progress').hide();
                    var error_message = wpvivid_output_ajaxerror('rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            //
            jQuery('#wpvivid_rollback_backup_list').on("click",'#wpvivid_rollback_bulk_action_top',function()
            {
                var action=jQuery('#wpvivid_rollback_bulk_action_select_top').val();
                if(action=='-1')
                {
                    return;
                }
                else
                {
                    wpvivid_delete_rollback();
                }
            });

            jQuery('#wpvivid_rollback_backup_list').on("click",'#wpvivid_rollback_bulk_action_bottom',function()
            {
                var action=jQuery('#wpvivid_rollback_bulk_action_select_bottom').val();
                if(action=='-1')
                {
                    return;
                }
                else
                {
                    wpvivid_delete_rollback();
                }
            });

            function wpvivid_delete_rollback()
            {
                var versions= new Array();
                var count = 0;

                jQuery('#wpvivid_rollback_backup_list th input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        versions[count]=jQuery(this).closest('tr').data('version');
                        count++;
                    }
                });

                if( count === 0 )
                {
                    alert('<?php _e('Please select at least one item.','wpvivid'); ?>');
                    return;
                }

                var ajax_data = {
                    'action':'wpvivid_delete_rollback',
                    'slug':wpvivid_current_rollback_slug,
                    'versions':versions,
                    'type':wpvivid_current_type
                };

                wpvivid_post_request_addon(ajax_data, function(data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            wpvivid_get_rollback_list();
                            wpvivid_get_plugins_list();
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('rollback', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#wpvivid_rollback_backup_list').on('click', 'thead tr td input', function()
            {
                wpvivid_control_rollback_select(jQuery(this));
            });

            jQuery('#wpvivid_rollback_backup_list').on('click', 'tfoot tr td input', function()
            {
                wpvivid_control_rollback_select(jQuery(this));
            });

            function wpvivid_control_rollback_select(obj)
            {
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('#wpvivid_rollback_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_rollback_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#wpvivid_rollback_backup_list tbody tr').each(function()
                    {
                        jQuery(this).find('th input').prop('checked', true);
                    });
                }
                else
                {
                    jQuery('#wpvivid_rollback_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_rollback_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#wpvivid_rollback_backup_list tbody tr').each(function ()
                    {
                        jQuery(this).find('th input').prop('checked', false);
                    });
                }
            }

            jQuery('#wpvivid_rollback_backup_list').on("click",'.first-page',function()
            {
                wpvivid_get_rollback_list('first');
            });

            jQuery('#wpvivid_rollback_backup_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_rollback_list(page-1);
            });

            jQuery('#wpvivid_rollback_backup_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_rollback_list(page+1);
            });

            jQuery('#wpvivid_rollback_backup_list').on("click",'.last-page',function()
            {
                wpvivid_get_rollback_list('last');
            });

            jQuery('#wpvivid_rollback_backup_list').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    wpvivid_get_rollback_list(page);
                }
            });

            function wpvivid_get_rollback_list(page=0)
            {
                if(page==0)
                {
                    page =jQuery('#wpvivid_rollback_backup_list').find('.current-page').val();
                }

                var ajax_data = {
                    'action': 'wpvivid_get_rollback_list',
                    'slug':wpvivid_current_rollback_slug,
                    'type':wpvivid_current_type,
                    'page':page
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_rollback_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_rollback_backup_list').html(jsonarray.html);
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
                    var error_message = wpvivid_output_ajaxerror('achieving plugins', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function get_plugin_slug($file)
    {
        $plugin=dirname($file);
        if($plugin=='.')
        {
            $slug = pathinfo($file, PATHINFO_FILENAME);
        }
        else
        {
            $slug=$plugin;
        }

        return $slug;
    }

    public function get_rollback_data($slug,$type='plugins')
    {
        $plugin=dirname($slug);
        if($plugin=='.')
        {
            $plugin = pathinfo($slug, PATHINFO_FILENAME);
        }

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/'.$type.'/'.$plugin;
        if(file_exists($path))
        {
            $rollback=array();
            $plugin_dir  = @opendir( $path );

            while ( ( $file = readdir( $plugin_dir ) ) !== false )
            {
                if ( '.' === substr( $file, 0, 1 ) )
                {
                    continue;
                }

                if ( is_dir( $path . '/' . $file ) )
                {
                    if(file_exists($path . '/' . $file.'/'.$plugin.'.zip'))
                    {
                        $rollback[$file]=$plugin.'.zip';
                    }
                }
            }

            closedir( $plugin_dir );
            return $rollback;
        }
        else
        {
            return array();
        }
    }

    public function get_core_data()
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core';
        $core_list=array();

        if(file_exists($path))
        {
            $core_dir  = @opendir( $path );

            while ( ( $file = readdir( $core_dir ) ) !== false )
            {
                if ( '.' === substr( $file, 0, 1 ) )
                {
                    continue;
                }

                if ( is_dir( $path . '/' . $file ) )
                {
                    if(file_exists($path . '/' . $file.'/wordpress.zip'))
                    {
                        $file_name=$path . '/' . $file.'/wordpress.zip';
                        $info['id']=$file;
                        $info['version']=$file;
                        $info['date']=date('M d Y h:i A', filemtime($file_name));
                        $info['size']=size_format(filesize($file_name),2);
                        $core_list[$file]=$info;
                    }
                }
            }

            closedir( $core_dir );
        }

        return $core_list;
    }

    public function get_rollback_file_info($slug,$version,$file,$type='plugins')
    {
        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/'.$type.'/'.$slug.'/'.$version.'/'.$file;
        if(file_exists($path))
        {
            $info['date']=date('M d Y h:i A', filemtime($path));
            $info['size']=size_format(filesize($path),2);
            return $info;
        }
        else
        {
            $info['date']='';
            $info['size']='';
            return $info;
        }
    }

    public function get_enable_auto_backup_status($slug)
    {
        $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());
        if(isset($plugins_auto_backup_status[$slug]))
        {
            return $plugins_auto_backup_status[$slug];
        }
        else
        {
            return false;
        }
    }

    public function get_theme_enable_auto_backup_status($slug)
    {
        $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
        if(isset($themes_auto_backup_status[$slug]))
        {
            return $themes_auto_backup_status[$slug];
        }
        else
        {
            return false;
        }
    }

    public function rollback_plugin()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
        {
            $slug = sanitize_key($_POST['slug']);
        }
        else
        {
            die();
        }

        if (isset($_POST['version']) && !empty($_POST['version']) && is_string($_POST['version']))
        {
            $version = sanitize_text_field($_POST['version']);
        }
        else
        {
            die();
        }

        $package=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/plugins/'.$slug.'/' . $version.'/'.$slug.'.zip';
        if(!file_exists($package))
        {
            $ret['result']='failed';
            $ret['error']="Could not find the backup. Please check whether the backup exists.";
            echo json_encode($ret);
            die();
        }

        if( ! function_exists('plugins_api') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        }

        if(!class_exists('WP_Upgrader'))
            require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

        //require_once( ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php' );
        if(!class_exists('Plugin_Upgrader'))
            require_once( ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php' );


        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $ret['test']=$package;


        $remote_destination = WP_PLUGIN_DIR .'/'.$slug.'/';

        if(file_exists($remote_destination))
        {
            WP_Filesystem();
            $upgrader->clear_destination($remote_destination);
        }

        $return=$upgrader->install($package);

        if($return)
        {
            $ret['result']= 'success';
        }
        else
        {
            $ret['result'] = 'failed';
            if(is_wp_error( $return ))
            {
                $ret['error'] =$return->get_error_message() ;
            }
            else
            {
                $ret['error']='Installing the plugin failed. '.$return;
            }
        }

        echo json_encode($ret);
        die();
    }

    public function rollback_theme()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
        {
            $slug = sanitize_text_field($_POST['slug']);
        }
        else
        {
            die();
        }

        if (isset($_POST['version']) && !empty($_POST['version']) && is_string($_POST['version']))
        {
            $version = sanitize_text_field($_POST['version']);
        }
        else
        {
            die();
        }

        $package=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/themes/'.$slug.'/' . $version.'/'.$slug.'.zip';
        if(!file_exists($package))
        {
            $ret['result']='failed';
            $ret['error']="Could not find the backup. Please check whether the backup exists.";
            echo json_encode($ret);
            die();
        }

        if( ! function_exists('plugins_api') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        }

        if(!class_exists('WP_Upgrader'))
            require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );


        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Theme_Upgrader( $skin );
        $ret['test']=$package;


        $remote_destination = get_theme_root() .'/'.$slug.'/';

        if(file_exists($remote_destination))
        {
            WP_Filesystem();
            $upgrader->clear_destination($remote_destination);
        }

        $return=$upgrader->install($package);

        if($return)
        {
            $ret['result']= 'success';
        }
        else
        {
            $ret['result'] = 'failed';
            if(is_wp_error( $return ))
            {
                $ret['error'] =$return->get_error_message() ;
            }
            else
            {
                $ret['error']='Installing the theme failed. '.$return;
            }
        }

        echo json_encode($ret);
        die();
    }

    public function enable_auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
        {
            $slug = sanitize_key($_POST['slug']);
        }
        else
        {
            die();
        }

        if (isset($_POST['enable']))
        {
            $enable=$_POST['enable'];
        }
        else
        {
            die();
        }

        if($enable=='true')
        {
            $enable=true;
        }
        else
        {
            $enable=false;
        }

        $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());
        $plugins_auto_backup_status[$slug]=$enable;
        update_option('wpvivid_plugins_auto_backup_status',$plugins_auto_backup_status,'no');

        $ret['result']='success';
        echo json_encode($ret);
        die();
    }

    public function plugins_delete_rollback_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['plugins']) && !empty($_POST['plugins']) && is_array($_POST['plugins']))
        {
            set_time_limit(120);

            $plugins=$_POST['plugins'];

            foreach ($plugins as $slug)
            {
                $this->_delete_rollback($slug,'plugins');
            }

            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $all_plugins     = get_plugins();
            $current = get_site_transient( 'update_plugins' );
            $plugins = array();

            $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());
            $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());

            foreach ((array) $all_plugins as $plugin_file => $plugin_data)
            {
                if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
                {
                    continue;
                }

                if ( isset( $current->response[ $plugin_file ] ) )
                {
                    $plugins[ $plugin_file ]= $plugin_data;
                    $plugins[ $plugin_file ]['response']= (array)$current->response[ $plugin_file ];
                }
                else if( isset( $current->no_update[ $plugin_file ] ) )
                {
                    $plugins[ $plugin_file ]= $plugin_data;
                    $plugins[ $plugin_file ]['response']= (array)$current->no_update[ $plugin_file ];
                }
                else
                {
                    $plugins[ $plugin_file ]= $plugin_data;
                    $plugins[ $plugin_file ]['response']['new_version']='-';
                }

                $plugins[ $plugin_file ]['slug']=$this->get_plugin_slug($plugin_file);

                if(isset($plugins_auto_backup_status[$plugins[ $plugin_file ]['slug']]))
                {
                    $plugins[ $plugin_file ]['enable_auto_backup']= $plugins_auto_backup_status[$plugins[ $plugin_file ]['slug']];
                }
                else
                {
                    $auto_enable_new_plugin = get_option('wpvivid_auto_enable_new_plugin', false);
                    if($auto_enable_new_plugin)
                    {
                        $plugins[ $plugin_file ]['enable_auto_backup']= true;
                    }
                    else
                    {
                        $plugins[ $plugin_file ]['enable_auto_backup']= false;
                    }
                }

                $plugins[ $plugin_file ]['rollback']=$this->get_rollback_data($plugin_file);

                if(isset($plugins[ $plugin_file ]['slug']))
                {
                    if(isset($rollback_plugin_data[$plugins[ $plugin_file ]['slug']]))
                    {
                        $plugins[ $plugin_file ]['rollback_data']=$rollback_plugin_data[$plugins[ $plugin_file ]['slug']];
                    }
                    else
                    {
                        $plugins[ $plugin_file ]['rollback_data']=array();
                    }
                }
                else
                {
                    $plugins[ $plugin_file ]['rollback_data']=array();
                }
            }

            $table=new WPvivid_Rollback_Plugins_List();
            if(isset($_POST['page']))
            {
                $table->set_list($plugins,$_POST['page']);
            }
            else
            {
                $table->set_list($plugins);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
            $ret['plugins']=$plugins;
            echo json_encode($ret);

            die();
        }
        else
        {
            die();
        }
    }

    public function themes_delete_rollback_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['themes']) && !empty($_POST['themes']) && is_array($_POST['themes']))
        {
            set_time_limit(120);

            $themes=$_POST['themes'];

            foreach ($themes as $slug)
            {
                $this->_delete_rollback($slug,'themes');
            }

            $current = get_site_transient( 'update_themes' );
            $themes =wp_get_themes();
            $themes_list=array();

            $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
            $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());

            foreach ($themes as $key=>$theme)
            {
                $stylesheet=$theme->get_stylesheet();
                $them_data["name"]=$theme->display( 'Name' );
                $them_data["version"]=$theme->display( 'Version' );
                $them_data["slug"]=$key;

                if ( isset( $current->response[ $stylesheet ] ) )
                {
                    $update=(array)$current->response[ $stylesheet ];
                    $them_data["new_version"]=$update['new_version'];
                }
                else if( isset( $current->no_update[ $stylesheet ] ) )
                {
                    $update=(array)$current->no_update[ $stylesheet ];
                    $them_data["new_version"]=$update['new_version'];
                }
                else
                {
                    $them_data['new_version']='-';
                }

                $them_data['rollback']=$this->get_rollback_data($key,'themes');

                if(isset($rollback_theme_data[$key]))
                {
                    $them_data['rollback_data']=$rollback_theme_data[$key];
                }
                else
                {
                    $them_data['rollback_data']=array();
                }

                if(isset($themes_auto_backup_status[$key]))
                {
                    $them_data['enable_auto_backup']= $themes_auto_backup_status[$key];
                }
                else
                {
                    $them_data['enable_auto_backup']= false;
                }

                $themes_list[ $stylesheet ]= $them_data;
            }

            $table=new WPvivid_Themes_List();
            if(isset($_POST['page']))
            {
                $table->set_list($themes_list,$_POST['page']);
            }
            else
            {
                $table->set_list($themes_list);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
            echo json_encode($ret);

            die();
        }
        else
        {
            die();
        }
    }

    public function _get_plugin_list($slug,$rollback_data)
    {
        $rollback_list=array();

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $all_plugins     = get_plugins();
        $current_plugin_file='';
        foreach ((array) $all_plugins as $plugin_file => $plugin_data)
        {
            if($this->get_plugin_slug($plugin_file)==$slug)
            {
                $current_plugin_file=$plugin_file;
                break;
            }
        }

        if(empty($current_plugin_file))
        {
            return $rollback_list;
        }

        $plugin_data_rollback=$this->get_rollback_data($current_plugin_file);

        if(!empty($plugin_data_rollback))
        {
            foreach ($plugin_data_rollback as $version=>$file)
            {
                $info=$this->get_rollback_file_info($slug,$version,$file);
                $rollback['version']=$version;
                $rollback['slug']=$slug;
                $rollback['date']=$info['date'];
                $rollback['size']=$info['size'];
                $rollback_list[$version]=$rollback;
            }
        }

        if(!empty($rollback_data))
        {
            foreach ($rollback_data['version'] as $version=>$data)
            {
                if(!isset($rollback_list[$version]))
                {
                    if($data['upload'])
                    {
                        $rollback['version']=$version;
                        $rollback['slug']=$slug;
                        $rollback['date']=date('M d Y h:i A', $data['file']['modified']);
                        $rollback['size']=size_format($data['file']['size'],2);
                        $rollback_list[$version]=$rollback;
                    }
                }
            }
        }

        return $rollback_list;
    }

    public function _get_theme_list($slug,$rollback_data)
    {
        $rollback_list=array();

        $them_data_rollback=$this->get_rollback_data($slug,'themes');

        if(!empty($them_data_rollback))
        {
            foreach ($them_data_rollback as $version=>$file)
            {
                $info=$this->get_rollback_file_info($slug,$version,$file);
                $rollback['version']=$version;
                $rollback['slug']=$slug;
                $rollback['date']=$info['date'];
                $rollback['size']=$info['size'];
                $rollback_list[$version]=$rollback;
            }
        }

        if(!empty($rollback_data))
        {
            foreach ($rollback_data['version'] as $version=>$data)
            {
                if(!isset($rollback_list[$version]))
                {
                    if($data['upload'])
                    {
                        $rollback['version']=$version;
                        $rollback['slug']=$slug;
                        $rollback['date']=date('M d Y h:i A', $data['file']['modified']);
                        $rollback['size']=size_format($data['file']['size'],2);
                        $rollback_list[$version]=$rollback;
                    }
                }
            }
        }

        return $rollback_list;
    }

    public function _delete_rollback($slug,$type)
    {
        $rollback_data=false;

        if($type=="plugins")
        {
            $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
            if(isset($rollback_plugin_data[$slug]))
                $rollback_data=$rollback_plugin_data[$slug];

            $rollback_list=$this->_get_plugin_list($slug,$rollback_data);
        }
        else if($type=="themes")
        {
            $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());
            if(isset($rollback_theme_data[$slug]))
                $rollback_data=$rollback_theme_data[$slug];

            $rollback_list=$this->_get_theme_list($slug,$rollback_data);
        }
        else
        {
            return false;
        }

        if(empty($rollback_list))
            return false;

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/'.$type.'/'.$slug ;

        foreach ($rollback_list as $version=>$rollback)
        {
            if(file_exists($path.'/'.$version.'/'.$slug.'.zip'))
            {
                @unlink($path.'/'.$version.'/'.$slug.'.zip');
                @rmdir($path.'/'.$version);
            }

            if(!isset($rollback_data['version'][$version]))
                continue;

            $data=$rollback_data['version'][$version];
            if($data['upload'])
            {
                $this->cleanup_remote_rollback($type,$slug,$version);
            }

            if($type=="plugins")
            {
                $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
                unset($rollback_plugin_data[$slug]['version'][$version]);
                update_option('wpvivid_rollback_plugin_data',$rollback_plugin_data,'no');
            }
            else if($type=="themes")
            {
                $rollback_plugin_data=get_option('wpvivid_rollback_theme_data',array());
                unset($rollback_plugin_data[$slug]['version'][$version]);
                update_option('wpvivid_rollback_theme_data',$rollback_plugin_data,'no');
            }
        }

        return true;
    }

    public function theme_enable_auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
        {
            $slug = sanitize_text_field($_POST['slug']);
        }
        else
        {
            die();
        }

        if (isset($_POST['enable']))
        {
            $enable=$_POST['enable'];
        }
        else
        {
            die();
        }

        if($enable=='true')
        {
            $enable=true;
        }
        else
        {
            $enable=false;
        }

        $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
        $themes_auto_backup_status[$slug]=$enable;
        update_option('wpvivid_themes_auto_backup_status',$themes_auto_backup_status,'no');

        $ret['result']='success';
        echo json_encode($ret);
        die();
    }

    public function plugins_enable_auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['plugins']) && !empty($_POST['plugins']) && is_array($_POST['plugins']))
        {
            $plugins=$_POST['plugins'];

            if (isset($_POST['enable']))
            {
                $enable=$_POST['enable'];
            }
            else
            {
                die();
            }

            if($enable=='enable')
            {
                $enable=true;
            }
            else
            {
                $enable=false;
            }

            $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());

            foreach ($plugins as $slug)
            {
                $plugins_auto_backup_status[$slug]=$enable;
            }

            update_option('wpvivid_plugins_auto_backup_status',$plugins_auto_backup_status,'no');

            $ret['result']='success';
            echo json_encode($ret);

            die();
        }
        else
        {
            die();
        }
    }

    public function plugins_enable_all_auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['enable']))
        {
            $enable=$_POST['enable'];
        }
        else
        {
            die();
        }

        $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $all_plugins     = get_plugins();

        if($enable=="enable_all")
        {
            foreach ((array) $all_plugins as $plugin_file => $plugin_data)
            {
                if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
                {
                    continue;
                }

                $slug=$this->get_plugin_slug($plugin_file);

                $plugins_auto_backup_status[ $slug ]= true;
            }
        }
        else if($enable=="disable_all")
        {
            foreach ((array) $all_plugins as $plugin_file => $plugin_data)
            {
                if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
                {
                    continue;
                }

                $slug=$this->get_plugin_slug($plugin_file);

                $plugins_auto_backup_status[ $slug ]= false;
            }
        }
        else if($enable=="enable_active")
        {
            foreach ((array) $all_plugins as $plugin_file => $plugin_data)
            {
                if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
                {
                    continue;
                }

                $slug=$this->get_plugin_slug($plugin_file);

                if(is_plugin_active($plugin_file))
                {
                    $plugins_auto_backup_status[ $slug ]= true;
                }
                else
                {
                    $plugins_auto_backup_status[ $slug ]= false;
                }
            }
        }

        update_option('wpvivid_plugins_auto_backup_status',$plugins_auto_backup_status,'no');

        $ret['result']='success';
        $ret['test']=$plugins_auto_backup_status;
        echo json_encode($ret);

        die();
    }

    public function themes_enable_all_auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['enable']))
        {
            $enable=$_POST['enable'];
        }
        else
        {
            die();
        }

        $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
        $themes =wp_get_themes();

        if($enable=="enable_all")
        {
            foreach ($themes as $key=>$theme)
            {
                $themes_auto_backup_status[$key]=true;
            }
        }
        else if($enable=="disable_all")
        {
            foreach ($themes as $key=>$theme)
            {
                $themes_auto_backup_status[$key]=false;
            }
        }
        else if($enable=="enable_active")
        {
            foreach ($themes as $key=>$theme)
            {
                if ( get_stylesheet() === $key)
                {
                    $themes_auto_backup_status[$key]=true;
                }
                else
                {
                    $themes_auto_backup_status[$key]=false;
                }
            }
        }

        update_option('wpvivid_themes_auto_backup_status',$themes_auto_backup_status,'no');

        $ret['result']='success';
        echo json_encode($ret);

        die();
    }

    public function themes_enable_auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['themes']) && !empty($_POST['themes']) && is_array($_POST['themes']))
        {
            $themes=$_POST['themes'];

            if (isset($_POST['enable']))
            {
                $enable=$_POST['enable'];
            }
            else
            {
                die();
            }

            if($enable=='enable')
            {
                $enable=true;
            }
            else
            {
                $enable=false;
            }

            $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());

            foreach ($themes as $slug)
            {
                $themes_auto_backup_status[$slug]=$enable;
            }

            update_option('wpvivid_themes_auto_backup_status',$themes_auto_backup_status,'no');

            $ret['result']='success';
            echo json_encode($ret);

            die();
        }
        else
        {
            die();
        }
    }

    public function enable_core_auto_backup()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['enable']))
        {
            $enable=$_POST['enable'];
        }
        else
        {
            die();
        }

        if($enable=='true')
        {
            $enable=true;
        }
        else
        {
            $enable=false;
        }

        update_option('wpvivid_plugins_auto_backup_core',$enable,'no');

        $ret['result']='success';
        echo json_encode($ret);
        die();
    }

    public function view_plugin_versions()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
        {
            $slug = sanitize_key($_POST['slug']);
        }
        else
        {
            die();
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $all_plugins     = get_plugins();
        $current_plugin_file='';
        foreach ((array) $all_plugins as $plugin_file => $plugin_data)
        {
            if($this->get_plugin_slug($plugin_file)==$slug)
            {
                $current_plugin_file=$plugin_file;
                break;
            }
        }

        if(empty($current_plugin_file))
        {
            $ret['result']='failed';
            $ret['error']='plugin not found.';
            echo json_encode($ret);
            die();
        }

        $current = get_site_transient( 'update_plugins' );

        $plugin_data=get_plugin_data(WP_PLUGIN_DIR.'/'.$current_plugin_file);
        $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());

        if ( isset( $current->response[ $current_plugin_file ] ) )
        {
            $plugin_data['response']= (array)$current->response[ $current_plugin_file ];
        }
        else if( isset( $current->no_update[ $current_plugin_file ] ) )
        {
            $plugin_data['response']= (array)$current->no_update[ $current_plugin_file ];
        }
        else
        {
            $plugin_data['response']['new_version']='-';
        }

        $plugin_data['rollback']=$this->get_rollback_data($current_plugin_file);
        if(isset($rollback_plugin_data[$slug]))
            $plugin_data['rollback_data']=$rollback_plugin_data[$slug];
        else
            $plugin_data['rollback_data']=array();
        $rollback_list=array();
        if(!empty($plugin_data['rollback']))
        {
            foreach ($plugin_data['rollback'] as $version=>$file)
            {
                $info=$this->get_rollback_file_info($slug,$version,$file);
                $rollback['version']=$version;
                $rollback['slug']=$slug;
                $rollback['date']=$info['date'];
                $rollback['size']=$info['size'];
                $rollback_list[$version]=$rollback;
            }
        }

        if(!empty($plugin_data['rollback_data']))
        {
            foreach ($plugin_data['rollback_data']['version'] as $version=>$data)
            {
                if(!isset($rollback_list[$version]))
                {
                    if($data['upload'])
                    {
                        $rollback['version']=$version;
                        $rollback['slug']=$slug;
                        $rollback['date']=date('M d Y h:i A', $data['file']['modified']);
                        $rollback['size']=size_format($data['file']['size'],2);
                        $rollback_list[$version]=$rollback;
                    }
                }
            }
        }

        usort($rollback_list, function ($a, $b)
        {
            if ($a['version'] == $b['version'])
            {
                return 0;
            }

            if(version_compare($a['version'], $b['version'],'>'))
            {
                return 1;
            }
            else
            {
                return -1;
            }
        });

        $ret['result']='success';
        $table=new WPvivid_Rollback_List();
        $table->set_list($rollback_list);
        $table->prepare_items();
        ob_start();
        $table->display();
        $ret['backup_list'] = ob_get_clean();
        $ret['detail']=$this->get_plugin_detail($plugin_data,$current_plugin_file);
        $ret['test']=$rollback_list;

        echo json_encode($ret);

        die();
    }

    public function view_theme_versions()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
        {
            $slug = sanitize_text_field($_POST['slug']);
        }
        else
        {
            die();
        }

        $theme=wp_get_theme($slug);

        $them_data["Name"]=$theme->display( 'Name' );
        $them_data["Version"]=$theme->display( 'Version' );
        $them_data["Description"]=$theme->display( 'Description' );
        $them_data["icon"]=$theme->get_screenshot() . '?ver=' . $theme->get( 'Version' );
        $current = get_site_transient( 'update_themes' );

        if ( isset( $current->response[ $slug ] ) )
        {
            $update=(array)$current->response[ $slug ];
            $them_data["new_version"]=$update['new_version'];
            $them_data['response']= $update;
        }
        else if( isset( $current->no_update[ $slug ] ) )
        {
            $update=(array)$current->no_update[ $slug ];
            $them_data["new_version"]=$update['new_version'];
            $them_data['response']= $update;
        }
        else
        {
            $them_data['new_version']='-';
            $them_data['response']= array();
        }


        $them_data['rollback']=$this->get_rollback_data($slug,'themes');
        $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());
        if(isset($rollback_theme_data[$slug]))
            $them_data['rollback_data']=$rollback_theme_data[$slug];
        else
            $them_data['rollback_data']=array();

        $rollback_list=array();
        if(!empty($them_data['rollback']))
        {
            foreach ($them_data['rollback'] as $version=>$file)
            {
                $info=$this->get_rollback_file_info($slug,$version,$file,'themes');
                $rollback['version']=$version;
                $rollback['slug']=$slug;
                $rollback['date']=$info['date'];
                $rollback['size']=$info['size'];
                $rollback_list[$version]=$rollback;
            }
        }

        if(!empty($them_data['rollback_data']))
        {
            foreach ($them_data['rollback_data']['version'] as $version=>$data)
            {
                if(!isset($rollback_list[$version]))
                {
                    if($data['upload'])
                    {
                        $rollback['version']=$version;
                        $rollback['slug']=$slug;
                        $rollback['date']=date('M d Y h:i A', $data['file']['modified']);
                        $rollback['size']=size_format($data['file']['size'],2);
                        $rollback_list[$version]=$rollback;
                    }
                }
            }
        }

        usort($rollback_list, function ($a, $b)
        {
            if ($a['version'] == $b['version'])
            {
                return 0;
            }

            if(version_compare($a['version'], $b['version'],'>'))
            {
                return 1;
            }
            else
            {
                return -1;
            }
        });

        $ret['result']='success';
        $table=new WPvivid_Rollback_List();
        $table->set_list($rollback_list);
        $table->prepare_items();
        ob_start();
        $table->display();
        $ret['backup_list'] = ob_get_clean();
        $ret['detail']=$this->get_theme_detail($them_data,$slug);
        $ret['test']=$them_data;

        echo json_encode($ret);

        die();
    }

    public function get_plugin_detail($plugin_data,$plugin_file)
    {
        $icon= '<span class="dashicons dashicons-admin-plugins"></span>';
        if(isset($plugin_data['response']['icons']))
        {
            $preferred_icons = array( 'svg', '2x', '1x', 'default' );
            foreach ( $preferred_icons as $preferred_icon )
            {
                if ( ! empty( $plugin_data['response']['icons'][ $preferred_icon ] ) )
                {
                    $icon = '<img src="' . esc_url(  $plugin_data['response']['icons'][ $preferred_icon ] ) . '" alt="" />';
                    break;
                }
            }
        }

        $name=$plugin_data["Name"];
        $description=$plugin_data["Description"];
        $current_version=$plugin_data['Version'];
        $new_version= $plugin_data['response']['new_version'];

        if(is_plugin_active($plugin_file))
        {
            $plugin_active="This plugin is active for your site.";
        }
        else
        {
            $plugin_active="";
        }

        $html='<table class="wp-list-table widefat plugins" style="margin-bottom:0.5rem;">
                <tbody id="the-list" data-wp-lists="list:plugin">
                <tr class="active">
                    <th class="plugin-title" style="width:4.6rem">
                        '.$icon.'
                    </th>
                    <td class="column-description desc">
                        <div class="eum-plugins-name-actions"><h4 class="eum-plugins-name" style="margin:0;">'.$name.'</h4></div>
                        <div class="plugin-description"><p>'.$description.'</p></div>
                        <div class="active second plugin-version-author-uri">
                        <div><span>Current Version </span><strong><span class="wpvivid-rollback-current-version" style="color: orange;">'.$current_version.'</span></strong> | <span>Latest Version </span><strong><span style="color: green;">'.$new_version.'</span></strong> </div>
                        <div>'.$plugin_active.'</div></div>
                    </td>

                </tr>
                </tbody>
            </table>';

        return $html;
    }

    public function get_theme_detail($them_data,$slug)
    {
        $icon= '<span class="dashicons dashicons-admin-plugins"></span>';
        if(isset($them_data['icon']))
        {
            $icon = '<img src="' . esc_url(  $them_data['icon'] ) . '" width="85" height="64" class="updates-table-screenshot" alt="" />';
        }

        $name=$them_data["Name"];
        $description=$them_data["Description"];
        $current_version=$them_data['Version'];
        $new_version= $them_data['new_version'];

        if ( get_stylesheet() === $slug )
        {
            $theme_active="This theme is active for your site.";
        }
        else
        {
            $theme_active="";
        }

        $html='<table class="wp-list-table widefat plugins" style="margin-bottom:0.5rem;">
                <tbody id="the-list" data-wp-lists="list:plugin">
                <tr class="active">
                    <th class="plugin-title" style="width:4.6rem">
                        '.$icon.'
                    </th>
                    <td class="column-description desc">
                        <div class="eum-plugins-name-actions"><h4 class="eum-plugins-name" style="margin:0;">'.$name.'</h4></div>
                        <div class="plugin-description"><p>'.$description.'</p></div>
                        <div class="active second plugin-version-author-uri">
                        <div><span>Current Version </span><strong><span class="wpvivid-rollback-current-version" style="color: orange;">'.$current_version.'</span></strong> | <span>Latest Version </span><strong><span style="color: green;">'.$new_version.'</span></strong> </div>
                        <div>'.$theme_active.'</div></div>
                    </td>

                </tr>
                </tbody>
            </table>';

        return $html;
    }

    public function get_plugins_list()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $all_plugins     = get_plugins();
            $current = get_site_transient( 'update_plugins' );
            $plugins = array();

            $plugins_auto_backup_status=get_option('wpvivid_plugins_auto_backup_status',array());
            $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());

            foreach ((array) $all_plugins as $plugin_file => $plugin_data)
            {
                if(!isset($plugin_data['Version'])||empty($plugin_data['Version']))
                {
                    continue;
                }

                if ( isset( $current->response[ $plugin_file ] ) )
                {
                    $plugins[ $plugin_file ]= $plugin_data;
                    $plugins[ $plugin_file ]['response']= (array)$current->response[ $plugin_file ];
                }
                else if( isset( $current->no_update[ $plugin_file ] ) )
                {
                    $plugins[ $plugin_file ]= $plugin_data;
                    $plugins[ $plugin_file ]['response']= (array)$current->no_update[ $plugin_file ];
                }
                else
                {
                    $plugins[ $plugin_file ]= $plugin_data;
                    $plugins[ $plugin_file ]['response']['new_version']='-';
                }

                $plugins[ $plugin_file ]['slug']=$this->get_plugin_slug($plugin_file);

                if(isset($plugins_auto_backup_status[$plugins[ $plugin_file ]['slug']]))
                {
                    $plugins[ $plugin_file ]['enable_auto_backup']= $plugins_auto_backup_status[$plugins[ $plugin_file ]['slug']];
                }
                else
                {
                    $auto_enable_new_plugin = get_option('wpvivid_auto_enable_new_plugin', false);
                    if($auto_enable_new_plugin)
                    {
                        $plugins[ $plugin_file ]['enable_auto_backup']= true;
                    }
                    else
                    {
                        $plugins[ $plugin_file ]['enable_auto_backup']= false;
                    }
                }

                $plugins[ $plugin_file ]['rollback']=$this->get_rollback_data($plugin_file);

                if(isset($plugins[ $plugin_file ]['slug']))
                {
                    if(isset($rollback_plugin_data[$plugins[ $plugin_file ]['slug']]))
                    {
                        $plugins[ $plugin_file ]['rollback_data']=$rollback_plugin_data[$plugins[ $plugin_file ]['slug']];
                    }
                    else
                    {
                        $plugins[ $plugin_file ]['rollback_data']=array();
                    }
                }
                else
                {
                    $plugins[ $plugin_file ]['rollback_data']=array();
                }
            }

            $table=new WPvivid_Rollback_Plugins_List();
            if(isset($_POST['page']))
            {
                $table->set_list($plugins,$_POST['page']);
            }
            else
            {
                $table->set_list($plugins);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
            $ret['plugins']=$plugins;
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

    public function get_themes_list()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            $current = get_site_transient( 'update_themes' );
            $themes =wp_get_themes();
            $themes_list=array();

            $themes_auto_backup_status=get_option('wpvivid_themes_auto_backup_status',array());
            $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());

            foreach ($themes as $key=>$theme)
            {
                $stylesheet=$theme->get_stylesheet();
                $them_data["name"]=$theme->display( 'Name' );
                $them_data["version"]=$theme->display( 'Version' );
                $them_data["slug"]=$key;

                if ( isset( $current->response[ $stylesheet ] ) )
                {
                    $update=(array)$current->response[ $stylesheet ];
                    $them_data["new_version"]=$update['new_version'];
                }
                else if( isset( $current->no_update[ $stylesheet ] ) )
                {
                    $update=(array)$current->no_update[ $stylesheet ];
                    $them_data["new_version"]=$update['new_version'];
                }
                else
                {
                    $them_data['new_version']='-';
                }

                $them_data['rollback']=$this->get_rollback_data($key,'themes');

                if(isset($rollback_theme_data[$key]))
                {
                    $them_data['rollback_data']=$rollback_theme_data[$key];
                }
                else
                {
                    $them_data['rollback_data']=array();
                }

                if(isset($themes_auto_backup_status[$key]))
                {
                    $them_data['enable_auto_backup']= $themes_auto_backup_status[$key];
                }
                else
                {
                    $them_data['enable_auto_backup']= false;
                }

                $themes_list[ $stylesheet ]= $them_data;
            }

            $table=new WPvivid_Themes_List();
            if(isset($_POST['page']))
            {
                $table->set_list($themes_list,$_POST['page']);
            }
            else
            {
                $table->set_list($themes_list);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
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

    public function get_core_list()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            $core_list=$this->get_core_data();
            $table=new WPvivid_Core_List();
            if(isset($_POST['page']))
            {
                $table->set_list($core_list,$_POST['page']);
            }
            else
            {
                $table->set_list($core_list);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
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

    public function download_rollback()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_REQUEST['slug']) && !empty($_REQUEST['slug']) && is_string($_REQUEST['slug']))
        {
            $slug = sanitize_key($_REQUEST['slug']);
        }
        else
        {
            die();
        }

        if (isset($_REQUEST['version']) && !empty($_REQUEST['version']) && is_string($_REQUEST['version']))
        {
            $version = sanitize_text_field($_REQUEST['version']);
        }
        else
        {
            die();
        }

        if (isset($_REQUEST['type']) && !empty($_REQUEST['type']) && is_string($_REQUEST['type']))
        {
            $type = sanitize_text_field($_REQUEST['type']);
        }
        else
        {
            die();
        }

        $package=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/'.$type.'/'.$slug.'/' . $version.'/'.$slug.'.zip';

        if(!file_exists($package))
        {
            echo $package;
            die();
        }

        if (file_exists($package))
        {
            if (session_id())
                session_write_close();

            $size = filesize($package);
            if (!headers_sent())
            {
                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($package) . '"');
                header('Cache-Control: must-revalidate');
                header('Content-Length: ' . $size);
                header('Content-Transfer-Encoding: binary');
            }

            @ini_set( 'memory_limit', '512M' );

            if ($size < 1024 * 1024 * 60) {
                ob_end_clean();
                readfile($package);
                exit;
            } else {
                ob_end_clean();
                $download_rate = 1024 * 10;
                $file = fopen($package, "r");
                while (!feof($file)) {
                    @set_time_limit(20);
                    // send the current file part to the browser
                    print fread($file, round($download_rate * 1024));
                    // flush the content to the browser
                    ob_flush();
                    flush();
                    // sleep one second
                    sleep(1);
                }
                fclose($file);
                exit;
            }
        }
    }

    public function download_core_rollback()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_REQUEST['version']) && !empty($_REQUEST['version']) && is_string($_REQUEST['version']))
        {
            $version = sanitize_text_field($_REQUEST['version']);
        }
        else
        {
            die();
        }

        $package=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core/'. $version.'/wordpress.zip';

        if(!file_exists($package))
        {
            echo $package;
            die();
        }

        if (file_exists($package))
        {
            if (session_id())
                session_write_close();

            $size = filesize($package);
            if (!headers_sent())
            {
                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($package) . '"');
                header('Cache-Control: must-revalidate');
                header('Content-Length: ' . $size);
                header('Content-Transfer-Encoding: binary');
            }

            @ini_set( 'memory_limit', '512M' );

            if ($size < 1024 * 1024 * 60) {
                ob_end_clean();
                readfile($package);
                exit;
            } else {
                ob_end_clean();
                $download_rate = 1024 * 10;
                $file = fopen($package, "r");
                while (!feof($file)) {
                    @set_time_limit(20);
                    // send the current file part to the browser
                    print fread($file, round($download_rate * 1024));
                    // flush the content to the browser
                    ob_flush();
                    flush();
                    // sleep one second
                    sleep(1);
                }
                fclose($file);
                exit;
            }
        }
    }

    public function get_rollback_list()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
            {
                $slug = sanitize_key($_POST['slug']);
            }
            else
            {
                die();
            }

            if (isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']))
            {
                $type = sanitize_text_field($_POST['type']);
            }
            else
            {
                die();
            }

            if($type=='plugins')
            {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';

                $all_plugins     = get_plugins();
                $current_plugin_file='';
                foreach ((array) $all_plugins as $plugin_file => $plugin_data)
                {
                    if($this->get_plugin_slug($plugin_file)==$slug)
                    {
                        $current_plugin_file=$plugin_file;
                        break;
                    }
                }

                $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());

                if(empty($current_plugin_file))
                {
                    $ret['result']='failed';
                    $ret['error']='plugin not found.';
                    echo json_encode($ret);
                    die();
                }

                $current = get_site_transient( 'update_plugins' );

                $plugin_data=get_plugin_data(WP_PLUGIN_DIR.'/'.$current_plugin_file);

                if ( isset( $current->response[ $current_plugin_file ] ) )
                {
                    $plugin_data['response']= (array)$current->response[ $current_plugin_file ];
                }
                else if( isset( $current->no_update[ $current_plugin_file ] ) )
                {
                    $plugin_data['response']= (array)$current->no_update[ $current_plugin_file ];
                }
                else
                {
                    $plugin_data['response']['new_version']='-';
                }

                $plugin_data['rollback']=$this->get_rollback_data($current_plugin_file);
                if(isset($rollback_plugin_data[$slug]))
                    $plugin_data['rollback_data']=$rollback_plugin_data[$slug];
                else
                    $plugin_data['rollback_data']=array();

                $rollback_list=array();
                if(!empty($plugin_data['rollback']))
                {
                    foreach ($plugin_data['rollback'] as $version=>$file)
                    {
                        $info=$this->get_rollback_file_info($slug,$version,$file);
                        $rollback['version']=$version;
                        $rollback['slug']=$slug;
                        $rollback['date']=$info['date'];
                        $rollback['size']=$info['size'];
                        $rollback_list[$version]=$rollback;
                    }
                }

                if(!empty($plugin_data['rollback_data']))
                {
                    foreach ($plugin_data['rollback_data']['version'] as $version=>$data)
                    {
                        if(!isset($rollback_list[$version]))
                        {
                            if($data['upload'])
                            {
                                $rollback['version']=$version;
                                $rollback['slug']=$slug;
                                $rollback['date']=date('M d Y h:i A', $data['file']['modified']);
                                $rollback['size']=size_format($data['file']['size'],2);
                                $rollback_list[$version]=$rollback;
                            }
                        }
                    }
                }

                usort($rollback_list, function ($a, $b)
                {
                    if ($a['version'] == $b['version'])
                    {
                        return 0;
                    }

                    if(version_compare($a['version'], $b['version'],'>'))
                    {
                        return 1;
                    }
                    else
                    {
                        return -1;
                    }
                });

                $ret['result']='success';
                $table=new WPvivid_Rollback_List();
                if(isset($_POST['page']))
                {
                    $table->set_list($rollback_list,$_POST['page']);
                }
                else
                {
                    $table->set_list($rollback_list);
                }

                $table->prepare_items();
                ob_start();
                $table->display();
                $ret['html'] = ob_get_clean();

                echo json_encode($ret);
            }
            else
            {
                $theme=wp_get_theme($slug);

                $them_data["Name"]=$theme->display( 'Name' );
                $them_data["Version"]=$theme->display( 'Version' );
                $them_data["Description"]=$theme->display( 'Description' );
                $them_data["icon"]=$theme->get_screenshot() . '?ver=' . $theme->get( 'Version' );
                $current = get_site_transient( 'update_themes' );

                $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());

                if ( isset( $current->response[ $slug ] ) )
                {
                    $update=(array)$current->response[ $slug ];
                    $them_data["new_version"]=$update['new_version'];
                    $them_data['response']= $update;
                }
                else if( isset( $current->no_update[ $slug ] ) )
                {
                    $update=(array)$current->no_update[ $slug ];
                    $them_data["new_version"]=$update['new_version'];
                    $them_data['response']= $update;
                }
                else
                {
                    $them_data['new_version']='-';
                    $them_data['response']= array();
                }

                $them_data['rollback']=$this->get_rollback_data($slug,'themes');

                if(isset($rollback_theme_data[$slug]))
                    $them_data['rollback_data']=$rollback_theme_data[$slug];
                else
                    $them_data['rollback_data']=array();

                $rollback_list=array();
                if(!empty($them_data['rollback']))
                {
                    foreach ($them_data['rollback'] as $version=>$file)
                    {
                        $info=$this->get_rollback_file_info($slug,$version,$file,'themes');
                        $rollback['version']=$version;
                        $rollback['slug']=$slug;
                        $rollback['date']=$info['date'];
                        $rollback['size']=$info['size'];
                        $rollback_list[$version]=$rollback;
                    }
                }

                if(!empty($them_data['rollback_data']))
                {
                    foreach ($them_data['rollback_data']['version'] as $version=>$data)
                    {
                        if(!isset($rollback_list[$version]))
                        {
                            if($data['upload'])
                            {
                                $rollback['version']=$version;
                                $rollback['slug']=$slug;
                                $rollback['date']=date('M d Y h:i A', $data['file']['modified']);
                                $rollback['size']=size_format($data['file']['size'],2);
                                $rollback_list[$version]=$rollback;
                            }
                        }
                    }
                }

                usort($rollback_list, function ($a, $b)
                {
                    if ($a['version'] == $b['version'])
                    {
                        return 0;
                    }

                    if(version_compare($a['version'], $b['version'],'>'))
                    {
                        return 1;
                    }
                    else
                    {
                        return -1;
                    }
                });

                $ret['result']='success';
                $table=new WPvivid_Rollback_List();
                if(isset($_POST['page']))
                {
                    $table->set_list($rollback_list,$_POST['page']);
                }
                else
                {
                    $table->set_list($rollback_list);
                }
                $table->prepare_items();
                ob_start();
                $table->display();
                $ret['html'] = ob_get_clean();

                echo json_encode($ret);
            }


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

    public function delete_rollback()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['versions']) && !empty($_POST['versions']) && is_array($_POST['versions']))
        {
            $versions=$_POST['versions'];

            if (isset($_POST['slug']) && !empty($_POST['slug']) && is_string($_POST['slug']))
            {
                $slug = sanitize_key($_POST['slug']);
            }
            else
            {
                die();
            }

            if (isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']))
            {
                $type = sanitize_text_field($_POST['type']);
            }
            else
            {
                die();
            }

            $rollback_data=array();
            if($type=="plugins")
            {
                $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
                if(isset($rollback_plugin_data[$slug]))
                    $rollback_data=$rollback_plugin_data[$slug];
            }
            else if($type=="themes")
            {
                $rollback_theme_data=get_option('wpvivid_rollback_theme_data',array());
                if(isset($rollback_theme_data[$slug]))
                    $rollback_data=$rollback_theme_data[$slug];
            }


            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/'.$type.'/'.$slug ;

            foreach ($versions as $version)
            {
                if(file_exists($path.'/'.$version.'/'.$slug.'.zip'))
                {
                    @unlink($path.'/'.$version.'/'.$slug.'.zip');
                    @rmdir($path.'/'.$version);
                }

                if(!isset($rollback_data['version'][$version]))
                    continue;

                $data=$rollback_data['version'][$version];
                if($data['upload'])
                {
                    $this->cleanup_remote_rollback($type,$slug,$version);
                }

                if($type=="plugins")
                {
                    $rollback_plugin_data=get_option('wpvivid_rollback_plugin_data',array());
                    unset($rollback_plugin_data[$slug]['version'][$version]);
                    update_option('wpvivid_rollback_plugin_data',$rollback_plugin_data,'no');
                }
                else if($type=="themes")
                {
                    $rollback_plugin_data=get_option('wpvivid_rollback_theme_data',array());
                    unset($rollback_plugin_data[$slug]['version'][$version]);
                    update_option('wpvivid_rollback_theme_data',$rollback_plugin_data,'no');
                }
            }


            $ret['result']='success';
            echo json_encode($ret);

            die();
        }
        else
        {
            die();
        }
    }

    public function delete_core_rollback()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['versions']) && !empty($_POST['versions']) && is_array($_POST['versions']))
        {
            $versions=$_POST['versions'];

            $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core' ;

            foreach ($versions as $version)
            {
                if(file_exists($path.'/'.$version.'/wordpress.zip'))
                {
                    @unlink($path.'/'.$version.'/wordpress.zip');
                    @rmdir($path.'/'.$version);
                }
            }


            $core_list=$this->get_core_data();
            $table=new WPvivid_Core_List();
            if(isset($_POST['page']))
            {
                $table->set_list($core_list,$_POST['page']);
            }
            else
            {
                $table->set_list($core_list);
            }
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();

            $ret['result']='success';
            $ret['html']=$html;
            echo json_encode($ret);

            die();
        }
        else
        {
            die();
        }
    }

    public function rollback_core()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if (isset($_POST['version']) && !empty($_POST['version']) && is_string($_POST['version']))
        {
            $version = sanitize_text_field($_POST['version']);
        }
        else
        {
            die();
        }

        $package=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core/'. $version.'/wordpress.zip';
        if(!file_exists($package))
        {
            $ret['result']='failed';
            $ret['error']="Could not find the backup. Please check whether the backup exists.";
            echo json_encode($ret);
            die();
        }

        $rollback_core['version']=$version;
        $rollback_core['update_time']=time();
        $rollback_core['restore_timeout_count']=0;
        $rollback_core['status']='ready';

        update_option('wpvivid_core_rollback_task',$rollback_core,'no');

        $ret['result']='success';
        echo json_encode($ret);
        die();
    }

    public function do_rollback_core()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        set_time_limit(300);

        $rollback_core=get_option('wpvivid_core_rollback_task',false);

        if(!isset($rollback_core['version'])||empty($rollback_core['version']))
        {
            $rollback_core['status']='error';
            $rollback_core['error']="Could not find the backup. Please check whether the backup exists.";
            update_option('wpvivid_core_rollback_task',$rollback_core,'no');

            $ret['result']='failed';
            $ret['error']="Could not find the backup. Please check whether the backup exists.";
            echo json_encode($ret);
            die();
        }

        $version=$rollback_core['version'];
        $package=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/'.'rollback/core/'. $version.'/wordpress.zip';
        if(!file_exists($package))
        {
            $rollback_core['status']='error';
            $rollback_core['error']="Could not find the backup. Please check whether the backup exists.";
            update_option('wpvivid_core_rollback_task',$rollback_core,'no');

            $ret['result']='failed';
            $ret['error']="Could not find the backup. Please check whether the backup exists.";
            echo json_encode($ret);
            die();
        }

        $rollback_core['status']='running';
        $rollback_core['update_time']=time();
        update_option('wpvivid_core_rollback_task',$rollback_core,'no');

        if (!class_exists('WPvivid_PclZip'))
            include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';

        if(!defined('PCLZIP_TEMPORARY_DIR'))
            define(PCLZIP_TEMPORARY_DIR,dirname($package));

        $root_path = $this->transfer_path(ABSPATH);

        $root_path = rtrim($root_path, '/');
        $root_path = rtrim($root_path, DIRECTORY_SEPARATOR);

        $archive = new WPvivid_PclZip($package);
        $zip_ret = $archive->extract(WPVIVID_PCLZIP_OPT_PATH, $root_path,WPVIVID_PCLZIP_OPT_REPLACE_NEWER,WPVIVID_PCLZIP_CB_PRE_EXTRACT,'wpvivid_pro_function_pre_core_extract_callback',WPVIVID_PCLZIP_OPT_TEMP_FILE_THRESHOLD,16);
        if(!$zip_ret)
        {
            $ret['result']='failed';
            $ret['error'] = $archive->errorInfo(true);
            $rollback_core['status']='error';
            $rollback_core['error']=$ret['error'];
            update_option('wpvivid_core_rollback_task',$rollback_core,'no');

            echo json_encode($ret);
            die();
        }
        else
        {
            $ret['result']='success';
            $rollback_core['status']='completed';
            update_option('wpvivid_core_rollback_task',$rollback_core,'no');
            echo json_encode($ret);
            die();
        }
    }

    public function get_rollback_core_progress()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );

        $rollback_task=get_option('wpvivid_core_rollback_task',false);

        if($rollback_task==false)
        {
            $ret['result']='failed';
            $ret['error']='restore task has error';
            $ret['test']=$rollback_task;
            echo json_encode($ret);
            die();
        }

        $ret['test']=$rollback_task;
        if($rollback_task['status']=='error')
        {
            $ret['result']='failed';
            $ret['error']=$rollback_task['error'];
            echo json_encode($ret);
            die();
        }
        else if($rollback_task['status']=='ready')
        {
            $ret['result']='success';
            $ret['status']='running';
            echo json_encode($ret);
            die();
        }
        else if($rollback_task['status']=='running')
        {
            if(time()-$rollback_task['update_time']>300)
            {
                $ret['result']='failed';
                $ret['error']='restore timeout';
                echo json_encode($ret);
                die();
            }
            else
            {
                $ret['result']='success';
                $ret['status']='running';
                echo json_encode($ret);
                die();
            }
        }
        else if($rollback_task['status']=='completed')
        {
            $ret['result']='success';
            $ret['status']='completed';
            echo json_encode($ret);
            die();
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='restore task has error';
            $ret['test']=$rollback_task;
            echo json_encode($ret);
            die();
        }
    }

    public function _list_tasks($task_id)
    {
        $ret=array();
        $list_tasks=array();
        $task=WPvivid_taskmanager::get_task($task_id);
        if($task!==false)
        {
            if($task['action']=='backup' || $task['action']=='backup_remote')
            {
                $backup=new WPvivid_Backup_Task($task['id']);
                $list_tasks[$task['id']]=$backup->get_backup_task_info($task['id']);

                $list_tasks[$task['id']]['progress_html'] = '<div class="action-progress-bar" id="wpvivid_action_progress_bar">
                                                <div class="action-progress-bar-percent" id="wpvivid_action_progress_bar_percent" style="height:24px;width:' . $list_tasks[$task['id']]['task_info']['backup_percent'] . '"></div>
                                             </div>                                                    
                                             <div style="margin-left:10px; float: left; width:100%;"><p id="wpvivid_current_doing">' . $list_tasks[$task['id']]['task_info']['descript'] . '</p></div>
                                             <div style="clear: both;"></div>';
            }
        }

        $ret['backup']['result']='success';
        $ret['backup']['data']=$list_tasks;
        return $ret;
    }

    public function prepare_rollback_file()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        set_time_limit(120);

        if (isset($_REQUEST['slug']) && !empty($_REQUEST['slug']) && is_string($_REQUEST['slug']))
        {
            $slug = sanitize_text_field($_REQUEST['slug']);
        }
        else
        {
            die();
        }

        if (isset($_REQUEST['version']) && !empty($_REQUEST['version']) && is_string($_REQUEST['version']))
        {
            $version = sanitize_text_field($_REQUEST['version']);
        }
        else
        {
            die();
        }

        if (isset($_REQUEST['type']) && !empty($_REQUEST['type']) && is_string($_REQUEST['type']))
        {
            $type = sanitize_text_field($_REQUEST['type']);
        }
        else
        {
            die();
        }

        $path=WPvivid_Custom_Interface_addon::wpvivid_get_local_backup_abspath().'/';
        $package='rollback/'.$type.'/'.$slug.'/' . $version.'/'.$slug.'.zip';
        if(file_exists($path.$package))
        {
            $ret['result']='success';
            $ret['finished']=1;
            echo json_encode($ret);
            die();
        }
        else
        {
            if($type=='plugins')
            {
                $rollback_data=get_option('wpvivid_rollback_plugin_data',array());
            }
            else
            {
                $rollback_data=get_option('wpvivid_rollback_theme_data',array());
            }

            if(!isset($rollback_data[$slug])||!isset($rollback_data[$slug]['version'][$version]))
            {
                $ret['result']='failed';
                $ret['error']='file not found';
                echo json_encode($ret);
                die();
            }

            $data=$rollback_data[$slug]['version'][$version];

            if($data['upload'])
            {
                $rollback_remote = get_option('wpvivid_rollback_remote', 0);
                if($rollback_remote)
                {
                    $remote_id = get_option('wpvivid_rollback_remote_id', 0);
                    $remoteslist=WPvivid_Setting::get_all_remote_options();
                    if(isset($remoteslist[$remote_id]))
                    {
                        $remote_option = $remoteslist[$remote_id];
                    }
                    else
                    {
                        $ret['result']='failed';
                        $ret['error']='file not found';
                        echo json_encode($ret);
                        die();
                    }

                    $download_info['package']=$package;
                    $download_info['file_name']=$slug.'.zip';
                    $download_info['size']=$data['file']['size'];
                    $download_info['type']=$type;
                    $download_info['slug']=$slug;
                    $download_info['version']=$version;
                    $tmp_name= uniqid('wpvividtmp-');

                    $download_info['local_path']=$path.'rollback/'.$type.'/'.$slug.'/' . $version.'/'.$tmp_name;
                    $download_info['root_path']=$path.'rollback/'.$type.'/'.$slug.'/' . $version.'/';
                    if(!file_exists($download_info['root_path']))
                    {
                        mkdir($download_info['root_path'], 0777, true);
                    }
                    $download_info['offset']=0;
                    $download_info['finished']=0;
                    $download_info['status']['str']='running';
                    $download_info['status']['error']='';
                    update_option("wpvivid_rollback_download_info",$download_info,'no');

                    global $wpvivid_plugin;

                    $remote_collection=new WPvivid_Remote_collection_addon();
                    $remote=$remote_collection->get_remote($remote_option);
                    $ret=$remote->download_rollback($download_info);
                    if($ret['result']=='success')
                    {
                        $result['result']='success';
                        $result['finished']=$ret['finished'];
                        $download_info['status']['str']='ready';
                        $download_info['offset']=$ret['offset'];
                        $download_info['finished']=$ret['finished'];
                        update_option("wpvivid_rollback_download_info",$download_info,'no');

                        echo json_encode($result);
                        die();
                    }
                    else
                    {
                        $download_info['status']['str']='error';
                        $download_info['status']['error']=$ret['error'];
                        update_option("wpvivid_rollback_download_info",$download_info,'no');

                        echo json_encode($ret);
                        die();
                    }
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='file not found';
                    echo json_encode($ret);
                    die();
                }
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='file not found';
                echo json_encode($ret);
                die();
            }
        }
    }

    public function get_rollback_download_progress()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        $download_info=get_option("wpvivid_rollback_download_info",array());
        if(empty($download_info))
        {
            $ret['result']='failed';
            $ret['error']='task not found';
            echo json_encode($ret);
            die();
        }

        if($download_info['status']['str']=='error')
        {
            $ret['result']='failed';
            $ret['error']=$download_info['status']['error'];
            echo json_encode($ret);
            die();
        }

        $ret['result']='success';
        $finished=$download_info['finished'];

        if($finished)
        {
            $ret['finished']=true;
            echo json_encode($ret);
            die();
        }
        else
        {
            $ret['finished']=false;
        }

        $ret['set_timeout']=true;

        if($download_info['status']['str']=='ready')
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='file not found';
                echo json_encode($ret);
                die();
            }

            $download_info=get_option("wpvivid_rollback_download_info",array());
            $download_info['status']['str']='running';
            $download_info['status']['error']='';
            update_option("wpvivid_rollback_download_info",$download_info,'no');

            global $wpvivid_plugin;

            $remote_collection=new WPvivid_Remote_collection_addon();
            $remote=$remote_collection->get_remote($remote_option);
            $ret=$remote->download_rollback($download_info);
            if($ret['result']=='success')
            {
                $result['result']='success';
                $result['finished']=$ret['finished'];
                $download_info['status']['str']='ready';
                $download_info['offset']=$ret['offset'];
                $download_info['finished']=$ret['finished'];
                update_option("wpvivid_rollback_download_info",$download_info,'no');

                echo json_encode($result);
                die();
            }
            else
            {
                $download_info['status']['str']='error';
                $download_info['status']['error']=$ret['error'];
                update_option("wpvivid_rollback_download_info",$download_info,'no');

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

    public function rollback_plugins_sync()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='remote storage not found';
                echo json_encode($ret);
                die();
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='remote storage not found';
            echo json_encode($ret);
            die();
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $result=$remote->scan_rollback("plugins");
        if($result['result']=='success')
        {
            $rollback_data=array();
            if(!empty($result['rollback']))
            {
                foreach ($result['rollback'] as $slug)
                {
                    $rollback_data[$slug]=array();
                }
            }
            update_option("wpvivid_tmp_rollback_plugin_data",$rollback_data,'no');

            $ret['result']='success';
            $ret['test']=$result['rollback'];
            echo json_encode($ret);
            die();
        }
        else
        {
            echo json_encode($result);
            die();
        }
    }

    public function rollback_plugins_sync_continue()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='remote storage not found';
                echo json_encode($ret);
                die();
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='remote storage not found';
            echo json_encode($ret);
            die();
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $tmp_rollback_data=get_option('wpvivid_tmp_rollback_plugin_data');
        if(empty($tmp_rollback_data))
        {
            update_option("wpvivid_rollback_plugin_data",array(),'no');
            $ret['result']='success';
            echo json_encode($ret);
            die();
        }

        $time_limit = 21;
        $start_time = time();

        foreach ($tmp_rollback_data as $slug=>$data)
        {
            if(!empty($data))
            {
                continue;
            }

            $ret=$remote->get_rollback_data("plugins",$slug);
            if($ret['result']=='success')
            {
                $tmp_rollback_data[$slug]=$ret['data'];
            }
            else
            {
                $tmp_rollback_data[$slug]=array();
            }

            update_option('wpvivid_tmp_rollback_plugin_data',$tmp_rollback_data,'no');

            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                $ret['result']='success';
                $ret['finished']=false;
                echo json_encode($ret);
                die();
            }
        }

        $rollback_data=get_option("wpvivid_tmp_rollback_plugin_data",array());
        update_option("wpvivid_rollback_plugin_data",$rollback_data,'no');

        $ret['result']='success';
        $ret['finished']=true;
        $ret['test']=$rollback_data;
        echo json_encode($ret);
        die();
    }

    public function rollback_themes_sync()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='remote storage not found';
                echo json_encode($ret);
                die();
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='remote storage not found';
            echo json_encode($ret);
            die();
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $result=$remote->scan_rollback("themes");
        if($result['result']=='success')
        {
            $rollback_data=array();
            if(!empty($result['rollback']))
            {
                foreach ($result['rollback'] as $slug)
                {
                    $rollback_data[$slug]=array();
                }
            }
            update_option("wpvivid_tmp_rollback_themes_data",$rollback_data,'no');

            $ret['result']='success';
            $ret['test']=$result['rollback'];
            echo json_encode($ret);
            die();
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='remote storage not found';
            echo json_encode($ret);
            die();
        }
    }

    public function rollback_themes_sync_continue()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        $rollback_remote = get_option('wpvivid_rollback_remote', 0);
        if($rollback_remote)
        {
            $remote_id = get_option('wpvivid_rollback_remote_id', 0);
            $remoteslist=WPvivid_Setting::get_all_remote_options();
            if(isset($remoteslist[$remote_id]))
            {
                $remote_option = $remoteslist[$remote_id];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='remote storage not found';
                echo json_encode($ret);
                die();
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='remote storage not found';
            echo json_encode($ret);
            die();
        }

        global $wpvivid_plugin;

        $remote_collection=new WPvivid_Remote_collection_addon();
        $remote=$remote_collection->get_remote($remote_option);

        $tmp_rollback_data=get_option('wpvivid_tmp_rollback_themes_data');
        if(empty($tmp_rollback_data))
        {
            update_option("wpvivid_rollback_theme_data",array(),'no');
            $ret['result']='success';
            echo json_encode($ret);
            die();
        }

        $time_limit = 21;
        $start_time = time();

        foreach ($tmp_rollback_data as $slug=>$data)
        {
            if(!empty($data))
            {
                continue;
            }

            $ret=$remote->get_rollback_data("themes",$slug);
            if($ret['result']=='success')
            {
                $tmp_rollback_data[$slug]=$ret['data'];
            }
            else
            {
                $tmp_rollback_data[$slug]=array();
            }

            update_option('wpvivid_tmp_rollback_themes_data',$tmp_rollback_data,'no');

            $time_taken = microtime(true) - $start_time;
            if($time_taken >= $time_limit)
            {
                $ret['result']='success';
                $ret['finished']=false;
                echo json_encode($ret);
                die();
            }
        }

        $rollback_data=get_option("wpvivid_tmp_rollback_themes_data",array());
        update_option("wpvivid_rollback_theme_data",$rollback_data,'no');

        $ret['result']='success';
        $ret['finished']=true;
        $ret['test']=$rollback_data;
        echo json_encode($ret);
        die();
    }
}