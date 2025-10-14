<?php
namespace WPvividProAws\Exception;

use WPvividProAws\HasMonitoringEventsTrait;
use WPvividProAws\MonitoringEventsInterface;

class CredentialsException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
