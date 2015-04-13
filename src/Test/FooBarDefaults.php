<?php

namespace JDesrosiers\Silex\Provider\Test;

use Swagger\Annotations as SWG;

/**
 * @SWG\Resource(
 * )
 */
class FooBarDefaults
{
    /**
     * @SWG\Api(
     *     path="/foo/bar/defaults",
     *     @SWG\Operations(
     *         @SWG\Operation(method="GET", type="FooBarDefaultsModel")
     *     )
     * )
     */
    public function fooBarDefaults()
    {

    }
}
