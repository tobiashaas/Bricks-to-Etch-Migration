<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Dashboard_Connect_server
{
    private $url='https://wpvivid.com/wc-api/wpvivid_api';
    private $update_url='https://download.wpvivid.com';
    private $direct_url='https://pro.wpvivid.com/wc-api/wpvivid_api';
    private $direct_update_url='https://download2.wpvivid.com';

    public function login($user_info,$encrypt_user_info,$retry=true,$get_key=false)
    {
        if($get_key)
            $public_key='';
        else
            $public_key=get_option('wpvivid_connect_key','');

        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key,'no');
        }

        $crypt=new WPvivid_Dashboard_Crypt($public_key);

        if($encrypt_user_info)
        {
            $user_info=$crypt->encrypt_user_token($user_info);
            $user_info=base64_encode($user_info);
        }


        $crypt->generate_key();

        $json['user_info']=$user_info;

        $json['domain'] = $this->get_domain();
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $action='get_dashboard_status';
        if(get_option('wpvivid_use_direct_connect_url', false))
        {
            $url=$this->direct_url;
        }
        else
        {
            $url=$this->url;
        }
        $url.='?request='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));

        $ret=$this->remote_request($url, $retry);
        if($ret['result']=='success')
        {
            if($encrypt_user_info)
            {
                $ret['user_info']=$user_info;
            }
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function login_direct($user_info,$encrypt_user_info,$retry=true,$get_key=false)
    {
        if($get_key)
            $public_key='';
        else
            $public_key=get_option('wpvivid_connect_key','');

        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key,'no');
        }

        $crypt=new WPvivid_Dashboard_Crypt($public_key);

        if($encrypt_user_info)
        {
            $user_info=$crypt->encrypt_user_token($user_info);
            $user_info=base64_encode($user_info);
        }


        $crypt->generate_key();

        $json['user_info']=$user_info;

        $json['domain'] = $this->get_domain();
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $action='get_dashboard_status';
        $url=$this->direct_url;
        $url.='?request='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));

        $ret=$this->remote_request($url, $retry);
        if($ret['result']=='success')
        {
            if($encrypt_user_info)
            {
                update_option('wpvivid_use_direct_connect_url', true);
                $ret['user_info']=$user_info;
            }
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function active_site($user_info,$encrypt_user_info=false)
    {
        $public_key=get_option('wpvivid_connect_key','');

        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key,'no');
        }

        $crypt=new WPvivid_Dashboard_Crypt($public_key);

        if($encrypt_user_info)
        {
            $user_info=$crypt->encrypt_user_token($user_info);
            $user_info=base64_encode($user_info);
        }

        $crypt->generate_key();

        $json['user_info']=$user_info;

        $json['domain'] = $this->get_domain();
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);
        $action='active_dashboard_site';
        if(get_option('wpvivid_use_direct_connect_url', false))
        {
            $url=$this->direct_url;
        }
        else
        {
            $url=$this->url;
        }
        $url.='?request='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));
        $options=array();
        $options['timeout']=30;

        $ret=$this->remote_request($url);

        if($ret['result']=='success')
        {
            if($encrypt_user_info)
            {
                $ret['user_info']=$user_info;
            }

            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function install_addon($user_info,$slug,$folder)
    {
        $public_key=get_option('wpvivid_connect_key','');
        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key,'no');
        }

        $crypt=new WPvivid_Dashboard_Crypt($public_key);

        $crypt->generate_key();

        $json['user_info']=$user_info;

        $json['domain'] = $this->get_domain();
        $json['slug']=$slug;
        $json['install_addon']=1;
        $json=json_encode($json);

        $data=$crypt->encrypt_message($json);

        if(get_option('wpvivid_use_direct_connect_url', false))
        {
            $url=$this->direct_update_url;
        }
        else
        {
            $url=$this->update_url;
        }
        $data=base64_encode($data);

        $body['data']=$data;
        $ret=$this->remote_post($url,$body);

        if($ret['result']=='success')
        {
            $data=base64_decode($ret['data']);
            $data=$crypt->decrypt_message($data);
            $params=json_decode($data,1);
            if(is_null($params))
            {
                $ret['result']='failed';
                $ret['error']='Dectypting data failed. Please try again later.';
                return $ret;
            }
            else
            {
                if($params['result']=='success')
                {
                    $pro_plugin_path=WPVIVID_BACKUP_PRO_PLUGIN_DIR. $folder;
                    if(!file_exists($pro_plugin_path))
                    {
                        @mkdir($pro_plugin_path,0777,true);
                    }

                    $path=$pro_plugin_path.'/' .$params['file_name'];
                    @unlink($path);
                    file_put_contents($path,base64_decode($params['content']));

                    if (!class_exists('WPvivid_PclZip'))
                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';
                    $archive = new WPvivid_PclZip($path);

                    $zip_ret = $archive->extract(WPVIVID_PCLZIP_OPT_PATH,dirname($path),WPVIVID_PCLZIP_OPT_REPLACE_NEWER);
                    if(!$zip_ret)
                    {
                        $ret['result']='failed';
                        $ret['error'] = $archive->errorInfo(true);
                    }
                    else
                    {
                        @unlink($path);
                        $ret['result']='success';
                    }
                    return $ret;
                }
                else
                {
                    return $params;
                }
            }
        }
        else
        {
            return $ret;
        }
    }

    public function update_dashboard($user_info)
    {
        $public_key=get_option('wpvivid_connect_key','');
        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key,'no');
        }

        $crypt=new WPvivid_Dashboard_Crypt($public_key);

        $crypt->generate_key();

        $json['user_info']=$user_info;

        $json['domain'] = $this->get_domain();
        $json['update_dashboard']=1;
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        if(get_option('wpvivid_use_direct_connect_url', false))
        {
            $url=$this->direct_update_url;
        }
        else
        {
            $url=$this->update_url;
        }
        $data=base64_encode($data);
        $body['data']=$data;
        $ret=$this->remote_post($url,$body,120);

        if($ret['result']=='success')
        {
            $data=base64_decode($ret['data']);
            $data=$crypt->decrypt_message($data);

            $params=json_decode($data,1);
            if(is_null($params))
            {
                $ret['result']='failed';
                $ret['error']='Dectypting data failed. Please try again later.';
                return $ret;
            }
            else
            {
                if($params['result']=='success')
                {
                    $dashboard_plugin_path=WPVIVID_BACKUP_PRO_PLUGIN_DIR;
                    $path=$dashboard_plugin_path.$params['file_name'];
                    @unlink($path);
                    file_put_contents($path,base64_decode($params['content']));

                    if (!class_exists('WPvivid_PclZip'))
                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/zip/class-wpvivid-pclzip.php';
                    $archive = new WPvivid_PclZip($path);

                    $zip_ret = $archive->extract(WPVIVID_PCLZIP_OPT_PATH,dirname($path),WPVIVID_PCLZIP_OPT_REPLACE_NEWER,WPVIVID_PCLZIP_OPT_REMOVE_PATH,'wpvivid-backup-pro');
                    if(!$zip_ret)
                    {
                        $ret['result']='failed';
                        $ret['error'] = $archive->errorInfo(true);
                    }
                    else
                    {
                        @unlink($path);
                        $ret['result']='success';
                    }
                    return $ret;
                }
                else
                {
                    return $params;
                }
            }
        }
        else
        {
            return $ret;
        }
    }

    public function download_package($user_info,$folder,$des)
    {
        $public_key=get_option('wpvivid_connect_key','');
        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key,'no');
        }

        $crypt=new WPvivid_Dashboard_Crypt($public_key);

        $crypt->generate_key();

        $json['user_info']=$user_info;

        $json['domain'] = $this->get_domain();
        $json['slug']=$folder;
        $json['download_package']=1;
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        if(get_option('wpvivid_use_direct_connect_url', false))
        {
            $url=$this->direct_update_url;
        }
        else
        {
            $url=$this->update_url;
        }
        $data=base64_encode($data);

        $body['data']=$data;
        $ret=$this->remote_post($url,$body,120);

        if($ret['result']=='success')
        {
            $data=base64_decode($ret['data']);
            $data=$crypt->decrypt_message($data);

            $params=json_decode($data,1);
            if(is_null($params))
            {
                $ret['result']='failed';
                $ret['error']='Dectypting data failed. Please try again later.';
                $ret['data']=$body['data'];
                return $ret;
            }
            else
            {
                $dashboard_plugin_path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'wpvivid_dashboard_cache'.DIRECTORY_SEPARATOR;

                $path=$dashboard_plugin_path.$des.DIRECTORY_SEPARATOR.$params['file_name'];
                if(file_exists($path))
                {
                    @unlink($path);
                }

                file_put_contents($path,base64_decode($params['content']));

                $ret['result']='success';

                return $ret;
            }
        }
        else
        {
            return $ret;
        }
    }

    public function get_key()
    {
        $options=array();
        $options['timeout']=30;
        $options['sslverify']=false;
        if(get_option('wpvivid_use_direct_connect_url', false))
        {
            $url=$this->direct_url;
        }
        else
        {
            $url=$this->url;
        }
        $request=wp_remote_request($url.'?request=get_key',$options);

        if(!is_wp_error($request) && ($request['response']['code'] == 200))
        {
            $json= wp_remote_retrieve_body($request);
            $body=json_decode($json,true);
            if(is_null($body))
            {
                return false;
            }

            if($body['result']=='success')
            {
                $public_key=base64_decode($body['public_key']);
                if($public_key==null)
                {
                    return false;
                }
                else
                {
                    return $public_key;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            if(is_wp_error($request))
            {
                $error_messages = $request->get_error_messages();
                $error_msg = implode(', ', $error_messages);
                if (preg_match('/cURL error 28/i', $error_msg))
                {
                    $direct_ret=$this->direct_get_key();
                    return $direct_ret;
                }
            }

            return false;
        }
    }

    public function direct_get_key()
    {
        $options=array();
        $options['timeout']=30;
        $options['sslverify']=false;
        $url=$this->direct_url;
        $request=wp_remote_request($url.'?request=get_key',$options);

        if(!is_wp_error($request) && ($request['response']['code'] == 200))
        {
            $json= wp_remote_retrieve_body($request);
            $body=json_decode($json,true);
            if(is_null($body))
            {
                return false;
            }

            if($body['result']=='success')
            {
                $public_key=base64_decode($body['public_key']);
                if($public_key==null)
                {
                    return false;
                }
                else
                {
                    update_option('wpvivid_use_direct_connect_url', true);
                    return $public_key;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    public function remote_request($url,$retry=true,$body=array())
    {
        $options=array();
        $options['timeout']=30;
        $options['sslverify']=false;
        if(empty($options['body']))
        {
            $options['body']=$body;
        }

        $retry_times=0;
        $max_retry_times=3;

        $ret['result']='failed';
        $ret['error']='remote request failed';

        while($retry_times<$max_retry_times)
        {
            $request=wp_remote_request($url,$options);

            if(!is_wp_error($request) && ($request['response']['code'] == 200))
            {
                $json= wp_remote_retrieve_body($request);
                $body=json_decode($json,true);

                if(is_null($body))
                {
                    $ret['result']='failed';
                    $ret['error']='Decoding json failed. Please try again later.';
                }

                if(isset($body['result'])&&$body['result']=='success')
                {
                    return $body;
                }
                else
                {
                    if(isset($body['result'])&&$body['result']=='failed')
                    {
                        $ret['result']='failed';
                        $ret['error']=$body['error'];
                        if(isset($body['error_code']))
                        {
                            $ret['error_code']=$body['error_code'];
                        }
                    }
                    else if(isset($body['error']))
                    {
                        $ret['result']='failed';
                        $ret['error']=$body['error'];
                        if(isset($body['error_code']))
                        {
                            $ret['error_code']=$body['error_code'];
                        }
                    }
                    else
                    {
                        $ret['result']='failed';
                        $ret['error']='login failed';
                    }
                }
            }
            else
            {
                $ret['result']='failed';
                if ( is_wp_error( $request ) )
                {
                    $error_message = $request->get_error_message();
                    $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
                }
                else if($request['response']['code'] != 200)
                {
                    $ret['error']=$request['response']['message'];
                }
                else {
                    $ret['error']=$request;
                }
            }

            if($retry)
            {
                $retry_times++;
            }
            else
            {
                $retry_times+=3;
            }
        }


        return $ret;
    }

    public function remote_post($url,$body=array(),$timeout=120)
    {
        $options=array();
        $options['timeout']=$timeout;
        $options['sslverify']=false;
        if(empty($options['body']))
        {
            $options['body']=$body;
        }

        $retry=0;
        $max_retry=3;

        $ret['result']='failed';
        $ret['error']='remote request failed';

        while($retry<$max_retry)
        {
            $request=wp_remote_post($url,$options);
            if(!is_wp_error($request) && ($request['response']['code'] == 200))
            {
                $json= wp_remote_retrieve_body($request);
                $body=json_decode($json,true);

                if(is_null($body))
                {
                    $ret['result']='failed';
                    $ret['error']=$json;
                }

                if(!isset($body['data']) && isset($body['result']) && $body['result'] == 'failed' && isset($body['error']) && $body['error'] == 'not allowed')
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'need_reactive';
                    return $ret;
                }

                if(isset($body['result'])&&$body['result']=='success')
                {
                    return $body;
                }
                else if(isset($body['result'])&&$body['result']=='failed')
                {
                    $ret['result']='failed';
                    $ret['error']=$body['error'];
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='empty body';
                }
            }
            else
            {
                $ret['result']='failed';
                if ( is_wp_error( $request ) )
                {
                    $error_message = $request->get_error_message();
                    $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
                }
                else if($request['response']['code'] != 200)
                {
                    $ret['error']=$request['response']['message'];
                }
                else {
                    $ret['error']=$request;
                }
            }

            $retry++;
        }

        return $ret;
    }

    public function clear_destination($path)
    {
        if( ! function_exists('plugins_api') )
        {
            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        }

        if(!class_exists('WP_Upgrader'))
            require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

        //require_once( ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php' );
        if(!class_exists('Plugin_Upgrader'))
            require_once( ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php' );

        WP_Filesystem();
        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $upgrader->clear_destination($path);

    }

    public function get_download_link()
    {
        $info= get_option('wpvivid_pro_user',false);
        if($info===false||!isset($info['token']))
        {
            return '';
        }
        else
        {
            global $wpvivid_backup_pro;

            $user_info=$info['token'];
            $public_key=get_option('wpvivid_connect_key','');
            if(empty($public_key))
            {
                return '';
            }
            $crypt=new WPvivid_Dashboard_Crypt($public_key);
            $crypt->generate_key();
            $json['user_info']=$user_info;

            $json['domain'] = $this->get_domain();
            $json['update']=1;
            $json['addons']=$wpvivid_backup_pro->addons_loader->get_addons();

            $json=json_encode($json);

            $data=$crypt->encrypt_message($json);

            if(get_option('wpvivid_use_direct_connect_url', false))
            {
                $url='https://update2.wpvivid.com';
            }
            else
            {
                $url='https://update.wpvivid.com';
            }
            $url.='?data='.rawurlencode(base64_encode($data));

            return $url;
        }
    }

    public function get_domain()
    {
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

        $domain=apply_filters('wpvivid_get_login_domain',$domain);

        return strtolower($domain);
    }
}