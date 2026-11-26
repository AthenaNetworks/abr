<?php

declare(strict_types=1);

use AthenaNetworks\Abr\Abn;
use AthenaNetworks\Abr\Exceptions\InvalidIdentifierException;

it('accepts valid ABNs in any common format', function (string $input) {
    expect(Abn::fromString($input)->value)->toBe('51824753556');
})->with(['51824753556', '51 824 753 556', ' 51-824-753-556 ']);

it('validates the mod-89 checksum', function (string $abn, bool $valid) {
    expect(Abn::isValid($abn))->toBe($valid);
})->with([
    ['51824753556', true],
    ['33051775556', true],
    ['50169561394', true],
    ['51824753557', false],
    ['12345678901', false],
    ['01824753556', false],
    ['5182475355', false],
    ['518247535560', false],
    ['5182475355a', false],
    ['', false],
]);

it('throws for an invalid ABN', function () {
    Abn::fromString('12345678901');
})->throws(InvalidIdentifierException::class, '"12345678901" is not a valid ABN.');

it('returns null from tryFromString for an invalid ABN', function () {
    expect(Abn::tryFromString('nope'))->toBeNull();
});

it('formats, compares and serialises', function () {
    $abn = Abn::fromString('51824753556');

    expect($abn->formatted())->toBe('51 824 753 556')
        ->and((string) $abn)->toBe('51824753556')
        ->and(json_encode($abn))->toBe('"51824753556"')
        ->and($abn->equals('51 824 753 556'))->toBeTrue()
        ->and($abn->equals(Abn::fromString('33051775556')))->toBeFalse();
});
