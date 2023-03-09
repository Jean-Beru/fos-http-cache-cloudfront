<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\CallerReference;

interface CallerReferenceGenerator
{
    public function __invoke(): string;
}
