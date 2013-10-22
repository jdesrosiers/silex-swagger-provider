<?php

namespace JDesrosiers\Tests\Silex\Provider\Exclude;

use Swagger\Annotations as SWG;

/**
 * @SWG\Resource(
 *     apiVersion="0.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/baz",
 *     basePath="http://localhost:8000"
 * )
 */
class Baz
{
    public function baz()
    {

    }
}
