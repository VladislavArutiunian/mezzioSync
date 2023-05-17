<?php

namespace Sync\Repository;

use Sync\Model\Contact;

class ContactRepository
{
    /**
     * Save array of contacts
     *
     * @param array $contacts
     * @param int $accountId
     * @return void
     */
    public function saveContacts(array $contacts, int $accountId): void
    {
        foreach ($contacts as $contact) {
            $this->saveContact($contact, $accountId);
        }
    }

    /**
     * Save one contact
     *
     * @param array $contactColl
     * @param int $accountId
     * @return void
     */
    public function saveContact(array $contactColl, int $accountId): void
    {
        $contact = new Contact();

        $contact::updateOrCreate(
            [
                'kommo_contact_id' => $contactColl['id'],
            ],
            [
                'account_id' => $accountId,
                'emails' => json_encode($contactColl['emails'])
            ]
        );
    }
}
