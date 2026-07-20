<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter\Tests;

use Arquivei\LaravelPrometheusExporter\PrometheusExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;
use Prometheus\Counter;
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
        $histogram->shouldReceive('observe')->once()->andReturnUsing($observe);

        $counter = \Mockery::mock(Counter::class);
        $counter->shouldReceive('inc')->andReturn(null);

        $latencyHistogram = \Mockery::mock(Histogram::class);
        $latencyHistogram->shouldReceive('observe')->andReturn(null);

        $prometheus = \Mockery::mock(PrometheusExporter::class);
        $prometheus->shouldReceive('getOrRegisterHistogram')->andReturn($histogram);
        $prometheus->shouldReceive('getOrRegisterNamelessCounter')->andReturn($counter);
        $prometheus->shouldReceive('getOrRegisterNamelessHistogram')->andReturn($latencyHistogram);
        app()['prometheus'] = $prometheus;

        $request = new Request();
        $expectedResponse = new Response();
        $minimumRequestDurationUs = 10_000;
        $minimumRequestDurationS = $minimumRequestDurationUs / 1_000_000;
        $next = function (Request $request) use ($expectedResponse, $minimumRequestDurationUs) {
            usleep($minimumRequestDurationUs); // min 10us request duration for testing
            return $expectedResponse;
        };

        $matchedRouteMock = \Mockery::mock(Route::class);
        $matchedRouteMock->shouldReceive('uri')->andReturn('/test/route');
        $matchedRouteMock->shouldReceive('getActionName')->andReturn('TestController@testAction');

        $middleware = \Mockery::mock('Arquivei\LaravelPrometheusExporter\PrometheusLumenRouteMiddleware[getMatchedRoute]');
        $middleware->shouldReceive('getMatchedRoute')->andReturn($matchedRouteMock);
        $actualResponse = $middleware->handle($request, $next);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertGreaterThan($minimumRequestDurationS, $value, 'Duration must be greater than simulated delay');
        $this->assertSame(['GET', '/test/route', 200], $labels);
    }
}
