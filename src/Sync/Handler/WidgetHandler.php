<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Sync\Repository\ContactRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\ApiContactService;
use Sync\Service\KommoApiClient;
use Sync\Service\UnisenderApiService;

class WidgetHandler implements RequestHandlerInterface
{
    /** @var AccessRepository  */
    private AccessRepository $accessRepository;

    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    /** @var ContactRepository  */
    private ContactRepository $contactRepository;

    /**
     * WidgetHandler констурктор
     *
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     * @param ContactRepository $contactRepository
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository,
        ContactRepository $contactRepository
    )
    {
        $this->accessRepository = $accessRepository;
        $this->integrationRepository = $integrationRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Save unisender apikey from widget
     * Case don't valid, stop executing
     * Case success performs opening synchronization for all existing accounts at kommo
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            if (!isset($body['account_id']) || !isset($body['unisender_key'])) {
                throw new Exception('you need provide account_id and unisender_key');
            }


            if (!$this->accessRepository->isAccessTokenValid($body['account_id'])) {
                throw new Exception('Access token is not valid');
            }

            $this->accessRepository->saveApiKey($body['account_id'], $body['unisender_key']);

            $kommoApiClient = new KommoApiClient($this->accessRepository, $this->integrationRepository);
            $contacts = $kommoApiClient->getContacts($body['account_id']);

            $normalizedContacts = (new ApiContactService())->getNormalizedContacts($contacts);

            $this->contactRepository->saveContacts($normalizedContacts, (int) $body['account_id']);

            $unisenderService = new UnisenderApiService($body['unisender_key']);
            $unisenderService
                ->importContactsByLimit($normalizedContacts, $body['account_id']);

            $kommoApiClient->subscribeWebhook($body['account_id']);
        } catch (Exception $e) {
            $message = $e->getMessage();
            return new JsonResponse([
                'status' => 'error',
                'data' => [
                    'message' => $message,
                    'webhook' => 'Webhook not installed',
                    'contacts' => "Contacts didn't send"
                ]
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'message' => 'ok',
                'webhook' => 'Webhook installed',
                'contacts' => 'Contacts sent'
            ]
        ]);
    }
}
