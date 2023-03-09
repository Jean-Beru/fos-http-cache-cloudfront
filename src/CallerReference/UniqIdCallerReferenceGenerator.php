<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\CallerReference;

final class UniqIdCallerReferenceGenerator implements CallerReferenceGenerator
{
    public function __toString(): string
    {
        return uniqid('', true);
    }
}
