<?php

namespace Sync\Api;

use Exception;
use Sync\Handler\SendHandler;
use Unisender\ApiWrapper\UnisenderApi;

class UnisenderService
{
    /**
     * api key unisender
     * @var string
     */
    private string $apiKey;

    /**
     * contacts from kommo
     * @var string
     */
    private array $contacts;

    public function __construct(array $contacts, string $apiKey)
    {
        $this->contacts = $contacts;
        $this->apiKey = $apiKey;
    }

    /**
     * Filter contact with non-empty name and at least one email
     *
     * @return $this
     */
    public function filterContacts(): UnisenderService
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
    public function filterFields(): UnisenderService
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
    public function formatForUnisender(): UnisenderService
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
    public function prepareForUnisender(string $accountId, array $contacts): array
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
        foreach ($contacts as $contact) {
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
     * Creates contacts list in unisender
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

    public function importContactsByLimit(string $accountId): array
    {
        $unisenderApi = new UnisenderApi($this->apiKey);
        $responses = [];

        if (count($this->contacts) <= 500) {
            $params = $this->prepareForUnisender($accountId, $this->contacts);
            $responses[] = $unisenderApi->importContacts($params);
            return $responses;
        }

        $contactsChunked = array_chunk($this->contacts, 500);
        foreach ($contactsChunked as $contacts) {
            $params = $this->prepareForUnisender($accountId, $contacts);
            $responses[] = $unisenderApi->importContacts($params);
        }
        return $responses;
    }
}
