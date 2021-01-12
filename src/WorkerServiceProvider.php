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

    private $starts;
    private $jobName;
    private $queueName;
    private $connectionName;

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
        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            $this->starts = microtime(true);
            $this->jobName = $event->job->resolveName();
            $this->queueName = $event->job->getQueue();
            $this->connectionName = $event->connectionName;
        });

        $this->app['events']->listen([
            JobProcessed::class,
            JobFailed::class,
            JobExceptionOccurred::class,
            WorkerStopping::class
        ], function ($event) {

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
                    default:
                        $status = "unkown_status";
                        $errorCode = -1;
                }

                $histogram = app('prometheus.workers.client.histogram');
                $histogram->observe(
                    microtime(true) - $this->starts,
                    [
                        $this->jobName,
                        $this->queueName,
                        $this->connectionName,
                        $errorCode,
                        $status,
                    ]
                );
            } catch(\Throwable $e) {
                // fail silently and don't stop the application
                $eventName = get_class($event);
                \Log::error("Failed while processing job event $eventName: ".$e->getMessage());
            }
        });
    }
}