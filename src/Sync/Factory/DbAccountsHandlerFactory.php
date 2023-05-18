<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\DbAccountsHandler;
use Sync\Repository\AccessRepository;
use Sync\Repository\AccountRepository;
use Sync\Repository\IntegrationRepository;

class DbAccountsHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            return new DbAccountsHandler(
                $container->get(AccountRepository::class),
                $container->get(AccessRepository::class),
                $container->get(IntegrationRepository::class),
            );
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
