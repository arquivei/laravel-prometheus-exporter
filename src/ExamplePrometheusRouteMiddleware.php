<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Prometheus\Histogram;
use Taxibeat\LaravelPrometheusExporter\PrometheusExporter;

class ExamplePrometheusRouteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $duration = microtime(true) - $start;

        /** @var PrometheusExporter $exporter */
        $exporter = app('prometheus');

        $histogram = $exporter->getOrRegisterHistogram(
            'response_time_seconds',
            'It observes response time.',
            [
                'method',
                'route',
                'status_code',
            ]
        );

        /** @var  Histogram $histogram */
        $histogram->observe(
            $duration,
            [
                $request->method(),
                $request->getPathInfo(),
                $response->getStatusCode(),
            ]
        );

        return $response;
    }
}
