<?php
/**
 * Transfer Manager for Bricks to Etch Migration Plugin
 * 
 * Handles chunked data transfer with retry logic
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Transfer_Manager {
    
    /**
     * Chunk size for post transfer
     */
    private $post_chunk_size = 5;
    
    /**
     * Maximum retry attempts
     */
    private $max_retries = 3;
    
    /**
     * Retry delay in seconds (will be multiplied by attempt number)
     */
    private $retry_delay = 2;
    
    /**
     * API client instance
     */
    private $api_client;
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = new B2E_API_Client();
        $this->error_handler = new B2E_Error_Handler();
    }
    
    /**
     * Send data with retry logic
     */
    public function send_with_retry($endpoint, $data, $api_key) {
        $attempt = 0;
        $last_error = null;
        
        while ($attempt < $this->max_retries) {
            $attempt++;
            
            try {
                $response = $this->api_client->send_data($endpoint, $data, $api_key);
                
                if (!is_wp_error($response)) {
                    return $response; // Success
                }
                
                $last_error = $response;
                
                // Don't retry on authentication errors
                if ($response->get_error_code() === 'unauthorized') {
                    break;
                }
                
            } catch (Exception $e) {
                $last_error = new WP_Error('exception', $e->getMessage());
            }
            
            // Wait before retry (exponential backoff)
            if ($attempt < $this->max_retries) {
                sleep($this->retry_delay * $attempt);
            }
        }
        
        // All retries failed
        $this->error_handler->log_error('E201', array(
            'endpoint' => $endpoint,
            'attempts' => $attempt,
            'error' => $last_error->get_error_message(),
            'action' => 'Data transfer failed after retries'
        ));
        
        return $last_error;
    }
    
    /**
     * Send posts in chunks
     */
    public function send_posts_chunked($posts, $target_url, $api_key, $progress_callback = null) {
        $total_posts = count($posts);
        $chunks = array_chunk($posts, $this->post_chunk_size);
        $processed = 0;
        $failed = array();
        
        foreach ($chunks as $chunk_index => $chunk) {
            $chunk_data = array(
                'chunk_index' => $chunk_index,
                'total_chunks' => count($chunks),
                'posts' => $chunk
            );
            
            $response = $this->send_with_retry(
                $target_url . '/wp-json/b2e/v1/import-posts-chunk',
                $chunk_data,
                $api_key
            );
            
            if (is_wp_error($response)) {
                // Log failed posts
                foreach ($chunk as $post) {
                    $failed[] = $post['id'];
                }
            } else {
                $processed += count($chunk);
            }
            
            // Update progress
            if ($progress_callback && is_callable($progress_callback)) {
                call_user_func($progress_callback, array(
                    'processed' => $processed,
                    'total' => $total_posts,
                    'percentage' => round(($processed / $total_posts) * 100),
                    'failed' => count($failed)
                ));
            }
        }
        
        return array(
            'success' => count($failed) === 0,
            'processed' => $processed,
            'failed' => $failed,
            'total' => $total_posts
        );
    }
    
    /**
     * Send CSS classes in chunks
     */
    public function send_css_chunked($classes, $target_url, $api_key) {
        // CSS is usually not huge, but we still chunk it for consistency
        $chunk_size = 50; // 50 classes per chunk
        $total_classes = count($classes);
        $chunks = array_chunk($classes, $chunk_size, true); // Preserve keys
        $processed = 0;
        
        foreach ($chunks as $chunk_index => $chunk) {
            $chunk_data = array(
                'chunk_index' => $chunk_index,
                'total_chunks' => count($chunks),
                'classes' => $chunk
            );
            
            $response = $this->send_with_retry(
                $target_url . '/wp-json/b2e/v1/import-css-chunk',
                $chunk_data,
                $api_key
            );
            
            if (!is_wp_error($response)) {
                $processed += count($chunk);
            }
        }
        
        return array(
            'success' => $processed === $total_classes,
            'processed' => $processed,
            'total' => $total_classes
        );
    }
    
    /**
     * Get optimal chunk size based on memory and post size
     */
    public function get_optimal_chunk_size($avg_post_size_bytes) {
        $available_memory = $this->get_available_memory();
        
        // Use 10% of available memory for chunk
        $chunk_memory = $available_memory * 0.1;
        
        // Calculate how many posts fit in chunk
        $optimal_size = floor($chunk_memory / $avg_post_size_bytes);
        
        // Min 1, max 20
        return max(1, min(20, $optimal_size));
    }
    
    /**
     * Get available memory in bytes
     */
    private function get_available_memory() {
        $memory_limit = ini_get('memory_limit');
        
        if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
            switch ($matches[2]) {
                case 'G':
                    $memory_limit = $matches[1] * 1024 * 1024 * 1024;
                    break;
                case 'M':
                    $memory_limit = $matches[1] * 1024 * 1024;
                    break;
                case 'K':
                    $memory_limit = $matches[1] * 1024;
                    break;
                default:
                    $memory_limit = $matches[1];
            }
        }
        
        $current_usage = memory_get_usage(true);
        return $memory_limit - $current_usage;
    }
    
    /**
     * Create checkpoint for resume functionality
     */
    public function create_checkpoint($step, $data = array()) {
        $checkpoint = array(
            'step' => $step,
            'timestamp' => time(),
            'data' => $data
        );
        
        update_option('b2e_migration_checkpoint', $checkpoint);
    }
    
    /**
     * Get last checkpoint
     */
    public function get_checkpoint() {
        return get_option('b2e_migration_checkpoint', null);
    }
    
    /**
     * Clear checkpoint
     */
    public function clear_checkpoint() {
        delete_option('b2e_migration_checkpoint');
    }
    
    /**
     * Set chunk size
     */
    public function set_chunk_size($size) {
        $this->post_chunk_size = max(1, min(50, intval($size)));
    }
    
    /**
     * Set max retries
     */
    public function set_max_retries($retries) {
        $this->max_retries = max(1, min(10, intval($retries)));
    }
}

