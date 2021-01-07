<?php


namespace Arquivei\LaravelPrometheusExporter;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\WorkerStopping;
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
            JobExceptionOccurred::class,
            WorkerStopping::class
        ], function ($event) {

            $status = "success";
            $errorCode = 0;

            try {
                switch(true){
                    case $event instanceof JobProcessed:
                        $status = "success";
                        $errorCode = 0;
                        break;
                    case $event instanceof WorkerStopping:
                        $status = "timeout";
                        $errorCode = isset($event->exception) ? $event->exception->getCode() : -1;
                        break;
                    case $event instanceof JobFailed:
                        $status = "failed";
                        $errorCode = isset($event->exception) ? $event->exception->getCode() : -1;
                        break;
                    case $event instanceof JobExceptionOccurred:
                        $status = "exception";
                        $errorCode = isset($event->exception) ? $event->exception->getCode() : -1;
                        break;
                }

                $histogram = app('prometheus.workers.client.histogram');
                $histogram->observe(
                    microtime(true) - self::$starts,
                    [
                        $event->job->resolveName(),
                        $event->job->getQueue(),
                        $event->connectionName,
                        $errorCode,
                        $status,
                    ]
                );
            } catch (\Throwable $e) {
                //fail silently
            }
        });
    }
}