<?php

declare(strict_types=1);

use AthenaNetworks\Abr\Acn;
use AthenaNetworks\Abr\Exceptions\InvalidIdentifierException;

it('validates the ASIC checksum', function (string $acn, bool $valid) {
    expect(Acn::isValid($acn))->toBe($valid);
})->with([
    ['005749986', true],
    ['051775556', true],
    ['000000019', true],
    ['010 499 966', true],
    ['005749987', false],
    ['123456789', false],
    ['12345678', false],
    ['abcdefghi', false],
]);

it('normalises and formats', function () {
    $acn = Acn::fromString('051-775-556');

    expect($acn->value)->toBe('051775556')
        ->and($acn->formatted())->toBe('051 775 556')
        ->and($acn->equals('051 775 556'))->toBeTrue();
});

it('throws for an invalid ACN', function () {
    Acn::fromString('123456789');
})->throws(InvalidIdentifierException::class);
