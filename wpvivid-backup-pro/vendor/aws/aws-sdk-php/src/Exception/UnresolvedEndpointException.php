<?php
namespace WPvividProAws\Exception;

use WPvividProAws\HasMonitoringEventsTrait;
use WPvividProAws\MonitoringEventsInterface;

class UnresolvedEndpointException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
