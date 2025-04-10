<?php

namespace Arquivei\LaravelPrometheusExporter;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\HttpFoundation\Request;

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
