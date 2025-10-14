<?php
namespace WPvividProAws\Api\Parser;

use WPvividProAws\Api\StructureShape;
use WPvividProAws\Api\Service;
use WPvividProAws\Result;
use WPvividProAws\CommandInterface;
use WPvividProPsr\Http\Message\ResponseInterface;
use WPvividProPsr\Http\Message\StreamInterface;

/**
 * @internal Implements JSON-RPC parsing (e.g., DynamoDB)
 */
class JsonRpcParser extends AbstractParser
{
    use PayloadParserTrait;

    /**
     * @param Service    $api    Service description
     * @param JsonParser $parser JSON body builder
     */
    public function __construct(Service $api, JsonParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new JsonParser();
    }

    public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    ) {
        $operation = $this->api->getOperation($command->getName());
        $result = null === $operation['output']
            ? null
            : $this->parseMemberFromStream(
                $response->getBody(),
                $operation->getOutput(),
                $response
            );

        return new Result($result ?: []);
    }

    public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    ) {
        return $this->parser->parse($member, $this->parseJson($stream, $response));
    }
}
