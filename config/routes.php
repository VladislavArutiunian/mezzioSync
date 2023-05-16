<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * FastRoute route configuration
 *
 * @see https://github.com/nikic/FastRoute
 *
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/{id:\d+}', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', \App\Handler\HomePageHandler::class, 'home');
//    $app->get('/api/ping', App\Handler\PingHandler::class, 'api.ping');
//    $app->get('/sum', SyncTrait\Handler\AuthHandler::class, 'sum');
    $app->get('/auth', Sync\Handler\AuthHandler::class, 'auth');
//    $app->get('/contacts', SyncTrait\Handler\ContactsHandler::class, 'contacts');
    //$app->get('/', SyncTrait\Handler\MainSyncHandler::class, 'main-sync');
    $app->get('/webhook', Sync\Handler\WebhookHandler::class, 'webhook-get');
    $app->post('/webhook', Sync\Handler\WebhookHandler::class, 'webhook');
    $app->get('/contact', Sync\Handler\ContactHandler::class, 'contact');
    $app->get('/send', Sync\Handler\SendHandler::class, 'send');
};
