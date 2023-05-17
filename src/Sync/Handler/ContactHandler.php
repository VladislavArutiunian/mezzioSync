<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Unisender\ApiWrapper\UnisenderApi;

/**
 * Class Contact
 *
 * Selects contact from Unisendler
 */
class ContactHandler implements RequestHandlerInterface
{
    /**
     * Unisender api_key from project configs
     *
     * @var string
     */
    private AccessRepository $accessRepository;

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
            if (!isset($queryParams['id'])) {
                throw new \Exception('Provide the contact email');
            }
            $apiKey = $this->accessRepository->getApiKey($queryParams['id']);
            if (!isset($request->getQueryParams()['email'])) {
                throw new \Exception('Provide the contact email');
            }
            if (empty($apiKey)) {
                throw new \Exception('Add the unisender api key to configs');
            }
            $params = [
                'email' => $request->getQueryParams()['email'],
            ];

            $unisenderApi = new UnisenderApi($apiKey);
            $contactInfo = $unisenderApi->getContact($params);
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        http_response_code(200);
        return new JsonResponse($contactInfo);
    }
}
