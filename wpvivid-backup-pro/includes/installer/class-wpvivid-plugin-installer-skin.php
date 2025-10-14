<?php

if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Plugin_Installer_Skin extends WP_Upgrader_Skin
{
    public $api;
    public $type;

    public $progress_string;

    public function __construct( $args = array() )
    {
        $defaults = array(
            'type'   => 'web',
            'url'    => '',
            'plugin' => '',
            'nonce'  => '',
            'title'  => '',
        );

        $this->progress_string=array(
          'unpack_package'=>20,
          'installing_package'=>60,
        );

        $args     = wp_parse_args( $args, $defaults );

        $this->type = $args['type'];
        $this->api  = isset( $args['api'] ) ? $args['api'] : array();

        parent::__construct( $args );
    }

    public function header()
    {
        return;
    }

    public function footer()
    {
        return;
    }

    public function feedback( $string ,...$args)
    {
        if ( isset( $this->progress_string[ $string ] ) )
        {
            $progress=$this->progress_string[ $string ];
        }
        else
        {
            $progress=false;
        }

        if ( isset( $this->upgrader->strings[ $string ] ) )
        {
            $string = $this->upgrader->strings[ $string ];
        }

        if ( strpos( $string, '%' ) !== false ) {
            $args = func_get_args();
            $args = array_splice( $args, 1 );
            if ( $args ) {
                $args   = array_map( 'strip_tags', $args );
                $args   = array_map( 'esc_html', $args );
                $string = vsprintf( $string, $args );
            }
        }
        if ( empty( $string ) ) {
            return;
        }

        $this->show_message( $string );

        if($progress)
        {
            $this->progress($progress);
        }
    }

    public function show_message($message)
    {
        if ( is_wp_error( $message ) ) {
            if ( $message->get_error_data() && is_string( $message->get_error_data() ) ) {
                $message = $message->get_error_message() . ': ' . $message->get_error_data();
            } else {
                $message = $message->get_error_message();
            }
        }
        echo '<script> jQuery("#wpvivid_plugin_progress_text").html("'.$message.'");</script>';

        wp_ob_end_flush_all();
        flush();
    }

    public function progress($progress)
    {
        $html="<span class='wpvivid-span-processed-progress' style='width:$progress%;'>$progress% completed</span>";
        echo '<script> jQuery("#wpvivid_plugin_progress").html("'.$html.'");</script>';
        wp_ob_end_flush_all();
        flush();
    }

    public function before()
    {
        $this->progress(20);
    }

    /**
     */
    public function after()
    {
        $this->progress(100);
    }
}