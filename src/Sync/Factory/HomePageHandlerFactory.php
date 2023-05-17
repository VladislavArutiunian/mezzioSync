<?php

declare(strict_types=1);

namespace Sync\Factory;

use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Handler\HomePageHandler;

use function assert;
use function get_class;

class HomePageHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        try {
            $router = $container->get(RouterInterface::class);
            assert($router instanceof RouterInterface);

            $template = $container->has(TemplateRendererInterface::class)
                ? $container->get(TemplateRendererInterface::class)
                : null;
            assert($template instanceof TemplateRendererInterface || null === $template);

            return new HomePageHandler(get_class($container), $router, $template);
        } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
            exit($e->getMessage());
        }
    }
}
