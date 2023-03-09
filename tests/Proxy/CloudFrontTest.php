<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\Tests\Proxy;

use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;
use FOS\HttpCache\Exception\ProxyResponseException;
use JeanBeru\HttpCacheCloudFront\CallerReference\CallerReferenceGenerator;
use JeanBeru\HttpCacheCloudFront\Proxy\CloudFront;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class CloudFrontTest extends TestCase
{
    /** @var CloudFrontClient&\PHPUnit\Framework\MockObject\MockObject */
    private CloudFrontClient $client;
    private CallerReferenceGenerator $callerReferenceGenerator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(CloudFrontClient::class);
        $this->callerReferenceGenerator = new class implements CallerReferenceGenerator {
            public function __invoke(): string { return 'test-caller-reference'; }
        };
    }

    public function test__purge(): void
    {
        $this->client
            ->expects($this->once())
            ->method('__call')
            ->with('createInvalidation', [[
                'DistributionId' => 'test-distribution-id',
                'InvalidationBatch' => [
                    'CallerReference' => 'test-caller-reference',
                    'Paths' => [
                        'Items' => ['/homepage', '/contact', '/assets/*'],
                        'Quantity' => 3,
                    ],
                ],
            ]])
        ;

        $this->getCloudFront(false)
            ->purge('/homepage')
            ->purge('/contact')
            ->purge( '/assets/*')
            ->flush()
        ;
    }

    public function test__purge_asynchronously(): void
    {
        $this->client
            ->expects($this->once())
            ->method('__call')
            ->with('createInvalidationAsync', [[
                'DistributionId' => 'test-distribution-id',
                'InvalidationBatch' => [
                    'CallerReference' => 'test-caller-reference',
                    'Paths' => [
                        'Items' => ['/homepage', '/contact', '/assets/*'],
                        'Quantity' => 3,
                    ],
                ],
            ]])
        ;

        $this->getCloudFront(true)
            ->purge('/homepage')
            ->purge('/contact')
            ->purge( '/assets/*')
            ->flush()
        ;
    }

    public function test__purge_without_flush(): void
    {
        $this->client
            ->expects($this->never())
            ->method('__call')
            ->with('createInvalidation', $this->any())
        ;
        $this->client
            ->expects($this->never())
            ->method('__call')
            ->with('createInvalidationAsync', $this->any())
        ;

        $this->getCloudFront(true)
            ->purge('/homepage')
            ->purge('/contact')
            ->purge( '/assets/*')
        ;
    }

    public function test__exception(): void
    {
        $this->client
            ->method('__call')
            ->willThrowException(
                $this->createConfiguredMock(AwsException::class, [
                    'getResponse' => $this->createConfiguredMock(ResponseInterface::class, [
                        'getStatusCode'  => '500',
                        'getReasonPhrase'  => 'An error occurred.',
                    ]),
                ]),
            )
        ;

        $this->expectException(ProxyResponseException::class);
        $this->expectExceptionMessage('500 error response "An error occurred." from caching proxy');

        $this->getCloudFront(true)
            ->purge('/homepage')
            ->purge('/contact')
            ->purge( '/assets/*')
            ->flush()
        ;
    }

    private function getCloudFront(bool $useAsync): CloudFront
    {
        return new CloudFront(
            $this->client,
            'test-distribution-id',
            $useAsync,
            $this->callerReferenceGenerator,
        );
    }
}
