<?php

declare(strict_types=1);

namespace Sync\Handler;

use AmoCRM\Client\AmoCRMApiClient;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\KommoApiService;
use Sync\Service\TokenService;

class AuthHandler implements RequestHandlerInterface
{
    /* @var IntegrationRepository */
    private IntegrationRepository $integrationRepository;

    /* @var AccessRepository */
    private AccessRepository $accessRepository;

    /**
     * @param IntegrationRepository $integrationRepository
     * @param AccessRepository $accessRepository
     */
    public function __construct(
        IntegrationRepository $integrationRepository,
        AccessRepository $accessRepository
    ) {
        $this->integrationRepository = $integrationRepository;
        $this->accessRepository = $accessRepository;
    }

    /**
     * performs authorization
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $kommoId = $queryParams['id'];

        $accountId = isset($queryParams['client_id'])
            ? $this->integrationRepository->getAccountIdByClientId($queryParams['client_id'])
            : $this->integrationRepository->getAccountIdByKommoId($kommoId);

        $integration = $this->integrationRepository->getIntegration($accountId);
        $apiClient = new AmoCRMApiClient(
            $integration->client_id,
            $integration->secret_key,
            $integration->url
        );

        $tokenService = new TokenService($this->accessRepository);

        $kommoApiService = new KommoApiService($apiClient, $tokenService);
        $kommoApiService->auth($queryParams);

        $accountName = $kommoApiService->getName($kommoId);

        return new JsonResponse(["name" => $accountName]);
    }
}
