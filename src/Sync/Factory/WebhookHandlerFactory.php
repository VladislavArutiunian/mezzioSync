<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\WebhookHandler;
use Sync\Handler\WidgetHandler;
use Sync\Repository\AccessRepository;
use Sync\Repository\ContactRepository;
use Sync\Repository\IntegrationRepository;

class WebhookHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            return new WebhookHandler(
                $container->get(AccessRepository::class),
                $container->get(IntegrationRepository::class),
                $container->get(ContactRepository::class)
            );
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
