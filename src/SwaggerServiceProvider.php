<?php

namespace JDesrosiers\Silex\Provider;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Swagger\Logger;

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

        $app->get($app["swagger.apiDocPath"], new ResourceListController());
        $app->get("{$app["swagger.apiDocPath"]}/{service}", new ResourceDefinitionController());
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

        $app["swagger"] = $app->share(new SwaggerService());
    }
}
