<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Arquivei\LaravelPrometheusExporter\CollectorInterface;
use Arquivei\LaravelPrometheusExporter\PrometheusExporter;

class PrometheusExporterTest extends TestCase
{
    public function testConstruct() : void
    {
        $registry = Mockery::mock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);
        $this->assertEquals('app', $exporter->getNamespace());
        $this->assertSame($registry, $exporter->getPrometheus());
    }

    public function testConstructWithCollectors() : void
    {
        $collector1 = Mockery::mock(CollectorInterface::class);
        $collector1->shouldReceive('getName')
            ->once()
            ->andReturn('users');
        $collector1->shouldReceive('registerMetrics')
            ->once()
            ->with(Mockery::type(PrometheusExporter::class));
        $collector2 = Mockery::mock(CollectorInterface::class);
        $collector2->shouldReceive('getName')
            ->once()
            ->andReturn('search_requests');
        $collector2->shouldReceive('registerMetrics')
            ->once()
            ->with(Mockery::type(PrometheusExporter::class));

        $registry = Mockery::mock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry, [$collector1, $collector2]);

        $collectors = $exporter->getCollectors();
        $this->assertCount(2, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertArrayHasKey('search_requests', $collectors);
        $this->assertSame($collector1, $collectors['users']);
        $this->assertSame($collector2, $collectors['search_requests']);
    }

    public function testRegisterCollector() : void
    {
        $registry = Mockery::mock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $this->assertEmpty($exporter->getCollectors());

        $collector = Mockery::mock(CollectorInterface::class);
        $collector->shouldReceive('getName')
            ->once()
            ->andReturn('users');
        $collector->shouldReceive('registerMetrics')
            ->once()
            ->with($exporter);

        $exporter->registerCollector($collector);

        $collectors = $exporter->getCollectors();
        $this->assertCount(1, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertSame($collector, $collectors['users']);
    }

    public function testRegisterCollectorWhenCollectorIsAlreadyRegistered() : void
    {
        $registry = Mockery::mock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $this->assertEmpty($exporter->getCollectors());

        $collector = Mockery::mock(CollectorInterface::class);
        $collector->shouldReceive('getName')
            ->andReturn('users');
        $collector->shouldReceive('registerMetrics')
            ->once()
            ->with($exporter);

        $exporter->registerCollector($collector);

        $collectors = $exporter->getCollectors();
        $this->assertCount(1, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertSame($collector, $collectors['users']);

        $exporter->registerCollector($collector);

        $collectors = $exporter->getCollectors();
        $this->assertCount(1, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertSame($collector, $collectors['users']);
    }

    public function testGetCollector() : void
    {
        $registry = Mockery::mock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $this->assertEmpty($exporter->getCollectors());

        $collector = Mockery::mock(CollectorInterface::class);
        $collector->shouldReceive('getName')
            ->once()
            ->andReturn('users');
        $collector->shouldReceive('registerMetrics')
            ->once()
            ->with($exporter);

        $exporter->registerCollector($collector);

        $c = $exporter->getCollector('users');
        $this->assertSame($collector, $c);
    }

    public function testGetCollectorWhenCollectorIsNotRegistered() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The collector "test" is not registered.');

        $registry = Mockery::mock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $exporter->getCollector('test');
    }

    public function testRegisterCounter() : void
    {
        $counter = Mockery::mock(Counter::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('registerCounter')
            ->once()
            ->withArgs([
                'app',
                'search_requests_total',
                'The total number of search requests.',
                ['request_type'],
            ])
            ->andReturn($counter);

        $exporter = new PrometheusExporter('app', $registry);

        $c = $exporter->registerCounter(
            'search_requests_total',
            'The total number of search requests.',
            ['request_type']
        );
        $this->assertSame($counter, $c);
    }

    public function testGetCounter() : void
    {
        $counter = Mockery::mock(Counter::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('getCounter')
            ->once()
            ->withArgs([
                'app',
                'search_requests_total',
            ])
            ->andReturn($counter);

        $exporter = new PrometheusExporter('app', $registry);

        $c = $exporter->getCounter('search_requests_total');
        $this->assertSame($counter, $c);
    }

    public function testGetOrRegisterCounter() : void
    {
        $counter = Mockery::mock(Counter::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('getOrRegisterCounter')
            ->once()
            ->withArgs([
                'app',
                'search_requests_total',
                'The total number of search requests.',
                ['request_type'],
            ])
            ->andReturn($counter);

        $exporter = new PrometheusExporter('app', $registry);

        $c = $exporter->getOrRegisterCounter(
            'search_requests_total',
            'The total number of search requests.',
            ['request_type']
        );
        $this->assertSame($counter, $c);
    }

    public function testRegisterGauge() : void
    {
        $gauge = Mockery::mock(Gauge::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('registerGauge')
            ->once()
            ->withArgs([
                'app',
                'users_online_total',
                'The total number of users online.',
                ['group'],
            ])
            ->andReturn($gauge);

        $exporter = new PrometheusExporter('app', $registry);

        $g = $exporter->registerGauge(
            'users_online_total',
            'The total number of users online.',
            ['group']
        );
        $this->assertSame($gauge, $g);
    }

    public function testGetGauge() : void
    {
        $gauge = Mockery::mock(Gauge::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('getGauge')
            ->once()
            ->withArgs([
                'app',
                'users_online_total',
            ])
            ->andReturn($gauge);

        $exporter = new PrometheusExporter('app', $registry);

        $g = $exporter->getGauge('users_online_total');
        $this->assertSame($gauge, $g);
    }

    public function testGetOrRegisterGauge() : void
    {
        $gauge = Mockery::mock(Gauge::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('getOrRegisterGauge')
            ->once()
            ->withArgs([
                'app',
                'users_online_total',
                'The total number of users online.',
                ['group'],
            ])
            ->andReturn($gauge);

        $exporter = new PrometheusExporter('app', $registry);

        $g = $exporter->getOrRegisterGauge(
            'users_online_total',
            'The total number of users online.',
            ['group']
        );
        $this->assertSame($gauge, $g);
    }

    public function testRegisterHistogram() : void
    {
        $histogram = Mockery::mock(Histogram::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('registerHistogram')
            ->once()
            ->withArgs([
                'app',
                'response_time_seconds',
                'The response time of a request.',
                ['request_type'],
                [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0],
            ])
            ->andReturn($histogram);

        $exporter = new PrometheusExporter('app', $registry);

        $h = $exporter->registerHistogram(
            'response_time_seconds',
            'The response time of a request.',
            ['request_type'],
            [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
        );
        $this->assertSame($histogram, $h);
    }

    public function testGetHistogram() : void
    {
        $histogram = Mockery::mock(Histogram::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('getHistogram')
            ->once()
            ->withArgs([
                'app',
                'response_time_seconds',
            ])
            ->andReturn($histogram);

        $exporter = new PrometheusExporter('app', $registry);

        $h = $exporter->getHistogram('response_time_seconds');
        $this->assertSame($histogram, $h);
    }

    public function testGetOrRegisterHistogram() : void
    {
        $histogram = Mockery::mock(Histogram::class);

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('getOrRegisterHistogram')
            ->once()
            ->withArgs([
                'app',
                'response_time_seconds',
                'The response time of a request.',
                ['request_type'],
                [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0],
            ])
            ->andReturn($histogram);

        $exporter = new PrometheusExporter('app', $registry);

        $h = $exporter->getOrRegisterHistogram(
            'response_time_seconds',
            'The response time of a request.',
            ['request_type'],
            [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
        );
        $this->assertSame($histogram, $h);
    }

    public function testExport() : void
    {
        $samples = ['meh'];

        $registry = Mockery::mock(CollectorRegistry::class);
        $registry->shouldReceive('getMetricFamilySamples')
            ->once()
            ->andReturn($samples);

        $exporter = new PrometheusExporter('app', $registry);

        $collector1 = Mockery::mock(CollectorInterface::class);
        $collector1->shouldReceive('getName')
            ->once()
            ->andReturn('users');
        $collector1->shouldReceive('registerMetrics')
            ->once()
            ->with($exporter);
        $collector1->shouldReceive('collect')
            ->once();

        $exporter->registerCollector($collector1);

        $collector2 = Mockery::mock(CollectorInterface::class);
        $collector2->shouldReceive('getName')
            ->once()
            ->andReturn('search_requests');
        $collector2->shouldReceive('registerMetrics')
            ->once()
            ->with($exporter);
        $collector2->shouldReceive('collect')
            ->once();

        $exporter->registerCollector($collector2);

        $s = $exporter->export();
        $this->assertSame($samples, $s);
    }
}
