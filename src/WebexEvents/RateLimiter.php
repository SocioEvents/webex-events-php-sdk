<?php

namespace WebexEvents;

class RateLimiter
{
    private array $responseHeaders;
    private ?int $usedSecondBasedCost =null;
    private ?int $secondBasedCostThreshold=null;
    private ?int $usedDailyBasedCost=null;
    private ?int $dailyBasedCostThreshold=null;
    private ?int $dailyRetryAfterInSecond=null;
    private ?int $secondlyRetryAfterInMs=null;

    function __construct(array $responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;
        $this->parseSecondlyRetryAfterInMs();
        $this->parseDailyRetryAfter();
        $this->parseDailyBasedCost();
        $this->parseSecondBasedCost();
    }

    private function parseSecondlyRetryAfterInMs(): void
    {
        $this->secondlyRetryAfterInMs =  $this->intValFromHeader('X-SECONDLY-RETRY-AFTER');
    }

    private function parseDailyRetryAfter(): void
    {
        $this->dailyRetryAfterInSecond =  $this->intValFromHeader('X-DAILY-RETRY-AFTER');
    }

    private function parseDailyBasedCost(): void{
        $values = $this->parseUsedAndThreshold('X-DAILY-CALL-LIMIT');
        if($values === null)
            return;
        [$used, $threshold] = $values;
        $this->usedDailyBasedCost = intval($used);
        $this->dailyBasedCostThreshold = intval($threshold);
    }

    private function parseSecondBasedCost(): void{
        $values = $this->parseUsedAndThreshold('X-SECONDLY-CALL-LIMIT');
        if($values === null)
            return;
        [$used, $threshold] = $values;
        $this->usedSecondBasedCost = intval($used);
        $this->secondBasedCostThreshold = intval($threshold);
    }

    private function intValFromHeader(string $key): ?int{
        if(!array_key_exists($key,$this->responseHeaders))
            return null;

        $value = $this->responseHeaders[$key];
        if(!$value)
            return null;
        return intval($value);
    }

    private function parseUsedAndThreshold($key): ?array{
        if(!array_key_exists($key,$this->responseHeaders))
            return null;

        $values = $this->responseHeaders[$key];
        if(!$values)
            return null;

        [$used, $threshold] = explode("/",$values);
        return [$used, $threshold];
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function getUsedSecondBasedCost(): ?int
    {
        return $this->usedSecondBasedCost;
    }

    public function getSecondBasedCostThreshold(): ?int
    {
        return $this->secondBasedCostThreshold;
    }

    public function getUsedDailyBasedCost(): ?int
    {
        return $this->usedDailyBasedCost;
    }

    public function getDailyBasedCostThreshold(): ?int
    {
        return $this->dailyBasedCostThreshold;
    }

    public function getDailyRetryAfterInSecond(): ?int
    {
        return $this->dailyRetryAfterInSecond;
    }

    public function getSecondlyRetryAfterInMs(): ?int
    {
        return $this->secondlyRetryAfterInMs;
    }
}