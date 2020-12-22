<?php

namespace Arquivei\LaravelPrometheusExporter\Exporter\PushGateway\Client;

use Arquivei\LaravelPrometheusExporter\Exporter\PushGateway\Client\Handler\RetryDecider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class ClientFactory
{
    public static function create(): Client
    {
        return new \GuzzleHttp\Client([
            'handler' => self::buildHandlerStack()
        ]);
    }

    public static function buildHandlerStack(): callable
    {
        $handlerStack = HandlerStack::create(new CurlHandler());
        $handlerStack->push(Middleware::retry(new RetryDecider()));
        return $handlerStack;
    }
}