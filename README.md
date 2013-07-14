silex-swagger-provider
======================

[![Build Status](https://travis-ci.org/jdesrosiers/silex-swagger-provider.png)](https://travis-ci.org/jdesrosiers/silex-swagger-provider)

[silex-swagger-provider](https://github.com/jdesrosiers/silex-swagger-provider) is a silex service provider that integrates [swagger-php](https://github.com/zircote/swagger-php) into [silex](https://github.com/fabpot/Silex).  This service provider adds routes for generating and exposing a swagger defintion based on swagger-php annotations.  The swagger definition can then be used with [swagger-ui](https://github.com/wordnik/swagger-ui).

Parameters
----------
* **swagger.srcDir**: The path to the swagger-php component.
* **swagger.servicePath**: The path to the classes that contain your swagger annotations.
* **swagger.excludePath**: A colon `:` separated list of paths to be excluded when generating annotations.
* **swagger.apiDocPath**: The URI that will be used to access the swagger definition. Defaults to `/api-docs.json`.
* **swagger.prettyPrint**: Determines whether or not the JSON generated will be formatted for human readability.

Services
--------
* **swagger**: An instance of Swagger\Swagger.  It's used internally to generate the swagger definition.  You probably won't need to use it directly.

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
