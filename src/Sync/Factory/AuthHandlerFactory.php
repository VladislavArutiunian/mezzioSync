<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\AuthHandler;

class AuthHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            $integration = $container->get('config')['integration'];
            return new AuthHandler($integration);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
