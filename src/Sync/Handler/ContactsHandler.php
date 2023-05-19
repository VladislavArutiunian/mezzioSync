<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\ApiContactService;
use Sync\Service\KommoApiClient;

class ContactsHandler implements RequestHandlerInterface
{
    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    /** @var AccessRepository  */
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
        try {
            $kommoId = $request->getQueryParams()['id'];
            if (!isset($kommoId)) {
                throw new Exception('provide your kommo account id');
            }

            $kommoApiClient = new KommoApiClient(
                $this->accessRepository,
                $this->integrationRepository
            );

            $contacts = $kommoApiClient->getContacts($kommoId);

            $normalizedContacts = (new ApiContactService())->getNormalizedContacts($contacts);

            return new JsonResponse($normalizedContacts);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
