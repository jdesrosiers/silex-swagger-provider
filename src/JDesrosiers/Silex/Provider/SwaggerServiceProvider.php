<?php

namespace JDesrosiers\Silex\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Swagger\Swagger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SwaggerServiceProvider implements ServiceProviderInterface
{
    public function boot(Application $app)
    {
        AnnotationRegistry::registerAutoloadNamespace("Swagger\Annotations", $app["swagger.srcDir"]);

        $app->get($app["swagger.apiDocPath"], function () use ($app) {
            $json = $app["swagger"]->getResourceList($app["swagger.prettyPrint"]);

            return new Response($json, 200, array("Content-Type" => "application/json"));
        });

        $app->get(dirname($app["swagger.apiDocPath"]) . "/resources/{service}.json", function ($service) use ($app) {
            $resourceName = "/" . str_replace("-", "/", $service);
            if (!in_array($resourceName, $app["swagger"]->getResourceNames())) {
                throw new NotFoundHttpException("No such swagger definition");
            }

            $json = $app["swagger"]->getResource($resourceName, $app["swagger.prettyPrint"]);

            return new Response($json, 200, array("Content-Type" => "application/json"));
        });
    }

    public function register(Application $app)
    {
        $app["swagger.apiDocPath"] = "/api-docs.json";
        $app["swagger.excludePath"] = null;
        $app["swagger.prettyPrint"] = true;

        $app["swagger"] = $app->share(function (Application $app) {
            return new Swagger($app["swagger.servicePath"], $app["swagger.excludePath"]);
        });
    }    
}
