<?php
/**
 * API Client Service
 * 
 * Handles communication with Etch API
 */

namespace BricksEtchMigration\Services\API;

class APIClientService {
    /**
     * Send styles to Etch API
     * 
     * @param string $url Etch API URL
     * @param string $apiKey API Key
     * @param array $data Styles and style map
     * @return array|WP_Error Response or error
     */
    public function sendStyles(string $url, string $apiKey, array $data): array|\WP_Error {
        $response = wp_remote_post($url . '/wp-json/etch/v1/styles', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $apiKey
            ],
            'body' => json_encode($data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error('json_error', 'Invalid JSON response');
        }
        
        return $decoded;
    }
    
    /**
     * Send content to Etch API
     * 
     * @param string $url Etch API URL
     * @param string $apiKey API Key
     * @param int $postId Post ID
     * @param string $content Gutenberg content
     * @return array|WP_Error Response or error
     */
    public function sendContent(string $url, string $apiKey, int $postId, string $content): array|\WP_Error {
        $response = wp_remote_post($url . '/wp-json/etch/v1/content', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $apiKey
            ],
            'body' => json_encode([
                'post_id' => $postId,
                'content' => $content
            ]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true) ?? [];
    }
    
    /**
     * Validate API connection
     * 
     * @param string $url Etch API URL
     * @param string $apiKey API Key
     * @return bool True if valid
     */
    public function validateConnection(string $url, string $apiKey): bool {
        $response = wp_remote_get($url . '/wp-json/etch/v1/validate', [
            'headers' => [
                'X-API-Key' => $apiKey
            ],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    }
}
