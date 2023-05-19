<?php

namespace Sync\Repository;

use Sync\Model\Account;
use Sync\Model\Contact;

class ContactRepository
{
    /**
     * Saving an array of contacts
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
     * Save contact in db
     *
     * @param array $contactColl
     * @param int $kommoId
     * @return void
     */
    public function saveContact(array $contactColl, int $kommoId): void
    {
        $accountId = Account::where('kommo_id', $kommoId)->first()->id;
        $contact = new Contact();

        $contact::updateOrCreate([
                'kommo_contact_id' => $contactColl['id'],
            ], [
                'account_id' => $accountId,
                'emails' => json_encode($contactColl['emails'])
            ]);
    }

    /**
     * Gets contact emails from db
     *
     * @param int $contactId
     * @return array
     */
    public function getContactEmails(int $contactId): array
    {
        $emails = Contact::where('kommo_contact_id', $contactId)->first()->emails;
        $resultEmails = ['emails' => json_decode($emails)];
        $result[] = $resultEmails;
        return $result;
    }

    /**
     * Deletes contact from db
     *
     * @param int $contactId
     * @return void
     */
    public function deleteContact(int $contactId)
    {
        $contact = Contact::where('kommo_contact_id', $contactId)->first();
        $contact->delete();
    }
}
