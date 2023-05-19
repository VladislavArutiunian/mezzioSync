<?php

namespace Sync\Service\Authorization;

use AmoCRM\Client\AmoCRMApiClient;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\TokenService;
use Throwable;

class SimpleAuthorization extends AbstractAuthorization // TODO: PHPDocs
{
    /**
     * SimpleAuthorization конструктор.
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
     * @return string Kommo Id.
     */
    public function auth(array $queryParams): string
    {
        try {
            $accountId = $this->integrationRepository->getAccountIdByClientId($queryParams['client_id']);
            $kommoId = $this->integrationRepository->getKommoIdByAccountId($accountId);

            $integration = $this->integrationRepository->getIntegration($kommoId);
            $this->apiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url,
            );

            $isTokenExists = $this->tokenService->isTokenExists($kommoId);

            if (isset($queryParams['referer'])) {
                $this
                    ->apiClient
                    ->setAccountBaseDomain($queryParams['referer'])
                    ->getOAuthClient()
                    ->setBaseDomain($queryParams['referer']);
            }


            if ($isTokenExists) {
                $this->accessToken = $this->tokenService->readToken($kommoId);
                return $kommoId;
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
        return $kommoId;
    }
}
