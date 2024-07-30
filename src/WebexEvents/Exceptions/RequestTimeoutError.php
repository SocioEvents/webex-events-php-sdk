<?php

namespace WebexEvents\Exceptions;

class RequestTimeoutError extends WebexEventsBaseRequestError
{
    protected bool $retryable = true;
}