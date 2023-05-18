<?php

namespace Sync\Repository;

use Illuminate\Database\Eloquent\Builder;
use Sync\Model\Account;

class AccountRepository
{
    /**
     * get all accounts with entities
     * @return Builder
     */
    public function getAllWithEntities(): Builder
    {
        return Account::with('access', 'integration', 'contacts');
    }

    /**
     * Creates account and integration related to him
     *
     * @param array $body
     * @return void
     */
    public function createAccountWithIntegration(array $body): void
    {
        $account = Account::updateOrCreate([
            'kommo_id' => $body['kommo_id']
        ]);
        $account->integration()->updateOrCreate(
            [
            'client_id' => $body['client_id'],
            ],
            [
            'secret_key' => $body['secret_key'],
            'url' => $body['url'],
            ]
        );
    }
}
