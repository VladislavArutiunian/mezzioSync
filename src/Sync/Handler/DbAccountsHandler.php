<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccessRepository;
use Sync\Repository\AccountRepository;
use Sync\Service\AccountService;

class DbAccountsHandler implements RequestHandlerInterface
{
    /**
     * @var AccountRepository
     */
    private AccountRepository $accountRepository;

    /**
     * @var AccessRepository
     */
    private AccessRepository $accessRepository;

    public function __construct(
        AccountRepository $accountRepository,
        AccessRepository $accessRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->accessRepository = $accessRepository;
    }

    /**
     * Get all accounts from Db
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $accountService = new AccountService(
            $this->accountRepository,
            $this->accessRepository
        );
        return new JsonResponse($accountService->buildResponse());
    }
}
