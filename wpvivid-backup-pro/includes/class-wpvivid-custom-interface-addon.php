<?php
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Custom_Interface_addon
{
    public $table_list = array();
    public $themes_list = array();
    public $plugins_list = array();
    public $uploads_list = array();
    public $content_list = array();
    public $core_list = array();

    public function __construct(){

    }

    public static function update_custom_backup_setting($options){
        $history = get_option('wpvivid_custom_backup_history', array());

        $custom_option['database_check_ex'] = $options['database_check_ex'];
        $custom_option['folder_check_ex'] = $options['folder_check_ex'];
        $custom_option['exclude_custom'] = $options['exclude_custom'];

        $custom_option['database_option']['database_check'] = $options['database_check'];
        $custom_option['database_option']['exclude_table_list'] = isset($options['database_list']) ? $options['database_list'] : array();

        $custom_option['themes_option']['themes_check'] = $options['themes_check'];
        $custom_option['themes_option']['exclude_themes_list'] = isset($options['themes_list']) ? $options['themes_list'] : array();
        $custom_option['themes_option']['themes_extension_list'] = isset($options['themes_extension']) ? $options['themes_extension'] : array();
        $custom_option['themes_option']['exclude_themes_folder'] = isset($options['exclude_themes_folder']) ? $options['exclude_themes_folder'] : array();

        $custom_option['plugins_option']['plugins_check'] = $options['plugins_check'];
        $custom_option['plugins_option']['exclude_plugins_list'] = isset($options['plugins_list']) ? $options['plugins_list'] : array();
        $custom_option['plugins_option']['plugins_extension_list'] = isset($options['plugins_extension']) ? $options['plugins_extension'] : array();
        $custom_option['plugins_option']['exclude_plugins_folder'] = isset($options['exclude_plugins_folder']) ? $options['exclude_plugins_folder'] : array();

        $custom_option['uploads_option']['uploads_check'] = $options['uploads_check'];
        $custom_option['uploads_option']['exclude_uploads_list'] = isset($options['uploads_list']) ? $options['uploads_list'] : array();
        $custom_option['uploads_option']['uploads_extension_list'] = isset($options['upload_extension']) ? $options['upload_extension'] : array();

        $custom_option['content_option']['content_check'] = $options['content_check'];
        $custom_option['content_option']['exclude_content_list'] = isset($options['content_list']) ? $options['content_list'] : array();
        $custom_option['content_option']['content_extension_list'] = isset($options['content_extension']) ? $options['content_extension'] : array();

        $custom_option['core_option']['core_check'] = $options['core_check'];

        $custom_option['other_option']['other_check'] = $options['other_check'];
        $custom_option['other_option']['include_other_list'] = isset($options['other_list']) ? $options['other_list'] : array();
        $custom_option['other_option']['other_extension_list'] = isset($options['other_extension']) ? $options['other_extension'] : array();

        if(isset($history['additional_database_option']))
        {
            $custom_option['additional_database_option'] = $history['additional_database_option'];
        }
        $custom_option['additional_database_option']['additional_database_check'] = $options['additional_database_check'];

        WPvivid_Setting::update_option('wpvivid_custom_backup_history', $custom_option);
    }

    public static function get_custom_backup_setting(){
        $data = get_option('wpvivid_custom_backup_history');
        return $data;
    }

    public function wpvivid_get_tables($pref = ''){
        global $wpdb;
        $exclude = self::get_custom_backup_setting();
        if(empty($exclude)){
            $exclude = array();
        }

        $base_table = '';
        $other_table = '';
        $default_table = array('wp_commentmeta', 'wp_comments', 'wp_links', 'wp_options', 'wp_postmeta', 'wp_posts', 'wp_term_relationships',
            'wp_term_taxonomy', 'wp_termmeta', 'wp_terms', 'wp_usermeta', 'wp_users');

        if (is_multisite() && !defined('MULTISITE'))
        {
            $prefix = $wpdb->base_prefix;
        } else {
            $prefix = $wpdb->get_blog_prefix(0);
        }

        $ret = '';
        $tables=$wpdb->get_results('SHOW TABLE STATUS',ARRAY_A);

        if (!is_null($tables))
        {
            $tables_info=array();
            $has_base_table = false;
            $has_other_table = false;
            foreach ($tables as $row)
            {
                if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1)
                {
                    continue;
                }

                $tables_info[$row["Name"]]["Rows"]=$row["Rows"];
                $tables_info[$row["Name"]]["Data_length"]=size_format($row["Data_length"]+$row["Index_length"],2);

                $checked = 'checked';
                if(!empty($exclude['database_option']['exclude_table_list'])) {
                    if (in_array($row["Name"], $exclude['database_option']['exclude_table_list'])) {
                        $checked = '';
                    }
                }

                if(in_array($row["Name"], $default_table)){
                    $has_base_table = true;
                    $base_table .= '
                        <tr>
                            <th class="check-column" style="border-left: 4px solid #f7fcfe;"><input type="checkbox" name="'.$pref.'Database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' /></th>
                            <td style="border-left: 0;"><strong>'.esc_html($row["Name"]).'</strong></td>
                            <td style="border-left: 0;"><div class="plugin-description"><span>Rows: '.$row["Rows"].'</span><span style="padding-left: 20px;">Size: '.$tables_info[$row["Name"]]["Data_length"].'</span></div></td>
                        </tr>
                    ';
                }
                else{
                    $has_other_table = true;
                    $other_table .= '
                        <tr>
                            <th class="check-column" style="border-left: 4px solid #f7fcfe;"><input type="checkbox" name="'.$pref.'Database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' /></th>
                            <td style="border-left: 0;"><strong>'.esc_html($row["Name"]).'</strong></td>
                            <td style="border-left: 0;"><div class="plugin-description"><span>Rows: '.$row["Rows"].'</span><span style="padding-left: 20px;">Size: '.$tables_info[$row["Name"]]["Data_length"].'</span></div></td>
                        </tr>
                    ';
                }
            }

            $base_table_html = '';
            $other_table_html = '';
            if($has_base_table){
                $base_table_html .= '<p>Tables created by wordpress</p>';
                $base_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" style="background-color: #f7fcfe;">
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_database_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_database_table" type="checkbox" />
                                            </td>
                                            <th>Table Name</th>
                                            <th>Table Info</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$base_table.'
                                        </tbody>
                                      </table>';
            }

            if($has_other_table){
                $other_table_html .= '<p>Others</p>';
                $other_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" >
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0;" style="background-color: #f7fcfe;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_other_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_other_table" type="checkbox" />
                                            </td>
                                            <th>Table Name</th>
                                            <th>Table Info</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$other_table.'
                                        </tbody>
                                      </table>';
            }

            $ret = $base_table_html.$other_table_html;
        }
        return $ret;
    }

    public function wpvivid_get_themes_plugins($pref = ''){
        $ret = '';
        $ret .= $this->wpvivid_get_themes($pref).$this->wpvivid_get_plugins($pref);
        return $ret;
    }

    public function wpvivid_get_themes($pref = ''){
        $exclude = self::get_custom_backup_setting();
        if(empty($exclude)){
            $exclude = array();
        }
        $type = 'Themes';
        $themes_path = get_theme_root();
        $has_themes = false;
        $themes_table = '';
        $themes_table_html = '<p>Themes</p>';
        //$files = self::wpvivid_traversal_dir($plugin_path);
        //$files = self::wpvivid_exclude_files_default($type, $files, $plugin_path);
        if (@$handle = opendir($themes_path))
        {
            while (($file = readdir($handle)) != false) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($themes_path . DIRECTORY_SEPARATOR . $file)) {
                        $has_themes = true;
                        $themes_info[$file] = $this->get_theme_plugin_info($themes_path . DIRECTORY_SEPARATOR . $file);

                        $checked = 'checked';
                        if (!empty($exclude['themes_option']['exclude_themes_list'])) {
                            if (in_array($file, $exclude['themes_option']['exclude_themes_list'])) {
                                $checked = '';
                            }
                        }

                        $themes_table .= '
                                <tr>
                                    <th class="check-column" style="border-left: 4px solid #f7fcfe;"><input type="checkbox" name="'.$pref.esc_attr('Themes').'" value="'.esc_attr($file).'" '.esc_html($checked).' /></th>
                                    <td style="border-left: 0;"><strong>'.esc_html($file).'</strong></td>
                                    <td style="border-left: 0;"><div class="plugin-description"><span>Size: '.size_format($themes_info[$file]["size"], 2).'</span></div></td>
                                </tr>
                            ';
                    }
                }
            }
            closedir($handle);
        }
        if($has_themes){
            $themes_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" >
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0;" style="background-color: #f7fcfe;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_themes_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_themes_table" type="checkbox" />
                                            </td>
                                            <th>Themes Name</th>
                                            <th>Themes Info</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$themes_table.'
                                        </tbody>
                                      </table>';
        }
        $ret = $themes_table_html;
        return $ret;
    }

    public function wpvivid_get_plugins($pref = ''){
        $exclude = self::get_custom_backup_setting();
        if(empty($exclude)){
            $exclude = array();
        }
        $type = 'Plugins';
        $has_plugins = false;
        $plugins_table = '';
        $plugins_table_html = '<p>Plugins</p>';
        $path = WP_PLUGIN_DIR;

        $plugin_info = array();
        if (@$handle = opendir($path)) {
            while (($file = readdir($handle)) != false) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                        $has_plugins = true;
                        $plugin_info[$file] = $this->get_theme_plugin_info($path . DIRECTORY_SEPARATOR . $file);

                        $checked = 'checked';
                        if (!empty($exclude['plugins_option']['exclude_plugins_list'])) {
                            if (in_array($file, $exclude['plugins_option']['exclude_plugins_list'])) {
                                $checked = '';
                            }
                        }
                        $plugins_table .= '
                                <tr>
                                    <th class="check-column" style="border-left: 4px solid #f7fcfe;"><input type="checkbox" name="'.$pref.esc_attr('Plugins').'" value="'.esc_attr($file).'" '.esc_html($checked).' /></th>
                                    <td style="border-left: 0;"><strong>'.esc_html($file).'</strong></td>
                                    <td style="border-left: 0;"><div class="plugin-description"><span>Size: '.size_format($plugin_info[$file]["size"], 2).'</span></div></td>
                                </tr>
                            ';
                    }
                }
            }
            closedir($handle);
        }
        $ret['plugin_info'] = $plugin_info;
        if($has_plugins){
            $plugins_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" >
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0;" style="background-color: #f7fcfe;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_plugins_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_plugins_table" type="checkbox" />
                                            </td>
                                            <th>plugins Name</th>
                                            <th>plugins Info</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$plugins_table.'
                                        </tbody>
                                      </table>';
        }
        $ret = $plugins_table_html;
        return $ret;
    }

    public function wpvivid_get_uploads(){
        $exclude = self::get_custom_backup_setting();
        if(empty($exclude)){
            $exclude = array();
        }
        $upload_dir = wp_upload_dir();
        $type = 'Uploads';
        $path = $upload_dir['basedir'];
        $plugin_path = realpath($path);
        $files = $this->wpvivid_traversal_dir($plugin_path);
        $files = $this->wpvivid_exclude_files_default($type, $files, $plugin_path);
        $ret = '';
        $counter = 0;
        $ret .= '<fieldset><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
        foreach ($files as $file) {
            $dir = str_replace ($plugin_path.'/', '', $file);
            $this->uploads_list[] = $dir;
            $checked = 'checked';
            if (!empty($exclude['uploads_option']['exclude_uploads_list'])) {
                if (in_array($dir, $exclude['uploads_option']['exclude_uploads_list'])) {
                    $checked = '';
                }
            }
            $ret .= '<label><input class="checkbox" type="checkbox" name="' . esc_attr($type) . '" value="' . esc_attr($dir) . '" ' . esc_html($checked) . ' />' . $dir . '</label><br />';
            $counter++;
            if ($counter > 0) {
                $ret .= '</div><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
                $counter = 0;
            }
        }
        $ret .= '</div></fieldset>';
        return $ret;
    }

    public function wpvivid_get_content(){
        $exclude = self::get_custom_backup_setting();
        if(empty($exclude)){
            $exclude = array();
        }
        $upload_dir = wp_upload_dir();
        $type = 'Content';
        $path = WP_CONTENT_DIR;
        $plugin_path = realpath($path);
        $files = $this->wpvivid_traversal_dir($plugin_path);
        $files = $this->wpvivid_exclude_files_default($type, $files, $plugin_path);
        $ret = '';
        $counter = 0;
        $ret .= '<fieldset><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
        foreach ($files as $file) {
            $dir = str_replace ($plugin_path.'/', '', $file);
            $this->content_list[] = $dir;
            $checked = 'checked';
            if (!empty($exclude['content_option']['exclude_content_list'])) {
                if (in_array($dir, $exclude['content_option']['exclude_content_list'])) {
                    $checked = '';
                }
            }
            $ret .= '<label><input class="checkbox" type="checkbox" name="' . esc_attr($type) . '" value="' . esc_attr($dir) . '" ' . esc_html($checked) . ' />' . $dir . '</label><br />';
            $counter++;
            if ($counter > 0) {
                $ret .= '</div><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
                $counter = 0;
            }
        }
        $ret .= '</div></fieldset>';
        return $ret;
    }

    public function wpvivid_get_core(){
        $exclude = self::get_custom_backup_setting();
        if(empty($exclude)){
            $exclude = array();
        }
        $upload_dir = wp_upload_dir();
        $type = 'Core';
        $path = ABSPATH;
        $plugin_path = realpath($path);
        $files = $this->wpvivid_traversal_dir($plugin_path);
        $files = $this->wpvivid_exclude_files_default($type, $files, $plugin_path);
        $ret = '';
        $counter = 0;
        $ret .= '<fieldset><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
        foreach ($files as $file) {
            $dir = str_replace ($plugin_path.'/', '', $file);
            $this->core_list[] = $dir;
            $checked = 'checked';
            if (!empty($exclude['core_option']['exclude_core_list'])) {
                if (in_array($dir, $exclude['core_option']['exclude_core_list'])) {
                    $checked = '';
                }
            }
            $ret .= '<label><input class="checkbox" type="checkbox" name="' . esc_attr($type) . '" value="' . esc_attr($dir) . '" ' . esc_html($checked) . ' />' . $dir . '</label><br />';
            $counter++;
            if ($counter > 0) {
                $ret .= '</div><div style="width: 30%; float:left; min-width: 250px; margin-right: 10px;">';
                $counter = 0;
            }
        }
        $ret .= '</div></fieldset>';
        return $ret;
    }

    public function wpvivid_get_others(){
        $include_path = self::get_custom_backup_setting();
        $max_index = 1;
        $ret_html = '';
        if(!empty($include_path['other_option']['include_other_list'])) {
            foreach ($include_path['other_option']['include_other_list'] as $index => $value) {
                //$value = trailingslashit( str_replace( '\\', '/', realpath( $value ) ) );
                //$value = rtrim($value, '/');
                $max_index = max($index, $max_index);
                $ret_html .= '<div>';
                $ret_html .= '<input type="text" id="wpvivid_select_custom_'.$index.'" name="Other" value="'.$value.'" style="width: 500px;" />';
                $ret_html .= '<input type="button" class="wpvivid_remove_custom" value="X" />';
                $ret_html .= '</div>';
            }
        }
        $ret = '<div class="postbox schedule-tab-block setting-page-content">
                    <div> 
                        <div class="wpvivid-custom-tree-contain">
                            <div id="wpvivid_custom_backup_tree"></div>
                        </div>
                        <div id="wpvivid_select_custom_backup">
                            '.$ret_html.'
                        </div>
                        <input type="button" id="wpvivid_select_tree_node" value="Select This Node" />
                    </div>
                </div>';
        return $ret;
    }

    public static function wpvivid_traversal_dir($dir)
    {
        $files = array();
        if(@$handle = opendir($dir)){
            while(($file = readdir($handle)) != false){
                if($file != '.' && $file != '..'){
                    if(is_dir($dir.'/'.$file)){
                        $files[$file] = $dir.'/'.$file;
                    }
                }
            }
            closedir($handle);
            return $files;
        }
    }

    private static function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode(DIRECTORY_SEPARATOR,$values);
    }

    public static function wpvivid_exclude_files_default($type, $files, $plugin_path){
        $file_array = array();
        $upload_dir = wp_upload_dir();
        foreach ($files as $file) {
            $exclude = false;
            $dir = self::transfer_path($file, '/');
            if($type == 'Core'){
                if($dir == self::transfer_path(WP_CONTENT_DIR, '/')){
                    $exclude = true;
                }
            }
            else if($type == 'Content'){
                if($dir == self::transfer_path(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.self::wpvivid_get_content_backupdir(), '/')){
                    $exclude = true;
                }
                else if($dir == self::transfer_path(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'plugins', '/')){
                    $exclude = true;
                }
                else if($dir == self::transfer_path($upload_dir['basedir'], '/')){
                    $exclude = true;
                }
                else if($dir == self::transfer_path(get_theme_root(), '/')){
                    $exclude = true;
                }
            }
            else if($type == 'Plugins'){
                if($dir == self::transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wpvivid-backuprestore', '/')){
                    $exclude = true;
                }
            }
            if(!$exclude){
                $file_array[] = $file;
            }
        }
        return $file_array;
    }

    public function get_themes_plugin_info()
    {
        $exclude = self::get_custom_backup_setting();
        if(empty($exclude)){
            $exclude = array();
        }

        if(isset($_POST['type']) && !empty($_POST['type'])) {
            $type = $_POST['type'];

            $pref = '';
            if ($type === 'schedule') {
                $pref = $type . '_';
            }

            $themes_path = get_theme_root();

            $has_themes = false;
            $themes_table = '';
            $themes_table_html = '';
            $themes_count=0;
            $themes_info = array();
            if (@$handle = opendir($themes_path)) {
                while (($file = readdir($handle)) != false) {
                    if ($file != '.' && $file != '..') {
                        if (is_dir($themes_path . DIRECTORY_SEPARATOR . $file)) {
                            $has_themes = true;
                            $themes_info[$file] = $this->get_theme_plugin_info($themes_path . DIRECTORY_SEPARATOR . $file);

                            $checked = 'checked';
                            if (!empty($exclude['themes_option']['exclude_themes_list'])) {
                                if (in_array($file, $exclude['themes_option']['exclude_themes_list'])) {
                                    $checked = '';
                                }
                            }

                            if($themes_count % 2 == 0){
                                $themes_table .= '<tr>';
                            }
                            $themes_table .= '   
                                    <td class="plugin-title column-primary check-column" style="border-left: 0;">
                                        <input type="checkbox" name="'.$pref.esc_attr('Themes').'" value="'.esc_attr($file).'" '.esc_html($checked).' />
                                        <span>'.esc_html($file).'</span><span>Size: '.size_format($themes_info[$file]["size"], 2).'</span>
                                    </td>
                                ';
                            if($themes_count % 2 != 0){
                                $themes_table .= '</tr>';
                            }
                            $themes_count++;
                        }
                    }
                }
                closedir($handle);
            }
            $ret['result'] = 'success';
            $ret['themes_info'] = $themes_info;
            if($has_themes){
                $themes_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" >
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_themes_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_themes_table" type="checkbox" />
                                            </td>
                                            <th scope="col" class="manage-column column-name column-primary">Themes Name</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$themes_table.'
                                        </tbody>
                                      </table>';
            }
            $ret['html'] .= $themes_table_html;

            $ret['html'] .= '<div style="padding: 10px;"></div>';

            $has_plugins = false;
            $plugins_table = '';
            $plugins_table_html = '';
            $path = WP_PLUGIN_DIR;
            $plugin_count=0;
            $plugin_info = array();
            if (@$handle = opendir($path)) {
                while (($file = readdir($handle)) != false) {
                    if ($file != '.' && $file != '..') {
                        if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                            $has_plugins = true;
                            $plugin_info[$file] = $this->get_theme_plugin_info($path . DIRECTORY_SEPARATOR . $file);

                            $checked = 'checked';
                            if (!empty($exclude['plugins_option']['exclude_plugins_list'])) {
                                if (in_array($file, $exclude['plugins_option']['exclude_plugins_list'])) {
                                    $checked = '';
                                }
                            }

                            if($plugin_count % 2 == 0){
                                $plugins_table .= '<tr>';
                            }
                            $plugins_table .= '   
                                    <td class="plugin-title column-primary check-column" style="border-left: 0;">
                                        <input type="checkbox" name="'.$pref.esc_attr('Plugins').'" value="'.esc_attr($file).'" '.esc_html($checked).' />
                                        <span>'.esc_html($file).'</span><span>Size: '.size_format($plugin_info[$file]["size"], 2).'</span>
                                    </td>
                                ';
                            if($plugin_count % 2 != 0){
                                $plugins_table .= '</tr>';
                            }
                            $plugin_count++;
                        }
                    }
                }
                closedir($handle);
            }
            $ret['plugin_info'] = $plugin_info;
            if($has_plugins){
                $plugins_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" >
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_plugins_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_plugins_table" type="checkbox" />
                                            </td>
                                            <th scope="col" class="manage-column column-name column-primary">Plugins Name</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$plugins_table.'
                                        </tbody>
                                      </table>';
            }
            $ret['html'] .= $plugins_table_html;
            echo json_encode($ret);
            die();
        }
    }

    public function get_tables_info()
    {
        global $wpdb;

        if(isset($_POST['type']) && !empty($_POST['type'])){
            $type = $_POST['type'];

            $pref = $type.'_';
            /*if($type === 'schedule'){
                $pref = $type.'_';
            }*/

            $exclude = self::get_custom_backup_setting();
            if(empty($exclude)){
                $exclude = array();
            }

            $base_table = '';
            $other_table = '';
            $default_table = array('wp_commentmeta', 'wp_comments', 'wp_links', 'wp_options', 'wp_postmeta', 'wp_posts', 'wp_term_relationships',
                'wp_term_taxonomy', 'wp_termmeta', 'wp_terms', 'wp_usermeta', 'wp_users');

            if (is_multisite() && !defined('MULTISITE'))
            {
                $prefix = $wpdb->base_prefix;
            } else {
                $prefix = $wpdb->get_blog_prefix(0);
            }

            $tables=$wpdb->get_results('SHOW TABLE STATUS',ARRAY_A);

            if (is_null($tables))
            {
                $ret['result']='failed';
                $ret['error']='show table status failed';
                echo json_encode($ret);
                die();
            }

            $tables_info=array();
            $ret['result']='success';
            $ret['html']='';
            $has_base_table = false;
            $has_other_table = false;
            $count = 0;
            foreach ($tables as $row)
            {
                if (preg_match('/^(?!' . $prefix . ')/', $row["Name"]) == 1)
                {
                    continue;
                }

                $tables_info[$row["Name"]]["Rows"]=$row["Rows"];
                $tables_info[$row["Name"]]["Data_length"]=size_format($row["Data_length"]+$row["Index_length"],2);

                $checked = 'checked';
                if(!empty($exclude['database_option']['exclude_table_list'])) {
                    if (in_array($row["Name"], $exclude['database_option']['exclude_table_list'])) {
                        $checked = '';
                    }
                }

                if(in_array($row["Name"], $default_table)){
                    /*$base_table .= '<div>
                               <input type="checkbox" name="'.$pref.'Database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />'
                        .esc_html($row["Name"]).'<span style="padding-left: 20px;">Rows: '.$row["Rows"].'</span><span style="padding-left: 20px;">Length: '.$tables_info[$row["Name"]]["Data_length"].'</span>
                             </div>';*/

                    /*
                     * <td style="border-left: 0;"><div class="plugin-description"><span>Rows: '.$row["Rows"].'</span><span style="padding-left: 20px;">Size: '.$tables_info[$row["Name"]]["Data_length"].'</span></div></td>
                    */
                    if($count % 2 == 0){
                        $base_table .= '<tr>';
                    }

                    $has_base_table = true;
                    $base_table .= '   
                            <td class="plugin-title column-primary check-column" style="border-left: 0;">
                                <input type="checkbox" name="'.$pref.'Database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />
                                <span>'.esc_html($row["Name"]).'</span><span>(Rows: '.$row["Rows"].'</span><span style="padding-left: 10px;">Size: '.$tables_info[$row["Name"]]["Data_length"].')</span>
                            </td>
                    ';

                    if($count % 2 != 0){
                        $base_table .= '</tr>';
                    }

                    $count++;
                }
                else{
                    /*$other_table .= '<div>
                               <input type="checkbox" name="'.$pref.'Database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' />'
                        .esc_html($row["Name"]).'<span style="padding-left: 20px;">Rows: '.$row["Rows"].'</span><span style="padding-left: 20px;">Length: '.$tables_info[$row["Name"]]["Data_length"].'</span>
                             </div>';*/
                    $has_other_table = true;
                    $other_table .= '
                        <tr>
                            <th class="check-column" style="border-left: 4px solid #f7fcfe;"><input type="checkbox" name="'.$pref.'Database" value="'.esc_html($row["Name"]).'" '.esc_html($checked).' /></th>
                            <td style="border-left: 0;"><strong>'.esc_html($row["Name"]).'</strong></td>
                            <td style="border-left: 0;"><div class="plugin-description"><span>Rows: '.$row["Rows"].'</span><span style="padding-left: 20px;">Size: '.$tables_info[$row["Name"]]["Data_length"].'</span></div></td>
                        </tr>
                    ';
                }
            }

            $base_table_html = '';
            $other_table_html = '';
            if($has_base_table){
                $base_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" style="background-color: #f7fcfe;">
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_database_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_database_table" type="checkbox" />
                                            </td>
                                            <th scope="col" class="manage-column column-name column-primary">WordPress Table Name</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$base_table.'
                                        </tbody>
                                      </table>';
            }

            if($has_other_table){
                $other_table_html .= '<p>Others</p>';
                $other_table_html .= '<table class="wp-list-table widefat plugins wpvivid-custom-database-table-list" >
                                        <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column" style="border-left: 0; background-color: #f7fcfe;">
                                                <label class="screen-reader-text" for="wpvivid_'.$pref.'custom_other_table">Select All</label>
                                                <input id="wpvivid_'.$pref.'custom_other_table" type="checkbox" />
                                            </td>
                                            <th>Table Name</th>
                                            <th>Table Info</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            '.$other_table.'
                                        </tbody>
                                      </table>';
            }

            $ret['html'] = $base_table_html.$other_table_html;
            $ret['tables_info']=$tables_info;
            echo json_encode($ret);
            die();
        }

    }

    public function get_themes_info()
    {
        $themes_path = get_theme_root();

        $themes_info = array();
        if(@$handle = opendir($themes_path))
        {
            while(($file = readdir($handle)) != false)
            {
                if($file != '.' && $file != '..')
                {
                    if(is_dir($themes_path.DIRECTORY_SEPARATOR.$file))
                    {
                        $themes_info[$file]=$this->get_theme_plugin_info($themes_path.DIRECTORY_SEPARATOR.$file);
                    }
                }
            }
            closedir($handle);
        }

        $ret['result']='success';
        $ret['themes_info']=$themes_info;

        return $ret;
    }

    public function get_theme_plugin_info($root)
    {
        $theme_info['size']=$this->get_folder_size($root,0);
        return $theme_info;
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
                            $size=$this->get_folder_size($root . DIRECTORY_SEPARATOR . $filename,$size);
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

    public function get_plugin_info()
    {
        $path = WP_PLUGIN_DIR;

        $plugin_info = array();
        if(@$handle = opendir($path))
        {
            while(($file = readdir($handle)) != false)
            {
                if($file != '.' && $file != '..')
                {
                    if(is_dir($path.DIRECTORY_SEPARATOR.$file))
                    {
                        $plugin_info[$file]=$this->get_theme_plugin_info($path.DIRECTORY_SEPARATOR.$file);
                    }
                }
            }
            closedir($handle);
        }

        $ret['result']='success';
        $ret['plugin_info']=$plugin_info;
        return $ret;
    }

    public function wpvivid_get_include_list($type)
    {
        $ret = '';
        $backup_history = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
        if($type === 'manual_backup')
        {
            $backup_history = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
        }
        else if($type === 'incremental_backup')
        {
            $backup_history = WPvivid_Custom_Backup_Manager::get_incremental_file_settings();
        }

        if(!empty($backup_history))
        {
            if(isset($backup_history['custom_dirs']['other_list']) && !empty($backup_history['custom_dirs']['other_list']))
            {
                $include_folders = $backup_history['custom_dirs']['other_list'];
                foreach ($include_folders as $index => $value)
                {
                    $type = 'folder';
                    $path = $value;
                    $class_type = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    $ret .= '<div class="wpvivid-text-line" type="'.$type.'">
                            <span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree"></span><span class="'.$class_type.'"></span><span class="wpvivid-text-line">'.$path.'</span>
                         </div>';
                }
            }
        }
        return $ret;
    }

    public function wpvivid_get_exclude_list($type)
    {
        $ret = '';
        $exclude_path = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
        if($type === 'manual_backup')
        {
            $exclude_path = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
        }
        else if($type === 'incremental_backup')
        {
            $exclude_path = WPvivid_Custom_Backup_Manager::get_incremental_file_settings();
        }

        if(!empty($exclude_path))
        {
            if(isset($exclude_path['exclude_files']) && !empty($exclude_path['exclude_files']))
            {
                $exclude_files = $exclude_path['exclude_files'];
                foreach ($exclude_files as $index => $value)
                {
                    if($value['type'] === 'folder')
                    {
                        $class_type = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    }
                    else if($value['type'] === 'file')
                    {
                        $class_type = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                    }
                    else
                    {
                        $class_type = 'dashicons dashicons-media-code wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                    }
                    $ret .= '<div class="wpvivid-text-line" type="'.$value['type'].'">
                            <span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree"></span><span class="'.$class_type.'"></span><span class="wpvivid-text-line">'.$value['path'].'</span>
                         </div>';
                }
            }
        }
        else
        {
            $exclude_default=apply_filters('wpvivid_default_exclude_folders',array());
            if(!empty($exclude_default))
            {
                foreach ($exclude_default as $index => $value)
                {
                    if($value['type'] === 'folder')
                    {
                        $class_type = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    }
                    else if($value['type'] === 'file')
                    {
                        $class_type = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                    }
                    else
                    {
                        $class_type = 'dashicons dashicons-media-code wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                    }
                    $ret .= '<div class="wpvivid-text-line" type="'.$value['type'].'">
                            <span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree"></span><span class="'.$class_type.'"></span><span class="wpvivid-text-line">'.$value['path'].'</span>
                         </div>';
                }
            }
        }
        return $ret;
    }

    public function wpvivid_get_exclude_list_ex($backup_type, $type){
        $option_type = 'themes_option';
        $list_type = 'exclude_themes_list';
        if($backup_type == 'themes'){
            $option_type = 'themes_option';
            $list_type = 'exclude_themes_list';
        }
        else if($backup_type == 'plugins'){
            $option_type = 'plugins_option';
            $list_type = 'exclude_plugins_list';
        }
        else if($backup_type == 'content'){
            $option_type = 'content_option';
            $list_type = 'exclude_content_list';
        }
        else if($backup_type == 'uploads'){
            $option_type = 'uploads_option';
            $list_type = 'exclude_uploads_list';
        }
        else if($backup_type == 'additional-folder'){
            $option_type = 'other_option';
            $list_type = 'include_other_list';
        }

        $ret = '';
        $exclude_path = self::get_custom_backup_setting();
        if($type === 'manual_backup')
        {
            $exclude_path = self::get_custom_backup_setting();
        }
        else if($type === 'incremental_backup')
        {
            $exclude_path = WPvivid_Custom_Backup_Manager::get_incremental_file_settings();
        }
        //fix old
        if($backup_type == 'themes'){
            if(isset($exclude_path['themes_option']['exclude_themes_list'])){
                foreach ($exclude_path['themes_option']['exclude_themes_list'] as $index => $value) {
                    if(!isset($value['type'])){
                        $exclude_path['themes_option']['exclude_themes_list'][$value]['name'] = $value;
                        $exclude_path['themes_option']['exclude_themes_list'][$value]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        unset($exclude_path['themes_option']['exclude_themes_list'][$index]);
                    }
                }
            }
            if(isset($exclude_path['themes_option']['exclude_themes_folder'])){
                foreach ($exclude_path['themes_option']['exclude_themes_folder'] as $value){
                    $exclude_path['themes_option']['exclude_themes_list'][$value]['name'] = $value;
                    $exclude_path['themes_option']['exclude_themes_list'][$value]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                }
                unset($exclude_path['themes_option']['exclude_themes_folder']);
            }
        }
        else if($backup_type == 'plugins'){
            if(isset($exclude_path['plugins_option']['exclude_plugins_list'])){
                foreach ($exclude_path['plugins_option']['exclude_plugins_list'] as $index => $value) {
                    if(!isset($value['type'])){
                        $exclude_path['plugins_option']['exclude_plugins_list'][$value]['name'] = $value;
                        $exclude_path['plugins_option']['exclude_plugins_list'][$value]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        unset($exclude_path['plugins_option']['exclude_plugins_list'][$index]);
                    }
                }
            }
            if(isset($exclude_path['plugins_option']['exclude_plugins_folder'])){
                foreach ($exclude_path['plugins_option']['exclude_plugins_folder'] as $value){
                    $exclude_path['plugins_option']['exclude_plugins_list'][$value]['name'] = $value;
                    $exclude_path['plugins_option']['exclude_plugins_list'][$value]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                }
                unset($exclude_path['plugins_option']['exclude_plugins_folder']);
            }
        }
        //

        //exclude inactive themes & plugins
        if($type === 'manual_backup')
        {
            $backup_history = get_option('wpvivid_custom_backup_history', array());
        }
        else if($type === 'incremental_backup')
        {
            $exclude_path = WPvivid_Custom_Backup_Manager::get_incremental_file_settings();
        }

        if(empty($backup_history)) {
            if(is_child_theme())
            {
                $parent_theme = get_template();
            }
            else
            {
                $parent_theme = false;
            }

            $current_active_theme = get_stylesheet();
            $themes = wp_get_themes();
            foreach ($themes as $theme) {
                $file = $theme->get_stylesheet();
                if ($file !== $current_active_theme && $file !== $parent_theme) {
                    $exclude_path['themes_option']['exclude_themes_list'][$file]['name'] = $file;
                    $exclude_path['themes_option']['exclude_themes_list'][$file]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                }
            }

            $active_plugins = get_option('active_plugins');
            $plugins = get_plugins();
            foreach ($plugins as $key => $plugin) {
                $slug = dirname($key);
                if ($slug == '.' || $slug == 'wpvivid-backuprestore' || $slug == 'wpvivid-backup-pro' || $slug == 'wpvividdashboard')
                    continue;
                if (!in_array($key, $active_plugins)) {
                    $exclude_path['plugins_option']['exclude_plugins_list'][$slug]['name'] = $slug;
                    $exclude_path['plugins_option']['exclude_plugins_list'][$slug]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                }
            }
        }
        //
        if(!empty($exclude_path[$option_type][$list_type])) {
            foreach ($exclude_path[$option_type][$list_type] as $index => $value) {
                if(isset($value['type'])){
                    if($value['type'] === 'wpvivid-custom-li-folder-icon'){
                        $value['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    }
                    else if($value['type'] === 'wpvivid-custom-li-file-icon'){
                        $value['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                    }

                    $class_type = $value['type'];
                    $exclude_name = $value['name'];
                    if($value['type'] === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                        $type = 'folder';
                    }
                    else{
                        $type = 'file';
                    }
                }
                else{
                    $class_type = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    $exclude_name = $value;
                    $type = 'folder';
                }
                $ret .= '<div class="wpvivid-text-line" type="'.$type.'">
                            <span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-remove-custom-exlcude-tree"></span><span class="'.$class_type.'"></span><span class="wpvivid-text-line">'.$exclude_name.'</span>
                         </div>';
            }
        }

        return $ret;
    }

    public function wpvivid_get_exclude_content_list(){
        $exclude_path = self::get_custom_backup_setting();
        $ret = '';
        if(!empty($exclude_path['content_option']['exclude_content_list'])) {
            foreach ($exclude_path['content_option']['exclude_content_list'] as $index => $value) {
                $ret .= '<ul>
                            <li>
                                <div class="'.$value['type'].'"></div>
                                <div class="wpvivid-custom-li-font">'.$value['name'].'</div>
                                <div class="wpvivid-custom-li-close" onclick="wpvivid_remove_tree(this);" title="Remove" style="cursor: pointer;">X</div>
                            </li>
                         </ul>';
            }
        }
        return $ret;
    }

    public function wpvivid_get_include_other_list(){
        $include_path = self::get_custom_backup_setting();
        $ret = '';
        if(!empty($include_path['other_option']['include_other_list'])) {
            foreach ($include_path['other_option']['include_other_list'] as $index => $value) {
                if(isset($value)){

                }
                $ret .= '<ul>
                            <li>
                                <div class="'.$value['type'].'"></div>
                                <div class="wpvivid-custom-li-font">'.$value['name'].'</div>
                                <div class="wpvivid-custom-li-close" onclick="wpvivid_remove_tree(this);" title="Remove" style="cursor: pointer;">X</div>
                            </li>
                         </ul>';
            }
        }
        return $ret;
    }

    public static function wpvivid_get_exclude_themes_folder(){
        $exclude_path = self::get_custom_backup_setting();
        $ret = '';
        if(!empty($exclude_path['themes_option']['exclude_themes_folder'])) {
            foreach ($exclude_path['themes_option']['exclude_themes_folder'] as $index => $value) {
                $ret .= '<ul>
                            <li>
                                <div class="wpvivid-custom-li-folder-icon"></div>
                                <div class="wpvivid-custom-li-font">'.$value.'</div>
                                <div class="wpvivid-custom-li-close" onclick="wpvivid_remove_tree(this);" title="Remove" style="cursor: pointer;">X</div>
                            </li>
                         </ul>';
            }
        }
        return $ret;
    }

    public static function wpvivid_get_exclude_plugins_folder(){
        $exclude_path = self::get_custom_backup_setting();
        $ret = '';
        if(!empty($exclude_path['plugins_option']['exclude_plugins_folder'])) {
            foreach ($exclude_path['plugins_option']['exclude_plugins_folder'] as $index => $value) {
                $ret .= '<ul>
                            <li>
                                <div class="wpvivid-custom-li-folder-icon"></div>
                                <div class="wpvivid-custom-li-font">'.$value.'</div>
                                <div class="wpvivid-custom-li-close" onclick="wpvivid_remove_tree(this);" title="Remove" style="cursor: pointer;">X</div>
                            </li>
                         </ul>';
            }
        }
        return $ret;
    }

    public static function wpvivid_reset_backup_retry_times($task_id)
    {
        $default = array();
        $options = get_option('wpvivid_task_list', $default);
        if(isset($options[$task_id]))
        {
            $task=$options[$task_id];
            $task['status']['resume_count']=0;
            $options[$task_id]=$task;
            update_option('wpvivid_task_list', $options, 'no');
        }
    }

    public static function wpvivid_get_content_backupdir()
    {
        $local_setting=get_option('wpvivid_local_setting');
        if($local_setting['path'])
        {
            $path=$local_setting['path'];
        }
        else
        {
            $path=WPVIVID_DEFAULT_BACKUP_DIR;
        }
        return $path;
    }

    public static function wpvivid_get_local_backupdir()
    {
        $common_setting=get_option('wpvivid_common_setting');
        $local_setting=get_option('wpvivid_local_setting');
        if(empty($local_setting))
        {
            $local_setting['path'] = 'wpvividbackups';
        }
        if(isset($common_setting['local_backup_folder']))
        {
            if($common_setting['local_backup_folder'] === 'content_folder')
            {
                $path=$local_setting['path'];
            }
            else if($common_setting['local_backup_folder'] === 'outside_folder')
            {
                $path=$local_setting['outside_path'];
            }
            else
            {
                $path=$local_setting['path'];
            }
        }
        else
        {
            $path=$local_setting['path'];
        }
        return $path;
    }

    public static function wpvivid_get_local_backup_abspath()
    {
        $common_setting=get_option('wpvivid_common_setting');
        $local_setting=get_option('wpvivid_local_setting');
        if(empty($local_setting))
        {
            $local_setting['path'] = 'wpvividbackups';
        }
        if(isset($common_setting['local_backup_folder']))
        {
            if($common_setting['local_backup_folder'] === 'content_folder')
            {
                $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$local_setting['path'];
            }
            else if($common_setting['local_backup_folder'] === 'outside_folder')
            {
                $path=$local_setting['outside_path'];
            }
            else
            {
                $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$local_setting['path'];
            }
        }
        else
        {
            $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$local_setting['path'];
        }
        return $path;
    }

    public static function wpvivid_get_backuprestore_log_folder()
    {
        $common_setting=get_option('wpvivid_common_setting');
        $local_setting=get_option('wpvivid_local_setting');
        if(empty($local_setting))
        {
            $local_setting['path'] = 'wpvividbackups';
        }
        if(isset($common_setting['local_backup_folder']))
        {
            if($common_setting['local_backup_folder'] === 'content_folder')
            {
                $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$common_setting['log_save_location'];
            }
            else if($common_setting['local_backup_folder'] === 'outside_folder')
            {
                $path=$common_setting['log_save_location_outside'];
            }
            else
            {
                $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$common_setting['log_save_location'];
            }
        }
        else
        {
            $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$common_setting['log_save_location'];
        }
        return $path;
    }

    public static function delete_backup_by_id($backup_id,$force=0)
    {
        $backup_list=new WPvivid_New_BackupList();
        $backup = $backup_list->get_backup_by_id($backup_id);
        if(!$backup)
        {
            $ret['result']='failed';
            $ret['error']=__('Retrieving the backup(s) information failed while deleting the selected backup(s). Please try again later.', 'wpvivid-backuprestore');
            return $ret;
        }

        $backup_item=new WPvivid_New_Backup_Item($backup);
        if($backup_item->is_lock())
        {
            if($force==0)
            {
                $ret['result']='failed';
                $ret['error']=__('Unable to delete the locked backup. Please unlock it first and try again.', 'wpvivid-backuprestore');
                return $ret;
            }
        }
        $backup_list->delete_backup($backup_id);

        $backup_item->cleanup_local_backup();
        $backup_item->cleanup_remote_backup();
        $ret['result']='success';
        return $ret;
    }
}