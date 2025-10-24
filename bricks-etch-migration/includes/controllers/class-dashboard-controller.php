<?php
namespace Bricks2Etch\Controllers;

use Bricks2Etch\Core\B2E_Plugin_Detector;
use Bricks2Etch\Core\B2E_Error_Handler;
use Bricks2Etch\Services\B2E_Migration_Service;

if (!defined('ABSPATH')) {
    exit;
}

class B2E_Dashboard_Controller {
    private $plugin_detector;
    private $error_handler;
    private $migration_service;
    private $settings_controller;

    public function __construct(
        B2E_Plugin_Detector $plugin_detector,
        B2E_Error_Handler $error_handler,
        B2E_Migration_Service $migration_service,
        B2E_Settings_Controller $settings_controller
    ) {
        $this->plugin_detector = $plugin_detector;
        $this->error_handler = $error_handler;
        $this->migration_service = $migration_service;
        $this->settings_controller = $settings_controller;
    }

    public function render() {
        $data = $this->get_dashboard_context();
        $this->render_view('dashboard', $data);
    }

    public function detect_environment() {
        return array(
            'is_bricks_site' => $this->plugin_detector->is_bricks_active(),
            'is_etch_site' => $this->plugin_detector->is_etch_active(),
            'site_url' => home_url(),
            'is_https' => is_ssl(),
        );
    }

    public function get_dashboard_context() {
        $env = $this->detect_environment();

        return array(
            'is_bricks_site' => $env['is_bricks_site'],
            'is_etch_site' => $env['is_etch_site'],
            'site_url' => $env['site_url'],
            'is_https' => $env['is_https'],
            'logs' => $this->get_logs(),
            'progress_data' => $this->get_progress(),
            'settings' => $this->get_settings(),
            'nonce' => wp_create_nonce('b2e_nonce'),
        );
    }

    private function get_logs() {
        return $this->error_handler->get_recent_logs();
    }

    private function get_progress() {
        $progress = $this->migration_service->get_progress();
        return is_array($progress) ? $progress : array();
    }

    private function get_settings() {
        return $this->settings_controller->get_settings();
    }

    private function render_view($template, array $data = array()) {
        $path = plugin_dir_path(__FILE__) . '../views/' . $template . '.php';
        $path = realpath($path);
        if (!$path || !file_exists($path)) {
            return;
        }
        extract($data, EXTR_SKIP);
        include $path;
    }
}

\class_alias(__NAMESPACE__ . '\\B2E_Dashboard_Controller', 'B2E_Dashboard_Controller');
