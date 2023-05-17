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
use Sync\Service\ContactService;
use Sync\Service\KommoApiService;
use Sync\Service\TokenService;

class ContactsHandler implements RequestHandlerInterface
{
    /**
     * @var IntegrationRepository
     */
    private IntegrationRepository $integrationRepository;

    /**
     * @var AccessRepository
     */
    private AccessRepository $accessRepository;

    /**
     * ContactsHandler констурктор
     *
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
     * Get all contacts from Kommo
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $accountId = $this->integrationRepository->getAccountIdByKommoId($queryParams['id']);
        $integration = $this->integrationRepository->getIntegration($accountId);
        $apiClient = new AmoCRMApiClient(
            $integration->getIntegrationId(),
            $integration->getSecretKey(),
            $integration->getReturnUrl()
        );
        $tokenService = new TokenService($this->accessRepository);

        $kommoApiService = new KommoApiService($apiClient, $tokenService);

        $contacts = $kommoApiService->getContacts($queryParams);
        $normalizedContacts = (new ContactService())->getNormalizedContacts($contacts);

        return new JsonResponse($normalizedContacts);
    }
}
