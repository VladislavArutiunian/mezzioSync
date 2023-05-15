<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Api\ApiService;
use Sync\Api\UnisenderService;
use Unisender\ApiWrapper\UnisenderApi;

/**
 * Class receives all contacts from kommo
 * processes it and performs api request to send it to unisender
 */
class SendHandler implements RequestHandlerInterface
{
    /**
     * api key unisender
     * @var string
     */
    private string $apiKey;

    /* @var string */
    private string $returnUrl;

    /* @var string */
    private string $integrationId;

    /* @var string */
    private string $secretKey;

    public function __construct(array $integration, array $unisender)
    {
        $this->secretKey = $integration['secret_key'];
        $this->integrationId = $integration['integration_id'];
        $this->returnUrl = $integration['return_url'];
        $this->apiKey = $unisender['api_key'];
    }

    /**
     * Get contacts from kommo,
     * Filter and prepare for unisender,
     * Import contacts to unisender
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $accountId = $request->getQueryParams()['id'];
            if (!isset($accountId)) {
                throw new Exception('Provide an id in GET parameters');
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        $contacts =
            (new ApiService(
                $this->integrationId,
                $this->secretKey,
                $this->returnUrl
            ))->getContacts($request->getQueryParams());

        $unisenderService = new UnisenderService($contacts, $this->apiKey);
        $unisenderService
            ->filterFields()
            ->filterContacts()
            ->formatForUnisender();

        $unisenderResp = $unisenderService->importContactsByLimit($accountId);
        return new JsonResponse($unisenderResp);
    }
}
