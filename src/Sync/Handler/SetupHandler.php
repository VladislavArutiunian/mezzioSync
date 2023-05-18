<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sync\Repository\AccountRepository;

/** Class SetupHandler creates Account and related Integration */
class SetupHandler implements RequestHandlerInterface
{
    /** @var AccountRepository  */
    private AccountRepository $accountRepository;

    /**
     * @param AccountRepository $accountRepository
     */
    public function __construct(
        AccountRepository $accountRepository
    ) {
        $this->accountRepository = $accountRepository;
    }

    /**
     * Get all accounts from Db
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        try {
            $this->accountRepository->createAccountWithIntegration($body);
        } catch (Exception $e) {
            $errorResponse = [
                'status' => 'error',
                'data' => [
                    'message' => 'DataBase connection issues'
                ]
            ];
            return new JsonResponse($errorResponse);
        }
        $successResponse = [
            'status' => 'success',
            'data' => [
                'message' => ''
            ]
        ];
        return new JsonResponse($successResponse);
    }
}
