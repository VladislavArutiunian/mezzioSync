<?php

namespace Sync\Service\Authorization;

use AmoCRM\Client\AmoCRMApiClient;
use Exception;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\TokenService;
use Throwable;

class StandardAuthorization extends AbstractAuthorization
{
    /**
     * StandardAuthorization конструктор.
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
     * @return string Kommo id.
     */
    public function auth(array $queryParams): string
    {
        session_start();

        /** Занесение системного идентификатора в сессию для реализации OAuth2.0. */
        if (!empty($queryParams['id'])) {
            $_SESSION['service_id'] = $queryParams['id'];
        }
        try {
            if (!isset($queryParams['id']) && !isset($_SESSION['service_id'])) {
                throw new Exception('provide acc id');
            }

            $accountId = $this->integrationRepository->getAccountIdByKommoId($_SESSION['service_id']);
            $integration = $this->integrationRepository->getIntegration($accountId);
            $this->apiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url
            );

            $isTokenExists = $this->tokenService->isTokenExists($_SESSION['service_id']);

            if (isset($queryParams['referer'])) {
                $this
                    ->apiClient
                    ->setAccountBaseDomain($queryParams['referer'])
                    ->getOAuthClient()
                    ->setBaseDomain($queryParams['referer']);
            }


            if ($isTokenExists) {
                $this->accessToken = $this->tokenService->readToken($_SESSION['service_id']);

                return $_SESSION['service_id'];
            } elseif (!isset($queryParams['code'])) {
                $state = bin2hex(random_bytes(16));
                $_SESSION['oauth2state'] = $state;
                if (isset($queryParams['button'])) {
                    echo $this
                        ->apiClient
                        ->getOAuthClient()
                        ->setBaseDomain(self::TARGET_DOMAIN)
                        ->getOAuthButton([
                            'title' => 'Установить интеграцию',
                            'compact' => true,
                            'class_name' => 'className',
                            'color' => 'default',
                            'error_callback' => 'handleOauthError',
                            'state' => $state,
                        ]);
                } else {
                    $authorizationUrl = $this
                        ->apiClient
                        ->getOAuthClient()
                        ->setBaseDomain(self::TARGET_DOMAIN)
                        ->getAuthorizeUrl([
                            'state' => $state,
                            'mode' => 'post_message',
                        ]);
                    header('Location: ' . $authorizationUrl);
                }
                die;
            } elseif (
                empty($queryParams['state']) ||
                empty($_SESSION['oauth2state']) ||
                ($queryParams['state'] !== $_SESSION['oauth2state'])
            ) {
                unset($_SESSION['oauth2state']);
                exit('Invalid state');
            }
        } catch (Throwable $e) {
            die($e->getMessage());
        }

        try {
            $this->accessToken = $this
                ->apiClient
                ->getOAuthClient()
                ->setBaseDomain($queryParams['referer'])
                ->getAccessTokenByCode($queryParams['code']);

            if (!$this->accessToken->hasExpired()) {
                $this->tokenService->saveToken($_SESSION['service_id'], [
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
        return $_SESSION['service_id'];
    }
}
