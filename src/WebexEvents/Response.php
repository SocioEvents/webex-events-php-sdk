<?php

namespace WebexEvents;

class Response
{
    private string $requestBody;
    private int $retryCount = 0;
    private int $timeSpendInMs = 0;
    private RateLimiter $rateLimiter;
    private int $httpStatusCode;
    private ?string $responseBodyString;
    private ?array $jsonResponseBody = null;
    private array $responseHeaders = [];
    private array $requestHeaders = [];
    private string $url;
    private LoggerInterface $logger;

    function __construct($rawResponse, $retryCount = 0)
    {
        $this->logger = Configuration::getLogger();

        $this->responseHeaders = $rawResponse['responseHeaders'];
        $this->responseBodyString = $rawResponse['responseBodyString'];
        $this->requestHeaders = $rawResponse['requestHeaders'];
        $this->retryCount = $retryCount;
        $this->timeSpendInMs = intval($rawResponse['totalTimeMs']);
        $this->httpStatusCode = $rawResponse['httpStatusCode'];
        $this->requestBody = $rawResponse['requestBody'];
        $this->url = $rawResponse['url'];
        $this->rateLimiter = new RateLimiter($this->responseHeaders);
    }

    /**
     * @param $body
     * @return mixed
     */
    private function parseBody($body): mixed
    {
        $jsonBody = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonError = json_last_error();
            $this->logger->error("Response body json decode Error: {$jsonError}, responseBody: {$body}");
            $jsonBody = [];
        }
        return $jsonBody;
    }

    /**
     * @return RateLimiter
     */
    public function getRateLimiter(): RateLimiter
    {
        return $this->rateLimiter;
    }

    /**
     * @param RateLimiter $rateLimiter
     */
    public function setRateLimiter(RateLimiter $rateLimiter): void
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * @param int $httpStatusCode
     */
    public function setHttpStatusCode(int $httpStatusCode): void
    {
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * @param array $responseHeaders
     */
    public function setResponseHeaders(array $responseHeaders): void
    {
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    /**
     * @param array $requestHeaders
     */
    public function setRequestHeaders(array $requestHeaders): void
    {
        $this->requestHeaders = $requestHeaders;
    }

    public function getResponseBodyString(): string
    {
        return $this->responseBodyString ?: '';
    }

    public function getJsonResponseBody(): array
    {
        if ($this->jsonResponseBody === null) {
            $this->jsonResponseBody = $this->parseBody($this->getResponseBodyString());
        }
        return $this->jsonResponseBody;
    }

    public function setJsonResponseBody(array $jsonResponseBody): void
    {
        $this->jsonResponseBody = $jsonResponseBody;
    }

    /**
     * @return string
     */
    public function getRequestBody(): string
    {
        return $this->requestBody;
    }

    /**
     * @param string $requestBody
     */
    public function setRequestBody(string $requestBody): void
    {
        $this->requestBody = $requestBody;
    }

    /**
     * @return int
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * @param int $retryCount
     */
    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    /**
     * @return int
     */
    public function getTimeSpendInMs(): int
    {
        return $this->timeSpendInMs;
    }

    /**
     * @param int $timeSpendInMs
     */
    public function setTimeSpendInMs(int $timeSpendInMs): void
    {
        $this->timeSpendInMs = $timeSpendInMs;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}