<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\Proxy;

use AsyncAws\CloudFront\CloudFrontClient;
use AsyncAws\Core\Exception\Http\HttpException;
use FOS\HttpCache\Exception\ExceptionCollection;
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
        CallerReferenceGenerator $callerReferenceGenerator = null,
    ) {
        $this->callerReferenceGenerator = $callerReferenceGenerator ?? new UniqIdCallerReferenceGenerator();
    }

    /**
     * @param array<string, string> $headers
     */
    public function purge($url, array $headers = []): self
    {
        $this->items[$url] = true;

        return $this;
    }

    public function flush(): int
    {
        $items = $this->items;
        $this->items = [];

        if (0 === $quantity = count($items)) {
            return 0;
        }

        $exceptions = new ExceptionCollection();

        try {
            $this->client->createInvalidation([
                'DistributionId' => $this->distributionId,
                'InvalidationBatch' => [
                    'CallerReference' => (string) $this->callerReferenceGenerator,
                    'Paths' => [
                        'Items' => array_keys($items),
                        'Quantity' => $quantity,
                    ],
                ],
            ]);
        } catch (HttpException $e) {
            $exceptions->add(new ProxyResponseException(
                message: sprintf(
                    '%s error response "%s" from caching proxy',
                    $e->getResponse()->getStatusCode(),
                    $e->getMessage(),
                ),
                previous: $e,
            ));
        }

        if (0 !== count($exceptions)) {
            throw $exceptions;
        }

        return $quantity;
    }
}
