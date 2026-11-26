<?php

declare(strict_types=1);

use AthenaNetworks\Abr\Abn;
use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\Acn;
use AthenaNetworks\Abr\Exceptions\AuthenticationException;
use AthenaNetworks\Abr\Exceptions\InvalidIdentifierException;
use AthenaNetworks\Abr\Exceptions\NotFoundException;
use AthenaNetworks\Abr\State;

it('looks up a company by ABN', function () {
    [$abr, $http] = fakeAbr('acn-company');

    $entity = $abr->lookupAbn('33 051 775 556');

    expect($http->lastRequest()->getMethod())->toBe('POST')
        ->and((string) $http->lastRequest()->getUri())->toBe('https://abr.business.gov.au/abrxmlsearch/abrxmlsearch.asmx/SearchByABNv202001')
        ->and($http->lastParameters())->toBe([
            'searchString' => '33051775556',
            'includeHistoricalDetails' => 'N',
            'authenticationGuid' => 'test-guid',
        ]);

    expect($entity->abn->value)->toBe('33051775556')
        ->and($entity->acn?->value)->toBe('051775556')
        ->and($entity->name())->toBe('TELSTRA CORPORATION LIMITED')
        ->and($entity->status())->toBe(AbnStatus::Active)
        ->and($entity->isActive())->toBeTrue()
        ->and($entity->isIndividual())->toBeFalse()
        ->and($entity->entityType->code)->toBe('PUB')
        ->and($entity->entityType->description)->toBe('Australian Public Company')
        ->and($entity->entityType->isCompany())->toBeTrue()
        ->and($entity->isGstRegistered())->toBeTrue()
        ->and($entity->gstRegisteredFrom()?->format('Y-m-d'))->toBe('2000-07-01')
        ->and($entity->address()?->state)->toBe(State::VIC)
        ->and($entity->address()?->postcode)->toBe('3000')
        ->and($entity->tradingNames())->toBe(['TELSTRA', 'TELSTRA CORPORATION LIMITED'])
        ->and($entity->currentBusinessNames())->toContain('TELSTRA INFRACO', 'YELLOW PAGES')
        ->and($entity->recordLastUpdated?->format('Y-m-d'))->toBe('2024-02-15')
        ->and($entity->isCharity())->toBeFalse();
});

it('treats the 0001-01-01 sentinel as an open-ended date', function () {
    [$abr] = fakeAbr('acn-company');

    $status = $abr->lookupAbn('33051775556')->statusHistory[0];

    expect($status->period->from?->format('Y-m-d'))->toBe('1999-11-01')
        ->and($status->period->to)->toBeNull()
        ->and($status->period->isCurrent())->toBeTrue();
});

it('parses an individual (sole trader)', function () {
    [$abr] = fakeAbr('abn-individual');

    $entity = $abr->lookupAbn('14977152310');

    expect($entity->isIndividual())->toBeTrue()
        ->and($entity->name())->toBe('JOHN SMITH')
        ->and($entity->legalNames[0]->givenName)->toBe('JOHN')
        ->and($entity->legalNames[0]->otherGivenName)->toBeNull()
        ->and($entity->legalNames[0]->familyName)->toBe('SMITH')
        ->and($entity->mainNames)->toBe([])
        ->and($entity->acn)->toBeNull()
        ->and($entity->isGstRegistered())->toBeFalse()
        ->and($entity->gstRegisteredFrom())->toBeNull();
});

it('parses a cancelled ABN', function () {
    [$abr] = fakeAbr('abn-cancelled-government');

    $entity = $abr->lookupAbn('51835430479');

    expect($entity->status())->toBe(AbnStatus::Cancelled)
        ->and($entity->isActive())->toBeFalse()
        ->and($entity->name())->toBe('DEPARTMENT OF INDUSTRY TOURISM AND RESOURCES')
        ->and($entity->entityType->code)->toBe('CGE');
});

it('parses charity, DGR and ACNC details', function () {
    [$abr] = fakeAbr('abn-charity');

    $entity = $abr->lookupAbn('50169561394');

    expect($entity->name())->toBe('AUSTRALIAN RED CROSS SOCIETY')
        ->and($entity->isCharity())->toBeTrue()
        ->and($entity->isAcncRegistered())->toBeTrue()
        ->and($entity->isDgrEndorsed())->toBeTrue()
        ->and($entity->dgrEndorsements[0]->endorsement)->toBe('Endorsed DGR')
        ->and($entity->dgrEndorsements[0]->itemNumber)->toBe('Item 1')
        ->and($entity->dgrFunds)->toHaveCount(2)
        ->and($entity->dgrFunds[1]->name())->toBe('AUSTRALIAN RED CROSS BLOOD SERVICE')
        ->and($entity->charityTypes[0]->description)->toBe('Public Benevolent Institution')
        ->and(array_map(fn ($c) => $c->type, $entity->taxConcessions))
            ->toBe(['GST Concession', 'FBT Exemption', 'Income Tax Exemption'])
        ->and($entity->acncRegistrations[0]->status)->toBe('Registered')
        ->and($entity->otherTradingNames[1]->name)->toBe('AUSTRALIAN RED CROSS BLOOD SERVICE - NSW & ACT');
});

it('includes historical records when asked', function () {
    [$abr, $http] = fakeAbr('abn-with-history');

    $entity = $abr->lookupAbn(Abn::fromString('33051775556'), withHistory: true);

    expect($http->lastParameters()['includeHistoricalDetails'])->toBe('Y')
        ->and($entity->mainNames)->toHaveCount(2)
        ->and($entity->mainNames[1]->name)->toBe('TELSTRA CORP LTD')
        ->and($entity->mainNames[1]->period->isCurrent())->toBeFalse()
        ->and($entity->mainNames[1]->period->to?->format('Y-m-d'))->toBe('2000-01-20')
        ->and($entity->name())->toBe('TELSTRA CORPORATION LIMITED')
        ->and(count($entity->addresses))->toBeGreaterThan(1)
        ->and($entity->address()?->period->isCurrent())->toBeTrue();
});

it('looks up by ACN', function () {
    [$abr, $http] = fakeAbr('acn-company');

    $entity = $abr->lookupAcn('051 775 556');

    expect((string) $http->lastRequest()->getUri())->toEndWith('/SearchByASICv201408')
        ->and($http->lastParameters()['searchString'])->toBe('051775556')
        ->and($entity->acn?->equals(Acn::fromString('051775556')))->toBeTrue()
        ->and($entity->abn->value)->toBe('33051775556');
});

it('rejects a malformed ABN or ACN without calling the ABR', function () {
    [$abr, $http] = fakeAbr();

    expect(fn () => $abr->lookupAbn('12345678901'))->toThrow(InvalidIdentifierException::class)
        ->and(fn () => $abr->lookupAcn('123456789'))->toThrow(InvalidIdentifierException::class)
        ->and($http->requests)->toBe([]);
});

it('throws NotFoundException when the ABN is not on the register', function () {
    [$abr] = fakeAbr('not-found');

    $abr->lookupAbn('10000000000');
})->throws(NotFoundException::class, 'No records found');

it('throws AuthenticationException for an unrecognised GUID', function () {
    [$abr] = fakeAbr('bad-guid');

    $abr->lookupAbn('51835430479');
})->throws(AuthenticationException::class, 'not recognised as a Registered Party');

it('maps the ABR "not a valid ABN" error to InvalidIdentifierException', function () {
    [$abr] = fakeAbr('invalid-identifier');

    $abr->lookupAbn('51835430479');
})->throws(InvalidIdentifierException::class, 'Search text is not a valid ABN or ACN');

it('serialises an entity to a compact JSON summary', function () {
    [$abr] = fakeAbr('acn-company');

    $json = json_decode((string) json_encode($abr->lookupAbn('33051775556')), true);

    expect($json)->toMatchArray([
        'abn' => '33051775556',
        'acn' => '051775556',
        'name' => 'TELSTRA CORPORATION LIMITED',
        'status' => 'Active',
        'entity_type' => ['code' => 'PUB', 'description' => 'Australian Public Company'],
        'gst_registered' => true,
        'state' => 'VIC',
        'postcode' => '3000',
    ]);
});
