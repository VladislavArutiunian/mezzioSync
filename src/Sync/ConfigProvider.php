<?php

declare(strict_types=1);

namespace Sync;

use Sync\Handler\AuthHandler;
use Sync\Handler\AuthHandlerFactory;
use Sync\Handler\ContactHandler;
use Sync\Handler\ContactHandlerFactory;
use Sync\Handler\ContactsHandler;
use Sync\Handler\ContactsHandlerFactory;
use Sync\Handler\MainSyncHandler;
use Sync\Handler\MainSyncHandlerFactory;
use Sync\Handler\SumHandler;
use Sync\Handler\WebhookHandlerFactory;
use Sync\Handler\WebhookHandler;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [],
            'factories' => [
//                SumHandler::class => SumHandlerFactory::class,
//                AuthHandler::class => AuthHandlerFactory::class,
//                ContactsHandler::class => ContactsHandlerFactory::class,
                WebhookHandler::class => WebhookHandlerFactory::class,
                MainSyncHandler::class => MainSyncHandlerFactory::class,
                ContactHandler::class => ContactHandlerFactory::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'app'    => ['templates/app'],
                'error'  => ['templates/error'],
                'layout' => ['templates/layout'],
            ],
        ];
    }
}
