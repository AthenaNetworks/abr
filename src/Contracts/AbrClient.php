<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Contracts;

use AthenaNetworks\Abr\Abn;
use AthenaNetworks\Abr\Acn;
use AthenaNetworks\Abr\Entity\BusinessEntity;
use AthenaNetworks\Abr\Exceptions\AbrException;
use AthenaNetworks\Abr\Exceptions\InvalidIdentifierException;
use AthenaNetworks\Abr\Exceptions\InvalidQueryException;
use AthenaNetworks\Abr\Exceptions\NotFoundException;
use AthenaNetworks\Abr\Query\AbnStatusQuery;
use AthenaNetworks\Abr\Query\CharityQuery;
use AthenaNetworks\Abr\Query\NameQuery;
use AthenaNetworks\Abr\Search\AbnList;
use AthenaNetworks\Abr\Search\SearchResults;

/**
 * Type-hint against this interface so you can swap in a fake in your own tests.
 */
interface AbrClient
{
    /**
     * Fetch the full record for an ABN.
     *
     * @param bool $withHistory Include historical names, addresses, statuses and GST periods.
     *
     * @throws InvalidIdentifierException The ABN is malformed (checked locally, before any request).
     * @throws NotFoundException          The ABN is well formed but not on the register.
     * @throws AbrException
     */
    public function lookupAbn(Abn|string $abn, bool $withHistory = false): BusinessEntity;

    /**
     * Fetch the full record for the ABN held by a company (ACN, ARBN or ARSN).
     *
     * @throws InvalidIdentifierException
     * @throws NotFoundException
     * @throws AbrException
     */
    public function lookupAcn(Acn|string $acn, bool $withHistory = false): BusinessEntity;

    /**
     * Search legal, trading and business names. Returns an empty result set when nothing matches.
     *
     * @throws InvalidQueryException
     * @throws AbrException
     */
    public function searchByName(NameQuery|string $query): SearchResults;

    /**
     * Every currently active ABN in a postcode.
     *
     * @throws InvalidQueryException
     * @throws AbrException
     */
    public function searchByPostcode(string $postcode): AbnList;

    /**
     * ABNs in a postcode, filtered by status, GST registration and entity type.
     *
     * @throws AbrException
     */
    public function searchByStatus(AbnStatusQuery $query): AbnList;

    /**
     * ABNs that hold charity tax concessions.
     *
     * @throws AbrException
     */
    public function searchCharities(CharityQuery $query): AbnList;
}
