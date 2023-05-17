<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\AuthHandler;
use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;

class AuthHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            return new AuthHandler(
                $container->get(IntegrationRepository::class),
                $container->get(AccessRepository::class),
            );
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
