# CloudFront implementation of FOSHttpCache

This library provides an implementation of [FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache/) for
[CloudFront](https://aws.amazon.com/cloudfront/).

## /!\ This library is experimental /!\

## Usage

### Initialize dependency

First, create an instance of `Aws\CloudFront\CloudFrontClient` to allow the invalidator to make requests.
See [aws-sdk-php documentation](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.AwsClient.html#___construct) 
for more information.

```php
use Aws\CloudFront\CloudFrontClient;

$client = new CloudFrontClient(/* client configuration */);
```

### Invalidate URLs

To invalidate `/homepage` URL and all URLs matching the `/assets/*` pattern on the "XYZ1234657" distribution.

```php
use JeanBeru\FosHttpCacheCloudFront\Proxy\CloudFrontCacheInvalidator;

$distributionId = 'XYZ1234657';

$invalidator = new CloudFrontCacheInvalidator(
    client: $client,
    distributionId: 'XYZ1234657',
);
$invalidator
    ->purge('/homepage')
    ->purge('/assets/*')
    // To send the purge request, flush() method must be called
    ->flush()
; 
```

### Send request synchronously

By default, `CloudFrontCacheInvalidator` sends requests asynchronously. You can configure the proxy client to use 
synchronous mode:
```php
use JeanBeru\FosHttpCacheCloudFront\Proxy\CloudFrontCacheInvalidator;
use JeanBeru\FosHttpCacheCloudFront\CallerReference\DateCallerReferenceGenerator;

$invalidator = new CloudFrontCacheInvalidator(
    client: $client,
    distributionId: 'XYZ1234657',
    useAsync: false,
);
```

### Avoid request duplication

CloudFront APIs asks for a "caller reference" to avoid duplicated requests. By default, this library use the
[UniqIdCallerReferenceGenerator](./CallerReference/UniqIdCallerReferenceGenerator.php) to generate a unique identifier.

You can use other generators present in the [CallerReference folder](./CallerReference/) or implement your own by
implementing the
[CallerReferenceGenerator](./CallerReference/CallerReferenceGenerator)
interface.

For instance, if you want to avoid duplicate calls in the same minute:

```php
use JeanBeru\FosHttpCacheCloudFront\Proxy\CloudFrontCacheInvalidator;
use JeanBeru\FosHttpCacheCloudFront\CallerReference\DateCallerReferenceGenerator;

$invalidator = new CloudFrontCacheInvalidator(
    client: $client,
    distributionId: 'XYZ1234657',
    callerReferenceGenerator: new DateCallerReferenceGenerator('YmdHi'),
);
```

If a duplication is detected by AWS, a `FOS\HttpCache\Exception\ProxyResponseException` will be thrown.

## Resources

* [Report issues](https://github.com/jean-beru/fos-http-cache-cloudfrontr/issues) and
  [send Pull Requests](https://github.com/jean-beru/fos-http-cache-cloudfrontr/pulls) 
