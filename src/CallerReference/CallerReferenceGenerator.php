<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\CallerReference;

interface CallerReferenceGenerator extends \Stringable
{
    public function __toString(): string;
}
