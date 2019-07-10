<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot() : void
    {
        DB::listen(function ($query) {
            $querySql = $this->cleanupSqlString((string)$query->sql);
            $type = strtoupper(strtok($querySql, ' '));
            $labels = array_values(array_filter([
                config('prometheus.collect_full_sql_query') ? $querySql : null,
                $type
            ]));
            $this->app->get('prometheus.sql.histogram')->observe($query->time, $labels);
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register() : void
    {
        $this->app->singleton('prometheus.sql.histogram', function ($app) {
            return $app['prometheus']->getOrRegisterHistogram(
                'sql_query_duration',
                'SQL query duration histogram',
                array_values(array_filter([
                    config('prometheus.collect_full_sql_query') ? 'query' : null,
                    'query_type'
                ]))
            );
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
            'prometheus.sql.histogram',
        ];
    }

    /**
     * Cleans the SQL string for registering the metric.
     * Removes repetitive question marks and simplifies "VALUES" clauses.
     *
     * @return string
     */
    private function cleanupSqlString(string $sql): string
    {
        $sql = preg_replace('/(VALUES\s*)(\(.*?\),?)+/i', '$1()', $sql);
        $sql = preg_replace('/(\s*\?\s*,?\s*){2,}/i', '?', $sql);
        $sql = str_replace('"', '', $sql);

        return $sql;
    }
}
