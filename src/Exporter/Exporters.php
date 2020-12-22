<?php

namespace Arquivei\LaravelPrometheusExporter\Exporter;

use Arquivei\LaravelPrometheusExporter\Exporter\PushGateway\PushGateway;
use Arquivei\LaravelPrometheusExporter\Exporter\Textfile\TextfileExporter;

class Exporters
{
    private $pushGateway;
    private $textfile;

    public function __construct(array $config)
    {
        $job = $config['job'];

        $this->pushGateway = new PushGateway(
            $job,
            $config['push_gateway']['address']
        );

        $this->textfile = new TextfileExporter(
            $job,
            $config['textfile']['path']
        );
    }

    public function getPushGateway(): PushGateway
    {
        return $this->pushGateway;
    }

    public function getTextfile(): TextfileExporter
    {
        return $this->textfile;
    }
}