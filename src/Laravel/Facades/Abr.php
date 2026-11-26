<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Laravel\Facades;

use AthenaNetworks\Abr\Contracts\AbrClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \AthenaNetworks\Abr\Entity\BusinessEntity lookupAbn(\AthenaNetworks\Abr\Abn|string $abn, bool $withHistory = false)
 * @method static \AthenaNetworks\Abr\Entity\BusinessEntity lookupAcn(\AthenaNetworks\Abr\Acn|string $acn, bool $withHistory = false)
 * @method static \AthenaNetworks\Abr\Search\SearchResults searchByName(\AthenaNetworks\Abr\Query\NameQuery|string $query)
 * @method static \AthenaNetworks\Abr\Search\AbnList searchByPostcode(string $postcode)
 * @method static \AthenaNetworks\Abr\Search\AbnList searchByStatus(\AthenaNetworks\Abr\Query\AbnStatusQuery $query)
 * @method static \AthenaNetworks\Abr\Search\AbnList searchCharities(\AthenaNetworks\Abr\Query\CharityQuery $query)
 *
 * @see \AthenaNetworks\Abr\AbrClient
 */
final class Abr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AbrClient::class;
    }
}
