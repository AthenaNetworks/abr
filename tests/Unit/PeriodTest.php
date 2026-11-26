<?php

declare(strict_types=1);

use AthenaNetworks\Abr\Period;

it('is current when it has no end date', function () {
    expect((new Period(new DateTimeImmutable('2020-01-01')))->isCurrent())->toBeTrue()
        ->and((new Period(new DateTimeImmutable('2020-01-01'), new DateTimeImmutable('2021-01-01')))->isCurrent())->toBeFalse();
});

it('knows whether it contains a date', function () {
    $period = new Period(new DateTimeImmutable('2020-01-01'), new DateTimeImmutable('2020-12-31'));

    expect($period->contains(new DateTimeImmutable('2020-06-01')))->toBeTrue()
        ->and($period->contains(new DateTimeImmutable('2020-12-31 23:00')))->toBeTrue()
        ->and($period->contains(new DateTimeImmutable('2021-01-01')))->toBeFalse()
        ->and($period->contains(new DateTimeImmutable('2019-12-31')))->toBeFalse();
});

it('serialises to ISO dates', function () {
    expect(json_encode(new Period(new DateTimeImmutable('2020-01-01'))))->toBe('{"from":"2020-01-01","to":null}');
});
