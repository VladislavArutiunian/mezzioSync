<?php

namespace Sync\Service;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\WebhookModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;

/**
 * Class KommoApiClient.
 *
 * @package SyncTrait\Api
 */
class KommoApiClient
{
    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    /** @var TokenService  */
    private TokenService $tokenService;
    private AmoCRMApiClient $amoCRMApiClient;

    /**
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository
    ) {
        $this->integrationRepository = $integrationRepository;
        $this->tokenService = new TokenService($accessRepository);
    }

    /**
     * Получить имя аккаунта
     *
     * @param string|null $kommoId
     * @return string
     */
    public function getName(string $kommoId): string
    {
        try {
            $accessToken = $this->tokenService->readToken($kommoId);

            $integration = $this->integrationRepository->getIntegration($kommoId);
            $this->amoCRMApiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url
            );

            return $this
                ->amoCRMApiClient
                ->getOAuthClient()
                ->setBaseDomain($accessToken->jsonSerialize()['base_domain'])
                ->getResourceOwner($accessToken)
                ->getName();
        } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException | AmoCRMException $e) {
            $this->tokenService->deleteToken($kommoId);
            header('Location: ' . '/auth?id=' . $kommoId);
            exit($e->getMessage());
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Получить список контактов
     *
     * @param string $kommoId
     * @return array
     */
    public function getContacts(string $kommoId): array
    {
        try {
            if (!isset($kommoId)) {
                throw new Exception('provide an account id');
            }

            $integration = $this->integrationRepository->getIntegration($kommoId);
            $this->amoCRMApiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url
            );

            $pageNumber = $pageNumber ?? 1;

            if (!$this->tokenService->isTokenExists($kommoId)) {
                header('Location: ' . "/auth?id=$kommoId");
            }

            $accessToken = $this->tokenService->readToken($kommoId);

            $filter = new ContactsFilter();
            $filter->setLimit(250);
            $flag = true;
            $result = [];

            while ($flag) {
                try {
                    $filter->setPage($pageNumber);
                    $bunch = $this
                        ->amoCRMApiClient
                        ->setAccountBaseDomain($accessToken->jsonSerialize()['base_domain'])
                        ->setAccessToken($accessToken)
                        ->contacts()
                        ->get($filter)
                        ->toArray();

                    $pageNumber += 1;
                    $result = array_merge($result, $bunch);
                } catch (AmoCRMApiNoContentException $e) {
                    $flag = false;
                } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException $e) {
                    $this->tokenService->deleteToken($kommoId);
                    header('Location: ' . "/auth?id=$kommoId");
                    exit($e->getMessage());
                }
            }
            return $result;
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Gets Contact from kommo
     *
     * @param string $kommoId
     * @param string $contactId
     * @return array
     */
    public function getContact(string $kommoId, string $contactId): array
    {
        try {
            if (!isset($kommoId)) {
                throw new Exception('provide an account id');
            }

            $integration = $this->integrationRepository->getIntegration($kommoId);
            $this->amoCRMApiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url
            );

            if (!$this->tokenService->isTokenExists($kommoId)) {
                header('Location: ' . "/auth?id=$kommoId");
            }

            $accessToken = $this->tokenService->readToken($kommoId);
            return $this
                ->amoCRMApiClient
                ->setAccountBaseDomain($accessToken->jsonSerialize()['base_domain'])
                ->setAccessToken($accessToken)
                ->contacts()
                ->getOne($contactId)
                ->toArray();
        } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException $e) {
            $this->tokenService->deleteToken($kommoId);
            header('Location: ' . "/auth?id=$kommoId");
            exit($e->getMessage());
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Subscribes on webhook on integration host
     *
     * @param string $kommoId
     * @return void
     */
    public function subscribeWebhook(string $kommoId)
    {
        try {
            $integration = $this->integrationRepository->getIntegration($kommoId);
            $this->amoCRMApiClient = new AmoCRMApiClient(
                $integration->client_id,
                $integration->secret_key,
                $integration->url
            );
            if (!$this->tokenService->isTokenExists($kommoId)) {
                header('Location: ' . "/auth?id=$kommoId");
            }
            $url = $this->integrationRepository->getUrl($kommoId);
            ['scheme' => $scheme, 'host' => $host] = parse_url($url);
            $webhookUrl = "$scheme://$host/webhook";

            $accessToken = $this->tokenService->readToken($kommoId);
            $webHookModel = (new WebhookModel())
                ->setSettings([
                    'add_contact',
                    'update_contact',
                    'delete_contact'
                ])->setDestination($webhookUrl);
            $this->amoCRMApiClient
                ->setAccountBaseDomain($accessToken->jsonSerialize()['base_domain'])
                ->setAccessToken($accessToken)
                ->webhooks()
                ->subscribe($webHookModel)
                ->toArray();
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
