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
        "jdesrosiers/silex-swagger-provider": "~0.1"
    }
}
```

Parameters
----------
* **swagger.srcDir**: The path to the swagger-php component.
* **swagger.servicePath**: The path to the classes that contain your swagger annotations.
* **swagger.excludePath**: A colon `:` separated list of paths to be excluded when generating annotations.
* **swagger.apiDocPath**: The URI that will be used to access the swagger definition. Defaults to `/api-docs.json`.
* **swagger.prettyPrint**: Determines whether or not the JSON generated will be formatted for human readability.
* **swagger.cache**: An array of caching options that will be passed to Symfony 2's `Response::setCache` method.

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
* `GET /api-docs.json`: Get the list of resources
* `GET /resources/{service}.json`: Get the definition for a specific resource

The results of the swagger definition file is not cached internally.  Instead, the routes that are created are designed
to work with an HTTP cache such as a browser cache or reverse proxy.  You can configure how you want to your service
cached using the `swagger.cache` parameter.  By default, no caching will be done.  Read about
[HTTP caching](http://symfony.com/doc/current/book/http_cache.html) in Symfony for more information about how to
customize caching behavior.  The following example will allow the service definition file to be cached for 5 days.

```php
$app["swagger.cache"] = array(
    "max_age": "7200", // 5 days in minutes
    "s_maxage": "7200", // 5 days in minutes
    "public": true,
)
```

Logging
-------
Swagger uses php notices and warnings to log issues it encounters when trying to generate your API documentation.  If
your silex application has a `logger` service, it will log those issues as well.
