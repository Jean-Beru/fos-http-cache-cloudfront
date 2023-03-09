<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\CallerReference;

final class DateCallerReferenceGenerator implements CallerReferenceGenerator
{
    public function __construct(
        private readonly string $format = 'U.u',
    ) {
    }

    public function __invoke(): string
    {
        return date($this->format);
    }
}
