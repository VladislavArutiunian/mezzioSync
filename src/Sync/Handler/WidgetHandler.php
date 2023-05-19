<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Service\KommoApiClient;

class WidgetHandler implements RequestHandlerInterface
{
    /** @var AccessRepository  */
    private AccessRepository $accessRepository;

    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    /**
     * ContactsHandler констурктор
     *
     * @param AccessRepository $accessRepository
     * @param IntegrationRepository $integrationRepository
     */
    public function __construct(
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository
    )
    {
        $this->accessRepository = $accessRepository;
        $this->integrationRepository = $integrationRepository;
    }

    /**
     * Save unisender apikey from widget
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            if (!isset($body['account_id']) || !isset($body['unisender_key'])) {
                throw new Exception('you need provide account_id and unisender_key');
            }

            $this->accessRepository->saveApiKey($body['account_id'], $body['unisender_key']);
            $kommoApiClient = new KommoApiClient($this->accessRepository, $this->integrationRepository);
            $kommoApiClient->subscribeWebhook($body['account_id']);
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
