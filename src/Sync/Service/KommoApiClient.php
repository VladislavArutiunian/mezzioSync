<?php

namespace Sync\Service;

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;
use Sync\Service\Authorization\AbstractAuthorization;

/**
 * Class KommoApiService.
 *
 * @package SyncTrait\Api
 */
class KommoApiService
{
    /** @var AbstractAuthorization  */
    private AbstractAuthorization $abstractAuthorization;

    /**
     * @param AbstractAuthorization $abstractAuthorization
     */
    public function __construct(AbstractAuthorization $abstractAuthorization)
    {
        $this->abstractAuthorization = $abstractAuthorization;
    }

    /**
     * Получить имя аккаунта
     *
     * @param string|null $kommoId
     * @return string
     */
    public function getName(?string $kommoId = null): string
    {
        try {
            $tokenService = $this->abstractAuthorization->tokenService;

            if (is_null($kommoId) && !isset($_SESSION['service_id'])) {
                throw new Exception('provide an account id');
            } elseif (is_null($kommoId)) {
                $kommoId = $_SESSION['service_id'];
            }

            $accessToken = $tokenService->readToken($kommoId);

            return $this->abstractAuthorization
                ->apiClient
                ->getOAuthClient()
                ->setBaseDomain($accessToken->jsonSerialize()['base_domain'])
                ->getResourceOwner($accessToken)
                ->getName();
        } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException | AmoCRMException $e) {
            $tokenService->deleteToken($kommoId);
            header('Location: ' . '/auth?id=' . $kommoId);
            exit($e->getMessage());
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * Получить список контактов
     *
     * @param array $queryParams
     * @return array
     */
    public function getContacts(array $queryParams): array
    {
        try {
            $tokenService = $this->abstractAuthorization->tokenService;

            if (!isset($queryParams['id'])) {
                throw new Exception('provide an account id');
            }

            $pageNumber = $queryParams['page'] ?? 1;
            $id = $queryParams['id'];

            if (!$tokenService->isTokenExists($id)) {
                header('Location: ' . "/auth?id=$id");
            }

            $accessToken = $tokenService->readToken($id);

            $filter = new ContactsFilter();
            $filter->setLimit(250);
            $flaq = true;
            $result = [];

            while ($flaq) {
                try {
                    $filter->setPage($pageNumber);
                    $bunch = $this
                        ->abstractAuthorization
                        ->apiClient
                        ->setAccountBaseDomain($accessToken->jsonSerialize()['base_domain'])
                        ->setAccessToken($this->accessToken)
                        ->contacts()
                        ->get($filter)
                        ->toArray();

                    $pageNumber += 1;
                    $result = array_merge($result, $bunch);
                } catch (AmoCRMApiNoContentException $e) {
                    $flaq = false;
                } catch (AmoCRMMissedTokenException | AmoCRMoAuthApiException $e) {
                    $tokenService->deleteToken($id);
                    header('Location: ' . "/auth?id=$id");
                    exit($e->getMessage());
                }
            }
            return $result;
        } catch (Exception | AmoCRMApiException $e) {
            exit($e->getMessage());
        }
    }
}
