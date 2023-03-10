<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\Tests\CallerReference;

use JeanBeru\HttpCacheCloudFront\CallerReference\UniqIdCallerReferenceGenerator;
use PHPUnit\Framework\TestCase;

class UniqIdCallerReferenceGeneratorTest extends TestCase
{
    public function test__invoke(): void
    {
        $generator = new UniqIdCallerReferenceGenerator();

        $this->assertNotSame($generator(), $generator());
    }
}
