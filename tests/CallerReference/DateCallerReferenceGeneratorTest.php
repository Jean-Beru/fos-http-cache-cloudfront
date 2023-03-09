<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\Tests\CallerReference;

use JeanBeru\HttpCacheCloudFront\CallerReference\DateCallerReferenceGenerator;
use PHPUnit\Framework\TestCase;

class DateCallerReferenceGeneratorTest extends TestCase
{
    public function test__invoke(): void
    {
        $generator = new DateCallerReferenceGenerator('Y');

        $this->assertSame(date('Y'), (string) $generator);
    }
}
