<?php
namespace WPvividProAws\Exception;

use WPvividProAws\HasMonitoringEventsTrait;
use WPvividProAws\MonitoringEventsInterface;

class InvalidJsonException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
