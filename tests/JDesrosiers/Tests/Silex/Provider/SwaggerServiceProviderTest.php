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
            array("/api-docs.json", "/resources/foo.json"),
            array("/api/api-docs.json", "/api/resources/foo.json"),
        );
    }

    /**
     * @dataProvider dataProviderApiDocs
     */
    public function testApiDocs($apiDocPath, $resourcePath)
    {
        $app = new Application();
        $app->register(new SwaggerServiceProvider(), array(
            "swagger.srcDir" => __DIR__ . "/../../../../../vendor/zircote/swagger-php/library",
            "swagger.servicePath" => __DIR__,
            "swagger.apiDocPath" => $apiDocPath,
        ));

        $client = new Client($app);
        $client->request("GET", $apiDocPath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($app["swagger"]->getResourceList(), $response->getContent());

        $client = new Client($app);
        $client->request("GET", $resourcePath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($app["swagger"]->getResource("/foo"), $response->getContent());
    }
}
