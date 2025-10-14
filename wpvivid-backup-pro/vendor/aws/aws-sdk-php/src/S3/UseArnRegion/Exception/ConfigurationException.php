<?php
namespace WPvividProAws\S3\UseArnRegion\Exception;

use WPvividProAws\HasMonitoringEventsTrait;
use WPvividProAws\MonitoringEventsInterface;

/**
 * Represents an error interacting with configuration for S3's UseArnRegion
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
