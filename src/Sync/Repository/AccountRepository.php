<?php

namespace Sync\Repository;

use Sync\Model\Account;

class AccountRepository
{
    public function getAllAccountsWithEntities()
    {
        return Account::with('access', 'integration', 'contacts');
    }

    /**
     * Creates account and integration related to him
     *
     * @return void
     */
    public function createAccountWithIntegration(array $body): void
    {
        $account = Account::firstOrCreate([
            'kommo_id' => $body['kommo_id']
        ]);
        $account->integration()->firstOrCreate([
            'client_id' => $body['client_id'],
            'secret_key' => $body['secret_key'],
            'url' => $body['url'],
        ]);
    }
}
