<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\CallerReference;

final class DateCallerReferenceGenerator implements CallerReferenceGenerator
{
    /** @var string */
    private $format;

    public function __construct(string $format = 'U.u')
    {
        $this->format = $format;
    }

    public function __toString(): string
    {
        return date($this->format);
    }
}
