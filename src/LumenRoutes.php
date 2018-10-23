<?php

$route = Route::get(
    config('prometheus.metrics_route_path'),
    \Taxibeat\LaravelPrometheusExporter\MetricsController::class . '@getMetrics'
); /** @var \Laravel\Lumen\Routing */
$middleware = config('prometheus.metrics_route_middleware');

if ($middleware) {
    $route->middleware($middleware);
}
