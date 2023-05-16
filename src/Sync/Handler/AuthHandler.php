<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Api\ApiService;

class AuthHandler implements RequestHandlerInterface
{
    /**
     * Secret key from integration
     * @var string|mixed
     */
    private string $secretKey;

    /* @var string|mixed */
    private string $integrationId;

    /* @var string|mixed */
    private string $returnUrl;

    /**
     * ApiService конструктор.
     *
     * @param array $integration
     */
    public function __construct(array $integration)
    {
        $this->secretKey = $integration['secret_key'];
        $this->integrationId = $integration['integration_id'];
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
