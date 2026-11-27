<?php

declare(strict_types=1);

/*
 * Real ABR responses recorded on 2026-11-28 (GUID scrubbed), covering the lookups and the
 * state-filtered name search that applications rely on most.
 */

use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\NameType;
use AthenaNetworks\Abr\Query\NameQuery;
use AthenaNetworks\Abr\State;

it('parses a private company lookup', function () {
    [$abr] = fakeAbr('abn-company-ccts');

    $entity = $abr->lookupAbn('60003792881');

    expect($entity->name())->toBe('CENTRAL COAST TELEPHONE SYSTEMS PTY LIMITED')
        ->and($entity->status())->toBe(AbnStatus::Active)
        ->and($entity->isActive())->toBeTrue()
        ->and($entity->entityType->code)->toBe('PRV')
        ->and($entity->acn?->value)->toBe('003792881')
        ->and($entity->address()?->state)->toBe(State::NSW)
        ->and($entity->address()?->postcode)->toBe('2250')
        ->and($entity->isGstRegistered())->toBeTrue()
        ->and($entity->gstRegisteredFrom()?->format('Y-m-d'))->toBe('2000-07-01')
        ->and($entity->tradingNames())->toBe(['CENTRAL COAST TELEPHONES SYSTEMS'])
        ->and($entity->currentBusinessNames())->toBe(['CCTS Technologies', 'CCTS TELECOMMUNICATIONS']);
});

it('parses a cancelled sole trader with a middle name', function () {
    [$abr] = fakeAbr('abn-individual-cancelled');

    $entity = $abr->lookupAbn('30120904895');

    expect($entity->isIndividual())->toBeTrue()
        ->and($entity->status())->toBe(AbnStatus::Cancelled)
        ->and($entity->isActive())->toBeFalse()
        ->and($entity->name())->toBe('JOHN GEOFFREY SMITH')
        ->and($entity->isGstRegistered())->toBeFalse()
        ->and($entity->address()?->state)->toBe(State::NSW);
});

it('parses a state-filtered name search', function () {
    [$abr, $http] = fakeAbr('name-search-state');

    $results = $abr->searchByName(NameQuery::for('Central Coast Telephone Systems')->inStates(State::NSW));

    expect($http->lastParameters())->toMatchArray(['NSW' => 'Y', 'VIC' => 'N', 'QLD' => 'N', 'ACT' => 'N'])
        ->and($results)->toHaveCount(30)
        ->and($results->filter(fn ($r) => $r->state !== State::NSW))->toHaveCount(0);

    [$first, $second] = $results->results;
    expect($first->abn->value)->toBe('60003792881')
        ->and($first->nameType)->toBe(NameType::MainName)
        ->and($first->score)->toBe(99)
        ->and($first->isCurrentName)->toBeTrue()
        ->and($second->abn->value)->toBe('60003792881')
        ->and($second->nameType)->toBe(NameType::MainTradingName)
        ->and($results->uniqueByAbn()->first()?->nameType)->toBe(NameType::MainName);

    $historical = $results->filter(fn ($r) => $r->abn->value === '40883925541')->first();
    expect($historical?->nameType)->toBe(NameType::BusinessName)
        ->and($historical?->isCurrentName)->toBeFalse()
        ->and($historical?->abnStatus)->toBe(AbnStatus::Cancelled);
});
