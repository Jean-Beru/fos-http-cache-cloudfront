<?php

declare(strict_types=1);

namespace JeanBeru\HttpCacheCloudFront\Tests\Proxy;

use AsyncAws\CloudFront\CloudFrontClient;
use AsyncAws\CloudFront\Exception\AccessDeniedException;
use AsyncAws\Core\Response;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use FOS\HttpCache\Exception\ExceptionCollection;
use JeanBeru\HttpCacheCloudFront\CallerReference\CallerReferenceGenerator;
use JeanBeru\HttpCacheCloudFront\Proxy\CloudFront;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;

class CloudFrontTest extends TestCase
{
    /** @var CloudFrontClient&\PHPUnit\Framework\MockObject\MockObject */
    private CloudFrontClient $client;
    private CallerReferenceGenerator $callerReferenceGenerator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(CloudFrontClient::class);
        $this->callerReferenceGenerator = new class implements CallerReferenceGenerator {
            public function __toString(): string { return 'test-caller-reference'; }
        };
    }

    public function test__purge(): void
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
            ->purge( '/assets/*')
            ->flush()
        ;
    }

    public function test__purge_asynchronously(): void
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
            ->purge( '/assets/*')
            ->flush()
        ;
    }

    public function test__purge_without_flush(): void
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
            ->purge( '/assets/*')
        ;
    }

    public function test__exception(): void
    {
        $this->client
            ->method('createInvalidation')
            ->willThrowException(new AccessDeniedException(new SimpleMockedResponse(content: '<Error><message>Access denied.</message></Error>',  statusCode: 403)))
        ;

        $this->expectException(ExceptionCollection::class);

        $this->getCloudFront()
            ->purge('/homepage')
            ->purge('/contact')
            ->purge( '/assets/*')
            ->flush()
        ;
    }

    private function getCloudFront(): CloudFront
    {
        return new CloudFront(
            $this->client,
            'test-distribution-id',
            $this->callerReferenceGenerator,
        );
    }
}
