<?php

declare(strict_types=1);

use Mezzio\Application;
use Sync\Handler\WebhookHandler;
use Sync\Handler\WidgetHandler;
use Sync\Handler\AuthHandler;
use Sync\Handler\ContactHandler;
use Sync\Handler\ContactsHandler;
use Sync\Handler\DbAccountsHandler;
use Sync\Handler\SendHandler;
use Sync\Handler\SetupHandler;
use Sync\Handler\SumHandler;

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

return static function (Application $app): void {
    $app->get('/sum', SumHandler::class, 'sum');
    $app->get('/auth', AuthHandler::class, 'auth');
    $app->get('/contacts', ContactsHandler::class, 'contacts');
    $app->get('/get-accounts', DbAccountsHandler::class, 'all-db-accounts');
    $app->get('/contact', ContactHandler::class, 'contact');
    $app->get('/send', SendHandler::class, 'send');
    $app->post('/setup', SetupHandler::class, 'setup');
    $app->post('/widget', WidgetHandler::class, 'widget');
    $app->post('/webhook', WebhookHandler::class, 'webhook');
};
