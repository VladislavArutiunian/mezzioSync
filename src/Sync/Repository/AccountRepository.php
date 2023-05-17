<?php

namespace Sync\Repository;

use Sync\Model\Account;

class AccountRepository
{
    /**
     * @param Account $account
     * @return void
     */
    public function save(Account $account): void
    {
        $account->save();
    }
}
