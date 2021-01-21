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
                        // Check https://github.com/illuminate/queue/blob/1cdd2c3bd3a74ef35709b5e2cea75e63c7299fa4/Worker.php#L199
                        // to see what status is assigned based on why worker is stopping
                        $errorCode = isset($event->status) ? $event->status : -1;
                        if ($errorCode == 1) {
                            // Worker exiting because timeout specified in --timeout has been crossed
                            // Setting code as -1 for timeout exceeded
                            $errorCode = -1;
                            // Setting status as timeout for timeout exceeded
                            $status = "timeout";
                        } else if ($errorCode == 12) {
                            // Worker exiting because memory specified in --memory has been crossed
                            // Setting code as -2 for memory exceeded
                            $errorCode = -2;
                            // Setting status as memory_exceeded for memory exceeded
                            $status = "memory_exceeded";
                        } else {
                            // Worker exiting because of other reasons
                            $errorCode = isset($event->exception) ? $event->exception->getCode() : -3;
                            $status = "other";
                        }
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
                        $status = "unknown_status";
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