silex-swagger-provider
======================

[![Build Status](https://travis-ci.org/jdesrosiers/silex-swagger-provider.png?branch=master)](https://travis-ci.org/jdesrosiers/silex-swagger-provider)
[![Scrutinizer Continuous Inspections](https://scrutinizer-ci.com/g/jdesrosiers/silex-swagger-provider/badges/general.png?s=28b0cb934469ff164179fd1bc5909b18635aaffc)](https://scrutinizer-ci.com/g/jdesrosiers/silex-swagger-provider/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/jdesrosiers/silex-swagger-provider/badges/quality-score.png?s=b8a3f40bdf416ccc53fcff296fc3bd00ea5e3f48)](https://scrutinizer-ci.com/g/jdesrosiers/silex-swagger-provider/)
[![Code Coverage](https://scrutinizer-ci.com/g/jdesrosiers/silex-swagger-provider/badges/coverage.png?s=5df2c7fb18f7a0e2d7cef52ab9661810b6b45d6b)](https://scrutinizer-ci.com/g/jdesrosiers/silex-swagger-provider/)

[silex-swagger-provider](https://github.com/jdesrosiers/silex-swagger-provider) is a silex service provider that
integrates [swagger-php](https://github.com/zircote/swagger-php) into [silex](https://github.com/fabpot/Silex).  This
service provider adds routes for generating and exposing a swagger defintion based on swagger-php annotations.  The
swagger definition can then be used with [swagger-ui](https://github.com/wordnik/swagger-ui).

Installation
------------
Install the silex-swagger-provider using [composer](http://getcomposer.org/).  This project uses [sematic versioning](http://semver.org/).

```json
{
    "require": {
        "jdesrosiers/silex-swagger-provider": "~1.0"
    }
}
```

Parameters
----------
* **swagger.srcDir**: The path to the swagger-php component.
* **swagger.servicePath**: The path to the classes that contain your swagger annotations.
* **swagger.excludePath**: A colon `:` separated list of paths to be excluded when generating annotations.
* **swagger.apiDocPath**: The URI that will be used to access the swagger definition. Defaults to `/api/api-docs`.
* **swagger.prettyPrint**: Determines whether or not the JSON generated will be formatted for human readability.
* **swagger.cache**: An array of caching options that will be passed to Symfony 2's `Response::setCache` method.
* **swagger.basePath**: The url where your API can be found. If your Swagger annotation contains a basePath it will override this value. Eg. "http://api.example.com/
* **swagger.apiVersion**: The version of your API. If your Swagger annotation contains a version it will override this value.
* **swagger.swaggerVersion**: The Swagger version of your API. If your Swagger annotation contains a swagger version it will override this value.
* **swagger.resourcePrefix**: A prefix string that will be appended for every resource URI. Defaults to "/".
* **swagger.resourceSuffix**: A suffix string that will be appended for every resource URI. Defaults to "".

Services
--------
* **swagger**: An instance of `Swagger\Swagger`.  It's used internally to generate the swagger definition.  You probably
won't need to use it directly.

Registering
-----------
```php
$app->register(new JDesrosiers\Silex\Provider\SwaggerServiceProvider(), array(
    "swagger.srcDir" => __DIR__ . "/vendor/zircote/swagger-php/library",
    "swagger.servicePath" => __DIR__ . "/path/to/your/api",
));
```
Usage
-----
The following routes are made available by default
* `GET /api/api-docs`: Get the list of resources
* `GET /api/api-docs/{service}`: Get the definition for a specific resource

The results of the swagger definition file is not cached internally.  Instead, the routes that are created are designed
to work with an HTTP cache such as a browser cache or reverse proxy.  You can configure how you want to your service
cached using the `swagger.cache` parameter.  By default, no caching will be done.  Read about
[HTTP caching](http://symfony.com/doc/current/book/http_cache.html) in Symfony for more information about how to
customize caching behavior.  The following example will allow the service definition file to be cached for 5 days.

```php
$app["swagger.cache"] = array(
    "max_age": "432000", // 5 days in seconds
    "s_maxage": "432000", // 5 days in seconds
    "public": true,
)
```

Logging
-------
Swagger uses php notices and warnings to log issues it encounters when trying to generate your API documentation.  If
your silex application has a `logger` service, it will log those issues as well.

Getting Started
---------------
The following is a minimal example to get you started quickly.  It uses the [jdesrosiers/silex-cors-provider](https://github.com/jdesrosiers/silex-cors-provider)
to allow us to use the demo installation of swagger-ui so we don't have to stand up our own.  See the
[swagger-php documentation](http://zircote.com/swagger-php/) for details on how to expand on this example.

* Create a composer.json with at minimum, the following dependecies

```json
{
    "require": {
        "jdesrosiers/silex-swagger-provider": "~1.0",
        "jdesrosiers/silex-cors-provider": "~0.1"
    }
}
```
* Run composer install
* Create /web/index.php

```php
<?php

use Swagger\Annotations as SWG;

require __DIR__ . "/../vendor/autoload.php";

/**
 * @SWG\Resource(basePath="http://localhost:8000", resourcePath="/foo")
 */
$app = new Silex\Application();
$app["debug"] = true;

$app->register(new JDesrosiers\Silex\Provider\SwaggerServiceProvider(), array(
    "swagger.srcDir" => __DIR__ . "/../vendor/zircote/swagger-php/library",
    "swagger.servicePath" => __DIR__ . "/",
));

$app->register(new JDesrosiers\Silex\Provider\CorsServiceProvider(), array(
    "cors.allowOrigin" => "http://petstore.swagger.wordnik.com",
));

/**
 * @SWG\Api(path="/foo", @SWG\Operations(@SWG\Operation(method="GET", nickname="foo")))
 */
$app->get('/foo', function () use ($app) {
    return 'bar';
});

$app->after($app["cors"]);

$app->run();
```
* Run the service `php -S localhost:8000 -t web web/index.php`
* Go to http://petstore.swagger.wordnik.com and put `http://localhost:8000/api/api-docs` in the top input field and click `Explore`
