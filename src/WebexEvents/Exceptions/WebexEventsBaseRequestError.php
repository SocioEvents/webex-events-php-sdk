<?php

namespace  WebexEvents\Exceptions;

use Exception;
use Throwable;
use WebexEvents\Response;


abstract class WebexEventsBaseRequestError extends Exception{
    protected Response $response;
    protected bool $retryable = false;

    public function __construct(Response $response, $code = 0, Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($response->getResponseBodyString(), $code, $previous);
    }
    public function isRetryable(): bool
    {
        return $this->retryable;
    }
    public function getResponse(): Response
    {
        return $this->response;
    }
}
