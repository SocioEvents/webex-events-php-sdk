[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.txt)
[![Webex Events](https://github.com/SocioEvents/webex-events-php-sdk/actions/workflows/php.yml/badge.svg)](https://github.com/SocioEvents/webex-events-php-sdk/actions)


[![Webex EVENTS](webex-events-logo-white.svg 'Webex Events')](https://socio.events)

# Webex Events Api Php SDK

Webex Events provides a range of additional SDKs to accelerate your development process.
They allow a standardized way for developers to interact with and leverage the features and functionalities. 
Pre-built code modules will help access the APIs with your private keys, simplifying data gathering and update flows.

Requirements
-----------------

- PHP ^8.1

Installation
-----------------

Via command line:

```
composer require socioevents/webex-events-php-sdk
```

Configuration
-----------------

```php
    Configuration::setAccessToken('access token');
    Configuration::setLogger($logger); // implement your logger via LoggerInterface, default is off
    Configuration::setMaxRetries(3); //optional default 3
    Configuration::setReadTimeoutSeconds(30); //optional default 30 sec
    Configuration::setConnectTimeoutSeconds(10); //optional default 10 sec
```

Usage
-----------------

```php
  $query = 'query EventsConnection($first: Int) {
          eventsConnection(first: $first){
              edges{
                  cursor
                  node{
                      id
                      name
                  }
              }
          }
      }';
    $variables = ["first" => 100];
    $requestOptions = new RequestOptions(Helpers::generateUUID());
    
    $response = Client::query($query, 'eventsConnection', $variables, $requestOptions );
    $data = $response->getJsonResponseBody()['data'];
    
```

If the request is successful, `WebexEvents\Client.query` will return `WebexEvents\Response` object which has the following methods.

| Method               | Type                                                                                                 |
|----------------------|------------------------------------------------------------------------------------------------------|
| `httpStatusCode`     | `int`                                                                                                |
| `responseHeaders`    | `array`                                                                                              |
| `jsonResponseBody`   | `?array` (lazy loaded)                                                                               |
| `responseBodyString` | `string`                                                                                             |
| `requestHeaders`     | `array`                                                                                              |
| `requestBody`        | `array`                                                                                              |
| `url`                | `string`                                                                                             |
| `retryCount`         | `int`                                                                                                |
| `$timeSpendInMs`     | `int`                                                                                                |
| `rateLimiter`        | [`WebexEvents\RateLimiter`](https://github.com/SocioEvents/webex-events-php-sdk/src/RateLimiter.php) |


For non 200 status codes, an exception is raised for every status code such as `WebexEvents\Errors\ServerError` for server errors. 
For the flow-control these exceptions should be handled like the following. This is an example for `429` status code.
For the full list please refer to [this](https://github.com/SocioEvents/webex-events-php-sdk/blob/main/lib/webex/request.rb#L39) file.
```php
try {
    $response = Client::query($query, $operationName, $variables, $requestOptions);
}
catch (DailyQuotaIsReachedError $e) {
    // do someteging here
}
catch (SecondBasedQuotaIsReachedError $e){
    $sleepTime = $e->getResponse()->getRateLimiter()->getSecondlyRetryAfterInMs();
    usleep($sleepTime * 1000);
    // do retry
}

```
By default, `Webex::Client.query` has retry policy under the hood. It retries the request for the following exceptions according to the `Configuration::getMaxRetries()`.
```
WebexEvents\Errors\RequestTimeoutError => 408
WebexEvents\Errors\ConflictError => 409
WebexEvents\Errors\SecondBasedQuotaIsReachedError => 429
WebexEvents\Errors\BadGatewayError => 502
WebexEvents\Errors\ServiceUnavailableError => 503
WebexEvents\Errors\GatewayTimeoutError => 504
```

For Introspection
-----------------
```
WebexEvents\Client.doIntrospectionQuery()
```

Idempotency
-----------------
The API supports idempotency for safely retrying requests without accidentally performing the same operation twice. 
When doing a mutation request, use an idempotency key. If a connection error occurs, you can repeat 
the request without risk of creating a second object or performing the update twice.

To perform mutation request, you must add a header which contains the idempotency key such as 
`Idempotency-Key: <your key>`. The SDK does not produce an Idempotency Key on behalf of you if it is missed. Here is an example
like the following:

```php
    $mutation = 'mutation ComponentCreate($input: ComponentCreateInput!) {
                  componentCreate(input: $input) {
                    eventId
                    featureTypeId
                    id
                    name
                  } }';
    
    $mutationVariables = [
        "input" => [
            "eventId" => 1,
            "featureTypeId" => 6,
            "name" => "Speakers",
            "pictureUrl"=>'https://webexevents.com/media_test.jpeg',
            "settings" => [
                "displayMethod" => "GRID",
                "isHidden" => false
            ]
        ]
    ];
    
    try
    {
        $response = Client::query(
            $mutation,
            'ComponentCreate',
            $mutationVariables,
            new RequestOptions(Helpers::generateUUID())
        );    
    }
    catch (ConflictError $e)
    {
        // Conflict errors will be retried, but to guarantee it you can handle the exception again.
        usleep(20000);
        // do retry
    }
```

Telemetry Data Collection
-----------------
Webex Events collects telemetry data, including hostname, operating system, language and SDK version, via API requests. 
This information allows us to improve our services and track any usage-related faults/issues. We handle all data with 
the utmost respect for your privacy. For more details, please refer to the Privacy Policy at https://www.cisco.com/c/en/us/about/legal/privacy-full.html

Development
-----------------

After checking out the repo, `composer install` install dependencies. Then, run `./vendor/bin/phpunit tests` to run the tests.


Contributing
-----------------
Please see the [contributing guidelines](CONTRIBUTING.md).

License
-----------------

The gem is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).

Code of Conduct
-----------------

Everyone interacting in the Webex Events API project's codebases, issue trackers, chat rooms and mailing lists is expected to follow the [code of conduct](CODE_OF_CONDUCT.md).
