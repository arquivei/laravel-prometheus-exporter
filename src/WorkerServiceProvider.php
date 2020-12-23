<?php


namespace Arquivei\LaravelPrometheusExporter;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Support\ServiceProvider;


class WorkerServiceProvider extends ServiceProvider
{

    public static $starts;

    /**
     *
     * register
     *
     * @access public
     */
    public function register() : void
    {
        $this->app->singleton('prometheus.workers.client.histogram', function ($app) {
            return $app['prometheus']->getOrRegisterHistogram(
                'worker_run_duration',
                'workers run time histogram',
                ['name', 'queue', 'connection', 'error_code', 'success']
            );
        });
    }

    /**
     *
     * boot
     *
     * @access public
     */
    public function boot()
    {
        $start;

        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            self::$starts = microtime(true);
        });

        $this->app['events']->listen([
            JobProcessed::class,
            JobFailed::class,
            JobExceptionOccurred::class
        ], function ($event) {
            $status = $success = ($event instanceof JobProcessed);
            if(!$status) {
                $status = ($event instanceof JobFailed) ? 0 : -1;
            }
            try{
                $histogram = app('prometheus.workers.client.histogram');
                $histogram->observe(
                    microtime(true) - self::$starts,
                    [
                        $event->job->resolveName(),
                        $event->job->getQueue(),
                        $event->connectionName,
                        $success ? 0 : $event->exception->getCode(),
                        (int) $status //1 : success, 0: fail, -1: exception
                    ]
                );
            } catch (\Throwable $e) {
                //fail silently
            }
        });
    }
}