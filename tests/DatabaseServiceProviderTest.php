<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;

/**
 * @covers \Beat\Pyr\DatabaseServiceProvider<extended>
 */
class DatabaseServiceProviderTest extends TestCase
{
    public function testServiceProviderWithDefaultConfig() : void
    {
        $this->createTestTable();

        /* @var \Prometheus\Histogram $histogram */
        $histogram = $this->app->get('prometheus.sql.histogram');
        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertSame(['query', 'query_type'], $histogram->getLabelNames());
        $this->assertSame('app_mysql_query_duration', $histogram->getName());
        $this->assertSame('MySQL query duration histogram', $histogram->getHelp());

        /* @var PrometheusExporter $prometheus */
        $prometheus = $this->app->get('prometheus');
        $export = $prometheus->export();
        $this->assertCount(1, $export);

        /* @var \Prometheus\MetricFamilySamples $samples */
        $samples = $export[0];
        $this->assertInstanceOf(MetricFamilySamples::class, $samples);
        $this->assertSame(['query', 'query_type'], $samples->getLabelNames());
        $this->assertSame('app_mysql_query_duration', $samples->getName());
        $this->assertSame('MySQL query duration histogram', $samples->getHelp());
    }

    public function testServiceProviderWithoutCollectingFullSqlQueries()
    {
        $this->app->get('config')->set('prometheus.collect_full_sql_query', false);
        $this->createTestTable();

        /* @var \Prometheus\Histogram $histogram */
        $histogram = $this->app->get('prometheus.sql.histogram');
        $this->assertInstanceOf(Histogram::class, $histogram);
        $this->assertSame(['query_type'], $histogram->getLabelNames());

        /* @var PrometheusExporter $prometheus */
        $prometheus = $this->app->get('prometheus');
        $export = $prometheus->export();
        $this->assertCount(1, $export);

        /* @var \Prometheus\MetricFamilySamples $samples */
        $samples = $export[0];
        $this->assertInstanceOf(MetricFamilySamples::class, $samples);
        $this->assertSame(['query_type'], $samples->getLabelNames());
    }

    protected function createTestTable()
    {
        Schema::connection('test')->create('test', function($table)
        {
            $table->increments('id');
            $table->timestamps();
        });
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
}
