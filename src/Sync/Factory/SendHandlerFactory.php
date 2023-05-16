<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\SendHandler;

class SendHandlerFactory
{
    /**
     * Send factory. Passes integrationId, secretKey, returnUrl from configs to Send Handler class
     *
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            $integration = $container->get('config')['integration'];
            $apiKey = $container->get('config')['unisender'];
            return new SendHandler($integration, $apiKey);
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface | NotFoundExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
