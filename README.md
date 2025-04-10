# Laravel (and Lumen) Prometheus Exporter

A prometheus exporter package for Laravel and Lumen.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Introduction

Prometheus is a time-series database with a UI and sophisticated querying language (PromQL) that can scrape metrics, counters, gauges and histograms over HTTP.

This package is a wrapper bridging [jimdo/prometheus_client_php](https://github.com/jimdo/prometheus_client_php) into Laravel and Lumen.

## Example

Head to [examples/lumen-app](https://github.com/arquivei/laravel-prometheus-exporter/tree/example-application/examples/lumen-app)
to check out our awesome example application.
To get it you'll have to clone the [Laravel Prometheus Exporter](https://github.com/arquivei/laravel-prometheus-exporter/) repo, as the example
is not included when downloaded from composer.

The example is a full project containing it's own `README.md` so you can check the
library's functionality and the way it's intended to be used.


## Installation

Add the repository to composer.json
```composer.json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/arquivei/laravel-prometheus-exporter"
  }
],
```

Install the package via composer
```bash
composer require arquivei/laravel-prometheus-exporter
```

After that you may enable facades and register the facade in your application's `bootstrap/app.php`
```php
$userAliases = [
    // ...
    Arquivei\LaravelPrometheusExporter\PrometheusFacade::class => 'Prometheus',
];
$app->withFacades(true, $userAliases);
```

Then you should register the service provider in `bootstrap/app.php`
```php
$app->register(Arquivei\LaravelPrometheusExporter\PrometheusServiceProvider::class);
```

Please see below for instructions on how to enable metrics on Application routes, Guzzle calls and SQL queries.

## Configuration

The package has a default configuration which uses the following environment variables.
```
PROMETHEUS_NAMESPACE=app

PROMETHEUS_METRICS_ROUTE_ENABLED=true
PROMETHEUS_METRICS_ROUTE_PATH=metrics
PROMETHEUS_METRICS_ROUTE_MIDDLEWARE=null
PROMETHEUS_COLLECT_FULL_SQL_QUERY=true
PROMETHEUS_STORAGE_ADAPTER=memory

PROMETHEUS_REDIS_HOST=localhost
PROMETHEUS_REDIS_PORT=6379
PROMETHEUS_REDIS_TIMEOUT=0.1
PROMETHEUS_REDIS_READ_TIMEOUT=10
PROMETHEUS_REDIS_PERSISTENT_CONNECTIONS=0
PROMETHEUS_REDIS_PREFIX=PROMETHEUS_
```

To customize the configuration values you can either override the environment variables above (usually this is done in your application's `.env` file), or you can copy the included [prometheus.php](config/prometheus.php)
to `config/prometheus.php`, edit it and use it in your application as follows:
```php
$app->loadComponent('prometheus', [
    Arquivei\LaravelPrometheusExporter\PrometheusServiceProvider::class
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
    Arquivei\LaravelPrometheusExporter\RouteMiddleware::class,
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
$app->register(Arquivei\LaravelPrometheusExporter\GuzzleServiceProvider::class);
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
$app->register(Arquivei\LaravelPrometheusExporter\DatabaseServiceProvider::class);
```

The labels exported are

```php
[
    'query',
    'query_type',
]
```

Note: you can disable logging the full query by turning off the configuration of `PROMETHEUS_COLLECT_FULL_SQL_QUERY`.

### Standard metrics

When using the Arquivei\LaravelPrometheusExporter\PrometheusLaravelRouteMiddleware
middleware, there are two metrics that get exported automatically:

- execution_count: number of executions
- execution_latency_seconds: latency of executions in seconds

The execution_count metric is a counter.

The execution_latency_seconds metric is a histogram with the following values as buckets:

- 0,
- 0.005,
- 0.01,
- 0.025,
- 0.05,
- 0.075,
- 0.1,
- 0.25,
- 0.5,
- 0.75,
- 1,
- 1.5,
- 2,
- 2.5,
- 5,
- 7.5,
- 10,
- 20,
- 30,
- 40,
- 50,
- 60

Both metrics have the following labels:

- owner: person or team responsible for the system
- domain: domain of the system
- system: system's name
- component: name of the controller that handled the request
- operation: method inside the controller that handled the request (action)
- error: error message, if any, NONE otherwise
- error_class: HTTP status text, if any errors, NONE otherwise

Labels not set will be exported as an empty string.

Owner, domain, and system can be set by the following environment variables:

- PROMETHEUS_STANDARD_METRICS_OWNER for owner
- PROMETHEUS_STANDARD_METRICS_DOMAIN for domain
- PROMETHEUS_STANDARD_METRICS_SYSTEM for system

Or using the config/prometheus.php file:

- standard_metrics.owner for owner
- standard_metrics.domain for domain
- standard_metrics.system for system

### Storage Adapters

The storage adapter is used to persist metrics across requests.  The `memory` adapter is enabled by default, meaning
data will only be persisted across the current request.

We recommend using the `redis` or `apc` adapter in production
environments. Of course your installation has to provide a Redis or APC implementation.

The `PROMETHEUS_STORAGE_ADAPTER` environment variable is used to specify the storage adapter.

If `redis` is used, the `PROMETHEUS_REDIS_HOST` and `PROMETHEUS_REDIS_PORT` vars also need to be configured. Optionally you can change the `PROMETHEUS_REDIS_TIMEOUT`, `PROMETHEUS_REDIS_READ_TIMEOUT` and `PROMETHEUS_REDIS_PERSISTENT_CONNECTIONS` variables.

## Exporting Metrics

The package adds a `/metrics` endpoint, enabled by default, which exposes all metrics gathered by collectors.

This can be turned on/off using the `PROMETHEUS_METRICS_ROUTE_ENABLED` environment variable,
and can also be changed using the `PROMETHEUS_METRICS_ROUTE_PATH` environment variable.

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
$exporter = app(\Arquivei\LaravelPrometheusExporter\PrometheusExporter::class);

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

namespace Arquivei\LaravelPrometheusExporter;

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
