<?php

namespace WebexEvents\Exceptions;

class GatewayTimeoutError extends WebexEventsBaseRequestError
{
    protected bool $retryable = true;
}