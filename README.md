# Pyr

A prometheus exporter package for Lumen and Laravel.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Introduction

Prometheus is a time-series database with a UI and sophisticated querying language (PromQL) that can scrape metrics, counters, gauges and histograms over HTTP.

This package is a wrapper bridging [thebeatapp/prometheus_client_php](https://github.com/thebeatapp/prometheus_client_php) (a fork of [jimdo/prometheus_client_php](https://github.com/jimdo/prometheus_client_php)) into Lumen and Laravel.

## Installation

Add the repository to composer.json
```composer.json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/thebeatapp/pyr"
  }
],
```

Install the package via composer
```bash
composer require thebeatapp/pyr
```

After that you may enable facades and register the facade in your application's `bootstrap/app.php`
```php
$userAliases = [
    // ...
    Taxibeat\Pyr\PrometheusFacade::class => 'Prometheus',
];
$app->withFacades(true, $userAliases);
```

Then you should register the service provider in `bootstrap/app.php`
```php
$app->register(Taxibeat\Pyr\PrometheusServiceProvider::class);
```

Please see below for instructions on how to enable metrics on Application routes, Guzzle calls and SQL queries.

## Configuration

The package has a default configuration which uses the following environment variables.
```
PYR_NAMESPACE=app

PYR_METRICS_ROUTE_ENABLED=true
PYR_METRICS_ROUTE_PATH=metrics
PYR_METRICS_ROUTE_MIDDLEWARE=null
PYR_COLLECT_FULL_SQL_QUERY=true
PYR_STORAGE_ADAPTER=memory

PYR_REDIS_HOST=localhost
PYR_REDIS_PORT=6379
PYR_REDIS_TIMEOUT=0.1
PYR_REDIS_READ_TIMEOUT=10
PYR_REDIS_PERSISTENT_CONNECTIONS=0
PYR_REDIS_PREFIX=PYR_
```

To customize the configuration values you can either override the environment variables above (usually this is done in your application's `.env` file), or you can copy the included [prometheus.php](config/prometheus.php)
to `config/prometheus.php`, edit it and use it in your application as follows:
```php
$app->loadComponent('prometheus', [
    Taxibeat\Pyr\PrometheusServiceProvider::class
]);
```

## Metrics

The package allows you to observe metrics on:

* Application routes. Metrics on request method, request path and status code.
* Guzzle calls. Metrics on request method, URI and status code.
* SQL queries. Metrics on SQL query and query type.

In order to observe metrics in application routes (the time between a request and response),
you should register the following middleware in your application's `bootstrap/app.php`:
```php
$app->middleware([
    Taxibeat\Pyr\RouteMiddleware::class,
]);
```

The labels exported are

```php
[
    'method',
    'route',
    'status_code',
]
```

To observe Guzzle metrics, you should register the following provider in `bootstrap/app.php`:
```php
$app->register(Taxibeat\Pyr\GuzzleServiceProvider::class);
```

The labels exported are

```php
[
    'method',
    'external_endpoint',
    'status_code'
]
```

To observe SQL metrics, you should register the following provider in `bootstrap/app.php`:
```php
$app->register(Taxibeat\Pyr\DatabaseServiceProvider::class);
```

The labels exported are

```php
[
    'query',
    'query_type',
]
```

Note: you can disable logging the full query by turning off the configuration of `PYR_COLLECT_FULL_SQL_QUERY`.

### Storage Adapters

The storage adapter is used to persist metrics across requests.  The `memory` adapter is enabled by default, meaning
data will only be persisted across the current request.

We recommend using the `redis` or `apc` adapter in production
environments. Of course your installation has to provide a Redis or APC implementation.

The `PYR_STORAGE_ADAPTER` environment variable is used to specify the storage adapter.

If `redis` is used, the `PYR_REDIS_HOST` and `PYR_REDIS_PORT` vars also need to be configured. Optionally you can change the `PYR_REDIS_TIMEOUT`, `PYR_REDIS_READ_TIMEOUT` and `PYR_REDIS_PERSISTENT_CONNECTIONS` variables.

## Exporting Metrics

The package adds a `/metrics` endpoint, enabled by default, which exposes all metrics gathered by collectors.

This can be turned on/off using the `PYR_METRICS_ROUTE_ENABLED` environment variable,
and can also be changed using the `PYR_METRICS_ROUTE_PATH` environment variable.

## Collectors

A collector is a class, implementing the [CollectorInterface](src/CollectorInterface.php), which is responsible for
collecting data for one or many metrics.

Please see the [Example](#Collector) included below.

You can auto-load your collectors by adding them to the `collectors` array in the `prometheus.php` config.

## Examples

### Example usage

This is an example usage for a Lumen application

```php
// retrieve the exporter (you can also use app('prometheus') or Prometheus::getFacadeRoot())
$exporter = app(\Taxibeat\Pyr\PrometheusExporter::class);

// register a new collector
$collector = new \My\New\Collector();
$exporter->registerCollector($collector);

// retrieve all collectors
var_dump($exporter->getCollectors());

// retrieve a collector by name
$collector = $exporter->getCollector('user');

// export all metrics
// this is called automatically when the /metrics end-point is hit
var_dump($exporter->export());

// the following methods can be used to create and interact with counters, gauges and histograms directly
// these methods will typically be called by collectors, but can be used to register any custom metrics directly,
// without the need of a collector

// create a counter
$counter = $exporter->registerCounter('search_requests_total', 'The total number of search requests.');
$counter->inc(); // increment by 1
$counter->incBy(2);

// create a counter (with labels)
$counter = $exporter->registerCounter('search_requests_total', 'The total number of search requests.', ['request_type']);
$counter->inc(['GET']); // increment by 1
$counter->incBy(2, ['GET']);

// retrieve a counter
$counter = $exporter->getCounter('search_requests_total');

// create a gauge
$gauge = $exporter->registerGauge('users_online_total', 'The total number of users online.');
$gauge->inc(); // increment by 1
$gauge->incBy(2);
$gauge->dec(); // decrement by 1
$gauge->decBy(2);
$gauge->set(36);

// create a gauge (with labels)
$gauge = $exporter->registerGauge('users_online_total', 'The total number of users online.', ['group']);
$gauge->inc(['staff']); // increment by 1
$gauge->incBy(2, ['staff']);
$gauge->dec(['staff']); // decrement by 1
$gauge->decBy(2, ['staff']);
$gauge->set(36, ['staff']);

// retrieve a gauge
$counter = $exporter->getGauge('users_online_total');

// create a histogram
$histogram = $exporter->registerHistogram(
    'response_time_seconds',
    'The response time of a request.',
    [],
    [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
);
// the buckets must be in asc order
// if buckets aren't specified, the default 0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0 buckets will be used
$histogram->observe(5.0);

// create a histogram (with labels)
$histogram = $exporter->registerHistogram(
    'response_time_seconds',
    'The response time of a request.',
    ['request_type'],
    [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
);
// the buckets must be in asc order
// if buckets aren't specified, the default 0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0 buckets will be used
$histogram->observe(5.0, ['GET']);

// retrieve a histogram
$counter = $exporter->getHistogram('response_time_seconds');
```

### Collector

This is an example collector implementation:

```php
<?php

declare(strict_types = 1);

namespace Taxibeat\Pyr;

use Prometheus\Gauge;

class ExampleCollector implements CollectorInterface
{
    /**
     * @var Gauge
     */
    protected $usersRegisteredGauge;

    /**
     * Return the name of the collector.
     *
     * @return string
     */
    public function getName() : string
    {
        return 'users';
    }

    /**
     * Register all metrics associated with the collector.
     *
     * The metrics needs to be registered on the exporter object.
     * eg:
     * ```php
     * $exporter->registerCounter('search_requests_total', 'The total number of search requests.');
     * ```
     *
     * @param PrometheusExporter $exporter
     */
    public function registerMetrics(PrometheusExporter $exporter) : void
    {
        $this->usersRegisteredGauge = $exporter->registerGauge(
            'users_registered_total',
            'The total number of registered users.',
            ['group']
        );
    }

    /**
     * Collect metrics data, if need be, before exporting.
     *
     * As an example, this may be used to perform time consuming database queries and set the value of a counter
     * or gauge.
     */
    public function collect() : void
    {
        // retrieve the total number of staff users registered
        // eg: $totalUsers = Users::where('group', 'staff')->count();
        $this->usersRegisteredGauge->set(36, ['staff']);

        // retrieve the total number of regular users registered
        // eg: $totalUsers = Users::where('group', 'regular')->count();
        $this->usersRegisteredGauge->set(192, ['regular']);
    }
}
```
