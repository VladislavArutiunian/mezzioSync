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
use Sync\Repository\IntegrationRepository;
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

    /** @var IntegrationRepository  */
    private IntegrationRepository $integrationRepository;

    public function __construct(
        AccountRepository $accountRepository,
        AccessRepository $accessRepository,
        IntegrationRepository $integrationRepository
    ) {
        $this->accountRepository = $accountRepository;
        $this->accessRepository = $accessRepository;
        $this->integrationRepository = $integrationRepository;
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
            $this->accessRepository,
            $this->integrationRepository
        );
        return new JsonResponse($accountService->buildResponse());
    }
}
