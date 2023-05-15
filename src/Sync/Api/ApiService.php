<?php

namespace Sync\Api;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;
use League\OAuth2\Client\Token\AccessToken;
use Sync\Common\Token;
use Throwable;

/**
 * Class ApiService.
 *
 * @package SyncTrait\Api
 */
class ApiService
{
    /** @var string Базовый домен авторизации. */
    private const TARGET_DOMAIN = 'kommo.com';

    /** @var AmoCRMApiClient AmoCRM клиент. */
    private AmoCRMApiClient $apiClient;

    /**
     * @var AccessToken
     */
    private AccessToken $accessToken;

    /**
     * ApiService конструктор.
     *
     * @param string $integrationId
     * @param string $integrationSecretKey
     * @param string $integrationRedirectUri
     */
    public function __construct(string $integrationId, string $integrationSecretKey, string $integrationRedirectUri)
    {
        $this->apiClient = new AmoCRMApiClient(
            $integrationId,
            $integrationSecretKey,
            $integrationRedirectUri
        );
    }

    /**
     * Получение токена доступа для аккаунта.
     *
     * @param array $queryParams Входные GET параметры. Имя параметра - id
     * @return ApiService Имя авторизованного аккаунта.
     */
    public function auth(array $queryParams): ApiService
    {
        session_start();

        /** Занесение системного идентификатора в сессию для реализации OAuth2.0. */
        if (!empty($queryParams['id'])) {
            $_SESSION['service_id'] = $queryParams['id'];
        }

        $isTokenExists = Token::isTokenExists($_SESSION['service_id']);

        if (isset($queryParams['referer'])) {
            $this
                ->apiClient
                ->setAccountBaseDomain($queryParams['referer'])
                ->getOAuthClient()
                ->setBaseDomain($queryParams['referer']);
        }

        try {
            if ($isTokenExists) {
                $this->accessToken = Token::readToken($_SESSION['service_id']);

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
                Token::saveToken($_SESSION['service_id'], [
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

    /**
     * Получить имя аккаунта
     *
     * @param array $queryParams
     * @return string
     */
    public function getName(array $queryParams): string
    {
        try {
            if (!isset($queryParams['id']) && !isset($_SESSION['service_id'])) {
                throw new Exception('provide an account id');
            }
            $this->accessToken = Token::readToken($_SESSION['service_id']);
            return $this
                ->apiClient
                ->getOAuthClient()
                ->setBaseDomain($this->accessToken->jsonSerialize()['base_domain'])
                ->getResourceOwner($this->accessToken)
                ->getName();
        } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException | AmoCRMException $e) {
            Token::deleteToken($_SESSION['service_id']);
            header('Location: ' . '/auth?id=' . $_SESSION['service_id']);
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

            if (!Token::isTokenExists($id)) {
                header('Location: ' . "/auth?id=$id");
            }
            $this->accessToken = Token::readToken($id);

            $filter = new ContactsFilter();
            $filter->setLimit(250);
            $flaq = true;
            $result = [];
            while ($flaq) {
                try {
                    $filter->setPage($pageNumber);
                    $result[] = $this
                        ->apiClient
                        ->setAccountBaseDomain($this->accessToken->jsonSerialize()['base_domain'])
                        ->setAccessToken($this->accessToken)
                        ->contacts()
                        ->get($filter)
                        ->toArray();
                    $pageNumber += 1;
                } catch (AmoCRMApiNoContentException $e) {
                    $flaq = false;
                } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException $e) {
                    Token::deleteToken($id);
                    header('Location: ' . "/auth?id=$id");
                    exit($e->getMessage());
                }
            }
            return $result[0];
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }
}
