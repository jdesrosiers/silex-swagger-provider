<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceDefinitionController
{
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
    public function __invoke(Application $app, Request $request, $service)
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