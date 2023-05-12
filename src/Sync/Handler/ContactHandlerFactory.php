<?php

declare(strict_types=1);

namespace Sync\Handler;

use AmoCRM\EntitiesServices\Webhooks;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function get_class;

class ContactHandlerFactory
{
    /**
     * Contact factory. Passes unisender api_key to Contact class
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
