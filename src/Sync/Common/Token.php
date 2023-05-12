<?php

namespace Sync\Common;

use Exception;
use League\OAuth2\Client\Token\AccessToken;
use Throwable;

/**
 * Class ApiService.
 *
 * @package SyncTrait\Api
 */
class Token
{
    /** @var string Файл хранения токенов. */
    private const TOKENS_FILE = './tokens.json';

    /**
     * Сохранение токена авторизации.
     *
     * @param int $clientId
     * @param array $token Токен доступа Api.
     * @return void
     */
    public static function saveToken(int $clientId, array $token): void
    {
        $tokens = file_exists(self::TOKENS_FILE)
            ? json_decode(file_get_contents(self::TOKENS_FILE), true)
            : [];
        $tokens[$clientId] = $token;
        file_put_contents(self::TOKENS_FILE, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    /**
     * Удаление токена из файла.
     *
     * @param int $clientId
     * @return void
     */
    public static function deleteToken(int $clientId): void
    {
        $tokens = file_exists(self::TOKENS_FILE)
            ? json_decode(file_get_contents(self::TOKENS_FILE), true)
            : [];
        unset($tokens[$clientId]);
        file_put_contents(self::TOKENS_FILE, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    /**
     * Получение токена из файла.
     *
     * @param int $clientId
     * @return AccessToken
     */
    public static function readToken(int $clientId): AccessToken // TODO: PHPDocs
    {
        try {
            if (!file_exists(self::TOKENS_FILE)) {
                throw new Exception('Tokens file not found.');
            }

            $accesses = json_decode(file_get_contents(self::TOKENS_FILE), true);
            if (empty($accesses[$clientId])) {
                throw new Exception("Unknown account name \"$clientId\".");
            }

            return new AccessToken($accesses[$clientId]);
        } catch (Throwable $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Проверка на существование токена
     *
     * @param int $clientId
     * @return bool
     */
    public static function isTokenExists(int $clientId): bool
    {
        if (!file_exists(self::TOKENS_FILE)) {
            return false;
        }
        $accesses = json_decode(file_get_contents(self::TOKENS_FILE), true);

        if (empty($accesses[$clientId])) {
            return false;
        }
        return true;
    }
}
