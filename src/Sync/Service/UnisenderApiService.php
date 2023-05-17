<?php

namespace Sync\Service;

use Exception;
use Unisender\ApiWrapper\UnisenderApi;

class UnisenderApiService
{
    /**
     * api key unisender
     * @var string
     */
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Prepares data for request to unisender api
     *
     * @param string $accountId
     * @param array $contacts
     * @return array
     */
    public function prepareForRequest(string $accountId, array $contacts): array
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

    /**
     * Check if list exists
     *
     * @param string $listName
     * @return bool
     */
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

            if (isset($unisenderLists['error'])) {
                throw new Exception($unisenderLists['error'] . ' ' . $unisenderLists['code'] ?? '');
            }
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

    /**
     * Limit for contact import to unisender - 500 per request
     * This method chunks contact's collection into several collections to do import
     *
     * @param array $contacts
     * @param string $accountId
     * @return array
     */
    public function importContactsByLimit(array $contacts, string $accountId): array
    {
        $unisenderApi = new UnisenderApi($this->apiKey);
        $responses = [];

        $contactsChunked = array_chunk($contacts, 500);
        foreach ($contactsChunked as $contacts) {
            $params = $this->prepareForRequest($accountId, $contacts);
            $responses[] = json_decode($unisenderApi->importContacts($params), true);
        }

        return array_reduce($responses, function ($carry, $item) use ($responses) {
            $prevValue = $carry['result'];
            $newValue = $item['result'];

            $oldLogs = $this->prepareLog($prevValue['log']);
            $newLogs = $this->prepareLog($newValue['log']);

            if (!empty($oldLogs)) {
                $newLogs[] = $oldLogs;
            }
            return [
                'result' => [
                    'total' => $newValue['total'] += $prevValue['total'],
                    'inserted' => $newValue['inserted'] += $prevValue['inserted'],
                    'updated' => $newValue['updated'] += $prevValue['updated'],
                    'deleted' => $newValue['deleted'] += $prevValue['deleted'],
                    'new_emails' => $newValue['new_emails'] += $prevValue['new_emails'],
                    'invalid' => $newValue['invalid'] += $prevValue['invalid'],
                    'log' => $newLogs,
                ]
            ];
        }, []);
    }

    /**
     * Prepares log
     *
     * @param array|null $logs
     * @return array
     */
    public function prepareLog(?array $logs): array
    {
        if (!isset($logs)) {
            return [];
        }
        return array_map(fn ($log) => $log['code'] . ' ' . $log['message'], $logs);
    }
}
