<?php

namespace JDesrosiers\Tests\Silex\Provider\Exclude;

use Swagger\Annotations as SWG;

/**
 * @SWG\Resource(
 *     apiVersion="0.1",
 *     swaggerVersion="1.1",
 *     resourcePath="/baz",
 *     basePath="http://localhost:8000"
 * )
 */
class Baz
{
    /**
     * @SWG\Api(
     *     path="/baz",
     *     @SWG\Operations(
     *         @SWG\Operation(httpMethod="GET", responseClass="BazModel")
     *     )
     * )
     */
    public function baz()
    {

    }
}
