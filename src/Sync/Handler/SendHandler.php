<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Api\ApiService;
use Unisender\ApiWrapper\UnisenderApi;

/**
 * Class receives all contacts from kommo
 * processes it and performs api request to send it to unisender
 */
class SendHandler implements RequestHandlerInterface
{
    /**
     * api key unisender
     * @var string
     */
    private string $apiKey;

    /* @var string */
    private string $returnUrl;

    /* @var string */
    private string $integrationId;

    /* @var string */
    private string $secretKey;

    /**
     * Contact list id from unisender
     * @var null|string
     */
    private ?string $listId;

    /**
     * Contacts from kommo
     * @var array
     */
    private array $contacts;

    public function __construct(array $integration, array $unisender)
    {
        $this->secretKey = $integration['secret_key'];
        $this->integrationId = $integration['integration_id'];
        $this->returnUrl = $integration['return_url'];
        $this->listId = $unisender['list_id'] ?? null;
        $this->apiKey = $unisender['api_key'];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            if (!isset($request->getQueryParams()['id'])) {
                throw new Exception('Provide an id in GET parameters');
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        $this->contacts =
            (new ApiService(
                $this->integrationId,
                $this->secretKey,
                $this->returnUrl
            ))->getContacts($request->getQueryParams());

        $params = $this
            ->filterContacts()
            ->formatForUnisender()
            ->prepareForUnisender();

        $unisenderApi = new UnisenderApi($this->apiKey);

        return new JsonResponse($unisenderApi->importContacts($params));
    }

    /**
     * Filter contact with non-empty name and at least one email
     *
     * @return $this
     */
    public function filterContacts(): SendHandler
    {
        $this->contacts = array_filter(
            $this->contacts,
            fn ($item) => $item['name'] && $item['custom_fields_values'][0]['values'][0]['value']
        );
        return $this;
    }

    /**
     * Filter data which will be necessary for unisender
     *
     * @return $this
     */
    public function formatForUnisender(): SendHandler
    {
        $result = [];
        foreach ($this->contacts as $contact) {
            $emails = self::getContactEmails($contact);

            $result[] = [
                'name' => $contact['name'],
                'emails' => $emails
            ];
        }
        $this->contacts = $result;
        return $this;
    }

    public function getContactEmails(array $contact): array
    {
        $emails = [];
        foreach ($contact['custom_fields_values'][0]['values'] as $emailItem) {
            $emails[] = $emailItem['value'];
        }
        return $emails;
    }

    /**
     * Prepares data for request to unisender api
     *
     * @return array
     */
    public function prepareForUnisender(): array
    {
        $fieldNames = [
            "field_names[0]" => 'email',
            "field_names[1]" => 'Name',
            "field_names[2]" => 'email_list_ids',
        ];
        $fieldData = [];
        $contactRow = 0;
        foreach ($this->contacts as $contact) {
            foreach ($contact['emails'] as $email) {
                $fieldData["data[$contactRow][0]"] = $email;
                $fieldData["data[$contactRow][1]"] = $contact['name'];
                $fieldData["data[$contactRow][2]"] = $this->listId;
                $contactRow += 1;
            }
        }
        return array_merge($fieldNames, $fieldData);
    }
}
