<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\ContactsHandler;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;

class ContactsHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        return new ContactsHandler(
            $container->get(IntegrationRepository::class),
            $container->get(AccessRepository::class),
        );
    }
}
