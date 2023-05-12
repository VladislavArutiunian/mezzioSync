<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
    private string $unisenderApiKey;

    public function __construct(string $unisenderApiKey)
    {
        $this->unisenderApiKey = $unisenderApiKey;
    }

    /**
     * Contact Handler
     * Requires email GET parameter and api key from config
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            if (!isset($request->getQueryParams()['email'])) {
                throw new \Exception('Provide the contact email');
            }
            if (empty($this->unisenderApiKey)) {
                throw new \Exception('Add the unisender api key to configs');
            }
            $params = [
                'email' => $request->getQueryParams()['email'],
            ];

            $unisenderApi = new UnisenderApi($this->unisenderApiKey);
            $contactInfo = $unisenderApi->getContact($params);
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        http_response_code(200);
        return new JsonResponse($contactInfo);
    }
}
