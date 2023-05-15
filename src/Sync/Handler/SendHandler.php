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
     * Contacts from kommo
     * @var array
     */
    private array $contacts;

    public function __construct(array $integration, array $unisender)
    {
        $this->secretKey = $integration['secret_key'];
        $this->integrationId = $integration['integration_id'];
        $this->returnUrl = $integration['return_url'];
        $this->apiKey = $unisender['api_key'];
    }

    /**
     * Get contacts from commo,
     * Filter and prepare for unisender,
     * Import contacts to unisender
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $accountId = $request->getQueryParams()['id'];
            if (!isset($accountId)) {
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
            ->filterFields()
            ->filterContacts()
            ->formatForUnisender()
            ->prepareForUnisender($accountId);

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
            fn ($contact) => $contact['name'] && $this->hasAtLeastOneWorkingEmail($contact)
        );
        return $this;
    }

    /**
     * Filter only email fields
     *
     * @return $this
     */
    public function filterFields(): SendHandler
    {
        foreach ($this->contacts as &$contact) {
            $fields = $contact['custom_fields_values'] ?? [];
            $filteredFields = array_filter($fields, fn ($field) => $field['field_code'] === 'EMAIL');
            $contact['custom_fields_values'] = array_values($filteredFields);
        }
        unset($contact);

        return $this;
    }

    /**
     * Checks if contact have at least 1 working email
     *
     * @param array $contact
     * @return bool
     */
    public function hasAtLeastOneWorkingEmail(array $contact): bool
    {
        $hasWorkingEmail = false;
        $emails = $contact['custom_fields_values'][0]['values'];
        if (!$emails) {
            return false;
        }
        foreach ($emails as $email) {
            $type = $email["enum_code"];
            if ($type === 'WORK') {
                $hasWorkingEmail = true;
            }
        }
        return $hasWorkingEmail;
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
            $emails = $this->getContactEmails($contact);

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
     * @param string $accountId
     * @return array
     */
    public function prepareForUnisender(string $accountId): array
    {
        $listName = $accountId;
        $listIds = $this->getListIdByName($listName);
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
                $fieldData["data[$contactRow][2]"] = $listIds;
                $contactRow += 1;
            }
        }
        return array_merge($fieldNames, $fieldData);
    }

    public function isListExists(string $listName): bool
    {
        $unisenderApi = new UnisenderApi($this->apiKey);
        $isExists = strpos($unisenderApi->getLists(), $listName);
        return is_int($isExists);
    }

    /**
     * Checks if list is exist unless creates it
     *
     * @param string $listName
     * @return string
     */
    public function getListIdByName(string $listName): string
    {
        try {
            if (!$this->isListExists($listName)) {
                $this->createList($listName);
            }

            $unisenderApi = new UnisenderApi($this->apiKey);
            $unisenderLists = json_decode($unisenderApi->getLists(), true);

            foreach ($unisenderLists['result'] as $list) {
                if ($list['title'] === $listName) {
                    return (string) $list['id'];
                }
            }
            throw new Exception('Problem occurred. contact the developers for help');
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Creates contacts list in unisendler
     *
     * @param string $listName
     * @return void
     */
    public function createList(string $listName): void
    {
        $unisenderApi = new UnisenderApi($this->apiKey);
        $params = ['title' => $listName];
        $unisenderApi->createList($params);
    }
}
