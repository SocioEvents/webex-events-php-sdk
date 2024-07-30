<?php

namespace WebexEvents;


use WebexEvents\Exceptions\AccessTokenIsExpiredError;
use WebexEvents\Exceptions\AuthenticationRequiredError;
use WebexEvents\Exceptions\AuthorizationFailedError;
use WebexEvents\Exceptions\BadRequestError;
use WebexEvents\Exceptions\ConflictError;
use WebexEvents\Exceptions\HttpClientError;
use WebexEvents\Exceptions\DailyQuotaIsReachedError;
use WebexEvents\Exceptions\GatewayTimeoutError;
use WebexEvents\Exceptions\InvalidAccessTokenError;
use WebexEvents\Exceptions\NullStatusError;
use WebexEvents\Exceptions\QueryComplexityIsTooHighError;
use WebexEvents\Exceptions\RequestTimeoutError;
use WebexEvents\Exceptions\ResourceNotFoundError;
use WebexEvents\Exceptions\SecondBasedQuotaIsReachedError;
use WebexEvents\Exceptions\ServerError;
use WebexEvents\Exceptions\ServiceUnavailableError;
use WebexEvents\Exceptions\TooManyRequestError;
use WebexEvents\Exceptions\UnprocessableEntityError;
use  WebexEvents\Exceptions\BadGatewayError;


class Request
{
    private string $query;
    private string $operationName;
    private ?array $variables;
    private RequestOptions $requestOptions;
    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;

    /**
     * @param string $query
     * @param string $operationName
     * @param array|null $variables
     * @param RequestOptions $requestOptions
     */
    public function __construct(string $query, string $operationName, ?array $variables, RequestOptions $requestOptions)
    {
        $this->query = $query;
        $this->operationName = $operationName;
        $this->variables = $variables;
        $this->requestOptions = $requestOptions;
        $this->logger = Configuration::getLogger();
        $this->httpClient = new HttpClient($requestOptions);
    }

    /**
     * @throws HttpClientError
     * @throws ServiceUnavailableError
     * @throws BadRequestError
     * @throws UnprocessableEntityError
     * @throws AuthenticationRequiredError
     * @throws DailyQuotaIsReachedError
     * @throws InvalidAccessTokenError
     * @throws QueryComplexityIsTooHighError
     * @throws ResourceNotFoundError
     * @throws AuthorizationFailedError
     * @throws ConflictError
     * @throws NullStatusError
     * @throws BadGatewayError
     * @throws RequestTimeoutError
     * @throws SecondBasedQuotaIsReachedError
     * @throws GatewayTimeoutError
     * @throws ServerError
     * @throws AccessTokenIsExpiredError
     */
    public function post(): Response
    {
        $data = [
            "query" => $this->query,
            "operation_name" => $this->operationName,
        ];

        if ($this->variables != null && count($this->variables) != 0) {
            $data["variables"] = $this->variables;
        }

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->requestOptions->getAccessToken(),
            "X-Sdk-Name: PHP SDK",
            "X-Sdk-Version: " . Helpers::getSdkVersion(),
            "X-Sdk-Lang-Version: " . phpversion(),
            "User-Agent: " . Helpers::getUserAgent(),
            "Accept: " . 'application/json',
        ];
        if ($this->requestOptions->getIdempotencyKey() != null) {
            $headers = array_merge($headers, ['Idempotency-Key: ' . $this->requestOptions->getIdempotencyKey()]);
        }

        $rawResponse = $this->httpClient->post($this->getUrl(), $data, $headers);
        $response = new Response($rawResponse);
        $this->throwIfNotSucceeded($response);

        return $response;
    }

    /**
     * @throws ServiceUnavailableError
     * @throws UnprocessableEntityError
     * @throws AuthenticationRequiredError
     * @throws DailyQuotaIsReachedError
     * @throws InvalidAccessTokenError
     * @throws QueryComplexityIsTooHighError
     * @throws ResourceNotFoundError
     * @throws AuthorizationFailedError
     * @throws ConflictError
     * @throws NullStatusError
     * @throws BadGatewayError
     * @throws RequestTimeoutError
     * @throws SecondBasedQuotaIsReachedError
     * @throws GatewayTimeoutError
     * @throws ServerError
     * @throws AccessTokenIsExpiredError
     * @throws BadRequestError
     */
    private function throwIfNotSucceeded(Response $response): void
    {
        if ($response->getHttpStatusCode() == 200)
            return;

        switch ($response->getHttpStatusCode()) {
            case 400:
                if(!array_key_exists('extensions',$response->getJsonResponseBody()))
                    throw new BadRequestError($response);

                switch ($response->getJsonResponseBody()["extensions"]['code']) {
                    case "TOKEN_IS_REVOKED":
                    case "INVALID_TOKEN":
                    case "JWT_TOKEN_IS_INVALID":
                        throw new InvalidAccessTokenError($response);
                    case "TOKEN_IS_EXPIRED":
                    case "JWT_TOKEN_IS_EXPIRED":
                        throw new AccessTokenIsExpiredError($response);
                    default:
                        throw new BadRequestError($response);
                }
            case 401:
                throw new AuthenticationRequiredError($response);
            case 403:
                throw new AuthorizationFailedError($response);
            case 404:
                throw new ResourceNotFoundError($response);
            case 408:
                throw new RequestTimeoutError($response);
            case 409:
                throw new ConflictError($response);
            case 413:
                throw new QueryComplexityIsTooHighError($response);
            case 422:
                throw new UnprocessableEntityError($response);
            case 429:
                if(array_key_exists('extensions',$response->getJsonResponseBody()))
                {
                    $extensions = $response->getJsonResponseBody()['extensions'];
                    if (intval($extensions['dailyAvailableCost']) < 1) {
                        throw new DailyQuotaIsReachedError($response);
                    }
                    if (intval($extensions['availableCost']) < 1) {
                        throw new SecondBasedQuotaIsReachedError($response);
                    }
                }
                throw new TooManyRequestError($response);
            case 500:
                throw new ServerError($response);
            case 502:
                throw new BadGatewayError($response);
            case 503:
                throw new ServiceUnavailableError($response);
            case 504:
                throw new GatewayTimeoutError($response);
            case null:
                throw new NullStatusError($response);
        }
    }

    private function getUrl(): string
    {
        return Helpers::getUri(Configuration::getAccessToken());
    }
}