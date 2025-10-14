<?php
namespace WPvividProAws\S3;

use WPvividProAws\Api\Parser\AbstractParser;
use WPvividProAws\Api\StructureShape;
use WPvividProAws\Api\Parser\Exception\ParserException;
use WPvividProAws\CommandInterface;
use WPvividProAws\Exception\AwsException;
use WPvividProPsr\Http\Message\ResponseInterface;
use WPvividProPsr\Http\Message\StreamInterface;

/**
 * Converts malformed responses to a retryable error type.
 *
 * @internal
 */
class RetryableMalformedResponseParser extends AbstractParser
{
    /** @var string */
    private $exceptionClass;

    public function __construct(
        callable $parser,
        $exceptionClass = AwsException::class
    ) {
        $this->parser = $parser;
        $this->exceptionClass = $exceptionClass;
    }

    public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    ) {
        $fn = $this->parser;

        try {
            return $fn($command, $response);
        } catch (ParserException $e) {
            throw new $this->exceptionClass(
                "Error parsing response for {$command->getName()}:"
                    . " AWS parsing error: {$e->getMessage()}",
                $command,
                ['connection_error' => true, 'exception' => $e],
                $e
            );
        }
    }

    public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    ) {
        return $this->parser->parseMemberFromStream($stream, $member, $response);
    }
}
