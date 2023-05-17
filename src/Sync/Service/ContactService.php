<?php

namespace Sync\Service;

class ContactService
{
    /**
     * Filter only email fields
     *
     * @param array $contacts
     * @return array
     */
    private function filterFields(array $contacts): array
    {
        foreach ($contacts as &$contact) {
            $fields = $contact['custom_fields_values'] ?? [];
            $filteredFields = array_filter($fields, fn ($field) => $field['field_code'] === 'EMAIL');
            $contact['custom_fields_values'] = array_values($filteredFields);
        }
        unset($contact);

        return $contacts;
    }

    /**
     * Filter contact with non-empty name and at least one email
     *
     * @param array $contacts
     * @return array
     */
    private function filterContacts(array $contacts): array
    {
        return array_filter(
            $contacts,
            fn ($contact) => $contact['name'] && $this->hasAtLeastOneWorkingEmail($contact)
        );
    }

    /**
     * Checks if contact have at least 1 working email
     *
     * @param array $contact
     * @return bool
     */
    private function hasAtLeastOneWorkingEmail(array $contact): bool
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
     * @param array $contacts
     * @return array
     */
    public function formatContacts(array $contacts): array
    {
        $result = [];
        foreach ($contacts as $contact) {
            $emails = $this->getContactEmails($contact);

            $result[] = [
                'name' => $contact['name'],
                'id' => $contact['id'],
                'emails' => $emails
            ];
        }
        return $result;
    }

    /**
     * Get contact's emails
     *
     * @param array $contact
     * @return array
     */
    public function getContactEmails(array $contact): array
    {
        $emails = [];
        foreach ($contact['custom_fields_values'][0]['values'] as $emailItem) {
            $emails[] = $emailItem['value'];
        }
        return $emails;
    }

    /**
     * @param array $contacts
     * @return array
     */
    public function getNormalizedContacts(array $contacts): array
    {
        $filtered = $this->filterContacts($this->filterFields($contacts));
        return $this->formatContacts($filtered);
    }
}
