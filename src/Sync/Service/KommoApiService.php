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
use League\OAuth2\Client\Token\AccessToken;
use Throwable;

/**
 * Class KommoApiService.
 *
 * @package SyncTrait\Api
 */
class KommoApiService
{
    /** @var string Базовый домен авторизации. */
    private const TARGET_DOMAIN = 'kommo.com';

    /** @var AmoCRMApiClient AmoCRM клиент. */
    private AmoCRMApiClient $apiClient;

    /**
     * @var AccessToken
     */
    private AccessToken $accessToken;

    private TokenService $tokenService;

    /**
     * KommoApiService конструктор.
     *
     * @param AmoCRMApiClient $apiClient
     * @param TokenService $tokenService
     */
    public function __construct(AmoCRMApiClient $apiClient, TokenService $tokenService)
    {
        $this->apiClient = $apiClient;
        $this->tokenService = $tokenService;
    }

    /**
     * Получение токена доступа для аккаунта.
     *
     * @param array $queryParams Входные GET параметры. Имя параметра - id
     * @return KommoApiService Имя авторизованного аккаунта.
     */
    public function auth(array $queryParams): KommoApiService
    {
        session_start();

        /** Занесение системного идентификатора в сессию для реализации OAuth2.0. */
        if (!empty($queryParams['id'])) {
            $_SESSION['service_id'] = $queryParams['id'];
        }

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

        //session_abort();
        return $this;
    }

    /**
     * Получить имя аккаунта
     *
     * @param array $queryParams
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
            $this->accessToken = $this->tokenService->readToken($kommoId);
            return $this
                ->apiClient
                ->getOAuthClient()
                ->setBaseDomain($this->accessToken->jsonSerialize()['base_domain'])
                ->getResourceOwner($this->accessToken)
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
            $pageNumber = $queryParams['page'] ?? 1;
            $id = $queryParams['id'];

            if (!$this->tokenService->isTokenExists($id)) {
                header('Location: ' . "/auth?id=$id");
            }
            $this->accessToken = $this->tokenService->readToken($id);

            $filter = new ContactsFilter();
            $filter->setLimit(250);
            $flaq = true;
            $result = [];
            while ($flaq) {
                try {
                    $filter->setPage($pageNumber);
                    $bunch = $this
                        ->apiClient
                        ->setAccountBaseDomain($this->accessToken->jsonSerialize()['base_domain'])
                        ->setAccessToken($this->accessToken)
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
