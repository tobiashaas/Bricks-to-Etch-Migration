<?php
namespace Bricks2Etch\Services;

use Bricks2Etch\Api\B2E_API_Client;
use Bricks2Etch\Core\B2E_Error_Handler;
use Bricks2Etch\Parsers\B2E_CSS_Converter;

class B2E_CSS_Service
{
    /** @var B2E_CSS_Converter */
    private $css_converter;

    /** @var B2E_API_Client */
    private $api_client;

    /** @var B2E_Error_Handler */
    private $error_handler;

    public function __construct(
        B2E_CSS_Converter $css_converter,
        B2E_API_Client $api_client,
        B2E_Error_Handler $error_handler
    ) {
        $this->css_converter = $css_converter;
        $this->api_client = $api_client;
        $this->error_handler = $error_handler;
    }

    /**
     * @param string $target_url
     * @param string $api_key
     *
     * @return array|\WP_Error
     */
    public function migrate_css_classes($target_url, $api_key)
    {
        try {
            $etch_styles = $this->css_converter->convert_bricks_classes_to_etch();

            if (empty($etch_styles)) {
                $this->error_handler->log_error('I008', [
                    'message' => 'No CSS classes found to migrate',
                    'action' => 'CSS migration skipped',
                ]);

                return [
                    'success' => true,
                    'migrated' => 0,
                    'message' => __('No CSS classes found to migrate.', 'bricks-etch-migration'),
                ];
            }

            $response = $this->api_client->send_css_styles($target_url, $api_key, $etch_styles);

            if (is_wp_error($response)) {
                $this->error_handler->log_error('E106', [
                    'error' => $response->get_error_message(),
                    'action' => 'Failed to send CSS styles to target site',
                ]);

                return $response;
            }

            $this->error_handler->log_error('I009', [
                'css_classes_found' => count($etch_styles),
                'css_class_names' => array_keys($etch_styles),
                'action' => 'CSS migration completed successfully',
            ]);

            return [
                'success' => true,
                'migrated' => count($etch_styles),
                'message' => __('CSS classes migrated successfully.', 'bricks-etch-migration'),
                'response' => $response,
            ];
        } catch (\Exception $exception) {
            $this->error_handler->log_error('E906', [
                'message' => $exception->getMessage(),
                'action' => 'CSS migration failed',
            ]);

            return new \WP_Error('css_migration_failed', $exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public function convert_bricks_to_etch()
    {
        return $this->css_converter->convert_bricks_classes_to_etch();
    }

    /**
     * @param string $target_url
     * @param string $api_key
     * @param array  $etch_styles
     *
     * @return mixed
     */
    public function import_etch_styles($target_url, $api_key, array $etch_styles)
    {
        return $this->css_converter->import_etch_styles($target_url, $api_key, $etch_styles);
    }

    /**
     * @return array
     */
    public function get_bricks_global_classes()
    {
        if (method_exists($this->css_converter, 'get_bricks_global_classes')) {
            return $this->css_converter->get_bricks_global_classes();
        }

        return [];
    }

    /**
     * @param string $css
     *
     * @return bool
     */
    public function validate_css_syntax($css)
    {
        return $this->css_converter->validate_css_syntax($css);
    }
}

class_alias(B2E_CSS_Service::class, 'B2E_CSS_Service');
