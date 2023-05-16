<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\ContactsHandler;

class ContactsHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $integration = $container->get('config')['integration'];
        return new ContactsHandler($integration);
    }
}
