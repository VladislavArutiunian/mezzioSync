<?php

declare(strict_types=1);

namespace Sync\Handler;

use AmoCRM\Client\AmoCRMApiClient;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\Authorization\SimpleAuthorization;
use Sync\Service\Authorization\StandardAuthorization;
use Sync\Service\KommoApiClient;
use Sync\Service\TokenService;

class AuthHandler implements RequestHandlerInterface
{
    /* @var IntegrationRepository */
    private IntegrationRepository $integrationRepository;

    /* @var AccessRepository */
    private AccessRepository $accessRepository;

    /**
     * @param IntegrationRepository $integrationRepository
     * @param AccessRepository $accessRepository
     */
    public function __construct(
        IntegrationRepository $integrationRepository,
        AccessRepository $accessRepository
    ) {
        $this->integrationRepository = $integrationRepository;
        $this->accessRepository = $accessRepository;
    }

    /**
     * performs authorization
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if ($queryParams['from_widget']) {
            $authorization = new SimpleAuthorization($this->accessRepository, $this->integrationRepository);
        } else {
            $authorization = new StandardAuthorization($this->accessRepository, $this->integrationRepository);
        }
        $authorization->auth($queryParams);

        $accountName = (new KommoApiClient($authorization))->getName((string) $_SESSION['service_id']);

        return new JsonResponse(["name" => $accountName]);
    }
}
