<?php

namespace Arquivei\LaravelPrometheusExporter;

use Closure;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Prometheus\Histogram;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrometheusLumenFastRouteMiddleware extends PrometheusLumenRouteMiddleware
{
    public function getMatchedRoute(Request $request)
    {

        try {
            return parent::getMatchedRoute($request);
        } catch (NotFoundHttpException $exception) {
            //start working in there is an exception from the parent
        }


        $method = $request->getMethod();
        $method = $method == 'HEAD' ? 'GET' : $method;
        $uri = $request->getRequestUri();


        foreach (RouteFacade::getRoutes() as $route) {
            /** @var \FastRoute\RouteCollector $routeCollector */
            $routeCollector = new RouteCollector(new Std(), new GroupCountBased());

            if($route['method'] != $method) {
                continue;
            }

            $routeCollector->addRoute($route['method'], $route['uri'], $route['action']);
            list($staticRouteMap, $variableRouteData) = $routeCollector->getData();

            if(!empty($variableRouteData)) {
                foreach ($variableRouteData[$method] as $data) {
                    if (!preg_match($data['regex'], $uri, $matches)) {
                        continue;
                    }
                }

                return new Route(
                    $route['method'],
                    $route['uri'],
                    $route['action']
                );
            }
        }


        return new Route(
            $request->getMethod(),
            'unknonwn',
            $route['action']
        );
    }
}
