<?php

namespace Arquivei\LaravelPrometheusExporter;

use Closure;
use Illuminate\Support\Facades\Route as RouteFacade;
use Prometheus\Histogram;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrometheusLaravelRouteMiddleware
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
        $matchedRoute = $this->getMatchedRoute($request);

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
            ],
            config('prometheus.guzzle_buckets') ?? null
        );
        /** @var  Histogram $histogram */
        $histogram->observe(
            $duration,
            [
                $request->method(),
                $matchedRoute->uri(),
                $response->getStatusCode(),
            ]
        );
        return $response;
    }

    public function getMatchedRoute(Request $request)
    {
        $routeCollection = RouteFacade::getRoutes();
        return $routeCollection->match($request);
    }
}
