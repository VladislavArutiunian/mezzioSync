<?php

namespace Sync\Service;

use AmoCRM\Client\AmoCRMApiClient;
use Exception;
use Sync\Model\Account;
use Sync\Repository\AccessRepository;
use Sync\Repository\AccountRepository;

class AccountService
{
    /**
     * @var AccountRepository
     */
    private AccountRepository $accountRepository;

    /**
     * @var AccessRepository
     */
    private AccessRepository $accessRepository;

    /**
     * @param AccountRepository $accountRepository
     * @param AccessRepository $accessRepository
     */
    public function __construct(
        AccountRepository $accountRepository,
        AccessRepository $accessRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->accessRepository = $accessRepository;
    }

    /**
     * Возвращает ответ с аккаунтами и их связанными сущностями
     *
     * @return array
     */
    public function buildResponse(): array
    {
        $accounts = $this->accountRepository->getAllWithEntities();
        $accountInfo = [];
        $accountsWithAccesses = $accounts->has('access')->get();
        $accountNamesWithAccesses = [];
        foreach ($accountsWithAccesses as $accountWithAccess) {
            $accountNamesWithAccesses[] = $this->getAccountNameFromKommoApi($accountWithAccess);
        }

        foreach ($accounts->get() as $account) {
            try {
                $kommoAccountName = $this->getAccountNameFromKommoApi($account);
                $accountInfo[$kommoAccountName] = [
                    'kommo_id' => $account->kommo_id,
                    'integration_id' => $account->integration->client_id,
                    'contacts_count' => $account->contacts->count(),
                    'unisender_key' => $account->access->unisender_api_key,
                ];
            } catch (Exception $e) {
                $errorStatus = 'error';
                $errorMessage = $e->getMessage();
                return [
                    'status' => $errorStatus,
                    'data' => [
                        'message' => $errorMessage
                    ]
                ];
            }
        }
        return [
            'status' => 'success',
            'data' => [
                'accounts' => [
                    'all' => $accountInfo,
                    'with_accesses' => $accountNamesWithAccesses
                ]
            ],
        ];
    }

    /**
     * Get kommo account from api
     *
     * @param Account $accountModel
     * @return string
     */
    public function getAccountNameFromKommoApi(Account $accountModel): string
    {
        $kommoApi = new KommoApiService(
            new AmoCRMApiClient(
                $accountModel->integration->client_id,
                $accountModel->integration->secret_key,
                $accountModel->integration->url,
            ),
            new TokenService($this->accessRepository)
        );
        return $kommoApi->getName($accountModel->kommo_id);
    }
}
