<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Http;

use AthenaNetworks\Abr\Exceptions\AuthenticationException;
use AthenaNetworks\Abr\Exceptions\ServiceException;
use AthenaNetworks\Abr\Exceptions\TransportException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Calls ABR operations through the HTTP POST binding (no SOAP envelope needed).
 *
 * @internal
 */
final readonly class Transport
{
    public function __construct(
        private ClientInterface $http,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $endpoint,
        #[\SensitiveParameter]
        private string $guid,
    ) {
    }

    /**
     * @param array<string, string> $parameters Operation parameters, excluding the GUID.
     *
     * @return string The raw XML body.
     */
    public function call(string $operation, array $parameters): string
    {
        // Trimmed because the ABR rejects a GUID with surrounding whitespace (such as the trailing
        // newline of a secrets file) as "not recognised as a Registered Party".
        $guid = trim($this->guid);
        if ($guid === '') {
            throw new AuthenticationException(
                'No ABR authentication GUID is configured. Register for one at https://abr.business.gov.au/Tools/WebServices',
            );
        }

        $parameters['authenticationGuid'] = $guid;

        $request = $this->requestFactory
            ->createRequest('POST', rtrim($this->endpoint, '/') . '/' . $operation)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'text/xml, application/xml')
            ->withBody($this->streamFactory->createStream(http_build_query($parameters, '', '&', PHP_QUERY_RFC3986)));

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('Could not reach the ABR: ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            // The ABR sends plain-text bodies such as "Missing parameter: postcode." with HTTP 500.
            $detail = trim(strip_tags($body));
            throw new ServiceException(
                sprintf('The ABR returned HTTP %d%s', $status, $detail === '' ? '.' : ': ' . substr($detail, 0, 300)),
                httpStatus: $status,
            );
        }

        return $body;
    }
}
