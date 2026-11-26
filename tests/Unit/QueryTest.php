<?php

declare(strict_types=1);

use AthenaNetworks\Abr\Exceptions\InvalidQueryException;
use AthenaNetworks\Abr\Query\AbnStatusQuery;
use AthenaNetworks\Abr\Query\CharityQuery;
use AthenaNetworks\Abr\Query\NameQuery;
use AthenaNetworks\Abr\SearchWidth;
use AthenaNetworks\Abr\State;

it('sends every name-search parameter, with all filters on by default', function () {
    expect(NameQuery::for('  Athena  ')->toParameters())->toBe([
        'name' => 'Athena',
        'postcode' => '',
        'legalName' => 'Y',
        'tradingName' => 'Y',
        'businessName' => 'Y',
        'activeABNsOnly' => 'N',
        'NSW' => 'Y', 'VIC' => 'Y', 'QLD' => 'Y', 'SA' => 'Y',
        'WA' => 'Y', 'TAS' => 'Y', 'NT' => 'Y', 'ACT' => 'Y',
        'searchWidth' => '',
        'minimumScore' => '',
        'maxSearchResults' => '',
    ]);
});

it('narrows name types, states and other options', function () {
    $params = NameQuery::for('Athena')
        ->businessNames()
        ->inStates(State::NSW, State::ACT)
        ->inPostcode('2600')
        ->activeOnly()
        ->width(SearchWidth::Narrow)
        ->minimumScore(90)
        ->limit(10)
        ->toParameters();

    expect($params)->toMatchArray([
        'postcode' => '2600',
        'legalName' => 'N',
        'tradingName' => 'N',
        'businessName' => 'Y',
        'activeABNsOnly' => 'Y',
        'NSW' => 'Y', 'ACT' => 'Y', 'VIC' => 'N', 'QLD' => 'N',
        'searchWidth' => 'narrow',
        'minimumScore' => '90',
        'maxSearchResults' => '10',
    ]);
});

it('is immutable', function () {
    $base = NameQuery::for('Athena');
    $base->activeOnly()->inStates(State::VIC);

    expect($base->toParameters()['activeABNsOnly'])->toBe('N')
        ->and($base->toParameters()['VIC'])->toBe('Y')
        ->and($base->toParameters()['NSW'])->toBe('Y');
});

it('rejects invalid name queries', function () {
    expect(fn () => NameQuery::for('   '))->toThrow(InvalidQueryException::class)
        ->and(fn () => NameQuery::for('x')->minimumScore(101))->toThrow(InvalidQueryException::class)
        ->and(fn () => NameQuery::for('x')->minimumScore(-1))->toThrow(InvalidQueryException::class)
        ->and(fn () => NameQuery::for('x')->limit(0))->toThrow(InvalidQueryException::class)
        ->and(fn () => NameQuery::for('x')->inPostcode('26000'))->toThrow(InvalidQueryException::class);
});

it('builds charity parameters', function () {
    expect(CharityQuery::create()->inState(State::NSW)->charityType('PBI')->toParameters())->toBe([
        'postcode' => '',
        'state' => 'NSW',
        'charityTypeCode' => 'PBI',
        'concessionTypeCode' => '',
    ]);
});

it('builds ABN status parameters', function () {
    expect(AbnStatusQuery::inPostcode('2600')->activeOnly()->gstRegisteredOnly()->entityType('prv')->toParameters())->toBe([
        'postcode' => '2600',
        'activeABNsOnly' => 'Y',
        'currentGSTRegistrationOnly' => 'Y',
        'entityTypeCode' => 'PRV',
    ]);
});

it('requires a valid postcode for status searches', function () {
    AbnStatusQuery::inPostcode('');
})->throws(InvalidQueryException::class);
