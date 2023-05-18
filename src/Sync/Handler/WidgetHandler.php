<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;

class WidgetHandler implements RequestHandlerInterface
{
    /** @var AccessRepository  */
    private AccessRepository $accessRepository;

    /**
     * ContactsHandler констурктор
     *
     * @param AccessRepository $accessRepository
     */
    public function __construct(AccessRepository $accessRepository)
    {
        $this->accessRepository = $accessRepository;
    }

    /**
     * Save unisender apikey from widget
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            if (!isset($body['account_id']) || !isset($body['unisender_key'])) {
                throw new Exception('you need provide account_id and unisender_key');
            }

            $this->accessRepository->saveApiKey($body['account_id'], $body['unisender_key']);
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
}
