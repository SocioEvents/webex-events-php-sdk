<?php


use WebexEvents\Response;
use PHPUnit\Framework\TestCase;
require_once 'TestDataHelper.php';
class ResponseTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $httpResponse = TestDataHelper::getHTTPClientResult();

        $response = new Response($httpResponse);

        $this->assertEquals(200, $response->getHttpStatusCode());
        $this->assertEquals(62, $response->getRateLimiter()->getUsedDailyBasedCost());
        $this->assertEquals(2000, $response->getRateLimiter()->getDailyBasedCostThreshold());
        $this->assertEquals(1, $response->getRateLimiter()->getUsedSecondBasedCost());
        $this->assertEquals(500, $response->getRateLimiter()->getSecondBasedCostThreshold());
        $this->assertCount(12, $response->getJsonResponseBody()['data']['currenciesList']);
        $this->assertEquals(TestDataHelper::requestBodyString(), $response->getRequestBody());
        $this->assertEquals(TestDataHelper::responseBodyString(), $response->getResponseBodyString());

    }
}
