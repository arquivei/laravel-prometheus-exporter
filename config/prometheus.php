<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace to use as a prefix for all metrics.
    |
    | This will typically be the name of your project, eg: 'search'.
    |
    */

    'namespace' => env('PYR_NAMESPACE', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Enabled?
    |--------------------------------------------------------------------------
    |
    | If enabled, a /metrics route will be registered to export prometheus
    | metrics.
    |
    */

    'metrics_route_enabled' => env('PYR_METRICS_ROUTE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Metrics Route Path
    |--------------------------------------------------------------------------
    |
    | The path at which prometheus metrics are exported.
    |
    | This is only applicable if metrics_route_enabled is set to true.
    |
    */

    'metrics_route_path' => env('PYR_METRICS_ROUTE_PATH', 'metrics'),

    /*
    |--------------------------------------------------------------------------
    | Storage Adapter
    |--------------------------------------------------------------------------
    |
    | The storage adapter to use.
    |
    | Supported: "memory", "redis", "apc"
    |
    */

    'storage_adapter' => env('PYR_STORAGE_ADAPTER', 'memory'),

    /*
    |--------------------------------------------------------------------------
    | Storage Adapters
    |--------------------------------------------------------------------------
    |
    | The storage adapter configs.
    |
    */

    'storage_adapters' => [

        'redis' => [
            'host' => env('PYR_REDIS_HOST', 'localhost'),
            'port' => env('PYR_REDIS_PORT', 6379),
            'database' => env('PYR_REDIS_DATABASE', 0),
            'timeout' => env('PYR_REDIS_TIMEOUT', 0.1),
            'read_timeout' => env('PYR_REDIS_READ_TIMEOUT', 10),
            'persistent_connections' => env('PYR_REDIS_PERSISTENT_CONNECTIONS', false),
            'prefix' => env('PYR_REDIS_PREFIX', 'PYR_'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Collect full SQL query
    |--------------------------------------------------------------------------
    |
    | Indicates whether we should collect the full SQL query or not.
    |
    */

    'collect_full_sql_query' => env('PYR_COLLECT_FULL_SQL_QUERY', true),

    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | The collectors specified here will be auto-registered in the exporter.
    |
    */

    'collectors' => [
        // \Your\ExporterClass::class,
    ],
];
