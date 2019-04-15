<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use PDO;
use PDOException;

class DatabaseCreate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates a new database';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'db:create';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $defaultConnection = config('database.default');
        $database = config("database.connections.{$defaultConnection}.database");
        $host = config("database.connections.{$defaultConnection}.host");
        $port = config("database.connections.{$defaultConnection}.port");
        $username = config("database.connections.{$defaultConnection}.username");
        $password = config("database.connections.{$defaultConnection}.password");

        if (App::environment() === 'production') {
            $this->error('Command supports only local and testing environments!');

            return;
        }

        if (!$database) {
            $this->info('Skipping creation of database as env(DB_DATABASE) is empty');

            return;
        }

        try {
            $pdo = $this->getPDOConnection($host, $port, $username, $password);

            $res = $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS %s',
                $database
            ));

            if ($res) {
                $this->info(sprintf('Successfully created %s database', $database));

                return;
            }
            throw new PDOException;
        } catch (PDOException $exception) {
            $this->error(sprintf('Failed to create %s database, %s', $database, $exception->getMessage()));
        }
    }

    /**
     * @param string $host
     * @param int    $port
     * @param string $username
     * @param string $password
     *
     * @return PDO
     */
    private function getPDOConnection($host, $port, $username, $password)
    {
        return new PDO(sprintf('mysql:host=%s;port=%d;', $host, $port), $username, $password);
    }
}
