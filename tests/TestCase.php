<?php

declare(strict_types = 1);

namespace Tests;

use Arquivei\LaravelPrometheusExporter\DatabaseServiceProvider;
use Arquivei\LaravelPrometheusExporter\GuzzleServiceProvider;
use Arquivei\LaravelPrometheusExporter\PrometheusServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DatabaseServiceProvider::class,
            GuzzleServiceProvider::class,
            PrometheusServiceProvider::class,
       	];
    }
}
