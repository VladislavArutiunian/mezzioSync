<?php

namespace Sync\Service;

/**
 * Class WebhookContactService
 *
 * Works with contacts, validate it to synchronization
 */
class WebhookContactService
{
    /**
     * Нормализует переданный контакт по необходимым условиям
     *
     * @param array $contact
     * @return array|array[]
     */
    public function validateAndNormalize(array $contact): array
    {
        $name = $contact['name'];
        $contactId = $contact['id'];
        $emailFields = $this->getOnlyEmailFields($contact);
        if (!$this->hasWorkingEmail($emailFields)) {
            return [];
        }
        $emails = $this->getAllEmails($emailFields);
        $contact = [
            'name' => $name,
            'id' => $contactId,
            'emails'=> $emails,
        ];
        return [$contact];
   }

    /**
     * Keep only email field
     *
     * @param array $contact
     * @return array
     */
    public function getOnlyEmailFields(array $contact): array
   {
       $filtered = array_filter(
           $contact['custom_fields_values'],
           fn ($customField) => $customField['field_code'] === 'EMAIL'
       );
       return array_values($filtered);
   }

    /**
     * Иммет ли контакт поле с типом work email
     *
     * @param array $emailFields
     * @return bool
     */
    public function hasWorkingEmail(array $emailFields): bool
    {
        $emails = $emailFields[0]['values'];
        if (is_null($emails)) {
            return false;
        }
        $filtered = array_filter(
            $emails,
            fn ($emailField) => $emailField['enum_code'] === 'WORK'
        );
        return !empty($filtered);
    }

    /**
     * Returns all emails
     *
     * @param array $emailFields
     * @return array
     */
    public function getAllEmails(array $emailFields): array
    {
        return array_map(
            fn ($emailField) => $emailField['value'],
            $emailFields[0]['values']
        );
    }
}
