<?php
namespace WPvividProAws\ClientSideMonitoring\Exception;

use WPvividProAws\HasMonitoringEventsTrait;
use WPvividProAws\MonitoringEventsInterface;


/**
 * Represents an error interacting with configuration for client-side monitoring.
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
