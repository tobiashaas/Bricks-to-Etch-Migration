<?php
namespace WPvividProAws\Arn\S3;

use WPvividProAws\Arn\ArnInterface;

/**
 * @internal
 */
interface BucketArnInterface extends ArnInterface
{
    public function getBucketName();
}
