<?php

namespace WebexEvents\Exceptions;

class ServiceUnavailableError extends WebexEventsBaseRequestError
{
    protected bool $retryable = true;
}