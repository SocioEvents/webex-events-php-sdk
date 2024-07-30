<?php

namespace  WebexEvents;


class RequestOptions
{
    private string $accessToken = "";
    private int $maxRetries = 0;
    private int $readTimeoutSeconds = 0;
    private int $connectTimeoutSeconds = 0;
    private ?string $idempotencyKey = null;

    function __construct(?string $idempotencyKey)
    {
        $this->accessToken = Configuration::getAccessToken();
        $this->maxRetries = Configuration::getMaxRetries();
        $this->readTimeoutSeconds = Configuration::getReadTimeoutSeconds();
        $this->connectTimeoutSeconds = Configuration::getConnectTimeoutSeconds();
        $this->idempotencyKey = $idempotencyKey;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->readTimeoutSeconds;
    }

    public function getConnectTimeoutSeconds(): int
    {
        return $this->connectTimeoutSeconds;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function setIdempotencyKey(?string $idempotencyKey): void
    {
        $this->idempotencyKey = $idempotencyKey;
    }
}