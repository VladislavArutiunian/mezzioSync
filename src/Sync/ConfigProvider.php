<?php

declare(strict_types=1);

namespace Sync;

use Sync\Repository\AccessRepository;
use Sync\Repository\IntegrationRepository;
use Sync\Factory\AuthHandlerFactory;
use Sync\Factory\ContactHandlerFactory;
use Sync\Factory\ContactsHandlerFactory;
use Sync\Factory\HomePageHandlerFactory;
use Sync\Factory\SendHandlerFactory;
use Sync\Factory\SumHandlerFactory;
use Sync\Handler\AuthHandler;
use Sync\Handler\ContactHandler;
use Sync\Handler\ContactsHandler;
use Sync\Handler\HomePageHandler;
use Sync\Handler\SendHandler;
use Sync\Handler\SumHandler;
use Sync\Repository\AccountRepository;
use Sync\Repository\ContactRepository;

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
            'invokables' => [
                // Repositories
                AccountRepository::class => AccountRepository::class, // TODO
                ContactRepository::class => ContactRepository::class,
                AccessRepository::class => AccessRepository::class,
                IntegrationRepository::class => IntegrationRepository::class,
            ],
            'factories' => [
                // Handlers
                HomePageHandler::class => HomePageHandlerFactory::class,
                SumHandler::class => SumHandlerFactory::class,
                AuthHandler::class => AuthHandlerFactory::class,
                ContactsHandler::class => ContactsHandlerFactory::class,
                //WebhookHandler::class => WebhookHandlerFactory::class,
                ContactHandler::class => ContactHandlerFactory::class,
                SendHandler::class => SendHandlerFactory::class,
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
