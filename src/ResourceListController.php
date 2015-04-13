<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResourceListController
{
    /**
     * Route for GETting the resource list
     *
     * @param Application $app
     * @param Request $request
     *
     * @return Response
     */
    public function __invoke(Application $app, Request $request)
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
}