<?php

use WebexEvents\RateLimiter;
use PHPUnit\Framework\TestCase;
require_once 'TestDataHelper.php';

class RateLimiterTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $headers = TestDataHelper::responseHeadersSuccess();

        $rateLimiter = new RateLimiter($headers);

        $this->assertEquals(62, $rateLimiter->getUsedDailyBasedCost());
        $this->assertEquals(2000, $rateLimiter->getDailyBasedCostThreshold());
        $this->assertEquals(1, $rateLimiter->getUsedSecondBasedCost());
        $this->assertEquals(500, $rateLimiter->getSecondBasedCostThreshold());
        $this->assertEquals(null, $rateLimiter->getSecondlyRetryAfterInMs());
        $this->assertEquals(null, $rateLimiter->getDailyRetryAfterInSecond());
    }

    public function testRetryAfterResponse(): void
    {
        $headers = TestDataHelper::responseRetryAfterHeaders();

        $rateLimiter = new RateLimiter($headers);

        $this->assertEquals(500, $rateLimiter->getUsedDailyBasedCost());
        $this->assertEquals(2000, $rateLimiter->getDailyBasedCostThreshold());
        $this->assertEquals(500, $rateLimiter->getUsedSecondBasedCost());
        $this->assertEquals(500, $rateLimiter->getSecondBasedCostThreshold());
        $this->assertEquals(100, $rateLimiter->getSecondlyRetryAfterInMs());
        $this->assertEquals(200, $rateLimiter->getDailyRetryAfterInSecond());

    }
}
