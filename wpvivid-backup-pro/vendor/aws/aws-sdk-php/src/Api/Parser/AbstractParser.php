<?php
namespace WPvividProAws\Api\Parser;

use WPvividProAws\Api\Service;
use WPvividProAws\Api\StructureShape;
use WPvividProAws\CommandInterface;
use WPvividProAws\ResultInterface;
use WPvividProPsr\Http\Message\ResponseInterface;
use WPvividProPsr\Http\Message\StreamInterface;

/**
 * @internal
 */
abstract class AbstractParser
{
    /** @var \Aws\Api\Service Representation of the service API*/
    protected $api;

    /** @var callable */
    protected $parser;

    /**
     * @param Service $api Service description.
     */
    public function __construct(Service $api)
    {
        $this->api = $api;
    }

    /**
     * @param CommandInterface  $command  Command that was executed.
     * @param ResponseInterface $response Response that was received.
     *
     * @return ResultInterface
     */
    abstract public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    );

    abstract public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    );
}
