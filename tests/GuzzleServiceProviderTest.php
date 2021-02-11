<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter\Tests;

use Arquivei\LaravelPrometheusExporter\GuzzleMiddleware;
use Arquivei\LaravelPrometheusExporter\GuzzleServiceProvider;
use Arquivei\LaravelPrometheusExporter\PrometheusServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use Prometheus\Histogram;

/**
 * @covers \Arquivei\LaravelPrometheusExporter\GuzzleServiceProvider<extended>
 */
class GuzzleServiceProviderTest extends TestCase
{
    public function testServiceProvidersShouldHaveCorrectClasses() : void
    {
        $this->assertInstanceOf(Client::class, $this->app->get('prometheus.guzzle.client'));
        $this->assertInstanceOf(CurlHandler::class, $this->app->get('prometheus.guzzle.handler'));
        $this->assertInstanceOf(GuzzleMiddleware::class, $this->app->get('prometheus.guzzle.middleware'));
        $this->assertInstanceOf(HandlerStack::class, $this->app->get('prometheus.guzzle.handler-stack'));
        $this->assertInstanceOf(Histogram::class, $this->app->get('prometheus.guzzle.client.histogram'));
    }

    public function testHistogramShouldHaveCorrectData()
    {
        /* @var \Prometheus\Histogram $histogram */
        $histogram = $this->app->get('prometheus.guzzle.client.histogram');
        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertSame(['method', 'external_endpoint', 'status_code'], $histogram->getLabelNames());
        $this->assertSame('app_guzzle_response_duration', $histogram->getName());
        $this->assertSame('Guzzle response duration histogram', $histogram->getHelp());
    }

    public function testGuzzleClientShouldCallHandlerStack()
    {
        $response = new Response(200, ['X-Foo' => 'Bar']);
        $this->app->singleton('prometheus.guzzle.handler', function ($app) use ($response) {
            return new MockHandler([$response]);
        });
        /* @var Client $guzzleClient */
        $guzzleClient = $this->app->get('prometheus.guzzle.client');
        $response = $guzzleClient->request('GET', '/');
        $this->assertNotEmpty($response);
    }

    protected function getPackageProviders($app) : array
    {
        return [
            PrometheusServiceProvider::class,
            GuzzleServiceProvider::class
        ];
    }
}
