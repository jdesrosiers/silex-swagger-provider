<?php

namespace JDesrosiers\Tests\Silex\Provider;

use Swagger\Annotations as SWG;

/**
 * @SWG\Resource(
 *     apiVersion="0.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/foo",
 *     basePath="http://localhost:8000"
 * )
 */
class Foo
{
    /**
     * @SWG\Api(
     *     path="/foo",
     *     @SWG\Operations(
     *         @SWG\Operation(method="GET", type="FooModel")
     *     )
     * )
     */
    public function foo()
    {

    }
}
