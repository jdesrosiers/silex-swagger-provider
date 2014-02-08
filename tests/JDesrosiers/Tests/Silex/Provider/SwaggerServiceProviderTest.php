<?php

namespace JDesrosiers\Tests\Silex\Provider;

use JDesrosiers\Silex\Provider\SwaggerServiceProvider;
use Silex\Application;
use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

date_default_timezone_set("GMT");

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
        $configs = array(
            "swagger.basePath" => "http://localhost:8888",
            "swagger.apiVersion" => "1.1",
            "swagger.swaggerVersion" => "9.9.9",
        );

        return array(
            array("/api-docs", array(), "/foo", "/api-docs/foo", "/api-docs/baz"),
            array("/api-docs", array(), "/foo/bar", "/api-docs/foo-bar", "/api-docs/baz"),
            array("/api-docs", $configs, "/fooBarDefaults", "/api-docs/fooBarDefaults", "/api-docs/baz"),
            array("/api/api-docs", array(), "/foo", "/api/api-docs/foo", "/api/api-docs/baz"),
            array("/api/api-docs", array(), "/foo/bar", "/api/api-docs/foo-bar", "/api/api-docs/baz"),
            array("/api/api-docs", $configs, "/fooBarDefaults", "/api/api-docs/fooBarDefaults", "/api/api-docs/baz"),
        );
    }

    /**
     * @dataProvider dataProviderApiDocs
     */
    public function testApiDocs($apiDocPath, $options, $resource, $resourcePath, $excludePath)
    {
        $resourceList = array(
            "apiVersion" => isset($options["swagger.apiVersion"]) ? $options["swagger.apiVersion"] : "0.1",
            "swaggerVersion" => isset($options["swagger.swaggerVersion"]) ? $options["swagger.swaggerVersion"] : "1.2",
            "apis" => array(
                array(
                    "path" => "/foo",
                ),
                array(
                    "path" => "/foo-bar",
                ),
                array(
                    "path" => "/fooBarDefaults",
                ),
            ),
        );

        if (isset($options["swagger.basePath"])) {
            $resourceList["basePath"] = $options["swagger.basePath"];
        }

        // Ensure that swagger.basePath, .apiVersion and .swaggerVersion options are respected.
        foreach ($options as $key => $value) {
            $this->app[$key] = $value;
        }

        $options = array(
            "output" => "json",
            "json_pretty_print" => $this->app["swagger.prettyPrint"],
            "defaultBasePath" => $this->app["swagger.basePath"],
            "defaultApiVersion" => $this->app["swagger.apiVersion"],
            "defaultSwaggerVersion" => $this->app["swagger.swaggerVersion"],
        );
        $expectedResponse = $this->app["swagger"]->getResource($resource, $options);

        $this->app["swagger.apiDocPath"] = $apiDocPath;

        // Test resource list
        $client = new Client($this->app);
        $client->request("GET", $apiDocPath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($resourceList, json_decode($response->getContent(), true));

        // Test resource
        $client = new Client($this->app);
        $client->request("GET", $resourcePath);
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
        $this->assertEquals($expectedResponse, $response->getContent());

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
        $client->request("GET", "/api/api-docs");
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        foreach ($expectedHeaders as $header => $value) {
            $this->assertEquals($value, $response->headers->get($header));
        }
    }

    public function testNotModified()
    {
        $client = new Client($this->app);
        $client->request("GET", "/api/api-docs");
        $response = $client->getResponse();

        $client = new Client($this->app, array("HTTP_IF_NONE_MATCH" => $response->headers->get("ETag")));
        $client->request("GET", "/api/api-docs");
        $response = $client->getResponse();

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals("", $response->getContent());

        // Responses without content should not have a Content-Type header.  This appears to be a bug in Symfony 2.
//        $this->assertFalse($response->headers->has("Content-Type"));
    }

    public function testModified()
    {
        $client = new Client($this->app, array("HTTP_IF_NONE_MATCH" => '"49fe5e81e4d90156fbef0a3ae347777f"'));
        $client->request("GET", "/api/api-docs");
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(strlen($response->getContent()) > 0);
        $this->assertEquals("application/json", $response->headers->get("Content-Type"));
    }

    public function testLogging()
    {
        $this->app["swagger.excludePath"] = array();
        $this->app["swagger.servicePath"] = __DIR__ . "/../../../../../vendor/zircote/swagger-php";
        $realPath = realpath($this->app["swagger.servicePath"]);

        $this->app["logger"] = $this->getMock("Symfony\Component\HttpKernel\Log\LoggerInterface");
        $this->app["logger"]->expects($this->any())
            ->method("notice")
            ->with("Skipping files in \"$realPath/tests\" add your \"vendor\" directory to the exclude paths");

        $client = new Client($this->app);
        $client->request("GET", "/api/api-docs");
        $client->getResponse();
    }
}
