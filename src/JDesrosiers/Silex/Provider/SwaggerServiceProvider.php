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
                $app["logger"]->warning($entry);
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
        $app["swagger.excludePath"] = null;
        $app["swagger.prettyPrint"] = true;
        $app["swagger.cache"] = array();

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
        $list = $app["swagger"]->getResourceList($app["swagger.prettyPrint"], false);

        // swagger-php doesn't yet have a way to build resources correctly for swagger-ui 2.0, so we need to
        // make some modifications manually.  This should be refactored when swagger-php can do it for us.
        $apis = array();
        foreach ($list["apis"] as $api) {
            $matches = array();
            if (preg_match('/^\/resources(\/.+)\.\{format\}$/', $api["path"], $matches)) {
                $api["path"] = $matches[1];
            }

            $apis[] = $api;
        }
        $list["apis"] = $apis;

        $json = $app["swagger"]->jsonEncode($list, $app["swagger.prettyPrint"]);

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
        if (!in_array($resourceName, $app["swagger"]->getResourceNames())) {
            throw new NotFoundHttpException("No such swagger definition");
        }

        $json = $app["swagger"]->getResource($resourceName, $app["swagger.prettyPrint"]);

        $response = Response::create($json, 200, array("Content-Type" => "application/json"));
        $response->setCache($app["swagger.cache"]);
        $response->setEtag(md5($json));
        $response->isNotModified($request);

        return $response;
    }
}
