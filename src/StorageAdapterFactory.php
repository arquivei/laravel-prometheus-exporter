<?php

declare(strict_types = 1);

namespace Arquivei\LaravelPrometheusExporter;

use InvalidArgumentException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class StorageAdapterFactory
{
    private $hostname;

    public function __construct()
    {
        $this->hostname = gethostname() ?? '';
    }

    /**
     * Factory a storage adapter.
     *
     * @param string $driver
     * @param array  $config
     *
     * @return Adapter
     */
    public function make(string $driver, array $config = []) : Adapter
    {
        switch ($driver) {
            case 'memory':
                return new InMemory();
            case 'redis':
                return $this->makeRedisAdapter($config);
            case 'apc':
                return new APC();
        }

        throw new InvalidArgumentException(sprintf('The driver [%s] is not supported.', $driver));
    }

    /**
     * Factory a redis storage adapter.
     *
     * @param array $config
     *
     * @return Redis
     */
    protected function makeRedisAdapter(array $config) : Redis
    {
        if (isset($config['prefix'])) {
            $prefix = !empty($config['prefix_dynamic']) ? sprintf('%s_%s_', $config['prefix'], $this->hostname) : $config['prefix'];
            Redis::setPrefix($prefix);
        }

        return new Redis($config);
    }
}
