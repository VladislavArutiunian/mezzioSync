<?php

namespace Sync\Repository;

use Sync\Model\Account;

class AccountRepository
{
    public function getAllAccountsWithEntities()
    {
//        return Account::with('access', 'integration', 'contacts')->get();
        return Account::with('access', 'integration', 'contacts');
    }
}
