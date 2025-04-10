<?php

namespace Arquivei\LaravelPrometheusExporter;

use Closure;
use Illuminate\Support\Facades\Route as RouteFacade;
use Prometheus\Histogram;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrometheusLaravelRouteMiddleware
{
    private const NONE_ERROR = "NONE";
    private const BUCKETS = [
        0,
        0.005,
        0.01,
        0.025,
        0.05,
        0.075,
        0.1,
        0.25,
        0.5,
        0.75,
        1,
        1.5,
        2,
        2.5,
        5,
        7.5,
        10,
        20,
        30,
        40,
        50,
        60
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
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

        $labels = $this->getLabels($request, $response);

        $executionCounter = $exporter->getOrRegisterNamelessCounter(
            'execution_count',
            'Counter of system execution',
            ['owner', 'domain', 'system', 'component', 'operation', 'error', 'error_class']
        );
        $executionCounter->inc($labels);

        $latencyHistogram = $exporter->getOrRegisterNamelessHistogram(
            'execution_latency_seconds',
            'Latency os system execution in seconds',
            ['owner', 'domain', 'system', 'component', 'operation', 'error', 'error_class'],
            self::BUCKETS
        );

        $latencyHistogram->observe(
            $duration,
            $labels
        );

        return $response;
    }

    public function getMatchedRoute(Request $request)
    {
        $routeCollection = RouteFacade::getRoutes();
        return $routeCollection->match($request);
    }

    private function getLabels(Request $request, Response $response): array
    {
        return array_merge(
            $this->getConfigLabels(),
            $this->getComponentOperationLabels($request),
            $this->getErrorLabels($response)
        );
    }

    private function getConfigLabels(): array
    {
        return [
            'owner' => config('prometheus.standard_metrics.owner'),
            'domain' => config('prometheus.standard_metrics.domain'),
            'system' => config('prometheus.standard_metrics.system'),
        ];
    }

    private function getComponentOperationLabels(Request $request): array
    {
        $route = $this->getMatchedRoute($request);
        $controllerAction = $route->getActionName();
        $component = class_basename(explode('@', $controllerAction)[0]);
        $operation = explode('@', $controllerAction)[1] ?? null;

        return [
            'component' => $component,
            'operation' => $operation
        ];
    }

    private function getErrorLabels(Response $response): array
    {
        $error = self::NONE_ERROR;
        $errorClass = self::NONE_ERROR;

        if ($response->isClientError() || $response->isServerError()) {
            $errorClass = (string)$response->getStatusCode();
            $error = Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown error';
        }

        return [
            'error' => $error,
            'error_class' => $errorClass,
        ];
    }
}
