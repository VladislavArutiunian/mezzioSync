<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\WidgetHandler;
use Sync\Repository\AccessRepository;

class WidgetHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            return new WidgetHandler(
                $container->get(AccessRepository::class)
            );
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
