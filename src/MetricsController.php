<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter;

use Illuminate\Routing\Controller;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

class MetricsController extends Controller
{
    /**
     * @var PrometheusExporter
     */
    protected $prometheusExporter;

    /**
     * @param PrometheusExporter $prometheusExporter
     */
    public function __construct(PrometheusExporter $prometheusExporter)
    {
        $this->prometheusExporter = $prometheusExporter;
    }

    /**
     * GET /metrics
     *
     * The route path is configurable in the prometheus.metrics_route_path config var, or the
     * PROMETHEUS_METRICS_ROUTE_PATH env var.
     *
     * @return Response
     */
    public function getMetrics() : Response
    {
        $metrics = $this->prometheusExporter->export();

        $renderer = new RenderTextFormat();
        $result = $renderer->render($metrics);

        return response($result, 200, ['Content-Type' => RenderTextFormat::MIME_TYPE]);
    }
}
