<?php
namespace WPvividProAws\DefaultsMode\Exception;

use WPvividProAws\HasMonitoringEventsTrait;
use WPvividProAws\MonitoringEventsInterface;

/**
 * Represents an error interacting with configuration mode
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
