<?php

declare(strict_types=1);

namespace Sync\Handler;

use AmoCRM\EntitiesServices\Webhooks;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function get_class;

class SendHandlerFactory
{
    /**
     * Send factory. Passes integrationId, secretKey, returnUrl from configs to Send Handler class
     *
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            $integration = $container->get('config')['integration'];
            $apiKey = $container->get('config')['unisender'];
            return new SendHandler($integration, $apiKey);
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface | NotFoundExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
