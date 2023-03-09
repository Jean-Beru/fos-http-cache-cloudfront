<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\Proxy;

use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ProxyClient\ProxyClient;
use JeanBeru\HttpCacheCloudFront\CallerReference\CallerReferenceGenerator;
use JeanBeru\HttpCacheCloudFront\CallerReference\UniqIdCallerReferenceGenerator;

final class CloudFront implements ProxyClient, PurgeCapable
{
    private readonly CallerReferenceGenerator $callerReferenceGenerator;
    /** @var array<string, true> */
    private array $items = [];

    public function __construct(
        private readonly CloudFrontClient $client,
        private readonly string $distributionId,
        private readonly bool $useAsync = true,
        CallerReferenceGenerator $callerReferenceGenerator = null,
    ) {
        $this->callerReferenceGenerator = $callerReferenceGenerator ?? new UniqIdCallerReferenceGenerator();
    }

    /**
     * @param string $url
     * @param array<string, string> $headers
     */
    public function purge($url, array $headers = []): self
    {
        $this->items[$url] = true;

        return $this;
    }

    public function flush(): int
    {
        $method = $this->useAsync ? 'createInvalidationAsync' : 'createInvalidation';
        $items = array_keys($this->items);
        $quantity = count($this->items);

        try {
            $this->client->$method([
                'DistributionId' => $this->distributionId,
                'InvalidationBatch' => [
                    'CallerReference' => ($this->callerReferenceGenerator)(),
                    'Paths' => [
                        'Items' => $items,
                        'Quantity' => $quantity,
                    ],
                ],
            ]);
        } catch (AwsException $e) {
            throw new ProxyResponseException(
                message: sprintf(
                    '%s error response "%s" from caching proxy',
                    $e->getResponse()?->getStatusCode() ?? 0,
                    $e->getResponse()?->getReasonPhrase() ?? '',
                ),
                previous: $e,
            );
        }

        return $quantity;
    }
}
