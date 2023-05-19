<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Sync\Repository\ContactRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\ContactService;
use Sync\Service\KommoApiClient;
use Sync\Service\UnisenderApiService;

class WebhookHandler implements RequestHandlerInterface
{
    /** @var AccessRepository  */
    private AccessRepository $accessRepository;

    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;
    private ContactRepository $contactRepository;

    /**
     * ContactsHandler констурктор
     *
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository,
        ContactRepository $contactRepository
    ) {
        $this->accessRepository = $accessRepository;
        $this->integrationRepository = $integrationRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Save unisender apikey from widget
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
//        $body = json_decode(file_get_contents('php://input'), true);
        if (!isset($body['contacts'])) {
            throw new Exception('contacts not provided');
        }
        $status = key($body['contacts']);
        $contactId = $body['contacts'][$status][0]['id'];
        $kommoId = $body['account']['id'];
        $params['id'] = $body['account']['id'];

        $kommoApiClient = new KommoApiClient($this->accessRepository, $this->integrationRepository);
        $apiKey = $this->accessRepository->getApiKey($kommoId);

        if ($status != 'delete') {
            $contact = array_filter(
                $kommoApiClient->getContacts($kommoId),
                fn ($item) => $item['id'] == $contactId
            );
            $normalizedContacts = (new ContactService())->getNormalizedContacts($contact);

            switch ($status) {
                case 'add':
                    $this->contactRepository->saveContacts($normalizedContacts, (int) $kommoId);

                    $unisenderService = new UnisenderApiService($apiKey);
                    $unisenderResponseAdded = $unisenderService
                        ->importContactsByLimit($normalizedContacts, $kommoId);
                    break;
                case 'update':
                    $unisenderService = new UnisenderApiService($apiKey);
                    $contactsEmails = $this->contactRepository->getContactsEmails((int) $contactId);
                    $unisenderResponseDeleted = $unisenderService
                        ->importContactsByLimit($contactsEmails, $kommoId, true);
                    $unisenderResponseAdded = $unisenderService
                        ->importContactsByLimit($normalizedContacts, $kommoId);
                    $this->contactRepository->saveContacts($normalizedContacts, (int) $kommoId);
                        break;
            }
        } else {
            $unisenderService = new UnisenderApiService($apiKey);
            $contactsEmails = $this->contactRepository->getContactsEmails((int) $contactId);
            $unisenderResponseDeleted = $unisenderService
                ->importContactsByLimit($contactsEmails, $kommoId, true);
            $this->contactRepository->deleteContact((int) $contactId);
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
//            file_put_contents('request_delete_many.json', json_encode($body), FILE_APPEND);

//                    $exported_data = var_export($body, true);
//        file_put_contents('array_add.php', "<?php\n\n\$data = " . $exported_data . ";\n");

//$exported_data = var_export($normalizedContacts, true);
//file_put_contents('test_11.php', "<?php\n\n\$data = " . $exported_data . ";\n");
//        $exported_data = var_export($body, true);
//file_put_contents('test_12.php', "<?php\n\n\$data = " . $exported_data . ";\n");
//file_put_contents('test14.txt', json_encode($data));
