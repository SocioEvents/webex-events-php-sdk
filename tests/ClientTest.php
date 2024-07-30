<?php


use WebexEvents\Client;
use PHPUnit\Framework\TestCase;
use WebexEvents\Configuration;
use WebexEvents\Exceptions\AccessTokenIsExpiredError;
use WebexEvents\Exceptions\BadGatewayError;
use WebexEvents\Exceptions\InvalidAccessTokenError;
use WebexEvents\RequestOptions;
use Mockery as m;

require_once 'TestDataHelper.php';

class ClientTest extends TestCase
{

    public function mockHttpClient(array $response, int $callCount = 1): void
    {
        $mockHttpClient = m::mock('overload:WebexEvents\HttpClient',\WebexEvents\HttpClientInterface::class);
        $mockHttpClient->shouldReceive('post')
            ->times($callCount)
            ->withAnyArgs()
            ->andReturn($response);
    }

    public function mockHttpClientWithArgumentCheck(array $response, int $callCount, callable $argumentChecker): void
    {
        $mockHttpClient = m::mock('overload:WebexEvents\HttpClient',\WebexEvents\HttpClientInterface::class);
        $mockHttpClient->shouldReceive('post')
            ->times($callCount)
            ->withAnyArgs()
            ->withArgs(function (...$args) use ($argumentChecker) {
                $argumentChecker(...$args);
                return true;
            })
            ->andReturn($response);
    }

    protected function setUp(): void
    {
        //before each test
    }

    protected function tearDown(): void
    {
        //after each test
        m::close();
    }

    public static function setUpBeforeClass(): void
    {
        Configuration::setAccessToken('sk_test_token');
        Configuration::setMaxRetries(3);
    }

    public function testSuccessResponse(): void
    {
        $success_response = TestDataHelper::getHTTPClientResult();

        $this->mockHttpClient($success_response);

        $requestOptions = new RequestOptions(null);
        $query = "query Query {\n  currenciesList {\n    isoCode\n  }\n}";
        $response = Client::query($query, 'currenciesList', null, $requestOptions);

        $data = $response->getJsonResponseBody()['data'];
        $this->assertCount(12, $data['currenciesList']);
        $this->assertEquals(200, $response->getHttpStatusCode());
        $this->assertEquals(62, $response->getRateLimiter()->getUsedDailyBasedCost());
        $this->assertEquals(2000, $response->getRateLimiter()->getDailyBasedCostThreshold());
        $this->assertEquals(1, $response->getRateLimiter()->getUsedSecondBasedCost());
        $this->assertEquals(500, $response->getRateLimiter()->getSecondBasedCostThreshold());
    }

    public function testSuccessWithVariableQueryResponse(): void
    {
        $success_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 200,
            'responseHeaders' => TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"data":{"eventsConnection":{"edges":[{"cursor":"WzJd","node":{"id":2,"name":"Sandbox Event Title"}},{"cursor":"WzRd","node":{"id":4,"name":"test"}},{"cursor":"Wzdd","node":{"id":7,"name":"Clone - Sandbox Event Title"}},{"cursor":"Wzhd","node":{"id":8,"name":"test 2"}}]}}}',
            'totalTimeMs' => 569,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"query EventsConnection($first: Int) {\\n          eventsConnection(first: $first){\\n              edges{\\n                  cursor\\n                  node{\\n                      id\\n                      name\\n                  }\\n              }\\n          }\\n      }","operation_name":"eventsConnection","variables":{"first":20}}'
        ];
        $operationName = 'eventsConnection';
        $idempotencyKey = '6f7f544c-ba64-4ecf-8397-71e19f2d2bb2';
        $query = 'query EventsConnection($first: Int) { eventsConnection(first: $first){ edges{ cursor node{ id name } } } }';
        $variables = ['first' => 20];
        $requestOptions = new RequestOptions($idempotencyKey);

        //test function HttpClient parameters
        $checkArgs = function (...$args) use ($idempotencyKey, $operationName, $variables, $query) {
            $this->assertEquals($query, $args[1]['query']);
            $this->assertEquals($operationName, $args[1]['operation_name']);
            $this->assertEquals($variables, $args[1]['variables']);
            $this->assertContains('Idempotency-Key: ' . $idempotencyKey, $args[2]);
        };

        //mock HttpClient
        $this->mockHttpClientWithArgumentCheck($success_response, 1, $checkArgs);

        //under test
        $response = Client::query($query, $operationName, $variables, $requestOptions);

        $this->assertCount(4, $response->getJsonResponseBody()['data']['eventsConnection']['edges']);
        $this->assertEquals(200, $response->getHttpStatusCode());
        $this->assertEquals(62, $response->getRateLimiter()->getUsedDailyBasedCost());
        $this->assertEquals(2000, $response->getRateLimiter()->getDailyBasedCostThreshold());
        $this->assertEquals(1, $response->getRateLimiter()->getUsedSecondBasedCost());
        $this->assertEquals(500, $response->getRateLimiter()->getSecondBasedCostThreshold());
    }

    public function testInvalidTokenResponse(): void
    {
        $invalid_token_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 400,
            'responseHeaders' => TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"message":"Invalid Access Token.","extensions":{"code":"INVALID_TOKEN"}}',
            'totalTimeMs' => 569,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"query Query {\\n  currenciesList {\\n    isoCode\\n  }\\n}","operation_name":"currenciesList"}'
        ];

        $this->mockHttpClient($invalid_token_response);

        $query = "query Query {\n  currenciesList {\n    isoCode\n  }\n}";

        $this->expectException(InvalidAccessTokenError::class);
        $requestOptions = new RequestOptions(null);
        $response = Client::query($query, 'currenciesList', null, $requestOptions);
    }

    public function testExpiredTokenResponse(): void
    {
        $invalid_token_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 400,
            'responseHeaders' => TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"message":"Access Token is expired.","extensions":{"code":"TOKEN_IS_EXPIRED"}}',
            'totalTimeMs' => 569,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"query Query {\\n  currenciesList {\\n    isoCode\\n  }\\n}","operation_name":"currenciesList"}'
        ];

        $this->mockHttpClient($invalid_token_response);

        $query = "query Query {\n  currenciesList {\n    isoCode\n  }\n}";

        $this->expectException(AccessTokenIsExpiredError::class);
        $requestOptions = new RequestOptions(null);
        $response = Client::query($query, 'currenciesList', null, $requestOptions);
    }

    public function testRetryBadGatewayResponse(): void
    {
        $bad_gateway_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 502,
            'responseHeaders' => array(
                'CONTENT-TYPE' => 'application/json; charset=utf-8'
            ),
            'responseBodyString' => 'Bad Gateway',
            'totalTimeMs' => 569,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"query Query {\\n  currenciesList {\\n    isoCode\\n  }\\n}","operation_name":"currenciesList"}'
        ];

        $this->mockHttpClient($bad_gateway_response, 3);

        $query = "query Query {\n  currenciesList {\n    isoCode\n  }\n}";

        $this->expectException(BadGatewayError::class);
        $requestOptions = new RequestOptions(null);
        try {
            Client::query($query, 'currenciesList', null, $requestOptions);
        } catch (BadGatewayError $e) {
            $this->assertEquals(3, $e->getResponse()->getRetryCount());
            $this->assertCount(0, $e->getResponse()->getJsonResponseBody());
            $this->assertEquals('Bad Gateway', $e->getResponse()->getResponseBodyString());
            $this->assertEquals(null, $e->getResponse()->getRateLimiter()->getUsedDailyBasedCost());
            throw $e;
        }
    }

    public function testRetryBadGatewayResponseAndReturnSuccess(): void
    {
        $bad_gateway_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 502,
            'responseHeaders' => array(
                'CONTENT-TYPE' => 'application/json; charset=utf-8'
            ),
            'responseBodyString' => 'Bad Gateway',
            'totalTimeMs' => 569,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"query Query {\\n  currenciesList {\\n    isoCode\\n  }\\n}","operation_name":"currenciesList"}'
        ];
        $success_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 200,
            'responseHeaders' => TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"data":{"currenciesList":[{"isoCode":"USD"},{"isoCode":"EUR"},{"isoCode":"GBP"},{"isoCode":"AUD"},{"isoCode":"CAD"},{"isoCode":"SGD"},{"isoCode":"NZD"},{"isoCode":"CHF"},{"isoCode":"MXN"},{"isoCode":"THB"},{"isoCode":"BRL"},{"isoCode":"SEK"}]}}',
            'totalTimeMs' => 569,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"query Query {\\n  currenciesList {\\n    isoCode\\n  }\\n}","operation_name":"currenciesList"}'
        ];

        $mockHttpClient = m::mock('overload:WebexEvents\HttpClient', \WebexEvents\HttpClientInterface::class);
        $mockHttpClient->shouldReceive('post')
            ->times(2)
            ->withAnyArgs()
            ->andReturn($bad_gateway_response);

        $mockHttpClient->shouldReceive('post')
            ->times(1)
            ->withAnyArgs()
            ->andReturn($success_response);



        $query = "query Query {\n  currenciesList {\n    isoCode\n  }\n}";

        $requestOptions = new RequestOptions(null);

        $response = Client::query($query, 'currenciesList', null, $requestOptions);

        $data = $response->getJsonResponseBody()['data'];
        $this->assertCount(12, $data['currenciesList']);
        $this->assertEquals(200, $response->getHttpStatusCode());
        $this->assertEquals(2, $response->getRetryCount());
        $this->assertEquals(62, $response->getRateLimiter()->getUsedDailyBasedCost());
        $this->assertEquals(2000, $response->getRateLimiter()->getDailyBasedCostThreshold());
        $this->assertEquals(1, $response->getRateLimiter()->getUsedSecondBasedCost());
        $this->assertEquals(500, $response->getRateLimiter()->getSecondBasedCostThreshold());
    }

    public function testSuccessComponentCreateMutationResponse(): void
    {
        $success_mutation_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 200,
            'responseHeaders' =>TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"data":{"componentCreate":{"eventId":8,"featureTypeId":6,"id":86,"name":"Component1"}}}',
            'totalTimeMs' => 569,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"mutation ComponentCreate($input: ComponentCreateInput!) {\\n  componentCreate(input: $input) {\\n    eventId\\n    featureTypeId\\n    id\\n    name\\n  }\\n}","operation_name":"ComponentCreate","variables":{"input":{"eventId":8,"featureTypeId":6,"name":"Component1","pictureUrl":"https:\\/\\/media.socio.events\\/","settings":{"displayMethod":"GRID","isHidden":false}}}}'
        ];
        $operationName = 'ComponentCreate';
        $idempotencyKey = '6f7f544c-ba64-4ecf-8397-71e19f2d2bb2';
        $mutation = 'mutation ComponentCreate($input: ComponentCreateInput!) {
          componentCreate(input: $input) {
            eventId
            featureTypeId
            id
            name
          }
        }';
        $mutationVariables = [
            "input" => [
                "eventId" => 8,
                "featureTypeId" => 6,
                "name" => "Component1",
                "pictureUrl" => 'https://media.socio.events/',
                "settings" => [
                    "displayMethod" => "GRID",
                    "isHidden" => false
                ]
            ]
        ];
        $requestOptions = new RequestOptions($idempotencyKey);

        //test function HttpClient parameters
        $checkArgs = function (...$args) use ($idempotencyKey, $operationName, $mutationVariables, $mutation) {
            $this->assertEquals($mutation, $args[1]['query']);
            $this->assertEquals($operationName, $args[1]['operation_name']);
            $this->assertEquals(json_encode($mutationVariables), json_encode($args[1]['variables']));
            $this->assertContains('Idempotency-Key: ' . $idempotencyKey, $args[2]);
        };

        //mock HttpClient
        $this->mockHttpClientWithArgumentCheck($success_mutation_response, 1, $checkArgs);

        //under test
        $response = Client::query($mutation, $operationName, $mutationVariables, $requestOptions);

        $this->assertEquals(8, $response->getJsonResponseBody()['data']['componentCreate']['eventId']);
        $this->assertEquals(6, $response->getJsonResponseBody()['data']['componentCreate']['featureTypeId']);
        $this->assertEquals(86, $response->getJsonResponseBody()['data']['componentCreate']['id']);
        $this->assertEquals('Component1', $response->getJsonResponseBody()['data']['componentCreate']['name']);
        $this->assertEquals(200, $response->getHttpStatusCode());
        $this->assertEquals(62, $response->getRateLimiter()->getUsedDailyBasedCost());
        $this->assertEquals(2000, $response->getRateLimiter()->getDailyBasedCostThreshold());
        $this->assertEquals(1, $response->getRateLimiter()->getUsedSecondBasedCost());
        $this->assertEquals(500, $response->getRateLimiter()->getSecondBasedCostThreshold());
    }

    public function testMissingIdempotencyKeyResponse(): void
    {
        $failed_mutation_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 400,
            'responseHeaders' => TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"message":"This operation is idempotent and it requires correct usage of Idempotency Key.","extensions":{"code":"BAD_REQUEST"}}',
            'totalTimeMs' => 576,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"mutation ComponentCreate($input: ComponentCreateInput!) {\\n                  componentCreate(input: $input) {\\n                    eventId\\n                    featureTypeId\\n                    id\\n                    name\\n                  } }","operation_name":"ComponentCreate","variables":{"input":{"eventId":8,"featureTypeId":6,"name":"ComponentGorkem","pictureUrl":"https:\\/\\/media.socio.events\\/","settings":{"displayMethod":"GRID","isHidden":false}}}}',
        ];
        $operationName = 'ComponentCreate';

        $mutation = 'mutation ComponentCreate($input: ComponentCreateInput!) {
          componentCreate(input: $input) {
            eventId
            featureTypeId
            id
            name
          }
        }';
        $mutationVariables = [
            "input" => [
                "eventId" => 8,
                "featureTypeId" => 6,
                "name" => "Component1",
                "pictureUrl" => 'https://media.socio.events/',
                "settings" => [
                    "displayMethod" => "GRID",
                    "isHidden" => false
                ]
            ]
        ];
        $requestOptions = new RequestOptions(null);

        //test function HttpClient parameters
        $checkArgs = function (...$args) use ($operationName, $mutationVariables, $mutation) {
            $this->assertEquals($mutation, $args[1]['query']);
            $this->assertEquals($operationName, $args[1]['operation_name']);
            $this->assertEquals(json_encode($mutationVariables), json_encode($args[1]['variables']));
        };

        //mock HttpClient
        $this->mockHttpClientWithArgumentCheck($failed_mutation_response, 1, $checkArgs);

        $this->expectException(\WebexEvents\Exceptions\BadRequestError::class);
        //under test
        Client::query($mutation, $operationName, $mutationVariables, $requestOptions);
    }

    public function testWrongFormatIdempotencyKeyResponse(): void
    {
        $failed_mutation_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 400,
            'responseHeaders' => TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"message":"This operation is idempotent and it requires UUID as Idempotency Key. Your key is not a UUID format.","extensions":{"code":"BAD_REQUEST"}}',
            'totalTimeMs' => 576,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"mutation ComponentCreate($input: ComponentCreateInput!) {\\n                  componentCreate(input: $input) {\\n                    eventId\\n                    featureTypeId\\n                    id\\n                    name\\n                  } }","operation_name":"ComponentCreate","variables":{"input":{"eventId":8,"featureTypeId":6,"name":"ComponentGorkem","pictureUrl":"https:\\/\\/media.socio.events\\/","settings":{"displayMethod":"GRID","isHidden":false}}}}',
        ];
        $operationName = 'ComponentCreate';

        $mutation = 'mutation ComponentCreate($input: ComponentCreateInput!) {
          componentCreate(input: $input) {
            eventId
            featureTypeId
            id
            name
          }
        }';
        $invalidUUIDFormatIdempotencyKey = 'bad_uuid';
        $mutationVariables = [
            "input" => [
                "eventId" => 8,
                "featureTypeId" => 6,
                "name" => "Component1",
                "pictureUrl" => 'https://media.socio.events/',
                "settings" => [
                    "displayMethod" => "GRID",
                    "isHidden" => false
                ]
            ]
        ];
        $requestOptions = new RequestOptions($invalidUUIDFormatIdempotencyKey);

        //test function HttpClient parameters
        $checkArgs = function (...$args) use ($operationName, $mutationVariables, $mutation, $invalidUUIDFormatIdempotencyKey) {
            $this->assertEquals($mutation, $args[1]['query']);
            $this->assertEquals($operationName, $args[1]['operation_name']);
            $this->assertEquals(json_encode($mutationVariables), json_encode($args[1]['variables']));
            $this->assertContains('Idempotency-Key: ' . $invalidUUIDFormatIdempotencyKey, $args[2]);
        };

        //mock HttpClient
        $this->mockHttpClientWithArgumentCheck($failed_mutation_response, 1, $checkArgs);

        $this->expectException(\WebexEvents\Exceptions\BadRequestError::class);
        //under test
        Client::query($mutation, $operationName, $mutationVariables, $requestOptions);
    }

    public function testUnauthorizedMutationResponse(): void
    {
        $failed_mutation_response = [
            'url' => 'https://public.sandbox-api.socio.events/graphql',
            'httpStatusCode' => 403,
            'responseHeaders' => TestDataHelper::responseHeadersSuccess(),
            'responseBodyString' => '{"message":"User does not have access","extensions":{"code":"UNAUTHORIZED"}}',
            'totalTimeMs' => 576,
            'requestHeaders' => TestDataHelper::requestHeadersSuccess(),
            'requestBody' => '{"query":"mutation ComponentCreate($input: ComponentCreateInput!) {\\n                  componentCreate(input: $input) {\\n                    eventId\\n                    featureTypeId\\n                    id\\n                    name\\n                  } }","operation_name":"ComponentCreate","variables":{"input":{"eventId":8,"featureTypeId":6,"name":"ComponentGorkem","pictureUrl":"https:\\/\\/media.socio.events\\/","settings":{"displayMethod":"GRID","isHidden":false}}}}',
        ];
        $operationName = 'ComponentCreate';

        $mutation = 'mutation ComponentCreate($input: ComponentCreateInput!) {
          componentCreate(input: $input) {
            eventId
            featureTypeId
            id
            name
          }
        }';
        $IdempotencyKey = '3c50c37c-98fb-4449-bdc4-0c5074cfe0e3';
        $mutationVariables = [
            "input" => [
                "eventId" => 1,
                "featureTypeId" => 6,
                "name" => "Component1",
                "pictureUrl" => 'https://media.socio.events/',
                "settings" => [
                    "displayMethod" => "GRID",
                    "isHidden" => false
                ]
            ]
        ];
        $requestOptions = new RequestOptions($IdempotencyKey);

        //test function HttpClient parameters
        $checkArgs = function (...$args) use ($operationName, $mutationVariables, $mutation, $IdempotencyKey) {
            $this->assertEquals($mutation, $args[1]['query']);
            $this->assertEquals($operationName, $args[1]['operation_name']);
            $this->assertEquals(json_encode($mutationVariables), json_encode($args[1]['variables']));
            $this->assertContains('Idempotency-Key: ' . $IdempotencyKey, $args[2]);
        };

        //mock HttpClient
        $this->mockHttpClientWithArgumentCheck($failed_mutation_response, 1, $checkArgs);

        $this->expectException(\WebexEvents\Exceptions\AuthorizationFailedError::class);
        //under test
        Client::query($mutation, $operationName, $mutationVariables, $requestOptions);
    }
}

