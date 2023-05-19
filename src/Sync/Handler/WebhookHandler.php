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
use Sync\Service\KommoApiClient;
use Sync\Service\UnisenderApiService;
use Sync\Service\WebhookContactService;

class WebhookHandler implements RequestHandlerInterface
{
    /** @var AccessRepository  */
    private AccessRepository $accessRepository;

    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    /** @var ContactRepository  */
    private ContactRepository $contactRepository;


    /**
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     * @param ContactRepository $contactRepository
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository,
        ContactRepository $contactRepository
    ) {
        $this->accessRepository = $accessRepository;
        $this->integrationRepository = $integrationRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Webhook, handles POST requests from kommo
     * (Update, remove, add) contacts to db - unisender
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();

            if (!isset($body['contacts'])) {
                throw new Exception('contacts not provided');
            }
            $status = key($body['contacts']);
            $contactId = $body['contacts'][$status][0]['id'];
            $kommoId = $body['account']['id'];

            $kommoApiClient = new KommoApiClient($this->accessRepository, $this->integrationRepository);
            $apiKey = $this->accessRepository->getApiKey($kommoId);
            $unisenderService = new UnisenderApiService($apiKey);

            switch ($status) {
                case 'add':
                    $contact = (new WebhookContactService())
                        ->validateAndNormalize($kommoApiClient->getContact($kommoId, $contactId));

                    $this->contactRepository->saveContacts($contact, (int) $kommoId);

                    $unisenderService
                        ->importContactsByLimit($contact, $kommoId);
                    break;

                case 'update':
                    $contact = (new WebhookContactService())
                        ->validateAndNormalize($kommoApiClient->getContact($kommoId, $contactId));

                    $contactsEmails = $this->contactRepository->getContactEmails((int) $contactId);

                    $unisenderService
                        ->importContactsByLimit($contactsEmails, $kommoId, true);

                    $unisenderService
                        ->importContactsByLimit($contact, $kommoId);
                    $this->contactRepository->saveContacts($contact, (int) $kommoId);
                    break;

                case 'delete':
                    $contactsEmails = $this->contactRepository->getContactEmails((int) $contactId);
                    $unisenderService
                        ->importContactsByLimit($contactsEmails, $kommoId, true);
                    $this->contactRepository->deleteContact((int) $contactId);
                    break;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'message' => $message ?? 'contacts synchronized'
            ]
        ]);
    }
}
