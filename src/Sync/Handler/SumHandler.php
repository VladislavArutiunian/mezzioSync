<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SumHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $getParams = $request->getQueryParams();
        $paramsSum = $this->getParamsSum($getParams);
        return new HtmlResponse("<p>Sum of params is $paramsSum</p>");
    }

    public function getParamsSum(array $getParams): ?int
    {
        $sum = 0;
        foreach ($getParams as $param) {
            if (!is_numeric($param)) {
                return null;
            }
            $sum += $param;
        }
        return $sum;
    }
}
