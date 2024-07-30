<?php

namespace WebexEvents\Exceptions;

class BadGatewayError extends WebexEventsBaseRequestError
{
    protected bool $retryable = true;
}