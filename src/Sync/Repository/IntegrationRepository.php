<?php

namespace Sync\Repository;

use Exception;
use Sync\Model\Account;
use Sync\Model\Integration;

class IntegrationRepository
{
    /**
     * Save integration to db
     *
     * @param Integration $integration
     * @return void
     */
    public function save(Integration $integration): void // TODO
    {
        $integration->save();
    }

    /**
     * Get integration by account_id
     *
     * @param int|null $accountId
     * @return Integration
     * @throws Exception
     */
    public function getIntegration(?int $accountId): Integration
    {
        $account = (new Account())::find($accountId); // TODO
        if (is_null($account)) {
            throw new Exception('create integration first !');
        }
        return $account->integration;
    }

    /**
     * Get account_id by kommo id
     *
     * @param string $accountId
     * @return int|null
     */
    public function getAccountIdByKommoId(string $accountId): ?int
    {
        $account = (new Account())::where('kommo_id', '=', $accountId)->first(); // TODO
        return $account !== null ? $account->getAccountId() : null; // TODO
    }

    /**
     * Get account id by client id
     *
     * @param string $clientId
     * @return int
     */
    public function getAccountIdByClientId(string $clientId): int
    {
        $integration = (new Integration())::where('client_id', '=', $clientId)->first(); // TODO
        return $integration->getAccountId();
    }
}
