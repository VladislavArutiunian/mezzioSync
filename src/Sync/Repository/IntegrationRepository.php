<?php

namespace Sync\Repository;

use Exception;
use Sync\Model\Account;
use Sync\Model\Integration;

class IntegrationRepository
{
    /**
     * Get integration by account_id
     *
     * @param int|null $accountId
     * @return Integration
     * @throws Exception
     */
    public function getIntegration(?int $accountId): Integration
    {
        $account = Account::find($accountId);

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
        $account = Account::where('kommo_id', '=', $accountId)->first();
        return $account !== null ? $account->id : null; // TODO
    }

    /**
     * Get account id by client id
     *
     * @param string $clientId
     * @return int
     */
    public function getAccountIdByClientId(string $clientId): int
    {
        $integration = Integration::where('client_id', '=', $clientId)->first();

        return $integration->account_id;
    }
}
