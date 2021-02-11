<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter\Tests;

use Arquivei\LaravelPrometheusExporter\PrometheusExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;
use Prometheus\Histogram;

class RouteMiddlewareTest extends TestCase
{
    public function testMiddleware()
    {
        $value = null;
        $labels = null;
        $observe = function (float $time, array $data) use (&$value, &$labels) {
            $value = $time;
            $labels = $data;
        };
        $histogram = \Mockery::mock(Histogram::class);
        $histogram->shouldReceive('observe')->andReturnUsing($observe);

        $prometheus = \Mockery::mock(PrometheusExporter::class);
        $prometheus->shouldReceive('getOrRegisterHistogram')->andReturn($histogram);
        app()['prometheus'] = $prometheus;

        $request = new Request();
        $expectedResponse = new Response();
        $next = function (Request $request) use ($expectedResponse) {
            return $expectedResponse;
        };

        $matchedRouteMock = \Mockery::mock(\Symfony\Component\Routing\Route::class);
        $matchedRouteMock->shouldReceive('uri')->andReturn('/test/route');

        $middleware = \Mockery::mock('Arquivei\LaravelPrometheusExporter\PrometheusLumenRouteMiddleware[getMatchedRoute]');
        $middleware->shouldReceive('getMatchedRoute')->andReturn($matchedRouteMock);
        $actualResponse = $middleware->handle($request, $next);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertGreaterThan(0, $value);
        $this->assertSame(['GET', '/test/route', 200], $labels);
    }
}
