<?php

namespace Sync\Service\Authorization;

use AmoCRM\Client\AmoCRMApiClient;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\TokenService;
use Throwable;

class SimpleAuthorization extends AbstractAuthorization
{
    /**
     * KommoApiClient конструктор.
     *
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository
    ) {
        parent::__construct($accessRepository, $integrationRepository);

        $this->tokenService = new TokenService($accessRepository);
    }

    /**
     * Получение токена доступа для аккаунта.
     *
     * @param array $queryParams Входные GET параметры. Имя параметра - id
     * @return SimpleAuthorization Имя авторизованного аккаунта.
     */
    public function auth(array $queryParams): SimpleAuthorization
    {
        session_start();
        $accountId = $this->integrationRepository->getAccountIdByClientId($queryParams['client_id']);
        $kommoId = $this->integrationRepository->getKommoIdByAccountId($accountId);
        if (!empty($kommoId)) {
            $_SESSION['service_id'] = $kommoId;
        }
        $integration = $this->integrationRepository->getIntegration($accountId);
        $this->apiClient = new AmoCRMApiClient(
            $integration->client_id,
            $integration->secret_key,
            $integration->url,
        );

//        dd();
        $isTokenExists = $this->tokenService->isTokenExists($_SESSION['service_id']);

        if (isset($queryParams['referer'])) {
            $this
                ->apiClient
                ->setAccountBaseDomain($queryParams['referer'])
                ->getOAuthClient()
                ->setBaseDomain($queryParams['referer']);
        }

        try {
            if ($isTokenExists) {
                $this->accessToken = $this->tokenService->readToken($_SESSION['service_id']);
                return $this;
            }

            $this->accessToken = $this
                ->apiClient
                ->getOAuthClient()
                ->setBaseDomain($queryParams['referer'])
                ->getAccessTokenByCode($queryParams['code']);

            if (!$this->accessToken->hasExpired()) {
                $this->tokenService->saveToken($kommoId, [
                    'base_domain' => $this->apiClient->getAccountBaseDomain(),
                    'access_token' => $this->accessToken->getToken(),
                    'refresh_token' => $this->accessToken->getRefreshToken(),
                    'expires' => $this->accessToken->getExpires(),
                ]);
            }
        } catch (Throwable $e) {
            die($e->getMessage());
        }
        session_abort();
        return $this;
    }
}
