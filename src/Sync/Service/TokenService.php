<?php

namespace Sync\Service;

use League\OAuth2\Client\Token\AccessToken;
use Sync\Repository\AccessRepository;

/**
 * Class KommoApiClient.
 *
 * @package SyncTrait\Api
 */
class TokenService
{
    /** @var AccessRepository */
    private AccessRepository $accessRepository;

    /**
     * @param AccessRepository $accessRepository
     */
    public function __construct(AccessRepository $accessRepository)
    {
        $this->accessRepository = $accessRepository;
    }

    /**
     * Сохранение токена в БД.
     *
     * @param int $clientId
     * @param array $token Токен доступа Api.
     * @return void
     */
    public function saveToken(int $clientId, array $token): void
    {
        $this->accessRepository->saveToken($clientId, $token);
    }

    /**
     * Удаление токена из файла.
     *
     * @param int $clientId
     * @return void
     */
    public function deleteToken(int $clientId): void
    {
        $this->accessRepository->deleteToken($clientId);
    }

    /**
    Save * Получение токена из файла.
     *
     * @param int $clientId
     * @return AccessToken
     */
    public function readToken(int $clientId): AccessToken
    {
        $token = $this->accessRepository->getToken($clientId);

        return new AccessToken($token);
    }

    /**
     * Проверка на существование токена
     *
     * @param int $clientId
     * @return bool
     */
    public function isTokenExists(int $clientId): bool
    {
        return $this->accessRepository->getToken($clientId) !== null;
    }
}
