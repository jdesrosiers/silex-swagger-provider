<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\SwaggerServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class SwaggerServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->app->register(new SwaggerServiceProvider(), array(
            "swagger.srcDir" => __DIR__ . "/../../../../../vendor/zircote/swagger-php/library",
            "swagger.servicePath" => __DIR__,
            "swagger.excludePath" => __DIR__ . "/Exclude",
        ));
    }

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
        $this->app["swagger.apiDocPath"] = $apiDocPath;

        // Test resource list
        $client = new Client($this->app);
        $client->request("GET", $apiDocPath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($this->app["swagger"]->getResourceList($this->app["swagger.prettyPrint"]), $response->getContent());

        // Test resource
        $client = new Client($this->app);
        $client->request("GET", $resourcePath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($this->app["swagger"]->getResource($resource, $this->app["swagger.prettyPrint"]), $response->getContent());

        // Test excluded resource
        $client = new Client($this->app);
        $client->request("GET", $excludePath);
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function dataProviderCaching()
    {
        return array(
            array(array("public" => true, "max_age" => "6"), array("Cache-Control" => "max-age=6, public")),
            array(array("public" => false, "s_maxage" => "6"), array("Cache-Control" => "private, s-maxage=6")),
            array(array("last_modified" => new \DateTime("Thu, 11 Jul 2013 07:22:49 GMT")), array("Cache-Control" => "private, must-revalidate", "Last-Modified" => "Thu, 11 Jul 2013 07:22:49 GMT")),
        );
    }

    /**
     * @dataProvider dataProviderCaching
     */
    public function testCaching($cache, $expectedHeaders)
    {
        $this->app["swagger.cache"] = $cache;

        $client = new Client($this->app);
        $client->request("GET", "/api-docs.json");
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        foreach ($expectedHeaders as $header => $value) {
            $this->assertEquals($value, $response->headers->get($header));
        }
    }

    public function testNotModified()
    {
        $json = $this->app["swagger"]->getResourceList($this->app["swagger.prettyPrint"]);

        $client = new Client($this->app, array("HTTP_IF_NONE_MATCH" => '"' . md5($json) . '"'));
        $client->request("GET", "/api-docs.json");
        $response = $client->getResponse();

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals("", $response->getContent());

        // Responses without content should not have a Content-Type header.  This appears to be a bug in Symfony 2.
//        $this->assertFalse($response->headers->has("Content-Type"));
    }

    public function testModified()
    {
        $json = $this->app["swagger"]->getResourceList($this->app["swagger.prettyPrint"]);

        $client = new Client($this->app, array("HTTP_IF_NONE_MATCH" => '"49fe5e81e4d90156fbef0a3ae347777f"'));
        $client->request("GET", "/api-docs.json");
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($json, $response->getContent());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
    }

    public function testLogging()
    {
        $this->app["swagger.excludePath"] = null;

        if (class_exists("Swagger\Logger")) {
            $this->app["logger"] = $this->getMock("Psr\Log\LoggerInterface", array("emergency", "alert", "critical", "warning", "notice", "error", "info", "debug", "log"));

            $this->app["logger"]->expects($this->once())
                    ->method("warning")
                    ->with("Resource \"http://localhost:8000\" doesn't have any valid api calls");
        }

        $client = new Client($this->app);
        $client->request("GET", "/api-docs.json");
        $client->getResponse();
    }
}
