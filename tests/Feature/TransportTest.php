<?php

declare(strict_types=1);

use AthenaNetworks\Abr\AbrClient;
use AthenaNetworks\Abr\Exceptions\AbrException;
use AthenaNetworks\Abr\Exceptions\AuthenticationException;
use AthenaNetworks\Abr\Exceptions\ServiceException;
use AthenaNetworks\Abr\Exceptions\TransportException;
use AthenaNetworks\Abr\Exceptions\UnexpectedResponseException;
use AthenaNetworks\Abr\Tests\FakeHttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

function abrWith(FakeHttpClient $http, string $guid = 'test-guid', string $endpoint = AbrClient::DEFAULT_ENDPOINT): AbrClient
{
    $factory = new HttpFactory();

    return new AbrClient($guid, $http, $factory, $factory, $endpoint);
}

it('wraps network failures in TransportException', function () {
    $http = (new FakeHttpClient())->queue(new ConnectException('Connection refused', new Request('POST', 'https://x')));

    abrWith($http)->lookupAbn('51824753556');
})->throws(TransportException::class, 'Could not reach the ABR: Connection refused');

it('turns the plain-text HTTP 500 errors into ServiceException', function () {
    $http = (new FakeHttpClient())->queue(new Response(500, ['Content-Type' => 'text/plain'], 'This feature is currently not available !'));

    try {
        abrWith($http)->searchByName('telstra');
        test()->fail('Expected an exception.');
    } catch (ServiceException $e) {
        expect($e->httpStatus)->toBe(500)
            ->and($e->getMessage())->toBe('The ABR returned HTTP 500: This feature is currently not available !');
    }
});

it('rejects a non-XML success body', function () {
    $http = (new FakeHttpClient())->queue(new Response(200, [], '<html><body>Maintenance</body'));

    abrWith($http)->lookupAbn('51824753556');
})->throws(UnexpectedResponseException::class, 'not valid XML');

it('rejects XML that is not an ABR payload', function () {
    $http = (new FakeHttpClient())->queue(new Response(200, [], '<?xml version="1.0"?><foo/>'));

    abrWith($http)->lookupAbn('51824753556');
})->throws(UnexpectedResponseException::class);

it('fails fast without a GUID', function () {
    $http = new FakeHttpClient();

    expect(fn () => abrWith($http, guid: '  ')->lookupAbn('51824753556'))->toThrow(AuthenticationException::class)
        ->and($http->requests)->toBe([]);
});

it('uses a custom endpoint', function () {
    $http = (new FakeHttpClient())->queueFixture('acn-company');

    abrWith($http, endpoint: 'https://proxy.example/abr/')->lookupAbn('33051775556');

    expect((string) $http->lastRequest()->getUri())->toBe('https://proxy.example/abr/SearchByABNv202001');
});

it('sends a form-encoded body', function () {
    $http = (new FakeHttpClient())->queueFixture('acn-company');

    abrWith($http)->lookupAbn('33051775556');

    expect($http->lastRequest()->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded');
});

it('throws only AbrException subclasses', function () {
    expect(is_subclass_of(TransportException::class, AbrException::class))->toBeTrue()
        ->and(is_subclass_of(ServiceException::class, AbrException::class))->toBeTrue()
        ->and(is_subclass_of(UnexpectedResponseException::class, AbrException::class))->toBeTrue();
});

it('discovers a PSR-18 client when none is given', function () {
    expect(new AbrClient('guid'))->toBeInstanceOf(AbrClient::class);
});
