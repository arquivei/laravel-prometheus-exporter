<?php

declare(strict_types = 1);

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Taxibeat\Pyr\StorageAdapterFactory;

/**
 * @covers \Taxibeat\Pyr\StorageAdapterFactory<extended>
 */
class StorageAdapterFactoryTest extends TestCase
{
    /**
     * @var StorageAdapterFactory
     */
    private $factory;

    public function setUp() : void
    {
        parent::setUp();
        $this->factory = new StorageAdapterFactory();
    }

    public function testMakeMemoryAdapter() : void
    {
        $adapter = $this->factory->make('memory');
        $this->assertInstanceOf(InMemory::class, $adapter);
    }

    public function testMakeApcAdapter() : void
    {
        $adapter = $this->factory->make('apc');
        $this->assertInstanceOf(APC::class, $adapter);
    }

    public function testMakeRedisAdapter() : void
    {
        $adapter = $this->factory->make('redis');
        $this->assertInstanceOf(Redis::class, $adapter);
    }

    public function testMakeInvalidAdapter() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The driver [moo] is not supported.');
        $this->factory->make('moo');
    }
}
