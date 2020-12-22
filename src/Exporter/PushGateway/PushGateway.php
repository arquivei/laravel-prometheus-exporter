<?php

namespace Arquivei\LaravelPrometheusExporter\Exporter\PushGateway;

use Arquivei\LaravelPrometheusExporter\Exporter\PushGateway\Client\ClientFactory;
use Prometheus\CollectorRegistry;

class PushGateway extends \Prometheus\PushGateway
{
    private $job;
    private $instance;

    public function __construct(string $job, string $address)
    {
        parent::__construct($address, ClientFactory::create());
        $this->job = $job;
        $this->instance = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function publish(CollectorRegistry $collectorRegistry, array $groupingKey = []): void
    {
        $groupingKey['instance'] = $this->instance;
        parent::push($collectorRegistry, $this->job, $groupingKey);
    }
}