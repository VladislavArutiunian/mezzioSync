<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\SumHandler;

class SumHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        return new SumHandler();
    }
}
