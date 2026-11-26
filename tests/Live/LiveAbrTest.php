<?php

declare(strict_types=1);

/*
 * Integration tests against the real ABR. Opt in with:
 *
 *     ABR_GUID=your-guid composer test:live
 */

use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\AbrClient;
use AthenaNetworks\Abr\Exceptions\AuthenticationException;
use AthenaNetworks\Abr\Exceptions\NotFoundException;
use AthenaNetworks\Abr\Query\AbnStatusQuery;
use AthenaNetworks\Abr\Query\CharityQuery;
use AthenaNetworks\Abr\Query\NameQuery;
use AthenaNetworks\Abr\State;

beforeEach(function () {
    if (liveGuid() === null) {
        $this->markTestSkipped('Set ABR_GUID to run live ABR tests.');
    }
    $this->abr = new AbrClient((string) liveGuid());
});

it('looks up a live ABN', function () {
    $entity = $this->abr->lookupAbn('33051775556');

    expect($entity->name())->toBe('TELSTRA CORPORATION LIMITED')
        ->and($entity->status())->toBe(AbnStatus::Active)
        ->and($entity->acn?->value)->toBe('051775556');
});

it('looks up a live ACN', function () {
    expect($this->abr->lookupAcn('051775556')->abn->value)->toBe('33051775556');
});

it('fetches history', function () {
    expect(count($this->abr->lookupAbn('33051775556', withHistory: true)->mainNames))->toBeGreaterThan(1);
});

it('throws NotFound for an unregistered but well-formed ABN', function () {
    $this->abr->lookupAbn('10000000000');
})->throws(NotFoundException::class);

it('rejects a bad GUID', function () {
    (new AbrClient('00000000-0000-0000-0000-000000000000'))->lookupAbn('33051775556');
})->throws(AuthenticationException::class);

it('searches names', function () {
    $results = $this->abr->searchByName(NameQuery::for('telstra')->activeOnly()->inStates(State::VIC)->limit(5));

    expect($results->count())->toBeGreaterThan(0)->toBeLessThanOrEqual(5)
        ->and($results->first()?->state)->toBe(State::VIC);
});

it('returns empty results for an unmatched name', function () {
    expect($this->abr->searchByName('zzqqxxnonexistentcoqq')->isEmpty())->toBeTrue();
});

it('searches postcode, status and charities', function () {
    expect($this->abr->searchByPostcode('2913')->count())->toBeGreaterThan(0)
        ->and($this->abr->searchByStatus(AbnStatusQuery::inPostcode('2600')->entityType('PRV')->activeOnly())->count())->toBeGreaterThan(0)
        ->and($this->abr->searchCharities(CharityQuery::create()->inPostcode('2600')->charityType('PBI'))->count())->toBeGreaterThan(0);
});
