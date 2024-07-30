<?php

namespace  WebexEvents;

use WebexEvents\Exceptions\AccessTokenIsRequiredError;
use WebexEvents\Exceptions\HttpClientError;
use WebexEvents\Exceptions\WebexEventsBaseError;
use WebexEvents\Exceptions\WebexEventsBaseRequestError;

class Client
{

    /**
     * @throws AccessTokenIsRequiredError
     * @throws WebexEventsBaseRequestError
     * @throws HttpClientError|WebexEventsBaseError
     */
    public static function doIntrospectionQuery(): Response
    {
        return self::query(Helpers::getIntrospectionQuery(),'IntrospectionQuery',[], new RequestOptions(null));
    }

    /**
     * @throws AccessTokenIsRequiredError
     * @throws HttpClientError
     * @throws WebexEventsBaseRequestError|WebexEventsBaseError
     */
    public static function query(
        string         $query,
        string         $operationName,
        ?array         $variables,
        RequestOptions $requestOptions
    ): Response
    {
        Helpers::validateAccessTokenExistence();

        $logger = Configuration::getLogger();
        $request = new Request($query, $operationName, $variables, $requestOptions);
        $response = self::retryPost($requestOptions->getMaxRetries(), function ($request) {
            return $request->post();
        }, $request);

        $logger->debug("Executing query is finished with {$response->getHttpStatusCode()} status code. It took {$response->getTimeSpendInMs()} ms and retried {$response->getRetryCount()} times. query: {$query}");
        if ($response->getHttpStatusCode() > 299) {
            $logger->error("Executing query is failed. Received status code is {$response->getHttpStatusCode()}, query: {$query}");
        }

        return $response;
    }

    /**
     * @throws WebexEventsBaseRequestError
     * @throws WebexEventsBaseError
     */
    private static function retryPost($retryCount, callable $function, ...$args): Response
    {
        $logger = Configuration::getLogger();
        $attempts = 0;
        $lastException = null;
        do {
            try
            {
                $response = $function(...$args);
                $response->setRetryCount($attempts);
                return $response;
            }
            catch (WebexEventsBaseRequestError $e) {
                $lastException = $e;
                if(!$e->isRetryable())
                    throw $e;
                $attempts++;
                $sleep = min(3, $attempts);
                if ($attempts < $retryCount){
                    $logger->error("Post request returned an error, will be retried in {$sleep} sec, response {$e->getResponse()->getHttpStatusCode()}");
                    sleep($sleep);
                }
                else{
                    $lastException->getResponse()->setRetryCount($attempts);
                    throw $lastException;
                }
            }
        } while ($attempts < $retryCount);
        throw $lastException;
    }
}