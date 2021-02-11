<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter\Tests;

use Arquivei\LaravelPrometheusExporter\DatabaseServiceProvider;
use Arquivei\LaravelPrometheusExporter\PrometheusExporter;
use Arquivei\LaravelPrometheusExporter\PrometheusServiceProvider;
use Arquivei\LaravelPrometheusExporter\Tests\Fixture\MetricSamplesSpec;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;

/**
 * @covers \Arquivei\LaravelPrometheusExporter\DatabaseServiceProvider<extended>
 */
class DatabaseServiceProviderTest extends TestCase
{
    private $createdTable = false;

    public function testServiceProviderWithDefaultConfig() : void
    {
        $this->createTestTable();

        /* @var \Prometheus\Histogram $histogram */
        $histogram = $this->app->get('prometheus.sql.histogram');
        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertSame(['query', 'query_type'], $histogram->getLabelNames());
        $this->assertSame('app_sql_query_duration', $histogram->getName());
        $this->assertSame('SQL query duration histogram', $histogram->getHelp());

        /* @var PrometheusExporter $prometheus */
        $prometheus = $this->app->get('prometheus');
        $export = $prometheus->export();

        $this->assertContainsSamplesMatching(
            $export,
            MetricSamplesSpec::create()
                ->withName('app_sql_query_duration')
                ->withLabelNames(['query', 'query_type'])
                ->withHelp('SQL query duration histogram')
        );
    }

    public function testServiceProviderWithoutCollectingFullSqlQueries()
    {
        $this->app->get('config')->set('prometheus.collect_full_sql_query', false);
        $this->createTestTable();

        /* @var \Prometheus\Histogram $histogram */
        $histogram = $this->app->get('prometheus.sql.histogram');
        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertSame(['query', 'query_type'], $histogram->getLabelNames());

        /* @var PrometheusExporter $prometheus */
        $prometheus = $this->app->get('prometheus');
        $export = $prometheus->export();
        $this->assertContainsSamplesMatching(
            $export,
            MetricSamplesSpec::create()
                ->withLabelNames(['query', 'query_type'])
        );
    }

    protected function createTestTable()
    {
        $this->createdTable = false;
        Schema::connection('test')->create('test', function($table)
        {
            $table->increments('id');
            $table->timestamps();
            $this->createdTable = true;
        });

        while (!$this->createdTable) {
            continue;
        }
    }

    protected function getPackageProviders($app) : array
    {
        return [
            PrometheusServiceProvider::class,
            DatabaseServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function assertContainsSamplesMatching(array $samples, MetricSamplesSpec $spec, int $count = 1): void
    {
        $matched = array_filter($samples, [$spec, 'matches']);
        $this->assertCount($count, $matched);
    }
}
