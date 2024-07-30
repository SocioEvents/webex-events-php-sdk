<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;
use WebexEvents\Configuration;
use WebexEvents\Exceptions\AccessTokenIsExpiredError;
use WebexEvents\Exceptions\AuthenticationRequiredError;
use WebexEvents\Exceptions\AuthorizationFailedError;
use WebexEvents\Exceptions\BadGatewayError;
use WebexEvents\Exceptions\BadRequestError;
use WebexEvents\Exceptions\ConflictError;
use WebexEvents\Exceptions\DailyQuotaIsReachedError;
use WebexEvents\Exceptions\GatewayTimeoutError;
use WebexEvents\Exceptions\InvalidAccessTokenError;
use WebexEvents\Exceptions\QueryComplexityIsTooHighError;
use WebexEvents\Exceptions\RequestTimeoutError;
use WebexEvents\Exceptions\ResourceNotFoundError;
use WebexEvents\Exceptions\SecondBasedQuotaIsReachedError;
use WebexEvents\Exceptions\ServerError;
use WebexEvents\Exceptions\ServiceUnavailableError;
use WebexEvents\Exceptions\TooManyRequestError;
use WebexEvents\Exceptions\UnprocessableEntityError;
use WebexEvents\Request;
use WebexEvents\RequestOptions;

require_once 'TestDataHelper.php';

class RequestTest extends TestCase
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
        Configuration::setAccessToken('sk_test_1');
        Configuration::setMaxRetries(3);
    }

    public function testSuccessResponse(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult();

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);
        $response = $request->post();
        $this->assertEquals(200, $response->getHttpStatusCode());
    }

    public function testThrowBadRequest(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(400, 'Bad request');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(BadRequestError::class);

        $request->post();
    }

    public function testThrowTokenIsRevoked(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(400,
            TestDataHelper::responseErrorBodyString('TOKEN_IS_REVOKED'));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(InvalidAccessTokenError::class);

        $request->post();
    }

    public function testThrowTokenIsInvalid(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(400,
            TestDataHelper::responseErrorBodyString('INVALID_TOKEN'));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(InvalidAccessTokenError::class);

        $request->post();
    }

    public function testThrowJWTTokenIsInvalid(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(400,
            TestDataHelper::responseErrorBodyString('JWT_TOKEN_IS_INVALID'));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(InvalidAccessTokenError::class);

        $request->post();
    }

    public function testThrowTokenExpired(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(400,
            TestDataHelper::responseErrorBodyString('TOKEN_IS_EXPIRED'));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(AccessTokenIsExpiredError::class);

        $request->post();
    }

    public function testThrowJWTTokenExpired(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(400,
            TestDataHelper::responseErrorBodyString('JWT_TOKEN_IS_EXPIRED'));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(AccessTokenIsExpiredError::class);

        $request->post();
    }

    public function testThrowUnauthorized(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(401, 'AuthenticationRequiredError');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(AuthenticationRequiredError::class);

        $request->post();
    }

    public function testThrowAuthorizationFailedError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(403, 'AuthorizationFailedError');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(AuthorizationFailedError::class);

        $request->post();
    }

    public function testThrowResourceNotFoundError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(404, 'ResourceNotFoundError');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(ResourceNotFoundError::class);

        $request->post();
    }
    
    public function testThrowRequestTimeoutError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(408, 'RequestTimeoutError');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(RequestTimeoutError::class);

        $request->post();
    }

    public function testThrowConflictError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(409, 'ConflictError');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(ConflictError::class);

        $request->post();
    }

    public function testThrowQueryComplexityIsTooHighError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(413, 'QueryComplexityIsTooHighError');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(QueryComplexityIsTooHighError::class);

        $request->post();
    }

    public function testThrowUnprocessableEntityError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(422, 'UnprocessableEntityError');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(UnprocessableEntityError::class);

        $request->post();
    }

    public function testThrowDailyQuotaIsReachedError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(429,
        TestDataHelper::responseErrorCostString(0,10));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(DailyQuotaIsReachedError::class);

        $request->post();
    }

    public function testThrowSecondBasedQuotaIsReachedError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(429,
            TestDataHelper::responseErrorCostString(10,0));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(SecondBasedQuotaIsReachedError::class);

        $request->post();
    }

    public function testThrowTooManyRequestError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(429,
            TestDataHelper::responseErrorCostString(10,10));

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(TooManyRequestError::class);

        $request->post();
    }

    public function testThrowServerError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(500,'Internal Server Error');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(ServerError::class);

        $request->post();
    }

    public function testThrowBadGatewayError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(502,'Bad Gateway');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(BadGatewayError::class);

        $request->post();
    }

    public function testThrowServiceUnavailableError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(503,'Bad Gateway');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(ServiceUnavailableError::class);

        $request->post();
    }

    public function testThrowGatewayTimeoutError(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult(504,'Bad Gateway');

        $this->mockHttpClient($httpResponse);

        $requestOptions = new RequestOptions(null);
        [$query, $operationName, $variables] = TestDataHelper::queryOperationNameVariables();
        $request = new Request($query, $operationName, $variables, $requestOptions);

        $this->expectException(GatewayTimeoutError::class);

        $request->post();
    }
}
