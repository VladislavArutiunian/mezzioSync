<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Api\ApiService;

class ContactsHandler implements RequestHandlerInterface
{
    private string $secretKey; // TODO: PHPDocs
    private string $integrationId; // TODO: PHPDocs
    private string $returnUrl; // TODO: PHPDocs

    /**
     * ContactsHandler констурктор
     *
     * @param array $integration
     */
    public function __construct(array $integration)
    {
        $this->secretKey = $integration['secret_key'];
        $this->integrationId = $integration['integration_id'];
        $this->returnUrl = $integration['return_url'];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $apiClient = new ApiService($this->integrationId, $this->secretKey, $this->returnUrl);

        $contacts = $apiClient->getContacts($request->getQueryParams());

        $contactsFiltered = array_map(function ($contact) {
            $name = $contact['name'];
            $emailsArr = $contact['custom_fields_values'][1]['values'] ?? [];
            $emails = [];
            foreach ($emailsArr as $email) {
                $emails[] = $email['value'];
            }
            return ["name" => $name, "emails" => $emails];
        }, $contacts);

        return new JsonResponse($contactsFiltered);
    }
}
