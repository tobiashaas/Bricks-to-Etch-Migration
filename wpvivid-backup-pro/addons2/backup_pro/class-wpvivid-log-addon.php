<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Admin_load: yes
 * Interface Name: WPvivid_Log_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPvivid_Log_List extends WP_List_Table
{
    public $page_num;
    public $log_list;
    public $log_type;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'log',
                'screen' => 'log'
            )
        );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        foreach ( $columns as $column_key => $column_display_name ) {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) ) {
                $class[] = 'hidden';
            }

            if ( $column_key === $primary )
            {
                $class[] = 'column-primary';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( ! empty( $class ) ) {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['wpvivid_date'] = 'Date';
        $columns['wpvivid_log_type'] = __( 'Log Type', 'wpvivid' );
        $columns['wpvivid_log_file_name'] =__( 'Log File Name	', 'wpvivid'  );
        $columns['wpvivid_log_action'] = __( 'Action	', 'wpvivid'  );
        $columns['wpvivid_download'] = __( 'Download', 'wpvivid'  );

        return $columns;
    }

    public function set_log_list($log_list, $log_type, $page_num=1)
    {
        $this->log_list=$log_list;
        $this->log_type=$log_type;
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

        $total_items =sizeof($this->log_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->log_list);
    }

    public function _column_wpvivid_date( $log )
    {
        //$offset=get_option('gmt_offset');
        $localtime = strtotime($log['time'])/* + $offset * 60 * 60*/;
        echo '<td><label for="tablecell">'.date('F-d-Y H:i:s',$localtime).'</label></td>';
    }

    protected function column_wpvivid_log_type($log)
    {
        if($log['error'])
        {
            echo '<span>Error</span>';
        }
        else
        {
            echo '<span>'.$log['des'].'</span>';
        }
    }

    public function column_wpvivid_log_file_name( $log )
    {
        $log['file_name'] = apply_filters('wpvivid_white_label_log_name', $log['file_name']);
        echo '<span>'.$log['file_name'].'</span>';
    }

    public function column_wpvivid_log_action( $log )
    {
        $html='<span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-blue open-log" style="cursor:pointer;" log="'.$log['file_name'].'"></span>
			    <span style="cursor:pointer; margin:0;" class="open-log" log="'.$log['file_name'].'">Log</span>';
        echo $html;
    }

    public function column_wpvivid_download( $log )
    {
        $html='<div class="download-log" log="'.$log['file_name'].'" style="cursor:pointer;" title="Prepare to download the backup">
                    <span class="dashicons dashicons-arrow-down-alt wpvivid-dashicons-blue"></span><span>Download</span>
               </div>';
        echo $html;
    }

    public function display_rows()
    {
        $this->_display_rows( $this->log_list );
    }

    private function _display_rows($log_list)
    {
        $page=$this->get_pagenum();

        $page_log_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_log_list = array_splice( $log_list, 0, 10);
            $count++;
        }
        foreach ( $page_log_list as $log)
        {
            $this->single_row($log);
        }
    }

    public function single_row($log)
    {
        ?>
        <tr>
            <?php $this->single_row_columns( $log ); ?>
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
                "%s<input class='current-page' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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
            $css_type = 'padding:0 0 1em 0;';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'display: none;';
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php esc_attr_e($css_type); ?>">
                <?php
                if($this->log_type === 'backup&restore&transfer' || $this->log_type === 'backup' || $this->log_type === 'restore' || $this->log_type === 'transfer'){
                    ?>
                    <span>Filter Logs: </span>
                        <span>
                        <select id="wpvivid_backup_restore_log_type">
                            <option value="0" selected="selected">All</option>
                            <option value="backup">Backup</option>
                            <option value="restore">Restoration</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </span>
                        <span>
                        <select id="wpvivid_backup_restore_log_result">
                            <option value="0" selected="selected">All</option>
                            <option value="succeeded">Succeeded</option>
                            <option value="failed">Failed</option>
                        </select>
                    </span>
                    <span><input id="wpvivid_search_backup_restore_log_btn" type="submit" class="button action" value="Search"></span>
                    <?php
                }
                ?>
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

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages <= 1){
            if($this->log_type === 'backup&restore&transfer' || $this->log_type === 'backup' || $this->log_type === 'restore' || $this->log_type === 'transfer'){
                ?>
                <div style="padding: 0 0 1em 0;">
                    <span>Filter Logs: </span>
                    <span>
                        <select id="wpvivid_backup_restore_log_type">
                            <option value="0" selected="selected">All</option>
                            <option value="backup">Backup</option>
                            <option value="restore">Restoration</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </span>
                    <span>
                        <select id="wpvivid_backup_restore_log_result">
                            <option value="0" selected="selected">All</option>
                            <option value="succeeded">Succeeded</option>
                            <option value="failed">Failed</option>
                        </select>
                    </span>
                    <span><input id="wpvivid_search_backup_restore_log_btn" type="submit" class="button action" value="Search"></span>
                </div>
                <?php
            }
        }

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
}

class WPvivid_Log_addon
{
    public function __construct()
    {
        //add_action('admin_head', array($this, 'my_test_admin_custom_styles'));
        add_action('wp_ajax_wpvivid_get_log_list_page', array($this, 'get_log_list_page'));
        add_action('wp_ajax_wpvivid_view_log_ex', array($this, 'view_log_ex'));
        add_action('wp_ajax_wpvivid_download_log', array($this, 'download_log'));
    }

    public function get_log_list_ex()
    {
        $ret['log_list']['file']=array();
        $log=new WPvivid_Log_Ex_addon();
        $dir=$log->GetSaveLogFolder();
        $files=array();
        $error_files=array();
        $handler=opendir($dir);
        $regex='#^wpvivid.*_log.txt#';
        if($handler!==false)
        {
            while(($filename=readdir($handler))!==false)
            {
                if($filename != "." && $filename != "..")
                {
                    if(is_dir($dir.$filename))
                    {
                        continue;
                    }else{
                        if(preg_match($regex,$filename))
                        {
                            $files[$filename] = $dir.$filename;
                        }
                    }
                }
            }
            if($handler)
                @closedir($handler);
        }

        $dir.='error'.DIRECTORY_SEPARATOR;
        if(file_exists($dir))
        {
            $handler=opendir($dir);
            if($handler!==false)
            {
                while(($filename=readdir($handler))!==false)
                {
                    if($filename != "." && $filename != "..")
                    {
                        if(is_dir($dir.$filename))
                        {
                            continue;
                        }else{
                            if(preg_match($regex,$filename))
                            {
                                $error_files[$filename] = $dir.$filename;
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }
        }


        foreach ($files as $file)
        {
            $handle = @fopen($file, "r");
            if ($handle)
            {
                $log_file=array();
                $log_file['file_name']=basename($file);
                $log_file['path']=$file;
                $log_file['des']='';
                $log_file['time']='';
                $log_file['error']=false;
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Log created: ');
                    if($pos!==false)
                    {
                        $log_file['time']=substr ($line,$pos+strlen('Log created: '));
                    }
                }
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Type: ');
                    if($pos!==false)
                    {
                        $log_file['des']=substr ($line,$pos+strlen('Type: '));
                    }
                    else
                    {
                        $log_file['des']='other';
                    }
                }
                $ret['log_list']['file'][basename($file)]=$log_file;
                fclose($handle);
            }
        }

        foreach ($error_files as $file)
        {
            $handle = @fopen($file, "r");
            if ($handle)
            {
                $log_file=array();
                $log_file['file_name']=basename($file);
                $log_file['path']=$file;
                $log_file['des']='';
                $log_file['time']='';
                $log_file['error']=true;
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Log created: ');
                    if($pos!==false)
                    {
                        $log_file['time']=substr ($line,$pos+strlen('Log created: '));
                    }
                }
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Type: ');
                    if($pos!==false)
                    {
                        $log_file['des']=substr ($line,$pos+strlen('Type: '));
                    }
                    else
                    {
                        $log_file['des']='other';
                    }
                }
                $ret['log_list']['file'][basename($file)]=$log_file;
                fclose($handle);
            }
        }

        $ret['log_list']['file'] =$this->sort_list($ret['log_list']['file']);

        return $ret;
    }

    public function get_log_list($type='backup')
    {
        $ret['log_list']['file']=array();
        $log=new WPvivid_Log_Ex_addon();
        $dir=$log->GetSaveLogFolder();
        $files=array();
        $error_files=array();
        $handler=opendir($dir);
        $regex='#^wpvivid.*_log.txt#';
        if($handler!==false)
        {
            while(($filename=readdir($handler))!==false)
            {
                if($filename != "." && $filename != "..")
                {
                    if(is_dir($dir.$filename))
                    {
                        continue;
                    }else{
                        if(preg_match($regex,$filename))
                        {
                            $files[$filename] = $dir.$filename;
                        }
                    }
                }
            }
            if($handler)
                @closedir($handler);
        }

        $dir.='error'.DIRECTORY_SEPARATOR;
        if(file_exists($dir))
        {
            $handler=opendir($dir);
            if($handler!==false)
            {
                while(($filename=readdir($handler))!==false)
                {
                    if($filename != "." && $filename != "..")
                    {
                        if(is_dir($dir.$filename))
                        {
                            continue;
                        }else{
                            if(preg_match($regex,$filename))
                            {
                                $error_files[$filename] = $dir.$filename;
                            }
                        }
                    }
                }
                if($handler)
                    @closedir($handler);
            }
        }


        foreach ($files as $file)
        {
            $handle = @fopen($file, "r");
            if ($handle)
            {
                $log_file=array();
                $log_file['file_name']=basename($file);
                $log_file['path']=$file;
                $log_file['des']='';
                $log_file['time']='';
                $log_file['error']=false;
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Log created: ');
                    if($pos!==false)
                    {
                        $log_file['time']=substr ($line,$pos+strlen('Log created: '));
                    }

                    //fix new restore log
                    $pos_des=strpos($line,'Start restoring');
                    if($pos_des!==false)
                    {
                        $pos_time=strpos($line,'[notice]');
                        $log_file['time']=substr ($line,0, $pos_time);
                        $log_file['time']=ltrim($log_file['time'], '[');
                        $log_file['time']=rtrim($log_file['time'], ']');
                        $log_file['des']='restore';
                    }
                }
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Type: ');
                    if($pos!==false)
                    {
                        $log_file['des']=substr ($line,$pos+strlen('Type: '));
                    }
                    else
                    {
                        //$log_file['des']='other';
                    }
                }
                fclose($handle);

                if($type === 'backup&restore&transfer'){
                    $type_arr = array('backup', 'restore', 'transfer');
                    foreach ($type_arr as $value){
                        if(preg_match('#'.$value.'#',$log_file['des']))
                        {
                            $ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if($value=='other')
                        {
                            if(preg_match('#scan#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#export#',$log_file['des']))
                            {
                                $ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#Add Remote Test Connection	#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#upload#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#import#',$log_file['des']))
                            {
                                $ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#transfer#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#other#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                        }
                    }
                }
                else{
                    if(preg_match('#'.$type.'#',$log_file['des']))
                    {
                        $ret['log_list']['file'][basename($file)]=$log_file;
                    }
                    else if($type=='other')
                    {
                        if(preg_match('#scan#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#export#',$log_file['des']))
                        {
                            $ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#Add Remote Test Connection	#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#upload#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#import#',$log_file['des']))
                        {
                            $ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#transfer#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#other#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                    }
                }
            }
        }

        foreach ($error_files as $file)
        {
            $handle = @fopen($file, "r");
            if ($handle)
            {
                $log_file=array();
                $log_file['file_name']=basename($file);
                $log_file['path']=$file;
                $log_file['des']='';
                $log_file['time']='';
                $log_file['error']=true;
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Log created: ');
                    if($pos!==false)
                    {
                        $log_file['time']=substr ($line,$pos+strlen('Log created: '));
                    }
                }
                $line = fgets($handle);
                if($line!==false)
                {
                    $pos=strpos($line,'Type: ');
                    if($pos!==false)
                    {
                        $log_file['des']=substr ($line,$pos+strlen('Type: '));
                    }
                    else
                    {
                        //$log_file['des']='other';
                    }
                }
                fclose($handle);

                if($type === 'backup&restore&transfer'){
                    $type_arr = array('backup', 'restore', 'transfer');
                    foreach ($type_arr as $value){
                        if(preg_match('#'.$value.'#',$log_file['des']))
                        {
                            $ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if($value=='other')
                        {
                            if(preg_match('#scan#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#export#',$log_file['des']))
                            {
                                $ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#Add Remote Test Connection	#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#upload#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#import#',$log_file['des']))
                            {
                                $ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#transfer#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                            else if(preg_match('#other#',$log_file['des']))
                            {
                                //$ret['log_list']['file'][basename($file)]=$log_file;
                            }
                        }
                    }
                }
                else{
                    if(preg_match('#'.$type.'#',$log_file['des']))
                    {
                        $ret['log_list']['file'][basename($file)]=$log_file;
                    }
                    else if($type=='other')
                    {
                        if(preg_match('#scan#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#export#',$log_file['des']))
                        {
                            $ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#Add Remote Test Connection	#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#upload#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#import#',$log_file['des']))
                        {
                            $ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#transfer#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                        else if(preg_match('#other#',$log_file['des']))
                        {
                            //$ret['log_list']['file'][basename($file)]=$log_file;
                        }
                    }
                }
            }
        }

        $ret['log_list']['file'] =$this->sort_list($ret['log_list']['file']);

        return $ret;
    }

    public function sort_list($list)
    {
        uasort ($list,function($a, $b)
        {
            /*if($a['error']>$b['error'])
            {
                return -1;
            }
            else if($a['error']<$b['error'])
            {
                return 1;
            }*/

            if($a['time']>$b['time'])
            {
                return -1;
            }
            else if($a['time']===$b['time'])
            {
                return 0;
            }
            else
            {
                return 1;
            }
        });

        return $list;
    }

    public function get_log_list_page()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try
        {
            $page = $_POST['page'];
            $type=$_POST['type'];

            $backup_restore_type='';
            if(isset($_POST['backup_restore_type'])) {
                $backup_restore_type=$_POST['backup_restore_type'];
                if($backup_restore_type === 'backup' || $backup_restore_type === 'restore' || $backup_restore_type === 'transfer'){
                    $type = $backup_restore_type;
                }
            }

            $backup_restore_result='';
            if(isset($_POST['backup_restore_result'])) {
                $backup_restore_result=$_POST['backup_restore_result'];
            }

            $loglist = $this->get_log_list($type);
            $table = new WPvivid_Log_List();
            if($backup_restore_result !== '' && $backup_restore_result !== 0 && $backup_restore_result !== '0'){
                foreach ($loglist['log_list']['file'] as $key => $value){
                    if($backup_restore_result === 'failed'){
                        if(!$value['error']){
                            unset($loglist['log_list']['file'][$key]);
                        }
                    }
                    else{
                        if($value['error']){
                            unset($loglist['log_list']['file'][$key]);
                        }
                    }
                }
            }

            $table->set_log_list($loglist['log_list']['file'], $type, $page);
            $table->prepare_items();
            ob_start();
            $table->display();
            $rows = ob_get_clean();

            $ret['result'] = 'success';
            $ret['rows'] = $rows;
            echo json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function view_log_ex()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try
        {
            if (isset($_POST['log']) && !empty($_POST['log']) && is_string($_POST['log']))
            {
                $log = sanitize_text_field($_POST['log']);
                $log = basename($log);
                $loglist=$this->get_log_list_ex();

                if(isset($loglist['log_list']['file'][$log]))
                {
                    $log=$loglist['log_list']['file'][$log];
                }
                else
                {
                    $json['result'] = 'failed';
                    $json['error'] = __('Log does not exist. It might have been deleted or lost during a website migration.', 'wpvivid');
                    echo json_encode($json);
                    die();
                }

                $path=$log['path'];

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

    public function download_log()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );

        $admin_url = apply_filters('wpvivid_get_admin_url', '');

        try
        {
            if (isset($_REQUEST['log']))
            {
                $log = sanitize_text_field($_REQUEST['log']);
                $log = basename($log);
                $loglist=$this->get_log_list_ex();

                if(isset($loglist['log_list']['file'][$log]))
                {
                    $log=$loglist['log_list']['file'][$log];
                }
                else
                {
                    $message= __('Log does not exist. It might have been deleted or lost during a website migration.', 'wpvivid');
                    echo __($message.' <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'">retry</a> again.');
                    die();
                }

                $path=$log['path'];

                if (!file_exists($path))
                {
                    $message= __('Log does not exist. It might have been deleted or lost during a website migration.', 'wpvivid');
                    echo __($message.' <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'">retry</a> again.');
                    die();
                }

                if (file_exists($path))
                {
                    if (session_id())
                        session_write_close();

                    $size = filesize($path);
                    if (!headers_sent())
                    {
                        header('Content-Description: File Transfer');
                        header('Content-Type: text');
                        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
                        header('Cache-Control: must-revalidate');
                        header('Content-Length: ' . $size);
                        header('Content-Transfer-Encoding: binary');
                    }

                    if ($size < 1024 * 1024 * 60) {
                        ob_end_clean();
                        readfile($path);
                        exit;
                    } else {
                        ob_end_clean();
                        $download_rate = 1024 * 10;
                        $file = fopen($path, "r");
                        while (!feof($file)) {
                            @set_time_limit(20);
                            // send the current file part to the browser
                            print fread($file, round($download_rate * 1024));
                            // flush the content to the browser
                            flush();
                            if (ob_get_level())
                            {
                                ob_end_clean();
                            }
                            // sleep one second
                            sleep(1);
                        }
                        fclose($file);
                        exit;
                    }
                }
                else
                {
                    echo __(' file not found. please <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'">retry</a> again.');
                    die();
                }

            } else {
                $message = __('Reading the log failed. Please try again.', 'wpvivid');
                echo __($message.' <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'">retry</a> again.');
                die();
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo __($message.' <a href="'.$admin_url.'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-debug').'">retry</a> again.');
            die();
        }
    }

    public function output_log_list($type,$slug,$id)
    {
        ?>
        <div class="wpvivid-log-list" id="<?php echo $id; ?>">
            <?php
            $loglist=$this->get_log_list($type);
            $table = new WPvivid_Log_List();
            $table->set_log_list($loglist['log_list']['file'], $type);
            $table->prepare_items();
            $table->display();
            ?>
        </div>
        <script>
            jQuery('#<?php echo $id?>').on("click",'.first-page',function()
            {
                wpvivid_log_change_page('first','<?php echo $type;?>','<?php echo $id;?>');
            });

            jQuery('#<?php echo $id?>').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_log_change_page(page-1,'<?php echo $type;?>','<?php echo $id?>');
            });

            jQuery('#<?php echo $id?>').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_log_change_page(page+1,'<?php echo $type;?>','<?php echo $id?>');
            });

            jQuery('#<?php echo $id?>').on("click",'.last-page',function()
            {
                wpvivid_log_change_page('last','<?php echo $type;?>','<?php echo $id?>');
            });

            jQuery('#<?php echo $id?>').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    wpvivid_log_change_page(page,'<?php echo $type;?>','<?php echo $id?>');
                }
            });

            jQuery('#<?php echo $id?>').on("click",'.open-log',function()
            {
                var log=jQuery(this).attr("log");
                wpvivid_open_log(log,'<?php echo $slug;?>');
            });

            jQuery('#<?php echo $id?>').on("click",'.download-log',function()
            {
                var log=jQuery(this).attr("log");
                wpvivid_download_log(log);
            });
        </script>
        <?php
    }

    public function output_backup_log_list()
    {
        $this->output_log_list('backup','backup_log','wpvivid_backup_log_list');
    }

    public function output_restore_log_list()
    {
        $this->output_log_list('restore','restore_log','wpvivid_restore_log_list');
    }

    public function output_staging_log_list()
    {
        $this->output_log_list('staging','staging_log','wpvivid_staging_log_list');
    }

    public function output_other_log_list()
    {
        $this->output_log_list('other','other_log','wpvivid_other_log_list');
    }

    public function output_backup_restore_log_list()
    {
        $this->output_log_list('backup&restore&transfer','backup_log_list','wpvivid_backup_log_list');
    }
}