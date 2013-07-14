<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\SwaggerServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class SwaggerServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function dataProviderApiDocs()
    {
        return array(
            array("/api-docs.json", "/foo", "/resources/foo.json", "/resources/baz.json"),
            array("/api-docs.json", "/foo/bar", "/resources/foo-bar.json", "/resources/baz.json"),
            array("/api/api-docs.json", "/foo", "/api/resources/foo.json", "/api/resources/baz.json"),
            array("/api/api-docs.json", "/foo/bar", "/api/resources/foo-bar.json", "/api/resources/baz.json"),
        );
    }

    /**
     * @dataProvider dataProviderApiDocs
     */
    public function testApiDocs($apiDocPath, $resource, $resourcePath, $excludePath)
    {
        $app = new Application();
        $app->register(new SwaggerServiceProvider(), array(
            "swagger.srcDir" => __DIR__ . "/../../../../../vendor/zircote/swagger-php/library",
            "swagger.servicePath" => __DIR__,
            "swagger.excludePath" => __DIR__ . "/Exclude",
            "swagger.apiDocPath" => $apiDocPath,
        ));

        // Test resource list
        $client = new Client($app);
        $client->request("GET", $apiDocPath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($app["swagger"]->getResourceList($app["swagger.prettyPrint"]), $response->getContent());

        // Test resource
        $client = new Client($app);
        $client->request("GET", $resourcePath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($app["swagger"]->getResource($resource, $app["swagger.prettyPrint"]), $response->getContent());

        // Test excluded resource
        $client = new Client($app);
        $client->request("GET", $excludePath);
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }
}
