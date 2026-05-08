<?php

declare(strict_types=1);

namespace ResilientLink;

/**
 * ResilientLink PHP SDK
 * Official client for the ResilientLink Web Scraping API.
 *
 * Usage:
 *   $client = new \ResilientLink\Client(['api_key' => 'YOUR_API_KEY']);
 *   $result = $client->scrape('https://example.com');
 *   echo $result['data']['title'];
 */

class ResilientLinkException extends \RuntimeException
{
    private int    $statusCode;
    private array  $body;

    public function __construct(string $message, int $statusCode = 0, array $body = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->body       = $body;
    }

    public function getStatusCode(): int  { return $this->statusCode; }
    public function getBody(): array      { return $this->body; }
}

class Client
{
    private const DEFAULT_BASE_URL = 'https://resilientlink-api.vercel.app';
    private const DEFAULT_TIMEOUT  = 60;
    private const SDK_VERSION      = '1.0.0';

    private string $apiKey;
    private string $baseUrl;
    private int    $timeout;

    /**
     * @param array $options {
     *   @type string $api_key   Your ResilientLink API key (required)
     *   @type string $base_url  Override API base URL
     *   @type int    $timeout   Request timeout in seconds (default: 60)
     * }
     */
    public function __construct(array $options = [])
    {
        if (empty($options['api_key'])) {
            throw new \InvalidArgumentException('ResilientLink: api_key is required.');
        }

        $this->apiKey  = $options['api_key'];
        $this->baseUrl = rtrim($options['base_url'] ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = (int) ($options['timeout'] ?? self::DEFAULT_TIMEOUT);
    }

    // ── SCRAPE ────────────────────────────────────────────────────────────────

    /**
     * Scrape a URL and return structured metadata.
     *
     * @param  string $url     The URL to scrape (required)
     * @param  array  $options {
     *   @type bool   $return_html        Include raw HTML in response
     *   @type bool   $screenshot         Return base64 PNG screenshot (Pro/Enterprise)
     *   @type bool   $full_page          Full-page screenshot (default: true)
     *   @type bool   $pdf                Return base64 PDF (Pro/Enterprise)
     *   @type string $pdf_format         PDF format: 'A4', 'Letter', etc. (default: 'A4')
     *   @type bool   $pdf_background     Include background in PDF (default: true)
     *   @type bool   $pdf_landscape      Landscape PDF orientation
     *   @type bool   $bypass_cache       Skip cache, force fresh scrape
     *   @type bool   $js_render          Enable JavaScript rendering (Pro/Enterprise)
     *   @type string $wait_for_selector  CSS selector to wait for before scraping
     *   @type string $wait_until         Puppeteer waitUntil: 'networkidle0', 'load', etc.
     *   @type int    $wait_ms            Extra milliseconds to wait before scraping
     *   @type array  $custom_headers     HTTP headers to forward with the request
     *   @type string $custom_js          JavaScript to execute on the page (Enterprise)
     *   @type bool   $return_cookies     Return cookies from the page
     *   @type array  $block_resources    Resource types to block: ['media','font']
     *   @type int    $timeout            Per-request timeout in ms (max 60000)
     * }
     * @return array Scrape result
     * @throws ResilientLinkException
     */
    public function scrape(string $url, array $options = []): array
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('url is required.');
        }

        $body = array_filter([
            'url'               => $url,
            'return_html'       => $options['return_html']       ?? null,
            'screenshot'        => $options['screenshot']        ?? null,
            'full_page'         => $options['full_page']         ?? null,
            'pdf'               => $options['pdf']               ?? null,
            'pdf_format'        => $options['pdf_format']        ?? null,
            'pdf_background'    => $options['pdf_background']    ?? null,
            'pdf_landscape'     => $options['pdf_landscape']     ?? null,
            'bypass_cache'      => $options['bypass_cache']      ?? null,
            'js_render'         => $options['js_render']         ?? null,
            'wait_for_selector' => $options['wait_for_selector'] ?? null,
            'wait_until'        => $options['wait_until']        ?? null,
            'wait_ms'           => $options['wait_ms']           ?? null,
            'custom_headers'    => $options['custom_headers']    ?? null,
            'custom_js'         => $options['custom_js']         ?? null,
            'return_cookies'    => $options['return_cookies']    ?? null,
            'block_resources'   => $options['block_resources']   ?? null,
            'timeout'           => $options['timeout']           ?? null,
        ], fn($v) => $v !== null);

        return $this->request('POST', '/api/scrape', $body);
    }

    // ── INTERNAL HTTP ─────────────────────────────────────────────────────────

    private function request(string $method, string $path, array $body = []): array
    {
        $url     = $this->baseUrl . $path;
        $payload = json_encode($body);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'X-API-Key: '     . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: resilientlink-php/' . self::SDK_VERSION,
                'Content-Length: ' . strlen($payload),
            ],
        ]);

        $response   = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new ResilientLinkException('cURL error: ' . $curlError);
        }

        $parsed = json_decode($response, true) ?? ['raw' => $response];

        if ($httpStatus >= 400) {
            $message = $parsed['error'] ?? "HTTP {$httpStatus}";
            throw new ResilientLinkException($message, $httpStatus, $parsed);
        }

        return $parsed;
    }
}
