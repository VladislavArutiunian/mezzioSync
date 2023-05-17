<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\SetupHandler;
use Sync\Handler\DbAccountsHandler;
use Sync\Repository\AccessRepository;
use Sync\Repository\AccountRepository;
use Sync\Repository\IntegrationRepository;

class SetupHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            return new SetupHandler(
                $container->get(AccountRepository::class)
            );
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
