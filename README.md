# ResilientLink PHP SDK

Official PHP client for the [ResilientLink](https://resilientlink.io) Web Scraping API.

## Requirements
- PHP >= 7.4
- ext-curl
- ext-json

## Installation

```bash
composer require resilientlink/resilientlink-php
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

$client = new \ResilientLink\Client(['api_key' => 'YOUR_API_KEY']);

$result = $client->scrape('https://example.com');

echo $result['data']['title'];        // "Example Domain"
echo $result['data']['description'];  // meta description
echo $result['data']['image'];        // OG image URL
```

## Options

```php
$result = $client->scrape('https://example.com', [
    'return_html'       => true,      // include raw HTML
    'screenshot'        => true,      // base64 PNG (Pro/Enterprise)
    'pdf'               => true,      // base64 PDF (Pro/Enterprise)
    'pdf_format'        => 'A4',
    'bypass_cache'      => true,      // force fresh scrape
    'js_render'         => true,      // JS rendering (Pro/Enterprise)
    'wait_for_selector' => '#app',    // wait for CSS selector
    'wait_ms'           => 2000,      // wait 2s before scraping
    'custom_headers'    => ['Accept-Language' => 'en-US'],
    'timeout'           => 30000,     // ms (max 60000)
]);
```

## Response

```php
[
  'success'      => true,
  'cached'       => false,
  'tier'         => 'starter',
  'engine'       => 'axios',
  'responseTime' => 412,
  'data'         => [
    'url'         => 'https://example.com',
    'title'       => 'Example Domain',
    'description' => '...',
    'image'       => '...',
    'domain'      => 'example.com',
    'og'          => ['title' => '...', 'description' => '...'],
    'content'     => ['wordCount' => 423, 'readTimeMinutes' => 2],
    'seo'         => ['keywords' => '...', 'robots' => '...'],
    'scrapedAt'   => '2026-...',
  ]
]
```

## Error Handling

```php
use ResilientLink\ResilientLinkException;

try {
    $result = $client->scrape('https://example.com');
} catch (ResilientLinkException $e) {
    echo $e->getMessage();    // human-readable error
    echo $e->getStatusCode(); // 429 = rate limit, 401 = bad key
    print_r($e->getBody());   // full response body
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

## Get Your API Key

Sign up at [resilientlink.io](https://resilientlink.io) → Dashboard → API Key.
