<?php

namespace JDesrosiers\Silex\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Swagger\Logger;
use Swagger\Swagger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The SwaggerServiceProvider adds a swagger-php service to a silex app.  It also adds the routes necessary for
 * integrating with swagger-ui.
 */
class SwaggerServiceProvider implements ServiceProviderInterface
{
    /**
     * Add routes to the app that generate swagger documentation based on your annotations
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
        AnnotationRegistry::registerAutoloadNamespace("Swagger\Annotations", $app["swagger.srcDir"]);

        if ($app["logger"]) {
            $logger = Logger::getInstance();
            $originalLog = $logger->log;
            $logger->log = function ($entry, $type) use ($app, $originalLog) {
                $app["logger"]->notice($entry);
                $originalLog($entry, $type);
            };
        }

        $app->get($app["swagger.apiDocPath"], array($this, "getResourceList"));
        $app->get("{$app["swagger.apiDocPath"]}/{service}", array($this, "getResourceDefinition"));
    }

    /**
     * Registers the swagger service
     *
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app["swagger.apiDocPath"] = "/api/api-docs";
        $app["swagger.excludePath"] = array();
        $app["swagger.prettyPrint"] = true;
        $app["swagger.cache"] = array();
        $app["swagger.basePath"] = null;
        $app["swagger.apiVersion"] = null;
        $app["swagger.swaggerVersion"] = "1.2";
        $app["swagger.resourcePrefix"] = "/";
        $app["swagger.resourceSuffix"] = "";

        $app["swagger"] = $app->share(
            function (Application $app) {
                return new Swagger($app["swagger.servicePath"], $app["swagger.excludePath"]);
            }
        );
    }

    /**
     * Route for GETting the resource list
     *
     * @param Application $app
     * @param Request $request
     *
     * @return Response
     */
    public function getResourceList(Application $app, Request $request)
    {
        $options = array(
            "output" => "json",
            "json_pretty_print" => $app["swagger.prettyPrint"],
            'basePath' => $app["swagger.basePath"],
            "prefix" => $app["swagger.resourcePrefix"],
            "suffix" => $app["swagger.resourceSuffix"],
            "apiVersion" => $app["swagger.apiVersion"],
            "swaggerVersion" => $app["swagger.swaggerVersion"],
        );
        $json = $app["swagger"]->getResourceList($options);

        $response = Response::create($json, 200, array("Content-Type" => "application/json"));
        $response->setCache($app["swagger.cache"]);
        $response->setEtag(md5($json));
        $response->isNotModified($request);

        return $response;
    }

    /**
     * Route for GETting each of the resource definitions
     *
     * @param Application $app
     * @param Request $request
     * @param string $service
     *
     * @return Response
     * @throws NotFoundHttpException
     */
    public function getResourceDefinition(Application $app, Request $request, $service)
    {
        $resourceName = "/" . str_replace("-", "/", $service);
        $resourceNames = $app["swagger"]->getResourceNames();
        if (!in_array($resourceName, $resourceNames)) {
            $resourceNamesDisplay = implode('", "', $resourceNames);
            throw new NotFoundHttpException("Resource \"$resourceName\" not found, try \"$resourceNamesDisplay\"");
        }

        $options = array(
            "output" => "json",
            "json_pretty_print" => $app["swagger.prettyPrint"],
            "defaultBasePath" => $app["swagger.basePath"],
            "defaultApiVersion" => $app["swagger.apiVersion"],
            "defaultSwaggerVersion" => $app["swagger.swaggerVersion"],
        );
        $json = $app["swagger"]->getResource($resourceName, $options);

        $response = Response::create($json, 200, array("Content-Type" => "application/json"));
        $response->setCache($app["swagger.cache"]);
        $response->setEtag(md5($json));
        $response->isNotModified($request);

        return $response;
    }
}
