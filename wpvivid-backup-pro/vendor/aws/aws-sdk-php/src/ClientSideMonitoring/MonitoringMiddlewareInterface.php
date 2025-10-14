<?php

namespace WPvividProAws\ClientSideMonitoring;

use WPvividProAws\CommandInterface;
use WPvividProAws\Exception\AwsException;
use WPvividProAws\ResultInterface;
use WPvividProGuzzleHttp\Psr7\Request;
use WPvividProPsr\Http\Message\RequestInterface;

/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{

    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(RequestInterface $request);


    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);

    public function __invoke(CommandInterface $cmd, RequestInterface $request);
}