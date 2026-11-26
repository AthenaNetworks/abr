<?php

declare(strict_types=1);

use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\NameType;
use AthenaNetworks\Abr\Query\AbnStatusQuery;
use AthenaNetworks\Abr\Query\CharityQuery;
use AthenaNetworks\Abr\Query\NameQuery;
use AthenaNetworks\Abr\Exceptions\InvalidQueryException;
use AthenaNetworks\Abr\Search\SearchResult;
use AthenaNetworks\Abr\State;

it('searches by name', function () {
    [$abr, $http] = fakeAbr('name-search');

    $results = $abr->searchByName(NameQuery::for('telstra')->activeOnly()->limit(5));

    expect((string) $http->lastRequest()->getUri())->toEndWith('/ABRSearchByNameAdvancedSimpleProtocol2017')
        ->and($http->lastParameters())->toMatchArray(['name' => 'telstra', 'activeABNsOnly' => 'Y', 'maxSearchResults' => '5'])
        ->and($results)->toHaveCount(5)
        ->and($results->exceedsMaximum)->toBeFalse();

    $first = $results->first();
    expect($first)->toBeInstanceOf(SearchResult::class)
        ->and($first?->abn->value)->toBe('33051775556')
        ->and($first?->abnStatus)->toBe(AbnStatus::Active)
        ->and($first?->isActive())->toBeTrue()
        ->and($first?->name)->toBe('TELSTRA')
        ->and($first?->nameType)->toBe(NameType::MainTradingName)
        ->and($first?->nameType->isTradingName())->toBeTrue()
        ->and($first?->score)->toBe(99)
        ->and($first?->isCurrentName)->toBeTrue()
        ->and($first?->state)->toBe(State::VIC)
        ->and($first?->postcode)->toBe('3000');

    expect($results->results[3]->nameType)->toBe(NameType::MainName)
        ->and($results->results[3]->name)->toBe('TELSTRA LIMITED');
});

it('accepts a plain string as a name query', function () {
    [$abr, $http] = fakeAbr('name-search');

    $abr->searchByName('telstra');

    expect($http->lastParameters()['name'])->toBe('telstra');
});

it('parses individual names in search results', function () {
    [$abr] = fakeAbr('name-search-individual');

    $result = $abr->searchByName(NameQuery::for('john smith')->legalNames())->first();

    expect($result?->nameType)->toBe(NameType::LegalName)
        ->and($result?->name)->toBe('JOHN SMITH')
        ->and($result?->state)->toBe(State::QLD);
});

it('returns empty results rather than throwing when nothing matches', function () {
    [$abr] = fakeAbr('name-search-empty');

    $results = $abr->searchByName('zzqqxxnonexistentcoqq');

    expect($results->isEmpty())->toBeTrue()
        ->and($results)->toHaveCount(0)
        ->and($results->first())->toBeNull();
});

it('filters and de-duplicates results', function () {
    [$abr] = fakeAbr('name-search');

    $results = $abr->searchByName('telstra');

    expect($results->filter(fn (SearchResult $r) => $r->state === State::NSW))->toHaveCount(1)
        ->and($results->uniqueByAbn())->toHaveCount(5)
        ->and(iterator_to_array($results))->toHaveCount(5);
});

it('searches by postcode', function () {
    [$abr, $http] = fakeAbr('postcode-list');

    $abns = $abr->searchByPostcode('2913');

    expect((string) $http->lastRequest()->getUri())->toEndWith('/SearchByPostcode')
        ->and($http->lastParameters())->toBe(['postcode' => '2913', 'authenticationGuid' => 'test-guid'])
        ->and($abns)->toHaveCount(3)
        ->and($abns->toStrings())->each->toMatch('/^\d{11}$/');
});

it('rejects a malformed postcode without calling the ABR', function () {
    [$abr, $http] = fakeAbr();

    expect(fn () => $abr->searchByPostcode('abc'))->toThrow(InvalidQueryException::class)
        ->and($http->requests)->toBe([]);
});

it('searches charities', function () {
    [$abr, $http] = fakeAbr('charity-list');

    $abns = $abr->searchCharities(CharityQuery::create()->inPostcode('2600'));

    expect((string) $http->lastRequest()->getUri())->toEndWith('/SearchByCharity')
        ->and($http->lastParameters())->toMatchArray(['postcode' => '2600', 'state' => ''])
        ->and($abns)->toHaveCount(164)
        ->and($abns->contains('12003741878'))->toBeTrue()
        ->and($abns->contains('51824753556'))->toBeFalse();
});

it('searches by ABN status', function () {
    [$abr, $http] = fakeAbr('postcode-list');

    $abr->searchByStatus(AbnStatusQuery::inPostcode('2600')->activeOnly());

    expect((string) $http->lastRequest()->getUri())->toEndWith('/SearchByABNStatus')
        ->and($http->lastParameters())->toMatchArray(['postcode' => '2600', 'activeABNsOnly' => 'Y']);
});
