<?php

namespace Sync\Service\Authorization;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\TokenService;

abstract class AbstractAuthorization // TODO: PHPDocs
{
    /** @var string Базовый домен авторизации. */
    protected const TARGET_DOMAIN = 'kommo.com';

    /** @var AmoCRMApiClient AmoCRM клиент. */
    public AmoCRMApiClient $apiClient;

    /** @var AccessToken  */
    public AccessToken $accessToken;

    /** @var TokenService  */
    public TokenService $tokenService;

    /** @var AccessRepository  */
    protected AccessRepository $accessRepository;
    protected IntegrationRepository $integrationRepository; // TODO: PHPDocs

    /**
     * AbstractAuthorization конструктор.
     *
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository
    ) {
        $this->accessRepository = $accessRepository;
        $this->integrationRepository = $integrationRepository;
    }

    /**
     * @param array $queryParams
     * @return mixed
     */
    abstract public function auth(array $queryParams);
}
