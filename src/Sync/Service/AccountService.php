<?php

namespace Sync\Service;

use Exception;
use Sync\Model\Account;
use Sync\Repository\AccessRepository;
use Sync\Repository\AccountRepository;
use Sync\Repository\IntegrationRepository;


class AccountService // TODO: PHPDocs
{
    /**
     * @var AccountRepository
     */
    private AccountRepository $accountRepository;

    /**
     * @var AccessRepository
     */
    private AccessRepository $accessRepository;

    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    /**
     * @param AccountRepository $accountRepository
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(
        AccountRepository $accountRepository,
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->accessRepository = $accessRepository;
        $this->integrationRepository = $integrationRepository;
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
        $kommoApi = new KommoApiClient(
            $this->accessRepository,
            $this->integrationRepository
        );
        return $kommoApi->getName($accountModel->kommo_id);
    }
}
