<?php

declare(strict_types = 1);

namespace Beat\Pyr;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot() : void
    {
        $this->publishes([
            __DIR__ . '/../config/prometheus.php' => $this->configPath('prometheus.php'),
        ]);
        $this->loadRoutes();
        /* @var PrometheusExporter $exporter */
        $exporter = $this->app->make(PrometheusExporter::class);
        foreach (config('prometheus.collectors') as $class) {
            $collector = $this->app->make($class);
            $exporter->registerCollector($collector);
        }
    }

    /**
     * Register bindings in the container.
     */
    public function register() : void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/prometheus.php', 'prometheus');

        $this->app->singleton(PrometheusExporter::class, function ($app) {
            $adapter = $app['prometheus.storage_adapter'];
            $prometheus = new CollectorRegistry($adapter);

            return new PrometheusExporter(config('prometheus.namespace'), $prometheus);
        });
        $this->app->alias(PrometheusExporter::class, 'prometheus');

        $this->app->bind('prometheus.storage_adapter_factory', function () {
            return new StorageAdapterFactory();
        });

        $this->app->bind(Adapter::class, function ($app) {
            /* @var StorageAdapterFactory $factory */
            $factory = $app['prometheus.storage_adapter_factory'];
            $driver = config('prometheus.storage_adapter');
            $configs = config('prometheus.storage_adapters');
            $config = Arr::get($configs, $driver, []);

            return $factory->make($driver, $config);
        });
        $this->app->alias(Adapter::class, 'prometheus.storage_adapter');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() : array
    {
        return [
            'prometheus',
            'prometheus.storage_adapter',
            'prometheus.storage_adapter_factory',
        ];
    }

    private function loadRoutes()
    {
        if (!config('prometheus.metrics_route_enabled')) {
            return;
        }

        $router = $this->app['router'];

        /** @var Route $route */
        $isLumen = str_contains($this->app->version(), 'Lumen');
        if ($isLumen) {
            $router->get(
                config('prometheus.metrics_route_path'),
                [
                    'as' => 'metrics',
                    'uses' => MetricsController::class . '@getMetrics',
                ]
            );
        } else {
            $router->get(
                config('prometheus.metrics_route_path'),
                MetricsController::class . '@getMetrics'
            )->name('metrics');
        }
    }

    private function configPath($path) : string
    {
        return $this->app->basePath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
