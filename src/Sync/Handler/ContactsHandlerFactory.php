<?php

declare(strict_types=1);

namespace Sync\Handler;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContactsHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $integration = $container->get('config')['integration'];
        return new ContactsHandler($integration);
    }
}
