<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Unisender\ApiWrapper\UnisenderApi;

class WebhookHandler implements RequestHandlerInterface
{
    public function __construct()
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
//        file_put_contents(__DIR__ . '/test.txt', '');
        file_put_contents(__DIR__ . '/test.txt', print_r($request->getParsedBody(), true), FILE_APPEND);
        http_response_code(200);
        //$t = new UnisenderApi('');
//        return new HtmlResponse('<h2>Webhook</h2>');
        return new JsonResponse(['status' => 'ok']);
    }
}
