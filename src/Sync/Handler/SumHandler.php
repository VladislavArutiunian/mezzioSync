<?php

declare(strict_types=1);

namespace Sync\Handler;

use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SumHandler implements RequestHandlerInterface
{
    /**
     * Require Get params
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $getParams = $request->getQueryParams();
        $paramsSum = $this->getParamsSum($getParams);

        $inputParamsString = empty($getParams) ? '' : implode(', ', array_values($getParams));
        $message = "Sum Operation performed successfully";

        $this->writeLog('info', $message, $inputParamsString, $paramsSum);

        return new HtmlResponse("<p>Sum of params is $paramsSum</p>");
    }

    /**
     * @param array|null $getParams
     * @return int
     */
    public function getParamsSum(?array $getParams): int
    {
        if (is_null($getParams)) {
            return 0;
        }
        return array_sum(array_values($getParams));
    }

    /**
     * @param string $logType
     * @param string $message
     * @param string $inputParams
     * @param int|null $result
     * @return void
     */
    public function writeLog(string $logType, string $message, string $inputParams, ?int $result): void
    {
        $log = new Logger('sums-logger');
        $currentDate = date('Y-m-d');
        $stream = new StreamHandler(
            dirname(__DIR__, 3) . "/log/$currentDate/request.log",
            Logger::DEBUG
        );
        $log->pushHandler($stream);

        $output = "Message: $message; input params: $inputParams; result: $result";
        try {
            switch ($logType) {
                case "info":
                    $log->info($output);
                    break;
                default:
                    throw new Exception("Unknown type of log Type");
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
