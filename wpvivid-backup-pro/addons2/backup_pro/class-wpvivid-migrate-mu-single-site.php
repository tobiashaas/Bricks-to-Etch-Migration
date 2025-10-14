<?php
/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.37
 * Need_init: yes
 * Interface Name: WPvivid_Migrate_MU_Single_Site
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}


class WPvivid_Migrate_MU_Single_Site
{
    public function __construct()
    {
        add_filter('wpvivid_set_custom_backup', array($this, 'set_mu_backup'), 10,4);
        //old
        add_action('wpvivid_custom_backup_setting', array($this, 'mu_site_setting'), 12, 4);
        //new

        add_filter('wpvivid_exclude_db_table', array($this, 'exclude_table'),12,2);
        add_filter('wpvivid_include_db_table', array($this, 'include_table'),12,2);

        add_filter('wpvivid_dump_set_site_url', array($this, 'dump_set_site_url'),10,2);
        add_filter('wpvivid_dump_set_home_url', array($this, 'dump_set_home_url'),10,2);
        add_filter('wpvivid_dump_set_prefix', array($this, 'dump_set_prefix'),10,2);
        //manual_backup
    }

    public function dump_set_site_url($site_url,$data)
    {
        if(isset($data['mu_migrate']))
        {
            $site_url=get_site_url($data['site_id']);
        }
        return $site_url;
    }

    public function dump_set_home_url($home_url,$data)
    {
        if(isset($data['mu_migrate']))
        {
            $home_url=get_home_url($data['site_id']);
        }
        return $home_url;
    }

    public function dump_set_prefix($prefix,$data)
    {
        if(isset($data['mu_migrate']))
        {
            global $wpdb;

            $prefix=$wpdb->get_blog_prefix($data['site_id']);
        }
        return $prefix;
    }

    public function include_table($include,$data)
    {
        if(isset($data['mu_migrate']))
        {
            global $wpdb;

            $include=array();

            $prefix=$wpdb->get_blog_prefix(0);

            $tables=array();

            if(is_main_site($data['site_id']))
            {
                $sql=$wpdb->prepare("SHOW TABLES LIKE %s;", $wpdb->esc_like($prefix) . '%');
                $result = $wpdb->get_results($sql, OBJECT_K);

                $exclude = array('/^(?!' . $prefix . ')/i');
                $exclude[] ='/^' . $prefix . '\d+_/';

                foreach ($result as $table_name=>$value)
                {
                    if($this->matches($table_name,$exclude))
                    {
                        continue;
                    }
                    $tables[]=$table_name;
                }
            }
            else
            {
                $site_prefix=$wpdb->get_blog_prefix($data['site_id']);

                $sql=$wpdb->prepare("SHOW TABLES LIKE %s;", $wpdb->esc_like($site_prefix) . '%');

                $result = $wpdb->get_results($sql, OBJECT_K);

                foreach ($result as $table_name=>$value)
                {
                    $tables[]=$table_name;
                }

                $tables[]=$prefix.'users';
                $tables[]=$prefix.'usermeta';
            }


            if(isset($data['exclude_tables']) && isset($data['dump_db']))
            {
                foreach ($tables as $table)
                {
                    if(in_array($table,$data['exclude_tables']))
                    {
                        continue;
                    }

                    $include[]=$table;
                }
            }
            else
            {
                $include=$tables;
            }
        }
        return $include;
    }

    private function matches($table, $arr)
    {
        $match = false;

        foreach ($arr as $pattern) {
            if ( '/' != $pattern[0] ) {
                continue;
            }
            if ( 1 == preg_match($pattern, $table) ) {
                $match = true;
            }
        }

        return in_array($table, $arr) || $match;
    }

    public function exclude_table($exclude,$data)
    {
        if(isset($data['mu_migrate']))
        {
            $exclude = array();
            return $exclude;
        }
        if(isset( $data['blog_prefix']))
        {
            if($data['is_main_site'])
            {
                $exclude = array('/^(?!' . $data['blog_prefix'] . ')/i');
                $exclude[] ='/^' . $data['blog_prefix'] . '\d+_/';
            }
            else
            {
                $exclude = array('/^(?!' . $data['blog_prefix'] . ')/i');
            }

        }

        if(isset($data['exclude_tables']) && isset($data['dump_db']))
        {
            foreach ($data['exclude_tables'] as $table)
            {
                $exclude[] = $table;
            }
        }
        if(isset($data['dump_additional_db']))
        {
            $exclude = array();
        }

        return $exclude;
    }

    public function set_mu_backup($backup_options,$backup_type,$options)
    {
        if($backup_type=='backup_mu_sites')
        {
            $site_id=$options['mu_setting']['mu_site_id'];

            $backup_options['backup']['backup_mu_site_db']=$this->set_mu_db($site_id,$backup_options,$options);

            $backup_options['backup']['backup_mu_site_uploads']=$this->set_mu_uploads($site_id,$backup_options,$options);

            if(!is_main_site($site_id))
            {
                $backup_options['backup']['backup_custom_uploads']=$this->set_custom_uploads($backup_options,$options);
            }

            $backup_options['backup']['backup_custom_core']=$this->set_custom_core($backup_options,$options);

            if($options['mu_setting']['themes_check']== '1')
            {
                $backup_options['backup']['backup_custom_themes']=$this->set_custom_themes($backup_options,$options);
            }

            if($options['mu_setting']['plugins_check']== '1')
            {
                $backup_options['backup']['backup_custom_plugin']=$this->set_custom_plugins($backup_options,$options);
            }

            if($options['mu_setting']['content_check']== '1')
            {
                $backup_options['backup']['backup_custom_content']=$this->set_custom_content($backup_options,$options);
            }

            if($options['mu_setting']['additional_file_check']== '1')
            {
                $backup_options['backup']['backup_custom_other']=$this->set_custom_other($backup_options,$options);
            }
        }

        return $backup_options;

    }

    public function set_mu_db($site_id,$backup_options,$options)
    {
        global $wpdb;

        $site_prefix= $wpdb->get_blog_prefix($site_id);
        $backup_data=array();
        $backup_data['key']='backup_mu_site_db';
        $backup_data['result']=false;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;
        $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_CUSTOM;
        if(isset($options['mu_setting']['exclude_tables']))
        {
            $backup_data['exclude_tables']=$options['mu_setting']['exclude_tables'];
        }
        else
        {
            $backup_data['exclude_tables']=array();
        }
        $backup_data['dump_db']=1;
        $backup_data['sql_file_name']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_options['dir'].DIRECTORY_SEPARATOR.$backup_options['prefix']. '_mu_site_backup_db.sql';
        $backup_data['json_info']['dump_db']=1;
        $backup_data['json_info']['file_type']='databases';
        //$backup_data['json_info']['is_mu']=1;
        $backup_data['json_info']['site_id']=$site_id;
        $backup_data['json_info']['home_url']=get_home_url($site_id);
        $backup_data['json_info']['site_url']=get_site_url($site_id);
        $backup_data['json_info']['blog_prefix']=$site_prefix;
        $backup_data['json_info']['mu_migrate']=1;
        $backup_data['json_info']['base_prefix']=$wpdb->get_blog_prefix(0);
        $backup_data['blog_prefix']=$site_prefix;
        $backup_data['mu_migrate']=1;
        $backup_data['site_id']=$site_id;
        $backup_data['is_main_site']=is_main_site($site_id);
        //site_id
        $backup_data['prefix'] = $backup_options['prefix'] . '_mu_site_backup_db';

        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);

        return $backup_data;
    }

    public function set_mu_uploads($site_id,$backup_options,$options)
    {
        $backup_data=array();
        $backup_data['key']='backup_mu_site_uploads';
        $backup_data['result']=false;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;

        $backup_data['prefix'] = $backup_options['prefix'] . '_mu_site_backup_uploads';
        $upload_dir = $this->get_site_upload_dir($site_id);
        $backup_data['root_flag']='uploads';
        $backup_data['root_path']=$this -> transfer_path($upload_dir['basedir']);
        $backup_data['files_root']=$this -> transfer_path($upload_dir['basedir']);
        $exclude_regex=array();

        $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,'backup_custom_uploads');
        $backup_data['exclude_regex']=$exclude_regex;
        if(is_main_site($site_id))
        {
            $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.'sites'), '/').'#';
        }
        if(isset($options['exclude_uploads']))
        {
            foreach ($options['exclude_uploads'] as $uploads)
            {
                $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.$uploads), '/').'#';
            }
        }
        $backup_data['exclude_files_regex']=array();
        $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],'backup_custom_uploads');
        if(isset($options['exclude_uploads_files']))
        {
            foreach ($options['exclude_uploads_files'] as $file)
            {
                $backup_data['exclude_files_regex'][]='#'.$file.'#';
            }
        }

        $backup_data['include_regex']=array();
        $backup_data['json_info']['file_type']='upload';
        $backup_data['json_info']['site_id']=$site_id;
        $backup_data['json_info']['home_url']=get_home_url($site_id);
        $backup_data['json_info']['site_url']=get_site_url($site_id);
        //$backup_data['json_info']['is_mu']=1;
        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
        return $backup_data;
    }

    public function set_custom_themes($backup_options,$options)
    {
        $backup_data=array();
        $backup_data['key']='backup_custom_themes';
        $backup_data['result']=false;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;

        $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT;
        $backup_data['prefix'] = $backup_options['prefix'] . '_backup_themes';
        $backup_data['files_root']=$this->transfer_path(get_theme_root());
        $backup_data['exclude_regex']=array();

        if(isset($options['exclude_themes']))
        {
            foreach ($options['exclude_themes'] as $themes)
            {
                $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'$#';
            }
        }

        $backup_data['include_regex']=array();
        if(isset($options['include_themes']))
        {
            foreach ($options['include_themes'] as $themes)
            {
                $backup_data['include_regex'][]='#^'.preg_quote($this -> transfer_path(get_theme_root().DIRECTORY_SEPARATOR.$themes), '/').'#';
            }
        }

        $backup_data['json_info']['file_type']='themes';
        if(isset($options['include_themes']))
        {
            $backup_data['json_info']['themes']=$this->get_themes_list($options['mu_setting']['exclude_themes']);
        }
        else
        {
            $backup_data['json_info']['themes']=$this->get_themes_list(array());
        }

        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
        return $backup_data;
    }

    public function set_custom_plugins($backup_options,$options)
    {
        $backup_data=array();
        $backup_data['key']='backup_custom_plugin';
        $backup_data['result']=false;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;

        if(isset($backup_data['compress']['subpackage_plugin_upload'])&&$backup_data['compress']['subpackage_plugin_upload'])
        {
            $backup_data['plugin_subpackage']=1;
        }
        $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT;
        $backup_data['prefix']=$backup_options['prefix'].'_backup_plugin';
        $backup_data['files_root']=$this->transfer_path(WP_PLUGIN_DIR);

        if(isset($options['exclude_plugins']))
        {
            $exclude_plugins=$options['exclude_plugins'];
        }
        else
        {
            $exclude_plugins=array();
        }

        $exclude_plugins=apply_filters('wpvivid_exclude_plugins',$exclude_plugins);
        $exclude_regex=array();
        foreach ($exclude_plugins as $exclude_plugin)
        {
            $exclude_regex[]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$exclude_plugin), '/').'#';
        }

        $backup_data['exclude_regex']=$exclude_regex;
        if(isset($options['exclude_plugins']))
        {
            foreach ($options['exclude_plugins'] as $plugins)
            {
                $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
            }
        }

        $backup_data['include_regex']=array();
        $include_plugins_array = array();
        if(isset($options['include_plugins']))
        {
            $include_plugins_array = $options['include_plugins'];
            foreach ($options['include_plugins'] as $plugins)
            {
                $backup_data['include_regex'][]='#^'.preg_quote($this -> transfer_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugins), '/').'#';
            }
        }

        $backup_data['json_info']['file_type']='plugin';
        $backup_data['json_info']['plugin']=$this->get_plugins_list($exclude_plugins,$include_plugins_array);
        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
        return $backup_data;
    }

    public function set_custom_uploads($backup_options,$options)
    {
        $backup_data=array();
        $backup_data['result']=false;
        $backup_data['key']='backup_custom_uploads';
        $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_CONTENT;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;

        $backup_data['prefix'] = $backup_options['prefix'] . '_backup_uploads';
        $upload_dir = wp_upload_dir();
        $backup_data['files_root']=$this -> transfer_path($upload_dir['basedir']);
        $exclude_regex=array();
        $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,'backup_custom_uploads');
        $backup_data['exclude_regex']=$exclude_regex;
        $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.'sites'), '/').'#';
        if(isset($options['exclude_uploads']))
        {
            foreach ($options['exclude_uploads'] as $uploads)
            {
                $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path($upload_dir['basedir'].DIRECTORY_SEPARATOR.$uploads), '/').'#';
            }
        }
        $backup_data['exclude_files_regex']=array();
        $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],'backup_custom_uploads');
        if(isset($options['exclude_uploads_files']))
        {
            foreach ($options['exclude_uploads_files'] as $file)
            {
                $backup_data['exclude_files_regex'][]='#'.$file.'#';
            }
        }

        $backup_data['include_regex']=array();
        $backup_data['json_info']['file_type']='upload';
        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
        return $backup_data;
    }

    public function set_custom_content($backup_options,$options)
    {
        $backup_data=array();
        $backup_data['result']=false;
        $backup_data['key']='backup_custom_content';
        $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_ROOT;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['prefix'] = $backup_options['prefix'] . '_backup_content';
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;

        $backup_data['files_root']=$this -> transfer_path(WP_CONTENT_DIR);
        $exclude_regex=array();
        $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,'backup_custom_content');
        $backup_data['exclude_regex']=$exclude_regex;
        $backup_data['include_regex']=array();
        $backup_data['exclude_files_regex']=array();
        $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],'backup_custom_content');

        if(isset($options['exclude_content']))
        {
            foreach ($options['exclude_content'] as $uploads)
            {
                $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$uploads), '/').'#';
            }
        }

        if(isset($options['exclude_content_files']))
        {
            foreach ($options['exclude_content_files'] as $file)
            {
                $backup_data['exclude_files_regex'][]='#'.$file.'#';
            }
        }

        $backup_data['json_info']['file_type']='wp-content';
        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
        return $backup_data;
    }

    public function set_custom_other($backup_options,$options)
    {
        $backup_data=array();
        $backup_data['result']=false;
        $backup_data['key']='backup_custom_other';
        $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_ROOT;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['custom_other']=1;
        $backup_data['prefix'] = $backup_options['prefix'] . '_backup_other';
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;

        if(!function_exists('get_home_path'))
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        $backup_data['files_root']=$this -> transfer_path(get_home_path());
        $backup_data['exclude_regex']=array();
        $backup_data['include_regex']=array();
        $backup_data['exclude_files_regex']=array();

        if(isset($options['exclude_custom_other']))
        {
            foreach ($options['exclude_custom_other'] as $other)
            {
                $backup_data['exclude_regex'][]='#^'.preg_quote($this -> transfer_path(get_home_path().$other), '/').'#';
            }
        }

        if(isset($options['exclude_custom_other_files']))
        {
            foreach ($options['exclude_custom_other_files'] as $file)
            {
                $backup_data['exclude_files_regex'][]='#'.$file.'#';
            }
        }

        if(isset($options['custom_other_root']))
        {
            foreach ($options['custom_other_root'] as $other)
            {
                $backup_data['custom_other_root'][]=$this -> transfer_path(get_home_path().$other);
            }
        }
        $backup_data['json_info']['file_type']='custom';
        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
        return $backup_data;
    }

    public function set_custom_core($backup_options,$options)
    {
        $backup_data=array();
        $backup_data['result']=false;
        $backup_data['key']='backup_custom_core';
        $backup_data['root_flag']=WPVIVID_PRO_BACKUP_ROOT_WP_ROOT;
        $backup_data['compress']=$backup_options['compress'];
        $backup_data['finished']=0;
        $backup_data['prefix'] = $backup_options['prefix'] . '_backup_core';
        $backup_data['path']=WP_CONTENT_DIR.DIRECTORY_SEPARATOR. $backup_options['dir'].DIRECTORY_SEPARATOR;
        if(!function_exists('get_home_path'))
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        $backup_data['files_root']=$this -> transfer_path(get_home_path());
        $backup_data['json_info']['include_path'][]='wp-includes';
        $backup_data['json_info']['include_path'][]='wp-admin';
        $backup_data['json_info']['include_path'][]='lotties';
        $backup_data['json_info']['wp_core']=1;
        $backup_data['json_info']['home_url']=home_url();

        $include_regex[]='#^'.preg_quote($this -> transfer_path(get_home_path().'wp-admin'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(get_home_path().'wp-includes'), '/').'#';
        $include_regex[]='#^'.preg_quote($this->transfer_path(get_home_path().'lotties'), '/').'#';
        $exclude_regex=array();
        $exclude_regex=apply_filters('wpvivid_get_backup_exclude_regex',$exclude_regex,'backup_custom_core');
        $backup_data['exclude_regex']=$exclude_regex;
        $backup_data['include_regex']=$include_regex;
        $backup_data['exclude_files_regex']=array();
        $backup_data['exclude_files_regex']=apply_filters('wpvivid_get_backup_exclude_files_regex',$backup_data['exclude_files_regex'],'backup_custom_core');
        $backup_data['json_info']['file_type']='wp-core';
        $backup_data=apply_filters('wpvivid_custom_set_backup',$backup_data,$options);
        return $backup_data;
    }

    public function get_site_upload_dir($site_id, $time = null, $create_dir = true, $refresh_cache = false)
    {
        static $cache = array(), $tested_paths = array();

        $key = sprintf( '%d-%s',$site_id, (string) $time );

        if ( $refresh_cache || empty( $cache[ $key ] ) ) {
            $cache[ $key ] = $this->_wp_upload_dir( $site_id,$time );
        }

        /**
         * Filters the uploads directory data.
         *
         * @since 2.0.0
         *
         * @param array $uploads Array of upload directory data with keys of 'path',
         *                       'url', 'subdir, 'basedir', and 'error'.
         */
        $uploads = apply_filters( 'upload_dir', $cache[ $key ] );

        if ( $create_dir ) {
            $path = $uploads['path'];

            if ( array_key_exists( $path, $tested_paths ) ) {
                $uploads['error'] = $tested_paths[ $path ];
            } else {
                if ( ! wp_mkdir_p( $path ) ) {
                    if ( 0 === strpos( $uploads['basedir'], ABSPATH ) ) {
                        $error_path = str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir'];
                    } else {
                        $error_path = basename( $uploads['basedir'] ) . $uploads['subdir'];
                    }

                    $uploads['error'] = sprintf(
                    /* translators: %s: directory path */
                        __( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
                        esc_html( $error_path )
                    );
                }

                $tested_paths[ $path ] = $uploads['error'];
            }
        }

        return $uploads;
    }

    public function _wp_upload_dir($site_id, $time = null ) {
        $siteurl     = get_option( 'siteurl' );
        $upload_path = trim( get_option( 'upload_path' ) );

        if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
            $dir = WP_CONTENT_DIR . '/uploads';
        } elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
            // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
            $dir = path_join( ABSPATH, $upload_path );
        } else {
            $dir = $upload_path;
        }

        if ( ! $url = get_option( 'upload_url_path' ) ) {
            if ( empty( $upload_path ) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) ) {
                $url = WP_CONTENT_URL . '/uploads';
            } else {
                $url = trailingslashit( $siteurl ) . $upload_path;
            }
        }

        /*
         * Honor the value of UPLOADS. This happens as long as ms-files rewriting is disabled.
         * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
         */
        if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
            $dir = ABSPATH . UPLOADS;
            $url = trailingslashit( $siteurl ) . UPLOADS;
        }

        // If multisite (and if not the main site in a post-MU network)
        if ( is_multisite() && ! ( is_main_network() && is_main_site($site_id) && defined( 'MULTISITE' ) ) ) {
            if ( ! get_site_option( 'ms_files_rewriting' ) ) {
                /*
                 * If ms-files rewriting is disabled (networks created post-3.5), it is fairly
                 * straightforward: Append sites/%d if we're not on the main site (for post-MU
                 * networks). (The extra directory prevents a four-digit ID from conflicting with
                 * a year-based directory for the main site. But if a MU-era network has disabled
                 * ms-files rewriting manually, they don't need the extra directory, as they never
                 * had wp-content/uploads for the main site.)
                 */

                if ( defined( 'MULTISITE' ) ) {
                    $ms_dir = '/sites/' . $site_id;
                } else {
                    $ms_dir = '/' . $site_id;
                }

                $dir .= $ms_dir;
                $url .= $ms_dir;
            } elseif ( defined( 'UPLOADS' ) && ! ms_is_switched() ) {
                /*
                 * Handle the old-form ms-files.php rewriting if the network still has that enabled.
                 * When ms-files rewriting is enabled, then we only listen to UPLOADS when:
                 * 1) We are not on the main site in a post-MU network, as wp-content/uploads is used
                 *    there, and
                 * 2) We are not switched, as ms_upload_constants() hardcodes these constants to reflect
                 *    the original blog ID.
                 *
                 * Rather than UPLOADS, we actually use BLOGUPLOADDIR if it is set, as it is absolute.
                 * (And it will be set, see ms_upload_constants().) Otherwise, UPLOADS can be used, as
                 * as it is relative to ABSPATH. For the final piece: when UPLOADS is used with ms-files
                 * rewriting in multisite, the resulting URL is /files. (#WP22702 for background.)
                 */

                if ( defined( 'BLOGUPLOADDIR' ) ) {
                    $dir = untrailingslashit( BLOGUPLOADDIR );
                } else {
                    $dir = ABSPATH . UPLOADS;
                }
                $url = trailingslashit( $siteurl ) . 'files';
            }
        }

        $basedir = $dir;
        $baseurl = $url;

        $subdir = '';
        if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
            // Generate the yearly and monthly dirs
            if ( ! $time ) {
                $time = current_time( 'mysql' );
            }
            $y      = substr( $time, 0, 4 );
            $m      = substr( $time, 5, 2 );
            $subdir = "/$y/$m";
        }

        $dir .= $subdir;
        $url .= $subdir;

        return array(
            'path'    => $dir,
            'url'     => $url,
            'subdir'  => $subdir,
            'basedir' => $basedir,
            'baseurl' => $baseurl,
            'error'   => false,
        );
    }

    public function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode(DIRECTORY_SEPARATOR,$values);
    }

    public function get_plugins_list($exclude_plugins,$include_plugins=array())
    {
        if(!empty($include_plugins))
        {
            $plugins_list=array();
            if(!function_exists('get_plugins'))
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $list=get_plugins();

            foreach ($list as $key=>$item)
            {
                if(in_array(dirname($key),$include_plugins))
                {
                    $plugins_list[dirname($key)]['slug']=dirname($key);
                    $plugins_list[dirname($key)]['size']=self::get_folder_size(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.dirname($key),0);
                }
            }
            return $plugins_list;
        }
        else
        {
            $plugins_list=array();
            if(!function_exists('get_plugins'))
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $list=get_plugins();

            $exclude_plugins=array();
            $exclude_plugins=apply_filters('wpvivid_exclude_plugins',$exclude_plugins);

            foreach ($list as $key=>$item)
            {
                if(in_array(dirname($key),$exclude_plugins))
                {
                    continue;
                }
                $plugins_list[dirname($key)]['slug']=dirname($key);
                $plugins_list[dirname($key)]['size']=$this->get_folder_size(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.dirname($key),0);
            }
            return $plugins_list;
        }
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

    public function get_themes_list($exclude_themes)
    {
        $themes_list=array();
        $list=wp_get_themes();
        foreach ($list as $key=>$item)
        {
            if(in_array($key,$exclude_themes))
            {
                continue;
            }
            $themes_list[$key]['slug']=$key;
            $themes_list[$key]['size']=$this->get_folder_size(get_theme_root().DIRECTORY_SEPARATOR.$key,0);
        }
        return $themes_list;
    }

    //old
    public function mu_site_setting($mu_single_list_id, $mu_single_custom_id, $type, $is_get_size)
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
        <div class="wpvivid_single_site_step2">
            <div id="wpvivid_custom_mu_single_list">
                <?php
                /*$custom_mu_list = new WPvivid_MU_Single_Site_Custom_List();
                $custom_mu_list ->set_parent_id('wpvivid_custom_mu_single_list', $type);
                $custom_mu_list ->display_rows();
                $custom_mu_list ->load_js();*/

                $custom_backup_manager = new WPvivid_Custom_Backup_Manager($mu_single_custom_id, $type, $is_get_size, '1');
                $custom_backup_manager->output_custom_backup_table();
                $custom_backup_manager->load_js();
                ?>
                <br>
            </div>
        </div>

        <script>
            var archieve_info = {};
            archieve_info.db_retry    = 0;
            archieve_info.theme_retry = 0;

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.first-page',function() {
                wpvivid_get_mu_single_list('first', '<?php echo $mu_single_list_id; ?>', '<?php echo $mu_single_custom_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_mu_single_list(page-1, '<?php echo $mu_single_list_id; ?>', '<?php echo $mu_single_custom_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_mu_single_list(page+1, '<?php echo $mu_single_list_id; ?>', '<?php echo $mu_single_custom_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("click",'.last-page',function() {
                wpvivid_get_mu_single_list('last', '<?php echo $mu_single_list_id; ?>', '<?php echo $mu_single_custom_id; ?>');
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid_mu_single_site_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_get_mu_single_list(page, '<?php echo $mu_single_list_id; ?>', '<?php echo $mu_single_custom_id; ?>');
                }
            });

            jQuery('#<?php echo $mu_single_list_id; ?>').find('.wpvivid-mu-single-search-submit').click(function()
            {
                wpvivid_get_single_mu_site_list('<?php echo $mu_single_list_id; ?>', '<?php echo $mu_single_custom_id; ?>');
            });

            function wpvivid_get_single_mu_site_list(mu_single_list_id, mu_single_custom_id)
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
                            jQuery('#'+mu_single_custom_id).find('.wpvivid_single_site_step1').show();
                            jQuery('#'+mu_single_custom_id).find('.wpvivid_single_site_step2').show();
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
                    site_id=jQuery(this).val();
                    wpvivid_get_mu_custom_themes_plugins_info(site_id);
                    return false;
                });
            }

            function wpvivid_get_mu_single_list(page, mu_single_list_id, mu_single_custom_id)
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
                            jQuery('#'+mu_single_custom_id).find('.wpvivid_single_site_step1').show();
                            jQuery('#'+mu_single_custom_id).find('.wpvivid_single_site_step2').show();
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
                var site_id;
                site_id=jQuery(this).val();

                wpvivid_get_mu_custom_themes_plugins_info(site_id);
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
                jQuery('#<?php echo $mu_single_custom_id; ?>').find('.wpvivid_single_site_step1').hide();
                jQuery('#<?php echo $mu_single_custom_id; ?>').find('.wpvivid_single_site_step2').hide();
            });
        </script>
        <?php
    }
}