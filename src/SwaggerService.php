<?php

namespace JDesrosiers\Silex\Provider;

use Silex\Application;
use Swagger\Swagger;

class SwaggerService
{
    public function __invoke(Application $app)
    {
        return new Swagger($app["swagger.servicePath"], $app["swagger.excludePath"]);
    }
}
