<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\Tests\Proxy;

use AsyncAws\CloudFront\CloudFrontClient;
use AsyncAws\CloudFront\Exception\AccessDeniedException;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use FOS\HttpCache\Exception\ExceptionCollection;
use JeanBeru\HttpCacheCloudFront\CallerReference\CallerReferenceGenerator;
use JeanBeru\HttpCacheCloudFront\Proxy\CloudFront;
use PHPUnit\Framework\TestCase;

class CloudFrontTest extends TestCase
{
    /** @var CloudFrontClient&\PHPUnit\Framework\MockObject\MockObject */
    private $client;
    /** @var CallerReferenceGenerator */
    private $callerReferenceGenerator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(CloudFrontClient::class);
        $this->callerReferenceGenerator = new class() implements CallerReferenceGenerator {
            public function __invoke(): string
            {
                return 'test-caller-reference';
            }
        };
    }

    public function testPurge(): void
    {
        $this->client
            ->expects($this->once())
            ->method('createInvalidation')
            ->with([
                'DistributionId' => 'test-distribution-id',
                'InvalidationBatch' => [
                    'CallerReference' => 'test-caller-reference',
                    'Paths' => [
                        'Items' => ['/homepage', '/contact', '/assets/*'],
                        'Quantity' => 3,
                    ],
                ],
            ])
        ;

        $this->getCloudFront()
            ->purge('/homepage')
            ->purge('/contact')
            ->purge('/assets/*')
            ->flush()
        ;
    }

    public function testPurgeWithoutFlush(): void
    {
        $this->client
            ->expects($this->never())
            ->method('createInvalidation')
        ;
        $this->client
            ->expects($this->never())
            ->method('createInvalidation')
        ;

        $this->getCloudFront()
            ->purge('/homepage')
            ->purge('/contact')
            ->purge('/assets/*')
        ;
    }

    public function testException(): void
    {
        $this->client
            ->method('createInvalidation')
            ->willThrowException(new AccessDeniedException(new SimpleMockedResponse('<Error><message>Access denied.</message></Error>', [], 403)))
        ;

        $this->expectException(ExceptionCollection::class);

        $this->getCloudFront()
            ->purge('/homepage')
            ->purge('/contact')
            ->purge('/assets/*')
            ->flush()
        ;
    }

    private function getCloudFront(): CloudFront
    {
        return new CloudFront(
            $this->client,
            [
                'distribution_id' => 'test-distribution-id',
                'caller_reference_generator' => $this->callerReferenceGenerator,
            ]
        );
    }
}
