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
use Sync\Service\ContactService;
use Sync\Service\TokenService;
use Sync\Service\UnisenderApiService;
use Sync\Repository\ContactRepository;

/**
 * Class receives all contacts from kommo
 * processes it and performs api request to send it to unisender
 */
class SendHandler implements RequestHandlerInterface
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
     * @var ContactRepository
     */
    private ContactRepository $contactRepository;

    public function __construct( // TODO
        IntegrationRepository $integrationRepository,
        AccessRepository $accessRepository,
        ContactRepository $contactRepository
    ) {
        $this->integrationRepository = $integrationRepository;
        $this->accessRepository = $accessRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Get contacts from kommo,
     * Filter and prepare for unisender,
     * Import contacts to unisender
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $kommoId = $queryParams['id'];

            if (!isset($kommoId)) {
                throw new Exception('Provide an id in GET parameters');
            }
            $tokenService = new TokenService($this->accessRepository);
            $accountId = $this->integrationRepository->getAccountIdByKommoId($kommoId);
            $integration = $this->integrationRepository->getIntegration($accountId);
            $apiClient = new AmoCRMApiClient(
                $integration->getIntegrationId(),
                $integration->getSecretKey(),
                $integration->getReturnUrl()
            );

            $kommoApiService = new KommoApiService($apiClient, $tokenService);
            $contacts = $kommoApiService->getContacts($queryParams);
            $normalizedContacts = (new ContactService())->getNormalizedContacts($contacts);

            $this->contactRepository->saveContacts($normalizedContacts, $accountId);

            $apiKey = $this->accessRepository->getApiKey($kommoId);

            $unisenderService = new UnisenderApiService($apiKey);
            $unisenderResponse = $unisenderService->importContactsByLimit($normalizedContacts, $kommoId);

            return new JsonResponse($unisenderResponse);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
