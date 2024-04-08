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
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CloudFront implements ProxyClient, PurgeCapable
{
    /**
     * @var array{
     *     distribution_id: string,
     *     caller_reference_generator: CallerReferenceGenerator,
     * }
     */
    private readonly array $options;

    /** @var array<string, true> */
    private array $items = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly CloudFrontClient $client,
        array $options = [],
    ) {
        $this->options = $this->configureOptions()->resolve($options);
    }

    /**
     * @param array<string, string> $headers
     */
    public function purge($url, array $headers = []): static
    {
        $this->items[$url] = true;

        return $this;
    }

    public function flush(): int
    {
        if (0 === $quantity = count($this->items)) {
            return 0;
        }

        $items = $this->items;
        $this->items = [];

        $exceptions = new ExceptionCollection();

        try {
            $this->client->createInvalidation([
                'DistributionId' => $this->options['distribution_id'],
                'InvalidationBatch' => [
                    'CallerReference' => $this->options['caller_reference_generator'],
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

    private function configureOptions(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setRequired('distribution_id')
            ->setAllowedTypes('distribution_id', 'string')
            ->setDefault('caller_reference_generator', new UniqIdCallerReferenceGenerator())
            ->setAllowedTypes('caller_reference_generator', CallerReferenceGenerator::class)
        ;
    }
}
