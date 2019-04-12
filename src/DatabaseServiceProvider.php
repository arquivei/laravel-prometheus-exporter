<?php

declare(strict_types = 1);

namespace Beat\Pyr;

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
            $type = strtoupper(strtok($query->sql, ' '));
            $labels = array_values(array_filter([
                config('prometheus.collect_full_sql_query') ? $query->sql : null,
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
                'mysql_query_duration',
                'MySQL query duration histogram',
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
}
