<?php

namespace JDesrosiers\Silex\Provider\Exclude\Test;

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
    public function run()
    {

    }
}
