<?php

declare(strict_types=1);

use AthenaNetworks\Abr\AbrClient;
use AthenaNetworks\Abr\Tests\FakeHttpClient;
use GuzzleHttp\Psr7\HttpFactory;

pest()->group('live')->in('Live');

/**
 * An AbrClient wired to a fake HTTP client. Returns [client, fake].
 *
 * @return array{0: AbrClient, 1: FakeHttpClient}
 */
function fakeAbr(string ...$fixtures): array
{
    $http = new FakeHttpClient();
    foreach ($fixtures as $fixture) {
        $http->queueFixture($fixture);
    }

    $factory = new HttpFactory();

    return [new AbrClient('test-guid', $http, $factory, $factory), $http];
}

function liveGuid(): ?string
{
    $guid = getenv('ABR_GUID');

    return is_string($guid) && $guid !== '' ? $guid : null;
}
