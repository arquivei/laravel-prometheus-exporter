<?php

namespace Arquivei\LaravelPrometheusExporter\Exporter\PushGateway\Client\Handler;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Throwable;

class RetryDecider
{
    private $maxRetries;

    public function __construct(int $maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
    }

    public function __invoke(
        int $numberOfRetries,
        Request $request,
        ?Response $response = null,
        ?Throwable $exception = null
    ) {
        if ($numberOfRetries >= $this->maxRetries - 1) {
            return false;
        }

        if ($exception instanceof ConnectException) {
            return true;
        }

        if ($response && $response->getStatusCode() >= 500) {
            return true;
        }

        return false;
    }
}