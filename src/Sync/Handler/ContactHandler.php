<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Unisender\ApiWrapper\UnisenderApi;

/**
 * Class Contact
 *
 * Selects contact from Unisender
 */
class ContactHandler implements RequestHandlerInterface
{
    /**
     * Unisender api_key from project configs
     *
     * @var AccessRepository
     */
    private AccessRepository $accessRepository;

    /**
     * @param AccessRepository $accessRepository
     */
    public function __construct(AccessRepository $accessRepository)
    {
        $this->accessRepository = $accessRepository;
    }

    /**
     * Contact Handler
     * Requires email GET parameter and api key from db
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            if (!isset($queryParams['id']) || !isset($request->getQueryParams()['email'])) {
                throw new Exception('Not provided the contact email or account id');
            }
            $apiKey = $this->accessRepository->getApiKey($queryParams['id']);

            if (empty($apiKey)) {
                throw new Exception('The unisender api key dont exist');
            }
            $params = [
                'email' => $request->getQueryParams()['email'],
            ];

            $unisenderApi = new UnisenderApi($apiKey);
            $contactInfo = $unisenderApi->getContact($params);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
        http_response_code(200);
        return new JsonResponse($contactInfo);
    }
}
