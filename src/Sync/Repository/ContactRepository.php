<?php

namespace Sync\Repository;

use Sync\Model\Account;
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
//    public function saveContact(array $contactColl, int $accountId): void
//    {
//        $contact = new Contact();
//
//        $contact::updateOrCreate([
//                'kommo_contact_id' => $contactColl['id'],
//            ], [
//                'account_id' => $accountId,
//                'emails' => json_encode($contactColl['emails'])
//            ]);
//    }

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

//    public function getContactsEmails(int $kommoId): array
//    {
//        $acc = Account::with('contacts')->where('kommo_id', $kommoId)->first();
//        $contacts = $acc->contacts;
//        $result = [];
//        foreach ($contacts as $contact) {
//            $emails = ['emails' => json_decode($contact->emails)];
//            $result[] = $emails;
//        }
//        return $result;
//    }

    public function getContactsEmails(int $contactId): array
    {
        $emails = Contact::where('kommo_contact_id', $contactId)->first()->emails;
        $resultEmails = ['emails' => json_decode($emails)];
        $result[] = $resultEmails;
        return $result;
    }

    public function deleteContact(int $contactId)
    {
        $contact = Contact::where('kommo_contact_id', $contactId)->first();
        $contact->delete();
    }
}
