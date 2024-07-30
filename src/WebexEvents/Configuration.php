<?php

namespace  WebexEvents;

class Configuration
{
    private static string $accessToken = "";
    private static int $readTimeoutSeconds = 30;
    private static int $connectTimeoutSeconds = 10;
    private static int $maxRetries = 3;
    private static ?LoggerInterface $logger = null;


    public static function getAccessToken(): string
    {
        return self::$accessToken;
    }

    public static function setAccessToken(string $accessToken): void
    {
        self::$accessToken = $accessToken;
    }

    public static function getReadTimeoutSeconds(): int
    {
        return self::$readTimeoutSeconds;
    }

    public static function setReadTimeoutSeconds(int $readTimeoutSeconds): void
    {
        self::$readTimeoutSeconds = $readTimeoutSeconds;
    }

    public static function getConnectTimeoutSeconds(): int
    {
        return self::$connectTimeoutSeconds;
    }

    public static function setConnectTimeoutSeconds(int $connectTimeoutSeconds): void
    {
        self::$connectTimeoutSeconds = $connectTimeoutSeconds;
    }

    public static function getMaxRetries(): int
    {
        return self::$maxRetries;
    }

    public static function setMaxRetries(int $maxRetries): void
    {
        self::$maxRetries = $maxRetries;
    }

    public static function getLogger(): LoggerInterface
    {
        if (!self::$logger) {
            self::$logger = new Logger();
        }
        return self::$logger;
    }

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

}