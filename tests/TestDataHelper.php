<?php

class TestDataHelper
{
    public static function responseHeadersSuccess(): array
    {
        return array(
            'DATE' => 'Tue, 23 Jul 2024 20:03:47 GMT',
            'CONTENT-TYPE' => 'application/json; charset=utf-8',
            'CONTENT-LENGTH' => '245',
            'X-FRAME-OPTIONS' => 'SAMEORIGIN',
            'X-XSS-PROTECTION' => '0',
            'X-CONTENT-TYPE-OPTIONS' => 'nosniff',
            'X-PERMITTED-CROSS-DOMAIN-POLICIES' => 'none',
            'REFERRER-POLICY' => 'strict-origin-when-cross-origin',
            'VARY' => 'Accept, Origin',
            'X-DAILY-CALL-LIMIT' => '62/2000',
            'X-SECONDLY-CALL-LIMIT' => '1/500',
            'ETAG' => 'W/"2d5bb6de22c82e2b954b669e42963f93"',
            'CACHE-CONTROL' => 'max-age=0, private, must-revalidate',
            'X-REQUEST-ID' => 'd877259b-8388-4087-b548-280702cc1b67',
            'X-RUNTIME' => '0.076380',
        );
    }

    public static function responseRetryAfterHeaders(): array
    {
        return array(
            'DATE' => 'Tue, 23 Jul 2024 20:03:47 GMT',
            'CONTENT-TYPE' => 'application/json; charset=utf-8',
            'CONTENT-LENGTH' => '245',
            'X-FRAME-OPTIONS' => 'SAMEORIGIN',
            'X-XSS-PROTECTION' => '0',
            'X-CONTENT-TYPE-OPTIONS' => 'nosniff',
            'X-PERMITTED-CROSS-DOMAIN-POLICIES' => 'none',
            'REFERRER-POLICY' => 'strict-origin-when-cross-origin',
            'VARY' => 'Accept, Origin',
            'X-DAILY-CALL-LIMIT' => '500/2000',
            'X-SECONDLY-CALL-LIMIT' => '500/500',
            'ETAG' => 'W/"2d5bb6de22c82e2b954b669e42963f93"',
            'CACHE-CONTROL' => 'max-age=0, private, must-revalidate',
            'X-REQUEST-ID' => 'd877259b-8388-4087-b548-280702cc1b67',
            'X-RUNTIME' => '0.076380',
            'X-SECONDLY-RETRY-AFTER' => '100',
            'X-DAILY-RETRY-AFTER' => '200'
        );
    }

    public static function requestHeadersSuccess(): array
    {
        return array(
            0 => 'Content-Type: application/json',
            1 => 'Authorization: Bearer sk_test_token',
            2 => 'X-Sdk-Name: PHP SDK',
            3 => 'X-Sdk-Version: 1.0.0',
            4 => 'X-Sdk-Lang-Version: 8.3.9',
            5 => 'User-Agent: WebexEventsPhpSDK',
            6 => 'Accept: application/json',
        );
    }

    public static function requestBodyString(): string {
        return '{"query":"query Query {\\n  currenciesList {\\n    isoCode\\n  }\\n}","operation_name":"currenciesList"}';
    }

    public static function responseBodyString(): string {
        return '{"data":{"currenciesList":[{"isoCode":"USD"},{"isoCode":"EUR"},{"isoCode":"GBP"},{"isoCode":"AUD"},{"isoCode":"CAD"},{"isoCode":"SGD"},{"isoCode":"NZD"},{"isoCode":"CHF"},{"isoCode":"MXN"},{"isoCode":"THB"},{"isoCode":"BRL"},{"isoCode":"SEK"}]}}';
    }

    public static function responseErrorBodyString($extensionCode): string {
        return '{"message":"Invalid Access Token.","extensions":{"code":"'.$extensionCode.'"}}';
    }

    public static function responseErrorCostString($dailyAvailableCost, $availableCost): string {
        return '{"message":"Invalid Access Token.","extensions":{"dailyAvailableCost":'.$dailyAvailableCost.', "availableCost": '.$availableCost.'}}';
    }

    public static function queryOperationNameVariables(): array {
        return ["query Query {\n  currenciesList {\n    isoCode\n  }\n}",'currenciesList', null];
    }

    public static function getHTTPClientResult(int $httpResponseCode = 200, ?string $responseBodyString = null): array
    {
        return [
            'url' => 'https://localhost/graphql',
            'httpStatusCode' => $httpResponseCode,
            'responseHeaders' => self::responseHeadersSuccess(),
            'responseBodyString' => $responseBodyString ?: self::responseBodyString(),
            'totalTimeMs' => 569,
            'requestHeaders' => self::requestHeadersSuccess(),
            'requestBody' => self::requestBodyString()
        ];
    }
}