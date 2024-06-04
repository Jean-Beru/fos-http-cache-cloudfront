# CloudFront implementation of FOSHttpCache

[![Latest Version](https://img.shields.io/github/release/Jean-Beru/fos-http-cache-cloudfront.svg?style=flat-square)](https://github.com/Jean-Beru/fos-http-cache-cloudfront/releases)
[![Total Downloads](https://poser.pugx.org/Jean-Beru/fos-http-cache-cloudfront/downloads)](https://packagist.org/packages/Jean-Beru/fos-http-cache-cloudfront)
[![Monthly Downloads](https://poser.pugx.org/Jean-Beru/fos-http-cache-cloudfront/d/monthly.png)](https://packagist.org/packages/Jean-Beru/fos-http-cache-cloudfront)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENCE)
[![Tests](https://github.com/Jean-Beru/fos-http-cache-cloudfront/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Jean-Beru/fos-http-cache-cloudfront/actions/workflows/ci.yml?query=branch%3Amain)

This library provides an implementation of [FOSHttpCache](https://github.com/FriendsOfSymfony/FOSHttpCache/) for
[CloudFront](https://aws.amazon.com/cloudfront/).

## Usage

### Initialize dependency

First, create an instance of `AsyncAws\CloudFront\CloudFrontClient` to allow the proxy to make requests.
See [aws-sdk-php documentation](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.AwsClient.html#___construct) 
for more information.

```php
use Aws\CloudFront\CloudFrontClient;

$client = new CloudFrontClient(/* client configuration */);
```

### Create the CloudFront proxy

To instantiate the proxy, pass the CloudFront client and the AWS CloudFront distribution ID.

```php
use JeanBeru\HttpCacheCloudFront\Proxy\CloudFront;

$proxy = new CloudFront(
    client: $client,
    options: [
      'distribution_id' => 'XYZ1234657',
    ],
);
```

### Invalidate URLs

To invalidate `/homepage` URL and all URLs matching the `/assets/*` pattern on the "XYZ1234657" distribution.

```php
$proxy
    ->purge('/homepage')
    ->purge('/assets/*')
    // To send the purge request, flush() method must be called
    ->flush()
; 
```

### Avoid request duplication

CloudFront APIs asks for a "caller reference" to avoid duplicated requests. By default, this library use the
[UniqIdCallerReferenceGenerator](./CallerReference/UniqIdCallerReferenceGenerator.php) to generate a unique identifier.

You can use other generators present in the [CallerReference folder](./CallerReference/) or implement your own by
implementing the [CallerReferenceGenerator](./CallerReference/CallerReferenceGenerator) interface.

For instance, if you want to avoid duplicate calls in the same minute:

```php
use JeanBeru\HttpCacheCloudFront\Proxy\CloudFront;
use JeanBeru\HttpCacheCloudFront\CallerReference\DateCallerReferenceGenerator;

$proxy = new CloudFront(
    client: $client,
    options: [
      'distribution_id' => 'XYZ1234657',
      'caller_reference_generator' => new DateCallerReferenceGenerator('YmdHi'),
    ],
);
```

If a duplication is detected by AWS, a `FOS\HttpCache\Exception\ProxyResponseException` will be thrown.

## Resources

* [Report issues](https://github.com/jean-beru/fos-http-cache-cloudfront/issues) and
  [send Pull Requests](https://github.com/jean-beru/fos-http-cache-cloudfront/pulls) 
