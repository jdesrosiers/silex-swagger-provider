<?php

namespace JDesrosiers\Silex\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Swagger\Swagger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SwaggerServiceProvider implements ServiceProviderInterface
{
    public function boot(Application $app)
    {
        AnnotationRegistry::registerAutoloadNamespace("Swagger\Annotations", $app["swagger.srcDir"]);

        $app->get($app["swagger.apiDocPath"], function () use ($app) {
            $json = $app["swagger"]->getResourceList();
            if ($json === false) {
                throw new HttpException(500, "Failed to retrieve swagger api definition file");
            }

            return new Response($json, 200, array("Content-Type" => "application/json"));
        });

        $app->get(dirname($app["swagger.apiDocPath"]) . "/resources/{service}.json", function ($service) use ($app) {
            $json = $app["swagger"]->getResource("/" . str_replace("-", "/", $service));
            if ($json === false) {
                throw new HttpException(500, "Failed to retrieve swagger api definition file");
            }

            return new Response($json, 200, array("Content-Type" => "application/json"));
        });
    }

    public function register(Application $app)
    {
        $app["swagger.apiDocPath"] = "/api-docs.json";

        $app["swagger"] = $app->share(function (Application $app) {
            return Swagger::discover($app["swagger.servicePath"]);
        });
    }    
}
