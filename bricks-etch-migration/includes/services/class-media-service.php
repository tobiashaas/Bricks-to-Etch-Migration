<?php
namespace Bricks2Etch\Services;

use Bricks2Etch\Api\B2E_API_Client;
use Bricks2Etch\Core\B2E_Error_Handler;
use Bricks2Etch\Migrators\B2E_Media_Migrator;

class B2E_Media_Service
{
    /** @var B2E_Media_Migrator */
    private $media_migrator;

    /** @var B2E_Error_Handler */
    private $error_handler;

    /**
     * @param B2E_Media_Migrator $media_migrator
     * @param B2E_Error_Handler  $error_handler
     */
    public function __construct(B2E_Media_Migrator $media_migrator, B2E_Error_Handler $error_handler)
    {
        $this->media_migrator = $media_migrator;
        $this->error_handler = $error_handler;
    }

    /**
     * @param string $target_url
     * @param string $api_key
     *
     * @return array|\WP_Error
     */
    public function migrate_media($target_url, $api_key)
    {
        try {
            $result = $this->media_migrator->migrate_media($target_url, $api_key);

            if (is_wp_error($result)) {
                $this->error_handler->log_error('E105', [
                    'error' => $result->get_error_message(),
                    'action' => 'Media migration failed',
                ]);

                return $result;
            }

            $this->error_handler->log_error('I007', [
                'action' => 'Media Files Migration Summary',
                'summary' => $result,
            ]);

            return [
                'success' => true,
                'summary' => $result,
            ];
        } catch (\Exception $exception) {
            $this->error_handler->log_error('E905', [
                'message' => $exception->getMessage(),
                'action' => 'Media migration failure',
            ]);

            return new \WP_Error('media_migration_failed', $exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public function get_media_stats()
    {
        if (method_exists($this->media_migrator, 'get_media_stats')) {
            return $this->media_migrator->get_media_stats();
        }

        return [];
    }

    /**
     * @param int $source_media_id
     *
     * @return mixed
     */
    public function get_media_mapping($source_media_id)
    {
        if (method_exists($this->media_migrator, 'get_media_mapping')) {
            return $this->media_migrator->get_media_mapping($source_media_id);
        }

        return null;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public function find_media_in_content($content)
    {
        if (method_exists($this->media_migrator, 'find_media_in_content')) {
            return $this->media_migrator->find_media_in_content($content);
        }

        return [];
    }
}

class_alias(B2E_Media_Service::class, 'B2E_Media_Service');
