<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Tests;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * A PSR-18 client that replays queued responses and records every request it receives.
 */
final class FakeHttpClient implements ClientInterface
{
    /** @var list<ResponseInterface|Throwable> */
    private array $queue = [];

    /** @var list<RequestInterface> */
    public array $requests = [];

    public static function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/Fixtures/' . $name . '.xml');
    }

    public function queueFixture(string $name): self
    {
        return $this->queue(new Response(200, ['Content-Type' => 'text/xml; charset=utf-8'], self::fixture($name)));
    }

    public function queue(ResponseInterface|Throwable $response): self
    {
        $this->queue[] = $response;

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $next = array_shift($this->queue) ?? throw new \LogicException('No fake response queued.');
        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }

    public function lastRequest(): RequestInterface
    {
        return $this->requests[array_key_last($this->requests)] ?? throw new \LogicException('No requests sent.');
    }

    /** @return array<string, string> */
    public function lastParameters(): array
    {
        $request = $this->lastRequest();
        $body = $request->getBody();
        $body->rewind();
        parse_str($body->getContents(), $parameters);

        /** @var array<string, string> $parameters */
        return $parameters;
    }
}
