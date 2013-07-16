<?php

namespace JDesrosiers\Silex\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Swagger\Swagger;
use Swagger\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SwaggerServiceProvider implements ServiceProviderInterface
{
    public function boot(Application $app)
    {
        AnnotationRegistry::registerAutoloadNamespace("Swagger\Annotations", $app["swagger.srcDir"]);

        if (class_exists("Swagger\Logger") && $app["logger"]) {
            $logger = Logger::getInstance();
            $originalLog = $logger->log;
            $logger->log = function ($entry, $type) use ($app, $originalLog) {
                switch ($type) {
                    case E_USER_NOTICE:
                        $app["logger"]->warning($entry);
                        break;

                    case E_USER_WARNING:
                        $app["logger"]->error($entry);
                        break;
                }

                $originalLog($entry, $type);
            };
        }

        $app->get($app["swagger.apiDocPath"], function (Request $request) use ($app) {
            $json = $app["swagger"]->getResourceList($app["swagger.prettyPrint"]);

            $response = new Response($json, 200, array("Content-Type" => "application/json"));
            $response->setCache($app["swagger.cache"]);
            $response->setEtag(md5($json));
            $response->isNotModified($request);

            return $response;
        });

        $app->get(dirname($app["swagger.apiDocPath"]) . "/resources/{service}.json", function (Request $request, $service) use ($app) {
            $resourceName = "/" . str_replace("-", "/", $service);
            if (!in_array($resourceName, $app["swagger"]->getResourceNames())) {
                throw new NotFoundHttpException("No such swagger definition");
            }

            $json = $app["swagger"]->getResource($resourceName, $app["swagger.prettyPrint"]);

            $response = new Response($json, 200, array("Content-Type" => "application/json"));
            $response->setCache($app["swagger.cache"]);
            $response->setEtag(md5($json));
            $response->isNotModified($request);

            return $response;
        });
    }

    public function register(Application $app)
    {
        $app["swagger.apiDocPath"] = "/api-docs.json";
        $app["swagger.excludePath"] = null;
        $app["swagger.prettyPrint"] = true;
        $app["swagger.cache"] = array();

        $app["swagger"] = $app->share(function (Application $app) {
            return new Swagger($app["swagger.servicePath"], $app["swagger.excludePath"]);
        });
    }
}
