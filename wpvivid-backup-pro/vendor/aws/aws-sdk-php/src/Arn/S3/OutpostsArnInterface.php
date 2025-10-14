<?php
namespace WPvividProAws\Arn\S3;

use WPvividProAws\Arn\ArnInterface;

/**
 * @internal
 */
interface OutpostsArnInterface extends ArnInterface
{
    public function getOutpostId();
}
