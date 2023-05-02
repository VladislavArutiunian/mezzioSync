<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Api\ApiService;

class AuthHandler implements RequestHandlerInterface
{
    private string $secretKey;
    private string $integrationId;
    private string $authCode;

    public function __construct(array $integration)
    {
        $this->secretKey     = $integration['secret_key'];
        $this->integrationId = $integration['integration_id'];
        $this->authCode      = $integration['auth_code'];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $apiClient = new ApiService($this->integrationId, $this->secretKey, $this->authCode);
        $apiClient->auth($request->getQueryParams());
        return new JsonResponse([]);
    }
}
