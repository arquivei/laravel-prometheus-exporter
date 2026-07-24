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
            $querySql = '[omitted]';
            $type = strtoupper(strtok((string)$query->sql, ' '));
            if (config('prometheus.collect_full_sql_query')) {
                $querySql = $this->cleanupSqlString((string)$query->sql);
            }
            $labels = array_values(array_filter([
                $querySql,
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
                    'query',
                    'query_type'
                ])),
                config('prometheus.sql_buckets') ?? null
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
     * @param string $sql
     * @return string
     */
    private function cleanupSqlString(string $sql): string
    {
        // 1. Replace all string literals (single or double quoted) with ?
        $sql = preg_replace("/'[^']*'/", "?", $sql);
        $sql = preg_replace('/"[^"]*"/', "?", $sql);

        // 2. Replace all numbers (integers, decimals, negative values) with ?
        $sql = preg_replace('/\b\d+(\.\d+)?\b/', "?", $sql);

        // 3. Normalize IN (...) lists to a single placeholder
        $sql = preg_replace('/IN\s*\([^)]+\)/i', "IN (?)", $sql);

        // 4. Normalize VALUES (...) lists to a single placeholder
        $sql = preg_replace('/(VALUES\s*)\([^)]+\)(\s*,\s*\([^)]+\))*/i', 'VALUES (?)', $sql);

        // 5. Collapse multiple placeholders (?, ?, ?) into a single ?
        $sql = preg_replace('/(\?\s*,\s*)+/', "?", $sql);

        // 6. Normalize whitespace
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        // 7. Return normalized SQL or [error] if something went wrong
        return empty($sql) ? '[error]' : strtolower($sql);
    }
}
