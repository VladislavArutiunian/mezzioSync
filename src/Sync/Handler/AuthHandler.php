<?php

declare(strict_types=1);

namespace Sync\Handler;
// TODO: неиспользуемые юзы
use http\Env\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Api\ApiService;

class AuthHandler implements RequestHandlerInterface
{
    private string $secretKey; // TODO: PHPDocs
    private string $integrationId; // TODO: PHPDocs
    private string $authCode; // TODO: не используется

    private string $returnUrl; // TODO: PHPDocs

    /**
     * ApiService конструктор.
     *
     * @param array $integration
     */
    public function __construct(array $integration)
    {
        $this->secretKey = $integration['secret_key'];
        $this->integrationId = $integration['integration_id'];
        $this->authCode = $integration['auth_code'];
        $this->returnUrl = $integration['return_url'];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $apiClient = new ApiService($this->integrationId, $this->secretKey, $this->returnUrl);
        $apiClient->auth($queryParams);

        $accountName = $apiClient->getName($queryParams);

        return new JsonResponse(["name" => $accountName]);
    }
}
