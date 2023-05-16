<?php

declare(strict_types=1);

namespace Sync\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\ContactHandler;

class ContactHandlerFactory
{
    /**
     * Contact factory. Passes unisender api key to Contact class
     *
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $unisenderApiKey = $container->get('config')['unisender']['api_key'];
        return new ContactHandler($unisenderApiKey);
    }
}
