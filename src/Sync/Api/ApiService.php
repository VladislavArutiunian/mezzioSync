<?php

namespace Sync\Api;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;
use League\OAuth2\Client\Token\AccessToken;
use Throwable;

/**
 * Class ApiService.
 *
 * @package Sync\Api
 */
class ApiService
{
    /** @var string Базовый домен авторизации. */
    private const TARGET_DOMAIN = 'kommo.com';

    /** @var string Файл хранения токенов. */
    private const TOKENS_FILE = './tokens.json';

    /** @var AmoCRMApiClient AmoCRM клиент. */
    private AmoCRMApiClient $apiClient;

    /**
     * Существует ли токен
     *
     * @var bool
     */
    private bool $isTokenExists;

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

        $this->isTokenExists = $this->isTokenExists($_SESSION['service_id']);

        if (isset($queryParams['referer'])) {
            $this
                ->apiClient
                ->setAccountBaseDomain($queryParams['referer'])
                ->getOAuthClient()
                ->setBaseDomain($queryParams['referer']);
        }

        try {
            if ($this->isTokenExists) {
                $this->accessToken = $this->readToken($_SESSION['service_id']);

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
                $this->saveToken($_SESSION['service_id'], [
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
     * Сохранение токена авторизации.
     *
     * @param int $serviceId Системный идентификатор аккаунта.
     * @param array $token Токен доступа Api.
     * @return void
     */
    private function saveToken(int $serviceId, array $token): void
    {
        $tokens = file_exists(self::TOKENS_FILE)
            ? json_decode(file_get_contents(self::TOKENS_FILE), true)
            : [];
        $tokens[$serviceId] = $token;
        file_put_contents(self::TOKENS_FILE, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    public function deleteToken(int $serviceId): void
    {
        $tokens = file_exists(self::TOKENS_FILE)
            ? json_decode(file_get_contents(self::TOKENS_FILE), true)
            : [];
        unset($tokens[$serviceId]);
        file_put_contents(self::TOKENS_FILE, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    /**
     * Получение токена из файла.
     *
     * @param int $serviceId Системный идентификатор аккаунта.
     * @return null | AccessToken
     */
    public function readToken(int $serviceId): AccessToken // TODO: PHPDocs
    {
        try {
            if (!file_exists(self::TOKENS_FILE)) {
                throw new Exception('Tokens file not found.');
            }

            $accesses = json_decode(file_get_contents(self::TOKENS_FILE), true);
            if (empty($accesses[$serviceId])) {
                throw new Exception("Unknown account name \"$serviceId\".");
            }

            return new AccessToken($accesses[$serviceId]);
        } catch (Throwable $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Проверка на существование токена
     *
     * @param int $service_id
     * @return bool
     */
    public function isTokenExists(int $service_id): bool
    {
        if (!file_exists(self::TOKENS_FILE)) {
            return false;
        }
        $accesses = json_decode(file_get_contents(self::TOKENS_FILE), true);

        if (empty($accesses[$service_id])) {
            return false;
        }
        return true;
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
            if (!isset($queryParams['id'])) {
                throw new Exception('provide an account id');
            }
            $this->accessToken = $this->readToken($_SESSION['service_id']);
            return $this
                ->apiClient
                ->getOAuthClient()
                ->setBaseDomain($this->accessToken->jsonSerialize()['base_domain'])
                ->getResourceOwner($this->accessToken)
                ->getName();
        } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException | AmoCRMException $e) {
            $this->deleteToken($_SESSION['service_id']);
            header('Location: ' . 'https://mezziostudy.loca.lt/auth?id=31197031');
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
            $id = $queryParams['id'];

            if (!$this->isTokenExists($id)) {
                header('Location: ' . "/auth?id=$id");
            }
            $this->accessToken = $this->readToken($id);

            return $this
                ->apiClient
                ->setAccountBaseDomain($this->accessToken->jsonSerialize()['base_domain'])
                ->setAccessToken($this->accessToken)
                ->contacts()
                ->get()
                ->toArray();
        } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException $e) {
            $this->deleteToken($id);
            header('Location: ' . "/auth?id=$id");
            exit($e->getMessage());
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }
}
