<?php

namespace Sync\Repository;

use Sync\Model\Account;
use Sync\Model\Integration;

class IntegrationRepository
{
    /**
     * Get integration by account_id
     *
     * @param int $kommoId
     * @return Integration
     */
    public function getIntegration(int $kommoId): ?Integration
    {
        $account = Account::where('kommo_id', $kommoId)->first();
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
        return $account->id;
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

    /**
     * Gets kommo account id by table acc id
     *
     * @param int $accountId
     * @return void
     */
    public function getKommoIdByAccountId(int $accountId): int
    {
        $integration = Integration::find($accountId)->first();

        return $integration->account->kommo_id;
    }

    /**
     * @param string $accountId
     * @return string
     */
    public function getUrl(string $accountId): string
    {
        return Account::where('kommo_id', $accountId)->first()->integration->url;
    }
}
