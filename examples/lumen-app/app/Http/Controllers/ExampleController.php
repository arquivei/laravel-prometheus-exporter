<?php

namespace App\Http\Controllers;

use App\Models\Test;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

/**
 * Class ExampleController
 * @package App\Http\Controllers
 * This class contains the example route used
 * for testing purposes in this application
 */
class ExampleController extends Controller
{
    /**
     * Route used to test anything that requires
     * a working action.
     *
     * @throws Exception
     */
    public function test()
    {
        // Flush table with Test models
        Test::getQuery()->delete();

        /* @var \GuzzleHttp\Client $client The prometheus example client from lib */
        $client = app('prometheus.guzzle.client');

        $response = $client->get('https://jsonplaceholder.typicode.com/todos');

        // Parse
        $dec = json_decode($response->getBody(), true);

        // What follows are model operations resulting in SQL queries
        // Each query should be logged and the metrics applicable exported
        foreach ($dec as $item) {
            // Create model and save
            /** @var Test $test */
            $test = new Test();
            $test->id = null;
            $test->userId = $item['userId'];
            $test->title = $item['title'];
            $test->save();

            //Update model
            $test = Test::find($test->id);
            $test->title = 'Test string';
            $test->save();

            // Delete
            $test->delete();
        }

        // Count rows and dump the result
        dd(count($dec), count(Test::all()));
    }
}
