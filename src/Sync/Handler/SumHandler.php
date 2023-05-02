<?php

declare(strict_types=1);

namespace Sync\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Mezzio\Router;

class SumHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $getParams = $request->getQueryParams();
        $paramsSum = $this->getParamsSum($getParams);

        $inputParamsString = is_null($getParams) ? '' : implode(', ', array_values($getParams));
        $message = "Sum Operation performed successfully";

        $this->writeLog('info', $message, $inputParamsString, $paramsSum);

        return new HtmlResponse("<p>Sum of params is $paramsSum</p>");
    }

    public function getParamsSum(?array $getParams): int
    {
        if (is_null($getParams)) {
            return 0;
        }
        return array_sum(array_values($getParams));
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
            case "info":
                $log->info($output);
                break;
            default:
                throw new \Exception("Unknown type of log Type");
        }
    }
}
