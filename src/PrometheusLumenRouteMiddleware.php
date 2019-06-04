<?php

namespace Arquivei\LaravelPrometheusExporter;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Prometheus\Histogram;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrometheusLumenRouteMiddleware extends PrometheusLaravelRouteMiddleware
{
    public function getMatchedRoute(Request $request)
    {
        $routeCollection = new RouteCollection();
        $routes = RouteFacade::getRoutes();

        foreach ($routes as $route) {
            $routeCollection->add(
                new Route(
                    $route['method'],
                    $route['uri'],
                    $route['action']
                )
            );
        }
        return $routeCollection->match($request);
    }
}
