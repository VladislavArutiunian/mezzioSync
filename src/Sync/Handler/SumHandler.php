<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SumHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $getParams = $request->getQueryParams();
        $paramsSum = $this->getParamsSum($getParams);
        $inputParams = implode(', ', array_values($getParams));

        if (is_null($paramsSum)) {
            $message = "Provided Get Params isn't type of integer";
            $this->writeLog('error', $message, $inputParams, $paramsSum);

            return new HtmlResponse("<p>Error: input data is not type of integer</p>");
        }
        $message = "Success sum operation";
        $result = $paramsSum;
        $this->writeLog('info', $message, $inputParams, $result);

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

    public function writeLog(string $logType, string $message, string $inputParams, ?int $result)
    {
        $log = new Logger('sums-logger');
        $currentDate = date('Y-m-d');
        $stream = new StreamHandler(
            dirname(__DIR__, 3) . "/log/$currentDate/request.log",
            Logger::DEBUG);
        $log->pushHandler($stream);

        $output = "Message: $message; input params: $inputParams; result: $result";
        switch ($logType) {
            case "error":
                $log->error($output);
                break;
            case "info":
                $log->info($output);
                break;
            default:
                throw new \Exception("Unknown type of log Type");
        }
    }
}
