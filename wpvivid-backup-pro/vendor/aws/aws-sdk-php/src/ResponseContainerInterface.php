<?php

namespace WPvividProAws;

use WPvividProPsr\Http\Message\ResponseInterface;

interface ResponseContainerInterface
{
    /**
     * Get the received HTTP response if any.
     *
     * @return ResponseInterface|null
     */
    public function getResponse();
}