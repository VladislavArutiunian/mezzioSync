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

class WebhookHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        return new WebhookHandler();
    }
}
