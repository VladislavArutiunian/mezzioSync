<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Unisender\ApiWrapper\UnisenderApi;

class ContactHandler implements RequestHandlerInterface
{
    private array $unisenderApiKey;

    public function __construct(array $unisenderApiKey)
    {
        $this->unisenderApiKey = $unisenderApiKey;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            if (!isset($request->getQueryParams()['email'])) {
                throw new \Exception('provide the contact email');
            }
            if (!isset($this->unisenderApiKey['api_key'])) {
                throw new \Exception('add unisender api key to configs.unisendler');
            }
            $email = $request->getQueryParams()['email'];
            $apiKey = $this->unisenderApiKey['api_key'];
            $params = [
                'email' => $email,
            ];
            $unisenderApi = new UnisenderApi($apiKey);
            $contactInfo = $unisenderApi->getContact($params);
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        http_response_code(200);
        return new HtmlResponse(print_r($contactInfo, true));
    }
}
//'kicis43537@meidecn.com'
