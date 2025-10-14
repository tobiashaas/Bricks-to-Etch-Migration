<?php
namespace WPvividProAws\Endpoint\UseFipsEndpoint\Exception;

use WPvividProAws\HasMonitoringEventsTrait;
use WPvividProAws\MonitoringEventsInterface;

/**
 * Represents an error interacting with configuration for useFipsRegion
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
