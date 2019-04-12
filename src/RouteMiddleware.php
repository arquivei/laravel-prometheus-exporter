<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Prometheus\Histogram;

class RouteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next) : Response
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
