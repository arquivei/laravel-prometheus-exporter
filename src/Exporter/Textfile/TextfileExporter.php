<?php

namespace Arquivei\LaravelPrometheusExporter\Exporter\Textfile;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class TextfileExporter
{
    private $job;
    private $path;

    public function __construct(string $job, string $path)
    {
        $this->job = $job;
        $this->path = $path;
    }

    public function publish(CollectorRegistry $collectorRegistry): void
    {
        $renderer = new RenderTextFormat();
        $body = $renderer->render($collectorRegistry->getMetricFamilySamples());

        $filePath = sprintf('%s/%s.prom', $this->path, $this->job);

        if (!file_put_contents($filePath, $body)) {
            throw new \Exception(sprintf('Unable to save the file %s on disk', $filePath));
        }
    }
}