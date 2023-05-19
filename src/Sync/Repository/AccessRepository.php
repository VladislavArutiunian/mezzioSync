<?php

namespace Sync\Repository;

use Sync\Model\Access;
use Sync\Model\Account;

/**
 * @property string $unisender_api_key
 */
class AccessRepository
{
    /**
     * Save Token to db table access
     *
     * @param int $kommoId
     * @param array $token
     * @return void
     */
    public function saveToken(int $kommoId, array $token): void
    {
        $accountId = $this->getAccountIdByKommoId($kommoId);
        Access::create([
            'account_id' => $accountId,
            'kommo_access_token' => json_encode($token),
        ]);
    }

    /**
     * Delete from db
     *
     * @param int $kommoId
     * @return void
     */
    public function deleteToken(int $kommoId): void
    {
        $accountId = $this->getAccountIdByKommoId($kommoId);
        $access = Account::find($accountId)->access()->first();
        $access->delete();
    }

    /**
     * Gets account_id by kommo_id
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
     * Gets token json structure
     *
     * @param string $kommoId
     * @return array|null
     */
    public function getToken(string $kommoId): ?array
    {
        $accountId = $this->getAccountIdByKommoId($kommoId);
        $token = Account::find($accountId)->access->kommo_access_token;
        return json_decode($token, true);
    }

    /**
     * Gets unisender api key
     *
     * @param string $kommoId
     * @return string|null
     */
    public function getApiKey(string $kommoId): ?string
    {
        $accountId = $this->getAccountIdByKommoId($kommoId);
        return Account::find($accountId)->access->unisender_api_key;
    }

    /**
     * Save to table accesses api key
     *
     * @param string $kommoId
     * @param string $apiKey
     * @return void
     */
    public function saveApiKey(string $kommoId, string $apiKey): void
    {
        $accountId = $this->getAccountIdByKommoId($kommoId);
        $access = Access::where('account_id', '=', $accountId)->first();
        $access->unisender_api_key = $apiKey;
        $access->save();
    }

    /**
     * Is access token exists and don't expire
     *
     * @param string $kommoId
     * @return bool
     */
    public function isAccessTokenValid(string $kommoId): bool
    {
        $accountId = $this->getAccountIdByKommoId($kommoId);
        $token = json_decode(Account::find($accountId)->access->kommo_access_token);
        $tokenExpires = $token->expires ?? null;
        if ($tokenExpires > time() && isset($token->access_token)) {
            return true;
        }
        return false;
    }
}
