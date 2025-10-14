<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Admin_load: yes
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

class WPvivid_New_Backup_List extends WP_List_Table
{
    public $page_num;
    public $backup_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'backup',
                'screen' => 'backup'
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
        $columns['wpvivid_date2'] = __( 'Date', 'wpvivid' );
        $columns['wpvivid_content2'] =__( 'Content', 'wpvivid'  );
        $columns['wpvivid_storage'] =__( 'Storage', 'wpvivid'  );
        $columns['wpvivid_size2'] =__( 'Size', 'wpvivid'  );
        $columns['wpvivid_action2'] = __( 'Actions', 'wpvivid' );
        $columns['wpvivid_comment2'] = __( 'Comment', 'wpvivid'  );
        return $columns;
    }

    public function column_cb( $backup )
    {
        $html='<input type="checkbox"/>';
        echo $html;
    }

    public function _column_wpvivid_date2( $backup )
    {
        $type_display = $backup['type'];
        if($type_display === 'Migrate')
        {
            $type_display = 'Migration';
        }

        if (empty($backup['lock']))
        {
            $lock_class = 'dashicons-unlock';
        }
        else
        {
            if ($backup['lock'] == 0)
            {
                $lock_class = 'dashicons-unlock';
            }
            else
            {
                $lock_class = 'dashicons-lock';
            }
        }

        $backups_lock=WPvivid_Setting::get_option('wpvivid_remote_backups_lock');
        if(isset($backups_lock[$backup['id']]))
        {
            $lock_class = 'dashicons-lock';
        }

        //$offset=get_option('gmt_offset');
        //$localtime = $backup['create_time'] + $offset * 60 * 60;
        $localtime = $backup['create_time'];

        $offset=get_option('gmt_offset');
        $utc_time = $backup['create_time'] - $offset * 60 * 60;

        if(isset($backup['log']))
        {
            $log_name = basename($backup['log']);
        }
        else
        {
            $log_name = '';
        }

        $html='<td class="tablelistcolumn">
                    <div class="backuptime"  title="">
						<span class="dashicons '.$lock_class.' wpvivid-dashicons-blue wpvivid-lock" style="cursor:pointer;"></span>																
						<span>'.date('M-d-Y H:i', $localtime).'</span>
						<span style="margin:0 0 0 5px; opacity: 0.5;">|</span>
						<span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey" style="cursor:pointer;" title="Backup Log" onclick="wpvivid_backup_open_log(\''.$log_name.'\');"></span>
						<span style="margin:0 5px 0 0; opacity: 0.5;">|</span> <span><strong>Type: </strong></span>
						<span><strong>' . $type_display . '</strong></span>
					</div>	                 
                </td>';
        echo $html;
    }

    public function _column_wpvivid_content2( $backup )
    {
        $content=$this->get_backup_content_ex($backup);

        echo '<td>';
        echo '    <div>';
        echo '         <span>';

        if( $content['files'])
        {
            echo '                <span class="dashicons dashicons-open-folder wpvivid-dashicons-orange" title="Backup Wordpress Files"></span>';
        }
        else if( $content['custom'])
        {
            echo '                <span class="dashicons dashicons-open-folder wpvivid-dashicons-orange" title="Backup Wordpress Files"></span>';
        }
        else
        {
            echo "                <span class='dashicons dashicons-open-folder wpvivid-dashicons-grey' title='Backup Wordpress Files'></span>";
        }

        if( $content['db'])
        {
            echo "                <span class='dashicons dashicons-database wpvivid-dashicons-blue' title='Backup Wordpresss DB'></span>";
        }
        else
        {
            echo '                <span class="dashicons dashicons-database wpvivid-dashicons-grey" title="Backup Wordpresss DB"></span>';
        }
        echo '         </span>';
        echo '    </div>';
        echo ' </td>';
    }

    public function _column_wpvivid_storage( $backup )
    {
        echo "<td>";
        if($this->is_localhost($backup))
        {
            echo "<span><span class='dashicons dashicons-desktop'></span><span>Localhost</span></span>";
        }
        else
        {
            $icon=$this->get_storage_icon($backup);
            echo $icon;
        }
        echo "</td>";
    }

    public function _column_wpvivid_size2( $backup )
    {
        $size=0;
        foreach ($backup['backup']['files'] as $file)
        {
            $size+=$file['size'];
        }
        $size=size_format($size,2);
        $html='<td class="tablelistcolumn">
                    <div>'.$size.'</div>
                </td>';
        echo $html;
    }

    public function _column_wpvivid_action2( $backup )
    {
        if($this->is_localhost($backup))
        {
            $can_download=apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-download-localhost-backup');
        }
        else
        {
            $can_download=apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-download-remote-backup');
        }

        if($can_download )
        {
            $html='<td class="tablelistcolumn" style="min-width:100px;">
                    <div class="wpvivid-download">
                        <div style="cursor:pointer;">
                            <span class="dashicons dashicons-visibility wpvivid-dashicons-grey wpvivid-view-backup" title="View details"></span>
							<span class="dashicons dashicons-download wpvivid-dashicons-grey wpvivid-view-backup" title="Prepare to download the backup"></span>
							<span class="dashicons dashicons-update-alt wpvivid-dashicons-grey wpvivid-restore" title="Restore"></span>
							<span class="dashicons dashicons-trash wpvivid-dashicons-grey backuplist-delete-backup" title="Delete"></span>
                        </div>
                    </div>
                </td>';
            echo $html;
        }
        else
        {
            $html='<td class="tablelistcolumn" style="min-width:100px;">
                    <div class="wpvivid-download">
                        <div style="cursor:pointer;">                           
							<span class="dashicons dashicons-trash wpvivid-dashicons-grey backuplist-delete-backup" title="Delete"></span>
                        </div>
                    </div>
                </td>';
            echo $html;
        }
    }

    public function _column_wpvivid_comment2( $backup )
    {
        if(isset($backup['backup_prefix']) && !empty($backup['backup_prefix']))
        {
            $backup_prefix = $backup['backup_prefix'];
        }
        else{
            $backup_prefix = 'N/A';
        }
        $html='<td class="tablelistcolumn">
                    <div>'.$backup_prefix.'</div>
                </td>';
        echo $html;
    }

    public function set_backup_list($backup_list,$page_num=1)
    {
        $this->backup_list=$backup_list;
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

        if(empty($this->backup_list))
        {
            $total_items=0;
        }
        else
        {
            $total_items =sizeof($this->backup_list);
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
        return !empty($this->backup_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->backup_list);
    }

    public function get_backup_content_ex($backup)
    {
        $content['db']=false;
        $content['files']=false;
        $content['custom']=false;

        if(isset($backup['backup_info'])&&!empty($backup['backup_info']))
        {
            foreach ($backup['backup_info']['types'] as $type=>$data)
            {
                if($type==='Database')
                {
                    $content['db']=true;
                }
                else if($type==='Additional Databases')
                {
                    $content['db']=true;
                }
                else if($type==='Others')
                {
                    $content['custom']=true;
                }
                else if($type==='themes' || $type==='plugins' || $type==='uploads' || $type==='wp-content' || $type==='Wordpress Core')
                {
                    $content['files']=true;
                }
            }


            return $content;
        }

        $has_db = false;
        $has_file = false;
        $has_custom = false;
        $type_list = array();
        $ismerge = false;

        if(isset($backup['backup']['files']))
        {
            foreach ($backup['backup']['files'] as $key => $value)
            {
                $file_name = $value['file_name'];
                if(WPvivid_backup_pro_function::is_wpvivid_db_backup($file_name))
                {
                    $has_db = true;
                    if(!in_array('Database', $type_list))
                    {
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
                    $has_custom = true;
                    if(!in_array('Additional Folder', $type_list)) {
                        $type_list[] = 'Additional Folder';
                    }
                }
                else if(WPvivid_backup_pro_function::is_wpvivid_additional_db_backup($file_name))
                {
                    $has_file = true;
                    if(!in_array('Additional Databases', $type_list)) {
                        $type_list[] = 'Additional Databases';
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
            $backup_item = new WPvivid_New_Backup_Item($backup);
            $files_info=array();
            foreach ($backup['backup']['files'] as $file)
            {
                $file_name = $file['file_name'];
                $files_info[$file_name]=$backup_item->get_file_info($file_name);
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
                        $has_custom = true;
                        if(!in_array('Additional Folder', $type_list))
                        {
                            $type_list[] = 'Additional Folder';
                        }
                    }
                    if($backup_content === 'additional_databases')
                    {
                        $has_file = true;
                        if(!in_array('Additional Databases', $type_list))
                        {
                            $type_list[] = 'Additional Databases';
                        }
                    }
                }
            }
        }

        $content['db']=$has_db;
        $content['files']=$has_file;
        $content['custom']=$has_custom;

        return $content;
    }

    public function is_localhost($backup)
    {
        if(empty($backup['remote']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function get_storage_icon($backup)
    {
        $icon="";
        if(sizeof($backup['remote'])>1)
        {
            $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
            $icon= "<span>";
            foreach ($backup['remote'] as $remote)
            {
                if($remote['type']=='amazons3')
                {
                    $icon.="<img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Amazon'/>";
                }
                else if($remote['type']=='b2')
                {
                    $icon.="<img src='$assets_url/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Backblaze'/>";
                }
                else if($remote['type']=='dropbox')
                {
                    $icon.="<img src='$assets_url/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Dropbox'/>";
                }
                else if($remote['type']=='ftp')
                {
                    $icon.="<img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='FTP'/>";
                }
                else if($remote['type']=='ftp2')
                {
                    $icon.="<img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='FTP'/>";
                }
                else if($remote['type']=='googledrive')
                {
                    $icon.="<img src='$assets_url/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='GoogleDrive'/>";
                }
                else if($remote['type']=='nextcloud')
                {
                    $icon.="<img src='$assets_url/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Nextcloud'/>";
                }
                else if($remote['type']=='onedrive')
                {
                    $icon.="<img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='OneDrive'/>";
                }
                else if($remote['type']=='onedrive_shared')
                {
                    $icon.="<img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='OneDrive Shared Drives'/>";
                }
                else if($remote['type']=='pCloud')
                {
                    $icon.="<img src='$assets_url/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='pCloud'/>";
                }
                else if($remote['type']=='s3compat')
                {
                    $icon.="<img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='S3 Compatible Storage'/>";
                }
                else if($remote['type']=='sftp')
                {
                    $icon.="<img src='$assets_url/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='sFTP'/>";
                }
                else if($remote['type']=='wasabi')
                {
                    $icon.="<img src='$assets_url/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Wasabi'/>";
                }
                else if($remote['type']=='webdav')
                {
                    $icon.="<img src='$assets_url/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='WebDav'/>";
                }
            }
            $icon.= "</span>";
        }
        else
        {
            $remote=array_shift($backup['remote']);
            $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
            if($remote['type']=='amazons3')
            {
                $icon="<span><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Amazon S3</span></span>";
            }
            else if($remote['type']=='b2')
            {
                $icon="<span><img src='$assets_url/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Backblaze</span></span>";
            }
            else if($remote['type']=='dropbox')
            {
                $icon="<span><img src='$assets_url/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Dropbox</span></span>";
            }
            else if($remote['type']=='ftp')
            {
                $icon="<span><img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>FTP</span></span>";
            }
            else if($remote['type']=='ftp2')
            {
                $icon="<span><img src='$assets_url/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>FTP</span></span>";
            }
            else if($remote['type']=='googledrive')
            {
                $icon="<span><img src='$assets_url/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>GoogleDrive</span></span>";
            }
            else if($remote['type']=='nextcloud')
            {
                $icon="<span><img src='$assets_url/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Nextcloud</span></span>";
            }
            else if($remote['type']=='onedrive')
            {
                $icon="<span><img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>OneDrive</span></span>";
            }
            else if($remote['type']=='onedrive_shared')
            {
                $icon="<span><img src='$assets_url/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>OneDrive Shared Drives</span></span>";            }
            else if($remote['type']=='pCloud')
            {
                $icon="<span><img src='$assets_url/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>pCloud</span></span>";
            }
            else if($remote['type']=='s3compat')
            {
                $icon="<span><img src='$assets_url/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>S3 Compatible Storage</span></span>";
            }
            else if($remote['type']=='sftp')
            {
                $icon="<span><img src='$assets_url/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>sFTP</span></span>";
            }
            else if($remote['type']=='wasabi')
            {
                $icon="<span><img src='$assets_url/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Wasabi</span></span>";
            }
            else if($remote['type']=='webdav')
            {
                $icon="<span><img src='$assets_url/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>WebDav</span></span>";
            }
        }
        return $icon;
    }

    private function _display_rows($backup_list)
    {
        $page=$this->get_pagenum();

        $page_backup_list=array();
        $temp_page_backup_list=array();

        if(empty($backup_list))
        {
            return;
        }

        foreach ( $backup_list as $key=>$backup)
        {
            $page_backup_list[$key]=$backup;
        }

        $count=0;
        while ( $count<$page )
        {
            $temp_page_backup_list = array_splice( $page_backup_list, 0, 30);
            $count++;
        }

        foreach ( $temp_page_backup_list as $key=>$backup)
        {
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
        <tr style="<?php echo $row_style?>" class='wpvivid-backup-row <?php echo $class?>' id="<?php echo $backup['id'];?>">
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
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>

                <br class="clear" />
            </div>
            <?php
        }
    }
}

class WPvivid_Incremental_Files_List extends WP_List_Table
{
    public $page_num;
    public $incremental_data;
    public $backup_id;

    public function __construct($args = array())
    {
        parent::__construct(
            array(
                'plural' => 'files',
                'screen' => 'files'
            )
        );
    }

    protected function get_table_classes()
    {
        return array('widefat striped');
    }

    public function print_column_headers($with_id = true)
    {
        list($columns, $hidden, $sortable, $primary) = $this->get_column_info();

        if (!empty($columns['cb'])) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox"/>';
            $cb_counter++;
        }

        foreach ($columns as $column_key => $column_display_name) {
            $class = array('manage-column', "column-$column_key");

            if (in_array($column_key, $hidden)) {
                $class[] = 'hidden';
            }

            if ($column_key === $primary) {
                $class[] = 'column-primary';
            }

            if ($column_key === 'cb') {
                $class[] = 'check-column';
            }

            $tag = ('cb' === $column_key) ? 'td' : 'th';
            $scope = ('th' === $tag) ? 'scope="col"' : '';
            $id = $with_id ? "id='$column_key'" : '';

            if (!empty($class)) {
                $class = "class='" . join(' ', $class) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['cb'] = __('cb', 'wpvivid');
        $columns['wpvivid_i_date'] = __('Date', 'wpvivid');
        $columns['wpvivid_i_type'] = __('Type', 'wpvivid');
        $columns['wpvivid_file_name'] = __('File Name', 'wpvivid');
        $columns['wpvivid_i_file_size'] = __('Size', 'wpvivid');
        $columns['wpvivid_download_action'] = __('Actions', 'wpvivid');
        return $columns;
    }

    public function column_cb($file)
    {
        $html = '<input type="checkbox"/>';
        echo $html;
    }

    public function _column_wpvivid_i_date($file)
    {
        echo "<td class=\"tablelistcolumn\">			
				<span>".date("F-d-Y H:i",$file['date'])."</span>
			</td>";
    }

    public function _column_wpvivid_i_type($file)
    {
        if($file['version']>0)
        {
            $type="Incremental";
            echo "<td class=\"tablelistcolumn\">			
				  <span class=\"dashicons dashicons-star-half wpvivid-dashicons-blue\"></span><span>$type</span>
			  </td>";
        }
        else
        {
            $type="Full Backup";
            echo "<td class=\"tablelistcolumn\">			
				  <span class=\"dashicons dashicons-star-filled wpvivid-dashicons-green\"></span><span>$type</span>
			  </td>";
        }

    }

    public function _column_wpvivid_file_name($file)
    {
        $file_name=$file['file_name'];

        $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
        echo "<td class=\"tablelistcolumn\">
                <span>
                    <img src='$assets_url/zip.png' style='vertical-align:middle;width:1rem;'>
                </span>
                <span class=\"wpvivid-file-name\">$file_name</span>
            </td>";
    }

    public function _column_wpvivid_i_file_size($file)
    {
        $size=size_format($file['size'],2);

        echo "<td class=\"tablelistcolumn\">";
        echo "<span>" . size_format($file['size'],2) . "</span>";
        echo '</td>';
    }

    public function _column_wpvivid_download_action($file)
    {
        echo '<td class="tablelistcolumn">
					<div style="cursor:pointer;">		
						<span class="dashicons dashicons-download wpvivid-dashicons-grey wpvivid-download" title="Download"></span>					
					</div>
				</td>';
    }

    public function set_files_list($incremental_data,$backup_id,$page_num=1)
    {
        $this->incremental_data=$incremental_data;
        $this->backup_id=$backup_id;
        $this->page_num=$page_num;
    }

    public function get_files()
    {
        $files=array();

        foreach ($this->incremental_data['incremental_backup_versions'] as $version=>$version_data)
        {
            foreach ($version_data['files'] as $file)
            {
                $file_data['file_name']=$file['file_name'];
                $file_data['size']=$file['size'];
                $file_data['date']=$version_data['backup_time'];
                $file_data['version']=$version_data['version'];
                $files[]=$file_data;
            }
        }

        return $files;
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

        $total_items =sizeof($this->get_files());

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 30,
            )
        );
    }

    public function has_items()
    {
        $files=$this->get_files();
        return !empty($files);
    }

    public function display_rows()
    {
        $this->_display_rows();
    }

    private function _display_rows()
    {
        $files=$this->get_files();

        $page=$this->get_pagenum();

        $page_file_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_file_list = array_splice( $files, 0, 30);
            $count++;
        }
        foreach ( $page_file_list as $file)
        {
            $this->single_row($file);
        }
    }

    public function single_row($file)
    {
        ?>
        <tr data-filename="<?php echo $file['file_name']?>" type="common">
            <?php $this->single_row_columns( $file ); ?>
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
                <label for="wpvivid_select_bulk_download" class="screen-reader-text">Select bulk action</label>
                <select id="wpvivid_select_bulk_download">
                    <option value="download">Download</option>
                </select>
                <input type="submit" class="button action wpvivid-select-bulk wpvivid_bulk_download2" value="Apply">
                <?php
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
                <label for="wpvivid_select_bulk_download" class="screen-reader-text">Select bulk action</label>
                <select id="wpvivid_select_bulk_download">
                    <option value="download">Download</option>
                </select>
                <input type="submit" class="button action wpvivid-select-bulk wpvivid_bulk_download2" value="Apply">
                <br class="clear" />
            </div>
            <?php
        }
    }

    public function display()
    {
        $singular = $this->_args['singular'];

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" data-id="<?php echo $this->backup_id?>">
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
}

class WPvivid_Types_Files_List
{
    public $page_num;
    public $types;
    public $backup_id;
    public $_pagination_args;

    public function __construct($types,$backup_id)
    {
        $this->types=$types;
        $this->backup_id=$backup_id;
    }

    public function display()
    {
        ?>
        <table class="wp-list-table wp-list-table widefat plugins striped" data-id="<?php echo $this->backup_id?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>
            <tbody id="the-list">
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>
            <tfoot>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </tfoot>
        </table>
        <?php
    }

    public function print_column_headers()
    {
        ?>
        <td id="cb" class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
            <input id="cb-select-all-1" type="checkbox"></td>
        <th scope="col" id="wpvivid_content" class="manage-column column-wpvivid_content column-primary">Content</th>
        <th scope="col" id="wpvivid_file_name" class="manage-column column-wpvivid_file_name">File Name</th>
        <th scope="col" id="wpvivid_file_size" class="manage-column column-wpvivid_file_size">Size</th>
        <th scope="col" id="wpvivid_download_action" class="manage-column column-wpvivid_download_action">Actions</th>
        <?php
    }

    public function display_rows_or_placeholder()
    {
        if ( $this->has_items() )
        {
            $this->display_rows();
        }
        else
        {
            echo '<tr class="no-items"><td class="colspanchange" colspan="5">';
            _e( 'No items found.' );
            echo '</td></tr>';
        }
    }

    public function display_rows()
    {
        foreach ($this->types as $type=>$data)
        {
            if($type=='All' || !isset($data['files']))
                continue;

            $this->main_type_tr($type,$data['files']);

            $this->sub_type_trs($type,$data['files']);
        }
    }

    public function has_items()
    {
        return !empty($this->types);
    }

    public function main_type_tr($type,&$files)
    {
        $total_size = 0;
        foreach ($files as $file)
        {
            $total_size += $file['size'];
        }
        $size=size_format($total_size,2);

        $file_count=count($files);
        $file_data=array_shift($files);
        $file_name=$file_data['file_name'];

        $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
        ?>
        <tr data-type="<?php echo $type?>" data-filename="<?php echo $file_name?>" data-total-size="<?php echo $total_size ?>" data-file-size="<?php echo $file_data['size'] ?>">
            <th scope="row" class="check-column"><input type="checkbox" checked></th>
            <td class="tablelistcolumn">
                <label>
                    <?php
                    if($file_count>1)
                    {
                        echo '<span class="dashicons dashicons-plus-alt2 wpvivid-dashicons-grey wpvivid-expand" style="cursor:pointer;font-size:1rem;margin-top:0.2rem;"></span>';
                    }

                    if ($type == 'Database' || $type == 'Additional Databases')
                    {
                        echo "<span class='dashicons dashicons-database-view wpvivid-dashicons-blue'></span><span>" . $type . "</span>";

                    }
                    else
                    {
                        echo '<span class="dashicons dashicons-open-folder wpvivid-dashicons-orange"></span><span>'.$type . '</span>';
                    }
                    ?>

                </label>
            </td>
            <td class="tablelistcolumn">
                <span>
                    <img src='<?php echo $assets_url?>/zip.png' style='vertical-align:middle;width:1rem;'>
                </span>
                <span class="wpvivid-file-name"><?php echo $file_name?></span>
            </td>
            <td class="tablelistcolumn">
                <div class="size-placeholder"><?php echo $size?></div>
            </td>
            <td class="tablelistcolumn">
                <div style="cursor:pointer;">
                    <span class="dashicons dashicons-download wpvivid-dashicons-grey wpvivid-download" title="Download"></span>
                </div>
            </td>
        </tr>
        <?php
    }

    public function sub_type_trs($type,$files)
    {
        if(!empty($files))
        {
            $assets_url=WPVIVID_BACKUP_PRO_PLUGIN_URL . 'includes/display/images';
            foreach ($files as $file_data)
            {
                $file_name=$file_data['file_name'];
                $size=size_format($file_data['size'],2);
                ?>
                <tr class="wpvivid-sub-tr" data-ptype="<?php echo $type?>" data-filename="<?php echo $file_name?>" style="display: none">
                    <th scope="row" class="check-column"></th>
                    <td class="tablelistcolumn">
                    </td>
                    <td class="tablelistcolumn">
                        <span>
                            <img src='<?php echo $assets_url?>/zip.png' style='vertical-align:middle;width:1rem;'>
                        </span>
                        <span><?php echo $file_name?></span>
                    </td>
                    <td class="tablelistcolumn">
                        <div><?php echo $size?></div>
                    </td>
                    <td class="tablelistcolumn">
                        <div style="cursor:pointer;">
                            <span class="dashicons dashicons-download wpvivid-dashicons-grey wpvivid-download" title="Download"></span>
                        </div>
                    </td>
                </tr>
                <?php
            }
        }
    }
}