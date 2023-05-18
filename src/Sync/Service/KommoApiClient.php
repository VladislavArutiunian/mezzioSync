<?php

namespace Sync\Service;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;

/**
 * Class KommoApiClient.
 *
 * @package SyncTrait\Api
 */
class KommoApiClient
{
    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    /** @var TokenService  */
    private TokenService $tokenService;
    private AmoCRMApiClient $apiClient;

    /**
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository
    ) {
        $this->integrationRepository = $integrationRepository;
        $this->tokenService = new TokenService($accessRepository);
    }

    /**
     * Получить имя аккаунта
     *
     * @param string|null $kommoId
     * @return string
     */
    public function getName(?string $kommoId = null): string
    {
        try {
            if (is_null($kommoId) && !isset($_SESSION['service_id'])) {
                throw new Exception('provide an account id');
            } elseif (is_null($kommoId)) {
                $kommoId = $_SESSION['service_id'];
            }

            $accessToken = $this->tokenService->readToken($kommoId);

            $accountId = $this->integrationRepository->getAccountIdByKommoId($kommoId);
            $integration = $this->integrationRepository->getIntegration($accountId);
            $this->apiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url
            );

            return $this
                ->apiClient
                ->getOAuthClient()
                ->setBaseDomain($accessToken->jsonSerialize()['base_domain'])
                ->getResourceOwner($accessToken)
                ->getName();
        } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException | AmoCRMException $e) {
            $this->tokenService->deleteToken($kommoId);
            header('Location: ' . '/auth?id=' . $kommoId);
            exit($e->getMessage());
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Получить список контактов
     *
     * @param array $queryParams
     * @return array
     */
    public function getContacts(array $queryParams): array
    {
        try {
            if (!isset($queryParams['id'])) {
                throw new Exception('provide an account id');
            }

            $accountId = $this->integrationRepository->getAccountIdByKommoId($queryParams['id']);
            $integration = $this->integrationRepository->getIntegration($accountId);
            $this->apiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url
            );

            $pageNumber = $queryParams['page'] ?? 1;
            $id = $queryParams['id'];

            if (!$this->tokenService->isTokenExists($id)) {
                header('Location: ' . "/auth?id=$id");
            }

            $accessToken = $this->tokenService->readToken($id);

            $filter = new ContactsFilter();
            $filter->setLimit(250);
            $flaq = true;
            $result = [];

            while ($flaq) {
                try {
                    $filter->setPage($pageNumber);
                    $bunch = $this
                        ->apiClient
                        ->setAccountBaseDomain($accessToken->jsonSerialize()['base_domain'])
                        ->setAccessToken($accessToken)
                        ->contacts()
                        ->get($filter)
                        ->toArray();

                    $pageNumber += 1;
                    $result = array_merge($result, $bunch);
                } catch (AmoCRMApiNoContentException $e) {
                    $flaq = false;
                } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException $e) {
                    $this->tokenService->deleteToken($id);
                    header('Location: ' . "/auth?id=$id");
                    exit($e->getMessage());
                }
            }
            return $result;
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }
}
