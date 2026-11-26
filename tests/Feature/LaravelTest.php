<?php

declare(strict_types=1);

use AthenaNetworks\Abr\AbrClient;
use AthenaNetworks\Abr\Contracts\AbrClient as AbrClientContract;
use AthenaNetworks\Abr\Laravel\AbrServiceProvider;
use AthenaNetworks\Abr\Laravel\Facades\Abr;
use Orchestra\Testbench\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(AbrServiceProvider::class);
});

it('binds the client as a singleton', function () {
    config()->set('abr.guid', 'laravel-guid');

    $client = app(AbrClientContract::class);

    expect($client)->toBeInstanceOf(AbrClient::class)
        ->and(app(AbrClient::class))->toBe($client)
        ->and(app('abr'))->toBe($client);
});

it('merges the default config', function () {
    expect(config('abr.endpoint'))->toBe(AbrClient::DEFAULT_ENDPOINT)
        ->and(config()->has('abr.guid'))->toBeTrue();
});

it('lets apps swap the client for a fake through the facade', function () {
    $entity = fakeAbr('acn-company')[0]->lookupAbn('33051775556');
    $fake = Mockery::mock(AbrClientContract::class);
    $fake->shouldReceive('lookupAbn')->with('51824753556')->andReturn($entity);

    Abr::swap($fake);

    expect(Abr::lookupAbn('51824753556'))->toBe($entity);
});
