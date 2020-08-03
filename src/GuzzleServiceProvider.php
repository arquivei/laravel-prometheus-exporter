<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\ServiceProvider;

class GuzzleServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register() : void
    {
        $this->app->singleton('prometheus.guzzle.client.histogram', function ($app) {
            return $app['prometheus']->getOrRegisterHistogram(
                'guzzle_response_duration',
                'Guzzle response duration histogram',
                ['method', 'external_endpoint', 'status_code'],
                config('prometheus.guzzle_buckets') ?? null
            );
        });
        $this->app->singleton('prometheus.guzzle.handler', function ($app) {
            return new CurlHandler();
        });
        $this->app->singleton('prometheus.guzzle.middleware', function ($app) {
            return new GuzzleMiddleware($app['prometheus.guzzle.client.histogram']);
        });
        $this->app->singleton('prometheus.guzzle.handler-stack', function ($app) {
            $stack = HandlerStack::create($app['prometheus.guzzle.handler']);
            $stack->push($app['prometheus.guzzle.middleware']);
            return $stack;
        });
        $this->app->singleton('prometheus.guzzle.client', function ($app) {
            return new Client(['handler' => $app['prometheus.guzzle.handler-stack']]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() : array
    {
        return [
            'prometheus.guzzle.client',
            'prometheus.guzzle.handler-stack',
            'prometheus.guzzle.middleware',
            'prometheus.guzzle.handler',
            'prometheus.guzzle.client.histogram',
        ];
    }
}
